<?php
// api/auth/login.php
require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse(false, "Metode HTTP tidak diizinkan. Gunakan POST.", null, 405);
}

$input = getRequestBody();
$email = trim($input['email'] ?? '');
$password = trim($input['password'] ?? '');

if (empty($email) || empty($password)) {
    sendResponse(false, "Email dan password wajib diisi.", null, 400);
}

$db = Database::getConnection();
$stmt = $db->prepare("SELECT id, name, email, role, password, phone, city, avatar, created_at FROM users WHERE email = :email LIMIT 1");
$stmt->execute([':email' => $email]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password'])) {
    sendResponse(false, "Email atau password salah.", null, 401);
}

// Hapus hash password dari response demi keamanan
unset($user['password']);
$user['id'] = (int)$user['id'];

// Generate simple auth token
$token = base64_encode($user['id'] . ':' . time() . ':' . bin2hex(random_bytes(16)));
$user['token'] = $token;

sendResponse(true, "Login berhasil!", $user);
