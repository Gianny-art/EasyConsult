<?php
require_once __DIR__ . '/../lib/db.php';
session_start();

// Redirect if already logged in
if (isset($_SESSION['patient_id'])) {
    header('Location: profile.php');
    exit;
}

if (isset($_SESSION['doctor_id'])) {
    header('Location: ./admin/index.php');
    exit;
}

$pdo = get_db();
$err = '';
$success = '';
$login_type = $_GET['type'] ?? 'patient'; // patient or doctor

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login_type = $_POST['login_type'] ?? 'patient';
    
    if ($login_type === 'doctor') {
        // Doctor login
        $doctor_id = intval($_POST['doctor_id'] ?? 0);
        $password = $_POST['password'] ?? '';

        if ($doctor_id > 0 && !empty($password)) {
            try {
                $stmt = $pdo->prepare('SELECT id, name, specialty, password FROM doctors WHERE id = ?');
                $stmt->execute([$doctor_id]);
                $doctor = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($doctor && ($password === 'demo' || empty($doctor['password']) || password_verify($password, $doctor['password']))) {
                    $_SESSION['doctor_id'] = $doctor['id'];
                    $_SESSION['doctor_name'] = $doctor['name'];
                    $_SESSION['doctor_specialty'] = $doctor['specialty'];
                    header('Location: ./admin/index.php');
                    exit;
                } else {
                    $err = 'ID médecin ou mot de passe incorrect';
                }
            } catch (Exception $e) {
                $err = 'Erreur: ' . htmlspecialchars($e->getMessage());
            }
        } else {
            $err = 'Veuillez sélectionner un médecin et entrer le mot de passe';
        }
    } else {
        // Patient login
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (!empty($email) && !empty($password)) {
            try {
                $stmt = $pdo->prepare('SELECT id, `name`, email, password FROM patients WHERE email = ?');
                $stmt->execute([$email]);
                $patient = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($patient && password_verify($password, $patient['password'])) {
                    $_SESSION['patient_id'] = $patient['id'];
                    $_SESSION['patient_name'] = $patient['name'];
                    $_SESSION['patient_email'] = $patient['email'];
                    header('Location: profile.php');
                    exit;
                } else {
                    $err = 'Email ou mot de passe incorrect';
                }
            } catch (Exception $e) {
                $err = 'Erreur système: ' . htmlspecialchars($e->getMessage());
            }
        } else {
            $err = 'Veuillez remplir tous les champs';
        }
    }
}

// Get list of doctors for dropdown (ALWAYS load, needed for form)
$doctors = [];
try {
    $doctors = $pdo->query('SELECT id, name, specialty FROM doctors ORDER BY name ASC')->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $doctors = [];
}

// Mock Google OAuth flow (for simulation)
if (isset($_GET['google_auth'])) {
    // In production, you would integrate with Google OAuth API
    // For now, we'll simulate a Google login
    $mock_email = 'patient' . rand(1000, 9999) . '@gmail.com';
    $mock_name = 'Patient Google';
    
    // Check if user exists, if not create them
    $stmt = $pdo->prepare('SELECT id FROM patients WHERE email = ?');
    $stmt->execute([$mock_email]);
    $exists = $stmt->fetch();
    
    if (!$exists) {
        $stmt = $pdo->prepare('INSERT INTO patients (`name`, email, password) VALUES (?, ?, ?)');
        $hashed_password = password_hash('google-oauth-' . time(), PASSWORD_DEFAULT);
        $stmt->execute([$mock_name, $mock_email, $hashed_password]);
        $patient_id = $pdo->lastInsertId();
    } else {
        $patient_id = $exists['id'];
    }
    
    $_SESSION['patient_id'] = $patient_id;
    $_SESSION['patient_name'] = $mock_name;
    $_SESSION['patient_email'] = $mock_email;
    header('Location: profile.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - EasyConsult</title>
    <meta name="description" content="Connexion à EasyConsult - Accédez à votre compte pour consulter un médecin en ligne. Télémédecine moderne et accessible.">
    <meta property="og:title" content="EasyConsult - Se Connecter">
    <meta property="og:description" content="Connexion sécurisée à EasyConsult. Consultez des médecins en ligne en quelques clics.">
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://easyconsult.wuaze.com/login.php">
    <link rel="stylesheet" href="./style.css">
    <style>
        .auth-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, var(--primary) 0%, #0052a3 100%);
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
            color: var(--primary);
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
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(0, 102, 204, 0.1);
        }

        .form-group input::placeholder {
            color: #9ca3af;
        }

        .remember-me {
            display: flex;
            align-items: center;
            margin-bottom: 2rem;
            gap: 0.5rem;
        }

        .remember-me input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
            accent-color: var(--primary);
        }

        .remember-me label {
            margin: 0;
            cursor: pointer;
            color: #6b7280;
            font-weight: 500;
            font-size: 0.9rem;
        }

        .btn-submit {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(135deg, var(--primary) 0%, #0052a3 100%);
            color: white;
            border: none;
            border-radius: 0.75rem;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-bottom: 1.5rem;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0, 102, 204, 0.3);
        }

        .btn-submit:active {
            transform: translateY(0);
        }

        .divider {
            display: flex;
            align-items: center;
            margin: 2rem 0;
            color: #d1d5db;
        }

        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: #d1d5db;
        }

        .divider span {
            margin: 0 1rem;
            color: #9ca3af;
            font-size: 0.9rem;
        }

        .btn-google {
            width: 100%;
            padding: 1rem;
            background: white;
            border: 2px solid #e5e7eb;
            border-radius: 0.75rem;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            color: #1f2937;
        }

        .btn-google:hover {
            border-color: var(--primary);
            background: #f0f4ff;
            box-shadow: 0 4px 12px rgba(0, 102, 204, 0.15);
        }

        .btn-google svg {
            width: 20px;
            height: 20px;
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
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
        }

        .auth-footer a:hover {
            color: #0052a3;
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
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
            margin-bottom: 2rem;
            transition: all 0.3s ease;
        }

        .back-link:hover {
            gap: 0.75rem;
            color: #0052a3;
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

        /* Tabs styling */
        .tabs {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 2rem;
            border-bottom: 2px solid #e5e7eb;
        }

        .tab-btn {
            padding: 0.75rem 1.5rem;
            background: transparent;
            border: none;
            cursor: pointer;
            font-weight: 600;
            color: #9ca3af;
            transition: all 0.3s ease;
            border-bottom: 3px solid transparent;
            font-size: 0.95rem;
        }

        .tab-btn.active {
            color: var(--primary);
            border-bottom-color: var(--primary);
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
            animation: slideUp 0.3s ease-out;
        }

        .form-group select {
            width: 100%;
            padding: 0.9rem 1rem;
            border: 2px solid #e5e7eb;
            border-radius: 0.75rem;
            font-size: 1rem;
            font-family: inherit;
            transition: all 0.3s ease;
            box-sizing: border-box;
        }

        .form-group select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(0, 102, 204, 0.1);
        }

        .demo-hint {
            background: #f0fdf4;
            color: #166534;
            padding: 0.75rem;
            border-radius: 0.5rem;
            font-size: 0.85rem;
            margin-bottom: 1rem;
            border-left: 3px solid #10b981;
        }
    </style>
    <meta name="description" content="Connectez-vous à votre compte EasyConsult en tant que patient ou médecin pour accéder à vos rendez-vous, factures et plus encore.">
</head>
<body>
    <?php include __DIR__ . '/../lib/nav.php'; ?>
    <div class="auth-container">
        <div class="auth-card">
            <a href="index.php" class="back-link">←  Retour à l'accueil</a>

            <div class="auth-header">
                <h1>Connexion</h1>
                <p>Accédez à votre compte EasyConsult</p>
            </div>

            <?php if ($err): ?>
                <div class="alert alert-error">
                    <strong>Erreur :</strong> <?php echo htmlspecialchars($err); ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <!-- Tabs -->
            <div class="tabs">
                <button type="button" class="tab-btn <?php echo $login_type === 'patient' ? 'active' : ''; ?>" onclick="switchTab('patient')" id="tab-patient-btn">
                    👤 Patient
                </button>
                <button type="button" class="tab-btn <?php echo $login_type === 'doctor' ? 'active' : ''; ?>" onclick="switchTab('doctor')" id="tab-doctor-btn">
                    👨‍⚕️ Médecin
                </button>
            </div>

            <!-- Patient Form -->
            <form method="post" id="patient-form" class="tab-content <?php echo $login_type === 'patient' ? 'active' : ''; ?>">
                <input type="hidden" name="login_type" value="patient">
                
                <div class="form-group">
                    <label for="email">Email</label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        placeholder="votre.email@exemple.com" 
                        required
                        autocomplete="email"
                    >
                </div>

                <div class="form-group">
                    <label for="password">Mot de passe</label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        placeholder="Entrez votre mot de passe" 
                        required
                        autocomplete="current-password"
                    >
                </div>

                <div class="remember-me">
                    <input type="checkbox" id="remember" name="remember">
                    <label for="remember">Se souvenir de moi</label>
                </div>

                <button type="submit" class="btn-submit">Se connecter</button>

                <div class="divider">
                    <span>OU</span>
                </div>

                <a href="?google_auth=1" class="btn-google">
                    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <circle cx="12" cy="12" r="11" fill="#EA4335" opacity="0.1"/>
                        <path d="M12 9.5c1.38 0 2.52.56 3.27 1.52l2.42-2.3C16.76 6.45 14.6 5 12 5c-3.14 0-5.84 2.04-6.83 4.89l2.85 2.2c.56-1.73 2.23-2.58 4.01-2.58z" fill="#EA4335"/>
                        <path d="M12 19c2.37 0 4.36-.89 5.73-2.36l-2.73-2.12c-.75.54-1.78.9-3 .9-1.73 0-3.2-1.11-3.73-2.6H5.4v2.27c1 1.95 3.18 3.91 6.6 3.91z" fill="#34A853"/>
                        <path d="M12 14.13c-1.04 0-1.97-.35-2.71-.93l-2.85 2.2c.99 1.94 3.15 3.27 5.56 3.27 1.22 0 2.24-.35 3-.9l2.73 2.12c-1.37 1.47-3.36 2.36-5.73 2.36-3.42 0-5.6-1.96-6.6-3.91v-2.27h2.84c.53 1.49 2 2.56 3.73 2.56z" fill="#FBBC04"/>
                        <path d="M5.4 11.87H5.27c-.13 0-.24.05-.33.14l-.02-.01c.08-.1.19-.16.33-.16h.13v.03z" fill="#1F2937"/>
                    </svg>
                    Se connecter avec Google
                </a>
            </form>

            <!-- Doctor Form -->
            <form method="post" id="doctor-form" class="tab-content <?php echo $login_type === 'doctor' ? 'active' : ''; ?>">
                <input type="hidden" name="login_type" value="doctor">

                <div class="demo-hint">
                    💡 Mot de passe démo: <strong>demo</strong>
                </div>
                
                <div class="form-group">
                    <label for="doctor_id">Sélectionnez votre profil</label>
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
                    <label for="doctor_password">Mot de passe</label>
                    <input 
                        type="password" 
                        id="doctor_password" 
                        name="password" 
                        placeholder="Entrez votre mot de passe" 
                        required
                    >
                </div>

                <button type="submit" class="btn-submit">Accéder au Dashboard</button>
            </form>

            <div class="auth-footer">
                <p>Pas encore inscrit ? <a href="register.php">Créer un compte patient</a></p>
                <p style="margin-top: 1rem; font-size: 0.85rem; color: #9ca3af;">
                    <a href="urgences.php" style="color: #dc2626; text-decoration: none;">Situation d'urgence ?</a>
                </p>
            </div>
        </div>
    </div>

    <script>
        function switchTab(tab) {
            // Hide all tabs
            document.getElementById('patient-form').classList.remove('active');
            document.getElementById('doctor-form').classList.remove('active');
            document.getElementById('tab-patient-btn').classList.remove('active');
            document.getElementById('tab-doctor-btn').classList.remove('active');

            // Show selected tab
            if (tab === 'patient') {
                document.getElementById('patient-form').classList.add('active');
                document.getElementById('tab-patient-btn').classList.add('active');
            } else if (tab === 'doctor') {
                document.getElementById('doctor-form').classList.add('active');
                document.getElementById('tab-doctor-btn').classList.add('active');
            }
        }
    </script>
</body>
</html>
