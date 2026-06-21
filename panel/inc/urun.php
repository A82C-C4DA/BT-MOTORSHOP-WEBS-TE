<?php
if($_POST){
    
    // STOK DEĞERİNİ DOĞRUDAN POST'TAN AL - EN BASİT YOL
    // Select name="stok" olduğu için $_POST['stok'] direkt değeri içerir
    $stok_post_value = isset($_POST['stok']) ? $_POST['stok'] : '0';
    $eskiUrunResimleri = [];
    
    // Debug
    error_log("=== URUN.PHP STOK DEBUG ===");
    error_log("POST stok değeri: " . var_export($stok_post_value, true));
    error_log("POST stok tipi: " . gettype($stok_post_value));
    
    if(isset($_GET['duzenle_id'])){
        // Depo stok sütunlarının varlığını kontrol et
        try {
            $checkColumns = $db->query("SHOW COLUMNS FROM urun LIKE 'maslak_stok'")->fetch();
            $hasWarehouseColumns = ($checkColumns !== false);
        } catch (Exception $e) {
            $hasWarehouseColumns = false;
        }
        
        // STOK DEĞERİNİ DOĞRUDAN POST'TAN AL - EN BASİT YOL
        // "1" ise 1, değilse 0
        $stok_degeri = ($stok_post_value === '1' || $stok_post_value === 1) ? 1 : 0;
        
        // Debug - Tarayıcıya da göster
        echo '<script>console.log("PHP UPDATE - stok_post_value: ' . addslashes(var_export($stok_post_value, true)) . ' -> stok_degeri: ' . $stok_degeri . '");</script>';
        error_log("UPDATE - stok_post_value: " . var_export($stok_post_value, true) . " -> stok_degeri: " . $stok_degeri);
        
        // TCMB'den Euro efektif satış kurunu çek
        require_once __DIR__ . '/../../get-tcmb-euro-rate.php';
        $doviz_kuru = getTCMBEuroRate();
        if(!$doviz_kuru || $doviz_kuru <= 0){
            // Eğer çekilemezse, mevcut değeri kullan veya varsayılan bir değer
            $mevcut_urun = $db->query("SELECT doviz_kuru FROM urun WHERE id = '{$_GET['duzenle_id']}' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
            $doviz_kuru = isset($mevcut_urun['doviz_kuru']) && $mevcut_urun['doviz_kuru'] > 0 ? (float)$mevcut_urun['doviz_kuru'] : 35.00;
        }
        
        // Fiyatlandırma alanlarını hazırla
        $liste_fiyati_eur = isset($_POST['liste_fiyati_eur']) ? (float)$_POST['liste_fiyati_eur'] : 0;
        $iskonto_orani = isset($_POST['iskonto_orani']) ? (float)$_POST['iskonto_orani'] : 0;
        
        // Otomatik hesaplamalar
        $liste_fiyati_tl = $liste_fiyati_eur * $doviz_kuru; // Liste Fiyatı TL = Liste Fiyatı Euro × Döviz Kuru
        $kdv_orani = 20; // KDV her zaman %20
        $kdvsiz_net_fiyat = $liste_fiyati_tl * (1 - $iskonto_orani / 100); // KDV'siz Net Fiyat = Liste Fiyatı TL × (1 - İskonto Oranı)
        $net_fiyat_kdv_dahil = $kdvsiz_net_fiyat * 1.20; // Net Fiyat KDV Dahil = KDV'siz Net Fiyat × 1.20
        
        // Kredi Kartı ile Fiyat = Net Fiyat KDV Dahil
        $kredi_karti_fiyati = $net_fiyat_kdv_dahil;
        
        // Peşin Ödeme ile Fiyat = Net Fiyat KDV Dahil × 0.95 (%5 az)
        $pesin_odeme_fiyati = $net_fiyat_kdv_dahil * 0.95;
        
        // Fiyatlandırma sütunlarının varlığını kontrol et
        try {
            $checkPricingColumns = $db->query("SHOW COLUMNS FROM urun LIKE 'liste_fiyati_eur'")->fetch();
            $hasPricingColumns = ($checkPricingColumns !== false);
        } catch (Exception $e) {
            $hasPricingColumns = false;
        }
        
        // Eski fiyat alanları artık formda yok, varsayılan değerler kullan
        $eski_fiyat = isset($_POST['eski_fiyat']) ? $_POST['eski_fiyat'] : '';
        $fiyat = isset($_POST['fiyat']) ? $_POST['fiyat'] : '';
        $kargo_fiyati = isset($_POST['kargo_fiyati']) ? $_POST['kargo_fiyati'] : '';
        
        // Stok değerini integer olarak garanti et
        $stok_degeri = (int)$stok_degeri;
        if($stok_degeri != 0 && $stok_degeri != 1){
            $stok_degeri = 0;
        }
        
        // Debug: SQL sorgusuna gönderilecek stok değerini logla
        error_log("UPDATE - SQL'e gönderilecek stok_degeri: " . $stok_degeri . " (Type: " . gettype($stok_degeri) . ")");
        
        // stok_manuel kolonunun varlığını kontrol et
        try {
            $checkStokManuel = $db->query("SHOW COLUMNS FROM urun LIKE 'stok_manuel'")->fetch();
            $hasStokManuelColumn = ($checkStokManuel !== false);
        } catch (Exception $e) {
            $hasStokManuelColumn = false;
        }
        
        if($hasPricingColumns){
            // KDV her zaman %20
            $kdv_degeri = 20;
            
            // Çok dilli alanları hazırla
            $baslik_en = isset($_POST['baslik_en']) ? trim($_POST['baslik_en']) : '';
            $baslik_ru = isset($_POST['baslik_ru']) ? trim($_POST['baslik_ru']) : '';
            $baslik_fr = isset($_POST['baslik_fr']) ? trim($_POST['baslik_fr']) : '';
            $baslik_es = isset($_POST['baslik_es']) ? trim($_POST['baslik_es']) : '';
            $baslik_ar = isset($_POST['baslik_ar']) ? trim($_POST['baslik_ar']) : '';
            $baslik_pl = isset($_POST['baslik_pl']) ? trim($_POST['baslik_pl']) : '';
            $kisa_aciklama_en = isset($_POST['kisa_aciklama_en']) ? trim($_POST['kisa_aciklama_en']) : '';
            $kisa_aciklama_ru = isset($_POST['kisa_aciklama_ru']) ? trim($_POST['kisa_aciklama_ru']) : '';
            $kisa_aciklama_fr = isset($_POST['kisa_aciklama_fr']) ? trim($_POST['kisa_aciklama_fr']) : '';
            $kisa_aciklama_es = isset($_POST['kisa_aciklama_es']) ? trim($_POST['kisa_aciklama_es']) : '';
            $kisa_aciklama_ar = isset($_POST['kisa_aciklama_ar']) ? trim($_POST['kisa_aciklama_ar']) : '';
            $kisa_aciklama_pl = isset($_POST['kisa_aciklama_pl']) ? trim($_POST['kisa_aciklama_pl']) : '';
            $aciklama = isset($_POST['aciklama']) ? trim($_POST['aciklama']) : '';
            $aciklama_en = isset($_POST['aciklama_en']) ? trim($_POST['aciklama_en']) : '';
            $aciklama_ru = isset($_POST['aciklama_ru']) ? trim($_POST['aciklama_ru']) : '';
            $aciklama_fr = isset($_POST['aciklama_fr']) ? trim($_POST['aciklama_fr']) : '';
            $aciklama_es = isset($_POST['aciklama_es']) ? trim($_POST['aciklama_es']) : '';
            $aciklama_ar = isset($_POST['aciklama_ar']) ? trim($_POST['aciklama_ar']) : '';
            $aciklama_pl = isset($_POST['aciklama_pl']) ? trim($_POST['aciklama_pl']) : '';
            
            if($hasStokManuelColumn){
                // stok_manuel = 1 ekle - Bu sayede Eryaz cron job bu ürünün stok değerini override etmeyecek
                $islem = $db->prepare("UPDATE urun SET baslik = ?, baslik_en = ?, baslik_ru = ?, baslik_fr = ?, baslik_es = ?, baslik_ar = ?, baslik_pl = ?, sef = ?, kisa_aciklama = ?, kisa_aciklama_en = ?, kisa_aciklama_ru = ?, kisa_aciklama_fr = ?, kisa_aciklama_es = ?, kisa_aciklama_ar = ?, kisa_aciklama_pl = ?, aciklama = ?, aciklama_en = ?, aciklama_ru = ?, aciklama_fr = ?, aciklama_es = ?, aciklama_ar = ?, aciklama_pl = ?, stok_kodu = ?, stok = ?, stok_manuel = 1, marka_id = ?, eski_fiyat = ?, fiyat = ?, kdv = ?, kargo_fiyati = ?, liste_fiyati_eur = ?, liste_fiyati_tl = ?, iskonto_orani = ?, doviz_kuru = ?, kredi_karti_fiyati = ?, pesin_odeme_fiyati = ? WHERE id = ?");
                $islem = $islem->execute(array($_POST['baslik'], $baslik_en, $baslik_ru, $baslik_fr, $baslik_es, $baslik_ar, $baslik_pl, '', $_POST['kisa_aciklama'], $kisa_aciklama_en, $kisa_aciklama_ru, $kisa_aciklama_fr, $kisa_aciklama_es, $kisa_aciklama_ar, $kisa_aciklama_pl, $aciklama, $aciklama_en, $aciklama_ru, $aciklama_fr, $aciklama_es, $aciklama_ar, $aciklama_pl, $_POST['stok_kodu'], $stok_degeri, $_POST['marka_id'], $eski_fiyat, $fiyat, $kdv_degeri, $kargo_fiyati, $liste_fiyati_eur, $liste_fiyati_tl, $iskonto_orani, $doviz_kuru, $kredi_karti_fiyati, $pesin_odeme_fiyati, $_GET['duzenle_id']));
            } else {
                // stok_manuel kolonu yok, eski sorguyu kullan
                $islem = $db->prepare("UPDATE urun SET baslik = ?, baslik_en = ?, baslik_ru = ?, baslik_fr = ?, baslik_es = ?, baslik_ar = ?, baslik_pl = ?, sef = ?, kisa_aciklama = ?, kisa_aciklama_en = ?, kisa_aciklama_ru = ?, kisa_aciklama_fr = ?, kisa_aciklama_es = ?, kisa_aciklama_ar = ?, kisa_aciklama_pl = ?, aciklama = ?, aciklama_en = ?, aciklama_ru = ?, aciklama_fr = ?, aciklama_es = ?, aciklama_ar = ?, aciklama_pl = ?, stok_kodu = ?, stok = ?, marka_id = ?, eski_fiyat = ?, fiyat = ?, kdv = ?, kargo_fiyati = ?, liste_fiyati_eur = ?, liste_fiyati_tl = ?, iskonto_orani = ?, doviz_kuru = ?, kredi_karti_fiyati = ?, pesin_odeme_fiyati = ? WHERE id = ?");
                $islem = $islem->execute(array($_POST['baslik'], $baslik_en, $baslik_ru, $baslik_fr, $baslik_es, $baslik_ar, $baslik_pl, '', $_POST['kisa_aciklama'], $kisa_aciklama_en, $kisa_aciklama_ru, $kisa_aciklama_fr, $kisa_aciklama_es, $kisa_aciklama_ar, $kisa_aciklama_pl, $aciklama, $aciklama_en, $aciklama_ru, $aciklama_fr, $aciklama_es, $aciklama_ar, $aciklama_pl, $_POST['stok_kodu'], $stok_degeri, $_POST['marka_id'], $eski_fiyat, $fiyat, $kdv_degeri, $kargo_fiyati, $liste_fiyati_eur, $liste_fiyati_tl, $iskonto_orani, $doviz_kuru, $kredi_karti_fiyati, $pesin_odeme_fiyati, $_GET['duzenle_id']));
            }
            
            // UPDATE işleminin başarılı olup olmadığını kontrol et
            if(!$islem){
                $errorInfo = $db->errorInfo();
                error_log("UPDATE hatası: " . (isset($errorInfo[2]) ? $errorInfo[2] : 'Bilinmeyen hata'));
            } else {
                error_log("UPDATE başarılı - Stok değeri: " . $stok_degeri);
                
                // Stok değerinin gerçekten kaydedildiğini doğrula
                $verifyQuery = $db->prepare("SELECT stok FROM urun WHERE id = ? LIMIT 1");
                $verifyQuery->execute(array($_GET['duzenle_id']));
                $verifyResult = $verifyQuery->fetch(PDO::FETCH_ASSOC);
                error_log("UPDATE doğrulama - Veritabanındaki stok değeri: " . (isset($verifyResult['stok']) ? $verifyResult['stok'] : 'BULUNAMADI'));
                
                // Eğer stok değeri yanlış kaydedilmişse tekrar güncelle
                if(isset($verifyResult['stok']) && (int)$verifyResult['stok'] != $stok_degeri){
                    error_log("UYARI: Stok değeri yanlış kaydedilmiş! Düzeltiliyor...");
                    $fixQuery = $db->prepare("UPDATE urun SET stok = ? WHERE id = ?");
                    $fixQuery->execute(array($stok_degeri, $_GET['duzenle_id']));
                    error_log("Stok değeri düzeltildi: " . $stok_degeri);
                }
            }
        } else {
            // KDV her zaman %20
            $kdv_degeri = 20;
            
            // Çok dilli alanları hazırla
            $baslik_en = isset($_POST['baslik_en']) ? trim($_POST['baslik_en']) : '';
            $baslik_ru = isset($_POST['baslik_ru']) ? trim($_POST['baslik_ru']) : '';
            $baslik_fr = isset($_POST['baslik_fr']) ? trim($_POST['baslik_fr']) : '';
            $baslik_es = isset($_POST['baslik_es']) ? trim($_POST['baslik_es']) : '';
            $baslik_ar = isset($_POST['baslik_ar']) ? trim($_POST['baslik_ar']) : '';
            $baslik_pl = isset($_POST['baslik_pl']) ? trim($_POST['baslik_pl']) : '';
            $kisa_aciklama_en = isset($_POST['kisa_aciklama_en']) ? trim($_POST['kisa_aciklama_en']) : '';
            $kisa_aciklama_ru = isset($_POST['kisa_aciklama_ru']) ? trim($_POST['kisa_aciklama_ru']) : '';
            $kisa_aciklama_fr = isset($_POST['kisa_aciklama_fr']) ? trim($_POST['kisa_aciklama_fr']) : '';
            $kisa_aciklama_es = isset($_POST['kisa_aciklama_es']) ? trim($_POST['kisa_aciklama_es']) : '';
            $kisa_aciklama_ar = isset($_POST['kisa_aciklama_ar']) ? trim($_POST['kisa_aciklama_ar']) : '';
            $kisa_aciklama_pl = isset($_POST['kisa_aciklama_pl']) ? trim($_POST['kisa_aciklama_pl']) : '';
            $aciklama = isset($_POST['aciklama']) ? trim($_POST['aciklama']) : '';
            $aciklama_en = isset($_POST['aciklama_en']) ? trim($_POST['aciklama_en']) : '';
            $aciklama_ru = isset($_POST['aciklama_ru']) ? trim($_POST['aciklama_ru']) : '';
            $aciklama_fr = isset($_POST['aciklama_fr']) ? trim($_POST['aciklama_fr']) : '';
            $aciklama_es = isset($_POST['aciklama_es']) ? trim($_POST['aciklama_es']) : '';
            $aciklama_ar = isset($_POST['aciklama_ar']) ? trim($_POST['aciklama_ar']) : '';
            $aciklama_pl = isset($_POST['aciklama_pl']) ? trim($_POST['aciklama_pl']) : '';
            
            if($hasStokManuelColumn){
                // stok_manuel = 1 ekle - Bu sayede Eryaz cron job bu ürünün stok değerini override etmeyecek
                $islem = $db->prepare("UPDATE urun SET baslik = ?, baslik_en = ?, baslik_ru = ?, baslik_fr = ?, baslik_es = ?, baslik_ar = ?, baslik_pl = ?, sef = ?, kisa_aciklama = ?, kisa_aciklama_en = ?, kisa_aciklama_ru = ?, kisa_aciklama_fr = ?, kisa_aciklama_es = ?, kisa_aciklama_ar = ?, kisa_aciklama_pl = ?, aciklama = ?, aciklama_en = ?, aciklama_ru = ?, aciklama_fr = ?, aciklama_es = ?, aciklama_ar = ?, aciklama_pl = ?, stok_kodu = ?, stok = ?, stok_manuel = 1, marka_id = ?, eski_fiyat = ?, fiyat = ?, kdv = ?, kargo_fiyati = ? WHERE id = ?");
                $islem = $islem->execute(array($_POST['baslik'], $baslik_en, $baslik_ru, $baslik_fr, $baslik_es, $baslik_ar, $baslik_pl, '', $_POST['kisa_aciklama'], $kisa_aciklama_en, $kisa_aciklama_ru, $kisa_aciklama_fr, $kisa_aciklama_es, $kisa_aciklama_ar, $kisa_aciklama_pl, $aciklama, $aciklama_en, $aciklama_ru, $aciklama_fr, $aciklama_es, $aciklama_ar, $aciklama_pl, $_POST['stok_kodu'], $stok_degeri, $_POST['marka_id'], $eski_fiyat, $fiyat, $kdv_degeri, $kargo_fiyati, $_GET['duzenle_id']));
            } else {
                // stok_manuel kolonu yok, eski sorguyu kullan
                $islem = $db->prepare("UPDATE urun SET baslik = ?, baslik_en = ?, baslik_ru = ?, baslik_fr = ?, baslik_es = ?, baslik_ar = ?, baslik_pl = ?, sef = ?, kisa_aciklama = ?, kisa_aciklama_en = ?, kisa_aciklama_ru = ?, kisa_aciklama_fr = ?, kisa_aciklama_es = ?, kisa_aciklama_ar = ?, kisa_aciklama_pl = ?, aciklama = ?, aciklama_en = ?, aciklama_ru = ?, aciklama_fr = ?, aciklama_es = ?, aciklama_ar = ?, aciklama_pl = ?, stok_kodu = ?, stok = ?, marka_id = ?, eski_fiyat = ?, fiyat = ?, kdv = ?, kargo_fiyati = ? WHERE id = ?");
                $islem = $islem->execute(array($_POST['baslik'], $baslik_en, $baslik_ru, $baslik_fr, $baslik_es, $baslik_ar, $baslik_pl, '', $_POST['kisa_aciklama'], $kisa_aciklama_en, $kisa_aciklama_ru, $kisa_aciklama_fr, $kisa_aciklama_es, $kisa_aciklama_ar, $kisa_aciklama_pl, $aciklama, $aciklama_en, $aciklama_ru, $aciklama_fr, $aciklama_es, $aciklama_ar, $aciklama_pl, $_POST['stok_kodu'], $stok_degeri, $_POST['marka_id'], $eski_fiyat, $fiyat, $kdv_degeri, $kargo_fiyati, $_GET['duzenle_id']));
            }
            
            // UPDATE işleminin başarılı olup olmadığını kontrol et
            if(!$islem){
                $errorInfo = $db->errorInfo();
                error_log("UPDATE hatası: " . (isset($errorInfo[2]) ? $errorInfo[2] : 'Bilinmeyen hata'));
            } else {
                error_log("UPDATE başarılı - Stok değeri: " . $stok_degeri);
                
                // Stok değerinin gerçekten kaydedildiğini doğrula
                $verifyQuery = $db->prepare("SELECT stok FROM urun WHERE id = ? LIMIT 1");
                $verifyQuery->execute(array($_GET['duzenle_id']));
                $verifyResult = $verifyQuery->fetch(PDO::FETCH_ASSOC);
                error_log("UPDATE doğrulama - Veritabanındaki stok değeri: " . (isset($verifyResult['stok']) ? $verifyResult['stok'] : 'BULUNAMADI'));
                
                // Eğer stok değeri yanlış kaydedilmişse tekrar güncelle
                if(isset($verifyResult['stok']) && (int)$verifyResult['stok'] != $stok_degeri){
                    error_log("UYARI: Stok değeri yanlış kaydedilmiş! Düzeltiliyor...");
                    $fixQuery = $db->prepare("UPDATE urun SET stok = ? WHERE id = ?");
                    $fixQuery->execute(array($stok_degeri, $_GET['duzenle_id']));
                    error_log("Stok değeri düzeltildi: " . $stok_degeri);
                }
            }
        }


        $id = $_GET['duzenle_id'];
        
        // Stok değerini tekrar güncelle (Eryaz veya başka bir işlem üzerine yazmış olabilir)
        $stok_fix_query = $db->prepare("UPDATE urun SET stok = ? WHERE id = ?");
        $stok_fix_query->execute(array($stok_degeri, $id));
        error_log("Stok değeri tekrar güncellendi (ID: {$id}): " . $stok_degeri);
        
        $eskiImgQ = $db->prepare("SELECT img FROM urun_img WHERE urun_id = ?");
        $eskiImgQ->execute([$id]);
        $eskiUrunResimleri = $eskiImgQ->fetchAll(PDO::FETCH_COLUMN);

        $delete = $db->exec("DELETE FROM urun_kategori WHERE urun_id = '{$id}' ");
        $delete = $db->exec("DELETE FROM urun_renk WHERE urun_id = '{$id}' ");
        $delete = $db->exec("DELETE FROM urun_img WHERE urun_id = '{$id}' ");
        $query = $db->query("SELECT * FROM urun_secenek WHERE urun_id = '{$id}'", PDO::FETCH_ASSOC);
		if($query->rowCount()){
			foreach( $query as $row ){
				$delete = $db->exec("DELETE FROM urun_secenek_alt WHERE urun_secenek_id = '{$row['id']}'");
				$delete = $db->exec("DELETE FROM urun_secenek WHERE id = '{$row['id']}'");
			}
		}


    }else{
        // INSERT - STOK DEĞERİNİ DOĞRUDAN POST'TAN AL
        // "1" ise 1, değilse 0
        $stok_degeri = ($stok_post_value === '1' || $stok_post_value === 1) ? 1 : 0;
        
        // Debug
        error_log("INSERT - stok_post_value: " . var_export($stok_post_value, true) . " -> stok_degeri: " . $stok_degeri);
        
        // TCMB'den Euro efektif satış kurunu çek
        require_once __DIR__ . '/../../get-tcmb-euro-rate.php';
        $doviz_kuru = getTCMBEuroRate();
        if(!$doviz_kuru || $doviz_kuru <= 0){
            // Eğer çekilemezse varsayılan bir değer
            $doviz_kuru = 35.00;
        }
        
        // Fiyatlandırma alanlarını hazırla
        $liste_fiyati_eur = isset($_POST['liste_fiyati_eur']) ? (float)$_POST['liste_fiyati_eur'] : 0;
        $iskonto_orani = isset($_POST['iskonto_orani']) ? (float)$_POST['iskonto_orani'] : 0;
        
        // Otomatik hesaplamalar
        $liste_fiyati_tl = $liste_fiyati_eur * $doviz_kuru; // Liste Fiyatı TL = Liste Fiyatı Euro × Döviz Kuru
        $kdv_orani = 20; // KDV her zaman %20
        $kdvsiz_net_fiyat = $liste_fiyati_tl * (1 - $iskonto_orani / 100); // KDV'siz Net Fiyat = Liste Fiyatı TL × (1 - İskonto Oranı)
        $net_fiyat_kdv_dahil = $kdvsiz_net_fiyat * 1.20; // Net Fiyat KDV Dahil = KDV'siz Net Fiyat × 1.20
        
        // Kredi Kartı ile Fiyat = Net Fiyat KDV Dahil
        $kredi_karti_fiyati = $net_fiyat_kdv_dahil;
        
        // Peşin Ödeme ile Fiyat = Net Fiyat KDV Dahil × 0.95 (%5 az)
        $pesin_odeme_fiyati = $net_fiyat_kdv_dahil * 0.95;
        
        // Fiyatlandırma sütunlarının varlığını kontrol et
        try {
            $checkPricingColumns = $db->query("SHOW COLUMNS FROM urun LIKE 'liste_fiyati_eur'")->fetch();
            $hasPricingColumns = ($checkPricingColumns !== false);
        } catch (Exception $e) {
            $hasPricingColumns = false;
        }
        
        // Eski fiyat alanları artık formda yok, varsayılan değerler kullan
        $eski_fiyat = isset($_POST['eski_fiyat']) ? $_POST['eski_fiyat'] : '';
        $fiyat = isset($_POST['fiyat']) ? $_POST['fiyat'] : '';
        $kargo_fiyati = isset($_POST['kargo_fiyati']) ? $_POST['kargo_fiyati'] : '';
        
        // Çok dilli alanları hazırla
        $baslik_en = isset($_POST['baslik_en']) ? trim($_POST['baslik_en']) : '';
        $baslik_ru = isset($_POST['baslik_ru']) ? trim($_POST['baslik_ru']) : '';
        $baslik_fr = isset($_POST['baslik_fr']) ? trim($_POST['baslik_fr']) : '';
        $baslik_es = isset($_POST['baslik_es']) ? trim($_POST['baslik_es']) : '';
        $baslik_ar = isset($_POST['baslik_ar']) ? trim($_POST['baslik_ar']) : '';
        $baslik_pl = isset($_POST['baslik_pl']) ? trim($_POST['baslik_pl']) : '';
        $kisa_aciklama_en = isset($_POST['kisa_aciklama_en']) ? trim($_POST['kisa_aciklama_en']) : '';
        $kisa_aciklama_ru = isset($_POST['kisa_aciklama_ru']) ? trim($_POST['kisa_aciklama_ru']) : '';
        $kisa_aciklama_fr = isset($_POST['kisa_aciklama_fr']) ? trim($_POST['kisa_aciklama_fr']) : '';
        $kisa_aciklama_es = isset($_POST['kisa_aciklama_es']) ? trim($_POST['kisa_aciklama_es']) : '';
        $kisa_aciklama_ar = isset($_POST['kisa_aciklama_ar']) ? trim($_POST['kisa_aciklama_ar']) : '';
        $kisa_aciklama_pl = isset($_POST['kisa_aciklama_pl']) ? trim($_POST['kisa_aciklama_pl']) : '';
        $aciklama = isset($_POST['aciklama']) ? trim($_POST['aciklama']) : '';
        $aciklama_en = isset($_POST['aciklama_en']) ? trim($_POST['aciklama_en']) : '';
        $aciklama_ru = isset($_POST['aciklama_ru']) ? trim($_POST['aciklama_ru']) : '';
        $aciklama_fr = isset($_POST['aciklama_fr']) ? trim($_POST['aciklama_fr']) : '';
        $aciklama_es = isset($_POST['aciklama_es']) ? trim($_POST['aciklama_es']) : '';
        $aciklama_ar = isset($_POST['aciklama_ar']) ? trim($_POST['aciklama_ar']) : '';
        $aciklama_pl = isset($_POST['aciklama_pl']) ? trim($_POST['aciklama_pl']) : '';
        
        if($hasPricingColumns){
            // KDV her zaman %20
            $kdv_degeri = 20;
            $stmt = $db->prepare("INSERT INTO urun SET baslik = ?, baslik_en = ?, baslik_ru = ?, baslik_fr = ?, baslik_es = ?, baslik_ar = ?, baslik_pl = ?, sef = ?, kisa_aciklama = ?, kisa_aciklama_en = ?, kisa_aciklama_ru = ?, kisa_aciklama_fr = ?, kisa_aciklama_es = ?, kisa_aciklama_ar = ?, kisa_aciklama_pl = ?, aciklama = ?, aciklama_en = ?, aciklama_ru = ?, aciklama_fr = ?, aciklama_es = ?, aciklama_ar = ?, aciklama_pl = ?, stok_kodu = ?, stok = ?, marka_id = ?, eski_fiyat = ?, fiyat = ?, kdv = ?, kargo_fiyati = ?, liste_fiyati_eur = ?, liste_fiyati_tl = ?, iskonto_orani = ?, doviz_kuru = ?, kredi_karti_fiyati = ?, pesin_odeme_fiyati = ?");
            $islem = $stmt->execute(array($_POST['baslik'], $baslik_en, $baslik_ru, $baslik_fr, $baslik_es, $baslik_ar, $baslik_pl, '', $_POST['kisa_aciklama'], $kisa_aciklama_en, $kisa_aciklama_ru, $kisa_aciklama_fr, $kisa_aciklama_es, $kisa_aciklama_ar, $kisa_aciklama_pl, $aciklama, $aciklama_en, $aciklama_ru, $aciklama_fr, $aciklama_es, $aciklama_ar, $aciklama_pl, $_POST['stok_kodu'], $stok_degeri, $_POST['marka_id'], $eski_fiyat, $fiyat, $kdv_degeri, $kargo_fiyati, $liste_fiyati_eur, $liste_fiyati_tl, $iskonto_orani, $doviz_kuru, $kredi_karti_fiyati, $pesin_odeme_fiyati));
        } else {
            // KDV her zaman %20
            $kdv_degeri = 20;
            $stmt = $db->prepare("INSERT INTO urun SET baslik = ?, baslik_en = ?, baslik_ru = ?, baslik_fr = ?, baslik_es = ?, baslik_ar = ?, baslik_pl = ?, sef = ?, kisa_aciklama = ?, kisa_aciklama_en = ?, kisa_aciklama_ru = ?, kisa_aciklama_fr = ?, kisa_aciklama_es = ?, kisa_aciklama_ar = ?, kisa_aciklama_pl = ?, aciklama = ?, aciklama_en = ?, aciklama_ru = ?, aciklama_fr = ?, aciklama_es = ?, aciklama_ar = ?, aciklama_pl = ?, stok_kodu = ?, stok = ?, marka_id = ?, eski_fiyat = ?, fiyat = ?, kdv = ?, kargo_fiyati = ?");
            $islem = $stmt->execute(array($_POST['baslik'], $baslik_en, $baslik_ru, $baslik_fr, $baslik_es, $baslik_ar, $baslik_pl, '', $_POST['kisa_aciklama'], $kisa_aciklama_en, $kisa_aciklama_ru, $kisa_aciklama_fr, $kisa_aciklama_es, $kisa_aciklama_ar, $kisa_aciklama_pl, $aciklama, $aciklama_en, $aciklama_ru, $aciklama_fr, $aciklama_es, $aciklama_ar, $aciklama_pl, $_POST['stok_kodu'], $stok_degeri, $_POST['marka_id'], $eski_fiyat, $fiyat, $kdv_degeri, $kargo_fiyati));
        }
        
        // INSERT işleminin başarılı olup olmadığını kontrol et
        if(!$islem){
            $errorInfo = $stmt->errorInfo();
            $errorMsg = isset($errorInfo[2]) ? $errorInfo[2] : 'Bilinmeyen hata';
            echo '<script>alert("Ürün ekleme hatası: ' . addslashes($errorMsg) . '");</script>';
            exit; // İşlemi durdur
        }
        
        $id = $db->lastInsertId();
        
        // Yeni urun en onde gorunsun (sira=1, digerleri bir kaydir)
        try {
            $checkSiraIns = $db->query("SHOW COLUMNS FROM urun LIKE 'sira'")->fetch();
            if ($checkSiraIns) {
                $shiftSira = $db->prepare("UPDATE urun SET sira = sira + 1 WHERE id != ?");
                $shiftSira->execute([$id]);
                $setSira = $db->prepare("UPDATE urun SET sira = 1 WHERE id = ?");
                $setSira->execute([$id]);
            }
        } catch (Exception $e) {
        }
        
        // ID'nin doğru alındığını kontrol et
        if(!$id || $id <= 0){
            echo '<script>alert("Ürün ID alınamadı! Lütfen veritabanı bağlantısını kontrol edin.");</script>';
            exit; // İşlemi durdur
        }
        
        // Stok değerini tekrar güncelle (Eryaz veya başka bir işlem üzerine yazmış olabilir)
        $stok_fix_query = $db->prepare("UPDATE urun SET stok = ? WHERE id = ?");
        $stok_fix_query->execute(array($stok_degeri, $id));
        error_log("INSERT - Stok değeri tekrar güncellendi (ID: {$id}): " . $stok_degeri);
        
        // Stok değerinin gerçekten kaydedildiğini doğrula
        $verifyQuery = $db->prepare("SELECT stok FROM urun WHERE id = ? LIMIT 1");
        $verifyQuery->execute(array($id));
        $verifyResult = $verifyQuery->fetch(PDO::FETCH_ASSOC);
        error_log("INSERT doğrulama - Veritabanındaki stok değeri: " . (isset($verifyResult['stok']) ? $verifyResult['stok'] : 'BULUNAMADI'));
        
        // Eğer stok değeri hala yanlışsa tekrar düzelt
        if(isset($verifyResult['stok']) && (int)$verifyResult['stok'] != $stok_degeri){
            error_log("UYARI: INSERT sonrası stok değeri yanlış! Tekrar düzeltiliyor...");
            $fixQuery = $db->prepare("UPDATE urun SET stok = ? WHERE id = ?");
            $fixQuery->execute(array($stok_degeri, $id));
            error_log("INSERT - Stok değeri tekrar düzeltildi: " . $stok_degeri);
        }
    }

    // Referans numaralarını kaydet
    if(isset($_POST['referans_no']) && is_array($_POST['referans_no'])){
        $urun_id = isset($_GET['duzenle_id']) ? $_GET['duzenle_id'] : $id;
        
        // Mevcut referansları sil
        try {
            $db->exec("DELETE FROM urun_referans WHERE urun_id = '{$urun_id}'");
        } catch (Exception $e) {
            // Tablo yoksa hata verme
        }
        
        // Yeni referansları ekle
        try {
            foreach($_POST['referans_no'] as $index => $referans_no){
                if(!empty(trim($referans_no))){
                    $marka_adi = isset($_POST['referans_marka'][$index]) ? trim($_POST['referans_marka'][$index]) : '';
                    $referans_no = trim($referans_no);
                    
                    $islem = $db->prepare("INSERT INTO urun_referans SET urun_id = ?, marka_adi = ?, referans_no = ?, sira = ?");
                    $islem->execute(array($urun_id, $marka_adi, $referans_no, $index));
                }
            }
        } catch (Exception $e) {
            // Tablo yoksa hata verme
        }
    }

    if(isset($_POST['renk_urun_id'])){
    	foreach ($_POST['renk_urun_id'] as $renk_urun_id) {
    		$islem = $db->prepare("INSERT INTO urun_renk SET urun_id = ?, renk_urun_id = ?");
        	$islem = $islem->execute(array($id,$renk_urun_id));
    	}
    }


    if(isset($_POST['img'])){
    	foreach ($_POST['img'] as $img) {
    		$islem = $db->prepare("INSERT INTO urun_img SET urun_id = ?, img = ?");
        	$islem = $islem->execute(array($id,$img));
    	}
    }
    if (!empty($eskiUrunResimleri)) {
        foreach ($eskiUrunResimleri as $eskiImg) {
            urun_resim_dosya_sil_if_orphan($db, $eskiImg);
        }
    }

    $i = 0;
    if(isset($_POST['secenek_adi'])){
    	foreach ($_POST['secenek_adi'] as $s) {
    		$islem = $db->prepare("INSERT INTO urun_secenek SET urun_id = ?, baslik = ?");
        	$islem = $islem->execute(array($id,$s));
        	$secenek_id = $db->lastInsertId();

        	$ii = 0;
        	if(isset($_POST['alt_secenek_adi'.$i])){
        		foreach ($_POST['alt_secenek_adi'.$i] as $as) {
        			$islem = $db->prepare("INSERT INTO urun_secenek_alt SET urun_secenek_id = ?, baslik = ?, stok = ?, fiyat = ?");
        			$islem = $islem->execute(array($secenek_id,$as,$_POST['alt_secenek_stok'.$i][$ii],$_POST['alt_secenek_fiyat'.$i][$ii]));
        			$ii++;
        		}
        	}

        	$i++;
    	}
    }


    // Kategori kaydetme işlemi
    $kategori_basarili = true;
    $kategori_eklendi = false;
    if(isset($_POST['kategori']) && is_array($_POST['kategori']) && count($_POST['kategori']) > 0){
		foreach ($_POST['kategori'] as $k) {
			if(!empty($k)){
				$kategori_stmt = $db->prepare("INSERT INTO urun_kategori SET urun_id = ?, kategori_id = ?");
        		$kategori_islem = $kategori_stmt->execute(array($id,$k));
        		if(!$kategori_islem){
        		    $kategori_basarili = false;
        		    $errorInfo = $kategori_stmt->errorInfo();
        		    $errorMsg = isset($errorInfo[2]) ? $errorInfo[2] : 'Kategori kaydetme hatası';
        		    echo '<script>alert("Kategori kaydetme hatası: ' . addslashes($errorMsg) . '");</script>';
        		} else {
        		    $kategori_eklendi = true;
        		}
			}
		}
	} else {
	    // Kategori seçilmemişse uyarı ver ama işlemi durdurma
	    echo '<script>console.warn("Uyarı: Hiç kategori seçilmedi!");</script>';
	}

    // SEF URL güncelleme
    $sef_basarili = true;
    if(isset($_POST['baslik']) && !empty($_POST['baslik'])){
    	$sef = sef($_POST['baslik']).'-'.$id;
    	$sef_islem = $db->prepare("UPDATE urun SET sef = ? WHERE id = ?");
    	$sef_islem = $sef_islem->execute(array($sef,$id));
    	if(!$sef_islem){
    	    $sef_basarili = false;
    	}
    }
    
    // Tüm işlemler başarılıysa başarı mesajı göster
    // Ana INSERT işlemi başarılıysa ve SEF güncellemesi başarılıysa başarı mesajı göster
    // Kategori seçilmemiş olsa bile ürün kaydedilmiş sayılır
    
    // DEBUG: Stok değerini göster
    echo '<script>console.log("PHP - Kaydedilen stok değeri: ' . $stok_degeri . '");</script>';
    
    if($islem && $sef_basarili){
        if(!$kategori_eklendi){
            echo '<script>alert("Ürün kaydedildi ancak kategori seçilmedi!");</script>';
        }
        echo b();
    }else{
        // Hata mesajını daha detaylı göster
        $hata_detay = '';
        if(!$islem){
            $hata_detay .= 'Ürün ekleme başarısız. ';
        }
        if(!$sef_basarili){
            $hata_detay .= 'SEF URL güncelleme başarısız. ';
        }
        if(!$kategori_basarili && $kategori_eklendi){
            $hata_detay .= 'Kategori kaydetme başarısız. ';
        }
        echo '<script>alert("Hata: ' . addslashes($hata_detay) . '");</script>';
        echo h();
    }
	?>
	<iframe src="../sitemap-olustur.php" style="width: 1px;height: 1px;"></iframe>
	<?php
}

if(isset($_GET['duzenle_id'])){
    // Depo stok sütunlarının varlığını kontrol et
    try {
        $checkColumns = $db->query("SHOW COLUMNS FROM urun LIKE 'maslak_stok'")->fetch();
        $hasWarehouseColumns = ($checkColumns !== false);
    } catch (Exception $e) {
        $hasWarehouseColumns = false;
    }
    
    // Fiyatlandırma sütunlarının varlığını kontrol et
    try {
        $checkPricingColumns = $db->query("SHOW COLUMNS FROM urun LIKE 'liste_fiyati_eur'")->fetch();
        $hasPricingColumns = ($checkPricingColumns !== false);
    } catch (Exception $e) {
        $hasPricingColumns = false;
    }
    
    // SELECT sorgusunu hazırla - depo stok ve fiyatlandırma sütunlarını da dahil et
    $selectFields = "*";
    if($hasWarehouseColumns){
        $selectFields .= ", maslak_stok, bolu_stok, imes_stok, ankara_stok, ikitelli_stok";
    }
    if($hasPricingColumns){
        $selectFields .= ", liste_fiyati_eur, liste_fiyati_tl, iskonto_orani, doviz_kuru, kredi_karti_fiyati, pesin_odeme_fiyati";
    }
    
    $duzenle = $db->query("SELECT {$selectFields} FROM urun WHERE id = '{$_GET['duzenle_id']}' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    
    // Depo stok durumlarını göstermek için al (ama genel stok değerini DEĞİŞTİRME!)
    // Kullanıcı manuel olarak stok durumunu ayarlayabilir
    if($hasWarehouseColumns && isset($duzenle['maslak_stok'])){
        $maslak = isset($duzenle['maslak_stok']) ? (int)$duzenle['maslak_stok'] : 0;
        $bolu = isset($duzenle['bolu_stok']) ? (int)$duzenle['bolu_stok'] : 0;
        $imes = isset($duzenle['imes_stok']) ? (int)$duzenle['imes_stok'] : 0;
        $ankara = isset($duzenle['ankara_stok']) ? (int)$duzenle['ankara_stok'] : 0;
        $ikitelli = isset($duzenle['ikitelli_stok']) ? (int)$duzenle['ikitelli_stok'] : 0;
        
        // NOT: Genel stok değerini DEĞİŞTİRMİYORUZ!
        // Kullanıcı admin panelinden manuel olarak stok durumunu belirleyebilir
        // $duzenle['stok'] değeri veritabanından gelen değer olarak kalacak
    }
    ?>
		<script type="text/javascript">
			$(function(){
				$('select[name="marka_id"] option[value="<?php echo $duzenle['marka_id']; ?>"]').attr('selected','select');
			});
		</script>
	<?php
}
?>
<div class="breadcrumb-header justify-content-between">
	<div class="my-auto">
		<div class="d-flex">
			<h4 class="content-title mb-0 my-auto">Ürün</h4><span class="text-muted mt-1 tx-13 ml-2 mb-0">/ Ekle - Düzenle</span>
		</div>
	</div>
</div>

<form class="form-horizontal" action="" method="post">
	<div class="row">
		<div class="col-lg-6 col-xl-6 col-md-12 col-sm-12">
			<div class="card  box-shadow-0">
				<div class="card-body pt-10">
					<div class="form-group">
						<label>Ürün Adı (Türkçe) <span class="text-danger">*</span></label>
						<div class="input-group">
							<input type="text" class="form-control" name="baslik" id="baslik" placeholder="Ürün Adı (Türkçe)" required="" value="<?php echo @$duzenle['baslik']; ?>">
							<div class="input-group-append">
								<button type="button" class="btn btn-success" id="btnSeoOptimizeTitle" title="AI ile SEO uyumlu urun adi olustur">
									<i class="fa fa-magic"></i> SEO Isim
								</button>
								<button type="button" class="btn btn-info" id="btnAutoTranslate" title="Türkçe metni otomatik çevir">
									<i class="fa fa-language"></i> Otomatik Çevir
								</button>
							</div>
						</div>
						<small class="form-text text-muted">SEO Isim: OpenAI API varsa AI, yoksa kural tabanli oneri uretir (AI Ayarlari).</small>
					</div>
					<div class="form-group">
						<label>Ürün Adı (İngilizce)</label>
						<input type="text" class="form-control" name="baslik_en" id="baslik_en" placeholder="Product Name (English)" value="<?php echo @$duzenle['baslik_en']; ?>">
						<small class="form-text text-muted">Boş bırakılırsa Türkçe ad gösterilir</small>
					</div>
					<div class="form-group">
						<label>Ürün Adı (Rusça)</label>
						<input type="text" class="form-control" name="baslik_ru" id="baslik_ru" placeholder="Название продукта (Russian)" value="<?php echo @$duzenle['baslik_ru']; ?>">
						<small class="form-text text-muted">Boş bırakılırsa Türkçe ad gösterilir</small>
					</div>
					<div class="form-group">
						<label>Ürün Adı (Fransızca)</label>
						<input type="text" class="form-control" name="baslik_fr" id="baslik_fr" placeholder="Nom du produit (French)" value="<?php echo @$duzenle['baslik_fr']; ?>">
						<small class="form-text text-muted">Boş bırakılırsa Türkçe ad gösterilir</small>
					</div>
					<div class="form-group">
						<label>Ürün Adı (İspanyolca)</label>
						<input type="text" class="form-control" name="baslik_es" id="baslik_es" placeholder="Nombre del producto (Spanish)" value="<?php echo @$duzenle['baslik_es']; ?>">
						<small class="form-text text-muted">Boş bırakılırsa Türkçe ad gösterilir</small>
					</div>
					<div class="form-group">
						<label>Ürün Adı (Arapça)</label>
						<input type="text" class="form-control" name="baslik_ar" id="baslik_ar" placeholder="اسم المنتج (Arabic)" dir="rtl" value="<?php echo @$duzenle['baslik_ar']; ?>">
						<small class="form-text text-muted">Boş bırakılırsa Türkçe ad gösterilir</small>
					</div>
					<div class="form-group">
						<label>Ürün Adı (Lehçe)</label>
						<input type="text" class="form-control" name="baslik_pl" id="baslik_pl" placeholder="Nazwa produktu (Polish)" value="<?php echo @$duzenle['baslik_pl']; ?>">
						<small class="form-text text-muted">Boş bırakılırsa Türkçe ad gösterilir</small>
					</div>
					<div class="form-group">
						<label>Kısa Açıklama (Türkçe)</label>
						<textarea class="form-control" name="kisa_aciklama" id="kisa_aciklama" placeholder="Kısa Açıklama (Türkçe)" rows="3"><?php echo @$duzenle['kisa_aciklama']; ?></textarea>
					</div>
					<div class="form-group">
						<label>Kısa Açıklama (İngilizce)</label>
						<textarea class="form-control" name="kisa_aciklama_en" id="kisa_aciklama_en" placeholder="Short Description (English)" rows="3"><?php echo @$duzenle['kisa_aciklama_en']; ?></textarea>
					</div>
					<div class="form-group">
						<label>Kısa Açıklama (Rusça)</label>
						<textarea class="form-control" name="kisa_aciklama_ru" id="kisa_aciklama_ru" placeholder="Краткое описание (Russian)" rows="3"><?php echo @$duzenle['kisa_aciklama_ru']; ?></textarea>
					</div>
					<div class="form-group">
						<label>Kısa Açıklama (Fransızca)</label>
						<textarea class="form-control" name="kisa_aciklama_fr" id="kisa_aciklama_fr" placeholder="Description courte (French)" rows="3"><?php echo @$duzenle['kisa_aciklama_fr']; ?></textarea>
					</div>
					<div class="form-group">
						<label>Kısa Açıklama (İspanyolca)</label>
						<textarea class="form-control" name="kisa_aciklama_es" id="kisa_aciklama_es" placeholder="Descripción corta (Spanish)" rows="3"><?php echo @$duzenle['kisa_aciklama_es']; ?></textarea>
					</div>
					<div class="form-group">
						<label>Kısa Açıklama (Arapça)</label>
						<textarea class="form-control" name="kisa_aciklama_ar" id="kisa_aciklama_ar" placeholder="وصف قصير (Arabic)" dir="rtl" rows="3"><?php echo @$duzenle['kisa_aciklama_ar']; ?></textarea>
					</div>
					<div class="form-group">
						<label>Kısa Açıklama (Lehçe)</label>
						<textarea class="form-control" name="kisa_aciklama_pl" id="kisa_aciklama_pl" placeholder="Krótki opis (Polish)" rows="3"><?php echo @$duzenle['kisa_aciklama_pl']; ?></textarea>
					</div>
					<div class="form-group">
						<label>Detay Açıklama (Türkçe)</label>
						<textarea class="form-control" name="aciklama" id="aciklama" placeholder="Detay Açıklama (Türkçe)" rows="5"><?php echo @$duzenle['aciklama']; ?></textarea>
					</div>
					<div class="form-group">
						<label>Detay Açıklama (İngilizce)</label>
						<textarea class="form-control" name="aciklama_en" id="aciklama_en" placeholder="Full Description (English)" rows="5"><?php echo @$duzenle['aciklama_en']; ?></textarea>
					</div>
					<div class="form-group">
						<label>Detay Açıklama (Rusça)</label>
						<textarea class="form-control" name="aciklama_ru" id="aciklama_ru" placeholder="Полное описание (Russian)" rows="5"><?php echo @$duzenle['aciklama_ru']; ?></textarea>
					</div>
					<div class="form-group">
						<label>Detay Açıklama (Fransızca)</label>
						<textarea class="form-control" name="aciklama_fr" id="aciklama_fr" placeholder="Description complète (French)" rows="5"><?php echo @$duzenle['aciklama_fr']; ?></textarea>
					</div>
					<div class="form-group">
						<label>Detay Açıklama (İspanyolca)</label>
						<textarea class="form-control" name="aciklama_es" id="aciklama_es" placeholder="Descripción completa (Spanish)" rows="5"><?php echo @$duzenle['aciklama_es']; ?></textarea>
					</div>
					<div class="form-group">
						<label>Detay Açıklama (Arapça)</label>
						<textarea class="form-control" name="aciklama_ar" id="aciklama_ar" placeholder="وصف كامل (Arabic)" dir="rtl" rows="5"><?php echo @$duzenle['aciklama_ar']; ?></textarea>
					</div>
					<div class="form-group">
						<label>Detay Açıklama (Lehçe)</label>
						<textarea class="form-control" name="aciklama_pl" id="aciklama_pl" placeholder="Pełny opis (Polish)" rows="5"><?php echo @$duzenle['aciklama_pl']; ?></textarea>
					</div>
					<div class="form-group">
						<input type="text" class="form-control" name="stok_kodu" id="stok_kodu" placeholder="Stok Kodu" value="<?php echo @$duzenle['stok_kodu']; ?>" required>
					</div>
					<div class="form-group">
						<label>Stok Durumu</label>
						<?php 
						// Stok değerini belirle
						$current_stok = 0;
						if(isset($duzenle['stok'])){
							$current_stok = (int)$duzenle['stok'];
						}
						?>
						<select class="form-control" name="stok" id="stok_durumu" required>
							<option value="0" <?php echo ($current_stok == 0) ? 'selected' : ''; ?>>Yok</option>
							<option value="1" <?php echo ($current_stok == 1) ? 'selected' : ''; ?>>Var</option>
						</select>
						<small class="form-text text-muted">Stok durumunu seçiniz (Mevcut: <?php echo $current_stok == 1 ? 'Var' : 'Yok'; ?>)</small>
					</div>
					<script>
					// Sayfa yüklendiğinde stok değerini kontrol et
					console.log('Sayfa yüklendi - Stok select değeri:', document.getElementById('stok_durumu').value);
					</script>
					<div class="form-group">
						<select class="form-control select2" name="marka_id">
							<option value="0">Markasız Ürün</option>
							<?php
								$query = $db->query("SELECT * FROM marka ORDER BY id DESC", PDO::FETCH_ASSOC);
			                    if($query->rowCount()){
			                        foreach( $query as $row ){
										echo '<option value="'.$row['id'].'">'.$row['baslik'].'</option>';
									}
								}
							?>
						</select>
					</div>
				</div>
			</div>
		</div>
		<div class="col-lg-6 col-xl-6 col-md-12 col-sm-12">
			<div class="card  box-shadow-0">
				<div class="card-body pt-10">
					<label>Kategori Seçiniz</label>
					<select class="form-control" name="kategori[]" id="kategori_select" multiple="multiple" style="width: 100%;">
						<?php
							// Performans için: Düzenleme modunda seçili kategorileri tek sorguda al
							$secili_kategoriler = array();
							if(isset($_GET['duzenle_id'])){
								$secili_kategori_query = $db->query("SELECT kategori_id FROM urun_kategori WHERE urun_id = '{$_GET['duzenle_id']}'", PDO::FETCH_ASSOC);
								if($secili_kategori_query->rowCount()){
									foreach($secili_kategori_query as $sk){
										$secili_kategoriler[] = $sk['kategori_id'];
									}
								}
							}
							
							// Tüm kategorileri al
							$cek = $db->query("SELECT * FROM kategori ORDER BY baslik ASC", PDO::FETCH_ASSOC);
							if($cek->rowCount()){
								foreach( $cek as $c ){
									$selected = in_array($c['id'], $secili_kategoriler) ? 'selected' : '';
									echo '<option value="'.$c['id'].'" '.$selected.'>#' .$c['id'].' '.$c['baslik'].'</option>';
					  			}
					  		}
				  		?>
					</select>
					<div class="form-group">
						<label class="font-weight-bold">Fiyatlandırma</label>
					</div>
					<?php
					// TCMB'den güncel Euro kurunu çek
					require_once __DIR__ . '/../../get-tcmb-euro-rate.php';
					$guncel_doviz_kuru = getTCMBEuroRate();
					if(!$guncel_doviz_kuru || $guncel_doviz_kuru <= 0){
						$guncel_doviz_kuru = isset($duzenle['doviz_kuru']) && $duzenle['doviz_kuru'] > 0 ? $duzenle['doviz_kuru'] : 35.00;
					}
					?>
					<div class="form-group">
						<label>Liste Fiyatı (Euro) <span class="text-danger">*</span></label>
						<input type="number" step="0.01" class="form-control" name="liste_fiyati_eur" placeholder="Liste Fiyatı (Euro)" value="<?php echo isset($duzenle['liste_fiyati_eur']) ? $duzenle['liste_fiyati_eur'] : ''; ?>" id="liste_fiyati_eur" required>
					</div>
					<div class="form-group">
						<label>Döviz Kuru (TL/Euro) <small class="text-muted">(TCMB'den otomatik çekilir)</small></label>
						<input type="number" step="0.0001" class="form-control" name="doviz_kuru" value="<?php echo number_format($guncel_doviz_kuru, 4, '.', ''); ?>" id="doviz_kuru" readonly style="background-color: #e9ecef;">
					</div>
					<div class="form-group">
						<label>Liste Fiyatı (TL) <small class="text-muted">(Otomatik hesaplanır)</small></label>
						<input type="number" step="0.01" class="form-control" name="liste_fiyati_tl" placeholder="Liste Fiyatı (TL)" value="<?php echo isset($duzenle['liste_fiyati_tl']) ? $duzenle['liste_fiyati_tl'] : ''; ?>" id="liste_fiyati_tl" readonly style="background-color: #e9ecef;">
					</div>
					<div class="form-group">
						<label>İskonto Oranı (%) <small class="text-muted">(Boş bırakılırsa %0)</small></label>
						<input type="number" step="0.01" class="form-control" name="iskonto_orani" placeholder="İskonto Oranı (%)" value="<?php echo isset($duzenle['iskonto_orani']) ? $duzenle['iskonto_orani'] : ''; ?>" id="iskonto_orani">
					</div>
					<div class="form-group">
						<label>KDV Oranı <small class="text-muted">(Her zaman %20 - Değiştirilemez)</small></label>
						<input type="text" class="form-control" value="%20" readonly style="background-color: #e9ecef;">
						<input type="hidden" name="kdv" value="20">
					</div>
					<div class="form-group">
						<label>KDV'siz Net Fiyat (TL) <small class="text-muted">(Otomatik hesaplanır - Değiştirilemez)</small></label>
						<input type="number" step="0.01" class="form-control" id="kdvsiz_net_fiyat" readonly style="background-color: #e9ecef;">
					</div>
					<div class="form-group">
						<label>Net Fiyat KDV Dahil (TL) <small class="text-muted">(Otomatik hesaplanır - Değiştirilemez)</small></label>
						<input type="number" step="0.01" class="form-control" id="net_fiyat_kdv_dahil" readonly style="background-color: #e9ecef;">
					</div>
					<div class="form-group">
						<label>Kredi Kartı ile Fiyat (TL) <small class="text-muted">(Net Fiyat KDV Dahil - Otomatik hesaplanır - Değiştirilemez)</small></label>
						<input type="number" step="0.01" class="form-control" name="kredi_karti_fiyati" id="kredi_karti_fiyati" value="<?php echo isset($duzenle['kredi_karti_fiyati']) ? $duzenle['kredi_karti_fiyati'] : ''; ?>" readonly style="background-color: #e9ecef;">
					</div>
					<div class="form-group">
						<label>Peşin Ödeme ile Fiyat (TL) <small class="text-muted">(Net Fiyat KDV Dahil'in %5 azı - Otomatik hesaplanır - Değiştirilemez)</small></label>
						<input type="number" step="0.01" class="form-control" name="pesin_odeme_fiyati" id="pesin_odeme_fiyati" value="<?php echo isset($duzenle['pesin_odeme_fiyati']) ? $duzenle['pesin_odeme_fiyati'] : ''; ?>" readonly style="background-color: #e9ecef;">
					</div>
					<script>
					$(document).ready(function(){
						function calculatePrices(){
							var listeFiyatiEur = parseFloat($('#liste_fiyati_eur').val()) || 0;
							var dovizKuru = parseFloat($('#doviz_kuru').val()) || 0;
							var iskontoOrani = parseFloat($('#iskonto_orani').val()) || 0;
							
							if(listeFiyatiEur > 0 && dovizKuru > 0){
								// Liste Fiyatı TL = Liste Fiyatı Euro × Döviz Kuru
								var listeFiyatiTl = listeFiyatiEur * dovizKuru;
								$('#liste_fiyati_tl').val(listeFiyatiTl.toFixed(2));
								
								// KDV'siz Net Fiyat = Liste Fiyatı TL × (1 - İskonto Oranı / 100)
								var kdvsizNetFiyat = listeFiyatiTl * (1 - iskontoOrani / 100);
								$('#kdvsiz_net_fiyat').val(kdvsizNetFiyat.toFixed(2));
								
								// Net Fiyat KDV Dahil = KDV'siz Net Fiyat × 1.20
								var netFiyatKdvDahil = kdvsizNetFiyat * 1.20;
								$('#net_fiyat_kdv_dahil').val(netFiyatKdvDahil.toFixed(2));
								
								// Kredi Kartı ile Fiyat = Net Fiyat KDV Dahil
								$('#kredi_karti_fiyati').val(netFiyatKdvDahil.toFixed(2));
								
								// Peşin Ödeme ile Fiyat = Net Fiyat KDV Dahil × 0.95 (%5 az)
								var pesinOdemeFiyati = netFiyatKdvDahil * 0.95;
								$('#pesin_odeme_fiyati').val(pesinOdemeFiyati.toFixed(2));
							} else {
								$('#liste_fiyati_tl').val('');
								$('#kdvsiz_net_fiyat').val('');
								$('#net_fiyat_kdv_dahil').val('');
								$('#kredi_karti_fiyati').val('');
								$('#pesin_odeme_fiyati').val('');
							}
						}
						
						// Liste fiyatı Euro, döviz kuru ve iskonto oranı değiştiğinde hesapla
						$('#liste_fiyati_eur, #doviz_kuru, #iskonto_orani').on('input', calculatePrices);
						
						// Sayfa yüklendiğinde de hesapla
						calculatePrices();
					});
					
					// Otomatik Çeviri Fonksiyonu
					$('#btnSeoOptimizeTitle').on('click', function() {
						var btn = $(this);
						var originalHTML = btn.html();
						var baslik = $('#baslik').val().trim();
						if (!baslik) {
							alert('Once urun adini girin.');
							return;
						}
						btn.prop('disabled', true);
						btn.html('<i class="fa fa-spinner fa-spin"></i>');
						var fd = new FormData();
						fd.append('ajax_seo_action', 'optimize_one');
						fd.append('baslik', baslik);
						fd.append('stok_kodu', ($('#stok_kodu').val() || '').trim());
						<?php if (isset($_GET['duzenle_id'])) { ?>
						fd.append('urun_id', '<?php echo (int)$_GET['duzenle_id']; ?>');
						<?php } ?>
						fetch('inc/ajax-product-seo-title.php', { method: 'POST', body: fd })
							.then(function(r) { return r.json(); })
							.then(function(data) {
								if (!data.success || !data.title) {
									throw new Error(data.error || 'SEO onerisi alinamadi');
								}
								if (confirm('Onerilen SEO isim:\n\n' + data.title + '\n\n(' + (data.method === 'ai' ? 'AI' : 'Kural') + ')\n\nUygulansin mi?')) {
									$('#baslik').val(data.title);
								}
							})
							.catch(function(err) {
								alert(err.message || 'SEO isim hatasi');
							})
							.finally(function() {
								btn.prop('disabled', false);
								btn.html(originalHTML);
							});
					});

					$('#btnAutoTranslate').on('click', function() {
						var btn = $(this);
						var originalHTML = btn.html();
						btn.prop('disabled', true);
						btn.html('<i class="fa fa-spinner fa-spin"></i> Çeviriliyor...');
						
						// Türkçe metinleri al
						var baslik = $('#baslik').val().trim();
						var kisa_aciklama = $('#kisa_aciklama').val().trim();
						var aciklama = $('#aciklama').val().trim();
						
						if (!baslik && !kisa_aciklama && !aciklama) {
							alert('Lütfen en az bir Türkçe metin girin!');
							btn.prop('disabled', false);
							btn.html(originalHTML);
							return;
						}
						
						var translations = {
							baslik_en: '', baslik_ru: '', baslik_fr: '', baslik_es: '', baslik_ar: '', baslik_pl: '',
							kisa_aciklama_en: '', kisa_aciklama_ru: '', kisa_aciklama_fr: '', kisa_aciklama_es: '', kisa_aciklama_ar: '', kisa_aciklama_pl: '',
							aciklama_en: '', aciklama_ru: '', aciklama_fr: '', aciklama_es: '', aciklama_ar: '', aciklama_pl: ''
						};
						
						var promises = [];
						var languages = ['en', 'ru', 'fr', 'es', 'ar', 'pl'];
						
						// Ürün adını çevir
						if (baslik) {
							languages.forEach(function(lang) {
								promises.push(
									translateText(baslik, 'tr', lang).then(function(result) {
										translations['baslik_' + lang] = result;
										return { type: 'baslik', lang: lang, success: true };
									}).catch(function(error) {
										console.error('Baslik çeviri hatası (' + lang + '):', error);
										return { type: 'baslik', lang: lang, success: false, error: error };
									})
								);
							});
						}
						
						// Kısa açıklamayı çevir
						if (kisa_aciklama) {
							languages.forEach(function(lang) {
								promises.push(
									translateText(kisa_aciklama, 'tr', lang).then(function(result) {
										translations['kisa_aciklama_' + lang] = result;
										return { type: 'kisa_aciklama', lang: lang, success: true };
									}).catch(function(error) {
										console.error('Kısa açıklama çeviri hatası (' + lang + '):', error);
										return { type: 'kisa_aciklama', lang: lang, success: false, error: error };
									})
								);
							});
						}
						
						// Detay açıklamayı çevir
						if (aciklama) {
							languages.forEach(function(lang) {
								promises.push(
									translateText(aciklama, 'tr', lang).then(function(result) {
										translations['aciklama_' + lang] = result;
										return { type: 'aciklama', lang: lang, success: true };
									}).catch(function(error) {
										console.error('Açıklama çeviri hatası (' + lang + '):', error);
										return { type: 'aciklama', lang: lang, success: false, error: error };
									})
								);
							});
						}
						
						// Tüm çevirileri bekle (bazıları başarısız olsa bile devam et)
						Promise.all(promises).then(function(results) {
							var successCount = 0;
							var failCount = 0;
							var failedLangs = [];
							
							// Sonuçları işle
							results.forEach(function(result) {
								if (result && result.success) {
									successCount++;
								} else {
									failCount++;
									if (result && result.lang && failedLangs.indexOf(result.lang) === -1) {
										failedLangs.push(result.lang);
									}
								}
							});
							
							// Çevirileri input alanlarına yaz
							languages.forEach(function(lang) {
								if (translations['baslik_' + lang]) $('#baslik_' + lang).val(translations['baslik_' + lang]);
								if (translations['kisa_aciklama_' + lang]) $('#kisa_aciklama_' + lang).val(translations['kisa_aciklama_' + lang]);
								if (translations['aciklama_' + lang]) $('#aciklama_' + lang).val(translations['aciklama_' + lang]);
							});
							
							// Sonuç mesajı
							if (failCount === 0) {
								alert('Tüm çeviriler başarıyla tamamlandı!');
							} else if (successCount > 0) {
								var langNames = {
									'en': 'İngilizce',
									'ru': 'Rusça',
									'fr': 'Fransızca',
									'es': 'İspanyolca',
									'ar': 'Arapça',
									'pl': 'Lehçe'
								};
								var failedLangNames = failedLangs.map(function(lang) {
									return langNames[lang] || lang;
								}).join(', ');
								alert('Çeviriler tamamlandı! (' + successCount + ' başarılı, ' + failCount + ' başarısız)\n\nBaşarısız diller: ' + (failedLangNames || 'Bilinmeyen') + '\n\nLütfen bu dilleri manuel olarak doldurun.');
							} else {
								alert('Çeviri başarısız oldu. Lütfen manuel olarak girin veya tekrar deneyin.');
							}
							
							btn.prop('disabled', false);
							btn.html(originalHTML);
						}).catch(function(error) {
							console.error('Genel çeviri hatası:', error);
							// Yine de başarılı olan çevirileri yaz
							languages.forEach(function(lang) {
								if (translations['baslik_' + lang]) $('#baslik_' + lang).val(translations['baslik_' + lang]);
								if (translations['kisa_aciklama_' + lang]) $('#kisa_aciklama_' + lang).val(translations['kisa_aciklama_' + lang]);
								if (translations['aciklama_' + lang]) $('#aciklama_' + lang).val(translations['aciklama_' + lang]);
							});
							alert('Çeviri sırasında bazı hatalar oluştu. Başarılı çeviriler yüklendi.\n\nLütfen eksik dilleri manuel olarak doldurun.');
							btn.prop('disabled', false);
							btn.html(originalHTML);
						});
					});
					
					// Çeviri fonksiyonu - Google Translate API (Ücretsiz)
					function translateText(text, fromLang, toLang) {
						return new Promise(function(resolve, reject) {
							if (!text || text.trim() === '') {
								resolve('');
								return;
							}
							
							// Dil kodlarını normalize et
							var langMap = {
								'tr': 'tr',
								'en': 'en',
								'ru': 'ru',
								'fr': 'fr',
								'es': 'es',
								'ar': 'ar',
								'pl': 'pl'
							};
							
							var normalizedFromLang = langMap[fromLang] || fromLang;
							var normalizedToLang = langMap[toLang] || toLang;
							
							// Google Translate API (Ücretsiz versiyon)
							var apiUrl = 'https://translate.googleapis.com/translate_a/single?client=gtx&sl=' + normalizedFromLang + '&tl=' + normalizedToLang + '&dt=t&q=' + encodeURIComponent(text);
							
							$.ajax({
								url: apiUrl,
								method: 'GET',
								dataType: 'json',
								timeout: 15000,
								success: function(data) {
									try {
										if (data && Array.isArray(data) && data[0] && Array.isArray(data[0])) {
											// Google Translate API yanıtını parse et
											var translatedText = '';
											for (var i = 0; i < data[0].length; i++) {
												if (data[0][i] && data[0][i][0]) {
													translatedText += data[0][i][0];
												}
											}
											if (translatedText) {
												resolve(translatedText);
											} else {
												// Alternatif: MyMemory API dene
												translateWithMyMemory(text, normalizedFromLang, normalizedToLang).then(resolve).catch(reject);
											}
										} else {
											// Alternatif: MyMemory API dene
											translateWithMyMemory(text, normalizedFromLang, normalizedToLang).then(resolve).catch(reject);
										}
									} catch (e) {
										console.error('Google Translate parse hatası:', e);
										// Alternatif: MyMemory API dene
										translateWithMyMemory(text, normalizedFromLang, normalizedToLang).then(resolve).catch(reject);
									}
								},
								error: function(xhr, status, error) {
									console.error('Google Translate API hatası:', error);
									// Alternatif: MyMemory API dene
									translateWithMyMemory(text, normalizedFromLang, normalizedToLang).then(resolve).catch(function(err) {
										reject('Çeviri başarısız: ' + error);
									});
								}
							});
						});
					}
					
					// Alternatif çeviri API'si (MyMemory - Ücretsiz)
					function translateWithMyMemory(text, fromLang, toLang) {
						return new Promise(function(resolve, reject) {
							// MyMemory API dil kodlarını kontrol et
							var myMemoryLangMap = {
								'tr': 'tr',
								'en': 'en',
								'ru': 'ru',
								'fr': 'fr',
								'es': 'es',
								'ar': 'ar',
								'pl': 'pl'
							};
							
							var myMemoryFromLang = myMemoryLangMap[fromLang] || fromLang;
							var myMemoryToLang = myMemoryLangMap[toLang] || toLang;
							
							var apiUrl = 'https://api.mymemory.translated.net/get?q=' + encodeURIComponent(text) + '&langpair=' + myMemoryFromLang + '|' + myMemoryToLang;
							
							$.ajax({
								url: apiUrl,
								method: 'GET',
								dataType: 'json',
								timeout: 10000,
								success: function(data) {
									if (data && data.responseStatus === 200 && data.responseData && data.responseData.translatedText) {
										resolve(data.responseData.translatedText);
									} else {
										reject('MyMemory çeviri başarısız');
									}
								},
								error: function() {
									reject('MyMemory API hatası');
								}
							});
						});
					}
					</script>
				</div>
			</div>
		</div>
	</div>
	<div class="row">
		<div class="col-lg-12 col-xl-12 col-md-12 col-sm-12">
			<div class="card  box-shadow-0">
				<div class="card-header">
					<h4 class="card-title">Referans Numaraları</h4>
				</div>
				<div class="card-body pt-10">
					<div class="form-group">
						<label>Referans Numarası Ekle</label>
						<div class="input-group">
							<textarea class="form-control" id="yeni_referans" rows="4" placeholder="Tekli veya çoklu satır yapıştırın (örn: CASE IH[TAB]2855491)"></textarea>
							<div class="input-group-append">
								<button type="button" class="btn btn-primary" id="referans_ekle_btn">Ekle</button>
							</div>
						</div>
						<small class="text-muted">Format: "Marka # Referans", "Marka[TAB]Referans" veya sadece "Referans". Excel/tablodan çoklu satır yapıştırmayı destekler. Ekle butonuna basın (veya Ctrl+Enter).</small>
					</div>
					
					<div class="table-responsive mt-3">
						<table class="table table-bordered table-hover" id="referans_tablosu">
							<thead>
								<tr>
									<th style="width: 30%;">Marka Adı</th>
									<th style="width: 60%;">Referans Numarası</th>
									<th style="width: 10%;">İşlem</th>
								</tr>
							</thead>
							<tbody id="referans_tbody">
								<?php
								if(isset($_GET['duzenle_id'])){
									$referanslar = $db->query("SELECT * FROM urun_referans WHERE urun_id = '{$_GET['duzenle_id']}' ORDER BY sira ASC, id ASC", PDO::FETCH_ASSOC);
									if($referanslar->rowCount()){
										foreach($referanslar as $ref){
											echo '<tr data-id="'.$ref['id'].'">
												<td><input type="text" class="form-control form-control-sm" name="referans_marka[]" value="'.htmlspecialchars($ref['marka_adi']).'" placeholder="Marka Adı"></td>
												<td><input type="text" class="form-control form-control-sm" name="referans_no[]" value="'.htmlspecialchars($ref['referans_no']).'" required></td>
												<td>
													<input type="hidden" name="referans_id[]" value="'.$ref['id'].'">
													<button type="button" class="btn btn-danger btn-sm remove-referans-row">Sil</button>
												</td>
											</tr>';
										}
									}
								}
								?>
							</tbody>
						</table>
					</div>
				</div>
			</div>
		</div>
	</div>
	<script>
	$(document).ready(function(){
		var referansIndex = 0;
		
		function htmlEscape(value){
			return $('<div>').text(value).html();
		}

		function referansSatiriEkle(marka, referans){
			if(!referans) return;
			var html = '<tr data-index="'+referansIndex+'">' +
				'<td><input type="text" class="form-control form-control-sm" name="referans_marka[]" value="'+htmlEscape(marka)+'" placeholder="Marka Adı"></td>' +
				'<td><input type="text" class="form-control form-control-sm" name="referans_no[]" value="'+htmlEscape(referans)+'" required></td>' +
				'<td>' +
					'<input type="hidden" name="referans_id[]" value="0">' +
					'<button type="button" class="btn btn-danger btn-sm remove-referans-row">Sil</button>' +
				'</td>' +
			'</tr>';
			
			$('#referans_tbody').append(html);
			referansIndex++;
		}

		function satiriParcala(line){
			var temizSatir = line.trim();
			if(!temizSatir){
				return null;
			}

			// Eski format: "Marka # Referans"
			if(temizSatir.includes('#')){
				var hashParcalari = temizSatir.split('#');
				return {
					marka: hashParcalari[0].trim(),
					referans: hashParcalari.slice(1).join('#').trim()
				};
			}

			// Çoklu kopyala-yapıştır formatı: "MARKA<TAB>REFERANS" veya "MARKA    REFERANS"
			var tabParcalari = temizSatir.split(/\t+/);
			if(tabParcalari.length >= 2){
				return {
					marka: tabParcalari[0].trim(),
					referans: tabParcalari.slice(1).join(' ').trim()
				};
			}

			var boslukParcalari = temizSatir.split(/\s{2,}/);
			if(boslukParcalari.length >= 2){
				return {
					marka: boslukParcalari[0].trim(),
					referans: boslukParcalari.slice(1).join(' ').trim()
				};
			}

			return {
				marka: '',
				referans: temizSatir
			};
		}

		function topluMetniParcala(input){
			var sonuclar = [];
			var temizInput = input.trim();
			if(!temizInput){
				return sonuclar;
			}

			var satirlar = temizInput.split(/\r?\n/).filter(function(s){ return s.trim() !== ''; });

			// Bazı kaynaklar çoklu satırı tek satır ve tab dizisi olarak yapıştırır:
			// MARKA<TAB>REF<TAB>MARKA<TAB>REF...
			if(satirlar.length === 1){
				var tabParcalari = satirlar[0].split(/\t+/).map(function(s){ return s.trim(); }).filter(function(s){ return s !== ''; });
				if(tabParcalari.length >= 4 && tabParcalari.length % 2 === 0){
					for(var i = 0; i < tabParcalari.length; i += 2){
						sonuclar.push({
							marka: tabParcalari[i],
							referans: tabParcalari[i + 1]
						});
					}
					return sonuclar;
				}

				// Bazı formlarda yapıştırma newline/tab karakterlerini tek boşluğa çevirir.
				// Örn: "CASE IH 2855491 IRISBUS 504091504 ..."
				var tekSatirTokenlar = satirlar[0].split(/\s+/).filter(function(s){ return s !== ''; });
				if(tekSatirTokenlar.length >= 4){
					var markaTokenlari = [];
					var tahminiKayitlar = [];

					$.each(tekSatirTokenlar, function(_, token){
						if(/\d/.test(token)){
							var markaAdayi = markaTokenlari.join(' ').trim();
							if(markaAdayi){
								tahminiKayitlar.push({
									marka: markaAdayi,
									referans: token
								});
								markaTokenlari = [];
							} else {
								markaTokenlari.push(token);
							}
						} else {
							markaTokenlari.push(token);
						}
					});

					if(tahminiKayitlar.length >= 2){
						return tahminiKayitlar;
					}
				}
			}

			$.each(satirlar, function(_, satir){
				var parsed = satiriParcala(satir);
				if(parsed && parsed.referans){
					sonuclar.push(parsed);
				}
			});

			return sonuclar;
		}

		// Referans ekleme fonksiyonu
		function referansEkle(){
			var input = $('#yeni_referans').val().trim();
			if(!input) return;

			var kayitlar = topluMetniParcala(input);
			$.each(kayitlar, function(_, kayit){
				referansSatiriEkle(kayit.marka, kayit.referans);
			});

			$('#yeni_referans').val('');
		}
		
		// Ctrl+Enter ile ekle
		$('#yeni_referans').on('keydown', function(e){
			if((e.ctrlKey || e.metaKey) && e.key === 'Enter'){
				e.preventDefault();
				referansEkle();
			}
		});
		
		// Buton ile ekle
		$('#referans_ekle_btn').on('click', function(){
			referansEkle();
		});
		
		// Satır silme
		$(document).on('click', '.remove-referans-row', function(){
			$(this).closest('tr').remove();
		});
	});
	</script>
	<div class="row">
		<div class="col-lg-12 col-xl-12 col-md-12 col-sm-12">
			<div class="card">
				<div class="card-header">
					<h4 class="card-title">Varyantlar</h4>
				</div>
				<div class="card-body">
					<div class="row">
						<div class="col-lg-12 col-md-12">
							<div class="form-group">
								<div class="row" id="secenekler">
								<?php
									$i = 0;
									if(isset($_GET['duzenle_id'])){
										$cek = $db->query("SELECT * FROM urun_secenek WHERE urun_id = '{$_GET['duzenle_id']}' ", PDO::FETCH_ASSOC);
										if($cek->rowCount()){
											foreach( $cek as $c ){
												echo '<div class="col-md-12" data-secenek="'.$i.'">
														<div class="row form-group">
															<div class="col-md-8"><input type="text" class="form-control" name="secenek_adi[]" value="'.$c['baslik'].'" placeholder="Varyant Adı"></div>
															<div class="col-md-1"><button type="button" data-secenek-sil="'.$i.'" class="btn btn-danger">Sil</button></div>
															<div class="col-md-3"><button type="button" data-alt-secenek-ekle="'.$i.'" class="btn btn-success">Alt Seçenek Ekle</button></div>
														</div>
														<div class="row form-group alt_senecekler" data-alt-secenek="'.$i.'">
														';

														$ii = 0;
														$cek1 = $db->query("SELECT * FROM urun_secenek_alt WHERE urun_secenek_id = '{$c['id']}' ", PDO::FETCH_ASSOC);
														if($cek1->rowCount()){
															foreach( $cek1 as $c1 ){
																echo '<div class="col-md-12" data-alt-secenek-dis="'.$ii.'">
																		<div class="row form-group">
																			<div class="col-md-3">
																				<input type="text" class="form-control" placeholder="Alt Seçenek Adı" value="'.$c1['baslik'].'" name="alt_secenek_adi'.$i.'[]">
																			</div>
																			<div class="col-md-3">
																				<input type="text" class="form-control" placeholder="Stok Sayısı" value="'.$c1['stok'].'" name="alt_secenek_stok'.$i.'[]">
																			</div>
																			<div class="col-md-3">
																				<input type="text" class="form-control" placeholder="+Fiyat" value="'.$c1['fiyat'].'" name="alt_secenek_fiyat'.$i.'[]">
																			</div>
																			<div class="col-md-3">
																				<button type="button" data-alt-secenek-sil="'.$ii.'" class="btn btn-danger">Sil</button>
																			</div>
																		</div>
																	</div>';
																$ii++;
															}
														}


												echo 	'</div>
													</div>';
									            $i++;
											}
											?>
											<script type="text/javascript">
												jQuery(document).ready(function($){
													$('#ekle').hide(1000);
												});
											</script>
											<?php
										}
									}
								?>
								</div>
								<button type="button" id="ekle" class="btn btn-success">Varyant Ekle</button>
								<script type="text/javascript">
									jQuery(document).ready(function($){

										$('#ekle').click(function(){
											var say = $('[data-secenek]').length;
											$('#secenekler').append('\
												<div class="col-md-12" data-secenek="'+say+'">\
													<div class="row form-group">\
														<div class="col-md-8"><input type="text" class="form-control" name="secenek_adi[]" placeholder="Seçenek Adı"></div>\
														<div class="col-md-1"><button type="button" data-secenek-sil="'+say+'" class="btn btn-danger">Sil</button></div>\
														<div class="col-md-3"><button type="button" data-alt-secenek-ekle="'+say+'" class="btn btn-success">Alt Seçenek Ekle</button></div>\
													</div>\
													<div class="row form-group alt_senecekler" data-alt-secenek="'+say+'"></div>\
												</div>\
											');
											$('#ekle').hide(1000);
										});

										$(document).on('click','[data-secenek-sil]', function(){
											$('[data-secenek="'+$(this).attr('data-secenek-sil')+'"]').remove();
											$('#ekle').fadeIn(1000);
										});

										$(document).on('click','[data-alt-secenek-ekle]', function(){
											var say = $('[data-alt-secenek-dis]').length;
											$('[data-alt-secenek="'+$(this).attr('data-alt-secenek-ekle')+'"]').append('\
												<div class="col-md-12" data-alt-secenek-dis="'+say+'">\
													<div class="row form-group">\
														<div class="col-md-3">\
															<input type="text" class="form-control" placeholder="Alt Seçenek Adı" name="alt_secenek_adi'+$(this).attr('data-alt-secenek-ekle')+'[]">\
														</div>\
														<div class="col-md-3">\
															<input type="text" class="form-control" placeholder="Stok Sayısı" name="alt_secenek_stok'+$(this).attr('data-alt-secenek-ekle')+'[]">\
														</div>\
														<div class="col-md-3">\
															<input type="text" class="form-control" placeholder="+Fiyat" name="alt_secenek_fiyat'+$(this).attr('data-alt-secenek-ekle')+'[]">\
														</div>\
														<div class="col-md-3">\
															<button type="button" data-alt-secenek-sil="'+say+'" class="btn btn-danger">Sil</button>\
														</div>\
													</div>\
												</div>\
											');
										});

										$(document).on('click','[data-alt-secenek-sil]', function(){
											$('[data-alt-secenek-dis="'+$(this).attr('data-alt-secenek-sil')+'"]').remove();
										});

									});
								</script>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
	<div class="row">
		<div class="col-lg-12 col-xl-12 col-md-12 col-sm-12">
			<div class="card">
				<div class="card-header">
					<h4 class="card-title">Renk Varyantı</h4>
				</div>
				<div class="card-body">
					<div class="row">
						<div class="col-lg-12 col-md-12">
							<div class="form-group">
								<div class="row" id="renksecenekler">
									<?php
										$i = 0;
										if(isset($_GET['duzenle_id'])){
											$cek = $db->query("SELECT * FROM urun_renk WHERE urun_id = '{$_GET['duzenle_id']}' ", PDO::FETCH_ASSOC);
											if($cek->rowCount()){
												foreach( $cek as $c ){
													?>
													<div class="col-md-12" data-renksecenekdis="<?php echo $i; ?>">
														<div class="row form-group">
															<div class="col-md-8">
															<select class="form-control select2" name="renk_urun_id[]" required="">
																<option value="">Ürün Seçiniz</option>
																<?php
																	$cek1 = $db->query("SELECT * FROM urun", PDO::FETCH_ASSOC);
																	if($cek1->rowCount()){
																		foreach( $cek1 as $c1 ){
																			$aktif = '';
																			if($c['renk_urun_id'] == $c1['id']){
																				$aktif = 'selected';
																			}
																			echo '<option value="'.$c1['id'].'" '.$aktif.'>'.$c1['baslik'].'</option>';
																		}
																	}
																?>
															</select>
															</div>
															<div class="col-md-4"><button type="button" data-renksecenek-sil="<?php echo $i; ?>" class="btn btn-danger">Sil</button></div>
														</div>
													</div>
													<?php
													$i++;
												}
											}
										}
									?>
								</div>
								<button type="button" id="renkekle" class="btn btn-success">Renk Ekle</button>
								<script type="text/javascript">
									jQuery(document).ready(function($){

										$('#renkekle').click(function(){
											var say = $('[data-renksecenekdis]').length;
											$('#renksecenekler').append('\
												<div class="col-md-12" data-renksecenekdis="'+say+'">\
													<div class="row form-group">\
														<div class="col-md-8">\
														<select class="form-control select2" name="renk_urun_id[]" required="">\
															<option value="">Ürün Seçiniz</option>\
															<?php
																$cek = $db->query("SELECT * FROM urun", PDO::FETCH_ASSOC);
																if($cek->rowCount()){
																	foreach( $cek as $c ){
																		echo '<option value="'.$c['id'].'">'.addslashes($c['baslik']).'</option>';
																	}
																}
															?>
														</select>\
														</div>\
														<div class="col-md-4"><button type="button" data-renksecenek-sil="'+say+'" class="btn btn-danger">Sil</button></div>\
													</div>\
												</div>\
											');
										});

										$(document).on('click','[data-renksecenek-sil]', function(){
											$('[data-renksecenekdis="'+$(this).attr('data-renksecenek-sil')+'"]').remove();
										});

									});
								</script>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
	<div class="row">
		<div class="col-lg-12 col-xl-12 col-md-12 col-sm-12">
			<div class="card">
				<div class="card-header">
					<h4 class="card-title">Ürün Fotoğrafları</h4>
				</div>
				<div class="card-body">
					<div class="row">
						<div class="col-lg-12 col-md-12">
							<div class="form-group row" id="resimler">
								<?php
									$i = 0;
									if(isset($_GET['duzenle_id'])){
										$cek = $db->query("SELECT * FROM urun_img WHERE urun_id = '{$_GET['duzenle_id']}' ", PDO::FETCH_ASSOC);
										if($cek->rowCount()){
											foreach( $cek as $c ){
												echo '<div class="col-md-3" data-resim-dis-id="'.$i.'">
									                    <div class="uploaddis pasif" style="float:left;">
									        			  <div class="yuklendi">
									        				  <img src="'.htmlspecialchars(media_panel_url($c['img']), ENT_QUOTES, 'UTF-8').'">
									        				  <div class="icon" data-resim-sil-id="'.$i.'"><span class="fa fa-trash"></span></div>
									        				  <input type="hidden" name="img[]" value="'.$c['img'].'" required="">
									        			  </div>
									        			</div>
									                </div>';
									            $i++;
											}
										}
									}
								?>
							</div>
							<div class="form-group row">
				                <div class="col-md-4 offset-4">
				                    <div class="uploaddis aktif" data-id="1" style="margin:0 auto;">
				        			  <div class="upload1" data-upload-context="product" style="cursor: pointer;">
				        				  <span class="metin" style="width: 100%;float: left; font-size: 12px; line-height: 1.4;">
				        				  	Ürün Resimi Yükle<br>
				        				  	<small style="font-size: 10px; color: #666;">Sürükle-Bırak veya Ctrl+V ile yapıştır</small>
				        				  </span>
				        				  <div class="icon"><span class="fa fa-upload" data-id="1"></span></div>
				        			  </div>
				        			</div>
				                </div>
				            </div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
	<div class="row">
		<div class="col-md-12">
			<center><div class="form-group"><button type="submit" class="btn btn-success">Değişiklikleri Kaydet</button></div></center>
		</div>
	</div>
</form>

<div id="queue"></div>

<link href="inc/assets/plugins/wysiwyag/richtext.css" rel="stylesheet" />
<script src="inc/assets/plugins/wysiwyag/jquery.richtext.js"></script>
<script src="inc/assets/js/form-editor.js"></script>

<!-- Kategori Select2 Özel Başlatma - Arama özellikli -->
<script>
jQuery(document).ready(function($){
	// form-elements.js ve tüm script'ler yüklendikten sonra çalış
	setTimeout(function(){
		var $select = $('#kategori_select');
		
		if($select.length === 0) return;
		
		// Select2 kontrolü
		if(typeof $.fn.select2 === 'undefined'){
			console.log('Select2 yüklenmedi, kategori seçimi normal select olarak çalışacak');
			return;
		}
		
		// Mevcut select2 başlatmasını kaldır (eğer varsa)
		if($select.hasClass('select2-hidden-accessible')){
			try {
				$select.select2('destroy');
			} catch(e) {
				// Hata varsa devam et
			}
		}
		
		// Select2'yi arama özellikli olarak başlat
		try {
			$select.select2({
				placeholder: 'Kategori seçiniz veya yazarak arayın...',
				allowClear: true,
				width: '100%',
				minimumInputLength: 0,
				closeOnSelect: false,
				tags: false
			});
		} catch(e) {
			console.error('Kategori Select2 başlatma hatası:', e);
		}
	}, 2500);
});
</script>
