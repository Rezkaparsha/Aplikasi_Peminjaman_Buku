<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Proteksi Akses Siswa
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'siswa') {
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
        $kembali = $_SESSION['last_page_admin'] ?? '/Aplikasi_Peminjaman_Buku/admin/dashboardAdmin';
        header("Location: " . $kembali);
        exit;
    }
    header("Location: /Aplikasi_Peminjaman_Buku/login");
    exit;
}

// Simpan URL lokasi controller aktif saat ini
$_SESSION['last_page_siswa'] = $_SERVER['REQUEST_URI'];

require_once __DIR__ . "/../../MODEL/m_koneksi.php";

$db = new Koneksi();
$koneksi = $db->getKoneksi();

$id_user = (int)$_SESSION['id_user'];

// Ambil data user terbaru dari database (Termasuk kolom kelas)
$stmt = $koneksi->prepare("SELECT id_user, nis_nip, username, nama_lengkap, kelas, role FROM users WHERE id_user = ?");
$stmt->bind_param("i", $id_user);
$stmt->execute();
$userData = $stmt->get_result()->fetch_assoc();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Saya - Aplikasi Perpustakaan</title>

    <!-- FontAwesome Icon -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        /* Reset & Layout Kunci Layar */
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        html,
        body {
            height: 100vh;
            width: 100vw;
            margin: 0;
            padding: 0;
            overflow: hidden;
            background-color: #f4f8fb;
        }

        .app-wrapper {
            display: flex;
            height: 100vh;
            width: 100vw;
            overflow: hidden;
        }

        .main-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            height: 100vh;
            overflow: hidden;
        }

        /* Topbar Header */
        .topbar {
            background-color: #ffffff;
            padding: 15px 30px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.02);
            display: flex;
            justify-content: space-between;
            align-items: center;
            z-index: 10;
        }

        .topbar h2 {
            font-size: 18px;
            color: #1e293b;
            font-weight: 700;
        }

        .topbar-profile {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* Container Content */
        .content-container {
            padding: 30px;
            flex: 1;
            overflow-y: auto;
            overflow-x: hidden;
        }

        .page-title-block {
            margin-bottom: 25px;
        }

        .page-title-block h3 {
            font-size: 22px;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 4px;
        }

        .page-title-block p {
            color: #64748b;
            font-size: 14px;
        }

        /* Grid Layout Dual Column */
        .profile-grid {
            display: grid;
            grid-template-columns: 380px 1fr;
            gap: 25px;
            align-items: start;
        }

        @media (max-width: 992px) {
            .profile-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Card Custom Styling */
        .card-custom {
            background: #ffffff;
            border-radius: 16px;
            padding: 28px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03);
            border: 1px solid #f1f5f9;
        }

        /* Avatar Circle */
        .avatar-wrapper {
            text-align: center;
            margin-bottom: 20px;
        }

        .avatar-circle {
            width: 85px;
            height: 85px;
            background: linear-gradient(135deg, #2563eb, #3b82f6);
            color: #ffffff;
            font-size: 2.2rem;
            font-weight: 800;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            margin: 0 auto 12px;
            box-shadow: 0 8px 16px rgba(37, 99, 235, 0.2);
        }

        .user-title-name {
            font-size: 18px;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 6px;
        }

        .role-badge {
            display: inline-block;
            background-color: #eff6ff;
            color: #2563eb;
            border: 1px solid #bfdbfe;
            padding: 4px 14px;
            border-radius: 20px;
            font-size: 11.5px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Info Item List (Read-only) */
        .info-list {
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid #f1f5f9;
        }

        .info-item {
            margin-bottom: 16px;
        }

        .info-item:last-child {
            margin-bottom: 0;
        }

        .info-label {
            font-size: 12px;
            font-weight: 600;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .info-value {
            font-size: 15px;
            font-weight: 600;
            color: #1e293b;
            background: #f8fafc;
            padding: 10px 14px;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
        }

        /* Form Controls */
        .form-title {
            font-size: 17px;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #f1f5f9;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .form-group {
            margin-bottom: 18px;
        }

        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #475569;
            margin-bottom: 6px;
        }

        .input-box {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-box i {
            position: absolute;
            left: 14px;
            color: #94a3b8;
            font-size: 14px;
        }

        .form-control {
            width: 100%;
            padding: 11px 14px 11px 40px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: 14px;
            color: #1e293b;
            transition: all 0.2s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
        }

        .btn-submit {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 28px;
            background-color: #2563eb;
            color: #ffffff;
            border: none;
            border-radius: 30px;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            transition: background 0.2s ease;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.25);
            margin-top: 10px;
        }

        .btn-submit:hover {
            background-color: #1d4ed8;
        }

        /* Alert styling */
        .alert {
            padding: 14px 18px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 14px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-danger {
            background-color: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        .alert-success {
            background-color: #f0fdf4;
            color: #166534;
            border: 1px solid #bbf7d0;
        }
    </style>
</head>

<body>

    <div class="app-wrapper">

        <!-- Include Sidebar Siswa -->
        <?php include __DIR__ . "/../TEMPLATE/SideBarSiswa.php"; ?>

        <!-- Main Content Area -->
        <div class="main-content">

            <!-- Topbar Header -->
            <div class="topbar">
                <h2>Profil Pengguna</h2>
                <div class="topbar-profile">
                    <span style="font-size: 14px; font-weight: 600; color: #475569;"><?= htmlspecialchars($userData['nama_lengkap'] ?? 'Siswa') ?></span>
                    <div style="width: 32px; height: 32px; background: #2563eb; color: #fff; border-radius: 50%; display: flex; justify-content: center; align-items: center; font-weight: bold; font-size: 12px;">
                        <?= strtoupper(substr($userData['nama_lengkap'] ?? 'S', 0, 1)) ?>
                    </div>
                </div>
            </div>

            <div class="content-container">

                <div class="page-title-block">
                    <h3><i class="fa-solid fa-user-gear text-primary me-2"></i>Profil Saya</h3>
                    <p>Informasi identitas akun siswa dan formulir pembaruan kata sandi.</p>
                </div>

                <!-- Alert Session -->
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger">
                        <i class="fa-solid fa-circle-exclamation"></i>
                        <div><?= htmlspecialchars($_SESSION['error']) ?></div>
                    </div>
                    <?php unset($_SESSION['error']); ?>
                <?php endif; ?>

                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success">
                        <i class="fa-solid fa-circle-check"></i>
                        <div><?= htmlspecialchars($_SESSION['success']) ?></div>
                    </div>
                    <?php unset($_SESSION['success']); ?>
                <?php endif; ?>

                <div class="profile-grid">

                    <!-- Kiri: Informasi Akun (Read-only) -->
                    <div class="card-custom">
                        <div class="avatar-wrapper">
                            <div class="avatar-circle">
                                <?= strtoupper(substr($userData['nama_lengkap'] ?? 'S', 0, 1)) ?>
                            </div>
                            <div class="user-title-name"><?= htmlspecialchars($userData['nama_lengkap'] ?? '-') ?></div>
                            <span class="role-badge">
                                <?= strtoupper(htmlspecialchars($userData['role'] ?? 'SISWA')) ?>
                            </span>
                        </div>

                        <div class="info-list">
                            <div class="info-item">
                                <div class="info-label"><i class="fa-solid fa-id-card"></i> Nama Lengkap</div>
                                <div class="info-value"><?= htmlspecialchars($userData['nama_lengkap'] ?? '-') ?></div>
                            </div>

                            <div class="info-item">
                                <div class="info-label"><i class="fa-solid fa-graduation-cap"></i> Kelas</div>
                                <div class="info-value"><?= htmlspecialchars($userData['kelas'] ?? '-') ?></div>
                            </div>

                            <div class="info-item">
                                <div class="info-label"><i class="fa-solid fa-hashtag"></i> NIS</div>
                                <div class="info-value"><?= htmlspecialchars($userData['nis_nip'] ?? '-') ?></div>
                            </div>

                            <div class="info-item">
                                <div class="info-label"><i class="fa-solid fa-user"></i> Username</div>
                                <div class="info-value"><?= htmlspecialchars($userData['username'] ?? '-') ?></div>
                            </div>
                        </div>
                    </div>

                    <!-- Kanan: Form Ubah Password -->
                    <div class="card-custom">
                        <h5 class="form-title">
                            <i class="fa-solid fa-key text-warning"></i> Ubah Password
                        </h5>

                        <!-- Form action tetap terhubung ke Controller User -->
                        <form action="/Aplikasi_Peminjaman_Buku/CONTROLLER/c_user.php?aksi=updatePassword" method="POST">

                            <div class="form-group">
                                <label for="password_lama">Password Saat Ini</label>
                                <div class="input-box">
                                    <i class="fa-solid fa-lock"></i>
                                    <input type="password" id="password_lama" name="password_lama" class="form-control" placeholder="Masukkan password lama" required>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="password_baru">Password Baru</label>
                                <div class="input-box">
                                    <i class="fa-solid fa-key"></i>
                                    <input type="password" id="password_baru" name="password_baru" class="form-control" placeholder="Masukkan password baru" required>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="konfirmasi_password_baru">Konfirmasi Password Baru</label>
                                <div class="input-box">
                                    <i class="fa-solid fa-check-double"></i>
                                    <input type="password" id="konfirmasi_password_baru" name="konfirmasi_password_baru" class="form-control" placeholder="Ulangi password baru" required>
                                </div>
                            </div>

                            <button type="submit" class="btn-submit">
                                <i class="fa-solid fa-floppy-disk"></i> Simpan Perubahan Password
                            </button>

                        </form>
                    </div>

                </div>

            </div>

        </div>
    </div>

</body>

</html>