<?php
session_start();
if (!isset($_SESSION['admin_id'])) { header('Location: dangnhap.php'); exit(); }
require_once 'config.php';

$message = "";
if (isset($_POST['btn_save'])) {
    $user = mysqli_real_escape_string($conn, $_POST['username']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $pass = $_POST['password']; // Mật khẩu gốc

    // Kiểm tra trùng lặp username
    $check = mysqli_query($conn, "SELECT id FROM users WHERE user_name = '$user'");
    if (mysqli_num_rows($check) > 0) {
        $message = "Lỗi: Tên đăng nhập đã tồn tại!";
    } else {
        $sql = "INSERT INTO users (user_name, password, email, status) VALUES ('$user', '$pass', '$email', 1)";
        if (mysqli_query($conn, $sql)) {
            header('Location: quanlynguoidung.php');
            exit();
        } else {
            $message = "Lỗi hệ thống, không thể thêm người dùng.";
        }
    }
}

include 'thanhmenu.php';
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Thêm khách hàng | Private Space</title>
    <style>
        .main-content { padding: 40px; background: #0f172a; min-height: 100vh; display: flex; flex-direction: column; align-items: center; }
        .form-card { background: #1e293b; padding: 40px; border-radius: 16px; width: 100%; max-width: 500px; border: 1px solid #334155; }
        .form-group { margin-bottom: 20px; }
        label { display: block; color: #94a3b8; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; margin-bottom: 8px; letter-spacing: 1px; }
        input { width: 100%; padding: 12px; background: #0f172a; border: 1px solid #334155; border-radius: 8px; color: white; box-sizing: border-box; font-size: 1rem; }
        input:focus { outline: none; border-color: #3b82f6; box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.2); }
        .btn-submit { width: 100%; padding: 14px; background: #3b82f6; color: white; border: none; border-radius: 8px; font-weight: 700; cursor: pointer; margin-top: 10px; }
        .btn-submit:hover { background: #2563eb; }
        .btn-back { display: block; text-align: center; margin-top: 20px; color: #94a3b8; text-decoration: none; font-size: 0.9rem; }
        .btn-back:hover { color: white; }
    </style>
</head>
<body>
    <div class="main-content">
        <div class="form-card">
            <h2 style="margin: 0 0 30px 0; color: #3b82f6; text-align: center;">Tạo tài khoản mới</h2>
            
            <?php if($message): ?>
                <p style="color: #ef4444; background: rgba(239, 68, 68, 0.1); padding: 10px; border-radius: 5px; text-align: center; font-size: 0.85rem;"><?php echo $message; ?></p>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label>Tên đăng nhập</label>
                    <input type="text" name="username" required placeholder="Ví dụ: nva_2026">
                </div>
                <div class="form-group">
                    <label>Địa chỉ Email</label>
                    <input type="email" name="email" required placeholder="email@example.com">
                </div>
                <div class="form-group">
                    <label>Mật khẩu khởi tạo</label>
                    <input type="password" name="password" required placeholder="••••••••">
                </div>
                <button type="submit" name="btn_save" class="btn-submit">XÁC NHẬN THÊM MỚI</button>
            </form>
            <a href="quanlynguoidung.php" class="btn-back">← Quay lại danh sách</a>
        </div>
    </div>
</body>
</html>