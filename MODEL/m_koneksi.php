<?php
// =====================================================================
// FILE   : MODEL/m_koneksi.php
// FUNGSI : Membuat koneksi ke database MySQL. Dipakai oleh SEMUA model
//          dan controller (setiap kali butuh akses database, mereka
//          membuat objek dari class Koneksi ini).
// =====================================================================

// Class = "cetakan" objek. Class Koneksi bertugas menyimpan pengaturan
// database dan membuka sambungan ke database.
class Koneksi
{
    // 'private' = hanya bisa dibaca/diubah dari DALAM class ini (aman,
    // tidak bisa diutak-atik dari file lain).
    private $host = "localhost";          // alamat server database (komputer sendiri/Laragon)
    private $username = "root";           // user bawaan MySQL di Laragon/XAMPP
    private $pass = "";                   // password MySQL, kosong by default
    private $dbname = "db_perpustakaan";  // nama database yang dibuat lewat phpMyAdmin

    // 'public' = boleh diakses dari luar class. Menyimpan hasil koneksi
    // (objek mysqli) agar bisa dipakai menjalankan query.
    public $koneksi;

    // __construct = fungsi khusus yang OTOMATIS jalan saat objek dibuat
    // (new Koneksi()). Jadi koneksi langsung terbuka tanpa dipanggil lagi.
    public function __construct()
    {
        // mysqli_connect() membuka sambungan ke MySQL dengan 4 data di atas.
        // $this->  artinya "milik objek ini sendiri".
        $this->koneksi = mysqli_connect(
            $this->host,      // 1. server
            $this->username,  // 2. user
            $this->pass,      // 3. password
            $this->dbname     // 4. database yang dipilih
        );

        // Jika koneksi gagal (hasilnya false), hentikan program (die) dan
        // tampilkan pesan error dari MySQL supaya mudah dicari masalahnya.
        if (!$this->koneksi) {
            die("Koneksi database gagal: " . mysqli_connect_error());
        }

        // Set charset utf8mb4 supaya karakter khusus/emoji/huruf non-Latin
        // tersimpan dan tampil dengan benar.
        $this->koneksi->set_charset("utf8mb4");
    }

    // Getter: mengembalikan objek koneksi agar class lain bisa memakainya.
    // Contoh pemakaian di model lain:
    //   $db = new Koneksi();                  // buka koneksi
    //   $this->koneksi = $db->getKoneksi();   // ambil koneksinya
    public function getKoneksi()
    {
        return $this->koneksi;
    }
}
