<?php
  include "connect.php";
  if(isset($_POST["login"]))
  {
    $username = $_POST["user_email"] ?? "";
    $password = $_POST["password"] ?? "";
    if(empty($username))
    {
      $loi_user="Tên người dùng hoặc gmail không được để trống";
    }
    if(empty($password))
    {
      $loi_password="Mật khẩu không được để trống";
    }
    if(!empty($username) && !empty($password)){
      $sql="SELECT user_name,email,password FROM users WHERE user_name='$username' OR email='$username'";
      $result=mysqli_query($conn,$sql);
      if($row=mysqli_fetch_assoc($result))
        {
          if(password_verify($password, $row["password"])){

            session_start();

            $_SESSION["user_name"] = $row["user_name"];
            
            header("Location: trangchu.php");
            exit();
          }
          else
          {
            $wrong_login="Mật khẩu không đúng";
          }
        }
      else
        {
          $wrong_login="Tên người dùng hoặc email không tồn tại";
      }
    }
  }
        
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Đăng nhập</title>
  <link rel="stylesheet" href="css/dangky.css">
</head>
<body>

  
  <header class="logo_header">
    <a href="#"><img src="image/logo2.avif" alt="Logo Phong Cách Xanh" class="logo"></a>
  </header>

 
  <div class="nen_mo">
    <div class="hop_chinh_sua form_dangnhap">
      <h2 class="tieu_de">Đăng nhập tài khoản</h2>

  
      <form action="dangnhap.php" method="POST">
        <div class="cot_rong">
          <label> Tên tài khoản hoặc Gmail</label>
          <input type="text" name="user_email" placeholder="Nhập tài khoản hoặc gmail của bạn" class="<?php if(!empty($loi_user)||!empty($wrong_login)) echo"input_loi";?>">
           <p class="loi"><?php if(isset($loi_user)) echo $loi_user;if(isset($wrong_login)) echo $wrong_login; ?></p>
        </div>

        <div class="cot_rong">
          <label>Mật khẩu</label>
          <input type="password" name="password" placeholder="Nhập mật khẩu" class="<?php if(!empty($loi_password)||!empty($wrong_login)) echo"input_loi";?>">
          <p class="loi"><?php if(isset($loi_password)) echo $loi_password; if(isset($wrong_login)) echo $wrong_login; ?></p>
        </div>

        <button type="submit" class="nut_dangnhap" name="login">Đăng nhập</button>
      </form>

      <p class="ghi_chu">
        Chưa có tài khoản? <a href="dangky.php">Đăng ký ngay</a>
      </p>
    </div>
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