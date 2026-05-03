<?php
session_start();
require_once '../config/db.php';

// Proteksi halaman
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'kepala_sekolah') {
    header("Location: ../auth/login.php");
    exit();
}

try {
    // 1. Ambil Total Seluruh Dana LUNAS
    $stmt_all = $pdo->query("SELECT SUM(jumlah) AS total FROM spp_status WHERE status = 'LUNAS'");
    $total_all = $stmt_all->fetchColumn() ?: 0; 

    // 2. Ambil Pemasukan Bulan Ini
    $bulan_sekarang = (int)date('n');
    $tahun_sekarang = (int)date('Y');
    $nama_bulan_arr = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    $bulan_nama = $nama_bulan_arr[$bulan_sekarang]; 

    $stmt_month = $pdo->prepare("SELECT SUM(jumlah) FROM spp_status WHERE status = 'LUNAS' AND bulan = ? AND tahun = ?");
    $stmt_month->execute([$bulan_sekarang, $tahun_sekarang]);
    $res_month = $stmt_month->fetchColumn() ?: 0;

    // 3. Riwayat Pembayaran (Dari tabel spp_status)
    $payments = $pdo->query("SELECT * FROM spp_status WHERE status = 'LUNAS' ORDER BY tanggal_bayar DESC, id DESC")->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Error Database: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monitoring SPP - TKIT FATHUROBANI</title>
    <link rel="stylesheet" href="dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <div class="header">
            <h1>💰 Monitoring Pembayaran SPP</h1>
            <p>Laporan pemasukan iuran siswa secara real-time dari database utama.</p>
        </div>

        <div class="stats-grid">
            <div class="stat-card" style="border-left: 5px solid #3b82f6;">
                <div class="label">Total Seluruh Dana</div>
                <div class="value">Rp <?= number_format($total_all, 0, ',', '.') ?></div>
            </div>
            <div class="stat-card" style="border-left: 5px solid #10b981;">
                <div class="label">Pemasukan Bulan <?= $bulan_nama ?></div>
                <div class="value">Rp <?= number_format($res_month, 0, ',', '.') ?></div>
            </div>
        </div>

        <div class="content-card">
            <div class="card-header">Riwayat Transaksi Terbaru</div>
            <div style="overflow-x: auto; margin-top: 15px;">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="text-align: left; background: #f8fafc; border-bottom: 2px solid #e2e8f0;">
                            <th style="padding: 12px;">Tanggal Bayar</th>
                            <th>Nama Siswa</th>
                            <th>Periode</th>
                            <th>Jumlah</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($payments)): ?>
                            <tr>
                                <td colspan="5" style="text-align: center; padding: 20px; color: #94a3b8;">Belum ada data pembayaran.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach($payments as $p): ?>
                            <tr style="border-bottom: 1px solid #f1f5f9;">
                                <td style="padding: 12px; color: #64748b;"><?= date('d M Y', strtotime($p['tanggal_bayar'])) ?></td>
                                <td><strong><?= htmlspecialchars($p['nama']) ?></strong></td>
                                <td><span style="background:#eff6ff; color:#2563eb; padding:4px 8px; border-radius:4px; font-size:12px; font-weight:600;"><?= $nama_bulan_arr[$p['bulan']] ?> <?= $p['tahun'] ?></span></td>
                                <td style="font-weight: 700; color: #1e293b;">Rp <?= number_format($p['jumlah'], 0, ',', '.') ?></td>
                                <td><span style="background: #dcfce7; color: #166534; padding: 4px 8px; border-radius: 20px; font-size: 11px; font-weight: 700;">LUNAS</span></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>