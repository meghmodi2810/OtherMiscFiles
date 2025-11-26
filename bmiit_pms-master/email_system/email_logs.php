<?php
/**
 * Email Log Viewer
 * View all email attempts system-wide
 * Access: Direct URL (not linked to dashboards)
 */

session_start();
require_once 'email_log.php';
require_once 'email_config.php';
require_once 'email_daily_counter.php';

// Simple password protection (same as email config)
define('LOG_PASSWORD', 'admin123'); // ⚠️ CHANGE THIS!

$error = '';
$authenticated = false;

// Check if already authenticated
if (isset($_SESSION['email_log_auth']) && $_SESSION['email_log_auth'] === true) {
    $authenticated = true;
}

// Handle authentication
if (isset($_POST['auth_password'])) {
    if ($_POST['auth_password'] === LOG_PASSWORD) {
        $_SESSION['email_log_auth'] = true;
        $authenticated = true;
    } else {
        $error = 'Incorrect password!';
    }
}

// Handle logout
if (isset($_GET['logout'])) {
    unset($_SESSION['email_log_auth']);
    $authenticated = false;
}

// Handle clear old logs
if ($authenticated && isset($_POST['clear_old_logs'])) {
    $days = intval($_POST['days'] ?? 30);
    clearOldEmailLogs($days);
    $success = "Cleared logs older than $days days";
}

// Get filter parameters
$status_filter = $_GET['status'] ?? 'all';
$role_filter = $_GET['role'] ?? 'all';
$limit = intval($_GET['limit'] ?? 50);

// Get logs and stats
if ($authenticated) {
    $logs = getEmailLogs($limit, $status_filter, $role_filter);
    $stats = getEmailStats();
    $email_status = getEmailServiceStatus();
    $daily_limit = checkGmailDailyLimit();
    $counter_info = getDailyCounterInfo();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Logs</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: Arial, sans-serif;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            max-width: 1400px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border: 1px solid #ddd;
        }
        h1 {
            font-size: 24px;
            margin-bottom: 20px;
            color: #333;
        }
        h2 {
            font-size: 18px;
            margin: 20px 0 10px 0;
            color: #555;
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
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }
        .stat-box {
            background: #f8f9fa;
            padding: 15px;
            border: 1px solid #ddd;
            text-align: center;
        }
        .stat-number {
            font-size: 32px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .stat-label {
            font-size: 14px;
            color: #666;
        }
        .status-sent { color: #28a745; }
        .status-failed { color: #dc3545; }
        .status-paused { color: #ffc107; }
        .filters {
            background: #f8f9fa;
            padding: 15px;
            margin-bottom: 20px;
            border: 1px solid #ddd;
        }
        .filters form {
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
        }
        .filters select,
        .filters input {
            padding: 8px;
            border: 1px solid #ccc;
        }
        .filters button {
            padding: 8px 15px;
            background: #007bff;
            color: white;
            border: none;
            cursor: pointer;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 14px;
        }
        th, td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background: #f8f9fa;
            font-weight: bold;
            position: sticky;
            top: 0;
        }
        tr:hover {
            background: #f8f9fa;
        }
        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: bold;
        }
        .badge-sent {
            background: #d4edda;
            color: #155724;
        }
        .badge-failed {
            background: #f8d7da;
            color: #721c24;
        }
        .badge-paused {
            background: #fff3cd;
            color: #856404;
        }
        .badge-admin {
            background: #d1ecf1;
            color: #0c5460;
        }
        .badge-student {
            background: #e2e3e5;
            color: #383d41;
        }
        .badge-faculty {
            background: #d6d8db;
            color: #1b1e21;
        }
        input[type="password"] {
            width: 100%;
            padding: 8px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
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
        .logout-link {
            margin-top: 20px;
            text-align: center;
        }
        .service-status {
            background: #f8f9fa;
            padding: 15px;
            margin-bottom: 20px;
            border-left: 4px solid #007bff;
        }
        .service-status.enabled {
            border-left-color: #28a745;
        }
        .service-status.paused {
            border-left-color: #ffc107;
        }
        .truncate {
            max-width: 300px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📧 Email Logs</h1>
        
        <?php if (!$authenticated): ?>
            <!-- Authentication Form -->
            <?php if ($error): ?>
                <div class="alert alert-error"><?= $error ?></div>
            <?php endif; ?>
            
            <p>Enter password to view email logs</p>
            
            <form method="POST">
                <label>Password:</label>
                <input type="password" 
                       name="auth_password" 
                       placeholder="Enter password"
                       required
                       autofocus>
                <button type="submit" class="btn-primary">View Logs</button>
            </form>
            
        <?php else: ?>
            <!-- Email Service Status -->
            <div class="service-status <?= $email_status['enabled'] ? 'enabled' : 'paused' ?>">
                <strong>Email Service Status: <?= $email_status['enabled'] ? '✅ ENABLED' : '⚠️ PAUSED' ?></strong>
                <?php if (!$email_status['enabled']): ?>
                    <p style="margin-top: 5px; font-size: 14px;"><?= $email_status['message'] ?></p>
                <?php endif; ?>
                <p style="margin-top: 5px; font-size: 13px; color: #666;">
                    <a href="email_service_config.php" style="color: #007bff;">Configure Email Service →</a>
                </p>
            </div>
            
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
            
            <!-- Statistics -->
            <h2>Statistics</h2>
            <div class="stats-grid">
                <div class="stat-box">
                    <div class="stat-number"><?= $stats['total'] ?></div>
                    <div class="stat-label">Total Emails</div>
                </div>
                <div class="stat-box">
                    <div class="stat-number status-sent"><?= $stats['sent'] ?></div>
                    <div class="stat-label">Sent</div>
                </div>
                <div class="stat-box">
                    <div class="stat-number status-failed"><?= $stats['failed'] ?></div>
                    <div class="stat-label">Failed</div>
                </div>
                <div class="stat-box">
                    <div class="stat-number status-paused"><?= $stats['paused'] ?></div>
                    <div class="stat-label">Paused</div>
                </div>
                <div class="stat-box">
                    <div class="stat-number"><?= $stats['today'] ?></div>
                    <div class="stat-label">Today</div>
                </div>
                <div class="stat-box">
                    <div class="stat-number"><?= $stats['this_week'] ?></div>
                    <div class="stat-label">This Week</div>
                </div>
            </div>
            
            <!-- Filters -->
            <div class="filters">
                <form method="GET">
                    <label>Status:</label>
                    <select name="status">
                        <option value="all" <?= $status_filter === 'all' ? 'selected' : '' ?>>All</option>
                        <option value="sent" <?= $status_filter === 'sent' ? 'selected' : '' ?>>Sent</option>
                        <option value="failed" <?= $status_filter === 'failed' ? 'selected' : '' ?>>Failed</option>
                        <option value="paused" <?= $status_filter === 'paused' ? 'selected' : '' ?>>Paused</option>
                    </select>
                    
                    <label>Sent By:</label>
                    <select name="role">
                        <option value="all" <?= $role_filter === 'all' ? 'selected' : '' ?>>All</option>
                        <option value="admin" <?= $role_filter === 'admin' ? 'selected' : '' ?>>Admin</option>
                        <option value="student" <?= $role_filter === 'student' ? 'selected' : '' ?>>Student</option>
                        <option value="faculty" <?= $role_filter === 'faculty' ? 'selected' : '' ?>>Faculty</option>
                        <option value="system" <?= $role_filter === 'system' ? 'selected' : '' ?>>System</option>
                    </select>
                    
                    <label>Limit:</label>
                    <input type="number" name="limit" value="<?= $limit ?>" min="10" max="500" style="width: 80px;">
                    
                    <button type="submit">Apply Filters</button>
                    <a href="email_logs.php" style="margin-left: 10px; color: #666;">Clear Filters</a>
                </form>
            </div>
            
            <!-- Logs Table -->
            <h2>Email Logs (<?= count($logs) ?> entries)</h2>
            
            <?php if (empty($logs)): ?>
                <p style="padding: 20px; text-align: center; color: #666;">No email logs found.</p>
            <?php else: ?>
                <div style="overflow-x: auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>Timestamp</th>
                                <th>Status</th>
                                <th>To</th>
                                <th>Subject</th>
                                <th>Sent By</th>
                                <th>Page</th>
                                <th>Error</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($logs as $log): ?>
                                <tr>
                                    <td style="white-space: nowrap;">
                                        <?= date('M d, Y H:i', strtotime($log['timestamp'])) ?>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?= $log['status'] ?>">
                                            <?= strtoupper($log['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div><strong><?= $log['to_name'] ?></strong></div>
                                        <div style="font-size: 12px; color: #666;"><?= $log['to'] ?></div>
                                    </td>
                                    <td class="truncate" title="<?= $log['subject'] ?>">
                                        <?= $log['subject'] ?>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?= $log['sent_by'] ?>">
                                            <?= strtoupper($log['sent_by']) ?>
                                        </span>
                                    </td>
                                    <td style="font-size: 12px; color: #666;">
                                        <?= basename($log['page']) ?>
                                    </td>
                                    <td class="truncate" title="<?= $log['error_message'] ?>">
                                        <?php if (!empty($log['error_message'])): ?>
                                            <span style="color: #dc3545; font-size: 12px;">
                                                <?= $log['error_message'] ?>
                                            </span>
                                        <?php else: ?>
                                            <span style="color: #999;">—</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
            
            <!-- Maintenance -->
            <div style="background: #f8f9fa; padding: 15px; margin-top: 30px; border: 1px solid #ddd;">
                <h3 style="margin-bottom: 10px;">Maintenance</h3>
                <form method="POST" onsubmit="(function(e){ e.preventDefault(); window.confirmAsync('Are you sure you want to clear old logs?','Confirm', true).then(function(ok){ if(ok) e.target.submit(); }); })(event); return false;">
                    <label>Clear logs older than:</label>
                    <input type="number" name="days" value="30" min="1" max="365" style="width: 80px;">
                    <span>days</span>
                    <button type="submit" name="clear_old_logs" style="margin-left: 10px;">Clear Old Logs</button>
                </form>
            </div>
            
            <div class="logout-link">
                <a href="?logout=1">Lock Log Viewer</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
