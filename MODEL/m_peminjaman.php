<?php

require_once __DIR__ . "/m_koneksi.php";

/**
 * Model transaksi peminjaman (peminjaman, detail peminjaman, denda, dan histori).
 */
class M_Peminjaman
{
    private $koneksi;

    /**
     * Membuka koneksi database.
     */
    public function __construct()
    {
        $db = new Koneksi();
        $this->koneksi = $db->getKoneksi();
        $this->koneksi->set_charset("utf8mb4");
    }


    // 1. TAMBAH PEMINJAMAN

    /**
     * Membuat pengajuan peminjaman baru. Stok hanya dicek, belum dikurangi.
     *
     * @param int $id_user ID user
     * @param array $daftarBuku Daftar buku (id_buku dan jumlah)
     * @return array Hasil proses (status, pesan, id_peminjaman)
     */
    public function tambahPeminjaman(
        $id_user,
        $daftarBuku
    ) {

        $id_user = (int)$id_user;

        if ($id_user <= 0) {

            return [
                'status' => false,
                'pesan' => 'User tidak ditemukan.'
            ];
        }


        if (
            empty($daftarBuku) ||
            !is_array($daftarBuku)
        ) {

            return [
                'status' => false,
                'pesan' => 'Belum ada buku yang dipilih.'
            ];
        }


        $this->koneksi
            // Mulai transaksi database
            ->begin_transaction();


        try {

            $bukuGabungan = [];


            // -----------------------------------------
            // GABUNGKAN BUKU YANG SAMA
            // -----------------------------------------

            foreach ($daftarBuku as $buku) {

                if (
                    !isset(
                        $buku['id_buku'],
                        $buku['jumlah']
                    )
                ) {

                    throw new Exception(
                        'Data buku tidak lengkap.'
                    );
                }


                $id_buku =
                    (int)$buku['id_buku'];

                $jumlah =
                    (int)$buku['jumlah'];


                if ($id_buku <= 0) {

                    throw new Exception(
                        'ID buku tidak valid.'
                    );
                }


                if ($jumlah <= 0) {

                    throw new Exception(
                        'Jumlah buku minimal 1.'
                    );
                }


                if (
                    isset(
                        $bukuGabungan[$id_buku]
                    )
                ) {

                    $bukuGabungan[$id_buku]
                        += $jumlah;
                } else {

                    $bukuGabungan[$id_buku]
                        = $jumlah;
                }
            }


            // -----------------------------------------
            // CEK STOK
            // -----------------------------------------

            foreach (
                $bukuGabungan
                as $id_buku => $jumlah
            ) {

                $stmt =
                    $this->koneksi->prepare("
                        SELECT
                            id_buku,
                            judul_buku,
                            stok
                        FROM buku
                        WHERE id_buku = ?
                        FOR UPDATE
                    ");

                if (!$stmt) {

                    throw new Exception(
                        'Gagal memeriksa buku.'
                    );
                }


                $stmt->bind_param(
                    "i",
                    $id_buku
                );

                $stmt->execute();


                $dataBuku =
                    $stmt
                    ->get_result()
                    ->fetch_assoc();


                $stmt->close();


                if (!$dataBuku) {

                    throw new Exception(
                        "Buku dengan ID {$id_buku} tidak ditemukan."
                    );
                }


                if (
                    (int)$dataBuku['stok']
                    < $jumlah
                ) {

                    throw new Exception(
                        'Stok buku "'
                            . $dataBuku['judul_buku']
                            . '" tidak mencukupi. Stok tersedia: '
                            . $dataBuku['stok']
                            . '.'
                    );
                }
            }


            // -----------------------------------------
            // BUAT PEMINJAMAN
            // -----------------------------------------

            $tanggal_pinjam =
                date('Y-m-d');

            $status =
                'Diajukan';


            $stmt =
                $this->koneksi->prepare("
                    INSERT INTO peminjaman
                    (
                        id_user,
                        tanggal_pinjam,
                        status
                    )
                    VALUES (?, ?, ?)
                ");


            if (!$stmt) {

                throw new Exception(
                    'Gagal menyiapkan peminjaman.'
                );
            }


            $stmt->bind_param(
                "iss",
                $id_user,
                $tanggal_pinjam,
                $status
            );


            if (!$stmt->execute()) {

                throw new Exception(
                    'Gagal menyimpan peminjaman.'
                );
            }


            $id_peminjaman =
                // Ambil ID data yang baru disimpan
                $this->koneksi->insert_id;


            $stmt->close();


            // -----------------------------------------
            // DETAIL PEMINJAMAN
            // -----------------------------------------

            $statusDetail =
                'Diajukan';


            $stmtDetail =
                $this->koneksi->prepare("
                    INSERT INTO detail_peminjaman
                    (
                        id_peminjaman,
                        id_buku,
                        jumlah_buku,
                        tanggal_pengembalian,
                        tanggal_dikembalikan,
                        status
                    )
                    VALUES (?, ?, ?, NULL, NULL, ?)
                ");


            if (!$stmtDetail) {

                throw new Exception(
                    'Gagal menyiapkan detail peminjaman.'
                );
            }


            foreach (
                $bukuGabungan
                as $id_buku => $jumlah
            ) {

                $stmtDetail->bind_param(
                    "iiis",
                    $id_peminjaman,
                    $id_buku,
                    $jumlah,
                    $statusDetail
                );


                if (!$stmtDetail->execute()) {

                    throw new Exception(
                        'Gagal menyimpan detail buku.'
                    );
                }
            }


            $stmtDetail->close();


            $this->koneksi
                // Simpan semua perubahan
                ->commit();


            return [
                'status' => true,
                'pesan' =>
                'Pengajuan peminjaman berhasil dibuat.',
                'id_peminjaman' =>
                $id_peminjaman
            ];
        } catch (Exception $e) {

            $this->koneksi
                // Batalkan semua perubahan
                ->rollback();


            return [
                'status' => false,
                'pesan' => $e->getMessage()
            ];
        }
    }


    // 2. PEMINJAMAN MILIK USER

    /**
     * Mengambil daftar peminjaman milik satu user.
     *
     * @param int $id_user ID user
     * @return array Daftar peminjaman
     */
    public function getPeminjamanByUser($id_user)
    {
        $sql = "
            SELECT
                p.id_peminjaman,
                p.id_user,
                p.tanggal_pinjam,
                p.status,
                u.nis_nip AS nis,
                u.nama_lengkap,

                COUNT(dp.id_detail)
                    AS jumlah_jenis_buku,

                COALESCE(
                    SUM(dp.jumlah_buku),
                    0
                ) AS total_buku

            FROM peminjaman p

            INNER JOIN users u
                ON p.id_user = u.id_user

            LEFT JOIN detail_peminjaman dp
                ON p.id_peminjaman =
                   dp.id_peminjaman

            WHERE p.id_user = ?

            GROUP BY
                p.id_peminjaman,
                p.id_user,
                p.tanggal_pinjam,
                p.status,
                u.nis_nip,
                u.nama_lengkap

            ORDER BY
                p.id_peminjaman DESC
        ";


        $stmt =
            $this->koneksi
            ->prepare($sql);


        if (!$stmt) {
            return [];
        }


        $id_user =
            (int)$id_user;


        $stmt->bind_param(
            "i",
            $id_user
        );

        $stmt->execute();


        $result =
            $stmt->get_result();


        $data = [];


        while (
            $row =
            $result->fetch_assoc()
        ) {

            $data[] = $row;
        }


        $stmt->close();


        return $data;
    }


    // 3. SEMUA PEMINJAMAN

    /**
     * Mengambil semua peminjaman beserta data siswa.
     *
     * @return array Daftar peminjaman
     */
    public function getSemuaPeminjaman()
    {
        $sql = "
            SELECT
                p.id_peminjaman,
                p.id_user,
                p.tanggal_pinjam,
                p.status,
                u.nis_nip AS nis,
                u.nama_lengkap,

                COUNT(dp.id_detail)
                    AS jumlah_jenis_buku,

                COALESCE(
                    SUM(dp.jumlah_buku),
                    0
                ) AS total_buku

            FROM peminjaman p

            INNER JOIN users u
                ON p.id_user = u.id_user

            LEFT JOIN detail_peminjaman dp
                ON p.id_peminjaman =
                   dp.id_peminjaman

            GROUP BY
                p.id_peminjaman,
                p.id_user,
                p.tanggal_pinjam,
                p.status,
                u.nis_nip,
                u.nama_lengkap

            ORDER BY
                p.id_peminjaman DESC
        ";


        $result =
            $this->koneksi
            ->query($sql);


        if (!$result) {
            return [];
        }


        $data = [];


        while (
            $row =
            $result->fetch_assoc()
        ) {

            $data[] = $row;
        }


        return $data;
    }


    // 4. DETAIL PEMINJAMAN

    /**
     * Mengambil daftar buku dalam satu peminjaman.
     *
     * @param int $id_peminjaman ID peminjaman
     * @return array Daftar detail peminjaman
     */
    public function getDetailPeminjaman(
        $id_peminjaman
    ) {

        $sql = "
            SELECT
                p.id_peminjaman,
                p.id_user,
                p.tanggal_pinjam,
                p.status AS status_peminjaman,

                u.nis_nip AS nis,
                u.nama_lengkap,

                dp.id_detail,
                dp.id_buku,
                dp.jumlah_buku,
                dp.tanggal_pengembalian,
                dp.tanggal_dikembalikan,
                dp.status AS status_detail,

                b.judul_buku,
                b.harga_buku,
                b.stok,
                b.cover

            FROM peminjaman p

            INNER JOIN users u
                ON p.id_user = u.id_user

            INNER JOIN detail_peminjaman dp
                ON p.id_peminjaman =
                   dp.id_peminjaman

            INNER JOIN buku b
                ON dp.id_buku =
                   b.id_buku

            WHERE p.id_peminjaman = ?

            ORDER BY
                dp.id_detail ASC
        ";


        $stmt =
            $this->koneksi
            ->prepare($sql);


        if (!$stmt) {
            return [];
        }


        $id_peminjaman =
            (int)$id_peminjaman;


        $stmt->bind_param(
            "i",
            $id_peminjaman
        );

        $stmt->execute();


        $result =
            $stmt->get_result();


        $data = [];


        while (
            $row =
            $result->fetch_assoc()
        ) {

            $data[] = $row;
        }


        $stmt->close();


        return $data;
    }


    // 5. GET PEMINJAMAN BY ID

    /**
     * Mengambil data satu peminjaman.
     *
     * @param int $id_peminjaman ID peminjaman
     * @return array|null Data peminjaman, atau null jika tidak ada
     */
    public function getPeminjamanById($id_peminjaman)
    {

        $stmt =
            $this->koneksi
            ->prepare("
                    SELECT
                        p.id_peminjaman,
                        p.id_user,
                        p.tanggal_pinjam,
                        p.status,
                        p.alasan_penolakan,
                        u.nis_nip AS nis,
                        u.nama_lengkap

                    FROM peminjaman p

                    INNER JOIN users u
                        ON p.id_user =
                           u.id_user

                    WHERE p.id_peminjaman = ?
                ");


        if (!$stmt) {
            return null;
        }


        $id_peminjaman =
            (int)$id_peminjaman;


        $stmt->bind_param(
            "i",
            $id_peminjaman
        );

        $stmt->execute();


        $data =
            $stmt
            ->get_result()
            ->fetch_assoc();


        $stmt->close();


        return $data ?: null;
    }


    // 6. TOLAK PEMINJAMAN (DENGAN ALASAN)

    /**
     * Menolak pengajuan peminjaman beserta alasannya.
     *
     * @param int $id_peminjaman ID peminjaman
     * @param string $alasan_penolakan Alasan penolakan
     * @return array Hasil proses (status, pesan)
     */
    public function tolakPeminjaman($id_peminjaman, $alasan_penolakan)
    {
        $stmt = $this->koneksi->prepare("
            UPDATE peminjaman 
            SET status = 'Ditolak', alasan_penolakan = ? 
            WHERE id_peminjaman = ? AND status = 'Diajukan'
        ");

        if (!$stmt) {
            return [
                'status' => false,
                'pesan' => 'Gagal menyiapkan penolakan.'
            ];
        }

        $id_peminjaman = (int)$id_peminjaman;
        $stmt->bind_param("si", $alasan_penolakan, $id_peminjaman);

        if (!$stmt->execute()) {
            $stmt->close();
            return [
                'status' => false,
                'pesan' => 'Gagal menolak peminjaman.'
            ];
        }

        if ($stmt->affected_rows === 0) {
            $stmt->close();
            return [
                'status' => false,
                'pesan' => 'Peminjaman tidak ditemukan atau sudah diproses.'
            ];
        }

        $stmt->close();
        return [
            'status' => true,
            'pesan' => 'Peminjaman berhasil ditolak beserta alasan.'
        ];
    }


    // 7. SETUJUI PEMINJAMAN

    /**
     * Menyetujui pengajuan: mengurangi stok dan menetapkan batas pengembalian.
     *
     * @param int $id_peminjaman ID peminjaman
     * @param string $tanggal_pengembalian Batas tanggal pengembalian (Y-m-d)
     * @return array Hasil proses (status, pesan)
     */
    public function setujuiPeminjaman(
        $id_peminjaman,
        $tanggal_pengembalian
    ) {

        if (
            empty($tanggal_pengembalian)
        ) {

            return [
                'status' => false,
                'pesan' =>
                'Tanggal pengembalian harus diisi.'
            ];
        }


        $this->koneksi
            // Mulai transaksi database
            ->begin_transaction();


        try {

            // -----------------------------------------
            // CEK PEMINJAMAN
            // -----------------------------------------

            $stmt =
                $this->koneksi->prepare("
                    SELECT
                        id_peminjaman,
                        status

                    FROM peminjaman

                    WHERE id_peminjaman = ?

                    FOR UPDATE
                ");


            if (!$stmt) {

                throw new Exception(
                    'Gagal mengambil peminjaman.'
                );
            }


            $id_peminjaman =
                (int)$id_peminjaman;


            $stmt->bind_param(
                "i",
                $id_peminjaman
            );

            $stmt->execute();


            $peminjaman =
                $stmt
                ->get_result()
                ->fetch_assoc();


            $stmt->close();


            if (!$peminjaman) {

                throw new Exception(
                    'Peminjaman tidak ditemukan.'
                );
            }


            if (
                $peminjaman['status']
                !== 'Diajukan'
            ) {

                throw new Exception(
                    'Peminjaman sudah diproses.'
                );
            }


            // -----------------------------------------
            // AMBIL DETAIL
            // -----------------------------------------

            $stmt =
                $this->koneksi->prepare("
                    SELECT
                        dp.id_detail,
                        dp.id_buku,
                        dp.jumlah_buku,
                        b.judul_buku,
                        b.stok

                    FROM detail_peminjaman dp

                    INNER JOIN buku b
                        ON dp.id_buku =
                           b.id_buku

                    WHERE dp.id_peminjaman = ?

                    FOR UPDATE
                ");


            if (!$stmt) {

                throw new Exception(
                    'Gagal mengambil detail.'
                );
            }


            $stmt->bind_param(
                "i",
                $id_peminjaman
            );

            $stmt->execute();


            $result =
                $stmt->get_result();


            $detail = [];


            while (
                $row =
                $result->fetch_assoc()
            ) {

                $detail[] = $row;
            }


            $stmt->close();


            if (empty($detail)) {

                throw new Exception(
                    'Detail buku tidak ditemukan.'
                );
            }


            // -----------------------------------------
            // CEK STOK
            // -----------------------------------------

            foreach ($detail as $item) {

                if (
                    (int)$item['stok']
                    <
                    (int)$item['jumlah_buku']
                ) {

                    throw new Exception(
                        'Stok buku "'
                            . $item['judul_buku']
                            . '" tidak mencukupi.'
                    );
                }
            }


            // -----------------------------------------
            // KURANGI STOK
            // -----------------------------------------

            $stmtStok =
                $this->koneksi->prepare("
                    UPDATE buku

                    SET stok =
                        stok - ?

                    WHERE id_buku = ?

                    AND stok >= ?
                ");


            if (!$stmtStok) {

                throw new Exception(
                    'Gagal mengubah stok.'
                );
            }


            foreach ($detail as $item) {

                $jumlah =
                    (int)$item['jumlah_buku'];

                $id_buku =
                    (int)$item['id_buku'];


                $stmtStok->bind_param(
                    "iii",
                    $jumlah,
                    $id_buku,
                    $jumlah
                );


                if (!$stmtStok->execute()) {

                    throw new Exception(
                        'Gagal mengurangi stok.'
                    );
                }


                if (
                    $stmtStok->affected_rows
                    === 0
                ) {

                    throw new Exception(
                        'Stok tidak mencukupi.'
                    );
                }
            }


            $stmtStok->close();


            // -----------------------------------------
            // UPDATE DETAIL
            // -----------------------------------------

            $stmt =
                $this->koneksi->prepare("
                    UPDATE detail_peminjaman

                    SET
                        tanggal_pengembalian = ?,
                        status = 'Dipinjam'

                    WHERE id_peminjaman = ?
                ");


            if (!$stmt) {

                throw new Exception(
                    'Gagal memperbarui detail.'
                );
            }


            $stmt->bind_param(
                "si",
                $tanggal_pengembalian,
                $id_peminjaman
            );


            if (!$stmt->execute()) {

                throw new Exception(
                    'Gagal memperbarui detail.'
                );
            }


            $stmt->close();


            // -----------------------------------------
            // UPDATE PEMINJAMAN
            // -----------------------------------------

            $stmt =
                $this->koneksi->prepare("
                    UPDATE peminjaman

                    SET status = 'Dipinjam'

                    WHERE id_peminjaman = ?
                ");


            if (!$stmt) {

                throw new Exception(
                    'Gagal memperbarui status.'
                );
            }


            $stmt->bind_param(
                "i",
                $id_peminjaman
            );


            if (!$stmt->execute()) {

                throw new Exception(
                    'Gagal memperbarui status peminjaman.'
                );
            }


            $stmt->close();


            $this->koneksi
                // Simpan semua perubahan
                ->commit();


            return [
                'status' => true,
                'pesan' =>
                'Peminjaman berhasil disetujui.'
            ];
        } catch (Exception $e) {

            $this->koneksi
                // Batalkan semua perubahan
                ->rollback();


            return [
                'status' => false,
                'pesan' => $e->getMessage()
            ];
        }
    }


    // 8. CEK KEPEMILIKAN

    /**
     * Memeriksa apakah peminjaman milik user tertentu.
     *
     * @param int $id_peminjaman ID peminjaman
     * @param int $id_user ID user
     * @return bool true jika milik user tersebut
     */
    public function cekKepemilikanPeminjaman(
        $id_peminjaman,
        $id_user
    ) {

        $stmt =
            $this->koneksi->prepare("
                SELECT
                    id_peminjaman

                FROM peminjaman

                WHERE id_peminjaman = ?

                AND id_user = ?
            ");


        if (!$stmt) {
            return false;
        }


        $id_peminjaman =
            (int)$id_peminjaman;

        $id_user =
            (int)$id_user;


        $stmt->bind_param(
            "ii",
            $id_peminjaman,
            $id_user
        );


        $stmt->execute();


        $ada =
            $stmt
            ->get_result()
            ->num_rows > 0;


        $stmt->close();


        return $ada;
    }


    // 9. PROSES PENGEMBALIAN BUKU & PERHITUNGAN DENDA (PER BUKU)

    /**
     * Memproses pengembalian: stok, denda keterlambatan dan tambahan, histori, dan status.
     *
     * @param int $id_peminjaman ID peminjaman
     * @param array $kondisi_buku Kondisi tiap buku (Normal/Rusak/Hilang), kunci id_detail
     * @param array $denda_tambahan Denda tambahan per buku, kunci id_detail
     * @return array Hasil proses (status, pesan)
     */
    public function kembalikanBuku($id_peminjaman, $kondisi_buku = [], $denda_tambahan = [])
    {
        $id_peminjaman = (int)$id_peminjaman;
        $tanggal_sekarang = date('Y-m-d');

        // Mulai transaksi database
        $this->koneksi->begin_transaction();

        try {
            $stmtPmj = $this->koneksi->prepare("
                SELECT p.id_peminjaman, p.id_user, p.tanggal_pinjam, p.status, u.nis_nip, u.nama_lengkap
                FROM peminjaman p
                JOIN users u ON p.id_user = u.id_user
                WHERE p.id_peminjaman = ? FOR UPDATE
            ");
            $stmtPmj->bind_param("i", $id_peminjaman);
            $stmtPmj->execute();
            $dataPmj = $stmtPmj->get_result()->fetch_assoc();
            $stmtPmj->close();

            if (!$dataPmj || $dataPmj['status'] !== 'Dipinjam') {
                throw new Exception("Peminjaman tidak ditemukan atau statusnya bukan 'Dipinjam'.");
            }

            $stmtDetail = $this->koneksi->prepare("
                SELECT dp.id_detail, dp.id_buku, dp.jumlah_buku, dp.tanggal_pengembalian, b.judul_buku, b.harga_buku
                FROM detail_peminjaman dp
                JOIN buku b ON dp.id_buku = b.id_buku
                WHERE dp.id_peminjaman = ? FOR UPDATE
            ");
            $stmtDetail->bind_param("i", $id_peminjaman);
            $stmtDetail->execute();
            $resultDetail = $stmtDetail->get_result();

            $details = [];
            while ($row = $resultDetail->fetch_assoc()) {
                $details[] = $row;
            }
            $stmtDetail->close();

            if (empty($details)) throw new Exception("Detail peminjaman tidak ditemukan.");

            $stmtStok = $this->koneksi->prepare("UPDATE buku SET stok = stok + ? WHERE id_buku = ?");

            $stmtHistori = $this->koneksi->prepare("
                INSERT INTO histori_transaksi 
                (id_peminjaman, id_detail, nis_nip, nama_siswa, id_buku, judul_buku, harga_buku, tanggal_pinjam, tanggal_pengembalian, tanggal_dikembalikan, jenis_denda, denda, tanggal_selesai)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            // Tarif denda per hari keterlambatan
            $tarif_denda_per_hari = 1000;

            foreach ($details as $item) {
                $id_detail = $item['id_detail'];

                // Ambil kondisi dan denda khusus untuk BUKU INI dari array yang dikirim form
                $kondisi_item = $kondisi_buku[$id_detail] ?? 'Normal';
                $denda_nominal_item = (int)($denda_tambahan[$id_detail] ?? 0);

                // Jika buku HILANG, stok TIDAK dikembalikan. Jika Normal/Rusak, stok dikembalikan.
                if ($kondisi_item !== 'Hilang') {
                    $stmtStok->bind_param("ii", $item['jumlah_buku'], $item['id_buku']);
                    $stmtStok->execute();
                }

                // Hitung Denda Keterlambatan
                $tgl_kembali_seharusnya = new DateTime($item['tanggal_pengembalian']);
                $tgl_dikembalikan_real = new DateTime($tanggal_sekarang);

                $total_denda = 0;
                $selisih_hari = 0;
                $keterangan_denda = "Tidak Ada";

                if ($tgl_dikembalikan_real > $tgl_kembali_seharusnya) {
                    // Hitung selisih hari keterlambatan
                    $selisih_hari = $tgl_dikembalikan_real->diff($tgl_kembali_seharusnya)->days;
                    $total_denda = $selisih_hari * $tarif_denda_per_hari;
                    $keterangan_denda = "Terlambat " . $selisih_hari . " Hari";
                }

                // Jika ada denda tambahan untuk buku ini (karena rusak/hilang)
                if ($kondisi_item !== 'Normal' && $denda_nominal_item > 0) {
                    $total_denda += $denda_nominal_item;
                    if ($keterangan_denda === "Tidak Ada") {
                        $keterangan_denda = "Buku " . $kondisi_item;
                    } else {
                        $keterangan_denda .= " + Buku " . $kondisi_item;
                    }
                }

                if ($total_denda > 0) {
                    $stmtDenda = $this->koneksi->prepare("
                        INSERT INTO denda (id_detail, jenis_denda, jumlah_hari_terlambat, jumlah_denda, tanggal_denda) VALUES (?, ?, ?, ?, ?)
                    ");
                    $stmtDenda->bind_param("isiis", $id_detail, $keterangan_denda, $selisih_hari, $total_denda, $tanggal_sekarang);
                    $stmtDenda->execute();
                    $stmtDenda->close();
                }

                $stmtHistori->bind_param(
                    "iissisissssis",
                    $id_peminjaman,
                    $id_detail,
                    $dataPmj['nis_nip'],
                    $dataPmj['nama_lengkap'],
                    $item['id_buku'],
                    $item['judul_buku'],
                    $item['harga_buku'],
                    $dataPmj['tanggal_pinjam'],
                    $item['tanggal_pengembalian'],
                    $tanggal_sekarang,
                    $keterangan_denda,
                    $total_denda,
                    $tanggal_sekarang
                );
                $stmtHistori->execute();
            }

            $stmtStok->close();
            $stmtHistori->close();

            $stmtUpdDetail = $this->koneksi->prepare("UPDATE detail_peminjaman SET status = 'Dikembalikan', tanggal_dikembalikan = ? WHERE id_peminjaman = ?");
            $stmtUpdDetail->bind_param("si", $tanggal_sekarang, $id_peminjaman);
            $stmtUpdDetail->execute();
            $stmtUpdDetail->close();

            $stmtUpdPmj = $this->koneksi->prepare("UPDATE peminjaman SET status = 'Dikembalikan' WHERE id_peminjaman = ?");
            $stmtUpdPmj->bind_param("i", $id_peminjaman);
            $stmtUpdPmj->execute();
            $stmtUpdPmj->close();

            // Simpan semua perubahan
            $this->koneksi->commit();

            return ['status' => true, 'pesan' => 'Pengembalian berhasil. Kondisi dan stok buku telah disesuaikan.'];
        } catch (Exception $e) {
            // Batalkan semua perubahan
            $this->koneksi->rollback();
            return ['status' => false, 'pesan' => $e->getMessage()];
        }
    }


    // 10. AMBIL DATA HISTORI TRANSAKSI (ADMIN) DENGAN JOIN KELAS

    /**
     * Mengambil histori transaksi beserta kelas siswa.
     *
     * @return array Daftar histori
     */
    public function getHistoriTransaksi()
    {
        $sql = "
            SELECT 
                h.*, 
                u.kelas 
            FROM histori_transaksi h
            LEFT JOIN users u ON h.nis_nip = u.nis_nip
            ORDER BY h.tanggal_dikembalikan DESC
        ";

        $result = $this->koneksi->query($sql);

        $data = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
        }
        return $data;
    }


    // 11. AMBIL JUMLAH BUKU YANG BELUM DIKEMBALIKAN

    /**
     * Menghitung peminjaman yang masih berstatus Dipinjam.
     *
     * @return int Jumlah peminjaman
     */
    public function getJumlahBelumKembali()
    {
        $query = "SELECT COUNT(*) as total FROM peminjaman WHERE status = 'Dipinjam'";
        $result = mysqli_query($this->koneksi, $query);

        if ($result) {
            $row = mysqli_fetch_assoc($result);
            return $row['total'];
        }
        return 0;
    }
}