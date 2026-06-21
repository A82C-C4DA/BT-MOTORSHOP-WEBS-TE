<?php
/**
 * Çok Dilli Ürün Toplu Güncelleme Sayfası
 * Tüm ürünlerin çok dilli alanlarını toplu olarak güncellemek için
 */

// AJAX isteği kontrolü
if (isset($_POST['ajax']) && $_POST['ajax'] == '1') {
    header('Content-Type: application/json');
    
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    
    if ($action == 'update_batch') {
        $product_ids = isset($_POST['product_ids']) ? json_decode($_POST['product_ids'], true) : [];
        $update_type = isset($_POST['update_type']) ? $_POST['update_type'] : 'manual'; // manual, translate_en, translate_ru
        
        $updated = 0;
        $errors = 0;
        $results = [];
        
        foreach ($product_ids as $product_id) {
            try {
                // Ürünü çek
                $product = $db->query("SELECT id, baslik, kisa_aciklama, aciklama FROM urun WHERE id = " . (int)$product_id . " LIMIT 1")->fetch(PDO::FETCH_ASSOC);
                
                if (!$product) {
                    $errors++;
                    continue;
                }
                
                $baslik_en = '';
                $baslik_ru = '';
                $kisa_aciklama_en = '';
                $kisa_aciklama_ru = '';
                $aciklama_en = '';
                $aciklama_ru = '';
                
                if ($update_type == 'translate_en') {
                    // Google Translate API kullanarak İngilizce'ye çevir (basit bir çeviri servisi kullanılabilir)
                    // Şimdilik manuel güncelleme yapılacak
                    $baslik_en = $product['baslik']; // Geçici olarak aynı
                    $kisa_aciklama_en = $product['kisa_aciklama'];
                    $aciklama_en = $product['aciklama'];
                } elseif ($update_type == 'translate_ru') {
                    // Rusça'ya çevir
                    $baslik_ru = $product['baslik']; // Geçici olarak aynı
                    $kisa_aciklama_ru = $product['kisa_aciklama'];
                    $aciklama_ru = $product['aciklama'];
                } else {
                    // Manuel güncelleme - POST'tan al
                    $baslik_en = isset($_POST['baslik_en_' . $product_id]) ? trim($_POST['baslik_en_' . $product_id]) : '';
                    $baslik_ru = isset($_POST['baslik_ru_' . $product_id]) ? trim($_POST['baslik_ru_' . $product_id]) : '';
                    $kisa_aciklama_en = isset($_POST['kisa_aciklama_en_' . $product_id]) ? trim($_POST['kisa_aciklama_en_' . $product_id]) : '';
                    $kisa_aciklama_ru = isset($_POST['kisa_aciklama_ru_' . $product_id]) ? trim($_POST['kisa_aciklama_ru_' . $product_id]) : '';
                    $aciklama_en = isset($_POST['aciklama_en_' . $product_id]) ? trim($_POST['aciklama_en_' . $product_id]) : '';
                    $aciklama_ru = isset($_POST['aciklama_ru_' . $product_id]) ? trim($_POST['aciklama_ru_' . $product_id]) : '';
                }
                
                // Güncelle
                $updateQuery = $db->prepare("UPDATE urun SET baslik_en = ?, baslik_ru = ?, kisa_aciklama_en = ?, kisa_aciklama_ru = ?, aciklama_en = ?, aciklama_ru = ? WHERE id = ?");
                $updateQuery->execute([
                    $baslik_en,
                    $baslik_ru,
                    $kisa_aciklama_en,
                    $kisa_aciklama_ru,
                    $aciklama_en,
                    $aciklama_ru,
                    $product_id
                ]);
                
                $updated++;
                $results[] = ['id' => $product_id, 'status' => 'success'];
                
            } catch (Exception $e) {
                $errors++;
                $results[] = ['id' => $product_id, 'status' => 'error', 'message' => $e->getMessage()];
            }
        }
        
        echo json_encode([
            'success' => true,
            'updated' => $updated,
            'errors' => $errors,
            'results' => $results
        ]);
        exit;
    }
    
    if ($action == 'get_products') {
        $page = isset($_POST['page']) ? (int)$_POST['page'] : 1;
        $perPage = isset($_POST['per_page']) ? (int)$_POST['per_page'] : 50;
        $search = isset($_POST['search']) ? trim($_POST['search']) : '';
        $filter_empty = isset($_POST['filter_empty']) ? $_POST['filter_empty'] : 'all'; // all, empty_en, empty_ru, empty_both
        
        $offset = ($page - 1) * $perPage;
        
        // Sorgu oluştur
        $where = "1=1";
        $params = [];
        if (!empty($search)) {
            $where .= " AND (baslik LIKE ? OR stok_kodu LIKE ?)";
            $searchPattern = '%' . $search . '%';
            $params[] = $searchPattern;
            $params[] = $searchPattern;
        }
        
        if ($filter_empty == 'empty_en') {
            $where .= " AND (baslik_en IS NULL OR baslik_en = '')";
        } elseif ($filter_empty == 'empty_ru') {
            $where .= " AND (baslik_ru IS NULL OR baslik_ru = '')";
        } elseif ($filter_empty == 'empty_both') {
            $where .= " AND ((baslik_en IS NULL OR baslik_en = '') OR (baslik_ru IS NULL OR baslik_ru = ''))";
        }
        
        // Toplam sayı
        $totalQuery = $db->prepare("SELECT COUNT(*) as total FROM urun WHERE $where");
        $totalQuery->execute($params);
        $total = $totalQuery->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Ürünleri çek
        $query = $db->prepare("SELECT id, baslik, baslik_en, baslik_ru, kisa_aciklama, kisa_aciklama_en, kisa_aciklama_ru, stok_kodu FROM urun WHERE $where ORDER BY id DESC LIMIT $offset, $perPage");
        $query->execute($params);
        $products = $query->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'products' => $products,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => ceil($total / $perPage)
        ]);
        exit;
    }
    
    echo json_encode(['success' => false, 'error' => 'Invalid action']);
    exit;
}

// Sayfa görünümü
$perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 50;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$filter_empty = isset($_GET['filter_empty']) ? $_GET['filter_empty'] : 'all';
?>

<div class="breadcrumb-header justify-content-between">
	<div class="my-auto">
		<div class="d-flex">
			<h4 class="content-title mb-0 my-auto">Çok Dilli Ürün Toplu Güncelleme</h4>
			<span class="text-muted mt-1 tx-13 ml-2 mb-0">/ Toplu Güncelleme</span>
		</div>
	</div>
</div>

<div class="row">
	<div class="col-md-12">
		<div class="card">
			<div class="card-header">
				<h4 class="card-title">Ürünleri Filtrele</h4>
			</div>
			<div class="card-body">
				<form id="filterForm" method="get" class="row">
					<input type="hidden" name="sayfa" value="urun-cok-dilli-guncelleme">
					<div class="col-md-4">
						<label>Ara (Ürün Adı veya Stok Kodu)</label>
						<input type="text" class="form-control" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Ara...">
					</div>
					<div class="col-md-3">
						<label>Filtre</label>
						<select class="form-control" name="filter_empty">
							<option value="all" <?php echo $filter_empty == 'all' ? 'selected' : ''; ?>>Tümü</option>
							<option value="empty_en" <?php echo $filter_empty == 'empty_en' ? 'selected' : ''; ?>>İngilizce Boş</option>
							<option value="empty_ru" <?php echo $filter_empty == 'empty_ru' ? 'selected' : ''; ?>>Rusça Boş</option>
							<option value="empty_both" <?php echo $filter_empty == 'empty_both' ? 'selected' : ''; ?>>Her İkisi de Boş</option>
						</select>
					</div>
					<div class="col-md-2">
						<label>Sayfa Başına</label>
						<select class="form-control" name="per_page">
							<option value="25" <?php echo $perPage == 25 ? 'selected' : ''; ?>>25</option>
							<option value="50" <?php echo $perPage == 50 ? 'selected' : ''; ?>>50</option>
							<option value="100" <?php echo $perPage == 100 ? 'selected' : ''; ?>>100</option>
							<option value="200" <?php echo $perPage == 200 ? 'selected' : ''; ?>>200</option>
						</select>
					</div>
					<div class="col-md-3">
						<label>&nbsp;</label><br>
						<button type="submit" class="btn btn-primary">Filtrele</button>
						<button type="button" class="btn btn-secondary" onclick="loadProducts()">Yenile</button>
					</div>
				</form>
			</div>
		</div>
	</div>
</div>

<div class="row mt-3">
	<div class="col-md-12">
		<div class="card">
			<div class="card-header d-flex justify-content-between align-items-center">
				<h4 class="card-title mb-0">Ürünler</h4>
				<div>
					<button type="button" class="btn btn-sm btn-info" onclick="selectAll()">Tümünü Seç</button>
					<button type="button" class="btn btn-sm btn-warning" onclick="selectNone()">Seçimi Temizle</button>
					<button type="button" class="btn btn-sm btn-success" onclick="updateSelected()">Seçilenleri Güncelle</button>
				</div>
			</div>
			<div class="card-body">
				<div id="loadingIndicator" class="text-center" style="display: none;">
					<i class="fa fa-spinner fa-spin fa-3x"></i>
					<p>Yükleniyor...</p>
				</div>
				<div id="productsContainer">
					<!-- Ürünler buraya yüklenecek -->
				</div>
				<div id="paginationContainer" class="mt-3">
					<!-- Sayfalama buraya eklenecek -->
				</div>
			</div>
		</div>
	</div>
</div>


<script>
let currentPage = <?php echo $page; ?>;
let currentPerPage = <?php echo $perPage; ?>;
let currentSearch = '<?php echo addslashes($search); ?>';
let currentFilter = '<?php echo $filter_empty; ?>';
let selectedProducts = [];

// Sayfa yüklendiğinde ürünleri yükle
$(document).ready(function() {
	loadProducts();
});

function loadProducts() {
	$('#loadingIndicator').show();
	$('#productsContainer').html('');
	
	$.ajax({
		url: '',
		type: 'POST',
		data: {
			ajax: 1,
			action: 'get_products',
			page: currentPage,
			per_page: currentPerPage,
			search: currentSearch,
			filter_empty: currentFilter
		},
		dataType: 'json',
		success: function(response) {
			$('#loadingIndicator').hide();
			
			if (response.success) {
				displayProducts(response.products);
				displayPagination(response);
			} else {
				$('#productsContainer').html('<div class="alert alert-danger">Hata: ' + (response.error || 'Bilinmeyen hata') + '</div>');
			}
		},
		error: function() {
			$('#loadingIndicator').hide();
			$('#productsContainer').html('<div class="alert alert-danger">Ürünler yüklenirken bir hata oluştu.</div>');
		}
	});
}

function displayProducts(products) {
	if (products.length === 0) {
		$('#productsContainer').html('<div class="alert alert-info">Ürün bulunamadı.</div>');
		return;
	}
	
	let html = '<form id="productsForm">';
	html += '<div class="table-responsive"><table class="table table-bordered table-hover">';
	html += '<thead><tr>';
	html += '<th width="30"><input type="checkbox" id="selectAllCheckbox" onchange="toggleSelectAll(this)"></th>';
	html += '<th>ID</th>';
	html += '<th>Stok Kodu</th>';
	html += '<th>Ürün Adı (TR)</th>';
	html += '<th>Ürün Adı (EN)</th>';
	html += '<th>Ürün Adı (RU)</th>';
	html += '<th>Durum</th>';
	html += '<th>İşlem</th>';
	html += '</tr></thead><tbody>';
	
	products.forEach(function(product) {
		let hasEn = product.baslik_en && product.baslik_en.trim() !== '';
		let hasRu = product.baslik_ru && product.baslik_ru.trim() !== '';
		let statusClass = '';
		let statusText = '';
		
		if (hasEn && hasRu) {
			statusClass = 'success';
			statusText = 'Tamam';
		} else if (hasEn || hasRu) {
			statusClass = 'warning';
			statusText = 'Kısmi';
		} else {
			statusClass = 'danger';
			statusText = 'Boş';
		}
		
		html += '<tr data-product-id="' + product.id + '">';
		html += '<td><input type="checkbox" class="product-checkbox" name="selected_products[]" value="' + product.id + '" onchange="updateSelectedList()"></td>';
		html += '<td>' + product.id + '</td>';
		html += '<td>' + (product.stok_kodu || '-') + '</td>';
		html += '<td><strong>' + (product.baslik || '-') + '</strong></td>';
		html += '<td><input type="text" class="form-control form-control-sm" name="baslik_en_' + product.id + '" value="' + (product.baslik_en || '') + '" placeholder="İngilizce ad"></td>';
		html += '<td><input type="text" class="form-control form-control-sm" name="baslik_ru_' + product.id + '" value="' + (product.baslik_ru || '') + '" placeholder="Rusça ad"></td>';
		html += '<td><span class="badge badge-' + statusClass + '">' + statusText + '</span></td>';
		html += '<td><button type="button" class="btn btn-sm btn-primary" onclick="quickUpdate(' + product.id + ')">Hızlı Güncelle</button></td>';
		html += '</tr>';
	});
	
	html += '</tbody></table></div>';
	html += '</form>';
	
	$('#productsContainer').html(html);
	updateSelectedList();
}

function quickUpdate(productId) {
	let baslikEn = $('input[name="baslik_en_' + productId + '"]').val();
	let baslikRu = $('input[name="baslik_ru_' + productId + '"]').val();
	
	if (!baslikEn && !baslikRu) {
		alert('Lütfen en az bir alan doldurun!');
		return;
	}
	
	$.ajax({
		url: '',
		type: 'POST',
		data: {
			ajax: 1,
			action: 'update_batch',
			product_ids: JSON.stringify([productId]),
			update_type: 'manual',
			'baslik_en_' + productId: baslikEn,
			'baslik_ru_' + productId: baslikRu
		},
		dataType: 'json',
		success: function(response) {
			if (response.success) {
				alert('Ürün başarıyla güncellendi!');
				loadProducts(); // Sayfayı yenile
			} else {
				alert('Güncelleme hatası: ' + (response.error || 'Bilinmeyen hata'));
			}
		},
		error: function() {
			alert('Güncelleme sırasında bir hata oluştu.');
		}
	});
}

function displayPagination(data) {
	let html = '<nav><ul class="pagination justify-content-center">';
	
	// Önceki sayfa
	if (data.page > 1) {
		html += '<li class="page-item"><a class="page-link" href="javascript:void(0)" onclick="goToPage(' + (data.page - 1) + ')">Önceki</a></li>';
	}
	
	// Sayfa numaraları
	for (let i = Math.max(1, data.page - 2); i <= Math.min(data.total_pages, data.page + 2); i++) {
		html += '<li class="page-item ' + (i === data.page ? 'active' : '') + '">';
		html += '<a class="page-link" href="javascript:void(0)" onclick="goToPage(' + i + ')">' + i + '</a>';
		html += '</li>';
	}
	
	// Sonraki sayfa
	if (data.page < data.total_pages) {
		html += '<li class="page-item"><a class="page-link" href="javascript:void(0)" onclick="goToPage(' + (data.page + 1) + ')">Sonraki</a></li>';
	}
	
	html += '</ul></nav>';
	html += '<p class="text-center text-muted">Toplam: ' + data.total + ' ürün | Sayfa: ' + data.page + ' / ' + data.total_pages + '</p>';
	
	$('#paginationContainer').html(html);
}

function goToPage(page) {
	currentPage = page;
	loadProducts();
}

function toggleSelectAll(checkbox) {
	$('.product-checkbox').prop('checked', checkbox.checked);
	updateSelectedList();
}

function selectAll() {
	$('.product-checkbox').prop('checked', true);
	$('#selectAllCheckbox').prop('checked', true);
	updateSelectedList();
}

function selectNone() {
	$('.product-checkbox').prop('checked', false);
	$('#selectAllCheckbox').prop('checked', false);
	updateSelectedList();
}

function updateSelectedList() {
	selectedProducts = [];
	$('.product-checkbox:checked').each(function() {
		selectedProducts.push($(this).val());
	});
	$('#selectedCount').text(selectedProducts.length);
}

function updateSelected() {
	if (selectedProducts.length === 0) {
		alert('Lütfen en az bir ürün seçin!');
		return;
	}
	
	// Form verilerini topla
	let formData = {};
	let hasData = false;
	selectedProducts.forEach(function(productId) {
		let baslikEn = $('input[name="baslik_en_' + productId + '"]').val() || '';
		let baslikRu = $('input[name="baslik_ru_' + productId + '"]').val() || '';
		
		if (baslikEn || baslikRu) {
			formData['baslik_en_' + productId] = baslikEn;
			formData['baslik_ru_' + productId] = baslikRu;
			hasData = true;
		}
	});
	
	if (!hasData) {
		alert('Lütfen seçili ürünler için en az bir çok dilli alan doldurun!');
		return;
	}
	
	// Onay iste
	if (!confirm('Seçili ' + selectedProducts.length + ' ürünü güncellemek istediğinize emin misiniz?')) {
		return;
	}
	
	// İlerleme göster
	$('#productsContainer').prepend('<div id="updateProgressBar" class="alert alert-info"><div class="progress mb-2"><div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%"></div></div><p class="text-center mb-0" id="progressText">Güncelleniyor...</p></div>');
	
	totalUpdated = 0;
	totalErrors = 0;
	
	// Seçilen ürünleri güncelle
	updateProductsBatch(selectedProducts, 0, formData);
}

function confirmBulkUpdate() {
	if (selectedProducts.length === 0) {
		alert('Lütfen en az bir ürün seçin!');
		return;
	}
	
	// Form verilerini topla
	let formData = {};
	let hasData = false;
	selectedProducts.forEach(function(productId) {
		let baslikEn = $('input[name="baslik_en_' + productId + '"]').val() || '';
		let baslikRu = $('input[name="baslik_ru_' + productId + '"]').val() || '';
		
		if (baslikEn || baslikRu) {
			formData['baslik_en_' + productId] = baslikEn;
			formData['baslik_ru_' + productId] = baslikRu;
			hasData = true;
		}
	});
	
	if (!hasData) {
		alert('Lütfen en az bir ürün için çok dilli alan doldurun!');
		return;
	}
	
	$('#updateForm').hide();
	$('#updateProgress').show();
	$('#updateResults').hide();
	totalUpdated = 0;
	totalErrors = 0;
	
	// Seçilen ürünleri güncelle
	updateProductsBatch(selectedProducts, 0, formData);
}

let totalUpdated = 0;
let totalErrors = 0;

function updateProductsBatch(productIds, index, formData) {
	if (index >= productIds.length) {
		// Tüm güncellemeler tamamlandı
		$('#updateProgressBar').html('<div class="alert alert-success"><h5>Güncelleme Tamamlandı!</h5><p>Güncellenen: ' + totalUpdated + ' | Hatalar: ' + totalErrors + '</p><button class="btn btn-sm btn-primary" onclick="$(\'#updateProgressBar\').remove(); loadProducts();">Sayfayı Yenile</button></div>');
		totalUpdated = 0;
		totalErrors = 0;
		return;
	}
	
	// Her seferinde 10 ürün güncelle (performans için)
	let batch = productIds.slice(index, index + 10);
	
	// Bu batch için form verilerini hazırla
	let batchData = {
		ajax: 1,
		action: 'update_batch',
		product_ids: JSON.stringify(batch),
		update_type: 'manual'
	};
	
	// Her ürün için form verilerini ekle
	batch.forEach(function(productId) {
		if (formData['baslik_en_' + productId] !== undefined) {
			batchData['baslik_en_' + productId] = formData['baslik_en_' + productId];
		}
		if (formData['baslik_ru_' + productId] !== undefined) {
			batchData['baslik_ru_' + productId] = formData['baslik_ru_' + productId];
		}
		if (formData['kisa_aciklama_en_' + productId] !== undefined) {
			batchData['kisa_aciklama_en_' + productId] = formData['kisa_aciklama_en_' + productId];
		}
		if (formData['kisa_aciklama_ru_' + productId] !== undefined) {
			batchData['kisa_aciklama_ru_' + productId] = formData['kisa_aciklama_ru_' + productId];
		}
		if (formData['aciklama_en_' + productId] !== undefined) {
			batchData['aciklama_en_' + productId] = formData['aciklama_en_' + productId];
		}
		if (formData['aciklama_ru_' + productId] !== undefined) {
			batchData['aciklama_ru_' + productId] = formData['aciklama_ru_' + productId];
		}
	});
	
	$.ajax({
		url: '',
		type: 'POST',
		data: batchData,
		dataType: 'json',
		success: function(response) {
			if (response.success) {
				totalUpdated += response.updated;
				totalErrors += response.errors;
				
				// İlerleme güncelle
				let progress = Math.round(((index + batch.length) / productIds.length) * 100);
				$('#updateProgressBar .progress-bar').css('width', progress + '%');
				$('#progressText').text('Güncelleniyor... ' + (index + batch.length) + ' / ' + productIds.length + ' (Başarılı: ' + totalUpdated + ', Hata: ' + totalErrors + ')');
				
				// Sonraki batch'i işle
				setTimeout(function() {
					updateProductsBatch(productIds, index + 10, formData);
				}, 100);
			} else {
				alert('Güncelleme hatası: ' + (response.error || 'Bilinmeyen hata'));
			}
		},
		error: function() {
			alert('Güncelleme sırasında bir hata oluştu.');
		}
	});
}
</script>

