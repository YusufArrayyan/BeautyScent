<?php 
require_once '../config/database.php';
session_start();

// 1. KEAMANAN: Cek Login & Role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'toko') {
    header("Location: ../auth/login.php"); exit();
}

$user_id = $_SESSION['user_id'];
$query_toko = mysqli_query($conn, "SELECT * FROM toko WHERE user_id = '$user_id'");
$toko = mysqli_fetch_assoc($query_toko);
$toko_id = $toko['id'];

require_once '../includes/header.php'; 
?>

<link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">

<div class="bg-[#f4f7fa] min-h-screen pb-24 font-sans text-gray-800">
    
    <!-- HEADER BANNER -->
    <div class="bg-gradient-to-r from-orange to-red-600 py-12 relative overflow-hidden shadow-lg">
        <div class="absolute top-0 right-0 w-64 h-64 bg-white/5 rounded-full blur-[80px] pointer-events-none"></div>
        <div class="max-w-screen-xl mx-auto px-5 md:px-8 relative z-10 flex flex-col md:flex-row justify-between items-center gap-6" data-aos="fade-down">
            <div class="flex items-center gap-5 text-white">
                <div class="w-16 h-16 bg-white/20 backdrop-blur-md rounded-2xl flex items-center justify-center text-3xl shadow-xl transform -rotate-6">
                    <i class="fas fa-rocket"></i>
                </div>
                <div>
                    <h1 class="text-2xl md:text-3xl font-black tracking-tight">Pusat Promosi Store</h1>
                    <p class="text-sm font-medium text-orange-100">Naikkan omzet dengan fitur Iklan Prioritas BeautyScent.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="max-w-screen-xl mx-auto px-5 md:px-8 mt-8 grid grid-cols-1 lg:grid-cols-12 gap-8">
        
        <!-- SIDEBAR FULL -->
        <div class="lg:col-span-3 space-y-4" data-aos="fade-right">
            <div class="bg-white rounded-[2rem] shadow-sm border border-gray-100 p-4 sticky top-28">
                <div class="flex flex-col gap-2">
                    <a href="index.php" class="text-gray-500 hover:bg-gray-50 hover:text-navy flex items-center gap-4 p-4 rounded-2xl font-bold text-sm transition-all group">
                        <div class="w-8 h-8 rounded-full bg-gray-100 group-hover:bg-navy group-hover:text-white flex items-center justify-center transition-colors"><i class="fas fa-list-ul"></i></div> Product Catalog
                    </a>
                    <a href="pesanan.php" class="text-gray-500 hover:bg-gray-50 hover:text-navy flex items-center gap-4 p-4 rounded-2xl font-bold text-sm transition-all group">
                        <div class="w-8 h-8 rounded-full bg-gray-100 group-hover:bg-navy group-hover:text-white flex items-center justify-center transition-colors"><i class="fas fa-shopping-bag"></i></div> Incoming Orders
                    </a>
                    <a href="jadwal.php" class="text-gray-500 hover:bg-gray-50 hover:text-navy flex items-center gap-4 p-4 rounded-2xl font-bold text-sm transition-all group">
                        <div class="w-8 h-8 rounded-full bg-gray-100 group-hover:bg-navy group-hover:text-white flex items-center justify-center transition-colors"><i class="far fa-calendar-check"></i></div> Schedule & Queue
                    </a>
                    <!-- MENU AKTIF -->
                    <a href="promosi.php" class="bg-orange/10 text-orange flex items-center gap-4 p-4 rounded-2xl font-black text-sm transition-all border border-orange/20">
                        <div class="w-8 h-8 rounded-full bg-orange text-white flex items-center justify-center shadow-md"><i class="fas fa-bullhorn"></i></div> Ads & Promotions
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

        <!-- MAIN KONTENT (KANAN) -->
        <div class="lg:col-span-9 space-y-8" data-aos="fade-up" data-aos-delay="100">
            
            <div class="bg-white p-8 rounded-[2.5rem] shadow-sm border border-gray-100 flex flex-col md:flex-row items-center justify-between gap-6">
                <div>
                    <h3 class="text-xl font-black text-navy mb-1">Ad Status Store</h3>
                    <?php if(isset($toko['is_iklan']) && $toko['is_iklan'] == 1): ?>
                        <div class="inline-flex items-center gap-2 bg-green-100 text-green-600 px-4 py-1.5 rounded-full text-xs font-black uppercase tracking-widest mt-2 border border-green-200">
                            <i class="fas fa-check-circle"></i> Iklan Sedang Active
                        </div>
                        <p class="text-xs text-gray-400 font-bold mt-3 uppercase tracking-widest">Berakhir pada: <?= !empty($toko['iklan_expires']) ? date('d M Y', strtotime($toko['iklan_expires'])) : '-' ?></p>
                    <?php else: ?>
                        <div class="inline-flex items-center gap-2 bg-gray-100 text-gray-400 px-4 py-1.5 rounded-full text-xs font-black uppercase tracking-widest mt-2">
                            <i class="fas fa-times-circle"></i> Tidak Ada Ads Active
                        </div>
                    <?php endif; ?>
                </div>
                <div class="text-right">
                    <p class="text-sm text-gray-500 font-medium max-w-xs md:text-right text-center">Store yang beriklan akan muncul di urutan teratas hasil pencarian dan mendapatkan badge <b>"Sponsor"</b>.</p>
                </div>
            </div>

            <h3 class="text-2xl font-black text-navy flex items-center gap-3"><i class="fas fa-fire text-orange"></i> Pilih Paket Iklan</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- PAKET 1 -->
                <div class="bg-white p-8 rounded-[2.5rem] border border-gray-100 hover:border-orange transition-all hover:shadow-xl flex flex-col items-center text-center group">
                    <div class="w-16 h-16 bg-blue-50 text-blue-500 rounded-2xl flex items-center justify-center text-2xl mb-6 group-hover:bg-blue-500 group-hover:text-white transition-colors">
                        <i class="fas fa-paper-plane"></i>
                    </div>
                    <h4 class="font-black text-navy text-lg mb-1">Starter Dash</h4>
                    <p class="text-xs text-gray-400 font-bold uppercase tracking-widest mb-4">Durasi 7 Hari</p>
                    <p class="text-2xl font-black text-orange mb-8">Rp 50.000</p>
                    <button onclick="orderIklan('Starter Dash', 50000)" class="w-full bg-navy text-white py-3 rounded-xl font-black text-[10px] uppercase tracking-widest hover:bg-orange transition-colors mt-auto">Pilih Paket</button>
                </div>

                <!-- PAKET 2 -->
                <div class="bg-white p-8 rounded-[2.5rem] border-2 border-orange shadow-orange/10 shadow-lg flex flex-col items-center text-center relative overflow-hidden group">
                    <div class="absolute top-4 right-[-35px] bg-orange text-white text-[8px] font-black px-10 py-1 rotate-45 uppercase tracking-widest">Best Seller</div>
                    <div class="w-16 h-16 bg-orange text-white rounded-2xl flex items-center justify-center text-2xl mb-6 shadow-lg shadow-orange/30">
                        <i class="fas fa-bolt"></i>
                    </div>
                    <h4 class="font-black text-navy text-lg mb-1">Business Pro</h4>
                    <p class="text-xs text-gray-400 font-bold uppercase tracking-widest mb-4">Durasi 30 Hari</p>
                    <p class="text-2xl font-black text-orange mb-8">Rp 150.000</p>
                    <button onclick="orderIklan('Business Pro', 150000)" class="w-full bg-orange text-white py-3 rounded-xl font-black text-[10px] uppercase tracking-widest hover:bg-navy transition-colors shadow-lg shadow-orange/20 mt-auto">Pilih Paket</button>
                </div>

                <!-- PAKET 3 -->
                <div class="bg-white p-8 rounded-[2.5rem] border border-gray-100 hover:border-orange transition-all hover:shadow-xl flex flex-col items-center text-center group">
                    <div class="w-16 h-16 bg-purple-50 text-purple-500 rounded-2xl flex items-center justify-center text-2xl mb-6 group-hover:bg-purple-500 group-hover:text-white transition-colors">
                        <i class="fas fa-crown"></i>
                    </div>
                    <h4 class="font-black text-navy text-lg mb-1">Market Master</h4>
                    <p class="text-xs text-gray-400 font-bold uppercase tracking-widest mb-4">Durasi 90 Hari</p>
                    <p class="text-2xl font-black text-orange mb-8">Rp 400.000</p>
                    <button onclick="orderIklan('Market Master', 400000)" class="w-full bg-navy text-white py-3 rounded-xl font-black text-[10px] uppercase tracking-widest hover:bg-orange transition-colors mt-auto">Pilih Paket</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MENGATASI HALAMAN NGUMPET -->
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    // INI DIA YANG KETINGGALAN TADI!
    AOS.init({ once: true, duration: 800, offset: 50 });

    function orderIklan(paket, harga) {
        Swal.fire({
            title: 'Konfirmasi Iklan',
            text: "Anda akan memesan paket " + paket + " seharga Rp " + harga.toLocaleString() + ". Lanjutkan ke pembayaran manual via Admin?",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#ff6600',
            cancelButtonColor: '#0a0a2a',
            confirmButtonText: 'Hubungi Admin (WA)'
        }).then((result) => {
            if (result.isConfirmed) {
                window.open('https://wa.me/628123456789?text=Hello%20BeautyScent,%20saya%20ingin%20pasang%20iklan%20toko%20paket%20' + paket + '%20untuk%20toko%20' + '<?= urlencode($toko['nama_toko']) ?>', '_blank');
            }
        })
    }
</script>

<?php require_once '../includes/footer.php'; ?>