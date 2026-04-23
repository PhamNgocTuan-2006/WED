<?php
if (session_status() == PHP_SESSION_NONE) session_start();
require_once 'config.php';

/* ==========================================================
   1. LOGIC HOÀN TẤT PHIẾU & CẬP NHẬT GIÁ VỐN
   ========================================================== */
if (isset($_GET['finalize']) && $_GET['finalize'] == 1) {
    $date = $conn->real_escape_string($_GET['date']);
    $batch = intval($_GET['batch']);

    $conn->begin_transaction();

    try {
        $sql_items = "SELECT product_id, quantity, import_price FROM phieu_nhap_hang 
                      WHERE import_date = '$date' AND import_batch = $batch AND status = 0";
        $res_items = $conn->query($sql_items);

        if ($res_items->num_rows == 0) {
            throw new Exception("Không tìm thấy dữ liệu nháp hoặc phiếu đã chốt.");
        }

        while ($item = $res_items->fetch_assoc()) {
            $p_id = $item['product_id'];
            $new_qty = $item['quantity'];
            $new_price = $item['import_price'];

            // Lấy dữ liệu hiện tại để tính giá vốn bình quân
            $sql_cur = "SELECT stock, cost_price FROM giaca WHERE product_id = $p_id FOR UPDATE";
            $cur = $conn->query($sql_cur)->fetch_assoc();
            
            $old_qty = $cur['stock'];
            $old_price = $cur['cost_price'];

            // CÔNG THỨC GIÁ VỐN BÌNH QUÂN GIA QUYỀN
            $total_qty_calc = $old_qty + $new_qty;
            if ($total_qty_calc > 0) {
                $updated_cost = (($old_qty * $old_price) + ($new_qty * $new_price)) / $total_qty_calc;
            } else {
                $updated_cost = $new_price;
            }

            // --- SỬA LỖI TẠI ĐÂY ---
            // 1. Cập nhật GIÁ VỐN vào bảng giaca (KHÔNG cập nhật stock tại đây vì Trigger sẽ tự cộng)
            $conn->query("UPDATE giaca SET cost_price = $updated_cost WHERE product_id = $p_id");
            
            // 2. Cập nhật GIÁ BÁN/GIÁ HIỂN THỊ ở bảng san_pham (nếu cần đồng bộ)
            $conn->query("UPDATE san_pham SET price = $updated_cost WHERE id = $p_id");
        }

        // 3. Chốt phiếu (Chuyển status sang 1)
        // NGAY KHI LỆNH NÀY CHẠY, TRIGGER 'trg_after_update_phieu_nhap_hang' SẼ TỰ CỘNG KHO LẦN DUY NHẤT
        $conn->query("UPDATE phieu_nhap_hang SET status = 1 WHERE import_date = '$date' AND import_batch = $batch");

        $conn->commit();
        header("Location: qlnhaphang.php?msg=success");
        exit();

    } catch (Exception $e) {
        $conn->rollback();
        die("Lỗi xử lý: " . $e->getMessage());
    }
}

/* ==========================================================
   2. LOGIC XÓA PHIẾU NHẬP
   ========================================================== */
if (isset($_GET['delete_batch'])) {
    $date = $conn->real_escape_string($_GET['date']);
    $batch = intval($_GET['batch']);

    $check = $conn->query("SELECT status FROM phieu_nhap_hang WHERE import_date = '$date' AND import_batch = $batch LIMIT 1")->fetch_assoc();
    
    if ($check && $check['status'] == 0) {
        $conn->query("DELETE FROM phieu_nhap_hang WHERE import_date = '$date' AND import_batch = $batch");
        header("Location: qlnhaphang.php?msg=deleted");
    } else {
        header("Location: qlnhaphang.php?msg=error_locked");
    }
    exit();
}

header("Location: qlnhaphang.php");
?>