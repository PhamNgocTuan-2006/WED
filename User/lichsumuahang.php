<?php
session_start();
require_once 'connect.php';

// 1. Kiểm tra đăng nhập (Bắt buộc phải đăng nhập mới xem được lịch sử)
if (!isset($_SESSION["user_name"])) {
    header("Location: dangnhap.php");
    exit();
}

// 2. Lấy thông tin user đang đăng nhập
$user_name = $_SESSION["user_name"];
$sql_user = "SELECT * FROM users WHERE user_name='$user_name'";
$result_user = mysqli_query($conn, $sql_user);
$user = mysqli_fetch_assoc($result_user);

// Lấy đúng ID của người dùng hiện tại
$user_id = $user["id"]; 

// 3. Câu truy vấn lấy danh sách đơn hàng của user này, sắp xếp từ mới nhất đến cũ nhất
$sql = "SELECT 
            dh.id AS order_id, 
            dh.order_date, 
            dh.status, 
            dh.total_amount,
            SUM(ct.quantity) AS total_items,
            MIN(sp.main_image) AS rep_image,
            MIN(sp.id) AS rep_product_id
        FROM don_hang dh
        LEFT JOIN chi_tiet_don_hang ct ON dh.id = ct.order_id
        LEFT JOIN san_pham sp ON ct.product_id = sp.id
        WHERE dh.user_id = $user_id
        GROUP BY dh.id
        ORDER BY dh.order_date DESC";

$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/lichsumuahang.css">
    <link rel="stylesheet" href="css/bootstrap-icons.css">
    <title>Lịch sử đơn hàng</title>
    <style>
        /* Thêm một chút CSS để trạng thái đơn hàng có màu sắc phân biệt */
        .status-0 { color: #f39c12; } /* Chờ xử lý - Vàng */
        .status-1 { color: #3498db; } /* Đã xác nhận - Xanh dương */
        .status-2 { color: #2ecc71; } /* Đã giao - Xanh lá */
        .status-3 { color: #e74c3c; } /* Đã hủy - Đỏ */
        .empty-message { text-align: center; padding: 50px; font-size: 18px; color: #666; }
    </style>
</head>

<body>
    <header>
        <nav class="menu">
            <div class="containner1">
                <div class="logo">
                    <a href="trangchu.php">
                        <img src="image/logo2.avif" alt="logo" class="logo-img">
                    </a>
                </div>

                <div class="words">
                    <a href="trangchu.php">Trang chủ</a>
                    <a href="lichsumuahang.php">Đơn hàng</a>
                </div>
            </div>

            <div class="khachhang">
                <a href="#"><?php echo $user['user_name']; ?> ▾</a>
                <ul class="dropdown-menu">
                    <li><a href="thongtinkhachhang.php">Thông tin khách hàng</a></li>
                    <li><a href="trangchu.php?logout=true">Đăng xuất</a></li>
                </ul>
            </div>
        </nav>
    </header>
    <main>
        <div class="donhangsanpham">
            <a href="#">Đơn hàng của bạn</a>
        </div>
        <div class="khung-list-product">
            <div class="list-product">

                <?php 
                if (mysqli_num_rows($result) > 0) {
                    // Vòng lặp in ra từng đơn hàng
                    while ($row = mysqli_fetch_assoc($result)) {
                        
                        // Xử lý định dạng ngày (VD: 3 thg 5)
                        $date_formatted = date('j \t\h\g n', strtotime($row['order_date']));
                        
                        // Xử lý trạng thái đơn hàng (Dựa vào status trong DB)
                        $status_icon = "";
                        $status_text = "";
                        $status_class = "status-" . $row['status'];

                        switch ($row['status']) {
                            case 0:
                                $status_icon = "bi-clock-history";
                                $status_text = "Chờ xử lý";
                                break;
                            case 1:
                                $status_icon = "bi-truck";
                                $status_text = "Đang giao hàng";
                                break;
                            case 2:
                                $status_icon = "bi-check-circle";
                                $status_text = "Đã giao thành công";
                                break;
                            case 3:
                                $status_icon = "bi-x-circle";
                                $status_text = "Đã hủy";
                                break;
                        }
                ?>
                
                <div class="product_card">
                    <div class="time <?php echo $status_class; ?>">
                        <i class="bi <?php echo $status_icon; ?>"></i>
                        <a href="#" class="<?php echo $status_class; ?>"><?php echo $status_text; ?> <span><?php echo $date_formatted; ?></span></a>
                    </div>

                    <div class="img_product">
                        <a href="chitietdonhang.php?id=<?php echo $row['order_id']; ?>">
                            <img src="image/<?php echo $row['rep_image'] ? $row['rep_image'] : 'default_product.png'; ?>" alt="Product" class="product_img">
                        </a>
                    </div>

                    <div class="chitiet">
                        <a><?php echo $row['total_items'] ? $row['total_items'] : 0; ?> mặt hàng</a><br>
                        <span>Đơn hàng #<?php echo $row['order_id']; ?></span>
                    </div>

                    <div class="cost_product"><a><?php echo number_format($row['total_amount'], 0, ',', '.'); ?>₫</a></div>
                    <div class="muatlai">
                        <a href="chitietsanpham.php?id=<?php echo $row['rep_product_id']; ?>" class="muatlai">Mua lại</a>
                        <a href="chitietdonhang.php?id=<?php echo $row['order_id']; ?>" class="chitiet_btn">Chi tiết</a>
                    </div>
                </div>

                <?php 
                    } // Kết thúc while
                } else {
                    // Nếu khách hàng chưa mua đơn nào
                    echo "<div class='empty-message'>Bạn chưa có đơn hàng nào. <a href='trangchu.php' style='color:#007bff; text-decoration:underline;'>Tiếp tục mua sắm</a></div>";
                }
                ?>

            </div>
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