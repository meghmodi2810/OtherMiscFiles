<?php
/**
 * Background Email Sender - Runs independently
 * This script processes queued emails without blocking the main page
 */

// Prevent timeout
@ini_set('max_execution_time', 0);
@ini_set('ignore_user_abort', 1);

// Allow script to run even if user closes browser
ignore_user_abort(true);

// Get session ID from parameter (passed when spawning this process)
$session_id = $_GET['sid'] ?? '';
if (!$session_id) {
	error_log("Background email sender: No session ID provided");
	exit('No session ID');
}

// Resume the session using provided session ID
session_id($session_id);
session_start();

error_log("Background email sender started with session ID: $session_id");

require_once __DIR__ . '/../email_system/email_helper.php';

// Log file for tracking
$log_file = __DIR__ . '/email_queue_log.txt';

if (!isset($_SESSION['email_queue']) || empty($_SESSION['email_queue'])) {
	file_put_contents($log_file, date('Y-m-d H:i:s') . " - No emails in queue\n", FILE_APPEND);
	error_log("Background email sender: No emails in queue");
	exit('No emails to send');
}

$queue = $_SESSION['email_queue'];
$total = count($queue);
$sent = 0;
$failed = 0;

file_put_contents($log_file, date('Y-m-d H:i:s') . " - Starting to send $total emails\n", FILE_APPEND);

foreach ($queue as $index => $email_data) {
	try {
			// Build a concise welcome email without any web links
			$messageLine = "You have been registered in the BMIIT Project Management Portal.\n\nUsername: {$email_data['username']}\nPasskey: {$email_data['passkey']}";
		list($htmlBody, $plainBody) = buildSimpleEmailBody($email_data['name'], $messageLine);

		$emailResult = sendEmail($email_data['email'], $email_data['name'], 'Welcome to BMIIT PMS', $htmlBody, $plainBody);

		if (isset($emailResult['success']) && $emailResult['success']) {
			$sent++;
			unset($_SESSION['email_queue'][$index]);

			// Update progress in session
			$_SESSION['email_progress'] = [
				'sent' => $sent,
				'failed' => $failed,
				'total' => $total,
				'percent' => round(($sent + $failed) / $total * 100),
				'done' => false
			];
			session_write_close(); // Write immediately
			session_start(); // Re-open for next iteration

			file_put_contents($log_file, date('Y-m-d H:i:s') . " - Sent to {$email_data['email']}\n", FILE_APPEND);
		} elseif (isset($emailResult['paused']) && $emailResult['paused']) {
			// Email service paused - do not count as failure; log and continue
			file_put_contents($log_file, date('Y-m-d H:i:s') . " - Paused (not sent) to {$email_data['email']}\n", FILE_APPEND);
		} else {
			$failed++;
			$_SESSION['email_progress'] = [
				'sent' => $sent,
				'failed' => $failed,
				'total' => $total,
				'percent' => round(($sent + $failed) / $total * 100),
				'done' => false
			];
			session_write_close();
			session_start();
			file_put_contents($log_file, date('Y-m-d H:i:s') . " - Failed to send to {$email_data['email']}\n", FILE_APPEND);
		}

		// Small delay to avoid overwhelming mail server
		usleep(200000); // 0.2 seconds between emails

	} catch (Exception $e) {
		$failed++;
		file_put_contents($log_file, date('Y-m-d H:i:s') . " - Error for {$email_data['email']}: {$e->getMessage()}\n", FILE_APPEND);
	}
}

// Clean up
$_SESSION['email_queue'] = [];
$_SESSION['email_progress'] = [
	'sent' => $sent,
	'failed' => $failed,
	'total' => $total,
	'percent' => 100,
	'done' => true
];

file_put_contents($log_file, date('Y-m-d H:i:s') . " - Completed. Sent: $sent, Failed: $failed\n", FILE_APPEND);

session_write_close();
exit('Done');
?>
						// Build a concise welcome email without any web links
						$messageLine = "You have been registered in the BMIIT Project Management Portal.\n\nUsername: {$email_data['username']}\nPasskey: {$email_data['passkey']}";
						list($htmlBody, $plainBody) = buildSimpleEmailBody($email_data['name'], $messageLine);

						$emailResult = sendEmail($email_data['email'], $email_data['name'], 'Welcome to BMIIT PMS', $htmlBody, $plainBody);

						if (isset($emailResult['success']) && $emailResult['success']) {
							$sent++;
							unset($_SESSION['email_queue'][$index]);

							// Update progress in session
							$_SESSION['email_progress'] = [
								'sent' => $sent,
								'failed' => $failed,
								'total' => $total,
								'percent' => round(($sent + $failed) / $total * 100),
								'done' => false
							];
							session_write_close(); // Write immediately
							session_start(); // Re-open for next iteration

							file_put_contents($log_file, date('Y-m-d H:i:s') . " - Sent to {$email_data['email']}\n", FILE_APPEND);
						} else {
							$failed++;
							$_SESSION['email_progress'] = [
								'sent' => $sent,
								'failed' => $failed,
								'total' => $total,
								'percent' => round(($sent + $failed) / $total * 100),
								'done' => false
							];
							session_write_close();
							session_start();
							file_put_contents($log_file, date('Y-m-d H:i:s') . " - Failed to send to {$email_data['email']}\n", FILE_APPEND);
						}

						// Small delay to avoid overwhelming mail server
						usleep(200000); // 0.2 seconds between emails

					} catch (Exception $e) {
						$failed++;
						file_put_contents($log_file, date('Y-m-d H:i:s') . " - Error for {$email_data['email']}: {$e->getMessage()}\n", FILE_APPEND);
					}
				}

				// Clean up
				$_SESSION['email_queue'] = [];
				$_SESSION['email_progress'] = [
					'sent' => $sent,
					'failed' => $failed,
					'total' => $total,
					'percent' => 100,
					'done' => true
				];

				file_put_contents($log_file, date('Y-m-d H:i:s') . " - Completed. Sent: $sent, Failed: $failed\n", FILE_APPEND);

				session_write_close();
				exit('Done');
				?>
