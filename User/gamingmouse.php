<?php
include "connect.php";

// 1. CÂU LỆNH TRUY VẤN CƠ BẢN (Category 1: Chuột Gaming)
$sql = "SELECT * FROM san_pham WHERE category_id = 1";

// 2. LỌC THEO THƯƠNG HIỆU
if(isset($_GET['brands']) && !empty($_GET['brands'])) {
    $brand_id = implode(',', array_map('intval', $_GET['brands']));
    $sql .= " AND brand_id IN ($brand_id)";
}

// 3. LỌC THEO THÔNG SỐ KỸ THUẬT ĐỘNG
if (isset($_GET['specs']) && is_array($_GET['specs'])) {
    foreach ($_GET['specs'] as $key => $values) {
        if (!empty($values)) {
            $val_list = "'" . implode("','", array_map(function($v) use ($conn) {
                return mysqli_real_escape_string($conn, $v);
            }, $values)) . "'";
            
            $safe_key = mysqli_real_escape_string($conn, $key);
            $sql .= " AND id IN (SELECT product_id FROM thong_so WHERE spec_key = '$safe_key' AND spec_value IN ($val_list))";
        }
    }
}

// 4. LỌC THEO GIÁ
if (isset($_GET['price']) && !empty($_GET['price'])) {
    $price_conditions = [];
    foreach ($_GET['price'] as $range) {
        if ($range == "under-1") {
            $price_conditions[] = "(price < 1000000)";
        } else {
            $parts = explode('-', $range);
            if(count($parts) == 2) {
                $min = intval($parts[0]); 
                $max = intval($parts[1]);
                $price_conditions[] = "(price BETWEEN $min AND $max)";
            }
        }
    }
    if (!empty($price_conditions)) $sql .= " AND (" . implode(' OR ', $price_conditions) . ")";
}

$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/gamingmouse.css">
    <link rel="stylesheet" href="css/bootstrap-icons.css">
    <title>Chuột Gaming - Phong Cách Xanh</title>
</head>
<body>
    <header>
        <nav class="menu">
            <div class="logo">
                <a href="trangchu.php"><img src="image/test2.png" alt="logo" class="logo-img"></a>
            </div>
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
                <form class="search-box" action="timkiem.php" method="GET">
                    <a href="#" id="searchToggle"><i class="bi bi-search"></i></a>
                    <input type="text" id="searchInput" name="q" placeholder="Tìm...">
                </form>
                <div class="box-login">
                    <a href="#" id="Login"><i class="bi bi-person"></i></a>
                    <ul class="dropdown-menu">
                        <li><a href="thongtinkhachhang.php">Thông tin người dùng</a></li>
                        <li><a href="lichsumuahang.php">Đơn hàng của bạn</a></li>
                        <li><a href="index.php">Đăng xuất</a></li>
                    </ul>
                </div>
                <a href="cart.php"><i class="bi bi-bag"></i></a>
            </div>
        </nav>
    </header>

    <section class="mota">
        <img src="image/tenz.webp" alt="" class="mota_img">
        <div class="mota-box">
            <p class="de">Chuột Gaming Chuẩn Bài – Cầm Vừa Tay, Bắn Vừa Tâm</p>
            <p>Không phải ai cũng aim giống nhau. Có người <strong>flick cổ tay</strong> lia mệt nghỉ, có người <strong>tracking mượt</strong> như lướt pad bằng lòng tin.</p>
            <p>Vậy nên tụi mình <strong>không chọn chuột theo trend</strong>.<br><strong>Cầm không ngon – click không đã – là gạch tên thẳng tay.</strong></p>
            <p>Ở đây, chuột <strong>không chỉ nhẹ</strong>, mà phải <strong>vừa tay – đúng gu – đủ ổn định</strong> để bắn nghiêm túc từng round.</p>
            <p>Không whiff vì gear. Không blame thiết bị. Chỉ còn lại bạn và crosshair.</p>
        </div>
    </section>

    <main>
        <div class="loc"><a><i class="bi bi-filter-left"></i></a><a> Lọc</a></div>
        <div class="main">
            <div class="khung_thanh_loc">
                <form action="" method="GET" id="filter">
                    <div class="filter-box">
                        <div class="filter-header">
                            <span class="filter-toggle">Thương hiệu</span>
                            <span class="pointer">▾</span>
                        </div>
                        <div class="filter-content">
                            <?php
                            $brand_query = "SELECT * FROM thuong_hieu";
                            $brand_run = mysqli_query($conn, $brand_query);
                            while($brandlist = mysqli_fetch_assoc($brand_run)) {
                                $brand_id = $brandlist['id'];
                                $checked = (isset($_GET['brands']) && in_array($brand_id, $_GET['brands'])) ? 'checked' : '';
                                echo '<label><input type="checkbox" name="brands[]" value="'.$brand_id.'" onchange="this.form.submit()" '.$checked.'> '.$brandlist['name'].'</label><br>';
                            }
                            ?>
                        </div>
                    </div>

                    <?php
                    $keys_query = "SELECT DISTINCT spec_key FROM danhmuc_spec_keys WHERE category_id = 1"; 
                    $keys_run = mysqli_query($conn, $keys_query);
                    while($key_row = mysqli_fetch_assoc($keys_run)):
                        $current_key = $key_row['spec_key'];
                    ?>
                        <div class="filter-box">
                            <div class="filter-header">
                                <span class="filter-toggle"><?= $current_key ?></span>
                                <span class="pointer">▾</span>
                            </div>
                            <div class="filter-content">
                                <?php
                                $val_query = "SELECT DISTINCT spec_value FROM thong_so WHERE spec_key = '$current_key' AND product_id IN (SELECT id FROM san_pham WHERE category_id = 1)";
                                $val_run = mysqli_query($conn, $val_query);
                                while($val_row = mysqli_fetch_assoc($val_run)):
                                    $val = $val_row['spec_value'];
                                    $checked = (isset($_GET['specs'][$current_key]) && in_array($val, $_GET['specs'][$current_key])) ? 'checked' : '';
                                ?>
                                    <label>
                                        <input type="checkbox" name="specs[<?= $current_key ?>][]" value="<?= $val ?>" onchange="this.form.submit()" <?= $checked ?>> 
                                        <?= $val ?>
                                    </label><br>
                                <?php endwhile; ?>
                            </div>
                        </div>
                    <?php endwhile; ?>

                    <div class="filter-box">
                        <div class="filter-header">
                            <span class="filter-toggle">Giá</span>
                            <span class="pointer">▾</span>
                        </div>
                        <div class="filter-content">
                            <label><input type="checkbox" name="price[]" value="under-1" onchange="this.form.submit()" <?= (isset($_GET['price']) && in_array("under-1", $_GET['price'])) ? 'checked' : '' ?>> Dưới 1 triệu</label><br>
                            <label><input type="checkbox" name="price[]" value="1000000-2000000" onchange="this.form.submit()" <?= (isset($_GET['price']) && in_array("1000000-2000000", $_GET['price'])) ? 'checked' : '' ?>> 1 - 2 triệu</label><br>
                            <label><input type="checkbox" name="price[]" value="2000000-3000000" onchange="this.form.submit()" <?= (isset($_GET['price']) && in_array("2000000-3000000", $_GET['price'])) ? 'checked' : '' ?>> 2 - 3 triệu</label><br>
                            <label><input type="checkbox" name="price[]" value="3000000-5000000" onchange="this.form.submit()" <?= (isset($_GET['price']) && in_array("3000000-5000000", $_GET['price'])) ? 'checked' : '' ?>> 3 - 5 triệu</label><br>
                        </div>
                    </div>
                </form>
            </div>

            <div class="khung-list-product">
                <div class="list-product">
                    <?php if (mysqli_num_rows($result) > 0) : ?>
                        <?php while ($row = mysqli_fetch_assoc($result)) : ?>
                            <div class="product_card">
                                <div class="img_product">
                                    <a href="chitietsanpham.php?id=<?= $row['id']; ?>"><img src="image/<?= $row['main_image']; ?>" alt="<?= $row['name']; ?>" class="product_img"></a>
                                    <a href="chitietsanpham.php?id=<?= $row['id']; ?>"><img src="image/<?= $row['back_image']; ?>" alt="<?= $row['name']; ?>" class="backproduct_img"></a>
                                </div>
                                <div class="info_container">
                                    <div class="name_product"><a href="chitietsanpham.php?id=<?= $row['id']; ?>"><?= $row['name']; ?></a></div>
                                    <div class="cost_product"><span><?= number_format((float) $row['price'], 0, ',', '.'); ?>₫</span></div>
                                    <div class="quick-btn-box">
                                        <a href="cart.php?action=add&id=<?= $row['id']; ?>" class="quick-btn">+ Thêm giỏ hàng</a>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else : ?>
                        <p style="width: 100%; text-align: center; padding-top: 100px; font-size: 20px;">Không tìm thấy sản phẩm phù hợp.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <footer class="footer">
        <div class="footer-container">
            <div class="footer-about">
                <img src="image/test2.png" alt="logo" class="footer-logo">
                <p>Trang web bán chuột và bàn phím chuyên cung cấp các mẫu gaming gear cao cấp, đảm bảo chất lượng và hiệu năng, với nhiều lựa chọn phù hợp phong cách của bạn.</p>
            </div>
            <div class="footer-info">
                <h3>ĐỊA CHỈ</h3>
                <p>273 Đ. An Dương Vương, Phường 3, Quận 5, TP. Hồ Chí Minh</p>
                <p>Việt Nam</p>
            </div>
            <div class="footer-info">
                <h3>LIÊN HỆ</h3>
                <p>SDT: 035 2006 9999</p>
                <p>SDT: 091 2006 6666</p>
            </div>
            <div class="footer-social">
                <h3>THEO DÕI CHÚNG TÔI</h3>
                <div class="social-icons">
                    <a href="#"><i class="bi bi-facebook"></i></a>
                    <a href="#"><i class="bi bi-youtube"></i></a>
                    <a href="#"><i class="bi bi-instagram"></i></a>
                </div>
            </div>
        </div>
        <div class="footer-bottom">
            <p>© 2025 Phong Cách Xanh. All rights reserved.</p>
        </div>
    </footer>    

    <script>
    document.addEventListener("DOMContentLoaded", function() {
        // 1. XỬ LÝ SEARCH TOGGLE
        const searchToggle = document.getElementById('searchToggle');
        const searchInput = document.getElementById('searchInput');
        if(searchToggle && searchInput) {
            searchToggle.addEventListener('click', function(e) {
                e.preventDefault(); e.stopPropagation(); 
                searchInput.classList.toggle('active');
                if (searchInput.classList.contains('active')) searchInput.focus();
            });
        }

        // 2. XỬ LÝ DROPDOWN NAV (Gaming Gear / Office Gear)
        const navDropdownLinks = document.querySelectorAll('.has-dropdown > a');
        navDropdownLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault(); e.stopPropagation();
                const parent = this.parentElement;
                // Đóng các menu khác
                document.querySelectorAll('.has-dropdown').forEach(d => { if(d !== parent) d.classList.remove('active'); });
                parent.classList.toggle('active');
            });
        });

        // 3. XỬ LÝ DROPDOWN LOGIN (Icon User)
        const loginIcon = document.getElementById('Login');
        const boxLogin = document.querySelector('.box-login');
        if(loginIcon && boxLogin) {
            loginIcon.addEventListener('click', function(e) {
                e.preventDefault(); e.stopPropagation();
                boxLogin.classList.toggle('active');
            });
        }

        // 4. XỬ LÝ THANH LỌC (ACCORDION)
        const filterHeaders = document.querySelectorAll('.filter-header');
        filterHeaders.forEach(header => {
            header.addEventListener('click', function() {
                this.closest('.filter-box').classList.toggle('active');
            });
        });

        // 5. ĐÓNG TẤT CẢ KHI CLICK RA NGOÀI
        document.addEventListener('click', function(e) {
            if (searchInput && !searchInput.contains(e.target) && e.target !== searchToggle) searchInput.classList.remove('active');
            if (boxLogin && !boxLogin.contains(e.target)) boxLogin.classList.remove('active');
            document.querySelectorAll('.has-dropdown').forEach(d => {
                if(!d.contains(e.target)) d.classList.remove('active');
            });
        });
    });
    </script>
</body>
</html>