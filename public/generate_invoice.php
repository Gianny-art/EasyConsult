    <?php include __DIR__ . '/../lib/nav.php'; ?>
    
    <?php
require_once __DIR__ . '/../lib/db.php';
$pdo = get_db();
if ($_SERVER['REQUEST_METHOD']!=='POST'){ http_response_code(405); echo 'Method'; exit; }
$cid = $_POST['consultation_id'] ?? null; $amount = floatval($_POST['amount'] ?? 0);
if (!$cid){ http_response_code(400); echo 'Missing consultation_id'; exit; }
$uuid = strtoupper(bin2hex(random_bytes(6)));
$pdo->prepare('INSERT INTO invoices (invoice_uuid,consultation_id,amount) VALUES (?,?,?)')
    ->execute([$uuid,$cid,$amount]);
$iid = $pdo->lastInsertId();

// Generate QR using Google Chart API link (stored as external URL)
$qrData = urlencode(json_encode(['invoice'=>$uuid,'id'=>$iid,'amount'=>$amount]));
$qrUrl = "https://chart.googleapis.com/chart?chs=250x250&cht=qr&chl={$qrData}";

// Generate barcode (Code39) as PNG
$invoicePath = '../uploads/invoices';
$barcodePath = $invoicePath.'/barcode_'.$iid.'.png';
$barcodeFile = __DIR__ . '/../uploads/invoices/barcode_'.$iid.'.png';
@mkdir(dirname($barcodeFile),0755,true);
$code = $uuid; // uppercase
$img = imagecreate(400,80);
$white = imagecolorallocate($img,255,255,255);
$black = imagecolorallocate($img,0,0,0);
imagefill($img,0,0,$white);
$fontw = 5;
imagestring($img, $fontw, 10, 55, $code, $black);
imagepng($img,$barcodeFile);
imagedestroy($img);

$pdo->prepare('UPDATE invoices SET qr_path=?,barcode_path=? WHERE id=?')->execute([$qrUrl,$barcodePath,$iid]);

header('Content-Type: application/json');
echo json_encode(['invoice_id'=>$iid,'uuid'=>$uuid,'qr'=>$qrUrl,'barcode'=>$barcodePath]);
