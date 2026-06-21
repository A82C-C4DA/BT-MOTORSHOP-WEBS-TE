<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['admin']['login'])) {
    echo json_encode(['success' => false, 'error' => 'Oturum bulunamadi. Lutfen tekrar giris yapin.']);
    exit;
}

require_once __DIR__ . '/../db-ayar.php';

$action = isset($_POST['ajax_translate_action']) ? trim($_POST['ajax_translate_action']) : '';

if ($action === 'get_products') {
    try {
        $allRequiredCols = [
            'id', 'baslik', 'kisa_aciklama', 'aciklama',
            'baslik_en', 'baslik_ru', 'baslik_fr', 'baslik_es', 'baslik_ar', 'baslik_pl',
            'kisa_aciklama_en', 'kisa_aciklama_ru', 'kisa_aciklama_fr', 'kisa_aciklama_es', 'kisa_aciklama_ar', 'kisa_aciklama_pl',
            'aciklama_en', 'aciklama_ru', 'aciklama_fr', 'aciklama_es', 'aciklama_ar', 'aciklama_pl'
        ];

        $availableColumns = [];
        $columnRows = $db->query("SHOW COLUMNS FROM urun", PDO::FETCH_ASSOC)->fetchAll(PDO::FETCH_ASSOC);
        foreach ($columnRows as $colRow) {
            if (isset($colRow['Field'])) {
                $availableColumns[] = $colRow['Field'];
            }
        }

        $selectCols = [];
        foreach ($allRequiredCols as $col) {
            if (in_array($col, $availableColumns, true)) {
                $selectCols[] = $col;
            }
        }

        if (empty($selectCols)) {
            echo json_encode(['success' => false, 'error' => 'Urun tablosunda uygun kolon bulunamadi']);
            exit;
        }

        $sql = "SELECT " . implode(', ', $selectCols) . " FROM urun ORDER BY id DESC";
        $stmt = $db->query($sql, PDO::FETCH_ASSOC);
        if (!$stmt) {
            echo json_encode(['success' => false, 'error' => 'Urun listesi sorgusu calistirilamadi']);
            exit;
        }

        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($products as &$p) {
            foreach ($allRequiredCols as $col) {
                if (!isset($p[$col])) {
                    $p[$col] = '';
                }
            }
        }
        unset($p);

        echo json_encode(['success' => true, 'products' => $products]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

if ($action === 'save_product') {
    try {
        $productId = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
        if ($productId <= 0) {
            echo json_encode(['success' => false, 'error' => 'Gecersiz urun id']);
            exit;
        }

        $fields = [
            'baslik_en', 'baslik_ru', 'baslik_fr', 'baslik_es', 'baslik_ar', 'baslik_pl',
            'kisa_aciklama_en', 'kisa_aciklama_ru', 'kisa_aciklama_fr', 'kisa_aciklama_es', 'kisa_aciklama_ar', 'kisa_aciklama_pl',
            'aciklama_en', 'aciklama_ru', 'aciklama_fr', 'aciklama_es', 'aciklama_ar', 'aciklama_pl'
        ];

        $availableColumns = [];
        $columnRows = $db->query("SHOW COLUMNS FROM urun", PDO::FETCH_ASSOC)->fetchAll(PDO::FETCH_ASSOC);
        foreach ($columnRows as $colRow) {
            if (isset($colRow['Field'])) {
                $availableColumns[] = $colRow['Field'];
            }
        }

        $setParts = [];
        $params = [];
        foreach ($fields as $field) {
            if (isset($_POST[$field]) && in_array($field, $availableColumns, true)) {
                $setParts[] = "{$field} = ?";
                $params[] = trim((string)$_POST[$field]);
            }
        }

        if (empty($setParts)) {
            echo json_encode(['success' => true, 'updated' => false, 'message' => 'Guncellenecek alan yok']);
            exit;
        }

        $params[] = $productId;
        $sql = "UPDATE urun SET " . implode(', ', $setParts) . " WHERE id = ? LIMIT 1";
        $update = $db->prepare($sql);
        $update->execute($params);

        echo json_encode(['success' => true, 'updated' => true]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

if ($action === 'get_one_product') {
    try {
        $productId = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
        if ($productId <= 0) {
            echo json_encode(['success' => false, 'error' => 'Gecersiz urun id']);
            exit;
        }

        $allRequiredCols = [
            'id', 'baslik', 'kisa_aciklama', 'aciklama',
            'baslik_en', 'baslik_ru', 'baslik_fr', 'baslik_es', 'baslik_ar', 'baslik_pl',
            'kisa_aciklama_en', 'kisa_aciklama_ru', 'kisa_aciklama_fr', 'kisa_aciklama_es', 'kisa_aciklama_ar', 'kisa_aciklama_pl',
            'aciklama_en', 'aciklama_ru', 'aciklama_fr', 'aciklama_es', 'aciklama_ar', 'aciklama_pl'
        ];

        $availableColumns = [];
        $columnRows = $db->query("SHOW COLUMNS FROM urun", PDO::FETCH_ASSOC)->fetchAll(PDO::FETCH_ASSOC);
        foreach ($columnRows as $colRow) {
            if (isset($colRow['Field'])) {
                $availableColumns[] = $colRow['Field'];
            }
        }

        $selectCols = [];
        foreach ($allRequiredCols as $col) {
            if (in_array($col, $availableColumns, true)) {
                $selectCols[] = $col;
            }
        }

        if (empty($selectCols)) {
            echo json_encode(['success' => false, 'error' => 'Urun tablosunda uygun kolon bulunamadi']);
            exit;
        }

        $stmt = $db->prepare("SELECT " . implode(', ', $selectCols) . " FROM urun WHERE id = ? LIMIT 1");
        $stmt->execute([$productId]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$product) {
            echo json_encode(['success' => false, 'error' => 'Urun bulunamadi']);
            exit;
        }

        foreach ($allRequiredCols as $col) {
            if (!isset($product[$col])) {
                $product[$col] = '';
            }
        }

        echo json_encode(['success' => true, 'product' => $product], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

echo json_encode(['success' => false, 'error' => 'Gecersiz islem']);
exit;

