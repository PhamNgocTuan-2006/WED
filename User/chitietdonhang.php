<?php
session_start();
require_once 'connect.php';

// 1. Kiểm tra đăng nhập (Bắt buộc phải đăng nhập mới xem được chi tiết đơn)
if (!isset($_SESSION["user_name"])) {
    header("Location: dangnhap.php");
    exit();
}

// 2. Kiểm tra xem có truyền id đơn hàng trên URL không
if (!isset($_GET['id'])) {
    header("Location: lichsumuahang.php");
    exit();
}

$order_id = intval($_GET['id']);

// 3. Lấy thông tin user đang đăng nhập từ Session
$user_name = $_SESSION["user_name"];
$sql_user = "SELECT * FROM users WHERE user_name='$user_name'";
$result_user = mysqli_query($conn, $sql_user);
$user = mysqli_fetch_assoc($result_user);

// Lấy ĐÚNG ID của khách hàng đang xem
$user_id = $user["id"]; 

// 4. Truy vấn thông tin tổng quan của đơn hàng (Đảm bảo đơn hàng này thuộc về user_id hiện tại)
$sql_order = "SELECT dh.*, u.email 
              FROM don_hang dh 
              LEFT JOIN users u ON dh.user_id = u.id 
              WHERE dh.id = $order_id AND dh.user_id = $user_id";
$res_order = mysqli_query($conn, $sql_order);

if (mysqli_num_rows($res_order) == 0) {
    die("<h2 style='text-align:center; padding: 50px; font-family: sans-serif;'>Đơn hàng không tồn tại hoặc bạn không có quyền xem đơn hàng của người khác!</h2>");
}
$order = mysqli_fetch_assoc($res_order);

// 5. Truy vấn danh sách sản phẩm trong đơn hàng đó
$sql_items = "SELECT ct.quantity, ct.price_at_purchase, sp.name, sp.main_image, sp.id AS product_id
              FROM chi_tiet_don_hang ct 
              JOIN san_pham sp ON ct.product_id = sp.id 
              WHERE ct.order_id = $order_id";
$res_items = mysqli_query($conn, $sql_items);

// 6. Xử lý định dạng dữ liệu để hiển thị
$date_formatted = date('j \t\h\g n', strtotime($order['order_date']));
$full_date = date('j \t\h\g n, Y', strtotime($order['order_date']));
$tax_amount = round($order['total_amount'] / 11); // Giả sử VAT 10% đã bao gồm trong giá

// Xử lý logic hiển thị trạng thái
$status = $order['status'];
$status_label = "";
$status_icon = "";

switch ($status) {
    case 0:
        $status_label = "Đang chờ xử lý";
        $status_icon = "bi-clock-history";
        break;
    case 1:
        $status_label = "Đang trên đường";
        $status_icon = "bi-truck";
        break;
    case 2:
        $status_label = "Đã giao thành công";
        $status_icon = "bi-check2-circle";
        break;
    case 3:
        $status_label = "Đã hủy";
        $status_icon = "bi-x-circle";
        break;
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/bootstrap-icons.css">
    <link rel="stylesheet" href="css/chitietdonhang.css">
    <title>Chi tiết đơn hàng #<?php echo $order['id']; ?></title>
    <style>
        .tieu_de_nho { font-weight: bold; margin-bottom: 5px; color: #333; }
        .xam { color: #666; }
        .mua_lai a { text-decoration: none; color: inherit; }
    </style>
</head>

<body>
    <main class="trang_don_hang">
        <div class="tieu_de">
            <a href="lichsumuahang.php" class="quay_lai">←</a>
            <h1>Đơn hàng #<?php echo $order['id']; ?></h1>
            <span class="da_xac_nhan">Đặt vào <?php echo $full_date; ?></span>
        </div>

        <div class="khung_luoi">
            <section class="cot_trai">
                
                <div class="the trang_thai">
                    <div class="nhan"><?php echo $status_label; ?></div>
                    <ul class="tien_trinh">
                        <li>
                            <i class="bi bi-cart-check"></i>
                            <div>
                                <strong>Đã đặt hàng</strong>
                                <div class="mo_ta_nho"><?php echo $date_formatted; ?></div>
                            </div>
                        </li>
                        <li style="opacity: <?php echo ($status >= 1 && $status != 3) ? '1' : '0.5'; ?>">
                            <i class="bi bi-truck"></i>
                            <div>
                                <strong><?php echo ($status == 3) ? "Đã hủy" : "Đang giao / Xác nhận"; ?></strong>
                                <div class="mo_ta_nho">Cập nhật hệ thống</div>
                            </div>
                        </li>
                        <li style="opacity: <?php echo ($status == 2) ? '1' : '0.5'; ?>">
                            <i class="bi bi-check2-circle"></i>
                            <div>
                                <strong>Đã hoàn thành</strong>
                                <div class="mo_ta_nho">Giao hàng thành công</div>
                            </div>
                        </li>
                    </ul>
                </div>
                
                <div class="the grid_2cot">
                    <div class="cot">
                        <div class="tieu_de_nho">Thông tin liên hệ</div>
                        <div class="dong"><?php echo $order['customer_name']; ?></div>
                        <div class="dong"><?php echo $order['email'] ? $order['email'] : 'Không có email'; ?></div>
                        <div class="dong"><?php echo $order['phone']; ?></div>
                    </div>
                    <div class="cot">
                        <div class="tieu_de_nho">Thanh toán</div>
                        <div class="dong">Thanh toán khi nhận hàng (COD)</div>
                        <div class="dong xam"><?php echo number_format($order['total_amount'], 0, ',', '.'); ?> ₫ VND</div>
                        <div class="mo_ta_nho"><?php echo $date_formatted; ?></div>
                    </div>
                    <div class="cot">
                        <div class="tieu_de_nho">Địa chỉ giao hàng</div>
                        <div class="dong"><?php echo $order['customer_name']; ?></div>
                        <div class="dong"><?php echo $order['address']; ?></div>
                        <div class="dong"><?php echo $order['ward']; ?></div>
                        <div class="dong">Hồ Chí Minh</div>
                        <div class="dong">Việt Nam</div>
                    </div>
                    <div class="cot">
                        <div class="tieu_de_nho">Địa chỉ thanh toán</div>
                        <div class="dong"><?php echo $order['customer_name']; ?></div>
                        <div class="dong"><?php echo $order['address']; ?></div>
                        <div class="dong"><?php echo $order['ward']; ?></div>
                        <div class="dong">Hồ Chí Minh</div>
                        <div class="dong">Việt Nam</div>
                    </div>
                    <div class="cot day_du">
                        <div class="tieu_de_nho">Phương thức vận chuyển</div>
                        <div class="dong">Miễn phí HCM (trong ngày)</div>
                    </div>
                </div>
            </section>

            <aside class="cot_phai">
                <div class="the tom_tat_don">
                    
                    <?php 
                    $first_product_id = null; // Biến lưu id sản phẩm đầu tiên để gán cho nút Mua lại
                    while ($item = mysqli_fetch_assoc($res_items)): 
                        if ($first_product_id === null) $first_product_id = $item['product_id'];
                    ?>
                    <div class="dong_sp" style="margin-bottom: 15px;">
                        <div class="anh_sp">
                            <img src="image/<?php echo $item['main_image']; ?>" alt="Product">
                            <span class="so_luong"><?php echo $item['quantity']; ?></span>
                        </div>
                        <div class="noi_dung_sp">
                            <a href="chitietsanpham.php?id=<?php echo $item['product_id']; ?>" class="ten_sp"><?php echo $item['name']; ?></a>
                        </div>
                        <div class="gia_sp"><?php echo number_format($item['price_at_purchase'] * $item['quantity'], 0, ',', '.'); ?> ₫</div>
                    </div>
                    <?php endwhile; ?>

                    <div class="duong_ke"></div>

                    <div class="dong_tien">
                        <span>Tổng phụ</span>
                        <span><?php echo number_format($order['total_amount'], 0, ',', '.'); ?> ₫</span>
                    </div>
                    <div class="dong_tien">
                        <span>Vận chuyển</span>
                        <span>Miễn phí</span>
                    </div>

                    <div class="duong_ke"></div>

                    <div class="tong">
                        <div>
                            <div class="nho_xam">VND</div>
                            <div class="tong_so"><?php echo number_format($order['total_amount'], 0, ',', '.'); ?> ₫</div>
                            <div class="nho_xam">Bao gồm ~<?php echo number_format($tax_amount, 0, ',', '.'); ?> ₫ tiền thuế (10%)</div>
                        </div>
                    </div>
                </div>

                <?php if ($first_product_id): ?>
                    <button class="mua_lai"><a href="chitietsanpham.php?id=<?php echo $first_product_id; ?>">Mua lại</a></button>
                <?php endif; ?>
            </aside>
        </div>
    </main>
    <footer class="c">
        <div class="f">
            <a>Chính sách hoàn tiền</a>
            <a>Vận chuyển</a>
            <a>Chính sách quyền riêng tư </a>
            <a>Điều khoản dịch vụ</a>
        </div>
    </footer>
</body>
</html>