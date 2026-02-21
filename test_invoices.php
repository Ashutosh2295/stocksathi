<?php
require_once '_includes/config.php';
require_once '_includes/database.php';
require_once '_includes/Session.php';

Session::start();
Session::setUser(78, 'issue', 'super_admin', 78);

ob_start();
include 'pages/invoices.php';
$content = ob_get_clean();

echo "Invoices Page loaded successfully.\n";
