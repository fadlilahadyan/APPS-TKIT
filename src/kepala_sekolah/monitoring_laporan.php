<?php
// src/kepala_sekolah/monitoring_laporan.php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'kepala_sekolah') {
    header("Location: ../auth/login.php");
    exit();
}

// Query SINKRON dengan tabel perkembangan dinamis
$query = "SELECT p.*, s.nama_siswa, u.nama_lengkap as nama_guru 
          FROM perkembangan p 
          JOIN siswa s ON p.id_siswa = s.id_siswa 
          JOIN users u ON p.id_guru = u.id_user 
          ORDER BY p.tanggal DESC, p.id_laporan DESC";
$stmt = $pdo->prepare($query);
$stmt->execute();
$reports = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monitoring Perkembangan - SINKRON</title>
    <link rel="stylesheet" href="dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <div class="header">
            <h1>📈 Monitoring Laporan Perkembangan</h1>
            <p>Rekapitulasi seluruh aktivitas perkembangan anak dari tabel utama.</p>
        </div>

        <div class="content-card">
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background: #f8fafc; text-align: left; border-bottom: 2px solid #e2e8f0;">
                            <th style="padding: 12px; width:15%;">Tanggal</th>
                            <th style="padding: 12px; width:20%;">Nama Siswa</th>
                            <th style="padding: 12px; width:15%;">Aspek</th>
                            <th style="padding: 12px; width:35%;">Deskripsi Capaian</th>
                            <th style="padding: 12px; width:15%;">Guru Penginput</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($reports)): ?>
                            <tr><td colspan="5" style="text-align:center; padding:30px; color:#64748b;">Belum ada input perkembangan dari guru.</td></tr>
                        <?php endif; ?>
                        
                        <?php foreach($reports as $r): ?>
                        <tr style="border-bottom: 1px solid #f1f5f9;">
                            <td style="padding: 12px; color:#64748b; font-size:14px;"><?= date('d/m/Y', strtotime($r['tanggal'])) ?></td>
                            <td style="padding: 12px;"><strong><?= htmlspecialchars($r['nama_siswa']) ?></strong></td>
                            <td style="padding: 12px;"><span style="background:#eff6ff; color:#2563eb; padding:4px 8px; border-radius:4px; font-size:12px; font-weight:700;"><?= htmlspecialchars($r['aspek']) ?></span></td>
                            <td style="padding: 12px; font-size:14px; line-height:1.5;"><?= htmlspecialchars($r['deskripsi']) ?></td>
                            <td style="padding: 12px;"><span style="background:#f1f5f9; color:#475569; padding:4px 8px; border-radius:20px; font-size:12px; font-weight:600;"><i class="fas fa-user"></i> <?= htmlspecialchars($r['nama_guru']) ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>