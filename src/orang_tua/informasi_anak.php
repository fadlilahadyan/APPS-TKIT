<?php
session_start();
// Mundur satu folder untuk panggil db
require_once '../config/db.php';
date_default_timezone_set('Asia/Jakarta');

// PROTEKSI: Hanya Orang Tua yang bisa akses
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'orang_tua') {
    header("Location: ../auth/login.php");
    exit();
}

$id_user = $_SESSION['id_user'];
// Mengambil nama orang tua dari session saat login
$nama_orangtua = $_SESSION['nama_lengkap'] ?? 'Orang Tua'; 
$success_msg = '';
$error_msg = '';

// --- LOGIKA PROSES UPDATE DATA & FOTO ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_siswa'])) {
    $nama_baru = $_POST['nama_siswa'];
    $alamat_baru = $_POST['alamat'];
    $jk_baru = $_POST['jk']; 
    $tgl_lahir_baru = !empty($_POST['tgl_lahir']) ? $_POST['tgl_lahir'] : NULL; // Menangkap input tanggal lahir
    $id_siswa = $_POST['id_siswa'];

    $foto_query = "";
    $params = [$nama_baru, $alamat_baru, $jk_baru, $tgl_lahir_baru];

    // Cek apakah ada file foto yang diunggah
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
        
        // MENGARAHKAN KE FOLDER ASSETS MILIKMU
        $folder_upload = "../assets/img/"; 
        
        if (!is_dir($folder_upload)) {
            mkdir($folder_upload, 0777, true);
        }

        $nama_file = time() . "_" . preg_replace("/[^a-zA-Z0-9.]/", "", basename($_FILES["foto"]["name"]));
        $target_file = $folder_upload . $nama_file;
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        
        // Validasi ekstensi
        $valid_extensions = array("jpg", "jpeg", "png", "webp");
        if (in_array($imageFileType, $valid_extensions)) {
            if (move_uploaded_file($_FILES["foto"]["tmp_name"], $target_file)) {
                $foto_query = ", foto = ?";
                $params[] = "img/" . $nama_file; // Simpan path relatif ke db
            } else {
                $error_msg = "Sistem gagal menyimpan. Pastikan folder assets bisa ditulis.";
            }
        } else {
            $error_msg = "Format foto tidak didukung. Gunakan JPG, PNG, atau WEBP.";
        }
    }

    // Eksekusi Update ke Database jika tidak ada error upload
    if (empty($error_msg)) {
        $params[] = $id_siswa;
        $params[] = $id_user;
        try {
            // Update nama, alamat, jk, tgl_lahir, dan foto (jika ada)
            $update = $pdo->prepare("UPDATE siswa SET nama_siswa = ?, alamat = ?, jk = ?, tgl_lahir = ? $foto_query WHERE id_siswa = ? AND id_ortu = ?");
            $update->execute($params);
            $success_msg = "Profil ananda berhasil diperbarui!";
        } catch (PDOException $e) {
            $error_msg = "Gagal memperbarui data: " . $e->getMessage();
        }
    }
}

try {
    // Ambil Data Lengkap Anak yang terbaru
    $sql = "SELECT s.*, k.nama_kelas 
            FROM siswa s 
            LEFT JOIN kelas k ON s.id_kelas = k.id_kelas 
            WHERE s.id_ortu = ? LIMIT 1";
            
    $stmtChild = $pdo->prepare($sql);
    $stmtChild->execute([$id_user]);
    $data_anak = $stmtChild->fetch(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Sistem Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profil Anak - SIS TKIT Fathurrobbany</title>
    <!-- Arahkan CSS ke dashboard.css -->
    <link rel="stylesheet" href="dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        .header { margin-bottom: 30px; }
        .header h1 { font-size: 28px; font-weight: 800; color: #1e293b; margin: 0 0 8px 0; }
        .header p { color: #64748b; font-size: 15px; margin: 0; }

        /* Tata letak Form Grid yang rapi */
        .profile-layout {
            display: flex;
            gap: 30px;
            align-items: flex-start;
        }

        .card-foto {
            flex: 0 0 320px; /* Fix lebar card foto di desktop */
            background: white; 
            border: 1px solid #f1f5f9; 
            border-radius: 20px; 
            padding: 40px 20px; 
            text-align: center;
            box-shadow: 0 10px 25px -5px rgba(0,0,0,0.03);
            position: sticky; 
            top: 20px;
        }

        .card-formulir {
            flex: 1; /* Biarkan form ngambil sisa space (justify) */
            background: white; 
            border: 1px solid #f1f5f9; 
            border-radius: 20px; 
            padding: 35px;
            box-shadow: 0 10px 25px -5px rgba(0,0,0,0.03);
        }

        .form-grid-inner {
            display: grid; 
            grid-template-columns: 1fr 1fr; 
            gap: 20px;
        }

        /* Input styling */
        .form-group { margin-bottom: 5px; }
        .form-label { display: block; font-size: 12px; font-weight: 700; color: #64748b; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px; }
        
        .input-icon-wrapper { position: relative; width: 100%; display: flex; align-items: center; }
        .input-icon-wrapper i { position: absolute; left: 16px; color: #94a3b8; font-size: 16px; pointer-events: none;}
        
        .input-modern { width: 100%; padding: 14px 16px 14px 45px; border: 1px solid #cbd5e1; border-radius: 12px; font-family: 'Inter', sans-serif; font-size: 14px; color: #1e293b; background: #f8fafc; transition: all 0.3s ease; box-sizing: border-box; font-weight: 500;}
        .input-modern:focus { border-color: #3b82f6; background: #fff; outline: none; box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1); }
        .input-modern:disabled, .input-modern[readonly] { background: #f1f5f9; color: #64748b; cursor: not-allowed; border-color: #e2e8f0; font-weight: 600;}
        
        /* Textarea tanpa icon kiri */
        .input-modern.no-icon { padding-left: 16px; resize: vertical; min-height: 100px; }

        .profile-avatar-wrapper { position: relative; width: 150px; height: 150px; margin: 0 auto 20px; cursor: pointer; transition: transform 0.2s; }
        .profile-avatar-wrapper:hover { transform: scale(1.05); }
        .profile-badge { position: absolute; bottom: 5px; right: 5px; background: #3b82f6; color: white; width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 16px; border: 4px solid #fff; box-shadow: 0 4px 10px rgba(0,0,0,0.2); }
        
        .btn-save-modern { background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); color: white; border: none; padding: 16px 24px; border-radius: 12px; font-weight: 700; font-size: 15px; cursor: pointer; transition: 0.3s; width: 100%; display: flex; align-items: center; justify-content: center; gap: 8px; box-shadow: 0 4px 10px rgba(37, 99, 235, 0.3); margin-top: 20px;}
        .btn-save-modern:hover { transform: translateY(-3px); box-shadow: 0 10px 15px -3px rgba(37, 99, 235, 0.4); }

        .alert { padding: 16px 20px; border-radius: 12px; margin-bottom: 25px; font-weight: 600; display: flex; align-items: center; gap: 10px; font-size: 14px;}
        .alert-success { background: #ecfdf5; border: 1px solid #a7f3d0; color: #065f46; }
        .alert-error { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; }

        /* Responsif HP */
        @media (max-width: 1024px) {
            .profile-layout { flex-direction: column; }
            .card-foto { flex: unset; width: 100%; position: relative; top: 0; box-sizing: border-box; }
            .card-formulir { width: 100%; box-sizing: border-box; }
            .form-grid-inner { grid-template-columns: 1fr; } /* Form turun jadi 1 kolom */
            .span-2 { grid-column: span 1 !important; }
        }
    </style>
</head>
<body>
    
    <?php include 'sidebar.php'; ?>

    <main class="main-content">
        <div class="header">
            <h1>Informasi Profil Ananda</h1>
            <p>Kelola dan pastikan biodata anak Anda selalu diperbarui sesuai KK.</p>
        </div>

        <?php if ($success_msg): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle" style="font-size: 20px;"></i>
                <?= $success_msg; ?>
            </div>
        <?php endif; ?>

        <?php if ($error_msg): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-triangle" style="font-size: 20px;"></i>
                <?= $error_msg; ?>
            </div>
        <?php endif; ?>

        <?php if (!$data_anak): ?>
            <div style="background: #fef2f2; border: 1px solid #fecaca; padding: 40px; border-radius: 20px; text-align: center;">
                <i class="fas fa-user-times" style="font-size: 50px; color: #ef4444; margin-bottom: 20px;"></i>
                <h3 style="color: #991b1b; margin: 0 0 10px 0; font-size: 22px; font-weight: 800;">Data Ananda Belum Ditautkan</h3>
                <p style="color: #b91c1c; font-size: 15px; margin: 0;">Silakan kembali ke Dashboard untuk menautkan NIS anak Anda terlebih dahulu agar dapat melihat profilnya.</p>
            </div>
        <?php else: ?>
            <form method="POST" enctype="multipart/form-data" class="profile-layout">
                <input type="hidden" name="id_siswa" value="<?= $data_anak['id_siswa']; ?>">

                <!-- SISI KIRI: KARTU FOTO -->
                <div class="card-foto">
                    <div class="profile-avatar-wrapper" onclick="document.getElementById('input_foto').click()" title="Klik untuk ubah foto">
                        <?php 
                            // Tampilkan foto dari folder assets
                            $foto_url = !empty($data_anak['foto']) 
                                        ? "../assets/" . htmlspecialchars($data_anak['foto']) 
                                        : "https://ui-avatars.com/api/?name=" . urlencode($data_anak['nama_siswa']) . "&background=eff6ff&color=2563eb&size=200";
                        ?>
                        <img id="preview_foto" src="<?= $foto_url ?>" style="width: 100%; height: 100%; border-radius: 50%; object-fit: cover; border: 4px solid #fff; box-shadow: 0 10px 25px -5px rgba(59, 130, 246, 0.4);">
                        
                        <div class="profile-badge">
                            <i class="fas fa-camera"></i>
                        </div>
                    </div>
                    
                    <input type="file" name="foto" id="input_foto" accept="image/*" style="display: none;" onchange="tampilkanPreview(event)">
                    
                    <h3 style="margin: 0 0 8px 0; color: #0f172a; font-size: 22px; font-weight: 800;" id="preview_nama">
                        <?= htmlspecialchars($data_anak['nama_siswa']); ?>
                    </h3>
                    <div style="display: inline-block; background: #f8fafc; border: 1px solid #e2e8f0; color: #475569; padding: 6px 16px; border-radius: 20px; font-size: 13px; font-weight: 700; margin-bottom: 20px;">
                        NIS: <?= htmlspecialchars($data_anak['nis']); ?>
                    </div>
                    
                    <p style="color: #64748b; font-size: 13px; line-height: 1.6; margin-bottom: 0; padding: 0 10px;">
                        Klik gambar profil di atas untuk memilih dan mengganti foto Ananda yang baru. Maksimal ukuran file 2MB.
                    </p>

                    <button type="submit" name="update_siswa" class="btn-save-modern">
                        <i class="fas fa-save"></i> Simpan Perubahan
                    </button>
                </div>

                <!-- SISI KANAN: FORMULIR -->
                <div class="card-formulir">
                    <div style="border-bottom: 2px dashed #e2e8f0; padding-bottom: 15px; margin-bottom: 25px; display: flex; align-items: center; gap: 12px;">
                        <i class="fas fa-id-card" style="color: #3b82f6; font-size: 22px;"></i>
                        <h3 style="margin: 0; color: #1e293b; font-size: 20px; font-weight: 800;">Formulir Biodata Anak</h3>
                    </div>

                    <div class="form-grid-inner">
                        <!-- NAMA ORANG TUA (READ-ONLY) -->
                        <div class="form-group span-2" style="grid-column: span 2;">
                            <label class="form-label">Nama Orang Tua / Wali</label>
                            <div class="input-icon-wrapper">
                                <i class="fas fa-user-shield"></i>
                                <input type="text" class="input-modern" value="<?= htmlspecialchars($nama_orangtua); ?>" readonly title="Nama Orang Tua diambil otomatis dari profil akun Anda">
                            </div>
                        </div>

                        <!-- NAMA ANAK -->
                        <div class="form-group span-2" style="grid-column: span 2;">
                            <label class="form-label">Nama Lengkap Anak</label>
                            <div class="input-icon-wrapper">
                                <i class="fas fa-user"></i>
                                <input type="text" name="nama_siswa" class="input-modern" value="<?= htmlspecialchars($data_anak['nama_siswa']); ?>" required oninput="document.getElementById('preview_nama').innerText = this.value || 'Nama Kosong'">
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Nomor Induk Siswa (NIS)</label>
                            <div class="input-icon-wrapper">
                                <i class="fas fa-id-badge"></i>
                                <input type="text" class="input-modern" value="<?= htmlspecialchars($data_anak['nis']); ?>" readonly title="NIS terikat dengan sistem sekolah">
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Rombongan Belajar (Kelas)</label>
                            <div class="input-icon-wrapper">
                                <i class="fas fa-chalkboard-teacher"></i>
                                <input type="text" class="input-modern" value="<?= htmlspecialchars($data_anak['nama_kelas'] ?? 'Belum Ditentukan'); ?>" readonly>
                            </div>
                        </div>

                        <!-- TANGGAL LAHIR -->
                        <div class="form-group">
                            <label class="form-label">Tanggal Lahir</label>
                            <div class="input-icon-wrapper">
                                <i class="fas fa-calendar-alt"></i>
                                <input type="date" name="tgl_lahir" class="input-modern" value="<?= htmlspecialchars($data_anak['tgl_lahir'] ?? ''); ?>">
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Jenis Kelamin</label>
                            <div style="display: flex; gap: 20px; margin-top: 15px;">
                                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                    <input type="radio" name="jk" value="L" <?= ($data_anak['jk'] == 'L') ? 'checked' : ''; ?> style="width: 18px; height: 18px; accent-color: #3b82f6;">
                                    <span style="font-size: 15px; font-weight: 600; color: #1e293b;">Laki-Laki</span>
                                </label>
                                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                    <input type="radio" name="jk" value="P" <?= ($data_anak['jk'] == 'P') ? 'checked' : ''; ?> style="width: 18px; height: 18px; accent-color: #ec4899;">
                                    <span style="font-size: 15px; font-weight: 600; color: #1e293b;">Perempuan</span>
                                </label>
                            </div>
                        </div>

                        <!-- ALAMAT DOMISILI -->
                        <div class="form-group span-2" style="grid-column: span 2; margin-top: 5px;">
                            <label class="form-label">Alamat Domisili Lengkap</label>
                            <textarea name="alamat" class="input-modern no-icon" required placeholder="Masukkan nama jalan, RT/RW, kelurahan..."><?= htmlspecialchars($data_anak['alamat']); ?></textarea>
                        </div>
                    </div>
                </div>
            </form>
        <?php endif; ?>
    </main>

    <!-- SCRIPT PREVIEW FOTO -->
    <script>
        function tampilkanPreview(event) {
            if(event.target.files.length > 0) {
                var reader = new FileReader();
                reader.onload = function(){
                    var output = document.getElementById('preview_foto');
                    output.src = reader.result;
                };
                reader.readAsDataURL(event.target.files[0]);
            }
        }
    </script>
</body>
</html>