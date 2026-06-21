<?php
/**
 * TecDoc API Entegrasyon Dosyası
 * OEM kodları ve görselleri TecDoc'dan çeker
 */

class TecDocAPI {
    private $apiUrl;
    private $rapidApiKey;
    private $rapidApiHost;
    private $languageId;
    
    /**
     * TecDoc API sınıfını başlatır (RapidAPI üzerinden)
     * 
     * @param array $config API yapılandırması
     *   - rapidApiKey: RapidAPI anahtarı (zorunlu)
     *   - rapidApiHost: RapidAPI host (varsayılan: 'tecdoc-catalog.p.rapidapi.com')
     *   - languageId: Dil ID (varsayılan: 2 = Türkçe, 1 = İngilizce)
     */
    public function __construct($config = []) {
        $this->rapidApiKey = $config['rapidApiKey'] ?? $config['apiKey'] ?? '';
        $this->rapidApiHost = $config['rapidApiHost'] ?? 'tecdoc-catalog.p.rapidapi.com';
        $this->apiUrl = 'https://' . $this->rapidApiHost;
        $this->languageId = $config['languageId'] ?? $config['langId'] ?? 2; // 2 = Türkçe
    }
    
    /**
     * Ürün için OEM kodlarını ve görselleri çeker
     * 
     * @param string $articleNumber Ürün numarası/stok kodu
     * @param string $manufacturerName Üretici adı (opsiyonel)
     * @param int $articleId Direkt article ID (varsa arama yapmaz)
     * @return array OEM kodları ve görseller
     */
    public function getProductOEMCodesAndImages($articleNumber, $manufacturerName = '', $articleId = null) {
        $result = [
            'success' => false,
            'oem_codes' => [],
            'images' => [],
            'article_id' => null,
            'error' => ''
        ];
        
        try {
            // Eğer articleId verilmişse direkt kullan
            if ($articleId) {
                $result['article_id'] = $articleId;
            } else {
                // ÖNEMLİ: articleNumber (stok kodu) ile arama yap
                // RapidAPI TecDoc'da article number ile arama yaparak article ID'yi bul
                
                // Önce ürünü bulmayı dene (stok kodu ile)
                $articleData = $this->searchArticle($articleNumber, $manufacturerName);
                
                if ($articleData && (isset($articleData['articleId']) || isset($articleData['id']))) {
                    $articleId = $articleData['articleId'] ?? $articleData['id'] ?? null;
                    $result['article_id'] = $articleId;
                } else {
                    // Arama başarısız - stok kodu ile article bulunamadı
                    $result['error'] = 'Stok kodu ile article bulunamadı: ' . $articleNumber;
                    return $result;
                }
            }
            
            if (!$articleId) {
                $result['error'] = 'Article ID bulunamadı';
                return $result;
            }
            
            // OEM kodlarını çek
            try {
                $oemCodes = $this->getOEMCodes($articleId);
                $result['oem_codes'] = $oemCodes;
            } catch (Exception $e) {
                $result['error'] .= ' OEM kodları çekilemedi: ' . $e->getMessage();
            }
            
            // Görselleri çek
            try {
                $images = $this->getArticleImages($articleId);
                $result['images'] = $images;
            } catch (Exception $e) {
                $result['error'] .= ' Görseller çekilemedi: ' . $e->getMessage();
            }
            
            // Eğer en az bir veri çekildiyse başarılı say
            if (!empty($result['oem_codes']) || !empty($result['images'])) {
                $result['success'] = true;
                $result['error'] = ''; // Hata mesajını temizle
            } else {
                $result['error'] = 'OEM kodu ve görsel bulunamadı';
            }
            
        } catch (Exception $e) {
            $result['error'] = $e->getMessage();
        }
        
        return $result;
    }
    
    /**
     * Ürün arama (article number ile)
     * RapidAPI TecDoc'da article number ile arama yapmak için farklı endpoint'ler denenir
     * 
     * ÖNEMLİ: Stok kodunu (article number) kullanarak article ID'yi bulmaya çalışır
     */
    private function searchArticle($articleNumber, $manufacturerName = '') {
        // RapidAPI TecDoc'da arama endpoint'lerini dene
        // RapidAPI TecDoc dokümantasyonuna göre olası endpoint'ler:
        $searchEndpoints = [
            // Article number ile arama
            ['endpoint' => '/articles/search-by-number', 'params' => ['articleNumber' => $articleNumber]],
            ['endpoint' => '/articles/find-by-number', 'params' => ['articleNumber' => $articleNumber, 'numberType' => 'articleNumber']],
            ['endpoint' => '/articles/get-by-article-number', 'params' => ['articleNumber' => $articleNumber]],
            ['endpoint' => '/articles/search', 'params' => ['query' => $articleNumber, 'searchType' => 'articleNumber']],
            // Alternatif parametre isimleri
            ['endpoint' => '/articles/search', 'params' => ['number' => $articleNumber]],
            ['endpoint' => '/articles/search', 'params' => ['articleNo' => $articleNumber]],
        ];
        
        // Manufacturer name varsa ekle
        if (!empty($manufacturerName)) {
            foreach ($searchEndpoints as &$endpoint) {
                $endpoint['params']['manufacturerName'] = $manufacturerName;
            }
        }
        
        foreach ($searchEndpoints as $endpointConfig) {
            try {
                $url = $this->apiUrl . $endpointConfig['endpoint'];
                $params = $endpointConfig['params'];
                
                $response = $this->makeApiCall($url, 'GET', $params);
                
                if ($response) {
                    // Response formatına göre parse et
                    if (isset($response['data']) && is_array($response['data'])) {
                        if (!empty($response['data'])) {
                            // İlk sonucu döndür
                            $firstResult = $response['data'][0];
                            if (isset($firstResult['articleId']) || isset($firstResult['id'])) {
                                return $firstResult;
                            }
                        }
                    } elseif (isset($response['articleId']) || isset($response['id'])) {
                        return $response;
                    } elseif (isset($response['article']) && (isset($response['article']['articleId']) || isset($response['article']['id']))) {
                        return $response['article'];
                    }
                }
            } catch (Exception $e) {
                // Bu endpoint çalışmıyor, diğerini dene
                continue;
            }
        }
        
        // Hiçbir endpoint çalışmazsa null döndür (articleNumber'ı direkt kullanma)
        // Çünkü stok kodu genellikle article ID değildir
        return null;
    }
    
    /**
     * OEM kodlarını çeker
     * RapidAPI endpoint: /articles/get-article-oem-numbers/article-id/{articleId}/lang-id/{langId}
     */
    private function getOEMCodes($articleId) {
        $url = $this->apiUrl . '/articles/get-article-oem-numbers/article-id/' . urlencode($articleId) . '/lang-id/' . $this->languageId;
        
        try {
            $response = $this->makeApiCall($url, 'GET');
            
            if ($response) {
                $oemCodes = [];
                
                // Response formatına göre parse et
                if (isset($response['data']) && is_array($response['data'])) {
                    foreach ($response['data'] as $item) {
                        $oemCode = $item['oemNumber'] ?? $item['oem'] ?? $item['oemNumber'] ?? '';
                        $manufacturer = $item['manufacturerName'] ?? $item['brand'] ?? $item['maker'] ?? '';
                        
                        if (!empty($oemCode)) {
                            $oemCodes[] = [
                                'code' => $oemCode,
                                'manufacturer' => $manufacturer
                            ];
                        }
                    }
                } elseif (is_array($response)) {
                    // Direkt array döndüyse
                    foreach ($response as $item) {
                        if (is_array($item)) {
                            $oemCode = $item['oemNumber'] ?? $item['oem'] ?? $item['oemNumber'] ?? '';
                            $manufacturer = $item['manufacturerName'] ?? $item['brand'] ?? $item['maker'] ?? '';
                            
                            if (!empty($oemCode)) {
                                $oemCodes[] = [
                                    'code' => $oemCode,
                                    'manufacturer' => $manufacturer
                                ];
                            }
                        }
                    }
                }
                
                return $oemCodes;
            }
        } catch (Exception $e) {
            // Hata durumunda boş array döndür
            return [];
        }
        
        return [];
    }
    
    /**
     * Ürün görsellerini çeker
     * RapidAPI endpoint: /articles/get-article-images/article-id/{articleId}/lang-id/{langId}
     * 
     * @param int|string $articleId TecDoc article ID
     * @return array Görsel URL'leri
     */
    private function getArticleImages($articleId) {
        // RapidAPI TecDoc endpoint'i
        // Format: /articles/get-article-images/article-id/{articleId}/lang-id/{langId}
        $url = $this->apiUrl . '/articles/get-article-images/article-id/' . urlencode($articleId) . '/lang-id/' . $this->languageId;
        
        try {
            $response = $this->makeApiCall($url, 'GET');
            
            if ($response) {
                $images = [];
                
                // Response formatına göre parse et
                // RapidAPI genellikle şu formatlardan birini döndürür:
                // 1. { "data": [{ "imageUrl": "...", ... }] }
                // 2. { "images": [{ "url": "...", ... }] }
                // 3. [{ "url": "...", ... }]
                
                if (isset($response['data']) && is_array($response['data'])) {
                    // Format 1: data array içinde
                    foreach ($response['data'] as $item) {
                        $imageUrl = $this->extractImageUrl($item);
                        if (!empty($imageUrl)) {
                            $images[] = $imageUrl;
                        }
                    }
                } elseif (isset($response['images']) && is_array($response['images'])) {
                    // Format 2: images array içinde
                    foreach ($response['images'] as $item) {
                        $imageUrl = $this->extractImageUrl($item);
                        if (!empty($imageUrl)) {
                            $images[] = $imageUrl;
                        }
                    }
                } elseif (is_array($response) && !isset($response['data']) && !isset($response['images'])) {
                    // Format 3: Direkt array
                    foreach ($response as $item) {
                        if (is_array($item)) {
                            $imageUrl = $this->extractImageUrl($item);
                            if (!empty($imageUrl)) {
                                $images[] = $imageUrl;
                            }
                        } elseif (is_string($item) && filter_var($item, FILTER_VALIDATE_URL)) {
                            // Direkt URL string ise
                            $images[] = $item;
                        }
                    }
                }
                
                return $images;
            }
        } catch (Exception $e) {
            // Hata durumunda boş array döndür
            // Log için hata mesajını sakla (production'da log dosyasına yazılabilir)
            error_log('TecDoc getArticleImages hatası: ' . $e->getMessage() . ' - ArticleId: ' . $articleId);
            return [];
        }
        
        return [];
    }
    
    /**
     * Response item'ından görsel URL'ini çıkarır
     */
    private function extractImageUrl($item) {
        if (!is_array($item)) {
            return '';
        }
        
        // Farklı alan isimlerini dene
        $possibleFields = [
            'imageUrl', 'image_url', 'imageURL', 'ImageUrl',
            'url', 'URL', 'Url',
            'image', 'Image', 'IMAGE',
            'link', 'Link', 'LINK',
            'src', 'Src', 'SRC',
            'picture', 'Picture', 'PICTURE',
            'photo', 'Photo', 'PHOTO',
            'thumbnail', 'Thumbnail', 'THUMBNAIL',
            'fullImage', 'full_image', 'fullImageUrl'
        ];
        
        foreach ($possibleFields as $field) {
            if (isset($item[$field]) && !empty($item[$field])) {
                $url = trim($item[$field]);
                // URL doğrulaması
                if (filter_var($url, FILTER_VALIDATE_URL) || 
                    preg_match('/^https?:\/\//', $url) ||
                    preg_match('/^\/\//', $url)) {
                    // Eğer // ile başlıyorsa https: ekle
                    if (preg_match('/^\/\//', $url)) {
                        $url = 'https:' . $url;
                    }
                    return $url;
                }
            }
        }
        
        return '';
    }
    
    /**
     * Ürün kategorisini çeker
     * RapidAPI endpoint: /articles/get-article-category/article-id/{articleId}/lang-id/{langId}
     */
    private function getArticleCategory($articleId) {
        $url = $this->apiUrl . '/articles/get-article-category/article-id/' . urlencode($articleId) . '/lang-id/' . $this->languageId;
        
        try {
            $response = $this->makeApiCall($url, 'GET');
            return $response['data'] ?? $response ?? null;
        } catch (Exception $e) {
            return null;
        }
    }
    
    /**
     * API çağrısı yapar (RapidAPI formatında)
     */
    private function makeApiCall($url, $method = 'GET', $params = []) {
        if (!function_exists('curl_init')) {
            throw new Exception('cURL desteği yok');
        }
        
        if (empty($this->rapidApiKey)) {
            throw new Exception('RapidAPI anahtarı belirtilmedi');
        }
        
        // GET isteği için parametreleri URL'e ekle
        if ($method === 'GET' && !empty($params)) {
            $url .= '?' . http_build_query($params);
        }
        
        $ch = curl_init();
        
        $headers = [
            'x-rapidapi-host: ' . $this->rapidApiHost,
            'x-rapidapi-key: ' . $this->rapidApiKey,
            'Accept: application/json'
        ];
        
        $curlOptions = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false
        ];
        
        if ($method === 'POST') {
            $curlOptions[CURLOPT_POST] = true;
            if (!empty($params)) {
                $curlOptions[CURLOPT_POSTFIELDS] = json_encode($params);
                $headers[] = 'Content-Type: application/json';
            }
        }
        
        curl_setopt_array($ch, $curlOptions);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        if ($curlError) {
            throw new Exception('cURL hatası: ' . $curlError);
        }
        
        if ($httpCode !== 200) {
            $errorMsg = 'API hatası: HTTP ' . $httpCode;
            if ($response) {
                // Response'u JSON olarak parse etmeyi dene
                $errorData = @json_decode($response, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($errorData)) {
                    if (isset($errorData['message'])) {
                        $errorMsg .= ' - ' . $errorData['message'];
                    } elseif (isset($errorData['error'])) {
                        $errorMsg .= ' - ' . $errorData['error'];
                    } else {
                        $errorMsg .= ' - Response: ' . substr($response, 0, 200);
                    }
                } else {
                    // JSON değilse direkt göster
                    $errorMsg .= ' - Response: ' . substr($response, 0, 200);
                }
            } else {
                $errorMsg .= ' - Boş yanıt';
            }
            throw new Exception($errorMsg);
        }
        
        // Response boş mu kontrol et
        if (empty($response)) {
            throw new Exception('API boş yanıt döndürdü');
        }
        
        $decoded = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            // JSON parse hatası - response'u logla
            $errorMsg = 'JSON parse hatası: ' . json_last_error_msg();
            $errorMsg .= ' - Response (ilk 500 karakter): ' . substr($response, 0, 500);
            error_log('TecDoc API JSON Parse Hatası: ' . $errorMsg);
            throw new Exception($errorMsg);
        }
        
        return $decoded;
    }
    
    /**
     * Toplu ürün güncelleme - OEM kodları ve görselleri ekler
     * 
     * @param PDO $db Veritabanı bağlantısı
     * @param int $limit İşlenecek ürün sayısı (0 = tümü)
     * @return array İşlem sonuçları
     */
    public function updateProductsWithTecDocData($db, $limit = 0) {
        $results = [
            'success' => true,
            'processed' => 0,
            'updated' => 0,
            'errors' => []
        ];
        
        // Ürünleri çek (stok kodu olanlar)
        $sql = "SELECT id, stok_kodu, baslik FROM urun WHERE stok_kodu IS NOT NULL AND stok_kodu != ''";
        if ($limit > 0) {
            $sql .= " LIMIT " . (int)$limit;
        }
        
        $query = $db->query($sql);
        $products = $query->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($products as $product) {
            $results['processed']++;
            
            try {
                // ÖNEMLİ: Stok kodunu (article number) kullanarak TecDoc'dan veri çek
                // Ürün adını DEĞİL, stok kodunu kullan
                $stokKodu = trim($product['stok_kodu']);
                
                if (empty($stokKodu)) {
                    $results['errors'][] = [
                        'product_id' => $product['id'],
                        'product_name' => $product['baslik'],
                        'stok_kodu' => $stokKodu,
                        'error' => 'Stok kodu boş'
                    ];
                    continue;
                }
                
                // TecDoc'dan veri çek (stok kodu ile)
                $tecdocData = $this->getProductOEMCodesAndImages($stokKodu);
                
                $hasData = false;
                $errorMessages = [];
                
                // OEM kodlarını kaydet
                if (!empty($tecdocData['oem_codes'])) {
                    try {
                        $this->saveOEMCodes($db, $product['id'], $tecdocData['oem_codes']);
                        $hasData = true;
                    } catch (Exception $e) {
                        $errorMessages[] = 'OEM kayıt hatası: ' . $e->getMessage();
                    }
                }
                
                // Görselleri kaydet
                if (!empty($tecdocData['images'])) {
                    try {
                        $this->saveImages($db, $product['id'], $tecdocData['images']);
                        $hasData = true;
                    } catch (Exception $e) {
                        $errorMessages[] = 'Görsel kayıt hatası: ' . $e->getMessage();
                    }
                }
                
                if ($hasData) {
                    $results['updated']++;
                } else {
                    // Hata mesajını oluştur
                    $errorMsg = $tecdocData['error'] ?? 'Veri bulunamadı';
                    if (!empty($errorMessages)) {
                        $errorMsg .= ' | ' . implode(' | ', $errorMessages);
                    }
                    
                    // Debug için stok kodunu da ekle
                    $errorMsg .= ' (Stok Kodu: ' . $stokKodu . ')';
                    
                    $results['errors'][] = [
                        'product_id' => $product['id'],
                        'product_name' => $product['baslik'],
                        'stok_kodu' => $stokKodu,
                        'error' => $errorMsg
                    ];
                }
                
            } catch (Exception $e) {
                $stokKodu = isset($stokKodu) ? $stokKodu : ($product['stok_kodu'] ?? '');
                $results['errors'][] = [
                    'product_id' => $product['id'],
                    'product_name' => $product['baslik'],
                    'stok_kodu' => $stokKodu,
                    'error' => $e->getMessage() . ' (Line: ' . $e->getLine() . ', Stok Kodu: ' . $stokKodu . ')'
                ];
            }
            
            // Rate limit için kısa bekleme (RapidAPI free tier için)
            usleep(200000); // 0.2 saniye bekle (artırıldı)
        }
        
        return $results;
    }
    
    /**
     * OEM kodlarını veritabanına kaydeder
     */
    private function saveOEMCodes($db, $productId, $oemCodes) {
        // OEM kodları tablosu yoksa oluştur
        try {
            $db->exec("CREATE TABLE IF NOT EXISTS urun_oem (
                id INT AUTO_INCREMENT PRIMARY KEY,
                urun_id INT NOT NULL,
                oem_kodu VARCHAR(255) NOT NULL,
                uretici VARCHAR(255) DEFAULT '',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_urun_id (urun_id),
                INDEX idx_oem_kodu (oem_kodu),
                UNIQUE KEY unique_urun_oem (urun_id, oem_kodu)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        } catch (Exception $e) {
            // Tablo zaten varsa hata vermez
        }
        
        // OEM kodlarını ekle
        foreach ($oemCodes as $oem) {
            $oemCode = is_array($oem) ? ($oem['code'] ?? $oem['oemCode'] ?? $oem['oem'] ?? '') : $oem;
            $manufacturer = is_array($oem) ? ($oem['manufacturer'] ?? $oem['brand'] ?? $oem['maker'] ?? '') : '';
            
            if (!empty($oemCode)) {
                // Mevcut kontrolü
                $check = $db->prepare("SELECT id FROM urun_oem WHERE urun_id = ? AND oem_kodu = ? LIMIT 1");
                $check->execute([$productId, $oemCode]);
                
                if (!$check->fetch()) {
                    try {
                        $insert = $db->prepare("INSERT INTO urun_oem SET urun_id = ?, oem_kodu = ?, uretici = ?");
                        $insert->execute([$productId, $oemCode, $manufacturer]);
                    } catch (Exception $e) {
                        // Unique key hatası olabilir, görmezden gel
                    }
                }
            }
        }
    }
    
    /**
     * Görselleri veritabanına kaydeder
     * 
     * @param PDO $db Veritabanı bağlantısı
     * @param int $productId Ürün ID
     * @param array $images Görsel URL'leri
     */
    private function saveImages($db, $productId, $images) {
        if (empty($images) || !is_array($images)) {
            return;
        }
        
        foreach ($images as $image) {
            // Eğer array ise URL'i çıkar
            $imageUrl = is_array($image) ? $this->extractImageUrl($image) : trim($image);
            
            // URL doğrulaması
            if (empty($imageUrl)) {
                continue;
            }
            
            // Eğer // ile başlıyorsa https: ekle
            if (preg_match('/^\/\//', $imageUrl)) {
                $imageUrl = 'https:' . $imageUrl;
            }
            
            // URL formatını kontrol et
            if (!filter_var($imageUrl, FILTER_VALIDATE_URL) && !preg_match('/^https?:\/\//', $imageUrl)) {
                // Geçersiz URL, atla
                continue;
            }
            
            // Mevcut kontrolü (aynı URL'den sadece bir tane)
            $check = $db->prepare("SELECT id FROM urun_img WHERE urun_id = ? AND img = ? LIMIT 1");
            $check->execute([$productId, $imageUrl]);
            
            if (!$check->fetch()) {
                try {
                    $insert = $db->prepare("INSERT INTO urun_img SET urun_id = ?, img = ?");
                    $insert->execute([$productId, $imageUrl]);
                } catch (Exception $e) {
                    // Hata durumunda log (production'da log dosyasına yazılabilir)
                    error_log('TecDoc saveImages hatası: ' . $e->getMessage() . ' - ProductId: ' . $productId . ' - ImageUrl: ' . $imageUrl);
                }
            }
        }
    }
}

// ============================================
// AJAX ENDPOINT
// ============================================

// AJAX isteği kontrolü
$isAjax = isset($_GET['ajax']) || isset($_POST['ajax']) || 
          (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest');

if ($isAjax) {
    // Hata yakalama için try-catch
    try {
        header('Content-Type: application/json; charset=utf-8');
        
        // Veritabanı bağlantısını dahil et
        $dbFile = __DIR__ . '/panel/db-ayar.php';
        if (!file_exists($dbFile)) {
            $dbFile = __DIR__ . '/db-ayar.php';
        }
        if (!file_exists($dbFile)) {
            $dbFile = dirname(__DIR__) . '/panel/db-ayar.php';
        }
        if (file_exists($dbFile)) {
            require_once $dbFile;
        } else {
            throw new Exception('Veritabanı bağlantı dosyası bulunamadı. Aranan yollar: ' . __DIR__ . '/panel/db-ayar.php, ' . __DIR__ . '/db-ayar.php');
        }
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // TecDoc API yapılandırması (RapidAPI üzerinden)
    $tecdocConfig = [
        'rapidApiKey' => 'f26d3e7e77msheb8c9c02341b056p143434jsnc02c50667ead', // RapidAPI anahtarı
        'rapidApiHost' => 'tecdoc-catalog.p.rapidapi.com', // RapidAPI host
        'languageId' => 2 // 2 = Türkçe, 1 = İngilizce
    ];
    
    // Yapılandırmayı dosyadan oku (isteğe bağlı)
    $configFile = __DIR__ . '/tecdoc-config.php';
    if (file_exists($configFile)) {
        try {
            $customConfig = require $configFile;
            if (is_array($customConfig)) {
                $tecdocConfig = array_merge($tecdocConfig, $customConfig);
            }
        } catch (Exception $e) {
            // Config dosyası hatası, varsayılan değerleri kullan
            error_log('TecDoc config dosyası hatası: ' . $e->getMessage());
        }
    }
    
    try {
        $tecdocAPI = new TecDocAPI($tecdocConfig);
    } catch (Exception $e) {
        $errorResponse = [
            'success' => false,
            'error' => 'TecDoc API başlatılamadı: ' . $e->getMessage()
        ];
        echo json_encode($errorResponse, JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    $action = $_GET['action'] ?? $_POST['action'] ?? '';
    
    if ($action === 'updateProducts') {
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : (isset($_POST['limit']) ? (int)$_POST['limit'] : 0);
        
        try {
            // TecDoc API yapılandırmasını kontrol et
            if (empty($tecdocConfig['rapidApiKey'])) {
                throw new Exception('RapidAPI anahtarı belirtilmedi. api-tecdoc.php dosyasında rapidApiKey ayarlayın.');
            }
            
            // Veritabanı bağlantısını kontrol et
            if (!isset($db) || !$db) {
                throw new Exception('Veritabanı bağlantısı kurulamadı');
            }
            
            $results = $tecdocAPI->updateProductsWithTecDocData($db, $limit);
            
            // JSON encode öncesi kontrol
            $json = json_encode($results, JSON_UNESCAPED_UNICODE);
            if ($json === false) {
                $errorMsg = 'JSON encode hatası: ' . json_last_error_msg();
                error_log('TecDoc JSON encode hatası: ' . $errorMsg);
                throw new Exception($errorMsg);
            }
            
            echo $json;
        } catch (Exception $e) {
            $errorResponse = [
                'success' => false,
                'error' => $e->getMessage(),
                'file' => basename($e->getFile()),
                'line' => $e->getLine()
            ];
            
            // Debug mode için trace ekle
            if (isset($_GET['debug']) || isset($_POST['debug'])) {
                $errorResponse['trace'] = $e->getTraceAsString();
            }
            
            $json = json_encode($errorResponse, JSON_UNESCAPED_UNICODE);
            if ($json === false) {
                // JSON encode başarısız olursa basit format döndür
                header('Content-Type: text/plain; charset=utf-8');
                echo 'HATA: ' . $e->getMessage();
            } else {
                echo $json;
            }
        }
        exit;
    }
    
    if ($action === 'getProductData') {
        $articleNumber = $_GET['articleNumber'] ?? $_POST['articleNumber'] ?? '';
        $manufacturer = $_GET['manufacturer'] ?? $_POST['manufacturer'] ?? '';
        
        if (empty($articleNumber)) {
            $errorResponse = [
                'success' => false,
                'error' => 'Ürün numarası belirtilmedi'
            ];
            $json = json_encode($errorResponse, JSON_UNESCAPED_UNICODE);
            echo $json ?: '{"success":false,"error":"JSON encode hatası"}';
            exit;
        }
        
        try {
            $result = $tecdocAPI->getProductOEMCodesAndImages($articleNumber, $manufacturer);
            $json = json_encode($result, JSON_UNESCAPED_UNICODE);
            if ($json === false) {
                throw new Exception('JSON encode hatası: ' . json_last_error_msg());
            }
            echo $json;
        } catch (Exception $e) {
            $errorResponse = [
                'success' => false,
                'error' => $e->getMessage()
            ];
            if (isset($_GET['debug']) || isset($_POST['debug'])) {
                $errorResponse['trace'] = $e->getTraceAsString();
            }
            $json = json_encode($errorResponse, JSON_UNESCAPED_UNICODE);
            echo $json ?: '{"success":false,"error":"' . addslashes($e->getMessage()) . '"}';
        }
        exit;
    }
    
    // Geçersiz action
    $errorResponse = [
        'success' => false,
        'error' => 'Geçersiz action: ' . ($action ?: 'belirtilmedi'),
        'available_actions' => ['updateProducts', 'getProductData']
    ];
    
    $json = json_encode($errorResponse, JSON_UNESCAPED_UNICODE);
    if ($json === false) {
        // JSON encode başarısız olursa basit text döndür
        header('Content-Type: text/plain; charset=utf-8');
        echo 'HATA: JSON encode başarısız - ' . json_last_error_msg();
    } else {
        echo $json;
    }
    exit;
}
?>
