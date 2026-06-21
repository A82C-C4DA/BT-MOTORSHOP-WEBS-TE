<?php
// Dil seçeneği yönetimi
$language = isset($_GET['lang']) ? $_GET['lang'] : (isset($_COOKIE['site_language']) ? $_COOKIE['site_language'] : 'en');

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

// Çok dilli alanları da seç
$multilangFields = ", baslik_en, baslik_ru, baslik_fr, baslik_es, baslik_ar, baslik_pl, 
                     kisa_aciklama_en, kisa_aciklama_ru, kisa_aciklama_fr, kisa_aciklama_es, kisa_aciklama_ar, kisa_aciklama_pl,
                     aciklama_en, aciklama_ru, aciklama_fr, aciklama_es, aciklama_ar, aciklama_pl";

$query = $db->prepare("SELECT {$selectFields}{$multilangFields} FROM urun WHERE sef=:sef LIMIT 1");
$query->execute(array(":sef"=>$_GET['sef']));
$urun = $query->fetch(PDO::FETCH_ASSOC);

if(!$urun){
  echo '<meta http-equiv="refresh" content="0;URL=index.php">';
}

if (!function_exists('display_stock_code_without_eryaz_prefix')) {
    function display_stock_code_without_eryaz_prefix($stokKodu) {
        return preg_replace('/^(30-|31-|32-|3e-?)/i', '', trim((string)$stokKodu));
    }
}

// Ürün ismini dil seçimine göre al
$urun_baslik = $urun['baslik'];
$urun_aciklama = $urun['kisa_aciklama'];
$urun_detay = $urun['aciklama'];
$display_stok_kodu = display_stock_code_without_eryaz_prefix(isset($urun['stok_kodu']) ? $urun['stok_kodu'] : '');

// Eğer veritabanında çok dilli alanlar varsa (baslik_en, baslik_ru, kisa_aciklama_en, vb.)
if ($language == 'en') {
    $urun_baslik = (isset($urun['baslik_en']) && trim($urun['baslik_en']) != '') ? $urun['baslik_en'] : $urun['baslik'];
    $urun_aciklama = (isset($urun['kisa_aciklama_en']) && trim($urun['kisa_aciklama_en']) != '') ? $urun['kisa_aciklama_en'] : $urun['kisa_aciklama'];
    $urun_detay = (isset($urun['aciklama_en']) && trim($urun['aciklama_en']) != '') ? $urun['aciklama_en'] : $urun['aciklama'];
} elseif ($language == 'ru') {
    $urun_baslik = (isset($urun['baslik_ru']) && trim($urun['baslik_ru']) != '') ? $urun['baslik_ru'] : $urun['baslik'];
    $urun_aciklama = (isset($urun['kisa_aciklama_ru']) && trim($urun['kisa_aciklama_ru']) != '') ? $urun['kisa_aciklama_ru'] : $urun['kisa_aciklama'];
    $urun_detay = (isset($urun['aciklama_ru']) && trim($urun['aciklama_ru']) != '') ? $urun['aciklama_ru'] : $urun['aciklama'];
} elseif ($language == 'fr') {
    $urun_baslik = (isset($urun['baslik_fr']) && trim($urun['baslik_fr']) != '') ? $urun['baslik_fr'] : $urun['baslik'];
    $urun_aciklama = (isset($urun['kisa_aciklama_fr']) && trim($urun['kisa_aciklama_fr']) != '') ? $urun['kisa_aciklama_fr'] : $urun['kisa_aciklama'];
    $urun_detay = (isset($urun['aciklama_fr']) && trim($urun['aciklama_fr']) != '') ? $urun['aciklama_fr'] : $urun['aciklama'];
} elseif ($language == 'es') {
    $urun_baslik = (isset($urun['baslik_es']) && trim($urun['baslik_es']) != '') ? $urun['baslik_es'] : $urun['baslik'];
    $urun_aciklama = (isset($urun['kisa_aciklama_es']) && trim($urun['kisa_aciklama_es']) != '') ? $urun['kisa_aciklama_es'] : $urun['kisa_aciklama'];
    $urun_detay = (isset($urun['aciklama_es']) && trim($urun['aciklama_es']) != '') ? $urun['aciklama_es'] : $urun['aciklama'];
} elseif ($language == 'ar') {
    $urun_baslik = (isset($urun['baslik_ar']) && trim($urun['baslik_ar']) != '') ? $urun['baslik_ar'] : $urun['baslik'];
    $urun_aciklama = (isset($urun['kisa_aciklama_ar']) && trim($urun['kisa_aciklama_ar']) != '') ? $urun['kisa_aciklama_ar'] : $urun['kisa_aciklama'];
    $urun_detay = (isset($urun['aciklama_ar']) && trim($urun['aciklama_ar']) != '') ? $urun['aciklama_ar'] : $urun['aciklama'];
} elseif ($language == 'pl') {
    $urun_baslik = (isset($urun['baslik_pl']) && trim($urun['baslik_pl']) != '') ? $urun['baslik_pl'] : $urun['baslik'];
    $urun_aciklama = (isset($urun['kisa_aciklama_pl']) && trim($urun['kisa_aciklama_pl']) != '') ? $urun['kisa_aciklama_pl'] : $urun['kisa_aciklama'];
    $urun_detay = (isset($urun['aciklama_pl']) && trim($urun['aciklama_pl']) != '') ? $urun['aciklama_pl'] : $urun['aciklama'];
}

$_title         =  $urun_baslik;
$_description   =  $urun_aciklama;

// SEO için canonical URL ve hreflang etiketleri
$current_url = $site . 'urun/' . $urun['sef'];
$canonical_url = $current_url;
$hreflang_tags = '';

// Hreflang etiketleri için alternatif dilleri hazırla
if (!empty($urun['baslik_en']) || !empty($urun['baslik_ru'])) {
    $hreflang_tags .= '<link rel="alternate" hreflang="tr" href="' . $current_url . '?lang=tr" />' . "\n";
    
    if (!empty($urun['baslik_en'])) {
        $hreflang_tags .= '<link rel="alternate" hreflang="en" href="' . $current_url . '?lang=en" />' . "\n";
        $hreflang_tags .= '<link rel="alternate" hreflang="x-default" href="' . $current_url . '?lang=en" />' . "\n";
    }
    
    if (!empty($urun['baslik_ru'])) {
        $hreflang_tags .= '<link rel="alternate" hreflang="ru" href="' . $current_url . '?lang=ru" />' . "\n";
    }
}

// Canonical URL (mevcut dil versiyonu)
$canonical_url = $current_url . ($language != 'tr' ? '?lang=' . $language : '');

// Open Graph locale
$og_locale = 'tr_TR';
if ($language == 'en') {
    $og_locale = 'en_US';
} elseif ($language == 'ru') {
    $og_locale = 'ru_RU';
} elseif ($language == 'fr') {
    $og_locale = 'fr_FR';
} elseif ($language == 'es') {
    $og_locale = 'es_ES';
} elseif ($language == 'ar') {
    $og_locale = 'ar_SA';
} elseif ($language == 'pl') {
    $og_locale = 'pl_PL';
}

// HTML lang attribute
$html_lang = 'tr';
if ($language == 'en') {
    $html_lang = 'en';
} elseif ($language == 'ru') {
    $html_lang = 'ru';
} elseif ($language == 'fr') {
    $html_lang = 'fr';
} elseif ($language == 'es') {
    $html_lang = 'es';
} elseif ($language == 'ar') {
    $html_lang = 'ar';
} elseif ($language == 'pl') {
    $html_lang = 'pl';
}

// Product schema (JSON-LD)
$product_image = '';
$img_query = $db->query("SELECT img FROM urun_img WHERE urun_id = '{$urun['id']}' ORDER BY id ASC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
if ($img_query && !empty($img_query['img'])) {
    $product_image = $site . 'upload/' . $img_query['img'];
}

$product_description = trim(strip_tags($urun_aciklama));
if ($product_description === '') {
    $product_description = trim(strip_tags($urun_detay));
}

$product_schema = [
    '@context' => 'https://schema.org',
    '@type' => 'Product',
    'name' => $urun_baslik,
    'description' => $product_description,
    'sku' => $display_stok_kodu,
    'url' => $canonical_url,
    'image' => $product_image !== '' ? [$product_image] : [],
    'offers' => [
        '@type' => 'Offer',
        'priceCurrency' => 'TRY',
        'price' => isset($urun['fiyat']) ? (string)((float)$urun['fiyat']) : '0',
        'availability' => ((int)$urun['stok'] > 0) ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
        'url' => $canonical_url
    ]
];

$json_ld_schema = '<script type="application/ld+json">' . json_encode($product_schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '</script>';

?>
<main id="content" role="main">
    <!-- breadcrumb -->
    <div class="bg-gray-13 bg-md-transparent">
        <div class="container">
            <!-- breadcrumb -->
            <div class="my-md-3">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-3 flex-nowrap flex-xl-wrap overflow-auto overflow-xl-visble">
                        <li class="breadcrumb-item flex-shrink-0 flex-xl-shrink-1"><a href="index.php"><?php echo t('home', $language); ?></a></li>
                        <li class="breadcrumb-item flex-shrink-0 flex-xl-shrink-1 active" aria-current="page"><?php echo $urun_baslik; ?></li>
                    </ol>
                </nav>
            </div>
            <!-- End breadcrumb -->
        </div>
    </div>
    <!-- End breadcrumb -->
    <div class="container">
    <!-- Single Product Body -->
    <div class="mb-xl-14 mb-6">
        <div class="row">
            <div class="col-md-5 mb-4 mb-md-0">
                <div id="sliderSyncingNav" class="js-slick-carousel u-slick mb-2"
                    data-infinite="true"
                    data-arrows-classes="d-none d-lg-inline-block u-slick__arrow-classic u-slick__arrow-centered--y rounded-circle"
                    data-arrow-left-classes="fas fa-arrow-left u-slick__arrow-classic-inner u-slick__arrow-classic-inner--left ml-lg-2 ml-xl-4"
                    data-arrow-right-classes="fas fa-arrow-right u-slick__arrow-classic-inner u-slick__arrow-classic-inner--right mr-lg-2 mr-xl-4"
                    data-nav-for="#sliderSyncingThumb">
                    <?php
                        $alt_img = '';
                        $img_alt = htmlspecialchars($urun_baslik, ENT_QUOTES, 'UTF-8');
                        $query = $db->query("SELECT * FROM urun_img WHERE urun_id = '{$urun['id']}' ", PDO::FETCH_ASSOC);
                        if($query->rowCount()){
                          foreach($query as $row){
                            $imgSrc = 'upload/'.$row['img'];
                            echo '<div class="js-slide">
                                    <a class="js-fancybox d-block" href="'.$imgSrc.'" data-fancybox="urun-galeri" data-caption="'.$img_alt.'" style="cursor: zoom-in;">
                                      <img class="img-fluid" src="'.$imgSrc.'" alt="'.$img_alt.'">
                                    </a>
                                  </div>';
                            $alt_img.= '<div class="js-slide" style="cursor: pointer;"><img class="img-fluid" src="'.$imgSrc.'" alt="'.$img_alt.'"></div>';
                          }
                        }
                    ?>
                </div>

                <div id="sliderSyncingThumb" class="js-slick-carousel u-slick u-slick--slider-syncing u-slick--slider-syncing-size u-slick--gutters-1 u-slick--transform-off"
                    data-infinite="true"
                    data-slides-show="5"
                    data-is-thumbs="true"
                    data-nav-for="#sliderSyncingNav">
                    <?php echo $alt_img; ?>
                </div>
            </div>
            <div class="col-md-7 mb-md-6 mb-lg-0">
                <div class="mb-2">
                    <div class="border-bottom mb-3 pb-md-1 pb-3">
                        <h1 class="font-size-25 text-lh-1dot2"><?php echo $urun_baslik; ?></h1>
                        <div class="d-md-flex align-items-center">
                            <?php 
                            if($urun['marka_id'] != 0){
                                $marka = $db->query("SELECT * FROM marka WHERE id = '{$urun['marka_id']}' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
                            ?>
                            <a href="#" class="max-width-150 ml-n2 mb-2 mb-md-0 d-block"><img class="img-fluid" src="upload/<?php echo $marka['img']; ?>" alt=""></a>
                            <?php } ?>
                        </div>
                    </div>
                    <div class="mb-2">
                        <?php echo $urun_aciklama; ?>
                    </div>
                    <p><strong><?php echo t('stock_code', $language); ?></strong>: <?php echo htmlspecialchars($display_stok_kodu, ENT_QUOTES, 'UTF-8'); ?></p>
                    
                    <?php 
                    // Stok durumunu DOĞRUDAN veritabanından al (admin panelinde ayarlanan değer)
                    // Depo stoklarına göre hesaplama YAPMA!
                    $genel_stok_durumu = isset($urun['stok']) ? (int)$urun['stok'] : 0;
                    ?>
                    
                    <p><strong><?php echo t('stock_status', $language); ?></strong>: <span class="badge <?php echo ($genel_stok_durumu == 1) ? 'badge-success' : 'badge-danger'; ?>" style="font-size: 0.9em; padding: 4px 8px;">
                        <?php echo ($genel_stok_durumu == 1) ? t('in_stock', $language) : t('out_of_stock', $language); ?>
                    </span></p>
                    
                    <div class="mb-4">
                        <div class="d-flex align-items-baseline">
                            <?php 
                            // Fiyatlandırma bilgilerini hazırla
                            $liste_fiyati_eur = isset($urun['liste_fiyati_eur']) && $urun['liste_fiyati_eur'] > 0 ? (float)$urun['liste_fiyati_eur'] : (isset($urun['fiyat']) ? (float)$urun['fiyat'] : 0);
                            $liste_fiyati_tl = isset($urun['liste_fiyati_tl']) ? (float)$urun['liste_fiyati_tl'] : 0;
                            $iskonto_orani = isset($urun['iskonto_orani']) && $urun['iskonto_orani'] !== '' ? (float)$urun['iskonto_orani'] : 0;
                            if ($iskonto_orani < 0) {
                                $iskonto_orani = 0;
                            }
                            if ($iskonto_orani > 100) {
                                $iskonto_orani = 100;
                            }
                            $doviz_kuru = isset($urun['doviz_kuru']) ? (float)$urun['doviz_kuru'] : 0;
                            $kdv_orani = isset($urun['kdv']) ? (float)$urun['kdv'] : 0;
                            $kredi_karti_fiyati = isset($urun['kredi_karti_fiyati']) ? (float)$urun['kredi_karti_fiyati'] : 0;
                            $pesin_odeme_fiyati = isset($urun['pesin_odeme_fiyati']) ? (float)$urun['pesin_odeme_fiyati'] : 0;

                            // Liste TL yok, Euro var, kayıtlı kur yok/0: TCMB ile hesapla (iskonto girilmese bile tablo dolu olsun)
                            if ($liste_fiyati_tl <= 0 && $liste_fiyati_eur > 0 && $doviz_kuru <= 0) {
                                $tcmbPath = __DIR__ . '/../get-tcmb-euro-rate.php';
                                if (is_file($tcmbPath) && !function_exists('getTCMBEuroRate')) {
                                    require_once $tcmbPath;
                                }
                                if (function_exists('getTCMBEuroRate')) {
                                    $doviz_kuru = getTCMBEuroRate();
                                }
                                if (!$doviz_kuru || $doviz_kuru <= 0) {
                                    $doviz_kuru = 35.0;
                                }
                            }
                            
                            // Eğer liste fiyatı TL yoksa ve döviz kuru varsa hesapla
                            if($liste_fiyati_tl == 0 && $doviz_kuru > 0 && $liste_fiyati_eur > 0){
                                $liste_fiyati_tl = $liste_fiyati_eur * $doviz_kuru;
                            } elseif($liste_fiyati_tl == 0 && $liste_fiyati_eur == 0 && isset($urun['fiyat'])){
                                $liste_fiyati_tl = (float)$urun['fiyat'];
                            }
                            
                            // KDV her zaman %20
                            $kdv_orani = 20;
                            
                            // Net Döviz Fiyatı = Liste Fiyatı Euro × (1 - İskonto Oranı / 100)
                            $net_doviz_fiyati = $liste_fiyati_eur * (1 - $iskonto_orani / 100);
                            
                            // Hesaplamalar (KDV'siz Net Fiyat = Liste Fiyatı TL × (1 - İskonto Oranı))
                            if($liste_fiyati_tl > 0){
                                $kdvsiz_net_fiyat = $liste_fiyati_tl * (1 - $iskonto_orani / 100);
                            } else {
                                // Eğer liste fiyatı TL yoksa, Euro'dan hesapla
                                $kdvsiz_net_fiyat = $net_doviz_fiyati * $doviz_kuru;
                            }
                            
                            // Net Fiyat KDV Dahil = KDV'siz Net Fiyat × 1.20
                            $net_fiyat_kdv_dahil = $kdvsiz_net_fiyat * 1.20;
                            
                            // Kredi Kartı ile Fiyat = Net Fiyat KDV Dahil (her zaman)
                            $kredi_karti_fiyati = $net_fiyat_kdv_dahil;
                            
                            // Peşin Ödeme ile Fiyat = Net Fiyat KDV Dahil × 0.95 (%5 az)
                            $pesin_odeme_fiyati = $net_fiyat_kdv_dahil * 0.95;
                            ?>
                            <div style="position: relative; display: inline-block;" id="fiyat_container">
                                <div class="d-flex align-items-center">
                                    <ins class="font-size-36 text-decoration-none" data-guncel-fiyat="<?php echo $liste_fiyati_eur; ?>" id="urun_fiyat" style="cursor: pointer;">
                                        € <?php echo number_format($liste_fiyati_eur, 2, ',', '.'); ?>
                                    </ins>
                                    <i class="fa fa-info-circle ml-2" id="fiyat_info_icon" style="cursor: pointer; color: #007bff; font-size: 1.2em;" title="<?php echo t('hover_for_details', $language); ?>"></i>
                                    <small class="ml-2 text-muted" id="fiyat_info_text" style="font-size: 0.85em; cursor: pointer;">(<?php echo t('hover_for_details', $language); ?>)</small>
                                </div>
                                <div id="fiyat_tooltip" style="display: none; position: absolute; background: white; border: 2px solid #007bff; border-radius: 8px; padding: 15px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); z-index: 10000; min-width: 300px; top: 0; left: 100%; margin-left: 15px;">
                                    <table class="table table-sm table-bordered mb-0" style="font-size: 0.9em;">
                                        <tr>
                                            <td style="font-weight: 600; padding: 8px;"><?php echo t('list_price_eur', $language); ?></td>
                                            <td style="padding: 8px; text-align: right;">€ <?php echo number_format($liste_fiyati_eur, 2, ',', '.'); ?></td>
                                        </tr>
                                        <tr>
                                            <td style="font-weight: 600; padding: 8px;"><?php echo t('list_price_tl', $language); ?></td>
                                            <td style="padding: 8px; text-align: right;"><?php echo number_format($liste_fiyati_tl, 2, ',', '.'); ?> ₺</td>
                                        </tr>
                                        <tr>
                                            <td style="font-weight: 600; padding: 8px;"><?php echo t('discount_rate', $language); ?></td>
                                            <td style="padding: 8px; text-align: right;">%<?php echo number_format($iskonto_orani, 2, ',', '.'); ?></td>
                                        </tr>
                                        <tr>
                                            <td style="font-weight: 600; padding: 8px;"><?php echo t('net_currency_price', $language); ?></td>
                                            <td style="padding: 8px; text-align: right;">€ <?php echo number_format($net_doviz_fiyati, 2, ',', '.'); ?></td>
                                        </tr>
                                        <tr>
                                            <td style="font-weight: 600; padding: 8px;"><?php echo t('net_price_no_tax', $language); ?></td>
                                            <td style="padding: 8px; text-align: right;"><?php echo number_format($kdvsiz_net_fiyat, 2, ',', '.'); ?> ₺</td>
                                        </tr>
                                        <tr>
                                            <td style="font-weight: 600; padding: 8px;"><?php echo t('tax', $language); ?></td>
                                            <td style="padding: 8px; text-align: right;">%20</td>
                                        </tr>
                                        <tr>
                                            <td style="font-weight: 600; padding: 8px;"><?php echo t('net_price_with_tax', $language); ?></td>
                                            <td style="padding: 8px; text-align: right;"><?php echo number_format($net_fiyat_kdv_dahil, 2, ',', '.'); ?> ₺</td>
                                        </tr>
                                        <tr style="background-color: #f8f9fa;">
                                            <td colspan="2" style="padding: 8px; font-weight: 600; color: #dc3545;">
                                                <i class="fa fa-exclamation-circle"></i> <?php echo t('early_payment_prices', $language); ?>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style="font-weight: 600; padding: 8px;"><?php echo t('credit_card', $language); ?></td>
                                            <td style="padding: 8px; text-align: right;">
                                                <?php echo $net_fiyat_kdv_dahil > 0 ? number_format($kredi_karti_fiyati, 2, ',', '.') . ' ₺' : '-'; ?>
                                                <?php if($net_fiyat_kdv_dahil > 0){ ?>
                                                <br><small class="text-muted"><?php echo t('net_price_with_tax', $language); ?></small>
                                                <?php } ?>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style="font-weight: 600; padding: 8px;"><?php echo t('cash_payment', $language); ?></td>
                                            <td style="padding: 8px; text-align: right;">
                                                <?php echo $net_fiyat_kdv_dahil > 0 ? number_format($pesin_odeme_fiyati, 2, ',', '.') . ' ₺' : '-'; ?>
                                                <?php if($net_fiyat_kdv_dahil > 0){ ?>
                                                <br><small class="text-muted"><?php echo t('net_price_with_tax', $language); ?></small>
                                                <?php } ?>
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                            <?php if(!empty($urun['eski_fiyat'])){ ?><del class="font-size-20 ml-2 text-gray-6">€ <?php echo number_format((float)$urun['eski_fiyat'], 2, ',', '.'); ?></del><?php } ?>
                        </div>
                    </div>
                    <script>
                    (function(){
                        // jQuery yüklenene kadar bekle
                        function initTooltip(){
                            if(typeof jQuery === 'undefined'){
                                setTimeout(initTooltip, 100);
                                return;
                            }
                            
                            var $ = jQuery;
                            var tooltipTimeout;
                            
                            function showTooltip(){
                                clearTimeout(tooltipTimeout);
                                var tooltip = $('#fiyat_tooltip');
                                tooltip.css('display', 'block').hide().fadeIn(200);
                                
                                // Tooltip konumunu ayarla
                                var container = $('#fiyat_container');
                                var fiyatElement = $('#urun_fiyat');
                                var offset = container.offset();
                                var fiyatHeight = fiyatElement.outerHeight();
                                var tooltipWidth = tooltip.outerWidth();
                                var tooltipHeight = tooltip.outerHeight();
                                var windowWidth = $(window).width();
                                var windowHeight = $(window).height();
                                var scrollTop = $(window).scrollTop();
                                
                                // Varsayılan: sağda göster
                                var leftPos = '100%';
                                var rightPos = 'auto';
                                var topPos = '0';
                                var marginLeft = '15px';
                                var marginRight = 'auto';
                                
                                // Eğer tooltip sağa taşacaksa sola hizala
                                if(offset.left + container.outerWidth() + tooltipWidth + 15 > windowWidth - 20){
                                    leftPos = 'auto';
                                    rightPos = '100%';
                                    marginLeft = 'auto';
                                    marginRight = '15px';
                                }
                                
                                // Eğer tooltip aşağı taşacaksa yukarı hizala
                                if(offset.top + tooltipHeight > scrollTop + windowHeight - 20){
                                    topPos = 'auto';
                                    var bottomPos = '0';
                                    tooltip.css({
                                        'top': topPos,
                                        'bottom': bottomPos,
                                        'left': leftPos,
                                        'right': rightPos,
                                        'margin-left': marginLeft,
                                        'margin-right': marginRight
                                    });
                                } else {
                                    tooltip.css({
                                        'top': topPos,
                                        'bottom': 'auto',
                                        'left': leftPos,
                                        'right': rightPos,
                                        'margin-left': marginLeft,
                                        'margin-right': marginRight
                                    });
                                }
                            }
                            
                            function hideTooltip(){
                                clearTimeout(tooltipTimeout);
                                tooltipTimeout = setTimeout(function(){
                                    $('#fiyat_tooltip').fadeOut(200);
                                }, 200);
                            }
                            
                            // Sadece fiyat ve tooltip üzerinde hover
                            $('#urun_fiyat, #fiyat_info_icon, #fiyat_info_text, #fiyat_tooltip').on('mouseenter', function(){
                                clearTimeout(tooltipTimeout);
                                showTooltip();
                            });
                            
                            $('#urun_fiyat, #fiyat_info_icon, #fiyat_info_text, #fiyat_tooltip').on('mouseleave', function(){
                                hideTooltip();
                            });
                        }
                        
                        // Sayfa yüklendiğinde veya jQuery hazır olduğunda çalıştır
                        if(document.readyState === 'loading'){
                            document.addEventListener('DOMContentLoaded', initTooltip);
                        } else {
                            initTooltip();
                        }
                    })();
                    </script>
                    <?php
                      $query = $db->query("SELECT * FROM urun_renk WHERE urun_id = '{$urun['id']}' ", PDO::FETCH_ASSOC);
                      if($query->rowCount()){
                    ?>
                    <div class="border-top border-bottom py-3 mb-4">
                        <div class="d-flex align-items-center">
                            <h6 class="font-size-14 mb-0"><?php 
                                if ($language == 'ru') echo 'Цвет';
                                elseif ($language == 'en') echo 'Color';
                                else echo t('color', $language);
                            ?></h6>
                            <!-- Select -->
                            <select class="js-select selectpicker dropdown-select ml-3"
                                data-style="btn-sm bg-white font-weight-normal py-2 border" id="renk">
                                <?php 
                                foreach( $query as $row1 ){
                                    $u = $db->query("SELECT sef, baslik, baslik_en, baslik_ru FROM urun WHERE id = '{$row1['renk_urun_id']}' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
                                // Renk ürün başlığını dil seçimine göre al
                                $renk_urun_baslik = isset($u['baslik']) ? $u['baslik'] : '';
                                if ($language == 'en' && isset($u['baslik_en']) && trim($u['baslik_en']) != '') {
                                    $renk_urun_baslik = $u['baslik_en'];
                                } elseif ($language == 'ru' && isset($u['baslik_ru']) && trim($u['baslik_ru']) != '') {
                                    $renk_urun_baslik = $u['baslik_ru'];
                                }
                                ?>
                                <option value="urun/<?php echo $u['sef']; ?>"><?php echo $renk_urun_baslik; ?></option>
                                <?php } ?>
                            </select>
                            <!-- End Select -->
                        </div>
                    </div>
                    <?php } ?>

                    <?php
                      $query = $db->query("SELECT * FROM urun_secenek WHERE urun_id = '{$urun['id']}' ", PDO::FETCH_ASSOC);
                      if($query->rowCount()){
                        foreach( $query as $row ){
                    ?>
                    <div class="row varyant" style="margin-bottom:20px">
                        <div class="col-md-12">
                            <strong><?php echo $row['baslik']; ?></strong>
                        </div>
                        <div class="col-md-12">
                            <ul>
                                <?php
                                  $query1 = $db->query("SELECT * FROM urun_secenek_alt WHERE urun_secenek_id = '{$row['id']}' ORDER BY id ASC", PDO::FETCH_ASSOC);
                                  if($query1->rowCount()){
                                    foreach( $query1 as $row1 ){
                                        echo '<li data-stok="'.$row1['stok'].'" data-fiyat="'.$row1['fiyat'].'" data-secenek-id="'.$row1['id'].'">'.$row1['baslik'].'</li>';
                                    }
                                  }
                                ?>
                            </ul>
                        </div>
                    </div>
                    <?php } } ?>

                    <div class="row">
                        <div id="sepete_ekle_durum"></div>
                    </div>

                    <div class="d-md-flex align-items-end mb-3">
                        <div class="max-width-150 mb-4 mb-md-0">
                            <!-- Quantity -->
                            <div class="border rounded-pill py-2 px-3 border-color-1">
                                <div class="js-quantity row align-items-center">
                                    <div class="col">
                                        <input class="js-result form-control h-auto border-0 rounded p-0 shadow-none" name="adet" type="number" min="1" value="1">
                                    </div>
                                </div>
                            </div>
                            <!-- End Quantity -->
                        </div>
                        <div class="ml-md-3">
                            <button class="btn px-5 btn-primary-dark transition-3d-hover" data-sepete-ekle="<?php echo $urun['id']; ?>"><i class="ec ec-add-to-cart mr-2 font-size-20"></i> <?php 
                                if ($language == 'ru') echo 'Добавить в корзину';
                                elseif ($language == 'en') echo 'Add to Cart';
                                else echo t('add_to_cart', $language);
                            ?></button>
                            <a href="https://api.whatsapp.com/send?phone=9<?php echo $cek['whatsapp']; ?>&amp;text=<?php 
                                $whatsapp_text = '';
                                if ($language == 'ru') $whatsapp_text = urlencode('Здравствуйте, я хочу заказать этот товар: ' . $site.'urun/'.$urun['sef']);
                                elseif ($language == 'en') $whatsapp_text = urlencode('Hello, I would like to order this product: ' . $site.'urun/'.$urun['sef']);
                                else $whatsapp_text = urlencode('Merhaba, bu ürünü sipariş vermek istiyorum: ' . $site.'urun/'.$urun['sef']);
                                echo $whatsapp_text;
                            ?>" class="btn px-5 btn-success transition-3d-hover" data-sepete-ekle="<?php echo $urun['id']; ?>"><i class="ec ec-add-to-cart mr-2 font-size-20"></i> <?php 
                                if ($language == 'ru') echo 'Заказ через WhatsApp';
                                elseif ($language == 'en') echo 'Order via WhatsApp';
                                else echo 'Whatsapp ile Sipariş';
                            ?></a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End Single Product Body -->
    <!-- Single Product Tab -->
    <div class="mb-8">
        <div class="position-relative position-md-static px-md-6">
            <ul class="nav nav-classic nav-tab nav-tab-lg justify-content-xl-center flex-nowrap flex-xl-wrap overflow-auto overflow-xl-visble border-0 pb-1 pb-xl-0 mb-n1 mb-xl-0" id="pills-tab-8" role="tablist">
                <li class="nav-item flex-shrink-0 flex-xl-shrink-1 z-index-2">
                    <a class="nav-link active" id="Jpills-one-example1-tab" data-toggle="pill" href="#Jpills-one-example1" role="tab" aria-controls="Jpills-one-example1" aria-selected="true"><?php echo t('reference_numbers', $language); ?></a>
                </li>
                <li class="nav-item flex-shrink-0 flex-xl-shrink-1 z-index-2">
                    <a class="nav-link" id="Jpills-three-example1-tab" data-toggle="pill" href="#Jpills-three-example1" role="tab" aria-controls="Jpills-three-example1" aria-selected="false"><?php 
                        if ($language == 'ru') echo 'Условия возврата';
                        elseif ($language == 'en') echo 'Return Conditions';
                        else echo 'İade Koşulları';
                    ?></a>
                </li>
                <li class="nav-item flex-shrink-0 flex-xl-shrink-1 z-index-2">
                    <a class="nav-link" id="Jpills-four-example1-tab" data-toggle="pill" href="#Jpills-four-example1" role="tab" aria-controls="Jpills-four-example1" aria-selected="false"><?php 
                        if ($language == 'ru') echo 'Отзывы о товаре';
                        elseif ($language == 'en') echo 'Product Reviews';
                        else echo 'Ürün Yorumları';
                    ?></a>
                </li>
            </ul>
        </div>
        <!-- Tab Content -->
        <style type="text/css">
            #Jpills-one-example1 *{max-width: 85%;}
        </style>
        <div class="borders-radius-17 border p-4 mt-4 mt-md-0 px-lg-10 py-lg-9">
            <div class="tab-content" id="Jpills-tabContent">
                <div class="tab-pane fade active show" id="Jpills-one-example1" role="tabpanel" aria-labelledby="Jpills-one-example1-tab">
                    <?php
                    // Referans numaralarını çek
                    try {
                        $referanslar = $db->query("SELECT * FROM urun_referans WHERE urun_id = '{$urun['id']}' ORDER BY sira ASC, id ASC", PDO::FETCH_ASSOC);
                        if($referanslar && $referanslar->rowCount() > 0){
                            echo '<div class="referans-numaralari">';
                            echo '<h4 class="mb-4" style="font-weight: bold; font-size: 1.5em;">' . t('reference_numbers', $language) . '</h4>';
                            echo '<div class="table-responsive">';
                            echo '<table class="table" style="background-color: #f5f5f5; border-collapse: collapse; width: 100%;">';
                            echo '<tbody>';
                            
                            // Tüm referansları listele
                            $tum_referanslar = [];
                            foreach($referanslar as $ref){
                                $tum_referanslar[] = [
                                    'marka' => $ref['marka_adi'],
                                    'no' => $ref['referans_no']
                                ];
                            }
                            
                            // 3 sütunlu grid yapısı
                            $satir_sayisi = ceil(count($tum_referanslar) / 3);
                            
                            for($i = 0; $i < $satir_sayisi; $i++){
                                echo '<tr style="border-bottom: 1px solid #ddd;">';
                                for($j = 0; $j < 3; $j++){
                                    $index = ($i * 3) + $j;
                                    $border_right = ($j < 2) ? 'border-right: 1px solid #ddd;' : '';
                                    echo '<td style="padding: 12px 15px; vertical-align: middle; '.$border_right.'">';
                                    if(isset($tum_referanslar[$index])){
                                        $ref = $tum_referanslar[$index];
                                        
                                        // Marka adı varsa badge göster
                                        if(!empty($ref['marka'])){
                                            echo '<span style="background-color: #cd853f; color: white; padding: 3px 8px; border-radius: 3px; font-weight: 600; font-size: 0.85em; margin-right: 5px; display: inline-block;">'.$ref['marka'].' #</span>';
                                        }
                                        
                                        // Referans numarası (mavi, altı çizili)
                                        echo '<a href="#" style="color: #007bff; text-decoration: underline; font-size: 0.95em;" onclick="return false;">'.$ref['no'].'</a>';
                                    }
                                    echo '</td>';
                                }
                                echo '</tr>';
                            }
                            
                            echo '</tbody>';
                            echo '</table>';
                            echo '</div>';
                            echo '</div>';
                        } else {
                            // Referans yoksa eski açıklamayı göster
                            echo $urun_detay;
                        }
                    } catch (Exception $e) {
                        // Tablo yoksa eski açıklamayı göster
                        echo $urun_detay;
                    }
                    ?>
                </div>
                <div class="tab-pane fade" id="Jpills-three-example1" role="tabpanel" aria-labelledby="Jpills-three-example1-tab">
                    <?php
                        $iade_kosullari = $db->query("SELECT * FROM sayfa WHERE sef = 'iade-kosullari' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
                        echo $iade_kosullari['aciklama'];
                    ?>
                </div>
                <div class="tab-pane fade" id="Jpills-four-example1" role="tabpanel" aria-labelledby="Jpills-four-example1-tab">
                    <?php if(isset($_SESSION['kullanici']['login'])){ ?>
                    <div class="row mb-8">
                        <div class="col-md-12">
                            <h3 class="font-size-18 mb-5"><?php echo t('add_review', $language); ?></h3>
                            <!-- Form -->
                            <form class="js-validate">
                                <div class="js-form-message form-group mb-3 row">
                                    <div class="col-md-4 col-lg-3">
                                        <label for="descriptionTextarea" class="form-label"><?php echo t('your_review', $language); ?></label>
                                    </div>
                                    <div class="col-md-8 col-lg-9">
                                        <textarea class="form-control" name="yorum" rows="3" id="descriptionTextarea"
                                        data-msg="Please enter your message."
                                        data-error-class="u-has-error"
                                        data-success-class="u-has-success"></textarea>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="offset-md-4 offset-lg-3 col-auto">
                                        <button type="submit" class="btn btn-primary-dark btn-wide transition-3d-hover"><?php echo t('add_review', $language); ?></button>
                                    </div>
                                </div>
                            </form>
                            <!-- End Form -->
                        </div>
                    </div>
                    <?php } ?>
                    <!-- Review -->

                    <?php
                    $query = $db->query("
                            SELECT
                            kullanici.ad,
                            urun_yorum.yorum,
                            urun_yorum.tarih
                            FROM
                            urun_yorum
                            INNER JOIN kullanici ON urun_yorum.kullanici_id = kullanici.id
                            WHERE
                            urun_yorum.urun_id = '{$urun['id']}' AND
                            urun_yorum.durum = 1
                            ", PDO::FETCH_ASSOC);
                      if($query->rowCount()){
                        foreach($query as $row){
                        echo '
                        <div class="border-bottom border-color-1 pb-4 mb-4">
                            <p class="text-gray-90">'.$row['yorum'].'</p>
                            <div class="mb-2">
                                <strong>'.$row['ad'].'</strong>
                                <span class="font-size-13 text-gray-23">'.date('Y-m-d H:i', $row['tarih']).'</span>
                            </div>
                        </div>';
                        }
                    }else{
                        $no_comment_text = '';
                        if ($language == 'ru') $no_comment_text = 'Отзывы не найдены.';
                        elseif ($language == 'en') $no_comment_text = 'No reviews found.';
                        else $no_comment_text = 'Yorum bulunamadı.';
                        echo '
                        <div class="border-bottom border-color-1 pb-4 mb-4">
                            <p class="text-gray-90">'.$no_comment_text.'</p>
                        </div>';
                    } 
                ?>
                </div>
            </div>
        </div>
        <!-- End Tab Content -->
    </div>
    <!-- End Single Product Tab -->
    <!-- Related products -->
    <div class="mb-6">
        <div class="d-flex justify-content-between align-items-center border-bottom border-color-1 flex-lg-nowrap flex-wrap mb-4">
            <h3 class="section-title mb-0 pb-2 font-size-22"><?php echo t('recommended_products', $language); ?></h3>
        </div>
        <ul class="row list-unstyled products-group no-gutters">
            <?php
            $query = $db->query("SELECT
                                urun.id,
                                urun.baslik,
                                urun.baslik_en,
                                urun.baslik_ru,
                                urun.sef,
                                urun.fiyat,
                                urun.eski_fiyat,
                                urun.stok,
                                urun.liste_fiyati_eur,
                                urun.liste_fiyati_tl,
                                urun.doviz_kuru,
                                urun_img.img
                                FROM
                                urun
                                INNER JOIN urun_img ON urun.id = urun_img.urun_id
                                GROUP BY
                                urun_img.urun_id
                                ORDER BY RAND()
                                LIMIT 5
                      ", PDO::FETCH_ASSOC);
            if($query->rowCount()){
              foreach($query as $row){
          ?>
            <li class="col-6 col-md-3 col-xl-2gdot4-only col-wd-2 product-item">
                <?php include 'inc/urun-sabit.php'; ?>
            </li>
            <?php } } ?>
        </ul>
    </div>
    <!-- End Related products -->
    </div>
</main>