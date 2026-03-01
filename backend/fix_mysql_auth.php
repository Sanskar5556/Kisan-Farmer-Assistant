<?php
$host = '127.0.0.1';
$port = 3306;
$user = 'root';
$pass = 'sanskar';

echo "Connecting to MySQL...\n";
try {
    $pdo = new PDO(
        "mysql:host={$host};port={$port}",
        $user,
        $pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 5]
    );
    echo "Connected successfully!\n";

    $pdo->exec("CREATE DATABASE IF NOT EXISTS kisan_smart_assistant CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "Database 'kisan_smart_assistant' created!\n";

    $dbs = $pdo->query("SHOW DATABASES")->fetchAll(PDO::FETCH_COLUMN);
    echo "Databases: " . implode(', ', $dbs) . "\n";

    echo "\nAll done! DB is ready.\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
