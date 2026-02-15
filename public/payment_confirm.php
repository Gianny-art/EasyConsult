<?php
require_once __DIR__ . '/../lib/db.php';
session_start();
if (!isset($_SESSION['patient_id'])) { header('Location: login.php'); exit; }
$pdo = get_db();
$patient_id = $_SESSION['patient_id'];
$invoice_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

$HOSPITAL_USSD_HOTLINE = '9900';

try {
    $stmt = $pdo->prepare('SELECT inv.*, p.phone as patient_phone FROM invoices inv LEFT JOIN consultations c ON inv.consultation_id = c.id LEFT JOIN patients p ON c.patient_id = p.id WHERE inv.id = ?');
    $stmt->execute([$invoice_id]);
    $inv = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $inv = null;
}

if (!$inv) { echo 'Facture non trouvée.'; exit; }
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Confirmation de paiement</title>
  <link rel="stylesheet" href="./style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    .payment-confirm-page{max-width:600px;margin:2rem auto;padding:1rem}
    .card{background:#fff;padding:1.5rem;border-radius:0.8rem;box-shadow:0 6px 18px rgba(2,6,23,0.08)}
    .recap{background:#f0fdf4;padding:1.5rem;border-radius:0.8rem;border-left:4px solid #10b981}
    .recap-line{display:flex;justify-content:space-between;margin:0.75rem 0;font-weight:600}
    .provider-select{display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin:1rem 0}
    .provider-btn{padding:1rem;border:2px solid var(--gray-300);border-radius:0.75rem;background:white;cursor:pointer;font-weight:600;transition:all 0.3s}
    .provider-btn.active{border-color:var(--primary);background:var(--primary-light);color:var(--primary)}
    .btn-confirm{width:100%;padding:1rem;background:var(--success);color:white;border:none;border-radius:0.75rem;font-weight:600;cursor:pointer;margin-top:1rem}
    .btn-cancel{background:var(--gray-300);color:var(--dark)}
    .alert{padding:0.75rem;background:#fee2e2;border-left:4px solid #dc2626;color:#dc2626;border-radius:0.5rem;margin:1rem 0}
  </style>
</head>
<body>
  <?php include __DIR__ . '/../lib/nav.php'; ?>
  <main class="payment-confirm-page">
    <a href="list_invoices.php" style="display:inline-block;margin-bottom:1rem;color:var(--primary);text-decoration:none">← Retour aux factures</a>

    <div class="card">
      <h2>Confirmation de paiement</h2>
      <p>Veuillez vérifier vos informations avant de procéder au paiement par USSD.</p>

      <div class="recap">
        <div class="recap-line">
          <span>Facture:</span>
          <strong>#<?php echo htmlspecialchars($inv['invoice_uuid']); ?></strong>
        </div>
        <div class="recap-line">
          <span>Montant:</span>
          <strong><?php echo number_format($inv['amount']); ?> FCFA</strong>
        </div>
        <div class="recap-line">
          <span>Numéro client:</span>
          <strong><?php echo htmlspecialchars($inv['patient_phone'] ?? ''); ?></strong>
        </div>
        <div class="recap-line">
          <span>Code hôpital:</span>
          <strong><?php echo htmlspecialchars($HOSPITAL_USSD_HOTLINE); ?></strong>
        </div>
      </div>

      <h3>Choisir un fournisseur</h3>
      <div class="provider-select">
        <button class="provider-btn active" data-provider="MTN">
          <i class="fa fa-mobile" style="font-size:1.5rem;margin-right:0.5rem"></i>MTN Money
        </button>
        <button class="provider-btn" data-provider="ORANGE">
          <i class="fa fa-mobile" style="font-size:1.5rem;margin-right:0.5rem"></i>Orange Money
        </button>
      </div>

      <div class="alert">
        ℹ️ Après confirmation, votre téléphone ouvrira le dialer avec le code USSD. Validez et saisissez votre code secret pour finaliser le paiement.
      </div>

      <button class="btn-confirm" id="confirm-btn">Confirmer et payer</button>
      <a href="list_invoices.php" class="btn btn-cancel" style="display:block;text-align:center;margin-top:0.75rem;text-decoration:none">Annuler</a>
    </div>
  </main>

  <script src="./app.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', function(){
      var selected = 'MTN';
      document.querySelectorAll('.provider-btn').forEach(btn => {
        btn.addEventListener('click', function(){
          document.querySelectorAll('.provider-btn').forEach(b => b.classList.remove('active'));
          this.classList.add('active');
          selected = this.dataset.provider;
        });
      });

      document.getElementById('confirm-btn').addEventListener('click', function(){
        const provider = selected;
        const patternMTN = '*126*{client}*{amount}#';
        const patternOrange = '*144*{client}*{amount}#';
        const pattern = (provider === 'MTN') ? patternMTN : patternOrange;
        const client = '<?php echo htmlspecialchars($inv['patient_phone'] ?? ''); ?>';
        const amount = '<?php echo htmlspecialchars($inv['amount']); ?>';
        const hospital = '<?php echo htmlspecialchars($HOSPITAL_USSD_HOTLINE); ?>';

        let ussd = pattern.replace('{hospital}', hospital).replace('{client}', client).replace('{amount}', amount);
        const tel = 'tel:' + encodeURIComponent(ussd);
        window.location.href = tel;
      });
    });
  </script>
  <!-- spacer for mobile nav -->
  <div style="height:76px"></div>
</body>
</html>
