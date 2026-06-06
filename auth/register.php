<?php
session_start();
require_once '../config/koneksi.php';
$error = ''; $success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $nama = trim($_POST['nama_lengkap']);
    $email = strtolower(trim($_POST['email']));
    $password = trim($_POST['password']); 

    // Konversi otomatis jika pengguna hanya mengetik angka NPM saja di kolom email
    if (is_numeric($email)) { 
        $email = $email . "@student.upnjatim.ac.id"; 
    }

    // Validasi domain pendukung student UPNVJ atau Gmail umum
    $is_valid_student = str_ends_with($email, '@student.upnjatim.ac.id');
    $is_valid_gmail = str_ends_with($email, '@gmail.com');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || (!$is_valid_student && !$is_valid_gmail)) {
        $error = "Pendaftaran gagal! Gunakan Email Resmi UPN (@student.upnjatim.ac.id) atau akun @gmail.com.";
    } else {
        // OTOMATISASI: Mengambil teks sebelum simbol @ untuk dijadikan username di database
        $username = explode('@', $email)[0];

        // Cek duplicate email di database
        $stmtCek = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmtCek->execute([$email]);
        if ($stmtCek->fetch()) {
            $error = "Alamat Email tersebut sudah terdaftar di dalam sistem.";
        } else {
            // Daftar 3 Email Admin Utama Kelompok 5
            $admin_emails = [
                '24081010008@student.upnjatim.ac.id',
                '24081010042@student.upnjatim.ac.id',
                '24081010047@student.upnjatim.ac.id'
            ];

            // Filter role otomatis
            $role = in_array($email, $admin_emails) ? 'admin' : 'user';
            $msg = ($role === 'admin') ? "Akun TIM ADMIN berhasil aktif!" : "Akun Pengguna berhasil aktif!";

            // Simpan ke database (is_verified langsung 1, langsung aktif tanpa OTP)
            $stmt = $pdo->prepare("INSERT INTO users (nama_lengkap, username, email, password, role, is_verified) VALUES (?, ?, ?, ?, ?, 1)");
            $stmt->execute([$nama, $username, $email, $password, $role]);

            // Ambil data barusan untuk otomatis login instan
            $stmtProfile = $pdo->prepare("SELECT * FROM users WHERE email = ?");
            $stmtProfile->execute([$email]);
            $user = $stmtProfile->fetch();

            if ($user) {
                $_SESSION['id_user']  = $user['id'];
                $_SESSION['username'] = $user['nama_lengkap']; 
                $_SESSION['role']     = $user['role'];

                $success = $msg . " Selamat datang! Mengalihkan ke Beranda...";
                
                if ($user['role'] === 'admin') {
                    echo "<script>setTimeout(function(){ window.location.href = '../admin/dashboard.php'; }, 2000);</script>";
                } else {
                    echo "<script>setTimeout(function(){ window.location.href = '../index.php'; }, 2000);</script>";
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Akun E-Kantin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex align-items-center" style="min-height: 100vh;">
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card border-0 shadow-sm p-4 rounded-4">
                <h3 class="fw-bold text-center mb-1">Registrasi Akun</h3>
                <p class="text-muted text-center small mb-4">Gunakan Email Student UPNVJ atau Gmail Umum</p>
                
                <?php if($error): ?><div class="alert alert-danger border-0 small py-2"><?= $error ?></div><?php endif; ?>
                <?php if($success): ?><div class="alert alert-success border-0 small py-2"><?= $success ?></div><?php endif; ?>
                
                <form action="" method="POST">
                    <div class="mb-2">
                        <label class="form-label small fw-bold">Nama Lengkap</label>
                        <input type="text" name="nama_lengkap" class="form-control" placeholder="Nama Lengkap Anda" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small fw-bold">Alamat Email</label>
                        <input type="text" name="email" class="form-control" placeholder="Contoh: 24081010008 atau email@gmail.com" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Buat Kata Sandi Baru</label>
                        <input type="password" name="password" class="form-control" placeholder="Buat password untuk login" required>
                    </div>
                    
                    <button type="submit" name="register" class="btn btn-primary w-100 rounded-pill py-2 fw-bold shadow-sm">Daftar Sekarang</button>
                    <div class="text-center mt-3"><a href="login.php" class="small text-decoration-none">Sudah punya akun? Login</a></div>
                </form>
            </div>
        </div>
    </div>
</div>
</body>
</html>