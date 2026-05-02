<?php
session_start(); 
require_once '../config/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'bendahara') {
    header("Location: ../auth/login.php");
    exit();
}

try {
    // JOIN ke tabel kelas agar muncul nama kelas aslinya
    $sql = "SELECT s.*, k.nama_kelas 
            FROM siswa s 
            LEFT JOIN kelas k ON s.id_kelas = k.id_kelas 
            ORDER BY s.nama_siswa ASC";
    $stmt = $pdo->query($sql);
    $data_siswa = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error Database: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Data Siswa - SIS TKIT FATHUROBANIrobbany</title>
    <link rel="stylesheet" href="dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <main class="main-content">
        <header class="header">
            <h1>Data Siswa</h1>
            <p>Manajemen database murid aktif TKIT Fathurrobbany</p>
        </header>

        <div class="content-card">
            <div class="card-header">
                <span><i class="fas fa-users"></i> Daftar Murid</span>
                <a href="tambah_siswa.php" class="btn-primary" style="padding: 8px 16px; text-decoration:none; font-size:12px; border-radius:8px;">+ Tambah</a>
            </div>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>NIS</th>
                            <th>NAMA LENGKAP</th>
                            <th>JK</th>
                            <th>KELAS</th>
                            <th>STATUS</th>
                            <th style="text-align:center;">AKSI</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($data_siswa as $s): ?>
                        <tr>
                            <td><strong style="color: var(--primary);"><?= htmlspecialchars($s['nis']) ?></strong></td>
                            <td>
                                <div style="font-weight:700;"><?= htmlspecialchars($s['nama_siswa']) ?></div>
                                <div style="font-size:11px; color:var(--text-muted);"><?= htmlspecialchars($s['alamat']) ?></div>
                            </td>
                            <td><?= ($s['jk'] == 'L') ? 'Laki-laki' : 'Perempuan' ?></td>
                            <td><?= htmlspecialchars($s['nama_kelas'] ?? 'Belum Ada Kelas') ?></td>
                            <td><span class="badge badge-lunas">AKTIF</span></td>
                            <td style="text-align:center;">
                                <a href="edit_siswa.php?id=<?= $s['id_siswa'] ?>" style="color:var(--primary); margin-right:10px;"><i class="fas fa-edit"></i></a>
                                <a href="hapus_siswa.php?id=<?= $s['id_siswa'] ?>" style="color:#ef4444;" onclick="return confirm('Hapus murid ini?')"><i class="fas fa-trash"></i></a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</body>
</html>