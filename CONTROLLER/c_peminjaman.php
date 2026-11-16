<?php
// =====================================================================
// FILE   : CONTROLLER/c_peminjaman.php
// FUNGSI : Controller UTAMA transaksi peminjaman (siswa & admin).
//          Menerima request dari URL bersih (.htaccess), mengecek login
//          dan role, memanggil MODEL (M_Peminjaman), lalu menampilkan VIEW
//          atau me-redirect.
// ALUR PEMINJAMAN SECARA UMUM:
//   1) Siswa mengajukan pinjam        -> tambah()      status 'Diajukan'
//   2) Admin menyetujui / menolak     -> setujui() / tolak()
//         setuju -> status 'Dipinjam', stok buku BERKURANG
//   3) Admin memproses pengembalian   -> prosesKembali()
//         -> status 'Dikembalikan', stok kembali, denda dihitung,
//            data disalin ke histori_transaksi
// =====================================================================
// Mengaktifkan session jika belum aktif (supaya $_SESSION bisa dipakai).
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// GERBANG LOGIN: kalau tidak ada id_user di session (belum login),
// lempar ke halaman login lalu hentikan script.
if (!isset($_SESSION['id_user'])) {
    header("Location: /Aplikasi_Peminjaman_Buku/login");
    exit;
}

// Memuat file koneksi database.
require_once __DIR__ . "/../MODEL/m_koneksi.php";
// Memuat MODEL peminjaman (berisi semua query transaksi).
require_once __DIR__ . "/../MODEL/m_peminjaman.php";

// Class controller. Setiap method = satu aksi yang dipanggil oleh switch di bagian bawah file.
class C_Peminjaman
{
    // Menyimpan objek M_Peminjaman agar bisa dipakai di semua method.
    private $model;

    // Constructor: jalan otomatis saat new C_Peminjaman() dipanggil di bagian router (paling bawah).
    public function __construct()
    {
        // Pengecekan login kedua (pengaman berlapis).
        if (!isset($_SESSION['id_user'])) {
            header("Location: /Aplikasi_Peminjaman_Buku/VIEW/AUTH/login.php");
            exit;
        }
        // Buat objek model. Mulai sini controller bisa memanggil $this->model->namaFungsi().
        $this->model = new M_Peminjaman();
    }

    // CATATAN POLA YANG BERULANG di semua method siswa:
    //  - Jika yang login ADMIN tetapi membuka halaman siswa, ia dikembalikan
    //    ke halaman admin terakhir ($_SESSION['last_page_admin']) atau dashboard admin.
    //  - last_page_siswa menyimpan URL terakhir yang dibuka siswa; dipakai untuk
    //    mengembalikan siswa jika ia nyasar ke halaman admin.
    // --- FITUR SISWA ---
    // AKSI dashboard_siswa : menampilkan dashboard siswa.
    public function dashboard_siswa()
    {
        // Jika Admin menyusup ke halaman siswa, kembalikan presisi ke halaman admin terakhir yang valid
        // Proteksi role: admin dilarang masuk halaman siswa.
        if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
            // ?? = pakai nilai kiri bila ada, kalau tidak pakai dashboard admin sebagai cadangan.
            $kembali = $_SESSION['last_page_admin'] ?? '/Aplikasi_Peminjaman_Buku/admin/dashboardAdmin';
            header("Location: " . $kembali);
            exit;
        }
        // Catat URL halaman ini ($_SERVER['REQUEST_URI'] = alamat yang sedang dibuka).
        $_SESSION['last_page_siswa'] = $_SERVER['REQUEST_URI'];
        // Tampilkan VIEW dashboard siswa. exit sesudahnya agar tidak ada output lain.
        require_once __DIR__ . "/../VIEW/SISWA/dashboardSiswa.php";
        exit;
    }

    // AKSI katalog : daftar buku (katalog) untuk siswa, URL: /siswa/daftarBuku.
    public function katalog()
    {
        if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
            $kembali = $_SESSION['last_page_admin'] ?? '/Aplikasi_Peminjaman_Buku/admin/dashboardAdmin';
            header("Location: " . $kembali);
            exit;
        }
        $_SESSION['last_page_siswa'] = $_SERVER['REQUEST_URI'];
        // Tampilkan VIEW katalog buku.
        require_once __DIR__ . "/../VIEW/SISWA/daftarBuku.php";
        exit;
    }

    // AKSI peminjaman_siswa : daftar peminjaman milik siswa, URL: /siswa/peminjaman.
    public function peminjaman_siswa()
    {
        if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
            $kembali = $_SESSION['last_page_admin'] ?? '/Aplikasi_Peminjaman_Buku/admin/dashboardAdmin';
            header("Location: " . $kembali);
            exit;
        }
        $_SESSION['last_page_siswa'] = $_SERVER['REQUEST_URI'];
        // Tampilkan VIEW daftar peminjaman siswa.
        require_once __DIR__ . "/../VIEW/SISWA/peminjaman.php";
        exit;
    }

    // AKSI tambah : siswa MENGAJUKAN peminjaman (dari form_PinjamBuku / keranjang).
    // Data masuk lewat POST berupa dua array: id_buku[] dan jumlah[] (urutannya berpasangan).
    public function tambah()
    {
        if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
            $kembali = $_SESSION['last_page_admin'] ?? '/Aplikasi_Peminjaman_Buku/admin/dashboardAdmin';
            header("Location: " . $kembali);
            exit;
        }

        // Hanya terima POST. Kalau dibuka lewat URL biasa (GET), kembalikan ke katalog.
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: /Aplikasi_Peminjaman_Buku/siswa/daftarBuku");
            exit;
        }

        // id peminjam diambil dari SESSION (bukan dari form) supaya tidak bisa dipalsukan. (int) = paksa jadi angka.
        $id_user = (int)$_SESSION['id_user'];
        // Array id buku yang dipilih.
        $id_buku = $_POST['id_buku'] ?? [];
        // Array jumlah tiap buku (index sama dengan id_buku).
        $jumlah = $_POST['jumlah'] ?? [];
        // Wadah hasil rapi: [['id_buku'=>..,'jumlah'=>..], ...] yang dikirim ke model.
        $daftarBuku = [];

        // Pastikan keduanya benar-benar array (mencegah input aneh).
        if (is_array($id_buku) && is_array($jumlah)) {
            // Loop tiap buku: $index = nomor urut, $id = id buku.
            foreach ($id_buku as $index => $id) {
                // Lewati bila jumlah untuk buku ini tidak ada.
                if (!isset($jumlah[$index])) {
                    continue;
                }
                // Tambahkan pasangan id_buku + jumlah (dipaksa (int) agar aman).
                $daftarBuku[] = [
                    'id_buku' => (int)$id,
                    'jumlah' => (int)$jumlah[$index]
                ];
            }
        }

        // Serahkan ke MODEL: validasi stok & simpan ke database. Balikannya array ['status','pesan','id_peminjaman'].
        $hasil = $this->model->tambahPeminjaman($id_user, $daftarBuku);

        // Berhasil -> simpan pesan sukses di session lalu buka halaman detail peminjaman yang baru dibuat.
        if ($hasil['status']) {
            $_SESSION['success'] = $hasil['pesan'];
            header("Location: /Aplikasi_Peminjaman_Buku/siswa/detailPeminjaman?id_peminjaman=" . $hasil['id_peminjaman']);
            exit;
        }

        // Gagal -> tampilkan pesan error (mis. stok tidak cukup) dan kembali ke katalog.
        $_SESSION['error'] = $hasil['pesan'];
        header("Location: /Aplikasi_Peminjaman_Buku/siswa/daftarBuku");
        exit;
    }

    // AKSI detail : siswa melihat detail SATU peminjaman, URL: /siswa/detailPeminjaman?id_peminjaman=..
    public function detail()
    {
        if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
            $kembali = $_SESSION['last_page_admin'] ?? '/Aplikasi_Peminjaman_Buku/admin/dashboardAdmin';
            header("Location: " . $kembali);
            exit;
        }

        // Ambil id dari URL (GET), paksa angka. Kosong/tidak valid jadi 0.
        $id_peminjaman = (int)($_GET['id_peminjaman'] ?? 0);

        // id tidak valid -> kembali ke daftar peminjaman.
        if ($id_peminjaman <= 0) {
            header("Location: /Aplikasi_Peminjaman_Buku/siswa/peminjaman");
            exit;
        }

        // Id siswa yang sedang login.
        $id_user = (int)$_SESSION['id_user'];

        // KEAMANAN: siswa hanya boleh melihat peminjaman MILIKNYA sendiri (aturan sistem no. 9).
        // Mencegah siswa mengintip data siswa lain dengan mengganti angka di URL.
        if (!$this->model->cekKepemilikanPeminjaman($id_peminjaman, $id_user)) {
            $_SESSION['error'] = 'Anda tidak memiliki akses ke peminjaman ini.';
            header("Location: /Aplikasi_Peminjaman_Buku/siswa/peminjaman");
            exit;
        }

        $_SESSION['last_page_siswa'] = $_SERVER['REQUEST_URI'];
        // Ambil data induk peminjaman (tanggal, status, alasan penolakan, data siswa).
        $dataPeminjaman = $this->model->getPeminjamanById($id_peminjaman);
        // Ambil daftar buku di peminjaman ini (judul, jumlah, tanggal kembali, status).
        $detailPeminjaman = $this->model->getDetailPeminjaman($id_peminjaman);

        // Tampilkan VIEW; variabel $dataPeminjaman & $detailPeminjaman otomatis bisa dipakai di file view.
        require_once __DIR__ . "/../VIEW/SISWA/detailPeminjaman.php";
        exit;
    }

    // Semua method di bawah ini hanya untuk ADMIN.
    // --- PROTEKSI ADMIN & FITUR ADMIN ---
    // Fungsi pelindung (private = hanya dipanggil dari dalam class ini). Dipanggil di awal setiap fitur admin.
    private function cekRoleAdmin()
    {
        // Bukan admin -> kembalikan ke halaman siswa terakhir lalu hentikan script (RBAC).
        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
            $kembali = $_SESSION['last_page_siswa'] ?? '/Aplikasi_Peminjaman_Buku/siswa/dashboardSiswa';
            header("Location: " . $kembali);
            exit;
        }
    }

    // AKSI dashboard_admin : dashboard admin.
    public function dashboard_admin()
    {
        // Pastikan yang mengakses adalah admin.
        $this->cekRoleAdmin();
        // Catat halaman admin terakhir.
        $_SESSION['last_page_admin'] = $_SERVER['REQUEST_URI'];
        // Tampilkan VIEW dashboard admin.
        require_once __DIR__ . "/../VIEW/ADMIN/dashboardAdmin.php";
        exit;
    }

    // AKSI admin : daftar SEMUA peminjaman, URL: /admin/daftarPeminjaman.
    public function admin()
    {
        $this->cekRoleAdmin();
        $_SESSION['last_page_admin'] = $_SERVER['REQUEST_URI'];
        // Ambil semua peminjaman dari semua siswa (JOIN dengan tabel users di model).
        $dataPeminjaman = $this->model->getSemuaPeminjaman();
        // Tampilkan VIEW daftar peminjaman admin.
        require_once __DIR__ . "/../VIEW/ADMIN/daftarPeminjaman.php";
        exit;
    }

    // AKSI detailAdmin : admin melihat detail satu peminjaman (tempat tombol Setujui / Tolak / Proses Kembali).
    public function detailAdmin()
    {
        $this->cekRoleAdmin();
        $_SESSION['last_page_admin'] = $_SERVER['REQUEST_URI'];
        // Ambil id peminjaman dari URL.
        $id_peminjaman = (int)($_GET['id_peminjaman'] ?? 0);

        // id tidak valid -> kembali ke daftar.
        if ($id_peminjaman <= 0) {
            header("Location: /Aplikasi_Peminjaman_Buku/admin/daftarPeminjaman");
            exit;
        }

        // Ambil data induk peminjaman.
        $dataPeminjaman = $this->model->getPeminjamanById($id_peminjaman);
        // Ambil rincian buku.
        $detailPeminjaman = $this->model->getDetailPeminjaman($id_peminjaman);

        // Data tidak ada (id ngawur) -> pesan error lalu kembali.
        if (!$dataPeminjaman) {
            $_SESSION['error'] = 'Data peminjaman tidak ditemukan.';
            header("Location: /Aplikasi_Peminjaman_Buku/admin/daftarPeminjaman");
            exit;
        }

        // Tampilkan VIEW detail peminjaman admin.
        require_once __DIR__ . "/../VIEW/ADMIN/detailPeminjaman.php";
        exit;
    }

    // AKSI setujui : admin MENYETUJUI pengajuan dan menentukan tanggal batas pengembalian.
    // Efek di model: stok dikurangi, status jadi 'Dipinjam'.
    public function setujui()
    {
        $this->cekRoleAdmin();
        // Hanya lewat POST (dari form di halaman detail).
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: /Aplikasi_Peminjaman_Buku/admin/daftarPeminjaman");
            exit;
        }

        // id peminjaman yang disetujui (dari form).
        $id_peminjaman = (int)($_POST['id_peminjaman'] ?? 0);
        // Batas tanggal pengembalian yang diisi admin.
        $tanggal_pengembalian = $_POST['tanggal_pengembalian'] ?? '';

        // Proses di MODEL (memakai transaksi database).
        $hasil = $this->model->setujuiPeminjaman($id_peminjaman, $tanggal_pengembalian);

        if ($hasil['status']) {
            $_SESSION['success'] = $hasil['pesan'];
        } else {
            $_SESSION['error'] = $hasil['pesan'];
        }

        // Kembali ke halaman detail untuk melihat hasil + pesan notifikasi.
        header("Location: /Aplikasi_Peminjaman_Buku/admin/detailPeminjaman?id_peminjaman=" . $id_peminjaman);
        exit;
    }

    // AKSI tolak : admin MENOLAK pengajuan dengan alasan. Stok tidak berubah.
    public function tolak()
    {
        $this->cekRoleAdmin();
        // Hanya lewat POST.
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: /Aplikasi_Peminjaman_Buku/admin/daftarPeminjaman");
            exit;
        }

        $id_peminjaman = (int)($_POST['id_peminjaman'] ?? 0);
        // Alasan penolakan (spasi ujung dibuang). Disimpan di kolom peminjaman.alasan_penolakan.
        $alasan_penolakan = trim($_POST['alasan_penolakan'] ?? '');

        // Proses penolakan di MODEL.
        $hasil = $this->model->tolakPeminjaman($id_peminjaman, $alasan_penolakan);

        if ($hasil['status']) {
            $_SESSION['success'] = $hasil['pesan'];
        } else {
            $_SESSION['error'] = $hasil['pesan'];
        }

        header("Location: /Aplikasi_Peminjaman_Buku/admin/detailPeminjaman?id_peminjaman=" . $id_peminjaman);
        exit;
    }

    // AKSI prosesKembali : admin memproses PENGEMBALIAN buku.
    // Form mengirim: id_peminjaman, kondisi[id_detail] (Normal/Rusak/Hilang),
    // dan denda_tambahan[id_detail] untuk buku rusak/hilang.
    public function prosesKembali()
    {
        $this->cekRoleAdmin();
        // Hanya lewat POST.
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: /Aplikasi_Peminjaman_Buku/admin/daftarPeminjaman");
            exit;
        }

        // Ambil id; operator ?: (ternary) = kalau ada pakai nilainya, kalau tidak pakai 0.
        $id_peminjaman = isset($_POST['id_peminjaman']) ? (int)$_POST['id_peminjaman'] : 0;
        // Array kondisi per buku, kuncinya id_detail.
        $kondisi_buku = $_POST['kondisi'] ?? [];
        // Array denda tambahan per buku, kuncinya id_detail.
        $denda_tambahan = $_POST['denda_tambahan'] ?? [];

        // Proses di MODEL: stok, denda keterlambatan, histori, update status.
        $hasil = $this->model->kembalikanBuku($id_peminjaman, $kondisi_buku, $denda_tambahan);

        if ($hasil['status']) {
            $_SESSION['success'] = $hasil['pesan'];
        } else {
            $_SESSION['error'] = $hasil['pesan'];
        }

        header("Location: /Aplikasi_Peminjaman_Buku/admin/detailPeminjaman?id_peminjaman=" . $id_peminjaman);
        exit;
    }

    // AKSI histori : menampilkan histori transaksi. CATATAN: rute /admin/historiTransaksi di .htaccess sebenarnya
    // mengarah ke c_histori.php, bukan ke aksi ini; method ini tersedia tetapi tidak dipakai oleh rute yang ada.
    public function histori()
    {
        $this->cekRoleAdmin();
        $_SESSION['last_page_admin'] = $_SERVER['REQUEST_URI'];
        // Ambil data dari tabel histori_transaksi.
        $dataHistori = $this->model->getHistoriTransaksi();
        // Tampilkan VIEW histori transaksi.
        require_once __DIR__ . "/../VIEW/ADMIN/historiTransaksi.php";
        exit;
    }
}

// Router (DIUBAH KE CLEAN URL SEPENUHNYA)
// =====================================================================
// ROUTER: bagian yang PERTAMA berjalan saat file dibuka lewat URL.
// .htaccess mengubah /siswa/daftarBuku menjadi
// CONTROLLER/c_peminjaman.php?aksi=katalog, lalu switch di bawah memilih method.
// =====================================================================
$controller = new C_Peminjaman();
// Baca parameter ?aksi= dari URL.
$aksi = $_GET['aksi'] ?? '';

switch ($aksi) {
    // --- aksi untuk SISWA ---
    case 'dashboard_siswa': $controller->dashboard_siswa(); break;
    case 'katalog': $controller->katalog(); break;
    case 'peminjaman_siswa': $controller->peminjaman_siswa(); break;
    case 'tambah': $controller->tambah(); break;
    case 'detail': $controller->detail(); break;

    // --- aksi untuk ADMIN ---
    case 'dashboard_admin': $controller->dashboard_admin(); break;
    case 'admin': $controller->admin(); break;
    case 'detailAdmin': $controller->detailAdmin(); break;
    case 'setujui': $controller->setujui(); break;
    case 'tolak': $controller->tolak(); break;
    case 'prosesKembali': $controller->prosesKembali(); break;
    case 'histori': $controller->histori(); break;

    // Aksi kosong/tidak dikenal -> kirim ke dashboard sesuai role.
    default:
        // Jika aksi kosong atau tidak dikenal, arahkan ke Clean URL dashboard masing-masing role
        if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
            header("Location: /Aplikasi_Peminjaman_Buku/admin/dashboardAdmin");
        } else {
            header("Location: /Aplikasi_Peminjaman_Buku/siswa/dashboardSiswa");
        }
        exit;
}
?>