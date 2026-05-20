<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}
require_once '../config/koneksi.php';

// PROSES A: Update Status Pesanan Toko
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $id_pesanan = intval($_POST['id_pesanan']);
    $status_baru = $_POST['status_baru'];

    if (in_array($status_baru, ['Pending', 'Memasak', 'Selesai', 'Dibatalkan'])) {
        $stmtUpdate = $pdo->prepare("UPDATE pesanan SET status_pesanan = ? WHERE id_pesanan = ?");
        $stmtUpdate->execute([$status_baru, $id_pesanan]);
        header("Location: dashboard.php");
        exit;
    }
}

// PROSES B: Sembunyikan/Arsip Riwayat Pesanan (Agar list bersih tapi PENDAPATAN TIDAK BERKURANG)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hapus_pesanan'])) {
    $id_pesanan = intval($_POST['id_pesanan']);
    
    $stmtCekStatus = $pdo->prepare("SELECT status_pesanan FROM pesanan WHERE id_pesanan = ?");
    $stmtCekStatus->execute([$id_pesanan]);
    $cek = $stmtCekStatus->fetch();

    if ($cek) {
        // Jika diselesaikan lalu dihapus, ubah statusnya menjadi 'Selesai & Diarsip'
        if ($cek['status_pesanan'] === 'Selesai') {
            $stmtArsip = $pdo->prepare("UPDATE pesanan SET status_pesanan = 'Selesai & Diarsip' WHERE id_pesanan = ?");
            $stmtArsip->execute([$id_pesanan]);
        } 
        // Jika dibatalkan lalu dihapus, ubah statusnya menjadi 'Batal & Diarsip'
        elseif ($cek['status_pesanan'] === 'Dibatalkan') {
            $stmtArsip = $pdo->prepare("UPDATE pesanan SET status_pesanan = 'Batal & Diarsip' WHERE id_pesanan = ?");
            $stmtArsip->execute([$id_pesanan]);
        }
    }
    header("Location: dashboard.php");
    exit;
}

// Tarik data menu kantin
$menus = $pdo->query("SELECT * FROM menu ORDER BY id_menu DESC")->fetchAll();

// HITUNG TOTAL PENDAPATAN: Mengambil semua data pesanan (termasuk yang sudah diarsip agar uangnya tetap utuh)
$querySemuaPesanan = "SELECT status_pesanan, total_harga FROM pesanan";
$semuaPesanan = $pdo->query($querySemuaPesanan)->fetchAll();
$totalPendapatan = 0;
foreach ($semuaPesanan as $p) { 
    // Pendapatan dihitung dari yang berstatus Selesai, Memasak, Pending, atau Selesai & Diarsip
    if (!in_array($p['status_pesanan'], ['Dibatalkan', 'Batal & Diarsip'])) { 
        $totalPendapatan += $p['total_harga']; 
    }
}

// TAMPILAN LIST KANAN: Hanya memunculkan pesanan aktif (Pending, Memasak, Selesai, Dibatalkan) 
// Pesanan yang berstatus '& Diarsip' otomatis tidak akan dimunculkan lagi di sini
$queryPesananTampil = "SELECT p.*, m.nama_menu, u.nama_lengkap FROM pesanan p 
                       JOIN menu m ON p.id_menu = m.id_menu 
                       JOIN users u ON p.id_user = u.id 
                       WHERE p.status_pesanan IN ('Pending', 'Memasak', 'Selesai', 'Dibatalkan')
                       ORDER BY p.tanggal_pesanan DESC";
$pesanans = $pdo->query($queryPesananTampil)->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - E-Kantin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        .navbar-gradient { background: linear-gradient(135deg, #212529 0%, #343a40 100%); }
        .card-summary-1 { background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%); color: white; }
        .card-summary-2 { background: linear-gradient(135deg, #198754 0%, #146c43 100%); color: white; }
        .card-summary-3 { background: linear-gradient(135deg, #ffc107 0%, #ca9300 100%); color: white; }
        .table-container { background: white; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
    </style>
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark navbar-gradient sticky-top mb-4">
    <div class="container">
        <a class="navbar-brand fw-bold" href="#"><i class="bi bi-shop-window me-2"></i>E-Kantin Admin</a>
        <div class="ms-auto">
            <span class="text-light me-3"><i class="bi bi-person-circle me-1"></i> <?= htmlspecialchars($_SESSION['username']) ?></span>
            <a href="../auth/logout.php" class="btn btn-outline-danger btn-sm rounded-pill px-3">Keluar</a>
        </div>
    </div>
</nav>

<div class="container mb-5">
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card card-summary-1 border-0 shadow-sm p-3 rounded-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div><h6 class="text-light text-opacity-75 mb-1">Total Variasi Menu</h6><h2 class="fw-bold mb-0"><?= count($menus) ?> Items</h2></div>
                    <i class="bi bi-egg-fried fs-1 text-light text-opacity-50"></i>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-summary-2 border-0 shadow-sm p-3 rounded-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div><h6 class="text-light text-opacity-75 mb-1">Total Pendapatan</h6><h2 class="fw-bold mb-0">Rp <?= number_format($totalPendapatan, 0, ',', '.') ?></h2></div>
                    <i class="bi bi-wallet2 fs-1 text-light text-opacity-50"></i>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-summary-3 border-0 shadow-sm p-3 rounded-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div><h6 class="text-light text-opacity-75 mb-1">Total Transaksi Aktif</h6><h2 class="fw-bold mb-0"><?= count($pesanans) ?> Antrean</h2></div>
                    <i class="bi bi-cart-check fs-1 text-light text-opacity-50"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="p-4 table-container border-0">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold m-0 text-dark"><i class="bi bi-list-stars me-2 text-primary"></i>Daftar Menu Kantin</h5>
                    <a href="menu_create.php" class="btn btn-primary btn-sm rounded-pill px-3 shadow-sm">Tambah Menu</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle m-0">
                        <thead class="table-light">
                            <tr>
                                <th>Nama Menu</th>
                                <th>Kategori</th>
                                <th>Harga</th>
                                <th class="text-center">Stok</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($menus as $m): ?>
                            <tr>
                                <td class="fw-semibold text-secondary"><?= htmlspecialchars($m['nama_menu']) ?></td>
                                <td><span class="badge bg-light text-dark border rounded-pill px-2"><?= $m['kategori'] ?></span></td>
                                <td class="text-primary fw-medium">Rp <?= number_format($m['harga'], 0, ',', '.') ?></td>
                                <td class="text-center"><?= $m['stok'] ?></td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm">
                                        <a href="menu_edit.php?id=<?= $m['id_menu'] ?>" class="btn btn-outline-warning"><i class="bi bi-pencil-square"></i></a>
                                        <a href="menu_delete.php?id=<?= $m['id_menu'] ?>" class="btn btn-outline-danger" onclick="return confirm('Yakin hapus menu?')"><i class="bi bi-trash"></i></a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="p-4 table-container border-0">
                <h5 class="fw-bold mb-3 text-dark"><i class="bi bi-bell me-2 text-success"></i>Pesanan & Pembayaran Masuk</h5>
                <div class="overflow-auto" style="max-height: 520px;">
                    <div class="list-group list-group-flush">
                        <?php 
                        if (empty($pesanans)): ?>
                            <div class="text-center py-4 text-muted">Belum ada pesanan aktif saat ini.</div>
                        <?php endif;

                        foreach ($pesanans as $p): 
                            $borderColor = 'border-warning';
                            if ($p['status_pesanan'] === 'Memasak') $borderColor = 'border-info';
                            if ($p['status_pesanan'] === 'Selesai') $borderColor = 'border-success';
                            if ($p['status_pesanan'] === 'Dibatalkan') $borderColor = 'border-danger';
                            
                            $payBadge = 'bg-dark-subtle text-dark';
                            if ($p['metode_pembayaran'] === 'Cash') $payBadge = 'bg-success-subtle text-success';
                            if (in_array($p['metode_pembayaran'], ['QRIS', 'DANA', 'GoPay', 'ShopeePay'])) $payBadge = 'bg-info-subtle text-info';
                            if (strpos($p['metode_pembayaran'], 'Bank') !== false) $payBadge = 'bg-danger-subtle text-danger';
                        ?>
                            <div class="list-group-item px-3 py-3 border rounded-3 mb-2 <?= $borderColor ?>" style="border-width: 2px !important;">
                                <div class="d-flex w-100 justify-content-between align-items-center mb-1">
                                    <h6 class="mb-0 fw-bold text-dark"><?= htmlspecialchars($p['nama_menu']) ?> <span class="text-secondary">x<?= $p['jumlah'] ?></span></h6>
                                    <span class="badge <?= $payBadge ?> rounded-pill" style="font-size: 0.7rem;"><?= $p['metode_pembayaran'] ?></span>
                                </div>
                                <p class="mb-2 text-secondary" style="font-size: 0.85rem;"><i class="bi bi-person me-1"></i> Pemesan: **<?= htmlspecialchars($p['nama_lengkap']) ?>**</p>
                                
                                <div class="d-flex justify-content-between align-items-center mt-3 pt-2 border-top">
                                    <span class="text-success fw-bold">Rp <?= number_format($p['total_harga'], 0, ',', '.') ?></span>
                                    
                                    <div class="d-flex gap-1 align-items-center">
                                        <form action="" method="POST" class="d-flex gap-1 align-items-center m-0">
                                            <input type="hidden" name="id_pesanan" value="<?= $p['id_pesanan'] ?>">
                                            <select name="status_baru" class="form-select form-select-sm rounded-pill" style="font-size: 0.8rem; width: 110px;">
                                                <option value="Pending" <?= $p['status_pesanan'] == 'Pending' ? 'selected' : '' ?>>Pending</option>
                                                <option value="Memasak" <?= $p['status_pesanan'] == 'Memasak' ? 'selected' : '' ?>>Memasak</option>
                                                <option value="Selesai" <?= $p['status_pesanan'] == 'Selesai' ? 'selected' : '' ?>>Selesai</option>
                                                <option value="Dibatalkan" <?= $p['status_pesanan'] == 'Dibatalkan' ? 'selected' : '' ?>>Dibatalkan</option>
                                            </select>
                                            <button type="submit" name="update_status" class="btn btn-dark btn-sm rounded-circle" title="Update Status"><i class="bi bi-check-lg" style="font-size: 0.75rem;"></i></button>
                                        </form>

                                        <?php if ($p['status_pesanan'] === 'Selesai' || $p['status_pesanan'] === 'Dibatalkan'): ?>
                                            <form action="" method="POST" class="m-0" onsubmit="return confirm('Bersihkan pesanan ini dari antrean? (Pendapatan Anda tetap akan disimpan aman)')">
                                                <input type="hidden" name="id_pesanan" value="<?= $p['id_pesanan'] ?>">
                                                <button type="submit" name="hapus_pesanan" class="btn btn-outline-secondary btn-sm rounded-circle" title="Bersihkan List"><i class="bi bi-eye-slash-fill" style="font-size: 0.72rem;"></i></button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>