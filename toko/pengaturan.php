<?php
require_once '../config/database.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'toko') {
    header("Location: ../auth/login.php");
    exit();
}

$toko_id = $_SESSION['toko_id'];
$pesan = "";

// Ambil data awal agar variabel tidak kosong (mencegah warning)
$query_awal = mysqli_query($conn, "SELECT * FROM toko WHERE id = '$toko_id'");
$data = mysqli_fetch_assoc($query_awal);

// Proses Update Data
if (isset($_POST['update'])) {
    $nama_toko = mysqli_real_escape_string($conn, $_POST['nama_toko']);
    $deskripsi = mysqli_real_escape_string($conn, $_POST['deskripsi']);
    $kategori  = mysqli_real_escape_string($conn, $_POST['kategori_jasa']);
    $alamat    = mysqli_real_escape_string($conn, $_POST['alamat']);

    // Logika Upload Foto
    $foto_name = $data['foto'] ?? ''; // Pakai foto lama jika ada
    if (!empty($_FILES['foto']['name'])) {
        $foto_name = time() . "_" . $_FILES['foto']['name'];
        move_uploaded_file($_FILES['foto']['tmp_name'], '../uploads/profil/' . $foto_name);
    }

    $update = mysqli_query($conn, "UPDATE toko SET 
                nama_toko = '$nama_toko', 
                deskripsi = '$deskripsi', 
                kategori_jasa = '$kategori',
                alamat = '$alamat',
                foto = '$foto_name'
                WHERE id = '$toko_id'");

    if ($update) {
        $pesan = "<div class='bg-green-500 text-white p-4 rounded-2xl mb-6 shadow-lg'>✨ Profil successfully diperbarui!</div>";
        // Refresh data setelah update
        $query_awal = mysqli_query($conn, "SELECT * FROM toko WHERE id = '$toko_id'");
        $data = mysqli_fetch_assoc($query_awal);
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Pengaturan Store - BeautyScent</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script>
        tailwind.config = { theme: { extend: { colors: { navy: '#0b0b3a', orange: '#ff6600' } } } }
    </script>
</head>
<body class="bg-gray-50 flex">

    <!-- Sidebar -->
    <div class="w-64 bg-navy min-h-screen text-white p-6 hidden md:block sticky top-0 h-screen">
        <h2 class="text-2xl font-bold mb-10">Elok<span class="text-orange">Galo</span></h2>
        <nav class="space-y-4">
            <a href="index.php" class="flex items-center gap-3 p-3 hover:bg-white/10 rounded-xl transition text-gray-400">
                <i class="fas fa-home"></i> Dashboard
            </a>
            <a href="pengaturan.php" class="flex items-center gap-3 bg-orange p-3 rounded-xl font-bold shadow-lg">
                <i class="fas fa-user-cog"></i> Pengaturan Store
            </a>
            <hr class="border-white/10 my-4">
            <a href="../auth/logout.php" class="flex items-center gap-3 p-3 text-red-400 hover:bg-red-500/10 rounded-xl transition">
                <i class="fas fa-sign-out-alt"></i> Keluar
            </a>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="flex-1 p-8 lg:p-12">
        <h1 class="text-4xl font-black text-navy mb-2">Pengaturan Profil</h1>
        <p class="text-gray-500 mb-10">Lengkapi data tokomu agar pelanggan semakin percaya.</p>
        
        <?= $pesan ?>

        <form action="" method="POST" enctype="multipart/form-data" class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Foto -->
            <div class="lg:col-span-1">
                <div class="bg-white p-8 rounded-[2rem] shadow-sm border text-center">
                    <label class="block text-sm font-bold text-gray-700 mb-4">Foto Store Profile</label>
                    <div class="relative inline-block">
                        <?php 
                            $img = (!empty($data['foto'])) ? '../uploads/profil/'.$data['foto'] : "https://ui-avatars.com/api/?name=".urlencode($data['nama_toko'])."&background=0b0b3a&color=fff";
                        ?>
                        <img src="<?= $img ?>" id="preview" class="w-40 h-40 object-cover rounded-[2rem] shadow-md border-4 border-gray-50">
                        <label for="upload" class="absolute -bottom-2 -right-2 bg-orange text-white w-10 h-10 rounded-xl flex items-center justify-center cursor-pointer hover:scale-110 transition shadow-lg">
                            <i class="fas fa-camera"></i>
                        </label>
                        <input type="file" name="foto" id="upload" class="hidden" onchange="previewImage(event)">
                    </div>
                </div>
            </div>

            <!-- Form -->
            <div class="lg:col-span-2">
                <div class="bg-white p-10 rounded-[2.5rem] shadow-sm border space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-xs font-black text-gray-400 uppercase mb-2">Store Name</label>
                            <input type="text" name="nama_toko" value="<?= htmlspecialchars($data['nama_toko']) ?>" class="w-full px-4 py-3 bg-gray-50 border rounded-xl outline-none focus:ring-2 focus:ring-orange">
                        </div>
                        <div>
                            <label class="block text-xs font-black text-gray-400 uppercase mb-2">Kategori</label>
                            <select name="kategori_jasa" class="w-full px-4 py-3 bg-gray-50 border rounded-xl outline-none">
                                <option value="Elektronik Rumahan" <?= ($data['kategori_jasa'] == 'Elektronik Rumahan') ? 'selected' : '' ?>>📺 Elektronik Rumahan</option>
                                <option value="Servis Gadget & HP" <?= ($data['kategori_jasa'] == 'Servis Gadget & HP') ? 'selected' : '' ?>>📱 Servis Gadget & HP</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-black text-gray-400 uppercase mb-2">Alamat di Bengkulu</label>
                        <input type="text" name="alamat" value="<?= htmlspecialchars($data['alamat'] ?? '') ?>" class="w-full px-4 py-3 bg-gray-50 border rounded-xl outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-black text-gray-400 uppercase mb-2">Deskripsi Layanan</label>
                        <textarea name="deskripsi" rows="4" class="w-full px-4 py-3 bg-gray-50 border rounded-xl outline-none"><?= htmlspecialchars($data['deskripsi'] ?? '') ?></textarea>
                    </div>
                    <button type="submit" name="update" class="bg-navy text-white px-10 py-4 rounded-2xl font-black shadow-lg hover:bg-orange transition-all transform hover:-translate-y-1">
                        Save Changes
                    </button>
                </div>
            </div>
        </form>
    </div>

    <script>
        function previewImage(event) {
            var reader = new FileReader();
            reader.onload = function(){ document.getElementById('preview').src = reader.result; };
            reader.readAsDataURL(event.target.files[0]);
        }
    </script>
</body>
</html>