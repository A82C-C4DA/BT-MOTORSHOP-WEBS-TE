<?php
// AI Handler - OpenAI API ile iletişim
header('Content-Type: application/json');

// Dosya yolu kontrolü
if (file_exists(__DIR__ . '/../fonksiyon.php')) {
    include __DIR__ . '/../fonksiyon.php';
} else {
    include 'fonksiyon.php';
}

if(!isset($_SESSION['admin']['login'])){
    echo json_encode(['success' => false, 'error' => 'Yetkisiz erişim']);
    exit;
}

// AI ayarlarını al
$ai_settings = $db->query("SELECT * FROM ai_ayar LIMIT 1")->fetch(PDO::FETCH_ASSOC);

if (!$ai_settings || empty($ai_settings['api_key'])) {
    echo json_encode([
        'success' => false, 
        'error' => 'AI API anahtarı yapılandırılmamış. Lütfen AI Ayarları sayfasından API anahtarınızı girin.'
    ]);
    exit;
}

$action = $_POST['action'] ?? 'chat';
$message = $_POST['message'] ?? '';

if (empty($message)) {
    echo json_encode(['success' => false, 'error' => 'Mesaj boş olamaz']);
    exit;
}

// Kategori oluşturma komutunu kontrol et
if (stripos($message, 'kategori oluştur') !== false || 
    stripos($message, 'kategori ekle') !== false ||
    stripos($message, 'yeni kategori') !== false ||
    stripos($message, 'create category') !== false) {
    
    $categoryResult = createCategoryFromAI($message, $db);
    if ($categoryResult['success']) {
        echo json_encode([
            'success' => true,
            'message' => $categoryResult['message']
        ]);
        exit;
    }
}

// Kategori sıralama komutunu kontrol et
if (stripos($message, 'kategori sırala') !== false || 
    stripos($message, 'kategorileri sırala') !== false ||
    stripos($message, 'kategori düzenle') !== false ||
    stripos($message, 'sort categories') !== false ||
    stripos($message, 'kategorileri düzenle') !== false) {
    
    $sortResult = sortCategoriesFromAI($message, $db);
    if ($sortResult['success']) {
        echo json_encode([
            'success' => true,
            'message' => $sortResult['message']
        ]);
        exit;
    }
}

// Veritabanından ilgili verileri al (context için)
$context = getAdminContext();

// AI prompt'unu hazırla
$systemPrompt = "Sen bir e-ticaret admin paneli asistanısın. Türkçe yanıt ver. Aşağıdaki bilgileri kullanarak yardımcı ol:\n\n";
$systemPrompt .= "Sistem Bilgileri:\n";
$systemPrompt .= "- Bugünkü satış: " . $context['bugun_satis'] . " TL\n";
$systemPrompt .= "- Bu hafta satış: " . $context['bu_hafta_satis'] . " TL\n";
$systemPrompt .= "- Bu ay satış: " . $context['bu_ay_satis'] . " TL\n";
$systemPrompt .= "- Toplam ürün sayısı: " . $context['toplam_urun'] . "\n";
$systemPrompt .= "- Onay bekleyen sipariş: " . $context['onay_bekleyen'] . "\n";
$systemPrompt .= "- Toplam müşteri: " . $context['toplam_musteri'] . "\n\n";
$systemPrompt .= "Kullanıcı sorularını yanıtla, ürün açıklamaları oluştur, satış analizleri yap ve genel e-ticaret konularında yardımcı ol.";

// OpenAI API çağrısı
$apiKey = $ai_settings['api_key'];
$model = $ai_settings['model'] ?? 'gpt-3.5-turbo';

$response = callOpenAI($apiKey, $model, $systemPrompt, $message);

if ($response['success']) {
    echo json_encode([
        'success' => true,
        'message' => $response['message']
    ]);
} else {
    echo json_encode([
        'success' => false,
        'error' => $response['error']
    ]);
}

function getAdminContext() {
    global $db;
    
    $bugun_baslangic = strtotime(date('Y-m-d'));
    $bugun_bitis = strtotime(date('Y-m-d').' 23:59:00');
    $bugun_satis = $db->query("SELECT sum(toplam_tutar) as tutar FROM siparis WHERE durum != 6 AND siparis_tarihi > '{$bugun_baslangic}' AND siparis_tarihi < '{$bugun_bitis}'")->fetch(PDO::FETCH_ASSOC);
    
    $strtotime = date("o-\WW");
    $start = strtotime($strtotime);
    $end = strtotime("+6 days 23:59:59", $start);
    $bu_hafta_satis = $db->query("SELECT sum(toplam_tutar) as tutar FROM siparis WHERE durum != 6 AND siparis_tarihi > '{$start}' AND siparis_tarihi < '{$end}'")->fetch(PDO::FETCH_ASSOC);
    
    $month_ini = date("Y-m-d", mktime(0, 0, 0, date("m"), 1, date("Y")));
    $start = strtotime($month_ini);
    $month_end = date("Y-m-d", mktime(0, 0, 0, date("m"), date("t"), date("Y")));
    $end = strtotime($month_end);
    $bu_ay_satis = $db->query("SELECT sum(toplam_tutar) as tutar FROM siparis WHERE durum != 6 AND siparis_tarihi BETWEEN '{$start}' AND '{$end}'")->fetch(PDO::FETCH_ASSOC);
    
    $toplam_urun = $db->query("SELECT count(id) as toplam FROM urun")->fetch(PDO::FETCH_ASSOC);
    $onay_bekleyen = $db->query("SELECT count(id) as toplam FROM siparis WHERE durum = 0")->fetch(PDO::FETCH_ASSOC);
    $toplam_musteri = $db->query("SELECT count(id) as toplam FROM kullanici")->fetch(PDO::FETCH_ASSOC);
    
    return [
        'bugun_satis' => number_format($bugun_satis['tutar'] ?? 0, 2, ',', '.'),
        'bu_hafta_satis' => number_format($bu_hafta_satis['tutar'] ?? 0, 2, ',', '.'),
        'bu_ay_satis' => number_format($bu_ay_satis['tutar'] ?? 0, 2, ',', '.'),
        'toplam_urun' => $toplam_urun['toplam'] ?? 0,
        'onay_bekleyen' => $onay_bekleyen['toplam'] ?? 0,
        'toplam_musteri' => $toplam_musteri['toplam'] ?? 0
    ];
}

function createCategoryFromAI($message, $db) {
    // Mevcut kategorileri al (context için)
    $kategoriler = $db->query("SELECT id, baslik, ust_kategori FROM kategori ORDER BY baslik ASC")->fetchAll(PDO::FETCH_ASSOC);
    $kategoriListesi = '';
    foreach($kategoriler as $kat) {
        $kategoriListesi .= "- ID: {$kat['id']}, Ad: {$kat['baslik']}, Üst Kategori: " . ($kat['ust_kategori'] == 0 ? 'Ana Kategori' : $kat['ust_kategori']) . "\n";
    }
    
    // AI'dan kategori bilgilerini al
    $apiKey = $db->query("SELECT api_key, model FROM ai_ayar LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    if (!$apiKey || empty($apiKey['api_key'])) {
        return ['success' => false, 'error' => 'AI API anahtarı bulunamadı'];
    }
    
    $systemPrompt = "Sen bir e-ticaret kategori oluşturma asistanısın. Kullanıcıdan kategori oluşturma isteği geldiğinde, aşağıdaki JSON formatında yanıt ver:\n\n";
    $systemPrompt .= "{\n";
    $systemPrompt .= '  "baslik": "Kategori Adı",\n';
    $systemPrompt .= '  "kisa_aciklama": "Kısa açıklama (SEO için)",\n';
    $systemPrompt .= '  "aciklama": "Detaylı açıklama",\n';
    $systemPrompt .= '  "ust_kategori_id": 0,\n';
    $systemPrompt .= '  "ust_menu": 1,\n';
    $systemPrompt .= '  "alt_menu": 1\n';
    $systemPrompt .= "}\n\n";
    $systemPrompt .= "Mevcut kategoriler:\n" . $kategoriListesi . "\n\n";
    $systemPrompt .= "Sadece JSON formatında yanıt ver, başka açıklama yapma. Eğer üst kategori belirtilmişse, ust_kategori_id'yi uygun kategori ID'si ile değiştir.";
    
    $userPrompt = $message . "\n\nYukarıdaki isteğe göre kategori bilgilerini JSON formatında ver.";
    
    $response = callOpenAI($apiKey['api_key'], $apiKey['model'] ?? 'gpt-3.5-turbo', $systemPrompt, $userPrompt);
    
    if (!$response['success']) {
        return $response;
    }
    
    // JSON'u parse et
    $jsonMatch = [];
    if (preg_match('/\{[^}]+\}/s', $response['message'], $jsonMatch)) {
        $categoryData = json_decode($jsonMatch[0], true);
        
        if ($categoryData && isset($categoryData['baslik'])) {
            // Kategori oluştur
            $baslik = trim($categoryData['baslik']);
            $kisa_aciklama = $categoryData['kisa_aciklama'] ?? '';
            $aciklama = $categoryData['aciklama'] ?? '';
            $ust_kategori = intval($categoryData['ust_kategori_id'] ?? 0);
            $ust_menu = isset($categoryData['ust_menu']) ? ($categoryData['ust_menu'] ? 1 : 0) : 0;
            $alt_menu = isset($categoryData['alt_menu']) ? ($categoryData['alt_menu'] ? 1 : 0) : 0;
            
            // Aynı isimde kategori var mı kontrol et
            $check = $db->prepare("SELECT id FROM kategori WHERE baslik = ? LIMIT 1");
            $check->execute([$baslik]);
            if ($check->fetch()) {
                return ['success' => false, 'error' => "'{$baslik}' adında bir kategori zaten mevcut."];
            }
            
            // Üst kategori kontrolü
            if ($ust_kategori > 0) {
                $ustCheck = $db->prepare("SELECT id FROM kategori WHERE id = ? LIMIT 1");
                $ustCheck->execute([$ust_kategori]);
                if (!$ustCheck->fetch()) {
                    $ust_kategori = 0; // Geçersiz üst kategori, ana kategori yap
                }
            }
            
            // Kategoriyi ekle
            $insert = $db->prepare("INSERT INTO kategori SET baslik = ?, sef = ?, ust_kategori = ?, ust_menu = ?, alt_menu = ?, aciklama = ?, kisa_aciklama = ?, sira = ?");
            $result = $insert->execute([$baslik, '', $ust_kategori, $ust_menu, $alt_menu, $aciklama, $kisa_aciklama, 9999]);
            
            if ($result) {
                $id = $db->lastInsertId();
                // SEF oluştur
                $sef = sef($baslik) . '-' . $id;
                $update = $db->prepare("UPDATE kategori SET sef = ? WHERE id = ?");
                $update->execute([$sef, $id]);
                
                // Sitemap güncelle
                if (file_exists(__DIR__ . '/../../sitemap-olustur.php')) {
                    // Sitemap'i arka planda güncelle (non-blocking)
                    @file_get_contents('http' . (isset($_SERVER['HTTPS']) ? 's' : '') . '://' . $_SERVER['HTTP_HOST'] . dirname(dirname($_SERVER['PHP_SELF'])) . '/sitemap-olustur.php');
                }
                
                $ustKategoriText = $ust_kategori > 0 ? " (Üst Kategori ID: {$ust_kategori})" : " (Ana Kategori)";
                return [
                    'success' => true,
                    'message' => "✅ Kategori başarıyla oluşturuldu!\n\n📁 Kategori Adı: {$baslik}\n📝 Kısa Açıklama: {$kisa_aciklama}\n🔗 Kategori ID: {$id}{$ustKategoriText}\n\nKategoriyi düzenlemek için: Yapılandırma > Kategori sayfasına gidin."
                ];
            } else {
                return ['success' => false, 'error' => 'Kategori oluşturulurken veritabanı hatası oluştu.'];
            }
        }
    }
    
    return ['success' => false, 'error' => 'AI yanıtından kategori bilgileri çıkarılamadı. Lütfen daha açık bir istek yapın.'];
}

function sortCategoriesFromAI($message, $db) {
    // Mevcut kategorileri al
    $kategoriler = $db->query("SELECT id, baslik, ust_kategori, sira FROM kategori ORDER BY sira ASC, baslik ASC")->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($kategoriler)) {
        return ['success' => false, 'error' => 'Sıralanacak kategori bulunamadı.'];
    }
    
    // Kategorileri hiyerarşik yapıya çevir
    $kategoriMap = [];
    
    foreach($kategoriler as $kat) {
        $kategoriMap[$kat['id']] = $kat;
    }
    
    // AI'dan sıralama önerisi al
    $apiKey = $db->query("SELECT api_key, model FROM ai_ayar LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    if (!$apiKey || empty($apiKey['api_key'])) {
        return ['success' => false, 'error' => 'AI API anahtarı bulunamadı'];
    }
    
    $kategoriListesi = '';
    foreach($kategoriler as $kat) {
        $ustKat = $kat['ust_kategori'] == 0 ? 'Ana Kategori' : ($kategoriMap[$kat['ust_kategori']]['baslik'] ?? 'Bilinmiyor');
        $kategoriListesi .= "- ID: {$kat['id']}, Ad: {$kat['baslik']}, Üst: {$ustKat}, Mevcut Sıra: {$kat['sira']}\n";
    }
    
    $systemPrompt = "Sen bir e-ticaret kategori sıralama asistanısın. Kullanıcıdan kategori sıralama isteği geldiğinde, kategorileri mantıklı bir şekilde sırala.\n\n";
    $systemPrompt .= "Mevcut kategoriler:\n" . $kategoriListesi . "\n\n";
    $systemPrompt .= "Kategorileri mantıklı bir sıraya göre düzenle. Örneğin:\n";
    $systemPrompt .= "- Benzer kategorileri grupla\n";
    $systemPrompt .= "- Alfabetik sıralama yapabilirsin\n";
    $systemPrompt .= "- Önemli kategorileri üste al\n";
    $systemPrompt .= "- Hiyerarşik yapıyı koru (alt kategoriler üst kategorilerin altında kalmalı)\n\n";
    $systemPrompt .= "Yanıt olarak sadece kategori ID'lerini yeni sıralarına göre liste halinde ver. Format:\n";
    $systemPrompt .= "ID1, ID2, ID3, ...\n\n";
    $systemPrompt .= "Sadece ID numaralarını virgülle ayırarak ver, başka açıklama yapma.";
    
    $userPrompt = $message . "\n\nYukarıdaki kategorileri mantıklı bir sıraya göre düzenle ve sadece ID numaralarını virgülle ayırarak listele.";
    
    $response = callOpenAI($apiKey['api_key'], $apiKey['model'] ?? 'gpt-3.5-turbo', $systemPrompt, $userPrompt);
    
    if (!$response['success']) {
        return $response;
    }
    
    // ID'leri çıkar
    preg_match_all('/\b(\d+)\b/', $response['message'], $matches);
    $orderedIds = array_map('intval', $matches[1]);
    
    if (empty($orderedIds)) {
        return ['success' => false, 'error' => 'AI yanıtından kategori ID\'leri çıkarılamadı.'];
    }
    
    // Geçerli ID'leri filtrele
    $validIds = [];
    $existingIds = array_column($kategoriler, 'id');
    foreach($orderedIds as $id) {
        if (in_array($id, $existingIds) && !in_array($id, $validIds)) {
            $validIds[] = $id;
        }
    }
    
    // Eksik kategorileri ekle (sıralamada olmayanlar)
    foreach($existingIds as $id) {
        if (!in_array($id, $validIds)) {
            $validIds[] = $id;
        }
    }
    
    // Sıralamayı uygula
    $sira = 1;
    $updated = 0;
    $errors = [];
    
    foreach($validIds as $id) {
        try {
            $update = $db->prepare("UPDATE kategori SET sira = ? WHERE id = ?");
            $result = $update->execute([$sira, $id]);
            if ($result) {
                $updated++;
            }
            $sira++;
        } catch(Exception $e) {
            $errors[] = "Kategori ID {$id} güncellenirken hata: " . $e->getMessage();
        }
    }
    
    if ($updated > 0) {
        // Sitemap güncelle
        if (file_exists(__DIR__ . '/../../sitemap-olustur.php')) {
            @file_get_contents('http' . (isset($_SERVER['HTTPS']) ? 's' : '') . '://' . $_SERVER['HTTP_HOST'] . dirname(dirname($_SERVER['PHP_SELF'])) . '/sitemap-olustur.php');
        }
        
        $message = "✅ Kategoriler başarıyla sıralandı!\n\n";
        $message .= "📊 Toplam {$updated} kategori güncellendi.\n";
        $message .= "🔄 Yeni sıralama uygulandı.\n\n";
        $message .= "Kategorileri görmek için: Yapılandırma > Kategori sayfasına gidin.";
        
        if (!empty($errors)) {
            $message .= "\n\n⚠️ Bazı hatalar oluştu:\n" . implode("\n", $errors);
        }
        
        return ['success' => true, 'message' => $message];
    } else {
        return ['success' => false, 'error' => 'Kategoriler güncellenemedi.'];
    }
}

function callOpenAI($apiKey, $model, $systemPrompt, $userMessage) {
    $url = 'https://api.openai.com/v1/chat/completions';
    
    $data = [
        'model' => $model,
        'messages' => [
            [
                'role' => 'system',
                'content' => $systemPrompt
            ],
            [
                'role' => 'user',
                'content' => $userMessage
            ]
        ],
        'temperature' => 0.7,
        'max_tokens' => 1000
    ];
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $apiKey
    ]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        return ['success' => false, 'error' => 'Bağlantı hatası: ' . $error];
    }
    
    if ($httpCode !== 200) {
        $errorData = json_decode($response, true);
        $errorMsg = $errorData['error']['message'] ?? 'API hatası oluştu';
        return ['success' => false, 'error' => $errorMsg];
    }
    
    $result = json_decode($response, true);
    
    if (isset($result['choices'][0]['message']['content'])) {
        return [
            'success' => true,
            'message' => trim($result['choices'][0]['message']['content'])
        ];
    }
    
    return ['success' => false, 'error' => 'Yanıt alınamadı'];
}
?>

