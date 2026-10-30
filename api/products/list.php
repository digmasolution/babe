<?php
// api/products/list.php
require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/database.php';

$db = Database::getConnection();

// Filter parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$categoryId = isset($_GET['category_id']) && is_numeric($_GET['category_id']) ? (int)$_GET['category_id'] : 0;
$condition = isset($_GET['condition']) ? trim($_GET['condition']) : '';
$minPrice = isset($_GET['min_price']) && is_numeric($_GET['min_price']) ? (float)$_GET['min_price'] : 0;
$maxPrice = isset($_GET['max_price']) && is_numeric($_GET['max_price']) ? (float)$_GET['max_price'] : 0;
$status = isset($_GET['status']) ? trim($_GET['status']) : 'tersedia';
$userId = isset($_GET['user_id']) && is_numeric($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
$limit = isset($_GET['limit']) && is_numeric($_GET['limit']) ? (int)$_GET['limit'] : 50;
$sort = isset($_GET['sort']) ? trim($_GET['sort']) : 'newest';

$whereClauses = [];
$params = [];

// Filter status (default 'tersedia' jika bukan 'all')
if ($status !== 'all') {
    $whereClauses[] = "p.status = :filter_status";
    $params[':filter_status'] = $status;
}

// Search keyword
if (!empty($search)) {
    $whereClauses[] = "(p.title LIKE :search_title OR p.description LIKE :search_desc OR p.location LIKE :search_loc)";
    $params[':search_title'] = "%$search%";
    $params[':search_desc'] = "%$search%";
    $params[':search_loc'] = "%$search%";
}

// Kategori
if ($categoryId > 0) {
    $whereClauses[] = "p.category_id = :filter_cat";
    $params[':filter_cat'] = $categoryId;
}

// Kondisi
if (!empty($condition) && in_array($condition, ['seperti_baru', 'mulus', 'bekas_wajar', 'minus_pemakaian'])) {
    $whereClauses[] = "p.condition_type = :filter_cond";
    $params[':filter_cond'] = $condition;
}

// Range Harga
if ($minPrice > 0) {
    $whereClauses[] = "p.price >= :min_price";
    $params[':min_price'] = $minPrice;
}
if ($maxPrice > 0) {
    $whereClauses[] = "p.price <= :max_price";
    $params[':max_price'] = $maxPrice;
}

// User spesifik
if ($userId > 0) {
    $whereClauses[] = "p.user_id = :filter_user";
    $params[':filter_user'] = $userId;
}

$whereSql = "";
if (!empty($whereClauses)) {
    $whereSql = "WHERE " . implode(" AND ", $whereClauses);
}

// Sorting
$orderSql = "ORDER BY p.id DESC";
if ($sort === 'price_asc') {
    $orderSql = "ORDER BY p.price ASC";
} elseif ($sort === 'price_desc') {
    $orderSql = "ORDER BY p.price DESC";
} elseif ($sort === 'popular') {
    $orderSql = "ORDER BY p.views_count DESC";
}

$query = "SELECT 
            p.id, 
            p.user_id, 
            p.category_id, 
            p.title, 
            p.description, 
            p.price, 
            p.condition_type, 
            p.location, 
            p.status, 
            p.views_count, 
            p.created_at,
            c.name AS category_name,
            c.icon AS category_icon,
            u.name AS seller_name,
            u.phone AS seller_phone,
            (SELECT image_path FROM product_images WHERE product_id = p.id ORDER BY is_primary DESC, id ASC LIMIT 1) AS primary_image
          FROM products p
          INNER JOIN categories c ON p.category_id = c.id
          INNER JOIN users u ON p.user_id = u.id
          $whereSql
          $orderSql
          LIMIT :limit";

$stmt = $db->prepare($query);

// Bind params satu per satu dengan tipe data yang tepat
foreach ($params as $key => $val) {
    if (is_int($val)) {
        $stmt->bindValue($key, $val, PDO::PARAM_INT);
    } else {
        $stmt->bindValue($key, $val, PDO::PARAM_STR);
    }
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);

$stmt->execute();
$products = $stmt->fetchAll();

// Format numeric & URL gambar
foreach ($products as &$prod) {
    $prod['id'] = (int)$prod['id'];
    $prod['price'] = (float)$prod['price'];
    $prod['views_count'] = (int)$prod['views_count'];
    if (empty($prod['primary_image'])) {
        $prod['primary_image'] = 'https://images.unsplash.com/photo-1584438784894-089d6a62b8fa?w=600&auto=format&fit=crop&q=80';
    }
}

sendResponse(true, "Data produk berhasil diambil.", $products);
