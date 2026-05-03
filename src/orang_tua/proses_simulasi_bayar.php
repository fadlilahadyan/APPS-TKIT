<?php
session_start();
require_once '../config/db.php';

// Pastikan request adalah POST dan actionnya sesuai
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'bayar_simulasi') {
    
    $nama = $_POST['nama'];
    $bulan = (int)$_POST['bulan'];
    $tahun = (int)$_POST['tahun'];
    $metode = $_POST['metode'] ?? 'Simulasi System';
    $tanggal_sekarang = date('Y-m-d');

    $nama_bulan_arr = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    $nama_bulan_teks = $nama_bulan_arr[$bulan];

    try {
        // MULAI TRANSAKSI
        $pdo->beginTransaction();

        // 1. Ambil id_siswa dan nama kelas untuk kelengkapan data
        $stmtSiswa = $pdo->prepare("SELECT s.id_siswa, k.nama_kelas FROM siswa s LEFT JOIN kelas k ON s.id_kelas = k.id_kelas WHERE s.nama_siswa = ? LIMIT 1");
        $stmtSiswa->execute([$nama]);
        $siswa = $stmtSiswa->fetch();
        
        $id_siswa = $siswa ? $siswa['id_siswa'] : NULL;
        $nama_kelas = $siswa ? ($siswa['nama_kelas'] ?? 'Belum Ditentukan') : 'Belum Ditentukan';
        
        $jumlah_bayar = 150000; // Nominal standar jika tagihan baru diciptakan otomatis

        // 2. Cek apakah tagihan sudah ada di tabel spp_status
        $stmtCek = $pdo->prepare("SELECT id, status, jumlah FROM spp_status WHERE nama = ? AND bulan = ? AND tahun = ?");
        $stmtCek->execute([$nama, $bulan, $tahun]);
        $tagihan = $stmtCek->fetch();

        if ($tagihan) {
            // Jika tagihan ADA, cek statusnya
            if ($tagihan['status'] === 'LUNAS') {
                echo json_encode(['status' => 'error', 'message' => 'Tagihan bulan ini sudah lunas!']);
                $pdo->rollBack();
                exit;
            }
            // Jika BELUM lunas, lakukan UPDATE
            $jumlah_bayar = $tagihan['jumlah'];
            $stmtUpdate = $pdo->prepare("UPDATE spp_status SET status = 'LUNAS', tanggal_bayar = ? WHERE id = ?");
            $stmtUpdate->execute([$tanggal_sekarang, $tagihan['id']]);
        } else {
            // Jika tagihan TIDAK ADA (Tagihan masa lalu), lakukan INSERT otomatis berstatus LUNAS
            $stmtInsertStatus = $pdo->prepare("INSERT INTO spp_status (nama, kelas, jumlah, bulan, tahun, tanggal_bayar, status) VALUES (?, ?, ?, ?, ?, ?, 'LUNAS')");
            $stmtInsertStatus->execute([$nama, $nama_kelas, $jumlah_bayar, $bulan, $tahun, $tanggal_sekarang]);
        }

        // 3. INSERT riwayat transaksi ke pembayaran_spp (Buku Besar)
        $stmtInsert = $pdo->prepare("INSERT INTO pembayaran_spp (id_siswa, jenis, periode, tanggal_bayar, bulan, jumlah_bayar, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmtInsert->execute([$id_siswa, $metode, $tahun, $tanggal_sekarang, $nama_bulan_teks, $jumlah_bayar, 'LUNAS']);

        // SELESAI TRANSAKSI
        $pdo->commit();

        echo json_encode(['status' => 'success', 'message' => 'Pembayaran berhasil dan riwayat tercatat di sistem']);

    } catch (PDOException $e) {
        $pdo->rollBack();
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Akses ditolak secara ilegal']);
}
?>