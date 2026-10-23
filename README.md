# 📚 Aplikasi Peminjaman Buku

Aplikasi Peminjaman Buku adalah sistem informasi perpustakaan berbasis web yang dirancang untuk membantu proses peminjaman dan pengembalian buku secara digital. Sistem ini memudahkan siswa dalam mencari dan meminjam buku serta membantu admin dalam mengelola data buku, transaksi, pengguna, dan denda secara terorganisir.

---

## 🎯 Tujuan Program

Aplikasi ini dibuat untuk:

- Mempermudah siswa dalam mencari dan meminjam buku.
- Membantu admin mengelola perpustakaan secara digital.
- Mencatat seluruh aktivitas peminjaman dan pengembalian buku.
- Mengelola denda keterlambatan secara terstruktur.
- Memantau stok buku secara lebih efektif.

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
- Role Based Access Control (RBAC)

### 📚 Manajemen Buku

#### Admin

- Tambah Buku
- Edit Buku
- Hapus Buku
- Kelola Genre
- Kelola Stok Buku

#### Siswa

- Melihat Daftar Buku
- Mencari Buku
- Melihat Detail Buku
- Melihat Ketersediaan Buku

### 📝 Manajemen Peminjaman

- Peminjaman Buku
- Pencatatan Tanggal Peminjaman
- Penentuan Batas Waktu Pengembalian
- Pengurangan Stok Otomatis
- Monitoring Status Peminjaman

### 🔄 Manajemen Pengembalian

- Pengembalian Buku
- Verifikasi Pengembalian
- Pencatatan Tanggal Pengembalian
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

### 👨‍🎓 Manajemen Siswa

- Tambah Data Siswa
- Ubah Data Siswa
- Hapus Data Siswa
- Lihat Data Siswa

### 👨‍💼 Manajemen Admin

- Tambah Admin
- Ubah Admin
- Hapus Admin
- Lihat Daftar Admin

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

1. Siswa wajib login sebelum melakukan peminjaman.
2. Buku hanya dapat dipinjam jika stok masih tersedia.
3. Setiap transaksi memiliki tanggal peminjaman dan batas pengembalian.
4. Stok buku berkurang saat peminjaman berhasil.
5. Stok buku bertambah setelah buku dikembalikan.
6. Keterlambatan pengembalian dikenakan denda.
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

Buka phpMyAdmin kemudian buat database:

```sql
CREATE DATABASE db_perpustakaan;
```

## 4. Import Database

1. Buka phpMyAdmin.
2. Pilih database `db_perpustakaan`.
3. Klik menu **Import**.
4. Pilih file database `.sql`.
5. Klik **Import**.

## 5. Konfigurasi Database

Sesuaikan pengaturan koneksi database pada file konfigurasi aplikasi.

Contoh:

```php
$host = "localhost";
$user = "root";
$password = "";
$database = "db_perpustakaan";
```

## 6. Jalankan Aplikasi

```text
http://localhost/Aplikasi_Peminjaman_Buku
```

---

# 🔑 Akun Demo

## Admin

| Keterangan | Data |
|------------|------------|
| Username / NIS / NIP | 1234567890 |
| Password | 123 |
| Role | Admin |

### Hak Akses

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
|------------|------------|
| Username / NIS / NIP | 0123456789 |
| Password | 123 |
| Role | Siswa |

### Hak Akses

- Melihat Buku
- Mencari Buku
- Meminjam Buku
- Mengembalikan Buku
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

- PHP OOP
- MVC (Model View Controller)
- MySQL
- Bootstrap 5
- HTML5
- CSS3
- JavaScript
- phpMyAdmin
- Laragon

---

# 👨‍💻 Pengembang

**Rezka Parsha**

Proyek ini dibuat sebagai media pembelajaran pengembangan aplikasi perpustakaan berbasis web menggunakan konsep Object-Oriented Programming (OOP) dan arsitektur MVC (Model View Controller) untuk menghasilkan kode yang lebih terstruktur, mudah dikembangkan, dan mudah dipelihara.