<?php

require_once 'm_koneksi.php';

/**
 * Model tabel detail_peminjaman.
 */
class DetailPeminjaman
{
    private $koneksi;

    public $id_detail;
    public $id_peminjaman;
    public $id_buku;
    public $tanggal_pengembalian;
    public $tanggal_dikembalikan;
    public $status;

    /**
     * Membuka koneksi database.
     */
    public function __construct()
    {
        $db = new Koneksi();
        $this->koneksi = $db->getKoneksi();
    }

    // MENAMPILKAN SEMUA DETAIL PEMINJAMAN
    /**
     * Mengambil semua detail peminjaman beserta judul buku.
     *
     * @return array Daftar detail
     */
    public function getAll()
    {
        $query = "
            SELECT
                detail_peminjaman.id_detail,
                detail_peminjaman.id_peminjaman,
                detail_peminjaman.id_buku,
                buku.judul_buku,
                detail_peminjaman.tanggal_pengembalian,
                detail_peminjaman.tanggal_dikembalikan,
                detail_peminjaman.status
            FROM detail_peminjaman
            INNER JOIN buku
                ON detail_peminjaman.id_buku = buku.id_buku
            ORDER BY detail_peminjaman.id_detail DESC
        ";

        $result = $this->koneksi->query($query);
        $data = [];

        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }

        return $data;
    }

    // MENCARI DETAIL BERDASARKAN ID
    /**
     * Mengambil satu detail peminjaman berdasarkan ID.
     *
     * @param int $id_detail ID detail peminjaman
     * @return array|null Data detail, atau null jika tidak ada
     */
    public function getById($id_detail)
    {
        $stmt = $this->koneksi->prepare("
            SELECT
                detail_peminjaman.*,
                buku.judul_buku
            FROM detail_peminjaman
            INNER JOIN buku
                ON detail_peminjaman.id_buku = buku.id_buku
            WHERE detail_peminjaman.id_detail = ?
        ");

        $stmt->bind_param("i", $id_detail);
        $stmt->execute();

        return $stmt->get_result()->fetch_assoc();
    }

    // MENCARI DETAIL BERDASARKAN PEMINJAMAN
    /**
     * Mengambil buku-buku dalam satu peminjaman.
     *
     * @param int $id_peminjaman ID peminjaman
     * @return array Daftar detail
     */
    public function getByPeminjaman($id_peminjaman)
    {
        $stmt = $this->koneksi->prepare("
            SELECT
                detail_peminjaman.id_detail,
                detail_peminjaman.id_peminjaman,
                detail_peminjaman.id_buku,
                buku.judul_buku,
                buku.harga_buku,
                detail_peminjaman.tanggal_pengembalian,
                detail_peminjaman.tanggal_dikembalikan,
                detail_peminjaman.status
            FROM detail_peminjaman
            INNER JOIN buku
                ON detail_peminjaman.id_buku = buku.id_buku
            WHERE detail_peminjaman.id_peminjaman = ?
            ORDER BY detail_peminjaman.id_detail ASC
        ");

        $stmt->bind_param("i", $id_peminjaman);
        $stmt->execute();

        $result = $stmt->get_result();
        $data = [];

        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }

        return $data;
    }

    // TAMBAH DETAIL PEMINJAMAN
    /**
     * Menyimpan detail peminjaman dari properti objek.
     *
     * @return bool
     */
    public function insert()
    {
        $stmt = $this->koneksi->prepare("
            INSERT INTO detail_peminjaman
            (
                id_peminjaman,
                id_buku,
                tanggal_pengembalian,
                tanggal_dikembalikan,
                status
            )
            VALUES (?, ?, ?, ?, ?)
        ");

        $stmt->bind_param(
            "iisss",
            $this->id_peminjaman,
            $this->id_buku,
            $this->tanggal_pengembalian,
            $this->tanggal_dikembalikan,
            $this->status
        );

        return $stmt->execute();
    }

    // UPDATE DETAIL PEMINJAMAN
    /**
     * Mengubah detail peminjaman dari properti objek.
     *
     * @return bool
     */
    public function update()
    {
        $stmt = $this->koneksi->prepare("
            UPDATE detail_peminjaman SET
                id_buku = ?,
                tanggal_pengembalian = ?,
                tanggal_dikembalikan = ?,
                status = ?
            WHERE id_detail = ?
        ");

        $stmt->bind_param(
            "isssi",
            $this->id_buku,
            $this->tanggal_pengembalian,
            $this->tanggal_dikembalikan,
            $this->status,
            $this->id_detail
        );

        return $stmt->execute();
    }

    // PROSES PENGEMBALIAN BUKU
    /**
     * Menandai satu detail sebagai dikembalikan.
     *
     * @param int $id_detail ID detail peminjaman
     * @param string $tanggal_dikembalikan Tanggal buku dikembalikan (Y-m-d)
     * @return bool
     */
    public function kembalikanBuku($id_detail, $tanggal_dikembalikan)
    {
        $status = 'Dikembalikan';

        $stmt = $this->koneksi->prepare("
            UPDATE detail_peminjaman SET
                tanggal_dikembalikan = ?,
                status = ?
            WHERE id_detail = ?
        ");

        $stmt->bind_param(
            "ssi",
            $tanggal_dikembalikan,
            $status,
            $id_detail
        );

        return $stmt->execute();
    }

    // MENGHAPUS DETAIL
    /**
     * Menghapus satu detail peminjaman.
     *
     * @param int $id_detail ID detail peminjaman
     * @return bool
     */
    public function delete($id_detail)
    {
        $stmt = $this->koneksi->prepare("
            DELETE FROM detail_peminjaman
            WHERE id_detail = ?
        ");

        $stmt->bind_param("i", $id_detail);

        return $stmt->execute();
    }

    // CEK APAKAH BUKU MASIH DIPINJAM
    /**
     * Memeriksa apakah satu buku sedang berstatus Dipinjam.
     *
     * @param int $id_buku ID buku
     */
    public function cekBukuSedangDipinjam($id_buku)
    {
        $stmt = $this->koneksi->prepare("
            SELECT COUNT(*) AS jumlah
            FROM detail_peminjaman
            WHERE id_buku = ?
              AND status = 'Dipinjam'
        ");

        $stmt->bind_param("i", $id_buku);
        $stmt->execute();

        return $stmt->get_result()->fetch_assoc()['jumlah'];
    }
}
?>