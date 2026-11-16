<?php
// =====================================================================
// FILE   : VIEW/ADMIN/tambahBuku.php  (URL: /admin/tambahBuku, lewat c_buku.php?aksi=tambah_view)
// FUNGSI : FORM TAMBAH BUKU (termasuk upload cover dan pilihan banyak penulis).
// ALUR   : submit (POST multipart) -> CONTROLLER/c_buku.php?aksi=tambah. Data dropdown ($dataKategori, $dataPenerbit, $dataSemuaPenulis) disiapkan controller.
// =====================================================================
// Mulai session hanya jika belum aktif (mencegah error session already started).
if (session_status() === PHP_SESSION_NONE) {
    // Mengaktifkan session (penyimpanan data login sementara di server).
    session_start();
}

// Proteksi Akses Admin Terpusat
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'admin') {
    // Jika yang login ternyata berrole 'siswa' (nyasar ke halaman ini), kembalikan ke halamannya sendiri.
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'siswa') {
        // Alamat tujuan: halaman siswa terakhir yang tersimpan di session; ?? = nilai cadangan (dashboard) jika belum ada.
        $kembali = $_SESSION['last_page_siswa'] ?? '/Aplikasi_Peminjaman_Buku/siswa/dashboardSiswa';
        header("Location: " . $kembali);
        exit;
    }
    header("Location: /Aplikasi_Peminjaman_Buku/login");
    exit;
}

// Simpan URL lokasi controller aktif saat ini
$_SESSION['last_page_admin'] = $_SERVER['REQUEST_URI'];

// Memuat file model m_kategori.php (berisi class untuk query database).
require_once __DIR__ . "/../../MODEL/m_kategori.php";
// Memuat file model m_penerbit.php (berisi class untuk query database).
require_once __DIR__ . "/../../MODEL/m_penerbit.php";
// Memuat file model m_penulis.php (berisi class untuk query database).
require_once __DIR__ . "/../../MODEL/m_penulis.php";

// Buat objek dari class Kategori; variabel $kategoriModel dipakai untuk memanggil fungsi-fungsinya.
$kategoriModel = new Kategori();
// Buat objek dari class Penerbit; variabel $penerbitModel dipakai untuk memanggil fungsi-fungsinya.
$penerbitModel = new Penerbit();
// Buat objek dari class Penulis; variabel $penulisModel dipakai untuk memanggil fungsi-fungsinya.
$penulisModel = new Penulis();

// Panggil method getAll() milik objek $kategoriModel; hasilnya disimpan di $dataKategori.
$dataKategori = $kategoriModel->getAll();
// Panggil method getAll() milik objek $penerbitModel; hasilnya disimpan di $dataPenerbit.
$dataPenerbit = $penerbitModel->getAll();
// Panggil method getAll() milik objek $penulisModel; hasilnya disimpan di $dataPenulis.
$dataPenulis = $penulisModel->getAll();
?>
<!DOCTYPE html>
<html lang="id">
<!-- BAGIAN HEAD: judul tab, pengaturan karakter, dan pemuat CSS/JS dari luar (tidak tampil di halaman). -->
<head>
    <!-- Set karakter UTF-8 agar huruf khusus tampil benar. -->
    <meta charset="UTF-8">
    <!-- Viewport: tampilan menyesuaikan lebar layar HP/laptop (responsif). -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Judul yang tampil di tab browser. -->
    <title>Tambah Buku - Perpustakaan Online</title>
    <!-- CSS khusus halaman ini (warna, ukuran, tata letak); tidak dikomentari baris demi baris. -->
    <style>
        /* Reset & Base Styles */
        * {
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        body {
            background-color: #f4f7f6;
            color: #333;
            margin: 0;
            padding: 20px;
        }

        /* Container Setup */
        .container {
            max-width: 600px; /* Dibuat lebih kecil untuk form */
            margin: 0 auto;
            background: #ffffff;
            padding: 30px 40px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.05);
        }

        /* Typography */
        h2 {
            color: #2c3e50;
            margin-top: 0;
            margin-bottom: 25px;
            text-align: center;
            border-bottom: 2px solid #ecf0f1;
            padding-bottom: 10px;
        }

        /* Form Layout */
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: #34495e;
            font-size: 14px;
        }

        /* Inputs & Selects */
        .form-control {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ccd1d1;
            border-radius: 5px;
            font-size: 14px;
            color: #2c3e50;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: #3498db;
            outline: none;
            box-shadow: 0 0 5px rgba(52, 152, 219, 0.3);
        }

        select[multiple] {
            height: auto;
            padding: 5px;
        }
        
        select[multiple] option {
            padding: 8px;
            border-radius: 3px;
        }

        /* Helper Text */
        .help-text {
            display: block;
            margin-top: 5px;
            font-size: 12px;
            color: #7f8c8d;
        }

        /* Buttons */
        .action-group {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }

        .btn {
            flex: 1;
            padding: 12px;
            border: none;
            border-radius: 5px;
            font-size: 15px;
            font-weight: bold;
            cursor: pointer;
            text-align: center;
            text-decoration: none;
            transition: background-color 0.3s ease;
        }

        .btn-submit {
            background-color: #2ecc71;
            color: white;
        }
        .btn-submit:hover { background-color: #27ae60; }
        
        .btn-cancel {
            background-color: #e74c3c;
            color: white;
        }
        .btn-cancel:hover { background-color: #c0392b; }

    </style>
</head>
<!-- BAGIAN BODY: seluruh isi yang tampil di layar. -->
<body>

    <div class="container">
        <h2>Tambah Buku Baru</h2>

        <!-- FORM: saat disubmit, data dikirim dengan method POST ke /Aplikasi_Peminjaman_Buku/CONTROLLER/c_buku.php?aksi=tambah (enctype multipart = form boleh mengirim file). Atribut name pada setiap input menjadi kunci $_POST['...'] di controller. -->
        <form action="/Aplikasi_Peminjaman_Buku/CONTROLLER/c_buku.php?aksi=tambah" method="POST" enctype="multipart/form-data">

            <div class="form-group">
                <label for="judul_buku">Judul Buku</label>
                <!-- Kolom input 'judul_buku' (type=text) -> dibaca controller sebagai $_POST['judul_buku']. -->
                <input type="text" id="judul_buku" name="judul_buku" class="form-control" required placeholder="Masukkan judul buku">
            </div>

            <div class="form-group">
                <label for="tahun_terbit">Tahun Terbit</label>
                <!-- Kolom input 'tahun_terbit' (type=number) -> dibaca controller sebagai $_POST['tahun_terbit']. -->
                <input type="number" id="tahun_terbit" name="tahun_terbit" class="form-control" min="1000" max="9999" required placeholder="Contoh: 2023">
            </div>

            <div class="form-group">
                <label for="harga_buku">Harga Buku (Rp)</label>
                <!-- Kolom input 'harga_buku' (type=number) -> dibaca controller sebagai $_POST['harga_buku']. -->
                <input type="number" id="harga_buku" name="harga_buku" class="form-control" min="0" required placeholder="Contoh: 75000">
            </div>

            <div class="form-group">
                <label for="stok">Stok Buku</label>
                <!-- Kolom input 'stok' (type=number) -> dibaca controller sebagai $_POST['stok']. -->
                <input type="number" id="stok" name="stok" class="form-control" min="0" required placeholder="Jumlah stok">
            </div>

            <div class="form-group">
                <label for="id_penerbit">Penerbit</label>
                <!-- Dropdown 'id_penerbit': nilai opsi yang dipilih dikirim sebagai kunci 'id_penerbit'. -->
                <select id="id_penerbit" name="id_penerbit" class="form-control" required>
                    <option value="">-- Pilih Penerbit --</option>
                    <!-- PERULANGAN: blok di bawah (sampai endforeach) diulang untuk setiap elemen $dataPenerbit; elemen aktif ada di $penerbit. -->
                    <?php foreach ($dataPenerbit as $penerbit): ?>
                        <?php /* Sintaks echo singkat PHP (tag buka PHP diikuti tanda sama dengan): mencetak nilai PHP langsung ke dalam HTML. */ ?>
                        <option value="<?= $penerbit['id_penerbit'] ?>">
                            <?php /* htmlspecialchars() mengubah karakter khusus HTML (< > " ') menjadi aman ditampilkan; ini pencegah XSS (penyisipan script lewat data). */ ?>
                            <?= htmlspecialchars($penerbit['nama_penerbit']) ?>
                        </option>
                    <?php /* Akhir perulangan foreach. */ ?>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="id_kategori">Kategori</label>
                <!-- Dropdown 'id_kategori': nilai opsi yang dipilih dikirim sebagai kunci 'id_kategori'. -->
                <select id="id_kategori" name="id_kategori" class="form-control" required>
                    <option value="">-- Pilih Kategori --</option>
                    <!-- PERULANGAN: blok di bawah (sampai endforeach) diulang untuk setiap elemen $dataKategori; elemen aktif ada di $kategori. -->
                    <?php foreach ($dataKategori as $kategori): ?>
                        <option value="<?= $kategori['id_kategori'] ?>">
                            <?= htmlspecialchars($kategori['nama_kategori']) ?>
                        </option>
                    <?php /* Akhir perulangan foreach. */ ?>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="id_penulis">Penulis (Bisa lebih dari 1)</label>
                <!-- Dropdown 'id_penulis[]': nilai opsi yang dipilih dikirim sebagai kunci 'id_penulis[]'. -->
                <select id="id_penulis" name="id_penulis[]" class="form-control" multiple required size="5">
                    <!-- PERULANGAN: blok di bawah (sampai endforeach) diulang untuk setiap elemen $dataPenulis; elemen aktif ada di $penulis. -->
                    <?php foreach ($dataPenulis as $penulis): ?>
                        <option value="<?= $penulis['id_penulis'] ?>">
                            <?= htmlspecialchars($penulis['nama_penulis']) ?>
                        </option>
                    <?php /* Akhir perulangan foreach. */ ?>
                    <?php endforeach; ?>
                </select>
                <span class="help-text">*Gunakan <b>Ctrl + klik</b> (Windows) atau <b>Cmd + klik</b> (Mac) untuk memilih lebih dari satu penulis.</span>
            </div>

            <div class="form-group">
                <label for="cover">Cover Buku</label>
                <!-- Input file 'cover': pilih file dari komputer (dibaca controller lewat $_FILES['cover']). -->
                <input type="file" id="cover" name="cover" class="form-control" accept=".jpg,.jpeg,.png,.webp" style="padding: 7px 12px;">
                <span class="help-text">*Format: JPG, JPEG, PNG, WEBP. Maksimal ukuran 2 MB.</span>
            </div>

            <div class="action-group">
                <!-- CLEAN URL: Kembali ke Daftar Buku -->
                <a href="/Aplikasi_Peminjaman_Buku/admin/daftarBuku" class="btn btn-cancel">Batal</a>
                <!-- Tombol SUBMIT: mengirim form. -->
                <button type="submit" class="btn btn-submit">Simpan Buku</button>
            </div>

        </form>
    </div>

</body>
</html>