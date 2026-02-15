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

// GLOBAL STATS
try {
    // Total users
    $total_users = $pdo->query('SELECT COUNT(*) FROM patients')->fetchColumn();
    
    // Total doctors
    $total_doctors = $pdo->query('SELECT COUNT(*) FROM doctors')->fetchColumn();
    
    // Total consultations
    $total_consultations = $pdo->query('SELECT COUNT(*) FROM consultations')->fetchColumn();
    
    // Total revenue
    $total_revenue = $pdo->query('SELECT COALESCE(SUM(amount), 0) FROM invoices WHERE payment_status = "paid"')->fetchColumn();
    
    // Invoices stats
    $paid_invoices = $pdo->query('SELECT COUNT(*) FROM invoices WHERE payment_status = "paid"')->fetchColumn();
    $pending_invoices = $pdo->query('SELECT COUNT(*) FROM invoices WHERE payment_status = "pending"')->fetchColumn();
    
    // Available doctors
    $available_doctors = $pdo->query('SELECT COUNT(*) FROM doctors WHERE status = "libre"')->fetchColumn();
    
    // Monthly consultations
    $monthly_consults = $pdo->query('
        SELECT DATE_FORMAT(date, "%Y-%m") as month, COUNT(*) as count
        FROM consultations
        WHERE MONTH(date) = MONTH(CURDATE())
        GROUP BY DATE_FORMAT(date, "%Y-%m")
    ')->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $total_users = $total_doctors = $total_consultations = $total_revenue = 0;
    $paid_invoices = $pending_invoices = $available_doctors = 0;
    $monthly_consults = [];
}

// Get all doctors with their stats
try {
    $all_doctors = $pdo->query('
        SELECT 
            d.*,
            COUNT(c.id) as total_consultations,
            SUM(i.amount) as total_revenue
        FROM doctors d
        LEFT JOIN consultations c ON d.id = c.doctor_id
        LEFT JOIN invoices i ON c.id = i.consultation_id AND i.payment_status = "paid"
        GROUP BY d.id
        ORDER BY d.name ASC
    ')->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $all_doctors = [];
}

// Get recent invoices
try {
    $recent_invoices = $pdo->query('
        SELECT 
            i.*,
            p.name as patient_name,
            d.name as doctor_name,
            c.date as consultation_date
        FROM invoices i
        LEFT JOIN patients p ON i.patient_id = p.id
        LEFT JOIN consultations c ON i.consultation_id = c.id
        LEFT JOIN doctors d ON c.doctor_id = d.id
        ORDER BY i.id DESC
        LIMIT 10
    ')->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $recent_invoices = [];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin Global - EasyConsult</title>
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
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .admin-header h1 {
            margin: 0;
            color: #0066cc;
        }

        .admin-header .logout {
            color: #dc2626;
            text-decoration: none;
            font-weight: 600;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-box {
            background: white;
            padding: 1.5rem;
            border-radius: 1rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            border-left: 4px solid #0066cc;
        }

        .stat-box.success { border-left-color: #10b981; }
        .stat-box.warning { border-left-color: #f59e0b; }
        .stat-box.danger { border-left-color: #dc2626; }

        .stat-label {
            color: #6b7280;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: #1f2937;
        }

        .stat-unit {
            font-size: 0.85rem;
            color: #9ca3af;
        }

        .content-section {
            background: white;
            padding: 2rem;
            border-radius: 1rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }

        .content-section h2 {
            color: #0066cc;
            margin-bottom: 1.5rem;
            border-bottom: 2px solid #e5e7eb;
            padding-bottom: 1rem;
        }

        .table-wrapper {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        table thead {
            background: #f3f4f6;
        }

        table th {
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            color: #1f2937;
            border-bottom: 2px solid #e5e7eb;
        }

        table td {
            padding: 0.9rem 1rem;
            border-bottom: 1px solid #e5e7eb;
        }

        table tr:hover {
            background: #f9fafb;
        }

        .badge-status {
            padding: 0.4rem 0.8rem;
            border-radius: 0.4rem;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .badge-libre {
            background: #dcfce7;
            color: #166534;
        }

        .badge-occupe {
            background: #fee2e2;
            color: #991b1b;
        }

        .badge-paid {
            background: #dcfce7;
            color: #166534;
        }

        .badge-pending {
            background: #fef3c7;
            color: #92400e;
        }

        .doctor-row {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr 1fr;
            gap: 1rem;
            align-items: center;
            padding: 1rem;
            border-bottom: 1px solid #e5e7eb;
            background: white;
            margin-bottom: 0.5rem;
            border-radius: 0.5rem;
        }

        .doctor-row:hover {
            background: #f9fafb;
        }

        @media (max-width: 768px) {
            .admin-sidebar {
                width: 200px;
            }

            .admin-main {
                margin-left: 200px;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .doctor-row {
                grid-template-columns: 1fr;
            }

            .admin-header {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="admin-sidebar">
        <h2>👨‍⚕️ EasyConsult</h2>
        <ul class="admin-menu">
            <li><a href="index.php"><i class="fa fa-chart-line"></i> Mon Dashboard</a></li>
            <li><a href="super.php" class="active"><i class="fa fa-globe"></i> Vue Globale</a></li>
            <li><a href="consultations.php"><i class="fa fa-calendar"></i> Consultations</a></li>
            <li><a href="caisse.php"><i class="fa fa-money-bill"></i> Caisse</a></li>
            <li><a href="medecins.php"><i class="fa fa-users-doctor"></i> Médecins</a></li>
            <li><a href="../logout.php"><i class="fa fa-sign-out"></i> Déconnexion</a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="admin-main">
        <div class="admin-header">
            <div>
                <h1>📊 Dashboard Global</h1>
                <p style="color: #6b7280; margin: 0;">Vue d'ensemble de la plateforme</p>
            </div>
            <div style="text-align: right;">
                <p style="margin: 0; color: #6b7280;">Connecté: <strong><?php echo htmlspecialchars($doctor['name']); ?></strong></p>
            </div>
        </div>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-box success">
                <div class="stat-label">👥 Total Patients</div>
                <div class="stat-value"><?php echo number_format($total_users); ?></div>
            </div>
            <div class="stat-box success">
                <div class="stat-label">👨‍⚕️ Médecins</div>
                <div class="stat-value"><?php echo number_format($total_doctors); ?></div>
                <div class="stat-unit"><?php echo $available_doctors; ?> disponibles</div>
            </div>
            <div class="stat-box">
                <div class="stat-label">📋 Consultations</div>
                <div class="stat-value"><?php echo number_format($total_consultations); ?></div>
                <div class="stat-unit">Ce mois: <?php echo isset($monthly_consults[0]) ? $monthly_consults[0]['count'] : 0; ?></div>
            </div>
            <div class="stat-box success">
                <div class="stat-label">💰 Revenus (Payés)</div>
                <div class="stat-value"><?php echo number_format($total_revenue); ?></div>
                <div class="stat-unit">FCFA</div>
            </div>
            <div class="stat-box success">
                <div class="stat-label">✅ Factures Payées</div>
                <div class="stat-value"><?php echo number_format($paid_invoices); ?></div>
            </div>
            <div class="stat-box warning">
                <div class="stat-label">⏳ Factures En Attente</div>
                <div class="stat-value"><?php echo number_format($pending_invoices); ?></div>
            </div>
        </div>

        <!-- Doctors Management -->
        <div class="content-section">
            <h2><i class="fa fa-users-doctor"></i> Gestion des Médecins</h2>
            <p style="color: #6b7280; margin-bottom: 1.5rem;">Cliquez sur un médecin pour gérer son profil</p>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Médecin</th>
                            <th>Spécialité</th>
                            <th>Statut</th>
                            <th>Consultations</th>
                            <th>Revenus</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($all_doctors as $doc): ?>
                            <tr>
                                <td><strong>Dr. <?php echo htmlspecialchars($doc['name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($doc['specialty']); ?></td>
                                <td>
                                    <span class="badge-status <?php echo $doc['status'] === 'libre' ? 'badge-libre' : 'badge-occupe'; ?>">
                                        <?php echo ucfirst($doc['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo number_format($doc['total_consultations'] ?? 0); ?></td>
                                <td><?php echo number_format($doc['total_revenue'] ?? 0); ?> FCFA</td>
                                <td>
                                    <a href="medecins.php?id=<?php echo $doc['id']; ?>" style="color: #0066cc; text-decoration: none;">
                                        <i class="fa fa-edit"></i> Éditer
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Recent Invoices -->
        <div class="content-section">
            <h2><i class="fa fa-file-invoice"></i> Dernières Factures (10)</h2>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>ID Facture</th>
                            <th>Patient</th>
                            <th>Médecin</th>
                            <th>Montant</th>
                            <th>Statut</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_invoices as $invoice): ?>
                            <tr>
                                <td><strong>#<?php echo htmlspecialchars($invoice['invoice_uuid']); ?></strong></td>
                                <td><?php echo htmlspecialchars($invoice['patient_name'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($invoice['doctor_name'] ?? 'N/A'); ?></td>
                                <td><?php echo number_format($invoice['amount']); ?> FCFA</td>
                                <td>
                                    <span class="badge-status <?php echo $invoice['payment_status'] === 'paid' ? 'badge-paid' : 'badge-pending'; ?>">
                                        <?php echo ucfirst($invoice['payment_status']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($invoice['created_at']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
