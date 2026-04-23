<?php
session_start();
include "connect.php";

/* xử lý đăng xuất ngay trong trangchu.php */
if(isset($_GET["logout"]))
{
    session_unset();
    session_destroy();

    header("Location: index.php");
    exit();
}

/* lấy thông tin user nếu đã login */
if(isset($_SESSION["user_name"]))
{
    $username = $_SESSION["user_name"];

    $sql = "SELECT * FROM users WHERE user_name='$username'";
    $result = mysqli_query($conn,$sql);
    $user = mysqli_fetch_assoc($result);
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="css/trangchu.css">
  <link rel="stylesheet" href="css/bootstrap-icons.css">
  <title>Phong Cách Xanh</title>
</head>

<body>
  <header>
    <nav class="menu">
      <div class="logo">
        <a href="trangchu.php"><img src="image/test2.png" alt="logo" class="logo-img"></a>
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
        <form class="search-box" action="timkiem.php" method="get">
    <a href="#" id="searchToggle"><i class="bi bi-search"></i></a>
    <input type="text" id="searchInput" name="q" placeholder="Tìm...">
</form>
  
        <div class="box-login">
         <a href="#" id="Login"><i class="bi bi-person"></i></a>
          <ul class="dropdown-menu">
          <li><a href="thongtinkhachhang.php">Thông tin người dùng</a></li>
          <li><a href="lichsumuahang.php">Đơn hàng của bạn</a></li>
          <li><a href="trangchu.php?logout=true">Đăng xuất</a></li>
        </ul>
      </div>

        <a href="cart.php"><i class="bi bi-bag"></i></a>
      </div>
    </nav>
   
    <section class="brand-hero" aria-label="Thương hiệu Scyrox">
      <img src="image/mainpics.jpg" alt="Scyrox Hero" class="brand-hero__img">
      <div class="brand-hero__text">
        <p class="brand-hero__subtitle">
          Thương hiệu gaming gear mới nhất được Phong Cách Xanh phân phối
        </p>
        <h1 class="brand-hero__title">SCYROX</h1>
      </div>
    </section>

  </header>
  
  <section class="home-categories" aria-label="Danh mục sản phẩm">
    <div class="cat-grid">
      <a class="cat-card" href="gamingmouse.php" title="Chuột gaming">
        <div class="cat-icon"><img src="image/icon-chuot-gaming.webp" alt="Chuột gaming"></div>
        <p class="cat-title">Chuột gaming</p>
      </a>

      <a class="cat-card" href="gamingkey.php" title="Bàn phím cơ & HE">
        <div class="cat-icon"><img src="image/icon-ban-phim-gaming.webp" alt="Bàn phím cơ & HE"></div>
        <p class="cat-title">Bàn phím cơ &amp; HE</p>
      </a>

      <a class="cat-card" href="mousepad.php" title="Lót chuột">
        <div class="cat-icon"><img src="image/icon-lot-chuot.webp" alt="Lót chuột"></div>
        <p class="cat-title">Lót chuột</p>
      </a>
    </div>
  </section>
  
  <section class="new-gear">
    <p class="newgear-subtitle">
      Nhà Xanh test kỹ từng món – lỡ yêu ngay từ cú click đầu thì... không ai cản được đâu 😎
    </p>
    <h2 class="newgear-title">Gear mới về – cẩn thận dính ví</h2>
  </section>

  <div class="khung-list-product">
    <div class="list-product">

      <div class="product_card">
        <div class="img_product">
          <a href="chitietsanpham.php?id=35"><img src="image/scyroxv61.webp" alt="" class="product_img"></a>
          <a href="chitietsanpham.php?id=35"><img src="image/scyroxv6back.webp" alt="" class="backproduct_img"></a>
        </div>
        <div class="info_container">
          <div class="name_product"><a href="chitietsanpham.php?id=35">Scyrox V6 – Chuột đối xứng kèm dongle 8K</a></div>
          <div class="cost_product"><a>1.750.000₫</a></div>
          <div class="color_product">
            <a><img src="image/scyroxvr1.webp" class="img_color"></a>
            <a><img src="image/scyroxvr2.webp" class="img_color"></a>
            <a><img src="image/scyroxvr3.webp" class="img_color"></a>
          </div>
        </div>
      </div>

      <div class="product_card">
        <div class="img_product">
          <a href="chitietsanpham.php?id=1"><img src="image/made68pro.webp" alt="" class="product_img"></a>
          <a href="chitietsanpham.php?id=1"><img src="image/made68proback.webp" alt="" class="backproduct_img"></a>
        </div>
        <div class="info_container">
          <div class="name_product"><a href="chitietsanpham.php?id=1">MelGeek MADE68 Pro – Bàn phím HE Rapid Trigger</a>
          </div>
          <div class="cost_product"><span>3.840.000₫</span></div>
          <div class="color_product">
            <a><img src="image/made68pro.webp" class="img_color"></a>
            <a><img src="image/made68provr1.webp" class="img_color"></a>
          </div>
        </div>
      </div>

      <div class="product_card">
        <div class="img_product">
          <a href="chitietsanpham.php?id=70"><img src="image/MX4.webp" alt="" class="product_img"></a>
          <a href="chitietsanpham.php?id=70"><img src="image/MX4back.webp" alt="" class="backproduct_img"></a>
        </div>
        <div class="info_container">
          <div class="name_product"><a href="chitietsanpham.php?id=70">Logitech MX Master 4 – Chuột không dây văn phòng</a>
          </div>
          <div class="cost_product"><span>2.890.000₫</span></div>
          <div class="color_product">
            <a><img src="image/MX4.webp" class="img_color"></a>
            <a><img src="image/lomxvr1.webp" class="img_color"></a>
          </div>
        </div>
      </div>

      <div class="product_card">
        <div class="img_product">
          <a href="chitietsanpham.php?id=64"><img src="image/galaxy.webp" alt="" class="product_img"></a>
          <a href="chitietsanpham.php?id=64"><img src="image/galaxyback.webp" alt="" class="backproduct_img"></a>
        </div>
        <div class="info_container">
          <div class="name_product"><a href="chitietsanpham.php?id=64">Filco Minila-R Convertible Galaxy Gold – Bàn phím cơ 60% ánh vàng</a>
          </div>
          <div class="cost_product"><a>6.160.000₫</a></div>
        </div>
      </div>

      <div class="product_card">
        <div class="img_product">
          <a href="chitietsanpham.php?id=44"><img src="image/demon1kinh.webp" alt="" class="product_img"></a>
          <a href="chitietsanpham.php?id=44"><img src="image/demon1kinhback.webp" alt="" class="backproduct_img"></a>
        </div>
        <div class="info_container">
          <div class="name_product"><a href="chitietsanpham.php?id=44">Yuki Aim x Demon1 Glass Pad – Lót chuột kính speed
              giới hạn</a></div>
          <div class="cost_product"><span>4.180.000₫</span></div>
        </div>
      </div>

    </div>
  </div>
  
  <main>
    
    <section class="why-choose">
      <h2 class="why-title">Vì sao nhà Xanh luôn trong loadout của pro?</h2>

      <div class="why-grid">
        <div class="why-item">
          <div class="why-icon"><img src="image/Lydo1.png" alt="Tư vấn đúng cách"></div>
          <h3 class="why-heading">Tư vấn đúng cách bạn chơi</h3>
          <p class="why-desc">
            Nhà Xanh hiểu gear lẫn game. Trước khi tư vấn, tụi mình hỏi kỹ: aim cổ tay hay cánh tay, flick hay tracking.
            Nắm chắc lối chơi rồi mới gợi ý.
          </p>
        </div>

        <div class="why-item">
          <div class="why-icon"><img src="image/Lydo2.png" alt="Gear cao cấp"></div>
          <h3 class="why-heading">Gear cao cấp – test kỹ mới bán</h3>
          <p class="why-desc">
            Mỗi món đều qua bài test thực chiến: tracking chuẩn, cảm giác aim ổn định, build bền bỉ.
            Bán vì chất lượng, không phải vì trend.
          </p>
        </div>

        <div class="why-item">
          <div class="why-icon"><img src="image/Lydo3.png" alt="Luôn có hàng mới"></div>
          <h3 class="why-heading">Luôn có hàng mới nhanh nhất</h3>
          <p class="why-desc">
            Chuột, phím vừa ra mắt quốc tế, chỉ vài ngày sau đã có trên kệ nhà Xanh – đủ màu, đủ size cho anh em chọn.
          </p>
        </div>

        <div class="why-item">
          <div class="why-icon"><img src="image/Lydo4.png" alt="Hậu mãi nhanh gọn"></div>
          <h3 class="why-heading">Hậu mãi nhanh gọn, rõ ràng</h3>
          <p class="why-desc">
            Gặp vấn đề? Nhắn một tiếng, tụi mình xử lý liền. Chính sách minh bạch, hỗ trợ tận tay để bạn tập trung vào
            trận đấu.
          </p>
        </div>
      </div>
    </section>
    
    <section class="testimonials">
      <h2 class="ts-title">Đây là lời thật lòng sau khi mua gear ở nhà Xanh</h2>

      <div class="ts-grid">
      
        <article class="ts-card">
          <div class="ts-header">
            <img class="ts-avatar" src="image/Danhgia1.png" alt="Zoeyyy">
            <div>
              <div class="ts-stars">★★★★★</div>
              <div class="ts-name">Zoeyyy</div>
            </div>
          </div>
          <h3 class="ts-heading">Nhà phê bình đánh giá dụng cụ hỗ trợ chơi game số 1 Việt Nam</h3>
          <p class="ts-text">
            Mình thấy mua hàng tại Phong Cách Xanh nhanh chóng và nếu có thắc mắc gì thì có thể ra showroom để trải
            nghiệm thử những món mình muốn mua, đã vậy mua hàng còn được tích điểm giảm giá nữa, quá đã.
          </p>
        </article>

        
        <article class="ts-card">
          <div class="ts-header">
            <img class="ts-avatar" src="image/Danhgia2.png" alt="Alex Nguyen">
            <div>
              <div class="ts-stars">★★★★★</div>
              <div class="ts-name">Alex Nguyen</div>
            </div>
          </div>
          <h3 class="ts-heading">Founder VGS</h3>
          <p class="ts-text">
            Là một người yêu công nghệ và sưu tầm gears (phím chuột) lâu năm. PCX luôn là địa chỉ tin cậy để tìm những
            món đồ mới nhất – độc nhất. Ngoài ra với dịch vụ và hậu mãi tuyệt vời, PCX chưa bao giờ làm tôi thất vọng.
          </p>
        </article>

       
        <article class="ts-card">
          <div class="ts-header">
            <img class="ts-avatar" src="image/Danhgia3.png" alt="OcleoP">
            <div>
              <div class="ts-stars">★★★★★</div>
              <div class="ts-name">OcleoP</div>
            </div>
          </div>
          <h3 class="ts-heading">Streamer</h3>
          <p class="ts-text">
            Là người thích chơi game FPS có tính cạnh tranh cao, tôi rất kỹ trong việc chọn Gear. Từ khi biết đến Phong
            Cách Xanh, tôi được tư vấn, trải nghiệm sản phẩm tại showroom và thấy những món mà rất ít store ở Việt Nam
            có. Dịch vụ cũng rất chu đáo và nhiệt tình.
          </p>
        </article>
      </div>
    </section>
   
    <section class="brands">
      <h2 class="brands-title">Phân phối chính hãng</h2>
      <p class="brands-desc">
        Nhà Xanh chỉ chọn hãng có sản phẩm đủ ngon để tụi mình muốn xài trước khi bán.
      </p>

      <div class="brands-row">
        <img src="image/LogitechLogo.png" alt="Logitech">
        <img src="image/LogoRazer.png" alt="Razer">
        <img src="image/ScyroxLogo.webp" alt="Scyrox">
        <img src="image/PulsarLogo.png" alt="Pulsar">
        <img src="image/LamzuLogo.avif" alt="Lamzu">
      </div>
    </section>

    <footer class="footer">
      <div class="footer-container">

       
        <div class="footer-about">
          <img src="image/test2.png" alt="logo" class="footer-logo">
          <p>
            Trang web bán chuột và bàn phím chuyên cung cấp các mẫu gaming gear cao cấp, đảm bảo
            chất lượng và hiệu năng, với nhiều lựa chọn phù hợp phong cách của bạn.
          </p>
        </div>

        
        <div class="footer-info">
          <h3>ĐỊA CHỈ</h3>
          <p>273 Đ. An Dương Vương, Phường 3, Quận 5, TP. Hồ Chí Minh</p>
          <p>Việt Nam</p>
        </div>

       
        <div class="footer-info">
          <h3>LIÊN HỆ</h3>
          <p>SDT: 035 2006 9999</p>
          <p>SDT: 091 2006 6666</p>
        </div>

        
        <div class="footer-social">
          <h3>THEO DÕI CHÚNG TÔI</h3>
          <div class="social-icons">
            <a href="#"><i class="bi bi-facebook"></i></a>
            <a href="#"><i class="bi bi-youtube"></i></a>
            <a href="#"><i class="bi bi-instagram"></i></a>
          </div>
        </div>

      </div>

      <div class="footer-bottom">
        <p>© 2026 Phong Cách Xanh. All rights reserved.</p>
      </div>
    </footer>
  </main>
</body>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        // 1. XỬ LÝ SEARCH TOGGLE
        const searchToggle = document.getElementById('searchToggle');
        const searchInput = document.getElementById('searchInput');
        if(searchToggle && searchInput) {
            searchToggle.addEventListener('click', function(e) {
                e.preventDefault(); e.stopPropagation(); 
                searchInput.classList.toggle('active');
                if (searchInput.classList.contains('active')) searchInput.focus();
            });
        }

        // 2. XỬ LÝ DROPDOWN NAV (Gaming Gear / Office Gear)
        const navDropdownLinks = document.querySelectorAll('.has-dropdown > a');
        navDropdownLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault(); e.stopPropagation();
                const parent = this.parentElement;
                // Đóng các menu khác
                document.querySelectorAll('.has-dropdown').forEach(d => { if(d !== parent) d.classList.remove('active'); });
                parent.classList.toggle('active');
            });
        });

        // 3. XỬ LÝ DROPDOWN LOGIN (Icon User)
        const loginIcon = document.getElementById('Login');
        const boxLogin = document.querySelector('.box-login');
        if(loginIcon && boxLogin) {
            loginIcon.addEventListener('click', function(e) {
                e.preventDefault(); e.stopPropagation();
                boxLogin.classList.toggle('active');
            });
        }

        // 4. XỬ LÝ THANH LỌC (ACCORDION)
        const filterHeaders = document.querySelectorAll('.filter-header');
        filterHeaders.forEach(header => {
            header.addEventListener('click', function() {
                this.closest('.filter-box').classList.toggle('active');
            });
        });

        // 5. ĐÓNG TẤT CẢ KHI CLICK RA NGOÀI
        document.addEventListener('click', function(e) {
            if (searchInput && !searchInput.contains(e.target) && e.target !== searchToggle) searchInput.classList.remove('active');
            if (boxLogin && !boxLogin.contains(e.target)) boxLogin.classList.remove('active');
            document.querySelectorAll('.has-dropdown').forEach(d => {
                if(!d.contains(e.target)) d.classList.remove('active');
            });
        });
    });
    </script>
</html>