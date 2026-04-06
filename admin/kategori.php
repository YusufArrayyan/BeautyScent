<?php 
require_once '../config/database.php';
session_start();

// 1. KEAMANAN SUPER KETAT: Hanya Admin yang boleh masuk
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

// 2. LOGIKA TAMBAH KATEGORI (Tanpa Deskripsi)
if (isset($_POST['tambah_kategori'])) {
    $nama_kategori = mysqli_real_escape_string($conn, trim($_POST['nama_kategori']));
    $ikon          = mysqli_real_escape_string($conn, trim($_POST['ikon']));

    $query_insert = "INSERT INTO kategori (nama_kategori, ikon) VALUES ('$nama_kategori', '$ikon')";
    
    if (mysqli_query($conn, $query_insert)) {
        $_SESSION['pesan_sukses'] = "Kategori baru successfully ditambahkan!";
    } else {
        $_SESSION['pesan_error'] = "Failed menambahkan kategori.";
    }
    header("Location: kategori.php");
    exit();
}

// 3. LOGIKA EDIT KATEGORI (Tanpa Deskripsi)
if (isset($_POST['edit_kategori'])) {
    $id_kategori   = mysqli_real_escape_string($conn, $_POST['id_kategori']);
    $nama_kategori = mysqli_real_escape_string($conn, trim($_POST['nama_kategori']));
    $ikon          = mysqli_real_escape_string($conn, trim($_POST['ikon']));

    $query_update = "UPDATE kategori SET nama_kategori='$nama_kategori', ikon='$ikon' WHERE id='$id_kategori'";
    
    if (mysqli_query($conn, $query_update)) {
        $_SESSION['pesan_sukses'] = "Data kategori successfully diperbarui!";
    } else {
        $_SESSION['pesan_error'] = "Failed memperbarui kategori.";
    }
    header("Location: kategori.php");
    exit();
}

// 4. LOGIKA HAPUS KATEGORI
if (isset($_POST['hapus_kategori'])) {
    $id_kategori = mysqli_real_escape_string($conn, $_POST['id_kategori']);
    
    if (mysqli_query($conn, "DELETE FROM kategori WHERE id = '$id_kategori'")) {
        $_SESSION['pesan_sukses'] = "Kategori successfully dihapus permanen.";
    } else {
        $_SESSION['pesan_error'] = "Failed to delete category.";
    }
    header("Location: kategori.php");
    exit();
}

// 5. AMBIL SEMUA DATA KATEGORI
$query_kategori = mysqli_query($conn, "SELECT * FROM kategori ORDER BY nama_kategori ASC");
$total_kategori = mysqli_num_rows($query_kategori);

// 6. AMBIL NOTIFIKASI PENDING VERIFIKASI UNTUK SIDEBAR
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
                    <i class="fas fa-tags"></i>
                </div>
                <div>
                    <h1 class="text-2xl md:text-3xl font-black text-white tracking-tight">Manage Categories</h1>
                    <p class="text-sm font-medium text-gray-400">Atur <span class="text-white font-bold"><?= $total_kategori ?> kategori</span> layanan di platform BeautyScent.</p>
                </div>
            </div>
            
            <button onclick="toggleModal('modalTambah')" class="bg-blue-600 hover:bg-blue-500 text-white px-6 py-3.5 rounded-xl font-black text-xs uppercase tracking-widest transition-all shadow-xl shadow-blue-600/30 flex items-center gap-3 transform hover:-translate-y-1">
                <i class="fas fa-plus"></i> Kategori Baru
            </button>
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
                    <!-- Menu Active: Manage Categories -->
                    <a href="kategori.php" class="bg-blue-50 text-blue-600 flex items-center gap-4 p-4 rounded-2xl font-black text-sm transition-all border border-blue-100">
                        <div class="w-8 h-8 rounded-full bg-blue-600 text-white flex items-center justify-center shadow-md"><i class="fas fa-tags"></i></div> Manage Categories
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

            <!-- GRID KATEGORI -->
            <?php if($total_kategori > 0): ?>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php while($k = mysqli_fetch_assoc($query_kategori)): ?>
                    <div class="bg-white p-6 rounded-[2rem] shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-gray-100 group hover:border-blue-500 transition-all duration-300 relative overflow-hidden flex flex-col hover:-translate-y-1 hover:shadow-xl">
                        
                        <div class="flex justify-between items-start mb-6">
                            <div class="w-14 h-14 bg-gray-50 border border-gray-100 rounded-2xl flex items-center justify-center text-2xl text-navy group-hover:bg-blue-600 group-hover:text-white group-hover:border-blue-600 transition-all shadow-sm">
                                <i class="<?= htmlspecialchars($k['ikon']) ?>"></i>
                            </div>
                            
                            <!-- Action Buttons -->
                            <div class="flex gap-2">
                                <button type="button" onclick="openEditModal(<?= $k['id'] ?>, '<?= htmlspecialchars(addslashes($k['nama_kategori'])) ?>', '<?= htmlspecialchars(addslashes($k['ikon'])) ?>')" class="w-8 h-8 bg-gray-50 hover:bg-navy text-gray-400 hover:text-white rounded-xl flex items-center justify-center transition-colors shadow-sm">
                                    <i class="fas fa-pen text-xs"></i>
                                </button>
                                <form action="" method="POST" class="inline-block">
                                    <input type="hidden" name="id_kategori" value="<?= $k['id'] ?>">
                                    <button type="submit" name="hapus_kategori" onclick="return confirm('Are you sure you want to permanently delete this category? All stores may lose this category reference!')" class="w-8 h-8 bg-red-50 hover:bg-red text-red-500 hover:text-white rounded-xl flex items-center justify-center transition-colors shadow-sm">
                                        <i class="fas fa-trash text-xs"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                        
                        <h3 class="text-lg font-black text-navy uppercase tracking-tight mb-2 group-hover:text-blue-600 transition-colors"><?= htmlspecialchars($k['nama_kategori']) ?></h3>
                    </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <!-- EMPTY STATE -->
                <div class="bg-white rounded-[2.5rem] border-2 border-dashed border-gray-200 py-20 px-6 flex flex-col items-center justify-center text-center">
                    <div class="w-24 h-24 bg-gray-50 rounded-full flex items-center justify-center text-gray-300 text-5xl mb-6"><i class="fas fa-tags"></i></div>
                    <h3 class="text-xl font-black text-navy mb-2">No Categories Yet</h3>
                    <p class="text-sm text-gray-500 font-medium mb-6 max-w-md">Tambahkan kategori jasa pertama Anda agar seller bisa mendaftar dengan spesialisasi yang tepat.</p>
                    <button onclick="toggleModal('modalTambah')" class="bg-blue-600 text-white px-8 py-3.5 rounded-xl font-black text-xs uppercase tracking-widest shadow-lg shadow-blue-600/30 hover:-translate-y-1 transition-transform">
                        Add Category Pertama
                    </button>
                </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<!-- ==========================================
     MODAL TAMBAH KATEGORI
=========================================== -->
<div id="modalTambah" class="fixed inset-0 bg-navy/90 backdrop-blur-sm z-[100] hidden items-center justify-center p-4">
    <div class="bg-white w-full max-w-lg rounded-[2.5rem] overflow-hidden shadow-2xl transform transition-all scale-95 opacity-0 duration-300 border border-white/20" id="contentTambah">
        <div class="p-6 md:p-8 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
            <h2 class="text-xl md:text-2xl font-black text-navy flex items-center gap-3">
                <i class="fas fa-plus-circle text-blue-600"></i> Add Category
            </h2>
            <button type="button" onclick="toggleModal('modalTambah')" class="w-10 h-10 bg-white border border-gray-200 rounded-full flex items-center justify-center text-gray-400 hover:text-red hover:border-red hover:bg-red-50 transition-colors shadow-sm">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <form action="" method="POST" class="p-6 md:p-8 space-y-5">
            <div>
                <label class="block text-[10px] font-black text-navy uppercase tracking-widest mb-2 pl-1">Category Name</label>
                <div class="relative">
                    <i class="fas fa-tag absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                    <input type="text" name="nama_kategori" required placeholder="Contoh: Servis AC Mobil" class="w-full pl-11 pr-4 py-3.5 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-600/20 focus:border-blue-600 focus:bg-white outline-none font-bold transition text-sm text-navy">
                </div>
            </div>
            
            <div>
                <label class="block text-[10px] font-black text-navy uppercase tracking-widest mb-2 pl-1 flex justify-between items-center">
                    <span>Ikon (FontAwesome Class)</span>
                    <a href="https://fontawesome.com/search?o=r&m=free" target="_blank" class="text-blue-600 hover:underline normal-case"><i class="fas fa-external-link-alt mr-1"></i>Cari Ikon</a>
                </label>
                <div class="relative">
                    <i class="fas fa-icons absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                    <input type="text" name="ikon" required placeholder="Contoh: fas fa-car" class="w-full pl-11 pr-4 py-3.5 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-600/20 focus:border-blue-600 focus:bg-white outline-none font-bold transition text-sm text-navy">
                </div>
            </div>
            
            <button type="submit" name="tambah_kategori" class="w-full bg-navy text-white py-4 mt-2 rounded-2xl font-black text-xs md:text-sm uppercase tracking-widest shadow-xl shadow-navy/20 hover:bg-blue-600 hover:shadow-blue-600/30 transition-all transform hover:-translate-y-1 flex justify-center items-center gap-2 group">
                Save Category <i class="fas fa-save group-hover:scale-110 transition-transform"></i>
            </button>
        </form>
    </div>
</div>

<!-- ==========================================
     MODAL EDIT KATEGORI
=========================================== -->
<div id="modalEdit" class="fixed inset-0 bg-navy/90 backdrop-blur-sm z-[100] hidden items-center justify-center p-4">
    <div class="bg-white w-full max-w-lg rounded-[2.5rem] overflow-hidden shadow-2xl transform transition-all scale-95 opacity-0 duration-300 border border-white/20" id="contentEdit">
        <div class="p-6 md:p-8 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
            <h2 class="text-xl md:text-2xl font-black text-navy flex items-center gap-3">
                <i class="fas fa-edit text-blue-600"></i> Edit Category
            </h2>
            <button type="button" onclick="toggleModal('modalEdit')" class="w-10 h-10 bg-white border border-gray-200 rounded-full flex items-center justify-center text-gray-400 hover:text-red hover:border-red hover:bg-red-50 transition-colors shadow-sm">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <form action="" method="POST" class="p-6 md:p-8 space-y-5">
            <input type="hidden" name="id_kategori" id="edit_id">

            <div>
                <label class="block text-[10px] font-black text-navy uppercase tracking-widest mb-2 pl-1">Category Name</label>
                <div class="relative">
                    <i class="fas fa-tag absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                    <input type="text" name="nama_kategori" id="edit_nama" required class="w-full pl-11 pr-4 py-3.5 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-600/20 focus:border-blue-600 focus:bg-white outline-none font-bold transition text-sm text-navy">
                </div>
            </div>
            
            <div>
                <label class="block text-[10px] font-black text-navy uppercase tracking-widest mb-2 pl-1 flex justify-between items-center">
                    <span>Ikon (FontAwesome)</span>
                    <a href="https://fontawesome.com/search?o=r&m=free" target="_blank" class="text-blue-600 hover:underline normal-case"><i class="fas fa-external-link-alt mr-1"></i>Cari Ikon</a>
                </label>
                <div class="relative">
                    <i class="fas fa-icons absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                    <input type="text" name="ikon" id="edit_ikon" required class="w-full pl-11 pr-4 py-3.5 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-600/20 focus:border-blue-600 focus:bg-white outline-none font-bold transition text-sm text-navy">
                </div>
            </div>
            
            <button type="submit" name="edit_kategori" class="w-full bg-navy text-white py-4 mt-2 rounded-2xl font-black text-xs md:text-sm uppercase tracking-widest shadow-xl shadow-navy/20 hover:bg-blue-600 hover:shadow-blue-600/30 transition-all transform hover:-translate-y-1 flex justify-center items-center gap-2 group">
                Perbarui Kategori <i class="fas fa-check-circle group-hover:scale-110 transition-transform"></i>
            </button>
        </form>
    </div>
</div>

<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script>
    AOS.init({ once: true, duration: 800, offset: 50 });

    // Fungsi Toggle General untuk Modal (Tambah & Edit)
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

    // Fungsi untuk Pop-up Edit
    function openEditModal(id, nama, ikon) {
        document.getElementById('edit_id').value = id;
        document.getElementById('edit_nama').value = nama;
        document.getElementById('edit_ikon').value = ikon;
        
        toggleModal('modalEdit');
    }
</script>

<?php require_once '../includes/footer.php'; ?>