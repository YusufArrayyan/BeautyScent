<?php
session_start();
require_once '../config/database.php';

if (isset($_POST['login'])) {
    $email = mysqli_real_escape_string($conn, trim($_POST['email']));
    $password = $_POST['password'];
    $redirect_url = isset($_POST['redirect']) ? $_POST['redirect'] : '';

    // Cek apakah email terdaftar
    $query = mysqli_query($conn, "SELECT * FROM users WHERE email = '$email'");
    
    if (mysqli_num_rows($query) === 1) {
        $user = mysqli_fetch_assoc($query);
        
        // Verifikasi Password
        if (password_verify($password, $user['password'])) {
            // Set Session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['nama_lengkap'] = $user['nama_lengkap'];

            // === LOGIKA REDIRECT BERDASARKAN ROLE ===
            
            // 1. Jika ada Redirect URL bawaan (seperti dari keranjang/detail toko)
            if (!empty($redirect_url)) {
                header("Location: ../" . $redirect_url);
                exit();
            } 
            
            // 2. Jika dia adalah ADMIN (Bosku)
            else if ($user['role'] === 'admin') {
                header("Location: ../admin/index.php");
                exit();
            }
            
            // 3. Jika dia adalah TOKO / TEKNISI
            else if ($user['role'] === 'toko') {
                header("Location: ../toko/index.php");
                exit();
            }
            
            // 4. Jika dia adalah PEMBELI BIASA
            else {
                header("Location: ../index.php");
                exit();
            }

        } else {
            $_SESSION['error_login'] = "The password you entered is incorrect.";
            header("Location: login.php" . (!empty($redirect_url) ? "?redirect=" . urlencode($redirect_url) : ""));
            exit();
        }
    } else {
        $_SESSION['error_login'] = "Email not found or not registered.";
        header("Location: login.php" . (!empty($redirect_url) ? "?redirect=" . urlencode($redirect_url) : ""));
        exit();
    }
} else {
    // Jika akses file ini tanpa lewat tombol submit form
    header("Location: login.php");
    exit();
}