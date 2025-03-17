<?php
include_once 'helper/server/db.php';
session_start();
ob_start(); // Start output buffering to prevent header issues

// Define allowed round codes for dropdown
$sql = "SELECT * FROM round WHERE is_enabled = 1";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_execute($stmt);
$rounds = mysqli_stmt_get_result($stmt);

// Process CSV upload
if (isset($_POST['import'])) {
    // Validate if file was uploaded
    if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] != UPLOAD_ERR_OK) {
        $_SESSION['error'] = '<div class="alert alert-danger">กรุณาเลือกไฟล์ CSV</div>';
    } else {
        $round_code = $_POST['round_code'];
        $round_year = $_POST['round_year'];
        
        // Verify round exists
        $check_sql = "SELECT * FROM round WHERE code = ? AND year = ?";
        $check_stmt = mysqli_prepare($conn, $check_sql);
        mysqli_stmt_bind_param($check_stmt, "ss", $round_code, $round_year);
        mysqli_stmt_execute($check_stmt);
        $check_result = mysqli_stmt_get_result($check_stmt);
        
        if (mysqli_num_rows($check_result) === 0) {
            $_SESSION['error'] = '<div class="alert alert-danger">รอบการรับสมัครไม่ถูกต้อง</div>';
        } else {
            // Read CSV file
            $handle = fopen($_FILES['csv_file']['tmp_name'], "r");
            if ($handle !== FALSE) {
                // Skip the header row
                $header = fgetcsv($handle, 1000, ",");
                
                // Begin transaction
                mysqli_begin_transaction($conn);
                
                try {
                    $success_count = 0;
                    $error_count = 0;
                    
                    // Process integrated data format (students and scores together)
                    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                        if (count($data) >= 9) { // Ensure we have all required fields
                            $csv_round_code = trim($data[0]);
                            $csv_round_year = trim($data[1]);
                            $reg_id = trim($data[2]);
                            $student_name = trim($data[3]);
                            $student_cid = trim($data[4]);
                            $student_from = trim($data[5]);
                            $attr_eng = floatval(trim($data[6]));
                            $attr_math = floatval(trim($data[7]));
                            $attr_sci = floatval(trim($data[8]));
                            
                            // Use provided round code/year from CSV or fallback to form selection
                            $use_round_code = !empty($csv_round_code) ? $csv_round_code : $round_code;
                            $use_round_year = !empty($csv_round_year) ? $csv_round_year : $round_year;
                            
                            // Insert or update student data
                            $check_student_sql = "SELECT id FROM student WHERE cid = ? AND round_code = ? AND round_year = ?";
                            $check_student_stmt = mysqli_prepare($conn, $check_student_sql);
                            mysqli_stmt_bind_param($check_student_stmt, "ssi", $student_cid, $use_round_code, $use_round_year);
                            mysqli_stmt_execute($check_student_stmt);
                            $check_student_result = mysqli_stmt_get_result($check_student_stmt);
                            
                            if ($student = mysqli_fetch_assoc($check_student_result)) {
                                // Update existing student
                                $student_id = $student['id'];
                                $update_student_sql = "UPDATE student SET 
                                    fullname = ?, 
                                    `from` = ?,
                                    reg_id = ?,
                                    updated_at = NOW() 
                                    WHERE id = ?";
                                $update_student_stmt = mysqli_prepare($conn, $update_student_sql);
                                mysqli_stmt_bind_param($update_student_stmt, "sssi", $student_name, $student_from, $reg_id, $student_id);
                                
                                if (mysqli_stmt_execute($update_student_stmt)) {
                                    $success_count++;
                                } else {
                                    $error_count++;
                                }
                            } else {
                                // Insert new student
                                $insert_student_sql = "INSERT INTO student (
                                    round_code, round_year, cid, fullname, `from`, reg_id, created_at, updated_at
                                ) VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())";
                                $insert_student_stmt = mysqli_prepare($conn, $insert_student_sql);
                                mysqli_stmt_bind_param($insert_student_stmt, "sissss", $use_round_code, $use_round_year, 
                                    $student_cid, $student_name, $student_from, $reg_id);
                                
                                if (mysqli_stmt_execute($insert_student_stmt)) {
                                    $student_id = mysqli_insert_id($conn);
                                    $success_count++;
                                } else {
                                    $error_count++;
                                    continue; // Skip score insertion if student creation failed
                                }
                            }
                            
                            // Process scores
                            $attributes = [
                                'eng' => $attr_eng,
                                'math' => $attr_math,
                                'sci' => $attr_sci
                            ];
                            
                            foreach ($attributes as $attr_key => $score) {
                                // Check if attribute exists for this round
                                $check_attr_sql = "SELECT id FROM round_attr WHERE round_code = ? AND round_year = ? AND `key` = ?";
                                $check_attr_stmt = mysqli_prepare($conn, $check_attr_sql);
                                mysqli_stmt_bind_param($check_attr_stmt, "sis", $use_round_code, $use_round_year, $attr_key);
                                mysqli_stmt_execute($check_attr_stmt);
                                $check_attr_result = mysqli_stmt_get_result($check_attr_stmt);
                                
                                // If attribute doesn't exist, create it
                                if (mysqli_num_rows($check_attr_result) === 0) {
                                    $attr_label = ($attr_key == 'eng') ? 'อังกฤษ' : 
                                                 (($attr_key == 'math') ? 'คณิตศาสตร์' : 
                                                 (($attr_key == 'sci') ? 'วิทยาศาสตร์' : $attr_key));
                                    
                                    $insert_attr_sql = "INSERT INTO round_attr (`key`, round_code, round_year, label_attr, max_score, created_at) 
                                                      VALUES (?, ?, ?, ?, 100, NOW())";
                                    $insert_attr_stmt = mysqli_prepare($conn, $insert_attr_sql);
                                    mysqli_stmt_bind_param($insert_attr_stmt, "ssis", $attr_key, $use_round_code, $use_round_year, $attr_label);
                                    mysqli_stmt_execute($insert_attr_stmt);
                                }
                                
                                // Check if score already exists
                                $check_score_sql = "SELECT id FROM student_score WHERE student_id = ? AND round_code = ? AND round_attr_key = ?";
                                $check_score_stmt = mysqli_prepare($conn, $check_score_sql);
                                mysqli_stmt_bind_param($check_score_stmt, "iss", $student_id, $use_round_code, $attr_key);
                                mysqli_stmt_execute($check_score_stmt);
                                $check_score_result = mysqli_stmt_get_result($check_score_stmt);
                                
                                if (mysqli_num_rows($check_score_result) > 0) {
                                    // Update existing score
                                    $score_id = mysqli_fetch_assoc($check_score_result)['id'];
                                    $update_score_sql = "UPDATE student_score SET score = ?, updated_at = NOW() WHERE id = ?";
                                    $update_score_stmt = mysqli_prepare($conn, $update_score_sql);
                                    mysqli_stmt_bind_param($update_score_stmt, "di", $score, $score_id);
                                    
                                    if (mysqli_stmt_execute($update_score_stmt)) {
                                        $success_count++;
                                    } else {
                                        $error_count++;
                                    }
                                } else {
                                    // Insert new score
                                    $insert_score_sql = "INSERT INTO student_score (student_id, round_code, round_attr_key, score, created_at, updated_at) 
                                                      VALUES (?, ?, ?, ?, NOW(), NOW())";
                                    $insert_score_stmt = mysqli_prepare($conn, $insert_score_sql);
                                    mysqli_stmt_bind_param($insert_score_stmt, "issd", $student_id, $use_round_code, $attr_key, $score);
                                    
                                    if (mysqli_stmt_execute($insert_score_stmt)) {
                                        $success_count++;
                                    } else {
                                        $error_count++;
                                    }
                                }
                            }
                        } else {
                            $error_count++;
                        }
                    }
                    
                    // Commit transaction
                    mysqli_commit($conn);
                    
                    // Set success message
                    if ($success_count > 0) {
                        $_SESSION['success'] = "<div class='alert alert-success'>นำเข้าข้อมูลสำเร็จ $success_count รายการ" . 
                                              ($error_count > 0 ? ", ผิดพลาด $error_count รายการ" : "") . "</div>";
                    } else {
                        $_SESSION['error'] = "<div class='alert alert-danger'>ไม่สามารถนำเข้าข้อมูลได้</div>";
                    }
                    
                } catch (Exception $e) {
                    // Rollback transaction on error
                    mysqli_rollback($conn);
                    $_SESSION['error'] = "<div class='alert alert-danger'>เกิดข้อผิดพลาด: " . $e->getMessage() . "</div>";
                }
                
                fclose($handle);
            } else {
                $_SESSION['error'] = "<div class='alert alert-danger'>ไม่สามารถอ่านไฟล์ CSV ได้</div>";
            }
        }
    }
    
    // Redirect to refresh the page
    header("Location: import.php");
    exit;
}

// Generate CSV template
if (isset($_GET['template'])) {
    // Prevent any output before headers
    ob_clean();
    
    $filename = "score_template.csv";
    
    // Set headers for download
    header('Content-Description: File Transfer');
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    
    // Create output stream directly to output
    $output = fopen('php://output', 'w');
    
    // Write UTF-8 BOM
    fwrite($output, "\xEF\xBB\xBF");
    
    // Integrated template matching the provided CSV format
    fputcsv($output, ['round_code', 'round_year', 'reg_id', 'student_name', 'student_cid', 'student_from', 'attr_eng', 'attr_math', 'attr_sci'], ",", '"', "\\");
    fputcsv($output, ['gifted6711', '2568', '11001', 'เด็กชายตัวอย่าง ทดสอบ', '1234567890123', 'โรงเรียนตัวอย่าง', '75.50', '85.25', '90.00'], ",", '"', "\\");
    fputcsv($output, ['gifted6711', '2568', '11002', 'เด็กหญิงตัวอย่าง สองคน', '1234567890124', 'โรงเรียนมัธยม', '80.75', '70.50', '88.25'], ",", '"', "\\");
    
    fclose($output);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>นำเข้าข้อมูล CSV | โรงเรียนภูเขียว</title>
    <?php include 'helper/source/link.php' ?>
    <style>
        .import-container {
            max-width: 800px;
            margin: 50px auto;
        }
        .template-links {
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <ul class="background">
        <li></li>
        <li></li>
        <li></li>
        <li></li>
    </ul>
    <main>
        <section>
            <div class="container">
                <div class="content import-container animate__animated animate__zoomIn animate__fast">
                    <h3 class="text-center mb-3 head-text">นำเข้าข้อมูลจากไฟล์ CSV</h3>
                    
                    <?php if (isset($_SESSION['success'])): ?>
                        <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                    <?php endif; ?>
                    
                    <?php if (isset($_SESSION['error'])): ?>
                        <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                    <?php endif; ?>
                    
                    <form action="import.php" method="post" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="round_code" class="form-label">รอบการสมัคร</label>
                            <select name="round_code" id="round_code" class="form-select" required>
                                <option value="">--- เลือกรอบการสมัคร ---</option>
                                <?php while ($round = mysqli_fetch_assoc($rounds)): ?>
                                    <option value="<?php echo $round['code']; ?>" data-year="<?php echo $round['year']; ?>">
                                        <?php echo $round['des']; ?> ปีการศึกษา <?php echo $round['year']; ?>
                                    </option>
                                <?php endwhile; mysqli_data_seek($rounds, 0); ?>
                            </select>
                            <div class="form-text">หมายเหตุ: ใช้เป็นค่าเริ่มต้นในกรณีที่ข้อมูล CSV ไม่มีรหัสรอบหรือปีการศึกษา</div>
                        </div>
                        
                        <input type="hidden" name="round_year" id="round_year" value="">
                        
                        <div class="mb-3">
                            <label for="csv_file" class="form-label">ไฟล์ CSV</label>
                            <input type="file" name="csv_file" id="csv_file" class="form-control" accept=".csv" required>
                            <div class="form-text">โปรดอัปโหลดไฟล์ CSV ที่มีรูปแบบ: round_code, round_year, reg_id, student_name, student_cid, student_from, attr_eng, attr_math, attr_sci</div>
                        </div>
                        
                        <div class="template-links mb-3">
                            <p class="fw-bold">ดาวน์โหลดไฟล์ต้นแบบ:</p>
                            <a href="import.php?template=1" class="btn btn-back">
                                <i class="fa-solid fa-download"></i> ดาวน์โหลดไฟล์ต้นแบบ
                            </a>
                        </div>
                        
                        <div class="text-end mt-4">
                            <a href="./" class="btn btn-back me-2">
                                <i class="fa-solid fa-arrow-left"></i> กลับหน้าหลัก
                            </a>
                            <button type="submit" name="import" class="btn btn-score">
                                <i class="fa-solid fa-file-import"></i> นำเข้าข้อมูล
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </section>
        <?php include 'helper/source/footer.php' ?>
    </main>
    
    <script>
        // Update hidden year field when round changes
        document.getElementById('round_code').addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const year = selectedOption.getAttribute('data-year');
            document.getElementById('round_year').value = year;
        });
    </script>
</body>
</html>