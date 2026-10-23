<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . "/../MODEL/m_histori_transaksi.php";

class C_Histori {
    private $historiModel;

    public function __construct() {
        // Proteksi Akses Admin
        if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'admin') {
            header("Location: /Aplikasi_Peminjaman_Buku/VIEW/AUTH/login.php");
            exit;
        }
        // Simpan URL halaman admin terakhir
        $_SESSION['last_page_admin'] = $_SERVER['REQUEST_URI'];
        
        $this->historiModel = new HistoriTransaksi();
    }

    public function index() {
        // Ambil data histori transaksi dari database
        $daftarHistori = $this->historiModel->getAll(); 

        // Panggil View Histori Transaksi Admin
        require_once __DIR__ . "/../VIEW/ADMIN/historiTransaksi.php";
        exit;
    }
}

// Inisialisasi dan jalankan aksi berdasarkan parameter GET
$controller = new C_Histori();
$aksi = $_GET['aksi'] ?? 'index';

if (method_exists($controller, $aksi)) {
    $controller->$aksi();
} else {
    $controller->index();
}
?>