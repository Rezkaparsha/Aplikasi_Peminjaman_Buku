<?php

// =====================================================================
// FILE   : MODEL/m_detail_peminjaman.php
// FUNGSI : MODEL tabel `detail_peminjaman` (baris buku dalam satu peminjaman).
// CATATAN: class ini tidak dipakai di bagian lain proyek. Operasi detail yang
//          berjalan di aplikasi dilakukan langsung di M_Peminjaman (tambah,
//          setujui, kembalikan). Tetap berguna dipelajari sebagai model tabel detail.
// =====================================================================
require_once 'm_koneksi.php';

// Class DetailPeminjaman.
class DetailPeminjaman
{
    // Koneksi database.
    private $koneksi;

    // Penampung data satu baris detail.
    public $id_detail;
    public $id_peminjaman;
    public $id_buku;
    public $tanggal_pengembalian;
    public $tanggal_dikembalikan;
    public $status;

    // Constructor: buka koneksi.
    public function __construct()
    {
        $db = new Koneksi();
        $this->koneksi = $db->getKoneksi();
    }

    // MENAMPILKAN SEMUA DETAIL PEMINJAMAN
    // getAll(): semua detail + judul buku.
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
    // getById(): satu detail + judul buku.
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
    // getByPeminjaman(): semua buku dalam satu peminjaman, termasuk harga buku.
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
    // insert(): tambah baris detail.
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
            // i id_peminjaman, i id_buku, s tgl_pengembalian, s tgl_dikembalikan, s status.
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
    // update(): ubah detail berdasarkan id_detail.
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
            // Empat kolom + id_detail (i) untuk WHERE.
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
    // kembalikanBuku(): menandai satu detail 'Dikembalikan' + isi tanggal dikembalikan (versi sederhana; versi lengkap dengan stok & denda ada di M_Peminjaman).
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
    // delete(): hapus satu detail.
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
    // cekBukuSedangDipinjam(): menghitung berapa detail berstatus 'Dipinjam' untuk satu buku (>0 = sedang dipinjam).
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