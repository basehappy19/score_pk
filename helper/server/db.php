<?php
$host = "localhost";
$username = "root";
$password = "";
$dbname = "score";
$port = 3306;
$conn = mysqli_connect($host, $username, $password, $dbname, $port);

if (!$conn) {
    die("เชื่อมต่อกับ database ไม่ได้" . mysqli_connect_error());
}
