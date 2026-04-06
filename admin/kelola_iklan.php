<?php 
require_once '../config/database.php';
session_start();

// 1. KEAMANAN SUPER KETAT: Hanya Admin yang boleh masuk
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

// 2. SISTEM PINTAR (AUTO-EXPIRE): Cabut iklan yang sudah lewat tanggal kadaluarsa
mysqli_query($conn, "UPDATE toko SET is_iklan = 0, iklan_expires = NULL WHERE is_iklan = 1 AND iklan_expires < NOW()");

// 3. LOGIKA AKTIFKAN IKLAN
if (isset($_POST['aktifkan_iklan'])) {
    $id_toko = mysqli_real_escape_string($conn, $_POST['id_toko']);
    $durasi = (int)$_POST['durasi_hari']; // 7, 30, atau 90
    
    // Hitung tanggal kedaluwarsa dari sekarang
    $expires = date('Y-m-d H:i:s', strtotime("+$durasi days"));

    $query_update = "UPDATE toko SET is_iklan = 1, iklan_expires = '$expires' WHERE id = '$id_toko'";
    
    if (mysqli_query($conn, $query_update)) {
        $_SESSION['pesan_sukses'] = "Iklan successfully diaktifkan selama $durasi Hari!";
    } else {
        $_SESSION['pesan_error'] = "Failed mengaktifkan iklan.";
    }
    header("Location: kelola_iklan.php");
    exit();
}

// 4. LOGIKA CABUT IKLAN (MANUAL)
if (isset($_POST['cabut_iklan'])) {
    $id_toko = mysqli_real_escape_string($conn, $_POST['id_toko']);
    
    if (mysqli_query($conn, "UPDATE toko SET is_iklan = 0, iklan_expires = NULL WHERE id = '$id_toko'")) {
        $_SESSION['pesan_sukses'] = "Iklan successfully dicabut secara manual.";
    } else {
        $_SESSION['pesan_error'] = "Failed mencabut iklan.";
    }
    header("Location: kelola_iklan.php");
    exit();
}

// 5. AMBIL DATA TOKO YANG IKLANNYA AKTIF
$q_iklan_aktif = mysqli_query($conn, "
    SELECT t.*, u.nama_lengkap 
    FROM toko t 
    JOIN users u ON t.user_id = u.id 
    WHERE t.is_iklan = 1 
    ORDER BY t.iklan_expires ASC
");
$tot_iklan_aktif = mysqli_num_rows($q_iklan_aktif);

// 6. AMBIL SEMUA DATA TOKO VERIFIED (Untuk didaftarkan iklannya)
$q_semua_toko = mysqli_query($conn, "
    SELECT t.*, u.nama_lengkap 
    FROM toko t 
    JOIN users u ON t.user_id = u.id 
    WHERE t.status_verifikasi = 'verified' AND (t.is_iklan = 0 OR t.is_iklan IS NULL)
    ORDER BY t.nama_toko ASC
");

// Ambil notifikasi untuk sidebar
$q_pending = mysqli_query($conn, "SELECT COUNT(id) as total FROM toko WHERE status_verifikasi = 'pending'");
$tot_pending = mysqli_fetch_assoc($q_pending)['total'] ?? 0;

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
                    <i class="fas fa-bullhorn"></i>
                </div>
                <div>
                    <h1 class="text-2xl md:text-3xl font-black text-white tracking-tight">Manajemen Iklan</h1>
                    <p class="text-sm font-medium text-gray-400">Activekan status prioritas (Sponsor) untuk toko yang sudah membayar.</p>
                </div>
            </div>
            
            <div class="bg-white/10 backdrop-blur border border-white/20 px-5 py-3 rounded-xl flex items-center gap-3">
                <div class="w-2 h-2 bg-green-400 rounded-full animate-pulse"></div>
                <span class="text-white text-xs font-bold uppercase tracking-widest">Auto-Expire Active</span>
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
                    <a href="produk.php" class="text-gray-500 hover:bg-gray-50 hover:text-blue-600 flex items-center gap-4 p-4 rounded-2xl font-bold text-sm transition-all group">
                        <div class="w-8 h-8 rounded-full bg-gray-100 group-hover:bg-blue-600 group-hover:text-white flex items-center justify-center transition-colors"><i class="fas fa-box"></i></div> Monitor Products
                    </a>
                    <!-- Menu Active: Manage Ads -->
                    <a href="kelola_iklan.php" class="bg-blue-50 text-blue-600 flex items-center gap-4 p-4 rounded-2xl font-black text-sm transition-all border border-blue-100">
                        <div class="w-8 h-8 rounded-full bg-blue-600 text-white flex items-center justify-center shadow-md"><i class="fas fa-bullhorn"></i></div> Manage Ads
                    </a>
                    <a href="laporan.php" class="text-gray-500 hover:bg-gray-50 hover:text-blue-600 flex items-center gap-4 p-4 rounded-2xl font-bold text-sm transition-all group">
                        <div class="w-8 h-8 rounded-full bg-gray-100 group-hover:bg-blue-600 group-hover:text-white flex items-center justify-center transition-colors"><i class="fas fa-chart-bar"></i></div> Transaction Reports
                    </a>
                </div>
            </div>
        </div>

        <!-- MAIN KONTEN -->
        <div class="lg:col-span-9 space-y-8" data-aos="fade-up" data-aos-delay="100">
            
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

            <!-- SEGMEN 1: IKLAN YANG SEDANG BERJALAN -->
            <div class="bg-white rounded-[2rem] shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-gray-100 overflow-hidden">
                <div class="p-6 md:p-8 border-b border-gray-50 flex justify-between items-center bg-blue-50/30">
                    <div>
                        <h3 class="text-lg font-black text-navy mb-1"><i class="fas fa-rocket text-blue-500 mr-2"></i> Iklan Sedang Active</h3>
                        <p class="text-xs text-gray-500 font-medium">Daftar toko yang saat ini menduduki posisi prioritas.</p>
                    </div>
                    <span class="bg-orange text-white px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest"><?= $tot_iklan_aktif ?> Store</span>
                </div>

                <div class="p-0 overflow-x-auto">
                    <table class="w-full text-left border-collapse min-w-[800px]">
                        <thead class="bg-gray-50/80">
                            <tr class="text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-100">
                                <th class="p-6 w-1/3">Store / Mitra</th>
                                <th class="p-6 text-center">Ad Status</th>
                                <th class="p-6 text-center">Berakhir Pada</th>
                                <th class="p-6 text-right">Tindakan Admin</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50 text-sm">
                            <?php if($tot_iklan_aktif > 0): ?>
                                <?php while($i = mysqli_fetch_assoc($q_iklan_aktif)): 
                                    // Hitung sisa hari
                                    $now = new DateTime();
                                    $exp = new DateTime($i['iklan_expires']);
                                    $diff = $now->diff($exp);
                                    $sisa_hari = $diff->days;
                                    $is_today = ($sisa_hari == 0) ? true : false;
                                ?>
                                <tr class="hover:bg-blue-50/20 transition-colors group">
                                    <td class="p-6">
                                        <h4 class="font-black text-navy mb-1"><?= htmlspecialchars($i['nama_toko']) ?></h4>
                                        <p class="text-[11px] text-gray-500 font-bold"><i class="fas fa-user-tie text-gray-300 mr-1.5"></i> <?= htmlspecialchars($i['nama_lengkap']) ?></p>
                                    </td>
                                    <td class="p-6 text-center">
                                        <span class="bg-green-100 text-green-600 px-3 py-1 rounded-full text-[9px] font-black uppercase tracking-widest border border-green-200 inline-flex items-center gap-1.5">
                                            <i class="fas fa-broadcast-tower"></i> Mengudara
                                        </span>
                                    </td>
                                    <td class="p-6 text-center">
                                        <p class="font-bold text-navy mb-0.5"><?= date('d M Y, H:i', strtotime($i['iklan_expires'])) ?></p>
                                        <?php if($is_today): ?>
                                            <p class="text-[10px] text-red font-black uppercase tracking-widest animate-pulse">Berakhir Hari Ini!</p>
                                        <?php else: ?>
                                            <p class="text-[10px] text-orange font-black uppercase tracking-widest">Sisa <?= $sisa_hari ?> Hari</p>
                                        <?php endif; ?>
                                    </td>
                                    <td class="p-6 text-right">
                                        <form action="" method="POST" class="inline">
                                            <input type="hidden" name="id_toko" value="<?= $i['id'] ?>">
                                            <button type="submit" name="cabut_iklan" onclick="return confirm('Are you sure you want to revoke this ad early?')" class="px-4 py-2 border border-gray-200 text-gray-500 hover:bg-red hover:text-white hover:border-red rounded-lg text-[10px] font-black uppercase tracking-widest transition-colors flex items-center gap-2 ml-auto">
                                                <i class="fas fa-power-off"></i> Cabut Manual
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="p-16 text-center">
                                        <div class="w-16 h-16 bg-gray-50 text-gray-300 rounded-full flex items-center justify-center text-2xl mx-auto mb-4"><i class="fas fa-sleep"></i></div>
                                        <h4 class="text-base font-black text-navy mb-1">No Active Ads</h4>
                                        <p class="text-sm text-gray-500 font-medium">Silakan aktifkan iklan dari daftar toko di bawah.</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- SEGMEN 2: AKTIFKAN IKLAN BARU -->
            <div class="bg-white rounded-[2rem] shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-gray-100 overflow-hidden" data-aos="fade-up" data-aos-delay="200">
                <div class="p-6 md:p-8 border-b border-gray-50 flex justify-between items-center">
                    <div>
                        <h3 class="text-lg font-black text-navy mb-1"><i class="fas fa-bolt text-orange mr-2"></i> Activate Ads Baru</h3>
                        <p class="text-xs text-gray-500 font-medium">Pilih toko yang sudah mentransfer pembayaran, lalu klik durasi paketnya.</p>
                    </div>
                </div>

                <div class="p-0 overflow-x-auto">
                    <table class="w-full text-left border-collapse min-w-[800px]">
                        <thead class="bg-gray-50/80">
                            <tr class="text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-100">
                                <th class="p-6">Store Name</th>
                                <th class="p-6">Pemilik</th>
                                <th class="p-6 text-right">Pilih Paket Durasi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50 text-sm">
                            <?php if(mysqli_num_rows($q_semua_toko) > 0): ?>
                                <?php while($s = mysqli_fetch_assoc($q_semua_toko)): ?>
                                <tr class="hover:bg-orange/5 transition-colors">
                                    <td class="p-6">
                                        <h4 class="font-black text-navy"><?= htmlspecialchars($s['nama_toko']) ?></h4>
                                    </td>
                                    <td class="p-6 font-bold text-gray-600">
                                        <?= htmlspecialchars($s['nama_lengkap']) ?>
                                    </td>
                                    <td class="p-6 text-right">
                                        <form action="" method="POST" class="flex justify-end gap-2">
                                            <input type="hidden" name="id_toko" value="<?= $s['id'] ?>">
                                            
                                            <!-- Tombol 7 Hari -->
                                            <button type="submit" name="aktifkan_iklan" value="true" onclick="document.getElementById('durasi_<?= $s['id'] ?>').value='7'; return confirm('Activekan Paket Starter (7 Hari) untuk toko ini?');" class="px-4 py-2.5 bg-white border border-gray-200 text-gray-500 hover:border-blue-500 hover:text-blue-600 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all shadow-sm">
                                                7 Hari
                                            </button>
                                            
                                            <!-- Tombol 30 Hari -->
                                            <button type="submit" name="aktifkan_iklan" value="true" onclick="document.getElementById('durasi_<?= $s['id'] ?>').value='30'; return confirm('Activekan Paket Pro (30 Hari) untuk toko ini?');" class="px-4 py-2.5 bg-orange text-white rounded-xl text-[10px] font-black uppercase tracking-widest transition-all shadow-md shadow-orange/20 hover:bg-navy">
                                                30 Hari
                                            </button>

                                            <!-- Tombol 90 Hari -->
                                            <button type="submit" name="aktifkan_iklan" value="true" onclick="document.getElementById('durasi_<?= $s['id'] ?>').value='90'; return confirm('Activekan Paket Master (90 Hari) untuk toko ini?');" class="px-4 py-2.5 bg-navy text-white rounded-xl text-[10px] font-black uppercase tracking-widest transition-all shadow-md hover:bg-blue-600">
                                                90 Hari
                                            </button>

                                            <input type="hidden" name="durasi_hari" id="durasi_<?= $s['id'] ?>" value="">
                                        </form>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="3" class="p-10 text-center text-gray-500 text-sm font-bold">Tidak ada toko tersedia untuk diiklankan saat ini.</td>
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