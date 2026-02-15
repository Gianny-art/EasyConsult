<?php
require_once __DIR__ . '/../../lib/db.php';
session_start();

// Check if admin/doctor is logged in
if (!isset($_SESSION['doctor_id'])) {
    header('Location: ../login.php');
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
        header('Location: ../login.php');
        exit;
    }
} catch (Exception $e) {
    die('Erreur: ' . htmlspecialchars($e->getMessage()));
}

// Handle status change
$status_msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'toggle_status') {
        $new_status = ($doctor['status'] === 'libre') ? 'occupé' : 'libre';
        try {
            $stmt = $pdo->prepare('UPDATE doctors SET status = ? WHERE id = ?');
            $stmt->execute([$new_status, $doctor_id]);
            $doctor['status'] = $new_status;
            $status_msg = 'Statut mis à jour: ' . ($new_status === 'libre' ? 'Disponible' : 'Non disponible');
        } catch (Exception $e) {
            $status_msg = 'Erreur: ' . htmlspecialchars($e->getMessage());
        }
    }
}

// Get today's consultations
try {
        $stmt = $pdo->prepare('
            SELECT c.*, p.name as patient_name, p.email, p.phone
            FROM consultations c
            JOIN patients p ON c.patient_id = p.id
            WHERE c.doctor_id = ? AND DATE(c.date) = CURDATE()
            ORDER BY c.start_time ASC
        ');
    $stmt->execute([$doctor_id]);
    $today_consultations = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $today_consultations = [];
}

// Get statistics
try {
    $stmt = $pdo->prepare('
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = "confirmed" THEN 1 ELSE 0 END) as confirmed,
            SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as completed
        FROM consultations
        WHERE doctor_id = ? AND MONTH(date) = MONTH(CURDATE()) AND YEAR(date) = YEAR(CURDATE())
    ');
    $stmt->execute([$doctor_id]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $stats = ['total' => 0, 'confirmed' => 0, 'completed' => 0];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de Bord Médecin - EasyConsult</title>
    <link rel="stylesheet" href="../style.css">
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
            background: linear-gradient(135deg, var(--primary), #0052a3);
            color: white;
            padding: 2rem 0;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
            z-index: 100;
        }

        .admin-sidebar h2 {
            padding: 0 1.5rem;
            margin: 0 0 2rem 0;
            font-size: 1.1rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            padding-bottom: 1.5rem;
        }

        .admin-menu {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .admin-menu li {
            padding: 0;
        }

        .admin-menu a {
            display: block;
            padding: 1rem 1.5rem;
            color: rgba(255, 255, 255, 0.9);
            text-decoration: none;
            transition: all 0.3s ease;
            border-left: 4px solid transparent;
        }

        .admin-menu a:hover {
            background: rgba(255, 255, 255, 0.1);
            border-left-color: white;
            padding-left: 1.75rem;
        }

        .admin-menu a.active {
            background: rgba(255, 255, 255, 0.2);
            border-left-color: white;
        }

        .admin-main {
            margin-left: 250px;
        }

        .admin-header {
            background: white;
            padding: 2rem;
            border-radius: 1rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .admin-header h1 {
            margin: 0;
            color: var(--primary);
        }

        .status-badge {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            border-radius: 50px;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .status-libre {
            background: #dcfce7;
            color: #16a34a;
        }

        .status-occupé {
            background: #fee2e2;
            color: #dc2626;
        }

        .toggle-status-btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 0.75rem;
            font-weight: 600;
            cursor: pointer;
            background: var(--primary);
            color: white;
            transition: all 0.3s ease;
        }

        .toggle-status-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 102, 204, 0.3);
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            border-radius: 1rem;
            padding: 1.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            animation: slideUp 0.5s ease-out;
        }

        .stat-label {
            color: #6b7280;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .stat-value {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--primary);
        }

        .content-section {
            background: white;
            border-radius: 1rem;
            padding: 2rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }

        .content-section h2 {
            margin-top: 0;
            color: var(--primary);
        }

        .consultations-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }

        .consultations-table thead {
            background: #f9fafb;
            border-bottom: 2px solid #e5e7eb;
        }

        .consultations-table th {
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            color: #1f2937;
            font-size: 0.9rem;
        }

        .consultations-table td {
            padding: 1rem;
            border-bottom: 1px solid #e5e7eb;
            color: #6b7280;
        }

        .consultations-table tbody tr:hover {
            background: #f9fafb;
        }

        .status-badge-table {
            display: inline-block;
            padding: 0.35rem 0.75rem;
            border-radius: 0.35rem;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .status-pending {
            background: #fef3c7;
            color: #92400e;
        }

        .status-confirmed {
            background: #dbeafe;
            color: #1e40af;
        }

        .status-completed {
            background: #dcfce7;
            color: #15803d;
        }

        .empty-state {
            text-align: center;
            padding: 2rem;
            color: #9ca3af;
        }

        .alert {
            padding: 1rem 1.25rem;
            border-radius: 0.75rem;
            margin-bottom: 1.5rem;
            animation: slideDown 0.3s ease-out;
        }

        .alert-success {
            background: #dcfce7;
            color: #16a34a;
            border: 1px solid #bbf7d0;
        }

        @media (max-width: 768px) {
            .admin-sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }

            .admin-main {
                margin-left: 0;
            }

            .dashboard-grid {
                grid-template-columns: 1fr;
            }

            .admin-header {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/_sidebar.php'; ?>

    <!-- Main Content -->
    <div class="admin-main">
        <!-- Header -->
        <div class="admin-header">
            <div>
                <h1>👋 Bienvenue, Dr. <?php echo htmlspecialchars($doctor['name']); ?></h1>
                <p style="margin: 0.5rem 0 0 0; color: #6b7280;">
                    Spécialité: <?php echo htmlspecialchars($doctor['specialty']); ?>
                </p>
            </div>
            <div style="text-align: right;">
                <div class="status-badge <?php echo ($doctor['status'] === 'libre') ? 'status-libre' : 'status-occupé'; ?>">
                    <?php echo ($doctor['status'] === 'libre') ? '✓ Disponible' : '✗ Non disponible'; ?>
                </div>
                <form method="post" style="margin-top: 1rem;">
                    <input type="hidden" name="action" value="toggle_status">
                    <button type="submit" class="toggle-status-btn">
                        🔄 Changer de statut
                    </button>
                </form>
            </div>
        </div>

        <?php if ($status_msg): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($status_msg); ?>
            </div>
        <?php endif; ?>

        <!-- Statistics -->
        <div class="dashboard-grid">
            <div class="stat-card">
                <div class="stat-label">📅 Total du mois</div>
                <div class="stat-value"><?php echo htmlspecialchars($stats['total'] ?? 0); ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">✓ Confirmées</div>
                <div class="stat-value" style="color: #10b981;">
                    <?php echo htmlspecialchars($stats['confirmed'] ?? 0); ?>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-label">✓ Terminées</div>
                <div class="stat-value" style="color: #059669;">
                    <?php echo htmlspecialchars($stats['completed'] ?? 0); ?>
                </div>
            </div>
        </div>

        <!-- Today's Consultations -->
        <div class="content-section">
            <h2>📋 Consultations d'aujourd'hui</h2>
            <?php if (count($today_consultations) > 0): ?>
                <table class="consultations-table">
                    <thead>
                        <tr>
                            <th>Heure</th>
                            <th>Patient</th>
                            <th>Contact</th>
                            <th>Motif</th>
                            <th>Statut</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($today_consultations as $consultation): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($consultation['start_time']); ?></strong></td>
                                <td><?php echo htmlspecialchars($consultation['patient_name']); ?></td>
                                <td>
                                    📧 <?php echo htmlspecialchars($consultation['email']); ?><br>
                                    📱 <?php echo htmlspecialchars($consultation['phone']); ?>
                                </td>
                                <td><?php echo htmlspecialchars(substr($consultation['motif'], 0, 50)); ?></td>
                                <td>
                                    <span class="status-badge-table status-<?php echo htmlspecialchars($consultation['status']); ?>">
                                                                <?php
                                                                    $cstatus = $consultation['status'] ?? 'pending_payment';
                                                                    if ($cstatus === 'pending_payment') {
                                                                        echo '⏳ En attente';
                                                                    } elseif ($cstatus === 'confirmed') {
                                                                        echo '✓ Confirmée';
                                                                    } elseif ($cstatus === 'completed') {
                                                                        echo '✓ Terminée';
                                                                    } elseif ($cstatus === 'cancelled') {
                                                                        echo '✗ Annulée';
                                                                    } else {
                                                                        echo htmlspecialchars($cstatus);
                                                                    }
                                                                ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-state">
                    <p>Aucune consultation programmée pour aujourd'hui</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Quick Actions -->
        <div class="content-section">
            <h2>⚡ Actions rapides</h2>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                <a href="consultations.php" style="display: block; padding: 1rem; background: linear-gradient(135deg, var(--primary), #0052a3); color: white; text-decoration: none; border-radius: 0.75rem; text-align: center; font-weight: 600;">
                    📋 Voir toutes les consultations
                </a>
                <a href="caisse.php" style="display: block; padding: 1rem; background: linear-gradient(135deg, #10b981, #059669); color: white; text-decoration: none; border-radius: 0.75rem; text-align: center; font-weight: 600;">
                    💰 Consulter la caisse
                </a>
            </div>
        </div>
    </div>
</body>
</html>
