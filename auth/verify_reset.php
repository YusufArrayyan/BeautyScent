<?php
session_start();
require_once '../config/database.php';

if (!isset($_GET['email'])) {
    header("Location: login.php");
    exit();
}

$email = mysqli_real_escape_string($conn, $_GET['email']);
$error = '';

if (isset($_POST['reset_password'])) {
    $otp_input = $_POST['otp1'].$_POST['otp2'].$_POST['otp3'].$_POST['otp4'].$_POST['otp5'].$_POST['otp6'];
    $password_baru = $_POST['password_baru'];
    $konfirmasi = $_POST['konfirmasi'];

    if ($password_baru !== $konfirmasi) {
        $error = "New password and confirmation do not match!";
    } elseif (strlen($password_baru) < 8) {
        $error = "Password must be at least 8 characters!";
    } else {
        $cek = mysqli_query($conn, "SELECT id, otp_expiry FROM users WHERE email = '$email' AND otp_code = '$otp_input'");
        
        if (mysqli_num_rows($cek) > 0) {
            $user = mysqli_fetch_assoc($cek);
            $sekarang = date('Y-m-d H:i:s');
            
            if ($sekarang > $user['otp_expiry']) {
                $error = "OTP code has expired. Please request a new one.";
            } else {
                $hashed_password = password_hash($password_baru, PASSWORD_DEFAULT);
                mysqli_query($conn, "UPDATE users SET password = '$hashed_password', otp_code = NULL, otp_expiry = NULL WHERE id = '{$user['id']}'");
                
                $_SESSION['pesan_sukses'] = "Password changed successfully! Please log in with your new password.";
                header("Location: login.php");
                exit();
            }
        } else {
            $error = "Incorrect OTP code! Please check your email.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - BeautyScent</title>
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
    
    <div class="absolute top-[-10%] left-[-10%] w-96 h-96 bg-rose-gold rounded-full blur-[120px] opacity-15 pointer-events-none"></div>
    <div class="absolute bottom-[-10%] right-[-10%] w-80 h-80 bg-charcoal rounded-full blur-[120px] opacity-10 pointer-events-none"></div>

    <div class="max-w-md w-full bg-white rounded-[2rem] shadow-[0_20px_60px_rgba(0,0,0,0.05)] p-8 md:p-10 border border-gray-100 relative z-10">
        
        <div class="text-center mb-8">
            <div class="w-16 h-16 bg-gradient-to-br from-rose-gold to-pink-300 text-white rounded-full flex items-center justify-center text-2xl mx-auto mb-5 shadow-lg shadow-rose-gold/30">
                <i class="fas fa-shield-alt"></i>
            </div>
            <h2 class="text-2xl font-black font-serif text-charcoal mb-2 tracking-tight">Reset Password</h2>
            <p class="text-xs text-gray-500 font-medium">Enter the 6-digit OTP sent to <br><span class="font-bold text-charcoal"><?= htmlspecialchars($email) ?></span> and set your new password.</p>
        </div>

        <?php if($error): ?>
            <div class="bg-red-50 text-red-500 text-xs font-bold p-4 rounded-2xl mb-6 border border-red-100 flex items-center gap-2">
                <i class="fas fa-exclamation-circle text-lg"></i> <?= $error ?>
            </div>
        <?php endif; ?>

        <form action="" method="POST" class="space-y-6">
            
            <div>
                <label class="block text-[10px] font-black text-charcoal uppercase tracking-widest mb-3 pl-1 text-center">6-Digit OTP Code</label>
                <div class="flex justify-center gap-2" id="otp-container">
                    <?php for($i=1; $i<=6; $i++): ?>
                    <input type="text" name="otp<?= $i ?>" maxlength="1" class="w-10 h-12 md:w-12 md:h-14 text-center text-xl font-black text-charcoal bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:border-rose-gold focus:ring-2 focus:ring-rose-gold/20 transition-all" required oninput="focusNext(this, <?= $i ?>)" onkeydown="focusPrev(event, this, <?= $i ?>)">
                    <?php endfor; ?>
                </div>
            </div>

            <div>
                <label class="block text-[10px] font-black text-charcoal uppercase tracking-widest mb-2 pl-1 mt-4">New Password</label>
                <div class="relative group">
                    <i class="fas fa-lock absolute left-5 top-1/2 -translate-y-1/2 text-gray-400 group-focus-within:text-rose-gold transition-colors"></i>
                    <input type="password" id="pass1" name="password_baru" required placeholder="Minimum 8 characters" class="w-full pl-12 pr-12 py-3.5 bg-gray-50 border border-gray-200 rounded-2xl focus:outline-none focus:ring-4 focus:ring-rose-gold/10 focus:border-rose-gold focus:bg-white transition-all font-bold text-charcoal tracking-widest placeholder-gray-400 text-sm">
                    <button type="button" onclick="toggleVis('pass1', 'eye1')" class="absolute right-5 top-1/2 -translate-y-1/2 text-gray-400 hover:text-charcoal focus:outline-none"><i id="eye1" class="far fa-eye"></i></button>
                </div>
            </div>

            <div>
                <label class="block text-[10px] font-black text-charcoal uppercase tracking-widest mb-2 pl-1">Confirm New Password</label>
                <div class="relative group">
                    <i class="fas fa-check-double absolute left-5 top-1/2 -translate-y-1/2 text-gray-400 group-focus-within:text-rose-gold transition-colors"></i>
                    <input type="password" id="pass2" name="konfirmasi" required placeholder="Re-type password" class="w-full pl-12 pr-12 py-3.5 bg-gray-50 border border-gray-200 rounded-2xl focus:outline-none focus:ring-4 focus:ring-rose-gold/10 focus:border-rose-gold focus:bg-white transition-all font-bold text-charcoal tracking-widest placeholder-gray-400 text-sm">
                    <button type="button" onclick="toggleVis('pass2', 'eye2')" class="absolute right-5 top-1/2 -translate-y-1/2 text-gray-400 hover:text-charcoal focus:outline-none"><i id="eye2" class="far fa-eye"></i></button>
                </div>
            </div>

            <button type="submit" name="reset_password" class="w-full bg-gradient-to-r from-rose-gold to-pink-400 hover:brightness-110 text-white py-4 mt-4 rounded-2xl font-black text-xs md:text-sm uppercase tracking-widest shadow-xl shadow-rose-gold/20 transition-all duration-300 hover:-translate-y-1">
                Save & Reset Password
            </button>
        </form>

        <p class="text-center mt-6 text-xs font-bold text-gray-500">
            Wrong email? <a href="forgot_password.php" class="text-charcoal hover:text-rose-gold transition-colors underline">Go back</a>
        </p>
    </div>

    <script>
        function focusNext(elem, index) {
            if (elem.value.length === 1 && index < 6) { document.getElementsByName('otp' + (index + 1))[0].focus(); }
        }
        function focusPrev(e, elem, index) {
            if (e.key === 'Backspace' && elem.value.length === 0 && index > 1) { document.getElementsByName('otp' + (index - 1))[0].focus(); }
        }
        function toggleVis(fieldId, iconId) {
            const field = document.getElementById(fieldId);
            const icon = document.getElementById(iconId);
            if (field.type === "password") { field.type = "text"; icon.classList.replace("fa-eye", "fa-eye-slash"); } 
            else { field.type = "password"; icon.classList.replace("fa-eye-slash", "fa-eye"); }
        }
    </script>
</body>
</html>