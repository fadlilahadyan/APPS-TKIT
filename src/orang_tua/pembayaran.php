<?php
session_start();
require_once '../config/db.php';
require_once 'otomasi_tagihan.php'; // Otomasi sekarang akan langsung berjalan saat halaman ini diakses
date_default_timezone_set('Asia/Jakarta');

// PROTEKSI: Hanya Orang Tua yang bisa akses
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'orang_tua') {
    header("Location: ../auth/login.php");
    exit();
}

$id_user = $_SESSION['id_user'];
$nama_orang_tua = $_SESSION['nama_lengkap'] ?? 'Orang Tua';

try {
    // 1. Ambil ID Siswa yang terhubung
    $stmtSiswa = $pdo->prepare("SELECT id_siswa, nama_siswa FROM siswa WHERE id_ortu = ? LIMIT 1");
    $stmtSiswa->execute([$id_user]);
    $data_siswa = $stmtSiswa->fetch();
    
    $id_siswa = $data_siswa['id_siswa'] ?? 0;
    $nama_anak = $data_siswa['nama_siswa'] ?? 'Anak Belum Terdaftar';

    // 2. Ambil Riwayat Semua Bulan di Tahun Berjalan (Lunas & Belum)
    $tahun_ini = (int)date('Y');
    $bulan_ini = (int)date('n');
    $nama_bulan_arr = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

    // Ambil data tagihan dari database untuk siswa ini di tahun ini
    $stmtHistory = $pdo->prepare("SELECT bulan, tahun, jumlah as jumlah_bayar, tanggal_bayar, status 
                                  FROM spp_status 
                                  WHERE nama = ? AND tahun = ?");
    $stmtHistory->execute([$nama_anak, $tahun_ini]);
    $db_history = $stmtHistory->fetchAll(PDO::FETCH_ASSOC);

    // Format ulang array untuk mempermudah pencarian berdasarkan bulan
    $riwayat_formatted = [];
    foreach ($db_history as $row) {
        $riwayat_formatted[$row['bulan']] = $row;
    }

    // 3. Bangun Rekapan dari Bulan 1 hingga Bulan Ini
    $riwayat_pembayaran = [];
    for ($i = 1; $i <= $bulan_ini; $i++) {
        if (isset($riwayat_formatted[$i])) {
            // Jika ada di database, gunakan data tersebut
            $riwayat_pembayaran[] = $riwayat_formatted[$i];
        } else {
            // Jika tidak ada (mungkin otomasi terlewat), anggap belum bayar dengan nominal default
            $riwayat_pembayaran[] = [
                'bulan' => $i,
                'tahun' => $tahun_ini,
                'jumlah_bayar' => 150000, // Sesuaikan dengan nominal SPP
                'tanggal_bayar' => null,
                'status' => 'BELUM'
            ];
        }
    }
    
    // Balik urutan agar bulan terbaru di atas
    $riwayat_pembayaran = array_reverse($riwayat_pembayaran);

} catch (PDOException $e) {
    die("Sistem Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Pembayaran SPP - SIS TKIT FATHUROBANI</title>
    <link rel="stylesheet" href="dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); z-index: 1000; justify-content: center; align-items: center; }
        .modal-box { background: #fff; padding: 25px; border-radius: 12px; width: 400px; max-width: 90%; box-shadow: 0 10px 25px rgba(0,0,0,0.2); }
        .modal-header { font-size: 18px; font-weight: bold; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #e2e8f0; padding-bottom: 10px;}
        .close-btn { cursor: pointer; color: #ef4444; font-size: 20px; }
        .tab-container { display: flex; gap: 10px; margin-bottom: 20px; }
        .tab-btn { flex: 1; padding: 10px; border: 1px solid #cbd5e1; background: #f8fafc; cursor: pointer; border-radius: 8px; font-weight: 600; color: #64748b; transition: all 0.2s; }
        .tab-btn.active { background: #eff6ff; color: #2563eb; border-color: #2563eb; }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        .form-select { width: 100%; padding: 12px; border-radius: 8px; border: 1px solid #cbd5e1; margin-bottom: 20px; font-size: 14px; outline: none; }
        .form-select:focus { border-color: #2563eb; }
        .pay-submit-btn { width: 100%; padding: 12px; background: #166534; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: bold; font-size: 15px; transition: 0.2s; }
        .pay-submit-btn:hover { background: #14532d; }
        .pay-submit-btn:disabled { background: #94a3b8; cursor: not-allowed; }
        .qr-container { text-align: center; margin: 20px 0; padding: 15px; background: #f8fafc; border-radius: 10px; border: 1px dashed #cbd5e1; }
        .qr-container img { max-width: 200px; height: auto; margin-bottom: 10px; }
    </style>
</head>
<body>
    
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <div class="header">
            <h1>Riwayat Pembayaran SPP</h1>
            <p>Data pembayaran dari awal tahun untuk ananda: <strong><?= htmlspecialchars($nama_anak); ?></strong></p>
        </div>

        <div class="content-card">
            <div class="card-header">Detail Transaksi Masuk Tahun <?= $tahun_ini ?></div>
            
            <?php if (empty($riwayat_pembayaran)): ?>
                <div style="text-align: center; padding: 60px 20px; background: #f8fafc; border-radius: 12px; border: 2px dashed #cbd5e1; margin-top: 15px;">
                    <div style="width: 80px; height: 80px; background: #eff6ff; color: #2563eb; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 35px; margin: 0 auto 20px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);">
                        <i class="fas fa-file-invoice-dollar"></i>
                    </div>
                    <h3 style="color: #0f172a; margin-bottom: 10px; font-size: 18px; font-weight: 700;">Belum Ada Tagihan Tercatat</h3>
                    <p style="color: #64748b; font-size: 14px; max-width: 450px; margin: 0 auto; line-height: 1.6;">
                        Saat ini belum ada data tagihan SPP atau riwayat pembayaran yang tercatat untuk ananda <strong><?= htmlspecialchars($nama_anak); ?></strong>.
                    </p>
                </div>
            <?php else: ?>
                <div class="table-wrapper" style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="background: #f8fafc;">
                                <th style="padding: 15px; text-align: left; border-bottom: 2px solid #e2e8f0; color: #64748b; font-size: 13px;">PERIODE BULAN</th>
                                <th style="padding: 15px; text-align: left; border-bottom: 2px solid #e2e8f0; color: #64748b; font-size: 13px;">NOMINAL</th>
                                <th style="padding: 15px; text-align: center; border-bottom: 2px solid #e2e8f0; color: #64748b; font-size: 13px;">STATUS</th>
                                <th style="padding: 15px; text-align: right; border-bottom: 2px solid #e2e8f0; color: #64748b; font-size: 13px;">TANGGAL BAYAR</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($riwayat_pembayaran as $item): ?>
                                <tr>
                                    <td style="padding: 15px; border-bottom: 1px solid #f1f5f9; font-weight: 600;">
                                        <?= htmlspecialchars($nama_bulan_arr[$item['bulan']] . ' ' . $item['tahun']); ?>
                                    </td>
                                    <td style="padding: 15px; border-bottom: 1px solid #f1f5f9; color: var(--text-main); font-weight: 500;">
                                        Rp <?= number_format($item['jumlah_bayar'], 0, ',', '.'); ?>
                                    </td>
                                    <td style="padding: 15px; border-bottom: 1px solid #f1f5f9; text-align: center;">
                                        <?php if (strtoupper($item['status']) === 'LUNAS'): ?>
                                            <span style="background: #dcfce7; color: #166534; padding: 6px 14px; border-radius: 20px; font-size: 11px; font-weight: 700; border: 1px solid #bbf7d0;">LUNAS</span>
                                        <?php else: ?>
                                            <span style="background: #fee2e2; color: #991b1b; padding: 6px 14px; border-radius: 20px; font-size: 11px; font-weight: 700; margin-bottom: 10px; display: inline-block; border: 1px solid #fecaca;">BELUM LUNAS</span>
                                            <br>
                                            <button class="btn-buka-modal" 
                                                    data-nama="<?= htmlspecialchars($nama_anak) ?>" 
                                                    data-bulan="<?= $item['bulan'] ?>" 
                                                    data-tahun="<?= $item['tahun'] ?>" 
                                                    data-nominal="<?= $item['jumlah_bayar'] ?>"
                                                    style="background: #2563eb; color: white; border: none; padding: 8px 14px; border-radius: 6px; cursor: pointer; font-size: 12px; font-weight: 600;">
                                                <i class="fas fa-wallet"></i> Pilih Pembayaran
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                    <td style="padding: 15px; border-bottom: 1px solid #f1f5f9; text-align: right; color: #64748b;">
                                        <?= $item['tanggal_bayar'] ? date('d/m/Y', strtotime($item['tanggal_bayar'])) : '-'; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            <div style="margin-top: 25px; padding: 15px; background: #eff6ff; border-radius: 10px; border: 1px solid #dbeafe; display: flex; align-items: center; gap: 12px;">
                <i class="fas fa-info-circle" style="color: #2563eb;"></i>
                <span style="font-size: 13px; color: #1e40af;">
                    Data di atas adalah riwayat resmi yang telah dikonfirmasi oleh Bendahara Sekolah. Silakan hubungi bagian keuangan jika ada ketidaksesuaian.
                </span>
            </div>
        </div>
    </div>

    <div class="modal-overlay" id="paymentModal">
        <div class="modal-box">
            <div class="modal-header">
                <span>Metode Pembayaran</span>
                <i class="fas fa-times close-btn" onclick="closeModal()"></i>
            </div>
            
            <div style="margin-bottom: 15px; font-size: 14px; color: #475569;">
                Total Tagihan: <strong id="modalNominal" style="color: #0f172a; font-size: 20px; display: block; margin-top: 5px;">Rp 0</strong>
            </div>

            <div class="tab-container">
                <button class="tab-btn active" onclick="switchTab('va')"><i class="fas fa-university"></i> Virtual Account</button>
                <button class="tab-btn" onclick="switchTab('qris')"><i class="fas fa-qrcode"></i> QRIS</button>
            </div>

            <div id="tab-va" class="tab-content active">
                <label style="display: block; font-size: 13px; color: #64748b; margin-bottom: 8px;">Pilih Bank Tujuan</label>
                <select class="form-select" id="bankSelect" onchange="generateVA()">
                    <option value="" disabled selected>-- Pilih Bank --</option>
                    <option value="BCA">BCA Virtual Account</option>
                    <option value="MANDIRI">Mandiri Virtual Account</option>
                    <option value="BRI">BRIVA</option>
                    <option value="BNI">BNI Virtual Account</option>
                </select>

                <div id="va-info" style="display: none; background: #f8fafc; padding: 15px; border-radius: 8px; border: 1px dashed #cbd5e1; margin-bottom: 20px; text-align: center;">
                    <p style="font-size: 13px; color: #64748b; margin-bottom: 5px;">Nomor Virtual Account Anda:</p>
                    <h3 id="va-number" style="margin: 0; color: #0f172a; font-family: monospace; font-size: 24px; letter-spacing: 2px;"></h3>
                    <p style="font-size: 12px; font-weight: bold; color: #166534; margin-top: 10px;">Total: <span id="va-total-bayar"></span></p>
                    <p style="font-size: 11px; color: #ef4444; margin-top: 8px;">*Silakan transfer tepat sesuai nominal ke nomor di atas</p>
                </div>

                <button class="pay-submit-btn" id="btn-bayar-va" onclick="prosesBayar('Virtual Account')" disabled>Simulasi: Saya Sudah Transfer ke VA Ini</button>
            </div>

            <div id="tab-qris" class="tab-content">
                <div class="qr-container">
                    <img id="qrImage" src="" alt="QRIS Code">
                    <p style="font-size: 12px; color: #64748b; margin: 0;">Scan QR ini menggunakan aplikasi e-Wallet atau M-Banking Anda.</p>
                </div>
                <button class="pay-submit-btn" onclick="prosesBayar('QRIS')">Simulasi: Saya Sudah Scan & Bayar</button>
            </div>
        </div>
    </div>

    <script>
        let currentData = {};

        document.querySelectorAll('.btn-buka-modal').forEach(button => {
            button.addEventListener('click', function() {
                currentData = {
                    nama: this.getAttribute('data-nama'),
                    bulan: this.getAttribute('data-bulan'),
                    tahun: this.getAttribute('data-tahun'),
                    nominal: this.getAttribute('data-nominal')
                };

                // Set ulang form VA setiap kali modal dibuka
                document.getElementById('bankSelect').value = "";
                document.getElementById('va-info').style.display = 'none';
                document.getElementById('btn-bayar-va').disabled = true;
                document.getElementById('btn-bayar-va').style.background = "#94a3b8";

                document.getElementById('modalNominal').innerText = 'Rp ' + parseInt(currentData.nominal).toLocaleString('id-ID');
                
                const qrData = `QRIS-SPP-${currentData.nama}-${currentData.bulan}-${currentData.tahun}-${currentData.nominal}`;
                document.getElementById('qrImage').src = `https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=${encodeURIComponent(qrData)}`;

                document.getElementById('paymentModal').style.display = 'flex';
            });
        });

        function closeModal() {
            document.getElementById('paymentModal').style.display = 'none';
        }

        document.getElementById('paymentModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });

        function switchTab(tab) {
            document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
            
            if(tab === 'va') {
                document.querySelectorAll('.tab-btn')[0].classList.add('active');
                document.getElementById('tab-va').classList.add('active');
            } else {
                document.querySelectorAll('.tab-btn')[1].classList.add('active');
                document.getElementById('tab-qris').classList.add('active');
            }
        }

        // FUNGSI GENERATE NOMOR VIRTUAL ACCOUNT OTOMATIS
        function generateVA() {
            const bank = document.getElementById('bankSelect').value;
            let prefix = "";
            
            switch(bank) {
                case 'BCA': prefix = "3901"; break;
                case 'MANDIRI': prefix = "89508"; break;
                case 'BRI': prefix = "26215"; break;
                case 'BNI': prefix = "8206"; break;
            }

            // Generate nomor VA = Prefix + 6 digit acak
            const nisSimulasi = Math.floor(100000 + Math.random() * 900000); 
            const vaNumber = prefix + nisSimulasi;

            // Tampilkan UI VA
            document.getElementById('va-number').innerText = vaNumber;
            document.getElementById('va-total-bayar').innerText = 'Rp ' + parseInt(currentData.nominal).toLocaleString('id-ID');
            document.getElementById('va-info').style.display = 'block';
            
            // Buka kunci tombol bayar
            const btnBayar = document.getElementById('btn-bayar-va');
            btnBayar.disabled = false;
            btnBayar.style.background = "#166534"; // Hijau menandakan siap diklik
        }

        async function prosesBayar(metode) {
            const btnAll = document.querySelectorAll('.pay-submit-btn');
            const originalTextVA = btnAll[0].innerHTML;
            const originalTextQRIS = btnAll[1].innerHTML;

            btnAll.forEach(btn => { 
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memproses...'; 
                btn.disabled = true; 
            });

            setTimeout(async () => {
                try {
                    const response = await fetch('proses_simulasi_bayar.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: new URLSearchParams({
                            'action': 'bayar_simulasi',
                            'nama': currentData.nama,
                            'bulan': currentData.bulan,
                            'tahun': currentData.tahun,
                            'metode': metode
                        })
                    });

                    const result = await response.json();

                    if (result.status === 'success') {
                        alert(`Pembayaran via ${metode} Berhasil Dikonfirmasi!`);
                        window.location.reload(); 
                    } else {
                        alert('Gagal: ' + result.message);
                        resetButtons(btnAll, originalTextVA, originalTextQRIS);
                        closeModal();
                    }
                } catch (error) {
                    alert('Terjadi kesalahan koneksi server.');
                    resetButtons(btnAll, originalTextVA, originalTextQRIS);
                    closeModal();
                }
            }, 2000); 
        }

        function resetButtons(btnAll, textVA, textQRIS) {
            btnAll[0].innerHTML = textVA;
            btnAll[1].innerHTML = textQRIS;
            btnAll.forEach(btn => btn.disabled = false);
            
            // Jika reset, pastikan tombol VA mati jika bank belum dipilih ulang
            if(document.getElementById('bankSelect').value === "") {
                btnAll[0].disabled = true;
                btnAll[0].style.background = "#94a3b8";
            }
        }
    </script>
</body>
</html>