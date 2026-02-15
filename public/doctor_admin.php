<?php
require_once __DIR__ . '/../lib/db.php';
session_start();
$pdo = get_db();
// Simple doctor toggle UI (no auth for demo)
if (isset($_GET['toggle']) && isset($_GET['id'])){
    $id = intval($_GET['id']);
    $d = $pdo->prepare('SELECT * FROM doctors WHERE id=?'); $d->execute([$id]); $d=$d->fetch(PDO::FETCH_ASSOC);
    if ($d){
        $new = $d['status']==='libre' ? 'occupé' : 'libre';
        $pdo->prepare('UPDATE doctors SET status=? WHERE id=?')->execute([$new,$id]);
        header('Location: doctor_admin.php'); exit;
    }
}
$doctors = $pdo->query('SELECT * FROM doctors')->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html><html><head><meta charset="utf-8"><title>Doctor admin</title><link rel="stylesheet" href="./style.css"></head><body>
<?php include __DIR__ . '/../lib/nav.php'; ?>
<div class="container"><div class="card"><h2>Doctors</h2>
<?php foreach($doctors as $d):?>
  <div class="slot">
    <strong><?=htmlspecialchars($d['name'])?></strong> — <span class="badge"><?=htmlspecialchars($d['status'])?></span>
    <a class="btn" href="doctor_admin.php?toggle=1&id=<?=$d['id']?>" style="float:right">Toggle</a>
  </div>
<?php endforeach;?>
</div></div>
</body></html>
