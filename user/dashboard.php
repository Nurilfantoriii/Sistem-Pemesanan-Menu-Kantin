<?php
session_start();

// 1. PROTEKSI HALAMAN: Pastikan hanya pengguna dengan role 'user' yang bisa masuk
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'user') { 
    header("Location: ../auth/login.php"); 
    exit; 
}

// Hubunakan ke database menggunakan PDO dari folder config
require_once '../config/koneksi.php';

$success = ''; 
$error = '';

// Mengambil ID User aktif yang sedang login dari session pembeli
$id_user_aktif = isset($_SESSION['id_user']) ? $_SESSION['id_user'] : (isset($_SESSION['id']) ? $_SESSION['id'] : 0);

// 2. PROSES UTAMA: KETIKA MAHASISWA KLIK TOMBOL "PESAN MENU"
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['beli'])) {
    $id_menu = intval($_POST['id_menu']);
    $jumlah = intval($_POST['jumlah']);
    $metode = $_POST['metode_pembayaran'];

    $valid_payments = ['Cash', 'QRIS', 'DANA', 'GoPay', 'ShopeePay'];

    if ($jumlah <= 0) {
        $error = "Jumlah pesanan minimal 1 porsi.";
    } elseif (!in_array($metode, $valid_payments)) {
        $error = "Metode pembayaran tidak valid.";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM menu WHERE id_menu = ?");
        $stmt->execute([$id_menu]);
        $menu = $stmt->fetch();

        if ($menu && $menu['stok'] >= $jumlah) {
            $total_harga = $menu['harga'] * $jumlah;
            
            $stmtOrder = $pdo->prepare("INSERT INTO pesanan (id_user, id_menu, jumlah, total_harga, metode_pembayaran, status_pesanan) VALUES (?, ?, ?, ?, ?, 'Pending')");
            $stmtOrder->execute([$id_user_aktif, $id_menu, $jumlah, $total_harga, $metode]);

            $stokBaru = $menu['stok'] - $jumlah;
            $stmtUpdateStok = $pdo->prepare("UPDATE menu SET stok = ? WHERE id_menu = ?");
            $stmtUpdateStok->execute([$stokBaru, $id_menu]);

            $success = "Hore! Pesanan '" . htmlspecialchars($menu['nama_menu']) . "' berhasil dikirim ke dapur. Pantau status antreanmu secara real-time di bawah!";
        } else {
            $error = "Maaf, porsi makanan/minuman ini sudah habis atau tidak mencukupi.";
        }
    }
}

// 3. PROSES PEMBATALAN
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['batalkan_pesanan'])) {
    $id_pesanan = intval($_POST['id_pesanan']);

    $stmtCek = $pdo->prepare("SELECT * FROM pesanan WHERE id_pesanan = ? AND id_user = ?");
    $stmtCek->execute([$id_pesanan, $id_user_aktif]);
    $pesanan = $stmtCek->fetch();

    if ($pesanan && $pesanan['status_pesanan'] === 'Pending') {
        $stmtBatal = $pdo->prepare("UPDATE pesanan SET status_pesanan = 'Dibatalkan' WHERE id_pesanan = ?");
        $stmtBatal->execute([$id_pesanan]);

        $stmtMenu = $pdo->prepare("SELECT stok FROM menu WHERE id_menu = ?");
        $stmtMenu->execute([$pesanan['id_menu']]);
        $menu = $stmtMenu->fetch();
        if ($menu) {
            $stmtRestock = $pdo->prepare("UPDATE menu SET stok = ? WHERE id_menu = ?");
            $stmtRestock->execute([$menu['stok'] + $pesanan['jumlah'], $pesanan['id_menu']]);
        }

        $success = "Pesanan berhasil dibatalkan!";
    } else {
        $error = "Gagal membatalkan! Pesanan sudah terlanjur diproses.";
    }
}

// 4. AMBIL DATA PRODUK
$menus = $pdo->query("SELECT * FROM menu WHERE stok > 0 ORDER BY nama_menu ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Mahasiswa - E-Kantin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        .navbar-user { background: linear-gradient(135deg, #0d6efd 0%, #0b5ed7 100%); }
        .menu-card { border: none; border-radius: 16px; transition: transform 0.2s, box-shadow 0.2s; overflow: hidden; }
        .menu-card:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.08) !important; }
        .img-wrapper { height: 160px; width: 100%; overflow: hidden; background-color: #f8f9fa; display: flex; align-items: center; justify-content: center; }
        .img-wrapper img { width: 100%; height: 100%; object-fit: cover; }
        .rounded-4 { border-radius: 16px !important; }
        
        /* Style Lonceng Notifikasi Dropdown */
        .notif-badge { position: absolute; top: -2px; right: -2px; padding: 3px 6px; border-radius: 50%; background-color: #dc3545; color: white; font-size: 0.65rem; font-weight: bold; line-height: 1; }
        .dropdown-notif { width: 340px; max-height: 400px; overflow-y: auto; border-radius: 12px; box-shadow: 0 8px 24px rgba(0,0,0,0.15); border: none; }
        .notif-item { padding: 12px 16px; border-bottom: 1px solid #f1f3f5; font-size: 0.85rem; transition: background 0.2s; }
        .notif-item:hover { background-color: #f8f9fa; }
        .notif-item:last-child { border-bottom: none; }
    </style>
</head>
<body class="bg-light">

<!-- NAVBAR UTAMA -->
<nav class="navbar navbar-expand-lg navbar-dark navbar-user sticky-top mb-4 shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-bold" href="#"><i class="bi bi-cup-hot-fill me-2"></i>E-Kantin UPNVJT</a>
        
        <div class="ms-auto d-flex align-items-center gap-3">
            
            <!-- LONCENG NOTIFIKASI DROPDOWN -->
            <div class="dropdown me-2">
                <button class="btn btn-link position-relative text-white p-1 shadow-none" type="button" id="dropdownNotifBell" data-bs-toggle="dropdown" aria-expanded="false" style="text-decoration: none;">
                    <i class="bi bi-bell-fill fs-4"></i>
                    <span id="jumlah-notif-badge" class="notif-badge d-none">0</span>
                </button>
                <div class="dropdown-menu dropdown-menu-end dropdown-notif p-0 animate fade-In" aria-labelledby="dropdownNotifBell">
                    <div class="bg-light fw-bold px-3 py-2 border-bottom text-dark rounded-top-12" style="font-size: 0.9rem;">NOTIFIKASI</div>
                    <div id="konten-list-notif">
                        <div class="text-center text-muted py-3 small">Tidak ada pemberitahuan baru</div>
                    </div>
                </div>
            </div>

            <span class="text-light"><i class="bi bi-person-fill me-1"></i> Halo, <?= htmlspecialchars($_SESSION['username']) ?></span>
            <a href="../auth/logout.php" class="btn btn-light btn-sm rounded-pill px-3 text-primary fw-medium shadow-sm">Keluar</a>
        </div>
    </div>
</nav>

<div class="container mb-5">
    
    <!-- BANNER JUMBOTRON -->
    <div class="p-4 p-md-5 mb-4 rounded-4 text-bg-dark shadow-sm" style="background: linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.5)), url('https://images.unsplash.com/photo-1555396273-367ea4eb4db5?q=80&w=1000&auto=format&fit=crop'); background-size: cover; background-position: center;">
        <h1 class="display-6 fw-bold">Lapar di Sela Kuliah?</h1>
        <p class="lead my-2 mb-0">Pesan hidangan langsung dari kelas. Pembaruan status pesanan dipantau otomatis melalui menu lonceng di atas.</p>
    </div>

    <?php if($success): ?><div class="alert alert-success border-0 shadow-sm rounded-3 mb-4"><i class="bi bi-check-circle-fill me-2"></i><?= $success ?></div><?php endif; ?>
    <?php if($error): ?><div class="alert alert-danger border-0 shadow-sm rounded-3 mb-4"><i class="bi bi-exclamation-triangle-fill me-2"></i><?= $error ?></div><?php endif; ?>

    <!-- KATALOG ETALASE MENU -->
    <h4 class="fw-bold text-dark mb-4"><i class="bi bi-grid-fill text-primary me-2"></i>Katalog Menu Hari Ini</h4>
    <div class="row g-4">
        <?php foreach ($menus as $m): ?>
        <div class="col-sm-6 col-md-4 col-lg-3">
            <div class="card h-100 shadow-sm menu-card">
                <div class="img-wrapper">
                    <?php if (!empty($m['gambar'])): ?>
                        <img src="../assets/img/<?= htmlspecialchars($m['gambar']) ?>" alt="<?= htmlspecialchars($m['nama_menu']) ?>">
                    <?php else: ?>
                        <img src="https://images.unsplash.com/photo-1546069901-ba9599a7e63c?q=80&w=500&auto=format&fit=crop" alt="Default Food">
                    <?php endif; ?>
                </div>
                <div class="card-body p-4 d-flex flex-column justify-content-between">
                    <div>
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <span class="badge bg-secondary-subtle text-secondary rounded-pill px-2.5 py-1" style="font-size: 0.75rem Susunan;"><?= htmlspecialchars($m['kategori']) ?></span>
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
                            </select>
                        </div>
                        <button type="submit" name="beli" class="btn btn-primary btn-sm w-100 rounded-pill py-2 shadow-sm"><i class="bi bi-bag-plus me-1"></i> Pesan Menu</button>
                    </form>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- TABEL RIWAYAT BELANJA -->
    <hr class="my-5">
    <h4 class="fw-bold text-dark mb-4"><i class="bi bi-clock-history text-success me-2"></i>Riwayat Pesanan Anda</h4>
    <div class="card border-0 shadow-sm p-4 rounded-4 bg-white">
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
                <tbody id="tabel-riwayat-ajax">
                    <!-- Data masuk lewat AJAX -->
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
const idUserAktif = <?= json_encode($id_user_aktif) ?>;
let memoriStatusPesanan = {}; 
let daftarNotifikasiAktif = []; 

// RENDERING DAFTAR ELEMEN DI LONCENG DROPDOWN NAVBAR
function perbaruiTampilanLoncengDropdown() {
    const badge = document.getElementById('jumlah-notif-badge');
    const wadahList = document.getElementById('konten-list-notif');
    
    if (daftarNotifikasiAktif.length === 0) {
        badge.classList.add('d-none');
        wadahList.innerHTML = `<div class="text-center text-muted py-3 small">Tidak ada pemberitahuan baru</div>`;
    } else {
        badge.innerText = daftarNotifikasiAktif.length;
        badge.classList.remove('d-none');
        
        let htmlList = "";
        daftarNotifikasiAktif.forEach(info => {
            htmlList = `
                <div class="notif-item text-dark">
                    <div class="d-flex align-items-start">
                        <span class="me-2">${info.icon}</span>
                        <div>
                            Pesanan ID <strong>#${info.id_pesanan}</strong> (<em>${info.menu}</em>) statusnya: 
                            <span class="badge ${info.warna} mx-1 small">${info.status.toUpperCase()}</span>
                        </div>
                    </div>
                </div>
            ` + htmlList; // Menyusun data terbaru di urutan teratas dropdown
        });
        wadahList.innerHTML = htmlList;
    }
}

// MESIN ENGINE REAL-TIME POLLING AJAX
function muatDataRiwayatRealTime() {
    fetch('ambil_riwayat.php?id_user=' + idUserAktif)
    .then(res => res.json())
    .then(data => {
        let barisHtml = "";
        
        if(data.length === 0) {
            barisHtml = `<tr><td colspan="7" class="text-center py-4 text-muted">Kamu belum melakukan pemesanan makanan hari ini.</td></tr>`;
        } else {
            data.forEach(r => {
                let badgeColor = 'bg-warning text-dark'; 
                if (r.status_pesanan === 'Memasak') badgeColor = 'bg-info text-white';
                if (r.status_pesanan === 'Selesai') badgeColor = 'bg-success text-white';
                if (r.status_pesanan === 'Dibatalkan') badgeColor = 'bg-danger text-white';

                // ====================================================================
                // PENYEMPURNAAN LOGIKA DETEKSI NOTIFIKASI DROPDOWN (ANTI LOGOUT-STUCK)
                // ====================================================================
                const isFirstLoad = typeof memoriStatusPesanan[r.id_pesanan] === 'undefined';
                const statusBerubah = memoriStatusPesanan[r.id_pesanan] && memoriStatusPesanan[r.id_pesanan] !== r.status_pesanan;

                // Membaca status ganti dapur sejak awal muat halaman, atau saat update real-time berjalan
                if ((isFirstLoad && r.status_pesanan !== 'Pending') || statusBerubah) {
                    
                    // Validasi duplikasi id pesanan dan status yang sama agar tidak tertumpuk ganda
                    const sudahAdaDiList = daftarNotifikasiAktif.some(notif => notif.id_pesanan === r.id_pesanan && notif.status === r.status_pesanan);
                    
                    if (!sudahAdaDiList) {
                        let iconNotif = "⏳";
                        let kelasWarnaBadge = "bg-warning text-dark";
                        if (r.status_pesanan === 'Memasak') { iconNotif = "🍳"; kelasWarnaBadge = "bg-info text-white"; }
                        if (r.status_pesanan === 'Selesai') { iconNotif = "🎉"; kelasWarnaBadge = "bg-success text-white"; }
                        if (r.status_pesanan === 'Dibatalkan') { iconNotif = "❌"; kelasWarnaBadge = "bg-danger text-white"; }

                        daftarNotifikasiAktif.push({
                            id_pesanan: r.id_pesanan,
                            menu: r.nama_menu,
                            status: r.status_pesanan,
                            icon: iconNotif,
                            warna: kelasWarnaBadge
                        });
                    }
                    
                    perbaruiTampilanLoncengDropdown();
                }
                
                // Kunci status saat ini ke dalam memori
                memoriStatusPesanan[r.id_pesanan] = r.status_pesanan;

                let payIcon = 'bi-credit-card text-primary';
                if (r.metode_pembayaran === 'Cash') payIcon = 'bi-cash text-success';
                if (r.metode_pembayaran === 'QRIS') payIcon = 'bi-qr-code-scan text-dark';

                let tombolBatal = r.status_pesanan === 'Pending' 
                    ? `<form action="" method="POST" onsubmit="return confirm('Batalkan pesanan ini?')">
                            <input type="hidden" name="id_pesanan" value="${r.id_pesanan}">
                            <button type="submit" name="batalkan_pesanan" class="btn btn-outline-danger btn-sm rounded-pill px-3">Batal</button>
                       </form>`
                    : `<span class="text-muted" style="font-size: 0.8rem;">- Terkunci -</span>`;

                barisHtml += `
                    <tr>
                        <td class="text-secondary" style="font-size: 0.85rem;">${r.waktu_format}</td>
                        <td class="fw-semibold text-dark">${r.nama_menu}</td>
                        <td class="text-center">${r.jumlah}</td>
                        <td class="fw-bold text-dark">Rp ${parseInt(r.total_harga).toLocaleString('id-ID')}</td>
                        <td class="text-center fw-medium"><i class="bi ${payIcon} me-1"></i>${r.metode_pembayaran}</td>
                        <td class="text-center"><span class="badge ${badgeColor} rounded-pill px-3 py-1.5">${r.status_pesanan}</span></td>
                        <td class="text-center">${tombolBatal}</td>
                    </tr>`;
            });
        }
        document.getElementById('tabel-riwayat-ajax').innerHTML = barisHtml;
    });
}

// Interval otomatis berjalan mengecek data per 4 detik
setInterval(muatDataRiwayatRealTime, 4000);
window.onload = muatDataRiwayatRealTime;
</script>
</body>
</html>
