<?php 
require_once '../config/database.php';
session_start();

// 1. KEAMANAN: Cek Login & Role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'toko') {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// 2. AMBIL DATA TOKO MILIK USER INI
$query_toko = mysqli_query($conn, "SELECT id, nama_toko FROM toko WHERE user_id = '$user_id'");
$toko = mysqli_fetch_assoc($query_toko);

if (!$toko) {
    die("Data toko tidak ditemukan. Silakan hubungi admin.");
}
$toko_id = $toko['id'];

// 3. AMBIL DATA ANTREAN PESANAN (Hanya yang Active)
// Diurutkan berdasarkan Tanggal dan Waktu paling dekat
$query_jadwal = mysqli_query($conn, "
    SELECT p.*, u.nama_lengkap as nama_pembeli, u.no_hp as wa_pembeli, l.nama_layanan 
    FROM pesanan p 
    JOIN users u ON p.user_id = u.id 
    LEFT JOIN layanan l ON p.layanan_id = l.id
    WHERE p.toko_id = '$toko_id' AND p.status IN ('sudah_dibayar', 'diproses') 
    ORDER BY p.tanggal_layanan ASC, p.waktu_layanan ASC
");

// Kelompokkan jadwal berdasarkan Tanggal (Hari ini, Besok, Lusa, dst)
$jadwal_group = [];
$today = date('Y-m-d');
$tomorrow = date('Y-m-d', strtotime('+1 day'));

while ($row = mysqli_fetch_assoc($query_jadwal)) {
    // [PERBAIKAN ERROR NULL] Beri nilai default hari ini jika tanggal di DB kosong (NULL)
    $tgl = !empty($row['tanggal_layanan']) ? $row['tanggal_layanan'] : $today;
    
    // Format Label Tanggal
    if ($tgl == $today) {
        $label = "HARI INI (" . date('d M Y', strtotime($tgl)) . ")";
    } elseif ($tgl == $tomorrow) {
        $label = "BESOK (" . date('d M Y', strtotime($tgl)) . ")";
    } else {
        $label = date('l, d M Y', strtotime($tgl));
        // Translate hari ke Bahasa Indonesia
        $label = str_replace(
            ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'],
            ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'],
            $label
        );
    }
    
    $jadwal_group[$label][] = $row;
}

// Panggil Header
require_once '../includes/header.php'; 
?>

<!-- PUSTAKA ANIMASI -->
<link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">

<div class="bg-[#f4f7fa] min-h-screen pb-24 font-sans text-gray-800">

    <!-- HERO DASHBOARD BANNER -->
    <div class="bg-gradient-to-r from-navy to-[#111144] py-12 relative overflow-hidden shadow-lg">
        <div class="absolute top-0 right-0 w-64 h-64 bg-white/5 rounded-full blur-[80px] pointer-events-none"></div>
        <div class="max-w-screen-xl mx-auto px-5 md:px-8 relative z-10 flex flex-col md:flex-row justify-between items-center gap-6" data-aos="fade-down">
            <div class="flex items-center gap-5">
                <div class="w-16 h-16 bg-gradient-to-br from-purple-500 to-indigo-600 rounded-2xl flex items-center justify-center text-white text-2xl shadow-xl transform -rotate-6">
                    <i class="far fa-calendar-alt"></i>
                </div>
                <div>
                    <h1 class="text-2xl md:text-3xl font-black text-white tracking-tight">Manajemen Schedule & Queue</h1>
                    <p class="text-sm font-medium text-gray-400">Monitor your schedule and manage your time efficiently.</p>
                </div>
            </div>
            
            <div class="flex gap-4">
                <a href="pesanan.php" class="bg-white/10 hover:bg-white/20 border border-white/20 text-white px-5 py-2.5 rounded-xl text-xs font-black uppercase tracking-widest transition-all backdrop-blur-sm flex items-center gap-2 shadow-sm hover:shadow-md">
                    <i class="fas fa-clipboard-list"></i> Lihat Pesanan
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
                        <div class="w-8 h-8 rounded-full bg-gray-100 group-hover:bg-navy group-hover:text-white flex items-center justify-center transition-colors"><i class="fas fa-list-ul"></i></div>
                        Product Catalog
                    </a>
                    <a href="pesanan.php" class="text-gray-500 hover:bg-gray-50 hover:text-navy flex items-center gap-4 p-4 rounded-2xl font-bold text-sm transition-all group">
                        <div class="w-8 h-8 rounded-full bg-gray-100 group-hover:bg-navy group-hover:text-white flex items-center justify-center transition-colors"><i class="fas fa-shopping-bag"></i></div>
                        Incoming Orders
                    </a>
                    <a href="jadwal.php" class="bg-orange/10 text-orange flex items-center gap-4 p-4 rounded-2xl font-black text-sm transition-all border border-orange/20">
                        <div class="w-8 h-8 rounded-full bg-orange text-white flex items-center justify-center shadow-md"><i class="far fa-calendar-check"></i></div>
                        Schedule & Queue
                    </a>
                    <a href="promosi.php" class="text-gray-500 hover:bg-gray-50 hover:text-navy flex items-center gap-4 p-4 rounded-2xl font-bold text-sm transition-all group">
                        <div class="w-8 h-8 rounded-full bg-gray-100 group-hover:bg-navy group-hover:text-white flex items-center justify-center transition-colors"><i class="fas fa-bullhorn"></i></div>
                        Ads & Promotions
                    </a>
                    <a href="profil_toko.php" class="text-gray-500 hover:bg-gray-50 hover:text-navy flex items-center gap-4 p-4 rounded-2xl font-bold text-sm transition-all group">
                        <div class="w-8 h-8 rounded-full bg-gray-100 group-hover:bg-navy group-hover:text-white flex items-center justify-center transition-colors"><i class="fas fa-id-card"></i></div>
                        Store Profile
                    </a>
                    <a href="pengaturan_maps.php" class="text-gray-500 hover:bg-gray-50 hover:text-navy flex items-center gap-4 p-4 rounded-2xl font-bold text-sm transition-all group">
                        <div class="w-8 h-8 rounded-full bg-gray-100 group-hover:bg-navy group-hover:text-white flex items-center justify-center transition-colors"><i class="fas fa-map-marked-alt"></i></div>
                        Map Settings
                    </a>
                    <a href="keuangan.php" class="text-gray-500 hover:bg-gray-50 hover:text-navy flex items-center gap-4 p-4 rounded-2xl font-bold text-sm transition-all group">
                        <div class="w-8 h-8 rounded-full bg-gray-100 group-hover:bg-navy group-hover:text-white flex items-center justify-center transition-colors"><i class="fas fa-wallet"></i></div>
                        Finances
                    </a>
                </div>
            </div>
        </div>

        <!-- MAIN KONTEN: TIMELINE JADWAL (KANAN) -->
        <div class="lg:col-span-9 space-y-8" data-aos="fade-up" data-aos-delay="100">

            <?php if(empty($jadwal_group)): ?>
                <!-- EMPTY STATE JADWAL -->
                <div class="bg-white rounded-[2.5rem] border-2 border-dashed border-gray-200 py-24 px-6 flex flex-col items-center justify-center text-center shadow-sm">
                    <div class="w-24 h-24 bg-purple-50 rounded-full flex items-center justify-center text-purple-300 text-5xl mb-6 relative">
                        <i class="far fa-calendar-times"></i>
                    </div>
                    <h3 class="text-xl font-black text-navy mb-2">Jadwal Anda Masih Kosong</h3>
                    <p class="text-sm text-gray-500 font-medium max-w-sm">Anda tidak memiliki jadwal antrean pekerjaan yang aktif saat ini. Waktunya istirahat atau sebar promo!</p>
                </div>
            <?php else: ?>
                
                <!-- TIMELINE JADWAL -->
                <div class="bg-white p-8 md:p-10 rounded-[2.5rem] shadow-sm border border-gray-100">
                    <div class="relative border-l-4 border-gray-100 ml-4 md:ml-6 space-y-12">
                        
                        <?php foreach($jadwal_group as $tanggal => $jadwals): ?>
                            <!-- Grup Tanggal -->
                            <div class="relative">
                                <!-- Node Tanda Waktu -->
                                <div class="absolute -left-[30px] md:-left-[34px] w-14 h-14 bg-orange rounded-full border-4 border-white shadow-md flex items-center justify-center text-white text-xl z-10 ring-4 ring-orange/20">
                                    <i class="far fa-clock"></i>
                                </div>
                                
                                <h3 class="text-xl font-black text-navy ml-10 md:ml-12 pt-3 mb-6"><?= $tanggal ?></h3>
                                
                                <div class="ml-10 md:ml-12 space-y-6">
                                    <?php foreach($jadwals as $j): 
                                        $nama_layanan_tampil = !empty($j['nama_layanan']) ? $j['nama_layanan'] : "Product Removed";
                                        
                                        // Styling Badge Status
                                        if($j['status'] == 'diproses') { 
                                            $badge_bg = 'bg-blue-100 text-blue-700 border-blue-200'; $icon_status = 'fa-motorcycle animate-bounce';
                                        } else {
                                            $badge_bg = 'bg-purple-100 text-purple-700 border-purple-200'; $icon_status = 'fa-stopwatch';
                                        }
                                    ?>
                                    <!-- KARTU JADWAL -->
                                    <div class="bg-gray-50/80 p-5 rounded-2xl border border-gray-200 hover:shadow-lg hover:-translate-y-1 hover:border-orange/30 transition-all duration-300 relative overflow-hidden group">
                                        
                                        <!-- Waktu Pojok Kanan Atas (DIPERBAIKI ANTI ERROR) -->
                                        <div class="absolute top-0 right-0 bg-navy text-white px-4 py-2 rounded-bl-2xl font-black text-[10px] md:text-sm shadow-sm">
                                            <?= !empty($j['waktu_layanan']) ? date('H:i', strtotime($j['waktu_layanan'])) . ' WIB' : 'FLEKSIBEL' ?>
                                        </div>

                                        <div class="pr-24 md:pr-20">
                                            <div class="flex flex-wrap items-center gap-2 mb-3">
                                                <span class="bg-white text-gray-500 px-3 py-1 rounded-full text-[9px] font-black uppercase tracking-widest border border-gray-200 shadow-sm">
                                                    ORD-<?= str_pad($j['id'], 5, '0', STR_PAD_LEFT) ?>
                                                </span>
                                                <span class="<?= $badge_bg ?> border px-3 py-1 rounded-full text-[9px] font-black uppercase tracking-widest flex items-center gap-1.5 shadow-sm">
                                                    <i class="fas <?= $icon_status ?>"></i> <?= str_replace('_', ' ', htmlspecialchars($j['status'])) ?>
                                                </span>
                                            </div>
                                            
                                            <h4 class="text-lg font-black text-navy mb-1 group-hover:text-orange transition-colors"><?= htmlspecialchars($nama_layanan_tampil) ?></h4>
                                            <p class="text-sm font-bold text-gray-500 flex items-center gap-2 mb-4">
                                                <i class="fas fa-user-circle text-gray-400"></i> Customer: <?= htmlspecialchars($j['nama_pembeli']) ?>
                                            </p>
                                        </div>
                                        
                                        <div class="flex flex-col sm:flex-row gap-3 pt-4 border-t border-gray-200 mt-auto">
                                            <a href="../chat.php?uid=<?= $j['user_id'] ?>" class="flex-1 bg-white hover:bg-blue-50 text-blue-600 border border-blue-200 py-2.5 rounded-xl font-black text-[10px] uppercase tracking-widest flex items-center justify-center gap-2 transition-colors">
                                                <i class="fas fa-comments"></i> Hubungi
                                            </a>
                                            <!-- Tombol Jalan Arahkan ke Halaman Pesanan agar bisa diubah statusnya -->
                                            <a href="pesanan.php" class="flex-1 bg-navy hover:bg-orange text-white py-2.5 rounded-xl font-black text-[10px] uppercase tracking-widest flex items-center justify-center gap-2 transition-colors shadow-md">
                                                <i class="fas fa-external-link-alt"></i> Proses Pesanan
                                            </a>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>

                    </div>
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