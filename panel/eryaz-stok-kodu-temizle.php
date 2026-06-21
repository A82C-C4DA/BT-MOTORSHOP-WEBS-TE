<?php
/**
 * Tek seferlik Eryaz stok kodu temizleme script'i.
 *
 * 30-, 31-, 32-, 3E-/3E ile başlayan ürünlerde:
 * - Orijinal kodu urun.eryaz_stok_kodu alanına yazar
 * - Görünen urun.stok_kodu alanından başlangıç prefix'ini siler
 *
 * Eryaz API eşleşmesi artık eryaz_stok_kodu üzerinden devam eder.
 * İş bitince bu dosyayı sunucudan silin.
 */
require_once __DIR__ . '/fonksiyon.php';

if (!isset($_SESSION['admin']['login'])) {
    die('Yetki yok. Once panele giris yapin.');
}

@set_time_limit(0);

try {
    $col = $db->query("SHOW COLUMNS FROM urun LIKE 'eryaz_stok_kodu'")->fetch();
    if (!$col) {
        $db->exec("ALTER TABLE urun ADD COLUMN eryaz_stok_kodu VARCHAR(255) NULL DEFAULT NULL AFTER stok_kodu");
        try {
            $db->exec("CREATE INDEX idx_eryaz_stok_kodu ON urun (eryaz_stok_kodu)");
        } catch (Exception $e) {
        }
    }
} catch (Exception $e) {
    die('Kolon eklenemedi: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'));
}

$rows = $db->query("
    SELECT id, stok_kodu, eryaz_stok_kodu
    FROM urun
    WHERE stok_kodu IS NOT NULL AND stok_kodu <> ''
", PDO::FETCH_ASSOC)->fetchAll(PDO::FETCH_ASSOC);

$updated = 0;
$skipped = 0;
$collisions = [];

$check = $db->prepare("SELECT id FROM urun WHERE stok_kodu = ? AND id <> ? LIMIT 1");
$up = $db->prepare("UPDATE urun SET stok_kodu = ?, eryaz_stok_kodu = ? WHERE id = ? LIMIT 1");

foreach ($rows as $row) {
    $id = (int)$row['id'];
    $current = trim((string)$row['stok_kodu']);
    $lower = strtolower($current);
    $clean = $current;

    if (strpos($lower, '30-') === 0 || strpos($lower, '31-') === 0 || strpos($lower, '32-') === 0) {
        $clean = substr($current, 3);
    } elseif (strpos($lower, '3e-') === 0) {
        $clean = substr($current, 3);
    } elseif (strpos($lower, '3e') === 0) {
        $clean = substr($current, 2);
    } else {
        $skipped++;
        continue;
    }

    $clean = trim($clean);
    if ($clean === '' || $clean === $current) {
        $skipped++;
        continue;
    }

    // Aynı temiz kod başka üründe varsa otomatik değiştirme; çakışmayı raporla.
    $check->execute([$clean, $id]);
    $other = $check->fetch(PDO::FETCH_ASSOC);
    if ($other) {
        $collisions[] = $current . ' -> ' . $clean . ' (ID ' . $id . ', çakışan ID ' . (int)$other['id'] . ')';
        continue;
    }

    $original = trim((string)$row['eryaz_stok_kodu']);
    if ($original === '') {
        $original = $current;
    }

    $up->execute([$clean, $original, $id]);
    $updated++;
}

header('Content-Type: text/html; charset=utf-8');
?>
<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <title>Eryaz Stok Kodu Temizle</title>
    <style>
        body{font-family:Arial,sans-serif;margin:40px;color:#222}
        .box{max-width:760px;padding:20px;border:1px solid #ddd;border-radius:8px;background:#f8fafc}
        .ok{color:#166534;font-weight:bold}.warn{color:#92400e}
        code{background:#e5e7eb;padding:2px 5px;border-radius:4px}
    </style>
</head>
<body>
    <div class="box">
        <h2>Eryaz Stok Kodlari Temizlendi</h2>
        <p class="ok">Islem tamamlandi.</p>
        <p>Prefix silinen urun: <strong><?php echo (int)$updated; ?></strong></p>
        <p>Atlanan urun: <strong><?php echo (int)$skipped; ?></strong></p>
        <p>Orijinal kodlar <code>eryaz_stok_kodu</code> alanina saklandi. Eryaz API eslesmesi bu alandan devam eder.</p>
        <?php if (!empty($collisions)): ?>
            <p class="warn"><strong>Çakışma nedeniyle degistirilmeyenler:</strong></p>
            <ul>
                <?php foreach (array_slice($collisions, 0, 50) as $c): ?>
                    <li><?php echo htmlspecialchars($c, ENT_QUOTES, 'UTF-8'); ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
        <p><strong>Guvenlik:</strong> Is bitince <code>panel/eryaz-stok-kodu-temizle.php</code> dosyasini sunucudan sil.</p>
    </div>
</body>
</html>
