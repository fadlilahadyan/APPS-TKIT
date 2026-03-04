<?php
echo "=== Menjalankan Unit Test Modul Dashboard Guru ===\n";

// 1. Cek ketersediaan file antarmuka dashboard
$dashboard_files = [
    '../src/dashboard.php',
    '../src/dashboard.css'
];

echo "\n--- Pengecekan File Antarmuka ---\n";
foreach ($dashboard_files as $file) {
    if (file_exists($file)) {
        echo "[SUCCESS] File $file ditemukan dan siap digunakan.\n";
    } else {
        echo "[FAILED] File $file tidak ditemukan pada direktori yang tepat!\n";
    }
}

// 2. Cek Koneksi Database & Ketersediaan Tabel Statistik
echo "\n--- Pengecekan Kesiapan Query Database ---\n";
$db_file = '../src/config/db.php';
if (file_exists($db_file)) {
    require_once $db_file;
    if (isset($pdo)) {
        echo "[SUCCESS] Koneksi database untuk dashboard berhasil.\n";

        // Simulasi pengecekan tabel yang di-query oleh dashboard.php
        $tabel_dibutuhkan = ['siswa', 'absensi', 'pengumuman'];
        foreach ($tabel_dibutuhkan as $tabel) {
            try {
                // Mencoba melakukan query ringan untuk memastikan tabel eksis
                $stmt = $pdo->query("SELECT 1 FROM $tabel LIMIT 1");
                echo "[SUCCESS] Tabel '$tabel' tersedia dan siap menyuplai data statistik.\n";
            } catch (PDOException $e) {
                echo "[WARNING] Tabel '$tabel' belum ada di database atau tidak dapat diakses.\n";
            }
        }
    }
} else {
    echo "[FAILED] Gagal memuat konfigurasi database untuk dashboard!\n";
}

echo "\n=== Pengujian Dashboard Selesai ===\n";
?>