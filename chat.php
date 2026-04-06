<?php
// PASTIKAN TIDAK ADA SPASI SEBELUM TAG INI
session_start();
require_once 'config/database.php';

// Wajib Login
if (!isset($_SESSION['user_id'])) {
    if(isset($_GET['action'])) { echo json_encode([]); exit(); } // Cegah error AJAX
    header("Location: auth/login.php");
    exit();
}
$user_id = $_SESSION['user_id'];

// =========================================================
// 1. MESIN AJAX (MENGIRIM & MENGAMBIL PESAN TANPA LOADING)
// =========================================================
if (isset($_GET['action'])) {
    header('Content-Type: application/json');
    
    // A. Mengambil Pesan Real-time
    if ($_GET['action'] == 'load') {
        $lawan_id = (int)$_GET['uid'];
        
        // Tandai pesan sebagai "Sudah Dibaca" (Centang Biru)
        mysqli_query($conn, "UPDATE chat_messages SET is_read = 1 WHERE pengirim_id = '$lawan_id' AND penerima_id = '$user_id'");
        
        $q = mysqli_query($conn, "SELECT * FROM chat_messages 
                                  WHERE (pengirim_id = '$user_id' AND penerima_id = '$lawan_id') 
                                     OR (pengirim_id = '$lawan_id' AND penerima_id = '$user_id') 
                                  ORDER BY tanggal ASC");
        $html = '';
        while($msg = mysqli_fetch_assoc($q)){
            $waktu = date('H:i', strtotime($msg['tanggal']));
            if($msg['pengirim_id'] == $user_id) {
                // Bubble Kanan (Pesan Saya)
                $html .= '<div class="flex justify-end mb-4"><div class="bg-orange text-white rounded-t-2xl rounded-bl-2xl px-5 py-3 max-w-[75%] shadow-md"><p class="text-sm font-medium">'.htmlspecialchars($msg['isi_pesan']).'</p><span class="text-[9px] text-white/70 block mt-1 text-right">'.$waktu.'</span></div></div>';
            } else {
                // Bubble Kiri (Pesan Lawan)
                $html .= '<div class="flex justify-start mb-4"><div class="bg-white border border-gray-100 text-navy rounded-t-2xl rounded-br-2xl px-5 py-3 max-w-[75%] shadow-sm"><p class="text-sm font-medium">'.htmlspecialchars($msg['isi_pesan']).'</p><span class="text-[9px] text-gray-400 block mt-1">'.$waktu.'</span></div></div>';
            }
        }
        echo json_encode(['html' => $html]);
        exit();
    }
    
    // B. Mengirim Pesan Baru
    if ($_GET['action'] == 'send') {
        $lawan_id = (int)$_POST['uid'];
        $pesan = mysqli_real_escape_string($conn, trim($_POST['pesan']));
        
        if (!empty($pesan) && $lawan_id > 0) {
            mysqli_query($conn, "INSERT INTO chat_messages (pengirim_id, penerima_id, isi_pesan) VALUES ('$user_id', '$lawan_id', '$pesan')");
            echo json_encode(['status' => 'ok']);
        } else {
            echo json_encode(['status' => 'error']);
        }
        exit();
    }
}

// =========================================================
// 2. TAMPILAN ANTARMUKA (UI CHATBOX)
// =========================================================
$lawan_id = isset($_GET['uid']) ? (int)$_GET['uid'] : 0;
$nama_lawan = "Pilih Kontak";
$role_lawan = "";

// Jika sedang membuka chat dengan seseorang
if ($lawan_id > 0) {
    $q_lawan = mysqli_query($conn, "SELECT nama_lengkap, role FROM users WHERE id = '$lawan_id'");
    if($l = mysqli_fetch_assoc($q_lawan)){
        $nama_lawan = $l['nama_lengkap'];
        $role_lawan = $l['role'];
    }
}

// Ambil Riwayat Kontak (Orang yang pernah kita chat)
$q_kontak = mysqli_query($conn, "
    SELECT u.id, u.nama_lengkap, u.role, 
           (SELECT isi_pesan FROM chat_messages WHERE (pengirim_id = u.id AND penerima_id = '$user_id') OR (pengirim_id = '$user_id' AND penerima_id = u.id) ORDER BY tanggal DESC LIMIT 1) as last_msg,
           (SELECT COUNT(id) FROM chat_messages WHERE pengirim_id = u.id AND penerima_id = '$user_id' AND is_read = 0) as unread
    FROM users u
    WHERE u.id IN (
        SELECT pengirim_id FROM chat_messages WHERE penerima_id = '$user_id'
        UNION
        SELECT penerima_id FROM chat_messages WHERE pengirim_id = '$user_id'
    )
");

require_once 'includes/header.php';
?>

<div class="bg-bglight min-h-screen pt-4 pb-24 md:pt-8 font-sans">
    <div class="max-w-screen-xl mx-auto px-4 md:px-8 h-[80vh]">
        <div class="bg-white rounded-[2rem] shadow-xl border border-gray-100 flex h-full overflow-hidden">
            
            <!-- PANEL KIRI: DAFTAR KONTAK -->
            <div class="w-full md:w-1/3 border-r border-gray-100 flex flex-col <?= $lawan_id > 0 ? 'hidden md:flex' : 'flex' ?>">
                <div class="p-6 border-b border-gray-100 bg-gray-50 flex items-center justify-between">
                    <h2 class="text-xl font-black text-navy"><i class="far fa-comments text-orange mr-2"></i> Messages</h2>
                </div>
                
                <div class="flex-1 overflow-y-auto p-4 space-y-2">
                    <?php while($k = mysqli_fetch_assoc($q_kontak)): 
                        $aktif = ($k['id'] == $lawan_id) ? 'bg-orange/5 border-orange ring-1 ring-orange/50' : 'bg-white border-gray-100 hover:bg-gray-50';
                    ?>
                    <a href="chat.php?uid=<?= $k['id'] ?>" class="block p-4 border rounded-2xl transition-all <?= $aktif ?>">
                        <div class="flex justify-between items-start">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-gradient-to-tr from-navy to-blue-900 text-white font-bold rounded-full flex items-center justify-center shrink-0 shadow-sm">
                                    <?= strtoupper(substr($k['nama_lengkap'], 0, 1)) ?>
                                </div>
                                <div>
                                    <h4 class="font-bold text-sm text-navy"><?= htmlspecialchars($k['nama_lengkap']) ?></h4>
                                    <p class="text-[9px] uppercase font-black tracking-widest text-orange mb-1"><?= $k['role'] ?></p>
                                    <p class="text-xs truncate max-w-[150px] text-gray-400 font-medium"><?= htmlspecialchars($k['last_msg']) ?></p>
                                </div>
                            </div>
                            <?php if($k['unread'] > 0): ?>
                                <span class="bg-red-500 text-white text-[9px] font-black w-5 h-5 flex items-center justify-center rounded-full shadow-md animate-bounce"><?= $k['unread'] ?></span>
                            <?php endif; ?>
                        </div>
                    </a>
                    <?php endwhile; ?>
                    
                    <?php if(mysqli_num_rows($q_kontak) == 0): ?>
                        <div class="text-center text-gray-400 text-xs py-10 font-bold">
                            <i class="fas fa-inbox text-4xl text-gray-200 mb-3 block"></i>
                            No conversations yet.
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- PANEL KANAN: RUANG CHAT -->
            <div class="w-full md:w-2/3 flex flex-col bg-[#f8f9fa] <?= $lawan_id == 0 ? 'hidden md:flex' : 'flex' ?>">
                <?php if($lawan_id > 0): ?>
                    <!-- Header Chat -->
                    <div class="p-4 md:p-6 bg-white border-b border-gray-100 flex items-center gap-4 shadow-sm z-10">
                        <a href="chat.php" class="md:hidden w-10 h-10 bg-gray-50 hover:bg-gray-100 rounded-full flex items-center justify-center text-gray-600 transition-colors"><i class="fas fa-arrow-left"></i></a>
                        <div class="w-12 h-12 bg-gradient-to-br from-navy to-blue-900 rounded-full flex items-center justify-center text-white font-bold text-lg shadow-inner">
                            <?= strtoupper(substr($nama_lawan, 0, 1)) ?>
                        </div>
                        <div>
                            <h3 class="font-black text-navy text-base md:text-lg"><?= htmlspecialchars($nama_lawan) ?></h3>
                            <p class="text-[10px] uppercase font-black tracking-widest text-orange flex items-center gap-1"><i class="fas fa-circle text-[8px] text-green-500"></i> <?= $role_lawan ?></p>
                        </div>
                    </div>

                    <!-- Area Pesan (Otomatis Scroll) -->
                    <div id="chat-box" class="flex-1 overflow-y-auto p-4 md:p-6 flex flex-col scroll-smooth">
                        <div class="text-center text-gray-400 text-xs py-10 font-bold animate-pulse">Loading messages...</div>
                    </div>

                    <!-- Form Input Pesan -->
                    <div class="p-4 bg-white border-t border-gray-100">
                        <form id="form-chat" class="flex gap-3 items-center">
                            <input type="hidden" id="lawan_id" value="<?= $lawan_id ?>">
                            <div class="flex-1 bg-gray-100 rounded-full flex items-center border border-transparent focus-within:border-orange focus-within:bg-white focus-within:ring-2 focus-within:ring-orange/20 transition-all px-2">
                                <div class="p-3 text-gray-400"><i class="fas fa-keyboard"></i></div>
                                <input type="text" id="isi_pesan" placeholder="Type a message..." class="w-full bg-transparent py-3 pr-4 text-sm font-medium text-navy outline-none" autocomplete="off" required>
                            </div>
                            <button type="submit" class="w-12 h-12 shrink-0 bg-navy hover:bg-orange text-white rounded-full flex items-center justify-center transition-colors shadow-lg hover:shadow-orange/50 hover:-translate-y-0.5 transform">
                                <i class="fas fa-paper-plane"></i>
                            </button>
                        </form>
                    </div>
                <?php else: ?>
                    <div class="flex-1 flex flex-col items-center justify-center text-gray-400 bg-gray-50/50">
                        <div class="w-32 h-32 bg-white rounded-full flex items-center justify-center shadow-sm mb-6 border-4 border-gray-50">
                            <i class="far fa-comments text-6xl text-gray-200"></i>
                        </div>
                        <h3 class="font-black text-xl text-navy mb-2">Message Center</h3>
                        <p class="text-sm font-medium text-gray-400 max-w-xs text-center">Select a contact on the left to start a conversation.</p>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </div>
</div>

<script>
    const chatBox = document.getElementById('chat-box');
    const lawanId = document.getElementById('lawan_id') ? document.getElementById('lawan_id').value : 0;
    let isUserScrolling = false;

    // Deteksi jika user sedang scroll ke atas (biar gak dipaksa scroll ke bawah terus saat baca pesan lama)
    if(chatBox) {
        chatBox.addEventListener('scroll', function() {
            let isAtBottom = chatBox.scrollHeight - chatBox.clientHeight <= chatBox.scrollTop + 50;
            isUserScrolling = !isAtBottom;
        });
    }

    function scrollToBottom() {
        if(chatBox && !isUserScrolling) chatBox.scrollTop = chatBox.scrollHeight;
    }

    function loadMessages() {
        if (lawanId == 0) return;
        fetch('chat.php?action=load&uid=' + lawanId)
            .then(res => res.json())
            .then(data => {
                if (data.html === '') {
                    if(chatBox.innerHTML.includes("Memuat")) {
                        chatBox.innerHTML = '<div class="text-center text-gray-400 text-xs py-10 font-bold bg-white rounded-2xl border border-dashed border-gray-200"><i class="fas fa-hand-sparkles text-2xl text-orange mb-2 block"></i>Send the first message to start chatting! 🚀</div>';
                    }
                } else {
                    // Hanya update HTML jika ada perubahan (optimasi biar tidak kedip)
                    if(chatBox.innerHTML !== data.html) {
                        chatBox.innerHTML = data.html;
                        scrollToBottom();
                    }
                }
            });
    }

    if (lawanId > 0) {
        loadMessages(); // Load langsung saat halaman dibuka
        setInterval(loadMessages, 3000); // REFRESH TIAP 3 DETIK (AJAX POLLING)
        
        // Logika saat tombol kirim ditekan
        document.getElementById('form-chat').addEventListener('submit', function(e) {
            e.preventDefault();
            let pesanInput = document.getElementById('isi_pesan');
            let pesan = pesanInput.value;
            if(pesan.trim() === '') return;
            
            pesanInput.value = ''; // Kosongkan form secepat kilat
            isUserScrolling = false; // Paksa scroll ke bawah setelah kirim
            
            let formData = new URLSearchParams();
            formData.append('uid', lawanId);
            formData.append('pesan', pesan);

            fetch('chat.php?action=send', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: formData.toString()
            })
            .then(res => res.json())
            .then(data => {
                if(data.status === 'ok') {
                    loadMessages(); // Panggil pesan baru secara instan
                }
            });
        });
    }
</script>

<?php require_once 'includes/footer.php'; ?>