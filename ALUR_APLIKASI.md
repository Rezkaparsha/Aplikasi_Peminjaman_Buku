# Panduan Belajar: Aplikasi Peminjaman Buku (Lengkap: semua file PHP)

Dokumen ini pendamping file kode berkomentar. Isinya hanya bagian yang sudah
saya baca langsung dari kode proyekmu.

## 1. Arsitektur MVC di proyek ini

| Lapisan | Folder | Tugas | Contoh |
|---|---|---|---|
| Model | `MODEL/` | Satu-satunya yang bicara ke database (query SQL) | `m_users.php` |
| View | `VIEW/` | Tampilan HTML (admin, siswa, auth, template sidebar) | `VIEW/AUTH/login.php` |
| Controller | `CONTROLLER/` | Menerima request, validasi, panggil model, redirect | `c_login.php` |

Pola pemanggilan di controller: URL membawa `?aksi=...`, lalu blok `switch`
di bagian bawah file memilih fungsi yang dijalankan.

## 2. Alur sebuah request (routing lewat .htaccess)

1. Browser membuka URL bersih, misalnya `/Aplikasi_Peminjaman_Buku/admin/daftarBuku`.
2. Apache membaca `.htaccess` (butuh `mod_rewrite`) dan menulis ulang URL itu
   menjadi `CONTROLLER/c_buku.php?aksi=index`.
3. Controller membuat objek model, mengambil data, lalu memuat View.
4. View menampilkan HTML ke browser.

Hal penting di `.htaccess`:
- `Options -Indexes`: isi folder tidak bisa dilihat lewat browser.
- `RewriteRule ^(DATABASE|MODEL)(/|$) - [F,L]`: folder DATABASE dan MODEL
  tidak bisa dibuka langsung dari URL (respon 403 Forbidden).
- Blok `ASSETS`: file `.php` di folder ASSETS ditolak, supaya file PHP yang
  diunggah tidak bisa dijalankan.
- `RewriteCond ... -f [OR] -d`: kalau yang diminta file/folder nyata, tidak
  di-rewrite.
- Flag `[QSA,L]`: QSA = query string ikut terbawa, L = aturan terakhir.

Pembagian rute: halaman statis/form (login, register, dashboard, daftar user,
kategori, penerbit, penulis) langsung ke file di `VIEW/`. Halaman yang butuh
data dari controller (buku, peminjaman, histori, katalog siswa) lewat
`CONTROLLER/...?aksi=...`.

## 3. Alur Login (sudah dikomentari di `c_login.php`)

1. Pengguna isi NIS/NIP atau username + password di `VIEW/AUTH/login.php`.
2. Form mengirim POST ke `c_login.php?aksi=login`.
3. Controller cek input tidak kosong, lalu `SELECT` user berdasarkan
   `nis_nip` ATAU `username` (prepared statement).
4. `password_verify()` mencocokkan input dengan hash bcrypt di database.
5. Jika cocok, `$_SESSION` diisi: `id_user`, `nis_nip`, `username`,
   `nama_lengkap`, `role`.
6. Redirect sesuai role: `admin` ke `/admin/dashboardAdmin`, selain itu ke
   `/siswa/dashboardSiswa`.
7. Jika gagal: pesan di `$_SESSION['error']`, kembali ke halaman login.

Alur Registrasi: validasi (kolom wajib, password sama, nama hanya huruf,
NIS 3 sampai 10 digit) -> cek duplikat NIS/username -> `password_hash`
(bcrypt) -> `INSERT` dengan role `siswa` -> redirect ke login.

## 4. Struktur database `db_perpustakaan` (dari file SQL)

| Tabel | Isi | Kolom penting |
|---|---|---|
| `users` | Admin dan siswa | `id_user`, `nis_nip`, `username`, `password` (hash), `role` (admin/siswa) |
| `buku` | Data buku | `id_penerbit`, `id_kategori`, `judul_buku`, `stok`, `cover` |
| `kategori` | Genre buku | `nama_kategori`, `status` (aktif/nonaktif) |
| `penerbit` | Penerbit | nama, alamat, kota, telepon, email |
| `penulis` | Penulis | `nama_penulis` |
| `buku_penulis` | Penghubung buku dan penulis (relasi banyak ke banyak) | PK gabungan `id_buku` + `id_penulis` |
| `peminjaman` | Header transaksi | `id_user`, `tanggal_pinjam`, `status` (Diajukan/Dipinjam/Dikembalikan/Ditolak), `alasan_penolakan` |
| `detail_peminjaman` | Buku apa saja di satu transaksi | `id_peminjaman`, `id_buku`, `jumlah_buku`, `tanggal_pengembalian` (batas), `tanggal_dikembalikan` (realisasi), `status` |
| `denda` | Denda keterlambatan | `id_detail`, `jumlah_hari_terlambat`, `jumlah_denda`, `status_pembayaran` |
| `histori_transaksi` | Arsip transaksi selesai (salinan data siswa/buku saat itu) | `id_peminjaman`, `id_detail`, `judul_buku`, `denda`, `tanggal_selesai` |

Relasi utama (semua foreign key memakai `ON DELETE RESTRICT`, artinya data
induk tidak bisa dihapus selama masih dipakai tabel lain):

- `users` 1 ke banyak `peminjaman`
- `peminjaman` 1 ke banyak `detail_peminjaman`
- `buku` 1 ke banyak `detail_peminjaman`
- `detail_peminjaman` 1 ke banyak `denda`
- `kategori` / `penerbit` 1 ke banyak `buku`
- `buku` banyak ke banyak `penulis` lewat `buku_penulis`

## 5. Poin yang sering ditanyakan penguji (dari kode yang sudah dibaca)

- Kenapa pakai prepared statement? Mencegah SQL Injection.
- Kenapa password di-hash? Database bocor pun password asli tidak terbaca.
- Apa fungsi `session`? Menyimpan identitas login antar halaman.
- Apa itu RBAC? Hak akses dibedakan per role (admin vs siswa).
- Catatan jujur di `c_login.php`: baris `$passValid = ... or ... or ...`
  hanya menyimpan hasil `password_verify()` karena prioritas operator `=`
  lebih tinggi daripada `or`. Fallback md5/plain text tidak aktif.

## 6. Alur Peminjaman dan Pengembalian (dari `c_peminjaman.php` dan `m_peminjaman.php`)

Penting: di kode, yang memproses pengembalian adalah ADMIN, bukan siswa.
README menulis "Siswa mengembalikan buku", jadi sebelum ujian pastikan kamu
menjelaskan sesuai kode yang sebenarnya.

| Langkah | Pelaku | Method controller | Fungsi model | Perubahan data |
|---|---|---|---|---|
| 1. Mengajukan pinjam | Siswa | `tambah()` | `tambahPeminjaman()` | Insert `peminjaman` (status Diajukan) + `detail_peminjaman`. Stok hanya dicek, BELUM berkurang |
| 2a. Menyetujui | Admin | `setujui()` | `setujuiPeminjaman()` | Stok BERKURANG, `tanggal_pengembalian` diisi admin, status jadi Dipinjam |
| 2b. Menolak | Admin | `tolak()` | `tolakPeminjaman()` | Status jadi Ditolak + `alasan_penolakan`. Stok tidak berubah |
| 3. Mengembalikan | Admin | `prosesKembali()` | `kembalikanBuku()` | Stok bertambah (kecuali buku Hilang), denda dihitung, data disalin ke `histori_transaksi`, status jadi Dikembalikan |

Status: `Diajukan` -> `Dipinjam` -> `Dikembalikan`, atau `Diajukan` -> `Ditolak`.

### Aturan denda (di `kembalikanBuku`)
- Tarif: Rp 1.000 per hari terlambat (variabel `$tarif_denda_per_hari`).
- Terlambat jika tanggal hari ini > `tanggal_pengembalian` (batas yang diisi admin saat menyetujui).
- Jumlah hari = `diff()->days` antara dua tanggal.
- Buku Rusak/Hilang: admin mengisi denda tambahan per buku, ditambahkan ke total.
- Denda hanya disimpan ke tabel `denda` bila total > 0.
- Buku berkondisi Hilang: stok TIDAK dikembalikan.

### Konsep database yang dipakai
- TRANSAKSI (`begin_transaction`, `commit`, `rollback`): beberapa perubahan
  tersimpan semuanya atau dibatalkan semuanya (misalnya stok tidak berkurang
  kalau simpan detail gagal).
- `SELECT ... FOR UPDATE`: mengunci baris supaya dua proses yang berjalan
  bersamaan tidak membaca stok yang sama.
- Syarat `AND stok >= ?` pada UPDATE stok: pengaman agar stok tidak minus.
- Cek kepemilikan (`cekKepemilikanPeminjaman`): siswa tidak bisa membuka
  peminjaman siswa lain dengan mengganti angka di URL.

### Perlindungan role di controller
- Admin yang membuka halaman siswa dikembalikan ke halaman admin.
- Non-admin yang membuka fitur admin dikembalikan ke halaman siswa
  (`cekRoleAdmin()`).

## 7. Catatan yang perlu kamu ketahui (jujur, dari kode)

1. `c_login.php`: fallback md5/plain text tidak aktif (prioritas operator `=`
   vs `or`). Hanya hash bcrypt yang diterima.
2. Di tabel `histori_transaksi`, kolom `nis_nip` bertipe `int`, sedangkan di
   `users` bertipe `varchar(10)` (NIS seperti `0060254001` diawali angka nol).
   Kemungkinan besar nol di depan hilang pada tabel histori. Pencocokan
   (JOIN) di `getHistoriTransaksi()` tetap bisa jalan karena dibandingkan
   sebagai angka. Ini sebaiknya kamu cek sendiri di phpMyAdmin.
3. `index.php` dan `.htaccess` memakai clean URL, tetapi `VIEW/AUTH/login.php`
   (bagian atas file) masih me-redirect ke URL lama
   `CONTROLLER/c_peminjaman.php?aksi=admin` dan `VIEW/SISWA/daftarBuku.php`
   untuk user yang sudah login. Fungsinya tetap jalan, hanya gayanya
   tidak seragam.
4. Denda keterlambatan dihitung per baris buku (per `id_detail`), tidak
   dikali jumlah eksemplar.

## 8. Alur CRUD Data Master (tahap 3)

### Pola yang sama di buku, kategori, penerbit, penulis
| Aksi | URL / pemicu | Yang terjadi |
|---|---|---|
| Lihat daftar | `/admin/daftarBuku` lewat controller; kategori/penerbit/penulis langsung ke VIEW (view memanggil `getAll()` sendiri) | Data tampil di tabel |
| Tambah | Form (POST) ke `c_x.php?aksi=tambah` | Validasi -> isi properti model -> `insert()` -> pesan sukses/error di session -> redirect ke daftar |
| Ubah | `?aksi=edit&id_x=..` menampilkan form, lalu POST ke `?aksi=update` | Ambil data lama -> validasi -> `update()` |
| Hapus | `?aksi=hapus&id_x=..` | Cek `isUsedInBuku()`; kalau masih dipakai buku, ditolak dengan pesan |

Setiap controller admin diawali gerbang keamanan: bukan admin atau belum login
langsung dialihkan.

### Khusus Buku (`c_buku.php`, `m_buku.php`, `m_buku_penulis.php`)
- Satu buku punya satu kategori dan satu penerbit (kolom di tabel `buku`),
  tetapi boleh punya banyak penulis lewat tabel penghubung `buku_penulis`.
- Tambah buku: simpan buku dulu -> ambil `insert_id` -> simpan satu baris
  `buku_penulis` untuk tiap penulis terpilih.
- Ubah buku: data buku diperbarui, lalu semua relasi penulis lama dihapus
  (`deleteByBuku`) dan dimasukkan ulang sesuai pilihan baru.
- Upload cover: ekstensi dibatasi `jpg/jpeg/png/webp`, nama file dibuat acak
  (`uniqid`), disimpan di `ASSETS/COVER/`. Saat cover diganti atau buku
  dihapus, file cover lama ikut dihapus (`unlink`).
- Hapus buku: ditolak oleh foreign key bila buku sudah punya riwayat
  peminjaman (kode error MySQL 1451), controller menampilkan pesan yang ramah.
- Katalog siswa memakai `cariDanFilterBuku()`: query disusun bertahap sesuai
  filter yang diisi (judul, kategori, penerbit); filter penulis dilakukan di
  PHP karena relasinya banyak-ke-banyak.

### Konsep yang sering ditanyakan
- Relasi banyak-ke-banyak buku-penulis: butuh tabel penghubung dengan primary
  key gabungan (`id_buku`, `id_penulis`).
- Integritas data: foreign key `ON DELETE RESTRICT` + cek `isUsedInBuku()` di
  aplikasi (dua lapis perlindungan).
- Keamanan upload: whitelist ekstensi, nama file acak, file PHP tidak
  dieksekusi di folder `ASSETS` (diblokir `.htaccess`).

## 9. Catatan tambahan dari tahap 3 (dari kode)

5. `c_buku.php` -> `hapus()`: relasi penulis dihapus lebih dulu, baru buku.
   Keduanya tidak dibungkus transaksi. Jika penghapusan buku gagal karena
   foreign key, relasi penulis yang sudah terhapus tidak kembali.
   (Di `m_peminjaman.php` kamu sudah memakai transaksi; pola itu bisa
   dipakai di sini juga.)
6. Aksi hapus memakai link/URL (method GET). Secara praktik web, aksi yang
   mengubah data sebaiknya memakai POST. Jika ditanya penguji, ini bisa
   disebut sebagai bahan pengembangan.
7. Fungsi `getAktif()` di `m_kategori.php` tidak dipanggil di mana pun dalam
   proyek yang saya baca.
8. Validasi upload hanya memeriksa ekstensi nama file, bukan isi file.

## 10. Sisa controller dan model (tahap 4)

| File | Fungsi | Terhubung? |
|---|---|---|
| `c_user.php` | Tambah, ubah, hapus user, ganti password. Berupa `switch` di bawah file | Ya (form di view user dan profil) |
| `c_histori.php` | Gerbang admin untuk halaman histori | Ya (`/admin/historiTransaksi`) |
| `m_histori_transaksi.php` | Model tabel histori | Hanya `getAll()` dipanggil `c_histori`, tetapi view tidak memakai hasilnya |
| `c_denda.php`, `m_denda.php` | Kerangka CRUD denda dan "bayar denda" | Tidak ada rute, tidak dipanggil file lain |
| `m_detail_peminjaman.php` | Model tabel detail | Tidak dipanggil; operasi detail dikerjakan langsung di `M_Peminjaman` |

Sumber data histori yang sebenarnya tampil: view `historiTransaksi.php`
memanggil `M_Peminjaman::getHistoriTransaksi()` sendiri.

## 11. Folder VIEW (29 file)

Pola semua view:
1. Blok PHP di atas: mulai session -> gerbang role -> `require_once` model ->
   ambil data -> hitung pagination.
2. HTML di bawah: `<head>` (CSS dari CDN: Bootstrap atau Tailwind, Font Awesome,
   SweetAlert2), sidebar lewat `include`, lalu isi halaman (tabel/form).
3. `<script>` kecil di bagian akhir: konfirmasi hapus (SweetAlert2), toast
   notifikasi, dan sedikit logika checkbox di katalog siswa.

Konsep penting di view:
- Flash message: controller mengisi `$_SESSION['error'/'success']`, view
  menampilkannya lalu `unset` agar tampil sekali.
- `htmlspecialchars()` mencegah XSS saat mencetak data.
- Pagination: `ceil(total/limit)` untuk jumlah halaman, `array_slice` untuk
  memotong data per halaman (10 data admin, 12 buku katalog siswa).
- Perulangan dan kondisi memakai sintaks alternatif `foreach: ... endforeach`
  dan `if: ... endif` yang cocok dicampur dengan HTML.

Yang dikomentari di View: header tiap file, seluruh logika PHP, setiap form
(beserta nama input dan tujuan POST/GET), perulangan dan kondisi, tautan
navigasi, dan semua fungsi JavaScript. Kode CSS dan class styling HTML tidak
dikomentari baris demi baris karena tidak membantu pemahaman alur.

## 12. Ringkasan temuan dari kode (belum diubah, untuk bahan diskusi atau perbaikan)

Prioritas lebih penting:
1. `c_user.php`: hanya mengecek sudah login, tanpa cek role admin. Pada aksi
   `updatePassword`, siapa pun yang login dan mengirim POST `id_user` dapat
   mengganti password user itu. Tambahkan pengecekan `role === 'admin'`
   untuk aksi tambah, update, hapus, dan blok ganti-password-user-lain.
2. `tambahPenerbit.php` dan `tambahPenulis.php`: action form memakai path
   relatif `../../CONTROLLER/...`. Pada clean URL ini kemungkinan besar
   menunjuk ke luar folder aplikasi (404). Samakan dengan form lain:
   `/Aplikasi_Peminjaman_Buku/CONTROLLER/...`.
3. `c_user.php`: redirect error ke `/admin/user/tambahUser` dan
   `/admin/user/editUser` tidak ada di `.htaccess` (rute yang ada
   `/admin/tambahUser` dan `/admin/editUser`), sehingga kemungkinan 404.
4. `c_user.php`: regex NIS `\d{10}` mewajibkan tepat 10 digit, sedangkan pesan
   menyebut 3 sampai 10 digit (registrasi siswa memakai 3 sampai 10).

Catatan lain:
5. `dashboardSiswa.php`: statistik memakai `'menunggu'` yang bukan nilai status;
   status pengajuan sebenarnya `Diajukan`, sehingga pengajuan tidak terhitung.
6. `dashboardAdmin.php`: `$_SESSION['nama']` tidak pernah diisi saat login.
7. `c_buku.php` hapus: relasi penulis dihapus lebih dulu tanpa transaksi;
   hapus lewat GET; upload cover hanya cek ekstensi.
8. `c_login.php`: fallback md5/plain tidak aktif (prioritas operator).
9. `histori_transaksi.nis_nip` bertipe `int` padahal `users.nis_nip` varchar
   (angka nol di depan kemungkinan hilang). Cek di phpMyAdmin.
10. `c_denda`, `m_denda`, `m_detail_peminjaman` belum tersambung;
    halaman pembayaran denda belum ada.

## 13. Status pengerjaan komentar

SELESAI untuk semua file kode dan konfigurasi proyek:
- 50 file PHP: `index.php`, 9 controller, 11 model, 29 view (AUTH, ADMIN, SISWA, TEMPLATE)
- `.htaccess` (routing clean URL dan proteksi folder)
- `DATABASE/db_perpustakaan.sql` (penjelasan tiap tabel, kolom, key, dan relasi)
- `README.md` dan dokumen ini

Folder `ASSETS` hanya berisi gambar (cover dan logo), jadi tidak ada yang dikomentari
dan tidak disertakan dalam zip ini.

Cara memakai: salin file-file berkomentar ini menimpa file yang sama di folder
proyekmu (struktur folder sama), lalu biarkan folder `ASSETS` apa adanya.

Cara menjamin kode tidak rusak: komentar hanya ditambahkan sebagai baris baru.
Setelah baris komentar dibuang, setiap file (PHP, .htaccess, SQL) kembali
identik dengan aslinya; diperiksa otomatis. Untuk View, struktur PHP/HTML/JS
juga dicek tidak bergeser dan JavaScript diperiksa sintaksnya. Komentar tidak
memuat tag PHP (`<?`, `?>`) agar tidak mengganggu eksekusi. Catatan: PHP, Apache,
dan MySQL tidak tersedia di lingkungan saya, jadi aplikasi belum dijalankan;
uji sekali di Laragon (dan impor ulang SQL ke database kosong bila ingin
memastikan file SQL berkomentar tetap terimpor).
