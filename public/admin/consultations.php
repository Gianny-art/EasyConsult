<?php
require_once __DIR__ . '/../../lib/db.php';
session_start();

// Vérifier que le médecin est connecté
if (!isset($_SESSION['doctor_id'])) {
    header('Location: ../login.php');
    exit;
}

$pdo = get_db();
$doctor_id = $_SESSION['doctor_id'];

// Récupérer les infos du médecin
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

// Filtre
$filter = $_GET['filter'] ?? 'all';

// Récupérer les consultations
$query = 'SELECT c.*, COALESCE(p.name, "Patient Anonyme") as patient_name, p.email
          FROM consultations c
          LEFT JOIN patients p ON c.patient_id = p.id
          WHERE c.doctor_id = :doctor_id';

$params = ['doctor_id' => $doctor_id];

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
    $error = 'Erreur lors du chargement';
}

// Statistiques
$total = count($consultations);
$completed = count(array_filter($consultations, function($c) { return $c['status'] === 'completed'; }));
$pending = count(array_filter($consultations, function($c) { return $c['status'] === 'pending'; }));
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consultations - EasyConsult Admin</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #0066cc;
        }

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
        }

        .admin-header h1 {
            margin: 0;
            color: var(--primary);
            display: flex;
            align-items: center;
            gap: 1rem;
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
            margin-bottom: 1.5rem;
            color: var(--primary);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .filters {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
        }

        .filter-btn {
            padding: 0.75rem 1.5rem;
            border: 2px solid #e5e7eb;
            background: white;
            border-radius: 0.75rem;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            color: #6b7280;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .filter-btn:hover {
            border-color: var(--primary);
            color: var(--primary);
        }

        .filter-btn.active {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
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

        .status-badge {
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

        .status-completed {
            background: #dcfce7;
            color: #15803d;
        }

        .status-cancelled {
            background: #fee2e2;
            color: #991b1b;
        }

        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #9ca3af;
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media (max-width: 768px) {
            .admin-sidebar {
                width: 100%;
                height: auto;
                position: relative;
                padding: 1rem 0;
            }

            .admin-menu {
                display: flex;
                gap: 0;
                flex-wrap: wrap;
            }

            .admin-menu a {
                padding: 0.75rem 1rem;
                font-size: 0.85rem;
            }

            .admin-main {
                margin-left: 0;
                padding-bottom: 76px;
            }

            .admin-header {
                padding: 1rem;
            }

            .admin-header h1 {
                font-size: 1.25rem;
            }

            .dashboard-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }

            .consultations-table {
                font-size: 0.85rem;
            }

            .consultations-table th,
            .consultations-table td {
                padding: 0.75rem 0.5rem;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="admin-sidebar">
        <h2>📋 EasyConsult</h2>
        <ul class="admin-menu">
            <li><a href="./index.php"><i class="fa fa-chart-line"></i> Tableau de bord</a></li>
            <li><a href="./consultations.php" class="active"><i class="fa fa-calendar-check"></i> Consultations</a></li>
            <li><a href="./caisse.php"><i class="fa fa-cash-register"></i> Caisse</a></li>
            <li><a href="../logout.php"><i class="fa fa-sign-out"></i> Déconnexion</a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="admin-main">
        <div class="admin-container">
            <!-- Header -->
            <div class="admin-header">
                <h1>
                    <i class="fa fa-stethoscope"></i>
                    Consultations - Dr. <?php echo htmlspecialchars($doctor['name']); ?>
                </h1>
            </div>

            <!-- Stats -->
            <div class="dashboard-grid">
                <div class="stat-card">
                    <div class="stat-label">Total</div>
                    <div class="stat-value"><?php echo $total; ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Complétées</div>
                    <div class="stat-value"><?php echo $completed; ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">En attente</div>
                    <div class="stat-value"><?php echo $pending; ?></div>
                </div>
            </div>

            <!-- Content Section -->
            <div class="content-section">
                <h2>
                    <i class="fa fa-filter"></i>
                    Filtrer les consultations
                </h2>

                <div class="filters">
                    <a href="?filter=all" class="filter-btn <?php echo $filter === 'all' ? 'active' : ''; ?>">
                        <i class="fa fa-list"></i> Toutes
                    </a>
                    <a href="?filter=today" class="filter-btn <?php echo $filter === 'today' ? 'active' : ''; ?>">
                        <i class="fa fa-calendar-day"></i> Aujourd'hui
                    </a>
                    <a href="?filter=week" class="filter-btn <?php echo $filter === 'week' ? 'active' : ''; ?>">
                        <i class="fa fa-calendar-week"></i> Cette semaine
                    </a>
                    <a href="?filter=month" class="filter-btn <?php echo $filter === 'month' ? 'active' : ''; ?>">
                        <i class="fa fa-calendar"></i> Ce mois
                    </a>
                </div>

                <!-- Table -->
                <?php if (count($consultations) > 0): ?>
                    <table class="consultations-table">
                        <thead>
                            <tr>
                                <th>Date & Heure</th>
                                <th>Patient</th>
                                <th>Email</th>
                                <th>Type</th>
                                <th>Statut</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($consultations as $c): ?>
                                <tr>
                                    <td><strong><?php echo date('d/m/Y H:i', strtotime($c['date'])); ?></strong></td>
                                    <td><?php echo htmlspecialchars($c['patient_name']); ?></td>
                                    <td><?php echo htmlspecialchars($c['email'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($c['type'] ?? 'Standard'); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo htmlspecialchars($c['status']); ?>">
                                            <?php 
                                                $labels = [
                                                    'pending' => '⏳ En attente',
                                                    'completed' => '✅ Complétée',
                                                    'cancelled' => '❌ Annulée'
                                                ];
                                                echo $labels[$c['status']] ?? ucfirst($c['status']);
                                            ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fa fa-inbox"></i>
                        <h3>Aucune consultation trouvée</h3>
                        <p>Pas de consultation pour cette période.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div style="height: 76px;"></div> <!-- Spacer pour mobile nav -->
</body>
</html>
