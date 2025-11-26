<?php
session_start();
require_once '../db.php';
require '../email_system/email_helper.php';
require '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Setup session user array
if (!isset($_SESSION['user'])) {
    $_SESSION['user'] = [
        'user_id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'] ?? 'admin',
        'role' => 'admin'
    ];
}

$message = "";
$msg_type = "";
$preview_data = [];
$credentials = [];

// Handle cancel - clear session and reload
if (isset($_GET['cancel'])) {
    unset($_SESSION['preview_data'], $_SESSION['semester_map'], $_SESSION['class_map']);
    header("Location: add_student_bulk.php");
    exit();
}

// Handle cancel - clear session and reload
if (isset($_GET['cancel'])) {
    unset($_SESSION['preview_data'], $_SESSION['semester_map'], $_SESSION['class_map'], $_SESSION['student_failed_rows'], $_SESSION['bulk_credentials'], $_SESSION['insertion_errors']);
    header("Location: add_student_bulk.php");
    exit();
}

// Handle template download with dropdowns
if (isset($_GET['download_template'])) {
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    
    // Set headers
    $headers = ['Name', 'Enrollment No', 'Email', 'Phone', 'Course & Semester', 'Division'];
    $col = 'A';
    foreach ($headers as $header) {
        $sheet->setCellValue($col . '1', $header);
        $col++;
    }
    
    // Get active semesters for dropdown
    $semester_query = "SELECT s.id, c.name as course_name, s.semester_no, s.year 
                      FROM semesters s 
                      JOIN courses c ON s.course_id = c.id 
                      WHERE s.project_active = 1 
                      ORDER BY c.name, s.semester_no";
    $semester_result = $conn->query($semester_query);
    $semester_options = [];
    while ($row = $semester_result->fetch_assoc()) {
        $semester_options[] = "{$row['course_name']} - Semester {$row['semester_no']} ({$row['year']})";
    }
    
    // Get divisions for dropdown
    $class_query = "SELECT DISTINCT name FROM classes ORDER BY name";
    $class_result = $conn->query($class_query);
    $division_options = [];
    while ($row = $class_result->fetch_assoc()) {
        $division_options[] = $row['name'];
    }
    
    // Add sample data
    $sheet->setCellValue('A2', 'John Doe');
    $sheet->setCellValue('B2', '123456789012345');
    $sheet->setCellValue('C2', 'john.doe@example.com');
    $sheet->setCellValue('D2', '9876543210');
    if (!empty($semester_options)) {
        $sheet->setCellValue('E2', $semester_options[0]);
    }
    if (!empty($division_options)) {
        $sheet->setCellValue('F2', $division_options[0]);
    }
    
    // Add data validation (dropdowns) for Course & Semester column
    if (!empty($semester_options)) {
        $validation = $sheet->getCell('E2')->getDataValidation();
        $validation->setType(DataValidation::TYPE_LIST);
        $validation->setErrorStyle(DataValidation::STYLE_INFORMATION);
        $validation->setAllowBlank(false);
        $validation->setShowInputMessage(true);
        $validation->setShowErrorMessage(true);
        $validation->setShowDropDown(true);
        $validation->setErrorTitle('Invalid Selection');
        $validation->setError('Please select from the dropdown list.');
        $validation->setPromptTitle('Select Course & Semester');
        $validation->setPrompt('Choose from available options');
        $validation->setFormula1('"' . implode(',', $semester_options) . '"');
        
        // Apply to more rows (E2:E100)
        for ($row = 2; $row <= 100; $row++) {
            $sheet->getCell('E' . $row)->setDataValidation(clone $validation);
        }
    }
    
    // Add data validation for Division column
    if (!empty($division_options)) {
        $validation = $sheet->getCell('F2')->getDataValidation();
        $validation->setType(DataValidation::TYPE_LIST);
        $validation->setErrorStyle(DataValidation::STYLE_INFORMATION);
        $validation->setAllowBlank(false);
        $validation->setShowInputMessage(true);
        $validation->setShowErrorMessage(true);
        $validation->setShowDropDown(true);
        $validation->setErrorTitle('Invalid Selection');
        $validation->setError('Please select from the dropdown list.');
        $validation->setPromptTitle('Select Division');
        $validation->setPrompt('Choose division (A, B, C, etc.)');
        $validation->setFormula1('"' . implode(',', $division_options) . '"');
        
        // Apply to more rows (F2:F100)
        for ($row = 2; $row <= 100; $row++) {
            $sheet->getCell('F' . $row)->setDataValidation(clone $validation);
        }
    }
    
    // Style headers
    $sheet->getStyle('A1:F1')->getFont()->setBold(true);
    $sheet->getStyle('A1:F1')->getFill()
          ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
          ->getStartColor()->setRGB('0B4DA0');
    $sheet->getStyle('A1:F1')->getFont()->getColor()->setRGB('FFFFFF');
    
    // Auto-size columns
    foreach(range('A','F') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }
    
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="student_bulk_template_' . date('Ymd') . '.xlsx"');
    header('Cache-Control: max-age=0');
    
    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit();
}

// Handle file upload and parse for preview
if (isset($_POST['upload_file']) && isset($_FILES['excel_file'])) {
    // Clear previous session data when starting a new upload
    unset($_SESSION['preview_data'], $_SESSION['student_failed_rows'], $_SESSION['bulk_credentials'], $_SESSION['insertion_errors']);
    
    $file = $_FILES['excel_file'];
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $message = "Error uploading file.";
        $msg_type = "error";
    } elseif ($file['size'] > 5 * 1024 * 1024) {
        $message = "File size exceeds 5MB limit.";
        $msg_type = "error";
    } else {
        try {
            $spreadsheet = IOFactory::load($file['tmp_name']);
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray();
            
            // Get semester and class mappings
            $semester_query = "SELECT s.id, c.name as course_name, s.semester_no, s.year 
                              FROM semesters s 
                              JOIN courses c ON s.course_id = c.id 
                              WHERE s.project_active = 1";
            $semester_result = $conn->query($semester_query);
            $semesters = [];
            while ($row = $semester_result->fetch_assoc()) {
                $key = "{$row['course_name']} - Semester {$row['semester_no']} ({$row['year']})";
                $semesters[$key] = ['id' => $row['id'], 'course' => $row['course_name'], 'sem' => $row['semester_no']];
            }
            
            $class_query = "SELECT id, name, semester_id FROM classes";
            $class_result = $conn->query($class_query);
            $classes = [];
            while ($row = $class_result->fetch_assoc()) {
                $classes[$row['semester_id'] . '_' . $row['name']] = $row['id'];
            }
            
            // Parse rows for preview (skip header)
            for ($i = 1; $i < count($rows); $i++) {
                $row = $rows[$i];
                if (empty(array_filter($row))) continue;
                
                $preview_data[] = [
                    // show data-row index starting at 1 (first data row = 1)
                    'row_num' => $i,
                    'name' => trim($row[0] ?? ''),
                    'enrollment' => trim($row[1] ?? ''),
                    'email' => trim($row[2] ?? ''),
                    'phone' => trim($row[3] ?? ''),
                    'course_semester' => trim($row[4] ?? ''),
                    'division' => trim($row[5] ?? ''),
                    'include' => true
                ];
            }
            
            // Store in session for review
            $_SESSION['preview_data'] = $preview_data;
            $_SESSION['semester_map'] = $semesters;
            $_SESSION['class_map'] = $classes;
            
            $message = count($preview_data) . " records loaded. Please review and make changes if needed.";
            $msg_type = "success";
            
        } catch (Exception $e) {
            $message = "Error processing file: " . $e->getMessage();
            $msg_type = "error";
        }
    }
}

// Handle final insertion after review
if (isset($_POST['confirm_insert']) && (isset($_SESSION['preview_data']) || isset($_SESSION['student_failed_rows']))) {
    // Senior dev fix: Increase limits for large batches
    @ini_set('max_execution_time', 300); // 5 minutes
    @ini_set('memory_limit', '256M');
    
    // Senior dev critical check: Detect POST data truncation
    if (!isset($_POST['include']) || empty($_POST['include'])) {
        $max_input_vars = ini_get('max_input_vars') ?: 1000;
        $message = "❌ ERROR: No records were selected for insertion. This might be due to PHP max_input_vars limit ($max_input_vars). Please process in smaller batches (try 50-100 records at a time) or contact your administrator to increase max_input_vars in php.ini.";
        $msg_type = "error";
        error_log("POST data appears truncated - no include array found");
    } else {
    
    // Use failed rows if retrying, otherwise use preview data
    if (isset($_SESSION['student_failed_rows']) && !empty($_SESSION['student_failed_rows'])) {
        $preview_data = $_SESSION['student_failed_rows'];
    } else {
        $preview_data = $_SESSION['preview_data'];
    }
    $semesters = $_SESSION['semester_map'];
    $classes = $_SESSION['class_map'];
    
    $success_count = 0;
    $error_count = 0;
    $results = [];
    $error_details = []; // Track detailed errors
    $seen_enrollments = []; // Track duplicates within Excel file
    $seen_emails = []; // Track duplicate emails within Excel file
    
    // Preserve existing credentials from previous successful insertions (for retry scenario)
    if (!isset($_SESSION['bulk_credentials'])) {
        $_SESSION['bulk_credentials'] = [];
    }
    $credentials = $_SESSION['bulk_credentials']; // Start with existing credentials
    
    // Senior dev fix: Start transaction for data integrity
    $conn->begin_transaction();
    
    error_log("Transaction started, autocommit disabled");
    
    try {
        $processed = 0;
        $total_selected = isset($_POST['include']) ? count($_POST['include']) : 0;
        
        error_log("Starting loop for $total_selected records");
        
        foreach ($preview_data as $idx => $data) {
            // Check if row is included (from checkboxes)
            if (!isset($_POST['include'][$idx])) continue;
            
            $processed++;
        
            // Get updated values from form
            $name = trim($_POST['name'][$idx] ?? $data['name']);
            $enroll = trim($_POST['enrollment'][$idx] ?? $data['enrollment']);
            $email = trim($_POST['email'][$idx] ?? $data['email']);
            $phone = trim($_POST['phone'][$idx] ?? $data['phone']);
            // Senior dev fix: Normalize whitespace in course_semester and division
            $course_semester = preg_replace('/\s+/', ' ', trim($_POST['course_semester'][$idx] ?? $data['course_semester']));
            $division = trim($_POST['division'][$idx] ?? $data['division']);
        
        $errors = [];
        
        // Check for duplicates within the Excel file itself ONLY for records being processed
        // Use the actual form values, not preview data
        // Only check if enrollment is not empty and valid to avoid false positives
        if (!empty($enroll) && strlen($enroll) == 15 && isset($seen_enrollments[$enroll])) {
            $errors[] = "Duplicate enrollment in Excel file (first appears in row {$seen_enrollments[$enroll]})";
        } elseif (!empty($enroll) && strlen($enroll) == 15) {
            $seen_enrollments[$enroll] = $data['row_num'];
        }
        
        if (!empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL) && isset($seen_emails[$email])) {
            $errors[] = "Duplicate email in Excel file (first appears in row {$seen_emails[$email]})";
        } elseif (!empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $seen_emails[$email] = $data['row_num'];
        }
        
        // Validation
        if (empty($name) || !preg_match('/^[A-Za-z \.]{2,50}$/', $name)) {
            $errors[] = "Invalid name";
        }
        if (empty($enroll) || !preg_match('/^[0-9]{15}$/', $enroll)) {
            $errors[] = "Invalid enrollment";
        }
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Invalid email";
        }
        if (empty($phone) || !preg_match('/^[0-9]{10}$/', $phone)) {
            $errors[] = "Invalid phone";
        }
        
        // Senior dev fix: Try exact match first, then fuzzy match
        $semester_id = $semesters[$course_semester]['id'] ?? null;
        
        // If exact match fails, try case-insensitive and whitespace-normalized match
        if (!$semester_id) {
            foreach ($semesters as $key => $value) {
                if (strcasecmp(preg_replace('/\s+/', ' ', $key), preg_replace('/\s+/', ' ', $course_semester)) === 0) {
                    $semester_id = $value['id'];
                    break;
                }
            }
        }
        
        $class_id = $classes[$semester_id . '_' . $division] ?? null;
        
        // If exact match fails, try case-insensitive match for division
        if (!$class_id && $semester_id) {
            foreach ($classes as $key => $value) {
                if (strpos($key, $semester_id . '_') === 0) {
                    $class_division = substr($key, strlen($semester_id . '_'));
                    if (strcasecmp($class_division, $division) === 0) {
                        $class_id = $value;
                        break;
                    }
                }
            }
        }
        
        if (!$semester_id) {
            $errors[] = "Invalid course/semester";
        }
        if (!$class_id) {
            $errors[] = "Invalid division";
        }
        
        // Check for duplicates (but don't stop the whole process)
        if (empty($errors)) {
            // Check if username (enrollment) already exists
            $check_username = $conn->prepare("SELECT id FROM users WHERE username=?");
            $check_username->bind_param("s", $enroll);
            $check_username->execute();
            if ($check_username->get_result()->num_rows > 0) {
                $errors[] = "Enrollment already exists in system";
            }
            $check_username->close();
            
            // Check if email already exists (if enrollment check passed)
            if (empty($errors)) {
                $check_email = $conn->prepare("SELECT student_id FROM students WHERE email=?");
                $check_email->bind_param("s", $email);
                $check_email->execute();
                if ($check_email->get_result()->num_rows > 0) {
                    $errors[] = "Email already exists in system";
                }
                $check_email->close();
            }
        }
        
        // If validation errors OR duplicates found, skip this record and continue with next
        if (!empty($errors)) {
            $error_count++;
            $error_details[] = "Row {$data['row_num']} ({$name}): " . implode(', ', $errors);
            $results[] = ['name' => $name, 'status' => 'skipped', 'errors' => $errors];
            error_log("Skipping row {$data['row_num']} ({$name}): " . implode(', ', $errors));
            continue; // Move to next record, don't stop entire process
        }
        
        // Generate passkey
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $passkey = '';
        for ($j = 0; $j < 8; $j++) {
            $passkey .= $chars[random_int(0, strlen($chars) - 1)];
        }
        $hashed = password_hash($passkey, PASSWORD_BCRYPT);
        
        // Insert
        $result = $conn->query("SELECT MAX(id) AS max_id FROM users");
        $user_row = $result->fetch_assoc();
        $next_user_id = ($user_row['max_id'] ?? 0) + 1;
        
        $stmt = $conn->prepare("INSERT INTO users (id, username, password_hash, role, first_login, is_active) VALUES (?, ?, ?, 'student', 1, 0)");
        $stmt->bind_param("iss", $next_user_id, $enroll, $hashed);
        
        if ($stmt->execute()) {
            error_log("User inserted: ID=$next_user_id, Username=$enroll");
            
            $result2 = $conn->query("SELECT MAX(student_id) AS max_id FROM students");
            $student_row = $result2->fetch_assoc();
            $next_student_id = ($student_row['max_id'] ?? 0) + 1;
            
            $stmt2 = $conn->prepare("INSERT INTO students(student_id, user_id, name, email, class_id, phone, temp_passkey) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt2->bind_param("iississ", $next_student_id, $next_user_id, $name, $email, $class_id, $phone, $passkey);
            
            if ($stmt2->execute()) {
                $success_count++;
                // Append to credentials (accumulates across retries)
                $credentials[] = ['name' => $name, 'email' => $email, 'username' => $enroll, 'passkey' => $passkey];
                error_log("Student inserted successfully: $name");
            } else {
                $error_count++;
                $error_details[] = "Row {$data['row_num']} ({$name}): Failed to insert student record";
            }
            $stmt2->close();
        } else {
            $error_count++;
            $error_details[] = "Row {$data['row_num']} ({$name}): Failed to insert user record - " . $stmt->error;
        }
        $stmt->close();
    }
        
        // Commit transaction ONLY if there are successful records
        if ($success_count > 0) {
            $conn->commit();
            error_log("Transaction committed successfully. Records: $success_count");
            
            // POPULATE EMAIL QUEUE ONLY AFTER SUCCESSFUL COMMIT
            if (!isset($_SESSION['email_queue'])) {
                $_SESSION['email_queue'] = [];
            }
            foreach ($credentials as $cred) {
                $_SESSION['email_queue'][] = [
                    'email' => $cred['email'],
                    'name' => $cred['name'],
                    'username' => $cred['username'],
                    'passkey' => $cred['passkey']
                ];
            }
        } else {
            // Rollback if no successful insertions
            $conn->rollback();
            error_log("Transaction rolled back. No successful insertions.");
        }
        
        // Update session with accumulated credentials (includes both old and new)
        $_SESSION['bulk_credentials'] = $credentials;
        $_SESSION['insertion_errors'] = $error_details;
        
        // If there are errors, keep failed rows for retry
        if ($error_count > 0) {
            $failed_rows = [];
            foreach ($preview_data as $idx => $data) {
                // Check if this row had an error (not in credentials list)
                $row_succeeded = false;
                foreach ($credentials as $cred) {
                    if ($cred['username'] === $data['enrollment']) {
                        $row_succeeded = true;
                        break;
                    }
                }
                if (!$row_succeeded) {
                    $failed_rows[] = $data;
                }
            }
            $_SESSION['student_failed_rows'] = $failed_rows;
        } else {
            unset($_SESSION['preview_data'], $_SESSION['semester_map'], $_SESSION['class_map'], $_SESSION['student_failed_rows']);
        }
        
        if ($error_count == 0) {
            unset($_SESSION['preview_data'], $_SESSION['semester_map'], $_SESSION['class_map']);
        }
        
        if ($success_count == 0 && $error_count > 0) {
            // Analyze error types
            $format_errors = 0;
            $duplicate_errors = 0;
            $validation_errors = 0;
            
            foreach ($error_details as $err) {
                if (strpos($err, 'Invalid course/semester') !== false || strpos($err, 'Invalid division') !== false) {
                    $format_errors++;
                } elseif (strpos($err, 'already exists') !== false || strpos($err, 'Duplicate') !== false) {
                    $duplicate_errors++;
                } else {
                    $validation_errors++;
                }
            }
            
            if ($format_errors > 0) {
                $message = "❌ <strong>CRITICAL: All records failed validation!</strong><br><br>";
                $message .= "<div style='line-height: 1.8;'>";
                $message .= "🔍 <strong>Common Cause:</strong> The Excel file format doesn't match the current database.<br><br>";
                $message .= "✅ <strong>Solution:</strong><br>";
                $message .= "<ol style='margin: 8px 0; padding-left: 20px;'>";
                $message .= "<li><a href='?download_template=1' style='color: #0B4DA0; font-weight: bold; text-decoration: underline;'>Download a fresh template</a> (with correct dropdowns)</li>";
                $message .= "<li>Copy your student data (Name, Enrollment, Email, Phone) into the new template</li>";
                $message .= "<li><strong>Use the DROPDOWN selections</strong> for 'Course & Semester' and 'Division' columns</li>";
                $message .= "<li>Upload the corrected file</li>";
                $message .= "</ol>";
                $message .= "⚠️ <strong>Note:</strong> Manually typing course/semester formats will cause validation failures. Always use the dropdowns!";
                $message .= "</div>";
                $msg_type = "error";
            } elseif ($duplicate_errors == $error_count) {
                $message = "⚠️ <strong>All records were duplicates!</strong><br>";
                $message .= "All $error_count records already exist in the system (duplicate enrollments or emails).<br>";
                $message .= "No new students were added. Check the error details below to see which records are duplicates.";
                $msg_type = "warning";
            } else {
                $message = "⚠️ <strong>WARNING: No records inserted.</strong><br>";
                $message .= "$error_count validation error(s) found. You can fix the errors below and try again.";
                $msg_type = "warning";
            }
        } else {
            // Analyze errors for partial success
            $duplicate_count = 0;
            foreach ($error_details as $err) {
                if (strpos($err, 'already exists') !== false || strpos($err, 'Duplicate') !== false) {
                    $duplicate_count++;
                }
            }
            
            $message = "✅ Insertion complete! Successfully added: $success_count student(s)";
            if ($error_count > 0) {
                $message .= " | ⚠️ Skipped: $error_count record(s)";
                if ($duplicate_count > 0) {
                    $message .= " ($duplicate_count duplicates)";
                }
                $message .= "<br>You can fix the failed records below and try again.";
            }
            $msg_type = $error_count > 0 ? "warning" : "success";
        }
        
        // Trigger automatic email sending if emails were queued
        $email_count = isset($_SESSION['email_queue']) ? count($_SESSION['email_queue']) : 0;
        if ($email_count > 0) {
            // Check if email service is paused
            if (!isEmailServiceEnabled()) {
                // Email service is paused - don't trigger sender, show warning
                $status = getEmailServiceStatus();
                $message .= "<br><br>⚠️ <strong>Email service is currently paused.</strong> " . ($status['message'] ?? 'All students were created in the database, but no welcome emails were sent.') . " Please manually share the credentials or enable the email service.";
                $msg_type = "warning";
                // Clear the queue since we won't send
                unset($_SESSION['email_queue']);
            } else {
                // Email service is active - proceed with background sender
                // Initialize progress tracking
                $_SESSION['email_progress'] = [
                    'sent' => 0,
                    'failed' => 0,
                    'total' => $email_count,
                    'percent' => 0,
                    'done' => false
                ];
                
                // Get current session ID
                $session_id = session_id();
                
                // Spawn background email process automatically
                $background_url = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/background_email_sender.php?sid=" . $session_id;
                
                error_log("Triggering background email sender: $background_url");
                
                // Use cURL to trigger background process without waiting
                $ch = curl_init($background_url);
                curl_setopt($ch, CURLOPT_TIMEOUT, 2); // 2 second timeout to ensure it starts
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_NOSIGNAL, 1);
                curl_setopt($ch, CURLOPT_HEADER, 0);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                $result = curl_exec($ch);
                $curl_error = curl_error($ch);
                $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
                
                error_log("Background email sender triggered. HTTP Code: $http_code, Error: $curl_error, Response: " . substr($result, 0, 200));
                error_log("Background email sender triggered for $email_count emails");
                
                // Store success message in session
                $_SESSION['bulk_insert_message'] = $message;
                $_SESSION['bulk_insert_msg_type'] = $msg_type;
                
                // Redirect to email sending page with progress bar
                header("Location: ?email_sending=1");
                exit();
            }
        }
        
    } catch (Exception $e) {
        // Rollback on error
        $conn->rollback();
        $message = "❌ Critical error during bulk insert: " . $e->getMessage();
        $msg_type = "error";
        error_log("Bulk insert error: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
    }
    
    // Debug: Log final counts
    error_log("Bulk insert completed: Success=$success_count, Errors=$error_count");
    
    } // End of POST include check
}

// Handle background email processing - TRIGGER ONLY
if (isset($_GET['process_emails']) && isset($_SESSION['email_queue'])) {
    // Initialize progress tracking
    $_SESSION['email_progress'] = [
        'sent' => 0,
        'failed' => 0,
        'total' => count($_SESSION['email_queue']),
        'percent' => 0,
        'done' => false
    ];
    
    // Get current session ID
    $session_id = session_id();
    
    // Spawn background process (Windows-compatible)
    $background_url = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/background_email_sender.php?sid=" . $session_id;
    
    // Use cURL to trigger background process without waiting
    $ch = curl_init($background_url);
    curl_setopt($ch, CURLOPT_TIMEOUT, 1); // 1 second timeout
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_NOSIGNAL, 1);
    curl_exec($ch);
    curl_close($ch);
    
    // Redirect immediately - don't wait for emails
    header("Location: ?email_sending=1");
    exit();
}

// Check email progress via AJAX
if (isset($_GET['check_progress']) && isset($_SESSION['email_progress'])) {
    header('Content-Type: application/json');
    echo json_encode($_SESSION['email_progress']);
    exit();
}

// Handle credentials download
if (isset($_GET['download_credentials']) && isset($_SESSION['bulk_credentials'])) {
    $credentials = $_SESSION['bulk_credentials'];
    
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    
    $sheet->setCellValue('A1', 'Name');
    $sheet->setCellValue('B1', 'Email');
    $sheet->setCellValue('C1', 'Username');
    $sheet->setCellValue('D1', 'Temporary Passkey');
    
    $row_num = 2;
    foreach ($credentials as $cred) {
        $sheet->setCellValue('A' . $row_num, $cred['name']);
        $sheet->setCellValue('B' . $row_num, $cred['email']);
        $sheet->setCellValue('C' . $row_num, $cred['username']);
        $sheet->setCellValue('D' . $row_num, $cred['passkey']);
        $row_num++;
    }
    
    $sheet->getStyle('A1:D1')->getFont()->setBold(true);
    $sheet->getStyle('A1:D1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('10B981');
    $sheet->getStyle('A1:D1')->getFont()->getColor()->setRGB('FFFFFF');
    
    foreach(range('A','D') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }
    
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="student_credentials_' . date('Ymd_His') . '.xlsx"');
    header('Cache-Control: max-age=0');
    
    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    
    // Don't clear credentials here - keep them for potential retries
    // They will be cleared when user cancels or starts a new upload
    exit();
}

$page_title = 'Bulk Upload Students';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

// Check if we have a stored message from redirect
if (isset($_SESSION['bulk_insert_message'])) {
    $message = $_SESSION['bulk_insert_message'];
    $msg_type = $_SESSION['bulk_insert_msg_type'] ?? 'info';
    unset($_SESSION['bulk_insert_message'], $_SESSION['bulk_insert_msg_type']);
}
?>

<main class="main-wrapper">
    <div class="container">
        <!-- Senior dev addition: System diagnostics -->
        <?php if (isset($_GET['show_diagnostics'])): ?>
        <div class="alert alert-info" style="font-family: monospace; font-size: 12px;">
            <strong>🔧 System Diagnostics (for troubleshooting):</strong><br>
            PHP max_execution_time: <?= ini_get('max_execution_time') ?>s<br>
            PHP max_input_vars: <?= ini_get('max_input_vars') ?><br>
            PHP memory_limit: <?= ini_get('memory_limit') ?><br>
            PHP post_max_size: <?= ini_get('post_max_size') ?><br>
            PHP upload_max_filesize: <?= ini_get('upload_max_filesize') ?><br>
            Session preview_data count: <?= isset($_SESSION['preview_data']) ? count($_SESSION['preview_data']) : 0 ?><br>
            Estimated form fields: <?= isset($_SESSION['preview_data']) ? count($_SESSION['preview_data']) * 7 : 0 ?>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($message)): ?>
            <div class="alert alert-<?= $msg_type ?>">
                <?= $message ?>
            </div>
            <?php if (isset($_SESSION['insertion_errors']) && !empty($_SESSION['insertion_errors'])): ?>
                <div class="alert alert-error" style="margin-top: 10px;">
                    <details>
                        <summary style="cursor: pointer; font-weight: bold;">📋 View Error Details (<?= count($_SESSION['insertion_errors']) ?>)</summary>
                        <ul style="margin-top: 10px; padding-left: 20px;">
                            <?php foreach ($_SESSION['insertion_errors'] as $error): ?>
                                <li><?= $error ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </details>
                </div>
                <?php unset($_SESSION['insertion_errors']); ?>
            <?php endif; ?>
            <?php if (isset($_GET['show_diagnostics']) && $msg_type === 'success'): ?>
                <div class="alert alert-info" style="margin-top: 10px; font-family: monospace; font-size: 12px;">
                    <strong>📊 Debug Info:</strong><br>
                    Success Count: <?= $success_count ?? 0 ?><br>
                    Error Count: <?= $error_count ?? 0 ?><br>
                    Total Selected: <?= $total_selected ?? 0 ?><br>
                    Credentials Generated: <?= isset($_SESSION['bulk_credentials']) ? count($_SESSION['bulk_credentials']) : 0 ?><br>
                    Email Queue: <?= isset($_SESSION['email_queue']) ? count($_SESSION['email_queue']) : 0 ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <?php if (empty($preview_data) && !isset($_SESSION['preview_data'])): ?>
        <!-- Step 1 & 2: Upload -->
        <div class="card fade-in">
            <div class="card-header">
                <h1 class="card-title">
                    <i data-feather="upload-cloud"></i>
                    Bulk Upload Students
                </h1>
                <p class="card-subtitle">Upload multiple students using Excel file with smart dropdown validation</p>
            </div>

            <div class="form-grid-2" style="margin-bottom: 20px;">
                <div style="background: var(--bg); padding: 20px; border-radius: 10px; border: 2px solid var(--border);">
                    <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 12px;">
                        <div style="width: 36px; height: 36px; background: var(--primary); border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                            <i data-feather="download" style="color: white; width: 18px; height: 18px;"></i>
                        </div>
                        <h3 style="margin: 0; color: var(--text); font-size: 15px; font-weight: 600;">Step 1: Download Template</h3>
                    </div>
                    <p style="color: var(--text-light); margin-bottom: 16px; line-height: 1.5; font-size: 13px;">
                        Get the pre-configured Excel template with dropdown lists for courses, semesters, and divisions.
                    </p>
                    <a href="?download_template=1" class="btn" style="width: 100%;">
                        <i data-feather="file-text"></i>
                        Download Template File
                    </a>
                </div>

                <div style="background: var(--bg); padding: 20px; border-radius: 10px; border: 2px solid var(--border);">
                    <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 12px;">
                        <div style="width: 36px; height: 36px; background: var(--secondary); border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                            <i data-feather="upload" style="color: white; width: 18px; height: 18px;"></i>
                        </div>
                        <h3 style="margin: 0; color: var(--text); font-size: 15px; font-weight: 600;">Step 2: Upload Completed File</h3>
                    </div>
                    <form method="POST" enctype="multipart/form-data">
                        <div class="form-group" style="margin-bottom: 12px;">
                            <label for="excel_file">Select Excel File</label>
                            <input type="file" 
                                   id="excel_file" 
                                   name="excel_file" 
                                   accept=".xlsx,.xls" 
                                   required>
                            <small class="help-text">Maximum file size: 5MB</small>
                        </div>
                        <button type="submit" name="upload_file" class="btn-accent" style="width: 100%;">
                            <i data-feather="upload-cloud"></i>
                            Upload & Preview Data
                        </button>
                    </form>
                </div>
            </div>

            <!-- Instructions Card -->
            <div style="background: rgba(11, 77, 160, 0.05); padding: 16px; border-radius: 8px; border-left: 3px solid var(--primary);">
                <h4 style="margin: 0 0 10px 0; color: var(--primary); font-size: 14px; font-weight: 600;">
                    <i data-feather="info"></i>
                    How to use this feature
                </h4>
                <ol style="margin: 0; padding-left: 20px; line-height: 1.6; color: var(--text); font-size: 13px;">
                    <li>Click "Download Template File" to get the Excel file</li>
                    <li>Open the file and fill in student details (use dropdowns for Course/Semester and Division)</li>
                    <li>Save the completed file</li>
                    <li>Upload it using the form above</li>
                    <li>Review the preview and make any necessary edits</li>
                    <li>Click "Import All Students" to complete the process</li>
                </ol>
            </div>
        </div>
        <?php endif; ?>

        <?php if (!empty($preview_data) || isset($_SESSION['preview_data']) || isset($_SESSION['student_failed_rows'])): 
            // Show failed rows if they exist, otherwise show preview data
            if (isset($_SESSION['student_failed_rows']) && !empty($_SESSION['student_failed_rows'])) {
                $preview_data = $_SESSION['student_failed_rows'];
                $is_retry = true;
            } else {
                $preview_data = $_SESSION['preview_data'] ?? $preview_data;
                $is_retry = false;
            }
            
            // Senior dev check: Warn about max_input_vars limit
            $max_input_vars = ini_get('max_input_vars') ?: 1000;
            $estimated_vars = count($preview_data) * 7; // 7 fields per row
            $vars_warning = ($estimated_vars > $max_input_vars * 0.8);
            $vars_info = ($estimated_vars > 700); // Show info if processing large dataset
        ?>
        <!-- Step 3: Review & Edit -->
        <?php if ($vars_warning): ?>
        <div class="alert alert-warning">
            <strong>⚠️ Large Dataset Warning:</strong> You have <?= count($preview_data) ?> records (≈<?= $estimated_vars ?> form fields). 
            Your PHP max_input_vars is set to <?= $max_input_vars ?>. If the form doesn't submit properly, you may need to process in smaller batches 
            or contact your system administrator to increase this limit in php.ini.
            <br><br>
            <details style="margin-top: 10px;">
                <summary style="cursor: pointer; font-weight: bold;">📚 How to fix this permanently</summary>
                <ol style="margin-top: 10px; padding-left: 20px;">
                    <li>Locate your php.ini file (usually in E:\Dev-Tools\xampp\php\php.ini)</li>
                    <li>Find the line: <code>;max_input_vars = 1000</code></li>
                    <li>Change it to: <code>max_input_vars = 5000</code></li>
                    <li>Restart Apache from XAMPP Control Panel</li>
                </ol>
            </details>
        </div>
        <?php elseif ($vars_info): ?>
        <div class="alert alert-info">
            <strong>ℹ️ Processing <?= count($preview_data) ?> records</strong> (≈<?= $estimated_vars ?> form fields). 
            Your PHP max_input_vars limit is <?= $max_input_vars ?>. You're good to go! 
            Click "Confirm & Insert" to process all records.
        </div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-header">
                <h1 class="card-title"><?= $is_retry ? '🔄 Fix Errors & Retry' : 'Review & Edit Data' ?> (<?= count($preview_data) ?> records)</h1>
                <p class="card-subtitle"><?= $is_retry ? 'Correct the errors below and resubmit' : 'Make last-minute changes before inserting into database' ?></p>
            </div>

            <form method="POST">
                <div class="table-wrapper" style="max-height: 600px; overflow-y: auto;">
                    <table class="table">
                        <thead style="position: sticky; top: 0; background: var(--bg); z-index: 10;">
                            <tr>
                                <th style="width: 50px;">
                                    <input type="checkbox" id="select_all" checked style="width: 18px; height: 18px;">
                                </th>
                                <th>Row</th>
                                <th>Name</th>
                                <th>Enrollment</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Course & Semester</th>
                                <th>Division</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($preview_data as $idx => $data): ?>
                            <tr>
                                <td>
                                    <input type="checkbox" name="include[<?= $idx ?>]" class="row-checkbox" checked style="width: 18px; height: 18px;">
                                </td>
                                <td><?= $data['row_num'] ?></td>
                                <td><input type="text" name="name[<?= $idx ?>]" value="<?= htmlspecialchars($data['name']) ?>" style="width: 100%; padding: 4px; border: 1px solid var(--border); border-radius: 4px;"></td>
                                <td><input type="text" name="enrollment[<?= $idx ?>]" value="<?= htmlspecialchars($data['enrollment']) ?>" style="width: 100%; padding: 4px; border: 1px solid var(--border); border-radius: 4px;"></td>
                                <td><input type="text" name="email[<?= $idx ?>]" value="<?= htmlspecialchars($data['email']) ?>" style="width: 100%; padding: 4px; border: 1px solid var(--border); border-radius: 4px;"></td>
                                <td><input type="text" name="phone[<?= $idx ?>]" value="<?= htmlspecialchars($data['phone']) ?>" style="width: 100%; padding: 4px; border: 1px solid var(--border); border-radius: 4px;"></td>
                                <td>
                                    <?php 
                                    $cs_exists = isset($_SESSION['semester_map'][$data['course_semester']]);
                                    $border_color = $cs_exists ? 'var(--border)' : '#ef4444';
                                    ?>
                                    <input type="text" name="course_semester[<?= $idx ?>]" value="<?= htmlspecialchars($data['course_semester']) ?>" style="width: 100%; padding: 4px; border: 2px solid <?= $border_color ?>; border-radius: 4px;" title="<?= $cs_exists ? 'Valid' : '⚠️ Not found in database - check Debug Mappings' ?>">
                                </td>
                                <td>
                                    <?php 
                                    $sem_id = $_SESSION['semester_map'][$data['course_semester']]['id'] ?? null;
                                    $div_exists = $sem_id && isset($_SESSION['class_map'][$sem_id . '_' . $data['division']]);
                                    $div_border = $div_exists ? 'var(--border)' : '#ef4444';
                                    ?>
                                    <input type="text" name="division[<?= $idx ?>]" value="<?= htmlspecialchars($data['division']) ?>" style="width: 100%; padding: 4px; border: 2px solid <?= $div_border ?>; border-radius: 4px;" title="<?= $div_exists ? 'Valid' : '⚠️ Not found for this semester - check Debug Mappings' ?>">
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div style="display: flex; gap: 12px; margin-top: 20px; justify-content: flex-end;">
                    <a href="?cancel=1" class="btn-secondary" onclick="(function(e){ e.preventDefault(); window.confirmAsync('Are you sure? This will clear all data.','Confirm', true).then(function(ok){ if(ok) location.href='?cancel=1'; }); })(event); return false;">
                        <i data-feather="x"></i>
                        <?= $is_retry ? 'Discard Failed Records' : 'Cancel' ?>
                    </a>
                    <button type="submit" name="confirm_insert" class="btn" id="submit_btn">
                        <i data-feather="check-circle"></i>
                        <?= $is_retry ? 'Retry Insertion' : 'Confirm & Insert Selected Records' ?>
                    </button>
                </div>
            </form>
        </div>

        <!-- Senior dev addition: Loading overlay -->
        <div id="loading_overlay" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); z-index: 9999; justify-content: center; align-items: center; flex-direction: column;">
            <div style="background: white; padding: 40px; border-radius: 12px; text-align: center; box-shadow: 0 10px 40px rgba(0,0,0,0.3);">
                <div style="width: 60px; height: 60px; border: 6px solid #f3f3f3; border-top: 6px solid #0B4DA0; border-radius: 50%; animation: spin 1s linear infinite; margin: 0 auto 20px;"></div>
                <h2 style="margin: 0 0 10px 0; color: #333;">Processing Records...</h2>
                <p style="margin: 0; color: #666;">Please wait. This may take a few minutes for large batches.</p>
                <p style="margin: 10px 0 0 0; color: #999; font-size: 14px;">Do not close this window or refresh the page.</p>
            </div>
        </div>
        <style>
            @keyframes spin {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }
        </style>

        <script>
            document.getElementById('select_all').addEventListener('change', function() {
                document.querySelectorAll('.row-checkbox').forEach(cb => cb.checked = this.checked);
            });
            
            // Senior dev fix: Show loading overlay on form submit
            document.querySelector('form[method="POST"]').addEventListener('submit', function(e) {
                const checkedCount = document.querySelectorAll('.row-checkbox:checked').length;
                if (checkedCount === 0) {
                    e.preventDefault();
                    alert('Please select at least one record to insert.');
                    return false;
                }
                
                // Show loading overlay
                document.getElementById('loading_overlay').style.display = 'flex';
                document.getElementById('submit_btn').disabled = true;
                
                // Prevent double submission
                this.addEventListener('submit', function(e) {
                    e.preventDefault();
                    return false;
                });
            });
        </script>
        <?php endif; ?>

        <?php if (isset($_SESSION['bulk_credentials']) && !empty($_SESSION['bulk_credentials'])): ?>
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Download Credentials</h2>
                <p class="card-subtitle">Save login credentials for distribution</p>
            </div>
            <a href="?download_credentials=1" class="btn">
                <i data-feather="download"></i>
                Download Credentials Excel
            </a>
        </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['email_queue']) && !empty($_SESSION['email_queue']) && !isset($_GET['email_sending'])): ?>
        <div class="card" id="email_card">
            <div class="card-header">
                <h2 class="card-title">📧 Send Welcome Emails</h2>
                <p class="card-subtitle">Send login credentials to <?= count($_SESSION['email_queue']) ?> students</p>
            </div>
            
            <div id="email_status" style="margin-bottom: 20px;">
                <p>Click the button below to start sending emails in the <strong>background</strong>. You can continue working while emails are being sent.</p>
            </div>
            
            <button onclick="startEmailSending()" id="send_email_btn" class="btn" style="background: #10B981;">
                <i data-feather="send"></i>
                Send <?= count($_SESSION['email_queue']) ?> Welcome Emails
            </button>
        </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['email_sending']) && isset($_SESSION['email_progress'])): ?>
        <div class="card" id="email_card">
            <div class="card-header">
                <h2 class="card-title">📧 Sending Welcome Emails</h2>
                <p class="card-subtitle">Emails are being sent in the background...</p>
            </div>
            
            <div id="email_status" style="margin-bottom: 20px;">
                <div style="background: #EFF6FF; padding: 12px; border-radius: 8px; border-left: 4px solid #3B82F6;">
                    ℹ️ <strong>You can navigate away!</strong> Emails will continue sending in the background. Come back anytime to check progress.
                </div>
            </div>
            
            <div id="email_progress">
                <div style="background: #f3f4f6; border-radius: 8px; height: 30px; overflow: hidden; margin-bottom: 10px;">
                    <div id="progress_bar" style="background: linear-gradient(90deg, #0B4DA0, #10B981); height: 100%; width: 0%; transition: width 0.3s; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; font-size: 14px;">
                        0%
                    </div>
                </div>
                <p id="progress_text" style="color: #666; font-size: 14px;">Initializing email sender...</p>
            </div>
        </div>
        <?php endif; ?>
        
        <script>
        function startEmailSending() {
            window.location.href = '?process_emails=1';
        }
        
        // Auto-start polling if we're on the email_sending page
        <?php if (isset($_GET['email_sending']) && isset($_SESSION['email_progress'])): ?>
        document.addEventListener('DOMContentLoaded', function() {
            pollEmailProgress();
        });
        
        function pollEmailProgress() {
            fetch('?check_progress=1&t=' + Date.now())
                .then(response => response.json())
                .then(data => {
                    const progressBar = document.getElementById('progress_bar');
                    const progressText = document.getElementById('progress_text');
                    const statusDiv = document.getElementById('email_status');
                    
                    if (data.percent !== undefined) {
                        progressBar.style.width = data.percent + '%';
                        progressBar.textContent = data.percent + '%';
                        progressText.innerHTML = `📧 Sending emails... Sent: <strong>${data.sent}</strong> | Failed: <strong>${data.failed}</strong> | Total: <strong>${data.total}</strong>`;
                    }
                    
                    if (data.done) {
                        progressBar.style.width = '100%';
                        progressBar.textContent = '100%';
                        progressBar.style.background = '#10B981';
                        
                        if (data.failed > 0) {
                            statusDiv.innerHTML = `<div style="background: #FEF3C7; padding: 12px; border-radius: 8px; border-left: 4px solid #F59E0B; margin-top: 10px;">⚠️ Completed with ${data.failed} failure(s). Sent: ${data.sent}/${data.total}</div>`;
                            progressText.innerHTML = `✅ Email sending complete! Sent: <strong>${data.sent}</strong>, Failed: <strong>${data.failed}</strong>`;
                        } else {
                            statusDiv.innerHTML = `<div style="background: #D1FAE5; padding: 12px; border-radius: 8px; border-left: 4px solid #10B981; margin-top: 10px;">✅ All ${data.sent} emails sent successfully!</div>`;
                            progressText.innerHTML = `✅ Perfect! All <strong>${data.total}</strong> emails delivered successfully!`;
                        }
                        
                        // Clean up session and reload after 3 seconds
                        setTimeout(() => {
                            window.location.href = '?';
                        }, 3000);
                    } else {
                        // Continue polling every 1 second
                        setTimeout(pollEmailProgress, 1000);
                    }
                })
                .catch(error => {
                    console.error('Polling error:', error);
                    // Retry after 2 seconds if error
                    setTimeout(pollEmailProgress, 2000);
                });
        }
        <?php endif; ?>
        </script>
    </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
