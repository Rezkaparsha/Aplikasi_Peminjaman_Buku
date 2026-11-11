<?php
/**
 * Controller pencarian nama untuk dropdown filter (kategori, penerbit, penulis).
 * Mengembalikan maksimal 10 nama dalam format JSON.
 */
session_start();
header('Content-Type: application/json; charset=utf-8');

// Hanya pengguna yang sudah login
if (!isset($_SESSION['id_user'])) {
    http_response_code(403);
    echo json_encode([]);
    exit;
}

require_once __DIR__ . "/../MODEL/m_kategori.php";
require_once __DIR__ . "/../MODEL/m_penerbit.php";
require_once __DIR__ . "/../MODEL/m_penulis.php";

class C_Cari
{ 
    const BATAS_DATA = 10;

    /**
     * Menampilkan daftar nama sesuai jenis dan kata kunci.
     */
    public function daftarNama()
    {
        $jenis = $_GET['jenis'] ?? '';
        $keyword = trim($_GET['q'] ?? '');

        switch ($jenis) {
            case 'kategori':
                $model = new Kategori();
                break;
            case 'penerbit':
                $model = new Penerbit();
                break;
            case 'penulis':
                $model = new Penulis();
                break;
            default:
                echo json_encode([]);
                return;
        }

        echo json_encode($model->cariNama($keyword, self::BATAS_DATA));
    }
}

$controller = new C_Cari();
$controller->daftarNama();
