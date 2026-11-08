<?php
// =====================================================================
// FILE   : CONTROLLER/c_user.php
// FUNGSI : Controller pengelolaan USER: tambah, ubah, hapus user (dipakai admin)
//          dan ubah password (dipakai admin untuk user lain, serta siswa
//          untuk dirinya sendiri dari halaman profil).
//          Halaman daftar/tambah/edit user adalah VIEW yang diarahkan langsung
//          oleh .htaccess; form-nya mengirim ke file ini dengan ?aksi=...
// BERBEDA dari controller lain: file ini TIDAK memakai method per aksi di router.
// Class UserController hanya berisi fungsi bantu; logika request ada di switch
// pada bagian bawah file.
// =====================================================================
// Pastikan session aktif.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Gerbang: hanya cek SUDAH LOGIN. CATATAN BELAJAR: tidak ada pengecekan role 'admin' di file ini,
// berbeda dengan c_buku / c_kategori. Artinya akses aksi tambah/update/hapus hanya dibatasi login.
if (!isset($_SESSION['id_user'])) {
    header("Location: /Aplikasi_Peminjaman_Buku/login");
    exit;
}

// Catat jejak otomatis berdasarkan role yang sedang aktif
// Catat halaman terakhir sesuai role: admin -> last_page_admin, siswa -> last_page_siswa.
if ($_SESSION['role'] === 'admin') {
    $_SESSION['last_page_admin'] = $_SERVER['REQUEST_URI'];
} else {
    $_SESSION['last_page_siswa'] = $_SERVER['REQUEST_URI'];
}

// Memuat MODEL user (class Users).
require_once __DIR__ . "/../MODEL/m_users.php";

// Class pembungkus: tiap method memanggil fungsi di model Users.
class UserController
{
    // Objek model Users.
    private $user;

    // Constructor: buat objek model Users.
    public function __construct()
    {
        $this->user = new Users();
    }

    // TAMPILKAN SEMUA USER
    // index(): mengembalikan semua user (array).
    public function index()
    {
        return $this->user->getAll();
    }

    // DETAIL USER
    // detail(): mengembalikan satu user berdasarkan id.
    public function detail($id_user)
    {
        return $this->user->getById($id_user);
    }

    // TAMBAH USER
    // tambah(): isi properti model, lalu insert(). Password di-hash dulu (password_hash, algoritma bawaan PHP = bcrypt) sebelum disimpan.
    public function tambah($nis_nip, $nama_lengkap, $kelas, $username, $password, $role)
    {
        $this->user->nis_nip      = $nis_nip;
        $this->user->nama_lengkap = $nama_lengkap;
        $this->user->kelas        = $kelas;
        $this->user->username     = $username;
        $this->user->password     = password_hash($password, PASSWORD_DEFAULT);
        $this->user->role         = $role;

        return $this->user->insert();
    }

    // UPDATE USER (tanpa password)
    // update(): ubah data user (password TIDAK ikut diubah).
    public function update($id_user, $nis_nip, $nama_lengkap, $kelas, $username, $role)
    {
        $this->user->id_user      = $id_user;
        $this->user->nis_nip      = $nis_nip;
        $this->user->nama_lengkap = $nama_lengkap;
        $this->user->kelas        = $kelas;
        $this->user->username     = $username;
        $this->user->role         = $role;

        return $this->user->update();
    }

    // UPDATE PASSWORD
    // updatePassword(): ganti password; password baru di-hash lebih dulu.
    public function updatePassword($id_user, $password)
    {
        $this->user->id_user  = $id_user;
        $this->user->password = password_hash($password, PASSWORD_DEFAULT);

        return $this->user->updatePassword();
    }

    // HAPUS USER DENGAN TANGKAPAN ERROR (TRY-CATCH)
    // hapus(): menghapus user. Jika user punya riwayat peminjaman, MySQL menolak (foreign key, error 1451) dan pesan error disimpan di session.
    public function hapus($id_user)
    {
        // try/catch: menangkap error duplikat (NIS/username yang sama) supaya tidak muncul fatal error.
        try {
            return $this->user->delete($id_user);
        // Tangkap error MySQL; 1451 = data masih dipakai tabel lain.
        } catch (mysqli_sql_exception $e) {
            if ($e->getCode() === 1451) {
                $_SESSION['error'] = "User tidak dapat dihapus karena memiliki riwayat peminjaman buku!";
            } else {
                $_SESSION['error'] = "Gagal menghapus user: " . $e->getMessage();
            }
            return false;
        }
    }
}


// =============================================================
// HANDLER REQUEST
// =============================================================
// ROUTER (berupa switch): bagian yang pertama berjalan saat file dibuka. Nilai ?aksi= menentukan blok case yang dijalankan.
$controller = new UserController();
// Baca ?aksi= dari URL.
$aksi = $_GET['aksi'] ?? '';

switch ($aksi) {

    // ---------------------------------------------------------
    // 1. PROSES TAMBAH USER
    // ---------------------------------------------------------
    // AKSI tambah : admin menambah user dari form tambahUser (POST).
    case 'tambah':
        // Hanya proses jika dikirim lewat POST.
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Ambil data form; trim() membuang spasi ujung.
            $nis_nip      = trim($_POST['nis_nip'] ?? '');
            $nama_lengkap = trim($_POST['nama_lengkap'] ?? '');
            $kelas        = trim($_POST['kelas'] ?? '');
            $username     = trim($_POST['username'] ?? '');
            $password     = $_POST['password'] ?? '';
            // Role dari form; default 'siswa'.
            $role         = $_POST['role'] ?? 'siswa';

            // VALIDASI REGEX NAMA
            // VALIDASI nama: hanya huruf, spasi, petik dan strip. preg_match = cocokkan dengan pola regex.
            if (!preg_match("/^[a-zA-Z\s'-]+$/", $nama_lengkap)) {
                $_SESSION['error'] = "Nama lengkap hanya boleh berisi huruf dan spasi (tidak boleh ada angka atau simbol)!";
                // CATATAN BELAJAR: rute /admin/user/tambahUser tidak ada di .htaccess (rute yang terdaftar: /admin/tambahUser),
                // sehingga redirect error ini kemungkinan besar menghasilkan halaman 404. Hal yang sama berlaku untuk /admin/user/editUser.
                header("Location: /Aplikasi_Peminjaman_Buku/admin/user/tambahUser");
                exit;
            }

            // VALIDASI REGEX NIS (3 - 10 DIGIT)
            // VALIDASI NIS/NIP. CATATAN BELAJAR: pola \d{10} = TEPAT 10 digit, sedangkan pesan error menyebut 3 sampai 10 digit
            // (registrasi siswa di c_login.php memakai \d{3,10}). Ada ketidaksesuaian antara pola dan pesan.
            if (!preg_match("/^\d{10}$/", $nis_nip)) {
                $_SESSION['error'] = "NIS/NIP harus berupa angka murni dengan panjang 3 hingga 10 digit!";
                header("Location: /Aplikasi_Peminjaman_Buku/admin/user/tambahUser");
                exit;
            }

            // TRY-CATCH UNTUK MENCEGAH FATAL ERROR DUPLICATE ENTRY
            try {
                // Simpan user baru lewat controller -> model.
                $hasil = $controller->tambah($nis_nip, $nama_lengkap, $kelas, $username, $password, $role);

                if ($hasil) {
                    $_SESSION['success'] = "User berhasil ditambahkan!";
                    header("Location: /Aplikasi_Peminjaman_Buku/admin/daftarUser");
                    exit;
                } else {
                    $_SESSION['error'] = "Gagal menambahkan user baru.";
                    header("Location: /Aplikasi_Peminjaman_Buku/admin/user/tambahUser");
                    exit;
                }
            // Tangkap error MySQL.
            } catch (mysqli_sql_exception $e) {
                // Tangkap Error Code 1062 (Duplicate Entry pada MySQL)
                // Kode 1062 = duplicate entry (nis_nip atau username bersifat UNIQUE).
                if ($e->getCode() === 1062) {
                    $_SESSION['error'] = "NIS/NIP atau Username tersebut sudah terdaftar!";
                } else {
                    $_SESSION['error'] = "Terjadi kesalahan sistem: " . $e->getMessage();
                }
                header("Location: /Aplikasi_Peminjaman_Buku/admin/user/tambahUser");
                exit;
            }
        }
        header("Location: /Aplikasi_Peminjaman_Buku/admin/daftarUser");
        exit;

        // ---------------------------------------------------------
        // 2. PROSES UPDATE USER
        // ---------------------------------------------------------
    // AKSI update : admin menyimpan perubahan data user (POST dari form editUser).
    case 'update':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // id user yang diubah.
            $id_user      = (int) ($_POST['id_user'] ?? 0);
            $nis_nip      = trim($_POST['nis_nip'] ?? '');
            $nama_lengkap = trim($_POST['nama_lengkap'] ?? '');
            $kelas        = trim($_POST['kelas'] ?? '');
            $username     = trim($_POST['username'] ?? '');
            $role         = $_POST['role'] ?? 'siswa';

            // VALIDASI REGEX NAMA
            if (!preg_match("/^[a-zA-Z\s'-]+$/", $nama_lengkap)) {
                $_SESSION['error'] = "Nama lengkap hanya boleh berisi huruf dan spasi (tidak boleh ada angka atau simbol)!";
                header("Location: /Aplikasi_Peminjaman_Buku/admin/user/editUser?id_user=" . $id_user);
                exit;
            }

            // VALIDASI REGEX NIS (3 - 10 DIGIT)
            if (!preg_match("/^\d{10}$/", $nis_nip)) {
                $_SESSION['error'] = "NIS/NIP harus berupa angka murni dengan panjang 3 hingga 10 digit!";
                header("Location: /Aplikasi_Peminjaman_Buku/admin/user/editUser?id_user=" . $id_user);
                exit;
            }

            // Simpan perubahan lewat controller -> model.
            $hasil = $controller->update($id_user, $nis_nip, $nama_lengkap, $kelas, $username, $role);

            if ($hasil) {
                $_SESSION['success'] = "Data user berhasil diperbarui!";
                header("Location: /Aplikasi_Peminjaman_Buku/admin/daftarUser");
                exit;
            } else {
                $_SESSION['error'] = "Gagal memperbarui data user.";
                header("Location: /Aplikasi_Peminjaman_Buku/admin/user/editUser?id_user=" . $id_user);
                exit;
            }
        }
        header("Location: /Aplikasi_Peminjaman_Buku/admin/daftarUser");
        exit;

        // ---------------------------------------------------------
        // 3. PROSES UPDATE PASSWORD
        // ---------------------------------------------------------
    // AKSI updatePassword : melayani DUA situasi: (1) admin mengganti password user lain, (2) siswa mengganti password sendiri.
    case 'updatePassword':
        // Pengecekan login ulang.
        if (!isset($_SESSION['id_user'])) {
            header("Location: /Aplikasi_Peminjaman_Buku/login");
            exit;
        }

        // SITUASI 1 (admin mengganti password user lain): ditandai adanya POST id_user.
        // CATATAN KEAMANAN: blok ini tidak mengecek role admin; siapa pun yang login dan mengirim POST id_user
        // bisa mengganti password user itu. Sebaiknya ditambah pengecekan $_SESSION['role'] === 'admin'.
        if (isset($_POST['id_user']) && !empty($_POST['id_user'])) {
            // Ganti password user target (di-hash di fungsi updatePassword).
            $controller->updatePassword((int) $_POST['id_user'], $_POST['password']);
            $_SESSION['success'] = "Password user berhasil diperbarui!";
            header("Location: /Aplikasi_Peminjaman_Buku/admin/daftarUser");
            exit;
        }

        // SITUASI 2 (siswa ganti password sendiri): id diambil dari SESSION, bukan dari form, jadi hanya bisa mengubah akunnya sendiri.
        $id_user       = (int) $_SESSION['id_user'];
        // Password lama, baru, dan konfirmasi dari form profil.
        $password_lama = $_POST['password_lama'] ?? '';
        $password_baru = $_POST['password_baru'] ?? '';
        $konfirmasi    = $_POST['konfirmasi_password_baru'] ?? '';

        // Semua kolom wajib diisi.
        if (empty($password_lama) || empty($password_baru) || empty($konfirmasi)) {
            $_SESSION['error'] = "Semua bidang password wajib diisi!";
            header("Location: /Aplikasi_Peminjaman_Buku/siswa/profil");
            exit;
        }

        // Password baru dan konfirmasi harus sama persis.
        if ($password_baru !== $konfirmasi) {
            $_SESSION['error'] = "Konfirmasi password baru tidak cocok!";
            header("Location: /Aplikasi_Peminjaman_Buku/siswa/profil");
            exit;
        }

        // Ambil data user (butuh hash password saat ini).
        $userCurrent = $controller->detail($id_user);
        // Verifikasi PASSWORD LAMA dengan password_verify() sebelum boleh mengganti.
        if (!$userCurrent || !password_verify($password_lama, $userCurrent['password'])) {
            $_SESSION['error'] = "Password saat ini (lama) tidak sesuai!";
            header("Location: /Aplikasi_Peminjaman_Buku/siswa/profil");
            exit;
        }

        // Simpan password baru (di-hash).
        $hasil = $controller->updatePassword($id_user, $password_baru);
        if ($hasil) {
            $_SESSION['success'] = "Password Anda berhasil diperbarui!";
        } else {
            $_SESSION['error'] = "Gagal memperbarui password. Silakan coba lagi.";
        }

        header("Location: /Aplikasi_Peminjaman_Buku/siswa/profil");
        exit;

        // ---------------------------------------------------------
        // 4. PROSES HAPUS USER
        // ---------------------------------------------------------
    // AKSI hapus : menghapus user (id dari URL ?id_user=..).
    case 'hapus':
        // Ambil id dari URL.
        $id_user = (int) ($_GET['id_user'] ?? 0);
        if ($id_user > 0) {
            // Hapus; bila gagal karena riwayat peminjaman, pesan error sudah diisi di dalam fungsi hapus().
            $statusHapus = $controller->hapus($id_user);
            if ($statusHapus) {
                $_SESSION['success'] = "User berhasil dihapus.";
            }
        }
        header("Location: /Aplikasi_Peminjaman_Buku/admin/daftarUser");
        exit;

    // Aksi tidak dikenal -> kembali ke daftar user.
    default:
        header("Location: /Aplikasi_Peminjaman_Buku/admin/daftarUser");
        exit;
}
