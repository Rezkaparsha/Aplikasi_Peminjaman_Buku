<?php
// =====================================================================
// FILE   : CONTROLLER/c_kategori.php
// FUNGSI : Controller CRUD data KATEGORI (khusus ADMIN): tambah, ubah, hapus.
//          Halaman DAFTAR kategori tidak lewat controller ini; .htaccess mengarahkan
//          /admin/daftarKategori langsung ke VIEW (view memanggil model sendiri).
//          Form tambah/ubah mengirim POST ke file ini dengan ?aksi=tambah / update.
// =====================================================================
// Pastikan session aktif.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Proteksi Akses Admin & Rute Silang
// GERBANG KEAMANAN: hanya user login berrole 'admin' yang boleh lanjut.
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'admin') {
    // Bukan admin -> kembalikan ke halaman siswa terakhir / dashboard siswa.
    $kembali = $_SESSION['last_page_siswa'] ?? '/Aplikasi_Peminjaman_Buku/siswa/dashboardSiswa';
    // Belum login sama sekali -> arahkan ke halaman login.
    if (!isset($_SESSION['id_user'])) {
        $kembali = '/Aplikasi_Peminjaman_Buku/login';
    }
    header("Location: " . $kembali);
    exit;
}

// Simpan URL halaman admin terakhir
// Catat URL admin terakhir (dipakai untuk mengembalikan admin yang nyasar ke halaman siswa).
$_SESSION['last_page_admin'] = $_SERVER['REQUEST_URI'];

// Memuat MODEL kategori.
require_once __DIR__ . "/../MODEL/m_kategori.php";

// Class controller kategori.
class C_Kategori
{
    // Objek model Kategori (tabel kategori).
    private $kategoriModel;

    // Constructor: buat objek model.
    public function __construct()
    {
        $this->kategoriModel = new Kategori();
    }

    // AKSI index (default): tidak menampilkan apa pun sendiri, langsung redirect ke halaman daftar kategori.
    public function index()
    {
        header("Location: /Aplikasi_Peminjaman_Buku/admin/daftarKategori");
        exit;
    }

    // AKSI tambah : MENYIMPAN kategori baru dari form tambah (method POST).
    public function tambah()
    {
        // Hanya terima POST; selain itu kembalikan ke form.
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: /Aplikasi_Peminjaman_Buku/admin/tambahKategori");
            exit;
        }

        // Ambil data form; trim() membuang spasi di awal/akhir.
        $namaKategori = trim($_POST['nama_kategori'] ?? '');
        $deskripsi = trim($_POST['deskripsi'] ?? '');
        $status = $_POST['status'] ?? 'aktif';

        // VALIDASI: nama kategori wajib diisi.
        if ($namaKategori === '') {
            $_SESSION['error'] = "Nama kategori wajib diisi.";
            header("Location: /Aplikasi_Peminjaman_Buku/admin/daftarKategori");
            exit;
        }

        // Status hanya boleh 'aktif' atau 'nonaktif' (whitelist); nilai lain dipaksa 'aktif'. true = perbandingan ketat.
        if (!in_array($status, ['aktif', 'nonaktif'], true)) {
            $status = 'aktif';
        }

        // Isi properti model dari data form (model yang nanti menyimpan ke database).
        $this->kategoriModel->nama_kategori = $namaKategori;
        $this->kategoriModel->deskripsi = $deskripsi;
        $this->kategoriModel->status = $status;

        // Simpan ke database lewat MODEL. Berhasil -> pesan sukses, gagal -> pesan error (disimpan di session lalu tampil di halaman daftar).
        if ($this->kategoriModel->insert()) {
            $_SESSION['success'] = "Kategori berhasil ditambahkan.";
        } else {
            $_SESSION['error'] = "Gagal menambahkan kategori.";
        }
        header("Location: /Aplikasi_Peminjaman_Buku/admin/daftarKategori");
        exit;
    }

    // AKSI edit : menampilkan FORM ubah kategori (URL /admin/editKategori?id_kategori=..).
    public function edit()
    {
        // Ambil id dari URL dan paksa jadi angka.
        $idKategori = (int)($_GET['id_kategori'] ?? 0);

        if ($idKategori <= 0) {
            header("Location: /Aplikasi_Peminjaman_Buku/admin/daftarKategori");
            exit;
        }

        // Ambil data kategori dari database untuk mengisi form.
        $dataKategori = $this->kategoriModel->getById($idKategori);

        // Data tidak ada -> pesan error lalu kembali ke daftar.
        if (!$dataKategori) {
            $_SESSION['error'] = "Data kategori tidak ditemukan.";
            header("Location: /Aplikasi_Peminjaman_Buku/admin/daftarKategori");
            exit;
        }

        // Tampilkan form edit; variabel $dataKategori bisa dipakai di file view.
        require_once __DIR__ . "/../VIEW/ADMIN/editKategori.php";
        exit;
    }

    // AKSI update : MENYIMPAN perubahan kategori (POST dari form edit).
    public function update()
    {
        // Hanya terima POST.
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: /Aplikasi_Peminjaman_Buku/admin/daftarKategori");
            exit;
        }

        // id data yang diubah (dari input hidden di form).
        // Ambil id dari URL.
        $idKategori = (int)($_POST['id_kategori'] ?? 0);
        // Ambil data form.
        $namaKategori = trim($_POST['nama_kategori'] ?? '');
        $deskripsi = trim($_POST['deskripsi'] ?? '');
        $status = $_POST['status'] ?? 'aktif';

        // VALIDASI: id harus valid dan nama tidak boleh kosong ('or' = salah satu gagal -> error).
        if ($idKategori <= 0 or $namaKategori === '') {
            $_SESSION['error'] = "Data kategori tidak valid.";
            header("Location: /Aplikasi_Peminjaman_Buku/admin/daftarKategori");
            exit;
        }

        if (!in_array($status, ['aktif', 'nonaktif'], true)) {
            $status = 'aktif';
        }

        // Isi properti model, termasuk id (dipakai di WHERE saat UPDATE).
        $this->kategoriModel->id_kategori = $idKategori;
        $this->kategoriModel->nama_kategori = $namaKategori;
        $this->kategoriModel->deskripsi = $deskripsi;
        $this->kategoriModel->status = $status;

        // Jalankan UPDATE lewat MODEL.
        if ($this->kategoriModel->update()) {
            $_SESSION['success'] = "Data kategori berhasil diperbarui.";
        } else {
            $_SESSION['error'] = "Gagal memperbarui kategori.";
        }
        header("Location: /Aplikasi_Peminjaman_Buku/admin/daftarKategori");
        exit;
    }

    // AKSI hapus : menghapus kategori (id dari URL ?id_kategori=..).
    public function hapus()
    {
        $idKategori = (int)($_GET['id_kategori'] ?? 0);

        // Proses hanya jika id valid.
        if ($idKategori > 0) {
            // PROTEKSI: kategori yang masih dipakai data buku TIDAK boleh dihapus (menjaga integritas data).
            if ($this->kategoriModel->isUsedInBuku($idKategori)) {
                $_SESSION['error'] = "Kategori tidak dapat dihapus karena masih terhubung dengan data buku di perpustakaan!";
            } else {
                // Belum dipakai buku -> hapus. $hapus berisi true/false.
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
// ROUTER: bagian yang pertama berjalan saat file dibuka. $aksi dari URL menentukan method yang dipanggil.
$controller = new C_Kategori();
// Baca ?aksi= dari URL; kosong jika tidak ada.
$aksi = $_GET['aksi'] ?? '';

switch ($aksi) {
    case 'tambah': $controller->tambah(); break;
    case 'edit': $controller->edit(); break;
    case 'update': $controller->update(); break;
    case 'hapus': $controller->hapus(); break;
    // Aksi kosong/tidak dikenal -> index (redirect ke daftar).
    default: $controller->index(); break;
}
?>