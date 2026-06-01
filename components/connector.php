<?php
ob_start();
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function check_csrf() {
    $token = $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'], $token)) {
        die("CSRF token validation failed.");
    }
}


$is_local = ($_SERVER['HTTP_HOST'] === 'localhost' || $_SERVER['HTTP_HOST'] === '127.0.0.1');

if ($is_local) {
    define('DB_SERVER', 'localhost');
    define('DB_USERNAME', 'root');
    define('DB_PASSWORD', '');
    define('DB_NAME', 'programko_db');
} else {
    
    define('DB_SERVER', 'localhost');
    define('DB_USERNAME', 'machkrystof');
    define('DB_PASSWORD', 'streda6.zari');
    define('DB_NAME', 'machkrystof');
}


$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD);


if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}


$is_setup_script = (basename($_SERVER['SCRIPT_NAME']) === 'setup.php');

// Try selecting the DB, but only error out if we are not in setup.php
$db_selected = $conn->select_db(DB_NAME);
if (!$db_selected && !$is_setup_script) {
    die("Database selection failed. Please run setup.php first. Error: " . $conn->error);
}

// Auto-migration for guest columns and access_hash (safe check)
if ($db_selected && !$is_setup_script) {
    $check_table = $conn->query("SHOW TABLES LIKE 'bookings'");
    if ($check_table && $check_table->num_rows > 0) {
        $check_guest = $conn->query("SHOW COLUMNS FROM `bookings` LIKE 'guest_name'");
        if ($check_guest && $check_guest->num_rows == 0) {
            $conn->query("ALTER TABLE `bookings` ADD COLUMN guest_name VARCHAR(100) AFTER description");
            $conn->query("ALTER TABLE `bookings` ADD COLUMN guest_email VARCHAR(100) AFTER guest_name");
            $conn->query("ALTER TABLE `bookings` ADD COLUMN guest_phone VARCHAR(50) AFTER guest_email");
        }
        $check_hash = $conn->query("SHOW COLUMNS FROM `bookings` LIKE 'access_hash'");
        if ($check_hash && $check_hash->num_rows == 0) {
            $conn->query("ALTER TABLE `bookings` ADD COLUMN access_hash VARCHAR(64) UNIQUE AFTER completed_photos_json");
        }
        
        $check_blocks = $conn->query("SHOW TABLES LIKE 'profile_blocks'");
        if ($check_blocks && $check_blocks->num_rows > 0) {
            $conn->query("ALTER TABLE `profile_blocks` MODIFY COLUMN content LONGTEXT");
        }
    }
}

$conn->set_charset("utf8mb4");


$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'];
$script_name = $_SERVER['SCRIPT_NAME'];
$dir = dirname($script_name);
if ($dir === '/' || $dir === '\\') $dir = '';
$base_url = $protocol . $host . $dir;
define('BASE_URL', rtrim($base_url, '/\\'));


/**
 * Calculates real disk usage of a directory recursively
 */
function getDirSize($dir) {
    $size = 0;
    if (!is_dir($dir)) return 0;
    $files = scandir($dir);
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;
        $path = $dir . DIRECTORY_SEPARATOR . $file;
        if (is_dir($path)) {
            $size += getDirSize($path);
        } else {
            $size += filesize($path);
        }
    }
    return $size;
}

/**
 * Returns formatted storage usage info for a specific user
 */
function getUserStorageInfo($user_id, $conn) {
    $stmt = $conn->prepare("SELECT storage_limit FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user_info = $stmt->get_result()->fetch_assoc();
    $storage_limit = $user_info['storage_limit'] ?? 52428800; // Default 50MB
    
    // Use absolute path for storage calculation
    $cloud_dir = __DIR__ . "/../cloud/" . $user_id . "/";
    $used_bytes = getDirSize($cloud_dir);
    
    // Calculate percentage
    $percent = ($storage_limit > 0) ? round(($used_bytes / $storage_limit) * 100, 1) : 0;
    
    return [
        'limit_bytes' => $storage_limit,
        'used_bytes' => $used_bytes,
        'percent' => min(100, $percent),
        'limit_mb' => round($storage_limit / (1024 * 1024), 0),
        'used_mb' => round($used_bytes / (1024 * 1024), 2)
    ];
}
?>
