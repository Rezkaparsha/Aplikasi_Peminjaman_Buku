<?php
// =====================================================================
// FILE   : index.php  (pintu masuk aplikasi: http://localhost/Aplikasi_Peminjaman_Buku)
// FUNGSI : Hanya sebagai "penunjuk jalan". Tidak menampilkan halaman,
//          tapi mengarahkan (redirect) pengguna ke halaman yang tepat
//          sesuai status login dan role-nya.
// =====================================================================

// Mengaktifkan session. Session = penyimpanan data sementara di server
// per pengguna (misalnya id_user dan role setelah login).
// WAJIB dipanggil sebelum membaca/menulis $_SESSION.
session_start();

// Jika belum login (tidak ada 'id_user' di session)...
if (!isset($_SESSION['id_user'])) {
    // ...kirim ke halaman login. header("Location: ...") = perintah redirect.
    header("Location: /Aplikasi_Peminjaman_Buku/login");
    // exit = hentikan script SEKARANG, supaya kode di bawah tidak ikut jalan.
    exit;
}

// Sampai sini berarti sudah login. Cek role yang tersimpan saat login.
if (isset($_SESSION['role'])) {
    if ($_SESSION['role'] === 'admin') {
        // Admin diarahkan ke dashboard admin.
        header("Location: /Aplikasi_Peminjaman_Buku/admin/dashboardAdmin");
    } else {
        // Selain admin (berarti siswa) diarahkan ke dashboard siswa.
        header("Location: /Aplikasi_Peminjaman_Buku/siswa/dashboardSiswa");
    }
    exit; // hentikan script setelah redirect
}
?>
