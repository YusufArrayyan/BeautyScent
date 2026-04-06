<?php
session_start();
require_once '../config/database.php';

// PENGHADANG GUEST: Wajib Login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$pesan_sukses = '';
$pesan_error = '';

// ==========================================
// PROSES UPDATE PROFIL KETIKA TOMBOL "SIMPAN" DITEKAN
// ==========================================
if (isset($_POST['update_profil'])) {
    $nama = mysqli_real_escape_string($conn, $_POST['nama_lengkap']);
    $no_hp = mysqli_real_escape_string($conn, $_POST['no_hp']);
    $password_baru = $_POST['password_baru'];
    
    // 1. Proses Upload Foto
    $foto_query = "";
    if (isset($_FILES['foto_profil']) && $_FILES['foto_profil']['error'] == 0) {
        $ekstensi_diperbolehkan = array('png', 'jpg', 'jpeg');
        $nama_file = $_FILES['foto_profil']['name'];
        $x = explode('.', $nama_file);
        $ekstensi = strtolower(end($x));
        $ukuran = $_FILES['foto_profil']['size'];
        $file_tmp = $_FILES['foto_profil']['tmp_name'];
        
        if (in_array($ekstensi, $ekstensi_diperbolehkan) === true) {
            if ($ukuran < 2048000) { // Maks 2MB
                $nama_file_baru = time() . '_' . $user_id . '.' . $ekstensi;
                $path_upload = '../uploads/profil/' . $nama_file_baru;
                
                // Pastikan folder ada
                if (!is_dir('../uploads/profil/')) {
                    mkdir('../uploads/profil/', 0777, true);
                }
                
                if (move_uploaded_file($file_tmp, $path_upload)) {
                    $foto_query = ", foto_profil = '$nama_file_baru'";
                } else {
                    $pesan_error = "Failed to upload profile photo.";
                }
            } else {
                $pesan_error = "File size too large. Max 2MB.";
            }
        } else {
            $pesan_error = "File extension not allowed. Use JPG or PNG.";
        }
    }

    // 2. Proses Update Password
    $pass_query = "";
    if (!empty($password_baru)) {
        if (strlen($password_baru) >= 8) {
            $hashed_password = password_hash($password_baru, PASSWORD_DEFAULT);
            $pass_query = ", password = '$hashed_password'";
        } else {
            $pesan_error = "New password must be at least 8 characters.";
        }
    }

    // 3. Eksekusi Update Database
    if (empty($pesan_error)) {
        $query_update = "UPDATE users SET nama_lengkap = '$nama', no_hp = '$no_hp' $foto_query $pass_query WHERE id = '$user_id'";
        if (mysqli_query($conn, $query_update)) {
            $pesan_sukses = "Profile updated successfully!";
            $_SESSION['nama_lengkap'] = $nama; // Update session
        } else {
            $pesan_error = "A system error occurred while updating profile.";
        }
    }
}

// ==========================================
// MENGAMBIL DATA UNTUK DITAMPILKAN
// ==========================================

// 1. Ambil Data Asli User dari Database
$q_user = mysqli_query($conn, "SELECT * FROM users WHERE id = '$user_id'");
$user = mysqli_fetch_assoc($q_user);

// 2. Hitung Statistik Nyata
$q_pesanan = mysqli_query($conn, "SELECT COUNT(id) as total FROM pesanan WHERE user_id = '$user_id'");
$total_pesanan = $q_pesanan ? mysqli_fetch_assoc($q_pesanan)['total'] : 0;

$q_notif = mysqli_query($conn, "SELECT COUNT(id) as total FROM pesanan WHERE user_id = '$user_id' AND status IN ('pending', 'diproses')");
$total_notif = $q_notif ? mysqli_fetch_assoc($q_notif)['total'] : 0;

// 3. Ambil Order History Singkat
$q_riwayat = mysqli_query($conn, "SELECT p.*, t.nama_toko, l.nama_layanan 
                                  FROM pesanan p 
                                  JOIN toko t ON p.toko_id = t.id 
                                  LEFT JOIN layanan l ON p.layanan_id = l.id
                                  WHERE p.user_id = '$user_id' 
                                  ORDER BY p.id DESC LIMIT 5");

require_once '../includes/header.php';
?>

<div class="bg-[#f4f7fa] min-h-screen pb-24 font-sans text-gray-800 pt-8">
    <div class="max-w-screen-md mx-auto px-5 md:px-8">
        
        <?php if($pesan_sukses): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-5 py-4 rounded-2xl mb-8 font-bold text-sm flex items-center gap-3 animate-pulse">
                <i class="fas fa-check-circle text-xl"></i> <?= $pesan_sukses ?>
            </div>
        <?php endif; ?>

        <?php if($pesan_error): ?>
            <div class="bg-red-50 border border-red-200 text-red-600 px-5 py-4 mb-8 rounded-2xl text-sm font-bold flex items-center gap-3">
                <i class="fas fa-exclamation-circle text-xl"></i> <?= $pesan_error ?>
            </div>
        <?php endif; ?>

        <!-- CARD 1: INFORMASI PROFIL & FORM EDIT -->
        <div class="bg-white rounded-[2.5rem] p-8 md:p-10 shadow-xl shadow-navy/5 border border-gray-50 mb-8 relative overflow-hidden">
            <!-- Hiasan Background -->
            <div class="absolute -right-10 -top-10 w-40 h-40 bg-orange/10 rounded-full blur-2xl pointer-events-none"></div>
            
            <div class="flex flex-col md:flex-row items-center gap-8 mb-8 border-b border-gray-100 pb-8">
                <!-- Foto Profil (Ditampilkan dengan Radar yang Benar) -->
                <?php 
                    $foto_tampil = "";
                    if (!empty($user['foto_profil']) && file_exists('../uploads/profil/' . $user['foto_profil'])) {
                        $foto_tampil = $base_url . '/uploads/profil/' . $user['foto_profil'];
                    } else {
                        $foto_tampil = "https://ui-avatars.com/api/?name=" . urlencode($user['nama_lengkap']) . "&background=ff6600&color=fff&size=200";
                    }
                ?>
                <div class="w-24 h-24 md:w-32 md:h-32 bg-gray-100 rounded-[2rem] shadow-lg flex items-center justify-center text-white shrink-0 border-4 border-white overflow-hidden relative">
                    <img src="<?= $foto_tampil ?>" alt="Your Profile" class="w-full h-full object-cover">
                </div>
                
                <!-- Detail Profil Singkat -->
                <div class="text-center md:text-left flex-1 relative z-10">
                    <h2 class="text-2xl md:text-3xl font-black text-navy mb-2 tracking-tight"><?= htmlspecialchars($user['nama_lengkap']) ?></h2>
                    <div class="flex flex-wrap justify-center md:justify-start items-center gap-4 text-sm font-bold text-gray-500 mb-2">
                        <span class="flex items-center gap-1.5"><i class="far fa-envelope text-orange"></i> <?= htmlspecialchars($user['email']) ?></span>
                        <span class="hidden md:block text-gray-300">•</span>
                        <span class="flex items-center gap-1.5"><i class="fas fa-phone text-orange"></i> <?= htmlspecialchars($user['no_hp'] ?? 'Not set') ?></span>
                    </div>
                    <span class="bg-navy text-white text-[9px] uppercase tracking-widest font-black px-3 py-1 rounded-md"><?= $user['role'] ?></span>
                </div>
            </div>

            <!-- FORM EDIT PROFIL (Menyatu di bawah Foto) -->
            <form action="" method="POST" enctype="multipart/form-data" class="space-y-6 relative z-10">
                <h3 class="text-lg font-black text-navy mb-4 border-l-4 border-orange pl-3 tracking-tight">Update Your Profile</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-[10px] font-black text-navy uppercase tracking-widest mb-2 pl-1">Full Name</label>
                        <div class="relative group">
                            <i class="far fa-user absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 group-focus-within:text-orange transition-colors"></i>
                            <input type="text" name="nama_lengkap" value="<?= htmlspecialchars($user['nama_lengkap']) ?>" required class="w-full pl-11 pr-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:bg-white focus:border-orange focus:ring-2 focus:ring-orange/20 font-bold text-sm text-navy">
                        </div>
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-navy uppercase tracking-widest mb-2 pl-1">No. WhatsApp</label>
                        <div class="relative group">
                            <i class="fab fa-whatsapp absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 group-focus-within:text-orange transition-colors"></i>
                            <input type="text" name="no_hp" value="<?= htmlspecialchars($user['no_hp']) ?>" class="w-full pl-11 pr-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:bg-white focus:border-orange focus:ring-2 focus:ring-orange/20 font-bold text-sm text-navy">
                        </div>
                    </div>
                </div>

                <div>
                    <label class="block text-[10px] font-black text-navy uppercase tracking-widest mb-2 pl-1">Change Profile Photo</label>
                    <input type="file" name="foto_profil" accept="image/png, image/jpeg, image/jpg" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-black file:bg-orange/10 file:text-orange hover:file:bg-orange/20 transition-all cursor-pointer border border-gray-200 rounded-xl p-2 bg-gray-50">
                    <p class="text-[10px] text-gray-400 mt-2 pl-1 font-bold">Format: JPG, PNG. Max 2MB.</p>
                </div>

                <div class="pt-4 border-t border-gray-100">
                    <label class="block text-[10px] font-black text-navy uppercase tracking-widest mb-2 pl-1 text-red-500">Change Password (Optional)</label>
                    <div class="relative group">
                        <i class="fas fa-lock absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 group-focus-within:text-red-500 transition-colors"></i>
                        <input type="password" name="password_baru" placeholder="Leave empty if you don't want to change password" class="w-full pl-11 pr-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:bg-white focus:border-red-500 focus:ring-2 focus:ring-red-500/20 font-bold text-sm tracking-widest text-navy">
                    </div>
                </div>

                <div class="pt-4">
                    <button type="submit" name="update_profil" class="w-full md:w-auto bg-navy hover:bg-orange text-white px-8 py-4 rounded-xl font-black text-xs uppercase tracking-widest transition-colors shadow-lg shadow-navy/20 hover:shadow-orange/30">
                        Save Changes
                    </button>
                </div>
            </form>
        </div>

        <!-- CARD 2: STATISTIK -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-5 mb-8">
            <div class="bg-white p-6 rounded-[2rem] shadow-sm border border-gray-50 flex flex-col items-center justify-center text-center hover:-translate-y-1 transition-transform">
                <div class="w-12 h-12 bg-navy/5 text-navy rounded-2xl flex items-center justify-center text-xl mb-3"><i class="fas fa-shopping-bag"></i></div>
                <h3 class="text-3xl font-black text-navy mb-1"><?= $total_pesanan ?></h3>
                <p class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Total Orders</p>
            </div>
            <div class="bg-white p-6 rounded-[2rem] shadow-sm border border-gray-50 flex flex-col items-center justify-center text-center hover:-translate-y-1 transition-transform">
                <div class="w-12 h-12 bg-yellow-500/10 text-yellow-500 rounded-2xl flex items-center justify-center text-xl mb-3"><i class="fas fa-star"></i></div>
                <h3 class="text-3xl font-black text-navy mb-1">0</h3>
                <p class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Reviews Given</p>
            </div>
            <div class="bg-white p-6 rounded-[2rem] shadow-sm border border-gray-50 flex flex-col items-center justify-center text-center hover:-translate-y-1 transition-transform">
                <div class="w-12 h-12 bg-red/10 text-red rounded-2xl flex items-center justify-center text-xl mb-3"><i class="far fa-bell"></i></div>
                <h3 class="text-3xl font-black text-navy mb-1"><?= $total_notif ?></h3>
                <p class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Active Notifications</p>
            </div>
        </div>

        <!-- CARD 3: RIWAYAT SINGKAT -->
        <div class="bg-white rounded-[2.5rem] p-8 md:p-10 shadow-sm border border-gray-50 mb-8">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-xl font-black text-navy border-l-4 border-orange pl-3 tracking-tight">Order History</h3>
                <a href="<?= $base_url ?>/pembeli/pesanan.php" class="text-xs font-bold text-orange hover:text-navy transition-colors">View All</a>
            </div>

            <div class="space-y-4">
                <?php if(mysqli_num_rows($q_riwayat) == 0): ?>
                    <div class="text-center py-8 text-gray-400">
                        <i class="fas fa-receipt text-4xl mb-3 text-gray-200"></i>
                        <p class="text-sm font-bold">No order history yet.</p>
                    </div>
                <?php else: ?>
                    <?php while($riwayat = mysqli_fetch_assoc($q_riwayat)): 
                        $status_label = "bg-gray-100 text-gray-500";
                        if($riwayat['status'] == 'selesai') $status_label = "bg-green-100 text-green-600";
                        elseif($riwayat['status'] == 'diproses' || $riwayat['status'] == 'proses') $status_label = "bg-blue-100 text-blue-600";
                        elseif($riwayat['status'] == 'batal') $status_label = "bg-red-100 text-red-600";
                        
                        $layanan_tampil = !empty($riwayat['nama_layanan']) ? htmlspecialchars($riwayat['nama_layanan']) : "Product Removed";
                    ?>
                    <div class="flex items-center justify-between p-4 rounded-2xl border border-gray-100 hover:bg-gray-50 transition-colors">
                        <div class="flex items-center gap-4">
                            <div class="w-10 h-10 bg-gray-100 rounded-xl flex items-center justify-center text-gray-400"><i class="fas fa-image"></i></div>
                            <div>
                                <h4 class="font-black text-navy text-sm md:text-base"><?= $layanan_tampil ?></h4>
                                <p class="text-[10px] text-gray-500 font-bold"><?= htmlspecialchars($riwayat['nama_toko']) ?> • <?= date('d M Y', strtotime($riwayat['created_at'])) ?></p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="font-black text-navy text-sm md:text-base italic mb-1">Rp <?= number_format($riwayat['harga'], 0, ',', '.') ?></p>
                            <span class="<?= $status_label ?> px-2 py-0.5 rounded text-[8px] font-black uppercase tracking-widest inline-block"><?= $riwayat['status'] ?></span>
                        </div>
                    </div>
                    <?php endwhile; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- TOMBOL KELUAR -->
        <a href="<?= $base_url ?>/auth/logout.php" onclick="return confirm('Are you sure you want to sign out?')" class="w-full bg-white border border-red text-red hover:bg-red hover:text-white py-4 rounded-2xl font-black text-xs uppercase tracking-widest transition-all flex justify-center items-center gap-2 group shadow-sm">
            <i class="fas fa-sign-out-alt group-hover:-translate-x-1 transition-transform"></i> Sign Out
        </a>

    </div>
</div>

<?php require_once '../includes/footer.php'; ?>