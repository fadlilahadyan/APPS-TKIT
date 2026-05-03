<?php
session_start();
require_once '../config/db.php';
date_default_timezone_set('Asia/Jakarta');

if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'orang_tua') {
    header("Location: ../auth/login.php");
    exit();
}

try {
    $id_user = $_SESSION['id_user'];
    
    // 1. Ambil ID Siswa dan Nama yang terhubung
    $stmtSiswa = $pdo->prepare("SELECT s.id_siswa, s.nama_siswa, s.nis, k.nama_kelas FROM siswa s LEFT JOIN kelas k ON s.id_kelas = k.id_kelas WHERE s.id_ortu = ? LIMIT 1");
    $stmtSiswa->execute([$id_user]);
    $data_siswa = $stmtSiswa->fetch();
    
    $id_siswa = $data_siswa['id_siswa'] ?? 0;
    $nama_anak = $data_siswa['nama_siswa'] ?? 'Belum Terhubung';
    $nis_anak = $data_siswa['nis'] ?? '-';
    $kelas_anak = $data_siswa['nama_kelas'] ?? 'Belum Ada Kelas';

    // 2. Ambil Laporan Perkembangan dari database rill-time
    $stmtLaporan = $pdo->prepare("SELECT tanggal, aspek, deskripsi FROM perkembangan WHERE id_siswa = ? ORDER BY tanggal DESC");
    $stmtLaporan->execute([$id_siswa]);
    $laporan = $stmtLaporan->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Sistem Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-Raport Perkembangan - SIS TKIT FATHUROBANIrobbany</title>
    <link rel="stylesheet" href="dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- LIBRARY UNTUK CETAK PDF -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

    <style>
        .raport-container { background: #fff; border-radius: 12px; padding: 30px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); border: 1px solid #e2e8f0; }
        .raport-header-doc { text-align: center; border-bottom: 3px solid #1e40af; padding-bottom: 15px; margin-bottom: 25px; }
        .raport-header-doc h2 { margin: 0; color: #0f172a; font-size: 22px; font-weight: 800; text-transform: uppercase; }
        .raport-header-doc p { margin: 5px 0 0 0; color: #64748b; font-size: 14px; }
        .student-info { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; background: #f8fafc; padding: 15px; border-radius: 8px; margin-bottom: 30px; border: 1px solid #e2e8f0; }
        .info-row { display: flex; font-size: 14px; }
        .info-label { width: 120px; font-weight: 600; color: #475569; }
        .info-value { color: #0f172a; font-weight: 700; }
        
        .timeline-grid { display: grid; gap: 20px; }
        .raport-card { border: 1px solid #e2e8f0; border-radius: 10px; overflow: hidden; page-break-inside: avoid; }
        .raport-card-header { background: #eff6ff; padding: 12px 20px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #bfdbfe; }
        .raport-aspek { background: #2563eb; color: white; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; }
        .raport-tanggal { color: #3b82f6; font-size: 13px; font-weight: 600; }
        .raport-card-body { padding: 20px; font-size: 14px; line-height: 1.7; color: #334155; }
        
        .empty-raport { text-align: center; padding: 50px 20px; background: #f8fafc; border: 2px dashed #cbd5e1; border-radius: 12px; }
        .empty-raport i { font-size: 40px; color: #94a3b8; margin-bottom: 15px; }
    </style>
</head>
<body>
    
     <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <div class="header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
            <div>
                <h1>E-Raport Perkembangan</h1>
                <p>Catatan akademik, sikap, dan keterampilan ananda dari Guru</p>
            </div>
            <?php if (!empty($laporan)): ?>
                <button onclick="downloadPDF()" style="background: #ef4444; color: white; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 8px; box-shadow: 0 4px 6px rgba(239, 68, 68, 0.2); transition: 0.2s;">
                    <i class="fas fa-file-pdf"></i> Unduh PDF
                </button>
            <?php endif; ?>
        </div>

        <!-- AREA YANG AKAN DICETAK MENJADI PDF -->
        <div id="area-cetak-pdf" class="raport-container">
            
            <div class="raport-header-doc">
                <h2>Laporan Perkembangan Siswa</h2>
                <p>TKIT Fathurrobbany - Tahun Ajaran <?= date('Y') ?></p>
            </div>

            <div class="student-info">
                <div>
                    <div class="info-row"><span class="info-label">Nama Siswa</span><span class="info-value">: <?= htmlspecialchars($nama_anak); ?></span></div>
                    <div class="info-row" style="margin-top: 8px;"><span class="info-label">NIS</span><span class="info-value">: <?= htmlspecialchars($nis_anak); ?></span></div>
                </div>
                <div>
                    <div class="info-row"><span class="info-label">Kelas</span><span class="info-value">: <?= htmlspecialchars($kelas_anak); ?></span></div>
                    <div class="info-row" style="margin-top: 8px;"><span class="info-label">Tanggal Cetak</span><span class="info-value">: <?= date('d/m/Y'); ?></span></div>
                </div>
            </div>

            <?php if (empty($laporan)): ?>
                <div class="empty-raport">
                    <i class="fas fa-clipboard-list"></i>
                    <h3 style="color: #0f172a; margin-bottom: 5px;">Belum Ada Catatan</h3>
                    <p style="color: #64748b; font-size: 14px; margin: 0;">Guru belum menginput laporan perkembangan untuk ananda.</p>
                </div>
            <?php else: ?>
                <div class="timeline-grid">
                    <?php foreach ($laporan as $item): ?>
                        <div class="raport-card">
                            <div class="raport-card-header">
                                <span class="raport-aspek"><i class="fas fa-star" style="margin-right: 5px; font-size: 10px;"></i> <?= htmlspecialchars($item['aspek']); ?></span>
                                <span class="raport-tanggal"><i class="fas fa-calendar-alt"></i> <?= date('d M Y', strtotime($item['tanggal'])); ?></span>
                            </div>
                            <div class="raport-card-body">
                                <?= nl2br(htmlspecialchars($item['deskripsi'])); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

        </div>
    </div>

    <!-- SCRIPT UNTUK GENERATE PDF -->
    <script>
        function downloadPDF() {
            // Tampilkan efek loading pada tombol
            const btn = document.querySelector('button[onclick="downloadPDF()"]');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menyiapkan PDF...';
            btn.disabled = true;

            // Target area yang ingin di-print
            const element = document.getElementById('area-cetak-pdf');
            
            // Konfigurasi Kualitas PDF
            const opt = {
                margin:       [10, 10, 10, 10], // Margin (Atas, Kiri, Bawah, Kanan)
                filename:     'E-Raport_<?= htmlspecialchars(str_replace(' ', '_', $nama_anak)); ?>.pdf',
                image:        { type: 'jpeg', quality: 0.98 },
                html2canvas:  { scale: 2, useCORS: true },
                jsPDF:        { unit: 'mm', format: 'a4', orientation: 'portrait' }
            };

            // Proses Generate PDF
            html2pdf().set(opt).from(element).save().then(() => {
                // Kembalikan tombol seperti semula setelah selesai
                btn.innerHTML = originalText;
                btn.disabled = false;
            });
        }
    </script>
</body>
</html>