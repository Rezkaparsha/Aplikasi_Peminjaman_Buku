<?php

require_once 'm_koneksi.php';

/**
 * Model tabel denda.
 */
class Denda
{
    private $koneksi;

    public $id_denda;
    public $id_detail;
    public $jenis_denda;
    public $jumlah_hari_terlambat;
    public $jumlah_denda;
    public $tanggal_denda;
    public $status_pembayaran;
    public $keterangan;

    /**
     * Membuka koneksi database.
     */
    public function __construct()
    {
        $db = new Koneksi();
        $this->koneksi = $db->getKoneksi();
    }

    // MENAMPILKAN SEMUA DENDA
    /**
     * Mengambil semua denda beserta judul buku dan nama siswa.
     *
     * @return array Daftar denda
     */
    public function getAll()
    {
        $query = "
            SELECT
                denda.id_denda,
                denda.id_detail,
                buku.judul_buku,
                users.nama_lengkap,

                denda.jenis_denda,
                denda.jumlah_hari_terlambat,
                denda.jumlah_denda,
                denda.tanggal_denda,
                denda.status_pembayaran,
                denda.keterangan

            FROM denda

            INNER JOIN detail_peminjaman
                ON denda.id_detail = detail_peminjaman.id_detail

            INNER JOIN buku
                ON detail_peminjaman.id_buku = buku.id_buku

            INNER JOIN peminjaman
                ON detail_peminjaman.id_peminjaman = peminjaman.id_peminjaman

            INNER JOIN users
                ON peminjaman.id_user = users.id_user

            ORDER BY denda.id_denda DESC
        ";

        $result = $this->koneksi->query($query);
        $data = [];

        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }

        return $data;
    }

    // MENCARI DENDA BERDASARKAN ID
    /**
     * Mengambil satu denda berdasarkan ID.
     *
     * @param int $id_denda ID denda
     * @return array|null Data denda, atau null jika tidak ada
     */
    public function getById($id_denda)
    {
        $stmt = $this->koneksi->prepare("
            SELECT *
            FROM denda
            WHERE id_denda = ?
        ");

        $stmt->bind_param(
            "i",
            $id_denda
        );

        $stmt->execute();

        return $stmt->get_result()->fetch_assoc();
    }

    // MENCARI DENDA BERDASARKAN DETAIL
    /**
     * Mengambil denda milik satu detail peminjaman.
     *
     * @param int $id_detail ID detail peminjaman
     * @return array Daftar denda
     */
    public function getByDetail($id_detail)
    {
        $stmt = $this->koneksi->prepare("
            SELECT *
            FROM denda
            WHERE id_detail = ?
            ORDER BY id_denda DESC
        ");

        $stmt->bind_param(
            "i",
            $id_detail
        );

        $stmt->execute();

        $result = $stmt->get_result();
        $data = [];

        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }

        return $data;
    }

    // TAMBAH DENDA
    /**
     * Menyimpan denda baru dari properti objek.
     *
     * @return bool
     */
    public function insert()
    {
        $stmt = $this->koneksi->prepare("
            INSERT INTO denda
            (
                id_detail,
                jenis_denda,
                jumlah_hari_terlambat,
                jumlah_denda,
                tanggal_denda,
                status_pembayaran,
                keterangan
            )
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->bind_param(
            "isiisss",
            $this->id_detail,
            $this->jenis_denda,
            $this->jumlah_hari_terlambat,
            $this->jumlah_denda,
            $this->tanggal_denda,
            $this->status_pembayaran,
            $this->keterangan
        );

        return $stmt->execute();
    }

    // UPDATE DENDA
    /**
     * Mengubah data denda dari properti objek.
     *
     * @return bool
     */
    public function update()
    {
        $stmt = $this->koneksi->prepare("
            UPDATE denda SET
                jenis_denda = ?,
                jumlah_hari_terlambat = ?,
                jumlah_denda = ?,
                tanggal_denda = ?,
                status_pembayaran = ?,
                keterangan = ?
            WHERE id_denda = ?
        ");

        $stmt->bind_param(
            "siisssi",
            $this->jenis_denda,
            $this->jumlah_hari_terlambat,
            $this->jumlah_denda,
            $this->tanggal_denda,
            $this->status_pembayaran,
            $this->keterangan,
            $this->id_denda
        );

        return $stmt->execute();
    }

    // UPDATE STATUS PEMBAYARAN
    /**
     * Mengubah status pembayaran denda.
     *
     * @param int $id_denda ID denda
     * @param string $status_pembayaran Status pembayaran
     * @return bool
     */
    public function updateStatusPembayaran($id_denda, $status_pembayaran)
    {
        $stmt = $this->koneksi->prepare("
            UPDATE denda
            SET status_pembayaran = ?
            WHERE id_denda = ?
        ");

        $stmt->bind_param(
            "si",
            $status_pembayaran,
            $id_denda
        );

        return $stmt->execute();
    }

    // HAPUS DENDA
    /**
     * Menghapus satu denda.
     *
     * @param int $id_denda ID denda
     * @return bool
     */
    public function delete($id_denda)
    {
        $stmt = $this->koneksi->prepare("
            DELETE FROM denda
            WHERE id_denda = ?
        ");

        $stmt->bind_param(
            "i",
            $id_denda
        );

        return $stmt->execute();
    }
}
?>