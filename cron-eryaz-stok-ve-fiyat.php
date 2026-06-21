<?php
/**
 * Tek cron: Eryaz stokları + fiyatları sırayla günceller.
 *
 * Önerilen sıklık:
 *   - Her 10–15 dakika (yoğun mağaza), veya
 *   - Her saat (orta yoğunluk)
 *
 * cPanel örnek (15 dakikada bir):
 *   */15 * * * * /usr/bin/php /home/KULLANICI/public_html/cron-eryaz-stok-ve-fiyat.php
 *
 * Web ile tetikleme (yedek / test):
 *   https://site.com/cron-eryaz-stok-ve-fiyat.php?key=ANAHTARINIZ
 *
 * Eski ayrı cron'lar (isteğe bağlı kapatılabilir):
 *   cron-update-stocks-auto.php  → sadece stok
 *   cron-update-prices-auto.php    → sadece fiyat
 */

$secretKey = 'batuhan'; // Mutlaka güçlü bir değerle değiştirin
$webAccess = (php_sapi_name() !== 'cli');

if ($webAccess) {
    $providedKey = isset($_GET['key']) ? $_GET['key'] : '';
    if ($providedKey !== $secretKey) {
        http_response_code(403);
        die('Unauthorized access. Please provide a valid key.');
    }
    header('Content-Type: application/json; charset=utf-8');
}

require_once __DIR__ . '/panel/db-ayar.php';
require_once __DIR__ . '/api-eryaz.php';
require_once __DIR__ . '/eryaz-stock-update-worker.php';
require_once __DIR__ . '/eryaz-price-update-worker.php';

date_default_timezone_set('Europe/Istanbul');

$globalStart = microtime(true);
$log = [];

function eryazCombinedCronLog($message) {
    global $log;
    $timestamp = date('Y-m-d H:i:s');
    $line = "[$timestamp] $message";
    $log[] = $line;
    if (php_sapi_name() === 'cli') {
        echo $line . "\n";
    }
}

eryazCombinedCronLog('=== Eryaz birleşik cron (stok + fiyat) başladı ===');

$eryazAPI = new EryazAPI();

// 1) Stoklar (önce — sipariş tarafı için güncel durum)
eryazCombinedCronLog('--- Adım 1: Stok güncelleme ---');
$tStock = microtime(true);
$stockStats = eryaz_run_full_stock_update($db, $eryazAPI, 'eryazCombinedCronLog');
$stockStats['executionTime'] = round(microtime(true) - $tStock, 2);

if (!empty($stockStats['success'])) {
    eryazCombinedCronLog('Stok tamam. Güncellenen: ' . (int)$stockStats['updated'] . ', Atlanan (manuel): ' . (int)$stockStats['skipped'] . ', API\'de bulunamayan: ' . (int)$stockStats['notFound'] . ', Hata: ' . (int)$stockStats['errors'] . ' (' . $stockStats['executionTime'] . ' sn)');
} else {
    eryazCombinedCronLog('Stok HATA: ' . ($stockStats['error'] ?? 'bilinmeyen'));
}

// 2) Fiyatlar
eryazCombinedCronLog('--- Adım 2: Fiyat güncelleme ---');
$tPrice = microtime(true);
$priceStats = eryaz_run_full_price_update($db, $eryazAPI, 'eryazCombinedCronLog');
$priceStats['executionTime'] = round(microtime(true) - $tPrice, 2);

if (!empty($priceStats['success'])) {
    eryazCombinedCronLog('Fiyat tamam. Güncellenen: ' . (int)$priceStats['price_updated'] . ', Sitede yok: ' . (int)$priceStats['not_in_db'] . ', Atlanan: ' . (int)$priceStats['skipped'] . ' (' . $priceStats['executionTime'] . ' sn)');
} else {
    eryazCombinedCronLog('Fiyat HATA: ' . ($priceStats['error'] ?? 'bilinmeyen'));
}

$totalTime = round(microtime(true) - $globalStart, 2);
eryazCombinedCronLog('Toplam süre: ' . $totalTime . ' sn');
eryazCombinedCronLog('=== Bitti ===');

$logFile = __DIR__ . '/logs/eryaz-cron-combined-' . date('Y-m-d') . '.log';
$logDir = dirname($logFile);
if (!is_dir($logDir)) {
    @mkdir($logDir, 0755, true);
}
@file_put_contents($logFile, implode("\n", $log) . "\n", FILE_APPEND);

$overallSuccess = !empty($stockStats['success']) && !empty($priceStats['success']);

if ($webAccess) {
    echo json_encode([
        'success' => $overallSuccess,
        'totalExecutionTime' => $totalTime,
        'stock' => $stockStats,
        'price' => $priceStats,
        'timestamp' => date('Y-m-d H:i:s'),
    ], JSON_UNESCAPED_UNICODE);
}

exit($overallSuccess ? 0 : 1);
