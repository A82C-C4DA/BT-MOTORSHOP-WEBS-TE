<?php
/**
 * Eryaz ile eşleşen ürünleri tek seferde siler (API'deki stok kodlarına göre).
 *
 * İlk giriş: eryaz-toplu-sil.php?key=GIZLI_ANAHTAR
 * Anahtar: aşağıdaki $secretKey (fiyat sayfasıyla aynı varsayılanı kullanabilirsiniz).
 *
 * Uyarı: Geri alınamaz. API'de olmayan manuel ürünler silinmez.
 */
session_start();

$secretKey = 'fiyat_guncelleme_2024_secret_key'; // fiyat-guncelle-tek-tik.php ile aynı yapabilirsiniz

if (isset($_GET['key']) && is_string($_GET['key']) && hash_equals($secretKey, $_GET['key'])) {
    $_SESSION['eryaz_sil_panel_ok'] = true;
    header('Location: eryaz-toplu-sil.php', true, 302);
    exit;
}

if (!empty($_GET['ajax']) && $_GET['ajax'] === '1' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=utf-8');

    if (empty($_SESSION['eryaz_sil_panel_ok'])) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Oturum yok. Sayfayı ?key=... ile bir kez açın.'], JSON_UNESCAPED_UNICODE);
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
        echo json_encode(['success' => false, 'error' => 'Onay için JSON içinde "confirm":"SIL" gönderilmelidir.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    require_once __DIR__ . '/panel/db-ayar.php';
    require_once __DIR__ . '/api-eryaz.php';
    require_once __DIR__ . '/eryaz-delete-products-worker.php';

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
    exit;
}

if (empty($_SESSION['eryaz_sil_panel_ok'])) {
    http_response_code(403);
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html lang="tr"><head><meta charset="utf-8"><title>Eryaz toplu sil</title></head><body>';
    echo '<p>Erişim: <code>eryaz-toplu-sil.php?key=...</code> (<code>$secretKey</code> ile aynı)</p>';
    echo '</body></html>';
    exit;
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Eryaz ürünlerini toplu sil</title>
    <style>
        body { font-family: system-ui, sans-serif; max-width: 560px; margin: 48px auto; padding: 0 16px; color: #222; }
        h1 { font-size: 1.25rem; }
        .warn { background: #fff3cd; border: 1px solid #ffc107; padding: 12px; border-radius: 8px; margin-bottom: 16px; }
        button {
            background: #b02a37; color: #fff; border: 0; padding: 12px 20px;
            font-size: 1rem; border-radius: 8px; cursor: pointer;
        }
        button:disabled { opacity: .6; cursor: not-allowed; }
        #out { margin-top: 20px; white-space: pre-wrap; font-size: .9rem; background: #f8f9fa; padding: 12px; border-radius: 8px; }
        .ok { color: #0a7c32; }
        .err { color: #b02a37; }
        label { display: block; margin: 12px 0 6px; font-weight: 600; }
        input[type="text"] { width: 100%; max-width: 280px; padding: 8px; font-size: 1rem; box-sizing: border-box; }
    </style>
</head>
<body>
    <h1>Eryaz ürünlerini toplu sil</h1>
    <div class="warn">
        <strong>Dikkat:</strong> Eryaz API’de şu an görünen tüm stok kodları, sitede aynı <code>stok_kodu</code> ile kayıtlı ürünlerle eşleştirilir ve <strong>kalıcı olarak silinir</strong> (kategori bağları ve görseller dahil).
        API’de olmayan ürünler silinmez. API’den düşmüş eski Eryaz ürünleri bu işlemle temizlenmez.
    </div>
    <label for="onay">Onay için büyük harfle yazın: <strong>SIL</strong></label>
    <input type="text" id="onay" placeholder="SIL" autocomplete="off">
    <p style="margin-top:16px;">
        <button type="button" id="btn">Eşleşen tüm Eryaz ürünlerini sil</button>
    </p>
    <div id="out"></div>
    <script>
        document.getElementById('btn').addEventListener('click', function () {
            var btn = this;
            var out = document.getElementById('out');
            var v = document.getElementById('onay').value.trim();
            if (v !== 'SIL') {
                out.innerHTML = '<span class="err">Onay için kutuya SIL yazmalısınız.</span>';
                return;
            }
            btn.disabled = true;
            out.textContent = 'Çalışıyor… Bu işlem uzun sürebilir; sayfayı kapatmayın.';
            fetch('eryaz-toplu-sil.php?ajax=1', {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ confirm: 'SIL' })
            })
                .then(function (r) { return r.json().then(function (j) { return { ok: r.ok, j: j }; }); })
                .then(function (x) {
                    if (!x.ok || x.j.success === false) {
                        out.innerHTML = '<span class="err">Hata: ' + (x.j.error || JSON.stringify(x.j)) + '</span>';
                        return;
                    }
                    var s = x.j;
                    out.innerHTML = [
                        '<span class="ok">Tamamlandı.</span>',
                        'API benzersiz kod: ' + s.api_unique_codes,
                        'Eşleşen ürün: ' + s.matched_ids,
                        'Silinen satır (urun): ' + s.deleted,
                        'Süre: ' + s.executionTime + ' sn'
                    ].join('<br>');
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
