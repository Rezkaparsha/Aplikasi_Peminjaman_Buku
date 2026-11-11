<?php

require_once __DIR__ . "/m_koneksi.php";

/**
 * Model tabel penulis.
 */
class Penulis
{
    private $koneksi;

    public $id_penulis;
    public $nama_penulis;

    /**
     * Membuka koneksi database.
     */
    public function __construct()
    {
        $db = new Koneksi();
        $this->koneksi = $db->getKoneksi();
    }

    // MENAMPILKAN SEMUA PENULIS
    /**
     * Mengambil semua data penulis.
     *
     * @return array Daftar penulis
     */
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
    /**
     * Mencari penulis berdasarkan nama.
     *
     * @param string $keyword Kata kunci pencarian
     * @return array Daftar penulis yang sesuai
     */
    public function search($keyword)
    {
        $keyword = "%{$keyword}%";
        $stmt = $this->koneksi->prepare("SELECT id_penulis, nama_penulis FROM penulis WHERE nama_penulis LIKE ? ORDER BY nama_penulis ASC");
        $stmt->bind_param("s", $keyword);
        $stmt->execute();
        $result = $stmt->get_result();

        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }

        return $data;
    }

    /**
     * Mengambil nama penulis untuk daftar pilihan pada dropdown pencarian (jumlahnya dibatasi).
     *
     * @param string $keyword Kata kunci nama
     * @param int $batas Jumlah data maksimum
     * @return array Daftar nama penulis
     */
    public function cariNama($keyword, $batas = 10)
    {
        $keyword = "%{$keyword}%";
        $batas = (int)$batas;
        $stmt = $this->koneksi->prepare("SELECT nama_penulis FROM penulis WHERE nama_penulis LIKE ? ORDER BY nama_penulis ASC LIMIT ?");
        $stmt->bind_param("si", $keyword, $batas);
        $stmt->execute();
        $result = $stmt->get_result();

        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row['nama_penulis'];
        }

        return $data;
    }

    /**
     * Mengambil satu data penulis berdasarkan ID.
     *
     * @param int $id_penulis ID penulis
     * @return array|null Data penulis, atau null jika tidak ada
     */
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

        $stmt->bind_param("i", $id_penulis);
        $stmt->execute();

        $result = $stmt->get_result();
        $data = $result->fetch_assoc();

        $stmt->close();

        return $data;
    }

    // CEK APAKAH PENULIS MASIH TERHUBUNG DENGAN BUKU
    /**
     * Memeriksa apakah penulis masih dipakai oleh buku.
     *
     * @param int $id_penulis ID penulis
     * @return bool true jika masih dipakai
     */
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

        return ($result['total'] > 0);
    }

    // MENAMBAHKAN PENULIS
    /**
     * Menyimpan penulis baru dari properti objek.
     *
     * @return bool
     */
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
            "s",
            $this->nama_penulis
        );

        $hasil = $stmt->execute();

        if ($hasil) {
            // Ambil ID data yang baru disimpan
            $this->id_penulis = $stmt->insert_id;
        }

        $stmt->close();

        return $hasil;
    }

    // UPDATE PENULIS
    /**
     * Mengubah data penulis dari properti objek.
     *
     * @return bool
     */
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
            "si",
            $this->nama_penulis,
            $this->id_penulis
        );

        $hasil = $stmt->execute();

        $stmt->close();

        return $hasil;
    }

    // HAPUS PENULIS (DENGAN PENANGANAN EXCEPTION)
    /**
     * Menghapus penulis; gagal jika masih dipakai buku.
     *
     * @param int $id_penulis ID penulis
     * @return bool
     */
    public function delete($id_penulis)
    {
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
        } catch (mysqli_sql_exception $e) {
            return false;
        }
    }
}
?>