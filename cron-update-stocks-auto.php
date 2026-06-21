<?php
/**
 * Otomatik Stok Güncelleme Scripti
 * Eryaz'dan anlık stok verilerini çekerek veritabanındaki ürünlerin stok durumlarını günceller
 *
 * Kullanım:
 * 1. Cron Job ile otomatik çalıştırma (her 5-10 dakikada bir):
 *    */5 * * * * /usr/bin/php /home/username/public_html/cron-update-stocks-auto.php
 *
 * 2. Manuel: php cron-update-stocks-auto.php
 *
 * 3. Web: https://yoursite.com/cron-update-stocks-auto.php?key=YOUR_SECRET_KEY
 *
 * Stok + fiyat birlikte için: cron-eryaz-stok-ve-fiyat.php
 */

$secretKey = 'batuhan'; // Bu anahtarı değiştirin!
$webAccess = (php_sapi_name() !== 'cli');

if ($webAccess) {
    $providedKey = isset($_GET['key']) ? $_GET['key'] : '';
    if ($providedKey !== $secretKey) {
        http_response_code(403);
        die('Unauthorized access. Please provide a valid key.');
    }
    header('Content-Type: application/json');
}

require_once __DIR__ . '/panel/db-ayar.php';
require_once __DIR__ . '/api-eryaz.php';
require_once __DIR__ . '/eryaz-stock-update-worker.php';

date_default_timezone_set('Europe/Istanbul');

$startTime = microtime(true);
$log = [];

function logMessage($message) {
    global $log;
    $timestamp = date('Y-m-d H:i:s');
    $log[] = "[$timestamp] $message";
    if (php_sapi_name() === 'cli') {
        echo "[$timestamp] $message\n";
    }
}

logMessage('=== Otomatik Stok Güncelleme Başlatıldı ===');

try {
    logMessage('Eryaz API\'den ürünler çekiliyor...');
    $eryazAPI = new EryazAPI();
    $stats = eryaz_run_full_stock_update($db, $eryazAPI, 'logMessage');

    if (empty($stats['success'])) {
        logMessage('HATA: ' . ($stats['error'] ?? 'Bilinmeyen'));
        $logFile = __DIR__ . '/logs/stock-update-' . date('Y-m-d') . '.log';
        $logDir = dirname($logFile);
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }
        @file_put_contents($logFile, implode("\n", $log) . "\n", FILE_APPEND);

        if ($webAccess) {
            echo json_encode(['success' => false, 'error' => $stats['error'] ?? 'Hata']);
        }
        exit(1);
    }

    logMessage('Güncellenen: ' . (int)$stats['updated']);
    logMessage('Manuel stok (sadece depo bilgileri güncellendi): ' . (int)$stats['skipped']);
    logMessage('Bulunamayan: ' . (int)$stats['notFound']);
    logMessage('Hatalar: ' . (int)$stats['errors']);

    $endTime = microtime(true);
    $executionTime = round($endTime - $startTime, 2);
    logMessage('Toplam süre: ' . $executionTime . ' saniye');
    logMessage('=== İşlem Tamamlandı ===');

    $logFile = __DIR__ . '/logs/stock-update-' . date('Y-m-d') . '.log';
    $logDir = dirname($logFile);
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0755, true);
    }
    @file_put_contents($logFile, implode("\n", $log) . "\n", FILE_APPEND);

    if ($webAccess) {
        echo json_encode([
            'success' => true,
            'updated' => (int)$stats['updated'],
            'skipped' => (int)$stats['skipped'],
            'notFound' => (int)$stats['notFound'],
            'errors' => (int)$stats['errors'],
            'executionTime' => $executionTime,
            'timestamp' => date('Y-m-d H:i:s'),
        ]);
    }
} catch (Exception $e) {
    logMessage('KRİTİK HATA: ' . $e->getMessage());
    if ($webAccess) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit(1);
}
