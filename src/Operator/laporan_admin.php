<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'operator') {
    header("Location: ../auth/login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $judul = $_POST['judul'];
    $isi = $_POST['isi'];
    $id_user = $_SESSION['id_user'];
    
    $id_lap = 'LAP' . rand(100, 999);
    $stmt = $pdo->prepare("INSERT INTO laporan_administrasi (id_lap_admin, judul, isi, tanggal, id_user) VALUES (?, ?, ?, CURDATE(), ?)");
    $stmt->execute([$id_lap, $judul, $isi, $id_user]);
    header("Location: laporan_admin.php");
    exit;
}

$laporan_list = $pdo->query("SELECT * FROM laporan_administrasi ORDER BY tanggal DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Administrasi - Operator</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="dashboard.css">
    <style>
        .form-input, .form-textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: 14px;
            font-family: 'Inter', sans-serif;
            margin-bottom: 12px;
            outline: none;
        }
        .form-input:focus, .form-textarea:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(37,99,235,0.1); }
        .form-textarea { resize: vertical; min-height: 100px; }
        .btn-submit { background: #16a34a; color: white; padding: 12px 24px; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; font-size: 14px; transition: 0.2s;}
        .btn-submit:hover { background: #15803d; }
    </style>
</head>
<body>

    <div class="mobile-header">
        <div class="mobile-brand">
            <i class="fas fa-user-cog"></i> PANEL OPERATOR
        </div>
        <button class="hamburger-btn" onclick="toggleSidebar()">
            <i class="fas fa-bars"></i>
        </button>
    </div>
    <div class="sidebar-overlay" onclick="toggleSidebar()"></div>

    <?php include 'sidebar.php'; ?>

    <main class="main-content">
        <div class="header">
            <h1>Laporan Administrasi</h1>
            <p>Buat dan pantau riwayat pelaporan teknis sistem</p>
        </div>

        <div class="dashboard-grid">
            <div class="content-card">
                <div class="card-header"><i class="fas fa-plus-circle"></i> Buat Laporan Baru</div>
                <form method="POST">
                    <input type="text" name="judul" class="form-input" placeholder="Judul Laporan..." required>
                    <textarea name="isi" class="form-textarea" placeholder="Isi detail masalah atau catatan administrasi..." required></textarea>
                    <button type="submit" class="btn-submit" style="width: 100%;">
                        <i class="fas fa-paper-plane"></i> Simpan Laporan
                    </button>
                </form>
            </div>

            <div class="content-card">
                <div class="card-header"><i class="fas fa-history"></i> Riwayat Laporan Tersimpan</div>
                
                <?php if (empty($laporan_list)): ?>
                    <p style="text-align: center; color: #64748b; padding: 20px;">Belum ada riwayat laporan.</p>
                <?php else: ?>
                    <div style="overflow-x: auto;">
                        <table style="width: 100%; border-collapse: collapse; min-width: 400px;">
                            <thead>
                                <tr style="background: #f8fafc; border-bottom: 2px solid #e2e8f0; text-align: left;">
                                    <th style="padding: 14px; font-size: 12px; color: #64748b; text-transform: uppercase;">Tanggal</th>
                                    <th style="padding: 14px; font-size: 12px; color: #64748b; text-transform: uppercase;">Laporan</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($laporan_list as $lap): ?>
                                <tr style="border-bottom: 1px solid #e2e8f0;">
                                    <td style="padding: 14px; color: #64748b; font-size: 13px; white-space: nowrap;">
                                        <i class="far fa-calendar-alt"></i> <?= date('d/m/Y', strtotime($lap['tanggal'])) ?>
                                    </td>
                                    <td style="padding: 14px;">
                                        <strong style="color: #1e293b; display: block; margin-bottom: 4px;"><?= htmlspecialchars($lap['judul']) ?></strong>
                                        <span style="color: #64748b; font-size: 13px; line-height: 1.5;"><?= htmlspecialchars($lap['isi']) ?></span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <script>
        function toggleSidebar() {
            document.querySelector('.sidebar').classList.toggle('show');
            document.querySelector('.sidebar-overlay').classList.toggle('show');
        }
    </script>
</body>
</html>