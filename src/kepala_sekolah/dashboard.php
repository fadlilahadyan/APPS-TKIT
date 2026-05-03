<?php
// FILE: src/kepala_sekolah/dashboard.php
session_start();
require_once '../config/db.php';
date_default_timezone_set('Asia/Jakarta');

// Proteksi
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'kepala_sekolah') {
    header("Location: ../auth/login.php");
    exit();
}

try {
    // 1. Hitung Total Siswa Aktif
    $total_siswa = $pdo->query("SELECT COUNT(id_siswa) FROM siswa WHERE status = 'Aktif'")->fetchColumn();

    // 2. Hitung Total Keuangan SPP LUNAS (SINKRON spp_status)
    $total_spp = $pdo->query("SELECT SUM(jumlah) FROM spp_status WHERE status = 'LUNAS'")->fetchColumn() ?? 0;

    // 3. Pengumuman Aktif
    $pengumuman_aktif = $pdo->query("SELECT COUNT(id_pengumuman) FROM pengumuman")->fetchColumn();

    // 4. Guru Pengajar Aktif (Real-time dari tabel users)
    $total_guru = $pdo->query("SELECT COUNT(id_user) FROM users WHERE role = 'guru'")->fetchColumn(); 

    // 5. Aktivitas Terbaru (Mendeteksi guru yang baru saja menginput laporan)
    $aktivitas = $pdo->query("
        SELECT u.nama_lengkap as nama_guru, s.nama_siswa, p.tanggal, p.aspek 
        FROM perkembangan p
        JOIN users u ON p.id_guru = u.id_user
        JOIN siswa s ON p.id_siswa = s.id_siswa
        ORDER BY p.tanggal DESC, p.id_laporan DESC
        LIMIT 4
    ")->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Error Database: " . $e->getMessage());
}

// Tahun Ajaran Otomatis
$bulan = date('n');
$tahun = date('Y');
$tahun_ajaran = ($bulan >= 7) ? $tahun . '-' . ($tahun + 1) : ($tahun - 1) . '-' . $tahun;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Kepala Sekolah - SINKRON</title>
    <link rel="stylesheet" href="dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <div class="header">
            <h1>Dashboard Kepala Sekolah</h1>
            <p>Sistem Pemantauan Terpadu (Real-time Database)</p>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="label">Total Siswa Aktif</div>
                <div class="value"><?= $total_siswa ?> <span style="font-size:14px; color:#64748b;">Anak</span></div>
            </div>
            <div class="stat-card" style="border-left: 5px solid #10b981;">
                <div class="label">Total Keuangan SPP</div>
                <div class="value" style="font-size: 1.2rem; color: #10b981;">Rp <?= number_format($total_spp, 0, ',', '.') ?></div>
            </div>
            <div class="stat-card">
                <div class="label">Total Guru Aktif</div>
                <div class="value"><?= $total_guru ?> <span style="font-size:14px; color:#64748b;">Orang</span></div>
            </div>
            <div class="stat-card">
                <div class="label">Tahun Ajaran</div>
                <div class="value" style="font-size: 1.5rem;"><?= $tahun_ajaran ?></div>
            </div>
        </div>

        <div class="dashboard-grid">
            <div class="content-card">
                <div class="card-header"><i class="fas fa-history"></i> Aktivitas Guru Terbaru</div>
                <ul style="list-style: none; padding: 0;">
                    <?php if(empty($aktivitas)): ?>
                        <li style="color: #94a3b8; text-align: center; padding: 20px;">Belum ada aktivitas guru hari ini.</li>
                    <?php else: ?>
                        <?php foreach($aktivitas as $act): ?>
                            <li style="padding: 12px 0; border-bottom: 1px solid #f1f5f9; display: flex; gap: 12px;">
                                <div style="background: #eff6ff; color: #2563eb; width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                    <i class="fas fa-edit"></i>
                                </div>
                                <div>
                                    <strong style="color: #1e293b; font-size: 14px;"><?= htmlspecialchars($act['nama_guru']) ?></strong>
                                    <div style="font-size: 13px; color: #64748b;">Menginput aspek <b style="color:#2563eb;"><?= htmlspecialchars($act['aspek']) ?></b> untuk <?= htmlspecialchars($act['nama_siswa']) ?></div>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ul>
            </div>

            <div class="content-card">
                <div class="card-header"><i class="fas fa-bolt"></i> Aksi Cepat</div>
                <a href="pengumuman.php" style="text-decoration: none; display: block; margin-bottom: 10px;">
                    <button class="action-btn btn-blue" style="width: 100%;"><i class="fas fa-bullhorn"></i> Buat Pengumuman</button>
                </a>
                <a href="monitoring_spp.php" style="text-decoration: none; display: block;">
                    <button class="action-btn btn-purple" style="width: 100%;"><i class="fas fa-wallet"></i> Cek Keuangan SPP</button>
                </a>
            </div>
        </div>
    </div>
</body>
</html>