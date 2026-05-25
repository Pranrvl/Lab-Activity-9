<?php


header('Content-Type: application/xml; charset=UTF-8');

//  Fungsi helper
function buildXML(string $status, string $nik, string $nama, array $urls, string $pesan = ''): string {
    $xml  = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
    $xml .= '<response>' . PHP_EOL;
    $xml .= '  <status>' . htmlspecialchars($status, ENT_XML1, 'UTF-8') . '</status>' . PHP_EOL;
    $xml .= '  <nik>'    . htmlspecialchars($nik,    ENT_XML1, 'UTF-8') . '</nik>'    . PHP_EOL;
    $xml .= '  <nama>'   . htmlspecialchars($nama,   ENT_XML1, 'UTF-8') . '</nama>'   . PHP_EOL;

    if ($pesan) {
        $xml .= '  <pesan>' . htmlspecialchars($pesan, ENT_XML1, 'UTF-8') . '</pesan>' . PHP_EOL;
    }

    $xml .= '  <fotos>' . PHP_EOL;
    foreach ($urls as $url) {
        $xml .= '    <url>' . htmlspecialchars($url, ENT_XML1, 'UTF-8') . '</url>' . PHP_EOL;
    }
    $xml .= '  </fotos>' . PHP_EOL;
    $xml .= '</response>';
    return $xml;
}

// Hanya terima metode POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo buildXML('GAGAL', '', '', [], 'Method tidak diizinkan. Gunakan POST.');
    exit;
}

//Ambil & sanitasi input
$nik  = trim($_POST['nik']  ?? '');
$nama = trim($_POST['nama'] ?? '');

// Validasi input dasar
if (empty($nik) || empty($nama)) {
    echo buildXML('GAGAL', $nik, $nama, [], 'NIK dan Nama wajib diisi.');
    exit;
}
if (strlen($nik) > 20) {
    echo buildXML('GAGAL', $nik, $nama, [], 'NIK maksimal 20 karakter.');
    exit;
}

// Validasi foto
if (empty($_FILES['foto']) || empty($_FILES['foto']['name'][0])) {
    echo buildXML('GAGAL', $nik, $nama, [], 'Minimal satu foto JPG wajib diunggah.');
    exit;
}

// Konfigurasi uploa
$uploadDir    = __DIR__ . '/uploads/';
$uploadUrlBase = 'server/uploads/';   
$allowedMimes  = ['image/jpeg', 'image/jpg'];
$maxSizeBytes  = 5 * 1024 * 1024; 

// Pastikan folder uploads ada
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0775, true);
}

// Koneksi Database 
try {
    require_once __DIR__ . '/../config/dbconnect.php';
    // $db = new PDO(...) sudah tersedia dari dbconnect.php
} catch (Exception $e) {
    echo buildXML('GAGAL', $nik, $nama, [], 'Koneksi DB gagal: ' . $e->getMessage());
    exit;
}

// Mulai transaksi DB
try {
    $db->beginTransaction();

    $stmtCek = $db->prepare("SELECT id FROM mahasiswa WHERE nik = :nik LIMIT 1");
    $stmtCek->execute([':nik' => $nik]);
    if ($stmtCek->fetch()) {
        $db->rollBack();
        echo buildXML('GAGAL', $nik, $nama, [], "NIK '$nik' sudah terdaftar.");
        exit;
    }

    $stmtMhs = $db->prepare(
        "INSERT INTO mahasiswa (nik, nama) VALUES (:nik, :nama)"
    );
    $stmtMhs->execute([':nik' => $nik, ':nama' => $nama]);
    $mahasiswaId = $db->lastInsertId();

    $savedUrls = [];
    $files     = $_FILES['foto'];
    $count     = count($files['name']);

    for ($i = 0; $i < $count; $i++) {
        if ($files['error'][$i] !== UPLOAD_ERR_OK) continue;

        $finfo    = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $files['tmp_name'][$i]);
        finfo_close($finfo);

        if (!in_array($mimeType, $allowedMimes)) {
            continue;
        }

        if ($files['size'][$i] > $maxSizeBytes) {
            continue; // Lewati file > 5MB
        }

        $ext         = 'jpg';
        $uniqueName  = time() . '_' . uniqid() . '_' . preg_replace('/[^a-zA-Z0-9_\-]/', '_', pathinfo($files['name'][$i], PATHINFO_FILENAME)) . '.' . $ext;
        $destination = $uploadDir . $uniqueName;

        if (!move_uploaded_file($files['tmp_name'][$i], $destination)) {
            continue; 
        }
        $urlFoto  = $uploadUrlBase . $uniqueName;
        $namaFile = $files['name'][$i];

        $stmtFoto = $db->prepare(
            "INSERT INTO mahasiswa_foto (mahasiswa_id, url_foto, nama_file)
             VALUES (:mid, :url, :nama_file)"
        );
        $stmtFoto->execute([
            ':mid'       => $mahasiswaId,
            ':url'       => $urlFoto,
            ':nama_file' => $namaFile,
        ]);

        $savedUrls[] = $urlFoto;
    }

    if (empty($savedUrls)) {
        $db->rollBack();
        foreach (glob($uploadDir . time() . '_*') as $f) @unlink($f);
        echo buildXML('GAGAL', $nik, $nama, [], 'Tidak ada foto JPG yang valid berhasil diunggah.');
        exit;
    }

    $db->commit();

    echo buildXML('SUKSES', $nik, $nama, $savedUrls);

} catch (PDOException $e) {
    $db->rollBack();
    echo buildXML('GAGAL', $nik, $nama, [], 'Database error: ' . $e->getMessage());
} catch (Exception $e) {
    if ($db->inTransaction()) $db->rollBack();
    echo buildXML('GAGAL', $nik, $nama, [], 'Error: ' . $e->getMessage());
}




<?php
header('Content-Type: application/xml; charset=UTF-8');
require_once __DIR__ . '/../config/dbconnect.php';

$nik = $_POST['nik'];
$nama = $_POST['nama'];
$savedUrls = [];

// Proses Upload File Fisik ke Folder Server
for ($i = 0; $i < count($_FILES['foto']['name']); $i++) {
    $uniqueName = time() . '_' . $_FILES['foto']['name'][$i];
    $destination = __DIR__ . '/uploads/' . $uniqueName;
    
    if (move_uploaded_file($_FILES['foto']['tmp_name'][$i], $destination)) {
        $savedUrls[] = 'server/uploads/' . $uniqueName;
    }
}

// Proses Simpan ke Database
$stmt = $db->prepare("INSERT INTO mahasiswa (nik, nama) VALUES (?, ?)");
$stmt->execute([$nik, $nama]);
$mhsId = $db->lastInsertId();

foreach ($savedUrls as $url) {
    $stmtFoto = $db->prepare("INSERT INTO mahasiswa_foto (mahasiswa_id, url_foto) VALUES (?, ?)");
    $stmtFoto->execute([$mhsId, $url]);
}

echo '<?xml version="1.0" encoding="UTF-8"?>';
echo '<response>';
echo '  <status>SUKSES</status>';
echo '  <nik>' . htmlspecialchars($nik) . '</nik>';
echo '  <nama>' . htmlspecialchars($nama) . '</nama>';
echo '  <fotos>';
foreach ($savedUrls as $url) {
    echo '    <url>' . htmlspecialchars($url) . '</url>';
}
echo '  </fotos>';
echo '</response>';
?>