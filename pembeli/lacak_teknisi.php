<?php
session_start();
require_once '../config/database.php';

// 1. CEK LOGIN
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'pembeli') {
    header("Location: ../index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$pesanan_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// AJAX ENDPOINT UNTUK LIVE TRACKING
if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
    $q_ajax = mysqli_query($conn, "SELECT lat_seller, lng_seller, status_tracking FROM pesanan WHERE id = '$pesanan_id' AND user_id = '$user_id'");
    if($q_ajax && mysqli_num_rows($q_ajax) > 0) {
        $data = mysqli_fetch_assoc($q_ajax);
        echo json_encode($data);
    }
    exit();
}

// 2. AMBIL DATA PESANAN & TEKNISI
$query = mysqli_query($conn, "
    SELECT p.*, t.nama_toko, u.nama_lengkap as nama_seller, u.no_hp, u.foto_profil as foto_user, l.nama_layanan
    FROM pesanan p
    JOIN toko t ON p.toko_id = t.id
    JOIN users u ON t.user_id = u.id
    LEFT JOIN layanan l ON p.layanan_id = l.id
    WHERE p.id = '$pesanan_id' AND p.user_id = '$user_id'
");

if (!$query || mysqli_num_rows($query) == 0) {
    echo "<script>alert('Data pesanan tidak ditemukan!'); window.location='pesanan.php';</script>";
    exit();
}

$p = mysqli_fetch_assoc($query);

// Jika status sudah selesai atau batal, tidak usah dilacak lagi
if (in_array($p['status'], ['selesai', 'dibatalkan', 'batal'])) {
    echo "<script>alert('Pesanan sudah selesai/dibatalkan. Pelacakan ditutup.'); window.location='pesanan.php';</script>";
    exit();
}

// 3. LOGIKA KOORDINAT (Default Kota Bengkulu)
$lat_pembeli = !empty($p['lat_pembeli']) ? $p['lat_pembeli'] : '-3.792845';
$lng_pembeli = !empty($p['lng_pembeli']) ? $p['lng_pembeli'] : '102.260765';

$lat_seller = !empty($p['lat_seller']) ? $p['lat_seller'] : '-3.805000';
$lng_seller = !empty($p['lng_seller']) ? $p['lng_seller'] : '102.265000';

// 4. JURUS ULTIMATE TRABAS FOTO PROFIL TEKNISI
$img_profil = "https://ui-avatars.com/api/?name=".urlencode($p['nama_toko'])."&background=ff6600&color=fff&size=150&bold=true";
$raw_foto = !empty($p['foto_user']) ? $p['foto_user'] : '';

if (!empty($raw_foto) && !in_array(strtolower($raw_foto), ['default.png', 'default.jpg'])) {
    $clean_foto = str_replace(['uploads/profil/', 'profil/', 'uploads/'], '', $raw_foto);
    $img_profil = $base_url . '/uploads/profil/' . $clean_foto; 
}

// Panggil Header
require_once '../includes/header.php'; 
?>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<style>
    /* Bantai habis semua header, nav, dan footer bawaan template agar benar-benar Full Screen */
    header, nav, footer, .navbar, #header, #footer { display: none !important; }
    
    body, html { 
        margin: 0; padding: 0; height: 100%; width: 100%; overflow: hidden; background: #e5e7eb; 
    }
    
    /* Peta dibuat Absolute agar menutupi seluruh layar */
    #map {
        position: absolute;
        top: 0; left: 0;
        width: 100vw;
        height: 100vh;
        z-index: 10;
    }
    
    /* Bottom Sheet Kustomisasi Tarik */
    #bottom-sheet {
        position: absolute;
        bottom: 0; left: 0; width: 100%;
        background: #ffffff;
        border-radius: 2rem 2rem 0 0;
        box-shadow: 0 -10px 30px rgba(0,0,0,0.15);
        z-index: 40;
        transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        /* Padding bawah dilebarkan agar aman dari safe-area layar HP */
        padding: 0 1.5rem 3rem 1.5rem; 
    }

    /* Status ketika disembunyikan (Hanya menyisakan handle dan judul) */
    .sheet-collapsed {
        transform: translateY(calc(100% - 90px)); 
    }

    .marker-seller, .marker-pembeli { background: transparent; border: none; }
    
    .radar-pulse {
        position: absolute; top: -8px; left: -8px; width: 56px; height: 56px;
        background-color: rgba(255, 102, 0, 0.4); border-radius: 50%;
        animation: radar 2s infinite ease-out; z-index: -1;
    }
    @keyframes radar { 0% { transform: scale(0.5); opacity: 1; } 100% { transform: scale(1.5); opacity: 0; } }
</style>

<!-- AREA PETA -->
<div id="map"></div>

<!-- TOMBOL KEMBALI MENGAMBANG -->
<a href="pesanan.php" class="absolute top-5 left-5 bg-white/90 backdrop-blur-md w-10 h-10 rounded-full shadow-lg flex items-center justify-center text-navy text-lg z-50 hover:bg-navy hover:text-white transition-colors border border-gray-200">
    <i class="fas fa-arrow-left"></i>
</a>

<!-- PANEL BAWAH (BOTTOM SHEET) -->
<div id="bottom-sheet" class="sheet-collapsed">
    
    <!-- Garis Drag (Bisa di-klik untuk buka/tutup) -->
    <div id="drag-handle" class="w-full pt-4 pb-4 cursor-pointer flex justify-center">
        <div class="w-16 h-1.5 bg-gray-300 rounded-full hover:bg-gray-400 transition-colors"></div>
    </div>

    <!-- Info Singkat -->
    <div class="flex justify-between items-start mb-6">
        <div class="flex-1 pr-2">
            <div class="inline-flex items-center gap-1.5 bg-orange/10 px-2.5 py-0.5 rounded-full text-orange font-black text-[9px] uppercase tracking-widest mb-1.5 border border-orange/20 animate-pulse">
                <i class="fas fa-motorcycle"></i> Seller Menuju Lokasi
            </div>
            <h2 class="text-lg md:text-xl font-black text-navy leading-tight line-clamp-1"><?= htmlspecialchars($p['nama_layanan']) ?></h2>
            <p class="text-[10px] font-bold text-gray-400 mt-1 uppercase tracking-widest">ID: #<?= $p['kode_pesanan'] ?></p>
        </div>
        <div class="text-right shrink-0">
            <p class="text-[9px] font-bold text-gray-400 uppercase tracking-widest mb-0.5">Biaya</p>
            <p class="text-base md:text-lg font-black text-navy whitespace-nowrap">Rp <?= number_format($p['total_harga'], 0, ',', '.') ?></p>
        </div>
    </div>

    <!-- Profil Seller -->
    <div class="bg-gray-50 rounded-[1.5rem] p-4 flex items-center gap-4 border border-gray-100">
        <div class="w-12 h-12 bg-white rounded-full shadow-sm border border-white overflow-hidden shrink-0">
            <img src="<?= $img_profil ?>" alt="Seller" class="w-full h-full object-cover">
        </div>
        <div class="flex-1 min-w-0">
            <h4 class="font-black text-navy text-sm mb-0.5 truncate"><?= htmlspecialchars($p['nama_seller']) ?></h4>
            <p class="text-[10px] font-bold text-gray-500 uppercase tracking-widest flex items-center gap-1 truncate">
                <i class="fas fa-store text-gray-300"></i> <?= htmlspecialchars($p['nama_toko']) ?>
            </p>
        </div>
        
        <!-- Tombol Action -->
        <div class="flex gap-2 shrink-0">
            <!-- Arahkan ke chat.php internal -->
            <a href="chat.php?toko_id=<?= $p['toko_id'] ?>" class="w-10 h-10 bg-blue-100 hover:bg-blue-500 text-blue-600 hover:text-white rounded-full flex items-center justify-center text-sm transition-colors shadow-sm">
                <i class="fas fa-comments"></i>
            </a>
            <a href="https://wa.me/<?= htmlspecialchars($p['no_hp']) ?>?text=Hello%20<?= urlencode($p['nama_toko']) ?>,%20posisi%20seller%20sekarang%20dimana%20ya?" target="_blank" class="w-10 h-10 bg-[#25D366]/10 hover:bg-[#25D366] text-[#25D366] hover:text-white rounded-full flex items-center justify-center text-sm transition-colors shadow-sm">
                <i class="fab fa-whatsapp"></i>
            </a>
        </div>
    </div>

</div>

<!-- SCRIPT PETA & LIVE TRACKING & TOGGLE PANEL -->
<script>
    // Logika Panel Naik-Turun (Bisa di-klik garis abu-abunya)
    const bottomSheet = document.getElementById('bottom-sheet');
    const dragHandle = document.getElementById('drag-handle');
    
    // Buka panel otomatis setelah peta dimuat (1 detik)
    setTimeout(() => { bottomSheet.classList.remove('sheet-collapsed'); }, 1000);

    dragHandle.addEventListener('click', () => {
        bottomSheet.classList.toggle('sheet-collapsed');
    });

    // Peta Setup
    var latPembeli = <?= $lat_pembeli ?>;
    var lngPembeli = <?= $lng_pembeli ?>;
    var latSeller = <?= $lat_seller ?>;
    var lngSeller = <?= $lng_seller ?>;

    var map = L.map('map', { zoomControl: false }).setView([latPembeli, lngPembeli], 15);

    L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', {
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    // Paksa map untuk merender ulang ukurannya setelah CSS absolute diaplikasikan
    setTimeout(() => { map.invalidateSize(); }, 500);

    var iconPembeli = L.divIcon({
        className: 'marker-pembeli',
        html: `<div class="w-8 h-8 bg-navy rounded-full border-[3px] border-white shadow-[0_8px_15px_rgba(0,0,0,0.3)] flex items-center justify-center text-white text-xs relative">
                  <i class="fas fa-home"></i>
                  <div class="absolute -bottom-1.5 left-1/2 transform -translate-x-1/2 w-1.5 h-1.5 bg-navy rotate-45"></div>
               </div>`,
        iconSize: [32, 32],
        iconAnchor: [16, 32]
    });

    var iconSeller = L.divIcon({
        className: 'marker-seller',
        html: `<div class="relative">
                  <div class="radar-pulse"></div>
                  <div class="w-10 h-10 bg-orange rounded-full border-[3px] border-white shadow-[0_8px_15px_rgba(255,102,0,0.5)] flex items-center justify-center text-white text-sm relative z-10">
                      <i class="fas fa-motorcycle"></i>
                  </div>
               </div>`,
        iconSize: [40, 40],
        iconAnchor: [20, 20]
    });

    var markerPembeli = L.marker([latPembeli, lngPembeli], {icon: iconPembeli}).addTo(map);
    var markerSeller = L.marker([latSeller, lngSeller], {icon: iconSeller}).addTo(map);

    var routeLine = L.polyline([[latSeller, lngSeller], [latPembeli, lngPembeli]], {
        color: '#ff6600',
        weight: 3,
        dashArray: '8, 8',
        opacity: 0.6
    }).addTo(map);

    // Zoom agar kedua marker terlihat jelas di layar
    var groupBounds = new L.featureGroup([markerPembeli, markerSeller]);
    map.fitBounds(groupBounds.getBounds().pad(0.3));

    // Radar Tracking (Tarik data setiap 5 detik)
    setInterval(function() {
        fetch('lacak_seller.php?id=<?= $pesanan_id ?>&ajax=1')
            .then(response => response.json())
            .then(data => {
                if(data.lat_seller && data.lng_seller) {
                    var newLat = parseFloat(data.lat_seller);
                    var newLng = parseFloat(data.lng_seller);
                    var newLatLng = new L.LatLng(newLat, newLng);
                    
                    markerSeller.setLatLng(newLatLng);
                    routeLine.setLatLngs([newLatLng, [latPembeli, lngPembeli]]);
                }
                if(data.status_tracking === 'sampai' || data.status_tracking === 'selesai') {
                    window.location.reload();
                }
            })
            .catch(error => console.log('Radar error:', error));
    }, 5000); 
</script>

</body>
</html>