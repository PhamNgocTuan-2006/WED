<?php
if (session_status() == PHP_SESSION_NONE) session_start();
require_once 'config.php';

// 1. LẤY ID DANH MỤC TỪ URL
$cat_id = isset($_GET['cat_id']) ? intval($_GET['cat_id']) : 0;

if ($cat_id <= 0) {
    header("Location: sanpham.php?view=categories");
    exit();
}

// Lấy tên danh mục để hiển thị tiêu đề
$cat_res = $conn->query("SELECT name FROM danh_muc WHERE id = $cat_id");
$cat_name = ($cat_res->fetch_assoc())['name'] ?? 'Không xác định';

/* ==========================================================
   2. XỬ LÝ NGHIỆP VỤ (THÊM / XÓA)
   ========================================================== */

// 2.1. Thêm thông số mới
if (isset($_POST['btn_add_spec'])) {
    $spec_name = mysqli_real_escape_string($conn, $_POST['spec_name']);
    if (!empty($spec_name)) {
        $conn->query("INSERT INTO thong_so_danh_muc (category_id, spec_name) VALUES ($cat_id, '$spec_name')");
        header("Location: quanlythongso.php?cat_id=$cat_id&msg=added");
        exit();
    }
}

// 2.2. Xóa thông số
if (isset($_GET['del_spec'])) {
    $spec_id = intval($_GET['del_spec']);
    $conn->query("DELETE FROM thong_so_danh_muc WHERE id = $spec_id");
    header("Location: quanlythongso.php?cat_id=$cat_id&msg=deleted");
    exit();
}

/* ==========================================================
   3. TRUY VẤN DANH SÁCH THÔNG SỐ HIỆN TẠI
   ========================================================== */
$specs = $conn->query("SELECT * FROM thong_so_danh_muc WHERE category_id = $cat_id ORDER BY id ASC");

include 'thanhmenu.php';
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Thiết lập thông số | <?= $cat_name ?></title>
    <style>
        .container { padding: 30px; max-width: 800px; margin: 0 auto; }
        .back-link { display: inline-block; margin-bottom: 20px; color: var(--text-dim); text-decoration: none; font-size: 0.9rem; }
        .back-link:hover { color: var(--accent-blue); }

        .card { background: var(--bg-card); border-radius: 16px; padding: 25px; border: 1px solid rgba(255,255,255,0.05); }
        
        h2 { color: var(--accent-blue); margin-top: 0; font-size: 1.4rem; }
        .subtitle { color: var(--text-dim); font-size: 0.85rem; margin-bottom: 25px; }

        .form-add { display: flex; gap: 10px; margin-bottom: 30px; background: var(--bg-deep); padding: 15px; border-radius: 12px; }
        .form-add input { flex: 1; background: #000; border: 1px solid #334155; color: white; padding: 10px; border-radius: 8px; }
        .btn-add { background: var(--accent-blue); color: white; border: none; padding: 0 20px; border-radius: 8px; cursor: pointer; font-weight: 700; }

        .spec-list { list-style: none; padding: 0; }
        .spec-item { 
            display: flex; justify-content: space-between; align-items: center; 
            padding: 15px; border-bottom: 1px solid rgba(255,255,255,0.05);
            transition: 0.3s;
        }
        .spec-item:hover { background: rgba(255,255,255,0.02); }
        .spec-name { font-weight: 600; color: #e2e8f0; }
        .btn-del { color: var(--danger); text-decoration: none; font-size: 0.8rem; font-weight: 600; }
        
        .empty-state { text-align: center; padding: 40px; color: var(--text-dim); font-style: italic; }
    </style>
</head>
<body>
    <div class="container">
        <a href="sanpham.php?view=categories" class="back-link">← Quay lại Quản lý phân loại</a>

        <div class="card">
            <h2>⚙️ Thông số: <?= $cat_name ?></h2>
            <p class="subtitle">Định nghĩa các trường dữ liệu đặc trưng cho danh mục này.</p>

            <form method="POST" class="form-add">
                <input type="text" name="spec_name" placeholder="VD: Độ phân giải (DPI), Loại Switch, Polling Rate..." required>
                <button type="submit" name="btn_add_spec" class="btn-add">THÊM</button>
            </form>

            <ul class="spec-list">
                <?php if ($specs->num_rows > 0): ?>
                    <?php while($s = $specs->fetch_assoc()): ?>
                        <li class="spec-item">
                            <span class="spec-name"><?= $s['spec_name'] ?></span>
                            <a href="?cat_id=<?= $cat_id ?>&del_spec=<?= $s['id'] ?>" 
                               class="btn-del" 
                               onclick="return confirm('Xóa thông số này sẽ mất dữ liệu liên quan ở các sản phẩm cũ. Bạn chắc chắn?')">
                               Xóa
                            </a>
                        </li>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="empty-state">Danh mục này chưa có thông số đặc trưng nào.</div>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</body>
</html>