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

// Simpan URL Clean URL admin terakhir yang sah
$current_uri = $_SERVER['REQUEST_URI'] ?? '';
if (strpos($current_uri, 'CONTROLLER/') === false && strpos($current_uri, '/admin/') !== false) {
    $_SESSION['last_page_admin'] = $current_uri;
}

require_once __DIR__ . "/../MODEL/m_buku.php";
require_once __DIR__ . "/../MODEL/m_buku_penulis.php";
require_once __DIR__ . "/../MODEL/m_kategori.php";
require_once __DIR__ . "/../MODEL/m_penerbit.php";
require_once __DIR__ . "/../MODEL/m_penulis.php";

class C_Buku
{
    private $bukuModel;
    private $bukuPenulisModel;

    public function __construct()
    {
        $this->bukuModel = new Buku();
        $this->bukuPenulisModel = new BukuPenulis();
    }

    public function index()
    {
        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
            header("Location: /Aplikasi_Peminjaman_Buku/siswa/dashboardSiswa");
            exit;
        }

        $allBuku = $this->bukuModel->getAllBuku();
        require_once __DIR__ . "/../VIEW/ADMIN/daftarBuku.php";
        exit;
    }

    public function tambah_view()
    {
        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
            header("Location: /Aplikasi_Peminjaman_Buku/siswa/dashboardSiswa");
            exit;
        }

        $kategoriModel = new Kategori();
        $penerbitModel = new Penerbit();
        $penulisModel = new Penulis();

        $dataKategori = $kategoriModel->getAll();
        $dataPenerbit = $penerbitModel->getAll();
        $dataSemuaPenulis = $penulisModel->getAll();

        require_once __DIR__ . "/../VIEW/ADMIN/tambahBuku.php";
        exit;
    }

    public function tambah()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: /Aplikasi_Peminjaman_Buku/admin/tambahBuku");
            exit;
        }

        $this->bukuModel->id_kategori = (int) ($_POST['id_kategori'] ?? 0);
        $this->bukuModel->id_penerbit = (int) ($_POST['id_penerbit'] ?? 0);
        $this->bukuModel->judul_buku = trim($_POST['judul_buku'] ?? '');
        $this->bukuModel->tahun_terbit = (int) ($_POST['tahun_terbit'] ?? 0);
        $this->bukuModel->harga_buku = (int) ($_POST['harga_buku'] ?? 0);
        $this->bukuModel->stok = (int) ($_POST['stok'] ?? 0);

        if (
            $this->bukuModel->id_kategori <= 0 ||
            $this->bukuModel->id_penerbit <= 0 ||
            $this->bukuModel->judul_buku === '' ||
            $this->bukuModel->tahun_terbit <= 0 ||
            $this->bukuModel->stok < 0
        ) {
            $_SESSION['error'] = "Data buku belum lengkap.";
            header("Location: /Aplikasi_Peminjaman_Buku/admin/tambahBuku");
            exit;
        }

        $namaCover = '';
        if (isset($_FILES['cover']) && $_FILES['cover']['error'] === UPLOAD_ERR_OK) {
            $namaFile = $_FILES['cover']['name'];
            $tmpFile = $_FILES['cover']['tmp_name'];
            $extension = strtolower(pathinfo($namaFile, PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'webp'];

            if (!in_array($extension, $allowed)) {
                $_SESSION['error'] = "Format cover tidak diperbolehkan.";
                header("Location: /Aplikasi_Peminjaman_Buku/admin/tambahBuku");
                exit;
            }

            $namaCover = uniqid('cover_', true) . '.' . $extension;
            $folderCover = __DIR__ . "/../ASSETS/COVER/";

            if (!is_dir($folderCover)) {
                mkdir($folderCover, 0777, true);
            }

            move_uploaded_file($tmpFile, $folderCover . $namaCover);
        }

        $this->bukuModel->cover = $namaCover;

        if ($this->bukuModel->insert()) {
            $idBuku = $this->bukuModel->id_buku;
            $penulis = $_POST['id_penulis'] ?? [];

            if (!is_array($penulis)) {
                $penulis = [$penulis];
            }

            foreach ($penulis as $idPenulis) {
                $idPenulis = (int) $idPenulis;
                if ($idPenulis > 0) {
                    $this->bukuPenulisModel->id_buku = $idBuku;
                    $this->bukuPenulisModel->id_penulis = $idPenulis;
                    $this->bukuPenulisModel->insert();
                }
            }

            $_SESSION['success'] = "Buku berhasil ditambahkan.";
            header("Location: /Aplikasi_Peminjaman_Buku/admin/daftarBuku");
            exit;
        } else {
            $_SESSION['error'] = "Gagal menambahkan buku.";
            header("Location: /Aplikasi_Peminjaman_Buku/admin/tambahBuku");
            exit;
        }
    }

    public function edit()
    {
        $idBuku = (int) ($_GET['id_buku'] ?? 0);

        if ($idBuku <= 0) {
            header("Location: /Aplikasi_Peminjaman_Buku/admin/daftarBuku");
            exit;
        }

        $dataBuku = $this->bukuModel->getBukuById($idBuku);

        if (!$dataBuku) {
            $_SESSION['error'] = "Data buku tidak ditemukan.";
            header("Location: /Aplikasi_Peminjaman_Buku/admin/daftarBuku");
            exit;
        }

        $dataPenulis = $this->bukuPenulisModel->getPenulisByBuku($idBuku);
        $kategoriModel = new Kategori();
        $penerbitModel = new Penerbit();
        $penulisModel = new Penulis();

        $dataKategori = $kategoriModel->getAll();
        $dataPenerbit = $penerbitModel->getAll();
        $dataSemuaPenulis = $penulisModel->getAll();

        require_once __DIR__ . "/../VIEW/ADMIN/editBuku.php";
        exit;
    }

    public function update()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: /Aplikasi_Peminjaman_Buku/admin/daftarBuku");
            exit;
        }

        $idBuku = (int) ($_POST['id_buku'] ?? 0);

        if ($idBuku <= 0) {
            header("Location: /Aplikasi_Peminjaman_Buku/admin/daftarBuku");
            exit;
        }

        $dataLama = $this->bukuModel->getBukuById($idBuku);

        if (!$dataLama) {
            $_SESSION['error'] = "Data buku tidak ditemukan.";
            header("Location: /Aplikasi_Peminjaman_Buku/admin/daftarBuku");
            exit;
        }

        $this->bukuModel->id_buku = $idBuku;
        $this->bukuModel->id_kategori = (int) ($_POST['id_kategori'] ?? 0);
        $this->bukuModel->id_penerbit = (int) ($_POST['id_penerbit'] ?? 0);
        $this->bukuModel->judul_buku = trim($_POST['judul_buku'] ?? '');
        $this->bukuModel->tahun_terbit = (int) ($_POST['tahun_terbit'] ?? 0);
        $this->bukuModel->harga_buku = (int) ($_POST['harga_buku'] ?? 0);
        $this->bukuModel->stok = (int) ($_POST['stok'] ?? 0);

        $namaCover = $dataLama['cover'];

        if (isset($_FILES['cover']) && $_FILES['cover']['error'] === UPLOAD_ERR_OK) {
            $namaFile = $_FILES['cover']['name'];
            $tmpFile = $_FILES['cover']['tmp_name'];
            $extension = strtolower(pathinfo($namaFile, PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'webp'];

            if (!in_array($extension, $allowed)) {
                $_SESSION['error'] = "Format cover tidak diperbolehkan.";
                header("Location: /Aplikasi_Peminjaman_Buku/admin/editBuku?id_buku=" . $idBuku);
                exit;
            }

            $folderCover = __DIR__ . "/../ASSETS/COVER/";

            if (!is_dir($folderCover)) {
                mkdir($folderCover, 0777, true);
            }

            $namaCoverBaru = uniqid('cover_', true) . '.' . $extension;

            if (!empty($dataLama['cover']) && file_exists($folderCover . $dataLama['cover'])) {
                unlink($folderCover . $dataLama['cover']);
            }

            move_uploaded_file($tmpFile, $folderCover . $namaCoverBaru);
            $namaCover = $namaCoverBaru;
        }

        $this->bukuModel->cover = $namaCover;

        if ($this->bukuModel->update()) {
            $this->bukuPenulisModel->deleteByBuku($idBuku);
            $penulis = $_POST['id_penulis'] ?? [];

            if (!is_array($penulis)) {
                $penulis = [$penulis];
            }

            foreach ($penulis as $idPenulis) {
                $idPenulis = (int) $idPenulis;
                if ($idPenulis > 0) {
                    $this->bukuPenulisModel->id_buku = $idBuku;
                    $this->bukuPenulisModel->id_penulis = $idPenulis;
                    $this->bukuPenulisModel->insert();
                }
            }

            $_SESSION['success'] = "Data buku berhasil diperbarui.";
            header("Location: /Aplikasi_Peminjaman_Buku/admin/daftarBuku");
            exit;
        } else {
            $_SESSION['error'] = "Gagal memperbarui data buku.";
            header("Location: /Aplikasi_Peminjaman_Buku/admin/editBuku?id_buku=" . $idBuku);
            exit;
        }
    }

    public function hapus()
    {
        $idBuku = (int) ($_GET['id_buku'] ?? 0);

        if ($idBuku <= 0) {
            header("Location: /Aplikasi_Peminjaman_Buku/admin/daftarBuku");
            exit;
        }

        $dataBuku = $this->bukuModel->getBukuById($idBuku);

        if (!$dataBuku) {
            $_SESSION['error'] = "Data buku tidak ditemukan.";
            header("Location: /Aplikasi_Peminjaman_Buku/admin/daftarBuku");
            exit;
        }

        try {
            $this->bukuPenulisModel->deleteByBuku($idBuku);

            if ($this->bukuModel->delete($idBuku)) {
                if (!empty($dataBuku['cover'])) {
                    $fileCover = __DIR__ . "/../ASSETS/COVER/" . $dataBuku['cover'];
                    if (file_exists($fileCover)) {
                        unlink($fileCover);
                    }
                }
                $_SESSION['success'] = "Buku berhasil dihapus.";
            } else {
                $_SESSION['error'] = "Gagal menghapus data buku.";
            }
        } catch (mysqli_sql_exception $e) {
            if ($e->getCode() === 1451) {
                $_SESSION['error'] = "Buku tidak dapat dihapus karena sedang dipinjam atau memiliki riwayat transaksi peminjaman!";
            } else {
                $_SESSION['error'] = "Gagal menghapus buku: " . $e->getMessage();
            }
        }

        header("Location: /Aplikasi_Peminjaman_Buku/admin/daftarBuku");
        exit;
    }
}

// Router
$controller = new C_Buku();
$aksi = $_GET['aksi'] ?? 'index';

switch ($aksi) {
    case 'index': $controller->index(); break;
    case 'tambah_view': $controller->tambah_view(); break;
    case 'tambah': $controller->tambah(); break;
    case 'edit': $controller->edit(); break;
    case 'update': $controller->update(); break;
    case 'hapus': $controller->hapus(); break;
    default: $controller->index(); break;
}
?>