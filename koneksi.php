<?php
$host = "localhost";
$user = "root";
$pass = ""; // Kosongkan jika menggunakan bawaan Laragon
$db   = "db_kantin"; // Pastikan namanya db_kantin

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // CRITICAL: Memastikan data dibaca sebagai array asosiatif string
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC); 
} catch (PDOException $e) {
    die("Koneksi database gagal: " . $e->getMessage());
}
?>