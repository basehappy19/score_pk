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

    <main>
        <section>
            
            <div class="container my-5">
                <div class="text-center mb-3">
                    <img src="helper/img/pklogo.png" alt="logo" width="150px">
                </div>
                <div>
                    <h3 class="text-center mb-3 head-text">ตรวจสอบคะแนนสอบ โรงเรียนภูเขียว</h3>
                        <?php
                        if (isset($_SESSION['error'])) {
                            echo $_SESSION['error']; 
                            unset($_SESSION['error']);
                        }
                        ?>
                    <?php while ($row = mysqli_fetch_assoc($data)) : ?>
                        <div class="round me-auto mb-3">
                            
                            <h3 class="label-round"><?php echo $row['label'] ?> ปีการศึกษา <?php echo $row['year'] ?></h3>
                            <p class="des text"><?php echo $row['des'] ?></p>
                            <div class="text-end">
                                <a href="search?type=<?php echo $row['code'] ?>&year=<?php echo $row['year'] ?>" class="btn btn-score">เช็คคะแนน <i class="fa-solid fa-star"></i></a>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
            
        </section>
        <?php include 'helper/source/footer.php' ?>
    </main>
</body>

</html>