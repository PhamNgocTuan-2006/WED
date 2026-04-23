<?php
session_start(); 
require_once 'connect.php';

// --- 1. XỬ LÝ GIỎ HÀNG (POST) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $sp_id = intval($_POST['product_id']);
    $qty = intval($_POST['qty']);

    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }

    if (isset($_POST['buy_now'])) {
        $_SESSION['mua_ngay'] = [ $sp_id => $qty ];
        header("Location: muangay.php");
        exit();
    } 
    else if (isset($_POST['add_to_cart'])) {
        if (isset($_SESSION['cart'][$sp_id])) {
            $_SESSION['cart'][$sp_id] += $qty;
        } else {
            $_SESSION['cart'][$sp_id] = $qty;
        }
        header("Location: cart.php");
        exit();
    }
}

// --- 2. LẤY THÔNG TIN SẢN PHẨM ---
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0) {
    die("<h2 style='text-align:center; margin-top:50px;'>ID sản phẩm không hợp lệ.</h2>");
}

// Truy vấn kết hợp lấy Stock từ bảng giaca và thông tin từ bảng san_pham
$sql_product = "SELECT s.*, g.stock 
                FROM san_pham s 
                LEFT JOIN giaca g ON s.id = g.product_id 
                WHERE s.id = $id";

$result_product = mysqli_query($conn, $sql_product);

if (mysqli_num_rows($result_product) > 0) {
    $product = mysqli_fetch_assoc($result_product);
} else {
    die("<h2 style='text-align:center; margin-top:50px;'>Sản phẩm không tồn tại hoặc đã ngừng kinh doanh.</h2>");
}

// Lấy thông số kỹ thuật
$sql_specs = "SELECT * FROM thong_so WHERE product_id = $id";
$result_specs = mysqli_query($conn, $sql_specs);

// Xử lý ảnh hiển thị (Mặc định anhgoc.png nếu trống)
$main_img = (!empty($product['main_image']) && file_exists("image/" . $product['main_image'])) ? $product['main_image'] : "anhgoc.png";
$back_img = (!empty($product['back_image']) && file_exists("image/" . $product['back_image'])) ? $product['back_image'] : "";
?>

<!doctype html>
<html lang="vi">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" href="css/style.css" />
    <link rel="stylesheet" href="css/bootstrap-icons.css" />
    <title><?php echo htmlspecialchars($product['name']); ?></title>
    <style>
        .product__buy button { border: none; cursor: pointer; font-family: inherit; font-size: 16px; display: inline-block; text-align: center; }
        .status-badge { font-weight: 700; margin-bottom: 15px; display: block; }
    </style>
  </head>
  <body>
    <header>
      <nav class="menu">
        <div class="logo">
          <a href="trangchu.php"><img src="image/test2.png" alt="logo" class="logo-img" /></a>
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
            <input type="text" id="searchInput" name="q" placeholder="Tìm..." />
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

    <main>
      <div class="product">
        <div class="product__image">
          <div class="image-thumbs">
            <img src="image/<?php echo $main_img; ?>" alt="Ảnh chính" />
            <?php if ($back_img) { ?>
                <img src="image/<?php echo $back_img; ?>" alt="Ảnh phụ" />
            <?php } ?>
          </div>
          <div class="image-main">
            <img src="image/<?php echo $main_img; ?>" alt="<?php echo $product['name']; ?>" />
          </div>
        </div>

        <div class="product__details">
          <h1 class="product__title"><?php echo $product['name']; ?></h1>

          <div class="product__price"><?php echo number_format($product['price'], 0, ',', '.'); ?>₫</div>

          <div class="product__summary">
            <?php if(!empty($product['description'])) { ?>
                <p><strong><?php echo $product['description']; ?></strong></p>
            <?php } ?>
            
            <p><strong>Tóm tắt thông tin:</strong></p>
            <ul class="product__features">
              <?php 
                if(mysqli_num_rows($result_specs) > 0) {
                    while($spec = mysqli_fetch_assoc($result_specs)) {
                        echo "<li>" . $spec['spec_key'] . ": " . $spec['spec_value'] . "</li>";
                    }
                } else {
                    echo "<li>Đang cập nhật thông số...</li>";
                }
              ?>
            </ul>
            <p><strong>Bảo hành: 12 tháng đổi mới</strong></p>
          </div>
        
          <div class="product__status">
            <?php if ($product['stock'] > 0) { ?>
                <span class="status-badge" style="color: #10b981;">✔ Còn hàng (<?php echo $product['stock']; ?>)</span>
            <?php } else { ?>
                <span class="status-badge" style="color: #ef4444;">✖ Tạm hết hàng</span>
            <?php } ?>
          </div>

          <form action="" method="POST">
              <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
              
              <div class="product__quantity">
                <label for="qty"><strong>Số lượng:</strong></label>
                <div class="quantity-box">
                  <input id="qty" name="qty" type="number" value="1" min="1" 
                         max="<?php echo ($product['stock'] > 0) ? $product['stock'] : 1; ?>" 
                         step="1" inputmode="numeric" />
                </div>
              </div>

              <div class="product__buy">
                <?php if ($product['stock'] > 0): ?>
                    <button type="submit" name="add_to_cart" class="btn-add" style="background:#1e293b; color:white; padding:15px 25px; border-radius:8px; margin-right:10px;">🛒 Thêm vào giỏ</button>
                    <button type="submit" name="buy_now" class="btn-buy" style="background:#3b82f6; color:white; padding:15px 40px; border-radius:8px;">⚡ Mua ngay</button>
                <?php else: ?>
                    <button type="button" class="btn-buy" style="background:#334155; cursor:not-allowed; width:100%; color:#94a3b8;">SẢN PHẨM ĐANG HẾT HÀNG</button>
                <?php endif; ?>
              </div>
          </form>

          <div class="product__extra">
            <div class="policy">
              <h3>🏷 Chính sách & Quyền lợi</h3>
              <ul>
                <li>Trả góp 0%</li>
                <li>Giao hàng trong 2h</li>
                <li>Bảo vệ giá, hoàn tiền trong 31 ngày</li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </main>

    <footer class="footer">
      <div class="footer-container">
        <div class="footer-about">
          <img src="image/test2.png" alt="logo" class="footer-logo" />
          <p>Hệ thống cung cấp Gaming Gear cao cấp - SGU Project 2026.</p>
        </div>
        <div class="footer-info">
          <h3>ĐỊA CHỈ</h3>
          <p>273 Đ. An Dương Vương, Phường 3, Quận 5, TP. Hồ Chí Minh</p>
        </div>
      </div>
      <div class="footer-bottom"><p>© 2026 Phong Cách Xanh. All rights reserved.</p></div>
    </footer>

    <script>
    document.addEventListener("DOMContentLoaded", function() {
        // XỬ LÝ DROPDOWNS & SEARCH (Đã tối ưu ổn định)
        const searchToggle = document.getElementById('searchToggle');
        const searchInput = document.getElementById('searchInput');
        if(searchToggle && searchInput) {
            searchToggle.addEventListener('click', (e) => {
                e.preventDefault(); e.stopPropagation();
                searchInput.classList.toggle('active');
                if (searchInput.classList.contains('active')) searchInput.focus();
            });
        }

        const navDropdowns = document.querySelectorAll('.has-dropdown');
        navDropdowns.forEach(dropdown => {
            dropdown.querySelector('a').addEventListener('click', (e) => {
                e.preventDefault(); e.stopPropagation();
                navDropdowns.forEach(d => { if(d !== dropdown) d.classList.remove('active'); });
                dropdown.classList.toggle('active');
            });
        });

        const loginIcon = document.getElementById('Login');
        const boxLogin = document.querySelector('.box-login');
        if(loginIcon) {
            loginIcon.addEventListener('click', (e) => {
                e.preventDefault(); e.stopPropagation();
                boxLogin.classList.toggle('active');
            });
        }

        document.addEventListener('click', (e) => {
            if (searchInput) searchInput.classList.remove('active');
            if (boxLogin) boxLogin.classList.remove('active');
            navDropdowns.forEach(d => d.classList.remove('active'));
        });
    });
    </script>
  </body>
</html>