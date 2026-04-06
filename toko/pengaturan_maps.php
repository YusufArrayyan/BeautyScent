<?php 
require_once '../config/database.php';
session_start();

// 1. KEAMANAN: Cek Login & Role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'toko') {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// 2. AMBIL DATA TOKO
$query_toko = mysqli_query($conn, "SELECT * FROM toko WHERE user_id = '$user_id'");
$toko = mysqli_fetch_assoc($query_toko);

if (!$toko) {
    die("Data toko tidak ditemukan.");
}
$toko_id = $toko['id'];

// 3. LOGIKA SIMPAN LOKASI
if (isset($_POST['simpan_lokasi'])) {
    $alamat    = mysqli_real_escape_string($conn, trim($_POST['alamat']));
    $latitude  = mysqli_real_escape_string($conn, trim($_POST['latitude']));
    $longitude = mysqli_real_escape_string($conn, trim($_POST['longitude']));

    $query_update = "UPDATE toko SET alamat = '$alamat', latitude = '$latitude', longitude = '$longitude' WHERE id = '$toko_id'";
    
    if (mysqli_query($conn, $query_update)) {
        $_SESSION['pesan_sukses'] = "Titik lokasi dan alamat successfully diperbarui!";
    } else {
        $_SESSION['pesan_error'] = "Failed menyimpan lokasi: " . mysqli_error($conn);
    }
    header("Location: pengaturan_maps.php");
    exit();
}

// Set Koordinat Default (Default Location) jika belum ada
$lat_default = !empty($toko['latitude']) ? $toko['latitude'] : '-3.7928';
$lng_default = !empty($toko['longitude']) ? $toko['longitude'] : '102.2608';

require_once '../includes/header.php'; 
?>

<link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />

<div class="bg-[#f4f7fa] min-h-screen pb-24 font-sans text-gray-800">

    <!-- HERO DASHBOARD BANNER -->
    <div class="bg-gradient-to-r from-navy to-[#111144] py-12 relative overflow-hidden shadow-lg">
        <div class="absolute top-0 right-0 w-64 h-64 bg-white/5 rounded-full blur-[80px] pointer-events-none"></div>
        <div class="max-w-screen-xl mx-auto px-5 md:px-8 relative z-10 flex flex-col md:flex-row justify-between items-center gap-6" data-aos="fade-down">
            <div class="flex items-center gap-5">
                <div class="w-16 h-16 bg-gradient-to-br from-orange to-red-500 rounded-2xl flex items-center justify-center text-white text-2xl shadow-xl transform -rotate-6">
                    <i class="fas fa-map-marked-alt"></i>
                </div>
                <div>
                    <h1 class="text-2xl md:text-3xl font-black text-white tracking-tight">Map Settings</h1>
                    <p class="text-sm font-medium text-gray-400">Atur titik lokasi <span class="text-white font-bold"><?= htmlspecialchars($toko['nama_toko']) ?></span> agar mudah ditemukan.</p>
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
        
        <!-- SIDEBAR MENU (KIRI) -->
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
                        <div class="w-8 h-8 rounded-full bg-gray-100 group-hover:bg-navy group-hover:text-white flex items-center justify-center transition-colors"><i class="far fa-calendar-check"></i></div> 
                        Schedule & Queue
                    </a>
                    <a href="promosi.php" class="text-gray-500 hover:bg-gray-50 hover:text-navy flex items-center gap-4 p-4 rounded-2xl font-bold text-sm transition-all group">
                        <div class="w-8 h-8 rounded-full bg-gray-100 group-hover:bg-navy group-hover:text-white flex items-center justify-center transition-colors"><i class="fas fa-bullhorn"></i></div>
                        Ads & Promotions
                    </a>
                    <a href="profil_toko.php" class="text-gray-500 hover:bg-gray-50 hover:text-navy flex items-center gap-4 p-4 rounded-2xl font-bold text-sm transition-all group">
                        <div class="w-8 h-8 rounded-full bg-gray-100 group-hover:bg-navy group-hover:text-white flex items-center justify-center transition-colors"><i class="fas fa-id-card"></i></div>
                        Store Profile
                    </a>
                    <!-- Menu Active: Map Settings -->
                    <a href="pengaturan_maps.php" class="bg-orange/10 text-orange flex items-center gap-4 p-4 rounded-2xl font-black text-sm transition-all border border-orange/20">
                        <div class="w-8 h-8 rounded-full bg-orange text-white flex items-center justify-center shadow-md"><i class="fas fa-map-marked-alt"></i></div>
                        Map Settings
                    </a>
                    <a href="keuangan.php" class="text-gray-500 hover:bg-gray-50 hover:text-navy flex items-center gap-4 p-4 rounded-2xl font-bold text-sm transition-all group">
                        <div class="w-8 h-8 rounded-full bg-gray-100 group-hover:bg-navy group-hover:text-white flex items-center justify-center transition-colors"><i class="fas fa-wallet"></i></div>
                        Finances
                    </a>
                </div>
            </div>
        </div>

        <!-- MAIN KONTEN (KANAN) -->
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

            <div class="bg-white rounded-[2rem] shadow-sm border border-gray-100 overflow-hidden">
                <div class="p-6 md:p-8 border-b border-gray-50 bg-gray-50/50 flex items-start gap-4">
                    <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center text-blue-600 text-xl shrink-0"><i class="fas fa-info-circle"></i></div>
                    <div>
                        <h2 class="text-lg font-black text-navy mb-1">Tentukan Titik Workshop / Basecamp</h2>
                        <p class="text-sm text-gray-500 font-medium leading-relaxed">Geser pin (tanda merah) pada peta di bawah ini ke lokasi tepat bengkel atau rumah Anda. Ini akan membantu pelanggan menemukan Anda.</p>
                    </div>
                </div>

                <form action="" method="POST" class="p-6 md:p-8 space-y-8">
                    
                    <!-- PETA LEAFLET -->
                    <div class="relative rounded-2xl overflow-hidden border-4 border-gray-100 shadow-inner group">
                        <div class="absolute top-4 left-1/2 transform -translate-x-1/2 z-[400] bg-navy/90 backdrop-blur text-white px-5 py-2 rounded-full text-xs font-black shadow-lg uppercase tracking-widest pointer-events-none group-hover:opacity-0 transition-opacity">
                            <i class="fas fa-arrows-alt text-orange mr-2"></i> Geser Pin Merah ke Your Location
                        </div>
                        <div id="map" class="w-full h-[400px] z-10"></div>
                    </div>

                    <!-- INPUT KOORDINAT -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 bg-orange/5 p-6 rounded-2xl border border-orange/10">
                        <div>
                            <label class="block text-[10px] font-black text-navy uppercase tracking-widest mb-2 pl-1">Latitude (Garis Lintang)</label>
                            <div class="relative">
                                <i class="fas fa-globe absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                                <input type="text" id="lat_input" name="latitude" value="<?= $lat_default ?>" readonly class="w-full pl-11 pr-4 py-3.5 bg-white border border-gray-200 rounded-xl focus:outline-none font-bold transition text-sm text-gray-500 cursor-not-allowed">
                            </div>
                        </div>
                        <div>
                            <label class="block text-[10px] font-black text-navy uppercase tracking-widest mb-2 pl-1">Longitude (Garis Bujur)</label>
                            <div class="relative">
                                <i class="fas fa-globe absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                                <input type="text" id="lng_input" name="longitude" value="<?= $lng_default ?>" readonly class="w-full pl-11 pr-4 py-3.5 bg-white border border-gray-200 rounded-xl focus:outline-none font-bold transition text-sm text-gray-500 cursor-not-allowed">
                            </div>
                        </div>
                    </div>

                    <!-- TEXTAREA ALAMAT -->
                    <div>
                        <label class="block text-[10px] font-black text-navy uppercase tracking-widest mb-2 pl-1">Full Address</label>
                        <div class="relative">
                            <i class="fas fa-map-marker-alt absolute left-4 top-5 text-gray-400"></i>
                            <textarea rows="3" name="alamat" required placeholder="e.g.: 123 Main Street, Suite 100, City, State, ZIP Code" class="w-full pl-11 pr-5 py-4 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-orange/20 focus:border-orange focus:bg-white outline-none font-bold transition text-sm text-navy resize-none leading-relaxed"><?= htmlspecialchars($toko['alamat'] ?? '') ?></textarea>
                        </div>
                    </div>

                    <!-- TOMBOL SIMPAN -->
                    <button type="submit" name="simpan_lokasi" class="w-full bg-navy text-white py-4 rounded-2xl font-black text-sm uppercase tracking-widest shadow-xl shadow-navy/20 hover:bg-orange hover:shadow-orange/30 transition-all transform hover:-translate-y-1 flex justify-center items-center gap-3 group">
                        <i class="fas fa-save text-lg group-hover:scale-110 transition-transform"></i> Save Location Maps
                    </button>
                </form>

            </div>
        </div>
    </div>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script>
    AOS.init({ once: true, duration: 800, offset: 50 });

    // 1. Ambil koordinat dari PHP (Default atau yang sudah tersimpan)
    var currentLat = <?= $lat_default ?>;
    var currentLng = <?= $lng_default ?>;

    // 2. Inisialisasi Peta (Zoom level 15 agar cukup dekat)
    var map = L.map('map').setView([currentLat, currentLng], 15);

    // 3. Tambahkan Layer Peta dari OpenStreetMap (GRATIS & NO API KEY)
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
    }).addTo(map);

    // 4. Buat Custom Icon Pin Marker yang keren
    var customIcon = L.icon({
        iconUrl: 'https://cdn.rawgit.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-orange.png',
        shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
        iconSize: [25, 41],
        iconAnchor: [12, 41],
        popupAnchor: [1, -34],
        shadowSize: [41, 41]
    });

    // 5. Tambahkan Marker (Pin) yang BISA DIGESER (draggable: true)
    var marker = L.marker([currentLat, currentLng], {
        icon: customIcon,
        draggable: true 
    }).addTo(map);

    // 6. Tampilkan tooltip saat pertama kali dimuat
    marker.bindPopup("<b>Your Location</b><br>Geser pin ini ke lokasi yang tepat.").openPopup();

    // 7. Event Listener: Update text box latitude & longitude saat marker selesai digeser
    marker.on('dragend', function (e) {
        var position = marker.getLatLng();
        // Update input hidden/readonly
        document.getElementById('lat_input').value = position.lat.toFixed(6);
        document.getElementById('lng_input').value = position.lng.toFixed(6);
        
        // Update popup info
        marker.bindPopup("<b>Location Saved Temporarily</b><br>Don't forget to click Save below!").openPopup();
    });
</script>

<?php require_once '../includes/footer.php'; ?>