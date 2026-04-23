<?php
// Kiểm soát đầu ra để đảm bảo trả về JSON sạch
ob_start(); 
if (session_status() == PHP_SESSION_NONE) session_start();
require_once 'config.php';

/* ==========================================================
   1. XỬ LÝ AJAX (LƯU TẠI CHỖ)
   ========================================================== */
if (isset($_POST['ajax_update'])) {
    // Xóa sạch mọi nội dung đệm trước đó
    ob_end_clean(); 
    header('Content-Type: application/json');

    $p_id = intval($_POST['product_id']);
    $new_cost = floatval($_POST['cost_price']);
    $new_margin = floatval($_POST['profit_margin']);

    // Tính toán giá bán mới
    $new_selling_price = $new_cost * (1 + $new_margin / 100);

    // Cập nhật Database (Cả 2 bảng để đồng bộ)
    $sql1 = "UPDATE giaca SET cost_price = $new_cost, profit_margin = $new_margin WHERE product_id = $p_id";
    $sql2 = "UPDATE san_pham SET price = $new_cost, profit_rate = $new_margin, selling_price = $new_selling_price WHERE id = $p_id";

    if ($conn->query($sql1) && $conn->query($sql2)) {
        echo json_encode([
            'status' => 'success', 
            'new_selling_price' => number_format($new_selling_price, 0, ',', '.') . 'đ'
        ]);
    } else {
        echo json_encode(['status' => 'error', 'message' => $conn->error]);
    }
    exit(); 
}

/* ==========================================================
   2. TRUY VẤN DỮ LIỆU HIỂN THỊ
   ========================================================== */
$keyword = $_GET['keyword'] ?? '';
$where = $keyword ? "WHERE s.name LIKE '%$keyword%' OR g.product_code LIKE '%$keyword%'" : "";
$sql = "SELECT s.id, s.name, s.main_image, g.product_code, g.cost_price, g.profit_margin 
        FROM san_pham s JOIN giaca g ON s.id = g.product_id $where ORDER BY g.product_code ASC";
$result = $conn->query($sql);

include 'thanhmenu.php';
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quản lý Giá | Private Space</title>
    <style>
        .container { padding: 30px; max-width: 1450px; margin: 0 auto; }
        .card { background: var(--bg-card); border-radius: 16px; padding: 25px; border: 1px solid rgba(255,255,255,0.05); }
        .table-ui { width: 100%; border-collapse: collapse; }
        .table-ui td, .table-ui th { padding: 15px; border-bottom: 1px solid rgba(255,255,255,0.05); vertical-align: middle; }
        .price-input { width: 120px; padding: 8px; background: #0f172a; border: 1px solid #334155; color: white; border-radius: 8px; font-family: monospace; }
        .margin-input { width: 70px; padding: 8px; background: #0f172a; border: 1px solid #334155; color: #10b981; border-radius: 8px; text-align: center; font-weight: 800; }
        .btn-save { background: var(--accent-blue); color: white; border: none; padding: 10px 18px; border-radius: 8px; cursor: pointer; font-weight: 700; transition: 0.3s; }
        .btn-save.success { background: #10b981 !important; }
        .selling-price-text { color: #fbbf24; font-weight: 800; font-family: monospace; }
    </style>
</head>
<body>
    <div class="container">
        <header style="margin-bottom: 25px;">
            <h1 style="color: var(--accent-blue); margin: 0;">💰 Giá Linh kiện</h1>
            <p style="color: var(--text-dim); font-size: 0.9rem;">Dữ liệu được lưu tức thì mà không nạp lại trang.</p>
        </header>

        <section class="card">
            <table class="table-ui">
                <thead>
                    <tr>
                        <th>Sản phẩm</th>
                        <th>Giá vốn (đ)</th>
                        <th>Lợi nhuận (%)</th>
                        <th>Giá bán dự kiến</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($r = $result->fetch_assoc()): 
                        $selling = $r['cost_price'] * (1 + $r['profit_margin'] / 100);
                    ?>
                    <tr id="row-<?= $r['id'] ?>">
                        <td>
                            <div style="display:flex; align-items:center; gap:12px;">
                                <img src="image/<?= $r['main_image'] ?>" style="width:40px; height:40px; border-radius:6px; object-fit:cover;" onerror="this.src='image/no-image.png'">
                                <div>
                                    <div style="font-weight:700; font-size:0.9rem;"><?= $r['name'] ?></div>
                                    <small style="color:var(--accent-blue);"><?= $r['product_code'] ?></small>
                                </div>
                            </div>
                        </td>
                        <td><input type="number" step="any" class="price-input" value="<?= (float)$r['cost_price'] ?>"></td>
                        <td><input type="number" step="0.1" class="margin-input" value="<?= $r['profit_margin'] ?>"></td>
                        <td class="selling-price-text" id="selling-<?= $r['id'] ?>"><?= number_format($selling, 0, ',', '.') ?>đ</td>
                        <td><button type="button" class="btn-save" onclick="saveData(<?= $r['id'] ?>, this)">LƯU GIÁ</button></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </section>
    </div>

    <script>
    async function saveData(id, btn) {
        const row = document.getElementById('row-' + id);
        const costPrice = row.querySelector('.price-input').value;
        const profitMargin = row.querySelector('.margin-input').value;

        const formData = new FormData();
        formData.append('ajax_update', '1');
        formData.append('product_id', id);
        formData.append('cost_price', costPrice);
        formData.append('profit_margin', profitMargin);

        const originalText = btn.innerText;
        btn.innerText = 'ĐANG LƯU...';
        btn.disabled = true;

        try {
            // Gửi yêu cầu đến chính file này (quanlygiaban.php)
            const response = await fetch('quanlygiaban.php', { method: 'POST', body: formData });
            
            // Lấy dữ liệu văn bản từ phản hồi
            const text = await response.text(); 
            
            try {
                const data = JSON.parse(text);
                if (data.status === 'success') {
                    document.getElementById('selling-' + id).innerText = data.new_selling_price;
                    btn.innerText = 'ĐÃ LƯU ✓';
                    btn.classList.add('success');
                    setTimeout(() => {
                        btn.innerText = originalText;
                        btn.classList.remove('success');
                        btn.disabled = false;
                    }, 1500);
                } else {
                    alert('Lỗi Server: ' + data.message);
                    btn.innerText = originalText;
                    btn.disabled = false;
                }
            } catch (jsonError) {
                console.error("Phản hồi không phải JSON:", text);
                alert("Lỗi: Hệ thống trả về dữ liệu không hợp lệ.");
                btn.innerText = originalText;
                btn.disabled = false;
            }
        } catch (error) {
            alert('Lỗi kết nối hệ thống!');
            btn.innerText = originalText;
            btn.disabled = false;
        }
    }
    </script>
</body>
</html>