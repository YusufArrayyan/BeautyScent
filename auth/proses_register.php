<?php
session_start();
require_once '../config/database.php';
require_once '../config/mailer.php'; // Panggil file pengirim email

if (isset($_POST['register'])) {
    // Tangkap data dari form register.php
    $role = mysqli_real_escape_string($conn, $_POST['role']);
    $nama = mysqli_real_escape_string($conn, $_POST['nama']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $no_hp = mysqli_real_escape_string($conn, $_POST['no_hp']);
    $password = $_POST['password'];
    $konfirmasi = $_POST['konfirmasi'];
    $redirect_url = isset($_POST['redirect']) ? $_POST['redirect'] : '';

    // Validasi Password
    if ($password !== $konfirmasi) {
        $_SESSION['error_register'] = "Password and confirmation do not match!";
        header("Location: register.php" . (!empty($redirect_url) ? "?redirect=".urlencode($redirect_url) : ""));
        exit();
    }
    if (strlen($password) < 8) {
        $_SESSION['error_register'] = "Password must be at least 8 characters!";
        header("Location: register.php" . (!empty($redirect_url) ? "?redirect=".urlencode($redirect_url) : ""));
        exit();
    }

    // ==========================================
    // PENGECEKAN EMAIL & LOGIKA ANTI-ERROR
    // ==========================================
    $cek_email = mysqli_query($conn, "SELECT id, is_verified, nama_lengkap FROM users WHERE email = '$email'");
    
    if (mysqli_num_rows($cek_email) > 0) {
        $data_email = mysqli_fetch_assoc($cek_email);
        
        // Skenario 1: Email sudah terdaftar dan SUDAH VERIFIKASI
        if ($data_email['is_verified'] == '1') {
            $_SESSION['error_register'] = "This email is already registered and active. Please log in.";
            header("Location: register.php" . (!empty($redirect_url) ? "?redirect=".urlencode($redirect_url) : ""));
            exit();
        } 
        // Skenario 2: Email ada tapi BELUM VERIFIKASI (Akun Lama/Pendaftaran Menggantung)
        else {
            // JANGAN DI-DELETE (Mencegah Error Foreign Key). 
            // Kita cukup UPDATE password dan kirim OTP baru.
            
            $user_id_lama = $data_email['id'];
            $nama_lama = $data_email['nama_lengkap']; // Ambil nama yang lama
            
            $otp_code = sprintf("%06d", mt_rand(1, 999999));
            $otp_expiry = date('Y-m-d H:i:s', strtotime('+15 minutes'));
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // Update data user tersebut dengan OTP baru
            mysqli_query($conn, "UPDATE users SET password = '$hashed_password', otp_code = '$otp_code', otp_expiry = '$otp_expiry' WHERE id = '$user_id_lama'");
            
            // Kirim ulang email OTP
            $kirim_email = sendOTP($email, $nama_lama, $otp_code);
            
            if ($kirim_email) {
                header("Location: verify_otp.php?email=" . urlencode($email) . (!empty($redirect_url) ? "&redirect=".urlencode($redirect_url) : ""));
                exit();
            } else {
                $_SESSION['error_register'] = "Failed to send OTP code. Please check your email settings.";
                header("Location: register.php" . (!empty($redirect_url) ? "?redirect=".urlencode($redirect_url) : ""));
                exit();
            }
        }
    }

    // ==========================================
    // SKENARIO 3: EMAIL BENAR-BENAR BARU (INSERT)
    // ==========================================
    
    // Hash password demi keamanan
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Buat 6 Digit Angka Acak & Waktu Kadaluarsa
    $otp_code = sprintf("%06d", mt_rand(1, 999999));
    $otp_expiry = date('Y-m-d H:i:s', strtotime('+15 minutes'));

    // Simpan data user dengan is_verified = '0'
    $query_insert = "INSERT INTO users (nama_lengkap, email, password, no_hp, role, is_verified, otp_code, otp_expiry) 
                     VALUES ('$nama', '$email', '$hashed_password', '$no_hp', '$role', '0', '$otp_code', '$otp_expiry')";
                     
    if (mysqli_query($conn, $query_insert)) {
        $user_id_baru = mysqli_insert_id($conn);

        // KHUSUS TEKNISI: Masukkan juga datanya ke tabel `toko`
        if ($role === 'toko') {
            $nama_toko = mysqli_real_escape_string($conn, $_POST['nama_toko']);
            $kategori = mysqli_real_escape_string($conn, $_POST['kategori']);
            
            $kat_id = 1; // Default
            $q_kat = mysqli_query($conn, "SELECT id FROM kategori WHERE nama_kategori LIKE '%$kategori%' LIMIT 1");
            if($q_kat && mysqli_num_rows($q_kat) > 0){
                $kat_id = mysqli_fetch_assoc($q_kat)['id'];
            }

            mysqli_query($conn, "INSERT INTO toko (user_id, nama_toko, kategori_jasa, status_verifikasi) 
                                 VALUES ('$user_id_baru', '$nama_toko', '$kat_id', 'pending')");
        }

        // Eksekusi Pengiriman Email OTP
        $kirim_email = sendOTP($email, $nama, $otp_code);

        if ($kirim_email) {
            header("Location: verify_otp.php?email=" . urlencode($email) . (!empty($redirect_url) ? "&redirect=".urlencode($redirect_url) : ""));
            exit();
        } else {
            // Jika gagal kirim email hapus pendaftaran yang baru saja dibuat ini (aman dihapus karena belum punya relasi)
            mysqli_query($conn, "DELETE FROM users WHERE id = '$user_id_baru'");
            $_SESSION['error_register'] = "Failed to send OTP code. SMTP settings may be incorrect.";
            header("Location: register.php" . (!empty($redirect_url) ? "?redirect=".urlencode($redirect_url) : ""));
            exit();
        }

    } else {
        $_SESSION['error_register'] = "Registration failed. Database error: " . mysqli_error($conn);
        header("Location: register.php" . (!empty($redirect_url) ? "?redirect=".urlencode($redirect_url) : ""));
        exit();
    }
} else {
    header("Location: register.php");
    exit();
}
?>