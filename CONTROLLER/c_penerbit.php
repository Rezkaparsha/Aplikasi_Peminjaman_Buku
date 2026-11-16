<?php
// =====================================================================
// FILE   : CONTROLLER/c_penerbit.php
// FUNGSI : Controller CRUD data PENERBIT (khusus ADMIN): tambah, ubah, hapus.
//          Halaman DAFTAR penerbit tidak lewat controller ini; .htaccess mengarahkan
//          /admin/daftarPenerbit langsung ke VIEW (view memanggil model sendiri).
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

// Memuat MODEL penerbit.
require_once __DIR__ . "/../MODEL/m_penerbit.php";

// Class controller penerbit.
class C_Penerbit
{
    // Objek model Penerbit (tabel penerbit).
    private $penerbitModel;

    // Constructor: buat objek model.
    public function __construct()
    {
        $this->penerbitModel = new Penerbit();
    }

    // AKSI index (default): tidak menampilkan apa pun sendiri, langsung redirect ke halaman daftar penerbit.
    public function index()
    {
        header("Location: /Aplikasi_Peminjaman_Buku/admin/daftarPenerbit");
        exit;
    }

    // AKSI tambah : MENYIMPAN penerbit baru dari form tambah (method POST).
    public function tambah()
    {
        // Hanya terima POST; selain itu kembalikan ke form.
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: /Aplikasi_Peminjaman_Buku/admin/tambahPenerbit");
            exit;
        }

        // Ambil data form; trim() membuang spasi di awal/akhir.
        $namaPenerbit = trim($_POST['nama_penerbit'] ?? '');
        $alamat       = trim($_POST['alamat'] ?? '');
        $kota         = trim($_POST['kota'] ?? '');
        $telepon      = trim($_POST['telepon'] ?? '');
        $email        = trim($_POST['email'] ?? '');

        // VALIDASI: nama penerbit wajib diisi.
        if ($namaPenerbit === '') {
            $_SESSION['error'] = "Nama penerbit wajib diisi.";
            header("Location: /Aplikasi_Peminjaman_Buku/admin/daftarPenerbit");
            exit;
        }

        // Isi properti model dari data form (model yang nanti menyimpan ke database).
        $this->penerbitModel->nama_penerbit = $namaPenerbit;
        $this->penerbitModel->alamat        = $alamat;
        $this->penerbitModel->kota          = $kota;
        $this->penerbitModel->telepon       = $telepon;
        $this->penerbitModel->email         = $email;

        // Simpan ke database lewat MODEL. Berhasil -> pesan sukses, gagal -> pesan error (disimpan di session lalu tampil di halaman daftar).
        if ($this->penerbitModel->insert()) {
            $_SESSION['success'] = "Penerbit berhasil ditambahkan.";
        } else {
            $_SESSION['error'] = "Gagal menambahkan penerbit.";
        }
        header("Location: /Aplikasi_Peminjaman_Buku/admin/daftarPenerbit");
        exit;
    }

    // AKSI edit : menampilkan FORM ubah penerbit (URL /admin/editPenerbit?id_penerbit=..).
    public function edit()
    {
        // Ambil id dari URL dan paksa jadi angka.
        $idPenerbit = (int) ($_GET['id_penerbit'] ?? 0);

        if ($idPenerbit <= 0) {
            header("Location: /Aplikasi_Peminjaman_Buku/admin/daftarPenerbit");
            exit;
        }

        // Ambil data penerbit dari database untuk mengisi form.
        $dataPenerbit = $this->penerbitModel->getById($idPenerbit);

        // Data tidak ada -> pesan error lalu kembali ke daftar.
        if (!$dataPenerbit) {
            $_SESSION['error'] = "Data penerbit tidak ditemukan.";
            header("Location: /Aplikasi_Peminjaman_Buku/admin/daftarPenerbit");
            exit;
        }

        // Tampilkan form edit; variabel $dataPenerbit bisa dipakai di file view.
        require_once __DIR__ . "/../VIEW/ADMIN/editPenerbit.php";
        exit;
    }

    // AKSI update : MENYIMPAN perubahan penerbit (POST dari form edit).
    public function update()
    {
        // Hanya terima POST.
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: /Aplikasi_Peminjaman_Buku/admin/daftarPenerbit");
            exit;
        }

        // id data yang diubah (dari input hidden di form).
        $idPenerbit   = (int) ($_POST['id_penerbit'] ?? 0);
        // Ambil data form.
        $namaPenerbit = trim($_POST['nama_penerbit'] ?? '');
        $alamat       = trim($_POST['alamat'] ?? '');
        $kota         = trim($_POST['kota'] ?? '');
        $telepon      = trim($_POST['telepon'] ?? '');
        $email        = trim($_POST['email'] ?? '');

        // VALIDASI: id harus valid dan nama tidak boleh kosong ('or' = salah satu gagal -> error).
        if ($idPenerbit <= 0 or $namaPenerbit === '') {
            $_SESSION['error'] = "Data penerbit tidak valid.";
            header("Location: /Aplikasi_Peminjaman_Buku/admin/daftarPenerbit");
            exit;
        }

        // Isi properti model, termasuk id (dipakai di WHERE saat UPDATE).
        $this->penerbitModel->id_penerbit   = $idPenerbit;
        $this->penerbitModel->nama_penerbit = $namaPenerbit;
        $this->penerbitModel->alamat        = $alamat;
        $this->penerbitModel->kota          = $kota;
        $this->penerbitModel->telepon       = $telepon;
        $this->penerbitModel->email         = $email;

        // Jalankan UPDATE lewat MODEL.
        if ($this->penerbitModel->update()) {
            $_SESSION['success'] = "Data penerbit berhasil diperbarui.";
        } else {
            $_SESSION['error'] = "Gagal memperbarui penerbit.";
        }
        header("Location: /Aplikasi_Peminjaman_Buku/admin/daftarPenerbit");
        exit;
    }

    // AKSI hapus : menghapus penerbit (id dari URL ?id_penerbit=..).
    public function hapus()
    {
        // Ambil id dari URL.
        $idPenerbit = (int) ($_GET['id_penerbit'] ?? 0);

        // Proses hanya jika id valid.
        if ($idPenerbit > 0) {
            // PROTEKSI: penerbit yang masih dipakai data buku TIDAK boleh dihapus (menjaga integritas data).
            if ($this->penerbitModel->isUsedInBuku($idPenerbit)) {
                $_SESSION['error'] = "Penerbit tidak dapat dihapus karena masih terhubung dengan data buku di perpustakaan!";
            } else {
                // Belum dipakai buku -> hapus. $hapus berisi true/false.
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
// ROUTER: bagian yang pertama berjalan saat file dibuka. $aksi dari URL menentukan method yang dipanggil.
$controller = new C_Penerbit();
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