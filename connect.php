<?php
// Deteksi IP Address Wi-Fi / LAN komputer host
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
$wifi_ip = null;
foreach ($adapters as $name => $ip) {
    if (stripos($name, 'wi-fi') !== false || stripos($name, 'wireless') !== false) {
        $wifi_ip = $ip;
        break;
    }
}
if (!$wifi_ip && !empty($adapters)) {
    $wifi_ip = reset($adapters);
}
if (!$wifi_ip) {
    $wifi_ip = '127.0.0.1';
}

$port = $_SERVER['SERVER_PORT'] ?? 80;
$port_str = ($port == 80 || $port == 443) ? '' : ':' . $port;
$mobile_url = "http://{$wifi_ip}{$port_str}/Babe/";
$qr_url = "https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=" . urlencode($mobile_url);

header("Content-Type: text/html; charset=UTF-8");
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Buka Babe di HP (Localhost Wi-Fi)</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/remixicon@4.2.0/fonts/remixicon.css">
  <style>
    :root {
      --primary: #2563eb;
      --primary-dark: #1d4ed8;
      --success: #10b981;
      --bg: #f8fafc;
      --card: #ffffff;
      --text-dark: #0f172a;
      --text-muted: #64748b;
      --border: #e2e8f0;
    }
    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
      font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
    }
    body {
      background: #f1f5f9;
      color: var(--text-dark);
      display: flex;
      justify-content: center;
      align-items: center;
      min-height: 100vh;
      padding: 20px;
    }
    .container {
      background: var(--card);
      max-width: 520px;
      width: 100%;
      border-radius: 24px;
      box-shadow: 0 20px 40px -15px rgba(0,0,0,0.1);
      padding: 32px 24px;
      text-align: center;
      border: 1px solid var(--border);
    }
    .badge {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      background: #eff6ff;
      color: var(--primary);
      padding: 6px 14px;
      border-radius: 20px;
      font-size: 0.85rem;
      font-weight: 600;
      margin-bottom: 16px;
    }
    h1 {
      font-size: 1.45rem;
      font-weight: 800;
      color: var(--text-dark);
      margin-bottom: 8px;
    }
    p.subtitle {
      color: var(--text-muted);
      font-size: 0.92rem;
      line-height: 1.5;
      margin-bottom: 24px;
    }
    .qr-card {
      background: #f8fafc;
      border: 2px dashed #cbd5e1;
      border-radius: 20px;
      padding: 20px;
      display: inline-block;
      margin-bottom: 20px;
      position: relative;
    }
    .qr-card img {
      width: 220px;
      height: 220px;
      border-radius: 12px;
      display: block;
      background: #fff;
      padding: 8px;
    }
    .scan-hint {
      margin-top: 10px;
      font-size: 0.8rem;
      color: var(--text-muted);
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 4px;
    }
    .url-box {
      background: #f8fafc;
      border: 1px solid var(--border);
      border-radius: 14px;
      padding: 12px 16px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 10px;
      margin-bottom: 24px;
      text-align: left;
    }
    .url-text {
      font-family: monospace;
      font-size: 0.95rem;
      font-weight: 700;
      color: var(--primary);
      word-break: break-all;
    }
    .copy-btn {
      background: var(--primary);
      color: #fff;
      border: none;
      padding: 8px 14px;
      border-radius: 10px;
      font-size: 0.82rem;
      font-weight: 600;
      cursor: pointer;
      display: flex;
      align-items: center;
      gap: 5px;
      white-space: nowrap;
      transition: background 0.2s;
    }
    .copy-btn:hover {
      background: var(--primary-dark);
    }
    .steps {
      text-align: left;
      background: #f8fafc;
      border-radius: 16px;
      padding: 16px 20px;
      margin-bottom: 24px;
    }
    .step-item {
      display: flex;
      align-items: flex-start;
      gap: 12px;
      margin-bottom: 12px;
      font-size: 0.88rem;
      line-height: 1.4;
    }
    .step-item:last-child {
      margin-bottom: 0;
    }
    .step-num {
      background: var(--primary);
      color: #fff;
      width: 22px;
      height: 22px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 0.75rem;
      font-weight: 700;
      flex-shrink: 0;
      margin-top: 2px;
    }
    .actions {
      display: flex;
      gap: 10px;
    }
    .btn-main {
      flex: 1;
      background: var(--primary);
      color: #fff;
      text-decoration: none;
      padding: 12px;
      border-radius: 12px;
      font-weight: 600;
      font-size: 0.9rem;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 6px;
    }
    .btn-sub {
      flex: 1;
      background: #e2e8f0;
      color: var(--text-dark);
      text-decoration: none;
      padding: 12px;
      border-radius: 12px;
      font-weight: 600;
      font-size: 0.9rem;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 6px;
    }
    #toast {
      position: fixed;
      bottom: 24px;
      left: 50%;
      transform: translateX(-50%) translateY(100px);
      background: #1e293b;
      color: #fff;
      padding: 10px 20px;
      border-radius: 30px;
      font-size: 0.85rem;
      opacity: 0;
      transition: all 0.3s ease;
      box-shadow: 0 10px 25px rgba(0,0,0,0.2);
    }
    #toast.show {
      transform: translateX(-50%) translateY(0);
      opacity: 1;
    }
  </style>
</head>
<body>

  <div class="container">
    <div class="badge">
      <i class="ri-wifi-line"></i> Local Wi-Fi Testing Hub
    </div>
    <h1>Coba Babe di HP Anda</h1>
    <p class="subtitle">Buka dan uji aplikasi Babe langsung di smartphone Anda tanpa perlu install APK.</p>

    <!-- QR Code Box -->
    <div class="qr-card">
      <img id="qr-image" src="<?= htmlspecialchars($qr_url) ?>" alt="QR Code Babe Localhost">
      <div class="scan-hint">
        <i class="ri-qr-scan-2-line"></i> Arahkan kamera HP ke QR Code ini
      </div>
    </div>

    <!-- URL Box -->
    <div class="url-box">
      <span class="url-text" id="target-url"><?= htmlspecialchars($mobile_url) ?></span>
      <button class="copy-btn" onclick="copyUrl()">
        <i class="ri-file-copy-line"></i> Salin
      </button>
    </div>

    <!-- Langkah-langkah -->
    <div class="steps">
      <div class="step-item">
        <div class="step-num">1</div>
        <div><strong>Hubungkan ke Wi-Fi yang sama:</strong> Pastikan HP dan Laptop/PC ini terkoneksi ke satu jaringan Wi-Fi (atau hotspot HP).</div>
      </div>
      <div class="step-item">
        <div class="step-num">2</div>
        <div><strong>Scan QR atau Buka Browser di HP:</strong> Buka Chrome / Safari di HP, lalu scan QR Code di atas atau ketik alamat link di atas.</div>
      </div>
      <div class="step-item">
        <div class="step-num">3</div>
        <div><strong>Selesai!</strong> Tampilan mobile interaktif Babe langsung terbuka. Anda bisa coba login, pasang iklan, upload foto langsung dari kamera HP!</div>
      </div>
    </div>

    <!-- Tombol Aksi -->
    <div class="actions">
      <a href="index.html" class="btn-main">
        <i class="ri-computer-line"></i> Buka Babe di Laptop
      </a>
      <a href="setup.php" class="btn-sub">
        <i class="ri-database-2-line"></i> Reset Data Demo
      </a>
    </div>
  </div>

  <div id="toast">Link berhasil disalin ke clipboard!</div>

  <script>
    function copyUrl() {
      const urlText = document.getElementById('target-url').innerText;
      navigator.clipboard.writeText(urlText).then(() => {
        const toast = document.getElementById('toast');
        toast.classList.add('show');
        setTimeout(() => toast.classList.remove('show'), 2500);
      });
    }
  </script>
</body>
</html>
