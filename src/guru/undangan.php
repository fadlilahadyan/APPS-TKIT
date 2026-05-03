<?php
session_start();
require_once '../config/db.php';

// PROTEKSI OTORISASI GURU
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'guru') {
    header("Location: ../auth/login.php");
    exit();
}

// TARIK DATA SEMUA UNDANGAN YANG PERNAH DIBUAT GURU
try {
    $stmt = $pdo->query("SELECT * FROM undangan ORDER BY tanggal DESC");
    $daftar_undangan = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Gagal mengambil data: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Undangan - SIS TKIT Fathurrobbany</title>
    <link rel="stylesheet" href="dashboard.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .header-flex { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .btn-add { background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); color: white; padding: 12px 20px; border-radius: 12px; text-decoration: none; font-weight: 700; font-size: 14px; display: flex; align-items: center; gap: 8px; box-shadow: 0 4px 10px rgba(37, 99, 235, 0.2); transition: 0.3s; }
        .btn-add:hover { transform: translateY(-2px); box-shadow: 0 6px 15px rgba(37, 99, 235, 0.3); }
        
        .card-undangan { background: white; border-radius: 20px; border: 1px solid #e2e8f0; padding: 25px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; transition: 0.3s; }
        .card-undangan:hover { border-color: #3b82f6; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.05); }
        
        .info-acara h3 { margin: 0 0 10px 0; color: #1e293b; font-size: 18px; font-weight: 800; }
        .meta-detail { display: flex; gap: 20px; color: #64748b; font-size: 13px; font-weight: 600; }
        .meta-detail span { display: flex; align-items: center; gap: 6px; }
        
        .rsvp-stats { display: flex; gap: 10px; }
        .badge-rsvp { padding: 8px 15px; border-radius: 10px; font-size: 12px; font-weight: 700; text-align: center; min-width: 80px; }
        .hadir { background: #dcfce7; color: #166534; }
        .tidak { background: #fee2e2; color: #991b1b; }
        .pending { background: #fef9c3; color: #854d0e; }
    </style>
</head>
<body>

    <?php include 'sidebar.php'; ?>

    <main class="main-content">
        <div class="header-flex">
            <div>
                <h1 style="margin:0; font-size: 26px; font-weight: 800; color: #1e293b;">Daftar Undangan Acara</h1>
                <p style="margin:5px 0 0 0; color: #64748b; font-size: 14px;">Pantau konfirmasi kehadiran orang tua murid secara real-time.</p>
            </div>
            <a href="form_undangan.php" class="btn-add">
                <i class="fas fa-plus-circle"></i> Buat Undangan Baru
            </a>
        </div>

        <?php if(isset($_GET['msg']) && $_GET['msg'] == 'sukses'): ?>
            <div style="background: #ecfdf5; color: #065f46; padding: 15px 20px; border-radius: 12px; margin-bottom: 25px; border: 1px solid #a7f3d0; font-weight: 600; display: flex; align-items: center; gap: 12px;">
                <i class="fas fa-check-circle" style="font-size: 20px;"></i> Undangan berhasil disebarkan ke semua Wali Murid!
            </div>
        <?php endif; ?>

        <div class="list-container">
            <?php if(empty($daftar_undangan)): ?>
                <div style="text-align: center; padding: 50px; background: white; border-radius: 20px; border: 1px dashed #cbd5e1;">
                    <i class="fas fa-calendar-times" style="font-size: 40px; color: #cbd5e1; margin-bottom: 15px;"></i>
                    <p style="color: #64748b; font-weight: 600;">Belum ada undangan yang dibuat.</p>
                </div>
            <?php else: ?>
                <?php foreach($daftar_undangan as $u): 
                    // Hitung jumlah RSVP otomatis
                    $stmtStat = $pdo->prepare("SELECT 
                        SUM(CASE WHEN status = 'Hadir' THEN 1 ELSE 0 END) as total_hadir,
                        SUM(CASE WHEN status = 'Tidak Hadir' THEN 1 ELSE 0 END) as total_absen,
                        SUM(CASE WHEN status = 'Belum Konfirmasi' THEN 1 ELSE 0 END) as total_pending
                        FROM undangan_rsvp WHERE id_undangan = ?");
                    $stmtStat->execute([$u['id_undangan']]);
                    $stat = $stmtStat->fetch();
                ?>
                <div class="card-undangan">
                    <div class="info-acara">
                        <h3><?= htmlspecialchars($u['judul']) ?></h3>
                        <div class="meta-detail">
                            <span><i class="far fa-calendar-alt"></i> <?= date('d M Y', strtotime($u['tanggal'])) ?></span>
                            <span><i class="far fa-clock"></i> <?= htmlspecialchars($u['waktu']) ?></span>
                            <span><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($u['lokasi']) ?></span>
                        </div>
                    </div>
                    
                    <div class="rsvp-stats">
                        <div class="badge-rsvp hadir">
                            <div style="font-size: 10px; opacity: 0.8; margin-bottom: 2px;">HADIR</div>
                            <?= $stat['total_hadir'] ?>
                        </div>
                        <div class="badge-rsvp tidak">
                            <div style="font-size: 10px; opacity: 0.8; margin-bottom: 2px;">ABSEN</div>
                            <?= $stat['total_absen'] ?>
                        </div>
                        <div class="badge-rsvp pending">
                            <div style="font-size: 10px; opacity: 0.8; margin-bottom: 2px;">PENDING</div>
                            <?= $stat['total_pending'] ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>

</body>
</html>