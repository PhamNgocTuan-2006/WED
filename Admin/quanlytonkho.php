<?php
if (session_status() == PHP_SESSION_NONE) session_start();
require_once 'config.php';

/* ==========================================================
   1. XỬ LÝ LOGIC CẬP NHẬT NGƯỠNG CẢNH BÁO
   ========================================================== */
if (isset($_POST['update_threshold'])) {
    $p_id = intval($_POST['product_id']);
    $threshold = intval($_POST['min_threshold']);
    $conn->query("INSERT INTO kho_canh_bao (product_id, min_threshold) VALUES ($p_id, $threshold) 
                  ON DUPLICATE KEY UPDATE min_threshold = $threshold");
    header("Location: quanlytonkho.php?tab=canhbao&msg=updated"); 
    exit();
}

/* ==========================================================
   2. KHỞI TẠO CÁC THAM SỐ LỌC & TAB
   ========================================================== */
$tab = $_GET['tab'] ?? 'canhbao';

// Tab Nhập - Xuất
$from_date = $_GET['from_date'] ?? date('Y-m-01');
$to_date = $_GET['to_date'] ?? date('Y-m-d');

// Tab Tra cứu quá khứ (Chỉ lấy Ngày)
$target_date = $_GET['target_date'] ?? date('Y-m-d'); 
$end_of_day = $target_date . ' 23:59:59'; // Chốt số liệu đến cuối ngày

include 'thanhmenu.php';
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quản lý Tồn kho & Báo cáo | Private Space</title>
    <style>
        .container { padding: 30px; max-width: 1400px; margin: 0 auto; }
        .card { background: var(--bg-card); border-radius: 16px; padding: 25px; border: 1px solid rgba(255,255,255,0.05); }
        
        /* HỆ THỐNG PHÂN MỤC TABS */
        .tab-menu { display: flex; gap: 10px; margin-bottom: 25px; border-bottom: 1px solid #334155; padding-bottom: 15px; }
        .tab-link { 
            padding: 12px 25px; border-radius: 10px; color: var(--text-dim); 
            text-decoration: none; font-weight: 700; font-size: 0.85rem; 
            background: rgba(255,255,255,0.02); transition: 0.3s;
        }
        .tab-link.active { background: var(--accent-blue); color: white; }
        .tab-link:hover:not(.active) { background: rgba(59, 130, 246, 0.1); color: var(--accent-blue); }

        /* THANH TÌM KIẾM SẢN PHẨM */
        .search-container { margin-bottom: 20px; position: relative; }
        .search-input { 
            width: 100%; padding: 15px 45px; background: #0f172a; 
            border: 2px solid #334155; border-radius: 12px; color: white; font-size: 0.9rem;
        }
        .search-input:focus { border-color: var(--accent-blue); outline: none; }
        .search-icon { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: var(--text-dim); }

        /* GIAO DIỆN BẢNG DỮ LIỆU */
        .table-ui { width: 100%; border-collapse: collapse; }
        .table-ui th { text-align: left; padding: 15px; font-size: 0.7rem; color: var(--text-dim); text-transform: uppercase; border-bottom: 2px solid #334155; }
        .table-ui td { padding: 15px; border-bottom: 1px solid rgba(255,255,255,0.05); font-size: 0.9rem; }

        .btn-save { background: var(--accent-blue); color: white; border: none; padding: 8px 15px; border-radius: 6px; cursor: pointer; font-weight: 700; }
        .badge-alert { background: rgba(239, 68, 68, 0.1); color: #ef4444; padding: 4px 10px; border-radius: 6px; font-weight: 700; font-size: 0.75rem; border: 1px solid rgba(239,68,68,0.3); }
        
        input[type="date"], input[type="number"] { background: #0f172a; border: 1px solid #334155; color: white; padding: 8px; border-radius: 6px; }
    </style>
</head>
<body>
    <div class="container">
        <header style="margin-bottom: 20px;">
            <h1 style="color: var(--accent-blue); margin: 0;">📊 Quản lý Kho & Thống kê</h1>
            <p style="color:var(--text-dim); font-size:0.85rem;">Phân tích tồn kho dựa trên lịch sử giao dịch thực tế.</p>
        </header>

        <nav class="tab-menu">
            <a href="?tab=canhbao" class="tab-link <?= $tab == 'canhbao' ? 'active' : '' ?>">🚨 CẢNH BÁO HẾT HÀNG</a>
            <a href="?tab=nhapxuat" class="tab-link <?= $tab == 'nhapxuat' ? 'active' : '' ?>">📈 BÁO CÁO NHẬP - XUẤT</a>
            <a href="?tab=tracuu" class="tab-link <?= $tab == 'tracuu' ? 'active' : '' ?>">🕒 TRA CỨU QUÁ KHỨ</a>
        </nav>

        <section class="card">
            
            <?php if($tab == 'canhbao'): ?>
                <div class="search-container">
                    <span class="search-icon">🔍</span>
                    <input type="text" id="pSearch" class="search-input" placeholder="Tìm kiếm linh kiện để điều chỉnh ngưỡng cảnh báo..." onkeyup="searchProduct()">
                </div>

                <table class="table-ui" id="pTable">
                    <thead>
                        <tr>
                            <th>Sản phẩm</th>
                            <th>Tồn kho hiện tại</th>
                            <th>Ngưỡng sắp hết</th>
                            <th>Trạng thái</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $res = $conn->query("SELECT s.id, s.name, s.stock, c.min_threshold 
                                             FROM san_pham s 
                                             LEFT JOIN kho_canh_bao c ON s.id = c.product_id 
                                             ORDER BY s.stock ASC");
                        while($r = $res->fetch_assoc()):
                            $threshold = $r['min_threshold'] ?? 10;
                            $is_low = ($r['stock'] <= $threshold);
                        ?>
                        <tr>
                            <td class="p-name"><strong><?= $r['name'] ?></strong></td>
                            <td style="font-weight:700; color:<?= $is_low ? '#ef4444' : '#10b981' ?>;"><?= $r['stock'] ?> cái</td>
                            <form method="POST">
                                <input type="hidden" name="product_id" value="<?= $r['id'] ?>">
                                <td><input type="number" name="min_threshold" value="<?= $threshold ?>" style="width:70px; text-align:center;"></td>
                                <td><?= $is_low ? '<span class="badge-alert">SẮP HẾT HÀNG</span>' : '<span style="color:#10b981; font-size:0.75rem;">● An toàn</span>' ?></td>
                                <td><button type="submit" name="update_threshold" class="btn-save">LƯU</button></td>
                            </form>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>

            <?php elseif($tab == 'nhapxuat'): ?>
                <form method="GET" style="display:flex; gap:15px; margin-bottom:25px; align-items:flex-end;">
                    <input type="hidden" name="tab" value="nhapxuat">
                    <div><label style="font-size:0.7rem; color:var(--text-dim); font-weight:700;">TỪ NGÀY</label><br><input type="date" name="from_date" value="<?= $from_date ?>"></div>
                    <div><label style="font-size:0.7rem; color:var(--text-dim); font-weight:700;">ĐẾN NGÀY</label><br><input type="date" name="to_date" value="<?= $to_date ?>"></div>
                    <button type="submit" class="btn-save" style="padding:10px 25px;">XEM BÁO CÁO</button>
                </form>
                
                <table class="table-ui">
                    <thead><tr><th>Sản phẩm</th><th>Tổng Nhập</th><th>Tổng Xuất</th><th>Tồn hiện tại</th></tr></thead>
                    <tbody>
                        <?php
                        $res = $conn->query("SELECT s.name, s.stock,
                            (SELECT IFNULL(SUM(change_quantity),0) FROM nhat_ky_kho WHERE product_id = s.id AND transaction_type='NHAP' AND created_at BETWEEN '$from_date 00:00:00' AND '$to_date 23:59:59') as nhap,
                            (SELECT ABS(IFNULL(SUM(change_quantity),0)) FROM nhat_ky_kho WHERE product_id = s.id AND transaction_type='XUAT' AND created_at BETWEEN '$from_date 00:00:00' AND '$to_date 23:59:59') as xuat
                            FROM san_pham s ORDER BY s.name ASC");
                        while($r = $res->fetch_assoc()):
                        ?>
                        <tr>
                            <td><?= $r['name'] ?></td>
                            <td style="color:#10b981; font-weight:700;">+<?= number_format($r['nhap']) ?></td>
                            <td style="color:#ef4444; font-weight:700;">-<?= number_format($r['xuat']) ?></td>
                            <td><strong><?= number_format($r['stock']) ?></strong></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>

            <?php elseif($tab == 'tracuu'): ?>
                <form method="GET" style="display:flex; gap:15px; margin-bottom:25px; align-items:flex-end;">
                    <input type="hidden" name="tab" value="tracuu">
                    <div style="flex:1;"><label style="font-size:0.7rem; color:var(--text-dim); font-weight:700;">TRA CỨU TỒN KHO TÍNH ĐẾN CUỐI NGÀY</label><br>
                    <input type="date" name="target_date" value="<?= $target_date ?>" style="width:100%;"></div>
                    <button type="submit" class="btn-save" style="padding:10px 25px;">KIỂM TRA SỐ DƯ</button>
                </form>

                <p style="font-size:0.85rem; color:var(--text-dim); margin-bottom:15px;">Dữ liệu tồn kho chốt vào lúc: <b style="color:white;"><?= date('23:59:59 - d/m/Y', strtotime($target_date)) ?></b></p>
                
                <table class="table-ui">
                    <thead><tr><th>Sản phẩm linh kiện</th><th>Số lượng tồn kho khi đó</th></tr></thead>
                    <tbody>
                        <?php
                        // Logic: Tổng tất cả biến động kho cho tới cuối ngày được chọn
                        $res = $conn->query("SELECT s.name, (SELECT IFNULL(SUM(change_quantity),0) FROM nhat_ky_kho WHERE product_id = s.id AND created_at <= '$end_of_day') as past_stock FROM san_pham s ORDER BY s.name ASC");
                        while($r = $res->fetch_assoc()):
                        ?>
                        <tr>
                            <td><?= $r['name'] ?></td>
                            <td style="font-weight:800; color:var(--accent-blue);"><?= number_format($r['past_stock']) ?> cái</td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php endif; ?>

        </section>
    </div>

    <script>
    function searchProduct() {
        const input = document.getElementById("pSearch");
        const filter = input.value.toUpperCase();
        const table = document.getElementById("pTable");
        const tr = table.getElementsByTagName("tr");

        for (let i = 1; i < tr.length; i++) {
            const td = tr[i].getElementsByClassName("p-name")[0];
            if (td) {
                const txtValue = td.textContent || td.innerText;
                if (txtValue.toUpperCase().indexOf(filter) > -1) {
                    tr[i].style.display = "";
                } else {
                    tr[i].style.display = "none";
                }
            }
        }
    }
    </script>
</body>
</html>