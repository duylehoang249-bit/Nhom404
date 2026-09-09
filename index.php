<?php
require_once 'db.php';

// Lấy Thông tin Cấu hình Hệ thống từ CSDL
$info_res = $conn->query("SELECT * FROM system_info LIMIT 1");
$sys_info = $info_res->fetch_assoc();

// Xử lý Lọc & Tìm kiếm
$type_filter = isset($_GET['type']) ? $_GET['type'] : '';
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';

$sql = "SELECT * FROM pitches WHERE 1=1";
if (!empty($type_filter)) {
    $sql .= " AND type = '$type_filter'";
}
if (!empty($search_query)) {
    $sql .= " AND name LIKE '%$search_query%'";
}
$pitches = $conn->query($sql);

// Xử lý gửi phiếu Đặt sân
$booking_msg = '';
$booking_error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['btn_booking'])) {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit();
    }
    $pitch_id = (int)$_POST['pitch_id'];
    $user_id = (int)$_SESSION['user_id'];
    $booking_date = $_POST['booking_date'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];

    // Định nghĩa giờ mở cửa & đóng cửa của sân bóng
    $open_time = "05:00";
    $close_time = "22:00";

    // 1. Kiểm tra thời gian kết thúc phải lớn hơn thời gian bắt đầu
    if (strtotime($end_time) <= strtotime($start_time)) {
        $booking_error = "Thời gian kết thúc phải lớn hơn thời gian bắt đầu!";
    } 
    // 2. Kiểm tra giờ đặt sân có nằm trong khung giờ hoạt động (05:00 - 22:00) hay không
    elseif (strtotime($start_time) < strtotime($open_time) || strtotime($end_time) > strtotime($close_time)) {
        $booking_error = "Sân bóng chỉ hoạt động từ " . $open_time . " đến " . $close_time . ". Vui lòng chọn lại khung giờ!";
    } 
    else {
        // 3. Kiểm tra trùng lịch đặt sân trong CSDL (Bỏ qua những phiếu đã bị hủy)
        $check_stmt = $conn->prepare("
            SELECT start_time, end_time 
            FROM bookings 
            WHERE pitch_id = ? 
              AND booking_date = ? 
              AND status != 'Đã hủy'
              AND (start_time < ? AND end_time > ?)
            LIMIT 1
        ");
        $check_stmt->bind_param("isss", $pitch_id, $booking_date, $end_time, $start_time);
        $check_stmt->execute();
        $check_res = $check_stmt->get_result();

        if ($check_res->num_rows > 0) {
            $existed_booking = $check_res->fetch_assoc();
            $booking_error = "Sân bóng đã được đặt trong khoảng thời gian này (" . $existed_booking['start_time'] . " - " . $existed_booking['end_time'] . "). Vui lòng chọn khung giờ khác!";
        } else {
            // 4. Lấy giá tiền theo giờ của sân từ Database
            $pitch_stmt = $conn->prepare("SELECT price_per_hour FROM pitches WHERE id = ?");
            $pitch_stmt->bind_param("i", $pitch_id);
            $pitch_stmt->execute();
            $pitch_data = $pitch_stmt->get_result()->fetch_assoc();

            if ($pitch_data) {
                $price_per_hour = $pitch_data['price_per_hour'];

                // 5. Tính số giờ đá (DateTime Diff)
                $start = new DateTime($booking_date . ' ' . $start_time);
                $end = new DateTime($booking_date . ' ' . $end_time);
                $interval = $start->diff($end);
                
                // Quy đổi tổng số phút ra số giờ (Ví dụ: 1h30p = 1.5 giờ)
                $hours = $interval->h + ($interval->i / 60) + ($interval->days * 24);
                
                // 6. Tính tổng tiền chuẩn
                $total_price = $hours * $price_per_hour;

                // 7. Lưu phiếu đặt sân vào DB
                $stmt = $conn->prepare("INSERT INTO bookings (user_id, pitch_id, booking_date, start_time, end_time, total_price) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("iisssd", $user_id, $pitch_id, $booking_date, $start_time, $end_time, $total_price);
                
                if ($stmt->execute()) {
                    $booking_msg = "Đặt lịch thành công! Bạn có thể nhấn vào 'Lịch sử đặt sân' để kiểm tra trạng thái phiếu đặt.";
                } else {
                    $booking_error = "Có lỗi xảy ra khi thực hiện đặt sân. Vui lòng thử lại!";
                }
            }
        }
    }
}

// Lấy danh sách lịch sử đặt sân của người dùng hiện tại
$user_bookings = null;
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $stmt_history = $conn->prepare("
        SELECT b.*, p.name AS pitch_name, p.type AS pitch_type 
        FROM bookings b 
        JOIN pitches p ON b.pitch_id = p.id 
        WHERE b.user_id = ? 
        ORDER BY b.id DESC
    ");
    $stmt_history->bind_param("i", $user_id);
    $stmt_history->execute();
    $user_bookings = $stmt_history->get_result();
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đặt Lịch Sân Bóng - <?= htmlspecialchars($sys_info['brand_name']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --navy: #0B2545; --orange: #F26419; }
        .bg-navy { background-color: var(--navy); }
        .btn-orange { background-color: var(--orange); color: white; }
        .btn-orange:hover { background-color: #d8530d; color: white; }
        .card-pitch img { height: 200px; object-fit: cover; }
    </style>
</head>
<body class="bg-light">

<!-- Navbar Navigation -->
<nav class="navbar navbar-expand-lg navbar-dark bg-navy sticky-top shadow-sm">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center" href="index.php">
            <img src="assets/images/logo-sanbong.png" alt="Logo" height="45" class="me-2 bg-white rounded p-1">
            <span class="fw-bold text-white fs-4"><?= htmlspecialchars($sys_info['brand_name']) ?></span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto ms-3">
                <li class="nav-item"><a class="nav-link active" href="index.php">Trang chủ</a></li>
                <li class="nav-item"><a class="nav-link" href="#danh-sach-san">Danh sách sân</a></li>
                <?php if(isset($_SESSION['user_id'])): ?>
                    <li class="nav-item">
                        <a class="nav-link text-warning fw-bold" href="#" data-bs-toggle="modal" data-bs-target="#historyModal">
                            <i class="fa-solid fa-clock-rotate-left me-1"></i> Lịch sử đặt sân
                        </a>
                    </li>
                <?php endif; ?>
                <li class="nav-item"><a class="nav-link" href="#lien-he">Liên hệ chủ sân</a></li>
                <?php if(isset($_SESSION['role']) && $_SESSION['role'] == 'admin'): ?>
                    <li class="nav-item"><a class="nav-link text-warning fw-bold" href="admin.php"><i class="fa-solid fa-gear me-1"></i> Trang Quản Trị</a></li>
                <?php endif; ?>
            </ul>
            <div class="d-flex align-items-center text-white">
                <?php if(isset($_SESSION['email'])): ?>
                    <span class="me-3"><i class="fa-solid fa-circle-user"></i> <strong><?= htmlspecialchars($_SESSION['fullname']) ?></strong></span>
                    <a href="logout.php" class="btn btn-outline-light btn-sm">Đăng xuất</a>
                <?php else: ?>
                    <a href="login.php" class="btn btn-outline-light btn-sm me-2">Đăng nhập</a>
                    <a href="register.php" class="btn btn-orange btn-sm">Đăng ký</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>

<!-- Banner Thông tin Sân & SĐT Chủ sân -->
<div class="bg-navy text-white py-4 border-top border-warning border-3">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-7">
                <h2 class="fw-bold text-warning mb-3">HỆ THỐNG SÂN BÓNG ĐÁ <?= htmlspecialchars($sys_info['brand_name']) ?></h2>
                <p class="mb-2 fs-6">
                    <i class="fa-solid fa-location-dot text-danger me-2"></i><strong>Địa chỉ sân bóng:</strong> <?= htmlspecialchars($sys_info['address']) ?>
                </p>
                <p class="mb-2 fs-6">
                    <i class="fa-solid fa-clock text-warning me-2"></i><strong>Giờ hoạt động:</strong> <span class="badge bg-warning text-dark fs-6 ms-1">05:00 - 22:00</span> (Hằng ngày)
                </p>
                <p class="mb-2 fs-6">
                    <i class="fa-solid fa-phone text-success me-2"></i><strong>SĐT Chủ sân (<?= htmlspecialchars($sys_info['owner_name']) ?>):</strong> 
                    <a href="tel:<?= htmlspecialchars($sys_info['owner_phone']) ?>" class="text-warning text-decoration-none fw-bold"><?= htmlspecialchars($sys_info['owner_phone']) ?></a>
                </p>
                <p class="mb-0 fs-6">
                    <i class="fa-solid fa-layer-group text-info me-2"></i><strong>Quy mô hệ thống:</strong> 10 sân 5 | 5 sân 7 | 2 sân 11 (Cỏ nhân tạo FIFA)
                </p>
            </div>
            <div class="col-md-5 text-center mt-3 mt-md-0">
                <img src="assets/images/sanbong.jpg" class="img-fluid rounded shadow border border-2 border-warning" style="max-height: 200px; width: 100%; object-fit: cover;" alt="Sân bóng">
            </div>
        </div>
    </div>
</div>

<div class="container my-5" id="danh-sach-san">
    <?php if(!empty($booking_msg)): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fa-solid fa-circle-check me-2"></i><?= $booking_msg ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if(!empty($booking_error)): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fa-solid fa-triangle-exclamation me-2"></i><?= $booking_error ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Bộ Lọc Danh Mục & Tìm Kiếm -->
    <div class="row mb-4">
        <div class="col-md-8 mb-2">
            <div class="btn-group" role="group">
                <a href="index.php" class="btn btn-outline-primary <?= empty($type_filter)?'active':'' ?>">Tất cả sân</a>
                <a href="index.php?type=Sân 5" class="btn btn-outline-primary <?= $type_filter=='Sân 5'?'active':'' ?>">Sân 5 (10 sân)</a>
                <a href="index.php?type=Sân 7" class="btn btn-outline-primary <?= $type_filter=='Sân 7'?'active':'' ?>">Sân 7 (5 sân)</a>
                <a href="index.php?type=Sân 11" class="btn btn-outline-primary <?= $type_filter=='Sân 11'?'active':'' ?>">Sân 11 (2 sân)</a>
            </div>
        </div>
        <div class="col-md-4">
            <form method="GET" class="d-flex">
                <input type="text" name="search" class="form-control me-2" placeholder="Tìm tên sân..." value="<?= htmlspecialchars($search_query) ?>">
                <button type="submit" class="btn text-white" style="background-color: #0B2545;">Tìm</button>
            </form>
        </div>
    </div>

    <!-- Grid Hiển thị Sân Bóng -->
    <div class="row g-4">
        <?php if($pitches->num_rows > 0): ?>
            <?php while($row = $pitches->fetch_assoc()): ?>
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="card card-pitch shadow-sm h-100 border-0">
                        <img src="assets/images/<?= htmlspecialchars($row['image']) ?>" class="card-img-top" alt="<?= htmlspecialchars($row['name']) ?>">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="badge bg-warning text-dark"><?= htmlspecialchars($row['type']) ?></span>
                                <!-- Hiển thị Trạng Thái Sân -->
                                <?php if($row['status'] == 'Trống'): ?>
                                    <span class="badge bg-success"><i class="fa-solid fa-circle-check me-1"></i> Sân trống</span>
                                <?php else: ?>
                                    <span class="badge bg-danger"><i class="fa-solid fa-screwdriver-wrench me-1"></i> Đang bảo trì</span>
                                <?php endif; ?>
                            </div>

                            <h5 class="card-title fw-bold"><?= htmlspecialchars($row['name']) ?></h5>
                            <p class="card-text text-danger fw-bold fs-5 mb-2"><?= number_format($row['price_per_hour'], 0, ',', '.') ?> VNĐ / giờ</p>
                            <p class="text-muted small mb-3"><i class="fa-solid fa-circle-check text-success"></i> Đèn chiếu sáng, miễn phí nước uống</p>

                            <!-- Nút Đặt Lịch hoặc Báo Bảo Trì -->
                            <?php if($row['status'] == 'Trống'): ?>
                                <button class="btn btn-orange w-100 fw-bold" data-bs-toggle="modal" data-bs-target="#bookingModal<?= $row['id'] ?>">Đặt lịch ngay</button>
                            <?php else: ?>
                                <button class="btn btn-secondary w-100 fw-bold" disabled><i class="fa-solid fa-ban me-1"></i> Tạm ngưng dịch vụ</button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Modal Đặt Sân (Chỉ hoạt động khi sân Trống) -->
                <?php if($row['status'] == 'Trống'): ?>
                <div class="modal fade" id="bookingModal<?= $row['id'] ?>" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <form method="POST">
                                <div class="modal-header bg-navy text-white">
                                    <h5 class="modal-title">Đặt lịch: <?= htmlspecialchars($row['name']) ?></h5>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <!-- Khung hiển thị Thông tin Sân & Chủ sân -->
                                    <div class="p-3 mb-3 bg-light rounded border">
                                        <p class="mb-1 small">
                                            <i class="fa-solid fa-location-dot text-danger me-2"></i><strong>Địa chỉ:</strong> <?= htmlspecialchars($sys_info['address']) ?>
                                        </p>
                                        <p class="mb-0 small">
                                            <i class="fa-solid fa-user-tie text-primary me-2"></i><strong>Chủ sân:</strong> <?= htmlspecialchars($sys_info['owner_name']) ?> - 
                                            <i class="fa-solid fa-phone text-success ms-1 me-1"></i><strong>SĐT:</strong> <a href="tel:<?= htmlspecialchars($sys_info['owner_phone']) ?>" class="text-primary text-decoration-none fw-bold"><?= htmlspecialchars($sys_info['owner_phone']) ?></a>
                                        </p>
                                    </div>

                                    <input type="hidden" name="pitch_id" value="<?= $row['id'] ?>">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Chọn ngày đá</label>
                                        <input type="date" name="booking_date" class="form-control" required min="<?= date('Y-m-d') ?>">
                                    </div>
                                    <div class="row">
                                        <div class="col-6 mb-3">
                                            <label class="form-label fw-bold">Giờ bắt đầu</label>
                                            <input type="time" name="start_time" class="form-control" min="05:00" max="22:00" required>
                                        </div>
                                        <div class="col-6 mb-3">
                                            <label class="form-label fw-bold">Giờ kết thúc</label>
                                            <input type="time" name="end_time" class="form-control" min="05:00" max="22:00" required>
                                        </div>
                                    </div>
                                    <div class="alert alert-info py-2 small mb-0">
                                        Đơn giá: <strong><?= number_format($row['price_per_hour'], 0, ',', '.') ?> VNĐ/giờ</strong><br>
                                        Giờ hoạt động: <strong class="text-danger">05:00 - 22:00</strong> hằng ngày<br>
                                        <small class="text-muted">* Tổng tiền sẽ được hệ thống tự động tính chính xác theo số giờ đá.</small>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                                    <button type="submit" name="btn_booking" class="btn btn-orange">Xác nhận đặt</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="col-12 text-center py-5">
                <p class="text-muted">Không tìm thấy sân bóng phù hợp!</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Trạng Thái & Lịch Sử Đặt Sân Của Người Dùng -->
<?php if(isset($_SESSION['user_id'])): ?>
<div class="modal fade" id="historyModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-navy text-white">
                <h5 class="modal-title fw-bold"><i class="fa-solid fa-clock-rotate-left me-2"></i>Trạng Thái Phiếu Đặt Sân Của Bạn</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <?php if($user_bookings && $user_bookings->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Mã Phiếu</th>
                                    <th>Tên Sân</th>
                                    <th>Ngày Đá</th>
                                    <th>Khung Giờ</th>
                                    <th>Tổng Tiền</th>
                                    <th>Trạng Thái</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($ub = $user_bookings->fetch_assoc()): ?>
                                    <tr>
                                        <td><strong>#<?= $ub['id'] ?></strong></td>
                                        <td>
                                            <strong><?= htmlspecialchars($ub['pitch_name']) ?></strong><br>
                                            <small class="text-muted"><?= htmlspecialchars($ub['pitch_type']) ?></small>
                                        </td>
                                        <td><?= date('d/m/Y', strtotime($ub['booking_date'])) ?></td>
                                        <td><?= $ub['start_time'] ?> - <?= $ub['end_time'] ?></td>
                                        <td class="text-danger fw-bold"><?= number_format($ub['total_price'], 0, ',', '.') ?> VNĐ</td>
                                        <td>
                                            <?php if($ub['status'] == 'Đã xác nhận'): ?>
                                                <span class="badge bg-success py-2 px-3"><i class="fa-solid fa-circle-check me-1"></i> Đã xác nhận</span>
                                            <?php elseif($ub['status'] == 'Chờ xác nhận'): ?>
                                                <span class="badge bg-warning text-dark py-2 px-3"><i class="fa-solid fa-clock me-1"></i> Chờ xác nhận</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger py-2 px-3"><i class="fa-solid fa-circle-xmark me-1"></i> Đã hủy</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4 text-muted">
                        <i class="fa-solid fa-calendar-xmark fs-1 mb-2"></i>
                        <p class="mb-0">Bạn chưa thực hiện phiếu đặt sân nào.</p>
                    </div>
                <?php endif; ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Footer -->
<footer class="bg-navy text-white py-4 mt-5" id="lien-he">
    <div class="container text-center">
        <p class="mb-1 fw-bold">HỆ THỐNG SÂN BÓNG ĐÁ <?= htmlspecialchars($sys_info['brand_name']) ?></p>
        <p class="small mb-1"><strong>Địa chỉ:</strong> <?= htmlspecialchars($sys_info['address']) ?></p>
        <p class="small mb-0"><strong>Chủ sân:</strong> <?= htmlspecialchars($sys_info['owner_name']) ?> - <strong>SĐT Hotline:</strong> <?= htmlspecialchars($sys_info['owner_phone']) ?></p>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
