<?php
session_start();
if (!isset($_SESSION['admin_id'])) { 
    header('Location: dangnhap.php'); 
    exit(); 
}
require_once 'config.php';

// Xử lý Reset mật khẩu về '123'
if (isset($_GET['reset'])) {
    $id = intval($_GET['reset']);
    mysqli_query($conn, "UPDATE users SET password = '123' WHERE id = $id");
    header('Location: quanlynguoidung.php');
}

// Xử lý Khóa/Mở tài khoản
if (isset($_GET['toggle'])) {
    $id = intval($_GET['toggle']);
    $st = intval($_GET['st']);
    $new_st = ($st == 1) ? 0 : 1;
    mysqli_query($conn, "UPDATE users SET status = $new_st WHERE id = $id");
    header('Location: quanlynguoidung.php');
}

$result = mysqli_query($conn, "SELECT * FROM users ORDER BY id DESC");
include 'thanhmenu.php';
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quản lý người dùng | Private Space</title>
    <style>
        .main-content { padding: 40px; background: #0f172a; min-height: 100vh; color: #f8fafc; }
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .btn-add { background: #3b82f6; color: white; padding: 12px 24px; border-radius: 8px; text-decoration: none; font-weight: 600; transition: 0.3s; }
        .btn-add:hover { background: #2563eb; transform: translateY(-2px); }
        
        table { width: 100%; border-collapse: collapse; background: #1e293b; border-radius: 12px; overflow: hidden; border: 1px solid #334155; }
        th { background: #334155; color: #94a3b8; text-align: left; padding: 15px; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 1px; }
        td { padding: 15px; border-bottom: 1px solid #334155; font-size: 0.95rem; }
        tr:hover { background: rgba(59, 130, 246, 0.05); }

        .status-pill { padding: 4px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 700; }
        .status-active { background: rgba(16, 185, 129, 0.1); color: #10b981; }
        .status-locked { background: rgba(239, 68, 68, 0.1); color: #ef4444; }

        .action-links a { color: #3b82f6; text-decoration: none; font-size: 0.85rem; margin-right: 15px; font-weight: 600; }
        .action-links a:hover { text-decoration: underline; }
        .btn-lock { color: #f59e0b !important; }
    </style>
</head>
<body>
    <div class="main-content">
        <div class="page-header">
            <div>
                <h1 style="margin:0; color: #3b82f6;">Quản lý khách hàng</h1>
                <p style="color: #94a3b8; margin: 5px 0 0 0;">Điều khiển tài khoản người dùng hệ thống</p>
            </div>
            <a href="themnguoidung.php" class="btn-add">+ THÊM TÀI KHOẢN</a>
        </div>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Tên đăng nhập</th>
                    <th>Mật khẩu</th>
                    <th>Email</th>
                    <th>Trạng thái</th>
                    <th>Hành động</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = mysqli_fetch_assoc($result)): ?>
                <tr>
                    <td>#<?php echo $row['id']; ?></td>
                    <td style="font-weight: 600;"><?php echo $row['user_name']; ?></td>
                    <td style="font-family: monospace; color: #fbbf24;"><?php echo $row['password']; ?></td>
                    <td style="color: #94a3b8;"><?php echo $row['email']; ?></td>
                    <td>
                        <?php if($row['status'] == 1): ?>
                            <span class="status-pill status-active">● HOẠT ĐỘNG</span>
                        <?php else: ?>
                            <span class="status-pill status-locked">● BỊ KHÓA</span>
                        <?php endif; ?>
                    </td>
                    <td class="action-links">
                        <a href="?reset=<?php echo $row['id']; ?>" onclick="return confirm('Đặt lại mật khẩu về 123?')">Khởi tạo lại</a>
                        <a href="?toggle=<?php echo $row['id']; ?>&st=<?php echo $row['status']; ?>" class="btn-lock">
                            <?php echo ($row['status'] == 1) ? "Khóa tài khoản" : "Mở tài khoản"; ?>
                        </a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</body>
</html>