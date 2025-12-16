<?php
require 'includes/db.php';

$type = isset($_GET['type']) ? $_GET['type'] : 'gallery';
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Handle Logo
if ($type === 'logo') {
    $stmt = $pdo->prepare("SELECT logo_data as img FROM site_identity WHERE id = 1");
    $stmt->execute();
    $row = $stmt->fetch();
} 
// Handle Network
elseif ($type === 'network') {
    $stmt = $pdo->prepare("SELECT flag_data as img FROM network WHERE id = ?");
    $stmt->execute([$id]);
    $row = $stmt->fetch();
}
// --- NEW: Handle Site Content Images (Services, Hero, etc.) ---
elseif ($type === 'content') {
    $key = isset($_GET['key']) ? $_GET['key'] : '';
    // Fetch the BLOB directly from site_content
    $stmt = $pdo->prepare("SELECT content_value as img FROM site_content WHERE content_key = ?");
    $stmt->execute([$key]);
    $row = $stmt->fetch();
    
}
// NEW: Handle About Carousel
elseif ($type === 'about_slide') {
    $stmt = $pdo->prepare("SELECT image_data as img FROM about_carousel WHERE id = ?");
    $stmt->execute([$id]);
    $row = $stmt->fetch();
}
// NEW: Handle Badge (Specific Key content)
elseif ($type === 'content') {
    $key = $_GET['key'] ?? '';
    $stmt = $pdo->prepare("SELECT content_value as img FROM site_content WHERE content_key = ?");
    $stmt->execute([$key]);
    $row = $stmt->fetch();
}
// Handle Gallery (Default)
else {
    $stmt = $pdo->prepare("SELECT image_data as img FROM gallery WHERE id = ?");
    $stmt->execute([$id]);
    $row = $stmt->fetch();
}

if ($row && !empty($row['img'])) {
    header("Content-Type: image/png");
    echo $row['img'];
} else {
    // 1x1 Transparent Pixel
    header("Content-Type: image/png");
    echo base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=');
}
?>