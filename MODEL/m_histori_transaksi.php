<?php

require_once 'm_koneksi.php';

/**
 * Model tabel histori_transaksi.
 */
class HistoriTransaksi
{
    private $koneksi;

    public $id_histori;
    public $id_peminjaman;
    public $id_detail;

    public $nis_nip;
    public $nama_siswa;

    public $id_buku;
    public $judul_buku;
    public $harga_buku;

    public $tanggal_pinjam;
    public $tanggal_pengembalian;
    public $tanggal_dikembalikan;

    public $jenis_denda;
    public $denda;

    public $tanggal_selesai;

    /**
     * Membuka koneksi database.
     */
    public function __construct()
    {
        $db = new Koneksi();
        $this->koneksi = $db->getKoneksi();
    }


    // MENAMPILKAN SEMUA HISTORI

    /**
     * Mengambil semua histori transaksi (terbaru di atas).
     *
     * @return array Daftar histori
     */
    public function getAll()
    {
        $query = "
            SELECT *
            FROM histori_transaksi
            ORDER BY id_histori DESC
        ";

        $result = $this->koneksi->query($query);

        $data = [];

        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }

        return $data;
    }


    // MENCARI HISTORI BERDASARKAN ID

    /**
     * Mengambil satu histori berdasarkan ID.
     *
     * @param int $id_histori ID histori
     * @return array|null Data histori, atau null jika tidak ada
     */
    public function getById($id_histori)
    {
        $stmt = $this->koneksi->prepare("
            SELECT *
            FROM histori_transaksi
            WHERE id_histori = ?
        ");

        $stmt->bind_param(
            "i",
            $id_histori
        );

        $stmt->execute();

        return $stmt->get_result()->fetch_assoc();
    }


    // HISTORI BERDASARKAN USER

    /**
     * Mengambil histori satu siswa berdasarkan NIS/NIP.
     *
     * @param string $nis_nip NIS/NIP
     * @return array Daftar histori
     */
    public function getByUser($nis_nip)
    {
        $stmt = $this->koneksi->prepare("
            SELECT *
            FROM histori_transaksi
            WHERE nis_nip = ?
            ORDER BY tanggal_selesai DESC
        ");

        $stmt->bind_param(
            "s",
            $nis_nip
        );

        $stmt->execute();

        $result = $stmt->get_result();

        $data = [];

        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }

        return $data;
    }


    // HISTORI BERDASARKAN PEMINJAMAN

    /**
     * Mengambil histori satu transaksi peminjaman.
     *
     * @param int $id_peminjaman ID peminjaman
     * @return array Daftar histori
     */
    public function getByPeminjaman($id_peminjaman)
    {
        $stmt = $this->koneksi->prepare("
            SELECT *
            FROM histori_transaksi
            WHERE id_peminjaman = ?
            ORDER BY id_histori ASC
        ");

        $stmt->bind_param(
            "i",
            $id_peminjaman
        );

        $stmt->execute();

        $result = $stmt->get_result();

        $data = [];

        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }

        return $data;
    }


    // TAMBAH HISTORI

    /**
     * Menyimpan satu baris histori dari properti objek.
     *
     * @return bool
     */
    public function insert()
    {
        $stmt = $this->koneksi->prepare("
            INSERT INTO histori_transaksi
            (
                id_peminjaman,
                id_detail,
                nis_nip,
                nama_siswa,
                id_buku,
                judul_buku,
                harga_buku,
                tanggal_pinjam,
                tanggal_pengembalian,
                tanggal_dikembalikan,
                jenis_denda,
                denda,
                tanggal_selesai
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        // Format tipe data: iissisissssis (13 kolom)
        $stmt->bind_param(
            "iissisissssis",
            $this->id_peminjaman,
            $this->id_detail,
            $this->nis_nip,
            $this->nama_siswa,
            $this->id_buku,
            $this->judul_buku,
            $this->harga_buku,
            $this->tanggal_pinjam,
            $this->tanggal_pengembalian,
            $this->tanggal_dikembalikan,
            $this->jenis_denda,
            $this->denda,
            $this->tanggal_selesai
        );

        return $stmt->execute();
    }


    // MENGHAPUS HISTORI

    /**
     * Menghapus satu histori.
     *
     * @param int $id_histori ID histori
     * @return bool
     */
    public function delete($id_histori)
    {
        $stmt = $this->koneksi->prepare("
            DELETE FROM histori_transaksi
            WHERE id_histori = ?
        ");

        $stmt->bind_param(
            "i",
            $id_histori
        );

        return $stmt->execute();
    }


    // PENCARIAN HISTORI

    /**
     * Mencari histori berdasarkan NIS/NIP, nama siswa, atau judul buku.
     *
     * @param string $keyword Kata kunci pencarian
     * @return array Daftar histori yang sesuai
     */
    public function search($keyword)
    {
        $keyword = "%" . $keyword . "%";

        $stmt = $this->koneksi->prepare("
            SELECT *
            FROM histori_transaksi
            WHERE nis_nip LIKE ?
               OR nama_siswa LIKE ?
               OR judul_buku LIKE ?
            ORDER BY tanggal_selesai DESC
        ");

        $stmt->bind_param(
            "sss",
            $keyword,
            $keyword,
            $keyword
        );

        $stmt->execute();

        $result = $stmt->get_result();

        $data = [];

        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }

        return $data;
    }
}