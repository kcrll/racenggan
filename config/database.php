<?php
define('DB_HOST', 'localhost');
define('DB_NAME', 'racel_sash');
define('DB_USER', 'root');
define('DB_PASS', '');

$docRoot = str_replace('\\', '/', (string) realpath($_SERVER['DOCUMENT_ROOT'] ?? ''));
$projDir = str_replace('\\', '/', (string) realpath(__DIR__ . '/..'));
define('BASE_URL', ($docRoot !== '' && strpos($projDir, $docRoot) === 0)
    ? rtrim(substr($projDir, strlen($docRoot)), '/')
    : '');

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }
    try {
        $pdo = new PDO(
            'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]
        );
    } catch (PDOException $ex) {
        http_response_code(500);
        exit('<div style="font-family:sans-serif;max-width:520px;margin:12vh auto;padding:24px;border:1px solid #eadfcc;border-radius:12px">'
            . '<h2 style="margin-bottom:8px">Database connection failed</h2>'
            . '<p>Start MySQL in XAMPP, import <b>database.sql</b> in phpMyAdmin, then check '
            . '<b>config/database.php</b>.</p></div>');
    }
    return $pdo;
}
