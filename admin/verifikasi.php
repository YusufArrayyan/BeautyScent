<?php 
require_once '../config/database.php';
session_start();

// 1. KEAMANAN TINGKAT DEWA: Cek Login & Role khusus 'admin'
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// 2. LOGIKA AKSI ADMIN (TERIMA, TOLAK, CABUT VERIFIKASI)
if (isset($_POST['aksi_verifikasi'])) {
    $id_toko = mysqli_real_escape_string($conn, $_POST['id_toko']);
    $aksi = $_POST['aksi']; // 'terima', 'tolak', 'cabut'

    if ($aksi == 'terima') {
        mysqli_query($conn, "UPDATE toko SET status_verifikasi = 'verified' WHERE id = '$id_toko'");
        $_SESSION['pesan_sukses'] = "Store successfully diverifikasi dan sekarang mengudara!";
    } elseif ($aksi == 'cabut') {
        mysqli_query($conn, "UPDATE toko SET status_verifikasi = 'pending' WHERE id = '$id_toko'");
        $_SESSION['pesan_sukses'] = "Verifikasi toko dicabut. Store disembunyikan dari publik.";
    } elseif ($aksi == 'tolak') {
        // Opsional: Hapus toko atau biarkan dengan status 'rejected'
        // Untuk amannya, kita hapus saja datanya agar dia bisa daftar ulang jika salah
        mysqli_query($conn, "DELETE FROM toko WHERE id = '$id_toko'");
        $_SESSION['pesan_sukses'] = "Store registration rejected and removed from queue.";
    }
    header("Location: verifikasi.php");
    exit();
}

// 3. AMBIL DATA TOKO PENDING (PERBAIKAN ORDER BY ID)
$q_pending = mysqli_query($conn, "
    SELECT t.*, u.nama_lengkap, u.email, u.no_hp as no_hp_user 
    FROM toko t 
    JOIN users u ON t.user_id = u.id 
    WHERE t.status_verifikasi = 'pending' 
    ORDER BY t.id ASC
");
$tot_pending = mysqli_num_rows($q_pending);

// 4. AMBIL DATA TOKO VERIFIED (Untuk dikelola/dicabut jika nakal)
$q_verified = mysqli_query($conn, "
    SELECT t.*, u.nama_lengkap, u.email 
    FROM toko t 
    JOIN users u ON t.user_id = u.id 
    WHERE t.status_verifikasi = 'verified' 
    ORDER BY t.id DESC
");

require_once '../includes/header.php'; 
?>

<link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">

<div class="bg-[#f4f7fa] min-h-screen pb-24 font-sans text-gray-800">

    <!-- HEADER ADMIN -->
    <div class="bg-gradient-to-r from-gray-900 to-black py-12 relative overflow-hidden shadow-lg border-b-4 border-blue-500">
        <div class="absolute top-0 left-0 w-full h-full bg-[url('https://www.transparenttextures.com/patterns/cubes.png')] opacity-10 pointer-events-none"></div>
        <div class="max-w-screen-xl mx-auto px-5 md:px-8 relative z-10 flex flex-col md:flex-row justify-between items-center gap-6" data-aos="fade-down">
            <div class="flex items-center gap-5">
                <div class="w-16 h-16 bg-blue-600 rounded-2xl flex items-center justify-center text-white text-2xl shadow-[0_0_20px_rgba(37,99,235,0.5)]">
                    <i class="fas fa-user-check"></i>
                </div>
                <div>
                    <h1 class="text-2xl md:text-3xl font-black text-white tracking-tight">Store Verification</h1>
                    <p class="text-sm font-medium text-gray-400">Review, approve, or reject new seller registrations.</p>
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
        
        <!-- SIDEBAR ADMIN -->
        <div class="lg:col-span-3 space-y-4" data-aos="fade-right">
            <div class="bg-white rounded-[2rem] shadow-sm border border-gray-100 p-4 sticky top-28">
                <div class="flex flex-col gap-2">
                    <a href="index.php" class="text-gray-500 hover:bg-gray-50 hover:text-blue-600 flex items-center gap-4 p-4 rounded-2xl font-bold text-sm transition-all group">
                        <div class="w-8 h-8 rounded-full bg-gray-100 group-hover:bg-blue-600 group-hover:text-white flex items-center justify-center transition-colors"><i class="fas fa-tachometer-alt"></i></div> Dashboard
                    </a>
                    <!-- Menu Active: Store Verification -->
                    <a href="verifikasi.php" class="bg-blue-50 text-blue-600 flex items-center gap-4 p-4 rounded-2xl font-black text-sm transition-all border border-blue-100">
                        <div class="w-8 h-8 rounded-full bg-blue-600 text-white flex items-center justify-center shadow-md"><i class="fas fa-user-check"></i></div> Store Verification
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

        <!-- MAIN KONTEN ADMIN -->
        <div class="lg:col-span-9 space-y-8" data-aos="fade-up" data-aos-delay="100">

            <?php if (isset($_SESSION['pesan_sukses'])): ?>
                <div class="bg-green-50 border border-green-200 text-green-700 px-6 py-4 rounded-2xl text-sm font-bold flex items-center gap-3 shadow-sm">
                    <i class="fas fa-check-circle text-xl"></i> <?= $_SESSION['pesan_sukses']; unset($_SESSION['pesan_sukses']); ?>
                </div>
            <?php endif; ?>

            <!-- SEGMEN 1: ANTREAN PENDING -->
            <div class="bg-white rounded-[2rem] shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-gray-100 overflow-hidden">
                <div class="p-6 md:p-8 border-b border-gray-50 flex justify-between items-center bg-yellow-50/30">
                    <div>
                        <h3 class="text-lg font-black text-navy mb-1"><i class="fas fa-user-clock text-yellow-500 mr-2"></i> Verification Queue</h3>
                        <p class="text-xs text-gray-500 font-medium">Store baru yang perlu ditinjau sebelum tampil ke publik.</p>
                    </div>
                    <span class="bg-red text-white px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest"><?= $tot_pending ?> Pending</span>
                </div>

                <div class="p-0 overflow-x-auto">
                    <table class="w-full text-left border-collapse min-w-[800px]">
                        <thead class="bg-gray-50/80">
                            <tr class="text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-100">
                                <th class="p-6 w-1/3">Informasi Store</th>
                                <th class="p-6 w-1/3">Data Pemilik</th>
                                <th class="p-6 text-center">Status</th>
                                <th class="p-6 text-right">Action Eksekusi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50 text-sm">
                            <?php if($tot_pending > 0): ?>
                                <?php while($p = mysqli_fetch_assoc($q_pending)): ?>
                                <tr class="hover:bg-blue-50/20 transition-colors group">
                                    <td class="p-6">
                                        <h4 class="font-black text-navy mb-1"><?= htmlspecialchars($p['nama_toko']) ?></h4>
                                        <p class="text-[11px] text-gray-500 font-bold mb-2"><i class="fas fa-map-marker-alt text-orange mr-1"></i> <?= htmlspecialchars($p['alamat'] ?? 'Belum isi alamat') ?></p>
                                    </td>
                                    <td class="p-6">
                                        <p class="font-bold text-gray-600 mb-0.5"><i class="fas fa-user-tie text-gray-300 mr-1.5"></i> <?= htmlspecialchars($p['nama_lengkap']) ?></p>
                                        <p class="text-[11px] text-gray-400 font-bold"><i class="fab fa-whatsapp text-green-500 mr-1.5"></i> <?= htmlspecialchars($p['no_hp'] ?? $p['no_hp_user']) ?></p>
                                    </td>
                                    <td class="p-6 text-center">
                                        <span class="bg-yellow-100 text-yellow-600 px-3 py-1 rounded-md text-[9px] font-black uppercase tracking-widest border border-yellow-200">Pending</span>
                                    </td>
                                    <td class="p-6 text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <form action="" method="POST" class="inline">
                                                <input type="hidden" name="id_toko" value="<?= $p['id'] ?>">
                                                <input type="hidden" name="aksi" value="tolak">
                                                <button type="submit" onclick="return confirm('Reject and remove this store registration?')" class="w-10 h-10 bg-white border border-gray-200 text-red-400 hover:bg-red-50 hover:text-red hover:border-red rounded-xl flex items-center justify-center transition-colors shadow-sm group-hover:border-red/30" title="Tolak">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </form>
                                            <form action="" method="POST" class="inline">
                                                <input type="hidden" name="id_toko" value="<?= $p['id'] ?>">
                                                <input type="hidden" name="aksi" value="terima">
                                                <button type="submit" onclick="return confirm('Approve toko ini untuk tampil ke publik?')" class="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-[10px] font-black uppercase tracking-widest flex items-center gap-2 transition-all shadow-md shadow-blue-600/20 transform hover:-translate-y-0.5">
                                                    <i class="fas fa-check"></i> Approve
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="p-16 text-center">
                                        <div class="w-16 h-16 bg-green-50 text-green-500 rounded-full flex items-center justify-center text-2xl mx-auto mb-4"><i class="fas fa-clipboard-check"></i></div>
                                        <h4 class="text-base font-black text-navy mb-1">Semua Bersih!</h4>
                                        <p class="text-sm text-gray-500 font-medium">Tidak ada pendaftaran mitra baru yang perlu ditinjau.</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- SEGMEN 2: TOKO TERVERIFIKASI (AKTIF) -->
            <div class="bg-white rounded-[2rem] shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-gray-100 overflow-hidden" data-aos="fade-up" data-aos-delay="200">
                <div class="p-6 md:p-8 border-b border-gray-50 flex justify-between items-center">
                    <div>
                        <h3 class="text-lg font-black text-navy mb-1"><i class="fas fa-store text-green-500 mr-2"></i> Active Sellers (Verified)</h3>
                        <p class="text-xs text-gray-500 font-medium">Daftar toko yang saat ini beroperasi di platform.</p>
                    </div>
                </div>

                <div class="p-0 overflow-x-auto">
                    <table class="w-full text-left border-collapse min-w-[800px]">
                        <thead class="bg-gray-50/80">
                            <tr class="text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-100">
                                <th class="p-6">Store Name</th>
                                <th class="p-6">Pemilik</th>
                                <th class="p-6 text-center">Status</th>
                                <th class="p-6 text-right">Tindakan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50 text-sm">
                            <?php if(mysqli_num_rows($q_verified) > 0): ?>
                                <?php while($v = mysqli_fetch_assoc($q_verified)): ?>
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="p-6">
                                        <h4 class="font-black text-navy mb-1"><?= htmlspecialchars($v['nama_toko']) ?></h4>
                                        <a href="../detail_toko.php?id=<?= $v['id'] ?>" target="_blank" class="text-[10px] font-bold text-blue-500 hover:underline flex items-center gap-1 w-max"><i class="fas fa-external-link-alt"></i> View Store</a>
                                    </td>
                                    <td class="p-6 font-bold text-gray-600">
                                        <?= htmlspecialchars($v['nama_lengkap']) ?>
                                    </td>
                                    <td class="p-6 text-center">
                                        <span class="bg-green-100 text-green-600 px-3 py-1 rounded-full text-[9px] font-black uppercase tracking-widest inline-flex items-center gap-1.5 border border-green-200"><i class="fas fa-check-circle"></i> Verified</span>
                                    </td>
                                    <td class="p-6 text-right">
                                        <form action="" method="POST" class="inline">
                                            <input type="hidden" name="id_toko" value="<?= $v['id'] ?>">
                                            <input type="hidden" name="aksi" value="cabut">
                                            <button type="submit" onclick="return confirm('Cabut verifikasi? Store ini tidak akan bisa dilihat oleh pembeli lagi.')" class="px-4 py-2 bg-white border border-gray-200 text-gray-500 hover:bg-red-50 hover:text-red hover:border-red rounded-lg text-[10px] font-black uppercase tracking-widest transition-colors flex items-center gap-2 ml-auto shadow-sm">
                                                <i class="fas fa-ban"></i> Suspend Store
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="p-16 text-center">
                                        <div class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center text-gray-300 text-2xl mx-auto mb-4"><i class="fas fa-store-slash"></i></div>
                                        <p class="text-navy font-black text-lg">No Active Sellers Yet</p>
                                        <p class="text-gray-400 text-xs font-bold">No stores are currently operating on the platform.</p>
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