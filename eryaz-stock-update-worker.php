<?php
/**
 * Eryaz API → site depo ve genel stok güncellemesi (cron-update-stocks-auto ile aynı mantık).
 */

if (!function_exists('eryaz_stock_get_status_value')) {

    function eryaz_stock_get_status_value($product, $fieldNames) {
        if (!is_array($product)) {
            return 0;
        }

        $value = null;

        if (!is_array($fieldNames)) {
            $fieldNames = [$fieldNames];
        }

        foreach ($fieldNames as $fieldName) {
            foreach ($product as $key => $val) {
                if (strtolower(trim($key)) === strtolower(trim($fieldName))) {
                    $value = $val;
                    break 2;
                }
            }
        }

        if ($value !== null && $value !== '') {
            $value = strtolower(trim($value));
            return ($value === 'var') ? 1 : 0;
        }

        return 0;
    }

    /**
     * @param PDO $db
     * @param EryazAPI $eryazAPI
     * @param callable|null $logCallback
     * @return array{success:bool,error?:string,updated:int,skipped:int,notFound:int,errors:int}
     */
    function eryaz_run_full_stock_update(PDO $db, EryazAPI $eryazAPI, $logCallback = null) {
        $log = function ($msg) use ($logCallback) {
            if (is_callable($logCallback)) {
                $logCallback($msg);
            }
        };

        try {
            $checkColumns = $db->query("SHOW COLUMNS FROM urun LIKE 'maslak_stok'")->fetch();
            $hasWarehouseColumns = ($checkColumns !== false);
        } catch (Exception $e) {
            $hasWarehouseColumns = false;
        }

        if (!$hasWarehouseColumns) {
            return [
                'success' => false,
                'error' => 'Depo stok sütunları mevcut değil (maslak_stok vb.).',
                'updated' => 0,
                'skipped' => 0,
                'notFound' => 0,
                'errors' => 0,
            ];
        }

        $result = $eryazAPI->getProductList(1, 50000);

        if (!$result || !$result['success']) {
            return [
                'success' => false,
                'error' => $result['error'] ?? 'Ürünler çekilemedi',
                'updated' => 0,
                'skipped' => 0,
                'notFound' => 0,
                'errors' => 0,
            ];
        }

        $products = $result['data']['Data'] ?? $result['data'] ?? [];
        if (!is_array($products)) {
            $products = [];
        }

        $productsByCode = [];
        foreach ($products as $product) {
            if (is_array($product) && isset($product['Code']) && !empty($product['Code'])) {
                $productsByCode[$product['Code']] = $product;
            }
        }

        $updated = 0;
        $notFound = 0;
        $errors = 0;
        $skipped = 0;

        try {
            $hasEryazStockCode = $db->query("SHOW COLUMNS FROM urun LIKE 'eryaz_stok_kodu'")->fetch() !== false;
        } catch (Exception $e) {
            $hasEryazStockCode = false;
        }
        $selectStockFields = $hasEryazStockCode ? 'id, stok_kodu, eryaz_stok_kodu, stok_manuel' : 'id, stok_kodu, stok_manuel';
        $allProducts = $db->query("SELECT {$selectStockFields} FROM urun WHERE stok_kodu IS NOT NULL AND stok_kodu != ''")->fetchAll(PDO::FETCH_ASSOC);

        foreach ($allProducts as $urun) {
            $stokKodu = (!empty($urun['eryaz_stok_kodu'])) ? $urun['eryaz_stok_kodu'] : $urun['stok_kodu'];
            $stokManuel = isset($urun['stok_manuel']) ? (int)$urun['stok_manuel'] : 0;

            if (!isset($productsByCode[$stokKodu])) {
                $notFound++;
                continue;
            }

            $product = $productsByCode[$stokKodu];

            $maslak = eryaz_stock_get_status_value($product, [
                'Maslak_Status', 'maslak_status', 'MaslakStatus', 'Maslak_Stok', 'maslak_stok',
                'MaslakStatus', 'MASLAK_STATUS', 'MASLAK_STOK', 'Maslak', 'maslak',
            ]);
            $bolu = eryaz_stock_get_status_value($product, [
                'Bolu_Status', 'bolu_status', 'BoluStatus', 'Bolu_Stok', 'bolu_stok',
                'BoluStatus', 'BOLU_STATUS', 'BOLU_STOK', 'Bolu', 'bolu',
            ]);
            $imes = eryaz_stock_get_status_value($product, [
                'İmes_Status', 'imes_status', 'İmesStatus', 'Imes_Status', 'imesStatus',
                'İmes_Stok', 'imes_stok', 'İmesStok', 'Imes_Stok', 'imesStok',
                'İMES_STATUS', 'İMES_STOK', 'İmes', 'imes',
            ]);
            $ankara = eryaz_stock_get_status_value($product, [
                'Ankara_Status', 'ankara_status', 'AnkaraStatus', 'Ankara_Stok', 'ankara_stok',
                'AnkaraStatus', 'ANKARA_STATUS', 'ANKARA_STOK', 'Ankara', 'ankara',
            ]);
            $ikitelli = eryaz_stock_get_status_value($product, [
                'İkitelli_Status', 'ikitelli_status', 'İkitelliStatus', 'Ikitelli_Status', 'ikitelliStatus',
                'İkitelli_Stok', 'ikitelli_stok', 'İkitelliStok', 'Ikitelli_Stok', 'ikitelliStok',
                'İKİTELLİ_STATUS', 'İKİTELLİ_STOK', 'İkitelli', 'ikitelli',
            ]);

            if ($stokManuel == 1) {
                try {
                    $updateQuery = $db->prepare("
                        UPDATE urun SET 
                            maslak_stok = ?,
                            bolu_stok = ?,
                            imes_stok = ?,
                            ankara_stok = ?,
                            ikitelli_stok = ?
                        WHERE id = ?
                    ");
                    $updateQuery->execute([
                        $maslak,
                        $bolu,
                        $imes,
                        $ankara,
                        $ikitelli,
                        $urun['id'],
                    ]);
                    $skipped++;
                } catch (Exception $e) {
                    $log('HATA (ID: ' . $urun['id'] . '): ' . $e->getMessage());
                    $errors++;
                }
            } else {
                $genel_stok = ($maslak == 1 || $bolu == 1 || $imes == 1 || $ankara == 1 || $ikitelli == 1) ? 1 : 0;

                try {
                    $updateQuery = $db->prepare("
                        UPDATE urun SET 
                            maslak_stok = ?,
                            bolu_stok = ?,
                            imes_stok = ?,
                            ankara_stok = ?,
                            ikitelli_stok = ?,
                            stok = ?
                        WHERE id = ?
                    ");
                    $updateQuery->execute([
                        $maslak,
                        $bolu,
                        $imes,
                        $ankara,
                        $ikitelli,
                        $genel_stok,
                        $urun['id'],
                    ]);
                    $updated++;
                } catch (Exception $e) {
                    $log('HATA (ID: ' . $urun['id'] . '): ' . $e->getMessage());
                    $errors++;
                }
            }
        }

        return [
            'success' => true,
            'updated' => $updated,
            'skipped' => $skipped,
            'notFound' => $notFound,
            'errors' => $errors,
        ];
    }
}
