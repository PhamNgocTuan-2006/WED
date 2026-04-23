<?php
if (session_status() == PHP_SESSION_NONE) session_start();
require_once 'config.php'; 

// Lấy view hiện tại: products, categories, hoặc brands
$view = $_GET['view'] ?? 'products';

/* ==========================================================
   1. XỬ LÝ NGHIỆP VỤ (THÊM/XÓA)
   ========================================================== */

// 1.1. Xóa Sản phẩm
if (isset($_GET['delete_prod'])) {
    $id = intval($_GET['delete_prod']);
    // Xóa ở bảng phụ trước để tránh lỗi khóa ngoại nếu có
    $conn->query("DELETE FROM giaca WHERE product_id = $id");
    $conn->query("DELETE FROM thong_so WHERE product_id = $id");
    $conn->query("DELETE FROM san_pham WHERE id = $id");
    header("Location: sanpham.php?view=products"); exit();
}

// 1.2. Xóa Danh mục
if (isset($_GET['del_cat'])) {
    $cat_id = intval($_GET['del_cat']);
    $check = $conn->query("SELECT id FROM san_pham WHERE category_id = $cat_id LIMIT 1");
    if ($check->num_rows > 0) {
        echo "<script>alert('LỖI: Danh mục đang có sản phẩm!'); window.location.href='sanpham.php?view=categories';</script>";
    } else {
        $conn->query("DELETE FROM danh_muc WHERE id = $cat_id");
        header("Location: sanpham.php?view=categories");
    }
    exit();
}

// 1.3. Thêm Thương hiệu mới
if (isset($_POST['btn_add_brand'])) {
    $bname = mysqli_real_escape_string($conn, trim($_POST['brand_name']));
    if ($bname != "") {
        $conn->query("INSERT INTO thuong_hieu (name) VALUES ('$bname')");
        header("Location: sanpham.php?view=brands");
    }
    exit();
}

// 1.4. Xóa Thương hiệu
if (isset($_GET['del_brand'])) {
    $bid = intval($_GET['del_brand']);
    $check = $conn->query("SELECT id FROM san_pham WHERE brand_id = $bid LIMIT 1");
    if ($check->num_rows > 0) {
        echo "<script>alert('LỖI: Thương hiệu đang có sản phẩm!'); window.location.href='sanpham.php?view=brands';</script>";
    } else {
        $conn->query("DELETE FROM thuong_hieu WHERE id = $bid");
        header("Location: sanpham.php?view=brands");
    }
    exit();
}

/* ==========================================================
   2. TRUY VẤN DỮ LIỆU HIỂN THỊ
   ========================================================== */
$keyword = $_GET['keyword'] ?? "";
$where = !empty($keyword) ? "WHERE s.name LIKE '%$keyword%' OR g.product_code LIKE '%$keyword%'" : "";

// SỬA LỖI CẬP NHẬT SỐ LƯỢNG: Ưu tiên lấy stock từ bảng giaca (Dữ liệu gốc)
$products = $conn->query("SELECT s.*, g.product_code, g.cost_price, g.profit_margin, 
                                 COALESCE(g.stock, 0) as real_stock, 
                                 d.name as cat_name 
                          FROM san_pham s 
                          LEFT JOIN giaca g ON s.id = g.product_id 
                          LEFT JOIN danh_muc d ON s.category_id = d.id 
                          $where ORDER BY s.id DESC");

// Truy vấn Danh mục
$categories = $conn->query("SELECT d.*, (SELECT GROUP_CONCAT(spec_key SEPARATOR ', ') FROM danhmuc_spec_keys WHERE category_id = d.id) as keys_list FROM danh_muc d ORDER BY d.id ASC");

// Truy vấn Thương hiệu
$brands = $conn->query("SELECT * FROM thuong_hieu ORDER BY id ASC");

include 'thanhmenu.php';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Hệ thống Quản lý Kho</title>
    <style>
        :root {
            --accent-blue: #3b82f6;
            --bg-card: #111827;
            --bg-deep: #000000;
            --text-dim: #94a3b8;
            --danger: #ef4444;
            --success: #10b981;
        }

        body { background: var(--bg-deep); color: white; font-family: 'Inter', sans-serif; margin: 0; }
        .content-wrapper { padding: 30px; max-width: 1400px; margin: 0 auto; }
        
        .tab-nav { display: flex; gap: 10px; margin-bottom: 25px; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 15px; }
        .tab-link { padding: 12px 24px; border-radius: 10px; text-decoration: none; color: var(--text-dim); font-weight: 600; transition: 0.3s; }
        .tab-link.active { background: var(--accent-blue); color: white; }

        .main-card { background: var(--bg-card); border-radius: 16px; border: 1px solid rgba(255,255,255,0.05); padding: 25px; }
        .table-ui { width: 100%; border-collapse: collapse; }
        .table-ui th { text-align: left; color: var(--text-dim); padding: 15px; font-size: 0.75rem; text-transform: uppercase; border-bottom: 2px solid rgba(255,255,255,0.05); }
        .table-ui td { padding: 15px; border-bottom: 1px solid rgba(255,255,255,0.05); color: white; font-size: 0.9rem; vertical-align: middle; }

        .prod-img { width: 50px; height: 50px; border-radius: 8px; object-fit: cover; border: 1px solid rgba(255,255,255,0.1); display: block; background: #222; }

        .action-btn { color: var(--accent-blue); text-decoration: none; font-weight: 700; margin-right: 15px; }
        .action-btn.del { color: var(--danger); }
        .key-badge { background: rgba(59, 130, 246, 0.1); color: var(--accent-blue); padding: 2px 8px; border-radius: 4px; font-size: 0.75rem; margin-right: 5px; }
        
        .btn-main { background: var(--success); color: white; padding: 12px 25px; border-radius: 10px; text-decoration: none; font-weight: 700; border: none; cursor: pointer; }
        .input-ui { padding: 12px; background: var(--bg-deep); border: 1px solid #334155; color: white; border-radius: 10px; }
    </style>
</head>
<body>
    <div class="content-wrapper">
        <div class="tab-nav">
            <a href="?view=products" class="tab-link <?= $view == 'products' ? 'active' : '' ?>">📦 SẢN PHẨM</a>
            <a href="?view=categories" class="tab-link <?= $view == 'categories' ? 'active' : '' ?>">🗂️ PHÂN LOẠI</a>
            <a href="?view=brands" class="tab-link <?= $view == 'brands' ? 'active' : '' ?>">🏷️ THƯƠNG HIỆU</a>
        </div>

        <div class="main-card">
            <?php if ($view == 'products'): ?>
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:25px;">
                    <form method="GET" style="display:flex; gap:10px;">
                        <input type="hidden" name="view" value="products">
                        <input name="keyword" class="input-ui" value="<?= htmlspecialchars($keyword) ?>" placeholder="Tìm tên hoặc mã..." style="width:300px;">
                        <button type="submit" class="btn-main" style="background:var(--accent-blue);">LỌC</button>
                    </form>
                    <a href="themsanpham.php" class="btn-main">+ THÊM LINH KIỆN</a>
                </div>
                <table class="table-ui">
                    <thead><tr><th>Ảnh</th><th>Mã & Tên</th><th>Loại</th><th>Giá</th><th>Kho</th><th>Thao tác</th></tr></thead>
                    <tbody>
                        <?php while($r = $products->fetch_assoc()): 
                            $sell = $r['cost_price'] * (1 + $r['profit_margin']/100);
                            
                            $img_path = "image/" . $r['main_image'];
                            if (empty($r['main_image']) || !file_exists($img_path)) {
                                $display_img = "image/anhgoc.png";
                            } else {
                                $display_img = $img_path;
                            }
                        ?>
                        <tr>
                            <td><img src="<?= $display_img ?>" class="prod-img" alt="Product Image"></td>
                            <td><div style="font-weight:700;"><?= $r['name'] ?></div><small style="color:var(--accent-blue);"><?= $r['product_code'] ?></small></td>
                            <td><?= $r['cat_name'] ?></td>
                            <td style="color:#fbbf24; font-weight:700;"><?= number_format($sell) ?> đ</td>
                            <td><b style="color:<?= $r['real_stock'] > 0 ? 'white' : 'var(--danger)' ?>;"><?= $r['real_stock'] ?></b></td>
                            <td>
                                <a href="suasanpham.php?id=<?= $r['id'] ?>" class="action-btn">Sửa</a>
                                <a href="?delete_prod=<?= $r['id'] ?>" class="action-btn del" onclick="return confirm('Xóa linh kiện này?')">Xóa</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>

            <?php elseif ($view == 'categories'): ?>
                <div style="margin-bottom:25px;">
                    <h3 style="color:var(--accent-blue);">🗂️ CÁC LOẠI HÀNG HIỆN CÓ</h3>
                </div>
                <table class="table-ui">
                    <thead><tr><th>ID</th><th>Tên Danh Mục</th><th>Bộ khung thông số</th><th style="text-align:right;">Thao tác</th></tr></thead>
                    <tbody>
                        <?php while($c = $categories->fetch_assoc()): ?>
                        <tr>
                            <td>#<?= $c['id'] ?></td>
                            <td><div style="font-weight:700; font-size:1.1rem;"><?= $c['name'] ?></div></td>
                            <td>
                                <?php 
                                    if($c['keys_list']) {
                                        foreach(explode(', ', $c['keys_list']) as $k) echo "<span class='key-badge'>$k</span>";
                                    } else echo "<small style='color:var(--text-dim);'>Chưa cài đặt</small>";
                                ?>
                            </td>
                            <td style="text-align:right;">
                                <a href="suadanhmuc.php?id=<?= $c['id'] ?>" class="action-btn" style="color:var(--success);">⚙️ Thiết lập</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>

            <?php else: ?>
                <div style="margin-bottom:25px;">
                    <h3 style="color:var(--accent-blue);">🏷️ QUẢN LÝ THƯƠNG HIỆU</h3>
                </div>
                <div class="main-card" style="background:rgba(59, 130, 246, 0.02); border:1px dashed var(--accent-blue); margin-bottom:30px;">
                    <form method="POST" style="display:flex; gap:10px; align-items:flex-end;">
                        <div style="flex:1;">
                            <label style="display:block; color:var(--text-dim); font-size:0.7rem; font-weight:700; margin-bottom:8px;">TÊN THƯƠNG HIỆU MỚI</label>
                            <input type="text" name="brand_name" placeholder="Ví dụ: Logitech, Razer..." class="input-ui" required style="width:100%;">
                        </div>
                        <button type="submit" name="btn_add_brand" class="btn-main" style="padding:12px 30px;">+ THÊM MỚI</button>
                    </form>
                </div>
                <table class="table-ui">
                    <thead><tr><th>ID</th><th>Tên Thương Hiệu</th><th style="text-align:right;">Thao tác</th></tr></thead>
                    <tbody>
                        <?php while($b = $brands->fetch_assoc()): ?>
                        <tr>
                            <td>#<?= $b['id'] ?></td>
                            <td><b style="font-size:1.1rem;"><?= $b['name'] ?></b></td>
                            <td style="text-align:right;">
                                <a href="?view=brands&del_brand=<?= $b['id'] ?>" class="action-btn del" onclick="return confirm('Xóa thương hiệu này?')">Xóa</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>