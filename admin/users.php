<?php 
require_once '../config/database.php';
session_start();

// 1. KEAMANAN SUPER KETAT: Hanya Admin yang boleh masuk
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// 2. LOGIKA HAPUS USER (Sapu Bersih / Cascade Delete)
if (isset($_POST['hapus_user'])) {
    $id_user_hapus = mysqli_real_escape_string($conn, $_POST['id_user']);
    
    // Cari apakah user ini punya toko
    $cek_toko = mysqli_query($conn, "SELECT id FROM toko WHERE user_id = '$id_user_hapus'");
    if ($toko = mysqli_fetch_assoc($cek_toko)) {
        $id_toko_hapus = $toko['id'];
        // Hapus layanan dan pesanan yang terkait dengan toko ini
        mysqli_query($conn, "DELETE FROM layanan WHERE toko_id = '$id_toko_hapus'");
        mysqli_query($conn, "DELETE FROM pesanan WHERE toko_id = '$id_toko_hapus'");
        // Hapus ulasan
        mysqli_query($conn, "DELETE FROM ulasan WHERE toko_id = '$id_toko_hapus'");
        // Hapus keranjang
        mysqli_query($conn, "DELETE FROM keranjang WHERE toko_id = '$id_toko_hapus'");
        // Hapus tokonya
        mysqli_query($conn, "DELETE FROM toko WHERE id = '$id_toko_hapus'");
    }
    
    // Jika yang dihapus pembeli, bersihkan keranjang & ulasannya juga
    mysqli_query($conn, "DELETE FROM keranjang WHERE user_id = '$id_user_hapus'");
    mysqli_query($conn, "DELETE FROM ulasan WHERE user_id = '$id_user_hapus'");
    
    // Terakhir, hapus akun usernya (Kecuali Admin Utama)
    if ($id_user_hapus != $_SESSION['user_id']) {
        if (mysqli_query($conn, "DELETE FROM users WHERE id = '$id_user_hapus'")) {
            $_SESSION['pesan_sukses'] = "Pengguna dan seluruh datanya successfully dihapus permanen.";
        } else {
            $_SESSION['pesan_error'] = "Failed menghapus pengguna.";
        }
    } else {
        $_SESSION['pesan_error'] = "Anda tidak bisa menghapus akun Anda sendiri!";
    }
    
    header("Location: users.php");
    exit();
}

// 3. AMBIL SEMUA DATA USER (Kecuali Admin Utama)
$query = mysqli_query($conn, "SELECT * FROM users WHERE role != 'admin' ORDER BY role ASC, nama_lengkap ASC");
$total_user = mysqli_num_rows($query);

// Gunakan Header Global
require_once '../includes/header.php'; 

// [TAMBAHAN] Ambil jumlah pending verifikasi untuk badge notifikasi di sidebar
$q_pending = mysqli_query($conn, "SELECT COUNT(id) as total FROM toko WHERE status_verifikasi = 'pending'");
$tot_pending = mysqli_fetch_assoc($q_pending)['total'] ?? 0;
?>

<!-- PUSTAKA ANIMASI -->
<link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">

<div class="bg-[#f4f7fa] min-h-screen pb-24 font-sans text-gray-800">

    <!-- HEADER ADMIN -->
    <div class="bg-gradient-to-r from-gray-900 to-black py-12 relative overflow-hidden shadow-lg border-b-4 border-blue-500">
        <div class="absolute top-0 left-0 w-full h-full bg-[url('https://www.transparenttextures.com/patterns/cubes.png')] opacity-10 pointer-events-none"></div>
        <div class="max-w-screen-xl mx-auto px-5 md:px-8 relative z-10 flex flex-col md:flex-row justify-between items-center gap-6" data-aos="fade-down">
            <div class="flex items-center gap-5">
                <div class="w-16 h-16 bg-blue-600 rounded-2xl flex items-center justify-center text-white text-2xl shadow-[0_0_20px_rgba(37,99,235,0.5)]">
                    <i class="fas fa-users-cog"></i>
                </div>
                <div>
                    <h1 class="text-2xl md:text-3xl font-black text-white tracking-tight">User Management</h1>
                    <p class="text-sm font-medium text-gray-400">Total <span class="text-white font-bold"><?= $total_user ?> akun</span> terdaftar di BeautyScent.</p>
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
                    <a href="verifikasi.php" class="text-gray-500 hover:bg-gray-50 hover:text-blue-600 flex items-center gap-4 p-4 rounded-2xl font-bold text-sm transition-all group">
                        <div class="w-8 h-8 rounded-full bg-gray-100 group-hover:bg-blue-600 group-hover:text-white flex items-center justify-center transition-colors"><i class="fas fa-user-check"></i></div> Store Verification
                        <?php if($tot_pending > 0): ?><span class="ml-auto bg-red text-white text-[10px] px-2 py-0.5 rounded-full animate-pulse shadow-sm"><?= $tot_pending ?></span><?php endif; ?>
                    </a>
                    <!-- Menu Active: User Data -->
                    <a href="users.php" class="bg-blue-50 text-blue-600 flex items-center gap-4 p-4 rounded-2xl font-black text-sm transition-all border border-blue-100">
                        <div class="w-8 h-8 rounded-full bg-blue-600 text-white flex items-center justify-center shadow-md"><i class="fas fa-users"></i></div> User Data
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
        <div class="lg:col-span-9 space-y-6" data-aos="fade-up" data-aos-delay="100">
            
            <!-- ALERT NOTIFIKASI -->
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

            <!-- TABEL USER -->
            <div class="bg-white rounded-[2rem] shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-gray-100 overflow-hidden">
                <div class="p-6 md:p-8 border-b border-gray-50 flex justify-between items-center">
                    <div>
                        <h3 class="text-lg font-black text-navy mb-1">User List Active</h3>
                        <p class="text-xs text-gray-500 font-medium">Manage buyer and seller accounts.</p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse min-w-[800px]">
                        <thead class="bg-gray-50/80">
                            <tr class="text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-100">
                                <th class="p-6">Full Name</th>
                                <th class="p-6">Kontak & Email</th>
                                <th class="p-6 text-center">Role / Akses</th>
                                <th class="p-6 text-center">Status</th>
                                <th class="p-6 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50 text-sm">
                            <?php if($total_user > 0): ?>
                                <?php while($u = mysqli_fetch_assoc($query)): ?>
                                <tr class="hover:bg-blue-50/20 transition duration-300 group">
                                    <td class="p-6 font-black text-navy uppercase"><?= htmlspecialchars($u['nama_lengkap']) ?></td>
                                    <td class="p-6">
                                        <p class="font-bold text-gray-600 mb-0.5"><i class="fas fa-envelope text-gray-300 mr-1.5"></i> <?= htmlspecialchars($u['email']) ?></p>
                                        <p class="text-[11px] text-gray-400 font-bold"><i class="fas fa-phone-alt text-gray-300 mr-1.5"></i> <?= htmlspecialchars($u['no_hp']) ?></p>
                                    </td>
                                    <td class="p-6 text-center">
                                        <?php 
                                            $role_class = "bg-blue-50 text-blue-600 border-blue-200"; 
                                            if($u['role'] == 'toko') $role_class = "bg-orange/10 text-orange border-orange/20";
                                        ?>
                                        <span class="px-4 py-1.5 rounded-xl text-[9px] font-black uppercase tracking-widest border <?= $role_class ?>">
                                            <?php if($u['role'] === 'toko'): ?>
                                                <i class="fas fa-image mr-1"></i> Seller
                                            <?php else: ?>
                                                <i class="fas fa-user mr-1"></i> Pembeli
                                            <?php endif; ?>
                                        </span>
                                    </td>
                                    <td class="p-6 text-center">
                                        <span class="flex items-center justify-center gap-2 text-green-500 font-black text-[10px] uppercase tracking-widest bg-green-50 w-max mx-auto px-3 py-1 rounded-md">
                                            <span class="w-1.5 h-1.5 bg-green-500 rounded-full animate-pulse"></span> Active
                                        </span>
                                    </td>
                                    <td class="p-6 text-right">
                                        <!-- Form Hapus Real -->
                                        <form action="" method="POST" class="inline-block">
                                            <input type="hidden" name="id_user" value="<?= $u['id'] ?>">
                                            <button type="submit" name="hapus_user" onclick="return confirm('DELETE PERMANENTLY? \nIf this is a Seller account, all their products and transactions will also be permanently removed!')" class="w-10 h-10 bg-white border border-gray-200 text-gray-400 hover:bg-red-50 hover:text-red hover:border-red transition-all rounded-xl shadow-sm flex items-center justify-center group-hover:border-red/30">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="p-16 text-center">
                                        <div class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center text-gray-300 text-2xl mx-auto mb-4"><i class="fas fa-users-slash"></i></div>
                                        <p class="text-navy font-black text-lg">No Users Yet</p>
                                        <p class="text-gray-400 text-xs font-bold">No buyers or sellers have registered yet.</p>
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