<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Proteksi Akses Admin & Rute Silang
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

require_once __DIR__ . "/../MODEL/m_kategori.php";

class C_Kategori
{
    private $kategoriModel;

    public function __construct()
    {
        $this->kategoriModel = new Kategori();
    }

    public function index()
    {
        header("Location: /Aplikasi_Peminjaman_Buku/admin/daftarKategori");
        exit;
    }

    public function tambah()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: /Aplikasi_Peminjaman_Buku/admin/tambahKategori");
            exit;
        }

        $namaKategori = trim($_POST['nama_kategori'] ?? '');
        $deskripsi = trim($_POST['deskripsi'] ?? '');
        $status = $_POST['status'] ?? 'aktif';

        if ($namaKategori === '') {
            $_SESSION['error'] = "Nama kategori wajib diisi.";
            header("Location: /Aplikasi_Peminjaman_Buku/admin/daftarKategori");
            exit;
        }

        if (!in_array($status, ['aktif', 'nonaktif'], true)) {
            $status = 'aktif';
        }

        $this->kategoriModel->nama_kategori = $namaKategori;
        $this->kategoriModel->deskripsi = $deskripsi;
        $this->kategoriModel->status = $status;

        if ($this->kategoriModel->insert()) {
            $_SESSION['success'] = "Kategori berhasil ditambahkan.";
        } else {
            $_SESSION['error'] = "Gagal menambahkan kategori.";
        }
        header("Location: /Aplikasi_Peminjaman_Buku/admin/daftarKategori");
        exit;
    }

    public function edit()
    {
        $idKategori = (int)($_GET['id_kategori'] ?? 0);

        if ($idKategori <= 0) {
            header("Location: /Aplikasi_Peminjaman_Buku/admin/daftarKategori");
            exit;
        }

        $dataKategori = $this->kategoriModel->getById($idKategori);

        if (!$dataKategori) {
            $_SESSION['error'] = "Data kategori tidak ditemukan.";
            header("Location: /Aplikasi_Peminjaman_Buku/admin/daftarKategori");
            exit;
        }

        require_once __DIR__ . "/../VIEW/ADMIN/editKategori.php";
        exit;
    }

    public function update()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: /Aplikasi_Peminjaman_Buku/admin/daftarKategori");
            exit;
        }

        $idKategori = (int)($_POST['id_kategori'] ?? 0);
        $namaKategori = trim($_POST['nama_kategori'] ?? '');
        $deskripsi = trim($_POST['deskripsi'] ?? '');
        $status = $_POST['status'] ?? 'aktif';

        if ($idKategori <= 0 or $namaKategori === '') {
            $_SESSION['error'] = "Data kategori tidak valid.";
            header("Location: /Aplikasi_Peminjaman_Buku/admin/daftarKategori");
            exit;
        }

        if (!in_array($status, ['aktif', 'nonaktif'], true)) {
            $status = 'aktif';
        }

        $this->kategoriModel->id_kategori = $idKategori;
        $this->kategoriModel->nama_kategori = $namaKategori;
        $this->kategoriModel->deskripsi = $deskripsi;
        $this->kategoriModel->status = $status;

        if ($this->kategoriModel->update()) {
            $_SESSION['success'] = "Data kategori berhasil diperbarui.";
        } else {
            $_SESSION['error'] = "Gagal memperbarui kategori.";
        }
        header("Location: /Aplikasi_Peminjaman_Buku/admin/daftarKategori");
        exit;
    }

    public function hapus()
    {
        $idKategori = (int)($_GET['id_kategori'] ?? 0);

        if ($idKategori > 0) {
            if ($this->kategoriModel->isUsedInBuku($idKategori)) {
                $_SESSION['error'] = "Kategori tidak dapat dihapus karena masih terhubung dengan data buku di perpustakaan!";
            } else {
                $hapus = $this->kategoriModel->delete($idKategori);
                if ($hapus) {
                    $_SESSION['success'] = "Kategori berhasil dihapus.";
                } else {
                    $_SESSION['error'] = "Gagal menghapus kategori.";
                }
            }
        } else {
            $_SESSION['error'] = "ID Kategori tidak valid.";
        }

        header("Location: /Aplikasi_Peminjaman_Buku/admin/daftarKategori");
        exit;
    }
}

// Router
$controller = new C_Kategori();
$aksi = $_GET['aksi'] ?? '';

switch ($aksi) {
    case 'tambah': $controller->tambah(); break;
    case 'edit': $controller->edit(); break;
    case 'update': $controller->update(); break;
    case 'hapus': $controller->hapus(); break;
    default: $controller->index(); break;
}
?>