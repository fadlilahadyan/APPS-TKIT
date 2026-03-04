# APPS-TKIT
Tugas Kelompok Rancang Bangun Perangkat Lunak Kelompok 3, memahami penggunaan GitHub dalam pengembangan perangkat Lunak

# Tkit-Fathurrobbany
Tugas Kelompok Rancang Bangun Perangkat Lunak Kelompok 3, memahami penggunaan GitHub dalam 
# Sistem Informasi TKIT Fathurrobbany

Project ini merupakan tugas kelompok mata kuliah Rekayasa Perangkat Lunak (RBPL).  
Sistem ini digunakan untuk membantu pengelolaan data sekolah TKIT Fathurrobbany.

---

## Fitur Utama

- Login & Logout (Session Management)
- Laporan Perkembangan Siswa
- Informasi SPP
- Presensi Siswa

---

## Pembagian Tugas

### Team Lead (Fadlilahadian)
- Setup repository GitHub
- Branching strategy
- Review Pull Request
- Login & Session Management

### Backend Developer (Najmi)
- CRUD Laporan Perkembangan
- Integrasi database PDO
- Validasi session guru

### Backend / Feature Developer (Ayesha)
- Informasi SPP
- Generate laporan akhir PDF

### Feature Developer + Testing (Devin)
- Presensi
- Unit Testing

---

## Teknologi yang Digunakan

- PHP 8
- MySQL
- PDO
- HTML & CSS
- XAMPP
- Git & GitHub

---

## Struktur Folder
Tkit-Fathurrobbany/
│
├── docs/
├── src/
│ ├── config/
│ ├── auth/
│ ├── laporan/
│ ├── spp/
│ └── presensi/
│
├── tests/
└── README.md

---

## Cara Menjalankan Project

1. Clone repository
2. Pindahkan ke folder `htdocs` XAMPP
3. Import database ke phpMyAdmin
4. Jalankan melalui browser:

   http://localhost/Tkit-Fathurrobbany

---

## Keamanan

- Session validation
- PDO prepared statement (anti SQL Injection)
- htmlspecialchars() untuk mencegah XSS

---

## Status Project

Test 1: Koneksi Database
✅ Koneksi database berhasil
Test 2: Cek Tabel laporan_perkembangan
✅ Tabel ditemukan. Total data saat ini: 5
Test 3: Cek Struktur Kolom
✅ Struktur tabel tersedia
Test 4: Simulasi Insert Data
✅ Insert data berhasil