<?php

require_once __DIR__ . "/m_koneksi.php";

/**
 * Model tabel buku.
 */
class Buku
{
    private $koneksi;

    public $id_buku;
    public $id_kategori;
    public $id_penerbit;
    public $judul_buku;
    public $tahun_terbit;
    public $harga_buku;
    public $stok;
    public $cover;

    /**
     * Membuka koneksi database.
     */
    public function __construct()
    {
        $db = new Koneksi();
        $this->koneksi = $db->getKoneksi();
    }


    // GET SEMUA BUKU

    /**
     * Mengambil semua buku beserta kategori, penerbit, dan penulis.
     *
     * @return array Daftar buku
     */
    public function getAllBuku()
    {
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

        $result = $this->koneksi->query($query);

        $data = [];

        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
        }

        return $data;
    }


    // GET BUKU BERDASARKAN ID

    /**
     * Mengambil satu buku berdasarkan ID.
     *
     * @param int $id_buku ID buku
     * @return array|null Data buku, atau null jika tidak ada
     */
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

        $id_buku = (int) $id_buku;

        $stmt->bind_param("i", $id_buku);
        $stmt->execute();

        $result = $stmt->get_result();

        $data = $result->fetch_assoc();

        $stmt->close();

        return $data ?: null;
    }


    // TAMBAH BUKU

    /**
     * Menyimpan buku baru dari properti objek.
     *
     * @return bool
     */
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
            "iisiiis",
            $this->id_kategori,
            $this->id_penerbit,
            $this->judul_buku,
            $this->tahun_terbit,
            $this->harga_buku,
            $this->stok,
            $this->cover
        );

        $hasil = $stmt->execute();

        if ($hasil) {
            // Ambil ID data yang baru disimpan
            $this->id_buku = $stmt->insert_id;
        }

        $stmt->close();

        return $hasil;
    }


    // UPDATE BUKU

    /**
     * Mengubah data buku dari properti objek.
     *
     * @return bool
     */
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

    /**
     * Menghapus buku; gagal jika buku sudah pernah dipinjam.
     *
     * @param int $id_buku ID buku
     * @return bool
     */
    public function delete($id_buku)
    {
        $stmt = $this->koneksi->prepare("
            DELETE FROM buku
            WHERE id_buku = ?
        ");

        if (!$stmt) {
            return false;
        }

        $id_buku = (int) $id_buku;

        $stmt->bind_param("i", $id_buku);

        $hasil = $stmt->execute();

        $stmt->close();

        return $hasil;
    }

    /**
     * Mencari buku berdasarkan judul dengan filter kategori, penulis, penerbit, dan kondisi stok.
     *
     * @param string $keyword  Kata kunci judul buku
     * @param string $kategori Nama kategori (pencarian sebagian)
     * @param string $penulis  Nama penulis (pencarian sebagian)
     * @param string $penerbit Nama penerbit (pencarian sebagian)
     * @param string $stok     Filter stok: habis, satu, menipis, atau tersedia (kosong = semua)
     * @return array Daftar buku yang sesuai
     */
    public function cariDanFilterBuku($keyword = '', $kategori = '', $penulis = '', $penerbit = '', $stok = '')
    {
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
        $params = [];
        $types = "";

        // Filter judul buku
        if ($keyword !== '') {
            $sql .= " AND b.judul_buku LIKE ?";
            $params[] = "%" . $keyword . "%";
            $types .= "s";
        }

        // Filter kategori berdasarkan nama
        if ($kategori !== '') {
            $sql .= " AND k.nama_kategori LIKE ?";
            $params[] = "%" . $kategori . "%";
            $types .= "s";
        }

        // Filter penerbit berdasarkan nama
        if ($penerbit !== '') {
            $sql .= " AND p.nama_penerbit LIKE ?";
            $params[] = "%" . $penerbit . "%";
            $types .= "s";
        }

        // Filter penulis berdasarkan nama. EXISTS dipakai agar daftar penulis
        // pada hasil tetap lengkap (tidak ikut tersaring)
        if ($penulis !== '') {
            $sql .= " AND EXISTS (
                SELECT 1 FROM buku_penulis bp2
                JOIN penulis pn2 ON bp2.id_penulis = pn2.id_penulis
                WHERE bp2.id_buku = b.id_buku AND pn2.nama_penulis LIKE ?
            )";
            $params[] = "%" . $penulis . "%";
            $types .= "s";
        }

        // Filter kondisi stok (nilainya dibatasi daftar tetap, bukan input bebas)
        $batasMenipis = 5;
        $urutan = "b.judul_buku ASC";
        switch ($stok) {
            case 'habis':
                $sql .= " AND b.stok = 0";
                break;
            case 'satu':
                $sql .= " AND b.stok = 1";
                break;
            case 'menipis':
                $sql .= " AND b.stok BETWEEN 1 AND " . (int)$batasMenipis;
                $urutan = "b.stok ASC, b.judul_buku ASC";
                break;
            case 'tersedia':
                $sql .= " AND b.stok > 0";
                break;
        }

        $sql .= " GROUP BY b.id_buku ORDER BY " . $urutan;

        $stmt = $this->koneksi->prepare($sql);
        if (!$stmt) {
            return [];
        }

        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();
        $result = $stmt->get_result();

        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }

        $stmt->close();
        return $data;
    }
}
