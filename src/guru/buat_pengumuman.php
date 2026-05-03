<?php
session_start();
// PERBAIKAN PATH DATABASE: Mundur 1 folder ke atas '../' karena file ini ada di dalam folder 'guru/'
require_once '../config/db.php'; 
date_default_timezone_set('Asia/Jakarta');

// Proteksi akses Guru
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'guru') {
    header("Location: ../auth/login.php");
    exit();
}

$success_msg = '';
$error_msg = '';

// PROSES SIMPAN PENGUMUMAN
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['kirim_pengumuman'])) {
    $judul = $_POST['judul'];
    $isi = $_POST['isi'];
    $prioritas = $_POST['prioritas'];
    $target = "Orang Tua"; 
    $tanggal = date('Y-m-d');
    $id_user = $_SESSION['id_user'];
    
    $nama_file_db = null;

    // Logika Unggah Foto
    if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] == 0) {
        // PERBAIKAN PATH UPLOAD: Mundur ke '../assets/' karena kita ada di 'guru/'
        $folder_tujuan = "../assets/img/";
        
        // Cek apakah folder assets/img ada, jika tidak buat otomatis
        if (!is_dir($folder_tujuan)) {
            mkdir($folder_tujuan, 0777, true);
        }

        $ekstensi = pathinfo($_FILES['gambar']['name'], PATHINFO_EXTENSION);
        $nama_file_baru = "announcement_" . time() . "." . $ekstensi;
        $target_path = $folder_tujuan . $nama_file_baru;

        if (move_uploaded_file($_FILES['gambar']['tmp_name'], $target_path)) {
            // Yang disimpan ke database hanyalah NAMA FILE-nya, bukan path utuhnya (misal: "announcement_123.jpg")
            // Nanti pas di front-end Orang Tua baru dipanggil: <img src="../assets/img/namafile.jpg">
            $nama_file_db = "img/" . $nama_file_baru; 
        } else {
            $error_msg = "Gagal mengunggah gambar ke server. Pastikan folder memiliki izin akses (write permission).";
        }
    }

    // Eksekusi Database jika tidak ada error upload
    if (empty($error_msg)) {
        try {
            $sql = "INSERT INTO pengumuman (judul, isi, tanggal, target_audiens, prioritas, gambar, id_user) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$judul, $isi, $tanggal, $target, $prioritas, $nama_file_db, $id_user]);
            $success_msg = "Pengumuman berhasil disiarkan ke seluruh Orang Tua!";
        } catch (PDOException $e) {
            // Menangkap error spesifik dari database
            $error_msg = "Error Database: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buat Pengumuman - SIS TKIT FATHUROBANI</title>
    <!-- PERBAIKAN PATH CSS: Arahkan ke file CSS yang baru kita buat -->
    <link rel="stylesheet" href="dashboard.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* CSS Khusus Layout Halaman Ini (Sisa stylenya ngikut dashboard.css) */
        .header-box { margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center; }
        .header-box h1 { font-size: 26px; font-weight: 800; color: #1e293b; margin: 0; }
        .create-grid { display: grid; grid-template-columns: 1.5fr 1fr; gap: 30px; }
        .form-card { background: white; border-radius: 24px; padding: 30px; border: 1px solid #f1f5f9; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.02); }
        .form-group { margin-bottom: 20px; }
        .form-label { display: block; font-size: 13px; font-weight: 700; color: #475569; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px; }
        .form-input { width: 100%; padding: 14px 16px; border-radius: 12px; border: 1px solid #cbd5e1; font-family: 'Inter', sans-serif; font-size: 15px; background: #f8fafc; transition: 0.3s; box-sizing: border-box; outline: none; }
        .form-input:focus { border-color: #3b82f6; background: #fff; box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1); }
        
        .preview-card { background: white; border-radius: 24px; border: 1px solid #f1f5f9; overflow: hidden; position: sticky; top: 30px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.05); }
        .preview-img { width: 100%; height: 200px; object-fit: cover; background: #eff6ff; display: flex; align-items: center; justify-content: center; color: #3b82f6; font-size: 40px; }
        .preview-body { padding: 20px; }
        .preview-tag { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: 800; text-transform: uppercase; margin-bottom: 10px; }
        
        .btn-broadcast { width: 100%; padding: 16px; border-radius: 12px; border: none; background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); color: white; font-weight: 800; font-size: 16px; cursor: pointer; transition: 0.3s; display: flex; align-items: center; justify-content: center; gap: 10px; box-shadow: 0 10px 15px -3px rgba(37, 99, 235, 0.3); margin-top: 15px;}
        .btn-broadcast:hover { transform: translateY(-3px); box-shadow: 0 15px 20px -5px rgba(37, 99, 235, 0.4); }
        
        .upload-area { border: 2px dashed #cbd5e1; border-radius: 12px; padding: 20px; text-align: center; cursor: pointer; transition: 0.3s; background: #f8fafc; }
        .upload-area:hover { border-color: #3b82f6; background: #eff6ff; }
        
        .alert { padding: 16px 20px; border-radius: 12px; margin-bottom: 25px; font-weight: 600; display: flex; align-items: center; gap: 10px; border: 1px solid transparent; }
        .alert-success { background: #ecfdf5; color: #065f46; border-color: #a7f3d0; }
        .alert-danger { background: #fef2f2; color: #dc2626; border-color: #fecaca; }

        @media (max-width: 1024px) {
            .create-grid { grid-template-columns: 1fr; }
            .preview-card { position: static; }
        }
    </style>
</head>
<body>

    <!-- PERBAIKAN PEMANGGILAN SIDEBAR -->
    <?php include 'sidebar.php'; ?>

    <main class="main-content">
        <div class="header-box">
            <h1>Siaran Pengumuman Baru</h1>
            <a href="dashboard.php" style="text-decoration:none; background:#f1f5f9; color:#475569; padding:10px 20px; border-radius:10px; font-weight:700; font-size:13px; border: 1px solid #cbd5e1; display:flex; align-items:center; gap:8px;">
                <i class="fas fa-arrow-left"></i> Kembali
            </a>
        </div>

        <?php if($success_msg): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= $success_msg ?></div>
        <?php endif; ?>

        <?php if($error_msg): ?>
            <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?= $error_msg ?></div>
        <?php endif; ?>

        <div class="create-grid">
            <div class="form-card">
                <form method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <label class="form-label">Judul Pengumuman</label>
                        <input type="text" name="judul" id="input_judul" class="form-input" placeholder="Contoh: Info Libur Semester" required oninput="updatePreview()">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Level Prioritas</label>
                        <select name="prioritas" id="input_prioritas" class="form-input" onchange="updatePreview()">
                            <option value="Sedang">Normal (Biru)</option>
                            <option value="Tinggi">Penting / Darurat (Merah)</option>
                            <option value="Rendah">Hanya Informasi (Abu-abu)</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Unggah Foto Pendukung (Opsional)</label>
                        <div class="upload-area" onclick="document.getElementById('file_gambar').click()">
                            <i class="fas fa-cloud-upload-alt" style="font-size: 30px; color: #94a3b8; margin-bottom: 10px;"></i>
                            <p style="margin: 0; font-size: 14px; color: #64748b;">Klik atau tarik foto ke sini</p>
                            <input type="file" name="gambar" id="file_gambar" hidden accept="image/*" onchange="previewImage(event)">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Isi Pesan Lengkap</label>
                        <textarea name="isi" id="input_isi" class="form-input" rows="6" placeholder="Tulis rincian pengumuman Anda..." required oninput="updatePreview()"></textarea>
                    </div>

                    <button type="submit" name="kirim_pengumuman" class="btn-broadcast">
                        <i class="fas fa-paper-plane"></i> Siarkan Sekarang
                    </button>
                </form>
            </div>

            <div>
                <span class="form-label" style="margin-bottom: 15px; display: block;">Tampilan Pratinjau (Preview)</span>
                <div class="preview-card">
                    <div id="preview_img_container" class="preview-img">
                        <i class="fas fa-image"></i>
                    </div>
                    <div class="preview-body">
                        <span id="preview_badge" class="preview-tag" style="background:#eff6ff; color:#2563eb;">UMUM</span>
                        <h2 id="view_judul" style="margin: 0 0 10px 0; font-size: 20px; font-weight: 800;">Judul Pengumuman</h2>
                        <p id="view_isi" style="font-size: 14px; color: #64748b; line-height: 1.6; margin: 0;">Isi pengumuman Anda...</p>
                        <hr style="border: 0; border-top: 1px solid #f1f5f9; margin: 20px 0;">
                        <span style="font-size: 11px; color: #94a3b8; font-weight: 600;"><i class="fas fa-clock"></i> Baru Saja &bull; Oleh Anda</span>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
        // Logika untuk pratinjau gambar yang dipilih
        function previewImage(event) {
            const container = document.getElementById('preview_img_container');
            const file = event.target.files[0];
            const reader = new FileReader();

            reader.onload = function(e) {
                container.innerHTML = `<img src="${e.target.result}" style="width:100%; height:100%; object-fit:cover;">`;
            }
            reader.readAsDataURL(file);
        }

        // Logika untuk mengubah teks pratinjau secara real-time
        function updatePreview() {
            document.getElementById('view_judul').innerText = document.getElementById('input_judul').value || "Judul Pengumuman";
            document.getElementById('view_isi').innerText = document.getElementById('input_isi').value || "Isi pengumuman Anda...";
            
            const prio = document.getElementById('input_prioritas').value;
            const badge = document.getElementById('preview_badge');
            
            if(prio === 'Tinggi') {
                badge.style.background = '#fef2f2'; badge.style.color = '#dc2626'; badge.innerText = 'PENTING';
            } else if(prio === 'Rendah') {
                badge.style.background = '#f8fafc'; badge.style.color = '#64748b'; badge.innerText = 'INFO';
            } else {
                badge.style.background = '#eff6ff'; badge.style.color = '#2563eb'; badge.innerText = 'UMUM';
            }
        }
    </script>
</body>
</html>