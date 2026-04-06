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

// 3. KALKULASI KEUANGAN TOKO
// A. Total Revenue Bersih (Completed Orders)
$q_saldo = mysqli_query($conn, "SELECT SUM(harga) as total_bersih FROM pesanan WHERE toko_id = '$toko_id' AND status = 'selesai'");
$saldo_aktif = mysqli_fetch_assoc($q_saldo)['total_bersih'] ?? 0;

// B. Potensi Pendapatan (Pesanan Sedang Diproses / Sudah Dibayar tapi belum selesai)
$q_pending = mysqli_query($conn, "SELECT SUM(harga) as total_pending FROM pesanan WHERE toko_id = '$toko_id' AND status IN ('sudah_dibayar', 'diproses')");
$saldo_pending = mysqli_fetch_assoc($q_pending)['total_pending'] ?? 0;

// C. Ambil Riwayat Transaksi (Completed Orders & Diproses)
$q_riwayat = mysqli_query($conn, "SELECT p.*, u.nama_lengkap as nama_pembeli, l.nama_layanan 
                                  FROM pesanan p 
                                  JOIN users u ON p.user_id = u.id 
                                  LEFT JOIN layanan l ON p.layanan_id = l.id
                                  WHERE p.toko_id = '$toko_id' AND p.status IN ('selesai', 'sudah_dibayar', 'diproses') 
                                  ORDER BY p.id DESC");

// Panggil Header
require_once '../includes/header.php'; 
?>

<!-- PUSTAKA ANIMASI & SWEETALERT -->
<link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<div class="bg-[#f4f7fa] min-h-screen pb-24 font-sans text-gray-800">

    <!-- HERO DASHBOARD BANNER -->
    <div class="bg-gradient-to-r from-navy to-[#111144] py-12 relative overflow-hidden shadow-lg">
        <div class="absolute top-0 right-0 w-64 h-64 bg-white/5 rounded-full blur-[80px] pointer-events-none"></div>
        <div class="max-w-screen-xl mx-auto px-5 md:px-8 relative z-10 flex flex-col md:flex-row justify-between items-center gap-6" data-aos="fade-down">
            <div class="flex items-center gap-5">
                <div class="w-16 h-16 bg-gradient-to-br from-green-400 to-green-600 rounded-2xl flex items-center justify-center text-white text-2xl shadow-xl transform -rotate-6">
                    <i class="fas fa-wallet"></i>
                </div>
                <div>
                    <h1 class="text-2xl md:text-3xl font-black text-white tracking-tight">Manajemen Finances</h1>
                    <p class="text-sm font-medium text-gray-400">Pantau saldo, pendapatan, dan riwayat transaksi toko Anda.</p>
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
                        <div class="w-8 h-8 rounded-full bg-gray-100 group-hover:bg-navy group-hover:text-white flex items-center justify-center transition-colors"><i class="fas fa-list-ul"></i></div>
                        Product Catalog
                    </a>
                    <a href="pesanan.php" class="text-gray-500 hover:bg-gray-50 hover:text-navy flex items-center gap-4 p-4 rounded-2xl font-bold text-sm transition-all group">
                        <div class="w-8 h-8 rounded-full bg-gray-100 group-hover:bg-navy group-hover:text-white flex items-center justify-center transition-colors"><i class="fas fa-shopping-bag"></i></div>
                        Incoming Orders
                    </a>
                    <a href="jadwal.php" class="text-gray-500 hover:bg-gray-50 hover:text-navy flex items-center gap-4 p-4 rounded-2xl font-bold text-sm transition-all group">
                        <div class="w-8 h-8 rounded-full bg-gray-100 group-hover:bg-navy group-hover:text-white flex items-center justify-center transition-colors"><i class="far fa-calendar-check"></i></div> 
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
                    <!-- Menu Active: Finances -->
                    <a href="keuangan.php" class="bg-orange/10 text-orange flex items-center gap-4 p-4 rounded-2xl font-black text-sm transition-all border border-orange/20">
                        <div class="w-8 h-8 rounded-full bg-orange text-white flex items-center justify-center shadow-md"><i class="fas fa-wallet"></i></div>
                        Finances
                    </a>
                </div>
            </div>
        </div>

        <!-- MAIN KONTEN KEUANGAN (KANAN) -->
        <div class="lg:col-span-9 space-y-6" data-aos="fade-up" data-aos-delay="100">

            <!-- WIDGET DOMPET -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Saldo Active -->
                <div class="bg-gradient-to-br from-navy to-blue-900 rounded-[2rem] p-8 shadow-xl shadow-navy/20 relative overflow-hidden border border-navy/50">
                    <div class="absolute -right-6 -bottom-6 text-white/5 text-9xl pointer-events-none"><i class="fas fa-wallet"></i></div>
                    <div class="relative z-10 flex flex-col h-full justify-between">
                        <p class="text-xs font-black text-blue-300 uppercase tracking-widest mb-2 flex items-center gap-2"><i class="fas fa-check-circle"></i> Saldo Active (Bisa Ditarik)</p>
                        <h2 class="text-4xl md:text-5xl font-black text-white italic tracking-tighter mb-6">Rp <?= number_format($saldo_aktif, 0, ',', '.') ?></h2>
                        <button onclick="tarikSaldo()" class="bg-white hover:bg-orange text-navy hover:text-white w-max px-8 py-3 rounded-xl font-black text-xs uppercase tracking-widest transition-colors shadow-lg transform hover:-translate-y-1">
                            <i class="fas fa-money-bill-wave mr-1"></i> Tarik Saldo
                        </button>
                    </div>
                </div>

                <!-- Potensi Pendapatan -->
                <div class="bg-white rounded-[2rem] p-8 shadow-sm border border-gray-100 flex flex-col justify-center">
                    <div class="w-12 h-12 bg-yellow-50 text-yellow-500 rounded-xl flex items-center justify-center text-xl mb-4 border border-yellow-100">
                        <i class="fas fa-hourglass-half animate-pulse"></i>
                    </div>
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Dana Tertahan (Pesanan Diproses)</p>
                    <h3 class="text-3xl font-black text-navy italic tracking-tight">Rp <?= number_format($saldo_pending, 0, ',', '.') ?></h3>
                    <p class="text-xs text-gray-500 font-medium mt-3 leading-relaxed">Dana ini akan otomatis masuk ke Saldo Active setelah Anda menandai pesanan sebagai <b>Selesai</b>.</p>
                </div>
            </div>

            <!-- TABEL RIWAYAT TRANSAKSI -->
            <div class="bg-white rounded-[2rem] shadow-sm border border-gray-100 overflow-hidden mt-8">
                <div class="p-6 md:p-8 border-b border-gray-50 bg-gray-50/50 flex justify-between items-center">
                    <div>
                        <h3 class="text-lg font-black text-navy mb-1"><i class="fas fa-history text-orange mr-2"></i> Riwayat Pemasukan</h3>
                        <p class="text-xs text-gray-500 font-medium">Catatan pendapatan dari pesanan yang dikerjakan.</p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse min-w-[600px]">
                        <thead class="bg-white">
                            <tr class="text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-100">
                                <th class="p-6">ID & Tanggal</th>
                                <th class="p-6">Customer & Layanan</th>
                                <th class="p-6">Status Dana</th>
                                <th class="p-6 text-right">Nominal</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <?php if(mysqli_num_rows($q_riwayat) > 0): ?>
                                <?php while($r = mysqli_fetch_assoc($q_riwayat)): 
                                    $nama_layanan_tampil = !empty($r['nama_layanan']) ? $r['nama_layanan'] : "Product Removed";
                                ?>
                                <tr class="hover:bg-gray-50 transition-colors group">
                                    <td class="p-6">
                                        <p class="text-[10px] font-black text-navy uppercase tracking-widest mb-1">#ORD-<?= str_pad($r['id'], 5, '0', STR_PAD_LEFT) ?></p>
                                        <p class="text-[9px] text-gray-400 font-bold tracking-widest"><?= date('d M Y, H:i', strtotime($r['tanggal_layanan'] . ' ' . $r['waktu_layanan'])) ?></p>
                                    </td>
                                    <td class="p-6">
                                        <p class="text-sm font-bold text-navy mb-0.5"><?= htmlspecialchars($r['nama_pembeli']) ?></p>
                                        <p class="text-[10px] text-gray-500 uppercase tracking-widest font-bold"><i class="fas fa-image text-orange mr-1"></i> <?= htmlspecialchars($nama_layanan_tampil) ?></p>
                                    </td>
                                    <td class="p-6">
                                        <?php if($r['status'] == 'selesai'): ?>
                                            <span class="bg-green-100 text-green-600 px-3 py-1 rounded-md text-[9px] font-black uppercase tracking-widest border border-green-200 inline-flex items-center gap-1.5"><i class="fas fa-check"></i> Masuk Dompet</span>
                                        <?php else: ?>
                                            <span class="bg-yellow-100 text-yellow-600 px-3 py-1 rounded-md text-[9px] font-black uppercase tracking-widest border border-yellow-200 inline-flex items-center gap-1.5"><i class="fas fa-lock"></i> Tertahan</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="p-6 text-right">
                                        <p class="text-base font-black italic <?= $r['status'] == 'selesai' ? 'text-green-600' : 'text-gray-400' ?>">
                                            + Rp <?= number_format($r['harga'], 0, ',', '.') ?>
                                        </p>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="p-16 text-center text-gray-400 font-bold text-sm">
                                        <i class="fas fa-receipt text-4xl text-gray-200 mb-3 block"></i>
                                        No transaction history yet.
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

    // Dummy Fungsi Tarik Saldo menggunakan SweetAlert2
    function tarikSaldo() {
        let saldoActive = <?= $saldo_aktif ?>;
        
        if(saldoActive <= 0) {
            Swal.fire({
                icon: 'error',
                title: 'Oops...',
                text: 'Saldo Anda masih kosong. Selesaikan pesanan untuk mendapatkan saldo!',
                confirmButtonColor: '#0a0a2a',
                confirmButtonText: 'Mengerti'
            });
            return;
        }

        Swal.fire({
            title: 'Tarik Saldo',
            text: 'Fitur Penarikan Otomatis (Payment Gateway) saat ini sedang dalam tahap integrasi oleh Developer. Silakan hubungi Admin untuk penarikan manual.',
            icon: 'info',
            showCancelButton: true,
            confirmButtonColor: '#ff6600',
            cancelButtonColor: '#0a0a2a',
            confirmButtonText: 'Hubungi Admin via WA',
            cancelButtonText: 'Tutup'
        }).then((result) => {
            if (result.isConfirmed) {
                // Arahkan ke WA Admin (Ganti nomor WA di bawah dengan nomor aslimu)
                window.open('https://wa.me/6281234567890?text=Hello%20BeautyScent,%20saya%20ingin%20melakukan%20penarikan%20saldo%20toko%20sebesar%20Rp%20<?= number_format($saldo_aktif, 0, ',', '.') ?>', '_blank');
            }
        });
    }
</script>

<?php require_once '../includes/footer.php'; ?>