<?php
// PASTIKAN TIDAK ADA SPASI KOSONG SEBELUM TAG PHP INI
require_once '../config/database.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. KEAMANAN: Cek Login & Role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'toko') {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// 2. AMBIL ID TOKO
$query_toko = mysqli_query($conn, "SELECT id, nama_toko, status_verifikasi FROM toko WHERE user_id = '$user_id'");
$toko = mysqli_fetch_assoc($query_toko);
$toko_id = $toko['id'];

// 3. LOGIKA SMART ACTION BUTTONS (UPDATE STATUS)
if (isset($_POST['update_status'])) {
    $id_pesanan = mysqli_real_escape_string($conn, $_POST['id_pesanan']);
    $status_baru = mysqli_real_escape_string($conn, $_POST['status_baru']);

    // Update status (Pastikan pesanan ini benar-benar milik toko ini)
    $query_update = "UPDATE pesanan SET status = '$status_baru' WHERE id = '$id_pesanan' AND toko_id = '$toko_id'";
    
    if (mysqli_query($conn, $query_update)) {
        $teks_status = "";
        if($status_baru == 'diproses') $teks_status = "Order accepted! You may start processing.";
        elseif($status_baru == 'selesai') $teks_status = "Great work! Order has been completed.";
        elseif($status_baru == 'batal') $teks_status = "Order has been cancelled.";
        
        $_SESSION['pesan_sukses'] = $teks_status;
    } else {
        $_SESSION['pesan_error'] = "Failed to update order status.";
    }
    header("Location: pesanan.php");
    exit();
}

// 4. AMBIL DATA PESANAN + PEMBELI + LAYANAN
$query_pesanan = mysqli_query($conn, "SELECT p.*, u.id as id_pembeli, u.nama_lengkap as nama_pembeli, u.no_hp as wa_pembeli, l.nama_layanan 
                                      FROM pesanan p 
                                      JOIN users u ON p.user_id = u.id 
                                      LEFT JOIN layanan l ON p.layanan_id = l.id
                                      WHERE p.toko_id = '$toko_id' 
                                      ORDER BY p.id DESC");
$total_pesanan = mysqli_num_rows($query_pesanan);

require_once '../includes/header.php'; 
?>

<link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">

<div class="bg-[#f4f7fa] min-h-screen pb-24 font-sans text-gray-800">

    <!-- HERO DASHBOARD BANNER -->
    <div class="bg-gradient-to-r from-navy to-[#111144] py-12 relative overflow-hidden shadow-lg">
        <div class="absolute top-0 right-0 w-64 h-64 bg-white/5 rounded-full blur-[80px] pointer-events-none"></div>
        <div class="max-w-screen-xl mx-auto px-5 md:px-8 relative z-10 flex flex-col md:flex-row justify-between items-center gap-6" data-aos="fade-down">
            <div class="flex items-center gap-5">
                <div class="w-16 h-16 bg-gradient-to-br from-orange to-red-500 rounded-2xl flex items-center justify-center text-white text-2xl shadow-xl transform -rotate-6">
                    <i class="fas fa-clipboard-list"></i>
                </div>
                <div>
                    <h1 class="text-2xl md:text-3xl font-black text-white tracking-tight">Incoming Orders</h1>
                    <p class="text-sm font-medium text-gray-400">Manage and fulfill customer orders.</p>
                </div>
            </div>
            
            <div class="flex gap-4">
                <a href="<?= $base_url ?>/detail_toko.php?id=<?= $toko_id ?>" target="_blank" class="bg-white/10 hover:bg-white/20 border border-white/20 text-white px-5 py-2.5 rounded-xl text-xs font-black uppercase tracking-widest transition-all backdrop-blur-sm flex items-center gap-2 shadow-sm hover:shadow-md">
                    <i class="far fa-eye"></i> View Store
                </a>
            </div>
        </div>
    </div>

    <div class="max-w-screen-xl mx-auto px-5 md:px-8 mt-8 grid grid-cols-1 lg:grid-cols-12 gap-8">
        
        <!-- SIDEBAR MENU (KIRI) -->
        <div class="lg:col-span-3 space-y-4" data-aos="fade-right">
            <div class="bg-white rounded-[2rem] shadow-sm border border-gray-100 p-4 sticky top-28">
                <div class="flex flex-col gap-2">
                    <a href="index.php" class="text-gray-500 hover:bg-gray-50 hover:text-navy flex items-center gap-4 p-4 rounded-2xl font-bold text-sm transition-all group">
                        <div class="w-8 h-8 rounded-full bg-gray-100 group-hover:bg-navy group-hover:text-white flex items-center justify-center transition-colors"><i class="fas fa-list-ul"></i></div> Product Catalog
                    </a>
                    <a href="pesanan.php" class="bg-orange/10 text-orange flex items-center gap-4 p-4 rounded-2xl font-black text-sm transition-all border border-orange/20">
                        <div class="w-8 h-8 rounded-full bg-orange text-white flex items-center justify-center shadow-md"><i class="fas fa-shopping-bag"></i></div> Incoming Orders <span class="ml-auto bg-red text-white text-[10px] px-2 py-0.5 rounded-full animate-pulse shadow-sm"><?= $total_pesanan ?></span>
                    </a>
                    <a href="jadwal.php" class="text-gray-500 hover:bg-gray-50 hover:text-navy flex items-center gap-4 p-4 rounded-2xl font-bold text-sm transition-all group">
                        <div class="w-8 h-8 rounded-full bg-gray-100 group-hover:bg-navy group-hover:text-white flex items-center justify-center transition-colors"><i class="far fa-calendar-check"></i></div> Schedule & Queue
                    </a>
                    <a href="promosi.php" class="text-gray-500 hover:bg-gray-50 hover:text-navy flex items-center gap-4 p-4 rounded-2xl font-bold text-sm transition-all group">
                        <div class="w-8 h-8 rounded-full bg-gray-100 group-hover:bg-navy group-hover:text-white flex items-center justify-center transition-colors"><i class="fas fa-bullhorn"></i></div> Ads & Promotions
                    </a>
                    <a href="profil_toko.php" class="text-gray-500 hover:bg-gray-50 hover:text-navy flex items-center gap-4 p-4 rounded-2xl font-bold text-sm transition-all group">
                        <div class="w-8 h-8 rounded-full bg-gray-100 group-hover:bg-navy group-hover:text-white flex items-center justify-center transition-colors"><i class="fas fa-id-card"></i></div> Store Profile
                    </a>
                    <a href="pengaturan_maps.php" class="text-gray-500 hover:bg-gray-50 hover:text-navy flex items-center gap-4 p-4 rounded-2xl font-bold text-sm transition-all group">
                        <div class="w-8 h-8 rounded-full bg-gray-100 group-hover:bg-navy group-hover:text-white flex items-center justify-center transition-colors"><i class="fas fa-map-marked-alt"></i></div> Map Settings
                    </a>
                    <a href="keuangan.php" class="text-gray-500 hover:bg-gray-50 hover:text-navy flex items-center gap-4 p-4 rounded-2xl font-bold text-sm transition-all group">
                        <div class="w-8 h-8 rounded-full bg-gray-100 group-hover:bg-navy group-hover:text-white flex items-center justify-center transition-colors"><i class="fas fa-wallet"></i></div> Finances
                    </a>
                </div>
            </div>
        </div>

        <!-- MAIN KONTEN (KANAN) -->
        <div class="lg:col-span-9 space-y-6" data-aos="fade-up" data-aos-delay="100">

            <!-- ALERT VERIFIKASI PENDING -->
            <?php if($toko['status_verifikasi'] === 'pending'): ?>
            <div class="bg-yellow-50 border-2 border-yellow-400 p-6 rounded-[2rem] flex flex-col sm:flex-row items-center gap-5 shadow-lg shadow-yellow-500/10">
                <div class="w-16 h-16 bg-yellow-400 rounded-full flex items-center justify-center text-white text-3xl shrink-0 animate-bounce shadow-md">
                    <i class="fas fa-user-clock"></i>
                </div>
                <div>
                    <h3 class="text-yellow-800 font-black text-lg mb-1 tracking-tight">Awaiting Admin Verification</h3>
                    <p class="text-yellow-700 text-sm font-medium">Your store has been created but is <b>not yet visible to the public</b>. Our admin team is reviewing your registration.</p>
                </div>
            </div>
            <?php endif; ?>

            <!-- ALERT NOTIFIKASI SUKSES/ERROR -->
            <?php if (isset($_SESSION['pesan_sukses'])): ?>
                <div class="bg-green-50 border border-green-200 text-green-700 px-6 py-4 rounded-2xl text-sm font-bold flex items-center gap-3 shadow-sm">
                    <i class="fas fa-check-circle text-xl"></i> <?= $_SESSION['pesan_sukses']; unset($_SESSION['pesan_sukses']); ?>
                </div>
            <?php endif; ?>
            <?php if (isset($_SESSION['pesan_error'])): ?>
                <div class="bg-red-50 border border-red-200 text-red-700 px-6 py-4 rounded-2xl text-sm font-bold flex items-center gap-3 shadow-sm">
                    <i class="fas fa-exclamation-circle text-xl"></i> <?= $_SESSION['pesan_error']; unset($_SESSION['pesan_error']); ?>
                </div>
            <?php endif; ?>

            <?php if($total_pesanan > 0): ?>
                <div class="space-y-6">
                    <?php 
                    while($p = mysqli_fetch_assoc($query_pesanan)): 
                        // Tentukan Warna Badge Berdasarkan Status
                        $badge_bg = 'bg-gray-100 text-gray-700 border-gray-200';
                        $icon_status = 'fa-clock';
                        if($p['status'] == 'menunggu_pembayaran') {
                            $badge_bg = 'bg-yellow-100 text-yellow-700 border-yellow-200'; $icon_status = 'fa-hourglass-half';
                        }
                        elseif($p['status'] == 'sudah_dibayar') {
                            $badge_bg = 'bg-purple-100 text-purple-700 border-purple-200'; $icon_status = 'fa-money-check-alt';
                        }
                        elseif($p['status'] == 'diproses') { 
                            $badge_bg = 'bg-blue-100 text-blue-700 border-blue-200'; $icon_status = 'fa-image animate-spin-slow';
                        }
                        elseif($p['status'] == 'selesai') { 
                            $badge_bg = 'bg-green-100 text-green-700 border-green-200'; $icon_status = 'fa-check-double';
                        }
                        elseif(in_array($p['status'], ['batal', 'dibatalkan'])) { 
                            $badge_bg = 'bg-red-100 text-red-700 border-red-200'; $icon_status = 'fa-times-circle';
                        }
                        
                        $nama_layanan_tampil = !empty($p['nama_layanan']) ? $p['nama_layanan'] : "Product Removed";
                        $total_biaya = !empty($p['total_harga']) ? $p['total_harga'] : ($p['harga'] * $p['jumlah']);
                    ?>
                    <!-- KARTU PESANAN SMART -->
                    <div class="bg-white rounded-[2rem] p-6 md:p-8 shadow-sm border border-gray-100 flex flex-col lg:flex-row gap-8 hover:shadow-xl hover:border-orange/30 transition-all duration-300">
                        
                        <!-- Info Order Dasar -->
                        <div class="flex-1 space-y-4">
                            <div class="flex flex-wrap items-center gap-3 mb-2">
                                <span class="bg-gray-50 text-gray-500 px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest border border-gray-200">
                                    <i class="fas fa-hashtag"></i> ORD-<?= str_pad($p['id'], 5, '0', STR_PAD_LEFT) ?>
                                </span>
                                <span class="<?= $badge_bg ?> border px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest flex items-center gap-1.5 shadow-sm">
                                    <i class="fas <?= $icon_status ?>"></i> 
                                    <?= str_replace('_', ' ', htmlspecialchars($p['status'])) ?>
                                </span>
                                <span class="text-[10px] text-gray-400 font-bold uppercase tracking-widest flex items-center gap-1.5 ml-auto md:ml-0 border border-gray-100 px-3 py-1 rounded-full">
                                    <i class="fas fa-calendar-alt text-orange"></i> <?= date('d M Y, H:i', strtotime($p['tanggal_layanan'] . ' ' . $p['waktu_layanan'])) ?> WIB
                                </span>
                            </div>
                            
                            <h3 class="text-xl md:text-2xl font-black text-navy leading-tight"><?= htmlspecialchars($nama_layanan_tampil) ?></h3>
                            <p class="text-2xl md:text-3xl font-black text-orange italic">Rp <?= number_format($total_biaya, 0, ',', '.') ?></p>
                            
                            <div class="bg-gray-50 p-4 rounded-2xl border border-gray-100 flex items-center gap-4 mt-4">
                                <div class="w-12 h-12 bg-white rounded-full flex items-center justify-center text-gray-400 shadow-sm border border-gray-100 shrink-0 text-xl"><i class="fas fa-user"></i></div>
                                <div class="flex-1">
                                    <p class="text-[9px] uppercase font-black tracking-widest text-gray-400 mb-0.5">Customer</p>
                                    <p class="text-sm font-bold text-navy"><?= htmlspecialchars($p['nama_pembeli']) ?></p>
                                </div>
                                
                                <!-- TOMBOL CHAT PEMBELI INTERNAL -->
                                <a href="../chat.php?uid=<?= $p['id_pembeli'] ?>" class="w-10 h-10 bg-blue-100 hover:bg-blue-600 text-blue-600 hover:text-white rounded-full flex items-center justify-center transition-colors shadow-sm shrink-0">
                                    <i class="fas fa-comments"></i>
                                </a>
                                <!-- TOMBOL WHATSAPP CADANGAN -->
                                <a href="https://wa.me/<?= htmlspecialchars($p['wa_pembeli']) ?>" target="_blank" class="w-10 h-10 bg-[#25D366]/10 hover:bg-[#25D366] text-[#25D366] hover:text-white rounded-full flex items-center justify-center transition-colors shadow-sm shrink-0">
                                    <i class="fab fa-whatsapp"></i>
                                </a>
                            </div>
                        </div>

                        <!-- Action Area Cerdas (Berdasarkan Status) -->
                        <div class="w-full lg:w-64 shrink-0 flex flex-col justify-center space-y-3 lg:border-l lg:border-gray-100 lg:pl-8">
                            
                            <?php if($p['status'] == 'menunggu_pembayaran'): ?>
                                <div class="bg-yellow-50 text-yellow-600 p-4 rounded-xl text-center border border-yellow-200">
                                    <i class="fas fa-hourglass-half text-2xl mb-2 animate-pulse"></i>
                                    <p class="text-[10px] font-black uppercase tracking-widest">Waiting for Customer Payment</p>
                                </div>

                            <?php elseif($p['status'] == 'sudah_dibayar'): ?>
                                <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest text-center mb-1">Order Actions</p>
                                <!-- Tombol Terima -->
                                <form action="" method="POST">
                                    <input type="hidden" name="id_pesanan" value="<?= $p['id'] ?>">
                                    <input type="hidden" name="status_baru" value="diproses">
                                    <button type="submit" name="update_status" class="w-full bg-navy hover:bg-orange text-white py-3.5 rounded-xl font-black text-[10px] md:text-xs uppercase tracking-widest shadow-lg shadow-navy/20 hover:shadow-orange/30 flex items-center justify-center gap-2 transition transform hover:-translate-y-1">
                                        <i class="fas fa-check-circle text-lg"></i> Accept & Process
                                    </button>
                                </form>
                                <!-- Tombol Tolak -->
                                <form action="" method="POST">
                                    <input type="hidden" name="id_pesanan" value="<?= $p['id'] ?>">
                                    <input type="hidden" name="status_baru" value="batal">
                                    <button type="submit" name="update_status" onclick="return confirm('Are you sure you want to reject this order?')" class="w-full bg-red-50 hover:bg-red text-red-500 hover:text-white py-3 rounded-xl font-black text-[10px] md:text-xs uppercase tracking-widest flex items-center justify-center gap-2 transition border border-red-200">
                                        <i class="fas fa-times"></i> Reject Order
                                    </button>
                                </form>

                            <?php elseif($p['status'] == 'diproses'): ?>
                                <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest text-center mb-1">Processing Stage</p>
                                
                                <!-- TOMBOL SAKTI: MENUJU LOKASI PEMBELI -->
                                <a href="lacak_pembeli.php?id=<?= $p['id'] ?>" class="w-full bg-blue-500 hover:bg-blue-600 text-white py-3.5 rounded-xl font-black text-[10px] md:text-xs uppercase tracking-widest shadow-lg shadow-blue-500/30 flex items-center justify-center gap-2 transition transform hover:-translate-y-1">
                                    <i class="fas fa-motorcycle animate-bounce text-lg"></i> Start Delivery
                                </a>

                                <!-- Tombol Selesai -->
                                <form action="" method="POST" class="mt-2">
                                    <input type="hidden" name="id_pesanan" value="<?= $p['id'] ?>">
                                    <input type="hidden" name="status_baru" value="selesai">
                                    <button type="submit" name="update_status" onclick="return confirm('Has the order been fully completed?')" class="w-full bg-green-500 hover:bg-green-600 text-white py-3.5 rounded-xl font-black text-[10px] md:text-xs uppercase tracking-widest shadow-md flex items-center justify-center gap-2 transition transform hover:-translate-y-1">
                                        <i class="fas fa-check-double text-lg"></i> Mark as Complete
                                    </button>
                                </form>

                            <?php elseif($p['status'] == 'selesai'): ?>
                                <div class="bg-green-50 text-green-600 p-4 rounded-xl text-center border border-green-200">
                                    <i class="fas fa-medal text-3xl mb-2"></i>
                                    <p class="text-[10px] font-black uppercase tracking-widest">Great Job! Completed</p>
                                </div>
                            
                            <?php else: ?>
                                <div class="bg-red-50 text-red-500 p-4 rounded-xl text-center border border-red-200">
                                    <i class="fas fa-ban text-3xl mb-2"></i>
                                    <p class="text-[10px] font-black uppercase tracking-widest">Order Cancelled</p>
                                </div>
                            <?php endif; ?>

                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <!-- EMPTY STATE -->
                <div class="bg-white rounded-[2rem] border-2 border-dashed border-gray-200 py-20 px-6 flex flex-col items-center justify-center text-center">
                    <div class="w-24 h-24 bg-gray-50 rounded-full flex items-center justify-center text-gray-300 text-5xl mb-6 relative">
                        <i class="fas fa-box-open"></i>
                        <div class="absolute -top-2 -right-2 bg-red w-6 h-6 rounded-full flex items-center justify-center text-white text-xs animate-ping"></div>
                    </div>
                    <h3 class="text-xl font-black text-navy mb-2">No Orders Yet</h3>
                    <p class="text-sm text-gray-500 font-medium max-w-sm">No customers have placed orders yet. Stay ready and provide the best service!</p>
                </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script>
    AOS.init({ once: true, duration: 800, offset: 50 });
</script>

<?php require_once '../includes/footer.php'; ?>