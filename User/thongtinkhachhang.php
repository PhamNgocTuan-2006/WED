<?php
session_start();
include "connect.php";

/* xử lý đăng xuất */

if(isset($_GET["logout"]))
{
    session_unset();
    session_destroy();

    header("Location: index.php");
    exit();
}

if (!isset($_SESSION["user_name"])) {
    header("Location: dangnhap.php");
    exit();
}

$user_name = $_SESSION["user_name"];

/* Lấy thông tin user */
$sql_user = "SELECT * FROM users WHERE user_name='$user_name'";
$result_user = mysqli_query($conn, $sql_user);
$user = mysqli_fetch_assoc($result_user);

$user_id = $user["id"];

if(isset($_POST["xoa_dia_chi"])){

$id = $_POST["xoa_dia_chi"];

/* xóa địa chỉ */

mysqli_query($conn,
"DELETE FROM dia_chi WHERE id='$id' AND user_id='$user_id'");

header("Location: thongtinkhachhang.php");
exit();
}

/* Lấy địa chỉ của user */
$sql_diachi = "SELECT * FROM dia_chi 
               WHERE user_id='$user_id'
               ORDER BY mac_dinh DESC";

$result_diachi = mysqli_query($conn, $sql_diachi);

if(isset($_POST["set_mac_dinh"])){

$id = $_POST["set_mac_dinh"];

/* bỏ mặc định cũ */

mysqli_query($conn,
"UPDATE dia_chi SET mac_dinh=0 WHERE user_id='$user_id'");

/* đặt mặc định mới */

mysqli_query($conn,
"UPDATE dia_chi SET mac_dinh=1 WHERE id='$id'");

header("Location: thongtinkhachhang.php");
exit();
}

?>

<!DOCTYPE html>
<html lang="vi">

<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="css/thongtinkhachhang.css">
<link rel="stylesheet" href="css/bootstrap-icons.css">
<title>Thông tin khách hàng</title>

<style>

.block-dia-chi{
margin-bottom:20px;
}

.radio-mac-dinh{
display:block;
cursor:pointer;
padding:10px;
border-radius:6px;
transition:0.2s;
}

/* ẩn radio thật */

.radio-mac-dinh input{
display:none;
}

/* ô vuông */

.custom-radio{
width:16px;
height:16px;
border:2px solid #888;
display:inline-block;
margin-right:8px;
border-radius:3px;
position:relative;
}

/* khi chọn → nền xanh */

.radio-mac-dinh input:checked + .custom-radio{
background:#2b6cff;
border-color:#2b6cff;
}

/* dấu tích trắng */

.radio-mac-dinh input:checked + .custom-radio::after{
content:"✓";
color:white;
font-size:12px;
position:absolute;
top:-2px;
left:2px;
font-weight:bold;
}

/* khi chọn → xanh */

.radio-mac-dinh input:checked + .custom-radio{
background:#2b6cff;
border-color:#2b6cff;
}

/* nền xanh cả block */

.radio-mac-dinh input:checked ~ *{
background:transparent;
}

.radio-mac-dinh:has(input:checked){
background:#eef3ff;
}

/* chữ mặc định */

.label-macdinh{
color:#2b6cff;
font-size:13px;
margin-top:4px;
}

.btn-xoa{
background:none;
border:none;
color:#e53935;
font-size:14px;
cursor:pointer;
display:block;
margin-top:4px;
padding:0;
}

.btn-xoa:hover{
text-decoration:underline;
}

</style>

</head>

<body>

<header>
<nav class="menu">

<div class="containner1">

<div class="logo">
<a href="trangchu.php">
<img src="image/logo2.avif" class="logo-img">
</a>
</div>

<div class="words">
<a href="trangchu.php">Trang chủ</a>
<a href="lichsumuahang.php">Đơn hàng</a>
</div>

</div>

<div class="khachhang">
<a href="#"><?php echo $user["user_name"]; ?> ▾</a>
<ul class="dropdown-menu">
<li><a href="#">Thông tin khách hàng</a></li>
<li><a href="thongtinkhachhang.php?logout=true">Đăng xuất</a></li>
</ul>
</div>

</nav>
</header>

<main>

<div class="thongtin">
Thông tin khách hàng
</div>

<div class="ten">
<?php echo $user["user_name"]; ?>

<div class="them">
<i class="bi bi-plus"></i>
<a href="suathongtin.php">Sửa</a>
</div>

</div>

<div class="email">
Email<br>
<?php echo $user["email"]; ?>
</div>


<div class="thongtindiachi">

<div class="containner2">

<div class="diachi">
Địa chỉ
</div>

<div class="them">
<i class="bi bi-plus"></i>
<a href="themdiachi.php">Thêm</a>
</div>

</div>

</div>


<div class="macdinh">
Địa chỉ của bạn
</div>


<div class="infodiachi">

<?php
if(mysqli_num_rows($result_diachi) > 0){

while($row = mysqli_fetch_assoc($result_diachi)){
?>

<form method="POST" class="block-dia-chi">

<input type="hidden"
name="set_mac_dinh"
value="<?php echo $row["id"]; ?>">

<label class="radio-mac-dinh">

<input type="radio"
name="mac_dinh_radio"
onchange="this.form.submit()"
<?php if($row["mac_dinh"]==1) echo "checked"; ?>>

<span class="custom-radio"></span>

<b><?php echo $row["ho"]." ".$row["ten"]; ?></b><br>

<?php echo $row["dia_chi"]; ?><br>
<?php echo $row["thanh_pho"]; ?><br>
<?php echo $row["dien_thoai"]; ?><br>

<?php
if($row["mac_dinh"]==1){
echo "<div class='label-macdinh'>Địa chỉ mặc định</div>";
}
?>

</label>

<button type="submit"
name="xoa_dia_chi"
value="<?php echo $row["id"]; ?>"
class="btn-xoa"
onclick="return confirm('Bạn có chắc muốn xóa địa chỉ này không?');">
Xóa địa chỉ
</button>

</form>

<?php
}

}else{
echo "Chưa có địa chỉ nào.";
}
?>

</div>

</main>


<footer class="c">
<div class="f">
<a>Chính sách hoàn tiền</a>
<a>Vận chuyển</a>
<a>Chính sách quyền riêng tư</a>
<a>Điều khoản dịch vụ</a>
</div>
</footer>

</body>
</html>