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
        $import_type = $_POST['import_type'];
        
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
                    
                    if ($import_type === 'students') {
                        // Process student data
                        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                            if (count($data) >= 5) { // Ensure we have required fields
                                $cid = trim($data[0]);
                                $fullname = trim($data[1]);
                                $school = trim($data[2]);
                                $reg_id = trim($data[3]);
                                $reg_type_label = trim($data[4]);
                                $note = isset($data[5]) ? trim($data[5]) : '';
                                
                                // Insert student data
                                $insert_sql = "INSERT INTO student (round_code, round_year, cid, fullname, `from`, reg_id, reg_type_label, note, created_at) 
                                              VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
                                $insert_stmt = mysqli_prepare($conn, $insert_sql);
                                mysqli_stmt_bind_param($insert_stmt, "sissssss", $round_code, $round_year, $cid, $fullname, $school, $reg_id, $reg_type_label, $note);
                                
                                if (mysqli_stmt_execute($insert_stmt)) {
                                    $success_count++;
                                } else {
                                    $error_count++;
                                }
                            } else {
                                $error_count++;
                            }
                        }
                    } else if ($import_type === 'scores') {
                        // Get all round attributes for this round
                        $attr_sql = "SELECT * FROM round_attr WHERE round_code = ? AND round_year = ?";
                        $attr_stmt = mysqli_prepare($conn, $attr_sql);
                        mysqli_stmt_bind_param($attr_stmt, "si", $round_code, $round_year);
                        mysqli_stmt_execute($attr_stmt);
                        $attr_result = mysqli_stmt_get_result($attr_stmt);
                        
                        $attributes = [];
                        while ($attr = mysqli_fetch_assoc($attr_result)) {
                            $attributes[$attr['key']] = $attr;
                        }
                        
                        // Process score data
                        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                            if (count($data) >= 3) { // Ensure we have required fields
                                $cid = trim($data[0]);
                                
                                // Find student ID
                                $student_sql = "SELECT id FROM student WHERE cid = ? AND round_code = ? AND round_year = ?";
                                $student_stmt = mysqli_prepare($conn, $student_sql);
                                mysqli_stmt_bind_param($student_stmt, "ssi", $cid, $round_code, $round_year);
                                mysqli_stmt_execute($student_stmt);
                                $student_result = mysqli_stmt_get_result($student_stmt);
                                
                                if ($student = mysqli_fetch_assoc($student_result)) {
                                    $student_id = $student['id'];
                                    
                                    // Insert scores for each subject
                                    for ($i = 1; $i < count($data); $i++) {
                                        if (isset($header[$i]) && isset($attributes[$header[$i]])) {
                                            $attr_key = $header[$i];
                                            $score = floatval(trim($data[$i]));
                                            
                                            // Check if score already exists
                                            $check_score_sql = "SELECT id FROM student_score WHERE student_id = ? AND round_code = ? AND round_attr_key = ?";
                                            $check_score_stmt = mysqli_prepare($conn, $check_score_sql);
                                            mysqli_stmt_bind_param($check_score_stmt, "iss", $student_id, $round_code, $attr_key);
                                            mysqli_stmt_execute($check_score_stmt);
                                            $check_score_result = mysqli_stmt_get_result($check_score_stmt);
                                            
                                            if (mysqli_num_rows($check_score_result) > 0) {
                                                // Update existing score
                                                $score_id = mysqli_fetch_assoc($check_score_result)['id'];
                                                $update_sql = "UPDATE student_score SET score = ?, updated_at = NOW() WHERE id = ?";
                                                $update_stmt = mysqli_prepare($conn, $update_sql);
                                                mysqli_stmt_bind_param($update_stmt, "di", $score, $score_id);
                                                
                                                if (mysqli_stmt_execute($update_stmt)) {
                                                    $success_count++;
                                                } else {
                                                    $error_count++;
                                                }
                                            } else {
                                                // Insert new score
                                                $insert_sql = "INSERT INTO student_score (student_id, round_code, round_attr_key, score, created_at, updated_at) 
                                                              VALUES (?, ?, ?, ?, NOW(), NOW())";
                                                $insert_stmt = mysqli_prepare($conn, $insert_sql);
                                                mysqli_stmt_bind_param($insert_stmt, "issd", $student_id, $round_code, $attr_key, $score);
                                                
                                                if (mysqli_stmt_execute($insert_stmt)) {
                                                    $success_count++;
                                                } else {
                                                    $error_count++;
                                                }
                                            }
                                        }
                                    }
                                } else {
                                    $error_count++;
                                }
                            } else {
                                $error_count++;
                            }
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
    $template_type = $_GET['template'];
    
    // Prevent any output before headers
    ob_clean();
    
    // Prepare filename
    if ($template_type === 'students') {
        $filename = "students_template.csv";
    } else if ($template_type === 'scores') {
        if (isset($_GET['round_code']) && isset($_GET['round_year'])) {
            $round_code = $_GET['round_code'];
            $round_year = $_GET['round_year'];
            $filename = "scores_template_{$round_code}_{$round_year}.csv";
        } else {
            $filename = "scores_template.csv";
        }
    } else {
        $filename = "template.csv";
    }
    
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
    
    if ($template_type === 'students') {
        // Student template with explicit escape parameter
        fputcsv($output, ['เลขบัตรประชาชน', 'ชื่อ-นามสกุล', 'โรงเรียน', 'เลขประจำตัวสอบ', 'ประเภทที่สมัคร', 'หมายเหตุ'], ",", '"', "\\");
        fputcsv($output, ['1234567890123', 'นายตัวอย่าง ทดสอบ', 'โรงเรียนตัวอย่าง', '00001', 'ห้องเรียนพิเศษวิทยาศาสตร์-คณิตศาสตร์', ''], ",", '"', "\\");
        fputcsv($output, ['1234567890124', 'นางสาวทดสอบ สองคน', 'โรงเรียนมัธยม', '00002', 'ห้องเรียนพิเศษวิทยาศาสตร์-คณิตศาสตร์', 'ตัวอย่าง'], ",", '"', "\\");
    } else if ($template_type === 'scores') {
        // Get attributes for specific round
        if (isset($_GET['round_code']) && isset($_GET['round_year'])) {
            $round_code = $_GET['round_code'];
            $round_year = $_GET['round_year'];
            
            $attr_sql = "SELECT * FROM round_attr WHERE round_code = ? AND round_year = ?";
            $attr_stmt = mysqli_prepare($conn, $attr_sql);
            mysqli_stmt_bind_param($attr_stmt, "si", $round_code, $round_year);
            mysqli_stmt_execute($attr_stmt);
            $attr_result = mysqli_stmt_get_result($attr_stmt);
            
            $header = ['เลขบัตรประชาชน'];
            $example1 = ['1234567890123'];
            $example2 = ['1234567890124'];
            
            while ($attr = mysqli_fetch_assoc($attr_result)) {
                $header[] = $attr['key'];
                $example1[] = rand(50, 100); // Example score between 50-100
                $example2[] = rand(50, 100); // Example score between 50-100
            }
            
            fputcsv($output, $header, ",", '"', "\\");
            fputcsv($output, $example1, ",", '"', "\\");
            fputcsv($output, $example2, ",", '"', "\\");
        } else {
            // Generic template if no round is provided
            fputcsv($output, ['เลขบัตรประชาชน', 'sci', 'math', 'eng'], ",", '"', "\\");
            fputcsv($output, ['1234567890123', '85', '75', '90'], ",", '"', "\\");
            fputcsv($output, ['1234567890124', '78', '88', '65'], ",", '"', "\\");
        }
    }
    
    fclose($output);
    exit;
}

// Function to get attributes for a specific round
function getAttributes($conn, $round_code, $round_year) {
    $attr_sql = "SELECT * FROM round_attr WHERE round_code = ? AND round_year = ?";
    $attr_stmt = mysqli_prepare($conn, $attr_sql);
    mysqli_stmt_bind_param($attr_stmt, "si", $round_code, $round_year);
    mysqli_stmt_execute($attr_stmt);
    return mysqli_stmt_get_result($attr_stmt);
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
                            <label for="import_type" class="form-label">ประเภทข้อมูล</label>
                            <select name="import_type" id="import_type" class="form-select" required>
                                <option value="">--- เลือกประเภทข้อมูล ---</option>
                                <option value="students">ข้อมูลนักเรียน</option>
                                <option value="scores">ข้อมูลคะแนน</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="round_code" class="form-label">รอบการสมัคร</label>
                            <select name="round_code" id="round_code" class="form-select" required>
                                <option value="">--- เลือกรอบการสมัคร ---</option>
                                <?php while ($round = mysqli_fetch_assoc($rounds)): ?>
                                    <option value="<?php echo $round['code']; ?>" data-year="<?php echo $round['year']; ?>">
                                        <?php echo $round['label']; ?> ปีการศึกษา <?php echo $round['year']; ?>
                                    </option>
                                <?php endwhile; mysqli_data_seek($rounds, 0); ?>
                            </select>
                        </div>
                        
                        <input type="hidden" name="round_year" id="round_year" value="">
                        
                        <div class="mb-3">
                            <label for="csv_file" class="form-label">ไฟล์ CSV</label>
                            <input type="file" name="csv_file" id="csv_file" class="form-control" accept=".csv" required>
                            <div class="form-text">โปรดอัปโหลดไฟล์ CSV ที่ถูกต้องตามรูปแบบที่กำหนด</div>
                        </div>
                        
                        <div class="template-links mb-3">
                            <p class="fw-bold">ดาวน์โหลดไฟล์ต้นแบบ:</p>
                            <a href="import.php?template=students" class="btn btn-back">
                                <i class="fa-solid fa-download"></i> ต้นแบบข้อมูลนักเรียน
                            </a>
                            <a href="#" id="scores_template_btn" class="btn btn-back">
                                <i class="fa-solid fa-download"></i> ต้นแบบข้อมูลคะแนน
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
        
        // Handle scores template download with selected round
        document.getElementById('scores_template_btn').addEventListener('click', function(e) {
            e.preventDefault();
            
            const roundCode = document.getElementById('round_code').value;
            const roundYear = document.getElementById('round_year').value;
            
            if (!roundCode) {
                Swal.fire({
                    icon: 'warning',
                    title: 'โปรดเลือกรอบการสมัคร',
                    text: 'คุณต้องเลือกรอบการสมัครก่อนดาวน์โหลดไฟล์ต้นแบบคะแนน'
                });
                return;
            }
            
            window.location.href = `import.php?template=scores&round_code=${roundCode}&round_year=${roundYear}`;
        });
    </script>
</body>
</html>