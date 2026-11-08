<?php

// =====================================================================
// FILE   : CONTROLLER/c_denda.php
// FUNGSI : Controller untuk data DENDA (lihat, tambah, ubah, bayar, hapus).
// CATATAN BELAJAR (dari pemeriksaan seluruh proyek):
//  - File ini TIDAK punya router/switch dan tidak dipanggil dari .htaccess maupun file lain.
//  - Tidak ada session_start() dan require_once memakai path relatif 'm_denda.php',
//    padahal m_denda.php berada di folder MODEL (bukan CONTROLLER).
//  Jadi file ini sebaiknya dianggap kerangka fitur denda yang belum tersambung.
//  Denda yang tampil/tersimpan di aplikasi dihitung & disimpan oleh
//  M_Peminjaman::kembalikanBuku() saat pengembalian diproses.
// =====================================================================
// Memuat model Denda.
require_once 'm_denda.php';

// Gerbang admin (butuh session aktif yang tidak dimulai di file ini).
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'admin') {
    $kembali = $_SESSION['last_page_siswa'] ?? '/Aplikasi_Peminjaman_Buku/siswa/dashboardSiswa';
    if (!isset($_SESSION['id_user'])) {
        $kembali = '/Aplikasi_Peminjaman_Buku/login';
    }
    header("Location: " . $kembali);
    exit;
}

// Catat halaman admin terakhir.
$_SESSION['last_page_admin'] = $_SERVER['REQUEST_URI'];

// Class pembungkus fungsi model Denda.
class DendaController
{
    // Objek model Denda.
    private $denda;

    public function __construct()
    {
        $this->denda = new Denda();
    }


    // SEMUA DENDA

    // index(): semua denda (join ke buku dan nama siswa).
    public function index()
    {
        return $this->denda->getAll();
    }


    // DETAIL DENDA

    // detail(): satu denda berdasarkan id_denda.
    public function detail($id_denda)
    {
        return $this->denda->getById($id_denda);
    }


    // DENDA BERDASARKAN DETAIL PEMINJAMAN

    // berdasarkanDetail(): denda milik satu baris detail peminjaman.
    public function berdasarkanDetail($id_detail)
    {
        return $this->denda->getByDetail($id_detail);
    }


    // TAMBAH DENDA

    // tambah(): isi properti model lalu insert().
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

    // update(): ubah data denda berdasarkan id_denda.
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

    // bayar(): menandai denda lunas dengan mengisi status_pembayaran = 'Sudah Dibayar'.
    public function bayar($id_denda)
    {
        return $this->denda->updateStatusPembayaran(
            $id_denda,
            'Sudah Dibayar'
        );
    }


    // HAPUS

    // hapus(): menghapus satu denda.
    public function hapus($id_denda)
    {
        return $this->denda->delete($id_denda);
    }
}