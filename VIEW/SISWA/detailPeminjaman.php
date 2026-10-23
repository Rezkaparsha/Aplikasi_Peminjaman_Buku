<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Proteksi Akses Siswa Terpusat
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

require_once __DIR__ . "/../../MODEL/m_peminjaman.php";

// Mengambil ID User dari sesi akun yang sedang login
$id_user = (int)$_SESSION['id_user'];
$id_peminjaman = isset($_GET['id_peminjaman']) ? (int)$_GET['id_peminjaman'] : 0;

if ($id_peminjaman <= 0) {
    $_SESSION['error'] = "ID Peminjaman tidak valid.";
    header("Location: /Aplikasi_Peminjaman_Buku/siswa/peminjaman");
    exit;
}

$modelPeminjaman = new M_Peminjaman();

// Cek apakah transaksi ini milik user yang sedang login
if (!$modelPeminjaman->cekKepemilikanPeminjaman($id_peminjaman, $id_user)) {
    $_SESSION['error'] = "Anda tidak memiliki akses ke data peminjaman ini.";
    header("Location: /Aplikasi_Peminjaman_Buku/siswa/peminjaman");
    exit;
}

// Ambil data header peminjaman dan rincian detail buku
$headerPeminjaman = $modelPeminjaman->getPeminjamanById($id_peminjaman);
$detailBuku = $modelPeminjaman->getDetailPeminjaman($id_peminjaman);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Peminjaman #PMJ-<?= $id_peminjaman ?> - Perpustakaan</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome Icon -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
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
            padding: 25px;
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
            min-width: 100%;
            /* UBAH min-width: 800px/900px menjadi 100% agar pas dengan card */
        }

        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
        }

        .cover-buku {
            width: 60px;
            height: 85px;
            object-fit: cover;
            border-radius: 6px;
        }

        .badge-status {
            font-size: 0.85rem;
            padding: 0.5em 0.85em;
            border-radius: 20px;
            font-weight: 600;
        }
    </style>
</head>

<body>

    <div class="container py-5">

        <!-- Header Navigasi -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="fw-bold mb-1">
                    <i class="fa-solid fa-file-invoice text-primary me-2"></i>Rincian Peminjaman #PMJ-<?= htmlspecialchars($id_peminjaman) ?>
                </h3>
                <p class="text-muted mb-0">Detail informasi buku dan status pengajuan Anda</p>
            </div>
            <!-- CLEAN URL: Kembali ke Riwayat Peminjaman -->
            <a href="/Aplikasi_Peminjaman_Buku/siswa/peminjaman" class="btn btn-outline-secondary rounded-pill px-4">
                <i class="fa-solid fa-arrow-left me-2"></i>Kembali ke Peminjaman Saya
            </a>
        </div>

        <?php if ($headerPeminjaman): ?>
            <div class="row g-4">
                <!-- Information Card (Kiri) -->
                <div class="col-lg-4">
                    <div class="card p-4 h-100">
                        <h5 class="fw-bold mb-3 border-bottom pb-2">Informasi Transaksi</h5>

                        <div class="mb-3">
                            <label class="text-muted small d-block">ID Peminjaman</label>
                            <span class="fw-bold text-primary fs-5">#PMJ-<?= htmlspecialchars($headerPeminjaman['id_peminjaman']) ?></span>
                        </div>

                        <div class="mb-3">
                            <label class="text-muted small d-block">Peminjam (NIS/NIP)</label>
                            <span class="fw-semibold"><?= htmlspecialchars($headerPeminjaman['nama_lengkap']) ?></span>
                            <span class="text-muted"> (<?= htmlspecialchars($headerPeminjaman['nis']) ?>)</span>
                        </div>

                        <div class="mb-3">
                            <label class="text-muted small d-block">Tanggal Pengajuan / Pinjam</label>
                            <span class="fw-semibold">
                                <i class="fa-regular fa-calendar-days text-muted me-1"></i>
                                <?= date('d F Y', strtotime($headerPeminjaman['tanggal_pinjam'])) ?>
                            </span>
                        </div>

                        <div class="mb-3">
                            <label class="text-muted small d-block">Status Transaksi Utama</label>
                            <?php
                            $statusUtama = $headerPeminjaman['status'];
                            $badgeUtama = 'bg-secondary';
                            if ($statusUtama === 'Diajukan') $badgeUtama = 'bg-warning text-dark';
                            elseif ($statusUtama === 'Dipinjam') $badgeUtama = 'bg-info text-white';
                            elseif ($statusUtama === 'Dikembalikan') $badgeUtama = 'bg-success text-white';
                            elseif ($statusUtama === 'Ditolak') $badgeUtama = 'bg-danger text-white';
                            ?>
                            <span class="badge badge-status <?= $badgeUtama ?> mt-1">
                                <?= htmlspecialchars($statusUtama) ?>
                            </span>
                        </div>
                        <!-- TAMBAHKAN KODE INI DI SINI -->
                        <?php if ($headerPeminjaman['status'] === 'Ditolak'): ?>
                            <div class="alert alert-danger mt-3 p-3 text-sm">
                                <strong><i class="fa-solid fa-circle-exclamation me-1"></i> Pengajuan Ditolak!</strong><br>
                                Alasan: <?= htmlspecialchars($headerPeminjaman['alasan_penolakan'] ?? 'Tidak ada alasan yang diberikan.') ?>
                            </div>
                        <?php endif; ?>
                        <!-- SAMPAI SINI -->
                    </div>
                </div>

                <!-- List Buku Card (Kanan) -->
                <div class="col-lg-8">
                    <div class="card p-4 h-100">
                        <h5 class="fw-bold mb-3 border-bottom pb-2">Daftar Buku yang Dipinjam</h5>

                        <?php if (!empty($detailBuku)): ?>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Buku</th>
                                            <th class="text-center">Jumlah</th>
                                            <th>Tgl Harus Kembali</th>
                                            <th>Tgl Dikembalikan</th>
                                            <th class="text-center">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($detailBuku as $item): ?>
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <!-- CLEAN URL: Path gambar diubah ke Absolute Path -->
                                                        <?php if (!empty($item['cover'])): ?>
                                                            <img src="/Aplikasi_Peminjaman_Buku/ASSETS/COVER/<?= htmlspecialchars($item['cover']) ?>" class="cover-buku me-3" alt="Cover">
                                                        <?php else: ?>
                                                            <div class="bg-light border rounded d-flex align-items-center justify-content-center me-3 cover-buku">
                                                                <i class="fa-solid fa-book text-muted"></i>
                                                            </div>
                                                        <?php endif; ?>
                                                        <div>
                                                            <span class="fw-bold d-block"><?= htmlspecialchars($item['judul_buku']) ?></span>
                                                            <small class="text-muted">Harga Buku: Rp <?= number_format($item['harga_buku'], 0, ',', '.') ?></small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="text-center font-monospace fw-bold">
                                                    <?= htmlspecialchars($item['jumlah_buku']) ?> Bks
                                                </td>
                                                <td>
                                                    <?= $item['tanggal_pengembalian'] ? date('d M Y', strtotime($item['tanggal_pengembalian'])) : '<span class="text-muted">-</span>' ?>
                                                </td>
                                                <td>
                                                    <?= $item['tanggal_dikembalikan'] ? date('d M Y', strtotime($item['tanggal_dikembalikan'])) : '<span class="text-muted">-</span>' ?>
                                                </td>
                                                <td class="text-center">
                                                    <?php
                                                    $stDetail = $item['status_detail'];
                                                    $badgeDetail = 'bg-secondary';
                                                    if ($stDetail === 'Diajukan') $badgeDetail = 'bg-warning text-dark';
                                                    elseif ($stDetail === 'Dipinjam') $badgeDetail = 'bg-info text-white';
                                                    elseif ($stDetail === 'Dikembalikan') $badgeDetail = 'bg-success text-white';
                                                    elseif ($stDetail === 'Ditolak') $badgeDetail = 'bg-danger text-white';
                                                    ?>
                                                    <span class="badge badge-status <?= $badgeDetail ?>">
                                                        <?= htmlspecialchars($stDetail) ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p class="text-muted">Tidak ada rincian buku pada peminjaman ini.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="alert alert-danger">Data peminjaman tidak ditemukan.</div>
        <?php endif; ?>

    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>