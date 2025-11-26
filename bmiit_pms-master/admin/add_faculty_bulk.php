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
    unset($_SESSION['faculty_preview_data'], $_SESSION['faculty_failed_rows'], $_SESSION['bulk_credentials'], $_SESSION['insertion_errors']);
    header("Location: add_faculty_bulk.php");
    exit();
}

// Handle template download with dropdowns for specialization
if (isset($_GET['download_template'])) {
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    
    // Set headers
    $headers = ['Name', 'Email', 'Phone', 'Specialization', 'Experience (years)'];
    $col = 'A';
    foreach ($headers as $header) {
        $sheet->setCellValue($col . '1', $header);
        $col++;
    }
    
    // Common specializations dropdown
    $specializations = [
        'Data Science', 'Web Development', 'Mobile Development', 'Machine Learning',
        'Artificial Intelligence', 'Cybersecurity', 'Cloud Computing', 'DevOps',
        'Database Management', 'Software Engineering', 'Network Administration',
        'IoT', 'Blockchain', 'UI/UX Design', 'Other'
    ];
    
    // Add sample data
    $sheet->setCellValue('A2', 'Dr. Jane Smith');
    $sheet->setCellValue('B2', 'jane.smith@example.com');
    $sheet->setCellValue('C2', '9876543210');
    $sheet->setCellValue('D2', 'Data Science');
    $sheet->setCellValue('E2', '5');
    
    // Add data validation for Specialization column
    $validation = $sheet->getCell('D2')->getDataValidation();
    $validation->setType(DataValidation::TYPE_LIST);
    $validation->setErrorStyle(DataValidation::STYLE_INFORMATION);
    $validation->setAllowBlank(false);
    $validation->setShowInputMessage(true);
    $validation->setShowErrorMessage(true);
    $validation->setShowDropDown(true);
    $validation->setErrorTitle('Invalid Selection');
    $validation->setError('Please select from the dropdown or type your own.');
    $validation->setPromptTitle('Select Specialization');
    $validation->setPrompt('Choose from common specializations');
    $validation->setFormula1('"' . implode(',', $specializations) . '"');
    
    // Apply to more rows (D2:D100)
    for ($row = 2; $row <= 100; $row++) {
        $sheet->getCell('D' . $row)->setDataValidation(clone $validation);
    }
    
    // Style headers
    $sheet->getStyle('A1:E1')->getFont()->setBold(true);
    $sheet->getStyle('A1:E1')->getFill()
          ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
          ->getStartColor()->setRGB('0B4DA0');
    $sheet->getStyle('A1:E1')->getFont()->getColor()->setRGB('FFFFFF');
    
    // Auto-size columns
    foreach(range('A','E') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }
    
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="faculty_bulk_template_' . date('Ymd') . '.xlsx"');
    header('Cache-Control: max-age=0');
    
    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit();
}

// Handle file upload and parse for preview
if (isset($_POST['upload_file']) && isset($_FILES['excel_file'])) {
    // Clear previous session data when starting a new upload
    unset($_SESSION['faculty_preview_data'], $_SESSION['faculty_failed_rows'], $_SESSION['bulk_credentials'], $_SESSION['insertion_errors']);
    
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
            
            // Parse rows for preview (skip header)
            for ($i = 1; $i < count($rows); $i++) {
                $row = $rows[$i];
                if (empty(array_filter($row))) continue;
                
            $preview_data[] = [
                // show data-row index starting at 1 (first data row = 1)
                'row_num' => $i,
                    'name' => trim($row[0] ?? ''),
                    'email' => trim($row[1] ?? ''),
                    'phone' => trim($row[2] ?? ''),
                    'specialization' => trim($row[3] ?? ''),
                    'experience' => trim($row[4] ?? ''),
                    'include' => true
                ];
            }
            
            // Store in session for review
            $_SESSION['faculty_preview_data'] = $preview_data;
            
            $message = count($preview_data) . " records loaded. Please review and make changes if needed.";
            $msg_type = "success";
            
        } catch (Exception $e) {
            $message = "Error processing file: " . $e->getMessage();
            $msg_type = "error";
        }
    }
}

// Handle final insertion after review
if (isset($_POST['confirm_insert']) && (isset($_SESSION['faculty_preview_data']) || isset($_SESSION['faculty_failed_rows']))) {
    // Use failed rows if retrying, otherwise use preview data
    if (isset($_SESSION['faculty_failed_rows']) && !empty($_SESSION['faculty_failed_rows'])) {
        $preview_data = $_SESSION['faculty_failed_rows'];
    } else {
        $preview_data = $_SESSION['faculty_preview_data'];
    }
    
    $success_count = 0;
    $error_count = 0;
    $error_details = []; // human-readable errors to show like student bulk
    $email_service_paused = false;
    
    // Check if email service is enabled
    if (!isEmailServiceEnabled()) {
        $email_service_paused = true;
    }
    
    // Preserve existing credentials from previous successful insertions (for retry scenario)
    if (!isset($_SESSION['bulk_credentials'])) {
        $_SESSION['bulk_credentials'] = [];
    }
    $credentials = $_SESSION['bulk_credentials']; // Start with existing credentials
    
    foreach ($preview_data as $idx => $data) {
        // Check if row is included
        if (!isset($_POST['include'][$idx])) continue;
        
        // Get updated values from form
        $name = trim($_POST['name'][$idx] ?? $data['name']);
        $email = trim($_POST['email'][$idx] ?? $data['email']);
        $phone = trim($_POST['phone'][$idx] ?? $data['phone']);
        $specialization = trim($_POST['specialization'][$idx] ?? $data['specialization']);
        $experience = trim($_POST['experience'][$idx] ?? $data['experience']);
        
        $errors = [];
        
        // Validation
        if (empty($name) || !preg_match('/^[A-Za-z\. ]{2,50}$/', $name)) {
            $errors[] = "Invalid name";
        }
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Invalid email";
        }
        if (empty($phone) || !preg_match('/^[0-9]{10}$/', $phone)) {
            $errors[] = "Invalid phone";
        }
        if (empty($specialization) || strlen($specialization) < 3) {
            $errors[] = "Invalid specialization";
        }
        if (!is_numeric($experience) || $experience < 0 || $experience > 40) {
            $errors[] = "Invalid experience";
        }
        
        // Check duplicates
        if (empty($errors)) {
            $check = $conn->prepare("SELECT id FROM users WHERE username=?");
            $check->bind_param("s", $email);
            $check->execute();
            if ($check->get_result()->num_rows > 0) {
                $errors[] = "Email exists";
            }
            $check->close();
        }
        
        if (!empty($errors)) {
            $error_count++;
            $error_details[] = "Row {$data['row_num']} ({$name}): " . implode(', ', $errors);
            continue;
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
        
        $stmt = $conn->prepare("INSERT INTO users (id, username, password_hash, role, first_login, is_active) VALUES (?, ?, ?, 'faculty', 1, 0)");
        $stmt->bind_param("iss", $next_user_id, $email, $hashed);
        
        if ($stmt->execute()) {
            $result2 = $conn->query("SELECT MAX(faculty_id) AS max_id FROM faculty");
            $faculty_row = $result2->fetch_assoc();
            $next_faculty_id = ($faculty_row['max_id'] ?? 0) + 1;
            
            $stmt2 = $conn->prepare("INSERT INTO faculty(faculty_id, user_id, name, email, phone, specialization, experience, temp_passkey) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt2->bind_param("iissssis", $next_faculty_id, $next_user_id, $name, $email, $phone, $specialization, $experience, $passkey);
            
                if ($stmt2->execute()) {
                $success_count++;
                // Append to credentials (accumulates across retries)
                $credentials[] = ['name' => $name, 'email' => $email, 'username' => $email, 'passkey' => $passkey];

                // Send email only if service is not paused
                if (!$email_service_paused) {
                    $messageLine = "You have been registered as faculty in the BMIIT Project Management Portal.\n\nUsername: $email\nPasskey: $passkey";
                    list($htmlBody, $plainBody) = buildSimpleEmailBody($name, $messageLine);
                    $r = sendEmail($email, $name, 'Welcome to BMIIT PMS', $htmlBody, $plainBody);
                    if (is_array($r) && empty($r['success']) && empty($r['paused'])) {
                        // Record an email send failure for this row so admin can review
                        $error_count++;
                        $error_details[] = "Row {$data['row_num']} ({$name}): Email send failed - " . ($r['message'] ?? 'Unknown error');
                    }
                }
            } else {
                // faculty insert failed - capture DB error
                $error_count++;
                $errMsg = "Row {$data['row_num']} ({$name}): Failed to insert faculty record - " . $stmt2->error;
                $error_details[] = $errMsg;
            }
            $stmt2->close();
        } else {
            // user insert failed - capture DB error
            $error_count++;
            $errMsg = "Row {$data['row_num']} ({$name}): Failed to insert user record - " . $stmt->error;
            $error_details[] = $errMsg;
        }
        $stmt->close();
    }
    
    // Update session with accumulated credentials (includes both old and new)
    $_SESSION['bulk_credentials'] = $credentials;
    // Store human-readable insertion errors for the error panel
    $_SESSION['insertion_errors'] = $error_details;
    
    // If there are errors, keep failed rows for retry
    if ($error_count > 0) {
        $failed_rows = [];
        foreach ($preview_data as $idx => $data) {
            // Check if this row had an error (not in credentials list)
            $row_succeeded = false;
            foreach ($credentials as $cred) {
                if ($cred['email'] === $data['email']) {
                    $row_succeeded = true;
                    break;
                }
            }
            if (!$row_succeeded) {
                $failed_rows[] = $data;
            }
        }
        $_SESSION['faculty_failed_rows'] = $failed_rows;
    } else {
        unset($_SESSION['faculty_preview_data'], $_SESSION['faculty_failed_rows']);
    }
    
    if ($error_count == 0) {
        unset($_SESSION['faculty_preview_data']);
    }

    // Build appropriate message based on results
    if ($success_count == 0 && $error_count > 0) {
        // All records failed - analyze error types
        $duplicate_errors = 0;
        $validation_errors = 0;
        
        foreach ($error_details as $err) {
            if (strpos($err, 'Email exists') !== false || strpos($err, 'already exists') !== false) {
                $duplicate_errors++;
            } else {
                $validation_errors++;
            }
        }
        
        if ($duplicate_errors == $error_count) {
            $message = "⚠️ <strong>All records were duplicates!</strong><br>";
            $message .= "All $error_count records already exist in the system (duplicate emails).<br>";
            $message .= "No new faculty members were added. Check the error details below to see which records are duplicates.";
            $msg_type = "warning";
        } else {
            $message = "⚠️ <strong>WARNING: No records inserted.</strong><br>";
            $message .= "$error_count validation error(s) found. You can fix the errors below and try again.";
            $msg_type = "error";
        }
    } else {
        // Some or all records succeeded
        // Build message with email service warning if paused
        if ($email_service_paused && $success_count > 0) {
            $message = "✅ Insertion complete. Success: $success_count, Errors: $error_count<br>⚠️ <strong>Emails were not sent as the email service is disabled right now.</strong> Please manually share the credentials or enable the email service.";
            if ($error_count > 0) {
                $message .= "<br><br>You can fix the failed records below and try again.";
            }
            $msg_type = "warning";
        } else {
            $message = "✅ Insertion complete! Successfully added: $success_count faculty member(s)";
            if ($error_count > 0) {
                $message .= " | ⚠️ Skipped: $error_count record(s)<br>You can fix the failed records below and try again.";
            }
            $msg_type = $error_count > 0 ? "warning" : "success";
        }
    }
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
    header('Content-Disposition: attachment;filename="faculty_credentials_' . date('Ymd_His') . '.xlsx"');
    header('Cache-Control: max-age=0');
    
    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    
    // Don't clear credentials here - keep them for potential retries
    // They will be cleared when user cancels or starts a new upload
    exit();
}

$page_title = 'Bulk Upload Faculty';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<main class="main-wrapper">
    <div class="container">
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
        <?php endif; ?>

        <?php if (empty($preview_data) && !isset($_SESSION['faculty_preview_data'])): ?>
        <!-- Step 1 & 2: Upload -->
        <div class="card fade-in">
            <div class="card-header">
                <h1 class="card-title">
                    <i data-feather="upload-cloud"></i>
                    Bulk Upload Faculty
                </h1>
                <p class="card-subtitle">Upload multiple faculty members using Excel file with smart dropdown validation</p>
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
                        Get the pre-configured Excel template with dropdown list for common specializations.
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
                    <li>Open the file and fill in faculty details (use dropdown for Specialization)</li>
                    <li>Save the completed file</li>
                    <li>Upload it using the form above</li>
                    <li>Review the preview and make any necessary edits</li>
                    <li>Click "Import All Faculty" to complete the process</li>
                </ol>
            </div>
        </div>
        <?php endif; ?>

        <?php if (!empty($preview_data) || isset($_SESSION['faculty_preview_data']) || isset($_SESSION['faculty_failed_rows'])): 
            // Show failed rows if they exist, otherwise show preview data
            if (isset($_SESSION['faculty_failed_rows']) && !empty($_SESSION['faculty_failed_rows'])) {
                $preview_data = $_SESSION['faculty_failed_rows'];
                $is_retry = true;
            } else {
                $preview_data = $_SESSION['faculty_preview_data'] ?? $preview_data;
                $is_retry = false;
            }
        ?>
        <!-- Step 3: Review & Edit -->
        <div class="card">
            <div class="card-header">
                <h1 class="card-title"><?= $is_retry ? '🔄 Fix Errors & Retry' : 'Review & Edit Data' ?></h1>
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
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Specialization</th>
                                <th>Experience</th>
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
                                <td><input type="text" name="email[<?= $idx ?>]" value="<?= htmlspecialchars($data['email']) ?>" style="width: 100%; padding: 4px; border: 1px solid var(--border); border-radius: 4px;"></td>
                                <td><input type="text" name="phone[<?= $idx ?>]" value="<?= htmlspecialchars($data['phone']) ?>" style="width: 100%; padding: 4px; border: 1px solid var(--border); border-radius: 4px;"></td>
                                <td><input type="text" name="specialization[<?= $idx ?>]" value="<?= htmlspecialchars($data['specialization']) ?>" style="width: 100%; padding: 4px; border: 1px solid var(--border); border-radius: 4px;"></td>
                                <td><input type="number" name="experience[<?= $idx ?>]" value="<?= htmlspecialchars($data['experience']) ?>" min="0" max="40" style="width: 80px; padding: 4px; border: 1px solid var(--border); border-radius: 4px;"></td>
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
                    <button type="submit" name="confirm_insert" class="btn">
                        <i data-feather="check-circle"></i>
                        <?= $is_retry ? 'Retry Insertion' : 'Confirm & Insert Selected Records' ?>
                    </button>
                </div>
            </form>
        </div>

        <script>
            document.getElementById('select_all').addEventListener('change', function() {
                document.querySelectorAll('.row-checkbox').forEach(cb => cb.checked = this.checked);
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
    </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
