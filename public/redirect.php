<?php
require_once '../src/Models/QrCodeManager.php';

$uniqueCode = isset($_GET['code']) ? $_GET['code'] : null;
if ($uniqueCode) {
    $qrManager = new QrCodeManager();
    $qrManager->incrementScanCount($uniqueCode);
    $link = $qrManager->getLinkByUniqueCode($uniqueCode);
    if ($link) {
        header("Location: $link");
        exit();
    } else {
        echo "Geçersiz QR kodu.";
    }
} else {
    echo "Kod belirtilmedi.";
}
unset($qrManager);
?>