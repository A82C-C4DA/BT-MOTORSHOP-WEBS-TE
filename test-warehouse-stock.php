<?php
/**
 * Depo Stok Bilgilerini Test Et
 * API'den gelen veriyi kontrol etmek için
 */

require_once __DIR__ . '/panel/db-ayar.php';
require_once __DIR__ . '/api-eryaz.php';

// Türkiye saat dilimini ayarla
date_default_timezone_set('Europe/Istanbul');

echo "<h2>Depo Stok Bilgileri Test</h2>";

try {
    // Eryaz API'den birkaç ürün çek
    $eryazAPI = new EryazAPI();
    $result = $eryazAPI->getProductList(1, 5);
    
    if (!$result || !$result['success']) {
        echo "<p style='color:red;'>HATA: Ürünler çekilemedi: " . ($result['error'] ?? 'Bilinmeyen hata') . "</p>";
        exit;
    }
    
    $products = $result['data']['Data'] ?? $result['data'] ?? [];
    if (!is_array($products)) {
        $products = [];
    }
    
    echo "<p><strong>" . count($products) . " ürün bulundu.</strong></p>";
    echo "<hr>";
    
    foreach ($products as $index => $product) {
        if (!is_array($product)) {
            continue;
        }
        
        echo "<h3>Ürün #" . ($index + 1) . "</h3>";
        echo "<p><strong>Ürün Adı:</strong> " . ($product['ProductName'] ?? $product['Name'] ?? 'Bilinmiyor') . "</p>";
        echo "<p><strong>Stok Kodu:</strong> " . ($product['Code'] ?? $product['StockCode'] ?? 'Bilinmiyor') . "</p>";
        
        // Tüm field'ları göster
        echo "<h4>Tüm Field'lar:</h4>";
        echo "<pre style='background:#f5f5f5;padding:10px;overflow:auto;max-height:300px;'>";
        print_r($product);
        echo "</pre>";
        
        // Depo stok bilgilerini kontrol et
        echo "<h4>Depo Stok Bilgileri:</h4>";
        $warehouseFields = [
            'Maslak_Status', 'maslak_status', 'MaslakStatus', 'Maslak_Stok', 'maslak_stok',
            'Bolu_Status', 'bolu_status', 'BoluStatus', 'Bolu_Stok', 'bolu_stok',
            'İmes_Status', 'imes_status', 'İmesStatus', 'Imes_Status', 'imesStatus',
            'Ankara_Status', 'ankara_status', 'AnkaraStatus', 'Ankara_Stok', 'ankara_stok',
            'İkitelli_Status', 'ikitelli_status', 'İkitelliStatus', 'Ikitelli_Status', 'ikitelliStatus'
        ];
        
        echo "<ul>";
        foreach ($warehouseFields as $field) {
            if (isset($product[$field])) {
                echo "<li><strong>{$field}:</strong> " . htmlspecialchars($product[$field]) . "</li>";
            }
        }
        echo "</ul>";
        
        // API'den çekilen değerleri göster
        $maslak_status = $eryazAPI->getProductField($product, ['Maslak_Status', 'maslak_status', 'MaslakStatus'], 'Yok');
        $bolu_status = $eryazAPI->getProductField($product, ['Bolu_Status', 'bolu_status', 'BoluStatus'], 'Yok');
        $imes_status = $eryazAPI->getProductField($product, ['İmes_Status', 'imes_status', 'İmesStatus', 'Imes_Status', 'imesStatus'], 'Yok');
        $ankara_status = $eryazAPI->getProductField($product, ['Ankara_Status', 'ankara_status', 'AnkaraStatus'], 'Yok');
        $ikitelli_status = $eryazAPI->getProductField($product, ['İkitelli_Status', 'ikitelli_status', 'İkitelliStatus', 'Ikitelli_Status', 'ikitelliStatus'], 'Yok');
        
        echo "<h4>Çekilen Değerler:</h4>";
        echo "<ul>";
        echo "<li><strong>Maslak:</strong> " . htmlspecialchars($maslak_status) . " → " . ((strtolower(trim($maslak_status)) === 'var') ? '1' : '0') . "</li>";
        echo "<li><strong>Bolu:</strong> " . htmlspecialchars($bolu_status) . " → " . ((strtolower(trim($bolu_status)) === 'var') ? '1' : '0') . "</li>";
        echo "<li><strong>İmes:</strong> " . htmlspecialchars($imes_status) . " → " . ((strtolower(trim($imes_status)) === 'var') ? '1' : '0') . "</li>";
        echo "<li><strong>Ankara:</strong> " . htmlspecialchars($ankara_status) . " → " . ((strtolower(trim($ankara_status)) === 'var') ? '1' : '0') . "</li>";
        echo "<li><strong>İkitelli:</strong> " . htmlspecialchars($ikitelli_status) . " → " . ((strtolower(trim($ikitelli_status)) === 'var') ? '1' : '0') . "</li>";
        echo "</ul>";
        
        echo "<hr>";
        
        // Sadece ilk 3 ürünü göster
        if ($index >= 2) {
            break;
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color:red;'>KRİTİK HATA: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>

