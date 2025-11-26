<?php
/**
 * Email Logging System
 * Tracks all email attempts across the system
 */

// Log file path
define('EMAIL_LOG_FILE', __DIR__ . '/email_log.json');

/**
 * Log an email attempt
 * @param string $to Recipient email
 * @param string $toName Recipient name
 * @param string $subject Email subject
 * @param bool $success Whether email was sent successfully
 * @param string $status Status: 'sent', 'failed', 'paused'
 * @param string $errorMessage Error message if failed
 * @param string $sentBy Who triggered the email (admin/student/faculty/system)
 * @param string $page Which page sent the email
 */
function logEmail($to, $toName, $subject, $success, $status, $errorMessage = '', $sentBy = 'system', $page = '') {
    $log_entry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'to' => $to,
        'to_name' => $toName,
        'subject' => $subject,
        'success' => $success,
        'status' => $status, // 'sent', 'failed', 'paused'
        'error_message' => $errorMessage,
        'sent_by' => $sentBy,
        'page' => $page,
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ];
    
    // Read existing logs
    $logs = [];
    if (file_exists(EMAIL_LOG_FILE)) {
        $content = file_get_contents(EMAIL_LOG_FILE);
        if (!empty($content)) {
            $logs = json_decode($content, true) ?? [];
        }
    }
    
    // Add new log entry at the beginning
    array_unshift($logs, $log_entry);
    
    // Keep only last 1000 entries to prevent file from growing too large
    $logs = array_slice($logs, 0, 1000);
    
    // Save logs
    file_put_contents(EMAIL_LOG_FILE, json_encode($logs, JSON_PRETTY_PRINT));
}

/**
 * Get email logs with optional filters
 * @param int $limit Number of logs to return
 * @param string $status Filter by status: 'sent', 'failed', 'paused', or 'all'
 * @param string $sentBy Filter by sender role
 * @return array Array of log entries
 */
function getEmailLogs($limit = 50, $status = 'all', $sentBy = 'all') {
    if (!file_exists(EMAIL_LOG_FILE)) {
        return [];
    }
    
    $content = file_get_contents(EMAIL_LOG_FILE);
    if (empty($content)) {
        return [];
    }
    
    $logs = json_decode($content, true) ?? [];
    
    // Apply filters
    if ($status !== 'all') {
        $logs = array_filter($logs, function($log) use ($status) {
            return $log['status'] === $status;
        });
    }
    
    if ($sentBy !== 'all') {
        $logs = array_filter($logs, function($log) use ($sentBy) {
            return $log['sent_by'] === $sentBy;
        });
    }
    
    // Re-index array after filtering
    $logs = array_values($logs);
    
    // Apply limit
    return array_slice($logs, 0, $limit);
}

/**
 * Get email statistics
 * @return array Statistics about email logs
 */
function getEmailStats() {
    if (!file_exists(EMAIL_LOG_FILE)) {
        return [
            'total' => 0,
            'sent' => 0,
            'failed' => 0,
            'paused' => 0,
            'today' => 0,
            'this_week' => 0
        ];
    }
    
    $content = file_get_contents(EMAIL_LOG_FILE);
    if (empty($content)) {
        return [
            'total' => 0,
            'sent' => 0,
            'failed' => 0,
            'paused' => 0,
            'today' => 0,
            'this_week' => 0
        ];
    }
    
    $logs = json_decode($content, true) ?? [];
    
    $stats = [
        'total' => count($logs),
        'sent' => 0,
        'failed' => 0,
        'paused' => 0,
        'today' => 0,
        'this_week' => 0
    ];
    
    $today = date('Y-m-d');
    $week_ago = date('Y-m-d', strtotime('-7 days'));
    
    foreach ($logs as $log) {
        // Count by status
        if ($log['status'] === 'sent') $stats['sent']++;
        elseif ($log['status'] === 'failed') $stats['failed']++;
        elseif ($log['status'] === 'paused') $stats['paused']++;
        
        // Count by date
        $log_date = date('Y-m-d', strtotime($log['timestamp']));
        if ($log_date === $today) {
            $stats['today']++;
        }
        if ($log_date >= $week_ago) {
            $stats['this_week']++;
        }
    }
    
    return $stats;
}

/**
 * Clear old email logs
 * @param int $days Keep logs from last N days
 */
function clearOldEmailLogs($days = 30) {
    if (!file_exists(EMAIL_LOG_FILE)) {
        return true;
    }
    
    $content = file_get_contents(EMAIL_LOG_FILE);
    if (empty($content)) {
        return true;
    }
    
    $logs = json_decode($content, true) ?? [];
    $cutoff_date = date('Y-m-d H:i:s', strtotime("-$days days"));
    
    $filtered_logs = array_filter($logs, function($log) use ($cutoff_date) {
        return $log['timestamp'] >= $cutoff_date;
    });
    
    file_put_contents(EMAIL_LOG_FILE, json_encode(array_values($filtered_logs), JSON_PRETTY_PRINT));
    return true;
}

/**
 * Get count of SUCCESSFULLY SENT emails today (for Gmail daily limit)
 * Gmail allows ~500 emails per day
 * @return int Number of emails sent today
 */
function getTodaysSentEmailCount() {
    if (!file_exists(EMAIL_LOG_FILE)) {
        return 0;
    }
    
    $content = file_get_contents(EMAIL_LOG_FILE);
    if (empty($content)) {
        return 0;
    }
    
    $logs = json_decode($content, true) ?? [];
    $today = date('Y-m-d');
    $count = 0;
    
    foreach ($logs as $log) {
        $log_date = date('Y-m-d', strtotime($log['timestamp']));
        // Only count SENT emails (not failed or paused)
        if ($log_date === $today && $log['status'] === 'sent') {
            $count++;
        }
    }
    
    return $count;
}

/**
 * Check if approaching Gmail daily limit
 * @return array ['approaching' => bool, 'count' => int, 'limit' => int, 'remaining' => int]
 */
function checkDailyEmailLimit() {
    $limit = 500; // Gmail's approximate daily limit
    $count = getTodaysSentEmailCount();
    $remaining = $limit - $count;
    $approaching = ($count >= ($limit * 0.8)); // Warning at 80% (400 emails)
    
    return [
        'approaching' => $approaching,
        'count' => $count,
        'limit' => $limit,
        'remaining' => $remaining
    ];
}
