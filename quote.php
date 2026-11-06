<?php
/**
 * quote.php - Real-Time IDX/BEI Proxy via Yahoo Finance
 * Khusus cPanel Jagoan Hosting (Subdomain: dividen.kerjaremot.com)
 */

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$ticker = isset($_GET['ticker']) ? strtoupper(trim($_GET['ticker'])) : '';
$ticker = str_replace('.JK', '', $ticker);

if (empty($ticker)) {
    http_response_code(400);
    echo json_encode([
        'error' => 'Parameter ticker diperlukan (contoh: quote.php?ticker=BBRI)',
        'status' => 'error'
    ]);
    exit;
}

// ── Simple Cache (30 detik) untuk menghemat bandwidth & mempercepat response ──
$cacheDir = __DIR__ . '/.cache';
if (!is_dir($cacheDir)) {
    @mkdir($cacheDir, 0755, true);
}
$cleanTicker = preg_replace('/[^A-Z0-9]/', '', $ticker);
$cacheFile = $cacheDir . '/quote_' . $cleanTicker . '.json';
$cacheDuration = 30; // 30 detik

if (file_exists($cacheFile) && (time() - filemtime($cacheFile) < $cacheDuration)) {
    $cachedData = @file_get_contents($cacheFile);
    if (!empty($cachedData)) {
        echo $cachedData;
        exit;
    }
}

// ── Ambil Data dari Yahoo Finance v8 Chart API ──
$queryTicker = urlencode($ticker . '.JK');
$url = "https://query1.finance.yahoo.com/v8/finance/chart/{$queryTicker}?interval=1d&range=1d";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 6);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 4);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
    'Accept: application/json'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlErr = curl_error($ch);
curl_close($ch);

if ($response === false || $httpCode !== 200) {
    http_response_code(502);
    echo json_encode([
        'error' => $curlErr ? $curlErr : 'Gagal menghubungi Yahoo Finance (HTTP ' . $httpCode . ')',
        'status' => 'error',
        'ticker' => $ticker
    ]);
    exit;
}

$data = json_decode($response, true);
$result = $data['chart']['result'][0] ?? null;

if (!$result || !isset($result['meta'])) {
    http_response_code(404);
    echo json_encode([
        'error' => 'Data emiten tidak ditemukan di BEI/Yahoo Finance',
        'status' => 'error',
        'ticker' => $ticker
    ]);
    exit;
}

$meta = $result['meta'];
$price = $meta['regularMarketPrice'] ?? $meta['chartPreviousClose'] ?? 0;
$prev = $meta['chartPreviousClose'] ?? $meta['previousClose'] ?? $price;
$chgPct = ($prev > 0) ? (($price - $prev) / $prev * 100) : 0;
$volume = $meta['regularMarketVolume'] ?? 0;

$payload = [
    'ticker' => $ticker,
    'price' => (float)$price,
    'previousClose' => (float)$prev,
    'changePercent' => round((float)$chgPct, 2),
    'volume' => (int)$volume,
    'timestamp' => time(),
    'source' => 'Yahoo Finance Real-Time (via cPanel PHP Proxy)',
    'status' => 'ok'
];

$jsonPayload = json_encode($payload);
@file_put_contents($cacheFile, $jsonPayload);

echo $jsonPayload;

