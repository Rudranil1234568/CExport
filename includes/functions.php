<?php
require_once 'db.php';

function getContent($key) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT content_value FROM site_content WHERE content_key = ?");
    $stmt->execute([$key]);
    $res = $stmt->fetch();
    return $res ? $res['content_value'] : '[Missing Text]';
}

function getGallery() {
    global $pdo;
    // FIXED: Changed 'grade_info' to 'grade' to match your database schema
    $stmt = $pdo->query("SELECT id, title, scientific_name, origin, category, grade FROM gallery ORDER BY id DESC");
    return $stmt->fetchAll();
}
?>