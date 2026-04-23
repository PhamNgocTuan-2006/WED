<?php
session_start();
include "connect.php";

// 1. LẤY TỪ KHÓA TÌM KIẾM
$keyword = "";
if(isset($_GET['q'])) {
    $keyword = mysqli_real_escape_string($conn, $_GET['q']);
}

// 2. KIỂM TRA SẢN PHẨM TỒN TẠI (ĐỂ HIỆN BỘ LỌC)
$has_any_product = false;
$check_sql = "SELECT id FROM san_pham WHERE name LIKE '%$keyword%' LIMIT 1";
$check_run = mysqli_query($conn, $check_sql);
if ($check_run && mysqli_num_rows($check_run) > 0) {
    $has_any_product = true;
}

// 3. TRUY VẤN CHÍNH (LẤY GIÁ BÁN THỰC TẾ TỪ GIACA)
$sql_products = "SELECT s.*, g.cost_price, g.profit_margin, 
                 (g.cost_price * (1 + g.profit_margin/100)) as real_selling_price 
                 FROM san_pham s 
                 JOIN giaca g ON s.id = g.product_id 
                 WHERE s.name LIKE '%$keyword%'";

// --- XỬ LÝ CÁC BỘ LỌC (GIỮ NGUYÊN LOGIC CŨ) ---
if(isset($_GET['brands']) && !empty($_GET['brands'])) {
    $brand_ids = implode(',', array_map('intval', $_GET['brands']));
    $sql_products .= " AND s.brand_id IN ($brand_ids)";
}

if (isset($_GET['price']) && !empty($_GET['price'])) {
    $price_conditions = [];
    foreach ($_GET['price'] as $range) {
        if ($range === '1') { $price_conditions[] = "(g.cost_price * (1 + g.profit_margin/100) < 1000000)"; }
        else {
            $parts = explode('-', $range);
            if(count($parts) == 2) {
                $min = intval($parts[0]) * 1000000; 
                $max = intval($parts[1]) * 1000000;
                $price_conditions[] = "(g.cost_price * (1 + g.profit_margin/100) BETWEEN $min AND $max)";
            }
        }
    }
    if (!empty($price_conditions)) { $sql_products .= " AND (" . implode(' OR ', $price_conditions) . ")"; }
}

if (isset($_GET['specs']) && is_array($_GET['specs'])) {
    foreach ($_GET['specs'] as $spec_key => $spec_values) {
        if (!empty($spec_values)) {
            $safe_key = mysqli_real_escape_string($conn, $spec_key);
            $safe_vals = "'" . implode("','", array_map(function($val) use ($conn) { return mysqli_real_escape_string($conn, $val); }, $spec_values)) . "'";
            $sql_products .= " AND s.id IN (SELECT product_id FROM thong_so WHERE spec_key = '$safe_key' AND spec_value IN ($safe_vals))";
        }
    }
}

$result_products = mysqli_query($conn, $sql_products);
$total_results = ($result_products) ? mysqli_num_rows($result_products) : 0;
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/gamingmouse.css">
    <link rel="stylesheet" href="css/bootstrap-icons.css">
    <title>Tìm kiếm: <?= htmlspecialchars($keyword) ?></title>
</head>
<body>
    <header>
        <nav class="menu">
            <div class="logo"><a href="trangchu.php"><img src="image/test2.png" alt="logo" class="logo-img"></a></div>
            <div class="words">
                <a href="trangchu.php">Home</a>
                <div class="has-dropdown">
                    <a href="#">Gaming Gear ▾</a>
                    <ul class="dropdown-gaming">
                        <li><a href="gamingmouse.php">Chuột Gaming</a></li>
                        <li><a href="gamingkey.php">Bàn Phím Cơ HE</a></li> 
                    </ul>
                </div>
                <div class="has-dropdown"> 
                    <a href="#">Office Gear ▾</a>
                    <ul class="dropdown-gaming">
                        <li><a href="officemouse.php">Chuột Văn Phòng</a></li>
                        <li><a href="officekey.php">Bàn Phím Cơ Văn Phòng</a></li>
                    </ul>
                </div>
                <a href="mousepad.php">Mouse Pad</a>
            </div>
            <div class="icons">
                <form class="search-box" action="timkiem.php" method="get">
                    <a href="#" id="searchToggle"><i class="bi bi-search"></i></a>
                    <input type="text" id="searchInput" name="q" placeholder="Tìm..." value="<?= htmlspecialchars($keyword) ?>">
                </form>
                <div class="box-login">
                    <a href="#" id="Login"><i class="bi bi-person"></i></a>
                    <ul class="dropdown-menu">
                        <li><a href="thongtinkhachhang.php">Thông tin người dùng</a></li>
                        <li><a href="lichsumuahang.php">Đơn hàng của bạn</a></li>
                        <li><a href="dangnhap.php">Đăng xuất</a></li>
                    </ul>
                </div>
                <a href="cart.php"><i class="bi bi-bag"></i></a>       
            </div>
        </nav>
    </header>

    <div style="text-align: center; padding: 80px 0 30px 0;">
        <h2 style="font-size: 28px; font-weight: 600; color: #111;">
            <?= $total_results ?> kết quả cho "<span><?= htmlspecialchars($keyword) ?></span>"
        </h2>
    </div>

    <main style="min-height: 50vh;">
        <?php if ($has_any_product) : ?>
            <div class="loc"><a><i class="bi bi-filter-left"></i> Lọc</a></div>
        <?php endif; ?>

        <div class="main">
            <div class="khung_thanh_loc">
                <?php if ($has_any_product) : ?>
                <form action="timkiem.php" method="GET" id="filter">
                    <input type="hidden" name="q" value="<?= htmlspecialchars($keyword) ?>">
                    <div class="filter-box active">
                        <div class="filter-header"><span>Thương hiệu</span><span class="pointer">▾</span></div>
                        <div class="filter-content">
                            <?php
                            $brand_q = "SELECT th.id, th.name, COUNT(sp.id) as total 
                                        FROM thuong_hieu th JOIN san_pham sp ON th.id = sp.brand_id 
                                        WHERE sp.name LIKE '%$keyword%' GROUP BY th.id";
                            $brand_r = mysqli_query($conn, $brand_q);
                            while($brand = mysqli_fetch_assoc($brand_r)) {
                                $checked = (isset($_GET['brands']) && in_array($brand['id'], $_GET['brands'])) ? 'checked' : '';
                                echo '<label><input type="checkbox" name="brands[]" value="'.$brand['id'].'" onchange="this.form.submit()" '.$checked.'> '.$brand['name'].' ('.$brand['total'].')</label><br>';
                            }
                            ?>
                        </div>
                    </div>
                    </form>
                <?php endif; ?>
            </div>
    
            <div class="khung-list-product">
                <div class="list-product" <?= (!$has_any_product) ? 'style="justify-content: center; width: 100%;"' : '' ?>>
                    <?php if ($total_results > 0) : ?>
                        <?php while ($row = mysqli_fetch_assoc($result_products)) : 
                            // LOGIC ẢNH MẶC ĐỊNH
                            $img1 = (!empty($row['main_image']) && file_exists("image/".$row['main_image'])) ? $row['main_image'] : "anhgoc.png";
                            $img2 = (!empty($row['back_image']) && file_exists("image/".$row['back_image'])) ? $row['back_image'] : "anhgoc.png";
                        ?>
                            <div class="product_card">
                                <div class="img_product">
                                    <a href="chitietsanpham.php?id=<?= $row['id']; ?>"><img src="image/<?= $img1 ?>" class="product_img"></a>
                                    <a href="chitietsanpham.php?id=<?= $row['id']; ?>"><img src="image/<?= $img2 ?>" class="backproduct_img"></a>
                                </div>
                                <div class="info_container">
                                    <div class="name_product"><a href="chitietsanpham.php?id=<?= $row['id']; ?>"><?= $row['name']; ?></a></div>
                                    <div class="cost_product"><a><?= number_format($row['real_selling_price'], 0, ',', '.'); ?>₫</a></div>
                                    
                                    <div class="quick-btn-box">
                                        <a href="cart.php?action=add&id=<?= $row['id']; ?>" class="quick-btn">+ Thêm vào giỏ hàng</a>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else : ?>
                        <div style="grid-column: 1 / -1; text-align: center; padding: 60px 0;">
                            <img src="image/giohangtrong.jpg" style="max-width:200px; opacity:0.6;"><br>
                            <p style="font-size: 18px; color: #777; margin-top:20px;">Không tìm thấy linh kiện nào.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <footer class="footer">
        <div class="footer-bottom"><p>© 2026 Phong Cách Xanh. All rights reserved.</p></div>
    </footer>

    <script>
    document.addEventListener("DOMContentLoaded", function() {
        // Tương tác Search/Dropdown (Giữ nguyên giao diện cũ)
        const searchToggle = document.getElementById('searchToggle');
        const searchInput = document.getElementById('searchInput');
        if (searchToggle) {
            searchToggle.addEventListener('click', (e) => {
                e.preventDefault(); e.stopPropagation();
                searchInput.classList.toggle('active');
                if (searchInput.classList.contains('active')) searchInput.focus();
            });
        }
        document.querySelectorAll('.filter-header').forEach(h => {
            h.addEventListener('click', () => h.closest('.filter-box').classList.toggle('active'));
        });
    });
    </script>
</body> 
</html>