<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'admin') {
    $kembali = $_SESSION['last_page_siswa'] ?? '/Aplikasi_Peminjaman_Buku/siswa/dashboardSiswa';
    if (!isset($_SESSION['id_user'])) {
        $kembali = '/Aplikasi_Peminjaman_Buku/login';
    }
    header("Location: " . $kembali);
    exit;
}

// Simpan URL halaman admin terakhir
$_SESSION['last_page_admin'] = $_SERVER['REQUEST_URI'];

require_once __DIR__ . "/../MODEL/m_penulis.php";

class C_Penulis
{
    private $penulisModel;

    public function __construct()
    {
        $this->penulisModel = new Penulis();
    }

    public function index()
    {
        header("Location: /Aplikasi_Peminjaman_Buku/admin/daftarPenulis");
        exit;
    }

    public function tambah()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: /Aplikasi_Peminjaman_Buku/admin/tambahPenulis");
            exit;
        }

        $namaPenulis = trim($_POST['nama_penulis'] ?? '');

        if ($namaPenulis === '') {
            $_SESSION['error'] = "Nama penulis wajib diisi.";
            header("Location: /Aplikasi_Peminjaman_Buku/admin/daftarPenulis");
            exit;
        }

        $this->penulisModel->nama_penulis = $namaPenulis;

        if ($this->penulisModel->insert()) {
            $_SESSION['success'] = "Penulis berhasil ditambahkan.";
        } else {
            $_SESSION['error'] = "Gagal menambahkan penulis.";
        }
        header("Location: /Aplikasi_Peminjaman_Buku/admin/daftarPenulis");
        exit;
    }

    public function edit()
    {
        $idPenulis = (int) ($_GET['id_penulis'] ?? 0);

        if ($idPenulis <= 0) {
            header("Location: /Aplikasi_Peminjaman_Buku/admin/daftarPenulis");
            exit;
        }

        $dataPenulis = $this->penulisModel->getById($idPenulis);

        if (!$dataPenulis) {
            $_SESSION['error'] = "Data penulis tidak ditemukan.";
            header("Location: /Aplikasi_Peminjaman_Buku/admin/daftarPenulis");
            exit;
        }

        require_once __DIR__ . "/../VIEW/ADMIN/editPenulis.php";
        exit;
    }

    public function update()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: /Aplikasi_Peminjaman_Buku/admin/daftarPenulis");
            exit;
        }

        $idPenulis   = (int) ($_POST['id_penulis'] ?? 0);
        $namaPenulis = trim($_POST['nama_penulis'] ?? '');

        if ($idPenulis <= 0 or $namaPenulis === '') {
            $_SESSION['error'] = "Data penulis tidak valid.";
            header("Location: /Aplikasi_Peminjaman_Buku/admin/daftarPenulis");
            exit;
        }

        $this->penulisModel->id_penulis   = $idPenulis;
        $this->penulisModel->nama_penulis = $namaPenulis;

        if ($this->penulisModel->update()) {
            $_SESSION['success'] = "Data penulis berhasil diperbarui.";
        } else {
            $_SESSION['error'] = "Gagal memperbarui penulis.";
        }
        header("Location: /Aplikasi_Peminjaman_Buku/admin/daftarPenulis");
        exit;
    }

    public function hapus()
    {
        $id_penulis = (int)($_GET['id_penulis'] ?? 0);

        if ($id_penulis > 0) {
            if ($this->penulisModel->isUsedInBuku($id_penulis)) {
                $_SESSION['error'] = "Penulis tidak dapat dihapus karena masih terhubung dengan data buku di perpustakaan!";
            } else {
                $hapus = $this->penulisModel->delete($id_penulis);
                if ($hapus) {
                    $_SESSION['success'] = "Penulis berhasil dihapus.";
                } else {
                    $_SESSION['error'] = "Gagal menghapus penulis.";
                }
            }
        } else {
            $_SESSION['error'] = "ID Penulis tidak valid.";
        }

        header("Location: /Aplikasi_Peminjaman_Buku/admin/daftarPenulis");
        exit;
    }
}

// Router
$controller = new C_Penulis();
$aksi = $_GET['aksi'] ?? '';

switch ($aksi) {
    case 'tambah': $controller->tambah(); break;
    case 'edit': $controller->edit(); break;
    case 'update': $controller->update(); break;
    case 'hapus': $controller->hapus(); break;
    default: $controller->index(); break;
}
?>