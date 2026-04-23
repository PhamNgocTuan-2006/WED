<?php
session_start();
require_once 'connect.php';

// Khởi tạo giỏ hàng nếu chưa có
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// 1. XỬ LÝ THÊM SẢN PHẨM VÀO GIỎ HÀNG
if (isset($_GET['action']) && $_GET['action'] == 'add' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    if (isset($_SESSION['cart'][$id])) {
        $_SESSION['cart'][$id]++;
    } else {
        $_SESSION['cart'][$id] = 1;
    }
    header("Location: cart.php"); 
    exit();
}

// 2. XỬ LÝ XÓA 1 SẢN PHẨM
if (isset($_GET['action']) && $_GET['action'] == 'remove' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    if (isset($_SESSION['cart'][$id])) {
        unset($_SESSION['cart'][$id]);
    }
    header("Location: cart.php");
    exit();
}

// 3. XỬ LÝ XÓA TOÀN BỘ
if (isset($_GET['action']) && $_GET['action'] == 'clear') {
    unset($_SESSION['cart']); 
    header("Location: cart.php");
    exit();
}

// 4. XỬ LÝ CẬP NHẬT SỐ LƯỢNG
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_cart'])) {
    if (isset($_POST['qty'])) {
        foreach ($_POST['qty'] as $id => $quantity) {
            $qty = intval($quantity);
            if ($qty <= 0) {
                unset($_SESSION['cart'][$id]);
            } else {
                $_SESSION['cart'][$id] = $qty;
            }
        }
    }
    header("Location: cart.php");
    exit();
}

$is_cart_empty = empty($_SESSION['cart']);
$total_price = 0;
?>

<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Giỏ hàng của bạn | Phong Cách Xanh</title>
  <link rel="stylesheet" href="css/cart.css">
  <link rel="stylesheet" href="css/style.css"> 
  <link rel="stylesheet" href="css/bootstrap-icons.css">
  
  <style>
    .cart-item { display: flex; justify-content: space-between; align-items: center; padding: 15px 0; border-bottom: 1px solid #eee; }
    .cart-header { display: flex; justify-content: space-between; align-items: center; padding-bottom: 10px;}
    .col-product { flex: 2; display: flex; gap: 15px; align-items: center; text-align: left; }
    .col-quantity { flex: 1; text-align: center; }
    .col-total { flex: 1; text-align: right; color: #d70018; font-weight: bold; }
    .product-img { width: 80px; height: 80px; border-radius: 8px; border: 1px solid #ddd; object-fit: cover; background: #f9f9f9; }
    .product-info h4 { margin: 0 0 5px 0; font-size: 16px; font-weight: 700; color: #333; }
    .product-info p { margin: 0; color: #d70018; font-size: 14px; font-weight: 600; }
    
    .remove-btn { color: #d70018; text-decoration: none; font-size: 13px; margin-top: 8px; display: inline-block; padding: 4px 8px; border: 1px solid #f8d7da; border-radius: 4px; background: #fff3f4; transition: 0.2s; }
    .remove-btn:hover { background: #d70018; color: #fff; }
    
    .qty-input { width: 60px; padding: 6px; text-align: center; border: 1px solid #ccc; border-radius: 4px; }
    .btn-update { background: #333; color: #fff; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; transition: 0.3s; font-weight: 700; }
    .btn-update:hover { background: #000; }
    .btn-clear { text-decoration: none; color: #999; border: 1px solid #ccc; padding: 9px 20px; border-radius: 5px; font-weight: 600; transition: 0.3s; }
    .btn-clear:hover { background: #f8d7da; color: #d70018; border-color: #d70018; }
  </style>
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
                    <input type="text" id="searchInput" name="q" placeholder="Tìm...">
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

    <div class="container" style="max-width: 1200px; margin: 40px auto; padding: 0 20px;">
        <h1 style="font-size: 24px; margin-bottom: 20px; color: var(--accent-blue);">🛒 Giỏ hàng của bạn</h1>

        <div class="cart" style="display: flex; gap: 30px; flex-wrap: wrap;">
            <div class="cart-items" style="flex: 2; min-width: 60%;">
                <?php if ($is_cart_empty): ?>
                    <div style="text-align: center; padding: 60px 0; background: #fff; border-radius: 12px;">
                        <img src="image/giohangtrong.jpg" alt="Trống" style="max-width: 150px; margin-bottom: 20px; opacity: 0.5;">
                        <p style="color: #666;">Giỏ hàng của bạn đang trống.</p>
                        <a href="trangchu.php" style="display: inline-block; margin-top: 15px; color: #3b82f6; font-weight: 700;">Mua sắm ngay →</a>
                    </div>
                <?php else: ?>
                    <div class="cart-header">
                        <div class="col-product"><strong>Sản phẩm</strong></div>
                        <div class="col-quantity"><strong>Số lượng</strong></div>
                        <div class="col-total"><strong>Tổng</strong></div>
                    </div>
                    <form action="cart.php" method="POST">
                        <?php 
                        foreach ($_SESSION['cart'] as $id => $quantity): 
                            // SỬA LỖI: Lấy cột 'price' thay vì 'selling_price'
                            $sql = "SELECT id, name, price, main_image, stock FROM san_pham WHERE id = $id";
                            $result = mysqli_query($conn, $sql);
                            
                            if ($row = mysqli_fetch_assoc($result)):
                                $subtotal = $row['price'] * $quantity;
                                $total_price += $subtotal; 
                                
                                // LOGIC ẢNH MẶC ĐỊNH
                                $img_src = (!empty($row['main_image']) && file_exists("image/".$row['main_image'])) 
                                           ? "image/".$row['main_image'] : "image/anhgoc.png";
                        ?>
                            <div class="cart-item">
                                <div class="col-product">
                                    <img src="<?= $img_src ?>" class="product-img">
                                    <div class="product-info">
                                        <h4><?= $row['name'] ?></h4>
                                        <p><?= number_format($row['price'], 0, ',', '.') ?>₫</p>
                                        <a href="cart.php?action=remove&id=<?= $row['id'] ?>" class="remove-btn" onclick="return confirm('Xóa món này?')">
                                            <i class="bi bi-trash"></i> Xóa
                                        </a>
                                    </div>
                                </div>
                                <div class="col-quantity">
                                    <input type="number" name="qty[<?= $row['id'] ?>]" value="<?= $quantity ?>" min="1" max="<?= $row['stock'] ?>" class="qty-input">
                                </div>
                                <div class="col-total"><?= number_format($subtotal, 0, ',', '.') ?>₫</div>
                            </div>
                        <?php endif; endforeach; ?>
                        
                        <div style="display: flex; justify-content: space-between; margin-top: 30px;">
                            <a href="trangchu.php" style="text-decoration:none; color:#666; font-weight:600;">← Tiếp tục mua sắm</a>
                            <div style="display: flex; gap: 15px;">
                                <a href="cart.php?action=clear" class="btn-clear" onclick="return confirm('Xóa sạch giỏ hàng?')">Xóa tất cả</a>
                                <button type="submit" name="update_cart" class="btn-update">🔄 Cập nhật số lượng</button>
                            </div>
                        </div>
                    </form>
                <?php endif; ?>
            </div>

            <div class="summary" style="flex: 1; background: #f9f9f9; padding: 25px; border-radius: 12px; height: fit-content; min-width: 300px; border: 1px solid #eee;">
                <h3 style="margin-top:0; font-size: 18px;">Tạm tính</h3>
                <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                    <span>Tổng tiền hàng:</span>
                    <strong><?= number_format($total_price, 0, ',', '.') ?>₫</strong>
                </div>
                <hr style="border: 0; border-top: 1px solid #ddd; margin: 20px 0;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 25px;">
                    <span style="font-size: 18px; font-weight: 700;">Thành tiền:</span>
                    <span style="font-size: 20px; font-weight: 800; color: #d70018;"><?= number_format($total_price, 0, ',', '.') ?>₫</span>
                </div>
                
                <textarea placeholder="Ghi chú thêm cho shop..." style="width: 100%; padding: 12px; border-radius: 8px; border: 1px solid #ccc; margin-bottom: 20px; height: 80px;"></textarea>

                <?php if (!$is_cart_empty): ?>
                    <a href="thanhtoan.php" style="display: block; text-align: center; background: #d70018; color: white; text-decoration: none; padding: 15px; border-radius: 8px; font-weight: 700; font-size: 16px;">MUA NGAY</a>
                <?php else: ?>
                    <button disabled style="width: 100%; background: #ccc; color: #fff; padding: 15px; border-radius: 8px; border: none; cursor: not-allowed;">THANH TOÁN</button>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener("DOMContentLoaded", function() {
        const searchToggle = document.getElementById('searchToggle');
        const searchInput = document.getElementById('searchInput');
        if(searchToggle) {
            searchToggle.addEventListener('click', (e) => {
                e.preventDefault(); e.stopPropagation();
                searchInput.classList.toggle('active');
                if (searchInput.classList.contains('active')) searchInput.focus();
            });
        }

        const navDropdowns = document.querySelectorAll('.has-dropdown');
        navDropdowns.forEach(d => {
            d.querySelector('a').addEventListener('click', (e) => {
                e.preventDefault(); e.stopPropagation();
                navDropdowns.forEach(el => { if(el !== d) el.classList.remove('active'); });
                d.classList.toggle('active');
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

        document.addEventListener('click', () => {
            if(searchInput) searchInput.classList.remove('active');
            if(boxLogin) boxLogin.classList.remove('active');
            navDropdowns.forEach(d => d.classList.remove('active'));
        });
    });
    </script>
</body>
</html>