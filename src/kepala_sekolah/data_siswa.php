<?php
session_start();
require_once '../config/db.php';

// Proteksi
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'kepala_sekolah') {
    header("Location: ../auth/login.php");
    exit();
}

// Ambil data siswa
$query = "SELECT * FROM siswa ORDER BY nama_siswa ASC";
$stmt = $pdo->query($query);
$siswa = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Siswa - TKIT FATHUROBANI</title>
    <link rel="stylesheet" href="dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <div class="header">
            <h1>👦👧 Data Siswa</h1>
            <p>Manajemen data seluruh siswa aktif.</p>
        </div>

        <div class="content-card">
            <div class="card-header">Daftar Siswa</div>
            <div style="overflow-x: auto; margin-top: 15px;">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="text-align: left; background: #f8fafc; border-bottom: 2px solid #e2e8f0;">
                            <th style="padding: 12px;">NIS</th>
                            <th>Nama Siswa</th>
                            <th>Jenis Kelamin</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($siswa as $s): ?>
                        <tr style="border-bottom: 1px solid #f1f5f9;">
                            <td style="padding: 12px;"><?= $s['nis'] ?></td>
                            <td style="font-weight: 600;"><?= htmlspecialchars($s['nama_siswa']) ?></td>
                            <td><?= $s['jk'] ?></td>
                            <td><span style="color: #10b981;">● <?= $s['status'] ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>