<?php
/**
 * Tek seferlik Bosch grubu iskonto uygulama script'i.
 * İş bitince sunucudan silin.
 */
require_once __DIR__ . '/fonksiyon.php';

if (!isset($_SESSION['admin']['login'])) {
    die('Yetki yok. Once panele giris yapin.');
}

@set_time_limit(0);

$dovizGlobal = 0.0;
$tcmbPath = __DIR__ . '/../get-tcmb-euro-rate.php';
if (is_file($tcmbPath) && !function_exists('getTCMBEuroRate')) {
    require_once $tcmbPath;
}
if (function_exists('getTCMBEuroRate')) {
    try {
        $rate = getTCMBEuroRate();
        if ($rate !== false && $rate !== null && (float)$rate > 0) {
            $dovizGlobal = (float)$rate;
        }
    } catch (Throwable $e) {
        $dovizGlobal = 0.0;
    }
}

$rows = $db->query("
    SELECT id, stok_kodu, liste_fiyati_eur, doviz_kuru
    FROM urun
    WHERE LOWER(stok_kodu) LIKE '30-%'
       OR LOWER(stok_kodu) LIKE '31-%'
       OR LOWER(stok_kodu) LIKE '32-%'
       OR LOWER(stok_kodu) LIKE '3e-%'
", PDO::FETCH_ASSOC)->fetchAll(PDO::FETCH_ASSOC);

$updated = 0;
$priceUpdated = 0;
$noPrice = 0;

$up = $db->prepare("
    UPDATE urun
    SET iskonto_orani = ?,
        liste_fiyati_tl = ?,
        doviz_kuru = ?,
        kredi_karti_fiyati = ?,
        pesin_odeme_fiyati = ?
    WHERE id = ?
");

$upOnlyDiscount = $db->prepare("UPDATE urun SET iskonto_orani = ? WHERE id = ?");

foreach ($rows as $row) {
    $stok = strtolower(trim((string)$row['stok_kodu']));
    $iskonto = null;
    if (strpos($stok, '30-') === 0 || strpos($stok, '31-') === 0 || strpos($stok, '32-') === 0) {
        $iskonto = 20.0;
    } elseif (strpos($stok, '3e-') === 0) {
        $iskonto = 10.0;
    }
    if ($iskonto === null) {
        continue;
    }

    $euro = isset($row['liste_fiyati_eur']) ? (float)$row['liste_fiyati_eur'] : 0.0;
    $doviz = $dovizGlobal > 0 ? $dovizGlobal : ((isset($row['doviz_kuru']) && (float)$row['doviz_kuru'] > 0) ? (float)$row['doviz_kuru'] : 35.0);

    if ($euro > 0) {
        $listeTl = $euro * $doviz;
        $kdvsiz = $listeTl * (1 - $iskonto / 100);
        $netKdv = $kdvsiz * 1.20;
        $kredi = $netKdv;
        $pesin = $netKdv * 0.95;
        $up->execute([$iskonto, $listeTl, $doviz, $kredi, $pesin, (int)$row['id']]);
        $priceUpdated++;
    } else {
        $upOnlyDiscount->execute([$iskonto, (int)$row['id']]);
        $noPrice++;
    }
    $updated++;
}

header('Content-Type: text/html; charset=utf-8');
?>
<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <title>Bosch Iskonto Uygulama</title>
    <style>
        body{font-family:Arial,sans-serif;margin:40px;color:#222}
        .box{max-width:650px;padding:20px;border:1px solid #ddd;border-radius:8px;background:#f8fafc}
        .ok{color:#166534;font-weight:bold}
        code{background:#e5e7eb;padding:2px 5px;border-radius:4px}
    </style>
</head>
<body>
    <div class="box">
        <h2>Bosch Grubu Iskonto Uygulandi</h2>
        <p class="ok">Islem tamamlandi.</p>
        <p>Eslesen urun sayisi: <strong><?php echo (int)$updated; ?></strong></p>
        <p>Fiyati yeniden hesaplanan: <strong><?php echo (int)$priceUpdated; ?></strong></p>
        <p>Sadece iskonto yazilan (liste fiyati 0/bos): <strong><?php echo (int)$noPrice; ?></strong></p>
        <p>Kural: <code>30-/31-/32- = %20</code>, <code>3e- = %10</code></p>
        <p><strong>Guvenlik:</strong> Is bitince <code>panel/bosch-iskonto-uygula.php</code> dosyasini sunucudan sil.</p>
    </div>
</body>
</html>
