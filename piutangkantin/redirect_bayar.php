<?php
if (isset($_GET['id'])) {
    header('Location: bayar_piutang.php?id=' . $_GET['id'] . '&type=piutang');
    exit;
} else {
    echo "ID tidak ditemukan";
}
?>