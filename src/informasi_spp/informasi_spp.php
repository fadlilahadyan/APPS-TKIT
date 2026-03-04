<?php
require_once __DIR__ . '/../config/db.php';
if (!isset($pdo)) die("Koneksi PDO tidak ditemukan. Cek src/config/db.php");

function rupiah($n){ return 'Rp ' . number_format((int)$n, 0, ',', '.'); }

$bulan = isset($_GET['bulan']) ? (int)$_GET['bulan'] : (int)date('m');
$tahun = isset($_GET['tahun']) ? (int)$_GET['tahun'] : (int)date('Y');
if ($bulan < 1 || $bulan > 12) $bulan = (int)date('m');
if ($tahun < 2000 || $tahun > 2100) $tahun = (int)date('Y');

// 1) AUTO CREATE TABLE supaya gak error & simpan pasti bisa
$pdo->exec("
  CREATE TABLE IF NOT EXISTS spp_status (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    kelas VARCHAR(50) NOT NULL,
    jumlah INT NOT NULL,
    bulan TINYINT NOT NULL,
    tahun SMALLINT NOT NULL,
    tanggal_bayar DATE NULL,
    status ENUM('LUNAS','BELUM') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
  )
");

$err = null;

// 2) SIMPAN (CREATE)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create') {
  $nama = trim($_POST['nama'] ?? '');
  $kelas = trim($_POST['kelas'] ?? '');
  $jumlah_raw = trim($_POST['jumlah'] ?? '');
  $jumlah = (int)preg_replace('/[^0-9]/', '', $jumlah_raw); // aman kalau user ketik titik/koma
  $status = $_POST['status'] ?? 'BELUM';
  $tanggal_bayar = trim($_POST['tanggal_bayar'] ?? '');

  if ($nama === '' || $kelas === '' || $jumlah <= 0) {
    $err = "Nama, Kelas, dan Jumlah wajib diisi.";
  } elseif (!in_array($status, ['LUNAS','BELUM'], true)) {
    $err = "Status tidak valid.";
  } elseif ($status === 'LUNAS' && $tanggal_bayar === '') {
    $err = "Tanggal bayar wajib jika status LUNAS.";
  } else {
    $tgl = ($tanggal_bayar === '') ? null : $tanggal_bayar;

    try {
      $stmt = $pdo->prepare("
        INSERT INTO spp_status (nama, kelas, jumlah, bulan, tahun, tanggal_bayar, status)
        VALUES (:nama, :kelas, :jumlah, :bulan, :tahun, :tanggal_bayar, :status)
      ");
      $stmt->execute([
        ':nama' => $nama,
        ':kelas' => $kelas,
        ':jumlah' => $jumlah,
        ':bulan' => $bulan,
        ':tahun' => $tahun,
        ':tanggal_bayar' => $tgl,
        ':status' => $status
      ]);

      header("Location: status_spp.php?bulan=$bulan&tahun=$tahun");
      exit;
    } catch (PDOException $e) {
      $err = "Gagal simpan: " . $e->getMessage();
    }
  }
}

// 3) HAPUS
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
  $id = (int)($_POST['id'] ?? 0);
  if ($id > 0) {
    try {
      $stmt = $pdo->prepare("DELETE FROM spp_status WHERE id = :id");
      $stmt->execute([':id' => $id]);
    } catch (PDOException $e) {
      $err = "Gagal hapus: " . $e->getMessage();
    }
  }
  header("Location: status_spp.php?bulan=$bulan&tahun=$tahun");
  exit;
}

// 4) LIST DATA
$stmt = $pdo->prepare("
  SELECT * FROM spp_status
  WHERE bulan = :bulan AND tahun = :tahun
  ORDER BY id DESC
");
$stmt->execute([':bulan' => $bulan, ':tahun' => $tahun]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Status SPP</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <link rel="stylesheet" href="../dashboard.css">
  <link rel="stylesheet" href="informasi_spp.css">
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
            <a href="../laporan/laporan.php" class="nav-item"><i class="fas fa-chart-line"></i> Laporan Perkembangan</a>
        </div>

        <div class="nav-group">
            <span class="nav-label">Keuangan</span>
            <a href="informasi_spp.php" class="nav-item active"><i class="fas fa-wallet"></i> Informasi SPP</a>
        </div>



        <div style="margin-top: auto;">
            <a href="../auth/logout.php" class="nav-item" style="color: #ef4444;"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>

<main class="main-content">
  <div class="header">
    <h1>Status SPP</h1>
    <p>Input pembayaran dan lihat data yang sudah masuk</p>

    <form class="keu-filter" method="GET" style="margin-top:14px;">
      <select name="bulan" class="keu-select">
        <?php for($m=1;$m<=12;$m++): ?>
          <option value="<?= $m ?>" <?= ($m===$bulan?'selected':'') ?>>
            <?= date('F', mktime(0,0,0,$m,1)) ?>
          </option>
        <?php endfor; ?>
      </select>

      <select name="tahun" class="keu-select">
        <?php for($y=(int)date('Y')-2; $y<=(int)date('Y')+2; $y++): ?>
          <option value="<?= $y ?>" <?= ($y===$tahun?'selected':'') ?>><?= $y ?></option>
        <?php endfor; ?>
      </select>

      <button class="keu-btn" type="submit">Terapkan</button>
    </form>
  </div>

  <?php if ($err): ?>
    <section class="content-card" style="border-color:#fecaca;background:#fff1f2;margin-bottom:24px;">
      <div class="card-header" style="margin-bottom:10px;">Error</div>
      <div style="color:#991b1b;font-weight:700;"><?= htmlspecialchars($err) ?></div>
    </section>
  <?php endif; ?>

  <!-- INPUT -->
  <section class="content-card">
    <div class="card-header">Input SPP</div>

    <form method="POST">
      <input type="hidden" name="action" value="create">

      <div style="display:grid;grid-template-columns:repeat(5,1fr);gap:12px;">
        <div>
          <div style="font-size:12px;color:var(--text-muted);margin-bottom:6px;font-weight:600;">Nama</div>
          <input class="keu-input" style="width:100%;" name="nama" placeholder="Nama siswa" required>
        </div>

        <div>
          <div style="font-size:12px;color:var(--text-muted);margin-bottom:6px;font-weight:600;">Kelas</div>
          <input class="keu-input" style="width:100%;" name="kelas" placeholder="Contoh: TK A1" required>
        </div>

        <div>
          <div style="font-size:12px;color:var(--text-muted);margin-bottom:6px;font-weight:600;">Jumlah</div>
          <input class="keu-input" style="width:100%;" name="jumlah" type="text" placeholder="500000" required>
        </div>

        <div>
          <div style="font-size:12px;color:var(--text-muted);margin-bottom:6px;font-weight:600;">Tanggal Bayar</div>
          <input class="keu-input" style="width:100%;" name="tanggal_bayar" type="date">
        </div>

        <div>
          <div style="font-size:12px;color:var(--text-muted);margin-bottom:6px;font-weight:600;">Status</div>
          <select class="keu-select" style="width:100%;" name="status">
            <option value="LUNAS">Lunas</option>
            <option value="BELUM">Belum Bayar</option>
          </select>
        </div>
      </div>

      <div style="display:flex;justify-content:flex-end;margin-top:14px;">
        <button class="keu-btn" type="submit">Simpan</button>
      </div>
    </form>
  </section>

  <div style="height:24px;"></div>

  <!-- DATA -->
  <section class="content-card">
    <div class="card-header">Data SPP (<?= count($rows) ?>)</div>

    <table class="keu-table">
      <thead>
        <tr>
          <th>Nama</th>
          <th>Kelas</th>
          <th>Jumlah</th>
          <th>Tanggal Bayar</th>
          <th>Status</th>
          <th style="width:120px;">Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$rows): ?>
          <tr>
            <td colspan="6" style="color:var(--text-muted);">Belum ada data untuk bulan ini.</td>
          </tr>
        <?php endif; ?>

        <?php foreach($rows as $r): ?>
          <tr>
            <td><?= htmlspecialchars($r['nama']) ?></td>
            <td><?= htmlspecialchars($r['kelas']) ?></td>
            <td><?= rupiah($r['jumlah']) ?></td>
            <td><?= $r['tanggal_bayar'] ? htmlspecialchars($r['tanggal_bayar']) : '-' ?></td>
            <td>
              <?php if ($r['status'] === 'LUNAS'): ?>
                <span class="badge lunas">Lunas</span>
              <?php else: ?>
                <span class="badge belum">Belum</span>
              <?php endif; ?>
            </td>
            <td>
              <div class="keu-actions">
                <form method="POST" onsubmit="return confirm('Hapus data ini?')">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                  <button class="keu-linkbtn" type="submit">Hapus</button>
                </form>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </section>

</main>
</body>
</html>