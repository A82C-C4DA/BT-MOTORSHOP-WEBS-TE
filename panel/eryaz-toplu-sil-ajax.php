<?php
/**
 * Panel içinden (Eryaz ürünler sayfası) toplu sil — JSON API.
 * Oturum: eryaz_panel_giris_kontrol.php
 *
 * POST JSON: { "confirm": "SIL" }
 */
session_start();

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/eryaz-panel-giris-kontrol.php';

if (!eryaz_panel_is_logged_in()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Panel girişi gerekli. Önce yönetim paneline giriş yapın.'], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Sadece POST'], JSON_UNESCAPED_UNICODE);
    exit;
}

$raw = file_get_contents('php://input');
$body = json_decode($raw, true);
if (!is_array($body)) {
    $body = [];
}

$confirm = isset($body['confirm']) ? (string)$body['confirm'] : '';
if (trim($confirm) !== 'SIL') {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Onay için "confirm":"SIL" gönderin.'], JSON_UNESCAPED_UNICODE);
    exit;
}

require_once __DIR__ . '/db-ayar.php';
require_once dirname(__DIR__) . '/api-eryaz.php';
require_once dirname(__DIR__) . '/eryaz-delete-products-worker.php';

date_default_timezone_set('Europe/Istanbul');
$t0 = microtime(true);

try {
    $eryazAPI = new EryazAPI();
    $stats = eryaz_delete_products_matching_current_api($db, $eryazAPI);
    $stats['executionTime'] = round(microtime(true) - $t0, 2);
    $stats['timestamp'] = date('Y-m-d H:i:s');
    echo json_encode($stats, JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
