<?php
// api/products/my_products.php
require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/database.php';

$userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
if ($userId <= 0) {
    sendResponse(false, "User ID wajib dikirim.", null, 400);
}

$db = Database::getConnection();

$query = "SELECT 
            p.*, 
            c.name AS category_name,
            (SELECT image_path FROM product_images WHERE product_id = p.id ORDER BY is_primary DESC, id ASC LIMIT 1) AS primary_image
          FROM products p
          INNER JOIN categories c ON p.category_id = c.id
          WHERE p.user_id = :u_id
          ORDER BY p.id DESC";

$stmt = $db->prepare($query);
$stmt->execute([':u_id' => $userId]);
$products = $stmt->fetchAll();

foreach ($products as &$p) {
    $p['id'] = (int)$p['id'];
    $p['price'] = (float)$p['price'];
    $p['views_count'] = (int)$p['views_count'];
    if (empty($p['primary_image'])) {
        $p['primary_image'] = 'https://images.unsplash.com/photo-1584438784894-089d6a62b8fa?w=600&auto=format&fit=crop&q=80';
    }
}

sendResponse(true, "Daftar barang jualan saya berhasil dimuat.", $products);
