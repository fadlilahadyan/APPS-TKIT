<?php 
// Mendeteksi nama file aktif untuk class 'active' secara dinamis
$current_page = basename($_SERVER['PHP_SELF']); 
?>

<!-- Mobile Header (Hanya muncul di layar kecil) -->
<div class="mobile-header">
    <button class="hamburger-btn" onclick="toggleSidebar()">
        <i class="fas fa-bars"></i>
    </button>
    <div class="mobile-brand" style="flex: 1; text-align: center; margin-right: 24px; font-size: 15px;">
        <i class="fas fa-graduation-cap"></i> SIS TKIT FATHUR
    </div>
</div>

<!-- Overlay Transparan untuk Mobile -->
<div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

<div class="sidebar" id="mainSidebar">
    <div class="sidebar-header">
        <i class="fas fa-graduation-cap" style="font-size: 24px; color: #3b82f6;"></i> SIS TKIT FATHUR
        <button class="close-sidebar-btn" onclick="toggleSidebar()">
            <i class="fas fa-times"></i>
        </button>
    </div>
    
    <div class="nav-group">
        <span class="nav-label">Utama</span>
        <a href="dashboard.php" class="nav-item <?= $current_page == 'dashboard.php' ? 'active' : '' ?>"><i class="fas fa-th-large"></i> Dashboard</a>
    </div>
    
    <div class="nav-group">
        <span class="nav-label">Kelas & Akademik</span>
        <a href="presensi.php" class="nav-item <?= $current_page == 'presensi.php' ? 'active' : '' ?>"><i class="fas fa-calendar-check"></i> Absensi Siswa</a>
        <a href="laporan.php" class="nav-item <?= $current_page == 'laporan.php' ? 'active' : '' ?>"><i class="fas fa-chart-line"></i> Laporan Perkembangan</a>
    </div>

    <div class="nav-group">
        <span class="nav-label">Komunikasi Orang Tua</span>
        <a href="buat_pengumuman.php" class="nav-item <?= $current_page == 'buat_pengumuman.php' ? 'active' : '' ?>"><i class="fas fa-bullhorn"></i> Pengumuman</a>
        <a href="form_undangan.php" class="nav-item <?= ($current_page == 'undangan.php' || $current_page == 'form_undangan.php') ? 'active' : '' ?>"><i class="fas fa-envelope-open-text"></i> Undangan & RSVP</a>
    </div>

    <div class="nav-group">
        <span class="nav-label">Keuangan</span>
        <a href="informasi_spp.php" class="nav-item <?= $current_page == 'informasi_spp.php' ? 'active' : '' ?>"><i class="fas fa-wallet"></i> Informasi SPP</a>
    </div>

    <div style="margin-top: auto; padding-bottom: 20px;">
        <a href="../auth/logout.php" class="nav-item" style="color: #ef4444; background: #fef2f2; border: 1px solid #fecaca;"><i class="fas fa-sign-out-alt"></i> Logout Akun</a>
    </div>
</div>

<!-- Script untuk Toggle Sidebar di Mobile -->
<script>
function toggleSidebar() {
    const sidebar = document.getElementById('mainSidebar');
    const overlay = document.getElementById('sidebarOverlay');
    sidebar.classList.toggle('show');
    overlay.classList.toggle('show');
}
</script>