<?php
require_once 'db.php';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    $stmt = $conn->prepare("SELECT id, email, password, fullname, role FROM users WHERE email = ? AND password = ?");
    $stmt->bind_param("ss", $email, $password);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($user = $result->fetch_assoc()) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['fullname'] = $user['fullname'];
        $_SESSION['role'] = $user['role'];
        
        if ($user['role'] == 'admin') {
            header("Location: admin.php");
        } else {
            header("Location: index.php");
        }
        exit();
    } else {
        $error = "Email hoặc mật khẩu không chính xác!";
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Đăng nhập - 404 SPORTS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex align-items-center vh-100">
<div class="container" style="max-width: 400px;">
    <div class="card shadow border-0">
        <div class="card-body p-4 text-center">
            <img src="assets/images/logo-sanbong.png" alt="Logo" class="img-fluid mb-3" style="max-height: 80px;">
            <h4 class="fw-bold mb-3" style="color: #0B2545;">ĐĂNG NHẬP HỆ THỐNG</h4>
            <?php if($error): ?><div class="alert alert-danger py-2"><?= $error ?></div><?php endif; ?>
            <form method="POST">
                <div class="mb-3 text-start">
                    <label class="form-label fw-bold">Địa chỉ Email</label>
                    <input type="email" name="email" class="form-control" placeholder="admin@gmail.com hoặc duy@gmail.com" required>
                </div>
                <div class="mb-3 text-start">
                    <label class="form-label fw-bold">Mật khẩu</label>
                    <input type="password" name="password" class="form-control" placeholder="Nhập mật khẩu" required>
                </div>
                <button type="submit" class="btn text-white w-100 py-2 fw-bold" style="background-color: #0B2545;">Đăng nhập</button>
            </form>
            <div class="mt-3 text-center">
                <small>Chưa có tài khoản? <a href="register.php">Đăng ký tài khoản mới</a></small>
            </div>
        </div>
    </div>
</div>
</body>
</html>