<?php
session_start();
require_once '../src/Models/QrModel.php';
$uniqueCode = isset($_GET['unique_code']) ? $_GET['unique_code'] : null;
if ($uniqueCode) {
    $qrModel = new QrModel();
    $record = $qrModel->getQRCodeByUniqueCode($uniqueCode);
    
    if (!$record) {
        header('Location: index.php?status=error&message=Geçersiz QR kodu.');
        exit();
    }
    $qrModel->deleteQRCode($uniqueCode);
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