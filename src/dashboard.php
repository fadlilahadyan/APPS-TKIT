<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/db.php';
date_default_timezone_set('Asia/Jakarta');

if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'guru') {
    header("Location: ../login.php");
    exit();
}

$id_user = $_SESSION['id_user'];
$hari_ini = date('Y-m-d');

try {
    // 1. Hitung Total Siswa (Fleksibel: hitung semua siswa aktif di database)
    $stmtSiswa = $pdo->query("SELECT COUNT(id_siswa) FROM siswa WHERE status = 'Aktif'");
    $total_siswa = $stmtSiswa->fetchColumn();

    // 2. Hitung Siswa Hadir Hari Ini (Fleksibel: hitung semua yang hadir hari ini)
    $stmtHadir = $pdo->prepare("SELECT COUNT(id_absen) FROM absensi WHERE tanggal = ? AND status = 'Hadir'");
    $stmtHadir->execute([$hari_ini]);
    $hadir_hari_ini = $stmtHadir->fetchColumn();

    // 3. Belum Bayar SPP (Dibiarkan 0 dulu menyesuaikan database yang sekarang)
    $belum_bayar_spp = 0; 

    // 4. Ambil Aktivitas Terbaru (Pengumuman)
    $stmtAktivitas = $pdo->prepare("
        SELECT judul, prioritas, tanggal 
        FROM pengumuman 
        WHERE id_user = ? 
        ORDER BY tanggal DESC 
        LIMIT 4
    ");
    $stmtAktivitas->execute([$id_user]);
    $aktivitas_terbaru = $stmtAktivitas->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Error mengambil data dashboard: " . $e->getMessage());
}

// Penentuan Tahun Ajaran Otomatis
$bulan_sekarang = date('n');
$tahun_sekarang = date('Y');
$tahun_ajaran = ($bulan_sekarang >= 7) ? $tahun_sekarang . '-' . ($tahun_sekarang + 1) : ($tahun_sekarang - 1) . '-' . $tahun_sekarang;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Guru - Premium</title>
    <link rel="stylesheet" href="dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header">
            <i class="fas fa-graduation-cap" style="color: var(--accent);"></i> TKIT FATHUROBANI
        </div>
        
        <div class="nav-group">
            <span class="nav-label">Utama</span>
            <a href="dashboard.php" class="nav-item active"><i class="fas fa-th-large"></i> Dashboard</a>
        </div>

        <div class="nav-group">
            <span class="nav-label">Kelas</span>
            <a href="presensi/halaman_absensi_guru.php" class="nav-item"><i class="fas fa-calendar-check"></i> Absensi Siswa</a>
            <a href="laporan/laporan.php" class="nav-item"><i class="fas fa-chart-line"></i> Laporan Perkembangan</a>
        </div>

        <div class="nav-group">
            <span class="nav-label">Keuangan</span>
            <a href="informasi_spp/informasi_spp.php" class="nav-item"><i class="fas fa-wallet"></i> Informasi SPP</a>
        </div>

        <div style="margin-top: auto;">
            <a href="auth/logout.php" class="nav-item" style="color: #ef4444;"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>

    <div class="main-content">
        <div class="header">
            <h1>Dashboard</h1>
            <p>Welcome to the School Management System</p>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="label">Total Siswa Kelas</div>
                <div class="value"><?= $total_siswa ?></div>
            </div>
            <div class="stat-card">
                <div class="label">Hadir Hari Ini</div>
                <div class="value"><?= $hadir_hari_ini ?></div>
            </div>
            <div class="stat-card">
                <div class="label">Belum Bayar SPP</div>
                <div class="value"><?= $belum_bayar_spp ?></div>
            </div>
            <div class="stat-card">
                <div class="label">Tahun Ajaran</div>
                <div class="value" style="font-size: 1.5rem;"><?= $tahun_ajaran ?></div>
            </div>
        </div>

        <div class="dashboard-grid">
            <div class="content-card">
                <div class="card-header">Aktivitas Terbaru</div>
                <ul style="list-style: none; font-size: 14px; padding: 0;">
                    <?php if (empty($aktivitas_terbaru)): ?>
                        <li style="color: #94a3b8; text-align: center; padding: 20px 0;">Belum ada aktivitas terbaru.</li>
                    <?php else: ?>
                        <?php foreach ($aktivitas_terbaru as $act): ?>
                            <li style="margin-bottom: 15px; display: flex; gap: 15px; align-items: flex-start; border-bottom: 1px solid #f1f5f9; padding-bottom: 10px;">
                                <div style="background: <?= $act['prioritas'] == 'Tinggi' ? '#fee2e2' : '#eff6ff' ?>; color: <?= $act['prioritas'] == 'Tinggi' ? '#dc2626' : '#2563eb' ?>; padding: 10px; border-radius: 50%;">
                                    <i class="fas fa-bullhorn"></i>
                                </div>
                                <div>
                                    <strong style="color: #1e293b;">Pengumuman Dibuat:</strong> <?= htmlspecialchars($act['judul']) ?><br>
                                    <small style="color: #64748b; font-weight: 500;">
                                        <i class="fas fa-calendar-alt"></i> <?= date('d M Y', strtotime($act['tanggal'])) ?>
                                    </small>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ul>
            </div>

            <div class="content-card">
                <div class="card-header">Aksi Cepat</div>
                <a href="presensi/halaman_absensi_guru.php" style="text-decoration: none; display: block; margin-bottom: 10px;">
                    <button class="action-btn btn-blue" style="width: 100%;"><i class="fas fa-user-check"></i> Mulai Absensi</button>
                </a>
                <a href="laporan/laporan.php" style="text-decoration: none; display: block;">
                    <button class="action-btn btn-purple" style="width: 100%;"><i class="fas fa-file-alt"></i> Input Perkembangan</button>
                </a>
            </div>
        </div>
    </div>
</body>
</html>