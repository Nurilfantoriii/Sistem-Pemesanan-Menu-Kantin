<?php
session_start();
require_once '../config/koneksi.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['username'])) {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (empty($username) || empty($password)) {
        $error = "Username dan Password wajib diisi!";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        // Menggunakan pencocokan string langsung (Plain Text) sesuai settingan terakhir kamu
        if ($user && $password === $user['password']) {
            $_SESSION['id_user']  = $user['id']; // Mengunci ID User ke session
            $_SESSION['username'] = $user['nama_lengkap']; 
            $_SESSION['role']     = $user['role'];

            header("Location: ../index.php");
            exit;
        } else {
            $error = "Username atau Password salah.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistem Kantin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex align-items-center" style="height: 100vh;">
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-4">
            <div class="card shadow border-0 rounded-3">
                <div class="card-body p-4">
                    <h3 class="card-title text-center mb-4 fw-bold">Login E-Kantin</h3>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger border-0 shadow-sm rounded-3 py-2"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>
                    
                    <form action="" method="POST">
                        <div class="mb-3">
                            <label class="form-label fw-medium">Username</label>
                            <input type="text" name="username" class="form-control" placeholder="Ketik admin atau user" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-medium">Password</label>
                            <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100 py-2 rounded-3 fw-bold">Masuk</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>