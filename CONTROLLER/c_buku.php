<?php
// =====================================================================
// FILE   : CONTROLLER/c_buku.php
// FUNGSI : Controller CRUD BUKU (khusus ADMIN): lihat, tambah, ubah, hapus.
//          Juga mengurus upload cover dan relasi buku <-> penulis.
// URL    : /admin/daftarBuku, /admin/tambahBuku, /admin/editBuku
//          (diarahkan .htaccess ke file ini dengan ?aksi=...)
//          Aksi simpan/ubah/hapus dipanggil langsung dari form/tombol di VIEW.
// =====================================================================
// Pastikan session aktif.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// GERBANG KEAMANAN utama: yang boleh masuk hanya user login dengan role 'admin'.
// Proteksi Akses Admin & Rute Silang
// Belum login ATAU bukan admin -> tolak dan arahkan pergi.
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'admin') {
    // Default: kembalikan ke halaman siswa terakhir / dashboard siswa.
    $kembali = $_SESSION['last_page_siswa'] ?? '/Aplikasi_Peminjaman_Buku/siswa/dashboardSiswa';
    // Kalau sama sekali belum login -> arahkan ke halaman login.
    if (!isset($_SESSION['id_user'])) {
        $kembali = '/Aplikasi_Peminjaman_Buku/login';
    }
    header("Location: " . $kembali);
    exit;
}

// Simpan URL Clean URL admin terakhir yang sah
// Ambil URL yang sedang dibuka.
$current_uri = $_SERVER['REQUEST_URI'] ?? '';
// Catat URL bersih admin (bukan URL CONTROLLER/) sebagai halaman admin terakhir yang sah.
if (strpos($current_uri, 'CONTROLLER/') === false && strpos($current_uri, '/admin/') !== false) {
    $_SESSION['last_page_admin'] = $current_uri;
}

// Memuat semua MODEL yang dibutuhkan: buku, relasi buku-penulis, kategori, penerbit, penulis.
require_once __DIR__ . "/../MODEL/m_buku.php";
require_once __DIR__ . "/../MODEL/m_buku_penulis.php";
require_once __DIR__ . "/../MODEL/m_kategori.php";
require_once __DIR__ . "/../MODEL/m_penerbit.php";
require_once __DIR__ . "/../MODEL/m_penulis.php";

// Class controller buku. Setiap method = satu aksi.
class C_Buku
{
    // Objek model Buku (tabel buku).
    private $bukuModel;
    // Objek model BukuPenulis (tabel penghubung buku_penulis).
    private $bukuPenulisModel;

    // Constructor: buat kedua objek model saat controller dibuat.
    public function __construct()
    {
        $this->bukuModel = new Buku();
        $this->bukuPenulisModel = new BukuPenulis();
    }

    // AKSI index : tampilkan daftar semua buku (URL /admin/daftarBuku).
    public function index()
    {
        // Pengecekan role kedua (pengaman berlapis).
        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
            header("Location: /Aplikasi_Peminjaman_Buku/siswa/dashboardSiswa");
            exit;
        }

        // Ambil semua buku (sudah digabung dengan kategori, penerbit, penulis) dari MODEL.
        $allBuku = $this->bukuModel->getAllBuku();
        // Tampilkan VIEW; variabel $allBuku bisa dipakai di file view.
        require_once __DIR__ . "/../VIEW/ADMIN/daftarBuku.php";
        exit;
    }

    // AKSI tambah_view : menampilkan FORM tambah buku. Form butuh data dropdown kategori, penerbit, dan daftar penulis.
    public function tambah_view()
    {
        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
            header("Location: /Aplikasi_Peminjaman_Buku/siswa/dashboardSiswa");
            exit;
        }

        // Model untuk mengisi dropdown pada form.
        $kategoriModel = new Kategori();
        $penerbitModel = new Penerbit();
        $penulisModel = new Penulis();

        // Data untuk dropdown kategori.
        $dataKategori = $kategoriModel->getAll();
        // Data untuk dropdown penerbit.
        $dataPenerbit = $penerbitModel->getAll();
        // Data untuk pilihan penulis (boleh lebih dari satu).
        $dataSemuaPenulis = $penulisModel->getAll();

        // Tampilkan form tambah buku.
        require_once __DIR__ . "/../VIEW/ADMIN/tambahBuku.php";
        exit;
    }

    // AKSI tambah : MENYIMPAN buku baru (dari submit form tambah, method POST, form berisi upload file).
    public function tambah()
    {
        // Hanya terima POST; kalau dibuka lewat URL biasa kembalikan ke form.
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: /Aplikasi_Peminjaman_Buku/admin/tambahBuku");
            exit;
        }

        // Isi properti model dari data form. (int) memaksa jadi angka, ?? 0 = default bila kosong.
        $this->bukuModel->id_kategori = (int) ($_POST['id_kategori'] ?? 0);
        $this->bukuModel->id_penerbit = (int) ($_POST['id_penerbit'] ?? 0);
        // trim() membuang spasi di awal/akhir judul.
        $this->bukuModel->judul_buku = trim($_POST['judul_buku'] ?? '');
        $this->bukuModel->tahun_terbit = (int) ($_POST['tahun_terbit'] ?? 0);
        $this->bukuModel->harga_buku = (int) ($_POST['harga_buku'] ?? 0);
        $this->bukuModel->stok = (int) ($_POST['stok'] ?? 0);

        // VALIDASI: kategori & penerbit harus dipilih, judul tidak kosong, tahun valid, stok tidak negatif.
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

        // Nama file cover; kosong bila admin tidak mengunggah gambar.
        $namaCover = '';
        // UPLOAD COVER: jalan hanya jika ada file dan upload sukses ($_FILES = data file dari form).
        if (isset($_FILES['cover']) && $_FILES['cover']['error'] === UPLOAD_ERR_OK) {
            $namaFile = $_FILES['cover']['name'];
            $tmpFile = $_FILES['cover']['tmp_name'];
            // Ambil ekstensi file (jpg/png/...) dalam huruf kecil.
            $extension = strtolower(pathinfo($namaFile, PATHINFO_EXTENSION));
            // Daftar ekstensi yang diizinkan (whitelist).
            $allowed = ['jpg', 'jpeg', 'png', 'webp'];

            // Ekstensi tidak diizinkan -> tolak (mencegah upload file berbahaya seperti .php).
            if (!in_array($extension, $allowed)) {
                $_SESSION['error'] = "Format cover tidak diperbolehkan.";
                header("Location: /Aplikasi_Peminjaman_Buku/admin/tambahBuku");
                exit;
            }

            // Buat nama file acak unik agar tidak menimpa file lain (contoh: cover_6ab46....jpg).
            $namaCover = uniqid('cover_', true) . '.' . $extension;
            // Folder penyimpanan cover.
            $folderCover = __DIR__ . "/../ASSETS/COVER/";

            // Buat folder jika belum ada (0777 = izin penuh, true = boleh membuat folder bertingkat).
            if (!is_dir($folderCover)) {
                mkdir($folderCover, 0777, true);
            }

            // Pindahkan file dari lokasi sementara PHP ke folder ASSETS/COVER.
            move_uploaded_file($tmpFile, $folderCover . $namaCover);
        }

        // Simpan nama file cover ke properti model.
        $this->bukuModel->cover = $namaCover;

        // Simpan buku ke database lewat MODEL. true = berhasil.
        if ($this->bukuModel->insert()) {
            // Ambil id buku yang baru dibuat (insert_id) untuk menyimpan relasi penulis.
            $idBuku = $this->bukuModel->id_buku;
            // Penulis dikirim sebagai array (satu buku boleh banyak penulis).
            $penulis = $_POST['id_penulis'] ?? [];

            // Jika ternyata bukan array, bungkus jadi array supaya bisa di-loop.
            if (!is_array($penulis)) {
                $penulis = [$penulis];
            }

            // Simpan satu baris ke tabel buku_penulis untuk setiap penulis yang dipilih.
            foreach ($penulis as $idPenulis) {
                $idPenulis = (int) $idPenulis;
                if ($idPenulis > 0) {
                    $this->bukuPenulisModel->id_buku = $idBuku;
                    $this->bukuPenulisModel->id_penulis = $idPenulis;
                    // INSERT relasi (id_buku, id_penulis).
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

    // AKSI edit : menampilkan FORM ubah buku (URL /admin/editBuku?id_buku=..).
    public function edit()
    {
        // Ambil id buku dari URL.
        $idBuku = (int) ($_GET['id_buku'] ?? 0);

        if ($idBuku <= 0) {
            header("Location: /Aplikasi_Peminjaman_Buku/admin/daftarBuku");
            exit;
        }

        // Ambil data buku untuk mengisi form.
        $dataBuku = $this->bukuModel->getBukuById($idBuku);

        // Buku tidak ada -> pesan error dan kembali ke daftar.
        if (!$dataBuku) {
            $_SESSION['error'] = "Data buku tidak ditemukan.";
            header("Location: /Aplikasi_Peminjaman_Buku/admin/daftarBuku");
            exit;
        }

        // Penulis yang saat ini terhubung dengan buku (untuk dicentang di form).
        $dataPenulis = $this->bukuPenulisModel->getPenulisByBuku($idBuku);
        // Model dropdown.
        $kategoriModel = new Kategori();
        $penerbitModel = new Penerbit();
        $penulisModel = new Penulis();

        // Data dropdown kategori.
        $dataKategori = $kategoriModel->getAll();
        $dataPenerbit = $penerbitModel->getAll();
        // Semua penulis (pilihan).
        $dataSemuaPenulis = $penulisModel->getAll();

        // Tampilkan form edit.
        require_once __DIR__ . "/../VIEW/ADMIN/editBuku.php";
        exit;
    }

    // AKSI update : MENYIMPAN perubahan buku (POST dari form edit).
    public function update()
    {
        // Hanya POST.
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: /Aplikasi_Peminjaman_Buku/admin/daftarBuku");
            exit;
        }

        // id buku yang diubah (dari input hidden form).
        $idBuku = (int) ($_POST['id_buku'] ?? 0);

        if ($idBuku <= 0) {
            header("Location: /Aplikasi_Peminjaman_Buku/admin/daftarBuku");
            exit;
        }

        // Ambil data lama (terutama nama cover lama).
        $dataLama = $this->bukuModel->getBukuById($idBuku);

        // Data tidak ditemukan.
        if (!$dataLama) {
            $_SESSION['error'] = "Data buku tidak ditemukan.";
            header("Location: /Aplikasi_Peminjaman_Buku/admin/daftarBuku");
            exit;
        }

        // Isi properti model dengan data baru dari form.
        $this->bukuModel->id_buku = $idBuku;
        $this->bukuModel->id_kategori = (int) ($_POST['id_kategori'] ?? 0);
        $this->bukuModel->id_penerbit = (int) ($_POST['id_penerbit'] ?? 0);
        $this->bukuModel->judul_buku = trim($_POST['judul_buku'] ?? '');
        $this->bukuModel->tahun_terbit = (int) ($_POST['tahun_terbit'] ?? 0);
        $this->bukuModel->harga_buku = (int) ($_POST['harga_buku'] ?? 0);
        $this->bukuModel->stok = (int) ($_POST['stok'] ?? 0);

        // Default: pakai cover lama. Hanya diganti jika admin mengunggah cover baru.
        $namaCover = $dataLama['cover'];

        // Ada cover baru yang diunggah.
        if (isset($_FILES['cover']) && $_FILES['cover']['error'] === UPLOAD_ERR_OK) {
            $namaFile = $_FILES['cover']['name'];
            $tmpFile = $_FILES['cover']['tmp_name'];
            // Ekstensi file baru.
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

            // Nama unik untuk cover baru.
            $namaCoverBaru = uniqid('cover_', true) . '.' . $extension;

            // Jika cover lama ada di folder...
            if (!empty($dataLama['cover']) && file_exists($folderCover . $dataLama['cover'])) {
                // ...hapus file cover lama (unlink) supaya tidak menumpuk.
                unlink($folderCover . $dataLama['cover']);
            }

            // Simpan file cover baru.
            move_uploaded_file($tmpFile, $folderCover . $namaCoverBaru);
            // Pakai nama cover baru.
            $namaCover = $namaCoverBaru;
        }

        // Simpan nama cover (lama/baru) ke model.
        $this->bukuModel->cover = $namaCover;

        // UPDATE data buku di database.
        if ($this->bukuModel->update()) {
            // Relasi penulis diperbarui dengan cara: hapus SEMUA relasi lama, lalu masukkan ulang sesuai pilihan baru.
            $this->bukuPenulisModel->deleteByBuku($idBuku);
            // Penulis terpilih (array).
            $penulis = $_POST['id_penulis'] ?? [];

            if (!is_array($penulis)) {
                $penulis = [$penulis];
            }

            // Masukkan ulang relasi penulis.
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

    // AKSI hapus : menghapus buku beserta file cover dan relasi penulisnya (id lewat URL ?id_buku=..).
    public function hapus()
    {
        // Ambil id dari URL.
        $idBuku = (int) ($_GET['id_buku'] ?? 0);

        if ($idBuku <= 0) {
            header("Location: /Aplikasi_Peminjaman_Buku/admin/daftarBuku");
            exit;
        }

        // Ambil data buku (butuh nama cover untuk dihapus filenya).
        $dataBuku = $this->bukuModel->getBukuById($idBuku);

        if (!$dataBuku) {
            $_SESSION['error'] = "Data buku tidak ditemukan.";
            header("Location: /Aplikasi_Peminjaman_Buku/admin/daftarBuku");
            exit;
        }

        // try/catch menangkap error database (mis. buku sudah punya riwayat peminjaman).
        try {
            // Hapus dulu relasi buku-penulis (tabel anak) sebelum menghapus buku.
            // CATATAN BELAJAR: langkah ini tidak dibungkus transaksi. Jika penghapusan buku
            // gagal sesudahnya (karena FK), relasi penulis yang sudah terhapus tidak kembali.
            $this->bukuPenulisModel->deleteByBuku($idBuku);

            // Hapus baris buku. Foreign key ON DELETE RESTRICT akan MENOLAK jika buku masih dipakai di detail_peminjaman.
            if ($this->bukuModel->delete($idBuku)) {
                // Jika buku punya cover, hapus juga filenya dari folder.
                if (!empty($dataBuku['cover'])) {
                    // Alamat lengkap file cover.
                    $fileCover = __DIR__ . "/../ASSETS/COVER/" . $dataBuku['cover'];
                    if (file_exists($fileCover)) {
                        unlink($fileCover);
                    }
                }
                $_SESSION['success'] = "Buku berhasil dihapus.";
            } else {
                $_SESSION['error'] = "Gagal menghapus data buku.";
            }
        // Menangkap error MySQL.
        } catch (mysqli_sql_exception $e) {
            // Kode error 1451 = gagal karena foreign key (data masih dipakai tabel lain).
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
// ROUTER: bagian yang pertama berjalan saat file dibuka. $aksi dari URL menentukan method yang dipanggil.
$controller = new C_Buku();
// Default aksi = index (daftar buku).
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