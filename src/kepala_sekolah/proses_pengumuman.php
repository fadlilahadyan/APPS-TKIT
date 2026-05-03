<?php
session_start();
require_once '../config/db.php';

if (isset($_POST['submit'])) {
    $judul     = trim($_POST['judul']);
    $isi       = trim($_POST['isi']);
    $prioritas = $_POST['prioritas']; // Akan berisi 'rendah', 'Sedang', atau 'Tinggi'
    $target    = $_POST['target'];
    $tanggal   = date('Y-m-d');
    $id_user   = $_SESSION['id_user']; // Pastikan session id_user ada

    try {
        // Query disesuaikan dengan urutan kolom di SQL: judul, isi, prioritas, target_audiens, tanggal, id_user
        $sql = "INSERT INTO pengumuman (judul, isi, prioritas, target_audiens, tanggal, id_user) 
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$judul, $isi, $prioritas, $target, $tanggal, $id_user]);

        header("Location: pengumuman.php?status=sukses");
        exit();
    } catch (PDOException $e) {
        // Jika error muncul lagi, kita bisa baca pesan detailnya di sini
        die("Koneksi gagal atau data tidak sinkron: " . $e->getMessage());
    }
} else {
    header("Location: pengumuman.php");
    exit();
}