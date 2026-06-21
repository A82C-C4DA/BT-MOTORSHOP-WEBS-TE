<?php
/**
 * TecDoc API Yapılandırma Dosyası (RapidAPI)
 * 
 * Bu dosyayı kopyalayıp 'tecdoc-config.php' olarak kaydedin
 * ve kendi RapidAPI anahtarınızı girin
 * 
 * RapidAPI anahtarınızı almak için:
 * 1. https://rapidapi.com adresine gidin
 * 2. Hesap oluşturun veya giriş yapın
 * 3. "TecDoc Catalog" API'sini bulun
 * 4. Subscribe olun ve API key'inizi alın
 */

return [
    // RapidAPI anahtarı (ZORUNLU)
    'rapidApiKey' => 'YOUR_RAPIDAPI_KEY_HERE',
    
    // RapidAPI host (genelde değiştirmenize gerek yok)
    'rapidApiHost' => 'tecdoc-catalog.p.rapidapi.com',
    
    // Dil ID (2 = Türkçe, 1 = İngilizce, 3 = Almanca, vb.)
    'languageId' => 2
];
