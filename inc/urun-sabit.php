<?php
// Dil seçeneği yönetimi - eğer henüz tanımlı değilse
if (!isset($language)) {
    // Önce GET parametresini kontrol et
    if (isset($_GET['lang']) && in_array($_GET['lang'], ['tr', 'ru', 'en'])) {
        $language = $_GET['lang'];
    } 
    // Sonra cookie'yi kontrol et
    elseif (isset($_COOKIE['site_language']) && in_array($_COOKIE['site_language'], ['tr', 'ru', 'en'])) {
        $language = $_COOKIE['site_language'];
    } 
    // Varsayılan olarak İngilizce
    else {
        $language = 'en';
    }
}

// Ürün başlığını dil seçimine göre al
$urun_baslik = isset($row['baslik']) ? $row['baslik'] : '';
$urun_img_src = 'upload/no-image.jpg';
if (!empty($row['img'])) {
	$imgVal = trim((string)$row['img']);
	if (preg_match('#^https?://#i', $imgVal)) {
		$urun_img_src = $imgVal;
	} else {
		$urun_img_src = 'upload/' . $imgVal;
	}
}

// Çok dilli alanları kontrol et
if ($language == 'en') {
    // İngilizce başlık varsa kullan
    if (isset($row['baslik_en']) && $row['baslik_en'] !== null && $row['baslik_en'] !== '' && trim($row['baslik_en']) !== '') {
        $urun_baslik = trim($row['baslik_en']);
    }
} elseif ($language == 'ru') {
    // Rusça başlık varsa kullan
    if (isset($row['baslik_ru']) && $row['baslik_ru'] !== null && $row['baslik_ru'] !== '' && trim($row['baslik_ru']) !== '') {
        $urun_baslik = trim($row['baslik_ru']);
    }
}

// Eğer hala boşsa, varsayılan başlığı kullan
if (empty($urun_baslik) && isset($row['baslik'])) {
    $urun_baslik = $row['baslik'];
}
?>
<div class="js-slide products-group">
    <div class="product-item">
        <div class="product-item__outer h-100">
            <div class="product-item__inner px-wd-4 p-2 p-md-3">
                <div class="product-item__body pb-xl-2">
                    <div class="mb-2">
                        <a href="urun/<?php echo $row['sef']; ?>" class="d-block text-center"><img class="img-fluid" src="<?php echo htmlspecialchars($urun_img_src, ENT_QUOTES, 'UTF-8'); ?>" alt=""></a>
                    </div>

                    <h5 class="mb-1 product-item__title"><a href="urun/<?php echo $row['sef']; ?>" class="text-blue font-weight-bold"><?php echo $urun_baslik; ?></a></h5>
                    <?php 
                    // Stok durumunu göster (admin panelinde ayarlanan değer)
                    $stok_durumu = isset($row['stok']) ? (int)$row['stok'] : 0;
                    ?>
                    <div class="mb-1">
                        <span class="badge <?php echo ($stok_durumu == 1) ? 'badge-success' : 'badge-danger'; ?>" style="font-size: 0.8em;">
                            <?php 
                            if ($language == 'ru') {
                                echo ($stok_durumu == 1) ? 'В наличии' : 'Нет в наличии';
                            } elseif ($language == 'en') {
                                echo ($stok_durumu == 1) ? 'In Stock' : 'Out of Stock';
                            } else {
                                echo ($stok_durumu == 1) ? 'Stokta Var' : 'Stokta Yok';
                            }
                            ?>
                        </span>
                    </div>
                    <div class="flex-center-between mb-1">
                        <div>
                            <?php 
                            // Fiyatlandırma bilgilerini hazırla
                            $liste_fiyati_eur = isset($row['liste_fiyati_eur']) && $row['liste_fiyati_eur'] > 0 ? (float)$row['liste_fiyati_eur'] : 0;
                            $liste_fiyati_tl = isset($row['liste_fiyati_tl']) ? (float)$row['liste_fiyati_tl'] : 0;
                            $doviz_kuru = isset($row['doviz_kuru']) && $row['doviz_kuru'] > 0 ? (float)$row['doviz_kuru'] : 35.00;
                            
                            // Eğer liste fiyatı TL yoksa ve döviz kuru varsa hesapla
                            if($liste_fiyati_tl == 0 && $doviz_kuru > 0 && $liste_fiyati_eur > 0){
                                $liste_fiyati_tl = $liste_fiyati_eur * $doviz_kuru;
                            } elseif($liste_fiyati_tl == 0 && $liste_fiyati_eur == 0 && isset($row['fiyat'])){
                                $liste_fiyati_tl = (float)$row['fiyat'];
                            }
                            
                            // Euro fiyatı varsa göster
                            if($liste_fiyati_eur > 0){
                            ?>
                            <div class="prodcut-price" style="float:left;width:100%">
                                <div class="text-gray-100 font-weight-bold" style="font-size: 1.1em;">
                                    € <?php echo number_format($liste_fiyati_eur, 2, ',', '.'); ?>
                                </div>
                                <?php if($liste_fiyati_tl > 0){ ?>
                                <div class="text-gray-100" style="font-size: 0.9em; color: #666;">
                                    <?php echo number_format($liste_fiyati_tl, 2, ',', '.'); ?> ₺
                                </div>
                                <?php } ?>
                            </div>
                            <?php } else { 
                                // Eski sistem - sadece TL fiyatı varsa
                                if(!empty($row['eski_fiyat'])){
                            ?>
                            <div class="prodcut-price" style="float:left;width:100%">
                                <div class="text-gray-100" style="text-decoration: line-through;color:red"><?php echo fiyat($row['fiyat']); ?> TL</div>
                            </div>
                            <?php } ?>
                            <div class="prodcut-price" style="float:left;width:100%">
                                <div class="text-gray-100"><?php echo fiyat($row['fiyat']); ?> TL</div>
                            </div>
                            <?php } ?>
                        </div>
                        <div class="d-none d-xl-block prodcut-add-cart">
                            <a href="urun/<?php echo $row['sef']; ?>" class="btn-add-cart btn-primary transition-3d-hover"><i class="ec ec-add-to-cart"></i></a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>