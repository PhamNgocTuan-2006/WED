<?php
require_once 'config.php';
if (isset($_POST['save_cat'])) {
    $name = mysqli_real_escape_string($conn, $_POST['cat_name']);
    $conn->query("INSERT INTO danh_muc (name) VALUES ('$name')");
    $new_id = $conn->insert_id;

    if (!empty($_POST['keys'])) {
        foreach ($_POST['keys'] as $key) {
            $k = mysqli_real_escape_string($conn, trim($key));
            if ($k != "") $conn->query("INSERT INTO danhmuc_spec_keys (category_id, spec_key) VALUES ($new_id, '$k')");
        }
    }
    header("Location: sanpham.php?view=categories"); exit();
}
include 'thanhmenu.php';
?>
<div class="content-wrapper" style="max-width:700px; margin:50px auto;">
    <form method="POST" style="background:var(--bg-card); padding:35px; border-radius:20px; border:1px solid rgba(255,255,255,0.05);">
        <h2 style="color:var(--accent-blue); margin-bottom:25px;">➕ THÊM LOẠI HÀNG MỚI</h2>
        
        <label style="color:var(--text-dim); font-size:0.7rem; font-weight:700; text-transform:uppercase;">Tên danh mục (Ví dụ: Tai nghe Gaming)</label>
        <input type="text" name="cat_name" required style="width:100%; padding:12px; background:#000; color:white; border:1px solid #334155; border-radius:8px; margin:10px 0 25px 0;">
        
        <label style="color:var(--text-dim); font-size:0.7rem; font-weight:700; text-transform:uppercase;">Bộ khung thông số (Spec Keys)</label>
        <div id="key-list">
            <input type="text" name="keys[]" placeholder="VD: Kiểu kết nối" style="width:100%; padding:12px; background:#000; color:white; border:1px solid #334155; border-radius:8px; margin-top:10px;">
        </div>
        <button type="button" onclick="addMoreKey()" style="background:none; border:1px dashed var(--accent-blue); color:var(--accent-blue); width:100%; padding:10px; margin:15px 0; border-radius:8px; cursor:pointer;">+ Thêm ô nhập thông số</button>
        
        <button type="submit" name="save_cat" style="width:100%; padding:15px; background:var(--accent-blue); color:white; border:none; border-radius:10px; font-weight:800; cursor:pointer; margin-top:10px;">LƯU DANH MỤC</button>
        <a href="sanpham.php?view=categories" style="display:block; text-align:center; color:var(--text-dim); margin-top:20px; text-decoration:none;">← Quay lại</a>
    </form>
</div>
<script>
function addMoreKey() {
    const input = document.createElement('input');
    input.type = 'text'; input.name = 'keys[]'; input.placeholder = 'Thông số tiếp theo...';
    input.style = 'width:100%; padding:12px; background:#000; color:white; border:1px solid #334155; border-radius:8px; margin-top:10px;';
    document.getElementById('key-list').appendChild(input);
}
</script>