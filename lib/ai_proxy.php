<?php
// Simple placeholder proxy for advanced AI integration.
// This file intentionally does not call any external API by default.
// To enable: create a file lib/ai_config.php with an array returning ['provider'=>'openai','api_key'=>'KEY']

header('Content-Type: application/json');
session_start();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['error'=>'POST required']); exit; }
$body = json_decode(file_get_contents('php://input'), true) ?: [];
$prompt = $body['prompt'] ?? '';
if (!$prompt) { echo json_encode(['error'=>'missing prompt']); exit; }

$cfgFile = __DIR__ . '/ai_config.php';
if (!file_exists($cfgFile)) {
    echo json_encode(['error'=>'AI integration not configured','hint'=>'Create lib/ai_config.php returning ["provider"=>"openai","api_key"=>"..."]']);
    exit;
}

$cfg = include $cfgFile;
if (empty($cfg['provider']) || empty($cfg['api_key'])) {
    echo json_encode(['error'=>'invalid ai_config','hint'=>'Provide provider and api_key in lib/ai_config.php']); exit;
}

// Placeholder: do NOT implement third-party calls here without config.
// For now return a safe canned response pointing to next steps.
echo json_encode([
    'provider'=>$cfg['provider'],
    'response'=>'AI proxy configured but external calls are disabled in this demo. Provide implementation for '.$cfg['provider'].' in lib/ai_proxy.php or enable in this file.',
    'note'=>'See lib/ai_config.php for configuration instructions.'
]);
