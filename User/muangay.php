<?php
session_start();
require_once 'connect.php';

// 1. Kiểm tra đăng nhập (Bắt buộc phải đăng nhập mới được mua hàng)
if (!isset($_SESSION["user_name"])) {
    header("Location: index.php");
    exit();
}

// 2. Kiểm tra session mua ngay. Nếu trống thì không cho truy cập, đẩy về trang chủ.
if (!isset($_SESSION['mua_ngay']) || empty($_SESSION['mua_ngay'])) {
    header("Location: trangchu.php");
    exit();
}

$error = "";
$success = false;
$order_id = 0;

// --- LẤY THÔNG TIN USER ĐANG ĐĂNG NHẬP ---
$user_name = $_SESSION["user_name"];
$sql_user = "SELECT * FROM users WHERE user_name='$user_name'";
$result_user = mysqli_query($conn, $sql_user);
$user = mysqli_fetch_assoc($result_user);
$user_id = $user["id"];

// --- LẤY DANH SÁCH TẤT CẢ ĐỊA CHỈ CỦA USER ---
$sql_diachi = "SELECT * FROM dia_chi WHERE user_id='$user_id' ORDER BY mac_dinh DESC";
$result_diachi = mysqli_query($conn, $sql_diachi);
$saved_addresses = [];
while($row = mysqli_fetch_assoc($result_diachi)) {
    $saved_addresses[] = $row;
}

// 3. Xử lý khi nhấn nút "Thanh toán ngay"
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['confirm_order'])) {
    $ho = mysqli_real_escape_string($conn, $_POST['ho']);
    $ten = mysqli_real_escape_string($conn, $_POST['ten']);
    $customer_name = $ho . " " . $ten;
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);
    $ward = mysqli_real_escape_string($conn, $_POST['ward']); 
    $total_amount = floatval($_POST['total_amount']);
    
    mysqli_begin_transaction($conn);

    try {
        // A. Lưu vào bảng don_hang 
        $sql_order = "INSERT INTO don_hang (user_id, customer_name, phone, address, ward, total_amount, order_date, status) 
                      VALUES ('$user_id', '$customer_name', '$phone', '$address', '$ward', '$total_amount', NOW(), 0)";
        
        if (mysqli_query($conn, $sql_order)) {
            $order_id = mysqli_insert_id($conn); 

            // B. Lưu chi tiết sản phẩm "Mua ngay" vào bảng chi_tiet_don_hang
            foreach ($_SESSION['mua_ngay'] as $product_id => $quantity) {
                // CHỈ CHỈNH SỬA TẠI ĐÂY: Lấy giá từ bảng giaca
                $sql_p = "SELECT cost_price, profit_margin FROM giaca WHERE product_id = $product_id";
                $res_p = mysqli_query($conn, $sql_p);
                $p_data = mysqli_fetch_assoc($res_p);
                $price_at_purchase = $p_data['cost_price'] * (1 + $p_data['profit_margin'] / 100);

                $sql_detail = "INSERT INTO chi_tiet_don_hang (order_id, product_id, quantity, price_at_purchase) 
                               VALUES ('$order_id', '$product_id', '$quantity', '$price_at_purchase')";
                mysqli_query($conn, $sql_detail);
            }

            mysqli_commit($conn); 
            // C. Xóa phiên mua ngay, giữ nguyên giỏ hàng chính
            unset($_SESSION['mua_ngay']); 
            $success = true;
        }
    } catch (Exception $e) {
        mysqli_rollback($conn); 
        $error = "Có lỗi xảy ra, vui lòng thử lại!";
    }
}

$total_checkout = 0;
?>

<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Mua ngay - Thanh toán</title>
  <link rel="stylesheet" href="css/thanhtoan.css">
  <style>
      .alert { padding: 20px; border-radius: 8px; margin-bottom: 20px; text-align: center; }
      .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
      .btn-home { display: inline-block; margin-top: 15px; padding: 10px 20px; background: #333; color: #fff; text-decoration: none; border-radius: 5px; }
      .nut_thanh_toan { border: none; cursor: pointer; display: block; width: 100%; text-align: center; line-height: 50px; font-weight: bold; }
      .select-address { width: 100%; padding: 12px; margin-bottom: 15px; border: 1px solid #ccc; border-radius: 4px; font-family: inherit; font-size: 14px; background-color: #f9f9f9; }
  </style>
</head>
<body>
  <div class="trang_thanh_toan">
    <div class="cot_trai" style="<?php echo $success ? 'width: 100%; max-width: 600px; margin: 0 auto; float: none;' : ''; ?>">
      <div class="logo">
        <a href="trangchu.php"><img src="image/logo2.avif" class="image_logo"></a>
        <div class="info_nd">
          <div class="t"><a href="thongtinkhachhang.php">THE</a></div>
          <div class="gmail"><?php echo htmlspecialchars($user['email']); ?></div>
        </div>
      </div>

      <?php if ($success): ?>
          <div class="alert alert-success">
              <h2>🎉 Đặt hàng thành công!</h2>
              <p>Mã đơn hàng của bạn là: #<?php echo $order_id; ?></p>
              <p>Chúng mình sẽ sớm liên hệ để xác nhận đơn hàng.</p>
              <a href="lichsumuahang.php" class="btn-home" style="margin-right: 10px;">Xem đơn hàng</a>
              <a href="trangchu.php" class="btn-home">Tiếp tục mua sắm</a>
          </div>
      <?php else: ?>

      <form action="muangay.php" method="POST">
          <div class="giao_hang">
            <h2 class="tieu_de">Giao hàng</h2>
            <div class="lua_chon_giao_hang">
              <label><input type="radio" name="shipping" checked> Vận chuyển </label>
              <label><input type="radio" name="shipping"> Nhận tại cửa hàng</label>
            </div>
          </div>

          <div class="lua_chon_thanh_toan">
            <h2 class="tieu_de">Phương thức thanh toán</h2>
            <label><input type="radio" name="thanhtoan" value="the"> OnePAY - Credit/ATM Card</label><br>
            <label><input type="radio" name="thanhtoan" value="cod" checked> Thanh toán khi nhận hàng (COD)</label>
          </div>

          <div class="dia_chi_thanh_toan">
            <h2 class="tieu_de">Thông tin nhận hàng</h2>

            <?php if(count($saved_addresses) > 0): ?>
<select id="choose_address" class="select-address">
                <?php foreach($saved_addresses as $idx => $addr): ?>
                    <option value="<?php echo $idx; ?>" 
                        data-ho="<?php echo htmlspecialchars($addr['ho']); ?>"
                        data-ten="<?php echo htmlspecialchars($addr['ten']); ?>"
                        data-diachi="<?php echo htmlspecialchars($addr['dia_chi']); ?>"
                        data-thanhpho="<?php echo htmlspecialchars($addr['thanh_pho']); ?>"
                        data-dienthoai="<?php echo htmlspecialchars($addr['dien_thoai']); ?>"
                        <?php echo ($addr['mac_dinh'] == 1) ? 'selected' : ''; ?>
                    >
                        🏠 <?php echo htmlspecialchars($addr['ho'] . ' ' . $addr['ten'] . ' - ' . $addr['dien_thoai']); ?> 
                        (<?php echo htmlspecialchars($addr['dia_chi']); ?>)
                        <?php echo ($addr['mac_dinh'] == 1) ? ' - [Mặc định]' : ''; ?>
                    </option>
                <?php endforeach; ?>
                <option value="new">➕ Nhập địa chỉ giao hàng mới (Khác)</option>
            </select>
            <?php else: ?>
                <p style="color: #d70018; font-size: 14px; margin-bottom: 10px;">Bạn chưa có địa chỉ lưu sẵn. Vui lòng nhập thông tin bên dưới:</p>
            <?php endif; ?>

            <div class="dong_nho">
              <input type="text" name="ten" id="input_ten" placeholder="Tên" required>
              <input type="text" name="ho" id="input_ho" placeholder="Họ" required>
            </div>

            <input type="text" name="address" id="input_address" placeholder="Địa chỉ giao hàng (Số nhà, Tên đường)" required>
            <input type="text" name="ward" id="input_ward" placeholder="Phường/Xã/Thành phố" required>
            <input type="text" name="phone" id="input_phone" placeholder="Số điện thoại" required>
            
            <input type="hidden" name="total_amount" id="input_total" value="0">

            <button type="submit" name="confirm_order" class="nut_thanh_toan">Thanh toán ngay</button>
          </div>
      </form>
      <?php endif; ?>
    </div>

    <?php if (!$success): ?>
    <div class="cot_phai">
      <div class="don_hang">
        <?php 
        $count = 0;
        // Kiểm tra an toàn biến $_SESSION['mua_ngay']
        if(isset($_SESSION['mua_ngay']) && is_array($_SESSION['mua_ngay'])) {
            foreach ($_SESSION['mua_ngay'] as $id => $qty): 
                // CHỈ CHỈNH SỬA TẠI ĐÂY: JOIN lấy giá bán thực tế
                $sql = "SELECT s.name, s.main_image, g.cost_price, g.profit_margin 
                        FROM san_pham s 
                        JOIN giaca g ON s.id = g.product_id 
                        WHERE s.id = $id";
                $res = mysqli_query($conn, $sql);
                if ($p = mysqli_fetch_assoc($res)):
                    $selling_price = $p['cost_price'] * (1 + $p['profit_margin'] / 100);
                    $sub = $selling_price * $qty;
                    $total_checkout += $sub;
                    $count += $qty;
        ?>
        <div class="hang_san_pham">
<img src="image/<?php echo $p['main_image']; ?>" alt="Sản phẩm">
          <div>
            <p><?php echo $p['name']; ?></p>
            <p class="mau">Số lượng: <?php echo $qty; ?></p>
          </div>
          <span class="gia"><?php echo number_format($sub, 0, ',', '.'); ?> đ</span>
        </div>
        <?php 
                endif; 
            endforeach; 
        }
        ?>

        <div class="tong_tien">
          <p>Số lượng: <span><?php echo $count; ?></span></p>
          <p>Tổng phụ: <span><?php echo number_format($total_checkout, 0, ',', '.'); ?> đ</span></p>
          <p>Phí vận chuyển: <span>Miễn phí</span></p>
          <hr>
          <p class="tong">Tổng: <strong><?php echo number_format($total_checkout, 0, ',', '.'); ?>đ</strong></p>
        </div>
      </div>
    </div>
    <?php endif; ?>

  </div>

  <script>
      // Gán tổng tiền vào ô input ẩn
      const inputTotal = document.getElementById('input_total');
      if (inputTotal) {
          inputTotal.value = "<?php echo $total_checkout; ?>";
      }

      // Logic xử lý Auto-fill Địa chỉ
      document.addEventListener('DOMContentLoaded', function() {
          const selectAddress = document.getElementById('choose_address');
          const inHo = document.getElementById('input_ho');
          const inTen = document.getElementById('input_ten');
          const inAddress = document.getElementById('input_address');
          const inWard = document.getElementById('input_ward');
          const inPhone = document.getElementById('input_phone');

          function updateFields() {
              if(!selectAddress) return;
              
              const selectedOpt = selectAddress.options[selectAddress.selectedIndex];
              
              if(selectedOpt.value === 'new') {
                  inHo.value = '';
                  inTen.value = '';
                  inAddress.value = '';
                  inWard.value = '';
                  inPhone.value = '';
                  inHo.focus();
              } else {
                  inHo.value = selectedOpt.getAttribute('data-ho');
                  inTen.value = selectedOpt.getAttribute('data-ten');
                  inAddress.value = selectedOpt.getAttribute('data-diachi');
                  inWard.value = selectedOpt.getAttribute('data-thanhpho');
                  inPhone.value = selectedOpt.getAttribute('data-dienthoai');
              }
          }

          if(selectAddress) {
              selectAddress.addEventListener('change', updateFields);
              updateFields(); // Chạy 1 lần lúc vừa load trang
          }
      });
  </script>

  <footer class="c">
    <div class="f">
      <a>Chính sách hoàn tiền</a>
      <a>Vận chuyển</a>
      <a>Chính sách quyền riêng tư</a>
      <a>Điều khoản dịch vụ</a>
    </div>
  </footer>
</body>
</html> 