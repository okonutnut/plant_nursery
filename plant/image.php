<?php
// Start output buffering to prevent any accidental output
ob_start();
require_once '../config/database.php';

if (!isset($_GET['id'])) {
    // Return placeholder if no ID
    header("Content-Type: image/svg+xml");
    echo '<?xml version="1.0"?><svg width="200" height="200" xmlns="http://www.w3.org/2000/svg"><rect width="200" height="200" fill="#e0e0e0"/><text x="50%" y="50%" text-anchor="middle" dy=".3em" fill="#999" font-family="Arial" font-size="14">No Image</text></svg>';
    exit;
}

$id = (int)$_GET['id'];

// Use prepared statement for proper BLOB handling
$stmt = mysqli_prepare($conn, "SELECT PlantImg FROM plant WHERE PlantID = ?");
if (!$stmt) {
    header("Content-Type: image/svg+xml");
    echo '<?xml version="1.0"?><svg width="200" height="200" xmlns="http://www.w3.org/2000/svg"><rect width="200" height="200" fill="#e0e0e0"/><text x="50%" y="50%" text-anchor="middle" dy=".3em" fill="#999" font-family="Arial" font-size="14">No Image</text></svg>';
    exit;
}

mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);

// Bind result variable for BLOB
mysqli_stmt_bind_result($stmt, $imageData);
$fetched = mysqli_stmt_fetch($stmt);
mysqli_stmt_close($stmt);

if ($fetched && $imageData !== null && strlen($imageData) > 0) {
    // Clear any output that might have been buffered
    ob_clean();
    
    // Try to detect image type from the binary data
    $imageInfo = @getimagesizefromstring($imageData);
    
    if ($imageInfo !== false && isset($imageInfo['mime'])) {
        $mimeType = $imageInfo['mime'];
        header("Content-Type: " . $mimeType);
        header("Content-Length: " . strlen($imageData));
        header("Cache-Control: public, max-age=3600");
        echo $imageData;
        exit;
    } else {
        // Try to detect by file signature if getimagesizefromstring fails
        $mimeType = 'image/jpeg'; // default
        
        // Check for common image signatures
        if (substr($imageData, 0, 2) === "\xFF\xD8") {
            $mimeType = 'image/jpeg';
        } elseif (substr($imageData, 0, 8) === "\x89\x50\x4E\x47\x0D\x0A\x1A\x0A") {
            $mimeType = 'image/png';
        } elseif (substr($imageData, 0, 6) === "GIF87a" || substr($imageData, 0, 6) === "GIF89a") {
            $mimeType = 'image/gif';
        } elseif (substr($imageData, 0, 4) === "RIFF" && substr($imageData, 8, 4) === "WEBP") {
            $mimeType = 'image/webp';
        }
        
        header("Content-Type: " . $mimeType);
        header("Content-Length: " . strlen($imageData));
        header("Cache-Control: public, max-age=3600");
        echo $imageData;
        exit;
    }
} else {
    // Clear any output that might have been buffered
    ob_clean();
    // No image data or empty BLOB
    header("Content-Type: image/svg+xml");
    echo '<?xml version="1.0"?><svg width="200" height="200" xmlns="http://www.w3.org/2000/svg"><rect width="200" height="200" fill="#e0e0e0"/><text x="50%" y="50%" text-anchor="middle" dy=".3em" fill="#999" font-family="Arial" font-size="14">No Image</text></svg>';
}
exit;
