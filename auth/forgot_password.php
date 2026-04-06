<?php
session_start();
require_once '../config/database.php';
require_once '../config/mailer.php';

if (isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

$error = '';
$success = '';

if (isset($_POST['kirim_otp'])) {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $cek = mysqli_query($conn, "SELECT id, nama_lengkap, is_verified FROM users WHERE email = '$email'");
    
    if (mysqli_num_rows($cek) > 0) {
        $user = mysqli_fetch_assoc($cek);
        if ($user['is_verified'] == '1') {
            $otp_code = sprintf("%06d", mt_rand(1, 999999));
            $otp_expiry = date('Y-m-d H:i:s', strtotime('+15 minutes'));
            mysqli_query($conn, "UPDATE users SET otp_code = '$otp_code', otp_expiry = '$otp_expiry' WHERE email = '$email'");
            if (sendOTP($email, $user['nama_lengkap'], $otp_code)) {
                header("Location: verify_reset.php?email=" . urlencode($email));
                exit();
            } else {
                $error = "Failed to send OTP email. Please check server settings.";
            }
        } else {
            $error = "Your account is not verified. Please re-register.";
        }
    } else {
        $error = "This email address is not registered.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - BeautyScent</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400..900;1,400..900&family=Lora:ital,wght@0,400..700;1,400..700&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: { 
                extend: { 
                    colors: { charcoal: '#3a262a', 'rose-gold': '#e8a0bf', bglight: '#fdfbfb' }, 
                    fontFamily: { serif: ['"Playfair Display"', 'serif'], sans: ['"Lora"', 'serif'] } 
                } 
            }
        }
    </script>
</head>
<body class="bg-bglight font-sans min-h-screen flex items-center justify-center p-4 relative overflow-hidden selection:bg-rose-gold selection:text-white">

    <div class="absolute top-[-10%] right-[-10%] w-96 h-96 bg-rose-gold rounded-full blur-[120px] opacity-20 pointer-events-none"></div>
    <div class="absolute bottom-[-10%] left-[-10%] w-96 h-96 bg-charcoal rounded-full blur-[120px] opacity-10 pointer-events-none"></div>

    <div class="max-w-md w-full bg-white rounded-[2rem] shadow-[0_20px_60px_rgba(0,0,0,0.05)] p-8 md:p-10 border border-gray-100 relative z-10">
        
        <a href="login.php" class="absolute top-6 left-6 w-8 h-8 bg-gray-50 rounded-full flex items-center justify-center text-gray-400 hover:text-charcoal hover:bg-gray-200 transition-colors">
            <i class="fas fa-arrow-left"></i>
        </a>

        <div class="w-20 h-20 bg-gradient-to-br from-rose-gold to-pink-300 text-white rounded-full flex items-center justify-center text-3xl mx-auto mb-6 mt-4 shadow-lg shadow-rose-gold/30">
            <i class="fas fa-key"></i>
        </div>
        
        <div class="text-center mb-8">
            <h2 class="text-2xl md:text-3xl font-black font-serif text-charcoal mb-2 tracking-tight">Forgot Password?</h2>
            <p class="text-xs md:text-sm text-gray-500 font-medium">Enter your registered email address and we'll send you a 6-digit OTP code to reset your password.</p>
        </div>

        <?php if($error): ?>
            <div class="bg-red-50 text-red-500 text-xs font-bold p-4 rounded-2xl mb-6 border border-red-100 flex items-center gap-2">
                <i class="fas fa-exclamation-circle text-lg"></i> <?= $error ?>
            </div>
        <?php endif; ?>

        <form action="" method="POST" class="space-y-6">
            <div>
                <label class="block text-[10px] font-black text-charcoal uppercase tracking-widest mb-2 pl-1">Email Address</label>
                <div class="relative group">
                    <i class="far fa-envelope absolute left-5 top-1/2 -translate-y-1/2 text-gray-400 group-focus-within:text-rose-gold transition-colors"></i>
                    <input type="email" name="email" required placeholder="you@example.com" class="w-full pl-12 pr-4 py-4 bg-gray-50 border border-gray-200 rounded-2xl focus:outline-none focus:ring-4 focus:ring-rose-gold/10 focus:border-rose-gold focus:bg-white transition-all font-bold text-charcoal placeholder-gray-400 text-sm">
                </div>
            </div>

            <button type="submit" name="kirim_otp" class="w-full bg-charcoal hover:bg-[#2d1b1f] text-white py-4 rounded-2xl font-black text-xs md:text-sm uppercase tracking-widest shadow-xl shadow-charcoal/20 transition-all duration-300 hover:-translate-y-1 flex justify-center items-center gap-2 group">
                Send OTP Code <i class="fas fa-paper-plane group-hover:translate-x-1 transition-transform"></i>
            </button>
        </form>
    </div>
</body>
</html>