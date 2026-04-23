<?php
session_start();
require_once 'config.php';
if (!isset($_SESSION['admin_id'])) { header('Location: login.php'); exit(); }

// 1. Thêm tài khoản mới
if (isset($_POST['add_user'])) {
    $uname = mysqli_real_escape_string($conn, $_POST['u_name']);
    $uemail = mysqli_real_escape_string($conn, $_POST['u_email']);
    $upass = $_POST['u_pass'];
    mysqli_query($conn, "INSERT INTO users (user_name, password, email, status) VALUES ('$uname', '$upass', '$uemail', 1)");
    header('Location: user_manager.php');
}

// 2. Reset mật khẩu về '123'
if (isset($_GET['reset'])) {
    $id = intval($_GET['reset']);
    mysqli_query($conn, "UPDATE users SET password = '123' WHERE id = $id");
    header('Location: user_manager.php');
}

// 3. Khóa/Mở tài khoản
if (isset($_GET['toggle'])) {
    $id = intval($_GET['toggle']);
    $current_st = intval($_GET['st']);
    $new_st = ($current_st == 1) ? 0 : 1;
    mysqli_query($conn, "UPDATE users SET status = $new_st WHERE id = $id");
    header('Location: user_manager.php');
}

$res = mysqli_query($conn, "SELECT * FROM users");
?>
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"><title>Quản lý người dùng</title></head>
<body style="font-family:Arial; padding:30px; background:#f9f9f9;">
    <a href="index.php" style="text-decoration:none; color:#3498db;">← Quay lại Dashboard</a>
    <h2>QUẢN LÝ TÀI KHOẢN KHÁCH HÀNG</h2>

    <fieldset style="margin-bottom:20px; padding:15px; border:1px solid #ddd; border-radius:5px; background:#fff;">
        <legend>Thêm tài khoản khách hàng mới</legend>
        <form method="POST">
            <input type="text" name="u_name" placeholder="Tên đăng nhập" required>
            <input type="email" name="u_email" placeholder="Email" required>
            <input type="password" name="u_pass" placeholder="Mật khẩu" required>
            <button type="submit" name="add_user">Tạo tài khoản</button>
        </form>
    </fieldset>

    <table border="1" style="width:100%; border-collapse:collapse; background:#fff;">
        <tr style="background:#2c3e50; color:#fff;">
            <th>ID</th><th>Username</th><th>Email</th><th>Trạng thái</th><th>Hành động</th>
        </tr>
        <?php while($user = mysqli_fetch_assoc($res)): ?>
        <tr style="text-align:center;">
            <td><?php echo $user['id']; ?></td>
            <td><?php echo $user['user_name']; ?></td>
            <td><?php echo $user['email']; ?></td>
            <td><?php echo ($user['status'] == 1) ? "Đang hoạt động" : "<span style='color:red;'>Bị khóa</span>"; ?></td>
            <td>
                <a href="?reset=<?php echo $user['id']; ?>" onclick="return confirm('Reset mật khẩu về 123?')">Reset Pass</a> | 
                <a href="?toggle=<?php echo $user['id']; ?>&st=<?php echo $user['status']; ?>">
                    <?php echo ($user['status'] == 1) ? "Khóa tài khoản" : "Mở tài khoản"; ?>
                </a>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>
</body>
</html>