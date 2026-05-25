<?php
/**
 * config/dbconnect.php
 * ──────────────────────────────────────────────
 * Koneksi PDO ke MySQL/MariaDB
 * Sertakan file ini di setiap PHP yang butuh DB:
 *   require_once __DIR__ . '/config/dbconnect.php';
 * ──────────────────────────────────────────────
 */

define('DB_HOST', 'localhost');
define('DB_NAME', 'akademik_db');   
define('DB_USER', 'root');           
define('DB_PASS', '');               
define('DB_CHARSET', 'utf8mb4');

$dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $db = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {x
    throw new PDOException('Koneksi database gagal.', (int) $e->getCode());
}