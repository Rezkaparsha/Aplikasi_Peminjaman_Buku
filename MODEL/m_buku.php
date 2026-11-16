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
     * Mencari buku berdasarkan judul dengan filter kategori, penulis, dan penerbit.
     *
     * @param string $keyword Kata kunci pencarian
     * @param int $id_kategori ID kategori
     * @param int $id_penulis ID penulis
     * @param int $id_penerbit ID penerbit
     * @return array Daftar buku yang sesuai
     */
    public function cariDanFilterBuku($keyword = '', $id_kategori = '', $id_penulis = '', $id_penerbit = '')
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

        // Filter Kata Kunci (Judul Buku)
        if (!empty($keyword)) {
            $sql .= " AND b.judul_buku LIKE ?";
            $params[] = "%" . $keyword . "%";
            $types .= "s";
        }

        // Filter Kategori
        if (!empty($id_kategori)) {
            $sql .= " AND b.id_kategori = ?";
            $params[] = (int)$id_kategori;
            $types .= "i";
        }

        // Filter Penerbit
        if (!empty($id_penerbit)) {
            $sql .= " AND b.id_penerbit = ?";
            $params[] = (int)$id_penerbit;
            $types .= "i";
        }

        $sql .= " GROUP BY b.id_buku ORDER BY b.judul_buku ASC";

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
            // Filter Penulis (karena menggunakan relasi tabel perantara/many-to-many)
            if (!empty($id_penulis)) {
                // Cek apakah id_penulis ini ada di buku tersebut
                $cekPenulis = $this->koneksi->prepare("SELECT 1 FROM buku_penulis WHERE id_buku = ? AND id_penulis = ?");
                $cekPenulis->bind_param("ii", $row['id_buku'], $id_penulis);
                $cekPenulis->execute();
                if ($cekPenulis->get_result()->num_rows === 0) {
                    $cekPenulis->close();
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
