<?php
/**
 * TCMB Euro Efektif Satış Kuru Çekme Fonksiyonu
 * TCMB EVDS API kullanarak güncel Euro efektif satış kurunu çeker
 */

function getTCMBEuroRate() {
    // TCMB EVDS API URL
    // Euro efektif satış kuru için seri kodu: TP.DK.EUR.S
    $apiUrl = 'https://evds2.tcmb.gov.tr/service/evds/series=TP.DK.EUR.S&startDate=' . date('d-m-Y') . '&endDate=' . date('d-m-Y') . '&type=json&key=YOUR_API_KEY';
    
    // Alternatif: TCMB'nin XML servisi (API key gerektirmez ama daha yavaş)
    $xmlUrl = 'https://www.tcmb.gov.tr/kurlar/today.xml';
    
    // Önce XML'den çekmeyi deneyelim (API key gerektirmez)
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $xmlUrl,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
    ]);
    
    $response = @curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200 && $response) {
        // XML'i parse et
        libxml_use_internal_errors(true);
        $xml = @simplexml_load_string($response);
        
        if ($xml !== false) {
            // Euro'yu bul (Currency="EUR")
            foreach ($xml->Currency as $currency) {
                $attributes = $currency->attributes();
                if (isset($attributes['CurrencyCode']) && (string)$attributes['CurrencyCode'] === 'EUR') {
                    // Efektif satış kuru (BanknoteSelling)
                    $rate = (string)$currency->BanknoteSelling;
                    if (!empty($rate) && is_numeric($rate)) {
                        return (float)$rate;
                    }
                }
            }
        }
    }
    
    // Eğer XML'den çekilemezse, varsayılan bir değer döndür (veya cache'den)
    // Gerçek uygulamada burada bir cache mekanizması olmalı
    return false;
}

// Test için
if (php_sapi_name() === 'cli' || isset($_GET['test'])) {
    $rate = getTCMBEuroRate();
    if ($rate) {
        echo "Euro Efektif Satış Kuru: " . number_format($rate, 4, ',', '.') . " TL\n";
    } else {
        echo "Kur çekilemedi. Lütfen TCMB sitesini kontrol edin.\n";
    }
}

