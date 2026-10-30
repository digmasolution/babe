<?php
// api/products/detail.php
require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/database.php';

$id = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    sendResponse(false, "ID Produk tidak valid.", null, 400);
}

$db = Database::getConnection();

// Increment views_count
$db->prepare("UPDATE products SET views_count = views_count + 1 WHERE id = :id")->execute([':id' => $id]);

// Query produk utama
$query = "SELECT 
            p.*, 
            c.name AS category_name, 
            c.icon AS category_icon,
            u.name AS seller_name,
            u.email AS seller_email,
            u.phone AS seller_phone,
            u.city AS seller_city,
            u.created_at AS seller_joined
          FROM products p
          INNER JOIN categories c ON p.category_id = c.id
          INNER JOIN users u ON p.user_id = u.id
          WHERE p.id = :prod_id LIMIT 1";

$stmt = $db->prepare($query);
$stmt->execute([':prod_id' => $id]);
$product = $stmt->fetch();

if (!$product) {
    sendResponse(false, "Produk tidak ditemukan atau telah dihapus.", null, 404);
}

// Ambil semua foto produk
$imgStmt = $db->prepare("SELECT id, image_path, is_primary FROM product_images WHERE product_id = :p_id ORDER BY is_primary DESC, id ASC");
$imgStmt->execute([':p_id' => $id]);
$images = $imgStmt->fetchAll();

if (empty($images)) {
    $images = [
        ['id' => 0, 'image_path' => 'https://images.unsplash.com/photo-1584438784894-089d6a62b8fa?w=600&auto=format&fit=crop&q=80', 'is_primary' => 1]
    ];
}
$product['images'] = $images;

// Ambil produk terkait dari kategori yang sama
$relStmt = $db->prepare("SELECT p.id, p.title, p.price, p.location, p.condition_type,
            (SELECT image_path FROM product_images WHERE product_id = p.id ORDER BY is_primary DESC, id ASC LIMIT 1) AS primary_image
            FROM products p 
            WHERE p.category_id = :cat_id AND p.id != :cur_id AND p.status = 'tersedia'
            ORDER BY p.id DESC LIMIT 4");
$relStmt->execute([
    ':cat_id' => $product['category_id'],
    ':cur_id' => $id
]);
$related = $relStmt->fetchAll();
$product['related_products'] = $related;

sendResponse(true, "Detail produk berhasil dimuat.", $product);
