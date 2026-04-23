<?php
if (session_status() == PHP_SESSION_NONE) session_start();
require_once 'config.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) { header("Location: sanpham.php"); exit(); }

/* ==========================================================
   1. TRUY VẤN DỮ LIỆU GỐC TỪ DATABASE
   ========================================================== */
$sql_get = "SELECT s.*, g.product_code, g.profit_margin, g.unit as g_unit, g.status as g_status 
            FROM san_pham s 
            JOIN giaca g ON s.id = g.product_id 
            WHERE s.id = $id";
$p = $conn->query($sql_get)->fetch_assoc();

/* ==========================================================
   2. LOGIC "GHI NHỚ TẠM THỜI" (UI PERSISTENCE)
   Giúp giữ lại dữ liệu khi người dùng đổi danh mục để hiện Spec_key mới
   ========================================================== */
$display_cat_id = isset($_POST['cat']) ? intval($_POST['cat']) : $p['category_id'];
if (isset($_POST['cat'])) {
    $p['name'] = $_POST['name'] ?? $p['name'];
    $p['price'] = $_POST['cost'] ?? $p['price'];
    $p['profit_margin'] = $_POST['profit'] ?? $p['profit_margin'];
    $p['g_unit'] = $_POST['unit'] ?? $p['g_unit'];
    $p['sku'] = $_POST['sku'] ?? $p['sku'];
}

/* ==========================================================
   3. XỬ LÝ LƯU TẤT CẢ (KHI BẤM NÚT SAVE_ALL)
   ========================================================== */
if (isset($_POST['save_all'])) {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $cat = intval($_POST['cat']);
    $brand = intval($_POST['brand']);
    $cost = floatval($_POST['cost']);
    $profit = floatval($_POST['profit']);
    $unit = mysqli_real_escape_string($conn, $_POST['unit']);
    $status = intval($_POST['status']);
    $sku = mysqli_real_escape_string($conn, $_POST['sku']);

    // A. Cập nhật bảng san_pham và giaca
    $conn->query("UPDATE san_pham SET name='$name', category_id=$cat, brand_id=$brand, price=$cost, profit_rate=$profit, unit='$unit', status=$status, sku='$sku' WHERE id=$id");
    $conn->query("UPDATE giaca SET cost_price=$cost, profit_margin=$profit, unit='$unit', status=$status WHERE product_id=$id");

    // B. Cập nhật thông số (Dùng bảng thong_so cũ theo yêu cầu)
    $conn->query("DELETE FROM thong_so WHERE product_id = $id");
    if (isset($_POST['dynamic_specs']) && is_array($_POST['dynamic_specs'])) {
        foreach ($_POST['dynamic_specs'] as $spec_item) {
            $k = mysqli_real_escape_string($conn, $spec_item['key']);
            $v = mysqli_real_escape_string($conn, trim($spec_item['val']));
            if ($v !== '') {
                $conn->query("INSERT INTO thong_so (product_id, spec_key, spec_value) VALUES ($id, '$k', '$v')");
            }
        }
    }
    header("Location: suasanpham.php?id=$id&msg=success"); exit();
}

/* ==========================================================
   4. XỬ LÝ HÌNH ẢNH (MẶT TRƯỚC / SAU)
   ========================================================== */
if (isset($_POST['update_img'])) {
    $col = ($_POST['img_type'] == 'main') ? 'main_image' : 'back_image';
    if (isset($_FILES['new_img']) && $_FILES['new_img']['error'] == 0) {
        $filename = time() . "_" . preg_replace("/[^a-zA-Z0-9.]/", "_", $_FILES['new_img']['name']);
        if (move_uploaded_file($_FILES['new_img']['tmp_name'], "image/" . $filename)) {
            $conn->query("UPDATE san_pham SET $col = '$filename' WHERE id = $id");
        }
    }
    header("Location: suasanpham.php?id=$id"); exit();
}

// Lấy danh sách spec_key từ bảng định nghĩa và spec_value hiện tại từ bảng thong_so
$dynamic_specs = $conn->query("SELECT dk.spec_key, ts.spec_value 
                               FROM danhmuc_spec_keys dk 
                               LEFT JOIN thong_so ts ON dk.spec_key = ts.spec_key AND ts.product_id = $id
                               WHERE dk.category_id = $display_cat_id");

$cats = $conn->query("SELECT * FROM danh_muc ORDER BY name ASC");
$brands = $conn->query("SELECT * FROM thuong_hieu ORDER BY name ASC");

include 'thanhmenu.php';
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Sửa Linh Kiện | Private Space</title>
    <style>
        .edit-grid { display: grid; grid-template-columns: 1fr 400px; gap: 25px; padding: 25px; max-width: 1400px; margin: 0 auto; }
        .card { background: var(--bg-card); border-radius: 16px; padding: 25px; border: 1px solid rgba(255,255,255,0.05); margin-bottom: 25px; }
        h3 { font-size: 0.8rem; color: var(--accent-blue); margin: 0 0 20px 0; border-bottom: 1px solid #334155; padding-bottom: 10px; text-transform: uppercase; }
        label { display: block; font-size: 0.65rem; color: var(--text-dim); font-weight: 700; margin-bottom: 8px; text-transform: uppercase; }
        input, select, textarea { width: 100%; padding: 12px; background: #000; border: 1px solid #334155; color: white; border-radius: 10px; box-sizing: border-box; font-size: 0.9rem; }
        .btn-main { background: var(--accent-blue); color: white; border: none; padding: 15px; border-radius: 10px; font-weight: 700; width: 100%; cursor: pointer; transition: 0.3s; margin-top: 15px; }
        .spec-box { margin-bottom: 15px; background: rgba(255,255,255,0.02); padding: 15px; border-radius: 10px; border-left: 3px solid var(--accent-blue); }
        .img-view { width: 100%; height: 200px; object-fit: cover; border-radius: 12px; margin-bottom: 15px; border: 1px solid #334155; background: #000; }
    </style>
</head>
<body>
    <div class="edit-grid">
        <div>
            <?php if(isset($_GET['msg'])) echo '<div style="background:#10b981; color:white; padding:15px; border-radius:10px; margin-bottom:20px; text-align:center;">✓ CẬP NHẬT THÀNH CÔNG</div>'; ?>
            
            <form method="POST" id="mainForm">
                <div class="card">
                    <h3>📦 THÔNG TIN LINH KIỆN (#<?= $p['product_code'] ?>)</h3>
                    <div style="margin-bottom:15px;"><label>Tên sản phẩm</label>
                    <input type="text" name="name" value="<?= htmlspecialchars($p['name']) ?>" required></div>
                    
                    <div style="display:grid; grid-template-columns: 1fr 1fr; gap:15px; margin-bottom:15px;">
                        <div><label>Loại (Danh mục)</label>
                            <select name="cat" onchange="document.getElementById('mainForm').submit()">
                                <?php while($c = $cats->fetch_assoc()): ?>
                                    <option value="<?= $c['id'] ?>" <?= $c['id'] == $display_cat_id ? 'selected' : '' ?>><?= $c['name'] ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div><label>Thương hiệu</label>
                            <select name="brand">
                                <?php while($b = $brands->fetch_assoc()): ?>
                                    <option value="<?= $b['id'] ?>" <?= $b['id']==$p['brand_id']?'selected':'' ?>><?= $b['name'] ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>

                    <div style="display:grid; grid-template-columns: 1fr 1fr 1fr 1fr; gap:12px;">
                        <div><label>Giá vốn (đ)</label><input type="number" name="cost" value="<?= intval($p['price']) ?>"></div>
                        <div><label>Lợi nhuận (%)</label><input type="number" step="0.1" name="profit" value="<?= $p['profit_margin'] ?>"></div>
                        <div><label>Đơn vị</label><input type="text" name="unit" value="<?= $p['g_unit'] ?>"></div>
                        <div><label>SKU</label><input type="text" name="sku" value="<?= $p['sku'] ?>"></div>
                    </div>
                </div>

                <div class="card">
                    <h3>⚙️ THÔNG SỐ KỸ THUẬT BẮT BUỘC</h3>
                    <?php if($dynamic_specs->num_rows > 0): ?>
                        <?php while($ds = $dynamic_specs->fetch_assoc()): 
                            $k = $ds['spec_key'];
                            // Gợi ý các giá trị đã tồn tại của key này
                            $suggestions = $conn->query("SELECT DISTINCT spec_value FROM thong_so WHERE spec_key = '$k'");
                        ?>
                            <div class="spec-box">
                                <label style="color:var(--accent-blue);"><?= $k ?></label>
                                <input type="hidden" name="dynamic_specs[<?= $k ?>][key]" value="<?= $k ?>">
                                <input type="text" name="dynamic_specs[<?= $k ?>][val]" 
                                       list="list-<?= md5($k) ?>" 
                                       value="<?= htmlspecialchars($ds['spec_value'] ?? '') ?>" 
                                       placeholder="Nhập giá trị cho <?= $k ?>..." required>
                                <datalist id="list-<?= md5($k) ?>">
                                    <?php while($sv = $suggestions->fetch_assoc()) echo "<option value='{$sv['spec_value']}'>"; ?>
                                </datalist>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p style="color:var(--text-dim); font-style:italic;">Danh mục này chưa thiết lập khung thông số.</p>
                    <?php endif; ?>
                    
                    <button type="submit" name="save_all" class="btn-main">💾 LƯU TOÀN BỘ THÔNG TIN</button>
                </div>
            </form>
        </div>

        <div>
            <div class="card">
                <h3>🖼️ HÌNH ẢNH</h3>
                <form method="POST" enctype="multipart/form-data" style="margin-bottom:30px;">
                    <label>MẶT TRƯỚC</label>
                    <img src="image/<?= $p['main_image'] ?>" class="img-view" onerror="this.src='image/no-image.png'">
                    <input type="file" name="new_img" onchange="this.form.submit()">
                    <input type="hidden" name="img_type" value="main">
                    <input type="hidden" name="update_img" value="1">
                </form>
                <form method="POST" enctype="multipart/form-data">
                    <label>MẶT SAU</label>
                    <img src="image/<?= $p['back_image'] ?>" class="img-view" onerror="this.src='image/no-image.png'">
                    <input type="file" name="new_img" onchange="this.form.submit()">
                    <input type="hidden" name="img_type" value="back">
                    <input type="hidden" name="update_img" value="1">
                </form>
            </div>
            <a href="sanpham.php" style="display:block; text-align:center; color:var(--text-dim); text-decoration:none; font-weight:700;">← QUAY LẠI DANH SÁCH</a>
        </div>
    </div>
</body>
</html>