<?php
// Eryaz API'yi dahil et
$apiFile = __DIR__ . '/../../api-eryaz.php';
if (file_exists($apiFile)) {
    require_once $apiFile;
}

// NOT: Otomatik stok güncelleme kaldırıldı (performans sorunu nedeniyle)
// Stok güncellemesi için:
// 1. Cron job kullanın: cron-update-stocks-auto.php (her 5-10 dakikada bir)
// 2. Veya manuel olarak: https://yoursite.com/cron-update-stocks-auto.php?key=YOUR_SECRET_KEY
// 3. Veya sayfadaki "Stokları Güncelle" butonunu kullanın

// AJAX ile stok güncelleme (butona tıklanınca)
if (isset($_POST['ajax_update_stocks']) && $_POST['ajax_update_stocks'] == '1') {
	header('Content-Type: application/json');
	
	try {
		$productsData = isset($_POST['products_data']) ? $_POST['products_data'] : '';
		$productsByCode = json_decode($productsData, true);
		
		if (!$productsByCode || !is_array($productsByCode)) {
			echo json_encode(['success' => false, 'error' => 'Geçersiz veri']);
			exit;
		}
		
		// Veritabanındaki ürünleri güncelle
		try {
			$hasEryazStockCode = $db->query("SHOW COLUMNS FROM urun LIKE 'eryaz_stok_kodu'")->fetch() !== false;
		} catch (Exception $e) {
			$hasEryazStockCode = false;
		}
		$selectStockFields = $hasEryazStockCode ? 'id, stok_kodu, eryaz_stok_kodu, stok_manuel' : 'id, stok_kodu, stok_manuel';
		$allProducts = $db->query("SELECT {$selectStockFields} FROM urun WHERE stok_kodu IS NOT NULL AND stok_kodu != ''")->fetchAll(PDO::FETCH_ASSOC);
		
		$updated = 0;
		foreach ($allProducts as $urun) {
			$stokKodu = !empty($urun['eryaz_stok_kodu']) ? $urun['eryaz_stok_kodu'] : $urun['stok_kodu'];
			$stokManuel = isset($urun['stok_manuel']) ? (int)$urun['stok_manuel'] : 0;
			
			if (!isset($productsByCode[$stokKodu])) {
				continue;
			}
			
			$product = $productsByCode[$stokKodu];
			
			// Depo stok durumlarını al
			$maslak = getStatusValue($product, ['Maslak_Status', 'maslak_status', 'Maslak_Stok', 'maslak_stok']);
			$bolu = getStatusValue($product, ['Bolu_Status', 'bolu_status', 'Bolu_Stok', 'bolu_stok']);
			$imes = getStatusValue($product, ['İmes_Status', 'imes_status', 'İmes_Stok', 'imes_stok', 'Imes_Status', 'Imes_Stok']);
			$ankara = getStatusValue($product, ['Ankara_Status', 'ankara_status', 'Ankara_Stok', 'ankara_stok']);
			$ikitelli = getStatusValue($product, ['İkitelli_Status', 'ikitelli_status', 'İkitelli_Stok', 'ikitelli_stok', 'Ikitelli_Status', 'Ikitelli_Stok']);
			
			if ($stokManuel == 1) {
				// Sadece depo stok bilgilerini güncelle
				$updateQuery = $db->prepare("UPDATE urun SET maslak_stok = ?, bolu_stok = ?, imes_stok = ?, ankara_stok = ?, ikitelli_stok = ? WHERE id = ?");
				$updateQuery->execute([$maslak, $bolu, $imes, $ankara, $ikitelli, $urun['id']]);
			} else {
				// Genel stok durumunu da güncelle
				$genel_stok = ($maslak == 1 || $bolu == 1 || $imes == 1 || $ankara == 1 || $ikitelli == 1) ? 1 : 0;
				$updateQuery = $db->prepare("UPDATE urun SET maslak_stok = ?, bolu_stok = ?, imes_stok = ?, ankara_stok = ?, ikitelli_stok = ?, stok = ? WHERE id = ?");
				$updateQuery->execute([$maslak, $bolu, $imes, $ankara, $ikitelli, $genel_stok, $urun['id']]);
			}
			
			$updated++;
		}
		
		echo json_encode(['success' => true, 'updated' => $updated]);
	} catch (Exception $e) {
		echo json_encode(['success' => false, 'error' => $e->getMessage()]);
	}
	exit;
}

// AJAX ile toplu otomatik ceviri - urun listesini getir
if (isset($_POST['ajax_translate_action']) && $_POST['ajax_translate_action'] === 'get_products') {
	header('Content-Type: application/json');

	try {
		$allRequiredCols = [
			'id', 'baslik', 'kisa_aciklama', 'aciklama',
			'baslik_en', 'baslik_ru', 'baslik_fr', 'baslik_es', 'baslik_ar', 'baslik_pl',
			'kisa_aciklama_en', 'kisa_aciklama_ru', 'kisa_aciklama_fr', 'kisa_aciklama_es', 'kisa_aciklama_ar', 'kisa_aciklama_pl',
			'aciklama_en', 'aciklama_ru', 'aciklama_fr', 'aciklama_es', 'aciklama_ar', 'aciklama_pl'
		];

		$availableColumns = [];
		$columnRows = $db->query("SHOW COLUMNS FROM urun", PDO::FETCH_ASSOC)->fetchAll(PDO::FETCH_ASSOC);
		foreach ($columnRows as $colRow) {
			if (isset($colRow['Field'])) {
				$availableColumns[] = $colRow['Field'];
			}
		}

		$selectCols = [];
		foreach ($allRequiredCols as $col) {
			if (in_array($col, $availableColumns, true)) {
				$selectCols[] = $col;
			}
		}

		if (empty($selectCols)) {
			echo json_encode(['success' => false, 'error' => 'Urun tablosunda uygun kolon bulunamadi']);
			exit;
		}

		$sql = "SELECT " . implode(', ', $selectCols) . " FROM urun ORDER BY id DESC";
		$stmt = $db->query($sql, PDO::FETCH_ASSOC);
		if (!$stmt) {
			echo json_encode(['success' => false, 'error' => 'Urun listesi sorgusu calistirilamadi']);
			exit;
		}

		$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

		// JS tarafi sabit alan adlarini kullandigi icin eksik kolonlari bos olarak tamamla
		foreach ($products as &$p) {
			foreach ($allRequiredCols as $col) {
				if (!isset($p[$col])) {
					$p[$col] = '';
				}
			}
		}
		unset($p);

		echo json_encode(['success' => true, 'products' => $products]);
	} catch (Exception $e) {
		echo json_encode(['success' => false, 'error' => $e->getMessage()]);
	}
	exit;
}

// AJAX ile toplu otomatik ceviri - tek urunu kaydet
if (isset($_POST['ajax_translate_action']) && $_POST['ajax_translate_action'] === 'save_product') {
	header('Content-Type: application/json');

	try {
		$productId = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
		if ($productId <= 0) {
			echo json_encode(['success' => false, 'error' => 'Gecersiz urun id']);
			exit;
		}

		$fields = [
			'baslik_en', 'baslik_ru', 'baslik_fr', 'baslik_es', 'baslik_ar', 'baslik_pl',
			'kisa_aciklama_en', 'kisa_aciklama_ru', 'kisa_aciklama_fr', 'kisa_aciklama_es', 'kisa_aciklama_ar', 'kisa_aciklama_pl',
			'aciklama_en', 'aciklama_ru', 'aciklama_fr', 'aciklama_es', 'aciklama_ar', 'aciklama_pl'
		];

		$availableColumns = [];
		$columnRows = $db->query("SHOW COLUMNS FROM urun", PDO::FETCH_ASSOC)->fetchAll(PDO::FETCH_ASSOC);
		foreach ($columnRows as $colRow) {
			if (isset($colRow['Field'])) {
				$availableColumns[] = $colRow['Field'];
			}
		}

		$setParts = [];
		$params = [];
		foreach ($fields as $field) {
			if (isset($_POST[$field]) && in_array($field, $availableColumns, true)) {
				$setParts[] = "{$field} = ?";
				$params[] = trim((string)$_POST[$field]);
			}
		}

		if (empty($setParts)) {
			echo json_encode(['success' => true, 'updated' => false, 'message' => 'Guncellenecek alan yok']);
			exit;
		}

		$params[] = $productId;
		$sql = "UPDATE urun SET " . implode(', ', $setParts) . " WHERE id = ? LIMIT 1";
		$update = $db->prepare($sql);
		$update->execute($params);

		echo json_encode(['success' => true, 'updated' => true]);
	} catch (Exception $e) {
		echo json_encode(['success' => false, 'error' => $e->getMessage()]);
	}
	exit;
}

// AJAX ile tek urunun ceviri kaynak alanlarini getir (per-row otomatik cevir icin)
if (isset($_POST['ajax_translate_action']) && $_POST['ajax_translate_action'] === 'get_one_product') {
	header('Content-Type: application/json; charset=utf-8');
	try {
		$productId = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
		if ($productId <= 0) {
			echo json_encode(['success' => false, 'error' => 'Gecersiz urun id']);
			exit;
		}

		$wantCols = [
			'id', 'baslik', 'kisa_aciklama', 'aciklama',
			'baslik_en', 'baslik_ru', 'baslik_fr', 'baslik_es', 'baslik_ar', 'baslik_pl',
			'kisa_aciklama_en', 'kisa_aciklama_ru', 'kisa_aciklama_fr', 'kisa_aciklama_es', 'kisa_aciklama_ar', 'kisa_aciklama_pl',
			'aciklama_en', 'aciklama_ru', 'aciklama_fr', 'aciklama_es', 'aciklama_ar', 'aciklama_pl'
		];

		$availableColumns = [];
		$columnRows = $db->query("SHOW COLUMNS FROM urun", PDO::FETCH_ASSOC)->fetchAll(PDO::FETCH_ASSOC);
		foreach ($columnRows as $colRow) {
			if (isset($colRow['Field'])) {
				$availableColumns[] = $colRow['Field'];
			}
		}

		$selectCols = [];
		foreach ($wantCols as $col) {
			if (in_array($col, $availableColumns, true)) {
				$selectCols[] = $col;
			}
		}
		if (empty($selectCols)) {
			echo json_encode(['success' => false, 'error' => 'Uygun kolon bulunamadi']);
			exit;
		}

		$st = $db->prepare("SELECT " . implode(', ', $selectCols) . " FROM urun WHERE id = ? LIMIT 1");
		$st->execute([$productId]);
		$product = $st->fetch(PDO::FETCH_ASSOC);
		if (!$product) {
			echo json_encode(['success' => false, 'error' => 'Urun bulunamadi']);
			exit;
		}
		foreach ($wantCols as $col) {
			if (!isset($product[$col])) {
				$product[$col] = '';
			}
		}

		echo json_encode(['success' => true, 'product' => $product], JSON_UNESCAPED_UNICODE);
	} catch (Exception $e) {
		echo json_encode(['success' => false, 'error' => $e->getMessage()]);
	}
	exit;
}

// Status değerini al ve 1/0'a çevir (yardımcı fonksiyon)
function getStatusValue($product, $fieldNames) {
	if (!is_array($product)) {
		return 0;
	}
	
	$value = null;
	if (!is_array($fieldNames)) {
		$fieldNames = [$fieldNames];
	}
	
	foreach ($fieldNames as $fieldName) {
		foreach ($product as $key => $val) {
			if (strtolower(trim($key)) === strtolower(trim($fieldName))) {
				$value = $val;
				break 2;
			}
		}
	}
	
	if ($value !== null && $value !== '') {
		$value = strtolower(trim($value));
		return ($value === 'var') ? 1 : 0;
	}
	
	return 0;
}

function urunler_bosch_iskonto_kurali($stokKodu) {
	$stokKodu = strtolower(trim((string)$stokKodu));
	if ($stokKodu === '') {
		return null;
	}
	if (strpos($stokKodu, '30-') === 0 || strpos($stokKodu, '31-') === 0 || strpos($stokKodu, '32-') === 0) {
		return 20.0;
	}
	if (strpos($stokKodu, '3e-') === 0) {
		return 10.0;
	}
	return null;
}

/**
 * En son eklenen urun sira=1, en eski en buyuk numara (yukleme sirasi = id DESC).
 */
function urunler_recalc_sira_by_yukleme_tarihi(PDO $db) {
	try {
		$check = $db->query("SHOW COLUMNS FROM urun LIKE 'sira'")->fetch();
		if (!$check) {
			$db->exec('ALTER TABLE urun ADD COLUMN sira INT NOT NULL DEFAULT 9999');
		}
	} catch (Exception $e) {
		return ['ok' => false, 'error' => $e->getMessage(), 'updated' => 0];
	}
	try {
		$ids = $db->query('SELECT id FROM urun ORDER BY id DESC')->fetchAll(PDO::FETCH_COLUMN);
		if (empty($ids)) {
			return ['ok' => true, 'updated' => 0];
		}
		$st = $db->prepare('UPDATE urun SET sira = ? WHERE id = ? LIMIT 1');
		$rank = 1;
		foreach ($ids as $uid) {
			$st->execute([$rank, (int)$uid]);
			$rank++;
		}
		return ['ok' => true, 'updated' => count($ids)];
	} catch (Exception $e) {
		return ['ok' => false, 'error' => $e->getMessage(), 'updated' => 0];
	}
}

if (isset($_POST['ajax_urunler']) && $_POST['ajax_urunler'] === '1') {
	@ini_set('display_errors', '0');
	header('Content-Type: application/json; charset=utf-8');
	if (!isset($_SESSION['admin']['login'])) {
		echo json_encode(['ok' => false, 'error' => 'Yetki yok']);
		exit;
	}
	try {
		$checkPricingAjax = $db->query("SHOW COLUMNS FROM urun LIKE 'liste_fiyati_eur'")->fetch();
		$ajaxHasListeEuro = ($checkPricingAjax !== false);
	} catch (Exception $e) {
		$ajaxHasListeEuro = false;
	}
	$action = isset($_POST['action']) ? $_POST['action'] : '';
	if ($action === 'sira_toplu') {
		$result = urunler_recalc_sira_by_yukleme_tarihi($db);
		echo json_encode($result);
		exit;
	}
	if ($action === 'iskonto_orani') {
		$uid = isset($_POST['urun_id']) ? (int)$_POST['urun_id'] : 0;
		$iskontoRaw = isset($_POST['iskonto_orani']) ? str_replace(',', '.', trim((string)$_POST['iskonto_orani'])) : '';
		$iskonto = $iskontoRaw === '' ? 0.0 : (float)$iskontoRaw;
		if ($iskonto < 0) {
			$iskonto = 0.0;
		}
		if ($iskonto > 100) {
			$iskonto = 100.0;
		}
		if ($uid < 1) {
			echo json_encode(['ok' => false, 'error' => 'Geçersiz ürün']);
			exit;
		}
		$st = $db->prepare('SELECT id, liste_fiyati_eur, doviz_kuru FROM urun WHERE id = ? LIMIT 1');
		$st->execute([$uid]);
		$r = $st->fetch(PDO::FETCH_ASSOC);
		if (!$r) {
			echo json_encode(['ok' => false, 'error' => 'Ürün bulunamadı']);
			exit;
		}
		$euro = isset($r['liste_fiyati_eur']) && $r['liste_fiyati_eur'] !== '' ? (float)$r['liste_fiyati_eur'] : 0.0;
		$doviz = 0.0;
		$tcmbPathAjax = __DIR__ . '/../../get-tcmb-euro-rate.php';
		if (is_file($tcmbPathAjax) && !function_exists('getTCMBEuroRate')) {
			require_once $tcmbPathAjax;
		}
		if (function_exists('getTCMBEuroRate')) {
			try {
				$dTmp = getTCMBEuroRate();
				if ($dTmp !== false && $dTmp !== null && (float)$dTmp > 0) {
					$doviz = (float)$dTmp;
				}
			} catch (Throwable $e) {
			}
		}
		if ($doviz <= 0 && isset($r['doviz_kuru']) && (float)$r['doviz_kuru'] > 0) {
			$doviz = (float)$r['doviz_kuru'];
		}
		if ($doviz <= 0) {
			$doviz = 35.0;
		}
		$liste_tl = $euro * $doviz;
		$kdvsiz = $liste_tl * (1 - $iskonto / 100);
		$netkdv = $kdvsiz * 1.20;
		$kredi = $netkdv;
		$pesin = $netkdv * 0.95;
		$up = $db->prepare('UPDATE urun SET iskonto_orani = ?, liste_fiyati_tl = ?, doviz_kuru = ?, kredi_karti_fiyati = ?, pesin_odeme_fiyati = ? WHERE id = ?');
		$ok = $up->execute([$iskonto, $liste_tl, $doviz, $kredi, $pesin, $uid]);
		if ($ok) {
			echo json_encode([
				'ok' => true,
				'iskonto_orani' => $iskonto,
				'net_fiyat_label' => $kredi > 0 ? fiyat($kredi) . ' TL' : '-',
			]);
		} else {
			echo json_encode(['ok' => false, 'error' => 'Veritabanı güncellenemedi']);
		}
		exit;
	}
	if ($action === 'sira') {
		$uid = isset($_POST['urun_id']) ? (int)$_POST['urun_id'] : 0;
		$siraRaw = isset($_POST['sira']) ? trim((string)$_POST['sira']) : '';
		$sira = $siraRaw === '' ? 9999 : (int)$siraRaw;
		if ($sira < 0) {
			$sira = 0;
		}
		if ($uid < 1) {
			echo json_encode(['ok' => false, 'error' => 'Geçersiz ürün']);
			exit;
		}
		try {
			$checkSiraCol = $db->query("SHOW COLUMNS FROM urun LIKE 'sira'")->fetch();
			if (!$checkSiraCol) {
				$db->exec('ALTER TABLE urun ADD COLUMN sira INT NOT NULL DEFAULT 9999');
			}
		} catch (Exception $e) {
			echo json_encode(['ok' => false, 'error' => 'Sıra kolonu oluşturulamadı']);
			exit;
		}
		$up = $db->prepare('UPDATE urun SET sira = ? WHERE id = ?');
		$ok = $up->execute([$sira, $uid]);
		if ($ok) {
			echo json_encode(['ok' => true, 'sira' => $sira]);
		} else {
			echo json_encode(['ok' => false, 'error' => 'Veritabanı güncellenemedi']);
		}
		exit;
	}
	if ($action === 'liste_euro') {
		if (!$ajaxHasListeEuro) {
			echo json_encode(['ok' => false, 'error' => 'liste_fiyati_eur kolonu yok']);
			exit;
		}
		$uid = isset($_POST['urun_id']) ? (int)$_POST['urun_id'] : 0;
		$euroRaw = isset($_POST['liste_euro']) ? str_replace(',', '.', trim((string)$_POST['liste_euro'])) : '';
		$euro = $euroRaw === '' ? 0.0 : (float)$euroRaw;
		if ($euro < 0) {
			$euro = 0.0;
		}
		if ($uid < 1) {
			echo json_encode(['ok' => false, 'error' => 'Geçersiz ürün']);
			exit;
		}
		$st = $db->prepare('SELECT id, iskonto_orani, doviz_kuru FROM urun WHERE id = ? LIMIT 1');
		$st->execute([$uid]);
		$r = $st->fetch(PDO::FETCH_ASSOC);
		if (!$r) {
			echo json_encode(['ok' => false, 'error' => 'Ürün bulunamadı']);
			exit;
		}
		$iskonto = isset($r['iskonto_orani']) && $r['iskonto_orani'] !== '' ? (float)$r['iskonto_orani'] : 0.0;
		if ($iskonto < 0) {
			$iskonto = 0.0;
		}
		if ($iskonto > 100) {
			$iskonto = 100.0;
		}
		$doviz = 0.0;
		$tcmbPathAjax = __DIR__ . '/../../get-tcmb-euro-rate.php';
		if (is_file($tcmbPathAjax) && !function_exists('getTCMBEuroRate')) {
			require_once $tcmbPathAjax;
		}
		if (function_exists('getTCMBEuroRate')) {
			try {
				$dTmp = getTCMBEuroRate();
				if ($dTmp !== false && $dTmp !== null && (float)$dTmp > 0) {
					$doviz = (float)$dTmp;
				}
			} catch (Throwable $e) {
				// TCMB hata verirse aşağıdaki yedek kur kullanılır
			}
		}
		if ($doviz <= 0 && isset($r['doviz_kuru']) && (float)$r['doviz_kuru'] > 0) {
			$doviz = (float)$r['doviz_kuru'];
		}
		if ($doviz <= 0) {
			$doviz = 35.0;
		}
		$liste_tl = $euro * $doviz;
		$kdvsiz = $liste_tl * (1 - $iskonto / 100);
		$netkdv = $kdvsiz * 1.20;
		$kredi = $netkdv;
		$pesin = $netkdv * 0.95;
		$up = $db->prepare('UPDATE urun SET liste_fiyati_eur = ?, liste_fiyati_tl = ?, doviz_kuru = ?, kredi_karti_fiyati = ?, pesin_odeme_fiyati = ? WHERE id = ?');
		$ok = $up->execute([$euro, $liste_tl, $doviz, $kredi, $pesin, $uid]);
		if ($ok) {
			echo json_encode([
				'ok' => true,
				'liste_fiyati_eur' => $euro,
				'net_fiyat_label' => $kredi > 0 ? fiyat($kredi) . ' TL' : '-',
			]);
		} else {
			echo json_encode(['ok' => false, 'error' => 'Veritabanı güncellenemedi']);
		}
		exit;
	}
	if ($action === 'baslik') {
		$uid = isset($_POST['urun_id']) ? (int)$_POST['urun_id'] : 0;
		$baslik = isset($_POST['baslik']) ? trim((string)$_POST['baslik']) : '';
		if ($uid < 1) {
			echo json_encode(['ok' => false, 'error' => 'Geçersiz ürün']);
			exit;
		}
		if ($baslik === '') {
			echo json_encode(['ok' => false, 'error' => 'Ürün adı boş olamaz']);
			exit;
		}
		$sef = sef($baslik) . '-' . $uid;
		$up = $db->prepare('UPDATE urun SET baslik = ?, sef = ? WHERE id = ? LIMIT 1');
		$ok = $up->execute([$baslik, $sef, $uid]);
		if ($ok) {
			echo json_encode(['ok' => true, 'baslik' => $baslik, 'sef' => $sef]);
		} else {
			echo json_encode(['ok' => false, 'error' => 'Veritabanı güncellenemedi']);
		}
		exit;
	}
	if ($action === 'kapak_resim') {
		$uid = isset($_POST['urun_id']) ? (int)$_POST['urun_id'] : 0;
		if ($uid < 1 || empty($_FILES['kapak']['name']) || !isset($_FILES['kapak']['tmp_name']) || !is_uploaded_file($_FILES['kapak']['tmp_name'])) {
			echo json_encode(['ok' => false, 'error' => 'Dosya yüklenemedi']);
			exit;
		}
		$pi = pathinfo($_FILES['kapak']['name']);
		$ext = isset($pi['extension']) ? strtolower($pi['extension']) : '';
		$allowed = ['jpg', 'jpeg', 'png', 'gif', 'svg', 'webp'];
		if (!in_array($ext, $allowed, true)) {
			echo json_encode(['ok' => false, 'error' => 'Geçersiz dosya türü']);
			exit;
		}
		$uploadResult = media_upload_product_image($_FILES['kapak']['tmp_name'], $ext);
		if (empty($uploadResult['ok']) || empty($uploadResult['value'])) {
			echo json_encode(['ok' => false, 'error' => 'Kayıt başarısız']);
			exit;
		}
		$storedValue = (string)$uploadResult['value'];
		$stImg = $db->prepare('SELECT id, img FROM urun_img WHERE urun_id = ? ORDER BY id ASC LIMIT 1');
		$stImg->execute([$uid]);
		$imgRow = $stImg->fetch(PDO::FETCH_ASSOC);
		if ($imgRow && !empty($imgRow['id'])) {
			$oldImgName = isset($imgRow['img']) ? (string)$imgRow['img'] : '';
			$upi = $db->prepare('UPDATE urun_img SET img = ? WHERE id = ?');
			$upi->execute([$storedValue, (int)$imgRow['id']]);
			if ($oldImgName !== '') {
				urun_resim_dosya_sil_if_orphan($db, $oldImgName);
			}
		} else {
			$ins = $db->prepare('INSERT INTO urun_img SET urun_id = ?, img = ?');
			$ins->execute([$uid, $storedValue]);
		}
		echo json_encode(['ok' => true, 'src' => media_panel_url($storedValue)]);
		exit;
	}
	echo json_encode(['ok' => false, 'error' => 'Bilinmeyen işlem']);
	exit;
}

if(isset($_GET['sil_id'])){
	$silinecekUrunId = (int)$_GET['sil_id'];
	$eskiResimler = [];
	$oldImgQ = $db->prepare("SELECT img FROM urun_img WHERE urun_id = ?");
	$oldImgQ->execute([$silinecekUrunId]);
	$eskiResimler = $oldImgQ->fetchAll(PDO::FETCH_COLUMN);

    $delete = $db->exec("DELETE FROM urun WHERE id = '{$_GET['sil_id']}' LIMIT 1");

    $delete = $db->exec("DELETE FROM urun_kategori WHERE urun_id = '{$_GET['sil_id']}' ");
    $delete = $db->exec("DELETE FROM urun_renk WHERE urun_id = '{$_GET['sil_id']}'");
    $delete = $db->exec("DELETE FROM urun_img WHERE urun_id = '{$_GET['sil_id']}'");
    $delete = $db->exec("DELETE FROM vitrin_urun WHERE urun_id = '{$_GET['sil_id']}'");

    $query = $db->query("SELECT * FROM urun_secenek WHERE urun_id = '{$_GET['sil_id']}' ", PDO::FETCH_ASSOC);
	 if($query->rowCount()){
	    foreach( $query as $row ){

	    	$delete = $db->exec("DELETE FROM urun_secenek_alt WHERE urun_secenek_id = '{$row['id']}'");
	    	$delete = $db->exec("DELETE FROM urun_secenek WHERE id = '{$row['id']}'");

	    }
	 }
	if (!empty($eskiResimler)) {
		foreach ($eskiResimler as $oldImg) {
			urun_resim_dosya_sil_if_orphan($db, $oldImg);
		}
	}
    echo b();
}


$savedFilter = isset($_SESSION['urunler_filter']) && is_array($_SESSION['urunler_filter']) ? $_SESSION['urunler_filter'] : [];
$allowedLimits = [50, 100, 200];
if ($_POST) {
	if (isset($_POST['clear_filter']) && $_POST['clear_filter'] == '1') {
		unset($_SESSION['urunler_filter']);
		$sortType = 'yeni';
		$searchTerm = '';
		$pageLimit = 50;
	} else {
	$postedSort = isset($_POST['siralama']) ? trim((string)$_POST['siralama']) : '';
	$postedSearch = isset($_POST['ara']) ? trim((string)$_POST['ara']) : '';
	$postedLimit = isset($_POST['limit']) ? (int)$_POST['limit'] : 0;
	$sortType = $postedSort !== '' ? $postedSort : (isset($savedFilter['siralama']) ? $savedFilter['siralama'] : 'yeni');
	$searchTerm = $postedSearch;
	$pageLimit = in_array($postedLimit, $allowedLimits, true) ? $postedLimit : (isset($savedFilter['limit']) && in_array((int)$savedFilter['limit'], $allowedLimits, true) ? (int)$savedFilter['limit'] : 50);
	$_SESSION['urunler_filter'] = [
		'siralama' => $sortType,
		'ara' => $searchTerm,
		'limit' => $pageLimit
	];
	}
} else {
	$sortType = isset($savedFilter['siralama']) && $savedFilter['siralama'] !== '' ? $savedFilter['siralama'] : 'yeni';
	$searchTerm = isset($savedFilter['ara']) ? trim((string)$savedFilter['ara']) : '';
	$pageLimit = isset($savedFilter['limit']) && in_array((int)$savedFilter['limit'], $allowedLimits, true) ? (int)$savedFilter['limit'] : 50;
}
$selectedCategoryId = 0;
if (strpos($sortType, 'kategori_id_') === 0) {
	$selectedCategoryId = (int)str_replace('kategori_id_', '', $sortType);
}

$kategoriSecenekleri = $db->query("SELECT id, baslik FROM kategori ORDER BY baslik ASC", PDO::FETCH_ASSOC)->fetchAll(PDO::FETCH_ASSOC);
if (!is_array($kategoriSecenekleri)) {
	$kategoriSecenekleri = [];
}

$countSql = "SELECT COUNT(DISTINCT u.id) FROM urun u";
$countWhere = [];
$countParams = [];
if ($selectedCategoryId > 0) {
	$countSql .= " INNER JOIN urun_kategori ukf ON ukf.urun_id = u.id";
	$countWhere[] = "ukf.kategori_id = ?";
	$countParams[] = $selectedCategoryId;
}
if ($searchTerm !== '') {
	$countWhere[] = "(u.baslik LIKE ? OR u.stok_kodu LIKE ?)";
	$countParams[] = "%{$searchTerm}%";
	$countParams[] = "%{$searchTerm}%";
}
if (!empty($countWhere)) {
	$countSql .= " WHERE " . implode(' AND ', $countWhere);
}
$sorgusay = $db->prepare($countSql);
$sorgusay->execute($countParams);
$say = $sorgusay->fetchColumn();

$top_sayfa = $say;
$page      = isset($_GET['no']) ? (int)$_GET['no'] : 1;
if ($_POST) {
	$page = 1;
}
if ($page < 1) {
	$page = 1;
}
$limit     = $pageLimit;
$page_url  = $sayfa.'/';
$baslangic = ($page * $limit) - $limit;

try {
	$checkSiraColMain = $db->query("SHOW COLUMNS FROM urun LIKE 'sira'")->fetch();
	if (!$checkSiraColMain) {
		$db->exec('ALTER TABLE urun ADD COLUMN sira INT NOT NULL DEFAULT 9999');
	}
	$urunlerHasSira = true;
} catch (Exception $e) {
	$urunlerHasSira = false;
}

// PERFORMANS: Sira yeniden hesaplama ARTIK sayfa acilisinda calismiyor.
// Eskiden cogu urunun sira=9999 olmasi durumunda her acilista tum tablo tek tek
// UPDATE ediliyordu (binlerce sorgu = asiri yavas yukleme). Toplu yeniden
// numaralandirma icin sayfadaki "Sirayi Topluca Guncelle" aksiyonu kullanilir
// (AJAX action=sira_toplu -> urunler_recalc_sira_by_yukleme_tarihi).

// PERFORMANS: Liste sorgularinin dayandigi indexleri (yoksa) bir kez olustur.
try {
	$ensureIndexes = array(
		'urun'          => array(
			'idx_urun_sira' => 'sira',
			'idx_urun_id' => 'id',
			'idx_urun_baslik' => 'baslik(100)',
			'idx_urun_stok_kodu' => 'stok_kodu(50)',
		),
		'urun_img'      => array('idx_urunimg_urunid' => 'urun_id'),
		'urun_kategori' => array(
			'idx_uk_urunid' => 'urun_id',
			'idx_uk_katid' => 'kategori_id',
			'idx_uk_urunid_katid' => 'urun_id,kategori_id'
		),
	);
	foreach ($ensureIndexes as $tbl => $idxs) {
		foreach ($idxs as $idxName => $col) {
			$idxExists = $db->query("SHOW INDEX FROM {$tbl} WHERE Key_name = '{$idxName}'")->fetch();
			if (!$idxExists) {
				$db->exec("ALTER TABLE {$tbl} ADD INDEX {$idxName} ({$col})");
			}
		}
	}
} catch (Exception $e) {
}
?>

<div class="breadcrumb-header justify-content-between">
	<div class="my-auto">
		<div class="d-flex">
			<h4 class="content-title mb-0 my-auto">Ürünler</h4><span class="text-muted mt-1 tx-13 ml-2 mb-0">/ Listele</span>
		</div>
	</div>
	<div class="my-auto">
		<button type="button" id="btnUpdateStocks" class="btn btn-primary btn-sm" title="Stokları güncellemek için tıklayın">
			<i class="fa fa-sync-alt"></i> Stokları Güncelle
		</button>
		<button type="button" id="btnRecalcSira" class="btn btn-secondary btn-sm ml-2" title="Sira numaralarini yukleme tarihine gore guncelle (en yeni = 1)">
			<i class="fa fa-sort-numeric-down"></i> Siralari Guncelle
		</button>
		<button type="button" id="btnTranslateAllProducts" class="btn btn-info btn-sm ml-2" title="Tum urunleri otomatik cevir">
			<i class="fa fa-language"></i> Hepsini Otomatik Cevir
		</button>
		<button type="button" id="btnSeoOptimizeAllProducts" class="btn btn-success btn-sm ml-2" title="Eski formattaki urun adlarini SEO formatina cevirir">
			<i class="fa fa-magic"></i> SEO Isimleri Duzenle
		</button>
	</div>
</div>

<style>
	.main-content.horizontal-content > .container {
		max-width: 100% !important;
		width: 100% !important;
		padding-left: 12px;
		padding-right: 12px;
	}
	#example1 {
		width: 100% !important;
		table-layout: auto;
	}
	#example1 thead th,
	#example1 tbody td {
		font-size: 12px;
		padding: 8px 10px;
		vertical-align: middle;
	}
	#example1 thead th:nth-child(2),
	#example1 tbody td:nth-child(2) {
		width: 52px;
		min-width: 52px;
		max-width: 64px;
		padding: 6px 4px;
		text-align: center;
		white-space: nowrap !important;
	}
	#example1 thead th:nth-child(3),
	#example1 tbody td:nth-child(3) {
		min-width: 520px;
		width: 42%;
		white-space: normal !important;
		word-break: break-word;
	}
	#example1 .js-inline-sira {
		width: 48px;
		min-width: 48px;
		max-width: 56px;
		padding: 2px 4px;
		text-align: center;
		display: inline-block;
	}
	#example1 .js-inline-baslik {
		width: 100%;
		min-width: 500px;
		max-width: none;
		box-sizing: border-box;
	}
	#example1 tbody td:nth-child(7) {
		min-width: 220px;
		white-space: normal !important;
		word-break: break-word;
	}
	#example1 tbody td:last-child,
	#example1 thead th:last-child {
		min-width: 120px;
		text-align: center;
	}
	.urun-thumb-drop {
		cursor: pointer;
		border: 2px dashed transparent;
		border-radius: 6px;
		padding: 4px;
		transition: border-color 0.15s, background 0.15s;
		max-width: 56px;
	}
	.urun-thumb-drop:hover {
		border-color: #adb5bd;
		background: #f8f9fa;
	}
	.urun-thumb-drop.is-dragover {
		border-color: #28a745;
		background: #e8f5e9;
	}
</style>

<div class="row" style="margin: 0;">
	<div class="col-md-12" style="padding: 0;">
		<div class="card" style="margin: 0;">
			<div class="card-body" style="padding: 15px; width: 100%;">
				<div class="row" style="margin: 0;">
					<div class="col-md-12" style="padding: 0;">
						<div class="order-table" style="width: 100%;">
							<div class="table-responsive" style="width: 100%; overflow-x: auto; margin: 0; padding: 0;">
								<form action="<?php echo $sayfa; ?>/1" method="post" style="margin-bottom: 30px">
									<div class="row">
										<div class="col-md-4">
											<label class="mb-1" style="font-weight:600;">Ürün Ara</label>
											<input type="text" name="ara" placeholder="Ürün adı veya kodu ile arayın" class="form-control" value="<?php echo htmlspecialchars($searchTerm, ENT_QUOTES, 'UTF-8'); ?>">
										</div>
										<div class="col-md-3">
											<label class="mb-1" style="font-weight:600;">Sıralama</label>
											<select name="siralama" class="form-control">
												<option value="yeni" <?php echo $sortType === 'yeni' ? 'selected' : ''; ?>>En Yeni (varsayılan)</option>
												<option value="eski" <?php echo $sortType === 'eski' ? 'selected' : ''; ?>>En Eski</option>
												<option value="sira_asc" <?php echo $sortType === 'sira_asc' ? 'selected' : ''; ?>>Sıra (Artan)</option>
												<option value="sira_desc" <?php echo $sortType === 'sira_desc' ? 'selected' : ''; ?>>Sıra (Azalan)</option>
												<option value="kategori_asc" <?php echo $sortType === 'kategori_asc' ? 'selected' : ''; ?>>Kategori (A-Z)</option>
												<option value="kategori_desc" <?php echo $sortType === 'kategori_desc' ? 'selected' : ''; ?>>Kategori (Z-A)</option>
												<?php if(!empty($kategoriSecenekleri)){ ?>
													<optgroup label="Kategoriler">
														<?php foreach($kategoriSecenekleri as $kategoriSecenek){ ?>
															<option value="kategori_id_<?php echo (int)$kategoriSecenek['id']; ?>" <?php echo $sortType === 'kategori_id_'.(int)$kategoriSecenek['id'] ? 'selected' : ''; ?>>
																<?php echo htmlspecialchars($kategoriSecenek['baslik'], ENT_QUOTES, 'UTF-8'); ?>
															</option>
														<?php } ?>
													</optgroup>
												<?php } ?>
											</select>
										</div>
										<div class="col-md-2">
											<label class="mb-1" style="font-weight:600;">Sayfa başına</label>
											<select name="limit" class="form-control">
												<?php foreach ($allowedLimits as $limitOption) { ?>
													<option value="<?php echo $limitOption; ?>" <?php echo $pageLimit === $limitOption ? 'selected' : ''; ?>><?php echo $limitOption; ?> ürün</option>
												<?php } ?>
											</select>
										</div>
										<div class="col-md-1">
											<label class="mb-1" style="font-weight:600;">&nbsp;</label>
											<button type="submit" class="btn btn-success" style="width: 100%">Ara</button>
										</div>
										<div class="col-md-2">
											<label class="mb-1" style="font-weight:600;">&nbsp;</label>
											<button type="submit" name="clear_filter" value="1" class="btn btn-secondary" style="width: 100%">Sıfırla</button>
										</div>
									</div>
								</form>
								<?php if ($selectedCategoryId > 0) { ?>
								<div class="alert alert-info py-2 mb-3" style="font-size:13px;">
									<strong>Kategori filtresi aktif.</strong>
									Toplam <?php echo (int)$say; ?> ürün bulundu — sayfa başına <?php echo (int)$pageLimit; ?> ürün gösteriliyor.
									<strong>Sıra</strong> sütunundaki numaralar müşteri sitesindeki kategori sıralamasını belirler (küçük sayı önce).
								</div>
								<?php } ?>
								<table id="example1" class="table table-striped table-bordered text-nowrap mb-0" style="width: 100%; margin: 0;">
									<thead>
										<tr class="bold border-bottom">
											<th class="border-bottom-0" title="Kapak: tıklayın veya görseli sürükleyin">Resim</th>
											<th class="border-bottom-0" title="Küçük sayı önce görünür">Sıra</th>
											<th class="border-bottom-0">Ürün Adı</th>
											<th class="border-bottom-0">Ürün kodu</th>
											<th class="border-bottom-0" title="Liste fiyatı (Euro); değişince kaydedilir">Fiyat (Euro)</th>
											<th class="border-bottom-0" title="İskonto oranı (%); tıklayıp düzenleyin">İskonto Oranı</th>
											<th class="border-bottom-0">Net Fiyat</th>
											<th class="border-bottom-0">Stok</th>
											<th class="border-bottom-0">Kategorileri</th>
											<th class="border-bottom-0">Ürün Yönetimi</th>
										</tr>
									</thead>
									<tbody>
										<?php
										  // Depo stok sütunlarının varlığını kontrol et
										  try {
										  	$checkColumns = $db->query("SHOW COLUMNS FROM urun LIKE 'maslak_stok'")->fetch();
										  	$hasWarehouseColumns = ($checkColumns !== false);
										  } catch (Exception $e) {
										  	$hasWarehouseColumns = false;
										  }
										  try {
										  	$checkListeEuroCol = $db->query("SHOW COLUMNS FROM urun LIKE 'liste_fiyati_eur'")->fetch();
										  	$urunlerHasListeEuro = ($checkListeEuroCol !== false);
										  } catch (Exception $e) {
										  	$urunlerHasListeEuro = false;
										  }
										  
										  // SELECT sorgusunu hazırla - depo stok sütunlarını da dahil et
										  if($hasWarehouseColumns){
										  	$selectFields = "*, maslak_stok, bolu_stok, imes_stok, ankara_stok, ikitelli_stok";
										  }else{
										  	$selectFields = "*";
										  }
										  
										  // Siralama tipi
										  $categoryDir = ($sortType === 'kategori_desc') ? 'DESC' : 'ASC';
										  // ks (kategori siralama) alt-sorgusu yalnizca kategori sortunda gerekli.
										  // Diger durumlarda bu agir GROUP BY JOIN'i hic kurulmaz.
										  $ksJoin = '';
										  if ($sortType === 'yeni') {
										  	$orderSql = "u.id DESC";
										  } elseif ($sortType === 'eski') {
										  	$orderSql = "u.id ASC";
										  } elseif ($sortType === 'sira_desc') {
										  	$orderSql = "u.sira DESC, u.baslik ASC, u.id DESC";
										  } elseif ($sortType === 'sira_asc' || $selectedCategoryId > 0) {
										  	$orderSql = "u.sira ASC, u.baslik ASC, u.id DESC";
										  } else {
										  	$ksJoin = " LEFT JOIN ( SELECT uk.urun_id, MIN(k.baslik) AS kategori_siralama FROM urun_kategori uk INNER JOIN kategori k ON k.id = uk.kategori_id GROUP BY uk.urun_id ) ks ON ks.urun_id = u.id ";
										  	$orderSql = "CASE WHEN ks.kategori_siralama IS NULL OR ks.kategori_siralama = '' THEN 1 ELSE 0 END, ks.kategori_siralama {$categoryDir}, u.sira ASC, u.baslik ASC, u.id DESC";
										  }
										  // PERFORMANS: Kategori sortunda index yok mu ekle
										  if (($sortType === 'kategori_asc' || $sortType === 'kategori_desc') && !isset($_SESSION['urunler_idx_created'])) {
										  	try {
										  		$db->query("ALTER TABLE urun_kategori ADD INDEX IF NOT EXISTS idx_uk_urunid_katid (urun_id, kategori_id)");
										  		$_SESSION['urunler_idx_created'] = true;
										  	} catch (Exception $e) {}
										  }
										  $kategoriFilterJoin = '';
										  $kategoriFilterWhere = '';
										  if ($selectedCategoryId > 0) {
										  	$kategoriFilterJoin = " INNER JOIN urun_kategori ukf ON ukf.urun_id = u.id ";
										  	$kategoriFilterWhere = " AND ukf.kategori_id = {$selectedCategoryId} ";
										  }

										  // Siralamaya gore once id listesini cek, sonra ayni sirayla urunleri getir
										  // PERFORMANS: Hem arama hem non-arama sorgulerinde LIMIT uygula
										  if($searchTerm !== ''){
										  	$ara = $searchTerm;
										  	$araEscaped = str_replace(["\\", "'"], ["\\\\", "\\'"], $ara);
										  	$idRows = $db->query("
										  		SELECT u.id
										  		FROM urun u
										  		{$kategoriFilterJoin}
										  		{$ksJoin}
										  		WHERE (u.baslik LIKE '%{$araEscaped}%' OR u.stok_kodu LIKE '%{$araEscaped}%')
										  		{$kategoriFilterWhere}
										  		ORDER BY {$orderSql}
										  		LIMIT {$baslangic},{$limit}
										  	", PDO::FETCH_ASSOC);
										  }else{
										  	$idRows = $db->query("
										  		SELECT u.id
										  		FROM urun u
										  		{$kategoriFilterJoin}
										  		{$ksJoin}
										  		WHERE 1=1
										  		{$kategoriFilterWhere}
										  		ORDER BY {$orderSql}
										  		LIMIT {$baslangic},{$limit}
										  	", PDO::FETCH_ASSOC);
										  }

										  $query = false;
										  if ($idRows && $idRows->rowCount()) {
										  	$orderedIds = [];
										  	foreach ($idRows as $idRow) {
										  		$orderedIds[] = (int)$idRow['id'];
										  	}
										  	if (!empty($orderedIds)) {
										  		$idList = implode(',', $orderedIds);
										  		$query = $db->query("SELECT {$selectFields} FROM urun WHERE id IN ({$idList}) ORDER BY FIELD(id, {$idList})", PDO::FETCH_ASSOC);
										  	}
										  }

										  // Yedek: bir sey ters giderse klasik siralama
										  // PERFORMANS: Arama sorgularinda da LIMIT uygula
										  if ($query === false) {
										  	if($searchTerm !== ''){
										  		$ara = $searchTerm;
										  		if ($selectedCategoryId > 0) {
										  			$query = $db->query("SELECT {$selectFields} FROM urun WHERE id IN (SELECT urun_id FROM urun_kategori WHERE kategori_id = '{$selectedCategoryId}') AND (baslik LIKE '%{$ara}%' OR stok_kodu LIKE '%{$ara}%') ORDER BY sira ASC, baslik ASC, id DESC LIMIT {$baslangic},{$limit}", PDO::FETCH_ASSOC);
										  		} else {
										  			$query = $db->query("SELECT {$selectFields} FROM urun WHERE baslik LIKE '%{$ara}%' OR stok_kodu LIKE '%{$ara}%' ORDER BY id DESC LIMIT {$baslangic},{$limit}", PDO::FETCH_ASSOC);
										  		}
										  	}else{
										  		if ($selectedCategoryId > 0) {
										  			$query = $db->query("SELECT {$selectFields} FROM urun WHERE id IN (SELECT urun_id FROM urun_kategori WHERE kategori_id = '{$selectedCategoryId}') ORDER BY sira ASC, baslik ASC, id DESC LIMIT {$baslangic},{$limit}", PDO::FETCH_ASSOC);
										  		} else {
										  			$query = $db->query("SELECT {$selectFields} FROM urun ORDER BY id DESC LIMIT {$baslangic},{$limit}", PDO::FETCH_ASSOC);
										  		}
										  	}
										  }
					                      if($query && $query->rowCount()){
					                        // PERFORMANS: Tum satirlari bir kez al, sonra resim ve kategorileri
					                        // satir-basina-sorgu (N+1) yerine TEK sorguda toplu cek.
					                        $rows = $query->fetchAll(PDO::FETCH_ASSOC);
					                        $rowIds = array();
					                        foreach ($rows as $r) { $rowIds[] = (int)$r['id']; }
					                        $imgMap = array();
					                        $katMap = array();
					                        if (!empty($rowIds)) {
					                        	$inIds = implode(',', $rowIds);
					                        	// Her urun icin ilk (en kucuk id'li) resim
					                        	$imgStmt = $db->query("SELECT ui.* FROM urun_img ui INNER JOIN (SELECT urun_id, MIN(id) AS mid FROM urun_img WHERE urun_id IN ({$inIds}) GROUP BY urun_id) t ON t.mid = ui.id", PDO::FETCH_ASSOC);
					                        	if ($imgStmt) { foreach ($imgStmt as $ir) { $imgMap[(int)$ir['urun_id']] = $ir; } }
					                        	// Tum kategoriler tek sorguda
					                        	$katStmt = $db->query("SELECT uk.urun_id, k.baslik FROM urun_kategori uk INNER JOIN kategori k ON k.id = uk.kategori_id WHERE uk.urun_id IN ({$inIds}) ORDER BY k.baslik ASC", PDO::FETCH_ASSOC);
					                        	if ($katStmt) { foreach ($katStmt as $kr) { $katMap[(int)$kr['urun_id']][] = $kr['baslik']; } }
					                        }
					                        foreach( $rows as $row ){

					                        $rowId = (int)$row['id'];
					                        $img = isset($imgMap[$rowId]) ? $imgMap[$rowId] : false;

					                        // Kategori bilgisini al (toplu cekilen haritadan)
					                        $kategoriler = isset($katMap[$rowId]) ? $katMap[$rowId] : array();
					                        $kategori_text = !empty($kategoriler) ? implode(', ', $kategoriler) : 'Kategori Yok';
					                        
					                        $img_src = isset($img['img']) ? media_panel_url($img['img']) : media_panel_url('');
					                        $baslik_raw = isset($row['baslik']) ? trim((string)$row['baslik']) : '';
					                        $stok_kodu_raw = isset($row['stok_kodu']) ? trim((string)$row['stok_kodu']) : '';
					                        $baslik_cell = '<div class="input-group input-group-sm">'
					                        	. '<input type="text" class="form-control form-control-sm js-inline-baslik" data-urun-id="'.(int)$row['id'].'" data-stok-kodu="'.htmlspecialchars($stok_kodu_raw, ENT_QUOTES, 'UTF-8').'" value="'.htmlspecialchars($baslik_raw, ENT_QUOTES, 'UTF-8').'" title="Urun adi — Enter veya alan disina tiklayinca kaydedilir" />'
					                        	. '<div class="input-group-append">'
					                        		. '<button type="button" class="btn btn-outline-success btn-sm js-seo-baslik-btn" data-urun-id="'.(int)$row['id'].'" title="SEO isim oner"><i class="fa fa-magic"></i></button>'
					                        		. '<button type="button" class="btn btn-outline-info btn-sm js-translate-baslik-btn" data-urun-id="'.(int)$row['id'].'" title="Bu urunu tum dillere otomatik cevir"><i class="fa fa-language"></i></button>'
					                        		. '<button type="button" class="btn btn-outline-warning btn-sm js-tecdoc-sync-btn" data-urun-id="'.(int)$row['id'].'" data-stok-kodu="'.htmlspecialchars($stok_kodu_raw, ENT_QUOTES, 'UTF-8').'" title="TecDoc OEM kodlari ve gorselleri cek"><i class="fa fa-cloud-download"></i></button>'
					                        		. '<button type="button" class="btn btn-outline-secondary btn-sm js-kms-referans-btn" data-urun-id="'.(int)$row['id'].'" data-stok-kodu="'.htmlspecialchars($stok_kodu_raw, ENT_QUOTES, 'UTF-8').'" title="KMotorShop OEM referans + gorsel cek"><i class="fa fa-link"></i></button>'
					                        	. '</div>'
					                        	. '<span class="js-row-action-marks" data-urun-id="'.(int)$row['id'].'" style="display:inline-flex;align-items:center;gap:3px;margin-left:6px;"></span>'
					                        	. '</div>';
					                        $stok_kodu_html = $stok_kodu_raw !== '' ? htmlspecialchars($stok_kodu_raw, ENT_QUOTES, 'UTF-8') : '-';
					                        $stok_kodu_attr = $stok_kodu_raw !== '' ? htmlspecialchars($stok_kodu_raw, ENT_QUOTES, 'UTF-8') : '';
					                        $euro_raw = (isset($row['liste_fiyati_eur']) && $row['liste_fiyati_eur'] !== '' && $row['liste_fiyati_eur'] !== null) ? (float)$row['liste_fiyati_eur'] : null;
					                        $fiyat_euro_display = $euro_raw !== null ? number_format($euro_raw, 2, ',', '.') . ' EUR' : '-';
					                        if ($urunlerHasListeEuro) {
					                        	$euro_input_val = $euro_raw !== null ? htmlspecialchars((string)$euro_raw, ENT_QUOTES, 'UTF-8') : '';
					                        	$fiyat_euro_cell = '<input type="number" step="0.01" min="0" class="form-control form-control-sm js-inline-liste-euro" style="min-width:100px;max-width:130px;display:inline-block;" data-urun-id="'.(int)$row['id'].'" value="'.$euro_input_val.'" title="Liste fiyatı (Euro) — Enter veya alan dışına tıklayınca kaydedilir" />';
					                        } else {
					                        	$fiyat_euro_cell = $fiyat_euro_display;
					                        }
					                        $iskonto_raw = (isset($row['iskonto_orani']) && $row['iskonto_orani'] !== '' && $row['iskonto_orani'] !== null) ? (float)$row['iskonto_orani'] : null;
					                        $iskonto_input_val = $iskonto_raw !== null ? htmlspecialchars((string)$row['iskonto_orani'], ENT_QUOTES, 'UTF-8') : '';
					                        $eryaz_stok_kodu_raw = isset($row['eryaz_stok_kodu']) ? trim((string)$row['eryaz_stok_kodu']) : '';
					                        $bosch_iskonto = urunler_bosch_iskonto_kurali($eryaz_stok_kodu_raw !== '' ? $eryaz_stok_kodu_raw : $stok_kodu_raw);
					                        $bosch_badge = '';
					                        $iskonto_warn_style = '';
					                        if ($bosch_iskonto !== null) {
					                        	$bosch_label = rtrim(rtrim(number_format($bosch_iskonto, 2, ',', '.'), '0'), ',') . '% Bosch';
					                        	$bosch_badge_class = ($iskonto_raw !== null && abs((float)$iskonto_raw - $bosch_iskonto) < 0.001) ? 'badge-success' : 'badge-warning';
					                        	$bosch_badge = '<div class="mt-1"><span class="badge '.$bosch_badge_class.'" title="Stok kodu kuralı: 30-/31-/32- = %20, 3e- = %10">'.$bosch_label.'</span></div>';
					                        	if ($bosch_badge_class === 'badge-warning') {
					                        		$iskonto_warn_style = 'border-color:#ffc107;background:#fff8e1;';
					                        	}
					                        }
					                        $iskonto_cell = '<input type="number" step="0.01" min="0" max="100" class="form-control form-control-sm js-inline-iskonto" style="min-width:70px;max-width:90px;display:inline-block;'.$iskonto_warn_style.'" data-urun-id="'.(int)$row['id'].'" value="'.$iskonto_input_val.'" placeholder="0" title="İskonto oranı (%) — Enter veya alan dışına tıklayınca kaydedilir" />'.$bosch_badge;
					                        $sira_raw = isset($row['sira']) && $row['sira'] !== '' && $row['sira'] !== null ? (int)$row['sira'] : 0;
					                        $sira_cell = '<input type="number" step="1" min="0" class="form-control form-control-sm js-inline-sira" data-urun-id="'.(int)$row['id'].'" value="'.(int)$sira_raw.'" title="Sıra numarası — küçük sayı önce görünür" />';
					                        $net_fiyat_degeri = 0;
					                        if (isset($row['kredi_karti_fiyati']) && (float)$row['kredi_karti_fiyati'] > 0) {
					                        	$net_fiyat_degeri = (float)$row['kredi_karti_fiyati'];
					                        } elseif (isset($row['fiyat']) && (float)$row['fiyat'] > 0) {
					                        	$net_fiyat_degeri = (float)$row['fiyat'];
					                        }
					                        $net_fiyat_text = $net_fiyat_degeri > 0 ? fiyat($net_fiyat_degeri) . ' TL' : '-';
					                        $stok_text = (isset($row['stok']) && $row['stok'] !== '') ? $row['stok'] : '-';
					                        $img_src_esc = htmlspecialchars($img_src, ENT_QUOTES, 'UTF-8');
					                          echo '
					                            <tr data-urun-row-id="'.(int)$row['id'].'"'.($stok_kodu_attr !== '' ? ' data-stok-kodu="'.$stok_kodu_attr.'"' : '').'>
					                              <td class="align-middle"><div class="urun-thumb-drop js-urun-thumb-drop" data-urun-id="'.(int)$row['id'].'" title="Kapak görseli: dosyayı buraya sürükleyin"><img class="js-urun-thumb-img" src="'.$img_src_esc.'" alt="" style="width:42px;height:42px;border:1px solid #ddd;padding:2px;object-fit:cover;pointer-events:none;display:block;margin:0 auto;"></div></td>
					                              <td class="align-middle">'.$sira_cell.'</td>
					                              <td class="align-middle">'.$baslik_cell.'</td>
					                              <td class="align-middle text-monospace" style="font-size:0.9em;">'.$stok_kodu_html.'</td>
					                              <td class="align-middle">'.$fiyat_euro_cell.'</td>
					                              <td class="align-middle">'.$iskonto_cell.'</td>
					                              <td class="js-net-fiyat-cell align-middle">'.$net_fiyat_text.'</td>
					                              <td>'.$stok_text.'</td>
					                              <td>'.$kategori_text.'</td>
					                              <td>
					                              	<a href="urun/duzenle/'.$row['id'].'" data-toggle="tooltip" data-original-title="Düzenle"><svg class="svg-icon mr-2" xmlns="http://www.w3.org/2000/svg" height="20" viewBox="0 0 24 24" width="20"><path d="M0 0h24v24H0V0z" fill="none"/><path d="M14.06 9.02l.92.92L5.92 19H5v-.92l9.06-9.06M17.66 3c-.25 0-.51.1-.7.29l-1.83 1.83 3.75 3.75 1.83-1.83c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.2-.2-.45-.29-.71-.29zm-3.6 3.19L3 17.25V21h3.75L17.81 9.94l-3.75-3.75z"/></svg></a>
													<a href="'.$sayfa.'/sil/'.$row['id'].'" data-toggle="tooltip" data-original-title="Sil" onclick="return confirm(\'Bu ürünü silmek istediğinize emin misiniz?\');"><svg class="svg-icon" xmlns="http://www.w3.org/2000/svg" height="20" viewBox="0 0 24 24" width="20"><path d="M0 0h24v24H0V0z" fill="none"/><path d="M8 9h8v10H8z" opacity=".3"/><path d="M15.5 4l-1-1h-5l-1 1H5v2h14V4zM6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM8 9h8v10H8V9z"/></svg></a>
					                              </td>
					                            </tr>
					                          ';
					                        }
					                      }else{
					                      	echo '<tr><td colspan="10"><center><h2>Ürün bulunamadı.</h2></center></td></tr>';
					                      }
					                    ?>
									</tbody>
								</table>
							</div>
						</div>
						<div class="pagination-wrapper" style="margin-bottom: 10px;float: right;">
							<nav aria-label="Page navigation">
								<ul class="pagination mb-0">
									<?php Sayfala($top_sayfa,$page,$limit,$page_url); ?>
								</ul>
							</nav>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

<script>
// Depo stok bilgilerini AJAX ile yükle (Cache ile)
(function() {
	const CACHE_KEY = 'eryaz_warehouse_stock_cache';
	const CACHE_TIMESTAMP_KEY = 'eryaz_warehouse_stock_cache_time';
	const CACHE_DURATION = 30 * 60 * 1000; // 30 dakika (1800000 ms)
	
	// Tüm ürün stok kodlarını topla
	const stockCodes = [];
	document.querySelectorAll('[data-stok-kodu]').forEach(function(el) {
		const stokKodu = el.getAttribute('data-stok-kodu');
		if (stokKodu && stokKodu.trim() !== '') {
			stockCodes.push(stokKodu.trim());
		}
	});
	
	if (stockCodes.length === 0) {
		return; // Stok kodu yoksa çık
	}
	
	// Cache'den kontrol et
	let cachedData = null;
	let cacheTime = null;
	let useCache = false;
	
	try {
		const cachedDataStr = localStorage.getItem(CACHE_KEY);
		const cacheTimeStr = localStorage.getItem(CACHE_TIMESTAMP_KEY);
		
		if (cachedDataStr && cacheTimeStr) {
			cachedData = JSON.parse(cachedDataStr);
			cacheTime = parseInt(cacheTimeStr);
			
			// Cache hala geçerli mi kontrol et
			const now = Date.now();
			if (now - cacheTime < CACHE_DURATION) {
				// Cache geçerli, hemen göster
				useCache = true;
				updateWarehouseStocks(cachedData);
			}
		}
	} catch (e) {
		console.error('Cache okuma hatası:', e);
	}
	
	// Cache yok veya eski - sadece cache'den göster, API çağrısı yapma (performans için)
	// Kullanıcı "Stokları Güncelle" butonuna tıklarsa API çağrısı yapılacak
	if (!useCache && cachedData) {
		// Eski cache varsa onu göster
		updateWarehouseStocks(cachedData);
	} else if (!useCache) {
		// Hiç cache yoksa, veritabanından mevcut stok bilgilerini göster
		// (Sayfa yüklendiğinde veritabanındaki değerler zaten gösteriliyor)
	}
	
	// Depo stok durumlarını güncelle
	function updateWarehouseStocks(productsByCode) {
		document.querySelectorAll('[data-stok-kodu]').forEach(function(el) {
			const stokKodu = el.getAttribute('data-stok-kodu');
			if (productsByCode[stokKodu]) {
				const product = productsByCode[stokKodu];
				
				// Depo stok durumlarını al
				const maslak = getStatus(product, 'Maslak_Status');
				const bolu = getStatus(product, 'Bolu_Status');
				const imes = getStatus(product, ['İmes_Status', 'Imes_Status', 'imes_Status']);
				const ankara = getStatus(product, 'Ankara_Status');
				const ikitelli = getStatus(product, ['İkitelli_Status', 'Ikitelli_Status', 'ikitelli_Status']);
				
				// HTML'i güncelle - data-warehouse attribute'una göre
				const badges = el.querySelectorAll('.warehouse-badge');
				badges.forEach(function(badge) {
					const warehouse = badge.getAttribute('data-warehouse');
					let status = 'Yok';
					if (warehouse === 'maslak') status = maslak;
					else if (warehouse === 'bolu') status = bolu;
					else if (warehouse === 'imes') status = imes;
					else if (warehouse === 'ankara') status = ankara;
					else if (warehouse === 'ikitelli') status = ikitelli;
					updateBadge(badge, status);
				});
			} else {
				// Ürün bulunamadı - hepsi Yok
				el.querySelectorAll('.warehouse-badge').forEach(function(badge) {
					updateBadge(badge, 'Yok');
				});
			}
		});
	}
	
	// Status değerini al (birden fazla alan adı kontrol edilebilir)
	function getStatus(product, fieldNames) {
		if (Array.isArray(fieldNames)) {
			for (let i = 0; i < fieldNames.length; i++) {
				if (product[fieldNames[i]]) {
					return product[fieldNames[i]].toLowerCase() === 'var' ? 'Var' : 'Yok';
				}
			}
		} else {
			if (product[fieldNames]) {
				return product[fieldNames].toLowerCase() === 'var' ? 'Var' : 'Yok';
			}
		}
		return 'Yok';
	}
	
	// Badge'i güncelle
	function updateBadge(badge, status) {
		badge.textContent = status;
		badge.className = 'badge badge-' + (status === 'Var' ? 'success' : 'danger');
		badge.style.fontSize = '0.85em';
		badge.style.padding = '4px 8px';
	}
})();

// Stokları Güncelle butonu
document.getElementById('btnUpdateStocks').addEventListener('click', function() {
	const btn = this;
	const originalHTML = btn.innerHTML;
	btn.disabled = true;
	btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Güncelleniyor...';
	
	// API'den stok bilgilerini çek
	fetch('../api-eryaz.php?ajax=1&action=getProductList&start=1&end=50000')
		.then(response => response.json())
		.then(data => {
			if (data.success && data.data && data.data.Data) {
				// Ürünleri stok koduna göre indexle
				const productsByCode = {};
				data.data.Data.forEach(function(product) {
					if (product.Code) {
						productsByCode[product.Code] = product;
					}
				});
				
				// Cache'e kaydet
				try {
					localStorage.setItem(CACHE_KEY, JSON.stringify(productsByCode));
					localStorage.setItem(CACHE_TIMESTAMP_KEY, Date.now().toString());
				} catch (e) {
					console.error('Cache kaydetme hatası:', e);
				}
				
				// Güncelle
				updateWarehouseStocks(productsByCode);
				
				// Veritabanını da güncelle (AJAX ile)
				const formData = new FormData();
				formData.append('ajax_update_stocks', '1');
				formData.append('products_data', JSON.stringify(productsByCode));
				
				fetch('', {
					method: 'POST',
					body: formData
				})
				.then(response => response.json())
				.then(data => {
					if (data.success) {
						alert('Stoklar başarıyla güncellendi! (' + data.updated + ' ürün)');
						// Sayfayı yenile
						setTimeout(() => {
							location.reload();
						}, 1000);
					} else {
						alert('Stoklar görüntülendi ancak veritabanı güncellenirken bir hata oluştu: ' + (data.error || 'Bilinmeyen hata'));
					}
				})
				.catch(() => {
					alert('Stoklar görüntülendi ancak veritabanı güncellenirken bir hata oluştu.');
				});
			} else {
				alert('Stok bilgileri alınamadı. Lütfen tekrar deneyin.');
			}
		})
		.catch(error => {
			console.error('Stok güncelleme hatası:', error);
			alert('Stok güncelleme sırasında bir hata oluştu.');
		})
		.finally(() => {
			btn.disabled = false;
			btn.innerHTML = originalHTML;
		});
});

var btnRecalcSiraEl = document.getElementById('btnRecalcSira');
if (btnRecalcSiraEl) {
	btnRecalcSiraEl.addEventListener('click', function() {
		if (!confirm('Tum urunlerin sira numaralari yukleme tarihine gore guncellenecek.\nEn son eklenen urun sira=1 olacak. Devam?')) {
			return;
		}
		var btn = this;
		var originalHTML = btn.innerHTML;
		btn.disabled = true;
		btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Guncelleniyor...';
		var body = new URLSearchParams();
		body.append('ajax_urunler', '1');
		body.append('action', 'sira_toplu');
		fetch('index.php?sayfa=urunler', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: body })
			.then(function(r) { return r.json(); })
			.then(function(res) {
				if (res && res.ok) {
					alert('Sira numaralari guncellendi. (' + (res.updated || 0) + ' urun)');
					location.reload();
				} else {
					alert((res && res.error) ? res.error : 'Sira guncellenemedi');
				}
			})
			.catch(function() {
				alert('Baglanti hatasi');
			})
			.finally(function() {
				btn.disabled = false;
				btn.innerHTML = originalHTML;
			});
	});
}

window.seoAjaxUrl = 'inc/ajax-product-seo-title.php';

document.getElementById('btnSeoOptimizeAllProducts').addEventListener('click', function() {
	const btn = this;
	const originalHTML = btn.innerHTML;
	if (!confirm('Eski formattaki urun adlari (:/ iceren) SEO formatina cevrilsin mi?\n\nHer seferde en fazla 50 urun islenir. Tekrar tiklayarak devam edebilirsiniz.')) {
		return;
	}
	btn.disabled = true;
	btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> SEO...';
	const fd = new FormData();
	fd.append('ajax_seo_action', 'bulk_optimize');
	fd.append('limit', '50');
	fd.append('only_unoptimized', '1');
	fetch(seoAjaxUrl, { method: 'POST', body: fd })
		.then(function(r) { return r.json(); })
		.then(function(data) {
			if (!data.success) {
				throw new Error(data.error || 'Toplu SEO basarisiz');
			}
			alert('SEO isim guncelleme tamamlandi.\n\nGuncellenen: ' + (data.updated || 0) + '\nAtlanan: ' + (data.skipped || 0) + '\nHata: ' + (data.failed || 0));
			if ((data.updated || 0) > 0) {
				window.location.reload();
			}
		})
		.catch(function(err) {
			alert(err.message || 'SEO toplu islem hatasi');
		})
		.finally(function() {
			btn.disabled = false;
			btn.innerHTML = originalHTML;
		});
});

// Tum urunler icin otomatik ceviri
document.getElementById('btnTranslateAllProducts').addEventListener('click', function() {
	const btn = this;
	const originalHTML = btn.innerHTML;

	if (!confirm('Tum urunlerin bos dil alanlari otomatik cevrilsin mi? Mevcut ceviriler degistirilmeyecek.')) {
		return;
	}

	btn.disabled = true;
	btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Hazirlaniyor...';

	let progressBox = document.getElementById('translateProgressBox');
	if (!progressBox) {
		progressBox = document.createElement('div');
		progressBox.id = 'translateProgressBox';
		progressBox.className = 'alert alert-info mt-2';
		progressBox.innerHTML = '' +
			'<div class="progress mb-2"><div id="translateProgressBar" class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width:0%"></div></div>' +
			'<div id="translateProgressText">Urunler yukleniyor...</div>';
		btn.parentNode.appendChild(progressBox);
	}

	const formData = new FormData();
	formData.append('ajax_translate_action', 'get_products');
	const translateAjaxUrl = 'inc/ajax-product-translate.php';

	fetch(translateAjaxUrl, { method: 'POST', body: formData })
		.then(response => response.json())
		.then(async data => {
			if (!data.success || !Array.isArray(data.products)) {
				throw new Error(data.error || 'Urun listesi alinamadi');
			}

			const products = data.products;
			const total = products.length;
			const languages = ['en', 'ru', 'fr', 'es', 'ar', 'pl'];
			let updatedProducts = 0;
			let skippedProducts = 0;
			let failedProducts = 0;

			for (let i = 0; i < total; i++) {
				const product = products[i];
				const percent = total > 0 ? Math.round(((i + 1) / total) * 100) : 100;
				updateTranslateProgress(percent, '[' + (i + 1) + '/' + total + '] Isleniyor: #' + product.id);

				try {
					const payload = { product_id: product.id };
					let hasUpdate = false;

					for (const lang of languages) {
						const titleField = 'baslik_' + lang;
						const shortField = 'kisa_aciklama_' + lang;
						const detailField = 'aciklama_' + lang;

						if ((product.baslik || '').trim() !== '' && (product[titleField] || '').trim() === '') {
							payload[titleField] = await translateText(product.baslik, 'tr', lang);
							hasUpdate = true;
						}
						if ((product.kisa_aciklama || '').trim() !== '' && (product[shortField] || '').trim() === '') {
							payload[shortField] = await translateText(product.kisa_aciklama, 'tr', lang);
							hasUpdate = true;
						}
						if ((product.aciklama || '').trim() !== '' && (product[detailField] || '').trim() === '') {
							payload[detailField] = await translateText(product.aciklama, 'tr', lang);
							hasUpdate = true;
						}
					}

					if (!hasUpdate) {
						skippedProducts++;
						continue;
					}

					const saveForm = new FormData();
					saveForm.append('ajax_translate_action', 'save_product');
					Object.keys(payload).forEach(key => {
						saveForm.append(key, payload[key]);
					});

					const saveResp = await fetch(translateAjaxUrl, { method: 'POST', body: saveForm });
					const saveData = await saveResp.json();
					if (!saveData.success) {
						throw new Error(saveData.error || 'Kaydetme hatasi');
					}
					updatedProducts++;
				} catch (err) {
					failedProducts++;
					console.error('Toplu ceviri urun hatasi #' + product.id, err);
				}
			}

			updateTranslateProgress(100, 'Tamamlandi. Guncellenen: ' + updatedProducts + ', Atlanan: ' + skippedProducts + ', Hata: ' + failedProducts);
			alert('Toplu ceviri tamamlandi!\n\nGuncellenen urun: ' + updatedProducts + '\nAtlanan urun: ' + skippedProducts + '\nHata: ' + failedProducts);
		})
		.catch(error => {
			console.error('Toplu ceviri genel hata:', error);
			updateTranslateProgress(100, 'Hata: ' + error.message);
			alert('Toplu ceviri sirasinda hata olustu: ' + error.message);
		})
		.finally(() => {
			btn.disabled = false;
			btn.innerHTML = originalHTML;
		});
});

function updateTranslateProgress(percent, text) {
	const bar = document.getElementById('translateProgressBar');
	const txt = document.getElementById('translateProgressText');
	if (bar) {
		bar.style.width = percent + '%';
	}
	if (txt) {
		txt.textContent = text;
	}
}

function translateText(text, fromLang, toLang) {
	return new Promise(function(resolve, reject) {
		if (!text || text.trim() === '') {
			resolve('');
			return;
		}

		var apiUrl = 'https://translate.googleapis.com/translate_a/single?client=gtx&sl=' + encodeURIComponent(fromLang) + '&tl=' + encodeURIComponent(toLang) + '&dt=t&q=' + encodeURIComponent(text);

		$.ajax({
			url: apiUrl,
			method: 'GET',
			dataType: 'json',
			timeout: 15000,
			success: function(data) {
				try {
					if (data && Array.isArray(data) && data[0] && Array.isArray(data[0])) {
						var translatedText = '';
						for (var i = 0; i < data[0].length; i++) {
							if (data[0][i] && data[0][i][0]) {
								translatedText += data[0][i][0];
							}
						}
						if (translatedText) {
							resolve(translatedText);
							return;
						}
					}
					translateWithMyMemory(text, fromLang, toLang).then(resolve).catch(reject);
				} catch (e) {
					translateWithMyMemory(text, fromLang, toLang).then(resolve).catch(reject);
				}
			},
			error: function() {
				translateWithMyMemory(text, fromLang, toLang).then(resolve).catch(reject);
			}
		});
	});
}

function translateWithMyMemory(text, fromLang, toLang) {
	return new Promise(function(resolve, reject) {
		var apiUrl = 'https://api.mymemory.translated.net/get?q=' + encodeURIComponent(text) + '&langpair=' + encodeURIComponent(fromLang + '|' + toLang);
		$.ajax({
			url: apiUrl,
			method: 'GET',
			dataType: 'json',
			timeout: 10000,
			success: function(data) {
				if (data && data.responseStatus === 200 && data.responseData && data.responseData.translatedText) {
					resolve(data.responseData.translatedText);
				} else {
					reject(new Error('MyMemory ceviri basarisiz'));
				}
			},
			error: function() {
				reject(new Error('MyMemory API hatasi'));
			}
		});
	});
}
</script>

<script>
(function() {
	var seoAjaxUrl = window.seoAjaxUrl || 'inc/ajax-product-seo-title.php';
	var translateAjaxUrl = window.translateAjaxUrl || 'inc/ajax-product-translate.php';
	var tecdocAjaxUrl = '../api-tecdoc.php';
	var kmsReferansAjaxUrl = '../api-kms-referans.php';
	function postForm(url, data) {
		return new Promise(function(resolve, reject) {
			if (typeof jQuery !== 'undefined') {
				jQuery.ajax({
					url: url,
					type: 'POST',
					data: data,
					dataType: 'text'
				}).done(function(text) {
					var res = tryParseKapakJson(text);
					if (res) {
						resolve(res);
					} else {
						reject(new Error('Geçersiz sunucu yanıtı; sayfayı yenileyip tekrar deneyin.'));
					}
				}).fail(function(xhr, status, err) {
					if (xhr && xhr.responseText) {
						var parsed = tryParseKapakJson(xhr.responseText);
						if (parsed) {
							resolve(parsed);
							return;
						}
					}
					var msg = (err && String(err)) || status || 'İstek hatası';
					if (xhr && xhr.responseText && xhr.responseText.length < 500 && xhr.responseText.indexOf('<!') === 0) {
						msg = 'Geçersiz yanıt (oturum veya adres); sayfayı yenileyin.';
					}
					reject(new Error(msg));
				});
				return;
			}
			var body = new URLSearchParams();
			Object.keys(data).forEach(function(k) { body.append(k, data[k]); });
			fetch(url, { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: body })
				.then(function(r) { return r.text(); })
				.then(function(text) {
					var res = tryParseKapakJson(text);
					if (res) resolve(res);
					else reject(new Error('Geçersiz sunucu yanıtı'));
				})
				.catch(reject);
		});
	}
	function ajaxUrl() {
		// Panel index.php sadece GET ile sayfa seçiyor; sayfa yoksa anasayfa yüklenir ve bu dosya hiç çalışmaz.
		return 'index.php?sayfa=urunler';
	}
	/** Yanıta PHP notice/BOM karışsa bile JSON çıkarır (jQuery dataType:json parsererror önlenir). */
	function tryParseKapakJson(text) {
		if (text == null) return null;
		var s = String(text).replace(/^\uFEFF/, '').trim();
		try {
			return JSON.parse(s);
		} catch (e1) {}
		var i = s.indexOf('{');
		var j = s.lastIndexOf('}');
		if (i >= 0 && j > i) {
			try {
				return JSON.parse(s.slice(i, j + 1));
			} catch (e2) {}
		}
		return null;
	}
	function fetchJsonForm(url, formData) {
		return fetch(url, { method: 'POST', body: formData })
			.then(function(r) { return r.text(); })
			.then(function(text) {
				var parsed = tryParseKapakJson(text);
				if (parsed) {
					return parsed;
				}
				if (String(text || '').trim().charAt(0) === '<') {
					throw new Error('Sunucu JSON yerine HTML dondu. Oturum bitmis olabilir; paneli yenileyip tekrar deneyin.');
				}
				throw new Error('Gecersiz sunucu yaniti.');
			});
	}
	function bindInlineField(selector, action, valueKey) {
		if (typeof jQuery === 'undefined') return;
		var $ = jQuery;
		$(document).off('keydown', selector).on('keydown', selector, function(e) {
			if (e.key === 'Enter') {
				e.preventDefault();
				jQuery(this).trigger('change');
			}
		});
		$(document).off('change', selector).on('change', selector, function() {
			var $inp = $(this);
			var id = parseInt($inp.data('urun-id'), 10);
			if (!id) return;
			var payload = {
				ajax_urunler: '1',
				action: action,
				urun_id: id
			};
			payload[valueKey] = $inp.val();
			$inp.prop('disabled', true);
			postForm(ajaxUrl(), payload).then(function(res) {
				if (res && res.ok) {
					var $tr = $inp.closest('tr');
					if (res.net_fiyat_label) {
						$tr.find('.js-net-fiyat-cell').text(res.net_fiyat_label);
					}
					$inp.addClass('border-success');
					setTimeout(function() { $inp.removeClass('border-success'); }, 1200);
				} else {
					alert((res && res.error) ? res.error : 'Kaydedilemedi');
				}
			}).catch(function(err) {
				alert(err && err.message ? err.message : 'Bağlantı hatası');
			}).finally(function() {
				$inp.prop('disabled', false);
			});
		});
	}
	function bindInlineEuro() {
		bindInlineField('.js-inline-liste-euro', 'liste_euro', 'liste_euro');
	}
	function bindInlineIskonto() {
		bindInlineField('.js-inline-iskonto', 'iskonto_orani', 'iskonto_orani');
	}
	function bindInlineSira() {
		bindInlineField('.js-inline-sira', 'sira', 'sira');
	}
	function bindInlineBaslik() {
		bindInlineField('.js-inline-baslik', 'baslik', 'baslik');
	}
	function bindSeoBaslikBtn() {
		if (typeof jQuery === 'undefined') return;
		var $ = jQuery;
		$(document).off('click', '.js-seo-baslik-btn').on('click', '.js-seo-baslik-btn', function(e) {
			e.preventDefault();
			var $btn = $(this);
			var $inp = $btn.closest('.input-group').find('.js-inline-baslik');
			var baslik = ($inp.val() || '').trim();
			if (!baslik) {
				alert('Urun adi bos.');
				return;
			}
			var uid = parseInt($btn.data('urun-id'), 10) || parseInt($inp.data('urun-id'), 10);
			var stokKodu = ($inp.data('stok-kodu') || '').toString();
			var originalHtml = $btn.html();
			$btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i>');
			var fd = new FormData();
			fd.append('ajax_seo_action', 'optimize_one');
			fd.append('baslik', baslik);
			fd.append('stok_kodu', stokKodu);
			if (uid) fd.append('urun_id', String(uid));
			fetch(seoAjaxUrl, { method: 'POST', body: fd })
				.then(function(r) { return r.json(); })
				.then(function(data) {
					if (!data.success || !data.title) {
						throw new Error(data.error || 'SEO onerisi alinamadi');
					}
					if (confirm('Onerilen SEO isim:\n\n' + data.title + '\n\n(' + (data.method === 'ai' ? 'AI' : 'Kural') + ')\n\nKaydedilsin mi?')) {
						$inp.val(data.title).trigger('change');
						if (uid) markRowAction(uid, 'seo');
					}
				})
				.catch(function(err) {
					alert(err.message || 'SEO isim hatasi');
				})
				.finally(function() {
					$btn.prop('disabled', false).html(originalHtml);
				});
		});
	}
	// --- Per-row "Otomatik Cevir" butonu ---
	function bindTranslateBaslikBtn() {
		if (typeof jQuery === 'undefined') return;
		var $ = jQuery;
		$(document).off('click', '.js-translate-baslik-btn').on('click', '.js-translate-baslik-btn', function() {
			var $btn = $(this);
			var uid = parseInt($btn.data('urun-id'), 10);
			if (!uid) return;
			if ($btn.prop('disabled')) return;
			if (!confirm('Bu urunun bos dil alanlari (baslik, kisa/detayli aciklama) tum dillere otomatik cevrilsin mi?\nMevcut ceviriler degistirilmez.')) {
				return;
			}
			var originalHtml = $btn.html();
			$btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i>');
			var languages = ['en', 'ru', 'fr', 'es', 'ar', 'pl'];

			var getFd = new FormData();
			getFd.append('ajax_translate_action', 'get_one_product');
			getFd.append('product_id', String(uid));

			fetchJsonForm(translateAjaxUrl, getFd)
				.then(async function(data) {
					if (!data.success || !data.product) {
						throw new Error(data.error || 'Urun bilgisi alinamadi');
					}
					var p = data.product;
					var payload = { product_id: uid };
					var hasUpdate = false;

					for (var li = 0; li < languages.length; li++) {
						var lang = languages[li];
						var titleField = 'baslik_' + lang;
						var shortField = 'kisa_aciklama_' + lang;
						var detailField = 'aciklama_' + lang;

						if ((p.baslik || '').trim() !== '' && (p[titleField] || '').trim() === '') {
							payload[titleField] = await translateText(p.baslik, 'tr', lang);
							hasUpdate = true;
						}
						if ((p.kisa_aciklama || '').trim() !== '' && (p[shortField] || '').trim() === '') {
							payload[shortField] = await translateText(p.kisa_aciklama, 'tr', lang);
							hasUpdate = true;
						}
						if ((p.aciklama || '').trim() !== '' && (p[detailField] || '').trim() === '') {
							payload[detailField] = await translateText(p.aciklama, 'tr', lang);
							hasUpdate = true;
						}
					}

					if (!hasUpdate) {
						markRowAction(uid, 'translate');
						alert('Cevrilecek bos alan bulunamadi (zaten cevrili olabilir).');
						return;
					}

					var saveForm = new FormData();
					saveForm.append('ajax_translate_action', 'save_product');
					Object.keys(payload).forEach(function(key) {
						saveForm.append(key, payload[key]);
					});
					var saveData = await fetchJsonForm(translateAjaxUrl, saveForm);
					if (!saveData.success) {
						throw new Error(saveData.error || 'Kaydetme hatasi');
					}
					markRowAction(uid, 'translate');
					alert('Ceviri tamamlandi ve kaydedildi.');
				})
				.catch(function(err) {
					alert((err && err.message) ? err.message : 'Ceviri hatasi');
				})
				.finally(function() {
					$btn.prop('disabled', false).html(originalHtml);
				});
		});
	}
	function kmsLocalCommand(stok, uid) {
		var clean = String(stok || '').replace(/^(30-|31-|32-|3e-?)/i, '').trim();
		return '.\\kmotorshop-yerel-referans-cek.ps1 -Site "https://btmotorshop.com" -ProductId ' + uid + ' -Code "' + clean + '"';
	}
	function bindKmsReferansBtn() {
		if (typeof jQuery === 'undefined') return;
		var $ = jQuery;
		$(document).off('click', '.js-kms-referans-btn').on('click', '.js-kms-referans-btn', function() {
			var $btn = $(this);
			var uid = parseInt($btn.data('urun-id'), 10);
			var stok = String($btn.data('stok-kodu') || '').trim();
			if (!uid || $btn.prop('disabled')) return;
			var localCmd = kmsLocalCommand(stok, uid);
			if (!confirm(
				'Bu urun icin KMotorShop\'tan OEM referanslari ve gorsel cekilsin mi?\n\n' +
				'Stok: ' + (stok || '(bos)') + '\n\n' +
				'(Sunucu KMotorShop tarafindan engellenirse yerel PowerShell komutu gosterilir.)'
			)) {
				return;
			}
			var originalHtml = $btn.html();
			$btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i>');
			fetch(kmsReferansAjaxUrl + '?ajax=1&action=syncProduct&urun_id=' + encodeURIComponent(uid))
				.then(function(r) { return r.text(); })
				.then(function(text) {
					var data = tryParseKapakJson(text);
					if (!data) {
						throw new Error('Gecersiz sunucu yaniti: ' + String(text || '').substring(0, 200));
					}
					if (!data.success) {
						var err = new Error(data.error || 'KMS referans sync basarisiz');
						err.kmsLocal = data.use_local_script ? (data.local_command || localCmd) : localCmd;
						throw err;
					}
					markRowAction(uid, 'kms');
					var kmsMsg = 'KMotorShop verisi cekildi.\n\n';
					kmsMsg += 'Referans - yeni: ' + (data.referans_added || 0) + ' / bulunan: ' + (data.referans_found || 0) + '\n';
					kmsMsg += 'Gorsel eklenen: ' + (data.image_added || 0);
					if (data.image_note) { kmsMsg += ' (' + data.image_note + ')'; }
					alert(kmsMsg);
					if (data.image_added > 0) { location.reload(); }
				})
				.catch(function(err) {
					var cmd = (err && err.kmsLocal) ? err.kmsLocal : localCmd;
					alert(
						((err && err.message) ? err.message : 'KMS referans hatasi') +
						'\n\n--- Yerel cozum (PowerShell) ---\n' + cmd
					);
				})
				.finally(function() {
					$btn.prop('disabled', false).html(originalHtml);
				});
		});
	}
	function bindTecDocSyncBtn() {
		if (typeof jQuery === 'undefined') return;
		var $ = jQuery;
		$(document).off('click', '.js-tecdoc-sync-btn').on('click', '.js-tecdoc-sync-btn', function() {
			var $btn = $(this);
			var uid = parseInt($btn.data('urun-id'), 10);
			var stok = String($btn.data('stok-kodu') || '').trim();
			if (!uid) return;
			if ($btn.prop('disabled')) return;
			if (!confirm('Bu urun icin TecDoc\'tan OEM kodlari ve gorseller cekilsin mi?\n\nStok kodu: ' + (stok || '(bos)'))) {
				return;
			}
			var originalHtml = $btn.html();
			$btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i>');
			var url = tecdocAjaxUrl + '?ajax=1&action=syncProduct&urun_id=' + encodeURIComponent(uid);
			fetch(url)
				.then(function(r) { return r.text(); })
				.then(function(text) {
					var data = tryParseKapakJson(text);
					if (!data) {
						throw new Error('Gecersiz sunucu yaniti: ' + String(text || '').substring(0, 200));
					}
					if (!data.success) {
						throw new Error(data.error || 'TecDoc sync basarisiz');
					}
					markRowAction(uid, 'tecdoc');
					var msg = 'TecDoc verisi eklendi.\n\n';
					msg += 'OEM eklenen: ' + (data.oem_added || 0) + ' / ' + (data.oem_total || 0) + '\n';
					msg += 'Gorsel eklenen: ' + (data.images_added || 0) + ' / ' + (data.images_total || 0);
					if (data.images_added > 0) {
						msg += '\n\nSayfayi yenilerseniz kapak gorseli guncellenmis olabilir.';
					}
					alert(msg);
					if (data.images_added > 0) {
						location.reload();
					}
				})
				.catch(function(err) {
					alert((err && err.message) ? err.message : 'TecDoc hatasi');
				})
				.finally(function() {
					$btn.prop('disabled', false).html(originalHtml);
				});
		});
	}
	// --- "Basildi" isaretleri (localStorage'da saklanir, sayfa yenilense de kalir) ---
	function rowMarkStorageKey() { return 'urunler_row_marks_v1'; }
	function getRowMarks() {
		try { return JSON.parse(localStorage.getItem(rowMarkStorageKey()) || '{}') || {}; }
		catch (e) { return {}; }
	}
	function markRowAction(uid, type) {
		if (!uid || !type) return;
		var marks = getRowMarks();
		if (!marks[uid]) marks[uid] = {};
		marks[uid][type] = true;
		try { localStorage.setItem(rowMarkStorageKey(), JSON.stringify(marks)); } catch (e) {}
		renderRowMarks(uid);
	}
	function badgeHtml(text, color) {
		return '<span class="badge" style="background:' + color + ';color:#fff;font-size:10px;padding:2px 5px;border-radius:8px;">' +
			'<i class="fa fa-check" style="margin-right:2px;"></i>' + text + '</span>';
	}
	function renderRowMarks(uid) {
		if (typeof jQuery === 'undefined') return;
		var $ = jQuery;
		var marks = getRowMarks();
		var selector = uid ? '.js-row-action-marks[data-urun-id="' + uid + '"]' : '.js-row-action-marks';
		$(selector).each(function() {
			var id = parseInt($(this).data('urun-id'), 10);
			var m = (id && marks[id]) ? marks[id] : {};
			var html = '';
			if (m.seo) html += badgeHtml('SEO', '#28a745');
			if (m.translate) html += badgeHtml('Ceviri', '#17a2b8');
			if (m.tecdoc) html += badgeHtml('TecDoc', '#fd7e14');
			if (m.kms) html += badgeHtml('KMS', '#6c757d');
			$(this).html(html);
		});
	}
	function bindThumbDrop() {
		var zones = document.querySelectorAll('.js-urun-thumb-drop');
		zones.forEach(function(zone) {
			['dragenter', 'dragover'].forEach(function(ev) {
				zone.addEventListener(ev, function(e) {
					e.preventDefault();
					e.stopPropagation();
					zone.classList.add('is-dragover');
				});
			});
			['dragleave', 'drop'].forEach(function(ev) {
				zone.addEventListener(ev, function(e) {
					e.preventDefault();
					e.stopPropagation();
					if (ev === 'dragleave') zone.classList.remove('is-dragover');
				});
			});
			zone.addEventListener('drop', function(e) {
				zone.classList.remove('is-dragover');
				var files = e.dataTransfer && e.dataTransfer.files;
				if (!files || !files.length) return;
				var file = files[0];
				var fname = ((file && file.name) ? file.name : '').toLowerCase();
				var byMime = file.type && file.type.indexOf('image/') === 0;
				var byExt = /\.(jpe?g|png|gif|svg|webp|bmp|ico)$/i.test(fname);
				if (!byMime && !byExt) {
					return;
				}
				var uid = parseInt(zone.getAttribute('data-urun-id'), 10);
				if (!uid) return;
				var fd = new FormData();
				fd.append('ajax_urunler', '1');
				fd.append('action', 'kapak_resim');
				fd.append('urun_id', String(uid));
				fd.append('kapak', file);
				var img = zone.querySelector('.js-urun-thumb-img');
				if (typeof jQuery !== 'undefined') {
					jQuery.ajax({
						url: ajaxUrl(),
						type: 'POST',
						data: fd,
						processData: false,
						contentType: false,
						dataType: 'text'
					}).done(function(text) {
						var res = tryParseKapakJson(text);
						if (res && res.ok && res.src && img) {
							img.src = res.src + '?t=' + Date.now();
						}
					});
				} else {
					fetch(ajaxUrl(), { method: 'POST', body: fd })
						.then(function(r) { return r.text(); })
						.then(function(text) {
							var res = tryParseKapakJson(text);
							if (res && res.ok && res.src && img) {
								img.src = res.src + '?t=' + Date.now();
							}
						});
				}
			});
			zone.addEventListener('click', function() {
				var uid = parseInt(zone.getAttribute('data-urun-id'), 10);
				if (!uid) return;
				var input = document.createElement('input');
				input.type = 'file';
				input.accept = 'image/*';
				input.style.display = 'none';
				input.addEventListener('change', function() {
					if (!input.files || !input.files[0]) return;
					var fd = new FormData();
					fd.append('ajax_urunler', '1');
					fd.append('action', 'kapak_resim');
					fd.append('urun_id', String(uid));
					fd.append('kapak', input.files[0]);
					var imgEl = zone.querySelector('.js-urun-thumb-img');
					if (typeof jQuery !== 'undefined') {
						jQuery.ajax({
							url: ajaxUrl(),
							type: 'POST',
							data: fd,
							processData: false,
							contentType: false,
							dataType: 'text'
						}).done(function(text) {
							var res = tryParseKapakJson(text);
							if (res && res.ok && res.src && imgEl) {
								imgEl.src = res.src + '?t=' + Date.now();
							}
						});
					}
					document.body.removeChild(input);
				});
				document.body.appendChild(input);
				input.click();
			});
		});
	}
	function bindFilterAutoSubmit() {
		var form = document.querySelector('form[action*="urunler"]');
		if (!form) {
			var forms = document.querySelectorAll('form');
			for (var i = 0; i < forms.length; i++) {
				if (forms[i].querySelector('select[name="siralama"]')) {
					form = forms[i];
					break;
				}
			}
		}
		if (!form) return;
		['limit', 'siralama'].forEach(function(name) {
			var sel = form.querySelector('select[name="' + name + '"]');
			if (sel) {
				sel.addEventListener('change', function() {
					form.submit();
				});
			}
		});
	}
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', function() {
			bindInlineEuro();
			bindInlineIskonto();
			bindInlineSira();
			bindInlineBaslik();
			bindSeoBaslikBtn();
			bindTranslateBaslikBtn();
			bindTecDocSyncBtn();
			bindKmsReferansBtn();
			renderRowMarks();
			bindThumbDrop();
			bindFilterAutoSubmit();
		});
	} else {
		bindInlineEuro();
		bindInlineIskonto();
		bindInlineSira();
		bindInlineBaslik();
		bindSeoBaslikBtn();
		bindTranslateBaslikBtn();
		bindTecDocSyncBtn();
		bindKmsReferansBtn();
		renderRowMarks();
		bindThumbDrop();
		bindFilterAutoSubmit();
	}
})();
</script>



