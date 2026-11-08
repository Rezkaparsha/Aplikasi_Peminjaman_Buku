<?php
// =====================================================================
// FILE   : VIEW/AUTH/login.php  (URL: /login)
// FUNGSI : Halaman LOGIN (form NIS/NIP atau username + password).
// ALUR   : submit form (POST) -> CONTROLLER/c_login.php?aksi=login -> sukses: dashboard sesuai role; gagal: kembali ke sini dengan pesan error.
// Pesan error/sukses datang dari $_SESSION (flash message) yang diisi controller.
// =====================================================================
// Mengaktifkan session (penyimpanan data login sementara di server).
session_start();

// Jika user sudah login, arahkan langsung ke halaman masing-masing
if (isset($_SESSION['id_user'])) {
    // Jika yang login ternyata berrole 'admin' (nyasar ke halaman ini), kembalikan ke halamannya sendiri.
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
        header("Location: /Aplikasi_Peminjaman_Buku/CONTROLLER/c_peminjaman.php?aksi=admin");
        exit;
    } else {
        header("Location: /Aplikasi_Peminjaman_Buku/VIEW/SISWA/daftarBuku.php");
        exit;
    }
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
    <title>Login - Aplikasi Perpustakaan</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome Icon -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- CSS khusus halaman ini (warna, ukuran, tata letak); tidak dikomentari baris demi baris. -->
    <style>
        body {
            background-color: #f1f5f9;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .card-login {
            border: none;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            overflow: hidden;
        }
        .login-header {
            background: #1e293b;
            color: white;
            padding: 30px;
            text-align: center;
        }
    </style>
</head>
<!-- BAGIAN BODY: seluruh isi yang tampil di layar. -->
<body class="d-flex align-items-center justify-content-center min-vh-100 py-5">

<div class="container" style="max-width: 420px;">
    
    <!-- Alert Notifikasi Session -->
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show rounded-3 shadow-sm mb-3" role="alert">
            <!-- htmlspecialchars() mengubah karakter khusus HTML (< > " ') menjadi aman ditampilkan; ini pencegah XSS (penyisipan script lewat data). -->
            <i class="fa-solid fa-circle-exclamation me-2"></i><?= htmlspecialchars($_SESSION['error']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['error']); ?>
    <!-- Akhir kondisi if. -->
    <?php endif; ?>

    <!-- PESAN SUKSES (flash message): tampil bila controller mengisi $_SESSION['success']; langsung dihapus setelah tampil. -->
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show rounded-3 shadow-sm mb-3" role="alert">
            <i class="fa-solid fa-circle-check me-2"></i><?= htmlspecialchars($_SESSION['success']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['success']); ?>
    <!-- Akhir kondisi if. -->
    <?php endif; ?>

    <div class="card card-login">
        <div class="login-header">
            <i class="fa-solid fa-book-bookmark fs-1 text-primary mb-2"></i>
            <h4 class="fw-bold mb-1">E-PERPUS</h4>
            <p class="text-white-50 small mb-0">Silakan login ke akun Anda</p>
        </div>

        <div class="card-body p-4">
            <!-- FORM: saat disubmit, data dikirim dengan method POST ke /Aplikasi_Peminjaman_Buku/CONTROLLER/c_login.php?aksi=login. Atribut name pada setiap input menjadi kunci $_POST['...'] di controller. -->
            <form action="/Aplikasi_Peminjaman_Buku/CONTROLLER/c_login.php?aksi=login" method="POST">
                
                <div class="mb-3">
                    <label class="form-label small fw-semibold">NIS / NIP / Username</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light text-muted"><i class="fa-solid fa-user"></i></span>
                        <!-- Kolom input 'nis_nip' (type=text) -> dibaca controller sebagai $_POST['nis_nip']. -->
                        <input type="text" name="nis_nip" class="form-control" placeholder="Masukkan NIS/NIP" required autofocus>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label small fw-semibold">Password</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light text-muted"><i class="fa-solid fa-lock"></i></span>
                        <!-- Kolom input 'password' (type=password) -> dibaca controller sebagai $_POST['password']. -->
                        <input type="password" name="password" class="form-control" placeholder="Masukkan password" required>
                    </div>
                </div>

                <!-- Tombol SUBMIT: mengirim form. -->
                <button type="submit" class="btn btn-primary w-100 py-2 rounded-pill fw-bold mb-3">
                    <i class="fa-solid fa-right-to-bracket me-2"></i>Masuk
                </button>

            </form>

            <div class="text-center pt-3 border-top">
                <p class="small text-muted mb-0">Belum memiliki akun siswa?</p>
                <!-- CLEAN URL: Mengarah ke /register -->
                <a href="/Aplikasi_Peminjaman_Buku/register" class="fw-bold text-decoration-none">Daftar Akun Siswa Sekarang</a>
            </div>
        </div>
    </div>
</div>

<!-- Memuat JavaScript Bootstrap (alert yang bisa ditutup, modal, dsb). -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>