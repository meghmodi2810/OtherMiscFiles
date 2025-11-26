<?php
/**
 * Email Service Control Panel
 * Standalone page - not linked to any dashboard
 * Access directly via URL: /bmiit_pms/email_service_config.php
 * 
 * No database needed - uses file-based configuration
 */

// Start session FIRST
session_start();

require_once 'email_config.php';
require_once 'email_log.php';
require_once 'email_daily_counter.php';

// Simple password protection (change this password!)
define('CONFIG_PASSWORD', 'admin123'); // ⚠️ CHANGE THIS!

$error = '';
$success = '';
$authenticated = false;

// Check if already authenticated in session
if (isset($_SESSION['email_config_auth']) && $_SESSION['email_config_auth'] === true) {
    $authenticated = true;
}

// Handle authentication
if (isset($_POST['auth_password'])) {
    if (trim($_POST['auth_password']) === CONFIG_PASSWORD) {
        $_SESSION['email_config_auth'] = true;
        $authenticated = true;
    } else {
        $error = 'Incorrect password!';
    }
}

// Handle logout
if (isset($_GET['logout'])) {
    unset($_SESSION['email_config_auth']);
    $authenticated = false;
    session_destroy();
}

// Handle toggle action (only if authenticated)
if ($authenticated && isset($_POST['toggle_service'])) {
    $currentStatus = getEmailServiceStatus();
    $newStatus = !$currentStatus['enabled'];
    $customMessage = !empty($_POST['custom_message']) ? trim($_POST['custom_message']) : null;
    
    if (setEmailServiceStatus($newStatus, 'admin', $customMessage)) {
        $success = $newStatus 
            ? '✅ Email service has been ENABLED!' 
            : '⚠️ Email service has been PAUSED!';
    } else {
        $error = '❌ Failed to update email service status.';
    }
}

// Handle manual counter adjustment (admin feature)
if ($authenticated && isset($_POST['set_counter'])) {
    $newCount = intval($_POST['manual_count'] ?? 0);
    setDailyEmailCount($newCount);
    $success = "✅ Counter manually set to $newCount emails sent today.";
}

if ($authenticated && isset($_POST['reset_counter'])) {
    resetDailyEmailCounter();
    $success = "✅ Daily counter has been reset to 0.";
}

// Get current status
$status = getEmailServiceStatus();
$daily_limit = checkGmailDailyLimit();
$counter_info = getDailyCounterInfo();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Service Configuration</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 40px;
            background: #f5f5f5;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border: 1px solid #ddd;
        }
        h1 {
            font-size: 24px;
            margin-bottom: 20px;
        }
        .alert {
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
            border-color: #c3e6cb;
        }
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border-color: #f5c6cb;
        }
        .status-box {
            background: #f9f9f9;
            padding: 15px;
            margin-bottom: 20px;
            border: 1px solid #ddd;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input[type="password"],
        input[type="text"],
        textarea {
            width: 100%;
            padding: 8px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            box-sizing: border-box;
        }
        textarea {
            height: 80px;
            font-family: Arial, sans-serif;
        }
        button {
            padding: 10px 20px;
            border: none;
            cursor: pointer;
            font-size: 14px;
        }
        .btn-primary {
            background: #007bff;
            color: white;
        }
        .btn-success {
            background: #28a745;
            color: white;
        }
        .btn-warning {
            background: #ffc107;
            color: black;
        }
        .info-box {
            background: #e7f3ff;
            padding: 15px;
            margin-top: 20px;
            border-left: 3px solid #2196F3;
        }
        .logout-link {
            margin-top: 15px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Email Service Configuration</h1>
        
        <?php if (!$authenticated): ?>
            <!-- Authentication Form -->
            <?php if ($error): ?>
                <div class="alert alert-error"><?= $error ?></div>
            <?php endif; ?>
            
            <p>Enter password to access email service configuration</p>
            
            <form method="POST">
                <label>Password:</label>
                <input type="password" 
                       name="auth_password" 
                       placeholder="Enter configuration password"
                       required
                       autofocus>
                <button type="submit" class="btn-primary">Unlock Configuration</button>
            </form>
            
            <div class="info-box">
                <strong>About This Page</strong>
                <ul>
                    <li>Controls email sending for the entire system</li>
                    <li>Affects all pages: Admin, Faculty, and Student</li>
                    <li>No database tables needed (file-based)</li>
                    <li>Not linked from any dashboard</li>
                </ul>
            </div>
            
        <?php else: ?>
            <!-- Configuration Panel -->
            <?php if ($success): ?>
                <div class="alert alert-success"><?= $success ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?= $error ?></div>
            <?php endif; ?>
            
            <!-- Daily Limit Warning -->
            <?php if ($daily_limit['reached']): ?>
                <div class="alert alert-error">
                    <strong>🚫 DAILY LIMIT REACHED!</strong><br>
                    Gmail Account: <strong><?= $daily_limit['count'] ?></strong> / <?= $daily_limit['limit'] ?> emails sent today<br>
                    ❌ <strong>Cannot send more emails</strong> - Resets at midnight GMT (00:00 UTC)<br>
                    <small style="color: #666;">This is the ACTUAL count of emails sent to Gmail's SMTP server.</small>
                </div>
            <?php elseif ($daily_limit['critical']): ?>
                <div class="alert alert-error">
                    <strong>🔴 CRITICAL: <?= $daily_limit['percentage'] ?>% of daily limit used!</strong><br>
                    Gmail Account: <strong><?= $daily_limit['count'] ?></strong> / <?= $daily_limit['limit'] ?> emails sent today<br>
                    Remaining: <strong><?= $daily_limit['remaining'] ?></strong> emails<br>
                    <small style="color: #666;">Approaching Gmail's 500/day limit. Consider pausing non-urgent emails.</small>
                </div>
            <?php elseif ($daily_limit['approaching']): ?>
                <div class="alert alert-error" style="background: #fff3cd; color: #856404; border-color: #ffc107;">
                    <strong>⚠️ WARNING: <?= $daily_limit['percentage'] ?>% of daily limit used</strong><br>
                    Gmail Account: <strong><?= $daily_limit['count'] ?></strong> / <?= $daily_limit['limit'] ?> emails sent today<br>
                    Remaining: <strong><?= $daily_limit['remaining'] ?></strong> emails
                </div>
            <?php elseif ($daily_limit['count'] > 0): ?>
                <div class="alert alert-success">
                    ✅ Gmail Account Status: <strong><?= $daily_limit['count'] ?></strong> / <?= $daily_limit['limit'] ?> emails sent today
                    (<?= $daily_limit['remaining'] ?> remaining, <?= $daily_limit['percentage'] ?>% used)
                </div>
            <?php endif; ?>
            
            <div class="status-box">
                <strong>Current Status: <?= $status['enabled'] ? 'ENABLED ✓' : 'PAUSED ⚠' ?></strong>
                <p><strong>Message:</strong> <?= $status['message'] ?></p>
                
                <?php if ($status['last_updated']): ?>
                    <p><strong>Last Updated:</strong> <?= date('F j, Y g:i A', strtotime($status['last_updated'])) ?></p>
                <?php endif; ?>
                
                <?php if ($status['updated_by']): ?>
                    <p><strong>Updated By:</strong> <?= $status['updated_by'] ?></p>
                <?php endif; ?>
            </div>
            
            <form method="POST">
                <label for="custom_message">Custom Message (Optional):</label>
                <textarea id="custom_message" 
                          name="custom_message" 
                          placeholder="Enter a custom message to show when emails are not sent (leave empty for default message)"></textarea>
                
                <?php if ($status['enabled']): ?>
                    <button type="submit" name="toggle_service" class="btn-warning">Pause Email Service</button>
                <?php else: ?>
                    <button type="submit" name="toggle_service" class="btn-success">Enable Email Service</button>
                <?php endif; ?>
            </form>
            
            <div class="info-box">
                <strong>What Happens When Service is Paused?</strong>
                <ul>
                    <li>All database operations continue normally</li>
                    <li>No actual emails will be sent</li>
                    <li>Users will see your custom message</li>
                    <li>All pages with email operations are affected</li>
                </ul>
            </div>
            
            <div style="background: #e7f3ff; padding: 15px; margin-top: 20px;">
                <strong>📊 View Email Logs</strong>
                <p style="margin: 10px 0; font-size: 14px;">Track all email attempts (sent, failed, paused) from all users system-wide.</p>
                <a href="email_logs.php" style="color: #007bff; font-weight: bold;">View Email Logs →</a>
            </div>
            
            <!-- Manual Counter Management -->
            <div style="background: #fff3cd; padding: 15px; margin-top: 20px; border: 1px solid #ffc107;">
                <strong>🔧 Gmail Counter Management</strong>
                <p style="margin: 10px 0; font-size: 14px;">
                    Current counter tracks emails sent <strong>from this system only</strong>. 
                    If you sent emails from Gmail web interface or other apps, manually sync the counter here.
                </p>
                
                <div style="margin-top: 15px;">
                    <strong>Current Count:</strong> <?= $counter_info['count'] ?> / <?= $counter_info['limit'] ?> emails<br>
                    <strong>Last Reset:</strong> <?= $counter_info['last_reset'] ?><br>
                    <strong>Resets At:</strong> <?= $counter_info['resets_at'] ?>
                </div>
                
                <form method="POST" style="margin-top: 15px;">
                    <label>Set counter to a specific value:</label>
                    <input type="number" 
                           name="manual_count" 
                           min="0" 
                           max="500" 
                           value="<?= $counter_info['count'] ?>"
                           placeholder="Enter actual Gmail count"
                           style="width: 120px; padding: 8px; border: 1px solid #ccc;">
                    <button type="submit" name="set_counter" style="padding: 8px 15px; background: #ffc107; color: #000; border: none; cursor: pointer;">
                        Update Counter
                    </button>
                    <button type="submit" name="reset_counter" style="padding: 8px 15px; background: #6c757d; color: white; border: none; cursor: pointer;" onclick="(function(e,btn){ e.preventDefault(); window.confirmAsync('Reset counter to 0?','Confirm').then(function(ok){ if(ok) btn.form.submit(); }); })(event,this); return false;">
                        Reset to 0
                    </button>
                </form>
                
                <p style="margin-top: 10px; font-size: 12px; color: #856404;">
                    <strong>💡 Tip:</strong> Check Gmail's "Sent" folder to see actual emails sent today, then update this counter to match.
                </p>
            </div>
            
            <div class="logout-link">
                <a href="?logout=1">Lock Configuration Panel</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
