<?php
session_start();
require_once "../config/db.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    if (
        empty($_POST['id_siswa']) ||
        empty($_POST['agama_moral']) ||
        empty($_POST['fisik_motorik']) ||
        empty($_POST['kognitif_bahasa'])
    ) {
        echo "<script>alert('Semua field wajib diisi!'); window.location='laporan.php';</script>";
        exit();
    }

    try {
        $id_siswa = $_POST['id_siswa'];
        $id_guru  = $_SESSION['id_user'];
        $tanggal  = date("Y-m-d");
        $agama    = $_POST['agama_moral'];
        $fisik    = $_POST['fisik_motorik'];
        $kognitif = $_POST['kognitif_bahasa'];

        $sql = "INSERT INTO laporan_perkembangan 
                (id_siswa, id_guru, tanggal, agama_moral, fisik_motorik, kognitif_bahasa)
                VALUES (:id_siswa, :id_guru, :tanggal, :agama, :fisik, :kognitif)";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':id_siswa' => $id_siswa,
            ':id_guru'  => $id_guru,
            ':tanggal'  => $tanggal,
            ':agama'    => $agama,
            ':fisik'    => $fisik,
            ':kognitif' => $kognitif
        ]);

        echo "<script>alert('Data berhasil disimpan!'); window.location='laporan.php';</script>";

    } catch (PDOException $e) {
        die("Gagal Simpan: " . $e->getMessage());
    }
}