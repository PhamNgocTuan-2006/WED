<?php
if (session_status() == PHP_SESSION_NONE) session_start();
require_once 'config.php'; 

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) { header("Location: sanpham.php?view=categories"); exit(); }

$msg = "";

/* ==========================================================
   1. XỬ LÝ LOGIC QUẢN LÝ KHUNG (KEYS)
   ========================================================== */

// 1.1. Cập nhật tên danh mục
if (isset($_POST['update_cat_name'])) {
    $name = mysqli_real_escape_string($conn, trim($_POST['cat_name']));
    if ($name != "") {
        $conn->query("UPDATE danh_muc SET name = '$name' WHERE id = $id");
        $msg = "Đã cập nhật tên danh mục thành công.";
    }
}

// 1.2. Thêm đầu mục thông số mới (Spec Key)
if (isset($_POST['add_key'])) {
    $k = mysqli_real_escape_string($conn, trim($_POST['new_key']));
    if($k != "") {
        $conn->query("INSERT INTO danhmuc_spec_keys (category_id, spec_key) VALUES ($id, '$k')");
        $msg = "Đã thêm loại thông số '$k'.";
    }
}

// 1.3. Xóa đầu mục thông số
if (isset($_GET['del_key'])) {
    $conn->query("DELETE FROM danhmuc_spec_keys WHERE id = " . (int)$_GET['del_key']);
    header("Location: suadanhmuc.php?id=$id"); exit();
}

/* ==========================================================
   2. TRUY VẤN DỮ LIỆU HIỂN THỊ
   ========================================================== */
$cat = $conn->query("SELECT * FROM danh_muc WHERE id = $id")->fetch_assoc();
$spec_keys = $conn->query("SELECT * FROM danhmuc_spec_keys WHERE category_id = $id ORDER BY id ASC");

include 'thanhmenu.php';
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8"><title>Quản lý Danh mục | Private Space</title>
    <style>
        .container { max-width: 1000px; margin: 40px auto; padding: 0 20px; }
        .card { background: var(--bg-card); border-radius: 20px; padding: 30px; border: 1px solid rgba(255,255,255,0.05); margin-bottom: 25px; }
        label { display: block; color: var(--text-dim); font-size: 0.7rem; font-weight: 700; text-transform: uppercase; margin-bottom: 10px; }
        input[type="text"] { width: 100%; padding: 12px; background: #000; border: 1px solid #334155; color: white; border-radius: 10px; box-sizing: border-box; }
        
        .spec-item { background: rgba(59, 130, 246, 0.02); border-radius: 15px; padding: 25px; border-left: 4px solid var(--accent-blue); margin-bottom: 20px; }
        .badge { background: #0f172a; color: #94a3b8; padding: 6px 12px; border-radius: 8px; font-size: 0.8rem; margin-right: 5px; margin-top: 5px; display: inline-block; }
        
        .btn-save { background: #10b981; color: white; border: none; padding: 12px 25px; border-radius: 10px; font-weight: 700; cursor: pointer; }
        .btn-add { background: var(--accent-blue); color: white; border: none; padding: 0 20px; border-radius: 10px; font-weight: 700; cursor: pointer; }
        
        /* Style cho dòng hướng dẫn */
        .guide-box { background: rgba(251, 191, 36, 0.05); border: 1px solid rgba(251, 191, 36, 0.2); border-radius: 15px; padding: 20px; margin-bottom: 25px; }
        .guide-text { color: #fbbf24; margin: 0; font-size: 0.9rem; line-height: 1.5; }
    </style>
</head>
<body>
    <div class="container">
        <h1 style="color:var(--accent-blue);">⚙️ Quản lý: <?= $cat['name'] ?></h1>
        <?php if($msg): ?> <div style="color:#10b981; margin-bottom:20px; font-weight:700;">✓ <?= $msg ?></div> <?php endif; ?>

        <div class="guide-box">
            <p class="guide-text">
                💡 <b>Lưu ý:</b> Tại đây bạn chỉ quản lý <b>tên các đầu mục</b> thông số. <br>
                Để thêm các <b>giá trị cụ thể</b> (Ví dụ: 8000Hz, Blue Switch...), bạn hãy nhập trực tiếp khi <b>Thêm sản phẩm mới</b> hoặc <b>Sửa thông tin sản phẩm</b>. Hệ thống sẽ tự động cập nhật các giá trị đó vào bộ lọc ngoài trang chủ.
            </p>
        </div>

        <div class="card">
            <form method="POST">
                <label>Tên phân loại </label>
                <div style="display:flex; gap:10px;">
                    <input type="text" name="cat_name" value="<?= htmlspecialchars($cat['name']) ?>" required>
                </div>
            </form>
        </div>

        <div class="card" style="border: 1px dashed var(--accent-blue);">
            <form method="POST">
                <label>+ Thêm đầu mục thông số mới (VD: DPI, Switch, Layout...)</label>
                <div style="display:flex; gap:10px;">
                    <input type="text" name="new_key" placeholder="Nhập tên đầu mục..." required>
                    <button type="submit" name="add_key" class="btn-add">THÊM MỤC</button>
                </div>
            </form>
        </div>

        <h3 style="color:white; font-size:1rem; margin-bottom:15px;">📋 Cấu trúc & Giá trị lọc thực tế</h3>
        <?php while($sk = $spec_keys->fetch_assoc()): 
            $kn = $sk['spec_key'];
            // Truy vấn lấy các giá trị ĐANG CÓ THỰC TẾ trong kho sản phẩm
            $vals = $conn->query("SELECT DISTINCT spec_value FROM thong_so WHERE spec_key = '$kn' AND spec_value != ''");
        ?>
            <div class="spec-item">
                <div style="display:flex; justify-content:space-between; align-items:center;">
                    <b style="color:var(--accent-blue);"><?= strtoupper($kn) ?></b>
                    <a href="?id=<?= $id ?>&del_key=<?= $sk['id'] ?>" style="color:var(--danger); font-size:0.75rem; text-decoration:none;" onclick="return confirm('Xóa loại thông số này?')">XÓA KHÓA</a>
                </div>
                
                <div style="margin-top:15px;">
                    <label style="margin-bottom:5px;">Các lựa chọn đang hiện ở bộ lọc:</label>
                    <?php if($vals->num_rows > 0): ?>
                        <?php while($v = $vals->fetch_assoc()): ?>
                            <span class="badge"><?= $v['spec_value'] ?></span>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <i style="color:var(--text-dim); font-size:0.8rem;">Chưa có sản phẩm nào được nhập giá trị này.</i>
                    <?php endif; ?>
                </div>
            </div>
        <?php endwhile; ?>

        <p style="text-align:center;"><a href="sanpham.php?view=categories" style="color:var(--text-dim); text-decoration:none; font-weight:700;">← QUAY LẠI DANH SÁCH</a></p>
    </div>
</body>
</html>