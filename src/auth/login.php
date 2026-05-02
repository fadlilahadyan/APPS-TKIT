<?php

/**
 * LOGIN SYSTEM - SIS TKIT FATHURROBBANY
 * Disusun untuk tugas RBPL - Role: Guru, Orang Tua, Bendahara, Kepala Sekolah, Operator
 */

// 1. Inisialisasi Error Reporting untuk Debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 2. Jalankan Session & Koneksi Database

require_once '../config/db.php';

$error_message = '';
$success_message = '';

// 3. Cek Status Registrasi dari URL
if (isset($_GET['status']) && $_GET['status'] == 'sukses') {
    $success_message = "Pendaftaran berhasil! Silakan masuk.";
}

// 4. Logika Pemrosesan Form Login
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $identitas = trim($_POST['identitas']);
    $password  = $_POST['password'];
    $role      = $_POST['role'];

    if (empty($identitas) || empty($password) || empty($role)) {
        $error_message = "Semua kolom wajib diisi!";
    } else {
        try {
            // Ambil data user berdasarkan username/email DAN role
            $sql = "SELECT * FROM users WHERE (username = :identitas OR email = :identitas) AND role = :role";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                'identitas' => $identitas,
                'role'      => $role
            ]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            // Verifikasi Password menggunakan password_verify
            if ($user && password_verify($password, $user['password'])) {
                // Set Session User
                $_SESSION['id_user']       = $user['id_user'];
                $_SESSION['username']      = $user['username'];
                $_SESSION['role']          = $user['role'];
                $_SESSION['nama_lengkap']  = $user['nama_lengkap'];

                // LOGIKA REDIRECT BERDASARKAN ROLE
                switch ($user['role']) {
                    case 'guru':
                        header("Location: ../guru/dashboard.php");
                        break;
                    case 'orang_tua':
                        header("Location: ../orang_tua/dashboard.php");
                        break;
                    case 'bendahara':
                        header("Location: ../bendahara/dashboard.php");
                        break;
                    case 'kepala_sekolah':
                        header("Location: ../kepala_sekolah/dashboard.php");
                        break;
                    case 'operator':
                        header("Location: ../operator/dashboard.php");
                        break;
                    default:
                        $error_message = "Role tidak dikenali dalam sistem!";
                        break;
                }
                exit();
            } else {
                $error_message = "Username/Email atau password salah untuk peran ini!";
            }
        } catch (PDOException $e) {
            $error_message = "Terjadi kesalahan pada sistem. Silakan coba lagi.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - SIS TKIT Fathurrobbany</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>

<body>

    <div class="login-left">
        <img src="../assets/pramuka.jpg" alt="Kegiatan Siswa" class="background-photo">
        <div class="image-overlay"></div>
        <h1 class="text-hero">
            Permudah interaksi antar <br>
            <span>Guru</span>, <span>Bendahara</span>, dan <span>Orang Tua</span> <br>
            secara online!
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

            <h2>Hai, selamat datang</h2>
            <p class="subtitle">Baru di sistem ini? <a href="daftar.php">Daftar Sekarang</a></p>

            <?php if (!empty($error_message)): ?>
                <div class="alert" style="background-color: #fee2e2; color: #991b1b; border: 1px solid #fecaca; padding: 12px; border-radius: 8px; margin-bottom: 20px;">
                    <?= htmlspecialchars($error_message) ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($success_message)): ?>
                <div class="alert" style="background-color: #dcfce7; color: #166534; border: 1px solid #86efac; padding: 12px; border-radius: 8px; margin-bottom: 20px;">
                    <?= htmlspecialchars($success_message) ?>
                </div>
            <?php endif; ?>

            <form action="login.php" method="POST">
                <div class="input-group">
                    <input type="text" name="identitas" placeholder="Username atau Email" required>
                </div>
                <div class="input-group">
                    <input type="password" name="password" placeholder="Masukkan kata sandi kamu" required>
                </div>
                <div class="input-group">
                    <select name="role" required>
                        <option value="" disabled selected>-- Pilih Peran Kamu --</option>
                        <option value="guru">Guru</option>
                        <option value="bendahara">Bendahara (SPP)</option>
                        <option value="orang_tua">Orang Tua</option>
                        <option value="kepala_sekolah">Kepala Sekolah</option>
                        <option value="operator">Operator</option>
                    </select>
                </div>
                <button type="submit" class="btn-submit">Masuk ke Sistem</button>
                <div style="text-align: right; margin-bottom: 15px;">
                    <a href="lupa_sandi.php" style="color: #3b82f6; font-size: 13px; font-weight: 600; text-decoration: none; transition: 0.2s;">Lupa Kata Sandi?</a>
                </div>
            </form>

            <p class="footer-text">
                Dengan melanjutkan, kamu menerima <a href="#">Syarat Penggunaan</a> dan <br> <a href="#">Kebijakan Privasi</a> sekolah.
            </p>
        </div>
    </div>
</body>

</html>