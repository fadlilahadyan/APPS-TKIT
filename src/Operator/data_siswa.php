<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'operator') {
    header("Location: ../auth/login.php");
    exit;
}

if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    $stmt = $pdo->prepare("UPDATE siswa SET status = 'Tidak Aktif' WHERE id_siswa = ?");
    $stmt->execute([$id]);
    header("Location: data_siswa.php?msg=berhasil");
    exit;
}

$siswa_list = $pdo->query("
    SELECT s.*, k.nama_kelas 
    FROM siswa s 
    LEFT JOIN kelas k ON s.id_kelas = k.id_kelas 
    WHERE s.status = 'Aktif'
    ORDER BY s.nama_siswa ASC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Siswa - Operator</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="dashboard.css">
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <main class="main-content">
        <div class="header-container">
            <div>
                <h1>Data Siswa</h1>
                <p style="margin: 0; color: #64748b; font-size: 14px;">Kelola informasi induk siswa aktif</p>
            </div>
            <a href="form_siswa.php" class="btn-add-modern">
                <i class="fas fa-plus"></i> Tambah Siswa
            </a>
        </div>

        <?php if (isset($_GET['msg'])): ?>
            <div style="background: #dcfce7; color: #166534; padding: 12px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #bbf7d0; font-weight: 600;">
                <i class="fas fa-check-circle"></i> Data berhasil diperbarui!
            </div>
        <?php endif; ?>

        <div class="table-card">
            <table class="modern-table">
                <thead>
                    <tr>
                        <th>NIS</th>
                        <th>Nama Siswa</th>
                        <th>Kelas</th>
                        <th>Alamat</th>
                        <th style="text-align: center;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($siswa_list)): ?>
                        <tr><td colspan="5" style="text-align: center; color: #64748b; padding: 40px;">Tidak ada data siswa aktif</td></tr>
                    <?php else: ?>
                        <?php foreach($siswa_list as $siswa): ?>
                        <tr>
                            <td><?= htmlspecialchars($siswa['nis']) ?></td>
                            <td><strong><?= htmlspecialchars($siswa['nama_siswa']) ?></strong></td>
                            <td>
                                <span style="background: #eff6ff; color: #2563eb; padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 600;">
                                    <?= htmlspecialchars($siswa['nama_kelas'] ?? '') ?>
                                </span>
                            </td>
                            <td style="color: #64748b; font-size: 14px;"><?= htmlspecialchars($siswa['alamat']) ?></td>
                            <td style="text-align: center;">
                                <a href="form_siswa.php?id=<?= $siswa['id_siswa'] ?>" style="color: #2563eb; margin-right: 12px; text-decoration: none; font-weight: 600;"><i class="fas fa-edit"></i> Edit</a>
                                <a href="?hapus=<?= $siswa['id_siswa'] ?>" style="color: #ef4444; text-decoration: none; font-weight: 600;" onclick="return confirm('Yakin menonaktifkan siswa ini?')"><i class="fas fa-trash-alt"></i> Hapus</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</body>
</html>