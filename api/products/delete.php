<?php
// api/products/delete.php
require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse(false, "Metode HTTP tidak diizinkan. Gunakan POST.", null, 405);
}

$input = getRequestBody();
$productId = isset($input['product_id']) ? (int)$input['product_id'] : 0;
$userId = isset($input['user_id']) ? (int)$input['user_id'] : 0;

if ($productId <= 0 || $userId <= 0) {
    sendResponse(false, "Data product_id dan user_id wajib diisi.", null, 400);
}

$db = Database::getConnection();

// Pastikan pemilik produk
$checkStmt = $db->prepare("SELECT id FROM products WHERE id = :p_id AND user_id = :u_id LIMIT 1");
$checkStmt->execute([':p_id' => $productId, ':u_id' => $userId]);
if (!$checkStmt->fetch()) {
    sendResponse(false, "Anda tidak memiliki akses untuk menghapus produk ini.", null, 403);
}

// Ambil file foto lokal jika ada untuk dihapus fisiknya
$imgStmt = $db->prepare("SELECT image_path FROM product_images WHERE product_id = :p_id");
$imgStmt->execute([':p_id' => $productId]);
$images = $imgStmt->fetchAll();
foreach ($images as $img) {
    if (strpos($img['image_path'], 'uploads/') === 0) {
        $fullPath = __DIR__ . '/../../' . $img['image_path'];
        if (file_exists($fullPath)) {
            @unlink($fullPath);
        }
    }
}

// Hapus produk (foreign key ON DELETE CASCADE akan menghapus data di product_images & favorites)
$delStmt = $db->prepare("DELETE FROM products WHERE id = :p_id");
$delStmt->execute([':p_id' => $productId]);

sendResponse(true, "Iklan barang berhasil dihapus.", ['product_id' => $productId]);
