<?php
/**
 * Geçici teşhis dosyası.
 * Eryaz API'den ilk ürünleri çeker ve gelen kolon adlarını/değerlerini gösterir.
 * İş bitince sunucudan silin.
 */
require_once __DIR__ . '/api-eryaz.php';

$start = isset($_GET['start']) ? max(1, (int)$_GET['start']) : 1;
$end = isset($_GET['end']) ? max($start, (int)$_GET['end']) : ($start + 4);

$api = new EryazAPI();
$result = $api->getProductList($start, $end);

header('Content-Type: text/html; charset=utf-8');
echo '<!doctype html><html><head><meta charset="utf-8"><title>Eryaz Fiyat Alan Kontrol</title>';
echo '<style>body{font-family:Arial,sans-serif;margin:30px;color:#222}table{border-collapse:collapse;width:100%;margin:15px 0}td,th{border:1px solid #ddd;padding:6px;vertical-align:top}th{background:#f3f4f6;text-align:left}.hit{background:#fff3cd;font-weight:bold}.bad{background:#fee2e2}.ok{background:#dcfce7}code{background:#f3f4f6;padding:2px 4px;border-radius:4px}</style>';
echo '</head><body>';
echo '<h2>Eryaz Fiyat Alan Kontrol</h2>';
echo '<p>Aralık: <code>' . (int)$start . ' - ' . (int)$end . '</code></p>';

if (!$result || empty($result['success'])) {
    echo '<div class="bad" style="padding:12px">API hatası: ' . htmlspecialchars($result['error'] ?? 'Bilinmeyen hata') . '</div>';
    echo '<pre>' . htmlspecialchars(print_r($result, true)) . '</pre></body></html>';
    exit;
}

$products = $result['data']['Data'] ?? $result['data'] ?? [];
if (!is_array($products) || empty($products)) {
    echo '<div class="bad" style="padding:12px">Ürün gelmedi.</div>';
    echo '<pre>' . htmlspecialchars(print_r($result, true)) . '</pre></body></html>';
    exit;
}

$priceHints = ['price', 'fiyat', 'list', 'liste', 'eur', 'euro', 'sale', 'sell', 'satis', 'net', 'brut', 'iskonto'];

$idx = 0;
foreach ($products as $product) {
    if (!is_array($product) || isset($product['Error'])) {
        continue;
    }
    $idx++;
    echo '<h3>Ürün #' . $idx . '</h3>';
    echo '<table><thead><tr><th>Kolon adı</th><th>Değer</th></tr></thead><tbody>';
    foreach ($product as $key => $value) {
        $keyLower = mb_strtolower((string)$key, 'UTF-8');
        $isHit = false;
        foreach ($priceHints as $hint) {
            if (strpos($keyLower, $hint) !== false) {
                $isHit = true;
                break;
            }
        }
        $display = is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : (string)$value;
        echo '<tr' . ($isHit ? ' class="hit"' : '') . '><td><code>' . htmlspecialchars((string)$key) . '</code></td><td>' . htmlspecialchars($display) . '</td></tr>';
    }
    echo '</tbody></table>';
    if ($idx >= 5) {
        break;
    }
}

echo '<p class="ok" style="padding:12px"><strong>Ne yapacağız?</strong> Sarı görünen fiyat/liste/euro kolonlarından dolu ve doğru fiyatı içeren kolon adını bana gönder. Örn: <code>ListeFiyat</code>, <code>ListeFiyatı</code>, <code>Price1</code> gibi.</p>';
echo '<p><strong>Güvenlik:</strong> İş bitince bu dosyayı sunucudan sil.</p>';
echo '</body></html>';
