<?php

// =====================================================================
// FILE   : MODEL/m_histori_transaksi.php
// FUNGSI : MODEL tabel `histori_transaksi` (arsip transaksi yang selesai).
//          Tabel ini menyimpan SALINAN data (nama siswa, judul, harga, denda)
//          sehingga histori tetap utuh walaupun data asli berubah.
// CATATAN: yang memakai class ini hanya c_histori.php (getAll). Pengisian
//          histori saat pengembalian dilakukan di M_Peminjaman::kembalikanBuku().
// =====================================================================
require_once 'm_koneksi.php';

// Class HistoriTransaksi.
class HistoriTransaksi
{
    // Koneksi database.
    private $koneksi;

    // Penampung data satu baris histori (kolom sama dengan tabel).
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

    // Constructor: buka koneksi.
    public function __construct()
    {
        $db = new Koneksi();
        $this->koneksi = $db->getKoneksi();
    }


    // MENAMPILKAN SEMUA HISTORI

    // getAll(): semua histori, terbaru di atas (id_histori DESC).
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

    // getById(): satu histori.
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

    // getByUser(): histori satu siswa berdasarkan NIS/NIP (string), terbaru dulu.
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

    // getByPeminjaman(): histori satu transaksi peminjaman.
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

    // insert(): menambah satu baris histori (13 kolom).
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
            // Urutan tipe sesuai 13 kolom: i i s s i s i s s s s i s.
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

    // delete(): menghapus satu histori.
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

    // search(): cari histori berdasarkan NIS/NIP, nama siswa, atau judul buku (LIKE).
    public function search($keyword)
    {
        // Bungkus % di kiri-kanan agar LIKE mencari 'mengandung kata'.
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