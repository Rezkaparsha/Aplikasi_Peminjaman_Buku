<?php

// =====================================================================
// FILE   : MODEL/m_denda.php
// FUNGSI : MODEL tabel `denda` (denda keterlambatan / kerusakan / kehilangan).
// CATATAN: class ini tidak dipakai di bagian lain proyek (hanya oleh c_denda.php
//          yang juga belum tersambung). Penyimpanan denda saat pengembalian
//          dilakukan langsung oleh M_Peminjaman::kembalikanBuku().
// =====================================================================
require_once 'm_koneksi.php';

// Class Denda; properti public di bawah = kolom tabel denda.
class Denda
{
    // Koneksi database.
    private $koneksi;

    // Penampung data satu denda.
    public $id_denda;
    public $id_detail;
    public $jenis_denda;
    public $jumlah_hari_terlambat;
    public $jumlah_denda;
    public $tanggal_denda;
    public $status_pembayaran;
    public $keterangan;

    // Constructor: buka koneksi.
    public function __construct()
    {
        $db = new Koneksi();
        $this->koneksi = $db->getKoneksi();
    }

    // MENAMPILKAN SEMUA DENDA
    // getAll(): semua denda + judul buku + nama siswa (INNER JOIN 4 tabel: denda -> detail_peminjaman -> buku, peminjaman -> users).
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
    // getById(): satu denda.
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
    // getByDetail(): semua denda milik satu baris detail peminjaman.
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
    // insert(): tambah denda dari properti objek.
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
            // Tipe: i id_detail, s jenis, i hari, i jumlah, s tanggal, s status, s keterangan.
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
    // update(): ubah data denda berdasarkan id_denda.
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
            // Enam kolom + id_denda (i) untuk WHERE.
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
    // updateStatusPembayaran(): hanya mengubah status pembayaran (mis. jadi 'Sudah Dibayar').
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
    // delete(): hapus satu denda.
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