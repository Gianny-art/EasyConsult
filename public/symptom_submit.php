<?php
require_once __DIR__ . '/../lib/db.php';
session_start();
$pdo = get_db();
$data = json_decode(file_get_contents('php://input'), true);
$text = trim($data['text'] ?? '');
if (!$text){ echo json_encode(['message'=>'Entrez des symptômes']); exit; }
// simple rule-based pseudo-diagnosis
$low = ['fatigue','toux','mal de gorge'];
$fever = ['fièvre','température','frissons'];
$resp = ['toux','essoufflement','respiratoire','oxygène'];
$score = 0;
foreach($fever as $w) if (stripos($text,$w)!==false) $score+=2;
foreach($resp as $w) if (stripos($text,$w)!==false) $score+=3;
foreach($low as $w) if (stripos($text,$w)!==false) $score+=1;

if ($score>=5) $msg = "Possible infection respiratoire. Consultez urgemment un médecin. (résultat indicatif)";
elseif ($score>=2) $msg = "Symptômes légers; surveillez et consultez un médecin si s'aggrave.";
else $msg = "Symptômes non spécifiques. Surveillez et consultez si persistance.";

// store
$pid = $_SESSION['patient_id'] ?? null;
$pdo->prepare('INSERT INTO symptom_checks (patient_id,input_text,ai_result,confidence,recommendation) VALUES (?,?,?,?,?)')
    ->execute([$pid,$text,$msg,min(1,$score/6), 'Consulter un médecin']);

echo json_encode(['message'=>$msg]);
