<?php
session_start();
require_once '../config/db.php';

// PROTEKSI: Khusus Operator
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'operator') {
    header("Location: ../auth/login.php");
    exit();
}

$is_edit = isset($_GET['id']);
$guru = [
    'id_guru' => '', 
    'nama_guru' => '', 
    'nip' => '', 
    'no_hp' => '',
    'email' => ''
];

// Jika mode EDIT, ambil data guru berdasarkan ID
if ($is_edit) {
    $stmt = $pdo->prepare("SELECT * FROM guru WHERE id_guru = ?");
    $stmt->execute([$_GET['id']]);
    $guru = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Jika ID tidak ditemukan di database, tendang balik ke halaman data
    if (!$guru) {
        header("Location: data_guru.php");
        exit();
    }
}

// LOGIKA SIMPAN (INSERT / UPDATE)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_guru = $_POST['nama_guru'];
    $nip = $_POST['nip'];
    $no_hp = $_POST['no_hp'];
    $email = $_POST['email'];

    try {
        if ($is_edit) {
            // Proses Update
            $id_guru = $_POST['id_guru_hidden']; // ID lama disembunyikan agar tidak dimanipulasi
            $stmt = $pdo->prepare("UPDATE guru SET nama_guru = ?, nip = ?, no_hp = ?, email = ? WHERE id_guru = ?");
            $stmt->execute([$nama_guru, $nip, $no_hp, $email, $id_guru]);
        } else {
            // Proses Insert Baru
            $id_guru = trim($_POST['id_guru']); // ID Guru manual (contoh: G001)
            $stmt = $pdo->prepare("INSERT INTO guru (id_guru, nama_guru, nip, no_hp, email) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$id_guru, $nama_guru, $nip, $no_hp, $email]);
        }
        
        // Lempar kembali ke tabel dengan pesan sukses
        header("Location: data_guru.php?msg=tersimpan");
        exit();
    } catch (PDOException $e) {
        $error_msg = "Gagal menyimpan data (mungkin ID sudah dipakai): " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $is_edit ? 'Edit' : 'Tambah' ?> Guru - SIS TKIT Fathurrobbany</title>
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
        .input-modern:focus { border-color: #3b82f6; background: #fff; box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1); }
        .input-modern:disabled, .input-modern[readonly] { background: #e2e8f0; color: #64748b; cursor: not-allowed; border-color: #cbd5e1;}

        .btn-submit { width: 100%; padding: 15px; background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); color: white; border: none; border-radius: 12px; font-weight: 700; font-size: 15px; cursor: pointer; transition: 0.3s; box-shadow: 0 4px 10px rgba(59, 130, 246, 0.3); display: flex; align-items: center; justify-content: center; gap: 8px; margin-top: 10px;}
        .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 6px 15px rgba(59, 130, 246, 0.4); }

        .back-link { display: inline-flex; align-items: center; gap: 8px; color: #64748b; text-decoration: none; font-size: 14px; font-weight: 600; margin-bottom: 20px; transition: 0.2s; }
        .back-link:hover { color: #1e40af; transform: translateX(-3px); }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <main class="main-content">
        <a href="data_guru.php" class="back-link"><i class="fas fa-arrow-left"></i> Kembali ke Data Guru</a>
        
        <div class="header-container">
            <div class="header-title">
                <h1><?= $is_edit ? 'Edit Data Guru' : 'Tambah Tenaga Pendidik' ?></h1>
                <p>Kelola identitas, NIP, dan kontak staf pengajar.</p>
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
                    <input type="hidden" name="id_guru_hidden" value="<?= htmlspecialchars($guru['id_guru']) ?>">
                <?php endif; ?>

                <div class="form-group">
                    <label class="form-label">ID Guru / Kode Staf</label>
                    <input type="text" name="id_guru" class="input-modern" value="<?= htmlspecialchars($guru['id_guru']) ?>" 
                           placeholder="Contoh: G001" required <?= $is_edit ? 'readonly title="ID Guru tidak dapat diubah"' : '' ?>>
                </div>

                <div class="form-group">
                    <label class="form-label">Nama Lengkap Guru</label>
                    <input type="text" name="nama_guru" class="input-modern" value="<?= htmlspecialchars($guru['nama_guru']) ?>" 
                           placeholder="Masukkan nama lengkap beserta gelar..." required>
                </div>

                <div class="form-group">
                    <label class="form-label">NIP (Nomor Induk Pegawai)</label>
                    <input type="text" name="nip" class="input-modern" value="<?= htmlspecialchars($guru['nip']) ?>" 
                           placeholder="Kosongkan jika tidak ada (opsional)">
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="form-group">
                        <label class="form-label">No. WhatsApp / HP</label>
                        <input type="text" name="no_hp" class="input-modern" value="<?= htmlspecialchars($guru['no_hp']) ?>" 
                               placeholder="Contoh: 08123456789">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Alamat Email</label>
                        <input type="email" name="email" class="input-modern" value="<?= htmlspecialchars($guru['email']) ?>" 
                               placeholder="guru@tkit.com">
                    </div>
                </div>

                <button type="submit" class="btn-submit">
                    <i class="fas fa-save"></i> <?= $is_edit ? 'Simpan Perubahan' : 'Tambahkan Guru' ?>
                </button>
            </form>
        </div>
    </main>

</body>
</html>