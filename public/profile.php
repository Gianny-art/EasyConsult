<?php
require_once __DIR__ . '/../lib/db.php';
session_start();

// Redirect if not logged in
if (!isset($_SESSION['patient_id'])) {
    header('Location: login.php');
    exit;
}

$pdo = get_db();
$patient_id = $_SESSION['patient_id'];
$err = '';
$success = '';

// Get patient data
try {
    $stmt = $pdo->prepare('SELECT * FROM patients WHERE id = ?');
    $stmt->execute([$patient_id]);
    $patient = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$patient) {
        header('Location: login.php');
        exit;
    }
} catch (Exception $e) {
    $err = 'Erreur lors du chargement du profil';
    $patient = ['id' => $patient_id, 'name' => 'Patient'];
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    $poids = isset($_POST['poids']) ? floatval($_POST['poids']) : null;
    $taille = isset($_POST['taille']) ? floatval($_POST['taille']) : null;
    $groupe_sanguin = trim($_POST['groupe_sanguin'] ?? '');
    $rhesus = trim($_POST['rhesus'] ?? '');
    

    // Calculate IMC
    $imc = null;
    if ($poids > 0 && $taille > 0) {
        $imc = round($poids / ($taille * $taille), 2);
    }

    try {
        $stmt = $pdo->prepare('
            UPDATE patients 
            SET weight = ?, height = ?, imc = ?, blood_group = ?, rhesus = ?
            WHERE id = ?
        ');
        $stmt->execute([$poids, $taille, $imc, $groupe_sanguin, $rhesus, $patient_id]);
        $success = 'Profil mis à jour avec succès';
        
        // Refresh patient data
        $stmt = $pdo->prepare('SELECT * FROM patients WHERE id = ?');
        $stmt->execute([$patient_id]);
        $patient = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $err = 'Erreur lors de la mise à jour: ' . htmlspecialchars($e->getMessage());
    }
}

// Get patient's consultations
try {
    $stmt = $pdo->prepare('
        SELECT c.*, d.name as doctor_name, d.specialty
        FROM consultations c
        LEFT JOIN doctors d ON c.doctor_id = d.id
        WHERE c.patient_id = ?
        ORDER BY c.date DESC
        LIMIT 10
    ');
    $stmt->execute([$patient_id]);
    $consultations = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $consultations = [];
}

// Get uploaded documents
try {
    $stmt = $pdo->prepare('
        SELECT * FROM uploads
        WHERE patient_id = ?
        ORDER BY date_upload DESC
    ');
    $stmt->execute([$patient_id]);
    $documents = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $documents = [];
}

// Determine IMC status
$imc_status = '';
if ($patient['imc']) {
    if ($patient['imc'] < 18.5) {
        $imc_status = 'Insuffisance pondérale';
    } elseif ($patient['imc'] < 25) {
        $imc_status = 'Poids normal';
    } elseif ($patient['imc'] < 30) {
        $imc_status = 'Surpoids';
    } else {
        $imc_status = 'Obésité';
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon Profil - EasyConsult</title>
    <meta name="description" content="Gestion de votre profil EasyConsult. Consultez vos rendez-vous, votre historique médical et vos factures en toute sécurité.">
    <meta property="og:title" content="EasyConsult - Mon Profil">
    <meta property="og:description" content="Gérez votre compte EasyConsult et vos consultations médicales.">
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://easyconsult.wuaze.com/profile.php">
    <link rel="stylesheet" href="./style.css">
    <style>
        .profile-container {
            min-height: 100vh;
            background: linear-gradient(135deg, #f0f9ff 0%, #f0fdf4 100%);
            padding: 2rem;
        }

        .profile-header {
            max-width: 1200px;
            margin: 0 auto 2rem;
            background: white;
            border-radius: 1.5rem;
            padding: 2rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            animation: slideDown 0.5s ease-out;
        }

        .profile-header-content {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 2rem;
        }

        .profile-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), #0052a3);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 2.5rem;
            font-weight: 700;
            flex-shrink: 0;
        }

        .profile-info h1 {
            margin: 0 0 0.5rem 0;
            color: #1f2937;
        }

        .profile-info p {
            margin: 0;
            color: #6b7280;
            font-size: 0.95rem;
        }

        .profile-actions {
            display: flex;
            gap: 1rem;
        }

        .btn-action {
            padding: 0.75rem 1.5rem;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 0.75rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-action:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 102, 204, 0.3);
        }

        .btn-logout {
            background: #dc2626;
        }

        .btn-logout:hover {
            box-shadow: 0 5px 15px rgba(220, 38, 38, 0.3);
        }

        .profile-content {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
        }

        .profile-section {
            background: white;
            border-radius: 1.5rem;
            padding: 2rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            animation: slideUp 0.5s ease-out;
        }

        .profile-section h2 {
            color: var(--primary);
            margin-top: 0;
            margin-bottom: 1.5rem;
            font-size: 1.3rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
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
            box-sizing: border-box;
            transition: all 0.3s ease;
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(0, 102, 204, 0.1);
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .imc-box {
            background: linear-gradient(135deg, #fef3c7 0%, #fef08a 100%);
            border: 2px solid #fcd34d;
            border-radius: 1rem;
            padding: 1.5rem;
            margin: 1.5rem 0;
            text-align: center;
        }

        .imc-value {
            font-size: 2.5rem;
            font-weight: 700;
            color: #b45309;
            margin-bottom: 0.5rem;
        }

        .imc-label {
            font-size: 0.95rem;
            color: #92400e;
            font-weight: 600;
        }

        .blood-group-box {
            background: #fee2e2;
            border: 2px solid #fecaca;
            border-radius: 1rem;
            padding: 1rem;
            margin: 1rem 0;
        }

        .blood-group-label {
            font-size: 0.9rem;
            color: #991b1b;
            font-weight: 600;
            text-transform: uppercase;
        }

        .blood-group-value {
            font-size: 1.5rem;
            color: #dc2626;
            font-weight: 700;
        }

        .consultations-list {
            max-height: 400px;
            overflow-y: auto;
        }

        .consultation-item {
            background: #f9fafb;
            border-left: 4px solid var(--primary);
            border-radius: 0.5rem;
            padding: 1rem;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
        }

        .consultation-item:hover {
            background: #f3f4f6;
            transform: translateX(4px);
        }

        .consultation-date {
            font-weight: 600;
            color: var(--primary);
            margin-bottom: 0.25rem;
        }

        .consultation-doctor {
            color: #1f2937;
            font-size: 0.95rem;
            margin-bottom: 0.25rem;
        }

        .consultation-status {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            background: #dbeafe;
            color: var(--primary);
            border-radius: 0.5rem;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .empty-state {
            text-align: center;
            padding: 2rem;
            color: #9ca3af;
        }

        .documents-grid {
            display: grid;
            gap: 1rem;
        }

        .document-item {
            background: #f9fafb;
            border: 2px solid #e5e7eb;
            border-radius: 0.75rem;
            padding: 1rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            transition: all 0.3s ease;
        }

        .document-item:hover {
            border-color: var(--primary);
            background: #f0f4ff;
        }

        .document-info {
            flex: 1;
        }

        .document-name {
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 0.25rem;
        }

        .document-date {
            font-size: 0.85rem;
            color: #9ca3af;
        }

        .document-link {
            padding: 0.5rem 1rem;
            background: var(--primary);
            color: white;
            text-decoration: none;
            border-radius: 0.5rem;
            font-weight: 600;
            font-size: 0.85rem;
            transition: all 0.3s ease;
        }

        .document-link:hover {
            background: #0052a3;
        }

        .btn-submit {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(135deg, var(--primary), #0052a3);
            color: white;
            border: none;
            border-radius: 0.75rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 1rem;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 102, 204, 0.3);
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

        @media (max-width: 768px) {
            .profile-header-content {
                flex-direction: column;
                text-align: center;
            }

            .profile-content {
                grid-template-columns: 1fr;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .profile-actions {
                justify-content: center;
                flex-wrap: wrap;
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
    <div class="profile-container">
        <!-- Profile Header -->
        <div class="profile-header">
            <div class="profile-header-content">
                <div style="display: flex; align-items: center; gap: 1.5rem; flex: 1;">
                    <div class="profile-avatar">
                        <?php echo strtoupper(substr($patient['name'] ?? 'U', 0, 1)); ?>
                    </div>
                    <div class="profile-info">
                        <h1>👤 <?php echo htmlspecialchars($patient['name'] ?? 'Patient'); ?></h1>
                        <p>📧 <?php echo htmlspecialchars($patient['email'] ?? ''); ?></p>
                        <p>📱 <?php echo htmlspecialchars($patient['phone'] ?? 'Non renseigné'); ?></p>
                    </div>
                </div>
                <div class="profile-actions">
                    <a href="book.php" class="btn-action">📅 Réserver RDV</a>
                    <a href="logout.php" class="btn-action btn-logout">🚪 Déconnexion</a>
                </div>
            </div>
        </div>

        <!-- Profile Content -->
        <div class="profile-content">
            <!-- Health Information Section -->
            <div class="profile-section">
                <h2>💊 Informations de santé</h2>

                <?php if ($err): ?>
                    <div class="alert alert-error"><?php echo htmlspecialchars($err); ?></div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success">✓ <?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>

                <form method="post">
                    <input type="hidden" name="action" value="update_profile">

                    <div class="form-row">
                        <div class="form-group">
                            <label for="groupe_sanguin">Groupe sanguin</label>
                            <select id="groupe_sanguin" name="groupe_sanguin" style="width: 100%; padding: 0.9rem 1rem; border: 2px solid #e5e7eb; border-radius: 0.75rem; font-size: 1rem;">
                                <option value="">-- Sélectionnez --</option>
                                <option value="A">A</option>
                                <option value="B">B</option>
                                <option value="AB">AB</option>
                                <option value="O">O</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="rhesus">Rhésus</label>
                            <select id="rhesus" name="rhesus" style="width: 100%; padding: 0.9rem 1rem; border: 2px solid #e5e7eb; border-radius: 0.75rem; font-size: 1rem;">
                                <option value="">-- Sélectionnez --</option>
                                <option value="+" <?php echo ($patient['rhesus'] === '+') ? 'selected' : ''; ?>>Positif (+)</option>
                                <option value="-" <?php echo ($patient['rhesus'] === '-') ? 'selected' : ''; ?>>Négatif (-)</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="taille">Taille (m)</label>
                            <input 
                                type="number" 
                                id="taille" 
                                name="taille" 
                                step="0.01" 
                                min="0.5"
                                max="2.5"
                                placeholder="Ex: 1.75"
                                value="<?php echo htmlspecialchars($patient['taille'] ?? ''); ?>"
                            >
                        </div>
                        <div class="form-group">
                            <label for="poids">Poids (kg)</label>
                            <input 
                                type="number" 
                                id="poids" 
                                name="poids" 
                                step="0.1"
                                min="0"
                                max="300"
                                placeholder="Ex: 75.5"
                                value="<?php echo htmlspecialchars($patient['poids'] ?? ''); ?>"
                            >
                        </div>
                    </div>

                    <?php if (!empty($patient['imc'])): ?>
                        <div class="imc-box">
                            <div class="imc-value"><?php echo htmlspecialchars($patient['imc']); ?></div>
                            <div class="imc-label">IMC - <?php echo htmlspecialchars($imc_status); ?></div>
                        </div>
                    <?php endif; ?>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Tension artérielle</label>
                            <?php if (!empty($patient['blood_pressure'])): ?>
                                <div style="padding:0.9rem;border:1px solid var(--gray-300);border-radius:0.5rem"><?php echo htmlspecialchars($patient['blood_pressure']); ?> mmHg</div>
                            <?php else: ?>
                                <div style="padding:0.9rem;border:1px dashed var(--gray-300);border-radius:0.5rem;color:var(--gray-500)">Synchroniser votre montre connectée pour calculer automatiquement la pression artérielle.</div>
                            <?php endif; ?>
                        </div>
                        <div class="form-group">
                            <label>Glycémie</label>
                            <?php if (!empty($patient['glycemia'])): ?>
                                <div style="padding:0.9rem;border:1px solid var(--gray-300);border-radius:0.5rem"><?php echo htmlspecialchars($patient['glycemia']); ?> mmol/L</div>
                            <?php else: ?>
                                <div style="padding:0.9rem;border:1px dashed var(--gray-300);border-radius:0.5rem;color:var(--gray-500)">Synchroniser votre montre connectée pour calculer automatiquement la glycémie.</div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php if (!empty($patient['groupe_sanguin'])): ?>
                        <div class="blood-group-box">
                            <div class="blood-group-label">Groupe sanguin</div>
                            <div class="blood-group-value">
                                <?php echo htmlspecialchars(($patient['groupe_sanguin'] ?? '') . ($patient['rhesus'] ?? '')); ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <button type="submit" class="btn-submit">✓ Sauvegarder les modifications</button>
                </form>
            </div>

            <!-- Right Column: Consultations & Documents -->
            <div>
                <!-- Consultations History -->
                <div class="profile-section" style="margin-bottom: 2rem;">
                    <h2>📋 Historique des consultations</h2>
                    <div class="consultations-list">
                        <?php if (count($consultations) > 0): ?>
                            <?php foreach ($consultations as $consultation): ?>
                                <a href="consultation.php?id=<?php echo urlencode($consultation['id']); ?>" class="consultation-item consultation-link">
                                    <div class="consultation-date">
                                        📅 <?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($consultation['date'] . ' ' . $consultation['start_time']))); ?>
                                    </div>
                                    <div class="consultation-doctor">
                                        👨‍⚕️ <?php echo htmlspecialchars($consultation['doctor_name'] ?? 'Médecin'); ?> - <?php echo htmlspecialchars($consultation['specialite'] ?? ''); ?>
                                    </div>
                                    <div>
                                        <span class="consultation-status">
                                                <?php
                                                    $cstat = $consultation['status'] ?? 'pending_payment';
                                                    if ($cstat === 'pending_payment') {
                                                        echo '⏳ En attente de paiement';
                                                    } elseif ($cstat === 'confirmed') {
                                                        echo '✓ Confirmée';
                                                    } elseif ($cstat === 'completed') {
                                                        echo '✓ Terminée';
                                                    } elseif ($cstat === 'cancelled') {
                                                        echo '✗ Annulée';
                                                    } else {
                                                        echo htmlspecialchars($cstat);
                                                    }
                                                ?>
                                        </span>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="empty-state">
                                <p>Aucune consultation enregistrée</p>
                                <a href="book.php" style="color: var(--primary); text-decoration: none; font-weight: 600;">Réserver une consultation →</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Documents Section -->
                <div class="profile-section">
                    <h2>📄 Documents médicaux</h2>
                    <div class="documents-grid">
                        <?php if (count($documents) > 0): ?>
                            <?php foreach ($documents as $doc): ?>
                                <div class="document-item">
                                    <div class="document-info">
                                        <div class="document-name">
                                            📎 <?php echo htmlspecialchars($doc['tag'] ?? 'Document'); ?>
                                        </div>
                                        <div class="document-date">
                                            <?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($doc['date_upload'] ?? 'now'))); ?>
                                        </div>
                                    </div>
                                    <a href="<?php echo htmlspecialchars($doc['file_path']); ?>" class="document-link" target="_blank">
                                        Voir
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="empty-state">
                                <p>Pas de documents uploadés</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // IMC calculation on input change
        const tailleInput = document.getElementById('taille');
        const poidsInput = document.getElementById('poids');

        function calculateIMC() {
            const taille = parseFloat(tailleInput.value);
            const poids = parseFloat(poidsInput.value);

            if (taille > 0 && poids > 0) {
                const imc = (poids / (taille * taille)).toFixed(2);
                console.log('IMC calculé:', imc);
            }
        }

        if (tailleInput) tailleInput.addEventListener('input', calculateIMC);
        if (poidsInput) poidsInput.addEventListener('input', calculateIMC);
    </script>
</body>
</html>
