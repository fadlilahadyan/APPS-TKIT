<?php
session_start();
// PERBAIKAN PATH DATABASE: Mundur 1 folder ke atas '../' karena file ini ada di dalam folder 'guru/'
require_once '../config/db.php';

// PROTEKSI OTORISASI GURU
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'guru') {
    header("Location: ../auth/login.php");
    exit();
}

$error_msg = null; // Inisialisasi variabel error_msg biar gak undefined

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $judul = $_POST['judul'];
    $tanggal = $_POST['tanggal'];
    $waktu = $_POST['waktu'];
    $lokasi = $_POST['lokasi'];
    $deskripsi = $_POST['deskripsi'];
    $id_guru = $_SESSION['id_user'];

    try {
        $pdo->beginTransaction();

        // 1. Simpan Master Undangannya
        $stmt = $pdo->prepare("INSERT INTO undangan (judul, tanggal, waktu, lokasi, deskripsi, id_guru) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$judul, $tanggal, $waktu, $lokasi, $deskripsi, $id_guru]);
        
        $id_undangan_baru = $pdo->lastInsertId();

        // 2. Tarik semua data akun Orang Tua
        // Asumsi: tabel user kamu namanya 'users' dan rolenya 'orang_tua' atau 'Orang Tua'
        $stmtOrtu = $pdo->query("SELECT id_user FROM users WHERE role = 'orang_tua' OR role = 'Orang Tua'");
        $ortu_list = $stmtOrtu->fetchAll(PDO::FETCH_ASSOC);

        // 3. Generate data RSVP "Belum Konfirmasi" buat semua ortu
        if (!empty($ortu_list)) {
            $queryRsvp = "INSERT INTO undangan_rsvp (id_undangan, id_ortu, status) VALUES ";
            $values = [];
            $params = [];
            
            foreach($ortu_list as $o) {
                $values[] = "(?, ?, 'Belum Konfirmasi')";
                $params[] = $id_undangan_baru;
                $params[] = $o['id_user'];
            }
            
            $stmtRsvp = $pdo->prepare($queryRsvp . implode(', ', $values));
            $stmtRsvp->execute($params);
        }

        $pdo->commit();
        header("Location: undangan.php?msg=sukses");
        exit();

    } catch (PDOException $e) {
        $pdo->rollBack();
        $error_msg = "Gagal menyebarkan undangan: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buat Undangan - SIS TKIT Fathurrobbany</title>
    <link rel="stylesheet" href="dashboard.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Sisa CSS khusus layout form yang tidak tercover di dashboard.css */
        .back-link { display: inline-flex; align-items: center; gap: 8px; color: #64748b; text-decoration: none; font-size: 14px; font-weight: 600; margin-bottom: 20px; transition: 0.2s; background: white; padding: 10px 20px; border-radius: 12px; border: 1px solid #e2e8f0; }
        .back-link:hover { color: #1e40af; transform: translateX(-3px); border-color: #cbd5e1; background: #f8fafc;}
        
        .form-card { background: white; border-radius: 20px; padding: 35px; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.02); border: 1px solid #f1f5f9; max-width: 800px; }
        .form-title { font-size: 22px; font-weight: 800; color: #1e293b; margin: 0 0 5px 0; border-bottom: 2px dashed #e2e8f0; padding-bottom: 15px; margin-bottom: 25px;}
        
        .form-group { margin-bottom: 20px; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .form-label { display: block; font-size: 13px; font-weight: 700; color: #475569; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px; }
        
        .input-modern { width: 100%; padding: 14px 16px; border-radius: 12px; border: 1px solid #cbd5e1; font-family: 'Inter', sans-serif; font-size: 14px; background: #f8fafc; transition: 0.3s; box-sizing: border-box; outline: none; color: #1e293b;}
        .input-modern:focus { border-color: #3b82f6; background: #fff; box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1); }
        textarea.input-modern { resize: vertical; min-height: 120px; }

        .btn-submit { width: 100%; padding: 16px; background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; border: none; border-radius: 12px; font-weight: 800; font-size: 15px; cursor: pointer; transition: 0.3s; box-shadow: 0 4px 10px rgba(16, 185, 129, 0.3); display: flex; align-items: center; justify-content: center; gap: 8px; margin-top: 15px;}
        .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 6px 15px rgba(16, 185, 129, 0.4); }

        /* RESPONSIVE KHUSUS FORM INI */
        @media screen and (max-width: 768px) {
            .form-row { grid-template-columns: 1fr; gap: 0; } /* Jadikan 1 kolom di layar HP */
            .form-card { padding: 25px 20px; }
        }
    </style>
</head>
<body>

    <!-- PEMANGGILAN SIDEBAR PHP -->
    <?php include 'sidebar.php'; ?>

    <main class="main-content">
        <a href="undangan.php" class="back-link"><i class="fas fa-arrow-left"></i> Kembali ke Daftar Undangan</a>
        
        <div class="form-card">
            <h2 class="form-title">Buat Undangan Baru</h2>
            
            <?php if($error_msg): ?>
                <div style="background:#fef2f2; color:#991b1b; padding:15px; border-radius:12px; margin-bottom:20px; font-weight:600; border: 1px solid #fecaca; display: flex; align-items: center; gap: 10px;">
                    <i class="fas fa-exclamation-circle" style="font-size: 20px;"></i> <?= htmlspecialchars($error_msg) ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label class="form-label">Judul Acara</label>
                    <input type="text" name="judul" class="input-modern" placeholder="Contoh: Rapat Wali Murid & Pembagian Raport" required>
                </div>

                <!-- Bagian form-row ini otomatis jadi 1 baris ke bawah kalau dibuka di HP berkat CSS @media -->
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Tanggal Pelaksanaan</label>
                        <input type="date" name="tanggal" class="input-modern" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Waktu / Jam</label>
                        <input type="text" name="waktu" class="input-modern" placeholder="Contoh: 08:00 - 11:30 WIB" required>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Lokasi Acara</label>
                    <input type="text" name="lokasi" class="input-modern" placeholder="Contoh: Aula TKIT / Zoom Meeting" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Deskripsi & Catatan Tambahan</label>
                    <textarea name="deskripsi" class="input-modern" placeholder="Tuliskan tujuan acara, dresscode, atau catatan penting lainnya untuk orang tua..." required></textarea>
                </div>

                <button type="submit" class="btn-submit">
                    <i class="fas fa-paper-plane"></i> Simpan & Sebarkan ke Wali Murid
                </button>
            </form>
        </div>
    </main>

</body>
</html>