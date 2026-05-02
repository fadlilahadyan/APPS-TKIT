<?php
session_start();
require_once '../config/db.php';

// PROTEKSI: Khusus Bendahara
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'bendahara') {
    header("Location: ../auth/login.php");
    exit();
}

$tahun_pilihan = isset($_GET['tahun']) ? (int)$_GET['tahun'] : (int)date('Y');
$bulan_pilihan = isset($_GET['bulan']) ? (int)$_GET['bulan'] : 0; // 0 = Semua Bulan
$nama_bulan_arr = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

try {
    // 1. QUERY REKAP BULANAN (Aggregasi)
    $sql_rekap = "SELECT bulan, SUM(jumlah) as total_masuk, COUNT(*) as total_transaksi 
                  FROM spp_status 
                  WHERE status = 'LUNAS' AND tahun = :tahun ";
    
    if ($bulan_pilihan > 0) {
        $sql_rekap .= " AND bulan = :bulan ";
    }
    
    $sql_rekap .= " GROUP BY bulan ORDER BY bulan ASC";
    
    $stmt_rekap = $pdo->prepare($sql_rekap);
    $params_rekap = ['tahun' => $tahun_pilihan];
    if ($bulan_pilihan > 0) $params_rekap['bulan'] = $bulan_pilihan;
    $stmt_rekap->execute($params_rekap);
    $laporan_rekap = $stmt_rekap->fetchAll(PDO::FETCH_ASSOC);

    // Analisis Global
    $total_all = $pdo->prepare("SELECT SUM(jumlah) FROM spp_status WHERE status = 'LUNAS' AND tahun = ? " . ($bulan_pilihan > 0 ? "AND bulan = ?" : ""));
    $param_total = [$tahun_pilihan];
    if ($bulan_pilihan > 0) $param_total[] = $bulan_pilihan;
    $total_all->execute($param_total);
    $total_nominal = $total_all->fetchColumn() ?? 0;
    
    // Cari Bulan Puncak (Hanya relevan jika filter tahunan)
    $bulan_puncak = '-';
    $nominal_puncak = 0;
    if ($bulan_pilihan == 0) {
        $highest = $pdo->prepare("SELECT bulan, SUM(jumlah) as total FROM spp_status WHERE status = 'LUNAS' AND tahun = ? GROUP BY bulan ORDER BY total DESC LIMIT 1");
        $highest->execute([$tahun_pilihan]);
        $data_puncak = $highest->fetch(PDO::FETCH_ASSOC);
        $bulan_puncak = $data_puncak ? $nama_bulan_arr[$data_puncak['bulan']] : '-';
        $nominal_puncak = $data_puncak['total'] ?? 0;
    }

    $count_bulan = count($laporan_rekap) > 0 ? count($laporan_rekap) : 1;
    $rata_rata = $total_nominal / $count_bulan;

    // 2. QUERY DETAIL TRANSAKSI (Histori)
    $sql_detail = "SELECT p.*, s.nama_siswa, s.nis, k.nama_kelas 
                   FROM pembayaran_spp p 
                   LEFT JOIN siswa s ON p.id_siswa = s.id_siswa 
                   LEFT JOIN kelas k ON s.id_kelas = k.id_kelas 
                   WHERE p.periode = :tahun ";
                   
    $params_detail = [':tahun' => $tahun_pilihan];
    if ($bulan_pilihan > 0) {
        $sql_detail .= " AND p.bulan = :namabulan ";
        $params_detail[':namabulan'] = $nama_bulan_arr[$bulan_pilihan];
    }
    $sql_detail .= " ORDER BY p.tanggal_bayar DESC, p.id_bayar DESC";
    
    $stmt_detail = $pdo->prepare($sql_detail);
    $stmt_detail->execute($params_detail);
    $laporan_detail = $stmt_detail->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Error Database: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Keuangan - SIS TKIT FATHUROBANI</title>
    <link rel="stylesheet" href="dashboard.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* CSS KONSISTEN TEMA BIRU SLATE */
        body { font-family: 'Inter', sans-serif; background-color: #f8fafc; color: #0f172a; margin: 0; }
        .main-content { padding: 30px 40px; }

        .header-container { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 30px; background: white; padding: 20px 25px; border-radius: 20px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); border: 1px solid #f1f5f9; }
        .header-title h1 { font-size: 26px; font-weight: 800; color: #1e293b; margin: 0 0 5px 0; }
        .header-title p { margin: 0; color: #64748b; font-size: 14px; }

        .filter-wrapper { display: flex; gap: 12px; align-items: center; }
        .select-modern { padding: 10px 16px; border-radius: 10px; border: 1px solid #cbd5e1; font-family: 'Inter', sans-serif; font-weight: 600; color: #334155; background: #f8fafc; outline: none; cursor: pointer; transition: 0.2s; }
        .select-modern:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1); }
        .btn-icon { padding: 10px 14px; border-radius: 10px; border: 1px solid #cbd5e1; background: #ffffff; color: #475569; cursor: pointer; transition: 0.2s; }
        .btn-icon:hover { background: #f1f5f9; color: #0f172a; }
        .btn-print-modern { padding: 10px 20px; border-radius: 10px; border: none; background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%); color: white; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 8px; transition: 0.3s; box-shadow: 0 4px 10px rgba(99, 102, 241, 0.3); }
        .btn-print-modern:hover { transform: translateY(-2px); box-shadow: 0 6px 15px rgba(99, 102, 241, 0.4); }

        .stats-grid-modern { display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px; margin-bottom: 30px; }
        .card-gradient { border-radius: 20px; padding: 25px; color: white; position: relative; overflow: hidden; box-shadow: 0 10px 20px -5px rgba(0,0,0,0.1); }
        .card-gradient::after { content: ''; position: absolute; right: -20px; top: -20px; width: 100px; height: 100px; background: rgba(255,255,255,0.1); border-radius: 50%; }
        .card-blue { background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%); }
        .card-orange { background: linear-gradient(135deg, #f59e0b 0%, #b45309 100%); }
        .card-purple { background: linear-gradient(135deg, #8b5cf6 0%, #6d28d9 100%); }
        
        .card-label { font-size: 13px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; opacity: 0.9; margin-bottom: 8px; display: block; }
        .card-val { font-size: 26px; font-weight: 800; margin: 0; }
        .card-sub { font-size: 14px; opacity: 0.8; font-weight: 500; margin-top: 5px; }

        .table-card { background: white; border-radius: 20px; padding: 25px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); border: 1px solid #f1f5f9; margin-bottom: 24px;}
        .table-card-header { font-size: 18px; font-weight: 800; color: #1e293b; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid #e2e8f0; display: flex; align-items: center; gap: 10px;}

        /* TABEL REKAP */
        .modern-table { width: 100%; border-collapse: separate; border-spacing: 0 10px; }
        .modern-table th { padding: 0 15px 10px 15px; font-size: 12px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; text-align: left; border-bottom: 1px solid #e2e8f0; }
        .modern-table td { padding: 18px 15px; background: #f8fafc; }
        .modern-table tr td:first-child { border-top-left-radius: 12px; border-bottom-left-radius: 12px; font-weight: 700; color: #0f172a; }
        .modern-table tr td:last-child { border-top-right-radius: 12px; border-bottom-right-radius: 12px; }
        
        .badge-tx { background: #e0e7ff; color: #4338ca; padding: 6px 12px; border-radius: 8px; font-size: 12px; font-weight: 700; }
        .nominal-text { font-size: 16px; font-weight: 800; color: #10b981; }

        .progress-container { width: 100%; background-color: #e2e8f0; border-radius: 10px; height: 10px; overflow: hidden; margin-top: 8px; }
        .progress-bar-fill { height: 100%; background: linear-gradient(90deg, #3b82f6, #60a5fa); border-radius: 10px; transition: width 1s ease-in-out; }

        /* TABEL DETAIL TRANSAKSI */
        .detail-table { width: 100%; border-collapse: collapse; }
        .detail-table th { padding: 14px 15px; background: #f8fafc; color: #64748b; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; text-align: left; border-bottom: 2px solid #e2e8f0; }
        .detail-table td { padding: 14px 15px; border-bottom: 1px solid #f1f5f9; font-size: 14px; color: #334155; }
        /* Tambahkan efek pointer dan hover pada baris tabel detail */
        .detail-table tbody tr { cursor: pointer; transition: background 0.2s; }
        .detail-table tbody tr:hover td { background: #f8fafc; }
        
        /* MODAL E-RECEIPT */
        .modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px); display: none; justify-content: center; align-items: center; z-index: 1000; opacity: 0; transition: opacity 0.3s; }
        .modal-overlay.active { display: flex; opacity: 1; }
        .modal-receipt { background: #ffffff; width: 380px; border-radius: 20px; padding: 30px; box-shadow: 0 25px 50px rgba(0,0,0,0.15); transform: translateY(20px); transition: transform 0.3s; position: relative; border: 1px solid #e2e8f0; }
        .modal-overlay.active .modal-receipt { transform: translateY(0); }
        .modal-close { position: absolute; top: 15px; right: 15px; background: #f1f5f9; color: #64748b; border: none; width: 32px; height: 32px; border-radius: 50%; font-size: 14px; cursor: pointer; transition: 0.2s; }
        .modal-close:hover { background: #fee2e2; color: #dc2626; }
        
        .receipt-header { text-align: center; margin-bottom: 20px; border-bottom: 2px dashed #cbd5e1; padding-bottom: 20px; }
        .receipt-amount { font-size: 28px; font-weight: 800; color: #0f172a; margin: 10px 0 5px 0; }
        .receipt-status { background: #dcfce7; color: #166534; padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: 700; display: inline-block; border: 1px solid #bbf7d0;}
        .receipt-row { display: flex; justify-content: space-between; margin-bottom: 12px; font-size: 13px; }
        .receipt-label { color: #64748b; font-weight: 600; }
        .receipt-value { color: #1e293b; font-weight: 700; text-align: right; }

        /* PRINT STYLES */
        @media print { 
            .sidebar, .filter-wrapper { display: none !important; } 
            .main-content { margin-left: 0 !important; width: 100% !important; padding: 0 !important; } 
            .card-gradient { color: #000; box-shadow: none; border: 1px solid #ccc; }
            .card-blue, .card-orange, .card-purple { background: #fff !important; }
            .modern-table td, .detail-table td { background: transparent !important; border: 1px solid #e2e8f0; }
            .print-header { display: block !important; text-align: center; margin-bottom: 30px; border-bottom: 2px solid #000; padding-bottom: 10px;}
            .header-container { display: none !important; }
            .modal-overlay { display: none !important; }
        }

        .print-header { display: none; }
    </style>
</head>
<body>

    <?php include 'sidebar.php'; ?>

    <main class="main-content">
        <!-- HEADER UNTUK PRINT -->
        <div class="print-header">
            <h2 style="margin: 0; font-size: 24px; text-transform: uppercase;">Laporan Keuangan SPP TKIT Fathurrobbany</h2>
            <p style="margin: 5px 0 0 0; font-size: 14px;">Periode: <?= $bulan_pilihan > 0 ? $nama_bulan_arr[$bulan_pilihan] : 'Semua Bulan' ?> <?= $tahun_pilihan ?></p>
        </div>

        <div class="header-container">
            <div class="header-title">
                <h1>Laporan Keuangan SPP</h1>
                <p>Rekapitulasi dan rincian transaksi tersinkronisasi.</p>
            </div>
            <div class="filter-wrapper">
                <form method="GET" style="display:flex; gap:8px; margin:0;">
                    <select name="bulan" class="select-modern">
                        <option value="0" <?= ($bulan_pilihan == 0) ? 'selected' : '' ?>>Semua Bulan</option>
                        <?php for($i=1; $i<=12; $i++): ?>
                            <option value="<?= $i ?>" <?= ($bulan_pilihan == $i) ? 'selected' : '' ?>><?= $nama_bulan_arr[$i] ?></option>
                        <?php endfor; ?>
                    </select>
                    <select name="tahun" class="select-modern">
                        <?php for($y=date('Y')-2; $y<=date('Y')+2; $y++): ?>
                            <option value="<?= $y ?>" <?= ($tahun_pilihan == $y) ? 'selected' : '' ?>><?= $y ?></option>
                        <?php endfor; ?>
                    </select>
                    <button type="submit" class="btn-icon" title="Terapkan Filter"><i class="fas fa-filter"></i></button>
                </form>
                <button onclick="window.print()" class="btn-print-modern">
                    <i class="fas fa-print"></i> Cetak Laporan
                </button>
            </div>
        </div>

        <section class="stats-grid-modern">
            <div class="card-gradient card-blue">
                <span class="card-label">Total Pemasukan Filtered</span>
                <div class="card-val">Rp <?= number_format($total_nominal, 0, ',', '.') ?></div>
            </div>
            <div class="card-gradient card-orange">
                <span class="card-label">Rata-Rata Per Bulan</span>
                <div class="card-val">Rp <?= number_format($rata_rata, 0, ',', '.') ?></div>
            </div>
            <div class="card-gradient card-purple">
                <span class="card-label">Performa Bulan Tertinggi</span>
                <div class="card-val"><?= $bulan_puncak ?></div>
                <div class="card-sub">Rp <?= number_format($nominal_puncak, 0, ',', '.') ?></div>
            </div>
        </section>

        <!-- BAGIAN 1: REKAPITULASI -->
        <div class="table-card">
            <div class="table-card-header">
                <i class="fas fa-chart-bar" style="color: #3b82f6;"></i> Rekapitulasi Bulanan
            </div>

            <table class="modern-table">
                <thead>
                    <tr>
                        <th style="width: 20%;">PERIODE</th>
                        <th style="width: 20%;">VOLUME TRANSAKSI</th>
                        <th style="width: 25%; text-align: right; padding-right: 25px;">TOTAL NOMINAL</th>
                        <th style="width: 35%;">INDIKATOR PERFORMA</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($laporan_rekap)): ?>
                        <tr><td colspan="4" style="text-align:center; padding: 40px; color: #94a3b8; background: transparent;">Belum ada rekapitulasi untuk periode ini.</td></tr>
                    <?php else: ?>
                        <?php foreach ($laporan_rekap as $row): ?>
                        <?php $percent = ($nominal_puncak > 0) ? ($row['total_masuk'] / $nominal_puncak) * 100 : (($row['total_masuk'] > 0) ? 100 : 0); ?>
                        <tr>
                            <td><?= $nama_bulan_arr[$row['bulan']] ?> <?= $tahun_pilihan ?></td>
                            <td><span class="badge-tx"><?= $row['total_transaksi'] ?> Transaksi</span></td>
                            <td style="text-align: right; padding-right: 25px;"><span class="nominal-text">Rp <?= number_format($row['total_masuk'], 0, ',', '.') ?></span></td>
                            <td>
                                <div style="display: flex; justify-content: space-between; font-size: 12px; font-weight: 700; color: #475569; margin-bottom: 4px;">
                                    <span>Pencapaian</span>
                                    <span><?= round($percent) ?>%</span>
                                </div>
                                <div class="progress-container">
                                    <div class="progress-bar-fill" style="width: <?= $percent ?>%;"></div>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- BAGIAN 2: DETAIL TRANSAKSI DENGAN ONCLICK MODAL -->
        <div class="table-card">
            <div class="table-card-header">
                <i class="fas fa-list" style="color: #10b981;"></i> Rincian Transaksi Masuk
            </div>

            <table class="detail-table">
                <thead>
                    <tr>
                        <th>Tanggal Bayar</th>
                        <th>Nama Siswa (NIS)</th>
                        <th>Tagihan Bulan</th>
                        <th>Metode Pembayaran</th>
                        <th style="text-align: right;">Nominal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($laporan_detail)): ?>
                        <tr><td colspan="5" style="text-align:center; padding: 30px; color: #94a3b8;">Belum ada rincian transaksi.</td></tr>
                    <?php else: ?>
                        <?php foreach ($laporan_detail as $det): 
                            $metode = $det['jenis'] ?? 'Manual';
                            $tanggal = date('d M Y', strtotime($det['tanggal_bayar']));
                            
                            // JSON Data untuk Modal Detail
                            $json_data = htmlspecialchars(json_encode([
                                'nama' => $det['nama_siswa'] ?? 'Unknown',
                                'nis' => $det['nis'] ?? '-',
                                'kelas' => $det['nama_kelas'] ?? '-',
                                'nominal' => number_format($det['jumlah_bayar'], 0, ',', '.'),
                                'metode' => $metode,
                                'tanggal' => $tanggal,
                                'periode' => $det['bulan'] . ' ' . $det['periode']
                            ]));
                        ?>
                        <tr onclick="openReceipt(<?= $json_data ?>)">
                            <td style="color: #64748b;"><i class="far fa-calendar-alt"></i> <?= date('d/m/Y', strtotime($det['tanggal_bayar'])) ?></td>
                            <td>
                                <strong style="color: #1e293b;"><?= htmlspecialchars($det['nama_siswa'] ?? 'Unknown') ?></strong>
                                <span style="color: #94a3b8; font-size: 12px; display: block;"><?= htmlspecialchars($det['nis'] ?? '-') ?></span>
                            </td>
                            <td style="font-weight: 600; color: #475569;"><?= htmlspecialchars($det['bulan']) ?> <?= $det['periode'] ?></td>
                            <td><span style="background: #eff6ff; color: #2563eb; padding: 4px 8px; border-radius: 6px; font-size: 11px; font-weight: 600;"><?= htmlspecialchars($metode) ?></span></td>
                            <td style="text-align: right; font-weight: 700; color: #10b981;">Rp <?= number_format($det['jumlah_bayar'], 0, ',', '.') ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
            
            <!-- TEMPAT TANDA TANGAN UNTUK PRINT -->
            <div style="display: none; margin-top: 50px; justify-content: flex-end; padding-right: 50px;" class="ttd-print">
                <div style="text-align: center;">
                    <p style="margin-bottom: 70px;">Tasikmalaya, <?= date('d F Y') ?><br>Bendahara TKIT</p>
                    <p style="font-weight: bold; text-decoration: underline; margin: 0;"><?= htmlspecialchars($_SESSION['nama_lengkap']) ?></p>
                </div>
            </div>
            <style>
                @media print { .ttd-print { display: flex !important; } }
            </style>
        </div>

    </main>

    <!-- MODAL DETAIL HISTORI KONSISTEN TEMA -->
    <div class="modal-overlay" id="receiptModal" onclick="closeReceipt(event)">
        <div class="modal-receipt" onclick="event.stopPropagation()">
            <button class="modal-close" onclick="closeReceipt(event)"><i class="fas fa-times"></i></button>
            
            <div class="receipt-header">
                <i class="fas fa-check-circle" style="font-size: 40px; color: #10b981; margin-bottom: 10px;"></i>
                <div style="color: #64748b; font-size: 12px; font-weight: 700; text-transform: uppercase;">Pembayaran Berhasil</div>
                <div class="receipt-amount" id="modalAmount">Rp 0</div>
                <div class="receipt-status"><i class="fas fa-check"></i> LUNAS</div>
            </div>

            <div class="receipt-body">
                <div class="receipt-row">
                    <span class="receipt-label">Nama Siswa</span>
                    <span class="receipt-value" id="modalNama">-</span>
                </div>
                <div class="receipt-row">
                    <span class="receipt-label">NIS / Kelas</span>
                    <span class="receipt-value" id="modalNisKelas">-</span>
                </div>
                <div class="receipt-row">
                    <span class="receipt-label">Periode Tagihan</span>
                    <span class="receipt-value" id="modalPeriode">-</span>
                </div>
                <div class="receipt-row" style="border-top: 1px dashed #cbd5e1; padding-top: 12px; margin-top: 5px;">
                    <span class="receipt-label">Metode Pembayaran</span>
                    <span class="receipt-value" id="modalMetode" style="color: #2563eb;">-</span>
                </div>
                <div class="receipt-row">
                    <span class="receipt-label">Tgl Transaksi</span>
                    <span class="receipt-value" id="modalTanggal">-</span>
                </div>
            </div>
            
            <div style="text-align: center; margin-top: 25px; padding-top: 15px; border-top: 1px solid #f1f5f9;">
                <div style="font-size: 11px; color: #94a3b8; font-weight: 600;">Sistem Keuangan Terpadu<br>TKIT Fathurrobbany</div>
            </div>
        </div>
    </div>

    <!-- SCRIPT PENCARIAN & MODAL -->
    <script>
        // Logika Buka/Tutup Modal
        function openReceipt(data) {
            document.getElementById('modalAmount').innerText = 'Rp ' + data.nominal;
            document.getElementById('modalNama').innerText = data.nama;
            document.getElementById('modalNisKelas').innerText = data.nis + ' / ' + data.kelas;
            document.getElementById('modalPeriode').innerText = data.periode;
            document.getElementById('modalMetode').innerText = data.metode;
            document.getElementById('modalTanggal').innerText = data.tanggal;
            
            document.getElementById('receiptModal').classList.add('active');
        }

        function closeReceipt(e) {
            document.getElementById('receiptModal').classList.remove('active');
        }
    </script>
</body>
</html>