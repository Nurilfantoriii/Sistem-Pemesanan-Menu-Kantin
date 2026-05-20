<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') { header("Location: ../auth/login.php"); exit; }
require_once '../config/koneksi.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama_menu']);
    $kategori = $_POST['kategori'];
    $harga = intval($_POST['harga']);
    $stok = intval($_POST['stok']);

    if (empty($nama) || empty($kategori) || $harga <= 0 || $stok < 0) {
        $error = "Semua input harus diisi dengan benar!";
    } else {
        $stmt = $pdo->prepare("INSERT INTO menu (nama_menu, kategori, harga, stok) VALUES (?, ?, ?, ?)");
        $stmt->execute([$nama, $kategori, $harga, $stok]);
        header("Location: dashboard.php");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Menu - E-Kantin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
</head>
<body class="bg-light d-flex align-items-center" style="min-height: 100vh;">
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm p-3 rounded-4">
                <div class="card-body">
                    <h4 class="fw-bold text-dark mb-4"><i class="bi bi-plus-circle-fill text-primary me-2"></i>Tambah Menu Baru</h4>
                    
                    <?php if($error): ?>
                        <div class="alert alert-danger border-0 shadow-sm rounded-3"><?= $error ?></div>
                    <?php endif;?>

                    <form action="" method="POST">
                        <div class="mb-3">
                            <label class="form-label fw-semibold text-secondary">Nama Menu Makanan/Minuman</label>
                            <input type="text" name="nama_menu" class="form-control rounded-3" placeholder="Contoh: Es Teh Jeruk" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold text-secondary">Kategori</label>
                            <select name="kategori" class="form-select rounded-3">
                                <option>Makanan</option>
                                <option>Minuman</option>
                                <option>Cemilan</option>
                            </select>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold text-secondary">Harga (Rp)</label>
                                <input type="number" name="harga" class="form-control rounded-3" placeholder="0" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold text-secondary">Stok Porsi</label>
                                <input type="number" name="stok" class="form-control rounded-3" placeholder="0" required>
                            </div>
                        </div>
                        <div class="d-flex gap-2 mt-3">
                            <button type="submit" class="btn btn-primary rounded-pill px-4 shadow-sm">Simpan Menu</button>
                            <a href="dashboard.php" class="btn btn-light rounded-pill px-4 border">Batal</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>