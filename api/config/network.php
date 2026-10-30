<?php
require_once __DIR__ . '/cors.php';

// Deteksi IP Address Wi-Fi / LAN komputer host
function getHostIpAddresses() {
    $out = shell_exec('ipconfig 2>&1');
    $lines = explode("\n", (string)$out);
    $adapters = [];
    $current_adapter = 'Unknown';

    foreach ($lines as $line) {
        $trimmed = trim($line);
        if (preg_match('/adapter\s+(.+?):/i', $trimmed, $m)) {
            $current_adapter = trim($m[1]);
        }
        if (preg_match('/(?:IPv4 Address|Alamat IPv4)[ .]*: ([\d\.]+)/i', $trimmed, $m)) {
            $ip = trim($m[1]);
            if ($ip !== '127.0.0.1' && !str_starts_with($ip, '169.254')) {
                $adapters[$current_adapter] = $ip;
            }
        }
    }

    // Prioritaskan adapter Wi-Fi atau Wireless
    $wifi_ip = null;
    foreach ($adapters as $name => $ip) {
        if (stripos($name, 'wi-fi') !== false || stripos($name, 'wireless') !== false) {
            $wifi_ip = $ip;
            break;
        }
    }

    // Jika tidak ada Wi-Fi spesifik, ambil adapter LAN selain vEthernet/Hyper-V/WSL
    if (!$wifi_ip) {
        foreach ($adapters as $name => $ip) {
            if (stripos($name, 'vethernet') === false && stripos($name, 'wsl') === false && stripos($name, 'virtual') === false) {
                $wifi_ip = $ip;
                break;
            }
        }
    }

    // Fallback terakhir
    if (!$wifi_ip && !empty($adapters)) {
        $wifi_ip = reset($adapters);
    }

    if (!$wifi_ip) {
        $wifi_ip = gethostbyname(gethostname()) ?: '127.0.0.1';
    }

    return [
        'primary_ip' => $wifi_ip,
        'adapters' => $adapters
    ];
}

$net = getHostIpAddresses();
$port = $_SERVER['SERVER_PORT'] ?? 80;
$port_str = ($port == 80 || $port == 443) ? '' : ':' . $port;
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';

$mobile_url = "{$protocol}://{$net['primary_ip']}{$port_str}/Babe/";

echo json_encode([
    'status' => true,
    'wifi_ip' => $net['primary_ip'],
    'mobile_url' => $mobile_url,
    'adapters' => $net['adapters'],
    'server_time' => date('Y-m-d H:i:s')
], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
