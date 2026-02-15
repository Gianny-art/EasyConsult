<?php
// Test de connexion DB — upload this file to your server and open /db_test.php
require __DIR__ . '/../lib/db.php';
try {
    $pdo = get_db();
    echo 'OK — DB connected. Server version: ' . $pdo->getAttribute(PDO::ATTR_SERVER_VERSION);
} catch (Exception $e) {
    echo 'DB error: ' . htmlspecialchars($e->getMessage());
}
