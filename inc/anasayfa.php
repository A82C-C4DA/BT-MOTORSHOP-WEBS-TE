<?php
// Dil kontrolü
if (!isset($language)) {
    $language = isset($_COOKIE['site_language']) ? $_COOKIE['site_language'] : 'tr';
}
?>
<style>
/* ===== BT MOTORSHOP - YENİ ANA SAYFA ===== */
:root {
    --bt-dark:    #0f1923;
    --bt-dark2:   #1a2535;
    --bt-dark3:   #1e2d3d;
    --bt-dark4:   #243447;
    --bt-border:  #2e3d4d;
    --bt-muted:   #6b7a8d;
    --bt-orange:  #e85d04;
    --bt-orange2: #ff7a2b;
}

/* TOPBAR */
.bt-topbar {
    background: var(--bt-orange);
    padding: 6px 0;
}
.bt-topbar-inner {
    display: flex;
    justify-content: space-between;
    align-items: center;
    max-width: 1400px;
    margin: 0 auto;
    padding: 0 20px;
}
.bt-topbar span, .bt-topbar a {
    font-size: 12px;
    color: rgba(255,255,255,.9);
    text-decoration: none;
}
.bt-topbar a:hover { color: #fff; }
.bt-topbar-links { display: flex; gap: 16px; align-items: center; }
.bt-lang-btn {
    background: rgba(255,255,255,.2);
    border: none;
    color: #fff;
    padding: 2px 8px;
    border-radius: 3px;
    font-size: 11px;
    cursor: pointer;
    text-decoration: none;
}
.bt-lang-btn:hover, .bt-lang-btn.active { background: rgba(255,255,255,.4); color: #fff; }

/* HERO SEARCH SECTION */
.bt-hero {
    background: var(--bt-dark);
    padding: 28px 0 0;
}
.bt-hero-inner {
    max-width: 1400px;
    margin: 0 auto;
    padding: 0 20px;
    display: grid;
    grid-template-columns: 300px 1fr;
    gap: 20px;
    align-items: stretch;
}

/* Sol: Arama Kartı */
.bt-search-card {
    background: var(--bt-dark2);
    border: 0.5px solid var(--bt-border);
    border-radius: 12px;
    padding: 22px;
    display: flex;
    flex-direction: column;
    gap: 14px;
}
.bt-search-title {
    color: #fff;
    font-size: 15px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 8px;
}
.bt-search-title i { color: var(--bt-orange); font-size: 20px; }
.bt-search-card input,
.bt-search-card select {
    width: 100%;
    background: var(--bt-dark3);
    border: 0.5px solid var(--bt-border);
    border-radius: 8px;
    color: #fff;
    padding: 10px 14px;
    font-size: 13px;
    outline: none;
    height: 44px;
    transition: border-color .2s;
}
.bt-search-card input::placeholder { color: var(--bt-muted); }
.bt-search-card select { color: #ccc; }
.bt-search-card select option { background: var(--bt-dark3); color: #fff; }
.bt-search-card input:focus,
.bt-search-card select:focus { border-color: var(--bt-orange); }
.bt-search-card select:disabled { opacity: .5; cursor: not-allowed; }
.bt-search-btn {
    width: 100%;
    height: 44px;
    background: var(--bt-orange);
    border: none;
    border-radius: 8px;
    color: #fff;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: background .2s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}
.bt-search-btn:hover { background: var(--bt-orange2); }
.bt-search-divider {
    border: none;
    border-top: 0.5px solid var(--bt-border);
    margin: 2px 0;
}
.bt-search-label {
    font-size: 12px;
    color: var(--bt-muted);
    line-height: 1.5;
}

/* Sağ: Slider */
.bt-hero-slider-wrap {
    border-radius: 12px;
    overflow: hidden;
    position: relative;
}
.bt-hero-slider-wrap .js-slick-carousel { height: 100%; }
.bt-hero-slide img {
    width: 100%;
    height: 320px;
    object-fit: cover;
    display: block;
}

/* BRANDS BAR */
.bt-brands-bar {
    background: var(--bt-dark2);
    border-bottom: 0.5px solid var(--bt-border);
    padding: 14px 0;
}
.bt-brands-inner {
    max-width: 1400px;
    margin: 0 auto;
    padding: 0 20px;
    display: flex;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
}
.bt-brands-label {
    font-size: 11px;
    color: var(--bt-muted);
    text-transform: uppercase;
    letter-spacing: .5px;
    white-space: nowrap;
    margin-right: 4px;
}
.bt-brand-pill {
    background: var(--bt-dark3);
    border: 0.5px solid var(--bt-border);
    border-radius: 20px;
    padding: 4px 14px;
    font-size: 12px;
    color: #ccc;
    cursor: pointer;
    text-decoration: none;
    transition: all .2s;
    white-space: nowrap;
}
.bt-brand-pill:hover { border-color: var(--bt-orange); color: var(--bt-orange); }

/* HIKAYELER (story circles) */
.bt-stories-section {
    background: #fff;
    padding: 16px 0 0;
    border-bottom: 1px solid #eee;
}
.bt-stories-inner {
    max-width: 1400px;
    margin: 0 auto;
    padding: 0 20px;
}

/* MAIN CONTENT LAYOUT */
.bt-content {
    background: #f5f6f8;
    padding: 20px 0;
}
.bt-content-inner {
    max-width: 1400px;
    margin: 0 auto;
    padding: 0 20px;
    display: grid;
    grid-template-columns: 240px 1fr;
    gap: 20px;
    align-items: start;
}

/* SIDEBAR */
.bt-sidebar {
    background: #fff;
    border-radius: 12px;
    border: 0.5px solid #e2e5ea;
    overflow: hidden;
    position: sticky;
    top: 20px;
}
.bt-sidebar-header {
    background: var(--bt-dark);
    padding: 14px 16px;
    color: #fff;
    font-size: 13px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 8px;
}
.bt-sidebar-header i { color: var(--bt-orange); font-size: 16px; }
.bt-sidebar-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 16px;
    border-bottom: 0.5px solid #f0f2f5;
    cursor: pointer;
    text-decoration: none;
    border-left: 3px solid transparent;
    transition: all .15s;
}
.bt-sidebar-item:hover {
    background: #fff8f4;
    border-left-color: var(--bt-orange);
}
.bt-sidebar-item i { font-size: 18px; color: #7a8599; }
.bt-sidebar-item span { font-size: 13px; color: #333; flex: 1; }
.bt-sidebar-item .bt-count {
    font-size: 11px;
    color: #aaa;
}
.bt-sidebar-support {
    background: var(--bt-dark);
    margin: 0;
    padding: 16px;
}
.bt-sidebar-support p { font-size: 12px; color: var(--bt-muted); line-height: 1.5; margin-bottom: 10px; }
.bt-sidebar-support h4 { font-size: 13px; color: var(--bt-orange); margin-bottom: 6px; display: flex; align-items: center; gap: 6px; }
.bt-support-btn {
    width: 100%;
    background: var(--bt-orange);
    border: none;
    color: #fff;
    padding: 9px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
}
.bt-support-btn:hover { background: var(--bt-orange2); }

/* MAIN AREA */
.bt-main { display: flex; flex-direction: column; gap: 20px; }

/* CAMPAIGN BANNERS */
.bt-banners {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    gap: 12px;
}
.bt-banner {
    border-radius: 10px;
    padding: 18px 16px;
    display: flex;
    flex-direction: column;
    gap: 5px;
    cursor: pointer;
    position: relative;
    overflow: hidden;
    min-height: 90px;
    text-decoration: none;
    border: 0.5px solid rgba(255,255,255,.05);
}
.bt-banner-1 { background: var(--bt-dark); }
.bt-banner-2 { background: var(--bt-dark2); }
.bt-banner-3 { background: var(--bt-orange); }
.bt-banner-tag { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: .5px; }
.bt-banner-1 .bt-banner-tag, .bt-banner-2 .bt-banner-tag { color: var(--bt-orange); }
.bt-banner-3 .bt-banner-tag { color: rgba(255,255,255,.8); }
.bt-banner-title { font-size: 15px; font-weight: 700; color: #fff; }
.bt-banner-sub { font-size: 12px; }
.bt-banner-1 .bt-banner-sub, .bt-banner-2 .bt-banner-sub { color: var(--bt-muted); }
.bt-banner-3 .bt-banner-sub { color: rgba(255,255,255,.85); }
.bt-banner-icon { position: absolute; right: 14px; top: 50%; transform: translateY(-50%); font-size: 44px; opacity: .12; color: #fff; }

/* SECTION HEADER */
.bt-section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-bottom: 10px;
    border-bottom: 2px solid var(--bt-orange);
    margin-bottom: 14px;
}
.bt-section-header h3 { font-size: 16px; font-weight: 700; color: #1a1a2e; }
.bt-section-header a { font-size: 12px; color: var(--bt-orange); text-decoration: none; display: flex; align-items: center; gap: 4px; }

/* CATEGORY GRID */
.bt-cats-grid {
    display: grid;
    grid-template-columns: repeat(6, 1fr);
    gap: 10px;
}
.bt-cat-card {
    background: #fff;
    border: 0.5px solid #e2e5ea;
    border-radius: 10px;
    padding: 16px 8px;
    text-align: center;
    cursor: pointer;
    text-decoration: none;
    transition: all .2s;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
}
.bt-cat-card:hover { border-color: var(--bt-orange); background: #fff8f4; transform: translateY(-2px); }
.bt-cat-icon {
    width: 44px;
    height: 44px;
    background: #fff0e6;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
}
.bt-cat-icon i { font-size: 22px; color: var(--bt-orange); }
.bt-cat-card span { font-size: 11px; color: #555; line-height: 1.3; font-weight: 500; }

/* PRODUCTS GRID */
.bt-products-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 12px;
}
.bt-product-card {
    background: #fff;
    border: 0.5px solid #e2e5ea;
    border-radius: 10px;
    overflow: hidden;
    cursor: pointer;
    text-decoration: none;
    transition: all .2s;
    display: flex;
    flex-direction: column;
}
.bt-product-card:hover { border-color: var(--bt-orange); box-shadow: 0 4px 20px rgba(232,93,4,.1); transform: translateY(-2px); }
.bt-product-img {
    background: #f5f6f8;
    height: 150px;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    overflow: hidden;
}
.bt-product-img img { max-width: 100%; max-height: 140px; object-fit: contain; }
.bt-product-badge {
    position: absolute;
    top: 8px;
    left: 8px;
    background: var(--bt-orange);
    color: #fff;
    font-size: 10px;
    font-weight: 700;
    padding: 2px 8px;
    border-radius: 4px;
}
.bt-product-badge.new { background: #1a7a3c; }
.bt-product-info { padding: 12px; flex: 1; display: flex; flex-direction: column; gap: 4px; }
.bt-product-brand { font-size: 10px; color: #aaa; text-transform: uppercase; letter-spacing: .5px; }
.bt-product-name { font-size: 12px; color: #333; line-height: 1.4; flex: 1; }
.bt-product-price-row { display: flex; align-items: baseline; gap: 6px; margin-top: 4px; }
.bt-product-price { font-size: 16px; font-weight: 700; color: var(--bt-orange); }
.bt-product-old { font-size: 12px; color: #aaa; text-decoration: line-through; }
.bt-product-stock { font-size: 11px; display: flex; align-items: center; gap: 4px; margin-top: 2px; }
.bt-stock-in { color: #1a7a3c; }
.bt-stock-out { color: #c0392b; }
.bt-product-cart {
    margin: 0 12px 12px;
    background: var(--bt-dark);
    border: none;
    color: #fff;
    border-radius: 6px;
    padding: 8px;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    transition: background .2s;
    text-decoration: none;
}
.bt-product-cart:hover { background: var(--bt-orange); color: #fff; }

/* WHY US */
.bt-why-us {
    background: var(--bt-dark);
    padding: 24px 0;
    margin-top: 0;
}
.bt-why-inner {
    max-width: 1400px;
    margin: 0 auto;
    padding: 0 20px;
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
}
.bt-why-item {
    display: flex;
    align-items: flex-start;
    gap: 14px;
}
.bt-why-icon {
    width: 46px;
    height: 46px;
    border-radius: 10px;
    background: rgba(232,93,4,.15);
    border: 1px solid rgba(232,93,4,.3);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.bt-why-icon i { font-size: 22px; color: var(--bt-orange); }
.bt-why-text h4 { font-size: 14px; font-weight: 700; color: #fff; margin-bottom: 4px; }
.bt-why-text p { font-size: 12px; color: var(--bt-muted); line-height: 1.5; }

/* SLIDER OVERRIDES */
.bt-hero-slider-wrap .slick-dots {
    bottom: 12px !important;
}
.bt-hero-slider-wrap .u-slick__pagination li button::before {
    background: rgba(255,255,255,.5) !important;
}
.bt-hero-slider-wrap .u-slick__pagination li.slick-active button::before {
    background: var(--bt-orange) !important;
}

/* RESPONSIVE */
@media (max-width: 1200px) {
    .bt-banners { grid-template-columns: 1fr 1fr; }
    .bt-cats-grid { grid-template-columns: repeat(4, 1fr); }
    .bt-products-grid { grid-template-columns: repeat(3, 1fr); }
}
@media (max-width: 991px) {
    .bt-hero-inner { grid-template-columns: 1fr; }
    .bt-content-inner { grid-template-columns: 1fr; }
    .bt-sidebar { position: static; display: none; }
    .bt-cats-grid { grid-template-columns: repeat(3, 1fr); }
    .bt-products-grid { grid-template-columns: repeat(2, 1fr); }
    .bt-why-inner { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 576px) {
    .bt-banners { grid-template-columns: 1fr; }
    .bt-cats-grid { grid-template-columns: repeat(3, 1fr); }
    .bt-products-grid { grid-template-columns: repeat(2, 1fr); }
    .bt-why-inner { grid-template-columns: 1fr; }
    .bt-hero-slide img { height: 200px; }
}
</style>

<!-- ===== TOPBAR ===== -->
<div class="bt-topbar">
    <div class="bt-topbar-inner">
        <span><i class="fa fa-truck" style="margin-right:5px"></i>
        <?php echo ($language=='en') ? 'Free shipping over 500 TL' : (($language=='ru') ? 'Бесплатная доставка от 500 TL' : '500 TL üzeri ücretsiz kargo'); ?>
        &nbsp;|&nbsp;
        <i class="fa fa-phone" style="margin-right:5px"></i><?php echo $cek['telefon'] ?? ''; ?>
        </span>
        <div class="bt-topbar-links">
            <a href="?lang=tr" class="bt-lang-btn <?php echo ($language=='tr') ? 'active' : ''; ?>">TR</a>
            <a href="?lang=en" class="bt-lang-btn <?php echo ($language=='en') ? 'active' : ''; ?>">EN</a>
            <a href="?lang=ru" class="bt-lang-btn <?php echo ($language=='ru') ? 'active' : ''; ?>">RU</a>
            &nbsp;|&nbsp;
            <?php if(isset($_SESSION['kullanici']['login'])): ?>
                <a href="hesabim"><i class="fa fa-user" style="margin-right:4px"></i><?php echo ($language=='en') ? 'My Account' : 'Hesabım'; ?></a>
            <?php else: ?>
                <a href="giris-yap"><i class="fa fa-user" style="margin-right:4px"></i><?php echo ($language=='en') ? 'Sign In' : 'Giriş Yap'; ?></a>
                <a href="uye-ol"><?php echo ($language=='en') ? 'Register' : 'Üye Ol'; ?></a>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ===== HERO: ARAÇ ARAMA + SLIDER ===== -->
<div class="bt-hero">
    <div class="bt-hero-inner">

        <!-- Sol: Araç Arama Kartı -->
        <div class="bt-search-card">
            <div class="bt-search-title">
                <i class="fa fa-search-plus"></i>
                <?php echo ($language=='en') ? 'Find Parts for Your Car' : (($language=='ru') ? 'Найти запчасти' : 'Aracınıza Uygun Parça'); ?>
            </div>

            <!-- Şasi ile Ara -->
            <form action="ara" method="post">
                <input type="text" name="ara" placeholder="<?php echo ($language=='en') ? 'Search by VIN / Chassis number' : 'Şasi / VIN numarası ile ara'; ?>">
                <button type="submit" class="bt-search-btn" style="margin-top:8px">
                    <i class="fa fa-search"></i>
                    <?php echo ($language=='en') ? 'Search by VIN' : 'Şasi ile Ara'; ?>
                </button>
            </form>

            <hr class="bt-search-divider">

            <p class="bt-search-label">
                <?php echo ($language=='en') ? 'Or select your vehicle:' : 'Veya araç bilgilerini seçin:'; ?>
            </p>

            <!-- Marka / Model / Motor -->
            <form action="ara" method="post" id="bt-model-form">
                <select name="ara" id="bt-marka" style="margin-bottom:8px">
                    <option value="" selected disabled><?php echo ($language=='en') ? 'Select Brand' : 'Marka Seçin'; ?></option>
                    <?php
                    $markaQ = $db->query("SELECT baslik FROM marka WHERE baslik <> '' ORDER BY baslik ASC", PDO::FETCH_ASSOC);
                    if($markaQ){ foreach($markaQ as $m){ echo '<option value="'.htmlspecialchars($m['baslik']).'">'.htmlspecialchars($m['baslik']).'</option>'; } }
                    ?>
                </select>
                <select id="bt-model" disabled style="margin-bottom:8px">
                    <option value="" selected disabled><?php echo ($language=='en') ? 'Select Model' : 'Model Seçin'; ?></option>
                </select>
                <select id="bt-motor" disabled style="margin-bottom:12px">
                    <option value="" selected disabled><?php echo ($language=='en') ? 'Select Engine' : 'Motor Seçin'; ?></option>
                </select>
                <button type="submit" class="bt-search-btn">
                    <i class="fa fa-cogs"></i>
                    <?php echo ($language=='en') ? 'Find Parts' : 'Parça Ara'; ?>
                </button>
            </form>
        </div>

        <!-- Sağ: Slider -->
        <div class="bt-hero-slider-wrap">
            <?php
            $sliderQ = $db->query("SELECT * FROM slider ORDER BY sira ASC", PDO::FETCH_ASSOC);
            if($sliderQ && $sliderQ->rowCount()):
            ?>
            <div class="js-slick-carousel u-slick"
                data-slides-show="1"
                data-slides-scroll="1"
                data-autoplay="true"
                data-infinite="true"
                data-pagi-classes="text-center position-absolute right-0 bottom-0 left-0 u-slick__pagination justify-content-center mb-3">
                <?php foreach($sliderQ as $row): ?>
                <div class="bt-hero-slide">
                    <a href="<?php echo $row['link']; ?>">
                        <img src="upload/<?php echo $row['img']; ?>" alt="<?php echo htmlspecialchars($row['aciklama']); ?>">
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div style="background:var(--bt-dark2);height:320px;border-radius:12px;display:flex;align-items:center;justify-content:center;flex-direction:column;gap:12px">
                <i class="fa fa-image" style="font-size:48px;color:var(--bt-border)"></i>
                <p style="color:var(--bt-muted);font-size:14px">Slider görsel yükleyin</p>
            </div>
            <?php endif; ?>
        </div>

    </div>

    <!-- Marka Pilleri -->
    <?php
    $markaListQ = $db->query("SELECT baslik FROM marka WHERE baslik <> '' ORDER BY baslik ASC LIMIT 20", PDO::FETCH_ASSOC);
    $markalar = $markaListQ ? $markaListQ->fetchAll(PDO::FETCH_ASSOC) : [];
    if(!empty($markalar)):
    ?>
    <div class="bt-brands-bar">
        <div class="bt-brands-inner">
            <span class="bt-brands-label"><?php echo ($language=='en') ? 'Brands:' : 'Markalar:'; ?></span>
            <?php foreach($markalar as $mk): ?>
            <a href="ara" class="bt-brand-pill"><?php echo htmlspecialchars($mk['baslik']); ?></a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- ===== HİKAYELER ===== -->
<?php
$hikayeQ = $db->query("SELECT * FROM hikaye ORDER BY id DESC", PDO::FETCH_ASSOC);
if($hikayeQ && $hikayeQ->rowCount()):
?>
<div class="bt-stories-section">
    <div class="bt-stories-inner">
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

<!-- ===== ANA İÇERİK ===== -->
<div class="bt-content">
    <div class="bt-content-inner">

        <!-- SIDEBAR: KATEGORİLER -->
        <div class="bt-sidebar">
            <div class="bt-sidebar-header">
                <i class="fa fa-th-list"></i>
                <?php echo ($language=='en') ? 'Categories' : 'Kategoriler'; ?>
            </div>
            <?php
            $katQ = $db->query("SELECT id, baslik, sef FROM kategori ORDER BY baslik ASC LIMIT 15", PDO::FETCH_ASSOC);
            if($katQ){ foreach($katQ as $kat): ?>
            <a href="kategori/<?php echo $kat['sef']; ?>" class="bt-sidebar-item">
                <i class="fa fa-chevron-right" style="font-size:12px;color:#ccc"></i>
                <span><?php echo htmlspecialchars($kat['baslik']); ?></span>
            </a>
            <?php endforeach; } ?>
            <div class="bt-sidebar-support">
                <h4><i class="fa fa-headphones"></i> <?php echo ($language=='en') ? 'Technical Support' : 'Teknik Destek'; ?></h4>
                <p><?php echo ($language=='en') ? 'Need help finding the right part?' : 'Doğru parçayı bulmakta yardım ister misiniz?'; ?></p>
                <a href="iletisim" class="bt-support-btn">
                    <?php echo ($language=='en') ? 'Talk to an Expert' : 'Uzmanla Konuş'; ?>
                </a>
            </div>
        </div>

        <!-- MAIN ALAN -->
        <div class="bt-main">

            <!-- KAMPANYA BANNERLAR (üst) -->
            <?php
            $kampQ = $db->query("SELECT * FROM kampanya WHERE alt_ust = 0 ORDER BY sira ASC", PDO::FETCH_ASSOC);
            $kampList = $kampQ ? $kampQ->fetchAll(PDO::FETCH_ASSOC) : [];
            if(!empty($kampList)):
            ?>
            <div class="bt-banners">
                <?php
                $bannerClasses = ['bt-banner-1', 'bt-banner-2', 'bt-banner-3'];
                $bannerIcons = ['fa-tag', 'fa-bolt', 'fa-truck'];
                foreach($kampList as $i => $kamp):
                    $cls = $bannerClasses[$i % 3];
                    $ico = $bannerIcons[$i % 3];
                ?>
                <a href="<?php echo $kamp['link']; ?>" class="bt-banner <?php echo $cls; ?>">
                    <i class="fa <?php echo $ico; ?> bt-banner-icon"></i>
                    <?php if(!empty($kamp['img'])): ?>
                    <img src="upload/<?php echo $kamp['img']; ?>" style="position:absolute;inset:0;width:100%;height:100%;object-fit:cover;opacity:.35;border-radius:10px">
                    <?php endif; ?>
                    <div style="position:relative;z-index:1">
                        <div class="bt-banner-tag"><?php echo ($language=='en') ? 'Campaign' : 'Kampanya'; ?></div>
                        <div class="bt-banner-title"><?php echo htmlspecialchars($kamp['aciklama'] ?? ''); ?></div>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <!-- Varsayılan banner -->
            <div class="bt-banners">
                <div class="bt-banner bt-banner-1">
                    <i class="fa fa-tag bt-banner-icon"></i>
                    <div class="bt-banner-tag"><?php echo ($language=='en') ? 'Daily Deal' : 'Günün Fırsatı'; ?></div>
                    <div class="bt-banner-title"><?php echo ($language=='en') ? 'Brake Pad Sets' : 'Fren Balata Setleri'; ?></div>
                    <div class="bt-banner-sub"><?php echo ($language=='en') ? 'Up to 25% off' : '%25\'e varan indirim'; ?></div>
                </div>
                <div class="bt-banner bt-banner-2">
                    <i class="fa fa-filter bt-banner-icon"></i>
                    <div class="bt-banner-tag"><?php echo ($language=='en') ? 'New Arrivals' : 'Yeni Gelenler'; ?></div>
                    <div class="bt-banner-title"><?php echo ($language=='en') ? 'Filter Campaign' : 'Filtre Kampanyası'; ?></div>
                    <div class="bt-banner-sub"><?php echo ($language=='en') ? '4-piece set, special price' : '4\'lü set özel fiyatla'; ?></div>
                </div>
                <div class="bt-banner bt-banner-3">
                    <i class="fa fa-truck bt-banner-icon"></i>
                    <div class="bt-banner-tag"><?php echo ($language=='en') ? 'Free Shipping' : 'Ücretsiz Kargo'; ?></div>
                    <div class="bt-banner-title"><?php echo ($language=='en') ? 'Orders Over 500 TL' : '500 TL Üzeri'; ?></div>
                    <div class="bt-banner-sub"><?php echo ($language=='en') ? 'Fast delivery across Turkey' : 'Türkiye geneli hızlı teslimat'; ?></div>
                </div>
            </div>
            <?php endif; ?>

            <!-- KATEGORİ KARTLARI -->
            <?php
            $katGridQ = $db->query("SELECT id, baslik, sef FROM kategori ORDER BY baslik ASC LIMIT 12", PDO::FETCH_ASSOC);
            $katGrid = $katGridQ ? $katGridQ->fetchAll(PDO::FETCH_ASSOC) : [];
            $katIcons = ['fa-cog','fa-bolt','fa-filter','fa-wrench','fa-fire','fa-tachometer','fa-leaf','fa-sun-o','fa-road','fa-cube','fa-shield','fa-circle-o'];
            if(!empty($katGrid)):
            ?>
            <div>
                <div class="bt-section-header">
                    <h3><?php echo ($language=='en') ? 'Shop by Category' : 'Kategoriye Göre Alışveriş'; ?></h3>
                </div>
                <div class="bt-cats-grid">
                    <?php foreach($katGrid as $ki => $kat): $ico = $katIcons[$ki % count($katIcons)]; ?>
                    <a href="kategori/<?php echo $kat['sef']; ?>" class="bt-cat-card">
                        <div class="bt-cat-icon">
                            <i class="fa <?php echo $ico; ?>"></i>
                        </div>
                        <span><?php echo htmlspecialchars($kat['baslik']); ?></span>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- VİTRİN ÜRÜNLERİ -->
            <?php
            $vitrinQ = $db->query("SELECT * FROM vitrin
                WHERE LOWER(baslik) NOT LIKE '%en çok satanlar%'
                AND LOWER(baslik) NOT LIKE '%popüler%'
                AND LOWER(baslik) NOT LIKE '%haftanın%'
                AND LOWER(baslik) NOT LIKE '%fırsat%'
                ORDER BY sira ASC", PDO::FETCH_ASSOC);
            if($vitrinQ && $vitrinQ->rowCount()):
                foreach($vitrinQ as $vitrin):
                    $urunQ = $db->query("SELECT
                        urun.id, urun.baslik, urun.baslik_en, urun.baslik_ru,
                        urun.sef, urun.eski_fiyat, urun.fiyat, urun.stok,
                        urun.liste_fiyati_eur, urun.liste_fiyati_tl,
                        urun.kredi_karti_fiyati, urun.doviz_kuru,
                        urun_img.img
                        FROM vitrin_urun
                        INNER JOIN urun ON vitrin_urun.urun_id = urun.id
                        LEFT JOIN urun_img ON urun.id = urun_img.urun_id
                        WHERE vitrin_urun.vitrin_id = '{$vitrin['id']}'
                        GROUP BY urun.id
                        ORDER BY vitrin_urun.sira ASC
                        LIMIT 8", PDO::FETCH_ASSOC);
                    $urunler = $urunQ ? $urunQ->fetchAll(PDO::FETCH_ASSOC) : [];
                    if(empty($urunler)) continue;
            ?>
            <div>
                <div class="bt-section-header">
                    <h3><?php echo htmlspecialchars($vitrin['baslik']); ?></h3>
                    <a href="<?php echo $vitrin['link'] ?? 'urunler'; ?>">
                        <?php echo ($language=='en') ? 'View All' : 'Tümünü Gör'; ?> <i class="fa fa-arrow-right" style="font-size:11px"></i>
                    </a>
                </div>
                <div class="bt-products-grid">
                    <?php foreach($urunler as $row):
                        // Başlık
                        $urun_baslik = $row['baslik'];
                        if($language=='en' && !empty($row['baslik_en'])) $urun_baslik = $row['baslik_en'];
                        elseif($language=='ru' && !empty($row['baslik_ru'])) $urun_baslik = $row['baslik_ru'];

                        // Görsel
                        $img_src = '../upload/no-image.jpg';
                        if(!empty($row['img'])) {
                            if(preg_match('#^https?://#', $row['img'])) $img_src = $row['img'];
                            else $img_src = 'upload/'.basename($row['img']);
                        }

                        // Fiyat
                        $fiyat = 0;
                        if(!empty($row['kredi_karti_fiyati']) && $row['kredi_karti_fiyati'] > 0) {
                            $fiyat = (float)$row['kredi_karti_fiyati'];
                        } elseif(!empty($row['liste_fiyati_tl']) && $row['liste_fiyati_tl'] > 0) {
                            $fiyat = (float)$row['liste_fiyati_tl'];
                        } elseif(!empty($row['fiyat']) && $row['fiyat'] > 0) {
                            $fiyat = (float)$row['fiyat'];
                        }
                        $eski_fiyat = !empty($row['eski_fiyat']) ? (float)$row['eski_fiyat'] : 0;
                        $indirim = ($eski_fiyat > 0 && $fiyat > 0 && $eski_fiyat > $fiyat) ? round((1 - $fiyat/$eski_fiyat)*100) : 0;
                    ?>
                    <a href="urun/<?php echo $row['sef']; ?>" class="bt-product-card">
                        <div class="bt-product-img">
                            <img src="<?php echo htmlspecialchars($img_src); ?>" alt="<?php echo htmlspecialchars($urun_baslik); ?>" loading="lazy">
                            <?php if($indirim > 0): ?>
                            <div class="bt-product-badge">-<?php echo $indirim; ?>%</div>
                            <?php endif; ?>
                        </div>
                        <div class="bt-product-info">
                            <div class="bt-product-name"><?php echo htmlspecialchars(mb_substr($urun_baslik, 0, 60)); ?></div>
                            <?php if($fiyat > 0): ?>
                            <div class="bt-product-price-row">
                                <span class="bt-product-price">₺<?php echo number_format($fiyat, 0, ',', '.'); ?></span>
                                <?php if($eski_fiyat > 0 && $eski_fiyat > $fiyat): ?>
                                <span class="bt-product-old">₺<?php echo number_format($eski_fiyat, 0, ',', '.'); ?></span>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                            <div class="bt-product-stock">
                                <?php if($row['stok'] == 1): ?>
                                <i class="fa fa-check-circle bt-stock-in"></i>
                                <span class="bt-stock-in"><?php echo ($language=='en') ? 'In Stock' : 'Stokta Var'; ?></span>
                                <?php else: ?>
                                <i class="fa fa-times-circle bt-stock-out"></i>
                                <span class="bt-stock-out"><?php echo ($language=='en') ? 'Out of Stock' : 'Stokta Yok'; ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="bt-product-cart">
                            <i class="fa fa-shopping-cart" style="font-size:13px"></i>
                            <?php echo ($language=='en') ? 'View Product' : 'Ürünü Gör'; ?>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php
                endforeach;
            endif;
            ?>

            <!-- ALT KAMPANYA BANNERLAR -->
            <?php
            $kampAltQ = $db->query("SELECT * FROM kampanya WHERE alt_ust = 1 ORDER BY sira ASC", PDO::FETCH_ASSOC);
            if($kampAltQ && $kampAltQ->rowCount()):
            ?>
            <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px">
                <?php foreach($kampAltQ as $kamp): ?>
                <a href="<?php echo $kamp['link']; ?>" style="display:block;border-radius:10px;overflow:hidden;border:0.5px solid #e2e5ea">
                    <img src="upload/<?php echo $kamp['img']; ?>" alt="" style="width:100%;display:block;object-fit:cover">
                </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

        </div><!-- /bt-main -->
    </div><!-- /bt-content-inner -->
</div><!-- /bt-content -->

<!-- ===== NEDEN BİZ ===== -->
<div class="bt-why-us">
    <div class="bt-why-inner">
        <div class="bt-why-item">
            <div class="bt-why-icon"><i class="fa fa-truck"></i></div>
            <div class="bt-why-text">
                <h4><?php echo ($language=='en') ? 'Fast Delivery' : 'Hızlı Teslimat'; ?></h4>
                <p><?php echo ($language=='en') ? 'Same day shipping, 1-2 business days delivery' : 'Aynı gün kargo, 1-2 iş günü teslimat'; ?></p>
            </div>
        </div>
        <div class="bt-why-item">
            <div class="bt-why-icon"><i class="fa fa-certificate"></i></div>
            <div class="bt-why-text">
                <h4><?php echo ($language=='en') ? 'Original Products' : 'Orijinal Ürün'; ?></h4>
                <p><?php echo ($language=='en') ? '100% original and guaranteed parts' : '%100 orijinal ve garantili parçalar'; ?></p>
            </div>
        </div>
        <div class="bt-why-item">
            <div class="bt-why-icon"><i class="fa fa-refresh"></i></div>
            <div class="bt-why-text">
                <h4><?php echo ($language=='en') ? 'Easy Returns' : 'Kolay İade'; ?></h4>
                <p><?php echo ($language=='en') ? '14 day free return policy' : '14 gün içinde ücretsiz iade hakkı'; ?></p>
            </div>
        </div>
        <div class="bt-why-item">
            <div class="bt-why-icon"><i class="fa fa-headphones"></i></div>
            <div class="bt-why-text">
                <h4><?php echo ($language=='en') ? 'Technical Support' : 'Teknik Destek'; ?></h4>
                <p><?php echo ($language=='en') ? 'Expert team available for help' : 'Uzman kadromuzla 7/24 yardım'; ?></p>
            </div>
        </div>
    </div>
</div>

<!-- Hikaye popup (mevcut kodu koru) -->
<div class="modal fade" id="hikaye-popup" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document" style="width:fit-content">
        <div class="modal-content">
            <div id="saniye" style="width:0;height:10px;background:#f12870;float:left"></div>
            <div class="modal-body" style="padding:0">
                <div class="row"><div class="col-md-12" id="icerik"></div></div>
            </div>
        </div>
    </div>
</div>

<script>
// Marka / Model / Motor cascade
document.addEventListener('DOMContentLoaded', function(){
    var markaEl = document.getElementById('bt-marka');
    var modelEl = document.getElementById('bt-model');
    var motorEl = document.getElementById('bt-motor');
    if(!markaEl) return;

    markaEl.addEventListener('change', function(){
        var marka = this.value;
        modelEl.innerHTML = '<option value="" selected disabled><?php echo ($language=='en') ? "Select Model" : "Model Seçin"; ?></option>';
        motorEl.innerHTML = '<option value="" selected disabled><?php echo ($language=='en') ? "Select Engine" : "Motor Seçin"; ?></option>';
        modelEl.disabled = true;
        motorEl.disabled = true;
        if(!marka) return;
        fetch('?ajax=model&marka='+encodeURIComponent(marka))
            .then(function(r){return r.json();})
            .then(function(data){
                if(data && data.length){
                    data.forEach(function(m){
                        var o = document.createElement('option');
                        o.value = m; o.textContent = m;
                        modelEl.appendChild(o);
                    });
                    modelEl.disabled = false;
                }
            }).catch(function(){});
    });

    modelEl.addEventListener('change', function(){
        var marka = markaEl.value;
        var model = this.value;
        motorEl.innerHTML = '<option value="" selected disabled><?php echo ($language=='en') ? "Select Engine" : "Motor Seçin"; ?></option>';
        motorEl.disabled = true;
        if(!model) return;
        fetch('?ajax=motor&marka='+encodeURIComponent(marka)+'&model='+encodeURIComponent(model))
            .then(function(r){return r.json();})
            .then(function(data){
                if(data && data.length){
                    data.forEach(function(m){
                        var o = document.createElement('option');
                        o.value = marka+' '+model+' '+m; o.textContent = m;
                        motorEl.appendChild(o);
                    });
                    motorEl.disabled = false;
                    var form = document.getElementById('bt-model-form');
                    if(form) {
                        var hidden = form.querySelector('select[name="ara"]');
                        motorEl.addEventListener('change', function(){
                            if(hidden) hidden.name = '';
                            motorEl.name = 'ara';
                        });
                    }
                }
            }).catch(function(){});
    });
});
</script>
