<?php
// guru/laporan.php
session_start();
// Mundur satu folder untuk panggil koneksi database
require_once "../config/db.php";

// Cek login guru
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'guru') {
    header("Location: ../auth/login.php");
    exit();
}

$id_guru_user = $_SESSION['id_user'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Perkembangan - TKIT FATHUROBANI</title>
    <!-- Arahkan CSS ke dashboard.css yang sudah responsif -->
    <link rel="stylesheet" href="dashboard.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* Hanya CSS Khusus Komponen Laporan yang ditaruh di sini */
        .page-header { margin-bottom: 32px; display: flex; justify-content: space-between; align-items: flex-end; }
        .page-header h1 { font-size: 26px; font-weight: 800; color: #1e293b; margin: 0 0 5px 0;}
        .page-header p { color: #64748b; font-size: 14px; margin: 0; }

        .card { background: white; border: 1px solid #e2e8f0; border-radius: 20px; padding: 30px; margin-bottom: 25px; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.02); }

        .section-title { font-size: 16px; font-weight: 800; margin: 24px 0 20px; color: #2563eb; border-bottom: 2px solid #eff6ff; padding-bottom: 10px; }

        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; font-size: 13px; font-weight: 700; margin-bottom: 8px; color: #475569; text-transform: uppercase; letter-spacing: 0.5px;}

        /* Penyesuaian agar input box melebar penuh rata kiri-kanan */
        .form-control { width: 100%; padding: 14px 16px; border: 1px solid #cbd5e1; border-radius: 12px; font-size: 14px; background: #f8fafc; outline: none; transition: 0.3s; box-sizing: border-box; color: #1e293b;}
        .form-control:focus { border-color: #3b82f6; background: #fff; box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.1); }
        textarea.form-control { resize: vertical; min-height: 100px; }

        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; }

        .btn-submit { background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); color: white; width: 100%; padding: 16px; border: none; border-radius: 12px; font-weight: 800; font-size: 15px; cursor: pointer; margin-top: 15px; transition: 0.3s; box-shadow: 0 4px 10px rgba(59, 130, 246, 0.3); display: flex; align-items: center; justify-content: center; gap: 10px;}
        .btn-submit:hover { transform: translateY(-3px); box-shadow: 0 6px 15px rgba(59, 130, 246, 0.4); }

        /* Modern Table */
        .modern-table { width: 100%; border-collapse: collapse; }
        .modern-table th { text-align: left; padding: 16px 20px; font-size: 12px; color: #64748b; font-weight: 700; text-transform: uppercase; border-bottom: 2px solid #e2e8f0; background: #f8fafc; }
        .modern-table td { padding: 16px 20px; font-size: 14px; border-bottom: 1px solid #f1f5f9; color: #334155;}
        .modern-table tbody tr:hover td { background: #f8fafc; }
        
        .badge-aspek { background: #eff6ff; color: #2563eb; padding: 6px 12px; border-radius: 20px; font-size: 11px; font-weight: 800; letter-spacing: 0.5px;}

        @media (max-width: 768px) {
            .grid-2 { grid-template-columns: 1fr; gap: 15px; } /* Jadikan form 1 kolom jika di HP */
            .card { padding: 20px; }
            /* Buat tabel bisa di-scroll kesamping kalau di HP biar gak hancur */
            .table-responsive { overflow-x: auto; -webkit-overflow-scrolling: touch; }
        }
    </style>
</head>
<body>
    
    <!-- Panggil Sidebar (Pastikan sidebar.php sudah disetting dengan benar) -->
    <?php include 'sidebar.php'; ?>
    
    <main class="main-content">
        <div class="page-header">
            <div>
                <h1>Laporan Perkembangan</h1>
                <p>Input 6 Aspek capaian perkembangan siswa untuk laporan ke orang tua</p>
            </div>
        </div>

        <div class="card">
            <form action="proses_simpan_laporan.php" method="POST">
                <div class="grid-2">
                    <div class="form-group">
                        <label>Pilih Siswa</label>
                        <select name="id_siswa" class="form-control" required>
                            <option value="">-- Pilih Siswa --</option>
                            <?php
                            $query = $pdo->query("SELECT id_siswa, nama_siswa FROM siswa WHERE status = 'Aktif' ORDER BY nama_siswa ASC");
                            while ($s = $query->fetch(PDO::FETCH_ASSOC)) {
                                echo "<option value='{$s['id_siswa']}'>{$s['nama_siswa']}</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Tanggal Laporan</label>
                        <input type="date" class="form-control" value="<?= date('Y-m-d') ?>" readonly>
                    </div>
                </div>

                <div class="section-title">Enam Aspek Perkembangan (STPPA)</div>

                <div class="grid-2">
                    <div class="form-group">
                        <label>1. Nilai Agama & Moral</label>
                        <textarea name="agama_moral" class="form-control" placeholder="Contoh: Anak mampu melafalkan doa belajar..."></textarea>
                    </div>
                    <div class="form-group">
                        <label>2. Fisik Motorik</label>
                        <textarea name="fisik_motorik" class="form-control" placeholder="Contoh: Mampu menggunting pola lingkaran..."></textarea>
                    </div>
                    <div class="form-group">
                        <label>3. Kognitif</label>
                        <textarea name="kognitif" class="form-control" placeholder="Contoh: Mengenal konsep bilangan 1-10..."></textarea>
                    </div>
                    <div class="form-group">
                        <label>4. Bahasa</label>
                        <textarea name="bahasa" class="form-control" placeholder="Contoh: Berani bercerita di depan kelas..."></textarea>
                    </div>
                    <div class="form-group">
                        <label>5. Sosial Emosional</label>
                        <textarea name="sosial_emosional" class="form-control" placeholder="Contoh: Mau berbagi mainan dengan teman..."></textarea>
                    </div>
                    <div class="form-group">
                        <label>6. Seni</label>
                        <textarea name="seni" class="form-control" placeholder="Contoh: Mewarnai dengan rapi dan kreatif..."></textarea>
                    </div>
                </div>

                <button type="submit" class="btn-submit">
                    <i class="fas fa-save"></i> Simpan Laporan Perkembangan
                </button>
            </form>
        </div>

        <div class="card">
            <h3 style="margin-bottom: 20px; font-size: 18px; font-weight: 800; color: #1e293b;"><i class="fas fa-history" style="color:#3b82f6;"></i> Riwayat Input Terakhir</h3>
            <div class="table-responsive">
                <table class="modern-table">
                    <thead>
                        <tr>
                            <th width="15%">Tanggal</th>
                            <th width="20%">Nama Siswa</th>
                            <th width="15%">Aspek</th>
                            <th width="50%">Deskripsi Capaian</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    // Pastikan tabel "perkembangan" sudah ada di database sesuai asumsi kodingan awal.
                    try {
                        $stmt = $pdo->prepare("
                            SELECT p.*, s.nama_siswa
                            FROM perkembangan p
                            JOIN siswa s ON p.id_siswa = s.id_siswa
                            WHERE p.id_guru = :id_guru
                            ORDER BY p.tanggal DESC, p.id_laporan DESC
                            LIMIT 15
                        ");
                        $stmt->execute(['id_guru' => $id_guru_user]);

                        if ($stmt->rowCount() > 0) {
                            while ($data = $stmt->fetch()) {
                                echo "<tr>
                                    <td style='color: #64748b;'><i class='far fa-calendar-alt' style='margin-right:4px;'></i> " . date('d M Y', strtotime($data['tanggal'])) . "</td>
                                    <td style='font-weight: 700; color: #1e293b;'>" . htmlspecialchars($data['nama_siswa']) . "</td>
                                    <td><span class='badge-aspek'>" . htmlspecialchars($data['aspek']) . "</span></td>
                                    <td style='line-height: 1.5; color: #475569;'>" . htmlspecialchars($data['deskripsi']) . "</td>
                                </tr>";
                            }
                        } else {
                            echo "<tr><td colspan='4' style='text-align:center; padding: 40px; color: #94a3b8;'><i class='fas fa-folder-open' style='font-size: 30px; display: block; margin-bottom: 10px;'></i> Belum ada riwayat laporan yang Anda input.</td></tr>";
                        }
                    } catch (PDOException $e) {
                         echo "<tr><td colspan='4' style='text-align:center; color: #ef4444;'>Error Database: Pastikan tabel perkembangan tersedia.</td></tr>";
                    }
                    ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

</body>
</html>