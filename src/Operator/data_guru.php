<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'operator') {
    header("Location: ../auth/login.php");
    exit();
}

if (isset($_GET['hapus'])) {
    try {
        $stmt = $pdo->prepare("DELETE FROM guru WHERE id_guru = ?");
        $stmt->execute([$_GET['hapus']]);
        header("Location: data_guru.php?msg=terhapus");
        exit();
    } catch (PDOException $e) {
        $error = "Gagal menghapus: " . $e->getMessage();
    }
}

try {
    $stmt = $pdo->query("SELECT * FROM guru ORDER BY nama_guru ASC");
    $data_guru = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Sistem Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Guru - SIS TKIT Fathurrobbany</title>
    <link rel="stylesheet" href="dashboard.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .search-wrapper { position: relative; width: 100%; max-width: 400px; margin-bottom: 20px;}
        .search-wrapper i { position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: #94a3b8; }
        .search-input { width: 100%; padding: 12px 16px 12px 45px; border-radius: 10px; border: 1px solid #e2e8f0; background: #ffffff; font-size: 14px; transition: 0.3s; box-sizing: border-box; outline: none; }
        .search-input:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1); }
        .action-icon { display: inline-flex; align-items: center; justify-content: center; width: 32px; height: 32px; border-radius: 8px; transition: 0.2s; text-decoration: none; margin-right: 5px;}
        .btn-edit { background: #eff6ff; color: #2563eb; }
        .btn-edit:hover { background: #bfdbfe; }
        .btn-delete { background: #fef2f2; color: #dc2626; }
        .btn-delete:hover { background: #fecaca; }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <main class="main-content">
        <div class="header-container">
            <div>
                <h1>Manajemen Data Guru</h1>
                <p style="margin: 0; color: #64748b; font-size: 14px;">Kelola profil, kontak, dan identitas tenaga pendidik.</p>
            </div>
            <a href="form_guru.php" class="btn-add-modern">
                <i class="fas fa-plus"></i> Tambah Guru Baru
            </a>
        </div>

        <?php if (isset($_GET['msg']) && $_GET['msg'] == 'terhapus'): ?>
            <div style="background:#dcfce7; color:#166534; padding:15px; border-radius:12px; margin-bottom:20px; font-weight:600; border: 1px solid #bbf7d0;">
                <i class="fas fa-check-circle"></i> Data guru berhasil dihapus.
            </div>
        <?php endif; ?>

        <div class="search-wrapper">
            <i class="fas fa-search"></i>
            <input type="text" id="searchInput" class="search-input" placeholder="Cari nama guru atau NIP...">
        </div>

        <div class="table-card">
            <table class="modern-table" id="dataTable">
                <thead>
                    <tr>
                        <th>ID / NIP</th>
                        <th>Nama Lengkap</th>
                        <th>No. Handphone</th>
                        <th>Email</th>
                        <th style="text-align: center;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($data_guru)): ?>
                        <tr><td colspan="5" style="text-align: center; padding: 40px; color: #94a3b8;">Belum ada data guru terdaftar.</td></tr>
                    <?php else: ?>
                        <?php foreach ($data_guru as $row): ?>
                        <tr>
                            <td>
                                <strong style="color: #1e293b; display: block;"><?= htmlspecialchars($row['id_guru']) ?></strong>
                                <span style="color: #64748b; font-size: 12px;"><?= htmlspecialchars($row['nip'] ?? '-') ?></span>
                            </td>
                            <td style="font-weight: 600; color: #0f172a;"><?= htmlspecialchars($row['nama_guru']) ?></td>
                            <td style="color: #475569;"><?= htmlspecialchars($row['no_hp'] ?? '-') ?></td>
                            <td style="color: #475569;"><?= htmlspecialchars($row['email'] ?? '-') ?></td>
                            <td style="text-align: center;">
                                <a href="form_guru.php?id=<?= $row['id_guru'] ?>" class="action-icon btn-edit" title="Edit"><i class="fas fa-edit"></i></a>
                                <a href="?hapus=<?= $row['id_guru'] ?>" class="action-icon btn-delete" title="Hapus" onclick="return confirm('Yakin ingin menghapus guru ini?');"><i class="fas fa-trash"></i></a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>

    <script>
        document.getElementById('searchInput').addEventListener('keyup', function() {
            let filter = this.value.toUpperCase();
            let rows = document.querySelector("#dataTable tbody").rows;
            for (let i = 0; i < rows.length; i++) {
                if(rows[i].cells.length > 1) { 
                    let col1 = rows[i].cells[0].textContent.toUpperCase();
                    let col2 = rows[i].cells[1].textContent.toUpperCase();
                    if (col1.indexOf(filter) > -1 || col2.indexOf(filter) > -1) {
                        rows[i].style.display = "";
                    } else {
                        rows[i].style.display = "none";
                    }
                }      
            }
        });
    </script>
</body>
</html>