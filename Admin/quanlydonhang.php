<?php
if (session_status() == PHP_SESSION_NONE) session_start();
require_once 'config.php';

/* ==========================================================
   1. XỬ LÝ CẬP NHẬT TRẠNG THÁI & ĐỒNG BỘ KHO CHI TIẾT
   ========================================================== */
if (isset($_POST['update_status'])) {
    $order_id = intval($_POST['order_id']);
    $new_status = intval($_POST['status']);

    // Lấy trạng thái cũ của đơn hàng để tránh trừ kho 2 lần
    $old_res = $conn->query("SELECT status FROM don_hang WHERE id = $order_id");
    $old_data = $old_res->fetch_assoc();
    $old_status = $old_data['status'];

    if ($new_status != $old_status) {
        // Cập nhật trạng thái đơn hàng trong bảng don_hang
        $conn->query("UPDATE don_hang SET status = $new_status WHERE id = $order_id");

        // --- BẮT ĐẦU LOGIC CẬP NHẬT KHO DỰA TRÊN DATABASE 'mydb (3).sql' ---
        
        // TRƯỜNG HỢP 1: Chuyển sang trạng thái HỢP LỆ (1: Xác nhận hoặc 2: Đã giao)
        // Từ trạng thái CHƯA HỢP LỆ (0: Chờ hoặc 3: Đã hủy) -> THỰC HIỆN TRỪ KHO
        if (($new_status == 1 || $new_status == 2) && ($old_status == 0 || $old_status == 3)) {
            $items = $conn->query("SELECT product_id, quantity FROM chi_tiet_don_hang WHERE order_id = $order_id");
            while ($it = $items->fetch_assoc()) {
                $pid = $it['product_id']; 
                $qty = $it['quantity'];

                // Cập nhật đồng thời cả 2 bảng theo cấu trúc Database
                $conn->query("UPDATE giaca SET stock = stock - $qty WHERE product_id = $pid");
                $conn->query("UPDATE san_pham SET stock = stock - $qty WHERE id = $pid");
                
                // Ghi nhật ký xuất kho
                $conn->query("INSERT INTO nhat_ky_kho (product_id, change_quantity, transaction_type, reference_id) 
                              VALUES ($pid, -$qty, 'XUAT', $order_id)");
            }
        }
        
        // TRƯỜNG HỢP 2: Chuyển sang trạng thái HỦY (3) hoặc RESET CHỜ (0)
        // Từ trạng thái ĐÃ TRỪ KHO (1 hoặc 2) -> THỰC HIỆN CỘNG LẠI KHO (HOÀN KHO)
        elseif (($new_status == 3 || $new_status == 0) && ($old_status == 1 || $old_status == 2)) {
            $items = $conn->query("SELECT product_id, quantity FROM chi_tiet_don_hang WHERE order_id = $order_id");
            while ($it = $items->fetch_assoc()) {
                $pid = $it['product_id']; 
                $qty = $it['quantity'];

                // Hoàn lại số lượng vào cả 2 bảng
                $conn->query("UPDATE giaca SET stock = stock + $qty WHERE product_id = $pid");
                $conn->query("UPDATE san_pham SET stock = stock + $qty WHERE id = $pid");

                // Ghi nhật ký hoàn kho
                $conn->query("INSERT INTO nhat_ky_kho (product_id, change_quantity, transaction_type, reference_id) 
                              VALUES ($pid, $qty, 'DIEU_CHINH', $order_id)");
            }
        }
    }
    header("Location: quanlydonhang.php?msg=updated");
    exit();
}

/* ==========================================================
   2. TRUY VẤN DỮ LIỆU & BỘ LỌC (GIỮ NGUYÊN GIAO DIỆN CŨ)
   ========================================================== */
$filter_ward = $_GET['ward'] ?? '';
$filter_status = $_GET['status'] ?? '';
$from_date = $_GET['from_date'] ?? '';
$to_date = $_GET['to_date'] ?? '';

$where_clauses = [];
if ($filter_ward !== '') $where_clauses[] = "ward = '" . $conn->real_escape_string($filter_ward) . "'";
if ($filter_status !== '') $where_clauses[] = "status = " . intval($filter_status);
if ($from_date !== '') $where_clauses[] = "order_date >= '$from_date 00:00:00'";
if ($to_date !== '') $where_clauses[] = "order_date <= '$to_date 23:59:59'";

$where_sql = count($where_clauses) > 0 ? "WHERE " . implode(' AND ', $where_clauses) : "";

$sql = "SELECT * FROM don_hang $where_sql ORDER BY order_date DESC";
$result = $conn->query($sql);
$wards_res = $conn->query("SELECT DISTINCT ward FROM don_hang ORDER BY ward ASC");

include 'thanhmenu.php';
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quản lý Đơn hàng | Private Space</title>
    <style>
        .container { padding: 30px; max-width: 1500px; margin: 0 auto; }
        .card { background: var(--bg-card); border-radius: 16px; padding: 25px; border: 1px solid rgba(255,255,255,0.05); }
        .filter-bar { display: flex; gap: 15px; flex-wrap: wrap; margin-bottom: 25px; background: var(--bg-deep); padding: 20px; border-radius: 12px; align-items: flex-end; }
        .filter-group { display: flex; flex-direction: column; gap: 5px; }
        .filter-group label { font-size: 0.7rem; color: var(--text-dim); font-weight: 700; text-transform: uppercase; }
        select, input[type="date"] { padding: 10px; background: #0f172a; border: 1px solid #334155; color: white; border-radius: 8px; font-size: 0.85rem; }
        .table-ui { width: 100%; border-collapse: collapse; }
        .table-ui th { text-align: left; color: var(--text-dim); padding: 15px; font-size: 0.7rem; border-bottom: 2px solid rgba(255,255,255,0.05); text-transform: uppercase; }
        .table-ui td { padding: 15px; border-bottom: 1px solid rgba(255,255,255,0.05); font-size: 0.9rem; }
        .badge { padding: 5px 12px; border-radius: 20px; font-size: 0.7rem; font-weight: 700; }
        .st-0 { background: rgba(251, 191, 36, 0.1); color: #fbbf24; }
        .st-1 { background: rgba(59, 130, 246, 0.1); color: var(--accent-blue); }
        .st-2 { background: rgba(16, 185, 129, 0.1); color: #10b981; }
        .st-3 { background: rgba(239, 68, 68, 0.1); color: var(--danger); }
        .btn-view { color: var(--accent-blue); text-decoration: none; font-weight: 700; margin-right: 10px; }
        .btn-filter { background: var(--accent-blue); color: white; border: none; padding: 10px 25px; border-radius: 8px; cursor: pointer; font-weight: 700; }
    </style>
</head>
<body>
    <div class="container">
        <header style="margin-bottom: 25px;">
            <h1 style="color: var(--accent-blue); margin: 0;">🛒 Quản lý Đơn đặt hàng</h1>
            <p style="color: var(--text-dim); font-size: 0.9rem;">Dữ liệu kho sẽ tự động đồng bộ giữa bảng <b>san_pham</b> và <b>giaca</b>.</p>
        </header>

        <form method="GET" class="filter-bar">
            <div class="filter-group">
                <label>Theo Phường</label>
                <select name="ward">
                    <option value="">-- Tất cả phường --</option>
                    <?php while($w = $wards_res->fetch_assoc()): ?>
                        <option value="<?= $w['ward'] ?>" <?= $filter_ward == $w['ward'] ? 'selected' : '' ?>><?= $w['ward'] ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="filter-group">
                <label>Trạng thái</label>
                <select name="status">
                    <option value="">-- Tất cả --</option>
                    <option value="0" <?= $filter_status === '0' ? 'selected' : '' ?>>Chưa xử lý</option>
                    <option value="1" <?= $filter_status === '1' ? 'selected' : '' ?>>Đã xác nhận</option>
                    <option value="2" <?= $filter_status === '2' ? 'selected' : '' ?>>Đã giao thành công</option>
                    <option value="3" <?= $filter_status === '3' ? 'selected' : '' ?>>Đã hủy</option>
                </select>
            </div>
            <div class="filter-group">
                <label>Từ ngày</label>
                <input type="date" name="from_date" value="<?= $from_date ?>">
            </div>
            <div class="filter-group">
                <label>Đến ngày</label>
                <input type="date" name="to_date" value="<?= $to_date ?>">
            </div>
            <button type="submit" class="btn-filter">ÁP DỤNG</button>
            <a href="quanlydonhang.php" style="color:var(--text-dim); font-size:0.8rem; margin-left:10px; text-decoration:none;">Xóa lọc</a>
        </form>

        <section class="card">
            <table class="table-ui">
                <thead>
                    <tr>
                        <th>Mã Đơn</th>
                        <th>Khách hàng</th>
                        <th>Phường</th>
                        <th>Tổng tiền</th>
                        <th>Ngày đặt</th>
                        <th>Trạng thái</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result && $result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><strong style="color:var(--accent-blue);">#DH-<?= $row['id'] ?></strong></td>
                            <td>
                                <div style="font-weight:700;"><?= $row['customer_name'] ?></div>
                                <small style="color:var(--text-dim);"><?= $row['phone'] ?></small>
                            </td>
                            <td><?= $row['ward'] ?></td>
                            <td style="color:#fbbf24; font-weight:800;"><?= number_format($row['total_amount']) ?>đ</td>
                            <td><?= date('d/m/Y H:i', strtotime($row['order_date'])) ?></td>
                            <td>
                                <span class="badge st-<?= $row['status'] ?>">
                                    <?php 
                                        $st_txt = ['Chưa xử lý', 'Đã xác nhận', 'Đã giao', 'Đã hủy'];
                                        echo $st_txt[$row['status']];
                                    ?>
                                </span>
                            </td>
                            <td>
                                <div style="display:flex; align-items:center; gap:10px;">
                                    <a href="xemdonhang.php?id=<?= $row['id'] ?>" class="btn-view">🔎 Xem</a>
                                    <form method="POST">
                                        <input type="hidden" name="order_id" value="<?= $row['id'] ?>">
                                        <select name="status" onchange="this.form.submit()" style="padding:5px; font-size:0.75rem;">
                                            <option value="0" <?= $row['status']==0?'selected':'' ?>>Chờ xử lý</option>
                                            <option value="1" <?= $row['status']==1?'selected':'' ?>>Xác nhận</option>
                                            <option value="2" <?= $row['status']==2?'selected':'' ?>>Giao xong</option>
                                            <option value="3" <?= $row['status']==3?'selected':'' ?>>Hủy đơn</option>
                                        </select>
                                        <input type="hidden" name="update_status" value="1">
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="7" style="text-align:center; padding:50px; color:var(--text-dim);">Không có đơn hàng nào khớp.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </section>
    </div>
</body>
</html>