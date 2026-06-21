<?php
/**
 * TEK SEFERLİK TOPLU GÖRSEL OPTİMİZE SCRIPT'İ
 * ------------------------------------------------------------------
 * upload/ klasöründeki mevcut JPG/PNG görselleri YERİNDE (aynı ad ve
 * uzantı ile) yeniden boyutlandırıp sıkıştırır. Veritabanına DOKUNMAZ,
 * bu yüzden site linkleri bozulmaz. SVG/GIF dosyalarına dokunulmaz.
 *
 * !!! ÇOK ÖNEMLİ: ÇALIŞTIRMADAN ÖNCE upload/ KLASÖRÜNÜN YEDEĞİNİ ALIN.
 * Bu işlem orijinal dosyaların üzerine yazar (geri alınamaz).
 *
 * KULLANIM (tarayıcı):
 *   1) Aşağıdaki OPTIMIZE_TOKEN'i uzun/gizli bir şeyle değiştirin.
 *   2) Önce DENEME (hiçbir şey yazmaz, sadece kazancı raporlar):
 *        https://siteniz.com/panel/upload-optimize-toplu.php?token=GIZLI_TOKEN&mode=dry
 *   3) Sonuç iyiyse GERÇEK çalıştırma:
 *        https://siteniz.com/panel/upload-optimize-toplu.php?token=GIZLI_TOKEN&mode=run
 *   Tarayıcı sayfayı otomatik yenileyerek tüm dosyalar bitene kadar devam eder.
 *
 * KULLANIM (SSH / CLI):
 *   php panel/upload-optimize-toplu.php run
 *
 * İŞ BİTİNCE BU DOSYAYI SUNUCUDAN SİLİN.
 * ------------------------------------------------------------------
 */

// ====== AYARLAR ======
define('OPTIMIZE_TOKEN', 'DEGISTIR-bunu-uzun-gizli-bir-sifre-yap');
$MAX_DIM     = 1920;        // En uzun kenar (px) bu değere indirilir
$QUALITY     = 82;          // JPEG/WebP kalite (0-100)
$PNG_LEVEL   = 8;           // PNG sıkıştırma (0-9)
$MIN_BYTES   = 80 * 1024;   // Bu boyutun altındaki dosyaları (ve küçük boyutluları) atla
$BATCH_WEB   = 150;         // Tarayıcıda her turda işlenecek dosya sayısı
$UPLOAD_DIR  = __DIR__ . '/../upload';
$STATE_FILE  = __DIR__ . '/.upload-optimize-state.json';
// =====================

$isCli = (php_sapi_name() === 'cli');

// --- Yetki / mod ---
if ($isCli) {
	$mode = isset($argv[1]) ? strtolower($argv[1]) : 'dry';
} else {
	$token = isset($_GET['token']) ? $_GET['token'] : '';
	if (!hash_equals(OPTIMIZE_TOKEN, $token)) {
		http_response_code(403);
		exit('Yetkisiz. Doğru token ile çağırın.');
	}
	$mode = isset($_GET['mode']) ? strtolower($_GET['mode']) : 'dry';
}
$dryRun = ($mode !== 'run');

@set_time_limit(0);
@ini_set('memory_limit', '512M');
$startedAt = microtime(true);

if (!is_dir($UPLOAD_DIR)) {
	out("HATA: upload klasörü bulunamadı: $UPLOAD_DIR", $isCli);
	exit;
}

// --- Dosya listesini hazırla / state yükle ---
$state = null;
if (is_file($STATE_FILE)) {
	$state = json_decode(@file_get_contents($STATE_FILE), true);
}
if (!is_array($state) || !isset($state['files']) || ($state['mode'] ?? '') !== $mode) {
	$files = [];
	$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($UPLOAD_DIR, FilesystemIterator::SKIP_DOTS));
	foreach ($it as $f) {
		if (!$f->isFile()) continue;
		$ext = strtolower($f->getExtension());
		if (in_array($ext, ['jpg', 'jpeg', 'png'], true)) {
			$files[] = $f->getPathname();
		}
	}
	$state = [
		'mode'        => $mode,
		'files'       => $files,
		'index'       => 0,
		'done'        => 0,
		'optimized'   => 0,
		'skipped'     => 0,
		'errors'      => 0,
		'bytes_before'=> 0,
		'bytes_after' => 0,
	];
	save_state($STATE_FILE, $state);
}

$total = count($state['files']);
$batch = $isCli ? PHP_INT_MAX : $BATCH_WEB;
$processedThisRun = 0;

while ($state['index'] < $total && $processedThisRun < $batch) {
	$path = $state['files'][$state['index']];
	$state['index']++;
	$processedThisRun++;

	if (!is_file($path)) { $state['skipped']++; continue; }

	$origSize = filesize($path);
	$info = @getimagesize($path);
	if (!$info) { $state['skipped']++; continue; }

	list($w, $h) = $info;
	$needResize = ($w > $MAX_DIM || $h > $MAX_DIM);

	// Küçük ve zaten boyut içindeyse atla (hızlandırma)
	if (!$needResize && $origSize < $MIN_BYTES) { $state['skipped']++; continue; }

	$res = recompress_in_place($path, $MAX_DIM, $QUALITY, $PNG_LEVEL, $dryRun);
	$state['done']++;

	if ($res['status'] === 'ok') {
		$state['optimized']++;
		$state['bytes_before'] += $origSize;
		$state['bytes_after']  += $res['new_size'];
	} elseif ($res['status'] === 'error') {
		$state['errors']++;
	} else {
		$state['skipped']++;
	}
}

save_state($STATE_FILE, $state);

$finished = ($state['index'] >= $total);
$saved = $state['bytes_before'] - $state['bytes_after'];

// --- Çıktı ---
if ($isCli) {
	out(sprintf(
		"[%s] %d/%d islendi | optimize: %d | atlanan: %d | hata: %d | kazanc: %s",
		strtoupper($dryRun ? 'DENEME' : 'GERCEK'),
		$state['index'], $total, $state['optimized'], $state['skipped'], $state['errors'], human($saved)
	), true);
	if ($finished) {
		@unlink($STATE_FILE);
		out("BITTI. Toplam kazanc: " . human($saved) . " (" . human($state['bytes_before']) . " -> " . human($state['bytes_after']) . ")", true);
		out($dryRun ? "Bu DENEME moduydu, hicbir dosya degismedi. Gercek calistirma: php upload-optimize-toplu.php run" : "Is bitti. Bu dosyayi sunucudan silin.", true);
	}
	exit;
}

// Tarayıcı çıktısı
$pct = $total ? round($state['index'] / $total * 100) : 100;
header('Content-Type: text/html; charset=utf-8');
if (!$finished) {
	$qs = 'token=' . urlencode($_GET['token']) . '&mode=' . urlencode($mode);
	echo '<meta http-equiv="refresh" content="1;url=?' . htmlspecialchars($qs) . '">';
}
echo '<title>Toplu Görsel Optimize</title>';
echo '<div style="font-family:system-ui,Arial;max-width:640px;margin:40px auto;color:#222">';
echo '<h2>Toplu Görsel Optimize ' . ($dryRun ? '<span style="color:#b45309">(DENEME modu)</span>' : '<span style="color:#16a34a">(GERÇEK)</span>') . '</h2>';
if ($dryRun) {
	echo '<p style="background:#fef3c7;padding:10px;border-radius:8px">DENEME modu: hiçbir dosya değiştirilmiyor, sadece olası kazanç hesaplanıyor. Gerçek çalıştırmak için URL\'de <b>mode=run</b> kullanın. <b>Önce upload klasörünün yedeğini alın.</b></p>';
}
echo '<div style="background:#e5e7eb;border-radius:8px;overflow:hidden;height:22px;margin:14px 0">';
echo '<div style="background:#2563eb;height:22px;width:' . $pct . '%"></div></div>';
echo "<p><b>$pct%</b> &mdash; {$state['index']} / $total dosya tarandı</p>";
echo '<ul style="line-height:1.8">';
echo '<li>Optimize edilen: <b>' . $state['optimized'] . '</b></li>';
echo '<li>Atlanan (zaten küçük / format dışı): <b>' . $state['skipped'] . '</b></li>';
echo '<li>Hata: <b>' . $state['errors'] . '</b></li>';
echo '<li>' . ($dryRun ? 'Tahmini kazanç' : 'Kazanılan yer') . ': <b style="color:#16a34a">' . human($saved) . '</b> (' . human($state['bytes_before']) . ' &rarr; ' . human($state['bytes_after']) . ')</li>';
echo '</ul>';
if ($finished) {
	@unlink($STATE_FILE);
	echo '<p style="background:#dcfce7;padding:10px;border-radius:8px"><b>Bitti!</b> ' . ($dryRun
		? 'Bu bir denemeydi. Sonuç iyiyse <b>mode=run</b> ile gerçek çalıştırın.'
		: 'İşlem tamamlandı. Güvenlik için bu dosyayı (<code>upload-optimize-toplu.php</code>) sunucudan silin.') . '</p>';
} else {
	echo '<p style="color:#666">Sayfa otomatik yenileniyor, lütfen kapatmayın...</p>';
}
echo '</div>';
exit;


// ================= Fonksiyonlar =================

function recompress_in_place($path, $maxDim, $quality, $pngLevel, $dryRun) {
	$ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
	$origSize = filesize($path);

	if ($ext === 'png') {
		$img = @imagecreatefrompng($path);
	} else {
		$img = @imagecreatefromjpeg($path);
	}
	if (!$img) return ['status' => 'error'];

	$w = imagesx($img);
	$h = imagesy($img);

	if ($w > $maxDim || $h > $maxDim) {
		$ratio = min($maxDim / $w, $maxDim / $h);
		$nw = max(1, (int)round($w * $ratio));
		$nh = max(1, (int)round($h * $ratio));
		$resized = imagecreatetruecolor($nw, $nh);
		imagealphablending($resized, false);
		imagesavealpha($resized, true);
		$transparent = imagecolorallocatealpha($resized, 0, 0, 0, 127);
		imagefilledrectangle($resized, 0, 0, $nw, $nh, $transparent);
		imagecopyresampled($resized, $img, 0, 0, 0, 0, $nw, $nh, $w, $h);
		imagedestroy($img);
		$img = $resized;
	}

	$tmp = $path . '.tmp_opt';
	if ($ext === 'png') {
		imagealphablending($img, false);
		imagesavealpha($img, true);
		$ok = @imagepng($img, $tmp, $pngLevel);
	} else {
		$ok = @imagejpeg($img, $tmp, $quality);
	}
	imagedestroy($img);

	if (!$ok || !is_file($tmp)) {
		@unlink($tmp);
		return ['status' => 'error'];
	}

	$newSize = filesize($tmp);

	// Sadece gerçekten küçüldüyse uygula; yoksa orijinali koru.
	if ($newSize <= 0 || $newSize >= $origSize) {
		@unlink($tmp);
		return ['status' => 'skip'];
	}

	if ($dryRun) {
		@unlink($tmp);
		return ['status' => 'ok', 'new_size' => $newSize];
	}

	// Orijinalin üzerine yaz.
	if (!@rename($tmp, $path)) {
		@unlink($tmp);
		return ['status' => 'error'];
	}
	return ['status' => 'ok', 'new_size' => $newSize];
}

function save_state($file, $state) {
	@file_put_contents($file, json_encode($state), LOCK_EX);
}

function human($bytes) {
	$bytes = (float)$bytes;
	$units = ['B', 'KB', 'MB', 'GB', 'TB'];
	$i = 0;
	while ($bytes >= 1024 && $i < count($units) - 1) { $bytes /= 1024; $i++; }
	return round($bytes, 2) . ' ' . $units[$i];
}

function out($msg, $isCli) {
	echo $msg . ($isCli ? PHP_EOL : '<br>');
}
