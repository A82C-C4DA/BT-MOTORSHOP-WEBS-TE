<?php
/**
 * TEK SEFERLİK SAYFA-CACHE TEMİZLEME SCRIPT'İ
 * ------------------------------------------------------------------
 * cache/pages/ klasöründeki birikmiş .html cache dosyalarını siler.
 * Bu dosyalar otomatik üretilen önbellektir; silmek GÜVENLİDİR,
 * site gerektikçe yeniden oluşturur. Veritabanına dokunmaz.
 *
 * File Manager'ın aksine dosyaları KALICI siler (Çöp Kutusu'na atmaz),
 * bu yüzden disk anında boşalır. Yüz binlerce dosyada timeout olmasın
 * diye partiler halinde çalışır ve sayfayı otomatik yeniler.
 *
 * KULLANIM:
 *   1) Aşağıdaki CLEAN_TOKEN'i değiştir.
 *   2) Tarayıcıda aç:
 *      https://btmotorshop.com/cache-temizle.php?token=GIZLI_TOKEN
 *   3) "Bitti" yazana kadar bekle (sayfa kendini yeniler).
 *   4) İş bitince BU DOSYAYI SUNUCUDAN SİL.
 *
 * SSH varsa bunun yerine tek komut yeterli:
 *   find ~/public_html/cache/pages -name '*.html' -delete
 * ------------------------------------------------------------------
 */

define('CLEAN_TOKEN', 'BATU');

$CACHE_DIR  = __DIR__ . '/cache/pages';
$BATCH      = 5000;   // her turda silinecek dosya sayısı

$isCli = (php_sapi_name() === 'cli');
if (!$isCli) {
	$token = isset($_GET['token']) ? $_GET['token'] : '';
	if (!hash_equals(CLEAN_TOKEN, $token)) {
		http_response_code(403);
		exit('Yetkisiz.');
	}
}

@set_time_limit(0);

if (!is_dir($CACHE_DIR)) {
	finish_msg("cache/pages klasörü bulunamadı (zaten temiz olabilir): $CACHE_DIR", $isCli, true, '');
	exit;
}

$deleted = 0;
$h = @opendir($CACHE_DIR);
if ($h) {
	while (($f = readdir($h)) !== false) {
		if ($f === '.' || $f === '..' || substr($f, -5) !== '.html') {
			continue;
		}
		if (@unlink($CACHE_DIR . '/' . $f)) {
			$deleted++;
		}
		if ($deleted >= $BATCH) {
			break;
		}
	}
	closedir($h);
}

// Bu turda silinecek bir şey kalmadıysa bittik.
$finished = ($deleted === 0);

if ($isCli) {
	echo ($finished ? "BITTI. Klasor temiz." : "Bu turda $deleted dosya silindi, devam ediliyor...") . PHP_EOL;
	if (!$finished) {
		// CLI'da kendi kendine döngüye gir
		passthru(PHP_BINARY . ' ' . escapeshellarg(__FILE__));
	}
	exit;
}

header('Content-Type: text/html; charset=utf-8');
if (!$finished) {
	echo '<meta http-equiv="refresh" content="1;url=?token=' . htmlspecialchars(urlencode($_GET['token'])) . '">';
}
echo '<title>Cache Temizleme</title>';
echo '<div style="font-family:system-ui,Arial;max-width:600px;margin:40px auto;color:#222">';
echo '<h2>Sayfa Cache Temizleme</h2>';
if ($finished) {
	echo '<p style="background:#dcfce7;padding:12px;border-radius:8px"><b>Bitti!</b> Tüm birikmiş cache dosyaları silindi. Güvenlik için bu dosyayı (<code>cache-temizle.php</code>) sunucudan silin. Diskin gerçekten boşaldığını cPanel &gt; Disk Usage\'dan birkaç dakika sonra kontrol edin.</p>';
} else {
	echo '<p>Bu turda silinen: <b>' . $deleted . '</b> dosya. İşlem devam ediyor, sayfa otomatik yenileniyor...</p>';
	echo '<p style="color:#666">Lütfen sayfayı kapatmayın.</p>';
}
echo '</div>';

function finish_msg($msg, $isCli, $ok, $extra) {
	if ($isCli) { echo $msg . PHP_EOL; return; }
	header('Content-Type: text/html; charset=utf-8');
	echo '<div style="font-family:system-ui,Arial;max-width:600px;margin:40px auto">' . htmlspecialchars($msg) . '</div>';
}
