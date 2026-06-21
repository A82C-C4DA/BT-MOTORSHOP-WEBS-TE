<?php
/**
 * KMotorShop alici (receiver) endpoint.
 *
 * Yerel bilgisayardan cekilen gorsel ve OEM referanslari bu endpoint ile siteye kaydedilir.
 *   - action=list          : Gorseli olmayan Bosch urunleri
 *   - action=list_referans : Referansi olmayan Bosch urunleri
 *   - action=save          : Gorsel yukle -> urun_img
 *   - action=save_referans : JSON referans listesi -> urun_referans
 *   - action=delete        : Urun gorsellerini sil (geri alma)
 *
 * GUVENLIK: Sabit token ile korunur. Is bitince silin.
 */
require_once __DIR__ . '/fonksiyon.php';
require_once __DIR__ . '/kms-lib.php';

$KMS_TOKEN = 'btm-kms-2026';

header('Content-Type: application/json; charset=utf-8');

$token = isset($_GET['token']) ? $_GET['token'] : (isset($_POST['token']) ? $_POST['token'] : '');
if (!hash_equals($KMS_TOKEN, (string)$token)) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'token']);
    exit;
}

$action = isset($_GET['action']) ? $_GET['action'] : (isset($_POST['action']) ? $_POST['action'] : '');

function kms_receiver_product_batch($db, $limit, $lastId, $onlyNoImage, $onlyNoReferans) {
    $hasEryaz = $db->query("SHOW COLUMNS FROM urun LIKE 'eryaz_stok_kodu'")->fetch() !== false;
    $fields = $hasEryaz ? 'id, stok_kodu, eryaz_stok_kodu' : 'id, stok_kodu';
    $boschWhere = kms_bosch_product_where_sql($hasEryaz);

    $stmt = $db->prepare("SELECT {$fields} FROM urun WHERE id > ? AND {$boschWhere} ORDER BY id ASC LIMIT " . (int)$limit);
    $stmt->execute([$lastId]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $imgCheck = $db->prepare('SELECT id FROM urun_img WHERE urun_id = ? LIMIT 1');
    $refCheck = $db->prepare('SELECT id FROM urun_referans WHERE urun_id = ? LIMIT 1');

    $out = [];
    $nextLastId = $lastId;
    foreach ($rows as $r) {
        $nextLastId = (int)$r['id'];
        if ($onlyNoImage) {
            $imgCheck->execute([(int)$r['id']]);
            if ($imgCheck->fetch()) {
                continue;
            }
        }
        if ($onlyNoReferans) {
            try {
                $refCheck->execute([(int)$r['id']]);
                if ($refCheck->fetch()) {
                    continue;
                }
            } catch (Exception $e) {
                // tablo yoksa devam
            }
        }
        $raw = (!empty($r['eryaz_stok_kodu'])) ? $r['eryaz_stok_kodu'] : $r['stok_kodu'];
        $clean = kms_clean_code($raw);
        if ($clean === '') {
            continue;
        }
        $out[] = ['id' => (int)$r['id'], 'code' => $clean];
    }

    return [
        'products' => $out,
        'returned' => count($rows),
        'next_last_id' => $nextLastId,
        'done' => count($rows) < $limit,
    ];
}

try {
    if ($action === 'list' || $action === 'list_referans') {
        $limit = isset($_GET['limit']) ? max(1, min(200, (int)$_GET['limit'])) : 50;
        $lastId = isset($_GET['last_id']) ? max(0, (int)$_GET['last_id']) : 0;
        $onlyNoImage = ($action === 'list') && (!isset($_GET['only_no_image']) || $_GET['only_no_image'] === '1');
        $onlyNoReferans = ($action === 'list_referans') && (!isset($_GET['only_no_referans']) || $_GET['only_no_referans'] === '1');

        $batch = kms_receiver_product_batch($db, $limit, $lastId, $onlyNoImage, $onlyNoReferans);
        echo json_encode(['ok' => true] + $batch);
        exit;
    }

    if ($action === 'delete') {
        $urunId = isset($_GET['urun_id']) ? (int)$_GET['urun_id'] : (isset($_POST['urun_id']) ? (int)$_POST['urun_id'] : 0);
        if ($urunId <= 0) {
            echo json_encode(['ok' => false, 'error' => 'urun_id']);
            exit;
        }
        $sel = $db->prepare('SELECT img FROM urun_img WHERE urun_id = ?');
        $sel->execute([$urunId]);
        $imgs = $sel->fetchAll(PDO::FETCH_COLUMN);
        $deleted = 0;
        foreach ($imgs as $img) {
            $img = trim((string)$img);
            if ($img !== '' && !preg_match('#^https?://#i', $img) && !preg_match('#[/\\\\]#', $img)) {
                $fp = __DIR__ . '/../upload/' . basename($img);
                if (is_file($fp)) {
                    @unlink($fp);
                }
            }
            $deleted++;
        }
        $db->prepare('DELETE FROM urun_img WHERE urun_id = ?')->execute([$urunId]);
        echo json_encode(['ok' => true, 'deleted' => $deleted]);
        exit;
    }

    if ($action === 'save') {
        $urunId = isset($_POST['urun_id']) ? (int)$_POST['urun_id'] : 0;
        if ($urunId <= 0 || empty($_FILES['gorsel']['tmp_name']) || !is_uploaded_file($_FILES['gorsel']['tmp_name'])) {
            echo json_encode(['ok' => false, 'error' => 'eksik_veri']);
            exit;
        }

        // Yalnizca urunde hic gorsel yoksa ekle (mukerrer kapak onleme).
        if (isset($_POST['only_if_no_image']) && $_POST['only_if_no_image'] === '1') {
            $imgEx = $db->prepare('SELECT id FROM urun_img WHERE urun_id = ? LIMIT 1');
            $imgEx->execute([$urunId]);
            if ($imgEx->fetch()) {
                echo json_encode(['ok' => true, 'img' => '', 'skipped' => 'mevcut_gorsel']);
                exit;
            }
        }

        $ext = strtolower(pathinfo($_FILES['gorsel']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
            $ext = 'jpg';
        }

        $upload = media_upload_product_image($_FILES['gorsel']['tmp_name'], $ext);
        if (empty($upload['ok']) || empty($upload['value'])) {
            echo json_encode(['ok' => false, 'error' => 'kaydedilemedi']);
            exit;
        }

        $ins = $db->prepare('INSERT INTO urun_img SET urun_id = ?, img = ?');
        $ins->execute([$urunId, (string)$upload['value']]);

        echo json_encode(['ok' => true, 'img' => (string)$upload['value']]);
        exit;
    }

    if ($action === 'save_referans') {
        $rawBody = file_get_contents('php://input');
        $payload = json_decode($rawBody, true);
        if (!is_array($payload)) {
            $payload = $_POST;
        }

        $urunId = (int)($payload['urun_id'] ?? 0);
        $referans = $payload['referans'] ?? $payload['referans_list'] ?? [];
        if ($urunId <= 0 || !is_array($referans) || empty($referans)) {
            echo json_encode(['ok' => false, 'error' => 'eksik_veri']);
            exit;
        }

        $added = kms_save_referans_list($db, $urunId, $referans);
        echo json_encode([
            'ok' => true,
            'added' => $added,
            'total_sent' => count($referans),
        ]);
        exit;
    }

    echo json_encode(['ok' => false, 'error' => 'bilinmeyen_action']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'exception', 'message' => $e->getMessage()]);
}
