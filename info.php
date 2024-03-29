<?php
include_once 'helper/server/db.php';
session_start();
if (isset($_GET['cid'])) {
    $cid = $_GET['cid'];
    $code = $_GET['type'];
    $round_year = $_GET['round_year'];
    $sql = "SELECT *
            FROM student_score
            LEFT JOIN student ON student_score.student_id = student.id  
            LEFT JOIN round_attr ON student_score.round_attr_key = round_attr.key AND round_attr.round_code = student.round_code
            LEFT JOIN round ON round_attr.round_code = round.code
            WHERE student.round_code = ? AND student.cid = ? AND student.round_year = ?";

    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "sii", $code, $cid, $round_year);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($result) > 0) {
        $data = mysqli_fetch_assoc($result);
    } else {
        $_SESSION['error'] = '<div class="alert alert-danger">คุณใส่รหัสบัตรประชาชนผิด โปรดลองใหม่</div>';
        header("Location: ./");
    }
} else {
    header("Location: ./");
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>คะแนนของ <?php echo $data['fullname'] ?> | โรงเรียนภูเขียว</title>
    <?php include 'helper/source/link.php' ?>
</head>

<body>
    <main>
        <section>
            <div class="container my-5">
                <div>
                    <h1 class="text-center mb-5 head-text animate__animated animate__zoomIn animate__faster">ประกาศคะแนนสอบ<?php echo $data['label'] ?> ปีการศึกษา <?php echo $data['year'] ?></h1>
                    <div class="content animate__animated animate__zoomIn animate__fast">
                        <h3 class="mb-3"><?php echo $data['fullname'] ?></h3>
                        <div class="mb-3">
                            <p class="info text h5"><strong><i class="fa-solid fa-graduation-cap"></i> รายการที่สมัคร : </strong><?php echo $data['reg_type_label'] ?></p>
                            <p class="info text h5"><strong><i class="fa-solid fa-user"></i> เลขประจำตัวผู้สอบ : </strong><?php echo $data['reg_id'] ?></p>
                            <p class="info text h5"><strong><i class="fa-solid fa-school"></i> โรงเรียน : </strong><?php echo $data['from'] ?></p>
                        </div>
                        <hr>
                        <table>
                            <tr>
                                <th colspan="2"><?php echo $data['label'] ?></th>
                            </tr>
                            <?php mysqli_data_seek($result, 0); ?>
                            <?php
                            $total_score = 0;
                            $total_max_score = 0;
                            while ($row = mysqli_fetch_assoc($result)) {
                                $total_score += $row['score'];
                                $total_max_score += $row['max_score'];
                            ?>
                                <tr>
                                    <td class="fw-bold"><?php echo $row['label_attr'] ?></td>
                                    <td class="score">
                                        <span class="score-text"><?php echo $row['score'] ?></span>
                                        <span class="max-score"> / <?php echo $row['max_score'] ?></span>
                                    </td>
                                </tr>
                            <?php
                            }
                            ?>
                            <tr>
                                <td colspan="2">
                                    <h3>รวมคะแนน : <span class="total-point"><?php echo $total_score ?> / <?php echo $total_max_score ?> <i class="fa-solid fa-ranking-star"></i></span></h3>
                                </td>
                            </tr>

                        </table>
                        <div class="mt-3 text-end">
                            <a href="./" class="btn btn-back">กลับหน้าหลัก <i class="fa-solid fa-house"></i></a>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <?php include 'helper/source/footer.php' ?>

    </main>
</body>

</html>