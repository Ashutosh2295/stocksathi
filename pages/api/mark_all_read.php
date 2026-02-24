<?php
require_once __DIR__ . '/../../_includes/session_guard.php';
require_once __DIR__ . '/../../_includes/config.php';
require_once __DIR__ . '/../../_includes/database.php';
require_once __DIR__ . '/../../_includes/Session.php';

$userId = Session::getUserId();
if ($userId) {
    try {
        $db = Database::getInstance();
$orgIdPatch = isset($_SESSION['organization_id']) ? $_SESSION['organization_id'] : (class_exists('Session') ? Session::getOrganizationId() : null);
$orgFilter = $orgIdPatch ? " organization_id = " . intval($orgIdPatch) . " AND " : "";
$orgWhere = $orgIdPatch ? " WHERE organization_id = " . intval($orgIdPatch) . " " : "";
        $db->execute("UPDATE notifications SET is_read = TRUE WHERE " . ($orgIdPatch ? "organization_id = " . intval($orgIdPatch) . " AND " : "") . "(user_id = ? OR user_id IS NULL)", [$userId]);
    } catch (Exception $e) {}
}

// Redirect back
$referer = $_SERVER['HTTP_REFERER'] ?? '../index.php';
header("Location: $referer");
exit;
