<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'mahasiswa') {
    header("Location: ../index.php");
    exit();
}

require_once('../config/db_connection.php');

$nama = $_SESSION['nama'] ?? 'Mahasiswa';
$gedung_id = isset($_GET['gedung_id']) ? (int)$_GET['gedung_id'] : 0;

if ($gedung_id === 0) {
    header("Location: mahasiswa_fasilitas.php");
    exit();
}

$gedung = null;
$rooms_by_floor = [];

try {
    // 1. Ambil detail gedung
    $stmt_gedung = $conn->prepare("
        SELECT g.gedung_id, g.gedung_nama, fg.deskripsi, fg.foto_utama 
        FROM gedung g
        LEFT JOIN fasilitas_gedung fg ON g.gedung_id = fg.gedung_id
        WHERE g.gedung_id = ?
    ");
    $stmt_gedung->bind_param("i", $gedung_id);
    $stmt_gedung->execute();
    $result_gedung = $stmt_gedung->get_result();
    $gedung = $result_gedung->fetch_assoc();
    $stmt_gedung->close();

    if (!$gedung) {
        header("Location: mahasiswa_fasilitas.php");
        exit();
    }

    // 2. Ambil semua ruangan, fasilitas, dan foto-fotonya
    $stmt_rooms = $conn->prepare("
        SELECT 
            l.lantai_nomor, 
            r.ruangan_id, r.ruangan_nama, 
            fr.kategori, fr.deskripsi,
            (SELECT GROUP_CONCAT(frf.path_foto SEPARATOR ',') 
             FROM fasilitas_ruangan_foto frf 
             WHERE frf.ruangan_id = r.ruangan_id 
             ORDER BY frf.urutan) AS foto_list
        FROM ruangan r
        JOIN lantai l ON r.lantai_id = l.lantai_id
        LEFT JOIN fasilitas_ruangan fr ON r.ruangan_id = fr.ruangan_id
        WHERE l.gedung_id = ?
        ORDER BY CAST(l.lantai_nomor AS UNSIGNED), fr.kategori, r.ruangan_nama
    ");
    $stmt_rooms->bind_param("i", $gedung_id);
    $stmt_rooms->execute();
    $result_rooms = $stmt_rooms->get_result();

    // 3. Kelompokkan ruangan berdasarkan lantai
    while ($row = $result_rooms->fetch_assoc()) {
        $rooms_by_floor[$row['lantai_nomor']][] = $row;
    }
    $stmt_rooms->close();

} catch (Exception $e) {
    error_log("Error fetching building details: " . $e->getMessage());
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Detail <?php echo htmlspecialchars($gedung['gedung_nama']); ?> - Fasilitas Unpar</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html { height: 100%; } /* [LANGKAH 1] Pastikan html 100% */
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            background: #f0f2f5; 
            min-height: 100vh; /* Pastikan body setidaknya setinggi layar */
            padding-top: 70px; 
            background-image: url('../img/backgroundUnpar.jpeg'); 
            background-size: cover; 
            background-position: center; 
            background-attachment: fixed;
            
            /* [LANGKAH 2] Jadikan body sebagai flex container */
            display: flex;
            flex-direction: column;
        }
        
        /* [LANGKAH 3] Wrapper baru untuk memaksa konten tumbuh */
        .main-content-wrapper {
            flex-grow: 1; /* Ini KUNCINYA: Paksa wrapper ini untuk tumbuh mengisi ruang kosong */
        }
        
        .navbar { display: flex; justify-content: space-between; align-items: center; background:rgb(2, 71, 25); width: 100%; padding: 10px 30px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1); position: fixed; top: 0; left: 0; z-index: 1000; }
        .navbar-left { display: flex; align-items: center; gap: 10px; }
        .navbar-logo { width: 50px; height: 50px; }
        .navbar-title { color:rgb(255, 255, 255); font-size: 14px; line-height: 1.2; }
        .navbar-menu { display: flex; list-style: none; gap: 25px; }
        .navbar-menu li a { text-decoration: none; color:rgb(253, 253, 253); font-weight: 500; }
        .navbar-menu li a.active, .navbar-menu li a:hover { color: #007bff; }
        .navbar-right { display: flex; align-items: center; gap: 15px; color:rgb(255, 255, 255); }
        .icon { font-size: 20px; cursor: pointer; }
        
        .detail-header {
            max-width: 1100px;
            margin: 20px auto;
            padding: 30px;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            display: flex;
            gap: 30px;
            align-items: center;
        }
        .header-image {
            width: 300px;
            height: 200px;
            border-radius: 8px;
            background-size: cover;
            background-position: center;
            flex-shrink: 0;
            border: 1px solid #eee;
        }
        .header-content h1 {
            font-size: 32px;
            color: #333;
            margin-bottom: 10px;
        }
        .header-content p {
            font-size: 16px;
            color: #666;
            line-height: 1.6;
        }
        .header-content .back-link {
            display: inline-block;
            margin-top: 15px;
            color: rgb(2, 71, 25);
            text-decoration: none;
            font-weight: 600;
        }
        .header-content .back-link:hover { text-decoration: underline; }

        .floor-section {
            max-width: 1100px;
            margin: 25px auto;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            overflow: hidden;
        }
        .floor-title {
            background-color: #f8f9fa;
            padding: 15px 25px;
            border-bottom: 1px solid #eee;
            font-size: 24px;
            color: #333;
        }
        .category-group {
            padding: 20px 25px;
            border-bottom: 1px dashed #ddd;
        }
        .category-group:last-child { border-bottom: none; }
        .category-title {
            font-size: 20px;
            color: rgb(2, 71, 25);
            font-weight: 600;
            margin-bottom: 15px;
        }
        .room-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
        }
        .room-card {
            border: 1px solid #eee;
            border-radius: 8px;
            padding: 15px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .room-card:hover {
            border-color: rgb(2, 71, 25);
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            transform: translateY(-3px);
        }
        .room-card .fas {
            font-size: 28px;
            color: rgb(2, 71, 25);
            margin-bottom: 10px;
        }
        .room-card span {
            font-size: 16px;
            font-weight: 500;
            color: #444;
        }

        /* CSS MODAL */
        @keyframes fadeInScale { from { opacity: 0; transform: scale(0.9); } to { opacity: 1; transform: scale(1); } }
        .modal {
            display: none;
            position: fixed;
            z-index: 2000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.6);
            overflow-y: auto;
            padding: 40px 0;
        }
        .modal-content {
            position: relative;
            background-color: #fff;
            margin: auto;
            padding: 0;
            border-radius: 10px;
            width: 90%;
            max-width: 700px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            animation: fadeInScale 0.3s ease-out;
            overflow: hidden;
        }
        .modal-header {
            padding: 20px 30px;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .modal-header h3 {
            margin: 0;
            font-size: 24px;
            color: #333;
        }
        .close-button {
            font-size: 32px;
            font-weight: bold;
            cursor: pointer;
            color: #aaa;
            transition: all 0.2s ease;
        }
        .close-button:hover { color: #e74c3c; transform: rotate(90deg); }
        .modal-body {
            padding: 30px;
        }
        .modal-gallery {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-bottom: 25px;
        }
        .modal-gallery img {
            width: 100%;
            height: 150px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid #eee;
            cursor: pointer;
            transition: transform 0.2s;
        }
        .modal-gallery img:hover { transform: scale(1.05); }
        .modal-description {
            font-size: 16px;
            line-height: 1.7;
            color: #555;
        }
        .modal-description p { margin: 0; }
        .modal-description .no-desc { font-style: italic; color: #888; }
        .modal-gallery .no-photo {
            width: 100%; height: 150px; border-radius: 8px; background: #f5f5f5;
            display: flex; align-items: center; justify-content: center;
            color: #aaa; font-style: italic;
        }

        /* CSS FOOTER */
        .page-footer { 
            background-color: rgb(2, 71, 25); 
            color: #e9ecef; 
            padding: 40px 0; 
            
            /* [LANGKAH 4] Ganti margin-top: 40px menjadi auto */
            margin-top: auto; 
        }
        .footer-container { max-width: 1400px; margin: 0 auto; padding: 0 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 30px; }
        .footer-left { display: flex; align-items: center; gap: 20px; }
        .footer-logo { width: 60px; height: 60px; }
        .footer-left h4 { font-size: 1.2em; font-weight: 500; line-height: 1.4; }
        .footer-right ul { list-style: none; padding: 0; margin: 0; }
        .footer-right li { margin-bottom: 10px; display: flex; align-items: center; gap: 10px; }
        .footer-right .social-icons { margin-top: 20px; display: flex; gap: 15px; }
        .footer-right .social-icons a { color: #e9ecef; font-size: 1.5em; transition: color 0.3s; }
        .footer-right .social-icons a:hover { color: #fff; }
    </style>
</head>
<body>

<nav class="navbar">
    <div class="navbar-left">
        <img src="../img/logo.png" alt="Logo UNPAR" class="navbar-logo">
        <div class="navbar-title"><span>Pengelolaan</span><br><strong>Event UNPAR</strong></div>
    </div>
    <ul class="navbar-menu">
        <li><a href="mahasiswa_dashboard.php">Home</a></li>
        <li><a href="mahasiswa_fasilitas.php" class="active">Fasilitas</a></li>
        <li><a href="mahasiswa_rules.php">Rules</a></li>
        <li><a href="mahasiswa_pengajuan.php">Form</a></li>
        <li><a href="mahasiswa_kalender_gabungan.php">Kalender Gabungan</a></li> 
        <li><a href="mahasiswa_event.php">Kalender Event</a></li>
        <li><a href="mahasiswa_laporan.php">Laporan</a></li>
        <li><a href="mahasiswa_history.php">History</a></li>
    </ul>
    <div class="navbar-right">
        <a href="mahasiswa_profile.php" style="text-decoration: none; color: inherit;"><span class="user-name"><?php echo htmlspecialchars($nama); ?></span><i class="fas fa-user-circle icon" style="margin-left: 10px;"></i></a>
        <a href="logout.php"><i class="fas fa-sign-out-alt icon"></i></a>
    </div>
</nav>

<div class="main-content-wrapper">

    <div class="detail-header">
        <?php 
            $foto_utama = !empty($gedung['foto_utama']) ? htmlspecialchars($gedung['foto_utama']) : '../img/placeholder_gedung.png'; 
        ?>
        <div class="header-image" style="background-image: url('<?php echo $foto_utama; ?>');"></div>
        <div class="header-content">
            <h1><?php echo htmlspecialchars($gedung['gedung_nama']); ?></h1>
            <p><?php echo htmlspecialchars(!empty($gedung['deskripsi']) ? strip_tags($gedung['deskripsi']) : 'Deskripsi untuk gedung ini belum tersedia.'); ?></p>
            <a href="mahasiswa_fasilitas.php" class="back-link"><i class="fas fa-arrow-left"></i> Kembali ke Daftar Gedung</a>
        </div>
    </div>

    <?php if (empty($rooms_by_floor)): ?>
        <div class="floor-section">
            <div class="floor-title">Informasi</div>
            <div style="padding: 25px; text-align: center; font-size: 18px; color: #777;">
                Data ruangan untuk gedung ini belum tersedia.
            </div>
        </div>
    <?php else: ?>
        <?php foreach ($rooms_by_floor as $lantai_nomor => $rooms): ?>
            <div class="floor-section">
                <div class="floor-title">Lantai <?php echo htmlspecialchars($lantai_nomor); ?></div>
                
                <?php
                // Kelompokkan lagi berdasarkan kategori
                $rooms_by_category = [];
                foreach ($rooms as $room) {
                    $kategori = !empty($room['kategori']) ? $room['kategori'] : 'Lainnya';
                    $rooms_by_category[$kategori][] = $room;
                }
                ?>

                <?php foreach ($rooms_by_category as $kategori => $room_list): ?>
                    <div class="category-group">
                        <h3 class="category-title"><?php echo htmlspecialchars($kategori); ?></h3>
                        <div class="room-grid">
                            <?php foreach ($room_list as $room): ?>
                                <div class="room-card" onclick="openRoomModal(<?php echo htmlspecialchars(json_encode($room)); ?>)">
                                    <?php 
                                        $icon = 'fa-door-open'; // default
                                        if (stripos($kategori, 'lab') !== false) $icon = 'fa-flask';
                                        if (stripos($kategori, 'kelas') !== false) $icon = 'fa-chalkboard-user';
                                        if (stripos($kategori, 'auditorium') !== false) $icon = 'fa-landmark';
                                    ?>
                                    <i class="fas <?php echo $icon; ?>"></i>
                                    <span><?php echo htmlspecialchars($room['ruangan_nama']); ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

</div> <div id="roomModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modalRoomName">Nama Ruangan</h3>
            <span class="close-button">&times;</span>
        </div>
        <div class="modal-body">
            <div class="modal-gallery" id="modalRoomGallery">
                </div>
            <div class="modal-description">
                <p id="modalRoomDescription">Deskripsi ruangan...</p>
            </div>
        </div>
    </div>
</div>

<footer class="page-footer">
    <div class="footer-container">
        <div class="footer-left">
            <img src="../img/logo.png" alt="Logo UNPAR" class="footer-logo">
            <div>
                <h4>UNIVERSITAS KATOLIK PARAHYANGAN</h4>
                <h3 style="font-weight: bold; margin-top: 5px;">DIREKTORAT KEMAHASISWAAN</h3>
            </div>
        </div>
        <div class="footer-right">
            <ul>
                <li><i class="fas fa-map-marker-alt"></i> Jln. Ciumbuleuit No. 94 Bandung 40141 Jawa Barat</li>
                <li><i class="fas fa-phone-alt"></i> (022) 203 2655 ext. 100140</li>
                <li><i class="fas fa-envelope"></i> kemahasiswaan@unpar.ac.id</li>
            </ul>
            <div class="social-icons">
                <a href="httpsm://www.facebook.com/unparofficial" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                <a href="https://www.instagram.com/unparofficial/" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                <a href="https://www.youtube.com/channel/UCeIZdD9ul6JGpkSNM0oxcBw/featured" aria-label="YouTube"><i class="fab fa-youtube"></i></a>
                <a href="https://www.tiktok.com/@unparofficial" aria-label="TikTok"><i class="fab fa-tiktok"></i></a>
            </div>
        </div>
    </div>
</footer>

<script>
// [SCRIPT ANDA SUDAH BENAR]
const modal = document.getElementById('roomModal');
const closeBtn = document.querySelector('.close-button');

function openRoomModal(roomData) {
    document.getElementById('modalRoomName').textContent = roomData.ruangan_nama;
    
    const descEl = document.getElementById('modalRoomDescription');
    if (roomData.deskripsi) {
        descEl.innerHTML = roomData.deskripsi.replace(/\n/g, '<br>'); // Ganti newline dengan <br>
    } else {
        descEl.innerHTML = '<span class="no-desc">Deskripsi untuk ruangan ini tidak tersedia.</span>';
    }

    const galleryEl = document.getElementById('modalRoomGallery');
    galleryEl.innerHTML = '';
    let hasPhoto = false;

    if (roomData.foto_list) {
        const photos = roomData.foto_list.split(','); 
        photos.forEach(photo_path => {
            if (photo_path.trim() !== '') {
                galleryEl.innerHTML += `<img src="${photo_path.trim()}" alt="Foto Ruangan">`;
                hasPhoto = true;
            }
        });
    }

    if (!hasPhoto) {
        galleryEl.innerHTML = '<div class="no-photo"><span>Tidak ada foto</span></div>';
    }

    modal.style.display = 'block';
}

closeBtn.onclick = function() {
    modal.style.display = 'none';
}

window.onclick = function(event) {
    if (event.target == modal) {
        modal.style.display = 'none';
    }
}
</script>

</body>
</html>