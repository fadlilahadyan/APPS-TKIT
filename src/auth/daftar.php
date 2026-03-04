<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


// koneksi database
require_once '../config/db.php';

// Variabel untuk menyimpan pesan error atau sukses
$error_message = '';
$success_message = '';

// LOGIKA PENDAFTARAN MULAI DI SINI
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nama = trim($_POST['nama_lengkap']);
    $identitas = trim($_POST['identitas']);
    $password = $_POST['password'];
    $role = $_POST['role'];
    // Validasi input sederhana
    if (empty($nama) || empty($identitas) || empty($password) || empty($role)) {
        $error_message = "Semua kolom wajib diisi!";
    } else {
        try {
            // Hash password!
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // memasukan data ke database 
            $sql = "INSERT INTO users (nama_lengkap, username, password, role) 
                    VALUES (:nama, :identitas, :password, :role)";
            // KUNCI: Gunakan prepared statement untuk keamanan maksimal
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                'nama'      => $nama,
                'identitas' => $identitas,
                'password'  => $hashed_password,
                'role'      => $role
            ]);

            // KUNCI: Redirect ke login dengan parameter sukses
            header("Location: login.php?status=sukses");
            exit(); // WAJIB ada exit setelah header redirect
        //menangkap error jika terjadi masalah saat memasukan data ke database
        } catch (PDOException $e) {
            $error_message = "Gagal daftar: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Akun - SIS TKIT Fathurrobbany</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body class="flex-reverse"> <div class="login-left">
        <img src="../assets/pramuka.jpg" alt="Kegiatan Siswa" class="background-photo">
        <div class="image-overlay"></div>
        <h1 class="text-hero">
            Bergabunglah dengan <br>
            <span>Komunitas Sekolah</span> <br>
            Fathurrobbany
        </h1>
    </div>

    <div class="login-right">
        <div class="form-container">
            <div class="logo-container">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 10v6M2 10l10-5 10 5-10 5z"></path>
                    <path d="M6 12v5c3 3 9 3 12 0v-5"></path>
                </svg>
                TKIT FATHUROBANI
            </div>

            <h2>Buat Akun Baru</h2>
            <p class="subtitle">Sudah punya akun? <a href="login.php">Masuk di sini</a></p>

            <?php if (!empty($error_message)): ?>
                <div class="alert"><?= htmlspecialchars($error_message) ?></div>
            <?php endif; ?>
            
            <?php if (!empty($success_message)): ?>
                <div class="alert" style="background-color: #dcfce7; color: #166534; border-color: #86efac;">
                    <?= htmlspecialchars($success_message) ?>
                </div>
            <?php endif; ?>

            <form action="daftar.php" method="POST">
                <div class="input-group">
                    <input type="text" name="nama_lengkap" placeholder="Nama Lengkap" required>
                </div>
                
                <div class="input-group">
                    <input type="text" name="identitas" placeholder="Email atau Username" required>
                </div>
                
                <div class="input-group">
                    <input type="password" name="password" placeholder="Buat Kata Sandi" required>
                </div>

                <div class="input-group">
                    <select name="role" required>
                        <option value="" disabled selected>-- Daftar Sebagai --</option>
                        <option value="guru">Guru</option>
                        <option value="orang_tua">Orang Tua</option>
                    </select>
                </div>

                <button type="submit" class="btn-submit">Daftar Sekarang</button>
            </form>

            <p class="footer-text">
                Dengan mendaftar, kamu menyetujui <a href="#">Syarat dan Ketentuan</a> kami.
            </p>
        </div>
    </div>

</body>
</html>