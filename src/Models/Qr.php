<?php
require_once 'QrCodeManager.php';
require_once 'QRImageWithLogo.php';

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use chillerlan\QRCode\Output\QRGdImagePNG;
use chillerlan\QRCode\Data\QRMatrix;
use chillerlan\QRCode\Common\EccLevel;
use chillerlan\QRCode\Output\QRCodeOutputException;
use chillerlan\QRCode\Output\QRMarkupSVG;

class Qr{
    private $unique_code;
    private $qr_code_png;
    private $qr_code_svg;
    private $description;
    private $link;
    private $user_name;
    private $qr_base_link;
    private $logo_path;

    public function __construct($description, $link, $user_name='',$logo_path=null){
        $this->unique_code = $this->generateUniqueCode();
        $this->qr_base_link = "http://localhost/redirect.php?code=$this->unique_code";
        $this->logo_path = $logo_path;
        $this->qr_code_png = $this->createQRCodeWithLogo($this->qr_base_link, $this->logo_path);
        $this->qr_code_svg = $this->createQRCodeSvg($this->qr_base_link);
        $this->description = $description;
        $this->link = $link;
        $this->user_name = $user_name;
    }
    public function getUniqueCode(){
        return $this->unique_code;
    }
  
    public function saveQrCode(){
        $qrmanager= new QrCodeManager();
        return $qrmanager->saveQrCode($this->unique_code, $this->qr_code_png, $this->qr_code_svg, $this->description, $this->link, $this->user_name);
    }
    public function generateUniqueCode($numberDigits=5){ 
        $candidateCode = self::generateCustomString($numberDigits);
        while (true) {
            $candidateCode = self::generateCustomString($numberDigits);
            if (!(new QrCodeManager())->findByField('unique_code',$candidateCode)) {
                return $candidateCode;
            }
        }
    }    
    public static function generateCustomString($length = 5) {
        $characters = 'ABCDEFGHJKMNPQRSTVWXYZ1234567890abcdefghijklmnopqrstuvwxyz';
        $charactersLength = strlen($characters);
        $randomString = '';

        for ($i = 0; $i < $length; $i++) {
            $randomIndex = mt_rand(0, $charactersLength - 1);
            $randomString .= $characters[$randomIndex];
        }
        return $randomString;
    }
    public function createQRCodeSvg($qrlink){
        $options = new QROptions;
        $options->version              = 5;
        $options->outputBase64         = true;
        $options->svgUseFillAttributes = false;
        $options->drawCircularModules  = false;
        $options->circleRadius         = 0.4;
        $options->connectPaths         = true;
        $options->keepAsSquare         = [
            QRMatrix::M_FINDER_DARK,
            QRMatrix::M_FINDER_DOT,
            QRMatrix::M_ALIGNMENT_DARK,
        ];
        $qrCode = new QRCode($options);
        $qrCode->addByteSegment($qrlink);
        $outputInterface = new QRMarkupSVG($options, $qrCode->getQRMatrix());
        try {
            $base64Image = $outputInterface->dump();
        } catch (Exception $e) {
            echo 'QR kodu oluşturulurken bir hata oluştu: ' . $e->getMessage();
            $base64Image = '';
        }
        return $base64Image;
    }
    public function createQRCodeWithLogo($qrlink, $logoPath = null) {
        $options = new QROptions;
        $options->version = 5;
        $options->outputBase64 = true;
        $options->scale = 50;
        $options->imageTransparent = false;
        $options->drawCircularModules = true;
        $options->circleRadius = 0.5;
        $options->keepAsSquare = [
            QRMatrix::M_FINDER,
            QRMatrix::M_FINDER_DOT,
        ];
        $options->eccLevel = EccLevel::H;
        $options->addLogoSpace = $logoPath !== null;
        $options->logoSpaceWidth = 13;
        $options->logoSpaceHeight = 13;
        $qrCode = new QRCode($options);
        $qrCode->addByteSegment($qrlink);
        $qrOutputInterface = new QRImageWithLogo($options, $qrCode->getQRMatrix());
        try {
            $base64Image = $qrOutputInterface->dump(null, $logoPath);
        } catch (Exception $e) {
            echo 'QR kodu oluşturulurken bir hata oluştu: ' . $e->getMessage();
            $base64Image = '';
        }
        return $base64Image;
    }
    public function __destruct() {
        if ($this->db) {
            $this->db->close();
       }
    }
}