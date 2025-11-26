<?php
/**
 * Daily Email Counter
 * Persistent tracking of Gmail daily send count
 * Separate from logs - tracks ACTUAL sends to Gmail
 * Auto-resets at midnight
 */

// Counter file path
define('EMAIL_COUNTER_FILE', __DIR__ . '/email_daily_count.json');
define('GMAIL_DAILY_LIMIT', 500);

/**
 * Get today's email count from persistent counter
 * @return array ['date' => 'Y-m-d', 'count' => int, 'limit' => int, 'remaining' => int]
 */
function getDailyEmailCount() {
    $today = date('Y-m-d');
    
    // Initialize default data
    $data = [
        'date' => $today,
        'count' => 0,
        'limit' => GMAIL_DAILY_LIMIT,
        'remaining' => GMAIL_DAILY_LIMIT,
        'last_reset' => date('Y-m-d H:i:s')
    ];
    
    // Check if counter file exists
    if (file_exists(EMAIL_COUNTER_FILE)) {
        $content = file_get_contents(EMAIL_COUNTER_FILE);
        if (!empty($content)) {
            $stored = json_decode($content, true);
            
            // Check if stored date is today
            if (isset($stored['date']) && $stored['date'] === $today) {
                // Same day - use stored count
                $data['count'] = intval($stored['count'] ?? 0);
                $data['remaining'] = GMAIL_DAILY_LIMIT - $data['count'];
                $data['last_reset'] = $stored['last_reset'] ?? $data['last_reset'];
                return $data;
            }
            // Different day - counter already reset to 0
        }
    }
    
    // New day or first time - save initial state
    saveDailyEmailCount($data);
    return $data;
}

/**
 * Increment the daily email counter
 * Call this ONLY when email is successfully sent via Gmail SMTP
 * @return array Updated count data
 */
function incrementDailyEmailCount() {
    $data = getDailyEmailCount();
    
    // Increment count
    $data['count']++;
    $data['remaining'] = GMAIL_DAILY_LIMIT - $data['count'];
    
    // Save updated count
    saveDailyEmailCount($data);
    
    return $data;
}

/**
 * Save daily email count to file
 * @param array $data Counter data to save
 */
function saveDailyEmailCount($data) {
    file_put_contents(EMAIL_COUNTER_FILE, json_encode($data, JSON_PRETTY_PRINT));
}

/**
 * Check if daily limit is reached or approaching
 * @return array Status information
 */
function checkGmailDailyLimit() {
    $data = getDailyEmailCount();
    
    $percentage = ($data['count'] / GMAIL_DAILY_LIMIT) * 100;
    $approaching = ($percentage >= 80); // Warning at 80%
    $critical = ($percentage >= 95);    // Critical at 95%
    $reached = ($data['count'] >= GMAIL_DAILY_LIMIT);
    
    return [
        'count' => $data['count'],
        'limit' => GMAIL_DAILY_LIMIT,
        'remaining' => $data['remaining'],
        'percentage' => round($percentage, 1),
        'approaching' => $approaching,
        'critical' => $critical,
        'reached' => $reached,
        'date' => $data['date'],
        'can_send' => !$reached
    ];
}

/**
 * Manually reset the counter (admin only)
 * Use this if you need to reset mid-day
 */
function resetDailyEmailCounter() {
    $data = [
        'date' => date('Y-m-d'),
        'count' => 0,
        'limit' => GMAIL_DAILY_LIMIT,
        'remaining' => GMAIL_DAILY_LIMIT,
        'last_reset' => date('Y-m-d H:i:s')
    ];
    saveDailyEmailCount($data);
    return $data;
}

/**
 * Manually set a specific count (if you know Gmail's actual count)
 * Use this to sync with Gmail's actual usage
 * @param int $count The actual number of emails sent via Gmail today
 */
function setDailyEmailCount($count) {
    $count = max(0, intval($count)); // Ensure non-negative integer
    
    $data = [
        'date' => date('Y-m-d'),
        'count' => $count,
        'limit' => GMAIL_DAILY_LIMIT,
        'remaining' => GMAIL_DAILY_LIMIT - $count,
        'last_reset' => date('Y-m-d H:i:s')
    ];
    
    saveDailyEmailCount($data);
    return $data;
}

/**
 * Get detailed counter information for display
 * @return array Formatted data for UI
 */
function getDailyCounterInfo() {
    $data = getDailyEmailCount();
    $limit_check = checkGmailDailyLimit();
    
    return [
        'date' => $data['date'],
        'count' => $data['count'],
        'limit' => GMAIL_DAILY_LIMIT,
        'remaining' => $data['remaining'],
        'percentage' => $limit_check['percentage'],
        'status' => $limit_check['reached'] ? 'reached' : 
                   ($limit_check['critical'] ? 'critical' : 
                   ($limit_check['approaching'] ? 'warning' : 'normal')),
        'can_send' => $limit_check['can_send'],
        'last_reset' => $data['last_reset'],
        'resets_at' => 'Midnight GMT (00:00 UTC)'
    ];
}
