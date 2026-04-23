<?php
include "connect.php";

// Kiểm tra có id không
if(isset($_GET['id'])){
    $id = $_GET['id'];

    // Xóa danh mục
    $sql = "DELETE FROM danh_muc WHERE id = $id";

    if($conn->query($sql)){
        echo "<script>
                alert('Xóa danh mục thành công');
                window.location='danhmucsanpham.php';
              </script>";
    } else {
        echo "Lỗi: " . $conn->error;
    }

} else {
    echo "<script>
            alert('Không tìm thấy danh mục');
            window.location='danhmucsanpham.php';
          </script>";
}
?>