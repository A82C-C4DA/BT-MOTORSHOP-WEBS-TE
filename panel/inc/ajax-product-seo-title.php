<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../fonksiyon.php';

if (!isset($_SESSION['admin']['login'])) {
    echo json_encode(['success' => false, 'error' => 'Oturum bulunamadi']);
    exit;
}

$action = isset($_POST['ajax_seo_action']) ? trim((string)$_POST['ajax_seo_action']) : 'optimize_one';

if ($action === 'optimize_one') {
    $baslik = isset($_POST['baslik']) ? trim((string)$_POST['baslik']) : '';
    $stokKodu = isset($_POST['stok_kodu']) ? trim((string)$_POST['stok_kodu']) : '';
    $referans = isset($_POST['referans']) ? trim((string)$_POST['referans']) : '';

    if ($referans === '' && isset($_POST['urun_id'])) {
        $uid = (int)$_POST['urun_id'];
        if ($uid > 0) {
            try {
                $refQ = $db->prepare('SELECT referans_no FROM urun_referans WHERE urun_id = ? ORDER BY sira ASC, id ASC LIMIT 1');
                $refQ->execute([$uid]);
                $refRow = $refQ->fetch(PDO::FETCH_ASSOC);
                if ($refRow && !empty($refRow['referans_no'])) {
                    $referans = trim((string)$refRow['referans_no']);
                }
            } catch (Exception $e) {
            }
        }
    }

    $result = seo_optimize_product_title($db, $baslik, $stokKodu, $referans);
    echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if ($action === 'bulk_optimize') {
    $limit = isset($_POST['limit']) ? max(1, min(200, (int)$_POST['limit'])) : 50;
    $onlyEmpty = isset($_POST['only_unoptimized']) && $_POST['only_unoptimized'] === '1';

    $rows = $db->query('SELECT id, baslik, stok_kodu FROM urun ORDER BY id DESC', PDO::FETCH_ASSOC)->fetchAll(PDO::FETCH_ASSOC);
    $updated = 0;
    $skipped = 0;
    $failed = 0;
    $details = [];

    foreach ($rows as $row) {
        if ($updated >= $limit) {
            break;
        }

        $id = (int)$row['id'];
        $baslik = trim((string)$row['baslik']);
        if ($baslik === '') {
            $skipped++;
            continue;
        }

        if ($onlyEmpty) {
            // Basit sezgi: iki nokta ust uste ve slash iceren eski formatlari optimize et
            if (strpos($baslik, ':') === false && strpos($baslik, '/') === false) {
                $skipped++;
                continue;
            }
        }

        $seo = seo_optimize_product_title($db, $baslik, isset($row['stok_kodu']) ? (string)$row['stok_kodu'] : '', '');
        if (empty($seo['success']) || empty($seo['title'])) {
            $failed++;
            continue;
        }

        $newTitle = trim((string)$seo['title']);
        if ($newTitle === '' || $newTitle === $baslik) {
            $skipped++;
            continue;
        }

        $sef = sef($newTitle) . '-' . $id;
        $up = $db->prepare('UPDATE urun SET baslik = ?, sef = ? WHERE id = ? LIMIT 1');
        $ok = $up->execute([$newTitle, $sef, $id]);
        if ($ok) {
            $updated++;
            $details[] = ['id' => $id, 'old' => $baslik, 'new' => $newTitle, 'method' => $seo['method']];
        } else {
            $failed++;
        }
    }

    echo json_encode([
        'success' => true,
        'updated' => $updated,
        'skipped' => $skipped,
        'failed' => $failed,
        'details' => array_slice($details, 0, 30),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

echo json_encode(['success' => false, 'error' => 'Bilinmeyen islem']);
