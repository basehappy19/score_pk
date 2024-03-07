<?php
$host = "10.104.0.2";
$username = "score";
$password = "qwer";
$dbname = "pk_score_made_by_stu";
$port = 3306;

try {
    $conn = mysqli_connect($host, $username, $password, $dbname, $port);
    $conn->set_charset('utf8');
    if (!$conn) {
        die("เชื่อมต่อกับ database ไม่ได้" . mysqli_connect_error());
    }
} catch (\Throwable $th) {
    //throw $th;
    die('db fail');
    // die($th->getMessage());
}
