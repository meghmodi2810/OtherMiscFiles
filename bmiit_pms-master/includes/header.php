<?php
/**
 * Header Include - BMIIT PMS
 * Modern topbar with hamburger menu and user info
 * Include this at the top of all pages
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get user info from session
$user_name = $_SESSION['user']['name'] ?? $_SESSION['user']['username'] ?? 'Guest';
$user_role = $_SESSION['user']['role'] ?? 'user';
$page_title = $page_title ?? 'BMIIT PMS';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - BMIIT PMS</title>
    <link rel="icon" type="image/x-icon" href="/bmiit_pms/assets/bmiitfavicol.ico">
    
    <!-- Google Fonts - Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Feather Icons -->
    <script src="https://unpkg.com/feather-icons"></script>
    
    <!-- Modern Theme CSS -->
    <link rel="stylesheet" href="/bmiit_pms/css/modern-theme.css">
    <!-- Site-wide alerts CSS -->
    <link rel="stylesheet" href="/bmiit_pms/css/alerts.css">
    <!-- Global alerts JS (provides showAlert and safe showToast alias) -->
    <script src="/bmiit_pms/js/alerts.js" defer></script>
</head>
<body>
    <!-- Topbar -->
    <header class="topbar">
        <div class="topbar-left">
            <button class="hamburger-btn" id="hamburgerBtn" aria-label="Toggle menu">
                <i data-feather="menu"></i>
            </button>
            <div class="brand">
                <img src="/bmiit_pms/assets/Bmiit.png" alt="BMIIT Logo" onerror="this.style.display='none'">
                <span>BMIIT PMS</span>
            </div>
        </div>
        
        <div class="topbar-right">
            <div class="user-info">
                <div>
                    <div class="user-name"><?php echo $user_name; ?></div>
                    <div class="user-role"><?php echo $user_role; ?></div>
                </div>
            </div>
            <form action="/bmiit_pms/logout.php" method="post" style="display: inline;">
                <button type="submit" class="logout-btn" name="logout">
                    <i data-feather="log-out" style="width: 16px; height: 16px;"></i>
                    Logout
                </button>
            </form>
        </div>
    </header>
    
    <!-- Sidebar Overlay for Mobile -->
    <div class="sidebar-overlay"></div>
    <?php // Include the global modal so it's present on every page and styled consistently ?>
    <?php include __DIR__ . '/global_modal.php'; ?>
    <?php
    // If a server-side flash message is set, render a small script that uses the
    // global modal API to show it as a modal (keeps UI consistent across site).
    if (!empty($_SESSION['flash_message'])) {
        $flash_msg = addslashes($_SESSION['flash_message']);
        $flash_type = $_SESSION['flash_type'] ?? 'info';
        // choose an icon name based on type
        $icon = 'info';
        if ($flash_type === 'error' || $flash_type === 'danger') $icon = 'alert-triangle';
        if ($flash_type === 'success') $icon = 'check-circle';
        // unset so it doesn't show again
        unset($_SESSION['flash_message'], $_SESSION['flash_type']);
        echo "<script>document.addEventListener('DOMContentLoaded', function() {\n" .
             "  if (window && typeof window.showConfirm === 'function') {\n" .
             "    try { window.showConfirm({ title: '', message: '" . $flash_msg . "', okText: 'Close', hideCancel: true, iconName: '" . $icon . "' }).catch(()=>{}); } catch(e){}\n" .
             "  } else if (window && typeof window.showAlert === 'function') {\n" .
             "    try { window.showAlert('" . $flash_msg . "', '" . $flash_type . "', {anchor: '.container', timeout: 0}); } catch(e){}\n" .
             "  } else {\n" .
             "    // no JS helpers available — page will render any existing inline alerts as fallback\n" .
             "  }\n" .
             "});</script>";
    }
    ?>
