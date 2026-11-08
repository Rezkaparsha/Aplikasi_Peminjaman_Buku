<?php
// =====================================================================
// FILE   : CONTROLLER/c_histori.php
// FUNGSI : Controller halaman HISTORI TRANSAKSI admin.
// RUTE   : /admin/historiTransaksi -> CONTROLLER/c_histori.php?aksi=index
// CATATAN: view historiTransaksi.php mengambil datanya sendiri lewat
//          M_Peminjaman::getHistoriTransaksi(); variabel $daftarHistori dari
//          controller ini tersedia tetapi tidak dipakai oleh view tersebut.
// =====================================================================
// Pastikan session aktif.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Memuat model HistoriTransaksi.
require_once __DIR__ . "/../MODEL/m_histori_transaksi.php";

// Class controller histori.
class C_Histori {
    // Objek model histori.
    private $historiModel;

    // Constructor: sekaligus menjadi GERBANG ADMIN, lalu membuat objek model.
    public function __construct() {
        // Proteksi Akses Admin
        // Bukan admin / belum login -> ke halaman login. (URL lama VIEW/AUTH/login.php, bukan URL bersih /login, tetap berfungsi.)
        if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'admin') {
            header("Location: /Aplikasi_Peminjaman_Buku/VIEW/AUTH/login.php");
            exit;
        }
        // Simpan URL halaman admin terakhir
        // Catat halaman admin terakhir.
        $_SESSION['last_page_admin'] = $_SERVER['REQUEST_URI'];
        
        // Buat objek model.
        $this->historiModel = new HistoriTransaksi();
    }

    // index(): menampilkan halaman histori.
    public function index() {
        // Ambil data histori transaksi dari database
        // Ambil semua histori dari tabel histori_transaksi.
        $daftarHistori = $this->historiModel->getAll(); 

        // Panggil View Histori Transaksi Admin
        // Tampilkan VIEW histori transaksi.
        require_once __DIR__ . "/../VIEW/ADMIN/historiTransaksi.php";
        exit;
    }
}

// Inisialisasi dan jalankan aksi berdasarkan parameter GET
// ROUTER sederhana: buat controller lalu panggil method sesuai ?aksi=.
$controller = new C_Histori();
// Default aksi = index.
$aksi = $_GET['aksi'] ?? 'index';

// method_exists() memeriksa apakah method bernama $aksi ada di class; kalau ada panggil dinamis ($controller->$aksi()), kalau tidak panggil index().
if (method_exists($controller, $aksi)) {
    $controller->$aksi();
} else {
    $controller->index();
}
?>