<?php
// guru/informasi_spp.php
session_start();
// PERBAIKAN PATH DB: Mundur satu folder ('../')
require_once '../config/db.php';

// Cek Login
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'guru') {
    header("Location: ../auth/login.php");
    exit();
}

function rupiah($n){ return 'Rp ' . number_format((int)$n, 0, ',', '.'); }

$bulan = isset($_GET['bulan']) ? (int)$_GET['bulan'] : (int)date('m');
$tahun = isset($_GET['tahun']) ? (int)$_GET['tahun'] : (int)date('Y');
if ($bulan < 1 || $bulan > 12) $bulan = (int)date('m');
if ($tahun < 2000 || $tahun > 2100) $tahun = (int)date('Y');

// AUTO CREATE TABLE (jika belum ada)
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
$success = null;

// Mengambil Data Siswa yang Aktif + Relasi Kelas untuk Dropdown SPP
$stmtSiswa = $pdo->query("
    SELECT s.id_siswa, s.nama_siswa, k.nama_kelas 
    FROM siswa s 
    LEFT JOIN kelas k ON s.id_kelas = k.id_kelas 
    WHERE s.status = 'Aktif' 
    ORDER BY s.nama_siswa ASC
");
$listSiswa = $stmtSiswa->fetchAll(PDO::FETCH_ASSOC);

// PROSES SIMPAN (CREATE)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create') {
  $id_siswa = trim($_POST['id_siswa'] ?? '');
  $jumlah_raw = trim($_POST['jumlah'] ?? '');
  $jumlah = (int)preg_replace('/[^0-9]/', '', $jumlah_raw);
  $status = $_POST['status'] ?? 'BELUM';
  $tanggal_bayar = trim($_POST['tanggal_bayar'] ?? '');

  if ($id_siswa === '' || $jumlah <= 0) {
    $err = "Pilih Siswa dan masukkan Jumlah pembayaran yang valid.";
  } elseif (!in_array($status, ['LUNAS','BELUM'], true)) {
    $err = "Status tidak valid.";
  } elseif ($status === 'LUNAS' && $tanggal_bayar === '') {
    $err = "Tanggal bayar wajib diisi jika status SPP LUNAS.";
  } else {
    $stmtGetSiswa = $pdo->prepare("
        SELECT s.nama_siswa, k.nama_kelas 
        FROM siswa s 
        LEFT JOIN kelas k ON s.id_kelas = k.id_kelas 
        WHERE s.id_siswa = ?
    ");
    $stmtGetSiswa->execute([$id_siswa]);
    $dataSiswa = $stmtGetSiswa->fetch(PDO::FETCH_ASSOC);
    
    if ($dataSiswa) {
        $nama_real = $dataSiswa['nama_siswa'];
        $kelas_real = $dataSiswa['nama_kelas'] ?? 'Tidak Ada Kelas';
        $tgl = ($tanggal_bayar === '') ? null : $tanggal_bayar;

        try {
          $stmt = $pdo->prepare("
            INSERT INTO spp_status (nama, kelas, jumlah, bulan, tahun, tanggal_bayar, status)
            VALUES (:nama, :kelas, :jumlah, :bulan, :tahun, :tanggal_bayar, :status)
          ");
          $stmt->execute([
            ':nama' => $nama_real,
            ':kelas' => $kelas_real,
            ':jumlah' => $jumlah,
            ':bulan' => $bulan,
            ':tahun' => $tahun,
            ':tanggal_bayar' => $tgl,
            ':status' => $status
          ]);

          header("Location: informasi_spp.php?bulan=$bulan&tahun=$tahun&msg=success");
          exit;
        } catch (PDOException $e) {
          $err = "Gagal simpan: " . $e->getMessage();
        }
    } else {
        $err = "Data siswa tidak ditemukan di sistem.";
    }
  }
}

// PROSES HAPUS
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
  $id = (int)($_POST['id'] ?? 0);
  if ($id > 0) {
    try {
      $stmt = $pdo->prepare("DELETE FROM spp_status WHERE id = :id");
      $stmt->execute([':id' => $id]);
      header("Location: informasi_spp.php?bulan=$bulan&tahun=$tahun&msg=deleted");
      exit;
    } catch (PDOException $e) {
      $err = "Gagal hapus: " . $e->getMessage();
    }
  }
}

// Cek Pesan Sukses
if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'success') $success = "Data pembayaran SPP berhasil disimpan.";
    if ($_GET['msg'] === 'deleted') $success = "Data SPP berhasil dihapus.";
}

// LIST DATA BULAN & TAHUN INI
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
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
  <title>Informasi SPP - TKIT FATHUROBANI</title>
  
  <link rel="stylesheet" href="dashboard.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  
  <style>
    /* FIX LAYOUT MELEBAR */
    body { overflow-x: hidden; } /* Kunci layar biar nggak bisa digeser ke samping */
    .main-content { max-width: 100vw; box-sizing: border-box; }

    .header { margin-bottom: 25px; }
    .header h1 { font-size: 26px; font-weight: 800; color: #1e293b; margin: 0 0 8px 0; }
    .header p { color: #64748b; font-size: 14px; margin: 0; line-height: 1.5; }

    /* Penguncian max-width dan overflow pada Card agar Tabel tidak menjebol layar */
    .content-card { background: white; border-radius: 20px; padding: 25px; margin-bottom: 25px; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.02); border: 1px solid #f1f5f9; width: 100%; box-sizing: border-box; overflow: hidden; }
    .card-header { font-size: 16px; font-weight: 800; color: #1e293b; margin-bottom: 20px; display: flex; align-items: center; }

    /* Filter Form Modern */
    .filter-wrapper { display: flex; gap: 15px; align-items: flex-end; margin-top: 20px; width: 100%; }
    .filter-group { display: flex; flex-direction: column; gap: 8px; flex: 1; }
    .filter-label { font-size: 12px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; }
    
    .input-modern { width: 100%; padding: 14px 16px; border-radius: 12px; border: 1px solid #cbd5e1; font-family: 'Inter', sans-serif; font-size: 14px; background: #f8fafc; outline: none; transition: 0.3s; color: #1e293b; font-weight: 500; box-sizing: border-box;}
    .input-modern:focus { border-color: #3b82f6; background: #fff; box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1); }
    
    .btn-action { padding: 14px 24px; border-radius: 12px; font-weight: 700; cursor: pointer; transition: 0.3s; border: none; display: inline-flex; align-items: center; justify-content: center; gap: 8px; font-size: 14px;}
    .btn-primary { background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); color: white; box-shadow: 0 4px 10px rgba(59, 130, 246, 0.3); }
    .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 6px 15px rgba(59, 130, 246, 0.4); }
    .btn-danger-outline { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; padding: 8px 12px; font-size: 12px; width: 100%;}
    .btn-danger-outline:hover { background: #fee2e2; }

    /* Grid Input Form */
    .spp-grid { display: grid; grid-template-columns: 2fr 1fr 1.5fr 1fr; gap: 20px; align-items: flex-end; }

    /* Table Modern - Dibuat aman untuk HP */
    .table-wrapper { width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch; padding-bottom: 10px; }
    .modern-table { width: 100%; border-collapse: collapse; min-width: 650px; }
    .modern-table th { text-align: left; padding: 16px 20px; font-size: 12px; color: #64748b; font-weight: 700; text-transform: uppercase; border-bottom: 2px solid #e2e8f0; background: #f8fafc; white-space: nowrap;}
    .modern-table td { padding: 16px 20px; font-size: 14px; border-bottom: 1px solid #f1f5f9; color: #334155; vertical-align: middle;}
    .modern-table tbody tr:hover td { background: #f8fafc; }
    
    .badge { padding: 6px 12px; border-radius: 20px; font-size: 11px; font-weight: 800; letter-spacing: 0.5px; display: inline-block; white-space: nowrap;}
    .badge.lunas { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0;}
    .badge.belum { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca;}

    .alert { padding: 16px 20px; border-radius: 12px; margin-bottom: 25px; font-weight: 600; display: flex; align-items: center; gap: 10px; font-size: 14px;}
    .alert-success { background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; }
    .alert-error { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }

    /* --- RESPONSIVITAS TOTAL UNTUK HP --- */
    @media (max-width: 768px) {
      /* Form Input Utama diset ke bawah */
      .spp-grid { grid-template-columns: 1fr; gap: 15px; } 
      
      /* Form Filter diset Grid yang cantik */
      .filter-wrapper { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
      .filter-wrapper .btn-action { grid-column: span 2; width: 100%; margin-top: 5px; }
      
      /* Card di HP dikecilkan paddingnya biar layar lega */
      .content-card { padding: 20px 15px; }
    }
  </style>
</head>
<body>
   
  <!-- PANGGIL SIDEBAR -->
  <?php include 'sidebar.php'; ?>

  <main class="main-content">
    <div class="header">
      <h1>Informasi SPP</h1>
      <p>Kelola dan pantau status pembayaran SPP siswa terintegrasi dengan data master.</p>

      <form method="GET" class="filter-wrapper">
        <div class="filter-group">
            <select name="bulan" class="input-modern">
                <?php for($m=1;$m<=12;$m++): ?>
                <option value="<?= $m ?>" <?= ($m===$bulan?'selected':'') ?>>
                    <?= date('F', mktime(0,0,0,$m,1)) ?>
                </option>
                <?php endfor; ?>
            </select>
        </div>
        <div class="filter-group">
            <select name="tahun" class="input-modern">
                <?php for($y=(int)date('Y')-2; $y<=(int)date('Y')+2; $y++): ?>
                <option value="<?= $y ?>" <?= ($y===$tahun?'selected':'') ?>><?= $y ?></option>
                <?php endfor; ?>
            </select>
        </div>
        <button class="btn-action btn-primary" type="submit"><i class="fas fa-filter"></i> Terapkan</button>
      </form>
    </div>

    <?php if ($err): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-triangle" style="font-size: 20px;"></i> <?= htmlspecialchars($err) ?>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle" style="font-size: 20px;"></i> <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>

    <!-- Form Input SPP -->
    <section class="content-card">
      <div class="card-header"><i class="fas fa-plus-circle" style="color: #3b82f6; margin-right: 8px;"></i> Input Pembayaran SPP</div>

      <form method="POST">
        <input type="hidden" name="action" value="create">

        <div class="spp-grid">
          <div class="filter-group">
            <span class="filter-label">Nama Siswa</span>
            <select class="input-modern" name="id_siswa" required>
              <option value="">-- Pilih Siswa Aktif --</option>
              <?php foreach ($listSiswa as $s): ?>
                  <option value="<?= htmlspecialchars($s['id_siswa']) ?>">
                      <?= htmlspecialchars($s['nama_siswa']) ?> - <?= htmlspecialchars($s['nama_kelas'] ?? 'Tanpa Kelas') ?>
                  </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="filter-group">
            <span class="filter-label">Jumlah (Rp)</span>
            <input class="input-modern" name="jumlah" type="number" placeholder="Contoh: 150000" required>
          </div>

          <div class="filter-group">
            <span class="filter-label">Tanggal Bayar</span>
            <input class="input-modern" name="tanggal_bayar" type="date" value="<?= date('Y-m-d') ?>">
          </div>

          <div class="filter-group">
            <span class="filter-label">Status SPP</span>
            <select class="input-modern" name="status">
              <option value="LUNAS">Lunas</option>
              <option value="BELUM">Belum Bayar</option>
            </select>
          </div>
        </div>

        <button class="btn-action btn-primary" type="submit" style="width: 100%; margin-top: 25px;"><i class="fas fa-save"></i> Simpan Data SPP</button>
      </form>
    </section>

    <!-- Tabel Riwayat -->
    <section class="content-card">
      <div class="card-header"><i class="fas fa-list" style="color: #3b82f6; margin-right: 8px;"></i> Riwayat SPP Bulan Ini (<?= count($rows) ?> Data)</div>

      <div class="table-wrapper">
          <table class="modern-table">
            <thead>
              <tr>
                <th width="25%">Nama Siswa</th>
                <th width="15%">Kelas</th>
                <th width="20%">Jumlah Bayar</th>
                <th width="15%">Tanggal</th>
                <th width="15%">Status</th>
                <th width="10%">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php if (!$rows): ?>
                <tr>
                  <td colspan="6" style="color:#94a3b8; text-align: center; padding: 40px;">
                    <i class="fas fa-folder-open" style="font-size: 30px; display: block; margin-bottom: 10px;"></i>
                    Belum ada data pembayaran untuk bulan ini.
                  </td>
                </tr>
              <?php endif; ?>

              <?php foreach($rows as $r): ?>
                <tr>
                  <td style="font-weight: 700; color: #1e293b;"><?= htmlspecialchars($r['nama']) ?></td>
                  <td><span style="background: #f1f5f9; color: #475569; padding: 4px 8px; border-radius: 6px; font-size: 12px; font-weight: 700;"><?= htmlspecialchars($r['kelas']) ?></span></td>
                  <td style="font-weight: 800; color: #10b981;"><?= rupiah($r['jumlah']) ?></td>
                  <td style="color: #64748b; font-size: 13px; white-space: nowrap;"><i class="far fa-calendar-alt" style="margin-right: 4px;"></i> <?= $r['tanggal_bayar'] ? date('d M Y', strtotime($r['tanggal_bayar'])) : '-' ?></td>
                  <td>
                    <?php if ($r['status'] === 'LUNAS'): ?>
                      <span class="badge lunas"><i class="fas fa-check"></i> Lunas</span>
                    <?php else: ?>
                      <span class="badge belum"><i class="fas fa-times"></i> Belum</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <form method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus data SPP ini? Data tidak bisa dikembalikan.')">
                      <input type="hidden" name="action" value="delete">
                      <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                      <button class="btn-action btn-danger-outline" type="submit"><i class="fas fa-trash-alt"></i> Hapus</button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
      </div>
    </section>

  </main>

</body>
</html>