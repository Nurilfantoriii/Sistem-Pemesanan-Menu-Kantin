<?php
session_start();
require_once '../config/koneksi.php';

if (!isset($_SESSION['verify_email'])) { 
    header("Location: forgot_password.php"); 
    exit; 
}

$error = ''; $success = ''; 
$email_otomatis = $_SESSION['verify_email'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify_otp_button'])) {
    $otp_input = trim($_POST['otp']);

    try {
        $stmt = $pdo->prepare("SELECT * FROM verifikasi_otp WHERE email = ? AND kode_otp = ? ORDER BY id_otp DESC LIMIT 1");
        $stmt->execute([$email_otomatis, $otp_input]);
        $otp_match = $stmt->fetch();

        if ($otp_match) {
            // Tarik data profil pengguna untuk bypass login langsung
            $stmtProfile = $pdo->prepare("SELECT * FROM users WHERE email = ?");
            $stmtProfile->execute([$email_otomatis]);
            $user = $stmtProfile->fetch();

            if ($user) {
                // Set Session Login Sukses
                $_SESSION['id_user']  = $user['id'];
                $_SESSION['username'] = $user['nama_lengkap']; 
                $_SESSION['role']     = $user['role'];

                // Bersihkan OTP
                $pdo->prepare("DELETE FROM verifikasi_otp WHERE email = ?")->execute([$email_otomatis]);
                unset($_SESSION['verify_email']);

                $success = "Akses Diberikan! Selamat datang kembali " . htmlspecialchars($user['nama_lengkap']) . ". Mengalihkan...";
                
                if ($user['role'] === 'admin') {
                    echo "<script>setTimeout(function(){ window.location.href = '../admin/dashboard.php'; }, 1500);</script>";
                } else {
                    echo "<script>setTimeout(function(){ window.location.href = '../index.php'; }, 1500);</script>";
                }
                exit;
            }
        } else {
            $error = "Kode OTP salah atau sudah kedaluwarsa!";
        }
    } catch (PDOException $e) {
        $error = "Eror Sistem: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi OTP Reset - E-Kantin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex align-items-center" style="height: 100vh;">
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-4">
            <div class="card border-0 shadow p-4 rounded-4 text-center">
                <h4 class="fw-bold mb-1">Verifikasi Keamanan</h4>
                <p class="text-muted small mb-4">Memulihkan Akun: <br><span class="text-danger fw-bold"><?= htmlspecialchars($email_otomatis) ?></span></p>
                
                <?php if($error): ?><div class="alert alert-danger border-0 small py-2 text-start"><?= $error ?></div><?php endif; ?>
                <?php if($success): ?><div class="alert alert-success border-0 small py-2"><?= $success ?></div><?php endif; ?>

                <form action="" method="POST">
                    <div class="mb-4">
                        <label class="form-label small d-block text-start fw-bold">Masukkan 6 Digit OTP</label>
                        <input type="text" name="otp" class="form-control text-center fw-bold fs-4" placeholder="000000" maxlength="6" style="letter-spacing:0.4rem;" required>
                    </div>
                    <button type="submit" name="verify_otp_button" class="btn btn-dark w-100 rounded-pill py-2 fw-bold">Verifikasi & Masuk Instan</button>
                </form>
            </div>
        </div>
    </div>
</div>
</body>
</html>