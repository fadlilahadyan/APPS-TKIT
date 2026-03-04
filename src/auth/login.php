<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


require_once '../config/db.php';

$error_message = '';
$success_message = '';

// Cek jika ada pesan sukses dari halaman daftar
if (isset($_GET['status']) && $_GET['status'] == 'sukses') {
    $success_message = "Pendaftaran berhasil! Silakan masuk.";
}

// LOGIKA LOGIN MULAI DI SINI
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $identitas = trim($_POST['identitas']);
    $password = $_POST['password'];
    $role = $_POST['role'];

    if (empty($identitas) || empty($password) || empty($role)) {
        $error_message = "Semua kolom wajib diisi!";
    } else {
        // Ambil data user berdasarkan username/email DAN role
        $sql = "SELECT * FROM users WHERE (username = :identitas OR email = :identitas) AND role = :role";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'identitas' => $identitas,
            'role'      => $role
        ]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Verifikasi Password
        if ($user && password_verify($password, $user['password'])) {
            // Set Session
            $_SESSION['id_user'] = $user['id_user'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['nama_lengkap'] = $user['nama_lengkap'];

            // REDIRECT BERDASARKAN ROLE
            if ($user['role'] === 'guru') {
                header("Location: ../dashboard.php");
            } elseif ($user['role'] === 'orang_tua') {
                header("Location: orang_tua/dashboard.php");
            }
            exit();
        } else {
            $error_message = "Username/Email atau password salah untuk peran ini!";
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
            <span>Guru</span> dan <span>Orang Tua</span> <br>
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
            <p class="subtitle">Baru di sistem ini? Yuuuu mending <a href="daftar.php">Daftar Sekarang</a></p>

            <?php if (!empty($error_message)): ?>
                <div class="alert"><?= htmlspecialchars($error_message) ?></div>
            <?php endif; ?>

            <form action="login.php" method="POST">
                <div class="input-group">
                    <input type="text" name="identitas" placeholder="Contoh: email@example.com" required>
                </div>
                <div class="input-group">
                    <input type="password" name="password" placeholder="Masukkan kata sandi kamu" required>
                </div>
                <div class="input-group">
                    <select name="role" required>
                        <option value="" disabled selected>-- Pilih Peran Kamu --</option>
                        <option value="guru">Guru</option>
                    </select>
                </div>
                <button type="submit" class="btn-submit">Masuk</button>
            </form>
<?php if (!empty($success_message)): ?>
    <div class="alert" style="background-color: #dcfce7; color: #166534; border-color: #86efac;">
        <?= htmlspecialchars($success_message) ?>
    </div>
<?php endif; ?>
            <p class="footer-text">
                Dengan melanjutkan, kamu menerima <a href="#">Syarat Penggunaan</a> dan <br> <a href="#">Kebijakan Privasi</a> sekolah.
            </p>
        </div>
    </div>
</body>
</html>
