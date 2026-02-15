<?php
require_once __DIR__ . '/../lib/db.php';
session_start();

if (!isset($_SESSION['patient_id'])) {
    header('Location: login.php');
    exit;
}

$pdo = get_db();
$patient_id = $_SESSION['patient_id'];
$consultation_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Basic hospital config (modifiable)
$HOSPITAL_FEE = 5000; // montant fixe en FCFA
$HOSPITAL_USSD_HOTLINE = '9900'; // exemple de numéro interne
$USSD_PATTERN = '*150*{hospital}*{client}*{amount}#'; // modèle; {hospital},{client},{amount}

try {
    $stmt = $pdo->prepare('SELECT c.*, d.name as doctor_name, p.name as patient_name, p.phone as patient_phone FROM consultations c LEFT JOIN doctors d ON c.doctor_id = d.id LEFT JOIN patients p ON c.patient_id = p.id WHERE c.id = ? AND c.patient_id = ?');
    $stmt->execute([$consultation_id, $patient_id]);
    $consult = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $consult = null;
}

if (!$consult) {
    echo 'Consultation introuvable.'; exit;
}

?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Consultation #<?php echo htmlspecialchars($consultation_id); ?></title>
    <link rel="stylesheet" href="./style.css">
    <style>
        .consult-page{max-width:900px;margin:2rem auto;padding:1rem}
        .card{background:#fff;padding:1.2rem;border-radius:0.8rem;box-shadow:0 6px 18px rgba(2,6,23,0.08)}
        .pay-row{display:flex;gap:0.5rem;align-items:center}
        @media(max-width:600px){.pay-row{flex-direction:column;align-items:stretch}}
        a.consult-back{display:inline-block;margin-bottom:1rem;color:var(--primary);text-decoration:none}
    </style>
</head>
<body>
    <?php include __DIR__ . '/../lib/nav.php'; ?>
    <main class="consult-page">
        <a href="profile.php" class="consult-back">← Retour au profil</a>
        <div class="card">
            <h2>Consultation 📋</h2>
            <p><strong>Date:</strong> <?php echo htmlspecialchars($consult['date'] . ' ' . $consult['start_time']); ?></p>
            <p><strong>Médecin:</strong> <?php echo htmlspecialchars($consult['doctor_name'] ?? '—'); ?></p>
            <p><strong>Motif:</strong> <?php echo htmlspecialchars($consult['motif'] ?? '—'); ?></p>
            <p><strong>Statut:</strong> <?php echo htmlspecialchars($consult['status']); ?></p>

            <?php if (($consult['status'] ?? '') === 'pending_payment'): ?>
                <hr>
                <h3>Paiement</h3>
                <div class="card">
                    <p>Montant à payer: <strong><?php echo number_format($HOSPITAL_FEE); ?> FCFA</strong></p>

                    <label>Choisir un fournisseur:</label>
                    <div>
                        <label><input type="radio" name="provider" value="MTN" checked> MTN Mobile Money</label>
                        <label style="margin-left:1rem"><input type="radio" name="provider" value="ORANGE"> Orange Money</label>
                    </div>

                    <p style="margin-top:0.5rem">Numéro client: <strong><?php echo htmlspecialchars($consult['patient_phone'] ?? ''); ?></strong></p>

                    <div class="pay-row">
                        <button id="pay-btn" class="btn-action"
                            data-pattern-default="<?php echo htmlspecialchars($USSD_PATTERN); ?>"
                            data-pattern-mtn="*126*{client}*{amount}#"
                            data-pattern-orange="*144*{client}*{amount}#"
                            data-hospital="<?php echo htmlspecialchars($HOSPITAL_USSD_HOTLINE); ?>"
                            data-client="<?php echo htmlspecialchars($consult['patient_phone'] ?? ''); ?>"
                            data-amount="<?php echo htmlspecialchars($HOSPITAL_FEE); ?>"
                        >💳 Payer</button>
                        <small style="color:#6b7280">Le code USSD sera préparé et ouvert dans l'application téléphonique pour validation.</small>
                    </div>
                </div>
            <?php else: ?>
                <p>Cette consultation a le statut: <strong><?php echo htmlspecialchars($consult['status']); ?></strong></p>
            <?php endif; ?>
        </div>
    </main>

    <script src="./app.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function(){
        const payBtn = document.getElementById('pay-btn');
        if (!payBtn) return;

        payBtn.addEventListener('click', function(){
            // Determine selected provider
            const provider = document.querySelector('input[name="provider"]:checked')?.value || '';
            const patternDefault = this.dataset.patternDefault || '*150*{hospital}*{client}*{amount}#';
            const patternMTN = this.dataset.patternMtn || '';
            const patternOrange = this.dataset.patternOrange || '';
            const hospital = this.dataset.hospital || '';
            const client = this.dataset.client || '';
            const amount = this.dataset.amount || '';

            let pattern = patternDefault;
            if (provider === 'MTN' && patternMTN) pattern = patternMTN;
            if (provider === 'ORANGE' && patternOrange) pattern = patternOrange;

            let ussd = pattern.replace('{hospital}', hospital).replace('{client}', client).replace('{amount}', amount);
            const tel = 'tel:' + encodeURIComponent(ussd);
            window.location.href = tel;
        });
    });
    </script>
</body>
</html>
