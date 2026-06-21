<?php
/**
 * KMotorShop yardimci fonksiyonlari (gorsel + OEM referans).
 */

function kms_clean_code($code) {
    return preg_replace('/^(30-|31-|32-|3e-?)/i', '', trim((string)$code));
}

function kms_is_bosch_group_code($code) {
    $c = strtolower(trim((string)$code));
    return strpos($c, '30-') === 0
        || strpos($c, '31-') === 0
        || strpos($c, '32-') === 0
        || strpos($c, '3e-') === 0
        || strpos($c, '3e') === 0;
}

function kms_abs_url($url) {
    $url = html_entity_decode(trim((string)$url), ENT_QUOTES, 'UTF-8');
    if ($url === '') {
        return '';
    }
    if (strpos($url, '//') === 0) {
        return 'https:' . $url;
    }
    if (preg_match('#^https?://#i', $url)) {
        return $url;
    }
    if ($url[0] === '/') {
        return 'https://www.kmotorshop.com' . $url;
    }
    return 'https://www.kmotorshop.com/' . ltrim($url, '/');
}

function kms_bosch_spaced($code) {
    $digits = preg_replace('/\D+/', '', (string)$code);
    if (strlen($digits) !== 10) {
        return '';
    }
    return substr($digits, 0, 1) . ' ' . substr($digits, 1, 3) . ' ' . substr($digits, 4, 3) . ' ' . substr($digits, 7, 3);
}

function kms_get_search_codes($code) {
    $clean = kms_clean_code($code);
    $noSep = str_replace([' ', '-', '.'], '', $clean);
    $out = [];
    foreach ([$clean, $noSep, kms_bosch_spaced($clean)] as $candidate) {
        $candidate = trim((string)$candidate);
        if ($candidate !== '' && !in_array($candidate, $out, true)) {
            $out[] = $candidate;
        }
    }
    return $out;
}

function kms_normalize_referans_code($code) {
    return strtolower(preg_replace('/[\s\.\-]/', '', trim((string)$code)));
}

function kms_fetch($url) {
    if (!function_exists('curl_init')) {
        $GLOBALS['kms_last_err'] = 'curl yok';
        return false;
    }
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_CONNECTTIMEOUT => 12,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/120 Safari/537.36',
        CURLOPT_HTTPHEADER => [
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8',
            'Accept-Language: en-US,en;q=0.9,tr;q=0.8',
        ],
    ]);
    $body = curl_exec($ch);
    $http = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $cerr = curl_error($ch);
    curl_close($ch);
    $GLOBALS['kms_last_http'] = $http;
    $GLOBALS['kms_last_err'] = $cerr;
    if ($http === 403) {
        $GLOBALS['kms_last_err'] = 'KMotorShop sunucu IP engeli (HTTP 403)';
        return false;
    }
    if ($http < 200 || $http >= 300 || $body === false || $body === '') {
        if ($GLOBALS['kms_last_err'] === '' && $http > 0) {
            $GLOBALS['kms_last_err'] = 'HTTP ' . $http;
        }
        return false;
    }
    if (stripos($body, 'Just a moment') !== false && stripos($body, 'challenge-platform') !== false) {
        $GLOBALS['kms_last_err'] = 'Cloudflare engeli';
        return false;
    }
    return $body;
}

function kms_pick_detail_url_from_html($html, $digits, $preferBrand = 'BOSCH') {
    if (!preg_match_all('#href=["\']([^"\']*/en/article-detail/view/\d+/[^"\']+)["\']#iu', (string)$html, $matches)) {
        return '';
    }
    $preferred = '';
    $fallback = '';
    foreach ($matches[1] as $raw) {
        $url = kms_abs_url($raw);
        $slug = strtolower($url);
        if ($digits !== '' && strpos($slug, strtolower($digits)) === false) {
            continue;
        }
        if ($fallback === '') {
            $fallback = $url;
        }
        if ($preferBrand !== '' && stripos($slug, strtolower($preferBrand)) !== false) {
            $preferred = $url;
            break;
        }
    }
    return $preferred !== '' ? $preferred : $fallback;
}

function kms_extract_image_from_html($html) {
    $candidates = [];
    if (preg_match_all('#(?:src|href)=["\']([^"\']+\.(?:jpg|jpeg|png|webp)(?:\?[^"\']*)?)["\']#iu', (string)$html, $m)) {
        foreach ($m[1] as $raw) {
            $url = kms_abs_url($raw);
            if ($url === '') {
                continue;
            }
            $u = strtolower($url);
            if (strpos($u, '/images/brand-logo/') !== false) {
                continue;
            }
            if (strpos($u, '/images/360_') !== false) {
                continue;
            }
            if (strpos($u, 'tn_600_ruzne') !== false) {
                continue;
            }
            if (strpos($u, '/document/tecdoc/') !== false) {
                $candidates[] = $url;
            }
        }
    }
    return !empty($candidates) ? $candidates[0] : '';
}

function kms_find_image_url_for_code($code) {
    $code = trim((string)$code);
    if ($code === '') {
        return '';
    }
    foreach (kms_get_search_codes($code) as $searchCode) {
        $listUrl = 'https://www.kmotorshop.com/en/article-list/oe-list/' . rawurlencode($searchCode);
        $html = kms_fetch($listUrl);
        if ($html === false) {
            $html = kms_fetch($listUrl . '*');
        }
        if ($html === false) {
            continue;
        }
        $img = kms_extract_image_from_html($html);
        if ($img !== '') {
            return $img;
        }
        if (preg_match('#href=["\']([^"\']*/en/article-detail/view/[^"\']+)["\']#iu', $html, $dm)) {
            $detailHtml = kms_fetch(kms_abs_url($dm[1]));
            if ($detailHtml !== false) {
                $img = kms_extract_image_from_html($detailHtml);
                if ($img !== '') {
                    return $img;
                }
            }
        }
        usleep(250000);
    }
    return '';
}

function kms_extract_referans_from_detail_html($html, $primaryCode = '') {
    $refs = [];
    $seen = [];
    $primaryNorm = kms_normalize_referans_code(kms_clean_code($primaryCode));
    $brandNames = 'CUMMINS|TEMSA|TATA|FORD|BOSCH|DAF|MAN|VOLVO|SCANIA|IVECO|RENAULT|PEUGEOT|CITROEN|NISSAN|TOYOTA|HYUNDAI|KIA|MAZDA|HONDA|MERCEDES|VW|AUDI|OPEL|BMW|JAGUAR|LAND ROVER|CASE|DEUTZ|KUBOTA';

    $add = function ($no, $brand = 'OEM') use (&$refs, &$seen, $primaryNorm) {
        $no = trim(html_entity_decode((string)$no, ENT_QUOTES, 'UTF-8'));
        $brand = trim((string)$brand);
        if ($no === '') {
            return;
        }
        $norm = kms_normalize_referans_code($no);
        if ($norm === '' || ($primaryNorm !== '' && $norm === $primaryNorm)) {
            return;
        }
        if (isset($seen[$norm])) {
            return;
        }
        $seen[$norm] = true;
        $refs[] = [
            'marka_adi' => $brand !== '' ? strtoupper($brand) : 'OEM',
            'referans_no' => $no,
        ];
    };

    if (preg_match_all('#/en/article-list/oe-list/([^"\'?\s#]+)#iu', (string)$html, $oeLinks)) {
        foreach ($oeLinks[1] as $raw) {
            $add(rawurldecode($raw), 'OEM');
        }
    }

    if (preg_match_all('#(?i)badge-table[^>]*>\s*([A-Z][A-Z\s]{1,40})\s*#\s*</span>\s*<span[^>]*>\s*([^<]+?)\s*</span>#u', (string)$html, $badgeRows, PREG_SET_ORDER)) {
        foreach ($badgeRows as $row) {
            $add(trim($row[2]), trim($row[1]));
        }
    }

    if (preg_match_all('#(?:alt|title)=["\']([^"\']{12,})["\']#iu', (string)$html, $alts)) {
        foreach ($alts[1] as $alt) {
            if (stripos($alt, ',') === false) {
                continue;
            }
            if (stripos($alt, '/document/tecdoc/') === false && !preg_match('/^\d/', $alt)) {
                continue;
            }
            $parts = array_map('trim', explode(',', $alt));
            if (count($parts) < 4) {
                continue;
            }
            for ($i = 3, $n = count($parts); $i < $n; $i++) {
                $token = $parts[$i];
                if ($token === '' || stripos($token, 'http') !== false) {
                    continue;
                }
                if (preg_match('/^(?i)(' . $brandNames . ')\s+#?\s*(.+)$/', $token, $bm)) {
                    $add(trim($bm[2]), strtoupper($bm[1]));
                } else {
                    $add($token, 'OEM');
                }
            }
        }
    }

    if (preg_match_all('#(?i)For OE number:\s*</[^>]+>\s*([^<]+)#', (string)$html, $foe)) {
        foreach ($foe[1] as $cell) {
            $add(trim(strip_tags($cell)), 'OEM');
        }
    }

    if (preg_match_all('#(?i)\b(' . $brandNames . ')\s*(?:#|</|\|)\s*([0-9A-Z][0-9A-Z\.\s\-]{2,})#u', (string)$html, $brandRows, PREG_SET_ORDER)) {
        foreach ($brandRows as $row) {
            $add(trim($row[2]), strtoupper($row[1]));
        }
    }

    if (preg_match('#/en/article-detail/view/\d+/([^"\'?\s#]+)#iu', (string)$html, $slugMatch)) {
        $slug = rawurldecode($slugMatch[1]);
        if (preg_match('#(?i)-bosch-(.+)$#', $slug, $slugParts)) {
            foreach (explode('-', $slugParts[1]) as $token) {
                $token = str_replace('_', ' ', $token);
                if (strlen($token) >= 4 && preg_match('/\d/', $token)) {
                    $add($token, 'OEM');
                }
            }
        }
    }

    return $refs;
}

function kms_find_referans_for_code($code, $preferBrand = 'BOSCH') {
    $code = trim((string)$code);
    if ($code === '') {
        return [];
    }
    $digits = preg_replace('/\D/', '', kms_clean_code($code));

    foreach (kms_get_search_codes($code) as $searchCode) {
        $listUrl = 'https://www.kmotorshop.com/en/article-list/oe-list/' . rawurlencode($searchCode);
        $listHtml = kms_fetch($listUrl);
        if ($listHtml === false) {
            $listHtml = kms_fetch($listUrl . '*');
        }
        if ($listHtml === false) {
            continue;
        }

        $detailUrl = kms_pick_detail_url_from_html($listHtml, $digits, $preferBrand);
        if ($detailUrl === '') {
            continue;
        }

        $detailHtml = kms_fetch($detailUrl);
        if ($detailHtml === false) {
            continue;
        }

        $refs = kms_extract_referans_from_detail_html($detailHtml, $code);
        if (!empty($refs)) {
            return $refs;
        }
    }

    return [];
}

function kms_ensure_referans_table($db) {
    $db->exec("CREATE TABLE IF NOT EXISTS urun_referans (
        id INT AUTO_INCREMENT PRIMARY KEY,
        urun_id INT NOT NULL,
        marka_adi VARCHAR(255) DEFAULT '',
        referans_no VARCHAR(255) NOT NULL,
        sira INT NOT NULL DEFAULT 0,
        INDEX idx_urun_id (urun_id),
        INDEX idx_referans_no (referans_no)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

function kms_save_referans_list($db, $productId, array $referansList) {
    kms_ensure_referans_table($db);
    $productId = (int)$productId;
    if ($productId <= 0) {
        return 0;
    }

    $added = 0;
    $siraQ = $db->prepare('SELECT COALESCE(MAX(sira), -1) FROM urun_referans WHERE urun_id = ?');
    $siraQ->execute([$productId]);
    $sira = (int)$siraQ->fetchColumn() + 1;

    $check = $db->prepare('SELECT id FROM urun_referans WHERE urun_id = ? AND referans_no = ? LIMIT 1');
    $insert = $db->prepare('INSERT INTO urun_referans SET urun_id = ?, marka_adi = ?, referans_no = ?, sira = ?');

    foreach ($referansList as $row) {
        if (!is_array($row)) {
            continue;
        }
        $no = trim((string)($row['referans_no'] ?? $row['code'] ?? ''));
        $brand = trim((string)($row['marka_adi'] ?? $row['manufacturer'] ?? 'KMotorShop'));
        if ($no === '') {
            continue;
        }
        $check->execute([$productId, $no]);
        if ($check->fetch()) {
            continue;
        }
        $insert->execute([$productId, $brand !== '' ? $brand : 'KMotorShop', $no, $sira++]);
        $added++;
    }

    return $added;
}

function kms_download_image_to_temp($url, &$ext) {
    $url = trim((string)$url);
    if ($url === '' || !preg_match('#^https?://#i', $url) || !function_exists('curl_init')) {
        return false;
    }
    $tmp = tempnam(sys_get_temp_dir(), 'kmsimg_');
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/120 Safari/537.36',
    ]);
    $data = curl_exec($ch);
    $http = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $mime = (string)curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    curl_close($ch);
    if ($http < 200 || $http >= 300 || $data === false || $data === '') {
        @unlink($tmp);
        return false;
    }
    file_put_contents($tmp, $data);
    $ext = strtolower(pathinfo(parse_url($url, PHP_URL_PATH) ?: $url, PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
        $ext = stripos($mime, 'png') !== false ? 'png' : (stripos($mime, 'webp') !== false ? 'webp' : 'jpg');
    }
    return $tmp;
}

function kms_bosch_product_where_sql($hasEryaz) {
    $where = "(LOWER(stok_kodu) LIKE '30-%' OR LOWER(stok_kodu) LIKE '31-%' OR LOWER(stok_kodu) LIKE '32-%' OR LOWER(stok_kodu) LIKE '3e%'";
    if ($hasEryaz) {
        $where .= " OR LOWER(eryaz_stok_kodu) LIKE '30-%' OR LOWER(eryaz_stok_kodu) LIKE '31-%' OR LOWER(eryaz_stok_kodu) LIKE '32-%' OR LOWER(eryaz_stok_kodu) LIKE '3e%'";
    }
    $where .= ')';
    return $where;
}

function kms_is_server_blocked_error($http = null, $errMsg = '') {
    $http = (int)($http ?? ($GLOBALS['kms_last_http'] ?? 0));
    $errMsg = trim((string)($errMsg !== '' ? $errMsg : ($GLOBALS['kms_last_err'] ?? '')));
    if ($http === 403) {
        return true;
    }
    if (stripos($errMsg, 'Cloudflare') !== false || stripos($errMsg, '403') !== false || stripos($errMsg, 'engeli') !== false) {
        return true;
    }
    return false;
}

function kms_local_referans_command($productId, $code, $site = 'https://btmotorshop.com') {
    $productId = (int)$productId;
    $code = kms_clean_code($code);
    $site = rtrim(trim((string)$site), '/');
    if ($productId <= 0 || $code === '') {
        return '';
    }
    return '.\\kmotorshop-yerel-referans-cek.ps1 -Site "' . $site . '" -ProductId ' . $productId . ' -Code "' . str_replace('"', '`"', $code) . '"';
}

/**
 * KMotorShop gorselini indirip urune kaydeder (urun_img + upload/).
 * Varsayilan olarak yalnizca urunde hic gorsel yoksa ekler (kapak gorseli).
 * Donus: ['added'=>0/1, 'img'=>kaydedilen_dosya, 'url'=>kaynak_url, (varsa) 'error'/'skipped'].
 */
function kms_save_image_for_product($db, $productId, $imageUrl, $onlyIfNoImage = true) {
    $productId = (int)$productId;
    $imageUrl = trim((string)$imageUrl);
    if ($productId <= 0 || $imageUrl === '') {
        return ['added' => 0, 'img' => '', 'url' => $imageUrl];
    }

    if ($onlyIfNoImage) {
        $ex = $db->prepare('SELECT id FROM urun_img WHERE urun_id = ? LIMIT 1');
        $ex->execute([$productId]);
        if ($ex->fetch()) {
            return ['added' => 0, 'img' => '', 'url' => $imageUrl, 'skipped' => 'mevcut gorsel var'];
        }
    }

    if (!function_exists('media_upload_product_image')) {
        return ['added' => 0, 'img' => '', 'url' => $imageUrl, 'error' => 'media fonksiyonu yok'];
    }

    $ext = 'jpg';
    $tmp = kms_download_image_to_temp($imageUrl, $ext);
    if ($tmp === false) {
        return ['added' => 0, 'img' => '', 'url' => $imageUrl, 'error' => 'gorsel indirilemedi'];
    }

    $upload = media_upload_product_image($tmp, $ext);
    @unlink($tmp);
    if (empty($upload['ok']) || empty($upload['value'])) {
        return ['added' => 0, 'img' => '', 'url' => $imageUrl, 'error' => 'gorsel kaydedilemedi'];
    }

    $savedName = (string)$upload['value'];
    $chk = $db->prepare('SELECT id FROM urun_img WHERE urun_id = ? AND img = ? LIMIT 1');
    $chk->execute([$productId, $savedName]);
    if ($chk->fetch()) {
        return ['added' => 0, 'img' => $savedName, 'url' => $imageUrl];
    }

    $ins = $db->prepare('INSERT INTO urun_img SET urun_id = ?, img = ?');
    $ins->execute([$productId, $savedName]);
    return ['added' => 1, 'img' => $savedName, 'url' => $imageUrl];
}
