<?php
session_start();
require '../../lib/db.php';
require '../../lib/nav.php';

// Vérifier que le médecin est connecté
if (!isset($_SESSION['doctor_id'])) {
    header('Location: ./login.php');
    exit;
}

$doctor_id = $_SESSION['doctor_id'];
$doctor_name = $_SESSION['doctor_name'] ?? 'Médecin';
$pdo = get_db();

// Récupérer les consultations du médecin
$filter = $_GET['filter'] ?? 'all'; // all, today, week, month

$query = 'SELECT c.*, COALESCE(p.name, "Patient Anonyme") as patient_name, p.email
          FROM consultations c
          LEFT JOIN patients p ON c.patient_id = p.id
          WHERE c.doctor_id = :doctor_id';

$params = ['doctor_id' => $doctor_id];

// Appliquer le filtre
if ($filter === 'today') {
    $query .= ' AND DATE(c.date) = CURDATE()';
} elseif ($filter === 'week') {
    $query .= ' AND DATE(c.date) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)';
} elseif ($filter === 'month') {
    $query .= ' AND MONTH(c.date) = MONTH(CURDATE()) AND YEAR(c.date) = YEAR(CURDATE())';
}

$query .= ' ORDER BY c.date DESC';

try {
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $consultations = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $consultations = [];
    $error = 'Erreur lors du chargement des consultations';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consultations - EasyConsult Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
        }

        .header h1 {
            font-size: 28px;
            margin-bottom: 5px;
        }

        .header p {
            opacity: 0.9;
            font-size: 14px;
        }

        .menu {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #f8f9fa;
            padding: 15px 30px;
            border-bottom: 1px solid #e0e0e0;
        }

        .menu ul {
            list-style: none;
            display: flex;
            gap: 20px;
            flex: 1;
        }

        .menu a {
            text-decoration: none;
            color: #333;
            font-weight: 500;
            transition: color 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .menu a:hover {
            color: #667eea;
        }

        .menu a.active {
            color: #667eea;
            border-bottom: 2px solid #667eea;
        }

        .filters {
            padding: 20px 30px;
            background: #f8f9fa;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .filters a {
            padding: 8px 15px;
            border-radius: 20px;
            background: white;
            text-decoration: none;
            color: #333;
            border: 1px solid #ddd;
            transition: all 0.3s;
            font-size: 14px;
        }

        .filters a:hover,
        .filters a.active {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }

        .content {
            padding: 30px;
        }

        .stat-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
        }

        .stat-card .label {
            color: #666;
            font-size: 14px;
            margin-bottom: 10px;
        }

        .stat-card .value {
            font-size: 32px;
            font-weight: bold;
            color: #667eea;
        }

        .consultations-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .consultations-table thead {
            background: #f8f9fa;
        }

        .consultations-table th {
            padding: 15px;
            text-align: left;
            font-weight: 600;
            color: #333;
            border-bottom: 2px solid #e0e0e0;
            font-size: 14px;
        }

        .consultations-table td {
            padding: 15px;
            border-bottom: 1px solid #e0e0e0;
        }

        .consultations-table tbody tr:hover {
            background: #f8f9fa;
        }

        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .status-completed {
            background: #d4edda;
            color: #155724;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-cancelled {
            background: #f8d7da;
            color: #721c24;
        }

        .action-btn {
            padding: 8px 12px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 12px;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
        }

        .action-btn.view {
            background: #667eea;
            color: white;
        }

        .action-btn.view:hover {
            background: #5568d3;
        }

        .action-btn.cancel {
            background: #dc3545;
            color: white;
            border: none;
        }

        .action-btn.cancel:hover {
            background: #c82333;
        }

        .empty-state {
            text-align: center;
            padding: 40px;
            color: #999;
        }

        .empty-state i {
            font-size: 48px;
            margin-bottom: 20px;
            opacity: 0.5;
        }

        .mobile-card {
            display: none;
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
        }

        @media (max-width: 768px) {
            .header {
                padding: 20px;
            }

            .header h1 {
                font-size: 20px;
            }

            .menu {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }

            .menu ul {
                flex-direction: column;
                gap: 10px;
                width: 100%;
            }

            .consultations-table {
                display: none;
            }

            .mobile-card {
                display: block;
            }

            .mobile-card-header {
                display: flex;
                justify-content: space-between;
                align-items: start;
                margin-bottom: 12px;
            }

            .mobile-card-title {
                font-weight: 600;
                color: #333;
            }

            .mobile-card-status {
                font-size: 12px;
            }

            .mobile-card-info {
                font-size: 13px;
                color: #666;
                margin-bottom: 8px;
            }

            .mobile-card-footer {
                display: flex;
                gap: 8px;
                margin-top: 12px;
            }

            .mobile-card-footer a {
                flex: 1;
                text-align: center;
            }

            .content {
                padding: 15px;
            }

            .stat-cards {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📋 Consultations</h1>
            <p>Gestion de vos consultations</p>
        </div>

        <div class="menu">
            <ul>
                <li><a href="./index.php">📊 Tableau de bord</a></li>
                <li><a href="./consultations.php" class="active">📋 Consultations</a></li>
                <li><a href="./caisse.php">💰 Caisse</a></li>
                <li><a href="./logout.php">🚪 Déconnexion</a></li>
            </ul>
        </div>

        <div class="filters">
            <a href="?filter=all" class="<?php echo $filter === 'all' ? 'active' : ''; ?>">Toutes</a>
            <a href="?filter=today" class="<?php echo $filter === 'today' ? 'active' : ''; ?>">Aujourd'hui</a>
            <a href="?filter=week" class="<?php echo $filter === 'week' ? 'active' : ''; ?>">Cette semaine</a>
            <a href="?filter=month" class="<?php echo $filter === 'month' ? 'active' : ''; ?>">Ce mois</a>
        </div>

        <div class="content">
            <?php if (isset($error)): ?>
                <div style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
                    <i class="fa fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <!-- Statistiques -->
            <div class="stat-cards">
                <div class="stat-card">
                    <div class="label">Total</div>
                    <div class="value"><?php echo count($consultations); ?></div>
                </div>
                <div class="stat-card">
                    <div class="label">Complétées</div>
                    <div class="value"><?php echo count(array_filter($consultations, function($c) { return $c['status'] === 'completed'; })); ?></div>
                </div>
                <div class="stat-card">
                    <div class="label">En attente</div>
                    <div class="value"><?php echo count(array_filter($consultations, function($c) { return $c['status'] === 'pending'; })); ?></div>
                </div>
            </div>

            <!-- Tableau Desktop -->
            <?php if (count($consultations) > 0): ?>
                <table class="consultations-table">
                    <thead>
                        <tr>
                            <th>Date & Heure</th>
                            <th>Patient</th>
                            <th>Email</th>
                            <th>Statut</th>
                            <th>Type</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($consultations as $consultation): ?>
                            <tr>
                                <td>
                                    <strong><?php echo date('d/m/Y H:i', strtotime($consultation['date'])); ?></strong>
                                </td>
                                <td><?php echo htmlspecialchars($consultation['patient_name']); ?></td>
                                <td><?php echo htmlspecialchars($consultation['email'] ?? 'N/A'); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo htmlspecialchars($consultation['status']); ?>">
                                        <?php 
                                            $status_map = [
                                                'pending' => '⏳ En attente',
                                                'completed' => '✅ Complétée',
                                                'cancelled' => '❌ Annulée'
                                            ];
                                            echo $status_map[$consultation['status']] ?? ucfirst($consultation['status']);
                                        ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($consultation['type'] ?? 'Standard'); ?></td>
                                <td>
                                    <a href="#" class="action-btn view" onclick="viewDetails(<?php echo htmlspecialchars(json_encode($consultation)); ?>); return false;">
                                        <i class="fa fa-eye"></i> Détails
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fa fa-calendar-check"></i>
                    <h3>Aucune consultation trouvée</h3>
                    <p>Vous n'avez pas de consultation pour cette période.</p>
                </div>
            <?php endif; ?>

            <!-- Cartes Mobile -->
            <?php foreach ($consultations as $consultation): ?>
                <div class="mobile-card">
                    <div class="mobile-card-header">
                        <div class="mobile-card-title"><?php echo htmlspecialchars($consultation['patient_name']); ?></div>
                        <span class="status-badge status-<?php echo htmlspecialchars($consultation['status']); ?> mobile-card-status">
                            <?php 
                                $status_map = [
                                    'pending' => '⏳ En attente',
                                    'completed' => '✅ Complétée',
                                    'cancelled' => '❌ Annulée'
                                ];
                                echo $status_map[$consultation['status']] ?? ucfirst($consultation['status']);
                            ?>
                        </span>
                    </div>
                    <div class="mobile-card-info">
                        <strong>📅 <?php echo date('d/m/Y H:i', strtotime($consultation['date'])); ?></strong>
                    </div>
                    <div class="mobile-card-info">
                        📧 <?php echo htmlspecialchars($consultation['email'] ?? 'N/A'); ?>
                    </div>
                    <div class="mobile-card-info">
                        🏥 <?php echo htmlspecialchars($consultation['type'] ?? 'Standard'); ?>
                    </div>
                    <div class="mobile-card-footer">
                        <a href="#" class="action-btn view" onclick="viewDetails(<?php echo htmlspecialchars(json_encode($consultation)); ?>); return false;">
                            <i class="fa fa-eye"></i> Détails
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <script>
        function viewDetails(consultation) {
            alert('Patient: ' + consultation.patient_name + '\n' +
                  'Date: ' + new Date(consultation.date).toLocaleString('fr-FR') + '\n' +
                  'Type: ' + consultation.type + '\n' +
                  'Statut: ' + consultation.status);
        }
    </script>

    <div style="height: 76px;"></div> <!-- Spacer pour mobile nav bar -->
</body>
</html>
