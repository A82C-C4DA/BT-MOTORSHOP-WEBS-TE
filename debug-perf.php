<?php
try {
    $db = new PDO("mysql:host=localhost;dbname=btmotors_batuece;charset=utf8", "btmotors_batu","Batuece123.");
    echo "✅ DATABASE: Connected\n\n";

    $c = $db->query("SELECT COUNT(*) FROM urun")->fetchColumn();
    echo "✅ URUN TABLE: " . number_format($c) . " products\n\n";

    echo "✅ SIMPLE QUERY TEST:\n";
    $start = microtime(true);
    $r = $db->query("SELECT id, baslik FROM urun ORDER BY id DESC LIMIT 50");
    $time = (microtime(true) - $start) * 1000;
    echo "   Time: " . round($time) . "ms\n";
    echo "   Rows: " . $r->rowCount() . "\n";

} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage();
}
?>
