Sistem Informasi Akademik TKIT Fathurrobbany
Dokumen ini menjelaskan alur navigasi dan logika kerja aplikasi dari sudut pandang pengguna (Guru).
1. Proses Autentikasi
•	Akses Halaman Utama: Pengguna membuka aplikasi dan diarahkan ke halaman index.php (Login).
•	Login: Pengguna memasukkan username dan password. Sistem melakukan pengecekan ke database melalui src/config/db.php.
•	Session Management: Jika berhasil, sistem membuat session untuk menjaga status login pengguna.
•	Redirect: Pengguna diarahkan ke src/dashboard.php.
2. Navigasi Dashboard
•	Di halaman dashboard, pengguna dapat melihat ringkasan data dan memilih menu navigasi menuju 4 fitur utama:
1.	Laporan Perkembangan.
2.	Informasi SPP.
3.	Presensi.
4.	Logout.
3. Alur Fitur Utama
A. Laporan Perkembangan (CRUD)
•	Menu Laporan: Pengguna masuk ke src/laporan/laporan.php.
•	Tambah Data: Pengguna mengisi form laporan (Kognitif, Motorik, dll).
•	Proses Simpan: Data dikirim ke src/laporan/proses_simpan_laporan.php untuk divalidasi dan disimpan ke database menggunakan PDO.
•	Lihat/Update: Pengguna dapat melihat daftar laporan yang sudah masuk dan melakukan perubahan jika diperlukan.
B. Informasi SPP
•	Akses Data: Pengguna masuk ke src/informasi_spp/informasi_spp.php.
•	Cek Pembayaran: Sistem menampilkan status pembayaran siswa.
•	Cetak PDF: Pengguna dapat mengunduh laporan SPP dalam format PDF.
C. Presensi Siswa
•	Input Kehadiran: Pengguna masuk ke src/presensi/presensi.php.
•	Simpan Presensi: Pengguna memilih status kehadiran siswa (Hadir/Izin/Sakit/Alpa) dan menyimpannya ke database.
4. Proses Keluar (Logout)
•	Logout: Pengguna menekan tombol Logout.
•	Destroy Session: Sistem menjalankan src/auth/logout.php untuk menghapus semua data session.
•	Redirect: Pengguna dikembalikan ke halaman login awal.