<?php

require_once __DIR__ . "/m_koneksi.php";

/**
 * Model tabel penerbit.
 */
class Penerbit
{
    private $koneksi;

    public $id_penerbit;
    public $nama_penerbit;
    public $alamat;
    public $kota;
    public $telepon;
    public $email;

    /**
     * Membuka koneksi database.
     */
    public function __construct()
    {
        $db = new Koneksi();
        $this->koneksi = $db->getKoneksi();
    }

    // AMBIL SEMUA PENERBIT
    /**
     * Mengambil semua data penerbit.
     *
     * @return array Daftar penerbit
     */
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
    /**
     * Mengambil satu data penerbit berdasarkan ID.
     *
     * @param int $id_penerbit ID penerbit
     * @return array|null Data penerbit, atau null jika tidak ada
     */
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

        $stmt->bind_param("i", $id_penerbit);
        $stmt->execute();

        $result = $stmt->get_result();
        $data = $result->fetch_assoc();

        $stmt->close();

        return $data;
    }

    // CEK APAKAH PENERBIT MASIH TERHUBUNG DENGAN BUKU
    /**
     * Memeriksa apakah penerbit masih dipakai oleh buku.
     *
     * @param int $id_penerbit ID penerbit
     * @return bool true jika masih dipakai
     */
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

        return ($result['total'] > 0);
    }

    // TAMBAH PENERBIT
    /**
     * Menyimpan penerbit baru dari properti objek.
     *
     * @return bool
     */
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
            "sssss",
            $this->nama_penerbit,
            $this->alamat,
            $this->kota,
            $this->telepon,
            $this->email
        );

        $hasil = $stmt->execute();

        if ($hasil) {
            // Ambil ID data yang baru disimpan
            $this->id_penerbit = $stmt->insert_id;
        }

        $stmt->close();

        return $hasil;
    }

    // UPDATE PENERBIT
    /**
     * Mengubah data penerbit dari properti objek.
     *
     * @return bool
     */
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
    /**
     * Menghapus penerbit; gagal jika masih dipakai buku.
     *
     * @param int $id_penerbit ID penerbit
     * @return bool
     */
    public function delete($id_penerbit)
    {
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
        } catch (mysqli_sql_exception $e) {
            return false;
        }
    }
}
?>