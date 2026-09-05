-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Máy chủ: 127.0.0.1
-- Thời gian đã tạo: Th9 04, 2026 lúc 04:11 PM
-- Phiên bản máy phục vụ: 10.4.32-MariaDB
-- Phiên bản PHP: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Cơ sở dữ liệu: `quanly_sanbong`
--

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `bookings`
--

CREATE TABLE `bookings` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `pitch_id` int(11) NOT NULL,
  `booking_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `status` enum('Chờ xác nhận','Đã xác nhận','Đã hủy') DEFAULT 'Chờ xác nhận',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `bookings`
--

INSERT INTO `bookings` (`id`, `user_id`, `pitch_id`, `booking_date`, `start_time`, `end_time`, `total_price`, `status`, `created_at`) VALUES
(2, 3, 2, '2026-09-05', '20:30:00', '22:00:00', 200000.00, 'Đã xác nhận', '2026-09-04 12:38:23');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `pitches`
--

CREATE TABLE `pitches` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `type` enum('Sân 5','Sân 7','Sân 11') NOT NULL,
  `price_per_hour` decimal(10,2) NOT NULL,
  `status` enum('Trống','Đang bảo trì') DEFAULT 'Trống',
  `image` varchar(255) DEFAULT 'sanbong.jpg'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `pitches`
--

INSERT INTO `pitches` (`id`, `name`, `type`, `price_per_hour`, `status`, `image`) VALUES
(1, 'Sân 5 - Số 01', 'Sân 5', 200000.00, 'Đang bảo trì', 'sanbong.jpg'),
(2, 'Sân 5 - Số 02', 'Sân 5', 200000.00, 'Trống', 'sanbong.jpg'),
(3, 'Sân 5 - Số 03', 'Sân 5', 200000.00, 'Trống', 'sanbong.jpg'),
(4, 'Sân 5 - Số 04', 'Sân 5', 200000.00, 'Trống', 'sanbong.jpg'),
(5, 'Sân 5 - Số 05', 'Sân 5', 200000.00, 'Trống', 'sanbong.jpg'),
(6, 'Sân 5 - Số 06', 'Sân 5', 200000.00, 'Trống', 'sanbong.jpg'),
(7, 'Sân 5 - Số 07', 'Sân 5', 200000.00, 'Trống', 'sanbong.jpg'),
(8, 'Sân 5 - Số 08', 'Sân 5', 200000.00, 'Đang bảo trì', 'sanbong.jpg'),
(9, 'Sân 5 - Số 09', 'Sân 5', 200000.00, 'Trống', 'sanbong.jpg'),
(10, 'Sân 5 - Số 10', 'Sân 5', 200000.00, 'Trống', 'sanbong.jpg'),
(11, 'Sân 7 - Số 01', 'Sân 7', 350000.00, 'Trống', 'sanbong.jpg'),
(12, 'Sân 7 - Số 02', 'Sân 7', 350000.00, 'Trống', 'sanbong.jpg'),
(13, 'Sân 7 - Số 03', 'Sân 7', 350000.00, 'Trống', 'sanbong.jpg'),
(14, 'Sân 7 - Số 04', 'Sân 7', 350000.00, 'Trống', 'sanbong.jpg'),
(15, 'Sân 7 - Số 05', 'Sân 7', 350000.00, 'Trống', 'sanbong.jpg'),
(16, 'Sân 11 - Số 01', 'Sân 11', 600000.00, 'Trống', 'sanbong.jpg'),
(17, 'Sân 11 - Số 02', 'Sân 11', 600000.00, 'Đang bảo trì', 'sanbong.jpg');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `system_info`
--

CREATE TABLE `system_info` (
  `id` int(11) NOT NULL,
  `brand_name` varchar(100) NOT NULL,
  `address` text NOT NULL,
  `owner_phone` varchar(20) NOT NULL,
  `owner_name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `system_info`
--

INSERT INTO `system_info` (`id`, `brand_name`, `address`, `owner_phone`, `owner_name`) VALUES
(1, '404 SPORTS', '300A Nguyễn Tất Thành, Phường 13, Quận 4, TP. Hồ Chí Minh', '0909123456', 'Mr. Hoàng Duy');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `fullname` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `role` enum('admin','user') DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `users`
--

INSERT INTO `users` (`id`, `email`, `password`, `fullname`, `phone`, `role`, `created_at`) VALUES
(1, 'admin@gmail.com', 'admin123', 'Quản Trị Viên', '0909123456', 'admin', '2026-09-04 11:29:41'),
(2, 'duy@gmail.com', '123456', 'Hoàng Duy', '0987654321', 'user', '2026-09-04 11:29:41'),
(3, 'duyleh359@gmail.com', 'duyle2134', 'Lê Hoàng Duy', '0982798750', 'user', '2026-09-04 11:36:01'),
(4, 'Minhdepzai@gmail.com', 'binh123', 'Trần Bình Minh', '0706554004', 'user', '2026-09-04 12:45:42');

--
-- Chỉ mục cho các bảng đã đổ
--

--
-- Chỉ mục cho bảng `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `pitch_id` (`pitch_id`);

--
-- Chỉ mục cho bảng `pitches`
--
ALTER TABLE `pitches`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `system_info`
--
ALTER TABLE `system_info`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT cho các bảng đã đổ
--

--
-- AUTO_INCREMENT cho bảng `bookings`
--
ALTER TABLE `bookings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT cho bảng `pitches`
--
ALTER TABLE `pitches`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT cho bảng `system_info`
--
ALTER TABLE `system_info`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT cho bảng `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Các ràng buộc cho các bảng đã đổ
--

--
-- Các ràng buộc cho bảng `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `bookings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `bookings_ibfk_2` FOREIGN KEY (`pitch_id`) REFERENCES `pitches` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
