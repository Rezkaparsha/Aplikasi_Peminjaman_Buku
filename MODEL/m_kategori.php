<?php

// =====================================================================
// FILE   : MODEL/m_kategori.php
// FUNGSI : MODEL tabel `kategori`. Berisi query lihat, cari per id, tambah, ubah,
//          hapus, dan pengecekan apakah kategori masih dipakai data lain.
//          Semua query memakai prepared statement (aman dari SQL Injection).
// =====================================================================
// Memuat koneksi database.
require_once __DIR__ . "/m_koneksi.php";

// Class Kategori. Properti public di bawah = penampung data satu kategori (nama sama dengan kolom tabel).
class Kategori
{
    // Koneksi database (private).
    private $koneksi;

    // Properti data; diisi controller sebelum memanggil insert()/update().
    public $id_kategori;
    public $nama_kategori;
    public $deskripsi;
    public $status;

    // Constructor: buka koneksi database.
    public function __construct()
    {
        $db = new Koneksi();
        $this->koneksi = $db->getKoneksi();
    }

    // getAll(): semua data kategori, diurutkan nama A-Z. Dipakai untuk halaman daftar dan pilihan dropdown di form buku.
    public function getAll()
    {
        $query = "SELECT id_kategori, nama_kategori, deskripsi, status
                  FROM kategori
                  ORDER BY nama_kategori ASC";

        $result = $this->koneksi->query($query);
        $data = [];

        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
        }

        return $data;
    }

    // getById($id): satu data kategori untuk form edit. Hasil null/kosong jika id tidak ada.
    public function getById($id_kategori)
    {
        $stmt = $this->koneksi->prepare(
            "SELECT id_kategori, nama_kategori, deskripsi, status
             FROM kategori
             WHERE id_kategori = ?"
        );

        if (!$stmt) {
            return null;
        }

        // Isi tanda tanya dengan id (i = integer).
        $stmt->bind_param("i", $id_kategori);
        $stmt->execute();

        $data = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $data;
    }

    // getAktif(): hanya kategori berstatus 'aktif' (tabel kategori punya kolom status aktif/nonaktif).
    public function getAktif()
    {
        $stmt = $this->koneksi->prepare(
            "SELECT id_kategori, nama_kategori, deskripsi, status
             FROM kategori
             WHERE status = 'aktif'
             ORDER BY nama_kategori ASC"
        );

        if (!$stmt) {
            return [];
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $data = [];

        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }

        $stmt->close();

        return $data;
    }

    // CEK APAKAH KATEGORI MASIH TERHUBUNG DENGAN BUKU
    // isUsedInBuku($id): menghitung (COUNT) buku yang memakai kategori ini di tabel buku.
    public function isUsedInBuku($id_kategori)
    {
        $stmt = $this->koneksi->prepare("
            SELECT COUNT(*) AS total 
            FROM buku 
            WHERE id_kategori = ?
        ");

        if (!$stmt) {
            return false;
        }

        $stmt->bind_param("i", $id_kategori);
        $stmt->execute();

        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        // Kembalikan true jika jumlah > 0 (artinya masih dipakai), false jika tidak.
        return ($result['total'] > 0);
    }

    // insert(): menambah kategori baru dari nilai properti objek. Mengembalikan true/false.
    public function insert()
    {
        $stmt = $this->koneksi->prepare(
            "INSERT INTO kategori (nama_kategori, deskripsi, status)
             VALUES (?, ?, ?)"
        );

        if (!$stmt) {
            return false;
        }

        $stmt->bind_param(
            // Tiga string: nama_kategori, deskripsi, status.
            "sss",
            $this->nama_kategori,
            $this->deskripsi,
            $this->status
        );

        $hasil = $stmt->execute();
        $stmt->close();

        return $hasil;
    }

    // update(): mengubah data kategori berdasarkan id (klausa WHERE wajib agar hanya satu baris yang berubah).
    public function update()
    {
        $stmt = $this->koneksi->prepare(
            "UPDATE kategori
             SET nama_kategori = ?, deskripsi = ?, status = ?
             WHERE id_kategori = ?"
        );

        if (!$stmt) {
            return false;
        }

        $stmt->bind_param(
            // Tiga string + satu integer (id_kategori) untuk WHERE.
            "sssi",
            $this->nama_kategori,
            $this->deskripsi,
            $this->status,
            $this->id_kategori
        );

        $hasil = $stmt->execute();
        $stmt->close();

        return $hasil;
    }

    // HAPUS KATEGORI (DENGAN EXCEPTION)
    // delete($id): menghapus kategori. Double proteksi: cek isUsedInBuku() dulu, lalu try/catch menangkap error foreign key dari MySQL (return false, tidak membuat halaman error).
    public function delete($id_kategori)
    {
        // Masih dipakai -> jangan hapus.
        if ($this->isUsedInBuku($id_kategori)) {
            return false;
        }

        try {
            $stmt = $this->koneksi->prepare(
                "DELETE FROM kategori WHERE id_kategori = ?"
            );

            if (!$stmt) {
                return false;
            }

            $stmt->bind_param("i", $id_kategori);
            $hasil = $stmt->execute();
            $stmt->close();

            return $hasil;
        // Error dari MySQL (mis. foreign key) -> kembalikan false.
        } catch (mysqli_sql_exception $e) {
            return false;
        }
    }
}