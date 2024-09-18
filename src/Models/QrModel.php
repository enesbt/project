<?php
require_once 'Database.php';
require '../vendor/autoload.php'; 

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use chillerlan\QRCode\Output\QRGdImagePNG;
use chillerlan\QRCode\Data\QRMatrix;
use chillerlan\QRCode\Common\EccLevel;
use chillerlan\QRCode\Output\QRCodeOutputException;

use chillerlan\QRCode\Output\QRMarkupSVG;

error_reporting(E_ALL);
ini_set('display_errors', 1);


class QRImageWithLogo extends QRGdImagePNG{

    public function dump(string|null $file = null, string|null $logo = null): string {
        $this->options->returnResource = true;
        if ($logo !== null) {
            if (!is_file($logo) || !is_readable($logo)) {
                throw new QRCodeOutputException('Invalid logo');
            }
        }
        parent::dump($file);
        if ($logo !== null) {
            $im = imagecreatefrompng($logo);
            if ($im === false) {
                throw new QRCodeOutputException('imagecreatefrompng() error');
            }
            $w = imagesx($im);
            $h = imagesy($im);
            $lw = ($this->options->logoSpaceWidth - 2) * $this->options->scale;
            $lh = ($this->options->logoSpaceHeight - 2) * $this->options->scale;
            $ql = $this->matrix->getSize() * $this->options->scale;
            imagecopyresampled($this->image, $im, ($ql - $lw) / 2, ($ql - $lh) / 2, 0, 0, $lw, $lh, $w, $h);
            imagedestroy($im);
        }
        $imageData = $this->dumpImage();
        $this->saveToFile($imageData, $file);
        if ($this->options->outputBase64) {
            $imageData = $this->toBase64DataURI($imageData);
        }
        return $imageData;
    }


}   
class QrModel{
    private $db;
    public function __construct() {
        $this->db = (new Database())->getConnection();
        if ($this->db->connect_error) {
            echo "Connection failed: " . $this->db->connect_error;
        }
    }
    public function __destruct() {
        if ($this->db) {
            $this->db->close();
        }
    }
    private function findByField($field, $value){
        $sql = "SELECT * FROM qr_records WHERE $field = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('s', $value);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    public function getLinkByUniqueCode($uniqueCode) {
        $stmt = $this->db->prepare('SELECT link FROM qr_records WHERE unique_code = ?');
        $stmt->bind_param('s', $uniqueCode);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            return $row['link'];
        }
        return false;
    }
  
    public function getQRCodeBase64($uniqueCode,$format) {
        if ($format == 'png') {
            $sql = "SELECT qr_code_png FROM qr_records WHERE unique_code = ?";
        } else {
            $sql = "SELECT qr_code_svg FROM qr_records WHERE unique_code = ?";
        }
        $stmt = $this->db->prepare($sql);
        if ($stmt === false) {
            throw new Exception('Sorgu hazırlama hatası: ' . $this->db->error);
        }
        $stmt->bind_param('s', $uniqueCode);
        if (!$stmt->execute()) {
            throw new Exception('Sorgu çalıştırma hatası: ' . $stmt->error);
        }
        $stmt->bind_result($base64Image);
        if ($stmt->fetch()) {
            return $base64Image;
        }
        return null;
    }

    public function generateUniqueCode($numberDigits=5){ 
        $candidateCode = self::generateCustomString($numberDigits);
        while (true) {
            $candidateCode = self::generateCustomString($numberDigits);
            if (!$this->findByField('unique_code',$candidateCode)) {
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

    // public function createQRCodeWithLogo($qrlink, $qrImagePath, $logoPath=null){
    //     $options = new QROptions;
    //     $options->version = 6;
    //     $options->outputBase64 = false;
    //     $options->scale = 50;
    //     $options->imageTransparent = false;
    //     $options->drawCircularModules = true;
    //     $options->circleRadius = 0.45;
    //     $options->keepAsSquare = [
    //         QRMatrix::M_FINDER,
    //         QRMatrix::M_FINDER_DOT,
    //     ];
    //     $options->eccLevel = EccLevel::H;
    //     $options->addLogoSpace = $logoPath !== null;
    //     $options->logoSpaceWidth = 13;
    //     $options->logoSpaceHeight = 13;
    //     $qrCode = new QRCode($options);
    //     $qrCode->addByteSegment($qrlink);
    //     $qrOutputInterface = new QRImageWithLogo($options, $qrCode->getQRMatrix());

    //     if ($logoPath && file_exists($logoPath)) {
    //         $qrOutputInterface->dump($qrImagePath, $logoPath);
    //     } else {
    //         $qrOutputInterface->dump($qrImagePath);
    //     }
    //     return $qrImagePath;
    // }
    public function createQRCodeSvg($qrlink)
    {
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
        $options->drawCircularModules = false;
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
    public function getTotalRecords($search = null) {
        $sql = "SELECT COUNT(*) as total FROM qr_records WHERE deleted_at IS NULL";
        if ($search) {
            $sql .= " AND link LIKE ?";
        }
        $stmt = $this->db->prepare($sql);
        if ($search) {
            $searchTerm = "%$search%";
            $stmt->bind_param('s', $searchTerm);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        return $row['total'];
    }
    public function getRecords($start, $limit, $search = null) {
        $sql = "SELECT * FROM qr_records WHERE deleted_at IS NULL";
        if ($search) {
            $sql .= " AND link LIKE ?";
        }
        $sql .= " LIMIT ?, ?";
        $stmt = $this->db->prepare($sql);
        if ($search) {
            $searchTerm = "%$search%";
            $stmt->bind_param('ssi', $searchTerm, $start, $limit);
        } else {
            $stmt->bind_param('ii', $start, $limit);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function updateQRCode($uniqueCode, $description, $link){
        $sql = "UPDATE qr_records 
                SET description = ?, link = ? 
                WHERE unique_code = ?";
        $stmt = $this->db->prepare($sql);
        if ($stmt) {
            $stmt->bind_param('sss', $description, $link, $uniqueCode);
            return $stmt->execute();
        } else {
            echo "Prepare failed: " . $this->db->error;
            return false;
        }
    }

    public function deleteQRCode($uniqueCode){
        $sql = "UPDATE qr_records SET deleted_at = NOW() WHERE unique_code = ?";
        $stmt = $this->db->prepare($sql);

        if ($stmt) {
            $stmt->bind_param('s', $uniqueCode);
            return $stmt->execute();
        } else {
            echo "Prepare failed: " . $this->db->error;
            return false;
        }
    }
    public function getQRCodeByUniqueCode($uniqueCode){
        $sql = "SELECT * FROM qr_records WHERE unique_code = ? AND deleted_at IS NULL";
        $stmt = $this->db->prepare($sql);

        if ($stmt) {
            $stmt->bind_param('s', $uniqueCode);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                return $result->fetch_assoc();
            } else {
                return null;
            }
        } else {
            echo "Prepare failed: " . $this->db->error;
            return null;
        }
    }
    public function saveQrCode($uniqueCode, $qrPng,$qrSvg, $description, $link, $userName){
        $createdAt = date('Y-m-d');
        $sql = "INSERT INTO qr_records (created_at, unique_code, qr_code_png,qr_code_svg, description, link, user_name) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        if ($stmt) {
            $stmt->bind_param('sssssss', $createdAt, $uniqueCode, $qrPng,$qrSvg, $description, $link, $userName);
            
            return $stmt->execute();
        } else {
            echo "Prepare failed: " . $this->db->error;
            return false;
        }
    }
   
}
