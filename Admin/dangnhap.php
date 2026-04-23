<?php
session_start();
require_once 'config.php';
$error = "";

if (isset($_POST['login'])) {
    $user = mysqli_real_escape_string($conn, $_POST['user']);
    $pass = $_POST['pass'];

    // Truy vấn thông tin từ bảng admin đã tạo
    $sql = "SELECT * FROM admin WHERE username = '$user' AND password = '$pass'";
    $result = mysqli_query($conn, $sql);

    if (mysqli_num_rows($result) > 0) {
        $data = mysqli_fetch_assoc($result);
        $_SESSION['admin_id'] = $data['id'];
        $_SESSION['admin_fullname'] = $data['fullname'];
        header('Location: index.php');
        exit();
    } else {
        $error = "Thông tin xác thực không chính xác. Vui lòng thử lại.";
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hệ thống Quản trị Trung tâm | SGU IT</title>
    <style>
        :root {
            --bg-color: #0f172a;
            --card-bg: #1e293b;
            --primary-accent: #3b82f6;
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --error-color: #ef4444;
        }

        body {
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background-color: var(--bg-color);
            background-image: radial-gradient(circle at 50% 50%, #1e293b 0%, #0f172a 100%);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            color: var(--text-main);
        }

        .login-container {
            width: 100%;
            max-width: 400px;
            padding: 40px;
            background: var(--card-bg);
            border-radius: 16px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .header {
            text-align: center;
            margin-bottom: 32px;
        }

        .header h1 {
            font-size: 1.5rem;
            font-weight: 700;
            letter-spacing: -0.025em;
            margin: 0;
            text-transform: uppercase;
        }

        .header p {
            color: var(--text-muted);
            font-size: 0.875rem;
            margin-top: 8px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 8px;
            color: var(--text-muted);
        }

        input {
            width: 100%;
            padding: 12px 16px;
            background: #0f172a;
            border: 1px solid #334155;
            border-radius: 8px;
            color: white;
            font-size: 1rem;
            transition: all 0.2s;
            box-sizing: border-box;
        }

        input:focus {
            outline: none;
            border-color: var(--primary-accent);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
        }

        button {
            width: 100%;
            padding: 12px;
            background-color: var(--primary-accent);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
            margin-top: 10px;
        }

        button:hover {
            background-color: #2563eb;
        }

        .error-msg {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid var(--error-color);
            color: var(--error-color);
            padding: 12px;
            border-radius: 8px;
            font-size: 0.875rem;
            text-align: center;
            margin-bottom: 20px;
        }

        .footer {
            margin-top: 32px;
            text-align: center;
            font-size: 0.75rem;
            color: var(--text-muted);
        }
    </style>
</head>
<body>

<div class="login-container">
    <div class="header">
        <h1>PHONG CÁCH RIÊNG</h1>
        <p>Private Space</p>
    </div>

    <?php if($error): ?>
        <div class="error-msg"><?php echo $error; ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="form-group">
            <label for="user">Định danh Quản trị</label>
            <input type="text" id="user" name="user" required placeholder="Nhập username...">
        </div>

        <div class="form-group">
            <label for="pass">Mật mã truy cập</label>
            <input type="password" id="pass" name="pass" required placeholder="••••••••">
        </div>

        <button type="submit" name="login">XÁC THỰC TRUY CẬP</button>
    </form>

    <div class="footer">
        &copy; 2026 Khoa Công nghệ Thông tin - Đại học Sài Gòn
    </div>
</div>

</body>
</html>