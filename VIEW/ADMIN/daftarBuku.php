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

// 3. Panggil Model Langsung di View
require_once __DIR__ . "/../../MODEL/m_buku.php";
require_once __DIR__ . "/../../MODEL/m_kategori.php";
require_once __DIR__ . "/../../MODEL/m_penulis.php";
require_once __DIR__ . "/../../MODEL/m_penerbit.php";

$bukuModel = new Buku();
$kategoriModel = new Kategori();
$penulisModel = new Penulis();
$penerbitModel = new Penerbit();

// Ambil data untuk opsi dropdown filter
$daftarKategori = $kategoriModel->getAll();
$daftarPenulis  = $penulisModel->getAll();
$daftarPenerbit = $penerbitModel->getAll();

// Tangkap nilai input GET jika ada
$keyword     = trim($_GET['keyword'] ?? '');
$id_kategori = $_GET['id_kategori'] ?? '';
$id_penulis  = $_GET['id_penulis'] ?? '';
$id_penerbit = $_GET['id_penerbit'] ?? '';

// Eksekusi fungsi pencarian & filter
$daftarBuku = $bukuModel->cariDanFilterBuku($keyword, $id_kategori, $id_penulis, $id_penerbit);

// ==========================================
// LOGIKA PAGINATION (10 Data per Halaman)
// ==========================================
$limit = 10;
$total_data = count($daftarBuku);
$total_pages = ceil($total_data / $limit);

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
if ($page > $total_pages && $total_pages > 0) $page = $total_pages;

$offset = ($page - 1) * $limit;

// Potong array data sesuai halaman aktif
$bukuPaginated = array_slice($daftarBuku, $offset, $limit);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Buku - Perpustakaan Online</title>

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
        /* Scrollbar Table */
        .table-scroll::-webkit-scrollbar { height: 8px; }
        .table-scroll::-webkit-scrollbar-track { background: #f1f5f9; border-radius: 4px; }
        .table-scroll::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
        .table-scroll:hover::-webkit-scrollbar-thumb { background: #94a3b8; }
    </style>
</head>

<body class="bg-slate-50 flex h-screen overflow-hidden antialiased text-slate-800">

    <!-- Panggil Sidebar -->
    <?php include __DIR__ . "/../TEMPLATE/SideBarAdmin.php"; ?>

    <div class="flex-1 flex flex-col h-screen overflow-hidden">
        
        <!-- Topbar -->
        <div class="bg-white/80 backdrop-blur-md px-8 py-4 flex justify-between items-center shadow-sm border-b border-slate-200 z-10">
            <div>
                <h2 class="text-xl font-bold text-slate-800">Kelola Buku</h2>
                <p class="text-sm text-slate-500">Manajemen katalog buku perpustakaan</p>
            </div>
            <div class="hidden md:block">
                <a href="/Aplikasi_Peminjaman_Buku/admin/tambahBuku" class="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-lg shadow-lg shadow-blue-500/30 transition-all hover:-translate-y-0.5 flex items-center gap-2">
                    <i class="fas fa-plus"></i> Tambah Buku
                </a>
            </div>
        </div>

        <!-- Main Container -->
        <div class="p-8 flex-1 overflow-y-auto">
            
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                
                <!-- FILTER FORM -->
                <div class="p-6 bg-slate-50/50 border-b border-slate-200">
                    <form method="GET" action="/Aplikasi_Peminjaman_Buku/admin/daftarBuku" class="flex flex-col md:flex-row gap-4 items-center">
                        
                        <div class="relative flex-1 w-full">
                            <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-slate-400"></i>
                            <input type="text" name="keyword" class="w-full pl-10 pr-4 py-2.5 bg-white border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all" placeholder="Cari judul buku..." value="<?= htmlspecialchars($_GET['keyword'] ?? '') ?>">
                        </div>

                        <select name="id_kategori" class="w-full md:w-auto px-4 py-2.5 bg-white border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 outline-none transition-all">
                            <option value="">Semua Kategori</option>
                            <?php foreach ($daftarKategori as$kat): ?>
                                <option value="<?= $kat['id_kategori'] ?>" <?= (isset($_GET['id_kategori']) && $_GET['id_kategori'] ==$kat['id_kategori']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($kat['nama_kategori']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <select name="id_penulis" class="w-full md:w-auto px-4 py-2.5 bg-white border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 outline-none transition-all">
                            <option value="">Semua Penulis</option>
                            <?php foreach ($daftarPenulis as$pen): ?>
                                <option value="<?= $pen['id_penulis'] ?>" <?= (isset($_GET['id_penulis']) && $_GET['id_penulis'] ==$pen['id_penulis']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($pen['nama_penulis']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <select name="id_penerbit" class="w-full md:w-auto px-4 py-2.5 bg-white border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 outline-none transition-all">
                            <option value="">Semua Penerbit</option>
                            <?php foreach ($daftarPenerbit as$penb): ?>
                                <option value="<?= $penb['id_penerbit'] ?>" <?= (isset($_GET['id_penerbit']) && $_GET['id_penerbit'] ==$penb['id_penerbit']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($penb['nama_penerbit']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <div class="flex gap-2 w-full md:w-auto">
                            <button type="submit" class="flex-1 md:flex-none px-5 py-2.5 bg-slate-800 hover:bg-slate-900 text-white text-sm font-semibold rounded-xl transition-all shadow-md">
                                Filter
                            </button>
                            <a href="/Aplikasi_Peminjaman_Buku/admin/daftarBuku" class="flex-1 md:flex-none px-5 py-2.5 bg-white hover:bg-slate-100 text-slate-700 border border-slate-300 text-sm font-semibold rounded-xl transition-all text-center flex items-center justify-center gap-2">
                                <i class="fas fa-undo"></i>
                            </a>
                        </div>
                    </form>
                </div>

                <!-- TABLE -->
                <div class="w-full overflow-x-auto table-scroll">
                    <table class="w-full text-left border-collapse whitespace-nowrap">
                        <thead>
                            <tr class="bg-slate-100/50 text-slate-500 text-xs uppercase tracking-wider font-semibold border-b border-slate-200">
                                <th class="py-4 px-6 text-center">No</th>
                                <th class="py-4 px-6">Cover</th>
                                <th class="py-4 px-6">Judul Buku</th>
                                <th class="py-4 px-6">Penulis</th>
                                <th class="py-4 px-6">Penerbit</th>
                                <th class="py-4 px-6 text-center">Tahun</th>
                                <th class="py-4 px-6">Kategori</th>
                                <th class="py-4 px-6 text-right">Harga</th>
                                <th class="py-4 px-6 text-center">Stok</th>
                                <th class="py-4 px-6 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="text-sm divide-y divide-slate-100">
                            <?php if (!empty($bukuPaginated)): ?>
                                <?php $no =$offset + 1; ?>
                                <?php foreach ($bukuPaginated as$buku): ?>
                                    <tr class="hover:bg-slate-50/80 transition-colors group">
                                        <td class="py-3 px-6 text-center font-medium text-slate-500"><?= $no++ ?></td>
                                        <td class="py-3 px-6">
                                            <?php if (!empty($buku['cover'])): ?>
                                                <img src="/Aplikasi_Peminjaman_Buku/ASSETS/COVER/<?= htmlspecialchars($buku['cover']) ?>" class="w-12 h-16 object-cover rounded-md shadow-sm border border-slate-200 group-hover:shadow-md transition-shadow">
                                            <?php else: ?>
                                                <div class="w-12 h-16 bg-slate-100 rounded-md border border-slate-200 flex items-center justify-center text-xs text-slate-400">No Cover</div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="py-3 px-6 font-semibold text-slate-800 whitespace-normal min-w-[200px]"><?= htmlspecialchars($buku['judul_buku']) ?></td>
                                        <td class="py-3 px-6 text-slate-600"><?= htmlspecialchars($buku['daftar_penulis'] ?? ($buku['penulis'] ?? '-')) ?></td>
                                        <td class="py-3 px-6 text-slate-600"><?= htmlspecialchars($buku['nama_penerbit'] ?? '-') ?></td>
                                        <td class="py-3 px-6 text-center text-slate-600"><?= htmlspecialchars($buku['tahun_terbit'] ?? '-') ?></td>
                                        <td class="py-3 px-6">
                                            <span class="px-3 py-1 bg-blue-50 text-blue-600 rounded-full text-xs font-semibold border border-blue-100">
                                                <?= htmlspecialchars($buku['nama_kategori'] ?? '-') ?>
                                            </span>
                                        </td>
                                        <td class="py-3 px-6 text-right font-medium text-slate-700">Rp <?= number_format((int) $buku['harga_buku'], 0, ',', '.') ?></td>
                                        <td class="py-3 px-6 text-center">
                                            <span class="px-3 py-1 rounded-full text-xs font-bold <?= (int)$buku['stok'] > 0 ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?>">
                                                <?= htmlspecialchars($buku['stok']) ?>
                                            </span>
                                        </td>
                                        <td class="py-3 px-6 text-center">
                                            <div class="flex justify-center gap-2">
                                                <a href="/Aplikasi_Peminjaman_Buku/admin/editBuku?id_buku=<?= $buku['id_buku'] ?>" class="w-8 h-8 rounded-lg bg-sky-100 text-sky-600 flex items-center justify-center hover:bg-sky-500 hover:text-white transition-colors" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <button onclick="konfirmasiHapus(<?= $buku['id_buku'] ?>)" class="w-8 h-8 rounded-lg bg-red-100 text-red-600 flex items-center justify-center hover:bg-red-500 hover:text-white transition-colors" title="Hapus">
                                                    <i class="fas fa-trash-alt"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="10" class="py-12 text-center">
                                        <div class="flex flex-col items-center justify-center text-slate-400">
                                            <i class="fas fa-box-open text-4xl mb-3"></i>
                                            <p class="text-sm font-medium">Data buku tidak ditemukan.</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- PAGINATION -->
                <?php if (!empty($daftarBuku)): ?>
                    <div class="p-6 border-t border-slate-200 flex flex-col sm:flex-row justify-between items-center gap-4 bg-slate-50/30">
                        <p class="text-sm text-slate-500">
                            Menampilkan <span class="font-semibold text-slate-700"><?= count($bukuPaginated) ?></span> dari <span class="font-semibold text-slate-700"><?= $total_data ?></span> total buku
                        </p>

                        <?php if ($total_pages > 1): ?>
                            <div class="flex gap-1">
                                <?php if ($page > 1): ?>
                                    <a href="?page=<?= $page - 1 ?>" class="px-3 py-1.5 rounded-lg border border-slate-300 text-slate-600 text-sm font-medium hover:bg-slate-100 transition-colors">Prev</a>
                                <?php endif; ?>

                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <a href="?page=<?= $i ?>" class="px-3 py-1.5 rounded-lg text-sm font-medium transition-colors <?= ($i ===$page) ? 'bg-blue-600 text-white shadow-md' : 'border border-slate-300 text-slate-600 hover:bg-slate-100' ?>">
                                        <?= $i ?>
                                    </a>
                                <?php endfor; ?>

                                <?php if ($page <$total_pages): ?>
                                    <a href="?page=<?= $page + 1 ?>" class="px-3 py-1.5 rounded-lg border border-slate-300 text-slate-600 text-sm font-medium hover:bg-slate-100 transition-colors">Next</a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

            </div>
        </div>
    </div>

    <!-- JAVASCRIPT SWEETALERT UNTUK ALERT SESSION & KONFIRMASI HAPUS -->
    <script>
        // Tangkap Flash Message dari Session PHP menggunakan SweetAlert2 Toast
        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true
        });

        <?php if (isset($_SESSION['success'])): ?>
            Toast.fire({
                icon: 'success',
                title: '<?= htmlspecialchars($_SESSION['success']); ?>'
            });
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            Toast.fire({
                icon: 'error',
                title: '<?= htmlspecialchars($_SESSION['error']); ?>'
            });
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        // Fungsi Konfirmasi Hapus Modern
        function konfirmasiHapus(id) {
            Swal.fire({
                title: 'Hapus Buku Ini?',
                text: "Data buku beserta file cover akan dihapus secara permanen!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#64748b',
                confirmButtonText: '<i class="fas fa-trash-alt"></i> Ya, Hapus!',
                cancelButtonText: 'Batal',
                background: '#ffffff',
                backdrop: `rgba(0,0,0,0.4)`,
                customClass: {
                    confirmButton: 'rounded-lg',
                    cancelButton: 'rounded-lg'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = "/Aplikasi_Peminjaman_Buku/CONTROLLER/c_buku.php?aksi=hapus&id_buku=" + id;
                }
            });
        }
    </script>
</body>
</html>