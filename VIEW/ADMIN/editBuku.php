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

// Simpan URL lokasi halaman aktif
$_SESSION['last_page_admin'] = $_SERVER['REQUEST_URI'];

// 2. Ambil ID Buku dari URL
$idBuku = (int)($_GET['id_buku'] ?? 0);

if ($idBuku <= 0) {
    $_SESSION['error'] = "ID Buku tidak valid.";
    // Clean URL: Redirect ke Daftar Buku
    header("Location: /Aplikasi_Peminjaman_Buku/admin/daftarBuku");
    exit;
}

// 3. Panggil Model Langsung di View
require_once __DIR__ . "/../../MODEL/m_buku.php";
require_once __DIR__ . "/../../MODEL/m_buku_penulis.php";
require_once __DIR__ . "/../../MODEL/m_kategori.php";
require_once __DIR__ . "/../../MODEL/m_penerbit.php";
require_once __DIR__ . "/../../MODEL/m_penulis.php";

$bukuModel        = new Buku();
$bukuPenulisModel = new BukuPenulis();
$kategoriModel    = new Kategori();
$penerbitModel    = new Penerbit();
$penulisModel     = new Penulis();

// Ambil Data Buku
$dataBuku = $bukuModel->getBukuById($idBuku);

if (!$dataBuku) {
    $_SESSION['error'] = "Data buku tidak ditemukan di database.";
    // Clean URL: Redirect ke Daftar Buku
    header("Location: /Aplikasi_Peminjaman_Buku/admin/daftarBuku");
    exit;
}

// Ambil Data Pendukung
$daftarPenerbit   = $penerbitModel->getAll();
$daftarKategori   = $kategoriModel->getAll();
$dataSemuaPenulis = $penulisModel->getAll();

// Ambil ID Penulis Terpilih
$penulisTerpilih  = $bukuPenulisModel->getPenulisByBuku($idBuku);
$idPenulisTerpilih = [];
if (!empty($penulisTerpilih)) {
    foreach ($penulisTerpilih as $p) {
        $idPenulisTerpilih[] = $p['id_penulis'];
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Buku - Perpustakaan Online</title>
    <style>
        /* Reset & Base Layout */
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
            overflow: hidden;
            background-color: #f4f7f6;
            color: #333;
        }

        body {
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
            padding: 25px;
            flex: 1;
            overflow-y: auto;
            overflow-x: hidden;
        }

        .card {
            background: #ffffff;
            padding: 25px 30px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.05);
            max-width: 950px;
            margin: 0 auto;
        }

        .card-header {
            border-bottom: 2px solid #ecf0f1;
            padding-bottom: 12px;
            margin-bottom: 20px;
        }

        .card-header h3 {
            color: #2c3e50;
            font-size: 20px;
        }

        /* FORM LAYOUT 2 KOLOM (KANAN-KIRI) */
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px 30px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group.full-width {
            grid-column: span 2;
        }

        .form-group label {
            font-weight: 600;
            margin-bottom: 6px;
            color: #34495e;
            font-size: 13px;
        }

        .form-control {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ccd1d1;
            border-radius: 6px;
            font-size: 14px;
            color: #2c3e50;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: #3498db;
            outline: none;
            box-shadow: 0 0 5px rgba(52, 152, 219, 0.3);
        }

        /* Checkbox Group Penulis */
        .checkbox-group {
            background-color: #f8f9fa;
            padding: 10px 14px;
            border: 1px solid #ccd1d1;
            border-radius: 6px;
            max-height: 120px;
            overflow-y: auto;
        }

        .checkbox-item {
            display: flex;
            align-items: center;
            margin-bottom: 6px;
            font-size: 13px;
            cursor: pointer;
        }

        .checkbox-item input {
            margin-right: 8px;
            cursor: pointer;
        }

        /* Cover Preview */
        .cover-preview-wrapper {
            display: flex;
            align-items: center;
            gap: 15px;
            background: #f8f9fa;
            padding: 10px;
            border-radius: 6px;
            border: 1px solid #ecf0f1;
        }

        .current-cover {
            border-radius: 4px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            object-fit: cover;
            width: 60px;
            height: 80px;
        }

        .help-text {
            font-size: 11px;
            color: #7f8c8d;
            margin-top: 4px;
        }

        /* Buttons Group */
        .action-group {
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            margin-top: 25px;
            padding-top: 15px;
            border-top: 1px solid #ecf0f1;
        }

        .btn {
            padding: 10px 22px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: bold;
            cursor: pointer;
            text-align: center;
            text-decoration: none;
            transition: background-color 0.3s ease;
        }

        .btn-submit {
            background-color: #3498db;
            color: white;
        }

        .btn-submit:hover {
            background-color: #2980b9;
        }

        .btn-cancel {
            background-color: #e74c3c;
            color: white;
        }

        .btn-cancel:hover {
            background-color: #c0392b;
        }

        .alert-error {
            background-color: #fdeaea;
            color: #c0392b;
            padding: 15px;
            border-radius: 6px;
            text-align: center;
        }
    </style>
</head>

<body>

    <!-- Sidebar Admin -->
    <?php include __DIR__ . "/../TEMPLATE/SideBarAdmin.php"; ?>

    <div class="main-content">
        <div class="topbar">
            <h2>Edit Buku</h2>
            <div style="font-size: 14px; color: #7f8c8d;">Panel Admin</div>
        </div>

        <div class="container">
            <div class="card">
                <div class="card-header">
                    <h3>Formulir Perubahan Data Buku</h3>
                </div>

                <?php if (!empty($dataBuku)): ?>
                    <form action="/Aplikasi_Peminjaman_Buku/CONTROLLER/c_buku.php?aksi=update" method="POST" enctype="multipart/form-data">

                        <input type="hidden" name="id_buku" value="<?= htmlspecialchars($dataBuku['id_buku']) ?>">

                        <div class="form-grid">

                            <!-- KOLOM KIRI -->
                            <div class="form-group full-width">
                                <label for="judul_buku">Judul Buku</label>
                                <input type="text" id="judul_buku" name="judul_buku" class="form-control" value="<?= htmlspecialchars($dataBuku['judul_buku']) ?>" required>
                            </div>

                            <div class="form-group">
                                <label for="id_kategori">Kategori</label>
                                <select id="id_kategori" name="id_kategori" class="form-control" required>
                                    <option value="">-- Pilih Kategori --</option>
                                    <?php foreach ($daftarKategori as $kategori): ?>
                                        <option value="<?= $kategori['id_kategori'] ?>" <?= ($dataBuku['id_kategori'] == $kategori['id_kategori']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($kategori['nama_kategori']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="id_penerbit">Penerbit</label>
                                <select id="id_penerbit" name="id_penerbit" class="form-control" required>
                                    <option value="">-- Pilih Penerbit --</option>
                                    <?php foreach ($daftarPenerbit as $penerbit): ?>
                                        <option value="<?= $penerbit['id_penerbit'] ?>" <?= ($dataBuku['id_penerbit'] == $penerbit['id_penerbit']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($penerbit['nama_penerbit']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group full-width">
                                <label>Penulis</label>
                                <div class="checkbox-group">
                                    <?php if (!empty($dataSemuaPenulis)): ?>
                                        <?php foreach ($dataSemuaPenulis as $penulis): ?>
                                            <?php $isChecked = in_array($penulis['id_penulis'], $idPenulisTerpilih) ? 'checked' : ''; ?>
                                            <label class="checkbox-item">
                                                <input type="checkbox" name="id_penulis[]" value="<?= $penulis['id_penulis'] ?>" <?= $isChecked ?>>
                                                <?= htmlspecialchars($penulis['nama_penulis']) ?>
                                            </label>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <span style="font-size: 13px; color: #7f8c8d;">Data penulis belum tersedia.</span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- KOLOM KANAN -->
                            <div class="form-group">
                                <label for="tahun_terbit">Tahun Terbit</label>
                                <input type="number" id="tahun_terbit" name="tahun_terbit" class="form-control" value="<?= htmlspecialchars($dataBuku['tahun_terbit']) ?>" min="1900" max="<?= date('Y') ?>" required>
                            </div>

                            <div class="form-group">
                                <label for="stok">Stok Buku</label>
                                <input type="number" id="stok" name="stok" class="form-control" value="<?= htmlspecialchars($dataBuku['stok']) ?>" min="0" required>
                            </div>

                            <div class="form-group">
                                <label for="harga_buku">Harga Buku (Rp)</label>
                                <input type="number" id="harga_buku" name="harga_buku" class="form-control" value="<?= htmlspecialchars($dataBuku['harga_buku']) ?>" min="0" required>
                            </div>

                            <div class="form-group">
                                <label for="cover">Ganti Cover</label>
                                <input type="file" id="cover" name="cover" class="form-control" accept=".jpg,.jpeg,.png,.webp">
                                <span class="help-text">*Kosongkan jika tidak ingin mengubah cover.</span>
                            </div>

                            <div class="form-group full-width">
                                <label>Cover Saat Ini</label>
                                <div class="cover-preview-wrapper">
                                    <?php if (!empty($dataBuku['cover'])): ?>
                                        <img src="/Aplikasi_Peminjaman_Buku/ASSETS/COVER/<?= htmlspecialchars($dataBuku['cover']) ?>" alt="Cover Buku" class="current-cover">
                                        <span style="font-size: 13px; color: #2c3e50;"><?= htmlspecialchars($dataBuku['cover']) ?></span>
                                    <?php else: ?>
                                        <span class="help-text">Belum ada cover terpasang.</span>
                                    <?php endif; ?>
                                </div>
                            </div>

                        </div>

                        <div class="action-group">
                            <!-- CLEAN URL: Kembali ke Daftar Buku -->
                            <a href="/Aplikasi_Peminjaman_Buku/admin/daftarBuku" class="btn btn-cancel">Batal</a>
                            <button type="submit" class="btn btn-submit">Simpan Perubahan</button>
                        </div>

                    </form>
                <?php else: ?>
                    <div class="alert-error">Data buku tidak ditemukan.</div>
                    <div style="text-align: center; margin-top: 20px;">
                        <!-- CLEAN URL: Kembali ke Daftar Buku -->
                        <a href="/Aplikasi_Peminjaman_Buku/admin/daftarBuku" class="btn btn-cancel">Kembali ke Daftar Buku</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>