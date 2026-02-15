<?php
require_once __DIR__ . '/../lib/db.php';
session_start();

// Redirect if already logged in
if (isset($_SESSION['patient_id'])) {
    header('Location: profile.php');
    exit;
}

$pdo = get_db();
$err = '';
$success = '';
$form_data = ['name' => '', 'email' => '', 'phone' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Store form data for repopulation
    $form_data = ['name' => $name, 'email' => $email, 'phone' => $phone];

    // Validation
    if (empty($name) || empty($email) || empty($password) || empty($confirm_password)) {
        $err = 'Veuillez remplir tous les champs obligatoires';
    } elseif (strlen($password) < 8) {
        $err = 'Le mot de passe doit contenir au moins 8 caractères';
    } elseif ($password !== $confirm_password) {
        $err = 'Les mots de passe ne correspondent pas';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $err = 'Format d\'email invalide';
    } else {
        try {
            // Check if email already exists
            $stmt = $pdo->prepare('SELECT id FROM patients WHERE email = ?');
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $err = 'Cet email est déjà enregistré';
            } else {
                // Insert new patient
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare('INSERT INTO patients (`name`, email, phone, password) VALUES (?, ?, ?, ?)');
                $stmt->execute([$name, $email, $phone, $hashed_password]);
                
                $success = 'Compte créé avec succès! Redirection vers la connexion...';
                $_SESSION['register_success'] = true;
                // Redirect after 2 seconds
                header('Refresh: 2; url=login.php');
            }
        } catch (PDOException $e) {
            $err = 'Erreur lors de l\'inscription: ' . htmlspecialchars($e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription - EasyConsult</title>
    <meta name="description" content="Inscrivez-vous sur EasyConsult et accédez à des consultations médicales en ligne. Paiement sécurisé par USSD. Santé accessible pour tous.">
    <meta property="og:title" content="EasyConsult - Créer un Compte">
    <meta property="og:description" content="Rejoignez EasyConsult et consultez des médecins qualifiés en ligne. Inscription gratuite et rapide.">
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://easyconsult.wuaze.com/register.php">
    <link rel="stylesheet" href="./style.css">
    <style>
        .auth-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            padding: 2rem;
        }

        .auth-card {
            background: white;
            border-radius: 1.5rem;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 500px;
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
            color: #10b981;
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

        .form-group input:focus {
            outline: none;
            border-color: #10b981;
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
        }

        .form-group input::placeholder {
            color: #9ca3af;
        }

        .password-requirements {
            background: #f3faf8;
            border: 1px solid #d1e3db;
            border-radius: 0.75rem;
            padding: 1rem;
            margin: 1.5rem 0;
            font-size: 0.9rem;
            color: #6b7280;
        }

        .password-requirements h4 {
            margin: 0 0 0.75rem 0;
            color: #1f2937;
            font-weight: 600;
        }

        .requirement-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.5rem;
        }

        .requirement-item.done {
            color: #10b981;
        }

        .requirement-item.pending {
            color: #9ca3af;
        }

        .requirement-check {
            width: 18px;
            height: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            min-width: 18px;
        }

        .btn-submit {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            border: none;
            border-radius: 0.75rem;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 1rem;
            margin-bottom: 1.5rem;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(16, 185, 129, 0.3);
        }

        .btn-submit:active {
            transform: translateY(0);
        }

        .btn-submit:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .auth-footer {
            text-align: center;
            margin-top: 2rem;
        }

        .auth-footer p {
            color: #6b7280;
            font-size: 0.95rem;
        }

        .auth-footer a {
            color: #10b981;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
        }

        .auth-footer a:hover {
            color: #059669;
        }

        .alert {
            padding: 1rem 1.25rem;
            border-radius: 0.75rem;
            margin-bottom: 1.5rem;
            font-size: 0.95rem;
            animation: slideDown 0.3s ease-out;
        }

        .alert-error {
            background: #fee2e2;
            border: 1px solid #fecaca;
            color: #dc2626;
        }

        .alert-success {
            background: #dcfce7;
            border: 1px solid #bbf7d0;
            color: #16a34a;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: #10b981;
            text-decoration: none;
            font-weight: 600;
            margin-bottom: 2rem;
            transition: all 0.3s ease;
        }

        .back-link:hover {
            gap: 0.75rem;
            color: #059669;
        }

        @media (max-width: 768px) {
            .auth-container {
                padding: 1rem;
            }

            .auth-card {
                padding: 2rem 1.5rem;
            }

            .auth-header h1 {
                font-size: 1.75rem;
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
    <?php include __DIR__ . '/../lib/nav.php'; ?>
    <div class="auth-container">
        <div class="auth-card">
            <a href="index.php" class="back-link">←  Retour à l'accueil</a>

            <div class="auth-header">
                <h1>Inscription</h1>
                <p>Créez votre compte EasyConsult</p>
            </div>

            <?php if ($err): ?>
                <div class="alert alert-error">
                    <strong>Erreur :</strong> <?php echo htmlspecialchars($err); ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    ✓ <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <form method="post" action="" id="registerForm">
                <div class="form-group">
                    <label for="name">Nom complet *</label>
                    <input 
                        type="text" 
                        id="name" 
                        name="name" 
                        placeholder="Jean Dupont" 
                        value="<?php echo htmlspecialchars($form_data['name']); ?>"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="email">Email *</label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        placeholder="votre.email@exemple.com" 
                        value="<?php echo htmlspecialchars($form_data['email']); ?>"
                        required
                        autocomplete="email"
                    >
                </div>

                <div class="form-group">
                    <label for="phone">Téléphone</label>
                    <input 
                        type="tel" 
                        id="phone" 
                        name="phone" 
                        placeholder="+237 6XX XXX XXX" 
                        value="<?php echo htmlspecialchars($form_data['phone']); ?>"
                    >
                </div>

                <div class="form-group">
                    <label for="password">Mot de passe *</label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        placeholder="Minimum 8 caractères" 
                        required
                        autocomplete="new-password"
                    >
                </div>

                <div class="password-requirements">
                    <h4>Les critères du mot de passe:</h4>
                    <div class="requirement-item pending" id="req-length">
                        <span class="requirement-check">○</span>
                        <span>Au moins 8 caractères</span>
                    </div>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirmer le mot de passe *</label>
                    <input 
                        type="password" 
                        id="confirm_password" 
                        name="confirm_password" 
                        placeholder="Confirmez votre mot de passe" 
                        required
                        autocomplete="new-password"
                    >
                </div>

                <button type="submit" class="btn-submit" id="submitBtn">Créer mon compte</button>
            </form>

            <div class="auth-footer">
                <p>Vous avez déjà un compte ? <a href="login.php">Se connecter</a></p>
            </div>
        </div>
    </div>

    <script>
        const passwordInput = document.getElementById('password');
        const confirmPasswordInput = document.getElementById('confirm_password');
        const reqLength = document.getElementById('req-length');
        const submitBtn = document.getElementById('submitBtn');

        function validatePassword() {
            const password = passwordInput.value;
            const isLongEnough = password.length >= 8;
            
            if (isLongEnough) {
                reqLength.classList.remove('pending');
                reqLength.classList.add('done');
                reqLength.querySelector('.requirement-check').textContent = '✓';
            } else {
                reqLength.classList.remove('done');
                reqLength.classList.add('pending');
                reqLength.querySelector('.requirement-check').textContent = '○';
            }
        }

        passwordInput.addEventListener('input', validatePassword);
        
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            const password = passwordInput.value;
            const confirmPassword = confirmPasswordInput.value;
            
            if (password.length < 8) {
                e.preventDefault();
                alert('Le mot de passe doit contenir au moins 8 caractères');
                return false;
            }
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Les mots de passe ne correspondent pas');
                return false;
            }
        });
    </script>
</body>
</html>
