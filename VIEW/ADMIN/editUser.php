<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Proteksi Akses Admin Terpusat
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'admin') {
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'siswa') {
        $kembali = $_SESSION['last_page_siswa'] ?? '/Aplikasi_Peminjaman_Buku/siswa/dashboardSiswa';
        header("Location: " . $kembali);
        exit;
    }
    header("Location: /Aplikasi_Peminjaman_Buku/login");
    exit;
}

$_SESSION['last_page_admin'] = $_SERVER['REQUEST_URI'];

// 2. Ambil ID User dari URL
$id_user = (int)($_GET['id_user'] ?? 0);

if ($id_user <= 0) {
    $_SESSION['error'] = "ID User tidak valid.";
    header("Location: /Aplikasi_Peminjaman_Buku/admin/daftarUser");
    exit;
}

// 3. Panggil Model Users
require_once __DIR__ . "/../../MODEL/m_users.php";
$userModel = new Users();

$dataUser = $userModel->getById($id_user);

if (!$dataUser) {
    $_SESSION['error'] = "Data user tidak ditemukan di database.";
    header("Location: /Aplikasi_Peminjaman_Buku/admin/daftarUser");
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User - Perpustakaan Online</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: #f4f7f6;
            color: #333;
            display: flex;
            min-height: 100vh;
        }

        .main-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .topbar {
            background-color: #fff;
            padding: 15px 30px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .topbar h2 {
            font-size: 20px;
            color: #2c3e50;
        }

        .container {
            padding: 30px;
            flex: 1;
            overflow-y: auto;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 25px;
            max-width: 1000px;
        }

        .card {
            background: #ffffff;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.05);
        }

        .card-header {
            border-bottom: 2px solid #ecf0f1;
            padding-bottom: 12px;
            margin-bottom: 20px;
        }

        .card-header h3 {
            color: #2c3e50;
            font-size: 18px;
        }

        .alert-danger {
            background-color: #f8d7da;
            color: #842029;
            border: 1px solid #f5c2c7;
            padding: 12px 15px;
            border-radius: 6px;
            font-size: 13.5px;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 18px;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 6px;
            color: #34495e;
            font-size: 13px;
        }

        .form-control {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ccd1d1;
            border-radius: 5px;
            font-size: 14px;
            color: #2c3e50;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: #3498db;
            outline: none;
            box-shadow: 0 0 5px rgba(52, 152, 219, 0.3);
        }

        .action-group {
            display: flex;
            gap: 10px;
            margin-top: 25px;
        }

        .btn {
            padding: 10px 18px;
            border: none;
            border-radius: 5px;
            font-size: 14px;
            font-weight: bold;
            cursor: pointer;
            text-align: center;
            text-decoration: none;
            transition: background 0.3s ease;
        }

        .btn-submit {
            background-color: #3498db;
            color: white;
            flex: 1;
        }

        .btn-submit:hover {
            background-color: #2980b9;
        }

        .btn-warning {
            background-color: #e67e22;
            color: white;
            width: 100%;
        }

        .btn-warning:hover {
            background-color: #d35400;
        }

        .btn-cancel {
            background-color: #e74c3c;
            color: white;
            flex: 1;
        }

        .btn-cancel:hover {
            background-color: #c0392b;
        }
    </style>
</head>

<body>

    <!-- Sidebar Admin -->
    <?php include __DIR__ . "/../TEMPLATE/SideBarAdmin.php"; ?>

    <div class="main-content">
        <!-- Topbar -->
        <div class="topbar">
            <h2>Edit Data User</h2>
            <div style="font-size: 14px; color: #7f8c8d;">Panel Admin</div>
        </div>

        <!-- Main Container -->
        <div class="container">
            <div class="form-grid">

                <!-- CARD EDIT PROFIL USER -->
                <div class="card">
                    <div class="card-header">
                        <h3>Informasi Pengguna</h3>
                    </div>

                    <!-- ALERT NOTIFIKASI ERROR -->
                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="alert-danger">
                            <span>⚠️ <?= htmlspecialchars($_SESSION['error']) ?></span>
                        </div>
                        <?php unset($_SESSION['error']); ?>
                    <?php endif; ?>

                    <form action="/Aplikasi_Peminjaman_Buku/CONTROLLER/c_user.php?aksi=update" method="POST">
                        <input type="hidden" name="id_user" value="<?= htmlspecialchars($dataUser['id_user']) ?>">

                        <div class="form-group">
                            <label for="nis_nip">NIS / NIP</label>
                            <input type="text" id="nis_nip" name="nis_nip" class="form-control" value="<?= htmlspecialchars($dataUser['nis_nip']) ?>" placeholder="Masukkan NIS/NIP">
                        </div>

                        <div class="form-group">
                            <label for="nama_lengkap">Nama Lengkap</label>
                            <input type="text" id="nama_lengkap" name="nama_lengkap" class="form-control" value="<?= htmlspecialchars($dataUser['nama_lengkap']) ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="kelas">Kelas</label>
                            <input type="text" id="kelas" name="kelas" class="form-control" value="<?= htmlspecialchars($dataUser['kelas'] ?? '') ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="username">Username</label>
                            <input type="text" id="username" name="username" class="form-control" value="<?= htmlspecialchars($dataUser['username']) ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="role">Role / Akses</label>
                            <select id="role" name="role" class="form-control" required>
                                <option value="admin" <?= ($dataUser['role'] === 'admin') ? 'selected' : '' ?>>Admin</option>
                                <option value="siswa" <?= ($dataUser['role'] === 'siswa') ? 'selected' : '' ?>>Siswa</option>
                                <option value="guru" <?= ($dataUser['role'] === 'guru') ? 'selected' : '' ?>>Guru</option>
                            </select>
                        </div>

                        <div class="action-group">
                            <button type="submit" class="btn btn-submit">Simpan Perubahan</button>
                            <a href="/Aplikasi_Peminjaman_Buku/admin/daftarUser" class="btn btn-cancel">Batal</a>
                        </div>
                    </form>
                </div>

                <!-- CARD GANTI PASSWORD -->
                <div class="card" style="height: fit-content;">
                    <div class="card-header">
                        <h3>Ganti Password</h3>
                    </div>

                    <form action="/Aplikasi_Peminjaman_Buku/CONTROLLER/c_user.php?aksi=updatePassword" method="POST">
                        <input type="hidden" name="id_user" value="<?= htmlspecialchars($dataUser['id_user']) ?>">

                        <div class="form-group">
                            <label for="password">Password Baru</label>
                            <input type="password" id="password" name="password" class="form-control" placeholder="Ketik password baru" required>
                        </div>

                        <div style="margin-top: 25px;">
                            <button type="submit" class="btn btn-warning">Update Password</button>
                        </div>
                    </form>
                </div>

            </div>
        </div>
    </div>

</body>

</html>