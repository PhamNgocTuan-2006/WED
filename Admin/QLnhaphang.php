<?php
if (session_status() == PHP_SESSION_NONE) session_start();
require_once 'config.php';

$keyword = $_GET['keyword'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 6;
$offset = ($page - 1) * $perPage;

date_default_timezone_set('Asia/Ho_Chi_Minh');

// 1. XỬ LÝ TÌM KIẾM
$where_sql = "";
if ($keyword !== '') {
    $kw = $conn->real_escape_string($keyword);
    $where_sql = "WHERE (import_date LIKE '%$kw%' OR import_batch LIKE '%$kw%')";
}

// 2. TRUY VẤN DỮ LIỆU
$countSql = "SELECT COUNT(*) AS total FROM (SELECT 1 FROM phieu_nhap_hang $where_sql GROUP BY import_date, import_batch) AS t";
$totalRows = $conn->query($countSql)->fetch_assoc()['total'];
$totalPages = ceil($totalRows / $perPage);

$sql = "SELECT MIN(id) AS view_id, import_date, import_batch, 
               COUNT(product_id) AS total_items, 
               SUM(quantity) AS total_qty, 
               SUM(import_price * quantity) AS total_amount, 
               MIN(status) AS batch_status
        FROM phieu_nhap_hang
        $where_sql
        GROUP BY import_date, import_batch
        ORDER BY import_date DESC, import_batch DESC
        LIMIT $offset, $perPage";

$result = $conn->query($sql);

include 'thanhmenu.php';
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quản lý Nhập hàng | Private Space</title>
    <style>
        .content-wrap { padding: 30px; max-width: 1400px; margin: 0 auto; }
        .card { background: var(--bg-card); border-radius: 16px; padding: 25px; border: 1px solid rgba(255,255,255,0.05); }
        .search-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
        .search-box { display: flex; gap: 10px; flex: 0.6; }
        .search-box input { flex: 1; padding: 12px; background: var(--bg-deep); border: 1px solid rgba(255,255,255,0.1); border-radius: 10px; color: white; }
        .table-ui { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .table-ui th { text-align: left; color: var(--text-dim); padding: 15px; font-size: 0.75rem; text-transform: uppercase; border-bottom: 2px solid rgba(255,255,255,0.05); }
        .table-ui td { padding: 15px; border-bottom: 1px solid rgba(255,255,255,0.05); vertical-align: middle; font-size: 0.9rem; }
        .badge { padding: 5px 12px; border-radius: 20px; font-size: 0.7rem; font-weight: 700; text-transform: uppercase; }
        .done { background: rgba(16, 185, 129, 0.1); color: #10b981; }
        .draft { background: rgba(251, 191, 36, 0.1); color: #fbbf24; }
        .btn-action { color: var(--accent-blue); text-decoration: none; font-weight: 600; margin-right: 15px; font-size: 0.85rem; transition: 0.3s; }
        .btn-action:hover { opacity: 0.7; }
        .btn-finalize { color: #10b981 !important; }
        .code-pill { font-family: monospace; background: var(--bg-deep); padding: 4px 8px; border-radius: 6px; color: var(--accent-blue); font-weight: 700; }
    </style>
</head>
<body>
    <div class="content-wrap">
        <header style="margin-bottom: 25px;">
            <h1 style="color: var(--accent-blue); margin: 0;">📦 Quản lý Nhập kho</h1>
            <p style="color: var(--text-dim); font-size: 0.9rem;">Lập phiếu và cập nhật giá vốn linh kiện</p>
        </header>

        <section class="card">
            <div class="search-row">
                <form class="search-box" method="GET">
                    <input type="text" name="keyword" placeholder="Tìm theo ngày hoặc mã phiếu..." value="<?= htmlspecialchars($keyword) ?>">
                    <button type="submit" style="background: var(--bg-deep); border: 1px solid rgba(255,255,255,0.1); color: white; padding: 0 20px; border-radius: 10px; cursor: pointer;">🔍</button>
                </form>
                <a href="themphieunhap.php" style="background: var(--accent-blue); color: white; padding: 12px 25px; border-radius: 10px; text-decoration: none; font-weight: 700;">+ LẬP PHIẾU NHẬP</a>
            </div>

            <table class="table-ui">
                <thead>
                    <tr>
                        <th>Mã Phiếu</th>
                        <th>Ngày Nhập</th>
                        <th>Số mặt hàng</th>
                        <th>Tổng số lượng</th>
                        <th>Tổng Tiền</th>
                        <th>Trạng Thái</th>
                        <th>Thao Tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result && $result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): 
                            $pn_code = "PN" . date('Ymd', strtotime($row['import_date'])) . "-" . str_pad($row['import_batch'], 2, '0', STR_PAD_LEFT);
                        ?>
                        <tr>
                            <td><span class="code-pill"><?= $pn_code ?></span></td>
                            <td>
                                <div style="font-weight:600;"><?= date('d/m/Y', strtotime($row['import_date'])) ?></div>
                                <small style="color:var(--text-dim);">Lô: <?= $row['import_batch'] ?></small>
                            </td>
                            <td><?= $row['total_items'] ?> linh kiện</td>
                            <td><span style="font-weight:700;"><?= number_format($row['total_qty']) ?></span></td>
                            <td><div style="color:#fbbf24; font-weight:700;"><?= number_format($row['total_amount']) ?>đ</div></td>
                            <td>
                                <?php if ($row['batch_status'] == 1): ?>
                                    <span class="badge done">● Hoàn thành</span>
                                <?php else: ?>
                                    <span class="badge draft">● Bản nháp</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($row['batch_status'] == 0): ?>
                                    <a href="suaphieunhap.php?date=<?= $row['import_date'] ?>&batch=<?= $row['import_batch'] ?>" class="btn-action">✏️ Sửa</a>
                                    <a href="xuly_nhaphang.php?finalize=1&date=<?= $row['import_date'] ?>&batch=<?= $row['import_batch'] ?>" 
                                       class="btn-action btn-finalize" 
                                       onclick="return confirm('Chốt phiếu sẽ cập nhật GIÁ VỐN BÌNH QUÂN và KHO. Tiếp tục?')">✅ Hoàn tất</a>
                                <?php else: ?>
                                    <a href="phieunhapdone.php?date=<?= $row['import_date'] ?>&batch=<?= $row['import_batch'] ?>" class="btn-action">🔎 Xem</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="7" style="text-align: center; padding: 40px; color: var(--text-dim);">Chưa có dữ liệu phiếu nhập.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <?php if($totalPages > 1): ?>
            <div style="margin-top: 30px; display: flex; gap: 8px; justify-content: center;">
                <?php for($p = 1; $p <= $totalPages; $p++): ?>
                    <a href="?page=<?= $p ?>&keyword=<?= urlencode($keyword) ?>" 
                       style="padding: 10px 18px; background: <?= ($p == $page) ? 'var(--accent-blue)' : 'rgba(255,255,255,0.05)' ?>; color: white; text-decoration: none; border-radius: 8px; font-weight: 600;">
                        <?= $p ?>
                    </a>
                <?php endfor; ?>
            </div>
            <?php endif; ?>
        </section>
    </div>
</body>
</html>