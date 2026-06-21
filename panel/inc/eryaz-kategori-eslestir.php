<?php
// Eryaz Ürünlerini Kategorilerle Eşleştirme Sayfası

// API sınıfını dahil et
$apiFile = __DIR__ . '/../../api-eryaz.php';
if (!file_exists($apiFile)) {
    die('<div class="alert alert-danger">HATA: api-eryaz.php dosyası bulunamadı!</div>');
}
require_once $apiFile;

if (!class_exists('EryazAPI')) {
    die('<div class="alert alert-danger">HATA: EryazAPI sınıfı yüklenemedi!</div>');
}

$eryazAPI = new EryazAPI();

// AJAX isteği kontrolü
if (isset($_POST['action']) && $_POST['action'] === 'matchCategories') {
    header('Content-Type: application/json');
    
    $start = isset($_POST['start']) ? (int)$_POST['start'] : 1;
    $end = isset($_POST['end']) ? (int)$_POST['end'] : 1000;
    
    // API'den ürünleri çek
    $result = $eryazAPI->getProductList($start, $end);
    
    if (!$result || !$result['success']) {
        echo json_encode([
            'success' => false,
            'error' => 'Ürünler çekilemedi: ' . ($result['error'] ?? 'Bilinmeyen hata')
        ]);
        exit;
    }
    
    $products = $result['data']['Data'] ?? $result['data'] ?? [];
    if (!is_array($products)) {
        $products = [];
    }
    
    $matched = 0;
    $created = 0;
    $updated = 0;
    $errors = [];
    $details = [];
    
    foreach ($products as $product) {
        if (!is_array($product) || isset($product['Error'])) {
            continue;
        }
        
        // Manufacturer ve stok kodu al
        $manufacturer = null;
        $stockCode = null;
        
        foreach ($product as $key => $value) {
            $keyLower = strtolower($key);
            if ($keyLower === 'manufacturer' && !empty($value)) {
                $manufacturer = trim($value);
            }
            if (in_array($keyLower, ['stockcode', 'sku', 'code', 'stokkodu', 'stok_kodu', 'barcode', 'barkod']) && !empty($value)) {
                $stockCode = trim($value);
            }
        }
        
        if (empty($manufacturer) || empty($stockCode)) {
            continue;
        }
        
        // Veritabanında ürünü bul (Eryaz orijinal kodu veya temiz stok_kodu ile)
        try {
            $hasEryazStockCode = $db->query("SHOW COLUMNS FROM urun LIKE 'eryaz_stok_kodu'")->fetch() !== false;
        } catch (Exception $e) {
            $hasEryazStockCode = false;
        }
        $cleanStockCode = preg_replace('/^(30-|31-|32-|3e-?)/i', '', trim((string)$stockCode));
        if ($hasEryazStockCode) {
            $productQuery = $db->prepare("SELECT id, baslik FROM urun WHERE eryaz_stok_kodu = ? OR stok_kodu = ? OR stok_kodu = ? LIMIT 1");
            $productQuery->execute([$stockCode, $stockCode, $cleanStockCode]);
        } else {
            $productQuery = $db->prepare("SELECT id, baslik FROM urun WHERE stok_kodu = ? OR stok_kodu = ? LIMIT 1");
            $productQuery->execute([$stockCode, $cleanStockCode]);
        }
        $dbProduct = $productQuery->fetch(PDO::FETCH_ASSOC);
        
        if (!$dbProduct) {
            $errors[] = "Ürün bulunamadı: " . $stockCode;
            continue;
        }
        
        $productId = (int)$dbProduct['id'];
        
        // Manufacturer'ı kategoriyle eşleştir
        $categoryResult = $eryazAPI->getOrCreateCategoryByManufacturer($manufacturer, $db);
        $categoryId = $categoryResult['id'];
        
        if (!$categoryId) {
            $errors[] = "Kategori oluşturulamadı: " . $manufacturer . " (Ürün: " . $dbProduct['baslik'] . ")";
            continue;
        }
        
        // Ürün-kategori ilişkisini kontrol et
        $checkRelation = $db->prepare("SELECT id FROM urun_kategori WHERE urun_id = ? AND kategori_id = ? LIMIT 1");
        $checkRelation->execute([$productId, $categoryId]);
        $existingRelation = $checkRelation->fetch(PDO::FETCH_ASSOC);
        
        if ($existingRelation) {
            // İlişki zaten var
            $matched++;
            $details[] = [
                'product' => $dbProduct['baslik'],
                'manufacturer' => $manufacturer,
                'category' => $categoryResult['matched_category'] ?? 'Yeni',
                'action' => 'already_matched',
                'match_type' => $categoryResult['matched'] ?? 'exact'
            ];
        } else {
            // İlişki yok, ekle
            try {
                $insertRelation = $db->prepare("INSERT INTO urun_kategori SET urun_id = ?, kategori_id = ?");
                $insertRelation->execute([$productId, $categoryId]);
                
                $updated++;
                $details[] = [
                    'product' => $dbProduct['baslik'],
                    'manufacturer' => $manufacturer,
                    'category' => $categoryResult['matched_category'] ?? 'Yeni',
                    'action' => 'matched',
                    'match_type' => $categoryResult['matched'] ?? 'exact',
                    'category_created' => $categoryResult['created'] ?? false
                ];
                
                if ($categoryResult['created'] ?? false) {
                    $created++;
                }
            } catch (Exception $e) {
                $errors[] = "İlişki eklenemedi: " . $dbProduct['baslik'] . " - " . $e->getMessage();
            }
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Eşleştirme tamamlandı',
        'matched' => $matched,
        'updated' => $updated,
        'categories_created' => $created,
        'total_processed' => count($products),
        'errors' => $errors,
        'error_count' => count($errors),
        'details' => $details
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
?>

<div class="breadcrumb-header justify-content-between">
	<div class="my-auto">
		<div class="d-flex">
			<h4 class="content-title mb-0 my-auto">Eryaz Kategori Eşleştirme</h4><span class="text-muted mt-1 tx-13 ml-2 mb-0">/ Mevcut Ürünleri Kategorilerle Eşleştir</span>
		</div>
	</div>
</div>

<div class="row">
	<div class="col-lg-12">
		<div class="card">
			<div class="card-header">
				<h3 class="card-title">Mevcut Ürünleri Kategorilerle Eşleştir</h3>
			</div>
			<div class="card-body">
				<div class="alert alert-info">
					<strong>Bilgi:</strong> Bu işlem, Eryaz API'den ürünleri çekerek mevcut veritabanındaki ürünlerin manufacturer bilgilerini kategorilerle eşleştirir.
					<br><br>
					<strong>Nasıl Çalışır:</strong>
					<ul>
						<li>Eryaz API'den ürünler çekilir</li>
						<li>Her ürünün stok_kodu ile veritabanındaki ürün bulunur</li>
						<li>Manufacturer bilgisi mevcut kategorilerle eşleştirilir</li>
						<li>Ürün-kategori ilişkisi oluşturulur veya güncellenir</li>
					</ul>
				</div>
				
				<form id="matchForm">
					<div class="form-group row">
						<label class="col-md-3 form-label">Başlangıç Kayıt</label>
						<div class="col-md-9">
							<input type="number" class="form-control" name="start" value="1" min="1" required>
							<small class="form-text text-muted">API'den kaçıncı kayıttan başlanacak</small>
						</div>
					</div>
					
					<div class="form-group row">
						<label class="col-md-3 form-label">Bitiş Kayıt</label>
						<div class="col-md-9">
							<input type="number" class="form-control" name="end" value="1000" min="1" max="1000" required>
							<small class="form-text text-muted">Maksimum 1000 kayıt işlenebilir</small>
						</div>
					</div>
					
					<button type="submit" class="btn btn-primary" id="matchBtn">
						<i class="fas fa-link"></i> Eşleştirmeyi Başlat
					</button>
				</form>
				
				<div id="matchResults" class="mt-4" style="display: none;"></div>
			</div>
		</div>
	</div>
</div>

<script>
$(document).ready(function() {
    $('#matchForm').on('submit', function(e) {
        e.preventDefault();
        
        var btn = $('#matchBtn');
        var originalText = btn.html();
        btn.prop('disabled', true);
        btn.html('<i class="fas fa-spinner fa-spin"></i> Eşleştiriliyor...');
        
        var formData = {
            action: 'matchCategories',
            start: $('input[name="start"]').val(),
            end: $('input[name="end"]').val()
        };
        
        $.ajax({
            url: 'inc/eryaz-kategori-eslestir.php',
            method: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                btn.prop('disabled', false);
                btn.html(originalText);
                
                if (response.success) {
                    var html = '<div class="alert alert-success">';
                    html += '<h5><i class="fa fa-check-circle"></i> Eşleştirme Tamamlandı!</h5>';
                    html += '<p><strong>İşlenen Ürün:</strong> ' + response.total_processed + '</p>';
                    html += '<p><strong>Zaten Eşleşmiş:</strong> ' + response.matched + '</p>';
                    html += '<p><strong>Yeni Eşleştirilen:</strong> ' + response.updated + '</p>';
                    html += '<p><strong>Yeni Oluşturulan Kategori:</strong> ' + response.categories_created + '</p>';
                    
                    if (response.error_count > 0) {
                        html += '<p><strong style="color: red;">Hata Sayısı:</strong> ' + response.error_count + '</p>';
                    }
                    
                    html += '</div>';
                    
                    if (response.details && response.details.length > 0) {
                        html += '<details class="mt-3"><summary>Detaylı Sonuçlar</summary>';
                        html += '<div class="table-responsive mt-3">';
                        html += '<table class="table table-bordered table-sm">';
                        html += '<thead><tr><th>Ürün</th><th>Manufacturer</th><th>Kategori</th><th>İşlem</th><th>Eşleşme Tipi</th></tr></thead>';
                        html += '<tbody>';
                        
                        response.details.forEach(function(detail) {
                            var matchTypeBadge = '';
                            if (detail.match_type === 'exact') {
                                matchTypeBadge = '<span class="badge badge-success">Tam Eşleşme</span>';
                            } else if (detail.match_type === 'similar') {
                                matchTypeBadge = '<span class="badge badge-info">Benzer Eşleşme</span>';
                            } else {
                                matchTypeBadge = '<span class="badge badge-secondary">Yeni</span>';
                            }
                            
                            var actionBadge = '';
                            if (detail.action === 'already_matched') {
                                actionBadge = '<span class="badge badge-secondary">Zaten Var</span>';
                            } else {
                                actionBadge = '<span class="badge badge-primary">Eşleştirildi</span>';
                            }
                            
                            html += '<tr>';
                            html += '<td>' + escapeHtml(detail.product) + '</td>';
                            html += '<td>' + escapeHtml(detail.manufacturer) + '</td>';
                            html += '<td>' + escapeHtml(detail.category) + '</td>';
                            html += '<td>' + actionBadge + '</td>';
                            html += '<td>' + matchTypeBadge + '</td>';
                            html += '</tr>';
                        });
                        
                        html += '</tbody></table></div></details>';
                    }
                    
                    if (response.errors && response.errors.length > 0) {
                        html += '<div class="alert alert-warning mt-3">';
                        html += '<strong>Hatalar:</strong><ul>';
                        response.errors.forEach(function(error) {
                            html += '<li>' + escapeHtml(error) + '</li>';
                        });
                        html += '</ul></div>';
                    }
                    
                    $('#matchResults').html(html).show();
                } else {
                    $('#matchResults').html(
                        '<div class="alert alert-danger">' +
                        '<strong>Hata!</strong> ' + (response.error || 'Bilinmeyen hata') +
                        '</div>'
                    ).show();
                }
            },
            error: function() {
                btn.prop('disabled', false);
                btn.html(originalText);
                $('#matchResults').html(
                    '<div class="alert alert-danger">' +
                    '<strong>Bağlantı Hatası!</strong> Lütfen tekrar deneyin.' +
                    '</div>'
                ).show();
            }
        });
    });
    
    function escapeHtml(text) {
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
});
</script>

