<?php
// api/auth/register.php
require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse(false, "Metode HTTP tidak diizinkan. Gunakan POST.", null, 405);
}

$input = getRequestBody();
$name = trim($input['name'] ?? '');
$email = trim($input['email'] ?? '');
$password = trim($input['password'] ?? '');
$phone = trim($input['phone'] ?? '');
$city = trim($input['city'] ?? 'Jakarta');

if (empty($name) || empty($email) || empty($password) || empty($phone)) {
    sendResponse(false, "Nama, email, nomor HP/WhatsApp, dan password wajib diisi.", null, 400);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    sendResponse(false, "Format email tidak valid.", null, 400);
}

if (strlen($password) < 6) {
    sendResponse(false, "Password minimal 6 karakter.", null, 400);
}

$db = Database::getConnection();

// Cek apakah email sudah terdaftar
$checkStmt = $db->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
$checkStmt->execute([':email' => $email]);
if ($checkStmt->fetch()) {
    sendResponse(false, "Email ini sudah terdaftar. Silakan login atau gunakan email lain.", null, 409);
}

// Simpan user baru
$hashedPassword = password_hash($password, PASSWORD_BCRYPT);
$insertStmt = $db->prepare("INSERT INTO users (name, email, password, phone, city) VALUES (:name, :email, :password, :phone, :city)");
$success = $insertStmt->execute([
    ':name' => $name,
    ':email' => $email,
    ':password' => $hashedPassword,
    ':phone' => $phone,
    ':city' => $city
]);

if ($success) {
    $userId = $db->lastInsertId();
    $userData = [
        'id' => (int)$userId,
        'name' => $name,
        'email' => $email,
        'phone' => $phone,
        'city' => $city,
        'avatar' => null,
        'token' => base64_encode($userId . ':' . time() . ':' . bin2hex(random_bytes(16)))
    ];
    sendResponse(true, "Pendaftaran berhasil! Selamat datang di Babe.", $userData, 201);
} else {
    sendResponse(false, "Gagal mendaftarkan pengguna ke database.", null, 500);
}
