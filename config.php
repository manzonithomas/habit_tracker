<?php
// --- ERROR HANDLING ---
// Solo gli errori gravi diventano eccezioni fatai.
// Warning e notice vengono loggati ma NON crashano l'app.
set_error_handler(function ($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});
set_exception_handler(function ($exception) {
    // Se è una chiamata API, restituisci JSON. NON toccare error.php per evitare loop infiniti.
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        http_response_code(503);
        header('Content-Type: application.json');
        echo json_encode(['error' => true, 'message' => 'Errore connessione al database']);
        exit;
    }
    // Per le pagine normali, non facciamo niente. L'.htaccess penserà a error.php.
    http_response_code(500);
    exit;
});

// --- CARICAMENTO VARIABILI ---
$env_file = __DIR__ . '/.env';
if (file_exists($env_file)) {
    $lines = file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0)
            continue;
        putenv(trim($line));
    }
}

// Disabilita errori a schermo (gestiti dal nostro handler sopra)
ini_set('display_errors', '0');
error_reporting(E_ALL);

session_start();

// --- CONNESSIONE DB SICURA ---
$host = getenv('DB_HOST') ?: 'localhost';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') ?: '';
$dbname = getenv('DB_NAME') ?: 'habit_tracker';

$conn = @new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    $errorMsg = "DB Error (" . $conn->connect_errno . "): " . $conn->connect_error;

    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        http_response_code(503);
        header('Content-Type: application/json');
        echo json_encode(['error' => true, 'message' => 'Database non raggiungibile']);
        exit;
    }

    include __DIR__ . '/error.php';
    exit;
}

// Imposta cookie sicuri se usi HTTPS (prima di session_start sarebbe ideale, ma funziona comunque)
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
    ini_set('session.cookie_secure', 1);
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_samesite', 'Strict');
}

function checkLogin()
{
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit;
    }
}

function getLunedi()
{
    $d = new DateTime();
    $g = (int) $d->format('N');
    if ($g == 1)
        return $d->format('Y-m-d');
    $d->modify('-' . ($g - 1) . ' days');
    return $d->format('Y-m-d');
}
function getDomenica()
{
    $d = new DateTime();
    $g = (int) $d->format('N');
    if ($g == 7)
        return $d->format('Y-m-d');
    $d->modify('+' . (7 - $g) . ' days');
    return $d->format('Y-m-d');
}

// Crittografia
define('ENCRYPT_KEY', getenv('ENCRYPT_KEY') ?: 'G4m1fyL1f3_S3cr3t_K3y_32_Byt3s!!');

function encryptText($text)
{
    if (!$text)
        return '';
    $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
    $encrypted = openssl_encrypt($text, 'aes-256-cbc', ENCRYPT_KEY, 0, $iv);
    return base64_encode($iv . $encrypted);
}

function decryptText($text)
{
    if (!$text)
        return '';
    $data = base64_decode($text);
    if ($data === false || strlen($data) < 17)
        return '';
    $ivLen = openssl_cipher_iv_length('aes-256-cbc');
    $iv = substr($data, 0, $ivLen);
    $encrypted = substr($data, $ivLen);
    $decrypted = openssl_decrypt($encrypted, 'aes-256-cbc', ENCRYPT_KEY, 0, $iv);
    return $decrypted !== false ? $decrypted : '';
}

define('APP_URL', getenv('APP_URL') ?: 'http://localhost/gamifylife');