<?php

require_once __DIR__ . "/m_koneksi.php";

/**
 * Model tabel buku_penulis (relasi buku dan penulis).
 */
class BukuPenulis
{
    private $koneksi;

    public $id_buku;
    public $id_penulis;

    /**
     * Membuka koneksi database.
     */
    public function __construct()
    {
        $db = new Koneksi();
        $this->koneksi = $db->getKoneksi();
    }

    // MENAMPILKAN SEMUA RELASI BUKU DAN PENULIS
    /**
     * Mengambil semua relasi buku dan penulis.
     *
     * @return array Daftar relasi
     */
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
    /**
     * Mengambil daftar penulis dari satu buku.
     *
     * @param int $id_buku ID buku
     * @return array Daftar penulis
     */
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
    /**
     * Mengambil daftar buku dari satu penulis.
     *
     * @param int $id_penulis ID penulis
     * @return array Daftar buku
     */
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
    /**
     * Menyimpan relasi buku dan penulis dari properti objek.
     *
     * @return bool
     */
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
    /**
     * Menghapus satu relasi buku dan penulis.
     *
     * @param int $id_buku ID buku
     * @param int $id_penulis ID penulis
     * @return bool
     */
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
    /**
     * Menghapus semua relasi penulis dari satu buku.
     *
     * @param int $id_buku ID buku
     * @return bool
     */
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