<?php

require_once 'm_koneksi.php';

/**
 * Model tabel users (admin dan siswa).
 */
class Users
{
    private $koneksi;

    public $id_user;
    public $nis_nip;
    public $nama_lengkap;
    public $kelas;
    public $username;
    public $password;
    public $role;

    /**
     * Membuka koneksi database.
     */
    public function __construct()
    {
        $db = new Koneksi();
        $this->koneksi = $db->getKoneksi();
    }

    // MENAMPILKAN SEMUA USER
    /**
     * Mengambil semua user (terbaru di atas).
     *
     * @return array Daftar user
     */
    public function getAll()
    {
        $query = "SELECT * FROM users ORDER BY id_user DESC";

        $result = $this->koneksi->query($query);
        $data = [];

        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }

        return $data;
    }

    // MENCARI USER BERDASARKAN KEYWORD (PENCARIAN)
    /**
     * Mencari user berdasarkan NIS/NIP, nama, atau username.
     *
     * @param string $keyword Kata kunci pencarian
     * @return array Daftar user yang sesuai
     */
    public function search($keyword)
    {
        $keyword = "%{$keyword}%";
        // Mencari berdasarkan NIS/NIP, Nama Lengkap, atau Username
        $stmt = $this->koneksi->prepare(
            "SELECT * FROM users WHERE nis_nip LIKE ? OR nama_lengkap LIKE ? OR username LIKE ? ORDER BY id_user DESC"
        );

        $stmt->bind_param("sss", $keyword, $keyword, $keyword);
        $stmt->execute();
        $result = $stmt->get_result();

        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        return $data;
    }

    // MENCARI USER BERDASARKAN ID
    /**
     * Mengambil satu user berdasarkan ID.
     *
     * @param int $id_user ID user
     * @return array|null Data user, atau null jika tidak ada
     */
    public function getById($id_user)
    {
        $stmt = $this->koneksi->prepare(
            "SELECT * FROM users WHERE id_user = ?"
        );

        $stmt->bind_param("i", $id_user);
        $stmt->execute();

        return $stmt->get_result()->fetch_assoc();
    }

    // MENCARI USER BERDASARKAN USERNAME
    /**
     * Mengambil satu user berdasarkan username.
     *
     * @param string $username Username
     * @return array|null Data user, atau null jika tidak ada
     */
    public function getByUsername($username)
    {
        $stmt = $this->koneksi->prepare(
            "SELECT * FROM users WHERE username = ?"
        );

        $stmt->bind_param("s", $username);
        $stmt->execute();

        return $stmt->get_result()->fetch_assoc();
    }

    // MENCARI USER BERDASARKAN nis_nip
    /**
     * Mengambil satu user berdasarkan NIS/NIP.
     *
     * @param string $nis_nip NIS/NIP
     * @return array|null Data user, atau null jika tidak ada
     */
    public function getByNis_nip($nis_nip)
    {
        $stmt = $this->koneksi->prepare(
            "SELECT * FROM users WHERE nis_nip = ?"
        );

        $stmt->bind_param("s", $nis_nip);
        $stmt->execute();

        return $stmt->get_result()->fetch_assoc();
    }

    // TAMBAH USER
    /**
     * Menyimpan user baru dari properti objek.
     *
     * @return bool
     */
    public function insert()
    {
        $stmt = $this->koneksi->prepare(
            "INSERT INTO users
            (nis_nip, nama_lengkap, kelas, username, password, role)
            VALUES (?, ?, ?, ?, ?, ?)"
        );

        $stmt->bind_param(
            "ssssss",
            $this->nis_nip,
            $this->nama_lengkap,
            $this->kelas,
            $this->username,
            $this->password,
            $this->role
        );

        return $stmt->execute();
    }

    // UPDATE USER
    /**
     * Mengubah data user dari properti objek (tanpa password).
     *
     * @return bool
     */
    public function update()
    {
        $stmt = $this->koneksi->prepare(
            "UPDATE users SET
                nis_nip = ?,
                nama_lengkap = ?,
                kelas = ?,
                username = ?,
                role = ?
             WHERE id_user = ?"
        );

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

    // UPDATE PASSWORD
    /**
     * Mengganti password user dari properti objek.
     *
     * @return bool
     */
    public function updatePassword()
    {
        $stmt = $this->koneksi->prepare(
            "UPDATE users SET password = ?
             WHERE id_user = ?"
        );

        $stmt->bind_param(
            "si",
            $this->password,
            $this->id_user
        );

        return $stmt->execute();
    }

    // HAPUS USER
    /**
     * Menghapus user; gagal jika user sudah memiliki peminjaman.
     *
     * @param int $id_user ID user
     * @return bool
     */
    public function delete($id_user)
    {
        $stmt = $this->koneksi->prepare(
            "DELETE FROM users WHERE id_user = ?"
        );

        $stmt->bind_param("i", $id_user);

        return $stmt->execute();
    }
}
