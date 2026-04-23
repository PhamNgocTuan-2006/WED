<?php
if (session_status() == PHP_SESSION_NONE) session_start();
require_once 'config.php';

// 1. LẤY DANH SÁCH LINH KIỆN ĐỂ CHỌN
$products_res = $conn->query("SELECT id, name, sku FROM san_pham ORDER BY name ASC");
$all_products = [];
while($row = $products_res->fetch_assoc()) {
    $all_products[] = $row;
}

// 2. XỬ LÝ LƯU PHIẾU NHẬP (LƯU NHIỀU DÒNG CÙNG LÚC)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['btn_save'])) {
    $date = $_POST['import_date'];
    $batch = intval($_POST['import_batch']);
    
    $p_ids = $_POST['product_id'];
    $prices = $_POST['import_price'];
    $qtys = $_POST['quantity'];

    $conn->begin_transaction();
    try {
        for ($i = 0; $i < count($p_ids); $i++) {
            $pid = intval($p_ids[$i]);
            $price = floatval($prices[$i]);
            $qty = intval($qtys[$i]);

            if ($pid > 0 && $qty > 0) {
                $sql = "INSERT INTO phieu_nhap_hang (import_date, import_batch, product_id, import_price, quantity, status) 
                        VALUES ('$date', $batch, $pid, $price, $qty, 0)";
                $conn->query($sql);
            }
        }
        $conn->commit();
        header("Location: qlnhaphang.php?msg=added");
        exit();
    } catch (Exception $e) {
        $conn->rollback();
        $error = "Lỗi: " . $e->getMessage();
    }
}

include 'thanhmenu.php';
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Lập Phiếu Nhập | Private Space</title>
    <style>
        .container { padding: 30px; max-width: 1200px; margin: 0 auto; }
        .card { background: var(--bg-card); border-radius: 16px; padding: 25px; border: 1px solid rgba(255,255,255,0.05); }
        
        .header-grid { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; margin-bottom: 30px; background: var(--bg-deep); padding: 20px; border-radius: 12px; }
        label { display: block; font-size: 0.7rem; color: var(--text-dim); font-weight: 700; margin-bottom: 5px; text-transform: uppercase; }
        input, select { width: 100%; padding: 10px; background: #0f172a; border: 1px solid #334155; color: white; border-radius: 8px; box-sizing: border-box; }

        .import-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .import-table th { text-align: left; padding: 12px; color: var(--text-dim); font-size: 0.75rem; border-bottom: 1px solid #334155; }
        .import-table td { padding: 12px; border-bottom: 1px solid rgba(255,255,255,0.05); }

        .btn-add-row { background: rgba(59, 130, 246, 0.1); color: var(--accent-blue); border: 1px dashed var(--accent-blue); padding: 12px; width: 100%; border-radius: 10px; cursor: pointer; margin-top: 15px; font-weight: 700; transition: 0.3s; }
        .btn-add-row:hover { background: var(--accent-blue); color: white; border-style: solid; }
        
        .btn-submit { background: #10b981; color: white; border: none; padding: 15px 40px; border-radius: 10px; font-weight: 700; cursor: pointer; margin-top: 30px; width: 100%; font-size: 1rem; }
        .remove-btn { color: var(--danger); background: none; border: none; cursor: pointer; font-weight: 900; }
    </style>
</head>
<body>
    <div class="container">
        <header style="margin-bottom: 25px;">
            <h1 style="color: var(--accent-blue); margin: 0;">➕ Lập Phiếu Nhập Hàng</h1>
            <p style="color: var(--text-dim); font-size: 0.9rem;">Nhập nhiều linh kiện vào cùng một lô hệ thống</p>
        </header>

        <form method="POST" class="card">
            <div class="header-grid">
                <div>
                    <label>Ngày nhập hàng</label>
                    <input type="date" name="import_date" value="<?= date('Y-m-d') ?>" required>
                </div>
                <div>
                    <label>Lần nhập trong ngày (Lô)</label>
                    <input type="number" name="import_batch" placeholder="VD: 1" required>
                </div>
                <div>
                    <label>Mã Phiếu dự kiến</label>
                    <input type="text" value="PN<?= date('Ymd') ?>-..." disabled style="opacity:0.5; border-style:dashed;">
                </div>
            </div>

            <table class="import-table" id="productTable">
                <thead>
                    <tr>
                        <th width="45%">Linh kiện (Tìm tên hoặc mã)</th>
                        <th width="25%">Giá nhập (đ)</th>
                        <th width="20%">Số lượng</th>
                        <th width="10%"></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            <select name="product_id[]" required>
                                <option value="">-- Chọn sản phẩm --</option>
                                <?php foreach($all_products as $p): ?>
                                    <option value="<?= $p['id'] ?>"><?= $p['name'] ?> (<?= $p['sku'] ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td><input type="number" name="import_price[]" placeholder="0" required></td>
                        <td><input type="number" name="quantity[]" placeholder="0" required></td>
                        <td style="text-align:center;"></td>
                    </tr>
                </tbody>
            </table>

            <button type="button" class="btn-add-row" onclick="addRow()">+ THÊM DÒNG LINH KIỆN MỚI</button>

            <button type="submit" name="btn_save" class="btn-submit">LƯU PHIẾU NHẬP (TRẠNG THÁI NHÁP)</button>
            <a href="qlnhaphang.php" style="display:block; text-align:center; margin-top:15px; color:var(--text-dim); text-decoration:none; font-size:0.85rem;">← Hủy bỏ và quay lại</a>
        </form>
    </div>

    <script>
    // Hàm thêm dòng mới cho phiếu nhập
    function addRow() {
        const table = document.getElementById('productTable').getElementsByTagName('tbody')[0];
        const newRow = table.insertRow();
        
        // Copy nội dung từ dòng đầu tiên
        const firstRow = table.rows[0];
        newRow.innerHTML = firstRow.innerHTML;
        
        // Xóa giá trị cũ trong các ô input của dòng mới
        const inputs = newRow.getElementsByTagName('input');
        for(let i=0; i<inputs.length; i++) inputs[i].value = '';
        
        // Thêm nút xóa cho dòng mới
        newRow.cells[3].innerHTML = '<button type="button" class="remove-btn" onclick="removeRow(this)">✕</button>';
    }

    // Hàm xóa dòng
    function removeRow(btn) {
        const row = btn.parentNode.parentNode;
        row.parentNode.removeChild(row);
    }
    </script>
</body>
</html>