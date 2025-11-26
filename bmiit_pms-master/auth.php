<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Force no caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Check session
if (!isset($_SESSION['user_id'])) {
    // Always stop the page immediately if no session
    echo "<script>window.location.href='login.php';</script>";
    exit();
}
?>
