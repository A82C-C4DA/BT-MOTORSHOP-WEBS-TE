<?php
if (!isset($language)) {
    $language = isset($_COOKIE['site_language']) ? $_COOKIE['site_language'] : 'tr';
}
?>
<style>
/* ===== KMS STYLE ANASAYFA ===== */
.kms-filter {
    position: relative;
    background: #1a2535;
    overflow: hidden;
}
.kms-filter__bg {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
    opacity: .18;
}
.kms-filter__inner {
    position: relative;
    z-index: 1;
    max-width: 960px;
    margin: 0 auto;
    padding: 28px 20px 32px;
}
.kms-filter__tabs {
    display: flex;
    gap: 0;
    margin-bottom: 0;
    border-bottom: none;
}
.kms-filter__tab {
    background: rgba(255,255,255,.12);
    color: rgba(255,255,255,.7);
    border: none;
    padding: 10px 22px;
    font-size: 13px;
    font-weight: 500;
    cursor: pointer;
    border-radius: 6px 6px 0 0;
    margin-right: 3px;
    display: flex;
    align-items: center;
    gap: 7px;
    transition: all .15s;
}
.kms-filter__tab:hover { background: rgba(255,255,255,.2); color: #fff; }
.kms-filter__tab.active { background: #fff; color: #1a2535; font-weight: 600; }
.kms-filter__tab i { font-size: 16px; }
.kms-filter__body {
    background: #fff;
    border-radius: 0 6px 6px 6px;
    padding: 22px 24px;
    display: none;
}
.kms-filter__body.active { display: flex; flex-wrap: wrap; gap: 12px; align-items: flex-end; }
.kms-filter__group {
    display: flex;
    flex-direction: column;
    gap: 6px;
    flex: 1;
    min-width: 160px;
}
.kms-filter__num {
    width: 22px;
    height: 22px;
    background: #e85d04;
    color: #fff;
    border-radius: 50%;
    font-size: 11px;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 2px;
}
.kms-filter__select,
.kms-filter__input {
    width: 100%;
    height: 42px;
    border: 1.5px solid #d8dde5;
    border-radius: 6px;
    padding: 0 12px;
    font-size: 13px;
    color: #333;
    background: #fff;
    outline: none;
    transition: border-color .15s;
}
.kms-filter__select:focus,
.kms-filter__input:focus { border-color: #e85d04; }
.kms-filter__select:disabled { background: #f5f6f8; color: #aaa; cursor: not-allowed; }
.kms-filter__btn {
    height: 42px;
    background: #e85d04;
    color: #fff;
    border: none;
    border-radius: 6px;
    padding: 0 28px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 8px;
    white-space: nowrap;
    transition: background .15s;
}
.kms-filter__btn:hover { background: #d14f00; }
.kms-filter__sep {
    align-self: center;
    font-size: 14px;
    color: #aaa;
    font-weight: 500;
    padding: 0 4px;
}

/* KATEGORILER */
.kms-cats {
    background: #fff;
    padding: 32px 0 24px;
    border-bottom: 1px solid #eee;
}
.kms-cats .container { max-width: 1200px; margin: 0 auto; padding: 0 20px; }
.kms-section-title {
    font-size: 20px;
    font-weight: 700;
    color: #1a2535;
    margin-bottom: 20px;
    padding-bottom: 10px;
    border-bottom: 2px solid #e85d04;
    display: inline-block;
}
.kms-cats__grid {
    display: grid;
    grid-template-columns: repeat(6, 1fr);
    gap: 14px;
}
.kms-cat-item {
    border: 1.5px solid #e8eaf0;
    border-radius: 10px;
    padding: 16px 8px 14px;
    text-align: center;
    cursor: pointer;
    text-decoration: none;
    transition: all .18s;
    background: #fff;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 10px;
}
.kms-cat-item:hover {
    border-color: #e85d04;
    box-shadow: 0 4px 16px rgba(232,93,4,.1);
    transform: translateY(-2px);
}
.kms-cat-item img {
    width: 72px;
    height: 56px;
    object-fit: contain;
}
.kms-cat-item__placeholder {
    width: 72px;
    height: 56px;
    display: flex;
    align-items: center;
    justify-content: center;
}
.kms-cat-item__placeholder i {
    font-size: 32px;
    color: #e85d04;
}
.kms-cat-item span {
    font-size: 12px;
    font-weight: 500;
    color: #333;
    line-height: 1.35;
}
.kms-cat-item__more {
    font-size: 11px;
    color: #e85d04;
    display: flex;
    align-items: center;
    gap: 3px;
    margin-top: -4px;
}

/* BANNER / BLOG SLIDER */
.kms-banners {
    background: #f5f6f8;
    padding: 28px 0;
}
.kms-banners .container { max-width: 1200px; margin: 0 auto; padding: 0 20px; }
.kms-banners__slider {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 14px;
}
.kms-banner-item {
    border-radius: 10px;
    overflow: hidden;
    position: relative;
    min-height: 100px;
    display: block;
    text-decoration: none;
}
.kms-banner-item img {
    width: 100%;
    height: 140px;
    object-fit: cover;
    display: block;
    transition: transform .3s;
}
.kms-banner-item:hover img { transform: scale(1.03); }
.kms-banner-item--solid {
    display: flex;
    flex-direction: column;
    justify-content: center;
    padding: 20px;
    min-height: 140px;
}
.kms-banner-item--dark { background: #1a2535; }
.kms-banner-item--orange { background: #e85d04; }
.kms-banner-item--dark2 { background: #243447; }
.kms-banner-tag { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: .5px; color: rgba(255,255,255,.65); margin-bottom: 4px; }
.kms-banner-title { font-size: 16px; font-weight: 700; color: #fff; margin-bottom: 4px; line-height: 1.3; }
.kms-banner-sub { font-size: 12px; color: rgba(255,255,255,.75); }

/* ÜRÜNLER */
.kms-products {
    background: #fff;
    padding: 28px 0 32px;
}
.kms-products .container { max-width: 1200px; margin: 0 auto; padding: 0 20px; }
.kms-products__head {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 18px;
    padding-bottom: 10px;
    border-bottom: 2px solid #e85d04;
}
.kms-products__head h2 { font-size: 18px; font-weight: 700; color: #1a2535; margin: 0; }
.kms-products__head a { font-size: 12px; color: #e85d04; text-decoration: none; font-weight: 500; }
.kms-products__grid {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 14px;
}
.kms-product {
    border: 1.5px solid #e8eaf0;
    border-radius: 10px;
    overflow: hidden;
    cursor: pointer;
    text-decoration: none;
    transition: all .18s;
    background: #fff;
    display: flex;
    flex-direction: column;
}
.kms-product:hover { border-color: #e85d04; box-shadow: 0 4px 18px rgba(232,93,4,.12); transform: translateY(-2px); }
.kms-product__img {
    background: #f8f9fb;
    height: 140px;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    overflow: hidden;
    padding: 10px;
}
.kms-product__img img { max-width: 100%; max-height: 120px; object-fit: contain; }
.kms-product__badge {
    position: absolute;
    top: 7px;
    left: 7px;
    background: #e85d04;
    color: #fff;
    font-size: 9px;
    font-weight: 700;
    padding: 2px 7px;
    border-radius: 4px;
    text-transform: uppercase;
}
.kms-product__badge--new { background: #1a7a3c; }
.kms-product__info { padding: 10px 12px 12px; flex: 1; display: flex; flex-direction: column; }
.kms-product__name {
    font-size: 12px;
    color: #333;
    line-height: 1.4;
    flex: 1;
    margin-bottom: 6px;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
.kms-product__price { font-size: 16px; font-weight: 700; color: #e85d04; }
.kms-product__old { font-size: 11px; color: #aaa; text-decoration: line-through; margin-left: 5px; }
.kms-product__stock { font-size: 11px; margin-top: 4px; display: flex; align-items: center; gap: 4px; }
.kms-in { color: #1a7a3c; } .kms-out { color: #c0392b; }
.kms-product__btn {
    margin: 0 12px 12px;
    background: #1a2535;
    color: #fff;
    border: none;
    border-radius: 6px;
    padding: 8px;
    font-size: 11px;
    font-weight: 600;
    cursor: pointer;
    text-align: center;
    text-decoration: none;
    display: block;
    transition: background .15s;
}
.kms-product__btn:hover { background: #e85d04; color: #fff; }

/* AVANTAJLAR */
.kms-advantages {
    background: #1a2535;
    padding: 24px 0;
}
.kms-advantages__inner {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
}
.kms-adv-item {
    display: flex;
    align-items: flex-start;
    gap: 14px;
}
.kms-adv-icon {
    width: 48px;
    height: 48px;
    border-radius: 10px;
    background: rgba(232,93,4,.15);
    border: 1px solid rgba(232,93,4,.3);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.kms-adv-icon i { font-size: 22px; color: #e85d04; }
.kms-adv-text h4 { font-size: 14px; font-weight: 700; color: #fff; margin: 0 0 4px; }
.kms-adv-text p { font-size: 12px; color: #7a8a9a; line-height: 1.5; margin: 0; }

/* HİKAYELER */
.kms-stories { background: #fff; border-bottom: 1px solid #eee; }
.kms-stories .container { max-width: 1200px; margin: 0 auto; padding: 12px 20px; }
.kms-stories .item { text-align: center; padding: 0 6px; }
.kms-stories .item a { display: flex; flex-direction: column; align-items: center; gap: 6px; text-decoration: none; }
.kms-stories .item img { width: 64px; height: 64px; border-radius: 50%; border: 2.5px solid #e85d04; object-fit: cover; }
.kms-stories .item span { font-size: 11px; color: #555; font-weight: 500; }

/* RESPONSIVE */
@media (max-width: 1199px) {
    .kms-products__grid { grid-template-columns: repeat(4, 1fr); }
}
@media (max-width: 991px) {
    .kms-cats__grid { grid-template-columns: repeat(4, 1fr); }
    .kms-products__grid { grid-template-columns: repeat(3, 1fr); }
    .kms-advantages__inner { grid-template-columns: repeat(2, 1fr); }
    .kms-banners__slider { grid-template-columns: 1fr 1fr; }
    .kms-filter__tab span { display: none; }
}
@media (max-width: 575px) {
    .kms-cats__grid { grid-template-columns: repeat(3, 1fr); }
    .kms-products__grid { grid-template-columns: repeat(2, 1fr); }
    .kms-advantages__inner { grid-template-columns: 1fr; }
    .kms-banners__slider { grid-template-columns: 1fr; }
    .kms-filter__tabs { flex-wrap: wrap; }
    .kms-filter__group { min-width: 100%; }
}
</style>

<!-- ===== ARAÇ ARAMA HERO ===== -->
<section class="kms-filter">
    <?php
    // Slider varsa arka plan olarak kullan
    $bgQ = $db->query("SELECT img FROM slider ORDER BY sira ASC LIMIT 1", PDO::FETCH_ASSOC);
    $bgImg = $bgQ ? $bgQ->fetchColumn() : null;
    if ($bgImg): ?>
    <img class="kms-filter__bg" src="upload/<?php echo htmlspecialchars($bgImg); ?>" alt="">
    <?php endif; ?>

    <div class="kms-filter__inner">
        <!-- Sekmeler -->
        <div class="kms-filter__tabs">
            <button class="kms-filter__tab active" data-tab="vehicle">
                <i class="ec ec-transport"></i>
                <span><?php echo ($language=='en') ? 'Search by vehicle' : (($language=='ru') ? 'По автомобилю' : 'Araçla Ara'); ?></span>
            </button>
            <button class="kms-filter__tab" data-tab="oem">
                <i class="ec ec-search"></i>
                <span><?php echo ($language=='en') ? 'Search by part no.' : (($language=='ru') ? 'По номеру' : 'Parça No ile'); ?></span>
            </button>
            <button class="kms-filter__tab" data-tab="vin">
                <i class="fa fa-barcode" style="font-size:14px"></i>
                <span><?php echo ($language=='en') ? 'Search by VIN' : (($language=='ru') ? 'По VIN' : 'VIN ile Ara'); ?></span>
            </button>
        </div>

        <!-- Araçla Ara -->
        <div class="kms-filter__body active" id="kms-tab-vehicle">
            <form action="ara" method="post" id="kms-vehicle-form" style="display:contents">
                <div class="kms-filter__group">
                    <span class="kms-filter__num">1</span>
                    <select class="kms-filter__select" id="kms-marka" name="ara">
                        <option value="" disabled selected><?php echo ($language=='en') ? 'Select manufacturer...' : 'Marka seçin...'; ?></option>
                        <?php
                        $markaQ = $db->query("SELECT baslik FROM marka WHERE baslik <> '' ORDER BY baslik ASC", PDO::FETCH_ASSOC);
                        if ($markaQ) { foreach($markaQ as $m) echo '<option value="'.htmlspecialchars($m['baslik']).'">'.htmlspecialchars($m['baslik']).'</option>'; }
                        ?>
                    </select>
                </div>
                <div class="kms-filter__group">
                    <span class="kms-filter__num">2</span>
                    <select class="kms-filter__select" id="kms-model" disabled>
                        <option value=""><?php echo ($language=='en') ? 'Select manufacturer first...' : 'Önce marka seçin...'; ?></option>
                    </select>
                </div>
                <div class="kms-filter__group">
                    <span class="kms-filter__num">3</span>
                    <select class="kms-filter__select" id="kms-motor" name="ara" disabled>
                        <option value=""><?php echo ($language=='en') ? 'Select model first...' : 'Önce model seçin...'; ?></option>
                    </select>
                </div>
                <button type="submit" class="kms-filter__btn">
                    <?php echo ($language=='en') ? 'Search' : 'Ara'; ?> <i class="ec ec-search"></i>
                </button>
            </form>
        </div>

        <!-- Parça No ile Ara -->
        <div class="kms-filter__body" id="kms-tab-oem">
            <form action="ara" method="post" style="display:contents">
                <div class="kms-filter__group" style="flex:2">
                    <input type="text" name="ara" class="kms-filter__input" placeholder="<?php echo ($language=='en') ? 'Enter part number, OEM code or product name...' : 'Parça no, OEM kodu veya ürün adı girin...'; ?>">
                </div>
                <button type="submit" class="kms-filter__btn">
                    <?php echo ($language=='en') ? 'Search' : 'Ara'; ?> <i class="ec ec-search"></i>
                </button>
            </form>
        </div>

        <!-- VIN ile Ara -->
        <div class="kms-filter__body" id="kms-tab-vin">
            <form action="ara" method="post" style="display:contents">
                <div class="kms-filter__group" style="flex:2">
                    <input type="text" name="ara" class="kms-filter__input" placeholder="<?php echo ($language=='en') ? 'Enter chassis / VIN number...' : 'Şasi / VIN numarası girin...'; ?>">
                </div>
                <button type="submit" class="kms-filter__btn">
                    <?php echo ($language=='en') ? 'Search' : 'Ara'; ?> <i class="ec ec-search"></i>
                </button>
            </form>
        </div>
    </div>
</section>

<!-- ===== HİKAYELER ===== -->
<?php
$hikayeQ = $db->query("SELECT * FROM hikaye ORDER BY id DESC", PDO::FETCH_ASSOC);
if ($hikayeQ && $hikayeQ->rowCount()):
?>
<div class="kms-stories">
    <div class="container">
        <div id="hikaye" class="js-slick-carousel u-slick position-static overflow-hidden u-slick-overflow-visble pb-5 pt-2 px-1"
            data-pagi-classes="text-center right-0 bottom-1 left-0 u-slick__pagination u-slick__pagination--long mb-0 z-index-n1 mt-3"
            data-slides-show="10"
            data-slides-scroll="1"
            data-arrows-classes="position-absolute top-0 font-size-17 u-slick__arrow-normal top-10"
            data-arrow-left-classes="fa fa-angle-left right-1"
            data-arrow-right-classes="fa fa-angle-right right-0"
            data-responsive='[{"breakpoint":992,"settings":{"slidesToShow":7}},{"breakpoint":768,"settings":{"slidesToShow":5}},{"breakpoint":576,"settings":{"slidesToShow":4}}]'>
            <?php foreach($hikayeQ as $row): ?>
            <div class="item">
                <a href="javascript:void(0)" data-buyuk-img="<?php echo $row['buyuk_img']; ?>" data-link="<?php echo $row['link']; ?>">
                    <img src="upload/<?php echo $row['kucuk_img']; ?>">
                    <span><?php echo $row['baslik']; ?></span>
                </a>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- ===== KATEGORİLER ===== -->
<?php
$katQ = $db->query("SELECT id, baslik, sef FROM kategori ORDER BY sira ASC, baslik ASC LIMIT 12", PDO::FETCH_ASSOC);
$katList = $katQ ? $katQ->fetchAll(PDO::FETCH_ASSOC) : [];
if (!empty($katList)):
$katIcons = ['fa-cog','fa-bolt','fa-filter','fa-wrench','fa-fire','fa-tachometer','fa-leaf','fa-sun-o','fa-road','fa-cube','fa-shield','fa-circle-o'];
?>
<div class="kms-cats">
    <div class="container">
        <div class="kms-section-title">
            <?php echo ($language=='en') ? 'Category' : (($language=='ru') ? 'Категории' : 'Kategoriler'); ?>
        </div>
        <div class="kms-cats__grid">
            <?php foreach($katList as $ki => $kat):
                $ico = $katIcons[$ki % count($katIcons)];
            ?>
            <a href="kategori/<?php echo $kat['sef']; ?>" class="kms-cat-item">
                <div class="kms-cat-item__placeholder">
                    <i class="fa <?php echo $ico; ?>"></i>
                </div>
                <span><?php echo htmlspecialchars($kat['baslik']); ?></span>
                <div class="kms-cat-item__more">
                    <i class="fa fa-th-large" style="font-size:10px"></i>
                    <span><?php echo ($language=='en') ? 'Show all' : 'Tümü'; ?></span>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- ===== KAMPANYA BANNERLAR ===== -->
<?php
$kampQ = $db->query("SELECT * FROM kampanya WHERE alt_ust = 0 ORDER BY sira ASC LIMIT 3", PDO::FETCH_ASSOC);
$kampList = $kampQ ? $kampQ->fetchAll(PDO::FETCH_ASSOC) : [];

$sliderQ = $db->query("SELECT * FROM slider ORDER BY sira ASC LIMIT 3", PDO::FETCH_ASSOC);
$sliderList = $sliderQ ? $sliderQ->fetchAll(PDO::FETCH_ASSOC) : [];

// Kampanya varsa göster, yoksa slider görselleri kullan
$bannerItems = !empty($kampList) ? $kampList : [];
$bannerClasses = ['kms-banner-item--dark', 'kms-banner-item--orange', 'kms-banner-item--dark2'];

if (!empty($bannerItems) || !empty($sliderList)):
?>
<div class="kms-banners">
    <div class="container">
        <div class="kms-banners__slider">
            <?php
            if (!empty($bannerItems)):
                foreach($bannerItems as $bi => $b):
                    $cls = $bannerClasses[$bi % 3];
            ?>
            <a href="<?php echo $b['link'] ?? '#'; ?>" class="kms-banner-item <?php echo $cls; ?> kms-banner-item--solid">
                <?php if(!empty($b['img'])): ?>
                <img src="upload/<?php echo $b['img']; ?>" alt="" style="position:absolute;inset:0;width:100%;height:100%;object-fit:cover;opacity:.25;border-radius:10px">
                <?php endif; ?>
                <div style="position:relative;z-index:1">
                    <div class="kms-banner-tag"><?php echo ($language=='en') ? 'Campaign' : 'Kampanya'; ?></div>
                    <div class="kms-banner-title"><?php echo htmlspecialchars($b['aciklama'] ?? ''); ?></div>
                </div>
            </a>
            <?php endforeach;
            else:
                // Slider görselleri kullan
                $defBanners = [
                    ['tag'=>($language=='en'?'Daily Deal':'Günün Fırsatı'), 'title'=>($language=='en'?'Brake Systems':'Fren Sistemleri'), 'sub'=>($language=='en'?'Up to 25% off':'%25 indirime kadar'), 'cls'=>'kms-banner-item--dark'],
                    ['tag'=>($language=='en'?'New Arrivals':'Yeni Ürünler'), 'title'=>($language=='en'?'Filter Campaign':'Filtre Kampanyası'), 'sub'=>($language=='en'?'4-piece set price':'4\'lü set fiyatı'), 'cls'=>'kms-banner-item--orange'],
                    ['tag'=>($language=='en'?'Free Shipping':'Ücretsiz Kargo'), 'title'=>($language=='en'?'Orders Over 500 TL':'500 TL Üzeri'), 'sub'=>($language=='en'?'Fast delivery':'Hızlı teslimat'), 'cls'=>'kms-banner-item--dark2'],
                ];
                foreach($defBanners as $b):
            ?>
            <a href="#" class="kms-banner-item <?php echo $b['cls']; ?> kms-banner-item--solid">
                <div class="kms-banner-tag"><?php echo $b['tag']; ?></div>
                <div class="kms-banner-title"><?php echo $b['title']; ?></div>
                <div class="kms-banner-sub"><?php echo $b['sub']; ?></div>
            </a>
            <?php endforeach; endif; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- ===== VİTRİN ÜRÜNLERİ ===== -->
<?php
$vitrinQ = $db->query("SELECT * FROM vitrin ORDER BY sira ASC", PDO::FETCH_ASSOC);
if ($vitrinQ && $vitrinQ->rowCount()):
    foreach($vitrinQ as $vitrin):
        $urunQ = $db->query("SELECT
            u.id, u.baslik, u.baslik_en, u.baslik_ru,
            u.sef, u.eski_fiyat, u.fiyat, u.stok,
            u.liste_fiyati_eur, u.liste_fiyati_tl, u.kredi_karti_fiyati,
            ui.img
            FROM vitrin_urun vu
            INNER JOIN urun u ON vu.urun_id = u.id
            LEFT JOIN urun_img ui ON u.id = ui.urun_id
            WHERE vu.vitrin_id = '{$vitrin['id']}'
            GROUP BY u.id
            ORDER BY vu.sira ASC
            LIMIT 10", PDO::FETCH_ASSOC);
        $urunler = $urunQ ? $urunQ->fetchAll(PDO::FETCH_ASSOC) : [];
        if (empty($urunler)) continue;
?>
<div class="kms-products">
    <div class="container">
        <div class="kms-products__head">
            <h2><?php echo htmlspecialchars($vitrin['baslik']); ?></h2>
            <a href="<?php echo $vitrin['link'] ?? 'urunler'; ?>">
                <?php echo ($language=='en') ? 'Show all →' : 'Tümünü Gör →'; ?>
            </a>
        </div>
        <div class="kms-products__grid">
            <?php foreach($urunler as $row):
                $urun_baslik = $row['baslik'];
                if ($language=='en' && !empty($row['baslik_en'])) $urun_baslik = $row['baslik_en'];
                elseif ($language=='ru' && !empty($row['baslik_ru'])) $urun_baslik = $row['baslik_ru'];

                $img_src = 'upload/no-image.jpg';
                if (!empty($row['img'])) {
                    if (preg_match('#^https?://#', $row['img'])) $img_src = $row['img'];
                    else $img_src = 'upload/'.ltrim(basename($row['img']), '/');
                }

                $fiyat = 0;
                if (!empty($row['kredi_karti_fiyati']) && $row['kredi_karti_fiyati'] > 0) $fiyat = (float)$row['kredi_karti_fiyati'];
                elseif (!empty($row['liste_fiyati_tl']) && $row['liste_fiyati_tl'] > 0) $fiyat = (float)$row['liste_fiyati_tl'];
                elseif (!empty($row['fiyat']) && $row['fiyat'] > 0) $fiyat = (float)$row['fiyat'];
                $eski = !empty($row['eski_fiyat']) ? (float)$row['eski_fiyat'] : 0;
                $indirim = ($eski > $fiyat && $fiyat > 0) ? round((1 - $fiyat/$eski)*100) : 0;
            ?>
            <a href="urun/<?php echo $row['sef']; ?>" class="kms-product">
                <div class="kms-product__img">
                    <img src="<?php echo htmlspecialchars($img_src); ?>" alt="<?php echo htmlspecialchars($urun_baslik); ?>" loading="lazy">
                    <?php if ($indirim > 0): ?>
                    <div class="kms-product__badge">-<?php echo $indirim; ?>%</div>
                    <?php endif; ?>
                </div>
                <div class="kms-product__info">
                    <div class="kms-product__name"><?php echo htmlspecialchars($urun_baslik); ?></div>
                    <?php if ($fiyat > 0): ?>
                    <div>
                        <span class="kms-product__price">₺<?php echo number_format($fiyat, 0, ',', '.'); ?></span>
                        <?php if ($eski > $fiyat): ?>
                        <span class="kms-product__old">₺<?php echo number_format($eski, 0, ',', '.'); ?></span>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    <div class="kms-product__stock">
                        <?php if ($row['stok'] == 1): ?>
                        <i class="fa fa-check-circle kms-in"></i>
                        <span class="kms-in"><?php echo ($language=='en') ? 'In stock' : 'Stokta Var'; ?></span>
                        <?php else: ?>
                        <i class="fa fa-times-circle kms-out"></i>
                        <span class="kms-out"><?php echo ($language=='en') ? 'Out of stock' : 'Stokta Yok'; ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="kms-product__btn">
                    <?php echo ($language=='en') ? 'View product' : 'Ürünü Gör'; ?>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php
    endforeach;
endif;
?>

<!-- ===== ALT KAMPANYA BANNERLAR ===== -->
<?php
$kampAltQ = $db->query("SELECT * FROM kampanya WHERE alt_ust = 1 ORDER BY sira ASC LIMIT 3", PDO::FETCH_ASSOC);
if ($kampAltQ && $kampAltQ->rowCount()):
?>
<div class="kms-banners" style="padding-top:0">
    <div class="container">
        <div class="kms-banners__slider">
            <?php foreach($kampAltQ as $k): ?>
            <a href="<?php echo $k['link']; ?>" class="kms-banner-item" style="border-radius:10px;overflow:hidden">
                <img src="upload/<?php echo $k['img']; ?>" alt="">
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- ===== NEDEN BİZ ===== -->
<div class="kms-advantages">
    <div class="kms-advantages__inner">
        <div class="kms-adv-item">
            <div class="kms-adv-icon"><i class="fa fa-truck"></i></div>
            <div class="kms-adv-text">
                <h4><?php echo ($language=='en') ? 'Fast Delivery' : (($language=='ru') ? 'Быстрая доставка' : 'Hızlı Teslimat'); ?></h4>
                <p><?php echo ($language=='en') ? 'Same day shipping, 1-2 business days' : (($language=='ru') ? 'Отправка в день заказа' : 'Aynı gün kargo, 1-2 iş günü'); ?></p>
            </div>
        </div>
        <div class="kms-adv-item">
            <div class="kms-adv-icon"><i class="fa fa-certificate"></i></div>
            <div class="kms-adv-text">
                <h4><?php echo ($language=='en') ? 'Original Parts' : (($language=='ru') ? 'Оригинальные детали' : 'Orijinal Parça'); ?></h4>
                <p><?php echo ($language=='en') ? '100% original & guaranteed' : (($language=='ru') ? '100% оригинал и гарантия' : '%100 orijinal ve garantili'); ?></p>
            </div>
        </div>
        <div class="kms-adv-item">
            <div class="kms-adv-icon"><i class="fa fa-refresh"></i></div>
            <div class="kms-adv-text">
                <h4><?php echo ($language=='en') ? 'Easy Returns' : (($language=='ru') ? 'Лёгкий возврат' : 'Kolay İade'); ?></h4>
                <p><?php echo ($language=='en') ? '14 day free return policy' : (($language=='ru') ? '14 дней на возврат' : '14 gün ücretsiz iade'); ?></p>
            </div>
        </div>
        <div class="kms-adv-item">
            <div class="kms-adv-icon"><i class="fa fa-headphones"></i></div>
            <div class="kms-adv-text">
                <h4><?php echo ($language=='en') ? 'Technical Support' : (($language=='ru') ? 'Тех. поддержка' : 'Teknik Destek'); ?></h4>
                <p><?php echo ($language=='en') ? 'Expert team always available' : (($language=='ru') ? 'Эксперты всегда на связи' : 'Uzman ekip her zaman hazır'); ?></p>
            </div>
        </div>
    </div>
</div>

<!-- Hikaye Popup -->
<div class="modal fade" id="hikaye-popup" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document" style="width:fit-content">
        <div class="modal-content">
            <div id="saniye" style="width:0;height:10px;background:#f12870;float:left"></div>
            <div class="modal-body p-0">
                <div class="row"><div class="col-md-12" id="icerik"></div></div>
            </div>
        </div>
    </div>
</div>

<script>
// Sekme geçişi
document.querySelectorAll('.kms-filter__tab').forEach(function(btn) {
    btn.addEventListener('click', function() {
        document.querySelectorAll('.kms-filter__tab').forEach(function(b){ b.classList.remove('active'); });
        document.querySelectorAll('.kms-filter__body').forEach(function(b){ b.classList.remove('active'); });
        btn.classList.add('active');
        document.getElementById('kms-tab-' + btn.dataset.tab).classList.add('active');
    });
});

// Marka → Model → Motor cascade
(function() {
    var markaEl = document.getElementById('kms-marka');
    var modelEl = document.getElementById('kms-model');
    var motorEl = document.getElementById('kms-motor');
    if (!markaEl) return;

    markaEl.addEventListener('change', function() {
        var marka = this.value;
        modelEl.innerHTML = '<option value=""><?php echo ($language=='en') ? "Select model first..." : "Model seçin..."; ?></option>';
        motorEl.innerHTML = '<option value=""><?php echo ($language=='en') ? "Select model first..." : "Önce model seçin..."; ?></option>';
        modelEl.disabled = true;
        motorEl.disabled = true;
        if (!marka) return;
        fetch('?ajax=model&marka=' + encodeURIComponent(marka))
            .then(function(r){ return r.json(); })
            .then(function(data) {
                if (data && data.length) {
                    data.forEach(function(m) {
                        var o = document.createElement('option');
                        o.value = m; o.textContent = m;
                        modelEl.appendChild(o);
                    });
                    modelEl.disabled = false;
                }
            }).catch(function(){});
    });

    modelEl.addEventListener('change', function() {
        var marka = markaEl.value, model = this.value;
        motorEl.innerHTML = '<option value=""><?php echo ($language=='en') ? "Select engine..." : "Motor seçin..."; ?></option>';
        motorEl.disabled = true;
        if (!model) return;
        fetch('?ajax=motor&marka=' + encodeURIComponent(marka) + '&model=' + encodeURIComponent(model))
            .then(function(r){ return r.json(); })
            .then(function(data) {
                if (data && data.length) {
                    data.forEach(function(m) {
                        var o = document.createElement('option');
                        o.value = marka + ' ' + model + ' ' + m;
                        o.textContent = m;
                        motorEl.appendChild(o);
                    });
                    motorEl.disabled = false;
                    // Motor seçilince form submit için marka select'i disabled yap
                    motorEl.addEventListener('change', function() {
                        markaEl.removeAttribute('name');
                        motorEl.name = 'ara';
                    });
                }
            }).catch(function(){});
    });
})();
</script>
