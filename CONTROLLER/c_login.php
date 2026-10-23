<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . "/../MODEL/m_koneksi.php";

class C_Login
{
    private $koneksi;

    public function __construct()
    {
        $db = new Koneksi();
        $this->koneksi =$db->getKoneksi();
    }

    // 1. FITUR REGISTRASI SISWA
    public function register()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: /Aplikasi_Peminjaman_Buku/register");
            exit;
        }

        $nis_nip = trim($_POST['nis_nip'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $nama_lengkap = trim($_POST['nama_lengkap'] ?? '');
        $kelas = trim($_POST['kelas'] ?? '');
        $password =$_POST['password'] ?? '';
        $konfirmasi_password =$_POST['konfirmasi_password'] ?? '';

        // Validasi Kelengkapan Input (Menggunakan kata 'or' menggantikan simbol)
        if (empty($nis_nip) or empty($username) or empty($nama_lengkap) or empty($kelas) or empty($password)) {$_SESSION['error'] = "Semua bidang form wajib diisi!";
            header("Location: /Aplikasi_Peminjaman_Buku/register");
            exit;
        }

        // Validasi Kesesuaian Password
        if ($password !== $konfirmasi_password) {$_SESSION['error'] = "Konfirmasi password tidak cocok!";
            header("Location: /Aplikasi_Peminjaman_Buku/register");
            exit;
        }

        // Validasi Regex Nama (Hanya huruf, spasi, petik, dan strip)
        if (!preg_match("/^[a-zA-Z\s'-]+$/", $nama_lengkap)) {$_SESSION['error'] = "Nama lengkap hanya boleh berisi huruf dan spasi (tidak boleh ada angka atau simbol)!";
            header("Location: /Aplikasi_Peminjaman_Buku/register");
            exit;
        }

        // Validasi Regex NIS (Hanya angka, panjang 3 hingga 10 digit)
        if (!preg_match("/^\d{3,10}$/", $nis_nip)) {$_SESSION['error'] = "NIS harus berupa angka murni dengan panjang 3 hingga 10 digit!";
            header("Location: /Aplikasi_Peminjaman_Buku/register");
            exit;
        }

        // Cek Apakah NIS/NIP Atau Username Sudah Terdaftar
        $stmtCek =$this->koneksi->prepare("SELECT id_user FROM users WHERE `nis_nip` = ? OR `username` = ?");
        $stmtCek->bind_param("ss", $nis_nip, $username);$stmtCek->execute();
        $resCek =$stmtCek->get_result();

        if ($resCek->num_rows > 0) {
            $stmtCek->close();$_SESSION['error'] = "NIS/NIP atau Username sudah terdaftar dalam sistem!";
            header("Location: /Aplikasi_Peminjaman_Buku/register");
            exit;
        }
        $stmtCek->close();

        // Enkripsi Password & Simpan ke Tabel users
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);$role = 'siswa';

        $stmtInsert =$this->koneksi->prepare("INSERT INTO users (`nis_nip`, `username`, nama_lengkap, kelas, password, role) VALUES (?, ?, ?, ?, ?, ?)");
        $stmtInsert->bind_param("ssssss", $nis_nip,$username, $nama_lengkap,$kelas, $hashed_password,$role);

        if ($stmtInsert->execute()) {
            $stmtInsert->close();$_SESSION['success'] = "Pendaftaran berhasil! Silakan login menggunakan NIS/NIP Anda.";
            header("Location: /Aplikasi_Peminjaman_Buku/login");
            exit;
        } else {
            $stmtInsert->close();$_SESSION['error'] = "Gagal mendaftarkan akun. Silakan coba lagi.";
            header("Location: /Aplikasi_Peminjaman_Buku/register");
            exit;
        }
    }

    // 2. FITUR LOGIN USER (ADMIN & SISWA)
    public function login()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: /Aplikasi_Peminjaman_Buku/login");
            exit;
        }

        $nis_nip = trim($_POST['nis_nip'] ?? '');
        $password =$_POST['password'] ?? '';

        if (empty($nis_nip) or empty($password)) {$_SESSION['error'] = "NIS/NIP dan Password wajib diisi!";
            header("Location: /Aplikasi_Peminjaman_Buku/login");
            exit;
        }

        // Login bisa menggunakan NIS/NIP atau Username
        $stmt =$this->koneksi->prepare("SELECT id_user, `nis_nip`, username, nama_lengkap, password, role FROM users WHERE `nis_nip` = ? OR `username` = ?");
        $stmt->bind_param("ss", $nis_nip,$nis_nip);
        $stmt->execute();$user = $stmt->get_result()->fetch_assoc();$stmt->close();

        if ($user) {$passValid = password_verify($password,$user['password']) or (md5($password) ===$user['password']) or ($password ===$user['password']);

            if ($passValid) {
                $_SESSION['id_user'] =$user['id_user'];
                $_SESSION['nis_nip'] =$user['nis_nip'];
                $_SESSION['username'] =$user['username'];
                $_SESSION['nama_lengkap'] =$user['nama_lengkap'];
                $_SESSION['role'] = strtolower($user['role']);

                // CLEAN URL: Arahkan ke rute bersih sesuai role
                if ($_SESSION['role'] === 'admin') {
                    header("Location: /Aplikasi_Peminjaman_Buku/admin/dashboardAdmin");
                } else {
                    header("Location: /Aplikasi_Peminjaman_Buku/siswa/dashboardSiswa");
                }
                exit;
            }
        }

        $_SESSION['error'] = "NIS/NIP/Username atau Password salah!";
        header("Location: /Aplikasi_Peminjaman_Buku/login");
        exit;
    }

    // 3. FITUR LOGOUT
    public function logout()
    {
        session_unset();
        session_destroy();
        session_start();
        $_SESSION['success'] = "Anda telah berhasil keluar (logout).";
        header("Location: /Aplikasi_Peminjaman_Buku/login");
        exit;
    }
}

// =============================================================
// HANDLER REQUEST / ROUTER
// =============================================================
$controller = new C_Login();
$aksi =$_GET['aksi'] ?? '';

switch ($aksi) {
    case 'register':
        $controller->register();
        break;
    case 'login':
        $controller->login();
        break;
    case 'logout':
        $controller->logout();
        break;
    default:
        header("Location: /Aplikasi_Peminjaman_Buku/login");
        exit;
}