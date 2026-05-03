<?php
session_start();
require_once '../config/db.php';

// PROTEKSI OTORISASI
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'orang_tua') {
    header("Location: ../auth/login.php");
    exit();
}

$id_user_login = $_SESSION['id_user'];
$bulan_pilihan = isset($_GET['bulan']) ? (int)$_GET['bulan'] : (int)date('n');
$tahun_pilihan = isset($_GET['tahun']) ? (int)$_GET['tahun'] : (int)date('Y');
$nama_bulan_arr = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

try {
    // 1. Cek apakah ada anak yang tertaut
    $stmtChild = $pdo->prepare("SELECT id_siswa, nama_siswa FROM siswa WHERE id_ortu = ? LIMIT 1");
    $stmtChild->execute([$id_user_login]);
    $data_anak = $stmtChild->fetch();

    $riwayat_absen = [];
    $rekap = ['Hadir' => 0, 'Sakit' => 0, 'Izin' => 0, 'Alpa' => 0];

    // 2. Jika ada anak, ambil histori absensinya
    if ($data_anak) {
        $id_siswa = $data_anak['id_siswa'];

        // Ambil riwayat absen berdasarkan filter bulan & tahun
        $stmtAbsen = $pdo->prepare("SELECT * FROM absensi WHERE id_siswa = ? AND MONTH(tanggal) = ? AND YEAR(tanggal) = ? ORDER BY tanggal DESC");
        $stmtAbsen->execute([$id_siswa, $bulan_pilihan, $tahun_pilihan]);
        $riwayat_absen = $stmtAbsen->fetchAll(PDO::FETCH_ASSOC);

        // Hitung Rekapitulasi Bulan Ini
        foreach ($riwayat_absen as $row) {
            if (isset($rekap[$row['status']])) {
                $rekap[$row['status']]++;
            }
        }
    }
} catch (PDOException $e) {
    die("Sistem Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Absensi Anak - SIS TKIT Fathurrobbany</title>
    <link rel="stylesheet" href="dashboard.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* KONSISTENSI TEMA BIRU SLATE */
        body { font-family: 'Inter', sans-serif; background-color: #f8fafc; color: #0f172a; margin: 0; }
        .main-content { padding: 30px 40px; margin-left: 260px; }

        /* Header dibuat transparan agar lega */
        .header-container { margin-bottom: 35px; }
        .header-title h1 { font-size: 28px; font-weight: 800; color: #1e293b; margin: 0 0 8px 0; letter-spacing: -0.5px;}
        .header-title p { margin: 0; color: #64748b; font-size: 15px; }

        /* Rekap Grid Premium */
        .rekap-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 24px; margin-bottom: 35px; }
        .rekap-card { background: white; padding: 25px 20px; border-radius: 20px; border: 1px solid #f1f5f9; display: flex; align-items: center; gap: 18px; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.02); transition: 0.3s; }
        .rekap-card:hover { transform: translateY(-4px); box-shadow: 0 15px 30px -5px rgba(0,0,0,0.05); }
        
        .rekap-icon { width: 55px; height: 55px; border-radius: 16px; display: flex; align-items: center; justify-content: center; font-size: 24px; flex-shrink: 0; }
        .rekap-info { flex: 1; }
        .rekap-label { font-size: 13px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px; }
        .rekap-nilai { font-size: 28px; font-weight: 800; color: #1e293b; line-height: 1; }

        .card-hadir .rekap-icon { background: #dcfce7; color: #10b981; }
        .card-sakit .rekap-icon { background: #fef3c7; color: #f59e0b; }
        .card-izin .rekap-icon { background: #eff6ff; color: #3b82f6; }
        .card-alpa .rekap-icon { background: #fee2e2; color: #ef4444; }

        /* Filter Section */
        .filter-wrapper { background: white; padding: 20px 25px; border-radius: 16px; border: 1px solid #e2e8f0; margin-bottom: 25px; display: flex; gap: 15px; align-items: flex-end; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); }
        .filter-group { display: flex; flex-direction: column; gap: 6px; }
        .filter-label { font-size: 12px; font-weight: 700; color: #475569; text-transform: uppercase; letter-spacing: 0.5px; }
        .input-modern { padding: 12px 16px; border-radius: 10px; border: 1px solid #cbd5e1; font-family: 'Inter', sans-serif; font-size: 14px; background: #f8fafc; outline: none; min-width: 140px; color: #1e293b; font-weight: 500; transition: 0.2s;}
        .input-modern:focus { border-color: #3b82f6; background: #fff; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1); }
        
        .btn-apply { padding: 12px 24px; background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); color: white; border: none; border-radius: 10px; font-weight: 600; font-size: 14px; cursor: pointer; transition: 0.3s; height: 44px; display: flex; align-items: center; gap: 8px; box-shadow: 0 4px 10px rgba(59, 130, 246, 0.3); }
        .btn-apply:hover { transform: translateY(-2px); box-shadow: 0 6px 15px rgba(59, 130, 246, 0.4); }

        /* Table Card */
        .table-card { background: white; border-radius: 20px; border: 1px solid #e2e8f0; overflow: hidden; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.02); }
        .table-header { padding: 20px 25px; border-bottom: 1px solid #e2e8f0; display: flex; align-items: center; gap: 10px; font-size: 16px; font-weight: 800; color: #1e293b; }
        
        .modern-table { width: 100%; border-collapse: collapse; }
        .modern-table th { padding: 16px 25px; text-align: left; background: #f8fafc; color: #64748b; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 2px solid #e2e8f0; }
        .modern-table td { padding: 16px 25px; border-bottom: 1px solid #f1f5f9; font-size: 14px; color: #334155; transition: 0.2s;}
        .modern-table tbody tr:hover td { background: #f8fafc; }
        
        /* Badges */
        .badge { padding: 6px 14px; border-radius: 20px; font-size: 12px; font-weight: 700; display: inline-flex; align-items: center; gap: 6px;}
        .badge-hadir { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0;}
        .badge-sakit { background: #fef3c7; color: #b45309; border: 1px solid #fde68a;}
        .badge-izin { background: #dbeafe; color: #1e40af; border: 1px solid #bfdbfe;}
        .badge-alpa { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca;}
    </style>
</head>
<body>

    <?php include 'sidebar.php'; ?>

    <main class="main-content">
        <div class="header-container">
            <div class="header-title">
                <h1>Absensi Kehadiran Anak</h1>
                <p>Pantau kedisiplinan dan riwayat kehadiran <strong><?= $data_anak ? htmlspecialchars($data_anak['nama_siswa']) : 'Ananda' ?></strong> di sekolah.</p>
            </div>
        </div>

        <?php if (!$data_anak): ?>
            <div style="background: #fef2f2; color: #991b1b; padding: 25px; border-radius: 16px; border: 1px solid #fecaca; text-align: center; max-width: 600px;">
                <i class="fas fa-link-slash" style="font-size: 40px; margin-bottom: 15px; color: #ef4444;"></i>
                <h3 style="margin: 0 0 10px 0;">Akun Belum Terhubung</h3>
                <p style="margin: 0; color: #b91c1c;">Silakan tautkan akun Anda dengan NIS murid di halaman Dashboard terlebih dahulu untuk melihat data absensi.</p>
            </div>
        <?php else: ?>

            <div class="rekap-grid">
                <div class="rekap-card card-hadir">
                    <div class="rekap-icon"><i class="fas fa-check-circle"></i></div>
                    <div class="rekap-info">
                        <div class="rekap-label">Total Hadir</div>
                        <div class="rekap-nilai"><?= $rekap['Hadir'] ?></div>
                    </div>
                </div>
                <div class="rekap-card card-sakit">
                    <div class="rekap-icon"><i class="fas fa-briefcase-medical"></i></div>
                    <div class="rekap-info">
                        <div class="rekap-label">Sakit</div>
                        <div class="rekap-nilai"><?= $rekap['Sakit'] ?></div>
                    </div>
                </div>
                <div class="rekap-card card-izin">
                    <div class="rekap-icon"><i class="fas fa-envelope-open-text"></i></div>
                    <div class="rekap-info">
                        <div class="rekap-label">Izin</div>
                        <div class="rekap-nilai"><?= $rekap['Izin'] ?></div>
                    </div>
                </div>
                <div class="rekap-card card-alpa">
                    <div class="rekap-icon"><i class="fas fa-times-circle"></i></div>
                    <div class="rekap-info">
                        <div class="rekap-label">Alpa</div>
                        <div class="rekap-nilai"><?= $rekap['Alpa'] ?></div>
                    </div>
                </div>
            </div>

            <form method="GET" class="filter-wrapper">
                <div class="filter-group">
                    <label class="filter-label">Filter Bulan</label>
                    <select name="bulan" class="input-modern">
                        <?php for($i=1; $i<=12; $i++): ?>
                            <option value="<?= $i ?>" <?= ($bulan_pilihan == $i) ? 'selected' : '' ?>><?= $nama_bulan_arr[$i] ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="filter-group" style="width: 120px;">
                    <label class="filter-label">Tahun</label>
                    <input type="number" name="tahun" value="<?= $tahun_pilihan ?>" class="input-modern" style="min-width: auto; width: 100%;">
                </div>
                <button type="submit" class="btn-apply"><i class="fas fa-search"></i> Tampilkan Data</button>
            </form>

            <div class="table-card">
                <div class="table-header">
                    <i class="fas fa-list-ul" style="color: #3b82f6;"></i> Riwayat Detail
                </div>
                <table class="modern-table">
                    <thead>
                        <tr>
                            <th style="width: 30%;">Hari, Tanggal</th>
                            <th style="width: 30%;">Status Kehadiran</th>
                            <th style="width: 40%;">Keterangan Tambahan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($riwayat_absen)): ?>
                            <tr>
                                <td colspan="3" style="text-align: center; padding: 50px; color: #94a3b8;">
                                    <i class="fas fa-folder-open" style="font-size: 30px; margin-bottom: 15px; display: block;"></i>
                                    Belum ada data absensi untuk periode ini.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($riwayat_absen as $absen): 
                                $tanggal_format = date('d M Y', strtotime($absen['tanggal']));
                                $hari = date('l', strtotime($absen['tanggal']));
                                
                                // Translate hari ke Bahasa Indonesia
                                $hari_indo = ['Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa', 'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu'];
                                $hari_final = $hari_indo[$hari];

                                $badge_class = 'badge-' . strtolower($absen['status']);
                                
                                // Set icon berdasarkan status
                                $status_icon = '';
                                if($absen['status'] == 'Hadir') $status_icon = '<i class="fas fa-check"></i>';
                                if($absen['status'] == 'Sakit') $status_icon = '<i class="fas fa-plus"></i>';
                                if($absen['status'] == 'Izin') $status_icon = '<i class="fas fa-envelope"></i>';
                                if($absen['status'] == 'Alpa') $status_icon = '<i class="fas fa-times"></i>';
                            ?>
                            <tr>
                                <td>
                                    <strong style="color: #1e293b; display: block;"><?= $hari_final ?></strong>
                                    <span style="color: #64748b; font-size: 13px;"><i class="far fa-calendar-alt" style="margin-right: 4px;"></i> <?= $tanggal_format ?></span>
                                </td>
                                <td><span class="badge <?= $badge_class ?>"><?= $status_icon ?> <?= htmlspecialchars($absen['status']) ?></span></td>
                                <td style="color: #475569; font-style: <?= empty($absen['keterangan']) ? 'italic' : 'normal' ?>;">
                                    <?= !empty($absen['keterangan']) ? htmlspecialchars($absen['keterangan']) : '<span style="color: #94a3b8;">Tidak ada catatan</span>' ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        <?php endif; ?>
    </main>

</body>
</html>