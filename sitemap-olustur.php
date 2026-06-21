<?php
include 'panel/db-ayar.php';

@set_time_limit(0);

$lastmod = gmdate('c');
$supported_languages = ['tr', 'en', 'ru', 'fr', 'es', 'ar', 'pl'];
$base_url = rtrim($site, '/') . '/';
$max_urls_per_sitemap = 45000; // Google limiti 50.000

function build_urlset_xml($items) {
    $xml = '<?xml version="1.0" encoding="UTF-8"?>';
    $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
    foreach ($items as $item) {
        $xml .= '<url>';
        $xml .= '<loc>' . htmlspecialchars($item['loc'], ENT_QUOTES, 'UTF-8') . '</loc>';
        $xml .= '<lastmod>' . $item['lastmod'] . '</lastmod>';
        $xml .= '<changefreq>' . $item['changefreq'] . '</changefreq>';
        $xml .= '<priority>' . $item['priority'] . '</priority>';
        $xml .= '</url>';
    }
    $xml .= '</urlset>';
    return $xml;
}

function build_sitemap_index_xml($sitemapLocs, $lastmod) {
    $xml = '<?xml version="1.0" encoding="UTF-8"?>';
    $xml .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
    foreach ($sitemapLocs as $loc) {
        $xml .= '<sitemap>';
        $xml .= '<loc>' . htmlspecialchars($loc, ENT_QUOTES, 'UTF-8') . '</loc>';
        $xml .= '<lastmod>' . $lastmod . '</lastmod>';
        $xml .= '</sitemap>';
    }
    $xml .= '</sitemapindex>';
    return $xml;
}

$all_urls = [];

// Ana sayfa dil varyantlari
$all_urls[] = ['loc' => $base_url, 'lastmod' => $lastmod, 'priority' => '1.0', 'changefreq' => 'daily'];
foreach ($supported_languages as $lang) {
    if ($lang === 'en') {
        continue;
    }
    $all_urls[] = ['loc' => $base_url . '?lang=' . $lang, 'lastmod' => $lastmod, 'priority' => '0.95', 'changefreq' => 'daily'];
}

// Urunler
$query = $db->query("SELECT sef FROM urun WHERE stok > 0", PDO::FETCH_ASSOC);
if ($query && $query->rowCount()) {
    foreach ($query as $row) {
        $product_url = $base_url . 'urun/' . $row['sef'];
        $all_urls[] = ['loc' => $product_url, 'lastmod' => $lastmod, 'priority' => '0.90', 'changefreq' => 'daily'];

        foreach ($supported_languages as $lang) {
            if ($lang === 'en') {
                continue;
            }
            $all_urls[] = ['loc' => $product_url . '?lang=' . $lang, 'lastmod' => $lastmod, 'priority' => '0.80', 'changefreq' => 'daily'];
        }
    }
}

// Sayfalar
$query = $db->query("SELECT sef FROM sayfa", PDO::FETCH_ASSOC);
if ($query && $query->rowCount()) {
    foreach ($query as $row) {
        $page_url = $base_url . 'sayfa/' . $row['sef'];
        $all_urls[] = ['loc' => $page_url, 'lastmod' => $lastmod, 'priority' => '0.70', 'changefreq' => 'weekly'];
    }
}

// Kategoriler
$query = $db->query("SELECT sef FROM kategori", PDO::FETCH_ASSOC);
if ($query && $query->rowCount()) {
    foreach ($query as $row) {
        $category_url = $base_url . 'kategori/' . $row['sef'];
        $all_urls[] = ['loc' => $category_url, 'lastmod' => $lastmod, 'priority' => '0.85', 'changefreq' => 'daily'];
    }
}

// Parcali sitemap dosyalari olustur
$chunks = array_chunk($all_urls, $max_urls_per_sitemap);
$sitemap_locs = [];
foreach ($chunks as $i => $chunk_items) {
    $file_index = $i + 1;
    $chunk_file = 'sitemap-' . $file_index . '.xml';
    $chunk_xml = build_urlset_xml($chunk_items);
    file_put_contents($chunk_file, $chunk_xml);
    $sitemap_locs[] = $base_url . $chunk_file;
}

// Geriye donuk uyumluluk: urunler.xml ilk parcayi gostersin
if (!empty($chunks)) {
    file_put_contents('urunler.xml', build_urlset_xml($chunks[0]));
}

// sitemap.xml = index dosyasi
$sitemap_index_xml = build_sitemap_index_xml($sitemap_locs, $lastmod);
file_put_contents('sitemap.xml', $sitemap_index_xml);

header('Content-Type: application/xml; charset=UTF-8');
echo $sitemap_index_xml;
?>