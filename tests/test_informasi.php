<?php
echo "=== Menjalankan Unit Test Modul Informasi SPP ===\n";

// 1. Cek Koneksi Database
echo "\n--- Pengecekan Koneksi Database ---\n";
// Sesuaikan path ini dengan letak db.php dari folder tests/
$db_file = __DIR__ . '/../src/config/db.php'; 

if (file_exists($db_file)) {
    require_once $db_file;
    if (isset($pdo)) {
        echo "[SUCCESS] Koneksi database untuk modul SPP berhasil.\n";

        // 2. Cek Ketersediaan Tabel spp_status
        echo "\n--- Pengecekan Kesiapan Tabel & CRUD ---\n";
        try {
            // Test READ: Memastikan tabel bisa diakses
            $stmt = $pdo->query("SELECT 1 FROM spp_status LIMIT 1");
            echo "[SUCCESS] Tabel 'spp_status' tersedia dan dapat diakses.\n";

            // Test CREATE: Simulasi insert data dummy SPP
            $pdo->exec("INSERT INTO spp_status (nama, kelas, jumlah, bulan, tahun, status) VALUES ('Test Siswa Dummy', 'TK A', 150000, 3, 2026, 'BELUM')");
            echo "[SUCCESS] Fungsi Insert (CREATE) ke tabel SPP berjalan normal.\n";

            // Test DELETE: Menghapus kembali data dummy agar database tetap bersih
            $pdo->exec("DELETE FROM spp_status WHERE nama = 'Test Siswa Dummy'");
            echo "[SUCCESS] Fungsi Hapus (DELETE) data SPP berjalan normal.\n";

        } catch (PDOException $e) {
            echo "[WARNING] Tabel 'spp_status' belum ada atau terjadi error: " . $e->getMessage() . "\n";
            echo "[INFO] Karena kamu menggunakan auto-create, pastikan file status_spp.php dibuka minimal satu kali di browser agar tabel terbentuk otomatis.\n";
        }

    } else {
        echo "[FAILED] Variabel koneksi (\$pdo) tidak ditemukan!\n";
    }
} else {
    echo "[FAILED] File konfigurasi database tidak ditemukan di path: $db_file\n";
}

echo "\n=== Pengujian Modul SPP Selesai ===\n";
?>