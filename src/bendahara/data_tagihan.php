<?php
session_start();
require_once '../config/db.php'; 

// PROTEKSI: Khusus Bendahara
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'bendahara') {
    header("Location: ../auth/login.php");
    exit;
}

$bulan_pilihan = isset($_GET['bulan']) ? (int)$_GET['bulan'] : 0; // 0 = Tampilkan Semua Bulan
$tahun_pilihan = isset($_GET['tahun']) ? (int)$_GET['tahun'] : (int)date('Y');
$nama_bulan_arr = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

try {
    // Query Dasar untuk mengambil histori dari tabel pembayaran_spp
    $sql = "SELECT p.*, s.nama_siswa, s.nis, k.nama_kelas 
            FROM pembayaran_spp p 
            LEFT JOIN siswa s ON p.id_siswa = s.id_siswa 
            LEFT JOIN kelas k ON s.id_kelas = k.id_kelas 
            WHERE p.periode = :tahun ";
    
    $params = [':tahun' => $tahun_pilihan];

    // Jika filter bulan diaktifkan
    if ($bulan_pilihan > 0) {
        $sql .= " AND p.bulan = :namabulan ";
        $params[':namabulan'] = $nama_bulan_arr[$bulan_pilihan];
    }

    $sql .= " ORDER BY p.tanggal_bayar DESC, p.id_bayar DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $data_histori = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Sistem Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Histori Transaksi - SIS TKIT Fathurrobbany</title>
    <link rel="stylesheet" href="dashboard.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* KONSISTENSI TEMA BIRU SLATE */
        body { font-family: 'Inter', sans-serif; background-color: #f8fafc; color: #0f172a; margin: 0; }
        .main-content { padding: 30px 40px; }

        .header-container { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 30px; background: white; padding: 20px 25px; border-radius: 20px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); border: 1px solid #f1f5f9; }
        .header-title h1 { font-size: 26px; font-weight: 800; color: #1e293b; margin: 0 0 5px 0; }
        .header-title p { margin: 0; color: #64748b; font-size: 14px; }

        .btn-print-modern { padding: 10px 20px; border-radius: 10px; border: none; background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%); color: white; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 8px; transition: 0.3s; box-shadow: 0 4px 10px rgba(99, 102, 241, 0.3); }
        .btn-print-modern:hover { transform: translateY(-2px); box-shadow: 0 6px 15px rgba(99, 102, 241, 0.4); }

        /* KONTROL FILTER & PENCARIAN */
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

        /* TABEL HISTORI */
        .table-card { background: white; border-radius: 16px; border: 1px solid #e2e8f0; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); }
        .modern-table { width: 100%; border-collapse: collapse; }
        .modern-table th { padding: 16px 20px; text-align: left; background: #f8fafc; color: #64748b; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 2px solid #e2e8f0; }
        .modern-table td { padding: 16px 20px; border-bottom: 1px solid #f1f5f9; transition: 0.2s; font-size: 14px;}
        .modern-table tbody tr { cursor: pointer; }
        .modern-table tbody tr:hover td { background: #f8fafc; }
        
        .badge-status { padding: 6px 12px; border-radius: 20px; font-size: 11px; font-weight: 700; display: inline-flex; align-items: center; gap: 4px; }
        .badge-lunas { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .badge-metode { background: #eff6ff; color: #2563eb; padding: 4px 10px; border-radius: 8px; font-size: 11px; font-weight: 600; border: 1px solid #bfdbfe; }

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

        @media print { 
            .sidebar, .controls-card, .btn-print-modern, .header-container p { display: none !important; } 
            .main-content { margin-left: 0 !important; width: 100% !important; padding: 0 !important; } 
            .header-container { box-shadow: none; border: none; padding: 0; margin-bottom: 20px; }
        }
    </style>
</head>
<body>

    <?php include 'sidebar.php'; ?>

    <main class="main-content">
        <div class="header-container">
            <div class="header-title">
                <h1>Data Histori Transaksi</h1>
                <p>Seluruh riwayat pembayaran SPP yang masuk ke sistem.</p>
            </div>
            <button onclick="window.print()" class="btn-print-modern">
                <i class="fas fa-print"></i> Cetak Histori
            </button>
        </div>

        <div class="controls-card">
            <form method="GET" class="filter-row">
                <div style="flex: 1;">
                    <label class="form-label">Filter Bulan</label>
                    <select name="bulan" class="input-modern">
                        <option value="0" <?= ($bulan_pilihan == 0) ? 'selected' : '' ?>>Semua Bulan</option>
                        <?php for($i=1; $i<=12; $i++): ?>
                            <option value="<?= $i ?>" <?= ($bulan_pilihan == $i) ? 'selected' : '' ?>><?= $nama_bulan_arr[$i] ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div style="flex: 1;">
                    <label class="form-label">Tahun</label>
                    <input type="number" name="tahun" value="<?= $tahun_pilihan ?>" class="input-modern">
                </div>
                <button type="submit" class="btn-apply"><i class="fas fa-filter"></i> Filter</button>
            </form>

            <div class="search-wrapper">
                <i class="fas fa-search"></i>
                <input type="text" id="searchInput" class="search-input" placeholder="Cari nama, NIS, atau metode pembayaran...">
            </div>
        </div>

        <div class="table-card">
            <table class="modern-table" id="historiTable">
                <thead>
                    <tr>
                        <th>Tgl Bayar</th>
                        <th>Identitas Siswa</th>
                        <th>Tagihan Periode</th>
                        <th>Metode Bayar</th>
                        <th style="text-align: right;">Nominal</th>
                        <th style="text-align: center;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($data_histori)): ?>
                        <tr><td colspan="6" style="text-align: center; padding: 40px; color: #94a3b8;">Tidak ada riwayat transaksi pada periode terpilih.</td></tr>
                    <?php else: ?>
                        <?php foreach ($data_histori as $row): 
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
                        <tr onclick="openReceipt(<?= $json_data ?>)">
                            <td style="color: #64748b; font-weight: 500;"><i class="far fa-clock"></i> <?= $tanggal ?></td>
                            <td>
                                <strong style="color: #1e293b; display: block;"><?= htmlspecialchars($row['nama_siswa']) ?></strong>
                                <span style="color: #64748b; font-size: 12px;"><?= htmlspecialchars($row['nis'] ?? '-') ?></span>
                            </td>
                            <td style="color: #475569; font-weight: 600;"><?= $row['bulan'] ?> <?= $row['periode'] ?></td>
                            <td><span class="badge-metode"><?= $metode ?></span></td>
                            <td style="text-align: right; font-weight: 700; color: #10b981;">Rp <?= number_format($row['jumlah_bayar'], 0, ',', '.') ?></td>
                            <td style="text-align: center;">
                                <span class="badge-status badge-lunas"><i class="fas fa-check"></i> LUNAS</span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
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
        // Logika Live Search
        document.getElementById('searchInput').addEventListener('keyup', function() {
            let filter = this.value.toUpperCase();
            let rows = document.querySelector("#historiTable tbody").rows;
            
            for (let i = 0; i < rows.length; i++) {
                if(rows[i].cells.length > 1) { // Skip empty state row
                    let colNama = rows[i].cells[1].textContent.toUpperCase();
                    let colMetode = rows[i].cells[3].textContent.toUpperCase();
                    
                    if (colNama.indexOf(filter) > -1 || colMetode.indexOf(filter) > -1) {
                        rows[i].style.display = "";
                    } else {
                        rows[i].style.display = "none";
                    }
                }      
            }
        });

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