<?php
require_once '../config/database.php';
session_start();

// Proteksi: Cek apakah yang login adalah Store/Mitra
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'toko') {
    header("Location: ../auth/login.php"); exit();
}

$user_id = $_SESSION['user_id'];
// Ambil ID Store berdasarkan user yang login
$toko = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id FROM toko WHERE user_id = '$user_id'"));
$toko_id = $toko['id'];

// 1. Logika Add New Product
if (isset($_POST['tambah_jasa'])) {
    $nama = mysqli_real_escape_string($conn, $_POST['nama_layanan']);
    $desc = mysqli_real_escape_string($conn, $_POST['deskripsi']);
    $harga = mysqli_real_escape_string($conn, $_POST['harga']);
    $stok = mysqli_real_escape_string($conn, $_POST['stok']); // [TAMBAHAN] Ambil data stok dari form

    // [TAMBAHAN] Masukkan field stok dan value-nya ke query INSERT
    mysqli_query($conn, "INSERT INTO layanan (toko_id, nama_layanan, deskripsi_layanan, harga, stok) 
                         VALUES ('$toko_id', '$nama', '$desc', '$harga', '$stok')");
    header("Location: layanan.php?status=sukses");
    exit();
}

// 2. Logika Hapus Jasa
if (isset($_GET['hapus'])) {
    $id_layanan = mysqli_real_escape_string($conn, $_GET['hapus']);
    // Pastikan hanya bisa menghapus jasa miliknya sendiri
    mysqli_query($conn, "DELETE FROM layanan WHERE id = '$id_layanan' AND toko_id = '$toko_id'");
    header("Location: layanan.php");
    exit();
}

// 3. Ambil Daftar Jasa Milik Store Ini Saja
$query = mysqli_query($conn, "SELECT * FROM layanan WHERE toko_id = '$toko_id' ORDER BY id DESC");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Products - BeautyScent Seller</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script>
        tailwind.config = { theme: { extend: { colors: { navy: '#0b0b3a', orange: '#ff6600' } } } }
    </script>
</head>
<body class="bg-gray-100 flex font-sans text-sm text-navy min-h-screen">

    <div class="w-72 bg-navy min-h-screen text-white p-8 sticky top-0 h-screen shadow-2xl flex flex-col">
        <h2 class="text-2xl font-black mb-12 uppercase italic tracking-tighter text-orange">MITRA <span class="text-white">GALO</span></h2>
        
        <nav class="space-y-3 font-bold flex-1">
            <a href="index.php" class="flex items-center gap-4 p-4 hover:bg-white/10 rounded-2xl transition text-gray-400"><i class="fas fa-home w-5"></i> Ringkasan</a>
            <a href="layanan.php" class="flex items-center gap-4 bg-orange p-4 rounded-2xl shadow-lg transition-all text-white"><i class="fas fa-image w-5"></i> Manage Products</a>
            <a href="pesanan.php" class="flex items-center gap-4 p-4 hover:bg-white/10 rounded-2xl transition text-gray-400"><i class="fas fa-shopping-cart w-5"></i> Incoming Orders</a>
            <a href="profil_toko.php" class="flex items-center gap-4 p-4 hover:bg-white/10 rounded-2xl transition text-gray-400"><i class="fas fa-store w-5"></i> Store Profile</a>
        </nav>

        <div>
            <hr class="border-white/10 mb-6">
            <a href="../auth/logout.php" class="flex items-center gap-4 p-4 text-red-400 hover:bg-red-500/10 rounded-2xl font-bold transition"><i class="fas fa-power-off w-5"></i> Keluar Akun</a>
        </div>
    </div>

    <div class="flex-1 p-10">
        <header class="mb-10">
            <h1 class="text-4xl font-black uppercase tracking-tighter">Manajemen Jasa & Harga</h1>
            <p class="text-gray-500 font-medium italic">Atur daftar layanan yang kamu tawarkan ke customer.</p>
        </header>

        <?php if(isset($_GET['status']) && $_GET['status'] == 'sukses'): ?>
            <div class="bg-green-500 text-white p-4 rounded-2xl mb-8 shadow-lg font-bold flex items-center gap-3">
                <i class="fas fa-check-circle text-xl"></i> Jasa successfully ditambahkan ke etalase!
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-10 text-sm">
            
            <div class="lg:col-span-1">
                <div class="bg-white p-8 rounded-[2.5rem] shadow-sm border border-gray-100 sticky top-10">
                    <h3 class="text-xl font-black mb-6 italic uppercase tracking-tighter flex items-center gap-2">
                        <i class="fas fa-plus-circle text-orange"></i> Pasang Jasa Baru
                    </h3>
                    <form action="" method="POST" class="space-y-5">
                        <div>
                            <label class="text-[10px] font-black uppercase text-gray-400 block mb-2">Product Name</label>
                            <input type="text" name="nama_layanan" placeholder="Misal: Ganti Freon AC Split" required 
                                   class="w-full bg-gray-50 border border-gray-200 p-4 rounded-2xl outline-none focus:ring-2 focus:ring-orange transition font-bold text-navy">
                        </div>
                        <div>
                            <label class="text-[10px] font-black uppercase text-gray-400 block mb-2">Harga Jasa (Rp)</label>
                            <input type="number" name="harga" placeholder="75000" required min="0"
                                   class="w-full bg-gray-50 border border-gray-200 p-4 rounded-2xl outline-none focus:ring-2 focus:ring-orange transition font-black text-orange">
                        </div>
                        
                        <div>
                            <label class="text-[10px] font-black uppercase text-gray-400 block mb-2">Stok Tersedia</label>
                            <input type="number" name="stok" placeholder="Misal: 50" required min="0"
                                   class="w-full bg-gray-50 border border-gray-200 p-4 rounded-2xl outline-none focus:ring-2 focus:ring-orange transition font-bold text-navy">
                        </div>

                        <div>
                            <label class="text-[10px] font-black uppercase text-gray-400 block mb-2">Deskripsi Pekerjaan</label>
                            <textarea name="deskripsi" rows="4" placeholder="Jelaskan detail yang dikerjakan dan apakah sudah termasuk sparepart..." required 
                                      class="w-full bg-gray-50 border border-gray-200 p-4 rounded-2xl outline-none focus:ring-2 focus:ring-orange transition leading-relaxed"></textarea>
                        </div>
                        <button type="submit" name="tambah_jasa" class="w-full bg-navy text-white p-4 rounded-2xl font-black hover:bg-orange transition shadow-xl uppercase tracking-widest text-xs flex items-center justify-center gap-2">
                            <i class="fas fa-paper-plane"></i> Tayangkan Jasa
                        </button>
                    </form>
                </div>
            </div>

            <div class="lg:col-span-2 space-y-4">
                <h3 class="text-xl font-black mb-2 italic uppercase tracking-tighter text-navy border-l-4 border-orange pl-3">Daftar Layanan Active</h3>
                
                <?php if(mysqli_num_rows($query) == 0): ?>
                    <div class="bg-white p-12 rounded-[2.5rem] shadow-sm border border-dashed border-gray-300 text-center">
                        <div class="w-20 h-20 bg-gray-50 text-gray-300 rounded-full flex items-center justify-center mx-auto text-4xl mb-4">
                            <i class="fas fa-box-open"></i>
                        </div>
                        <h4 class="font-black text-gray-400 uppercase tracking-widest">No products added yet</h4>
                        <p class="text-xs text-gray-400 mt-2">Silakan isi form di sebelah kiri untuk mulai menawarkan jasa.</p>
                    </div>
                <?php endif; ?>

                <?php while($l = mysqli_fetch_assoc($query)): ?>
                <div class="bg-white p-6 rounded-[2.5rem] shadow-sm border border-gray-100 flex flex-col sm:flex-row sm:items-center justify-between group hover:shadow-xl hover:border-orange/30 transition-all gap-4">
                    <div class="flex items-start sm:items-center gap-6">
                        <div class="w-16 h-16 shrink-0 bg-orange/5 text-orange rounded-3xl flex items-center justify-center text-2xl shadow-inner border group-hover:bg-orange group-hover:text-white transition">
                            <i class="fas fa-cog"></i>
                        </div>
                        <div>
                            <h4 class="font-black uppercase tracking-tight text-lg leading-none mb-1"><?= htmlspecialchars($l['nama_layanan']) ?></h4>
                            <p class="text-xs text-gray-500 mb-3 line-clamp-2 leading-relaxed"><?= htmlspecialchars($l['deskripsi_layanan']) ?></p>
                            
                            <div class="flex items-center gap-4">
                                <p class="font-black italic text-orange text-lg">Rp <?= number_format($l['harga'], 0, ',', '.') ?></p>
                                
                                <span class="bg-gray-100 text-gray-600 px-3 py-1 rounded-full text-xs font-bold border border-gray-200">
                                    <i class="fas fa-box mr-1"></i> Stok: <?= htmlspecialchars($l['stok'] ?? 0) ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    <a href="?hapus=<?= $l['id'] ?>" onclick="return confirm('Are you sure you want to delete this product?')" 
                       class="shrink-0 w-12 h-12 bg-red-50 text-red-400 rounded-2xl flex items-center justify-center hover:bg-red-500 hover:text-white transition shadow-sm ml-auto sm:ml-0" title="Hapus Jasa">
                        <i class="fas fa-trash-alt"></i>
                    </a>
                </div>
                <?php endwhile; ?>
            </div>

        </div>
    </div>
</body>
</html>