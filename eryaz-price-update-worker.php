<?php
/**
 * Eryaz → site fiyat senkronu (yalnızca fiyat/KDV/kargo + liste_fiyati_* türevleri).
 * api-eryaz.php içindeki updateProductPricesFromEryazOnly / syncListeFiyatFromEryazProduct kullanılır.
 */

if (!function_exists('eryaz_run_full_price_update')) {

    /**
     * @param PDO $db
     * @param EryazAPI $eryazAPI
     * @param callable|null $logCallback function(string $message): void
     * @return array{success:bool,price_updated:int,not_in_db:int,skipped:int,error_count:int,errors:array,error?:string}
     */
    function eryaz_run_full_price_update(PDO $db, EryazAPI $eryazAPI, $logCallback = null) {
        set_time_limit(0);

        $log = function ($msg) use ($logCallback) {
            if (is_callable($logCallback)) {
                $logCallback($msg);
            }
        };

        $chunkSize = 50000;
        $start = 1;
        $price_updated = 0;
        $not_in_db = 0;
        $skipped = 0;
        $errors = [];

        while (true) {
            $end = $start + $chunkSize - 1;
            $log("API aralığı: $start – $end");

            $result = $eryazAPI->getProductList($start, $end);
            if (!$result || empty($result['success'])) {
                return [
                    'success' => false,
                    'error' => $result['error'] ?? 'Ürün listesi alınamadı',
                    'price_updated' => $price_updated,
                    'not_in_db' => $not_in_db,
                    'skipped' => $skipped,
                    'error_count' => count($errors),
                    'errors' => $errors,
                ];
            }

            $products = $result['data']['Data'] ?? $result['data'] ?? [];
            if (!is_array($products)) {
                $products = [];
            }

            if (empty($products)) {
                break;
            }

            $log('Bu dilimde ' . count($products) . ' kayıt işleniyor.');

            foreach ($products as $product) {
                if (!is_array($product) || isset($product['Error'])) {
                    $skipped++;
                    continue;
                }

                $res = $eryazAPI->updateProductPricesFromEryazOnly($product, $db);
                $act = $res['action'] ?? '';

                if (!empty($res['success']) && $act === 'price_updated') {
                    $price_updated++;
                } elseif ($act === 'not_in_db') {
                    $not_in_db++;
                } elseif ($act === 'skipped') {
                    $skipped++;
                } elseif ($act === 'error') {
                    $errors[] = $res['message'] ?? 'Hata';
                } else {
                    $skipped++;
                }
            }

            if (count($products) < $chunkSize) {
                break;
            }

            $start = $end + 1;
        }

        return [
            'success' => true,
            'price_updated' => $price_updated,
            'not_in_db' => $not_in_db,
            'skipped' => $skipped,
            'error_count' => count($errors),
            'errors' => $errors,
        ];
    }
}
