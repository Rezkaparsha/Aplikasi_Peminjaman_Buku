<?php

require_once 'm_koneksi.php';

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

    public function __construct()
    {
        $db = new Koneksi();
        $this->koneksi = $db->getKoneksi();
    }

    // MENAMPILKAN SEMUA USER
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
    public function delete($id_user)
    {
        $stmt = $this->koneksi->prepare(
            "DELETE FROM users WHERE id_user = ?"
        );

        $stmt->bind_param("i", $id_user);

        return $stmt->execute();
    }
}
