# 📚 Aplikasi Peminjaman Buku

Sistem informasi perpustakaan berbasis web yang membantu proses peminjaman dan pengembalian buku secara digital. Aplikasi ini memudahkan siswa dalam mencari dan meminjam buku serta membantu admin dalam mengelola data buku, transaksi, dan denda.

![PHP](https://img.shields.io/badge/PHP-8.x-777BB4?style=for-the-badge&logo=php)
![MySQL](https://img.shields.io/badge/MySQL-4479A1?style=for-the-badge&logo=mysql)
![Bootstrap](https://img.shields.io/badge/Bootstrap-5-7952B3?style=for-the-badge&logo=bootstrap)
![Status](https://img.shields.io/badge/Status-Development-success?style=for-the-badge)

---

## 🎯 Tujuan Program

Aplikasi ini dibuat untuk:

- Mempermudah proses peminjaman dan pengembalian buku.
- Membantu admin mengelola data perpustakaan secara digital.
- Mencatat seluruh aktivitas peminjaman dan pengembalian.
- Mengelola denda keterlambatan secara otomatis.
- Memantau stok buku dengan lebih mudah.

---

## 📌 Ruang Lingkup

Aplikasi mencakup beberapa modul utama:

- 📚 Daftar Buku
- 📖 Detail Buku
- 📝 Peminjaman Buku
- 🔄 Pengembalian Buku
- 💳 Transaksi
- 💰 Denda
- 📋 Histori Peminjaman

---

## 👥 Pengguna Aplikasi

### 👨‍🎓 Siswa

Siswa dapat:

- Login ke dalam aplikasi
- Melihat daftar buku
- Melihat detail buku
- Mencari buku berdasarkan judul, penulis, atau genre
- Melakukan peminjaman buku
- Melihat status peminjaman
- Mengembalikan buku
- Melihat histori peminjaman
- Melihat informasi denda

### 👨‍💼 Admin

Admin dapat:

- Login ke dalam aplikasi
- Mengelola data buku (CRUD)
- Mengelola data siswa (CRUD)
- Mengelola data admin
- Mengelola transaksi peminjaman
- Memproses pengembalian buku
- Mengelola denda keterlambatan
- Melihat histori transaksi
- Memantau stok buku

---

## ✨ Fitur Utama

### 🔐 Autentikasi

- Login Admin
- Login Siswa
- Registrasi Siswa
- Logout
- Session Management
- Role Based Access Control

### 📚 Manajemen Buku

#### Admin

- Tambah Buku
- Edit Buku
- Hapus Buku
- Kelola Genre
- Kelola Stok

#### Siswa

- Lihat Daftar Buku
- Cari Buku
- Lihat Detail Buku
- Cek Ketersediaan Buku

### 📝 Manajemen Peminjaman

- Peminjaman Buku
- Pencatatan Tanggal Pinjam
- Batas Waktu Pengembalian
- Pengurangan Stok Otomatis
- Monitoring Status Peminjaman

### 🔄 Manajemen Pengembalian

- Pengembalian Buku
- Verifikasi Pengembalian
- Pencatatan Tanggal Kembali
- Penambahan Stok Otomatis

### 💰 Manajemen Denda

- Perhitungan Denda Keterlambatan
- Monitoring Data Denda
- Pengelolaan Status Denda

### 📋 Histori Transaksi

- Riwayat Peminjaman
- Riwayat Pengembalian
- Detail Denda
- Status Transaksi

---

## 🔄 Alur Sistem

### Alur Siswa

1. Login ke aplikasi.
2. Masuk ke dashboard siswa.
3. Melihat daftar buku.
4. Mencari buku yang ingin dipinjam.
5. Melihat detail buku.
6. Melakukan peminjaman.
7. Sistem mencatat transaksi.
8. Sistem mengurangi stok buku.
9. Siswa melihat status peminjaman.
10. Siswa mengembalikan buku.
11. Sistem memeriksa keterlambatan.
12. Sistem mencatat histori transaksi.

### Alur Admin

1. Login ke dashboard admin.
2. Mengelola data buku.
3. Mengelola data siswa.
4. Mengelola data admin.
5. Mengelola transaksi peminjaman.
6. Memproses pengembalian buku.
7. Mengelola denda keterlambatan.
8. Memantau stok buku.
9. Melihat histori transaksi.

---

## ⚙️ Aturan Sistem

1. Siswa wajib login sebelum meminjam buku.
2. Buku hanya dapat dipinjam jika stok tersedia.
3. Setiap transaksi memiliki tanggal pinjam dan batas pengembalian.
4. Stok buku berkurang saat buku dipinjam.
5. Stok buku bertambah setelah buku dikembalikan.
6. Keterlambatan dikenakan denda.
7. Data transaksi disimpan sebagai histori.
8. Admin memiliki hak akses penuh terhadap pengelolaan sistem.
9. Siswa hanya dapat mengakses data peminjamannya sendiri.

---

# 🚀 Instalasi

## 1. Clone Repository

```bash
git clone https://github.com/Rezkaparsha/Aplikasi_Peminjaman_Buku.git
```

## 2. Masuk ke Folder Project

```bash
cd Aplikasi_Peminjaman_Buku
```

## 3. Buat Database

Buka phpMyAdmin lalu buat database:

```sql
CREATE DATABASE db_perpustakaan;
```

## 4. Import Database

1. Buka phpMyAdmin.
2. Pilih database `db_perpustakaan`.
3. Klik menu **Import**.
4. Pilih file database `.sql`.
5. Klik tombol **Import**.

## 5. Konfigurasi Database

Sesuaikan file koneksi database:

```php
$host = "localhost";
$user = "root";
$password = "";
$database = "db_perpustakaan";

$conn = mysqli_connect(
    $host,
    $user,
    $password,
    $database
);
```

## 6. Jalankan Aplikasi

```text
http://localhost/Aplikasi_Peminjaman_Buku
```

---

# 🔑 Akun Demo

## Admin

| Keterangan | Data |
|------------|---------|
| Username / NIS / NIP | 1234567890 |
| Password | 123 |
| Role | Admin |

### Hak Akses Admin

- Kelola Buku
- Kelola Siswa
- Kelola Admin
- Kelola Transaksi
- Kelola Denda
- Histori Transaksi
- Monitoring Stok

---

## Siswa

| Keterangan | Data |
|------------|---------|
| Username / NIS / NIP | 0123456789 |
| Password | 123 |
| Role | Siswa |

### Hak Akses Siswa

- Melihat Buku
- Mencari Buku
- Melakukan Peminjaman
- Melakukan Pengembalian
- Melihat Histori
- Melihat Denda

---

# 🏗️ Struktur Modul

```text
Aplikasi Peminjaman Buku
│
├── Login
├── Dashboard
│
├── Master Data
│   ├── Buku
│   ├── Genre
│   ├── Siswa
│   └── Admin
│
├── Transaksi
│   ├── Peminjaman
│   ├── Pengembalian
│   └── Denda
│
└── Histori
```

---

# 🛠️ Teknologi yang Digunakan

- PHP Native
- MySQL
- Bootstrap 5
- HTML5
- CSS3
- JavaScript
- phpMyAdmin
- Laragon
- XAMPP

---

# 📷 Screenshot

Tambahkan screenshot aplikasi agar repository terlihat lebih profesional.

```text
assets/images/login.png
assets/images/dashboard-admin.png
assets/images/dashboard-siswa.png
assets/images/daftar-buku.png
```

---

# 📊 Diagram Sistem

Dokumentasi yang dapat ditambahkan:

- Use Case Diagram
- Activity Diagram
- Class Diagram
- Entity Relationship Diagram (ERD)

---

# 👨‍💻 Pengembang

**Rezka Parsha**

Proyek ini dibuat sebagai media pembelajaran pengembangan aplikasi perpustakaan berbasis web untuk membantu proses peminjaman dan pengembalian buku secara digital, terstruktur, dan mudah digunakan.
