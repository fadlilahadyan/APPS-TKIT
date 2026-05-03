<?php 
// Mendeteksi nama file aktif untuk class 'active' secara dinamis
$current_page = basename($_SERVER['PHP_SELF']); 
?>

<!-- 1. Top Bar Mobile -->
<div class="mobile-header">
    <button class="hamburger-menu" id="hamburgerBtn">
        <i class="fas fa-bars"></i>
    </button>
    <!-- Logo di-senterin pake CSS nanti -->
    <div class="mobile-logo">
        <i class="fas fa-graduation-cap"></i> 
        <span>SIS TKIT FATHUR</span>
    </div>
</div>

<!-- 2. Overlay -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- 3. Sidebar (mainSidebar) -->
<div class="sidebar" id="mainSidebar">
    <div class="sidebar-header">
        <div style="display: flex; align-items: center; gap: 10px;">
            <i class="fas fa-graduation-cap icon-header"></i> 
            <span>SIS TKIT FATHURROBBANY</span>
        </div>
    </div>
    
    <div class="nav-group">
        <span class="nav-label">Utama</span>
        <a href="dashboard.php" class="nav-item <?= $current_page == 'dashboard.php' ? 'active' : '' ?>">
            <i class="fas fa-th-large"></i> Dashboard
        </a>
    </div>

    <div class="nav-group">
        <span class="nav-label">Informasi Anak</span>
        <a href="informasi_anak.php" class="nav-item <?= $current_page == 'informasi_anak.php' ? 'active' : '' ?>">
            <i class="fas fa-user-graduate"></i> Profil Anak
        </a>
        <a href="absensi_anak.php" class="nav-item <?= $current_page == 'absensi_anak.php' ? 'active' : '' ?>">
            <i class="fas fa-calendar-check"></i> Absensi Kehadiran
        </a>
        <a href="laporan_perkembangan.php" class="nav-item <?= $current_page == 'laporan_perkembangan.php' ? 'active' : '' ?>">
            <i class="fas fa-chart-line"></i> Laporan Perkembangan
        </a>
    </div>

    <div class="nav-group">
        <span class="nav-label">Administrasi</span>
        <a href="pembayaran.php" class="nav-item <?= $current_page == 'pembayaran.php' ? 'active' : '' ?>">
            <i class="fas fa-wallet"></i> Riwayat SPP
        </a>
        <a href="pengumuman.php" class="nav-item <?= $current_page == 'pengumuman.php' ? 'active' : '' ?>">
            <i class="fas fa-bullhorn"></i> Pengumuman
        </a>
        <a href="undangan.php" class="nav-item <?= $current_page == 'undangan.php' ? 'active' : '' ?>">
            <i class="fas fa-envelope-open-text"></i> Undangan & Acara
        </a>
    </div>

    <div style="margin-top: auto; padding-bottom: 20px;">
        <a href="../auth/logout.php" class="nav-item text-danger" style="color: #ef4444;">
            <i class="fas fa-sign-out-alt"></i> Logout Akun
        </a>
    </div>
</div>

<script>
    const hamburgerBtn = document.getElementById('hamburgerBtn');
    const mainSidebar = document.getElementById('mainSidebar');
    const sidebarOverlay = document.getElementById('sidebarOverlay');

    hamburgerBtn.addEventListener('click', () => {
        mainSidebar.classList.add('active');
        sidebarOverlay.classList.add('active');
    });

    sidebarOverlay.addEventListener('click', () => {
        mainSidebar.classList.remove('active');
        sidebarOverlay.classList.remove('active');
    });
</script>