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

require_once __DIR__ . "/../MODEL/m_penerbit.php";

class C_Penerbit
{
    private $penerbitModel;

    public function __construct()
    {
        $this->penerbitModel = new Penerbit();
    }

    public function index()
    {
        header("Location: /Aplikasi_Peminjaman_Buku/admin/daftarPenerbit");
        exit;
    }

    public function tambah()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: /Aplikasi_Peminjaman_Buku/admin/tambahPenerbit");
            exit;
        }

        $namaPenerbit = trim($_POST['nama_penerbit'] ?? '');
        $alamat       = trim($_POST['alamat'] ?? '');
        $kota         = trim($_POST['kota'] ?? '');
        $telepon      = trim($_POST['telepon'] ?? '');
        $email        = trim($_POST['email'] ?? '');

        if ($namaPenerbit === '') {
            $_SESSION['error'] = "Nama penerbit wajib diisi.";
            header("Location: /Aplikasi_Peminjaman_Buku/admin/daftarPenerbit");
            exit;
        }

        $this->penerbitModel->nama_penerbit = $namaPenerbit;
        $this->penerbitModel->alamat        = $alamat;
        $this->penerbitModel->kota          = $kota;
        $this->penerbitModel->telepon       = $telepon;
        $this->penerbitModel->email         = $email;

        if ($this->penerbitModel->insert()) {
            $_SESSION['success'] = "Penerbit berhasil ditambahkan.";
        } else {
            $_SESSION['error'] = "Gagal menambahkan penerbit.";
        }
        header("Location: /Aplikasi_Peminjaman_Buku/admin/daftarPenerbit");
        exit;
    }

    public function edit()
    {
        $idPenerbit = (int) ($_GET['id_penerbit'] ?? 0);

        if ($idPenerbit <= 0) {
            header("Location: /Aplikasi_Peminjaman_Buku/admin/daftarPenerbit");
            exit;
        }

        $dataPenerbit = $this->penerbitModel->getById($idPenerbit);

        if (!$dataPenerbit) {
            $_SESSION['error'] = "Data penerbit tidak ditemukan.";
            header("Location: /Aplikasi_Peminjaman_Buku/admin/daftarPenerbit");
            exit;
        }

        require_once __DIR__ . "/../VIEW/ADMIN/editPenerbit.php";
        exit;
    }

    public function update()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: /Aplikasi_Peminjaman_Buku/admin/daftarPenerbit");
            exit;
        }

        $idPenerbit   = (int) ($_POST['id_penerbit'] ?? 0);
        $namaPenerbit = trim($_POST['nama_penerbit'] ?? '');
        $alamat       = trim($_POST['alamat'] ?? '');
        $kota         = trim($_POST['kota'] ?? '');
        $telepon      = trim($_POST['telepon'] ?? '');
        $email        = trim($_POST['email'] ?? '');

        if ($idPenerbit <= 0 or $namaPenerbit === '') {
            $_SESSION['error'] = "Data penerbit tidak valid.";
            header("Location: /Aplikasi_Peminjaman_Buku/admin/daftarPenerbit");
            exit;
        }

        $this->penerbitModel->id_penerbit   = $idPenerbit;
        $this->penerbitModel->nama_penerbit = $namaPenerbit;
        $this->penerbitModel->alamat        = $alamat;
        $this->penerbitModel->kota          = $kota;
        $this->penerbitModel->telepon       = $telepon;
        $this->penerbitModel->email         = $email;

        if ($this->penerbitModel->update()) {
            $_SESSION['success'] = "Data penerbit berhasil diperbarui.";
        } else {
            $_SESSION['error'] = "Gagal memperbarui penerbit.";
        }
        header("Location: /Aplikasi_Peminjaman_Buku/admin/daftarPenerbit");
        exit;
    }

    public function hapus()
    {
        $idPenerbit = (int) ($_GET['id_penerbit'] ?? 0);

        if ($idPenerbit > 0) {
            if ($this->penerbitModel->isUsedInBuku($idPenerbit)) {
                $_SESSION['error'] = "Penerbit tidak dapat dihapus karena masih terhubung dengan data buku di perpustakaan!";
            } else {
                $hapus = $this->penerbitModel->delete($idPenerbit);
                if ($hapus) {
                    $_SESSION['success'] = "Penerbit berhasil dihapus.";
                } else {
                    $_SESSION['error'] = "Gagal menghapus penerbit.";
                }
            }
        } else {
            $_SESSION['error'] = "ID Penerbit tidak valid.";
        }
        
        header("Location: /Aplikasi_Peminjaman_Buku/admin/daftarPenerbit");
        exit;
    }
}

// Router
$controller = new C_Penerbit();
$aksi = $_GET['aksi'] ?? '';

switch ($aksi) {
    case 'tambah': $controller->tambah(); break;
    case 'edit': $controller->edit(); break;
    case 'update': $controller->update(); break;
    case 'hapus': $controller->hapus(); break;
    default: $controller->index(); break;
}
?>