-- DROP TABLE agar tidak error jika dijalankan ulang
DROP TABLE IF EXISTS Peminjaman_Ruangan;
DROP TABLE IF EXISTS Pengajuan_Event;
DROP TABLE IF EXISTS Ruangan;
DROP TABLE IF EXISTS Lantai;
DROP TABLE IF EXISTS Gedung;
DROP TABLE IF EXISTS Mahasiswa;
DROP TABLE IF EXISTS Ditmawa;
DROP TABLE IF EXISTS Organisasi;
DROP TABLE IF EXISTS Unit;

-- CREATE TABLE

CREATE TABLE `unit` (
  `unit_id` int(11) NOT NULL AUTO_INCREMENT,
  `unit_nama` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`unit_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `organisasi` (
  `organisasi_id` int(11) NOT NULL AUTO_INCREMENT,
  `organisasi_nama` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`organisasi_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `mahasiswa` (
  `mahasiswa_id` int(11) NOT NULL AUTO_INCREMENT,
  `mahasiswa_nama` varchar(100) DEFAULT NULL,
  `mahasiswa_npm` varchar(20) DEFAULT NULL,
  `mahasiswa_email` varchar(100) DEFAULT NULL,
  `mahasiswa_password` varchar(100) DEFAULT NULL,
  `mahasiswa_jurusan` varchar(100) DEFAULT NULL,
  `unit_id` int(11) DEFAULT NULL,
  `organisasi_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`mahasiswa_id`),
  FOREIGN KEY (`unit_id`) REFERENCES `unit`(`unit_id`),
  FOREIGN KEY (`organisasi_id`) REFERENCES `organisasi`(`organisasi_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `ditmawa` (
  `ditmawa_id` int(11) NOT NULL AUTO_INCREMENT,
  `ditmawa_nama` varchar(100) DEFAULT NULL,
  `ditmawa_email` varchar(100) DEFAULT NULL,
  `ditmawa_statusPersetujuan` varchar(20) DEFAULT NULL,
  `ditmawa_password` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`ditmawa_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `gedung` (
  `gedung_id` int(11) NOT NULL AUTO_INCREMENT,
  `gedung_nama` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`gedung_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `lantai` (
  `lantai_id` int(11) NOT NULL AUTO_INCREMENT,
  `gedung_id` int(11) DEFAULT NULL,
  `lantai_nomor` varchar(10) DEFAULT NULL,
  PRIMARY KEY (`lantai_id`),
  FOREIGN KEY (`gedung_id`) REFERENCES `gedung`(`gedung_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `ruangan` (
  `ruangan_id` int(11) NOT NULL AUTO_INCREMENT,
  `ruangan_nama` varchar(100) DEFAULT NULL,
  `lantai_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`ruangan_id`),
  FOREIGN KEY (`lantai_id`) REFERENCES `lantai`(`lantai_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `pengajuan_event` (
  `pengajuan_id` int(11) NOT NULL AUTO_INCREMENT,
  `pengajuan_namaEvent` varchar(100) DEFAULT NULL,
  `mahasiswa_id` int(11) DEFAULT NULL,
  `unit_id` int(11) DEFAULT NULL,
  `organisasi_id` int(11) DEFAULT NULL,
  `pengajuan_TypeKegiatan` varchar(50) DEFAULT NULL,
  `peminjaman_id` int(11) DEFAULT NULL,
  `pengajuan_event_jam_mulai` time DEFAULT NULL,
  `pengajuan_event_jam_selesai` time DEFAULT NULL,
  `pengajuan_event_tanggal_mulai` date DEFAULT NULL,
  `pengajuan_event_tanggal_selesai` date DEFAULT NULL,
  `jadwal_event_rundown_file` varchar(100) DEFAULT NULL,
  `pengajuan_event_proposal_file` varchar(100) DEFAULT NULL,
  `pengajuan_status` varchar(20) DEFAULT NULL,
  `pengajuan_tanggalApprove` date DEFAULT NULL,
  `ditmawa_id` int(11) DEFAULT NULL,
  `pengajuan_tanggalEdit` date DEFAULT NULL,
  `pengajuan_edditorId` varchar(50) DEFAULT NULL,
  `pengajuan_komentarDitmawa` text DEFAULT NULL,
  `pengajuan_LPJ` text DEFAULT NULL,
  PRIMARY KEY (`pengajuan_id`),
  FOREIGN KEY (`mahasiswa_id`) REFERENCES `mahasiswa`(`mahasiswa_id`),
  FOREIGN KEY (`unit_id`) REFERENCES `unit`(`unit_id`),
  FOREIGN KEY (`organisasi_id`) REFERENCES `organisasi`(`organisasi_id`),
  FOREIGN KEY (`ditmawa_id`) REFERENCES `ditmawa`(`ditmawa_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `peminjaman_ruangan` (
  `peminjaman_id` int(11) NOT NULL,
  `pengajuan_id` int(11) DEFAULT NULL,
  `ruangan_id` int(11) NOT NULL,
  PRIMARY KEY (`peminjaman_id`, `ruangan_id`),
  FOREIGN KEY (`pengajuan_id`) REFERENCES `pengajuan_event`(`pengajuan_id`),
  FOREIGN KEY (`ruangan_id`) REFERENCES `ruangan`(`ruangan_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- DUMMY DATA

INSERT INTO `unit` (`unit_nama`) VALUES
('Fakultas Teknologi Industri'),
('Fakultas Hukum');

INSERT INTO `organisasi` (`organisasi_nama`) VALUES
('Himpunan Mahasiswa Informatika'),
('Himpunan Mahasiswa Hukum');

INSERT INTO `mahasiswa` (
    `mahasiswa_nama`, `mahasiswa_npm`, `mahasiswa_email`,
    `mahasiswa_password`, `mahasiswa_jurusan`, `unit_id`, `organisasi_id`
) VALUES
('Budi Santoso', '1971001', '1971001@student.unpar.ac.id',
'12345678', 'Informatika', 1, 1),

('Siti Aminah', '1971002', '1971002@student.unpar.ac.id',
'12345678', 'Hukum', 2, 2);

INSERT INTO `ditmawa` (
    `ditmawa_nama`, `ditmawa_email`, `ditmawa_statusPersetujuan`, `ditmawa_password`
) VALUES
('Maria Santoso', 'maria.santoso@unpar.ac.id', 'Disetujui',
'12345678');

INSERT INTO `gedung` (`gedung_nama`) VALUES
('Gedung 10'), ('Gedung 12');

INSERT INTO `lantai` (`gedung_id`, `lantai_nomor`) VALUES
(1, '1'), (1, '2'), (2, '1');

INSERT INTO `ruangan` (`ruangan_nama`, `lantai_id`) VALUES
('Ruang 101', 1), ('Ruang 102', 1), ('Ruang 201', 2);
