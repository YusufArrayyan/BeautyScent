<?php
session_start();
require_once '../config/database.php';

// PENGHADANG: Wajib Login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php?redirect=pembeli/keranjang.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$pesan_error = '';

// ===================================================
// [FITUR BARU] JURUS AJAX UNTUK CEK VOUCHER LIVE
// ===================================================
if (isset($_GET['action']) && $_GET['action'] == 'cek_voucher') {
    header('Content-Type: application/json');
    $kode = mysqli_real_escape_string($conn, strtoupper(trim($_GET['kode'])));
    $total_belanja_cek = (int)$_GET['total'];

    $q_voc = mysqli_query($conn, "SELECT * FROM vouchers WHERE kode_voucher = '$kode' AND berlaku_sampai >= CURDATE() AND kuota > 0");
    if (mysqli_num_rows($q_voc) > 0) {
        $voc = mysqli_fetch_assoc($q_voc);
        if ($total_belanja_cek >= $voc['minimal_belanja']) {
            $diskon = ($voc['tipe_diskon'] == 'nominal') ? $voc['nilai_diskon'] : round(($voc['nilai_diskon'] / 100) * $total_belanja_cek);
            if ($diskon > $total_belanja_cek) $diskon = $total_belanja_cek; // Mencegah diskon melebihi harga
            echo json_encode(['status' => 'ok', 'diskon' => $diskon, 'pesan' => "Mantap! Discount Rp " . number_format($diskon, 0, ',', '.') . " successfully diterapkan."]);
        } else {
            echo json_encode(['status' => 'error', 'pesan' => "Minimal belanja belum tercapai (Min. Rp " . number_format($voc['minimal_belanja'], 0, ',', '.') . ")"]);
        }
    } else {
        echo json_encode(['status' => 'error', 'pesan' => "Voucher tidak valid, habis, atau sudah kadaluarsa!"]);
    }
    exit();
}
// ===================================================

// [!] KONFIGURASI MIDTRANS [!]
$MIDTRANS_SERVER_KEY = 'SB-Mid-server-Cd2x81xYYDa0AHINgoeU_dc7'; 

// Ambil Data User
$q_user = mysqli_query($conn, "SELECT nama_lengkap, email, no_hp FROM users WHERE id = '$user_id'");
$data_user = mysqli_fetch_assoc($q_user);

// Ambil Keranjang
$query_keranjang = mysqli_query($conn, "SELECT k.*, l.nama_layanan, t.nama_toko FROM keranjang k JOIN layanan l ON k.layanan_id = l.id JOIN toko t ON k.toko_id = t.id WHERE k.user_id = '$user_id'");
if (mysqli_num_rows($query_keranjang) == 0) {
    header("Location: keranjang.php");
    exit();
}

$total_belanja = 0;
$biaya_aplikasi = 2000;
while($item = mysqli_fetch_assoc($query_keranjang)) {
    $total_belanja += ($item['harga_satuan'] * $item['jumlah']);
}
$total_akhir = $total_belanja + $biaya_aplikasi;

// ===================================================
// PROSES SAAT TOMBOL "BUAT PESANAN" DITEKAN
// ===================================================
if (isset($_POST['buat_pesanan'])) {
    $alamat = mysqli_real_escape_string($conn, $_POST['alamat']);
    $lat = mysqli_real_escape_string($conn, $_POST['latitude']);
    $lng = mysqli_real_escape_string($conn, $_POST['longitude']);
    $tanggal = mysqli_real_escape_string($conn, $_POST['tanggal']);
    $waktu = mysqli_real_escape_string($conn, $_POST['waktu']);
    $metode_pembayaran = mysqli_real_escape_string($conn, $_POST['metode_pembayaran']); 
    $kode_pesanan = 'ELOK-' . strtoupper(substr(uniqid(), -6)) . rand(10,99);
    
    // [FITUR BARU] VALIDASI VOUCHER SAAT CHECKOUT
    $kode_voucher = isset($_POST['kode_voucher']) ? mysqli_real_escape_string($conn, strtoupper(trim($_POST['kode_voucher']))) : NULL;
    $nilai_diskon = 0;
    
    if (!empty($kode_voucher)) {
        $q_cek = mysqli_query($conn, "SELECT * FROM vouchers WHERE kode_voucher = '$kode_voucher' AND berlaku_sampai >= CURDATE() AND kuota > 0");
        if (mysqli_num_rows($q_cek) > 0) {
            $voc = mysqli_fetch_assoc($q_cek);
            if ($total_belanja >= $voc['minimal_belanja']) {
                $nilai_diskon = ($voc['tipe_diskon'] == 'nominal') ? $voc['nilai_diskon'] : round(($voc['nilai_diskon'] / 100) * $total_belanja);
                if ($nilai_diskon > $total_belanja) $nilai_diskon = $total_belanja;
                
                // Kurangi kuota voucher di database
                mysqli_query($conn, "UPDATE vouchers SET kuota = kuota - 1 WHERE id = '{$voc['id']}'");
            } else {
                $pesan_error = "Minimal belanja untuk voucher ini adalah Rp " . number_format($voc['minimal_belanja'], 0, ',', '.');
            }
        } else {
            $pesan_error = "Kode voucher tidak valid atau sudah habis.";
        }
    }

    // Potong total dengan diskon
    if(empty($pesan_error)){
        $total_akhir = $total_akhir - $nilai_diskon;
    }

    $snap_token = NULL;
    $status_awal = 'menunggu_pembayaran';

    if (empty($pesan_error) && $metode_pembayaran === 'Pembayaran Online') {
        
        $payload = [
            'transaction_details' => [
                'order_id' => $kode_pesanan,
                'gross_amount' => $total_akhir, // Midtrans akan baca total harga yang sudah didiskon!
            ],
            'customer_details' => [
                'first_name' => $data_user['nama_lengkap'],
                'email' => $data_user['email'],
                'phone' => $data_user['no_hp'],
                'billing_address' => ['address' => $alamat]
            ]
        ];

        // API MIDTRANS
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://app.sandbox.midtrans.com/snap/v1/transactions');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Basic ' . base64_encode($MIDTRANS_SERVER_KEY . ':')
        ]);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code == 201) {
            $response_json = json_decode($response, true);
            $snap_token = $response_json['token'];
        } else {
            $pesan_error = "Midtrans Error ($http_code). Pastikan Server Key Sandbox Bosku sudah benar (Diawali 'SB-').";
        }
    } elseif (empty($pesan_error)) {
        $status_awal = 'diproses'; 
    }

    // Insert ke Database
    if (empty($pesan_error)) {
        mysqli_data_seek($query_keranjang, 0); 
        $semua_sukses = true;
        
        while ($item = mysqli_fetch_assoc($query_keranjang)) {
            $toko_id = $item['toko_id'];
            $layanan_id = $item['layanan_id'];
            $harga = $item['harga_satuan'];
            $jumlah = $item['jumlah'];
            
            // Hitung diskon proporsional per barang agar rapi di tabel
            $prop_diskon = ($total_belanja > 0) ? round((($harga * $jumlah) / $total_belanja) * $nilai_diskon) : 0;
            $total_setelah_diskon = ($harga * $jumlah) - $prop_diskon;
            $catatan = mysqli_real_escape_string($conn, $item['catatan']);

            // [FITUR BARU] Insert menyertakan kolom diskon & kode_voucher
            $insert = mysqli_query($conn, "INSERT INTO pesanan (kode_pesanan, snap_token, user_id, toko_id, layanan_id, harga, jumlah, total_harga, alamat_layanan, latitude, longitude, tanggal_layanan, waktu_layanan, metode_pembayaran, catatan, status, diskon, kode_voucher) 
                                           VALUES ('$kode_pesanan', '$snap_token', '$user_id', '$toko_id', '$layanan_id', '$harga', '$jumlah', '$total_setelah_diskon', '$alamat', '$lat', '$lng', '$tanggal', '$waktu', '$metode_pembayaran', '$catatan', '$status_awal', '$prop_diskon', '$kode_voucher')");
            if (!$insert) $semua_sukses = false;
        }

        if ($semua_sukses) {
            mysqli_query($conn, "DELETE FROM keranjang WHERE user_id = '$user_id'");
            if ($metode_pembayaran === 'Pembayaran Online') {
                header("Location: pembayaran.php?kode=" . $kode_pesanan);
            } else {
                $_SESSION['pesan_sukses'] = "Pesanan COD successfully! Seller akan segera datang.";
                header("Location: pesanan.php");
            }
            exit();
        } else {
            $pesan_error = "Failed menyimpan ke database. Error: " . mysqli_error($conn);
        }
    }
}

mysqli_data_seek($query_keranjang, 0);
require_once '../includes/header.php';
?>

<!-- PETA LEAFLET -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<style>
    #map { height: 350px; width: 100%; border-radius: 1rem; border: 2px solid #e2e8f0; margin-bottom: 1rem; z-index: 1;}
</style>

<div class="bg-[#f4f7fa] min-h-screen pb-24 font-sans text-gray-800 pt-6 md:pt-10">
    <div class="max-w-screen-xl mx-auto px-4 md:px-8">
        
        <div class="mb-6 md:mb-8">
            <h2 class="text-2xl md:text-3xl font-black text-navy tracking-tight">Checkout Pesanan</h2>
            <p class="text-sm text-gray-500 font-bold mt-1">Lengkapi detail lokasi presisi menggunakan peta.</p>
        </div>

        <?php if($pesan_error): ?>
            <div class="bg-red-100 text-red-700 px-4 py-3 rounded-xl mb-6 text-sm font-bold flex items-center gap-3">
                <i class="fas fa-exclamation-circle"></i> <?= $pesan_error ?>
            </div>
        <?php endif; ?>

        <form action="" method="POST" id="formCheckout" class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
            
            <div class="lg:col-span-7 space-y-6">
                
                <!-- MAPS SECTION -->
                <div class="bg-white rounded-[2rem] p-6 md:p-8 shadow-sm border border-gray-100">
                    <h3 class="text-lg font-black text-navy mb-4 border-l-4 border-orange pl-3 flex items-center gap-2">
                        <i class="fas fa-map-marked-alt text-orange"></i> Lokasi Pengerjaan Presisi
                    </h3>
                    <p class="text-xs text-gray-500 mb-3 font-medium">Geser Pin Biru ke lokasi tepat Anda. Alamat akan otomatis terisi!</p>
                    <div id="map"></div>
                    <textarea name="alamat" id="alamat_tampil" required rows="3" placeholder="Detail alamat rumah otomatis terisi..." class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:border-orange font-medium text-xs text-navy resize-none mb-3"></textarea>
                    <input type="hidden" name="latitude" id="lat">
                    <input type="hidden" name="longitude" id="lng">
                </div>

                <div class="bg-white rounded-[2rem] p-6 md:p-8 shadow-sm border border-gray-100">
                    <h3 class="text-lg font-black text-navy mb-4 border-l-4 border-orange pl-3">Jadwal & Metode</h3>
                    
                    <div class="grid grid-cols-2 gap-4 mb-6">
                        <div>
                            <label class="block text-[10px] font-black text-navy uppercase tracking-widest mb-1">Tanggal</label>
                            <input type="date" name="tanggal" required min="<?= date('Y-m-d') ?>" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:border-orange font-bold text-sm text-navy">
                        </div>
                        <div>
                            <label class="block text-[10px] font-black text-navy uppercase tracking-widest mb-1">Waktu</label>
                            <input type="time" name="waktu" required class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:border-orange font-bold text-sm text-navy">
                        </div>
                    </div>

                    <div class="space-y-3">
                        <div id="card_cod" class="cursor-pointer p-4 border-2 rounded-xl transition-all flex items-center justify-between border-gray-100" onclick="pilihBayar('cod', 'Cash on Delivery (COD)')">
                            <div class="flex items-center gap-4 pointer-events-none">
                                <div class="w-10 h-10 bg-green-100 text-green-600 rounded-lg flex items-center justify-center text-xl"><i class="fas fa-handshake"></i></div>
                                <div><h4 class="font-black text-navy text-sm">Cash on Delivery (COD)</h4></div>
                            </div>
                            <div id="radio_cod" class="w-5 h-5 rounded-full border-2 border-gray-300 transition-all pointer-events-none"></div>
                        </div>

                        <div id="card_online" class="cursor-pointer p-4 border-2 rounded-xl transition-all flex items-center justify-between border-orange bg-orange/5 ring-4 ring-orange/20" onclick="pilihBayar('online', 'Pembayaran Online')">
                            <div class="flex items-center gap-4 pointer-events-none">
                                <div class="w-10 h-10 bg-blue-100 text-blue-600 rounded-lg flex items-center justify-center text-xl"><i class="fas fa-credit-card"></i></div>
                                <div><h4 class="font-black text-navy text-sm">Pembayaran Online (Real)</h4></div>
                            </div>
                            <div id="radio_online" class="w-5 h-5 rounded-full border-[6px] border-orange transition-all pointer-events-none"></div>
                        </div>
                        <input type="hidden" name="metode_pembayaran" id="metode_input" value="Pembayaran Online">
                    </div>
                </div>
            </div>

            <!-- SISI KANAN -->
            <div class="lg:col-span-5 sticky top-28">
                <div class="bg-white rounded-[2rem] p-6 md:p-8 shadow-xl shadow-navy/5 border border-gray-100">
                    <h3 class="text-lg font-black text-navy mb-4 border-b border-gray-100 pb-4">Ringkasan Pesanan</h3>
                    
                    <!-- FITUR BARU: FORM VOUCHER -->
                    <div class="mb-5">
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Punya Voucher Code?</label>
                        <div class="flex gap-2">
                            <input type="text" name="kode_voucher" id="input_voucher" placeholder="Misal: ELOKHEMAT" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:border-orange font-black text-sm text-navy uppercase tracking-widest placeholder-gray-300">
                            <button type="button" onclick="cekVoucher()" class="bg-navy hover:bg-orange text-white px-5 rounded-xl font-black text-xs uppercase tracking-widest transition-colors shadow-md">Apply</button>
                        </div>
                        <p id="msg_voucher" class="text-[10px] font-black mt-2 hidden"></p>
                    </div>

                    <div class="space-y-3 mb-6 pt-4 border-t border-gray-100">
                        <div class="flex justify-between items-center text-sm font-medium text-gray-500">
                            <span>Subtotal Layanan</span>
                            <span>Rp <?= number_format($total_belanja, 0, ',', '.') ?></span>
                        </div>
                        <div class="flex justify-between items-center text-sm font-medium text-gray-500">
                            <span>Biaya Aplikasi</span>
                            <span>Rp <?= number_format($biaya_aplikasi, 0, ',', '.') ?></span>
                        </div>
                        <div id="row_diskon" class="flex justify-between items-center text-sm font-black text-green-500 hidden">
                            <span>Discount Voucher</span>
                            <span id="tampil_diskon">- Rp 0</span>
                        </div>
                    </div>

                    <div class="border-t border-gray-100 pt-5 mb-6 flex justify-between items-end">
                        <span class="text-sm font-black text-navy">Total Akhir</span>
                        <span id="tampil_total" class="text-2xl font-black text-orange">Rp <?= number_format($total_akhir, 0, ',', '.') ?></span>
                    </div>

                    <button type="submit" name="buat_pesanan" class="w-full bg-navy hover:bg-orange text-white py-4 rounded-xl font-black text-sm uppercase tracking-widest shadow-lg transition-all transform hover:-translate-y-1">
                        Buat Orders Now
                    </button>
                </div>
            </div>
            
        </form>
    </div>
</div>

<script>
    // ==========================================
    // LOGIKA VOUCHER AJAX (LANGSUNG HITUNG DI TEMPAT!)
    // ==========================================
    let baseTotal = <?= $total_akhir ?>;
    
function cekVoucher() {
        let kode = document.getElementById('input_voucher').value.trim();
        let msgBox = document.getElementById('msg_voucher');
        let diskonRow = document.getElementById('row_diskon');
        let diskonTampil = document.getElementById('tampil_diskon');
        let totalTampil = document.getElementById('tampil_total');

        // JIKA KOLOM DIKOSONGKAN -> BATALKAN VOUCHER
        if(kode === '') {
            msgBox.textContent = "Voucher dibatalkan.";
            msgBox.className = "text-[10px] font-black mt-2 text-gray-500 block";
            
            diskonRow.classList.add('hidden'); // Sembunyikan baris diskon
            totalTampil.textContent = "Rp " + baseTotal.toLocaleString('id-ID'); // Kembalikan harga normal
            return;
        }

        msgBox.textContent = "Sedang mengecek kode...";
        msgBox.className = "text-[10px] font-black mt-2 text-gray-400 block animate-pulse";

        // Panggil endpoint pengecekan (file ini sendiri)
        fetch('checkout.php?action=cek_voucher&kode=' + encodeURIComponent(kode) + '&total=<?= $total_belanja ?>')
            .then(response => response.json())
            .then(data => {
                msgBox.classList.remove('animate-pulse');
                if(data.status === 'ok') {
                    // Update UI Discount
                    msgBox.textContent = data.pesan;
                    msgBox.className = "text-[10px] font-black mt-2 text-green-500 block";
                    
                    diskonRow.classList.remove('hidden');
                    diskonTampil.textContent = "- Rp " + data.diskon.toLocaleString('id-ID');
                    
                    let newTotal = baseTotal - data.diskon;
                    totalTampil.textContent = "Rp " + newTotal.toLocaleString('id-ID');
                } else {
                    // Tampilkan Error, Reset Total
                    msgBox.textContent = data.pesan;
                    msgBox.className = "text-[10px] font-black mt-2 text-red-500 block";
                    
                    diskonRow.classList.add('hidden');
                    totalTampil.textContent = "Rp " + baseTotal.toLocaleString('id-ID');
                }
            })
            .catch(error => {
                msgBox.textContent = "Terjadi kesalahan jaringan.";
                msgBox.className = "text-[10px] font-black mt-2 text-red-500 block";
            });
    }

    // Logika Pemilihan Pembayaran (Visual)
    function pilihBayar(id, value) {
        document.getElementById('card_cod').className = "cursor-pointer p-4 border-2 rounded-xl transition-all flex items-center justify-between border-gray-100";
        document.getElementById('card_online').className = "cursor-pointer p-4 border-2 rounded-xl transition-all flex items-center justify-between border-gray-100";
        document.getElementById('radio_cod').className = "w-5 h-5 rounded-full border-2 border-gray-300 transition-all pointer-events-none";
        document.getElementById('radio_online').className = "w-5 h-5 rounded-full border-2 border-gray-300 transition-all pointer-events-none";

        document.getElementById('card_' + id).classList.add('border-orange', 'bg-orange/5', 'ring-4', 'ring-orange/20');
        document.getElementById('radio_' + id).classList.replace('border-2', 'border-[6px]');
        document.getElementById('radio_' + id).classList.replace('border-gray-300', 'border-orange');
        document.getElementById('metode_input').value = value;
    }

    // ==========================================
    // LOGIKA PETA LEAFLET & REVERSE GEOCODING
    // ==========================================
    var defaultLat = -3.7928; // Default Location
    var defaultLng = 102.2608;

    var map = L.map('map').setView([defaultLat, defaultLng], 13);
    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap'
    }).addTo(map);

    var marker = L.marker([defaultLat, defaultLng], { draggable: true }).addTo(map);

    function updateCoordinates(lat, lng) {
        document.getElementById('lat').value = lat;
        document.getElementById('lng').value = lng;
    }
    updateCoordinates(defaultLat, defaultLng);

    function fetchAddress(lat, lng) {
        var textarea = document.getElementById('alamat_tampil');
        textarea.value = "Sedang melacak lokasi..."; 
        
        fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}`)
            .then(response => response.json())
            .then(data => {
                if(data && data.display_name) textarea.value = data.display_name;
                else textarea.value = "Alamat tidak ditemukan. Silakan ketik manual detailnya.";
            })
            .catch(err => {
                textarea.value = "Failed mengambil alamat otomatis. Silakan ketik manual.";
            });
    }

    fetchAddress(defaultLat, defaultLng);

    marker.on('dragend', function (e) {
        var position = marker.getLatLng();
        updateCoordinates(position.lat, position.lng);
        map.panTo(position);
        fetchAddress(position.lat, position.lng);
    });
</script>

<?php require_once '../includes/footer.php'; ?>