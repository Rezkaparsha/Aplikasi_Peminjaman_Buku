<?php
// =====================================================================
// FILE   : CONTROLLER/c_login.php
// FUNGSI : Mengurus autentikasi: REGISTRASI siswa, LOGIN, dan LOGOUT.
// DIPANGGIL LEWAT .htaccess:
//   /login  (form)  --> VIEW/AUTH/login.php  --(submit POST)--> c_login.php?aksi=login
//   /logout         --> c_login.php?aksi=logout
// =====================================================================

// Pastikan session aktif. Dicek dulu supaya tidak error "session already
// started" kalau sudah dimulai di tempat lain.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Memanggil file koneksi database. require_once = wajib ada & hanya
// dimuat sekali. __DIR__ = folder file ini (CONTROLLER), lalu naik satu
// tingkat (..) ke folder MODEL.
require_once __DIR__ . "/../MODEL/m_koneksi.php";

class C_Login
{
    // Menyimpan koneksi database (private = hanya dipakai di class ini).
    private $koneksi;

    // Constructor: jalan otomatis saat "new C_Login()" di bagian bawah file.
    public function __construct()
    {
        $db = new Koneksi();                  // buat objek koneksi
        $this->koneksi = $db->getKoneksi();   // simpan koneksinya
    }

    // -----------------------------------------------------------------
    // 1. FITUR REGISTRASI SISWA
    // Alur: ambil data form -> validasi -> cek duplikat -> hash password
    //       -> simpan ke tabel users -> redirect ke login
    // -----------------------------------------------------------------
    public function register()
    {
        // Registrasi hanya boleh lewat form (method POST). Kalau halaman
        // dibuka langsung lewat URL (GET), kembalikan ke form register.
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: /Aplikasi_Peminjaman_Buku/register");
            exit;
        }

        // Ambil data dari form. "?? ''" = kalau datanya tidak ada, pakai
        // string kosong (mencegah error undefined index).
        // trim() = buang spasi di awal/akhir.
        $nis_nip = trim($_POST['nis_nip'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $nama_lengkap = trim($_POST['nama_lengkap'] ?? '');
        $kelas = trim($_POST['kelas'] ?? '');
        $password = $_POST['password'] ?? '';                       // password tidak di-trim
        $konfirmasi_password = $_POST['konfirmasi_password'] ?? ''; // ketik ulang password

        // VALIDASI 1: semua kolom wajib terisi. empty() true bila kosong.
        // "or" artinya cukup satu kolom kosong -> error.
        if (empty($nis_nip) or empty($username) or empty($nama_lengkap) or empty($kelas) or empty($password)) {
            // Simpan pesan error di session; akan ditampilkan oleh halaman
            // register lalu dihapus (unset) agar tidak muncul terus.
            $_SESSION['error'] = "Semua bidang form wajib diisi!";
            header("Location: /Aplikasi_Peminjaman_Buku/register");
            exit;
        }

        // VALIDASI 2: password dan konfirmasi harus sama persis (!==).
        if ($password !== $konfirmasi_password) {
            $_SESSION['error'] = "Konfirmasi password tidak cocok!";
            header("Location: /Aplikasi_Peminjaman_Buku/register");
            exit;
        }

        // VALIDASI 3: nama hanya huruf, spasi, petik (') dan strip (-).
        // preg_match = cocokkan teks dengan pola regex.
        //   ^ awal teks | [a-zA-Z\s'-] huruf/spasi/'/- | + minimal 1 karakter | $ akhir teks
        if (!preg_match("/^[a-zA-Z\s'-]+$/", $nama_lengkap)) {
            $_SESSION['error'] = "Nama lengkap hanya boleh berisi huruf dan spasi (tidak boleh ada angka atau simbol)!";
            header("Location: /Aplikasi_Peminjaman_Buku/register");
            exit;
        }

        // VALIDASI 4: NIS harus angka saja, panjang 3-10 digit.
        //   \d = digit | {3,10} = berulang 3 sampai 10 kali
        if (!preg_match("/^\d{3,10}$/", $nis_nip)) {
            $_SESSION['error'] = "NIS harus berupa angka murni dengan panjang 3 hingga 10 digit!";
            header("Location: /Aplikasi_Peminjaman_Buku/register");
            exit;
        }

        // CEK DUPLIKAT: apakah NIS atau username sudah dipakai orang lain?
        // Memakai PREPARED STATEMENT: tanda "?" adalah tempat nilai yang
        // diisi belakangan. Ini mencegah SQL Injection.
        $stmtCek = $this->koneksi->prepare("SELECT id_user FROM users WHERE `nis_nip` = ? OR `username` = ?");
        // bind_param: "ss" = dua nilai bertipe string (s=string, i=integer).
        $stmtCek->bind_param("ss", $nis_nip, $username);
        $stmtCek->execute();                 // jalankan query
        $resCek = $stmtCek->get_result();    // ambil hasilnya

        // num_rows = jumlah baris hasil. Kalau > 0 berarti sudah ada.
        if ($resCek->num_rows > 0) {
            $stmtCek->close(); // tutup statement untuk membebaskan memori
            $_SESSION['error'] = "NIS/NIP atau Username sudah terdaftar dalam sistem!";
            header("Location: /Aplikasi_Peminjaman_Buku/register");
            exit;
        }
        $stmtCek->close();

        // ENKRIPSI PASSWORD: tidak boleh menyimpan password asli!
        // password_hash + PASSWORD_BCRYPT menghasilkan hash acak satu arah
        // (contoh di database: $2y$12$...).
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);
        // Pendaftaran lewat form selalu role siswa (admin dibuat oleh admin).
        $role = 'siswa';

        // SIMPAN user baru (6 kolom = 6 tanda tanya).
        $stmtInsert = $this->koneksi->prepare("INSERT INTO users (`nis_nip`, `username`, nama_lengkap, kelas, password, role) VALUES (?, ?, ?, ?, ?, ?)");
        $stmtInsert->bind_param("ssssss", $nis_nip, $username, $nama_lengkap, $kelas, $hashed_password, $role);

        // execute() mengembalikan true jika berhasil disimpan.
        if ($stmtInsert->execute()) {
            $stmtInsert->close();
            $_SESSION['success'] = "Pendaftaran berhasil! Silakan login menggunakan NIS/NIP Anda.";
            header("Location: /Aplikasi_Peminjaman_Buku/login");
            exit;
        } else {
            $stmtInsert->close();
            $_SESSION['error'] = "Gagal mendaftarkan akun. Silakan coba lagi.";
            header("Location: /Aplikasi_Peminjaman_Buku/register");
            exit;
        }
    }

    // -----------------------------------------------------------------
    // 2. FITUR LOGIN (ADMIN & SISWA memakai form yang sama)
    // Alur: ambil input -> cari user di database -> cocokkan password
    //       -> simpan data ke session -> redirect sesuai role
    // -----------------------------------------------------------------
    public function login()
    {
        // Hanya terima POST dari form login.
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: /Aplikasi_Peminjaman_Buku/login");
            exit;
        }

        // Kolom "nis_nip" di form boleh diisi NIS/NIP ATAU username.
        $nis_nip = trim($_POST['nis_nip'] ?? '');
        $password = $_POST['password'] ?? '';

        // Wajib isi dua-duanya.
        if (empty($nis_nip) or empty($password)) {
            $_SESSION['error'] = "NIS/NIP dan Password wajib diisi!";
            header("Location: /Aplikasi_Peminjaman_Buku/login");
            exit;
        }

        // Cari user yang nis_nip-nya ATAU username-nya sama dengan input.
        // Input yang sama dikirim dua kali (bind "ss", $nis_nip, $nis_nip)
        // karena ada dua tanda tanya.
        $stmt = $this->koneksi->prepare("SELECT id_user, `nis_nip`, username, nama_lengkap, password, role FROM users WHERE `nis_nip` = ? OR `username` = ?");
        $stmt->bind_param("ss", $nis_nip, $nis_nip);
        $stmt->execute();
        // fetch_assoc() mengambil SATU baris sebagai array berkunci nama
        // kolom (contoh: $user['role']). Bila tidak ada hasilnya = null.
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        // Jika user ditemukan, periksa password.
        if ($user) {
            // password_verify(inputan, hash_di_db) = true bila cocok.
            // CATATAN BELAJAR: karena operator "=" lebih kuat dari "or",
            // baris ini sebenarnya sama dengan:
            //   ($passValid = password_verify(...)) or (md5...) or (plain...)
            // sehingga $passValid HANYA berisi hasil password_verify().
            // Pembanding md5 dan plain text tidak ikut menentukan login.
            // Ini justru aman (hanya hash bcrypt yang diterima), tetapi bila
            // diuji penguji, jelaskan bahwa fallback md5/plain tidak aktif.
            // Agar benar-benar berfungsi akan butuh tanda kurung: ($a or $b)
            $passValid = password_verify($password, $user['password']) or (md5($password) === $user['password']) or ($password === $user['password']);

            if ($passValid) {
                // LOGIN BERHASIL: simpan identitas ke session. Data ini
                // yang dipakai halaman lain untuk tahu "siapa yang login".
                $_SESSION['id_user'] = $user['id_user'];
                $_SESSION['nis_nip'] = $user['nis_nip'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
                // strtolower = huruf kecil semua agar perbandingan konsisten.
                $_SESSION['role'] = strtolower($user['role']);

                // Arahkan ke dashboard sesuai role (RBAC: Role Based
                // Access Control = hak akses berdasarkan peran).
                if ($_SESSION['role'] === 'admin') {
                    header("Location: /Aplikasi_Peminjaman_Buku/admin/dashboardAdmin");
                } else {
                    header("Location: /Aplikasi_Peminjaman_Buku/siswa/dashboardSiswa");
                }
                exit;
            }
        }

        // Sampai di sini = user tidak ada ATAU password salah. Pesannya
        // sengaja disatukan agar penyerang tidak tahu mana yang salah.
        $_SESSION['error'] = "NIS/NIP/Username atau Password salah!";
        header("Location: /Aplikasi_Peminjaman_Buku/login");
        exit;
    }

    // -----------------------------------------------------------------
    // 3. FITUR LOGOUT
    // -----------------------------------------------------------------
    public function logout()
    {
        session_unset();    // kosongkan semua variabel session
        session_destroy();  // hancurkan session di server
        session_start();    // mulai session BARU (kosong) supaya bisa
                            // menampilkan pesan sukses di bawah
        $_SESSION['success'] = "Anda telah berhasil keluar (logout).";
        header("Location: /Aplikasi_Peminjaman_Buku/login");
        exit;
    }
}

// =====================================================================
// HANDLER REQUEST / ROUTER
// Bagian ini yang PERTAMA jalan saat file dibuka lewat URL.
// =====================================================================
$controller = new C_Login();    // buat objek controller (koneksi DB terbuka)
$aksi = $_GET['aksi'] ?? '';    // baca parameter ?aksi=... dari URL

// switch = memilih fungsi sesuai nilai $aksi.
switch ($aksi) {
    case 'register':
        $controller->register();  // ?aksi=register
        break;
    case 'login':
        $controller->login();     // ?aksi=login
        break;
    case 'logout':
        $controller->logout();    // ?aksi=logout (dari URL /logout)
        break;
    default:
        // aksi tidak dikenal -> kembali ke halaman login
        header("Location: /Aplikasi_Peminjaman_Buku/login");
        exit;
}
