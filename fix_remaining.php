<?php
// Fix attendance
$cat = file_get_contents('pages/attendance.php');
if (strpos($cat, 'WHERE a.date = ?') !== false) {
    $cat = str_replace(
        'WHERE a.date = ?',
        '" . ($orgIdPatch ? " WHERE a.organization_id = " . intval($orgIdPatch) . " AND a.date = ?" : " WHERE a.date = ?") . "',
        $cat
    );
    file_put_contents('pages/attendance.php', $cat);
    echo "Fixed attendance\n";
}

// Fix stores
$cat = file_get_contents('pages/stores.php');
if (strpos($cat, 'WHERE {$orgFilter} 1=1') !== false) {
    $cat = str_replace(
        'WHERE {$orgFilter} 1=1',
        '" . ($orgIdPatch ? " WHERE s.organization_id = " . intval($orgIdPatch) . " AND 1=1" : " WHERE 1=1"); // ',
        $cat
    );
    // Actually wait, let's fix replacing WHERE {$orgFilter} 1=1";
    $cat = preg_replace('/WHERE \{\$orgFilter\} 1=1";/', '" . ($orgIdPatch ? " WHERE s.organization_id = " . intval($orgIdPatch) . " AND 1=1" : " WHERE 1=1");', $cat);
    file_put_contents('pages/stores.php', $cat);
    echo "Fixed stores\n";
}

// Fix warehouses
$cat = file_get_contents('pages/warehouses.php');
if (strpos($cat, 'WHERE {$orgFilter} 1=1') !== false) {
    $cat = preg_replace('/WHERE \{\$orgFilter\} 1=1";/', '" . ($orgIdPatch ? " WHERE w.organization_id = " . intval($orgIdPatch) . " AND 1=1" : " WHERE 1=1");', $cat);
    file_put_contents('pages/warehouses.php', $cat);
    echo "Fixed warehouses\n";
}

// Ensure products and activity-logs are exactly correct
$cat = file_get_contents('pages/products.php');
if (strpos($cat, '"WHERE p.organization_id = "') === false) {
    $cat = preg_replace('/WHERE \{\$orgFilter\} 1=1";/', '" . ($orgIdPatch ? " WHERE p.organization_id = " . intval($orgIdPatch) . " AND 1=1" : " WHERE 1=1");', $cat);
    file_put_contents('pages/products.php', $cat);
    echo "Fixed products again\n";
}

$cat = file_get_contents('pages/activity-logs.php');
if (strpos($cat, '"WHERE al.organization_id = "') === false) {
    // There are 2 of these in activity logs (line 30 and 52)
    $cat = preg_replace('/WHERE \{\$orgFilter\} 1=1"/', '" . ($orgIdPatch ? " WHERE al.organization_id = " . intval($orgIdPatch) . " AND 1=1" : " WHERE 1=1") . "', $cat);
    file_put_contents('pages/activity-logs.php', $cat);
    echo "Fixed activity logs again\n";
}
