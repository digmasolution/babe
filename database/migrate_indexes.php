<?php
// database/migrate_indexes.php - Eksekusi Indeks Performa Database Babe
require_once __DIR__ . '/../api/config/database.php';

try {
    $db = Database::getConnection();
    echo "=== Memulai Optimasi Indeks Basis Data Babe (MySQL/InnoDB) ===" . PHP_EOL;

    // Helper untuk cek apakah index sudah ada
    function indexExists($db, $table, $indexName) {
        $stmt = $db->prepare("SHOW INDEX FROM `$table` WHERE Key_name = :key_name");
        $stmt->execute([':key_name' => $indexName]);
        return $stmt->rowCount() > 0;
    }

    $indexes = [
        [
            'table' => 'products',
            'name' => 'idx_products_feed',
            'sql' => "ALTER TABLE `products` ADD INDEX `idx_products_feed` (`status`, `created_at`, `id`)"
        ],
        [
            'table' => 'products',
            'name' => 'idx_products_category_status',
            'sql' => "ALTER TABLE `products` ADD INDEX `idx_products_category_status` (`category_id`, `status`)"
        ],
        [
            'table' => 'products',
            'name' => 'idx_products_user_status',
            'sql' => "ALTER TABLE `products` ADD INDEX `idx_products_user_status` (`user_id`, `status`)"
        ],
        [
            'table' => 'products',
            'name' => 'idx_products_search',
            'sql' => "ALTER TABLE `products` ADD FULLTEXT INDEX `idx_products_search` (`title`, `description`)"
        ],
        [
            'table' => 'product_images',
            'name' => 'idx_product_images_lookup',
            'sql' => "ALTER TABLE `product_images` ADD INDEX `idx_product_images_lookup` (`product_id`, `is_primary`)"
        ]
    ];

    foreach ($indexes as $idx) {
        if (!indexExists($db, $idx['table'], $idx['name'])) {
            $db->exec($idx['sql']);
            echo " [OK] Indeks '{$idx['name']}' berhasil ditambahkan pada tabel '{$idx['table']}'." . PHP_EOL;
        } else {
            echo " [SKIP] Indeks '{$idx['name']}' sudah ada pada tabel '{$idx['table']}'." . PHP_EOL;
        }
    }

    echo PHP_EOL . "=== Verifikasi Indeks Fisik Tabel products ===" . PHP_EOL;
    $currIndexes = $db->query("SHOW INDEX FROM products")->fetchAll();
    foreach ($currIndexes as $row) {
        echo " -> " . $row['Key_name'] . " (" . $row['Column_name'] . ") [Index Type: " . $row['Index_type'] . "]" . PHP_EOL;
    }

    echo PHP_EOL . "=== Verifikasi Indeks Fisik Tabel product_images ===" . PHP_EOL;
    $currImgIndexes = $db->query("SHOW INDEX FROM product_images")->fetchAll();
    foreach ($currImgIndexes as $row) {
        echo " -> " . $row['Key_name'] . " (" . $row['Column_name'] . ") [Index Type: " . $row['Index_type'] . "]" . PHP_EOL;
    }

    echo PHP_EOL . "Status: Sukses 100%!" . PHP_EOL;
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . PHP_EOL;
}
