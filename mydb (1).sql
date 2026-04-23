-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Máy chủ: 127.0.0.1
-- Thời gian đã tạo: Th4 09, 2026 lúc 04:16 PM
-- Phiên bản máy phục vụ: 10.4.32-MariaDB
-- Phiên bản PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Cơ sở dữ liệu: `mydb`
--

DELIMITER $$
--
-- Thủ tục
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `Sinh25DonHangNgauNhien` ()   BEGIN
    DECLARE i INT DEFAULT 1;
    DECLARE v_order_id INT;
    DECLARE v_user_id INT;
    DECLARE v_p_id INT;
    DECLARE v_p_price DECIMAL(15,2);
    DECLARE v_qty INT;
    DECLARE v_status TINYINT;

    WHILE i <= 25 DO
        SET v_user_id = FLOOR(10 + (RAND() * 11));
        SET v_status = FLOOR(RAND() * 4);
        
        -- Tạo đơn hàng
        INSERT INTO don_hang (user_id, customer_name, phone, address, ward, total_amount, order_date, status)
        SELECT id, user_name, '0912345678', 'Địa chỉ mẫu', 'Phường 4', 0, NOW(), v_status 
        FROM users WHERE id = v_user_id;
        
        SET v_order_id = LAST_INSERT_ID();

        -- CHỈ CHỌN SẢN PHẨM CÒN HÀNG (Stock > 5)
        SELECT id, selling_price INTO v_p_id, v_p_price 
        FROM san_pham 
        WHERE stock > 5 
        ORDER BY RAND() LIMIT 1;

        IF v_p_id IS NOT NULL THEN
            SET v_qty = FLOOR(1 + (RAND() * 2));
            
            INSERT INTO chi_tiet_don_hang (order_id, product_id, quantity, price_at_purchase)
            VALUES (v_order_id, v_p_id, v_qty, v_p_price);
            
            UPDATE don_hang SET total_amount = v_p_price * v_qty WHERE id = v_order_id;

            -- Nếu đơn hàng hợp lệ thì mới trừ kho
            IF v_status = 1 OR v_status = 2 THEN
                UPDATE san_pham SET stock = stock - v_qty WHERE id = v_p_id;
                INSERT INTO nhat_ky_kho (product_id, change_quantity, transaction_type, reference_id)
                VALUES (v_p_id, -v_qty, 'XUAT', v_order_id);
            END IF;
        END IF;

        SET i = i + 1;
        SET v_p_id = NULL; -- Reset để vòng lặp sau kiểm tra lại
    END WHILE;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SinhDuLieuNhapHang` ()   BEGIN
    DECLARE done INT DEFAULT FALSE;
    DECLARE p_id INT;
    DECLARE current_batch INT DEFAULT 10; -- Bắt đầu từ lô 10 để tránh trùng dữ liệu cũ của bạn
    DECLARE products_in_batch INT DEFAULT 0;
    DECLARE target_per_batch INT DEFAULT 0;
    
    -- Khai báo con trỏ để lặp qua tất cả sản phẩm trong bảng san_pham
    DECLARE cur CURSOR FOR SELECT id FROM san_pham;
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;

    OPEN cur;

    read_loop: LOOP
        FETCH cur INTO p_id;
        IF done THEN
            LEAVE read_loop;
        END IF;

        -- Logic gom nhóm cho 10 lô đầu tiên (mỗi lô 2-3 sản phẩm)
        IF current_batch < 20 THEN
            -- Nếu chưa quyết định số lượng sản phẩm cho lô này, chọn ngẫu nhiên 2 hoặc 3
            IF products_in_batch = 0 THEN
                SET target_per_batch = FLOOR(2 + (RAND() * 2)); 
            END IF;

            -- Thêm sản phẩm vào lô hiện tại
            INSERT INTO phieu_nhap_hang (import_date, import_batch, product_id, import_price, quantity, status)
            SELECT 
                '2026-04-08', 
                current_batch, 
                p_id, 
                (price * 0.75), -- Giá nhập giả định bằng 75% giá bán
                FLOOR(65 + (RAND() * 36)), -- Số lượng ngẫu nhiên từ 65 đến 100
                1 -- Trạng thái: Hoàn thành
            FROM san_pham WHERE id = p_id;

            SET products_in_batch = products_in_batch + 1;

            -- Nếu lô đã đủ số lượng sản phẩm mục tiêu (2 hoặc 3), chuyển sang lô tiếp theo
            IF products_in_batch >= target_per_batch THEN
                SET current_batch = current_batch + 1;
                SET products_in_batch = 0;
            END IF;
            
        ELSE
            -- Đối với tất cả sản phẩm còn lại: Mỗi sản phẩm 1 lô riêng biệt
            INSERT INTO phieu_nhap_hang (import_date, import_batch, product_id, import_price, quantity, status)
            SELECT 
                '2026-04-08', 
                current_batch, 
                p_id, 
                (price * 0.75), 
                FLOOR(65 + (RAND() * 36)), 
                1
            FROM san_pham WHERE id = p_id;
            
            SET current_batch = current_batch + 1;
        END IF;

    END LOOP;

    CLOSE cur;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `admin`
--

CREATE TABLE `admin` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `fullname` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_vietnamese_ci;

--
-- Đang đổ dữ liệu cho bảng `admin`
--

INSERT INTO `admin` (`id`, `username`, `password`, `email`, `created_at`, `fullname`) VALUES
(1, 'thuong_admin', '123', NULL, '2026-04-08 09:33:29', 'Thương'),
(2, 'van_admin', '123', NULL, '2026-04-08 09:33:29', 'Vân'),
(3, 'long_admin', '123', NULL, '2026-04-08 09:33:29', 'Long');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `chi_tiet_don_hang`
--

CREATE TABLE `chi_tiet_don_hang` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price_at_purchase` decimal(15,2) NOT NULL COMMENT 'Giá chốt lúc mua'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `chi_tiet_don_hang`
--

INSERT INTO `chi_tiet_don_hang` (`id`, `order_id`, `product_id`, `quantity`, `price_at_purchase`) VALUES
(1, 1, 1, 1, 3840000.00),
(2, 2, 29, 1, 3630000.00),
(3, 3, 35, 1, 1750000.00),
(4, 4, 33, 1, 4790000.00),
(5, 5, 39, 1, 2728000.00),
(6, 8, 60, 1, 3960000.00),
(7, 8, 70, 2, 2890000.00),
(8, 9, 35, 2, 1750000.00),
(9, 9, 37, 2, 3249000.00),
(10, 10, 29, 1, 3630000.00),
(11, 10, 49, 2, 1760000.00),
(12, 11, 62, 1, 1760000.00),
(13, 11, 64, 1, 6160000.00),
(14, 11, 1, 2, 4482989.98),
(15, 12, 72, 2, 2290000.00),
(16, 12, 35, 1, 1750000.00),
(17, 12, 39, 2, 2728000.00),
(18, 13, 43, 2, 1640000.00),
(19, 13, 34, 1, 1990000.00),
(20, 14, 76, 1, 1285000.00),
(21, 14, 64, 2, 6160000.00),
(22, 14, 41, 1, 3450000.00),
(23, 15, 64, 2, 6160000.00),
(24, 16, 71, 1, 1750000.00),
(25, 16, 75, 2, 2257500.00),
(26, 16, 61, 1, 4076923.08),
(27, 17, 71, 1, 1750000.00),
(28, 18, 74, 2, 2373160.00),
(29, 18, 42, 2, 2499000.00),
(30, 18, 46, 1, 490000.00),
(31, 19, 75, 1, 2257500.00),
(32, 19, 62, 1, 1760000.00),
(33, 20, 72, 1, 2290000.00),
(34, 20, 63, 2, 4950000.00),
(35, 20, 31, 1, 5500000.00),
(36, 21, 41, 2, 3450000.00),
(37, 22, 42, 2, 2499000.00),
(38, 23, 47, 1, 1430000.00),
(39, 24, 70, 2, 2890000.00),
(40, 24, 49, 1, 1760000.00),
(41, 25, 38, 1, 766500.00),
(42, 25, 44, 1, 4180000.00),
(43, 26, 49, 1, 1760000.00),
(44, 26, 74, 1, 2373160.00),
(45, 27, 36, 1, 1750000.00),
(46, 27, 60, 2, 3960000.00),
(47, 28, 63, 2, 4950000.00),
(48, 28, 30, 1, 3630000.00),
(49, 29, 49, 1, 1760000.00),
(50, 29, 48, 2, 1210000.00),
(51, 30, 64, 2, 6160000.00),
(52, 30, 48, 2, 1210000.00),
(53, 31, 46, 1, 490000.00),
(54, 31, 72, 1, 2290000.00),
(55, 31, 48, 1, 1210000.00),
(56, 32, 48, 2, 1210000.00),
(57, 32, 70, 1, 2890000.00),
(58, 32, 62, 2, 1760000.00),
(59, 33, 71, 2, 1750000.00),
(60, 34, 30, 2, 3630000.00),
(61, 35, 64, 1, 6160000.00),
(62, 36, 63, 1, 4950000.00),
(63, 37, 32, 2, 1390000.00),
(64, 38, 75, 1, 2257500.00),
(65, 39, 48, 2, 1210000.00),
(66, 40, 64, 1, 6160000.00),
(67, 41, 70, 1, 2890000.00),
(68, 42, 36, 2, 1750000.00),
(69, 43, 29, 1, 3630000.00),
(70, 44, 34, 2, 1990000.00),
(71, 45, 30, 2, 3630000.00),
(72, 46, 71, 2, 1750000.00),
(73, 47, 44, 2, 4180000.00),
(74, 48, 65, 2, 4070000.00),
(75, 49, 60, 2, 3960000.00),
(76, 50, 76, 1, 1285000.00),
(77, 51, 41, 1, 3450000.00),
(78, 52, 46, 2, 490000.00),
(79, 53, 75, 1, 2257500.00),
(80, 54, 40, 2, 2728000.00),
(81, 55, 48, 1, 1210000.00),
(82, 56, 32, 1, 1390000.00),
(83, 57, 30, 1, 3630000.00),
(84, 58, 35, 2, 1750000.00),
(85, 58, 36, 3, 1750000.00),
(86, 59, 35, 2, 1750000.00),
(87, 60, 70, 1, 2890000.00),
(88, 60, 35, 2, 1750000.00),
(89, 61, 35, 1, 1750000.00),
(90, 62, 35, 1, 1750000.00),
(91, 63, 35, 1, 1750000.00),
(92, 64, 1, 1, 4482989.98),
(93, 65, 44, 1, 4180000.00),
(94, 66, 49, 1, 1760000.00),
(95, 67, 1, 1, 4482989.98);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `danh_muc`
--

CREATE TABLE `danh_muc` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_vietnamese_ci;

--
-- Đang đổ dữ liệu cho bảng `danh_muc`
--

INSERT INTO `danh_muc` (`id`, `name`) VALUES
(1, 'Chuột Gaming'),
(2, 'Bàn Phím Gaming'),
(3, 'Chuột văn phòng'),
(4, 'Bàn phím văn phòng'),
(5, 'Pad chuột');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `dia_chi`
--

CREATE TABLE `dia_chi` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `ten` varchar(100) DEFAULT NULL,
  `ho` varchar(100) DEFAULT NULL,
  `dia_chi` varchar(255) DEFAULT NULL,
  `thanh_pho` varchar(100) DEFAULT NULL,
  `dien_thoai` varchar(20) DEFAULT NULL,
  `mac_dinh` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_vietnamese_ci;

--
-- Đang đổ dữ liệu cho bảng `dia_chi`
--

INSERT INTO `dia_chi` (`id`, `user_id`, `ten`, `ho`, `dia_chi`, `thanh_pho`, `dien_thoai`, `mac_dinh`) VALUES
(1, 21, 'TTT', 'LEWON T', '36', 'RAU MÁ', '0162222222', 1),
(2, 22, 'TTT', 'LEWON T', '36', 'RAU MÁ', '0162222222', 0),
(3, 22, 'ádsa', 'dsadasd', 'ádas', 'adad', '0162222222', 1);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `don_hang`
--

CREATE TABLE `don_hang` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL COMMENT 'Liên kết với bảng user',
  `customer_name` varchar(255) NOT NULL,
  `phone` varchar(15) NOT NULL,
  `address` varchar(255) NOT NULL,
  `ward` varchar(100) NOT NULL COMMENT 'Phường - Dùng để lọc/sắp xếp',
  `total_amount` decimal(15,2) NOT NULL,
  `order_date` datetime DEFAULT current_timestamp(),
  `status` tinyint(1) DEFAULT 0 COMMENT '0: Chưa xử lý, 1: Đã xác nhận, 2: Đã giao, 3: Đã hủy'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `don_hang`
--

INSERT INTO `don_hang` (`id`, `user_id`, `customer_name`, `phone`, `address`, `ward`, `total_amount`, `order_date`, `status`) VALUES
(1, 10, 'Nguyễn Văn A', '0901112223', '123 Cao Lỗ', 'Phường 4', 3840000.00, '2026-04-01 09:15:00', 3),
(2, 11, 'Trần Thị B', '0903334445', '45 An Dương Vương', 'Phường 3', 3630000.00, '2026-04-03 14:20:00', 1),
(3, 12, 'Lê Hoàng C', '0905556667', '789 Tạ Quang Bửu', 'Phường 5', 1750000.00, '2026-04-08 08:30:00', 0),
(4, 14, 'Hoàng Anh E', '0907778889', '12 Phạm Thế Hiển', 'Phường 4', 4790000.00, '2026-04-08 15:45:00', 3),
(5, 17, 'Ngô Bảo H', '0909990001', '102 Dương Bá Trạc', 'Phường 1', 2728000.00, '2026-04-08 20:10:00', 3),
(6, 19, 'bui_xuan_k', '0970482759', 'Số nhà ngẫu nhiên, TP.HCM', 'Phường 15', 0.00, '2026-04-07 19:29:07', 0),
(7, 17, 'ngo_bao_h', '0962359370', 'Địa chỉ ngẫu nhiên', 'Phường 5', 0.00, '2026-04-06 19:30:58', 0),
(8, 20, 'kaaaaaa', '0946019423', 'Địa chỉ ngẫu nhiên', 'Phường 1', 9740000.00, '2026-04-07 19:32:20', 1),
(9, 20, 'kaaaaaa', '0972704012', 'Địa chỉ ngẫu nhiên', 'Phường 4', 9998000.00, '2026-03-30 19:32:20', 3),
(10, 18, 'dang_quang_i', '0986592315', 'Địa chỉ ngẫu nhiên', 'Phường 15', 7150000.00, '2026-03-26 19:32:20', 2),
(11, 13, 'pham_minh_d', '0949111953', 'Địa chỉ ngẫu nhiên', 'Phường 1', 16885979.96, '2026-04-02 19:32:20', 0),
(12, 19, 'bui_xuan_k', '0941017280', 'Địa chỉ ngẫu nhiên', 'Phường 3', 11786000.00, '2026-04-02 19:32:20', 0),
(13, 20, 'kaaaaaa', '0959070368', 'Địa chỉ ngẫu nhiên', 'Phường 15', 5270000.00, '2026-03-29 19:32:20', 0),
(14, 10, 'nguyenvan_a', '0911014668', 'Địa chỉ ngẫu nhiên', 'Phường 1', 17055000.00, '2026-03-29 19:32:20', 0),
(15, 16, 'do_thu_g', '0988767727', 'Địa chỉ ngẫu nhiên', 'Phường 5', 12320000.00, '2026-04-07 19:32:20', 0),
(16, 14, 'hoang_anh_e', '0973839754', 'Địa chỉ ngẫu nhiên', 'Phường 3', 10341923.08, '2026-03-26 19:32:20', 3),
(17, 13, 'pham_minh_d', '0979916986', 'Địa chỉ ngẫu nhiên', 'Phường 15', 1750000.00, '2026-03-27 19:32:20', 2),
(18, 18, 'dang_quang_i', '0941741744', 'Địa chỉ ngẫu nhiên', 'Phường 4', 10234320.00, '2026-04-05 19:32:20', 3),
(19, 19, 'bui_xuan_k', '0963748567', 'Địa chỉ ngẫu nhiên', 'Phường 4', 4017500.00, '2026-04-01 19:32:20', 2),
(20, 13, 'pham_minh_d', '0942102617', 'Địa chỉ ngẫu nhiên', 'Phường 15', 17690000.00, '2026-03-26 19:32:20', 0),
(21, 19, 'bui_xuan_k', '0926387698', 'Địa chỉ ngẫu nhiên', 'Phường 3', 6900000.00, '2026-03-28 19:32:20', 1),
(22, 16, 'do_thu_g', '0987845324', 'Địa chỉ ngẫu nhiên', 'Phường 5', 4998000.00, '2026-03-29 19:32:20', 2),
(23, 13, 'pham_minh_d', '0919622694', 'Địa chỉ ngẫu nhiên', 'Phường 5', 1430000.00, '2026-04-06 19:32:20', 3),
(24, 20, 'kaaaaaa', '0987040968', 'Địa chỉ ngẫu nhiên', 'Phường 4', 7540000.00, '2026-03-30 19:32:20', 0),
(25, 19, 'bui_xuan_k', '0926916642', 'Địa chỉ ngẫu nhiên', 'Phường 4', 4946500.00, '2026-03-29 19:32:20', 3),
(26, 13, 'pham_minh_d', '0922270049', 'Địa chỉ ngẫu nhiên', 'Phường 5', 4133160.00, '2026-03-31 19:32:20', 1),
(27, 13, 'pham_minh_d', '0984310840', 'Địa chỉ ngẫu nhiên', 'Phường 3', 9670000.00, '2026-04-04 19:32:20', 2),
(28, 18, 'dang_quang_i', '0918418283', 'Địa chỉ ngẫu nhiên', 'Phường 1', 13530000.00, '2026-03-31 19:32:20', 0),
(29, 19, 'bui_xuan_k', '0982797900', 'Địa chỉ ngẫu nhiên', 'Phường 4', 4180000.00, '2026-04-03 19:32:20', 0),
(30, 20, 'kaaaaaa', '0940052868', 'Địa chỉ ngẫu nhiên', 'Phường 15', 14740000.00, '2026-03-31 19:32:20', 1),
(31, 18, 'dang_quang_i', '0943845688', 'Địa chỉ ngẫu nhiên', 'Phường 4', 3990000.00, '2026-03-26 19:32:20', 1),
(32, 14, 'hoang_anh_e', '0927422677', 'Địa chỉ ngẫu nhiên', 'Phường 5', 8830000.00, '2026-04-02 19:32:20', 1),
(33, 14, 'hoang_anh_e', '0912345678', 'Địa chỉ mẫu', 'Phường 4', 3500000.00, '2026-04-08 19:46:41', 1),
(34, 17, 'ngo_bao_h', '0912345678', 'Địa chỉ mẫu', 'Phường 4', 7260000.00, '2026-04-08 19:46:41', 0),
(35, 18, 'dang_quang_i', '0912345678', 'Địa chỉ mẫu', 'Phường 4', 6160000.00, '2026-04-08 19:46:41', 3),
(36, 18, 'dang_quang_i', '0912345678', 'Địa chỉ mẫu', 'Phường 4', 4950000.00, '2026-04-08 19:46:41', 2),
(37, 10, 'nguyenvan_a', '0912345678', 'Địa chỉ mẫu', 'Phường 4', 2780000.00, '2026-04-08 19:46:41', 3),
(38, 15, 'vu_duc_f', '0912345678', 'Địa chỉ mẫu', 'Phường 4', 2257500.00, '2026-04-08 19:46:41', 0),
(39, 13, 'pham_minh_d', '0912345678', 'Địa chỉ mẫu', 'Phường 4', 2420000.00, '2026-04-08 19:46:41', 3),
(40, 19, 'bui_xuan_k', '0912345678', 'Địa chỉ mẫu', 'Phường 4', 6160000.00, '2026-04-08 19:46:41', 3),
(41, 14, 'hoang_anh_e', '0912345678', 'Địa chỉ mẫu', 'Phường 4', 2890000.00, '2026-04-08 19:46:41', 2),
(42, 17, 'ngo_bao_h', '0912345678', 'Địa chỉ mẫu', 'Phường 4', 3500000.00, '2026-04-08 19:46:41', 1),
(43, 13, 'pham_minh_d', '0912345678', 'Địa chỉ mẫu', 'Phường 4', 3630000.00, '2026-04-08 19:46:41', 0),
(44, 12, 'le_hoang_c', '0912345678', 'Địa chỉ mẫu', 'Phường 4', 3980000.00, '2026-04-08 19:46:41', 0),
(45, 15, 'vu_duc_f', '0912345678', 'Địa chỉ mẫu', 'Phường 4', 7260000.00, '2026-04-08 19:46:41', 1),
(46, 16, 'do_thu_g', '0912345678', 'Địa chỉ mẫu', 'Phường 4', 3500000.00, '2026-04-08 19:46:41', 3),
(47, 18, 'dang_quang_i', '0912345678', 'Địa chỉ mẫu', 'Phường 4', 8360000.00, '2026-04-08 19:46:41', 1),
(48, 11, 'tran_thi_b', '0912345678', 'Địa chỉ mẫu', 'Phường 4', 8140000.00, '2026-04-08 19:46:41', 3),
(49, 18, 'dang_quang_i', '0912345678', 'Địa chỉ mẫu', 'Phường 4', 7920000.00, '2026-04-08 19:46:41', 3),
(50, 19, 'bui_xuan_k', '0912345678', 'Địa chỉ mẫu', 'Phường 4', 1285000.00, '2026-04-08 19:46:41', 1),
(51, 15, 'vu_duc_f', '0912345678', 'Địa chỉ mẫu', 'Phường 4', 3450000.00, '2026-04-08 19:46:41', 2),
(52, 16, 'do_thu_g', '0912345678', 'Địa chỉ mẫu', 'Phường 4', 980000.00, '2026-04-08 19:46:41', 2),
(53, 20, 'kaaaaaa', '0912345678', 'Địa chỉ mẫu', 'Phường 4', 2257500.00, '2026-04-08 19:46:41', 3),
(54, 19, 'bui_xuan_k', '0912345678', 'Địa chỉ mẫu', 'Phường 4', 5456000.00, '2026-04-08 19:46:41', 1),
(55, 12, 'le_hoang_c', '0912345678', 'Địa chỉ mẫu', 'Phường 4', 1210000.00, '2026-04-08 19:46:41', 3),
(56, 15, 'vu_duc_f', '0912345678', 'Địa chỉ mẫu', 'Phường 4', 1390000.00, '2026-04-08 19:46:41', 1),
(57, 18, 'dang_quang_i', '0912345678', 'Địa chỉ mẫu', 'Phường 4', 3630000.00, '2026-04-08 19:46:41', 0),
(58, 12, 'LEWON T TTT', '0345505825', 'ấp 2,Tiến Hưng,TP Đồng Xoài ,Bình Phước', 'ádsa', 8750000.00, '2026-04-08 23:22:11', 3),
(59, 12, 'LEWON T TTT', '0345505825', 'ấp 2,Tiến Hưng,TP Đồng Xoài ,Bình Phước', 'ádsa', 3500000.00, '2026-04-09 19:47:12', 3),
(60, 22, 'LEWON T TTT', '0162222222', '36', 'RAU MÁ', 6390000.00, '2026-04-09 20:43:16', 0),
(61, 22, 'LEWON T TTT', '0162222222', '36', 'RAU MÁ', 1750000.00, '2026-04-09 20:43:42', 0),
(62, 22, 'LEWON T TTT', '0162222222', '36', 'RAU MÁ', 1750000.00, '2026-04-09 20:46:29', 0),
(63, 22, 'LEWON T TTT', '0162222222', '36', 'RAU MÁ', 1750000.00, '2026-04-09 20:46:49', 0),
(64, 22, 'LEWON T TTT', '0162222222', '36', 'RAU MÁ', 4482989.98, '2026-04-09 20:50:15', 0),
(65, 22, 'LEWON T TTT', '0162222222', '36', 'RAU MÁ', 4180000.00, '2026-04-09 20:50:49', 0),
(66, 22, 'LEWON T TTT', '0162222222', '36', 'RAU MÁ', 1760000.00, '2026-04-09 20:52:42', 0),
(67, 22, 'dsadasd ádsa', '0162222222', 'ádas', 'adad', 4482989.98, '2026-04-09 20:56:29', 3);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `giaca`
--

CREATE TABLE `giaca` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL COMMENT 'Liên kết với san_pham.id',
  `product_code` varchar(50) DEFAULT NULL COMMENT 'Mã định danh sản phẩm',
  `unit` varchar(20) DEFAULT 'Cái' COMMENT 'Đơn vị tính',
  `stock` int(11) DEFAULT 0 COMMENT 'Số lượng tồn ban đầu/hiện tại',
  `cost_price` decimal(15,2) DEFAULT 0.00 COMMENT 'Giá vốn bình quân',
  `profit_margin` float DEFAULT 10 COMMENT 'Tỉ lệ lợi nhuận mong muốn (%)',
  `status` tinyint(4) DEFAULT 1 COMMENT '1: Đang bán, 0: Ẩn'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_vietnamese_ci;

--
-- Đang đổ dữ liệu cho bảng `giaca`
--

INSERT INTO `giaca` (`id`, `product_id`, `product_code`, `unit`, `stock`, `cost_price`, `profit_margin`, `status`) VALUES
(1, 1, 'SP-1', 'Cái', 94, 3954205.80, 12.1, 1),
(2, 29, 'SP-29', 'Cái', 73, 3300000.00, 10, 1),
(3, 30, 'SP-30', 'Cái', 78, 3300000.00, 10, 1),
(4, 31, 'SP-31', 'Cái', 70, 5000000.00, 10, 1),
(5, 32, 'SP-32', 'Cái', 79, 1263636.36, 10, 1),
(6, 33, 'SP-33', 'Cái', 76, 4354545.45, 10, 1),
(7, 34, 'SP-34', 'Cái', 79, 1809090.91, 10, 1),
(8, 35, 'SP-35', 'Cái', 86, 1578947.37, 10, 1),
(9, 36, 'SP-36', 'Cái', 70, 1590909.09, 10, 1),
(10, 37, 'SP-37', 'Cái', 93, 2953636.36, 10, 1),
(11, 38, 'SP-38', 'Cái', 90, 696818.18, 10, 1),
(12, 39, 'SP-39', 'Cái', 75, 2480000.00, 10, 1),
(13, 40, 'SP-40', 'Cái', 90, 2480000.00, 10, 1),
(14, 41, 'SP-41', 'Cái', 86, 3136363.64, 10, 1),
(15, 42, 'SP-42', 'Cái', 83, 2271818.18, 10, 1),
(16, 43, 'SP-43', 'Cái', 86, 1490909.09, 10, 1),
(17, 44, 'SP-44', 'Cái', 89, 3800000.00, 10, 1),
(18, 45, 'SP-45', 'Cái', 85, 1200000.00, 10, 1),
(19, 46, 'SP-46', 'Cái', 115, 490000.00, 10, 1),
(20, 47, 'SP-47', 'Cái', 87, 1300000.00, 10, 1),
(21, 48, 'SP-48', 'Cái', 77, 1100000.00, 10, 1),
(22, 49, 'SP-49', 'Cái', 83, 1600000.00, 10, 1),
(23, 60, 'SP-60', 'Cái', 85, 3600000.00, 10, 1),
(24, 61, 'SP-61', 'Cái', 86, 4076923.08, 10, 1),
(25, 62, 'SP-62', 'Cái', 85, 1600000.00, 10, 1),
(26, 63, 'SP-63', 'Cái', 90, 4500000.00, 10, 1),
(27, 64, 'SP-64', 'Cái', 86, 5600000.00, 10, 1),
(28, 65, 'SP-65', 'Cái', 69, 3700000.00, 10, 1),
(29, 70, 'SP-70', 'Cái', 62, 2627272.73, 10, 1),
(30, 71, 'SP-71', 'Cái', 97, 1590909.09, 10, 1),
(31, 72, 'SP-72', 'Cái', 68, 2081818.18, 10, 1),
(32, 73, 'SP-73', 'Cái', 97, 1374545.45, 10, 1),
(33, 74, 'SP-74', 'Cái', 66, 2157418.18, 10, 1),
(34, 75, 'SP-75', 'Cái', 79, 2052272.73, 10, 1),
(35, 76, 'SP-76', 'Cái', 100, 1168181.82, 10, 1);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `kho_canh_bao`
--

CREATE TABLE `kho_canh_bao` (
  `product_id` int(11) NOT NULL,
  `min_threshold` int(11) DEFAULT 10 COMMENT 'Ngưỡng sắp hết hàng do người dùng định'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `kho_canh_bao`
--

INSERT INTO `kho_canh_bao` (`product_id`, `min_threshold`) VALUES
(1, 5),
(32, 10),
(33, 3),
(35, 15),
(46, 20);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `nhat_ky_kho`
--

CREATE TABLE `nhat_ky_kho` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `change_quantity` int(11) NOT NULL COMMENT 'Số lượng thay đổi (+ nếu nhập, - nếu xuất)',
  `transaction_type` enum('NHAP','XUAT','DIEU_CHINH') NOT NULL,
  `reference_id` int(11) DEFAULT NULL COMMENT 'ID của phiếu nhập hoặc đơn hàng tương ứng',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `nhat_ky_kho`
--

INSERT INTO `nhat_ky_kho` (`id`, `product_id`, `change_quantity`, `transaction_type`, `reference_id`, `created_at`) VALUES
(1, 1, 10, 'NHAP', NULL, '2026-03-01 08:00:00'),
(2, 35, 30, 'NHAP', NULL, '2026-03-05 09:30:00'),
(3, 32, 15, 'NHAP', NULL, '2026-03-10 10:00:00'),
(4, 46, 50, 'NHAP', NULL, '2026-03-15 14:00:00'),
(5, 1, -2, 'XUAT', NULL, '2026-03-20 16:00:00'),
(6, 35, -5, 'XUAT', NULL, '2026-03-25 11:00:00'),
(7, 46, -10, 'XUAT', NULL, '2026-03-28 15:00:00'),
(8, 1, 5, 'NHAP', NULL, '2026-04-01 09:00:00'),
(9, 35, -12, 'XUAT', NULL, '2026-04-03 14:00:00'),
(10, 32, -8, 'XUAT', NULL, '2026-04-05 10:30:00'),
(11, 1, -9, 'XUAT', NULL, '2026-04-07 16:00:00'),
(12, 46, -25, 'XUAT', NULL, '2026-04-08 11:00:00'),
(13, 60, -1, 'XUAT', 8, '2026-04-08 19:32:20'),
(14, 70, -2, 'XUAT', 8, '2026-04-08 19:32:20'),
(15, 29, -1, 'XUAT', 10, '2026-04-08 19:32:20'),
(16, 49, -2, 'XUAT', 10, '2026-04-08 19:32:20'),
(17, 71, -1, 'XUAT', 17, '2026-04-08 19:32:20'),
(18, 75, -1, 'XUAT', 19, '2026-04-08 19:32:20'),
(19, 62, -1, 'XUAT', 19, '2026-04-08 19:32:20'),
(20, 41, -2, 'XUAT', 21, '2026-04-08 19:32:20'),
(21, 42, -2, 'XUAT', 22, '2026-04-08 19:32:20'),
(22, 49, -1, 'XUAT', 26, '2026-04-08 19:32:20'),
(23, 74, -1, 'XUAT', 26, '2026-04-08 19:32:20'),
(24, 36, -1, 'XUAT', 27, '2026-04-08 19:32:20'),
(25, 60, -2, 'XUAT', 27, '2026-04-08 19:32:20'),
(26, 64, -2, 'XUAT', 30, '2026-04-08 19:32:20'),
(27, 48, -2, 'XUAT', 30, '2026-04-08 19:32:20'),
(28, 46, -1, 'XUAT', 31, '2026-04-08 19:32:20'),
(29, 72, -1, 'XUAT', 31, '2026-04-08 19:32:20'),
(30, 48, -1, 'XUAT', 31, '2026-04-08 19:32:20'),
(31, 48, -2, 'XUAT', 32, '2026-04-08 19:32:20'),
(32, 70, -1, 'XUAT', 32, '2026-04-08 19:32:20'),
(33, 62, -2, 'XUAT', 32, '2026-04-08 19:32:20'),
(34, 30, 78, 'NHAP', 0, '2026-01-01 00:00:00'),
(35, 31, 70, 'NHAP', 0, '2026-01-01 00:00:00'),
(36, 33, 76, 'NHAP', 0, '2026-01-01 00:00:00'),
(37, 34, 79, 'NHAP', 0, '2026-01-01 00:00:00'),
(38, 37, 93, 'NHAP', 0, '2026-01-01 00:00:00'),
(39, 38, 90, 'NHAP', 0, '2026-01-01 00:00:00'),
(40, 39, 75, 'NHAP', 0, '2026-01-01 00:00:00'),
(41, 40, 90, 'NHAP', 0, '2026-01-01 00:00:00'),
(42, 43, 86, 'NHAP', 0, '2026-01-01 00:00:00'),
(43, 44, 89, 'NHAP', 0, '2026-01-01 00:00:00'),
(44, 45, 85, 'NHAP', 0, '2026-01-01 00:00:00'),
(45, 47, 87, 'NHAP', 0, '2026-01-01 00:00:00'),
(46, 61, 86, 'NHAP', 0, '2026-01-01 00:00:00'),
(47, 63, 90, 'NHAP', 0, '2026-01-01 00:00:00'),
(48, 65, 69, 'NHAP', 0, '2026-01-01 00:00:00'),
(49, 73, 97, 'NHAP', 0, '2026-01-01 00:00:00'),
(50, 76, 100, 'NHAP', 0, '2026-01-01 00:00:00'),
(65, 71, -2, 'XUAT', 33, '2026-04-08 19:46:41'),
(66, 63, -1, 'XUAT', 36, '2026-04-08 19:46:41'),
(67, 70, -1, 'XUAT', 41, '2026-04-08 19:46:41'),
(68, 36, -2, 'XUAT', 42, '2026-04-08 19:46:41'),
(69, 30, -2, 'XUAT', 45, '2026-04-08 19:46:41'),
(70, 44, -2, 'XUAT', 47, '2026-04-08 19:46:41'),
(71, 76, -1, 'XUAT', 50, '2026-04-08 19:46:41'),
(72, 41, -1, 'XUAT', 51, '2026-04-08 19:46:41'),
(73, 46, -2, 'XUAT', 52, '2026-04-08 19:46:41'),
(74, 40, -2, 'XUAT', 54, '2026-04-08 19:46:41'),
(75, 32, -1, 'XUAT', 56, '2026-04-08 19:46:41'),
(76, 39, -1, 'XUAT', 5, '2026-04-08 19:47:09'),
(77, 1, 5, 'NHAP', 1, '2026-04-08 19:47:18'),
(78, 35, 10, 'NHAP', 2, '2026-04-08 19:47:18'),
(79, 1, 85, 'NHAP', 0, '2026-01-01 00:00:00'),
(80, 29, 74, 'NHAP', 0, '2026-01-01 00:00:00'),
(81, 32, 72, 'NHAP', 0, '2026-01-01 00:00:00'),
(82, 35, 63, 'NHAP', 0, '2026-01-01 00:00:00'),
(83, 36, 71, 'NHAP', 0, '2026-01-01 00:00:00'),
(84, 41, 88, 'NHAP', 0, '2026-01-01 00:00:00'),
(85, 42, 85, 'NHAP', 0, '2026-01-01 00:00:00'),
(86, 46, 101, 'NHAP', 0, '2026-01-01 00:00:00'),
(87, 48, 82, 'NHAP', 0, '2026-01-01 00:00:00'),
(88, 49, 86, 'NHAP', 0, '2026-01-01 00:00:00'),
(89, 60, 88, 'NHAP', 0, '2026-01-01 00:00:00'),
(90, 62, 88, 'NHAP', 0, '2026-01-01 00:00:00'),
(91, 64, 88, 'NHAP', 0, '2026-01-01 00:00:00'),
(92, 70, 65, 'NHAP', 0, '2026-01-01 00:00:00'),
(93, 71, 98, 'NHAP', 0, '2026-01-01 00:00:00'),
(94, 72, 69, 'NHAP', 0, '2026-01-01 00:00:00'),
(95, 74, 67, 'NHAP', 0, '2026-01-01 00:00:00'),
(96, 75, 80, 'NHAP', 0, '2026-01-01 00:00:00'),
(110, 35, -2, 'XUAT', 59, '2026-04-09 21:11:27'),
(111, 1, -1, 'XUAT', 67, '2026-04-09 21:11:36'),
(112, 1, 1, 'DIEU_CHINH', 67, '2026-04-09 21:11:49'),
(113, 35, 2, 'DIEU_CHINH', 59, '2026-04-09 21:11:50'),
(114, 39, 1, 'DIEU_CHINH', 5, '2026-04-09 21:11:53');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `phieu_nhap_hang`
--

CREATE TABLE `phieu_nhap_hang` (
  `id` int(11) NOT NULL,
  `import_date` date NOT NULL COMMENT 'Ngày lập phiếu',
  `import_batch` int(11) NOT NULL COMMENT 'Số thứ tự lô nhập trong ngày',
  `product_id` int(11) NOT NULL COMMENT 'ID linh kiện nhập',
  `import_price` decimal(15,2) NOT NULL COMMENT 'Giá nhập thực tế tại thời điểm đó',
  `quantity` int(11) NOT NULL COMMENT 'Số lượng linh kiện nhập',
  `status` tinyint(1) DEFAULT 0 COMMENT '0: Bản nháp (Được sửa), 1: Hoàn thành (Khóa phiếu)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `phieu_nhap_hang`
--

INSERT INTO `phieu_nhap_hang` (`id`, `import_date`, `import_batch`, `product_id`, `import_price`, `quantity`, `status`) VALUES
(1, '2026-04-08', 1, 1, 3200000.00, 5, 1),
(2, '2026-04-08', 1, 35, 1500000.00, 10, 1),
(3, '2026-04-08', 2, 46, 85000.00, 20, 1),
(4, '2026-04-09', 1, 61, 4000000.00, 3, 1),
(5, '2026-04-08', 10, 35, 1312500.00, 66, 1),
(6, '2026-04-08', 10, 36, 1312500.00, 71, 1),
(7, '2026-04-08', 10, 37, 2436750.00, 93, 1),
(8, '2026-04-08', 11, 38, 574875.00, 90, 1),
(9, '2026-04-08', 11, 39, 2046000.00, 75, 1),
(10, '2026-04-08', 12, 40, 2046000.00, 90, 1),
(11, '2026-04-08', 12, 41, 2587500.00, 88, 1),
(12, '2026-04-08', 13, 42, 1874250.00, 85, 1),
(13, '2026-04-08', 13, 1, 2999324.25, 84, 1),
(14, '2026-04-08', 14, 29, 2722500.00, 74, 1),
(15, '2026-04-08', 14, 30, 2722500.00, 78, 1),
(16, '2026-04-08', 14, 31, 4125000.00, 70, 1),
(17, '2026-04-08', 15, 32, 1042500.00, 79, 1),
(18, '2026-04-08', 15, 33, 3592500.00, 76, 1),
(19, '2026-04-08', 15, 34, 1492500.00, 79, 1),
(20, '2026-04-08', 16, 70, 2167500.00, 65, 1),
(21, '2026-04-08', 16, 71, 1312500.00, 98, 1),
(22, '2026-04-08', 17, 72, 1717500.00, 69, 1),
(23, '2026-04-08', 17, 73, 1134000.00, 97, 1),
(24, '2026-04-08', 17, 74, 1779870.00, 67, 1),
(25, '2026-04-08', 18, 75, 1693125.00, 80, 1),
(26, '2026-04-08', 18, 76, 963750.00, 100, 1),
(27, '2026-04-08', 18, 60, 2970000.00, 88, 1),
(28, '2026-04-08', 19, 61, 3057692.31, 83, 1),
(29, '2026-04-08', 19, 62, 1320000.00, 88, 1),
(30, '2026-04-08', 20, 63, 3712500.00, 90, 1),
(31, '2026-04-08', 21, 64, 4620000.00, 88, 1),
(32, '2026-04-08', 22, 65, 3052500.00, 69, 1),
(33, '2026-04-08', 23, 43, 1230000.00, 86, 1),
(34, '2026-04-08', 24, 44, 3135000.00, 89, 1),
(35, '2026-04-08', 25, 45, 990000.00, 85, 1),
(36, '2026-04-08', 26, 46, 367500.00, 96, 1),
(37, '2026-04-08', 27, 47, 1072500.00, 87, 1),
(38, '2026-04-08', 28, 48, 907500.00, 82, 1),
(39, '2026-04-08', 29, 49, 1320000.00, 86, 1);

--
-- Bẫy `phieu_nhap_hang`
--
DELIMITER $$
CREATE TRIGGER `After_PhieuNhap_Update` AFTER UPDATE ON `phieu_nhap_hang` FOR EACH ROW BEGIN
    -- Chỉ xử lý khi phiếu chuyển từ trạng thái Nháp (0) sang Hoàn thành (1)
    IF OLD.status = 0 AND NEW.status = 1 THEN
        
        -- 1. Cộng kho trong bảng san_pham
        UPDATE san_pham 
        SET stock = stock + NEW.quantity 
        WHERE id = NEW.product_id;
        
        -- 2. Cộng kho trong bảng giaca
        UPDATE giaca 
        SET stock = stock + NEW.quantity 
        WHERE product_id = NEW.product_id;
        
        -- 3. Ghi vào nhật ký kho để làm báo cáo
        INSERT INTO nhat_ky_kho (product_id, change_quantity, transaction_type, reference_id)
        VALUES (NEW.product_id, NEW.quantity, 'NHAP', NEW.id);
        
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `product_spec_values`
--

CREATE TABLE `product_spec_values` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `spec_id` int(11) NOT NULL COMMENT 'ID từ bảng thong_so_danh_muc',
  `spec_value` text NOT NULL COMMENT 'Giá trị cụ thể'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `san_pham`
--

CREATE TABLE `san_pham` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `brand_id` int(11) DEFAULT NULL,
  `price` decimal(15,2) NOT NULL,
  `selling_price` decimal(15,2) DEFAULT NULL,
  `main_image` varchar(255) DEFAULT NULL,
  `back_image` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `unit` varchar(50) DEFAULT 'Cái',
  `stock` int(11) DEFAULT 0,
  `profit_rate` int(11) DEFAULT 0,
  `status` int(11) DEFAULT 1,
  `sku` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_vietnamese_ci;

--
-- Đang đổ dữ liệu cho bảng `san_pham`
--

INSERT INTO `san_pham` (`id`, `name`, `category_id`, `brand_id`, `price`, `selling_price`, `main_image`, `back_image`, `description`, `unit`, `stock`, `profit_rate`, `status`, `sku`) VALUES
(1, 'MelGeek MADE68 Pro – Bàn phím HE Rapid Trigger', 2, 3, 3954205.80, 4482989.98, 'made68pro.webp', 'made68proback.webp', NULL, 'Cái', 94, 12, 1, 0),
(29, 'MelGeek O2 – Bàn phím low-profile tri-mode trong suốt', 2, 3, 3630000.00, 3630000.00, 'melgeeko2.webp', 'melgeeko2back.webp', NULL, 'Cái', 73, 0, 1, 0),
(30, 'Scyrox Xpunk – Bàn phím HE 63 phím Rapid Trigger', 2, 5, 3630000.00, 3630000.00, 'keyscyrox.webp', 'keyscyroxback.webp', NULL, 'Cái', 76, 0, 1, 0),
(31, 'Yuki Aim x Arbiter Polar75 Oni 2.0 – Bàn phím HE limited', 2, 4, 5500000.00, 5500000.00, 'keyyukiaimback.webp', 'keyyukiaim.webp', NULL, 'Cái', 70, 0, 1, 0),
(32, 'Logitech G413 TKL SE – Bàn phím cơ TKL LED trắng', 2, 1, 1390000.00, 1390000.00, 'keylogi.webp', 'keylogiback.webp', NULL, 'Cái', 78, 0, 1, 0),
(33, 'Razer BlackWidow V4 X ZZZ – Bàn phím cơ Green giới hạn', 2, 2, 4790000.00, 4790000.00, 'backwidowback.webp', 'backwidow.webp', NULL, 'Cái', 76, 0, 1, 0),
(34, 'Razer BlackWidow V3 TKL – Bàn phím cơ Green cho FPS', 2, 2, 1990000.00, 1990000.00, 'razerkey.webp', 'razerkeyback.webp', NULL, 'Cái', 79, 0, 1, 0),
(35, 'Scyrox V6 – Chuột đối xứng kèm dongle 8K', 1, 5, 1578947.37, 1750000.00, 'scyroxv61.webp', 'scyroxv6back.webp', NULL, 'Cái', 86, 0, 1, 0),
(36, 'Scyrox V8 – Chuột đối xứng kèm dongle 8K', 1, 5, 1750000.00, 1750000.00, 'scyroxv81.webp', 'scyroxv8back.webp', NULL, 'Cái', 68, 0, 1, 0),
(37, 'Logitech G PRO X Superlight 2 – Chuột đối xứng LIGHTSPEED', 1, 1, 3249000.00, 3249000.00, 'spl2.webp', 'spl2back.webp', NULL, 'Cái', 93, 0, 1, 0),
(38, 'Logitech G304 Lightspeed – Chuột không dây HERO', 1, 1, 766500.00, 766500.00, 'g304.webp', 'g304back.webp', NULL, 'Cái', 90, 0, 1, 0),
(39, 'Pulsar X2H Medium Giyu – Chuột đối xứng collab Demon Slayer', 1, 6, 2728000.00, 2728000.00, 'X2H.webp', 'x2hmback.webp', NULL, 'Cái', 75, 0, 1, 0),
(40, 'Pulsar X2H Mini Muichiro – Chuột đối xứng collab Demon Slayer kèm dongle 8K', 1, 6, 2728000.00, 2728000.00, 'x2hmini.webp', 'x2hmiback.webp', NULL, 'Cái', 88, 0, 1, 0),
(41, 'Razer Viper V3 Pro – Chuột đối xứng kèm dongle 8K', 1, 2, 3450000.00, 3450000.00, 'viper.webp', 'viperback.webp', NULL, 'Cái', 85, 0, 1, 0),
(42, 'Lamzu Thorn – Chuột ergonomic hỗ trợ 8K', 1, 7, 2499000.00, 2499000.00, 'lamzu.webp', 'lamzuback.webp', NULL, 'Cái', 83, 0, 1, 0),
(43, 'Yuki Aim x Demon1 – Lót chuột control dày 3.7mm', 5, 4, 1640000.00, 1640000.00, 'demon1vai.webp', 'demon1vaiback.webp', NULL, 'Cái', 86, 0, 1, 0),
(44, 'Yuki Aim x Demon1 Glass Pad – Lót chuột kính speed giới hạn', 5, 4, 4180000.00, 4180000.00, 'demon1kinh.webp', 'demon1kinhback.webp', NULL, 'Cái', 87, 0, 1, 0),
(45, 'Artisan Ninja FX Zero – Lót chuột cân bằng bề mặt xoắn', 5, 8, 1320000.00, 1320000.00, 'fxzero.webp', 'fxzeroback.webp', NULL, 'Cái', 85, 0, 1, 0),
(46, 'Artisan Ninja FX Type-99 – Lót chuột control bề mặt khô', 5, 8, 490000.00, 490000.00, '99.webp', '99back.jpg', NULL, 'Cái', 113, 0, 1, 0),
(47, 'Gamesense Radar XL – Lót chuột control', 5, 9, 1430000.00, 1430000.00, 'radar.webp', 'radarback.webp', NULL, 'Cái', 87, 0, 1, 0),
(48, 'Gamesense Radar 8-bit Camo – Lót chuột control', 5, 9, 1210000.00, 1210000.00, 'camo.webp', 'camo2.webp', NULL, 'Cái', 77, 0, 1, 0),
(49, 'Yuki Aim Kitsune 2024 XL – Lót chuột control dày', 5, 4, 1760000.00, 1760000.00, 'kitsune.webp', 'kitsuneback.webp', NULL, 'Cái', 83, 0, 1, 0),
(60, 'Filco Majestouch Xacro M3A – Bàn phím cơ 65% macro đa layout', 4, 10, 3960000.00, 3960000.00, 'xacroback.webp', 'xacro.webp', NULL, 'Cái', 85, 0, 1, 0),
(61, 'Filco Convertible 3X Matcha – Bàn phím cơ fullsize 5 thiết bị', 4, 10, 4076923.08, 4076923.08, 'matcha.webp', 'matchaback.webp', NULL, 'Cái', 86, 0, 1, 0),
(62, 'Filco Majestouch Tenkeypad Pro – Numpad cơ 21 phím tiện ích', 4, 10, 1760000.00, 1760000.00, 'tenkey.webp', 'tenkeyback.webp', NULL, 'Cái', 85, 0, 1, 0),
(63, 'Filco Minila-R Convertible Milk – Bàn phím cơ 60% trắng sữa', 4, 10, 4950000.00, 4950000.00, 'milk.webp', 'milkback.webp', NULL, 'Cái', 89, 0, 1, 0),
(64, 'Filco Minila-R Convertible Galaxy Gold – Bàn phím cơ 60% ánh vàng', 4, 10, 6160000.00, 6160000.00, 'galaxy.webp', 'galaxyback.webp', NULL, 'Cái', 86, 0, 1, 0),
(65, 'Filco Majestouch LUCE60 – Bàn phím cơ 60% hot-swap RGB', 4, 10, 4070000.00, 4070000.00, 'luce60.webp', 'luce60back.webp', NULL, 'Cái', 69, 0, 1, 0),
(70, 'Logitech MX Master 4 – Chuột không dây văn phòng', 3, 1, 2890000.00, 2890000.00, 'MX4.webp', 'MX4back.webp', NULL, 'Cái', 61, 0, 1, 0),
(71, 'Razer Pro Click V2 Vertical – Chuột công thái học không dây tri-mode', 3, 2, 1750000.00, 1750000.00, 'razerV2pro.webp', 'razerV2proback.webp', NULL, 'Cái', 95, 0, 1, 0),
(72, 'Razer Pro Click V2 – Chuột công thái học không dây tri-mode', 3, 2, 2290000.00, 2290000.00, 'razeV2.webp', 'razerV2back.webp', NULL, 'Cái', 68, 0, 1, 0),
(73, 'Logitech MX Anywhere 3S – Chuột không dây compact DPI', 3, 1, 1512000.00, 1512000.00, 'lomx.webp', 'lomxback.webp', NULL, 'Cái', 97, 0, 1, 0),
(74, 'Logitech MX Master 3S for Mac – Chuột không dây DPI', 3, 1, 2373160.00, 2373160.00, 'lomxmmac.webp', 'lomxmmacback.webp', NULL, 'Cái', 66, 0, 1, 0),
(75, 'Logitech MX Master 3S – Chuột không dây DPI Quiet Click', 3, 1, 2257500.00, 2257500.00, 'lomx.webp', 'lomxback.webp', NULL, 'Cái', 79, 0, 1, 0),
(76, 'Logitech Lift Vertical – Chuột công thái học dọc cho tay nhỏ', 3, 1, 1285000.00, 1285000.00, 'lol.webp', 'lolback.webp', NULL, 'Cái', 99, 0, 1, 0);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `thong_so`
--

CREATE TABLE `thong_so` (
  `id` int(11) NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `spec_key` varchar(50) DEFAULT NULL,
  `spec_value` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_vietnamese_ci;

--
-- Đang đổ dữ liệu cho bảng `thong_so`
--

INSERT INTO `thong_so` (`id`, `product_id`, `spec_key`, `spec_value`) VALUES
(1, 1, 'Layout', '68%'),
(2, 1, 'Loại Sản Phẩm', 'Bàn phím HE'),
(3, 29, 'Layout', '75%'),
(4, 29, 'Loại Sản Phẩm', 'Bàn phím cơ'),
(5, 30, 'Layout', '60%'),
(6, 30, 'Loại Sản Phẩm', 'Bàn phím HE'),
(7, 31, 'Layout', '75%'),
(8, 31, 'Loại Sản Phẩm', 'Bàn phím HE'),
(9, 32, 'Layout', 'TKL'),
(10, 32, 'Loại Sản Phẩm', 'Bàn phím cơ'),
(11, 33, 'Layout', 'Full Size'),
(12, 33, 'Loại Sản Phẩm', 'Bàn phím cơ'),
(13, 34, 'Layout', 'TKL'),
(14, 34, 'Loại Sản Phẩm', 'Bàn phím cơ'),
(15, 35, 'Polling rate', 'Tối đa 8000Hz'),
(16, 35, 'Loại Switch', 'Switch quang'),
(17, 36, 'Polling rate', 'Tối đa 8000Hz'),
(18, 36, 'Loại Switch', 'Switch quang'),
(19, 37, 'Polling rate', 'Tối đa 4000Hz'),
(20, 37, 'Loại Switch', 'Switch quang'),
(21, 38, 'Polling rate', 'Tối đa 1000Hz'),
(22, 38, 'Loại Switch', 'Switch cơ'),
(23, 39, 'Polling rate', 'Tối đa 4000Hz'),
(24, 39, 'Loại Switch', 'Switch quang'),
(25, 40, 'Polling rate', 'Tối đa 8000Hz'),
(26, 40, 'Loại Switch', 'Switch quang'),
(27, 41, 'Polling rate', 'Tối đa 8000Hz'),
(28, 41, 'Loại Switch', 'Switch quang'),
(29, 42, 'Polling rate', 'Tối đa 8000Hz'),
(30, 42, 'Loại Switch', 'Switch quang'),
(31, 43, 'Bề mặt', 'Control'),
(32, 43, 'Kích thước', 'Lớn (L)'),
(33, 43, 'Chất liệu', 'Vải'),
(34, 44, 'Bề mặt', 'Speed'),
(35, 44, 'Kích thước', 'Lớn (L)'),
(36, 44, 'Chất liệu', 'Kính'),
(37, 45, 'Bề mặt', 'Hybrid'),
(38, 45, 'Kích thước', 'Lớn (L)'),
(39, 45, 'Chất liệu', 'Vải'),
(40, 46, 'Bề mặt', 'Control'),
(41, 46, 'Kích thước', 'Lớn (L)'),
(42, 46, 'Chất liệu', 'Nhám'),
(43, 47, 'Bề mặt', 'Control'),
(44, 47, 'Kích thước', 'Rất lớn (XXL)'),
(45, 47, 'Chất liệu', 'Vải'),
(46, 48, 'Bề mặt', 'Control'),
(47, 48, 'Kích thước', 'Lớn (L)'),
(48, 48, 'Chất liệu', 'Nhám'),
(49, 49, 'Bề mặt', 'Control'),
(50, 49, 'Kích thước', 'Rất lớn (XXL)'),
(51, 49, 'Chất liệu', 'Vải'),
(52, 60, 'Loại Sản Phẩm', 'Bàn phím cơ'),
(53, 60, 'Layout', '65%'),
(54, 61, 'Loại Sản Phẩm', 'Bàn phím cơ'),
(55, 61, 'Layout', 'Full Size'),
(56, 62, 'Loại Sản Phẩm', 'Bàn phím cơ'),
(57, 62, 'Layout', 'Numpad'),
(58, 63, 'Loại Sản Phẩm', 'Bàn phím cơ'),
(59, 63, 'Layout', '60%'),
(60, 64, 'Loại Sản Phẩm', 'Bàn phím cơ'),
(61, 64, 'Layout', '60%'),
(62, 65, 'Loại Sản Phẩm', 'Bàn phím cơ'),
(63, 65, 'Layout', '60%'),
(64, 70, 'Polling rate', 'Tối đa 1000Hz'),
(65, 70, 'Loại Switch', 'Switch cơ'),
(66, 71, 'Polling rate', 'Tối đa 1000Hz'),
(67, 71, 'Loại Switch', 'Switch cơ'),
(68, 72, 'Polling rate', 'Tối đa 1000Hz'),
(69, 72, 'Loại Switch', 'Switch cơ'),
(70, 73, 'Polling rate', 'Tối đa 1000Hz'),
(71, 73, 'Loại Switch', 'Switch cơ'),
(72, 74, 'Polling rate', 'Tối đa 1000Hz'),
(73, 74, 'Loại Switch', 'Switch cơ'),
(74, 75, 'Polling rate', 'Tối đa 1000Hz'),
(75, 75, 'Loại Switch', 'Switch cơ'),
(76, 76, 'Polling rate', 'Tối đa 1000Hz'),
(77, 76, 'Loại Switch', 'Switch cơ');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `thong_so_danh_muc`
--

CREATE TABLE `thong_so_danh_muc` (
  `id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `spec_name` varchar(255) NOT NULL COMMENT 'Tên thông số (VD: DPI, Switch, Pin...)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `thong_so_danh_muc`
--

INSERT INTO `thong_so_danh_muc` (`id`, `category_id`, `spec_name`) VALUES
(1, 1, 'DPI tối đa'),
(2, 1, 'Mắt đọc (Sensor)'),
(3, 1, 'Kiểu kết nối'),
(4, 2, 'Loại Switch'),
(5, 2, 'Layout'),
(6, 2, 'Chất liệu Keycap');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `thuong_hieu`
--

CREATE TABLE `thuong_hieu` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_vietnamese_ci;

--
-- Đang đổ dữ liệu cho bảng `thuong_hieu`
--

INSERT INTO `thuong_hieu` (`id`, `name`) VALUES
(1, 'Logitech'),
(2, 'Razer'),
(3, 'MelGeek'),
(4, 'Yuki Aim'),
(5, 'Scyrox'),
(6, 'Pulsar'),
(7, 'Lamzu'),
(8, 'Artisan'),
(9, 'Gamesense'),
(10, 'Filco');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `user_name` varchar(50) DEFAULT NULL,
  `password` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `status` tinyint(4) DEFAULT 1 COMMENT '1: Hoạt động, 0: Bị khóa'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_vietnamese_ci;

--
-- Đang đổ dữ liệu cho bảng `users`
--

INSERT INTO `users` (`id`, `user_name`, `password`, `email`, `status`) VALUES
(10, 'nguyenvan_a', '123', 'vana@gmail.com', 1),
(11, 'tran_thi_b', '123', 'thib@yahoo.com', 1),
(12, 'le_hoang_c', '123', 'hoangc@sgu.edu.vn', 1),
(13, 'pham_minh_d', '123', 'minhd@gmail.com', 0),
(14, 'hoang_anh_e', '123', 'anhe@outlook.com', 0),
(15, 'vu_duc_f', '123', 'ducf@gmail.com', 1),
(16, 'do_thu_g', '123', 'thug@gmail.com', 1),
(17, 'ngo_bao_h', '123', 'baoh@gmail.com', 1),
(18, 'dang_quang_i', '123', 'quangi@gmail.com', 1),
(19, 'bui_xuan_k', '123', 'xuank@gmail.com', 1),
(20, 'kaaaaaa', '1233', 'VN@aaaaa', 1),
(21, 'mittodat', '$2y$10$nK6PAQltHxNRnx5K5f9UeeBf6WRFOezaW0Zrgpt.OndWANQDxQ9Fu', 'mitodat09@gmail.com', 1),
(22, 'mittodat009', '$2y$10$wI.d8o7IcowsbMtI7tVW7upQmS71zJx/kkPzM6lp4emUzStnHl/ri', 'mitodat009@gmail.com', 1);

--
-- Chỉ mục cho các bảng đã đổ
--

--
-- Chỉ mục cho bảng `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `chi_tiet_don_hang`
--
ALTER TABLE `chi_tiet_don_hang`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_dh_don_hang` (`order_id`);

--
-- Chỉ mục cho bảng `danh_muc`
--
ALTER TABLE `danh_muc`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `dia_chi`
--
ALTER TABLE `dia_chi`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Chỉ mục cho bảng `don_hang`
--
ALTER TABLE `don_hang`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `giaca`
--
ALTER TABLE `giaca`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_giaca_product` (`product_id`);

--
-- Chỉ mục cho bảng `kho_canh_bao`
--
ALTER TABLE `kho_canh_bao`
  ADD PRIMARY KEY (`product_id`);

--
-- Chỉ mục cho bảng `nhat_ky_kho`
--
ALTER TABLE `nhat_ky_kho`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_log_product` (`product_id`);

--
-- Chỉ mục cho bảng `phieu_nhap_hang`
--
ALTER TABLE `phieu_nhap_hang`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_import_product_new` (`product_id`);

--
-- Chỉ mục cho bảng `product_spec_values`
--
ALTER TABLE `product_spec_values`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_value_product` (`product_id`),
  ADD KEY `fk_value_spec` (`spec_id`);

--
-- Chỉ mục cho bảng `san_pham`
--
ALTER TABLE `san_pham`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `brand_id` (`brand_id`);

--
-- Chỉ mục cho bảng `thong_so`
--
ALTER TABLE `thong_so`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`);

--
-- Chỉ mục cho bảng `thong_so_danh_muc`
--
ALTER TABLE `thong_so_danh_muc`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_spec_category` (`category_id`);

--
-- Chỉ mục cho bảng `thuong_hieu`
--
ALTER TABLE `thuong_hieu`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT cho các bảng đã đổ
--

--
-- AUTO_INCREMENT cho bảng `admin`
--
ALTER TABLE `admin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT cho bảng `chi_tiet_don_hang`
--
ALTER TABLE `chi_tiet_don_hang`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=96;

--
-- AUTO_INCREMENT cho bảng `danh_muc`
--
ALTER TABLE `danh_muc`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT cho bảng `dia_chi`
--
ALTER TABLE `dia_chi`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT cho bảng `don_hang`
--
ALTER TABLE `don_hang`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=68;

--
-- AUTO_INCREMENT cho bảng `giaca`
--
ALTER TABLE `giaca`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT cho bảng `nhat_ky_kho`
--
ALTER TABLE `nhat_ky_kho`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=115;

--
-- AUTO_INCREMENT cho bảng `phieu_nhap_hang`
--
ALTER TABLE `phieu_nhap_hang`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT cho bảng `product_spec_values`
--
ALTER TABLE `product_spec_values`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `san_pham`
--
ALTER TABLE `san_pham`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=82;

--
-- AUTO_INCREMENT cho bảng `thong_so`
--
ALTER TABLE `thong_so`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=83;

--
-- AUTO_INCREMENT cho bảng `thong_so_danh_muc`
--
ALTER TABLE `thong_so_danh_muc`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT cho bảng `thuong_hieu`
--
ALTER TABLE `thuong_hieu`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT cho bảng `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- Các ràng buộc cho các bảng đã đổ
--

--
-- Các ràng buộc cho bảng `chi_tiet_don_hang`
--
ALTER TABLE `chi_tiet_don_hang`
  ADD CONSTRAINT `fk_dh_don_hang` FOREIGN KEY (`order_id`) REFERENCES `don_hang` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `dia_chi`
--
ALTER TABLE `dia_chi`
  ADD CONSTRAINT `dia_chi_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Các ràng buộc cho bảng `giaca`
--
ALTER TABLE `giaca`
  ADD CONSTRAINT `fk_giaca_product` FOREIGN KEY (`product_id`) REFERENCES `san_pham` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `kho_canh_bao`
--
ALTER TABLE `kho_canh_bao`
  ADD CONSTRAINT `fk_alert_product` FOREIGN KEY (`product_id`) REFERENCES `san_pham` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `nhat_ky_kho`
--
ALTER TABLE `nhat_ky_kho`
  ADD CONSTRAINT `fk_log_product` FOREIGN KEY (`product_id`) REFERENCES `san_pham` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `phieu_nhap_hang`
--
ALTER TABLE `phieu_nhap_hang`
  ADD CONSTRAINT `fk_import_product_new` FOREIGN KEY (`product_id`) REFERENCES `san_pham` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `product_spec_values`
--
ALTER TABLE `product_spec_values`
  ADD CONSTRAINT `fk_value_product` FOREIGN KEY (`product_id`) REFERENCES `san_pham` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_value_spec` FOREIGN KEY (`spec_id`) REFERENCES `thong_so_danh_muc` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `san_pham`
--
ALTER TABLE `san_pham`
  ADD CONSTRAINT `san_pham_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `danh_muc` (`id`),
  ADD CONSTRAINT `san_pham_ibfk_2` FOREIGN KEY (`brand_id`) REFERENCES `thuong_hieu` (`id`);

--
-- Các ràng buộc cho bảng `thong_so`
--
ALTER TABLE `thong_so`
  ADD CONSTRAINT `thong_so_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `san_pham` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `thong_so_danh_muc`
--
ALTER TABLE `thong_so_danh_muc`
  ADD CONSTRAINT `fk_spec_category` FOREIGN KEY (`category_id`) REFERENCES `danh_muc` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
