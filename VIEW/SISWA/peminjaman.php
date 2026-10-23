<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Proteksi Akses Siswa
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'siswa') {
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
        $kembali = $_SESSION['last_page_admin'] ?? '/Aplikasi_Peminjaman_Buku/admin/dashboardAdmin';
        header("Location: " . $kembali);
        exit;
    }
    header("Location: /Aplikasi_Peminjaman_Buku/login");
    exit;
}

// Simpan URL lokasi controller aktif saat ini
$_SESSION['last_page_siswa'] = $_SERVER['REQUEST_URI'];

// Ambil ID User aktif dari session login
$id_user = (int)$_SESSION['id_user'];

require_once __DIR__ . "/../../MODEL/m_koneksi.php";
require_once __DIR__ . "/../../MODEL/m_peminjaman.php";

$db = new Koneksi();
$koneksi = $db->getKoneksi();

// Ambil Nama Siswa untuk Topbar
$stmtUser = $koneksi->prepare("SELECT nama_lengkap FROM users WHERE id_user = ?");
$stmtUser->bind_param("i", $id_user);
$stmtUser->execute();
$resUser = $stmtUser->get_result()->fetch_assoc();
$namaSiswa = $resUser['nama_lengkap'] ?? 'Siswa';
$stmtUser->close();

// Inisialisasi Model Peminjaman
$modelPeminjaman = new M_Peminjaman();
$dataPeminjaman = $modelPeminjaman->getPeminjamanByUser($id_user);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Peminjaman Saya - Aplikasi Perpustakaan</title>

    <!-- FontAwesome Icon & Google Fonts -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        /* 1. Reset & Kunci Layar Utama */
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
            background-color: #f4f8fb;
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
        }

        /* Topbar Header */
        .topbar {
            background-color: #ffffff;
            padding: 15px 30px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.02);
            display: flex;
            justify-content: space-between;
            align-items: center;
            z-index: 10;
        }

        .topbar h2 {
            font-size: 18px;
            color: #1e293b;
            font-weight: 700;
        }

        .topbar-profile {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* 3. Container Content */
        .content-container {
            padding: 30px;
            flex: 1;
            overflow-y: auto;
            overflow-x: hidden;
        }

        /* Header Title Block */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }

        .page-header h3 {
            font-size: 22px;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 4px;
        }

        .page-header p {
            color: #64748b;
            font-size: 14px;
        }

        .btn-back {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background-color: #ffffff;
            color: #334155;
            border: 1px solid #cbd5e1;
            border-radius: 30px;
            font-size: 13.5px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s ease;
            box-shadow: 0 2px 5px rgba(0,0,0,0.03);
        }

        .btn-back:hover {
            background-color: #2563eb;
            color: #ffffff;
            border-color: #2563eb;
        }

        /* Alert Styling */
        .alert {
            padding: 14px 18px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-size: 14px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-danger {
            background-color: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        .alert-success {
            background-color: #f0fdf4;
            color: #166534;
            border: 1px solid #bbf7d0;
        }

        /* Card Container */
        .card-custom {
            background: #ffffff;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03);
            border: 1px solid #f1f5f9;
        }

        /* Table Styling */
        .table-responsive {
            width: 100%;
            overflow-x: auto;
        }

        .custom-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
        }

        .custom-table th {
            background-color: #f8fafc;
            color: #475569;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 14px 16px;
            border-bottom: 2px solid #e2e8f0;
            white-space: nowrap;
        }

        .custom-table td {
            padding: 16px;
            border-bottom: 1px solid #f1f5f9;
            font-size: 14px;
            color: #334155;
            vertical-align: middle;
        }

        .custom-table tbody tr {
            transition: background-color 0.2s ease;
        }

        .custom-table tbody tr:hover {
            background-color: #f8fafc;
        }

        /* Badges */
        .badge-count {
            background: #f1f5f9;
            color: #475569;
            border: 1px solid #cbd5e1;
            padding: 4px 10px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
        }

        .badge-status {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
            border-radius: 30px;
            font-size: 12.5px;
            font-weight: 600;
        }

        .status-diajukan { background: #fef3c7; color: #92400e; }
        .status-dipinjam { background: #dbeafe; color: #1e40af; }
        .status-dikembalikan { background: #dcfce7; color: #166534; }
        .status-ditolak { background: #fee2e2; color: #991b1b; }

        /* Detail Button */
        .btn-detail {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 7px 16px;
            background-color: #eff6ff;
            color: #2563eb;
            border: 1px solid #bfdbfe;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s ease;
        }

        .btn-detail:hover {
            background-color: #2563eb;
            color: #ffffff;
            border-color: #2563eb;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 50px 20px;
        }

        .empty-state i {
            font-size: 50px;
            color: #cbd5e1;
            margin-bottom: 15px;
        }

        .empty-state h5 {
            font-size: 18px;
            font-weight: 700;
            color: #334155;
            margin-bottom: 8px;
        }

        .empty-state p {
            color: #64748b;
            font-size: 14px;
            margin-bottom: 20px;
        }

        .btn-primary-custom {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 24px;
            background-color: #2563eb;
            color: #ffffff;
            border-radius: 30px;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            transition: background 0.2s ease;
        }

        .btn-primary-custom:hover {
            background-color: #1d4ed8;
        }
    </style>
</head>

<body>

    <div class="app-wrapper">

        <!-- Sidebar Siswa -->
        <?php include __DIR__ . "/../TEMPLATE/SideBarSiswa.php"; ?>

        <!-- Main Content Area -->
        <div class="main-content">

            <!-- Topbar -->
            <div class="topbar">
                <h2>Aktivitas Peminjaman</h2>
                <div class="topbar-profile">
                    <span style="font-size: 14px; font-weight: 600; color: #475569;"><?= htmlspecialchars($namaSiswa) ?></span>
                    <div style="width: 32px; height: 32px; background: #2563eb; color: #fff; border-radius: 50%; display: flex; justify-content: center; align-items: center; font-weight: bold; font-size: 12px;">
                        <?= strtoupper(substr($namaSiswa, 0, 1)) ?>
                    </div>
                </div>
            </div>

            <div class="content-container">

                <!-- Header Judul -->
                <div class="page-header">
                    <div>
                        <h3><i class="fa-solid fa-bookmark text-primary me-2"></i> Peminjaman Saya</h3>
                        <p>Pantau status pengajuan serta riwayat transaksi buku perpustakaan Anda.</p>
                    </div>
                    <!-- CLEAN URL: Kembali ke Katalog Buku -->
                    <a href="/Aplikasi_Peminjaman_Buku/siswa/daftarBuku" class="btn-back">
                        <i class="fa-solid fa-arrow-left"></i> Kembali ke Katalog Buku
                    </a>
                </div>

                <!-- Alert Session -->
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger">
                        <i class="fa-solid fa-circle-exclamation"></i>
                        <div><?= htmlspecialchars($_SESSION['error']) ?></div>
                    </div>
                    <?php unset($_SESSION['error']); ?>
                <?php endif; ?>

                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success">
                        <i class="fa-solid fa-circle-check"></i>
                        <div><?= htmlspecialchars($_SESSION['success']) ?></div>
                    </div>
                    <?php unset($_SESSION['success']); ?>
                <?php endif; ?>

                <!-- Table Card Container -->
                <div class="card-custom">
                    <?php if (!empty($dataPeminjaman)): ?>
                        <div class="table-responsive">
                            <table class="custom-table">
                                <thead>
                                    <tr>
                                        <th style="width: 50px; text-align: center;">No</th>
                                        <th>ID Transaksi</th>
                                        <th>NIS / NIP</th>
                                        <th>Nama Peminjam</th>
                                        <th>Tanggal Pinjam</th>
                                        <th style="text-align: center;">Jumlah Jenis</th>
                                        <th style="text-align: center;">Total Unit</th>
                                        <th style="text-align: center;">Status</th>
                                        <th style="text-align: center; width: 110px;">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $no = 1;
                                    foreach ($dataPeminjaman as $row): ?>
                                        <tr>
                                            <td style="text-align: center; font-weight: 700; color: #94a3b8;"><?= $no++ ?></td>
                                            <td>
                                                <strong style="color: #2563eb;">#PMJ-<?= htmlspecialchars($row['id_peminjaman']) ?></strong>
                                            </td>
                                            <td><?= htmlspecialchars($row['nis']) ?></td>
                                            <td><strong style="color: #1e293b;"><?= htmlspecialchars($row['nama_lengkap']) ?></strong></td>
                                            <td>
                                                <i class="fa-regular fa-calendar text-muted me-1"></i>
                                                <?= date('d M Y', strtotime($row['tanggal_pinjam'])) ?>
                                            </td>
                                            <td style="text-align: center;">
                                                <span class="badge-count"><?= htmlspecialchars($row['jumlah_jenis_buku']) ?> Jenis</span>
                                            </td>
                                            <td style="text-align: center;">
                                                <span class="badge-count"><?= htmlspecialchars($row['total_buku']) ?> Bks</span>
                                            </td>
                                            <td style="text-align: center;">
                                                <?php
                                                $status = $row['status'];
                                                $badgeClass = 'status-diajukan';
                                                $icon = 'fa-clock';

                                                if (strtolower($status) === 'diajukan') {
                                                    $badgeClass = 'status-diajukan';
                                                    $icon = 'fa-hourglass-half';
                                                } elseif (strtolower($status) === 'dipinjam') {
                                                    $badgeClass = 'status-dipinjam';
                                                    $icon = 'fa-book-reader';
                                                } elseif (strtolower($status) === 'dikembalikan') {
                                                    $badgeClass = 'status-dikembalikan';
                                                    $icon = 'fa-circle-check';
                                                } elseif (strtolower($status) === 'ditolak') {
                                                    $badgeClass = 'status-ditolak';
                                                    $icon = 'fa-circle-xmark';
                                                }
                                                ?>
                                                <span class="badge-status <?= $badgeClass ?>">
                                                    <i class="fa-solid <?= $icon ?>"></i>
                                                    <?= htmlspecialchars($status) ?>
                                                </span>
                                            </td>
                                            <td style="text-align: center;">
                                                <!-- CLEAN URL: Detail Peminjaman -->
                                                <a href="/Aplikasi_Peminjaman_Buku/siswa/detailPeminjaman?id_peminjaman=<?= $row['id_peminjaman'] ?>" class="btn-detail">
                                                    <i class="fa-solid fa-eye"></i> Detail
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <!-- State jika belum ada data peminjaman -->
                        <div class="empty-state">
                            <i class="fa-solid fa-folder-open"></i>
                            <h5>Belum Ada Riwayat Peminjaman</h5>
                            <p>Anda belum mengajukan peminjaman buku apa pun saat ini.</p>
                            <!-- CLEAN URL: Pinjam Buku Sekarang -->
                            <a href="/Aplikasi_Peminjaman_Buku/siswa/daftarBuku" class="btn-primary-custom">
                                <i class="fa-solid fa-plus"></i> Pinjam Buku Sekarang
                            </a>
                        </div>
                    <?php endif; ?>
                </div>

            </div>

        </div>
    </div>

</body>
</html>