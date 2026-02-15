<?php
require_once __DIR__ . '/../../lib/db.php';
session_start();

// Redirect if already logged in as doctor
if (isset($_SESSION['doctor_id'])) {
    header('Location: index.php');
    exit;
}

$pdo = get_db();
$err = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $doctor_id = intval($_POST['doctor_id'] ?? 0);
    $password = $_POST['password'] ?? '';

    if ($doctor_id > 0 && !empty($password)) {
        try {
            $stmt = $pdo->prepare('SELECT id, name, specialty, password FROM doctors WHERE id = ?');
            $stmt->execute([$doctor_id]);
            $doctor = $stmt->fetch(PDO::FETCH_ASSOC);

            // For demo: use simple password or leave empty
            // In production, use password_verify($password, $doctor['password'])
            if ($doctor && ($password === 'demo' || $password === $doctor['password'] || empty($doctor['password']))) {
                $_SESSION['doctor_id'] = $doctor['id'];
                $_SESSION['doctor_name'] = $doctor['name'];
                $_SESSION['doctor_specialty'] = $doctor['specialty'];
                header('Location: ./index.php');
                exit;
            } else {
                $err = 'ID médecin ou mot de passe incorrect';
            }
        } catch (Exception $e) {
            $err = 'Erreur système: ' . htmlspecialchars($e->getMessage());
        }
    } else {
        $err = 'Veuillez remplir tous les champs';
    }
}

// Get list of doctors for dropdown
try {
    $doctors = $pdo->query('SELECT id, name, specialty FROM doctors ORDER BY name ASC')->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $doctors = [];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion Médecin - EasyConsult Admin</title>
    <link rel="stylesheet" href="../style.css">
    <style>
        .auth-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #0066cc 0%, #004a9f 100%);
            padding: 2rem;
        }

        .auth-card {
            background: white;
            border-radius: 1.5rem;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 450px;
            width: 100%;
            padding: 3rem 2.5rem;
            animation: slideUp 0.6s ease-out;
        }

        .auth-header {
            text-align: center;
            margin-bottom: 2.5rem;
        }

        .auth-header h1 {
            font-size: 2rem;
            color: #0066cc;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .auth-header p {
            color: #6b7280;
            font-size: 0.95rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 0.6rem;
            font-size: 0.95rem;
        }

        .form-group select,
        .form-group input {
            width: 100%;
            padding: 0.9rem 1rem;
            border: 2px solid #e5e7eb;
            border-radius: 0.75rem;
            font-size: 1rem;
            font-family: inherit;
            transition: all 0.3s ease;
            box-sizing: border-box;
        }

        .form-group select:focus,
        .form-group input:focus {
            outline: none;
            border-color: #0066cc;
            box-shadow: 0 0 0 3px rgba(0, 102, 204, 0.1);
        }

        .form-group input::placeholder {
            color: #9ca3af;
        }

        .btn-submit {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(135deg, #0066cc, #004a9f);
            color: white;
            border: none;
            border-radius: 0.75rem;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 1.5rem;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0, 102, 204, 0.3);
        }

        .btn-submit:active {
            transform: translateY(0);
        }

        .error-msg {
            background: #fee2e2;
            color: #dc2626;
            padding: 1rem;
            border-radius: 0.75rem;
            margin-bottom: 1.5rem;
            border-left: 4px solid #dc2626;
        }

        .info-msg {
            background: #f0fdf4;
            color: #059669;
            padding: 0.75rem;
            border-radius: 0.5rem;
            margin-top: 1rem;
            font-size: 0.85rem;
            border-left: 4px solid #10b981;
        }

        .back-link {
            text-align: center;
            margin-top: 1.5rem;
        }

        .back-link a {
            color: #0066cc;
            text-decoration: none;
            font-weight: 500;
        }

        .back-link a:hover {
            text-decoration: underline;
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
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <h1>👨‍⚕️ Espace Médecin</h1>
                <p>Connectez-vous au tableau de bord administrateur</p>
            </div>

            <?php if (!empty($err)): ?>
                <div class="error-msg">
                    <strong>Erreur:</strong> <?php echo htmlspecialchars($err); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="doctor_id">Sélectionnez votre profil médecin</label>
                    <select id="doctor_id" name="doctor_id" required>
                        <option value="">-- Choisir un médecin --</option>
                        <?php foreach ($doctors as $doctor): ?>
                            <option value="<?php echo htmlspecialchars($doctor['id']); ?>">
                                Dr. <?php echo htmlspecialchars($doctor['name']); ?> (<?php echo htmlspecialchars($doctor['specialty']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="password">Mot de passe</label>
                    <input type="password" id="password" name="password" placeholder="Entrez votre mot de passe" required>
                </div>

                <div class="info-msg">
                    💡 Mot de passe démo: <strong>demo</strong>
                </div>

                <button type="submit" class="btn-submit">Se connecter</button>
            </form>

            <div class="back-link">
                <a href="../index.php">← Retour à l'accueil</a>
            </div>
        </div>
    </div>
</body>
</html>
