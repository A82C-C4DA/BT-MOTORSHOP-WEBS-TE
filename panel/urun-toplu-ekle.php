<?php
/**
 * Toplu ürün ekleme aracı.
 *
 * Kutuya her satıra bir ürün gelecek şekilde yapıştırıp tek tıkla ekler.
 * Satır biçimi (| veya sekme/TAB ile ayrılır):
 *
 *   Ürün Adı | Kategori | Fiyat | İskonto
 *
 *   - Kategori boş bırakılabilir (o zaman üstteki "Varsayılan kategori" kullanılır).
 *   - Fiyat: virgül veya nokta ile (ör. 1.250,50 veya 1250.50).
 *   - İskonto: boş ise 0 kabul edilir.
 *
 * Fiyat modu:
 *   - liste : Girdiğin fiyat KDV/iskonto HARİÇ liste fiyatıdır. Site iskontoyu uygular,
 *             sonra KDV %20 ekler. (varsayılan)
 *   - son   : Girdiğin fiyat müşterinin gördüğü SON fiyattır (KDV + iskonto dahil, kredi
 *             kartı fiyatı). Bu durumda liste fiyatı geriye doğru hesaplanır.
 *
 * GÜVENLİK: Sabit token ile korunur. İş bittiğinde bu dosyayı sunucudan SİLİN.
 */
require_once __DIR__ . '/fonksiyon.php';

$TOKEN = 'btm-toplu-2026';

$token = isset($_GET['token']) ? $_GET['token'] : (isset($_POST['token']) ? $_POST['token'] : '');
if (!hash_equals($TOKEN, (string)$token)) {
    http_response_code(403);
    echo 'Yetkisiz. URL sonuna ?token=' . htmlspecialchars($TOKEN) . ' ekleyin.';
    exit;
}

/** "1.250,50" / "1250.50" / "1250" -> float */
function tbe_parse_price($raw) {
    $s = trim((string)$raw);
    if ($s === '') return 0.0;
    $s = preg_replace('/[^\d.,]/', '', $s);
    if ($s === '') return 0.0;
    $hasComma = strpos($s, ',') !== false;
    $hasDot   = strpos($s, '.') !== false;
    if ($hasComma && $hasDot) {
        // Hangisi en sağdaysa o ondalık ayraçtır
        if (strrpos($s, ',') > strrpos($s, '.')) {
            $s = str_replace('.', '', $s);
            $s = str_replace(',', '.', $s);
        } else {
            $s = str_replace(',', '', $s);
        }
    } elseif ($hasComma) {
        $s = str_replace(',', '.', $s);
    }
    return (float)$s;
}

/** Kategoriyi başlığa göre bul; yoksa ve izin verildiyse oluştur. id döner ya da 0. */
function tbe_get_or_create_category($db, $baslik, $createMissing) {
    $baslik = trim((string)$baslik);
    if ($baslik === '') return 0;
    $sel = $db->prepare("SELECT id FROM kategori WHERE LOWER(baslik) = LOWER(?) ORDER BY id ASC LIMIT 1");
    $sel->execute([$baslik]);
    $id = $sel->fetchColumn();
    if ($id) return (int)$id;
    if (!$createMissing) return 0;

    $ins = $db->prepare("INSERT INTO kategori SET baslik = ?, sef = ?, ust_kategori = 0, ust_menu = 0, alt_menu = 0, aciklama = '', kisa_aciklama = '', sira = 9999");
    $ins->execute([$baslik, '']);
    $newId = (int)$db->lastInsertId();
    if ($newId > 0) {
        $sef = sef($baslik) . '-' . $newId;
        $db->prepare("UPDATE kategori SET sef = ? WHERE id = ?")->execute([$sef, $newId]);
    }
    return $newId;
}

$results = [];
$summary = ['ok' => 0, 'fail' => 0, 'cat_created' => 0];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['veri'])) {
    $createMissing  = isset($_POST['create_missing']);
    $defaultCat     = trim((string)($_POST['default_kategori'] ?? ''));
    $priceMode      = (($_POST['fiyat_modu'] ?? 'liste') === 'son') ? 'son' : 'liste';
    $stokDegeri     = (isset($_POST['stok']) && $_POST['stok'] === '0') ? 0 : 1;
    $markaId        = (int)($_POST['marka_id'] ?? 0);
    $kdvOrani       = 20.0;

    $lines = preg_split('/\r\n|\r|\n/', (string)$_POST['veri']);
    foreach ($lines as $lineNo => $line) {
        $line = trim($line);
        if ($line === '') continue;

        // | ya da TAB ile böl
        $parts = preg_split('/\s*\|\s*|\t/', $line);
        $baslik   = isset($parts[0]) ? trim($parts[0]) : '';
        $kategori = isset($parts[1]) ? trim($parts[1]) : '';
        $fiyatRaw = isset($parts[2]) ? $parts[2] : '';
        $iskRaw   = isset($parts[3]) ? $parts[3] : '';

        if ($baslik === '') {
            $results[] = ['line' => $line, 'ok' => false, 'msg' => 'Ürün adı boş'];
            $summary['fail']++;
            continue;
        }

        $fiyat   = tbe_parse_price($fiyatRaw);
        $iskonto = tbe_parse_price($iskRaw);
        if ($iskonto < 0) $iskonto = 0;
        if ($iskonto > 100) $iskonto = 100;

        // Fiyat alanlarını hesapla
        if ($priceMode === 'son') {
            // Girilen = kredi kartı (KDV+iskonto dahil) son fiyat -> liste TL'ye çevir
            $kdvsizNet = $fiyat / 1.20;
            $factor = (1 - $iskonto / 100);
            $listeTl = $factor > 0 ? ($kdvsizNet / $factor) : $kdvsizNet;
        } else {
            $listeTl = $fiyat;
        }
        $kdvsizNet      = $listeTl * (1 - $iskonto / 100);
        $netKdvDahil    = $kdvsizNet * 1.20;
        $krediKarti     = $netKdvDahil;
        $pesin          = $netKdvDahil * 0.95;

        try {
            $stmt = $db->prepare("INSERT INTO urun SET baslik = ?, sef = ?, kisa_aciklama = '', aciklama = '', stok_kodu = '', stok = ?, marka_id = ?, eski_fiyat = 0, fiyat = ?, kdv = ?, kargo_fiyati = 0, liste_fiyati_eur = 0, liste_fiyati_tl = ?, iskonto_orani = ?, doviz_kuru = 0, kredi_karti_fiyati = ?, pesin_odeme_fiyati = ?");
            $stmt->execute([
                $baslik, '', $stokDegeri, $markaId,
                round($krediKarti, 2), $kdvOrani,
                round($listeTl, 2), $iskonto,
                round($krediKarti, 2), round($pesin, 2),
            ]);
            $urunId = (int)$db->lastInsertId();
            if ($urunId <= 0) {
                throw new Exception('ID alınamadı');
            }

            $sef = sef($baslik) . '-' . $urunId;
            $db->prepare("UPDATE urun SET sef = ? WHERE id = ?")->execute([$sef, $urunId]);

            // Yeni ürün öne gelsin (sira sütunu varsa)
            try {
                $hasSira = $db->query("SHOW COLUMNS FROM urun LIKE 'sira'")->fetch();
                if ($hasSira) {
                    $db->prepare("UPDATE urun SET sira = sira + 1 WHERE id != ?")->execute([$urunId]);
                    $db->prepare("UPDATE urun SET sira = 1 WHERE id = ?")->execute([$urunId]);
                }
            } catch (Exception $e) { /* yoksa geç */ }

            // Kategori
            $catName = $kategori !== '' ? $kategori : $defaultCat;
            $catId = tbe_get_or_create_category($db, $catName, $createMissing);
            $catMsg = '';
            if ($catId > 0) {
                $db->prepare("INSERT INTO urun_kategori SET urun_id = ?, kategori_id = ?")->execute([$urunId, $catId]);
                $catMsg = $catName;
            } elseif ($catName !== '') {
                $catMsg = $catName . ' (bulunamadı, atlandı)';
            } else {
                $catMsg = '— (kategori yok)';
            }

            $results[] = [
                'line' => $line, 'ok' => true,
                'msg' => "ID #{$urunId} eklendi · " . number_format($krediKarti, 2, ',', '.') . ' ₺ · kat: ' . $catMsg,
            ];
            $summary['ok']++;
        } catch (Exception $e) {
            $results[] = ['line' => $line, 'ok' => false, 'msg' => 'Hata: ' . $e->getMessage()];
            $summary['fail']++;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Toplu Ürün Ekle</title>
<style>
    body { font-family: -apple-system, Segoe UI, Roboto, Arial, sans-serif; background:#f4f6f9; margin:0; padding:24px; color:#222; }
    .wrap { max-width: 900px; margin:0 auto; background:#fff; border-radius:10px; padding:24px; box-shadow:0 2px 10px rgba(0,0,0,.06); }
    h1 { margin:0 0 4px; font-size:22px; }
    p.sub { margin:0 0 18px; color:#666; font-size:14px; }
    label { display:block; font-weight:600; margin:14px 0 6px; font-size:14px; }
    textarea { width:100%; min-height:240px; font-family:ui-monospace, Menlo, Consolas, monospace; font-size:13px; padding:12px; border:1px solid #ccd; border-radius:8px; box-sizing:border-box; }
    input[type=text], select { padding:8px 10px; border:1px solid #ccd; border-radius:8px; font-size:14px; }
    .row { display:flex; gap:18px; flex-wrap:wrap; align-items:center; }
    .row > div { flex:1; min-width:180px; }
    .hint { background:#eef4ff; border:1px solid #d6e4ff; border-radius:8px; padding:10px 12px; font-size:13px; color:#33507a; margin-bottom:8px; }
    code { background:#f0f0f3; padding:2px 5px; border-radius:4px; }
    button { margin-top:18px; background:#2563eb; color:#fff; border:0; padding:12px 22px; font-size:15px; border-radius:8px; cursor:pointer; font-weight:600; }
    button:hover { background:#1d4ed8; }
    table { width:100%; border-collapse:collapse; margin-top:20px; font-size:13px; }
    th, td { text-align:left; padding:7px 9px; border-bottom:1px solid #eee; }
    .ok { color:#15803d; } .fail { color:#b91c1c; }
    .badge { display:inline-block; padding:6px 12px; border-radius:20px; font-weight:600; font-size:13px; margin-right:8px; }
    .b-ok { background:#dcfce7; color:#15803d; } .b-fail { background:#fee2e2; color:#b91c1c; }
    .chk { display:flex; align-items:center; gap:8px; font-weight:600; font-size:14px; margin-top:14px; }
</style>
</head>
<body>
<div class="wrap">
    <h1>Toplu Ürün Ekle</h1>
    <p class="sub">Her satıra bir ürün. Alanları <code>|</code> (dik çizgi) ya da TAB ile ayır.</p>

    <div class="hint">
        <strong>Biçim:</strong> <code>Ürün Adı | Kategori | Fiyat | İskonto</code><br>
        Örnek:<br>
        <code>Bosch Hava Filtresi | Filtreler | 250 | 20</code><br>
        <code>Castrol 5W30 Motor Yağı | Yağlar | 1.450,00 | 10</code><br>
        <code>El Feneri | | 99,90 |</code> &nbsp;(kategori boş → varsayılan kategori, iskonto boş → %0)
    </div>

    <?php if (!empty($results)): ?>
        <div style="margin:10px 0 6px;">
            <span class="badge b-ok"><?php echo (int)$summary['ok']; ?> eklendi</span>
            <?php if ($summary['fail'] > 0): ?><span class="badge b-fail"><?php echo (int)$summary['fail']; ?> başarısız</span><?php endif; ?>
        </div>
        <table>
            <tr><th>#</th><th>Durum</th><th>Satır</th><th>Sonuç</th></tr>
            <?php foreach ($results as $i => $r): ?>
                <tr>
                    <td><?php echo $i + 1; ?></td>
                    <td class="<?php echo $r['ok'] ? 'ok' : 'fail'; ?>"><?php echo $r['ok'] ? '✓' : '✗'; ?></td>
                    <td><?php echo htmlspecialchars($r['line']); ?></td>
                    <td class="<?php echo $r['ok'] ? 'ok' : 'fail'; ?>"><?php echo htmlspecialchars($r['msg']); ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
        <hr style="margin:24px 0; border:0; border-top:1px solid #eee;">
    <?php endif; ?>

    <form method="post" action="?token=<?php echo urlencode($TOKEN); ?>">
        <input type="hidden" name="token" value="<?php echo htmlspecialchars($TOKEN); ?>">

        <div class="row">
            <div>
                <label>Fiyat modu</label>
                <select name="fiyat_modu">
                    <option value="liste">Liste fiyatı (KDV/iskonto hariç) — site iskonto+KDV uygular</option>
                    <option value="son">Son satış fiyatı (KDV+iskonto dahil)</option>
                </select>
            </div>
            <div>
                <label>Stok durumu</label>
                <select name="stok">
                    <option value="1">Stokta var</option>
                    <option value="0">Stokta yok</option>
                </select>
            </div>
        </div>

        <div class="row">
            <div>
                <label>Varsayılan kategori (satırda kategori boşsa)</label>
                <input type="text" name="default_kategori" placeholder="ör. Genel" style="width:100%; box-sizing:border-box;">
            </div>
            <div>
                <label>Marka ID (opsiyonel)</label>
                <input type="text" name="marka_id" value="0" style="width:100%; box-sizing:border-box;">
            </div>
        </div>

        <label class="chk"><input type="checkbox" name="create_missing" checked> Olmayan kategorileri otomatik oluştur</label>

        <label>Ürünler</label>
        <textarea name="veri" placeholder="Bosch Hava Filtresi | Filtreler | 250 | 20"><?php echo isset($_POST['veri']) ? htmlspecialchars($_POST['veri']) : ''; ?></textarea>

        <button type="submit">Ürünleri Ekle</button>
    </form>
</div>
</body>
</html>
