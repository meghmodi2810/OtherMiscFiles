<?php
/**
 * Finalize Group - This file is now handled in manage_group.php
 * Redirecting...
 */
session_start();
require_once '../db.php';
require_once '../auth.php';

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

$group_id = $_GET['group_id'] ?? null;

if ($group_id) {
    header("Location: manage_group.php?group_id=" . $group_id);
} else {
    header("Location: student_home.php");
}
exit;
?>
