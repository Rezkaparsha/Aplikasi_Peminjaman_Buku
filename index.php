<?php
session_start();

// Jika belum login, arahkan ke URL login bersih
if (!isset($_SESSION['id_user'])) {
    header("Location: /Aplikasi_Peminjaman_Buku/login");
    exit;
}

// Jika sudah login, arahkan ke dashboard/katalog masing-masing sesuai routing .htaccess Anda
if (isset($_SESSION['role'])) {
    if ($_SESSION['role'] === 'admin') {
        header("Location: /Aplikasi_Peminjaman_Buku/admin/dashboardAdmin");
    } else {
        header("Location: /Aplikasi_Peminjaman_Buku/siswa/dashboardSiswa");
    }
    exit;
}
?>