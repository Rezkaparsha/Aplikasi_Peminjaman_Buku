<?php
// Halaman: Histori transaksi
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

// 3. Panggil Model Langsung di View
require_once __DIR__ . "/../../MODEL/m_peminjaman.php";
$peminjamanModel = new M_Peminjaman();
$dataHistori = $peminjamanModel->getHistoriTransaksi();

// Inisialisasi variabel denda
$totalSemuaDenda = 0;
$namaPetugas = $_SESSION['nama_lengkap'] ?? 'Petugas Perpustakaan';
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Histori Transaksi - Panel Admin</title>

    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        html,
        body {
            height: 100vh;
            width: 100vw;
            margin: 0;
            padding: 0;
            overflow: hidden;
            background-color: #f4f7f6;
        }

        body {
            display: flex;
            color: #333;
        }

        .main-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            height: 100vh;
            overflow: hidden;
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

        .table-responsive {
            width: 100%;
            overflow-x: auto;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            min-width: 1150px;
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
            margin-bottom: 20px;
            border-bottom: 2px solid #ecf0f1;
            padding-bottom: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
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

        .btn-cetak-rekap {
            background-color: #1e293b;
            color: #ffffff;
            padding: 10px 18px;
            border: none;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: background-color 0.2s;
        }

        .btn-cetak-rekap:hover {
            background-color: #0f172a;
        }

        .btn-bukti {
            background-color: #2563eb;
            color: #ffffff;
            padding: 6px 12px;
            border-radius: 5px;
            font-size: 12px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            white-space: nowrap;
            transition: background-color 0.2s;
        }

        .btn-bukti:hover {
            background-color: #1d4ed8;
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

        .badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: bold;
            color: white;
            display: inline-block;
            white-space: nowrap;
        }

        .badge-success {
            background-color: #2ecc71;
        }

        .badge-danger {
            background-color: #e74c3c;
        }

        .badge-warning {
            background-color: #f39c12;
        }

        .badge-orange {
            background-color: #e67e22;
        }

        .total-denda-box {
            margin-top: 20px;
            padding: 15px;
            background: #fdfefe;
            border: 1px solid #e5e8e8;
            border-radius: 5px;
            text-align: right;
        }

        .print-only {
            display: none;
        }
    </style>
</head>

<body>

    <!-- Sidebar Admin -->
    <?php include __DIR__ . "/../TEMPLATE/SideBarAdmin.php"; ?>

    <div class="main-content">
        <div class="topbar no-print">
            <h2>Laporan &amp; Histori Transaksi</h2>
            <div style="font-size: 14px; color: #7f8c8d;">Panel Admin</div>
        </div>

        <div class="container">
            <div class="card">

                <!-- Kop Surat khusus saat Cetak Rekap Laporan -->
                <div class="print-only kop-print">
                    <h2>Perpustakaan SMK Sangkuriang 1 Cimahi</h2>
                    <h3>Yayasan Dayang Sumbi Jaya Lestari</h3>
                    <p>Laporan Rekapitulasi Pengembalian Buku &amp; Denda</p>
                </div>

                <div class="page-header no-print">
                    <div>
                        <h3>Rekap Pengembalian Buku &amp; Denda</h3>
                        <p>Catatan fisik seluruh transaksi peminjaman buku yang telah selesai diproses.</p>
                    </div>
                    <?php if (!empty($dataHistori)): ?>
                        <button type="button" onclick="window.print()" class="btn-cetak-rekap">
                            🖨️ Cetak Rekap Laporan
                        </button>
                    <?php endif; ?>
                </div>

                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>ID PMJ</th>
                                <th>Peminjam (NIS/NIP)</th>
                                <th>Kelas</th>
                                <th>Buku</th>
                                <th>Tgl Pinjam</th>
                                <th>Tgl Kembali Real</th>
                                <th>Kondisi Buku</th>
                                <th>Keterangan Denda</th>
                                <th>Nominal Denda</th>
                                <th class="no-print" style="text-align: center;">Bukti Fisik</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($dataHistori)): ?>
                                <?php $no = 1; ?>
                                <?php foreach ($dataHistori as $row): ?>
                                    <?php
                                        $totalSemuaDenda += (int)($row['denda'] ?? 0);
                                        $kondisi = $row['kondisi_buku'] ?? 'Normal';
                                        $badgeKondisi = 'badge-success';
                                        if ($kondisi === 'Rusak Ringan') {
                                            $badgeKondisi = 'badge-warning';
                                        } elseif ($kondisi === 'Rusak Sedang') {
                                            $badgeKondisi = 'badge-orange';
                                        } elseif ($kondisi === 'Rusak Berat' || $kondisi === 'Hilang') {
                                            $badgeKondisi = 'badge-danger';
                                        }
                                    ?>
                                    <tr>
                                        <td><?= $no++ ?></td>
                                        <td style="font-weight: bold; color: #3498db;">#<?= htmlspecialchars($row['id_peminjaman'] ?? '-') ?></td>
                                        <td>
                                            <strong><?= htmlspecialchars($row['nama_siswa'] ?? '-') ?></strong><br>
                                            <span style="font-size: 11px; color: #7f8c8d;"><?= htmlspecialchars($row['nis_nip'] ?? '-') ?></span>
                                        </td>
                                        <td><?= htmlspecialchars($row['kelas'] ?? '-') ?></td>
                                        <td><?= htmlspecialchars($row['judul_buku'] ?? '-') ?></td>
                                        <td><?= !empty($row['tanggal_pinjam']) ? date('d M Y', strtotime($row['tanggal_pinjam'])) : '-' ?></td>
                                        <td><?= !empty($row['tanggal_dikembalikan']) ? date('d M Y', strtotime($row['tanggal_dikembalikan'])) : '-' ?></td>
                                        <td>
                                            <span class="badge <?= $badgeKondisi ?>"><?= htmlspecialchars($kondisi) ?></span>
                                        </td>
                                        <td>
                                            <?php if ((int)($row['denda'] ?? 0) > 0): ?>
                                                <span class="badge badge-danger"><?= htmlspecialchars($row['jenis_denda'] ?? 'Denda') ?></span>
                                            <?php else: ?>
                                                <span class="badge badge-success">Tepat Waktu</span>
                                            <?php endif; ?>
                                        </td>
                                        <td style="font-weight: bold; color: <?= (int)($row['denda'] ?? 0) > 0 ? '#e74c3c' : '#2ecc71' ?>;">
                                            Rp <?= number_format((int)($row['denda'] ?? 0), 0, ',', '.') ?>
                                        </td>
                                        <td class="no-print" style="text-align: center;">
                                            <a href="/Aplikasi_Peminjaman_Buku/VIEW/ADMIN/cetakBukti.php?id_peminjaman=<?= (int)$row['id_peminjaman'] ?>" target="_blank" class="btn-bukti">
                                                🖨️ Cetak Bukti
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="11" style="text-align: center; padding: 30px; color: #7f8c8d;">Belum ada histori transaksi pengembalian buku.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <?php if (!empty($dataHistori)): ?>
                    <div class="total-denda-box">
                        <span style="font-size: 14px; color: #7f8c8d;">Total Keseluruhan Denda Terkumpul:</span>
                        <h3 style="color: #c0392b; display: inline-block; margin-left: 10px;">Rp <?= number_format($totalSemuaDenda, 0, ',', '.') ?></h3>
                    </div>

                    <!-- Tanda tangan khusus saat Cetak Rekap Laporan -->
                    <div class="print-only ttd-print">
                        <p>Cimahi, <?= date('d M Y') ?></p>
                        <p>Petugas Perpustakaan,</p>
                        <div style="height: 60px;"></div>
                        <p><strong>( <?= htmlspecialchars($namaPetugas) ?> )</strong></p>
                        <p>Admin Perpustakaan</p>
                    </div>
                <?php endif; ?>

            </div>
        </div>
    </div>

    <!-- CSS Khusus Cetak diletakkan di bawah agar menimpa seluruh style dari SideBarAdmin.php -->
    <style>
        @media print {
            @page {
                size: A4 landscape;
                margin: 12mm;
            }

            html,
            body {
                display: block !important;
                height: auto !important;
                width: 100% !important;
                overflow: visible !important;
                background: #ffffff !important;
                margin: 0 !important;
                padding: 0 !important;
            }

            /* Sembunyikan seluruh elemen di luar .main-content (Sidebar, dll) */
            body > *:not(.main-content) {
                display: none !important;
            }

            .topbar,
            .no-print {
                display: none !important;
            }

            .main-content,
            .container,
            .card,
            .table-responsive {
                display: block !important;
                position: static !important;
                width: 100% !important;
                max-width: 100% !important;
                height: auto !important;
                overflow: visible !important;
                margin: 0 !important;
                padding: 0 !important;
                box-shadow: none !important;
                border: none !important;
            }

            .print-only {
                display: block !important;
            }

            .kop-print {
                text-align: center;
                border-bottom: 3px double #000000;
                padding-bottom: 10px;
                margin-bottom: 16px;
                font-family: 'Times New Roman', Times, serif !important;
                color: #000000 !important;
            }

            .kop-print h2 {
                font-size: 16pt;
                text-transform: uppercase;
                margin-bottom: 2px;
            }

            .kop-print h3 {
                font-size: 13pt;
                text-transform: uppercase;
                margin-bottom: 3px;
            }

            .kop-print p {
                font-size: 11pt;
            }

            .data-table {
                width: 100% !important;
                min-width: 0 !important;
                border-collapse: collapse !important;
                margin-top: 8px !important;
            }

            .data-table thead {
                display: table-header-group;
            }

            .data-table tr {
                page-break-inside: avoid;
            }

            .data-table th,
            .data-table td {
                border: 1px solid #000000 !important;
                padding: 6px 8px !important;
                font-size: 9.5pt !important;
                color: #000000 !important;
                white-space: normal !important;
            }

            .data-table th {
                background-color: #f1f5f9 !important;
                text-align: center !important;
                font-weight: bold !important;
            }

            .badge {
                color: #000000 !important;
                background: transparent !important;
                padding: 0 !important;
                font-weight: normal !important;
                font-size: 9.5pt !important;
            }

            .total-denda-box {
                margin-top: 12px !important;
                padding: 8px 12px !important;
                border: 1px solid #000000 !important;
                background: #f8fafc !important;
                color: #000000 !important;
            }

            .total-denda-box span,
            .total-denda-box h3 {
                color: #000000 !important;
                font-size: 11pt !important;
            }

            .ttd-print {
                margin-top: 24px;
                float: right;
                text-align: center;
                font-family: 'Times New Roman', Times, serif !important;
                font-size: 11pt;
                color: #000000 !important;
                page-break-inside: avoid;
                padding-right: 20px;
            }
        }
    </style>

</body>

</html>