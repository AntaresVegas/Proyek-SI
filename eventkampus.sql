-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 21, 2025 at 04:27 AM
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
-- Table structure for table `asp`
--

CREATE TABLE `asp` (
  `asp_id` int(11) NOT NULL,
  `asp_nama` varchar(100) DEFAULT NULL,
  `asp_email` varchar(100) DEFAULT NULL,
  `asp_password` varchar(100) DEFAULT NULL,
  `asp_NIK` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `asp`
--

INSERT INTO `asp` (`asp_id`, `asp_nama`, `asp_email`, `asp_password`, `asp_NIK`) VALUES
(1, 'Bapak ASP', 'asp@gmail.com', '$2y$10$2Pm7fWvUmSlkpnfrjZ6PE.lkY7SENXMPfGYk7tSTRenWD6AO/VTgS', '3273012001990002');

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
  `ditmawa_NIK` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ditmawa`
--

INSERT INTO `ditmawa` (`ditmawa_id`, `ditmawa_nama`, `ditmawa_email`, `ditmawa_statusPersetujuan`, `ditmawa_password`, `ditmawa_NIK`) VALUES
(1, 'gugie', 'gugie@gmail.com', NULL, '12345678', '3273012001980001');

-- --------------------------------------------------------

--
-- Table structure for table `fasilitas_gedung`
--

CREATE TABLE `fasilitas_gedung` (
  `gedung_id` int(11) NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `foto_utama` varchar(255) DEFAULT NULL COMMENT 'Path ke foto utama gedung'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `fasilitas_gedung`
--

INSERT INTO `fasilitas_gedung` (`gedung_id`, `deskripsi`, `foto_utama`) VALUES
(1, 'Gedung 10 berada di Area Kampus UNPAR Ciumbuleuit, serta dapat diakses pula melalui Jalan Bukit Jarian. Selain memfasilitasi ruang perkuliahan mata kuliah umum, Gedung 10 juga dipergunakan oleh Fakultas Teknologi Rekayasa dan Fakultas Sains.', '../img/Gedung10.jpg'),
(2, 'Fakultas Ekonomi UNPAR menempati sebagian besar gedung 9 yang berada di area belakang Kampus UNPAR Ciumbuleuit. Gedung 9 diisi pula dengan ruang tata usaha, laboratorium serta beberapa ruang kuliah Fakultas Sains. Selain itu, Lantai 2 dan sebagian lantai 3 Gedung 9 dikhususkan sebagai Perpustakaan UNPAR.', '../img/Gedung9.jpg'),
(4, 'Gedung 2 merupakan gedung perkuliahan dan aktivitas akademik maupun non-akademik bagi Fakultas Hukum. Berbagai fasilitas seperti Ruang Peradilan Semu, Ruang Seminar, Ruang Sidang, serta B. Arief Sidharta Learning Center berada di gedung ini.', '../img/Gedung2.jpg'),
(5, 'Gedung 3 adalah gedung perkuliahan bagi Fakultas Ilmu Sosial dan Ilmu Politik. Fasilitas yang berada di gedung ini antara lain Ruang Veritas, Mgr. Geise Lecture Theatre, serta ruang perkuliahan dan seminar.', '../img/Gedung3.jpg'),
(6, 'Gedung 4 dan 5 adalah gedung terintegrasi yang menjadi pusat administrasi dan layanan mahasiswa.Fakultas Ilmu Sosial dan Ilmu Politik UNPAR berada di Gedung 3, bukan Gedung 4 dan 5. Gedung 5 digunakan untuk perkuliahan Fakultas Teknik UNPAR. ', '../img/Gedung45.jpg'),
(8, 'Kampus Merdeka 30 adalah lokasi bersejarah UNPAR yang kini menjadi pusat perkuliahan Fakultas Kedokteran dan Ilmu Hukum.Gedung ini berfungsi sebagai fasilitas untuk perkuliahan Fakultas Kedokteran dan Integrated Arts di UNPAR. Selain ruang kelas dan laboratorium, gedung ini juga dilengkapi dengan perpustakaan, pusat kegiatan, serta aula. Gedung ini terletak di Jalan Merdeka No.30.', '../img/Merdeka30.jpg'),
(9, 'Mekanika tanah di UNPAR dipelajari dalam Program Studi Teknik Sipil, khususnya melalui peminatan Geoteknik. Peminatan ini membahas karakteristik tanah sebagai material konstruksi untuk berbagai proyek sipil, termasuk menentukan daya dukung tanah, menganalisis kestabilan lereng, dan menghitung penurunan tanah.', '../img/GedungMekanika.jpg'),
(10, 'Gedung 0 atau Gedung Rektorat adalah pusat administrasi universitas, termasuk kantor Sekretariat Rektorat dan layanan akademik.', '../img/Gedung0.jpeg');

-- --------------------------------------------------------

--
-- Table structure for table `fasilitas_ruangan`
--

CREATE TABLE `fasilitas_ruangan` (
  `ruangan_id` int(11) NOT NULL,
  `kategori` varchar(100) DEFAULT 'Ruang Kelas' COMMENT 'Cth: Laboratorium, Ruang Kelas, Auditorium',
  `deskripsi` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `fasilitas_ruangan`
--

INSERT INTO `fasilitas_ruangan` (`ruangan_id`, `kategori`, `deskripsi`) VALUES
(1, 'Laboratorium Elektro', 'Laboratorium Teknik Elektro di Gedung 10, dilengkapi berbagai peralatan praktikum canggih untuk mahasiswa.'),
(2, 'Ruang Kelas', 'Ruang kelas standar di Gedung 10 dengan fasilitas AC, proyektor, dan papan tulis.');

-- --------------------------------------------------------

--
-- Table structure for table `fasilitas_ruangan_foto`
--

CREATE TABLE `fasilitas_ruangan_foto` (
  `foto_id` int(11) NOT NULL,
  `ruangan_id` int(11) NOT NULL,
  `path_foto` varchar(255) NOT NULL COMMENT 'Cth: img/fasilitas/ruangan_1_1.jpg',
  `urutan` int(3) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `fasilitas_ruangan_foto`
--

INSERT INTO `fasilitas_ruangan_foto` (`foto_id`, `ruangan_id`, `path_foto`, `urutan`) VALUES
(1, 1, '../img/LaboratoriumElektro.jpg', 1),
(2, 2, '../img/RuangKelasGed10.jpg', 1);

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
(4, 'Gedung 2'),
(5, 'Gedung 3'),
(6, 'Gedung 4 5'),
(8, 'Merdeka 30'),
(9, 'Parkiran Mekanika Tanah'),
(10, 'Gedung 0');

-- --------------------------------------------------------

--
-- Table structure for table `jadwal_kelas`
--

CREATE TABLE `jadwal_kelas` (
  `jadwal_id` int(11) NOT NULL,
  `ruangan_id` int(11) NOT NULL,
  `hari` enum('Senin','Selasa','Rabu','Kamis','Jumat','Sabtu') NOT NULL,
  `jam_mulai` time NOT NULL,
  `jam_selesai` time NOT NULL,
  `nama_matakuliah` varchar(150) DEFAULT NULL,
  `semester_tahun` varchar(50) DEFAULT NULL,
  `jurusan_fakultas` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `jadwal_kelas`
--

INSERT INTO `jadwal_kelas` (`jadwal_id`, `ruangan_id`, `hari`, `jam_mulai`, `jam_selesai`, `nama_matakuliah`, `semester_tahun`, `jurusan_fakultas`, `created_at`) VALUES
(1, 1, 'Senin', '07:30:00', '10:00:00', 'Pemrograman Berorientasi Objek', 'Ganjil 2025/2026', NULL, '2025-10-28 15:06:54'),
(2, 2, 'Senin', '07:30:00', '10:00:00', 'Struktur Data', 'Ganjil 2025/2026', NULL, '2025-10-28 15:06:54'),
(3, 3, 'Senin', '07:30:00', '10:00:00', 'Algoritma & Pemrograman', 'Ganjil 2025/2026', NULL, '2025-10-28 15:06:54'),
(4, 4, 'Senin', '07:30:00', '10:00:00', 'Matematika Diskrit', 'Ganjil 2025/2026', NULL, '2025-10-28 15:06:54'),
(5, 1, 'Senin', '10:00:00', '12:30:00', 'Sistem Digital', 'Ganjil 2025/2026', NULL, '2025-10-28 15:06:54'),
(6, 2, 'Senin', '10:00:00', '12:30:00', 'Jaringan Komputer Dasar', 'Ganjil 2025/2026', NULL, '2025-10-28 15:06:54'),
(7, 3, 'Senin', '10:00:00', '12:30:00', 'Kalkulus II', 'Ganjil 2025/2026', NULL, '2025-10-28 15:06:54'),
(8, 4, 'Senin', '10:00:00', '12:30:00', 'Bahasa Inggris Teknik', 'Ganjil 2025/2026', NULL, '2025-10-28 15:06:54'),
(9, 1, 'Senin', '13:00:00', '15:30:00', 'Basis Data', 'Ganjil 2025/2026', NULL, '2025-10-28 15:06:54'),
(10, 2, 'Senin', '13:00:00', '15:30:00', 'Pengantar Sistem Informasi', 'Ganjil 2025/2026', NULL, '2025-10-28 15:06:54'),
(11, 3, 'Senin', '13:00:00', '15:30:00', 'Sistem Operasi', 'Ganjil 2025/2026', NULL, '2025-10-28 15:06:54'),
(12, 4, 'Senin', '13:00:00', '15:30:00', 'Kewarganegaraan', 'Ganjil 2025/2026', NULL, '2025-10-28 15:06:54'),
(13, 1, 'Senin', '15:30:00', '18:00:00', 'Pemrograman Web', 'Ganjil 2025/2026', NULL, '2025-10-28 15:06:54'),
(14, 2, 'Senin', '15:30:00', '18:00:00', 'Rekayasa Perangkat Lunak', 'Ganjil 2025/2026', NULL, '2025-10-28 15:06:54'),
(15, 3, 'Selasa', '07:30:00', '10:00:00', 'Struktur Data', 'Ganjil 2025/2026', NULL, '2025-10-28 15:06:54'),
(16, 5, 'Selasa', '07:30:00', '10:00:00', 'Matematika Diskrit', 'Ganjil 2025/2026', NULL, '2025-10-28 15:06:54'),
(17, 1, 'Selasa', '10:00:00', '12:30:00', 'Pemrograman Berorientasi Objek', 'Ganjil 2025/2026', NULL, '2025-10-28 15:06:54'),
(18, 2, 'Selasa', '10:00:00', '12:30:00', 'Kalkulus II', 'Ganjil 2025/2026', NULL, '2025-10-28 15:06:54'),
(19, 3, 'Selasa', '10:00:00', '12:30:00', 'Algoritma & Pemrograman', 'Ganjil 2025/2026', NULL, '2025-10-28 15:06:54'),
(20, 5, 'Selasa', '10:00:00', '12:30:00', 'Bahasa Inggris Teknik', 'Ganjil 2025/2026', NULL, '2025-10-28 15:06:54'),
(21, 1, 'Selasa', '13:00:00', '15:30:00', 'Jaringan Komputer Dasar', 'Ganjil 2025/2026', NULL, '2025-10-28 15:06:54'),
(22, 2, 'Selasa', '13:00:00', '15:30:00', 'Basis Data', 'Ganjil 2025/2026', NULL, '2025-10-28 15:06:54'),
(23, 3, 'Selasa', '13:00:00', '15:30:00', 'Sistem Operasi', 'Ganjil 2025/2026', NULL, '2025-10-28 15:06:54'),
(24, 5, 'Selasa', '13:00:00', '15:30:00', 'Kewarganegaraan', 'Ganjil 2025/2026', NULL, '2025-10-28 15:06:54'),
(25, 1, 'Selasa', '15:30:00', '18:00:00', 'Pengantar Sistem Informasi', 'Ganjil 2025/2026', NULL, '2025-10-28 15:06:54'),
(26, 2, 'Selasa', '15:30:00', '18:00:00', 'Pemrograman Web', 'Ganjil 2025/2026', NULL, '2025-10-28 15:06:54'),
(27, 1, 'Rabu', '07:30:00', '10:00:00', 'Pemrograman Berorientasi Objek', 'Ganjil 2025/2026', NULL, '2025-10-28 15:06:54'),
(28, 2, 'Rabu', '07:30:00', '10:00:00', 'Struktur Data', 'Ganjil 2025/2026', NULL, '2025-10-28 15:06:54'),
(29, 3, 'Rabu', '07:30:00', '10:00:00', 'Algoritma & Pemrograman', 'Ganjil 2025/2026', NULL, '2025-10-28 15:06:54'),
(30, 4, 'Rabu', '07:30:00', '10:00:00', 'Matematika Diskrit', 'Ganjil 2025/2026', NULL, '2025-10-28 15:06:54'),
(31, 1, 'Rabu', '10:00:00', '12:30:00', 'Sistem Digital', 'Ganjil 2025/2026', NULL, '2025-10-28 15:06:54'),
(32, 2, 'Rabu', '10:00:00', '12:30:00', 'Jaringan Komputer Dasar', 'Ganjil 2025/2026', NULL, '2025-10-28 15:06:54'),
(33, 3, 'Rabu', '10:00:00', '12:30:00', 'Kalkulus II', 'Ganjil 2025/2026', NULL, '2025-10-28 15:06:54'),
(34, 4, 'Rabu', '10:00:00', '12:30:00', 'Bahasa Inggris Teknik', 'Ganjil 2025/2026', NULL, '2025-10-28 15:06:54'),
(35, 1, 'Rabu', '13:00:00', '15:30:00', 'Basis Data', 'Ganjil 2025/2026', NULL, '2025-10-28 15:06:54'),
(36, 2, 'Rabu', '13:00:00', '15:30:00', 'Pengantar Sistem Informasi', 'Ganjil 2025/2026', NULL, '2025-10-28 15:06:54'),
(37, 3, 'Rabu', '13:00:00', '15:30:00', 'Sistem Operasi', 'Ganjil 2025/2026', NULL, '2025-10-28 15:06:54'),
(38, 4, 'Rabu', '13:00:00', '15:30:00', 'Kewarganegaraan', 'Ganjil 2025/2026', NULL, '2025-10-28 15:06:54'),
(39, 1, 'Rabu', '15:30:00', '18:00:00', 'Pemrograman Web', 'Ganjil 2025/2026', NULL, '2025-10-28 15:06:54'),
(40, 2, 'Rabu', '15:30:00', '18:00:00', 'Rekayasa Perangkat Lunak', 'Ganjil 2025/2026', NULL, '2025-10-28 15:06:54'),
(41, 3, 'Kamis', '07:30:00', '10:00:00', 'Struktur Data', 'Ganjil 2025/2026', NULL, '2025-10-28 15:06:54'),
(42, 5, 'Kamis', '07:30:00', '10:00:00', 'Matematika Diskrit', 'Ganjil 2025/2026', NULL, '2025-10-28 15:06:54'),
(43, 1, 'Kamis', '10:00:00', '12:30:00', 'Pemrograman Berorientasi Objek', 'Ganjil 2025/2026', NULL, '2025-10-28 15:06:54'),
(44, 2, 'Kamis', '10:00:00', '12:30:00', 'Kalkulus II', 'Ganjil 2025/2026', NULL, '2025-10-28 15:06:54'),
(45, 3, 'Kamis', '10:00:00', '12:30:00', 'Algoritma & Pemrograman', 'Ganjil 2025/2026', NULL, '2025-10-28 15:06:54'),
(46, 5, 'Kamis', '10:00:00', '12:30:00', 'Bahasa Inggris Teknik', 'Ganjil 2025/2026', NULL, '2025-10-28 15:06:54'),
(47, 1, 'Kamis', '13:00:00', '15:30:00', 'Jaringan Komputer Dasar', 'Ganjil 2025/2026', NULL, '2025-10-28 15:06:54'),
(48, 2, 'Kamis', '13:00:00', '15:30:00', 'Basis Data', 'Ganjil 2025/2026', NULL, '2025-10-28 15:06:54'),
(49, 3, 'Kamis', '13:00:00', '15:30:00', 'Sistem Operasi', 'Ganjil 2025/2026', NULL, '2025-10-28 15:06:54'),
(50, 5, 'Kamis', '13:00:00', '15:30:00', 'Kewarganegaraan', 'Ganjil 2025/2026', NULL, '2025-10-28 15:06:54'),
(51, 1, 'Kamis', '15:30:00', '18:00:00', 'Pengantar Sistem Informasi', 'Ganjil 2025/2026', NULL, '2025-10-28 15:06:54'),
(52, 2, 'Kamis', '15:30:00', '18:00:00', 'Pemrograman Web', 'Ganjil 2025/2026', NULL, '2025-10-28 15:06:54'),
(53, 1, 'Jumat', '07:30:00', '10:00:00', 'Sistem Digital', 'Ganjil 2025/2026', NULL, '2025-10-28 15:06:54'),
(54, 2, 'Jumat', '07:30:00', '10:00:00', 'Rekayasa Perangkat Lunak', 'Ganjil 2025/2026', NULL, '2025-10-28 15:06:54'),
(55, 3, 'Jumat', '07:30:00', '10:00:00', 'Statistik & Probabilitas', 'Ganjil 2025/2026', NULL, '2025-10-28 15:06:54'),
(56, 4, 'Jumat', '07:30:00', '10:00:00', 'Pancasila', 'Ganjil 2025/2026', NULL, '2025-10-28 15:06:54'),
(57, 1, 'Jumat', '13:00:00', '15:30:00', 'Teori Bahasa & Automata', 'Ganjil 2025/2026', NULL, '2025-10-28 15:06:54'),
(58, 2, 'Jumat', '13:00:00', '15:30:00', 'Interaksi Manusia & Komputer', 'Ganjil 2025/2026', NULL, '2025-10-28 15:06:54'),
(59, 3, 'Jumat', '13:00:00', '15:30:00', 'Kecerdasan Buatan', 'Ganjil 2025/2026', NULL, '2025-10-28 15:06:54'),
(60, 4, 'Jumat', '13:00:00', '15:30:00', 'Agama', 'Ganjil 2025/2026', NULL, '2025-10-28 15:06:54');

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
(6, 2, '3'),
(7, 4, '1'),
(8, 4, '2');

-- --------------------------------------------------------

--
-- Table structure for table `log_import_jadwal`
--

CREATE TABLE `log_import_jadwal` (
  `log_id` int(11) NOT NULL,
  `semester_tahun` varchar(50) DEFAULT NULL,
  `jurusan_fakultas` varchar(100) DEFAULT NULL,
  `nama_file` varchar(255) DEFAULT NULL,
  `diupload_oleh_id` int(11) DEFAULT NULL,
  `diupload_pada` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
(0, 'audric', '6182101039', '6182101039@student.unpar.ac.id', '$2y$10$V1F8H6dfilDVZzUF.37Yl.JuEA.k4Uvu3ucXsrkqzDfyzIiuwgw0m', 'informatika', NULL, NULL),
(1, 'Bram', '6182101043', '6182101043@student.unpar.ac.id', '$2y$10$IdYqZUc2yXFSUbb6U.pm7..sFsddWVu0C9pxvGueVmjepzUDBrenC', 'Informatika', NULL, NULL),
(4, 'Rafli', '6182101027', '6182101027@student.unpar.ac.id', '$2y$10$yNr97wpx5LeCdB2wURmELO4ezPqUNHYNhNbpPtpUxXgCNakGicj4.', 'Informatika', NULL, NULL),
(7, 'afifah', '6182001062', '6182001062@student.unpar.ac.id', '$2y$10$kpZgEphyNOnqk5//4Q9qpOGXfQ/6yha9AiHx.OmP9AGkByRLbRVbK', 'Informatika', NULL, NULL);

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
(3, 0, 'Mohon maaf, pengajuan event \'Lomba Catur Antar Jurusan\' Anda ditolak. Silakan cek detail.', 'mahasiswa/mahasiswa_detail_pengajuan.php?id=3', 1, '2025-06-12 18:24:59'),
(4, 0, 'Selamat! Pengajuan event \'Science Fest\' Anda telah disetujui.', 'mahasiswa/mahasiswa_detail_pengajuan.php?id=5', 1, '2025-09-11 04:34:42'),
(5, 0, 'Selamat! Pengajuan event \'ISEC\' Anda telah disetujui.', 'mahasiswa/mahasiswa_detail_pengajuan.php?id=6', 1, '2025-09-11 05:09:48'),
(6, 0, 'Selamat! Pengajuan event \'Thormatics\' Anda telah disetujui.', 'mahasiswa/mahasiswa_detail_pengajuan.php?id=7', 1, '2025-09-11 11:13:53'),
(7, 0, 'Mohon maaf, pengajuan event \'Thormatics\' Anda ditolak. Silakan cek detail.', 'mahasiswa/mahasiswa_detail_pengajuan.php?id=10', 1, '2025-09-18 03:32:39'),
(8, 0, 'Selamat! Pengajuan event \'Science Fest\' Anda telah disetujui.', 'mahasiswa/mahasiswa_detail_pengajuan.php?id=11', 1, '2025-09-18 03:55:36'),
(9, 0, 'Selamat! Pengajuan event \'Thormatics\' Anda telah disetujui.', 'mahasiswa/mahasiswa_detail_pengajuan.php?id=10', 1, '2025-09-18 03:55:53'),
(10, 0, 'Selamat! Pengajuan event \'ISECS\' Anda telah disetujui.', 'mahasiswa/mahasiswa_detail_pengajuan.php?id=9', 1, '2025-09-18 04:53:50'),
(11, 0, 'Mohon maaf, pengajuan event \'ISEC\' Anda ditolak. Silakan cek detail.', 'mahasiswa/mahasiswa_detail_pengajuan.php?id=8', 1, '2025-09-18 04:58:50'),
(12, 0, 'Mohon maaf, pengajuan event \'ISEC\' Anda ditolak. Silakan cek detail.', 'mahasiswa/mahasiswa_detail_pengajuan.php?id=8', 1, '2025-09-18 05:01:25'),
(13, 0, 'Mohon maaf, pengajuan event \'ISEC\' Anda ditolak. Silakan cek detail.', 'mahasiswa/mahasiswa_detail_pengajuan.php?id=8', 1, '2025-09-18 05:06:50'),
(14, 0, 'Selamat! Pengajuan event \'ISEC\' Anda telah disetujui.', 'mahasiswa/mahasiswa_detail_pengajuan.php?id=8', 1, '2025-09-18 11:50:39'),
(15, 0, 'Mohon maaf, pengajuan event \'Testing\' Anda ditolak. Silakan cek detail.', 'mahasiswa/mahasiswa_detail_pengajuan.php?id=12', 1, '2025-09-18 11:55:53'),
(16, 0, 'Mohon maaf, pengajuan event \'Testing\' Anda ditolak. Silakan cek detail.', 'mahasiswa/mahasiswa_detail_pengajuan.php?id=13', 1, '2025-09-18 11:59:39'),
(17, 0, 'Mohon maaf, pengajuan event \'Testing\' Anda ditolak. Silakan cek detail.', 'mahasiswa/mahasiswa_detail_pengajuan.php?id=13', 1, '2025-09-18 12:05:03'),
(18, 0, 'Mohon maaf, pengajuan event \'Testing\' Anda ditolak. Silakan cek detail.', 'mahasiswa/mahasiswa_detail_pengajuan.php?id=13', 1, '2025-09-18 12:09:38'),
(19, 0, 'Mohon maaf, pengajuan event \'Testing\' Anda ditolak. Silakan cek detail.', 'mahasiswa/mahasiswa_detail_pengajuan.php?id=13', 1, '2025-09-18 13:24:51'),
(20, 0, 'Mohon maaf, pengajuan event \'Testing\' Anda ditolak. Silakan cek detail.', 'mahasiswa/mahasiswa_detail_pengajuan.php?id=13', 1, '2025-09-18 13:32:53'),
(21, 0, 'Mohon maaf, pengajuan event \'Science Fest\' Anda ditolak. Silakan cek detail.', 'mahasiswa/mahasiswa_detail_pengajuan.php?id=15', 0, '2025-09-19 03:11:37'),
(22, 0, 'Mohon maaf, pengajuan event \'Talkshow Inspiratif Bersama Alumni\' Anda ditolak. Silakan cek detail.', 'mahasiswa/mahasiswa_detail_pengajuan.php?id=19', 0, '2025-10-28 01:48:20'),
(23, 1, 'Pengajuan Pembatalan Event #18 telah diajukan.', 'ditmawa/ditmawa_editForm.php?id=18', 0, '2025-11-21 03:12:11');

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
-- Table structure for table `password_reset_sekretariat`
--

CREATE TABLE `password_reset_sekretariat` (
  `reset_id` int(11) NOT NULL,
  `reset_email` varchar(100) NOT NULL,
  `reset_token` varchar(255) NOT NULL,
  `reset_expires` bigint(20) NOT NULL
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
(7, 7, 2),
(8, 9, 5),
(9, 9, 3),
(11, 11, 4),
(12, 11, 5),
(13, 10, 4),
(16, 8, 2),
(17, 12, 4),
(18, 12, 5),
(27, 13, 4),
(28, 13, 5),
(29, 14, 1),
(30, 14, 2),
(31, 14, 3),
(32, 15, 2),
(33, 16, 4),
(34, 22, 2),
(35, 23, 5),
(36, 24, 1),
(37, 24, 2),
(38, 25, 3),
(39, 26, 4),
(40, 27, 3),
(41, 28, 2),
(42, 29, 4);

-- --------------------------------------------------------

--
-- Table structure for table `pengajuan_event`
--

CREATE TABLE `pengajuan_event` (
  `pengajuan_id` int(11) NOT NULL,
  `pengajuan_namaEvent` varchar(150) DEFAULT NULL,
  `pengaju_tipe` enum('mahasiswa','ditmawa','asp') NOT NULL COMMENT 'Tipe pengguna yang mengajukan',
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
  `pengajuan_status_ditmawa` enum('Diajukan','Disetujui','Ditolak') DEFAULT 'Diajukan',
  `tanggal_approve_ditmawa` datetime DEFAULT NULL,
  `pengajuan_tanggalEdit` datetime DEFAULT NULL,
  `komentar_ditmawa` text DEFAULT NULL,
  `approver_ditmawa_id` int(11) DEFAULT NULL,
  `pengajuan_status_asp` enum('Diajukan','Disetujui','Ditolak') DEFAULT 'Diajukan',
  `pengajuan_tanggalApprove_asp` datetime DEFAULT NULL,
  `komentar_asp` text DEFAULT NULL,
  `approver_asp_id` int(11) DEFAULT NULL,
  `pengajuan_status_proposal` enum('Diajukan','Disetujui','Ditolak') DEFAULT 'Diajukan',
  `pengajuan_LPJ` longblob DEFAULT NULL,
  `pengajuan_statusLPJ` enum('Menunggu Persetujuan','Disetujui','Ditolak') NOT NULL DEFAULT 'Menunggu Persetujuan',
  `pengajuan_komentarLPJ` text DEFAULT NULL,
  `pengajuan_status_pembatalan` enum('Tidak Ada','Diajukan','Disetujui','Ditolak') NOT NULL DEFAULT 'Tidak Ada',
  `surat_pembatalan_file` varchar(255) DEFAULT NULL,
  `komentar_ditmawa_pembatalan` text DEFAULT NULL,
  `tanggal_pembatalan_disetujui` datetime DEFAULT NULL,
  `surat_izin_kegiatan_file` varchar(255) DEFAULT NULL,
  `tanggal_terbit_surat_izin` datetime DEFAULT NULL,
  `penerbit_surat_izin_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pengajuan_event`
--

INSERT INTO `pengajuan_event` (`pengajuan_id`, `pengajuan_namaEvent`, `pengaju_tipe`, `pengaju_id`, `pengajuan_TypeKegiatan`, `pengajuan_event_jam_mulai`, `pengajuan_event_jam_selesai`, `pengajuan_event_tanggal_mulai`, `pengajuan_event_tanggal_selesai`, `tanggal_persiapan`, `tanggal_beres`, `jadwal_event_rundown_file`, `pengajuan_event_proposal_file`, `pengajuan_status_ditmawa`, `tanggal_approve_ditmawa`, `pengajuan_tanggalEdit`, `komentar_ditmawa`, `approver_ditmawa_id`, `pengajuan_status_asp`, `pengajuan_tanggalApprove_asp`, `komentar_asp`, `approver_asp_id`, `pengajuan_status_proposal`, `pengajuan_LPJ`, `pengajuan_statusLPJ`, `pengajuan_komentarLPJ`, `pengajuan_status_pembatalan`, `surat_pembatalan_file`, `komentar_ditmawa_pembatalan`, `tanggal_pembatalan_disetujui`, `surat_izin_kegiatan_file`, `tanggal_terbit_surat_izin`, `penerbit_surat_izin_id`) VALUES
(1, 'Seminar AI Masa Depan', 'mahasiswa', 0, 'Seminar', '09:00:00', '12:00:00', '2025-08-20', '2025-08-20', NULL, NULL, NULL, NULL, 'Disetujui', '2025-10-04 00:32:43', '2025-06-12 10:06:24', NULL, NULL, 'Disetujui', '2025-10-04 00:32:43', NULL, NULL, 'Disetujui', NULL, '', NULL, 'Tidak Ada', NULL, NULL, NULL, NULL, NULL, NULL),
(2, 'Workshop Fotografi Dasar', 'mahasiswa', 0, 'Workshop', '13:00:00', '16:00:00', '2025-09-10', '2025-09-10', NULL, NULL, NULL, NULL, 'Disetujui', '2025-10-04 00:32:43', '2025-06-12 10:06:24', NULL, NULL, 'Disetujui', '2025-10-04 00:32:43', NULL, NULL, 'Disetujui', NULL, '', NULL, 'Tidak Ada', NULL, NULL, NULL, NULL, NULL, NULL),
(3, 'Lomba Catur Antar Jurusan', 'mahasiswa', 0, 'Lomba', '08:00:00', '17:00:00', '2025-07-01', '2025-07-02', NULL, NULL, NULL, NULL, 'Disetujui', '2025-10-04 00:32:43', '2025-06-12 10:06:24', NULL, NULL, 'Disetujui', '2025-10-04 00:32:43', NULL, NULL, 'Disetujui', NULL, '', NULL, 'Tidak Ada', NULL, NULL, NULL, NULL, NULL, NULL),
(4, 'ISEC', 'mahasiswa', 0, 'Seminar/Workshop', '16:25:00', '18:25:00', '2025-09-01', '2025-09-02', NULL, NULL, '', '', 'Disetujui', '2025-10-04 00:32:43', '2025-06-12 11:20:39', NULL, NULL, 'Disetujui', '2025-10-04 00:32:43', NULL, NULL, 'Disetujui', 0x75706c6f6164732f6c706a2f6c706a5f36383463313134343534336637322e38303332393736342e646f6378, 'Ditolak', 'ga sesuai', 'Tidak Ada', NULL, NULL, NULL, NULL, NULL, NULL),
(5, 'Science Fest', 'mahasiswa', 0, 'Lomba', '11:12:00', '16:12:00', '2025-09-18', '2025-09-20', NULL, NULL, 0x75706c6f6164732f72756e646f776e2f363863323463333535393735395f36313832313031303339202d204b6567696174616e20312e706466, 0x75706c6f6164732f70726f706f73616c2f363863323463333535396334615f5450532026204d495320363138323130313033392e706466, 'Disetujui', '2025-10-04 00:32:43', '2025-09-11 11:12:37', NULL, NULL, 'Disetujui', '2025-10-04 00:32:43', NULL, NULL, 'Disetujui', NULL, 'Menunggu Persetujuan', NULL, 'Tidak Ada', NULL, NULL, NULL, NULL, NULL, NULL),
(6, 'ISEC', 'mahasiswa', 0, 'Lomba', '12:10:00', '14:08:00', '2025-10-01', '2025-10-03', '2025-09-30', '2025-10-04', 0x75706c6f6164732f72756e646f776e2f363863323539376337363962665f36313832313031303339202d204b6567696174616e20312e706466, 0x75706c6f6164732f70726f706f73616c2f363863323539376337366637385f5450532026204d495320363138323130313033392e706466, 'Disetujui', '2025-10-04 00:32:43', '2025-09-11 12:09:16', NULL, NULL, 'Disetujui', '2025-10-04 00:32:43', NULL, NULL, 'Disetujui', NULL, 'Menunggu Persetujuan', NULL, 'Tidak Ada', NULL, NULL, NULL, NULL, NULL, NULL),
(7, 'Thormatics', 'mahasiswa', 0, 'Tutoring', '10:00:00', '13:00:00', '2025-12-10', '2025-12-12', '2025-12-08', '2025-12-14', 0x75706c6f6164732f72756e646f776e2f363863326165633038316266655f36313832313031303339202d204b6567696174616e20322e706466, 0x75706c6f6164732f70726f706f73616c2f363863326165633038323365645f5475676173204d696e676775206b652d312e706466, 'Disetujui', '2025-10-04 00:32:43', '2025-09-11 18:13:04', NULL, NULL, 'Disetujui', '2025-10-04 00:32:43', NULL, NULL, 'Disetujui', NULL, 'Menunggu Persetujuan', NULL, 'Tidak Ada', NULL, NULL, NULL, NULL, NULL, NULL),
(8, 'ISEC', 'mahasiswa', 0, 'Lomba', '10:23:00', '11:23:00', '2025-09-12', '2025-09-14', '2025-09-11', '2025-09-15', 0x75706c6f6164732f72756e646f776e2f363863333834393264373130665f524b54412e706466, 0x75706c6f6164732f70726f706f73616c2f363863333834393264383266655f5450532026204d495320363138323130313033392e706466, 'Disetujui', '2025-10-04 00:32:43', '2025-09-18 18:50:21', NULL, NULL, 'Disetujui', '2025-10-04 00:32:43', NULL, NULL, 'Disetujui', NULL, 'Menunggu Persetujuan', NULL, 'Tidak Ada', NULL, NULL, NULL, NULL, NULL, NULL),
(9, 'ISECS', 'mahasiswa', 0, 'Lomba', '10:46:00', '20:46:00', '2025-09-22', '2025-09-23', '2025-09-20', '2025-09-24', 0x75706c6f6164732f72756e646f776e2f363863383138653535353836635f524b54412e706466, 0x75706c6f6164732f70726f706f73616c2f363863383138653535356139645f5450532026204d495320363138323130313033392e706466, 'Disetujui', '2025-10-04 00:32:43', '2025-09-15 20:47:17', NULL, NULL, 'Disetujui', '2025-10-04 00:32:43', NULL, NULL, 'Disetujui', NULL, 'Menunggu Persetujuan', NULL, 'Tidak Ada', NULL, NULL, NULL, NULL, NULL, NULL),
(10, 'Thormatics', 'mahasiswa', 0, 'Seminar', '10:29:00', '14:29:00', '2025-09-18', '2025-09-18', '2025-09-17', '2025-09-19', 0x75706c6f6164732f72756e646f776e2f363863623763386430663139315f524b54415f417564726963417572656c6975734a61776972796164696e6174615f36313832313031303339202d2072657631363932352e706466, 0x75706c6f6164732f70726f706f73616c2f363863623763386430663339335f6761727564613436313338312e706466, 'Disetujui', '2025-10-04 00:32:43', '2025-09-18 10:55:28', NULL, NULL, 'Disetujui', '2025-10-04 00:32:43', NULL, NULL, 'Disetujui', NULL, 'Menunggu Persetujuan', NULL, 'Tidak Ada', NULL, NULL, NULL, NULL, NULL, NULL),
(11, 'Science Fest', 'mahasiswa', 0, 'Pameran', '15:34:00', '17:34:00', '2025-09-18', '2025-09-18', '2025-09-17', '2025-09-19', 0x75706c6f6164732f72756e646f776e2f363863623764656333346366655f42494c4c494e475f434f44455f313735383039303936392e706466, 0x75706c6f6164732f70726f706f73616c2f363863623764656333346636375f4441465441522048415247412050415243454c20323032352e706466, 'Disetujui', '2025-10-04 00:32:43', '2025-09-18 10:35:08', NULL, NULL, 'Disetujui', '2025-10-04 00:32:43', NULL, NULL, 'Disetujui', NULL, 'Menunggu Persetujuan', NULL, 'Tidak Ada', NULL, NULL, NULL, NULL, NULL, NULL),
(12, 'Testing', 'mahasiswa', 0, 'Lomba', '08:54:00', '18:54:00', '2025-10-20', '2025-10-21', '2025-10-19', '2025-10-22', 0x75706c6f6164732f72756e646f776e2f363863626633333137336235655f4441465441522048415247412050415243454c20323032352e706466, 0x75706c6f6164732f70726f706f73616c2f363863626633333137336662645f4441465441522048415247412050415243454c20323032352e706466, 'Disetujui', '2025-10-04 00:32:43', '2025-09-18 18:55:29', NULL, NULL, 'Disetujui', '2025-10-04 00:32:43', NULL, NULL, 'Disetujui', NULL, 'Menunggu Persetujuan', NULL, 'Tidak Ada', NULL, NULL, NULL, NULL, NULL, NULL),
(13, 'Testing', 'mahasiswa', 0, 'Lomba', '08:54:00', '18:54:00', '2025-10-20', '2025-10-21', '2025-10-19', '2025-10-22', 0x75706c6f6164732f72756e646f776e2f363863626634303831336161665f4441465441522048415247412050415243454c20323032352e706466, 0x75706c6f6164732f70726f706f73616c2f363863626634303831336430315f4441465441522048415247412050415243454c20323032352e706466, 'Disetujui', '2025-10-04 00:32:43', '2025-09-18 20:30:09', NULL, NULL, 'Disetujui', '2025-10-04 00:32:43', NULL, NULL, 'Disetujui', NULL, 'Menunggu Persetujuan', NULL, 'Tidak Ada', NULL, NULL, NULL, NULL, NULL, NULL),
(14, 'SIAP IF', 'mahasiswa', 0, 'SIAP', '13:55:00', '10:55:00', '2025-09-21', '2025-09-22', '2025-09-20', '2025-09-23', 0x75706c6f6164732f72756e646f776e2f363863636238343765663239355f42494c4c494e475f434f44455f313735383039303936392e706466, 0x75706c6f6164732f70726f706f73616c2f363863636238343765663466355f4441465441522048415247412050415243454c20323032352e706466, 'Ditolak', '2025-10-04 00:32:43', '2025-09-19 08:56:23', 'Belum memenuhi persyaratan', NULL, 'Ditolak', '2025-10-04 00:32:43', 'Belum memenuhi persyaratan', NULL, 'Ditolak', NULL, 'Menunggu Persetujuan', NULL, 'Tidak Ada', NULL, NULL, NULL, NULL, NULL, NULL),
(15, 'Science Fest', 'mahasiswa', 0, 'SIAP', '10:08:00', '11:08:00', '2025-09-19', '2025-09-20', '2025-09-18', '2025-09-21', 0x75706c6f6164732f72756e646f776e2f363863636339636234386630365f42494c4c494e475f434f44455f313735383039303936392e706466, 0x75706c6f6164732f70726f706f73616c2f363863636339636234393931655f4441465441522048415247412050415243454c20323032352e706466, 'Ditolak', '2025-10-04 00:32:43', '2025-09-19 10:11:07', 'KURANG LENGKAP', NULL, 'Ditolak', '2025-10-04 00:32:43', 'KURANG LENGKAP', NULL, 'Ditolak', NULL, 'Menunggu Persetujuan', NULL, 'Tidak Ada', NULL, NULL, NULL, NULL, NULL, NULL),
(16, 'Dies Natalis', 'ditmawa', 1, 'Institusional', '10:14:00', '16:14:00', '2025-09-25', '2025-09-25', '2025-09-24', '2025-09-26', NULL, NULL, 'Disetujui', '2025-10-04 00:32:43', '2025-09-19 10:14:38', NULL, NULL, 'Disetujui', '2025-10-04 00:32:43', NULL, NULL, 'Disetujui', NULL, 'Menunggu Persetujuan', NULL, 'Tidak Ada', NULL, NULL, NULL, NULL, NULL, NULL),
(17, 'Seminar Pagi Tentang Teknologi Blockchain', 'mahasiswa', 0, 'Seminar', '08:00:00', '10:00:00', '2025-10-15', '2025-10-15', NULL, NULL, NULL, NULL, 'Disetujui', NULL, '2025-10-04 00:36:35', NULL, NULL, 'Diajukan', NULL, NULL, NULL, 'Disetujui', NULL, 'Menunggu Persetujuan', NULL, 'Tidak Ada', NULL, NULL, NULL, NULL, NULL, NULL),
(18, 'Workshop Desain Grafis untuk Pemula', 'mahasiswa', 0, 'Workshop', '10:00:00', '12:00:00', '2025-10-15', '2025-10-15', NULL, NULL, NULL, NULL, 'Disetujui', NULL, '2025-11-21 10:12:11', NULL, NULL, 'Diajukan', NULL, NULL, NULL, 'Disetujui', NULL, 'Menunggu Persetujuan', NULL, 'Diajukan', 'uploads/pembatalan/691fd88ba8c44_Pembatalan Event - Workshop Desain Grafis untuk Pemula.docx', NULL, NULL, NULL, NULL, NULL),
(19, 'Talkshow Inspiratif Bersama Alumni', 'mahasiswa', 0, 'Talkshow', '13:00:00', '15:00:00', '2025-10-15', '2025-10-15', NULL, NULL, NULL, NULL, 'Ditolak', '2025-10-28 02:48:20', '2025-10-04 00:36:35', '', NULL, 'Disetujui', '2025-10-27 09:56:41', '', NULL, 'Disetujui', NULL, 'Menunggu Persetujuan', NULL, 'Tidak Ada', NULL, NULL, NULL, NULL, NULL, NULL),
(20, 'Lomba Cepat Tepat Cerdas Cermat', 'mahasiswa', 0, 'Lomba', '15:00:00', '17:00:00', '2025-10-15', '2025-10-15', NULL, NULL, NULL, NULL, 'Disetujui', NULL, '2025-10-04 00:36:35', NULL, NULL, 'Ditolak', '2025-10-28 02:34:34', '', NULL, 'Disetujui', NULL, 'Menunggu Persetujuan', NULL, 'Tidak Ada', NULL, NULL, NULL, NULL, NULL, NULL),
(21, 'Pentas Seni Malam Apresiasi Budaya', 'mahasiswa', 0, 'Pentas Seni', '19:00:00', '21:00:00', '2025-10-15', '2025-10-15', NULL, NULL, NULL, NULL, 'Disetujui', NULL, '2025-10-04 00:36:35', NULL, NULL, 'Ditolak', '2025-10-28 02:34:27', '', NULL, 'Disetujui', NULL, 'Menunggu Persetujuan', NULL, 'Tidak Ada', NULL, NULL, NULL, NULL, NULL, NULL),
(22, 'Science Fest', 'mahasiswa', 0, 'Workshop', '10:07:00', '12:07:00', '2025-10-29', '2025-10-30', NULL, NULL, 0x75706c6f6164732f72756e646f776e2f363930303137343931626565315f5050545f50656e67656c6f6c61616e204576656e74204b616d7075732e706466, 0x75706c6f6164732f70726f706f73616c2f363930303137343931633462335f446f6b54656b6e69735f50656e67656c6f6c61616e204576656e74204b616d7075732e706466, 'Diajukan', NULL, '2025-10-28 08:07:21', NULL, NULL, 'Diajukan', NULL, NULL, NULL, 'Diajukan', NULL, 'Menunggu Persetujuan', NULL, 'Tidak Ada', NULL, NULL, NULL, NULL, NULL, NULL),
(23, 'Natal Unpar', 'ditmawa', 1, 'Institusional', '10:27:00', '11:27:00', '2025-10-29', '2025-10-30', NULL, NULL, NULL, NULL, 'Disetujui', '2025-10-28 08:27:59', '2025-10-28 08:27:59', NULL, NULL, 'Diajukan', NULL, NULL, NULL, 'Diajukan', NULL, 'Menunggu Persetujuan', NULL, 'Tidak Ada', NULL, NULL, NULL, NULL, NULL, NULL),
(24, 'ISECS', 'mahasiswa', 0, 'Lomba Game', '09:05:00', '10:05:00', '2025-10-29', '2025-10-30', NULL, NULL, 0x75706c6f6164732f72756e646f776e2f363930303234656338316462315f5050545f50656e67656c6f6c61616e204576656e74204b616d7075732e706466, 0x75706c6f6164732f70726f706f73616c2f363930303234656338323266615f446f6b54656b6e69735f50656e67656c6f6c61616e204576656e74204b616d7075732e706466, 'Diajukan', NULL, '2025-10-28 09:05:32', NULL, NULL, 'Diajukan', NULL, NULL, NULL, 'Diajukan', NULL, 'Menunggu Persetujuan', NULL, 'Tidak Ada', NULL, NULL, NULL, NULL, NULL, NULL),
(25, 'Testing', 'mahasiswa', 0, 'Seminar', '09:06:00', '10:06:00', '2025-10-21', '2025-10-22', NULL, NULL, 0x75706c6f6164732f72756e646f776e2f363930303235323563313439315f5050545f50656e67656c6f6c61616e204576656e74204b616d7075732e706466, 0x75706c6f6164732f70726f706f73616c2f363930303235323563323031665f446f6b54656b6e69735f50656e67656c6f6c61616e204576656e74204b616d7075732e706466, 'Diajukan', NULL, '2025-10-28 09:06:29', NULL, NULL, 'Diajukan', NULL, NULL, NULL, 'Diajukan', NULL, 'Menunggu Persetujuan', NULL, 'Tidak Ada', NULL, NULL, NULL, NULL, NULL, NULL),
(26, 'Dies Natalis', 'ditmawa', 1, 'Institusional', '10:09:00', '11:09:00', '2025-10-29', '2025-10-30', NULL, NULL, NULL, NULL, 'Disetujui', '2025-10-28 09:09:41', '2025-10-28 09:09:41', NULL, NULL, 'Diajukan', NULL, NULL, NULL, 'Diajukan', NULL, 'Menunggu Persetujuan', NULL, 'Tidak Ada', NULL, NULL, NULL, NULL, NULL, NULL),
(27, 'SIAP IF', 'mahasiswa', 0, 'Pameran', '16:00:00', '14:00:00', '2025-11-05', '2025-11-07', NULL, NULL, 0x75706c6f6164732f72756e646f776e2f363930386564613838356636345f5050545f50656e67656c6f6c61616e204576656e74204b616d7075732e706466, 0x75706c6f6164732f70726f706f73616c2f363930386564613838363633375f446f6b54656b6e69735f50656e67656c6f6c61616e204576656e74204b616d7075732e706466, 'Diajukan', NULL, '2025-11-04 01:00:08', NULL, NULL, 'Diajukan', NULL, NULL, NULL, 'Diajukan', NULL, 'Menunggu Persetujuan', NULL, 'Tidak Ada', NULL, NULL, NULL, NULL, NULL, NULL),
(28, 'Testing', 'mahasiswa', 0, 'Workshop', '12:00:00', '14:00:00', '2025-11-08', '2025-11-09', NULL, NULL, 0x75706c6f6164732f72756e646f776e2f363930643633656331313635355f5f50656d6f64656c616e204d6174656d6174696b61202d20363138323130313033392e706466, 0x75706c6f6164732f70726f706f73616c2f363930643633656331313937615f5f50656d6f64656c616e204d6174656d6174696b61202d20363138323130313033392e706466, 'Diajukan', NULL, '2025-11-07 10:13:48', NULL, NULL, 'Diajukan', NULL, NULL, NULL, 'Diajukan', NULL, 'Menunggu Persetujuan', NULL, 'Tidak Ada', NULL, NULL, NULL, NULL, NULL, NULL),
(29, 'Dies Natalis', 'ditmawa', 1, 'Institusional', '12:00:00', '13:00:00', '2025-11-16', '2025-11-16', NULL, NULL, NULL, NULL, 'Disetujui', '2025-11-07 10:18:06', '2025-11-07 10:18:06', NULL, NULL, 'Diajukan', NULL, NULL, NULL, 'Diajukan', NULL, 'Menunggu Persetujuan', NULL, 'Tidak Ada', NULL, NULL, NULL, NULL, NULL, NULL);

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
-- Table structure for table `sekretariat_universitas`
--

CREATE TABLE `sekretariat_universitas` (
  `sekuniv_id` int(11) NOT NULL,
  `sekuniv_nama` varchar(100) DEFAULT NULL,
  `sekuniv_email` varchar(100) DEFAULT NULL,
  `sekuniv_password` varchar(255) DEFAULT NULL,
  `sekuniv_NIK` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sekretariat_universitas`
--

INSERT INTO `sekretariat_universitas` (`sekuniv_id`, `sekuniv_nama`, `sekuniv_email`, `sekuniv_password`, `sekuniv_NIK`) VALUES
(1, 'Akun Tester Sekretariat', 'tester.sekretariat@unpar.ac.id', 'testing123', '1234567890');

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
-- Indexes for table `asp`
--
ALTER TABLE `asp`
  ADD PRIMARY KEY (`asp_id`);

--
-- Indexes for table `ditmawa`
--
ALTER TABLE `ditmawa`
  ADD PRIMARY KEY (`ditmawa_id`);

--
-- Indexes for table `fasilitas_gedung`
--
ALTER TABLE `fasilitas_gedung`
  ADD PRIMARY KEY (`gedung_id`);

--
-- Indexes for table `fasilitas_ruangan`
--
ALTER TABLE `fasilitas_ruangan`
  ADD PRIMARY KEY (`ruangan_id`);

--
-- Indexes for table `fasilitas_ruangan_foto`
--
ALTER TABLE `fasilitas_ruangan_foto`
  ADD PRIMARY KEY (`foto_id`),
  ADD KEY `ruangan_id` (`ruangan_id`);

--
-- Indexes for table `gedung`
--
ALTER TABLE `gedung`
  ADD PRIMARY KEY (`gedung_id`);

--
-- Indexes for table `jadwal_kelas`
--
ALTER TABLE `jadwal_kelas`
  ADD PRIMARY KEY (`jadwal_id`),
  ADD KEY `ruangan_id` (`ruangan_id`);

--
-- Indexes for table `lantai`
--
ALTER TABLE `lantai`
  ADD PRIMARY KEY (`lantai_id`),
  ADD KEY `gedung_id` (`gedung_id`);

--
-- Indexes for table `log_import_jadwal`
--
ALTER TABLE `log_import_jadwal`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `diupload_oleh_id` (`diupload_oleh_id`);

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
-- Indexes for table `password_reset_sekretariat`
--
ALTER TABLE `password_reset_sekretariat`
  ADD PRIMARY KEY (`reset_id`),
  ADD KEY `reset_email` (`reset_email`);

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
  ADD PRIMARY KEY (`pengajuan_id`),
  ADD KEY `fk_approver_ditmawa` (`approver_ditmawa_id`),
  ADD KEY `fk_approver_asp` (`approver_asp_id`),
  ADD KEY `fk_penerbit_surat_izin` (`penerbit_surat_izin_id`);

--
-- Indexes for table `ruangan`
--
ALTER TABLE `ruangan`
  ADD PRIMARY KEY (`ruangan_id`);

--
-- Indexes for table `sekretariat_universitas`
--
ALTER TABLE `sekretariat_universitas`
  ADD PRIMARY KEY (`sekuniv_id`),
  ADD UNIQUE KEY `sekuniv_email` (`sekuniv_email`),
  ADD UNIQUE KEY `sekuniv_NIK` (`sekuniv_NIK`);

--
-- Indexes for table `unit`
--
ALTER TABLE `unit`
  ADD PRIMARY KEY (`unit_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `asp`
--
ALTER TABLE `asp`
  MODIFY `asp_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `ditmawa`
--
ALTER TABLE `ditmawa`
  MODIFY `ditmawa_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `fasilitas_ruangan_foto`
--
ALTER TABLE `fasilitas_ruangan_foto`
  MODIFY `foto_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `gedung`
--
ALTER TABLE `gedung`
  MODIFY `gedung_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `jadwal_kelas`
--
ALTER TABLE `jadwal_kelas`
  MODIFY `jadwal_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=61;

--
-- AUTO_INCREMENT for table `lantai`
--
ALTER TABLE `lantai`
  MODIFY `lantai_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `log_import_jadwal`
--
ALTER TABLE `log_import_jadwal`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mahasiswa`
--
ALTER TABLE `mahasiswa`
  MODIFY `mahasiswa_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `organisasi`
--
ALTER TABLE `organisasi`
  MODIFY `organisasi_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `password_reset_sekretariat`
--
ALTER TABLE `password_reset_sekretariat`
  MODIFY `reset_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `peminjaman_ruangan`
--
ALTER TABLE `peminjaman_ruangan`
  MODIFY `peminjaman_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT for table `pengajuan_event`
--
ALTER TABLE `pengajuan_event`
  MODIFY `pengajuan_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `ruangan`
--
ALTER TABLE `ruangan`
  MODIFY `ruangan_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `sekretariat_universitas`
--
ALTER TABLE `sekretariat_universitas`
  MODIFY `sekuniv_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `unit`
--
ALTER TABLE `unit`
  MODIFY `unit_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `fasilitas_gedung`
--
ALTER TABLE `fasilitas_gedung`
  ADD CONSTRAINT `fk_fasilitas_gedung` FOREIGN KEY (`gedung_id`) REFERENCES `gedung` (`gedung_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `fasilitas_ruangan`
--
ALTER TABLE `fasilitas_ruangan`
  ADD CONSTRAINT `fk_fasilitas_ruangan` FOREIGN KEY (`ruangan_id`) REFERENCES `ruangan` (`ruangan_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `fasilitas_ruangan_foto`
--
ALTER TABLE `fasilitas_ruangan_foto`
  ADD CONSTRAINT `fk_fasilitas_foto` FOREIGN KEY (`ruangan_id`) REFERENCES `fasilitas_ruangan` (`ruangan_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `jadwal_kelas`
--
ALTER TABLE `jadwal_kelas`
  ADD CONSTRAINT `jadwal_kelas_ibfk_1` FOREIGN KEY (`ruangan_id`) REFERENCES `ruangan` (`ruangan_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `lantai`
--
ALTER TABLE `lantai`
  ADD CONSTRAINT `lantai_ibfk_1` FOREIGN KEY (`gedung_id`) REFERENCES `gedung` (`gedung_id`);

--
-- Constraints for table `log_import_jadwal`
--
ALTER TABLE `log_import_jadwal`
  ADD CONSTRAINT `log_import_jadwal_ibfk_1` FOREIGN KEY (`diupload_oleh_id`) REFERENCES `ditmawa` (`ditmawa_id`) ON DELETE SET NULL ON UPDATE CASCADE;

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

--
-- Constraints for table `pengajuan_event`
--
ALTER TABLE `pengajuan_event`
  ADD CONSTRAINT `fk_approver_asp` FOREIGN KEY (`approver_asp_id`) REFERENCES `asp` (`asp_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_approver_ditmawa` FOREIGN KEY (`approver_ditmawa_id`) REFERENCES `ditmawa` (`ditmawa_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_penerbit_surat_izin` FOREIGN KEY (`penerbit_surat_izin_id`) REFERENCES `sekretariat_universitas` (`sekuniv_id`) ON DELETE SET NULL ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
