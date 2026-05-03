<?php
// Pastikan file db.php sudah di-include sebelum file ini dipanggil
date_default_timezone_set('Asia/Jakarta');

$bulan_sekarang = (int)date('n');
$tahun_sekarang = (int)date('Y');

// STANDAR SPP (Bisa disesuaikan jika nominal tiap kelas beda)
$nominal_spp = 150000; 

try {
    // PERBAIKAN: Hanya ambil siswa yang statusnya 'Aktif'
    // Jangan sampai siswa yang pindah atau lulus terus-terusan ditagih
    $stmtSiswa = $pdo->query("SELECT s.nama_siswa, k.nama_kelas 
                              FROM siswa s 
                              LEFT JOIN kelas k ON s.id_kelas = k.id_kelas
                              WHERE s.status = 'Aktif'");
    $semua_siswa = $stmtSiswa->fetchAll(PDO::FETCH_ASSOC);

    // Looping untuk mengecek tagihan masing-masing siswa
    foreach ($semua_siswa as $siswa) {
        $nama_siswa = $siswa['nama_siswa'];
        $kelas_siswa = $siswa['nama_kelas'] ?? 'Belum Ditentukan';

        // Cek apakah tagihan untuk siswa ini di bulan dan tahun ini SUDAH ADA
        $cekTagihan = $pdo->prepare("SELECT id FROM spp_status WHERE nama = ? AND bulan = ? AND tahun = ?");
        $cekTagihan->execute([$nama_siswa, $bulan_sekarang, $tahun_sekarang]);
        
        // Jika belum ada tagihan, eksekusi INSERT
        // Karena tidak ada "if tanggal >= 2", sistem akan langsung menagih tepat di tanggal 1 setiap bulannya.
        if ($cekTagihan->rowCount() == 0) {
            $insertTagihan = $pdo->prepare("INSERT INTO spp_status (nama, kelas, jumlah, bulan, tahun, status) VALUES (?, ?, ?, ?, ?, 'BELUM')");
            $insertTagihan->execute([$nama_siswa, $kelas_siswa, $nominal_spp, $bulan_sekarang, $tahun_sekarang]);
        }
    }
} catch (PDOException $e) {
    // Error diam-diam (Silent Error)
    error_log("Gagal menjalankan otomasi SPP: " . $e->getMessage());
}
?>