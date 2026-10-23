<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['id_user'])) {
    header("Location: /Aplikasi_Peminjaman_Buku/login");
    exit;
}

require_once __DIR__ . "/../MODEL/m_koneksi.php";
require_once __DIR__ . "/../MODEL/m_peminjaman.php";

class C_Peminjaman
{
    private $model;

    public function __construct()
    {
        if (!isset($_SESSION['id_user'])) {
            header("Location: /Aplikasi_Peminjaman_Buku/VIEW/AUTH/login.php");
            exit;
        }
        $this->model = new M_Peminjaman();
    }

    // --- FITUR SISWA ---
    public function dashboard_siswa()
    {
        // Jika Admin menyusup ke halaman siswa, kembalikan presisi ke halaman admin terakhir yang valid
        if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
            $kembali = $_SESSION['last_page_admin'] ?? '/Aplikasi_Peminjaman_Buku/admin/dashboardAdmin';
            header("Location: " . $kembali);
            exit;
        }
        $_SESSION['last_page_siswa'] = $_SERVER['REQUEST_URI'];
        require_once __DIR__ . "/../VIEW/SISWA/dashboardSiswa.php";
        exit;
    }

    public function katalog()
    {
        if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
            $kembali = $_SESSION['last_page_admin'] ?? '/Aplikasi_Peminjaman_Buku/admin/dashboardAdmin';
            header("Location: " . $kembali);
            exit;
        }
        $_SESSION['last_page_siswa'] = $_SERVER['REQUEST_URI'];
        require_once __DIR__ . "/../VIEW/SISWA/daftarBuku.php";
        exit;
    }

    public function peminjaman_siswa()
    {
        if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
            $kembali = $_SESSION['last_page_admin'] ?? '/Aplikasi_Peminjaman_Buku/admin/dashboardAdmin';
            header("Location: " . $kembali);
            exit;
        }
        $_SESSION['last_page_siswa'] = $_SERVER['REQUEST_URI'];
        require_once __DIR__ . "/../VIEW/SISWA/peminjaman.php";
        exit;
    }

    public function tambah()
    {
        if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
            $kembali = $_SESSION['last_page_admin'] ?? '/Aplikasi_Peminjaman_Buku/admin/dashboardAdmin';
            header("Location: " . $kembali);
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: /Aplikasi_Peminjaman_Buku/siswa/daftarBuku");
            exit;
        }

        $id_user = (int)$_SESSION['id_user'];
        $id_buku = $_POST['id_buku'] ?? [];
        $jumlah = $_POST['jumlah'] ?? [];
        $daftarBuku = [];

        if (is_array($id_buku) && is_array($jumlah)) {
            foreach ($id_buku as $index => $id) {
                if (!isset($jumlah[$index])) {
                    continue;
                }
                $daftarBuku[] = [
                    'id_buku' => (int)$id,
                    'jumlah' => (int)$jumlah[$index]
                ];
            }
        }

        $hasil = $this->model->tambahPeminjaman($id_user, $daftarBuku);

        if ($hasil['status']) {
            $_SESSION['success'] = $hasil['pesan'];
            header("Location: /Aplikasi_Peminjaman_Buku/siswa/detailPeminjaman?id_peminjaman=" . $hasil['id_peminjaman']);
            exit;
        }

        $_SESSION['error'] = $hasil['pesan'];
        header("Location: /Aplikasi_Peminjaman_Buku/siswa/daftarBuku");
        exit;
    }

    public function detail()
    {
        if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
            $kembali = $_SESSION['last_page_admin'] ?? '/Aplikasi_Peminjaman_Buku/admin/dashboardAdmin';
            header("Location: " . $kembali);
            exit;
        }

        $id_peminjaman = (int)($_GET['id_peminjaman'] ?? 0);

        if ($id_peminjaman <= 0) {
            header("Location: /Aplikasi_Peminjaman_Buku/siswa/peminjaman");
            exit;
        }

        $id_user = (int)$_SESSION['id_user'];

        if (!$this->model->cekKepemilikanPeminjaman($id_peminjaman, $id_user)) {
            $_SESSION['error'] = 'Anda tidak memiliki akses ke peminjaman ini.';
            header("Location: /Aplikasi_Peminjaman_Buku/siswa/peminjaman");
            exit;
        }

        $_SESSION['last_page_siswa'] = $_SERVER['REQUEST_URI'];
        $dataPeminjaman = $this->model->getPeminjamanById($id_peminjaman);
        $detailPeminjaman = $this->model->getDetailPeminjaman($id_peminjaman);

        require_once __DIR__ . "/../VIEW/SISWA/detailPeminjaman.php";
        exit;
    }

    // --- PROTEKSI ADMIN & FITUR ADMIN ---
    private function cekRoleAdmin()
    {
        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
            $kembali = $_SESSION['last_page_siswa'] ?? '/Aplikasi_Peminjaman_Buku/siswa/dashboardSiswa';
            header("Location: " . $kembali);
            exit;
        }
    }

    public function dashboard_admin()
    {
        $this->cekRoleAdmin();
        $_SESSION['last_page_admin'] = $_SERVER['REQUEST_URI'];
        require_once __DIR__ . "/../VIEW/ADMIN/dashboardAdmin.php";
        exit;
    }

    public function admin()
    {
        $this->cekRoleAdmin();
        $_SESSION['last_page_admin'] = $_SERVER['REQUEST_URI'];
        $dataPeminjaman = $this->model->getSemuaPeminjaman();
        require_once __DIR__ . "/../VIEW/ADMIN/daftarPeminjaman.php";
        exit;
    }

    public function detailAdmin()
    {
        $this->cekRoleAdmin();
        $_SESSION['last_page_admin'] = $_SERVER['REQUEST_URI'];
        $id_peminjaman = (int)($_GET['id_peminjaman'] ?? 0);

        if ($id_peminjaman <= 0) {
            header("Location: /Aplikasi_Peminjaman_Buku/admin/daftarPeminjaman");
            exit;
        }

        $dataPeminjaman = $this->model->getPeminjamanById($id_peminjaman);
        $detailPeminjaman = $this->model->getDetailPeminjaman($id_peminjaman);

        if (!$dataPeminjaman) {
            $_SESSION['error'] = 'Data peminjaman tidak ditemukan.';
            header("Location: /Aplikasi_Peminjaman_Buku/admin/daftarPeminjaman");
            exit;
        }

        require_once __DIR__ . "/../VIEW/ADMIN/detailPeminjaman.php";
        exit;
    }

    public function setujui()
    {
        $this->cekRoleAdmin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: /Aplikasi_Peminjaman_Buku/admin/daftarPeminjaman");
            exit;
        }

        $id_peminjaman = (int)($_POST['id_peminjaman'] ?? 0);
        $tanggal_pengembalian = $_POST['tanggal_pengembalian'] ?? '';

        $hasil = $this->model->setujuiPeminjaman($id_peminjaman, $tanggal_pengembalian);

        if ($hasil['status']) {
            $_SESSION['success'] = $hasil['pesan'];
        } else {
            $_SESSION['error'] = $hasil['pesan'];
        }

        header("Location: /Aplikasi_Peminjaman_Buku/admin/detailPeminjaman?id_peminjaman=" . $id_peminjaman);
        exit;
    }

    public function tolak()
    {
        $this->cekRoleAdmin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: /Aplikasi_Peminjaman_Buku/admin/daftarPeminjaman");
            exit;
        }

        $id_peminjaman = (int)($_POST['id_peminjaman'] ?? 0);
        $alasan_penolakan = trim($_POST['alasan_penolakan'] ?? '');

        $hasil = $this->model->tolakPeminjaman($id_peminjaman, $alasan_penolakan);

        if ($hasil['status']) {
            $_SESSION['success'] = $hasil['pesan'];
        } else {
            $_SESSION['error'] = $hasil['pesan'];
        }

        header("Location: /Aplikasi_Peminjaman_Buku/admin/detailPeminjaman?id_peminjaman=" . $id_peminjaman);
        exit;
    }

    public function prosesKembali()
    {
        $this->cekRoleAdmin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: /Aplikasi_Peminjaman_Buku/admin/daftarPeminjaman");
            exit;
        }

        $id_peminjaman = isset($_POST['id_peminjaman']) ? (int)$_POST['id_peminjaman'] : 0;
        $kondisi_buku = $_POST['kondisi'] ?? [];
        $denda_tambahan = $_POST['denda_tambahan'] ?? [];

        $hasil = $this->model->kembalikanBuku($id_peminjaman, $kondisi_buku, $denda_tambahan);

        if ($hasil['status']) {
            $_SESSION['success'] = $hasil['pesan'];
        } else {
            $_SESSION['error'] = $hasil['pesan'];
        }

        header("Location: /Aplikasi_Peminjaman_Buku/admin/detailPeminjaman?id_peminjaman=" . $id_peminjaman);
        exit;
    }

    public function histori()
    {
        $this->cekRoleAdmin();
        $_SESSION['last_page_admin'] = $_SERVER['REQUEST_URI'];
        $dataHistori = $this->model->getHistoriTransaksi();
        require_once __DIR__ . "/../VIEW/ADMIN/historiTransaksi.php";
        exit;
    }
}

// Router (DIUBAH KE CLEAN URL SEPENUHNYA)
$controller = new C_Peminjaman();
$aksi = $_GET['aksi'] ?? '';

switch ($aksi) {
    case 'dashboard_siswa': $controller->dashboard_siswa(); break;
    case 'katalog': $controller->katalog(); break;
    case 'peminjaman_siswa': $controller->peminjaman_siswa(); break;
    case 'tambah': $controller->tambah(); break;
    case 'detail': $controller->detail(); break;

    case 'dashboard_admin': $controller->dashboard_admin(); break;
    case 'admin': $controller->admin(); break;
    case 'detailAdmin': $controller->detailAdmin(); break;
    case 'setujui': $controller->setujui(); break;
    case 'tolak': $controller->tolak(); break;
    case 'prosesKembali': $controller->prosesKembali(); break;
    case 'histori': $controller->histori(); break;

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