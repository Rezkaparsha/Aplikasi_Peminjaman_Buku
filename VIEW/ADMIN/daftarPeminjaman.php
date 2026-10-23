<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Proteksi Akses Admin Terpusat
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'admin') {
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'siswa') {
        $kembali = $_SESSION['last_page_siswa'] ?? '/Aplikasi_Peminjaman_Buku/siswa/dashboardSiswa';
        header("Location: " . $kembali);
        exit;
    }
    header("Location: /Aplikasi_Peminjaman_Buku/login");
    exit;
}

// Simpan URL halaman ini hanya jika user terbukti admin
$_SESSION['last_page_admin'] = $_SERVER['REQUEST_URI'];

// Panggil Model Peminjaman
require_once __DIR__ . "/../../MODEL/m_peminjaman.php";
$peminjamanModel = new M_Peminjaman();
$dataPeminjaman = $peminjamanModel->getSemuaPeminjaman();

// ==========================================
// LOGIKA PAGINATION (10 Data per Halaman)
// ==========================================
$limit = 10;
$total_data = count($dataPeminjaman);
$total_pages = ceil($total_data / $limit);

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
if ($page > $total_pages && $total_pages > 0) $page = $total_pages;

$offset = ($page - 1) * $limit;

// Potong array data sesuai halaman aktif
$dataPaginated = array_slice($dataPeminjaman, $offset, $limit);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Peminjaman - Perpustakaan Online</title>
        <!-- Google Fonts: Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
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

        /* Alerts */
        .alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-weight: 500;
        }

        .alert-success {
            background-color: #e8f8f5;
            color: #1abc9c;
            border-left: 4px solid #1abc9c;
        }

        .alert-error {
            background-color: #fdeaea;
            color: #e74c3c;
            border-left: 4px solid #e74c3c;
        }

        /* Table Styling */
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

        .data-table th,
        .data-table td {
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

        /* Status Badges */
        .badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: bold;
            color: white;
            text-transform: uppercase;
            display: inline-block;
            text-align: center;
            min-width: 80px;
        }

        .badge-warning { background-color: #f39c12; } /* Diajukan */
        .badge-primary { background-color: #3498db; }
        .badge-success { background-color: #2ecc71; } /* Dipinjam */
        .badge-danger { background-color: #e74c3c; }  /* Ditolak */
        .badge-default { background-color: #95a5a6; } /* Dikembalikan */

        /* Buttons */
        .btn {
            padding: 6px 12px;
            font-size: 12px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: bold;
            transition: 0.3s;
            display: inline-block;
            text-align: center;
        }
        
        .btn-info {
            background-color: #3498db;
            color: white;
            border: none;
        }

        .btn-info:hover {
            background-color: #2980b9;
        }

        /* Pagination Styling */
        .pagination {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            list-style: none;
            margin-top: 20px;
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
            background-color: #3498db;
            color: white;
            border-color: #3498db;
        }

        .pagination a:hover:not(.active) {
            background-color: #ecf0f1;
        }

        .text-center {
            text-align: center;
            color: #7f8c8d;
            padding: 30px;
        }
    </style>
</head>

<body>

    <!-- Sidebar Admin -->
    <?php include __DIR__ . "/../TEMPLATE/SideBarAdmin.php"; ?>

    <div class="main-content">
        <!-- Topbar -->
        <div class="topbar">
            <h2>Kelola Peminjaman</h2>
            <div style="font-size: 14px; color: #7f8c8d;">Panel Admin</div>
        </div>

        <!-- Main Container -->
        <div class="container">

            <!-- Notifikasi Pesan -->
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success">
                    <?= htmlspecialchars($_SESSION['success']) ?>
                </div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-error">
                    <?= htmlspecialchars($_SESSION['error']) ?>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            <div class="card">
                <div class="page-header">
                    <div>
                        <h3>Daftar Transaksi Peminjaman</h3>
                        <p>Tinjau dan kelola pengajuan peminjaman buku dari siswa.</p>
                    </div>
                </div>

                <?php if (!empty($dataPeminjaman)): ?>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>NO</th>
                                    <th>ID TRX</th>
                                    <th>NIS & PEMINJAM</th>
                                    <th>TGL PINJAM</th>
                                    <th>JML JENIS</th>
                                    <th>TOTAL BUKU</th>
                                    <th>STATUS</th>
                                    <th>AKSI</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($dataPaginated)): ?>
                                    <?php $no = $offset + 1; ?> 
                                    
                                    <?php foreach ($dataPaginated as $row): ?>
                                        <tr>
                                            <td><?= $no++ ?></td>
                                            <td style="font-weight: bold; color: #3498db;">#<?= htmlspecialchars($row['id_peminjaman']) ?></td>
                                            <td>
                                                <strong><?= htmlspecialchars($row['nama_lengkap']) ?></strong><br>
                                                <span style="font-size: 11px; color: #7f8c8d;"><?= htmlspecialchars($row['nis']) ?></span>
                                            </td>
                                            <td><?= date('d M Y', strtotime($row['tanggal_pinjam'])) ?></td>
                                            <td><?= htmlspecialchars($row['jumlah_jenis_buku'] ?? '1') ?> Jenis</td>
                                            <td><?= htmlspecialchars($row['total_buku']) ?> Buku</td>
                                            <td>
                                                <?php
                                                    $status = strtolower($row['status']);
                                                    $badgeClass = 'badge-warning';
                                                    if ($status === 'dipinjam') $badgeClass = 'badge-success';
                                                    if ($status === 'dikembalikan') $badgeClass = 'badge-default';
                                                    if ($status === 'ditolak') $badgeClass = 'badge-danger';
                                                ?>
                                                <span class="badge <?= $badgeClass ?>"><?= htmlspecialchars($row['status']) ?></span>
                                            </td>
                                            <td>
                                                <!-- CLEAN URL: Detail Peminjaman -->
                                                <a href="/Aplikasi_Peminjaman_Buku/admin/detailPeminjaman?id_peminjaman=<?= $row['id_peminjaman'] ?>" class="btn btn-info">Detail</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- KONTROL PAGINATION -->
                    <?php if ($total_pages > 1): ?>
                        <ul class="pagination">
                            <!-- Tombol Prev -->
                            <?php if ($page > 1): ?>
                                <li><a href="?page=<?= $page - 1 ?>">« Prev</a></li>
                            <?php endif; ?>

                            <!-- Angka Halaman -->
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li>
                                    <a href="?page=<?= $i ?>" class="<?= ($i === $page) ? 'active' : '' ?>">
                                        <?= $i ?>
                                    </a>
                                </li>
                            <?php endfor; ?>

                            <!-- Tombol Next -->
                            <?php if ($page < $total_pages): ?>
                                <li><a href="?page=<?= $page + 1 ?>">Next »</a></li>
                            <?php endif; ?>
                        </ul>
                    <?php endif; ?>

                <?php else: ?>
                    <div class="text-center">
                        <span style="font-size: 40px; display: block; margin-bottom: 10px;">📋</span>
                        Belum ada pengajuan peminjaman saat ini.
                    </div>
                <?php endif; ?>

            </div>
        </div>
    </div>

</body>
</html>