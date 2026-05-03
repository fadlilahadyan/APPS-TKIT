<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'kepala_sekolah') {
    header("Location: ../auth/login.php");
    exit();
}

// Ambil rekam jejak input laporan perkembangan sebagai aktivitas guru
$query_laporan = "
    SELECT p.tanggal, s.nama_siswa, p.aspek as detail, u.nama_lengkap as nama_guru
    FROM perkembangan p
    JOIN siswa s ON p.id_siswa = s.id_siswa
    JOIN users u ON p.id_guru = u.id_user
    ORDER BY p.tanggal DESC, p.id_laporan DESC LIMIT 20
";
$aktifitas = $pdo->query($query_laporan)->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aktivitas Guru - TKIT FATHUROBANI</title>
    <link rel="stylesheet" href="dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <div class="header">
            <h1>👨‍🏫 Aktivitas Guru</h1>
            <p>Pantau rekam jejak kinerja dan laporan yang diinput oleh Guru (Real-time).</p>
        </div>

        <div class="content-card">
            <div class="card-header">Rekam Jejak Operasional</div>
            <div style="overflow-x: auto; margin-top: 15px;">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="text-align: left; background: #f8fafc; border-bottom: 2px solid #e2e8f0;">
                            <th style="padding: 12px; width: 15%;">Waktu/Tanggal</th>
                            <th style="padding: 12px; width: 25%;">Nama Guru</th>
                            <th style="padding: 12px; width: 20%;">Jenis Aktivitas</th>
                            <th style="padding: 12px; width: 40%;">Detail Output</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($aktifitas)): ?>
                            <tr>
                                <td colspan="4" style="text-align: center; padding: 30px; color: #94a3b8;">
                                    Belum ada jejak aktivitas guru di database.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach($aktifitas as $a): ?>
                            <tr style="border-bottom: 1px solid #f1f5f9;">
                                <td style="padding: 12px; color: #64748b; font-size:14px;"><?= date('d M Y', strtotime($a['tanggal'])) ?></td>
                                <td style="padding: 12px; font-weight: 600; color:#1e293b;"><i class="fas fa-chalkboard-teacher" style="color:#2563eb; margin-right:5px;"></i> <?= htmlspecialchars($a['nama_guru']) ?></td>
                                <td style="padding: 12px;"><span style="background:#f0fdf4; color:#166534; padding:4px 8px; border-radius:4px; font-size:12px; font-weight:700;">Input Perkembangan</span></td>
                                <td style="padding: 12px; font-size:14px;">Mengisi aspek <b><?= htmlspecialchars($a['detail']) ?></b> untuk ananda <?= htmlspecialchars($a['nama_siswa']) ?></td>
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