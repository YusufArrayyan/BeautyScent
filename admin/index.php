<?php 
session_start(); // Dipindah ke paling atas untuk mencegah error "headers already sent"
require_once '../config/database.php';

// 1. KEAMANAN SUPER KETAT: Hanya Admin yang boleh masuk
// strtolower() digunakan agar 'ADMIN', 'Admin', atau 'admin' semuanya valid
if (!isset($_SESSION['user_id']) || strtolower($_SESSION['role']) !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// 2. MENGAMBIL DATA STATISTIK REAL-TIME DARI DATABASE
$query_users = mysqli_query($conn, "SELECT COUNT(id) as total FROM users WHERE role = 'user' OR role = 'pembeli'");
$total_users = mysqli_fetch_assoc($query_users)['total'] ?? 0;

$query_seller = mysqli_query($conn, "SELECT COUNT(id) as total FROM toko WHERE status_verifikasi = 'verified'");
$total_seller = mysqli_fetch_assoc($query_seller)['total'] ?? 0;

$query_pending = mysqli_query($conn, "SELECT COUNT(id) as total FROM toko WHERE status_verifikasi = 'pending'");
$tot_pending = mysqli_fetch_assoc($query_pending)['total'] ?? 0;

$query_uang = mysqli_query($conn, "SELECT SUM(harga) as total_uang FROM pesanan WHERE status = 'selesai'");
$total_uang = mysqli_fetch_assoc($query_uang)['total_uang'] ?? 0; 

// 3. LOGIKA VERIFIKASI / TOLAK TOKO LANGSUNG DARI DASHBOARD
if (isset($_POST['aksi_verifikasi'])) {
    $toko_id = mysqli_real_escape_string($conn, $_POST['toko_id']);
    $aksi = $_POST['aksi_verifikasi']; // 'verified' atau 'ditolak'

    if ($aksi == 'ditolak') {
        mysqli_query($conn, "DELETE FROM toko WHERE id = '$toko_id'");
        $_SESSION['pesan_sukses'] = "Store registration rejected and removed.";
    } else {
        mysqli_query($conn, "UPDATE toko SET status_verifikasi = 'verified' WHERE id = '$toko_id'");
        $_SESSION['pesan_sukses'] = "Store approved and verified successfully!";
    }
    header("Location: index.php");
    exit();
}

// 4. AMBIL DAFTAR TOKO YANG SEDANG PENDING
$query_log_pending = mysqli_query($conn, "SELECT toko.*, users.nama_lengkap, users.email 
                                          FROM toko 
                                          JOIN users ON toko.user_id = users.id 
                                          WHERE toko.status_verifikasi = 'pending' 
                                          ORDER BY toko.id ASC LIMIT 5");

// Ambil nama admin untuk disapa
$query_admin = mysqli_query($conn, "SELECT nama_lengkap FROM users WHERE id = '$user_id'");
$admin_data = mysqli_fetch_assoc($query_admin);

require_once '../includes/header.php'; 
?>

<link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">

<div class="bg-[#f4f7fa] min-h-screen pb-24 font-sans text-gray-800">

    <div class="bg-gradient-to-r from-gray-900 to-black py-12 relative overflow-hidden shadow-lg border-b-4 border-blue-500">
        <div class="absolute top-0 left-0 w-full h-full bg-[url('https://www.transparenttextures.com/patterns/cubes.png')] opacity-10 pointer-events-none"></div>
        <div class="max-w-screen-xl mx-auto px-5 md:px-8 relative z-10 flex flex-col md:flex-row justify-between items-center gap-6" data-aos="fade-down">
            <div class="flex items-center gap-5">
                <div class="w-16 h-16 bg-blue-600 rounded-2xl flex items-center justify-center text-white text-2xl shadow-[0_0_20px_rgba(37,99,235,0.5)]">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <div>
                    <div class="inline-flex items-center gap-2 bg-blue-500/20 border border-blue-500/30 px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest mb-2 text-blue-400">
                        <span class="w-2 h-2 bg-blue-400 rounded-full animate-ping"></span> Super Admin Access
                    </div>
                    <h1 class="text-2xl md:text-3xl font-black text-white tracking-tight">Admin Control Center</h1>
                    <p class="text-sm font-medium text-gray-400">Welcome, <span class="text-white font-bold"><?= htmlspecialchars($admin_data['nama_lengkap']) ?></span>.</p>
                </div>
            </div>
            
            <div class="flex gap-4">
                <a href="../index.php" target="_blank" class="bg-white/10 hover:bg-white/20 border border-white/20 text-white px-5 py-2.5 rounded-xl text-xs font-black uppercase tracking-widest transition-all flex items-center gap-2">
                    <i class="fas fa-globe"></i> View Website
                </a>
            </div>
        </div>
    </div>

    <div class="max-w-screen-xl mx-auto px-5 md:px-8 mt-8 grid grid-cols-1 lg:grid-cols-12 gap-8">
        
        <div class="lg:col-span-3 space-y-4" data-aos="fade-right">
            <div class="bg-white rounded-[2rem] shadow-sm border border-gray-100 p-4 sticky top-28">
                <div class="flex flex-col gap-2">
                    <a href="index.php" class="bg-blue-50 text-blue-600 flex items-center gap-4 p-4 rounded-2xl font-black text-sm transition-all border border-blue-100">
                        <div class="w-8 h-8 rounded-full bg-blue-600 text-white flex items-center justify-center shadow-md"><i class="fas fa-tachometer-alt"></i></div> Dashboard
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
                    <a href="laporan.php" class="text-gray-500 hover:bg-gray-50 hover:text-blue-600 flex items-center gap-4 p-4 rounded-2xl font-bold text-sm transition-all group">
                        <div class="w-8 h-8 rounded-full bg-gray-100 group-hover:bg-blue-600 group-hover:text-white flex items-center justify-center transition-colors"><i class="fas fa-chart-bar"></i></div> Transaction Reports
                    </a>
                </div>
            </div>
        </div>

        <div class="lg:col-span-9 space-y-6" data-aos="fade-up" data-aos-delay="100">

            <?php if (isset($_SESSION['pesan_sukses'])): ?>
                <div class="bg-green-50 border border-green-200 text-green-700 px-6 py-4 rounded-2xl text-sm font-bold flex items-center gap-3 shadow-sm">
                    <i class="fas fa-check-circle text-xl"></i> <?= $_SESSION['pesan_sukses']; unset($_SESSION['pesan_sukses']); ?>
                </div>
            <?php endif; ?>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-2">
                <div class="bg-white p-6 rounded-[2rem] shadow-sm border border-gray-100 flex flex-col justify-center group hover:border-green-500 transition-all">
                    <div class="flex justify-between items-start mb-4">
                        <div class="w-12 h-12 bg-green-50 text-green-500 rounded-xl flex items-center justify-center text-xl group-hover:scale-110 transition-transform"><i class="fas fa-wallet"></i></div>
                    </div>
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Total Revenue</p>
                    <h3 class="text-xl md:text-2xl font-black text-navy italic tracking-tight">Rp <?= number_format($total_uang, 0, ',', '.') ?></h3>
                </div>
                <div class="bg-white p-6 rounded-[2rem] shadow-sm border border-gray-100 flex flex-col justify-center group hover:border-orange transition-all">
                    <div class="flex justify-between items-start mb-4">
                        <div class="w-12 h-12 bg-orange/10 text-orange rounded-xl flex items-center justify-center text-xl group-hover:scale-110 transition-transform"><i class="fas fa-store"></i></div>
                    </div>
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Active Sellers</p>
                    <h3 class="text-2xl font-black text-navy"><?= number_format($total_seller) ?> <span class="text-sm text-gray-400">Store</span></h3>
                </div>
                <div class="bg-white p-6 rounded-[2rem] shadow-sm border border-gray-100 flex flex-col justify-center group hover:border-blue-500 transition-all">
                    <div class="flex justify-between items-start mb-4">
                        <div class="w-12 h-12 bg-blue-50 text-blue-500 rounded-xl flex items-center justify-center text-xl group-hover:scale-110 transition-transform"><i class="fas fa-users"></i></div>
                    </div>
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Total Buyers</p>
                    <h3 class="text-2xl font-black text-navy"><?= number_format($total_users) ?> <span class="text-sm text-gray-400">User</span></h3>
                </div>
                <div class="bg-gradient-to-br from-gray-900 to-gray-800 p-6 rounded-[2rem] shadow-xl shadow-gray-900/20 flex flex-col justify-center text-white border border-gray-700">
                    <div class="w-12 h-12 bg-white/10 rounded-xl flex items-center justify-center text-xl mb-4 border border-white/10"><i class="fas fa-server"></i></div>
                    <p class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-1">System Status</p>
                    <h3 class="text-xl font-black tracking-tight text-green-400"><i class="fas fa-check-circle mr-1"></i> Optimal</h3>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="lg:col-span-2 bg-white rounded-[2rem] p-6 md:p-8 shadow-sm border border-gray-100">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-lg font-black text-navy flex items-center gap-3">
                            <i class="fas fa-user-clock text-yellow-500"></i> Verification Queue
                        </h2>
                        <span class="bg-red text-white text-[10px] px-3 py-1 rounded-full font-black uppercase tracking-widest"><?= $tot_pending ?> Pending</span>
                    </div>
                    
                    <div class="space-y-4">
                        <?php if($tot_pending > 0): ?>
                            <?php while($pending = mysqli_fetch_assoc($query_log_pending)): ?>
                                <div class="flex flex-col md:flex-row md:items-center gap-4 p-4 rounded-2xl border border-yellow-200 bg-yellow-50/50 hover:bg-white hover:shadow-md transition-all">
                                    <div class="w-12 h-12 bg-white text-yellow-600 rounded-xl border border-yellow-100 flex items-center justify-center text-xl shrink-0"><i class="fas fa-store"></i></div>
                                    <div class="flex-grow">
                                        <h4 class="text-sm font-black text-navy leading-tight mb-1"><?= htmlspecialchars($pending['nama_toko']) ?></h4>
                                        <p class="text-[10px] text-gray-500 font-bold mb-1"><i class="fas fa-user-tie mr-1"></i> <?= htmlspecialchars($pending['nama_lengkap']) ?> | <i class="fas fa-envelope mx-1"></i> <?= htmlspecialchars($pending['email']) ?></p>
                                    </div>
                                    <div class="flex gap-2 shrink-0 mt-2 md:mt-0">
                                        <form action="" method="POST" class="w-full md:w-auto flex gap-2">
                                            <input type="hidden" name="toko_id" value="<?= $pending['id'] ?>">
                                            <button type="submit" name="aksi_verifikasi" value="ditolak" onclick="return confirm('Reject this store registration?')" class="w-10 h-10 bg-white border border-red-200 text-red-500 hover:bg-red-50 hover:text-red hover:border-red rounded-xl flex items-center justify-center transition-colors" title="Tolak">
                                                <i class="fas fa-times"></i>
                                            </button>
                                            <button type="submit" name="aksi_verifikasi" value="verified" onclick="return confirm('Approve and verify this store?')" class="px-4 h-10 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-black text-[10px] uppercase tracking-widest flex items-center justify-center gap-2 transition-colors shadow-md">
                                                <i class="fas fa-check"></i> Approve
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                            <a href="verifikasi.php" class="block text-center text-xs font-bold text-blue-600 hover:underline mt-4">View All Antrean &rarr;</a>
                        <?php else: ?>
                            <div class="text-center py-10 px-4 border-2 border-dashed border-gray-100 rounded-2xl">
                                <div class="text-3xl text-green-400 mb-3"><i class="fas fa-clipboard-check"></i></div>
                                <h4 class="text-base font-black text-navy mb-1">All Stores Have Been Verified</h4>
                                <p class="text-xs text-gray-400 font-medium">No new seller registrations at this time.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="lg:col-span-1 space-y-4">
                    <h4 class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-2 mb-2">Quick Admin Access</h4>
                    
                    <a href="users.php" class="flex items-center justify-between p-5 bg-gradient-to-br from-navy to-[#1a1a5c] text-white rounded-[1.5rem] hover:shadow-xl hover:-translate-y-1 transition-all group">
                        <div>
                            <span class="font-black text-xs uppercase tracking-widest block mb-1">User Management</span>
                            <span class="text-[9px] text-gray-400 font-medium">Manage user access rights</span>
                        </div>
                        <div class="w-8 h-8 bg-white/10 rounded-full flex items-center justify-center group-hover:bg-blue-500 transition-colors"><i class="fas fa-chevron-right text-[10px]"></i></div>
                    </a>
                    
                    <a href="kelola_iklan.php" class="flex items-center justify-between p-5 bg-white border border-gray-100 text-navy rounded-[1.5rem] hover:border-orange hover:shadow-md hover:-translate-y-1 transition-all group">
                        <div>
                            <span class="font-black text-xs uppercase tracking-widest block mb-1">Manage Ads</span>
                            <span class="text-[9px] text-gray-500 font-medium">Manage seller sponsor status</span>
                        </div>
                        <div class="w-8 h-8 bg-gray-50 rounded-full flex items-center justify-center group-hover:bg-orange group-hover:text-white transition-colors"><i class="fas fa-bullhorn text-[10px]"></i></div>
                    </a>

                    <a href="laporan.php" class="flex items-center justify-between p-5 bg-white border border-gray-100 text-navy rounded-[1.5rem] hover:border-blue-500 hover:shadow-md hover:-translate-y-1 transition-all group">
                        <div>
                            <span class="font-black text-xs uppercase tracking-widest block mb-1">Transaction Reports</span>
                            <span class="text-[9px] text-gray-500 font-medium">Print financial reports</span>
                        </div>
                        <div class="w-8 h-8 bg-gray-50 rounded-full flex items-center justify-center group-hover:bg-blue-500 group-hover:text-white transition-colors"><i class="fas fa-chevron-right text-[10px]"></i></div>
                    </a>
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