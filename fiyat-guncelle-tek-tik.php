<?php
/**
 * Tek tıkla tüm ürün fiyatlarını Eryaz ile senkronize eder.
 *
 * İlk giriş:  fiyat-guncelle-tek-tik.php?key=GIZLI_ANAHTAR
 * (cron-update-prices-auto.php içindeki $secretKey ile aynı olmalı)
 * Sonra adres çubuğundan key silinir; oturum açık kaldığı sürece tek buton yeterli.
 */
session_start();

$secretKey = 'fiyat_guncelleme_2024_secret_key'; // cron-update-prices-auto.php ile AYNI değer

if (isset($_GET['key']) && is_string($_GET['key']) && hash_equals($secretKey, $_GET['key'])) {
    $_SESSION['eryaz_fiyat_panel_ok'] = true;
    header('Location: fiyat-guncelle-tek-tik.php', true, 302);
    exit;
}

// AJAX: arka planda tam güncelleme
if (!empty($_GET['ajax']) && $_GET['ajax'] === '1') {
    header('Content-Type: application/json; charset=utf-8');
    if (empty($_SESSION['eryaz_fiyat_panel_ok'])) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Oturum yok. Sayfayı ?key=... ile bir kez açın.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    require_once __DIR__ . '/panel/db-ayar.php';
    require_once __DIR__ . '/api-eryaz.php';
    require_once __DIR__ . '/eryaz-price-update-worker.php';

    date_default_timezone_set('Europe/Istanbul');
    $t0 = microtime(true);

    try {
        $eryazAPI = new EryazAPI();
        $stats = eryaz_run_full_price_update($db, $eryazAPI);
        $stats['executionTime'] = round(microtime(true) - $t0, 2);
        $stats['timestamp'] = date('Y-m-d H:i:s');
        echo json_encode($stats, JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
    exit;
}

if (empty($_SESSION['eryaz_fiyat_panel_ok'])) {
    http_response_code(403);
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html lang="tr"><head><meta charset="utf-8"><title>Fiyat güncelleme</title></head><body>';
    echo '<p>Erişim için tek seferlik bağlantı kullanın: <code>fiyat-guncelle-tek-tik.php?key=...</code></p>';
    echo '<p>Anahtar, <code>cron-update-prices-auto.php</code> dosyasındaki <code>$secretKey</code> ile aynı olmalıdır.</p>';
    echo '</body></html>';
    exit;
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Eryaz fiyat senkronu</title>
    <style>
        body { font-family: system-ui, sans-serif; max-width: 560px; margin: 48px auto; padding: 0 16px; color: #222; }
        h1 { font-size: 1.25rem; }
        button {
            background: #0d6efd; color: #fff; border: 0; padding: 12px 20px;
            font-size: 1rem; border-radius: 8px; cursor: pointer;
        }
        button:disabled { opacity: .6; cursor: not-allowed; }
        #out { margin-top: 20px; white-space: pre-wrap; font-size: .9rem; background: #f8f9fa; padding: 12px; border-radius: 8px; }
        .ok { color: #0a7c32; }
        .err { color: #b02a37; }
    </style>
</head>
<body>
    <h1>Eryaz — tüm fiyatları güncelle</h1>
    <p>Stok veya başlık değişmez; yalnızca fiyat ve buna bağlı liste alanları güncellenir. Çok üründe işlem birkaç dakika sürebilir.</p>
    <button type="button" id="btn">Tüm fiyatları şimdi güncelle</button>
    <div id="out"></div>
    <script>
        document.getElementById('btn').addEventListener('click', function () {
            var btn = this;
            var out = document.getElementById('out');
            btn.disabled = true;
            out.textContent = 'Çalışıyor… Lütfen bekleyin (sayfayı kapatmayın).';
            fetch('fiyat-guncelle-tek-tik.php?ajax=1', { credentials: 'same-origin' })
                .then(function (r) { return r.json().then(function (j) { return { ok: r.ok, j: j }; }); })
                .then(function (x) {
                    if (!x.ok || !x.j.success) {
                        out.innerHTML = '<span class="err">Hata: ' + (x.j.error || JSON.stringify(x.j)) + '</span>';
                        return;
                    }
                    var s = x.j;
                    var lines = [
                        '<span class="ok">Tamamlandı.</span>',
                        'Güncellenen: ' + s.price_updated,
                        'Sitede yok (API’de var): ' + s.not_in_db,
                        'Atlanan: ' + s.skipped,
                        'Hata satırı: ' + s.error_count,
                        'Süre: ' + s.executionTime + ' sn'
                    ];
                    out.innerHTML = lines.join('<br>');
                })
                .catch(function (e) {
                    out.innerHTML = '<span class="err">İstek hatası: ' + e.message + '</span>';
                })
                .finally(function () {
                    btn.disabled = false;
                });
        });
    </script>
</body>
</html>
