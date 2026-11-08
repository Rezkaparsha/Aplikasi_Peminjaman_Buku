<?php

// =====================================================================
// FILE   : MODEL/m_peminjaman.php
// FUNGSI : MODEL transaksi peminjaman. Semua query ke tabel peminjaman,
//          detail_peminjaman, denda, histori_transaksi, serta perubahan
//          stok di tabel buku ada di sini.
// POLA BALIKAN: fungsi aksi mengembalikan array
//          ['status' => true/false, 'pesan' => 'teks notifikasi'].
// STRUKTUR DATA: 1 peminjaman (header) punya banyak detail_peminjaman
//          (satu baris per jenis buku).
// STATUS      : Diajukan -> Dipinjam -> Dikembalikan  (atau Diajukan -> Ditolak)
// =====================================================================
// Memuat class Koneksi dari file m_koneksi.php (satu folder).
require_once __DIR__ . "/m_koneksi.php";

// Class model peminjaman.
class M_Peminjaman
{
    // Menyimpan koneksi database.
    private $koneksi;

    // Constructor: membuka koneksi database saat objek dibuat.
    public function __construct()
    {
        $db = new Koneksi();
        $this->koneksi = $db->getKoneksi();
        // Pastikan karakter dibaca/ditulis dalam utf8mb4.
        $this->koneksi->set_charset("utf8mb4");
    }


    // 1. TAMBAH PEMINJAMAN

    // FUNGSI 1: tambahPeminjaman($id_user, $daftarBuku)
    // Membuat pengajuan peminjaman baru oleh siswa.
    //  - $daftarBuku : [['id_buku'=>1,'jumlah'=>2], ...]
    //  - Hanya MEMERIKSA stok; stok BELUM dikurangi (baru berkurang saat admin menyetujui).
    //  - Memakai TRANSAKSI: peminjaman + semua detailnya tersimpan bersama-sama
    //    atau dibatalkan semuanya jika ada yang gagal.
    //  - Balikan: ['status','pesan','id_peminjaman']
    public function tambahPeminjaman(
        $id_user,
        $daftarBuku
    ) {

        // Paksa jadi angka (mencegah input teks aneh).
        $id_user = (int)$id_user;

        // Validasi: id user harus valid.
        if ($id_user <= 0) {

            return [
                'status' => false,
                'pesan' => 'User tidak ditemukan.'
            ];
        }


        // Validasi: daftar buku tidak boleh kosong dan harus berupa array.
        if (
            empty($daftarBuku) ||
            !is_array($daftarBuku)
        ) {

            return [
                'status' => false,
                'pesan' => 'Belum ada buku yang dipilih.'
            ];
        }


        // MULAI TRANSAKSI. Sampai commit() dipanggil, perubahan belum permanen.
        // Jika terjadi error -> rollback() membatalkan semuanya (prinsip ACID: all or nothing).
        $this->koneksi
            ->begin_transaction();


        // Semua langkah di dalam try. Jika ada 'throw new Exception' -> lompat ke catch -> rollback.
        try {

            // Array [id_buku => total_jumlah]. Dipakai untuk menggabungkan buku yang sama.
            $bukuGabungan = [];


            // -----------------------------------------
            // GABUNGKAN BUKU YANG SAMA
            // -----------------------------------------

            // LANGKAH 1: validasi tiap item dan gabungkan buku yang sama (mis. buku A dipilih 2x -> jumlahnya dijumlahkan).
            foreach ($daftarBuku as $buku) {

                // Setiap item wajib punya id_buku dan jumlah.
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


                // Ambil id buku sebagai angka.
                $id_buku =
                    (int)$buku['id_buku'];

                // Ambil jumlah sebagai angka.
                $jumlah =
                    (int)$buku['jumlah'];


                // id buku harus > 0.
                if ($id_buku <= 0) {

                    throw new Exception(
                        'ID buku tidak valid.'
                    );
                }


                // Minimal meminjam 1 buah.
                if ($jumlah <= 0) {

                    throw new Exception(
                        'Jumlah buku minimal 1.'
                    );
                }


                // Jika buku sudah ada di array -> tambahkan jumlahnya (+=). Jika belum -> buat entri baru.
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

            // LANGKAH 2: cek stok TIAP buku di database.
            foreach (
                $bukuGabungan
                as $id_buku => $jumlah
            ) {

                // Ambil stok buku. 'FOR UPDATE' = mengunci baris ini sampai transaksi selesai,
                // sehingga dua siswa yang meminjam bersamaan tidak bisa membaca stok yang sama
                // secara bersamaan (mencegah stok jadi minus / race condition).
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


                // Isi tanda tanya (?) dengan id buku (i = integer).
                $stmt->bind_param(
                    "i",
                    $id_buku
                );

                $stmt->execute();


                // Ambil satu baris hasil (judul + stok).
                $dataBuku =
                    $stmt
                    ->get_result()
                    ->fetch_assoc();


                $stmt->close();


                // Buku dengan id itu tidak ada di database.
                if (!$dataBuku) {

                    throw new Exception(
                        "Buku dengan ID {$id_buku} tidak ditemukan."
                    );
                }


                // ATURAN SISTEM: buku hanya bisa dipinjam jika stok mencukupi.
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

            // Tanggal pinjam = hari ini (format Y-m-d sesuai tipe DATE MySQL).
            $tanggal_pinjam =
                date('Y-m-d');

            // Status awal selalu 'Diajukan' (menunggu admin).
            $status =
                'Diajukan';


            // LANGKAH 3: simpan data induk (header) peminjaman.
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


            // i = id_user, s = tanggal_pinjam, s = status.
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


            // insert_id = id otomatis (AUTO_INCREMENT) dari INSERT barusan; dipakai sebagai kunci detail.
            $id_peminjaman =
                $this->koneksi->insert_id;


            $stmt->close();


            // -----------------------------------------
            // DETAIL PEMINJAMAN
            // -----------------------------------------

            // Status awal tiap baris detail juga 'Diajukan'.
            $statusDetail =
                'Diajukan';


            // LANGKAH 4: simpan detail (satu baris per buku). tanggal_pengembalian dan
            // tanggal_dikembalikan NULL dulu; baru diisi saat disetujui / dikembalikan.
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


            // Loop: satu INSERT detail untuk setiap buku (statement yang sama dipakai ulang).
            foreach (
                $bukuGabungan
                as $id_buku => $jumlah
            ) {

                // i id_peminjaman, i id_buku, i jumlah_buku, s status.
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


            // COMMIT: semua perubahan disimpan permanen.
            // COMMIT: simpan permanen semua langkah di atas.
            $this->koneksi
                ->commit();


            // Kembalikan hasil sukses + id peminjaman baru (dipakai controller untuk redirect ke halaman detail).
            return [
                'status' => true,
                'pesan' =>
                'Pengajuan peminjaman berhasil dibuat.',
                'id_peminjaman' =>
                $id_peminjaman
            ];
        // Jika ada Exception di dalam try: batalkan semua perubahan (rollback) dan kembalikan pesan errornya.
        // Gagal di langkah mana pun -> rollback (stok kembali seperti semula) dan kirim pesan error.
        } catch (Exception $e) {

            $this->koneksi
                ->rollback();


            return [
                'status' => false,
                'pesan' => $e->getMessage()
            ];
        }
    }


    // 2. PEMINJAMAN MILIK USER

    // FUNGSI 2: getPeminjamanByUser($id_user)
    // Daftar peminjaman milik SATU siswa (halaman 'Peminjaman Saya').
    // JOIN users untuk nama/NIS; LEFT JOIN detail untuk menghitung jumlah jenis buku
    // (COUNT) dan total buku (SUM). GROUP BY karena memakai fungsi agregat.
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


        // Siapkan query (prepared statement).
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


        // Ubah hasil query menjadi array: tiap putaran mengambil satu baris.
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

    // FUNGSI 3: getSemuaPeminjaman()
    // Semua peminjaman semua siswa (halaman admin). Sama seperti fungsi 2 tanpa filter WHERE user.
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


        // query() langsung (tanpa tanda tanya) karena tidak ada input pengguna.
        $result =
            $this->koneksi
            ->query($sql);


        if (!$result) {
            return [];
        }


        $data = [];


        // Ubah hasil query menjadi array: tiap putaran mengambil satu baris.
        while (
            $row =
            $result->fetch_assoc()
        ) {

            $data[] = $row;
        }


        return $data;
    }


    // 4. DETAIL PEMINJAMAN

    // FUNGSI 4: getDetailPeminjaman($id_peminjaman)
    // Daftar buku (baris detail) dalam satu peminjaman. JOIN 3 tabel:
    // peminjaman + users (identitas) + detail_peminjaman + buku (judul, harga, cover).
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


        // Siapkan query detail.
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


        // Ubah hasil query menjadi array: tiap putaran mengambil satu baris.
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

    // FUNGSI 5: getPeminjamanById($id_peminjaman)
    // Data induk SATU peminjaman (termasuk alasan_penolakan). Mengembalikan null jika tidak ada.
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


        // ?: = jika $data kosong/false, kembalikan null.
        return $data ?: null;
    }


    // 6. TOLAK PEMINJAMAN (DENGAN ALASAN)

    // FUNGSI 6: tolakPeminjaman($id_peminjaman, $alasan_penolakan)
    // Admin menolak pengajuan. Stok tidak berubah karena belum pernah dikurangi.
    public function tolakPeminjaman($id_peminjaman, $alasan_penolakan)
    {
        // Syarat 'AND status = Diajukan' memastikan hanya pengajuan yang BELUM diproses yang bisa ditolak.
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

        // affected_rows = jumlah baris yang berubah. 0 berarti id tidak ada atau status sudah bukan 'Diajukan'.
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

    // FUNGSI 7: setujuiPeminjaman($id_peminjaman, $tanggal_pengembalian)
    // Admin menyetujui pengajuan. Langkah (semua dalam satu transaksi):
    //  1) kunci & cek peminjaman masih 'Diajukan'
    //  2) ambil semua detail buku
    //  3) cek stok setiap buku
    //  4) KURANGI STOK
    //  5) isi tanggal_pengembalian + status detail = 'Dipinjam'
    //  6) status peminjaman = 'Dipinjam'
    public function setujuiPeminjaman(
        $id_peminjaman,
        $tanggal_pengembalian
    ) {

        // Admin wajib mengisi tanggal batas pengembalian.
        if (
            empty($tanggal_pengembalian)
        ) {

            return [
                'status' => false,
                'pesan' =>
                'Tanggal pengembalian harus diisi.'
            ];
        }


        // Mulai transaksi.
        $this->koneksi
            ->begin_transaction();


        try {

            // -----------------------------------------
            // CEK PEMINJAMAN
            // -----------------------------------------

            // LANGKAH 1: ambil peminjaman dan KUNCI barisnya (FOR UPDATE) agar tidak diproses dua kali bersamaan.
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


            // Peminjaman tidak ada.
            if (!$peminjaman) {

                throw new Exception(
                    'Peminjaman tidak ditemukan.'
                );
            }


            // Hanya status 'Diajukan' yang boleh disetujui (cegah setujui ganda).
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

            // LANGKAH 2: ambil semua buku di peminjaman ini beserta stok saat ini (dikunci).
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


            // Wadah baris detail.
            $detail = [];


            // Ubah hasil query menjadi array: tiap putaran mengambil satu baris.
            while (
                $row =
                $result->fetch_assoc()
            ) {

                $detail[] = $row;
            }


            $stmt->close();


            // Peminjaman tanpa detail dianggap error.
            if (empty($detail)) {

                throw new Exception(
                    'Detail buku tidak ditemukan.'
                );
            }


            // -----------------------------------------
            // CEK STOK
            // -----------------------------------------

            // LANGKAH 3: pastikan stok semua buku cukup SEBELUM ada yang dikurangi.
            foreach ($detail as $item) {

                // Stok < jumlah yang diminta -> batalkan semuanya.
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

            // LANGKAH 4: kurangi stok. Syarat 'AND stok >= ?' = pengaman terakhir agar stok tidak pernah minus.
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


            // Loop: kurangi stok untuk setiap buku.
            foreach ($detail as $item) {

                $jumlah =
                    (int)$item['jumlah_buku'];

                $id_buku =
                    (int)$item['id_buku'];


                // i jumlah (pengurang), i id_buku, i jumlah (syarat stok >=).
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


                // Tidak ada baris berubah = stok tidak cukup (kalah cepat dari transaksi lain).
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

            // LANGKAH 5: isi tanggal batas pengembalian dan ubah status semua detail menjadi 'Dipinjam'.
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

            // LANGKAH 6: ubah status peminjaman (header) menjadi 'Dipinjam'.
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
                ->commit();


            // Sukses.
            return [
                'status' => true,
                'pesan' =>
                'Peminjaman berhasil disetujui.'
            ];
        // Ada error -> rollback semuanya, kirim pesan error.
        } catch (Exception $e) {

            $this->koneksi
                ->rollback();


            return [
                'status' => false,
                'pesan' => $e->getMessage()
            ];
        }
    }


    // 8. CEK KEPEMILIKAN

    // FUNGSI 8: cekKepemilikanPeminjaman($id_peminjaman, $id_user)
    // Balikan true jika peminjaman itu milik user tersebut. Dipakai controller agar siswa
    // tidak bisa membuka peminjaman milik orang lain.
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


        // Jika ada minimal 1 baris -> true (milik user), selain itu false.
        $ada =
            $stmt
            ->get_result()
            ->num_rows > 0;


        $stmt->close();


        return $ada;
    }


    // 9. PROSES PENGEMBALIAN BUKU & PERHITUNGAN DENDA (PER BUKU)

    // FUNGSI 9: kembalikanBuku($id_peminjaman, $kondisi_buku, $denda_tambahan)
    // INTI PENGEMBALIAN, dijalankan admin. Dalam satu transaksi:
    //  1) cek peminjaman berstatus 'Dipinjam'
    //  2) untuk TIAP buku:
    //       - kembalikan stok (kecuali kondisi 'Hilang')
    //       - hitung denda keterlambatan: selisih hari x Rp 1.000
    //       - tambahkan denda tambahan bila buku Rusak/Hilang
    //       - simpan ke tabel denda (jika ada) dan histori_transaksi
    //  3) ubah status detail & peminjaman menjadi 'Dikembalikan'
    // Parameter berupa array dengan kunci id_detail, mis. $kondisi_buku[12] = 'Rusak'.
    public function kembalikanBuku($id_peminjaman, $kondisi_buku = [], $denda_tambahan = [])
    {
        // Id sebagai angka.
        $id_peminjaman = (int)$id_peminjaman;
        // Tanggal hari ini = tanggal pengembalian sebenarnya.
        $tanggal_sekarang = date('Y-m-d');

        // Mulai transaksi.
        $this->koneksi->begin_transaction();

        try {
            // Ambil data peminjaman + identitas siswa (untuk disalin ke histori). FOR UPDATE = baris dikunci.
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

            // Hanya peminjaman berstatus 'Dipinjam' yang bisa dikembalikan.
            if (!$dataPmj || $dataPmj['status'] !== 'Dipinjam') {
                throw new Exception("Peminjaman tidak ditemukan atau statusnya bukan 'Dipinjam'.");
            }

            // Ambil semua buku di peminjaman ini + tanggal batas kembali + harga buku.
            $stmtDetail = $this->koneksi->prepare("
                SELECT dp.id_detail, dp.id_buku, dp.jumlah_buku, dp.tanggal_pengembalian, b.judul_buku, b.harga_buku
                FROM detail_peminjaman dp
                JOIN buku b ON dp.id_buku = b.id_buku
                WHERE dp.id_peminjaman = ? FOR UPDATE
            ");
            $stmtDetail->bind_param("i", $id_peminjaman);
            $stmtDetail->execute();
            $resultDetail = $stmtDetail->get_result();

            // Wadah detail buku.
            $details = [];
            while ($row = $resultDetail->fetch_assoc()) {
                $details[] = $row;
            }
            $stmtDetail->close();

            // Tidak ada detail = error.
            if (empty($details)) throw new Exception("Detail peminjaman tidak ditemukan.");

            // Query penambah stok (dipakai berulang di loop).
            $stmtStok = $this->koneksi->prepare("UPDATE buku SET stok = stok + ? WHERE id_buku = ?");

            // Query arsip: salin data transaksi ke histori_transaksi (menyimpan nama siswa, judul buku, harga, denda saat itu).
            $stmtHistori = $this->koneksi->prepare("
                INSERT INTO histori_transaksi 
                (id_peminjaman, id_detail, nis_nip, nama_siswa, id_buku, judul_buku, harga_buku, tanggal_pinjam, tanggal_pengembalian, tanggal_dikembalikan, jenis_denda, denda, tanggal_selesai)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            // Tarif denda Rp 1.000 per hari keterlambatan (diubah di sini bila aturan berubah).
            $tarif_denda_per_hari = 1000;

            // LOOP per buku dalam peminjaman.
            foreach ($details as $item) {
                // id_detail menjadi kunci untuk mengambil kondisi & denda tambahan dari form.
                $id_detail = $item['id_detail'];

                // Ambil kondisi dan denda khusus untuk BUKU INI dari array yang dikirim form
                // Jika form tidak mengirim kondisi untuk buku ini, anggap 'Normal'.
                $kondisi_item = $kondisi_buku[$id_detail] ?? 'Normal';
                $denda_nominal_item = (int)($denda_tambahan[$id_detail] ?? 0);

                // Jika buku HILANG, stok TIDAK dikembalikan. Jika Normal/Rusak, stok dikembalikan.
                if ($kondisi_item !== 'Hilang') {
                    // Tambah stok sebanyak jumlah_buku yang dipinjam.
                    $stmtStok->bind_param("ii", $item['jumlah_buku'], $item['id_buku']);
                    $stmtStok->execute();
                }

                // Hitung Denda Keterlambatan
                // DateTime = objek tanggal PHP agar bisa dibandingkan dan dihitung selisihnya.
                $tgl_kembali_seharusnya = new DateTime($item['tanggal_pengembalian']);
                $tgl_dikembalikan_real = new DateTime($tanggal_sekarang);

                // Awal: belum ada denda.
                $total_denda = 0;
                $selisih_hari = 0;
                $keterangan_denda = "Tidak Ada";

                // Terlambat jika tanggal sekarang LEBIH BESAR dari batas pengembalian.
                if ($tgl_dikembalikan_real > $tgl_kembali_seharusnya) {
                    // diff()->days = selisih dalam jumlah hari. Denda = hari x tarif.
                    $selisih_hari = $tgl_dikembalikan_real->diff($tgl_kembali_seharusnya)->days;
                    $total_denda = $selisih_hari * $tarif_denda_per_hari;
                    $keterangan_denda = "Terlambat " . $selisih_hari . " Hari";
                }

                // Jika ada denda tambahan untuk buku ini (karena rusak/hilang)
                // Denda tambahan (Rusak/Hilang) dijumlahkan ke total denda.
                if ($kondisi_item !== 'Normal' && $denda_nominal_item > 0) {
                    $total_denda += $denda_nominal_item;
                    if ($keterangan_denda === "Tidak Ada") {
                        $keterangan_denda = "Buku " . $kondisi_item;
                    } else {
                        $keterangan_denda .= " + Buku " . $kondisi_item;
                    }
                }

                // Simpan ke tabel denda HANYA bila ada denda. Status pembayaran memakai nilai default tabel.
                if ($total_denda > 0) {
                    $stmtDenda = $this->koneksi->prepare("
                        INSERT INTO denda (id_detail, jenis_denda, jumlah_hari_terlambat, jumlah_denda, tanggal_denda) VALUES (?, ?, ?, ?, ?)
                    ");
                    $stmtDenda->bind_param("isiis", $id_detail, $keterangan_denda, $selisih_hari, $total_denda, $tanggal_sekarang);
                    $stmtDenda->execute();
                    $stmtDenda->close();
                }

                // Isi 13 kolom histori. Urutan tipe: i i s s i s i s s s s i s.
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
                // Simpan satu baris histori untuk buku ini.
                $stmtHistori->execute();
            }

            $stmtStok->close();
            $stmtHistori->close();

            // Setelah semua buku diproses: tandai semua detail 'Dikembalikan' + isi tanggal_dikembalikan.
            $stmtUpdDetail = $this->koneksi->prepare("UPDATE detail_peminjaman SET status = 'Dikembalikan', tanggal_dikembalikan = ? WHERE id_peminjaman = ?");
            $stmtUpdDetail->bind_param("si", $tanggal_sekarang, $id_peminjaman);
            $stmtUpdDetail->execute();
            $stmtUpdDetail->close();

            // Tandai peminjaman (header) 'Dikembalikan'.
            $stmtUpdPmj = $this->koneksi->prepare("UPDATE peminjaman SET status = 'Dikembalikan' WHERE id_peminjaman = ?");
            $stmtUpdPmj->bind_param("i", $id_peminjaman);
            $stmtUpdPmj->execute();
            $stmtUpdPmj->close();

            // COMMIT: stok, denda, histori, dan status tersimpan permanen sekaligus.
            $this->koneksi->commit();

            return ['status' => true, 'pesan' => 'Pengembalian berhasil. Kondisi dan stok buku telah disesuaikan.'];
        } catch (Exception $e) {
            $this->koneksi->rollback();
            return ['status' => false, 'pesan' => $e->getMessage()];
        }
    }


    // 10. AMBIL DATA HISTORI TRANSAKSI (ADMIN) DENGAN JOIN KELAS

    // FUNGSI 10: getHistoriTransaksi()
    // Ambil histori transaksi + kelas siswa (LEFT JOIN ke users lewat nis_nip), terbaru di atas.
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

    // FUNGSI 11: getJumlahBelumKembali()
    // Menghitung jumlah peminjaman berstatus 'Dipinjam' (angka untuk dashboard admin).
    public function getJumlahBelumKembali()
    {
        $query = "SELECT COUNT(*) as total FROM peminjaman WHERE status = 'Dipinjam'";
        // Gaya prosedural mysqli_query() (hasilnya sama dengan $koneksi->query()).
        $result = mysqli_query($this->koneksi, $query);

        if ($result) {
            $row = mysqli_fetch_assoc($result);
            return $row['total'];
        }
        // Query gagal -> kembalikan 0.
        return 0;
    }
}