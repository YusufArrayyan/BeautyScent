<?php 
require_once '../config/database.php';
session_start();

// 1. KEAMANAN SUPER KETAT: Hanya Admin yang boleh masuk
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

// 2. AMBIL TOTAL OMZET (Hanya pesanan yang statusnya 'selesai')
$omzet_query = mysqli_query($conn, "SELECT SUM(harga) as total FROM pesanan WHERE status = 'selesai'");
$omzet_data = mysqli_fetch_assoc($omzet_query);
$total_omzet = $omzet_data['total'] ? $omzet_data['total'] : 0;

// 3. AMBIL RIWAYAT PESANAN BESERTA NAMA PEMBELI, NAMA LAYANAN & TOKO
$query = mysqli_query($conn, "SELECT p.*, u.nama_lengkap as pembeli, l.nama_layanan, t.nama_toko 
                              FROM pesanan p 
                              JOIN users u ON p.user_id = u.id 
                              JOIN layanan l ON p.layanan_id = l.id 
                              JOIN toko t ON l.toko_id = t.id 
                              ORDER BY p.id DESC");

$total_transaksi = $query ? mysqli_num_rows($query) : 0;

// 4. AMBIL NOTIFIKASI PENDING VERIFIKASI UNTUK SIDEBAR
$q_pending = mysqli_query($conn, "SELECT COUNT(id) as total FROM toko WHERE status_verifikasi = 'pending'");
$tot_pending = mysqli_fetch_assoc($q_pending)['total'] ?? 0;

// Gunakan Header Global
require_once '../includes/header.php'; 
?>

<!-- PUSTAKA ANIMASI -->
<link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">

<!-- CSS KHUSUS UNTUK PRINT (Menyembunyikan elemen yang tidak perlu dicetak) -->
<style>
    @media print {
        body { background-color: white !important; }
        .no-print { display: none !important; }
        .print-shadow-none { box-shadow: none !important; border: 1px solid #eee !important; }
        header, nav, footer { display: none !important; }
        /* Memastikan Sidebar hilang saat di-print dan tabel full width */
        .lg\:col-span-3 { display: none !important; }
        .lg\:col-span-9 { width: 100% !important; max-width: 100% !important; flex: 0 0 100% !important; }
    }
</style>

<div class="bg-[#f4f7fa] min-h-screen pb-24 font-sans text-gray-800">

    <!-- HEADER ADMIN (Disembunyikan saat di-print) -->
    <div class="no-print bg-gradient-to-r from-gray-900 to-black py-12 relative overflow-hidden shadow-lg border-b-4 border-blue-500">
        <div class="absolute top-0 left-0 w-full h-full bg-[url('https://www.transparenttextures.com/patterns/cubes.png')] opacity-10 pointer-events-none"></div>
        <div class="max-w-screen-xl mx-auto px-5 md:px-8 relative z-10 flex flex-col md:flex-row justify-between items-center gap-6" data-aos="fade-down">
            <div class="flex items-center gap-5">
                <div class="w-16 h-16 bg-blue-600 rounded-2xl flex items-center justify-center text-white text-2xl shadow-[0_0_20px_rgba(37,99,235,0.5)]">
                    <i class="fas fa-chart-bar"></i>
                </div>
                <div>
                    <h1 class="text-2xl md:text-3xl font-black text-white tracking-tight">Transaction Reports</h1>
                    <p class="text-sm font-medium text-gray-400">Rekap perputaran uang dan aktivitas pesanan di seluruh sistem.</p>
                </div>
            </div>
            
            <button onclick="window.print()" class="bg-blue-600 hover:bg-blue-500 text-white px-6 py-3.5 rounded-xl font-black text-xs uppercase tracking-widest transition-all shadow-xl shadow-blue-600/30 flex items-center gap-3 transform hover:-translate-y-1">
                <i class="fas fa-print"></i> Print Report
            </button>
        </div>
    </div>

    <div class="max-w-screen-xl mx-auto px-5 md:px-8 mt-8 grid grid-cols-1 lg:grid-cols-12 gap-8">
        
        <!-- SIDEBAR ADMIN (Disembunyikan saat di-print) -->
        <div class="no-print lg:col-span-3 space-y-4" data-aos="fade-right">
            <div class="bg-white rounded-[2rem] shadow-sm border border-gray-100 p-4 sticky top-28">
                <div class="flex flex-col gap-2">
                    <a href="index.php" class="text-gray-500 hover:bg-gray-50 hover:text-blue-600 flex items-center gap-4 p-4 rounded-2xl font-bold text-sm transition-all group">
                        <div class="w-8 h-8 rounded-full bg-gray-100 group-hover:bg-blue-600 group-hover:text-white flex items-center justify-center transition-colors"><i class="fas fa-tachometer-alt"></i></div> Dashboard
                    </a>
                    <a href="verifikasi.php" class="text-gray-500 hover:bg-gray-50 hover:text-blue-600 flex items-center gap-4 p-4 rounded-2xl font-bold text-sm transition-all group">
                        <div class="w-8 h-8 rounded-full bg-gray-100 group-hover:bg-blue-600 group-hover:text-white flex items-center justify-center transition-colors"><i class="fas fa-user-check"></i></div> Store Verification
                        <?php if($tot_pending > 0): ?><span class="ml-auto bg-red text-white text-[10px] px-2 py-0.5 rounded-full animate-pulse shadow-sm"><?= $tot_pending ?></span><?php endif; ?>
                    </a>
                    <a href="users.php" class="text-gray-500 hover:bg-gray-50 hover:text-blue-600 flex items-center gap-4 p-4 rounded-2xl font-bold text-sm transition-all group">
                        <div class="w-8 h-8 rounded-full bg-gray-100 group-hover:bg-blue-600 group-hover:text-white flex items-center justify-center transition-colors"><i class="fas fa-users"></i></div> User Data
                    </a>
                    <a href="kategori.php" class="text-gray-500 hover:bg-gray-50 hover:text-blue-600 flex items-center gap-4 p-4 rounded-2xl font-bold text-sm transition-all group">
                        <div class="w-8 h-8 rounded-full bg-gray-100 group-hover:bg-blue-600 group-hover:text-white flex items-center justify-center transition-colors"><i class="fas fa-tags"></i></div> Manage Categories
                    </a>
                    <a href="produk.php" class="text-gray-500 hover:bg-gray-50 hover:text-blue-600 flex items-center gap-4 p-4 rounded-2xl font-bold text-sm transition-all group">
                        <div class="w-8 h-8 rounded-full bg-gray-100 group-hover:bg-blue-600 group-hover:text-white flex items-center justify-center transition-colors"><i class="fas fa-box"></i></div> Monitor Products
                    </a>
                                        <a href="kelola_iklan.php" class="text-gray-500 hover:bg-gray-50 hover:text-blue-600 flex items-center gap-4 p-4 rounded-2xl font-bold text-sm transition-all group">
                        <div class="w-8 h-8 rounded-full bg-gray-100 group-hover:bg-orange group-hover:text-white flex items-center justify-center transition-colors"><i class="fas fa-bullhorn"></i></div> Manage Ads
                    </a>
                    <!-- Menu Active: Laporan -->
                    <a href="laporan.php" class="bg-blue-50 text-blue-600 flex items-center gap-4 p-4 rounded-2xl font-black text-sm transition-all border border-blue-100">
                        <div class="w-8 h-8 rounded-full bg-blue-600 text-white flex items-center justify-center shadow-md"><i class="fas fa-chart-bar"></i></div> Transaction Reports
                    </a>
                </div>
            </div>
        </div>

        <!-- MAIN KONTEN ADMIN -->
        <div class="lg:col-span-9 space-y-6" data-aos="fade-up" data-aos-delay="100">
            
            <!-- JUDUL KHUSUS PRINT (Hanya muncul di kertas print) -->
            <div class="hidden print:block text-center mb-8">
                <h1 class="text-3xl font-black text-navy uppercase tracking-widest mb-2">Transaction Reports BeautyScent</h1>
                <p class="text-sm font-bold text-gray-500">Dicetak pada: <?= date('d F Y, H:i') ?> WIB</p>
                <hr class="mt-4 border-2 border-navy">
            </div>

            <!-- SUMMARY CARDS -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-2">
                <!-- Card Omzet -->
                <div class="bg-gray-900 p-8 rounded-[2.5rem] text-white relative overflow-hidden shadow-xl print-shadow-none border border-gray-800">
                    <div class="absolute -top-10 -right-10 w-40 h-40 bg-blue-500 rounded-full blur-[60px] opacity-30"></div>
                    <div class="absolute right-8 top-1/2 transform -translate-y-1/2 text-7xl text-white/5"><i class="fas fa-coins"></i></div>
                    <p class="text-[10px] md:text-xs font-black uppercase tracking-widest mb-4 opacity-70 relative z-10">Total Omzet Platform</p>
                    <h2 class="text-3xl md:text-4xl font-black italic tracking-tight relative z-10">Rp <?= number_format($total_omzet, 0, ',', '.') ?></h2>
                    <div class="mt-6 inline-flex items-center gap-2 bg-white/10 backdrop-blur px-3 py-1.5 rounded-lg text-[10px] font-black text-green-400 relative z-10 border border-white/10">
                        <i class="fas fa-arrow-up"></i> Hanya dari pesanan selesai
                    </div>
                </div>
                
                <!-- Card Total Order -->
                <div class="bg-white p-8 rounded-[2.5rem] border border-gray-100 shadow-sm print-shadow-none relative overflow-hidden flex flex-col justify-center">
                    <div class="absolute right-8 top-1/2 transform -translate-y-1/2 text-7xl text-gray-50"><i class="fas fa-shopping-bag"></i></div>
                    <p class="text-[10px] md:text-xs font-black text-gray-400 uppercase tracking-widest mb-4 relative z-10">Total Order History</p>
                    <h2 class="text-3xl md:text-4xl font-black text-navy italic tracking-tight relative z-10"><?= $total_transaksi ?> <span class="text-sm font-bold text-gray-400 not-italic uppercase tracking-widest ml-1">Transaksi</span></h2>
                    <div class="mt-6 inline-flex items-center gap-2 bg-blue-50 px-3 py-1.5 rounded-lg text-[10px] font-black text-blue-600 w-max relative z-10">
                        <i class="fas fa-list-ul"></i> Keseluruhan data sistem
                    </div>
                </div>
            </div>

            <!-- TABEL LAPORAN -->
            <div class="bg-white rounded-[2rem] shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-gray-100 overflow-hidden print-shadow-none">
                <div class="p-6 md:p-8 border-b border-gray-50 flex justify-between items-center bg-gray-50/50 no-print">
                    <div>
                        <h3 class="text-lg font-black text-navy mb-1">Rincian Data Pesanan</h3>
                        <p class="text-xs text-gray-500 font-medium">Laporan mendetail setiap transaksi antara pembeli dan seller.</p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse min-w-[800px]">
                        <thead class="bg-gray-50/80">
                            <tr class="text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-100">
                                <th class="p-6 md:p-8">Kode Pesanan</th>
                                <th class="p-6 md:p-8">Detail Layanan & Store</th>
                                <th class="p-6 md:p-8">Pembeli</th>
                                <th class="p-6 md:p-8">Nominal</th>
                                <th class="p-6 md:p-8 text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50 text-sm">
                            <?php if($total_transaksi > 0): ?>
                                <?php while($p = mysqli_fetch_assoc($query)): ?>
                                <tr class="hover:bg-blue-50/30 transition duration-300">
                                    <td class="p-6 md:p-8">
                                        <div class="inline-flex items-center gap-2 bg-gray-50 border border-gray-200 px-3 py-1.5 rounded-lg text-[11px] font-black text-navy uppercase tracking-widest">
                                            <i class="fas fa-hashtag text-gray-400"></i> ELG-<?= str_pad($p['id'], 5, '0', STR_PAD_LEFT) ?>
                                        </div>
                                        <p class="text-[10px] text-gray-400 font-bold mt-2 ml-1"><?= isset($p['created_at']) ? date('d M Y, H:i', strtotime($p['created_at'])) : 'Data terekam' ?></p>
                                    </td>
                                    
                                    <td class="p-6 md:p-8">
                                        <p class="font-black text-navy mb-1"><?= htmlspecialchars($p['nama_layanan']) ?></p>
                                        <p class="text-[10px] font-black text-blue-600 uppercase tracking-widest"><i class="fas fa-store mr-1"></i> <?= htmlspecialchars($p['nama_toko']) ?></p>
                                    </td>
                                    
                                    <td class="p-6 md:p-8">
                                        <p class="font-bold text-gray-600"><i class="fas fa-user-circle text-gray-300 mr-1.5"></i> <?= htmlspecialchars($p['pembeli']) ?></p>
                                    </td>
                                    
                                    <td class="p-6 md:p-8">
                                        <p class="font-black text-navy italic text-lg">Rp <?= number_format($p['harga'], 0, ',', '.') ?></p>
                                    </td>
                                    
                                    <td class="p-6 md:p-8 text-center">
                                        <?php 
                                            $status_bg = "bg-yellow-100 text-yellow-700 border-yellow-200";
                                            $icon_status = "fa-clock";
                                            
                                            if($p['status'] == 'diproses' || $p['status'] == 'proses') { 
                                                $status_bg = "bg-blue-100 text-blue-700 border-blue-200"; 
                                                $icon_status = "fa-image";
                                            } elseif($p['status'] == 'selesai') { 
                                                $status_bg = "bg-green-100 text-green-700 border-green-200"; 
                                                $icon_status = "fa-check-double";
                                            } elseif($p['status'] == 'batal') { 
                                                $status_bg = "bg-red-100 text-red-700 border-red-200"; 
                                                $icon_status = "fa-times-circle";
                                            }
                                        ?>
                                        <span class="px-4 py-1.5 rounded-xl text-[9px] font-black uppercase tracking-widest border <?= $status_bg ?> flex items-center justify-center gap-1.5 w-max mx-auto">
                                            <i class="fas <?= $icon_status ?>"></i> <?= $p['status'] ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="p-16 text-center">
                                        <div class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center text-gray-300 text-2xl mx-auto mb-4"><i class="fas fa-receipt"></i></div>
                                        <p class="text-navy font-black text-lg">No Transactions Yet</p>
                                        <p class="text-gray-400 text-xs font-bold">Data pesanan dari seluruh seller akan muncul di sini.</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>

<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script>
    AOS.init({ once: true, duration: 800, offset: 50 });
</script>

<?php require_once '../includes/footer.php'; ?>