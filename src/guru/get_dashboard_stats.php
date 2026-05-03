<?php
session_start();
require_once '../config/db.php';
date_default_timezone_set('Asia/Jakarta');

if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'guru') {
    exit();
}

$id_user = $_SESSION['id_user'];
$hari_ini = date('Y-m-d');
$bulan_sekarang = (int)date('n');
$tahun_sekarang = (int)date('Y');

try {
    // Total Siswa Aktif
    $stmtSiswa = $pdo->query("SELECT COUNT(id_siswa) FROM siswa WHERE status = 'Aktif'");
    $total_siswa = (int)$stmtSiswa->fetchColumn();

    // Hadir Hari Ini
    $stmtHadir = $pdo->prepare("SELECT COUNT(id_absen) FROM absensi WHERE tanggal = ? AND status = 'Hadir'");
    $stmtHadir->execute([$hari_ini]);
    $hadir_hari_ini = (int)$stmtHadir->fetchColumn();

    // Belum Bayar SPP
    $stmtLunas = $pdo->prepare("SELECT COUNT(id) FROM spp_status WHERE bulan = ? AND tahun = ? AND status = 'LUNAS'");
    $stmtLunas->execute([$bulan_sekarang, $tahun_sekarang]);
    $sudah_bayar = (int)$stmtLunas->fetchColumn();
    $belum_bayar_spp = max(0, $total_siswa - $sudah_bayar);

    // Kirim JSON
    echo json_encode([
        'total_siswa' => $total_siswa,
        'hadir_hari_ini' => $hadir_hari_ini,
        'belum_bayar_spp' => $belum_bayar_spp
    ]);
} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}