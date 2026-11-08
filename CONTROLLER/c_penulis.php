<?php
// =====================================================================
// FILE   : CONTROLLER/c_penulis.php
// FUNGSI : Controller CRUD data PENULIS (khusus ADMIN): tambah, ubah, hapus.
//          Halaman DAFTAR penulis tidak lewat controller ini; .htaccess mengarahkan
//          /admin/daftarPenulis langsung ke VIEW (view memanggil model sendiri).
//          Form tambah/ubah mengirim POST ke file ini dengan ?aksi=tambah / update.
// =====================================================================
// Pastikan session aktif.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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

// Memuat MODEL penulis.
require_once __DIR__ . "/../MODEL/m_penulis.php";

// Class controller penulis.
class C_Penulis
{
    // Objek model Penulis (tabel penulis).
    private $penulisModel;

    // Constructor: buat objek model.
    public function __construct()
    {
        $this->penulisModel = new Penulis();
    }

    // AKSI index (default): tidak menampilkan apa pun sendiri, langsung redirect ke halaman daftar penulis.
    public function index()
    {
        header("Location: /Aplikasi_Peminjaman_Buku/admin/daftarPenulis");
        exit;
    }

    // AKSI tambah : MENYIMPAN penulis baru dari form tambah (method POST).
    public function tambah()
    {
        // Hanya terima POST; selain itu kembalikan ke form.
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: /Aplikasi_Peminjaman_Buku/admin/tambahPenulis");
            exit;
        }

        // Ambil data form; trim() membuang spasi di awal/akhir.
        $namaPenulis = trim($_POST['nama_penulis'] ?? '');

        // VALIDASI: nama penulis wajib diisi.
        if ($namaPenulis === '') {
            $_SESSION['error'] = "Nama penulis wajib diisi.";
            header("Location: /Aplikasi_Peminjaman_Buku/admin/daftarPenulis");
            exit;
        }

        // Isi properti model dari data form (model yang nanti menyimpan ke database).
        $this->penulisModel->nama_penulis = $namaPenulis;

        // Simpan ke database lewat MODEL. Berhasil -> pesan sukses, gagal -> pesan error (disimpan di session lalu tampil di halaman daftar).
        if ($this->penulisModel->insert()) {
            $_SESSION['success'] = "Penulis berhasil ditambahkan.";
        } else {
            $_SESSION['error'] = "Gagal menambahkan penulis.";
        }
        header("Location: /Aplikasi_Peminjaman_Buku/admin/daftarPenulis");
        exit;
    }

    // AKSI edit : menampilkan FORM ubah penulis (URL /admin/editPenulis?id_penulis=..).
    public function edit()
    {
        // Ambil id dari URL dan paksa jadi angka.
        $idPenulis = (int) ($_GET['id_penulis'] ?? 0);

        if ($idPenulis <= 0) {
            header("Location: /Aplikasi_Peminjaman_Buku/admin/daftarPenulis");
            exit;
        }

        // Ambil data penulis dari database untuk mengisi form.
        $dataPenulis = $this->penulisModel->getById($idPenulis);

        // Data tidak ada -> pesan error lalu kembali ke daftar.
        if (!$dataPenulis) {
            $_SESSION['error'] = "Data penulis tidak ditemukan.";
            header("Location: /Aplikasi_Peminjaman_Buku/admin/daftarPenulis");
            exit;
        }

        // Tampilkan form edit; variabel $dataPenulis bisa dipakai di file view.
        require_once __DIR__ . "/../VIEW/ADMIN/editPenulis.php";
        exit;
    }

    // AKSI update : MENYIMPAN perubahan penulis (POST dari form edit).
    public function update()
    {
        // Hanya terima POST.
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: /Aplikasi_Peminjaman_Buku/admin/daftarPenulis");
            exit;
        }

        // id data yang diubah (dari input hidden di form).
        $idPenulis   = (int) ($_POST['id_penulis'] ?? 0);
        // Ambil data form.
        $namaPenulis = trim($_POST['nama_penulis'] ?? '');

        // VALIDASI: id harus valid dan nama tidak boleh kosong ('or' = salah satu gagal -> error).
        if ($idPenulis <= 0 or $namaPenulis === '') {
            $_SESSION['error'] = "Data penulis tidak valid.";
            header("Location: /Aplikasi_Peminjaman_Buku/admin/daftarPenulis");
            exit;
        }

        // Isi properti model, termasuk id (dipakai di WHERE saat UPDATE).
        $this->penulisModel->id_penulis   = $idPenulis;
        $this->penulisModel->nama_penulis = $namaPenulis;

        // Jalankan UPDATE lewat MODEL.
        if ($this->penulisModel->update()) {
            $_SESSION['success'] = "Data penulis berhasil diperbarui.";
        } else {
            $_SESSION['error'] = "Gagal memperbarui penulis.";
        }
        header("Location: /Aplikasi_Peminjaman_Buku/admin/daftarPenulis");
        exit;
    }

    // AKSI hapus : menghapus penulis (id dari URL ?id_penulis=..).
    public function hapus()
    {
        // Ambil id dari URL.
        $id_penulis = (int)($_GET['id_penulis'] ?? 0);

        // Proses hanya jika id valid.
        if ($id_penulis > 0) {
            // PROTEKSI: penulis yang masih dipakai data buku TIDAK boleh dihapus (menjaga integritas data).
            if ($this->penulisModel->isUsedInBuku($id_penulis)) {
                $_SESSION['error'] = "Penulis tidak dapat dihapus karena masih terhubung dengan data buku di perpustakaan!";
            } else {
                // Belum dipakai buku -> hapus. $hapus berisi true/false.
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
// ROUTER: bagian yang pertama berjalan saat file dibuka. $aksi dari URL menentukan method yang dipanggil.
$controller = new C_Penulis();
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