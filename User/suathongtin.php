<?php
session_start();
include "connect.php";

if (!isset($_SESSION["user_name"])) {
    header("Location: dangnhap.php");
    exit();
}

$user_name = $_SESSION["user_name"];

/* lấy thông tin user */

$sql = "SELECT * FROM users WHERE user_name='$user_name'";
$result = mysqli_query($conn,$sql);
$user = mysqli_fetch_assoc($result);



/* xử lý lưu */

if(isset($_POST["luu"])){

$email = $_POST["email"];
$ho = $_POST["ho"];
$ten = $_POST["ten"];

/* ghép thành tên mới */

$user_name_moi = $ho . " " . $ten;

/* cập nhật bảng users */

mysqli_query($conn,
"UPDATE users 
SET email='$email',
user_name='$user_name_moi'
WHERE id='".$user["id"]."'");

/* cập nhật session */

$_SESSION["user_name"] = $user_name_moi;

header("Location: thongtinkhachhang.php");
exit();

}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chỉnh sửa tài khoản</title>
    <link rel="stylesheet" href="css/suathongtin.css">
</head>

<body>

   
    <header class="logo_header">
        <a href="trangchu.php"><img src="image/logo2.avif" alt="Logo Phong Cách Xanh" class="logo"></a>
    </header>

   
    <div class="nen_mo">
        <form method="POST" class="hop_chinh_sua">
            <h2 class="tieu_de">Chỉnh sửa tài khoản</h2>

            <div class="hang_ten">

                <div class="cot">
                    <label>Họ</label>
                    <input type="text" name="ho" value="<?php echo $user_diachi['ho'] ?? ''; ?>">
                </div>

                <div class="cot">
                    <label>Tên</label>
                    <input type="text" name="ten" value="<?php echo explode(' ', $user['user_name'])[1] ?? ''; ?>">
                </div>
                
            </div>

            <div class="cot_rong">
                <label>Email</label>
                <input type="text" name="email" value="<?php echo $user["email"]; ?>">
                <p class="ghi_chu">Email này được sử dụng để đăng nhập và cập nhật đơn hàng của bạn.</p>
            </div>

            <div class="hang_nut">
                <a href="thongtinkhachhang.php" class="nut_huy">Hủy</a>
                <button type="submit" name="luu" class="nut_luu">Lưu
            </div>
        </form>
    </div>
    <footer class="c">
        <div class="f">
            <a>Chính sách hoàn tiền</a>
            <a>Vận chuyển</a>
            <a>Chính sách quyền riêng tư </a>
            <a>Điều khoản dịch vụ</a>
        </div>
    </footer>
</body>

</html>