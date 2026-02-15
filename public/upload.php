<?php
require_once __DIR__ . '/../lib/db.php';
session_start();
if (!isset($_SESSION['patient_id'])){ header('Location: login.php'); exit; }
$pid = $_SESSION['patient_id'];
$pdo = get_db();
if ($_SERVER['REQUEST_METHOD']==='POST'){
    if (!empty($_FILES['file']) && $_FILES['file']['error']===0){
        $uploaddir = __DIR__ . '/../uploads'; @mkdir($uploaddir,0755,true);
        $name = time()."_".basename($_FILES['file']['name']);
        $target = $uploaddir . '/' . $name;
        if (move_uploaded_file($_FILES['file']['tmp_name'],$target)){
            $path = '../uploads/'.$name;
            $stmt = $pdo->prepare('INSERT INTO uploads (patient_id,file_path,mime,tag,uploaded_by) VALUES (?,?,?,?,?)');
            $stmt->execute([$pid,$path,$_FILES['file']['type'],$_POST['tag'],'patient']);
        }
    }
}
header('Location: profile.php');
