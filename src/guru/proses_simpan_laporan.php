<?php
session_start();
require_once "../config/db.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    if (empty($_POST['id_siswa'])) {
        echo "<script>alert('Siswa wajib dipilih!'); window.location='laporan.php';</script>";
        exit();
    }

    try {
        $id_siswa = $_POST['id_siswa'];
        $id_guru  = $_SESSION['id_user'];
        $tanggal  = date("Y-m-d");

        // Mengambil 6 Aspek Standar PAUD dari Form
        $aspek_data = [
            'Agama & Moral'    => $_POST['agama_moral'] ?? '',
            'Fisik Motorik'    => $_POST['fisik_motorik'] ?? '',
            'Kognitif'         => $_POST['kognitif'] ?? '',
            'Bahasa'           => $_POST['bahasa'] ?? '',
            'Sosial Emosional' => $_POST['sosial_emosional'] ?? '',
            'Seni'             => $_POST['seni'] ?? ''
        ];

        // Gunakan Transaction agar jika 1 gagal, gagal semua (aman untuk presentasi)
        $pdo->beginTransaction();

        $sql = "INSERT INTO perkembangan 
                (id_laporan, id_siswa, id_guru, tanggal, aspek, deskripsi)
                VALUES (:id_laporan, :id_siswa, :id_guru, :tanggal, :aspek, :deskripsi)";
        $stmt = $pdo->prepare($sql);

        // Looping untuk memasukkan setiap aspek yang diisi oleh guru
        foreach ($aspek_data as $nama_aspek => $deskripsi) {
            if (!empty(trim($deskripsi))) {
                // Generate ID Laporan secara manual karena varchar(10) bukan auto-increment
                $id_laporan = 'LAP' . strtoupper(substr(uniqid(), -5)); 

                $stmt->execute([
                    ':id_laporan' => $id_laporan,
                    ':id_siswa'   => $id_siswa,
                    ':id_guru'    => $id_guru,
                    ':tanggal'    => $tanggal,
                    ':aspek'      => $nama_aspek,
                    ':deskripsi'  => trim($deskripsi)
                ]);
            }
        }

        $pdo->commit();
        echo "<script>alert('Laporan ke-6 Aspek berhasil disimpan!'); window.location='laporan.php';</script>";

    } catch (PDOException $e) {
        $pdo->rollBack();
        die("Gagal Simpan: " . $e->getMessage());
    }
}
?>