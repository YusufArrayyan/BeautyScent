<?php 
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 1. KONEKSI DATABASE
require_once 'config/database.php';

// ... (sisa kodemu di bawahnya biarkan saja) ...

$total_toko = 0; $total_pesanan = 0;
$query_toko_terbaik = false; $query_kategori_populer = false; $query_ulasan = false;
$db_error = false;

try {
    if (!$conn) throw new Exception("Koneksi database gagal.");

    $q_toko = mysqli_query($conn, "SELECT COUNT(id) as total FROM toko WHERE status_verifikasi = 'verified'");
    if($q_toko) $total_toko = mysqli_fetch_assoc($q_toko)['total'];

    $q_pesanan = mysqli_query($conn, "SELECT COUNT(id) as total FROM pesanan WHERE status = 'selesai'");
    if($q_pesanan) $total_pesanan = mysqli_fetch_assoc($q_pesanan)['total'];

    $query_kategori_populer = mysqli_query($conn, "SELECT * FROM kategori ORDER BY RAND() LIMIT 4");

    $table_ulasan_exists = false;
    $check_table = mysqli_query($conn, "SHOW TABLES LIKE 'ulasan'");
    if($check_table && mysqli_num_rows($check_table) > 0) {
        $table_ulasan_exists = true;
        $query_ulasan = mysqli_query($conn, "
            SELECT ul.*, u.nama_lengkap, t.nama_toko 
            FROM ulasan ul
            JOIN users u ON ul.user_id = u.id
            JOIN toko t ON ul.toko_id = t.id
            ORDER BY ul.id DESC LIMIT 10
        ");
    }

    // Query Utama: AMBIL SEMUA KEMUNGKINAN KOLOM FOTO
    $sql_toko = "SELECT t.*, u.nama_lengkap, u.foto_profil as foto_user, k.nama_kategori as nama_kat_jasa";
    
    if ($table_ulasan_exists) {
        $sql_toko .= ", (SELECT COUNT(id) FROM ulasan WHERE toko_id = t.id) as jml_ulasan,
                        (SELECT IFNULL(AVG(rating), 5.0) FROM ulasan WHERE toko_id = t.id) as avg_rating";
    } else {
        $sql_toko .= ", 0 as jml_ulasan, 5.0 as avg_rating";
    }
    
    // LOGIKA SPONSOR: ORDER BY is_iklan DESC ditambahkan di sini!
    $sql_toko .= " FROM toko t 
                   JOIN users u ON t.user_id = u.id 
                   LEFT JOIN kategori k ON t.kategori_jasa = k.id 
                   WHERE t.status_verifikasi = 'verified' 
                   AND EXISTS (SELECT 1 FROM layanan l WHERE l.toko_id = t.id)
                   ORDER BY t.is_iklan DESC, t.id DESC LIMIT 4";
                   
    $query_toko_terbaik = mysqli_query($conn, $sql_toko);

} catch (Exception $e) {
    error_log("Index Error: " . $e->getMessage());
    $db_error = true;
}

$show_stats = ($total_toko >= 10 && $total_pesanan >= 10);
require_once 'includes/header.php'; 
?>

<link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.css" />

<style>
    .bg-gradient-radial { background: radial-gradient(circle, var(--tw-gradient-stops)); }
    .glass-card { background: rgba(255, 255, 255, 0.85); backdrop-filter: blur(20px); border: 1px solid rgba(255,255,255,0.8); }
    @keyframes float-slow { 0%, 100% { transform: translateY(0) rotate(0deg); } 50% { transform: translateY(-15px) rotate(2deg); } }
    .animate-float { animation: float-slow 6s ease-in-out infinite; }
    .typed-cursor { opacity: 1; animation: blink 0.7s infinite; color: #e8a0bf; }
    @keyframes blink { 0%, 100% { opacity: 1; } 50% { opacity: 0; } }
    noscript > style, .aos-animate { opacity: 1 !important; }
    .radius-std { border-radius: 2rem; }
    
    .title-wrapper { min-height: 80px; display: flex; flex-direction: column; justify-content: center; }
    @media (min-width: 640px) { .title-wrapper { min-height: 100px; } }
    @media (min-width: 768px) { .title-wrapper { min-height: 130px; } }

    .progress-line-container { position: absolute; top: 2.5rem; left: 16%; right: 16%; height: 4px; background-color: rgba(255,255,255,0.3); z-index: 0; overflow: hidden; border-radius: 2px; }
    .progress-line-fill { position: absolute; top: 0; left: 0; height: 100%; width: 0%; background: linear-gradient(90deg, #e8a0bf, #d4af37); transition: width 1.5s cubic-bezier(0.4, 0, 0.2, 1); }
    .progress-line-fill.fill-active { width: 100%; }
    .step-circle { transition: all 0.5s ease; border-color: rgba(255,255,255,0.4); background-color: rgba(255,255,255,0.1); color: #4a3b3c; }
    .step-circle.active-step { background-color: #e8a0bf; border-color: #e8a0bf; color: #fff; box-shadow: 0 0 20px rgba(232, 160, 191, 0.5); transform: scale(1.05); }
    .step-circle.done-step { background-color: #d4af37; border-color: #d4af37; color: #fff; box-shadow: 0 0 20px rgba(212, 175, 55, 0.4); }

    .swap-item-1 { animation: fadeA 8s infinite cubic-bezier(0.4, 0, 0.2, 1); }
    .swap-item-2 { animation: fadeB 8s infinite cubic-bezier(0.4, 0, 0.2, 1); opacity: 0; }
    @keyframes fadeA { 0%, 40% { opacity: 1; transform: translateY(0); pointer-events: auto; } 45%, 50% { opacity: 0; transform: translateY(-10px); pointer-events: none; } 51%, 95% { opacity: 0; transform: translateY(10px); pointer-events: none; } 100% { opacity: 1; transform: translateY(0); pointer-events: auto; } }
    @keyframes fadeB { 0%, 40% { opacity: 0; transform: translateY(10px); pointer-events: none; } 45%, 50% { opacity: 0; transform: translateY(-10px); pointer-events: none; } 51%, 95% { opacity: 1; transform: translateY(0); pointer-events: auto; } 100% { opacity: 0; transform: translateY(10px); pointer-events: none; } }

    /* CSS BANNER SLIDER */
    @keyframes autoSlide {
        0%, 25% { opacity: 1; transform: scale(1.05); }
        33%, 100% { opacity: 0; transform: scale(1); }
    }
    .animate-bg-slider {
        opacity: 0;
        animation-name: autoSlide;
        animation-timing-function: ease-in-out;
        animation-iteration-count: infinite;
    }
    
    .floating-sparkle {
        position: absolute;
        animation: float-slow 4s ease-in-out infinite alternate;
        opacity: 0.7;
    }
</style>

<div class="bg-[#fcfdfd] font-sans text-gray-800 selection:bg-orange selection:text-white overflow-hidden">

    <?php if($db_error): ?>
    <div class="bg-red text-white text-center py-2 text-[10px] md:text-xs font-bold uppercase tracking-widest z-50 relative shadow-md">
        <i class="fas fa-exclamation-triangle mr-2"></i> Database Connection Error. Please contact the administrator.
    </div>
    <?php endif; ?>

    <section class="relative pt-6 md:pt-20 pb-8 md:pb-32 overflow-hidden bg-white border-b border-gray-100">
        <div class="absolute top-0 right-0 w-[400px] h-[400px] md:w-[600px] md:h-[600px] bg-gradient-radial from-orange/20 to-transparent rounded-full blur-3xl opacity-80 translate-x-1/3 -translate-y-1/4 pointer-events-none"></div>
        <div class="absolute bottom-0 left-0 w-[300px] h-[300px] bg-gradient-radial from-red/20 to-transparent rounded-full blur-3xl opacity-50 -translate-x-1/3 translate-y-1/4 pointer-events-none"></div>

        <div class="max-w-screen-xl mx-auto px-4 md:px-8 grid grid-cols-1 lg:grid-cols-2 gap-6 md:gap-16 items-center relative z-10">
            
            <div class="text-center lg:text-left" data-aos="fade-right" data-aos-duration="1000">
                <div class="inline-flex items-center gap-1.5 md:gap-2 bg-orange/10 px-3 py-1 md:px-4 md:py-2 rounded-full text-orange font-bold text-[9px] md:text-xs uppercase tracking-widest mb-3 md:mb-6 border border-orange/20 shadow-[0_0_15px_rgba(232,160,191,0.2)]">
                    <i class="fas fa-crown text-[#d4af37]"></i> Premium Beauty Destination
                </div>

                <div class="title-wrapper w-full">
                    <h1 class="text-[2.2rem] leading-[1.2] sm:text-5xl md:text-6xl lg:text-7xl font-bold text-navy tracking-tight mb-1 md:mb-4 whitespace-nowrap font-serif">
                        Reveal Your <br class="hidden sm:block">
                        <span id="typewriter-text" class="text-transparent bg-clip-text bg-gradient-to-r from-orange to-red inline-block min-w-[120px] md:min-w-[200px] text-left italic"></span><span class="typed-cursor">|</span>
                    </h1>
                </div>
                
                <p class="text-sm sm:text-base md:text-lg text-gray-600 font-medium leading-relaxed mb-8 max-w-[90%] md:max-w-md mx-auto lg:mx-0">
                    A curated collection of the finest fragrances, luxurious skincare, and glamorous cosmetics. Embrace your inner elegance.
                </p>
                
                <div class="relative w-full max-w-xl mx-auto lg:mx-0 z-40 mb-6 md:mb-0">
                    <form action="<?= $base_url ?>/kategori.php" method="GET" class="flex items-center shadow-lg rounded-full bg-white border border-orange/20 p-1 focus-within:border-orange focus-within:ring-4 focus-within:ring-orange/20 transition-all duration-300">
                        <i class="fas fa-search text-orange ml-4 text-sm md:text-lg"></i>
                        <input type="text" id="search-input" name="q" autocomplete="off" placeholder="Search for perfumes, serums..." class="w-full bg-transparent py-3 md:py-4 px-3 outline-none text-[13px] md:text-base font-semibold text-navy placeholder-gray-400">
                        <button type="submit" class="bg-gradient-to-r from-orange to-red hover:brightness-110 text-white px-6 md:px-8 py-2.5 md:py-4 rounded-full font-black text-[10px] md:text-xs uppercase tracking-widest transition-all shadow-md shrink-0">
                            DISCOVER
                        </button>
                    </form>
                    
                    <div id="search-suggest" class="absolute top-full left-0 right-0 mt-2 bg-white rounded-2xl shadow-2xl border border-gray-100 overflow-hidden z-50 hidden opacity-0 transition-all duration-300 transform translate-y-2">
                        <div class="p-3 text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-50 bg-gray-50/50">Trending Searches</div>
                        <ul class="py-2" id="suggest-list">
                            <li><a href="<?= $base_url ?>/kategori.php?q=Chanel" class="flex items-center gap-3 px-5 py-3 hover:bg-orange/5 text-xs md:text-sm font-bold text-navy transition-colors"><i class="fas fa-spray-can text-pink-300"></i> Chanel No.5 Perfume</a></li>
                            <li><a href="<?= $base_url ?>/kategori.php?q=Dior" class="flex items-center gap-3 px-5 py-3 hover:bg-orange/5 text-xs md:text-sm font-bold text-navy transition-colors"><i class="fas fa-magic text-pink-300"></i> Dior Addict Lip Glow</a></li>
                            <li><a href="<?= $base_url ?>/kategori.php?q=SK-II" class="flex items-center gap-3 px-5 py-3 hover:bg-orange/5 text-xs md:text-sm font-bold text-navy transition-colors"><i class="fas fa-pump-soap text-pink-300"></i> SK-II Facial Treatment Essence</a></li>
                        </ul>
                        <div id="suggest-empty" class="hidden p-5 text-center text-xs md:text-sm font-bold text-gray-400">Press 'Enter' to search.</div>
                    </div>
                </div>

                <div class="grid grid-cols-4 gap-2 mt-2 mb-4 lg:hidden">
                    <a href="<?= $base_url ?>/kategori.php?k=skincare" class="flex flex-col items-center gap-1.5 p-2 rounded-xl hover:bg-pink-50 transition-colors">
                        <div class="w-10 h-10 rounded-full bg-pink-100 flex items-center justify-center text-pink-500 text-lg shadow-sm"><i class="fas fa-pump-soap"></i></div>
                        <span class="text-[9px] font-bold text-navy text-center leading-tight">Skincare</span>
                    </a>
                    <a href="<?= $base_url ?>/kategori.php?k=perfume" class="flex flex-col items-center gap-1.5 p-2 rounded-xl hover:bg-orange/5 transition-colors">
                        <div class="w-10 h-10 rounded-full bg-orange/10 flex items-center justify-center text-orange text-lg shadow-sm"><i class="fas fa-spray-can"></i></div>
                        <span class="text-[9px] font-bold text-navy text-center leading-tight">Perfume</span>
                    </a>
                    <a href="<?= $base_url ?>/kategori.php?k=makeup" class="flex flex-col items-center gap-1.5 p-2 rounded-xl hover:bg-yellow-50 transition-colors">
                        <div class="w-10 h-10 rounded-full bg-yellow-100 flex items-center justify-center text-yellow-600 text-lg shadow-sm"><i class="fas fa-magic"></i></div>
                        <span class="text-[9px] font-bold text-navy text-center leading-tight">Makeup</span>
                    </a>
                    <a href="<?= $base_url ?>/kategori.php?k=bodycare" class="flex flex-col items-center gap-1.5 p-2 rounded-xl hover:bg-green-50 transition-colors">
                        <div class="w-10 h-10 rounded-full bg-green-100 flex items-center justify-center text-green-500 text-lg shadow-sm"><i class="fas fa-leaf"></i></div>
                        <span class="text-[9px] font-bold text-navy text-center leading-tight">Body Care</span>
                    </a>
                    <a href="<?= $base_url ?>/kategori.php?k=haircare" class="flex flex-col items-center gap-1.5 p-2 rounded-xl hover:bg-purple-50 transition-colors">
                        <div class="w-10 h-10 rounded-full bg-purple-100 flex items-center justify-center text-purple-500 text-lg shadow-sm"><i class="fas fa-cut"></i></div>
                        <span class="text-[9px] font-bold text-navy text-center leading-tight">Hair Care</span>
                    </a>
                    <a href="<?= $base_url ?>/kategori.php?k=nails" class="flex flex-col items-center gap-1.5 p-2 rounded-xl hover:bg-red-50 transition-colors">
                        <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center text-red-500 text-lg shadow-sm"><i class="fas fa-hand-sparkles"></i></div>
                        <span class="text-[9px] font-bold text-navy text-center leading-tight">Nails</span>
                    </a>
                    <a href="<?= $base_url ?>/kategori.php?k=bestseller" class="flex flex-col items-center gap-1.5 p-2 rounded-xl hover:bg-gray-100 transition-colors">
                        <div class="w-10 h-10 rounded-full bg-gradient-to-tr from-yellow-300 to-yellow-500 flex items-center justify-center text-white text-lg shadow-sm"><i class="fas fa-star"></i></div>
                        <span class="text-[9px] font-bold text-navy text-center leading-tight">Best<br>Sellers</span>
                    </a>
                    <a href="<?= $base_url ?>/kategori.php?k=semua" class="flex flex-col items-center gap-1.5 p-2 rounded-xl hover:bg-gray-50 transition-colors">
                        <div class="w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center text-navy text-lg shadow-sm border border-gray-200"><i class="fas fa-th-large"></i></div>
                        <span class="text-[9px] font-bold text-navy text-center leading-tight">See<br>All</span>
                    </a>
                </div>

                <div class="hidden md:flex flex-wrap justify-start items-center gap-8 pt-8 mt-8 border-t border-gray-100">
                    <div class="flex items-center gap-2">
                        <div class="w-10 h-10 rounded-full bg-yellow-400/20 flex items-center justify-center text-[#d4af37] text-lg"><i class="fas fa-star"></i></div>
                        <div class="text-left">
                            <p class="font-black text-navy text-xl leading-tight">4.9/5</p>
                            <p class="text-[9px] text-gray-500 font-bold uppercase tracking-widest mt-0.5">Customer Joy</p>
                        </div>
                    </div>
                    <div class="w-px h-8 bg-gray-200"></div>
                    <div class="flex items-center gap-2">
                        <div class="w-10 h-10 rounded-full bg-pink-500/10 flex items-center justify-center text-pink-500 text-lg"><i class="fas fa-gem"></i></div>
                        <div class="text-left">
                            <p class="font-black text-navy text-xl leading-tight">100%</p>
                            <p class="text-[9px] text-gray-500 font-bold uppercase tracking-widest mt-0.5">Authentic</p>
                        </div>
                    </div>
                    <div class="w-px h-8 bg-gray-200"></div>
                    <div class="flex items-center gap-2">
                        <div class="w-10 h-10 rounded-full bg-purple-500/10 flex items-center justify-center text-purple-500 text-lg"><i class="fas fa-spa"></i></div>
                        <div class="text-left">
                            <p class="font-black text-navy text-xl leading-tight">Luxury</p>
                            <p class="text-[9px] text-gray-500 font-bold uppercase tracking-widest mt-0.5">Curated Brands</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="relative h-[400px] md:h-[450px] hidden lg:flex items-center justify-center z-10" data-aos="zoom-in-up" data-aos-duration="1500">
                <i class="fas fa-sparkles floating-sparkle text-3xl text-orange" style="top: 10%; right: 20%;"></i>
                <i class="fas fa-star floating-sparkle text-xl text-[#d4af37]" style="bottom: 10%; left: 10%; animation-delay: 1s;"></i>
                <i class="fas fa-heart floating-sparkle text-2xl text-pink-400" style="top: 40%; left: -5%; animation-delay: 2s;"></i>

                <div class="absolute w-[320px] h-[400px] bg-gradient-to-br from-lightorange to-bglight rounded-t-full border border-orange/30 shadow-[0_0_40px_rgba(232,160,191,0.3)] z-10 overflow-hidden flex items-center justify-center">
                    <img src="https://images.unsplash.com/photo-1596462502278-27bf85033e5a?auto=format&fit=crop&w=400&q=80" class="w-full h-full object-cover mix-blend-multiply opacity-90" alt="Beauty">
                </div>
                
                <div class="absolute top-10 left-0 w-64 h-24 animate-float z-20">
                    <div class="absolute top-0 left-0 glass-card p-4 radius-std flex items-center gap-3 shadow-xl swap-item-1 w-max"><div class="w-10 h-10 bg-pink-50 rounded-xl flex items-center justify-center text-pink-600 text-lg"><i class="fas fa-medal"></i></div><div><h4 class="font-black text-navy text-xs">Premium Quality</h4><p class="text-[9px] text-gray-500 font-bold">100% Authentic Brands</p></div></div>
                    <div class="absolute top-0 left-0 glass-card p-4 radius-std flex items-center gap-3 shadow-xl swap-item-2 w-max"><div class="w-10 h-10 bg-orange/10 rounded-xl flex items-center justify-center text-orange text-lg"><i class="fas fa-leaf"></i></div><div><h4 class="font-black text-navy text-xs">Cruelty Free</h4><p class="text-[9px] text-gray-500 font-bold">Ethically Sourced</p></div></div>
                </div>
                
                <div class="absolute bottom-16 right-[-2rem] w-64 h-24 animate-float z-20" style="animation-delay: 2s;">
                    <div class="absolute bottom-0 right-0 glass-card p-4 radius-std flex items-center gap-3 shadow-xl swap-item-1 w-max" style="animation-delay: 4s;"><div class="w-10 h-10 bg-purple-50 rounded-xl flex items-center justify-center text-purple-600 text-lg"><i class="fas fa-gift"></i></div><div><h4 class="font-black text-navy text-xs">Exclusive Deals</h4><p class="text-[9px] text-gray-500 font-bold">Member Only Prices</p></div></div>
                    <div class="absolute bottom-0 right-0 glass-card p-4 radius-std flex items-center gap-3 shadow-xl swap-item-2 w-max" style="animation-delay: 4s;"><div class="w-10 h-10 bg-[#d4af37]/20 rounded-xl flex items-center justify-center text-[#d4af37] text-lg"><i class="fas fa-star"></i></div><div><h4 class="font-black text-navy text-xs">Glow Guaranteed</h4><p class="text-[9px] text-gray-500 font-bold">Thousands of Reviews</p></div></div>
                </div>
            </div>
        </div>
    </section>

    <section class="py-16 md:py-20 bg-lightorange text-navy relative overflow-hidden" id="cara-kerja-section">
        <div class="max-w-screen-xl mx-auto px-5 md:px-8 relative z-10">
            <div class="text-center max-w-2xl mx-auto mb-12 md:mb-16" data-aos="fade-up">
                <h2 class="text-2xl md:text-4xl font-bold font-serif mb-3 md:mb-4 tracking-tight">How to Shine</h2>
                <p class="text-xs md:text-base text-gray-600 font-medium px-4">Just 3 elegant steps to discover your ultimate beauty routine.</p>
            </div>

            <div class="relative max-w-4xl mx-auto">
                <div class="hidden md:block progress-line-container">
                    <div class="progress-line-fill" id="progressFill"></div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-8 md:gap-8 text-center relative z-10">
                    <?php 
                    $steps = [
                        ['id' => 'step-1', 'icon' => '<i class="fas fa-search text-orange"></i>', 'title' => 'Explore Elegance', 'desc' => 'Discover our collection of premium skincare, cosmetics, and perfumes curated just for you.'],
                        ['id' => 'step-2', 'icon' => '<i class="fas fa-shopping-bag text-orange"></i>', 'title' => 'Add to Cart', 'desc' => 'Select your favorite items securely and arrange a convenient delivery straight to your door.'],
                        ['id' => 'step-3', 'icon' => '<i class="fas fa-sparkles text-orange"></i>', 'title' => 'Shine Brighter', 'desc' => 'Unbox the glamour. Enjoy your aesthetic glow-up and leave a radiant review!']
                    ];
                    foreach($steps as $idx => $step):
                    ?>
                    <div class="flex flex-col items-center step-wrapper relative" id="wrapper-<?= $step['id'] ?>">
                        <?php if($idx > 0): ?>
                            <div class="w-1 h-8 bg-gray-200 md:hidden mb-3 rounded-full transition-colors duration-500" id="line-<?= $step['id'] ?>"></div>
                        <?php endif; ?>
                        
                        <div class="w-16 h-16 md:w-20 md:h-20 bg-white border-4 border-white text-orange rounded-full flex items-center justify-center text-xl md:text-2xl font-black mb-3 md:mb-5 shadow-xl step-circle" id="<?= $step['id'] ?>">
                            <?= $step['icon'] ?>
                        </div>
                        <h4 class="text-base md:text-lg font-bold font-serif text-navy mb-2"><?= $step['title'] ?></h4>
                        <p class="text-[11px] md:text-xs text-gray-600 font-medium leading-relaxed max-w-[220px] md:max-w-[250px]"><?= $step['desc'] ?></p>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </section>

    <section class="py-16 md:py-20 bg-white">
        <div class="max-w-screen-xl mx-auto px-5 md:px-8">
            <div class="flex flex-col sm:flex-row justify-between items-center sm:items-end mb-8 md:mb-10 gap-4 text-center sm:text-left" data-aos="fade-right">
                <div>
                    <h2 class="text-2xl md:text-4xl font-bold font-serif text-navy tracking-tight mb-1 md:mb-2">Trending Brands</h2>
                    <p class="text-xs md:text-base text-gray-500 font-medium">Discover our most loved and frequently shopped beauty brands.</p>
                </div>
                <a href="<?= $base_url ?>/kategori.php?k=semua" class="flex items-center gap-2 text-xs md:text-sm font-bold text-orange hover:text-navy transition-colors bg-orange/10 px-4 py-2 rounded-full sm:bg-transparent sm:px-0 sm:py-0">
                    View All <i class="fas fa-arrow-right"></i>
                </a>
            </div>

            <?php if(!$query_toko_terbaik || mysqli_num_rows($query_toko_terbaik) == 0): ?>
                <div class="py-12 md:py-16 text-center border-2 border-dashed border-orange/30 radius-std bg-lightorange/50">
                    <i class="fas fa-spa text-4xl md:text-5xl text-orange/50 mb-4"></i>
                    <h3 class="text-lg md:text-xl font-bold font-serif text-navy mb-2">Brands Are Arriving Soon</h3>
                    <p class="text-xs md:text-sm text-gray-500 font-medium px-4">Our premium collections are being curated. Please check back shortly.</p>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 md:gap-6 lg:gap-8">
                    <?php 
                    $delay = 0; 
                    while($toko = mysqli_fetch_assoc($query_toko_terbaik)): 
                        // ==========================================
                        // LOGIKA UI KARTU & RATING
                        // ==========================================
                        $colors = [
                            ['bg' => 'bg-[#fbcfe8]', 'badge' => 'bg-pink-400'], 
                            ['bg' => 'bg-[#fed7aa]', 'badge' => 'bg-orange'], 
                            ['bg' => 'bg-[#e9d5ff]', 'badge' => 'bg-purple-400'], 
                            ['bg' => 'bg-[#fef08a]', 'badge' => 'bg-yellow-500']
                        ];
                        $rand_color = $colors[array_rand($colors)];
                        
                        $bg_color = $rand_color['bg'];
                        $badge_bg = $rand_color['badge'];
                        
                        $icon = !empty($toko['ikon_kat']) ? htmlspecialchars($toko['ikon_kat']) : 'fas fa-spray-can';
                        $nama_kat_tampil = !empty($toko['nama_kat_jasa']) ? htmlspecialchars($toko['nama_kat_jasa']) : htmlspecialchars($toko['kategori_jasa']);
                        
                        if($toko['kategori_jasa'] === 'darurat') {
                            $bg_color = 'bg-[#fecaca]'; $badge_bg = 'bg-red-400'; $icon = 'fas fa-fire'; $nama_kat_tampil = 'Hot Items';
                        }

                        $rating_format = number_format($toko['avg_rating'], 1);
                        $jml_ulasan = $toko['jml_ulasan'];
                        $teks_ulasan = $jml_ulasan > 0 ? "($jml_ulasan reviews)" : "(New)";

                        // FOTO PROFIL
                        $img_profil = "https://ui-avatars.com/api/?name=".urlencode($toko['nama_toko'])."&background=ff6600&color=fff&size=150&bold=true";
                        $raw_foto = '';
                        
                        if(!empty($toko['foto_toko'])) $raw_foto = $toko['foto_toko'];
                        elseif(!empty($toko['foto'])) $raw_foto = $toko['foto'];
                        elseif(!empty($toko['foto_user'])) $raw_foto = $toko['foto_user'];

                        if (!empty($raw_foto) && !in_array(strtolower($raw_foto), ['default.png', 'default.jpg'])) {
                            $clean_foto = str_replace(['uploads/profil/', 'profil/', 'uploads/'], '', $raw_foto);
                            $img_profil = $base_url . '/uploads/profil/' . $clean_foto; 
                        }
                        
                        // BANNER SLIDER
                        $banners = [];
                        if (!empty($toko['banner1'])) $banners[] = $base_url . '/uploads/profil/' . str_replace(['uploads/profil/', 'profil/', 'uploads/'], '', $toko['banner1']);
                        if (!empty($toko['banner2'])) $banners[] = $base_url . '/uploads/profil/' . str_replace(['uploads/profil/', 'profil/', 'uploads/'], '', $toko['banner2']);
                        if (!empty($toko['banner3'])) $banners[] = $base_url . '/uploads/profil/' . str_replace(['uploads/profil/', 'profil/', 'uploads/'], '', $toko['banner3']);
                    ?>
                    <div class="w-full bg-white rounded-[1.5rem] border border-gray-200 shadow-[0_8px_30px_rgb(0,0,0,0.04)] hover:shadow-xl hover:-translate-y-1.5 transition-all duration-300 flex flex-col cursor-pointer overflow-hidden group" onclick="window.location.href='<?= $base_url ?>/detail_toko.php?id=<?= $toko['id'] ?>'" data-aos="fade-up" data-aos-delay="<?= $delay ?>">
                        
                        <div class="h-28 md:h-32 <?= empty($banners) ? $bg_color : 'bg-gray-200' ?> relative flex items-center justify-center overflow-hidden border-b border-gray-100">
                            <?php if(empty($banners)): ?>
                                <div class="absolute inset-0 bg-black/20 mix-blend-overlay"></div>
                                <i class="<?= $icon ?> text-5xl md:text-6xl text-white/10 group-hover:scale-125 group-hover:rotate-12 transition-transform duration-700"></i>
                            <?php elseif(count($banners) == 1): ?>
                                <img src="<?= $banners[0] ?>" class="absolute inset-0 w-full h-full object-cover group-hover:scale-110 transition-transform duration-700">
                                <div class="absolute inset-0 bg-black/30 group-hover:bg-black/40 transition-colors"></div>
                            <?php else: ?>
                                <div class="absolute inset-0 w-full h-full">
                                    <?php 
                                    $total_slide = count($banners);
                                    $anim_duration = $total_slide * 4; 
                                    foreach($banners as $idx => $bnnr): 
                                        $anim_delay = $idx * 4;
                                    ?>
                                        <img src="<?= $bnnr ?>" class="absolute inset-0 w-full h-full object-cover animate-bg-slider" style="animation-duration: <?= $anim_duration ?>s; animation-delay: <?= $anim_delay ?>s;">
                                    <?php endforeach; ?>
                                    <div class="absolute inset-0 bg-black/30 group-hover:bg-black/40 transition-colors z-10"></div>
                                </div>
                            <?php endif; ?>
                            
                            <?php if(isset($toko['is_iklan']) && $toko['is_iklan'] == 1): ?>
                                <div title="Sponsor Utama" class="absolute top-3 left-3 bg-gradient-to-r from-yellow-400 to-yellow-600 text-navy px-2.5 py-1 rounded-full text-[8px] md:text-[9px] font-black uppercase tracking-widest shadow-lg flex items-center gap-1 z-20 animate-pulse border border-yellow-300">
                                    <i class="fas fa-crown"></i> Sponsor
                                </div>
                            <?php endif; ?>

                            <div title="Terverifikasi Resmi" class="absolute top-3 right-3 bg-green-500 text-white px-2.5 py-1 rounded-full text-[8px] md:text-[9px] font-black uppercase tracking-widest shadow-md flex items-center gap-1 z-20">
                                <i class="fas fa-check-circle"></i> Verified
                            </div>
                        </div>
                        
                        <div class="p-5 md:p-6 relative flex-1 flex flex-col">
                            <div class="absolute -top-8 left-5 w-14 h-14 md:w-16 md:h-16 bg-white rounded-[1rem] md:rounded-[1.2rem] shadow-lg border-2 border-white flex items-center justify-center overflow-hidden z-20 group-hover:scale-110 transition-transform p-0.5">
                                <img src="<?= $img_profil ?>" alt="<?= htmlspecialchars($toko['nama_toko']) ?>" class="w-full h-full object-cover rounded-[14px]">
                            </div>
                            
                            <div class="mt-6 md:mt-8 mb-3">
                                <h3 class="font-black text-base md:text-lg text-navy leading-tight mb-1 truncate group-hover:text-orange transition-colors"><?= htmlspecialchars($toko['nama_toko']) ?></h3>
                                <p class="text-[10px] md:text-xs font-bold text-gray-500 flex items-center gap-1.5 truncate"><i class="fas fa-user-tie text-gray-400"></i> <?= htmlspecialchars($toko['nama_lengkap']) ?></p>
                            </div>
                            
                            <div class="flex flex-wrap gap-2 mb-5 mt-auto">
                                <span class="bg-gray-100 border border-gray-200 text-gray-600 text-[8px] md:text-[9px] font-black uppercase px-2 py-1 rounded-md tracking-wider truncate max-w-full group-hover:border-gray-300 transition-colors"><?= htmlspecialchars($nama_kat_tampil) ?></span>
                            </div>
                            
                            <div class="mt-auto pt-4 border-t border-gray-100 flex items-center justify-between">
                                <div class="flex items-center gap-1 text-xs md:text-sm font-black text-navy">
                                    <i class="fas fa-star text-yellow-400"></i> <?= $rating_format ?> 
                                    <span class="text-[9px] md:text-[10px] font-medium text-gray-400 ml-0.5"><?= $teks_ulasan ?></span>
                                </div>
                                <div class="text-orange text-[10px] md:text-xs font-black uppercase tracking-widest flex items-center gap-1 group-hover:gap-1.5 transition-all">Shop Now <i class="fas fa-arrow-right"></i></div>
                            </div>
                        </div>
                    </div>
                    <?php 
                        $delay += 100; endwhile; 
                    ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <section class="py-16 md:py-24 bg-[#f8f9fc]">
        <div class="max-w-screen-xl mx-auto px-5 md:px-8">
            <div class="text-center mb-10 md:mb-12" data-aos="fade-up">
                <h2 class="text-2xl md:text-4xl font-bold font-serif text-navy mb-2 md:mb-3 tracking-tight">Customer Diaries</h2>
                <p class="text-xs md:text-base text-gray-600 font-medium px-4">Real love letters from our BeautyScent community.</p>
            </div>

            <?php if(!$query_ulasan || mysqli_num_rows($query_ulasan) == 0): ?>
                <div class="max-w-2xl mx-auto text-center bg-white p-8 md:p-12 rounded-[1.5rem] md:rounded-[2rem] border border-orange/20 shadow-sm relative overflow-hidden" data-aos="zoom-in">
                    <i class="fas fa-heart text-5xl md:text-6xl text-pink-400/10 absolute -top-4 -left-4 -rotate-12 pointer-events-none"></i>
                    <i class="fas fa-star text-4xl md:text-5xl text-yellow-400/20 absolute bottom-4 right-8 rotate-12 pointer-events-none"></i>
                    
                    <div class="w-16 h-16 md:w-24 md:h-24 bg-gradient-to-br from-orange to-red text-white rounded-full flex items-center justify-center text-3xl md:text-4xl mx-auto mb-5 shadow-lg shadow-orange/30">
                        <i class="fas fa-quote-right"></i>
                    </div>
                    <h3 class="text-xl md:text-2xl font-bold font-serif text-navy mb-2 tracking-tight">We Love to Hear From You!</h3>
                    <p class="text-xs md:text-sm text-gray-600 font-medium mb-6 md:mb-8 leading-relaxed max-w-lg mx-auto">There are no reviews just yet. Be the first to share your glam journey and help others find their perfect glow!</p>
                    <a href="<?= $base_url ?>/kategori.php?k=semua" class="inline-block bg-gradient-to-r from-orange to-[#d4af37] hover:brightness-110 text-white px-6 md:px-8 py-3.5 md:py-4 rounded-full text-[10px] md:text-xs font-black uppercase tracking-widest transition-all shadow-lg transform hover:-translate-y-1">Start Shopping</a>
                </div>
            <?php else: ?>
                <div class="swiper testimonialSwiper pb-12 md:pb-14" data-aos="fade-up" data-aos-delay="100">
                    <div class="swiper-wrapper">
                        <?php 
                        $avatar_colors = ['bg-orange', 'bg-blue-500', 'bg-green-500', 'bg-red', 'bg-purple-500', 'bg-navy'];
                        while($review = mysqli_fetch_assoc($query_ulasan)):
                            $color = $avatar_colors[array_rand($avatar_colors)];
                            $initial = strtoupper(substr($review['nama_lengkap'], 0, 1));
                        ?>
                        <div class="swiper-slide h-auto">
                            <div class="bg-white p-6 md:p-8 rounded-[1.5rem] border border-gray-100 shadow-[0_4px_20px_rgb(0,0,0,0.03)] h-full flex flex-col">
                                <div class="text-yellow-400 text-xs md:text-sm mb-3">
                                    <?php for($i=1; $i<=5; $i++): ?>
                                        <i class="fas <?= $i <= $review['rating'] ? 'fa-star' : 'fa-star text-gray-300' ?>"></i>
                                    <?php endfor; ?>
                                </div>
                                <p class="text-xs md:text-sm text-gray-600 font-medium leading-relaxed mb-6 flex-1 italic line-clamp-4">"<?= htmlspecialchars($review['komentar']) ?>"</p>
                                <div class="flex items-center gap-3 md:gap-4 pt-4 border-t border-gray-50">
                                    <div class="w-10 h-10 md:w-12 md:h-12 <?= $color ?> rounded-full flex items-center justify-center text-white font-black text-base shrink-0 shadow-sm"><?= $initial ?></div>
                                    <div>
                                        <h5 class="font-black text-xs md:text-sm text-navy truncate max-w-[150px]"><?= htmlspecialchars($review['nama_lengkap']) ?></h5>
                                        <p class="text-[9px] md:text-[10px] font-bold text-gray-400 uppercase tracking-widest truncate max-w-[150px] mt-0.5"><i class="fas fa-store text-gray-300"></i> <?= htmlspecialchars($review['nama_toko']) ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    </div>
                    <div class="swiper-pagination"></div>
                </div>
            <?php endif; ?>
        </div>
    </section>

</div>

<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script src="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.js"></script>

<script>
    AOS.init({ once: true, duration: 600, offset: 50, easing: 'ease-out-cubic' });

    // Auto-Suggestion Script
    const searchInput = document.getElementById('search-input');
    const searchSuggest = document.getElementById('search-suggest');
    const suggestList = document.getElementById('suggest-list');
    const suggestItems = suggestList ? suggestList.querySelectorAll('li') : [];
    const suggestEmpty = document.getElementById('suggest-empty');
    
    if(searchInput && searchSuggest) {
        searchInput.addEventListener('focus', () => {
            searchSuggest.classList.remove('hidden');
            setTimeout(() => { searchSuggest.classList.remove('opacity-0', 'translate-y-2'); }, 10);
        });

        searchInput.addEventListener('input', function(e) {
            const val = e.target.value.toLowerCase().trim();
            let hasVisible = false;
            suggestItems.forEach(item => {
                if(item.textContent.toLowerCase().includes(val)) {
                    item.style.display = ''; hasVisible = true;
                } else { item.style.display = 'none'; }
            });
            if(val.length > 0 && !hasVisible) { suggestEmpty.classList.remove('hidden'); } 
            else { suggestEmpty.classList.add('hidden'); }
        });
        
        document.addEventListener('click', (e) => {
            if(!searchInput.contains(e.target) && !searchSuggest.contains(e.target)) {
                searchSuggest.classList.add('opacity-0', 'translate-y-2');
                setTimeout(() => { searchSuggest.classList.add('hidden'); }, 300);
            }
        });
    }

    // Slider Testimoni
    if(document.querySelector('.testimonialSwiper')) {
        new Swiper(".testimonialSwiper", {
            slidesPerView: 1, spaceBetween: 15, loop: false, 
            pagination: { el: ".swiper-pagination", clickable: true },
            breakpoints: { 640: { slidesPerView: 1.5, spaceBetween: 20 }, 768: { slidesPerView: 2, spaceBetween: 24 }, 1024: { slidesPerView: 3, spaceBetween: 30 } },
            autoplay: { delay: 4000, disableOnInteraction: false },
        });
    }

    // Mesin Ketik
    const words = ["Signature Scent", "Flawless Skin", "Glamourous Look", "Daily Aesthetics", "Inner Elegance"];
    let i = 0; let timer;
    function typingEffect() {
        let textElement = document.getElementById('typewriter-text');
        if(!textElement) return;
        let word = words[i].split("");
        var loopTyping = function() {
            if (word.length > 0) { textElement.innerHTML += word.shift(); } 
            else { deletingEffect(); return false; }
            timer = setTimeout(loopTyping, 150); 
        }; loopTyping();
    }
    function deletingEffect() {
        let textElement = document.getElementById('typewriter-text');
        if(!textElement) return;
        let word = words[i].split("");
        var loopDeleting = function() {
            if (word.length > 0) { word.pop(); textElement.innerHTML = word.join(""); } 
            else { if (words.length > (i + 1)) { i++; } else { i = 0; } typingEffect(); return false; }
            timer = setTimeout(loopDeleting, 60);
        };
        setTimeout(loopDeleting, 3500); 
    }
    typingEffect();

    // JAVASCRIPT UNTUK ANIMASI CARA KERJA (SCROLL PROGRESS)
    document.addEventListener("DOMContentLoaded", function() {
        const section = document.getElementById('cara-kerja-section');
        const progressFill = document.getElementById('progressFill');
        const step1 = document.getElementById('step-1');
        const step2 = document.getElementById('step-2');
        const step3 = document.getElementById('step-3');
        
        const line2 = document.getElementById('line-step-2');
        const line3 = document.getElementById('line-step-3');

        if(!section || !step1) return;

        let animated = false;

        window.addEventListener('scroll', function() {
            if (animated) return;
            
            const rect = section.getBoundingClientRect();
            const triggerPoint = window.innerHeight * 0.75; 

            if (rect.top < triggerPoint) {
                animated = true; 

                step1.classList.add('active-step');
                
                setTimeout(() => {
                    step1.classList.remove('active-step');
                    step1.classList.add('done-step');
                    if(progressFill) progressFill.style.width = '50%';
                    if(line2) { line2.classList.remove('bg-gray-700'); line2.classList.add('bg-[#4ade80]'); }
                    step2.classList.add('active-step');
                }, 800);

                setTimeout(() => {
                    step2.classList.remove('active-step');
                    step2.classList.add('done-step');
                    if(progressFill) progressFill.style.width = '100%';
                    if(line3) { line3.classList.remove('bg-gray-700'); line3.classList.add('bg-[#4ade80]'); }
                    step3.classList.add('active-step');
                }, 2000);

                setTimeout(() => {
                    step3.classList.remove('active-step');
                    step3.classList.add('done-step');
                }, 3000);
            }
        });
    });
</script>

<?php require_once 'includes/footer.php'; ?>