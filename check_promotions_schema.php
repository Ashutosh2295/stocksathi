<?php
require_once '_includes/config.php';
require_once '_includes/database.php';

$db = Database::getInstance();
$conn = $db->getConnection();

echo "<h2>Checking promotions table schema</h2>";
try {
    $stmt = $conn->prepare("DESCRIBE promotions");
    $stmt->execute();
    $columns = $stmt->fetchAll();
    
    echo "<table border='1'><tr><th>Field</th><th>Type</th></tr>";
    $hasOrgId = false;
    foreach ($columns as $column) {
        echo "<tr><td>{$column['Field']}</td><td>{$column['Type']}</td></tr>";
        if ($column['Field'] === 'organization_id') $hasOrgId = true;
    }
    echo "</table>";
    
    if ($hasOrgId) {
        echo "<p style='color: green;'>✅ organization_id column EXISTS</p>";
    } else {
        echo "<p style='color: red;'>❌ organization_id column MISSING</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>
