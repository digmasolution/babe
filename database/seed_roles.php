<?php
// database/seed_roles.php - Tambahkan kolom role dan seeder akun demo standar online shop
require_once __DIR__ . '/../api/config/database.php';

try {
    $db = Database::getConnection();
    echo "=== Menyiapkan Kolom Role & Akun Demo Standar Online Shop ===" . PHP_EOL;

    // 1. Cek apakah kolom role sudah ada di tabel users
    $stmt = $db->query("SHOW COLUMNS FROM `users` LIKE 'role'");
    if ($stmt->rowCount() === 0) {
        $db->exec("ALTER TABLE `users` ADD COLUMN `role` ENUM('pembeli', 'penjual', 'logistik', 'admin') DEFAULT 'pembeli' AFTER `city`");
        echo " [OK] Kolom 'role' berhasil ditambahkan ke tabel `users`." . PHP_EOL;
    } else {
        echo " [SKIP] Kolom 'role' sudah ada." . PHP_EOL;
    }

    // 2. Daftar Akun Demo Standar Marketplace / Online Shop
    $demoPass = password_hash('password123', PASSWORD_BCRYPT);
    $demoUsers = [
        [
            'id' => 1,
            'name' => 'Budi Santoso',
            'email' => 'budi@example.com',
            'password' => $demoPass,
            'phone' => '081234567890',
            'city' => 'Jakarta Selatan',
            'role' => 'penjual'
        ],
        [
            'id' => 2,
            'name' => 'Siti Rahma',
            'email' => 'siti@example.com',
            'password' => $demoPass,
            'phone' => '089876543210',
            'city' => 'Bandung',
            'role' => 'penjual'
        ],
        [
            'id' => 3,
            'name' => 'Rian Pratama',
            'email' => 'rian.pembeli@babe.id',
            'password' => $demoPass,
            'phone' => '085712345678',
            'city' => 'Surabaya',
            'role' => 'pembeli'
        ],
        [
            'id' => 4,
            'name' => 'Joko Handoko (Babe Express)',
            'email' => 'joko.logistik@babe.id',
            'password' => $demoPass,
            'phone' => '087811223344',
            'city' => 'Jabodetabek & Bandung',
            'role' => 'logistik'
        ],
        [
            'id' => 5,
            'name' => 'Admin Babe Support',
            'email' => 'admin@babe.id',
            'password' => $demoPass,
            'phone' => '081199887766',
            'city' => 'Jakarta Pusat (HQ)',
            'role' => 'admin'
        ]
    ];

    foreach ($demoUsers as $u) {
        $chk = $db->prepare("SELECT id FROM users WHERE email = :email");
        $chk->execute([':email' => $u['email']]);
        $existing = $chk->fetch();

        if ($existing) {
            $update = $db->prepare("UPDATE users SET name = :name, phone = :phone, city = :city, role = :role WHERE id = :id");
            $update->execute([
                ':name' => $u['name'],
                ':phone' => $u['phone'],
                ':city' => $u['city'],
                ':role' => $u['role'],
                ':id' => $existing['id']
            ]);
            echo " [UPDATE] Akun demo {$u['name']} ({$u['role']}) berhasil diperbarui." . PHP_EOL;
        } else {
            $insert = $db->prepare("INSERT INTO users (name, email, password, phone, city, role) VALUES (:name, :email, :password, :phone, :city, :role)");
            $insert->execute([
                ':name' => $u['name'],
                ':email' => $u['email'],
                ':password' => $u['password'],
                ':phone' => $u['phone'],
                ':city' => $u['city'],
                ':role' => $u['role']
            ]);
            echo " [INSERT] Akun demo {$u['name']} ({$u['role']}) berhasil dibuat." . PHP_EOL;
        }
    }

    echo PHP_EOL . "=== Daftar Akun Demo Aktif ===" . PHP_EOL;
    $list = $db->query("SELECT id, name, email, role, city, phone FROM users ORDER BY id ASC")->fetchAll();
    foreach ($list as $row) {
        echo " -> [ID: {$row['id']}] {$row['name']} | Role: {$row['role']} | Email: {$row['email']} | Kota: {$row['city']}" . PHP_EOL;
    }

    echo PHP_EOL . "Status: Selesai 100%!" . PHP_EOL;
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . PHP_EOL;
}
