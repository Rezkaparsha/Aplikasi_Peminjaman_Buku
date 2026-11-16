<?php
// =====================================================================
// FILE   : VIEW/SISWA/daftarBuku.php  (URL: /siswa/daftarBuku, lewat c_peminjaman.php?aksi=katalog)
// FUNGSI : KATALOG BUKU siswa: filter (GET) + daftar buku dengan checkbox, kolom jumlah, dan bar bawah untuk mengajukan peminjaman.
// ALUR   : siswa mencentang buku & isi jumlah -> submit (POST) -> CONTROLLER/c_peminjaman.php?aksi=tambah (id_buku[] dan jumlah[]).
// =====================================================================
// Mulai session hanya jika belum aktif (mencegah error session already started).
if (session_status() === PHP_SESSION_NONE) {
    // Mengaktifkan session (penyimpanan data login sementara di server).
    session_start();
}

// Proteksi Akses Siswa
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'siswa') {
    // Jika admin nyasar, kembalikan ke halaman admin terakhir!
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
        // Alamat tujuan: halaman admin terakhir yang tersimpan di session; ?? = nilai cadangan (dashboard) jika belum ada.
        $kembali = $_SESSION['last_page_admin'] ?? '/Aplikasi_Peminjaman_Buku/admin/dashboardAdmin';
        header("Location: " . $kembali);
        exit;
    }
    // Jika belum login
    header("Location: /Aplikasi_Peminjaman_Buku/login");
    exit;
}

// Simpan URL lokasi controller aktif saat ini
$_SESSION['last_page_siswa'] = $_SERVER['REQUEST_URI'];

// Ambil ID User aktif
$id_user = (int)$_SESSION['id_user'];

// Menggunakan Model
require_once __DIR__ . "/../../MODEL/m_buku.php";
// Memuat file model m_kategori.php (berisi class untuk query database).
require_once __DIR__ . "/../../MODEL/m_kategori.php";
// Memuat file model m_penulis.php (berisi class untuk query database).
require_once __DIR__ . "/../../MODEL/m_penulis.php";
// Memuat file model m_penerbit.php (berisi class untuk query database).
require_once __DIR__ . "/../../MODEL/m_penerbit.php";

// Buat objek dari class Buku; variabel $bukuModel dipakai untuk memanggil fungsi-fungsinya.
$bukuModel = new Buku();
// Buat objek dari class Kategori; variabel $kategoriModel dipakai untuk memanggil fungsi-fungsinya.
$kategoriModel = new Kategori();
// Buat objek dari class Penulis; variabel $penulisModel dipakai untuk memanggil fungsi-fungsinya.
$penulisModel = new Penulis();
// Buat objek dari class Penerbit; variabel $penerbitModel dipakai untuk memanggil fungsi-fungsinya.
$penerbitModel = new Penerbit();

// Ambil data untuk opsi dropdown filter
$daftarKategori = $kategoriModel->getAll();
$daftarPenulis  = $penulisModel->getAll();
// Panggil method getAll() milik objek $penerbitModel; hasilnya disimpan di $daftarPenerbit.
$daftarPenerbit = $penerbitModel->getAll();

// Tangkap nilai input GET jika ada
$keyword     = trim($_GET['keyword'] ?? '');
// Baca nilai dari URL ($_GET) atau form ($_POST); '?? nilai' = cadangan jika datanya tidak ada.
$id_kategori = $_GET['id_kategori'] ?? '';
$id_penulis  = $_GET['id_penulis'] ?? '';
// Baca nilai dari URL ($_GET) atau form ($_POST); '?? nilai' = cadangan jika datanya tidak ada.
$id_penerbit = $_GET['id_penerbit'] ?? '';

// Eksekusi fungsi pencarian & filter
$allBuku = $bukuModel->cariDanFilterBuku($keyword, $id_kategori, $id_penulis, $id_penerbit);

// Filter hanya buku yang memiliki stok lebih dari 0
$daftarBuku = array_filter($allBuku, function ($buku) {
    return (int)$buku['stok'] > 0;
});

// Reset index array setelah di-filter agar berurutan kembali
$daftarBuku = array_values($daftarBuku);

// ==========================================
// LOGIKA PAGINATION (12 Buku per Halaman)
// ==========================================
$limit = 12;
// Total seluruh data (count = jumlah elemen array).
$total_data = count($daftarBuku);
// Jumlah halaman = total data / limit, dibulatkan ke atas (ceil).
$total_pages = ceil($total_data / $limit);

// Nomor halaman aktif dari URL (?page=); default halaman 1.
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
// Jaga nomor halaman: tidak boleh kurang dari 1.
if ($page < 1) $page = 1;
// Jaga nomor halaman: tidak boleh melebihi halaman terakhir.
if ($page > $total_pages && $total_pages > 0) $page = $total_pages;

// Posisi data pertama pada halaman aktif (offset).
$offset = ($page - 1) * $limit;

// Potong array data sesuai halaman aktif
$bukuPaginated = array_slice($daftarBuku, $offset, $limit);
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
    <title>Katalog Buku - Perpustakaan</title>

    <!-- Bootstrap 5 CSS & FontAwesome -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Memuat Font Awesome (kumpulan ikon, dipakai lewat class fa-...). -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="website icon" href="/Aplikasi_Peminjaman_Buku/ASSETS/logoApp.jpg">
    <!-- CSS khusus halaman ini (warna, ukuran, tata letak); tidak dikomentari baris demi baris. -->
    <style>
        /* 1. Kunci Layar Utama */
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        html, body {
            height: 100vh;
            width: 100vw;
            margin: 0;
            padding: 0;
            overflow: hidden;
            background-color: #f8fafc;
        }

        .app-wrapper {
            display: flex;
            height: 100vh;
            width: 100vw;
            overflow: hidden;
        }

        /* 2. Main Content Area */
        .main-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            height: 100vh;
            overflow: hidden;
            position: relative;
        }

        /* 3. Container Konten Scrollable */
        .katalog-container {
            padding: 30px;
            flex: 1;
            overflow-y: auto;
            overflow-x: hidden;
        }

        /* Form Filter Styling */
        .filter-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 25px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.02);
        }

        /* Card Buku Custom Styling */
        .card-buku {
            transition: all 0.3s ease;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            overflow: hidden;
            background: #ffffff;
        }

        .card-buku:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 20px rgba(0, 0, 0, 0.06);
            border-color: #cbd5e1;
        }

        /* Cover Container */
        .cover-container {
            height: 220px;
            background-color: #f1f5f9;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            padding: 12px;
        }

        .cover-buku {
            max-height: 100%;
            max-width: 100%;
            object-fit: contain;
            border-radius: 6px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        .buku-checkbox {
            width: 20px;
            height: 20px;
            cursor: pointer;
            accent-color: #2563eb;
        }

        /* STICKY BOTTOM BAR FIXED */
        .sticky-bottom-bar {
            position: fixed;
            bottom: 0;
            left: 280px; /* Disesuaikan persis dengan lebar Sidebar Siswa (280px) */
            right: 0;
            background: #ffffff;
            border-top: 1px solid #e2e8f0;
            box-shadow: 0 -10px 25px rgba(0, 0, 0, 0.08);
            padding: 16px 35px;
            z-index: 1000;
            display: none;
            animation: slideUp 0.3s ease-out;
        }

        @keyframes slideUp {
            from { transform: translateY(100%); }
            to { transform: translateY(0); }
        }

        /* Style Kontrol Paginasi */
        .pagination-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 30px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .pagination-info {
            font-size: 14px;
            color: #64748b;
        }

        .pagination {
            display: flex;
            align-items: center;
            list-style: none;
            margin: 0;
            padding: 0;
            gap: 6px;
        }

        .pagination a {
            padding: 8px 14px;
            text-decoration: none;
            background-color: #ffffff;
            color: #334155;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: 13.5px;
            font-weight: 600;
            transition: all 0.2s ease;
        }

        .pagination a.active {
            background-color: #2563eb;
            color: #ffffff;
            border-color: #2563eb;
            box-shadow: 0 4px 10px rgba(37, 99, 235, 0.25);
        }

        .pagination a:hover:not(.active) {
            background-color: #f1f5f9;
            color: #1e293b;
        }

        @media (max-width: 768px) {
            .sticky-bottom-bar {
                left: 0;
            }
        }
    </style>
</head>

<!-- BAGIAN BODY: seluruh isi yang tampil di layar. -->
<body>

    <div class="app-wrapper">

        <!-- PANGGIL SIDEBAR SISWA -->
        <?php include __DIR__ . "/../TEMPLATE/SideBarSiswa.php"; ?>

        <!-- AREA KONTEN KATALOG -->
        <div class="main-content">

            <div class="katalog-container">

                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h3 class="fw-bold text-dark mb-1"><i class="fa-solid fa-book-open text-primary me-2"></i>Katalog Buku</h3>
                        <p class="text-muted mb-0">Cari dan pilih buku yang ingin Anda pinjam.</p>
                    </div>
                    <!-- CLEAN URL: Peminjaman Saya -->
                    <a href="/Aplikasi_Peminjaman_Buku/siswa/peminjaman" class="btn btn-outline-primary rounded-pill px-4 fw-semibold">
                        <i class="fa-solid fa-list-check me-2"></i>Peminjaman Saya
                    </a>
                </div>

                <!-- Alert Notifikasi -->
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show rounded-3 shadow-sm mb-4" role="alert">
                        <!-- htmlspecialchars() mengubah karakter khusus HTML (< > " ') menjadi aman ditampilkan; ini pencegah XSS (penyisipan script lewat data). -->
                        <i class="fa-solid fa-circle-exclamation me-2"></i><?= htmlspecialchars($_SESSION['error']) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php unset($_SESSION['error']); ?>
                <!-- Akhir kondisi if. -->
                <?php endif; ?>

                <!-- FORM PENCARIAN & FILTER -->
                <div class="filter-card">
                    <!-- FORM PENCARIAN/FILTER: dikirim dengan method GET ke halaman ini sendiri; nilainya muncul di URL dan dibaca controller/view lewat $_GET['...']. -->
                    <form method="GET" action="" class="row g-3 align-items-center">
                        <div class="col-12 col-md-4">
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-search text-muted"></i></span>
                                <!-- Kolom input 'keyword' (type=text) -> dibaca controller sebagai $_GET['keyword']. -->
                                <input type="text" name="keyword" class="form-control border-start-0" placeholder="Cari judul buku..." value="<?= htmlspecialchars($_GET['keyword'] ?? '') ?>">
                            </div>
                        </div>
                        <div class="col-12 col-md-2">
                            <!-- Dropdown 'id_kategori': nilai opsi yang dipilih dikirim sebagai kunci 'id_kategori'. -->
                            <select name="id_kategori" class="form-select">
                                <option value="">Semua Kategori</option>
                                <!-- PERULANGAN: blok di bawah (sampai endforeach) diulang untuk setiap elemen $daftarKategori; elemen aktif ada di $kat. -->
                                <?php foreach ($daftarKategori as $kat): ?>
                                    <?php /* Sintaks echo singkat PHP (tag buka PHP diikuti tanda sama dengan): mencetak nilai PHP langsung ke dalam HTML. */ ?>
                                    <option value="<?= $kat['id_kategori'] ?>" <?= ($id_kategori == $kat['id_kategori']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($kat['nama_kategori']) ?>
                                    </option>
                                <?php /* Akhir perulangan foreach. */ ?>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12 col-md-2">
                            <!-- Dropdown 'id_penulis': nilai opsi yang dipilih dikirim sebagai kunci 'id_penulis'. -->
                            <select name="id_penulis" class="form-select">
                                <option value="">Semua Penulis</option>
                                <!-- PERULANGAN: blok di bawah (sampai endforeach) diulang untuk setiap elemen $daftarPenulis; elemen aktif ada di $pen. -->
                                <?php foreach ($daftarPenulis as $pen): ?>
                                    <option value="<?= $pen['id_penulis'] ?>" <?= ($id_penulis == $pen['id_penulis']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($pen['nama_penulis']) ?>
                                    </option>
                                <?php /* Akhir perulangan foreach. */ ?>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12 col-md-2">
                            <!-- Dropdown 'id_penerbit': nilai opsi yang dipilih dikirim sebagai kunci 'id_penerbit'. -->
                            <select name="id_penerbit" class="form-select">
                                <option value="">Semua Penerbit</option>
                                <!-- PERULANGAN: blok di bawah (sampai endforeach) diulang untuk setiap elemen $daftarPenerbit; elemen aktif ada di $penb. -->
                                <?php foreach ($daftarPenerbit as $penb): ?>
                                    <option value="<?= $penb['id_penerbit'] ?>" <?= ($id_penerbit == $penb['id_penerbit']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($penb['nama_penerbit']) ?>
                                    </option>
                                <?php /* Akhir perulangan foreach. */ ?>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12 col-md-2 d-flex gap-2">
                            <!-- Tombol SUBMIT: mengirim form. -->
                            <button type="submit" class="btn btn-primary w-100 fw-semibold">Filter</button>
                            <!-- CLEAN URL: Reset Filter -->
                            <a href="/Aplikasi_Peminjaman_Buku/siswa/daftarBuku" class="btn btn-secondary text-white"><i class="fa-solid fa-rotate-right"></i></a>
                        </div>
                    </form>
                </div>

                <!-- FORM PENGAJUAN PEMINJAMAN -->
                <form action="/Aplikasi_Peminjaman_Buku/CONTROLLER/c_peminjaman.php?aksi=tambah" method="POST" id="formPeminjaman" style="padding-bottom: 100px;">

                    <div class="row g-4">
                        <!-- KONDISI: blok di bawah (sampai else/endif) hanya tampil jika !empty($bukuPaginated) -->
                        <?php if (!empty($bukuPaginated)): ?>
                            <?php foreach ($bukuPaginated as $i => $buku): 
                                $index = $offset + $i; ?>
                                <div class="col-12 col-sm-6 col-md-4 col-xl-3">
                                    <div class="card card-buku h-100 shadow-sm">

                                        <!-- Container Cover Buku -->
                                        <div class="cover-container">
                                            <!-- KONDISI: blok di bawah (sampai else/endif) hanya tampil jika !empty($buku['cover']) -->
                                            <?php if (!empty($buku['cover'])): ?>
                                                <!-- Gambar COVER buku: nama file dari database disambung ke folder ASSETS/COVER. -->
                                                <img src="/Aplikasi_Peminjaman_Buku/ASSETS/COVER/<?= htmlspecialchars($buku['cover']) ?>" class="cover-buku" alt="Cover Buku">
                                            <!-- ELSE: blok di bawah tampil jika kondisi di atasnya TIDAK terpenuhi. -->
                                            <?php else: ?>
                                                <i class="fa-solid fa-book-open fa-3x text-secondary opacity-50"></i>
                                            <!-- Akhir kondisi if. -->
                                            <?php endif; ?>
                                        </div>

                                        <div class="card-body d-flex flex-column p-3">
                                            <h6 class="fw-bold mb-1 text-truncate" title="<?= htmlspecialchars($buku['judul_buku']) ?>">
                                                <?= htmlspecialchars($buku['judul_buku']) ?>
                                            </h6>
                                            <p class="text-muted small mb-1">Penulis: <?= htmlspecialchars($buku['daftar_penulis'] ?? ($buku['penulis'] ?? '-')) ?></p>
                                            <p class="text-muted small mb-2">Kategori: <?= htmlspecialchars($buku['nama_kategori'] ?? '-') ?></p>

                                            <div class="mt-auto border-top pt-3 d-flex justify-content-between align-items-center">
                                                <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2 py-1">
                                                    Stok: <?= $buku['stok'] ?>
                                                </span>

                                                <!-- Checkbox Pilihan -->
                                                <div class="form-check m-0">
                                                    <!-- Kolom input 'id_buku[{...}]' (type=checkbox) -> dibaca controller sebagai $_POST['id_buku[{...}]']. -->
                                                    <input class="form-check-input buku-checkbox" type="checkbox"
                                                        name="id_buku[<?= $index ?>]"
                                                        value="<?= $buku['id_buku'] ?>"
                                                        id="check_<?= $buku['id_buku'] ?>"
                                                        onchange="toggleJumlah(this, <?= $index ?>)">
                                                </div>
                                            </div>

                                            <!-- Input Jumlah -->
                                            <div class="mt-3" id="container_jumlah_<?= $index ?>" style="display: none;">
                                                <div class="input-group input-group-sm">
                                                    <span class="input-group-text bg-light fw-semibold">Jumlah</span>
                                                    <!-- Kolom input 'jumlah[{...}]' (type=number) -> dibaca controller sebagai $_POST['jumlah[{...}]']. -->
                                                    <input type="number" name="jumlah[<?= $index ?>]" id="input_jumlah_<?= $index ?>"
                                                        class="form-control text-center fw-bold" value="1" min="1" max="<?= $buku['stok'] ?>" disabled>
                                                </div>
                                            </div>

                                        </div>
                                    </div>
                                </div>
                            <!-- Akhir perulangan foreach. -->
                            <?php endforeach; ?>
                        <!-- ELSE: blok di bawah tampil jika kondisi di atasnya TIDAK terpenuhi. -->
                        <?php else: ?>
                            <div class="col-12 text-center py-5">
                                <i class="fa-solid fa-box-open text-muted fa-4x mb-3 opacity-50"></i>
                                <h5 class="text-muted fw-bold">Tidak ada buku yang ditemukan.</h5>
                                <p class="text-muted">Coba gunakan kata kunci atau filter lain.</p>
                            </div>
                        <!-- Akhir kondisi if. -->
                        <?php endif; ?>
                    </div>

                    <!-- KONTROL PAGINASI (SLIDE PER 12 BUKU) -->
                    <?php if (!empty($daftarBuku)): ?>
                        <div class="pagination-container">
                            <p class="pagination-info mb-0">Menampilkan <strong><?= count($bukuPaginated) ?></strong> dari <strong><?= $total_data ?></strong> total buku tersedia</p>
                            
                            <!-- KONDISI: blok di bawah (sampai else/endif) hanya tampil jika $total_pages > 1 -->
                            <?php if ($total_pages > 1): ?>
                                <ul class="pagination">
                                    <!-- KONDISI: blok di bawah (sampai else/endif) hanya tampil jika $page > 1 -->
                                    <?php if ($page > 1): ?>
                                        <li>
                                            <!-- Bawa parameter pencarian ke halaman paginasi -->
                                            <a href="?page=<?= $page - 1 ?>&keyword=<?= urlencode($keyword) ?>&id_kategori=<?= $id_kategori ?>&id_penulis=<?= $id_penulis ?>&id_penerbit=<?= $id_penerbit ?>">« Prev</a>
                                        </li>
                                    <!-- Akhir kondisi if. -->
                                    <?php endif; ?>

                                    <!-- PERULANGAN for ($p = 1; $p <= $total_pages; $p++): blok di bawah diulang sesuai kondisi ini (misalnya membuat tombol nomor halaman). -->
                                    <?php for ($p = 1; $p <= $total_pages; $p++): ?>
                                        <li>
                                            <a href="?page=<?= $p ?>&keyword=<?= urlencode($keyword) ?>&id_kategori=<?= $id_kategori ?>&id_penulis=<?= $id_penulis ?>&id_penerbit=<?= $id_penerbit ?>" class="<?= ($p === $page) ? 'active' : '' ?>">
                                                <?= $p ?>
                                            </a>
                                        </li>
                                    <?php endfor; ?>

                                    <!-- KONDISI: blok di bawah (sampai else/endif) hanya tampil jika $page < $total_pages -->
                                    <?php if ($page < $total_pages): ?>
                                        <li>
                                            <a href="?page=<?= $page + 1 ?>&keyword=<?= urlencode($keyword) ?>&id_kategori=<?= $id_kategori ?>&id_penulis=<?= $id_penulis ?>&id_penerbit=<?= $id_penerbit ?>">Next »</a>
                                        </li>
                                    <!-- Akhir kondisi if. -->
                                    <?php endif; ?>
                                </ul>
                            <!-- Akhir kondisi if. -->
                            <?php endif; ?>
                        </div>
                    <!-- Akhir kondisi if. -->
                    <?php endif; ?>

                    <!-- Sticky Bottom Bar -->
                    <div class="sticky-bottom-bar" id="bottomBar">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="mb-0 fw-bold text-primary">
                                    <i class="fa-solid fa-basket-shopping me-2"></i><span id="totalDipilih">0</span> Jenis Buku Dipilih
                                </h5>
                                <small class="text-muted">Pastikan buku yang dipilih sudah sesuai sebelum mengajukan.</small>
                            </div>
                            <!-- Tombol SUBMIT: mengirim form. -->
                            <button type="submit" class="btn btn-primary rounded-pill px-4 py-2 fw-bold" onclick="return confirm('Ajukan peminjaman sekarang?')">
                                Ajukan Peminjaman <i class="fa-solid fa-paper-plane ms-2"></i>
                            </button>
                        </div>
                    </div>

                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // toggleJumlah(): dipanggil saat checkbox buku dicentang/dilepas. Menampilkan/menyembunyikan kolom jumlah buku itu dan mengaktifkan/menonaktifkan inputnya (input disabled tidak ikut terkirim ke server), lalu memperbarui bar total.
        function toggleJumlah(checkbox, index) {
            // Ambil elemen HTML dan simpan di variabel container agar bisa dibaca/diubah lewat JavaScript.
            const container = document.getElementById('container_jumlah_' + index);
            // Ambil elemen HTML dan simpan di variabel inputJumlah agar bisa dibaca/diubah lewat JavaScript.
            const inputJumlah = document.getElementById('input_jumlah_' + index);

            // Dicentang: tampilkan kolom jumlah dan aktifkan input.
            if (checkbox.checked) {
                container.style.display = 'block';
                inputJumlah.disabled = false;
            } else {
                container.style.display = 'none';
                inputJumlah.disabled = true;
                // Dibatalkan: jumlah dikembalikan ke 1.
                inputJumlah.value = 1;
            }

            // Perbarui bar total di bawah layar.
            updateTotalBar();
        }

        // updateTotalBar(): menghitung buku yang tercentang, menampilkan angkanya, dan memunculkan bar bawah hanya jika ada buku terpilih.
        function updateTotalBar() {
            // Ambil SEMUA checkbox buku yang sedang tercentang (selector :checked).
            const checkboxes = document.querySelectorAll('.buku-checkbox:checked');
            // Ambil elemen HTML dan simpan di variabel bottomBar agar bisa dibaca/diubah lewat JavaScript.
            const bottomBar = document.getElementById('bottomBar');
            // Ambil elemen HTML dan simpan di variabel totalText agar bisa dibaca/diubah lewat JavaScript.
            const totalText = document.getElementById('totalDipilih');

            totalText.innerText = checkboxes.length;
            // Operator ternary: jika ada yang dipilih tampilkan bar (block), jika tidak sembunyikan (none).
            bottomBar.style.display = checkboxes.length > 0 ? 'block' : 'none';
        }
    </script>
</body>

</html>