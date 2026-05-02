<?php
session_start();
require_once '../config/db.php'; 

if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'bendahara') {
    header("Location: ../auth/login.php");
    exit;
}

$bulan_pilihan = isset($_GET['bulan']) ? (int)$_GET['bulan'] : (int)date('n');
$tahun_pilihan = isset($_GET['tahun']) ? (int)$_GET['tahun'] : (int)date('Y');
$nama_bulan_arr = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

// Query cerdas: Tarik semua siswa aktif, gabungkan dengan data spp_status di bulan/tahun terpilih
$sql = "SELECT s.nis, s.nama_siswa, p.jumlah, p.tanggal_bayar, p.status 
        FROM siswa s 
        LEFT JOIN spp_status p 
        ON s.nama_siswa = p.nama AND p.bulan = :bulan AND p.tahun = :tahun AND p.status = 'LUNAS'
        WHERE s.status = 'Aktif'
        ORDER BY s.nama_siswa ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute(['bulan' => $bulan_pilihan, 'tahun' => $tahun_pilihan]);
$data_tunggakan = $stmt->fetchAll();

// Kalkulasi Ringkasan
$total_siswa = count($data_tunggakan);
$total_lunas = 0;
foreach ($data_tunggakan as $d) {
    if ($d['status'] === 'LUNAS') $total_lunas++;
}
$total_nunggak = $total_siswa - $total_lunas;
$persentase = ($total_siswa > 0) ? round(($total_lunas / $total_siswa) * 100) : 0;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monitoring Tunggakan - SIS TKIT FATHUROBANI</title>
    <link rel="stylesheet" href="dashboard.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f8fafc; color: #0f172a; }
        .main-content { padding: 30px 40px; }

        .header-container { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 30px; background: white; padding: 20px 25px; border-radius: 20px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); border: 1px solid #f1f5f9; }
        .header-title h1 { font-size: 26px; font-weight: 800; color: #1e293b; margin: 0 0 5px 0; }
        .header-title p { margin: 0; color: #64748b; font-size: 14px; }
        .btn-print-modern { padding: 10px 20px; border-radius: 10px; border: none; background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%); color: white; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 8px; transition: 0.3s; box-shadow: 0 4px 10px rgba(99, 102, 241, 0.3); }
        .btn-print-modern:hover { transform: translateY(-2px); box-shadow: 0 6px 15px rgba(99, 102, 241, 0.4); }

        .content-grid-modern { display: grid; grid-template-columns: 2fr 1fr; gap: 24px; }

        /* Left Column Styling */
        .controls-card { background: white; padding: 20px; border-radius: 16px; border: 1px solid #e2e8f0; margin-bottom: 24px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); display: flex; flex-direction: column; gap: 15px; }
        .filter-row { display: flex; gap: 12px; align-items: flex-end; }
        .form-label { display: block; font-size: 12px; font-weight: 700; color: #475569; margin-bottom: 6px; text-transform: uppercase; letter-spacing: 0.5px; }
        .input-modern { padding: 12px 16px; border-radius: 10px; border: 1px solid #cbd5e1; font-family: 'Inter', sans-serif; font-size: 14px; background: #f8fafc; transition: 0.3s; width: 100%; box-sizing: border-box; outline: none; }
        .input-modern:focus { border-color: #3b82f6; background: #fff; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1); }
        .btn-apply { padding: 12px 24px; background: #2563eb; color: white; border: none; border-radius: 10px; font-weight: 600; cursor: pointer; transition: 0.2s; height: 45px; }
        .btn-apply:hover { background: #1d4ed8; }

        .search-wrapper { position: relative; width: 100%; }
        .search-wrapper i { position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: #94a3b8; }
        .search-input { width: 100%; padding: 14px 16px 14px 45px; border-radius: 10px; border: 1px solid #e2e8f0; background: #f1f5f9; font-family: 'Inter', sans-serif; font-size: 14px; transition: 0.3s; box-sizing: border-box; outline: none; }
        .search-input:focus { background: #fff; border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1); }

        .table-card { background: white; border-radius: 16px; border: 1px solid #e2e8f0; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); }
        .modern-table { width: 100%; border-collapse: collapse; }
        .modern-table th { padding: 16px 20px; text-align: left; background: #f8fafc; color: #64748b; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 2px solid #e2e8f0; }
        .modern-table td { padding: 16px 20px; border-bottom: 1px solid #f1f5f9; transition: 0.2s; }
        .modern-table tbody tr:hover td { background: #f8fafc; }
        
        .badge-status { padding: 6px 12px; border-radius: 20px; font-size: 11px; font-weight: 700; display: inline-flex; align-items: center; gap: 4px; }
        .badge-lunas { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .badge-nunggak { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }

        .wa-btn { color: #25d366; font-size: 22px; transition: 0.2s; display: inline-block; }
        .wa-btn:hover { transform: scale(1.15); color: #128c7e; }
        .wa-disabled { color: #cbd5e1; font-size: 12px; font-weight: 600; cursor: not-allowed; }

        /* Right Column Styling */
        .analysis-card { background: white; padding: 25px; border-radius: 20px; border: 1px solid #e2e8f0; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.03); position: sticky; top: 20px; }
        .analysis-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; padding-bottom: 15px; border-bottom: 1px solid #f1f5f9; }
        .analysis-header h3 { margin: 0; font-size: 18px; font-weight: 800; color: #1e293b; }
        
        .progress-circle { width: 65px; height: 65px; border-radius: 50%; background: conic-gradient(#3b82f6 <?= $persentase ?>%, #e2e8f0 0); display: flex; align-items: center; justify-content: center; position: relative; box-shadow: inset 0 0 10px rgba(0,0,0,0.05); }
        .progress-circle::after { content: '<?= $persentase ?>%'; position: absolute; width: 50px; height: 50px; background: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 13px; font-weight: 800; color: #1e293b; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }

        .stat-item { padding: 16px; border-radius: 12px; margin-bottom: 12px; display: flex; justify-content: space-between; align-items: center; border: 1px solid transparent; transition: 0.2s; }
        .stat-item:hover { transform: translateX(5px); }
        .stat-total { background: #f8fafc; border-color: #e2e8f0; }
        .stat-paid { background: #f0fdf4; border-color: #bbf7d0; }
        .stat-unpaid { background: #fef2f2; border-color: #fecaca; }
        
        .stat-label { display: flex; align-items: center; gap: 10px; font-size: 14px; font-weight: 600; }
        .stat-total .stat-label { color: #475569; }
        .stat-paid .stat-label { color: #166534; }
        .stat-unpaid .stat-label { color: #991b1b; }
        
        .stat-val { font-size: 20px; font-weight: 800; }

        @media print { 
            .sidebar, .controls-card, .btn-print-modern, .btn-action { display: none !important; } 
            .main-content { margin-left: 0 !important; width: 100% !important; padding: 0 !important; } 
            .content-grid-modern { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

    <div class="layout-wrapper">
        <?php include 'sidebar.php'; ?>

        <div class="main-content">
            <div class="header-container">
                <div class="header-title">
                    <h1>Monitoring Tunggakan SPP</h1>
                    <p>Periode: <strong><?= $nama_bulan_arr[$bulan_pilihan] ?> <?= $tahun_pilihan ?></strong></p>
                </div>
                <button onclick="window.print()" class="btn-print-modern">
                    <i class="fas fa-print"></i> Cetak Daftar
                </button>
            </div>

            <div class="content-grid-modern">
                
                <!-- KIRI: FILTER & TABEL -->
                <div class="left-column">
                    <div class="controls-card">
                        <form method="GET" class="filter-row">
                            <div style="flex: 1;">
                                <label class="form-label">Periode Bulan</label>
                                <select name="bulan" class="input-modern">
                                    <?php for($i=1; $i<=12; $i++): ?>
                                        <option value="<?= $i ?>" <?= ($bulan_pilihan == $i) ? 'selected' : '' ?>><?= $nama_bulan_arr[$i] ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div style="flex: 1;">
                                <label class="form-label">Tahun</label>
                                <input type="number" name="tahun" value="<?= $tahun_pilihan ?>" class="input-modern">
                            </div>
                            <button type="submit" class="btn-apply">Terapkan</button>
                        </form>

                        <div class="search-wrapper">
                            <i class="fas fa-search"></i>
                            <input type="text" id="searchInput" class="search-input" placeholder="Ketik nama siswa atau NIS untuk mencari...">
                        </div>
                    </div>

                    <div class="table-card">
                        <table class="modern-table" id="siswaTable">
                            <thead>
                                <tr>
                                    <th>NIS</th>
                                    <th>Nama Siswa</th>
                                    <th style="text-align:center;">Status Pembayaran</th>
                                    <th class="btn-action" style="text-align:center;">Aksi Reminder</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(empty($data_tunggakan)): ?>
                                    <tr><td colspan="4" style="text-align: center; padding: 30px; color: #94a3b8;">Tidak ada data siswa aktif ditemukan.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($data_tunggakan as $row): ?>
                                    <tr>
                                        <td style="color:#64748b; font-weight: 500;"><?= htmlspecialchars($row['nis'] ?? '-') ?></td>
                                        <td style="font-weight:700; color: #1e293b;"><?= htmlspecialchars($row['nama_siswa']) ?></td>
                                        <td style="text-align:center;">
                                            <?php if ($row['status'] === 'LUNAS'): ?>
                                                <span class="badge-status badge-lunas"><i class="fas fa-check-circle"></i> Lunas (<?= date('d/m/y', strtotime($row['tanggal_bayar'])) ?>)</span>
                                            <?php else: ?>
                                                <span class="badge-status badge-nunggak"><i class="fas fa-exclamation-circle"></i> Belum Lunas</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="btn-action" style="text-align:center;">
                                            <?php if ($row['status'] !== 'LUNAS'): ?>
                                                <?php 
                                                    $pesan = urlencode("Yth. Bapak/Ibu Wali Murid dari " . htmlspecialchars($row['nama_siswa']) . ". Kami mengingatkan bahwa tagihan SPP bulan " . $nama_bulan_arr[$bulan_pilihan] . " tahun " . $tahun_pilihan . " belum diselesaikan. Mohon segera melakukan pembayaran. Terima kasih.");
                                                ?>
                                                <a href="https://wa.me/?text=<?= $pesan ?>" target="_blank" class="wa-btn" title="Kirim Reminder WhatsApp"><i class="fab fa-whatsapp"></i></a>
                                            <?php else: ?>
                                                <span class="wa-disabled">Selesai</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- KANAN: ANALISIS DATA -->
                <div class="right-column">
                    <div class="analysis-card">
                        <div class="analysis-header">
                            <h3>Ringkasan Bulan Ini</h3>
                            <div class="progress-circle" title="Persentase Pelunasan"></div>
                        </div>
                        
                        <div class="stat-item stat-total">
                            <span class="stat-label"><i class="fas fa-users" style="color: #64748b;"></i> Total Murid</span>
                            <span class="stat-val" style="color: #334155;"><?= $total_siswa ?></span>
                        </div>
                        <div class="stat-item stat-paid">
                            <span class="stat-label"><i class="fas fa-check-circle" style="color: #10b981;"></i> Sudah Bayar</span>
                            <span class="stat-val" style="color: #166534;"><?= $total_lunas ?></span>
                        </div>
                        <div class="stat-item stat-unpaid">
                            <span class="stat-label"><i class="fas fa-exclamation-circle" style="color: #ef4444;"></i> Menunggak</span>
                            <span class="stat-val" style="color: #991b1b;"><?= $total_nunggak ?></span>
                        </div>
                    </div>
                </div>
            </div> 
        </div>
    </div>

    <script>
        document.getElementById('searchInput').addEventListener('keyup', function() {
            let filter = this.value.toUpperCase();
            let rows = document.querySelector("#siswaTable tbody").rows;
            for (let i = 0; i < rows.length; i++) {
                if(rows[i].cells.length > 1) { // Skip empty state row
                    let col1 = rows[i].cells[0].textContent.toUpperCase();
                    let col2 = rows[i].cells[1].textContent.toUpperCase();
                    if (col1.indexOf(filter) > -1 || col2.indexOf(filter) > -1) {
                        rows[i].style.display = "";
                    } else {
                        rows[i].style.display = "none";
                    }
                }      
            }
        });
    </script>
</body>
</html>