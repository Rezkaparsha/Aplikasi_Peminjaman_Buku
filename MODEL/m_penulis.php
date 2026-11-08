<?php

// =====================================================================
// FILE   : MODEL/m_penulis.php
// FUNGSI : MODEL tabel `penulis`. Berisi query lihat, cari per id, tambah, ubah,
//          hapus, dan pengecekan apakah penulis masih dipakai data lain.
//          Semua query memakai prepared statement (aman dari SQL Injection).
// =====================================================================
// Memuat koneksi database.
require_once __DIR__ . "/m_koneksi.php";

// Class Penulis. Properti public di bawah = penampung data satu penulis (nama sama dengan kolom tabel).
class Penulis
{
    // Koneksi database (private).
    private $koneksi;

    // Properti data; diisi controller sebelum memanggil insert()/update().
    public $id_penulis;
    public $nama_penulis;

    // Constructor: buka koneksi database.
    public function __construct()
    {
        $db = new Koneksi();
        $this->koneksi = $db->getKoneksi();
    }

    // MENAMPILKAN SEMUA PENULIS
    // getAll(): semua data penulis, diurutkan nama A-Z. Dipakai untuk halaman daftar dan pilihan dropdown di form buku.
    public function getAll()
    {
        $query = "
            SELECT
                id_penulis,
                nama_penulis
            FROM penulis
            ORDER BY nama_penulis ASC
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

    // MENCARI PENULIS BERDASARKAN ID
    // getById($id): satu data penulis untuk form edit. Hasil null/kosong jika id tidak ada.
    public function getById($id_penulis)
    {
        $stmt = $this->koneksi->prepare("
            SELECT
                id_penulis,
                nama_penulis
            FROM penulis
            WHERE id_penulis = ?
        ");

        if (!$stmt) {
            return null;
        }

        // Isi tanda tanya dengan id (i = integer).
        $stmt->bind_param("i", $id_penulis);
        $stmt->execute();

        $result = $stmt->get_result();
        $data = $result->fetch_assoc();

        $stmt->close();

        return $data;
    }

    // CEK APAKAH PENULIS MASIH TERHUBUNG DENGAN BUKU
    // isUsedInBuku($id): menghitung (COUNT) relasi di tabel buku_penulis yang memakai penulis ini
    // (penulis tidak berhubungan langsung ke tabel buku, tetapi lewat tabel penghubung).
    public function isUsedInBuku($id_penulis)
    {
        $stmt = $this->koneksi->prepare("
            SELECT COUNT(*) AS total 
            FROM buku_penulis 
            WHERE id_penulis = ?
        ");

        if (!$stmt) {
            return false;
        }

        $stmt->bind_param("i", $id_penulis);
        $stmt->execute();

        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        // Kembalikan true jika jumlah > 0 (artinya masih dipakai), false jika tidak.
        return ($result['total'] > 0);
    }

    // MENAMBAHKAN PENULIS
    // insert(): menambah penulis baru dari nilai properti objek. Mengembalikan true/false.
    public function insert()
    {
        $stmt = $this->koneksi->prepare("
            INSERT INTO penulis
            (nama_penulis)
            VALUES (?)
        ");

        if (!$stmt) {
            return false;
        }

        $stmt->bind_param(
            // Satu string: nama_penulis.
            "s",
            $this->nama_penulis
        );

        $hasil = $stmt->execute();

        if ($hasil) {
            // Simpan id otomatis hasil INSERT ke properti.
            $this->id_penulis = $stmt->insert_id;
        }

        $stmt->close();

        return $hasil;
    }

    // UPDATE PENULIS
    // update(): mengubah data penulis berdasarkan id (klausa WHERE wajib agar hanya satu baris yang berubah).
    public function update()
    {
        $stmt = $this->koneksi->prepare("
            UPDATE penulis
            SET nama_penulis = ?
            WHERE id_penulis = ?
        ");

        if (!$stmt) {
            return false;
        }

        $stmt->bind_param(
            // String nama_penulis + integer id_penulis untuk WHERE.
            "si",
            $this->nama_penulis,
            $this->id_penulis
        );

        $hasil = $stmt->execute();

        $stmt->close();

        return $hasil;
    }

    // HAPUS PENULIS (DENGAN PENANGANAN EXCEPTION)
    // delete($id): menghapus penulis. Double proteksi: cek isUsedInBuku() dulu, lalu try/catch menangkap error foreign key dari MySQL (return false, tidak membuat halaman error).
    public function delete($id_penulis)
    {
        // Masih dipakai -> jangan hapus.
        if ($this->isUsedInBuku($id_penulis)) {
            return false;
        }

        try {
            $stmt = $this->koneksi->prepare("
                DELETE FROM penulis
                WHERE id_penulis = ?
            ");

            if (!$stmt) {
                return false;
            }

            $stmt->bind_param("i", $id_penulis);
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