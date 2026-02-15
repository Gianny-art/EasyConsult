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
$assigned_doctor = null;
$consultation = null;

// Get available doctors (status = 'libre' and available slots)
try {
    $stmt = $pdo->prepare('
        SELECT d.*, COUNT(c.id) as appointment_count
        FROM doctors d
        LEFT JOIN consultations c ON d.id = c.doctor_id AND c.date = CURRENT_DATE
        WHERE d.status = "libre"
        GROUP BY d.id
        ORDER BY appointment_count ASC, d.name ASC
    ');
    $stmt->execute();
    $available_doctors = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $available_doctors = [];
    $err = 'Erreur lors du chargement des médecins disponibles';
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $consultation_date = $_POST['consultation_date'] ?? '';
    $consultation_time = $_POST['consultation_time'] ?? '';
    $reason = trim($_POST['reason'] ?? '');

    if (empty($consultation_date) || empty($consultation_time) || empty($reason)) {
        $err = 'Veuillez remplir tous les champs';
        if (isset($_GET['debug']) && $_GET['debug'] === '1') {
            $err .= ' — Données POST: ' . htmlspecialchars(print_r($_POST, true));
        }
    } else {
        try {
            // Auto-assign doctor based on:
            // 1. Status = libre
            // 2. Fewest appointments on this date
            // 3. Sort by name for consistency
            $stmt = $pdo->prepare('
                SELECT d.id, d.name, d.specialty, COUNT(c.id) as appointment_count
                FROM doctors d
                LEFT JOIN consultations c ON d.id = c.doctor_id AND c.date = ? AND c.start_time = ?
                WHERE d.status = "libre"
                GROUP BY d.id
                ORDER BY appointment_count ASC, d.name ASC
                LIMIT 1
            ');
            $stmt->execute([$consultation_date, $consultation_time]);
            $doctor = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$doctor) {
                $err = 'Aucun médecin disponible à cette date et heure';
            } else {
                // Check if this slot is already fully booked
                $slot_check = $pdo->prepare('
                    SELECT COUNT(*) FROM consultations 
                    WHERE doctor_id = ? AND date = ? AND start_time = ?
                    LIMIT 3
                ');
                $slot_check->execute([$doctor['id'], $consultation_date, $consultation_time]);
                if ($slot_check->fetchColumn() >= 3) {
                    $err = 'Ce créneau horaire n\'a plus de place disponible';
                } else {
                    // Create consultation
                    $insert_stmt = $pdo->prepare('
                        INSERT INTO consultations (patient_id, doctor_id, date, start_time, motif, status)
                        VALUES (?, ?, ?, ?, ?, "pending_payment")
                    ');
                    $insert_stmt->execute([
                        $patient_id,
                        $doctor['id'],
                        $consultation_date,
                        $consultation_time,
                        $reason
                    ]);

                    $assigned_doctor = $doctor;
                    $success = 'Médecin attribué automatiquement. Veuillez procéder au paiement.';
                }
            }
        } catch (PDOException $e) {
            $err = 'Erreur lors de la réservation: ' . htmlspecialchars($e->getMessage());
        }
    }
}

// Get minimum date (today)
$min_date = date('Y-m-d');
$max_date = date('Y-m-d', strtotime('+30 days'));
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prendre RDV - EasyConsult</title>
    <link rel="stylesheet" href="./style.css">
    <style>
        .book-container {
            min-height: 100vh;
            background: linear-gradient(135deg, #f0f9ff 0%, #f0fdf4 100%);
            padding: 2rem;
        }

        .book-header {
            background: white;
            padding: 1.5rem;
            border-radius: 1rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            animation: slideDown 0.5s ease-out;
        }

        .book-header h1 {
            color: var(--primary);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .book-header p {
            color: #6b7280;
            margin: 0.5rem 0 0 0;
            font-size: 0.95rem;
        }

        .book-content {
            max-width: 800px;
            margin: 0 auto;
        }

        .form-card {
            background: white;
            border-radius: 1.5rem;
            padding: 2.5rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            animation: slideUp 0.5s ease-out;
        }

        .form-section {
            margin-bottom: 2rem;
        }

        .form-section h3 {
            color: #1f2937;
            font-size: 1.1rem;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .section-number {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            background: linear-gradient(135deg, var(--primary), #0052a3);
            color: white;
            border-radius: 50%;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 0.6rem;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.9rem 1rem;
            border: 2px solid #e5e7eb;
            border-radius: 0.75rem;
            font-size: 1rem;
            font-family: inherit;
            transition: all 0.3s ease;
            box-sizing: border-box;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(0, 102, 204, 0.1);
        }

        .form-group textarea {
            min-height: 100px;
            resize: vertical;
        }

        .time-slots {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(80px, 1fr));
            gap: 0.75rem;
            margin-bottom: 1rem;
        }

        .time-slot {
            padding: 0.75rem;
            border: 2px solid #e5e7eb;
            border-radius: 0.5rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            background: white;
            font-weight: 500;
            font-size: 0.9rem;
        }

        .time-slot:hover {
            border-color: var(--primary);
            background: #f0f4ff;
        }

        .time-slot.selected {
            background: linear-gradient(135deg, var(--primary), #0052a3);
            color: white;
            border-color: var(--primary);
        }

        .doctor-info {
            background: #f8fbff;
            border: 2px solid #dbeafe;
            border-radius: 1rem;
            padding: 1.5rem;
            margin: 1.5rem 0;
            animation: slideUp 0.4s ease-out;
        }

        .doctor-info h4 {
            margin: 0 0 0.5rem 0;
            color: var(--primary);
            font-size: 0.95rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .doctor-details {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

        .doctor-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), #0052a3);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 1.5rem;
            flex-shrink: 0;
        }

        .doctor-text h5 {
            margin: 0 0 0.25rem 0;
            font-size: 1.1rem;
            color: #1f2937;
        }

        .doctor-text p {
            margin: 0;
            color: #6b7280;
            font-size: 0.9rem;
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

        .btn-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-top: 2rem;
        }

        .btn-primary {
            padding: 1rem;
            background: linear-gradient(135deg, var(--primary), #0052a3);
            color: white;
            border: none;
            border-radius: 0.75rem;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0, 102, 204, 0.3);
        }

        .btn-secondary {
            padding: 1rem;
            background: #f3f4f6;
            color: #1f2937;
            border: 2px solid #e5e7eb;
            border-radius: 0.75rem;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .btn-secondary:hover {
            background: white;
            border-color: var(--primary);
        }

        @media (max-width: 768px) {
            .form-card {
                padding: 1.5rem;
            }

            .btn-container {
                grid-template-columns: 1fr;
            }

            .doctor-details {
                flex-direction: column;
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
    <?php include __DIR__ . '/../lib/nav.php'; ?>
    <div class="book-container">
        <div class="book-header">
            <h1>
                📅 Prendre rendez-vous
            </h1>
            <p>Réservez une consultation avec un médecin disponible. Vous serez automatiquement assigné au meilleur praticien.</p>
        </div>

        <div class="book-content">
            <?php if ($err): ?>
                <div class="alert alert-error">
                    <strong>⚠️ Erreur :</strong> <?php echo htmlspecialchars($err); ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <strong>✓ Succès :</strong> <?php echo htmlspecialchars($success); ?>
                </div>

                <?php if ($assigned_doctor): ?>
                    <div class="doctor-info">
                        <h4>📋 Médecin assigné</h4>
                        <div class="doctor-details">
                            <div class="doctor-avatar">
                                <?php echo strtoupper(substr($assigned_doctor['name'], 0, 1)); ?>
                            </div>
                            <div class="doctor-text">
                                <h5><?php echo htmlspecialchars($assigned_doctor['name']); ?></h5>
                                <p><?php echo htmlspecialchars($assigned_doctor['specialty']); ?> - Dr. </p>
                            </div>
                        </div>
                    </div>

                    <div class="btn-container">
                        <a href="generate_invoice.php" class="btn-primary">💳 Procéder au paiement</a>
                        <a href="profile.php" class="btn-secondary">← Retour au profil</a>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="form-card">
                    <form method="post" action="">
                        <!-- Step 1: Reason -->
                        <div class="form-section">
                            <h3>
                                <span class="section-number">1</span>
                                Motif de la consultation
                            </h3>
                            <div class="form-group">
                                <label for="reason">Décrivez votre problème de santé</label>
                                <textarea 
                                    id="reason" 
                                    name="reason" 
                                    placeholder="Ex: Mal de tête persistant, fièvre depuis 3 jours, problèmes de sommeil..."
                                    required
                                ></textarea>
                            </div>
                        </div>

                        <!-- Step 2: Date -->
                        <div class="form-section">
                            <h3>
                                <span class="section-number">2</span>
                                Date de consultation
                            </h3>
                            <div class="form-group">
                                <label for="consultation_date">Choisissez une date</label>
                                <input 
                                    type="date" 
                                    id="consultation_date" 
                                    name="consultation_date" 
                                    min="<?php echo $min_date; ?>"
                                    max="<?php echo $max_date; ?>"
                                    required
                                >
                            </div>
                        </div>

                        <!-- Step 3: Time -->
                        <div class="form-section">
                            <h3>
                                <span class="section-number">3</span>
                                Heure de consultation
                            </h3>
                            <div class="form-group">
                                <label for="consultation_time">Sélectionnez une heure</label>
                                <input 
                                    type="time" 
                                    id="consultation_time" 
                                    name="consultation_time" 
                                    required
                                >
                            </div>
                            <small style="color: #6b7280;">
                                Les consultations sont disponibles de 08:00 à 18:00
                            </small>
                        </div>

                        <div style="background: #fef3c7; border: 1px solid #fcd34d; border-radius: 0.75rem; padding: 1rem; margin: 2rem 0; color: #92400e; font-size: 0.9rem;">
                            <strong>ℹ️ Information:</strong> Un médecin vous sera automatiquement assigné en fonction de sa disponibilité et de sa charge de travail. L'attribution est optimisée pour vous offrir le meilleur service.
                        </div>

                        <div class="btn-container">
                            <button type="submit" class="btn-primary">🚀 Réserver ma consultation</button>
                            <a href="profile.php" class="btn-secondary">← Annuler</a>
                        </div>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Time slot generation
        const timeInput = document.getElementById('consultation_time');
        if (timeInput) {
            // Set default time to 09:00
            timeInput.value = '09:00';
        }

        // Date validation
        const dateInput = document.getElementById('consultation_date');
        if (dateInput) {
            const today = new Date().toISOString().split('T')[0];
            dateInput.min = today;
        }
    </script>
</body>
</html>
