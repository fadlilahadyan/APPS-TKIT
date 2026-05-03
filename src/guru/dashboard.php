<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Panggil DB (Pastikan path benar)
require_once '../config/db.php'; 
date_default_timezone_set('Asia/Jakarta');

// Pastikan session berjalan
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Proteksi Role Guru
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'guru') {
    header("Location: ../auth/login.php");
    exit();
}

$id_user = $_SESSION['id_user'];
$hari_ini = date('Y-m-d');
$bulan_sekarang = (int)date('n');
$tahun_sekarang = (int)date('Y');

try {
    // 1. Total Siswa Aktif
    $stmtSiswa = $pdo->query("SELECT COUNT(id_siswa) FROM siswa WHERE status = 'Aktif'");
    $total_siswa = (int)$stmtSiswa->fetchColumn();

    // 2. Hadir Hari Ini
    $stmtHadir = $pdo->prepare("SELECT COUNT(id_absen) FROM absensi WHERE tanggal = ? AND status = 'Hadir'");
    $stmtHadir->execute([$hari_ini]);
    $hadir_hari_ini = (int)$stmtHadir->fetchColumn();

    // 3. Belum Bayar SPP Bulan Ini
    $stmtLunas = $pdo->prepare("SELECT COUNT(id) FROM spp_status WHERE bulan = ? AND tahun = ? AND status = 'LUNAS'");
    $stmtLunas->execute([$bulan_sekarang, $tahun_sekarang]);
    $sudah_bayar = (int)$stmtLunas->fetchColumn();
    $belum_bayar_spp = max(0, $total_siswa - $sudah_bayar); 

    // 4. LOGIKA GABUNGAN AKTIVITAS TERBARU (PENGUMUMAN + UNDANGAN)
    // Kita gunakan UNION ALL agar data dari dua tabel bisa disatukan dan diurutkan bersamaan
    $sqlAktivitas = "
        (SELECT judul, tanggal, prioritas, 'Pengumuman' as tipe 
         FROM pengumuman 
         WHERE id_user = :id_user)
        UNION ALL
        (SELECT judul, tanggal, 'Tinggi' as prioritas, 'Undangan' as tipe 
         FROM undangan 
         WHERE id_guru = :id_user)
        ORDER BY tanggal DESC 
        LIMIT 5
    ";
    $stmtAkt = $pdo->prepare($sqlAktivitas);
    $stmtAkt->execute(['id_user' => $id_user]);
    $aktivitas_terbaru = $stmtAkt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Error mengambil data dashboard: " . $e->getMessage());
}

$tahun_ajaran = ($bulan_sekarang >= 7) ? $tahun_sekarang . '-' . ($tahun_sekarang + 1) : ($tahun_sekarang - 1) . '-' . $tahun_sekarang;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Guru - SIS TKIT FATHUROBANI</title>
    <link rel="stylesheet" href="dashboard.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* CSS Tambahan Khusus Dashboard */
        .header { margin-bottom: 30px; }
        .header h1 { font-size: 28px; font-weight: 800; color: #1e293b; margin: 0 0 8px 0; }
        .header p { color: #64748b; font-size: 15px; margin: 0; }

        .stats-grid-modern { display: grid; grid-template-columns: repeat(4, 1fr); gap: 24px; margin-bottom: 30px; }
        .stat-card-modern { background: #ffffff; border-radius: 20px; padding: 24px; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.03); display: flex; align-items: center; justify-content: space-between; border: 1px solid #f1f5f9; transition: transform 0.3s ease; }
        .stat-card-modern:hover { transform: translateY(-5px); }
        .stat-info .label { font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px; display: block; }
        .stat-info .value { font-size: 24px; font-weight: 800; color: #0f172a; }
        .stat-icon { width: 50px; height: 50px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 22px; }
        
        .icon-blue { background: #eff6ff; color: #3b82f6; }
        .icon-green { background: #ecfdf5; color: #10b981; }
        .icon-red { background: #fef2f2; color: #ef4444; }
        .icon-purple { background: #faf5ff; color: #8b5cf6; }

        .dashboard-grid-modern { display: grid; grid-template-columns: 2fr 1fr; gap: 30px; }
        .box-modern { background: #ffffff; border-radius: 24px; padding: 25px; border: 1px solid #f1f5f9; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.02); }
        .box-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid #f1f5f9; }
        .box-title { font-size: 18px; font-weight: 800; color: #1e293b; margin: 0; display: flex; align-items: center; gap: 10px; }

        .tx-list { display: flex; flex-direction: column; gap: 12px; }
        .tx-item { display: flex; align-items: center; justify-content: space-between; padding: 12px; border-radius: 16px; border: 1px solid transparent; transition: 0.2s; }
        .tx-item:hover { background: #f8fafc; border-color: #e2e8f0; }
        .tx-left { display: flex; align-items: center; gap: 15px; }
        .tx-avatar { width: 45px; height: 45px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 18px; font-weight: 800; }
        
        /* Warna Avatar Berdasarkan Tipe Aktivitas */
        .avatar-pengumuman { background: #eff6ff; color: #3b82f6; }
        .avatar-undangan { background: #f0fdf4; color: #10b981; }
        .avatar-urgent { background: #fee2e2; color: #dc2626; }

        .tx-name { font-size: 14px; font-weight: 700; color: #0f172a; margin-bottom: 4px; display: block; }
        .tx-type { font-size: 11px; font-weight: 800; text-transform: uppercase; padding: 2px 8px; border-radius: 6px; background: #f1f5f9; color: #64748b; }
        .tx-date { font-size: 12px; color: #94a3b8; font-weight: 600; }

        .action-buttons { display: flex; flex-direction: column; gap: 15px; }
        .btn-modern { width: 100%; padding: 16px 20px; border-radius: 16px; border: none; font-weight: 700; font-size: 14px; cursor: pointer; display: flex; align-items: center; gap: 12px; transition: all 0.3s; text-decoration: none; }
        .btn-modern:hover { transform: translateY(-3px); box-shadow: 0 8px 20px rgba(0,0,0,0.1); }
        .btn-announce { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: white; }
        .btn-attendance { background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; }
        .btn-report { background: linear-gradient(135deg, #8b5cf6 0%, #6d28d9 100%); color: white; }

        @media screen and (max-width: 1024px) {
            .stats-grid-modern { grid-template-columns: repeat(2, 1fr); }
            .dashboard-grid-modern { grid-template-columns: 1fr; }
        }
        @media screen and (max-width: 480px) {
            .stats-grid-modern { grid-template-columns: repeat(2, 1fr); gap: 12px; }
            .stat-card-modern { padding: 16px; }
            .stat-card-modern .value { font-size: 18px !important; }
        }
    </style>
</head>
<body>

    <?php include 'sidebar.php'; ?>

    <main class="main-content">
        <header class="header">
            <h1>Halo, <?= htmlspecialchars($_SESSION['nama_lengkap'] ?? 'Guru') ?>! 👋</h1>
            <p>Sistem Manajemen Sekolah - Data tersinkronisasi secara real-time.</p>
        </header>

        <!-- STATS GRID -->
        <section class="stats-grid-modern">
            <div class="stat-card-modern">
                <div class="stat-info">
                    <span class="label">Total Murid</span>
                    <span class="value" id="stat-total-siswa"><?= $total_siswa ?></span>
                </div>
                <div class="stat-icon icon-blue"><i class="fas fa-users"></i></div>
            </div>
            
            <div class="stat-card-modern">
                <div class="stat-info">
                    <span class="label">Hadir Hari Ini</span>
                    <span class="value" id="stat-hadir-ini"><?= $hadir_hari_ini ?></span>
                </div>
                <div class="stat-icon icon-green"><i class="fas fa-user-check"></i></div>
            </div>
            
            <div class="stat-card-modern">
                <div class="stat-info">
                    <span class="label">Belum Bayar SPP</span>
                    <span class="value" id="stat-belum-spp" style="color: #ef4444;"><?= $belum_bayar_spp ?></span>
                </div>
                <div class="stat-icon icon-red"><i class="fas fa-wallet"></i></div>
            </div>
            
            <div class="stat-card-modern">
                <div class="stat-info">
                    <span class="label">Tahun Ajaran</span>
                    <span class="value" style="font-size: 18px;"><?= $tahun_ajaran ?></span>
                </div>
                <div class="stat-icon icon-purple"><i class="fas fa-calendar-alt"></i></div>
            </div>
        </section>

        <div class="dashboard-grid-modern">
            <!-- AKTIVITAS TERBARU (GABUNGAN) -->
            <div class="box-modern">
                <div class="box-header">
                    <h3 class="box-title"><i class="fas fa-bolt" style="color: #f59e0b;"></i> Aktivitas Terbaru Anda</h3>
                    <span class="tx-date">Bulan Ini</span>
                </div>
                <div class="tx-list">
                    <?php if (empty($aktivitas_terbaru)): ?>
                        <div style="text-align: center; padding: 40px; color: #94a3b8;">Belum ada aktivitas baru.</div>
                    <?php else: ?>
                        <?php foreach ($aktivitas_terbaru as $act): 
                            // Tentukan Icon & Warna berdasarkan tipe
                            $is_urgent = ($act['prioritas'] == 'Tinggi');
                            $avatar_class = $is_urgent ? 'avatar-urgent' : ($act['tipe'] == 'Undangan' ? 'avatar-undangan' : 'avatar-pengumuman');
                            $icon = ($act['tipe'] == 'Undangan') ? 'fa-envelope-open-text' : ($is_urgent ? 'fa-exclamation-circle' : 'fa-bullhorn');
                        ?>
                            <div class="tx-item">
                                <div class="tx-left">
                                    <div class="tx-avatar <?= $avatar_class ?>">
                                        <i class="fas <?= $icon ?>"></i>
                                    </div>
                                    <div>
                                        <span class="tx-name"><?= htmlspecialchars($act['judul']) ?></span>
                                        <span class="tx-type"><?= $act['tipe'] ?></span>
                                    </div>
                                </div>
                                <div class="tx-date">
                                    <?= date('d M', strtotime($act['tanggal'])) ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- AKSI CEPAT -->
            <div class="box-modern">
                <div class="box-header"><h3 class="box-title"><i class="fas fa-th-large" style="color: #3b82f6;"></i> Pintasan</h3></div>
                <div class="action-buttons">
                    <a href="undangan.php" class="btn-modern" style="background: #fdf2f8; color: #be185d; border: 1px solid #fbcfe8;"><i class="fas fa-envelope-open-text"></i> Daftar Undangan</a>
                    <a href="presensi.php" class="btn-modern" style="background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0;"><i class="fas fa-user-check"></i> Input Absensi</a>
                    <a href="laporan.php" class="btn-modern" style="background: #faf5ff; color: #6b21a8; border: 1px solid #e9d5ff;"><i class="fas fa-chart-line"></i> Catat Perkembangan</a>
                </div>
            </div>
        </div>
    </main>

    <!-- AJAX SCRIPT UNTUK REAL-TIME UPDATE -->
    <script>
        function updateStats() {
            fetch('get_dashboard_stats.php')
                .then(response => response.json())
                .then(data => {
                    if (!data.error) {
                        document.getElementById('stat-total-siswa').innerText = data.total_siswa;
                        document.getElementById('stat-hadir-ini').innerText = data.hadir_hari_ini;
                        document.getElementById('stat-belum-spp').innerText = data.belum_bayar_spp;
                    }
                })
                .catch(error => console.error('Error:', error));
        }
        setInterval(updateStats, 5000); // Sinkronisasi setiap 5 detik
    </script>
</body>
</html>