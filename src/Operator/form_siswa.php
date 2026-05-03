<?php
session_start();
require_once '../config/db.php';
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'operator') { header("Location: ../auth/login.php"); exit; }

$is_edit = isset($_GET['id']);
$siswa = ['id_siswa' => '', 'nis' => '', 'nama_siswa' => '', 'alamat' => '', 'id_kelas' => ''];

// Ambil data kelas langsung dari database secara murni
// Jika tabel kelas kosong, maka opsi di form juga akan kosong.
$kelas_list = $pdo->query("SELECT id_kelas, nama_kelas FROM kelas ORDER BY nama_kelas ASC")->fetchAll();

if ($is_edit) {
    $stmt = $pdo->prepare("SELECT * FROM siswa WHERE id_siswa = ?");
    $stmt->execute([$_GET['id']]);
    $siswa = $stmt->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nis = $_POST['nis'];
    $nama = $_POST['nama_siswa'];
    $alamat = $_POST['alamat'];
    // Validasi input kelas agar tidak error jika tidak dipilih
    $id_kelas = !empty($_POST['id_kelas']) ? $_POST['id_kelas'] : NULL;

    if ($is_edit) {
        $stmt = $pdo->prepare("UPDATE siswa SET nis=?, nama_siswa=?, alamat=?, id_kelas=? WHERE id_siswa=?");
        $stmt->execute([$nis, $nama, $alamat, $id_kelas, $_POST['id_siswa']]);
    } else {
        $new_id = 'S' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
        $stmt = $pdo->prepare("INSERT INTO siswa (id_siswa, nis, nama_siswa, alamat, id_kelas, status) VALUES (?, ?, ?, ?, ?, 'Aktif')");
        $stmt->execute([$new_id, $nis, $nama, $alamat, $id_kelas]);
    }
    header("Location: data_siswa.php?msg=berhasil");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $is_edit ? 'Edit' : 'Tambah' ?> Siswa - Operator</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="dashboard.css">
    <style>
        .input-group { margin-bottom: 20px; }
        .input-group label { display: block; margin-bottom: 8px; font-weight: 600; color: #1e293b; font-size: 14px; }
        .form-control { width: 100%; padding: 12px 15px; border: 1px solid #cbd5e1; border-radius: 8px; font-family: 'Inter', sans-serif; font-size: 14px; outline: none;}
        .form-control:focus { border-color: #1e40af; box-shadow: 0 0 0 3px rgba(30, 64, 175, 0.1); }
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
            <a href="data_siswa.php" style="color: #1e40af; text-decoration: none; font-size: 14px; font-weight: 600; display: inline-block; margin-bottom: 10px;">
                <i class="fas fa-arrow-left"></i> Kembali ke Data Siswa
            </a>
            <h1><?= $is_edit ? 'Edit' : 'Tambah' ?> Data Siswa</h1>
            <p>Pastikan data Induk yang dimasukkan valid dan sesuai akta.</p>
        </div>

        <div class="content-card" style="max-width: 600px;">
            <form method="POST">
                <?php if($is_edit): ?><input type="hidden" name="id_siswa" value="<?= $siswa['id_siswa'] ?>"><?php endif; ?>
                
                <div class="input-group">
                    <label>NIS (Nomor Induk Siswa)</label>
                    <input type="text" name="nis" value="<?= htmlspecialchars($siswa['nis']) ?>" class="form-control" required placeholder="Contoh: 23001">
                </div>
                
                <div class="input-group">
                    <label>Nama Lengkap Anak</label>
                    <input type="text" name="nama_siswa" value="<?= htmlspecialchars($siswa['nama_siswa']) ?>" class="form-control" required placeholder="Nama sesuai akta kelahiran">
                </div>
                
                <div class="input-group">
                    <label>Penempatan Kelas</label>
                    <select name="id_kelas" class="form-control" required>
                        <option value="" disabled selected>-- Pilih Kelas --</option>
                        
                        <?php if (empty($kelas_list)): ?>
                            <option value="" disabled>BELUM ADA DATA KELAS DI DATABASE!</option>
                        <?php else: ?>
                            <?php foreach($kelas_list as $k): ?>
                                <option value="<?= htmlspecialchars($k['id_kelas']) ?>" <?= ($siswa['id_kelas'] == $k['id_kelas']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($k['nama_kelas']) ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>

                    </select>
                    <?php if (empty($kelas_list)): ?>
                        <small style="color: #ef4444; font-weight: 600; display: block; margin-top: 5px;">*Tabel kelas masih kosong. Silakan tambahkan data kelas terlebih dahulu di menu Data Kelas.</small>
                    <?php endif; ?>
                </div>
                
                <div class="input-group">
                    <label>Alamat Lengkap Tempat Tinggal</label>
                    <textarea name="alamat" class="form-control" rows="4" required placeholder="Tulis alamat domisili saat ini..."><?= htmlspecialchars($siswa['alamat']) ?></textarea>
                </div>
                
                <button type="submit" style="background: #1e40af; color: white; padding: 12px; width: 100%; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; font-size: 15px;">
                    <i class="fas fa-save"></i> Simpan Data Siswa
                </button>
            </form>
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