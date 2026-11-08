<?php
// =====================================================================
// FILE   : VIEW/ADMIN/historiTransaksi.php  (URL: /admin/historiTransaksi, lewat c_histori.php)
// FUNGSI : HISTORI TRANSAKSI admin: tabel transaksi yang sudah selesai + total denda.
// DATA   : M_Peminjaman::getHistoriTransaksi() (view mengambil sendiri; $daftarHistori dari c_histori tidak dipakai).
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
require_once __DIR__ . "/../../MODEL/m_peminjaman.php";
// Buat objek dari class M_Peminjaman; variabel $peminjamanModel dipakai untuk memanggil fungsi-fungsinya.
$peminjamanModel = new M_Peminjaman();
// Panggil method getHistoriTransaksi() milik objek $peminjamanModel; hasilnya disimpan di $dataHistori.
$dataHistori = $peminjamanModel->getHistoriTransaksi();

// Inisialisasi variabel denda
// Penampung total seluruh denda; diisi (dijumlahkan) di dalam perulangan tabel di bawah.
$totalSemuaDenda = 0;
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
    <title>Histori Transaksi - Panel Admin</title>

    <!-- CSS khusus halaman ini (warna, ukuran, tata letak); tidak dikomentari baris demi baris. -->
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        /* 1. Kunci ukuran Layar Utama agar tidak bisa di-scroll ke mana pun */
        html,
        body {
            height: 100vh;
            width: 100vw;
            margin: 0;
            padding: 0;
            overflow: hidden;
            /* Mencegah scrollbar utama muncul */
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f4f7f6;
        }

        body {
            display: flex;
            color: #333;
        }

        /* 2. Main Content mengisi sisa area layar tanpa melebihi batas */
        .main-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            height: 100vh;
            overflow: hidden;
            /* Mengunci konten utama agar tidak keluar layar */
        }

        /* 3. Container utama dibuat responsif dan hanya konten di dalamnya yang di-scroll jika panjang */
        .container {
            padding: 30px;
            flex: 1;
            overflow-y: auto;
            /* Hanya scroll ke bawah jika isi tabel panjang */
            overflow-x: hidden;
            /* Hilangkan scroll samping kanan-kiri */
        }

        /* 4. Card Container */
        .card {
            background: #ffffff;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.05);
            width: 100%;
        }

        /* 5. Mencegah Tabel Memaksa Layar Melebar ke Kanan */
        .table-responsive {
            width: 100%;
            overflow-x: auto;
            /* Scroll horizontal hanya aktif di dalam area tabel saja jika terpaksa */
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            min-width: 1000px;
            /* UBAH min-width: 800px/900px menjadi 100% agar pas dengan card */
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
    </style>
</head>

<!-- BAGIAN BODY: seluruh isi yang tampil di layar. -->
<body>

    <!-- Sidebar Admin -->
    <?php include __DIR__ . "/../TEMPLATE/SideBarAdmin.php"; ?>

    <div class="main-content">
        <div class="topbar">
            <h2>Laporan & Histori Transaksi</h2>
            <div style="font-size: 14px; color: #7f8c8d;">Panel Admin</div>
        </div>

        <div class="container">
            <div class="card">
                <div class="page-header">
                    <h3>Rekap Pengembalian Buku & Denda</h3>
                    <p>Catatan fisik seluruh transaksi peminjaman buku yang telah selesai diproses.</p>
                </div>

                <div class="table-responsive">
                    <!-- TABEL data. -->
                    <table class="data-table">
                        <!-- Judul kolom tabel. -->
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>ID PMJ</th>
                                <th>Peminjam (NIS/NIP)</th>
                                <th>Kelas</th>
                                <th>Buku</th>
                                <th>Tgl Pinjam</th>
                                <th>Tgl Kembali Real</th>
                                <th>Keterangan Denda</th>
                                <th>Nominal Denda</th>
                            </tr>
                        </thead>
                        <!-- Isi tabel: satu baris (<tr>) untuk setiap data. -->
                        <tbody>
                            <!-- KONDISI: blok di bawah (sampai else/endif) hanya tampil jika !empty($dataHistori) -->
                            <?php if (!empty($dataHistori)): ?>
                                <?php $no = 1; ?>
                                <!-- PERULANGAN: blok di bawah (sampai endforeach) diulang untuk setiap elemen $dataHistori; elemen aktif ada di $row. -->
                                <?php foreach ($dataHistori as $row): ?>
                                    <?php $totalSemuaDenda += (int)($row['denda'] ?? 0); ?>
                                    <tr>
                                        <?php /* Sintaks echo singkat PHP (tag buka PHP diikuti tanda sama dengan): mencetak nilai PHP langsung ke dalam HTML. */ ?>
                                        <td><?= $no++ ?></td>
                                        <?php /* htmlspecialchars() mengubah karakter khusus HTML (< > " ') menjadi aman ditampilkan; ini pencegah XSS (penyisipan script lewat data). */ ?>
                                        <td style="font-weight: bold; color: #3498db;">#<?= htmlspecialchars($row['id_peminjaman'] ?? '-') ?></td>
                                        <td>
                                            <strong><?= htmlspecialchars($row['nama_siswa'] ?? '-') ?></strong><br>
                                            <span style="font-size: 11px; color: #7f8c8d;"><?= htmlspecialchars($row['nis_nip'] ?? '-') ?></span>
                                        </td>
                                        <!-- Penanganan fallback jika kelas kosong / NULL -->
                                        <td><?= htmlspecialchars($row['kelas'] ?? '-') ?></td>
                                        <td><?= htmlspecialchars($row['judul_buku'] ?? '-') ?></td>
                                        <td><?= !empty($row['tanggal_pinjam']) ? date('d M Y', strtotime($row['tanggal_pinjam'])) : '-' ?></td>
                                        <td><?= !empty($row['tanggal_dikembalikan']) ? date('d M Y', strtotime($row['tanggal_dikembalikan'])) : '-' ?></td>
                                        <td>
                                            <?php /* KONDISI: blok di bawah (sampai else/endif) hanya tampil jika (int)($row['denda'] ?? 0) > 0 */ ?>
                                            <?php if ((int)($row['denda'] ?? 0) > 0): ?>
                                                <span class="badge badge-danger"><?= htmlspecialchars($row['jenis_denda'] ?? 'Denda') ?></span>
                                            <?php /* ELSE: blok di bawah tampil jika kondisi di atasnya TIDAK terpenuhi. */ ?>
                                            <?php else: ?>
                                                <span class="badge badge-success">Tepat Waktu</span>
                                            <?php /* Akhir kondisi if. */ ?>
                                            <?php endif; ?>
                                        </td>
                                        <td style="font-weight: bold; color: <?= (int)($row['denda'] ?? 0) > 0 ? '#e74c3c' : '#2ecc71' ?>;">
                                            Rp <?= number_format((int)($row['denda'] ?? 0), 0, ',', '.') ?>
                                        </td>
                                    </tr>
                                <?php /* Akhir perulangan foreach. */ ?>
                                <?php endforeach; ?>
                            <!-- ELSE: blok di bawah tampil jika kondisi di atasnya TIDAK terpenuhi. -->
                            <?php else: ?>
                                <tr>
                                    <td colspan="9" style="text-align: center; padding: 30px; color: #7f8c8d;">Belum ada histori transaksi pengembalian buku.</td>
                                </tr>
                            <!-- Akhir kondisi if. -->
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- KONDISI: blok di bawah (sampai else/endif) hanya tampil jika !empty($dataHistori) -->
                <?php if (!empty($dataHistori)): ?>
                    <div style="margin-top: 20px; padding: 15px; background: #fdfefe; border: 1px solid #e5e8e8; border-radius: 5px; text-align: right;">
                        <span style="font-size: 14px; color: #7f8c8d;">Total Keseluruhan Denda Terkumpul:</span>
                        <h3 style="color: #c0392b; display: inline-block; margin-left: 10px;">Rp <?= number_format($totalSemuaDenda, 0, ',', '.') ?></h3>
                    </div>
                <!-- Akhir kondisi if. -->
                <?php endif; ?>

            </div>
        </div>
    </div>

</body>

</html>