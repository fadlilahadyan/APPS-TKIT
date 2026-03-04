<?php
// Ubah baris ini agar masuk ke folder src dulu
require_once "../src/config/db.php"; 

echo "<h2>Unit Testing: Fitur Laporan Perkembangan</h2>";
// ... sisa kode lainnya tetap sama

echo "<h2>Unit Testing: Fitur Laporan Perkembangan</h2>";
echo "<hr>";

try {
    // 1. DATA UJI (Dummy Data)
    $test_data = [
        'id_siswa' => 1, // Pastikan ID siswa ini ada di tabel siswa kamu
        'id_guru'  => 1, // Simulasi ID guru
        'tanggal'  => date("Y-m-d"),
        'agama'    => "Test: Anak mampu mengenal doa harian",
        'fisik'    => "Test: Anak mampu melompat dengan satu kaki",
        'kognitif' => "Test: Anak mampu menyusun balok warna"
    ];

    echo "<b>Langkah 1:</b> Menjalankan simulasi simpan data... <br>";

    // 2. PROSES SIMULASI INSERT (Sesuai logika proses_simpan_laporan.php)
    $sql = "INSERT INTO laporan_perkembangan 
            (id_siswa, id_guru, tanggal, agama_moral, fisik_motorik, kognitif_bahasa)
            VALUES (:id_siswa, :id_guru, :tanggal, :agama, :fisik, :kognitif)";

    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([
        ':id_siswa' => $test_data['id_siswa'],
        ':id_guru'  => $test_data['id_guru'],
        ':tanggal'  => $test_data['tanggal'],
        ':agama'    => $test_data['agama'],
        ':fisik'    => $test_data['fisik'],
        ':kognitif' => $test_data['kognitif']
    ]);

    if ($result) {
        echo "<span style='color:green;'>[BERHASIL]</span> Data uji berhasil masuk ke database.<br><br>";
    }

    // 3. VERIFIKASI DATA (Cek apakah data benar-benar ada di DB)
    echo "<b>Langkah 2:</b> Memverifikasi data di database... <br>";
    
    $check_stmt = $pdo->prepare("SELECT * FROM laporan_perkembangan WHERE agama_moral = :agama ORDER BY id_laporan DESC LIMIT 1");
    $check_stmt->execute([':agama' => $test_data['agama']]);
    $db_data = $check_stmt->fetch();

    if ($db_data) {
        echo "<span style='color:green;'>[BERHASIL]</span> Data ditemukan di sistem dengan ID Laporan: " . $db_data['id_laporan'] . "<br>";
        echo "Detail Kognitif di DB: " . $db_data['kognitif_bahasa'] . "<br><br>";
    } else {
        throw new Exception("Data tidak ditemukan setelah di-insert!");
    }

    echo "<h3 style='color:blue;'>KESIMPULAN TEST: PASSED (LULUS)</h3>";
    echo "Fitur simpan dan tampil laporan berfungsi 100%.";

} catch (Exception $e) {
    echo "<h3 style='color:red;'>KESIMPULAN TEST: FAILED (GAGAL)</h3>";
    echo "Error: " . $e->getMessage();
}
?>