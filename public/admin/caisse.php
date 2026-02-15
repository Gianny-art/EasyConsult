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
} catch (Exception $e) {
    die('Erreur: ' . htmlspecialchars($e->getMessage()));
}

// Get payment data
$filter_status = $_GET['status'] ?? '';
$filter_month = $_GET['month'] ?? date('Y-m');

try {
    $query = '
        SELECT i.*, p.name as patient_name, p.email, d.name as doctor_name, c.date as consultation_date
        FROM invoices i
        LEFT JOIN patients p ON i.patient_id = p.id
        LEFT JOIN doctors d ON i.doctor_id = d.id
        LEFT JOIN consultations c ON i.consultation_id = c.id
        WHERE i.doctor_id = ? AND DATE_FORMAT(i.date_created, "%Y-%m") = ?
    ';

    $params = [$doctor_id, $filter_month];
    
    if ($filter_status) {
        $query .= ' AND i.payment_status = ?';
        $params[] = $filter_status;
    }
    
    $query .= ' ORDER BY i.date_created DESC';
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $invoices = [];
}

// Calculate totals
$total_amount = 0;
$total_paid = 0;
$total_pending = 0;

foreach ($invoices as $invoice) {
    $total_amount += $invoice['amount'] ?? 0;
    if ($invoice['payment_status'] === 'paid') {
        $total_paid += $invoice['amount'] ?? 0;
    } else {
        $total_pending += $invoice['amount'] ?? 0;
    }
}

// Get monthly totals
try {
    $stmt = $pdo->prepare('
        SELECT 
            DATE_FORMAT(date_created, "%Y-%m") as month,
            COUNT(*) as count,
            SUM(amount) as total
        FROM invoices
        WHERE doctor_id = ?
        GROUP BY DATE_FORMAT(date_created, "%Y-%m")
        ORDER BY month DESC
        LIMIT 12
    ');
    $stmt->execute([$doctor_id]);
    $monthly_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $monthly_stats = [];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Caisse - Tableau de Bord Médecin</title>
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

        .content-section {
            background: white;
            border-radius: 1rem;
            padding: 2rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
            animation: slideUp 0.5s ease-out;
        }

        .content-section h1 {
            margin-top: 0;
            color: var(--primary);
        }

        .content-section h2 {
            color: #1f2937;
            margin-top: 2rem;
            margin-bottom: 1rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-box {
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
            border-left: 4px solid var(--primary);
            padding: 1.5rem;
            border-radius: 0.75rem;
        }

        .stat-box.success {
            background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
            border-left-color: #10b981;
        }

        .stat-box.warning {
            background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%);
            border-left-color: #f59e0b;
        }

        .stat-label {
            color: #6b7280;
            font-size: 0.9rem;
            font-weight: 600;
            text-transform: uppercase;
            margin-bottom: 0.5rem;
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary);
        }

        .stat-box.success .stat-value {
            color: #10b981;
        }

        .stat-box.warning .stat-value {
            color: #f59e0b;
        }

        .filters {
            background: #f9fafb;
            padding: 1rem;
            border-radius: 0.75rem;
            margin-bottom: 1.5rem;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }

        .filters select {
            padding: 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 0.5rem;
            font-size: 0.9rem;
            font-family: inherit;
        }

        .invoices-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }

        .invoices-table thead {
            background: #f9fafb;
            border-bottom: 2px solid #e5e7eb;
        }

        .invoices-table th {
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            color: #1f2937;
            font-size: 0.9rem;
        }

        .invoices-table td {
            padding: 1rem;
            border-bottom: 1px solid #e5e7eb;
            color: #6b7280;
        }

        .invoices-table tbody tr:hover {
            background: #f9fafb;
        }

        .status-badge {
            display: inline-block;
            padding: 0.35rem 0.75rem;
            border-radius: 0.5rem;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .status-paid {
            background: #dcfce7;
            color: #16a34a;
        }

        .status-pending {
            background: #fef3c7;
            color: #b45309;
        }

        .amount {
            font-weight: 600;
            color: #1f2937;
        }

        .empty-state {
            text-align: center;
            padding: 2rem;
            color: #9ca3af;
        }

        @media (max-width: 768px) {
            .admin-sidebar {
                transform: translateX(-100%);
            }

            .admin-main {
                margin-left: 0;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .filters {
                grid-template-columns: 1fr;
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
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="admin-sidebar">
        <h2>👨‍⚕️ EasyConsult</h2>
        <ul class="admin-menu">
            <li><a href="./index.php">📊 Mon Dashboard</a></li>
            <li><a href="./super.php">🌐 Vue Globale</a></li>
            <li><a href="./consultations.php">📋 Consultations</a></li>
            <li><a href="./caisse.php" class="active">💰 Caisse/Paiements</a></li>
            <li><a href="./medecins.php">👨‍⚕️ Médecins</a></li>
            <li><a href="./profil.php">⚙️ Mon profil</a></li>
            <li><a href="../logout.php">🚪 Déconnexion</a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="admin-main">
        <div class="content-section">
            <h1>💰 Caisse et Paiements</h1>
            <p style="color: #6b7280;">Suivi de tous vos revenus de consultations</p>

            <!-- Statistics -->
            <div class="stats-grid">
                <div class="stat-box">
                    <div class="stat-label">💵 Total</div>
                    <div class="stat-value"><?php echo number_format($total_amount, 0); ?> FCFA</div>
                </div>
                <div class="stat-box success">
                    <div class="stat-label">✓ Payé</div>
                    <div class="stat-value"><?php echo number_format($total_paid, 0); ?> FCFA</div>
                </div>
                <div class="stat-box warning">
                    <div class="stat-label">⏳ En attente</div>
                    <div class="stat-value"><?php echo number_format($total_pending, 0); ?> FCFA</div>
                </div>
            </div>

            <!-- Filters -->
            <div class="filters">
                <form method="get" style="display: contents;">
                    <div>
                        <label style="display: block; font-weight: 600; margin-bottom: 0.3rem; color: #1f2937; font-size: 0.9rem;">Mois</label>
                        <input 
                            type="month" 
                            name="month" 
                            value="<?php echo htmlspecialchars($filter_month); ?>"
                            style="padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 0.5rem; width: 100%; font-family: inherit;"
                        >
                    </div>
                    <div>
                        <label style="display: block; font-weight: 600; margin-bottom: 0.3rem; color: #1f2937; font-size: 0.9rem;">Statut</label>
                        <select name="status" style="padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 0.5rem; width: 100%; font-family: inherit;">
                            <option value="">Tous les statuts</option>
                            <option value="paid" <?php echo ($filter_status === 'paid') ? 'selected' : ''; ?>>Payé</option>
                            <option value="pending" <?php echo ($filter_status === 'pending') ? 'selected' : ''; ?>>En attente</option>
                        </select>
                    </div>
                    <div style="display: flex; align-items: flex-end;">
                        <button type="submit" style="padding: 0.75rem 1.5rem; background: var(--primary); color: white; border: none; border-radius: 0.5rem; font-weight: 600; cursor: pointer; font-family: inherit; width: 100%;">
                            🔍 Filtrer
                        </button>
                    </div>
                </form>
            </div>

            <!-- Invoices Table -->
            <h2>📊 Liste des factures</h2>
            <?php if (count($invoices) > 0): ?>
                <table class="invoices-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Patient</th>
                            <th>N° Facture</th>
                            <th>Montant</th>
                            <th>Statut</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($invoices as $invoice): ?>
                            <tr>
                                <td><?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($invoice['date_created']))); ?></td>
                                <td><?php echo htmlspecialchars($invoice['patient_name'] ?? 'Patient'); ?></td>
                                <td><strong><?php echo htmlspecialchars($invoice['invoice_number']); ?></strong></td>
                                <td class="amount"><?php echo number_format($invoice['amount'] ?? 0, 0); ?> FCFA</td>
                                <td>
                                    <span class="status-badge status-<?php echo htmlspecialchars($invoice['payment_status']); ?>">
                                        <?php
                                            $pstat = $invoice['payment_status'] ?? 'pending';
                                            if ($pstat === 'paid') {
                                                echo '✓ Payée';
                                            } elseif ($pstat === 'pending') {
                                                echo '⏳ En attente';
                                            } else {
                                                echo htmlspecialchars($pstat);
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
                    <p>Aucune facture pour la période sélectionnée</p>
                </div>
            <?php endif; ?>

            <!-- Monthly Statistics -->
            <h2>📈 Récapitulatif mensuel (12 derniers mois)</h2>
            <?php if (count($monthly_stats) > 0): ?>
                <table class="invoices-table">
                    <thead>
                        <tr>
                            <th>Mois</th>
                            <th>Nombre de consultations</th>
                            <th>Montant total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($monthly_stats as $stat): ?>
                            <tr>
                                <td><?php echo htmlspecialchars(date('F Y', strtotime($stat['month'] . '-01'))); ?></td>
                                <td class="amount"><?php echo htmlspecialchars($stat['count']); ?></td>
                                <td class="amount"><?php echo number_format($stat['total'] ?? 0, 0); ?> FCFA</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
