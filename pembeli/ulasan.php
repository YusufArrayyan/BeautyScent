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

// Validasi Pesanan
$cek_pesanan = mysqli_query($conn, "SELECT p.*, l.nama_layanan, t.nama_toko 
                                    FROM pesanan p 
                                    JOIN layanan l ON p.layanan_id = l.id 
                                    JOIN toko t ON p.toko_id = t.id 
                                    WHERE p.id = '$pesanan_id' AND p.user_id = '$user_id' AND p.status = 'selesai'");

if (mysqli_num_rows($cek_pesanan) == 0) {
    echo "<script>alert('Order not found or not yet completed!'); window.location='pesanan.php';</script>";
    exit();
}

$data_pesanan = mysqli_fetch_assoc($cek_pesanan);

// Cek apakah sudah pernah diulas
$cek_ulasan = mysqli_query($conn, "SELECT * FROM ulasan WHERE pesanan_id = '$pesanan_id'");
if (mysqli_num_rows($cek_ulasan) > 0) {
    echo "<script>alert('You have already reviewed this order!'); window.location='pesanan.php';</script>";
    exit();
}

// Proses Simpan Ulasan
if (isset($_POST['kirim_ulasan'])) {
    $rating = (int)$_POST['rating'];
    $komentar = mysqli_real_escape_string($conn, $_POST['komentar']);
    $toko_id = $data_pesanan['toko_id'];
    $layanan_id = $data_pesanan['layanan_id'];

    if ($rating >= 1 && $rating <= 5) {
        $insert = mysqli_query($conn, "INSERT INTO ulasan (pesanan_id, user_id, toko_id, layanan_id, rating, komentar) 
                                       VALUES ('$pesanan_id', '$user_id', '$toko_id', '$layanan_id', '$rating', '$komentar')");
        if ($insert) {
            $_SESSION['pesan_sukses'] = "Terima kasih! Ulasan Anda sangat membantu.";
            header("Location: pesanan.php");
            exit();
        } else {
            $error = "Failed menyimpan ulasan.";
        }
    } else {
        $error = "Pilih rating bintang terlebih dahulu!";
    }
}

require_once '../includes/header.php';
?>

<div class="bg-[#f4f7fa] min-h-screen font-sans text-gray-800 pb-24 pt-10">
    <div class="max-w-xl mx-auto px-5">
        <div class="bg-white rounded-[2rem] shadow-xl p-8 border border-gray-100">
            
            <div class="text-center mb-8">
                <div class="w-16 h-16 bg-orange/10 text-orange rounded-full flex items-center justify-center text-3xl mx-auto mb-4">
                    <i class="fas fa-star"></i>
                </div>
                <h2 class="text-2xl font-black text-navy">Nilai Layanan Seller</h2>
                <p class="text-sm text-gray-500 font-medium mt-1">How was your experience with <b><?= htmlspecialchars($data_pesanan['nama_toko']) ?></b> for product <b><?= htmlspecialchars($data_pesanan['nama_layanan']) ?></b>?</p>
            </div>

            <?php if(isset($error)): ?>
                <div class="bg-red-100 text-red-600 p-3 rounded-xl text-sm font-bold mb-4 text-center"><?= $error ?></div>
            <?php endif; ?>

            <form action="" method="POST">
                <!-- Sembunyikan Input Rating Asli -->
                <input type="hidden" name="rating" id="rating_input" value="0" required>
                
                <!-- Stars Interaktif -->
                <div class="flex justify-center gap-2 mb-8" id="star-container">
                    <i class="fas fa-star text-4xl text-gray-300 cursor-pointer transition-colors hover:text-yellow-400" id="star_1" onclick="setRating(1)"></i>
                    <i class="fas fa-star text-4xl text-gray-300 cursor-pointer transition-colors hover:text-yellow-400" id="star_2" onclick="setRating(2)"></i>
                    <i class="fas fa-star text-4xl text-gray-300 cursor-pointer transition-colors hover:text-yellow-400" id="star_3" onclick="setRating(3)"></i>
                    <i class="fas fa-star text-4xl text-gray-300 cursor-pointer transition-colors hover:text-yellow-400" id="star_4" onclick="setRating(4)"></i>
                    <i class="fas fa-star text-4xl text-gray-300 cursor-pointer transition-colors hover:text-yellow-400" id="star_5" onclick="setRating(5)"></i>
                </div>

                <div class="mb-6">
                    <label class="block text-xs font-black text-navy uppercase tracking-widest mb-2">Tulis Pengalamanmu (Opsional)</label>
                    <textarea name="komentar" rows="4" placeholder="Misal: Sellernya ramah banget dan kerjanya rapih. AC langsung dingin kyk di kutub!" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:border-orange font-medium text-sm text-navy resize-none"></textarea>
                </div>

                <button type="submit" name="kirim_ulasan" class="w-full bg-navy hover:bg-orange text-white py-4 rounded-xl font-black text-sm uppercase tracking-widest shadow-lg transition-all transform hover:-translate-y-1">
                    Submit Review Sekarang
                </button>
            </form>
        </div>
    </div>
</div>

<script>
    function setRating(rating) {
        document.getElementById('rating_input').value = rating;
        for(let i=1; i<=5; i++) {
            let star = document.getElementById('star_'+i);
            if(i <= rating) {
                star.classList.remove('text-gray-300');
                star.classList.add('text-yellow-400'); // Warna Emas
            } else {
                star.classList.remove('text-yellow-400');
                star.classList.add('text-gray-300'); // Warna Abu-abu
            }
        }
    }
</script>

<?php require_once '../includes/footer.php'; ?>