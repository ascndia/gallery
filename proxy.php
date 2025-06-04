<?php
$targetUrl = $_GET['url'] ?? '';

// // Setup cache system
$cacheDir = __DIR__ . '/cache';
if (!is_dir($cacheDir)) {
    mkdir($cacheDir, 0755, true);
}

// Generate cache filename using hash
$urlHash = md5($targetUrl);
$cacheFile = $cacheDir . '/img_' . $urlHash;
$cacheTime = 86400 * 30; // 30 days cache

// Serve from cache if available
if (file_exists($cacheFile) && (time() - filemtime($cacheFile) < $cacheTime)) {
    $mime = function_exists('mime_content_type')
        ? mime_content_type($cacheFile)
        : 'image/jpeg';

    header("Content-Type: $mime");
    header("Cache-Control: public, max-age=2592000"); // 30 days browser cache
    header("Expires: " . gmdate('D, d M Y H:i:s \G\M\T', time() + 2592000));
    header("Pragma: cache");
    readfile($cacheFile);
    exit;
}

// Validate URL
if (empty($targetUrl)) {
    http_response_code(400);
    exit('Missing URL');
}

// Auto-add http:// if missing
if (!preg_match('/^https?:\/\//i', $targetUrl)) {
    $targetUrl = 'http://' . ltrim($targetUrl, '/');
}

$parsed = parse_url($targetUrl);
if (!$parsed || !isset($parsed['host'])) {
    http_response_code(400);
    exit('Invalid URL');
}

$host = $parsed['host'];
$scheme = $parsed['scheme'] ?? 'http';

// Configure cURL with optimizations
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $targetUrl,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_MAXREDIRS => 2,
    CURLOPT_ENCODING => 'gzip,deflate',
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_FORBID_REUSE => false,
    CURLOPT_FRESH_CONNECT => false,
    CURLOPT_TCP_KEEPALIVE => true,
    CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/125.0.0.0 Safari/537.36',
    CURLOPT_HTTPHEADER => [
        "Referer: $scheme://$host/",
        'Accept: image/webp,image/*,*/*;q=0.8'
    ]
]);

$response = curl_exec($ch);
$errNo = curl_errno($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
curl_close($ch);

// Handle errors
if ($errNo || $httpCode >= 400 || empty($response)) {
    http_response_code(502);
    exit('Failed to fetch image');
}

// Validate content type
$allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'];
$mime = strtok($contentType, ';');
if (!in_array($mime, $allowedTypes)) {
    http_response_code(415);
    exit('Unsupported image type: ' . htmlspecialchars($contentType));
}

// Save to cache
file_put_contents($cacheFile, $response);

// Output with caching headers
header("Content-Type: $contentType");
header("Cache-Control: public, max-age=2592000"); // 30 days
header("Expires: " . gmdate('D, d M Y H:i:s \G\M\T', time() + 2592000));
header("Pragma: cache");
header("X-Image-Cache: MISS"); // For debugging

echo $response;
