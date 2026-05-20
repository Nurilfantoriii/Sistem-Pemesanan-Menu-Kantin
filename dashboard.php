<?php
session_start();
// Proteksi Halaman: Pastikan hanya user biasa yang bisa melihat katalog
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'user') { 
    header("Location: ../auth/login.php"); 
    exit; 
}
require_once '../config/koneksi.php';

$success = ''; $error = '';
$id_user_aktif = isset($_SESSION['id_user']) ? $_SESSION['id_user'] : (isset($_SESSION['id']) ? $_SESSION['id'] : 0);

// PROSES A: Membuat Pesanan Baru (Create Operation)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['beli'])) {
    $id_menu = intval($_POST['id_menu']);
    $jumlah = intval($_POST['jumlah']);
    $metode = $_POST['metode_pembayaran'];

    $valid_payments = ['Cash', 'QRIS', 'DANA', 'GoPay', 'ShopeePay', 'Bank BNI', 'Bank BRI'];

    if ($jumlah <= 0) {
        $error = "Jumlah pesanan minimal 1 porsi.";
    } elseif (!in_array($metode, $valid_payments)) {
        $error = "Metode pembayaran tidak valid.";
    } else {
        // Ambil detail menu
        $stmt = $pdo->prepare("SELECT * FROM menu WHERE id_menu = ?");
        $stmt->execute([$id_menu]);
        $menu = $stmt->fetch();

        if ($menu && $menu['stok'] >= $jumlah) {
            $total_harga = $menu['harga'] * $jumlah;
            
            // 1. Simpan transaksi ke tabel pesanan
            $stmtOrder = $pdo->prepare("INSERT INTO pesanan (id_user, id_menu, jumlah, total_harga, metode_pembayaran) VALUES (?, ?, ?, ?, ?)");
            $stmtOrder->execute([$id_user_aktif, $id_menu, $jumlah, $total_harga, $metode]);

            // 2. Potong stok produk di menu
            $stokBaru = $menu['stok'] - $jumlah;
            $stmtUpdateStok = $pdo->prepare("UPDATE menu SET stok = ? WHERE id_menu = ?");
            $stmtUpdateStok->execute([$stokBaru, $id_menu]);

            $success = "Hore! Pesanan '" . htmlspecialchars($menu['nama_menu']) . "' berhasil dipesan menggunakan metode [$metode].";
        } else {
            $error = "Maaf, stok porsi makanan ini sudah habis.";
        }
    }
}

// PROSES B: Pembatalan Pesanan (Kembalikan Stok Saja)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['batalkan_pesanan'])) {
    $id_pesanan = intval($_POST['id_pesanan']);

    $stmtCek = $pdo->prepare("SELECT * FROM pesanan WHERE id_pesanan = ? AND id_user = ?");
    $stmtCek->execute([$id_pesanan, $id_user_aktif]);
    $pesanan = $stmtCek->fetch();

    if ($pesanan && $pesanan['status_pesanan'] === 'Pending') {
        // 1. Ubah status pesanan menjadi 'Dibatalkan'
        $stmtBatal = $pdo->prepare("UPDATE pesanan SET status_pesanan = 'Dibatalkan' WHERE id_pesanan = ?");
        $stmtBatal->execute([$id_pesanan]);

        // 2. Kembalikan stok menu ke tabel menu
        $stmtMenu = $pdo->prepare("SELECT stok FROM menu WHERE id_menu = ?");
        $stmtMenu->execute([$pesanan['id_menu']]);
        $menu = $stmtMenu->fetch();
        if ($menu) {
            $stmtRestock = $pdo->prepare("UPDATE menu SET stok = ? WHERE id_menu = ?");
            $stmtRestock->execute([$menu['stok'] + $pesanan['jumlah'], $pesanan['id_menu']]);
        }

        $success = "Pesanan berhasil dibatalkan. Stok porsi makanan otomatis dikembalikan!";
    } else {
        $error = "Gagal membatalkan. Pesanan sudah terlanjur diproses oleh kantin.";
    }
}

$menus = $pdo->query("SELECT * FROM menu WHERE stok > 0 ORDER BY nama_menu ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu E-Kantin Mahasiswa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        .navbar-user { background: linear-gradient(135deg, #0d6efd 0%, #0b5ed7 100%); }
        .menu-card { border: none; border-radius: 16px; transition: transform 0.2s, box-shadow 0.2s; }
        .menu-card:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.08) !important; }
    </style>
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark navbar-user sticky-top mb-4 shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-bold" href="#"><i class="bi bi-cup-hot-fill me-2"></i>E-Kantin UPNVJ</a>
        <div class="ms-auto d-flex align-items-center gap-3">
            <span class="text-light"><i class="bi bi-person-fill me-1"></i> Halo, <?= htmlspecialchars($_SESSION['username']) ?></span>
            <a href="../auth/logout.php" class="btn btn-light btn-sm rounded-pill px-3 text-primary fw-medium">Keluar</a>
        </div>
    </div>
</nav>

<div class="container mb-5">
    <div class="p-4 p-md-5 mb-4 rounded-4 text-bg-dark shadow-sm" style="background: linear-gradient(rgba(0,0,0,0.4), rgba(0,0,0,0.4)), url('https://images.unsplash.com/photo-1555396273-367ea4eb4db5?q=80&w=1000&auto=format&fit=crop'); background-size: cover; background-position: center;">
        <h1 class="display-6 fw-bold">Lapar di Sela Kuliah?</h1>
        <p class="lead my-2">Pesan online dari kelas, bayar pakai QRIS, E-Wallet, Transfer Bank, atau Cash langsung di meja kasir.</p>
    </div>

    <?php if($success): ?><div class="alert alert-success border-0 shadow-sm rounded-3 mb-4"><i class="bi bi-check-circle-fill me-2"></i><?= $success ?></div><?php endif; ?>
    <?php if($error): ?><div class="alert alert-danger border-0 shadow-sm rounded-3 mb-4"><i class="bi bi-exclamation-triangle-fill me-2"></i><?= $error ?></div><?php endif; ?>

    <h4 class="fw-bold text-dark mb-4"><i class="bi bi-grid-fill text-primary me-2"></i>Katalog Menu Hari Ini</h4>
    <div class="row g-4">
        <?php foreach ($menus as $m): ?>
        <div class="col-sm-6 col-md-4 col-lg-3">
            <div class="card h-100 shadow-sm menu-card">
                <div class="card-body d-flex flex-column justify-content-between p-4">
                    <div>
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <span class="badge bg-secondary-subtle text-secondary rounded-pill px-2.5 py-1" style="font-size: 0.75rem;"><?= $m['kategori'] ?></span>
                            <span class="text-muted" style="font-size: 0.8rem;">Stok: <?= $m['stok'] ?></span>
                        </div>
                        <h5 class="card-title fw-bold text-dark mb-1"><?= htmlspecialchars($m['nama_menu']) ?></h5>
                        <p class="text-primary fw-bold fs-5 mb-3">Rp <?= number_format($m['harga'], 0, ',', '.') ?></p>
                    </div>
                    
                    <form action="" method="POST" class="mt-2">
                        <input type="hidden" name="id_menu" value="<?= $m['id_menu'] ?>">
                        <div class="input-group input-group-sm mb-2 rounded-3 overflow-hidden border">
                            <span class="input-group-text bg-white border-0 text-secondary" style="font-size: 0.75rem;">Qty</span>
                            <input type="number" name="jumlah" value="1" min="1" max="<?= $m['stok'] ?>" class="form-control border-0 text-center fw-bold">
                        </div>
                        
                        <div class="mb-3">
                            <select name="metode_pembayaran" class="form-select form-select-sm rounded-3 text-dark fw-medium" style="font-size: 0.8rem;" required>
                                <option value="Cash">💵 Tunai / Cash di Kasir</option>
                                <option value="QRIS">📱 QRIS (All Payment)</option>
                                <option value="DANA">🔴 DANA</option>
                                <option value="GoPay">🟢 GoPay</option>
                                <option value="ShopeePay">🟠 ShopeePay</option>
                                <option value="Bank BNI">🏦 Bank BNI</option>
                                <option value="Bank BRI">🏦 Bank BRI</option>
                            </select>
                        </div>
                        <button type="submit" name="beli" class="btn btn-primary btn-sm w-100 rounded-pill py-2 shadow-sm"><i class="bi bi-bag-plus me-1"></i> Pesan Menu</button>
                    </form>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <hr class="my-5">
    <h4 class="fw-bold text-dark mb-4"><i class="bi bi-clock-history text-success me-2"></i>Riwayat Pesanan Anda</h4>
    <div class="card border-0 shadow-sm p-4 rounded-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle m-0">
                <thead class="table-light">
                    <tr>
                        <th class="border-0">Waktu</th>
                        <th class="border-0">Nama Menu</th>
                        <th class="border-0 text-center">Jumlah</th>
                        <th class="border-0">Total Harga</th>
                        <th class="border-0 text-center">Metode Pembayaran</th>
                        <th class="border-0 text-center">Status</th>
                        <th class="border-0 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $myOrders = $pdo->prepare("SELECT p.*, m.nama_menu FROM pesanan p JOIN menu m ON p.id_menu = m.id_menu WHERE p.id_user = ? ORDER BY p.tanggal_pesanan DESC");
                    $myOrders->execute([$id_user_aktif]);
                    $riwayat = $myOrders->fetchAll();
                    
                    if(empty($riwayat)): ?>
                        <tr><td colspan="7" class="text-center py-4 text-muted">Kamu belum melakukan pemesanan makanan hari ini.</td></tr>
                    <?php endif;

                    foreach ($riwayat as $r): 
                        $badgeColor = 'bg-warning text-dark'; 
                        if ($r['status_pesanan'] === 'Memasak') $badgeColor = 'bg-info text-white';
                        if ($r['status_pesanan'] === 'Selesai') $badgeColor = 'bg-success text-white';
                        if ($r['status_pesanan'] === 'Dibatalkan') $badgeColor = 'bg-danger text-white';
                        
                        $payIcon = 'bi-credit-card text-primary';
                        if ($r['metode_pembayaran'] === 'Cash') $payIcon = 'bi-cash text-success';
                        if ($r['metode_pembayaran'] === 'QRIS') $payIcon = 'bi-qr-code-scan text-dark';
                        if (in_array($r['metode_pembayaran'], ['DANA', 'GoPay', 'ShopeePay'])) $payIcon = 'bi-phone-vibrate text-info';
                        if (strpos($r['metode_pembayaran'], 'Bank') !== false) $payIcon = 'bi-bank text-danger';
                    ?>
                    <tr>
                        <td class="text-secondary" style="font-size: 0.85rem;"><?= date('d M, H:i', strtotime($r['tanggal_pesanan'])) ?></td>
                        <td class="fw-semibold text-dark"><?= htmlspecialchars($r['nama_menu']) ?></td>
                        <td class="text-center"><?= $r['jumlah'] ?></td>
                        <td class="fw-bold text-dark">Rp <?= number_format($r['total_harga'], 0, ',', '.') ?></td>
                        <td class="text-center fw-medium"><i class="bi <?= $payIcon ?> me-1"></i><?= $r['metode_pembayaran'] ?></td>
                        <td class="text-center"><span class="badge <?= $badgeColor ?> rounded-pill px-3 py-1.5"><?= $r['status_pesanan'] ?></span></td>
                        <td class="text-center">
                            <?php if ($r['status_pesanan'] === 'Pending'): ?>
                                <form action="" method="POST" onsubmit="return confirm('Batalkan pesanan ini?')">
                                    <input type="hidden" name="id_pesanan" value="<?= $r['id_pesanan'] ?>">
                                    <button type="submit" name="batalkan_pesanan" class="btn btn-outline-danger btn-sm rounded-pill px-3">Batal</button>
                                </form>
                            <?php else: ?><span class="text-muted" style="font-size: 0.8rem;">- Locked -</span><?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>