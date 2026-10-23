<?php
// Panggil Model yang dibutuhkan
require_once __DIR__ . "/../../MODEL/m_buku.php";
require_once __DIR__ . "/../../MODEL/m_users.php";
require_once __DIR__ . "/../../MODEL/m_peminjaman.php";

$namaAdmin = $_SESSION['nama_lengkap'] ?? $_SESSION['nama'] ?? 'Administrator';
$currentUri = $_SERVER['REQUEST_URI'];

// FUNGSI CEK MENU AKTIF (BEBAS ERROR VERT & LEBIH AKURAT)
if (!function_exists('isMenuAktif')) {
    function isMenuAktif($uri, $keywords) {
        foreach ($keywords as $key) {
            if (strpos($uri, $key) !== false) {
                return true;
            }
        }
        return false;
    }
}

// LOGIKA MENU AKTIF YANG DIPERBAIKI
// Menggunakan nama rute/file spesifik agar tidak bentrok dengan nama folder "Aplikasi_Peminjaman_Buku"
$aktifDashboard  = isMenuAktif($currentUri, ['dashboardAdmin']);
$aktifPeminjaman = isMenuAktif($currentUri, ['daftarPeminjaman', 'detailPeminjaman']);
$aktifBuku       = isMenuAktif($currentUri, ['daftarBuku', 'tambahBuku', 'editBuku']);
$aktifKategori   = isMenuAktif($currentUri, ['daftarKategori', 'tambahKategori', 'editKategori']);
$aktifPenerbit   = isMenuAktif($currentUri, ['daftarPenerbit', 'tambahPenerbit', 'editPenerbit']);
$aktifPenulis    = isMenuAktif($currentUri, ['daftarPenulis', 'tambahPenulis', 'editPenulis']);
$aktifUser       = isMenuAktif($currentUri, ['daftarUser', 'tambahUser', 'editUser']);
$aktifHistori    = isMenuAktif($currentUri, ['historiTransaksi']);
?>

<!-- ASSETS PENDUKUNG -->
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
    /* CSS KUNCI MUTLAK (ANTI-BERANTAKAN) */
    .sidebar-permanen {
        width: 260px !important;
        min-width: 260px !important;
        max-width: 260px !important;
        flex: 0 0 260px !important;
        height: 100vh !important;
        background-color: #0b1121 !important;
        display: flex !important;
        flex-direction: column !important;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif !important;
        border-right: 1px solid #1e293b !important;
        user-select: none !important;
    }

    .sidebar-header {
        padding: 20px !important;
        display: flex !important;
        align-items: center !important;
        gap: 15px !important;
        border-bottom: 1px solid #1e293b !important;
        flex-shrink: 0 !important;
    }

    .sidebar-menu-list {
        flex: 1 !important;
        overflow-y: auto !important;
        padding: 15px !important;
        display: flex !important;
        flex-direction: column !important;
        gap: 5px !important;
    }

    .sidebar-menu-list::-webkit-scrollbar { width: 5px; }
    .sidebar-menu-list::-webkit-scrollbar-track { background: transparent; }
    .sidebar-menu-list::-webkit-scrollbar-thumb { background: #334155; border-radius: 10px; }

    .menu-item {
        display: flex !important;
        align-items: center !important;
        gap: 15px !important;
        padding: 12px 18px !important;
        border-radius: 10px !important;
        text-decoration: none !important;
        color: #94a3b8 !important;
        font-size: 14px !important;
        font-weight: 500 !important;
        transition: 0.3s !important;
    }

    .menu-item:hover {
        background-color: #1e293b !important;
        color: #ffffff !important;
    }

    .menu-item.active {
        background-color: #3b82f6 !important;
        color: #ffffff !important;
        box-shadow: 0 4px 15px rgba(59, 130, 246, 0.4) !important;
    }

    .menu-icon {
        width: 20px !important;
        text-align: center !important;
        font-size: 16px !important;
    }

    .sidebar-footer {
        padding: 20px !important;
        border-top: 1px solid #1e293b !important;
        flex-shrink: 0 !important;
    }

    .profil-box {
        background-color: #1e293b !important;
        padding: 12px !important;
        border-radius: 10px !important;
        display: flex !important;
        align-items: center !important;
        gap: 12px !important;
        margin-bottom: 15px !important;
    }

    .logout-btn {
        background-color: #3f1a1e !important;
        color: #fca5a5 !important;
        border: 1px solid #7f1d1d !important;
        width: 100% !important;
        padding: 12px !important;
        border-radius: 10px !important;
        text-align: center !important;
        font-size: 14px !important;
        font-weight: bold !important;
        cursor: pointer !important;
        transition: 0.3s !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        gap: 8px !important;
    }

    .logout-btn:hover {
        background-color: #7f1d1d !important;
        color: #ffffff !important;
    }
</style>

<aside class="sidebar-permanen">
    
    <!-- HEADER -->
    <div class="sidebar-header">
        <div style="background-color: #3b82f6; color: white; width: 38px; height: 38px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 18px; flex-shrink: 0;">
            <i class="fas fa-book"></i>
        </div>
        <div style="overflow: hidden;">
            <h3 style="color: white; font-size: 15px; font-weight: bold; margin: 0; line-height: 1.2;">Perpustakaan</h3>
            <span style="color: #38bdf8; font-size: 10px; font-weight: bold; letter-spacing: 1px; text-transform: uppercase;">Panel Admin</span>
        </div>
    </div>

    <!-- MENU LIST -->
    <div class="sidebar-menu-list">
        <a href="/Aplikasi_Peminjaman_Buku/admin/dashboardAdmin" class="menu-item <?= $aktifDashboard ? 'active' : '' ?>">
            <i class="fas fa-chart-pie menu-icon"></i> Dashboard
        </a>
        
        <a href="/Aplikasi_Peminjaman_Buku/admin/daftarPeminjaman" class="menu-item <?= $aktifPeminjaman ? 'active' : '' ?>">
            <i class="fas fa-exchange-alt menu-icon"></i> Kelola Peminjaman
        </a>
        
        <a href="/Aplikasi_Peminjaman_Buku/admin/daftarBuku" class="menu-item <?= $aktifBuku ? 'active' : '' ?>">
            <i class="fas fa-book menu-icon"></i> Kelola Buku
        </a>
        
        <a href="/Aplikasi_Peminjaman_Buku/admin/daftarKategori" class="menu-item <?= $aktifKategori ? 'active' : '' ?>">
            <i class="fas fa-tags menu-icon"></i> Kategori Buku
        </a>
        
        <a href="/Aplikasi_Peminjaman_Buku/admin/daftarPenerbit" class="menu-item <?= $aktifPenerbit ? 'active' : '' ?>">
            <i class="fas fa-building menu-icon"></i> Penerbit
        </a>
        
        <a href="/Aplikasi_Peminjaman_Buku/admin/daftarPenulis" class="menu-item <?= $aktifPenulis ? 'active' : '' ?>">
            <i class="fas fa-pen-nib menu-icon"></i> Penulis
        </a>
        
        <a href="/Aplikasi_Peminjaman_Buku/admin/daftarUser" class="menu-item <?= $aktifUser ? 'active' : '' ?>">
            <i class="fas fa-users menu-icon"></i> Kelola User
        </a>
        
        <a href="/Aplikasi_Peminjaman_Buku/admin/historiTransaksi" class="menu-item <?= $aktifHistori ? 'active' : '' ?>">
            <i class="fas fa-history menu-icon"></i> Histori Transaksi
        </a>
    </div>

    <!-- FOOTER (PROFIL & LOGOUT) -->
    <div class="sidebar-footer">
        <div class="profil-box">
            <div style="background-color: #38bdf8; color: white; width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 14px; font-weight: bold; flex-shrink: 0;">
                <?= strtoupper(substr($namaAdmin, 0, 1)) ?>
            </div>
            <div style="overflow: hidden;">
                <div style="color: white; font-size: 13px; font-weight: bold; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?= htmlspecialchars($namaAdmin) ?></div>
                <div style="color: #38bdf8; font-size: 11px;">Administrator</div>
            </div>
        </div>
        
        <button onclick="konfirmasiLogout(event)" class="logout-btn">
            <i class="fas fa-sign-out-alt"></i> Logout
        </button>
    </div>
</aside>

<!-- SCRIPT LOGOUT SWEETALERT -->
<script>
    function konfirmasiLogout(e) {
        if (e) e.preventDefault();
        
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Akhiri Sesi?',
                text: "Anda akan keluar dari Panel Admin Perpustakaan.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#475569',
                confirmButtonText: 'Ya, Keluar',
                cancelButtonText: 'Batal',
                background: '#1e293b',
                color: '#f8fafc'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = "/Aplikasi_Peminjaman_Buku/logout";
                }
            });
        } else {
            if (confirm("Apakah Anda yakin ingin logout?")) {
                window.location.href = "/Aplikasi_Peminjaman_Buku/logout";
            }
        }
    }
</script>