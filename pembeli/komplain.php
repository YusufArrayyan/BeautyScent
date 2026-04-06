<?php
session_start();
require_once '../config/database.php';

// Wajib Login
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'pembeli') {
    header("Location: ../index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$pesanan_id = isset($_GET['id']) ? mysqli_real_escape_string($conn, $_GET['id']) : '';

// Validasi Pesanan (Hanya bisa komplain jika status Selesai)
$cek_pesanan = mysqli_query($conn, "SELECT p.*, l.nama_layanan, t.nama_toko 
                                    FROM pesanan p 
                                    JOIN layanan l ON p.layanan_id = l.id 
                                    JOIN toko t ON p.toko_id = t.id 
                                    WHERE p.id = '$pesanan_id' AND p.user_id = '$user_id' AND p.status = 'selesai'");

if (mysqli_num_rows($cek_pesanan) == 0) {
    echo "<script>alert('Pesanan tidak valid untuk dikomplain!'); window.location='pesanan.php';</script>";
    exit();
}

$data_pesanan = mysqli_fetch_assoc($cek_pesanan);

// Cek apakah sudah pernah dikomplain
$cek_komplain = mysqli_query($conn, "SELECT * FROM komplain WHERE pesanan_id = '$pesanan_id'");
if (mysqli_num_rows($cek_komplain) > 0) {
    echo "<script>alert('Anda sudah mengajukan komplain untuk pesanan ini!'); window.location='pesanan.php';</script>";
    exit();
}

if (isset($_POST['kirim_komplain'])) {
    $alasan = mysqli_real_escape_string($conn, $_POST['alasan']);
    $toko_id = $data_pesanan['toko_id'];
    
    // Proses Upload Evidence Photo
    $foto_bukti = $_FILES['foto_bukti']['name'];
    $tmp_name = $_FILES['foto_bukti']['tmp_name'];
    $ext = pathinfo($foto_bukti, PATHINFO_EXTENSION);
    $nama_foto_baru = 'komplain_' . time() . '.' . $ext;
    $path = '../uploads/komplain/';
    
    // Buat folder jika belum ada
    if (!is_dir($path)) { mkdir($path, 0777, true); }

    if (move_uploaded_file($tmp_name, $path . $nama_foto_baru)) {
        // Insert data komplain
        $insert = mysqli_query($conn, "INSERT INTO komplain (pesanan_id, user_id, toko_id, alasan, foto_bukti) 
                                       VALUES ('$pesanan_id', '$user_id', '$toko_id', '$alasan', '$nama_foto_baru')");
        
        if ($insert) {
            // Ubah status pesanan menjadi dikomplain agar uang tertahan
            mysqli_query($conn, "UPDATE pesanan SET status = 'dikomplain' WHERE id = '$pesanan_id'");
            
            $_SESSION['pesan_sukses'] = "Komplain successfully diajukan. Tim Admin akan segera menengahi masalah ini.";
            header("Location: pesanan.php");
            exit();
        } else {
            $error = "Database error: " . mysqli_error($conn);
        }
    } else {
        $error = "Failed mengunggah foto bukti.";
    }
}

require_once '../includes/header.php';
?>

<div class="bg-[#f4f7fa] min-h-screen font-sans text-gray-800 pb-24 pt-10">
    <div class="max-w-xl mx-auto px-5">
        <div class="bg-white rounded-[2rem] shadow-xl p-8 border border-gray-100 relative overflow-hidden">
            <!-- Hiasan Merah Tanda Bahaya -->
            <div class="absolute top-0 left-0 w-full h-2 bg-red-500"></div>

            <div class="text-center mb-8">
                <div class="w-16 h-16 bg-red-50 text-red-500 rounded-full flex items-center justify-center text-3xl mx-auto mb-4 border-2 border-red-200">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <h2 class="text-2xl font-black text-navy">Pusat Resolusi</h2>
                <p class="text-sm text-gray-500 font-medium mt-1">Ajukan komplain untuk pesanan <b><?= htmlspecialchars($data_pesanan['nama_layanan']) ?></b> dari <b><?= htmlspecialchars($data_pesanan['nama_toko']) ?></b>.</p>
            </div>

            <?php if(isset($error)): ?>
                <div class="bg-red-100 text-red-600 p-4 rounded-xl text-sm font-bold mb-6 flex items-center gap-3">
                    <i class="fas fa-exclamation-triangle"></i> <?= $error ?>
                </div>
            <?php endif; ?>

            <form action="" method="POST" enctype="multipart/form-data">
                <div class="mb-5">
                    <label class="block text-xs font-black text-navy uppercase tracking-widest mb-2">Complaint Reason</label>
                    <textarea name="alasan" required rows="4" placeholder="Jelaskan masalah secara detail. Contoh: AC masih netes airnya, seller kerjanya belum selesai tapi buru-buru pulang..." class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:border-red-500 focus:ring-2 focus:ring-red-200 font-medium text-sm text-navy resize-none"></textarea>
                </div>

                <div class="mb-8">
                    <label class="block text-xs font-black text-navy uppercase tracking-widest mb-2">Upload Evidence Photo</label>
                    <div class="relative w-full h-32 border-2 border-dashed border-gray-300 rounded-xl bg-gray-50 hover:bg-gray-100 transition-colors flex flex-col items-center justify-center cursor-pointer overflow-hidden group">
                        <input type="file" name="foto_bukti" id="foto_bukti" required accept="image/*" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10" onchange="previewFile()">
                        <i class="fas fa-cloud-upload-alt text-3xl text-gray-400 group-hover:text-red-500 transition-colors mb-2"></i>
                        <span id="file-name" class="text-xs font-bold text-gray-500">Klik atau seret foto ke sini</span>
                    </div>
                </div>

                <div class="bg-yellow-50 border border-yellow-200 p-4 rounded-xl mb-6">
                    <p class="text-[10px] font-bold text-yellow-700 leading-relaxed">
                        <i class="fas fa-info-circle mr-1"></i> Dana pembayaran Anda saat ini sedang kami tahan. Tim BeautyScent akan menghubungi Anda dan Seller dalam waktu 1x24 jam untuk mencari solusi terbaik (Perbaikan Ulang / Refund).
                    </p>
                </div>

                <button type="submit" name="kirim_komplain" class="w-full bg-red-500 hover:bg-red-600 text-white py-4 rounded-xl font-black text-sm uppercase tracking-widest shadow-lg transition-all transform hover:-translate-y-1 flex justify-center items-center gap-2">
                    <i class="fas fa-paper-plane"></i> Submit Complaint
                </button>
            </form>
        </div>
    </div>
</div>

<script>
    function previewFile() {
        const file = document.getElementById('foto_bukti').files[0];
        if(file) {
            document.getElementById('file-name').textContent = file.name;
            document.getElementById('file-name').classList.add('text-red-500');
        }
    }
</script>

<?php require_once '../includes/footer.php'; ?>