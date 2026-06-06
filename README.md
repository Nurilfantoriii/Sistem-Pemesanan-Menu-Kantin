# 🛒 E-Kantin UPNVJT - Sistem Pemesanan Menu Kantin Digital

[![PWEB](https://img.shields.io/badge/Course-Pemrograman%20Web-blue)](https://github.com/Nurilfantoriii/Sistem-Pemesanan-Menu-Kantin)
[![PHP](https://img.shields.io/badge/Language-PHP%20Native%20(PDO)-777BB4)](https://www.php.net/)
[![Bootstrap](https://img.shields.io/badge/Framework-Bootstrap%205-7952B3)](https://getbootstrap.com/)

Tugas Besar Pemrograman Web (PWEB) - Aplikasi Sistem Pemesanan E-Kantin Mahasiswa berbasis PHP Native (PDO) dan Bootstrap 5. Dikembangkan oleh **Kelompok 11 Informatika UPN "Veteran" Jawa Timur**.

## 👥 Anggota Kelompok 11
* **Ahmad Nuril Fantori** (NPM: 24081010008) - *Backend, Database & Real-Time Engine*
* **Valan Akbar Saputra** (NPM: 240810100042) - *Frontend & UI/UX Designer*
*  **Gunawan Wibisono** (NPM: 240810100047) - *Technical Writer & Tester*

---

## 🌟 Fitur Utama Aplikasi
1. **Sistem Autentikasi & Otorisasi Sesi (Session)**
   * Pemisahan Hak Akses (*Role-Based Access Control*) yang ketat antara **Admin/Kasir** dan **User/Mahasiswa**.
   * Fitur Lupa Kata Sandi cerdas dengan deteksi otomatis NPM mahasiswa (`@student.upnjatim.ac.id`) maupun Gmail umum.
2. **Sistem Manajemen Menu (CRUD Admin)**
   * Admin dapat menambahkan, melihat, mengubah, dan menghapus menu hidangan secara dinamis lengkap dengan upload foto makanan ke folder *assets*.
3. **Pelacakan Status Pesanan Real-Time (Sisi User)**
   * Pembaruan status pesanan (*Pending / Memasak / Selesai / Dibatalkan*) berjalan otomatis di latar belakang memanfaatkan teknologi **AJAX Polling (Fetch API)** berkala setiap 4 detik tanpa *refresh* halaman manual.
4. **Sistem Notifikasi Lonceng Dropdown**
   * Integrasi menu lonceng di Navbar atas yang langsung memunculkan *badge* angka merah dan riwayat teks pembaruan pesanan secara interaktif saat status makanan diubah di dapur admin.
5. **Opsi Pembayaran Cashless & Manajemen Stok**
   * Mendukung berbagai metode pembayaran instan (Cash, QRIS, DANA, GoPay, ShopeePay) yang otomatis memotong stok hidangan di etalase dan mengembalikannya jika pesanan dibatalkan.

---

## 🛠️ Teknologi yang Digunakan
* **Backend:** PHP Native 8.x
* **Database Driver:** PDO (PHP Data Objects) — *Aman dari SQL Injection*
* **Frontend:** Bootstrap 5.3 & Bootstrap Icons
* **Database Engine:** MySQL / MariaDB

---

## 📐 Struktur Database (`db_kantin.sql`)
Aplikasi ini menggunakan basis data relasional dengan tabel-tabel utama yang saling relevan via *Foreign Key*:
* `users` : Menyimpan data kredensial login akun, nama, dan role.
* `menu` : Menyimpan data katalog makanan, harga, stok, dan path gambar.
* `pesanan` : Menyimpan detail nota transaksi, relasi user pembeli, menu yang dipilih, jumlah, metode bayar, dan status dapur.

---

## 🚀 Cara Menjalankan Project di Lokal (Laragon / XAMPP)
1. *Clone* repositori ini ke folder `www` (Laragon) atau `htdocs` (XAMPP):
   ```bash
   git clone [https://github.com/Nurilfantoriii/Sistem-Pemesanan-Menu-Kantin.git](https://github.com/Nurilfantoriii/Sistem-Pemesanan-Menu-Kantin.git)
