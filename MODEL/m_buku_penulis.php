<?php

// =====================================================================
// FILE   : MODEL/m_buku_penulis.php
// FUNGSI : MODEL tabel penghubung `buku_penulis` (relasi BANYAK-KE-BANYAK:
//          satu buku bisa punya banyak penulis, satu penulis bisa menulis
//          banyak buku). Primary key gabungan: (id_buku, id_penulis).
// =====================================================================
require_once __DIR__ . "/m_koneksi.php";

// Class BukuPenulis.
class BukuPenulis
{
    // Koneksi database.
    private $koneksi;

    // Properti relasi: diisi controller sebelum insert().
    public $id_buku;
    public $id_penulis;

    // Constructor: buka koneksi.
    public function __construct()
    {
        $db = new Koneksi();
        $this->koneksi = $db->getKoneksi();
    }

    // MENAMPILKAN SEMUA RELASI BUKU DAN PENULIS
    // getAll(): semua pasangan buku-penulis lengkap dengan judul dan nama penulis (INNER JOIN 2 tabel).
    public function getAll()
    {
        $query = "
            SELECT
                buku_penulis.id_buku,
                buku.judul_buku,
                buku_penulis.id_penulis,
                penulis.nama_penulis
            FROM buku_penulis
            INNER JOIN buku
                ON buku_penulis.id_buku = buku.id_buku
            INNER JOIN penulis
                ON buku_penulis.id_penulis = penulis.id_penulis
            ORDER BY buku.judul_buku ASC
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

    // MENCARI SEMUA PENULIS DARI SATU BUKU
    // getPenulisByBuku($id_buku): daftar penulis dari SATU buku (untuk form edit buku).
    public function getPenulisByBuku($id_buku)
    {
        $stmt = $this->koneksi->prepare("
            SELECT
                penulis.id_penulis,
                penulis.nama_penulis
            FROM buku_penulis
            INNER JOIN penulis
                ON buku_penulis.id_penulis = penulis.id_penulis
            WHERE buku_penulis.id_buku = ?
            ORDER BY penulis.nama_penulis ASC
        ");

        if (!$stmt) {
            return [];
        }

        $stmt->bind_param("i", $id_buku);
        $stmt->execute();

        $result = $stmt->get_result();
        $data = [];

        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }

        $stmt->close();

        return $data;
    }

    // MENCARI SEMUA BUKU DARI SATU PENULIS
    // getBukuByPenulis($id_penulis): daftar buku dari SATU penulis.
    public function getBukuByPenulis($id_penulis)
    {
        $stmt = $this->koneksi->prepare("
            SELECT
                buku.id_buku,
                buku.judul_buku
            FROM buku_penulis
            INNER JOIN buku
                ON buku_penulis.id_buku = buku.id_buku
            WHERE buku_penulis.id_penulis = ?
            ORDER BY buku.judul_buku ASC
        ");

        if (!$stmt) {
            return [];
        }

        $stmt->bind_param("i", $id_penulis);
        $stmt->execute();

        $result = $stmt->get_result();
        $data = [];

        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }

        $stmt->close();

        return $data;
    }

    // MENAMBAHKAN PENULIS KE BUKU
    // insert(): menghubungkan satu buku dengan satu penulis.
    public function insert()
    {
        $stmt = $this->koneksi->prepare("
            INSERT INTO buku_penulis
            (id_buku, id_penulis)
            VALUES (?, ?)
        ");

        if (!$stmt) {
            return false;
        }

        // Dua integer: id_buku dan id_penulis.
        $stmt->bind_param(
            "ii",
            $this->id_buku,
            $this->id_penulis
        );

        $hasil = $stmt->execute();
        $stmt->close();

        return $hasil;
    }

    // MENGHAPUS SATU RELASI
    // delete(): menghapus SATU relasi tertentu (kedua id harus cocok).
    public function delete($id_buku, $id_penulis)
    {
        $stmt = $this->koneksi->prepare("
            DELETE FROM buku_penulis
            WHERE id_buku = ?
            AND id_penulis = ?
        ");

        if (!$stmt) {
            return false;
        }

        // Dua integer: id_buku dan id_penulis.
        $stmt->bind_param(
            "ii",
            $id_buku,
            $id_penulis
        );

        $hasil = $stmt->execute();
        $stmt->close();

        return $hasil;
    }

    // MENGHAPUS SEMUA PENULIS DARI SATU BUKU
    // deleteByBuku($id_buku): menghapus SEMUA penulis dari satu buku. Dipakai saat update/hapus buku.
    public function deleteByBuku($id_buku)
    {
        $stmt = $this->koneksi->prepare("
            DELETE FROM buku_penulis
            WHERE id_buku = ?
        ");

        if (!$stmt) {
            return false;
        }

        $stmt->bind_param("i", $id_buku);

        $hasil = $stmt->execute();
        $stmt->close();

        return $hasil;
    }
}
?>