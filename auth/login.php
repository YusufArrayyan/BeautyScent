<?php
session_start();

// Tangkap parameter redirect jika ada (dari detail_toko.php atau lainnya)
$redirect_url = isset($_GET['redirect']) ? htmlspecialchars($_GET['redirect']) : '';

// Jika sudah login, tendang sesuai redirect atau ke index
if (isset($_SESSION['user_id'])) {
    if (!empty($redirect_url)) {
        header("Location: ../" . $redirect_url);
    } else {
        header("Location: ../index.php");
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log In - BeautyScent</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- FontAwesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts: Playfair Display & Lora -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400..900;1,400..900&family=Lora:ital,wght@0,400..700;1,400..700&display=swap" rel="stylesheet">
    
    <!-- Animate On Scroll -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">

    <script>
        tailwind.config = {
            theme: { 
                extend: { 
                    colors: { 
                        charcoal: '#3a262a', 
                        'rose-gold': '#e8a0bf', 
                        bglight: '#fdfbfb' 
                    }, 
                    fontFamily: { 
                        serif: ['"Playfair Display"', 'serif'],
                        sans: ['"Lora"', 'serif'] 
                    } 
                } 
            }
        }
    </script>
</head>
<body class="bg-bglight font-sans min-h-screen flex items-center justify-center p-5 md:p-10 selection:bg-rose-gold selection:text-white relative overflow-hidden">

    <!-- Ornamen Background Melayang -->
    <div class="absolute top-[-10%] left-[-10%] w-96 h-96 bg-rose-gold rounded-full blur-[120px] opacity-20 pointer-events-none"></div>
    <div class="absolute bottom-[-10%] right-[-10%] w-96 h-96 bg-charcoal rounded-full blur-[120px] opacity-10 pointer-events-none"></div>

    <!-- Container Utama -->
    <div class="max-w-5xl w-full bg-white rounded-[2rem] md:rounded-[3rem] shadow-[0_20px_60px_rgba(0,0,0,0.08)] overflow-hidden flex flex-col lg:flex-row min-h-[600px] relative z-10" data-aos="zoom-in" data-aos-duration="800">
        
        <!-- SISI KIRI: Branding (Sembunyi di Mobile) -->
        <div class="hidden lg:flex w-5/12 bg-gradient-to-br from-[#fdfbfb] to-[#f4e6e8] p-16 flex-col justify-center items-center text-charcoal border-r border-[#f4e6e8] relative overflow-hidden">
            <!-- Glassmorphism Effect -->
            <div class="absolute top-10 right-10 w-32 h-32 bg-white rounded-full blur-2xl"></div>
            <div class="absolute bottom-10 left-10 w-40 h-40 bg-rose-gold/20 rounded-full blur-3xl"></div>
            
            <div class="relative z-10 text-center">
                <div class="bg-gradient-to-br from-rose-gold to-pink-300 w-24 h-24 rounded-full flex items-center justify-center mx-auto mb-8 shadow-2xl shadow-rose-gold/30 transform transition-transform duration-500 scale-105">
                    <i class="fas fa-spa text-4xl text-white"></i>
                </div>
                <h1 class="text-4xl font-black font-serif mb-3 tracking-tight">Beauty<span class="text-rose-gold">Scent</span></h1>
                <p class="text-gray-500 font-medium mb-12 text-sm italic">Log in to discover premium skincare and signature fragrances.</p>

                <div class="space-y-6 text-left inline-block">
                    <div class="flex items-center gap-4 bg-white border border-gray-100 px-5 py-3 rounded-2xl shadow-sm backdrop-blur-sm">
                        <div class="w-8 h-8 rounded-full bg-rose-gold/20 flex items-center justify-center text-rose-gold"><i class="fas fa-gem text-sm"></i></div>
                        <span class="font-bold text-sm tracking-wide text-charcoal">100% Authentic Brands</span>
                    </div>
                    <div class="flex items-center gap-4 bg-white border border-gray-100 px-5 py-3 rounded-2xl shadow-sm backdrop-blur-sm">
                        <div class="w-8 h-8 rounded-full bg-pink-100 flex items-center justify-center text-pink-400"><i class="fas fa-heart text-sm"></i></div>
                        <span class="font-bold text-sm tracking-wide text-charcoal">Curated with Elegance</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- SISI KANAN: Form -->
        <div class="w-full lg:w-7/12 p-8 sm:p-12 lg:p-20 flex flex-col justify-center relative">
            
            <!-- Tombol Kembali -->
            <a href="../index.php" class="absolute top-6 right-6 md:top-8 md:right-8 w-10 h-10 bg-gray-50 border border-gray-100 rounded-full flex items-center justify-center text-gray-400 hover:text-charcoal hover:bg-gray-100 transition-colors shadow-sm">
                <i class="fas fa-times"></i>
            </a>

            <div class="mb-10">
                <div class="lg:hidden bg-gradient-to-br from-rose-gold to-pink-300 w-12 h-12 rounded-full flex items-center justify-center mb-6 shadow-lg shadow-rose-gold/30">
                    <i class="fas fa-spa text-xl text-white"></i>
                </div>
                <h2 class="text-3xl md:text-4xl font-black font-serif text-charcoal mb-3 tracking-tight">Welcome Back</h2>
                <p class="text-sm md:text-base text-gray-500 font-bold">Please log in to your BeautyScent account.</p>
                <?php if(!empty($redirect_url)): ?>
                    <p class="text-[10px] text-rose-gold font-bold mt-2 bg-rose-gold/10 inline-block px-3 py-1 rounded-md"><i class="fas fa-info-circle"></i> Log in required to continue.</p>
                <?php endif; ?>
            </div>

            <!-- Pesan Error/Sukses -->
            <?php if (isset($_SESSION['error_login'])): ?>
                <div class="bg-red-50 border border-red-200 text-red-600 px-5 py-4 mb-6 rounded-2xl text-xs md:text-sm font-bold flex items-center gap-3 animate-pulse">
                    <i class="fas fa-exclamation-circle text-lg"></i> 
                    <span><?= $_SESSION['error_login']; unset($_SESSION['error_login']); ?></span>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['pesan_sukses'])): ?>
                <div class="bg-green-50 border border-green-200 text-green-600 px-5 py-4 mb-6 rounded-2xl text-xs md:text-sm font-bold flex items-center gap-3">
                    <i class="fas fa-check-circle text-lg"></i> 
                    <span><?= $_SESSION['pesan_sukses']; unset($_SESSION['pesan_sukses']); ?></span>
                </div>
            <?php endif; ?>

            <form action="proses_login.php" method="POST" class="space-y-5">
                
                <!-- INPUT TERSEMBUNYI UNTUK REDIRECT -->
                <input type="hidden" name="redirect" value="<?= $redirect_url ?>">

                <!-- Field Email -->
                <div>
                    <label class="block text-[10px] font-black text-charcoal uppercase tracking-widest mb-2 pl-1">Email Address</label>
                    <div class="relative group">
                        <i class="far fa-envelope absolute left-5 top-1/2 -translate-y-1/2 text-gray-400 group-focus-within:text-rose-gold transition-colors"></i>
                        <input type="email" name="email" required placeholder="you@example.com" 
                               class="w-full pl-12 pr-4 py-4 bg-gray-50 border border-gray-200 rounded-2xl focus:outline-none focus:ring-4 focus:ring-rose-gold/10 focus:border-rose-gold focus:bg-white transition-all font-bold text-charcoal placeholder-gray-400 text-sm">
                    </div>
                </div>

                <!-- Field Password -->
                <div>
                    <label class="block text-[10px] font-black text-charcoal uppercase tracking-widest mb-2 pl-1">Password</label>
                    <div class="relative group">
                        <i class="fas fa-lock absolute left-5 top-1/2 -translate-y-1/2 text-gray-400 group-focus-within:text-rose-gold transition-colors"></i>
                        <input type="password" id="passwordField" name="password" required placeholder="••••••••" 
                               class="w-full pl-12 pr-12 py-4 bg-gray-50 border border-gray-200 rounded-2xl focus:outline-none focus:ring-4 focus:ring-rose-gold/10 focus:border-rose-gold focus:bg-white transition-all font-bold text-charcoal tracking-widest placeholder-gray-400 text-sm">
                        <!-- Toggle Password Visibility -->
                        <button type="button" id="togglePassword" class="absolute right-5 top-1/2 -translate-y-1/2 text-gray-400 hover:text-charcoal transition-colors focus:outline-none">
                            <i class="far fa-eye" id="eyeIcon"></i>
                        </button>
                    </div>
                    <div class="text-right mt-3">
                        <a href="forgot_password.php" class="text-[11px] md:text-xs text-charcoal font-black hover:text-rose-gold transition-colors">Forgot Password?</a>
                    </div>
                </div>

                <button type="submit" name="login" class="w-full bg-charcoal hover:bg-[#2d1b1f] text-white py-4 mt-2 rounded-2xl font-black text-xs md:text-sm uppercase tracking-widest shadow-xl shadow-charcoal/20 transition-all duration-300 transform hover:-translate-y-1 flex justify-center items-center gap-3 group">
                    Log In <i class="fas fa-arrow-right group-hover:translate-x-1 transition-transform"></i>
                </button>
            </form>

            <p class="text-center mt-8 text-xs md:text-sm font-bold text-gray-500">
                Don't have an account? <a href="register.php" class="text-rose-gold font-black hover:underline">Register Free</a>
            </p>
        </div>

    </div>

    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init();

        // Script untuk Fungsi Lihat Password
        const togglePassword = document.getElementById('togglePassword');
        const passwordField = document.getElementById('passwordField');
        const eyeIcon = document.getElementById('eyeIcon');

        togglePassword.addEventListener('click', function (e) {
            // Toggle the type attribute
            const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordField.setAttribute('type', type);
            // Toggle the eye slash icon
            eyeIcon.classList.toggle('fa-eye');
            eyeIcon.classList.toggle('fa-eye-slash');
        });
    </script>
</body>
</html>