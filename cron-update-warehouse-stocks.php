<?php
/**
 * Depo Stok Durumlarını Güncelleme Cron Job
 * Her gece 00:00'da çalışacak şekilde ayarlanmalı
 * 
 * Cron Job Ayarları (cPanel):
 * 0 0 * * * /usr/bin/php /home/username/public_html/cron-update-warehouse-stocks.php
 * 
 * Veya manuel çalıştırma:
 * php cron-update-warehouse-stocks.php
 */

// Veritabanı bağlantısını dahil et
require_once __DIR__ . '/panel/db-ayar.php';

// Eryaz API sınıfını dahil et
require_once __DIR__ . '/api-eryaz.php';

// Türkiye saat dilimini ayarla
date_default_timezone_set('Europe/Istanbul');

echo "[" . date('Y-m-d H:i:s') . "] Depo stok güncelleme işlemi başlatılıyor...\n";

try {
    // Eryaz API'den tüm ürünleri çek
    $eryazAPI = new EryazAPI();
    
    // Tüm ürünleri çek (maksimum 50000)
    $result = $eryazAPI->getProductList(1, 50000);
    
    if (!$result || !$result['success']) {
        echo "[" . date('Y-m-d H:i:s') . "] HATA: Ürünler çekilemedi: " . ($result['error'] ?? 'Bilinmeyen hata') . "\n";
        exit(1);
    }
    
    $products = $result['data']['Data'] ?? $result['data'] ?? [];
    if (!is_array($products)) {
        $products = [];
    }
    
    echo "[" . date('Y-m-d H:i:s') . "] " . count($products) . " ürün bulundu.\n";
    
    // Depo stok sütunlarının varlığını kontrol et
    try {
        $checkColumns = $db->query("SHOW COLUMNS FROM urun LIKE 'maslak_stok'")->fetch();
        $hasWarehouseColumns = ($checkColumns !== false);
    } catch (Exception $e) {
        $hasWarehouseColumns = false;
        echo "[" . date('Y-m-d H:i:s') . "] UYARI: Depo stok sütunları bulunamadı. SQL scriptini çalıştırmanız gerekebilir.\n";
    }
    
    if (!$hasWarehouseColumns) {
        echo "[" . date('Y-m-d H:i:s') . "] HATA: Depo stok sütunları mevcut değil. add_warehouse_columns.sql dosyasını çalıştırın.\n";
        exit(1);
    }
    
    $updated = 0;
    $notFound = 0;
    $errors = 0;
    
    // Her ürün için stok bilgilerini güncelle
    foreach ($products as $product) {
        if (!is_array($product) || !isset($product['Code']) || empty($product['Code'])) {
            continue;
        }
        
        $stokKodu = $product['Code'];
        
        // Depo stok durumlarını al - tüm olası field isimlerini kontrol et
        $maslak = getStatusValue($product, [
            'Maslak_Status', 'maslak_status', 'MaslakStatus', 'Maslak_Stok', 'maslak_stok',
            'MaslakStatus', 'MASLAK_STATUS', 'MASLAK_STOK', 'Maslak', 'maslak'
        ]);
        $bolu = getStatusValue($product, [
            'Bolu_Status', 'bolu_status', 'BoluStatus', 'Bolu_Stok', 'bolu_stok',
            'BoluStatus', 'BOLU_STATUS', 'BOLU_STOK', 'Bolu', 'bolu'
        ]);
        $imes = getStatusValue($product, [
            'İmes_Status', 'imes_status', 'İmesStatus', 'Imes_Status', 'imesStatus',
            'İmes_Stok', 'imes_stok', 'İmesStok', 'Imes_Stok', 'imesStok',
            'İMES_STATUS', 'İMES_STOK', 'İmes', 'imes'
        ]);
        $ankara = getStatusValue($product, [
            'Ankara_Status', 'ankara_status', 'AnkaraStatus', 'Ankara_Stok', 'ankara_stok',
            'AnkaraStatus', 'ANKARA_STATUS', 'ANKARA_STOK', 'Ankara', 'ankara'
        ]);
        $ikitelli = getStatusValue($product, [
            'İkitelli_Status', 'ikitelli_status', 'İkitelliStatus', 'Ikitelli_Status', 'ikitelliStatus',
            'İkitelli_Stok', 'ikitelli_stok', 'İkitelliStok', 'Ikitelli_Stok', 'ikitelliStok',
            'İKİTELLİ_STATUS', 'İKİTELLİ_STOK', 'İkitelli', 'ikitelli'
        ]);
        
        // Veritabanında bu Eryaz stok koduna sahip ürünü bul.
        // Bosch prefixleri stok_kodu alanından temizlenmiş olabilir; orijinal kod eryaz_stok_kodu'nda saklanır.
        try {
            $hasEryazStockCode = $db->query("SHOW COLUMNS FROM urun LIKE 'eryaz_stok_kodu'")->fetch() !== false;
        } catch (Exception $e) {
            $hasEryazStockCode = false;
        }
        if ($hasEryazStockCode) {
            $cleanStokKodu = preg_replace('/^(30-|31-|32-|3e-?)/i', '', trim((string)$stokKodu));
            $query = $db->prepare("SELECT id FROM urun WHERE eryaz_stok_kodu = ? OR stok_kodu = ? OR stok_kodu = ? LIMIT 1");
            $query->execute([$stokKodu, $stokKodu, $cleanStokKodu]);
        } else {
            $query = $db->prepare("SELECT id FROM urun WHERE stok_kodu = ? LIMIT 1");
            $query->execute([$stokKodu]);
        }
        $urun = $query->fetch(PDO::FETCH_ASSOC);
        
        if ($urun) {
            // Önce stok_manuel değerini kontrol et
            $manuelQuery = $db->prepare("SELECT stok_manuel FROM urun WHERE id = ? LIMIT 1");
            $manuelQuery->execute([$urun['id']]);
            $manuelResult = $manuelQuery->fetch(PDO::FETCH_ASSOC);
            $stokManuel = isset($manuelResult['stok_manuel']) ? (int)$manuelResult['stok_manuel'] : 0;
            
            // Eğer stok_manuel = 1 ise, bu ürünün stok değerini değiştirme (admin panelinden manuel ayarlanmış)
            if ($stokManuel == 1) {
                // Sadece depo stok bilgilerini güncelle, genel stok değerini değiştirme
                try {
                    $updateQuery = $db->prepare("
                        UPDATE urun SET 
                            maslak_stok = ?,
                            bolu_stok = ?,
                            imes_stok = ?,
                            ankara_stok = ?,
                            ikitelli_stok = ?
                        WHERE id = ?
                    ");
                    $updateQuery->execute([
                        $maslak,
                        $bolu,
                        $imes,
                        $ankara,
                        $ikitelli,
                        $urun['id']
                    ]);
                    $updated++;
                    // echo "[" . date('Y-m-d H:i:s') . "] Ürün (ID: {$urun['id']}) manuel stok, sadece depo bilgileri güncellendi.\n";
                } catch (Exception $e) {
                    echo "[" . date('Y-m-d H:i:s') . "] HATA (ID: {$urun['id']}): " . $e->getMessage() . "\n";
                    $errors++;
                }
            } else {
                // stok_manuel = 0 ise, Eryaz'dan gelen stok bilgisini kullan
                // Herhangi bir depoda stok varsa genel stok durumu "Var" (1)
                $genel_stok = ($maslak == 1 || $bolu == 1 || $imes == 1 || $ankara == 1 || $ikitelli == 1) ? 1 : 0;
                
                try {
                    $updateQuery = $db->prepare("
                        UPDATE urun SET 
                            maslak_stok = ?,
                            bolu_stok = ?,
                            imes_stok = ?,
                            ankara_stok = ?,
                            ikitelli_stok = ?,
                            stok = ?
                        WHERE id = ?
                    ");
                    $updateQuery->execute([
                        $maslak,
                        $bolu,
                        $imes,
                        $ankara,
                        $ikitelli,
                        $genel_stok,
                        $urun['id']
                    ]);
                    $updated++;
                } catch (Exception $e) {
                    echo "[" . date('Y-m-d H:i:s') . "] HATA (ID: {$urun['id']}): " . $e->getMessage() . "\n";
                    $errors++;
                }
            }
        } else {
            $notFound++;
        }
    }
    
    echo "[" . date('Y-m-d H:i:s') . "] İşlem tamamlandı!\n";
    echo "[" . date('Y-m-d H:i:s') . "] Güncellenen: {$updated}\n";
    echo "[" . date('Y-m-d H:i:s') . "] Bulunamayan: {$notFound}\n";
    echo "[" . date('Y-m-d H:i:s') . "] Hatalar: {$errors}\n";
    
} catch (Exception $e) {
    echo "[" . date('Y-m-d H:i:s') . "] KRİTİK HATA: " . $e->getMessage() . "\n";
    exit(1);
}

/**
 * Status değerini al ve 1/0'a çevir
 */
function getStatusValue($product, $fieldNames) {
    if (!is_array($product)) {
        return 0;
    }
    
    $value = null;
    
    // Field names array olarak geliyor
    if (!is_array($fieldNames)) {
        $fieldNames = [$fieldNames];
    }
    
    foreach ($fieldNames as $fieldName) {
        // Büyük/küçük harf duyarsız arama
        foreach ($product as $key => $val) {
            if (strtolower(trim($key)) === strtolower(trim($fieldName))) {
                $value = $val;
                break 2;
            }
        }
    }
    
    // Var/Yok değerini 1/0'a çevir
    if ($value !== null && $value !== '') {
        $value = strtolower(trim($value));
        return ($value === 'var') ? 1 : 0;
    }
    
    return 0;
}
?>

