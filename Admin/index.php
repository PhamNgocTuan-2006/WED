<?php
session_start();
if (!isset($_SESSION['admin_id'])) { 
    header('Location: dangnhap.php'); 
    exit(); 
}
require_once 'config.php';

$tong_sp = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM san_pham"));
$tong_user = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM users"));
$tong_dm = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM danh_muc"));
$tong_dh = 0; 

include 'thanhmenu.php'; 
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Dashboard | PHONG CÁCH RIÊNG</title>
    <style>
        .main-content { padding: 40px; background-color: #0f172a; min-height: 100vh; color: #f8fafc; }
        .welcome-header { margin-bottom: 40px; border-bottom: 1px solid #1e293b; padding-bottom: 20px; }
        .welcome-header h1 { font-size: 2rem; margin: 0; color: #3b82f6; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 25px; margin-top: 20px; }
        .stat-card { background: #1e293b; padding: 25px; border-radius: 12px; border: 1px solid #334155; transition: 0.3s; }
        .stat-card:hover { transform: translateY(-5px); border-color: #3b82f6; }
        .stat-card h3 { margin: 0; font-size: 0.875rem; text-transform: uppercase; color: #94a3b8; letter-spacing: 1px; }
        .stat-card .value { font-size: 2.5rem; font-weight: 700; margin: 10px 0; display: block; }
        .stat-card .desc { font-size: 0.85rem; color: #4ade80; }
        .quick-actions { margin-top: 50px; background: rgba(59, 130, 246, 0.05); padding: 30px; border-radius: 16px; border: 1px dashed #334155; }
    </style>
</head>
<body>
<div class="main-content">
    <div class="welcome-header">
        <p style="color: #94a3b8; margin-bottom: 5px;">Hệ thống quản trị trung tâm</p>
        <h1>PHONG CÁCH RIÊNG - <span style="font-weight: 300;">Private Space</span></h1>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <h3>Sản phẩm</h3>
            <span class="value"><?php echo $tong_sp; ?></span>
            <span class="desc">📦 Linh kiện & Chuột</span>
        </div>
        <div class="stat-card">
            <h3>Khách hàng</h3>
            <span class="value"><?php echo $tong_user; ?></span>
            <span class="desc">👤 Đã đăng ký</span>
        </div>
        <div class="stat-card">
            <h3>Danh mục</h3>
            <span class="value"><?php echo $tong_dm; ?></span>
            <span class="desc">📂 Phân loại thiết bị</span>
        </div>
        <div class="stat-card">
            <h3>Đơn hàng mới</h3>
            <span class="value"><?php echo $tong_dh; ?></span>
            <span class="desc" style="color: #fbbf24;">🕒 Chờ xử lý</span>
        </div>
    </div>

    <div class="quick-actions">
        <h2 style="font-size: 1.2rem; margin-top: 0;">Trạng thái hệ thống</h2>
        <p style="color: #94a3b8; font-size: 0.95rem; line-height: 1.6;">
            Quản trị viên: <strong><?php echo $_SESSION['admin_fullname']; ?></strong>. Hệ thống vận hành ổn định. 
            [cite_start]Cần lưu ý cập nhật <strong>giá vốn bình quân</strong> khi hoàn tất các phiếu nhập hàng mới[cite: 15].
        </p>
    </div>
</div>
</body>
</html>