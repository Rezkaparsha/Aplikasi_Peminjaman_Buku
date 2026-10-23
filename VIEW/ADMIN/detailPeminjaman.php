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

$_SESSION['last_page_admin'] = $_SERVER['REQUEST_URI'];

// 2. Tangkap ID Peminjaman dari URL
$idPeminjaman = (int)($_GET['id_peminjaman'] ?? 0);

if ($idPeminjaman <= 0) {
    $_SESSION['error'] = "ID Peminjaman tidak valid.";
    // Clean URL: Redirect ke daftar Peminjaman
    header("Location: /Aplikasi_Peminjaman_Buku/admin/daftarPeminjaman");
    exit;
}

// 3. Panggil Model Langsung
require_once __DIR__ . "/../../MODEL/m_peminjaman.php";
$peminjamanModel = new M_Peminjaman();

$dataPeminjaman   = $peminjamanModel->getPeminjamanById($idPeminjaman);
$detailPeminjaman = $peminjamanModel->getDetailPeminjaman($idPeminjaman);

if (!$dataPeminjaman) {
    $_SESSION['error'] = "Data peminjaman tidak ditemukan.";
    // Clean URL: Redirect ke daftar Peminjaman
    header("Location: /Aplikasi_Peminjaman_Buku/admin/daftarPeminjaman");
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Peminjaman #<?= htmlspecialchars($idPeminjaman) ?> - Panel Admin</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: #f4f7f6;
            color: #333;
            display: flex;
            min-height: 100vh;
        }

        .main-content {
            flex: 1;
            display: flex;
            flex-direction: column;
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
        }

        .card {
            background: #ffffff;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.05);
            margin-bottom: 25px;
        }

        .card-header {
            border-bottom: 2px solid #ecf0f1;
            padding-bottom: 12px;
            margin-bottom: 20px;
        }

        .card-header h3 {
            color: #2c3e50;
            font-size: 18px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }

        .info-box p {
            color: #7f8c8d;
            font-size: 13px;
            margin-bottom: 5px;
        }

        .info-box h4 {
            color: #2c3e50;
            font-size: 16px;
        }

        .alert-box {
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .alert-success {
            background: #e8f8f5;
            color: #1abc9c;
            border: 1px solid #a3e4d7;
        }

        .alert-danger {
            background: #fdeaea;
            color: #e74c3c;
            border: 1px solid #f5b7b1;
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
            vertical-align: middle;
        }

        .data-table th {
            background-color: #f8f9fa;
            color: #2c3e50;
            font-weight: 600;
        }

        .cover-img {
            width: 50px;
            height: 70px;
            object-fit: cover;
            border-radius: 4px;
            border: 1px solid #ddd;
        }

        .no-cover {
            width: 50px;
            height: 70px;
            background: #e2e8f0;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            color: #64748b;
            text-align: center;
        }

        .action-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }

        .action-card {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            border: 1px solid #e0e0e0;
        }

        .action-card h4 {
            margin-bottom: 15px;
            font-size: 15px;
            color: #2c3e50;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
            margin-bottom: 15px;
        }

        .form-group label {
            font-weight: 600;
            font-size: 13px;
            color: #34495e;
        }

        .form-control {
            padding: 9px 12px;
            border: 1px solid #ccd1d1;
            border-radius: 5px;
            font-size: 14px;
            width: 100%;
        }

        .form-control:focus {
            outline: none;
            border-color: #3498db;
        }

        .btn {
            padding: 10px 18px;
            border: none;
            border-radius: 5px;
            font-size: 14px;
            font-weight: bold;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }

        .btn-success { background-color: #2ecc71; color: white; }
        .btn-success:hover { background-color: #27ae60; }
        .btn-primary { background-color: #3498db; color: white; }
        .btn-primary:hover { background-color: #2980b9; }
        .btn-danger { background-color: #e74c3c; color: white; }
        .btn-danger:hover { background-color: #c0392b; }

        .btn-back {
            background-color: #95a5a6;
            color: white;
            margin-top: 10px;
        }
        .btn-back:hover { background-color: #7f8c8d; }

        .badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            color: white;
            text-transform: uppercase;
            display: inline-block;
        }
        .badge-warning { background-color: #f39c12; }
        .badge-info { background-color: #3498db; }
        .badge-success { background-color: #2ecc71; }
        .badge-danger { background-color: #e74c3c; }

        .input-group {
            display: flex;
            align-items: center;
        }
        .input-group-text {
            background-color: #eee;
            padding: 9px 12px;
            border: 1px solid #ccd1d1;
            border-right: none;
            border-radius: 5px 0 0 5px;
            font-size: 13px;
            color: #555;
        }
        .input-group .form-control {
            border-radius: 0 5px 5px 0;
        }
    </style>
</head>

<body>

    <!-- Sidebar Admin -->
    <?php include __DIR__ . "/../TEMPLATE/SideBarAdmin.php"; ?>

    <div class="main-content">
        <div class="topbar">
            <h2>Detail Peminjaman Buku</h2>
            <div style="font-size: 14px; color: #7f8c8d;">Panel Admin</div>
        </div>

        <div class="container">

            <!-- NOTIFIKASI -->
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert-box alert-success">
                    <?= htmlspecialchars($_SESSION['success']) ?>
                </div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert-box alert-danger">
                    <?= htmlspecialchars($_SESSION['error']) ?>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            <!-- INFORMASI UTAMA TRANSAKSI -->
            <div class="card">
                <div class="card-header">
                    <h3>Informasi Transaksi #<?= htmlspecialchars($dataPeminjaman['id_peminjaman']) ?></h3>
                </div>
                <div class="info-grid">
                    <div class="info-box">
                        <p>Status Peminjaman</p>
                        <?php
                        $status = strtolower($dataPeminjaman['status']);
                        $badgeClass = 'badge-info';
                        if ($status === 'diajukan') $badgeClass = 'badge-warning';
                        elseif ($status === 'dipinjam') $badgeClass = 'badge-info';
                        elseif ($status === 'dikembalikan') $badgeClass = 'badge-success';
                        elseif ($status === 'ditolak') $badgeClass = 'badge-danger';
                        ?>
                        <h4><span class="badge <?= $badgeClass ?>"><?= htmlspecialchars($dataPeminjaman['status']) ?></span></h4>
                    </div>
                    <div class="info-box">
                        <p>Nama Peminjam</p>
                        <h4><?= htmlspecialchars($dataPeminjaman['nama_lengkap']) ?></h4>
                    </div>
                    <div class="info-box">
                        <p>NIS Siswa</p>
                        <h4><?= htmlspecialchars($dataPeminjaman['nis']) ?></h4>
                    </div>
                    <div class="info-box">
                        <p>Tanggal Pengajuan</p>
                        <h4><?= !empty($dataPeminjaman['tanggal_pinjam']) ? date('d M Y', strtotime($dataPeminjaman['tanggal_pinjam'])) : '-' ?></h4>
                    </div>
                </div>

                <!-- ALASAN PENOLAKAN JIKA STATUS DITOLAK -->
                <?php if ($dataPeminjaman['status'] === 'Ditolak'): ?>
                    <div class="alert-box alert-danger" style="margin-top: 20px; margin-bottom: 0;">
                        <strong>Alasan Penolakan:</strong><br>
                        <?= htmlspecialchars($dataPeminjaman['alasan_penolakan'] ?? 'Tidak ada alasan yang dicantumkan.') ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- TABEL DAFTAR BUKU YANG DIPINJAM -->
            <div class="card">
                <div class="card-header">
                    <h3>Daftar Buku yang Dipinjam</h3>
                </div>

                <?php if ($dataPeminjaman['status'] === 'Dipinjam'): ?>
                    <!-- FORM PROSES PENGEMBALIAN BUKU -->
                    <form action="/Aplikasi_Peminjaman_Buku/CONTROLLER/c_peminjaman.php?aksi=prosesKembali" method="POST">
                        <input type="hidden" name="id_peminjaman" value="<?= htmlspecialchars($dataPeminjaman['id_peminjaman']) ?>">

                        <div class="table-responsive">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th style="width: 50px;">No</th>
                                        <th style="width: 80px;">Cover</th>
                                        <th>Judul Buku</th>
                                        <th style="width: 90px;">Jumlah</th>
                                        <th style="width: 150px;">Target Pengembalian</th>
                                        <th style="width: 180px;">Kondisi Buku</th>
                                        <th style="width: 200px;">Denda Tambahan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($detailPeminjaman as $no => $buku): ?>
                                        <tr>
                                            <td><?= $no + 1 ?></td>
                                            <td>
                                                <?php if (!empty($buku['cover'])): ?>
                                                    <img src="/Aplikasi_Peminjaman_Buku/ASSETS/COVER/<?= htmlspecialchars($buku['cover']) ?>" class="cover-img" alt="Cover">
                                                <?php else: ?>
                                                    <div class="no-cover">No Cover</div>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <strong><?= htmlspecialchars($buku['judul_buku']) ?></strong><br>
                                                <small style="color: #7f8c8d;">Harga: Rp <?= number_format($buku['harga_buku'] ?? 0, 0, ',', '.') ?></small>
                                            </td>
                                            <td><?= htmlspecialchars($buku['jumlah_buku']) ?> Bks</td>
                                            <td><?= !empty($buku['tanggal_pengembalian']) ? date('d M Y', strtotime($buku['tanggal_pengembalian'])) : '-' ?></td>
                                            <td>
                                                <select name="kondisi[<?= $buku['id_detail'] ?>]" class="form-control">
                                                    <option value="Normal">Normal</option>
                                                    <option value="Rusak">Rusak</option>
                                                    <option value="Hilang">Hilang</option>
                                                </select>
                                            </td>
                                            <td>
                                                <div class="input-group">
                                                    <span class="input-group-text">Rp</span>
                                                    <input type="number" name="denda_tambahan[<?= $buku['id_detail'] ?>]" class="form-control" value="0" min="0" placeholder="0">
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <div style="margin-top: 20px; text-align: right;">
                            <button type="submit" class="btn btn-primary" onclick="return confirm('Apakah Anda yakin ingin menyelesaikan pengembalian transaksi ini?');">
                                Selesaikan & Pengembalian Buku
                            </button>
                        </div>
                    </form>

                <?php else: ?>
                    <!-- TABEL BUKU STANDAR (SITUASI DIAJUKAN, DIKEMBALIKAN, ATAU DITOLAK) -->
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th style="width: 50px;">No</th>
                                    <th style="width: 80px;">Cover</th>
                                    <th>Judul Buku</th>
                                    <th style="width: 100px;">Jumlah</th>
                                    <th style="width: 180px;">Target Pengembalian</th>
                                    <th style="width: 150px;">Status Item</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($detailPeminjaman as $no => $buku): ?>
                                    <tr>
                                        <td><?= $no + 1 ?></td>
                                        <td>
                                            <?php if (!empty($buku['cover'])): ?>
                                                <img src="/Aplikasi_Peminjaman_Buku/ASSETS/COVER/<?= htmlspecialchars($buku['cover']) ?>" class="cover-img" alt="Cover">
                                            <?php else: ?>
                                                <div class="no-cover">No Cover</div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <strong><?= htmlspecialchars($buku['judul_buku']) ?></strong><br>
                                            <small style="color: #7f8c8d;">Harga: Rp <?= number_format($buku['harga_buku'] ?? 0, 0, ',', '.') ?></small>
                                        </td>
                                        <td><?= htmlspecialchars($buku['jumlah_buku']) ?> Bks</td>
                                        <td><?= !empty($buku['tanggal_pengembalian']) ? date('d M Y', strtotime($buku['tanggal_pengembalian'])) : '-' ?></td>
                                        <td>
                                            <?php
                                            $stDetail = strtolower($buku['status_detail']);
                                            $bdgDetail = 'badge-info';
                                            if ($stDetail === 'diajukan') $bdgDetail = 'badge-warning';
                                            elseif ($stDetail === 'dipinjam') $bdgDetail = 'badge-info';
                                            elseif ($stDetail === 'dikembalikan') $bdgDetail = 'badge-success';
                                            elseif ($stDetail === 'ditolak') $bdgDetail = 'badge-danger';
                                            ?>
                                            <span class="badge <?= $bdgDetail ?>"><?= htmlspecialchars($buku['status_detail']) ?></span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <!-- FORM PERSETUJUAN / PENOLAKAN ADMIN (MUNCUL JIKA STATUS MASIH 'Diajukan') -->
            <?php if ($dataPeminjaman['status'] === 'Diajukan'): ?>
                <div class="card" style="border-left: 4px solid #f39c12;">
                    <div class="card-header">
                        <h3>Aksi / Validasi Admin</h3>
                    </div>
                    <div class="action-grid">
                        <!-- Form Setujui -->
                        <div class="action-card">
                            <h4>Persetujuan Peminjaman</h4>
                            <form action="/Aplikasi_Peminjaman_Buku/CONTROLLER/c_peminjaman.php?aksi=setujui" method="POST">
                                <input type="hidden" name="id_peminjaman" value="<?= htmlspecialchars($dataPeminjaman['id_peminjaman']) ?>">
                                <div class="form-group">
                                    <label>Target Tanggal Pengembalian</label>
                                    <input type="date" name="tanggal_pengembalian" class="form-control" required>
                                </div>
                                <button type="submit" class="btn btn-success" style="width: 100%;" onclick="return confirm('Setujui peminjaman ini?')">✔ Setujui Peminjaman</button>
                            </form>
                        </div>

                        <!-- Form Tolak -->
                        <div class="action-card">
                            <h4>Penolakan Pengajuan</h4>
                            <form action="/Aplikasi_Peminjaman_Buku/CONTROLLER/c_peminjaman.php?aksi=tolak" method="POST">
                                <input type="hidden" name="id_peminjaman" value="<?= htmlspecialchars($dataPeminjaman['id_peminjaman']) ?>">
                                <div class="form-group">
                                    <label>Alasan Penolakan</label>
                                    <textarea name="alasan_penolakan" class="form-control" rows="3" placeholder="Berikan alasan kenapa ditolak..." required></textarea>
                                </div>
                                <button type="submit" class="btn btn-danger" style="width: 100%;" onclick="return confirm('Yakin ingin menolak pengajuan ini?')">✖ Tolak Pengajuan</button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- CLEAN URL: Kembali ke Daftar Peminjaman -->
            <a href="/Aplikasi_Peminjaman_Buku/admin/daftarPeminjaman" class="btn btn-back">← Kembali ke Daftar Peminjaman</a>

        </div>
    </div>

</body>

</html>