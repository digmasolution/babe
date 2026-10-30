<?php
// api/products/update_status.php
require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse(false, "Metode HTTP tidak diizinkan. Gunakan POST.", null, 405);
}

$input = getRequestBody();
$productId = isset($input['product_id']) ? (int)$input['product_id'] : 0;
$userId = isset($input['user_id']) ? (int)$input['user_id'] : 0;
$status = trim($input['status'] ?? '');

if ($productId <= 0 || $userId <= 0 || empty($status)) {
    sendResponse(false, "Data product_id, user_id, dan status wajib dikirim.", null, 400);
}

$allowedStatuses = ['tersedia', 'terjual', 'nonaktif'];
if (!in_array($status, $allowedStatuses)) {
    sendResponse(false, "Status tidak valid. Pilihan: tersedia, terjual, nonaktif.", null, 400);
}

$db = Database::getConnection();

// Pastikan pemilik produk yang mengupdate
$checkStmt = $db->prepare("SELECT id FROM products WHERE id = :p_id AND user_id = :u_id LIMIT 1");
$checkStmt->execute([':p_id' => $productId, ':u_id' => $userId]);
if (!$checkStmt->fetch()) {
    sendResponse(false, "Anda tidak memiliki akses untuk mengubah status produk ini.", null, 403);
}

$stmt = $db->prepare("UPDATE products SET status = :status WHERE id = :p_id");
$stmt->execute([':status' => $status, ':p_id' => $productId]);

sendResponse(true, "Status barang berhasil diubah menjadi: " . $status, [
    'product_id' => $productId,
    'status' => $status
]);
