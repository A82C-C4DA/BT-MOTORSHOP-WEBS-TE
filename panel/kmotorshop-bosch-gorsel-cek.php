<?php
/**
 * KMotorShop Bosch görsel import script'i.
 *
 * İzinli kullanım içindir. Bosch grubu Eryaz ürünleri için KMotorShop'ta stok kodu
 * ile arama yapar, uygun ürün görselini indirir ve upload/ klasörüne optimize ederek
 * kaydeder. İş bittikten sonra sunucudan silin.
 */
require_once __DIR__ . '/fonksiyon.php';

if (!isset($_SESSION['admin']['login'])) {
    die('Yetki yok. Once panele giris yapin.');
}

@set_time_limit(0);
@ini_set('memory_limit', '512M');

$run = isset($_GET['run']) && $_GET['run'] === '1';
$limit = isset($_GET['limit']) ? max(1, min(50, (int)$_GET['limit'])) : 10;
$lastId = isset($_GET['last_id']) ? max(0, (int)$_GET['last_id']) : 0;
$onlyNoImage = !isset($_GET['only_no_image']) || $_GET['only_no_image'] === '1';

function kms_clean_code($code) {
    return preg_replace('/^(30-|31-|32-|3e-?)/i', '', trim((string)$code));
}

function kms_is_bosch_group_code($code) {
    $c = strtolower(trim((string)$code));
    return strpos($c, '30-') === 0 || strpos($c, '31-') === 0 || strpos($c, '32-') === 0 || strpos($c, '3e-') === 0 || strpos($c, '3e') === 0;
}

function kms_abs_url($url) {
    $url = html_entity_decode(trim((string)$url), ENT_QUOTES, 'UTF-8');
    if ($url === '') return '';
    if (strpos($url, '//') === 0) return 'https:' . $url;
    if (preg_match('#^https?://#i', $url)) return $url;
    if ($url[0] === '/') return 'https://www.kmotorshop.com' . $url;
    return 'https://www.kmotorshop.com/' . ltrim($url, '/');
}

$GLOBALS['kms_last_http'] = 0;
$GLOBALS['kms_last_err'] = '';

function kms_fetch($url) {
    if (!function_exists('curl_init')) {
        $GLOBALS['kms_last_err'] = 'curl yok';
        return false;
    }
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 25,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/120 Safari/537.36',
        CURLOPT_HTTPHEADER => [
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8',
            'Accept-Language: en-US,en;q=0.9,tr;q=0.8',
        ],
    ]);
    $body = curl_exec($ch);
    $http = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $cerr = curl_error($ch);
    curl_close($ch);
    $GLOBALS['kms_last_http'] = $http;
    $GLOBALS['kms_last_err'] = $cerr;
    if ($http < 200 || $http >= 300 || $body === false || $body === '') {
        return false;
    }
    return $body;
}

function kms_extract_image_from_html($html) {
    $candidates = [];

    if (preg_match_all('#(?:src|href)=["\']([^"\']+\.(?:jpg|jpeg|png|webp)(?:\?[^"\']*)?)["\']#iu', (string)$html, $m)) {
        foreach ($m[1] as $raw) {
            $url = kms_abs_url($raw);
            if ($url === '') continue;
            $u = strtolower($url);
            if (strpos($u, '/images/brand-logo/') !== false) continue;
            if (strpos($u, '/images/360_') !== false) continue;
            if (strpos($u, 'tn_600_ruzne') !== false) continue; // genel placeholder
            if (strpos($u, '/document/tecdoc/') !== false) {
                $candidates[] = $url;
            }
        }
    }

    if (!empty($candidates)) {
        return $candidates[0];
    }

    return '';
}

function kms_bosch_spaced($code) {
    // Bosch numaralari KMotorShop'ta cogu zaman bosluklu yazilir: 0414700002 -> "0 414 700 002".
    $digits = preg_replace('/\D+/', '', (string)$code);
    if (strlen($digits) !== 10) {
        return '';
    }
    return substr($digits, 0, 1) . ' ' . substr($digits, 1, 3) . ' ' . substr($digits, 4, 3) . ' ' . substr($digits, 7, 3);
}

function kms_find_image_url_for_code($code) {
    $code = trim((string)$code);
    if ($code === '') return '';

    // Onemli: KMotorShop'ta Eryaz'a ozel "30-/31-/32-/3e-" on ekleri yok.
    // Bu yuzden aramayi SADECE on eksiz (temizlenmis) kod ile yap.
    $clean = kms_clean_code($code);
    $noSep = str_replace([' ', '-'], '', $clean);
    $searches = array_values(array_filter(array_unique([
        $clean,
        $noSep,
        kms_bosch_spaced($clean),
    ]), function ($v) {
        return $v !== '';
    }));

    foreach ($searches as $searchCode) {
        if ($searchCode === '') continue;
        $listUrl = 'https://www.kmotorshop.com/en/article-list/oe-list/' . rawurlencode($searchCode);
        $html = kms_fetch($listUrl);
        if ($html === false) {
            // Bazı aramalarda wildcard sayfası daha iyi sonuç verir.
            $html = kms_fetch($listUrl . '*');
        }
        if ($html === false) {
            continue;
        }

        $img = kms_extract_image_from_html($html);
        if ($img !== '') {
            return $img;
        }

        // Liste sayfasında detay linki varsa ilk uygun detay sayfasını aç.
        if (preg_match('#href=["\']([^"\']*/en/article-detail/view/[^"\']+)["\']#iu', $html, $dm)) {
            $detailHtml = kms_fetch(kms_abs_url($dm[1]));
            if ($detailHtml !== false) {
                $img = kms_extract_image_from_html($detailHtml);
                if ($img !== '') {
                    return $img;
                }
            }
        }
        usleep(300000);
    }

    return '';
}

function kms_download_image_to_temp($url, &$ext) {
    $url = trim((string)$url);
    if ($url === '' || !preg_match('#^https?://#i', $url) || !function_exists('curl_init')) {
        return false;
    }
    $tmp = tempnam(sys_get_temp_dir(), 'kmsimg_');
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/120 Safari/537.36',
    ]);
    $data = curl_exec($ch);
    $http = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $mime = (string)curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    curl_close($ch);
    if ($http < 200 || $http >= 300 || $data === false || $data === '') {
        @unlink($tmp);
        return false;
    }
    file_put_contents($tmp, $data);
    $ext = strtolower(pathinfo(parse_url($url, PHP_URL_PATH) ?: $url, PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
        $ext = stripos($mime, 'png') !== false ? 'png' : (stripos($mime, 'webp') !== false ? 'webp' : 'jpg');
    }
    return $tmp;
}

$log = [];
$processed = 0;
$added = 0;
$skipped = 0;
$notFound = 0;
$errors = 0;
$nextLastId = $lastId;

if ($run) {
    try {
        $hasEryaz = $db->query("SHOW COLUMNS FROM urun LIKE 'eryaz_stok_kodu'")->fetch() !== false;
    } catch (Exception $e) {
        $hasEryaz = false;
    }

    $fields = $hasEryaz ? 'id, stok_kodu, eryaz_stok_kodu, baslik' : 'id, stok_kodu, baslik';
    // Sadece Bosch grubu (30-/31-/32-/3e-) kodlu urunleri tara. Hem temiz stok_kodu
    // hem de orijinal eryaz_stok_kodu uzerinden esles (kod temizligi yapilmis olabilir).
    $boschWhere = "(LOWER(stok_kodu) LIKE '30-%' OR LOWER(stok_kodu) LIKE '31-%' OR LOWER(stok_kodu) LIKE '32-%' OR LOWER(stok_kodu) LIKE '3e%'";
    if ($hasEryaz) {
        $boschWhere .= " OR LOWER(eryaz_stok_kodu) LIKE '30-%' OR LOWER(eryaz_stok_kodu) LIKE '31-%' OR LOWER(eryaz_stok_kodu) LIKE '32-%' OR LOWER(eryaz_stok_kodu) LIKE '3e%'";
    }
    $boschWhere .= ")";
    $rows = $db->prepare("SELECT {$fields} FROM urun WHERE id > ? AND {$boschWhere} ORDER BY id ASC LIMIT " . (int)$limit);
    $rows->execute([$lastId]);
    $products = $rows->fetchAll(PDO::FETCH_ASSOC);

    foreach ($products as $p) {
        $processed++;
        $nextLastId = (int)$p['id'];
        $rawCode = !empty($p['eryaz_stok_kodu']) ? (string)$p['eryaz_stok_kodu'] : (string)$p['stok_kodu'];
        if (!kms_is_bosch_group_code($rawCode)) {
            $skipped++;
            continue;
        }

        if ($onlyNoImage) {
            $imgCheck = $db->prepare('SELECT id FROM urun_img WHERE urun_id = ? LIMIT 1');
            $imgCheck->execute([(int)$p['id']]);
            if ($imgCheck->fetch()) {
                $skipped++;
                $log[] = '#' . (int)$p['id'] . ' atlandi: gorsel var';
                continue;
            }
        }

        $imgUrl = kms_find_image_url_for_code($rawCode);
        if ($imgUrl === '') {
            $notFound++;
            $diag = 'son HTTP: ' . (int)$GLOBALS['kms_last_http'];
            if (!empty($GLOBALS['kms_last_err'])) {
                $diag .= ', curl: ' . $GLOBALS['kms_last_err'];
            }
            $log[] = '#' . (int)$p['id'] . ' bulunamadi: ' . htmlspecialchars($rawCode . ' (aranan: ' . kms_clean_code($rawCode) . ', ' . $diag . ')', ENT_QUOTES, 'UTF-8');
            continue;
        }

        $ext = 'jpg';
        $tmp = kms_download_image_to_temp($imgUrl, $ext);
        if ($tmp === false) {
            $errors++;
            $log[] = '#' . (int)$p['id'] . ' indirilemedi: ' . htmlspecialchars($imgUrl, ENT_QUOTES, 'UTF-8');
            continue;
        }

        $upload = media_upload_product_image($tmp, $ext);
        @unlink($tmp);
        if (empty($upload['ok']) || empty($upload['value'])) {
            $errors++;
            $log[] = '#' . (int)$p['id'] . ' kaydedilemedi';
            continue;
        }

        $ins = $db->prepare('INSERT INTO urun_img SET urun_id = ?, img = ?');
        $ins->execute([(int)$p['id'], (string)$upload['value']]);
        $added++;
        $log[] = '#' . (int)$p['id'] . ' eklendi: ' . htmlspecialchars((string)$upload['value'], ENT_QUOTES, 'UTF-8');
        usleep(600000);
    }
}

header('Content-Type: text/html; charset=utf-8');
$nextUrl = '?run=1&limit=' . (int)$limit . '&last_id=' . (int)$nextLastId . '&only_no_image=' . ($onlyNoImage ? '1' : '0');
?>
<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <title>KMotorShop Bosch Görsel Çek</title>
    <style>
        body{font-family:Arial,sans-serif;margin:40px;color:#222}
        .box{max-width:860px;padding:20px;border:1px solid #ddd;border-radius:8px;background:#f8fafc}
        .ok{color:#166534;font-weight:bold}.muted{color:#666}
        code{background:#e5e7eb;padding:2px 5px;border-radius:4px}
        pre{white-space:pre-wrap;max-height:360px;overflow:auto;background:#fff;border:1px solid #ddd;padding:12px}
    </style>
</head>
<body>
    <div class="box">
        <h2>KMotorShop Bosch Görsel Çek</h2>
        <?php if (!$run): ?>
            <p>Bu araç Bosch grubu ürünleri KMotorShop'ta stok koduyla arar, görsel bulursa indirip optimize ederek kaydeder.</p>
            <p class="muted">Varsayılan: ürünün zaten görseli varsa atlar. Her tur en fazla <?php echo (int)$limit; ?> ürün tarar.</p>
            <p><a href="?run=1&limit=<?php echo (int)$limit; ?>&last_id=0&only_no_image=1">Başlat (sadece görselsiz ürünler)</a></p>
            <p><a href="?run=1&limit=<?php echo (int)$limit; ?>&last_id=0&only_no_image=0">Başlat (mevcut görsel olsa bile ekle)</a></p>
        <?php else: ?>
            <p class="ok">Tur tamamlandı.</p>
            <p>İşlenen: <strong><?php echo (int)$processed; ?></strong>,
                Eklenen: <strong><?php echo (int)$added; ?></strong>,
                Atlanan: <strong><?php echo (int)$skipped; ?></strong>,
                Bulunamayan: <strong><?php echo (int)$notFound; ?></strong>,
                Hata: <strong><?php echo (int)$errors; ?></strong></p>
            <?php if (!empty($log)): ?>
                <pre><?php echo implode("\n", $log); ?></pre>
            <?php endif; ?>
            <?php if ($processed > 0): ?>
                <p><a href="<?php echo htmlspecialchars($nextUrl, ENT_QUOTES, 'UTF-8'); ?>">Sonraki <?php echo (int)$limit; ?> ürünü tara</a></p>
            <?php else: ?>
                <p class="ok">Bitti: taranacak ürün kalmadı.</p>
            <?php endif; ?>
        <?php endif; ?>
        <p><strong>Güvenlik:</strong> İş bitince <code>panel/kmotorshop-bosch-gorsel-cek.php</code> dosyasını sunucudan sil.</p>
    </div>
</body>
</html>
