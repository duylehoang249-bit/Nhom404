<?php
require_once 'db.php';

// Kiểm tra quyền Admin
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

$msg = '';
$error = '';

// 1. THÊM SÂN BÓNG MỚI (CREATE)
if (isset($_POST['btn_add'])) {
    $name = trim($_POST['name']);
    $type = $_POST['type'];
    $price = $_POST['price'];
    
    $stmt = $conn->prepare("INSERT INTO pitches (name, type, price_per_hour) VALUES (?, ?, ?)");
    $stmt->bind_param("ssd", $name, $type, $price);
    if ($stmt->execute()) {
        $msg = "Thêm sân bóng mới thành công!";
    } else {
        $error = "Lỗi khi thêm sân bóng!";
    }
}

// 2. CẬP NHẬT THÔNG TIN SÂN BÓNG (UPDATE)
if (isset($_POST['btn_edit_pitch'])) {
    $pitch_id = $_POST['pitch_id'];
    $name = trim($_POST['name']);
    $type = $_POST['type'];
    $price = $_POST['price'];
    $status = $_POST['status'];

    $stmt = $conn->prepare("UPDATE pitches SET name = ?, type = ?, price_per_hour = ?, status = ? WHERE id = ?");
    $stmt->bind_param("ssdsi", $name, $type, $price, $status, $pitch_id);
    if ($stmt->execute()) {
        $msg = "Cập nhật thông tin sân thành công!";
    } else {
        $error = "Lỗi khi cập nhật thông tin sân!";
    }
}

// 3. XÓA SÂN BÓNG (DELETE)
if (isset($_GET['delete_pitch'])) {
    $id = (int)$_GET['delete_pitch'];
    $stmt = $conn->prepare("DELETE FROM pitches WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $msg = "Đã xóa sân bóng thành công!";
    } else {
        $error = "Không thể xóa sân này do đang có đơn đặt hàng liên kết!";
    }
}

// 4. CẬP NHẬT TRẠNG THÁI PHIẾU ĐẶT LỊCH (UPDATE) - KIỂM TRA TRÙNG LỊCH NẾU CHUYỂN SANG "ĐÃ XÁC NHẬN"
if (isset($_POST['btn_update_booking'])) {
    $booking_id = (int)$_POST['booking_id'];
    $status = $_POST['status'];

    if ($status == 'Đã xác nhận') {
        // Lấy thông tin phiếu đặt cần duyệt
        $stmt_info = $conn->prepare("SELECT pitch_id, booking_date, start_time, end_time FROM bookings WHERE id = ?");
        $stmt_info->bind_param("i", $booking_id);
        $stmt_info->execute();
        $bk_info = $stmt_info->get_result()->fetch_assoc();
        
        if ($bk_info) {
            $p_id = $bk_info['pitch_id'];
            $b_date = $bk_info['booking_date'];
            $s_time = $bk_info['start_time'];
            $e_time = $bk_info['end_time'];

            // Kiểm tra xem đã có phiếu khác ĐÃ XÁC NHẬN trong khoảng giờ này chưa
            $check_stmt = $conn->prepare("
                SELECT id FROM bookings 
                WHERE pitch_id = ? 
                  AND booking_date = ? 
                  AND status = 'Đã xác nhận' 
                  AND id != ? 
                  AND start_time < ? 
                  AND end_time > ?
            ");
            // Sửa chuỗi kiểu dữ liệu đúng 5 tham số: "isiss"
            $check_stmt->bind_param("isiss", $p_id, $b_date, $booking_id, $e_time, $s_time);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();

            if ($check_result->num_rows > 0) {
                $error = "Không thể duyệt! Khung giờ này đã bị trùng với một đơn đã xác nhận trước đó.";
            } else {
                $update_stmt = $conn->prepare("UPDATE bookings SET status = ? WHERE id = ?");
                $update_stmt->bind_param("si", $status, $booking_id);
                $update_stmt->execute();
                $msg = "Cập nhật trạng thái phiếu đặt thành công!";
            }
        }
    } else {
        $update_stmt = $conn->prepare("UPDATE bookings SET status = ? WHERE id = ?");
        $update_stmt->bind_param("si", $status, $booking_id);
        $update_stmt->execute();
        $msg = "Cập nhật trạng thái phiếu đặt thành công!";
    }
}

// 5. XÓA PHIẾU ĐẶT LỊCH (DELETE)
if (isset($_GET['delete_booking'])) {
    $id = (int)$_GET['delete_booking'];
    $stmt = $conn->prepare("DELETE FROM bookings WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $msg = "Đã xóa phiếu đặt lịch thành công!";
    } else {
        $error = "Xóa phiếu đặt lịch thất bại!";
    }
}

// Truy vấn danh sách
$pitches = $conn->query("SELECT * FROM pitches ORDER BY id DESC");
$bookings = $conn->query("
    SELECT b.*, u.fullname, u.phone, p.name as pitch_name 
    FROM bookings b 
    JOIN users u ON b.user_id = u.id 
    JOIN pitches p ON b.pitch_id = p.id 
    ORDER BY b.id DESC
");
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản Trị Hệ Thống - 404 SPORTS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-light">

<nav class="navbar navbar-dark bg-dark sticky-top">
    <div class="container">
        <a class="navbar-brand fw-bold" href="admin.php">
            <i class="fa-solid fa-shield-halved text-warning me-2"></i> ADMIN PANEL - 404 SPORTS
        </a>
        <div>
            <a href="index.php" class="btn btn-outline-light btn-sm me-2"><i class="fa-solid fa-house"></i> Trang chủ</a>
            <a href="logout.php" class="btn btn-danger btn-sm"><i class="fa-solid fa-right-from-bracket"></i> Đăng xuất</a>
        </div>
    </div>
</nav>

<div class="container my-4">
    <?php if($msg): ?>
        <div class="alert alert-success alert-dismissible fade show py-2">
            <i class="fa-solid fa-circle-check me-2"></i><?= $msg ?>
            <button type="button" class="btn-close py-2" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if($error): ?>
        <div class="alert alert-danger alert-dismissible fade show py-2">
            <i class="fa-solid fa-triangle-exclamation me-2"></i><?= $error ?>
            <button type="button" class="btn-close py-2" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <ul class="nav nav-tabs mb-4" id="adminTab" role="tablist">
        <li class="nav-item">
            <button class="nav-link active fw-bold" data-bs-toggle="tab" data-bs-target="#tab-bookings">
                <i class="fa-solid fa-calendar-check me-1"></i> Quản Lý Đặt Lịch
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link fw-bold" data-bs-toggle="tab" data-bs-target="#tab-pitches">
                <i class="fa-solid fa-futbol me-1"></i> Quản Lý Sân Bóng (Full CRUD)
            </button>
        </li>
    </ul>

    <div class="tab-content" id="adminTabContent">
        <!-- 1. TAB QUẢN LÝ ĐẶT LỊCH (UPDATE & DELETE + KIỂM TRA TRÙNG LỊCH) -->
        <div class="tab-pane fade show active" id="tab-bookings">
            <h4 class="fw-bold text-primary mb-3">Danh sách phiếu đặt sân bóng</h4>
            <div class="table-responsive bg-white rounded shadow-sm p-3">
                <table class="table table-hover align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Khách hàng</th>
                            <th>SĐT</th>
                            <th>Tên Sân</th>
                            <th>Ngày đá</th>
                            <th>Khung giờ</th>
                            <th>Trạng thái</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($bookings->num_rows > 0): ?>
                            <?php while($b = $bookings->fetch_assoc()): ?>
                                <tr>
                                    <td>#<?= $b['id'] ?></td>
                                    <td><strong><?= htmlspecialchars($b['fullname']) ?></strong></td>
                                    <td><?= htmlspecialchars($b['phone']) ?></td>
                                    <td><?= htmlspecialchars($b['pitch_name']) ?></td>
                                    <td><?= date('d/m/Y', strtotime($b['booking_date'])) ?></td>
                                    <td><?= $b['start_time'] ?> - <?= $b['end_time'] ?></td>
                                    <td>
                                        <span class="badge bg-<?= $b['status']=='Đã xác nhận'?'success':($b['status']=='Chờ xác nhận'?'warning':'danger') ?>">
                                            <?= $b['status'] ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <!-- Form Cập nhật Trạng thái -->
                                            <form method="POST" class="d-flex me-1">
                                                <input type="hidden" name="booking_id" value="<?= $b['id'] ?>">
                                                <select name="status" class="form-select form-select-sm me-1" style="width:120px;">
                                                    <option value="Chờ xác nhận" <?= $b['status']=='Chờ xác nhận'?'selected':'' ?>>Chờ XN</option>
                                                    <option value="Đã xác nhận" <?= $b['status']=='Đã xác nhận'?'selected':'' ?>>Xác nhận</option>
                                                    <option value="Đã hủy" <?= $b['status']=='Đã hủy'?'selected':'' ?>>Hủy</option>
                                                </select>
                                                <button type="submit" name="btn_update_booking" class="btn btn-sm btn-primary" title="Lưu trạng thái"><i class="fa-solid fa-floppy-disk"></i></button>
                                            </form>

                                            <!-- Xóa phiếu đặt lịch -->
                                            <a href="admin.php?delete_booking=<?= $b['id'] ?>" 
                                               class="btn btn-sm btn-outline-danger" 
                                               onclick="return confirm('Bạn có chắc chắn muốn XÓA phiếu đặt lịch này?')" 
                                               title="Xóa phiếu đặt">
                                                <i class="fa-solid fa-trash-can"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="8" class="text-center text-muted">Chưa có phiếu đặt lịch nào.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- 2. TAB QUẢN LÝ SÂN BÓNG (FULL CRUD) -->
        <div class="tab-pane fade" id="tab-pitches">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="fw-bold text-primary mb-0">Danh sách sân bóng</h4>
                <button class="btn btn-success fw-bold" data-bs-toggle="modal" data-bs-target="#addPitchModal">
                    <i class="fa-solid fa-plus me-1"></i> Thêm sân mới
                </button>
            </div>
            
            <div class="table-responsive bg-white rounded shadow-sm p-3">
                <table class="table table-hover align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Tên Sân</th>
                            <th>Loại Sân</th>
                            <th>Giá Thuê / Giờ</th>
                            <th>Trạng Thái</th>
                            <th>Hành Động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($p = $pitches->fetch_assoc()): ?>
                            <tr>
                                <td><?= $p['id'] ?></td>
                                <td><strong><?= htmlspecialchars($p['name']) ?></strong></td>
                                <td><span class="badge bg-info text-dark"><?= $p['type'] ?></span></td>
                                <td class="text-danger fw-bold"><?= number_format($p['price_per_hour'], 0, ',', '.') ?> VNĐ</td>
                                <td>
                                    <span class="badge bg-<?= $p['status']=='Trống'?'success':'secondary' ?>"><?= $p['status'] ?></span>
                                </td>
                                <td>
                                    <!-- Nút Sửa Sân -->
                                    <button class="btn btn-sm btn-warning text-dark me-1" data-bs-toggle="modal" data-bs-target="#editPitchModal<?= $p['id'] ?>">
                                        <i class="fa-solid fa-pen-to-square"></i> Sửa
                                    </button>

                                    <!-- Nút Xóa Sân -->
                                    <a href="admin.php?delete_pitch=<?= $p['id'] ?>" 
                                       class="btn btn-sm btn-danger" 
                                       onclick="return confirm('Bạn có chắc chắn muốn XÓA sân bóng này?')">
                                        <i class="fa-solid fa-trash"></i> Xóa
                                    </a>
                                </td>
                            </tr>

                            <!-- Modal Chỉnh Sửa Sân Bóng (UPDATE) -->
                            <div class="modal fade" id="editPitchModal<?= $p['id'] ?>" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <form method="POST">
                                            <div class="modal-header bg-warning">
                                                <h5 class="modal-title fw-bold">Chỉnh sửa sân: <?= htmlspecialchars($p['name']) ?></h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <input type="hidden" name="pitch_id" value="<?= $p['id'] ?>">
                                                <div class="mb-3">
                                                    <label class="form-label fw-bold">Tên sân bóng</label>
                                                    <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($p['name']) ?>" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label fw-bold">Loại sân</label>
                                                    <select name="type" class="form-select" required>
                                                        <option value="Sân 5" <?= $p['type']=='Sân 5'?'selected':'' ?>>Sân 5</option>
                                                        <option value="Sân 7" <?= $p['type']=='Sân 7'?'selected':'' ?>>Sân 7</option>
                                                        <option value="Sân 11" <?= $p['type']=='Sân 11'?'selected':'' ?>>Sân 11</option>
                                                    </select>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label fw-bold">Giá thuê (VNĐ/giờ)</label>
                                                    <input type="number" name="price" class="form-control" value="<?= $p['price_per_hour'] ?>" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label fw-bold">Trạng thái sân</label>
                                                    <select name="status" class="form-select">
                                                        <option value="Trống" <?= $p['status']=='Trống'?'selected':'' ?>>Trống (Hoạt động)</option>
                                                        <option value="Đang bảo trì" <?= $p['status']=='Đang bảo trì'?'selected':'' ?>>Đang bảo trì</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                                                <button type="submit" name="btn_edit_pitch" class="btn btn-warning fw-bold">Cập nhật thông tin</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Thêm Sân Bóng Mới (CREATE) -->
<div class="modal fade" id="addPitchModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title fw-bold">Thêm sân bóng mới</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Tên sân bóng</label>
                        <input type="text" name="name" class="form-control" placeholder="Ví dụ: Sân 5 - Số 11" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Loại sân</label>
                        <select name="type" class="form-select" required>
                            <option value="Sân 5">Sân 5</option>
                            <option value="Sân 7">Sân 7</option>
                            <option value="Sân 11">Sân 11</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Giá thuê (VNĐ/giờ)</label>
                        <input type="number" name="price" class="form-control" placeholder="200000" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" name="btn_add" class="btn btn-success fw-bold">Thêm mới</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>