<?php
require_once 'db.php';
$msg = ''; $error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $fullname = trim($_POST['fullname']);
    $phone = trim($_POST['phone']);

    $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $check->bind_param("s", $email);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        $error = "Email này đã được sử dụng!";
    } else {
        $stmt = $conn->prepare("INSERT INTO users (email, password, fullname, phone) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $email, $password, $fullname, $phone);
        if ($stmt->execute()) {
            $msg = "Đăng ký thành công! <a href='login.php'>Đăng nhập ngay</a>";
        } else {
            $error = "Có lỗi xảy ra khi đăng ký!";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Đăng ký - 404 SPORTS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex align-items-center vh-100">
<div class="container" style="max-width: 450px;">
    <div class="card shadow border-0">
        <div class="card-body p-4 text-center">
            <img src="assets/images/logo-sanbong.png" alt="Logo" class="img-fluid mb-3" style="max-height: 80px;">
            <h4 class="fw-bold mb-3">ĐĂNG KÝ TÀI KHOẢN</h4>
            <?php if($error): ?><div class="alert alert-danger py-2"><?= $error ?></div><?php endif; ?>
            <?php if($msg): ?><div class="alert alert-success py-2"><?= $msg ?></div><?php endif; ?>
            <form method="POST">
                <div class="mb-2 text-start"><label class="form-label fw-bold">Họ và tên</label><input type="text" name="fullname" class="form-control" required></div>
                <div class="mb-2 text-start"><label class="form-label fw-bold">Địa chỉ Email</label><input type="email" name="email" class="form-control" placeholder="name@gmail.com" required></div>
                <div class="mb-2 text-start"><label class="form-label fw-bold">Số điện thoại</label><input type="text" name="phone" class="form-control" required></div>
                <div class="mb-3 text-start"><label class="form-label fw-bold">Mật khẩu</label><input type="password" name="password" class="form-control" required></div>
                <button type="submit" class="btn text-white w-100 py-2 fw-bold" style="background-color: #F26419;">Tạo tài khoản</button>
            </form>
            <div class="mt-3"><small>Đã có tài khoản? <a href="login.php">Đăng nhập</a></small></div>
        </div>
    </div>
</div>
</body>
</html>