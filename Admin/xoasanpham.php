```php
<?php
include "connect.php";

// kiểm tra có id không
if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];

    // Kiểm tra sản phẩm đã có phiếu nhập hàng hay chưa
    $sql_check_import = "SELECT COUNT(*) AS total FROM phieu_nhap_hang WHERE product_id = $id";
    $import_result = $conn->query($sql_check_import);
    $has_imports = false;
    if ($import_result && $row = $import_result->fetch_assoc()) {
        $has_imports = (int)$row['total'] > 0;
    }

    if ($has_imports) {
        // Nếu đã có nhập hàng thì chỉ đánh dấu ẩn sản phẩm
        $sql_hide = "UPDATE san_pham SET status = 0 WHERE id = $id";
        if ($conn->query($sql_hide) === TRUE) {
            header("Location: sanpham.php?msg=hidden");
            exit();
        } else {
            echo "Lỗi ẩn sản phẩm: " . $conn->error;
        }
    } else {
        // Nếu chưa nhập hàng thì xóa hẳn trong CSDL
        $sql_img = "SELECT main_image FROM san_pham WHERE id = $id";
        $result_img = $conn->query($sql_img);

        if ($result_img && $result_img->num_rows > 0) {
            $row = $result_img->fetch_assoc();
            $image = $row['main_image'];

            if (!empty($image) && file_exists(__DIR__ . '/../DOAN/image/' . $image)) {
                @unlink(__DIR__ . '/../DOAN/image/' . $image);
            }
        }

        $sql_delete = "DELETE FROM san_pham WHERE id = $id";
        if ($conn->query($sql_delete) === TRUE) {
            header("Location: sanpham.php?msg=deleted");
            exit();
        } else {
            echo "Lỗi xóa: " . $conn->error;
        }
    }

} else {
    echo "Không có ID!";
}
?>
```
