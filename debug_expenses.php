<?php
require_once __DIR__ . '/_includes/config.php';
$db = Database::getInstance();

try {
    echo "Checking expenses table...\n";
    $columns = $db->query("SHOW COLUMNS FROM expenses");
    echo "Columns:\n";
    foreach ($columns as $col) {
        echo "- " . $col['Field'] . " (" . $col['Type'] . ")\n";
    }
    
    echo "\nChecking data count:\n";
    $count = $db->queryOne("SELECT COUNT(*) as c FROM expenses")['c'];
    echo "Total rows: $count\n";
    
    if ($count > 0) {
        $sample = $db->queryOne("SELECT * FROM expenses LIMIT 1");
        echo "\nSample row:\n";
        print_r($sample);
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
