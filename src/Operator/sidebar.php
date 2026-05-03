<?php $current_page = basename($_SERVER['PHP_SELF']); ?>

<!-- 1. HEADER MOBILE (Otomatis muncul di layar HP) -->
<div class="mobile-header">
    <button class="hamburger-btn" onclick="toggleSidebar()">
        <i class="fas fa-bars"></i>
    </button>
    <div class="mobile-brand">
        <i class="fas fa-user-cog"></i> PANEL OPERATOR
    </div>
</div>

<!-- 2. OVERLAY GELAP -->
<div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

<!-- 3. SIDEBAR UTAMA -->
<div class="sidebar" id="mainSidebar">
    <div class="sidebar-header">
        <span><i class="fas fa-user-cog"></i> OPERATOR</span>
        <button class="close-sidebar-btn" onclick="toggleSidebar()">
            <i class="fas fa-times"></i>
        </button>
    </div>

    <div class="nav-group">
        <span class="nav-label">Utama</span>
        <a href="dashboard.php" class="nav-item <?= ($current_page == 'dashboard.php') ? 'active' : '' ?>">
            <i class="fas fa-tachometer-alt"></i> Dashboard
        </a>
    </div>

    <div class="nav-group">
        <span class="nav-label">Manajemen Data Master</span>
        <a href="data_siswa.php" class="nav-item <?= ($current_page == 'data_siswa.php' || $current_page == 'form_siswa.php') ? 'active' : '' ?>">
            <i class="fas fa-user-graduate"></i> Data Siswa
        </a>
        <a href="data_guru.php" class="nav-item <?= ($current_page == 'data_guru.php' || $current_page == 'form_guru.php') ? 'active' : '' ?>">
            <i class="fas fa-chalkboard-teacher"></i> Data Guru
        </a>
        <a href="data_kelas.php" class="nav-item <?= ($current_page == 'data_kelas.php' || $current_page == 'form_kelas.php') ? 'active' : '' ?>">
            <i class="fas fa-door-open"></i> Data Kelas
        </a>
    </div>

    <div class="nav-group">
        <span class="nav-label">Informasi Publik</span>
        <a href="pengumuman.php" class="nav-item <?= ($current_page == 'pengumuman.php' || $current_page == 'buat_pengumuman.php') ? 'active' : '' ?>">
            <i class="fas fa-bullhorn"></i> Pengumuman Sekolah
        </a>
    </div>

    <div class="nav-group">
        <span class="nav-label">Pelaporan</span>
        <a href="laporan_admin.php" class="nav-item <?= ($current_page == 'laporan_admin.php') ? 'active' : '' ?>">
            <i class="fas fa-file-invoice"></i> Laporan Admin
        </a>
    </div>

    <div style="margin-top: auto; padding: 0 15px;">
        <a href="../auth/logout.php" class="nav-item" style="color: #ef4444;">
            <i class="fas fa-sign-out-alt"></i> Logout Akun
        </a>
    </div>
</div>

<!-- 4. SCRIPT LOGIKA HAMBURGER -->
<script>
    function toggleSidebar() {
        document.getElementById('mainSidebar').classList.toggle('show');
        document.getElementById('sidebarOverlay').classList.toggle('show');
    }
</script>