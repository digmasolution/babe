<?php
// api/products/create.php
require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse(false, "Metode HTTP tidak diizinkan. Gunakan POST.", null, 405);
}

$input = getRequestBody();

$userId = isset($input['user_id']) ? (int)$input['user_id'] : 0;
$categoryId = isset($input['category_id']) ? (int)$input['category_id'] : 0;
$title = trim($input['title'] ?? '');
$description = trim($input['description'] ?? '');
$price = isset($input['price']) ? (float)$input['price'] : 0;
$conditionType = trim($input['condition_type'] ?? 'bekas_wajar');
$location = trim($input['location'] ?? 'Jakarta');

// Validasi
if ($userId <= 0) {
    sendResponse(false, "Pengguna belum login (user_id tidak valid).", null, 401);
}
if ($categoryId <= 0) {
    sendResponse(false, "Silakan pilih kategori barang.", null, 400);
}
if (empty($title)) {
    sendResponse(false, "Judul barang wajib diisi.", null, 400);
}
if (empty($description)) {
    sendResponse(false, "Deskripsi barang wajib diisi.", null, 400);
}
if ($price <= 0) {
    sendResponse(false, "Harga harus lebih besar dari 0.", null, 400);
}

$validConditions = ['seperti_baru', 'mulus', 'bekas_wajar', 'minus_pemakaian'];
if (!in_array($conditionType, $validConditions)) {
    $conditionType = 'bekas_wajar';
}

$db = Database::getConnection();

try {
    $db->beginTransaction();

    // 1. Simpan Produk
    $stmt = $db->prepare("INSERT INTO products (user_id, category_id, title, description, price, condition_type, location, status) 
                          VALUES (:uid, :cid, :title, :desc, :price, :cond, :loc, 'tersedia')");
    $stmt->execute([
        ':uid' => $userId,
        ':cid' => $categoryId,
        ':title' => $title,
        ':desc' => $description,
        ':price' => $price,
        ':cond' => $conditionType,
        ':loc' => $location
    ]);
    $productId = $db->lastInsertId();

    // 2. Proses Upload Foto
    $savedImages = [];
    $uploadDir = __DIR__ . '/../../uploads/';

    // A. Cek file multipart upload $_FILES
    if (isset($_FILES['images']) && is_array($_FILES['images']['name'])) {
        $imgCount = count($_FILES['images']['name']);
        for ($i = 0; $i < $imgCount; $i++) {
            if ($_FILES['images']['error'][$i] === UPLOAD_ERR_OK) {
                $tmpName = $_FILES['images']['tmp_name'][$i];
                $origName = basename($_FILES['images']['name'][$i]);
                $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
                
                if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'])) {
                    $newName = 'prod_' . $productId . '_' . time() . '_' . $i . '.' . $ext;
                    $dest = $uploadDir . $newName;
                    if (move_uploaded_file($tmpName, $dest)) {
                        $savedImages[] = 'uploads/' . $newName;
                    }
                }
            }
        }
    } elseif (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $tmpName = $_FILES['image']['tmp_name'];
        $origName = basename($_FILES['image']['name']);
        $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'])) {
            $newName = 'prod_' . $productId . '_' . time() . '.' . $ext;
            $dest = $uploadDir . $newName;
            if (move_uploaded_file($tmpName, $dest)) {
                $savedImages[] = 'uploads/' . $newName;
            }
        }
    }

    // B. Cek URL gambar manual dari input (opsional jika pengguna memasukkan URL atau base64)
    if (isset($input['image_url']) && !empty($input['image_url'])) {
        $savedImages[] = trim($input['image_url']);
    }

    // Default placeholder jika tidak ada foto sama sekali
    if (empty($savedImages)) {
        $savedImages[] = 'https://images.unsplash.com/photo-1526170375885-4d8ecf77b99f?w=600&auto=format&fit=crop&q=80';
    }

    // 3. Simpan ke tabel product_images
    $imgStmt = $db->prepare("INSERT INTO product_images (product_id, image_path, is_primary) VALUES (:pid, :path, :is_prim)");
    foreach ($savedImages as $idx => $path) {
        $isPrim = ($idx === 0) ? 1 : 0;
        $imgStmt->execute([
            ':pid' => $productId,
            ':path' => $path,
            ':is_prim' => $isPrim
        ]);
    }

    $db->commit();

    sendResponse(true, "Iklan barang bekas berhasil dipasang!", [
        'product_id' => (int)$productId,
        'title' => $title,
        'images' => $savedImages
    ], 201);

} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    sendResponse(false, "Terjadi kesalahan saat menyimpan produk: " . $e->getMessage(), null, 500);
}
