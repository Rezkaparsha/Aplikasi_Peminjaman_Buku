<?php
// =====================================================================
// FILE   : VIEW/ADMIN/daftarPenerbit.php  (URL: /admin/daftarPenerbit)
// FUNGSI : DAFTAR PENERBIT admin: tabel penerbit + tombol tambah/edit/hapus.
// DATA   : model Penerbit::getAll() (view memanggil model sendiri). Hapus -> konfirmasi JS -> CONTROLLER/c_penerbit.php?aksi=hapus.
// =====================================================================
// Mulai session hanya jika belum aktif (mencegah error session already started).
if (session_status() === PHP_SESSION_NONE) {
    // Mengaktifkan session (penyimpanan data login sementara di server).
    session_start();
}

// 1. Proteksi Akses Admin Terpusat
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

// 2. Simpan URL lokasi halaman view ini
$_SESSION['last_page_admin'] = $_SERVER['REQUEST_URI'];

// 3. Panggil Model Langsung di View
require_once __DIR__ . "/../../MODEL/m_penerbit.php";

// Buat objek dari class Penerbit; variabel $penerbitModel dipakai untuk memanggil fungsi-fungsinya.
$penerbitModel = new Penerbit();
// Panggil method getAll() milik objek $penerbitModel; hasilnya disimpan di $daftarPenerbit.
$daftarPenerbit = $penerbitModel->getAll();

// ==========================================
// LOGIKA PAGINATION (10 Data per Halaman)
// ==========================================
$limit = 10;
// Total seluruh data (count = jumlah elemen array).
$total_data = count($daftarPenerbit);
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
$penerbitPaginated = array_slice($daftarPenerbit, $offset, $limit);
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
    <title>Daftar Penerbit - Perpustakaan Online</title>
        <!-- Google Fonts: Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- CSS khusus halaman ini (warna, ukuran, tata letak); tidak dikomentari baris demi baris. -->
    <style>
        /* Reset & Base Styles */
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
            box-sizing: border-box;
            background-color: #f4f7f6;
            display: flex;
        }

        .main-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            height: 100vh;
            overflow: hidden;
        }

        .container {
            padding: 25px;
            flex: 1;
            overflow-y: auto;
            overflow-x: hidden;
        }

        .card {
            background: #ffffff;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.05);
            width: 100%;
        }

        .table-responsive {
            width: 100%;
            overflow-x: auto;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            min-width: 100%;
        }

        .topbar {
            background-color: #fff;
            padding: 15px 30px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .topbar h2 {
            font-size: 20px;
            color: #2c3e50;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #ecf0f1;
            padding-bottom: 15px;
        }
        
        .page-header h3 {
            color: #2c3e50;
            font-size: 22px;
        }
        
        .page-header p {
            color: #7f8c8d;
            font-size: 13px;
            margin-top: 4px;
        }

        /* Banner Alert Sesuai Desain */
        .alert-banner {
            padding: 14px 18px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 14px;
            font-weight: bold;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-banner-error {
            background-color: #fdeaea;
            color: #c0392b;
            border-left: 4px solid #e74c3c;
        }

        .alert-banner-success {
            background-color: #e8f8f5;
            color: #27ae60;
            border-left: 4px solid #2ecc71;
        }

        /* Buttons */
        .btn {
            padding: 10px 18px;
            border-radius: 5px;
            font-size: 14px;
            font-weight: bold;
            text-decoration: none;
            cursor: pointer;
            transition: background 0.3s ease;
            display: inline-block;
            text-align: center;
            border: none;
        }
        .btn-primary {
            background-color: #2ecc71;
            color: white;
        }
        .btn-primary:hover { background-color: #27ae60; }
        
        .btn-edit {
            background-color: #3498db;
            color: white;
            padding: 6px 12px;
            font-size: 12px;
        }
        .btn-edit:hover { background-color: #2980b9; }

        .btn-delete {
            background-color: #e74c3c;
            color: white;
            padding: 6px 12px;
            font-size: 12px;
        }
        .btn-delete:hover { background-color: #c0392b; }

        .btn-back {
            background-color: #95a5a6;
            color: white;
            margin-top: 20px;
        }
        .btn-back:hover { background-color: #7f8c8d; }

        .action-group {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
        }

        .data-table th, .data-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ecf0f1;
            font-size: 13px;
        }
        .data-table th {
            background-color: #f8f9fa;
            color: #2c3e50;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 12px;
            white-space: nowrap;
        }
        .data-table tr:hover {
            background-color: #fcfcfc;
        }

        .text-center {
            text-align: center;
            color: #7f8c8d;
            padding: 30px;
        }

        .table-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 20px;
            flex-wrap: wrap;
            gap: 10px;
        }

        .total-data {
            font-size: 14px;
            color: #7f8c8d;
        }

        .pagination {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            list-style: none;
            gap: 5px;
        }

        .pagination a {
            padding: 8px 12px;
            text-decoration: none;
            background-color: #f8f9fa;
            color: #2c3e50;
            border: 1px solid #ccd1d1;
            border-radius: 4px;
            font-size: 13px;
            transition: 0.3s;
        }

        .pagination a.active {
            background-color: #2ecc71;
            color: white;
            border-color: #2ecc71;
        }

        .pagination a:hover:not(.active) {
            background-color: #ecf0f1;
        }
    </style>
</head>
<!-- BAGIAN BODY: seluruh isi yang tampil di layar. -->
<body>

    <!-- Sidebar Admin -->
    <?php include __DIR__ . "/../TEMPLATE/SideBarAdmin.php"; ?>

    <div class="main-content">
        <!-- Topbar -->
        <div class="topbar">
            <h2>Kelola Penerbit</h2>
            <div style="font-size: 14px; color: #7f8c8d;">Panel Admin</div>
        </div>

        <!-- Main Container -->
        <div class="container">
            <div class="card">
                
                <div class="page-header">
                    <div>
                        <h3>Daftar Penerbit Buku</h3>
                        <p>Kelola data instansi/perusahaan penerbit yang bukunya tersedia di perpustakaan.</p>
                    </div>
                    <div>
                        <!-- CLEAN URL: Tambah Penerbit -->
                        <a href="/Aplikasi_Peminjaman_Buku/admin/tambahPenerbit" class="btn btn-primary">+ Tambah Penerbit</a>
                    </div>
                </div>

                <!-- BANNER NOTIFIKASI -->
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert-banner alert-banner-error">
                        <span>⚠️</span>
                        <!-- htmlspecialchars() mengubah karakter khusus HTML (< > " ') menjadi aman ditampilkan; ini pencegah XSS (penyisipan script lewat data). -->
                        <div><?= htmlspecialchars($_SESSION['error']); ?></div>
                    </div>
                    <?php unset($_SESSION['error']); ?>
                <!-- Akhir kondisi if. -->
                <?php endif; ?>

                <!-- PESAN SUKSES (flash message): tampil bila controller mengisi $_SESSION['success']; langsung dihapus setelah tampil. -->
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert-banner alert-banner-success">
                        <span>✅</span>
                        <div><?= htmlspecialchars($_SESSION['success']); ?></div>
                    </div>
                    <?php unset($_SESSION['success']); ?>
                <!-- Akhir kondisi if. -->
                <?php endif; ?>

                <div class="table-responsive">
                    <!-- TABEL data. -->
                    <table class="data-table">
                        <!-- Judul kolom tabel. -->
                        <thead>
                            <tr>
                                <th style="width: 5%;">No</th>
                                <th style="width: 20%;">Nama Penerbit</th>
                                <th style="width: 25%;">Alamat</th>
                                <th style="width: 15%;">Kota</th>
                                <th style="width: 10%;">Telepon</th>
                                <th style="width: 15%;">Email</th>
                                <th style="width: 10%;">Aksi</th>
                            </tr>
                        </thead>
                        <!-- Isi tabel: satu baris (<tr>) untuk setiap data. -->
                        <tbody>
                            <!-- KONDISI: blok di bawah (sampai else/endif) hanya tampil jika !empty($penerbitPaginated) -->
                            <?php if (!empty($penerbitPaginated)): ?>
                                <?php $no = $offset + 1; ?>
                                <!-- PERULANGAN: blok di bawah (sampai endforeach) diulang untuk setiap elemen $penerbitPaginated; elemen aktif ada di $penerbit. -->
                                <?php foreach ($penerbitPaginated as $penerbit): ?>
                                    <tr>
                                        <?php /* Sintaks echo singkat PHP (tag buka PHP diikuti tanda sama dengan): mencetak nilai PHP langsung ke dalam HTML. */ ?>
                                        <td><?= $no++ ?></td>
                                        <td><strong><?= htmlspecialchars($penerbit['nama_penerbit']) ?></strong></td>
                                        <td><?= htmlspecialchars($penerbit['alamat'] ?? '-') ?></td>
                                        <td><?= htmlspecialchars($penerbit['kota'] ?? '-') ?></td>
                                        <td><?= htmlspecialchars($penerbit['telepon'] ?? '-') ?></td>
                                        <td><?= htmlspecialchars($penerbit['email'] ?? '-') ?></td>
                                        <td>
                                            <div class="action-group">
                                                <!-- CLEAN URL: Edit Penerbit -->
                                                <a href="/Aplikasi_Peminjaman_Buku/admin/editPenerbit?id_penerbit=<?= $penerbit['id_penerbit'] ?>" class="btn btn-edit">Edit</a>
                                                <?php /* Tombol HAPUS: memanggil fungsi JavaScript konfirmasiHapus() (dialog 'Yakin?') sebelum data benar-benar dihapus. */ ?>
                                                <a href="#" onclick="konfirmasiHapus(<?= $penerbit['id_penerbit'] ?>)" class="btn btn-delete">Hapus</a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php /* Akhir perulangan foreach. */ ?>
                                <?php endforeach; ?>
                            <!-- ELSE: blok di bawah tampil jika kondisi di atasnya TIDAK terpenuhi. -->
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center">Belum ada data penerbit.</td>
                                </tr>
                            <!-- Akhir kondisi if. -->
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- KONDISI: blok di bawah (sampai else/endif) hanya tampil jika !empty($daftarPenerbit) -->
                <?php if (!empty($daftarPenerbit)): ?>
                    <div class="table-footer">
                        <p class="total-data">Menampilkan <strong><?= count($penerbitPaginated) ?></strong> dari <strong><?= $total_data ?></strong> total penerbit</p>
                        
                        <!-- KONTROL PAGINATION -->
                        <?php if ($total_pages > 1): ?>
                            <ul class="pagination">
                                <!-- KONDISI: blok di bawah (sampai else/endif) hanya tampil jika $page > 1 -->
                                <?php if ($page > 1): ?>
                                    <li><a href="?page=<?= $page - 1 ?>">« Prev</a></li>
                                <!-- Akhir kondisi if. -->
                                <?php endif; ?>

                                <!-- PERULANGAN for ($i = 1; $i <= $total_pages; $i++): blok di bawah diulang sesuai kondisi ini (misalnya membuat tombol nomor halaman). -->
                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <li>
                                        <a href="?page=<?= $i ?>" class="<?= ($i === $page) ? 'active' : '' ?>">
                                            <?= $i ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>

                                <!-- KONDISI: blok di bawah (sampai else/endif) hanya tampil jika $page < $total_pages -->
                                <?php if ($page < $total_pages): ?>
                                    <li><a href="?page=<?= $page + 1 ?>">Next »</a></li>
                                <!-- Akhir kondisi if. -->
                                <?php endif; ?>
                            </ul>
                        <!-- Akhir kondisi if. -->
                        <?php endif; ?>
                    </div>
                <!-- Akhir kondisi if. -->
                <?php endif; ?>

            </div>

            <!-- CLEAN URL: Kembali ke Daftar Buku -->
            <a href="/Aplikasi_Peminjaman_Buku/admin/daftarBuku" class="btn btn-back">← Kembali ke Daftar Buku</a>
        </div>
    </div>

    <!-- JAVASCRIPT SWEETALERT UNTUK KONFIRMASI HAPUS -->
    <script>
        // FUNGSI konfirmasiHapus(id): menampilkan dialog 'Yakin hapus?'. Jika pengguna menekan Ya, browser dipindah ke controller dengan ?aksi=hapus&id_...=ID sehingga data dihapus.
        function konfirmasiHapus(id) {
            // Tampilkan dialog SweetAlert2 (popup).
            Swal.fire({
                title: 'Apakah Anda yakin?',
                text: "Data penerbit yang dihapus tidak dapat dikembalikan!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#e74c3c',
                cancelButtonColor: '#95a5a6',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                // Jika pengguna menekan tombol konfirmasi (Ya)...
                if (result.isConfirmed) {
                    // Absolute path ke controller hapus
                    window.location.href = "/Aplikasi_Peminjaman_Buku/CONTROLLER/c_penerbit.php?aksi=hapus&id_penerbit=" + id;
                }
            });
        }
    </script>

</body>
</html>