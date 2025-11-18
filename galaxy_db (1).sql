-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Nov 18, 2025 at 03:45 PM
-- Server version: 8.0.35
-- PHP Version: 8.3.21

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `galaxy_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `aktivitas`
--

CREATE TABLE `aktivitas` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `tanggal` date NOT NULL,
  `jam_mulai` time DEFAULT NULL,
  `jam_selesai` time DEFAULT NULL,
  `aktivitas` text NOT NULL,
  `durasi_jam` float NOT NULL,
  `level` enum('A','B','C','D') NOT NULL,
  `bonus` int NOT NULL,
  `foto_bukti` text,
  `waktu_input` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `aktivitas`
--

INSERT INTO `aktivitas` (`id`, `user_id`, `tanggal`, `jam_mulai`, `jam_selesai`, `aktivitas`, `durasi_jam`, `level`, `bonus`, `foto_bukti`, `waktu_input`) VALUES
(11, 5, '2025-10-31', NULL, NULL, '09.00 - 10.00\r\nMengerjakan pergantian router client', 1, 'B', 25000, '6904c531b2075_1761920305.jpg', '2025-10-31 14:18:25'),
(12, 5, '2025-11-01', NULL, NULL, '08.00 - 12.30 \r\nMengkrimping Konektor LAN RJ-45, memasang cctv, dan dan merapikan kabel.', 4.5, 'D', 80000, '6905a2d9d7e6e_1761977049.jpg', '2025-11-01 06:04:09'),
(13, 5, '2025-11-03', NULL, NULL, '15.00 - 19.00 (pemasangan wifi client baru, pemasangan cctv 1 sebelah pos)', 4, 'D', 80000, '6908d3fa64fa4_1762186234.jpg', '2025-11-03 16:10:34'),
(14, 5, '2025-11-04', NULL, NULL, '12.00 - 16.30\r\n(melakukan finishing cctv area wilayah RT 7 RW 3 Bongsari)', 4.5, 'D', 80000, '690a1ba0ed778_1762270112.jpg', '2025-11-04 15:28:32'),
(15, 7, '2025-11-04', '08:05:00', '11:04:00', 'test aja sih sebetulnya', 2.98, 'C', 30000, '690a2d7d4b4da_1762274685_0.png,690a2d7d4b533_1762274685_1.png,690a2d7d4b565_1762274685_2.png', '2025-11-04 16:44:45'),
(16, 7, '2025-11-04', '20:00:00', '21:04:00', 'ok ok ok ok', 1.07, 'D', 35000, '690a2da327f56_1762274723_0.png', '2025-11-04 16:45:23'),
(17, 7, '2025-11-04', '23:00:00', '08:00:00', 'buat nyobain aja', 9, 'D', 0, '690a2e0b20188_1762274827_0.png', '2025-11-04 16:47:07'),
(18, 7, '2025-11-03', '09:00:00', '09:05:00', 'Meeting', 0.08, 'A', 0, '690a2e2e4f711_1762274862_0.png', '2025-11-04 16:47:42'),
(19, 7, '2025-11-03', '11:00:00', '14:00:00', 'es teh', 3, 'C', 30000, '690a2e5cd7ed3_1762274908_0.png', '2025-11-04 16:48:28'),
(20, 5, '2025-11-05', '10:30:00', '19:00:00', 'Jam 10.30 - 12.00 \r\n( persiapan alat dan pemasangan konektor LAN RJ-45 sepanjang ± 9 meter, Pemindahan cctv yang berawal dari tiang di pindah ke tembok rumah pak hardi, berhenti sejenak karena hujan deras).\r\nJam 16.00 - 19.00\r\n(Melanjutkan kegiatan, pemasangan kotak, merapikan kabel yang didalam pos, dan merapikan area monitor, NVR,dan adaptor)\r\npemasangan cctv dan pemindahan cctv selesai, kondisi pos RT 7 RW 3 sudah dikembalikan semula.', 8.5, 'D', 65000, '690b7ec69a56d_1762361030_0.jpg,690b7ec69a5ea_1762361030_1.jpg,690b7ec69a613_1762361030_2.jpg,690b7ec69a636_1762361030_3.jpeg,690b7ec69a659_1762361030_4.jpg', '2025-11-05 16:43:50'),
(21, 5, '2025-11-07', '09:00:00', '11:00:00', 'Pengecekan client Starlink di Resita Mulia Grub di wilayah Graha Padma Semarang', 2, 'C', 30000, '690e44ab6940a_1762542763_0.jpeg,690e44ab694bb_1762542763_1.jpeg,690e44ab694e2_1762542763_2.jpeg,690e44ab69506_1762542763_3.jpeg', '2025-11-07 19:12:43'),
(22, 5, '2025-11-10', '10:00:00', '12:00:00', 'menarik kabel LAN ke rumah client', 2, 'C', 30000, '6911774fb33e8_1762752335_0.jpg', '2025-11-10 05:25:35'),
(23, 5, '2025-11-11', '13:00:00', '16:30:00', 'menarik kabel FO dari pos RT 3 ke tiang depan rumah pak Adi Susanto, dan pengecekan internet di rumah pak Suroto kuburan', 3.5, 'C', 30000, '691302f0e3975_1762853616_0.jpg,691302f0e39e3_1762853616_1.jpg', '2025-11-11 09:33:36'),
(24, 5, '2025-11-11', '18:00:00', '19:00:00', 'mengganti htb pak santo san stb, serta mengganti router pak hadi', 1, 'D', 35000, '6913497f4bda7_1762871679_0.jpg', '2025-11-11 14:34:39'),
(25, 5, '2025-11-12', '14:00:00', '17:00:00', 'pergantian dan penarikan kabel FO di rumahnya suroto rt', 3, 'C', 30000, '69145a616e5b6_1762941537_0.jpg', '2025-11-12 09:58:57'),
(26, 5, '2025-11-13', '10:00:00', '14:30:00', 'memasang wifi client baru di desa', 4.5, 'D', 65000, '691636fc9e51f_1763063548_0.jpg,691636fc9e590_1763063548_1.jpg,691636fc9e5db_1763063548_2.jpg', '2025-11-13 19:52:28'),
(27, 5, '2025-11-15', '09:00:00', '14:00:00', 'Pergantian tiang antena di rumah soni, pergantian router di rumah Ayuk, nganter barang di unnes', 5, 'D', 65000, '6918d51136b71_1763235089_0.jpg,6918d51136c0d_1763235089_1.jpg,6918d51136c35_1763235089_2.jpg', '2025-11-15 19:31:29'),
(28, 5, '2025-11-16', '12:30:00', '17:00:00', 'pergantian router rumah abi, menelusuri router V.5, mensetting ulang antena di rumah pak sar, soni, dan revano', 4.5, 'D', 65000, '691a0f2da14a1_1763315501_0.jpg,691a0f2da1531_1763315501_1.jpg,691a0f2da1558_1763315501_2.jpg', '2025-11-16 17:51:41'),
(29, 5, '2025-11-17', '09:00:00', '15:00:00', 'survey tempat didaerah Anjasmoro', 6, 'D', 65000, '691bc683990a7_1763427971_0.jpg,691bc6839926b_1763427971_1.jpg,691bc683992ab_1763427971_2.jpg', '2025-11-18 01:06:11');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `nama` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','karyawan') NOT NULL DEFAULT 'karyawan',
  `level_karyawan` varchar(10) DEFAULT '3',
  `status` enum('aktif','nonaktif') NOT NULL DEFAULT 'aktif',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `nama`, `username`, `password`, `role`, `level_karyawan`, `status`, `created_at`) VALUES
(1, 'Administrator', 'admin', '$2y$10$Xbgl1HJkHti9wrYoQSWR0u58H9Pe2Bf.YNoL7F4sfvswlY0wKrJwK', 'admin', '3', 'aktif', '2025-10-26 15:58:48'),
(5, 'Fendi Putra Pradana', 'fendi', '$2y$10$TxVeoLXVIiR3Fpzm1w.b3OdIveduOczCVH0AWq.mq/TyqlIRlvVOO', 'karyawan', '3', 'aktif', '2025-10-26 16:07:32'),
(7, 'Bayu Putra Pratama', 'baiiput', '$2y$10$9c2kZYoCgkvvNpJd54ovGuAqG9SuqW2qosLJxDQMfZvuMNLR13waK', 'karyawan', '3', 'aktif', '2025-11-04 16:42:30');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `aktivitas`
--
ALTER TABLE `aktivitas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_tanggal` (`tanggal`),
  ADD KEY `idx_user_tanggal` (`user_id`,`tanggal`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `aktivitas`
--
ALTER TABLE `aktivitas`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `aktivitas`
--
ALTER TABLE `aktivitas`
  ADD CONSTRAINT `aktivitas_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
