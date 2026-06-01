<?php
require_once 'components/connector.php';
session_start();

echo "<h1>☁️ Cloud Upload Diagnostic</h1>";

// 1. Session & Auth Check
$uid = $_SESSION['user_id'] ?? 0;
echo "<h3>1. Session & Auth</h3>";
echo "User ID: " . ($uid ? $uid : "❌ NOT LOGGED IN") . "<br>";
if (!$uid) die("Please login first to run this diagnostic correctly.");

// 2. Server Limits
echo "<h3>2. Server Limits</h3>";
echo "upload_max_filesize: " . ini_get('upload_max_filesize') . "<br>";
echo "post_max_size: " . ini_get('post_max_size') . "<br>";
echo "memory_limit: " . ini_get('memory_limit') . "<br>";

// 3. Path & Permissions Check
echo "<h3>3. Path & Permissions</h3>";
$abs_cloud_root = __DIR__ . "/cloud/";
$abs_user_dir = $abs_cloud_root . $uid . "/";

echo "Cloud Root: <code>$abs_cloud_root</code><br>";
echo "User Dir: <code>$abs_user_dir</code><br>";

if (!file_exists($abs_cloud_root)) {
    echo "❌ Cloud root missing. Attempting to create...<br>";
    mkdir($abs_cloud_root, 0777, true);
}

if (is_writable($abs_cloud_root)) {
    echo "✅ Cloud root is writable.<br>";
} else {
    echo "❌ Cloud root is NOT writable. (Current perms: " . substr(sprintf('%o', fileperms($abs_cloud_root)), -4) . ")<br>";
}

if (!file_exists($abs_user_dir)) {
    echo "Creating user directory...<br>";
    mkdir($abs_user_dir, 0777, true);
}

if (is_writable($abs_user_dir)) {
    echo "✅ User directory is writable.<br>";
} else {
    echo "❌ User directory is NOT writable.<br>";
}

// 4. Real Write Test
echo "<h3>4. Physical Write Test</h3>";
$test_file = $abs_user_dir . "diag_test_" . time() . ".txt";
if (@file_put_contents($test_file, "Test content")) {
    echo "✅ Physical write SUCCESS.<br>";
    unlink($test_file);
    echo "✅ Physical delete SUCCESS.<br>";
} else {
    $err = error_get_last();
    echo "❌ Physical write FAILED: " . ($err['message'] ?? 'Unknown error') . "<br>";
}

// 5. Database Write Test
echo "<h3>5. Database Write Test</h3>";
$stmt = $conn->prepare("INSERT INTO cloud_files (user_id, filename, original_name, file_type, file_size) VALUES (?, 'test.tmp', 'test.tmp', 'text/plain', 0)");
if ($stmt) {
    $stmt->bind_param("i", $uid);
    if ($stmt->execute()) {
        $new_id = $stmt->insert_id;
        echo "✅ DB Insert SUCCESS (ID: $new_id).<br>";
        $conn->query("DELETE FROM cloud_files WHERE id = $new_id");
        echo "✅ DB Cleanup SUCCESS.<br>";
    } else {
        echo "❌ DB Insert FAILED: " . $stmt->error . "<br>";
    }
} else {
    echo "❌ DB Prepare FAILED: " . $conn->error . "<br>";
}

echo "<hr>";
echo "<h4>Instructions for you:</h4>";
echo "1. If everything above is GREEN (✅), the server environment is fine.<br>";
echo "2. If there are RED crosses (❌), tell me which ones.<br>";
echo "3. Try to upload a SMALL file (under 1MB) and see if it works now.";
?>
