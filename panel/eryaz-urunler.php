<?php
/**
 * Panel — Eryaz ürünler (Toplu sil dahil)
 * URL: https://btmotorshop.com/panel/eryaz-urunler
 *
 * Sunucuda ZATEN bu isimde bir dosyanız varsa: içeriği birleştirin veya
 * yedek aldıktan sonra bu dosyayı kullanın.
 */
session_start();

require_once __DIR__ . '/db-ayar.php';
require_once __DIR__ . '/eryaz-panel-giris-kontrol.php';

if (!eryaz_panel_is_logged_in()) {
    $giris = is_file(__DIR__ . '/giris.php') ? 'giris.php' : (is_file(__DIR__ . '/index.php') ? 'index.php' : '/');
    header('Location: ' . $giris);
    exit;
}

$pageTitle = 'Eryaz Ürünler';

/* Panel şablonu (varsa) */
$headerFile = null;
$footerFile = null;
foreach ([
    ['inc/ust.php', 'inc/alt.php'],
    ['inc/header.php', 'inc/footer.php'],
    ['ust.php', 'alt.php'],
] as $pair) {
    $h = __DIR__ . '/' . $pair[0];
    if (is_file($h)) {
        $headerFile = $h;
        $f = __DIR__ . '/' . $pair[1];
        $footerFile = is_file($f) ? $f : null;
        break;
    }
}

if ($headerFile) {
    require $headerFile;
    ?>
    <div class="container-fluid px-3 py-3">
        <h1 class="h4 mb-3"><?php echo htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?></h1>
        <?php require __DIR__ . '/inc/eryaz-urunler-toplu-sil-btn.php'; ?>
    </div>
    <?php
    if ($footerFile) {
        require $footerFile;
    }
    return;
}

/* Şablon yoksa basit sayfa */
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">
<div class="container py-4">
    <h1 class="h3 mb-4"><?php echo htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?></h1>
    <?php require __DIR__ . '/inc/eryaz-urunler-toplu-sil-btn.php'; ?>
</div>
</body>
</html>
