<?php

require_once __DIR__ . "/m_koneksi.php";

/**
 * Model tabel kategori.
 */
class Kategori
{
    private $koneksi;

    public $id_kategori;
    public $nama_kategori;
    public $deskripsi;
    public $status;

    /**
     * Membuka koneksi database.
     */
    public function __construct()
    {
        $db = new Koneksi();
        $this->koneksi = $db->getKoneksi();
    }

    /**
     * Mengambil semua data kategori.
     *
     * @return array Daftar kategori
     */
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

    /**
     * Mengambil satu data kategori berdasarkan ID.
     *
     * @param int $id_kategori ID kategori
     * @return array|null Data kategori, atau null jika tidak ada
     */
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

        $stmt->bind_param("i", $id_kategori);
        $stmt->execute();

        $data = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $data;
    }

    /**
     * Mengambil kategori yang berstatus aktif.
     *
     * @return array Daftar kategori aktif
     */
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
    /**
     * Memeriksa apakah kategori masih dipakai oleh buku.
     *
     * @param int $id_kategori ID kategori
     * @return bool true jika masih dipakai
     */
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

        return ($result['total'] > 0);
    }

    /**
     * Menyimpan kategori baru dari properti objek.
     *
     * @return bool
     */
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
            "sss",
            $this->nama_kategori,
            $this->deskripsi,
            $this->status
        );

        $hasil = $stmt->execute();
        $stmt->close();

        return $hasil;
    }

    /**
     * Mengubah data kategori dari properti objek.
     *
     * @return bool
     */
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
    /**
     * Menghapus kategori; gagal jika masih dipakai buku.
     *
     * @param int $id_kategori ID kategori
     * @return bool
     */
    public function delete($id_kategori)
    {
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
        } catch (mysqli_sql_exception $e) {
            return false;
        }
    }
}