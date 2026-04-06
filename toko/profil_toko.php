<?php
// ERROR REPORTING DINONAKTIFKAN KARENA MASALAH SUDAH TERATASI
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

require_once '../config/database.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. KEAMANAN: Cek Login & Role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'toko') {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// 2. AMBIL DATA TOKO DAN USER
$query_data = mysqli_query($conn, "
    SELECT t.*, u.nama_lengkap, u.no_hp, u.email, u.foto_profil 
    FROM toko t 
    JOIN users u ON t.user_id = u.id 
    WHERE t.user_id = '$user_id'
");
$data = mysqli_fetch_assoc($query_data);

// CEK PENGAMAN 1: Pastikan data toko ditemukan
if (!$data) {
    die("<div style='padding:20px; background:#f8d7da; color:#721c24;'>Error: Data toko tidak ditemukan di database untuk user ini!</div>");
}
$toko_id = $data['id'];

// Folder Upload (Ganti 0777 ke 0755 karena InfinityFree sangat ketat dengan 0777)
$upload_dir = '../uploads/profil/';
if (!file_exists($upload_dir)) { 
    @mkdir($upload_dir, 0755, true); 
}

// 3. FUNGSI UPLOAD (Dibungkus agar tidak bentrok)
if (!function_exists('prosesUploadGambarToko')) {
    function prosesUploadGambarToko($file_input, $old_file) {
        global $upload_dir;
        if (isset($_FILES[$file_input]) && $_FILES[$file_input]['error'] === UPLOAD_ERR_OK) {
            $tmp_name = $_FILES[$file_input]['tmp_name'];
            $file_name = $_FILES[$file_input]['name'];
            $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'webp'];
            
            if (in_array($ext, $allowed)) {
                $new_name = uniqid($file_input . '_') . '.' . $ext;
                if (move_uploaded_file($tmp_name, $upload_dir . $new_name)) {
                    // Hapus foto lama jika ada
                    if (!empty($old_file) && file_exists($upload_dir . $old_file) && !in_array(strtolower($old_file), ['default.png', 'default.jpg'])) {
                        @unlink($upload_dir . $old_file);
                    }
                    return $new_name;
                }
            }
        }
        return empty($old_file) ? '' : $old_file;
    }
}

// 4. LOGIKA SIMPAN PERUBAHAN DENGAN TRY-CATCH
if (isset($_POST['simpan_profil'])) {
    try {
        $nama_toko = mysqli_real_escape_string($conn, trim($_POST['nama_toko'] ?? ''));
        $deskripsi = mysqli_real_escape_string($conn, trim($_POST['deskripsi_toko'] ?? ''));
        $no_hp = mysqli_real_escape_string($conn, trim($_POST['no_hp'] ?? ''));
        $nama_lengkap = mysqli_real_escape_string($conn, trim($_POST['nama_lengkap'] ?? ''));
        
        // Update Data Teks
        $upd_users = mysqli_query($conn, "UPDATE users SET nama_lengkap = '$nama_lengkap', no_hp = '$no_hp' WHERE id = '$user_id'");
        
        // PERBAIKAN DI SINI: "deskripsi_toko" diubah menjadi "deskripsi" sesuai nama kolom di database
        $upd_toko = mysqli_query($conn, "UPDATE toko SET nama_toko = '$nama_toko', deskripsi = '$deskripsi' WHERE id = '$toko_id'");

        if (!$upd_users || !$upd_toko) {
            throw new Exception("Error Update SQL Teks: " . mysqli_error($conn));
        }

        // Proses Upload Foto Profil (Ke tabel users)
        $foto_profil_baru = prosesUploadGambarToko('foto_profil', $data['foto_profil'] ?? '');
        mysqli_query($conn, "UPDATE users SET foto_profil = '$foto_profil_baru' WHERE id = '$user_id'");

        // Proses Upload Banner 1, 2, 3 (Ke tabel toko)
        $b1 = prosesUploadGambarToko('banner1', $data['banner1'] ?? '');
        $b2 = prosesUploadGambarToko('banner2', $data['banner2'] ?? '');
        $b3 = prosesUploadGambarToko('banner3', $data['banner3'] ?? '');
        $upd_banner = mysqli_query($conn, "UPDATE toko SET banner1 = '$b1', banner2 = '$b2', banner3 = '$b3' WHERE id = '$toko_id'");

        if (!$upd_banner) {
            throw new Exception("Error Update SQL Banner: " . mysqli_error($conn));
        }

        $_SESSION['pesan_sukses'] = "Profil dan Etalase Store berhasil diperbarui!";
        header("Location: profil_toko.php");
        exit();

    } catch (Throwable $e) {
        // JIKA ADA ERROR 500, TAMPILKAN DI SINI
        die("<div style='padding:20px; background:#f8d7da; color:#721c24; margin:20px; border-radius:10px; font-family:sans-serif;'>
            <h3>⚠️ Terjadi Kesalahan Sistem (Internal Error Ditangkap):</h3>
            <p><b>Pesan:</b> " . $e->getMessage() . "</p>
            <p><b>Baris Kode:</b> " . $e->getLine() . "</p>
            <a href='profil_toko.php' style='display:inline-block; margin-top:15px; padding:10px 20px; background:#721c24; color:white; text-decoration:none; border-radius:5px;'>Kembali ke Form</a>
        </div>");
    }
}

require_once '../includes/header.php'; 

// CEK PENGAMAN 2: Helper untuk menampilkan gambar dibungkus agar tidak bentrok
if (!function_exists('getImageUrl')) {
    function getImageUrl($filename, $type = 'avatar', $fallback_name = 'Store') {
        global $base_url;
        if (!empty($filename) && !in_array(strtolower($filename), ['default.png', 'default.jpg'])) {
            $clean = str_replace(['uploads/profil/', 'profil/', 'uploads/'], '', $filename);
            return $base_url . '/uploads/profil/' . $clean;
        }
        if ($type == 'avatar') return "https://ui-avatars.com/api/?name=".urlencode($fallback_name)."&background=ff6600&color=fff&size=200";
        return "https://placehold.co/600x300/e2e8f0/94a3b8?text=Belum+Ada+Banner";
    }
}
?>

<link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">

<div class="bg-[#f4f7fa] min-h-screen pb-24 font-sans text-gray-800">

    <div class="bg-gradient-to-r from-navy to-[#111144] py-12 relative overflow-hidden shadow-lg">
        <div class="absolute top-0 right-0 w-64 h-64 bg-white/5 rounded-full blur-[80px] pointer-events-none"></div>
        <div class="max-w-screen-xl mx-auto px-5 md:px-8 relative z-10 flex flex-col md:flex-row justify-between items-center gap-6" data-aos="fade-down">
            <div class="flex items-center gap-5">
                <div class="w-16 h-16 bg-gradient-to-br from-orange to-red-500 rounded-2xl flex items-center justify-center text-white text-2xl shadow-xl transform -rotate-6">
                    <i class="fas fa-store-alt"></i>
                </div>
                <div>
                    <h1 class="text-2xl md:text-3xl font-black text-white tracking-tight">Etalase Store</h1>
                    <p class="text-sm font-medium text-gray-400">Atur profil, foto, dan banner agar toko Anda terlihat profesional.</p>
                </div>
            </div>
            
            <div class="flex gap-4">
                <a href="<?= $base_url ?>/detail_toko.php?id=<?= $toko_id ?>" target="_blank" class="bg-white/10 hover:bg-white/20 border border-white/20 text-white px-5 py-2.5 rounded-xl text-xs font-black uppercase tracking-widest transition-all backdrop-blur-sm flex items-center gap-2 shadow-sm">
                    <i class="far fa-eye"></i> Pratinjau Store
                </a>
            </div>
        </div>
    </div>

    <div class="max-w-screen-xl mx-auto px-5 md:px-8 mt-8 grid grid-cols-1 lg:grid-cols-12 gap-8">
        
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
                        <div class="w-8 h-8 rounded-full bg-gray-100 group-hover:bg-navy group-hover:text-white flex items-center justify-center transition-colors"><i class="far fa-calendar-check"></i></div> Schedule & Queue
                    </a>
                    <a href="promosi.php" class="text-gray-500 hover:bg-gray-50 hover:text-navy flex items-center gap-4 p-4 rounded-2xl font-bold text-sm transition-all group">
                        <div class="w-8 h-8 rounded-full bg-gray-100 group-hover:bg-navy group-hover:text-white flex items-center justify-center transition-colors"><i class="fas fa-bullhorn"></i></div> Ads & Promotions
                    </a>
                    <a href="profil_toko.php" class="bg-orange/10 text-orange flex items-center gap-4 p-4 rounded-2xl font-black text-sm transition-all border border-orange/20">
                        <div class="w-8 h-8 rounded-full bg-orange text-white flex items-center justify-center shadow-md"><i class="fas fa-id-card"></i></div>
                        Store Profile
                    </a>
                    <a href="pengaturan_maps.php" class="text-gray-500 hover:bg-gray-50 hover:text-navy flex items-center gap-4 p-4 rounded-2xl font-bold text-sm transition-all group">
                        <div class="w-8 h-8 rounded-full bg-gray-100 group-hover:bg-navy group-hover:text-white flex items-center justify-center transition-colors"><i class="fas fa-map-marked-alt"></i></div>
                        Map Settings
                    </a>
                    <a href="keuangan.php" class="text-gray-500 hover:bg-gray-50 hover:text-navy flex items-center gap-4 p-4 rounded-2xl font-bold text-sm transition-all group">
                        <div class="w-8 h-8 rounded-full bg-gray-100 group-hover:bg-navy group-hover:text-white flex items-center justify-center transition-colors"><i class="fas fa-wallet"></i></div>
                        Finances
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

            <form action="" method="POST" enctype="multipart/form-data" class="space-y-6">
                
                <div class="bg-white rounded-[2rem] p-6 md:p-8 shadow-sm border border-gray-100">
                    <h3 class="text-xl font-black text-navy mb-6 border-b border-gray-100 pb-4"><i class="fas fa-info-circle text-orange mr-2"></i> Informasi Dasar</h3>
                    
                    <div class="flex flex-col md:flex-row gap-8">
                        <div class="shrink-0 flex flex-col items-center gap-4">
                            <div class="w-32 h-32 md:w-40 md:h-40 rounded-full border-4 border-gray-100 overflow-hidden relative group shadow-md">
                                <img id="preview_avatar" src="<?= getImageUrl($data['foto_profil'], 'avatar', $data['nama_toko']) ?>" class="w-full h-full object-cover transition-transform group-hover:scale-110">
                                <label for="foto_profil" class="absolute inset-0 bg-black/50 flex flex-col items-center justify-center text-white opacity-0 group-hover:opacity-100 cursor-pointer transition-opacity backdrop-blur-sm">
                                    <i class="fas fa-camera text-2xl mb-1"></i>
                                    <span class="text-[10px] font-bold uppercase tracking-widest">Change Photo</span>
                                </label>
                                <input type="file" id="foto_profil" name="foto_profil" accept="image/*" class="hidden" onchange="previewImage(this, 'preview_avatar')">
                            </div>
                            <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest text-center">Foto Profil / Logo</p>
                        </div>

                        <div class="flex-1 space-y-5">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                <div>
                                    <label class="block text-[10px] font-black text-navy uppercase tracking-widest mb-2 pl-1">Store Name / Usaha</label>
                                    <input type="text" name="nama_toko" value="<?= htmlspecialchars($data['nama_toko']) ?>" required class="w-full px-4 py-3.5 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-orange/20 focus:border-orange outline-none font-bold text-sm text-navy">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-black text-navy uppercase tracking-widest mb-2 pl-1">Nama Pemilik (Seller)</label>
                                    <input type="text" name="nama_lengkap" value="<?= htmlspecialchars($data['nama_lengkap']) ?>" required class="w-full px-4 py-3.5 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-orange/20 focus:border-orange outline-none font-bold text-sm text-navy">
                                </div>
                            </div>
                            <div>
                                <label class="block text-[10px] font-black text-navy uppercase tracking-widest mb-2 pl-1">Nomor WhatsApp Active</label>
                                <input type="text" name="no_hp" value="<?= htmlspecialchars($data['no_hp']) ?>" required class="w-full px-4 py-3.5 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-orange/20 focus:border-orange outline-none font-bold text-sm text-navy">
                            </div>
                            <div>
                                <label class="block text-[10px] font-black text-navy uppercase tracking-widest mb-2 pl-1">Deskripsi & Keahlian Store</label>
                                <textarea name="deskripsi_toko" rows="4" placeholder="Ceritakan keahlian, pengalaman, dan keunggulan bengkel/toko Anda..." class="w-full px-4 py-3.5 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-orange/20 focus:border-orange outline-none font-medium text-sm text-gray-600 resize-none"><?= htmlspecialchars($data['deskripsi']) ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-[2rem] p-6 md:p-8 shadow-sm border border-gray-100">
                    <div class="flex flex-col md:flex-row md:items-center justify-between mb-6 border-b border-gray-100 pb-4 gap-2">
                        <h3 class="text-xl font-black text-navy"><i class="fas fa-images text-orange mr-2"></i> Banner Promo Store</h3>
                        <span class="bg-blue-50 text-blue-600 px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest border border-blue-100">Maks. 3 Gambar</span>
                    </div>
                    
                    <p class="text-xs text-gray-500 font-medium mb-6">Unggah foto spanduk, hasil kerja, atau promo diskon. Banner ini akan tampil secara otomatis (*slider*) di halaman kategori pembeli.</p>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="relative group cursor-pointer">
                            <div class="h-32 bg-gray-100 rounded-2xl border-2 border-dashed border-gray-300 overflow-hidden relative transition-all group-hover:border-orange">
                                <img id="preview_b1" src="<?= getImageUrl($data['banner1'], 'banner') ?>" class="w-full h-full object-cover">
                                <div class="absolute inset-0 bg-black/40 flex flex-col items-center justify-center text-white opacity-0 group-hover:opacity-100 transition-opacity backdrop-blur-[2px]">
                                    <i class="fas fa-cloud-upload-alt text-2xl mb-1"></i>
                                    <span class="text-[9px] font-bold uppercase tracking-widest">Pilih Gambar 1</span>
                                </div>
                                <input type="file" name="banner1" accept="image/*" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10" onchange="previewImage(this, 'preview_b1')">
                            </div>
                        </div>
                        <div class="relative group cursor-pointer">
                            <div class="h-32 bg-gray-100 rounded-2xl border-2 border-dashed border-gray-300 overflow-hidden relative transition-all group-hover:border-orange">
                                <img id="preview_b2" src="<?= getImageUrl($data['banner2'], 'banner') ?>" class="w-full h-full object-cover">
                                <div class="absolute inset-0 bg-black/40 flex flex-col items-center justify-center text-white opacity-0 group-hover:opacity-100 transition-opacity backdrop-blur-[2px]">
                                    <i class="fas fa-cloud-upload-alt text-2xl mb-1"></i>
                                    <span class="text-[9px] font-bold uppercase tracking-widest">Pilih Gambar 2</span>
                                </div>
                                <input type="file" name="banner2" accept="image/*" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10" onchange="previewImage(this, 'preview_b2')">
                            </div>
                        </div>
                        <div class="relative group cursor-pointer">
                            <div class="h-32 bg-gray-100 rounded-2xl border-2 border-dashed border-gray-300 overflow-hidden relative transition-all group-hover:border-orange">
                                <img id="preview_b3" src="<?= getImageUrl($data['banner3'], 'banner') ?>" class="w-full h-full object-cover">
                                <div class="absolute inset-0 bg-black/40 flex flex-col items-center justify-center text-white opacity-0 group-hover:opacity-100 transition-opacity backdrop-blur-[2px]">
                                    <i class="fas fa-cloud-upload-alt text-2xl mb-1"></i>
                                    <span class="text-[9px] font-bold uppercase tracking-widest">Pilih Gambar 3</span>
                                </div>
                                <input type="file" name="banner3" accept="image/*" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10" onchange="previewImage(this, 'preview_b3')">
                            </div>
                        </div>
                    </div>
                </div>

                <button type="submit" name="simpan_profil" class="w-full bg-navy text-white py-4 rounded-2xl font-black text-sm uppercase tracking-widest shadow-xl shadow-navy/20 hover:bg-orange hover:shadow-orange/30 transition-all transform hover:-translate-y-1 flex justify-center items-center gap-2 group">
                    <i class="fas fa-save group-hover:scale-110 transition-transform"></i> Save Changes Profil
                </button>

            </form>
        </div>
    </div>
</div>

<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script>
    AOS.init({ once: true, duration: 800, offset: 50 });

    // JS untuk Live Preview Gambar sebelum di Upload
    function previewImage(input, imgId) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById(imgId).src = e.target.result;
            }
            reader.readAsDataURL(input.files[0]);
        }
    }
</script>

<?php require_once '../includes/footer.php'; ?>