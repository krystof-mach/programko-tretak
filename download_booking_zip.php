<?php
session_start();
include 'components/connector.php';

$booking_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$uid = $_SESSION['user_id'] ?? 0;

if (!$uid || !$booking_id) die("Neautorizovaný přístup.");

$stmt = $conn->prepare("SELECT photographer_id, user_id, is_paid, completed_photos_json FROM bookings WHERE id = ?");
$stmt->bind_param("i", $booking_id);
$stmt->execute();
$booking = $stmt->get_result()->fetch_assoc();

if (!$booking) die("Rezervace nenalezena.");

$is_photog = ($booking['photographer_id'] == $uid);
$is_client = ($booking['user_id'] == $uid);

if (!$is_photog && (!$is_client || !$booking['is_paid'])) die("Přístup odepřen.");

$photos = json_decode($booking['completed_photos_json'] ?? '[]', true);
if (empty($photos)) die("Žádné fotky k dispozici.");

// Vytvoříme dočasnou složku v rámci htdocs/cloud/tmp pro lepší kompatibilitu s oprávněními
$tmp_base = 'cloud/tmp_zip_'.time().'/';
if (!is_dir('cloud/tmp_zip/')) @mkdir('cloud/tmp_zip/', 0777, true); // jen pro jistotu
@mkdir($tmp_base, 0777, true);

$files_to_zip = [];
foreach ($photos as $photo) {
    // Rozlišení, zda je to nový formát (pole) nebo starý (string)
    if (is_array($photo) && isset($photo['path'], $photo['name'])) {
        $path = $photo['path'];
        $name = $photo['name'];
    } else {
        $path = $photo;
        $name = basename($photo); // Staré fotky zůstanou pod unikátním ID
    }
    
    // Odstranění problematických znaků z názvu souboru pro ZIP
    $safe_name = preg_replace('/[^\w\-\.]/u', '_', $name);
    
    if (file_exists($path)) {
        $temp_file = $tmp_base . $safe_name;
        copy($path, $temp_file);
        $files_to_zip[] = '"' . $safe_name . '"';
    }
}

if (empty($files_to_zip)) {
    @rmdir($tmp_base);
    die("Soubory fyzicky neexistují na serveru.");
}

$zip_name = "foceni_objednavka_" . $booking_id . ".zip";
$zip_path = 'cloud/tmp_zip/' . $zip_name;

// Sestavení příkazu tar pro Windows
// -C změní pracovní adresář, takže v ZIPu nebudou složky, jen soubory
$cmd = "tar -a -c -f " . escapeshellarg($zip_path) . " -C " . escapeshellarg($tmp_base) . " " . implode(" ", $files_to_zip);
shell_exec($cmd);

// Úklid dočasných souborů
foreach (glob($tmp_base . "*") as $file) { unlink($file); }
rmdir($tmp_base);

if (!file_exists($zip_path)) {
    die("Chyba: ZIP nebyl vytvořen. Kontaktujte správce pro povolení ZipArchive v php.ini.");
}

header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="' . $zip_title = ($zip_name) . '"');
header('Content-Length: ' . filesize($zip_path));
header('Pragma: no-cache');
readfile($zip_path);

unlink($zip_path); // Smazání ZIPu po odeslání
exit();
?>
