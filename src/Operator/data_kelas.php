<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'operator') {
    header("Location: ../auth/login.php");
    exit();
}

if (isset($_GET['hapus'])) {
    try {
        $stmt = $pdo->prepare("DELETE FROM kelas WHERE id_kelas = ?");
        $stmt->execute([$_GET['hapus']]);
        header("Location: data_kelas.php?msg=terhapus");
        exit();
    } catch (PDOException $e) {
        $error = "Gagal menghapus: " . $e->getMessage();
    }
}

try {
    $sql = "SELECT k.*, g.nama_guru 
            FROM kelas k 
            LEFT JOIN guru g ON k.id_guru = g.id_guru 
            ORDER BY k.nama_kelas ASC";
    $stmt = $pdo->query($sql);
    $data_kelas = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Sistem Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Kelas - SIS TKIT Fathurrobbany</title>
    <link rel="stylesheet" href="dashboard.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .search-wrapper { position: relative; width: 100%; max-width: 400px; margin-bottom: 20px;}
        .search-wrapper i { position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: #94a3b8; }
        .search-input { width: 100%; padding: 12px 16px 12px 45px; border-radius: 10px; border: 1px solid #e2e8f0; background: #ffffff; font-size: 14px; transition: 0.3s; box-sizing: border-box; outline: none; }
        .search-input:focus { border-color: #10b981; box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1); }
        .action-icon { display: inline-flex; align-items: center; justify-content: center; width: 32px; height: 32px; border-radius: 8px; transition: 0.2s; text-decoration: none; margin-right: 5px;}
        .btn-edit { background: #eff6ff; color: #2563eb; }
        .btn-edit:hover { background: #bfdbfe; }
        .btn-delete { background: #fef2f2; color: #dc2626; }
        .btn-delete:hover { background: #fecaca; }
        .badge-tingkat { background: #f0fdf4; color: #166534; padding: 4px 10px; border-radius: 8px; font-size: 12px; font-weight: 700; border: 1px solid #bbf7d0; }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <main class="main-content">
        <div class="header-container">
            <div>
                <h1>Manajemen Rombongan Belajar</h1>
                <p style="margin: 0; color: #64748b; font-size: 14px;">Atur penempatan ruangan dan wali kelas.</p>
            </div>
            <a href="form_kelas.php" class="btn-add-modern" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);">
                <i class="fas fa-plus"></i> Tambah Kelas
            </a>
        </div>

        <?php if (isset($_GET['msg']) && $_GET['msg'] == 'terhapus'): ?>
            <div style="background:#dcfce7; color:#166534; padding:15px; border-radius:12px; margin-bottom:20px; font-weight:600; border: 1px solid #bbf7d0;">
                <i class="fas fa-check-circle"></i> Data kelas berhasil dihapus.
            </div>
        <?php endif; ?>

        <div class="search-wrapper">
            <i class="fas fa-search"></i>
            <input type="text" id="searchInput" class="search-input" placeholder="Cari nama kelas atau wali kelas...">
        </div>

        <div class="table-card">
            <table class="modern-table" id="dataTable">
                <thead>
                    <tr>
                        <th>Kode Kelas</th>
                        <th>Nama Kelas</th>
                        <th>Tingkat</th>
                        <th>Wali Kelas</th>
                        <th style="text-align: center;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($data_kelas)): ?>
                        <tr><td colspan="5" style="text-align: center; padding: 40px; color: #94a3b8;">Belum ada data kelas terdaftar.</td></tr>
                    <?php else: ?>
                        <?php foreach ($data_kelas as $row): ?>
                        <tr>
                            <td style="color: #64748b; font-weight: 600;"><?= htmlspecialchars($row['id_kelas']) ?></td>
                            <td style="font-weight: 700; color: #0f172a;"><?= htmlspecialchars($row['nama_kelas']) ?></td>
                            <td><span class="badge-tingkat"><?= htmlspecialchars($row['tingkat'] ?? 'TK') ?></span></td>
                            <td style="color: #475569;">
                                <i class="fas fa-user-tie" style="color: #94a3b8; margin-right: 5px;"></i> 
                                <?= htmlspecialchars($row['nama_guru'] ?? 'Belum Ditentukan') ?>
                            </td>
                            <td style="text-align: center;">
                                <a href="form_kelas.php?id=<?= $row['id_kelas'] ?>" class="action-icon btn-edit" title="Edit"><i class="fas fa-edit"></i></a>
                                <a href="?hapus=<?= $row['id_kelas'] ?>" class="action-icon btn-delete" title="Hapus" onclick="return confirm('Yakin ingin menghapus kelas ini? Menghapus kelas akan berdampak pada data siswa yang terhubung.');"><i class="fas fa-trash"></i></a>
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
                    let col1 = rows[i].cells[1].textContent.toUpperCase();
                    let col2 = rows[i].cells[3].textContent.toUpperCase();
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