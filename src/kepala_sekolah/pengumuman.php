<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'kepala_sekolah') {
    header("Location: ../auth/login.php");
    exit();
}

// Ambil data untuk tabel arsip
$arsip = $pdo->query("SELECT * FROM pengumuman ORDER BY id_pengumuman DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengumuman - TKIT FATHUROBANI</title>
    <link rel="stylesheet" href="dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body>
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <div class="header">
            <h1>📢 Pengumuman & Surat</h1>
            <p>Kirim informasi terbaru kepada Guru dan Orang Tua.</p>
        </div>

        <?php if (isset($_GET['status']) && $_GET['status'] == 'sukses'): ?>
            <div style="background: #dcfce7; color: #15803d; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                ✅ Pengumuman berhasil diterbitkan!
            </div>
        <?php endif; ?>

        <div class="content-card" style="margin-bottom: 25px;">
            <div class="card-header">Buat Pengumuman Baru</div>
            <form action="proses_pengumuman.php" method="POST" style="margin-top: 15px;">
                <div style="margin-bottom: 15px;">
                    <label style="display:block; margin-bottom: 5px; font-weight: 600;">Judul Pengumuman</label>
                    <input type="text" name="judul" required placeholder="Contoh: Undangan Rapat Wali Murid"
                        style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid #ddd;">
                </div>

                <div style="margin-bottom: 15px;">
                    <label style="display:block; margin-bottom: 5px; font-weight: 600;">Isi Pengumuman</label>
                    <textarea name="isi" required rows="4" placeholder="Tulis detail pengumuman di sini..."
                        style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid #ddd; font-family: inherit;"></textarea>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                    <div>
                        <label style="display:block; margin-bottom: 5px; font-weight: 600;">Prioritas</label>
                        <select name="prioritas" style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid #ddd;">
                            <option value="rendah">Biasa</option>
                            <option value="Sedang">Penting</option>
                            <option value="Tinggi">Sangat Penting</option>
                        </select>
                    </div>
                    <div>
                        <label style="display:block; margin-bottom: 5px; font-weight: 600;">Target Audiens</label>
                        <select name="target" style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid #ddd;">
                            <option value="Semua">Semua (Guru & Orang Tua)</option>
                            <option value="Guru">Hanya Guru</option>
                            <option value="Orang Tua">Hanya Orang Tua</option>
                        </select>
                    </div>
                </div>

                <button type="submit" name="submit" class="action-btn btn-blue" style="width: 100%; cursor: pointer;">
                    <i class="fas fa-paper-plane"></i> Terbitkan Pengumuman
                </button>
            </form>
        </div>

        <div class="content-card">
            <div class="card-header">Arsip Pengumuman</div>
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; margin-top: 15px;">
                    <thead>
                        <tr style="text-align: left; background: #f8fafc; border-bottom: 2px solid #e2e8f0;">
                            <th style="padding: 12px;">Tanggal</th>
                            <th>Judul</th>
                            <th>Target</th>
                            <th>Prioritas</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($arsip as $a): ?>
                            <tr style="border-bottom: 1px solid #f1f5f9;">
                                <td style="padding: 12px; font-size: 0.9em; color: #64748b;"><?= $a['tanggal'] ?></td>
                                <td style="font-weight: 600; color: #1e293b;"><?= htmlspecialchars($a['judul']) ?></td>
                                <td><span style="background: #e0f2fe; color: #0369a1; padding: 4px 8px; border-radius: 4px; font-size: 0.8em;"><?= $a['target_audiens'] ?></span></td>
                                <td><?= $a['prioritas'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($arsip)): ?>
                            <tr>
                                <td colspan="4" style="text-align:center; padding: 20px; color: #94a3b8;">Belum ada pengumuman.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>

</html>