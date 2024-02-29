<?php
include_once 'helper/server/db.php';
session_start();

$sql = "SELECT * FROM round WHERE is_enabled = 1";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_execute($stmt);
$data = mysqli_stmt_get_result($stmt);
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
    <ul class="background">
        <li></li>
        <li></li>
        <li></li>
        <li></li>
    </ul>
    <main>
        <section>
            <div class="container my-5">
                <div class="text-center mb-3 animate__animated animate__zoomIn animate__faster">
                    <img src="helper/img/pklogo.png" alt="logo" width="150px">
                </div>
                <div>
                    <h3 class="text-center mb-3 head-text animate__animated animate__zoomIn animate__faster">ตรวจสอบคะแนนสอบ โรงเรียนภูเขียว</h3>
                    <?php
                    if (isset($_SESSION['error'])) {
                        echo $_SESSION['error'];
                        unset($_SESSION['error']);
                    }
                    ?>
                    <?php while ($row = mysqli_fetch_assoc($data)) : ?>
                        <div class="round me-auto mb-3 animate__animated animate__zoomIn animate__fast">
                            <?php if ($row['is_new'] == 1) : ?>
                                <div class="new-round animate__animated animate__pulse animate__fast animate__infinite">NEW!!</div>
                            <?php endif ?>
                            <h3 class="label-round"><?php echo $row['label'] ?> ปีการศึกษา <?php echo $row['year'] ?></h3>
                            <p class="des text"><?php echo $row['des'] ?></p>
                            <div class="text-end">
                                <a href="search.php?type=<?php echo $row['code'] ?>&year=<?php echo $row['year'] ?>" class="btn btn-score">เช็คคะแนน <i class="fa-solid fa-star"></i></a>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
                <div class="mt-3 text-center animate__animated animate__zoomIn animate__fast">
                    <a href="https://regis.phukhieo.ac.th" target="_blank" class="btn mx-3 btn-regis">ระบบรับสมัครนักเรียน <span><img src="helper/icon/register.png" alt="register" srcset=""></span></a>
                    <a href="https://enroll.phukhieo.ac.th" target="_blank" class="btn btn-enroll">รายงานตัวและมอบตัว <span><img src="helper/icon/registered.png" alt="enroll"></span></a>
                </div>
            </div>

        </section>
        <?php include 'helper/source/footer.php' ?>
    </main>
</body>

</html>