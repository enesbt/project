<?php
session_start();
require_once '../src/Models/QrCodeManager.php';
$uniqueCode = isset($_GET['unique_code']) ? $_GET['unique_code'] : null;
if ($uniqueCode) {
    $qrManager = new QrCodeManager();
    $record = $qrManager->getQRCodeByUniqueCode($uniqueCode);
    if (!$record) {
        header('Location: index.php?status=error&message=Geçersiz QR kodu.');
        exit();
    }
    $qrManager->deleteQRCode($uniqueCode);
    header('Location: index.php?status=SUCCESS&message=QR kodu silindi.');
} else {
    header('Location: index.php?status=error&message=QR kodu seçilmedi.');
    exit();
}
if (!$record) {
    header('Location: index.php?status=error&message=Kayıt bulunamadı.');
    exit();
}
unset($qrModel);
?>