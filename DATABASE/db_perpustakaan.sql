-- =====================================================================
-- FILE   : DATABASE/db_perpustakaan.sql  (ekspor phpMyAdmin dari MySQL)
-- FUNGSI : Membuat 10 tabel database db_perpustakaan beserta data contoh.
-- CARA   : buat database db_perpustakaan di phpMyAdmin -> pilih -> Import file ini.
-- URUTAN ISI FILE:
--   1) CREATE TABLE   : bentuk tiap tabel dan kolomnya
--   2) INSERT INTO    : data contoh
--   3) ALTER TABLE    : primary key, index, UNIQUE
--   4) ALTER TABLE    : AUTO_INCREMENT (penomoran otomatis)
--   5) ALTER TABLE    : FOREIGN KEY (relasi antar tabel)
-- RELASI :
--   users 1-N peminjaman 1-N detail_peminjaman N-1 buku
--   detail_peminjaman 1-N denda ; buku N-N penulis (lewat buku_penulis)
--   buku N-1 kategori ; buku N-1 penerbit
-- Baris berawalan -- adalah komentar SQL dan tidak dijalankan.
-- =====================================================================
-- phpMyAdmin SQL Dump
-- version 6.0.0-dev+20260130.4d5432a85e
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Oct 23, 2026 at 08:51 AM
-- Server version: 8.4.3
-- PHP Version: 8.4.17

-- NO_AUTO_VALUE_ON_ZERO: nilai 0 pada kolom AUTO_INCREMENT tidak otomatis diganti nomor baru.
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
-- Mulai transaksi: seluruh impor dianggap satu paket, disimpan permanen oleh COMMIT di akhir file.
START TRANSACTION;
-- Zona waktu UTC (+00:00) selama proses impor.
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `db_perpustakaan`
--

-- --------------------------------------------------------

--
-- Table structure for table `buku`
--

-- TABEL `buku`: Data buku perpustakaan.
-- Kolom:
--   - id_buku: Kunci utama (primary key), nomor urut otomatis.
--   - id_penerbit: Kunci tamu ke penerbit.id_penerbit (siapa penerbitnya).
--   - id_kategori: Kunci tamu ke kategori.id_kategori (genre buku).
--   - judul_buku: Judul buku (maks 70 karakter).
--   - tahun_terbit: Tahun terbit (tipe YEAR).
--   - harga_buku: Harga buku (angka), default 0; disalin ke histori transaksi.
--   - stok: Jumlah buku tersedia; berkurang saat peminjaman disetujui, bertambah saat dikembalikan.
--   - cover: Nama file gambar cover (disimpan di folder ASSETS/COVER).
CREATE TABLE `buku` (
  `id_buku` int NOT NULL,
  `id_penerbit` int NOT NULL,
  `id_kategori` int NOT NULL,
  `judul_buku` varchar(70) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `tahun_terbit` year NOT NULL,
  `harga_buku` int NOT NULL DEFAULT '0',
  `stok` int NOT NULL DEFAULT '0',
  `cover` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `buku`
--

-- Data contoh untuk tabel `buku` (hasil ekspor; nilai disusun sesuai urutan kolom di atas).
INSERT INTO `buku` (`id_buku`, `id_penerbit`, `id_kategori`, `judul_buku`, `tahun_terbit`, `harga_buku`, `stok`, `cover`) VALUES
(1, 1, 1, 'Pemrograman PHP Dasar', '2022', 85000, 5, 'cover_6ab46e7eee27c9.39747311.jpg'),
(2, 2, 3, 'Pemrograman Web dengan PHP', '2021', 95000, 4, 'cover_6ab46ee621fc84.51771778.jpg'),
(3, 1, 2, 'Belajar MySQL untuk Pemula', '2023', 90000, 6, 'cover_6ab46aa5e7e111.11600786.jpg'),
(4, 3, 1, 'Pemrograman Berorientasi Objek', '2022', 100000, 9, 'cover_6ab46e38bb00a8.04606121.webp'),
(5, 2, 1, 'Dasar-Dasar Laravel', '2024', 110000, 5, 'cover_6ab46da32dee95.91928167.jpg'),
(6, 3, 3, 'Membangun Website Modern', '2023', 105000, 5, 'cover_6ab46e03199763.21977464.png'),
(7, 1, 4, 'Algoritma dan Pemrograman', '2021', 80000, 8, 'cover_6ab469e1bba4f8.82572613.jpg'),
(8, 4, 2, 'Basis Data untuk Pemula', '2022', 88000, 5, 'cover_6ab46a36d8aa41.35136856.jpg'),
(9, 4, 3, 'HTML CSS dan JavaScript', '2023', 92000, 6, 'cover_6ab46c086d8f61.12936511.jpg'),
(10, 5, 9, 'Belajar Pemrograman Mobile', '2024', 115000, 20, 'cover_6ab46b299d8414.39408010.jpg'),
(21, 12, 12, 'THE PSYCHOLOGY OF MONEY', '2014', 20000, 9, 'cover_6aa0ac6d6c5ca7.87431288.jpg'),
(22, 13, 13, 'Dia Adalah Dilanku Tahun 1990 (Dilan 1990)', '2014', 2000000, 18, 'cover_6aa0ad6cb69dd3.90579731.jpg'),
(23, 13, 13, 'Milea: Suara dari Dilan', '2016', 160000, 11, 'cover_6aa33a0d1a6777.01644088.jpg'),
(24, 13, 13, 'Ancika: Dia yang Bersamaku Tahun 1990', '2021', 159952, 20, 'cover_6aa689535832b0.47884805.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `buku_penulis`
--

-- TABEL `buku_penulis`: Tabel penghubung buku dan penulis (relasi banyak-ke-banyak: satu buku boleh banyak penulis).
-- Kolom:
--   - id_buku: Bagian dari kunci utama gabungan; mengacu ke buku.
--   - id_penulis: Bagian dari kunci utama gabungan; mengacu ke penulis.
CREATE TABLE `buku_penulis` (
  `id_buku` int NOT NULL,
  `id_penulis` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `buku_penulis`
--

-- Data contoh untuk tabel `buku_penulis` (hasil ekspor; nilai disusun sesuai urutan kolom di atas).
INSERT INTO `buku_penulis` (`id_buku`, `id_penulis`) VALUES
(3, 3),
(6, 6),
(8, 8),
(9, 9),
(21, 12),
(22, 13),
(23, 13);

-- --------------------------------------------------------

--
-- Table structure for table `denda`
--

-- TABEL `denda`: Catatan denda (keterlambatan, kerusakan, atau kehilangan) per baris detail peminjaman.
-- Kolom:
--   - id_denda: Kunci utama, nomor urut otomatis.
--   - id_detail: Kunci tamu ke detail_peminjaman.id_detail (denda milik buku yang mana).
--   - jenis_denda: Keterangan jenis denda (teks).
--   - jumlah_hari_terlambat: Jumlah hari terlambat (0 jika bukan denda keterlambatan).
--   - jumlah_denda: Nominal denda dalam rupiah.
--   - tanggal_denda: Tanggal denda dicatat.
--   - status_pembayaran: ENUM: 'Belum Dibayar' (default) atau 'Sudah Dibayar'.
--   - keterangan: Catatan tambahan (opsional).
CREATE TABLE `denda` (
  `id_denda` int NOT NULL,
  `id_detail` int NOT NULL,
  `jenis_denda` varchar(255) DEFAULT NULL,
  `jumlah_hari_terlambat` int NOT NULL DEFAULT '0',
  `jumlah_denda` int NOT NULL DEFAULT '0',
  `tanggal_denda` date DEFAULT NULL,
  `status_pembayaran` enum('Belum Dibayar','Sudah Dibayar') NOT NULL DEFAULT 'Belum Dibayar',
  `keterangan` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `denda`
--

-- Data contoh untuk tabel `denda` (hasil ekspor; nilai disusun sesuai urutan kolom di atas).
INSERT INTO `denda` (`id_denda`, `id_detail`, `jenis_denda`, `jumlah_hari_terlambat`, `jumlah_denda`, `tanggal_denda`, `status_pembayaran`, `keterangan`) VALUES
(1, 3, 'Terlambat', 2, 10000, '2026-08-14', 'Sudah Dibayar', 'Pengembalian terlambat 2 hari'),
(2, 4, 'Terlambat', 1, 5000, '2026-08-16', 'Sudah Dibayar', 'Pengembalian terlambat 1 hari'),
(3, 5, 'Rusak', 0, 55000, '2026-08-17', 'Belum Dibayar', 'Buku mengalami kerusakan'),
(4, 6, 'Terlambat 22 Hari', 0, 22000, NULL, 'Belum Dibayar', NULL),
(5, 19, 'Terlambat 4 Hari', 0, 4000, NULL, 'Belum Dibayar', NULL),
(6, 20, 'Terlambat 4 Hari', 0, 4000, NULL, 'Belum Dibayar', NULL),
(7, 21, 'Terlambat 4 Hari', 0, 4000, NULL, 'Belum Dibayar', NULL),
(8, 22, 'Terlambat 4 Hari', 0, 4000, NULL, 'Belum Dibayar', NULL),
(9, 18, 'Terlambat 4 Hari', 0, 4000, NULL, 'Belum Dibayar', NULL),
(10, 14, 'Terlambat 3 Hari + Buku Rusak', 0, 13000, NULL, 'Belum Dibayar', NULL),
(11, 7, 'Terlambat 23 Hari', 0, 23000, NULL, 'Belum Dibayar', NULL),
(12, 23, 'Terlambat 2 Hari', 0, 2000, NULL, 'Belum Dibayar', NULL),
(13, 24, 'Terlambat 2 Hari', 0, 2000, NULL, 'Belum Dibayar', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `detail_peminjaman`
--

-- TABEL `detail_peminjaman`: Rincian buku dalam satu peminjaman (satu baris per jenis buku yang dipinjam).
-- Kolom:
--   - id_detail: Kunci utama, nomor urut otomatis.
--   - id_peminjaman: Kunci tamu ke peminjaman.id_peminjaman (induk transaksinya).
--   - id_buku: Kunci tamu ke buku.id_buku (buku yang dipinjam).
--   - jumlah_buku: Jumlah eksemplar yang dipinjam.
--   - tanggal_pengembalian: Batas tanggal kembali (diisi admin saat menyetujui; NULL sebelum disetujui).
--   - tanggal_dikembalikan: Tanggal buku benar-benar dikembalikan (NULL sebelum dikembalikan).
--   - status: ENUM: Diajukan (default), Dipinjam, Dikembalikan.
CREATE TABLE `detail_peminjaman` (
  `id_detail` int NOT NULL,
  `id_peminjaman` int NOT NULL,
  `id_buku` int NOT NULL,
  `jumlah_buku` int NOT NULL,
  `tanggal_pengembalian` date DEFAULT NULL,
  `tanggal_dikembalikan` date DEFAULT NULL,
  `status` enum('Diajukan','Dipinjam','Dikembalikan') NOT NULL DEFAULT 'Diajukan'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `detail_peminjaman`
--

-- Data contoh untuk tabel `detail_peminjaman` (hasil ekspor; nilai disusun sesuai urutan kolom di atas).
INSERT INTO `detail_peminjaman` (`id_detail`, `id_peminjaman`, `id_buku`, `jumlah_buku`, `tanggal_pengembalian`, `tanggal_dikembalikan`, `status`) VALUES
(1, 1, 1, 1, '2026-08-08', '2026-08-07', 'Dikembalikan'),
(2, 2, 2, 1, '2026-08-10', '2026-08-10', 'Dikembalikan'),
(3, 3, 3, 1, '2026-08-12', '2026-08-14', 'Dikembalikan'),
(4, 4, 4, 1, '2026-08-17', '2026-08-16', 'Dikembalikan'),
(5, 4, 5, 1, '2026-08-17', '2026-08-17', 'Dikembalikan'),
(6, 5, 6, 1, '2026-08-22', '2026-09-13', 'Dikembalikan'),
(7, 6, 7, 1, '2026-08-25', '2026-09-17', 'Dikembalikan'),
(14, 14, 23, 1, '2026-09-14', '2026-09-17', 'Dikembalikan'),
(15, 15, 10, 1, '2026-09-13', '2026-09-13', 'Dikembalikan'),
(16, 16, 23, 1, '2026-09-13', '2026-09-13', 'Dikembalikan'),
(17, 16, 22, 1, '2026-09-13', '2026-09-13', 'Dikembalikan'),
(18, 17, 21, 3, '2026-09-13', '2026-09-17', 'Dikembalikan'),
(19, 18, 24, 1, '2026-09-13', '2026-09-17', 'Dikembalikan'),
(20, 18, 23, 1, '2026-09-13', '2026-09-17', 'Dikembalikan'),
(21, 18, 22, 1, '2026-09-13', '2026-09-17', 'Dikembalikan'),
(22, 18, 21, 1, '2026-09-13', '2026-09-17', 'Dikembalikan'),
(23, 19, 24, 1, '2026-09-18', '2026-09-20', 'Dikembalikan'),
(24, 19, 23, 1, '2026-09-18', '2026-09-20', 'Dikembalikan'),
(25, 20, 4, 1, NULL, NULL, 'Diajukan'),
(26, 20, 1, 1, NULL, NULL, 'Diajukan'),
(27, 21, 9, 1, '2026-09-21', '2026-09-20', 'Dikembalikan'),
(28, 22, 24, 1, '2026-09-23', '2026-09-22', 'Dikembalikan'),
(29, 23, 23, 1, '2026-09-22', NULL, 'Dipinjam'),
(30, 23, 22, 1, '2026-09-22', NULL, 'Dipinjam'),
(31, 23, 21, 1, '2026-09-22', NULL, 'Dipinjam'),
(32, 24, 9, 1, NULL, NULL, 'Diajukan'),
(33, 25, 6, 1, NULL, NULL, 'Diajukan'),
(34, 26, 7, 1, NULL, NULL, 'Diajukan');

-- --------------------------------------------------------

--
-- Table structure for table `histori_transaksi`
--

-- TABEL `histori_transaksi`: Arsip transaksi yang sudah selesai. Menyimpan SALINAN data (nama siswa, judul, harga) agar histori tetap utuh walau data aslinya berubah.
-- Kolom:
--   - id_histori: Kunci utama, nomor urut otomatis.
--   - id_peminjaman: Kunci tamu ke peminjaman.
--   - id_detail: Kunci tamu ke detail_peminjaman.
--   - nis_nip: NIS/NIP siswa. CATATAN: bertipe int, sedangkan users.nis_nip bertipe varchar(10).
--   - nama_siswa: Salinan nama siswa saat transaksi.
--   - id_buku: Id buku (salinan).
--   - judul_buku: Salinan judul buku.
--   - harga_buku: Salinan harga buku.
--   - tanggal_pinjam: Tanggal peminjaman.
--   - tanggal_pengembalian: Batas tanggal kembali.
--   - tanggal_dikembalikan: Tanggal dikembalikan sebenarnya.
--   - jenis_denda: Keterangan jenis denda (boleh kosong).
--   - denda: Total denda untuk buku ini (rupiah), default 0.
--   - tanggal_selesai: Tanggal transaksi dinyatakan selesai.
CREATE TABLE `histori_transaksi` (
  `id_histori` int NOT NULL,
  `id_peminjaman` int NOT NULL,
  `id_detail` int NOT NULL,
  `nis_nip` int NOT NULL,
  `nama_siswa` varchar(50) NOT NULL,
  `id_buku` int NOT NULL,
  `judul_buku` varchar(70) NOT NULL,
  `harga_buku` int NOT NULL,
  `tanggal_pinjam` date NOT NULL,
  `tanggal_pengembalian` date NOT NULL,
  `tanggal_dikembalikan` date NOT NULL,
  `jenis_denda` varchar(255) DEFAULT NULL,
  `denda` int NOT NULL DEFAULT '0',
  `tanggal_selesai` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `histori_transaksi`
--

-- Data contoh untuk tabel `histori_transaksi` (hasil ekspor; nilai disusun sesuai urutan kolom di atas).
INSERT INTO `histori_transaksi` (`id_histori`, `id_peminjaman`, `id_detail`, `nis_nip`, `nama_siswa`, `id_buku`, `judul_buku`, `harga_buku`, `tanggal_pinjam`, `tanggal_pengembalian`, `tanggal_dikembalikan`, `jenis_denda`, `denda`, `tanggal_selesai`) VALUES
(1, 15, 15, 7532159, 'galih rusli', 10, 'Belajar Pemrograman Mobile', 115000, '2026-09-13', '2026-09-13', '2026-09-13', 'Tidak Ada', 0, '2026-09-13'),
(2, 16, 16, 454545, 'M Eka Santika', 23, 'Milea: Suara dari Dilan', 123132131, '2026-09-13', '2026-09-13', '2026-09-13', 'Tidak Ada', 0, '2026-09-13'),
(3, 16, 17, 454545, 'M Eka Santika', 22, 'Dia Adalah Dilanku Tahun 1990 (Dilan 1990)', 2000000, '2026-09-13', '2026-09-13', '2026-09-13', 'Tidak Ada', 0, '2026-09-13'),
(5, 18, 19, 20260010, 'Joko Setiawan', 24, 'Ancika: Dia yang Bersamaku Tahun 1995', 159999, '2026-09-13', '2026-09-13', '2026-09-17', 'Terlambat 4 Hari', 4000, '2026-09-17'),
(6, 18, 20, 20260010, 'Joko Setiawan', 23, 'Milea: Suara dari Dilan', 160000, '2026-09-13', '2026-09-13', '2026-09-17', 'Terlambat 4 Hari', 4000, '2026-09-17'),
(7, 18, 21, 20260010, 'Joko Setiawan', 22, 'Dia Adalah Dilanku Tahun 1990 (Dilan 1990)', 2000000, '2026-09-13', '2026-09-13', '2026-09-17', 'Terlambat 4 Hari', 4000, '2026-09-17'),
(8, 18, 22, 20260010, 'Joko Setiawan', 21, 'THE PSYCHOLOGY OF MONEY', 20000, '2026-09-13', '2026-09-13', '2026-09-17', 'Terlambat 4 Hari', 4000, '2026-09-17'),
(10, 14, 14, 7532159, 'galih ruslii', 23, 'Milea: Suara dari Dilan', 160000, '2026-09-13', '2026-09-14', '2026-09-17', 'Terlambat 3 Hari + Buku Rusak', 13000, '2026-09-17'),
(11, 6, 7, 20260006, 'Fajar Maulana', 7, 'Algoritma dan Pemrograman', 80000, '2026-08-18', '2026-08-25', '2026-09-17', 'Terlambat 23 Hari', 23000, '2026-09-17'),
(12, 19, 23, 6042007, 'REZKA AJA', 24, 'Ancika: Dia yang Bersamaku Tahun 1990', 159952, '2026-09-17', '2026-09-18', '2026-09-20', 'Terlambat 2 Hari', 2000, '2026-09-20'),
(13, 19, 24, 6042007, 'REZKA AJA', 23, 'Milea: Suara dari Dilan', 160000, '2026-09-17', '2026-09-18', '2026-09-20', 'Terlambat 2 Hari', 2000, '2026-09-20'),
(14, 21, 27, 454545, 'M Eka Santika', 9, 'HTML CSS dan JavaScript', 92000, '2026-09-20', '2026-09-21', '2026-09-20', 'Tidak Ada', 0, '2026-09-20'),
(15, 22, 28, 789, 'Alfian Rizky Nugraha', 24, 'Ancika: Dia yang Bersamaku Tahun 1990', 159952, '2026-09-22', '2026-09-23', '2026-09-22', 'Tidak Ada', 0, '2026-09-22');

-- --------------------------------------------------------

--
-- Table structure for table `kategori`
--

-- TABEL `kategori`: Kategori/genre buku.
-- Kolom:
--   - id_kategori: Kunci utama, nomor urut otomatis.
--   - nama_kategori: Nama kategori (UNIQUE: tidak boleh kembar).
--   - deskripsi: Penjelasan singkat (opsional).
--   - status: ENUM: 'aktif' (default) atau 'nonaktif'.
CREATE TABLE `kategori` (
  `id_kategori` int NOT NULL,
  `nama_kategori` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `deskripsi` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `status` enum('aktif','nonaktif') NOT NULL DEFAULT 'aktif'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `kategori`
--

-- Data contoh untuk tabel `kategori` (hasil ekspor; nilai disusun sesuai urutan kolom di atas).
INSERT INTO `kategori` (`id_kategori`, `nama_kategori`, `deskripsi`, `status`) VALUES
(1, 'Pemrograman', 'Buku mengenai pemrograman komputer', 'aktif'),
(2, 'Basis Data', 'Buku mengenai database dan pengelolaan data', 'aktif'),
(3, 'Web Development', 'Buku mengenai pengembangan website', 'aktif'),
(4, 'Algoritma', 'Buku mengenai algoritma dan logika pemrograman', 'aktif'),
(5, 'Jaringan', 'Buku mengenai jaringan komputer', 'aktif'),
(6, 'Sistem Informasi', 'Buku mengenai sistem informasi', 'aktif'),
(7, 'Desain', 'Buku mengenai desain dan UI/UX', 'aktif'),
(8, 'Komputer Dasar', 'Buku mengenai dasar-dasar komputer', 'aktif'),
(9, 'Mobile Development', 'Buku mengenai pengembangan aplikasi mobile', 'aktif'),
(10, 'Teknologi', 'Buku mengenai teknologi informasi', 'aktif'),
(12, 'KEUANGAN', 'UNTUK BUKU MENGENAI KEUANGAN', 'aktif'),
(13, 'NOVEL', 'MENGENAI NOVEL', 'aktif'),
(14, 'FIKSI', 'untuk buku FIKSI', 'aktif'),
(17, 'test', 'test', 'aktif');

-- --------------------------------------------------------

--
-- Table structure for table `peminjaman`
--

-- TABEL `peminjaman`: Data induk (header) transaksi peminjaman, satu baris per pengajuan.
-- Kolom:
--   - id_peminjaman: Kunci utama, nomor urut otomatis.
--   - id_user: Kunci tamu ke users.id_user (siapa yang meminjam).
--   - tanggal_pinjam: Tanggal pengajuan/peminjaman.
--   - status: ENUM: Diajukan (default), Dipinjam, Dikembalikan, Ditolak.
--   - alasan_penolakan: Alasan jika ditolak admin (NULL jika tidak ditolak).
CREATE TABLE `peminjaman` (
  `id_peminjaman` int NOT NULL,
  `id_user` int NOT NULL,
  `tanggal_pinjam` date NOT NULL,
  `status` enum('Diajukan','Dipinjam','Dikembalikan','Ditolak') NOT NULL DEFAULT 'Diajukan',
  `alasan_penolakan` varchar(200) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `peminjaman`
--

-- Data contoh untuk tabel `peminjaman` (hasil ekspor; nilai disusun sesuai urutan kolom di atas).
INSERT INTO `peminjaman` (`id_peminjaman`, `id_user`, `tanggal_pinjam`, `status`, `alasan_penolakan`) VALUES
(1, 1, '2026-08-01', 'Dikembalikan', NULL),
(2, 2, '2026-08-03', 'Dikembalikan', NULL),
(3, 3, '2026-08-05', 'Dikembalikan', NULL),
(4, 4, '2026-08-10', 'Dikembalikan', NULL),
(5, 5, '2026-08-15', 'Dikembalikan', NULL),
(6, 6, '2026-08-18', 'Dikembalikan', NULL),
(14, 14, '2026-09-13', 'Dikembalikan', NULL),
(15, 14, '2026-09-13', 'Dikembalikan', NULL),
(16, 12, '2026-09-13', 'Dikembalikan', NULL),
(17, 12, '2026-09-13', 'Dikembalikan', NULL),
(18, 10, '2026-09-13', 'Dikembalikan', NULL),
(19, 19, '2026-09-17', 'Dikembalikan', NULL),
(20, 19, '2026-09-17', 'Ditolak', NULL),
(21, 12, '2026-09-20', 'Dikembalikan', NULL),
(22, 23, '2026-09-22', 'Dikembalikan', NULL),
(23, 23, '2026-09-22', 'Dipinjam', NULL),
(24, 23, '2026-09-22', 'Diajukan', NULL),
(25, 23, '2026-09-22', 'Diajukan', NULL),
(26, 12, '2026-09-27', 'Diajukan', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `penerbit`
--

-- TABEL `penerbit`: Data penerbit buku.
-- Kolom:
--   - id_penerbit: Kunci utama, nomor urut otomatis.
--   - nama_penerbit: Nama penerbit.
--   - alamat: Alamat (opsional).
--   - kota: Kota (opsional).
--   - telepon: Nomor telepon (opsional).
--   - email: Email (opsional).
CREATE TABLE `penerbit` (
  `id_penerbit` int NOT NULL,
  `nama_penerbit` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `alamat` varchar(70) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `kota` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `telepon` varchar(15) DEFAULT NULL,
  `email` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `penerbit`
--

-- Data contoh untuk tabel `penerbit` (hasil ekspor; nilai disusun sesuai urutan kolom di atas).
INSERT INTO `penerbit` (`id_penerbit`, `nama_penerbit`, `alamat`, `kota`, `telepon`, `email`) VALUES
(1, 'Informatika', 'Jl. Buah Batu No. 10', 'Bandung', '227001001', 'info@informatika.com'),
(2, 'Elex Media Komputindo', 'Jl. Palmerah Barat No. 29', 'Jakarta', '215486088', 'info@elexmedia.id'),
(3, 'Andi Publisher', 'Jl. Beo No. 38', 'Yogyakarta', '274588888', 'info@andipublisher.com'),
(4, 'Wahana Komputer', 'Jl. Imam Bonjol No. 27', 'Semarang', '243512345', 'info@wahanakomputer.com'),
(5, 'Gramedia Pustaka Utama', 'Jl. Palmerah Selatan No. 22', 'Jakarta', '821515546', 'info@gramedia.com'),
(6, 'Agromedia Pustaka', 'Jl. H. Montong No. 57', 'Jakarta', '217821234', 'info@agromedia.com'),
(7, 'Mediakom', 'Jl. Raya Condet No. 5', 'Jakarta', '218001234', 'info@mediakom.id'),
(8, 'Maxikom', 'Jl. Cempaka No. 20', 'Surabaya', '317001234', 'info@maxikom.com'),
(9, 'Lokomedia', 'Jl. Janti No. 45', 'Yogyakarta', '274456789', 'info@lokomedia.com'),
(10, 'Deepublish', 'Jl. Rajawali No. 10', 'Yogyakarta', '274555000', 'info@deepublish.com'),
(12, 'harimman house', 'jln sudirman', 'jakarta', '222', 'hariman@gmail.com'),
(13, 'Pastel Books', 'tidak diketahui', 'tidak diketahui', '23114684', 'PastelBooks@gmail.com'),
(5465464, 'GRAMEDIA', 'JLN JAKARTA', 'JAKARTA', '82156525', 'GRAMED@GMAIL.COM'),
(5465466, 'test', 'test', 'tset', '01515151', 'test@gmail.com');

-- --------------------------------------------------------

--
-- Table structure for table `penulis`
--

-- TABEL `penulis`: Data penulis buku.
-- Kolom:
--   - id_penulis: Kunci utama, nomor urut otomatis.
--   - nama_penulis: Nama penulis.
CREATE TABLE `penulis` (
  `id_penulis` int NOT NULL,
  `nama_penulis` varchar(70) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `penulis`
--

-- Data contoh untuk tabel `penulis` (hasil ekspor; nilai disusun sesuai urutan kolom di atas).
INSERT INTO `penulis` (`id_penulis`, `nama_penulis`) VALUES
(1, 'Abdul Kodir'),
(2, 'Budi Raharjo'),
(3, 'Rosa A.S.'),
(4, 'M. Shalahuddin'),
(5, 'Jubilee Enterprise'),
(6, 'Agus Saputra'),
(7, 'Kurniawan'),
(8, 'Madcoms'),
(9, 'Wahana Komputer'),
(10, 'Sukamto'),
(11, 'REZKA BAIQ'),
(12, 'MORGAR HOUSEL'),
(13, 'PIDI BAIQ'),
(16, 'test');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

-- TABEL `users`: Akun pengguna: admin dan siswa.
-- Kolom:
--   - id_user: Kunci utama, nomor urut otomatis.
--   - nis_nip: NIS (siswa) atau NIP (admin), UNIQUE; varchar agar angka nol di depan tidak hilang.
--   - nama_lengkap: Nama lengkap.
--   - kelas: Kelas siswa (opsional).
--   - username: Username untuk login, UNIQUE.
--   - password: Hash bcrypt password (bukan password asli), panjang sampai 200.
--   - role: ENUM: 'admin' atau 'siswa' (default). Menentukan hak akses.
CREATE TABLE `users` (
  `id_user` int NOT NULL,
  `nis_nip` varchar(10) NOT NULL,
  `nama_lengkap` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `kelas` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(200) NOT NULL,
  `role` enum('admin','siswa') NOT NULL DEFAULT 'siswa'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

-- Data contoh untuk tabel `users` (hasil ekspor; nilai disusun sesuai urutan kolom di atas).
INSERT INTO `users` (`id_user`, `nis_nip`, `nama_lengkap`, `kelas`, `username`, `password`, `role`) VALUES
(1, '0060254001', 'Andi Pratama', 'XII RPL 2', 'andi', '$2y$12$ct/h8TGxJCiVMyaz2kuLye5eiJmWHmZZ127e6.7C59ZVeoiwunT/i', 'siswa'),
(2, '0060254002', 'Budi Santoso', 'XII RPL 2', 'budi', '$2y$12$Xe83/iWekZ5/u/amOAGkQuckUtuX60o81/quzozOGa4daHzHuSoOq', 'siswa'),
(3, '0060254003', 'Citra Lestari', 'XII RPL 2', 'citra', '$2y$12$6LC20h/DsoQbICyViWL0Yek6kneEAZfpoQwPhy8jFB/iVs3EFV2KO', 'siswa'),
(4, '0060254004', 'Dimas Saputra', 'XII RPL 2', 'dimas', '$2y$12$X1gXt2ZQ5fvdQUnPy2slIu2MedVTwmt3p2.2kAQ9rii1itoaKKs9i', 'siswa'),
(5, '0060254005', 'Eka Putri', 'XII RPL 2', 'eka', '$2y$12$HTNU1rN3S3ItSe2mmfYxY.iwaOBFG5HTEr/95DsJcOeeYgYmnfNOC', 'siswa'),
(6, '0060254006', 'Fajar Maulana', 'XII RPL 2', 'fajar', '$2y$12$/F5OWg91eqgoqFZOEPv1lOXpNHtGSEyj7pTD6rwvchlMk94kS/fXi', 'siswa'),
(7, '0060254007', 'Gilang Ramadhan', 'XII RPL 2', 'gilang', '$2y$12$I1TnuDHevh5jN9dl7LjoA.HINwYWqIyaaDW64m37OYFDHN3MGtO5a', 'siswa'),
(8, '0060254008', 'Hana Salsabila', 'XII RPL 2', 'hana', '$2y$12$AcuXMRQD493mpDBn1MGcoOcFZHMRdnT66pq139g/IVgfkc.0GzySO', 'siswa'),
(9, '0060254009', 'Intan Permata', 'XII RPL 2', 'intan', '$2y$12$p3Hmy7pS7aVqF//RQSHy2OystEtQjblHwxQNp5XAUXJxG8Hcq6E4m', 'siswa'),
(10, '0060254010', 'Joko Setiawan', 'XII RPL 2', 'joko', '$2y$12$GE3XEMrO7o6bIXHjdaJfAOKXiWVzialhxRlb/UbQEqACMZEvoa2V2', 'siswa'),
(11, '1234567890', 'Admin Perpustakaan', '', 'admin', '$2y$12$cLfz55VwME1h1jSTWs5sveaXHr.C4GAozZGDNJJuoL4PC5uawYtMG', 'admin'),
(12, '0060254012', 'M Eka Santika', 'XII RPL 2', 'Ekan just', '$2y$12$uhDN3NYOYW5O3FH3OmWEZe4umVtmP9crnN/yEeOXliS26WZdlBABC', 'siswa'),
(14, '0060254014', 'galih ruslii', 'XII RPL 2', 'galih', '$2y$12$eDy0IOs9S/prMIvUcy.6me3YV8y3/Ata8YvPx51R4Bdjz2opuXeQO', 'siswa'),
(15, '0060254015', 'REZKA PARSHA AQEELA', 'XII RPL 2', 'REZKA SISWA', '$2y$12$NomrVbl9nwB.u7babyMS9OT/QNnJhzHbIPJ6Ijaoo/O.wj29FDSyG', 'siswa'),
(19, '0060254019', 'REZKA AJA', 'XII RPL 2', 'REZ AZA', '$2y$12$/ro72kgpnZ/MGwCO.8K/o.THKqQ/9XnhemNBHsEUKIfpCK910W4JW', 'siswa'),
(23, '0060254023', 'Alfian Rizky Nugraha', 'XII RPL 2', 'Alfian', '$2y$12$lUfs6UPlWc4hHMGwqn7Rf.0K6r5rCRPLKFu0lJHBt1fkMy//VgUFS', 'siswa'),
(26, '0060254026', 'contoh test', 'XII rpl 3', 'madun23', '$2y$12$9448hiYTZ6MxOjoZ2jiwzOeVos4w5ECsrXYGhxtBBlUJNneFTPlzy', 'siswa'),
(78, '0074191637', 'REZKA PARSHA AQEELA', 'XII RPL 2', 'Lloyd', '$2y$12$AWbeIVBVPSn6I5qTuDXNVOl/6pdJxv.udTXFCqx4PkPoe2SA8RQoK', 'siswa');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `buku`
--
-- INDEX DAN KUNCI (bagian 3): PRIMARY KEY = pengenal unik baris; KEY = index agar pencarian/join cepat dan syarat foreign key; UNIQUE KEY = nilai tidak boleh kembar.
-- Contoh: users.nis_nip dan users.username UNIQUE sehingga tidak ada dua akun dengan NIS/username sama.
ALTER TABLE `buku`
  ADD PRIMARY KEY (`id_buku`),
  ADD KEY `fk_buku_penerbit` (`id_penerbit`),
  ADD KEY `fk_buku_kategori` (`id_kategori`);

--
-- Indexes for table `buku_penulis`
--
-- Kunci utama GABUNGAN (id_buku, id_penulis): pasangan yang sama tidak boleh dimasukkan dua kali.
ALTER TABLE `buku_penulis`
  ADD PRIMARY KEY (`id_buku`,`id_penulis`),
  ADD KEY `fk_buku_penulis_penulis` (`id_penulis`);

--
-- Indexes for table `denda`
--
ALTER TABLE `denda`
  ADD PRIMARY KEY (`id_denda`),
  ADD KEY `fk_denda_detail` (`id_detail`);

--
-- Indexes for table `detail_peminjaman`
--
ALTER TABLE `detail_peminjaman`
  ADD PRIMARY KEY (`id_detail`),
  ADD KEY `fk_detail_peminjaman` (`id_peminjaman`),
  ADD KEY `fk_detail_buku` (`id_buku`);

--
-- Indexes for table `histori_transaksi`
--
ALTER TABLE `histori_transaksi`
  ADD PRIMARY KEY (`id_histori`),
  ADD KEY `fk_histori_peminjaman` (`id_peminjaman`),
  ADD KEY `fk_histori_detail` (`id_detail`);

--
-- Indexes for table `kategori`
--
-- UNIQUE pada nama_kategori: tidak boleh ada dua kategori bernama sama.
ALTER TABLE `kategori`
  ADD PRIMARY KEY (`id_kategori`),
  ADD UNIQUE KEY `nama_kategori` (`nama_kategori`);

--
-- Indexes for table `peminjaman`
--
ALTER TABLE `peminjaman`
  ADD PRIMARY KEY (`id_peminjaman`),
  ADD KEY `fk_peminjaman_user` (`id_user`);

--
-- Indexes for table `penerbit`
--
ALTER TABLE `penerbit`
  ADD PRIMARY KEY (`id_penerbit`);

--
-- Indexes for table `penulis`
--
ALTER TABLE `penulis`
  ADD PRIMARY KEY (`id_penulis`);

--
-- Indexes for table `users`
--
-- UNIQUE pada nis_nip dan username: mencegah akun ganda (kode aplikasi menangkap error duplikat 1062).
ALTER TABLE `users`
  ADD PRIMARY KEY (`id_user`),
  ADD UNIQUE KEY `nis` (`nis_nip`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `buku`
--
-- AUTO_INCREMENT (bagian 4): kolom id dibuat bernomor otomatis. Angka AUTO_INCREMENT=N adalah nomor berikutnya yang akan dipakai.
ALTER TABLE `buku`
  MODIFY `id_buku` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `denda`
--
ALTER TABLE `denda`
  MODIFY `id_denda` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `detail_peminjaman`
--
ALTER TABLE `detail_peminjaman`
  MODIFY `id_detail` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT for table `histori_transaksi`
--
ALTER TABLE `histori_transaksi`
  MODIFY `id_histori` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `kategori`
--
ALTER TABLE `kategori`
  MODIFY `id_kategori` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `peminjaman`
--
ALTER TABLE `peminjaman`
  MODIFY `id_peminjaman` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `penerbit`
--
ALTER TABLE `penerbit`
  MODIFY `id_penerbit` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5465467;

--
-- AUTO_INCREMENT for table `penulis`
--
ALTER TABLE `penulis`
  MODIFY `id_penulis` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id_user` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=79;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `buku`
--
-- FOREIGN KEY (bagian 5): menjaga relasi antar tabel. ON DELETE RESTRICT = data induk TIDAK boleh dihapus selama masih dipakai tabel anak (mis. kategori yang masih dipakai buku). Inilah yang memicu pesan 'tidak dapat dihapus' di aplikasi (error MySQL 1451).
-- Tabel buku: kategori dan penerbit harus ada.
ALTER TABLE `buku`
  ADD CONSTRAINT `fk_buku_kategori` FOREIGN KEY (`id_kategori`) REFERENCES `kategori` (`id_kategori`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `fk_buku_penerbit` FOREIGN KEY (`id_penerbit`) REFERENCES `penerbit` (`id_penerbit`) ON DELETE RESTRICT ON UPDATE RESTRICT;

--
-- Constraints for table `buku_penulis`
--
-- Relasi buku_penulis -> buku dan penulis.
ALTER TABLE `buku_penulis`
  ADD CONSTRAINT `fk_buku_penulis_buku` FOREIGN KEY (`id_buku`) REFERENCES `buku` (`id_buku`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `fk_buku_penulis_penulis` FOREIGN KEY (`id_penulis`) REFERENCES `penulis` (`id_penulis`) ON DELETE RESTRICT ON UPDATE RESTRICT;

--
-- Constraints for table `denda`
--
-- Relasi denda -> detail_peminjaman.
ALTER TABLE `denda`
  ADD CONSTRAINT `fk_denda_detail` FOREIGN KEY (`id_detail`) REFERENCES `detail_peminjaman` (`id_detail`) ON DELETE RESTRICT ON UPDATE RESTRICT;

--
-- Constraints for table `detail_peminjaman`
--
-- Relasi detail_peminjaman -> buku dan peminjaman.
ALTER TABLE `detail_peminjaman`
  ADD CONSTRAINT `fk_detail_buku` FOREIGN KEY (`id_buku`) REFERENCES `buku` (`id_buku`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `fk_detail_peminjaman` FOREIGN KEY (`id_peminjaman`) REFERENCES `peminjaman` (`id_peminjaman`) ON DELETE RESTRICT ON UPDATE RESTRICT;

--
-- Constraints for table `histori_transaksi`
--
-- Relasi histori_transaksi -> detail_peminjaman dan peminjaman (ON UPDATE CASCADE: jika id induk berubah, ikut berubah).
ALTER TABLE `histori_transaksi`
  ADD CONSTRAINT `fk_histori_detail` FOREIGN KEY (`id_detail`) REFERENCES `detail_peminjaman` (`id_detail`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_histori_peminjaman` FOREIGN KEY (`id_peminjaman`) REFERENCES `peminjaman` (`id_peminjaman`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Constraints for table `peminjaman`
--
-- Relasi peminjaman -> users. Karena RESTRICT, user yang sudah punya peminjaman tidak bisa dihapus.
ALTER TABLE `peminjaman`
  ADD CONSTRAINT `fk_peminjaman_user` FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`) ON DELETE RESTRICT ON UPDATE RESTRICT;
-- Simpan permanen semua perubahan di atas (akhir transaksi).
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
