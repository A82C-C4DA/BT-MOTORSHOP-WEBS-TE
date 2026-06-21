<?php
if (!isset($_SESSION['admin']['login'])) {
	die('Yetki yok');
}

$result = null;
$sampleCsv = "stok_kodu;oem_kodlari;gorsel_url;gorsel_dosya\n15451-21050;15451-21050,15451-21053,1G924-21050;https://ornek.com/resim.jpg;kubota-segman.jpg";

function tecdoc_import_norm_header($s) {
	$s = trim((string)$s);
	$s = preg_replace('/^\xEF\xBB\xBF/', '', $s);
	$s = str_replace(
		['İ', 'I', 'ı', 'Ş', 'Ğ', 'Ü', 'Ö', 'Ç', 'ş', 'ğ', 'ü', 'ö', 'ç', ' '],
		['i', 'i', 'i', 's', 'g', 'u', 'o', 'c', 's', 'g', 'u', 'o', 'c', '_'],
		$s
	);
	$s = strtolower($s);
	return preg_replace('/[^a-z0-9_]+/', '_', $s);
}

function tecdoc_import_detect_delimiter($line) {
	$delims = [";" => 0, "," => 0, "\t" => 0];
	foreach ($delims as $d => $v) {
		$delims[$d] = substr_count($line, $d);
	}
	arsort($delims);
	$keys = array_keys($delims);
	return $keys[0];
}

function tecdoc_import_split_codes($value) {
	$value = trim((string)$value);
	if ($value === '') {
		return [];
	}
	$parts = preg_split('/[\r\n,;|]+/u', $value);
	$out = [];
	foreach ($parts as $p) {
		$p = trim($p);
		if ($p !== '') {
			$out[$p] = true;
		}
	}
	return array_keys($out);
}

function tecdoc_import_image_ext($pathOrUrl, $mime = '') {
	$ext = strtolower(pathinfo(parse_url((string)$pathOrUrl, PHP_URL_PATH) ?: (string)$pathOrUrl, PATHINFO_EXTENSION));
	if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) {
		return $ext;
	}
	if (stripos($mime, 'png') !== false) return 'png';
	if (stripos($mime, 'gif') !== false) return 'gif';
	if (stripos($mime, 'webp') !== false) return 'webp';
	return 'jpg';
}

function tecdoc_import_download_url($url, &$ext) {
	$url = trim((string)$url);
	if ($url === '' || !preg_match('#^https?://#i', $url)) {
		return false;
	}
	$tmp = tempnam(sys_get_temp_dir(), 'tdimg_');
	$mime = '';
	if (function_exists('curl_init')) {
		$ch = curl_init($url);
		curl_setopt_array($ch, [
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_TIMEOUT => 25,
			CURLOPT_CONNECTTIMEOUT => 10,
			CURLOPT_USERAGENT => 'BTMotorShop TecDoc Import',
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
	} else {
		$data = @file_get_contents($url);
		if ($data === false || $data === '') {
			@unlink($tmp);
			return false;
		}
		file_put_contents($tmp, $data);
	}
	$ext = tecdoc_import_image_ext($url, $mime);
	return $tmp;
}

function tecdoc_import_add_image(PDO $db, $urunId, $sourcePath, $ext, $onlyIfNoImage) {
	if ($onlyIfNoImage) {
		$exists = $db->prepare('SELECT id FROM urun_img WHERE urun_id = ? LIMIT 1');
		$exists->execute([$urunId]);
		if ($exists->fetch()) {
			return ['ok' => true, 'skipped' => true, 'value' => 'urun_gorseli_var'];
		}
	}
	$upload = media_upload_product_image($sourcePath, $ext);
	if (empty($upload['ok']) || empty($upload['value'])) {
		return ['ok' => false, 'error' => 'gorsel_kaydedilemedi'];
	}
	$fileName = (string)$upload['value'];
	$check = $db->prepare('SELECT id FROM urun_img WHERE urun_id = ? AND img = ? LIMIT 1');
	$check->execute([$urunId, $fileName]);
	if (!$check->fetch()) {
		$ins = $db->prepare('INSERT INTO urun_img SET urun_id = ?, img = ?');
		$ins->execute([$urunId, $fileName]);
	}
	return ['ok' => true, 'skipped' => false, 'value' => $fileName];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
	$result = [
		'processed' => 0,
		'products_found' => 0,
		'oem_added' => 0,
		'oem_skipped' => 0,
		'images_added' => 0,
		'images_skipped' => 0,
		'errors' => [],
	];
	$onlyIfNoImage = isset($_POST['only_if_no_image']) && $_POST['only_if_no_image'] === '1';
	$csvPath = $_FILES['csv_file']['tmp_name'] ?? '';
	if ($csvPath === '' || !is_uploaded_file($csvPath)) {
		$result['errors'][] = 'CSV dosyasi yuklenemedi.';
	} else {
		try {
			$db->exec("CREATE TABLE IF NOT EXISTS urun_referans (
				id INT AUTO_INCREMENT PRIMARY KEY,
				urun_id INT NOT NULL,
				marka_adi VARCHAR(255) DEFAULT '',
				referans_no VARCHAR(255) NOT NULL,
				sira INT NOT NULL DEFAULT 0,
				INDEX idx_urun_id (urun_id),
				INDEX idx_referans_no (referans_no)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
		} catch (Exception $e) {
			// Tablo zaten varsa devam et.
		}

		$fh = fopen($csvPath, 'r');
		$firstLine = fgets($fh);
		if ($firstLine === false) {
			$result['errors'][] = 'CSV bos gorunuyor.';
		} else {
			$delimiter = tecdoc_import_detect_delimiter($firstLine);
			rewind($fh);
			$header = fgetcsv($fh, 0, $delimiter);
			$map = [];
			foreach ((array)$header as $idx => $h) {
				$map[tecdoc_import_norm_header($h)] = $idx;
			}
			$stokIdx = $map['stok_kodu'] ?? $map['stokkodu'] ?? $map['stok'] ?? null;
			$oemIdx = $map['oem_kodlari'] ?? $map['oem'] ?? $map['oem_kodu'] ?? $map['referans_no'] ?? null;
			$imgUrlIdx = $map['gorsel_url'] ?? $map['resim_url'] ?? $map['image_url'] ?? null;
			$imgFileIdx = $map['gorsel_dosya'] ?? $map['resim_dosya'] ?? $map['image_file'] ?? null;

			if ($stokIdx === null) {
				$result['errors'][] = 'CSV icinde stok_kodu kolonu bulunamadi.';
			} else {
				$productStmt = $db->prepare('SELECT id, baslik FROM urun WHERE stok_kodu = ? LIMIT 1');
				$refCheck = $db->prepare('SELECT id FROM urun_referans WHERE urun_id = ? AND referans_no = ? LIMIT 1');
				$refInsert = $db->prepare('INSERT INTO urun_referans SET urun_id = ?, marka_adi = ?, referans_no = ?, sira = ?');
				$importImageDir = __DIR__ . '/../tecdoc-import-images';

				while (($row = fgetcsv($fh, 0, $delimiter)) !== false) {
					$result['processed']++;
					$stok = isset($row[$stokIdx]) ? trim((string)$row[$stokIdx]) : '';
					if ($stok === '') {
						$result['errors'][] = 'Satir ' . $result['processed'] . ': stok_kodu bos.';
						continue;
					}
					$productStmt->execute([$stok]);
					$product = $productStmt->fetch(PDO::FETCH_ASSOC);
					if (!$product) {
						$result['errors'][] = 'Satir ' . $result['processed'] . ': urun bulunamadi (stok_kodu: ' . htmlspecialchars($stok, ENT_QUOTES, 'UTF-8') . ').';
						continue;
					}
					$urunId = (int)$product['id'];
					$result['products_found']++;

					if ($oemIdx !== null && isset($row[$oemIdx])) {
						$codes = tecdoc_import_split_codes($row[$oemIdx]);
						$sira = 0;
						foreach ($codes as $code) {
							$refCheck->execute([$urunId, $code]);
							if ($refCheck->fetch()) {
								$result['oem_skipped']++;
								continue;
							}
							$refInsert->execute([$urunId, 'TecDoc', $code, $sira++]);
							$result['oem_added']++;
						}
					}

					$imageHandled = false;
					if ($imgUrlIdx !== null && !empty($row[$imgUrlIdx])) {
						$ext = 'jpg';
						$tmpImg = tecdoc_import_download_url($row[$imgUrlIdx], $ext);
						if ($tmpImg !== false) {
							$imgRes = tecdoc_import_add_image($db, $urunId, $tmpImg, $ext, $onlyIfNoImage);
							@unlink($tmpImg);
							if (!empty($imgRes['ok']) && empty($imgRes['skipped'])) $result['images_added']++;
							elseif (!empty($imgRes['skipped'])) $result['images_skipped']++;
							else $result['errors'][] = 'Satir ' . $result['processed'] . ': gorsel URL kaydedilemedi.';
							$imageHandled = true;
						} else {
							$result['errors'][] = 'Satir ' . $result['processed'] . ': gorsel_url indirilemedi.';
						}
					}

					if (!$imageHandled && $imgFileIdx !== null && !empty($row[$imgFileIdx])) {
						$fileName = basename(trim((string)$row[$imgFileIdx]));
						$source = $importImageDir . '/' . $fileName;
						if (is_file($source)) {
							$ext = tecdoc_import_image_ext($source);
							$imgRes = tecdoc_import_add_image($db, $urunId, $source, $ext, $onlyIfNoImage);
							if (!empty($imgRes['ok']) && empty($imgRes['skipped'])) $result['images_added']++;
							elseif (!empty($imgRes['skipped'])) $result['images_skipped']++;
							else $result['errors'][] = 'Satir ' . $result['processed'] . ': gorsel dosyasi kaydedilemedi.';
						} else {
							$result['errors'][] = 'Satir ' . $result['processed'] . ': gorsel_dosya bulunamadi (' . htmlspecialchars($fileName, ENT_QUOTES, 'UTF-8') . ').';
						}
					}
				}
			}
		}
		if (is_resource($fh)) {
			fclose($fh);
		}
	}
}
?>

<div class="breadcrumb-header justify-content-between">
	<div class="left-content">
		<h4 class="content-title mb-1">TecDoc CSV Import</h4>
		<small>API olmadan TecDoc OEM kodlari ve gorsellerini CSV ile urunlere aktarir.</small>
	</div>
</div>

<?php if ($result): ?>
	<div class="alert alert-info">
		<strong>Import tamamlandi.</strong><br>
		Islenen satir: <?php echo (int)$result['processed']; ?>,
		Bulunan urun: <?php echo (int)$result['products_found']; ?>,
		Eklenen OEM: <?php echo (int)$result['oem_added']; ?>,
		Atlanan OEM: <?php echo (int)$result['oem_skipped']; ?>,
		Eklenen gorsel: <?php echo (int)$result['images_added']; ?>,
		Atlanan gorsel: <?php echo (int)$result['images_skipped']; ?>
	</div>
	<?php if (!empty($result['errors'])): ?>
		<div class="alert alert-warning">
			<strong>Uyarilar / Hatalar (ilk 50):</strong>
			<ul class="mb-0">
				<?php foreach (array_slice($result['errors'], 0, 50) as $err): ?>
					<li><?php echo $err; ?></li>
				<?php endforeach; ?>
			</ul>
		</div>
	<?php endif; ?>
<?php endif; ?>

<div class="row">
	<div class="col-lg-7">
		<div class="card">
			<div class="card-header"><h5 class="mb-0">CSV Yukle</h5></div>
			<div class="card-body">
				<form method="post" enctype="multipart/form-data">
					<div class="form-group">
						<label>CSV Dosyasi</label>
						<input type="file" name="csv_file" class="form-control" accept=".csv,.txt" required>
					</div>
					<div class="form-group">
						<label class="ckbox">
							<input type="checkbox" name="only_if_no_image" value="1" checked>
							<span>Gorseli sadece urunde hic gorsel yoksa ekle</span>
						</label>
					</div>
					<button type="submit" class="btn btn-primary">
						<i class="fa fa-upload"></i> Import Et
					</button>
				</form>
			</div>
		</div>
	</div>
	<div class="col-lg-5">
		<div class="card">
			<div class="card-header"><h5 class="mb-0">CSV Formati</h5></div>
			<div class="card-body">
				<p><code>stok_kodu</code> zorunlu. Diger kolonlar opsiyonel.</p>
				<pre style="white-space:pre-wrap;background:#f7f7f7;padding:10px;border-radius:6px;"><?php echo htmlspecialchars($sampleCsv, ENT_QUOTES, 'UTF-8'); ?></pre>
				<p class="mb-1"><strong>Gorsel URL:</strong> <code>gorsel_url</code> doluysa resim indirilir, WebP/optimize kaydedilir.</p>
				<p class="mb-0"><strong>Gorsel Dosya:</strong> <code>gorsel_dosya</code> kullanacaksan dosyalari <code>panel/tecdoc-import-images/</code> klasorune yukle.</p>
			</div>
		</div>
	</div>
</div>
