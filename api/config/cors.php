<?php
// api/config/cors.php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json; charset=UTF-8");

if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Helper untuk membaca request body JSON atau form data
function getRequestBody() {
    $rawInput = file_get_contents("php://input");
    $jsonData = json_decode($rawInput, true);
    if (is_array($jsonData)) {
        return array_merge($_POST, $jsonData);
    }
    return $_POST;
}

// Helper response JSON
function sendResponse($status, $message, $data = null, $code = 200) {
    http_response_code($code);
    $response = [
        "status" => $status,
        "message" => $message
    ];
    if ($data !== null) {
        $response["data"] = $data;
    }
    echo json_encode($response, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    exit;
}
