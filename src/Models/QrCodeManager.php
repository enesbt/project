<?php
require '../vendor/autoload.php'; 
require_once 'Database.php';
require_once 'Qr.php';
class QrCodeManager{
    private $db;
    public function __construct(){
        $this->db = (new Database())->getConnection();
        if ($this->db->connect_error) {
            echo "Connection failed: " . $this->db->connect_error;
        }   
    }
    public function saveQrCode($unique_code, $qr_code_png, $qr_code_svg, $description, $link, $user_name){
        $createdAt = date('Y-m-d');
        $sql = "INSERT INTO qr_records (created_at, unique_code, qr_code_png, qr_code_svg, description, link, user_name) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        if ($stmt) {
            $stmt->bind_param('sssssss', $createdAt, $unique_code, $qr_code_png,$qr_code_svg, $description, $link, $user_name);
            return $stmt->execute();
        } else {
            echo "Prepare failed: " . $this->db->error;
            return false;
        }
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
    public function findByField($field, $value){
        $sql = "SELECT * FROM qr_records WHERE $field = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('s', $value);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }
}