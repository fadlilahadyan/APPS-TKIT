<?php
require_once "../config/db.php";

if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'guru') {
    header("Location: ../login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Perkembangan - TKIT FATHUROBANI</title>

    <link rel="stylesheet" href="../dashboard.css">
    <link rel="stylesheet" href="laporan.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body>
<div class="sidebar">
        <div class="sidebar-header">
            <i class="fas fa-graduation-cap" style="color: var(--accent);"></i> TKIT FATHUROBANI
        </div>
        
        <div class="nav-group">
            <span class="nav-label">Utama</span>
            <a href="../dashboard.php" class="nav-item "><i class="fas fa-th-large"></i> Dashboard</a>
        </div>

        <div class="nav-group">
            <span class="nav-label">Kelas</span>
            <a href="../presensi/halaman_absensi_guru.php" class="nav-item"><i class="fas fa-calendar-check"></i> Absensi Siswa</a>
            <a href="../laporan/laporan.php" class="nav-item active"><i class="fas fa-chart-line"></i> Laporan Perkembangan</a>
        </div>

        <div class="nav-group">
            <span class="nav-label">Keuangan</span>
            <a href="../informasi_spp/informasi_spp.php" class="nav-item "><i class="fas fa-wallet"></i> Informasi SPP</a>
        </div>



        <div style="margin-top: auto;">
            <a href="../auth/logout.php" class="nav-item" style="color: #ef4444;"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>

<div class="main-content">

    <div class="header">
        <h1>Laporan Perkembangan</h1>
        <p>Input data perkembangan siswa agar terpantau orang tua</p>
    </div>

    <!-- FORM INPUT -->
    <div class="form-container">
        <form action="proses_simpan_laporan.php" method="POST">

            <div class="input-grid">
                <div class="form-group">
                    <label>Pilih Kelas</label>
                    <select name="kelas" required>
                        <option value="">-- Pilih Kelas --</option>
                        <option value="TK A">TK A</option>
                        <option value="TK B">TK B</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Nama Siswa</label>
                    <select name="id_siswa" required>
                        <option value="">-- Pilih Siswa --</option>
                        <?php
                        $query = $pdo->query("SELECT id_siswa, nama_siswa FROM siswa ORDER BY nama_siswa ASC");
                        while ($s = $query->fetch(PDO::FETCH_ASSOC)) {
                            echo "<option value='{$s['id_siswa']}'>{$s['nama_siswa']}</option>";
                        }
                        ?>
                    </select>
                </div>
            </div>

            <div class="section-divider">Aspek Perkembangan</div>

            <div class="form-group">
                <label>Agama & Moral</label>
                <textarea name="agama_moral" rows="3"></textarea>
            </div>

            <div class="form-group">
                <label>Fisik Motorik</label>
                <textarea name="fisik_motorik" rows="3"></textarea>
            </div>

            <div class="form-group">
                <label>Kognitif (Berpikir)</label>
                <textarea name="kognitif_bahasa" rows="3"></textarea>
            </div>

            <div class="btn-area">
                <button type="submit" class="btn-save">
                    <i class="fas fa-paper-plane"></i> Kirim Laporan
                </button>
            </div>
        </form>
    </div>

    <!-- RIWAYAT -->
    <div class="history-card">
        <h3>Riwayat Input Perkembangan</h3>

        <table class="table-laporan">
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Nama Siswa</th>
                    <th>Agama</th>
                    <th>Fisik</th>
                    <th>Kognitif</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $id_guru = $_SESSION['id_user'];

            $stmt = $pdo->prepare("
                SELECT lp.*, s.nama_siswa
                FROM laporan_perkembangan lp
                JOIN siswa s ON lp.id_siswa = s.id_siswa
                WHERE lp.id_guru = :id_guru
                ORDER BY lp.id_laporan DESC
            ");
            $stmt->execute(['id_guru' => $id_guru]);

            while ($data = $stmt->fetch()) {
                echo "<tr>
                    <td>{$data['tanggal']}</td>
                    <td>{$data['nama_siswa']}</td>
                    <td>" . htmlspecialchars(substr($data['agama_moral'],0,30)) . "</td>
                    <td>" . htmlspecialchars(substr($data['fisik_motorik'],0,30)) . "</td>
                    <td>" . htmlspecialchars(substr($data['kognitif_bahasa'],0,30)) . "</td>
                </tr>";
            }
            ?>
            </tbody>
        </table>
    </div>

</div>
</body>
</html>