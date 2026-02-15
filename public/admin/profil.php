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

// Traiter la mise à jour du profil
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $specialty = trim($_POST['specialty'] ?? '');
    $bio = trim($_POST['bio'] ?? '');
    
    if (!$name || !$email || !$specialty) {
        $message = 'Tous les champs obligatoires doivent être remplis';
        $message_type = 'error';
    } else {
        try {
            $stmt = $pdo->prepare('
                UPDATE doctors 
                SET name = ?, email = ?, phone = ?, specialty = ?, bio = ?
                WHERE id = ?
            ');
            $stmt->execute([$name, $email, $phone, $specialty, $bio, $doctor_id]);
            
            // Mettre à jour les infos locales
            $doctor['name'] = $name;
            $doctor['email'] = $email;
            $doctor['phone'] = $phone;
            $doctor['specialty'] = $specialty;
            $doctor['bio'] = $bio;
            
            $message = 'Profil mis à jour avec succès';
            $message_type = 'success';
        } catch (Exception $e) {
            $message = 'Erreur lors de la mise à jour: ' . htmlspecialchars($e->getMessage());
            $message_type = 'error';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon Profil - EasyConsult Admin</title>
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

        .content-section {
            background: white;
            border-radius: 1rem;
            padding: 2rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
            max-width: 600px;
        }

        .content-section h2 {
            margin-top: 0;
            margin-bottom: 1.5rem;
            color: var(--primary);
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #333;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #e5e7eb;
            border-radius: 0.5rem;
            font-family: inherit;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(0, 102, 204, 0.1);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 120px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .submit-btn {
            background: linear-gradient(135deg, var(--primary), #0052a3);
            color: white;
            padding: 0.75rem 2rem;
            border: none;
            border-radius: 0.5rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 102, 204, 0.3);
        }

        .alert {
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .alert-success {
            background: #dcfce7;
            color: #15803d;
            border-left: 4px solid #16a34a;
        }

        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border-left: 4px solid #ef4444;
        }

        .info-box {
            background: #f3f4f6;
            border-left: 4px solid var(--primary);
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1.5rem;
        }

        .info-box strong {
            color: var(--primary);
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

            .content-section {
                max-width: 100%;
                padding: 1.5rem;
            }

            .form-row {
                grid-template-columns: 1fr;
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
            <li><a href="./consultations.php"><i class="fa fa-calendar-check"></i> Consultations</a></li>
            <li><a href="./caisse.php"><i class="fa fa-cash-register"></i> Caisse</a></li>
            <li><a href="./profil.php" class="active"><i class="fa fa-user-circle"></i> Mon profil</a></li>
            <li><a href="../logout.php"><i class="fa fa-sign-out"></i> Déconnexion</a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="admin-main">
        <div class="admin-container">
            <!-- Header -->
            <div class="admin-header">
                <h1>
                    <i class="fa fa-user-circle"></i>
                    Mon Profil
                </h1>
            </div>

            <!-- Message -->
            <?php if ($message): ?>
                <div class="alert alert-<?php echo $message_type; ?>">
                    <i class="fa fa-<?php echo $message_type === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <!-- Info Box -->
            <div class="info-box">
                <strong><i class="fa fa-info-circle"></i> Informations personnelles</strong><br>
                Mettez à jour vos informations de profil. Ces données seront visibles aux patients.
            </div>

            <!-- Profile Form -->
            <div class="content-section">
                <h2>Modifier mon profil</h2>
                
                <form method="POST">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="name">
                                <i class="fa fa-user"></i> Nom complet *
                            </label>
                            <input 
                                type="text" 
                                id="name" 
                                name="name" 
                                value="<?php echo htmlspecialchars($doctor['name']); ?>"
                                required
                            >
                        </div>

                        <div class="form-group">
                            <label for="specialty">
                                <i class="fa fa-stethoscope"></i> Spécialité *
                            </label>
                            <input 
                                type="text" 
                                id="specialty" 
                                name="specialty" 
                                value="<?php echo htmlspecialchars($doctor['specialty']); ?>"
                                placeholder="Ex: Cardiologie, Dermatologie..."
                                required
                            >
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="email">
                                <i class="fa fa-envelope"></i> Email professionnel *
                            </label>
                            <input 
                                type="email" 
                                id="email" 
                                name="email" 
                                value="<?php echo htmlspecialchars($doctor['email']); ?>"
                                required
                            >
                        </div>

                        <div class="form-group">
                            <label for="phone">
                                <i class="fa fa-phone"></i> Téléphone
                            </label>
                            <input 
                                type="tel" 
                                id="phone" 
                                name="phone" 
                                value="<?php echo htmlspecialchars($doctor['phone'] ?? ''); ?>"
                                placeholder="+243..."
                            >
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="bio">
                            <i class="fa fa-pen"></i> Biographie
                        </label>
                        <textarea 
                            id="bio" 
                            name="bio"
                            placeholder="Parlez un peu de vous, votre expérience, vos qualifications..."
                        ><?php echo htmlspecialchars($doctor['bio'] ?? ''); ?></textarea>
                    </div>

                    <button type="submit" class="submit-btn">
                        <i class="fa fa-save"></i> Enregistrer les modifications
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div style="height: 76px;"></div> <!-- Spacer pour mobile nav -->
</body>
</html>
