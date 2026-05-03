<?php
session_start();
require_once '../config/db.php';
date_default_timezone_set('Asia/Jakarta');

if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'operator') {
    header("Location: ../auth/login.php");
    exit();
}

$nama_operator = $_SESSION['nama_lengkap'] ?? 'Operator';

try {
    $total_siswa = $pdo->query("SELECT COUNT(id_siswa) FROM siswa WHERE status = 'Aktif'")->fetchColumn();
    $total_guru = $pdo->query("SELECT COUNT(id_guru) FROM guru")->fetchColumn();
    $total_kelas = $pdo->query("SELECT COUNT(id_kelas) FROM kelas")->fetchColumn();
    $jml_pengumuman = $pdo->query("SELECT COUNT(*) FROM pengumuman")->fetchColumn();

    $laporan_stmt = $pdo->query("SELECT * FROM laporan_administrasi ORDER BY tanggal DESC LIMIT 5");
    $laporan_list = $laporan_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Kesalahan Database: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Operator - SIS TKIT FATHUROBANI</title>
    <link rel="stylesheet" href="dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <main class="main-content">
        <header class="header">
            <h1>Panel Kendali Utama</h1>
            <p>Selamat datang kembali, <strong><?= htmlspecialchars($nama_operator) ?></strong></p>
        </header>

        <div class="hero-banner">
            <div style="z-index: 1;">
                <h2 style="margin: 0; font-size: 22px;">Manajemen Data Master</h2>
                <p style="margin: 10px 0 0 0; opacity: 0.9; font-size: 14px;">Semua kendali operasional sekolah berada dalam satu dashboard terpusat.</p>
            </div>
            <i class="fas fa-user-shield" style="font-size: 60px; opacity: 0.2; position: absolute; right: 30px;"></i>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <span class="label"><i class="fas fa-user-graduate"></i> Siswa Aktif</span>
                <span class="value"><?= $total_siswa ?></span>
            </div>
            <div class="stat-card">
                <span class="label"><i class="fas fa-chalkboard-teacher"></i> Total Guru</span>
                <span class="value"><?= $total_guru ?></span>
            </div>
            <div class="stat-card">
                <span class="label"><i class="fas fa-school"></i> Data Kelas</span>
                <span class="value"><?= $total_kelas ?></span>
            </div>
            <div class="stat-card">
                <span class="label"><i class="fas fa-bullhorn"></i> Pengumuman</span>
                <span class="value"><?= $jml_pengumuman ?></span>
            </div>
        </div>

        <div class="dashboard-grid">
            <div class="content-card">
                <div class="card-header">Laporan Administrasi Terbaru</div>
                <?php if (empty($laporan_list)): ?>
                    <p style="color: #94a3b8; text-align: center;">Belum ada laporan masuk.</p>
                <?php else: ?>
                    <?php foreach($laporan_list as $lap): ?>
                    <div style="padding: 15px 0; border-bottom: 1px solid #f1f5f9;">
                        <strong style="display: block; color: #1e293b;"><?= htmlspecialchars($lap['judul']) ?></strong>
                        <small style="color: #64748b;"><?= date('d F Y', strtotime($lap['tanggal'])) ?></small>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="content-card">
                <div class="card-header">Akses Cepat Manajemen</div>
                <a href="data_siswa.php" class="btn-add-modern" style="width: 100%; justify-content: center; margin-bottom: 10px;">
                    <i class="fas fa-user-edit"></i> Kelola Data Siswa
                </a>
                <a href="data_guru.php" class="btn-add-modern" style="width: 100%; justify-content: center; margin-bottom: 10px;">
                    <i class="fas fa-chalkboard-teacher"></i> Kelola Data Guru
                </a>
                <a href="buat_pengumuman.php" class="btn-add-modern" style="width: 100%; justify-content: center;">
                    <i class="fas fa-bullhorn"></i> Siarkan Informasi
                </a>
            </div>
        </div>
    </main>
</body>
</html>