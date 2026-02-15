<?php
require_once __DIR__ . '/../../lib/db.php';
session_start();

// Check if admin/doctor is logged in
if (!isset($_SESSION['doctor_id'])) {
    header('Location: ./login.php');
    exit;
}

$pdo = get_db();
$doctor_id = $_SESSION['doctor_id'];

// Get doctor info
try {
    $stmt = $pdo->prepare('SELECT * FROM doctors WHERE id = ?');
    $stmt->execute([$doctor_id]);
    $doctor = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$doctor) {
        session_destroy();
        header('Location: ./login.php');
        exit;
    }
} catch (Exception $e) {
    die('Erreur: ' . htmlspecialchars($e->getMessage()));
}

// Handle status toggle
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'toggle_status') {
        $target_doctor_id = intval($_POST['doctor_id'] ?? 0);
        try {
            $stmt = $pdo->prepare('SELECT status FROM doctors WHERE id = ?');
            $stmt->execute([$target_doctor_id]);
            $target = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($target) {
                $new_status = $target['status'] === 'libre' ? 'occupé' : 'libre';
                $pdo->prepare('UPDATE doctors SET status = ? WHERE id = ?')->execute([$new_status, $target_doctor_id]);
                $msg = "✅ Statut mis à jour avec succès!";
            }
        } catch (Exception $e) {
            $msg = "❌ Erreur: " . htmlspecialchars($e->getMessage());
        }
    }
}

// Get all doctors
try {
    $all_doctors = $pdo->query('
        SELECT 
            d.*,
            COUNT(c.id) as consultations_today,
            SUM(CASE WHEN i.payment_status = "paid" THEN i.amount ELSE 0 END) as total_revenue
        FROM doctors d
        LEFT JOIN consultations c ON d.id = c.doctor_id AND DATE(c.date) = CURDATE()
        LEFT JOIN invoices i ON c.id = i.consultation_id
        GROUP BY d.id
        ORDER BY d.name ASC
    ')->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $all_doctors = [];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion Médecins - EasyConsult Admin</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .admin-container {
            min-height: 100vh;
            background: linear-gradient(135deg, #f0f9ff 0%, #f0fdf4 100%);
            padding: 2rem;
        }

        .admin-sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: 250px;
            height: 100vh;
            background: linear-gradient(180deg, #0066cc, #004a9f);
            color: white;
            padding: 2rem 1.5rem;
            overflow-y: auto;
            z-index: 100;
            box-shadow: 4px 0 20px rgba(0, 0, 0, 0.15);
        }

        .admin-sidebar h2 {
            margin-bottom: 2rem;
            font-size: 1.3rem;
        }

        .admin-menu {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .admin-menu li {
            margin-bottom: 1rem;
        }

        .admin-menu a {
            color: rgba(255, 255, 255, 0.85);
            text-decoration: none;
            padding: 0.75rem 1rem;
            border-radius: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            transition: all 0.3s ease;
        }

        .admin-menu a:hover,
        .admin-menu a.active {
            background: rgba(255, 255, 255, 0.15);
            color: white;
        }

        .admin-main {
            margin-left: 250px;
            padding: 2rem;
        }

        .admin-header {
            background: white;
            padding: 2rem;
            border-radius: 1rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }

        .admin-header h1 {
            margin: 0 0 0.5rem 0;
            color: #0066cc;
        }

        .admin-header p {
            margin: 0;
            color: #6b7280;
        }

        .content-section {
            background: white;
            padding: 2rem;
            border-radius: 1rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }

        .msg {
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1.5rem;
            font-weight: 500;
        }

        .msg.success {
            background: #dcfce7;
            color: #166534;
            border-left: 4px solid #10b981;
        }

        .doctor-card {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr;
            gap: 1rem;
            align-items: center;
            padding: 1.5rem;
            background: #f9fafb;
            border-radius: 0.75rem;
            margin-bottom: 1rem;
            border-left: 4px solid #e5e7eb;
            transition: all 0.3s ease;
        }

        .doctor-card:hover {
            background: white;
            border-left-color: #0066cc;
            box-shadow: 0 4px 12px rgba(0, 102, 204, 0.1);
        }

        .doctor-info h3 {
            margin: 0 0 0.3rem 0;
            color: #0066cc;
        }

        .doctor-info p {
            margin: 0.2rem 0;
            color: #6b7280;
            font-size: 0.9rem;
        }

        .doctor-stats {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 0.5rem;
            text-align: center;
        }

        .stat-mini {
            background: white;
            padding: 0.75rem;
            border-radius: 0.5rem;
        }

        .stat-mini .value {
            font-weight: 700;
            color: #0066cc;
            font-size: 1.2rem;
        }

        .stat-mini .label {
            color: #6b7280;
            font-size: 0.75rem;
        }

        .toggle-form {
            display: flex;
            gap: 0.5rem;
            align-items: center;
        }

        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            font-weight: 600;
            font-size: 0.9rem;
            text-align: center;
            min-width: 100px;
        }

        .status-libre {
            background: #dcfce7;
            color: #166534;
        }

        .status-occupe {
            background: #fee2e2;
            color: #991b1b;
        }

        .btn-toggle {
            background: linear-gradient(135deg, #0066cc, #004a9f);
            color: white;
            border: none;
            padding: 0.6rem 1.2rem;
            border-radius: 0.5rem;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-toggle:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 102, 204, 0.3);
        }

        @media (max-width: 768px) {
            .admin-sidebar {
                width: 200px;
            }

            .admin-main {
                margin-left: 200px;
            }

            .doctor-card {
                grid-template-columns: 1fr;
            }

            .doctor-stats {
                grid-template-columns: 1fr;
            }

            .toggle-form {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/_sidebar.php'; ?>

    <!-- Main Content -->
    <div class="admin-main">
        <div class="admin-header">
            <h1>👨‍⚕️ Gestion des Médecins</h1>
            <p>Modifiez les statuts et consultez les performances de chaque médecin</p>
        </div>

        <?php if (!empty($msg)): ?>
            <div class="msg success"><?php echo htmlspecialchars($msg); ?></div>
        <?php endif; ?>

        <div class="content-section">
            <h2 style="color: #0066cc; margin-bottom: 1.5rem;">Statuts et Performances</h2>
            
            <?php if (empty($all_doctors)): ?>
                <p style="color: #6b7280;">Aucun médecin trouvé.</p>
            <?php else: ?>
                <?php foreach ($all_doctors as $doc): ?>
                    <div class="doctor-card">
                        <!-- Infos -->
                        <div class="doctor-info">
                            <h3>Dr. <?php echo htmlspecialchars($doc['name']); ?></h3>
                            <p><strong>Spécialité:</strong> <?php echo htmlspecialchars($doc['specialty']); ?></p>
                            <p><strong>Horaires:</strong> <?php echo htmlspecialchars($doc['start_time']); ?> - <?php echo htmlspecialchars($doc['end_time']); ?></p>
                        </div>

                        <!-- Stats -->
                        <div class="doctor-stats">
                            <div class="stat-mini">
                                <div class="value"><?php echo $doc['consultations_today'] ?? 0; ?></div>
                                <div class="label">Aujourd'hui</div>
                            </div>
                            <div class="stat-mini">
                                <div class="value"><?php echo number_format($doc['total_revenue'] ?? 0); ?></div>
                                <div class="label">Revenus (FCFA)</div>
                            </div>
                        </div>

                        <!-- Status -->
                        <div class="status-badge <?php echo $doc['status'] === 'libre' ? 'status-libre' : 'status-occupe'; ?>">
                            <?php echo ucfirst($doc['status']); ?>
                        </div>

                        <!-- Toggle Form -->
                        <form method="POST" class="toggle-form">
                            <input type="hidden" name="action" value="toggle_status">
                            <input type="hidden" name="doctor_id" value="<?php echo $doc['id']; ?>">
                            <button type="submit" class="btn-toggle">
                                <i class="fa fa-toggle-on"></i>
                                <?php echo $doc['status'] === 'libre' ? 'Marquer absent' : 'Marquer disponible'; ?>
                            </button>
                        </form>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
