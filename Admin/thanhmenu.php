<?php
if (session_status() == PHP_SESSION_NONE) session_start();
?>
<style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');

    :root {
        --sidebar-width: 280px;
        --bg-deep: #0f172a;
        --bg-card: #1e293b;
        --accent-blue: #3b82f6;
        --text-bright: #f8fafc;
        --text-dim: #94a3b8;
        --danger: #ef4444;
        --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    body {
        margin: 0;
        padding-left: var(--sidebar-width);
        font-family: 'Inter', sans-serif;
        background-color: #020617;
        color: var(--text-bright);
    }

    .sidebar {
        width: var(--sidebar-width);
        height: 100vh;
        background: var(--bg-deep);
        border-right: 1px solid rgba(255, 255, 255, 0.05);
        position: fixed;
        left: 0;
        top: 0;
        display: flex;
        flex-direction: column;
        z-index: 1000;
    }

    /* Brand Section */
    .sidebar-header {
        padding: 32px 24px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.05);
    }

    .brand-title {
        font-size: 1.4rem; /* Tăng từ 1.25rem */
        font-weight: 700;
        letter-spacing: 1px;
        margin: 0;
        color: var(--text-bright);
    }

    .brand-subtitle {
        font-size: 0.85rem; /* Tăng từ 0.75rem */
        text-transform: uppercase;
        color: var(--accent-blue);
        letter-spacing: 2px;
        font-weight: 600;
    }

    /* User Profile */
    .user-profile {
        margin: 20px 24px;
        padding: 18px;
        background: var(--bg-card);
        border-radius: 12px;
        display: flex;
        align-items: center;
        gap: 15px;
    }

    .user-avatar {
        width: 45px;
        height: 45px;
        background: var(--accent-blue);
        border-radius: 12px;
        display: flex;
        justify-content: center;
        align-items: center;
        font-weight: 700;
        font-size: 1.2rem;
        color: white;
    }

    .user-info .name {
        font-size: 1rem; /* Tăng từ 0.875rem */
        font-weight: 600;
    }

    /* Menu Section - TRỌNG TÂM THAY ĐỔI */
    .sidebar-menu {
        list-style: none;
        padding: 0 16px;
        margin: 0;
        flex-grow: 1;
        overflow-y: auto;
    }

    .menu-label {
        font-size: 0.9rem; /* Tăng từ 0.7rem - Tiêu đề nhóm to rõ */
        font-weight: 700;
        color: var(--text-dim);
        text-transform: uppercase;
        margin: 30px 12px 15px;
        letter-spacing: 1.5px;
    }

    .sidebar-menu li a {
        display: flex;
        align-items: center;
        gap: 14px;
        padding: 14px 18px; /* Tăng padding để menu thoáng hơn */
        color: var(--text-dim);
        text-decoration: none;
        border-radius: 10px;
        font-size: 1.1rem; /* Tăng từ 0.935rem - Chữ to dễ đọc */
        font-weight: 500;
        transition: var(--transition);
    }

    .sidebar-menu li a:hover {
        background: rgba(255, 255, 255, 0.05);
        color: var(--text-bright);
        transform: translateX(5px); /* Hiệu ứng lướt nhẹ khi di chuột */
    }

    /* Active State */
    .sidebar-menu li.active a {
        background: var(--accent-blue);
        color: white;
        box-shadow: 0 4px 15px rgba(59, 130, 246, 0.4);
    }

    /* Footer Section (Logout) */
    .sidebar-footer {
        padding: 24px 16px;
        border-top: 1px solid rgba(255, 255, 255, 0.05);
    }

    .btn-logout {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 12px;
        padding: 14px;
        background: rgba(239, 68, 68, 0.1);
        color: var(--danger);
        text-decoration: none;
        border-radius: 10px;
        font-weight: 700;
        font-size: 1rem; /* Tăng cỡ chữ nút đăng xuất */
        transition: var(--transition);
    }

    .btn-logout:hover {
        background: var(--danger);
        color: white;
    }
</style>

<div class="sidebar">
    <div class="sidebar-header">
        <h1 class="brand-title">PHONG CÁCH RIÊNG</h1>
        <div class="brand-subtitle">Private Space</div>
    </div>

    <div class="user-profile">
        <div class="user-avatar">
            <?php echo substr($_SESSION['admin_fullname'] ?? 'A', 0, 1); ?>
        </div>
        <div class="user-info">
            <span class="name"><?php echo $_SESSION['admin_fullname'] ?? 'Administrator'; ?></span>
            <span class="status" style="font-size: 0.8rem; color: #10b981; display: flex; align-items: center; gap: 5px;">
                <span style="font-size: 12px;">●</span> Trực tuyến
            </span>
        </div>
    </div>

    <div class="menu-label">Hệ thống điều khiển</div>
    <ul class="sidebar-menu">
        <li><a href="quanlynguoidung.php">👤 Quản lý người dùng</a></li>
        <li><a href="sanpham.php">📦 Quản lý sản phẩm</a></li>
        <li><a href="QLnhaphang.php">📥 Quản lý phiếu nhập hàng</a></li>
        <li><a href="quanlygiaban.php">💰 Quản lý giá bán</a></li>
        <li><a href="quanlydonhang.php">🛒 Quản lý đơn đặt hàng</a></li>
        <li><a href="quanlytonkho.php">📊 Quản lý tồn kho & Báo cáo</a></li>
    </ul>

    <div class="sidebar-footer">
        <a href="dangxuat.php" class="btn-logout">
            <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
            </svg>
            ĐĂNG XUẤT HỆ THỐNG
        </a>
    </div>
</div>