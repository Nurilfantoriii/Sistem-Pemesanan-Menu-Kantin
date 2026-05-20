<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') { header("Location: ../auth/login.php"); exit; }
require_once '../config/koneksi.php';

$id = $_GET['id'] ?? null;
if (!$id) { header("Location: dashboard.php"); exit; }

$stmt = $pdo->prepare("SELECT * FROM menu WHERE id_menu = ?");
$stmt->execute([$id]);
$menu = $stmt->fetch();
if (!$menu) { header("Location: dashboard.php"); exit; }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama_menu']);
    $kategori = $_POST['kategori'];
    $harga = intval($_POST['harga']);
    $stok = intval($_POST['stok']);

    if (empty($nama) || empty($kategori) || $harga <= 0 || $stok < 0) {
        $error = "Input tidak boleh kosong / tidak valid.";
    } else {
        $stmt = $pdo->prepare("UPDATE menu SET nama_menu = ?, kategori = ?, harga = ?, stok = ? WHERE id_menu = ?");
        $stmt->execute([$nama, $kategori, $harga, $stok, $id]);
        header("Location: dashboard.php");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8"><title>Edit Menu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light"><div class="container mt-5"><div class="col-md-6 mx-auto card shadow"><div class="card-body">
    <h5>Edit Menu</h5>
    <?php if($error): ?><div class="alert alert-danger"><?=$error?></div><?php endif;?>
    <form action="" method="POST">
        <div class="mb-3"><label class="form-label">Nama Menu</label><input type="text" name="nama_menu" value="<?=htmlspecialchars($menu['nama_menu'])?>" class="form-control"></div>
        <div class="mb-3"><label class="form-label">Kategori</label><select name="kategori" class="form-select"><option <?=$menu['kategori']=='Makanan'?'selected':''?>>Makanan</option><option <?=$menu['kategori']=='Minuman'?'selected':''?>>Minuman</option><option <?=$menu['kategori']=='Cemilan'?'selected':''?>>Cemilan</option></select></div>
        <div class="mb-3"><label class="form-label">Harga</label><input type="number" name="harga" value="<?=$menu['harga']?>" class="form-control"></div>
        <div class="mb-3"><label class="form-label">Stok</label><input type="number" name="stok" value="<?=$menu['stok']?>" class="form-control"></div>
        <button type="submit" class="btn btn-warning">Update</button>
        <a href="dashboard.php" class="btn btn-secondary">Batal</a>
    </form>
</div></div></div></body>
</html>