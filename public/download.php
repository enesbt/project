<?php
require_once '../src/Models/QrCodeManager.php';
if (isset($_GET['code']) && isset($_GET['format'])) {
    $qrManager = new QrCodeManager();
    $uniqueCode = $_GET['code'];
    $format = $_GET['format'];
    if (!in_array($format, ['png', 'svg'])) {
        echo "Invalid format. Please specify 'png' or 'svg'.";
        exit;
    }
    $base64Image = $qrManager->getQRCodeBase64($uniqueCode, $format);
    unset($qrModel);
    if ($base64Image) {
        if($format=='svg')
            $base64Image = str_replace('data:image/' . $format . '+xml;base64,', '', $base64Image);
        else
            $base64Image = str_replace('data:image/' . $format . ';base64,', '', $base64Image);

        $imageData = base64_decode($base64Image);
        if ($imageData === false) {
            echo "Base64 data could not be decoded.";
            exit;
        }
        header('Content-Type: image/' . $format);
        header('Content-Disposition: attachment; filename="' . $uniqueCode . '.' . $format . '"');
        header('Content-Length: ' . strlen($imageData));
        echo $imageData;
        exit;
    } else
        echo "Image not found.";
} else
    echo "Code or format not specified.";
?>
