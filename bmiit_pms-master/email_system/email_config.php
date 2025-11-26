<?php
/**
 * Email Service Configuration
 * Simple file-based email service control
 * No database tables needed!
 */

// Configuration file path
define('EMAIL_STATUS_FILE', __DIR__ . '/email_service_status.json');

/**
 * Check if email service is enabled
 * @return bool True if enabled, False if paused
 */
function isEmailServiceEnabled() {
    if (!file_exists(EMAIL_STATUS_FILE)) {
        // Default to enabled if file doesn't exist
        return true;
    }
    
    $config = json_decode(file_get_contents(EMAIL_STATUS_FILE), true);
    return isset($config['enabled']) ? (bool)$config['enabled'] : true;
}

/**
 * Get email service status with details
 * @return array Status information
 */
function getEmailServiceStatus() {
    if (!file_exists(EMAIL_STATUS_FILE)) {
        return [
            'enabled' => true,
            'last_updated' => null,
            'updated_by' => null,
            'message' => 'Email service is running normally'
        ];
    }
    
    $config = json_decode(file_get_contents(EMAIL_STATUS_FILE), true);
    return $config;
}

/**
 * Set email service status
 * @param bool $enabled True to enable, False to pause
 * @param string $updatedBy Who made the change
 * @param string $customMessage Optional custom message
 * @return bool Success status
 */
function setEmailServiceStatus($enabled, $updatedBy = 'admin', $customMessage = null) {
    $status = [
        'enabled' => (bool)$enabled,
        'last_updated' => date('Y-m-d H:i:s'),
        'updated_by' => $updatedBy,
        'message' => $customMessage ?? ($enabled ? 'Email service is running normally' : 'Email service is currently paused. The action has been completed in the database, but no email was sent.')
    ];
    
    $result = file_put_contents(EMAIL_STATUS_FILE, json_encode($status, JSON_PRETTY_PRINT));
    return $result !== false;
}

/**
 * Initialize email service (create config file if doesn't exist)
 */
function initEmailService() {
    if (!file_exists(EMAIL_STATUS_FILE)) {
        setEmailServiceStatus(true, 'system', 'Email service initialized');
    }
}

// Auto-initialize on first load
initEmailService();
