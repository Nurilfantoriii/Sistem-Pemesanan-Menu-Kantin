<?php
header('Content-Type: application/json');
// JALUR DIUBAH: Naik satu folder dulu (../) baru masuk ke config
require_once '../config/koneksi.php';

$id_user = isset($_GET['id_user']) ? intval($_GET['id_user']) : 0;

if ($id_user > 0) {
    $stmt = $pdo->prepare("SELECT p.*, m.nama_menu FROM pesanan p JOIN menu m ON p.id_menu = m.id_menu WHERE p.id_user = ? ORDER BY p.tanggal_pesanan DESC");
    $stmt->execute([$id_user]);
    $riwayat = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($riwayat as &$r) {
        $r['waktu_format'] = date('d M, H:i', strtotime($r['tanggal_pesanan']));
    }

    echo json_encode($riwayat);
} else {
    echo json_encode([]);
}