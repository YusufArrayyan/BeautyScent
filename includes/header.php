<?php
// Pastikan tidak ada spasi atau baris kosong sebelum tag PHP ini!
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$is_logged_in = isset($_SESSION['user_id']);
$base_url = ''; // Root-relative (PHP dev server runs from project root). Ubah ini jika project ada di dalam folder (misal: '/namaproject')

// Variabel Default
$nama_user = 'Guest';
$email_user = 'user@beautyscent.com'; 
$role_user = '';
$foto_user_url = ''; 
$notif_count = 0;
$cart_count = 0;
$chat_count = 0;
$show_review_notif = false; // [FITUR BARU] Flag Notif Review Aplikasi
$user_id = $is_logged_in ? $_SESSION['user_id'] : null;

// SINKRONISASI 100% KE DATABASE
if($is_logged_in) {
    try {
        if(isset($conn)) {
            $q_user_sync = mysqli_query($conn, "SELECT nama_lengkap, email, role, foto_profil FROM users WHERE id = '$user_id'");
            
            if($q_user_sync && mysqli_num_rows($q_user_sync) > 0) {
                $user_data = mysqli_fetch_assoc($q_user_sync);
                $nama_user = $user_data['nama_lengkap'];
                $email_user = $user_data['email'];
                $role_user = strtolower(trim($user_data['role'])); 
                
                // [PERBAIKAN] Sinkronisasi session dengan database agar tidak terlempar dari panel admin
                $_SESSION['role'] = $role_user;
                
                // LOGIKA FOTO PROFIL
                if (!empty($user_data['foto_profil']) && file_exists(__DIR__ . '/../uploads/profil/' . $user_data['foto_profil'])) {
                    $foto_user_url = $base_url . '/uploads/profil/' . $user_data['foto_profil'];
                } else {
                    $foto_user_url = "https://ui-avatars.com/api/?name=" . urlencode($nama_user) . "&background=3a262a&color=fff&bold=true";
                }
            }
            
            $q_cart = mysqli_query($conn, "SELECT COUNT(id) as total FROM keranjang WHERE user_id = '$user_id'");
            if($q_cart) $cart_count = mysqli_fetch_assoc($q_cart)['total'];

            $q_notif = mysqli_query($conn, "SELECT COUNT(id) as total FROM pesanan WHERE user_id = '$user_id' AND status IN ('pending', 'diproses')");
            if($q_notif) $notif_count = mysqli_fetch_assoc($q_notif)['total'];

            $q_chat = mysqli_query($conn, "SELECT COUNT(id) as total FROM chat_messages WHERE penerima_id = '$user_id' AND is_read = 0");
            if($q_chat) $chat_count = mysqli_fetch_assoc($q_chat)['total'];

            // [FITUR BARU] Cek Kelayakan Notif Ulasan Platform (Hanya untuk Pembeli)
            if ($role_user === 'pembeli') {
                $q_cek_selesai = mysqli_query($conn, "SELECT id FROM pesanan WHERE user_id = '$user_id' AND status = 'selesai' LIMIT 1");
                $q_cek_rev_app = mysqli_query($conn, "SELECT id FROM ulasan_platform WHERE user_id = '$user_id'");
                
                if ($q_cek_selesai && mysqli_num_rows($q_cek_selesai) > 0) {
                    if ($q_cek_rev_app && mysqli_num_rows($q_cek_rev_app) == 0) {
                        $show_review_notif = true; // Nyalakan Teror Lonceng Merah!
                    }
                }
            }
        }
    } catch (Exception $e) {
        error_log("Header DB Error: " . $e->getMessage());
    }
}

// Kalkulasi Total Semua Notifikasi (Pesanan + Review)
$total_all_notif = $notif_count + ($show_review_notif ? 1 : 0);

// Deteksi halaman aktif untuk Bottom Nav Mobile
$current_url = $_SERVER['REQUEST_URI'];
$nav_active = 'beranda';
if (strpos($current_url, 'kategori.php') !== false) $nav_active = 'eksplor';
elseif (strpos($current_url, 'pesanan.php') !== false || strpos($current_url, 'admin/laporan.php') !== false) $nav_active = 'pesanan';
elseif (strpos($current_url, 'profil.php') !== false || strpos($current_url, 'login.php') !== false || strpos($current_url, 'admin/index.php') !== false || strpos($current_url, 'toko/index.php') !== false) $nav_active = 'saya';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title>BeautyScent - Premium Beauty & Cosmetics</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;0,700;0,800;0,900;1,400;1,600;1,700&family=Lora:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: { 
                extend: { 
                    colors: { 
                        darkest: '#3a2b2c', navy: '#4a3b3c', orange: '#e8a0bf',
                        lightorange: '#fff0f5', red: '#d4af37', bglight: '#fffafb', alertred: '#f472b6'
                    },
                    fontFamily: { 
                        sans: ['"Lora"', 'serif'],
                        serif: ['"Playfair Display"', 'serif'] 
                    },
                    boxShadow: { 'mega': '0 20px 50px -12px rgba(232, 160, 191, 0.25)', 'glow': '0 0 15px rgba(232, 160, 191, 0.6)', 'bottom-nav': '0 -4px 20px rgba(232, 160, 191, 0.1)' }
                } 
            }
        }
    </script>
    <style>
        .hide-scrollbar::-webkit-scrollbar { display: none; }
        .hide-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
        
        /* DROPDOWN MEGA AGAR CLICKABLE (DESKTOP) */
        .mega-container { position: relative; }
        .mega-dropdown { 
            visibility: hidden; 
            opacity: 0; 
            transform: translateY(10px); 
            transition: all 0.2s ease-in-out;
            pointer-events: none;
        }
        .mega-container:hover .mega-dropdown { 
            visibility: visible; 
            opacity: 1; 
            transform: translateY(0); 
            pointer-events: auto;
        }
        .mega-dropdown::before {
            content: ''; position: absolute; top: -15px; left: 0; right: 0; height: 15px; background: transparent;
        }
        
        @keyframes ticker { 0% { transform: translateY(0); } 33% { transform: translateY(-24px); } 66% { transform: translateY(-48px); } 100% { transform: translateY(0); } }
        .ticker-wrap { height: 24px; overflow: hidden; }
        .ticker-content { animation: ticker 9s infinite; }
        .ticker-item { height: 24px; display: flex; align-items: center; }
        .pb-safe { padding-bottom: env(safe-area-inset-bottom); }
    </style>
</head>
<body class="bg-bglight font-sans text-gray-800 antialiased flex flex-col min-h-screen pb-[68px] md:pb-0">

    <div class="bg-darkest text-white text-[10px] md:text-[11px] font-semibold py-2 hidden lg:block border-b border-white/5 relative z-50">
        <div class="max-w-screen-xl mx-auto px-8 flex justify-between items-center w-full">
            <div class="flex items-center gap-3">
                <span class="bg-gradient-to-r from-orange to-pink-400 text-white px-2 py-0.5 rounded uppercase tracking-widest text-[9px] font-black">INFO</span>
                <div class="ticker-wrap w-64">
                    <div class="ticker-content flex flex-col">
                        <div class="ticker-item hover:text-orange cursor-pointer transition-colors"><i class="fas fa-magic text-orange mr-2"></i> New Arrival: Floral Perfume Collection!</div>
                        <div class="ticker-item hover:text-orange cursor-pointer transition-colors"><i class="fas fa-gem text-alertred mr-2"></i> Get 20% Off Skincare This Weekend</div>
                        <div class="ticker-item hover:text-orange cursor-pointer transition-colors"><i class="fas fa-crown text-blue-400 mr-2"></i> Partner with Our Premium Brands</div>
                    </div>
                </div>
            </div>
            <div class="flex items-center gap-5 text-gray-300">
                <a href="<?= $base_url ?>/auth/register.php" class="text-orange hover:text-white transition-colors flex items-center gap-1.5"><i class="fas fa-arrow-right transform -rotate-45"></i> Start Selling</a>
                <span class="w-1 h-1 bg-gray-600 rounded-full"></span>
                <a href="<?= $base_url ?>/halaman.php?p=Pusat Bantuan (FAQ)" class="hover:text-white transition-colors">Help Center</a>
                <span class="w-1 h-1 bg-gray-600 rounded-full"></span>
                <a href="#" class="hover:text-white transition-colors flex items-center gap-1.5"><i class="fas fa-globe text-pink-300"></i> Worldwide</a>
            </div>
        </div>
    </div>

    <nav class="bg-navy/95 backdrop-blur-xl text-white py-2 md:py-3 sticky top-0 z-40 shadow-lg border-b border-white/10 hidden md:block">
        <div class="max-w-screen-xl mx-auto px-4 md:px-8 flex justify-between items-center w-full gap-4">
            
            <a href="<?= $base_url ?>/index.php" class="shrink-0 group mr-auto lg:mr-8 flex items-center gap-2 md:gap-3">
                <img src="<?= $base_url ?>/assets/img/logo.png" alt="Logo" class="h-10 md:h-16 lg:h-20 w-auto object-contain group-hover:rotate-12 transition-transform duration-300" onerror="this.style.display='none'; document.getElementById('fallback-icon').classList.remove('hidden');">
                <div id="fallback-icon" class="hidden bg-gradient-to-br from-orange to-red-500 p-2 rounded-xl text-white text-xs md:text-sm shadow-lg"><i class="fas fa-spa"></i></div>
                <div class="flex items-center font-serif">
                    <span class="text-lg md:text-3xl font-black tracking-wide text-orange drop-shadow-md">Beauty</span>
                    <span class="text-lg md:text-3xl font-black tracking-wide text-navy ml-1 drop-shadow-md">Scent</span>
                </div>
            </a>

            <form action="<?= $base_url ?>/kategori.php" method="GET" class="hidden lg:flex flex-1 max-w-sm relative group ml-auto mr-8">
                <div class="flex w-full bg-white/10 border border-white/20 rounded-full overflow-hidden focus-within:border-orange focus-within:ring-1 focus-within:ring-orange/50 transition-all duration-300">
                    <input type="text" name="q" placeholder="Search perfumes, skincare..." class="w-full py-2 px-4 bg-transparent text-white placeholder-gray-400 focus:outline-none text-xs font-medium">
                    <button type="submit" class="bg-orange/80 hover:bg-orange px-4 text-white text-xs transition-colors"><i class="fas fa-search"></i></button>
                </div>
            </form>

            <div class="flex items-center gap-3 md:gap-5 shrink-0">
                <?php if($is_logged_in): ?>
                
                <div class="mega-container cursor-pointer relative text-gray-300 hover:text-white transition-colors p-2 md:mr-1">
                    <i class="far fa-bell text-lg md:text-xl"></i>
                    <?php if($total_all_notif > 0): ?>
                        <span class="absolute top-1 right-1 bg-alertred text-white text-[8px] font-black w-4 h-4 rounded-full flex items-center justify-center border-[1.5px] border-navy shadow-sm animate-pulse"><?= $total_all_notif ?></span>
                    <?php endif; ?>
                    
                    <div class="mega-dropdown absolute right-[-50px] md:right-[-20px] top-[120%] mt-1 w-72 bg-white rounded-2xl shadow-mega border border-gray-100 z-[100] text-sm flex flex-col overflow-hidden cursor-default">
                        <div class="p-4 border-b border-gray-100 bg-gradient-to-br from-navy to-[#111144] flex justify-between items-center">
                            <h4 class="font-black text-white text-sm">Notifications</h4>
                            <?php if($total_all_notif > 0): ?>
                                <span class="text-[9px] bg-alertred text-white px-2 py-0.5 rounded-full font-black"><?= $total_all_notif ?> New</span>
                            <?php endif; ?>
                        </div>
                        <div class="max-h-[60vh] overflow-y-auto bg-white">
                            
                            <?php if($show_review_notif): ?>
                            <a href="<?= $base_url ?>/pembeli/ulasan_platform.php" class="block p-4 border-b border-gray-50 hover:bg-orange/5 transition-colors group cursor-pointer">
                                <div class="flex gap-3">
                                    <div class="w-10 h-10 rounded-full bg-orange/10 text-orange border border-orange/20 flex items-center justify-center shrink-0 group-hover:scale-110 transition-transform">
                                        <i class="fas fa-heart animate-pulse text-lg"></i>
                                    </div>
                                    <div>
                                        <h5 class="text-[11px] font-black text-navy mb-0.5">Order Complete! 🎉</h5>
                                        <p class="text-[10px] text-gray-500 font-medium leading-snug mb-1.5">Share your experience with BeautyScent and help other beauty lovers.</p>
                                        <span class="text-[9px] bg-orange text-white px-2 py-1 rounded border border-orange font-bold mt-1 inline-block hover:bg-navy transition-colors">Leave a Review &rarr;</span>
                                    </div>
                                </div>
                            </a>
                            <?php endif; ?>

                            <?php if($notif_count > 0): ?>
                            <a href="<?= $base_url ?>/pembeli/pesanan.php" class="block p-4 border-b border-gray-50 hover:bg-blue-50 transition-colors group cursor-pointer">
                                <div class="flex gap-3">
                                    <div class="w-10 h-10 rounded-full bg-blue-100 text-blue-500 flex items-center justify-center shrink-0 group-hover:scale-110 transition-transform">
                                        <i class="fas fa-box-open"></i>
                                    </div>
                                    <div>
                                        <h5 class="text-[11px] font-black text-navy mb-0.5">Orders In Progress</h5>
                                        <p class="text-[10px] text-gray-500 font-medium leading-snug">You have <b><?= $notif_count ?> order(s)</b> currently active or being processed.</p>
                                        <span class="text-[9px] text-blue-500 font-bold mt-1 inline-block">View Orders &rarr;</span>
                                    </div>
                                </div>
                            </a>
                            <?php endif; ?>

                            <?php if(!$show_review_notif && $notif_count == 0): ?>
                                <div class="p-8 text-center flex flex-col items-center justify-center">
                                    <i class="far fa-bell-slash text-4xl text-gray-200 mb-3"></i>
                                    <p class="text-xs text-gray-400 font-bold">No notifications yet.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <a href="<?= $base_url ?>/chat.php" class="relative cursor-pointer text-gray-300 hover:text-white transition-colors p-2">
                    <i class="far fa-comments text-lg md:text-xl"></i>
                    <?php if($chat_count > 0): ?><span class="absolute top-1 right-0 bg-orange text-white text-[8px] font-black w-4 h-4 rounded-full flex items-center justify-center border-[1.5px] border-navy shadow-sm animate-pulse"><?= $chat_count ?></span><?php endif; ?>
                </a>
                
                <a href="<?= $base_url ?>/pembeli/keranjang.php" class="relative cursor-pointer text-white hover:text-orange transition-colors p-2">
                    <i class="fas fa-shopping-cart text-[1.4rem] md:text-xl"></i>
                    <?php if($cart_count > 0): ?><span class="absolute top-0 md:top-1 right-0 md:right-1 bg-orange text-white text-[8px] font-black w-4 h-4 rounded-full flex items-center justify-center border-2 border-navy shadow-sm"><?= $cart_count ?></span><?php endif; ?>
                </a>
                
                <div class="mega-container cursor-pointer ml-1 md:ml-2">
                    <div class="w-8 h-8 md:w-10 md:h-10 bg-gradient-to-tr from-white/10 to-white/5 border border-white/20 rounded-full flex items-center justify-center hover:border-orange transition-all duration-300 overflow-hidden">
                       <img src="<?= $foto_user_url ?>" alt="Avatar" class="w-full h-full object-cover">
                    </div>
                    
                    <div class="mega-dropdown absolute right-0 top-[120%] mt-1 w-64 bg-white rounded-2xl shadow-mega border border-gray-100 z-[100] text-sm font-bold">
                        <div class="p-5 border-b border-gray-100 bg-gradient-to-br from-navy to-[#111144] text-white rounded-t-2xl">
                            <h4 class="text-white text-base font-black truncate mb-1"><?= htmlspecialchars($nama_user) ?></h4>
                            <div class="flex items-center gap-2">
                                <span class="bg-white/20 px-2 py-0.5 rounded text-[9px] uppercase tracking-widest font-black"><?= $role_user ?></span>
                                <p class="text-gray-300 text-[10px] font-medium truncate"><?= htmlspecialchars($email_user) ?></p>
                            </div>
                        </div>
                        
                        <div class="p-2 space-y-1">
                            <?php if ($role_user === 'admin'): ?>
                                <a href="<?= $base_url ?>/admin/index.php" class="block flex items-center gap-3 p-3 rounded-xl text-navy bg-blue-50 hover:bg-navy hover:text-white transition-colors font-black"><div class="w-5 text-center"><i class="fas fa-shield-alt"></i></div> Admin Control Center</a>
                            <?php elseif ($role_user === 'toko'): ?>
                                <a href="<?= $base_url ?>/toko/index.php" class="block flex items-center gap-3 p-3 rounded-xl text-orange bg-orange/10 hover:bg-orange hover:text-white transition-colors font-black"><div class="w-5 text-center"><i class="fas fa-store"></i></div> Seller Dashboard</a>
                                <a href="<?= $base_url ?>/toko/pesanan.php" class="block flex items-center gap-3 p-3 rounded-xl text-gray-600 hover:text-navy hover:bg-gray-50 transition-colors"><div class="w-5 text-center text-gray-400"><i class="fas fa-clipboard-list"></i></div> Incoming Orders</a>
                                <a href="<?= $base_url ?>/toko/profil_toko.php" class="block flex items-center gap-3 p-3 rounded-xl text-gray-600 hover:text-navy hover:bg-gray-50 transition-colors"><div class="w-5 text-center text-gray-400"><i class="fas fa-store-alt"></i></div> Store Profile</a>
                            <?php else: ?>
                                <a href="<?= $base_url ?>/pembeli/pesanan.php" class="block flex items-center gap-3 p-3 rounded-xl text-gray-600 hover:text-navy hover:bg-gray-50 transition-colors"><div class="w-5 text-center text-gray-400"><i class="fas fa-receipt"></i></div> My Orders</a>
                            <?php endif; ?>
                            
                            <a href="<?= $base_url ?>/pembeli/profil.php" class="block flex items-center gap-3 p-3 rounded-xl text-gray-600 hover:text-navy hover:bg-gray-50 transition-colors"><div class="w-5 text-center text-gray-400"><i class="far fa-user"></i></div> Account Settings</a>
                        </div>
                        
                        <div class="p-2 border-t border-gray-100 bg-gray-50 rounded-b-2xl">
                            <a href="<?= $base_url ?>/auth/logout.php" class="block flex items-center gap-3 p-3 rounded-xl text-alertred hover:bg-red/10 transition-colors font-black"><div class="w-5 text-center"><i class="fas fa-power-off"></i></div> Sign Out</a>
                        </div>
                    </div>
                </div>
                
                <?php else: ?>
                    <div class="hidden md:flex gap-3 items-center ml-2">
                        <a href="<?= $base_url ?>/auth/login.php" class="bg-white/10 hover:bg-white/20 border border-white/10 px-5 py-2 rounded-full font-bold text-xs text-white transition-all">Log In</a>
                        <a href="<?= $base_url ?>/auth/register.php" class="bg-gradient-to-r from-orange to-pink-400 hover:brightness-110 px-6 py-2 rounded-full text-white font-bold text-xs uppercase tracking-widest transition-all shadow-[0_4px_14px_rgba(232,160,191,0.3)] hover:-translate-y-0.5">Sign Up Free</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <?php if(!isset($hide_subnav)): ?>
    <div class="bg-darkest border-b border-white/5 shadow-inner relative z-30 hidden md:block">
        <div class="max-w-screen-xl mx-auto w-full">
            <div class="w-full overflow-x-auto hide-scrollbar touch-pan-x overscroll-x-contain">
                <div class="flex gap-3 px-8 py-3 w-max items-center">
                    <a href="<?= $base_url ?>/kategori.php?k=semua" class="flex items-center gap-2 whitespace-nowrap shrink-0 bg-white/5 border border-white/10 hover:bg-white/10 px-4 py-2 rounded-full text-xs font-semibold text-gray-300 hover:text-white transition-all"><i class="fas fa-th-large text-gray-400"></i> All Products</a>
                    <a href="<?= $base_url ?>/kategori.php?k=perfume" class="flex items-center gap-2 whitespace-nowrap shrink-0 bg-white/5 border border-white/10 hover:bg-white/10 px-4 py-2 rounded-full text-xs font-semibold text-gray-300 hover:text-white transition-all"><i class="fas fa-spray-can text-pink-400"></i> Perfumes</a>
                    <a href="<?= $base_url ?>/kategori.php?k=skincare" class="flex items-center gap-2 whitespace-nowrap shrink-0 bg-white/5 border border-white/10 hover:bg-white/10 px-4 py-2 rounded-full text-xs font-semibold text-gray-300 hover:text-white transition-all"><i class="fas fa-pump-soap text-orange"></i> Skincare</a>
                    <a href="<?= $base_url ?>/kategori.php?k=makeup" class="flex items-center gap-2 whitespace-nowrap shrink-0 bg-white/5 border border-white/10 hover:bg-white/10 px-4 py-2 rounded-full text-xs font-semibold text-gray-300 hover:text-white transition-all"><i class="fas fa-magic text-yellow-400"></i> Makeup</a>
                    <a href="<?= $base_url ?>/kategori.php?k=bodycare" class="flex items-center gap-2 whitespace-nowrap shrink-0 bg-white/5 border border-white/10 hover:bg-white/10 px-4 py-2 rounded-full text-xs font-semibold text-gray-300 hover:text-white transition-all"><i class="fas fa-leaf text-green-400"></i> Body Care</a>
                    <div class="w-px h-6 bg-white/20 my-auto mx-2"></div>
                    <a href="<?= $base_url ?>/kategori.php?k=bestseller" class="flex items-center gap-2 whitespace-nowrap shrink-0 bg-gradient-to-r from-orange to-[#d4af37] border border-orange hover:brightness-110 px-5 py-2 rounded-full text-xs font-black text-white transition-all duration-300 shadow-glow group">
                        <i class="fas fa-star animate-pulse text-white"></i> BEST SELLERS
                    </a>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <nav class="bg-navy/95 backdrop-blur-xl text-white py-3 sticky top-0 z-[60] shadow-lg border-b border-white/10 md:hidden flex justify-between items-center px-4">
        <div class="flex items-center gap-3">
            <button id="btnMobileMenu" class="text-white text-xl p-1 hover:text-orange transition-colors"><i class="fas fa-bars"></i></button>
            <a href="<?= $base_url ?>/index.php" class="flex items-center gap-1.5">
                <img src="<?= $base_url ?>/assets/img/logo.png" alt="Logo" class="h-8 w-auto object-contain" onerror="this.style.display='none'; document.getElementById('fallback-icon-mob').classList.remove('hidden');">
                <div id="fallback-icon-mob" class="hidden bg-gradient-to-br from-orange to-red-500 p-1.5 rounded-lg text-white text-xs shadow-lg"><i class="fas fa-spa"></i></div>
                <div class="flex items-center font-serif">
                    <span class="text-lg font-black tracking-wide text-orange">Beauty</span>
                    <span class="text-lg font-black tracking-wide text-white ml-0.5">Scent</span>
                </div>
            </a>
        </div>
        <div class="flex items-center gap-3">
            
            <button id="btnMobNotif" class="relative text-white hover:text-orange transition-colors focus:outline-none p-1">
                <i class="far fa-bell text-lg"></i>
                <?php if($total_all_notif > 0): ?><span class="absolute -top-1 -right-1 bg-alertred text-white text-[8px] font-black w-3.5 h-3.5 rounded-full flex items-center justify-center border border-navy animate-pulse"><?= $total_all_notif ?></span><?php endif; ?>
            </button>

            <a href="<?= $base_url ?>/chat.php" class="relative text-white hover:text-orange transition-colors p-1">
                <i class="far fa-comments text-lg"></i>
                <?php if($chat_count > 0): ?><span class="absolute -top-1 -right-1 bg-orange text-white text-[8px] font-black w-3.5 h-3.5 rounded-full flex items-center justify-center border border-navy animate-pulse"><?= $chat_count ?></span><?php endif; ?>
            </a>
            
            <a href="<?= $base_url ?>/pembeli/keranjang.php" class="relative text-white hover:text-orange transition-colors p-1">
                <i class="fas fa-shopping-cart text-lg"></i>
                <?php if($cart_count > 0): ?><span class="absolute -top-1 -right-1 bg-orange text-white text-[8px] font-black w-3.5 h-3.5 rounded-full flex items-center justify-center border border-navy"><?= $cart_count ?></span><?php endif; ?>
            </a>
        </div>
    </nav>

    <?php if($is_logged_in): ?>
    <div id="mobileNotifDropdown" class="absolute top-[60px] right-2 left-2 bg-white rounded-2xl shadow-2xl border border-gray-100 z-[100] hidden flex-col overflow-hidden transform transition-all">
        <div class="p-4 border-b border-gray-100 bg-gradient-to-br from-navy to-[#111144] flex justify-between items-center">
            <h4 class="font-black text-white text-sm">Notifications</h4>
            <?php if($total_all_notif > 0): ?>
                <span class="text-[9px] bg-alertred text-white px-2 py-0.5 rounded-full font-black"><?= $total_all_notif ?> New</span>
            <?php endif; ?>
        </div>
        <div class="max-h-[60vh] overflow-y-auto bg-white">
            <?php if($show_review_notif): ?>
            <a href="<?= $base_url ?>/pembeli/ulasan_platform.php" class="block p-4 border-b border-gray-50 hover:bg-orange/5 transition-colors">
                <div class="flex gap-3">
                    <div class="w-10 h-10 rounded-full bg-orange/10 text-orange border border-orange/20 flex items-center justify-center shrink-0">
                        <i class="fas fa-heart animate-pulse text-lg"></i>
                    </div>
                    <div>
                        <h5 class="text-[11px] font-black text-navy mb-0.5">Order Complete! 🎉</h5>
                        <p class="text-[10px] text-gray-500 font-medium leading-snug mb-1.5">Share your experience with BeautyScent and help other beauty lovers.</p>
                        <span class="text-[9px] bg-orange text-white px-2 py-1 rounded border border-orange font-bold mt-1 inline-block">Leave a Review &rarr;</span>
                    </div>
                </div>
            </a>
            <?php endif; ?>

            <?php if($notif_count > 0): ?>
            <a href="<?= $base_url ?>/pembeli/pesanan.php" class="block p-4 border-b border-gray-50 hover:bg-blue-50 transition-colors">
                <div class="flex gap-3">
                    <div class="w-10 h-10 rounded-full bg-blue-100 text-blue-500 flex items-center justify-center shrink-0">
                        <i class="fas fa-box-open"></i>
                    </div>
                    <div>
                        <h5 class="text-[11px] font-black text-navy mb-0.5">Orders In Progress</h5>
                        <p class="text-[10px] text-gray-500 font-medium leading-snug">You have <b><?= $notif_count ?> order(s)</b> currently active or being processed.</p>
                        <span class="text-[9px] text-blue-500 font-bold mt-1 inline-block">View Orders &rarr;</span>
                    </div>
                </div>
            </a>
            <?php endif; ?>

            <?php if(!$show_review_notif && $notif_count == 0): ?>
                <div class="p-8 text-center flex flex-col items-center justify-center">
                    <i class="far fa-bell-slash text-4xl text-gray-200 mb-3"></i>
                    <p class="text-xs text-gray-400 font-bold">No notifications yet.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <div class="md:hidden fixed bottom-0 left-0 right-0 bg-white border-t border-gray-100 z-[90] flex justify-between items-center px-1 pt-2 pb-[calc(env(safe-area-inset-bottom)+8px)] shadow-[0_-4px_10px_rgba(0,0,0,0.05)]">
        
        <a href="<?= $base_url ?>/index.php" class="block w-1/4 text-center <?= $nav_active == 'beranda' ? 'text-orange' : 'text-gray-400' ?> hover:text-orange transition-colors">
            <i class="fas fa-home text-[1.3rem] mb-1 block"></i>
            <span class="text-[9px] font-bold tracking-tight block">Home</span>
        </a>
        
        <a href="<?= $base_url ?>/kategori.php?k=semua" class="block w-1/4 text-center <?= $nav_active == 'eksplor' ? 'text-orange' : 'text-gray-400' ?> hover:text-orange transition-colors">
            <i class="fas fa-compass text-[1.3rem] mb-1 block"></i>
            <span class="text-[9px] font-bold tracking-tight block">Explore</span>
        </a>
        
        <?php 
            $pesanan_link = $base_url . '/pembeli/pesanan.php';
            $pesanan_text = 'Orders';
            $pesanan_icon = 'fa-clipboard-list';
            if ($role_user === 'admin') {
                $pesanan_link = $base_url . '/admin/laporan.php';
                $pesanan_text = 'Reports';
                $pesanan_icon = 'fa-chart-bar';
            } elseif ($role_user === 'toko') {
                $pesanan_link = $base_url . '/toko/pesanan.php';
            }
        ?>
        <a href="<?= $pesanan_link ?>" class="block w-1/4 text-center <?= $nav_active == 'pesanan' ? 'text-orange' : 'text-gray-400' ?> hover:text-orange transition-colors relative">
            <i class="fas <?= $pesanan_icon ?> text-[1.3rem] mb-1 block"></i>
            <span class="text-[9px] font-bold tracking-tight block"><?= $pesanan_text ?></span>
            <?php if($total_all_notif > 0 && $role_user !== 'admin'): ?><span class="absolute top-0 right-[20%] bg-alertred text-white text-[8px] font-black w-3.5 h-3.5 rounded-full flex items-center justify-center border-[1.5px] border-white shadow-sm animate-pulse"><?= $total_all_notif ?></span><?php endif; ?>
        </a>
        
        <?php if($is_logged_in): ?>
            <?php 
                $saya_link = $base_url.'/pembeli/profil.php'; // default pembeli
                if($role_user === 'admin') $saya_link = $base_url.'/admin/index.php';
                elseif($role_user === 'toko') $saya_link = $base_url.'/toko/index.php';
            ?>
            <a href="<?= $saya_link ?>" class="block w-1/4 text-center <?= $nav_active == 'saya' ? 'text-orange' : 'text-gray-400' ?> hover:text-orange transition-colors">
                <i class="far fa-user text-[1.3rem] mb-1 block"></i>
                <span class="text-[9px] font-bold tracking-tight block">Me</span>
            </a>
        <?php else: ?>
            <a href="<?= $base_url ?>/auth/login.php" class="block w-1/4 text-center <?= $nav_active == 'saya' ? 'text-orange' : 'text-gray-400' ?> hover:text-orange transition-colors">
                <i class="far fa-user text-[1.3rem] mb-1 block"></i>
                <span class="text-[9px] font-bold tracking-tight block">Log In</span>
            </a>
        <?php endif; ?>
    </div>

    <div id="mobileDrawerOverlay" class="fixed inset-0 bg-navy/80 mobile-overlay z-[105] opacity-0 pointer-events-none transition-opacity duration-300"></div>
    
    <div id="mobileDrawer" class="fixed top-0 left-0 h-full w-[80%] max-w-sm bg-gray-50 z-[110] shadow-2xl mobile-drawer -translate-x-full overflow-y-auto flex flex-col pb-safe transition-transform duration-300">
        <div class="p-5 border-b border-gray-100 flex justify-between items-center bg-white sticky top-0 z-10">
            <a href="<?= $base_url ?>/index.php" class="flex items-center gap-2">
                <img src="<?= $base_url ?>/assets/img/logo.png" alt="Logo" class="h-8 w-auto object-contain" onerror="this.style.display='none'; document.getElementById('fallback-icon-mob2').classList.remove('hidden');">
                <div id="fallback-icon-mob2" class="hidden bg-orange p-1.5 rounded-lg text-white text-xs"><i class="fas fa-spa"></i></div>
                <div class="flex items-center font-serif">
                    <span class="text-xl font-black tracking-wide text-orange">Beauty</span>
                    <span class="text-xl font-black tracking-wide text-navy ml-1">Scent</span>
                </div>
            </a>
            <button id="btnCloseMenu" class="w-8 h-8 rounded-full bg-gray-100 text-gray-500 flex items-center justify-center hover:bg-red hover:text-white transition-colors">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <?php if($is_logged_in): ?>
        <div class="p-5 bg-gradient-to-br from-navy to-[#111144] flex items-center gap-4">
            <div class="w-12 h-12 rounded-full overflow-hidden border-2 border-white shadow-sm shrink-0">
                <img src="<?= $foto_user_url ?>" alt="Avatar" class="w-full h-full object-cover">
            </div>
            <div class="text-white overflow-hidden">
                <h4 class="font-black text-sm truncate"><?= htmlspecialchars($nama_user) ?></h4>
                <p class="text-[10px] text-gray-300 font-medium truncate mb-1"><?= htmlspecialchars($email_user) ?></p>
                <span class="bg-white/20 px-2 py-0.5 rounded text-[8px] uppercase tracking-widest font-black inline-block"><?= $role_user ?></span>
            </div>
        </div>

        <div class="p-5 border-b border-gray-100 bg-white space-y-2">
            <h4 class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-3">My Account</h4>
            
            <?php if ($role_user === 'admin'): ?>
                <a href="<?= $base_url ?>/admin/index.php" class="flex items-center gap-3 p-3 rounded-xl text-navy bg-blue-50 font-black"><i class="fas fa-shield-alt w-5 text-center text-blue-500"></i> Admin Control Center</a>
            <?php elseif ($role_user === 'toko'): ?>
                <a href="<?= $base_url ?>/toko/index.php" class="flex items-center gap-3 p-3 rounded-xl text-orange bg-orange/10 font-black"><i class="fas fa-store w-5 text-center"></i> Seller Dashboard</a>
                <a href="<?= $base_url ?>/toko/pesanan.php" class="flex items-center gap-3 p-3 rounded-xl text-gray-600 hover:bg-gray-50 font-bold"><i class="fas fa-clipboard-list w-5 text-center text-gray-400"></i> Incoming Orders</a>
            <?php else: ?>
                <a href="<?= $base_url ?>/pembeli/pesanan.php" class="flex items-center gap-3 p-3 rounded-xl text-gray-600 hover:bg-gray-50 font-bold"><i class="fas fa-receipt w-5 text-center text-gray-400"></i> My Orders</a>
            <?php endif; ?>
            
            <a href="<?= $base_url ?>/pembeli/profil.php" class="flex items-center gap-3 p-3 rounded-xl text-gray-600 hover:bg-gray-50 font-bold"><i class="far fa-user w-5 text-center text-gray-400"></i> Account Settings</a>
        </div>
        <?php endif; ?>

        <div class="p-5 border-b border-gray-100 bg-white">
            <form action="<?= $base_url ?>/kategori.php" method="GET" class="relative">
                <input type="text" name="q" placeholder="Search beauty products..." class="w-full bg-gray-50 border border-gray-200 py-3 px-4 rounded-xl text-sm font-semibold focus:outline-none focus:border-orange focus:ring-2 focus:ring-orange/20">
                <button type="submit" class="absolute right-2 top-2 bottom-2 bg-navy text-white px-4 rounded-lg text-xs font-bold"><i class="fas fa-search"></i></button>
            </form>
        </div>
        
        <div class="p-5 flex-1 bg-white">
            <h4 class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-4">Beauty Categories</h4>
            <div class="space-y-2">
                <a href="<?= $base_url ?>/kategori.php?k=bestseller" class="block flex items-center gap-3 p-3 rounded-xl bg-orange text-white shadow-lg shadow-orange/30 font-black text-sm"><i class="fas fa-star w-5 text-center animate-pulse"></i> Best Sellers</a>
                <a href="<?= $base_url ?>/kategori.php?k=semua" class="block flex items-center gap-3 p-3 rounded-xl bg-gray-50 text-navy font-bold text-sm transition-colors"><i class="fas fa-th-large text-gray-400 w-5 text-center"></i> All Products</a>
                <a href="<?= $base_url ?>/kategori.php?k=perfume" class="block flex items-center gap-3 p-3 rounded-xl hover:bg-gray-50 text-gray-600 font-bold text-sm transition-colors"><i class="fas fa-spray-can text-pink-400 w-5 text-center"></i> Perfumes</a>
                <a href="<?= $base_url ?>/kategori.php?k=skincare" class="block flex items-center gap-3 p-3 rounded-xl hover:bg-gray-50 text-gray-600 font-bold text-sm transition-colors"><i class="fas fa-pump-soap text-orange w-5 text-center"></i> Skincare & Body</a>
            </div>
        </div>
        
        <?php if(!$is_logged_in): ?>
        <div class="p-5 pb-8 border-t border-gray-100 bg-white space-y-3 mt-auto">
            <a href="<?= $base_url ?>/auth/login.php" class="block w-full text-center border-2 border-navy text-navy py-3 rounded-xl font-bold text-sm">Log In</a>
            <a href="<?= $base_url ?>/auth/register.php" class="block w-full text-center bg-orange text-white py-3 rounded-xl font-bold text-sm shadow-lg shadow-orange/30">Create Account</a>
        </div>
        <?php else: ?>
        <div class="p-5 pb-8 border-t border-gray-100 bg-white mt-auto">
            <a href="<?= $base_url ?>/auth/logout.php" class="block flex items-center justify-center gap-2 w-full text-center border-2 border-red/20 bg-red/5 text-red hover:bg-red hover:text-white py-3 rounded-xl font-bold text-sm transition-colors">
                <i class="fas fa-sign-out-alt"></i> Sign Out
            </a>
        </div>
        <?php endif; ?>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // DRAWER LOGIC
            const btnOpen = document.getElementById('btnMobileMenu'), btnClose = document.getElementById('btnCloseMenu');
            const drawer = document.getElementById('mobileDrawer'), overlay = document.getElementById('mobileDrawerOverlay');
            
            function openDrawer() { 
                drawer.classList.remove('-translate-x-full'); 
                overlay.classList.remove('opacity-0', 'pointer-events-none'); 
                document.body.style.overflow = 'hidden'; 
            }
            function closeDrawer() { 
                drawer.classList.add('-translate-x-full'); 
                overlay.classList.add('opacity-0', 'pointer-events-none'); 
                document.body.style.overflow = ''; 
            }
            
            if(btnOpen) btnOpen.addEventListener('click', openDrawer);
            if(btnClose) btnClose.addEventListener('click', closeDrawer);
            if(overlay) overlay.addEventListener('click', closeDrawer);

            // MOBILE NOTIF DROPDOWN LOGIC
            const btnMobNotif = document.getElementById('btnMobNotif');
            const mobNotifDrop = document.getElementById('mobileNotifDropdown');
            
            if(btnMobNotif && mobNotifDrop) {
                btnMobNotif.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    if(mobNotifDrop.classList.contains('hidden')) {
                        mobNotifDrop.classList.remove('hidden');
                        mobNotifDrop.classList.add('flex');
                    } else {
                        mobNotifDrop.classList.add('hidden');
                        mobNotifDrop.classList.remove('flex');
                    }
                });

                // Tutup notif kalau user klik di tempat lain
                document.addEventListener('click', function(e) {
                    if(!btnMobNotif.contains(e.target) && !mobNotifDrop.contains(e.target)) {
                        mobNotifDrop.classList.add('hidden');
                        mobNotifDrop.classList.remove('flex');
                    }
                });
            }
        });
    </script>
    <main class="flex-grow">