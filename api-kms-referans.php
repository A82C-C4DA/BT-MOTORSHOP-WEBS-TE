<?php
/**
 * KMotorShop OEM referans sync (ucretsiz alternatif).
 * Panel butonu sunucudan dener; KMotorShop hosting IP'sini engeller (403).
 * Gercek cekme: kmotorshop-yerel-referans-cek.ps1 (yerel bilgisayar).
 */
require_once __DIR__ . '/panel/fonksiyon.php';
require_once __DIR__ . '/panel/kms-lib.php';

header('Content-Type: application/json; charset=utf-8');

$isAjax = isset($_GET['ajax']) || isset($_POST['ajax'])
    || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');

if (!$isAjax) {
    echo json_encode(['success' => false, 'error' => 'ajax gerekli']);
    exit;
}

if (!isset($_SESSION['admin']['login'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Yetki yok']);
    exit;
}

$GLOBALS['kms_last_http'] = 0;
$GLOBALS['kms_last_err'] = '';
$siteUrl = '';
if (!empty($_SERVER['HTTP_HOST'])) {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $siteUrl = $scheme . '://' . $_SERVER['HTTP_HOST'];
}

function kms_json_error($message, array $extra = []) {
    echo json_encode(array_merge([
        'success' => false,
        'error' => $message,
    ], $extra), JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $action = $_GET['action'] ?? $_POST['action'] ?? 'syncProduct';
    $productId = (int)($_GET['urun_id'] ?? $_POST['urun_id'] ?? 0);

    if ($action === 'localCommand') {
        if ($productId <= 0) {
            throw new Exception('urun_id gerekli');
        }
        $hasEryaz = $db->query("SHOW COLUMNS FROM urun LIKE 'eryaz_stok_kodu'")->fetch() !== false;
        $fields = $hasEryaz ? 'id, stok_kodu, eryaz_stok_kodu' : 'id, stok_kodu';
        $stmt = $db->prepare("SELECT {$fields} FROM urun WHERE id = ? LIMIT 1");
        $stmt->execute([$productId]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$product) {
            throw new Exception('Urun bulunamadi');
        }
        $rawCode = !empty($product['eryaz_stok_kodu']) ? (string)$product['eryaz_stok_kodu'] : (string)$product['stok_kodu'];
        $searchCode = kms_clean_code($rawCode);
        if ($searchCode === '') {
            throw new Exception('Stok kodu bos');
        }
        $cmd = kms_local_referans_command($productId, $searchCode, $siteUrl);
        echo json_encode([
            'success' => true,
            'product_id' => $productId,
            'stok_kodu' => $searchCode,
            'local_command' => $cmd,
            'note' => 'Panel sunucusundan KMotorShop acilmaz (403). Komutu kendi bilgisayarinizda PowerShell ile calistirin.',
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($action !== 'syncProduct') {
        throw new Exception('Gecersiz action');
    }
    if ($productId <= 0) {
        throw new Exception('urun_id gerekli');
    }

    $hasEryaz = $db->query("SHOW COLUMNS FROM urun LIKE 'eryaz_stok_kodu'")->fetch() !== false;
    $fields = $hasEryaz ? 'id, stok_kodu, eryaz_stok_kodu, baslik' : 'id, stok_kodu, baslik';
    $stmt = $db->prepare("SELECT {$fields} FROM urun WHERE id = ? LIMIT 1");
    $stmt->execute([$productId]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$product) {
        throw new Exception('Urun bulunamadi');
    }

    $rawCode = !empty($product['eryaz_stok_kodu']) ? (string)$product['eryaz_stok_kodu'] : (string)$product['stok_kodu'];
    $searchCode = kms_clean_code($rawCode);
    if ($searchCode === '') {
        throw new Exception('Stok kodu bos');
    }

    $localCmd = kms_local_referans_command($productId, $searchCode, $siteUrl);
    $blockedExtra = [
        'use_local_script' => true,
        'local_command' => $localCmd,
        'stok_kodu' => $searchCode,
        'product_id' => $productId,
    ];

    // OEM referanslar + gorsel ayni anda kmotorshop.com'dan cekilir.
    $referansList = kms_find_referans_for_code($searchCode, 'BOSCH');
    $imageUrl = kms_find_image_url_for_code($searchCode);

    if (empty($referansList) && $imageUrl === '') {
        if (kms_is_server_blocked_error()) {
            kms_json_error(
                'KMotorShop panel sunucusunu engelliyor (HTTP 403). Cekme islemi yerel bilgisayarinizdan yapilmali.',
                $blockedExtra
            );
        }
        $diag = 'HTTP ' . (int)$GLOBALS['kms_last_http'];
        if (!empty($GLOBALS['kms_last_err'])) {
            $diag .= ' - ' . $GLOBALS['kms_last_err'];
        }
        kms_json_error('KMotorShop kaydi bulunamadi: ' . $searchCode . ' (' . $diag . ')', $blockedExtra);
    }

    $added = !empty($referansList) ? kms_save_referans_list($db, $productId, $referansList) : 0;
    $imgResult = $imageUrl !== '' ? kms_save_image_for_product($db, $productId, $imageUrl, true) : ['added' => 0, 'img' => '', 'url' => ''];

    echo json_encode([
        'success' => true,
        'product_id' => $productId,
        'stok_kodu' => $searchCode,
        'referans_added' => $added,
        'referans_found' => count($referansList),
        'image_added' => (int)($imgResult['added'] ?? 0),
        'image_url' => (string)($imgResult['url'] ?? ''),
        'image_note' => isset($imgResult['skipped']) ? $imgResult['skipped'] : (isset($imgResult['error']) ? $imgResult['error'] : ''),
        'referans_sample' => array_slice($referansList, 0, 5),
    ], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
}
