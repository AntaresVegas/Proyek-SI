-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 04, 2025 at 03:43 PM
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
(1, 'Gedung 10');

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
(3, 1, '3');

-- --------------------------------------------------------

--
-- Table structure for table `mahasiswa`
--

CREATE TABLE `mahasiswa` (
  `mahasiswa_id` int(11) NOT NULL,
  `mahasiswa_nama` varchar(100) DEFAULT NULL,
  `mahasiswa_npm` varchar(20) DEFAULT NULL,
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
(0, 'audric', '6182101039', '6182101039@student.unpar.ac.id', '12345678', 'informatika', NULL, NULL),
(1, 'Bram', '6182101043', '6182101043@student.unpar.ac.id', '$2y$10$IdYqZUc2yXFSUbb6U.pm7..sFsddWVu0C9pxvGueVmjepzUDBrenC', 'Informatika', NULL, NULL);

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

-- --------------------------------------------------------

--
-- Table structure for table `pengajuan_event`
--

CREATE TABLE `pengajuan_event` (
  `pengajuan_id` int(11) NOT NULL,
  `pengajuan_namaEvent` varchar(150) DEFAULT NULL,
  `mahasiswa_id` int(11) DEFAULT NULL,
  `pengajuan_TypeKegiatan` varchar(100) DEFAULT NULL,
  `pengajuan_event_jam_mulai` time DEFAULT NULL,
  `pengajuan_event_jam_selesai` time DEFAULT NULL,
  `pengajuan_event_tanggal_mulai` date DEFAULT NULL,
  `pengajuan_event_tanggal_selesai` date DEFAULT NULL,
  `jadwal_event_rundown_file` longblob DEFAULT NULL,
  `pengajuan_event_proposal_file` longblob DEFAULT NULL,
  `pengajuan_status` enum('Diajukan','Disetujui','Ditolak') DEFAULT 'Diajukan',
  `pengajuan_tanggalApprove` datetime DEFAULT NULL,
  `ditmawa_id` int(11) DEFAULT NULL,
  `pengajuan_tanggalEdit` datetime DEFAULT NULL,
  `pengajuan_komentarDitmawa` text DEFAULT NULL,
  `pengajuan_LPJ` longblob DEFAULT NULL,
  `pengajuan_statusLPJ` enum('Belum Dikirim','Diterima','Revisi') DEFAULT 'Belum Dikirim'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ruangan`
--

CREATE TABLE `ruangan` (
  `ruangan_id` int(11) NOT NULL,
  `ruangan_nama` varchar(150) DEFAULT NULL,
  `lantai_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
  ADD PRIMARY KEY (`pengajuan_id`),
  ADD KEY `fk_pengajuan_mahasiswa` (`mahasiswa_id`),
  ADD KEY `fk_pengajuan_ditmawa` (`ditmawa_id`);

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
  MODIFY `gedung_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `lantai`
--
ALTER TABLE `lantai`
  MODIFY `lantai_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `mahasiswa`
--
ALTER TABLE `mahasiswa`
  MODIFY `mahasiswa_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `organisasi`
--
ALTER TABLE `organisasi`
  MODIFY `organisasi_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `peminjaman_ruangan`
--
ALTER TABLE `peminjaman_ruangan`
  MODIFY `peminjaman_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pengajuan_event`
--
ALTER TABLE `pengajuan_event`
  MODIFY `pengajuan_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ruangan`
--
ALTER TABLE `ruangan`
  MODIFY `ruangan_id` int(11) NOT NULL AUTO_INCREMENT;

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
-- Constraints for table `peminjaman_ruangan`
--
ALTER TABLE `peminjaman_ruangan`
  ADD CONSTRAINT `peminjaman_ruangan_ibfk_1` FOREIGN KEY (`pengajuan_id`) REFERENCES `pengajuan_event` (`pengajuan_id`),
  ADD CONSTRAINT `peminjaman_ruangan_ibfk_2` FOREIGN KEY (`ruangan_id`) REFERENCES `ruangan` (`ruangan_id`);

--
-- Constraints for table `pengajuan_event`
--
ALTER TABLE `pengajuan_event`
  ADD CONSTRAINT `fk_pengajuan_ditmawa` FOREIGN KEY (`ditmawa_id`) REFERENCES `ditmawa` (`ditmawa_id`),
  ADD CONSTRAINT `fk_pengajuan_mahasiswa` FOREIGN KEY (`mahasiswa_id`) REFERENCES `mahasiswa` (`mahasiswa_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
