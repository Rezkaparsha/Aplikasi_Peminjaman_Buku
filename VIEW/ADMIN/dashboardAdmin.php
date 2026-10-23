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

// Panggil Model yang dibutuhkan
require_once __DIR__ . "/../../MODEL/m_buku.php";
require_once __DIR__ . "/../../MODEL/m_users.php";
require_once __DIR__ . "/../../MODEL/m_peminjaman.php";

$bukuModel = new Buku();
$userModel = new Users();
$peminjamanModel = new M_Peminjaman();
$totalBelumKembali = $peminjamanModel->getJumlahBelumKembali();

// Ambil total data untuk ditampilkan di kartu statistik
$totalBuku = count($bukuModel->getAllBuku() ?? []);
$totalUser = count($userModel->getAll() ?? []);
$totalPeminjaman = count($peminjamanModel->getSemuaPeminjaman() ?? []);

// Jika ada session nama user, kita tampilkan. Jika tidak, pakai default Admin.
$namaAdmin = $_SESSION['nama'] ?? 'Administrator';
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - Perpustakaan Online</title>

    <!-- Google Fonts: Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>

<body class="bg-slate-50 flex h-screen overflow-hidden antialiased text-slate-800">

    <?php include __DIR__ . "/../TEMPLATE/SideBarAdmin.php"; ?>

    <!-- MAIN CONTENT -->
    <div class="flex-1 flex flex-col h-screen overflow-hidden">
        
        <!-- TOPBAR -->
        <div class="bg-white/80 backdrop-blur-md px-8 py-4 flex justify-between items-center shadow-sm border-b border-slate-200 z-10">
            <div>
                <h2 class="text-xl font-bold text-slate-800">Dashboard</h2>
                <p class="text-sm text-slate-500">Ringkasan aktivitas perpustakaan hari ini</p>
            </div>
            <div class="hidden md:flex items-center gap-3 bg-slate-50 px-4 py-2 rounded-full border border-slate-200">
                <span class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></span>
                <span class="text-sm font-medium text-slate-600">Halo, <strong class="text-blue-600"><?= htmlspecialchars($namaAdmin) ?></strong></span>
            </div>
        </div>

        <!-- CONTENT -->
        <div class="p-8 flex-1 overflow-y-auto">

            <!-- STATS CARDS -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <!-- Card 1 -->
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 flex justify-between items-center border-b-4 border-b-blue-500 transition-all duration-300 hover:-translate-y-1 hover:shadow-lg hover:shadow-blue-500/10 group">
                    <div>
                        <h4 class="text-sm font-semibold text-slate-500 mb-1">Total Buku</h4>
                        <h1 class="text-3xl font-bold text-slate-800 group-hover:text-blue-600 transition-colors"><?= $totalBuku ?></h1>
                    </div>
                    <div class="w-14 h-14 rounded-full bg-blue-50 flex items-center justify-center text-blue-500 text-2xl group-hover:scale-110 transition-transform">
                        <i class="fas fa-book"></i>
                    </div>
                </div>

                <!-- Card 2 -->
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 flex justify-between items-center border-b-4 border-b-green-500 transition-all duration-300 hover:-translate-y-1 hover:shadow-lg hover:shadow-green-500/10 group">
                    <div>
                        <h4 class="text-sm font-semibold text-slate-500 mb-1">Pengguna Aktif</h4>
                        <h1 class="text-3xl font-bold text-slate-800 group-hover:text-green-600 transition-colors"><?= $totalUser ?></h1>
                    </div>
                    <div class="w-14 h-14 rounded-full bg-green-50 flex items-center justify-center text-green-500 text-2xl group-hover:scale-110 transition-transform">
                        <i class="fas fa-users"></i>
                    </div>
                </div>

                <!-- Card 3 -->
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 flex justify-between items-center border-b-4 border-b-orange-500 transition-all duration-300 hover:-translate-y-1 hover:shadow-lg hover:shadow-orange-500/10 group">
                    <div>
                        <h4 class="text-sm font-semibold text-slate-500 mb-1">Total Transaksi</h4>
                        <h1 class="text-3xl font-bold text-slate-800 group-hover:text-orange-600 transition-colors"><?= $totalPeminjaman ?></h1>
                    </div>
                    <div class="w-14 h-14 rounded-full bg-orange-50 flex items-center justify-center text-orange-500 text-2xl group-hover:scale-110 transition-transform">
                        <i class="fas fa-exchange-alt"></i>
                    </div>
                </div>

                <!-- Card 4 -->
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 flex justify-between items-center border-b-4 border-b-purple-500 transition-all duration-300 hover:-translate-y-1 hover:shadow-lg hover:shadow-purple-500/10 group">
                    <div>
                        <h4 class="text-sm font-semibold text-slate-500 mb-1">Buku Belum Kembali</h4>
                        <h1 class="text-3xl font-bold text-slate-800 group-hover:text-purple-600 transition-colors"><?= $totalBelumKembali ?></h1>
                    </div>
                    <div class="w-14 h-14 rounded-full bg-purple-50 flex items-center justify-center text-purple-500 text-2xl group-hover:scale-110 transition-transform">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                </div>
            </div>

            <!-- QUICK ACTIONS -->
            <div>
                <h3 class="text-lg font-bold text-slate-800 mb-4 flex items-center gap-2"><i class="fas fa-bolt text-yellow-500"></i> Tindakan Cepat</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    
                    <!-- Action 1 -->
                    <a href="/Aplikasi_Peminjaman_Buku/admin/tambahBuku" class="block bg-white p-6 rounded-2xl border border-slate-200 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:border-blue-500 hover:shadow-xl hover:shadow-blue-500/10 group">
                        <div class="flex items-center gap-4 mb-3">
                            <div class="w-10 h-10 rounded-lg bg-blue-100 text-blue-600 flex items-center justify-center group-hover:bg-blue-600 group-hover:text-white transition-colors">
                                <i class="fas fa-plus"></i>
                            </div>
                            <h4 class="font-bold text-slate-800 group-hover:text-blue-600 transition-colors">Tambah Buku Baru</h4>
                        </div>
                        <p class="text-sm text-slate-500">Input data buku baru beserta cover ke dalam katalog perpustakaan.</p>
                    </a>

                    <!-- Action 2 -->
                    <a href="/Aplikasi_Peminjaman_Buku/admin/daftarPeminjaman" class="block bg-white p-6 rounded-2xl border border-slate-200 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:border-orange-500 hover:shadow-xl hover:shadow-orange-500/10 group">
                        <div class="flex items-center gap-4 mb-3">
                            <div class="w-10 h-10 rounded-lg bg-orange-100 text-orange-600 flex items-center justify-center group-hover:bg-orange-600 group-hover:text-white transition-colors">
                                <i class="fas fa-clipboard-check"></i>
                            </div>
                            <h4 class="font-bold text-slate-800 group-hover:text-orange-600 transition-colors">Cek Peminjaman</h4>
                        </div>
                        <p class="text-sm text-slate-500">Tinjau, setujui permintaan, atau proses pengembalian buku dari siswa.</p>
                    </a>

                    <!-- Action 3 -->
                    <a href="/Aplikasi_Peminjaman_Buku/admin/tambahUser" class="block bg-white p-6 rounded-2xl border border-slate-200 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:border-green-500 hover:shadow-xl hover:shadow-green-500/10 group">
                        <div class="flex items-center gap-4 mb-3">
                            <div class="w-10 h-10 rounded-lg bg-green-100 text-green-600 flex items-center justify-center group-hover:bg-green-600 group-hover:text-white transition-colors">
                                <i class="fas fa-user-plus"></i>
                            </div>
                            <h4 class="font-bold text-slate-800 group-hover:text-green-600 transition-colors">Daftarkan User</h4>
                        </div>
                        <p class="text-sm text-slate-500">Tambahkan akun login baru untuk siswa atau staf guru.</p>
                    </a>

                </div>
            </div>

        </div>
    </div>

</body>
</html>