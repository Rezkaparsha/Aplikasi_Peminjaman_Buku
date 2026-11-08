<?php
// =====================================================================
// FILE   : VIEW/SISWA/dashboardSiswa.php  (URL: /siswa/dashboardSiswa)
// FUNGSI : DASHBOARD siswa: sapaan nama dan statistik peminjaman milik siswa.
// Dibuka langsung oleh .htaccess; view membuat query sendiri (tanpa model khusus untuk statistik).
// =====================================================================
// Mulai session hanya jika belum aktif (mencegah error session already started).
if (session_status() === PHP_SESSION_NONE) {
    // Mengaktifkan session (penyimpanan data login sementara di server).
    session_start();
}

// Proteksi Akses Siswa
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'siswa') {
    // Jika admin nyasar, kembalikan ke halaman admin terakhir!
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
        // Alamat tujuan: halaman admin terakhir yang tersimpan di session; ?? = nilai cadangan (dashboard) jika belum ada.
        $kembali = $_SESSION['last_page_admin'] ?? '/Aplikasi_Peminjaman_Buku/admin/dashboardAdmin';
        header("Location: " . $kembali);
        exit;
    }
    header("Location: /Aplikasi_Peminjaman_Buku/login");
    exit;
}

// Simpan URL lokasi controller aktif saat ini
$_SESSION['last_page_siswa'] = $_SERVER['REQUEST_URI'];

// Memuat file model m_koneksi.php (berisi class untuk query database).
require_once __DIR__ . "/../../MODEL/m_koneksi.php";
// Memuat file model m_peminjaman.php (berisi class untuk query database).
require_once __DIR__ . "/../../MODEL/m_peminjaman.php";

// Buat objek dari class Koneksi; variabel $db dipakai untuk memanggil fungsi-fungsinya.
$db = new Koneksi();
// Ambil objek koneksi dari class Koneksi untuk menjalankan query.
$koneksi = $db->getKoneksi();
// id user yang sedang login (dari session), dipaksa jadi angka (int).
$id_user = (int)$_SESSION['id_user'];

// Ambil Nama Siswa
$stmtUser = $koneksi->prepare("SELECT nama_lengkap, nis_nip FROM users WHERE id_user = ?");
// Isi tanda tanya (?) pada query dengan nilai sebenarnya (i = integer, s = string).
$stmtUser->bind_param("i", $id_user);
// Jalankan query.
$stmtUser->execute();
// Ambil hasil query; fetch_assoc() mengambil satu baris sebagai array berkunci nama kolom.
$resUser = $stmtUser->get_result()->fetch_assoc();
$namaSiswa = $resUser['nama_lengkap'] ?? 'Siswa';
$nisSiswa = $resUser['nis_nip'] ?? '-';
$stmtUser->close();

// Hitung Statistik Siswa
// Statistik peminjaman siswa. CATATAN: query membandingkan status dengan 'dipinjam'/'menunggu'/'dikembalikan' (huruf kecil), sedangkan nilai di database 'Dipinjam', 'Diajukan', 'Dikembalikan'. Perbandingan enum di MySQL kemungkinan besar tidak membedakan huruf besar/kecil, jadi 'dipinjam' dan 'dikembalikan' cocok; 'menunggu' bukan nilai status yang ada, sehingga pengajuan berstatus 'Diajukan' tidak ikut terhitung sebagai aktif.
$stmtStat = $koneksi->prepare("
    SELECT 
        COUNT(CASE WHEN status = 'dipinjam' OR status = 'menunggu' THEN 1 END) as aktif,
        COUNT(CASE WHEN status = 'dikembalikan' THEN 1 END) as selesai
    FROM peminjaman WHERE id_user = ?
");
// Isi tanda tanya (?) pada query dengan nilai sebenarnya (i = integer, s = string).
$stmtStat->bind_param("i", $id_user);
// Jalankan query.
$stmtStat->execute();
// Ambil hasil query; fetch_assoc() mengambil satu baris sebagai array berkunci nama kolom.
$stat = $stmtStat->get_result()->fetch_assoc();
$stmtStat->close();
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
    <title>Dashboard Siswa - Perpustakaan Online</title>
    <!-- Memuat Font Awesome (kumpulan ikon, dipakai lewat class fa-...). -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- CSS khusus halaman ini (warna, ukuran, tata letak); tidak dikomentari baris demi baris. -->
    <style>
        /* 1. Kunci Layar Utama */
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        html, body {
            height: 100vh;
            width: 100vw;
            overflow: hidden; /* Mencegah layar bergeser/bocor */
            background-color: #f4f7f6;
        }

        .app-wrapper {
            display: flex;
            height: 100vh;
            width: 100vw;
        }

        /* 2. Main Content Setup */
        .main-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            height: 100vh;
            overflow: hidden;
            background-color: #f4f8fb; /* Background sedikit lebih cerah/biru muda */
        }

        /* Topbar Header */
        .topbar {
            background-color: #fff;
            padding: 15px 30px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.02);
            display: flex;
            justify-content: space-between;
            align-items: center;
            z-index: 10;
        }

        .topbar h2 {
            font-size: 18px;
            color: #2c3e50;
            font-weight: 700;
        }

        .topbar-profile {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* 3. Container Konten Bisa di Scroll */
        .dashboard-container {
            padding: 30px;
            flex: 1;
            overflow-y: auto;
            overflow-x: hidden;
        }

        /* 4. Welcome Hero Banner */
        .hero-banner {
            background: linear-gradient(135deg, #4f46e5 0%, #3b82f6 100%);
            border-radius: 20px;
            padding: 40px;
            color: white;
            position: relative;
            overflow: hidden;
            box-shadow: 0 10px 25px rgba(59, 130, 246, 0.2);
            margin-bottom: 30px;
        }

        .hero-banner::after {
            content: '';
            position: absolute;
            top: -50px;
            right: -50px;
            width: 250px;
            height: 250px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
        }

        .hero-banner h1 {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 10px;
            position: relative;
            z-index: 2;
        }

        .hero-banner p {
            font-size: 15px;
            color: rgba(255, 255, 255, 0.9);
            max-width: 600px;
            line-height: 1.6;
            position: relative;
            z-index: 2;
        }

        /* 5. Custom Grid untuk Statistik */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
            margin-bottom: 35px;
        }

        .stat-card {
            background: #ffffff;
            border-radius: 16px;
            padding: 25px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.03);
            border: 1px solid #f1f5f9;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 25px rgba(0, 0, 0, 0.06);
        }

        .stat-info span {
            font-size: 13px;
            color: #64748b;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: block;
            margin-bottom: 5px;
        }

        .stat-info h3 {
            font-size: 32px;
            color: #1e293b;
            font-weight: 800;
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }

        .icon-blue { background: #eff6ff; color: #3b82f6; }
        .icon-green { background: #f0fdf4; color: #10b981; }

        /* 6. Widget Akses Cepat */
        .section-title {
            font-size: 18px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .actions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
        }

        .action-widget {
            background: #ffffff;
            padding: 25px 20px;
            border-radius: 16px;
            text-decoration: none;
            color: #334155;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.03);
            border: 1px solid #f1f5f9;
            transition: all 0.3s ease;
        }

        .action-widget:hover {
            background: #3b82f6;
            color: #ffffff;
            transform: translateY(-5px);
            box-shadow: 0 12px 25px rgba(59, 130, 246, 0.2);
        }

        .action-widget i {
            font-size: 28px;
            margin-bottom: 15px;
            color: #3b82f6;
            transition: color 0.3s ease;
        }

        .action-widget:hover i {
            color: #ffffff;
        }

        .action-widget span {
            font-size: 15px;
            font-weight: 600;
        }

    </style>
</head>

<!-- BAGIAN BODY: seluruh isi yang tampil di layar. -->
<body>

    <div class="app-wrapper">
        <!-- Include Sidebar Siswa -->
        <?php include __DIR__ . "/../TEMPLATE/SideBarSiswa.php"; ?>

        <!-- Area Konten Utama -->
        <div class="main-content">
            
            <div class="topbar">
                <h2>Beranda Siswa</h2>
                <div class="topbar-profile">
                    <!-- htmlspecialchars() mengubah karakter khusus HTML (< > " ') menjadi aman ditampilkan; ini pencegah XSS (penyisipan script lewat data). -->
                    <span style="font-size: 14px; font-weight: 600; color: #475569;"><?= htmlspecialchars($namaSiswa) ?></span>
                    <div style="width: 32px; height: 32px; background: #3b82f6; color: #fff; border-radius: 50%; display: flex; justify-content: center; align-items: center; font-weight: bold; font-size: 12px;">
                        <!-- Sintaks echo singkat PHP (tag buka PHP diikuti tanda sama dengan): mencetak nilai PHP langsung ke dalam HTML. -->
                        <?= strtoupper(substr($namaSiswa, 0, 1)) ?>
                    </div>
                </div>
            </div>

            <div class="dashboard-container">

                <!-- Banner Selamat Datang -->
                <div class="hero-banner">
                    <h1>Selamat Datang, <?= htmlspecialchars($namaSiswa) ?>! 👋</h1>
                    <p>Jelajahi koleksi buku kami, tingkatkan pengetahuanmu, dan kelola aktivitas peminjaman buku perpustakaan dengan mudah melalui panel ini.</p>
                </div>

                <!-- Kartu Statistik Singkat -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-info">
                            <span>Peminjaman Aktif</span>
                            <h3><?= (int)($stat['aktif'] ?? 0) ?></h3>
                        </div>
                        <div class="stat-icon icon-blue">
                            <i class="fa-solid fa-book-open-reader"></i>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-info">
                            <span>Buku Selesai Dipinjam</span>
                            <h3><?= (int)($stat['selesai'] ?? 0) ?></h3>
                        </div>
                        <div class="stat-icon icon-green">
                            <i class="fa-solid fa-circle-check"></i>
                        </div>
                    </div>
                </div>

                <!-- Menu Akses Cepat -->
                <h3 class="section-title"><i class="fa-solid fa-bolt text-warning"></i> Menu Akses Cepat</h3>
                <div class="actions-grid">
                    <!-- CLEAN URL: Cari & Pinjam Buku -->
                    <a href="/Aplikasi_Peminjaman_Buku/siswa/daftarBuku" class="action-widget">
                        <i class="fa-solid fa-book-medical"></i>
                        <span>Cari & Pinjam Buku</span>
                    </a>

                    <!-- CLEAN URL: Riwayat Peminjaman -->
                    <a href="/Aplikasi_Peminjaman_Buku/siswa/peminjaman" class="action-widget">
                        <i class="fa-solid fa-clock-rotate-left"></i>
                        <span>Riwayat Peminjaman</span>
                    </a>

                    <!-- CLEAN URL: Pengaturan Profil -->
                    <a href="/Aplikasi_Peminjaman_Buku/siswa/profil" class="action-widget">
                        <i class="fa-solid fa-user-gear"></i>
                        <span>Pengaturan Profil</span>
                    </a>
                </div>

            </div>
        </div>
    </div>

</body>
</html>