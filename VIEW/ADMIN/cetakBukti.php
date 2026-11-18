<?php
// Halaman: Cetak Bukti Fisik Pengembalian & Denda
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Proteksi Akses Admin
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'admin') {
    header("Location: /Aplikasi_Peminjaman_Buku/login");
    exit;
}

require_once __DIR__ . "/../../MODEL/m_peminjaman.php";

$id_peminjaman = isset($_GET['id_peminjaman']) ? (int)$_GET['id_peminjaman'] : 0;
if ($id_peminjaman <= 0) {
    echo "<script>alert('ID Peminjaman tidak valid.'); window.close();</script>";
    exit;
}

$peminjamanModel = new M_Peminjaman();
$dataBukti = $peminjamanModel->getHistoriByIdPeminjaman($id_peminjaman);

if (empty($dataBukti)) {
    echo "<script>alert('Data histori transaksi tidak ditemukan.'); window.close();</script>";
    exit;
}

$header = $dataBukti[0];
$totalDenda = 0;
$namaPetugas = $_SESSION['nama_lengkap'] ?? 'Petugas Perpustakaan';
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bukti Pengembalian #PMJ-<?= htmlspecialchars($id_peminjaman) ?></title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Times New Roman', Times, serif;
            background-color: #eef2f5;
            color: #111;
            padding: 25px;
        }

        .action-bar {
            max-width: 800px;
            margin: 0 auto 15px auto;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .btn {
            padding: 9px 18px;
            border: none;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .btn-print {
            background-color: #2563eb;
            color: #fff;
        }

        .btn-print:hover {
            background-color: #1d4ed8;
        }

        .btn-close {
            background-color: #64748b;
            color: #fff;
        }

        .btn-close:hover {
            background-color: #475569;
        }

        .sheet {
            max-width: 800px;
            margin: 0 auto;
            background: #ffffff;
            padding: 35px 40px;
            border: 1px solid #cbd5e1;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }

        .kop-surat {
            text-align: center;
            border-bottom: 3px double #000;
            padding-bottom: 12px;
            margin-bottom: 20px;
        }

        .kop-surat h2 {
            font-size: 18px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 3px;
        }

        .kop-surat h3 {
            font-size: 15px;
            text-transform: uppercase;
            margin-bottom: 4px;
        }

        .kop-surat p {
            font-size: 12px;
            color: #333;
        }

        .judul-dokumen {
            text-align: center;
            margin-bottom: 20px;
        }

        .judul-dokumen h4 {
            font-size: 15px;
            text-decoration: underline;
            text-transform: uppercase;
            margin-bottom: 4px;
        }

        .judul-dokumen span {
            font-size: 13px;
        }

        .info-grid {
            width: 100%;
            margin-bottom: 18px;
            font-size: 13.5px;
        }

        .info-grid td {
            padding: 4px 6px;
            vertical-align: top;
        }

        .info-label {
            width: 165px;
            font-weight: bold;
        }

        .info-sep {
            width: 12px;
        }

        .tabel-buku {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 18px;
            font-size: 13px;
        }

        .tabel-buku th,
        .tabel-buku td {
            border: 1px solid #000;
            padding: 8px 10px;
        }

        .tabel-buku th {
            background-color: #f1f5f9;
            text-align: center;
            font-weight: bold;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .catatan {
            font-size: 12px;
            line-height: 1.5;
            margin-bottom: 28px;
            padding: 10px 12px;
            border: 1px dashed #64748b;
            background-color: #f8fafc;
        }

        .ttd-wrapper {
            width: 100%;
            margin-top: 20px;
            font-size: 13px;
        }

        .ttd-wrapper td {
            width: 50%;
            text-align: center;
            vertical-align: top;
        }

        .ttd-space {
            height: 65px;
        }

        @media print {
            body {
                background: #fff;
                padding: 0;
            }

            .action-bar {
                display: none !important;
            }

            .sheet {
                max-width: 100%;
                border: none;
                box-shadow: none;
                padding: 10px 15px;
            }
        }
    </style>
</head>

<body>

    <div class="action-bar">
        <button onclick="window.print()" class="btn btn-print">🖨️ Cetak Bukti Fisik</button>
        <button onclick="window.close()" class="btn btn-close">✖ Tutup</button>
    </div>

    <div class="sheet">
        <!-- KOP SURAT -->
        <div class="kop-surat">
            <h2>Perpustakaan SMK Sangkuriang 1 Cimahi</h2>
            <h3>Yayasan Dayang Sumbi Jaya Lestari</h3>
            <p>Bukti Resmi Sirkulasi Peminjaman, Pengembalian Buku &amp; Penyelesaian Denda</p>
        </div>

        <!-- JUDUL -->
        <div class="judul-dokumen">
            <h4>Bukti Fisik Pengembalian Buku</h4>
            <span>Nomor Transaksi: <strong>#PMJ-<?= htmlspecialchars($id_peminjaman) ?></strong></span>
        </div>

        <!-- INFORMASI PEMINJAM -->
        <table class="info-grid">
            <tr>
                <td class="info-label">Nama Peminjam</td>
                <td class="info-sep">:</td>
                <td><?= htmlspecialchars($header['nama_siswa'] ?? '-') ?></td>
                <td class="info-label">Tanggal Pinjam</td>
                <td class="info-sep">:</td>
                <td><?= !empty($header['tanggal_pinjam']) ? date('d/m/Y', strtotime($header['tanggal_pinjam'])) : '-' ?></td>
            </tr>
            <tr>
                <td class="info-label">NIS / NIP</td>
                <td class="info-sep">:</td>
                <td><?= htmlspecialchars($header['nis_nip'] ?? '-') ?></td>
                <td class="info-label">Batas Pengembalian</td>
                <td class="info-sep">:</td>
                <td><?= !empty($header['tanggal_pengembalian']) ? date('d/m/Y', strtotime($header['tanggal_pengembalian'])) : '-' ?></td>
            </tr>
            <tr>
                <td class="info-label">Kelas</td>
                <td class="info-sep">:</td>
                <td><?= htmlspecialchars($header['kelas'] ?? '-') ?></td>
                <td class="info-label">Tanggal Dikembalikan</td>
                <td class="info-sep">:</td>
                <td><strong><?= !empty($header['tanggal_dikembalikan']) ? date('d/m/Y', strtotime($header['tanggal_dikembalikan'])) : '-' ?></strong></td>
            </tr>
        </table>

        <!-- TABEL RINCIAN BUKU -->
        <table class="tabel-buku">
            <thead>
                <tr>
                    <th style="width: 35px;">No</th>
                    <th>Judul Buku</th>
                    <th style="width: 55px;">Jml</th>
                    <th style="width: 115px;">Kondisi Buku</th>
                    <th style="width: 165px;">Keterangan Denda</th>
                    <th style="width: 120px;">Nominal Denda</th>
                </tr>
            </thead>
            <tbody>
                <?php $no = 1; ?>
                <?php foreach ($dataBukti as $item): ?>
                    <?php
                        $nominal = (int)($item['denda'] ?? 0);
                        $totalDenda += $nominal;
                    ?>
                    <tr>
                        <td class="text-center"><?= $no++ ?></td>
                        <td><?= htmlspecialchars($item['judul_buku'] ?? '-') ?></td>
                        <td class="text-center"><?= (int)($item['jumlah_buku'] ?? 1) ?></td>
                        <td class="text-center"><?= htmlspecialchars($item['kondisi_buku'] ?? 'Normal') ?></td>
                        <td><?= htmlspecialchars($item['jenis_denda'] ?? 'Tidak Ada') ?></td>
                        <td class="text-right">Rp <?= number_format($nominal, 0, ',', '.') ?></td>
                    </tr>
                <?php endforeach; ?>
                <tr>
                    <th colspan="5" class="text-right">TOTAL DENDA YANG DIBAYARKAN</th>
                    <th class="text-right">Rp <?= number_format($totalDenda, 0, ',', '.') ?></th>
                </tr>
            </tbody>
        </table>

        <!-- CATATAN -->
        <div class="catatan">
            <strong>Keterangan:</strong><br>
            1. Dokumen ini merupakan bukti sah bahwa buku tersebut di atas telah dikembalikan dan diverifikasi oleh petugas perpustakaan.<br>
            2. Simpan bukti fisik ini sebagai arsip apabila sewaktu-waktu diperlukan pengecekan administrasi.
        </div>

        <!-- TANDA TANGAN -->
        <table class="ttd-wrapper">
            <tr>
                <td>
                    Peminjam / Siswa,<br>
                    <div class="ttd-space"></div>
                    <strong>( <?= htmlspecialchars($header['nama_siswa'] ?? '.........................') ?> )</strong><br>
                    <span>NIS/NIP: <?= htmlspecialchars($header['nis_nip'] ?? '-') ?></span>
                </td>
                <td>
                    Cimahi, <?= date('d M Y') ?><br>
                    Petugas Perpustakaan,<br>
                    <div class="ttd-space"></div>
                    <strong>( <?= htmlspecialchars($namaPetugas) ?> )</strong><br>
                    <span>Admin Perpustakaan</span>
                </td>
            </tr>
        </table>
    </div>

    <script>
        window.onload = function () {
            window.print();
        };
    </script>
</body>

</html>