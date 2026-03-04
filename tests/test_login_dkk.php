<?php
echo "=== Menjalankan Unit Test Modul Otentikasi & Database ===\n";

// 1. Test Ketersediaan File Konfigurasi dan Koneksi Database
$db_file = '../src/config/db.php';
if (file_exists($db_file)) {
    echo "[SUCCESS] File konfigurasi database ditemukan.\n";
    require_once $db_file;
    // Asumsi variabel koneksimu bernama $pdo, sesuaikan jika namanya $conn
    if (isset($pdo)) {
        echo "[SUCCESS] Koneksi ke database berhasil diinisialisasi.\n";
    } else {
        echo "[WARNING] Variabel koneksi tidak terdeteksi, pastikan penamaan variabel benar.\n";
    }
} else {
    echo "[FAILED] File konfigurasi database tidak ditemukan!\n";
}

// 2. Test Ketersediaan Modul Otentikasi
$auth_files = [
    '../src/auth/login.php',
    '../src/auth/daftar.php',
    '../src/auth/logout.php',
    '../src/dashboard.php'
];

foreach ($auth_files as $file) {
    if (file_exists($file)) {
        echo "[SUCCESS] Modul $file siap digunakan.\n";
    } else {
        echo "[FAILED] Modul $file hilang atau salah tempat!\n";
    }
}

echo "=== Pengujian Selesai ===\n";
?>