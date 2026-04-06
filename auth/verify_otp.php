<?php
session_start();
require_once '../config/database.php';

if (!isset($_GET['email'])) {
    header("Location: register.php");
    exit();
}

$email = mysqli_real_escape_string($conn, $_GET['email']);
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['verify'])) {
    $otp_input = $_POST['otp1'].$_POST['otp2'].$_POST['otp3'].$_POST['otp4'].$_POST['otp5'].$_POST['otp6'];
    $q_cek = mysqli_query($conn, "SELECT id, otp_expiry FROM users WHERE email = '$email' AND otp_code = '$otp_input' AND is_verified = '0'");
    
    if (mysqli_num_rows($q_cek) > 0) {
        $user = mysqli_fetch_assoc($q_cek);
        $sekarang = date('Y-m-d H:i:s');
        
        if ($sekarang > $user['otp_expiry']) {
            $error = "OTP code has expired. Please register again.";
            mysqli_query($conn, "DELETE FROM users WHERE id = '{$user['id']}'");
        } else {
            mysqli_query($conn, "UPDATE users SET is_verified = '1', otp_code = NULL, otp_expiry = NULL WHERE id = '{$user['id']}'");
            $_SESSION['user_id'] = $user['id'];
            echo "<script>alert('Verification successful! Welcome to BeautyScent.'); window.location.href='../index.php';</script>";
            exit();
        }
    } else {
        $error = "Incorrect OTP code! Please check your email again.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Email - BeautyScent</title>
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
    <div class="absolute bottom-[-10%] left-[-10%] w-80 h-80 bg-charcoal rounded-full blur-[120px] opacity-10 pointer-events-none"></div>

    <div class="max-w-md w-full bg-white rounded-[2rem] shadow-[0_10px_40px_rgba(0,0,0,0.05)] p-8 md:p-10 border border-gray-100 text-center relative z-10">
        
        <div class="w-20 h-20 bg-gradient-to-br from-rose-gold to-pink-300 text-white rounded-full flex items-center justify-center text-3xl mx-auto mb-6 shadow-lg shadow-rose-gold/30">
            <i class="far fa-envelope-open"></i>
        </div>
        
        <h2 class="text-2xl font-black font-serif text-charcoal mb-2">Check Your Email</h2>
        <p class="text-sm text-gray-500 font-medium mb-8">We've sent a 6-digit OTP code to <br><span class="font-bold text-charcoal"><?= htmlspecialchars($email) ?></span></p>

        <?php if($error): ?>
            <div class="bg-red-50 text-red-500 text-xs font-bold p-3 rounded-xl mb-6 border border-red-100">
                <i class="fas fa-exclamation-circle mr-1"></i> <?= $error ?>
            </div>
        <?php endif; ?>

        <form action="" method="POST" class="space-y-8">
            <div class="flex justify-center gap-2 md:gap-3" id="otp-container">
                <?php for($i=1; $i<=6; $i++): ?>
                <input type="text" name="otp<?= $i ?>" maxlength="1" class="w-10 h-12 md:w-12 md:h-14 text-center text-xl font-black text-charcoal bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:border-rose-gold focus:ring-2 focus:ring-rose-gold/20 transition-all" required oninput="focusNext(this, <?= $i ?>)" onkeydown="focusPrev(event, this, <?= $i ?>)">
                <?php endfor; ?>
            </div>

            <button type="submit" name="verify" class="w-full bg-charcoal hover:bg-[#2d1b1f] text-white py-4 rounded-full font-black text-sm uppercase tracking-widest transition-all shadow-lg hover:-translate-y-1">Verify Account</button>
        </form>

        <p class="text-xs text-gray-500 font-medium mt-8">
            Didn't receive the email? Check your Spam folder or <br>
            <a href="register.php" class="text-rose-gold font-bold hover:underline">Register with a different email</a>
        </p>
    </div>

    <script>
        function focusNext(elem, index) {
            if (elem.value.length === 1 && index < 6) {
                document.getElementsByName('otp' + (index + 1))[0].focus();
            }
        }
        function focusPrev(e, elem, index) {
            if (e.key === 'Backspace' && elem.value.length === 0 && index > 1) {
                document.getElementsByName('otp' + (index - 1))[0].focus();
            }
        }
    </script>
</body>
</html>