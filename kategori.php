<?php 
// 1. KONEKSI DATABASE & LOGIKA PENCARIAN AMAN
require_once 'config/database.php';

// Cek parameter q (query/kata kunci), k (kategori), dan sort (pengurutan)
$keyword = isset($_GET['q']) ? mysqli_real_escape_string($conn, trim($_GET['q'])) : '';
$kategori_id = isset($_GET['k']) ? $_GET['k'] : 'semua';
$sort_by = isset($_GET['sort']) ? $_GET['sort'] : 'relevan'; // FITUR BARU: Tangkap parameter Sortir

// Bersihkan kategori_id
if (!in_array($kategori_id, ['semua', 'bestseller', 'perfume', 'skincare', 'makeup', 'bodycare', 'haircare', 'nails'])) {
    $kategori_id = (int)$kategori_id;
}

// Menentukan Judul, Warna & Icon Halaman Bawaan (Default)
$title_page = "All Beauty Products & Brands";
$icon_page = '<i class="fas fa-gem text-white"></i>';
$bg_gradient = 'from-[#e8a0bf] to-[#d4af37]';
$badge_text = "All Categories";

if (!empty($keyword)) {
    $title_page = 'Search Results: "' . htmlspecialchars($keyword) . '"';
    $icon_page = '<i class="fas fa-search text-white"></i>';
    $bg_gradient = 'from-[#f472b6] to-[#e8a0bf]';
    $badge_text = "Search";
} elseif ($kategori_id === 'bestseller') {
    $title_page = "Bestselling Essentials";
    $icon_page = '<i class="fas fa-star text-white animate-pulse"></i>';
    $bg_gradient = 'from-[#d4af37] to-[#eab308]'; 
    $badge_text = "Best Sellers";
} elseif (is_numeric($kategori_id) && $kategori_id > 0) {
    $q_kat = mysqli_query($conn, "SELECT * FROM kategori WHERE id = '$kategori_id'");
    if($q_kat && mysqli_num_rows($q_kat) > 0) {
        $data_kat = mysqli_fetch_assoc($q_kat);
        $title_page = htmlspecialchars($data_kat['nama_kategori']);
        $icon_page = '<i class="'.htmlspecialchars($data_kat['ikon']).' text-white"></i>';
        $bg_gradient = 'from-[#1e3a8a] to-[#1e40af]'; 
        $badge_text = htmlspecialchars($data_kat['nama_kategori']);
    }
}

// CEK TABEL ULASAN
$table_ulasan_exists = false;
$check_table = mysqli_query($conn, "SHOW TABLES LIKE 'ulasan'");
if($check_table && mysqli_num_rows($check_table) > 0) {
    $table_ulasan_exists = true;
}

// 2. BUILD QUERY DINAMIS KE TABEL TOKO & USERS
$sql = "SELECT t.*, u.nama_lengkap, u.foto_profil as foto_user, k.nama_kategori as nama_kat_asli, k.ikon as ikon_kat";

// Tambahkan logika rating dinamis jika tabel ulasan ada
if ($table_ulasan_exists) {
    $sql .= ", (SELECT COUNT(id) FROM ulasan WHERE toko_id = t.id) as jml_ulasan,
               (SELECT IFNULL(AVG(rating), 5.0) FROM ulasan WHERE toko_id = t.id) as avg_rating";
} else {
    $sql .= ", 0 as jml_ulasan, 5.0 as avg_rating";
}

$sql .= " FROM toko t 
          JOIN users u ON t.user_id = u.id 
          LEFT JOIN kategori k ON t.kategori_jasa = k.id 
          WHERE t.status_verifikasi = 'verified' 
          AND EXISTS (SELECT 1 FROM layanan l WHERE l.toko_id = t.id)";

// Filter Kategori
if ($kategori_id === 'bestseller') {
    // maybe logic for bestseller
} elseif (in_array($kategori_id, ['perfume', 'skincare', 'makeup', 'bodycare', 'haircare', 'nails'])) {
    $sql .= " AND (t.kategori_jasa = '$kategori_id' OR k.nama_kategori LIKE '%$kategori_id%')";
} elseif (is_numeric($kategori_id) && $kategori_id > 0) {
    $sql .= " AND t.kategori_jasa = '$kategori_id'";
}

// Filter Search (Kata Kunci)
if (!empty($keyword)) {
    $sql .= " AND (t.nama_toko LIKE '%$keyword%' OR u.nama_lengkap LIKE '%$keyword%' OR k.nama_kategori LIKE '%$keyword%')";
}

// ==========================================
// FITUR BARU: LOGIKA SORTING (PENGURUTAN)
// ==========================================
if ($sort_by === 'rating' && $table_ulasan_exists) {
    $sql .= " ORDER BY avg_rating DESC, jml_ulasan DESC, t.id DESC";
} elseif ($sort_by === 'ulasan' && $table_ulasan_exists) {
    $sql .= " ORDER BY jml_ulasan DESC, avg_rating DESC, t.id DESC";
} elseif ($sort_by === 'terbaru') {
    $sql .= " ORDER BY t.id DESC";
} else {
    // Default: Paling Relevan
    $sql .= " ORDER BY t.id DESC";
}

$query_result = mysqli_query($conn, $sql);
$total_hasil = $query_result ? mysqli_num_rows($query_result) : 0;
$is_empty = ($total_hasil == 0);

require_once 'includes/header.php'; 
?>

<!-- PUSTAKA ANIMASI -->
<link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
<style>
    .sticky-filter { position: sticky; top: 70px; z-index: 35; }
    @media (min-width: 1024px) { .sticky-filter { top: 80px; } }
    @keyframes autoSlide { 0%, 25% { opacity: 1; transform: scale(1.05); } 33%, 100% { opacity: 0; transform: scale(1); } }
    .animate-bg-slider { opacity: 0; animation-name: autoSlide; animation-timing-function: ease-in-out; animation-iteration-count: infinite; }
</style>

<div class="bg-[#f4f7fa] min-h-[80vh] pb-24 font-sans text-gray-800">

    <div class="bg-gradient-to-r <?= $bg_gradient ?> relative overflow-hidden py-10 md:py-16 shadow-lg">
        <div class="absolute top-0 right-0 w-96 h-96 bg-white/10 rounded-full blur-[80px] pointer-events-none"></div>
        <div class="absolute bottom-0 left-0 w-64 h-64 bg-black/30 rounded-full blur-[60px] pointer-events-none"></div>
        <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAiIGhlaWdodD0iMjAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PGNpcmNsZSBjeD0iMiIgY3k9IjIiIHI9IjEiIGZpbGw9InJnYmEoMjU1LDI1NSwyNTUsMC4wNSkiLz48L3N2Zz4=')] opacity-50 pointer-events-none"></div>
        
        <div class="max-w-screen-xl mx-auto px-5 md:px-8 relative z-10" data-aos="fade-up">
            <nav class="flex text-[10px] md:text-xs font-bold text-white/60 mb-6 uppercase tracking-widest">
                <a href="<?= $base_url ?>/index.php" class="hover:text-white transition-colors">Home</a>
                <span class="mx-2">/</span><span class="text-white"><?= $badge_text ?></span>
            </nav>
            <div class="flex flex-col md:flex-row items-center md:items-end gap-6 md:gap-8 text-center md:text-left">
                <div class="w-20 h-20 md:w-24 md:h-24 bg-white/10 backdrop-blur-md rounded-[2rem] border border-white/20 shadow-2xl flex items-center justify-center text-4xl md:text-5xl shrink-0 rotate-3 hover:rotate-0 transition-transform duration-500">
                    <?= $icon_page ?>
                </div>
                <div>
                    <div class="inline-flex items-center gap-2 bg-white/20 backdrop-blur-sm px-3 py-1 rounded-full text-[9px] font-black text-white uppercase tracking-widest mb-3 border border-white/30 shadow-sm"><i class="fas fa-check-circle"></i> Authenticity Guaranteed</div>
                    <h1 class="text-3xl md:text-5xl font-bold font-serif tracking-tight mb-2 text-white"><?= $title_page ?></h1>
                    <p class="text-sm md:text-base font-medium text-white/80">Showing <?= $total_hasil ?> premium brands and curated beauty collections.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- ==========================================
         2. STICKY FILTER BAR & PENCARIAN (DIPERBARUI)
    =========================================== -->
    <div class="sticky-filter bg-white/80 backdrop-blur-xl border-b border-gray-200 shadow-sm py-3 transition-all z-30">
        <div class="max-w-screen-xl mx-auto px-5 md:px-8 flex flex-col md:flex-row items-center justify-between gap-4">
            <div class="text-sm font-bold text-gray-500 hidden lg:block">Discovering <span class="text-navy font-black"><?= $total_hasil ?> Highlights</span></div>
            
            <!-- FORM PENCARIAN DAN SORTING DIJADIKAN SATU -->
            <form action="kategori.php" method="GET" class="w-full md:w-auto flex flex-col sm:flex-row items-center gap-3">
                <?php if($kategori_id !== 'semua' && empty($keyword)): ?>
                    <input type="hidden" name="k" value="<?= htmlspecialchars($kategori_id) ?>">
                <?php endif; ?>
                
                <div class="relative w-full sm:w-64 lg:w-80">
                    <i class="fas fa-search absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400 text-sm"></i>
                    <input type="text" name="q" value="<?= htmlspecialchars($keyword) ?>" placeholder="Search perfumes, brands..." class="w-full pl-10 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-full focus:outline-none focus:bg-white focus:border-orange focus:ring-1 focus:ring-orange/50 text-xs font-bold text-navy transition-all placeholder-gray-400">
                </div>
                
                <div class="relative w-full sm:w-auto shrink-0 flex gap-2">
                    <div class="relative flex-1 sm:w-auto">
                        <!-- FITUR ONCHANGE OTOMATIS SUBMIT -->
                        <select name="sort" onchange="this.form.submit()" class="w-full sm:w-auto pl-4 pr-8 py-2.5 bg-white border border-gray-200 rounded-full text-xs text-navy font-bold focus:outline-none focus:border-orange focus:ring-2 focus:ring-orange/20 appearance-none cursor-pointer hover:border-orange transition-all shadow-sm">
                            <option value="relevan" <?= $sort_by == 'relevan' ? 'selected' : '' ?>>Most Relevant</option>
                            <option value="terbaru" <?= $sort_by == 'terbaru' ? 'selected' : '' ?>>Newest First</option>
                            <option value="rating" <?= $sort_by == 'rating' ? 'selected' : '' ?>>Highest Rated</option>
                            <option value="ulasan" <?= $sort_by == 'ulasan' ? 'selected' : '' ?>>Most Reviewed</option>
                        </select>
                        <i class="fas fa-chevron-down absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 text-[10px] pointer-events-none"></i>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- ==========================================
         3. AREA KONTEN (GRID TOKO)
    =========================================== -->
    <div class="max-w-screen-xl mx-auto px-5 md:px-8 mt-8">
        <?php if($is_empty): ?>
            <div class="flex flex-col items-center justify-center py-20 text-center bg-white rounded-[2rem] border border-gray-100 shadow-sm" data-aos="zoom-in">
                <div class="relative w-32 h-32 mb-6">
                    <div class="absolute inset-0 bg-orange/10 rounded-full animate-pulse"></div>
                    <div class="absolute inset-0 flex items-center justify-center transform -rotate-12 hover:rotate-0 transition-transform duration-500">
                        <i class="fas fa-search text-5xl md:text-6xl text-orange drop-shadow-lg"></i>
                        <i class="fas fa-question text-xl md:text-2xl text-navy absolute top-4 right-4 animate-bounce"></i>
                    </div>
                </div>
                <h3 class="text-2xl md:text-3xl font-bold font-serif text-navy mb-3 tracking-tight">Oops, No Glam Found</h3>
                <p class="text-sm md:text-base text-gray-500 font-medium mb-8 max-w-md">We couldn't find any matches <?= !empty($keyword) ? 'for <span class="text-navy font-black">"'.htmlspecialchars($keyword).'"</span>' : 'in this category' ?>.</p>
                <a href="kategori.php?k=semua" class="bg-gradient-to-r from-orange to-[#d4af37] text-white px-8 py-3.5 rounded-full font-black text-xs uppercase tracking-widest transition-all shadow-lg hover:shadow-orange/30 hover:-translate-y-1">Explore All Collections</a>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                <?php 
                $delay = 0; 
                while($toko = mysqli_fetch_assoc($query_result)): 
                    $colors = [['bg' => 'bg-[#1e3a8a]', 'badge' => 'bg-blue-600'], ['bg' => 'bg-[#c2410c]', 'badge' => 'bg-orange'], ['bg' => 'bg-[#15803d]', 'badge' => 'bg-green-600'], ['bg' => 'bg-[#6b21a8]', 'badge' => 'bg-purple-600']];
                    $rand_color = $colors[array_rand($colors)];
                    $bg_color = $rand_color['bg'];
                    
                    $icon = !empty($toko['ikon_kat']) ? htmlspecialchars($toko['ikon_kat']) : 'fas fa-spray-can';
                    $nama_kat_tampil = !empty($toko['nama_kat_asli']) ? htmlspecialchars($toko['nama_kat_asli']) : htmlspecialchars($toko['kategori_jasa']);
                    
                    if($toko['kategori_jasa'] === 'darurat') {
                        $bg_color = 'bg-[#e63946]'; $icon = 'fas fa-fire'; $nama_kat_tampil = 'Hot Items';
                    }

                    $rating_format = number_format($toko['avg_rating'], 1);
                    $jml_ulasan = $toko['jml_ulasan'];
                    $teks_ulasan = $jml_ulasan > 0 ? "($jml_ulasan reviews)" : "(New)";
                    
                    // JURUS ULTIMATE TRABAS FOTO PROFIL & BANNER
                    $img_profil = "https://ui-avatars.com/api/?name=".urlencode($toko['nama_toko'])."&background=ff6600&color=fff&size=150&bold=true";
                    $raw_foto = '';
                    
                    if(!empty($toko['foto_toko'])) $raw_foto = $toko['foto_toko'];
                    elseif(!empty($toko['foto'])) $raw_foto = $toko['foto'];
                    elseif(!empty($toko['foto_user'])) $raw_foto = $toko['foto_user'];

                    if (!empty($raw_foto) && !in_array(strtolower($raw_foto), ['default.png', 'default.jpg'])) {
                        $clean_foto = str_replace(['uploads/profil/', 'profil/', 'uploads/'], '', $raw_foto);
                        $img_profil = $base_url . '/uploads/profil/' . $clean_foto; 
                    }
                    
                    $banners = [];
                    foreach(['banner1', 'banner2', 'banner3'] as $bKey) {
                        if (!empty($toko[$bKey])) {
                            $clean_b = str_replace(['uploads/profil/', 'profil/', 'uploads/'], '', $toko[$bKey]);
                            $banners[] = $base_url . '/uploads/profil/' . $clean_b;
                        }
                    }
                ?>
                <a href="<?= $base_url ?>/detail_toko.php?id=<?= $toko['id'] ?>" class="bg-white rounded-[1.5rem] border border-gray-200 shadow-[0_8px_30px_rgb(0,0,0,0.04)] hover:shadow-[0_20px_40px_rgb(0,0,0,0.08)] hover:-translate-y-1.5 hover:border-orange/30 transition-all duration-300 flex flex-col group overflow-hidden" data-aos="fade-up" data-aos-delay="<?= $delay ?>">
                    
                    <div class="h-28 <?= empty($banners) ? $bg_color : 'bg-gray-200' ?> relative flex items-center justify-center overflow-hidden">
                        <?php if(empty($banners)): ?>
                            <div class="absolute inset-0 bg-black/20 mix-blend-overlay"></div>
                            <i class="<?= $icon ?> text-6xl text-white/10 group-hover:scale-125 transition-transform duration-700"></i>
                        <?php elseif(count($banners) == 1): ?>
                            <img src="<?= $banners[0] ?>" class="absolute inset-0 w-full h-full object-cover group-hover:scale-110 transition-transform duration-700">
                            <div class="absolute inset-0 bg-black/30 group-hover:bg-black/40 transition-colors"></div>
                        <?php else: ?>
                            <div class="absolute inset-0 w-full h-full">
                                <?php foreach($banners as $idx => $bnnr): ?>
                                    <img src="<?= $bnnr ?>" class="absolute inset-0 w-full h-full object-cover animate-bg-slider" style="animation-duration: <?= count($banners)*4 ?>s; animation-delay: <?= $idx*4 ?>s;">
                                <?php endforeach; ?>
                                <div class="absolute inset-0 bg-black/30 group-hover:bg-black/40 transition-colors z-10"></div>
                            </div>
                        <?php endif; ?>
                        
                        <div class="absolute top-3 right-3 bg-white/20 backdrop-blur-md text-white border border-white/30 px-3 py-1 rounded-full text-[9px] font-black uppercase tracking-widest shadow-sm flex items-center gap-1.5 z-20">
                            <i class="fas fa-check-circle"></i> Verified
                        </div>
                    </div>
                    
                    <div class="p-5 relative flex-1 flex flex-col bg-white">
                        <div class="absolute -top-8 left-5 w-14 h-14 bg-white rounded-2xl shadow-lg border border-gray-100 flex items-center justify-center overflow-hidden z-20 group-hover:scale-110 transition-transform p-0.5">
                            <img src="<?= $img_profil ?>" alt="Toko" class="w-full h-full object-cover rounded-[14px]">
                        </div>
                        
                        <div class="mt-6 mb-3">
                            <h3 class="font-black text-lg text-navy leading-tight mb-1 truncate"><?= htmlspecialchars($toko['nama_toko']) ?></h3>
                            <p class="text-xs font-semibold text-gray-500 flex items-center gap-1.5 truncate"><i class="fas fa-user-tie text-gray-300"></i> <?= htmlspecialchars($toko['nama_lengkap']) ?></p>
                        </div>
                        
                        <div class="flex flex-wrap gap-2 mb-6 mt-auto">
                            <span class="bg-gray-100 text-gray-600 text-[9px] font-black uppercase px-2.5 py-1 rounded-md"><?= $nama_kat_tampil ?></span>
                        </div>
                        
                        <div class="mt-auto pt-4 border-t border-gray-100 flex items-center justify-between">
                            <div class="flex items-center gap-1 text-sm font-black text-navy"><i class="fas fa-star text-yellow-400"></i> <?= $rating_format ?> <span class="text-[10px] text-gray-400 font-bold ml-0.5"><?= $teks_ulasan ?></span></div>
                            <div class="bg-navy text-white px-4 py-1.5 rounded-full text-[10px] font-black uppercase group-hover:bg-orange transition-colors">Detail</div>
                        </div>
                    </div>
                </a>
                <?php $delay += 50; endwhile; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script>AOS.init({ once: true, duration: 600, offset: 50, easing: 'ease-out-cubic' });</script>
<?php require_once 'includes/footer.php'; ?>