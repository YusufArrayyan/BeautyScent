<?php
session_start();
require_once '../config/database.php';

// Wajib Login sebagai Pembeli/User
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php?redirect=pembeli/keranjang.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$pesan_sukses = '';
$pesan_error = '';

// ==========================================
// FITUR HAPUS ITEM DARI KERANJANG
// ==========================================
if (isset($_POST['hapus_item'])) {
    $keranjang_id = mysqli_real_escape_string($conn, $_POST['keranjang_id']);
    $q_hapus = mysqli_query($conn, "DELETE FROM keranjang WHERE id = '$keranjang_id' AND user_id = '$user_id'");
    if ($q_hapus) {
        $pesan_sukses = "Layanan successfully dihapus dari keranjang.";
    } else {
        $pesan_error = "Failed menghapus layanan.";
    }
}

// ==========================================
// FITUR UPDATE JUMLAH/CATATAN
// ==========================================
if (isset($_POST['update_item'])) {
    $keranjang_id = mysqli_real_escape_string($conn, $_POST['keranjang_id']);
    $jumlah = (int)$_POST['jumlah'];
    $catatan = mysqli_real_escape_string($conn, $_POST['catatan']);
    
    if ($jumlah > 0) {
        mysqli_query($conn, "UPDATE keranjang SET jumlah = '$jumlah', catatan = '$catatan' WHERE id = '$keranjang_id' AND user_id = '$user_id'");
    }
}

// ==========================================
// AMBIL DATA KERANJANG DARI DATABASE
// ==========================================
// Kita JOIN tabel keranjang dengan layanan dan toko
$query_keranjang = mysqli_query($conn, "
    SELECT k.*, l.nama_layanan, t.nama_toko 
    FROM keranjang k 
    JOIN layanan l ON k.layanan_id = l.id 
    JOIN toko t ON k.toko_id = t.id 
    WHERE k.user_id = '$user_id' 
    ORDER BY k.created_at DESC
");

$total_belanja = 0;
$total_item = 0;

require_once '../includes/header.php';
?>

<div class="bg-[#f4f7fa] min-h-screen pb-32 font-sans text-gray-800 pt-6 md:pt-10">
    <div class="max-w-screen-md mx-auto px-4 md:px-8">
        
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-2xl md:text-3xl font-black text-navy tracking-tight">Keranjang Anda</h2>
            <span class="bg-orange/10 text-orange px-3 py-1 rounded-lg text-xs font-black uppercase tracking-widest border border-orange/20">
                <i class="fas fa-shopping-cart mr-1"></i> <span id="header-total-item"><?= mysqli_num_rows($query_keranjang) ?></span> Item
            </span>
        </div>

        <?php if($pesan_sukses): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-xl mb-6 text-sm font-bold flex items-center gap-3">
                <i class="fas fa-check-circle"></i> <?= $pesan_sukses ?>
            </div>
        <?php endif; ?>

        <?php if($pesan_error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-xl mb-6 text-sm font-bold flex items-center gap-3">
                <i class="fas fa-exclamation-circle"></i> <?= $pesan_error ?>
            </div>
        <?php endif; ?>

        <?php if(mysqli_num_rows($query_keranjang) == 0): ?>
            <!-- JIKA KERANJANG KOSONG -->
            <div class="bg-white rounded-[2rem] p-10 shadow-sm border border-gray-50 text-center flex flex-col items-center justify-center min-h-[400px]">
                <div class="w-32 h-32 bg-gray-50 rounded-full flex items-center justify-center mb-6">
                    <i class="fas fa-shopping-basket text-6xl text-gray-300"></i>
                </div>
                <h3 class="text-xl font-black text-navy mb-2">Keranjang Masih Kosong</h3>
                <p class="text-sm text-gray-500 font-medium mb-8 max-w-xs">Sepertinya Anda belum menambahkan layanan apapun. Yuk, cari seller terbaik untuk masalah Anda!</p>
                <a href="<?= $base_url ?>/kategori.php?k=semua" class="bg-navy hover:bg-orange text-white px-8 py-4 rounded-xl font-black text-xs uppercase tracking-widest transition-colors shadow-lg">
                    Mulai Cari Layanan
                </a>
            </div>
        <?php else: ?>
            <!-- JIKA KERANJANG ADA ISINYA -->
            <div class="space-y-4">
                <?php 
                while($item = mysqli_fetch_assoc($query_keranjang)): 
                    $subtotal = $item['harga_satuan'] * $item['jumlah'];
                    $total_belanja += $subtotal;
                    $total_item += $item['jumlah'];
                ?>
                <div class="bg-white rounded-[1.5rem] p-5 md:p-6 shadow-sm border border-gray-100 hover:shadow-md transition-shadow relative">
                    
                    <div class="flex items-start gap-4 mb-4">
                        <!-- Icon Jasa -->
                        <div class="w-16 h-16 md:w-20 md:h-20 bg-orange/10 text-orange rounded-2xl flex items-center justify-center shrink-0 border border-orange/20">
                            <i class="fas fa-image text-2xl md:text-3xl"></i>
                        </div>
                        
                        <div class="flex-1">
                            <h4 class="font-black text-navy text-base md:text-lg leading-tight mb-1"><?= htmlspecialchars($item['nama_layanan']) ?></h4>
                            <p class="text-xs text-gray-500 font-bold mb-2 flex items-center gap-1"><i class="fas fa-store text-gray-400"></i> <?= htmlspecialchars($item['nama_toko']) ?></p>
                            <p class="font-black text-orange text-sm md:text-base">Rp <?= number_format($item['harga_satuan'], 0, ',', '.') ?></p>
                        </div>
                    </div>

                    <!-- Form Update Qty & Catatan -->
                    <form action="" method="POST" class="bg-gray-50 p-4 rounded-xl border border-gray-200 flex flex-col md:flex-row gap-4 items-end md:items-center">
                        <input type="hidden" name="keranjang_id" value="<?= $item['id'] ?>">
                        
                        <div class="w-full md:flex-1">
                            <label class="block text-[9px] font-black text-navy uppercase tracking-widest mb-1.5 pl-1">Catatan untuk seller (Opsional)</label>
                            <div class="relative group">
                                <i class="far fa-edit absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                                <input type="text" name="catatan" value="<?= htmlspecialchars($item['catatan']) ?>" placeholder="Misal: AC merk Sharp bocor..." class="w-full pl-9 pr-3 py-2 bg-white border border-gray-200 rounded-lg focus:outline-none focus:border-orange text-xs font-medium">
                            </div>
                        </div>

                        <div class="flex items-center gap-3 w-full md:w-auto justify-between md:justify-end">
                            <div class="flex items-center border border-gray-300 bg-white rounded-lg overflow-hidden">
                                <button type="button" onclick="ubahQty(this, -1)" class="w-8 h-8 flex items-center justify-center bg-gray-50 hover:bg-gray-200 text-gray-600 transition-colors"><i class="fas fa-minus text-xs"></i></button>
                                <input type="number" name="jumlah" value="<?= $item['jumlah'] ?>" min="1" class="w-10 h-8 text-center text-sm font-black text-navy focus:outline-none border-x border-gray-300 appearance-none bg-white" readonly>
                                <button type="button" onclick="ubahQty(this, 1)" class="w-8 h-8 flex items-center justify-center bg-gray-50 hover:bg-gray-200 text-gray-600 transition-colors"><i class="fas fa-plus text-xs"></i></button>
                            </div>
                            
                            <div class="flex items-center gap-2">
                                <button type="submit" name="update_item" class="w-8 h-8 bg-blue-50 text-blue-600 hover:bg-blue-500 hover:text-white rounded-lg flex items-center justify-center transition-colors" title="Save Changes"><i class="fas fa-save text-sm"></i></button>
                                <button type="submit" name="hapus_item" onclick="return confirm('Hapus layanan ini dari keranjang?')" class="w-8 h-8 bg-red-50 text-red-500 hover:bg-red-500 hover:text-white rounded-lg flex items-center justify-center transition-colors" title="Hapus"><i class="fas fa-trash-alt text-sm"></i></button>
                            </div>
                        </div>
                    </form>
                </div>
                <?php endwhile; ?>
            </div>

            <!-- STICKY BOTTOM BAR UNTUK CHECKOUT -->
            <div class="fixed bottom-0 md:bottom-5 left-0 w-full z-30 px-0 md:px-4 pointer-events-none">
                <div class="max-w-screen-md mx-auto pointer-events-auto">
                    <div class="bg-white md:rounded-2xl border-t md:border border-gray-200 shadow-[0_-10px_40px_rgba(0,0,0,0.08)] p-4 md:p-5 flex flex-col sm:flex-row justify-between items-center gap-4">
                        <div class="w-full sm:w-auto flex justify-between sm:block">
                            <p class="text-[10px] uppercase font-black tracking-widest text-gray-400 mb-0.5">Total Pembayaran</p>
                            <p class="text-xl md:text-2xl font-black text-orange">Rp <?= number_format($total_belanja, 0, ',', '.') ?></p>
                        </div>
                        <a href="<?= $base_url ?>/pembeli/checkout.php" class="w-full sm:w-auto bg-navy hover:bg-blue-900 text-white px-8 py-3.5 md:py-4 rounded-xl font-black text-xs uppercase tracking-widest transition-all text-center flex items-center justify-center gap-2 shadow-lg shadow-navy/20">
                            Checkout Sekarang <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Padding tambahan agar konten terakhir tidak tertutup sticky bar -->
            <div class="h-24 md:h-10"></div>
        <?php endif; ?>

    </div>
</div>

<script>
    // Fungsi untuk tombol Plus/Minus Qty
    function ubahQty(btn, aksi) {
        let input = btn.parentElement.querySelector('input[type="number"]');
        let nilaiSekarang = parseInt(input.value);
        let nilaiBaru = nilaiSekarang + aksi;
        
        if (nilaiBaru >= 1) {
            input.value = nilaiBaru;
            // Highlight tombol save agar user ingat untuk menyimpan
            let form = btn.closest('form');
            let btnSave = form.querySelector('button[name="update_item"]');
            btnSave.classList.add('animate-pulse', 'ring-2', 'ring-blue-400');
        }
    }
</script>

<?php require_once '../includes/footer.php'; ?>