<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['id_user'])) {
    header("Location: /Aplikasi_Peminjaman_Buku/login");
    exit;
}

// Catat jejak otomatis berdasarkan role yang sedang aktif
if ($_SESSION['role'] === 'admin') {
    $_SESSION['last_page_admin'] = $_SERVER['REQUEST_URI'];
} else {
    $_SESSION['last_page_siswa'] = $_SERVER['REQUEST_URI'];
}

require_once __DIR__ . "/../MODEL/m_users.php";

class UserController
{
    private $user;

    public function __construct()
    {
        $this->user = new Users();
    }

    // TAMPILKAN SEMUA USER
    public function index()
    {
        return $this->user->getAll();
    }

    // DETAIL USER
    public function detail($id_user)
    {
        return $this->user->getById($id_user);
    }

    // TAMBAH USER
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
    public function updatePassword($id_user, $password)
    {
        $this->user->id_user  = $id_user;
        $this->user->password = password_hash($password, PASSWORD_DEFAULT);

        return $this->user->updatePassword();
    }

    // HAPUS USER DENGAN TANGKAPAN ERROR (TRY-CATCH)
    public function hapus($id_user)
    {
        try {
            return $this->user->delete($id_user);
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
$controller = new UserController();
$aksi = $_GET['aksi'] ?? '';

switch ($aksi) {

    // ---------------------------------------------------------
    // 1. PROSES TAMBAH USER
    // ---------------------------------------------------------
    case 'tambah':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nis_nip      = trim($_POST['nis_nip'] ?? '');
            $nama_lengkap = trim($_POST['nama_lengkap'] ?? '');
            $kelas        = trim($_POST['kelas'] ?? '');
            $username     = trim($_POST['username'] ?? '');
            $password     = $_POST['password'] ?? '';
            $role         = $_POST['role'] ?? 'siswa';

            // VALIDASI REGEX NAMA
            if (!preg_match("/^[a-zA-Z\s'-]+$/", $nama_lengkap)) {
                $_SESSION['error'] = "Nama lengkap hanya boleh berisi huruf dan spasi (tidak boleh ada angka atau simbol)!";
                header("Location: /Aplikasi_Peminjaman_Buku/admin/user/tambahUser");
                exit;
            }

            // VALIDASI REGEX NIS (3 - 10 DIGIT)
            if (!preg_match("/^\d{10}$/", $nis_nip)) {
                $_SESSION['error'] = "NIS/NIP harus berupa angka murni dengan panjang 3 hingga 10 digit!";
                header("Location: /Aplikasi_Peminjaman_Buku/admin/user/tambahUser");
                exit;
            }

            // TRY-CATCH UNTUK MENCEGAH FATAL ERROR DUPLICATE ENTRY
            try {
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
            } catch (mysqli_sql_exception $e) {
                // Tangkap Error Code 1062 (Duplicate Entry pada MySQL)
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
    case 'update':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
    case 'updatePassword':
        if (!isset($_SESSION['id_user'])) {
            header("Location: /Aplikasi_Peminjaman_Buku/login");
            exit;
        }

        if (isset($_POST['id_user']) && !empty($_POST['id_user'])) {
            $controller->updatePassword((int) $_POST['id_user'], $_POST['password']);
            $_SESSION['success'] = "Password user berhasil diperbarui!";
            header("Location: /Aplikasi_Peminjaman_Buku/admin/daftarUser");
            exit;
        }

        $id_user       = (int) $_SESSION['id_user'];
        $password_lama = $_POST['password_lama'] ?? '';
        $password_baru = $_POST['password_baru'] ?? '';
        $konfirmasi    = $_POST['konfirmasi_password_baru'] ?? '';

        if (empty($password_lama) || empty($password_baru) || empty($konfirmasi)) {
            $_SESSION['error'] = "Semua bidang password wajib diisi!";
            header("Location: /Aplikasi_Peminjaman_Buku/siswa/profil");
            exit;
        }

        if ($password_baru !== $konfirmasi) {
            $_SESSION['error'] = "Konfirmasi password baru tidak cocok!";
            header("Location: /Aplikasi_Peminjaman_Buku/siswa/profil");
            exit;
        }

        $userCurrent = $controller->detail($id_user);
        if (!$userCurrent || !password_verify($password_lama, $userCurrent['password'])) {
            $_SESSION['error'] = "Password saat ini (lama) tidak sesuai!";
            header("Location: /Aplikasi_Peminjaman_Buku/siswa/profil");
            exit;
        }

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
    case 'hapus':
        $id_user = (int) ($_GET['id_user'] ?? 0);
        if ($id_user > 0) {
            $statusHapus = $controller->hapus($id_user);
            if ($statusHapus) {
                $_SESSION['success'] = "User berhasil dihapus.";
            }
        }
        header("Location: /Aplikasi_Peminjaman_Buku/admin/daftarUser");
        exit;

    default:
        header("Location: /Aplikasi_Peminjaman_Buku/admin/daftarUser");
        exit;
}
