<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();

require_once 'config/db.php';

$error_message = '';
$success_message = '';

// Cek jika ada pesan sukses dari halaman daftar
if (isset($_GET['status']) && $_GET['status'] == 'sukses') {
    $success_message = "Pendaftaran berhasil! Silakan masuk.";
}

// LOGIKA LOGIN
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
                header("Location: presensi/presensi.php"); 
            } elseif ($user['role'] === 'orang_tua') {
                header("Location: orangtua/dashboard.php");
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
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { display: flex; min-height: 100vh; }
        
        .login-left {
            flex: 1;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            position: relative;
            overflow: hidden;
        }
        
        .background-photo {
            position: absolute;
            width: 100%;
            height: 100%;
            object-fit: cover;
            opacity: 0.3;
        }
        
        .image-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.9) 0%, rgba(118, 75, 162, 0.9) 100%);
        }
        
        .text-hero {
            position: relative;
            z-index: 1;
            color: white;
            font-size: 48px;
            font-weight: 800;
            padding: 60px;
            line-height: 1.2;
        }
        
        .text-hero span {
            color: #ffd700;
            border-bottom: 4px solid #ffd700;
        }
        
        .login-right {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f8fafc;
        }
        
        .form-container {
            max-width: 400px;
            width: 100%;
            padding: 40px;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }
        
        .logo-container {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 40px;
            color: #2563eb;
            font-weight: 700;
            font-size: 20px;
        }
        
        .logo-container svg {
            width: 32px;
            height: 32px;
        }
        
        h2 {
            font-size: 28px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 10px;
        }
        
        .subtitle {
            color: #64748b;
            margin-bottom: 30px;
        }
        
        .subtitle a {
            color: #2563eb;
            text-decoration: none;
            font-weight: 600;
        }
        
        .alert {
            padding: 16px;
            background: #fee2e2;
            color: #991b1b;
            border-radius: 12px;
            margin-bottom: 20px;
            border-left: 4px solid #ef4444;
        }
        
        .input-group {
            margin-bottom: 20px;
        }
        
        .input-group input, .input-group select {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 15px;
            transition: all 0.2s;
        }
        
        .input-group input:focus, .input-group select:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37,99,235,0.1);
        }
        
        .btn-submit {
            width: 100%;
            padding: 14px;
            background: #2563eb;
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s;
            margin-top: 10px;
        }
        
        .btn-submit:hover {
            background: #1d4ed8;
            transform: translateY(-2px);
            box-shadow: 0 10px 20px -5px rgba(37,99,235,0.3);
        }
        
        .footer-text {
            text-align: center;
            margin-top: 30px;
            color: #64748b;
            font-size: 14px;
        }
        
        .footer-text a {
            color: #2563eb;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="login-left">
        <img src="assets/pramuka.jpg" alt="Kegiatan Siswa" class="background-photo">
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
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M22 10v6M2 10l10-5 10 5-10 5z"></path>
                    <path d="M6 12v5c3 3 9 3 12 0v-5"></path>
                </svg>
                SISTEM INFORMASI TKIT
            </div>

            <h2>Hai, selamat datang</h2>
            <p class="subtitle">Baru di sistem ini? <a href="daftar.php">Daftar Sekarang</a></p>

            <?php if (!empty($error_message)): ?>
                <div class="alert"><?= htmlspecialchars($error_message) ?></div>
            <?php endif; ?>

            <form action="login.php" method="POST">
                <div class="input-group">
                    <input type="text" name="identitas" placeholder="Email atau Username" required>
                </div>
                <div class="input-group">
                    <input type="password" name="password" placeholder="Kata Sandi" required>
                </div>
                <div class="input-group">
                    <select name="role" required>
                        <option value="" disabled selected>-- Pilih Peran --</option>
                        <option value="guru">Guru</option>
                        <option value="orang_tua">Orang Tua Murid</option>
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
                Dengan melanjutkan, kamu menerima <a href="#">Syarat Penggunaan</a> dan <a href="#">Kebijakan Privasi</a> sekolah.
            </p>
        </div>
    </div>
</body>
</html>