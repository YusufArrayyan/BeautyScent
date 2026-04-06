<?php
session_start();
require_once '../config/database.php';

// 1. CEK LOGIN
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'toko') {
    header("Location: ../index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$pesanan_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// AMBIL ID TOKO
$q_toko = mysqli_query($conn, "SELECT id FROM toko WHERE user_id = '$user_id'");
$toko_id = mysqli_fetch_assoc($q_toko)['id'];

// =================================================================================
// FITUR RAHASIA: AJAX ENDPOINT UNTUK UPDATE LOKASI TEKNISI KE DATABASE
// =================================================================================
if (isset($_POST['update_lokasi']) && $_POST['update_lokasi'] == '1') {
    $lat = mysqli_real_escape_string($conn, $_POST['lat']);
    $lng = mysqli_real_escape_string($conn, $_POST['lng']);
    
    // Update titik kordinat seller di database secara real-time
    $update = mysqli_query($conn, "UPDATE pesanan SET lat_seller = '$lat', lng_seller = '$lng', status_tracking = 'di_jalan' WHERE id = '$pesanan_id' AND toko_id = '$toko_id'");
    
    if($update) { echo json_encode(['status' => 'ok']); } 
    else { echo json_encode(['status' => 'error']); }
    exit(); // Hentikan eksekusi PHP
}

// LOGIKA TANDAI SUDAH SAMPAI
if (isset($_POST['tandai_sampai'])) {
    mysqli_query($conn, "UPDATE pesanan SET status_tracking = 'sampai' WHERE id = '$pesanan_id' AND toko_id = '$toko_id'");
    $_SESSION['pesan_sukses'] = "Anda telah tiba di lokasi! Silakan selesaikan perbaikan.";
    header("Location: pesanan.php");
    exit();
}
// =================================================================================

// 2. AMBIL DATA PESANAN & PEMBELI
$query = mysqli_query($conn, "
    SELECT p.*, u.id as id_pembeli, u.nama_lengkap as nama_pembeli, u.no_hp as wa_pembeli, u.foto_profil as foto_user, l.nama_layanan
    FROM pesanan p
    JOIN users u ON p.user_id = u.id
    LEFT JOIN layanan l ON p.layanan_id = l.id
    WHERE p.id = '$pesanan_id' AND p.toko_id = '$toko_id'
");

if (!$query || mysqli_num_rows($query) == 0) {
    echo "<script>alert('Data pesanan tidak ditemukan!'); window.location='pesanan.php';</script>";
    exit();
}

$p = mysqli_fetch_assoc($query);

// 3. LOGIKA KOORDINAT (Default Bengkulu jika kosong)
$lat_pembeli = !empty($p['lat_pembeli']) ? $p['lat_pembeli'] : '-3.792845';
$lng_pembeli = !empty($p['lng_pembeli']) ? $p['lng_pembeli'] : '102.260765';

$lat_seller = !empty($p['lat_seller']) ? $p['lat_seller'] : '-3.805000';
$lng_seller = !empty($p['lng_seller']) ? $p['lng_seller'] : '102.265000';

// 4. FOTO PROFIL PEMBELI
$foto_pembeli = "https://ui-avatars.com/api/?name=".urlencode($p['nama_pembeli'])."&background=0a0a2a&color=fff&bold=true";
if (!empty($p['foto_user']) && !in_array(strtolower($p['foto_user']), ['default.png', 'default.jpg'])) {
    $clean_foto = str_replace(['uploads/profil/', 'profil/', 'uploads/'], '', $p['foto_user']);
    $foto_pembeli = $base_url . '/uploads/profil/' . $clean_foto; 
}

// 5. SEMBUNYIKAN SUB-NAVBAR KATEGORI (Fitur bawaan Header)
$hide_subnav = true;

require_once '../includes/header.php'; 
?>

<!-- LEAFLET MAPS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<style>
    /* Bantai habis semua Header, Navbar, Topbar agar Map Full Screen! */
    header, nav, footer, .navbar, #header, #footer, .bg-darkest { display: none !important; }
    body, html { margin: 0; padding: 0; height: 100vh; width: 100vw; overflow: hidden; background: #e5e7eb; }
    
    #map { position: absolute; top: 0; left: 0; width: 100vw; height: 100vh; z-index: 10; }
    
    #bottom-sheet {
        position: absolute; bottom: 0; left: 0; width: 100%;
        background: #ffffff; border-radius: 2rem 2rem 0 0;
        box-shadow: 0 -10px 30px rgba(0,0,0,0.15); z-index: 40;
        transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        padding: 0 1.5rem 3rem 1.5rem; 
    }
    .sheet-collapsed { transform: translateY(calc(100% - 90px)); }

    .marker-seller, .marker-pembeli { background: transparent; border: none; }
    
    .radar-pulse {
        position: absolute; top: -8px; left: -8px; width: 56px; height: 56px;
        background-color: rgba(37, 99, 235, 0.4); border-radius: 50%;
        animation: radar 2s infinite ease-out; z-index: -1;
    }
    @keyframes radar { 0% { transform: scale(0.5); opacity: 1; } 100% { transform: scale(1.5); opacity: 0; } }

    /* Notifikasi Akses GPS */
    #gps-alert { transition: opacity 0.5s; }
</style>

<!-- AREA PETA -->
<div id="map"></div>

<!-- TOMBOL KEMBALI MENGAMBANG -->
<a href="pesanan.php" class="absolute top-5 left-5 bg-white/90 backdrop-blur-md w-10 h-10 rounded-full shadow-lg flex items-center justify-center text-navy text-lg z-50 hover:bg-navy hover:text-white transition-colors border border-gray-200">
    <i class="fas fa-arrow-left"></i>
</a>

<!-- ALERT MENCARI GPS -->
<div id="gps-alert" class="absolute top-20 left-1/2 transform -translate-x-1/2 bg-navy text-white px-5 py-2.5 rounded-full shadow-lg z-50 flex items-center gap-3 text-xs font-black uppercase tracking-widest pointer-events-none">
    <i class="fas fa-satellite-dish animate-pulse text-orange"></i> Mencari Sinyal GPS...
</div>

<!-- PANEL BAWAH (BOTTOM SHEET) -->
<div id="bottom-sheet">
    
    <div id="drag-handle" class="w-full pt-4 pb-4 cursor-pointer flex justify-center">
        <div class="w-16 h-1.5 bg-gray-300 rounded-full hover:bg-gray-400 transition-colors"></div>
    </div>

    <!-- Info Singkat -->
    <div class="flex justify-between items-start mb-6">
        <div class="flex-1 pr-2">
            <div class="inline-flex items-center gap-1.5 bg-blue-50 px-2.5 py-0.5 rounded-full text-blue-600 font-black text-[9px] uppercase tracking-widest mb-1.5 border border-blue-200 animate-pulse">
                <i class="fas fa-route"></i> Mengantar Pesanan
            </div>
            <h2 class="text-lg md:text-xl font-black text-navy leading-tight line-clamp-1">Tujuan: Lokasi Customer</h2>
            
            <!-- [DIPERBAIKI] Error Handling (Fallback jika nama layanan NULL dihapus) -->
            <p class="text-[10px] font-bold text-gray-400 mt-1 uppercase tracking-widest">
                ID: #<?= $p['kode_pesanan'] ?> - <?= htmlspecialchars($p['nama_layanan'] ?? 'Product Removed') ?>
            </p>
        </div>
    </div>

    <!-- Profil Pembeli -->
    <div class="bg-gray-50 rounded-[1.5rem] p-4 flex items-center gap-4 border border-gray-100 mb-6">
        <div class="w-12 h-12 bg-white rounded-full shadow-sm border border-gray-200 overflow-hidden shrink-0">
            <img src="<?= $foto_pembeli ?>" alt="Customer" class="w-full h-full object-cover">
        </div>
        <div class="flex-1 min-w-0">
            <h4 class="font-black text-navy text-sm mb-0.5 truncate"><?= htmlspecialchars($p['nama_pembeli'] ?? 'Anonim') ?></h4>
            <p class="text-[10px] font-bold text-gray-500 uppercase tracking-widest flex items-center gap-1 truncate">
                <i class="fas fa-map-marker-alt text-alertred"></i> Awaiting Your Arrival
            </p>
        </div>
        
        <!-- Tombol Action Hubungi Customer -->
        <div class="flex gap-2 shrink-0">
            <a href="../chat.php?uid=<?= $p['id_pembeli'] ?>" class="w-10 h-10 bg-blue-100 hover:bg-blue-500 text-blue-600 hover:text-white rounded-full flex items-center justify-center text-sm transition-colors shadow-sm">
                <i class="fas fa-comments"></i>
            </a>
            <a href="https://wa.me/<?= htmlspecialchars($p['wa_pembeli']) ?>" target="_blank" class="w-10 h-10 bg-[#25D366]/10 hover:bg-[#25D366] text-[#25D366] hover:text-white rounded-full flex items-center justify-center text-sm transition-colors shadow-sm">
                <i class="fab fa-whatsapp"></i>
            </a>
        </div>
    </div>

    <!-- TOMBOL SAKTI: SAYA SUDAH SAMPAI -->
    <form action="" method="POST">
        <button type="submit" name="tandai_sampai" onclick="return confirm('Pastikan Anda sudah benar-benar tiba di lokasi pelanggan. Lanjutkan?')" class="w-full bg-navy hover:bg-green-500 text-white py-4 rounded-2xl font-black text-sm uppercase tracking-widest shadow-xl flex items-center justify-center gap-3 transition-colors">
            <i class="fas fa-flag-checkered text-lg"></i> Saya Sudah Sampai
        </button>
    </form>

</div>

<!-- SCRIPT PETA & ENGINE LIVE TRACKING -->
<script>
    const bottomSheet = document.getElementById('bottom-sheet');
    const dragHandle = document.getElementById('drag-handle');
    const gpsAlert = document.getElementById('gps-alert');
    
    dragHandle.addEventListener('click', () => { bottomSheet.classList.toggle('sheet-collapsed'); });

    // Koordinat Dasar (Diambil dari Database)
    var latPembeli = <?= $lat_pembeli ?>;
    var lngPembeli = <?= $lng_pembeli ?>;
    var latSeller = <?= $lat_seller ?>; // Titik awal sebelum GPS nyala
    var lngSeller = <?= $lng_seller ?>;

    var map = L.map('map', { zoomControl: false }).setView([latSeller, lngSeller], 15);

    L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', {
        attribution: '&copy; OpenStreetMap'
    }).addTo(map);

    // Paksa Peta Merender Ulang saat Full Screen CSS diterapkan
    setTimeout(() => { map.invalidateSize(); }, 500);

    // Ikon Customer
    var iconPembeli = L.divIcon({
        className: 'marker-pembeli',
        html: `<div class="w-8 h-8 bg-alertred rounded-full border-[3px] border-white shadow-lg flex items-center justify-center text-white text-xs relative">
                  <i class="fas fa-home"></i>
                  <div class="absolute -bottom-1.5 left-1/2 transform -translate-x-1/2 w-1.5 h-1.5 bg-alertred rotate-45"></div>
               </div>`,
        iconSize: [32, 32], iconAnchor: [16, 32]
    });

    // Ikon Seller (Beda warna dengan layar pembeli)
    var iconSeller = L.divIcon({
        className: 'marker-seller',
        html: `<div class="relative">
                  <div class="radar-pulse"></div>
                  <div class="w-10 h-10 bg-blue-600 rounded-full border-[3px] border-white shadow-lg flex items-center justify-center text-white text-sm relative z-10">
                      <i class="fas fa-motorcycle"></i>
                  </div>
               </div>`,
        iconSize: [40, 40], iconAnchor: [20, 20]
    });

    var markerPembeli = L.marker([latPembeli, lngPembeli], {icon: iconPembeli}).addTo(map);
    var markerSeller = L.marker([latSeller, lngSeller], {icon: iconSeller}).addTo(map);

    var routeLine = L.polyline([[latSeller, lngSeller], [latPembeli, lngPembeli]], {
        color: '#2563eb', weight: 4, dashArray: '8, 8', opacity: 0.7
    }).addTo(map);

    var groupBounds = new L.featureGroup([markerPembeli, markerSeller]);
    map.fitBounds(groupBounds.getBounds().pad(0.3));

    // ========================================================
    // ENGINE GPS ASLI: BACA KOORDINAT HP LALU KIRIM KE SERVER
    // ========================================================
    if (navigator.geolocation) {
        navigator.geolocation.watchPosition(
            function(position) {
                gpsAlert.style.opacity = '0'; 

                let realLat = position.coords.latitude;
                let realLng = position.coords.longitude;
                let newLatLng = new L.LatLng(realLat, realLng);
                
                markerSeller.setLatLng(newLatLng);
                routeLine.setLatLngs([newLatLng, [latPembeli, lngPembeli]]);
                map.setView(newLatLng); 

                let formData = new URLSearchParams();
                formData.append('update_lokasi', '1');
                formData.append('lat', realLat);
                formData.append('lng', realLng);

                fetch('lacak_pembeli.php?id=<?= $pesanan_id ?>', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: formData.toString()
                }).catch(err => console.log('Failed kirim GPS ke server.'));

            }, 
            function(error) {
                console.warn('GPS Error:', error.message);
                gpsAlert.innerHTML = '<i class="fas fa-exclamation-triangle text-red-500"></i> Akses GPS Ditolak';
                gpsAlert.classList.replace('bg-navy', 'bg-white');
                gpsAlert.classList.replace('text-white', 'text-red-600');
            }, 
            { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
        );
    } else {
        alert("Browser Anda tidak mendukung fitur Live GPS.");
    }
</script>

</body>
</html>