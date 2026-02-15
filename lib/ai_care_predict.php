<?php
// Simple PHP wrapper that calls the Python predictor to classify care type.
// POST JSON: { "text": "symptoms here" }
header('Content-Type: application/json');
session_start();
if ($_SERVER['REQUEST_METHOD']!=='POST') { echo json_encode(['error'=>'POST required']); exit; }
$data = json_decode(file_get_contents('php://input'), true);
$text = trim($data['text'] ?? '');
if ($text === '') { echo json_encode(['error'=>'text required']); exit; }

$py = __DIR__ . '/care_trainer.py';
if (!file_exists($py)) { echo json_encode(['error'=>'predictor missing']); exit; }

$cmd = escapeshellcmd("python \"". $py ."\" predict --text \"" . addslashes($text) ."\"");
// Execute and capture output
exec($cmd, $out, $rc);
if ($rc !== 0) {
    echo json_encode(['error'=>'prediction failed','rc'=>$rc,'output'=>$out]);
    exit;
}
$pred = trim(implode("\n", $out));
echo json_encode(['prediction'=>$pred]);
