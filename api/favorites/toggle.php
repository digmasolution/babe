<?php
// api/favorites/toggle.php
require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse(false, "Metode HTTP tidak diizinkan. Gunakan POST.", null, 405);
}

$input = getRequestBody();
$userId = isset($input['user_id']) ? (int)$input['user_id'] : 0;
$productId = isset($input['product_id']) ? (int)$input['product_id'] : 0;

if ($userId <= 0 || $productId <= 0) {
    sendResponse(false, "user_id dan product_id wajib diisi.", null, 400);
}

$db = Database::getConnection();

// Cek apakah sudah difavoritkan
$checkStmt = $db->prepare("SELECT id FROM favorites WHERE user_id = :u_id AND product_id = :p_id LIMIT 1");
$checkStmt->execute([':u_id' => $userId, ':p_id' => $productId]);
$fav = $checkStmt->fetch();

if ($fav) {
    // Hapus dari favorit
    $delStmt = $db->prepare("DELETE FROM favorites WHERE id = :fav_id");
    $delStmt->execute([':fav_id' => $fav['id']]);
    sendResponse(true, "Barang dihapus dari daftar favorit.", ['is_favorite' => false]);
} else {
    // Tambahkan ke favorit
    $insStmt = $db->prepare("INSERT INTO favorites (user_id, product_id) VALUES (:ins_uid, :ins_pid)");
    $insStmt->execute([':ins_uid' => $userId, ':ins_pid' => $productId]);
    sendResponse(true, "Barang ditambahkan ke daftar favorit!", ['is_favorite' => true]);
}
