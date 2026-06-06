<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}
require_once '../config/koneksi.php';

// PROSES A: Update Status Pesanan (Pending -> Memasak -> Selesai -> Dibatalkan)
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

// PROSES B: Bersihkan/Arsip Riwayat Pesanan dari List Toko (Pendapatan Tetap Aman Terkap)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hapus_pesanan'])) {
    $id_pesanan = intval($_POST['id_pesanan']);
    
    $stmtCekStatus = $pdo->prepare("SELECT status_pesanan FROM pesanan WHERE id_pesanan = ?");
    $stmtCekStatus->execute([$id_pesanan]);
    $cek = $stmtCekStatus->fetch();

    if ($cek) {
        if ($cek['status_pesanan'] === 'Selesai') {
            $pdo->prepare("UPDATE pesanan SET status_pesanan = 'Selesai & Diarsip' WHERE id_pesanan = ?")->execute([$id_pesanan]);
        } elseif ($cek['status_pesanan'] === 'Dibatalkan') {
            $pdo->prepare("UPDATE pesanan SET status_pesanan = 'Batal & Diarsip' WHERE id_pesanan = ?")->execute([$id_pesanan]);
        }
    }
    header("Location: dashboard.php");
    exit;
}

// Tarik data menu kantin (Kolom Kiri)
$menus = $pdo->query("SELECT * FROM menu ORDER BY id_menu DESC")->fetchAll();

// STATISTIK: Hitung laporan keuangan otomatis (Termasuk yang diarsip agar data akurat)
$rekapHarian = $pdo->query("SELECT SUM(total_harga) as total FROM pesanan WHERE status_pesanan NOT IN ('Dibatalkan', 'Batal & Diarsip') AND DATE(tanggal_pesanan) = CURRENT_DATE")->fetch()['total'] ?? 0;
$rekapMingguan = $pdo->query("SELECT SUM(total_harga) as total FROM pesanan WHERE status_pesanan NOT IN ('Dibatalkan', 'Batal & Diarsip') AND YEARWEEK(tanggal_pesanan, 1) = YEARWEEK(CURDATE(), 1)")->fetch()['total'] ?? 0;
$rekapBulanan = $pdo->query("SELECT SUM(total_harga) as total FROM pesanan WHERE status_pesanan NOT IN ('Dibatalkan', 'Batal & Diarsip') AND MONTH(tanggal_pesanan) = MONTH(CURDATE()) AND YEAR(tanggal_pesanan) = YEAR(CURDATE())")->fetch()['total'] ?? 0;
$rekapTahunan = $pdo->query("SELECT SUM(total_harga) as total FROM pesanan WHERE status_pesanan NOT IN ('Dibatalkan', 'Batal & Diarsip') AND YEAR(tanggal_pesanan) = YEAR(CURDATE())")->fetch()['total'] ?? 0;

// TAMPILAN LIST KANAN: Hanya tampilkan pesanan aktif di antrean toko kasir
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
        .table-container { background: white; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
    </style>
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark navbar-gradient sticky-top mb-4">
    <div class="container">
        <a class="navbar-brand fw-bold" href="#"><i class="bi bi-shop-window me-2"></i>E-Kantin Admin Master</a>
        <div class="ms-auto">
            <span class="text-light me-3"><i class="bi bi-person-circle me-1"></i> <?= htmlspecialchars($_SESSION['username']) ?></span>
            <a href="../auth/logout.php" class="btn btn-outline-danger btn-sm rounded-pill px-3">Keluar</a>
        </div>
    </div>
</nav>

<div class="container mb-5">
    <div class="card border-0 shadow-sm p-4 rounded-4 mb-4 bg-white">
        <h5 class="fw-bold mb-3 text-dark"><i class="bi bi-graph-up-arrow text-primary me-2"></i>Rekapitulasi Statistik Finansial Berkala</h5>
        <div class="row g-2 text-center">
            <div class="col-6 col-md-3">
                <div class="p-3 bg-light rounded-3 border-start border-primary border-4">
                    <span class="text-muted small fw-medium d-block mb-1">Harian</span>
                    <span class="fw-bold text-dark">Rp <?= number_format($rekapHarian, 0, ',', '.') ?></span>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="p-3 bg-light rounded-3 border-start border-info border-4">
                    <span class="text-muted small fw-medium d-block mb-1">Mingguan</span>
                    <span class="fw-bold text-dark">Rp <?= number_format($rekapMingguan, 0, ',', '.') ?></span>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="p-3 bg-light rounded-3 border-start border-success border-4">
                    <span class="text-muted small fw-medium d-block mb-1">Bulanan</span>
                    <span class="fw-bold text-dark">Rp <?= number_format($rekapBulanan, 0, ',', '.') ?></span>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="p-3 bg-light rounded-3 border-start border-warning border-4">
                    <span class="text-muted small fw-medium d-block mb-1">Tahunan</span>
                    <span class="fw-bold text-dark">Rp <?= number_format($rekapTahunan, 0, ',', '.') ?></span>
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
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold m-0 text-dark"><i class="bi bi-bell me-2 text-success"></i>Pesanan Masuk</h5>
                    <span class="badge bg-danger rounded-pill px-2.5 py-1" id="badge-antrean"><?= count($pesanans) ?> Transaksi</span>
                </div>
                
                <div class="overflow-auto" style="max-height: 520px;" id="list-pesanan-box">
                    <div class="list-group list-group-flush">
                        <?php if (empty($pesanans)): ?>
                            <div class="text-center py-4 text-muted">Belum ada pesanan aktif saat ini.</div>
                        <?php endif;

                        foreach ($pesanans as $p): 
                            $borderColor = 'border-warning';
                            if ($p['status_pesanan'] === 'Memasak') $borderColor = 'border-info';
                            if ($p['status_pesanan'] === 'Selesai') $borderColor = 'border-success';
                            if ($p['status_pesanan'] === 'Dibatalkan') $borderColor = 'border-danger';
                        ?>
                            <div class="list-group-item px-3 py-3 border rounded-3 mb-2 <?= $borderColor ?>" style="border-width: 2px !important;">
                                <div class="d-flex w-100 justify-content-between align-items-center mb-1">
                                    <h6 class="mb-0 fw-bold text-dark"><?= htmlspecialchars($p['nama_menu']) ?> <span class="text-secondary">x<?= $p['jumlah'] ?></span></h6>
                                    <span class="badge bg-light text-dark border rounded-pill" style="font-size: 0.7rem;"><?= $p['metode_pembayaran'] ?></span>
                                </div>
                                <p class="mb-2 text-secondary" style="font-size: 0.82rem;"><i class="bi bi-person me-1"></i> Pemesan: <?= htmlspecialchars($p['nama_lengkap']) ?></p>
                                
                                <div class="d-flex justify-content-between align-items-center mt-3 pt-2 border-top">
                                    <span class="text-success fw-bold" style="font-size:0.95rem;">Rp <?= number_format($p['total_harga'], 0, ',', '.') ?></span>
                                    
                                    <div class="d-flex gap-1 align-items-center">
                                        <form action="" method="POST" class="d-flex gap-1 align-items-center m-0">
                                            <input type="hidden" name="id_pesanan" value="<?= $p['id_pesanan'] ?>">
                                            <select name="status_baru" class="form-select form-select-sm rounded-pill" style="font-size: 0.8rem; width: 105px;">
                                                <option value="Pending" <?= $p['status_pesanan'] == 'Pending' ? 'selected' : '' ?>>Pending</option>
                                                <option value="Memasak" <?= $p['status_pesanan'] == 'Memasak' ? 'selected' : '' ?>>Memasak</option>
                                                <option value="Selesai" <?= $p['status_pesanan'] == 'Selesai' ? 'selected' : '' ?>>Selesai</option>
                                                <option value="Dibatalkan" <?= $p['status_pesanan'] == 'Dibatalkan' ? 'selected' : '' ?>>Dibatalkan</option>
                                            </select>
                                            <button type="submit" name="update_status" class="btn btn-dark btn-sm rounded-circle"><i class="bi bi-check-lg" style="font-size: 0.75rem;"></i></button>
                                        </form>

                                        <?php if ($p['status_pesanan'] === 'Selesai' || $p['status_pesanan'] === 'Dibatalkan'): ?>
                                            <form action="" method="POST" class="m-0" onsubmit="return confirm('Sembunyikan dari antrean?')">
                                                <input type="hidden" name="id_pesanan" value="<?= $p['id_pesanan'] ?>">
                                                <button type="submit" name="hapus_pesanan" class="btn btn-outline-secondary btn-sm rounded-circle" title="Arsip"><i class="bi bi-eye-slash-fill" style="font-size: 0.7rem;"></i></button>
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

<audio id="alarmKantin" src="https://assets.mixkit.co/active_storage/sfx/2869/2869-84.wav" preload="auto"></audio>

<script>
// Menghitung ID pesanan tertinggi yang ada saat ini di browser kasir admin
let lastCheckedOrderId = <?= !empty($pesanans) ? json_encode(max(array_column($pesanans, 'id_pesanan'))) : 0 ?>;

function checkNewOrdersLive() {
    fetch('api_realtime.php')
        .then(response => response.json())
        .then(data => {
            // Jika ID paling baru di database lebih besar dari yang dipegang layar kasir, bunyikan alarm!
            if (data.max_id > lastCheckedOrderId) {
                
                // 1. BUNYIKAN SUARA ALARM "TRING!" OTOMATIS
                let sound = document.getElementById('alarmKantin');
                sound.play().catch(err => console.log("Menunggu interaksi browser untuk aktivasi audio"));

                // 2. KONTROL DESKTOP NOTIFICATION BROWSER WINDOWS/MAC
                if (Notification.permission === "granted") {
                    new Notification("E-Kantin UPNVJ: Ada Pesanan Masuk!", {
                        body: "Antrean toko bertambah! Segera proses pesanan makanan mahasiswa.",
                        icon: "https://cdn-icons-png.flaticon.com/512/3144/3144456.png"
                    });
                }

                // Kunci ID pesanan terbaru agar alarm tidak berdering berulang-ulang
                lastCheckedOrderId = data.max_id;

                // 3. REFRESH HALAMAN SECARA SEKEJAP UTK UPDATE VIEW SECARA LIVE
                setTimeout(() => {
                    location.reload();
                }, 1200);
            }
        })
        .catch(err => console.error("Gagal sinkronisasi API real-time:", err));
}

// Minta izin notifikasi browser windows saat pertama kali admin membuka web
if (Notification.permission !== "granted" && Notification.permission !== "denied") {
    Notification.requestPermission();
}

// Jalankan pengecekan otomatis di latar belakang setiap 3 detik sekali
setInterval(checkNewOrdersLive, 3000);
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
