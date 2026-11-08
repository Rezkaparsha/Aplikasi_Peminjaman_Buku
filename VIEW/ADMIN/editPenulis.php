<?php
// =====================================================================
// FILE   : VIEW/ADMIN/editPenulis.php  (URL: /admin/editPenulis?id_penulis=..)
// FUNGSI : FORM UBAH PENULIS.
// ALUR   : data lama diisikan ke form -> submit (POST) -> CONTROLLER/c_penulis.php?aksi=update.
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

// 2. Simpan URL lokasi controller aktif saat ini
$_SESSION['last_page_admin'] = $_SERVER['REQUEST_URI'];

// 3. Tangkap ID dari parameter URL
$idPenulis = (int)($_GET['id_penulis'] ?? 0);
if ($idPenulis <= 0) {
    $_SESSION['error'] = "ID Penulis tidak valid.";
    // Clean URL: Redirect ke Daftar Penulis
    header("Location: /Aplikasi_Peminjaman_Buku/admin/daftarPenulis");
    exit;
}

// 4. Panggil Model Data Penulis
require_once __DIR__ . "/../../MODEL/m_penulis.php";
// Buat objek dari class Penulis; variabel $penulisModel dipakai untuk memanggil fungsi-fungsinya.
$penulisModel = new Penulis();
// Panggil method getById() milik objek $penulisModel; hasilnya disimpan di $dataPenulis.
$dataPenulis = $penulisModel->getById($idPenulis);

if (!$dataPenulis) {
    $_SESSION['error'] = "Data penulis tidak ditemukan.";
    header("Location: /Aplikasi_Peminjaman_Buku/admin/daftarPenulis");
    exit;
}
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
    <title>Edit Penulis - Perpustakaan Online</title>
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
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
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
            background-color: #3498db;
            color: white;
            flex: 1;
        }
        .btn-submit:hover { background-color: #2980b9; }

        .btn-cancel {
            background-color: #e74c3c;
            color: white;
            flex: 1;
        }
        .btn-cancel:hover { background-color: #c0392b; }

        /* Error Alert */
        .alert-error {
            background-color: #fdeaea;
            color: #c0392b;
            padding: 15px;
            border-radius: 5px;
            text-align: center;
            margin-bottom: 20px;
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
            <h2>Edit Data Penulis</h2>
            <div style="font-size: 14px; color: #7f8c8d;">Panel Admin</div>
        </div>

        <!-- Main Container -->
        <div class="container">
            <div class="card">
                <div class="card-header">
                    <h3>Formulir Perubahan Penulis</h3>
                </div>

                <!-- KONDISI: blok di bawah (sampai else/endif) hanya tampil jika !empty($dataPenulis) -->
                <?php if (!empty($dataPenulis)): ?>

                    <!-- FORM: saat disubmit, data dikirim dengan method POST ke /Aplikasi_Peminjaman_Buku/CONTROLLER/c_penulis.php?aksi=update. Atribut name pada setiap input menjadi kunci $_POST['...'] di controller. -->
                    <form action="/Aplikasi_Peminjaman_Buku/CONTROLLER/c_penulis.php?aksi=update" method="POST">
                        
                        <!-- Input TERSEMBUNYI 'id_penulis': membawa data ke controller tanpa ditampilkan ($_POST['id_penulis']). -->
                        <input type="hidden" name="id_penulis" value="<?= htmlspecialchars($dataPenulis['id_penulis']) ?>">

                        <div class="form-group">
                            <label for="nama_penulis">Nama Penulis</label>
                            <!-- Kolom input 'nama_penulis' (type=text) -> dibaca controller sebagai $_POST['nama_penulis']. -->
                            <input type="text" id="nama_penulis" name="nama_penulis" class="form-control" value="<?= htmlspecialchars($dataPenulis['nama_penulis']) ?>" required>
                        </div>

                        <div class="action-group">
                            <!-- Tombol SUBMIT: mengirim form. -->
                            <button type="submit" class="btn btn-submit">Simpan Perubahan</button>
                            <!-- CLEAN URL: Kembali ke Daftar Penulis -->
                            <a href="/Aplikasi_Peminjaman_Buku/admin/daftarPenulis" class="btn btn-cancel">Batal</a>
                        </div>

                    </form>

                <!-- ELSE: blok di bawah tampil jika kondisi di atasnya TIDAK terpenuhi. -->
                <?php else: ?>

                    <div class="alert-error">Data penulis tidak ditemukan.</div>
                    <div style="text-align: center;">
                        <!-- CLEAN URL: Kembali ke Daftar Penulis -->
                        <a href="/Aplikasi_Peminjaman_Buku/admin/daftarPenulis" class="btn btn-cancel" style="display: inline-block;">Kembali ke Daftar Penulis</a>
                    </div>

                <!-- Akhir kondisi if. -->
                <?php endif; ?>

            </div>
        </div>
    </div>

</body>
</html>