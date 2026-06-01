<?php
require_once 'components/connector.php';

echo "<h2>System Diagnostic</h2>";

// 1. Check PHP Version
echo "PHP Version: " . phpversion() . "<br>";

// 2. Check Directories and Permissions
$dirs = ['cloud', 'posts', 'uploads', 'uploads/avatars'];
foreach ($dirs as $dir) {
    $full_path = __DIR__ . '/' . $dir;
    echo "Directory: <strong>$dir</strong><br>";
    if (file_exists($full_path)) {
        echo "- Status: Exists<br>";
        echo "- Permissions: " . substr(sprintf('%o', fileperms($full_path)), -4) . "<br>";
        echo "- Writable: " . (is_writable($full_path) ? "✅ Yes" : "❌ No") . "<br>";
    } else {
        echo "- Status: ❌ Missing (Attempting to create...)<br>";
        if (mkdir($full_path, 0777, true)) {
            echo "- Creation: ✅ Success<br>";
        } else {
            echo "- Creation: ❌ Failed<br>";
        }
    }
    echo "<hr>";
}

// 3. Check Session
session_start();
echo "Session User ID: " . ($_SESSION['user_id'] ?? 'Not Logged In') . "<br>";

// 4. Check Database
if ($conn->ping()) {
    echo "Database: ✅ Connected<br>";
    $res = $conn->query("SELECT COUNT(*) as count FROM cloud_files");
    $count = $res->fetch_assoc()['count'];
    echo "Total files in DB: $count<br>";
} else {
    echo "Database: ❌ Disconnected<br>";
}
?>
