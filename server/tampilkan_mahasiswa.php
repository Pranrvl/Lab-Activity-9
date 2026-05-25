<?php
require_once __DIR__ . '/../config/dbconnect.php';
header("Content-Type: application/xml");

$xmlData = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
$xmlData .= '<data_mahasiswa>' . PHP_EOL;

try {
    $sql = "SELECT m.nik, m.nama, 
                   (SELECT url_foto FROM mahasiswa_foto f WHERE f.mahasiswa_id = m.id LIMIT 1) as foto 
            FROM mahasiswa m 
            ORDER BY m.created_at DESC";
    $stmt = $db->prepare($sql);
    
    if($stmt->execute()){
        $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach($records as $row) {
            $xmlData .= "<mahasiswa>";
            foreach($row as $key => $val) {
                $xmlData .= "<".$key.">";
                if(!empty($val)) {
                    $xmlData .= htmlspecialchars($val); 
                } else {
                    $xmlData .= "null";
                }
                $xmlData .= "</".$key.">";
            }
            $xmlData .= "</mahasiswa>";
        }
    }
} catch (Exception $e) {
    $xmlData .= '<error>' . htmlspecialchars($e->getMessage()) . '</error>';
}

$xmlData .= '</data_mahasiswa>';
echo $xmlData;
?>

