<?php
// AI Ayarları Sayfası

if(isset($_POST['ai_ayar_kaydet'])){
    $api_key = $_POST['api_key'] ?? '';
    $model = $_POST['model'] ?? 'gpt-3.5-turbo';
    
    // Mevcut ayarı kontrol et
    $mevcut = $db->query("SELECT * FROM ai_ayar LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    
    if($mevcut){
        $update = $db->prepare("UPDATE ai_ayar SET api_key = ?, model = ? WHERE id = ?");
        $update->execute([$api_key, $model, $mevcut['id']]);
    } else {
        $insert = $db->prepare("INSERT INTO ai_ayar (api_key, model) VALUES (?, ?)");
        $insert->execute([$api_key, $model]);
    }
    
    echo '<script>$(function(){ not7(); });</script>';
}

$ai_ayar = $db->query("SELECT * FROM ai_ayar LIMIT 1")->fetch(PDO::FETCH_ASSOC);
if(!$ai_ayar){
    $ai_ayar = ['api_key' => '', 'model' => 'gpt-3.5-turbo'];
}
?>

<!-- breadcrumb -->
<div class="breadcrumb-header justify-content-between">
	<div class="left-content">
		<div>
		  <h2 class="main-content-title tx-24 mg-b-1 mg-b-lg-1">AI Asistan Ayarları</h2>
		  <p class="mg-b-0">OpenAI API ayarlarını yapılandırın</p>
		</div>
	</div>
</div>
<!-- /breadcrumb -->

<div class="row">
	<div class="col-lg-12">
		<div class="card">
			<div class="card-header">
				<h3 class="card-title">AI API Yapılandırması</h3>
			</div>
			<div class="card-body">
				<form method="POST">
					<div class="form-group">
						<label>OpenAI API Key</label>
						<input type="text" class="form-control" name="api_key" value="<?php echo htmlspecialchars($ai_ayar['api_key']); ?>" placeholder="sk-..." required>
						<small class="form-text text-muted">
							OpenAI API anahtarınızı <a href="https://platform.openai.com/api-keys" target="_blank">buradan</a> alabilirsiniz.
						</small>
					</div>
					
					<div class="form-group">
						<label>Model</label>
						<select class="form-control" name="model">
							<option value="gpt-3.5-turbo" <?php echo $ai_ayar['model'] == 'gpt-3.5-turbo' ? 'selected' : ''; ?>>GPT-3.5 Turbo (Önerilen)</option>
							<option value="gpt-4" <?php echo $ai_ayar['model'] == 'gpt-4' ? 'selected' : ''; ?>>GPT-4 (Daha güçlü, daha pahalı)</option>
							<option value="gpt-4-turbo-preview" <?php echo $ai_ayar['model'] == 'gpt-4-turbo-preview' ? 'selected' : ''; ?>>GPT-4 Turbo Preview</option>
						</select>
						<small class="form-text text-muted">
							GPT-3.5 Turbo çoğu kullanım için yeterlidir ve daha ekonomiktir.
						</small>
					</div>
					
					<div class="alert alert-info">
						<strong>Bilgi:</strong> API anahtarınız güvenli bir şekilde saklanır ve sadece AI asistanı ile iletişim kurmak için kullanılır.
					</div>
					
					<button type="submit" name="ai_ayar_kaydet" class="btn btn-primary">
						<i class="fe fe-save"></i> Ayarları Kaydet
					</button>
				</form>
			</div>
		</div>
		
		<div class="card mt-3">
			<div class="card-header">
				<h3 class="card-title">AI Asistan Özellikleri</h3>
			</div>
			<div class="card-body">
				<div class="row">
					<div class="col-md-6">
						<h5>Yapabilecekleriniz:</h5>
						<ul>
							<li>✅ Ürün açıklamaları oluşturma</li>
							<li>✅ <strong>Ürün SEO isim önerisi</strong> (Ürün ekle/düzenle + Ürünler listesi)</li>
							<li>✅ Satış verilerini analiz etme</li>
							<li>✅ Blog yazısı başlıkları önerme</li>
							<li>✅ Müşteri yorumlarını analiz etme</li>
							<li>✅ İçerik üretme</li>
							<li>✅ <strong>Kategori oluşturma</strong> 🆕</li>
							<li>✅ <strong>Kategori sıralama</strong> 🆕</li>
							<li>✅ Genel sorularınızı yanıtlama</li>
						</ul>
					</div>
					<div class="col-md-6">
						<h5>Örnek Komutlar:</h5>
						<ul>
							<li>"Bugünkü satışları analiz et"</li>
							<li>"Bir ürün açıklaması oluştur"</li>
							<li>"Blog yazısı başlıkları öner"</li>
							<li>"Müşteri yorumlarını analiz et"</li>
							<li>"En çok satan ürünler hakkında bilgi ver"</li>
							<li><strong>"Otomobil yedek parçaları için kategori oluştur"</strong> 🆕</li>
							<li><strong>"Motor yağları kategorisi ekle, üst menüde görünsün"</strong> 🆕</li>
							<li><strong>"Kategorileri alfabetik sıraya göre düzenle"</strong> 🆕</li>
							<li><strong>"Kategorileri mantıklı bir sıraya göre sırala"</strong> 🆕</li>
						</ul>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

