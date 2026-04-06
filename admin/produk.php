<?php
require_once '../config/database.php';
session_start();

// 1. KEAMANAN SUPER KETAT: Hanya Admin yang boleh masuk
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

// 2. LOGIKA TAKE DOWN / HAPUS LAYANAN
if (isset($_POST['hapus_layanan'])) {
    $id_layanan = mysqli_real_escape_string($conn, $_POST['id_layanan']);
    
    // Hapus layanan dari keranjang pembeli (biar tidak error/nyangkut)
    mysqli_query($conn, "DELETE FROM keranjang WHERE layanan_id = '$id_layanan'");
    
    // Hapus layanan dari database utama
    if (mysqli_query($conn, "DELETE FROM layanan WHERE id = '$id_layanan'")) {
        $_SESSION['pesan_sukses'] = "Layanan successfully di-take down (dihapus permanen).";
    } else {
        $_SESSION['pesan_error'] = "Failed menghapus layanan.";
    }
    
    header("Location: produk.php");
    exit();
}

// 3. AMBIL DATA SEMUA LAYANAN (DENGAN NAMA TOKO & PEMILIK)
$query = mysqli_query($conn, "SELECT l.*, t.nama_toko, t.id as id_toko, u.nama_lengkap 
                              FROM layanan l 
                              JOIN toko t ON l.toko_id = t.id 
                              JOIN users u ON t.user_id = u.id 
                              ORDER BY l.id DESC");
$total_layanan = mysqli_num_rows($query);

// 4. AMBIL NOTIFIKASI PENDING VERIFIKASI UNTUK SIDEBAR
$q_pending = mysqli_query($conn, "SELECT COUNT(id) as total FROM toko WHERE status_verifikasi = 'pending'");
$tot_pending = mysqli_fetch_assoc($q_pending)['total'] ?? 0;

// Gunakan Header Global
require_once '../includes/header.php'; 
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
                    <i class="fas fa-box"></i>
                </div>
                <div>
                    <h1 class="text-2xl md:text-3xl font-black text-white tracking-tight">Monitor Layanan</h1>
                    <p class="text-sm font-medium text-gray-400">Total <span class="text-white font-bold"><?= $total_layanan ?> jasa aktif</span> yang ditawarkan oleh para seller.</p>
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
                    <a href="users.php" class="text-gray-500 hover:bg-gray-50 hover:text-blue-600 flex items-center gap-4 p-4 rounded-2xl font-bold text-sm transition-all group">
                        <div class="w-8 h-8 rounded-full bg-gray-100 group-hover:bg-blue-600 group-hover:text-white flex items-center justify-center transition-colors"><i class="fas fa-users"></i></div> User Data
                    </a>
                    <a href="kategori.php" class="text-gray-500 hover:bg-gray-50 hover:text-blue-600 flex items-center gap-4 p-4 rounded-2xl font-bold text-sm transition-all group">
                        <div class="w-8 h-8 rounded-full bg-gray-100 group-hover:bg-blue-600 group-hover:text-white flex items-center justify-center transition-colors"><i class="fas fa-tags"></i></div> Manage Categories
                    </a>
                    <!-- Menu Active: Monitor Layanan -->
                    <a href="produk.php" class="bg-blue-50 text-blue-600 flex items-center gap-4 p-4 rounded-2xl font-black text-sm transition-all border border-blue-100">
                        <div class="w-8 h-8 rounded-full bg-blue-600 text-white flex items-center justify-center shadow-md"><i class="fas fa-box"></i></div> Monitor Products
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

            <!-- TABEL LAYANAN -->
            <div class="bg-white rounded-[2rem] shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-gray-100 overflow-hidden">
                <div class="p-6 md:p-8 border-b border-gray-50 flex justify-between items-center">
                    <div>
                        <h3 class="text-lg font-black text-navy mb-1">Daftar Layanan Seller</h3>
                        <p class="text-xs text-gray-500 font-medium">Monitoring seluruh katalog harga dan jasa yang dipublikasikan.</p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse min-w-[800px]">
                        <thead class="bg-gray-50/80">
                            <tr class="text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-100">
                                <th class="p-6 md:p-8 w-5/12">Informasi Layanan</th>
                                <th class="p-6 md:p-8 w-3/12">Store Name / Seller</th>
                                <th class="p-6 md:p-8 w-2/12">Harga Layanan</th>
                                <th class="p-6 md:p-8 w-2/12 text-right">Action Admin</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50 text-sm">
                            <?php if($total_layanan > 0): ?>
                                <?php while($l = mysqli_fetch_assoc($query)): ?>
                                <tr class="hover:bg-blue-50/20 transition duration-300 group">
                                    <td class="p-6 md:p-8">
                                        <div class="flex items-start gap-4">
                                            <!-- Jika ada foto layanannya -->
                                            <?php if(!empty($l['foto_layanan'])): ?>
                                                <img src="../uploads/layanan/<?= htmlspecialchars($l['foto_layanan']) ?>" class="w-16 h-16 object-cover rounded-xl border border-gray-100 shadow-sm shrink-0">
                                            <?php else: ?>
                                                <div class="w-16 h-16 bg-gray-100 rounded-xl flex items-center justify-center text-gray-400 border border-gray-200 shrink-0"><i class="fas fa-image"></i></div>
                                            <?php endif; ?>
                                            
                                            <div>
                                                <h4 class="font-black text-navy uppercase text-sm mb-1 group-hover:text-blue-600 transition"><?= htmlspecialchars($l['nama_layanan']) ?></h4>
                                                <p class="text-[10px] text-gray-500 font-medium line-clamp-2 leading-relaxed"><?= htmlspecialchars($l['deskripsi_layanan']) ?></p>
                                            </div>
                                        </div>
                                    </td>

                                    <td class="p-6 md:p-8">
                                        <p class="font-bold text-navy mb-1">
                                            <i class="fas fa-store text-gray-400 text-xs mr-1"></i> <?= htmlspecialchars($l['nama_toko']) ?>
                                        </p>
                                        <p class="text-[10px] text-gray-500 font-bold uppercase tracking-widest"><i class="fas fa-user-tie text-gray-300 mr-1"></i> <?= htmlspecialchars($l['nama_lengkap']) ?></p>
                                    </td>

                                    <td class="p-6 md:p-8">
                                        <?php if(isset($l['harga_coret']) && $l['harga_coret'] > $l['harga']): ?>
                                            <p class="text-[9px] text-red font-black line-through mb-0.5">Rp <?= number_format($l['harga_coret'], 0, ',', '.') ?></p>
                                        <?php endif; ?>
                                        <p class="font-black text-navy italic text-base group-hover:text-orange transition-colors">Rp <?= number_format($l['harga'], 0, ',', '.') ?></p>
                                    </td>

                                    <td class="p-6 md:p-8 text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <!-- Tombol View Store -->
                                            <a href="../detail_toko.php?id=<?= $l['id_toko'] ?>" target="_blank" title="View Store" class="w-10 h-10 bg-blue-50 text-blue-500 hover:bg-blue-600 hover:text-white rounded-xl transition flex items-center justify-center border border-blue-100 shadow-sm group-hover:border-blue-300">
                                                <i class="far fa-eye"></i>
                                            </a>
                                            <!-- Form Take Down / Hapus -->
                                            <form action="" method="POST" class="inline-block">
                                                <input type="hidden" name="id_layanan" value="<?= $l['id'] ?>">
                                                <button type="submit" name="hapus_layanan" onclick="return confirm('Are you sure you want to take down (remove) this product?')" title="Take Down Product" class="w-10 h-10 bg-red-50 text-red-500 hover:bg-red hover:text-white rounded-xl transition flex items-center justify-center border border-red-100 shadow-sm group-hover:border-red/30">
                                                    <i class="far fa-trash-alt"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="p-16 text-center">
                                        <div class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center text-gray-300 text-2xl mx-auto mb-4"><i class="fas fa-box-open"></i></div>
                                        <p class="text-navy font-black text-lg">No Products Yet</p>
                                        <p class="text-gray-400 text-xs font-bold">No sellers have published products yet.</p>
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