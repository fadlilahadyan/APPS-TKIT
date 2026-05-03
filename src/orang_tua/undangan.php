<?php
session_start();
require_once '../config/db.php';

// PROTEKSI OTORISASI
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'orang_tua') {
    header("Location: ../auth/login.php");
    exit();
}

$id_user = $_SESSION['id_user'];
$success_msg = null;
$error_msg = null;

// 1. LOGIKA RSVP (MENGUBAH STATUS DI DATABASE)
if (isset($_GET['rsvp']) && isset($_GET['id'])) {
    $id_undangan = (int)$_GET['id'];
    $rsvp_action = $_GET['rsvp'];
    
    $status_update = 'Belum Konfirmasi';
    if ($rsvp_action === 'hadir') {
        $status_update = 'Hadir';
    } elseif ($rsvp_action === 'absen') {
        $status_update = 'Tidak Hadir';
    }

    try {
        // Update data RSVP khusus untuk orang tua yang sedang login ini
        $stmtUpdate = $pdo->prepare("UPDATE undangan_rsvp SET status = ? WHERE id_undangan = ? AND id_ortu = ?");
        $stmtUpdate->execute([$status_update, $id_undangan, $id_user]);
        
        // Redirect pakai ?msg=success biar query string rsvp-nya hilang (mencegah double submit kalau di-refresh)
        header("Location: undangan.php?msg=success");
        exit();
    } catch (PDOException $e) {
        $error_msg = "Gagal menyimpan konfirmasi: " . $e->getMessage();
    }
}

// Tangkap pesan sukses setelah redirect
if (isset($_GET['msg']) && $_GET['msg'] == 'success') {
    $success_msg = "Terima kasih! Konfirmasi kehadiran Anda berhasil disimpan.";
}

// 2. TARIK DATA UNDANGAN DARI DATABASE (REAL DATA)
try {
    $stmtUndangan = $pdo->prepare("
        SELECT u.id_undangan as id, u.judul, u.tanggal, u.waktu, u.lokasi, u.deskripsi, r.status as status_rsvp
        FROM undangan u
        JOIN undangan_rsvp r ON u.id_undangan = r.id_undangan
        WHERE r.id_ortu = ?
        ORDER BY u.tanggal DESC
    ");
    $stmtUndangan->execute([$id_user]);
    $daftar_undangan = $stmtUndangan->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_msg = "Gagal memuat data undangan: " . $e->getMessage();
    $daftar_undangan = [];
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Undangan Acara - SIS TKIT Fathurrobbany</title>
    <!-- PERBAIKAN PATH CSS -->
    <link rel="stylesheet" href="dashboard.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Styling khusus halaman undangan */
        .header-container { margin-bottom: 35px; }
        .header-title h1 { font-size: 28px; font-weight: 800; color: #1e293b; margin: 0 0 8px 0; letter-spacing: -0.5px;}
        .header-title p { margin: 0; color: #64748b; font-size: 15px; }

        .undangan-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 25px; }
        
        .undangan-card { background: white; border-radius: 20px; border: 1px solid #e2e8f0; overflow: hidden; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.02); display: flex; flex-direction: column; transition: 0.3s; }
        .undangan-card:hover { transform: translateY(-5px); box-shadow: 0 20px 30px -10px rgba(0,0,0,0.08); }
        
        .card-header-img { background: linear-gradient(135deg, #065f46 0%, #10b981 100%); padding: 25px; position: relative; color: white; }
        .card-header-img::after { content: '\f2b6'; font-family: 'Font Awesome 6 Free'; font-weight: 900; position: absolute; right: 20px; top: 15px; font-size: 60px; opacity: 0.1; }
        
        .tanggal-badge { background: rgba(255,255,255,0.2); backdrop-filter: blur(5px); padding: 8px 15px; border-radius: 12px; display: inline-flex; flex-direction: column; align-items: center; justify-content: center; border: 1px solid rgba(255,255,255,0.3); }
        .tanggal-badge .hari { font-size: 24px; font-weight: 800; line-height: 1; }
        .tanggal-badge .bulan { font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; }

        .card-body { padding: 25px; flex: 1; display: flex; flex-direction: column; }
        .undangan-title { font-size: 18px; font-weight: 800; color: #1e293b; margin: 0 0 15px 0; line-height: 1.4; }
        
        .info-list { display: flex; flex-direction: column; gap: 10px; margin-bottom: 20px; }
        .info-item { display: flex; align-items: flex-start; gap: 12px; font-size: 14px; color: #475569; }
        .info-item i { margin-top: 3px; color: #10b981; width: 16px; text-align: center; }

        .rsvp-section { margin-top: auto; padding-top: 20px; border-top: 1px dashed #cbd5e1; }
        .rsvp-status { font-size: 12px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 10px; display: block; }
        
        .btn-group { display: flex; gap: 10px; }
        .btn-rsvp { flex: 1; padding: 12px; border-radius: 10px; font-weight: 700; font-size: 13px; text-align: center; cursor: pointer; transition: 0.2s; text-decoration: none; border: none; display: flex; justify-content: center; align-items: center; gap: 6px;}
        .btn-hadir { background: #10b981; color: white; }
        .btn-hadir:hover { background: #059669; }
        .btn-absen { background: #fef2f2; color: #ef4444; border: 1px solid #fecaca; }
        .btn-absen:hover { background: #fee2e2; }
        
        .status-badge { padding: 10px; border-radius: 10px; font-weight: 700; font-size: 13px; text-align: center; width: 100%; box-sizing: border-box; display: flex; justify-content: center; align-items: center; gap: 6px;}
        .status-hadir { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .status-absen { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }

        @media (max-width: 768px) {
            .undangan-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

    <?php include 'sidebar.php'; ?>

    <main class="main-content">
        <div class="header-container">
            <div class="header-title">
                <h1>Undangan & Acara Sekolah</h1>
                <p>Informasi kegiatan penting dan konfirmasi kehadiran Bapak/Ibu.</p>
            </div>
        </div>

        <?php if($success_msg): ?>
            <div style="background: #dcfce7; color: #166534; padding: 16px 20px; border-radius: 12px; margin-bottom: 25px; border: 1px solid #bbf7d0; font-weight: 600; display: flex; align-items: center; gap: 12px;">
                <i class="fas fa-check-circle" style="font-size: 20px;"></i> <?= htmlspecialchars($success_msg) ?>
            </div>
        <?php endif; ?>

        <?php if($error_msg): ?>
            <div style="background: #fef2f2; color: #991b1b; padding: 16px 20px; border-radius: 12px; margin-bottom: 25px; border: 1px solid #fecaca; font-weight: 600; display: flex; align-items: center; gap: 12px;">
                <i class="fas fa-exclamation-circle" style="font-size: 20px;"></i> <?= htmlspecialchars($error_msg) ?>
            </div>
        <?php endif; ?>

        <div class="undangan-grid">
            <?php if (empty($daftar_undangan)): ?>
                <!-- Tampilan Empty State jika belum ada undangan dari Guru -->
                <div style="grid-column: 1 / -1; text-align: center; padding: 60px 20px; background: white; border-radius: 20px; border: 1px solid #e2e8f0;">
                    <i class="fas fa-envelope-open" style="font-size: 50px; color: #cbd5e1; margin-bottom: 15px;"></i>
                    <h3 style="color: #1e293b; margin-bottom: 5px;">Belum Ada Undangan</h3>
                    <p style="color: #64748b; font-size: 14px;">Saat ini belum ada jadwal acara atau pertemuan Wali Murid.</p>
                </div>
            <?php else: ?>
                <?php foreach ($daftar_undangan as $undangan): 
                    $timestamp = strtotime($undangan['tanggal']);
                    $tgl = date('d', $timestamp);
                    $bln = date('M', $timestamp);
                ?>
                <div class="undangan-card">
                    <div class="card-header-img">
                        <div class="tanggal-badge">
                            <span class="hari"><?= $tgl ?></span>
                            <span class="bulan"><?= $bln ?></span>
                        </div>
                    </div>
                    
                    <div class="card-body">
                        <h3 class="undangan-title"><?= htmlspecialchars($undangan['judul']) ?></h3>
                        
                        <div class="info-list">
                            <div class="info-item">
                                <i class="far fa-clock"></i>
                                <span><?= htmlspecialchars($undangan['waktu']) ?></span>
                            </div>
                            <div class="info-item">
                                <i class="fas fa-map-marker-alt"></i>
                                <span><?= htmlspecialchars($undangan['lokasi']) ?></span>
                            </div>
                            <div class="info-item" style="margin-top: 5px;">
                                <i class="fas fa-info-circle"></i>
                                <span style="line-height: 1.5;"><?= htmlspecialchars($undangan['deskripsi']) ?></span>
                            </div>
                        </div>

                        <div class="rsvp-section">
                            <span class="rsvp-status">Konfirmasi Kehadiran (RSVP)</span>
                            
                            <?php if ($undangan['status_rsvp'] == 'Belum Konfirmasi' || empty($undangan['status_rsvp'])): ?>
                                <div class="btn-group">
                                    <a href="?rsvp=hadir&id=<?= $undangan['id'] ?>" class="btn-rsvp btn-hadir"><i class="fas fa-check"></i> Saya Akan Hadir</a>
                                    <a href="?rsvp=absen&id=<?= $undangan['id'] ?>" class="btn-rsvp btn-absen"><i class="fas fa-times"></i> Berhalangan</a>
                                </div>
                            <?php elseif ($undangan['status_rsvp'] == 'Hadir'): ?>
                                <div class="status-badge status-hadir">
                                    <i class="fas fa-check-circle"></i> Anda telah mengonfirmasi Hadir
                                </div>
                            <?php else: ?>
                                <div class="status-badge status-absen">
                                    <i class="fas fa-times-circle"></i> Anda mengonfirmasi Berhalangan
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>

</body>
</html>