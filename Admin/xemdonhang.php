<?php
if (session_status() == PHP_SESSION_NONE) session_start();
require_once 'config.php';

// 1. LẤY ID ĐƠN HÀNG TỪ URL
$order_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($order_id <= 0) {
    die("Đơn hàng không tồn tại.");
}

// 2. TRUY VẤN THÔNG TIN CHUNG CỦA ĐƠN HÀNG
$sql_order = "SELECT * FROM don_hang WHERE id = $order_id";
$order_res = $conn->query($sql_order);
$order = $order_res->fetch_assoc();

if (!$order) {
    die("Không tìm thấy dữ liệu đơn hàng.");
}

// 3. TRUY VẤN CHI TIẾT SẢN PHẨM TRONG ĐƠN
$sql_items = "SELECT c.*, s.name, s.main_image, g.product_code 
              FROM chi_tiet_don_hang c
              JOIN san_pham s ON c.product_id = s.id
              JOIN giaca g ON s.id = g.product_id
              WHERE c.order_id = $order_id";
$items_res = $conn->query($sql_items);

include 'thanhmenu.php';
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Chi tiết đơn hàng #<?= $order_id ?></title>
    <style>
        .container { padding: 30px; max-width: 1000px; margin: 0 auto; }
        .invoice-card { background: var(--bg-card); border-radius: 20px; padding: 40px; border: 1px solid rgba(255,255,255,0.05); }
        
        .invoice-header { display: flex; justify-content: space-between; border-bottom: 1px solid #334155; padding-bottom: 30px; margin-bottom: 30px; }
        .invoice-info h2 { color: var(--accent-blue); margin: 0 0 10px 0; }
        .invoice-info p { margin: 5px 0; color: var(--text-dim); font-size: 0.9rem; }
        
        .customer-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 40px; margin-bottom: 40px; }
        .info-box h4 { font-size: 0.75rem; color: var(--accent-blue); text-transform: uppercase; margin-bottom: 15px; border-bottom: 1px dashed #334155; padding-bottom: 5px; }
        .info-box p { margin: 8px 0; font-size: 0.95rem; }

        .item-table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        .item-table th { text-align: left; padding: 15px; color: var(--text-dim); font-size: 0.75rem; border-bottom: 2px solid #334155; text-transform: uppercase; }
        .item-table td { padding: 15px; border-bottom: 1px solid rgba(255,255,255,0.05); }
        
        .product-meta { display: flex; align-items: center; gap: 15px; }
        .thumb { width: 50px; height: 50px; border-radius: 8px; object-fit: cover; background: #000; }

        .total-section { text-align: right; border-top: 2px solid #334155; padding-top: 20px; }
        .total-amount { font-size: 1.8rem; font-weight: 800; color: #fbbf24; }

        .status-pill { padding: 6px 15px; border-radius: 30px; font-size: 0.75rem; font-weight: 700; }
        .s-0 { background: rgba(251, 191, 36, 0.1); color: #fbbf24; }
        .s-1 { background: rgba(59, 130, 246, 0.1); color: #3b82f6; }
        .s-2 { background: rgba(16, 185, 129, 0.1); color: #10b981; }
        .s-3 { background: rgba(239, 68, 68, 0.1); color: #ef4444; }

        @media print {
            .sidebar, .btn-print, .back-link { display: none !important; }
            body { background: white; color: black; }
            .invoice-card { border: none; background: white; color: black; }
            .invoice-card td, .invoice-card th { color: black !important; border-bottom: 1px solid #ccc !important; }
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="quanlydonhang.php" class="back-link" style="color:var(--text-dim); text-decoration:none; display:inline-block; margin-bottom:20px;">← Quay lại danh sách đơn hàng</a>

        <div class="invoice-card">
            <div class="invoice-header">
                <div class="invoice-info">
                    <h2>HÓA ĐƠN MUA HÀNG</h2>
                    <p>Mã đơn: <b style="color:white;">#DH-<?= $order['id'] ?></b></p>
                    <p>Ngày đặt: <?= date('d/m/Y H:i', strtotime($order['order_date'])) ?></p>
                </div>
                <div>
                    <?php 
                        $st_txt = ['Chưa xử lý', 'Đã xác nhận', 'Đã giao thành công', 'Đã hủy'];
                    ?>
                    <span class="status-pill s-<?= $order['status'] ?>">● <?= $st_txt[$order['status']] ?></span>
                </div>
            </div>

            <div class="customer-grid">
                <div class="info-box">
                    <h4>Thông tin khách hàng</h4>
                    <p><b>Họ tên:</b> <?= $order['customer_name'] ?></p>
                    <p><b>Điện thoại:</b> <?= $order['phone'] ?></p>
                </div>
                <div class="info-box">
                    <h4>Địa chỉ giao hàng</h4>
                    <p><b>Địa chỉ:</b> <?= $order['address'] ?></p>
                    <p><b>Phường/Xã:</b> <?= $order['ward'] ?></p>
                </div>
            </div>

            <table class="item-table">
                <thead>
                    <tr>
                        <th>Sản phẩm linh kiện</th>
                        <th>Giá bán</th>
                        <th>Số lượng</th>
                        <th style="text-align:right;">Thành tiền</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($item = $items_res->fetch_assoc()): 
                        $subtotal = $item['price_at_purchase'] * $item['quantity'];
                    ?>
                    <tr>
                        <td>
                            <div class="product-meta">
                                <img src="image/<?= $item['main_image'] ?>" class="thumb" onerror="this.src='image/no-image.png'">
                                <div>
                                    <div style="font-weight:700;"><?= $item['name'] ?></div>
                                    <small style="color:var(--accent-blue); font-family:monospace;"><?= $item['product_code'] ?></small>
                                </div>
                            </div>
                        </td>
                        <td><?= number_format($item['price_at_purchase']) ?>đ</td>
                        <td>x <?= $item['quantity'] ?></td>
                        <td style="text-align:right; font-weight:700;"><?= number_format($subtotal) ?>đ</td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>

            <div class="total-section">
                <p style="color:var(--text-dim); margin-bottom:5px;">TỔNG THANH TOÁN</p>
                <div class="total-amount"><?= number_format($order['total_amount']) ?> VNĐ</div>
            </div>

            <div style="margin-top:40px; display:flex; justify-content:flex-end; gap:15px;">
                <button onclick="window.print()" class="btn-print" style="background:var(--accent-blue); color:white; border:none; padding:12px 25px; border-radius:10px; cursor:pointer; font-weight:700;">🖨️ IN HÓA ĐƠN</button>
            </div>
        </div>
    </div>
</body>
</html>