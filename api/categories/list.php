<?php
// api/categories/list.php
require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/database.php';

$db = Database::getConnection();

$query = "SELECT c.id, c.name, c.icon, c.slug, 
          COUNT(p.id) AS products_count
          FROM categories c
          LEFT JOIN products p ON c.id = p.category_id AND p.status = 'tersedia'
          GROUP BY c.id, c.name, c.icon, c.slug
          ORDER BY c.id ASC";

$stmt = $db->query($query);
$categories = $stmt->fetchAll();

sendResponse(true, "Daftar kategori berhasil diambil.", $categories);
