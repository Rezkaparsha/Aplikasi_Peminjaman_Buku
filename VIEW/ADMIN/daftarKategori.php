<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Proteksi Akses Admin Terpusat
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'admin') {
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'siswa') {
        $kembali = $_SESSION['last_page_siswa'] ?? '/Aplikasi_Peminjaman_Buku/siswa/dashboardSiswa';
        header("Location: " . $kembali);
        exit;
    }
    header("Location: /Aplikasi_Peminjaman_Buku/login");
    exit;
}

// 2. Simpan URL lokasi halaman view ini
$_SESSION['last_page_admin'] = $_SERVER['REQUEST_URI'];

// 3. Panggil Model
require_once __DIR__ . "/../../MODEL/m_kategori.php";
$kategoriModel = new Kategori();
$daftarKategori = $kategoriModel->getAll();

// ==========================================
// LOGIKA PAGINATION (10 Data per Halaman)
// ==========================================
$limit = 10;
$total_data = count($daftarKategori);
$total_pages = ceil($total_data / $limit);

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
if ($page > $total_pages && $total_pages > 0) $page = $total_pages;

$offset = ($page - 1) * $limit;

// Potong array data sesuai halaman aktif
$kategoriPaginated = array_slice($daftarKategori, $offset, $limit);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Kategori - Perpustakaan Online</title>
    <!-- Google Fonts: Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
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

        .topbar {
            background-color: #fff;
            padding: 15px 30px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .topbar h2 {
            font-size: 20px;
            color: #2c3e50;
        }

        .container {
            padding: 30px;
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

        .btn {
            padding: 10px 18px;
            border-radius: 5px;
            font-size: 14px;
            font-weight: bold;
            text-decoration: none;
            cursor: pointer;
            transition: background 0.3s ease;
            display: inline-block;
            border: none;
        }

        .btn-primary {
            background-color: #2ecc71;
            color: white;
        }

        .btn-primary:hover {
            background-color: #27ae60;
        }

        .btn-edit {
            background-color: #3498db;
            color: white;
            padding: 6px 12px;
            font-size: 13px;
        }

        .btn-edit:hover {
            background-color: #2980b9;
        }

        .btn-delete {
            background-color: #e74c3c;
            color: white;
            padding: 6px 12px;
            font-size: 13px;
        }

        .btn-delete:hover {
            background-color: #c0392b;
        }

        .btn-back {
            background-color: #95a5a6;
            color: white;
            margin-top: 20px;
        }

        .btn-back:hover {
            background-color: #7f8c8d;
        }

        .table-responsive {
            width: 100%;
            overflow-x: auto;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        .data-table th,
        .data-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ecf0f1;
            font-size: 14px;
        }

        .data-table th {
            background-color: #f8f9fa;
            color: #2c3e50;
            font-weight: 600;
        }

        .data-table tr:hover {
            background-color: #fcfcfc;
        }

        .text-center {
            text-align: center;
            color: #7f8c8d;
            padding: 20px;
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

<body>

    <!-- Sidebar Admin -->
    <?php include __DIR__ . "/../TEMPLATE/SideBarAdmin.php"; ?>

    <div class="main-content">
        <!-- Topbar -->
        <div class="topbar">
            <h2>Kelola Kategori</h2>
            <div style="font-size: 14px; color: #7f8c8d;">Panel Admin</div>
        </div>

        <!-- Main Container -->
        <div class="container">
            <div class="card">

                <div class="page-header">
                    <div>
                        <h3>Daftar Kategori Buku</h3>
                        <p>Kelola kategori buku yang tersedia di perpustakaan dengan mudah.</p>
                    </div>
                    <div>
                        <!-- CLEAN URL: Tambah Kategori -->
                        <a href="/Aplikasi_Peminjaman_Buku/admin/tambahKategori" class="btn btn-primary">+ Tambah Kategori</a>
                    </div>
                </div>

                <!-- BANNER NOTIFIKASI -->
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert-banner alert-banner-error">
                        <span>⚠️</span>
                        <div><?= htmlspecialchars($_SESSION['error']); ?></div>
                    </div>
                    <?php unset($_SESSION['error']); ?>
                <?php endif; ?>

                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert-banner alert-banner-success">
                        <span>✅</span>
                        <div><?= htmlspecialchars($_SESSION['success']); ?></div>
                    </div>
                    <?php unset($_SESSION['success']); ?>
                <?php endif; ?>

                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th style="width: 5%;">No</th>
                                <th style="width: 25%;">Nama Kategori</th>
                                <th style="width: 35%;">Deskripsi</th>
                                <th style="width: 15%;">Status</th>
                                <th style="width: 20%;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($kategoriPaginated)): ?>
                                <?php $no = $offset + 1; ?>
                                <?php foreach ($kategoriPaginated as $kategori): ?>
                                    <tr>
                                        <td><?= $no++ ?></td>
                                        <td><strong><?= htmlspecialchars($kategori['nama_kategori']) ?></strong></td>
                                        <td><?= htmlspecialchars($kategori['deskripsi'] ?? '-') ?></td>
                                        <td><?= htmlspecialchars($kategori['status'] ?? 'Aktif') ?></td>
                                        <td>
                                            <!-- CLEAN URL: Edit Kategori -->
                                            <a href="/Aplikasi_Peminjaman_Buku/admin/editKategori?id_kategori=<?= $kategori['id_kategori'] ?>" class="btn btn-edit">Edit</a>
                                            <!-- Tombol Hapus memanggil JS SweetAlert -->
                                            <a href="#" onclick="konfirmasiHapus(<?= $kategori['id_kategori'] ?>)" class="btn btn-delete">Hapus</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center">Belum ada data kategori.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <?php if (!empty($daftarKategori)): ?>
                    <div class="table-footer">
                        <p class="total-data">Menampilkan <strong><?= count($kategoriPaginated) ?></strong> dari <strong><?= $total_data ?></strong> total kategori</p>

                        <!-- KONTROL PAGINATION -->
                        <?php if ($total_pages > 1): ?>
                            <ul class="pagination">
                                <?php if ($page > 1): ?>
                                    <li><a href="?page=<?= $page - 1 ?>">« Prev</a></li>
                                <?php endif; ?>

                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <li>
                                        <a href="?page=<?= $i ?>" class="<?= ($i === $page) ? 'active' : '' ?>">
                                            <?= $i ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>

                                <?php if ($page < $total_pages): ?>
                                    <li><a href="?page=<?= $page + 1 ?>">Next »</a></li>
                                <?php endif; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

            </div>

            <!-- CLEAN URL: Kembali ke Dashboard -->
            <a href="/Aplikasi_Peminjaman_Buku/admin/dashboardAdmin" class="btn btn-back">← Kembali ke Dashboard</a>
        </div>
    </div>

    <!-- JAVASCRIPT SWEETALERT UNTUK KONFIRMASI HAPUS -->
    <script>
        function konfirmasiHapus(id) {
            Swal.fire({
                title: 'Apakah Anda yakin?',
                text: "Data kategori yang dihapus tidak dapat dikembalikan!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#e74c3c',
                cancelButtonColor: '#95a5a6',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Absolute path ke controller hapus agar tidak error dari mana pun dipanggil
                    window.location.href = "/Aplikasi_Peminjaman_Buku/CONTROLLER/c_kategori.php?aksi=hapus&id_kategori=" + id;
                }
            });
        }
    </script>

</body>

</html>