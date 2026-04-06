<?php
session_start();
require_once '../config/database.php';

// 1. WAJIB LOGIN SEBAGAI PEMBELI
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'pembeli') {
    header("Location: ../index.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// 2. CEK APAKAH SUDAH PERNAH REVIEW APLIKASI
$cek_ulasan = mysqli_query($conn, "SELECT id FROM ulasan_platform WHERE user_id = '$user_id'");
if (mysqli_num_rows($cek_ulasan) > 0) {
    echo "<script>alert('Thank you! You have already submitted a review for BeautyScent.'); window.location='pesanan.php';</script>";
    exit();
}

// 3. PROSES SIMPAN ULASAN KE DATABASE
if (isset($_POST['kirim_ulasan'])) {
    $rating = (int)$_POST['rating'];
    $komentar = mysqli_real_escape_string($conn, trim($_POST['komentar']));

    if ($rating >= 1 && $rating <= 5) {
        $insert = mysqli_query($conn, "INSERT INTO ulasan_platform (user_id, rating, komentar) VALUES ('$user_id', '$rating', '$komentar')");
        
        if ($insert) {
            $_SESSION['pesan_sukses'] = "Terima kasih banyak! Ulasan Anda membuat BeautyScent semakin baik.";
            header("Location: pesanan.php");
            exit();
        } else {
            $error = "Oops! System is busy, failed to save review. Please try again.";
        }
    } else {
        $error = "Pilih rating bintang terlebih dahulu ya!";
    }
}

require_once '../includes/header.php';
?>

<!-- PUSTAKA ANIMASI -->
<link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">

<div class="bg-[#f4f7fa] min-h-screen font-sans text-gray-800 pb-24 pt-10 flex items-center justify-center relative overflow-hidden">
    
    <!-- Dekorasi Background Latar -->
    <div class="absolute top-0 right-0 w-96 h-96 bg-orange rounded-full blur-[120px] opacity-10 pointer-events-none"></div>
    <div class="absolute bottom-0 left-0 w-80 h-80 bg-red-500 rounded-full blur-[100px] opacity-10 pointer-events-none"></div>

    <div class="max-w-xl w-full mx-auto px-5 relative z-10" data-aos="zoom-in" data-aos-duration="800">
        <div class="bg-white rounded-[2.5rem] shadow-2xl shadow-navy/5 p-8 md:p-10 border border-gray-100 relative overflow-hidden">
            
            <!-- Aksen Garis Atas -->
            <div class="absolute top-0 left-0 w-full h-2 bg-gradient-to-r from-orange to-red-500"></div>

            <div class="text-center mb-8 pt-4">
                <div class="w-20 h-20 bg-gradient-to-br from-orange to-red-500 text-white rounded-full flex items-center justify-center text-4xl mx-auto mb-5 shadow-lg shadow-orange/30 transform hover:scale-110 hover:rotate-12 transition-all duration-300">
                    <i class="far fa-heart"></i>
                </div>
                <h2 class="text-2xl md:text-3xl font-black text-navy tracking-tight">Bagaimana Pengalamanmu?</h2>
                <p class="text-sm text-gray-500 font-medium mt-2 leading-relaxed px-4">Ulasan Anda akan tampil di halaman depan untuk membantu pengguna lain percaya pada <b>BeautyScent</b>.</p>
            </div>

            <?php if(isset($error)): ?>
                <div class="bg-red-50 text-red-500 border border-red-100 p-4 rounded-2xl text-sm font-bold mb-6 text-center animate-pulse">
                    <i class="fas fa-exclamation-circle mr-1"></i> <?= $error ?>
                </div>
            <?php endif; ?>

            <form action="" method="POST">
                <!-- Sembunyikan Input Rating Asli -->
                <input type="hidden" name="rating" id="rating_input" value="0" required>
                
                <!-- Stars Interaktif -->
                <div class="flex justify-center gap-3 mb-8" id="star-container">
                    <i class="fas fa-star text-4xl md:text-5xl text-gray-200 cursor-pointer transition-all duration-300 hover:scale-125" id="star_1" onclick="setRating(1)"></i>
                    <i class="fas fa-star text-4xl md:text-5xl text-gray-200 cursor-pointer transition-all duration-300 hover:scale-125" id="star_2" onclick="setRating(2)"></i>
                    <i class="fas fa-star text-4xl md:text-5xl text-gray-200 cursor-pointer transition-all duration-300 hover:scale-125" id="star_3" onclick="setRating(3)"></i>
                    <i class="fas fa-star text-4xl md:text-5xl text-gray-200 cursor-pointer transition-all duration-300 hover:scale-125" id="star_4" onclick="setRating(4)"></i>
                    <i class="fas fa-star text-4xl md:text-5xl text-gray-200 cursor-pointer transition-all duration-300 hover:scale-125" id="star_5" onclick="setRating(5)"></i>
                </div>

                <div class="mb-8">
                    <label class="block text-[10px] font-black text-navy uppercase tracking-widest mb-3 ml-2">Ceritakan Pengalamanmu (Opsional)</label>
                    <textarea name="komentar" rows="4" placeholder="Misal: Aplikasinya gampang banget dipake! Seller yang datang juga pro banget, mantap BeautyScent! 🔥" class="w-full px-5 py-4 bg-gray-50 border border-gray-200 rounded-2xl focus:border-orange focus:ring-4 focus:ring-orange/10 font-medium text-sm text-navy resize-none transition-all outline-none"></textarea>
                </div>

                <div class="flex gap-3">
                    <a href="pesanan.php" class="w-1/3 bg-gray-100 hover:bg-gray-200 text-gray-500 py-4 rounded-2xl font-black text-[10px] md:text-xs uppercase tracking-widest text-center transition-colors">
                        Kembali
                    </a>
                    <button type="submit" name="kirim_ulasan" class="w-2/3 bg-navy hover:bg-orange text-white py-4 rounded-2xl font-black text-[10px] md:text-xs uppercase tracking-widest shadow-lg shadow-navy/20 transition-all transform hover:-translate-y-1">
                        Submit Review <i class="fas fa-paper-plane ml-1"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script>
    AOS.init({ once: true, duration: 800 });

    function setRating(rating) {
        document.getElementById('rating_input').value = rating;
        for(let i=1; i<=5; i++) {
            let star = document.getElementById('star_'+i);
            if(i <= rating) {
                star.classList.remove('text-gray-200');
                star.classList.add('text-yellow-400', 'drop-shadow-md'); 
            } else {
                star.classList.remove('text-yellow-400', 'drop-shadow-md');
                star.classList.add('text-gray-200');
            }
        }
    }
</script>

<?php require_once '../includes/footer.php'; ?>