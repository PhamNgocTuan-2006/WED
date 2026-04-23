<?php
if (session_status() == PHP_SESSION_NONE) session_start();
require_once 'config.php';

// Lấy thông tin từ URL
$date = $_GET['date'] ?? '';
$batch = isset($_GET['batch']) ? intval($_GET['batch']) : 0;

if (empty($date) || $batch == 0) {
    die("Thiếu thông tin phiếu nhập.");
}

/* ==========================================================
   1. TRUY VẤN DỮ LIỆU CHI TIẾT PHIẾU ĐÃ CHỐT
   ========================================================== */
$sql = "SELECT h.*, p.name, p.sku, p.unit, p.main_image 
        FROM phieu_nhap_hang h 
        JOIN san_pham p ON h.product_id = p.id 
        WHERE h.import_date = ? AND h.import_batch = ? 
        ORDER BY h.id ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("si", $date, $batch);
$stmt->execute();
$result = $stmt->get_result();

$items = [];
$total_value = 0;
$total_qty = 0;
$status = 0;

while ($row = $result->fetch_assoc()) {
    $items[] = $row;
    $total_value += ($row['import_price'] * $row['quantity']);
    $total_qty += $row['quantity'];
    $status = $row['status']; // Lấy trạng thái của lô hàng
}

if (empty($items)) {
    die("Không tìm thấy dữ liệu cho phiếu nhập này.");
}

// Định dạng mã phiếu chuyên nghiệp
$pn_code = "PN" . date('Ymd', strtotime($date)) . "-" . str_pad($batch, 2, '0', STR_PAD_LEFT);

include 'thanhmenu.php';
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Chi tiết phiếu: <?= $pn_code ?></title>
    <style>
        .view-container { padding: 30px; max-width: 1100px; margin: 0 auto; }
        .receipt-card { background: var(--bg-card); border-radius: 20px; padding: 40px; border: 1px solid rgba(255,255,255,0.05); position: relative; overflow: hidden; }
        
        /* Watermark trạng thái */
        .watermark { 
            position: absolute; top: 20px; right: -30px; transform: rotate(15deg);
            padding: 10px 60px; font-weight: 900; font-size: 0.9rem; text-transform: uppercase;
            background: rgba(16, 185, 129, 0.1); color: #10b981; border: 2px solid #10b981;
        }

        .receipt-header { display: flex; justify-content: space-between; margin-bottom: 40px; border-bottom: 1px solid #334155; padding-bottom: 20px; }
        .brand-info h2 { color: var(--accent-blue); margin: 0; font-size: 1.5rem; }
        .receipt-meta { text-align: right; }
        .receipt-meta p { margin: 5px 0; color: var(--text-dim); font-size: 0.9rem; }

        .table-view { width: 100%; border-collapse: collapse; margin: 30px 0; }
        .table-view th { text-align: left; padding: 15px; color: var(--text-dim); font-size: 0.75rem; text-transform: uppercase; border-bottom: 2px solid #334155; }
        .table-view td { padding: 15px; border-bottom: 1px solid rgba(255,255,255,0.05); font-size: 0.95rem; }
        
        .product-cell { display: flex; align-items: center; gap: 15px; }
        .thumb { width: 45px; height: 45px; border-radius: 8px; object-fit: cover; background: #000; }

        .summary-box { background: var(--bg-deep); border-radius: 12px; padding: 25px; display: flex; justify-content: flex-end; gap: 50px; }
        .sum-item label { display: block; font-size: 0.75rem; color: var(--text-dim); text-transform: uppercase; margin-bottom: 5px; }
        .sum-item span { font-size: 1.2rem; font-weight: 800; color: #fbbf24; }

        .btn-print { background: var(--accent-blue); color: white; border: none; padding: 12px 30px; border-radius: 10px; font-weight: 700; cursor: pointer; display: flex; align-items: center; gap: 10px; transition: 0.3s; }
        .btn-print:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(59, 130, 246, 0.3); }

        @media print {
            .sidebar, .btn-print, .back-link { display: none !important; }
            .view-container { padding: 0; width: 100%; }
            .receipt-card { border: none; background: white; color: black; }
            .receipt-card td, .receipt-card th { color: black !important; border-color: #eee !important; }
            .summary-box { background: #f8f9fa; border: 1px solid #eee; }
        }
    </style>
</head>
<body>
    <div class="view-container">
        <a href="qlnhaphang.php" class="back-link" style="color: var(--text-dim); text-decoration: none; font-size: 0.85rem; display: block; margin-bottom: 20px;">← Quay lại danh sách lô nhập</a>

        <div class="receipt-card">
            <?php if($status == 1): ?>
                <div class="watermark">Đã Hoàn Thành</div>
            <?php endif; ?>

            <div class="receipt-header">
                <div class="brand-info">
                    <h2>PHONG CÁCH RIÊNG</h2>
                    <p style="color: var(--text-dim); font-size: 0.8rem;">Hệ thống quản lý kho linh kiện máy tính</p>
                </div>
                <div class="receipt-meta">
                    <p>Mã phiếu: <strong style="color:white;"><?= $pn_code ?></strong></p>
                    <p>Ngày nhập: <strong><?= date('d/m/Y', strtotime($date)) ?></strong></p>
                    <p>Lô thứ: <strong><?= $batch ?></strong></p>
                </div>
            </div>

            <table class="table-view">
                <thead>
                    <tr>
                        <th>Mặt hàng linh kiện</th>
                        <th>Đơn vị</th>
                        <th>Giá nhập</th>
                        <th>Số lượng</th>
                        <th style="text-align: right;">Thành tiền</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): 
                        $subtotal = $item['import_price'] * $item['quantity'];
                    ?>
                    <tr>
                        <td>
                            <div class="product-cell">
                                <img src="image/<?= $item['main_image'] ?>" class="thumb" onerror="this.src='image/no-image.png'">
                                <div>
                                    <div style="font-weight:700;"><?= $item['name'] ?></div>
                                    <small style="color:var(--accent-blue); font-family:monospace;"><?= $item['sku'] ?></small>
                                </div>
                            </div>
                        </td>
                        <td><?= $item['unit'] ?></td>
                        <td><?= number_format($item['import_price']) ?> đ</td>
                        <td><strong><?= number_format($item['quantity']) ?></strong></td>
                        <td style="text-align: right; font-weight: 700;"><?= number_format($subtotal) ?> đ</td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="summary-box">
                <div class="sum-item">
                    <label>Tổng mặt hàng</label>
                    <span style="color:white;"><?= count($items) ?> loại</span>
                </div>
                <div class="sum-item">
                    <label>Tổng số lượng</label>
                    <span style="color:white;"><?= number_format($total_qty) ?></span>
                </div>
                <div class="sum-item">
                    <label>Tổng giá trị phiếu</label>
                    <span><?= number_format($total_value) ?> đ</span>
                </div>
            </div>

            <div style="margin-top: 40px; display: flex; justify-content: flex-end;">
                <button class="btn-print" onclick="window.print()">
                    <span>🖨️ IN PHIẾU NHẬP NÀY</span>
                </button>
            </div>
        </div>

        <p style="text-align: center; color: var(--text-dim); font-size: 0.8rem; margin-top: 25px;">
            * Đây là dữ liệu gốc từ bảng <code>phieu_nhap_hang</code>. Giá vốn đã được cập nhật vào bảng <code>giaca</code> ngay khi chốt phiếu.
        </p>
    </div>
</body>
</html>