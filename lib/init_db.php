<?php
// Initialize MySQL DB tables using lib/config.php (for WAMP)
$cfg = require __DIR__ . '/config.php';
$host = $cfg['db_host'];
$port = $cfg['db_port'];
$name = $cfg['db_name'];
$user = $cfg['db_user'];
$pass = $cfg['db_pass'];
if (isset($cfg['db_charset'])) { $charset = $cfg['db_charset']; } else { $charset = 'utf8mb4'; }

$dsn = "mysql:host={$host};port={$port};charset={$charset}";
try{
    $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
    // Create database if not exists
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$name}` CHARACTER SET {$charset} COLLATE {$charset}_general_ci");
    $pdo->exec("USE `{$name}`");

    // Create tables
    $pdo->exec("CREATE TABLE IF NOT EXISTS patients (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255),
        email VARCHAR(255) UNIQUE,
        phone VARCHAR(50),
        password VARCHAR(255),
        blood_group VARCHAR(10),
        rhesus VARCHAR(5),
        weight DECIMAL(6,2),
        height DECIMAL(5,2),
        imc DECIMAL(6,2),
        urgency_level INT DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB;");

    $pdo->exec("CREATE TABLE IF NOT EXISTS doctors (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255),
        specialty VARCHAR(255),
        work_days VARCHAR(255),
        start_time VARCHAR(10),
        end_time VARCHAR(10),
        status VARCHAR(20) DEFAULT 'libre',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB;");

    $pdo->exec("CREATE TABLE IF NOT EXISTS consultations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        patient_id INT,
        doctor_id INT,
        date DATE,
        start_time TIME,
        duration_minutes INT DEFAULT 30,
        status VARCHAR(20) DEFAULT 'booked',
        invoice_id INT,
        FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE SET NULL,
        FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE SET NULL
    ) ENGINE=InnoDB;");

    $pdo->exec("CREATE TABLE IF NOT EXISTS invoices (
        id INT AUTO_INCREMENT PRIMARY KEY,
        invoice_uuid VARCHAR(64) UNIQUE,
        consultation_id INT,
        amount DECIMAL(10,2),
        payment_provider VARCHAR(50),
        payment_status VARCHAR(20) DEFAULT 'pending',
        qr_path VARCHAR(1024),
        barcode_path VARCHAR(1024),
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB;");

    $pdo->exec("CREATE TABLE IF NOT EXISTS payments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        invoice_id INT,
        provider_txn_id VARCHAR(255),
        status VARCHAR(50),
        raw_payload TEXT,
        confirmed_at DATETIME,
        FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE
    ) ENGINE=InnoDB;");

    $pdo->exec("CREATE TABLE IF NOT EXISTS symptom_checks (
        id INT AUTO_INCREMENT PRIMARY KEY,
        patient_id INT,
        input_text TEXT,
        ai_result TEXT,
        confidence DECIMAL(4,2),
        recommendation TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB;");

    $pdo->exec("CREATE TABLE IF NOT EXISTS uploads (
        id INT AUTO_INCREMENT PRIMARY KEY,
        patient_id INT,
        file_path VARCHAR(1024),
        mime VARCHAR(255),
        tag VARCHAR(255),
        uploaded_by VARCHAR(50),
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB;");

    $pdo->exec("CREATE TABLE IF NOT EXISTS caisse_entries (
        id INT AUTO_INCREMENT PRIMARY KEY,
        invoice_id INT,
        amount DECIMAL(10,2),
        type VARCHAR(50),
        recorded_by VARCHAR(255),
        recorded_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB;");

    // Seed a sample doctor if none
    $stmt = $pdo->query("SELECT COUNT(*) FROM doctors");
    $count = $stmt->fetchColumn();
    if ($count == 0) {
        $ins = $pdo->prepare("INSERT INTO doctors (name, specialty, work_days, start_time, end_time, status) VALUES (?,?,?,?,?,?)");
        $ins->execute(['Dr. Jean T.', 'Generaliste', '1,2,3,4,5', '08:00', '15:00', 'libre']);
    }

    echo "MySQL database '{$name}' initialized and tables created.\n";

}catch(PDOException $e){
    die('DB init failed: '.$e->getMessage());
}
