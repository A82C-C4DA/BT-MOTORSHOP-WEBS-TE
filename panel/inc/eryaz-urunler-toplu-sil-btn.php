<?php
/**
 * Eryaz ürünler panel sayfasına toplu sil butonu.
 *
 * eryaz-urunler sayfanızın uygun yerine (butonların olduğu bölüm) ekleyin:
 *
 *   <?php include __DIR__ . '/inc/eryaz-urunler-toplu-sil-btn.php'; ?>
 *
 * Sayfa panel/eryaz-urunler.php ise:
 *   <?php include __DIR__ . '/inc/eryaz-urunler-toplu-sil-btn.php'; ?>
 *
 * Sayfa panel/inc/eryaz-urunler.php ise:
 *   <?php include __DIR__ . '/eryaz-urunler-toplu-sil-btn.php'; ?>
 */
?>
<div class="card border-danger mb-3" id="eryaz-toplu-sil-wrap" style="max-width: 640px;">
    <div class="card-header bg-danger text-white"><strong>Toplu Eryaz ürün sil</strong></div>
    <div class="card-body">
        <p class="small text-muted mb-2">
            Eryaz API’de görünen stok kodlarıyla eşleşen site ürünleri kalıcı silinir. Manuel ürünler (API’de olmayan kod) silinmez.
        </p>
        <label class="d-block"><strong>Onay:</strong> kutuya <code>SIL</code> yazın</label>
        <input type="text" class="form-control mb-2" id="eryaz-sil-onay" placeholder="SIL" autocomplete="off" style="max-width: 200px;">
        <button type="button" class="btn btn-danger" id="eryaz-btn-toplu-sil">Eşleşen Eryaz ürünlerini sil</button>
        <pre id="eryaz-sil-sonuc" class="mt-2 small mb-0 p-2 bg-light rounded" style="white-space:pre-wrap;display:none;"></pre>
    </div>
</div>
<script>
(function () {
    var btn = document.getElementById('eryaz-btn-toplu-sil');
    var onay = document.getElementById('eryaz-sil-onay');
    var out = document.getElementById('eryaz-sil-sonuc');
    if (!btn || !onay || !out) return;
    btn.addEventListener('click', function () {
        if (onay.value.trim() !== 'SIL') {
            out.style.display = 'block';
            out.textContent = 'Önce kutuya SIL yazmalısınız.';
            return;
        }
        btn.disabled = true;
        out.style.display = 'block';
        out.textContent = 'İşlem sürüyor… Sayfayı kapatmayın.';
        fetch('/panel/eryaz-toplu-sil-ajax.php', {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ confirm: 'SIL' })
        })
            .then(function (r) { return r.json().then(function (j) { return { ok: r.ok, j: j }; }); })
            .then(function (x) {
                if (!x.ok || x.j.success === false) {
                    out.textContent = 'Hata: ' + (x.j.error || JSON.stringify(x.j));
                    return;
                }
                var s = x.j;
                out.textContent = [
                    'Tamam.',
                    'API benzersiz kod: ' + s.api_unique_codes,
                    'Eşleşen ürün: ' + s.matched_ids,
                    'Silinen: ' + s.deleted,
                    'Süre: ' + s.executionTime + ' sn'
                ].join('\n');
            })
            .catch(function (e) {
                out.textContent = 'İstek hatası: ' + e.message;
            })
            .finally(function () {
                btn.disabled = false;
            });
    });
})();
</script>
