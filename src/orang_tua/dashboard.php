<?php
// WAJIB: Jalankan session_start() di paling atas
session_start(); 

require_once '../config/db.php';
date_default_timezone_set('Asia/Jakarta');

// PROTEKSI: Jika belum login atau bukan orang tua, tendang ke login
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'orang_tua') {
    header("Location: ../auth/login.php");
    exit();
}

$id_user_login = $_SESSION['id_user'];
$nama_ortu = $_SESSION['nama_lengkap'] ?? 'Orang Tua';
$success_msg = '';
$error_link = '';

// --- LOGIKA 1: PROSES MENAUTKAN ANAK ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['tautkan_anak'])) {
    $nis_input = $_POST['nis_murid'];

    try {
        $check = $pdo->prepare("SELECT id_siswa FROM siswa WHERE nis = ? AND (id_ortu IS NULL OR id_ortu = 0)");
        $check->execute([$nis_input]);
        $murid = $check->fetch();

        if ($murid) {
            $update = $pdo->prepare("UPDATE siswa SET id_ortu = ? WHERE nis = ?");
            $update->execute([$id_user_login, $nis_input]);
            $success_msg = "Berhasil! Akun Anda kini terhubung dengan Ananda.";
        } else {
            $error_link = "Maaf, NIS tidak ditemukan atau sudah terhubung dengan akun lain.";
        }
    } catch (PDOException $e) {
        $error_link = "Terjadi kesalahan sistem: " . $e->getMessage();
    }
}

// --- LOGIKA 2: AMBIL DATA ANAK YANG TERHUBUNG ---
try {
    $stmtChild = $pdo->prepare("SELECT s.*, k.nama_kelas 
                                FROM siswa s 
                                LEFT JOIN kelas k ON s.id_kelas = k.id_kelas 
                                WHERE s.id_ortu = ? LIMIT 1");
    $stmtChild->execute([$id_user_login]);
    $data_anak = $stmtChild->fetch();

    if ($data_anak) {
        $id_siswa = $data_anak['id_siswa'];
        $nama_anak = $data_anak['nama_siswa'];
        $nama_kelas = $data_anak['nama_kelas'] ?? 'Belum Ada Kelas';

        $bulan_ini_angka = (int)date('n');
        $tahun_ini = (int)date('Y');
        $nama_bulan_arr = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        $bulan_ini_nama = $nama_bulan_arr[$bulan_ini_angka];

        $stmtSpp = $pdo->prepare("SELECT status FROM spp_status WHERE nama = ? AND bulan = ? AND tahun = ? LIMIT 1");
        $stmtSpp->execute([$nama_anak, $bulan_ini_angka, $tahun_ini]);
        $spp = $stmtSpp->fetch();
        
        $status_spp = ($spp && $spp['status'] === 'LUNAS') ? 'Lunas (' . $bulan_ini_nama . ')' : 'Belum Bayar (' . $bulan_ini_nama . ')';

        $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM pengumuman WHERE target_audiens IN ('Semua', 'Orang Tua')");
        $stmtCount->execute();
        $jumlah_pengumuman = $stmtCount->fetchColumn();
        
        // Cek Absensi Hari Ini
        $tanggal_hari_ini = date('Y-m-d');
        $stmtAbsen = $pdo->prepare("SELECT status FROM absensi WHERE id_siswa = ? AND tanggal = ? LIMIT 1");
        $stmtAbsen->execute([$id_siswa, $tanggal_hari_ini]);
        $data_absen_hari_ini = $stmtAbsen->fetch();
        $status_absen_hari_ini = $data_absen_hari_ini ? $data_absen_hari_ini['status'] : 'Belum Diabsen';

    } else {
        $nama_anak = 'Belum terhubung';
        $status_spp = 'N/A';
        $jumlah_pengumuman = 0;
        $nama_kelas = 'Belum Ada Kelas';
        $status_absen_hari_ini = 'Belum Diabsen';
    }

    $bulan_sekarang = date('n');
    $tahun_sekarang = date('Y');
    $tahun_ajaran = ($bulan_sekarang >= 7) ? $tahun_sekarang . '-' . ($tahun_sekarang + 1) : ($tahun_sekarang - 1) . '-' . $tahun_sekarang;

    // --- LOGIKA 3: MENGGABUNGKAN PENGUMUMAN DAN UNDANGAN UNTUK CAROUSEL ---
    
    // 3A. Ambil Data Pengumuman
    $stmtPengumuman = $pdo->prepare("SELECT id_pengumuman as id, judul, isi as deskripsi, tanggal, gambar, 'Pengumuman' as tipe FROM pengumuman WHERE target_audiens IN ('Semua', 'Orang Tua') ORDER BY tanggal DESC LIMIT 5");
    $stmtPengumuman->execute();
    $data_pengumuman = $stmtPengumuman->fetchAll(PDO::FETCH_ASSOC);

    // 3B. Ambil Data Undangan & Status RSVP (REAL DB)
    $data_undangan = [];
    if($data_anak) { // Hanya tampil jika anak sudah ditautkan
        $stmtUndangan = $pdo->prepare("
            SELECT u.id_undangan as id, u.judul, u.deskripsi, u.tanggal, u.waktu, u.lokasi, r.status as status_rsvp, 'Undangan' as tipe 
            FROM undangan u
            LEFT JOIN undangan_rsvp r ON u.id_undangan = r.id_undangan AND r.id_ortu = ?
            ORDER BY u.tanggal DESC LIMIT 5
        ");
        $stmtUndangan->execute([$id_user_login]);
        $data_undangan = $stmtUndangan->fetchAll(PDO::FETCH_ASSOC);
    }

    // 3C. Gabungkan dan Urutkan Berdasarkan Tanggal Terbaru
    $semua_info = array_merge($data_pengumuman, $data_undangan);
    usort($semua_info, function($a, $b) {
        return strtotime($b['tanggal']) - strtotime($a['tanggal']);
    });
    // Ambil 5 teratas saja biar slider tidak terlalu panjang
    $semua_info = array_slice($semua_info, 0, 5);

} catch (PDOException $e) {
    die("Sistem Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Orang Tua - SIS TKIT Fathurrobbany</title>
    <!-- Pastikan path CSS bener mundur satu folder -->
    <link rel="stylesheet" href="dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        /* CSS Untuk Slider Otomatis */
        .announcement-slider {
            position: relative; 
            overflow: hidden; 
            border-radius: 16px; 
            margin-bottom: 30px; 
            box-shadow: 0 10px 25px -5px rgba(59, 130, 246, 0.4);
        }
        .slides-container {
            display: flex; 
            transition: transform 0.5s ease-in-out;
        }
        .slide {
            min-width: 100%; 
            color: white; 
            padding: 25px 30px; 
            display: flex; 
            align-items: center; 
            justify-content: space-between; 
            gap: 20px; 
            box-sizing: border-box; 
            flex-wrap: wrap;
            position: relative;
        }
        
        /* Warna Slide Berbeda Berdasarkan Tipe */
        .slide-pengumuman { background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%); }
        .slide-undangan { background: linear-gradient(135deg, #065f46 0%, #10b981 100%); }
        
        /* Badge Status RSVP di Slide Undangan */
        .badge-rsvp-belum { background: #fef08a; color: #854d0e; }
        .badge-rsvp-hadir { background: #dcfce7; color: #166534; }
        .badge-rsvp-absen { background: #fee2e2; color: #991b1b; }
    </style>
</head>
<body>

    <?php include 'sidebar.php'; ?>

    <main class="main-content">
        <header class="header" style="margin-bottom: 20px;">
            <h1>Dashboard Orang Tua</h1>
            <p>Selamat datang kembali, <strong><?= htmlspecialchars($nama_ortu); ?></strong></p>
        </header>

        <!-- BANNER PENGUMUMAN & UNDANGAN CAROUSEL -->
        <?php if (!empty($semua_info)): ?>
            <div class="announcement-slider">
                <div class="slides-container" id="slider">
                    <?php foreach ($semua_info as $info): ?>
                        <div class="slide <?= $info['tipe'] === 'Undangan' ? 'slide-undangan' : 'slide-pengumuman' ?>">
                            <div style="position: absolute; right: -20px; top: -30px; width: 150px; height: 150px; background: rgba(255,255,255,0.05); border-radius: 50%; pointer-events: none;"></div>
                            
                            <div style="flex: 1; min-width: 280px; z-index: 1; display: flex; gap: 20px; align-items: flex-start;">
                                <div style="background: rgba(255, 255, 255, 0.2); width: 60px; height: 60px; border-radius: 14px; display: flex; justify-content: center; align-items: center; font-size: 28px; flex-shrink: 0; backdrop-filter: blur(5px);">
                                    <?php if($info['tipe'] === 'Undangan'): ?>
                                        <i class="fas fa-envelope-open-text" style="transform: rotate(-10deg);"></i>
                                    <?php else: ?>
                                        <i class="fas fa-bullhorn" style="transform: rotate(-15deg);"></i>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <!-- BADGE BERDASARKAN TIPE -->
                                    <?php if($info['tipe'] === 'Undangan'): ?>
                                        <span style="background: white; color: #065f46; padding: 5px 12px; border-radius: 20px; font-size: 11px; font-weight: 800; text-transform: uppercase; margin-bottom: 10px; display: inline-block;">Undangan Acara</span>
                                        <span style="margin-left: 5px; font-size: 12px; font-weight: 600;"><i class="far fa-calendar-alt"></i> <?= date('d M Y', strtotime($info['tanggal'])) ?></span>
                                    <?php else: ?>
                                        <span style="background: #fbbf24; color: #92400e; padding: 5px 12px; border-radius: 20px; font-size: 11px; font-weight: 800; text-transform: uppercase; margin-bottom: 10px; display: inline-block;">Info Terbaru</span>
                                    <?php endif; ?>

                                    <h2 style="margin: 0 0 8px 0; font-size: 20px; font-weight: 700;"><?= htmlspecialchars($info['judul']); ?></h2>
                                    <p style="margin: 0; font-size: 14px; color: rgba(255,255,255,0.9); line-height: 1.6;">
                                        <?= htmlspecialchars(substr($info['deskripsi'], 0, 100)) . (strlen($info['deskripsi']) > 100 ? '...' : ''); ?>
                                    </p>
                                    
                                    <!-- TOMBOL AKSI BERDASARKAN TIPE -->
                                    <?php if($info['tipe'] === 'Undangan'): ?>
                                        <div style="margin-top: 15px; display: flex; align-items: center; gap: 10px;">
                                            <a href="undangan.php" style="display: inline-block; background: white; color: #065f46; padding: 8px 16px; border-radius: 8px; font-size: 13px; font-weight: 700; text-decoration: none; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">Cek Undangan</a>
                                            
                                            <?php 
                                            // Tampilan status RSVP di slide
                                            $status = $info['status_rsvp'] ?? 'Belum Konfirmasi';
                                            $badge_class = 'badge-rsvp-belum';
                                            $icon_rsvp = 'fa-clock';
                                            if($status == 'Hadir') { $badge_class = 'badge-rsvp-hadir'; $icon_rsvp = 'fa-check'; }
                                            elseif($status == 'Tidak Hadir') { $badge_class = 'badge-rsvp-absen'; $icon_rsvp = 'fa-times'; }
                                            ?>
                                            <span class="<?= $badge_class ?>" style="padding: 6px 12px; border-radius: 8px; font-size: 12px; font-weight: 700;"><i class="fas <?= $icon_rsvp ?>"></i> RSVP: <?= $status ?></span>
                                        </div>
                                    <?php else: ?>
                                        <a href="pengumuman.php" style="display: inline-block; margin-top: 15px; background: white; color: #1e40af; padding: 8px 16px; border-radius: 8px; font-size: 13px; font-weight: 700; text-decoration: none; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">Baca Selengkapnya</a>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Gambar hanya tampil untuk pengumuman -->
                            <?php if ($info['tipe'] === 'Pengumuman' && !empty($info['gambar'])): ?>
                                <div style="width: 220px; height: 140px; border-radius: 12px; overflow: hidden; flex-shrink: 0; box-shadow: 0 8px 20px rgba(0,0,0,0.25); z-index: 1; border: 3px solid rgba(255,255,255,0.2);">
                                    <img src="../assets/<?= htmlspecialchars($info['gambar']); ?>" alt="Gambar Pengumuman" style="width: 100%; height: 100%; object-fit: cover;">
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- NOTIFIKASI ABSENSI HARI INI -->
        <?php if ($data_anak && $status_absen_hari_ini !== 'Belum Diabsen'): ?>
            <?php 
                $absen_color = '#3b82f6'; 
                $absen_icon = 'fa-info-circle';
                $pesan_absen = "Ananda dinyatakan <strong>$status_absen_hari_ini</strong> hari ini.";

                if($status_absen_hari_ini == 'Hadir') { 
                    $absen_color = '#10b981'; $absen_icon = 'fa-check-circle'; 
                    $pesan_absen = "Alhamdulillah, Ananda sudah <strong>Hadir</strong> di sekolah hari ini.";
                } elseif($status_absen_hari_ini == 'Sakit' || $status_absen_hari_ini == 'Izin') { 
                    $absen_color = '#f59e0b'; $absen_icon = 'fa-envelope-open-text'; 
                    $pesan_absen = "Ananda tercatat <strong>$status_absen_hari_ini</strong>. Semoga segala urusan dilancarkan.";
                } elseif($status_absen_hari_ini == 'Alpa') { 
                    $absen_color = '#ef4444'; $absen_icon = 'fa-times-circle'; 
                    $pesan_absen = "Ananda tercatat <strong>Tanpa Keterangan (Alpa)</strong> hari ini.";
                }
            ?>
            <div style="background: <?= $absen_color ?>15; color: <?= $absen_color ?>; padding: 18px 25px; border-radius: 16px; margin-bottom: 25px; border: 1px solid <?= $absen_color ?>40; display: flex; align-items: center; gap: 20px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
                <i class="fas <?= $absen_icon ?>" style="font-size: 32px;"></i>
                <div>
                    <div style="font-size: 12px; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 4px;">Informasi Kehadiran Hari Ini</div>
                    <div style="font-size: 15px; color: #1e293b;"><?= $pesan_absen ?></div>
                </div>
            </div>
        <?php endif; ?>

        <?php if($success_msg): ?>
            <div style="background: #dcfce7; color: #166534; padding: 15px; border-radius: 12px; margin-bottom: 25px; border: 1px solid #bbf7d0; font-weight: 600;">
                <i class="fas fa-check-circle"></i> <?= $success_msg ?>
            </div>
        <?php endif; ?>

        <!-- FORM TAUTKAN ANAK -->
        <?php if (!$data_anak): ?>
            <div class="content-card" style="border-left: 5px solid #ef4444; margin-bottom: 30px;">
                <div class="card-header" style="color: #991b1b; font-size: 18px;">
                    <span><i class="fas fa-link-slash"></i> Hubungkan Akun dengan Murid</span>
                </div>
                <p style="margin-bottom: 20px; color: #64748b; font-size: 14px; line-height: 1.6;">
                    Akun Anda belum terhubung dengan data murid. Masukkan NIS ananda untuk memantau perkembangan.
                </p>
                
                <?php if($error_link): ?>
                    <div style="background: #fef2f2; color: #991b1b; padding: 12px; border-radius: 8px; margin-bottom: 15px; font-size: 13px; font-weight: 600;">
                        <?= $error_link ?>
                    </div>
                <?php endif; ?>

                <form method="POST" style="display: flex; gap: 12px;">
                    <input type="text" name="nis_murid" placeholder="Contoh: 220001" required 
                           style="flex: 1; padding: 14px; border-radius: 10px; border: 1px solid #cbd5e1; font-size: 14px;">
                    <button type="submit" name="tautkan_anak" style="padding: 0 30px; background: #3b82f6; color: white; border: none; border-radius: 10px; font-weight: 700; cursor: pointer;">
                        <i class="fas fa-link"></i> Tautkan
                    </button>
                </form>
            </div>
        <?php endif; ?>

        <!-- STATS CARD -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="label"><i class="fas fa-child" style="color: #3b82f6;"></i> Nama Anak</div>
                <div class="value" style="font-size: 16px; margin-top: 8px;"><?= htmlspecialchars($nama_anak); ?></div>
            </div>

            <div class="stat-card">
                <div class="label"><i class="fas fa-wallet" style="color: #10b981;"></i> Status SPP</div>
                <div class="value" style="font-size: 15px; margin-top: 8px; color: <?= ($status_spp != 'N/A' && strpos($status_spp, 'Lunas') !== false) ? '#166534' : '#ef4444'; ?>;">
                    <?= htmlspecialchars($status_spp); ?>
                </div>
            </div>

            <div class="stat-card">
                <div class="label"><i class="fas fa-bullhorn" style="color: #f59e0b;"></i> Pengumuman</div>
                <div class="value" style="margin-top: 8px;"><?= htmlspecialchars($jumlah_pengumuman); ?> Total</div>
            </div>

            <div class="stat-card">
                <div class="label"><i class="fas fa-calendar-day" style="color: #8b5cf6;"></i> Tahun Ajaran</div>
                <div class="value" style="margin-top: 8px;"><?= htmlspecialchars($tahun_ajaran); ?></div>
            </div>
        </div>

        <?php if ($data_anak): ?>
        <div class="dashboard-grid">
            <div class="content-card">
                <div class="card-header">Informasi Singkat</div>
                <div class="info-list" style="margin-top: 15px;">
                    <div style="display: flex; gap: 15px; margin-bottom: 20px;">
                        <div style="background: #eff6ff; color: #2563eb; width: 45px; height: 45px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 20px;">
                            <i class="fas fa-school"></i>
                        </div>
                        <div>
                            <strong style="display: block; font-size: 14px;">Rombongan Belajar</strong>
                            <span style="color: #64748b; font-size: 13px;">Ananda berada di <b><?= htmlspecialchars($nama_kelas); ?></b></span>
                        </div>
                    </div>
                    <div style="display: flex; gap: 15px;">
                        <div style="background: #f0fdf4; color: #166534; width: 45px; height: 45px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 20px;">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <div>
                            <strong style="display: block; font-size: 14px;">Perkembangan</strong>
                            <span style="color: #64748b; font-size: 13px;">Cek nilai karakter & akademik di menu perkembangan.</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="content-card">
                <div class="card-header">Aksi Cepat</div>
                <div style="display: flex; flex-direction: column; gap: 12px; margin-top: 15px;">
                    <a href="informasi_anak.php" style="text-decoration: none; padding: 14px 16px; background: #eff6ff; color: #1e40af; border-radius: 10px; font-weight: 600; display: flex; align-items: center; gap: 12px; font-size: 14px; border: 1px solid #bfdbfe;">
                        <i class="fas fa-user-graduate" style="font-size: 18px;"></i> Profil Lengkap Murid
                    </a>
                    <a href="pembayaran.php" style="text-decoration: none; padding: 14px 16px; background: #faf5ff; color: #6b21a8; border-radius: 10px; font-weight: 600; display: flex; align-items: center; gap: 12px; font-size: 14px; border: 1px solid #e9d5ff;">
                        <i class="fas fa-receipt" style="font-size: 18px;"></i> Cek Pembayaran SPP
                    </a>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </main>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            let currentIndex = 0;
            const slider = document.getElementById('slider');
            const slides = document.querySelectorAll('.slide');
            const totalSlides = slides.length;

            if (totalSlides > 1) {
                setInterval(() => {
                    currentIndex = (currentIndex + 1) % totalSlides;
                    slider.style.transform = `translateX(-${currentIndex * 100}%)`;
                }, 5000); // Slide otomatis setiap 5 detik
            }
        });
    </script>
</body>
</html>