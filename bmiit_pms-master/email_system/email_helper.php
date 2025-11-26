<?php
require '../vendor/autoload.php';
require_once __DIR__ . '/email_config.php';
require_once __DIR__ . '/email_log.php';
require_once __DIR__ . '/email_daily_counter.php';
use PHPMailer\PHPMailer\PHPMailer;

function sendEmail($to, $toName, $subject, $htmlBody, $plainBody = null) {
    // Determine who is sending (based on session)
    $sentBy = 'system';
    $page = $_SERVER['PHP_SELF'] ?? 'unknown';
    
    if (isset($_SESSION['user_role'])) {
        $sentBy = $_SESSION['user_role']; // 'admin', 'student', 'faculty'
    }
    
    // Check if email service is enabled
    if (!isEmailServiceEnabled()) {
        $status = getEmailServiceStatus();
        
        // Log the paused attempt
        logEmail($to, $toName, $subject, false, 'paused', $status['message'], $sentBy, $page);
        
        return [
            'success' => false, 
            'paused' => true,
            'message' => $status['message']
        ];
    }
    
    // Check Gmail daily limit using persistent counter
    $limitCheck = checkGmailDailyLimit();
    if ($limitCheck['reached']) {
        $errorMsg = "Daily email limit reached ({$limitCheck['limit']} emails sent to Gmail today). Resets at midnight GMT.";
        logEmail($to, $toName, $subject, false, 'failed', $errorMsg, $sentBy, $page);
        
        return [
            'success' => false, 
            'paused' => false,
            'limit_reached' => true,
            'message' => $errorMsg,
            'daily_count' => $limitCheck['count'],
            'remaining' => 0
        ];
    }
    
    $mail = new PHPMailer(false); // Disable exceptions

    // SMTP configuration
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'pmsbmiit@gmail.com';   // Gmail
    $mail->Password   = 'ssjazkeivfgmiqvn';    // App Password
    $mail->SMTPSecure = 'ssl';
    $mail->Port       = 465;

    // Sender & recipient
    $mail->setFrom('pmsbmiit@gmail.com', 'BMIIT Project Management System');
    $mail->addAddress($to, $toName);

    // Email content
    $mail->isHTML(true);
    $mail->Subject = $subject;
    $mail->Body    = $htmlBody;
    $mail->AltBody = $plainBody ?? strip_tags($htmlBody);

    if ($mail->send()) {
        // Increment persistent daily counter (tracks actual Gmail sends)
        incrementDailyEmailCount();
        
        // Log successful send
        logEmail($to, $toName, $subject, true, 'sent', '', $sentBy, $page);
        
        return ['success' => true, 'message' => 'Email sent successfully'];
    } else {
        // Log failed send
        logEmail($to, $toName, $subject, false, 'failed', $mail->ErrorInfo, $sentBy, $page);
        
        return ['success' => false, 'message' => 'Mailer Error: ' . $mail->ErrorInfo];
    }
}

/**
 * Queue email for sending. In this simplified setup we send immediately using sendEmail().
 * This wrapper preserves existing calls to queueEmail() used across the project.
 */
function queueEmail($to, $toName, $subject, $htmlBody, $plainBody = null) {
    return sendEmail($to, $toName, $subject, $htmlBody, $plainBody);
}

/**
 * Build a simple, consistent email body used across the system.
 * Returns an array: [htmlBody, plainBody]
 */
function buildSimpleEmailBody($recipientName, $messageLine) {
    $recipientName = trim((string)$recipientName);

    // Normalize newlines to \n
    $normalized = str_replace(["\r\n", "\r"], "\n", (string)$messageLine);

    // Split into paragraphs on double-newline (one blank line between paragraphs)
    $rawParagraphs = preg_split('/\n{2,}/', trim($normalized));

    // Remove any empty paragraphs and trim each
    $paragraphs = [];
    foreach ($rawParagraphs as $p) {
        $trimmed = trim($p);
        if ($trimmed !== '') {
            $paragraphs[] = $trimmed;
        }
    }

    $htmlParts = [];
    foreach ($paragraphs as $p) {
        // Convert single newlines within a paragraph to <br/> and escape
        $htmlParts[] = '<p>' . nl2br(htmlspecialchars($p)) . '</p>';
    }

    $greetingHtml = '';
    $greetingPlain = '';
    if ($recipientName !== '') {
        $greetingHtml = '<p>Hello ' . htmlspecialchars($recipientName) . ',</p>';
        $greetingPlain = "Hello {$recipientName},\n\n";
    }

    $html = $greetingHtml . implode("\n", $htmlParts) . '<p>Regards,<br/>BMIIT PMS</p>';

    // Plain text preserves the normalized newlines and paragraphs
    $plain = $greetingPlain . implode("\n\n", $paragraphs) . "\n\nRegards,\nBMIIT PMS";

    return [$html, $plain];
}
