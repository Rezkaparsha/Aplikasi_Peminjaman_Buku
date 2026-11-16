<?php

require_once 'm_denda.php';

// Hanya admin yang boleh mengakses
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'admin') {
    $kembali = $_SESSION['last_page_siswa'] ?? '/Aplikasi_Peminjaman_Buku/siswa/dashboardSiswa';
    if (!isset($_SESSION['id_user'])) {
        $kembali = '/Aplikasi_Peminjaman_Buku/login';
    }
    header("Location: " . $kembali);
    exit;
}

$_SESSION['last_page_admin'] = $_SERVER['REQUEST_URI'];

/**
 * Controller untuk data denda.
 */
class DendaController
{
    private $denda;

    /**
     * Membuat objek model denda.
     */
    public function __construct()
    {
        $this->denda = new Denda();
    }


    // SEMUA DENDA

    /**
     * Mengambil semua data denda.
     *
     * @return array Daftar denda
     */
    public function index()
    {
        return $this->denda->getAll();
    }


    // DETAIL DENDA

    /**
     * Mengambil satu denda berdasarkan ID.
     *
     * @param int $id_denda ID denda
     * @return array|null Data denda, atau null jika tidak ada
     */
    public function detail($id_denda)
    {
        return $this->denda->getById($id_denda);
    }


    // DENDA BERDASARKAN DETAIL PEMINJAMAN

    /**
     * Mengambil denda milik satu detail peminjaman.
     *
     * @param int $id_detail ID detail peminjaman
     * @return array Daftar denda
     */
    public function berdasarkanDetail($id_detail)
    {
        return $this->denda->getByDetail($id_detail);
    }


    // TAMBAH DENDA

    /**
     * Menyimpan denda baru.
     *
     * @param int $id_detail ID detail peminjaman
     * @param string $jenis_denda Jenis denda
     * @param int $jumlah_hari_terlambat Jumlah hari terlambat
     * @param int $jumlah_denda Nominal denda (rupiah)
     * @param string $tanggal_denda Tanggal denda
     * @param string $status_pembayaran Status pembayaran
     * @param string $keterangan Keterangan tambahan
     * @return bool
     */
    public function tambah(
        $id_detail,
        $jenis_denda,
        $jumlah_hari_terlambat,
        $jumlah_denda,
        $tanggal_denda,
        $status_pembayaran,
        $keterangan
    ) {
        $this->denda->id_detail = $id_detail;
        $this->denda->jenis_denda = $jenis_denda;
        $this->denda->jumlah_hari_terlambat =
            $jumlah_hari_terlambat;

        $this->denda->jumlah_denda =
            $jumlah_denda;

        $this->denda->tanggal_denda =
            $tanggal_denda;

        $this->denda->status_pembayaran =
            $status_pembayaran;

        $this->denda->keterangan =
            $keterangan;

        return $this->denda->insert();
    }


    // UPDATE DENDA

    /**
     * Mengubah data denda.
     *
     * @param int $id_denda ID denda
     * @param string $jenis_denda Jenis denda
     * @param int $jumlah_hari_terlambat Jumlah hari terlambat
     * @param int $jumlah_denda Nominal denda (rupiah)
     * @param string $tanggal_denda Tanggal denda
     * @param string $status_pembayaran Status pembayaran
     * @param string $keterangan Keterangan tambahan
     * @return bool
     */
    public function update(
        $id_denda,
        $jenis_denda,
        $jumlah_hari_terlambat,
        $jumlah_denda,
        $tanggal_denda,
        $status_pembayaran,
        $keterangan
    ) {
        $this->denda->id_denda = $id_denda;
        $this->denda->jenis_denda = $jenis_denda;

        $this->denda->jumlah_hari_terlambat =
            $jumlah_hari_terlambat;

        $this->denda->jumlah_denda =
            $jumlah_denda;

        $this->denda->tanggal_denda =
            $tanggal_denda;

        $this->denda->status_pembayaran =
            $status_pembayaran;

        $this->denda->keterangan =
            $keterangan;

        return $this->denda->update();
    }


    // BAYAR DENDA

    /**
     * Menandai denda sebagai sudah dibayar.
     *
     * @param int $id_denda ID denda
     * @return bool
     */
    public function bayar($id_denda)
    {
        return $this->denda->updateStatusPembayaran(
            $id_denda,
            'Sudah Dibayar'
        );
    }


    // HAPUS

    /**
     * Menghapus denda.
     *
     * @param int $id_denda ID denda
     * @return bool
     */
    public function hapus($id_denda)
    {
        return $this->denda->delete($id_denda);
    }
}