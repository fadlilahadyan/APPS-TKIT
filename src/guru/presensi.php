<?php
// guru/presensi.php
session_start();
// Mundur satu folder untuk panggil koneksi database
require_once '../config/db.php'; 

// Cek login guru
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'guru') {
    header("Location: ../auth/login.php"); 
    exit();
}

$id_user = $_SESSION['id_user'];
$email = $_SESSION['username'];

// CEK DATA GURU
$stmt_guru = $pdo->prepare("SELECT * FROM guru WHERE email = ?");
$stmt_guru->execute([$email]);
$guru = $stmt_guru->fetch();

// Jika data guru belum ada, buat baru
if (!$guru) {
    $id_guru = 'G' . rand(100, 999);
    $nama_lengkap = $_SESSION['nama_lengkap'] ?? 'Guru';
    $stmt_insert = $pdo->prepare("INSERT INTO guru (id_guru, nama_guru, email) VALUES (?, ?, ?)");
    $stmt_insert->execute([$id_guru, $nama_lengkap, $email]);
    $guru = ['id_guru' => $id_guru];
}

// Proses simpan presensi
if (isset($_POST['simpan'])) {
    $tanggal = $_POST['tanggal'];
    
    if (isset($_POST['status']) && is_array($_POST['status'])) {
        $pdo->beginTransaction();
        try {
            foreach ($_POST['status'] as $id_siswa => $status) {
                $catatan = $_POST['catatan'][$id_siswa] ?? '';
                
                $stmt_cek = $pdo->prepare("SELECT id_absen FROM absensi WHERE id_siswa = ? AND tanggal = ?");
                $stmt_cek->execute([$id_siswa, $tanggal]);
                $cek = $stmt_cek->fetch();
                
                if ($cek) {
                    $stmt_update = $pdo->prepare("UPDATE absensi SET status = ?, catatan = ?, input_by = ? WHERE id_absen = ?");
                    $stmt_update->execute([$status, $catatan, $guru['id_guru'], $cek['id_absen']]);
                } else {
                    // Penyesuaian nama field di insert sesuai dengan kolom database lu (misal Alpha jadi Alpa)
                    $status_db = ($status == 'Alpha') ? 'Alpa' : $status;
                    $id_absen = 'ABS' . rand(100, 999);
                    $stmt_insert_absen = $pdo->prepare("INSERT INTO absensi (id_absen, id_siswa, tanggal, status, catatan, input_by) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt_insert_absen->execute([$id_absen, $id_siswa, $tanggal, $status_db, $catatan, $guru['id_guru']]);
                }
            }
            $pdo->commit();
            $success = "Data presensi untuk tanggal " . date('d M Y', strtotime($tanggal)) . " berhasil disimpan!";
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Terjadi kesalahan: " . $e->getMessage();
        }
    }
}

// Filter hanya berdasarkan Tanggal
$selected_tanggal = $_GET['tanggal'] ?? date('Y-m-d');
$presensi_exists = [];

// LANGSUNG AMBIL SEMUA DATA SISWA AKTIF
$stmt_siswa = $pdo->prepare("SELECT * FROM siswa WHERE status = 'Aktif' ORDER BY nama_siswa ASC");
$stmt_siswa->execute();
$siswa = $stmt_siswa->fetchAll(PDO::FETCH_ASSOC);

// Ambil data absensi yang sudah ada di tanggal yang dipilih
foreach ($siswa as $s) {
    $stmt_abs = $pdo->prepare("SELECT * FROM absensi WHERE id_siswa = ? AND tanggal = ?");
    $stmt_abs->execute([$s['id_siswa'], $selected_tanggal]);
    $abs = $stmt_abs->fetch(PDO::FETCH_ASSOC);
    if ($abs) {
        $presensi_exists[$s['id_siswa']] = $abs;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Absensi Siswa - SIS TKIT FATHUROBANI</title>
    <!-- MENGGUNAKAN CSS TERPUSAT -->
    <link rel="stylesheet" href="dashboard.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* CSS Khusus Layout Presensi (Sisanya numpang dashboard.css) */
        .header { margin-bottom: 30px; }
        .header h1 { font-size: 28px; font-weight: 800; color: #1e293b; margin: 0 0 8px 0; }
        .header p { color: #64748b; font-size: 15px; margin: 0; }

        .filter-card { background: #ffffff; border-radius: 20px; padding: 25px; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.02); border: 1px solid #f1f5f9; margin-bottom: 24px; display: flex; justify-content: space-between; align-items: flex-end; }
        .filter-form { display: flex; gap: 20px; align-items: flex-end; width: 100%; max-width: 500px; }
        .form-group { display: flex; flex-direction: column; gap: 8px; flex: 1; }
        .form-label { font-size: 13px; font-weight: 700; color: #475569; text-transform: uppercase; letter-spacing: 0.5px; }
        .form-input { width: 100%; padding: 14px 16px; border-radius: 12px; border: 1px solid #cbd5e1; font-family: 'Inter', sans-serif; font-size: 15px; background: #f8fafc; transition: all 0.3s; outline: none; box-sizing: border-box; font-weight: 600; color: #1e293b; }
        .form-input:focus { background: #fff; border-color: #3b82f6; box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1); }
        .btn-search { padding: 14px 24px; background: #2563eb; color: white; border: none; border-radius: 12px; font-weight: 700; cursor: pointer; transition: 0.3s; height: 50px; display: flex; align-items: center; gap: 8px; }
        .btn-search:hover { background: #1d4ed8; transform: translateY(-2px); box-shadow: 0 4px 12px rgba(37,99,235,0.2); }

        .attendance-card { background: #ffffff; border-radius: 20px; padding: 0; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.02); border: 1px solid #f1f5f9; overflow: hidden; }
        .attendance-header { padding: 20px 25px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; background: #f8fafc; }
        .attendance-title { margin: 0; font-size: 18px; font-weight: 800; color: #1e293b; display: flex; align-items: center; gap: 10px; }
        
        .student-item { display: flex; align-items: center; justify-content: space-between; padding: 20px 25px; border-bottom: 1px solid #f1f5f9; transition: background 0.2s; }
        .student-item:hover { background: #f8fafc; }
        
        .student-info { display: flex; align-items: center; gap: 15px; width: 30%; }
        .student-avatar { width: 50px; height: 50px; border-radius: 14px; background: #eff6ff; color: #3b82f6; display: flex; align-items: center; justify-content: center; font-size: 20px; font-weight: 800; flex-shrink: 0; }
        .student-details { display: flex; flex-direction: column; gap: 4px; }
        .student-name { font-size: 15px; font-weight: 700; color: #0f172a; line-height: 1.2; }
        .student-nis { font-size: 12px; font-weight: 600; color: #64748b; background: #f1f5f9; padding: 2px 8px; border-radius: 8px; display: inline-block; width: fit-content; }

        .attendance-status { display: flex; gap: 8px; width: 45%; }
        .status-radio { display: none; }
        .status-label { flex: 1; text-align: center; padding: 10px 0; border-radius: 10px; font-size: 13px; font-weight: 700; cursor: pointer; border: 2px solid transparent; transition: 0.2s; background: #f1f5f9; color: #64748b; }
        
        .status-radio:checked + .status-label.hadir { background: #10b981; color: white; border-color: #059669; box-shadow: 0 4px 10px rgba(16,185,129,0.3); }
        .status-radio:checked + .status-label.sakit { background: #eab308; color: white; border-color: #ca8a04; box-shadow: 0 4px 10px rgba(234,179,8,0.3); }
        .status-radio:checked + .status-label.izin { background: #f97316; color: white; border-color: #c2410c; box-shadow: 0 4px 10px rgba(249,115,22,0.3); }
        .status-radio:checked + .status-label.alpha { background: #ef4444; color: white; border-color: #b91c1c; box-shadow: 0 4px 10px rgba(239,68,68,0.3); }

        .student-notes { width: 25%; }
        .input-notes { width: 100%; padding: 12px; border-radius: 10px; border: 1px solid #e2e8f0; font-family: 'Inter', sans-serif; font-size: 13px; background: #fff; transition: 0.3s; box-sizing: border-box; outline: none; }
        .input-notes:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,0.1); }

        .btn-submit-main { background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%); color: white; border: none; padding: 18px; width: 100%; font-size: 16px; font-weight: 800; cursor: pointer; transition: 0.3s; display: flex; align-items: center; justify-content: center; gap: 10px; }
        .btn-submit-main:hover { background: linear-gradient(135deg, #1d4ed8 0%, #1e40af 100%); }

        .alert { padding: 16px 20px; border-radius: 12px; margin-bottom: 24px; font-weight: 600; font-size: 14px; display: flex; align-items: center; gap: 10px; }
        .alert-success { background: #ecfdf5; border: 1px solid #a7f3d0; color: #065f46; }
        .alert-error { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; }

        /* FIX UNTUK MOBILE RESPONSIVE (Mencegah konten keluar layar) */
        @media (max-width: 1024px) {
            .filter-card { flex-direction: column; align-items: stretch; gap: 20px; }
            .filter-form { max-width: 100%; }
            .total-murid { text-align: left !important; }
        }

        @media (max-width: 768px) {
            .student-item { flex-direction: column; align-items: stretch; gap: 15px; }
            .student-info, .attendance-status, .student-notes { width: 100%; }
            .attendance-status { justify-content: space-between; flex-wrap: wrap; }
            .status-label { flex: 1; min-width: 45%; } /* Di HP, tombol status dibagi 2 baris biar gak kekecilan */
        }
    </style>
</head>
<body>

    <!-- PEMANGGILAN SIDEBAR PHP -->
    <?php include 'sidebar.php'; ?>

    <main class="main-content">
        <div class="header">
            <h1>Input Kehadiran Kelas</h1>
            <p>Catat presensi harian seluruh siswa aktif dengan cepat dan mudah.</p>
        </div>

        <?php if (isset($success)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle" style="font-size: 20px;"></i> <?= $success ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-triangle" style="font-size: 20px;"></i> <?= $error ?>
            </div>
        <?php endif; ?>

        <!-- Filter Modern (Hanya Tanggal) -->
        <div class="filter-card">
            <form method="GET" class="filter-form">
                <div class="form-group">
                    <label class="form-label">Pilih Tanggal Presensi</label>
                    <input type="date" name="tanggal" class="form-input" value="<?= htmlspecialchars($selected_tanggal) ?>" required>
                </div>

                <button type="submit" class="btn-search">
                    <i class="fas fa-calendar-day"></i> Cek Tanggal
                </button>
            </form>
            
            <div class="total-murid" style="text-align: right;">
                <span style="font-size: 13px; color: #64748b; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 5px;">Total Murid Aktif</span>
                <span style="font-size: 24px; font-weight: 800; color: #1e293b;"><?= count($siswa) ?> Anak</span>
            </div>
        </div>

        <!-- Attendance List -->
        <div class="attendance-card">
            <?php if (!empty($siswa)): ?>
                <div class="attendance-header">
                    <h2 class="attendance-title"><i class="fas fa-clipboard-list" style="color: #3b82f6;"></i> Lembar Presensi</h2>
                    <span style="background: #f1f5f9; padding: 6px 12px; border-radius: 8px; font-size: 13px; font-weight: 700; color: #475569;">
                        <?= date('d M Y', strtotime($selected_tanggal)) ?>
                    </span>
                </div>
                
                <form method="POST">
                    <input type="hidden" name="tanggal" value="<?= htmlspecialchars($selected_tanggal) ?>">
                    
                    <div class="student-list">
                        <?php foreach ($siswa as $s): 
                            $abs = $presensi_exists[$s['id_siswa']] ?? null;
                            // Nyesuaiin database lu, ubah fallback nya biar matching
                            $current_status = $abs['status'] ?? 'Hadir'; 
                            $inisial = strtoupper(substr($s['nama_siswa'], 0, 1));
                        ?>
                        <div class="student-item">
                            <!-- Avatar & Nama -->
                            <div class="student-info">
                                <div class="student-avatar"><?= $inisial ?></div>
                                <div class="student-details">
                                    <span class="student-name"><?= htmlspecialchars($s['nama_siswa']) ?></span>
                                    <span class="student-nis">NIS: <?= htmlspecialchars($s['nis'] ?? '-') ?></span>
                                </div>
                            </div>

                            <!-- Pilihan Kehadiran (Radio Buttons) -->
                            <div class="attendance-status">
                                <!-- Hadir -->
                                <input type="radio" id="hadir_<?= $s['id_siswa'] ?>" name="status[<?= $s['id_siswa'] ?>]" value="Hadir" class="status-radio" <?= $current_status == 'Hadir' ? 'checked' : '' ?>>
                                <label for="hadir_<?= $s['id_siswa'] ?>" class="status-label hadir">Hadir</label>
                                
                                <!-- Sakit -->
                                <input type="radio" id="sakit_<?= $s['id_siswa'] ?>" name="status[<?= $s['id_siswa'] ?>]" value="Sakit" class="status-radio" <?= $current_status == 'Sakit' ? 'checked' : '' ?>>
                                <label for="sakit_<?= $s['id_siswa'] ?>" class="status-label sakit">Sakit</label>
                                
                                <!-- Izin -->
                                <input type="radio" id="izin_<?= $s['id_siswa'] ?>" name="status[<?= $s['id_siswa'] ?>]" value="Izin" class="status-radio" <?= $current_status == 'Izin' ? 'checked' : '' ?>>
                                <label for="izin_<?= $s['id_siswa'] ?>" class="status-label izin">Izin</label>
                                
                                <!-- Alpha / Alpa -->
                                <input type="radio" id="alpha_<?= $s['id_siswa'] ?>" name="status[<?= $s['id_siswa'] ?>]" value="Alpha" class="status-radio" <?= ($current_status == 'Alpha' || $current_status == 'Alpa') ? 'checked' : '' ?>>
                                <label for="alpha_<?= $s['id_siswa'] ?>" class="status-label alpha">Alpha</label>
                            </div>

                            <!-- Catatan -->
                            <div class="student-notes">
                                <input type="text" name="catatan[<?= $s['id_siswa'] ?>]" 
                                       class="input-notes" 
                                       value="<?= htmlspecialchars($abs['catatan'] ?? '') ?>"
                                       placeholder="Catatan (opsional)...">
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <button type="submit" name="simpan" class="btn-submit-main">
                        <i class="fas fa-save"></i> Simpan Presensi Hari Ini
                    </button>
                </form>

            <?php else: ?>
                <div style="padding: 60px 20px; text-align: center; color: #64748b;">
                    <i class="fas fa-users-slash" style="font-size: 50px; color: #cbd5e1; margin-bottom: 16px;"></i>
                    <h3 style="font-size: 18px; font-weight: 700; color: #1e293b; margin: 0 0 8px 0;">Data Kosong</h3>
                    <p>Sistem tidak menemukan data murid yang aktif.</p>
                </div>
            <?php endif; ?>
        </div>
    </main>

</body>
</html>