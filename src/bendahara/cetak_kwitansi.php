<?php
session_start();
require_once '../config/db.php';

// Proteksi akses: Hanya bendahara yang bisa mencetak kwitansi resmi
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'bendahara') {
    die("Akses ditolak.");
}

$id = $_GET['id'] ?? 0;

// Update Query: Mengambil data pembayaran, siswa, dan nama orang tua yang tertaut
// Pastikan nama tabel user kamu adalah 'users' atau sesuaikan jika berbeda
$stmt = $pdo->prepare("SELECT p.*, s.nama_siswa, s.nis, u.nama_lengkap AS nama_ortu 
                       FROM pembayaran_spp p 
                       JOIN siswa s ON p.id_siswa = s.id_siswa 
                       LEFT JOIN users u ON s.id_ortu = u.id_user
                       WHERE p.id_bayar = ?");
$stmt->execute([$id]);
$data = $stmt->fetch();

if (!$data) die("Data transaksi tidak ditemukan.");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kwitansi #<?= $data['id_bayar'] ?></title>
    <style>
        body { font-family: 'Courier New', Courier, monospace; color: #333; padding: 20px; }
        .receipt-box { width: 600px; border: 2px dashed #333; padding: 30px; margin: auto; background: #fff; }
        .header { text-align: center; border-bottom: 2px solid #333; padding-bottom: 10px; margin-bottom: 20px; }
        .header h2 { margin: 0; font-size: 22px; color: #1e293b; }
        .header p { margin: 5px 0 0; font-size: 12px; font-style: italic; }
        .info-row { display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 14px; }
        .total-box { background: #f1f5f9; padding: 15px; text-align: center; font-size: 24px; font-weight: bold; margin: 20px 0; border: 1px solid #333; }
        .footer { display: flex; justify-content: space-between; margin-top: 40px; }
        .signature { text-align: center; width: 200px; }
        .signature p { margin-bottom: 60px; }
        @media print { .btn-print { display: none; } }
    </style>
</head>
<body onload="window.print()"> 
    <div class="receipt-box">
        <div class="header">
            <h2>TKIT FATHURROBBANY</h2>
            <p>Bukti Pembayaran SPP Resmi & Sah</p>
        </div>

        <div class="info-row">
            <span>No. Kwitansi:</span>
            <strong>#SPP-<?= $data['id_bayar'] ?></strong>
        </div>
        <div class="info-row">
            <span>Tanggal Cetak:</span>
            <span><?= date('d F Y', strtotime($data['tanggal_bayar'])) ?></span>
        </div>
        <hr>
        <div class="info-row">
            <span>Telah Diterima Dari:</span>
            <strong><?= strtoupper(htmlspecialchars($data['nama_ortu'] ?? 'Wali Murid')) ?></strong> </div>
        <div class="info-row">
            <span>Nama Siswa:</span>
            <strong><?= strtoupper(htmlspecialchars($data['nama_siswa'])) ?></strong>
        </div>
        <div class="info-row">
            <span>NIS Murid:</span>
            <span><?= htmlspecialchars($data['nis']) ?></span>
        </div>
        <div class="info-row">
            <span>Untuk Pembayaran:</span>
            <span style="font-weight: bold;">SPP BULAN <?= strtoupper($data['bulan']) ?></span>
        </div>

        <div class="total-box">
            Rp <?= number_format($data['jumlah_bayar'], 0, ',', '.') ?>
        </div>

        <p style="font-size: 11px; font-style: italic; text-align: center;">"Terima kasih telah melakukan pembayaran tepat waktu untuk mendukung pendidikan ananda."</p>

        <div class="footer">
            <div class="signature">
                <p>Orang Tua / Wali</p>
                <strong>( <?= htmlspecialchars($data['nama_ortu'] ?? '.................') ?> )</strong> </div>
            <div class="signature">
                <p>Bendahara Sekolah</p>
                <strong>( <?= htmlspecialchars($_SESSION['nama_lengkap']) ?> )</strong>
            </div>
        </div>
    </div>

    <div style="text-align: center; margin-top: 20px;" class="btn-print">
        <button onclick="window.print()" style="padding: 10px 20px; cursor: pointer; background: #2563eb; color: white; border: none; border-radius: 5px;">Cetak Kwitansi</button>
        <button onclick="window.close()" style="padding: 10px 20px; cursor: pointer; background: #64748b; color: white; border: none; border-radius: 5px;">Tutup</button>
    </div>
</body>
</html>