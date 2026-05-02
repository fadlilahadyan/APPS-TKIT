<?php $current_page = basename($_SERVER['PHP_SELF']); ?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<aside class="sidebar">
    <div class="sidebar-header">
        <i class="fas fa-graduation-cap"></i> SIS TKIT FATHUROBANI
    </div>
    
    <div class="nav-group">
        <span class="nav-label">Main Menu</span>
        <a href="dashboard.php" class="nav-item <?= $current_page == 'dashboard.php' ? 'active' : '' ?>">
            <i class="fas fa-chart-pie"></i> Dashboard
        </a>
        <a href="siswa.php" class="nav-item <?= $current_page == 'siswa.php' ? 'active' : '' ?>">
            <i class="fas fa-users"></i> Data Siswa
        </a>
    </div>

    <div class="nav-group">
    <span class="nav-label">Keuangan</span>
    <a href="laporan.php" class="nav-item <?= $current_page == 'laporan.php' ? 'active' : '' ?>">
        <i class="fas fa-file-invoice-dollar"></i> Laporan Bulanan
    </a>
    <a href="tunggakan.php" class="nav-item">
        <i class="fas fa-clock-rotate-left"></i> Tunggakan
    </a>
</div>

    <a href="../auth/logout.php" class="nav-item logout">
        <i class="fas fa-right-from-bracket"></i> Keluar
    </a>
</aside>