<?php

// =====================================================================
// FILE   : MODEL/m_penerbit.php
// FUNGSI : MODEL tabel `penerbit`. Berisi query lihat, cari per id, tambah, ubah,
//          hapus, dan pengecekan apakah penerbit masih dipakai data lain.
//          Semua query memakai prepared statement (aman dari SQL Injection).
// =====================================================================
// Memuat koneksi database.
require_once __DIR__ . "/m_koneksi.php";

// Class Penerbit. Properti public di bawah = penampung data satu penerbit (nama sama dengan kolom tabel).
class Penerbit
{
    // Koneksi database (private).
    private $koneksi;

    // Properti data; diisi controller sebelum memanggil insert()/update().
    public $id_penerbit;
    public $nama_penerbit;
    public $alamat;
    public $kota;
    public $telepon;
    public $email;

    // Constructor: buka koneksi database.
    public function __construct()
    {
        $db = new Koneksi();
        $this->koneksi = $db->getKoneksi();
    }

    // AMBIL SEMUA PENERBIT
    // getAll(): semua data penerbit, diurutkan nama A-Z. Dipakai untuk halaman daftar dan pilihan dropdown di form buku.
    public function getAll()
    {
        $query = "
            SELECT
                id_penerbit,
                nama_penerbit,
                alamat,
                kota,
                telepon,
                email
            FROM penerbit
            ORDER BY nama_penerbit ASC
        ";

        $result = $this->koneksi->query($query);
        $data = [];

        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
        }

        return $data;
    }

    // AMBIL PENERBIT BERDASARKAN ID
    // getById($id): satu data penerbit untuk form edit. Hasil null/kosong jika id tidak ada.
    public function getById($id_penerbit)
    {
        $stmt = $this->koneksi->prepare("
            SELECT
                id_penerbit,
                nama_penerbit,
                alamat,
                kota,
                telepon,
                email
            FROM penerbit
            WHERE id_penerbit = ?
        ");

        if (!$stmt) {
            return null;
        }

        // Isi tanda tanya dengan id (i = integer).
        $stmt->bind_param("i", $id_penerbit);
        $stmt->execute();

        $result = $stmt->get_result();
        $data = $result->fetch_assoc();

        $stmt->close();

        return $data;
    }

    // CEK APAKAH PENERBIT MASIH TERHUBUNG DENGAN BUKU
    // isUsedInBuku($id): menghitung (COUNT) buku yang memakai penerbit ini di tabel buku.
    public function isUsedInBuku($id_penerbit)
    {
        $stmt = $this->koneksi->prepare("
            SELECT COUNT(*) AS total 
            FROM buku 
            WHERE id_penerbit = ?
        ");

        if (!$stmt) {
            return false;
        }

        $stmt->bind_param("i", $id_penerbit);
        $stmt->execute();

        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        // Kembalikan true jika jumlah > 0 (artinya masih dipakai), false jika tidak.
        return ($result['total'] > 0);
    }

    // TAMBAH PENERBIT
    // insert(): menambah penerbit baru dari nilai properti objek. Mengembalikan true/false.
    public function insert()
    {
        $stmt = $this->koneksi->prepare("
            INSERT INTO penerbit
            (
                nama_penerbit,
                alamat,
                kota,
                telepon,
                email
            )
            VALUES (?, ?, ?, ?, ?)
        ");

        if (!$stmt) {
            return false;
        }

        $stmt->bind_param(
            // Lima string: nama, alamat, kota, telepon, email.
            "sssss",
            $this->nama_penerbit,
            $this->alamat,
            $this->kota,
            $this->telepon,
            $this->email
        );

        $hasil = $stmt->execute();

        if ($hasil) {
            // Simpan id otomatis (AUTO_INCREMENT) hasil INSERT ke properti.
            $this->id_penerbit = $stmt->insert_id;
        }

        $stmt->close();

        return $hasil;
    }

    // UPDATE PENERBIT
    // update(): mengubah data penerbit berdasarkan id (klausa WHERE wajib agar hanya satu baris yang berubah).
    public function update()
    {
        $stmt = $this->koneksi->prepare("
            UPDATE penerbit
            SET
                nama_penerbit = ?,
                alamat = ?,
                kota = ?,
                telepon = ?,
                email = ?
            WHERE id_penerbit = ?
        ");

        if (!$stmt) {
            return false;
        }

        $stmt->bind_param(
            // Lima string + satu integer (id_penerbit) untuk WHERE.
            "sssssi",
            $this->nama_penerbit,
            $this->alamat,
            $this->kota,
            $this->telepon,
            $this->email,
            $this->id_penerbit
        );

        $hasil = $stmt->execute();

        $stmt->close();

        return $hasil;
    }

    // HAPUS PENERBIT (DENGAN EXCEPTION)
    // delete($id): menghapus penerbit. Double proteksi: cek isUsedInBuku() dulu, lalu try/catch menangkap error foreign key dari MySQL (return false, tidak membuat halaman error).
    public function delete($id_penerbit)
    {
        // Masih dipakai -> jangan hapus.
        if ($this->isUsedInBuku($id_penerbit)) {
            return false;
        }

        try {
            $stmt = $this->koneksi->prepare("
                DELETE FROM penerbit
                WHERE id_penerbit = ?
            ");

            if (!$stmt) {
                return false;
            }

            $stmt->bind_param("i", $id_penerbit);
            $hasil = $stmt->execute();
            $stmt->close();

            return $hasil;
        // Error dari MySQL (mis. foreign key) -> kembalikan false.
        } catch (mysqli_sql_exception $e) {
            return false;
        }
    }
}
?>