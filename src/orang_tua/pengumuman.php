<?php
session_start();
require_once '../config/db.php';
date_default_timezone_set('Asia/Jakarta');

// PROTEKSI: Pastikan hanya Orang Tua yang bisa akses[cite: 6]
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'orang_tua') {
    header("Location: ../auth/login.php");
    exit();
}

try {
    // AMBIL DATA: Filter target audiens untuk Orang Tua atau Semua[cite: 6]
    $stmt = $pdo->prepare("SELECT * FROM pengumuman 
                           WHERE target_audiens = 'Orang Tua' OR target_audiens = 'Semua' 
                           ORDER BY tanggal DESC, id_pengumuman DESC");
    $stmt->execute();
    $pengumuman = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Array nama bulan Indonesia[cite: 6]
    $nama_bulan_arr = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

} catch (PDOException $e) {
    die("Sistem Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengumuman - SIS TKIT FATHUROBANIrobbany</title>
    <link rel="stylesheet" href="dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        /* Modern News Grid Styling[cite: 6] */
        .news-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 25px; margin-top: 20px; }
        .news-card { background: #ffffff; border-radius: 16px; border: 1px solid #e2e8f0; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); transition: all 0.3s ease; display: flex; flex-direction: column; height: 100%; }
        .news-card:hover { transform: translateY(-5px); box-shadow: 0 15px 30px -5px rgba(0,0,0,0.1); border-color: #cbd5e1; }
        .news-image { width: 100%; height: 180px; object-fit: cover; background: #f1f5f9; border-bottom: 1px solid #f1f5f9; }
        .news-image-placeholder { width: 100%; height: 180px; background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%); display: flex; align-items: center; justify-content: center; color: #93c5fd; font-size: 50px; border-bottom: 1px solid #e2e8f0; }
        .news-content { padding: 20px; display: flex; flex-direction: column; flex-grow: 1; }
        .news-meta { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; font-size: 12px; font-weight: 600; }
        .badge { padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; display: inline-flex; align-items: center; gap: 4px; }
        .badge-tinggi { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }
        .badge-sedang { background: #eff6ff; color: #2563eb; border: 1px solid #bfdbfe; }
        .badge-rendah { background: #f8fafc; color: #475569; border: 1px solid #e2e8f0; }
        .news-title { font-size: 18px; font-weight: 700; color: #0f172a; margin: 0 0 10px 0; line-height: 1.4; }
        .news-body { font-size: 14px; color: #475569; line-height: 1.6; margin: 0; flex-grow: 1; }
    </style>
</head>
<body>
    <!-- SIDEBAR LENGKAP[cite: 5, 6] -->
     <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <div class="header" style="margin-bottom: 20px;">
            <h1>Pengumuman Sekolah</h1>
            <p>Pusat informasi dan pembaruan terbaru dari TKIT Fathurrobbany</p>
        </div>

        <?php if (empty($pengumuman)): ?>
            <div style="text-align: center; padding: 60px 20px; background: #f8fafc; border: 2px dashed #cbd5e1; border-radius: 16px;">
                <i class="fas fa-bell-slash" style="font-size: 35px; color: #3b82f6; margin-bottom: 20px;"></i>
                <h3 style="color: #0f172a; font-weight: 700;">Belum Ada Informasi</h3>
            </div>
        <?php else: ?>
            <div class="news-grid">
                <?php foreach ($pengumuman as $item): ?>
                    <?php 
                        // Format Tanggal[cite: 6]
                        $tgl_parts = explode('-', $item['tanggal']);
                        $tgl_format = $tgl_parts[2] . ' ' . $nama_bulan_arr[(int)$tgl_parts[1]] . ' ' . $tgl_parts[0];

                        // Badge Prioritas[cite: 6]
                        $prio = strtolower($item['prioritas'] ?? 'sedang');
                        $badge_class = ($prio === 'tinggi') ? 'badge-tinggi' : (($prio === 'rendah') ? 'badge-rendah' : 'badge-sedang');
                        $label = ($prio === 'tinggi') ? 'Penting' : (($prio === 'rendah') ? 'Info' : 'Umum');
                        $icon = ($prio === 'tinggi') ? 'fa-exclamation-circle' : (($prio === 'rendah') ? 'fa-info-circle' : 'fa-bell');
                    ?>
                    
                    <div class="news-card">
                        <!-- TAMPILAN GAMBAR DENGAN PATH FIX[cite: 6] -->
                        <?php if (!empty($item['gambar'])): ?>
                            <img src="../assets/<?= htmlspecialchars($item['gambar']); ?>" alt="Gambar Pengumuman" class="news-image" onerror="this.src='https://via.placeholder.com/400x200?text=Gambar+Tidak+Ditemukan'">
                        <?php else: ?>
                            <div class="news-image-placeholder">
                                <i class="fas fa-newspaper"></i>
                            </div>
                        <?php endif; ?>

                        <div class="news-content">
                            <div class="news-meta">
                                <span class="badge <?= $badge_class ?>">
                                    <i class="fas <?= $icon ?>"></i> <?= $label ?>
                                </span>
                                <span style="color: #64748b;"><i class="fas fa-calendar-alt"></i> <?= $tgl_format ?></span>
                            </div>
                            <h2 class="news-title"><?= htmlspecialchars($item['judul']); ?></h2>
                            <p class="news-body"><?= nl2br(htmlspecialchars($item['isi'])); ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>