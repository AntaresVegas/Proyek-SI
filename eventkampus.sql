-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 12, 2025 at 05:15 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `eventkampus`
--

-- --------------------------------------------------------

--
-- Table structure for table `ditmawa`
--

CREATE TABLE `ditmawa` (
  `ditmawa_id` int(11) NOT NULL,
  `ditmawa_nama` varchar(100) DEFAULT NULL,
  `ditmawa_email` varchar(100) DEFAULT NULL,
  `ditmawa_statusPersetujuan` varchar(50) DEFAULT NULL,
  `ditmawa_password` varchar(100) DEFAULT NULL,
  `ditmawa_NIK` varchar(20) DEFAULT NULL,
  `ditmawa_Divisi` varchar(100) DEFAULT NULL,
  `ditmawa_Bagian` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ditmawa`
--

INSERT INTO `ditmawa` (`ditmawa_id`, `ditmawa_nama`, `ditmawa_email`, `ditmawa_statusPersetujuan`, `ditmawa_password`, `ditmawa_NIK`, `ditmawa_Divisi`, `ditmawa_Bagian`) VALUES
(1, 'gugie', 'gugie@gmail.com', NULL, '12345678', '3273012001980001', 'Kemahasiswaan', 'Layanan Beasiswa');

-- --------------------------------------------------------

--
-- Table structure for table `gedung`
--

CREATE TABLE `gedung` (
  `gedung_id` int(11) NOT NULL,
  `gedung_nama` varchar(150) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `gedung`
--

INSERT INTO `gedung` (`gedung_id`, `gedung_nama`) VALUES
(1, 'Gedung 10'),
(2, 'Gedung 9'),
(3, 'Gedung 0'),
(4, 'Gedung 2'),
(5, 'Gedung 3'),
(6, 'Gedung 4 5'),
(8, 'Merdeka 30');

-- --------------------------------------------------------

--
-- Table structure for table `lantai`
--

CREATE TABLE `lantai` (
  `lantai_id` int(11) NOT NULL,
  `gedung_id` int(11) DEFAULT NULL,
  `lantai_nomor` varchar(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lantai`
--

INSERT INTO `lantai` (`lantai_id`, `gedung_id`, `lantai_nomor`) VALUES
(1, 1, '1'),
(2, 1, '2'),
(3, 1, '3'),
(4, 2, '1'),
(5, 2, '2'),
(6, 2, '3');

-- --------------------------------------------------------

--
-- Table structure for table `mahasiswa`
--

CREATE TABLE `mahasiswa` (
  `mahasiswa_id` int(11) NOT NULL,
  `mahasiswa_nama` varchar(100) DEFAULT NULL,
  `mahasiswa_npm` varchar(10) DEFAULT NULL,
  `mahasiswa_email` varchar(100) DEFAULT NULL,
  `mahasiswa_password` varchar(100) DEFAULT NULL,
  `mahasiswa_jurusan` varchar(100) DEFAULT NULL,
  `unit_id` int(11) DEFAULT NULL,
  `organisasi_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `mahasiswa`
--

INSERT INTO `mahasiswa` (`mahasiswa_id`, `mahasiswa_nama`, `mahasiswa_npm`, `mahasiswa_email`, `mahasiswa_password`, `mahasiswa_jurusan`, `unit_id`, `organisasi_id`) VALUES
(0, 'audric', '6182101039', '6182101039@student.unpar.ac.id', '$2y$10$0ZZGKZvpWhH2901R8/idquZtb6FmLqt/SsGoyoDmqQNyqCtA5Uu16', 'informatika', NULL, NULL),
(1, 'Bram', '6182101043', '6182101043@student.unpar.ac.id', '$2y$10$IdYqZUc2yXFSUbb6U.pm7..sFsddWVu0C9pxvGueVmjepzUDBrenC', 'Informatika', NULL, NULL),
(2, 'Bram Mathew', '6182101043', 'asdsa@gmail.com', '$2y$10$tCjlz1y7ycAuSFTK3f14.efCerDYtSwKyo8fy4zIToNPav.MMuZ62', 'informatika', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `notification_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `message` varchar(255) NOT NULL,
  `link` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`notification_id`, `user_id`, `message`, `link`, `is_read`, `created_at`) VALUES
(2, 0, 'Selamat! Pengajuan event \'ISEC\' Anda telah disetujui.', 'mahasiswa/mahasiswa_detail_pengajuan.php?id=4', 1, '2025-06-12 04:22:17'),
(3, 0, 'Mohon maaf, pengajuan event \'Lomba Catur Antar Jurusan\' Anda ditolak. Silakan cek detail.', 'mahasiswa/mahasiswa_detail_pengajuan.php?id=3', 0, '2025-06-12 18:24:59'),
(4, 0, 'Selamat! Pengajuan event \'Science Fest\' Anda telah disetujui.', 'mahasiswa/mahasiswa_detail_pengajuan.php?id=5', 0, '2025-09-11 04:34:42'),
(5, 0, 'Selamat! Pengajuan event \'ISEC\' Anda telah disetujui.', 'mahasiswa/mahasiswa_detail_pengajuan.php?id=6', 0, '2025-09-11 05:09:48'),
(6, 0, 'Selamat! Pengajuan event \'Thormatics\' Anda telah disetujui.', 'mahasiswa/mahasiswa_detail_pengajuan.php?id=7', 0, '2025-09-11 11:13:53');

-- --------------------------------------------------------

--
-- Table structure for table `organisasi`
--

CREATE TABLE `organisasi` (
  `organisasi_id` int(11) NOT NULL,
  `organisasi_nama` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `peminjaman_ruangan`
--

CREATE TABLE `peminjaman_ruangan` (
  `peminjaman_id` int(11) NOT NULL,
  `pengajuan_id` int(11) DEFAULT NULL,
  `ruangan_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `peminjaman_ruangan`
--

INSERT INTO `peminjaman_ruangan` (`peminjaman_id`, `pengajuan_id`, `ruangan_id`) VALUES
(1, 1, 1),
(2, 4, 3),
(3, 5, 1),
(4, 5, 4),
(5, 6, 1),
(6, 6, 4),
(7, 7, 2);

-- --------------------------------------------------------

--
-- Table structure for table `pengajuan_event`
--

CREATE TABLE `pengajuan_event` (
  `pengajuan_id` int(11) NOT NULL,
  `pengajuan_namaEvent` varchar(150) DEFAULT NULL,
  `pengaju_tipe` enum('mahasiswa','ditmawa') NOT NULL COMMENT 'Tipe pengguna yang mengajukan',
  `pengaju_id` int(11) NOT NULL COMMENT 'ID dari mahasiswa atau ditmawa',
  `pengajuan_TypeKegiatan` varchar(100) DEFAULT NULL,
  `pengajuan_event_jam_mulai` time DEFAULT NULL,
  `pengajuan_event_jam_selesai` time DEFAULT NULL,
  `pengajuan_event_tanggal_mulai` date DEFAULT NULL,
  `pengajuan_event_tanggal_selesai` date DEFAULT NULL,
  `tanggal_persiapan` date DEFAULT NULL,
  `tanggal_beres` date DEFAULT NULL,
  `jadwal_event_rundown_file` longblob DEFAULT NULL,
  `pengajuan_event_proposal_file` longblob DEFAULT NULL,
  `pengajuan_status` enum('Diajukan','Disetujui','Ditolak') DEFAULT 'Diajukan',
  `pengajuan_tanggalApprove` datetime DEFAULT NULL,
  `pengajuan_tanggalEdit` datetime DEFAULT NULL,
  `pengajuan_komentarDitmawa` text DEFAULT NULL,
  `pengajuan_LPJ` longblob DEFAULT NULL,
  `pengajuan_statusLPJ` enum('Menunggu Persetujuan','Disetujui','Ditolak') NOT NULL DEFAULT 'Menunggu Persetujuan',
  `pengajuan_komentarLPJ` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pengajuan_event`
--

INSERT INTO `pengajuan_event` (`pengajuan_id`, `pengajuan_namaEvent`, `pengaju_tipe`, `pengaju_id`, `pengajuan_TypeKegiatan`, `pengajuan_event_jam_mulai`, `pengajuan_event_jam_selesai`, `pengajuan_event_tanggal_mulai`, `pengajuan_event_tanggal_selesai`, `tanggal_persiapan`, `tanggal_beres`, `jadwal_event_rundown_file`, `pengajuan_event_proposal_file`, `pengajuan_status`, `pengajuan_tanggalApprove`, `pengajuan_tanggalEdit`, `pengajuan_komentarDitmawa`, `pengajuan_LPJ`, `pengajuan_statusLPJ`, `pengajuan_komentarLPJ`) VALUES
(1, 'Seminar AI Masa Depan', 'mahasiswa', 0, 'Seminar', '09:00:00', '12:00:00', '2025-08-20', '2025-08-20', NULL, NULL, NULL, NULL, 'Disetujui', NULL, '2025-06-12 10:06:24', NULL, NULL, '', NULL),
(2, 'Workshop Fotografi Dasar', 'mahasiswa', 0, 'Workshop', '13:00:00', '16:00:00', '2025-09-10', '2025-09-10', NULL, NULL, NULL, NULL, 'Diajukan', NULL, '2025-06-12 10:06:24', NULL, NULL, '', NULL),
(3, 'Lomba Catur Antar Jurusan', 'mahasiswa', 0, 'Lomba', '08:00:00', '17:00:00', '2025-07-01', '2025-07-02', NULL, NULL, NULL, NULL, 'Ditolak', '2025-06-12 20:24:59', '2025-06-12 10:06:24', 'tempatnya tolong diisi', NULL, '', NULL),
(4, 'ISEC', 'mahasiswa', 0, 'Seminar/Workshop', '16:25:00', '18:25:00', '2025-09-01', '2025-09-02', NULL, NULL, '', '', 'Disetujui', '2025-06-12 06:22:17', '2025-06-12 11:20:39', '', 0x75706c6f6164732f6c706a2f6c706a5f36383463313134343534336637322e38303332393736342e646f6378, 'Ditolak', 'kurang lengkap'),
(5, 'Science Fest', 'mahasiswa', 0, 'Lomba', '11:12:00', '16:12:00', '2025-09-18', '2025-09-20', NULL, NULL, 0x75706c6f6164732f72756e646f776e2f363863323463333535393735395f36313832313031303339202d204b6567696174616e20312e706466, 0x75706c6f6164732f70726f706f73616c2f363863323463333535396334615f5450532026204d495320363138323130313033392e706466, 'Disetujui', '2025-09-11 06:34:42', '2025-09-11 11:12:37', '', NULL, 'Menunggu Persetujuan', NULL),
(6, 'ISEC', 'mahasiswa', 0, 'Lomba', '12:10:00', '14:08:00', '2025-10-01', '2025-10-03', '2025-09-30', '2025-10-04', 0x75706c6f6164732f72756e646f776e2f363863323539376337363962665f36313832313031303339202d204b6567696174616e20312e706466, 0x75706c6f6164732f70726f706f73616c2f363863323539376337366637385f5450532026204d495320363138323130313033392e706466, 'Disetujui', '2025-09-11 07:09:48', '2025-09-11 12:09:16', '', NULL, 'Menunggu Persetujuan', NULL),
(7, 'Thormatics', 'mahasiswa', 0, 'Tutoring', '10:00:00', '13:00:00', '2025-12-10', '2025-12-12', '2025-12-08', '2025-12-14', 0x75706c6f6164732f72756e646f776e2f363863326165633038316266655f36313832313031303339202d204b6567696174616e20322e706466, 0x75706c6f6164732f70726f706f73616c2f363863326165633038323365645f5475676173204d696e676775206b652d312e706466, 'Disetujui', '2025-09-11 13:13:53', '2025-09-11 18:13:04', '', NULL, 'Menunggu Persetujuan', NULL),
(8, 'ISEC', 'mahasiswa', 0, 'Lomba', '10:23:00', '11:23:00', '2025-09-12', '2025-09-10', '2025-09-11', '2025-09-14', 0x75706c6f6164732f72756e646f776e2f363863333834393264373130665f524b54412e706466, 0x75706c6f6164732f70726f706f73616c2f363863333834393264383266655f5450532026204d495320363138323130313033392e706466, 'Diajukan', NULL, '2025-09-12 09:25:22', NULL, NULL, 'Menunggu Persetujuan', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `ruangan`
--

CREATE TABLE `ruangan` (
  `ruangan_id` int(11) NOT NULL,
  `ruangan_nama` varchar(150) DEFAULT NULL,
  `lantai_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ruangan`
--

INSERT INTO `ruangan` (`ruangan_id`, `ruangan_nama`, `lantai_id`) VALUES
(1, '10317', 3),
(2, '10318', 3),
(3, '10323', 3),
(4, '9017', 4),
(5, '9018', 4);

-- --------------------------------------------------------

--
-- Table structure for table `unit`
--

CREATE TABLE `unit` (
  `unit_id` int(11) NOT NULL,
  `unit_nama` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `ditmawa`
--
ALTER TABLE `ditmawa`
  ADD PRIMARY KEY (`ditmawa_id`);

--
-- Indexes for table `gedung`
--
ALTER TABLE `gedung`
  ADD PRIMARY KEY (`gedung_id`);

--
-- Indexes for table `lantai`
--
ALTER TABLE `lantai`
  ADD PRIMARY KEY (`lantai_id`),
  ADD KEY `gedung_id` (`gedung_id`);

--
-- Indexes for table `mahasiswa`
--
ALTER TABLE `mahasiswa`
  ADD PRIMARY KEY (`mahasiswa_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`notification_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `organisasi`
--
ALTER TABLE `organisasi`
  ADD PRIMARY KEY (`organisasi_id`);

--
-- Indexes for table `peminjaman_ruangan`
--
ALTER TABLE `peminjaman_ruangan`
  ADD PRIMARY KEY (`peminjaman_id`),
  ADD KEY `peminjaman_ruangan_ibfk_1` (`pengajuan_id`),
  ADD KEY `peminjaman_ruangan_ibfk_2` (`ruangan_id`);

--
-- Indexes for table `pengajuan_event`
--
ALTER TABLE `pengajuan_event`
  ADD PRIMARY KEY (`pengajuan_id`);

--
-- Indexes for table `ruangan`
--
ALTER TABLE `ruangan`
  ADD PRIMARY KEY (`ruangan_id`);

--
-- Indexes for table `unit`
--
ALTER TABLE `unit`
  ADD PRIMARY KEY (`unit_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `ditmawa`
--
ALTER TABLE `ditmawa`
  MODIFY `ditmawa_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `gedung`
--
ALTER TABLE `gedung`
  MODIFY `gedung_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `lantai`
--
ALTER TABLE `lantai`
  MODIFY `lantai_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `mahasiswa`
--
ALTER TABLE `mahasiswa`
  MODIFY `mahasiswa_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `organisasi`
--
ALTER TABLE `organisasi`
  MODIFY `organisasi_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `peminjaman_ruangan`
--
ALTER TABLE `peminjaman_ruangan`
  MODIFY `peminjaman_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `pengajuan_event`
--
ALTER TABLE `pengajuan_event`
  MODIFY `pengajuan_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `ruangan`
--
ALTER TABLE `ruangan`
  MODIFY `ruangan_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `unit`
--
ALTER TABLE `unit`
  MODIFY `unit_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `lantai`
--
ALTER TABLE `lantai`
  ADD CONSTRAINT `lantai_ibfk_1` FOREIGN KEY (`gedung_id`) REFERENCES `gedung` (`gedung_id`);

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `mahasiswa` (`mahasiswa_id`);

--
-- Constraints for table `peminjaman_ruangan`
--
ALTER TABLE `peminjaman_ruangan`
  ADD CONSTRAINT `peminjaman_ruangan_ibfk_1` FOREIGN KEY (`pengajuan_id`) REFERENCES `pengajuan_event` (`pengajuan_id`),
  ADD CONSTRAINT `peminjaman_ruangan_ibfk_2` FOREIGN KEY (`ruangan_id`) REFERENCES `ruangan` (`ruangan_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
