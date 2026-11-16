<?php

// =====================================================================
// FILE   : MODEL/m_buku.php
// FUNGSI : MODEL tabel `buku`. Berisi query untuk daftar, detail, tambah,
//          ubah, hapus, serta pencarian + filter buku (katalog siswa).
// =====================================================================
// Memuat koneksi database.
require_once __DIR__ . "/m_koneksi.php";

// Class Buku. Properti public di bawah adalah penampung data satu buku (nama sama dengan kolom tabel).
class Buku
{
    // Koneksi database (private).
    private $koneksi;

    // Properti data buku; diisi controller sebelum memanggil insert()/update().
    public $id_buku;
    public $id_kategori;
    public $id_penerbit;
    public $judul_buku;
    public $tahun_terbit;
    public $harga_buku;
    public $stok;
    public $cover;

    // Constructor: buka koneksi database.
    public function __construct()
    {
        $db = new Koneksi();
        $this->koneksi = $db->getKoneksi();
    }


    // GET SEMUA BUKU

    // getAllBuku(): semua buku untuk halaman admin, lengkap dengan nama kategori, nama penerbit,
    // dan daftar penulis (digabung dalam satu kolom).
    public function getAllBuku()
    {
        // JOIN: LEFT JOIN supaya buku tetap tampil walau kategori/penerbit/penulisnya kosong.
        // GROUP_CONCAT menggabungkan banyak penulis menjadi satu teks dipisah koma.
        // COALESCE(..., '-') = jika tidak ada penulis, tampilkan tanda '-'.
        // GROUP BY wajib karena memakai GROUP_CONCAT. Urutan: id terbaru di atas.
        $query = "
            SELECT
                b.id_buku,
                b.id_kategori,
                b.id_penerbit,
                b.judul_buku,
                b.tahun_terbit,
                b.harga_buku,
                b.stok,
                b.cover,

                k.nama_kategori,
                p.nama_penerbit,

                COALESCE(
                    GROUP_CONCAT(
                        DISTINCT pen.nama_penulis
                        ORDER BY pen.nama_penulis ASC
                        SEPARATOR ', '
                    ),
                    '-'
                ) AS penulis

            FROM buku b

            LEFT JOIN kategori k
                ON b.id_kategori = k.id_kategori

            LEFT JOIN penerbit p
                ON b.id_penerbit = p.id_penerbit

            LEFT JOIN buku_penulis bp
                ON b.id_buku = bp.id_buku

            LEFT JOIN penulis pen
                ON bp.id_penulis = pen.id_penulis

            GROUP BY
                b.id_buku,
                b.id_kategori,
                b.id_penerbit,
                b.judul_buku,
                b.tahun_terbit,
                b.harga_buku,
                b.stok,
                b.cover,
                k.nama_kategori,
                p.nama_penerbit

            ORDER BY b.id_buku DESC
        ";

        // Jalankan query (tanpa input pengguna sehingga aman).
        $result = $this->koneksi->query($query);

        $data = [];

        // Cek query berhasil sebelum dibaca.
        if ($result) {
            // Baca hasil baris per baris.
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
        }

        return $data;
    }


    // GET BUKU BERDASARKAN ID

    // getBukuById($id_buku): satu buku untuk form edit / pengecekan. Mengembalikan null jika tidak ada.
    public function getBukuById($id_buku)
    {
        $stmt = $this->koneksi->prepare("
            SELECT
                id_buku,
                id_kategori,
                id_penerbit,
                judul_buku,
                tahun_terbit,
                harga_buku,
                stok,
                cover
            FROM buku
            WHERE id_buku = ?
        ");

        if (!$stmt) {
            return null;
        }

        // Paksa angka.
        $id_buku = (int) $id_buku;

        // Isi tanda tanya dengan id (i = integer).
        $stmt->bind_param("i", $id_buku);
        $stmt->execute();

        $result = $stmt->get_result();

        $data = $result->fetch_assoc();

        $stmt->close();

        // ?: = jika kosong, kembalikan null.
        return $data ?: null;
    }


    // TAMBAH BUKU

    // insert(): menambah buku baru memakai nilai properti objek. Mengembalikan true/false.
    public function insert()
    {
        $stmt = $this->koneksi->prepare("
            INSERT INTO buku
            (
                id_kategori,
                id_penerbit,
                judul_buku,
                tahun_terbit,
                harga_buku,
                stok,
                cover
            )
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");

        if (!$stmt) {
            return false;
        }

        $stmt->bind_param(
            // Urutan tipe: i kategori, i penerbit, s judul, i tahun, i harga, i stok, s cover.
            "iisiiis",
            $this->id_kategori,
            $this->id_penerbit,
            $this->judul_buku,
            $this->tahun_terbit,
            $this->harga_buku,
            $this->stok,
            $this->cover
        );

        // Jalankan INSERT.
        $hasil = $stmt->execute();

        if ($hasil) {
            // Simpan id otomatis (AUTO_INCREMENT) buku baru ke properti, agar controller bisa memakainya untuk relasi penulis.
            $this->id_buku = $stmt->insert_id;
        }

        $stmt->close();

        return $hasil;
    }


    // UPDATE BUKU

    // update(): mengubah data buku berdasarkan id_buku (WHERE wajib agar hanya satu buku yang berubah).
    public function update()
    {
        $stmt = $this->koneksi->prepare("
            UPDATE buku
            SET
                id_kategori = ?,
                id_penerbit = ?,
                judul_buku = ?,
                tahun_terbit = ?,
                harga_buku = ?,
                stok = ?,
                cover = ?
            WHERE id_buku = ?
        ");

        if (!$stmt) {
            return false;
        }

        $stmt->bind_param(
            // Sama seperti insert + satu 'i' di akhir untuk id_buku pada WHERE.
            "iisiiisi",
            $this->id_kategori,
            $this->id_penerbit,
            $this->judul_buku,
            $this->tahun_terbit,
            $this->harga_buku,
            $this->stok,
            $this->cover,
            $this->id_buku
        );

        $hasil = $stmt->execute();

        $stmt->close();

        return $hasil;
    }


    // HAPUS BUKU

    // delete($id_buku): hapus buku. Gagal (exception/false) jika buku masih terpakai di detail_peminjaman karena foreign key RESTRICT.
    public function delete($id_buku)
    {
        $stmt = $this->koneksi->prepare("
            DELETE FROM buku
            WHERE id_buku = ?
        ");

        if (!$stmt) {
            return false;
        }

        // Paksa angka.
        $id_buku = (int) $id_buku;

        $stmt->bind_param("i", $id_buku);

        $hasil = $stmt->execute();

        $stmt->close();

        return $hasil;
    }

    // cariDanFilterBuku(): pencarian + filter KATALOG SISWA.
    // Parameter opsional: kata kunci judul, kategori, penulis, penerbit.
    // Query disusun BERTAHAP: hanya filter yang diisi yang ditambahkan ke SQL.
    public function cariDanFilterBuku($keyword = '', $id_kategori = '', $id_penulis = '', $id_penerbit = '')
    {
        // Query dasar. 'WHERE 1=1' = trik supaya setiap filter tinggal ditambah dengan ' AND ...'.
        $sql = "
        SELECT 
            b.*, 
            k.nama_kategori, 
            p.nama_penerbit,
            GROUP_CONCAT(pn.nama_penulis SEPARATOR ', ') as daftar_penulis
        FROM buku b
        LEFT JOIN kategori k ON b.id_kategori = k.id_kategori
        LEFT JOIN penerbit p ON b.id_penerbit = p.id_penerbit
        LEFT JOIN buku_penulis bp ON b.id_buku = bp.id_buku
        LEFT JOIN penulis pn ON bp.id_penulis = pn.id_penulis
        WHERE 1=1
    ";

        // Penampung nilai untuk tanda tanya (?).
        $params = [];
        // Penampung kode tipe bind_param (s/i) yang disusun sesuai filter.
        $types = "";

        // Filter Kata Kunci (Judul Buku)
        // Filter judul memakai LIKE '%kata%' (mengandung kata).
        if (!empty($keyword)) {
            $sql .= " AND b.judul_buku LIKE ?";
            $params[] = "%" . $keyword . "%";
            $types .= "s";
        }

        // Filter Kategori
        // Filter kategori.
        if (!empty($id_kategori)) {
            $sql .= " AND b.id_kategori = ?";
            $params[] = (int)$id_kategori;
            $types .= "i";
        }

        // Filter Penerbit
        // Filter penerbit.
        if (!empty($id_penerbit)) {
            $sql .= " AND b.id_penerbit = ?";
            $params[] = (int)$id_penerbit;
            $types .= "i";
        }

        // Kelompokkan per buku (karena GROUP_CONCAT penulis) dan urutkan judul A-Z.
        $sql .= " GROUP BY b.id_buku ORDER BY b.judul_buku ASC";

        // Siapkan query hasil rakitan.
        $stmt = $this->koneksi->prepare($sql);
        if (!$stmt) {
            return [];
        }

        if (!empty($params)) {
            // Isi semua tanda tanya sekaligus. '...' (spread) membuka array jadi argumen terpisah.
            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();
        $result = $stmt->get_result();

        $data = [];
        while ($row = $result->fetch_assoc()) {
            // Filter Penulis (karena menggunakan relasi tabel perantara/many-to-many)
            // Filter penulis dikerjakan di PHP karena relasinya banyak-ke-banyak (buku_penulis).
            if (!empty($id_penulis)) {
                // Cek apakah id_penulis ini ada di buku tersebut
                $cekPenulis = $this->koneksi->prepare("SELECT 1 FROM buku_penulis WHERE id_buku = ? AND id_penulis = ?");
                $cekPenulis->bind_param("ii", $row['id_buku'], $id_penulis);
                $cekPenulis->execute();
                if ($cekPenulis->get_result()->num_rows === 0) {
                    $cekPenulis->close();
                    // continue = lewati baris ini (jangan masuk ke hasil), lanjut ke baris berikutnya.
                    continue; // Lewati jika penulis tidak cocok
                }
                $cekPenulis->close();
            }
            $data[] = $row;
        }

        $stmt->close();
        return $data;
    }
}
