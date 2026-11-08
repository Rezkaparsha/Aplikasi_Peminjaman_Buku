<?php
// =====================================================================
// FILE   : MODEL/m_users.php
// FUNGSI : MODEL untuk tabel `users` (admin & siswa). Model = satu-satunya
//          tempat yang bicara langsung ke database. Controller tinggal
//          memanggil fungsi-fungsi di sini (getAll, insert, update, dst).
// =====================================================================

// Panggil file koneksi (satu folder dengan file ini, jadi cukup nama file).
require_once 'm_koneksi.php';

class Users
{
    private $koneksi; // koneksi database

    // PROPERTI = penampung data satu user. Controller mengisi nilainya
    // (mis. $user->nama_lengkap = "Budi") lalu memanggil insert()/update().
    // Nama properti sama dengan nama kolom di tabel users.
    public $id_user;
    public $nis_nip;
    public $nama_lengkap;
    public $kelas;
    public $username;
    public $password;
    public $role;

    // Constructor: buka koneksi database begitu objek Users dibuat.
    public function __construct()
    {
        $db = new Koneksi();
        $this->koneksi = $db->getKoneksi();
    }

    // -----------------------------------------------------------------
    // READ: ambil SEMUA user, yang terbaru (id terbesar) di paling atas.
    // Dipakai halaman Daftar User admin.
    // -----------------------------------------------------------------
    public function getAll()
    {
        // ORDER BY id_user DESC = urut dari id terbesar ke terkecil.
        $query = "SELECT * FROM users ORDER BY id_user DESC";

        // query() dipakai langsung karena TIDAK ada input dari pengguna
        // (jadi aman tanpa prepared statement).
        $result = $this->koneksi->query($query);
        $data = []; // wadah array kosong

        // fetch_assoc() dipanggil berulang; tiap panggilan mengambil satu
        // baris. Loop berhenti saat baris habis (hasilnya null/false).
        while ($row = $result->fetch_assoc()) {
            $data[] = $row; // tambahkan baris ke array $data
        }

        return $data; // kembalikan array berisi semua user
    }

    // -----------------------------------------------------------------
    // SEARCH: cari user berdasarkan kata kunci (fitur pencarian admin).
    // -----------------------------------------------------------------
    public function search($keyword)
    {
        // % = wildcard LIKE. "%abc%" artinya "mengandung abc" di posisi mana pun.
        $keyword = "%{$keyword}%";
        // Cari di tiga kolom sekaligus: NIS/NIP, nama lengkap, username.
        $stmt = $this->koneksi->prepare(
            "SELECT * FROM users WHERE nis_nip LIKE ? OR nama_lengkap LIKE ? OR username LIKE ? ORDER BY id_user DESC"
        );

        // Tiga tanda tanya -> tiga string dengan nilai sama.
        $stmt->bind_param("sss", $keyword, $keyword, $keyword);
        $stmt->execute();
        $result = $stmt->get_result();

        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        return $data;
    }

    // -----------------------------------------------------------------
    // READ satu user berdasarkan id_user (untuk form Edit User).
    // -----------------------------------------------------------------
    public function getById($id_user)
    {
        $stmt = $this->koneksi->prepare(
            "SELECT * FROM users WHERE id_user = ?"
        );

        $stmt->bind_param("i", $id_user); // "i" = integer
        $stmt->execute();

        // Langsung kembalikan SATU baris (atau null bila tidak ada).
        return $stmt->get_result()->fetch_assoc();
    }

    // READ satu user berdasarkan username (cek apakah username sudah dipakai).
    public function getByUsername($username)
    {
        $stmt = $this->koneksi->prepare(
            "SELECT * FROM users WHERE username = ?"
        );

        $stmt->bind_param("s", $username); // "s" = string
        $stmt->execute();

        return $stmt->get_result()->fetch_assoc();
    }

    // READ satu user berdasarkan NIS/NIP (cek duplikat NIS/NIP).
    public function getByNis_nip($nis_nip)
    {
        $stmt = $this->koneksi->prepare(
            "SELECT * FROM users WHERE nis_nip = ?"
        );

        $stmt->bind_param("s", $nis_nip);
        $stmt->execute();

        return $stmt->get_result()->fetch_assoc();
    }

    // -----------------------------------------------------------------
    // CREATE: tambah user baru. Nilainya diambil dari properti objek
    // ($this->...) yang sudah diisi controller.
    // Mengembalikan true jika berhasil, false jika gagal.
    // -----------------------------------------------------------------
    public function insert()
    {
        $stmt = $this->koneksi->prepare(
            "INSERT INTO users
            (nis_nip, nama_lengkap, kelas, username, password, role)
            VALUES (?, ?, ?, ?, ?, ?)"
        );

        // 6 kolom, semuanya string -> "ssssss".
        $stmt->bind_param(
            "ssssss",
            $this->nis_nip,
            $this->nama_lengkap,
            $this->kelas,
            $this->username,
            $this->password,  // sudah berupa hash (di-hash oleh controller)
            $this->role
        );

        return $stmt->execute();
    }

    // -----------------------------------------------------------------
    // UPDATE: ubah data user. Password TIDAK ikut diubah di sini
    // (ada fungsi khusus updatePassword di bawah).
    // -----------------------------------------------------------------
    public function update()
    {
        $stmt = $this->koneksi->prepare(
            "UPDATE users SET
                nis_nip = ?,
                nama_lengkap = ?,
                kelas = ?,
                username = ?,
                role = ?
             WHERE id_user = ?"   // WHERE wajib! tanpa ini SEMUA user ikut berubah
        );

        // 5 string + 1 integer (id_user) -> "sssssi".
        $stmt->bind_param(
            "sssssi",
            $this->nis_nip,
            $this->nama_lengkap,
            $this->kelas,
            $this->username,
            $this->role,
            $this->id_user
        );

        return $stmt->execute();
    }

    // -----------------------------------------------------------------
    // UPDATE PASSWORD: hanya mengganti kolom password satu user.
    // -----------------------------------------------------------------
    public function updatePassword()
    {
        $stmt = $this->koneksi->prepare(
            "UPDATE users SET password = ?
             WHERE id_user = ?"
        );

        // "si" = password (string) + id_user (integer).
        $stmt->bind_param(
            "si",
            $this->password,
            $this->id_user
        );

        return $stmt->execute();
    }

    // -----------------------------------------------------------------
    // DELETE: hapus user berdasarkan id.
    // CATATAN: tabel peminjaman punya foreign key ke users dengan
    // ON DELETE RESTRICT, jadi user yang sudah pernah meminjam TIDAK
    // bisa dihapus (execute() akan gagal / false).
    // -----------------------------------------------------------------
    public function delete($id_user)
    {
        $stmt = $this->koneksi->prepare(
            "DELETE FROM users WHERE id_user = ?"
        );

        $stmt->bind_param("i", $id_user);

        return $stmt->execute();
    }
}
