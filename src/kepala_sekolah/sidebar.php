<?php $current_page = basename($_SERVER['PHP_SELF']); ?>

<!-- 1. Top Bar Mobile (Logo Biru & Senter) -->
<div class="mobile-header">
    <button id="hamburgerBtn" class="hamburger-menu">
        <i class="fas fa-bars"></i>
    </button>
    <div class="mobile-logo">
        <i class="fas fa-graduation-cap"></i> 
        <span>SIS KEPALA SEKOLAH</span>
    </div>
</div>

<!-- 2. Overlay (Biar UX lebih mantap) -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- 3. Sidebar Utama -->
<div class="sidebar" id="mainSidebar">
    <div class="sidebar-header">
        <!-- WARNA UNGU SUDAH DIGANTI JADI BIRU (primary) -->
        <i class="fas fa-graduation-cap" style="color: var(--primary);"></i> TKIT FATHUROBANI
    </div>
    
    <div class="nav-group">
        <span class="nav-label">Utama</span>
        <a href="dashboard.php" class="nav-item <?= $current_page == 'dashboard.php' ? 'active' : '' ?>">
            <i class="fas fa-th-large"></i> Dashboard
        </a>
    </div>

    <div class="nav-group">
        <span class="nav-label">Monitoring</span>
        <a href="aktifitas_guru.php" class="nav-item <?= $current_page == 'aktifitas_guru.php' ? 'active' : '' ?>">
            <i class="fas fa-chalkboard-teacher"></i> Aktivitas Guru
        </a>
        <a href="monitoring_laporan.php" class="nav-item <?= $current_page == 'monitoring_laporan.php' ? 'active' : '' ?>">
            <i class="fas fa-chart-line"></i> Laporan Perkembangan
        </a>
        <a href="monitoring_spp.php" class="nav-item <?= $current_page == 'monitoring_spp.php' ? 'active' : '' ?>">
            <i class="fas fa-wallet"></i> Laporan SPP
        </a>
    </div>

    <div class="nav-group">
        <span class="nav-label">Manajemen</span>
        <a href="data_guru.php" class="nav-item <?= $current_page == 'data_guru.php' ? 'active' : '' ?>">
            <i class="fas fa-chalkboard-teacher"></i> Data Guru
        </a>
        <a href="data_siswa.php" class="nav-item <?= $current_page == 'data_siswa.php' ? 'active' : '' ?>">
            <i class="fas fa-user-graduate"></i> Data Siswa
        </a>
    </div>

    <div class="nav-group">
        <span class="nav-label">Informasi</span>
        <a href="pengumuman.php" class="nav-item <?= $current_page == 'pengumuman.php' ? 'active' : '' ?>">
            <i class="fas fa-bullhorn"></i> Pengumuman
        </a>
    </div>

    <div style="margin-top: auto; padding-top: 20px;">
        <a href="../auth/logout.php" class="nav-item" style="color: #ef4444;">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>
</div>

<script>
    const btn = document.getElementById('hamburgerBtn');
    const sidebar = document.getElementById('mainSidebar');
    const overlay = document.getElementById('sidebarOverlay');

    btn.onclick = () => {
        sidebar.classList.toggle('active');
        overlay.classList.toggle('active');
    };

    overlay.onclick = () => {
        sidebar.classList.remove('active');
        overlay.classList.remove('active');
    };
</script>