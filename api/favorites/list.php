<?php
// api/favorites/list.php
require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/database.php';

$userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
if ($userId <= 0) {
    sendResponse(false, "user_id wajib dikirim.", null, 400);
}

$db = Database::getConnection();

$query = "SELECT 
            f.id AS favorite_id,
            f.created_at AS favorited_at,
            p.*,
            c.name AS category_name,
            u.name AS seller_name,
            u.phone AS seller_phone,
            (SELECT image_path FROM product_images WHERE product_id = p.id ORDER BY is_primary DESC, id ASC LIMIT 1) AS primary_image
          FROM favorites f
          INNER JOIN products p ON f.product_id = p.id
          INNER JOIN categories c ON p.category_id = c.id
          INNER JOIN users u ON p.user_id = u.id
          WHERE f.user_id = :u_id
          ORDER BY f.id DESC";

$stmt = $db->prepare($query);
$stmt->execute([':u_id' => $userId]);
$favorites = $stmt->fetchAll();

foreach ($favorites as &$fav) {
    $fav['id'] = (int)$fav['id'];
    $fav['price'] = (float)$fav['price'];
    if (empty($fav['primary_image'])) {
        $fav['primary_image'] = 'https://images.unsplash.com/photo-1584438784894-089d6a62b8fa?w=600&auto=format&fit=crop&q=80';
    }
}

sendResponse(true, "Daftar barang favorit berhasil dimuat.", $favorites);
