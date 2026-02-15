<?php
require_once __DIR__ . '/../lib/db.php';
$pdo = get_db();
// Join to get patient phone (if linked to consultation)
$invoices = $pdo->query('SELECT inv.*, c.patient_id, p.phone as patient_phone FROM invoices inv LEFT JOIN consultations c ON inv.consultation_id = c.id LEFT JOIN patients p ON c.patient_id = p.id ORDER BY inv.created_at DESC')->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html>
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Factures - EasyConsult</title>
    <link rel="stylesheet" href="./style.css">
  </head>
  <body>
    <?php include __DIR__ . '/../lib/nav.php'; ?>

    <div class="container mt-4">
      <div class="card">
        <h2 class="card-title">Vos factures</h2>
        <p class="card-subtitle">Consultez et finalisez le paiement de vos factures récentes</p>
        <div class="grid grid-1 grid-2-lg">
          <?php if (count($invoices) === 0): ?>
            <div class="card">Aucune facture trouvée</div>
          <?php endif; ?>

          <?php foreach ($invoices as $inv): ?>
            <div class="card">
              <!-- Header: Invoice # and Amount -->
              <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1rem">
                <div>
                  <div style="font-weight:700;font-size:0.95rem">Facture #<?php echo htmlspecialchars($inv['invoice_uuid']); ?></div>
                  <div style="color:#6b7280;font-size:0.85rem">Créée: <?php echo htmlspecialchars($inv['created_at'] ?? ''); ?></div>
                </div>
                <div style="text-align:right">
                  <div style="font-size:1.2rem;font-weight:700"><?php echo number_format($inv['amount']); ?> FCFA</div>
                  <div style="margin-top:0.3rem"><span style="padding:0.3rem 0.6rem;border-radius:0.5rem;background:<?php echo ($inv['payment_status']==='paid')? 'rgba(16,185,129,0.12)' : 'rgba(245,158,11,0.12)'; ?>;color:<?php echo ($inv['payment_status']==='paid')? '#059669' : '#b45309'; ?>;font-weight:600;font-size:0.85rem"><?php echo htmlspecialchars($inv['payment_status']); ?></span></div>
                </div>
              </div>

              <!-- QR + Barcode: Mobile stack, Desktop row -->
              <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(80px,1fr));gap:1rem;margin:1rem 0;justify-items:center">
                <?php if (!empty($inv['qr_path'])): ?><img src="<?php echo htmlspecialchars($inv['qr_path']); ?>" style="width:80px;height:80px;border-radius:8px" alt="QR"><?php endif; ?>
                <?php if (!empty($inv['barcode_path'])): ?><img src="<?php echo htmlspecialchars($inv['barcode_path']); ?>" style="height:60px;border-radius:6px" alt="Barcode"><?php endif; ?>
              </div>

              <!-- Provider Badges -->
              <div style="display:flex;gap:0.5rem;margin:1rem 0;flex-wrap:wrap;justify-content:center">
                <div class="pm mtn">🟡 MTN</div>
                <div class="pm orange">🟠 Orange</div>
              </div>

              <!-- Action Button: Full width on mobile -->
              <div style="display:flex;gap:0.6rem;margin-top:1rem">
                <?php if ($inv['payment_status'] !== 'paid'): ?>
                  <a class="btn btn-primary" href="payment_confirm.php?id=<?php echo intval($inv['id']); ?>" style="flex:1;text-align:center">Payer</a>
                <?php else: ?>
                  <a class="btn btn-secondary" href="generate_invoice.php?invoice=<?php echo intval($inv['id']); ?>" style="flex:1;text-align:center">Voir</a>
                <?php endif; ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <script src="./app.js"></script>
    <!-- spacer so bottom mobile nav does not overlap content -->
    <div style="height:76px"></div>
  </body>
</html>
