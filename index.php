<?php
session_start();
if (isset($_SESSION['role'])) {
    header("Location: " . ($_SESSION['role'] === 'admin' ? 'admin/dashboard.php' : 'user/dashboard.php'));
    exit;
} else {
    header("Location: auth/login.php");
    exit;
}