<?php 
require_once '../config/database.php';
session_start();

// 1. KEAMANAN: Cek Login & Role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'toko') {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// AMBIL DATA TOKO
$query_toko = mysqli_query($conn, "SELECT * FROM toko WHERE user_id = '$user_id'");
$toko = mysqli_fetch_assoc($query_toko);
if (!$toko) { die("Store data not found."); }
$toko_id = $toko['id'];

// Folder Upload Layanan
$upload_dir = '../uploads/layanan/';
if (!file_exists($upload_dir)) { mkdir($upload_dir, 0777, true); }

// 3. LOGIKA TAMBAH JASA (DENGAN FOTO, HARGA CORET & STOK)
if (isset($_POST['tambah_layanan'])) {
    $nama_layanan = mysqli_real_escape_string($conn, trim($_POST['nama_layanan']));
    $harga        = mysqli_real_escape_string($conn, $_POST['harga']);
    $harga_coret  = !empty($_POST['harga_coret']) ? mysqli_real_escape_string($conn, $_POST['harga_coret']) : 0;
    $stok         = !empty($_POST['stok']) ? mysqli_real_escape_string($conn, $_POST['stok']) : 0; // [TAMBAHAN] Ambil Stok
    $deskripsi    = mysqli_real_escape_string($conn, trim($_POST['deskripsi_layanan']));
    
    // Proses Upload Foto
    $foto_layanan = "";
    if (isset($_FILES['foto_layanan']) && $_FILES['foto_layanan']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['foto_layanan']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
            $foto_layanan = uniqid('jasa_') . '.' . $ext;
            move_uploaded_file($_FILES['foto_layanan']['tmp_name'], $upload_dir . $foto_layanan);
        }
    }

    // [TAMBAHAN] Masukkan stok ke query INSERT
    $query_insert = "INSERT INTO layanan (toko_id, nama_layanan, harga, harga_coret, stok, deskripsi_layanan, foto_layanan) 
                     VALUES ('$toko_id', '$nama_layanan', '$harga', '$harga_coret', '$stok', '$deskripsi', '$foto_layanan')";
    
    if (mysqli_query($conn, $query_insert)) { $_SESSION['pesan_sukses'] = "New product added successfully!"; } 
    else { $_SESSION['pesan_error'] = "Failed to add product."; }
    header("Location: index.php"); exit();
}

// 4. LOGIKA EDIT JASA (DENGAN FOTO, HARGA CORET & STOK)
if (isset($_POST['edit_layanan'])) {
    $id_layanan   = mysqli_real_escape_string($conn, $_POST['id_layanan']);
    $nama_layanan = mysqli_real_escape_string($conn, trim($_POST['nama_layanan']));
    $harga        = mysqli_real_escape_string($conn, $_POST['harga']);
    $harga_coret  = !empty($_POST['harga_coret']) ? mysqli_real_escape_string($conn, $_POST['harga_coret']) : 0;
    $stok         = !empty($_POST['stok']) ? mysqli_real_escape_string($conn, $_POST['stok']) : 0; // [TAMBAHAN] Ambil Stok
    $deskripsi    = mysqli_real_escape_string($conn, trim($_POST['deskripsi_layanan']));

    // Proses Upload Foto Baru (Jika Ada)
    $query_foto_tambahan = "";
    if (isset($_FILES['foto_layanan']) && $_FILES['foto_layanan']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['foto_layanan']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
            $foto_layanan = uniqid('jasa_') . '.' . $ext;
            if (move_uploaded_file($_FILES['foto_layanan']['tmp_name'], $upload_dir . $foto_layanan)) {
                $query_foto_tambahan = ", foto_layanan='$foto_layanan'";
            }
        }
    }

    // [TAMBAHAN] Update field stok di database
    $query_update = "UPDATE layanan SET nama_layanan='$nama_layanan', harga='$harga', harga_coret='$harga_coret', stok='$stok', deskripsi_layanan='$deskripsi' $query_foto_tambahan 
                     WHERE id='$id_layanan' AND toko_id='$toko_id'";
    
    if (mysqli_query($conn, $query_update)) { $_SESSION['pesan_sukses'] = "Product updated successfully!"; } 
    else { $_SESSION['pesan_error'] = "Failed to update product."; }
    header("Location: index.php"); exit();
}

// 5. LOGIKA HAPUS JASA
if (isset($_POST['hapus_layanan'])) {
    $id_layanan = mysqli_real_escape_string($conn, $_POST['id_layanan']);
    if (mysqli_query($conn, "DELETE FROM layanan WHERE id = '$id_layanan' AND toko_id = '$toko_id'")) {
        $_SESSION['pesan_sukses'] = "Product deleted successfully!";
    }
    header("Location: index.php"); exit();
}

// AMBIL DATA LAYANAN
$query_layanan = mysqli_query($conn, "SELECT * FROM layanan WHERE toko_id = '$toko_id' ORDER BY id DESC");
$total_layanan = mysqli_num_rows($query_layanan);

require_once '../includes/header.php'; 
?>

<link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">

<div class="bg-[#f4f7fa] min-h-screen pb-24 font-sans text-gray-800">

    <div class="bg-gradient-to-r from-navy to-[#111144] py-12 relative overflow-hidden shadow-lg">
        <div class="absolute top-0 right-0 w-64 h-64 bg-white/5 rounded-full blur-[80px] pointer-events-none"></div>
        <div class="max-w-screen-xl mx-auto px-5 md:px-8 relative z-10 flex flex-col md:flex-row justify-between items-center gap-6" data-aos="fade-down">
            <div class="flex items-center gap-5">
                <div class="w-16 h-16 bg-gradient-to-br from-orange to-red-500 rounded-2xl flex items-center justify-center text-white text-2xl shadow-xl transform -rotate-6">
                    <i class="fas fa-store"></i>
                </div>
                <div>
                    <h1 class="text-2xl md:text-3xl font-black text-white tracking-tight">Seller Dashboard</h1>
                    <p class="text-sm font-medium text-gray-400">Welcome back, <span class="text-white font-bold"><?= htmlspecialchars($toko['nama_toko']) ?></span>!</p>
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
        
        <div class="lg:col-span-3 space-y-4" data-aos="fade-right">
            <div class="bg-white rounded-[2rem] shadow-sm border border-gray-100 p-4 sticky top-28">
                <div class="flex flex-col gap-2">
                    <a href="index.php" class="bg-orange/10 text-orange flex items-center gap-4 p-4 rounded-2xl font-black text-sm transition-all border border-orange/20">
                        <div class="w-8 h-8 rounded-full bg-orange text-white flex items-center justify-center shadow-md"><i class="fas fa-list-ul"></i></div> Product Catalog
                    </a>
                    <a href="pesanan.php" class="text-gray-500 hover:bg-gray-50 hover:text-navy flex items-center gap-4 p-4 rounded-2xl font-bold text-sm transition-all group">
                        <div class="w-8 h-8 rounded-full bg-gray-100 group-hover:bg-navy group-hover:text-white flex items-center justify-center transition-colors"><i class="fas fa-shopping-bag"></i></div> Incoming Orders
                    </a>
                    <a href="jadwal.php" class="text-gray-500 hover:bg-gray-50 hover:text-navy flex items-center gap-4 p-4 rounded-2xl font-bold text-sm transition-all group">
                        <div class="w-8 h-8 rounded-full bg-gray-100 group-hover:bg-navy group-hover:text-white flex items-center justify-center transition-colors"><i class="far fa-calendar-check"></i></div> Schedule & Queue
                    </a>
                    <a href="promosi.php" class="text-gray-500 hover:bg-gray-50 hover:text-navy flex items-center gap-4 p-4 rounded-2xl font-bold text-sm transition-all group">
                        <div class="w-8 h-8 rounded-full bg-gray-100 group-hover:bg-navy group-hover:text-white flex items-center justify-center transition-colors"><i class="fas fa-bullhorn"></i></div> Ads & Promotions
                    </a>
                    <a href="profil_toko.php" class="text-gray-500 hover:bg-gray-50 hover:text-navy flex items-center gap-4 p-4 rounded-2xl font-bold text-sm transition-all group">
                        <div class="w-8 h-8 rounded-full bg-gray-100 group-hover:bg-navy group-hover:text-white flex items-center justify-center transition-colors"><i class="fas fa-id-card"></i></div> Store Profile
                    </a>
                    <a href="pengaturan_maps.php" class="text-gray-500 hover:bg-gray-50 hover:text-navy flex items-center gap-4 p-4 rounded-2xl font-bold text-sm transition-all group">
                        <div class="w-8 h-8 rounded-full bg-gray-100 group-hover:bg-navy group-hover:text-white flex items-center justify-center transition-colors"><i class="fas fa-map-marked-alt"></i></div> Map Settings
                    </a>
                    <a href="keuangan.php" class="text-gray-500 hover:bg-gray-50 hover:text-navy flex items-center gap-4 p-4 rounded-2xl font-bold text-sm transition-all group">
                        <div class="w-8 h-8 rounded-full bg-gray-100 group-hover:bg-navy group-hover:text-white flex items-center justify-center transition-colors"><i class="fas fa-wallet"></i></div> Finances
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
            <?php if (isset($_SESSION['pesan_error'])): ?>
                <div class="bg-red-50 border border-red-200 text-red-700 px-6 py-4 rounded-2xl text-sm font-bold flex items-center gap-3 shadow-sm">
                    <i class="fas fa-exclamation-circle text-xl"></i> <?= $_SESSION['pesan_error']; unset($_SESSION['pesan_error']); ?>
                </div>
            <?php endif; ?>

            <div class="bg-white rounded-[2rem] p-6 md:p-8 shadow-sm border border-gray-100 flex flex-col sm:flex-row justify-between sm:items-center gap-6">
                <div>
                    <h2 class="text-2xl font-black text-navy mb-1 flex items-center gap-3">
                        Product Catalog <span class="bg-gray-100 text-gray-500 px-3 py-1 rounded-full text-[10px] uppercase"><?= $total_layanan ?> Products</span>
                    </h2>
                    <p class="text-sm text-gray-500 font-medium">Manage your product listings, prices, and promotions.</p>
                </div>
                <button onclick="toggleModal('modalTambah')" class="bg-navy hover:bg-orange text-white px-6 md:px-8 py-3.5 rounded-xl font-black text-xs uppercase tracking-widest transition-all shadow-xl shadow-navy/20 flex items-center justify-center gap-3 shrink-0 transform hover:-translate-y-1">
                    <i class="fas fa-plus-circle text-lg"></i> Add Product
                </button>
            </div>

            <?php if($total_layanan > 0): ?>
                <div class="grid grid-cols-1 gap-6">
                    <?php while($l = mysqli_fetch_assoc($query_layanan)): ?>
                        <div class="bg-white rounded-[2rem] p-4 md:p-6 shadow-[0_4px_20px_rgba(0,0,0,0.03)] border border-gray-100 group hover:border-orange/50 transition-all duration-300 flex flex-col md:flex-row gap-6 items-center relative overflow-hidden">
                            
                            <?php if(isset($l['harga_coret']) && $l['harga_coret'] > $l['harga']): ?>
                                <div class="absolute top-4 left-[-35px] bg-red text-white text-[8px] font-black px-10 py-1 rotate-[-45deg] uppercase tracking-widest shadow-md z-10">Promo</div>
                            <?php endif; ?>

                            <div class="w-full md:w-48 h-40 shrink-0 rounded-2xl overflow-hidden bg-gray-100 border border-gray-200 relative">
                                <?php if(!empty($l['foto_layanan'])): ?>
                                    <img src="../uploads/layanan/<?= $l['foto_layanan'] ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                                <?php else: ?>
                                    <div class="w-full h-full flex flex-col items-center justify-center text-gray-400 bg-gray-50">
                                        <i class="fas fa-image text-3xl mb-2"></i>
                                        <span class="text-[10px] font-bold uppercase">No Photo</span>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="flex-1 w-full">
                                <div class="flex justify-between items-start mb-1">
                                    <h3 class="text-xl font-black text-navy leading-tight group-hover:text-orange transition-colors pr-4"><?= htmlspecialchars($l['nama_layanan']) ?></h3>
                                    
                                    <div class="text-right shrink-0">
                                        <?php if(isset($l['harga_coret']) && $l['harga_coret'] > $l['harga']): ?>
                                            <p class="text-xs text-red font-black line-through mb-0.5">Rp <?= number_format($l['harga_coret'], 0, ',', '.') ?></p>
                                        <?php endif; ?>
                                        <p class="text-xl font-black text-orange italic tracking-tight whitespace-nowrap">Rp <?= number_format($l['harga'], 0, ',', '.') ?></p>
                                    </div>
                                </div>
                                <p class="text-xs text-gray-500 font-medium leading-relaxed mb-3 line-clamp-2"><?= htmlspecialchars($l['deskripsi_layanan']) ?></p>
                                
                                <div class="mb-4">
                                    <span class="bg-gray-50 text-gray-500 px-3 py-1 rounded-lg text-[10px] font-black uppercase tracking-widest border border-gray-200 inline-flex items-center gap-1.5">
                                        <i class="fas fa-box-open text-orange"></i> Stock: <?= htmlspecialchars($l['stok'] ?? 0) ?> pcs
                                    </span>
                                </div>
                                
                                <div class="flex gap-3 pt-4 border-t border-gray-100 w-full md:w-max ml-auto">
                                    <button type="button" onclick="openEditModal(<?= $l['id'] ?>, '<?= htmlspecialchars(addslashes($l['nama_layanan'])) ?>', <?= $l['harga'] ?>, <?= isset($l['harga_coret']) ? $l['harga_coret'] : 0 ?>, <?= $l['stok'] ?? 0 ?>, '<?= htmlspecialchars(addslashes($l['deskripsi_layanan'])) ?>')" class="flex-1 md:w-32 bg-gray-50 hover:bg-gray-100 text-navy py-3 rounded-xl font-black text-[10px] uppercase tracking-widest transition flex items-center justify-center gap-2 border border-gray-200">
                                        <i class="far fa-edit"></i> Edit
                                    </button>
                                    
                                    <form action="" method="POST" class="flex-1 md:w-32">
                                        <input type="hidden" name="id_layanan" value="<?= $l['id'] ?>">
                                        <button type="submit" name="hapus_layanan" onclick="return confirm('Are you sure you want to delete this product?')" class="w-full bg-red-50 hover:bg-red text-red-600 hover:text-white py-3 rounded-xl font-black text-[10px] uppercase tracking-widest transition border border-red-200 flex items-center justify-center gap-2">
                                            <i class="far fa-trash-alt"></i> Delete
                                        </button>
                                    </form>
                                </div>
                            </div>

                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="bg-white rounded-[2rem] border-2 border-dashed border-gray-200 py-20 px-6 flex flex-col items-center justify-center text-center">
                    <div class="w-24 h-24 bg-gray-50 rounded-full flex items-center justify-center text-gray-300 text-5xl mb-6">
                        <i class="fas fa-box-open"></i>
                    </div>
                    <h3 class="text-xl font-black text-navy mb-2">No Products Yet</h3>
                    <p class="text-sm text-gray-500 font-medium mb-6 max-w-sm">You haven't added any products yet. Add your first product listing!</p>
                </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<div id="modalTambah" class="fixed inset-0 bg-navy/90 backdrop-blur-sm z-[100] hidden items-center justify-center p-4 overflow-y-auto pt-20 pb-10">
    <div class="bg-white w-full max-w-xl rounded-[2.5rem] overflow-hidden shadow-2xl transform transition-all scale-95 opacity-0 duration-300 border border-white/20 my-auto" id="contentTambah">
        <div class="p-6 md:p-8 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
            <h2 class="text-xl md:text-2xl font-black text-navy flex items-center gap-3">
                <i class="fas fa-plus-circle text-orange"></i> Add New Product
            </h2>
            <button type="button" onclick="toggleModal('modalTambah')" class="w-10 h-10 bg-white border border-gray-200 rounded-full flex items-center justify-center text-gray-400 hover:text-red hover:bg-red-50 transition-colors shadow-sm">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <form action="" method="POST" enctype="multipart/form-data" class="p-6 md:p-8 space-y-5">
            <div>
                <label class="block text-[10px] font-black text-navy uppercase tracking-widest mb-2 pl-1">Product Name</label>
                <div class="relative">
                    <i class="fas fa-tag absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                    <input type="text" name="nama_layanan" required placeholder="e.g.: Rose Perfume 50ml" class="w-full pl-11 pr-4 py-3.5 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-orange/20 outline-none font-bold transition text-sm text-navy">
                </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label class="block text-[10px] font-black text-navy uppercase tracking-widest mb-2 pl-1">Sale Price (Rp)</label>
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-orange font-black text-sm">Rp</span>
                        <input type="number" name="harga" required placeholder="150000" class="w-full pl-12 pr-4 py-3.5 bg-orange/5 border border-orange/20 rounded-xl focus:ring-2 focus:ring-orange/40 outline-none font-bold transition text-sm text-navy">
                    </div>
                </div>
                <div>
                    <label class="block text-[10px] font-black text-navy uppercase tracking-widest mb-2 pl-1 text-gray-400">Original Price (Strikethrough) *Optional</label>
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 font-black text-sm line-through">Rp</span>
                        <input type="number" name="harga_coret" placeholder="200000" class="w-full pl-12 pr-4 py-3.5 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-gray-300 outline-none font-bold transition text-sm text-gray-500">
                    </div>
                </div>
            </div>

            <div>
                <label class="block text-[10px] font-black text-navy uppercase tracking-widest mb-2 pl-1">Product Stock</label>
                <div class="relative">
                    <i class="fas fa-box absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                    <input type="number" name="stok" required placeholder="e.g.: 50" class="w-full pl-11 pr-4 py-3.5 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-orange/20 outline-none font-bold transition text-sm text-navy">
                </div>
            </div>

            <div>
                <label class="block text-[10px] font-black text-navy uppercase tracking-widest mb-2 pl-1">Product Photo</label>
                <div class="relative">
                    <input type="file" name="foto_layanan" accept="image/*" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl outline-none font-medium transition text-xs text-gray-500 cursor-pointer file:mr-4 file:py-1 file:px-3 file:rounded-full file:border-0 file:text-xs file:font-bold file:bg-orange/10 file:text-orange hover:file:bg-orange/20">
                </div>
            </div>
            
            <div>
                <label class="block text-[10px] font-black text-navy uppercase tracking-widest mb-2 pl-1">Description & Details</label>
                <textarea rows="3" name="deskripsi_layanan" required placeholder="Describe your product, ingredients, benefits..." class="w-full px-5 py-4 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-orange/20 outline-none font-medium transition text-sm text-gray-600 resize-none"></textarea>
            </div>
            
            <button type="submit" name="tambah_layanan" class="w-full bg-navy text-white py-4 mt-2 rounded-2xl font-black text-xs md:text-sm uppercase tracking-widest hover:bg-orange transition-all transform hover:-translate-y-1 flex justify-center items-center gap-2 group">
                Save Product <i class="fas fa-save group-hover:scale-110 transition-transform"></i>
            </button>
        </form>
    </div>
</div>

<div id="modalEdit" class="fixed inset-0 bg-navy/90 backdrop-blur-sm z-[100] hidden items-center justify-center p-4 overflow-y-auto pt-20 pb-10">
    <div class="bg-white w-full max-w-xl rounded-[2.5rem] overflow-hidden shadow-2xl transform transition-all scale-95 opacity-0 duration-300 border border-white/20 my-auto" id="contentEdit">
        <div class="p-6 md:p-8 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
            <h2 class="text-xl md:text-2xl font-black text-navy flex items-center gap-3">
                <i class="fas fa-edit text-orange"></i> Edit Product
            </h2>
            <button type="button" onclick="toggleModal('modalEdit')" class="w-10 h-10 bg-white border border-gray-200 rounded-full flex items-center justify-center text-gray-400 hover:bg-red-50 transition-colors shadow-sm">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <form action="" method="POST" enctype="multipart/form-data" class="p-6 md:p-8 space-y-5">
            <input type="hidden" name="id_layanan" id="edit_id">

            <div>
                <label class="block text-[10px] font-black text-navy uppercase tracking-widest mb-2 pl-1">Product Name</label>
                <div class="relative">
                    <i class="fas fa-tag absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                    <input type="text" name="nama_layanan" id="edit_nama" required class="w-full pl-11 pr-4 py-3.5 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-orange/20 outline-none font-bold transition text-sm text-navy">
                </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label class="block text-[10px] font-black text-navy uppercase tracking-widest mb-2 pl-1">Sale Price (Rp)</label>
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-orange font-black text-sm">Rp</span>
                        <input type="number" name="harga" id="edit_harga" required class="w-full pl-12 pr-4 py-3.5 bg-orange/5 border border-orange/20 rounded-xl focus:ring-2 focus:ring-orange/40 outline-none font-bold transition text-sm text-navy">
                    </div>
                </div>
                <div>
                    <label class="block text-[10px] font-black text-navy uppercase tracking-widest mb-2 pl-1 text-gray-400">Original Price (Strikethrough)</label>
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 font-black text-sm line-through">Rp</span>
                        <input type="number" name="harga_coret" id="edit_harga_coret" placeholder="200000" class="w-full pl-12 pr-4 py-3.5 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-gray-300 outline-none font-bold transition text-sm text-gray-500">
                    </div>
                </div>
            </div>

            <div>
                <label class="block text-[10px] font-black text-navy uppercase tracking-widest mb-2 pl-1">Product Stock</label>
                <div class="relative">
                    <i class="fas fa-box absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                    <input type="number" name="stok" id="edit_stok" required placeholder="e.g.: 50" class="w-full pl-11 pr-4 py-3.5 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-orange/20 outline-none font-bold transition text-sm text-navy">
                </div>
            </div>

            <div>
                <label class="block text-[10px] font-black text-navy uppercase tracking-widest mb-2 pl-1">Update Photo (Optional)</label>
                <div class="relative">
                    <input type="file" name="foto_layanan" accept="image/*" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl outline-none font-medium transition text-xs text-gray-500 cursor-pointer file:mr-4 file:py-1 file:px-3 file:rounded-full file:border-0 file:text-xs file:font-bold file:bg-blue-50 file:text-blue-600 hover:file:bg-blue-100">
                </div>
            </div>
            
            <div>
                <label class="block text-[10px] font-black text-navy uppercase tracking-widest mb-2 pl-1">Full Description</label>
                <textarea rows="3" name="deskripsi_layanan" id="edit_deskripsi" required class="w-full px-5 py-4 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-orange/20 outline-none font-medium transition text-sm text-gray-600 resize-none"></textarea>
            </div>
            
            <button type="submit" name="edit_layanan" class="w-full bg-navy text-white py-4 mt-2 rounded-2xl font-black text-xs md:text-sm uppercase tracking-widest hover:bg-orange transition-all transform hover:-translate-y-1 flex justify-center items-center gap-2 group">
                Update Product <i class="fas fa-check-circle group-hover:scale-110 transition-transform"></i>
            </button>
        </form>
    </div>
</div>

<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script>
    AOS.init({ once: true, duration: 800, offset: 50 });

    function toggleModal(modalId) {
        const modal = document.getElementById(modalId);
        const contentId = modalId === 'modalTambah' ? 'contentTambah' : 'contentEdit';
        const content = document.getElementById(contentId);

        if(modal.classList.contains('hidden')) {
            modal.classList.replace('hidden', 'flex');
            setTimeout(() => {
                content.classList.replace('scale-95', 'scale-100');
                content.classList.replace('opacity-0', 'opacity-100');
            }, 10);
        } else {
            content.classList.replace('scale-100', 'scale-95');
            content.classList.replace('opacity-100', 'opacity-0');
            setTimeout(() => modal.classList.replace('flex', 'hidden'), 300);
        }
    }

    // [TAMBAHAN] Menambahkan parameter stok ke fungsi ini
    function openEditModal(id, nama, harga, harga_coret, stok, deskripsi) {
        document.getElementById('edit_id').value = id;
        document.getElementById('edit_nama').value = nama;
        document.getElementById('edit_harga').value = harga;
        document.getElementById('edit_harga_coret').value = harga_coret;
        document.getElementById('edit_stok').value = stok; // Set value stok di modal edit
        document.getElementById('edit_deskripsi').value = deskripsi;
        toggleModal('modalEdit');
    }
</script>

<?php require_once '../includes/footer.php'; ?>