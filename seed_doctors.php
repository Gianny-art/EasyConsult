<?php
require_once __DIR__ . '/lib/db.php';
$pdo = get_db();

// Add sample doctors if table is empty
$stmt = $pdo->query("SELECT COUNT(*) FROM doctors");
$count = $stmt->fetchColumn();

if ($count == 0) {
    $doctors = [
        ['Dr. Jean Trou', 'Généraliste', '1,2,3,4,5', '08:00', '17:00', 'libre'],
        ['Dr. Marie Curie', 'Cardiologie', '1,2,3,4,5', '09:00', '18:00', 'libre'],
        ['Dr. Pierre Dupont', 'Neurologie', '2,3,4,5', '10:00', '16:00', 'libre'],
        ['Dr. Sophie Bernard', 'Pédiatrie', '1,2,3,4', '08:00', '14:00', 'libre'],
        ['Dr. André Lefebvre', 'Orthopédie', '1,3,4,5', '09:00', '17:00', 'occupé'],
    ];

    $stmt = $pdo->prepare("INSERT INTO doctors (name, specialty, work_days, start_time, end_time, status) VALUES (?,?,?,?,?,?)");
    $count = 0;
    foreach ($doctors as $doc) {
        $stmt->execute($doc);
        $count++;
    }
    
    echo "✅ {$count} médecins ajoutés à la base de données!\n\n";
    
    // Afficher la liste
    $result = $pdo->query('SELECT id, name, specialty, status FROM doctors')->fetchAll(PDO::FETCH_ASSOC);
    echo "Médecins actuels:\n";
    foreach ($result as $doc) {
        echo "  [{$doc['id']}] Dr. {$doc['name']} - {$doc['specialty']} ({$doc['status']})\n";
    }
} else {
    echo "✅ {$count} médecins trouvés dans la base de données.\n\n";
    $result = $pdo->query('SELECT id, name, specialty, status FROM doctors')->fetchAll(PDO::FETCH_ASSOC);
    foreach ($result as $doc) {
        echo "  [{$doc['id']}] Dr. {$doc['name']} - {$doc['specialty']} ({$doc['status']})\n";
    }
}
