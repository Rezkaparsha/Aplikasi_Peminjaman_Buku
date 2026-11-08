# 📚 Aplikasi Peminjaman Buku

Aplikasi Peminjaman Buku adalah sistem informasi perpustakaan berbasis web yang dirancang untuk membantu proses peminjaman dan pengembalian buku secara digital. Sistem ini memudahkan siswa dalam mencari dan mengajukan peminjaman buku, serta membantu admin dalam mengelola data buku, transaksi, pengguna, dan denda secara terorganisir.

---

## 🎯 Tujuan Program

Aplikasi ini dibuat untuk:

- Mempermudah siswa dalam mencari dan mengajukan peminjaman buku.
- Membantu admin mengelola perpustakaan secara digital.
- Mencatat seluruh aktivitas peminjaman dan pengembalian buku.
- Mengelola denda keterlambatan secara terstruktur.
- Memantau stok buku secara lebih efektif.

---

## 📌 Ruang Lingkup

Aplikasi mencakup beberapa modul utama:

- 📚 Daftar Buku
- 📖 Detail Buku
- 📝 Pengajuan dan Persetujuan Peminjaman
- 🔄 Pengembalian Buku (diproses admin)
- 💳 Transaksi
- 💰 Denda
- 📋 Histori Transaksi

---

## 👥 Pengguna Aplikasi

### 👨‍🎓 Siswa

Siswa dapat:

- Registrasi akun dan login ke dalam aplikasi
- Melihat daftar buku
- Mencari buku
- Mengajukan peminjaman buku (satu pengajuan dapat berisi beberapa buku)
- Melihat status dan detail peminjaman miliknya sendiri
- Melihat alasan jika pengajuan ditolak

### 👨‍💼 Admin

Admin dapat:

- Login ke dalam aplikasi
- Mengelola data buku, kategori, penerbit, dan penulis
- Mengelola data pengguna (admin dan siswa)
- Menyetujui pengajuan peminjaman dan menentukan batas tanggal pengembalian
- Menolak pengajuan peminjaman beserta alasannya
- Memproses pengembalian buku dan mencatat kondisi buku
- Melihat histori transaksi
- Memantau stok buku

---

## ✨ Fitur Utama

### 🔐 Autentikasi

- Login (NIS/NIP atau username)
- Registrasi Siswa
- Logout
- Session Management
- Role Based Access Control (RBAC): admin dan siswa memiliki halaman berbeda
- Password disimpan dalam bentuk hash bcrypt

### 📚 Manajemen Buku (Admin)

- Tambah, Ubah, Hapus Buku
- Kelola Kategori, Penerbit, dan Penulis
- Kelola Stok Buku

### 📝 Manajemen Peminjaman

- Siswa mengajukan peminjaman (status **Diajukan**); stok baru dicek, belum berkurang
- Admin menyetujui pengajuan: stok berkurang otomatis, batas pengembalian ditentukan admin, status menjadi **Dipinjam**
- Admin dapat menolak pengajuan dengan alasan, status menjadi **Ditolak**
- Proses persetujuan memakai transaksi database sehingga stok tetap konsisten

### 🔄 Manajemen Pengembalian

- Admin memproses pengembalian buku
- Kondisi buku dicatat per buku: **Normal**, **Rusak**, atau **Hilang**
- Stok bertambah otomatis (buku berkondisi Hilang tidak menambah stok)
- Status peminjaman menjadi **Dikembalikan**

### 💰 Manajemen Denda

- Denda keterlambatan: **Rp 1.000 per hari** sejak melewati batas pengembalian
- Denda tambahan untuk buku Rusak/Hilang (nominal diisi admin)
- Denda dicatat di tabel `denda` dan di histori transaksi
- Catatan: halaman untuk membayar/mengelola denda belum tersedia di antarmuka; kerangka `c_denda.php` dan `m_denda.php` ada tetapi belum tersambung ke rute

### 📋 Histori Transaksi

- Arsip transaksi yang sudah dikembalikan
- Menyimpan nama siswa, judul buku, tanggal pinjam, batas kembali, tanggal dikembalikan, dan denda

---

## 🔄 Alur Sistem

### Alur Siswa

1. Login ke aplikasi.
2. Masuk ke dashboard siswa.
3. Melihat daftar buku dan memilih buku.
4. Mengajukan peminjaman (status **Diajukan**).
5. Menunggu keputusan admin; status dapat dilihat di halaman Peminjaman.
6. Jika disetujui, status **Dipinjam** dan batas pengembalian tampil di detail.
7. Siswa menyerahkan buku ke admin pada saat pengembalian.

### Alur Admin

1. Login ke dashboard admin.
2. Mengelola data master (buku, kategori, penerbit, penulis, pengguna).
3. Membuka daftar peminjaman dan memeriksa pengajuan.
4. Menyetujui (isi batas pengembalian) atau menolak (isi alasan).
5. Saat buku kembali: memilih kondisi tiap buku dan mengisi denda tambahan bila perlu.
6. Sistem menghitung denda keterlambatan, menyesuaikan stok, dan menyimpan histori.
7. Melihat histori transaksi.

### Diagram Status Peminjaman

```text
Diajukan ──(admin setuju)──> Dipinjam ──(admin proses kembali)──> Dikembalikan
    └────(admin tolak)─────> Ditolak
```

---

## ⚙️ Aturan Sistem

1. Siswa wajib login sebelum mengajukan peminjaman.
2. Buku hanya dapat diajukan jika stok masih mencukupi.
3. Setiap transaksi memiliki tanggal pinjam; batas pengembalian ditentukan admin saat menyetujui.
4. Stok buku berkurang ketika admin MENYETUJUI pengajuan (bukan saat diajukan).
5. Stok buku bertambah ketika admin memproses pengembalian (kecuali buku Hilang).
6. Keterlambatan pengembalian dikenakan denda Rp 1.000 per hari.
7. Data transaksi yang selesai disimpan sebagai histori.
8. Admin memiliki hak akses penuh terhadap pengelolaan sistem.
9. Siswa hanya dapat mengakses data peminjamannya sendiri.

---

# 🚀 Instalasi

## Prasyarat

- Web server Apache dengan **mod_rewrite aktif** dan `AllowOverride All` (sudah default di Laragon), karena routing URL bersih memakai file `.htaccess`
- PHP dan MySQL (contoh: Laragon)
- Folder proyek bernama `Aplikasi_Peminjaman_Buku` (nama folder dipakai pada `RewriteBase` di `.htaccess`)

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
4. Pilih file `DATABASE/db_perpustakaan.sql`.
5. Klik **Import**.

## 5. Konfigurasi Database

Sesuaikan pengaturan koneksi pada file `MODEL/m_koneksi.php` (class `Koneksi`):

```php
private $host = "localhost";
private $username = "root";
private $pass = "";
private $dbname = "db_perpustakaan";
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

## Siswa

| Keterangan | Data |
|------------|------------|
| Username / NIS / NIP | 0060254015 |
| Password | 123 |
| Role | Siswa |

---

# 🏗️ Struktur Folder (MVC)

```text
Aplikasi_Peminjaman_Buku
│
├── index.php            # pintu masuk, mengarahkan sesuai login & role
├── .htaccess            # routing URL bersih + proteksi folder
├── ASSETS/              # cover buku dan logo
├── CONTROLLER/          # c_login, c_peminjaman, c_buku, c_kategori, c_penerbit,
│                        # c_penulis, c_user, c_denda, c_histori
├── MODEL/               # m_koneksi, m_users, m_buku, m_peminjaman, dll
├── VIEW/
│   ├── AUTH/            # login, register
│   ├── ADMIN/           # halaman admin
│   ├── SISWA/           # halaman siswa
│   └── TEMPLATE/        # sidebar admin dan siswa
└── DATABASE/            # db_perpustakaan.sql
```

## Struktur Modul

```text
Aplikasi Peminjaman Buku
│
├── Login / Registrasi
├── Dashboard
│
├── Master Data
│   ├── Buku
│   ├── Kategori
│   ├── Penerbit
│   ├── Penulis
│   └── Pengguna
│
├── Transaksi
│   ├── Pengajuan Peminjaman
│   ├── Persetujuan / Penolakan
│   ├── Pengembalian
│   └── Denda
│
└── Histori
```

---

# 🛠️ Teknologi yang Digunakan

- PHP OOP
- MVC (Model View Controller)
- MySQL (mysqli + prepared statement)
- Bootstrap 5 dan Tailwind CSS (via CDN)
- Font Awesome dan SweetAlert2
- HTML5, CSS3, JavaScript
- phpMyAdmin
- Laragon

---

# 👨‍💻 Pengembang

**Rezka Parsha**

Proyek ini dibuat sebagai media pembelajaran pengembangan aplikasi perpustakaan berbasis web menggunakan konsep Object-Oriented Programming (OOP) dan arsitektur MVC (Model View Controller) untuk menghasilkan kode yang lebih terstruktur, mudah dikembangkan, dan mudah dipelihara.
