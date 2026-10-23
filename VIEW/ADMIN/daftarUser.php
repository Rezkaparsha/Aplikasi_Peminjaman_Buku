<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Proteksi Akses Admin Terpusat
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'admin') {
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'siswa') {
        $kembali = $_SESSION['last_page_siswa'] ?? '/Aplikasi_Peminjaman_Buku/siswa/dashboardSiswa';
        header("Location: " . $kembali);
        exit;
    }
    header("Location: /Aplikasi_Peminjaman_Buku/login");
    exit;
}

// 2. Simpan URL lokasi halaman view ini
$_SESSION['last_page_admin'] = $_SERVER['REQUEST_URI'];

// 3. Panggil Model Users
require_once __DIR__ . "/../../MODEL/m_users.php";
$userModel = new Users();

// ==========================================
// LOGIKA PENCARIAN & AMBIL DATA
// ==========================================
$keyword = trim($_GET['search'] ?? '');

if (!empty($keyword)) {
    // Gunakan fungsi search jika ada inputan pencarian
    $daftarUser = $userModel->search($keyword);
} else {
    // Gunakan getAll jika tidak ada pencarian
    $daftarUser = $userModel->getAll();
}

// ==========================================
// LOGIKA PAGINATION (10 Data per Halaman)
// ==========================================
$limit = 10;
$total_data = count($daftarUser);
$total_pages = ceil($total_data / $limit);

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
if ($page > $total_pages && $total_pages > 0) $page = $total_pages;

$offset = ($page - 1) * $limit;

// Potong array data sesuai halaman aktif
$userPaginated = array_slice($daftarUser, $offset, $limit);

// Fungsi untuk menyambungkan query string search pada pagination
$searchQuery = !empty($keyword) ? '&search=' . urlencode($keyword) : '';
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar User - Perpustakaan Online</title>
    
    <!-- ASSETS PENDUKUNG -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body class="flex h-screen bg-slate-50 font-sans text-slate-800 antialiased overflow-hidden">

    <!-- Sidebar Admin -->
    <?php include __DIR__ . "/../TEMPLATE/SideBarAdmin.php"; ?>

    <!-- Main Content -->
    <div class="flex-1 flex flex-col overflow-hidden">
        
        <!-- Topbar -->
        <header class="bg-white border-b border-slate-200 px-6 py-4 flex justify-between items-center shadow-sm shrink-0">
            <div>
                <h2 class="text-xl font-bold text-slate-800">Kelola Data User</h2>
                <div class="text-xs text-sky-500 font-semibold tracking-wider uppercase mt-0.5">Panel Admin</div>
            </div>
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-full bg-slate-200 flex items-center justify-center text-slate-600">
                    <i class="fas fa-user-shield"></i>
                </div>
            </div>
        </header>

        <!-- Container (Scrollable) -->
        <main class="flex-1 overflow-y-auto p-6">
            
            <!-- ALERT NOTIFIKASI -->
            <?php if (isset($_SESSION['error'])): ?>
                <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded-r-lg flex items-center gap-3 shadow-sm">
                    <div class="text-red-500"><i class="fas fa-exclamation-circle text-xl"></i></div>
                    <div class="text-red-700 font-medium text-sm"><?= htmlspecialchars($_SESSION['error']) ?></div>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="mb-6 bg-emerald-50 border-l-4 border-emerald-500 p-4 rounded-r-lg flex items-center gap-3 shadow-sm">
                    <div class="text-emerald-500"><i class="fas fa-check-circle text-xl"></i></div>
                    <div class="text-emerald-700 font-medium text-sm"><?= htmlspecialchars($_SESSION['success']) ?></div>
                </div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>

            <!-- Card Utama -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200">
                
                <!-- Header Card (Pencarian & Tombol Tambah) -->
                <div class="p-6 border-b border-slate-100 flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4">
                    <div>
                        <h3 class="text-lg font-bold text-slate-800">Daftar Pengguna Sistem</h3>
                        <p class="text-sm text-slate-500 mt-1">Kelola data akun siswa, guru, dan admin yang memiliki akses ke aplikasi.</p>
                    </div>
                    
                    <div class="flex flex-wrap items-center gap-3 w-full lg:w-auto">
                        <!-- Form Pencarian -->
                        <form action="" method="GET" class="flex items-center relative w-full lg:w-64">
                            <i class="fas fa-search absolute left-3 text-slate-400 text-sm"></i>
                            <input type="text" name="search" value="<?= htmlspecialchars($keyword) ?>" placeholder="Cari NIS/Nama/Username..." 
                                class="w-full pl-9 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:bg-white transition-all">
                            
                            <!-- Tombol Reset Pencarian (Muncul jika ada pencarian) -->
                            <?php if (!empty($keyword)): ?>
                                <a href="?" class="absolute right-3 text-slate-400 hover:text-red-500 transition-colors">
                                    <i class="fas fa-times"></i>
                                </a>
                            <?php endif; ?>
                        </form>
                        <button type="button" onclick="document.querySelector('form').submit()" class="hidden"></button>

                        <!-- Tombol Tambah User -->
                        <a href="/Aplikasi_Peminjaman_Buku/admin/tambahUser" class="whitespace-nowrap px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-lg shadow-sm shadow-blue-600/20 transition-all flex items-center gap-2">
                            <i class="fas fa-plus"></i> Tambah User
                        </a>
                    </div>
                </div>

                <!-- Tabel Data -->
                <div class="overflow-x-auto w-full">
                    <table class="w-full text-left border-collapse whitespace-nowrap">
                        <thead>
                            <tr class="bg-slate-50/50 text-slate-500 text-[11px] uppercase tracking-wider border-b border-slate-200">
                                <th class="px-6 py-4 font-semibold w-16 text-center">No</th>
                                <th class="px-6 py-4 font-semibold">NIS/NIP</th>
                                <th class="px-6 py-4 font-semibold">Nama Lengkap</th>
                                <th class="px-6 py-4 font-semibold">Kelas</th>
                                <th class="px-6 py-4 font-semibold">Username</th>
                                <th class="px-6 py-4 font-semibold text-center">Role</th>
                                <th class="px-6 py-4 font-semibold text-center w-32">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-sm">
                            <?php if (!empty($userPaginated)): ?>
                                <?php $no = $offset + 1; ?>
                                <?php foreach ($userPaginated as $user): ?>
                                    <tr class="hover:bg-slate-50/70 transition-colors">
                                        <td class="px-6 py-4 text-center text-slate-500"><?= $no++ ?></td>
                                        <td class="px-6 py-4 font-bold text-slate-700"><?= htmlspecialchars($user['nis_nip'] ?? '-') ?></td>
                                        <td class="px-6 py-4 text-slate-600 font-medium"><?= htmlspecialchars($user['nama_lengkap']) ?></td>
                                        <td class="px-6 py-4 text-slate-500"><?= htmlspecialchars($user['kelas'] ?? '-') ?></td>
                                        <td class="px-6 py-4 text-slate-500"><?= htmlspecialchars($user['username']) ?></td>
                                        <td class="px-6 py-4 text-center">
                                            <?php
                                                // Tentukan warna badge berdasarkan role
                                                $role = strtolower($user['role']);
                                                $badgeClass = 'bg-slate-100 text-slate-600';
                                                
                                                if ($role === 'admin') {
                                                    $badgeClass = 'bg-red-100 text-red-600';
                                                } elseif ($role === 'siswa') {
                                                    $badgeClass = 'bg-blue-100 text-blue-600';
                                                } elseif ($role === 'guru') {
                                                    $badgeClass = 'bg-amber-100 text-amber-700';
                                                }
                                            ?>
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-md text-[11px] font-bold uppercase tracking-wider <?= $badgeClass ?>">
                                                <?= htmlspecialchars($user['role']) ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <!-- MENGHILANGKAN EFEK DISEMBUNYIKAN AGAR TOMBOL SELALU MUNCUL -->
                                            <div class="flex items-center justify-center gap-2">
                                                <!-- Tombol Edit -->
                                                <a href="/Aplikasi_Peminjaman_Buku/admin/editUser?id_user=<?= $user['id_user'] ?>" 
                                                   class="w-8 h-8 flex items-center justify-center rounded-lg bg-emerald-50 text-emerald-600 hover:bg-emerald-500 hover:text-white transition-colors border border-emerald-100" title="Edit Data">
                                                    <i class="fas fa-edit text-sm"></i>
                                                </a>
                                                <!-- Tombol Hapus -->
                                                <button type="button" onclick="konfirmasiHapus(<?= $user['id_user'] ?>)" 
                                                        class="w-8 h-8 flex items-center justify-center rounded-lg bg-red-50 text-red-600 hover:bg-red-500 hover:text-white transition-colors border border-red-100" title="Hapus Data">
                                                    <i class="fas fa-trash-alt text-sm"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="px-6 py-12 text-center text-slate-400">
                                        <div class="flex flex-col items-center justify-center">
                                            <i class="fas fa-folder-open text-4xl mb-3 text-slate-300"></i>
                                            <p class="font-medium">Tidak ada data pengguna ditemukan.</p>
                                            <?php if (!empty($keyword)): ?>
                                                <p class="text-xs mt-1">Coba gunakan kata kunci lain untuk mencari.</p>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Footer Card (Informasi Data & Pagination) -->
                <?php if (!empty($daftarUser)): ?>
                    <div class="p-5 border-t border-slate-100 bg-slate-50/50 flex flex-col md:flex-row justify-between items-center gap-4 rounded-b-2xl">
                        <div class="text-sm text-slate-500">
                            Menampilkan <span class="font-bold text-slate-700"><?= count($userPaginated) ?></span> dari <span class="font-bold text-slate-700"><?= $total_data ?></span> total pengguna
                        </div>

                        <!-- Kontrol Pagination -->
                        <?php if ($total_pages > 1): ?>
                            <div class="flex items-center gap-1 bg-white p-1 rounded-lg border border-slate-200 shadow-sm">
                                <!-- Tombol Prev -->
                                <?php if ($page > 1): ?>
                                    <a href="?page=<?= $page - 1 ?><?= $searchQuery ?>" class="px-3 py-1.5 text-sm font-medium text-slate-500 hover:text-blue-600 hover:bg-blue-50 rounded-md transition-colors">Prev</a>
                                <?php else: ?>
                                    <span class="px-3 py-1.5 text-sm font-medium text-slate-300 cursor-not-allowed">Prev</span>
                                <?php endif; ?>

                                <!-- Angka Halaman -->
                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <?php if ($i === $page): ?>
                                        <span class="px-3 py-1.5 text-sm font-bold bg-blue-600 text-white rounded-md shadow-sm"><?= $i ?></span>
                                    <?php else: ?>
                                        <a href="?page=<?= $i ?><?= $searchQuery ?>" class="px-3 py-1.5 text-sm font-medium text-slate-600 hover:bg-slate-100 rounded-md transition-colors"><?= $i ?></a>
                                    <?php endif; ?>
                                <?php endfor; ?>

                                <!-- Tombol Next -->
                                <?php if ($page < $total_pages): ?>
                                    <a href="?page=<?= $page + 1 ?><?= $searchQuery ?>" class="px-3 py-1.5 text-sm font-medium text-slate-500 hover:text-blue-600 hover:bg-blue-50 rounded-md transition-colors">Next</a>
                                <?php else: ?>
                                    <span class="px-3 py-1.5 text-sm font-medium text-slate-300 cursor-not-allowed">Next</span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

            </div>
        </main>
    </div>

    <!-- Script SweetAlert2 untuk Konfirmasi Hapus -->
    <script>
        function konfirmasiHapus(id_user) {
            Swal.fire({
                title: 'Hapus User?',
                text: "Data user ini akan dihapus permanen. Tindakan ini tidak dapat dibatalkan!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#94a3b8',
                confirmButtonText: '<i class="fas fa-trash-alt mr-1"></i> Ya, Hapus',
                cancelButtonText: 'Batal',
                reverseButtons: true,
                borderRadius: '1rem'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = "/Aplikasi_Peminjaman_Buku/CONTROLLER/c_user.php?aksi=hapus&id_user=" + id_user;
                }
            });
        }
    </script>

</body>
</html>