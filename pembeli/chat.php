<?php
// File ini cuma bertugas melempar user dari folder pembeli ke folder utama
$toko = isset($_GET['toko_id']) ? '?toko_id='.$_GET['toko_id'] : '';
header("Location: ../chat.php" . $toko);
exit();
?>