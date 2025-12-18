<?php
require '../includes/db.php';

// Prevent error output from corrupting the image binary data
ini_set('display_errors', 0);

$type = isset($_GET['type']) ? $_GET['type'] : 'gallery';
$id   = isset($_GET['id']) ? intval($_GET['id']) : 0;

$row = false;

// --- 1. Fetch Data Logic ---

if ($type === 'logo') {
    $stmt = $pdo->prepare("SELECT logo_data as img FROM site_identity WHERE id = 1");
    $stmt->execute();
    $row = $stmt->fetch();

} elseif ($type === 'network') {
    $stmt = $pdo->prepare("SELECT flag_data as img FROM network WHERE id = ?");
    $stmt->execute([$id]);
    $row = $stmt->fetch();

} elseif ($type === 'journey') {
    $stmt = $pdo->prepare("SELECT file_data as img FROM personal_journey WHERE id = ?");
    $stmt->execute([$id]);
    $row = $stmt->fetch();

} elseif ($type === 'content') {
    $key = isset($_GET['key']) ? $_GET['key'] : '';
    $stmt = $pdo->prepare("SELECT content_value as img FROM site_content WHERE content_key = ?");
    $stmt->execute([$key]);
    $row = $stmt->fetch();

} elseif ($type === 'about_slide') {
    $stmt = $pdo->prepare("SELECT image_data as img FROM about_carousel WHERE id = ?");
    $stmt->execute([$id]);
    $row = $stmt->fetch();

} else {
    // Default: Gallery
    $stmt = $pdo->prepare("SELECT image_data as img FROM gallery WHERE id = ?");
    $stmt->execute([$id]);
    $row = $stmt->fetch();
}

// --- 2. Output Logic ---

if ($row && !empty($row['img'])) {
    $imageData = $row['img'];

    // A. Detect correct MIME type (image/jpeg, image/png, etc.)
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->buffer($imageData);

    // B. Set Caching Headers (Cache for 7 days)
    // This reduces load on your MySQL server significantly
    $seconds_to_cache = 3600 * 24 * 7;
    $ts = gmdate("D, d M Y H:i:s", time() + $seconds_to_cache) . " GMT";
    
    header("Content-Type: " . $mimeType);
    header("Content-Length: " . strlen($imageData));
    header("Expires: $ts");
    header("Pragma: cache");
    header("Cache-Control: max-age=$seconds_to_cache");
    
    echo $imageData;

} else {
    // Image not found: Return 1x1 transparent pixel
    header("Content-Type: image/png");
    echo base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=');
}
?>