<?php
/**
 * Database restore script - run once then delete
 * Usage: php restore_db.php OR visit http://localhost/stocksathi/restore_db.php
 */

$host = '127.0.0.1';
$user = 'root';
$pass = '';
$dbname = 'stocksathi';

try {
    $pdo = new PDO("mysql:host=$host", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $pdo->exec("DROP DATABASE IF EXISTS `$dbname`");
    echo "Dropped existing database.\n";
    
    $pdo->exec("CREATE DATABASE `$dbname` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "Created fresh database.\n";
    
    $pdo->exec("USE `$dbname`");
    
    $sqlFile = __DIR__ . '/stocksathi_restore.sql';
    if (!file_exists($sqlFile)) {
        die("ERROR: stocksathi_restore.sql not found!\n");
    }
    
    $sql = file_get_contents($sqlFile);
    $pdo->exec($sql);
    
    echo "Database restored successfully!\n";
    
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "Tables created: " . count($tables) . "\n";
    foreach ($tables as $t) {
        $count = $pdo->query("SELECT COUNT(*) FROM `$t`")->fetchColumn();
        echo "  - $t: $count rows\n";
    }
    
    echo "\nDone! You can now delete restore_db.php and stocksathi_restore.sql\n";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
