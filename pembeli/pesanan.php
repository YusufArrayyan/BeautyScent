<?php
// PASTIKAN TIDAK ADA SPASI KOSONG SEBELUM TAG PHP INI
require_once '../config/database.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. AMBIL ID USER DARI SESI
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

// Jika belum login -> Arahkan ke Login
if (!$user_id) {
    header("Location: ../auth/login.php");
    exit();
}

// 2. CEK ROLE LANGSUNG KE DATABASE
$q_cek_role = mysqli_query($conn, "SELECT role FROM users WHERE id = '$user_id'");
if ($q_cek_role && mysqli_num_rows($q_cek_role) > 0) {
    $data_role = mysqli_fetch_assoc($q_cek_role);
    $user_role = strtolower(trim($data_role['role']));
} else {
    $user_role = '';
}

// [PERBAIKAN] Blok kode yang menendang user selain 'pembeli' telah DIHAPUS.
// Sekarang akun dengan role 'toko' atau 'admin' tetap bisa mengakses riwayat belanjanya.

// 3. AMBIL RIWAYAT PESANAN 
$query = mysqli_query($conn, "SELECT p.*, t.nama_toko, u.no_hp, l.nama_layanan 
                              FROM pesanan p 
                              JOIN toko t ON p.toko_id = t.id 
                              JOIN users u ON t.user_id = u.id
                              LEFT JOIN layanan l ON p.layanan_id = l.id
                              WHERE p.user_id = '$user_id' 
                              ORDER BY p.id DESC");

// 4. CEK APAKAH SUDAH PERNAH MENYELESAIKAN PESANAN (UNTUK TOMBOL REVIEW APLIKASI)
$q_cek_selesai = mysqli_query($conn, "SELECT id FROM pesanan WHERE user_id = '$user_id' AND status = 'selesai' LIMIT 1");
$sudah_ada_selesai = ($q_cek_selesai && mysqli_num_rows($q_cek_selesai) > 0);

// Cek apakah sudah pernah review aplikasi
$q_cek_rev_app = mysqli_query($conn, "SELECT id FROM ulasan_platform WHERE user_id = '$user_id'");
$sudah_review_app = ($q_cek_rev_app && mysqli_num_rows($q_cek_rev_app) > 0);

// Panggil Header Global
require_once '../includes/header.php'; 
?>

<link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">

<div class="bg-[#f4f7fa] min-h-screen font-sans text-gray-800 pb-24">

    <div class="bg-navy pt-12 pb-16 relative overflow-hidden">
        <div class="absolute top-0 right-0 w-64 h-64 bg-orange rounded-full blur-[100px] opacity-20 pointer-events-none"></div>
        <div class="absolute bottom-0 left-10 w-40 h-40 bg-blue-500 rounded-full blur-[80px] opacity-20 pointer-events-none"></div>
        <div class="max-w-screen-xl mx-auto px-5 md:px-8 relative z-10">
            <h1 class="text-3xl md:text-4xl font-black text-white mb-2 flex items-center gap-3" data-aos="fade-right">
                Order History
            </h1>
            <p class="text-gray-400 font-medium text-sm md:text-base" data-aos="fade-right" data-aos-delay="100">Pantau status seller dan selesaikan pembayaran Anda di sini.</p>
        </div>
    </div>

    <div class="max-w-screen-xl mx-auto px-4 md:px-8 pt-8 relative z-20">

        <?php if(isset($_SESSION['pesan_sukses'])): ?>
            <div class="bg-green-500 text-white p-5 rounded-[2rem] shadow-sm font-bold flex items-center gap-4 mb-6 border border-green-400" data-aos="zoom-in">
                <div class="w-10 h-10 bg-white/20 rounded-full flex items-center justify-center text-xl shrink-0">
                    <i class="fas fa-check-circle animate-bounce"></i>
                </div>
                <div>
                    <h4 class="text-base md:text-lg font-black tracking-tight">Berhasil!</h4>
                    <p class="text-xs md:text-sm font-medium text-green-50"><?= $_SESSION['pesan_sukses']; unset($_SESSION['pesan_sukses']); ?></p>
                </div>
            </div>
        <?php endif; ?>

        <?php if($sudah_ada_selesai && !$sudah_review_app): ?>
            <div class="bg-white border-2 border-orange/20 p-5 md:p-6 rounded-[1.5rem] shadow-sm flex flex-col md:flex-row items-center justify-between gap-4 mb-8" data-aos="fade-up">
                <div class="flex items-center gap-4 text-left">
                    <div class="w-12 h-12 bg-orange/10 text-orange rounded-full flex items-center justify-center text-2xl shrink-0 border border-orange/20">
                        <i class="fas fa-heart animate-pulse"></i>
                    </div>
                    <div>
                        <h4 class="text-sm md:text-lg font-black text-navy tracking-tight mb-0.5">Puas Menggunakan BeautyScent?</h4>
                        <p class="text-[10px] md:text-sm font-medium text-gray-500">Bagikan pengalamanmu agar pengguna lain ikut terbantu.</p>
                    </div>
                </div>
                <a href="ulasan_platform.php" class="w-full md:w-auto text-center bg-orange text-white px-6 py-3.5 rounded-xl text-[10px] md:text-xs font-black uppercase tracking-widest hover:bg-navy transition-all shadow-md transform hover:-translate-y-1 shrink-0">
                    Leave a Review Aplikasi
                </a>
            </div>
        <?php endif; ?>

        <div class="flex flex-col gap-5" data-aos="fade-up" data-aos-delay="200">
            
            <?php if($query && mysqli_num_rows($query) == 0): ?>
                <div class="bg-white rounded-[2rem] shadow-sm border border-gray-100 p-12 md:p-24 text-center">
                    <div class="w-20 h-20 bg-gray-50 border-2 border-dashed border-gray-200 rounded-[1.5rem] flex items-center justify-center mx-auto mb-6 text-gray-300 text-3xl shadow-inner">
                        <i class="fas fa-box-open"></i>
                    </div>
                    <h4 class="font-black text-navy uppercase tracking-widest text-base md:text-lg mb-2">No orders yet</h4>
                    <p class="text-xs md:text-sm text-gray-500 font-medium mb-8">Kamu belum pernah memesan jasa seller apapun.</p>
                    <a href="<?= $base_url ?>/index.php" class="inline-block bg-orange text-white px-8 py-3.5 rounded-xl font-black uppercase tracking-widest text-[10px] md:text-xs hover:bg-navy transition-all shadow-md transform hover:-translate-y-1">
                        Cari Seller Sekarang
                    </a>
                </div>
            <?php endif; ?>

            <?php 
            if($query):
                while($p = mysqli_fetch_assoc($query)): 
                    $nama_layanan_tampil = !empty($p['nama_layanan']) ? htmlspecialchars($p['nama_layanan']) : "Product Removed";
                    $total_biaya = !empty($p['total_harga']) ? $p['total_harga'] : ($p['harga'] * $p['jumlah']);
                    
                    // Logika Status & Warna
                    if($p['status'] == 'selesai') {
                        $status_bg = 'bg-green-100 text-green-600 border-green-200'; $icon = 'fa-check-double'; $text = 'Selesai';
                    } elseif($p['status'] == 'diproses' || $p['status'] == 'proses') {
                        $status_bg = 'bg-blue-100 text-blue-600 border-blue-200'; $icon = 'fa-cog fa-spin'; $text = 'Diproses';
                    } elseif($p['status'] == 'sudah_dibayar') {
                        $status_bg = 'bg-purple-100 text-purple-600 border-purple-200'; $icon = 'fa-money-check-alt'; $text = 'Sudah Dibayar';
                    } elseif($p['status'] == 'dibatalkan' || $p['status'] == 'batal') {
                        $status_bg = 'bg-red-100 text-red-600 border-red-200'; $icon = 'fa-times-circle'; $text = 'Dibatalkan';
                    } elseif($p['status'] == 'dikomplain') {
                        $status_bg = 'bg-red-500 text-white border-red-600 shadow-sm'; $icon = 'fa-shield-alt animate-pulse'; $text = 'Komplain';
                    } else {
                        $status_bg = 'bg-red-50 text-red-500 border-red-200'; $icon = 'fa-clock'; $text = 'Belum Dibayar';
                    }
            ?>
            
            <div class="bg-white rounded-[1.5rem] shadow-sm hover:shadow-md border border-gray-100 p-5 md:p-6 transition-all duration-300 group">
                
                <div class="flex justify-between items-start border-b border-gray-50 pb-4 mb-4">
                    <div>
                        <p class="text-[9px] md:text-[10px] font-black uppercase tracking-widest text-gray-400 mb-1">ID: #<?= $p['kode_pesanan'] ?></p>
                        <h4 class="font-black text-navy uppercase tracking-tight text-sm md:text-lg group-hover:text-orange transition-colors"><?= $nama_layanan_tampil ?></h4>
                    </div>
                    <div class="shrink-0 text-right ml-2">
                        <span class="px-3 py-1.5 rounded-lg text-[9px] font-black uppercase tracking-widest border <?= $status_bg ?> flex items-center justify-center gap-1.5 whitespace-nowrap">
                            <i class="fas <?= $icon ?>"></i> <?= $text ?>
                        </span>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-5">
                    
                    <div class="flex flex-col gap-2.5">
                        <p class="text-[10px] text-gray-500 font-bold uppercase tracking-widest flex items-center gap-2">
                            <i class="fas fa-calendar-alt text-orange w-4 text-center"></i> 
                            <?= date('d M Y, H:i', strtotime($p['tanggal_layanan'] . ' ' . $p['waktu_layanan'])) ?> WIB
                        </p>
                        <p class="font-black text-navy uppercase tracking-tight flex items-center gap-2 text-xs md:text-sm">
                            <i class="fas fa-store text-orange w-4 text-center"></i> 
                            <?= htmlspecialchars($p['nama_toko']) ?>
                        </p>
                    </div>

                    <div class="md:text-right flex justify-between md:block items-center bg-gray-50 md:bg-transparent p-3 md:p-0 rounded-xl border border-gray-100 md:border-none mt-2 md:mt-0">
                        <p class="text-[9px] text-gray-400 font-bold uppercase tracking-widest md:mb-1">Total Biaya</p>
                        <div class="text-right">
                            <p class="font-black italic text-base md:text-xl text-navy">Rp <?= number_format($total_biaya, 0, ',', '.') ?></p>
                            <p class="text-[8px] md:text-[9px] text-gray-400 font-bold uppercase tracking-widest mt-0.5"><?= htmlspecialchars($p['metode_pembayaran']) ?></p>
                        </div>
                    </div>
                </div>

                <div class="flex flex-wrap gap-2 pt-4 border-t border-gray-50">
                    
                    <a href="https://wa.me/<?= htmlspecialchars($p['no_hp']) ?>?text=Hello%20<?= urlencode($p['nama_toko']) ?>,%20I%20would%20like%20to%20inquire%20about%20my%20order%20(<?= $p['kode_pesanan'] ?>)." target="_blank" class="flex-1 min-w-[120px] text-[10px] bg-[#25D366]/10 text-[#25D366] border border-[#25D366]/20 px-3 py-2.5 rounded-xl font-black uppercase tracking-widest hover:bg-[#25D366] hover:text-white transition-all flex items-center justify-center gap-2">
                        <i class="fab fa-whatsapp text-sm"></i> Chat Seller
                    </a>

                    <?php if(in_array($p['status'], ['diproses', 'proses', 'sudah_dibayar'])): ?>
                        <a href="lacak_seller.php?id=<?= $p['id'] ?>" class="flex-1 min-w-[120px] bg-blue-500 hover:bg-blue-600 text-white px-3 py-2.5 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all flex items-center justify-center gap-2 shadow-sm">
                            <i class="fas fa-map-marker-alt animate-bounce"></i> Lacak Posisi
                        </a>
                    <?php endif; ?>

                    <?php if($p['status'] == 'menunggu_pembayaran'): ?>
                        <a href="pembayaran.php?kode=<?= $p['kode_pesanan'] ?>" class="flex-1 min-w-[120px] bg-orange hover:bg-[#e65c00] text-white px-3 py-2.5 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all shadow-md flex items-center justify-center gap-2">
                            <i class="fas fa-credit-card"></i> Bayar
                        </a>
                    <?php endif; ?>

                    <?php if($p['status'] == 'selesai'): ?>
                        <?php 
                        $pesanan_id_cek = $p['id'];
                        $cek_review = mysqli_query($conn, "SELECT id FROM ulasan WHERE pesanan_id = '$pesanan_id_cek'");
                        if(mysqli_num_rows($cek_review) == 0):
                        ?>
                            <a href="ulasan.php?id=<?= $p['id'] ?>" class="flex-1 min-w-[120px] bg-yellow-400 hover:bg-yellow-500 text-white px-3 py-2.5 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all flex justify-center items-center gap-2 shadow-sm">
                                <i class="fas fa-star"></i> Ulas Store
                            </a>
                        <?php endif; ?>
                        
                        <a href="komplain.php?id=<?= $p['id'] ?>" class="flex-1 min-w-[120px] text-red-500 hover:text-white bg-red-50 hover:bg-red-500 border border-red-200 hover:border-red-500 px-3 py-2.5 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all flex justify-center items-center gap-2">
                            <i class="fas fa-exclamation-triangle"></i> Komplain
                        </a>
                    <?php endif; ?>

                </div>
            </div>
            
            <?php 
                endwhile; 
            endif;
            ?>
        </div>
    </div>
</div>

<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script>
    AOS.init({ once: true, duration: 600, offset: 50, easing: 'ease-out-cubic' });
</script>

<?php require_once '../includes/footer.php'; ?>