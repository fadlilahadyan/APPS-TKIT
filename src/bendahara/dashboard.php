<?php
session_start();
require_once '../config/db.php'; 

// PROTEKSI
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'bendahara') {
    header("Location: ../auth/login.php");
    exit();
}

$success_msg = '';
$error_msg = '';

$nama_bulan_arr = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

// --- LOGIKA INPUT MANUAL & SINKRONISASI 2 TABEL ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['catat_spp'])) {
    $id_siswa      = $_POST['id_siswa'];
    $bulan         = (int)$_POST['bulan'];
    $tahun         = (int)$_POST['tahun'];
    $jumlah_bayar  = (int)$_POST['jumlah_bayar'];
    $tgl_bayar     = date('Y-m-d');
    $nama_bulan_teks = $nama_bulan_arr[$bulan];

    try {
        $pdo->beginTransaction();

        $stmtSiswa = $pdo->prepare("SELECT s.nama_siswa, k.nama_kelas FROM siswa s LEFT JOIN kelas k ON s.id_kelas = k.id_kelas WHERE s.id_siswa = ?");
        $stmtSiswa->execute([$id_siswa]);
        $dtSiswa = $stmtSiswa->fetch();
        
        if($dtSiswa) {
            $nama_real = $dtSiswa['nama_siswa'];
            $kelas_real = $dtSiswa['nama_kelas'] ?? 'Belum Ada Kelas';

            // 1. Cek & Update di spp_status
            $cekStatus = $pdo->prepare("SELECT id FROM spp_status WHERE nama = ? AND bulan = ? AND tahun = ?");
            $cekStatus->execute([$nama_real, $bulan, $tahun]);
            if ($cekStatus->rowCount() > 0) {
                $updateStatus = $pdo->prepare("UPDATE spp_status SET status = 'LUNAS', tanggal_bayar = ? WHERE nama = ? AND bulan = ? AND tahun = ?");
                $updateStatus->execute([$tgl_bayar, $nama_real, $bulan, $tahun]);
            } else {
                $insertStatus = $pdo->prepare("INSERT INTO spp_status (nama, kelas, jumlah, bulan, tahun, tanggal_bayar, status) VALUES (?, ?, ?, ?, ?, ?, 'LUNAS')");
                $insertStatus->execute([$nama_real, $kelas_real, $jumlah_bayar, $bulan, $tahun, $tgl_bayar]);
            }

            // 2. Catat Riwayat di pembayaran_spp agar histori detail muncul
            $insertRiwayat = $pdo->prepare("INSERT INTO pembayaran_spp (id_siswa, jenis, periode, jumlah_bayar, tanggal_bayar, bulan, status) VALUES (?, 'Tunai (Manual)', ?, ?, ?, ?, 'LUNAS')");
            $insertRiwayat->execute([$id_siswa, $tahun, $jumlah_bayar, $tgl_bayar, $nama_bulan_teks]);
            
            $pdo->commit();
            $success_msg = "Pembayaran tunai berhasil dicatat dan sinkron!";
        } else {
            $error_msg = "Gagal. Data Siswa tidak ditemukan.";
        }
    } catch (PDOException $e) {
        $pdo->rollBack();
        $error_msg = "Gagal menyimpan ke database: " . $e->getMessage();
    }
}

try {
    $total_masuk = $pdo->query("SELECT SUM(jumlah) FROM spp_status WHERE status = 'LUNAS'")->fetchColumn() ?? 0;
    $total_siswa = $pdo->query("SELECT COUNT(*) FROM siswa WHERE status = 'Aktif'")->fetchColumn() ?? 0;
    
    // AMBIL DATA HISTORI
    $stmtHistory = $pdo->query("SELECT p.*, s.nama_siswa, s.nis, k.nama_kelas 
                                FROM pembayaran_spp p 
                                LEFT JOIN siswa s ON p.id_siswa = s.id_siswa 
                                LEFT JOIN kelas k ON s.id_kelas = k.id_kelas
                                ORDER BY p.tanggal_bayar DESC, p.id_bayar DESC LIMIT 6");
    $data_spp = $stmtHistory->fetchAll(PDO::FETCH_ASSOC);

    $list_siswa = $pdo->query("SELECT id_siswa, nama_siswa, nis FROM siswa WHERE status = 'Aktif' ORDER BY nama_siswa ASC")->fetchAll();

    $tahun_ini = date('Y');
    $query_grafik = "SELECT bulan, SUM(jumlah) as total FROM spp_status WHERE status = 'LUNAS' AND tahun = ? GROUP BY bulan ORDER BY bulan ASC";
    $stmtGrafik = $pdo->prepare($query_grafik);
    $stmtGrafik->execute([$tahun_ini]);
    $res_grafik = $stmtGrafik->fetchAll(PDO::FETCH_ASSOC);

    $labels = [];
    $values = [];
    foreach ($res_grafik as $row) {
        $labels[] = substr($nama_bulan_arr[$row['bulan']], 0, 3);
        $values[] = $row['total'];
    }

} catch (PDOException $e) {
    die("Sistem Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Keuangan - SIS TKIT Fathurrobbany</title>
    <link rel="stylesheet" href="dashboard.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* KONSISTENSI DENGAN TUNGGAKAN DAN LAPORAN */
        body { font-family: 'Inter', sans-serif; background-color: #f8fafc; color: #0f172a; margin: 0; }
        .main-content { padding: 30px 40px; }

        /* HEADER IDENTIK */
        .header-container { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 30px; background: white; padding: 20px 25px; border-radius: 20px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); border: 1px solid #f1f5f9; }
        .header-title h1 { font-size: 26px; font-weight: 800; color: #1e293b; margin: 0 0 5px 0; }
        .header-title p { margin: 0; color: #64748b; font-size: 14px; }

        /* STATS GRID IDENTIK DENGAN LAPORAN.PHP */
        .stats-grid-modern { display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px; margin-bottom: 30px; }
        .card-gradient { border-radius: 20px; padding: 25px; color: white; position: relative; overflow: hidden; box-shadow: 0 10px 20px -5px rgba(0,0,0,0.1); }
        .card-gradient::after { content: ''; position: absolute; right: -20px; top: -20px; width: 100px; height: 100px; background: rgba(255,255,255,0.1); border-radius: 50%; }
        .card-blue { background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%); }
        .card-orange { background: linear-gradient(135deg, #f59e0b 0%, #b45309 100%); }
        .card-purple { background: linear-gradient(135deg, #8b5cf6 0%, #6d28d9 100%); }
        .card-label { font-size: 13px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; opacity: 0.9; margin-bottom: 8px; display: block; }
        .card-val { font-size: 26px; font-weight: 800; margin: 0; }

        /* GRID LAYOUT UTAMA */
        .content-grid-modern { display: grid; grid-template-columns: 2fr 1.2fr; gap: 24px; }

        /* BOX KONTEN IDENTIK */
        .box-card { background: white; padding: 25px; border-radius: 20px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); margin-bottom: 24px; }
        .box-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid #f1f5f9; }
        .box-header h3 { margin: 0; font-size: 18px; font-weight: 800; color: #1e293b; }
        
        .chart-container { height: 280px; width: 100%; position: relative; }

        /* INPUT FORM IDENTIK DENGAN TUNGGAKAN.PHP */
        .form-label { display: block; font-size: 12px; font-weight: 700; color: #475569; margin-bottom: 6px; text-transform: uppercase; letter-spacing: 0.5px; }
        .input-modern { padding: 12px 16px; border-radius: 10px; border: 1px solid #cbd5e1; font-family: 'Inter', sans-serif; font-size: 14px; background: #f8fafc; transition: 0.3s; width: 100%; box-sizing: border-box; outline: none; }
        .input-modern:focus { border-color: #3b82f6; background: #fff; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1); }
        .btn-apply { width: 100%; padding: 14px 24px; background: #2563eb; color: white; border: none; border-radius: 10px; font-weight: 600; cursor: pointer; transition: 0.2s; font-size: 14px; margin-top: 10px;}
        .btn-apply:hover { background: #1d4ed8; }

        /* LIST HISTORI TRANSAKSI KONSISTEN */
        .tx-list { display: flex; flex-direction: column; gap: 10px; }
        .tx-item { display: flex; align-items: center; justify-content: space-between; padding: 12px 16px; border-radius: 12px; border: 1px solid #f1f5f9; transition: 0.2s; cursor: pointer; background: #f8fafc; }
        .tx-item:hover { background: #ffffff; border-color: #cbd5e1; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); transform: translateX(4px); }
        .tx-left { display: flex; align-items: center; gap: 16px; }
        .tx-icon { width: 40px; height: 40px; border-radius: 10px; background: #eff6ff; color: #2563eb; display: flex; align-items: center; justify-content: center; font-size: 16px; font-weight: 800; }
        .tx-name { font-size: 14px; font-weight: 700; color: #0f172a; margin-bottom: 2px; display: block; }
        .tx-method { font-size: 12px; font-weight: 600; color: #64748b; display: block; }
        .tx-amount { font-size: 15px; font-weight: 800; color: #10b981; }

        /* MODAL (DETAIL STRUK) DENGAN TEMA BIRU SLATE */
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
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    
    <main class="main-content">
        <div class="header-container">
            <div class="header-title">
                <h1>Dashboard Keuangan</h1>
                <p>Selamat datang, <strong><?= htmlspecialchars($_SESSION['nama_lengkap']) ?></strong>. Pantau transaksi SPP hari ini.</p>
            </div>
        </div>

        <!-- STATS KONSISTEN DENGAN LAPORAN.PHP -->
        <section class="stats-grid-modern">
            <div class="card-gradient card-blue">
                <span class="card-label">Total Pemasukan Sistem</span>
                <div class="card-val">Rp <?= number_format($total_masuk, 0, ',', '.') ?></div>
            </div>
            <div class="card-gradient card-orange">
                <span class="card-label">Murid Aktif Terdaftar</span>
                <div class="card-val"><?= $total_siswa ?> Anak</div>
            </div>
            <div class="card-gradient card-purple">
                <span class="card-label">Status Jaringan</span>
                <div class="card-val" style="font-size: 20px; margin-top: 5px;"><i class="fas fa-check-circle"></i> Online</div>
            </div>
        </section>

        <div class="content-grid-modern">
            <!-- KOLOM KIRI -->
            <div style="display: flex; flex-direction: column; gap: 24px;">
                
                <!-- CHART -->
                <div class="box-card">
                    <div class="box-header">
                        <h3>Grafik Pemasukan SPP <?= $tahun_ini ?></h3>
                        <span style="background: #eff6ff; color: #2563eb; padding: 4px 10px; border-radius: 10px; font-size: 11px; font-weight: 700;">Year-to-Date</span>
                    </div>
                    <div class="chart-container">
                        <canvas id="incomeChart"></canvas>
                    </div>
                </div>

                <!-- FORM MANUAL -->
                <div class="box-card">
                    <div class="box-header">
                        <h3>Catat Pembayaran Tunai</h3>
                    </div>

                    <?php if($success_msg): ?>
                        <div style="background:#dcfce7; color:#166534; padding:12px; border-radius:10px; margin-bottom:15px; font-size:14px; font-weight:600; border: 1px solid #bbf7d0;"><i class="fas fa-check-circle"></i> <?= $success_msg ?></div>
                    <?php endif; ?>
                    <?php if($error_msg): ?>
                        <div style="background:#fef2f2; color:#991b1b; padding:12px; border-radius:10px; margin-bottom:15px; font-size:14px; font-weight:600; border: 1px solid #fecaca;"><i class="fas fa-exclamation-circle"></i> <?= $error_msg ?></div>
                    <?php endif; ?>

                    <form method="POST">
                        <div style="margin-bottom: 15px;">
                            <label class="form-label">Cari Nama / NIS Siswa</label>
                            <select name="id_siswa" class="input-modern" required>
                                <option value="" disabled selected>-- Pilih Siswa --</option>
                                <?php foreach($list_siswa as $s): ?>
                                    <option value="<?= $s['id_siswa'] ?>"><?= $s['nis'] ?> - <?= $s['nama_siswa'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div style="display: grid; grid-template-columns: 1.5fr 1fr 1.5fr; gap: 12px;">
                            <div>
                                <label class="form-label">Bulan</label>
                                <select name="bulan" class="input-modern" required>
                                    <?php for($i=1; $i<=12; $i++): ?>
                                        <option value="<?= $i ?>" <?= (date('n') == $i) ? 'selected' : '' ?>><?= $nama_bulan_arr[$i] ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div>
                                <label class="form-label">Tahun</label>
                                <input type="number" name="tahun" value="<?= date('Y') ?>" class="input-modern" required>
                            </div>
                            <div>
                                <label class="form-label">Nominal (Rp)</label>
                                <input type="number" name="jumlah_bayar" value="150000" class="input-modern" required>
                            </div>
                        </div>
                        
                        <button type="submit" name="catat_spp" class="btn-apply">
                            <i class="fas fa-save"></i> Proses Pembayaran
                        </button>
                    </form>
                </div>
            </div>

            <!-- KOLOM KANAN: HISTORI -->
            <div class="box-card" style="height: fit-content;">
                <div class="box-header">
                    <h3>Histori Transaksi</h3>
                    <a href="data_tagihan.php" style="color: #3b82f6; font-size: 12px; font-weight: 700; text-decoration: none;">Lihat Semua</a>
                </div>

                <div class="tx-list">
                    <?php if(empty($data_spp)): ?>
                        <div style="text-align:center; padding: 30px 10px; color: #94a3b8;">
                            <i class="fas fa-history" style="font-size: 30px; margin-bottom: 10px;"></i>
                            <p style="margin: 0; font-size: 13px;">Belum ada riwayat pembayaran.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach($data_spp as $row): 
                            $inisial = strtoupper(substr($row['nama_siswa'] ?? 'U', 0, 1));
                            $metode = $row['jenis'] ?? 'Sistem';
                            $tanggal = date('d M Y', strtotime($row['tanggal_bayar']));
                            
                            // JSON Data untuk Modal Detail
                            $json_data = htmlspecialchars(json_encode([
                                'nama' => $row['nama_siswa'] ?? 'Unknown',
                                'nis' => $row['nis'] ?? '-',
                                'kelas' => $row['nama_kelas'] ?? '-',
                                'nominal' => number_format($row['jumlah_bayar'], 0, ',', '.'),
                                'metode' => $metode,
                                'tanggal' => $tanggal,
                                'periode' => $row['bulan'] . ' ' . $row['periode']
                            ]));
                        ?>
                        <div class="tx-item" onclick="openReceipt(<?= $json_data ?>)">
                            <div class="tx-left">
                                <div class="tx-icon"><?= $inisial ?></div>
                                <div>
                                    <span class="tx-name"><?= htmlspecialchars($row['nama_siswa']) ?></span>
                                    <span class="tx-method"><?= $metode ?></span>
                                </div>
                            </div>
                            <div class="tx-amount">+Rp<?= number_format($row['jumlah_bayar'] / 1000, 0) ?>k</div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <div style="text-align: center; margin-top: 15px;">
                    <span style="font-size: 11px; color: #94a3b8;"><i class="fas fa-hand-pointer"></i> Klik transaksi untuk detail struk</span>
                </div>
            </div>

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

    <!-- SCRIPT -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Logika Buka/Tutup Modal Receipt Detail
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

        // Konfigurasi Grafik Biru Konsisten
        const ctx = document.getElementById('incomeChart').getContext('2d');
        let gradient = ctx.createLinearGradient(0, 0, 0, 300);
        gradient.addColorStop(0, 'rgba(59, 130, 246, 0.4)'); // Biru standar
        gradient.addColorStop(1, 'rgba(59, 130, 246, 0.0)');

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?= json_encode($labels) ?>,
                datasets: [{
                    label: 'Pemasukan',
                    data: <?= json_encode($values) ?>,
                    borderColor: '#3b82f6',
                    backgroundColor: gradient,
                    borderWidth: 3,
                    tension: 0.3, 
                    fill: true,
                    pointBackgroundColor: '#ffffff',
                    pointBorderColor: '#3b82f6',
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 6
                }]
            },
            options: { 
                responsive: true, 
                maintainAspectRatio: false, 
                plugins: { 
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#1e293b',
                        padding: 10,
                        titleFont: { family: 'Inter', size: 12 },
                        bodyFont: { family: 'Inter', size: 13, weight: 'bold' },
                        displayColors: false,
                        callbacks: {
                            label: function(context) { return 'Rp ' + context.raw.toLocaleString('id-ID'); }
                        }
                    }
                },
                scales: {
                    x: { grid: { display: false }, ticks: { color: '#64748b', font: { family: 'Inter', weight: '600' } } },
                    y: { 
                        grid: { borderDash: [5, 5], color: '#e2e8f0' }, 
                        beginAtZero: true,
                        ticks: { 
                            color: '#64748b', font: { family: 'Inter', weight: '600' },
                            callback: function(value) { return 'Rp ' + (value/1000) + 'k'; } 
                        } 
                    }
                }
            }
        });
    </script>
</body>
</html>