<?php
// =====================================================================
// FILE   : VIEW/AUTH/register.php  (URL: /register)
// FUNGSI : Halaman REGISTRASI akun siswa (form pendaftaran).
// ALUR   : submit form (POST) -> CONTROLLER/c_login.php?aksi=register -> validasi & simpan (role siswa) -> kembali ke login.
// =====================================================================
// Mengaktifkan session (penyimpanan data login sementara di server).
session_start();

if (isset($_SESSION['id_user'])) {
    header("Location: /Aplikasi_Peminjaman_Buku/VIEW/SISWA/daftarBuku.php");
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
    <title>Registrasi Siswa - Perpustakaan</title>
    <!-- Memuat CSS Bootstrap 5 (framework tampilan: grid, tombol, kartu, dsb). -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Memuat Font Awesome (kumpulan ikon, dipakai lewat class fa-...). -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<!-- BAGIAN BODY: seluruh isi yang tampil di layar. -->
<body class="bg-light d-flex align-items-center justify-content-center min-vh-100 py-5">

<div class="container" style="max-width: 450px;">

    <!-- PESAN ERROR (flash message): tampil bila controller mengisi $_SESSION['error']; setelah tampil dihapus (unset) agar tidak muncul lagi saat halaman dimuat ulang. -->
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show rounded-3 shadow-sm mb-3" role="alert">
            <!-- htmlspecialchars() mengubah karakter khusus HTML (< > " ') menjadi aman ditampilkan; ini pencegah XSS (penyisipan script lewat data). -->
            <i class="fa-solid fa-circle-exclamation me-2"></i><?= htmlspecialchars($_SESSION['error']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['error']); ?>
    <!-- Akhir kondisi if. -->
    <?php endif; ?>

    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="bg-dark text-white p-4 text-center">
            <i class="fa-solid fa-user-plus fs-2 text-primary mb-2"></i>
            <h4 class="fw-bold mb-0">Daftar Akun Siswa</h4>
        </div>
        <div class="card-body p-4">
            <!-- FORM: saat disubmit, data dikirim dengan method POST ke /Aplikasi_Peminjaman_Buku/CONTROLLER/c_login.php?aksi=register. Atribut name pada setiap input menjadi kunci $_POST['...'] di controller. -->
            <form action="/Aplikasi_Peminjaman_Buku/CONTROLLER/c_login.php?aksi=register" method="POST">
                
                <div class="mb-3">
                    <label class="form-label small fw-bold">NIS / NIP</label>
                    <!-- Kolom input 'nis_nip' (type=text) -> dibaca controller sebagai $_POST['nis_nip']. -->
                    <input type="text" name="nis_nip" class="form-control" placeholder="Masukkan NIS/NIP" required>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold">Username</label>
                    <!-- Kolom input 'username' (type=text) -> dibaca controller sebagai $_POST['username']. -->
                    <input type="text" name="username" class="form-control" placeholder="Masukkan Username" required>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold">Nama Lengkap</label>
                    <!-- Kolom input 'nama_lengkap' (type=text) -> dibaca controller sebagai $_POST['nama_lengkap']. -->
                    <input type="text" name="nama_lengkap" class="form-control" placeholder="Masukkan Nama Lengkap" required>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold">Kelas</label>
                    <!-- Kolom input 'kelas' (type=text) -> dibaca controller sebagai $_POST['kelas']. -->
                    <input type="text" name="kelas" class="form-control" placeholder="Masukkan Kelas" required>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold">Password</label>
                    <!-- Kolom input 'password' (type=password) -> dibaca controller sebagai $_POST['password']. -->
                    <input type="password" name="password" class="form-control" placeholder="Buat Password" required>
                </div>

                <div class="mb-4">
                    <label class="form-label small fw-bold">Konfirmasi Password</label>
                    <!-- Kolom input 'konfirmasi_password' (type=password) -> dibaca controller sebagai $_POST['konfirmasi_password']. -->
                    <input type="password" name="konfirmasi_password" class="form-control" placeholder="Ulangi Password" required>
                </div>

                <!-- Tombol SUBMIT: mengirim form. -->
                <button type="submit" class="btn btn-primary w-100 py-2 rounded-pill fw-bold">Daftar Sekarang</button>
            </form>

            <div class="text-center pt-3 mt-3 border-top">
                <!-- CLEAN URL: Mengarah ke /login -->
                <a href="/Aplikasi_Peminjaman_Buku/login" class="text-decoration-none small">Sudah punya akun? Login di sini</a>
            </div>
        </div>
    </div>
</div>

<!-- Memuat JavaScript Bootstrap (alert yang bisa ditutup, modal, dsb). -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>