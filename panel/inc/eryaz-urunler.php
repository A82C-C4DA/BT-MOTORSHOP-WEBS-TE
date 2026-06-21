<?php
// API sınıfını dahil et (sadece sınıf için)
$apiFile = __DIR__ . '/../../api-eryaz.php';
if (!file_exists($apiFile)) {
    die('<div class="alert alert-danger">HATA: api-eryaz.php dosyası bulunamadı! Yol: ' . htmlspecialchars($apiFile) . '</div>');
}

require_once $apiFile;

// Sınıfın yüklendiğini kontrol et
if (!class_exists('EryazAPI')) {
    die('<div class="alert alert-danger">HATA: EryazAPI sınıfı yüklenemedi!</div>');
}

// Parametreleri al - Sayfalama ile
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1; // Sayfa numarası
$perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 50; // Sayfa başına kayıt sayısı (varsayılan 50 - performans için)
$allowedPerPage = [25, 50, 100, 200, 500]; // İzin verilen sayfa başına kayıt sayıları
if (!in_array($perPage, $allowedPerPage)) {
    $perPage = 50; // Varsayılan değer
}

// API parametrelerini hesapla (butona tıklanınca kullanılacak)
$start = (($page - 1) * $perPage) + 1;
$end = $start + $perPage - 1;

// Maksimum kayıt limiti (performans için)
$maxEnd = 1000;
if ($end > $maxEnd) {
    $end = $maxEnd;
    $perPage = min($perPage, $maxEnd - $start + 1);
}

// API sınıfını başlat (sadece sınıf, veri çekme YOK - butona tıklanınca AJAX ile yapılacak)
try {
    $eryazAPI = new EryazAPI();
    // API çağrısı yapılmıyor - butona tıklanınca AJAX ile yapılacak
    $result = null; // Başlangıçta null
} catch (Exception $e) {
    die('<div class="alert alert-danger">HATA: ' . htmlspecialchars($e->getMessage()) . '</div>');
}

// IP tespiti - Gerçek sunucu IP'sini bul
// ÖNEMLİ: VDS IP adresi 141.11.109.206 olarak sabitlendi
// Eğer proxy/load balancer arkasındaysanız, gerçek sunucu IP'sini kullanın

// Öncelik sırası: SERVER_ADDR > HTTP_X_REAL_IP > HTTP_X_FORWARDED_FOR (son değer) > REMOTE_ADDR
$serverIP = $_SERVER['SERVER_ADDR'] ?? null;
$realIP = isset($_SERVER['HTTP_X_REAL_IP']) ? $_SERVER['HTTP_X_REAL_IP'] : null;
$forwardedIP = null;
if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
    $forwardedArray = explode(',', trim($_SERVER['HTTP_X_FORWARDED_FOR']));
    // Son değeri al (gerçek sunucu IP'si genelde sondadır)
    $forwardedIP = trim(end($forwardedArray));
}
$remoteIP = $_SERVER['REMOTE_ADDR'] ?? null;

// Gerçek sunucu IP'sini tespit et
// SERVER_ADDR genelde gerçek sunucu IP'sidir
$detectedIP = $serverIP ?: $realIP ?: $forwardedIP ?: $remoteIP ?: '141.11.109.206';

// Eğer tespit edilen IP private/local ise, VDS IP'sini kullan
if ($detectedIP && (
    strpos($detectedIP, '127.') === 0 || 
    strpos($detectedIP, '192.168.') === 0 || 
    strpos($detectedIP, '10.') === 0 ||
    strpos($detectedIP, '172.') === 0 ||
    $detectedIP === '::1'
)) {
    $detectedIP = '141.11.109.206'; // VDS IP adresi
}

// Manuel IP override (eğer yanlış IP gösteriyorsa)
// VDS IP adresiniz: 141.11.109.206
$detectedIP = '141.11.109.206'; // VDS IP adresi - Sabit değer

// Debug bilgisi
$ipDebug = [
    'public_ip' => 'Devre dışı (performans için - harici servis çağrısı yok)',
    'server_addr' => $serverIP ? $serverIP : 'Yok',
    'forwarded_for' => $forwardedIP ? $forwardedIP : 'Yok',
    'remote_addr' => $remoteIP ? $remoteIP : 'Yok',
    'selected_ip' => $detectedIP
];
?>

<div class="breadcrumb-header justify-content-between">
	<div class="my-auto">
		<div class="d-flex">
			<h4 class="content-title mb-0 my-auto">Eryaz Ürünleri</h4><span class="text-muted mt-1 tx-13 ml-2 mb-0">/ API'den Çekilen Ürünler</span>
		</div>
	</div>
</div>

<!-- SABİT IP BİLGİSİ - HER ZAMAN GÖRÜNÜR -->
<?php 
$finalIP = $detectedIP && $detectedIP !== 'Tespit edilemedi' ? $detectedIP : ($serverIP ?: $remoteIP ?: 'IP tespit edilemedi');
?>
<div class="row mb-3">
	<div class="col-md-12">
		<div class="alert alert-danger" style="background-color: #f8d7da; border: 2px solid #dc3545;">
			<div class="d-flex align-items-center">
				<i class="fa fa-exclamation-circle fa-3x text-danger mr-3"></i>
				<div class="flex-grow-1">
					<h5 class="mb-2"><strong>⚠️ ÖNEMLİ: Eryaz API IP Adresi</strong></h5>
					<p class="mb-2">Eryaz API'ye yapılan isteklerde görünecek IP adresiniz:</p>
					<div class="text-center" style="background-color: white; padding: 15px; border-radius: 5px; border: 2px solid #007bff; margin: 10px 0;">
						<h3 class="mb-2" style="color: #007bff; font-weight: bold; font-family: 'Courier New', monospace; font-size: 1.8em;">
							<?php echo htmlspecialchars($finalIP); ?>
						</h3>
						<button class="btn btn-primary" onclick="copyIP('<?php echo htmlspecialchars($finalIP); ?>')">
							<i class="fa fa-copy"></i> IP'yi Kopyala
						</button>
					</div>
					<p class="mb-0 text-danger"><strong>Not:</strong> "Geçersiz IP" hatası alıyorsanız, yukarıdaki IP adresini Eryaz yetkililerine bildirin!</p>
				</div>
			</div>
		</div>
	</div>
</div>

<div class="row">
	<div class="col-md-12">
		<div class="card">
			<div class="card-header">
				<h5 class="card-title mb-0">IP Bilgisi ve Arama Parametreleri</h5>
			</div>
			<div class="card-body">
				<!-- IP Bilgisi - Her Zaman Görünür -->
				<div class="alert alert-warning mb-3" style="border-left: 5px solid #ffc107; background-color: #fff3cd;">
					<div class="d-flex align-items-center mb-2">
						<i class="fa fa-exclamation-triangle fa-2x text-warning mr-3"></i>
						<div>
							<strong style="font-size: 1.1em;">Eryaz API'nin Göreceği IP Adresi (ÖNEMLİ!)</strong>
							<p class="mb-0 text-muted">Bu IP adresini Eryaz yetkililerine bildirmeniz gerekmektedir.</p>
						</div>
					</div>
					
					<div class="text-center mt-3 mb-3" style="background-color: #f8f9fa; padding: 20px; border-radius: 5px; border: 2px solid #007bff;">
						<p class="mb-2"><strong>IP Adresiniz:</strong></p>
						<h2 class="mb-0" style="color: #007bff; font-weight: bold; font-family: monospace;">
							<?php 
							if ($detectedIP && $detectedIP !== 'Tespit edilemedi') {
								echo htmlspecialchars($detectedIP);
							} else {
								// En azından bir IP göster
								$fallbackIP = $serverIP ?: $remoteIP ?: 'IP tespit edilemedi';
								echo htmlspecialchars($fallbackIP);
							}
							?>
						</h2>
						<button class="btn btn-sm btn-outline-primary mt-2" onclick="copyIP()">
							<i class="fa fa-copy"></i> IP'yi Kopyala
						</button>
					</div>
					
					<small class="text-muted mt-2 d-block">
						<strong>Önemli:</strong> Bu IP adresi, Eryaz API'ye yapılan isteklerde görünecek IP adresidir. 
						Eğer "Geçersiz IP" hatası alıyorsanız, yukarıdaki IP adresini kopyalayıp Eryaz yetkililerine bildirmeniz gerekmektedir.
					</small>
					
					<!-- Debug Bilgisi -->
					<details class="mt-3" open>
						<summary class="text-muted" style="cursor: pointer; font-size: 0.9em; font-weight: bold;">IP Tespit Detayları</summary>
						<div class="mt-2 p-3 bg-light rounded">
							<table class="table table-sm table-bordered mb-0">
								<tr class="table-success">
									<td><strong>VDS IP Adresi (Sabit):</strong></td>
									<td><code><strong style="color: #28a745; font-size: 1.1em;"><?php echo htmlspecialchars($ipDebug['vds_ip']); ?></strong></code></td>
								</tr>
								<tr>
									<td><strong>SERVER_ADDR:</strong></td>
									<td><code><?php echo htmlspecialchars($ipDebug['server_addr']); ?></code></td>
								</tr>
								<tr>
									<td><strong>HTTP_X_REAL_IP:</strong></td>
									<td><code><?php echo htmlspecialchars($ipDebug['http_x_real_ip']); ?></code></td>
								</tr>
								<tr>
									<td><strong>HTTP_X_FORWARDED_FOR:</strong></td>
									<td><code><?php echo htmlspecialchars($ipDebug['forwarded_for']); ?></code></td>
								</tr>
								<tr>
									<td><strong>REMOTE_ADDR:</strong></td>
									<td><code><?php echo htmlspecialchars($ipDebug['remote_addr']); ?></code></td>
								</tr>
								<tr class="table-primary">
									<td><strong>Eryaz'ın Göreceği IP (Kullanılan):</strong></td>
									<td><code><strong style="color: #007bff; font-size: 1.1em;"><?php echo htmlspecialchars($detectedIP); ?></strong></code></td>
								</tr>
							</table>
						</div>
					</details>
				</div>
				
				<hr>
				<form method="GET" action="" class="row">
					<input type="hidden" name="sayfa" value="eryaz-urunler">
					<div class="col-md-3">
						<label>Sayfa Başına Kayıt:</label>
						<select name="per_page" class="form-control" onchange="this.form.page.value=1; this.form.submit();">
							<?php foreach ($allowedPerPage as $option): ?>
								<option value="<?php echo $option; ?>" <?php echo $perPage == $option ? 'selected' : ''; ?>>
									<?php echo $option; ?> Kayıt
								</option>
							<?php endforeach; ?>
						</select>
						<small class="text-muted d-block mt-1">💡 Performans için önerilen: 25-50 kayıt</small>
					</div>
					<div class="col-md-3">
						<label>Sayfa Numarası:</label>
						<input type="number" name="page" class="form-control" value="<?php echo $page; ?>" min="1">
					</div>
					<div class="col-md-3">
						<label>Kayıt Aralığı:</label>
						<div class="form-control" style="background-color: #f8f9fa;">
							<small><?php echo number_format($start); ?> - <?php echo number_format($end); ?></small>
						</div>
					</div>
					<div class="col-md-3">
						<label>&nbsp;</label><br>
						<button type="button" class="btn btn-success btn-lg" onclick="loadProducts()" id="loadBtn">
							<i class="fa fa-download"></i> Ürünleri Yükle
						</button>
						<button type="button" class="btn btn-primary btn-lg mt-2 d-block" id="createCategoriesBtn" onclick="createCategoriesFromManufacturers()" style="width: 100%;">
							<i class="fa fa-tags"></i> Manufacturer'ları Kategoriye Dönüştür
						</button>
						<button type="button" class="btn btn-warning btn-lg mt-2 d-block" id="importProductsBtn" onclick="importProducts()" style="width: 100%;">
							<i class="fa fa-database"></i> Ürünleri Veritabanına Aktar
						</button>
						<button type="button" class="btn btn-dark btn-lg mt-2 d-block" id="updateEryazPricesBtn" onclick="updatePricesFromEryaz()" style="width: 100%;">
							<i class="fa fa-refresh"></i> Eryaz Fiyatlarını Güncelle (mevcut ürünler)
						</button>
						<small class="text-muted d-block mt-1">Seçili sayfa aralığındaki Eryaz ürünlerini çeker; sitede aynı stok kodu varsa yalnızca fiyat / liste fiyatı alanlarını günceller. Tüm katalog için sayfayı değiştirip tekrarlayın veya kayıt sayısını artırın.</small>
						<button type="button" class="btn btn-info btn-lg mt-2 d-block" id="updateTecDocBtn" onclick="updateTecDocData()" style="width: 100%;">
							<i class="fa fa-sync"></i> TecDoc'dan OEM Kodları ve Görselleri Çek
						</button>
						<button type="button" class="btn btn-secondary btn-sm mt-2 d-block" onclick="loadViaAjax()" style="width: 100%;">
							AJAX Test
						</button>
					</div>
				</form>
			</div>
		</div>
	</div>
</div>

<div class="row" style="margin: 0;">
	<div class="col-12" style="padding: 0; margin: 0;">
		<div class="card" style="margin: 0;">
			<div class="card-body" style="padding: 15px; width: 100%; overflow-x: hidden;">
				<!-- Yükleme Durumu -->
				<div id="loadingStatus" class="alert alert-info" style="display: none;">
					<i class="fa fa-spinner fa-spin"></i> Ürünler yükleniyor, lütfen bekleyin...
				</div>
				
				<!-- Sonuç Alanı -->
				<div id="productResults" style="width: 100%; margin: 0; padding: 0;">
					<?php if ($result === null): ?>
						<div class="alert alert-warning text-center">
							<i class="fa fa-info-circle fa-2x mb-3"></i>
							<h5>Ürünleri Yüklemek İçin Butona Tıklayın</h5>
							<p class="mb-0">Yukarıdaki <strong>"Ürünleri Yükle"</strong> butonuna tıklayarak API'den ürünleri çekebilirsiniz.</p>
						</div>
					<?php elseif (isset($result['success']) && $result['success']): ?>
					<?php
					$products = $result['data'];
					
					// Data içinde Error kontrolü
					$hasError = false;
					$errorMessage = '';
					if (isset($products['Data']) && is_array($products['Data'])) {
						foreach ($products['Data'] as $item) {
							if (is_array($item) && isset($item['Error'])) {
								$hasError = true;
								$errorMessage = $item['Error'];
								break;
							}
						}
					}
					
					// Data yapısını kontrol et - ürünler Data içinde olabilir
					$productList = [];
					if (isset($products['Data']) && is_array($products['Data'])) {
						$productList = $products['Data'];
					} elseif (is_array($products)) {
						$productList = $products;
					}
					
					if ($hasError):
					?>
						<div class="alert alert-danger">
							<strong><i class="fa fa-exclamation-triangle"></i> API Hatası!</strong>
							<p><strong>Hata Mesajı:</strong> <span class="badge badge-danger badge-lg"><?php echo htmlspecialchars($errorMessage); ?></span></p>
							
							<?php
							// Geçersiz IP hatası için özel gösterim
							if (stripos($errorMessage, 'IP') !== false || stripos($errorMessage, 'Geçersiz IP') !== false):
								$displayIP = $detectedIP && $detectedIP !== 'Tespit edilemedi' ? $detectedIP : ($serverIP ?: $remoteIP ?: 'IP tespit edilemedi');
							?>
								<div class="alert alert-warning mt-3" style="background-color: #fff3cd; border-left: 5px solid #ffc107;">
									<strong><i class="fa fa-exclamation-triangle fa-2x"></i> ÖNEMLİ: IP Adresi Bilgisi</strong>
									<div class="mt-3 text-center" style="background-color: #f8f9fa; padding: 25px; border-radius: 8px; border: 3px solid #007bff;">
										<p class="mb-2" style="font-size: 1.1em;"><strong>Eryaz API'nin gördüğü IP adresiniz:</strong></p>
										<h1 class="mb-3" style="color: #007bff; font-weight: bold; font-family: 'Courier New', monospace; font-size: 2em; letter-spacing: 2px;">
											<?php echo htmlspecialchars($displayIP); ?>
										</h1>
										<button class="btn btn-primary btn-lg" onclick="copyIP('<?php echo htmlspecialchars($displayIP); ?>')">
											<i class="fa fa-copy"></i> IP Adresini Kopyala
										</button>
										<div class="alert alert-info mt-3 mb-0 text-left">
											<p class="mb-1"><strong>Çözüm Adımları:</strong></p>
											<ol class="mb-0 pl-3">
												<li>Yukarıdaki IP adresini (<strong><?php echo htmlspecialchars($displayIP); ?></strong>) kopyalayın</li>
												<li>Eryaz yetkilileriyle iletişime geçin</li>
												<li>Bu IP adresini izinli IP listesine ekletmelerini isteyin</li>
											</ol>
										</div>
									</div>
								</div>
							<?php endif; ?>
							
							<details class="mt-2">
								<summary><strong>API Yanıt Detayları</strong></summary>
								<pre class="mt-2" style="max-height: 300px; overflow-y: auto;"><?php echo htmlspecialchars(json_encode($products, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)); ?></pre>
							</details>
						</div>
					<?php
					// Eğer data bir array ise ve içinde veri varsa
					elseif (is_array($productList) && !empty($productList)):
					?>
						<div class="alert alert-success">
							<strong>Başarılı!</strong> 
							Bu sayfada <strong><?php echo count($productList); ?></strong> ürün gösteriliyor.
							<?php if (isset($products['Count'])): ?>
								<span class="badge badge-info">Toplam: <?php echo number_format($products['Count']); ?> ürün</span>
							<?php endif; ?>
						</div>
						
						<!-- API Yanıtını Görüntüle (Debug için) -->
						<div class="mb-4">
							<button class="btn btn-info btn-sm" type="button" data-toggle="collapse" data-target="#apiResponse" aria-expanded="false">
								API Ham Yanıtını Görüntüle
							</button>
							<div class="collapse mt-3" id="apiResponse">
								<div class="card card-body">
									<pre style="max-height: 400px; overflow-y: auto;"><?php echo htmlspecialchars(json_encode($products, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)); ?></pre>
								</div>
							</div>
						</div>
						
						<!-- Ürün Listesi Tablosu -->
						<div class="table-responsive">
							<table class="table table-striped table-bordered text-nowrap mb-0">
								<thead>
									<tr class="bold border-bottom">
										<th class="border-bottom-0">#</th>
										<?php
										// İlk ürünün key'lerini başlık olarak kullan
										$firstProduct = reset($productList);
										if (is_array($firstProduct)):
											foreach (array_keys($firstProduct) as $key):
												echo '<th class="border-bottom-0">' . htmlspecialchars($key) . '</th>';
											endforeach;
										endif;
										?>
									</tr>
								</thead>
								<tbody>
									<?php
									$counter = 1;
									foreach ($productList as $product):
										if (is_array($product) && !isset($product['Error'])):
									?>
										<tr>
											<td><?php echo $counter++; ?></td>
											<?php foreach ($product as $value): ?>
												<td>
													<?php 
													if (is_array($value)) {
														echo '<small>' . htmlspecialchars(json_encode($value, JSON_UNESCAPED_UNICODE)) . '</small>';
													} elseif (is_bool($value)) {
														echo $value ? 'Evet' : 'Hayır';
													} else {
														echo htmlspecialchars(substr($value, 0, 100)) . (strlen($value) > 100 ? '...' : '');
													}
													?>
												</td>
											<?php endforeach; ?>
										</tr>
									<?php
										endif;
									endforeach;
									?>
								</tbody>
							</table>
						</div>
						
					<?php 
					else: ?>
						<div class="alert alert-warning">
							<strong>Uyarı!</strong> API yanıtı boş veya beklenmeyen formatta.
							<details class="mt-2">
								<summary>Yanıt Detayı</summary>
								<pre><?php print_r($products); ?></pre>
							</details>
						</div>
					<?php endif; ?>
					
					<?php elseif (isset($result['success']) && !$result['success']): ?>
						<div class="alert alert-danger">
							<strong><i class="fa fa-exclamation-triangle"></i> Hata!</strong> API çağrısı başarısız oldu.
						<hr>
						<p><strong>Hata Mesajı:</strong> <span class="badge badge-danger"><?php echo htmlspecialchars($result['error'] ?? 'Bilinmeyen hata'); ?></span></p>
						
						<?php 
						// Geçersiz IP hatası için özel mesaj
						if (stripos($result['error'] ?? '', 'IP') !== false || stripos($result['error'] ?? '', 'Geçersiz IP') !== false): 
							$displayIP = $detectedIP && $detectedIP !== 'Tespit edilemedi' ? $detectedIP : ($serverIP ?: $remoteIP ?: 'IP tespit edilemedi');
						?>
							<div class="alert alert-warning mt-3" style="background-color: #fff3cd; border-left: 5px solid #ffc107;">
								<strong><i class="fa fa-exclamation-triangle"></i> ÖNEMLİ: IP Adresi Bilgisi</strong>
								<div class="mt-3 text-center" style="background-color: #f8f9fa; padding: 20px; border-radius: 5px; border: 2px solid #007bff;">
									<p class="mb-2"><strong>Eryaz API'nin gördüğü IP adresiniz:</strong></p>
									<h2 class="mb-2" style="color: #007bff; font-weight: bold; font-family: monospace;">
										<?php echo htmlspecialchars($displayIP); ?>
									</h2>
									<button class="btn btn-sm btn-outline-primary" onclick="copyIP('<?php echo htmlspecialchars($displayIP); ?>')">
										<i class="fa fa-copy"></i> IP'yi Kopyala
									</button>
									<p class="mt-3 mb-0">
										<strong>Çözüm:</strong> Yukarıdaki IP adresini kopyalayıp Eryaz yetkililerine bildirerek izinli IP listesine ekletmeniz gerekmektedir.
									</p>
								</div>
							</div>
						<?php endif; ?>
						
						<?php if (isset($result['error_description'])): ?>
							<div class="alert alert-info mt-3">
								<strong><i class="fa fa-info-circle"></i> Hata Açıklaması:</strong>
								<div class="mb-0"><?php echo $result['error_description']; ?></div>
							</div>
						<?php endif; ?>
						
						<?php if (isset($result['api_response'])): ?>
							<details class="mt-2">
								<summary><strong>API Yanıt Detayları</strong></summary>
								<pre class="mt-2" style="max-height: 300px; overflow-y: auto;"><?php echo htmlspecialchars(json_encode($result['api_response'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)); ?></pre>
							</details>
						<?php endif; ?>
						
						<?php if (isset($result['raw_response'])): ?>
							<details class="mt-2">
								<summary><strong>Ham Yanıt (Raw Response)</strong></summary>
								<pre class="mt-2" style="max-height: 300px; overflow-y: auto;"><?php echo htmlspecialchars($result['raw_response']); ?></pre>
							</details>
						<?php endif; ?>
					</div>
					<?php endif; ?>
				</div>
			</div>
		</div>
	</div>
</div>

<script>
function loadProducts() {
	const page = parseInt(document.querySelector('input[name="page"]').value) || 1;
	const perPage = parseInt(document.querySelector('select[name="per_page"]').value) || 50;
	const start = ((page - 1) * perPage) + 1;
	const end = start + perPage - 1;
	
	console.log('Sayfalama Bilgisi:', { page, perPage, start, end });
	
	// Butonu devre dışı bırak
	const loadBtn = document.getElementById('loadBtn');
	const originalText = loadBtn.innerHTML;
	loadBtn.disabled = true;
	loadBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Yükleniyor...';
	
	// Yükleme durumunu göster
	document.getElementById('loadingStatus').style.display = 'block';
	document.getElementById('productResults').innerHTML = '';
	
	// API çağrısı - start ve end parametrelerini gönder
	const apiUrl = '../api-eryaz.php?ajax=1&action=getProductList&start=' + start + '&end=' + end;
	console.log('API URL:', apiUrl);
	
	fetch(apiUrl)
		.then(response => response.json())
		.then(data => {
			// Yükleme durumunu gizle
			document.getElementById('loadingStatus').style.display = 'none';
			
			// Butonu tekrar aktif et
			loadBtn.disabled = false;
			loadBtn.innerHTML = originalText;
			
			// Sonuçları göster
			displayResults(data);
		})
		.catch(error => {
			console.error('Hata:', error);
			document.getElementById('loadingStatus').style.display = 'none';
			loadBtn.disabled = false;
			loadBtn.innerHTML = originalText;
			
			document.getElementById('productResults').innerHTML = 
				'<div class="alert alert-danger">' +
				'<strong>Hata!</strong> API çağrısı başarısız oldu: ' + error.message +
				'</div>';
		});
}

function displayResults(data) {
	let html = '';
	
	// Sayfalama bilgilerini al
	const page = parseInt(document.querySelector('input[name="page"]').value) || 1;
	const perPage = parseInt(document.querySelector('select[name="per_page"]').value) || 50;
	
	if (data.success && data.data) {
		let allProducts = data.data.Data || data.data || [];
		
		// Eğer array değilse, array'e çevir
		if (!Array.isArray(allProducts)) {
			allProducts = [];
		}
		
		if (allProducts.length > 0) {
			// İlk ürünün Error olup olmadığını kontrol et
			if (allProducts[0] && allProducts[0].Error) {
				html = '<div class="alert alert-danger">' +
					'<strong>API Hatası!</strong> ' + allProducts[0].Error +
					'</div>';
			} else {
				// Sayfalama uygula - sadece istenen aralıktaki ürünleri göster
				const startIndex = (page - 1) * perPage;
				const endIndex = startIndex + perPage;
				const products = allProducts.slice(startIndex, endIndex);
				
				// Toplam kayıt sayısı
				const totalProducts = allProducts.length;
				const totalPages = Math.ceil(totalProducts / perPage);
				
				// Ürün listesi bilgisi
				html = '<div class="alert alert-success">' +
					'<strong>Başarılı!</strong> ' +
					'Bu sayfada <strong>' + products.length + '</strong> ürün gösteriliyor. ' +
					'Toplam: <strong>' + totalProducts + '</strong> ürün. ' +
					'Sayfa: <strong>' + page + ' / ' + totalPages + '</strong>' +
					'</div>';
				
				// Tablo - Tam genişlik, tüm sütunlar yan yana
				html += '<div class="table-responsive" style="width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch; margin: 0; padding: 0;">' +
					'<table class="table table-striped table-bordered mb-0" style="width: 100%; min-width: 100%; table-layout: auto; margin: 0;">' +
					'<thead><tr class="bold border-bottom" style="background-color: #f8f9fa;">' +
					'<th class="border-bottom-0" style="white-space: nowrap; padding: 10px; min-width: 60px; position: sticky; left: 0; background-color: #f8f9fa; z-index: 10; border-right: 2px solid #dee2e6;">#</th>';
				
				// Başlıklar - Tüm sütunlar yan yana
				const firstProduct = products[0];
				if (firstProduct && typeof firstProduct === 'object') {
					Object.keys(firstProduct).forEach(key => {
						html += '<th class="border-bottom-0" style="white-space: nowrap; padding: 10px; text-align: left; background-color: #f8f9fa; min-width: 120px; display: table-cell;">' + escapeHtml(key) + '</th>';
					});
				}
				
				html += '</tr></thead><tbody style="display: table-row-group;">';
				
				// Satırlar - sayfa numarasına göre sıra numarası, tüm sütunlar yan yana
				const startNumber = (page - 1) * perPage + 1;
				products.forEach((product, index) => {
					if (product && typeof product === 'object' && !product.Error) {
						html += '<tr style="display: table-row;">' +
							'<td style="white-space: nowrap; padding: 8px; font-weight: bold; position: sticky; left: 0; background-color: white; z-index: 5; border-right: 2px solid #dee2e6; display: table-cell;">' + (startNumber + index) + '</td>';
						Object.values(product).forEach(value => {
							let displayValue = '';
							if (Array.isArray(value)) {
								displayValue = '<div style="white-space: pre-wrap; word-break: break-word; max-width: 400px; max-height: 200px; overflow-y: auto; font-size: 0.85em; padding: 4px;">' + escapeHtml(JSON.stringify(value, null, 2)) + '</div>';
							} else if (typeof value === 'boolean') {
								displayValue = '<span class="badge badge-' + (value ? 'success' : 'secondary') + '">' + (value ? 'Evet' : 'Hayır') + '</span>';
							} else if (value === null || value === undefined) {
								displayValue = '<span class="text-muted">-</span>';
							} else {
								// Tüm değeri göster (kısaltma yok, uzun metinler için scroll)
								const strValue = String(value);
								displayValue = '<div style="white-space: pre-wrap; word-break: break-word; max-width: 350px; max-height: 150px; overflow-y: auto; font-size: 0.9em; padding: 4px;">' + escapeHtml(strValue) + '</div>';
							}
							html += '<td style="white-space: nowrap; padding: 8px; vertical-align: top; min-width: 120px; display: table-cell;">' + displayValue + '</td>';
						});
						html += '</tr>';
					}
				});
				
				html += '</tbody></table></div>';
				
				// Tablo genişliği için stil ekle - Tam genişlik, tüm sütunlar yan yana
				html += '<style>' +
					'#productResults { width: 100% !important; margin: 0 !important; padding: 0 !important; }' +
					'#productResults .table-responsive { width: 100% !important; margin: 0 !important; padding: 0 !important; max-width: 100% !important; overflow-x: auto !important; display: block !important; }' +
					'#productResults table { width: 100% !important; min-width: max-content !important; table-layout: auto !important; margin: 0 !important; border-collapse: collapse !important; display: table !important; }' +
					'#productResults table thead { display: table-header-group !important; }' +
					'#productResults table tbody { display: table-row-group !important; }' +
					'#productResults table th, #productResults table td { white-space: nowrap !important; padding: 8px 12px !important; border: 1px solid #dee2e6 !important; display: table-cell !important; }' +
					'#productResults table td > div { white-space: pre-wrap !important; word-break: break-word !important; }' +
					'#productResults table thead th { position: sticky; top: 0; z-index: 100; background-color: #f8f9fa !important; }' +
					'.container { max-width: 100% !important; width: 100% !important; padding: 15px !important; }' +
					'.main-content .container { max-width: 100% !important; width: 100% !important; }' +
					'.card-body { padding: 15px !important; width: 100% !important; }' +
					'</style>';
				
				// Sayfalama navigasyonu ekle
				if (totalPages > 1) {
					html += '<div class="d-flex justify-content-between align-items-center mt-3">';
					html += '<div><small>Gösterilen: ' + (startIndex + 1) + ' - ' + Math.min(endIndex, totalProducts) + ' / Toplam: ' + totalProducts + '</small></div>';
					html += '<nav><ul class="pagination pagination-sm mb-0">';
					
					// Önceki sayfa
					if (page > 1) {
						html += '<li class="page-item"><a class="page-link" href="javascript:void(0)" onclick="changePage(' + (page - 1) + ')">Önceki</a></li>';
					}
					
					// Sayfa numaraları
					const startPage = Math.max(1, page - 2);
					const endPage = Math.min(totalPages, page + 2);
					
					if (startPage > 1) {
						html += '<li class="page-item"><a class="page-link" href="javascript:void(0)" onclick="changePage(1)">1</a></li>';
						if (startPage > 2) html += '<li class="page-item disabled"><span class="page-link">...</span></li>';
					}
					
					for (let i = startPage; i <= endPage; i++) {
						html += '<li class="page-item ' + (i === page ? 'active' : '') + '">';
						html += '<a class="page-link" href="javascript:void(0)" onclick="changePage(' + i + ')">' + i + '</a>';
						html += '</li>';
					}
					
					if (endPage < totalPages) {
						if (endPage < totalPages - 1) html += '<li class="page-item disabled"><span class="page-link">...</span></li>';
						html += '<li class="page-item"><a class="page-link" href="javascript:void(0)" onclick="changePage(' + totalPages + ')">' + totalPages + '</a></li>';
					}
					
					// Sonraki sayfa
					if (page < totalPages) {
						html += '<li class="page-item"><a class="page-link" href="javascript:void(0)" onclick="changePage(' + (page + 1) + ')">Sonraki</a></li>';
					}
					
					html += '</ul></nav>';
					html += '</div>';
				}
			}
		} else {
			html = '<div class="alert alert-warning">Ürün bulunamadı.</div>';
		}
	} else {
		html = '<div class="alert alert-danger">' +
			'<strong>Hata!</strong> ' + (data.message || data.error || 'Bilinmeyen hata') +
			'</div>';
	}
	
	document.getElementById('productResults').innerHTML = html;
}

function changePage(newPage) {
	document.querySelector('input[name="page"]').value = newPage;
	loadProducts();
}

function escapeHtml(text) {
	const map = {
		'&': '&amp;',
		'<': '&lt;',
		'>': '&gt;',
		'"': '&quot;',
		"'": '&#039;'
	};
	return String(text).replace(/[&<>"']/g, m => map[m]);
}

function createCategoriesFromManufacturers() {
	const btn = document.getElementById('createCategoriesBtn');
	const originalText = btn.innerHTML;
	btn.disabled = true;
	btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Kategoriler oluşturuluyor...';
	
	const apiUrl = '../api-eryaz.php?ajax=1&action=createCategoriesFromManufacturers';
	
	fetch(apiUrl)
		.then(response => response.json())
		.then(data => {
			btn.disabled = false;
			btn.innerHTML = originalText;
			
			if (data.success) {
				let message = '<div class="alert alert-success">' +
					'<h5><i class="fa fa-check-circle"></i> İşlem Tamamlandı!</h5>' +
					'<p><strong>Yeni Oluşturulan Kategoriler:</strong> ' + data.total_created + '</p>' +
					'<p><strong>Zaten Mevcut Kategoriler:</strong> ' + data.total_existing + '</p>';
				
				if (data.total_created > 0) {
					message += '<details class="mt-2"><summary>Yeni Oluşturulan Kategoriler</summary><ul class="mt-2">';
					for (const [name, id] of Object.entries(data.created)) {
						message += '<li><strong>' + escapeHtml(name) + '</strong> (ID: ' + id + ')</li>';
					}
					message += '</ul></details>';
				}
				
				if (data.total_existing > 0) {
					message += '<details class="mt-2"><summary>Zaten Mevcut Kategoriler</summary><ul class="mt-2">';
					for (const [name, id] of Object.entries(data.existing)) {
						message += '<li><strong>' + escapeHtml(name) + '</strong> (ID: ' + id + ')</li>';
					}
					message += '</ul></details>';
				}
				
				if (data.errors && data.errors.length > 0) {
					message += '<div class="alert alert-warning mt-2"><strong>Hatalar:</strong><ul>';
					data.errors.forEach(error => {
						message += '<li>' + escapeHtml(error) + '</li>';
					});
					message += '</ul></div>';
				}
				
				message += '</div>';
				
				// Sonuçları göster
				const resultDiv = document.createElement('div');
				resultDiv.innerHTML = message;
				document.getElementById('productResults').insertBefore(resultDiv, document.getElementById('productResults').firstChild);
				
				// Sayfayı en üste kaydır
				window.scrollTo({ top: 0, behavior: 'smooth' });
			} else {
				document.getElementById('productResults').innerHTML = 
					'<div class="alert alert-danger">' +
					'<strong>Hata!</strong> ' + (data.error || 'Bilinmeyen hata') +
					'</div>';
			}
		})
		.catch(error => {
			console.error('Hata:', error);
			btn.disabled = false;
			btn.innerHTML = originalText;
			
			document.getElementById('productResults').innerHTML = 
				'<div class="alert alert-danger">' +
				'<strong>Hata!</strong> İşlem başarısız oldu: ' + error.message +
				'</div>';
		});
}

function updatePricesFromEryaz() {
	const btn = document.getElementById('updateEryazPricesBtn');
	if (!btn) return;
	const originalText = btn.innerHTML;
	btn.disabled = true;
	btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Fiyatlar güncelleniyor...';
	
	const page = parseInt(document.querySelector('input[name="page"]').value) || 1;
	const perPage = parseInt(document.querySelector('select[name="per_page"]').value) || 50;
	const start = ((page - 1) * perPage) + 1;
	const end = start + perPage - 1;
	
	const apiUrl = '../api-eryaz.php?ajax=1&action=updatePricesFromEryaz&start=' + start + '&end=' + end;
	
	fetch(apiUrl)
		.then(response => response.json())
		.then(data => {
			btn.disabled = false;
			btn.innerHTML = originalText;
			
			if (data.success) {
				let message = '<div class="alert alert-success">' +
					'<h5><i class="fa fa-check-circle"></i> Fiyat güncelleme tamamlandı</h5>' +
					'<p><strong>Güncellenen ürün:</strong> ' + (data.price_updated || 0) + '</p>' +
					'<p><strong>Sitede olmayan (stok kodu eşleşmedi):</strong> ' + (data.not_in_db || 0) + '</p>' +
					'<p><strong>Atlanan / hatalı satır:</strong> ' + (data.skipped || 0) + '</p>' +
					'<p><strong>API\'den gelen satır:</strong> ' + (data.total_from_api || 0) + '</p>';
				if (data.range) {
					message += '<p class="mb-0"><small>Aralık: ' + data.range.start + ' – ' + data.range.end + '</small></p>';
				}
				if (data.error_count > 0) {
					message += '<div class="alert alert-warning mt-2 mb-0"><strong>Hatalar:</strong> ' + data.error_count + '</div>';
				}
				message += '</div>';
				const resultDiv = document.createElement('div');
				resultDiv.innerHTML = message;
				const pr = document.getElementById('productResults');
				if (pr.firstChild) {
					pr.insertBefore(resultDiv, pr.firstChild);
				} else {
					pr.appendChild(resultDiv);
				}
				window.scrollTo({ top: 0, behavior: 'smooth' });
			} else {
				document.getElementById('productResults').innerHTML =
					'<div class="alert alert-danger"><strong>Hata!</strong> ' + (data.error || 'Bilinmeyen hata') + '</div>';
			}
		})
		.catch(error => {
			console.error('Hata:', error);
			btn.disabled = false;
			btn.innerHTML = originalText;
			document.getElementById('productResults').innerHTML =
				'<div class="alert alert-danger"><strong>Hata!</strong> ' + error.message + '</div>';
		});
}

function importProducts() {
	const btn = document.getElementById('importProductsBtn');
	const originalText = btn.innerHTML;
	btn.disabled = true;
	btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Ürünler aktarılıyor...';
	
	// Sayfalama bilgilerini al
	const page = parseInt(document.querySelector('input[name="page"]').value) || 1;
	const perPage = parseInt(document.querySelector('select[name="per_page"]').value) || 50;
	const start = ((page - 1) * perPage) + 1;
	const end = start + perPage - 1;
	
	const apiUrl = '../api-eryaz.php?ajax=1&action=importProducts&start=' + start + '&end=' + end;
	
	fetch(apiUrl)
		.then(response => response.json())
		.then(data => {
			btn.disabled = false;
			btn.innerHTML = originalText;
			
			if (data.success) {
				let message = '<div class="alert alert-success">' +
					'<h5><i class="fa fa-check-circle"></i> İşlem Tamamlandı!</h5>' +
					'<p><strong>Yeni Eklenen Ürünler:</strong> ' + data.imported + '</p>' +
					'<p><strong>Güncellenen Ürünler:</strong> ' + data.updated + '</p>' +
					'<p><strong>Atlanan Ürünler:</strong> ' + data.skipped + '</p>' +
					'<p><strong>Toplam İşlenen:</strong> ' + data.total + '</p>';
				
				if (data.error_count > 0) {
					message += '<div class="alert alert-warning mt-2">' +
						'<strong>Hatalar (' + data.error_count + '):</strong>' +
						'<details class="mt-2"><summary>Hata Detayları</summary><ul class="mt-2">';
					data.errors.forEach(error => {
						message += '<li><strong>' + escapeHtml(error.product) + ':</strong> ' + escapeHtml(error.error) + '</li>';
					});
					message += '</ul></details></div>';
				}
				
				message += '</div>';
				
				// Sonuçları göster
				const resultDiv = document.createElement('div');
				resultDiv.innerHTML = message;
				document.getElementById('productResults').insertBefore(resultDiv, document.getElementById('productResults').firstChild);
				
				// Sayfayı en üste kaydır
				window.scrollTo({ top: 0, behavior: 'smooth' });
			} else {
				document.getElementById('productResults').innerHTML = 
					'<div class="alert alert-danger">' +
					'<strong>Hata!</strong> ' + (data.error || 'Bilinmeyen hata') +
					'</div>';
			}
		})
		.catch(error => {
			console.error('Hata:', error);
			btn.disabled = false;
			btn.innerHTML = originalText;
			
			document.getElementById('productResults').innerHTML = 
				'<div class="alert alert-danger">' +
				'<strong>Hata!</strong> İşlem başarısız oldu: ' + error.message +
				'</div>';
		});
}

function loadViaAjax() {
	const page = document.querySelector('input[name="page"]').value || 1;
	const perPage = document.querySelector('select[name="per_page"]').value || 50;
	const start = ((page - 1) * perPage) + 1;
	const end = parseInt(start) + parseInt(perPage) - 1;
	
	fetch('../api-eryaz.php?ajax=1&action=getProductList&start=' + start + '&end=' + end)
		.then(response => response.json())
		.then(data => {
			console.log('AJAX Yanıt:', data);
			alert('AJAX ile veri çekildi! Konsolu kontrol edin.\n\nBaşarılı: ' + (data.success ? 'Evet' : 'Hayır') + '\nÜrün Sayısı: ' + (data.count || 0));
		})
		.catch(error => {
			console.error('Hata:', error);
			alert('AJAX hatası: ' + error.message);
		});
}

function copyIP(ip) {
	// IP adresini bul
	if (!ip) {
		const ipElement = document.querySelector('h2[style*="monospace"]');
		if (ipElement) {
			ip = ipElement.textContent.trim();
		} else {
			// Alternatif: badge içindeki IP'yi bul
			const badge = document.querySelector('.badge-primary');
			if (badge) {
				ip = badge.textContent.trim();
			}
		}
	}
	
	if (ip && ip !== 'IP tespit edilemedi' && ip !== 'Tespit edilemedi') {
		// Clipboard API kullan
		if (navigator.clipboard && navigator.clipboard.writeText) {
			navigator.clipboard.writeText(ip).then(function() {
				alert('IP adresi kopyalandı: ' + ip);
			}).catch(function(err) {
				// Fallback: Eski yöntem
				copyToClipboardFallback(ip);
			});
		} else {
			// Fallback: Eski yöntem
			copyToClipboardFallback(ip);
		}
	} else {
		alert('IP adresi bulunamadı!');
	}
}

function copyToClipboardFallback(text) {
	const textArea = document.createElement('textarea');
	textArea.value = text;
	textArea.style.position = 'fixed';
	textArea.style.left = '-999999px';
	document.body.appendChild(textArea);
	textArea.select();
	try {
		document.execCommand('copy');
		alert('IP adresi kopyalandı: ' + text);
	} catch (err) {
		alert('Kopyalama başarısız. IP adresini manuel olarak kopyalayın: ' + text);
	}
		document.body.removeChild(textArea);
	}

function updateTecDocData() {
	const btn = document.getElementById('updateTecDocBtn');
	const originalText = btn.innerHTML;
	btn.disabled = true;
	btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> TecDoc verileri çekiliyor...';
	
	const limit = prompt('Kaç ürün işlensin? (Boş bırakırsanız tümü işlenir)', '50');
	const limitParam = limit ? '&limit=' + parseInt(limit) : '';
	
	const apiUrl = '../api-tecdoc.php?ajax=1&action=updateProducts' + limitParam;
	
	fetch(apiUrl)
		.then(response => {
			// Response'un text olarak al
			return response.text().then(text => {
				// Önce text olarak kontrol et
				if (!text || text.trim() === '') {
					throw new Error('API boş yanıt döndürdü');
				}
				
				// JSON parse dene
				try {
					return JSON.parse(text);
				} catch (e) {
					// JSON parse hatası - response'u göster
					console.error('JSON Parse Hatası:', e);
					console.error('Response:', text);
					throw new Error('JSON parse hatası: ' + e.message + ' - Response: ' + text.substring(0, 200));
				}
			});
		})
		.then(data => {
			btn.disabled = false;
			btn.innerHTML = originalText;
			
			if (data.success) {
				let message = '<div class="alert alert-success">' +
					'<h5><i class="fa fa-check-circle"></i> İşlem Tamamlandı!</h5>' +
					'<p><strong>İşlenen Ürünler:</strong> ' + data.processed + '</p>' +
					'<p><strong>Güncellenen Ürünler:</strong> ' + data.updated + '</p>';
				
				if (data.errors && data.errors.length > 0) {
					message += '<div class="alert alert-warning mt-2">' +
						'<strong>Hatalar (' + data.errors.length + '):</strong>' +
						'<details class="mt-2"><summary>Hata Detayları</summary><ul class="mt-2">';
					data.errors.forEach(error => {
						message += '<li><strong>' + escapeHtml(error.product_name || 'Ürün ID: ' + error.product_id) + ':</strong> ' + escapeHtml(error.error) + '</li>';
					});
					message += '</ul></details></div>';
				}
				
				message += '</div>';
				
				// Sonuçları göster
				const resultDiv = document.createElement('div');
				resultDiv.innerHTML = message;
				document.getElementById('productResults').insertBefore(resultDiv, document.getElementById('productResults').firstChild);
				
				// Sayfayı en üste kaydır
				window.scrollTo({ top: 0, behavior: 'smooth' });
			} else {
				document.getElementById('productResults').innerHTML = 
					'<div class="alert alert-danger">' +
					'<strong>Hata!</strong> ' + (data.error || 'Bilinmeyen hata') +
					'</div>';
			}
		})
		.catch(error => {
			console.error('Hata:', error);
			btn.disabled = false;
			btn.innerHTML = originalText;
			
			document.getElementById('productResults').innerHTML = 
				'<div class="alert alert-danger">' +
				'<strong>Hata!</strong> İşlem başarısız oldu: ' + error.message +
				'</div>';
		});
}
</script>
