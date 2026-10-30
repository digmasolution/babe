<?php
// setup.php - Inisialisasi Database Babe
header('Content-Type: application/json');

$host = '127.0.0.1';
$user = 'root';
$pass = '';
$dbName = 'db_babe';

try {
    // 1. Koneksi ke server MySQL tanpa memilih DB terlebih dahulu
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    // 2. Buat Database jika belum ada
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `$dbName`");

    // 3. Buat Tabel Users
    $pdo->exec("CREATE TABLE IF NOT EXISTS `users` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `name` VARCHAR(100) NOT NULL,
        `email` VARCHAR(100) NOT NULL UNIQUE,
        `password` VARCHAR(255) NOT NULL,
        `phone` VARCHAR(25) NOT NULL,
        `city` VARCHAR(100) DEFAULT 'Jakarta',
        `role` ENUM('pembeli', 'penjual', 'logistik', 'admin') DEFAULT 'pembeli',
        `avatar` VARCHAR(255) DEFAULT NULL,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // 4. Buat Tabel Categories
    $pdo->exec("CREATE TABLE IF NOT EXISTS `categories` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `name` VARCHAR(100) NOT NULL,
        `icon` VARCHAR(50) NOT NULL,
        `slug` VARCHAR(100) NOT NULL UNIQUE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // 5. Buat Tabel Products
    $pdo->exec("CREATE TABLE IF NOT EXISTS `products` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT NOT NULL,
        `category_id` INT NOT NULL,
        `title` VARCHAR(200) NOT NULL,
        `description` TEXT NOT NULL,
        `price` DECIMAL(12, 2) NOT NULL,
        `condition_type` ENUM('seperti_baru', 'mulus', 'bekas_wajar', 'minus_pemakaian') DEFAULT 'bekas_wajar',
        `location` VARCHAR(100) NOT NULL,
        `status` ENUM('tersedia', 'terjual', 'nonaktif') DEFAULT 'tersedia',
        `views_count` INT DEFAULT 0,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
        FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE CASCADE,
        INDEX `idx_products_feed` (`status`, `created_at`, `id`),
        INDEX `idx_products_category_status` (`category_id`, `status`),
        INDEX `idx_products_user_status` (`user_id`, `status`),
        FULLTEXT KEY `idx_products_search` (`title`, `description`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // 6. Buat Tabel Product Images
    $pdo->exec("CREATE TABLE IF NOT EXISTS `product_images` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `product_id` INT NOT NULL,
        `image_path` VARCHAR(255) NOT NULL,
        `is_primary` TINYINT(1) DEFAULT 0,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE,
        INDEX `idx_product_images_lookup` (`product_id`, `is_primary`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // 7. Buat Tabel Favorites
    $pdo->exec("CREATE TABLE IF NOT EXISTS `favorites` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT NOT NULL,
        `product_id` INT NOT NULL,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY `unique_fav` (`user_id`, `product_id`),
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
        FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // 8. Seeder Kategori Default
    $categories = [
        ['Elektronik & Gadget', 'ri-smartphone-line', 'elektronik-gadget'],
        ['Pakaian & Mode', 'ri-t-shirt-line', 'pakaian-mode'],
        ['Otomotif & Aksesoris', 'ri-motorbike-line', 'otomotif-aksesoris'],
        ['Hobi, Game & Olahraga', 'ri-gamepad-line', 'hobi-game-olahraga'],
        ['Perabotan Rumah', 'ri-armchair-line', 'perabotan-rumah'],
        ['Buku & Edukasi', 'ri-book-read-line', 'buku-edukasi'],
        ['Lainnya', 'ri-more-fill', 'lainnya']
    ];

    $checkCat = $pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();
    if ($checkCat == 0) {
        $stmtCat = $pdo->prepare("INSERT INTO categories (name, icon, slug) VALUES (:name, :icon, :slug)");
        foreach ($categories as $cat) {
            $stmtCat->execute([':name' => $cat[0], ':icon' => $cat[1], ':slug' => $cat[2]]);
        }
    }

    // 9. Seeder Pengguna Default (Demo User Berbagai Peran Standar Online Shop)
    $checkUser = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    if ($checkUser == 0) {
        $stmtUser = $pdo->prepare("INSERT INTO users (name, email, password, phone, city, role) VALUES (:name, :email, :password, :phone, :city, :role)");
        $demoPass = password_hash('password123', PASSWORD_BCRYPT);
        
        // 1. Penjual Terverifikasi (Budi)
        $stmtUser->execute([
            ':name' => 'Budi Santoso',
            ':email' => 'budi@example.com',
            ':password' => $demoPass,
            ':phone' => '081234567890',
            ':city' => 'Jakarta Selatan',
            ':role' => 'penjual'
        ]);
        // 2. Penjual 2 (Siti)
        $stmtUser->execute([
            ':name' => 'Siti Rahma',
            ':email' => 'siti@example.com',
            ':password' => $demoPass,
            ':phone' => '089876543210',
            ':city' => 'Bandung',
            ':role' => 'penjual'
        ]);
        // 3. Pembeli (Rian)
        $stmtUser->execute([
            ':name' => 'Rian Pratama',
            ':email' => 'rian.pembeli@babe.id',
            ':password' => $demoPass,
            ':phone' => '085712345678',
            ':city' => 'Surabaya',
            ':role' => 'pembeli'
        ]);
        // 4. Logistik / Kurir (Joko - Babe Express)
        $stmtUser->execute([
            ':name' => 'Joko Handoko (Babe Express)',
            ':email' => 'joko.logistik@babe.id',
            ':password' => $demoPass,
            ':phone' => '087811223344',
            ':city' => 'Jabodetabek & Bandung',
            ':role' => 'logistik'
        ]);
        // 5. Admin Marketplace (Support & Verifikasi)
        $stmtUser->execute([
            ':name' => 'Admin Babe Support',
            ':email' => 'admin@babe.id',
            ':password' => $demoPass,
            ':phone' => '081199887766',
            ':city' => 'Jakarta Pusat (HQ)',
            ':role' => 'admin'
        ]);
    }

    // 10. Seeder Produk Demo jika kosong
    $checkProd = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
    if ($checkProd == 0) {
        $stmtProd = $pdo->prepare("INSERT INTO products (user_id, category_id, title, description, price, condition_type, location, status) 
            VALUES (:uid, :cid, :title, :desc, :price, :cond, :loc, 'tersedia')");
        
        $stmtImg = $pdo->prepare("INSERT INTO product_images (product_id, image_path, is_primary) VALUES (:pid, :img, 1)");

        // Produk 1
        $stmtProd->execute([
            ':uid' => 1,
            ':cid' => 1,
            ':title' => 'iPhone 11 128GB Black Bekas Mulus',
            ':desc' => 'Kondisi 92% BH 84% Original Ex iBox. Kelengkapan fullset original. Kamera jernih, Face ID on, True Tone aktif.',
            ':price' => 4500000,
            ':cond' => 'mulus',
            ':loc' => 'Jakarta Selatan'
        ]);
        $pid1 = $pdo->lastInsertId();
        $stmtImg->execute([':pid' => $pid1, ':img' => 'https://images.unsplash.com/photo-1591337676887-a217a6970a8a?w=600&auto=format&fit=crop&q=80']);

        // Produk 2
        $stmtProd->execute([
            ':uid' => 2,
            ':cid' => 3,
            ':title' => 'Helm KYT TT Course Dalla Porta Size L',
            ':desc' => 'Helm jarang dipakai, visor dapat 2 (smoke & clear). Busa masih padat dan wangi, ada lecet pemakaian minim di spoiler.',
            ':price' => 950000,
            ':cond' => 'bekas_wajar',
            ':loc' => 'Bandung'
        ]);
        $pid2 = $pdo->lastInsertId();
        $stmtImg->execute([':pid' => $pid2, ':img' => 'https://images.unsplash.com/photo-1558981806-ec527fa84c39?w=600&auto=format&fit=crop&q=80']);

        // Produk 3
        $stmtProd->execute([
            ':uid' => 1,
            ':cid' => 4,
            ':title' => 'Stik PS4 DualShock 4 Wireless Original Sony',
            ':desc' => 'Fungsi normal 100%, tombol empuk, baterai tahan 6-7 jam. Minus dus hilang, dikirim pakai bubble wrap tebal.',
            ':price' => 320000,
            ':cond' => 'mulus',
            ':loc' => 'Jakarta Selatan'
        ]);
        $pid3 = $pdo->lastInsertId();
        $stmtImg->execute([':pid' => $pid3, ':img' => 'https://images.unsplash.com/photo-1507457379470-08b800bebc67?w=600&auto=format&fit=crop&q=80']);

        // Produk 4
        $stmtProd->execute([
            ':uid' => 2,
            ':cid' => 5,
            ':title' => 'Kursi Kerja Ergonomis Mesh Jaring Fantech',
            ':desc' => 'Hidrolik naik turun normal, reclining lancar, jaring tidak ada yang robek. Cocok untuk WFH.',
            ':price' => 650000,
            ':cond' => 'bekas_wajar',
            ':loc' => 'Bandung'
        ]);
        $pid4 = $pdo->lastInsertId();
        $stmtImg->execute([':pid' => $pid4, ':img' => 'https://images.unsplash.com/photo-1580481077195-c3a821a58875?w=600&auto=format&fit=crop&q=80']);
    }

    echo json_encode([
        'status' => true,
        'message' => 'Database db_babe dan tabel berhasil dibuat/diperbarui dengan data seeder awal!',
        'database' => $dbName
    ], JSON_PRETTY_PRINT);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'status' => false,
        'error' => 'Koneksi MySQL Gagal: ' . $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
