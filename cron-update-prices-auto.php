<?php
/**
 * Eryaz fiyatlarını toplu günceller (stok cron ile aynı mantık; sadece fiyat alanları).
 *
 * CLI:   php cron-update-prices-auto.php
 * Web:   https://site.com/cron-update-prices-auto.php?key=GIZLI_ANAHTAR
 *
 * Cron örneği (günde bir):
 *   15 3 * * * /usr/bin/php /home/kullanici/public_html/cron-update-prices-auto.php
 */

$secretKey = 'batuhan'; // cron-update-prices-auto.php ve fiyat-guncelle-tek-tik.php ile aynı olmalı
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
require_once __DIR__ . '/eryaz-price-update-worker.php';

date_default_timezone_set('Europe/Istanbul');

$startTime = microtime(true);
$log = [];

function priceCronLog($message) {
    global $log;
    $timestamp = date('Y-m-d H:i:s');
    $line = "[$timestamp] $message";
    $log[] = $line;
    if (php_sapi_name() === 'cli') {
        echo $line . "\n";
    }
}

priceCronLog('=== Eryaz fiyat güncelleme başladı ===');

try {
    $eryazAPI = new EryazAPI();
    $stats = eryaz_run_full_price_update($db, $eryazAPI, 'priceCronLog');

    if (empty($stats['success'])) {
        priceCronLog('HATA: ' . ($stats['error'] ?? 'Bilinmeyen'));
        $logFile = __DIR__ . '/logs/price-update-' . date('Y-m-d') . '.log';
        $logDir = dirname($logFile);
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }
        @file_put_contents($logFile, implode("\n", $log) . "\n", FILE_APPEND);

        if ($webAccess) {
            echo json_encode([
                'success' => false,
                'error' => $stats['error'] ?? 'Güncelleme başarısız',
                'stats' => $stats,
            ], JSON_UNESCAPED_UNICODE);
        }
        exit(1);
    }

    $endTime = microtime(true);
    $executionTime = round($endTime - $startTime, 2);

    priceCronLog('Güncellenen fiyat (satır): ' . (int)$stats['price_updated']);
    priceCronLog('Sitede olmayan stok kodu: ' . (int)$stats['not_in_db']);
    priceCronLog('Atlanan: ' . (int)$stats['skipped']);
    priceCronLog('Hata sayısı: ' . (int)$stats['error_count']);
    priceCronLog('Süre: ' . $executionTime . ' sn');
    priceCronLog('=== Bitti ===');

    $logFile = __DIR__ . '/logs/price-update-' . date('Y-m-d') . '.log';
    $logDir = dirname($logFile);
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0755, true);
    }
    @file_put_contents($logFile, implode("\n", $log) . "\n", FILE_APPEND);

    if ($webAccess) {
        echo json_encode([
            'success' => true,
            'price_updated' => (int)$stats['price_updated'],
            'not_in_db' => (int)$stats['not_in_db'],
            'skipped' => (int)$stats['skipped'],
            'errors' => $stats['errors'],
            'error_count' => (int)$stats['error_count'],
            'executionTime' => $executionTime,
            'timestamp' => date('Y-m-d H:i:s'),
        ], JSON_UNESCAPED_UNICODE);
    }
} catch (Exception $e) {
    priceCronLog('KRİTİK: ' . $e->getMessage());
    $logFile = __DIR__ . '/logs/price-update-' . date('Y-m-d') . '.log';
    $logDir = dirname($logFile);
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0755, true);
    }
    @file_put_contents($logFile, implode("\n", $log) . "\n", FILE_APPEND);

    if ($webAccess) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
    exit(1);
}
