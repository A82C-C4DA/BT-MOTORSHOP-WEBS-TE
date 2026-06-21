<?php
/**
 * Eryaz araçları için panel girişi var mı?
 * Paneliniz farklı session anahtarı kullanıyorsa aşağıdaki listeye ekleyin
 * veya bu dosyanın yanına eryaz-panel-giris-ozel.php oluşturup true/false döndürün.
 */
function eryaz_panel_is_logged_in() {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        @session_start();
    }

    // Özel kontrol (varsa öncelikli)
    $ozel = __DIR__ . '/eryaz-panel-giris-ozel.php';
    if (is_file($ozel)) {
        $ok = include $ozel;
        if (is_bool($ok)) {
            return $ok;
        }
    }

    $anahtarlar = [
        'kul_id',
        'admin',
        'admin_id',
        'yonetici_id',
        'user_id',
        'panel_id',
        'kullanici_id',
    ];

    foreach ($anahtarlar as $k) {
        if (isset($_SESSION[$k]) && $_SESSION[$k] !== '' && $_SESSION[$k] !== null) {
            return true;
        }
    }

    return false;
}
