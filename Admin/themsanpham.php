<?php
if (session_status() == PHP_SESSION_NONE) session_start();
require_once 'config.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$msg = "";

/* ==========================================================
   1. XỬ LÝ LOGIC NGHIỆP VỤ
   ========================================================== */

// A. KHỞI TẠO SẢN PHẨM MỚI (BƯỚC 1)
if (isset($_POST['init_prod'])) {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $cat = intval($_POST['cat']);
    $brand = intval($_POST['brand']);
    $cost = floatval($_POST['cost']);
    $profit = floatval($_POST['profit']);
    $unit = mysqli_real_escape_string($conn, $_POST['unit']);
    $sku = mysqli_real_escape_string($conn, $_POST['sku']);
    
    // Tự động tạo Product Code (SP-XX)
    $res_next = $conn->query("SELECT AUTO_INCREMENT FROM information_schema.TABLES WHERE TABLE_SCHEMA = '$dbname' AND TABLE_NAME = 'san_pham'");
    $next_id = $res_next->fetch_assoc()['AUTO_INCREMENT'];
    $p_code = "SP-" . $next_id;

    // SỬA TẠI ĐÂY: Mặc định gắn anhgoc.png cho cả 2 ảnh
    $sql1 = "INSERT INTO san_pham (name, category_id, brand_id, price, unit, profit_rate, sku, main_image, back_image) 
             VALUES ('$name', $cat, $brand, $cost, '$unit', $profit, '$sku', 'anhgoc.png', 'anhgoc.png')";
    
    if ($conn->query($sql1)) {
        $new_id = $conn->insert_id;
        $conn->query("INSERT INTO giaca (product_id, product_code, unit, stock, cost_price, profit_margin, status) 
                      VALUES ($new_id, '$p_code', '$unit', 0, $cost, $profit, 1)");
        header("Location: themsanpham.php?id=$new_id&msg=init");
        exit();
    }
}

// B. LƯU THÔNG SỐ (GIỮ NGUYÊN LOGIC)
if (isset($_POST['save_specs']) && $id > 0) {
    $conn->query("DELETE FROM thong_so WHERE product_id = $id");
    if (isset($_POST['dynamic_specs']) && is_array($_POST['dynamic_specs'])) {
        foreach ($_POST['dynamic_specs'] as $spec_item) {
            $key = mysqli_real_escape_string($conn, $spec_item['key']);
            $val = mysqli_real_escape_string($conn, trim($spec_item['val']));
            if (!empty($val)) {
                $conn->query("INSERT INTO thong_so (product_id, spec_key, spec_value) VALUES ($id, '$key', '$val')");
            }
        }
    }
    header("Location: themsanpham.php?id=$id&msg=spec_saved"); exit();
}

// C. CẬP NHẬT ẢNH
if (isset($_POST['update_img']) && $id > 0) {
    $col = ($_POST['img_type'] == 'main') ? 'main_image' : 'back_image';
    if (isset($_FILES['new_img']) && $_FILES['new_img']['error'] == 0) {
        $fname = time() . "_" . preg_replace("/[^a-zA-Z0-9.]/", "_", $_FILES['new_img']['name']);
        if (move_uploaded_file($_FILES['new_img']['tmp_name'], "image/" . $fname)) {
            $conn->query("UPDATE san_pham SET $col = '$fname' WHERE id = $id");
        }
    }
    header("Location: themsanpham.php?id=$id"); exit();
}

// D. XÓA ẢNH (RESET VỀ anhgoc.png)
if (isset($_POST['delete_img']) && $id > 0) {
    $col = ($_POST['img_type'] == 'main') ? 'main_image' : 'back_image';
    $res = $conn->query("SELECT $col FROM san_pham WHERE id = $id");
    $old_img = $res->fetch_assoc()[$col];

    // Nếu không phải ảnh mặc định thì xóa file vật lý
    if ($old_img != "anhgoc.png" && file_exists("image/" . $old_img)) {
        unlink("image/" . $old_img);
    }

    // SỬA TẠI ĐÂY: Reset về anhgoc.png
    $conn->query("UPDATE san_pham SET $col='anhgoc.png' WHERE id=$id");
    header("Location: themsanpham.php?id=$id"); exit();
}

/* ==========================================================
   2. TRUY VẤN DỮ LIỆU HIỂN THỊ
   ========================================================== */
$p = null; $defined_keys = null;
if ($id > 0) {
    $p = $conn->query("SELECT s.*, g.product_code FROM san_pham s JOIN giaca g ON s.id = g.product_id WHERE s.id = $id")->fetch_assoc();
    $cat_id = $p['category_id'];
    $defined_keys = $conn->query("SELECT spec_key FROM danhmuc_spec_keys WHERE category_id = $cat_id");
}
$cats = $conn->query("SELECT * FROM danh_muc ORDER BY name ASC");
$brands = $conn->query("SELECT * FROM thuong_hieu ORDER BY name ASC");

include 'thanhmenu.php';
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8"><title>Thêm Linh Kiện | Private Space</title>
    <style>
        .add-grid { display: grid; grid-template-columns: 1fr 400px; gap: 25px; padding: 25px; max-width: 1400px; margin: 0 auto; }
        .card { background: var(--bg-card); border-radius: 16px; padding: 25px; border: 1px solid rgba(255,255,255,0.05); margin-bottom: 25px; }
        .card.disabled { opacity: 0.4; pointer-events: none; filter: grayscale(0.8); }
        label { display: block; font-size: 0.65rem; color: var(--text-dim); font-weight: 700; margin-bottom: 6px; text-transform: uppercase; }
        input, select { width: 100%; padding: 12px; background: #000; border: 1px solid #334155; color: white; border-radius: 8px; box-sizing: border-box; }
        .btn-main { background: var(--accent-blue); color: white; border: none; padding: 15px; border-radius: 10px; font-weight: 700; width: 100%; cursor: pointer; }
        .spec-row { margin-bottom: 15px; background: rgba(255,255,255,0.02); padding: 15px; border-radius: 10px; border-left: 3px solid var(--accent-blue); }
        .img-preview { width: 100%; height: 200px; object-fit: cover; border-radius: 12px; margin-bottom: 15px; background: #222; border: 1px solid #334155; }
    </style>
</head>
<body>
    <div style="padding: 30px 25px 0 25px; max-width: 1400px; margin: 0 auto;">
        <h1 style="margin:0; color: var(--accent-blue);">🛠️ Khai báo Linh kiện</h1>
    </div>

    <div class="add-grid">
        <div>
            <form method="POST">
                <div class="card <?= $id > 0 ? 'disabled' : '' ?>">
                    <h3 style="color:var(--accent-blue); border-bottom:1px solid #333; padding-bottom:10px; font-size:0.8rem;">📦 1. Thông tin cơ bản</h3>
                    <div style="margin-bottom:15px;"><label>Tên sản phẩm linh kiện</label><input type="text" name="name" required></div>
                    <div style="display:grid; grid-template-columns: 1fr 1fr; gap:15px; margin-bottom:15px;">
                        <div><label>Danh mục</label><select name="cat"><?php while($c=$cats->fetch_assoc()) echo "<option value='{$c['id']}'>{$c['name']}</option>"; ?></select></div>
                        <div><label>Thương hiệu</label><select name="brand"><?php while($b=$brands->fetch_assoc()) echo "<option value='{$b['id']}'>{$b['name']}</option>"; ?></select></div>
                    </div>
                    <div style="display:grid; grid-template-columns: 1fr 1fr 1fr 1fr; gap:12px;">
                        <div><label>Giá vốn</label><input type="number" name="cost" required></div>
                        <div><label>Lợi nhuận %</label><input type="number" step="0.1" name="profit" value="15"></div>
                        <div><label>Đơn vị</label><input type="text" name="unit" value="Cái"></div>
                        <div><label>Mã SKU</label><input type="text" name="sku"></div>
                    </div>
                    <?php if($id == 0): ?><button type="submit" name="init_prod" class="btn-main" style="margin-top:20px;">KHỞI TẠO SẢN PHẨM →</button><?php endif; ?>
                </div>
            </form>

            <div class="card <?= ($id == 0) ? 'disabled' : '' ?>">
                <h3 style="color:var(--accent-blue); border-bottom:1px solid #333; padding-bottom:10px; font-size:0.8rem;">⚙️ 2. Thông số kỹ thuật</h3>
                <?php if ($defined_keys): ?>
                    <form method="POST">
                        <?php while($dk = $defined_keys->fetch_assoc()): 
                            $k = $dk['spec_key'];
                            $cur_val_res = $conn->query("SELECT spec_value FROM thong_so WHERE product_id = $id AND spec_key = '$k'");
                            $cur_val = $cur_val_res->fetch_assoc()['spec_value'] ?? '';
                        ?>
                            <div class="spec-row">
                                <label><?= $k ?></label>
                                <input type="hidden" name="dynamic_specs[<?= $k ?>][key]" value="<?= $k ?>">
                                <input type="text" name="dynamic_specs[<?= $k ?>][val]" value="<?= htmlspecialchars($cur_val) ?>" placeholder="Nhập giá trị..." required>
                            </div>
                        <?php endwhile; ?>
                        <button type="submit" name="save_specs" class="btn-main" style="background:#10b981;">LƯU THÔNG SỐ</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>

        <div class="<?= ($id == 0) ? 'disabled' : '' ?>">
            <div class="card">
                <h3 style="color:var(--accent-blue); border-bottom:1px solid #333; padding-bottom:10px; font-size:0.8rem;">🖼️ 3. Hình ảnh (Mặc định: anhgoc.png)</h3>
                
                <form method="POST" enctype="multipart/form-data" style="margin-bottom:25px;">
                    <input type="hidden" name="img_type" value="main">
                    <label>Ảnh mặt trước</label>
                    <img src="image/<?= $p ? $p['main_image'] : 'anhgoc.png' ?>" class="img-preview" onerror="this.src='image/anhgoc.png'">
                    <input type="file" name="new_img" id="file_main" onchange="enableBtn('btn_main')">
                    <button type="submit" name="update_img" id="btn_main" class="btn-main" style="margin-top:10px; padding:10px; font-size:0.7rem;" disabled>TẢI ẢNH LÊN</button>
                    <button type="submit" name="delete_img" class="btn-main" style="margin-top:5px; padding:10px; font-size:0.7rem; background:#ef4444;">XÓA (VỀ GỐC)</button>
                </form>

                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="img_type" value="back">
                    <label>Ảnh mặt sau</label>
                    <img src="image/<?= $p ? $p['back_image'] : 'anhgoc.png' ?>" class="img-preview" onerror="this.src='image/anhgoc.png'">
                    <input type="file" name="new_img" id="file_back" onchange="enableBtn('btn_back')">
                    <button type="submit" name="update_img" id="btn_back" class="btn-main" style="margin-top:10px; padding:10px; font-size:0.7rem;" disabled>TẢI ẢNH LÊN</button>
                    <button type="submit" name="delete_img" class="btn-main" style="margin-top:5px; padding:10px; font-size:0.7rem; background:#ef4444;">XÓA (VỀ GỐC)</button>
                </form>
            </div>
            <a href="sanpham.php" style="display:block; text-align:center; color:var(--text-dim); text-decoration:none; font-weight:700;">← HOÀN TẤT & QUAY LẠI</a>
        </div>
    </div>

    <script>function enableBtn(btnId) { document.getElementById(btnId).disabled = false; }</script>
</body>
</html>