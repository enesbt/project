<?php
require_once '../src/Models/QrModel.php';

$uniqueCode = isset($_GET['code']) ? $_GET['code'] : null;

if ($uniqueCode) {
    $qrModel = new QrModel();
    $link = $qrModel->getLinkByUniqueCode($uniqueCode);
    if ($link) {
        header("Location: $link");
        exit();
    } else {
        echo "Geçersiz QR kodu.";
    }
} else {
    echo "Kod belirtilmedi.";
}
unset($qrModel);
?>