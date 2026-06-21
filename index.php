<?php
    foreach($_GET    as $k => $v) $_GET[$k]    = strip_tags($v);
    foreach($_POST   as $k => $v) $_POST[$k]   = strip_tags($v);

    include 'panel/fonksiyon.php';

    // Dil seçeneği yönetimi
    $language = isset($_GET['lang']) ? $_GET['lang'] : (isset($_COOKIE['site_language']) ? $_COOKIE['site_language'] : 'en');
    if (isset($_GET['lang']) && in_array($_GET['lang'], ['tr', 'ru', 'en', 'fr', 'es', 'ar', 'pl'])) {
        setcookie('site_language', $_GET['lang'], time() + (86400 * 365), '/'); // 1 yıl
        $language = $_GET['lang'];
    }

    if(isset($_GET['sayfa'])) {
      $sayfa = cleanAZ($_GET['sayfa']);
      if($sayfa == 'cikis-yap'){
        unset($_SESSION['kullanici']['login']);
        unset($_SESSION['kullanici']['id']);
      }
      if($sayfa !='cikis-yap'){
        if (!is_file('inc/'.$sayfa.'.php')) {
              $sayfa = '404';
        }
      }else{
        $sayfa = 'anasayfa';
      }
    }else{
      $sayfa = 'anasayfa';
    }

    $cek = $db->query("SELECT * FROM ayar LIMIT 1")->fetch(PDO::FETCH_ASSOC);

    $performance_settings = [
      'page_cache_enabled' => false,
      'page_cache_ttl' => 180, // saniye
      'asset_minify_enabled' => false
    ];

    function perf_should_cache_page() {
      if (PHP_SAPI === 'cli') {
        return false;
      }
      if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        return false;
      }
      if (!empty($_POST)) {
        return false;
      }
      if (isset($_GET['nocache']) || isset($_GET['clearcache'])) {
        return false;
      }
      if (isset($_SESSION['kullanici']['login'])) {
        return false;
      }
      // Sadece bilinen/güvenli parametrelere sahip URL'leri cache'le.
      // fbclid, utm_*, gclid gibi takip parametreleri her seferinde benzersiz bir
      // cache dosyası üretip diski şişirir; bu tür URL'leri cache'leme.
      $allowedGetKeys = ['sayfa', 'sef', 'lang', 'id', 'page'];
      foreach (array_keys($_GET) as $gk) {
        if (!in_array($gk, $allowedGetKeys, true)) {
          return false;
        }
      }
      return true;
    }

    // Süresi geçmiş sayfa-cache dosyalarını temizler (disk şişmesini önler).
    function perf_cache_gc($dir, $maxAge) {
      $cutoff = time() - $maxAge;
      $h = @opendir($dir);
      if (!$h) {
        return;
      }
      $checked = 0;
      while (($f = readdir($h)) !== false) {
        if ($f === '.' || $f === '..' || substr($f, -5) !== '.html') {
          continue;
        }
        $p = $dir . '/' . $f;
        if (@filemtime($p) < $cutoff) {
          @unlink($p);
        }
        if (++$checked >= 3000) { // tek istekte aşırı yük bindirme
          break;
        }
      }
      closedir($h);
    }

    function perf_minify_css($css) {
      $css = preg_replace('!/\*.*?\*/!s', '', $css);
      $css = preg_replace('/\s+/', ' ', $css);
      $css = str_replace(["\r", "\n", "\t"], '', $css);
      $css = preg_replace('/\s*([{}|:;,])\s+/', '$1', $css);
      $css = str_replace(';}', '}', $css);
      return trim($css);
    }

    function perf_minify_js($js) {
      // Düşük riskli minify: sadece blok yorumlar, boş satırlar ve gereksiz boşluklar
      $js = preg_replace('!/\*.*?\*/!s', '', $js);
      $lines = preg_split('/\R/', (string)$js);
      $out = [];
      foreach ($lines as $line) {
        $trimmed = trim($line);
        if ($trimmed === '' || strpos($trimmed, '//') === 0) {
          continue;
        }
        $out[] = $trimmed;
      }
      return implode("\n", $out);
    }

    function perf_asset_url($relativePath) {
      global $performance_settings;
      $relativePath = ltrim((string)$relativePath, '/');
      $fullPath = __DIR__ . '/' . $relativePath;
      if (!is_file($fullPath)) {
        return $relativePath;
      }

      $ext = strtolower(pathinfo($relativePath, PATHINFO_EXTENSION));
      $mtime = (int)@filemtime($fullPath);

      if (empty($performance_settings['asset_minify_enabled']) || !in_array($ext, ['css', 'js'], true)) {
        return $relativePath . '?v=' . $mtime;
      }

      $alreadyMin = preg_match('/\.min\.(css|js)$/i', $relativePath) === 1;
      if ($alreadyMin) {
        return $relativePath . '?v=' . $mtime;
      }

      $minRelative = 'cache/min/' . preg_replace('/\.(css|js)$/i', '.min.$1', $relativePath);
      $minFullPath = __DIR__ . '/' . $minRelative;
      $minDir = dirname($minFullPath);
      if (!is_dir($minDir)) {
        @mkdir($minDir, 0775, true);
      }

      $needsBuild = !is_file($minFullPath) || @filemtime($minFullPath) < $mtime;
      if ($needsBuild) {
        $content = (string)@file_get_contents($fullPath);
        if ($content !== '') {
          $minified = $ext === 'css' ? perf_minify_css($content) : perf_minify_js($content);
          if ($minified === '') {
            $minified = $content;
          }
          @file_put_contents($minFullPath, $minified, LOCK_EX);
        }
      }

      if (is_file($minFullPath)) {
        return $minRelative . '?v=' . (int)@filemtime($minFullPath);
      }

      return $relativePath . '?v=' . $mtime;
    }

    $page_cache_enabled = !empty($performance_settings['page_cache_enabled']);
    $page_cache_ttl = (int)$performance_settings['page_cache_ttl'];
    $can_use_page_cache = $page_cache_enabled && perf_should_cache_page();
    $page_cache_file = '';
    if ($can_use_page_cache) {
      $cacheDir = __DIR__ . '/cache/pages';
      if (!is_dir($cacheDir)) {
        @mkdir($cacheDir, 0775, true);
      }
      // Ara sıra (her ~100 istekte 1) süresi geçmiş cache dosyalarını temizle.
      if (mt_rand(1, 100) === 1) {
        perf_cache_gc($cacheDir, max(3600, $page_cache_ttl * 4));
      }
      // Cache anahtarını sadece bilinen parametrelerden ve sıralı üret;
      // parametre sırası değişse bile aynı dosya kullanılır.
      $keyParts = $_GET;
      ksort($keyParts);
      $cacheKey = md5(($sayfa ?? 'anasayfa') . '|' . http_build_query($keyParts));
      $page_cache_file = $cacheDir . '/' . $cacheKey . '.html';
      if (is_file($page_cache_file) && (time() - (int)@filemtime($page_cache_file) < $page_cache_ttl)) {
        readfile($page_cache_file);
        exit;
      }
    }

    function meta_degistir($icerik) {
      global  $_title, $_description, $hreflang_tags, $canonical_url, $og_locale, $html_lang, $json_ld_schema;
      
      $icerik = str_replace('[$_title]', $_title, $icerik);
      $icerik = str_replace('[$_description]', $_description, $icerik);
      
      // hreflang etiketlerini ekle
      if (isset($hreflang_tags) && !empty($hreflang_tags)) {
          $icerik = str_replace('</head>', $hreflang_tags . '</head>', $icerik);
      }
      
      // Canonical URL ekle
      if (isset($canonical_url) && !empty($canonical_url)) {
          $canonical_tag = '<link rel="canonical" href="' . htmlspecialchars($canonical_url) . '" />';
          $icerik = str_replace('</head>', $canonical_tag . "\n" . '</head>', $icerik);
      }
      
      // Open Graph locale güncelle
      if (isset($og_locale) && !empty($og_locale)) {
          $icerik = str_replace('[$og_locale]', htmlspecialchars($og_locale), $icerik);
      }
      
      // HTML lang attribute güncelle
      if (isset($html_lang) && !empty($html_lang)) {
          $icerik = preg_replace('/<html lang="[^"]*">/', '<html lang="' . htmlspecialchars($html_lang) . '">', $icerik);
      }

      // JSON-LD schema ekle
      if (isset($json_ld_schema) && !empty($json_ld_schema)) {
          $icerik = str_replace('</head>', $json_ld_schema . "\n" . '</head>', $icerik);
      }
      
      return $icerik;
    }

    function sayfa_cikti_isle($icerik) {
      global $can_use_page_cache, $page_cache_file;
      $icerik = meta_degistir($icerik);
      if ($can_use_page_cache && !empty($page_cache_file)) {
        @file_put_contents($page_cache_file, $icerik, LOCK_EX);
      }
      return $icerik;
    }

    ob_start('sayfa_cikti_isle');

    $_title         =  $cek['title'];
    $_description   =  $cek['description'];

    // Sayfa bazli varsayilan SEO degerleri (icerik sayfalari isterse override eder)
    $supported_languages = ['tr', 'en', 'ru', 'fr', 'es', 'ar', 'pl'];
    $lang_locale_map = [
      'tr' => 'tr_TR',
      'en' => 'en_US',
      'ru' => 'ru_RU',
      'fr' => 'fr_FR',
      'es' => 'es_ES',
      'ar' => 'ar_SA',
      'pl' => 'pl_PL'
    ];

    $og_locale = isset($lang_locale_map[$language]) ? $lang_locale_map[$language] : 'en_US';
    $html_lang = in_array($language, $supported_languages, true) ? $language : 'en';

    if ($sayfa === 'anasayfa') {
      $canonical_url = $site . ($language === 'en' ? '' : '?lang=' . $language);
      $hreflang_tags = '';
      foreach ($supported_languages as $lang_code) {
        $href = $site . ($lang_code === 'en' ? '' : '?lang=' . $lang_code);
        $hreflang_tags .= '<link rel="alternate" hreflang="' . $lang_code . '" href="' . htmlspecialchars($href) . '" />' . "\n";
      }
      $hreflang_tags .= '<link rel="alternate" hreflang="x-default" href="' . htmlspecialchars($site) . '" />' . "\n";
    } else {
      $canonical_url = '';
      $hreflang_tags = '';
    }

    // Ana sayfa icin temel schema
    $json_ld_data = [
      '@context' => 'https://schema.org',
      '@graph' => [
        [
          '@type' => 'WebSite',
          'name' => $cek['title'],
          'url' => rtrim($site, '/'),
          'inLanguage' => $html_lang
        ],
        [
          '@type' => 'Organization',
          'name' => $cek['title'],
          'url' => rtrim($site, '/')
        ]
      ]
    ];
    $json_ld_schema = '<script type="application/ld+json">' . json_encode($json_ld_data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '</script>';
?>
<!DOCTYPE html>
<html lang="tr">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
        <meta name="robots" content="all">
        <title>[$_title]</title>
        <meta name="description" content="[$_description]" />
        <base href="<?php echo $site; ?>">
        <meta property="og:title" content="[$_title]">
        <meta property="og:description" content="[$_description]">
        <meta property="og:locale" content="[$og_locale]">
        <meta property="og:type" content="website">
        <meta property="og:site_name" content="<?php echo htmlspecialchars($cek['title']); ?>">
        <meta name="robots" content="index, follow">
        <meta name="googlebot" content="index, follow">
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="[$_title]">
        <meta name="twitter:description" content="[$_description]">
        <meta name="theme-color" content="#08C" />
        <link rel="shortcut icon" href="upload/<?php echo $cek['fav']; ?>" type="image/x-icon">
        <!-- Google Fonts -->
        <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,300i,400,400i,600,600i,700,700i,800,800i&display=swap" rel="stylesheet">
        <!-- CSS Implementing Plugins -->
        <link rel="stylesheet" href="<?php echo perf_asset_url('assets/vendor/font-awesome/css/fontawesome-all.min.css'); ?>">
        <link rel="stylesheet" href="<?php echo perf_asset_url('assets/css/font-electro.css'); ?>">
        <link rel="stylesheet" href="<?php echo perf_asset_url('assets/vendor/animate.css/animate.min.css'); ?>">
        <link rel="stylesheet" href="<?php echo perf_asset_url('assets/vendor/hs-megamenu/src/hs.megamenu.css'); ?>">
        <link rel="stylesheet" href="<?php echo perf_asset_url('assets/vendor/malihu-custom-scrollbar-plugin/jquery.mCustomScrollbar.css'); ?>">
        <link rel="stylesheet" href="<?php echo perf_asset_url('assets/vendor/fancybox/jquery.fancybox.css'); ?>">
        <link rel="stylesheet" href="<?php echo perf_asset_url('assets/vendor/slick-carousel/slick/slick.css'); ?>">
        <link rel="stylesheet" href="<?php echo perf_asset_url('assets/vendor/bootstrap-select/dist/css/bootstrap-select.min.css'); ?>">
        <link rel="stylesheet" href="<?php echo perf_asset_url('assets/vendor/flag-icon-css/css/flag-icon.min.css'); ?>">
        <link rel="stylesheet" href="<?php echo perf_asset_url('assets/css/theme.css'); ?>">
        <style>
          .product-item .product-item__body .mb-2 a:first-child{
            height: 270px;
            justify-content: center;
            align-items: center;
            display: flex !important;
          }
          #loading {
              display: none !important;
          }
          .fpd-views-wrapper .fpd-views-selection{
           	float:left; 
          }
          @media only screen and (max-width: 600px){
           	.col-xs-6{
            	max-width:50%;
              	margin-bottom:20px
            }
          }
        </style>
    </head>

    <body>
      
         <?php if(!empty($cek['site_ust_img'])){ ?><div><a href="<?php echo $cek['ust_img_link']; ?>" class=""><img src="upload/<?php echo $cek['site_ust_img']; ?>" style="width: 100%;"></a></div><?php } ?>

        <!-- ========== HEADER ========== -->
        <header id="header" class="u-header u-header-left-aligned-nav">
            <div class="u-header__section">
                <!-- Topbar -->
                <div class="u-header-topbar py-2 d-none d-xl-block">
                    <div class="container">
                        <div class="d-flex align-items-center">
                            <div class="topbar-left">
                                <a href="#" class="text-gray-110 font-size-13 u-header-topbar__nav-link"><?php echo $cek['title']; ?></a>
                            </div>
                            <div class="topbar-right ml-auto">
                                <ul class="list-inline mb-0">
                                    <!-- Dil Seçeneği -->
                                    <li class="list-inline-item mr-0 u-header-topbar__nav-item u-header-topbar__nav-item-border">
                                        <div class="dropdown d-inline-block">
                                            <a class="u-header-topbar__nav-link dropdown-toggle" href="javascript:;" id="languageDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                <i class="ec ec-global mr-1"></i>
                                                <span id="currentLanguage"><?php 
                                                    if ($language == 'ru') echo 'Русский';
                                                    elseif ($language == 'en') echo 'English';
                                                    elseif ($language == 'fr') echo 'Français';
                                                    elseif ($language == 'es') echo 'Español';
                                                    elseif ($language == 'ar') echo 'العربية';
                                                    elseif ($language == 'pl') echo 'Polski';
                                                    else echo 'Türkçe';
                                                ?></span>
                                            </a>
                                            <div class="dropdown-menu dropdown-menu-right" aria-labelledby="languageDropdown" style="min-width: 150px;">
                                                <a class="dropdown-item <?php echo $language == 'tr' ? 'active' : ''; ?>" href="?lang=tr<?php echo isset($_GET['sayfa']) ? '&sayfa=' . $_GET['sayfa'] : ''; ?><?php echo isset($_GET['sef']) ? '&sef=' . $_GET['sef'] : ''; ?>">
                                                    <span class="flag-icon flag-icon-tr mr-2"></span> Türkçe
                                                </a>
                                                <a class="dropdown-item <?php echo $language == 'en' ? 'active' : ''; ?>" href="?lang=en<?php echo isset($_GET['sayfa']) ? '&sayfa=' . $_GET['sayfa'] : ''; ?><?php echo isset($_GET['sef']) ? '&sef=' . $_GET['sef'] : ''; ?>">
                                                    <span class="flag-icon flag-icon-gb mr-2"></span> English
                                                </a>
                                                <a class="dropdown-item <?php echo $language == 'ru' ? 'active' : ''; ?>" href="?lang=ru<?php echo isset($_GET['sayfa']) ? '&sayfa=' . $_GET['sayfa'] : ''; ?><?php echo isset($_GET['sef']) ? '&sef=' . $_GET['sef'] : ''; ?>">
                                                    <span class="flag-icon flag-icon-ru mr-2"></span> Русский
                                                </a>
                                                <a class="dropdown-item <?php echo $language == 'fr' ? 'active' : ''; ?>" href="?lang=fr<?php echo isset($_GET['sayfa']) ? '&sayfa=' . $_GET['sayfa'] : ''; ?><?php echo isset($_GET['sef']) ? '&sef=' . $_GET['sef'] : ''; ?>">
                                                    <span class="flag-icon flag-icon-fr mr-2"></span> Français
                                                </a>
                                                <a class="dropdown-item <?php echo $language == 'es' ? 'active' : ''; ?>" href="?lang=es<?php echo isset($_GET['sayfa']) ? '&sayfa=' . $_GET['sayfa'] : ''; ?><?php echo isset($_GET['sef']) ? '&sef=' . $_GET['sef'] : ''; ?>">
                                                    <span class="flag-icon flag-icon-es mr-2"></span> Español
                                                </a>
                                                <a class="dropdown-item <?php echo $language == 'ar' ? 'active' : ''; ?>" href="?lang=ar<?php echo isset($_GET['sayfa']) ? '&sayfa=' . $_GET['sayfa'] : ''; ?><?php echo isset($_GET['sef']) ? '&sef=' . $_GET['sef'] : ''; ?>" dir="rtl">
                                                    <span class="flag-icon flag-icon-sa mr-2"></span> العربية
                                                </a>
                                                <a class="dropdown-item <?php echo $language == 'pl' ? 'active' : ''; ?>" href="?lang=pl<?php echo isset($_GET['sayfa']) ? '&sayfa=' . $_GET['sayfa'] : ''; ?><?php echo isset($_GET['sef']) ? '&sef=' . $_GET['sef'] : ''; ?>">
                                                    <span class="flag-icon flag-icon-pl mr-2"></span> Polski
                                                </a>
                                            </div>
                                        </div>
                                    </li>
                                    <!-- End Dil Seçeneği -->
                                    <li class="list-inline-item mr-0 u-header-topbar__nav-item u-header-topbar__nav-item-border"><a href="siparis-takibi" title="" class="u-header-topbar__nav-link"><i class="ec ec-transport mr-1"></i>  <?php 
                                        echo t('order_tracking', $language);
                                    ?></a></li>
                                    <li class="list-inline-item mr-0 u-header-topbar__nav-item u-header-topbar__nav-item-border"><a href="odeme-bildirimi" title="" class="u-header-topbar__nav-link"><i class="ec ec-newsletter"></i> <?php 
                                        echo t('payment_notification', $language);
                                    ?></a></li>
                                    <li class="list-inline-item mr-0 u-header-topbar__nav-item u-header-topbar__nav-item-border"><a href="banka-hesaplarimiz" title="" class="u-header-topbar__nav-link"><i class="ec ec-payment"></i> <?php echo t('bank_accounts', $language); ?></a></li>
                                    <li class="list-inline-item mr-0 u-header-topbar__nav-item u-header-topbar__nav-item-border"><a href="blog" title="" class="u-header-topbar__nav-link"><i class="ec ec-comment"></i> <?php 
                                        echo t('blog', $language);
                                    ?></a></li>
                                    <?php if(isset($_SESSION['kullanici']['login'])){ ?>
                                    <li class="list-inline-item mr-0 u-header-topbar__nav-item u-header-topbar__nav-item-border">
                                        <a href="hesabim" xclass="u-header-topbar__nav-link" style="color:#666">
                                            <i class="ec ec-user mr-1"></i> <?php 
                                                echo t('my_account', $language);
                                            ?>
                                        </a>
                                    </li>
                                    <?php }else{ ?>
                                    <li class="list-inline-item mr-0 u-header-topbar__nav-item u-header-topbar__nav-item-border">
                                        <a href="giris-yap" xclass="u-header-topbar__nav-link" style="color:#666">
                                            <i class="ec ec-user mr-1"></i> <?php 
                                                if ($language == 'ru') echo 'Регистрация';
                                                elseif ($language == 'en') echo 'Sign Up';
                                                else echo t('sign_up', $language);
                                            ?> <span class="text-gray-50"><?php 
                                                if ($language == 'ru') echo 'или';
                                                elseif ($language == 'en') echo 'or';
                                                else echo 'veya';
                                            ?></span> <?php 
                                                if ($language == 'ru') echo 'Войти';
                                                elseif ($language == 'en') echo 'Sign In';
                                                else echo t('sign_in', $language);
                                            ?>
                                        </a>
                                    </li>
                                    <?php } ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- End Topbar -->

                <!-- Logo and Menu -->
                <div class="py-2 py-xl-4 bg-primary-down-lg">
                    <div class="container my-0dot5 my-xl-0">
                        <div class="row align-items-center">
                            <!-- Logo-offcanvas-menu -->
                            <div class="col-auto">
                                <!-- Nav -->
                                <nav class="navbar navbar-expand u-header__navbar py-0 justify-content-xl-between max-width-270 min-width-270">
                                    <!-- Logo -->
                                    <a class="order-1 order-xl-0 navbar-brand u-header__navbar-brand u-header__navbar-brand-center" href="index.php" aria-label="">
                                        <img src="upload/<?php echo $cek['logo']; ?>" alt="<?php echo $cek['title']; ?>" title="<?php echo $cek['title']; ?>">
                                    </a>
                                    <!-- End Logo -->

                                    <button id="sidebarHeaderInvokerMenu" type="button" class="navbar-toggler d-block btn u-hamburger mr-3 mr-xl-0"
                                        aria-controls="sidebarHeader"
                                        aria-haspopup="true"
                                        aria-expanded="false"
                                        data-unfold-event="click"
                                        data-unfold-hide-on-scroll="false"
                                        data-unfold-target="#sidebarHeader1"
                                        data-unfold-type="css-animation"
                                        data-unfold-animation-in="fadeInLeft"
                                        data-unfold-animation-out="fadeOutLeft"
                                        data-unfold-duration="500">
                                        <span id="hamburgerTriggerMenu" class="u-hamburger__box">
                                            <span class="u-hamburger__inner"></span>
                                        </span>
                                    </button>

                                </nav>
                                <!-- End Nav -->

                                <!-- ========== HEADER SIDEBAR ========== -->
                                <aside id="sidebarHeader1" class="u-sidebar u-sidebar--left" aria-labelledby="sidebarHeaderInvokerMenu">
                                    <div class="u-sidebar__scroller">
                                        <div class="u-sidebar__container">
                                            <div class="u-header-sidebar__footer-offset pb-0">
                                                <!-- Toggle Button -->
                                                <div class="position-absolute top-0 right-0 z-index-2 pt-4 pr-7">
                                                    <button type="button" class="close ml-auto"
                                                        aria-controls="sidebarHeader"
                                                        aria-haspopup="true"
                                                        aria-expanded="false"
                                                        data-unfold-event="click"
                                                        data-unfold-hide-on-scroll="false"
                                                        data-unfold-target="#sidebarHeader1"
                                                        data-unfold-type="css-animation"
                                                        data-unfold-animation-in="fadeInLeft"
                                                        data-unfold-animation-out="fadeOutLeft"
                                                        data-unfold-duration="500">
                                                        <span aria-hidden="true"><i class="ec ec-close-remove text-gray-90 font-size-20"></i></span>
                                                    </button>
                                                </div>
                                                <!-- End Toggle Button -->

                                                <!-- Content -->
                                                <div class="js-scrollbar u-sidebar__body">
                                                    <div id="headerSidebarContent" class="u-sidebar__content u-header-sidebar__content">
                                                        <!-- Logo -->
                                                        <a class="d-flex ml-0 navbar-brand u-header__navbar-brand u-header__navbar-brand-vertical" href="index.php" aria-label="Electro">
                                                            <img src="upload/<?php echo $cek['logo']; ?>" alt="<?php echo $cek['title']; ?>" title="<?php echo $cek['title']; ?>">
                                                        </a>
                                                        <!-- End Logo -->

                                                        <!-- List -->
                                                        <style type="text/css">
                                                            #headerSidebarList li{padding: 5px 0px;}
                                                        </style>
                                                        <ul id="headerSidebarList" class="u-header-collapse__nav">
                                                            <!-- Dil Seçeneği (Mobile) -->
                                                            <li>
                                                                <div class="dropdown">
                                                                    <a class="u-header-topbar__nav-link dropdown-toggle" href="javascript:;" id="languageDropdownMobile" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" style="color:#666">
                                                                        <i class="ec ec-global mr-1"></i>
                                                                        <span><?php 
                                            if ($language == 'ru') echo 'Русский';
                                            elseif ($language == 'en') echo 'English';
                                            elseif ($language == 'fr') echo 'Français';
                                            elseif ($language == 'es') echo 'Español';
                                            elseif ($language == 'ar') echo 'العربية';
                                            elseif ($language == 'pl') echo 'Polski';
                                            else echo 'Türkçe';
                                        ?></span>
                                                                    </a>
                                                                    <div class="dropdown-menu" aria-labelledby="languageDropdownMobile" style="min-width: 150px;">
                                                                        <a class="dropdown-item <?php echo $language == 'tr' ? 'active' : ''; ?>" href="?lang=tr<?php echo isset($_GET['sayfa']) ? '&sayfa=' . $_GET['sayfa'] : ''; ?><?php echo isset($_GET['sef']) ? '&sef=' . $_GET['sef'] : ''; ?>">
                                                                            <span class="flag-icon flag-icon-tr mr-2"></span> Türkçe
                                                                        </a>
                                                                        <a class="dropdown-item <?php echo $language == 'en' ? 'active' : ''; ?>" href="?lang=en<?php echo isset($_GET['sayfa']) ? '&sayfa=' . $_GET['sayfa'] : ''; ?><?php echo isset($_GET['sef']) ? '&sef=' . $_GET['sef'] : ''; ?>">
                                                                            <span class="flag-icon flag-icon-gb mr-2"></span> English
                                                                        </a>
                                                                        <a class="dropdown-item <?php echo $language == 'ru' ? 'active' : ''; ?>" href="?lang=ru<?php echo isset($_GET['sayfa']) ? '&sayfa=' . $_GET['sayfa'] : ''; ?><?php echo isset($_GET['sef']) ? '&sef=' . $_GET['sef'] : ''; ?>">
                                                                            <span class="flag-icon flag-icon-ru mr-2"></span> Русский
                                                                        </a>
                                                                        <a class="dropdown-item <?php echo $language == 'fr' ? 'active' : ''; ?>" href="?lang=fr<?php echo isset($_GET['sayfa']) ? '&sayfa=' . $_GET['sayfa'] : ''; ?><?php echo isset($_GET['sef']) ? '&sef=' . $_GET['sef'] : ''; ?>">
                                                                            <span class="flag-icon flag-icon-fr mr-2"></span> Français
                                                                        </a>
                                                                        <a class="dropdown-item <?php echo $language == 'es' ? 'active' : ''; ?>" href="?lang=es<?php echo isset($_GET['sayfa']) ? '&sayfa=' . $_GET['sayfa'] : ''; ?><?php echo isset($_GET['sef']) ? '&sef=' . $_GET['sef'] : ''; ?>">
                                                                            <span class="flag-icon flag-icon-es mr-2"></span> Español
                                                                        </a>
                                                                        <a class="dropdown-item <?php echo $language == 'ar' ? 'active' : ''; ?>" href="?lang=ar<?php echo isset($_GET['sayfa']) ? '&sayfa=' . $_GET['sayfa'] : ''; ?><?php echo isset($_GET['sef']) ? '&sef=' . $_GET['sef'] : ''; ?>" dir="rtl">
                                                                            <span class="flag-icon flag-icon-sa mr-2"></span> العربية
                                                                        </a>
                                                                        <a class="dropdown-item <?php echo $language == 'pl' ? 'active' : ''; ?>" href="?lang=pl<?php echo isset($_GET['sayfa']) ? '&sayfa=' . $_GET['sayfa'] : ''; ?><?php echo isset($_GET['sef']) ? '&sef=' . $_GET['sef'] : ''; ?>">
                                                                            <span class="flag-icon flag-icon-pl mr-2"></span> Polski
                                                                        </a>
                                                                    </div>
                                                                </div>
                                                            </li>
                                                            <!-- End Dil Seçeneği (Mobile) -->
                                                            <!-- Home Section -->
                                                            <?php if(isset($_SESSION['kullanici']['login'])){ ?>
                                                            <li>
                                                                <a href="hesabim" xclass="u-header-topbar__nav-link" style="color:#666">
                                                                    <i class="ec ec-user mr-1"></i> <?php 
                                        if ($language == 'ru') echo 'Мой аккаунт';
                                        elseif ($language == 'en') echo 'My Account';
                                        else echo 'Hesabım';
                                    ?>
                                                                </a>
                                                            </li>
                                                            <?php }else{ ?>
                                                            <li>
                                                                <a href="giris-yap" xclass="u-header-topbar__nav-link" style="color:#666">
                                                                    <i class="ec ec-user mr-1"></i> <?php 
                                        if ($language == 'ru') echo 'Регистрация';
                                        elseif ($language == 'en') echo 'Sign Up';
                                        else echo 'Kayıt Ol';
                                    ?> <span class="text-gray-50"><?php 
                                        echo t('or', $language);
                                    ?></span> <?php 
                                        if ($language == 'ru') echo 'Войти';
                                        elseif ($language == 'en') echo 'Sign In';
                                        else echo 'Giriş Yap';
                                    ?>
                                                                </a>
                                                            </li>
                                                            <?php } ?>
                                                            <li><a href="siparis-takibi" title="" class="u-header-topbar__nav-link"><i class="ec ec-transport mr-1"></i>  <?php 
                                        echo t('order_tracking', $language);
                                    ?></a></li>
                                                            <lix><a href="odeme-bildirimi" title="" class="u-header-topbar__nav-link"><i class="ec ec-newsletter"></i> <?php 
                                        echo t('payment_notification', $language);
                                    ?></a></li>
                                                            <li><a href="banka-hesaplarimiz" title="" class="u-header-topbar__nav-link"><i class="ec ec-payment"></i> <?php 
                                        if ($language == 'ru') echo 'Банковские счета';
                                        elseif ($language == 'en') echo 'Bank Accounts';
                                        else echo 'Banka Hesaplarımız';
                                    ?></a></li>
                                                            <li><a href="blog" title="" class="u-header-topbar__nav-link"><i class="ec ec-comment"></i> <?php 
                                        echo t('blog', $language);
                                    ?></a></li>


                                                            <?php
                                                              $query = $db->query("SELECT * FROM kategori WHERE ust_kategori = 0 ORDER BY sira ASC", PDO::FETCH_ASSOC);
                                                              if($query->rowCount()){
                                                                foreach($query as $row){
                                                                  echo '<li><a href="kategori/'.$row['sef'].'" title="'.$row['baslik'].'" class="u-header-topbar__nav-link"><i class="ec ec-arrow-right-categproes mr-1"></i>'.$row['baslik'].'</a></li>';
                                                                }
                                                              }
                                                            ?>

                                                            <?php
                                                                $query = $db->query("SELECT * FROM sayfa WHERE alt_menu = 1", PDO::FETCH_ASSOC);
                                                                if($query->rowCount()){
                                                                  foreach($query as $row){
                                                                    echo '<li><a class="u-header-topbar__nav-link" href="sayfa/'.$row['sef'].'" title="'.$row['baslik'].'"><i class="ec ec-arrow-right-categproes mr-1"></i>  '.$row['baslik'].'</a></li>';
                                                                  }
                                                                }
                                                              ?>

                                                           

                                                        </ul>
                                                        <!-- End List -->
                                                    </div>
                                                </div>
                                                <!-- End Content -->
                                            </div>
                                        </div>
                                    </div>
                                </aside>
                                <!-- ========== END HEADER SIDEBAR ========== -->

                            </div>
                            <!-- End Logo-offcanvas-menu -->
                            <!-- Primary Menu -->
                            <div class="col d-none d-xl-block">
                                <!-- Nav -->
                                <nav class="js-mega-menu navbar navbar-expand-md u-header__navbar u-header__navbar--no-space">
                                    <!-- Navigation -->
                                    <div id="navBar" class="collapse navbar-collapse u-header__navbar-collapse">
                                        <ul class="navbar-nav u-header__navbar-nav">
                                            
                                            <li class="nav-item u-header__nav-item">
                                                <a class="nav-link u-header__nav-link" href="anasayfa.php"><?php echo t('home', $language); ?></a>
                                            </li>
                                            <li class="nav-item hs-has-sub-menu u-header__nav-item"
                                                data-event="hover"
                                                data-animation-in="slideInUp"
                                                data-animation-out="fadeOut">
                                                <a id="blogMegaMenu" class="nav-link u-header__nav-link u-header__nav-link-toggle" href="javascript:;" aria-haspopup="true" aria-expanded="false" aria-labelledby="blogSubMenu"><?php 
                                                    if ($language == 'ru') echo 'Категории';
                                                    elseif ($language == 'en') echo 'Categories';
                                                    else echo t('categories', $language);
                                                ?></a>
                                                <ul id="blogSubMenu" class="hs-sub-menu u-header__sub-menu" aria-labelledby="blogMegaMenu" style="min-width: 230px;">
                                                    <?php
                                                      $query = $db->query("SELECT * FROM kategori WHERE ust_kategori = 0 ORDER BY sira ASC", PDO::FETCH_ASSOC);
                                                      if($query->rowCount()){
                                                        foreach($query as $row){
                                                          echo '<li><a href="kategori/'.$row['sef'].'" title="'.$row['baslik'].'" class="nav-link u-header__sub-menu-nav-link"><span>'.$row['baslik'].'</span></a></li>';
                                                        }
                                                      }
                                                    ?>
                                                </ul>
                                            </li>

                                            <?php
                                            $query = $db->query("SELECT * FROM sayfa WHERE ust_menu = 1", PDO::FETCH_ASSOC);
                                            if($query->rowCount()){
                                              foreach($query as $row){
                                                echo '<li class="nav-item u-header__nav-item"><a href="sayfa/'.$row['sef'].'" class="nav-link u-header__nav-link" title="'.$row['baslik'].'">'.$row['baslik'].'</a></li>';
                                              }
                                            }
                                            ?>
                                        </ul>
                                    </div>
                                    <!-- End Navigation -->
                                </nav>
                                <!-- End Nav -->
                            </div>
                            <!-- End Primary Menu -->
                            <!-- Customer Care -->
                            <div class="d-none d-xl-block col-md-auto">
                                <div class="d-flex">
                                    <i class="ec ec-support font-size-50 text-primary"></i>
                                    <div class="ml-2">
                                        <div class="phone">
                                            <strong><?php echo t('support', $language); ?></strong> <a href="tel:<?php echo $cek['telefon']; ?>" class="text-gray-90"><?php echo $cek['telefon']; ?></a>
                                        </div>
                                        <div class="email">
                                            <strong>Email</strong> <a href="mailto:<?php echo $cek['email']; ?>" class="text-gray-90"><?php echo $cek['email']; ?></a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- End Customer Care -->
                            <!-- Header Icons -->
                            <div class="d-xl-none col col-xl-auto text-right text-xl-left pl-0 pl-xl-3 position-static">
                                <div class="d-inline-flex">
                                    <ul class="d-flex list-unstyled mb-0 align-items-center">
                                        <!-- Search -->
                                        <li class="col d-xl-none px-2 px-sm-3 position-static">
                                            <a id="searchClassicInvoker" class="font-size-22 text-gray-90 text-lh-1 btn-text-secondary" href="javascript:;" role="button"
                                                data-toggle="tooltip"
                                                data-placement="top"
                                                title="<?php echo t('search', $language); ?>"
                                                aria-controls="searchClassic"
                                                aria-haspopup="true"
                                                aria-expanded="false"
                                                data-unfold-target="#searchClassic"
                                                data-unfold-type="css-animation"
                                                data-unfold-duration="300"
                                                data-unfold-delay="300"
                                                data-unfold-hide-on-scroll="true"
                                                data-unfold-animation-in="slideInUp"
                                                data-unfold-animation-out="fadeOut">
                                                <span class="ec ec-search"></span>
                                            </a>

                                            <!-- Input -->
                                            <div id="searchClassic" class="dropdown-menu dropdown-unfold dropdown-menu-right left-0 mx-2" aria-labelledby="searchClassicInvoker">
                                                <form class="js-focus-state input-group px-3" action="" method="post">
                                                    <input class="form-control" type="search" name="ara" placeholder="<?php echo t('search_placeholder', $language); ?>">
                                                    <div class="input-group-append">
                                                        <button class="btn btn-primary px-3" type="button"><i class="font-size-18 ec ec-search"></i></button>
                                                    </div>
                                                </form>
                                            </div>
                                            <!-- End Input -->
                                        </li>
                                        <!-- End Search -->
                                        <li class="col d-none d-xl-block"><a href="favorilerim" class="text-gray-90" data-toggle="tooltip" data-placement="top" title="Favorilerim"><i class="font-size-22 ec ec-favorites"></i></a></li>
                                        <li class="col d-xl-none px-2 px-sm-3"><a href="hesabim" class="text-gray-90" data-toggle="tooltip" data-placement="top" title="Hesabım"><i class="font-size-22 ec ec-user"></i></a></li>
                                        <li class="col pr-xl-0 px-2 px-sm-3">
                                            <a href="sepetim" class="text-gray-90 position-relative d-flex " data-toggle="tooltip" data-placement="top" title="<?php echo t('cart', $language); ?>">
                                                <i class="font-size-22 ec ec-shopping-bag"></i>
                                                <span class="width-22 height-22 bg-dark position-absolute d-flex align-items-center justify-content-center rounded-circle left-12 top-8 font-weight-bold font-size-12 text-white" data-sepetsayisi=""><?php echo @count($_SESSION['sepet']['key']); ?></span>
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                            <!-- End Header Icons -->
                        </div>
                    </div>
                </div>
                <!-- End Logo and Menu -->

                <!-- Vertical-and-Search-Bar -->
                <div class="d-none d-xl-block bg-primary">
                    <div class="container">
                        <div class="row align-items-stretch min-height-50">
                            <!-- Vertical Menu -->
                            <div class="col-md-auto d-none d-xl-flex align-items-end">
                                <div class="max-width-270 min-width-270">
                                    <!-- Basics Accordion -->
                                    <div id="basicsAccordion">
                                        <!-- Card -->
                                        <div class="card border-0 rounded-0">
                                            <div class="card-header bg-primary rounded-0 card-collapse border-0" id="basicsHeadingOne">
                                                <button type="button" class="btn-link btn-remove-focus btn-block d-flex card-btn py-3 text-lh-1 px-4 shadow-none btn-primary rounded-top-lg border-0 font-weight-bold text-gray-90"
                                                    data-toggle="collapse"
                                                    data-target="#basicsCollapseOne"
                                                    aria-expanded="false"
                                                    aria-controls="basicsCollapseOne">
                                                    <span class="pl-1 text-gray-90"><?php echo t('all_categories', $language); ?></span>
                                                    <span class="text-gray-90 ml-3">
                                                        <span class="ec ec-arrow-down-search"></span>
                                                    </span>
                                                </button>
                                            </div>
                                            <div id="basicsCollapseOne" class="collapse vertical-menu v1"
                                                aria-labelledby="basicsHeadingOne"
                                                data-parent="#basicsAccordion">
                                                <div class="card-body p-0">
                                                    <nav class="js-mega-menu navbar navbar-expand-xl u-header__navbar u-header__navbar--no-space hs-menu-initialized">
                                                        <div id="navBar" class="collapse navbar-collapse u-header__navbar-collapse">
                                                            <ul class="navbar-nav u-header__navbar-nav border-primary border-top-0">
                                                                <?php
                                                                  $query = $db->query("SELECT * FROM kategori WHERE ust_menu = 1 ORDER BY sira ASC", PDO::FETCH_ASSOC);
                                                                  if($query->rowCount()){
                                                                    foreach($query as $row){

                                                                      $query1 = $db->query("SELECT * FROM kategori WHERE ust_kategori = '{$row['id']}' ORDER BY sira ASC", PDO::FETCH_ASSOC);
                                                                      if($query1->rowCount()){
                                                                         echo '<li class="nav-item hs-has-mega-menu u-header__nav-item"
                                                                        data-event="hover"
                                                                        data-animation-in="slideInUp"
                                                                        data-animation-out="fadeOut"
                                                                        data-position="left">
                                                                        <a id="basicMegaMenu" class="nav-link u-header__nav-link u-header__nav-link-toggle" href="kategori/'.$row['sef'].'" aria-haspopup="true" aria-expanded="false">'.$row['baslik'].'</a>

                                                                        <!-- Nav Item - Mega Menu -->
                                                                        <div class="hs-mega-menu vmm-tfw u-header__sub-menu" aria-labelledby="basicMegaMenu'.$row['id'].'">
                                                                            <div class="row u-header__mega-menu-wrapper">';
                                                                        foreach($query1 as $row1){
                                                                          echo '<div class="col mb-3 mb-sm-0">';
                                                                                  echo '<a href="kategori/'.$row1['sef'].'" class="u-header__sub-menu-title" style="display:block;font-weight:bold;margin-bottom:10px;">'.$row1['baslik'].'</a>';

                                                                                  $query2 = $db->query("SELECT * FROM kategori WHERE ust_kategori = '{$row1['id']}' ORDER BY sira ASC", PDO::FETCH_ASSOC);
                                                                                  if($query2->rowCount()){
                                                                                    echo '<ul class="u-header__sub-menu-nav-group mb-3">';
                                                                                    foreach($query2 as $row2){
                                                                                      echo '<li><a href="kategori/'.$row2['sef'].'" class="nav-link u-header__sub-menu-nav-link" title="'.$row2['baslik'].'">'.$row2['baslik'].'</a></li>';
                                                                                    }
                                                                                    echo '</ul>';
                                                                                  }

                                                                          echo '</div>';
                                                                        }
                                                                        echo '</div></div></li>';
                                                                      }else{
                                                                        echo '<li class="nav-item u-header__nav-item"><a href="kategori/'.$row['sef'].'"  class="nav-link u-header__nav-link">'.$row['baslik'].'</a></li>';
                                                                      }

                                                                    }
                                                                  }
                                                                ?>
                                                            </ul>
                                                        </div>
                                                    </nav>
                                                </div>
                                            </div>
                                        </div>
                                        <!-- End Card -->
                                    </div>
                                    <!-- End Basics Accordion -->
                                </div>
                            </div>
                            <!-- End Vertical Menu -->
                            <!-- Search bar -->
                            <div class="col align-self-center">
                                <!-- Search-Form -->
                                <form class="js-focus-state" action="ara" method="post">
                                    <label class="sr-only" for="searchProduct">Arama</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control py-2 pl-5 font-size-15 border-0 height-40 rounded-left-pill" name="ara" id="searchProduct" placeholder="Binlerce ürün arasında arayın"  aria-describedby="searchProduct1" required>
                                        <div class="input-group-append">
                                            <button class="btn btn-dark height-40 py-2 px-3 rounded-right-pill" type="button" id="searchProduct1">
                                                <span class="ec ec-search font-size-24"></span>
                                            </button>
                                        </div>
                                    </div>
                                </form>
                                <!-- End Search-Form -->
                            </div>
                            <!-- End Search bar -->
                            <!-- Header Icons -->
                            <div class="col-md-auto align-self-center">
                                <div class="d-flex">
                                    <ul class="d-flex list-unstyled mb-0">
                                        <li class="col"><a href="favorilerim" class="text-gray-90" data-toggle="tooltip" data-placement="top" title="<?php echo t('favorites', $language); ?>"><i class="font-size-22 ec ec-favorites"></i></a></li>
                                        <li class="col pr-0">
                                            <a href="sepetim" class="text-gray-90 position-relative d-flex " data-toggle="tooltip" data-placement="top" title="<?php echo t('cart', $language); ?>">
                                                <i class="font-size-22 ec ec-shopping-bag"></i>
                                                <span class="width-22 height-22 bg-dark position-absolute flex-content-center text-white rounded-circle left-12 top-8 font-weight-bold font-size-12" data-sepetsayisi=""><?php echo @count($_SESSION['sepet']['key']); ?></span>
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                            <!-- End Header Icons -->
                        </div>
                    </div>
                </div>
                <!-- End Vertical-and-secondary-menu -->
            </div>
        </header>
        <!-- ========== END HEADER ========== -->

        <?php include 'inc/'.$sayfa.'.php'; ?>

        <!-- ========== FOOTER ========== -->
        <footer>
            <!-- Footer-newsletter -->
            <div class="bg-primary py-3">
                <div class="container">
                    <div class="row align-items-center">
                        <div class="col-lg-7 mb-md-3 mb-lg-0">
                            <div class="row align-items-center">
                                <div class="col-auto flex-horizontal-center">
                                    <i class="ec ec-newsletter font-size-40"></i>
                                    <h2 class="font-size-20 mb-0 ml-3"><?php echo t('get_notified', $language); ?></h2>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-5">
                            <!-- Subscribe Form -->
                            <form class="js-validate js-form-message" onsubmit="return false" id="abone" method="post">
                                <label class="sr-only" for="subscribeSrEmail">Email Adres</label>
                                <div class="input-group input-group-pill">
                                    <input type="email" class="form-control border-0 height-40" id="abonemail" name="email" id="subscribeSrEmail" placeholder="<?php echo t('email_address', $language); ?>" aria-label="Email address" aria-describedby="subscribeButton" required
                                    data-msg="Please enter a valid email address.">
                                    <div class="input-group-append">
                                        <button type="button" class="btn btn-dark btn-sm-wide height-40 py-2" id="subscribeButton"><?php echo t('register', $language); ?></button>
                                    </div>
                                </div>
                            </form>
                            <!-- End Subscribe Form -->
                        </div>
                    </div>
                </div>
            </div>
            <!-- End Footer-newsletter -->
            <!-- Footer-bottom-widgets -->
            <div class="pt-4 pb-4 bg-gray-13">
                <div class="container mt-1">
                    <div class="row">
                        <?php
                          $query = $db->query("SELECT * FROM etiket ORDER BY sira ASC", PDO::FETCH_ASSOC);
                          if($query->rowCount()){
                          ?>
                          <div class="col-md-12  etiket_dis">
                            <h4><?php echo t('most_searched', $language); ?></h4>
                            <ul>
                              <?php foreach($query as $row){ ?>
                                <li><a href="<?php echo $row['link']; ?>" title="<?php echo $row['baslik']; ?>"><?php echo $row['baslik']; ?></a></li>
                              <?php } ?>
                            </ul>
                          </div>
                        <?php } ?>
                        <div class="col-lg-4">
                            <div class="mb-6">
                                <a href="#" class="d-inline-block">
                                    <img src="upload/<?php echo $cek['logo']; ?>" alt="<?php echo $cek['title']; ?>" title="<?php echo $cek['title']; ?>" style="max-height: 40px; width: auto; max-width: 100%;">
                                </a>
                            </div>
                            <div class="mb-4">
                                <div class="row no-gutters">
                                    <div class="col-auto">
                                        <i class="ec ec-support text-primary font-size-56"></i>
                                    </div>
                                    <div class="col pl-3">
                                        <div class="font-size-13 font-weight-light"><?php echo t('customer_service', $language); ?></div>
                                        <a href="tel:<?php echo $cek['telefon']; ?>" class="font-size-20 text-gray-90"><?php echo $cek['telefon']; ?></a>
                                    </div>
                                </div>
                            </div>
                            <div class="mb-4">
                                <h6 class="mb-1 font-weight-bold"><?php echo t('our_address', $language); ?></h6>
                                <address class="">
                                    <?php echo $cek['adres']; ?>
                                </address>
                            </div>
                            <div class="my-4 my-md-4">
                                <ul class="list-inline mb-0 opacity-7">
                                  <?php if(!empty($cek['facebook'])){ echo '<li class="list-inline-item mr-0">
                                        <a class="btn font-size-20 btn-icon btn-soft-dark btn-bg-transparent rounded-circle" href="'.$cek['facebook'].'">
                                            <span class="fab fa-facebook-f btn-icon__inner"></span>
                                        </a>
                                    </li>'; } ?>
                                  <?php if(!empty($cek['twitter'])){ echo '<li class="list-inline-item mr-0">
                                        <a class="btn font-size-20 btn-icon btn-soft-dark btn-bg-transparent rounded-circle" href="'.$cek['twitter'].'">
                                            <span class="fab fa-twitter btn-icon__inner"></span>
                                        </a>
                                    </li>'; } ?>
                                  <?php if(!empty($cek['instagram'])){ echo '<li class="list-inline-item mr-0">
                                        <a class="btn font-size-20 btn-icon btn-soft-dark btn-bg-transparent rounded-circle" href="'.$cek['instagram'].'">
                                            <span class="fab fa-instagram btn-icon__inner"></span>
                                        </a>
                                    </li>'; } ?>
                                </ul>
                            </div>
                        </div>
                        <div class="col-lg-8">
                            <div class="row">
                                <div class="col-12 col-md mb-4 mb-md-0">
                                    <h6 class="mb-3 font-weight-bold"><?php echo t('popular_categories', $language); ?></h6>
                                    <!-- List Group -->
                                    <ul class="list-group list-group-flush list-group-borderless mb-0 list-group-transparent">
                                        <?php
                                        $query = $db->query("SELECT * FROM kategori WHERE alt_menu = 1 ORDER BY sira ASC", PDO::FETCH_ASSOC);
                                        if($query->rowCount()){
                                          foreach($query as $row){
                                            echo '<li><a class="list-group-item list-group-item-action" href="kategori/'.$row['sef'].'" title="'.$row['baslik'].'">'.$row['baslik'].'</a></li>';
                                          }
                                        }
                                      ?>

                                      <?php
                                        $query = $db->query("SELECT * FROM tkategori WHERE alt_menu = 1 ORDER BY sira ASC", PDO::FETCH_ASSOC);
                                        if($query->rowCount()){
                                          foreach($query as $row){
                                            echo '<li><a class="list-group-item list-group-item-action" href="tasarla-kategori/'.$row['sef'].'" title="'.$row['baslik'].'">'.$row['baslik'].'</a></li>';
                                          }
                                        }
                                      ?>
                                    </ul>
                                    <!-- End List Group -->
                                </div>

                                <div class="col-12 col-md mb-4 mb-md-0">
                                    <h6 class="mb-3 font-weight-bold"><?php echo t('pages', $language); ?></h6>
                                    <!-- List Group -->
                                    <ul class="list-group list-group-flush list-group-borderless mb-0 list-group-transparent">
                                        <?php
                                            $query = $db->query("SELECT * FROM sayfa WHERE alt_menu = 1", PDO::FETCH_ASSOC);
                                            if($query->rowCount()){
                                              foreach($query as $row){
                                                echo '<li><a class="list-group-item list-group-item-action" href="sayfa/'.$row['sef'].'" title="'.$row['baslik'].'">'.$row['baslik'].'</a></li>';
                                              }
                                            }
                                          ?>
                                    </ul>
                                    <!-- End List Group -->
                                </div>



                                <div class="col-12 col-md mb-4 mb-md-0">
                                    <h6 class="mb-3 font-weight-bold"><?php echo t('quick_access', $language); ?></h6>
                                    <!-- List Group -->
                                    <ul class="list-group list-group-flush list-group-borderless mb-0 list-group-transparent">
                                        <li><a class="list-group-item list-group-item-action" href="siparis-takibi" title=""><?php echo t('order_tracking', $language); ?></a></li>
                                        <li><a class="list-group-item list-group-item-action" href="odeme-bildirimi" title=""><?php echo t('payment_notification', $language); ?></a></li>
                                        <li><a class="list-group-item list-group-item-action" href="banka-hesaplarimiz" title=""><?php echo t('bank_accounts', $language); ?></a></li>
                                        <li><a class="list-group-item list-group-item-action" href="iletisim" title=""><?php echo t('contact', $language); ?></a></li>
                                    </ul>
                                    <!-- End List Group -->
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- End Footer-bottom-widgets -->
            <!-- Footer-copy-right -->
            <div class="bg-gray-14 py-2">
                <div class="container">
                    <div class="flex-center-between d-block d-md-flex">
                        <div class="mb-3 mb-md-0"><?php echo t('copyright', $language); ?></div>
                        <div class="text-md-right">
                            <span class="d-inline-block border rounded p-1">
                                <img src="assets/img/odeme-yontemleri.png" alt="Image Description">
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            <!-- End Footer-copy-right -->
        </footer>
        <!-- ========== END FOOTER ========== -->


        <!-- Go to Top -->
        <a class="js-go-to u-go-to" href="#"
            data-position='{"bottom": 15, "right": 15 }'
            data-type="fixed"
            data-offset-top="400"
            data-compensation="#header"
            data-show-effect="slideInUp"
            data-hide-effect="slideOutDown">
            <span class="fas fa-arrow-up u-go-to__inner"></span>
        </a>
        <!-- End Go to Top -->

        <!-- JS Global Compulsory -->
        <?php if($sayfa != 'urun-tasarla'){ ?>
        <script src="<?php echo perf_asset_url('assets/vendor/jquery/dist/jquery.min.js'); ?>"></script>
        <script src="<?php echo perf_asset_url('assets/vendor/jquery-migrate/dist/jquery-migrate.min.js'); ?>"></script>
        <?php } ?>

        <script src="<?php echo perf_asset_url('assets/vendor/popper.js/dist/umd/popper.min.js'); ?>"></script>
        <script src="<?php echo perf_asset_url('assets/vendor/bootstrap/bootstrap.min.js'); ?>"></script>

        <!-- JS Implementing Plugins -->
        <script src="<?php echo perf_asset_url('assets/vendor/appear.js'); ?>"></script>
        <script src="<?php echo perf_asset_url('assets/vendor/jquery.countdown.min.js'); ?>"></script>
        <script src="<?php echo perf_asset_url('assets/vendor/hs-megamenu/src/hs.megamenu.js'); ?>"></script>
        <script src="<?php echo perf_asset_url('assets/vendor/svg-injector/dist/svg-injector.min.js'); ?>"></script>
        <script src="<?php echo perf_asset_url('assets/vendor/malihu-custom-scrollbar-plugin/jquery.mCustomScrollbar.concat.min.js'); ?>"></script>
        <script src="<?php echo perf_asset_url('assets/vendor/jquery-validation/dist/jquery.validate.min.js'); ?>"></script>
        <script src="<?php echo perf_asset_url('assets/vendor/fancybox/jquery.fancybox.min.js'); ?>"></script>
        <script src="<?php echo perf_asset_url('assets/vendor/typed.js/lib/typed.min.js'); ?>"></script>
        <script src="<?php echo perf_asset_url('assets/vendor/slick-carousel/slick/slick.js'); ?>"></script>
        <script src="<?php echo perf_asset_url('assets/vendor/bootstrap-select/dist/js/bootstrap-select.min.js'); ?>"></script>

        <!-- JS Electro -->
        <script src="<?php echo perf_asset_url('assets/js/hs.core.js'); ?>"></script>
        <script src="<?php echo perf_asset_url('assets/js/components/hs.countdown.js'); ?>"></script>
        <script src="<?php echo perf_asset_url('assets/js/components/hs.header.js'); ?>"></script>
        <script src="<?php echo perf_asset_url('assets/js/components/hs.hamburgers.js'); ?>"></script>
        <script src="<?php echo perf_asset_url('assets/js/components/hs.unfold.js'); ?>"></script>
        <script src="<?php echo perf_asset_url('assets/js/components/hs.focus-state.js'); ?>"></script>
        <script src="<?php echo perf_asset_url('assets/js/components/hs.malihu-scrollbar.js'); ?>"></script>
        <script src="<?php echo perf_asset_url('assets/js/components/hs.validation.js'); ?>"></script>
        <script src="<?php echo perf_asset_url('assets/js/components/hs.fancybox.js'); ?>"></script>
        <script src="<?php echo perf_asset_url('assets/js/components/hs.onscroll-animation.js'); ?>"></script>
        <script src="<?php echo perf_asset_url('assets/js/components/hs.slick-carousel.js'); ?>"></script>
        <script src="<?php echo perf_asset_url('assets/js/components/hs.show-animation.js'); ?>"></script>
        <script src="<?php echo perf_asset_url('assets/js/components/hs.svg-injector.js'); ?>"></script>
        <script src="<?php echo perf_asset_url('assets/js/components/hs.go-to.js'); ?>"></script>
        <script src="<?php echo perf_asset_url('assets/js/components/hs.selectpicker.js'); ?>"></script>


        <script src="<?php echo perf_asset_url('assets/js/main.js'); ?>"></script>

        <!-- JS Plugins Init. -->
        <script>
            $(window).on('load', function () {
                // initialization of HSMegaMenu component
                $('.js-mega-menu').HSMegaMenu({
                    event: 'hover',
                    direction: 'horizontal',
                    pageContainer: $('.container'),
                    breakpoint: 767.98,
                    hideTimeOut: 0
                });
            });

            $(document).ready(function () {
                // initialization of header
                $.HSCore.components.HSHeader.init($('#header'));

                // initialization of animation
                $.HSCore.components.HSOnScrollAnimation.init('[data-animation]');

                // initialization of unfold component
                $.HSCore.components.HSUnfold.init($('[data-unfold-target]'), {
                    afterOpen: function () {
                        $(this).find('input[type="search"]').focus();
                    }
                });

                // initialization of popups
                $.HSCore.components.HSFancyBox.init('.js-fancybox');

                // initialization of countdowns
                var countdowns = $.HSCore.components.HSCountdown.init('.js-countdown', {
                    yearsElSelector: '.js-cd-years',
                    monthsElSelector: '.js-cd-months',
                    daysElSelector: '.js-cd-days',
                    hoursElSelector: '.js-cd-hours',
                    minutesElSelector: '.js-cd-minutes',
                    secondsElSelector: '.js-cd-seconds'
                });

                // initialization of malihu scrollbar
                $.HSCore.components.HSMalihuScrollBar.init($('.js-scrollbar'));

                // initialization of forms
                $.HSCore.components.HSFocusState.init();

                // initialization of form validation
                $.HSCore.components.HSValidation.init('.js-validate', {
                    rules: {
                        confirmPassword: {
                            equalTo: '#signupPassword'
                        }
                    }
                });

                // initialization of show animations
                $.HSCore.components.HSShowAnimation.init('.js-animation-link');

                // initialization of fancybox
                $.HSCore.components.HSFancyBox.init('.js-fancybox');

                // initialization of slick carousel
                $.HSCore.components.HSSlickCarousel.init('.js-slick-carousel');

                // initialization of go to
                $.HSCore.components.HSGoTo.init('.js-go-to');

                // initialization of hamburgers
                $.HSCore.components.HSHamburgers.init('#hamburgerTrigger');

                // initialization of unfold component
                $.HSCore.components.HSUnfold.init($('[data-unfold-target]'), {
                    beforeClose: function () {
                        $('#hamburgerTrigger').removeClass('is-active');
                    },
                    afterClose: function() {
                        $('#headerSidebarList .collapse.show').collapse('hide');
                    }
                });

                $('#headerSidebarList [data-toggle="collapse"]').on('click', function (e) {
                    e.preventDefault();

                    var target = $(this).data('target');

                    if($(this).attr('aria-expanded') === "true") {
                        $(target).collapse('hide');
                    } else {
                        $(target).collapse('show');
                    }
                });

                // initialization of unfold component
                $.HSCore.components.HSUnfold.init($('[data-unfold-target]'));

                // initialization of select picker
                $.HSCore.components.HSSelectPicker.init('.js-select');
            });
        </script>
    </body>
</html>
