<?php
// src/presensi/presensi.php
require_once '../config/db.php';

// Cek login guru
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'guru') {
    header("Location: ../login.php");
    exit();
}

$id_user = $_SESSION['id_user'];
$email = $_SESSION['username'];

// CEK DATA GURU
$sql_guru = "SELECT * FROM guru WHERE email = '$email'";
$result_guru = $pdo->query($sql_guru);
$guru = $result_guru->fetch();

// Jika data guru belum ada, buat baru
if (!$guru) {
    $id_guru = 'G' . rand(1,999);
    $sql_insert = "INSERT INTO guru (id_guru, nama_guru, email) 
                   VALUES ('$id_guru', '{$_SESSION['nama_lengkap']}', '$email')";
    $pdo->query($sql_insert);
    $guru = ['id_guru' => $id_guru];
}

// Ambil daftar kelas
$kelas = $pdo->query("SELECT * FROM kelas")->fetchAll();

// Proses simpan presensi
if (isset($_POST['simpan'])) {
    $tanggal = $_POST['tanggal'];
    $id_kelas = $_POST['id_kelas'];
    
    foreach ($_POST['status'] as $id_siswa => $status) {
        $catatan = $_POST['catatan'][$id_siswa] ?? '';
        
        // Cek apakah sudah ada
        $cek = $pdo->query("SELECT id_absen FROM absensi WHERE id_siswa='$id_siswa' AND tanggal='$tanggal'")->fetch();
        
        if ($cek) {
            // Update
            $pdo->query("UPDATE absensi SET status='$status', catatan='$catatan', input_by='{$guru['id_guru']}' WHERE id_absen='{$cek['id_absen']}'");
        } else {
            // Insert baru
            $id_absen = 'ABS' . rand(1,999);
            $pdo->query("INSERT INTO absensi (id_absen, id_siswa, tanggal, status, catatan, input_by) 
                         VALUES ('$id_absen', '$id_siswa', '$tanggal', '$status', '$catatan', '{$guru['id_guru']}')");
        }
    }
    $success = "Presensi berhasil disimpan!";
}

// Filter
$selected_kelas = $_GET['kelas'] ?? '';
$selected_tanggal = $_GET['tanggal'] ?? date('Y-m-d');
$siswa = [];

if ($selected_kelas) {
    $siswa = $pdo->query("SELECT * FROM siswa WHERE id_kelas='$selected_kelas' AND status='Aktif' ORDER BY nama_siswa")->fetchAll();
    
    // Ambil presensi yang sudah ada
    $presensi_exists = [];
    foreach ($siswa as $s) {
        $abs = $pdo->query("SELECT * FROM absensi WHERE id_siswa='{$s['id_siswa']}' AND tanggal='$selected_tanggal'")->fetch();
        if ($abs) {
            $presensi_exists[$s['id_siswa']] = $abs;
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Presensi Harian</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: Arial, sans-serif; }
        body { background: #f0f2f5; padding: 20px; }
        .container { max-width: 1200px; margin: 0 auto; }
        .header { background: #1877f2; color: white; padding: 20px; border-radius: 10px; margin-bottom: 20px; }
        .header h1 { font-size: 24px; }
        .header a { color: white; float: right; text-decoration: none; }
        .card { background: white; border-radius: 10px; padding: 20px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .filter-form { display: flex; gap: 10px; margin-bottom: 20px; flex-wrap: wrap; }
        .filter-form select, .filter-form input, .filter-form button { padding: 10px; border: 1px solid #ddd; border-radius: 5px; }
        .filter-form button { background: #1877f2; color: white; border: none; cursor: pointer; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #f5f6f7; padding: 12px; text-align: left; border-bottom: 2px solid #ddd; }
        td { padding: 12px; border-bottom: 1px solid #ddd; }
        .status-select { padding: 5px; border: 1px solid #ddd; border-radius: 3px; width: 100px; }
        .catatan-input { padding: 5px; border: 1px solid #ddd; border-radius: 3px; width: 100%; }
        .btn-simpan { background: #42b72a; color: white; padding: 15px; border: none; border-radius: 5px; font-size: 16px; cursor: pointer; width: 100%; margin-top: 20px; }
        .alert { background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .info { background: #e7f3ff; padding: 10px; border-radius: 5px; margin: 20px 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📋 Presensi Harian Siswa</h1>
            <a href="../logout.php">Logout</a>
        </div>

        <?php if (isset($success)): ?>
            <div class="alert"><?= $success ?></div>
        <?php endif; ?>

        <div class="card">
            <form method="GET" class="filter-form">
                <select name="kelas" required>
                    <option value="">-- Pilih Kelas --</option>
                    <?php foreach ($kelas as $k): ?>
                        <option value="<?= $k['id_kelas'] ?>" <?= $selected_kelas == $k['id_kelas'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($k['nama_kelas']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <input type="date" name="tanggal" value="<?= $selected_tanggal ?>" required>
                <button type="submit">Tampilkan</button>
            </form>

            <?php if ($selected_kelas && !empty($siswa)): ?>
                <div class="info">
                    <strong>Kelas:</strong> <?= htmlspecialchars($siswa[0]['nama_kelas'] ?? '') ?> | 
                    <strong>Tanggal:</strong> <?= date('d-m-Y', strtotime($selected_tanggal)) ?> |
                    <strong>Jumlah Siswa:</strong> <?= count($siswa) ?>
                </div>

                <form method="POST">
                    <input type="hidden" name="tanggal" value="<?= $selected_tanggal ?>">
                    <input type="hidden" name="id_kelas" value="<?= $selected_kelas ?>">
                    
                    <table>
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>NIS</th>
                                <th>Nama Siswa</th>
                                <th>Status</th>
                                <th>Catatan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no=1; foreach ($siswa as $s): 
                                $abs = $presensi_exists[$s['id_siswa']] ?? null;
                            ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td><?= htmlspecialchars($s['nis']) ?></td>
                                <td><strong><?= htmlspecialchars($s['nama_siswa']) ?></strong></td>
                                <td>
                                    <select name="status[<?= $s['id_siswa'] ?>]" class="status-select">
                                        <option value="Hadir" <?= ($abs['status']??'')=='Hadir' ? 'selected' : '' ?>>Hadir</option>
                                        <option value="Sakit" <?= ($abs['status']??'')=='Sakit' ? 'selected' : '' ?>>Sakit</option>
                                        <option value="Izin" <?= ($abs['status']??'')=='Izin' ? 'selected' : '' ?>>Izin</option>
                                        <option value="Alpha" <?= ($abs['status']??'')=='Alpha' ? 'selected' : '' ?>>Alpha</option>
                                    </select>
                                </td>
                                <td>
                                    <input type="text" name="catatan[<?= $s['id_siswa'] ?>]" 
                                           class="catatan-input" value="<?= htmlspecialchars($abs['catatan'] ?? '') ?>"
                                           placeholder="Catatan">
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <button type="submit" name="simpan" class="btn-simpan">💾 Simpan Presensi</button>
                </form>
            <?php elseif ($selected_kelas): ?>
                <p style="text-align: center; padding: 50px;">Tidak ada siswa di kelas ini</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>