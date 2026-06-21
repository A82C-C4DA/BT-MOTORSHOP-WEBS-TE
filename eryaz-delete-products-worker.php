<?php
/**
 * Eryaz API'de şu an listelenen stok kodlarıyla eşleşen site ürünlerini ve bağlı kayıtları siler.
 * Manuel eklenen (API'de olmayan kodlu) ürünlere dokunmaz.
 */

if (!function_exists('eryaz_delete_products_matching_current_api')) {

    /**
     * İsteğe bağlı tabloda DELETE çalıştırır; tablo yoksa sessiz geçer.
     *
     * @param PDO $db
     * @param string $sql DELETE ... WHERE urun_id IN (...)
     * @param array $ids
     */
    function eryaz_try_delete_by_urun_ids(PDO $db, $sql, array $ids) {
        if (empty($ids)) {
            return;
        }
        try {
            $st = $db->prepare($sql);
            $st->execute($ids);
        } catch (PDOException $e) {
            // tablo yok veya kolon farklı
        }
    }

    /**
     * @param PDO $db
     * @param EryazAPI $eryazAPI
     * @param callable|null $logCallback
     * @return array{success:bool,error?:string,api_unique_codes:int,matched_ids:int,deleted:int,chunks:int}
     */
    function eryaz_delete_products_matching_current_api(PDO $db, EryazAPI $eryazAPI, $logCallback = null) {
        set_time_limit(0);

        $log = function ($msg) use ($logCallback) {
            if (is_callable($logCallback)) {
                $logCallback($msg);
            }
        };

        $chunkSize = 50000;
        $start = 1;
        $uniqueCodes = [];

        while (true) {
            $end = $start + $chunkSize - 1;
            $log("API okunuyor: $start – $end");

            $result = $eryazAPI->getProductList($start, $end);
            if (!$result || empty($result['success'])) {
                return [
                    'success' => false,
                    'error' => $result['error'] ?? 'Ürün listesi alınamadı',
                    'api_unique_codes' => count($uniqueCodes),
                    'matched_ids' => 0,
                    'deleted' => 0,
                    'chunks' => 0,
                ];
            }

            $products = $result['data']['Data'] ?? $result['data'] ?? [];
            if (!is_array($products)) {
                $products = [];
            }

            if (empty($products)) {
                break;
            }

            foreach ($products as $product) {
                if (!is_array($product) || isset($product['Error'])) {
                    continue;
                }
                $code = $eryazAPI->extractStockCodeFromProduct($product);
                if ($code !== '') {
                    $uniqueCodes[$code] = true;
                }
            }

            if (count($products) < $chunkSize) {
                break;
            }

            $start = $end + 1;
        }

        $codes = array_keys($uniqueCodes);
        $cleanCodes = [];
        foreach ($codes as $code) {
            $cleanCodes[] = preg_replace('/^(30-|31-|32-|3e-?)/i', '', trim((string)$code));
        }
        $apiCount = count($codes);
        $log("API'de benzersiz stok kodu: $apiCount");

        if ($apiCount === 0) {
            return [
                'success' => false,
                'error' => 'Eryaz API boş döndü veya stok kodu yok; güvenlik için silme yapılmadı.',
                'api_unique_codes' => 0,
                'matched_ids' => 0,
                'deleted' => 0,
                'chunks' => 0,
            ];
        }

        $idsToDelete = [];
        $lookupCodes = array_values(array_unique(array_merge($codes, $cleanCodes)));
        $codeChunks = array_chunk($lookupCodes, 400);
        try {
            $hasEryazStockCode = $db->query("SHOW COLUMNS FROM urun LIKE 'eryaz_stok_kodu'")->fetch() !== false;
        } catch (Exception $e) {
            $hasEryazStockCode = false;
        }

        foreach ($codeChunks as $chunk) {
            $ph = implode(',', array_fill(0, count($chunk), '?'));
            try {
                if ($hasEryazStockCode) {
                    $st = $db->prepare("SELECT id FROM urun WHERE stok_kodu IN ($ph) OR eryaz_stok_kodu IN ($ph)");
                    $st->execute(array_merge($chunk, $chunk));
                } else {
                    $st = $db->prepare("SELECT id FROM urun WHERE stok_kodu IN ($ph)");
                    $st->execute($chunk);
                }
                while ($row = $st->fetch(PDO::FETCH_ASSOC)) {
                    $idsToDelete[(int)$row['id']] = true;
                }
            } catch (PDOException $e) {
                return [
                    'success' => false,
                    'error' => 'Veritabanı hatası: ' . $e->getMessage(),
                    'api_unique_codes' => $apiCount,
                    'matched_ids' => 0,
                    'deleted' => 0,
                    'chunks' => 0,
                ];
            }
        }

        $ids = array_keys($idsToDelete);
        $matched = count($ids);
        $log("Sitede silinecek ürün id sayısı: $matched");

        $deletedTotal = 0;
        $chunkNum = 0;
        $idBatches = array_chunk($ids, 150);

        foreach ($idBatches as $batch) {
            $chunkNum++;
            $ph = implode(',', array_fill(0, count($batch), '?'));

            try {
                $db->beginTransaction();

                eryaz_try_delete_by_urun_ids($db, "DELETE FROM urun_kategori WHERE urun_id IN ($ph)", $batch);
                eryaz_try_delete_by_urun_ids($db, "DELETE FROM urun_img WHERE urun_id IN ($ph)", $batch);
                eryaz_try_delete_by_urun_ids($db, "DELETE FROM urun_oem WHERE urun_id IN ($ph)", $batch);
                eryaz_try_delete_by_urun_ids($db, "DELETE FROM urun_secenek WHERE urun_id IN ($ph)", $batch);

                $del = $db->prepare("DELETE FROM urun WHERE id IN ($ph)");
                $del->execute($batch);
                $deletedTotal += $del->rowCount();

                $db->commit();
            } catch (PDOException $e) {
                if ($db->inTransaction()) {
                    $db->rollBack();
                }
                return [
                    'success' => false,
                    'error' => 'Silme hatası: ' . $e->getMessage(),
                    'api_unique_codes' => $apiCount,
                    'matched_ids' => $matched,
                    'deleted' => $deletedTotal,
                    'chunks' => $chunkNum,
                ];
            }
        }

        return [
            'success' => true,
            'api_unique_codes' => $apiCount,
            'matched_ids' => $matched,
            'deleted' => $deletedTotal,
            'chunks' => $chunkNum,
        ];
    }
}
