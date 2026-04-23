<?php
if (session_status() == PHP_SESSION_NONE) session_start();
require_once 'config.php';

$date = $_GET['date'] ?? '';
$batch = isset($_GET['batch']) ? intval($_GET['batch']) : 0;

$success = '';
$error = '';
$products = [];

// 1. TRUY VẤN THÔNG TIN LÔ NHẬP
$sql_check = "SELECT status FROM phieu_nhap_hang WHERE import_date = ? AND import_batch = ? LIMIT 1";
$stmt = $conn->prepare($sql_check);
$stmt->bind_param("si", $date, $batch);
$stmt->execute();
$batch_status = $stmt->get_result()->fetch_assoc()['status'] ?? null;

if ($batch_status === null) die("Không tìm thấy lô nhập hàng này.");

// Lấy chi tiết các sản phẩm trong lô
$sql_detail = "SELECT h.id as row_id, h.product_id, h.import_price, h.quantity, p.name, p.sku, p.unit 
               FROM phieu_nhap_hang h 
               JOIN san_pham p ON h.product_id = p.id 
               WHERE h.import_date = ? AND h.import_batch = ?";
$stmt = $conn->prepare($sql_detail);
$stmt->bind_param("si", $date, $batch);
$stmt->execute();
$res = $stmt->get_result();
while($row = $res->fetch_assoc()) $products[] = $row;

/* ==========================================================
   2. XỬ LÝ CẬP NHẬT HOẶC HOÀN THÀNH
   ========================================================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $batch_status == 0) {
    $action = $_POST['action'];
    $row_ids = $_POST['row_id'];
    $prices = $_POST['import_price'];
    $qtys = $_POST['quantity'];

    $conn->begin_transaction();
    try {
        // Cập nhật từng dòng trong lô nhập
        foreach ($row_ids as $index => $rid) {
            $u_price = floatval($prices[$index]);
            $u_qty = intval($qtys[$index]);
            $u_rid = intval($rid);
            
            $conn->query("UPDATE phieu_nhap_hang SET import_price = $u_price, quantity = $u_qty WHERE id = $u_rid");
        }

        // Nếu là hành động "Hoàn thành" -> Tính giá vốn bình quân
        if ($action === 'complete') {
            foreach ($products as $index => $p) {
                $pid = $p['product_id'];
                $new_qty = intval($qtys[$index]);
                $new_price = floatval($prices[$index]);

                // Lấy giá vốn và tồn kho hiện tại từ bảng giaca
                $cur = $conn->query("SELECT stock, cost_price FROM giaca WHERE product_id = $pid FOR UPDATE")->fetch_assoc();
                $old_qty = $cur['stock'];
                $old_price = $cur['cost_price'];

                // Công thức giá vốn bình quân gia quyền
                $total_qty = $old_qty + $new_qty;
                $updated_cost = ($total_qty > 0) ? (($old_qty * $old_price) + ($new_qty * $new_price)) / $total_qty : $new_price;

                // Cập nhật giaca & san_pham
                $conn->query("UPDATE giaca SET stock = $total_qty, cost_price = $updated_cost WHERE product_id = $pid");
                $conn->query("UPDATE san_pham SET stock = $total_qty, price = $updated_cost WHERE id = $pid");
            }
            // Khóa phiếu
            $conn->query("UPDATE phieu_nhap_hang SET status = 1 WHERE import_date = '$date' AND import_batch = $batch");
        }

        $conn->commit();
        header("Location: qlnhaphang.php?msg=updated");
        exit();
    } catch (Exception $e) {
        $conn->rollback();
        $error = "Lỗi xử lý: " . $e->getMessage();
    }
}

include 'thanhmenu.php';
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Sửa Phiếu Nhập | PN<?= date('Ymd', strtotime($date)) ?>-<?= $batch ?></title>
    <style>
        .container { padding: 30px; max-width: 1200px; margin: 0 auto; }
        .card { background: var(--bg-card); border-radius: 16px; padding: 25px; border: 1px solid rgba(255,255,255,0.05); }
        .readonly { opacity: 0.7; pointer-events: none; }
        
        .header-meta { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 30px; background: var(--bg-deep); padding: 20px; border-radius: 12px; }
        .meta-item label { display: block; font-size: 0.7rem; color: var(--text-dim); text-transform: uppercase; margin-bottom: 5px; }
        .meta-item span { font-weight: 700; color: var(--accent-blue); }

        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th { text-align: left; padding: 12px; color: var(--text-dim); font-size: 0.75rem; border-bottom: 1px solid #334155; }
        td { padding: 12px; border-bottom: 1px solid rgba(255,255,255,0.05); }
        input { background: var(--bg-deep); border: 1px solid #334155; color: white; padding: 8px; border-radius: 6px; width: 100px; }
        
        .badge { padding: 4px 12px; border-radius: 20px; font-size: 0.7rem; font-weight: 700; }
        .b-draft { background: rgba(251, 191, 36, 0.1); color: #fbbf24; }
        .b-done { background: rgba(16, 185, 129, 0.1); color: #10b981; }

        .footer-actions { display: flex; justify-content: space-between; align-items: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #334155; }
        .btn { padding: 12px 25px; border-radius: 8px; border: none; font-weight: 700; cursor: pointer; text-decoration: none; }
        .btn-save { background: var(--bg-deep); color: white; border: 1px solid #334155; }
        .btn-complete { background: #10b981; color: white; }
    </style>
</head>
<body>
    <div class="container">
        <header style="margin-bottom: 25px; display: flex; justify-content: space-between; align-items: center;">
            <h1 style="color: var(--accent-blue); margin: 0;">Hiệu chỉnh Phiếu nhập</h1>
            <span class="badge <?= $batch_status == 0 ? 'b-draft' : 'b-done' ?>">
                <?= $batch_status == 0 ? '● TRẠNG THÁI: BẢN NHÁP' : '● TRẠNG THÁI: ĐÃ HOÀN THÀNH' ?>
            </span>
        </header>

        <?php if($error): ?><div style="color:var(--danger); margin-bottom:15px;"><?= $error ?></div><?php endif; ?>

        <div class="card <?= $batch_status == 1 ? 'readonly' : '' ?>">
            <div class="header-meta">
                <div class="meta-item"><label>Mã phiếu</label><span>PN<?= date('Ymd', strtotime($date)) ?>-<?= $batch ?></span></div>
                <div class="meta-item"><label>Ngày nhập hàng</label><span><?= date('d/m/Y', strtotime($date)) ?></span></div>
                <div class="meta-item"><label>Lô nhập trong ngày</label><span>Lô số <?= $batch ?></span></div>
            </div>

            <form method="POST">
                <table>
                    <thead>
                        <tr>
                            <th>Mã SKU</th>
                            <th>Tên linh kiện</th>
                            <th width="150">Giá nhập (VNĐ)</th>
                            <th width="120">Số lượng</th>
                            <th>Thành tiền</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $total = 0; foreach ($products as $p): 
                            $subtotal = $p['import_price'] * $p['quantity'];
                            $total += $subtotal;
                        ?>
                        <tr>
                            <td style="font-family: monospace; font-size: 0.8rem;"><?= $p['sku'] ?></td>
                            <td style="font-weight: 600;"><?= $p['name'] ?></td>
                            <td>
                                <input type="hidden" name="row_id[]" value="<?= $p['row_id'] ?>">
                                <input type="number" name="import_price[]" value="<?= intval($p['import_price']) ?>" required>
                            </td>
                            <td><input type="number" name="quantity[]" value="<?= $p['quantity'] ?>" required></td>
                            <td style="color:#fbbf24; font-weight:700;"><?= number_format($subtotal) ?>đ</td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <div style="text-align: right; margin: 20px 0;">
                    <span style="color: var(--text-dim);">TỔNG GIÁ TRỊ PHIẾU: </span>
                    <span style="font-size: 1.5rem; color: #fbbf24; font-weight: 800;"><?= number_format($total) ?>đ</span>
                </div>

                <div class="footer-actions">
                    <a href="qlnhaphang.php" style="color: var(--text-dim); text-decoration: none; font-weight: 600;">← Quay lại</a>
                    <div style="display: flex; gap: 10px;">
                        <?php if($batch_status == 0): ?>
                            <button type="submit" name="action" value="save" class="btn btn-save">LƯU BẢN NHÁP</button>
                            <button type="submit" name="action" value="complete" class="btn btn-complete" 
                                    onclick="return confirm('XÁC NHẬN: Hoàn thành sẽ chốt GIÁ VỐN BÌNH QUÂN và không thể sửa phiếu nữa!')">
                                ✅ HOÀN THÀNH PHIẾU
                            </button>
                        <?php else: ?>
                            <button type="button" class="btn btn-save" onclick="window.print()">🖨️ IN PHIẾU NHẬP</button>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
        </div>
        
        <?php if($batch_status == 1): ?>
            <p style="margin-top: 20px; color: #10b981; font-size: 0.85rem; text-align: center;">
                * Phiếu này đã được chốt. Dữ liệu đã được cập nhật vào kho và tính toán lại giá vốn bình quân.
            </p>
        <?php endif; ?>
    </div>
</body>
</html>