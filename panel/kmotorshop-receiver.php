<?php
/**
 * KMotorShop görsel alıcı (receiver) endpoint.
 *
 * KMotorShop, hosting sunucusunun IP'sini (datacenter) Cloudflare ile engelliyor.
 * Bu yüzden görsel ÇEKME işi engellenmeyen bir IP'den (ör. yerel bilgisayar) yapılır;
 * bu dosya sadece "alıcı" görevi görür:
 *   - action=list : Görseli olmayan Bosch grubu ürünlerin (id + temiz kod) listesini verir.
 *   - action=save : Gönderilen görsel dosyasını upload/ klasörüne kaydeder ve urun_img'e ekler.
 *
 * GÜVENLİK: Sabit bir token ile korunur. İş bitince bu dosyayı sunucudan SİLİN.
 */
require_once __DIR__ . '/fonksiyon.php';

// Bu token'i isterseniz degistirin; yerel calistirici ile ayni olmali.
$KMS_TOKEN = 'btm-kms-2026';

header('Content-Type: application/json; charset=utf-8');

$token = isset($_GET['token']) ? $_GET['token'] : (isset($_POST['token']) ? $_POST['token'] : '');
if (!hash_equals($KMS_TOKEN, (string)$token)) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'token']);
    exit;
}

$action = isset($_GET['action']) ? $_GET['action'] : (isset($_POST['action']) ? $_POST['action'] : '');

function kms_rcv_clean_code($code) {
    return preg_replace('/^(30-|31-|32-|3e-?)/i', '', trim((string)$code));
}

try {
    if ($action === 'list') {
        $limit = isset($_GET['limit']) ? max(1, min(200, (int)$_GET['limit'])) : 50;
        $lastId = isset($_GET['last_id']) ? max(0, (int)$_GET['last_id']) : 0;
        $onlyNoImage = !isset($_GET['only_no_image']) || $_GET['only_no_image'] === '1';

        $hasEryaz = $db->query("SHOW COLUMNS FROM urun LIKE 'eryaz_stok_kodu'")->fetch() !== false;
        $fields = $hasEryaz ? 'id, stok_kodu, eryaz_stok_kodu' : 'id, stok_kodu';

        $boschWhere = "(LOWER(stok_kodu) LIKE '30-%' OR LOWER(stok_kodu) LIKE '31-%' OR LOWER(stok_kodu) LIKE '32-%' OR LOWER(stok_kodu) LIKE '3e%'";
        if ($hasEryaz) {
            $boschWhere .= " OR LOWER(eryaz_stok_kodu) LIKE '30-%' OR LOWER(eryaz_stok_kodu) LIKE '31-%' OR LOWER(eryaz_stok_kodu) LIKE '32-%' OR LOWER(eryaz_stok_kodu) LIKE '3e%'";
        }
        $boschWhere .= ")";

        $stmt = $db->prepare("SELECT {$fields} FROM urun WHERE id > ? AND {$boschWhere} ORDER BY id ASC LIMIT " . (int)$limit);
        $stmt->execute([$lastId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $imgCheck = $db->prepare('SELECT id FROM urun_img WHERE urun_id = ? LIMIT 1');

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
            $raw = (!empty($r['eryaz_stok_kodu'])) ? $r['eryaz_stok_kodu'] : $r['stok_kodu'];
            $clean = kms_rcv_clean_code($raw);
            if ($clean === '') {
                continue;
            }
            $out[] = ['id' => (int)$r['id'], 'code' => $clean];
        }

        echo json_encode([
            'ok' => true,
            'products' => $out,
            'returned' => count($rows),
            'next_last_id' => $nextLastId,
            'done' => count($rows) < $limit,
        ]);
        exit;
    }

    if ($action === 'delete') {
        // Belirtilen urunun TUM gorsellerini sil (dosya + db). Sadece bu oturumda
        // eklediklerimizi geri almak icin kullanilir (urun zaten gorselsizdi).
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
                if (is_file($fp)) { @unlink($fp); }
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

    echo json_encode(['ok' => false, 'error' => 'bilinmeyen_action']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'exception', 'message' => $e->getMessage()]);
}
