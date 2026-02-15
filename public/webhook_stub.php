<?php
// Simulated payment webhook (for testing). Call with ?invoice=ID&status=paid
require_once __DIR__ . '/../lib/db.php';
$pdo = get_db();
$invoice = $_GET['invoice'] ?? null;
$status = $_GET['status'] ?? 'paid';
if (!$invoice){ echo 'Missing invoice'; exit; }
$stmt = $pdo->prepare('SELECT * FROM invoices WHERE id=?'); $stmt->execute([$invoice]); $inv = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$inv){ echo 'Invoice not found'; exit; }
$pdo->prepare('UPDATE invoices SET payment_status=? WHERE id=?')->execute([$status,$invoice]);
$pdo->prepare('INSERT INTO payments (invoice_id,provider_txn_id,status,raw_payload,confirmed_at) VALUES (?,?,?,?,datetime("now"))')
    ->execute([$invoice,'SIMTXN'.$invoice,$status,json_encode($_GET)]);
echo 'ok';
