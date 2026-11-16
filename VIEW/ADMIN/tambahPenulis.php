<?php
// =====================================================================
// FILE   : VIEW/ADMIN/tambahPenulis.php  (URL: /admin/tambahPenulis)
// FUNGSI : FORM TAMBAH PENULIS.
// ALUR   : submit (POST) -> CONTROLLER/c_penulis.php?aksi=tambah -> simpan -> kembali ke daftar.
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
    <title>Tambah Penulis - Perpustakaan Online</title>
    <!-- CSS khusus halaman ini (warna, ukuran, tata letak); tidak dikomentari baris demi baris. -->
    <style>
        /* Reset & Base Styles */
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

        /* Main Content Layout */
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

        /* Card Container */
        .card {
            background: #ffffff;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.05);
            max-width: 600px;
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

        /* Form Layout */
        .form-group {
            margin-bottom: 18px;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 6px;
            color: #34495e;
            font-size: 13px;
        }

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

        /* Buttons */
        .action-group {
            display: flex;
            gap: 10px;
            margin-top: 25px;
        }

        .btn {
            padding: 10px 18px;
            border: none;
            border-radius: 5px;
            font-size: 14px;
            font-weight: bold;
            cursor: pointer;
            text-align: center;
            text-decoration: none;
            transition: background 0.3s ease;
        }

        .btn-submit {
            background-color: #2ecc71;
            color: white;
            flex: 1;
        }

        .btn-submit:hover {
            background-color: #27ae60;
        }

        .btn-cancel {
            background-color: #e74c3c;
            color: white;
            flex: 1;
        }

        .btn-cancel:hover {
            background-color: #c0392b;
        }
    </style>
</head>

<!-- BAGIAN BODY: seluruh isi yang tampil di layar. -->
<body>

    <!-- Sidebar Admin -->
    <?php include __DIR__ . "/../TEMPLATE/SideBarAdmin.php"; ?>

    <div class="main-content">
        <!-- Topbar -->
        <div class="topbar">
            <h2>Tambah Penulis</h2>
            <div style="font-size: 14px; color: #7f8c8d;">Panel Admin</div>
        </div>

        <!-- Main Container -->
        <div class="container">
            <div class="card">
                <div class="card-header">
                    <h3>Formulir Tambah Penulis Baru</h3>
                </div>

                <!-- FORM: dikirim (POST) ke c_penulis.php?aksi=tambah. CATATAN BELAJAR: alamat action memakai path RELATIF (../../CONTROLLER/...). Karena halaman dibuka lewat clean URL /Aplikasi_Peminjaman_Buku/admin/tambahPenulis, dua tingkat naik berakhir di akar server (/CONTROLLER/...), bukan di folder aplikasi, sehingga kemungkinan besar 404. Form lain memakai path absolut /Aplikasi_Peminjaman_Buku/CONTROLLER/... yang aman. -->
                <form action="../../CONTROLLER/c_penulis.php?aksi=tambah" method="POST">

                    <div class="form-group">
                        <label for="nama_penulis">Nama Penulis</label>
                        <!-- Kolom input 'nama_penulis' (type=text) -> dibaca controller sebagai $_POST['nama_penulis']. -->
                        <input type="text" id="nama_penulis" name="nama_penulis" class="form-control" placeholder="Masukkan nama penulis" required>
                    </div>

                    <div class="action-group">
                        <!-- Tombol SUBMIT: mengirim form. -->
                        <button type="submit" class="btn btn-submit">Simpan</button>
                        <!-- CLEAN URL: Kembali ke Daftar Penulis -->
                        <a href="/Aplikasi_Peminjaman_Buku/admin/daftarPenulis" class="btn btn-cancel">Batal</a>
                    </div>

                </form>
            </div>
        </div>
    </div>

</body>

</html>