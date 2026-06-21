<?php
/**
 * Eryaz API Entegrasyon Dosyası
 * Tüm API işlemleri, AJAX endpoint ve görüntüleme sayfası bu dosyada
 */

// ============================================
// ERYAZ API SINIFI
// ============================================
class EryazAPI {
    private $apiUrl = "http://share.eryaz.net/api/integration/getdata";
    private $companyKey = "Mh2HTV2R";
    private $userName = "teknikdizel_btmotorshop";
    private $password = "teknikdizel_btmotor123";

    public function stripEryazBoschPrefix($stokKodu) {
        return preg_replace('/^(30-|31-|32-|3e-?)/i', '', trim((string)$stokKodu));
    }

    private function ensureEryazStockCodeColumn(PDO $db) {
        try {
            $col = $db->query("SHOW COLUMNS FROM urun LIKE 'eryaz_stok_kodu'")->fetch();
            if (!$col) {
                $db->exec("ALTER TABLE urun ADD COLUMN eryaz_stok_kodu VARCHAR(255) NULL DEFAULT NULL AFTER stok_kodu");
                $db->exec("CREATE INDEX idx_eryaz_stok_kodu ON urun (eryaz_stok_kodu)");
            }
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    private function findProductByEryazStockCode(PDO $db, $rawStockCode, $fields = 'id') {
        $rawStockCode = trim((string)$rawStockCode);
        if ($rawStockCode === '') {
            return false;
        }
        $cleanStockCode = $this->stripEryazBoschPrefix($rawStockCode);
        $hasEryazColumn = $this->ensureEryazStockCodeColumn($db);
        if ($hasEryazColumn) {
            $st = $db->prepare("SELECT {$fields} FROM urun WHERE eryaz_stok_kodu = ? OR stok_kodu = ? OR stok_kodu = ? LIMIT 1");
            $st->execute([$rawStockCode, $rawStockCode, $cleanStockCode]);
        } else {
            $st = $db->prepare("SELECT {$fields} FROM urun WHERE stok_kodu = ? OR stok_kodu = ? LIMIT 1");
            $st->execute([$rawStockCode, $cleanStockCode]);
        }
        return $st->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Sunucunun dış IP adresini tespit eder (Eryaz API'nin göreceği IP)
     * 
     * @return string|false IP adresi veya hata durumunda false
     */
    public function getServerPublicIP() {
        // Önce cache'den kontrol et (aynı request içinde birden fazla çağrı olabilir)
        static $cachedIP = null;
        if ($cachedIP !== null) {
            return $cachedIP;
        }
        
        // CURL fonksiyonunun varlığını kontrol et
        if (!function_exists('curl_init')) {
            $cachedIP = false;
            return false;
        }
        
        // HIZLI IP TESPİTİ - Sadece en hızlı servisi dene (performans için)
        // Sadece bir servis denenecek, timeout çok kısa
        $services = [
            ['url' => 'https://api.ipify.org?format=json', 'type' => 'json'],
            ['url' => 'https://ifconfig.me/ip', 'type' => 'text']
        ];
        
        // Sadece ilk servisi dene (çok hızlı)
        $service = $services[0];
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $service['url'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 1, // Çok kısa timeout (1 saniye)
            CURLOPT_CONNECTTIMEOUT => 1,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_USERAGENT => 'Mozilla/5.0'
        ]);
        
        $response = @curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        if ($httpCode === 200 && $response && !$curlError) {
            $response = trim($response);
            
            if ($service['type'] === 'json') {
                $json = json_decode($response, true);
                if ($json && isset($json['ip'])) {
                    $ip = trim($json['ip']);
                    if (filter_var($ip, FILTER_VALIDATE_IP)) {
                        $cachedIP = $ip;
                        return $cachedIP;
                    }
                }
            } else {
                // Düz metin IP
                if (filter_var($response, FILTER_VALIDATE_IP)) {
                    $cachedIP = $response;
                    return $cachedIP;
                }
            }
        }
        
        $cachedIP = false;
        return false;
    }
    
    /**
     * API'den veri çeker
     * 
     * @param string $functionName Fonksiyon adı (örn: "GetProductList")
     * @param array $parameters Parametreler (örn: ["@pStart" => 1, "@pEnd" => 1000])
     * @return array|false API yanıtı veya hata durumunda false
     */
    public function getData($functionName, $parameters = []) {
        $curl = curl_init();
        
        // JSON body hazırla (C# örneğine göre)
        // C# örneğinde: Parameters = new { @pStart = 1, @pEnd = 1000 } veya Parameters = ""
        // PHP'de boş array ise boş string gönder, değilse object gönder
        if (empty($parameters)) {
            // Parameters boş ise boş string gönder
            $postData = [
                "CompanyKey" => $this->companyKey,
                "FunctionName" => $functionName,
                "UserName" => $this->userName,
                "Password" => $this->password,
                "Parameters" => ""
            ];
        } else {
            // Parameters varsa object olarak gönder
            $postData = [
                "CompanyKey" => $this->companyKey,
                "FunctionName" => $functionName,
                "UserName" => $this->userName,
                "Password" => $this->password,
                "Parameters" => $parameters
            ];
        }
        
        // JSON string'e çevir (C# JsonConvert.SerializeObject gibi)
        $jsonBody = json_encode($postData, JSON_UNESCAPED_UNICODE);
        
        curl_setopt_array($curl, array(
            CURLOPT_URL => $this->apiUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => $jsonBody,
            CURLOPT_HTTPHEADER => array(
                "cache-control: no-cache",
                "content-type: application/json",
                "Accept: application/json"
                // "Accept: application/xml" // XML yanıt isterseniz bunu kullanın
            ),
        ));
        
        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error = curl_error($curl);
        
        curl_close($curl);
        
        // Hata kontrolü
        if ($error) {
            return [
                'success' => false,
                'error' => 'CURL Error: ' . $error
            ];
        }
        
        if ($httpCode !== 200) {
            return [
                'success' => false,
                'error' => 'HTTP Error: ' . $httpCode,
                'response' => $response
            ];
        }
        
        // JSON yanıtını decode et
        $decodedResponse = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return [
                'success' => false,
                'error' => 'JSON Decode Error: ' . json_last_error_msg(),
                'raw_response' => $response
            ];
        }
        
        // API yanıtında hata kontrolü (Data içinde Error olup olmadığını kontrol et)
        if (isset($decodedResponse['Data']) && is_array($decodedResponse['Data'])) {
            foreach ($decodedResponse['Data'] as $item) {
                if (is_array($item) && isset($item['Error'])) {
                    $errorMessage = $item['Error'];
                    $errorDescription = '';
                    
                    // Hata mesajına göre açıklama ekle
                    if (stripos($errorMessage, 'IP') !== false || stripos($errorMessage, 'Geçersiz IP') !== false) {
                        // Sunucunun dış IP adresini tespit et (Eryaz API'nin göreceği IP)
                        $publicIP = $this->getServerPublicIP();
                        $serverIP = $publicIP ? $publicIP : 
                                   ($_SERVER['SERVER_ADDR'] ?? 
                                   (isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? explode(',', trim($_SERVER['HTTP_X_FORWARDED_FOR']))[0] : null) ??
                                   ($_SERVER['REMOTE_ADDR'] ?? 'Bilinmiyor'));
                        
                        $errorDescription = 'Bu hata, sunucunuzun IP adresinin Eryaz API izinli IP listesinde olmadığını gösterir. ' .
                                          'Eryaz API\'nin gördüğü IP adresiniz: ' . htmlspecialchars($serverIP) . '. ' .
                                          'Çözüm için: Eryaz yetkilileriyle iletişime geçerek yukarıdaki IP adresini izinli IP listesine ekletmeniz gerekmektedir. ' .
                                          'Alternatif olarak, API çağrılarını izinli bir IP adresinden yapmanız gerekebilir. ' .
                                          'Not: Eğer bir proxy veya load balancer kullanıyorsanız, gerçek sunucu IP adresini Eryaz\'a bildirmeniz gerekebilir.';
                    } elseif (stripos($errorMessage, 'kullanıcı') !== false || stripos($errorMessage, 'şifre') !== false || stripos($errorMessage, 'password') !== false) {
                        $errorDescription = 'Kullanıcı adı veya şifre hatalı. API bilgilerinizi kontrol edin.';
                    } elseif (stripos($errorMessage, 'CompanyKey') !== false || stripos($errorMessage, 'firma') !== false) {
                        $errorDescription = 'Firma kodu (CompanyKey) hatalı veya geçersiz. API bilgilerinizi kontrol edin.';
                    } elseif (stripos($errorMessage, 'fonksiyon') !== false || stripos($errorMessage, 'function') !== false) {
                        $errorDescription = 'Fonksiyon adı hatalı veya bu fonksiyon için yetkiniz bulunmuyor.';
                    } else {
                        $errorDescription = 'API tarafından bir hata döndürüldü. Lütfen hata mesajını kontrol edin.';
                    }
                    
                    return [
                        'success' => false,
                        'error' => $errorMessage,
                        'error_description' => $errorDescription,
                        'api_response' => $decodedResponse,
                        'raw_response' => $response
                    ];
                }
            }
        }
        
        // Status false ise de hata olarak işaretle
        if (isset($decodedResponse['Status']) && $decodedResponse['Status'] === false) {
            $errorMsg = $decodedResponse['Message'] ?? 'Bilinmeyen hata';
            return [
                'success' => false,
                'error' => $errorMsg,
                'error_description' => 'API yanıtında Status: false döndü. ' . $errorMsg,
                'api_response' => $decodedResponse,
                'raw_response' => $response
            ];
        }
        
        return [
            'success' => true,
            'data' => $decodedResponse,
            'raw_response' => $response
        ];
    }
    
    /**
     * Ürün listesini çeker
     * 
     * @param int $start Başlangıç kayıt numarası
     * @param int $end Bitiş kayıt numarası
     * @return array|false Ürün listesi veya hata durumunda false
     */
    public function getProductList($start = 1, $end = 1000) {
        // C# örneğine göre Parameters object olarak gönderiliyor
        $parameters = [
            "@pStart" => $start,
            "@pEnd" => $end
        ];
        
        return $this->getData("GetProductList", $parameters);
    }
    
    /**
     * API bilgilerini değiştirmek için setter metodları
     */
    public function setCompanyKey($key) {
        $this->companyKey = $key;
    }
    
    public function setUserName($userName) {
        $this->userName = $userName;
    }
    
    public function setPassword($password) {
        $this->password = $password;
    }
    
    public function setApiUrl($url) {
        $this->apiUrl = $url;
    }
    
    /**
     * Manufacturer'ı kategori olarak oluşturur veya mevcut kategori ID'sini döner
     * Mevcut kategorilerle eşleştirme yapar (fuzzy matching)
     * 
     * @param string $manufacturerName Manufacturer adı
     * @param PDO $db Veritabanı bağlantısı
     * @return array ['id' => int, 'created' => bool] Kategori ID ve yeni oluşturuldu mu bilgisi
     */
    public function getOrCreateCategoryByManufacturer($manufacturerName, $db) {
        if (empty($manufacturerName)) {
            return ['id' => false, 'created' => false];
        }
        
        // Manufacturer adını temizle
        $manufacturerName = trim($manufacturerName);
        if (empty($manufacturerName)) {
            return ['id' => false, 'created' => false];
        }
        
        // 1. Tam eşleşme kontrolü (büyük/küçük harf duyarsız)
        $checkQuery = $db->prepare("SELECT id, baslik FROM kategori WHERE LOWER(TRIM(baslik)) = LOWER(TRIM(?)) LIMIT 1");
        $checkQuery->execute([$manufacturerName]);
        $existingCategory = $checkQuery->fetch(PDO::FETCH_ASSOC);
        
        if ($existingCategory) {
            // Tam eşleşme bulundu
            return ['id' => (int)$existingCategory['id'], 'created' => false, 'matched' => 'exact'];
        }
        
        // 2. Benzer kategori arama (fuzzy matching)
        // Tüm kategorileri al
        $allCategories = $db->query("SELECT id, baslik FROM kategori ORDER BY baslik ASC")->fetchAll(PDO::FETCH_ASSOC);
        
        $bestMatch = null;
        $bestScore = 0;
        $manufacturerLower = mb_strtolower($manufacturerName, 'UTF-8');
        
        foreach ($allCategories as $category) {
            $categoryLower = mb_strtolower($category['baslik'], 'UTF-8');
            
            // a) İçeriyor mu kontrolü (manufacturer kategori içinde veya kategori manufacturer içinde)
            if (stripos($categoryLower, $manufacturerLower) !== false || 
                stripos($manufacturerLower, $categoryLower) !== false) {
                $score = max(
                    similar_text($manufacturerLower, $categoryLower),
                    strlen($manufacturerLower) / max(strlen($categoryLower), 1) * 100
                );
                if ($score > $bestScore) {
                    $bestScore = $score;
                    $bestMatch = $category;
                }
            }
            
            // b) Benzerlik skoru (similar_text)
            $similarity = similar_text($manufacturerLower, $categoryLower, $percent);
            if ($percent > 70 && $percent > $bestScore) { // %70'den fazla benzerlik
                $bestScore = $percent;
                $bestMatch = $category;
            }
            
            // c) Kelime bazlı eşleşme (her kelimeyi kontrol et)
            $manufacturerWords = preg_split('/[\s\-_]+/', $manufacturerLower);
            $categoryWords = preg_split('/[\s\-_]+/', $categoryLower);
            $matchingWords = 0;
            foreach ($manufacturerWords as $mWord) {
                foreach ($categoryWords as $cWord) {
                    if ($mWord === $cWord || stripos($cWord, $mWord) !== false || stripos($mWord, $cWord) !== false) {
                        $matchingWords++;
                        break;
                    }
                }
            }
            if (count($manufacturerWords) > 0) {
                $wordScore = ($matchingWords / count($manufacturerWords)) * 100;
                if ($wordScore > 50 && $wordScore > $bestScore) { // %50'den fazla kelime eşleşmesi
                    $bestScore = $wordScore;
                    $bestMatch = $category;
                }
            }
        }
        
        // Eğer iyi bir eşleşme bulunduysa (skor 50'den fazla)
        if ($bestMatch && $bestScore >= 50) {
            return [
                'id' => (int)$bestMatch['id'], 
                'created' => false, 
                'matched' => 'similar',
                'match_score' => $bestScore,
                'matched_category' => $bestMatch['baslik']
            ];
        }
        
        // Eşleşme bulunamadı, yeni kategori oluştur
        // Kategori oluştur
        $insertQuery = $db->prepare("INSERT INTO kategori SET baslik = ?, sef = ?, ust_kategori = ?, ust_menu = ?, alt_menu = ?, aciklama = ?, kisa_aciklama = ?, sira = ?");
        $insertResult = $insertQuery->execute([
            $manufacturerName,
            '', // Sef boş bırakılacak, sonra güncellenecek
            0,  // Ana kategori (ust_kategori = 0)
            0,  // ust_menu = 0
            0,  // alt_menu = 0
            '', // aciklama
            '', // kisa_aciklama
            9999 // sira
        ]);
        
        if (!$insertResult) {
            return ['id' => false, 'created' => false];
        }
        
        $categoryId = $db->lastInsertId();
        
        // Sef'i güncelle (id ile birlikte)
        if (function_exists('sef')) {
            $sef = sef($manufacturerName) . '-' . $categoryId;
        } else {
            $sef = $this->createSef($manufacturerName) . '-' . $categoryId;
        }
        
        $updateQuery = $db->prepare("UPDATE kategori SET sef = ? WHERE id = ?");
        $updateQuery->execute([$sef, $categoryId]);
        
        return ['id' => (int)$categoryId, 'created' => true];
    }
    
    /**
     * Basit SEF URL oluşturucu (sef fonksiyonu yoksa)
     */
    private function createSef($str) {
        // Türkçe karakterleri değiştir
        $turkish = ['ş', 'Ş', 'ı', 'İ', 'ğ', 'Ğ', 'ü', 'Ü', 'ö', 'Ö', 'ç', 'Ç'];
        $english = ['s', 's', 'i', 'i', 'g', 'g', 'u', 'u', 'o', 'o', 'c', 'c'];
        $str = str_replace($turkish, $english, $str);
        
        // Küçük harfe çevir
        $str = mb_strtolower($str, 'UTF-8');
        
        // Özel karakterleri temizle
        $str = preg_replace('/[^a-z0-9]+/', '-', $str);
        
        // Başındaki ve sonundaki tireleri temizle
        $str = trim($str, '-');
        
        return $str;
    }
    
    /**
     * API'den gelen ürünü veritabanına ekler
     * 
     * @param array $product API'den gelen ürün verisi
     * @param PDO $db Veritabanı bağlantısı
     * @return array ['success' => bool, 'product_id' => int, 'message' => string]
     */
    public function addProductToDatabase($product, $db) {
        if (!is_array($product) || empty($product)) {
            return ['success' => false, 'product_id' => 0, 'message' => 'Geçersiz ürün verisi'];
        }
        
        // Ürün alanlarını eşleştir (API'den gelen alan isimlerine göre)
        // Farklı alan isimleri olabilir, hepsini kontrol et
        $baslik = $this->getProductField($product, ['ProductName', 'Name', 'Title', 'Baslik', 'UrunAdi', 'urun_adi']);
        $eryaz_stok_kodu = $this->getProductField($product, ['StockCode', 'SKU', 'Code', 'StokKodu', 'stok_kodu', 'Barcode', 'Barkod']);
        $stok_kodu = $this->stripEryazBoschPrefix($eryaz_stok_kodu);
        $kisa_aciklama = $this->getProductField($product, ['Description', 'ShortDescription', 'KisaAciklama', 'kisa_aciklama', 'Summary']);
        $aciklama = $this->getProductField($product, ['FullDescription', 'LongDescription', 'Aciklama', 'aciklama', 'Detail', 'Details']);
        $stok = $this->getProductField($product, ['Stock', 'Quantity', 'Stok', 'stok', 'Qty', 'AvailableQuantity'], 0);
        $fiyat = $this->getProductField($product, ['Price', 'SalePrice', 'Fiyat', 'fiyat', 'SellingPrice'], 0);
        $eski_fiyat = $this->getProductField($product, ['OldPrice', 'ListPrice', 'EskiFiyat', 'eski_fiyat', 'RegularPrice', 'OriginalPrice'], 0);
        $kdv = $this->getProductField($product, ['VAT', 'KDV', 'kdv', 'Tax', 'TaxRate'], 0);
        $kargo_fiyati = $this->getProductField($product, ['ShippingPrice', 'KargoFiyati', 'kargo_fiyati', 'ShippingCost'], 0);
        $manufacturer = $this->getProductField($product, ['Manufacturer', 'manufacturer', 'Marka', 'marka', 'Brand', 'brand']);
        
        // Depo stok durumları - Eryaz API'den gelen bilgiler
        // Tüm olası field isimlerini kontrol et
        $maslak_status = $this->getProductField($product, [
            'Maslak_Status', 'maslak_status', 'MaslakStatus', 'Maslak_Stok', 'maslak_stok',
            'MaslakStatus', 'MASLAK_STATUS', 'MASLAK_STOK', 'Maslak', 'maslak'
        ], 'Yok');
        $bolu_status = $this->getProductField($product, [
            'Bolu_Status', 'bolu_status', 'BoluStatus', 'Bolu_Stok', 'bolu_stok',
            'BoluStatus', 'BOLU_STATUS', 'BOLU_STOK', 'Bolu', 'bolu'
        ], 'Yok');
        $imes_status = $this->getProductField($product, [
            'İmes_Status', 'imes_status', 'İmesStatus', 'Imes_Status', 'imesStatus',
            'İmes_Stok', 'imes_stok', 'İmesStok', 'Imes_Stok', 'imesStok',
            'İMES_STATUS', 'İMES_STOK', 'İmes', 'imes'
        ], 'Yok');
        $ankara_status = $this->getProductField($product, [
            'Ankara_Status', 'ankara_status', 'AnkaraStatus', 'Ankara_Stok', 'ankara_stok',
            'AnkaraStatus', 'ANKARA_STATUS', 'ANKARA_STOK', 'Ankara', 'ankara'
        ], 'Yok');
        $ikitelli_status = $this->getProductField($product, [
            'İkitelli_Status', 'ikitelli_status', 'İkitelliStatus', 'Ikitelli_Status', 'ikitelliStatus',
            'İkitelli_Stok', 'ikitelli_stok', 'İkitelliStok', 'Ikitelli_Stok', 'ikitelliStok',
            'İKİTELLİ_STATUS', 'İKİTELLİ_STOK', 'İkitelli', 'ikitelli'
        ], 'Yok');
        
        // Var/Yok değerlerini 1/0'a çevir (trim ve case-insensitive)
        $maslak_stok = (strtolower(trim($maslak_status)) === 'var') ? 1 : 0;
        $bolu_stok = (strtolower(trim($bolu_status)) === 'var') ? 1 : 0;
        $imes_stok = (strtolower(trim($imes_status)) === 'var') ? 1 : 0;
        $ankara_stok = (strtolower(trim($ankara_status)) === 'var') ? 1 : 0;
        $ikitelli_stok = (strtolower(trim($ikitelli_status)) === 'var') ? 1 : 0;
        
        // Zorunlu alanları kontrol et
        if (empty($baslik)) {
            return ['success' => false, 'product_id' => 0, 'message' => 'Ürün adı (baslik) boş olamaz'];
        }
        
        if (empty($stok_kodu)) {
            // Stok kodu yoksa, ürün adından oluştur
            $stok_kodu = $this->createSef($baslik) . '-' . time();
            $eryaz_stok_kodu = $stok_kodu;
        }
        
        // Stok kodu ile mevcut ürünü kontrol et (duplicate kontrolü)
        $existingProduct = $this->findProductByEryazStockCode($db, $eryaz_stok_kodu, 'id');
        
        if ($existingProduct) {
            // Ürün zaten var, güncelle
            $productId = (int)$existingProduct['id'];
            if ($this->ensureEryazStockCodeColumn($db)) {
                try {
                    $mapSt = $db->prepare('UPDATE urun SET stok_kodu = ?, eryaz_stok_kodu = ? WHERE id = ? LIMIT 1');
                    $mapSt->execute([$stok_kodu, $eryaz_stok_kodu, $productId]);
                } catch (Exception $e) {
                    // Esleme kolonu yoksa normal guncelleme devam etsin.
                }
            }
            
            // stok_manuel değerini kontrol et
            $manuelQuery = $db->prepare("SELECT stok_manuel FROM urun WHERE id = ? LIMIT 1");
            $manuelQuery->execute([$productId]);
            $manuelResult = $manuelQuery->fetch(PDO::FETCH_ASSOC);
            $stokManuel = isset($manuelResult['stok_manuel']) ? (int)$manuelResult['stok_manuel'] : 0;
            
            // Depo stok alanlarının varlığını kontrol et
            try {
                $checkColumns = $db->query("SHOW COLUMNS FROM urun LIKE 'maslak_stok'")->fetch();
                $hasWarehouseColumns = ($checkColumns !== false);
            } catch (Exception $e) {
                $hasWarehouseColumns = false;
            }
            
            if ($hasWarehouseColumns) {
                try {
                    // Herhangi bir depoda stok varsa genel stok durumu "Var" (1)
                    $genel_stok = ($maslak_stok == 1 || $bolu_stok == 1 || $imes_stok == 1 || $ankara_stok == 1 || $ikitelli_stok == 1) ? 1 : 0;
                    
                    if ($stokManuel == 1) {
                        // stok_manuel = 1 ise, stok değerini güncelleme (admin panelinden manuel ayarlanmış)
                        $updateQuery = $db->prepare("UPDATE urun SET baslik = ?, kisa_aciklama = ?, marka_id = ?, eski_fiyat = ?, fiyat = ?, kdv = ?, kargo_fiyati = ?, aciklama = ?, maslak_stok = ?, bolu_stok = ?, imes_stok = ?, ankara_stok = ?, ikitelli_stok = ? WHERE id = ?");
                        $updateResult = $updateQuery->execute([
                            $baslik,
                            $kisa_aciklama,
                            0, // marka_id (şimdilik 0)
                            (float)$eski_fiyat,
                            (float)$fiyat,
                            (float)$kdv,
                            (float)$kargo_fiyati,
                            $aciklama,
                            $maslak_stok,
                            $bolu_stok,
                            $imes_stok,
                            $ankara_stok,
                            $ikitelli_stok,
                            $productId
                        ]);
                    } else {
                        // stok_manuel = 0 ise, stok değerini de güncelle
                        $updateQuery = $db->prepare("UPDATE urun SET baslik = ?, kisa_aciklama = ?, stok = ?, marka_id = ?, eski_fiyat = ?, fiyat = ?, kdv = ?, kargo_fiyati = ?, aciklama = ?, maslak_stok = ?, bolu_stok = ?, imes_stok = ?, ankara_stok = ?, ikitelli_stok = ? WHERE id = ?");
                        $updateResult = $updateQuery->execute([
                            $baslik,
                            $kisa_aciklama,
                            $genel_stok,
                            0, // marka_id (şimdilik 0)
                            (float)$eski_fiyat,
                            (float)$fiyat,
                            (float)$kdv,
                            (float)$kargo_fiyati,
                            $aciklama,
                            $maslak_stok,
                            $bolu_stok,
                            $imes_stok,
                            $ankara_stok,
                            $ikitelli_stok,
                            $productId
                        ]);
                    }
                } catch (Exception $e) {
                    // Sütunlar yoksa normal güncelleme yap
                    if ($stokManuel == 1) {
                        $updateQuery = $db->prepare("UPDATE urun SET baslik = ?, kisa_aciklama = ?, marka_id = ?, eski_fiyat = ?, fiyat = ?, kdv = ?, kargo_fiyati = ?, aciklama = ? WHERE id = ?");
                        $updateResult = $updateQuery->execute([
                            $baslik,
                            $kisa_aciklama,
                            0, // marka_id (şimdilik 0)
                            (float)$eski_fiyat,
                            (float)$fiyat,
                            (float)$kdv,
                            (float)$kargo_fiyati,
                            $aciklama,
                            $productId
                        ]);
                    } else {
                        $updateQuery = $db->prepare("UPDATE urun SET baslik = ?, kisa_aciklama = ?, stok = ?, marka_id = ?, eski_fiyat = ?, fiyat = ?, kdv = ?, kargo_fiyati = ?, aciklama = ? WHERE id = ?");
                        $updateResult = $updateQuery->execute([
                            $baslik,
                            $kisa_aciklama,
                            (int)$stok,
                            0, // marka_id (şimdilik 0)
                            (float)$eski_fiyat,
                            (float)$fiyat,
                            (float)$kdv,
                            (float)$kargo_fiyati,
                            $aciklama,
                            $productId
                        ]);
                    }
                }
            } else {
                if ($stokManuel == 1) {
                    $updateQuery = $db->prepare("UPDATE urun SET baslik = ?, kisa_aciklama = ?, marka_id = ?, eski_fiyat = ?, fiyat = ?, kdv = ?, kargo_fiyati = ?, aciklama = ? WHERE id = ?");
                    $updateResult = $updateQuery->execute([
                        $baslik,
                        $kisa_aciklama,
                        0, // marka_id (şimdilik 0)
                        (float)$eski_fiyat,
                        (float)$fiyat,
                        (float)$kdv,
                        (float)$kargo_fiyati,
                        $aciklama,
                        $productId
                    ]);
                } else {
                    $updateQuery = $db->prepare("UPDATE urun SET baslik = ?, kisa_aciklama = ?, stok = ?, marka_id = ?, eski_fiyat = ?, fiyat = ?, kdv = ?, kargo_fiyati = ?, aciklama = ? WHERE id = ?");
                    $updateResult = $updateQuery->execute([
                        $baslik,
                        $kisa_aciklama,
                        (int)$stok,
                        0, // marka_id (şimdilik 0)
                        (float)$eski_fiyat,
                        (float)$fiyat,
                        (float)$kdv,
                        (float)$kargo_fiyati,
                        $aciklama,
                        $productId
                    ]);
                }
            }
            
            if ($updateResult) {
                $this->syncListeFiyatFromEryazProduct((int)$productId, $product, $fiyat, $eski_fiyat, $db);
                // SEF güncelle
                if (function_exists('sef')) {
                    $sef = sef($baslik) . '-' . $productId;
                } else {
                    $sef = $this->createSef($baslik) . '-' . $productId;
                }
                $sefQuery = $db->prepare("UPDATE urun SET sef = ? WHERE id = ?");
                $sefQuery->execute([$sef, $productId]);
                
                // Kategoriyi ekle
                $this->addProductCategory($productId, $manufacturer, $db);
                
                return ['success' => true, 'product_id' => $productId, 'message' => 'Ürün güncellendi', 'action' => 'updated'];
            } else {
                return ['success' => false, 'product_id' => 0, 'message' => 'Ürün güncellenemedi'];
            }
        } else {
            // Yeni ürün ekle
            // Depo stok alanlarının varlığını kontrol et
            try {
                $checkColumns = $db->query("SHOW COLUMNS FROM urun LIKE 'maslak_stok'")->fetch();
                $hasWarehouseColumns = ($checkColumns !== false);
            } catch (Exception $e) {
                $hasWarehouseColumns = false;
            }
            
            if ($hasWarehouseColumns) {
                try {
                    // Herhangi bir depoda stok varsa genel stok durumu "Var" (1)
                    $genel_stok = ($maslak_stok == 1 || $bolu_stok == 1 || $imes_stok == 1 || $ankara_stok == 1 || $ikitelli_stok == 1) ? 1 : 0;
                    
                    $insertQuery = $db->prepare("INSERT INTO urun SET baslik = ?, sef = ?, kisa_aciklama = ?, stok_kodu = ?, stok = ?, marka_id = ?, eski_fiyat = ?, fiyat = ?, kdv = ?, kargo_fiyati = ?, aciklama = ?, maslak_stok = ?, bolu_stok = ?, imes_stok = ?, ankara_stok = ?, ikitelli_stok = ?");
                    $insertResult = $insertQuery->execute([
                        $baslik,
                        '', // SEF boş, sonra güncellenecek
                        $kisa_aciklama,
                        $stok_kodu,
                        $genel_stok,
                        0, // marka_id (şimdilik 0)
                        (float)$eski_fiyat,
                        (float)$fiyat,
                        (float)$kdv,
                        (float)$kargo_fiyati,
                        $aciklama,
                        $maslak_stok,
                        $bolu_stok,
                        $imes_stok,
                        $ankara_stok,
                        $ikitelli_stok
                    ]);
                } catch (Exception $e) {
                    // Sütunlar yoksa normal ekleme yap
                    $insertQuery = $db->prepare("INSERT INTO urun SET baslik = ?, sef = ?, kisa_aciklama = ?, stok_kodu = ?, stok = ?, marka_id = ?, eski_fiyat = ?, fiyat = ?, kdv = ?, kargo_fiyati = ?, aciklama = ?");
                    $insertResult = $insertQuery->execute([
                        $baslik,
                        '', // SEF boş, sonra güncellenecek
                        $kisa_aciklama,
                        $stok_kodu,
                        (int)$stok,
                        0, // marka_id (şimdilik 0)
                        (float)$eski_fiyat,
                        (float)$fiyat,
                        (float)$kdv,
                        (float)$kargo_fiyati,
                        $aciklama
                    ]);
                }
            } else {
                $insertQuery = $db->prepare("INSERT INTO urun SET baslik = ?, sef = ?, kisa_aciklama = ?, stok_kodu = ?, stok = ?, marka_id = ?, eski_fiyat = ?, fiyat = ?, kdv = ?, kargo_fiyati = ?, aciklama = ?");
                $insertResult = $insertQuery->execute([
                    $baslik,
                    '', // SEF boş, sonra güncellenecek
                    $kisa_aciklama,
                    $stok_kodu,
                    (int)$stok,
                    0, // marka_id (şimdilik 0)
                    (float)$eski_fiyat,
                    (float)$fiyat,
                    (float)$kdv,
                    (float)$kargo_fiyati,
                    $aciklama
                ]);
            }
            
            if (!$insertResult) {
                return ['success' => false, 'product_id' => 0, 'message' => 'Ürün eklenemedi'];
            }
            
            $productId = $db->lastInsertId();
            if ($this->ensureEryazStockCodeColumn($db)) {
                try {
                    $mapSt = $db->prepare('UPDATE urun SET stok_kodu = ?, eryaz_stok_kodu = ? WHERE id = ? LIMIT 1');
                    $mapSt->execute([$stok_kodu, $eryaz_stok_kodu, $productId]);
                } catch (Exception $e) {
                    // Esleme kolonu yoksa import devam etsin.
                }
            }
            
            // SEF güncelle
            if (function_exists('sef')) {
                $sef = sef($baslik) . '-' . $productId;
            } else {
                $sef = $this->createSef($baslik) . '-' . $productId;
            }
            $sefQuery = $db->prepare("UPDATE urun SET sef = ? WHERE id = ?");
            $sefQuery->execute([$sef, $productId]);
            
            $this->syncListeFiyatFromEryazProduct((int)$productId, $product, $fiyat, $eski_fiyat, $db);
            
            // Kategoriyi ekle
            $this->addProductCategory($productId, $manufacturer, $db);
            
            // Resimleri ekle
            $this->addProductImages($productId, $product, $db);
            
            return ['success' => true, 'product_id' => $productId, 'message' => 'Ürün eklendi', 'action' => 'inserted'];
        }
    }
    
    /**
     * Sitede stok kodu eşleşen ürünün sadece fiyat alanlarını Eryaz verisiyle günceller.
     * Başlık, açıklama, depo stokları ve genel stok alanlarına dokunulmaz.
     *
     * @param array $product Eryaz API ürün satırı
     * @param PDO $db
     * @return array success, product_id, message, action (price_updated | not_in_db | skipped | error)
     */
    public function updateProductPricesFromEryazOnly($product, $db) {
        if (!is_array($product) || empty($product)) {
            return ['success' => false, 'product_id' => 0, 'message' => 'Geçersiz ürün verisi', 'action' => 'error'];
        }
        
        $stok_kodu = $this->getProductField($product, ['StockCode', 'SKU', 'Code', 'StokKodu', 'stok_kodu', 'Barcode', 'Barkod']);
        if ($stok_kodu === '' || $stok_kodu === null) {
            return ['success' => false, 'product_id' => 0, 'message' => 'Stok kodu yok', 'action' => 'skipped'];
        }
        
        $existing = $this->findProductByEryazStockCode($db, $stok_kodu, 'id, eski_fiyat, fiyat, kdv, kargo_fiyati');
        if (!$existing) {
            return ['success' => false, 'product_id' => 0, 'message' => 'Sitede bu stok kodu yok', 'action' => 'not_in_db'];
        }
        
        $productId = (int)$existing['id'];
        $fiyat = $this->getProductField($product, ['Price', 'SalePrice', 'Fiyat', 'fiyat', 'SellingPrice'], 0);
        $eski_fiyat = $this->getProductField($product, ['OldPrice', 'ListPrice', 'EskiFiyat', 'eski_fiyat', 'RegularPrice', 'OriginalPrice'], 0);
        $kdv = $this->getProductField($product, ['VAT', 'KDV', 'kdv', 'Tax', 'TaxRate'], 0);
        $kargo_fiyati = $this->getProductField($product, ['ShippingPrice', 'KargoFiyati', 'kargo_fiyati', 'ShippingCost'], 0);

        // Eryaz bazı ürünlerde fiyat alanını boş/0 döndürebiliyor. Bu durumda mevcut
        // site fiyatını 0'lamak yerine koru; yalnızca pozitif gelen değerleri uygula.
        $fiyat = (float)str_replace(',', '.', (string)$fiyat);
        $eski_fiyat = (float)str_replace(',', '.', (string)$eski_fiyat);
        $kdv = (float)str_replace(',', '.', (string)$kdv);
        $kargo_fiyati = (float)str_replace(',', '.', (string)$kargo_fiyati);
        if ($fiyat <= 0 && isset($existing['fiyat']) && (float)$existing['fiyat'] > 0) {
            $fiyat = (float)$existing['fiyat'];
        }
        if ($eski_fiyat <= 0 && isset($existing['eski_fiyat']) && (float)$existing['eski_fiyat'] > 0) {
            $eski_fiyat = (float)$existing['eski_fiyat'];
        }
        if ($kdv <= 0 && isset($existing['kdv']) && (float)$existing['kdv'] > 0) {
            $kdv = (float)$existing['kdv'];
        }
        if ($kargo_fiyati <= 0 && isset($existing['kargo_fiyati']) && (float)$existing['kargo_fiyati'] > 0) {
            $kargo_fiyati = (float)$existing['kargo_fiyati'];
        }
        
        try {
            $updateQuery = $db->prepare("UPDATE urun SET eski_fiyat = ?, fiyat = ?, kdv = ?, kargo_fiyati = ? WHERE id = ?");
            $ok = $updateQuery->execute([
                $eski_fiyat,
                $fiyat,
                $kdv,
                $kargo_fiyati,
                $productId
            ]);
        } catch (Exception $e) {
            return ['success' => false, 'product_id' => $productId, 'message' => $e->getMessage(), 'action' => 'error'];
        }
        
        if (!$ok) {
            return ['success' => false, 'product_id' => $productId, 'message' => 'Fiyat güncellenemedi', 'action' => 'error'];
        }
        
        $this->syncListeFiyatFromEryazProduct($productId, $product, $fiyat, $eski_fiyat, $db);
        
        return ['success' => true, 'product_id' => $productId, 'message' => 'Fiyatlar güncellendi', 'action' => 'price_updated'];
    }
    
    /**
     * Eryaz'dan gelen fiyatları liste_fiyati_eur / TL ve türev alanlara yazar (panel ile uyumlu).
     * Daha önce sadece fiyat / eski_fiyat güncelleniyordu; mağaza ve ürünler listesi liste_fiyati_eur kullanıyor.
     */
    private function getBoschGroupDiscountByStockCode($stokKodu) {
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

    private function syncListeFiyatFromEryazProduct($productId, $product, $fiyat, $eski_fiyat, $db) {
        $productId = (int)$productId;
        if ($productId < 1 || !is_array($product)) {
            return;
        }
        try {
            $col = $db->query("SHOW COLUMNS FROM urun LIKE 'liste_fiyati_eur'")->fetch();
            if (!$col) {
                return;
            }
        } catch (Exception $e) {
            return;
        }
        $listeEuroRaw = $this->getProductField($product, [
            'ListPriceEUR', 'ListeFiyatEuro', 'EuroListPrice', 'ListPrice_Euro', 'PriceEUR',
            'ListeFiyat_EUR', 'EURListPrice', 'ListPriceInEuro', 'ListeEuro', 'liste_fiyati_eur',
        ], '');
        $listeEuro = 0.0;
        if ($listeEuroRaw !== '' && is_numeric(str_replace(',', '.', (string)$listeEuroRaw))) {
            $listeEuro = (float)str_replace(',', '.', (string)$listeEuroRaw);
        }
        if ($listeEuro <= 0) {
            $ef = (float)$eski_fiyat;
            $pf = (float)$fiyat;
            $listeEuro = $ef > 0 ? $ef : $pf;
        }
        if ($listeEuro <= 0) {
            try {
                $st = $db->prepare('SELECT liste_fiyati_eur, eski_fiyat, fiyat FROM urun WHERE id = ? LIMIT 1');
                $st->execute([$productId]);
                $row = $st->fetch(PDO::FETCH_ASSOC);
                if ($row) {
                    if (isset($row['liste_fiyati_eur']) && (float)$row['liste_fiyati_eur'] > 0) {
                        $listeEuro = (float)$row['liste_fiyati_eur'];
                    } elseif (isset($row['eski_fiyat']) && (float)$row['eski_fiyat'] > 0) {
                        $listeEuro = (float)$row['eski_fiyat'];
                    } elseif (isset($row['fiyat']) && (float)$row['fiyat'] > 0) {
                        $listeEuro = (float)$row['fiyat'];
                    }
                }
            } catch (Exception $e) {
                // Pozitif liste fiyatı bulunamazsa aşağıda güncelleme yapılmayacak.
            }
        }
        if ($listeEuro <= 0) {
            return;
        }
        $iskApi = $this->getProductField($product, [
            'DiscountRate', 'Discount', 'IskontoOrani', 'iskonto_orani', 'DiscountPercent', 'Iskonto', 'iskonto',
        ], '');
        $iskonto = 0.0;
        if ($iskApi !== '' && is_numeric(str_replace(',', '.', (string)$iskApi))) {
            $iskonto = (float)str_replace(',', '.', (string)$iskApi);
        } else {
            try {
                $st = $db->prepare('SELECT iskonto_orani FROM urun WHERE id = ? LIMIT 1');
                $st->execute([$productId]);
                $row = $st->fetch(PDO::FETCH_ASSOC);
                if ($row && isset($row['iskonto_orani']) && $row['iskonto_orani'] !== '' && $row['iskonto_orani'] !== null) {
                    $iskonto = (float)$row['iskonto_orani'];
                }
            } catch (Exception $e) {
                $iskonto = 0.0;
            }
        }
        if ($iskonto < 0) {
            $iskonto = 0.0;
        }
        if ($iskonto > 100) {
            $iskonto = 100.0;
        }
        $stokKodu = '';
        try {
            $hasEryazColumn = $this->ensureEryazStockCodeColumn($db);
            $st = $db->prepare($hasEryazColumn ? 'SELECT stok_kodu, eryaz_stok_kodu FROM urun WHERE id = ? LIMIT 1' : 'SELECT stok_kodu FROM urun WHERE id = ? LIMIT 1');
            $st->execute([$productId]);
            $row = $st->fetch(PDO::FETCH_ASSOC);
            if ($row && !empty($row['eryaz_stok_kodu'])) {
                $stokKodu = (string)$row['eryaz_stok_kodu'];
            } elseif ($row && isset($row['stok_kodu'])) {
                $stokKodu = (string)$row['stok_kodu'];
            }
        } catch (Exception $e) {
            $stokKodu = '';
        }
        if ($stokKodu === '') {
            $stokKodu = $this->getProductField($product, ['StockCode', 'SKU', 'Code', 'StokKodu', 'stok_kodu', 'Barcode', 'Barkod'], '');
        }
        $boschGroupDiscount = $this->getBoschGroupDiscountByStockCode($stokKodu);
        if ($boschGroupDiscount !== null) {
            $iskonto = $boschGroupDiscount;
        }
        $doviz = 0.0;
        $tcmbFile = __DIR__ . '/get-tcmb-euro-rate.php';
        if (is_file($tcmbFile) && !function_exists('getTCMBEuroRate')) {
            require_once $tcmbFile;
        }
        if (function_exists('getTCMBEuroRate')) {
            try {
                $dTmp = getTCMBEuroRate();
                if ($dTmp !== false && $dTmp !== null && (float)$dTmp > 0) {
                    $doviz = (float)$dTmp;
                }
            } catch (Exception $e) {
                // yedek kur aşağıda
            }
        }
        if ($doviz <= 0) {
            try {
                $st = $db->prepare('SELECT doviz_kuru FROM urun WHERE id = ? LIMIT 1');
                $st->execute([$productId]);
                $row = $st->fetch(PDO::FETCH_ASSOC);
                if ($row && isset($row['doviz_kuru']) && (float)$row['doviz_kuru'] > 0) {
                    $doviz = (float)$row['doviz_kuru'];
                }
            } catch (Exception $e) {
                // ignore
            }
        }
        if ($doviz <= 0) {
            $doviz = 35.0;
        }
        $liste_tl = $listeEuro * $doviz;
        $kdvsiz = $liste_tl * (1 - $iskonto / 100);
        $netkdv = $kdvsiz * 1.20;
        $kredi = $netkdv;
        $pesin = $netkdv * 0.95;
        try {
            $up = $db->prepare('UPDATE urun SET liste_fiyati_eur = ?, liste_fiyati_tl = ?, doviz_kuru = ?, iskonto_orani = ?, kredi_karti_fiyati = ?, pesin_odeme_fiyati = ? WHERE id = ?');
            $up->execute([$listeEuro, $liste_tl, $doviz, $iskonto, $kredi, $pesin, $productId]);
        } catch (Exception $e) {
            // Eksik kolon vb.
        }
    }
    
    /**
     * API ürün satırından stok kodunu döner (toplu silme / eşleştirme için).
     *
     * @param array $product Eryaz GetProductList satırı
     * @return string Boşsa eşleşme yoktur
     */
    public function extractStockCodeFromProduct(array $product) {
        $code = $this->getProductField($product, ['StockCode', 'SKU', 'Code', 'StokKodu', 'stok_kodu', 'Barcode', 'Barkod'], '');
        return is_string($code) ? trim($code) : '';
    }
    
    /**
     * Ürün alanını farklı isimlerle arar
     */
    private function getProductField($product, $fieldNames, $default = '') {
        if (!is_array($product)) {
            return $default;
        }
        
        foreach ($fieldNames as $fieldName) {
            // Büyük/küçük harf duyarsız arama
            foreach ($product as $key => $value) {
                if (strtolower(trim($key)) === strtolower(trim($fieldName))) {
                    $result = $value !== null && $value !== '' ? trim($value) : $default;
                    return $result;
                }
            }
        }
        return $default;
    }
    
    /**
     * Ürünü kategoriye bağlar
     */
    private function addProductCategory($productId, $manufacturer, $db) {
        if (empty($manufacturer)) {
            return;
        }
        
        // Manufacturer'dan kategori ID'sini al
        $categoryResult = $this->getOrCreateCategoryByManufacturer($manufacturer, $db);
        $categoryId = $categoryResult['id'];
        
        if ($categoryId) {
            // Ürün-kategori ilişkisini kontrol et
            $checkQuery = $db->prepare("SELECT id FROM urun_kategori WHERE urun_id = ? AND kategori_id = ? LIMIT 1");
            $checkQuery->execute([$productId, $categoryId]);
            $existing = $checkQuery->fetch(PDO::FETCH_ASSOC);
            
            if (!$existing) {
                // İlişki yoksa ekle
                $insertQuery = $db->prepare("INSERT INTO urun_kategori SET urun_id = ?, kategori_id = ?");
                $insertQuery->execute([$productId, $categoryId]);
            }
        }
    }
    
    /**
     * Ürün resimlerini ekler
     */
    private function addProductImages($productId, $product, $db) {
        // Resim alanlarını bul
        $imageFields = ['Image', 'Images', 'ImageUrl', 'ImageURL', 'Resim', 'resim', 'Picture', 'Pictures', 'Photo', 'Photos'];
        $images = [];
        
        foreach ($imageFields as $fieldName) {
            foreach ($product as $key => $value) {
                if (strtolower($key) === strtolower($fieldName)) {
                    if (is_array($value)) {
                        $images = array_merge($images, $value);
                    } else if (!empty($value)) {
                        $images[] = $value;
                    }
                    break;
                }
            }
        }
        
        // Resimleri ekle
        foreach ($images as $imageUrl) {
            if (!empty($imageUrl)) {
                // Mevcut resmi kontrol et
                $checkQuery = $db->prepare("SELECT id FROM urun_img WHERE urun_id = ? AND img = ? LIMIT 1");
                $checkQuery->execute([$productId, $imageUrl]);
                $existing = $checkQuery->fetch(PDO::FETCH_ASSOC);
                
                if (!$existing) {
                    $insertQuery = $db->prepare("INSERT INTO urun_img SET urun_id = ?, img = ?");
                    $insertQuery->execute([$productId, $imageUrl]);
                }
            }
        }
    }
}

// ============================================
// İSTEK TİPİNE GÖRE İŞLEM
// ============================================

// Eğer sadece sınıf dahil ediliyorsa (include/require), HTML çıktısını engelle
// Dosyanın doğrudan çağrılıp çağrılmadığını kontrol et
$currentScript = isset($_SERVER['SCRIPT_FILENAME']) ? basename($_SERVER['SCRIPT_FILENAME']) : '';
$requestUri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
$isDirectCall = ($currentScript == 'api-eryaz.php' || strpos($requestUri, 'api-eryaz.php') !== false);

if (!$isDirectCall) {
    // Sadece sınıf dahil ediliyor, çıktı yok - sınıf tanımlandı, devam etme
    return;
}

// AJAX isteği kontrolü
$isAjax = isset($_GET['ajax']) || isset($_POST['ajax']) || 
          (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest');

// API sınıfını başlat
$eryazAPI = new EryazAPI();

// AJAX isteği ise JSON döndür
if ($isAjax) {
    header('Content-Type: application/json; charset=utf-8');
    
    // CORS ayarları (gerekirse)
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    
    // Manufacturer'ları kategoriye dönüştürme işlemi
    if (isset($_GET['action']) && $_GET['action'] === 'createCategoriesFromManufacturers') {
        // Veritabanı bağlantısını dahil et
        $dbFile = __DIR__ . '/panel/db-ayar.php';
        if (!file_exists($dbFile)) {
            $dbFile = __DIR__ . '/db-ayar.php';
        }
        if (!file_exists($dbFile)) {
            // Panel klasörü dışında olabilir
            $dbFile = dirname(__DIR__) . '/panel/db-ayar.php';
        }
        if (file_exists($dbFile)) {
            require_once $dbFile;
        } else {
            echo json_encode([
                'success' => false,
                'error' => 'Veritabanı bağlantı dosyası bulunamadı. Aranan yollar: ' . __DIR__ . '/panel/db-ayar.php, ' . __DIR__ . '/db-ayar.php'
            ]);
            exit;
        }
        
        // sef fonksiyonunu dahil et
        $funcFile = __DIR__ . '/panel/fonksiyon.php';
        if (!file_exists($funcFile)) {
            $funcFile = dirname(__DIR__) . '/panel/fonksiyon.php';
        }
        if (file_exists($funcFile)) {
            require_once $funcFile;
        }
        
        // Ürünleri çek
        $result = $eryazAPI->getProductList(1, 1000);
        
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
        
        $categoriesCreated = [];
        $categoriesExisting = [];
        $errors = [];
        
        foreach ($products as $product) {
            if (!is_array($product) || isset($product['Error'])) {
                continue;
            }
            
            // Manufacturer alanını bul (büyük/küçük harf duyarsız)
            $manufacturer = null;
            foreach ($product as $key => $value) {
                if (strtolower($key) === 'manufacturer' && !empty($value)) {
                    $manufacturer = trim($value);
                    break;
                }
            }
            
            if (empty($manufacturer)) {
                continue;
            }
            
            // Kategori oluştur veya al
            $categoryResult = $eryazAPI->getOrCreateCategoryByManufacturer($manufacturer, $db);
            $categoryId = $categoryResult['id'];
            $isCreated = $categoryResult['created'];
            
            if ($categoryId) {
                if ($isCreated) {
                    // Yeni oluşturuldu
                    if (!isset($categoriesCreated[$manufacturer])) {
                        $categoriesCreated[$manufacturer] = $categoryId;
                    }
                } else {
                    // Zaten mevcuttu
                    if (!isset($categoriesExisting[$manufacturer])) {
                        $categoriesExisting[$manufacturer] = $categoryId;
                    }
                }
            } else {
                $errors[] = "Kategori oluşturulamadı: " . $manufacturer;
            }
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Kategoriler oluşturuldu',
            'created' => $categoriesCreated,
            'existing' => $categoriesExisting,
            'errors' => $errors,
            'total_created' => count($categoriesCreated),
            'total_existing' => count($categoriesExisting)
        ]);
        exit;
    }
    
    // Ürünleri veritabanına ekleme işlemi
    if (isset($_GET['action']) && $_GET['action'] === 'importProducts') {
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
            echo json_encode([
                'success' => false,
                'error' => 'Veritabanı bağlantı dosyası bulunamadı'
            ]);
            exit;
        }
        
        // sef fonksiyonunu dahil et
        $funcFile = __DIR__ . '/panel/fonksiyon.php';
        if (!file_exists($funcFile)) {
            $funcFile = dirname(__DIR__) . '/panel/fonksiyon.php';
        }
        if (file_exists($funcFile)) {
            require_once $funcFile;
        }
        
        // Ürünleri çek
        $start = isset($_GET['start']) ? (int)$_GET['start'] : 1;
        $end = isset($_GET['end']) ? (int)$_GET['end'] : 1000;
        
        // Maksimum limit
        if ($end > 1000) {
            $end = 1000;
        }
        
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
        
        $imported = 0;
        $updated = 0;
        $errors = [];
        $skipped = 0;
        
        foreach ($products as $product) {
            if (!is_array($product) || isset($product['Error'])) {
                $skipped++;
                continue;
            }
            
            // Ürünü veritabanına ekle
            $addResult = $eryazAPI->addProductToDatabase($product, $db);
            
            if ($addResult['success']) {
                if (isset($addResult['action']) && $addResult['action'] === 'updated') {
                    $updated++;
                } else {
                    $imported++;
                }
            } else {
                // Ürün adını bul
                $productName = 'Bilinmeyen Ürün';
                foreach (['ProductName', 'Name', 'Title', 'Baslik', 'UrunAdi', 'urun_adi'] as $field) {
                    foreach ($product as $key => $value) {
                        if (strtolower($key) === strtolower($field) && !empty($value)) {
                            $productName = $value;
                            break 2;
                        }
                    }
                }
                
                $errors[] = [
                    'product' => $productName,
                    'error' => $addResult['message']
                ];
            }
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Ürünler işlendi',
            'imported' => $imported,
            'updated' => $updated,
            'skipped' => $skipped,
            'total' => count($products),
            'errors' => $errors,
            'error_count' => count($errors)
        ]);
        exit;
    }
    
    // Mevcut sitedeki ürünlerin fiyatlarını Eryaz ile güncelle (aynı stok kodu; başlık/stok vb. değişmez)
    if (isset($_GET['action']) && $_GET['action'] === 'updatePricesFromEryaz') {
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
            echo json_encode([
                'success' => false,
                'error' => 'Veritabanı bağlantı dosyası bulunamadı'
            ]);
            exit;
        }
        
        $funcFile = __DIR__ . '/panel/fonksiyon.php';
        if (!file_exists($funcFile)) {
            $funcFile = dirname(__DIR__) . '/panel/fonksiyon.php';
        }
        if (file_exists($funcFile)) {
            require_once $funcFile;
        }
        
        $start = isset($_GET['start']) ? (int)$_GET['start'] : 1;
        $end = isset($_GET['end']) ? (int)$_GET['end'] : 1000;
        if ($end > 1000) {
            $end = 1000;
        }
        
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
        
        $price_updated = 0;
        $skipped = 0;
        $not_in_db = 0;
        $errors = [];
        
        foreach ($products as $product) {
            if (!is_array($product) || isset($product['Error'])) {
                $skipped++;
                continue;
            }
            
            $res = $eryazAPI->updateProductPricesFromEryazOnly($product, $db);
            $act = $res['action'] ?? '';
            if (!empty($res['success']) && $act === 'price_updated') {
                $price_updated++;
            } elseif ($act === 'not_in_db') {
                $not_in_db++;
            } elseif ($act === 'skipped') {
                $skipped++;
            } elseif ($act === 'error') {
                $errors[] = ['message' => $res['message'] ?? 'Hata'];
            } else {
                $skipped++;
            }
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Fiyat güncelleme tamamlandı',
            'price_updated' => $price_updated,
            'not_in_db' => $not_in_db,
            'skipped' => $skipped,
            'total_from_api' => count($products),
            'errors' => $errors,
            'error_count' => count($errors),
            'range' => ['start' => $start, 'end' => $end]
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // OPTIONS isteği için
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit;
    }
    
    // İstek tipine göre işlem yap
    $action = $_GET['action'] ?? $_POST['action'] ?? 'getProductList';
    
    $response = ['success' => false, 'message' => '', 'data' => null];
    
    try {
        switch ($action) {
            case 'getProductList':
                $start = isset($_POST['start']) ? (int)$_POST['start'] : (isset($_GET['start']) ? (int)$_GET['start'] : 1);
                $end = isset($_POST['end']) ? (int)$_POST['end'] : (isset($_GET['end']) ? (int)$_GET['end'] : 1000);
                
                $result = $eryazAPI->getProductList($start, $end);
                
                if ($result['success']) {
                    $response = [
                        'success' => true,
                        'message' => 'Ürün listesi başarıyla çekildi',
                        'data' => $result['data'],
                        'count' => is_array($result['data']) ? count($result['data']) : 0
                    ];
                } else {
                    $response = [
                        'success' => false,
                        'message' => $result['error'] ?? 'Bilinmeyen hata',
                        'error' => $result
                    ];
                }
                break;
                
            case 'customFunction':
                // Özel fonksiyon çağrısı için
                $functionName = $_POST['functionName'] ?? $_GET['functionName'] ?? '';
                $parameters = $_POST['parameters'] ?? $_GET['parameters'] ?? [];
                
                if (empty($functionName)) {
                    $response['message'] = 'Fonksiyon adı belirtilmedi';
                    break;
                }
                
                // Eğer parameters string ise JSON decode et
                if (is_string($parameters)) {
                    $parameters = json_decode($parameters, true);
                }
                
                $result = $eryazAPI->getData($functionName, $parameters);
                
                if ($result['success']) {
                    $response = [
                        'success' => true,
                        'message' => 'API çağrısı başarılı',
                        'data' => $result['data']
                    ];
                } else {
                    $response = [
                        'success' => false,
                        'message' => $result['error'] ?? 'Bilinmeyen hata',
                        'error' => $result
                    ];
                }
                break;
                
            default:
                $response['message'] = 'Geçersiz action parametresi';
                break;
        }
    } catch (Exception $e) {
        $response = [
            'success' => false,
            'message' => 'Hata: ' . $e->getMessage()
        ];
    }
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

// ============================================
// HTML GÖRÜNTÜLEME SAYFASI
// ============================================

// Ürün listesini çek
$start = isset($_GET['start']) ? (int)$_GET['start'] : 1;
$end = isset($_GET['end']) ? (int)$_GET['end'] : 1000;
$result = $eryazAPI->getProductList($start, $end);

// Sayfa başlığı (eğer index.php içinde kullanılıyorsa)
if (!defined('IN_INDEX')) {
    $_title = "Eryaz Ürünler";
    $_description = "Eryaz API'den çekilen ürün listesi";
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title><?php echo $_title ?? 'Eryaz API Ürünler'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .container {
            margin-top: 30px;
            margin-bottom: 30px;
        }
        pre {
            max-height: 400px;
            overflow-y: auto;
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="row">
            <div class="col-12">
                <h1 class="mb-4">Eryaz API Ürün Listesi</h1>
                
                <!-- Parametre Formu -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Arama Parametreleri</h5>
                    </div>
                    <div class="card-body">
                        <form method="GET" action="" class="row">
                            <div class="col-md-4">
                                <label>Başlangıç:</label>
                                <input type="number" name="start" class="form-control" value="<?php echo $start; ?>" min="1">
                            </div>
                            <div class="col-md-4">
                                <label>Bitiş:</label>
                                <input type="number" name="end" class="form-control" value="<?php echo $end; ?>" min="1">
                            </div>
                            <div class="col-md-4">
                                <label>&nbsp;</label><br>
                                <button type="submit" class="btn btn-primary">Yenile</button>
                                <button type="button" class="btn btn-secondary" onclick="loadViaAjax()">AJAX ile Yükle</button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <?php if ($result['success']): ?>
                    <?php
                    $products = $result['data'];
                    
                    // Eğer data bir array ise ve içinde veri varsa
                    if (is_array($products) && !empty($products)):
                    ?>
                        <div class="alert alert-success">
                            <strong>Başarılı!</strong> <?php echo count($products); ?> ürün bulundu.
                        </div>
                        
                        <!-- API Yanıtını Görüntüle (Debug için) -->
                        <div class="mb-4">
                            <button class="btn btn-info" type="button" data-toggle="collapse" data-target="#apiResponse" aria-expanded="false">
                                API Yanıtını Görüntüle
                            </button>
                            <div class="collapse mt-3" id="apiResponse">
                                <div class="card card-body">
                                    <pre><?php echo htmlspecialchars(json_encode($products, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)); ?></pre>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Ürün Listesi Tablosu -->
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered">
                                <thead class="thead-dark">
                                    <tr>
                                        <th>#</th>
                                        <?php
                                        // İlk ürünün key'lerini başlık olarak kullan
                                        $firstProduct = reset($products);
                                        if (is_array($firstProduct)):
                                            foreach (array_keys($firstProduct) as $key):
                                                echo '<th>' . htmlspecialchars($key) . '</th>';
                                            endforeach;
                                        endif;
                                        ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $counter = 1;
                                    foreach ($products as $product):
                                        if (is_array($product)):
                                    ?>
                                        <tr>
                                            <td><?php echo $counter++; ?></td>
                                            <?php foreach ($product as $value): ?>
                                                <td><?php echo htmlspecialchars(is_array($value) ? json_encode($value) : $value); ?></td>
                                            <?php endforeach; ?>
                                        </tr>
                                    <?php
                                        endif;
                                    endforeach;
                                    ?>
                                </tbody>
                            </table>
                        </div>
                        
                    <?php else: ?>
                        <div class="alert alert-warning">
                            <strong>Uyarı!</strong> API yanıtı boş veya beklenmeyen formatta.
                            <pre><?php print_r($products); ?></pre>
                        </div>
                    <?php endif; ?>
                    
                <?php else: ?>
                    <div class="alert alert-danger">
                        <strong>Hata!</strong> API çağrısı başarısız oldu.
                        <p><strong>Hata Mesajı:</strong> <?php echo htmlspecialchars($result['error'] ?? 'Bilinmeyen hata'); ?></p>
                        <?php if (isset($result['raw_response'])): ?>
                            <details>
                                <summary>Ham Yanıt</summary>
                                <pre><?php echo htmlspecialchars($result['raw_response']); ?></pre>
                            </details>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function loadViaAjax() {
            const start = document.querySelector('input[name="start"]').value;
            const end = document.querySelector('input[name="end"]').value;
            
            fetch('api-eryaz.php?ajax=1&action=getProductList&start=' + start + '&end=' + end)
                .then(response => response.json())
                .then(data => {
                    console.log('AJAX Yanıt:', data);
                    alert('AJAX ile veri çekildi! Konsolu kontrol edin. Ürün sayısı: ' + (data.count || 0));
                })
                .catch(error => {
                    console.error('Hata:', error);
                    alert('AJAX hatası: ' + error.message);
                });
        }
    </script>
</body>
</html>
