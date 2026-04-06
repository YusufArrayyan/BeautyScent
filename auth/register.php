<?php
session_start();

// Tangkap parameter redirect jika ada (agar tidak hilang saat pindah dari login ke register)
$redirect_url = isset($_GET['redirect']) ? htmlspecialchars($_GET['redirect']) : '';

// Jika sudah login, tendang ke index atau redirect url
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
    <title>Register - BeautyScent</title>
    
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
    <style>
        /* Smooth Toggle Animation for Toko Form */
        .form-toko { 
            display: grid; 
            grid-template-rows: 0fr; 
            opacity: 0; 
            transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1); 
            overflow: hidden;
        }
        .form-toko.show { 
            grid-template-rows: 1fr; 
            opacity: 1; 
            margin-top: 1.25rem;
            margin-bottom: 1.25rem;
        }
        .form-toko-inner { min-height: 0; }
        
        /* Hide scrollbar for cleaner look */
        .hide-scrollbar::-webkit-scrollbar { display: none; }
        .hide-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
    </style>
</head>
<body class="bg-bglight font-sans min-h-screen flex items-center justify-center p-5 md:p-10 selection:bg-rose-gold selection:text-white relative overflow-hidden">

    <!-- Ornamen Background Melayang -->
    <div class="absolute top-[-10%] right-[-10%] w-96 h-96 bg-pink-300 rounded-full blur-[120px] opacity-10 pointer-events-none animate-pulse"></div>
    <div class="absolute bottom-[-10%] left-[-10%] w-96 h-96 bg-rose-gold rounded-full blur-[120px] opacity-20 pointer-events-none"></div>

    <!-- Container Utama -->
    <div class="max-w-6xl w-full bg-white rounded-[2rem] md:rounded-[3rem] shadow-[0_20px_60px_rgba(0,0,0,0.08)] flex flex-col lg:flex-row min-h-[700px] relative z-10 overflow-hidden" data-aos="zoom-in" data-aos-duration="800">
        
        <!-- SISI KIRI: Branding & Info (Sembunyi di Mobile) -->
        <div class="hidden lg:flex w-5/12 bg-gradient-to-br from-[#fdfbfb] to-[#f4e6e8] p-16 flex-col justify-center items-center text-charcoal border-r border-[#f4e6e8] relative overflow-hidden">
            <!-- Glassmorphism Effect -->
            <div class="absolute top-20 left-10 w-32 h-32 bg-white rounded-full blur-2xl"></div>
            <div class="absolute bottom-20 right-10 w-40 h-40 bg-rose-gold/20 rounded-full blur-3xl"></div>
            
            <div class="relative z-10 text-center w-full">
                <div class="bg-gradient-to-br from-rose-gold to-pink-300 w-20 h-20 rounded-full flex items-center justify-center mx-auto mb-8 shadow-2xl shadow-rose-gold/30 transform transition-transform duration-500 scale-105">
                    <i class="fas fa-crown text-3xl text-white"></i>
                </div>
                <h1 class="text-4xl font-black font-serif mb-3 tracking-tight leading-tight text-charcoal">Join The<br>Elegance</h1>
                <p class="text-gray-500 font-medium mb-10 text-sm italic">Access premium beauty collections or launch your luxury brand.</p>

                <!-- Grid Stats -->
                <div class="grid grid-cols-2 gap-4 w-full">
                    <div class="bg-white border border-gray-100 shadow-sm p-5 rounded-2xl backdrop-blur-sm text-left">
                        <div class="w-8 h-8 rounded-full bg-rose-gold/20 text-rose-gold flex items-center justify-center mb-3"><i class="fas fa-gem text-xs"></i></div>
                        <h4 class="text-xl font-black text-charcoal">50+</h4>
                        <p class="text-[9px] font-sans font-bold tracking-widest text-gray-400 mt-1 uppercase">Curated Brands</p>
                    </div>
                    <div class="bg-white border border-gray-100 shadow-sm p-5 rounded-2xl backdrop-blur-sm text-left">
                        <div class="w-8 h-8 rounded-full bg-pink-100 text-pink-400 flex items-center justify-center mb-3"><i class="fas fa-shopping-bag text-xs"></i></div>
                        <h4 class="text-xl font-black text-charcoal">12K+</h4>
                        <p class="text-[9px] font-sans font-bold tracking-widest text-gray-400 mt-1 uppercase">Happy Clients</p>
                    </div>
                    <div class="bg-white border border-gray-100 shadow-sm p-5 rounded-2xl backdrop-blur-sm text-left">
                        <div class="w-8 h-8 rounded-full bg-purple-100 text-purple-400 flex items-center justify-center mb-3"><i class="fas fa-star text-xs"></i></div>
                        <h4 class="text-xl font-black text-charcoal">100%</h4>
                        <p class="text-[9px] font-sans font-bold tracking-widest text-gray-400 mt-1 uppercase">Authenticity</p>
                    </div>
                    <div class="bg-white border border-gray-100 shadow-sm p-5 rounded-2xl backdrop-blur-sm text-left">
                        <div class="w-8 h-8 rounded-full bg-[#f4e6e8] text-charcoal flex items-center justify-center mb-3"><i class="fas fa-heart text-xs"></i></div>
                        <h4 class="text-xl font-black text-charcoal">24/7</h4>
                        <p class="text-[9px] font-sans font-bold tracking-widest text-gray-400 mt-1 uppercase">Boutique Support</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- SISI KANAN: FORM REGISTRASI -->
        <div class="w-full lg:w-7/12 flex flex-col relative h-[100vh] lg:h-auto max-h-[850px]">
            
            <!-- Tombol Kembali -->
            <a href="../index.php" class="absolute top-6 right-6 md:top-8 md:right-8 w-10 h-10 bg-gray-50 border border-gray-100 rounded-full flex items-center justify-center text-gray-400 hover:text-charcoal hover:bg-gray-100 transition-colors shadow-sm z-20">
                <i class="fas fa-times"></i>
            </a>

            <!-- Scrollable Form Area -->
            <div class="flex-1 overflow-y-auto hide-scrollbar p-8 sm:p-12 lg:p-16">
                
                <div class="mb-8 pt-4 md:pt-0">
                    <div class="lg:hidden bg-gradient-to-br from-rose-gold to-pink-300 w-12 h-12 rounded-full flex items-center justify-center mb-6 shadow-lg shadow-rose-gold/30">
                        <i class="fas fa-crown text-xl text-white"></i>
                    </div>
                    <h2 class="text-3xl md:text-4xl font-black font-serif text-charcoal mb-2 tracking-tight">Create an Account</h2>
                    <p class="text-sm text-gray-500 font-bold">Select your account type and fill in your details.</p>
                </div>

                <!-- Pesan Error dari Session -->
                <?php if(isset($_SESSION['error_register'])): ?>
                    <div class="bg-red-50 border border-red-200 text-red-600 px-5 py-4 mb-6 rounded-2xl text-xs font-bold flex items-center gap-3 animate-pulse">
                        <i class="fas fa-exclamation-circle text-lg"></i> <?= $_SESSION['error_register']; unset($_SESSION['error_register']); ?>
                    </div>
                <?php endif; ?>

                <form action="proses_register.php" method="POST">
                    
                    <!-- Hidden Inputs -->
                    <input type="hidden" name="role" id="role-input" value="pembeli">
                    <input type="hidden" name="redirect" value="<?= $redirect_url ?>">

                    <!-- TOGGLE ROLE (PREMIUM PILL) -->
                    <div class="flex bg-gray-50 p-1.5 rounded-2xl mb-8 border border-gray-200 relative">
                        <button type="button" onclick="toggleRole('pembeli')" id="btn-pembeli" class="flex-1 py-3.5 md:py-4 rounded-xl font-black text-[10px] md:text-xs uppercase tracking-widest transition-all duration-300 bg-charcoal text-white shadow-lg shadow-charcoal/20 z-10 flex justify-center items-center gap-2">
                            <i class="fas fa-user"></i> Beauty Buyer
                        </button>
                        <button type="button" onclick="toggleRole('toko')" id="btn-teknisi" class="flex-1 py-3.5 md:py-4 rounded-xl font-black text-[10px] md:text-xs uppercase tracking-widest transition-all duration-300 text-gray-400 hover:text-charcoal z-10 flex justify-center items-center gap-2">
                            <i class="fas fa-store"></i> Brand / Seller
                        </button>
                    </div>

                    <div class="space-y-5">
                        <!-- Input Dasar Grid -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div>
                                <label class="block text-[10px] font-black text-charcoal uppercase tracking-widest mb-2 pl-1">Full Name</label>
                                <div class="relative group">
                                    <i class="far fa-user absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 group-focus-within:text-rose-gold transition-colors"></i>
                                    <input type="text" name="nama" required placeholder="Legal Name" class="w-full pl-11 pr-4 py-3.5 bg-gray-50 border border-gray-200 rounded-2xl focus:outline-none focus:bg-white focus:border-rose-gold focus:ring-4 focus:ring-rose-gold/10 font-bold text-charcoal text-sm transition-all">
                                </div>
                            </div>
                            <div>
                                <label class="block text-[10px] font-black text-charcoal uppercase tracking-widest mb-2 pl-1">Email Address</label>
                                <div class="relative group">
                                    <i class="far fa-envelope absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 group-focus-within:text-rose-gold transition-colors"></i>
                                    <input type="email" name="email" required placeholder="you@example.com" class="w-full pl-11 pr-4 py-3.5 bg-gray-50 border border-gray-200 rounded-2xl focus:outline-none focus:bg-white focus:border-rose-gold focus:ring-4 focus:ring-rose-gold/10 font-bold text-charcoal text-sm transition-all">
                                </div>
                            </div>
                        </div>

                        <div>
                            <label class="block text-[10px] font-black text-charcoal uppercase tracking-widest mb-2 pl-1">Phone Number</label>
                            <div class="relative group">
                                <i class="fab fa-whatsapp absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 group-focus-within:text-rose-gold transition-colors"></i>
                                <input type="text" name="no_hp" required placeholder="Example: 08123456789" class="w-full pl-11 pr-4 py-3.5 bg-gray-50 border border-gray-200 rounded-2xl focus:outline-none focus:bg-white focus:border-rose-gold focus:ring-4 focus:ring-rose-gold/10 font-bold text-charcoal text-sm transition-all">
                            </div>
                        </div>

                        <!-- FORM TAMBAHAN KHUSUS TOKO (SMOOTH ACCORDION) -->
                        <div id="section-teknisi" class="form-toko">
                            <div class="form-toko-inner bg-rose-gold/5 border border-rose-gold/20 p-5 md:p-6 rounded-[2rem] space-y-5">
                                <h3 class="text-rose-gold font-black text-[10px] md:text-xs uppercase tracking-[0.2em] flex items-center gap-2">
                                    <i class="fas fa-crown"></i> Brand Details
                                </h3>
                                
                                <div>
                                    <label class="block text-[10px] font-black text-charcoal uppercase tracking-widest mb-2 pl-1">Brand/Store Name</label>
                                    <div class="relative group">
                                        <i class="fas fa-store-alt absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 group-focus-within:text-rose-gold transition-colors"></i>
                                        <input type="text" name="nama_toko" placeholder="Dior Beauty" class="w-full pl-11 pr-4 py-3.5 bg-white border border-rose-gold/20 rounded-xl focus:outline-none focus:border-rose-gold focus:ring-4 focus:ring-rose-gold/10 font-bold text-charcoal text-sm transition-all">
                                    </div>
                                </div>
                                
                                <div>
                                    <label class="block text-[10px] font-black text-charcoal uppercase tracking-widest mb-2 pl-1">Beauty Category</label>
                                    <div class="relative">
                                        <select name="kategori" class="w-full pl-4 pr-10 py-3.5 bg-white border border-rose-gold/20 rounded-xl focus:outline-none focus:border-rose-gold focus:ring-4 focus:ring-rose-gold/10 font-bold text-charcoal text-sm transition-all appearance-none cursor-pointer">
                                            <option value="perfume">✨ Signature Perfumes</option>
                                            <option value="skincare">🌿 Luxury Skincare</option>
                                            <option value="makeup">💄 Premium Makeup</option>
                                            <option value="bodycare">🛁 Body & Hair Care</option>
                                        </select>
                                        <i class="fas fa-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-xs"></i>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Passwords Grid -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5 pt-2">
                            <div>
                                <label class="block text-[10px] font-black text-charcoal uppercase tracking-widest mb-2 pl-1">Password</label>
                                <div class="relative group">
                                    <i class="fas fa-lock absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 group-focus-within:text-rose-gold transition-colors"></i>
                                    <input type="password" id="pass1" name="password" required placeholder="Min. 8 Chars" class="w-full pl-11 pr-10 py-3.5 bg-gray-50 border border-gray-200 rounded-2xl focus:outline-none focus:bg-white focus:border-rose-gold focus:ring-4 focus:ring-rose-gold/10 font-bold text-charcoal text-sm tracking-widest transition-all">
                                    <button type="button" onclick="toggleVis('pass1', 'eye1')" class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-charcoal focus:outline-none"><i id="eye1" class="far fa-eye"></i></button>
                                </div>
                            </div>
                            <div>
                                <label class="block text-[10px] font-black text-charcoal uppercase tracking-widest mb-2 pl-1">Confirm Password</label>
                                <div class="relative group">
                                    <i class="fas fa-check-double absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 group-focus-within:text-rose-gold transition-colors"></i>
                                    <input type="password" id="pass2" name="konfirmasi" required placeholder="Confirm" class="w-full pl-11 pr-10 py-3.5 bg-gray-50 border border-gray-200 rounded-2xl focus:outline-none focus:bg-white focus:border-rose-gold focus:ring-4 focus:ring-rose-gold/10 font-bold text-charcoal text-sm tracking-widest transition-all">
                                    <button type="button" onclick="toggleVis('pass2', 'eye2')" class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-charcoal focus:outline-none"><i id="eye2" class="far fa-eye"></i></button>
                                </div>
                            </div>
                        </div>

                        <button type="submit" name="register" class="w-full bg-charcoal hover:bg-[#2d1b1f] text-white py-4 mt-6 rounded-2xl font-black text-xs md:text-sm uppercase tracking-widest shadow-xl shadow-charcoal/20 transition-all duration-300 transform hover:-translate-y-1 flex items-center justify-center gap-3 group">
                            Create Account <i class="fas fa-arrow-right group-hover:translate-x-1 transition-transform"></i>
                        </button>
                    </div>
                </form>

                <p class="text-center mt-8 text-xs md:text-sm font-bold text-gray-500 pb-8">
                    Already have an account? 
                    <a href="login.php<?= !empty($redirect_url) ? '?redirect='.urlencode($redirect_url) : '' ?>" class="text-rose-gold font-black hover:underline">Log in here</a>
                </p>
            </div>
        </div>

    </div>

    <!-- SCRIPTS -->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init();

        // Script untuk Toggle Role Pembeli / Teknisi
        function toggleRole(role) {
            const btnP = document.getElementById('btn-pembeli');
            const btnT = document.getElementById('btn-teknisi');
            const sectionT = document.getElementById('section-teknisi');
            const roleInput = document.getElementById('role-input');

            const activeClass = 'flex-1 py-3.5 md:py-4 rounded-xl font-black text-[10px] md:text-xs uppercase tracking-widest transition-all duration-300 bg-charcoal text-white shadow-lg shadow-charcoal/20 z-10 flex justify-center items-center gap-2';
            const inactiveClass = 'flex-1 py-3.5 md:py-4 rounded-xl font-black text-[10px] md:text-xs uppercase tracking-widest transition-all duration-300 text-gray-400 hover:text-charcoal z-10 flex justify-center items-center gap-2';

            roleInput.value = role;

            if(role === 'pembeli') {
                btnP.className = activeClass;
                btnT.className = inactiveClass;
                sectionT.classList.remove('show');
            } else {
                btnT.className = activeClass;
                btnP.className = inactiveClass;
                sectionT.classList.add('show');
            }
        }

        // Script untuk fungsi Lihat Password
        function toggleVis(fieldId, iconId) {
            const field = document.getElementById(fieldId);
            const icon = document.getElementById(iconId);
            if (field.type === "password") {
                field.type = "text";
                icon.classList.replace("fa-eye", "fa-eye-slash");
            } else {
                field.type = "password";
                icon.classList.replace("fa-eye-slash", "fa-eye");
            }
        }
    </script>
</body>
</html>