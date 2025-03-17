<?php
$envFile = __DIR__ . '/../../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            $_ENV[$key] = $value;
            putenv("$key=$value");
        }
    }
}

$host = getenv('DB_HOST') ?: '127.0.0.1';
$username = getenv('DB_USERNAME') ?: 'root';
$password = getenv('DB_PASSWORD') ?: '';
$dbname = getenv('DB_NAME') ?: 'score';
$port = getenv('DB_PORT') ?: 3306;

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
