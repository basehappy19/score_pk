<?php
include_once 'helper/server/db.php';
session_start();

if (isset($_GET['type']) && isset($_GET['year'])) {
    $code = $_GET['type'];
    $year = $_GET['year'];
    $sql = "SELECT * FROM round WHERE `code` = ? AND `year` = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "si", $code, $year);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($result) > 0) {
        $data = mysqli_fetch_assoc($result);
    } else {
        $_SESSION['error'] = '<div class="alert alert-danger">คุณใส่รหัสบัตรประชาชนผิด โปรดลองใหม่</div>';
        header("Location: ./");
    }
} else {
    $_SESSION['error'] = '<div class="alert alert-danger">คุณใส่รหัสบัตรประชาชนผิด โปรดลองใหม่</div>';
    header("Location: ./");
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ตรวจสอบคะแนนสอบ | โรงเรียนภูเขียว</title>
    <?php include 'helper/source/link.php' ?>
</head>

<body>
    <main>
        <section>
            <div class="container my-5">
                <div class="form-search">
                    <h4><?php echo $data['label_sec'] ?> ปีการศึกษา <?php echo $data['year'] ?></h4>
                    <hr>
                    <form action="info.php" method="get">
                        <label for="cid" class="form-label">ระบุเลขบัตรประชาชน</label>
                        <div class="input-group">
                            <input type="text" name="cid" class="form-control" id="cid" placeholder="เลขบัตรประชาชน" required>
                            <input type="hidden" name="type" value="<?php echo $data['code'] ?>">
                            <input type="hidden" name="round_year" value="<?php echo $data['year'] ?>">
                            <div class="input-group-append">
                                <span><button type="submit" id="left">เช็คเลย <i class="fa-solid fa-id-card"></i></button></span>
                            </div>
                        </div>
                    </form>
                </div>

            </div>
        </section>
        <?php include 'helper/source/footer.php' ?>

    </main>
</body>

</html>