<?php
session_start();
require_once '../config/db.php';

// PROTEKSI: Khusus Operator
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'operator') {
    header("Location: ../auth/login.php");
    exit();
}

$is_edit = isset($_GET['id']);
$kelas = [
    'id_kelas' => '', 
    'nama_kelas' => '', 
    'tingkat' => '', 
    'id_guru' => ''
];

// Ambil daftar guru dari database untuk opsi Wali Kelas
$guru_list = $pdo->query("SELECT id_guru, nama_guru FROM guru ORDER BY nama_guru ASC")->fetchAll(PDO::FETCH_ASSOC);

// Jika mode EDIT, ambil data kelas berdasarkan ID
if ($is_edit) {
    $stmt = $pdo->prepare("SELECT * FROM kelas WHERE id_kelas = ?");
    $stmt->execute([$_GET['id']]);
    $kelas = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Jika ID tidak ditemukan, kembalikan ke data kelas
    if (!$kelas) {
        header("Location: data_kelas.php");
        exit();
    }
}

// LOGIKA SIMPAN (INSERT / UPDATE)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_kelas = $_POST['nama_kelas'];
    $tingkat = $_POST['tingkat'];
    // Jika tidak ada guru yang dipilih, set menjadi NULL
    $id_guru = empty($_POST['id_guru']) ? NULL : $_POST['id_guru'];

    try {
        if ($is_edit) {
            // Proses Update
            $id_kelas = $_POST['id_kelas_hidden']; // ID lama yang disembunyikan
            $stmt = $pdo->prepare("UPDATE kelas SET nama_kelas = ?, tingkat = ?, id_guru = ? WHERE id_kelas = ?");
            $stmt->execute([$nama_kelas, $tingkat, $id_guru, $id_kelas]);
        } else {
            // Proses Insert Baru
            $id_kelas = trim($_POST['id_kelas']); // Kode kelas baru yang diinput manual
            $stmt = $pdo->prepare("INSERT INTO kelas (id_kelas, nama_kelas, tingkat, id_guru) VALUES (?, ?, ?, ?)");
            $stmt->execute([$id_kelas, $nama_kelas, $tingkat, $id_guru]);
        }
        
        header("Location: data_kelas.php?msg=tersimpan");
        exit();
    } catch (PDOException $e) {
        $error_msg = "Gagal menyimpan data: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $is_edit ? 'Edit' : 'Tambah' ?> Kelas - SIS TKIT Fathurrobbany</title>
    <link rel="stylesheet" href="dashboard.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* KONSISTENSI TEMA BIRU SLATE */
        body { font-family: 'Inter', sans-serif; background-color: #f8fafc; color: #0f172a; margin: 0; }
        .main-content { padding: 30px 40px; margin-left: 260px; }

        .header-container { margin-bottom: 30px; }
        .header-title h1 { font-size: 26px; font-weight: 800; color: #1e293b; margin: 0 0 5px 0; }
        .header-title p { margin: 0; color: #64748b; font-size: 14px; }

        .form-card { background: white; border-radius: 20px; padding: 30px; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.03); border: 1px solid #f1f5f9; max-width: 600px; }
        
        .form-group { margin-bottom: 20px; }
        .form-label { display: block; font-size: 13px; font-weight: 700; color: #475569; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px; }
        
        .input-modern { width: 100%; padding: 14px 16px; border-radius: 12px; border: 1px solid #cbd5e1; font-family: 'Inter', sans-serif; font-size: 14px; background: #f8fafc; transition: 0.3s; box-sizing: border-box; outline: none; color: #1e293b;}
        .input-modern:focus { border-color: #10b981; background: #fff; box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.1); }
        .input-modern:disabled, .input-modern[readonly] { background: #e2e8f0; color: #64748b; cursor: not-allowed; border-color: #cbd5e1;}

        .btn-submit { width: 100%; padding: 15px; background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; border: none; border-radius: 12px; font-weight: 700; font-size: 15px; cursor: pointer; transition: 0.3s; box-shadow: 0 4px 10px rgba(16, 185, 129, 0.3); display: flex; align-items: center; justify-content: center; gap: 8px; margin-top: 10px;}
        .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 6px 15px rgba(16, 185, 129, 0.4); }

        .back-link { display: inline-flex; align-items: center; gap: 8px; color: #64748b; text-decoration: none; font-size: 14px; font-weight: 600; margin-bottom: 20px; transition: 0.2s; }
        .back-link:hover { color: #1e40af; transform: translateX(-3px); }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <main class="main-content">
        <a href="data_kelas.php" class="back-link"><i class="fas fa-arrow-left"></i> Kembali ke Data Kelas</a>
        
        <div class="header-container">
            <div class="header-title">
                <h1><?= $is_edit ? 'Edit Data Kelas' : 'Buka Kelas Baru' ?></h1>
                <p>Tentukan nama kelas, tingkatan, dan tetapkan Wali Kelas.</p>
            </div>
        </div>

        <div class="form-card">
            <?php if(isset($error_msg)): ?>
                <div style="background:#fef2f2; color:#991b1b; padding:15px; border-radius:12px; margin-bottom:20px; font-weight:600; border: 1px solid #fecaca;">
                    <i class="fas fa-exclamation-circle"></i> <?= $error_msg ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <?php if($is_edit): ?>
                    <input type="hidden" name="id_kelas_hidden" value="<?= htmlspecialchars($kelas['id_kelas']) ?>">
                <?php endif; ?>

                <div class="form-group">
                    <label class="form-label">Kode Kelas</label>
                    <input type="text" name="id_kelas" class="input-modern" value="<?= htmlspecialchars($kelas['id_kelas']) ?>" 
                           placeholder="Contoh: K001" required <?= $is_edit ? 'readonly title="Kode Kelas tidak dapat diubah"' : '' ?>>
                </div>

                <div class="form-group">
                    <label class="form-label">Nama Rombongan Belajar</label>
                    <input type="text" name="nama_kelas" class="input-modern" value="<?= htmlspecialchars($kelas['nama_kelas']) ?>" 
                           placeholder="Contoh: Kelas Abu Bakar" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Tingkat Pendidikan</label>
                    <select name="tingkat" class="input-modern" required>
                        <option value="" disabled <?= empty($kelas['tingkat']) ? 'selected' : '' ?>>-- Pilih Tingkatan --</option>
                        <option value="Playgroup" <?= ($kelas['tingkat'] == 'Playgroup') ? 'selected' : '' ?>>Playgroup (KB)</option>
                        <option value="TK A" <?= ($kelas['tingkat'] == 'TK A') ? 'selected' : '' ?>>TK A</option>
                        <option value="TK B" <?= ($kelas['tingkat'] == 'TK B') ? 'selected' : '' ?>>TK B</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Penugasan Wali Kelas</label>
                    <select name="id_guru" class="input-modern">
                        <option value="" <?= empty($kelas['id_guru']) ? 'selected' : '' ?>>-- Belum Ada Wali Kelas --</option>
                        
                        <?php if(empty($guru_list)): ?>
                            <option value="" disabled>⚠️ Data Guru Masih Kosong!</option>
                        <?php else: ?>
                            <?php foreach($guru_list as $guru): ?>
                                <option value="<?= htmlspecialchars($guru['id_guru']) ?>" 
                                    <?= ($kelas['id_guru'] == $guru['id_guru']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($guru['nama_guru']) ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        
                    </select>
                    <small style="color: #94a3b8; font-size: 12px; margin-top: 6px; display: block;">
                        <i class="fas fa-info-circle"></i> Nama guru ditarik otomatis dari menu Data Guru.
                    </small>
                </div>

                <button type="submit" class="btn-submit">
                    <i class="fas fa-save"></i> <?= $is_edit ? 'Simpan Perubahan' : 'Buat Kelas Baru' ?>
                </button>
            </form>
        </div>
    </main>

</body>
</html>