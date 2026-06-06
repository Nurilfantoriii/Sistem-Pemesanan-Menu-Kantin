<?php
session_start();
require_once '../config/koneksi.php';

$error = ''; 
$success = ''; 
// Status untuk mengontrol apakah kotak input OTP di bawah perlu muncul atau tidak
$show_otp_input = false; 

// --- 1. PROSES TAHAP AWAL: PENGGUNA MINTA KODE OTP ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_otp'])) {
    $email = strtolower(trim($_POST['email']));
    
    if (is_numeric($email)) { 
        $email = $email . "@student.upnjatim.ac.id"; 
    }

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        $otp = rand(100000, 999999);
        
        // Bersihkan data OTP usang milik email ini
        $pdo->prepare("DELETE FROM verifikasi_otp WHERE email = ?")->execute([$email]);
        
        // Masukkan data kode OTP yang baru ke database
        $pdo->prepare("INSERT INTO verifikasi_otp (email, kode_otp) VALUES (?, ?)")->execute([$email, $otp]);
        
        // Simpan email target ke dalam session sementara
        $_SESSION['forgot_email'] = $user['email'];
        
        $success = "Akun ditemukan! <br><strong>[SIMULASI OTP]:</strong> Kode keamanan Anda adalah <span class='badge bg-dark fs-6'>$otp</span>.<br>Silakan masukkan kode tersebut pada kolom di bawah ini.";
        $show_otp_input = true; // Nyalakan saklar input OTP
    } else { 
        $error = "Maaf, akun dengan Email atau NPM tersebut tidak terdaftar di sistem."; 
    }
}

// --- 2. PROSES TAHAP KEDUA: PENGGUNA MEMASUKKAN KODE OTP NYATA ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_otp_code'])) {
    $otp_input = trim($_POST['otp_digit']);
    $email_target = $_SESSION['forgot_email'] ?? '';

    // Tetap munculkan form OTP jika proses verifikasi ditekan agar tidak hilang penampakannya
    $show_otp_input = true; 

    if (!empty($email_target) && !empty($otp_input)) {
        try {
            $stmt = $pdo->prepare("SELECT * FROM verifikasi_otp WHERE email = ? AND kode_otp = ? ORDER BY id_otp DESC LIMIT 1");
            $stmt->execute([$email_target, $otp_input]);
            $otp_match = $stmt->fetch();

            if ($otp_match) {
                // Ambil profil user untuk langsung login bypass masuk dashboard
                $stmtProfile = $pdo->prepare("SELECT * FROM users WHERE email = ?");
                $stmtProfile->execute([$email_target]);
                $user = $stmtProfile->fetch();

                if ($user) {
                    $_SESSION['id_user']  = $user['id'];
                    $_SESSION['username'] = $user['nama_lengkap']; 
                    $_SESSION['role']     = $user['role'];

                    // Bersihkan log OTP dan session penampung
                    $pdo->prepare("DELETE FROM verifikasi_otp WHERE email = ?")->execute([$email_target]);
                    unset($_SESSION['forgot_email']);

                    $success = "Verifikasi Sukses! Selamat datang kembali " . htmlspecialchars($user['nama_lengkap']) . ". Masuk ke sistem...";
                    
                    // Alihkan halaman secara instan menggunakan JavaScript murni
                    if ($user['role'] === 'admin') {
                        echo "<script>setTimeout(function(){ window.location.href = '../admin/dashboard.php'; }, 1500);</script>";
                    } else {
                        echo "<script>setTimeout(function(){ window.location.href = '../index.php'; }, 1500);</script>";
                    }
                    exit;
                }
            } else {
                $error = "Kode Keamanan OTP yang Anda masukkan salah atau kedaluwarsa!";
            }
        } catch (PDOException $e) {
            $error = "Eror Sistem: " . $e->getMessage();
        }
    } else {
        $error = "Sesi habis atau kode belum diisi. Silakan minta OTP kembali.";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lupa Kata Sandi - E-Kantin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex align-items-center" style="height: 100vh;">
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-4">
            <div class="card border-0 shadow p-4 rounded-4">
                <h4 class="fw-bold mb-1">Lupa Kata Sandi?</h4>
                <p class="text-muted small mb-4">Verifikasi data akun Anda untuk masuk kembali ke aplikasi</p>
                
                <?php if($error): ?><div class="alert alert-danger border-0 small py-2"><?= $error ?></div><?php endif; ?>
                <?php if($success): ?><div class="alert alert-success border-0 small py-2"><?= $success ?></div><?php endif; ?>
                
                <form action="" method="POST" class="mb-3">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Email Student / Gmail / NPM</label>
                        <input type="text" name="email" class="form-control" placeholder="Contoh: 24081010008" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" <?= $show_otp_input ? 'readonly' : '' ?> required>
                    </div>
                    
                    <?php if (!$show_otp_input): ?>
                        <button type="submit" name="request_otp" class="btn btn-danger w-100 rounded-pill py-2 fw-bold shadow-sm">Kirim Kode OTP</button>
                    <?php endif; ?>
                </form>

                <?php if ($show_otp_input): ?>
                    <hr class="text-muted my-3">
                    <form action="" method="POST">
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-danger text-center d-block">Masukkan 6 Digit OTP Kamu</label>
                            <input type="text" name="otp_digit" class="form-control text-center fw-bold fs-4" placeholder="000000" maxlength="6" style="letter-spacing: 0.3rem;" required autocomplete="off">
                        </div>
                        <button type="submit" name="submit_otp_code" class="btn btn-dark w-100 rounded-pill py-2 fw-bold shadow-sm mb-2">Verifikasi & Masuk Instan</button>
                        <div class="text-center"><a href="forgot_password.php" class="small text-decoration-none text-muted">Minta kode baru lagi</a></div>
                    </form>
                <?php endif; ?>

                <?php if (!$show_otp_input): ?>
                    <div class="text-center mt-2"><a href="login.php" class="small text-decoration-none">Kembali ke Login</a></div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
</body>
</html>