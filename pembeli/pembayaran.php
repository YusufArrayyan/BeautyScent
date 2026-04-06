<?php
session_start();
require_once '../config/database.php';

// PENGHADANG: Cek apakah user sudah login dan ada kode pesanan di URL
if (!isset($_SESSION['user_id']) || !isset($_GET['kode'])) {
    header("Location: ../index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$kode_pesanan = mysqli_real_escape_string($conn, $_GET['kode']);

// 1. Ambil Data Pesanan
$query = mysqli_query($conn, "SELECT * FROM pesanan WHERE kode_pesanan = '$kode_pesanan' AND user_id = '$user_id'");
if (mysqli_num_rows($query) == 0) {
    header("Location: profil.php");
    exit();
}

$total_akhir = 0;
$metode = '';
$status = '';
$snap_token = '';

while ($row = mysqli_fetch_assoc($query)) {
    $total_akhir += $row['total_harga'];
    $metode = $row['metode_pembayaran'];
    $status = $row['status'];
    // Ambil Token Midtrans yang sudah digenerate saat Checkout
    if (!empty($row['snap_token'])) {
        $snap_token = $row['snap_token'];
    }
}
$total_akhir += 2000; // Ditambah Biaya Admin Aplikasi

require_once '../includes/header.php';
?>

<div class="bg-[#f4f7fa] min-h-screen flex items-center justify-center p-4 pt-10 pb-24 font-sans text-gray-800">
    <div class="max-w-md w-full bg-white rounded-[2rem] p-8 md:p-10 shadow-xl border border-gray-100 relative overflow-hidden">
        
        <?php if ($status == 'sudah_dibayar' || $status == 'diproses' || $status == 'selesai'): ?>
            <!-- ==========================================
                 TAMPILAN JIKA SUDAH BAYAR (UANG DI ESCROW)
            =========================================== -->
            <div class="absolute -right-10 -top-10 w-40 h-40 bg-green-500/10 rounded-full blur-2xl"></div>
            
            <div class="w-20 h-20 bg-green-500 text-white rounded-full flex items-center justify-center text-4xl mx-auto mb-6 shadow-lg shadow-green-500/30 relative z-10">
                <i class="fas fa-check"></i>
            </div>
            
            <div class="text-center relative z-10">
                <h2 class="text-2xl font-black text-navy mb-2">Pembayaran Berhasil!</h2>
                <p class="text-sm text-gray-500 font-medium mb-8">Your payment has been <b>securely held</b> by BeautyScent. The seller will process your order shortly.</p>
                
                <a href="pesanan.php" class="block w-full bg-navy hover:bg-orange text-white py-4 rounded-xl font-black text-xs uppercase tracking-widest transition-all shadow-lg shadow-navy/20 transform hover:-translate-y-1">
                    Check Order Status
                </a>
            </div>

        <?php else: ?>
            <!-- ==========================================
                 AWAITING MIDTRANS PAYMENT
            =========================================== -->
            <div class="text-center mb-8 border-b border-gray-100 pb-6">
                <h2 class="text-xl font-black text-navy mb-1">Selesaikan Pembayaran</h2>
                <p class="text-xs font-bold text-gray-500">ID Pesanan: <span class="text-orange font-black"><?= $kode_pesanan ?></span></p>
            </div>

            <div class="mb-10 text-center">
                <p class="text-[10px] uppercase font-black text-gray-400 mb-2">Total Tagihan (Termasuk Layanan)</p>
                <h1 class="text-4xl font-black text-orange">Rp <?= number_format($total_akhir, 0, ',', '.') ?></h1>
            </div>

            <?php if (strpos($metode, 'COD') !== false): ?>
                <!-- KHUSUS COD -->
                <div class="bg-green-50 p-6 rounded-2xl border border-green-200 mb-8 text-center">
                    <div class="w-12 h-12 bg-green-100 text-green-600 rounded-full flex items-center justify-center text-xl mx-auto mb-3">
                        <i class="fas fa-handshake"></i>
                    </div>
                    <p class="text-sm text-navy font-bold mb-1">Metode Bayar di Tempat</p>
                    <p class="text-[10px] text-gray-500 font-medium">Siapkan uang tunai sesuai tagihan di atas saat seller tiba di lokasi Anda.</p>
                </div>
                <a href="pesanan.php" class="block w-full text-center bg-navy hover:bg-orange text-white py-4 rounded-xl font-black text-xs uppercase tracking-widest transition-colors shadow-lg">Lihat Pesanan Saya</a>

            <?php else: ?>
                <!-- KHUSUS PEMBAYARAN ONLINE (MIDTRANS) -->
                
                <?php if(!empty($snap_token)): ?>
                    <button id="pay-button" class="w-full bg-[#00AEEF] hover:bg-[#008ec2] text-white py-4 rounded-xl font-black text-sm uppercase tracking-widest transition-all flex items-center justify-center gap-2 transform hover:-translate-y-1 shadow-xl shadow-[#00AEEF]/30">
                        <i class="fas fa-credit-card"></i> Bayar Via Midtrans
                    </button>
                    
                    <p class="text-[9px] text-center text-gray-400 mt-4 font-bold flex items-center justify-center gap-1">
                        <i class="fas fa-shield-alt text-green-500"></i> Transaksi Aman, Dilindungi oleh Midtrans
                    </p>
                <?php else: ?>
                    <div class="bg-red-50 p-5 rounded-xl border border-red-200 text-center">
                        <i class="fas fa-exclamation-triangle text-red-500 text-2xl mb-2"></i>
                        <p class="text-xs text-red-600 font-bold">Failed mengambil kode pembayaran.</p>
                        <p class="text-[10px] text-red-400 mt-1">Pastikan API Key Midtrans sudah di-setting dengan benar di kode Anda.</p>
                    </div>
                <?php endif; ?>
                
                <a href="pesanan.php" class="block w-full text-center text-xs font-bold text-gray-400 mt-8 hover:text-orange transition-colors">Bayar Nanti (Kembali ke My Orders)</a>
            
            <?php endif; ?>
        <?php endif; ?>

    </div>
</div>

<!-- ==========================================
     SCRIPT RESMI MIDTRANS SNAP
=========================================== -->
<!-- !!! MASUKKAN CLIENT KEY SANDBOX MIDTRANS BOSKU DI BAWAH INI !!! -->
<script src="https://app.sandbox.midtrans.com/snap/snap.js" data-client-key="SB-Mid-client-WNGSMZ0f_iYtbFog"></script>
<script type="text/javascript">
    var payButton = document.getElementById('pay-button');
    if(payButton) {
        payButton.onclick = function(){
            // Memicu popup Midtrans berdasarkan snap_token yang digenerate di checkout.php
            snap.pay('<?= $snap_token ?>', {
                onSuccess: function(result){
                    // Action jika pembayaran sukses. 
                    // (Nanti di Production, perubahan status DB dilakukan oleh Webhook/Notifikasi Midtrans, bukan lewat front-end ini demi keamanan)
                    alert("Pembayaran Berhasil! Pesanan Anda akan segera diproses.");
                    window.location.href = "pesanan.php"; // Arahkan ke daftar pesanan
                },
                onPending: function(result){
                    alert("Menunggu pembayaran Anda diselesaikan di aplikasi/ATM.");
                },
                onError: function(result){
                    alert("Maaf, pembayaran gagal. Silakan coba lagi.");
                },
                onClose: function(){
                    console.log('Anda menutup popup sebelum menyelesaikan pembayaran.');
                }
            });
        };
    }
</script>

<?php require_once '../includes/footer.php'; ?>