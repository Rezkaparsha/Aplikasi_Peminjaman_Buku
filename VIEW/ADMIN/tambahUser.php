<?php
// =====================================================================
// FILE   : VIEW/ADMIN/tambahUser.php  (URL: /admin/tambahUser)
// FUNGSI : FORM TAMBAH USER (admin atau siswa).
// ALUR   : submit (POST) -> CONTROLLER/c_user.php?aksi=tambah.
// =====================================================================
// Mulai session hanya jika belum aktif (mencegah error session already started).
if (session_status() === PHP_SESSION_NONE) {
    // Mengaktifkan session (penyimpanan data login sementara di server).
    session_start();
}

// Proteksi Akses Admin Terpusat
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

// Simpan URL lokasi controller aktif saat ini
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
    <title>Tambah User - Perpustakaan Online</title>
    <!-- CSS khusus halaman ini (warna, ukuran, tata letak); tidak dikomentari baris demi baris. -->
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

        /* Tampilan Alert Error */
        .alert-danger {
            background-color: #f8d7da;
            color: #842029;
            border: 1px solid #f5c2c7;
            padding: 12px 15px;
            border-radius: 6px;
            font-size: 13.5px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

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

        .btn-submit:disabled {
            background-color: #95a5a6;
            cursor: not-allowed;
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
            <h2>Tambah User Baru</h2>
            <div style="font-size: 14px; color: #7f8c8d;">Panel Admin</div>
        </div>

        <!-- Main Container -->
        <div class="container">
            <div class="card">
                <div class="card-header">
                    <h3>Formulir Registrasi User</h3>
                </div>

                <!-- ALERT NOTIFIKASI ERROR -->
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert-danger">
                        <!-- htmlspecialchars() mengubah karakter khusus HTML (< > " ') menjadi aman ditampilkan; ini pencegah XSS (penyisipan script lewat data). -->
                        <span>⚠️ <?= htmlspecialchars($_SESSION['error']) ?></span>
                    </div>
                    <?php unset($_SESSION['error']); ?>
                <!-- Akhir kondisi if. -->
                <?php endif; ?>

                <!-- FORM: saat disubmit, data dikirim dengan method POST ke /Aplikasi_Peminjaman_Buku/CONTROLLER/c_user.php?aksi=tambah. Atribut name pada setiap input menjadi kunci $_POST['...'] di controller. -->
                <form id="formTambahUser" action="/Aplikasi_Peminjaman_Buku/CONTROLLER/c_user.php?aksi=tambah" method="POST">

                    <div class="form-group">
                        <label for="nis_nip">NIS / NIP</label>
                        <!-- Kolom input 'nis_nip' (type=text) -> dibaca controller sebagai $_POST['nis_nip']. -->
                        <input type="text" id="nis_nip" name="nis_nip" class="form-control" placeholder="Masukkan NIS atau NIP (3-10 digit)" required>
                    </div>

                    <div class="form-group">
                        <label for="nama_lengkap">Nama Lengkap</label>
                        <!-- Kolom input 'nama_lengkap' (type=text) -> dibaca controller sebagai $_POST['nama_lengkap']. -->
                        <input type="text" id="nama_lengkap" name="nama_lengkap" class="form-control" placeholder="Masukkan nama lengkap (huruf saja)" required>
                    </div>

                    <div class="form-group">
                        <label for="kelas">Kelas</label>
                        <!-- Kolom input 'kelas' (type=text) -> dibaca controller sebagai $_POST['kelas']. -->
                        <input type="text" id="kelas" name="kelas" class="form-control" placeholder="Contoh: XII RPL 1">
                    </div>
                    
                    <div class="form-group">
                        <label for="username">Username</label>
                        <!-- Kolom input 'username' (type=text) -> dibaca controller sebagai $_POST['username']. -->
                        <input type="text" id="username" name="username" class="form-control" placeholder="Masukkan username akun" required>
                    </div>

                    <div class="form-group">
                        <label for="password">Password</label>
                        <!-- Kolom input 'password' (type=password) -> dibaca controller sebagai $_POST['password']. -->
                        <input type="password" id="password" name="password" class="form-control" placeholder="Masukkan password" required>
                    </div>

                    <div class="form-group">
                        <label for="role">Role / Akses</label>
                        <!-- Dropdown 'role': nilai opsi yang dipilih dikirim sebagai kunci 'role'. -->
                        <select id="role" name="role" class="form-control" required>
                            <option value="">-- Pilih Role --</option>
                            <option value="admin">Admin</option>
                            <option value="siswa">Siswa</option>
                        </select>
                    </div>

                    <div class="action-group">
                        <!-- Tombol SUBMIT: mengirim form. -->
                        <button type="submit" id="btnSubmit" class="btn btn-submit">Simpan User</button>
                        <!-- CLEAN URL: Kembali ke Daftar User -->
                        <a href="/Aplikasi_Peminjaman_Buku/admin/daftarUser" class="btn btn-cancel">Batal</a>
                    </div>

                </form>
            </div>
        </div>
    </div>

    <!-- SCRIPT MENCEGAH SPAM CLICK -->
    <script>
        // Saat form disubmit: tombol dinonaktifkan dan teksnya diganti 'Menyimpan...' supaya form tidak terkirim dua kali (double submit).
        document.getElementById('formTambahUser').addEventListener('submit', function() {
            // Ambil tombol submit.
            var btn = document.getElementById('btnSubmit');
            btn.disabled = true;
            btn.innerText = 'Menyimpan...';
        });
    </script>

</body>

</html>