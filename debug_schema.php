<?php
require_once __DIR__ . '/_includes/config.php';
require_once __DIR__ . '/_includes/database.php';

try {
    $db = Database::getInstance();
    $tables = ['promotions', 'suppliers', 'employees', 'settings', 'organization_settings'];
    $output = "";
    
    foreach ($tables as $table) {
        try {
            $columns = $db->query("SHOW COLUMNS FROM $table");
            $output .= "\n\nTable: $table\n";
            $output .= sprintf("%-20s %-20s %-10s %-20s\n", "Field", "Type", "Null", "Default");
            $output .= str_repeat("-", 70) . "\n";
            foreach ($columns as $col) {
                $output .= sprintf(
                    "%-20s %-20s %-10s %-20s\n", 
                    $col['Field'], 
                    $col['Type'], 
                    $col['Null'], 
                    $col['Default'] ?? 'NULL'
                );
            }
        } catch (Exception $e) {
            $output .= "\nTable $table not found or error: " . $e->getMessage();
        }
    }
    file_put_contents(__DIR__ . '/schema_output.txt', $output);
    echo "Output written to schema_output.txt";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
