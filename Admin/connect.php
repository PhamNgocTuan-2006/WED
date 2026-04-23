<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "mydb"; // Tên database từ file sql của bạn

$conn = mysqli_connect($servername, $username, $password, $dbname);

if (!$conn) {
    die("Kết nối thất bại: " . mysqli_connect_error());
}

mysqli_set_charset($conn, "utf8"); // Đảm bảo hiển thị đúng tiếng Việt [cite: 24]
?>