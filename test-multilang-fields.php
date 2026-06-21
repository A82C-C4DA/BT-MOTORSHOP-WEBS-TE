<?php
/**
 * Çok Dilli Alan Kontrolü
 * Veritabanında baslik_en ve baslik_ru alanlarının varlığını kontrol eder
 */

require_once __DIR__ . '/panel/db-ayar.php';

echo "<h2>Çok Dilli Alan Kontrolü</h2>";

// 1. Tablo yapısını kontrol et
echo "<h3>1. Tablo Yapısı:</h3>";
try {
    $columns = $db->query("SHOW COLUMNS FROM urun LIKE 'baslik%'")->fetchAll(PDO::FETCH_ASSOC);
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Alan Adı</th><th>Tip</th><th>Null</th><th>Varsayılan</th></tr>";
    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($col['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Default'] ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} catch (Exception $e) {
    echo "<p style='color:red;'>HATA: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// 2. Örnek ürünleri kontrol et
echo "<h3>2. Örnek Ürünler (İlk 5):</h3>";
try {
    $products = $db->query("SELECT id, baslik, baslik_en, baslik_ru FROM urun LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Baslik (TR)</th><th>Baslik (EN)</th><th>Baslik (RU)</th></tr>";
    foreach ($products as $product) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($product['id']) . "</td>";
        echo "<td>" . htmlspecialchars($product['baslik'] ?? 'NULL') . "</td>";
        echo "<td>" . htmlspecialchars($product['baslik_en'] ?? 'NULL') . " " . 
             (empty($product['baslik_en']) ? '<span style="color:red;">[BOŞ]</span>' : '') . "</td>";
        echo "<td>" . htmlspecialchars($product['baslik_ru'] ?? 'NULL') . " " . 
             (empty($product['baslik_ru']) ? '<span style="color:red;">[BOŞ]</span>' : '') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} catch (Exception $e) {
    echo "<p style='color:red;'>HATA: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// 3. Dolu alan sayısını kontrol et
echo "<h3>3. İstatistikler:</h3>";
try {
    $stats = $db->query("
        SELECT 
            COUNT(*) as toplam,
            COUNT(baslik_en) as en_dolu,
            COUNT(baslik_ru) as ru_dolu,
            SUM(CASE WHEN baslik_en IS NULL OR baslik_en = '' THEN 1 ELSE 0 END) as en_bos,
            SUM(CASE WHEN baslik_ru IS NULL OR baslik_ru = '' THEN 1 ELSE 0 END) as ru_bos
        FROM urun
    ")->fetch(PDO::FETCH_ASSOC);
    
    echo "<ul>";
    echo "<li>Toplam Ürün: " . $stats['toplam'] . "</li>";
    echo "<li>İngilizce Başlık Dolu: " . $stats['en_dolu'] . "</li>";
    echo "<li>Rusça Başlık Dolu: " . $stats['ru_dolu'] . "</li>";
    echo "<li>İngilizce Başlık Boş: " . $stats['en_bos'] . "</li>";
    echo "<li>Rusça Başlık Boş: " . $stats['ru_bos'] . "</li>";
    echo "</ul>";
} catch (Exception $e) {
    echo "<p style='color:red;'>HATA: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<hr>";
echo "<p><strong>Not:</strong> Eğer alanlar boşsa, admin panelinden ürünleri düzenleyip bu alanları doldurmanız gerekir.</p>";
?>

