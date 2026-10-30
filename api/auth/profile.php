<?php
// api/auth/profile.php
require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/database.php';

$input = getRequestBody();
$userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : (int)($input['user_id'] ?? 0);
if ($userId <= 0) {
    sendResponse(false, "Parameter user_id tidak valid.", null, 400);
}

$db = Database::getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $db->prepare("SELECT id, name, email, role, phone, city, avatar, created_at FROM users WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $userId]);
    $user = $stmt->fetch();

    if (!$user) {
        sendResponse(false, "Pengguna tidak ditemukan.", null, 404);
    }

    // Hitung total barang dijual & terjual
    $statStmt = $db->prepare("SELECT 
        COUNT(*) as total_items,
        SUM(CASE WHEN status = 'terjual' THEN 1 ELSE 0 END) as sold_items,
        SUM(CASE WHEN status = 'tersedia' THEN 1 ELSE 0 END) as active_items
        FROM products WHERE user_id = :uid");
    $statStmt->execute([':uid' => $userId]);
    $user['stats'] = $statStmt->fetch();

    sendResponse(true, "Data profil berhasil diambil.", $user);
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = getRequestBody();
    $name = trim($input['name'] ?? '');
    $phone = trim($input['phone'] ?? '');
    $city = trim($input['city'] ?? '');

    if (empty($name) || empty($phone)) {
        sendResponse(false, "Nama dan nomor telepon wajib diisi.", null, 400);
    }

    $stmt = $db->prepare("UPDATE users SET name = :name, phone = :phone, city = :city WHERE id = :id");
    $stmt->execute([
        ':name' => $name,
        ':phone' => $phone,
        ':city' => $city,
        ':id' => $userId
    ]);

    sendResponse(true, "Profil berhasil diperbarui.", [
        'id' => $userId,
        'name' => $name,
        'phone' => $phone,
        'city' => $city
    ]);
} else {
    sendResponse(false, "Metode HTTP tidak diizinkan.", null, 405);
}
