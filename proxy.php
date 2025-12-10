<?php
/**
 * Simple PHP Reverse Proxy
 * Untuk shared hosting yang tidak bisa install Nginx/Apache module
 *
 * PERHATIAN: Metode ini kurang efisien dibanding Nginx/Apache,
 * tapi cocok untuk shared hosting dengan keterbatasan akses
 */

// ========== KONFIGURASI ==========
// Ganti dengan URL website yang ingin disembunyikan
define('TARGET_URL', 'https://website-asli-yang-ingin-disembunyikan.com');

// Optional: Whitelist domain yang boleh akses proxy ini
// Kosongkan array jika ingin semua domain bisa akses
$allowed_domains = [
    // 'domainanda.com',
    // 'www.domainanda.com'
];

// ========== SECURITY CHECK ==========
// Validasi domain jika whitelist diaktifkan
if (!empty($allowed_domains)) {
    $current_domain = $_SERVER['HTTP_HOST'] ?? '';
    if (!in_array($current_domain, $allowed_domains)) {
        http_response_code(403);
        die('Access Denied');
    }
}

// Hindari proxy loop
if (strpos(TARGET_URL, $_SERVER['HTTP_HOST']) !== false) {
    http_response_code(508);
    die('Loop Detected');
}

// ========== PROXY LOGIC ==========
try {
    // Ambil path dari request
    $request_path = $_SERVER['REQUEST_URI'] ?? '/';
    $target_url = rtrim(TARGET_URL, '/') . $request_path;

    // Inisialisasi cURL
    $ch = curl_init();

    // Setup cURL options
    curl_setopt($ch, CURLOPT_URL, $target_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

    // Forward headers dari client ke target
    $headers = [];
    foreach (getallheaders() as $name => $value) {
        // Skip host header
        if (strtolower($name) === 'host') {
            continue;
        }
        $headers[] = "$name: $value";
    }
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    // Forward method (GET, POST, etc)
    $method = $_SERVER['REQUEST_METHOD'];
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

    // Forward POST data jika ada
    if ($method === 'POST') {
        $post_data = file_get_contents('php://input');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
    }

    // Header callback untuk forward response headers
    curl_setopt($ch, CURLOPT_HEADERFUNCTION, function($curl, $header) {
        $len = strlen($header);
        $header = explode(':', $header, 2);

        if (count($header) < 2) {
            return $len;
        }

        $name = strtolower(trim($header[0]));

        // Skip headers yang tidak perlu di-forward
        if (!in_array($name, [
            'content-encoding',
            'transfer-encoding',
            'connection',
            'keep-alive'
        ])) {
            header(trim($header[0]) . ':' . trim($header[1]));
        }

        return $len;
    });

    // Execute request
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);

    curl_close($ch);

    // Handle error
    if ($error) {
        http_response_code(502);
        die('Proxy Error: ' . htmlspecialchars($error));
    }

    // Set response code
    http_response_code($http_code);

    // Rewrite URLs dalam response (optional tapi direkomendasikan)
    $response = rewriteUrls($response, TARGET_URL, 'https://' . $_SERVER['HTTP_HOST']);

    // Output response
    echo $response;

} catch (Exception $e) {
    http_response_code(500);
    die('Internal Server Error');
}

/**
 * Fungsi untuk rewrite URL dalam response
 * Mengganti URL absolute dengan URL domain proxy
 */
function rewriteUrls($content, $from_url, $to_url) {
    // Parse URL
    $from_parts = parse_url($from_url);
    $from_domain = $from_parts['scheme'] . '://' . $from_parts['host'];

    // Replace absolute URLs
    $content = str_replace($from_domain, $to_url, $content);

    // Replace protocol-relative URLs
    $content = str_replace('//' . $from_parts['host'], '//' . parse_url($to_url, PHP_URL_HOST), $content);

    return $content;
}

/**
 * INSTRUKSI PENGGUNAAN:
 *
 * 1. Ganti TARGET_URL dengan URL yang ingin di-mask
 * 2. Upload file ini ke root directory domain Anda
 * 3. Buat file .htaccess dengan isi:
 *
 *    RewriteEngine On
 *    RewriteCond %{REQUEST_FILENAME} !-f
 *    RewriteCond %{REQUEST_FILENAME} !-d
 *    RewriteRule ^(.*)$ proxy.php [L,QSA]
 *
 * 4. Akses domain Anda, seharusnya menampilkan konten dari TARGET_URL
 *
 * CATATAN PENTING:
 * - Pastikan cURL extension enabled di PHP
 * - Metode ini lebih lambat dibanding Nginx/Apache reverse proxy
 * - Cocok untuk low-traffic websites
 * - Tidak cocok untuk website dengan banyak resource (gambar, CSS, JS)
 * - Pertimbangkan menggunakan CDN atau Cloudflare untuk performa lebih baik
 */
