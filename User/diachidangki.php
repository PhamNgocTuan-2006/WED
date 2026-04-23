<?php
session_start();
include "connect.php";

if (!isset($_SESSION["user_name"])) {
    header("Location: dangnhap.php");
    exit();
}
$loi_ten="";
$loi_ho="";
$loi_diachi="";
$loi_thanhpho="";
$loi_sdt="";

if(isset($_POST["luu"])){

if(empty($_POST["ten"]))
$loi_ten="Vui lòng nhập tên";

if(empty($_POST["ho"]))
$loi_ho="Vui lòng nhập họ";

if(empty($_POST["dia_chi"]))
$loi_diachi="Vui lòng nhập địa chỉ";

if(empty($_POST["thanh_pho"]))
$loi_thanhpho="Vui lòng nhập thành phố";

if(empty($_POST["dien_thoai"]))
$loi_sdt="Vui lòng nhập số điện thoại";

elseif(!preg_match("/^0[0-9]{9}$/",$_POST["dien_thoai"]))
$loi_sdt="Số điện thoại phải bắt đầu bằng 0 và đủ 10 số";


/* nếu không còn lỗi thì lưu database */

if($loi_ten=="" && $loi_ho=="" && $loi_diachi=="" && $loi_thanhpho=="" && $loi_sdt==""){

$user_name = $_SESSION["user_name"];

/* lấy user_id */

$sql_user = "SELECT id FROM users WHERE user_name='$user_name'";
$result_user = mysqli_query($conn,$sql_user);
$user = mysqli_fetch_assoc($result_user);

$user_id = $user["id"];

/* lấy dữ liệu form */

$ten = $_POST["ten"];
$ho = $_POST["ho"];
$dia_chi = $_POST["dia_chi"];
$thanh_pho = $_POST["thanh_pho"];
$dien_thoai = $_POST["dien_thoai"];

/* kiểm tra user đã có địa chỉ chưa */

$sql_check = "SELECT COUNT(*) as total FROM dia_chi WHERE user_id='$user_id'";
$result_check = mysqli_query($conn,$sql_check);
$row_check = mysqli_fetch_assoc($result_check);

/* nếu chưa có địa chỉ nào → tự động mặc định */

if($row_check["total"] == 0){
$mac_dinh = 1;
}
else{
$mac_dinh = isset($_POST["mac_dinh"]) ? 1 : 0;
}

/* nếu chọn mặc định thì bỏ mặc định cũ */

if($mac_dinh==1){
mysqli_query($conn,"UPDATE dia_chi SET mac_dinh=0 WHERE user_id='$user_id'");
}

/* insert địa chỉ */

$sql_insert = "INSERT INTO dia_chi
(user_id, ten, ho, dia_chi, thanh_pho, dien_thoai, mac_dinh)
VALUES
('$user_id','$ten','$ho','$dia_chi','$thanh_pho','$dien_thoai','$mac_dinh')";

mysqli_query($conn,$sql_insert);

/* quay về trang thông tin */

header("Location: trangchu.php");
exit();

}

}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Thêm địa chỉ</title>
<link rel="stylesheet" href="css/themdiachi.css">
<style>
.input_loi{
border:2px solid red;
}

.loi{
color:red;
font-size:14px;
margin-top:4px;
}
</style>
</head>

<body>



<header class="logo_header">
<a href="trangchu.php">
<img src="image/logo2.avif" class="logo">
</a>
</header>


<div class="hop_dia_chi">

<h2 class="tieu_de">Điền địa chỉ giao hàng</h2>

<form method="POST">


<div class="hang_ten">

<div>
<input type="text"
name="ho"
placeholder="Họ Người Nhận"
value="<?php echo $_POST['ho'] ?? ''; ?>"
class="<?php if($loi_ho!="") echo 'input_loi'; ?>">

<p class="loi"><?php echo $loi_ho; ?></p>
</div>

<div>
<input type="text"
name="ten"
placeholder="Tên Người Nhận"
value="<?php echo $_POST['ten'] ?? ''; ?>"
class="<?php if($loi_ten!="") echo 'input_loi'; ?>">

<p class="loi"><?php echo $loi_ten; ?></p>
</div>


</div>


<input type="text"
name="dia_chi"
placeholder="Địa chỉ"
value="<?php echo $_POST['dia_chi'] ?? ''; ?>"
class="<?php if($loi_diachi!="") echo 'input_loi'; ?>">

<p class="loi"><?php echo $loi_diachi; ?></p>




<input type="text"
name="thanh_pho"
placeholder="Thành phố"
value="<?php echo $_POST['thanh_pho'] ?? ''; ?>"
class="<?php if($loi_thanhpho!="") echo 'input_loi'; ?>">

<p class="loi"><?php echo $loi_thanhpho; ?></p>



<input type="text"
name="dien_thoai"
placeholder="Điện thoại"
value="<?php echo $_POST['dien_thoai'] ?? ''; ?>"
class="<?php if($loi_sdt!="") echo 'input_loi'; ?>">

<p class="loi"><?php echo $loi_sdt; ?></p>


<div class="hang_nut">

<button type="submit" name="luu" class="nut_luu">
Đăng kí
</button>

</div>

</form>

</div>


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