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

// Simpan URL lokasi controller aktif saat ini
$_SESSION['last_page_admin'] = $_SERVER['REQUEST_URI'];

require_once __DIR__ . "/../../MODEL/m_kategori.php";
require_once __DIR__ . "/../../MODEL/m_penerbit.php";
require_once __DIR__ . "/../../MODEL/m_penulis.php";

$kategoriModel = new Kategori();
$penerbitModel = new Penerbit();
$penulisModel = new Penulis();

$dataKategori = $kategoriModel->getAll();
$dataPenerbit = $penerbitModel->getAll();
$dataPenulis = $penulisModel->getAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Buku - Perpustakaan Online</title>
    <style>
        /* Reset & Base Styles */
        * {
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        body {
            background-color: #f4f7f6;
            color: #333;
            margin: 0;
            padding: 20px;
        }

        /* Container Setup */
        .container {
            max-width: 600px; /* Dibuat lebih kecil untuk form */
            margin: 0 auto;
            background: #ffffff;
            padding: 30px 40px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.05);
        }

        /* Typography */
        h2 {
            color: #2c3e50;
            margin-top: 0;
            margin-bottom: 25px;
            text-align: center;
            border-bottom: 2px solid #ecf0f1;
            padding-bottom: 10px;
        }

        /* Form Layout */
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: #34495e;
            font-size: 14px;
        }

        /* Inputs & Selects */
        .form-control {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ccd1d1;
            border-radius: 5px;
            font-size: 14px;
            color: #2c3e50;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: #3498db;
            outline: none;
            box-shadow: 0 0 5px rgba(52, 152, 219, 0.3);
        }

        select[multiple] {
            height: auto;
            padding: 5px;
        }
        
        select[multiple] option {
            padding: 8px;
            border-radius: 3px;
        }

        /* Helper Text */
        .help-text {
            display: block;
            margin-top: 5px;
            font-size: 12px;
            color: #7f8c8d;
        }

        /* Buttons */
        .action-group {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }

        .btn {
            flex: 1;
            padding: 12px;
            border: none;
            border-radius: 5px;
            font-size: 15px;
            font-weight: bold;
            cursor: pointer;
            text-align: center;
            text-decoration: none;
            transition: background-color 0.3s ease;
        }

        .btn-submit {
            background-color: #2ecc71;
            color: white;
        }
        .btn-submit:hover { background-color: #27ae60; }
        
        .btn-cancel {
            background-color: #e74c3c;
            color: white;
        }
        .btn-cancel:hover { background-color: #c0392b; }

    </style>
</head>
<body>

    <div class="container">
        <h2>Tambah Buku Baru</h2>

        <form action="/Aplikasi_Peminjaman_Buku/CONTROLLER/c_buku.php?aksi=tambah" method="POST" enctype="multipart/form-data">

            <div class="form-group">
                <label for="judul_buku">Judul Buku</label>
                <input type="text" id="judul_buku" name="judul_buku" class="form-control" required placeholder="Masukkan judul buku">
            </div>

            <div class="form-group">
                <label for="tahun_terbit">Tahun Terbit</label>
                <input type="number" id="tahun_terbit" name="tahun_terbit" class="form-control" min="1000" max="9999" required placeholder="Contoh: 2023">
            </div>

            <div class="form-group">
                <label for="harga_buku">Harga Buku (Rp)</label>
                <input type="number" id="harga_buku" name="harga_buku" class="form-control" min="0" required placeholder="Contoh: 75000">
            </div>

            <div class="form-group">
                <label for="stok">Stok Buku</label>
                <input type="number" id="stok" name="stok" class="form-control" min="0" required placeholder="Jumlah stok">
            </div>

            <div class="form-group">
                <label for="id_penerbit">Penerbit</label>
                <select id="id_penerbit" name="id_penerbit" class="form-control" required>
                    <option value="">-- Pilih Penerbit --</option>
                    <?php foreach ($dataPenerbit as $penerbit): ?>
                        <option value="<?= $penerbit['id_penerbit'] ?>">
                            <?= htmlspecialchars($penerbit['nama_penerbit']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="id_kategori">Kategori</label>
                <select id="id_kategori" name="id_kategori" class="form-control" required>
                    <option value="">-- Pilih Kategori --</option>
                    <?php foreach ($dataKategori as $kategori): ?>
                        <option value="<?= $kategori['id_kategori'] ?>">
                            <?= htmlspecialchars($kategori['nama_kategori']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="id_penulis">Penulis (Bisa lebih dari 1)</label>
                <select id="id_penulis" name="id_penulis[]" class="form-control" multiple required size="5">
                    <?php foreach ($dataPenulis as $penulis): ?>
                        <option value="<?= $penulis['id_penulis'] ?>">
                            <?= htmlspecialchars($penulis['nama_penulis']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <span class="help-text">*Gunakan <b>Ctrl + klik</b> (Windows) atau <b>Cmd + klik</b> (Mac) untuk memilih lebih dari satu penulis.</span>
            </div>

            <div class="form-group">
                <label for="cover">Cover Buku</label>
                <input type="file" id="cover" name="cover" class="form-control" accept=".jpg,.jpeg,.png,.webp" style="padding: 7px 12px;">
                <span class="help-text">*Format: JPG, JPEG, PNG, WEBP. Maksimal ukuran 2 MB.</span>
            </div>

            <div class="action-group">
                <!-- CLEAN URL: Kembali ke Daftar Buku -->
                <a href="/Aplikasi_Peminjaman_Buku/admin/daftarBuku" class="btn btn-cancel">Batal</a>
                <button type="submit" class="btn btn-submit">Simpan Buku</button>
            </div>

        </form>
    </div>

</body>
</html>