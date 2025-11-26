<?php
/**
 * Bulk Helpers - Validation Functions
 * Common validation functions used by manual and bulk insertion forms
 */

/**
 * Validate phone number
 * - Must be exactly 10 digits
 * - Should not be a dummy number like 9876543210, 1234567890, etc.
 * 
 * @param string $phone The phone number to validate
 * @return bool True if valid, false otherwise
 */
function is_valid_phone($phone) {
    // Check if phone is exactly 10 digits
    if (!preg_match('/^[0-9]{10}$/', $phone)) {
        return false;
    }
    
    // Check for common dummy numbers
    $dummy_phones = [
        '1234567890',
        '0123456789',
        '9876543210',
        '0987654321',
        '1111111111',
        '2222222222',
        '3333333333',
        '4444444444',
        '5555555555',
        '6666666666',
        '7777777777',
        '8888888888',
        '9999999999',
        '0000000000'
    ];
    
    if (in_array($phone, $dummy_phones)) {
        return false;
    }
    
    // Check if all digits are the same
    if (preg_match('/^(\d)\1{9}$/', $phone)) {
        return false;
    }
    
    return true;
}

/**
 * Validate email address
 * - Must be a valid email format
 * - Should not be a dummy email like abc@abc.com, test@test.com, etc.
 * 
 * @param string $email The email address to validate
 * @return bool True if valid, false otherwise
 */
function is_valid_email($email) {
    // Check basic email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return false;
    }
    
    // Get domain from email
    $email_parts = explode('@', $email);
    if (count($email_parts) !== 2) {
        return false;
    }
    
    $local_part = strtolower($email_parts[0]);
    $domain = strtolower($email_parts[1]);
    
    // Check for dummy patterns in local part
    $dummy_local_patterns = [
        'test',
        'dummy',
        'example',
        'sample',
        'temp',
        'fake'
    ];
    
    foreach ($dummy_local_patterns as $pattern) {
        if (strpos($local_part, $pattern) !== false) {
            return false;
        }
    }
    
    // Check for dummy domains
    $dummy_domains = [
        'abc.com',
        'test.com',
        'dummy.com',
        'example.com',
        'sample.com',
        'temp.com',
        'fake.com',
        'xyz.com',
        'xxx.com'
    ];
    
    if (in_array($domain, $dummy_domains)) {
        return false;
    }
    
    // Check if local and domain are the same (e.g., abc@abc.com)
    if ($local_part === str_replace('.com', '', $domain) || 
        $local_part === str_replace('.in', '', $domain)) {
        return false;
    }
    
    return true;
}

/**
 * Validate name
 * - Should contain only letters and spaces
 * - Should be at least 2 characters long
 * - Should not exceed 50 characters
 * - Should not contain dummy patterns
 * 
 * @param string $name The name to validate
 * @return bool True if valid, false otherwise
 */
function is_valid_name($name) {
    // Check if name contains only letters, spaces, and dots
    if (!preg_match('/^[A-Za-z \.]{2,50}$/', $name)) {
        return false;
    }
    
    // Check for dummy patterns
    $dummy_patterns = [
        'test',
        'dummy',
        'sample',
        'example',
        'temp',
        'fake',
        'asdf',
        'qwerty',
        'abc',
        'xyz'
    ];
    
    $name_lower = strtolower(trim($name));
    foreach ($dummy_patterns as $pattern) {
        if (strpos($name_lower, $pattern) !== false) {
            return false;
        }
    }
    
    // Check if name has at least one space (first and last name)
    // Allow single word names but warn if too simple
    $words = explode(' ', trim($name));
    if (count($words) === 1 && strlen($words[0]) < 3) {
        return false;
    }
    
    return true;
}

/**
 * Get validation error message for a specific field
 * 
 * @param string $field The field type (phone, email, name)
 * @param string $value The value that failed validation
 * @return string The error message
 */
function get_validation_error($field, $value) {
    switch ($field) {
        case 'phone':
            if (!preg_match('/^[0-9]{10}$/', $value)) {
                return "Phone number must be exactly 10 digits";
            }
            return "Phone number appears to be invalid or a dummy number (e.g., 9876543210, 1234567890)";
            
        case 'email':
            if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                return "Invalid email format";
            }
            return "Email appears to be invalid or a dummy email (e.g., abc@abc.com, test@test.com)";
            
        case 'name':
            if (!preg_match('/^[A-Za-z \.]{2,50}$/', $value)) {
                return "Name should only contain letters, spaces, and dots (2-50 characters)";
            }
            return "Name appears to contain dummy or invalid patterns";
            
        default:
            return "Invalid value";
    }
}

/**
 * Sanitize input string
 * 
 * @param string $input The input to sanitize
 * @return string The sanitized input
 */
function sanitize_input($input) {
    return trim($input);
}

/**
 * Validate enrollment number
 * - Must be exactly 15 digits
 * 
 * @param string $enrollment The enrollment number to validate
 * @return bool True if valid, false otherwise
 */
function is_valid_enrollment($enrollment) {
    return preg_match('/^[0-9]{15}$/', $enrollment);
}
