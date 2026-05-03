<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'kepala_sekolah') {
    header("Location: ../auth/login.php");
    exit();
}

// Ambil data guru dari tabel users
$query = "SELECT * FROM users WHERE role = 'guru' ORDER BY nama_lengkap ASC";
$stmt = $pdo->query($query);
$guru = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Guru - TKIT FATHUROBANI</title>
    <link rel="stylesheet" href="dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <div class="header">
            <h1>🧑‍🏫 Data Guru</h1>
            <p>Kelola informasi tenaga pendidik.</p>
        </div>

        <div class="content-card">
            <div class="card-header">Daftar Guru Pengajar</div>
            <div style="overflow-x: auto; margin-top: 15px;">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="text-align: left; background: #f8fafc; border-bottom: 2px solid #e2e8f0;">
                            <th style="padding: 12px;">Nama Lengkap</th>
                            <th>Email</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($guru)): ?>
                            <tr>
                                <td colspan="2" style="text-align: center; padding: 20px; color: #94a3b8;">Belum ada data guru.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach($guru as $g): ?>
                            <tr style="border-bottom: 1px solid #f1f5f9;">
                                <td style="padding: 12px; font-weight: 600;"><?= htmlspecialchars($g['nama_lengkap']) ?></td>
                                
                                <td><?= htmlspecialchars($g['username']) ?></td>
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