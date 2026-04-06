<?php
require_once 'config/database.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// Tangkap parameter halaman (default: Pusat Bantuan (FAQ))
$page_title = isset($_GET['p']) ? mysqli_real_escape_string($conn, trim($_GET['p'])) : 'Help Center (FAQ)';

// 1. Ambil semua halaman untuk Sidebar Menu
$query_menu = mysqli_query($conn, "SELECT * FROM halaman_statis ORDER BY kategori_menu ASC, urutan ASC");
$menus = [];
if ($query_menu) {
    while($row = mysqli_fetch_assoc($query_menu)) {
        $menus[$row['kategori_menu']][] = $row;
    }
}

// 2. Ambil konten halaman yang sedang aktif
$query_active = mysqli_query($conn, "SELECT * FROM halaman_statis WHERE judul = '$page_title' LIMIT 1");
$active_page = $query_active ? mysqli_fetch_assoc($query_active) : null;

require_once 'includes/header.php';
?>

<link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
<style>
    /* Animasi Buka Tutup FAQ */
    details > summary { list-style: none; }
    details > summary::-webkit-details-marker { display: none; }
    @keyframes fadeInDown { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
    .group-open\:animate-fadeIn { animation: fadeInDown 0.3s ease-out forwards; }
</style>

<div class="bg-[#f4f7fa] min-h-[80vh] py-8 md:py-20 font-sans relative overflow-hidden">
    <div class="absolute top-0 right-0 w-[300px] md:w-[400px] h-[300px] md:h-[400px] bg-gradient-radial from-orange/10 to-transparent rounded-full blur-3xl opacity-50 translate-x-1/3 -translate-y-1/4 pointer-events-none"></div>

    <div class="max-w-screen-xl mx-auto px-4 md:px-8 relative z-10">
        <!-- PERBAIKAN UX MOBILE: flex-col-reverse agar Konten di ATAS, Menu di BAWAH -->
        <div class="flex flex-col-reverse lg:flex-row gap-6 md:gap-12 items-start">
            
            <!-- SIDEBAR NAVIGASI DINAMIS -->
            <div class="w-full lg:w-1/4 shrink-0 lg:sticky lg:top-24" data-aos="fade-up" data-aos-delay="200">
                <div class="bg-white rounded-[1.5rem] shadow-sm border border-gray-100 p-2 overflow-hidden">
                    
                    <?php if(isset($menus['perusahaan'])): ?>
                        <div class="px-4 py-3 text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-50 mb-2">Company</div>
                        <?php foreach($menus['perusahaan'] as $m): 
                            $is_active = (strtolower($m['judul']) == strtolower($page_title));
                        ?>
                            <a href="?p=<?= urlencode($m['judul']) ?>" class="<?= $is_active ? 'bg-orange/10 text-orange font-bold border-r-4 border-orange' : 'text-gray-600 hover:bg-gray-50 hover:text-navy font-medium' ?> flex items-center gap-3 px-4 py-3 rounded-lg text-xs md:text-sm transition-all mb-1">
                                <i class="fas <?= htmlspecialchars($m['ikon']) ?> w-5 text-center"></i> <?= htmlspecialchars($m['judul']) ?>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <?php if(isset($menus['bantuan'])): ?>
                        <div class="px-4 py-3 text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-50 mt-4 mb-2">Help & Legal</div>
                        <?php foreach($menus['bantuan'] as $m): 
                            $is_active = (strtolower($m['judul']) == strtolower($page_title));
                        ?>
                            <a href="?p=<?= urlencode($m['judul']) ?>" class="<?= $is_active ? 'bg-orange/10 text-orange font-bold border-r-4 border-orange' : 'text-gray-600 hover:bg-gray-50 hover:text-navy font-medium' ?> flex items-center gap-3 px-4 py-3 rounded-lg text-xs md:text-sm transition-all mb-1">
                                <i class="fas <?= htmlspecialchars($m['ikon']) ?> w-5 text-center"></i> <?= htmlspecialchars($m['judul']) ?>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>

                </div>
            </div>

            <!-- AREA KONTEN BACAAN DINAMIS -->
            <div class="w-full lg:w-3/4 bg-white rounded-[1.5rem] md:rounded-[2rem] shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-gray-100 p-6 md:p-12" data-aos="fade-up">
                <?php if($active_page): ?>
                    <!-- KONTEN DARI DATABASE -->
                    <div class="prose prose-sm md:prose-base prose-orange max-w-none">
                        <?= $active_page['konten'] ?>
                    </div>
                    <div class="text-[10px] md:text-xs text-gray-400 mt-8 md:mt-12 pt-6 border-t border-gray-100 flex items-center gap-2">
                        <i class="far fa-clock"></i> Last updated: <?= date('d M Y', strtotime($active_page['updated_at'])) ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-12">
                        <div class="w-20 h-20 md:w-24 md:h-24 bg-orange/10 text-orange rounded-full flex items-center justify-center text-3xl md:text-4xl mx-auto mb-6">
                            <i class="fas fa-file-signature"></i>
                        </div>
                        <h1 class="text-2xl md:text-3xl font-black text-navy mb-4 tracking-tight">Page Not Found</h1>
                        <p class="text-xs md:text-sm text-gray-500 font-medium leading-relaxed mb-8 max-w-md mx-auto">
                            The content for <span class="font-bold text-navy">"<?= htmlspecialchars($page_title) ?>"</span> is not yet available.
                        </p>
                        <a href="<?= $base_url ?>/index.php" class="bg-navy hover:bg-orange text-white px-6 md:px-8 py-3 rounded-full font-black text-[10px] md:text-xs uppercase tracking-widest transition-all inline-block shadow-lg hover:-translate-y-1">Back to Home</a>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </div>
</div>

<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script>
    AOS.init({ once: true, duration: 600, easing: 'ease-out-cubic' });
</script>

<?php require_once 'includes/footer.php'; ?>