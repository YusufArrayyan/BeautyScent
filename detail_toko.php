<?php
// 1. KONEKSI & SESSION
require_once 'config/database.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Ambil ID Toko dari URL
if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$id_toko = mysqli_real_escape_string($conn, $_GET['id']);
$pesan_status = "";

// 2. AMBIL DATA TOKO & KATEGORI (JOIN)
$query = mysqli_query($conn, "SELECT t.*, u.id as pemilik_user_id, u.nama_lengkap, u.no_hp, u.foto_profil, k.nama_kategori as nama_kat_asli, k.ikon 
                              FROM toko t 
                              JOIN users u ON t.user_id = u.id 
                              LEFT JOIN kategori k ON t.kategori_jasa = k.id 
                              WHERE t.id = '$id_toko'");
$data = mysqli_fetch_assoc($query);

if (!$data) {
    die("<div style='text-align:center; padding:50px; font-family:serif;'><h2>Brand Not Found</h2><a href='index.php'>Return to Home</a></div>");
}

// --- LOGIKA KERANJANG (CART) ---
if (isset($_POST['masukkan_keranjang'])) {
    if (!isset($_SESSION['user_id'])) {
        header("Location: auth/login.php?redirect=detail_toko.php?id=" . $id_toko);
        exit();
    }
    
    $user_role = isset($_SESSION['role']) ? $_SESSION['role'] : '';
    if ($user_role === 'toko' && $_SESSION['user_id'] == $data['pemilik_user_id']) {
        $pesan_status = "error_self";
    } else {
        $user_id_pembeli = $_SESSION['user_id'];
        $id_layanan_dipesan = mysqli_real_escape_string($conn, $_POST['id_layanan']);
        $harga_satuan = mysqli_real_escape_string($conn, $_POST['harga']);

        try {
            $cek_keranjang = mysqli_query($conn, "SELECT * FROM keranjang WHERE user_id = '$user_id_pembeli' AND layanan_id = '$id_layanan_dipesan'");
            if(mysqli_num_rows($cek_keranjang) > 0) {
                $pesan_status = "sudah_ada";
            } else {
                $insert_cart = mysqli_query($conn, "INSERT INTO keranjang (user_id, toko_id, layanan_id, harga_satuan, jumlah) 
                                                    VALUES ('$user_id_pembeli', '$id_toko', '$id_layanan_dipesan', '$harga_satuan', 1)");
                if ($insert_cart) { $pesan_status = "sukses"; } 
                else { $pesan_status = "error_db"; error_log("Insert Cart Failed: " . mysqli_error($conn)); }
            }
        } catch (Exception $e) {
            $pesan_status = "error_db"; error_log("Cart Exception: " . $e->getMessage());
        }
    }
}

// ==========================================
// MENGAMBIL STATISTIK TOKO ASLI
// ==========================================
$q_pesanan = mysqli_query($conn, "SELECT COUNT(id) as total_selesai FROM pesanan WHERE toko_id = '$id_toko' AND status = 'selesai'");
$pesanan_selesai = mysqli_fetch_assoc($q_pesanan)['total_selesai'] ?? 0;

$q_ulasan = mysqli_query($conn, "SELECT u.*, us.nama_lengkap, us.foto_profil, p.tanggal_layanan, l.nama_layanan 
                                 FROM ulasan u 
                                 JOIN users us ON u.user_id = us.id 
                                 JOIN pesanan p ON u.pesanan_id = p.id
                                 LEFT JOIN layanan l ON p.layanan_id = l.id
                                 WHERE u.toko_id = '$id_toko' 
                                 ORDER BY u.id DESC");
$total_ulasan = mysqli_num_rows($q_ulasan);

$total_bintang = 0;
$rating_rata2 = 0.0;
$list_ulasan = [];

if ($total_ulasan > 0) {
    while($row = mysqli_fetch_assoc($q_ulasan)){
        $total_bintang += $row['rating'];
        $list_ulasan[] = $row;
    }
    $rating_rata2 = round($total_bintang / $total_ulasan, 1);
}

// ==========================================
// LOGIKA FOTO PROFIL DINAMIS (DISAMAKAN DENGAN INDEX.PHP)
// ==========================================
$img_profil = "https://ui-avatars.com/api/?name=".urlencode($data['nama_toko'])."&background=ff6600&color=fff&size=200&bold=true";
$raw_foto = '';

// Deteksi nama kolom apapun yang terisi di DB
if(!empty($data['foto_toko'])) $raw_foto = $data['foto_toko'];
elseif(!empty($data['foto'])) $raw_foto = $data['foto'];
elseif(!empty($data['foto_profil'])) $raw_foto = $data['foto_profil'];

if (!empty($raw_foto) && !in_array(strtolower($raw_foto), ['default.png', 'default.jpg'])) {
    // Bersihkan teks kotor path
    $clean_foto = str_replace(['uploads/profil/', 'profil/', 'uploads/'], '', $raw_foto);
    $img_profil = 'uploads/profil/' . $clean_foto; 
}

// Cek Banner Toko
$banners = [];
if(!empty($data['banner1'])) $banners[] = 'uploads/profil/' . str_replace(['uploads/profil/', 'profil/', 'uploads/'], '', $data['banner1']);
if(!empty($data['banner2'])) $banners[] = 'uploads/profil/' . str_replace(['uploads/profil/', 'profil/', 'uploads/'], '', $data['banner2']);
if(!empty($data['banner3'])) $banners[] = 'uploads/profil/' . str_replace(['uploads/profil/', 'profil/', 'uploads/'], '', $data['banner3']);

// Tema Warna
$bg_cover = 'from-[#e8a0bf] to-[#fbcfe8]'; 
$badge_color = 'bg-pink-400 text-white';

$kat_cek = strtolower($data['kategori_jasa'] ?? '');
if($kat_cek == 'perfume' || $kat_cek == 'makeup') { $bg_cover = 'from-[#f472b6] to-[#e8a0bf]'; $badge_color = 'bg-pink-500 text-white';}
elseif($kat_cek == 'skincare') { $bg_cover = 'from-[#fed7aa] to-[#fbcfe8]'; $badge_color = 'bg-orange text-white';}
elseif($kat_cek == 'bodycare') { $bg_cover = 'from-[#e9d5ff] to-[#fbcfe8]'; $badge_color = 'bg-purple-400 text-white';}
elseif($kat_cek == 'bestseller') { $bg_cover = 'from-[#fef08a] to-[#fbcfe8]'; $badge_color = 'bg-yellow-400 text-dark';}

$nama_kategori_final = !empty($data['nama_kat_asli']) ? htmlspecialchars($data['nama_kat_asli']) : htmlspecialchars($data['kategori_jasa'] ?? 'Umum');

// Ambil daftar jasa
$layanan_query = mysqli_query($conn, "SELECT * FROM layanan WHERE toko_id = '$id_toko' ORDER BY id DESC");

require_once 'includes/header.php';
?>

<link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.css" />

<div class="bg-[#f4f7fa] min-h-screen pb-24 font-sans text-gray-800">

    <!-- HERO COVER BANNER -->
    <div class="h-64 md:h-80 relative overflow-hidden bg-gradient-to-r <?= $bg_cover ?>">
        <?php if(count($banners) > 0): ?>
            <div class="swiper bannerSwiper h-full w-full absolute inset-0 z-0">
                <div class="swiper-wrapper">
                    <?php foreach($banners as $ban): ?>
                        <div class="swiper-slide">
                            <img src="<?= $ban ?>" class="w-full h-full object-cover opacity-80 mix-blend-overlay">
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="swiper-pagination"></div>
            </div>
            <div class="absolute inset-0 bg-gradient-to-t from-navy/90 to-transparent z-10"></div>
        <?php else: ?>
            <div class="absolute inset-0 opacity-10 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAiIGhlaWdodD0iMjAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PGNpcmNsZSBjeD0iMiIgY3k9IjIiIHI9IjEiIGZpbGw9InJnYmEoMjU1LDI1NSwyNTUsMSkiLz48L3N2Zz4=')]"></div>
            <div class="max-w-screen-xl mx-auto px-5 md:px-8 relative h-full flex items-center justify-end z-10">
                <?php $icon_bg = !empty($data['ikon']) ? htmlspecialchars($data['ikon']) : 'fas fa-store'; ?>
                <i class="<?= $icon_bg ?> absolute -right-10 -bottom-10 text-9xl md:text-[200px] text-white/10 rotate-12 pointer-events-none"></i>
            </div>
        <?php endif; ?>
    </div>

    <!-- MAIN CONTENT -->
    <div class="max-w-screen-xl mx-auto px-5 md:px-8 -mt-24 md:-mt-32 relative z-20">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 md:gap-10">
            
            <!-- SISI KIRI: PROFILE CARD -->
            <div class="lg:col-span-4" data-aos="fade-up">
                <div class="bg-white rounded-[2.5rem] shadow-xl border border-gray-100 overflow-hidden sticky top-28 pb-8">
                    
                    <div class="relative flex justify-center pt-8 pb-4">
                        <div class="w-32 h-32 md:w-40 md:h-40 rounded-full border-[6px] border-white shadow-2xl relative z-10 overflow-hidden bg-white p-1">
                            <img src="<?= $img_profil ?>" alt="Profil Toko" class="w-full h-full object-cover rounded-full">
                        </div>
                        <?php if(isset($data['status_verifikasi']) && $data['status_verifikasi'] == 'verified'): ?>
                            <div class="absolute bottom-5 ml-24 md:ml-32 z-20 bg-white p-1 rounded-full shadow-md" title="Verified Store">
                                <i class="fas fa-check-circle text-blue-500 text-2xl"></i>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="px-8 text-center">
                        <h1 class="text-2xl md:text-3xl font-black text-navy mb-1 tracking-tight leading-tight"><?= htmlspecialchars($data['nama_toko']) ?></h1>
                        <p class="text-xs font-bold text-gray-400 mb-6 flex items-center justify-center gap-1.5 uppercase tracking-widest"><i class="fas fa-user-tie"></i> <?= htmlspecialchars($data['nama_lengkap']) ?></p>
                        
                        <div class="grid grid-cols-2 gap-3 mb-8">
                            <div class="bg-gray-50 p-4 rounded-3xl border border-gray-100 flex flex-col items-center justify-center transition-all hover:bg-orange/5 hover:border-orange/20 group">
                                <p class="text-[9px] text-gray-400 font-black uppercase tracking-widest mb-1 group-hover:text-orange">Rating</p>
                                <p class="text-lg font-black text-navy flex items-center gap-1">
                                    <i class="fas fa-star text-yellow-400"></i> 
                                    <?= $rating_rata2 > 0 ? $rating_rata2 : '-' ?> 
                                    <span class="text-[10px] text-gray-400 font-bold ml-1">(<?= $total_ulasan ?>)</span>
                                </p>
                            </div>
                            <div class="bg-gray-50 p-4 rounded-3xl border border-gray-100 flex flex-col justify-center items-center transition-all hover:bg-pink-50 hover:border-pink-200 group">
                                <p class="text-[9px] text-gray-400 font-black uppercase tracking-widest mb-1 group-hover:text-pink-500">Orders</p>
                                <p class="text-lg font-black text-navy flex items-center gap-1">
                                    <i class="fas fa-shopping-bag text-pink-400"></i> <?= $pesanan_selesai ?> <span class="text-[10px] text-gray-400 font-bold ml-1">Sold</span>
                                </p>
                            </div>
                        </div>

                        <div class="mb-8 text-left bg-gray-50/50 p-5 rounded-3xl border border-gray-100">
                            <h4 class="font-black text-[10px] uppercase tracking-widest text-gray-400 mb-2 flex items-center gap-2"><i class="fas fa-map-marker-alt text-orange"></i> Store / Boutique Location</h4>
                            <p class="text-xs text-navy font-bold leading-relaxed"><?= !empty($data['alamat']) ? nl2br(htmlspecialchars($data['alamat'])) : "Address has not been updated yet." ?></p>
                            <?php if(!empty($data['latitude']) && !empty($data['longitude'])): ?>
                                <a href="https://www.google.com/maps/search/?api=1&query=<?= $data['latitude'] ?>,<?= $data['longitude'] ?>" target="_blank" class="text-[10px] font-black text-blue-600 uppercase tracking-widest mt-2 inline-block hover:underline"><i class="fas fa-location-arrow"></i> Open Google Maps</a>
                            <?php endif; ?>
                        </div>

                        <?php if(!isset($_SESSION['user_id'])): ?>
                            <a href="<?= $base_url ?>/auth/login.php?redirect=detail_toko.php?id=<?= $id_toko ?>" class="w-full bg-gradient-to-r from-orange to-[#d4af37] text-white font-black py-4 rounded-2xl shadow-lg flex items-center justify-center gap-3 transition-all duration-300 transform hover:-translate-y-1 uppercase tracking-widest text-xs group">
                                <i class="far fa-comments text-xl group-hover:animate-bounce"></i> Chat Expert
                            </a>
                            <p class="text-[9px] text-gray-400 mt-3 font-semibold">*Please log in to use the chat feature.</p>
                        <?php else: ?>
                            <a href="<?= $base_url ?>/chat.php?uid=<?= $data['pemilik_user_id'] ?>" class="w-full bg-gradient-to-r from-orange to-[#d4af37] text-white font-black py-4 rounded-2xl shadow-lg flex items-center justify-center gap-3 transition-all duration-300 transform hover:-translate-y-1 uppercase tracking-widest text-xs group">
                                <i class="far fa-comments text-xl group-hover:animate-bounce"></i> Chat Expert
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- SISI KANAN -->
            <div class="lg:col-span-8 space-y-8 mt-12 md:mt-0">
                
                <?php if($pesan_status == 'sukses'): ?>
                    <div class="bg-green-500 text-white p-6 rounded-[2rem] shadow-xl shadow-green-500/20 font-bold flex flex-col md:flex-row items-center justify-between animate-bounce border border-green-400">
                        <div class="flex items-center gap-3 mb-4 md:mb-0 text-sm md:text-base"><i class="fas fa-check-circle text-2xl"></i> Item added to your cart successfully!</div>
                        <a href="pembeli/keranjang.php" class="bg-white text-green-600 px-6 py-3 rounded-xl text-xs hover:bg-gray-100 transition shadow-sm uppercase tracking-widest font-black whitespace-nowrap">Proceed to Checkout &rarr;</a>
                    </div>
                <?php elseif($pesan_status == 'sudah_ada'): ?>
                    <div class="bg-yellow-500 text-white p-5 rounded-[2rem] shadow-lg font-bold flex items-center justify-between gap-3 text-sm">
                        <div class="flex items-center gap-3"><i class="fas fa-info-circle text-2xl"></i> This item is already in your cart.</div>
                        <a href="pembeli/keranjang.php" class="bg-white text-yellow-600 px-4 py-2 rounded-lg text-xs hover:bg-gray-100 font-black uppercase tracking-widest">Open Cart</a>
                    </div>
                <?php elseif($pesan_status == 'error_self'): ?>
                    <div class="bg-red-500 text-white p-5 rounded-[2rem] shadow-lg font-bold flex items-center gap-3 text-sm">
                        <i class="fas fa-exclamation-triangle text-2xl"></i> You cannot order from your own store!
                    </div>
                <?php elseif($pesan_status == 'error_db'): ?>
                    <div class="bg-red-500 text-white p-5 rounded-[2rem] shadow-lg font-bold flex items-center gap-3 text-sm">
                        <i class="fas fa-times-circle text-2xl"></i> A database error occurred while adding to cart.
                    </div>
                <?php endif; ?>

                <div class="bg-white rounded-[2.5rem] shadow-sm border border-gray-100 p-8 md:p-10" data-aos="fade-left">
                    <div class="flex justify-between items-center mb-6 border-b border-gray-100 pb-4">
                        <h3 class="text-xl md:text-2xl font-bold font-serif text-navy flex items-center gap-3 border-l-4 border-orange pl-4 tracking-tight">Brand Story & Details</h3>
                        <span class="<?= $badge_color ?> px-4 py-2 rounded-xl text-[10px] uppercase font-black tracking-widest text-center shrink-0">Expert in <?= $nama_kategori_final ?></span>
                    </div>
                    <div class="text-gray-500 leading-relaxed text-sm md:text-base font-medium prose prose-orange">
                        <?= !empty($data['deskripsi_toko']) ? nl2br(htmlspecialchars($data['deskripsi_toko'])) : "<p class='italic text-gray-400'>No description added yet.</p>" ?>
                    </div>
                </div>

                <!-- DAFTAR JASA & HARGA -->
                <div class="bg-white rounded-[2.5rem] shadow-sm border border-gray-100 p-8 md:p-10" data-aos="fade-up">
                    <div class="flex flex-col sm:flex-row justify-between sm:items-end mb-8 gap-4 border-b border-gray-100 pb-6">
                        <div>
                            <h3 class="text-xl md:text-2xl font-bold font-serif text-navy flex items-center gap-3 border-l-4 border-orange pl-4 tracking-tight mb-2">Products & Prices</h3>
                            <p class="text-xs font-bold text-gray-400">Select your favorite items and add them to your cart.</p>
                        </div>
                        <div class="bg-orange/10 text-orange px-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest flex items-center gap-2 shrink-0">
                            <i class="fas fa-gift"></i> Exclusive Online Prices
                        </div>
                    </div>
                    
                    <div class="space-y-5">
                        <?php if($layanan_query && mysqli_num_rows($layanan_query) == 0): ?>
                            <div class="text-center py-16 px-6 border-2 border-dashed border-gray-200 rounded-[2rem] text-gray-400">
                                <div class="text-5xl mb-4 text-gray-300"><i class="fas fa-box-open"></i></div>
                                <h4 class="text-lg font-black text-navy mb-2">No Products Yet</h4>
                                <p class="text-sm font-medium">This brand hasn't uploaded any products.</p>
                            </div>
                        <?php else: ?>
                            <?php while($l = mysqli_fetch_assoc($layanan_query)): ?>
                                <div class="bg-white border border-gray-100 p-4 md:p-6 rounded-[2rem] flex flex-col md:flex-row gap-6 items-center hover:shadow-2xl hover:-translate-y-1 hover:border-orange/40 transition-all duration-300 group relative overflow-hidden">
                                    
                                    <!-- BADGE PROMO -->
                                    <?php if(isset($l['harga_coret']) && $l['harga_coret'] > $l['harga']): ?>
                                        <div class="absolute top-4 left-[-35px] bg-red text-white text-[8px] font-black px-10 py-1 rotate-[-45deg] uppercase tracking-widest shadow-md z-10">Promo</div>
                                    <?php endif; ?>

                                    <!-- Foto Portofolio -->
                                    <div class="w-full md:w-40 h-32 shrink-0 rounded-2xl overflow-hidden bg-gray-100 border border-gray-200 relative">
                                        <?php if(!empty($l['foto_layanan'])): ?>
                                            <img src="uploads/layanan/<?= $l['foto_layanan'] ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                                        <?php else: ?>
                                            <div class="w-full h-full flex flex-col items-center justify-center text-gray-400 bg-gray-50">
                                                <i class="fas fa-image text-2xl mb-1"></i>
                                                <span class="text-[9px] font-bold uppercase">No Photo</span>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <div class="flex-1 w-full">
                                        <div class="flex justify-between items-start mb-2">
                                            <h4 class="font-black text-navy text-lg uppercase tracking-tight group-hover:text-orange transition-colors pr-4"><?= htmlspecialchars($l['nama_layanan']) ?></h4>
                                            
                                            <div class="text-right shrink-0">
                                                <?php if(isset($l['harga_coret']) && $l['harga_coret'] > $l['harga']): ?>
                                                    <p class="text-[10px] text-red font-black line-through mb-0.5">Rp <?= number_format($l['harga_coret'], 0, ',', '.') ?></p>
                                                <?php endif; ?>
                                                <p class="text-xl font-black text-orange italic tracking-tight whitespace-nowrap">Rp <?= number_format($l['harga'], 0, ',', '.') ?></p>
                                            </div>
                                        </div>
                                        <p class="text-xs text-gray-500 font-medium mb-4 line-clamp-2"><?= htmlspecialchars($l['deskripsi_layanan']) ?></p>
                                        
                                        <div class="shrink-0 w-full flex flex-col sm:flex-row gap-2 pt-4 border-t border-gray-100">
                                            <form action="" method="POST" class="flex-1">
                                                <input type="hidden" name="id_layanan" value="<?= $l['id'] ?>">
                                                <input type="hidden" name="harga" value="<?= $l['harga'] ?>">
                                                <button type="submit" name="masukkan_keranjang" class="w-full bg-gradient-to-r from-orange to-[#d4af37] text-white px-6 py-3 rounded-xl font-black uppercase tracking-widest text-[10px] hover:brightness-110 transition-all flex items-center justify-center gap-2 shadow-md">
                                                    <i class="fas fa-cart-plus"></i> Add to Cart
                                                </button>
                                            </form>

                                            <?php if(isset($_SESSION['user_id'])): ?>
                                                <a href="<?= $base_url ?>/chat.php?uid=<?= $data['pemilik_user_id'] ?>&pesan=Hi%20there,%20I%20have%20a%20question%20about%20<?= urlencode($l['nama_layanan']) ?>" class="flex-1 text-orange bg-orange/10 hover:bg-orange hover:text-white border border-orange/20 px-6 py-3 rounded-xl font-black uppercase tracking-widest text-[10px] transition-all flex items-center justify-center gap-2 text-center">
                                                    <i class="far fa-comment-dots"></i> Ask Expert
                                                </a>
                                            <?php else: ?>
                                                <a href="<?= $base_url ?>/auth/login.php?redirect=detail_toko.php?id=<?= $id_toko ?>" class="flex-1 text-orange bg-orange/10 hover:bg-orange hover:text-white border border-orange/20 px-6 py-3 rounded-xl font-black uppercase tracking-widest text-[10px] transition-all flex items-center justify-center gap-2 text-center">
                                                    <i class="far fa-comment-dots"></i> Ask Expert
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- ULASAN & RATING -->
                <div class="bg-white rounded-[2.5rem] shadow-sm border border-gray-100 p-8 md:p-10" data-aos="fade-up">
                    <div class="flex justify-between items-center mb-8 border-b border-gray-100 pb-6">
                        <h3 class="text-xl md:text-2xl font-bold font-serif text-navy flex items-center gap-3 border-l-4 border-yellow-400 pl-4 tracking-tight"><i class="fas fa-star text-yellow-400"></i> Customer Diaries</h3>
                        <span class="bg-gray-100 text-gray-500 px-4 py-2 rounded-xl text-[10px] uppercase font-black tracking-widest text-center shrink-0"><?= $total_ulasan ?> Reviews</span>
                    </div>

                    <?php if($total_ulasan > 0): ?>
                        <div class="space-y-6">
                            <?php foreach($list_ulasan as $ulasan): 
                                $bintang_html = '';
                                for($i=1; $i<=5; $i++) {
                                    if($i <= $ulasan['rating']) $bintang_html .= '<i class="fas fa-star text-yellow-400"></i>';
                                    else $bintang_html .= '<i class="far fa-star text-gray-300"></i>';
                                }
                                
                                $foto_pengulas = "https://ui-avatars.com/api/?name=".urlencode($ulasan['nama_lengkap'])."&background=f3f4f6&color=475569";
                                if (!empty($ulasan['foto_profil']) && !in_array(strtolower($ulasan['foto_profil']), ['default.png', 'default.jpg'])) {
                                    $foto_pengulas = 'uploads/profil/' . str_replace(['uploads/profil/', 'profil/', 'uploads/'], '', $ulasan['foto_profil']);
                                }

                                $tgl_tampil = $ulasan['tanggal_ulasan'] ?? $ulasan['tanggal'] ?? $ulasan['created_at'] ?? $ulasan['tanggal_layanan'] ?? date('Y-m-d');
                            ?>
                            
                            <div class="bg-gray-50/50 p-6 rounded-3xl border border-gray-100 flex gap-5">
                                <img src="<?= $foto_pengulas ?>" class="w-12 h-12 rounded-full object-cover shrink-0 border border-gray-200">
                                <div class="flex-1 min-w-0">
                                    <div class="flex justify-between items-start mb-2">
                                        <div>
                                            <h4 class="font-black text-navy text-sm"><?= htmlspecialchars($ulasan['nama_lengkap']) ?></h4>
                                            <p class="text-[9px] font-bold text-gray-400 uppercase tracking-widest mt-0.5"><?= date('d M Y', strtotime($tgl_tampil)) ?></p>
                                        </div>
                                        <div class="text-sm flex gap-0.5 shrink-0">
                                            <?= $bintang_html ?>
                                        </div>
                                    </div>
                                    <p class="text-gray-600 text-sm font-medium leading-relaxed bg-white p-4 rounded-2xl border border-gray-100">"<?= nl2br(htmlspecialchars($ulasan['komentar'])) ?>"</p>
                                    
                                    <?php if(!empty($ulasan['nama_layanan'])): ?>
                                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mt-3 flex items-center gap-1.5"><i class="fas fa-gift"></i> Product: <?= htmlspecialchars($ulasan['nama_layanan']) ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-12 px-6 border-2 border-dashed border-gray-200 rounded-[2rem] text-gray-400">
                            <div class="text-5xl mb-4 text-yellow-300"><i class="far fa-star"></i></div>
                            <h4 class="text-lg font-black text-navy mb-2">No Reviews Yet</h4>
                            <p class="text-sm font-medium">This brand hasn't received any customer diaries yet.</p>
                        </div>
                    <?php endif; ?>
                </div>

            </div>
        </div>
    </div>
</div>

<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script src="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.js"></script>
<script>
    AOS.init({ once: true, duration: 800, offset: 50, easing: 'ease-out-cubic' });

    var swiper = new Swiper(".bannerSwiper", {
        loop: true,
        autoplay: {
            delay: 4000,
            disableOnInteraction: false,
        },
        pagination: {
            el: ".swiper-pagination",
            clickable: true,
        },
        effect: "fade",
        fadeEffect: { crossFade: true }
    });
</script>

<?php require_once 'includes/footer.php'; ?>