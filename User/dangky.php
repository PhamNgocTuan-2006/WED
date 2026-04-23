<?php
    session_start();
    include "connect.php";
    if(isset($_POST["login"]))
        {
            $username = $_POST["user_name"] ?? "";
            $password = $_POST["password"] ?? "";
            $email = $_POST["email"] ?? "";
            if(empty($username))
                {
                    $loi_user="Tên người dùng không được để trống";
                }
            if(empty($password))
                {
                    $loi_password="Mật khẩu không được để trống";
                }
            if(empty($email))
                {
                    $loi_email="Email không được để trống";
                }
            if(!empty($username) && !empty($password) && !empty($email)){
              $sql="SELECT user_name,email FROM users WHERE user_name='$username' OR email='$email'";
              $result=mysqli_query($conn,$sql);
             if( $row=mysqli_fetch_assoc($result))
                {
                  if($row["user_name"]==$username){
                    $exit_username="Tên người dùng đã tồn tại";
                  }
                  if($row["email"]==$email)
                    {
                      $exit_email="Email đã tồn tại";
                    }
                }
             else
                {
                      $password=password_hash($_POST["password"],PASSWORD_DEFAULT);
                      $sql="INSERT INTO users (user_name,password,email) VALUES ('$username','$password','$email')";
                      if(mysqli_query($conn,$sql))
                      {
                          /* tự động đăng nhập sau khi đăng ký */

                          $_SESSION["user_name"] = $username;

                          header("Location: diachidangki.php");
                          exit();
                      }
                      else 
                      {
                        echo "Lỗi: " . mysqli_error($conn);
                      }
                }
          }
        }    
        

            
?>

<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Đăng ký</title>
  <link rel="stylesheet" href="css/dangky.css">
</head>
<body>

  
  <header class="logo_header">
    <a href="#"><img src="image/logo2.avif" alt="Logo Phong Cách Xanh" class="logo"></a>
  </header>


  <div class="nen_mo">
    <div class="hop_chinh_sua form_dangky">
      <h2 class="tieu_de">Tạo tài khoản mới</h2>

      <form action="" method="POST">
        <div class="cot_rong">
          <label>Tên tài khoản</label>
          <input type="text" placeholder="Nhập tên của bạn" name="user_name" class="<?php if(!empty($loi_user)||!empty($exit_username)) echo"input_loi"; ?>">
          <p class="loi"><?php if(isset($loi_user)) echo $loi_user; if(isset($exit_username)) echo $exit_username; ?></p>
        </div>

        <div class="cot_rong">
          <label>Email</label>
          <input type="email" placeholder="Nhập email của bạn" name="email" class="<?php if(!empty($loi_email)||!empty($exit_email)) echo"input_loi";?>">
          <p class="loi"><?php if(isset($loi_email)) echo $loi_email;if(isset($exit_email)) echo $exit_email; ?></p>
        </div>

        <div class="cot_rong">
          <label>Mật khẩu</label>
          <input type="password" placeholder="Nhập mật khẩu" name="password"  class="<?php if(!empty($loi_password)) echo"input_loi";?>">
          <p class="loi"><?php if(isset($loi_password)) echo $loi_password;?></p>
        </div>

         <button type="submit" class="nut_dangnhap" name="login">Tiếp</button>
      </form>

      <p class="ghi_chu">
        Đã có tài khoản? <a href="dangnhap.php"> Đăng nhập ngay</a>
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