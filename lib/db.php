<?php
function get_db(){
    static $pdo = null;
    if ($pdo) return $pdo;
    $cfg = require __DIR__ . '/config.php';
    $host = $cfg['db_host'];
    $port = $cfg['db_port'];
    $name = $cfg['db_name'];
    $user = $cfg['db_user'];
    $pass = $cfg['db_pass'];
    if (isset($cfg['db_charset'])) { $charset = $cfg['db_charset']; } else { $charset = 'utf8mb4'; }

    $dsn = "mysql:host={$host};port={$port};dbname={$name};charset={$charset}";
    try{
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }catch(PDOException $e){
        die('DB connection failed: '.$e->getMessage());
    }
    return $pdo;
}

function base_url($path = '') {
    $script = $_SERVER['SCRIPT_NAME'];
    $dir = dirname($script);
    if ($dir === '/' || $dir === '\\') $dir = '';
    if ($path && $path[0] !== '/') $path = '/' . $path;
    return $dir . $path;
}
