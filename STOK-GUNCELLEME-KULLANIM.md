# Otomatik Stok Güncelleme Sistemi

Bu sistem, Eryaz API'den anlık stok verilerini çekerek veritabanındaki ürünlerin stok durumlarını otomatik olarak günceller.

## Özellikler

- ✅ Otomatik stok güncelleme (cron job ile)
- ✅ Manuel stok güncelleme (admin panelinden)
- ✅ Web üzerinden API endpoint ile tetikleme
- ✅ Detaylı log kaydı
- ✅ Hata yönetimi ve raporlama

## Kurulum

### 1. Dosya Yerleşimi

`cron-update-stocks-auto.php` dosyası proje kök dizininde olmalıdır.

### 2. Log Klasörü Oluşturma

Log dosyalarının kaydedileceği klasörü oluşturun:

```bash
mkdir -p logs
chmod 755 logs
```

### 3. Secret Key Ayarlama

`cron-update-stocks-auto.php` dosyasındaki secret key'i değiştirin:

```php
$secretKey = 'stok_guncelleme_2024_secret_key'; // Bu anahtarı değiştirin!
```

**ÖNEMLİ:** Bu anahtarı güvenli bir değerle değiştirin!

### 4. Admin Panelindeki Buton İçin Secret Key Güncelleme

`panel/inc/urunler.php` dosyasındaki JavaScript kodunda da aynı secret key'i kullanın:

```javascript
fetch('../cron-update-stocks-auto.php?key=stok_guncelleme_2024_secret_key')
```

## Kullanım

### Otomatik Güncelleme (Cron Job)

cPanel veya sunucunuzda cron job oluşturun:

#### Her 5 Dakikada Bir:
```
*/5 * * * * /usr/bin/php /home/username/public_html/cron-update-stocks-auto.php
```

#### Her 10 Dakikada Bir:
```
*/10 * * * * /usr/bin/php /home/username/public_html/cron-update-stocks-auto.php
```

#### Her Saat:
```
0 * * * * /usr/bin/php /home/username/public_html/cron-update-stocks-auto.php
```

**Not:** `username` ve dosya yolu kendi sunucunuzdaki değerlerle değiştirin.

### Manuel Güncelleme

#### Admin Panelinden:

1. Admin paneline giriş yapın
2. **Ürünler** > **Listele** sayfasına gidin
3. Sağ üstteki **"Stokları Güncelle"** butonuna tıklayın
4. İşlem tamamlanana kadar bekleyin (genellikle 30-60 saniye)

#### Web Üzerinden (API):

Tarayıcınızdan veya curl ile:

```bash
curl "https://yoursite.com/cron-update-stocks-auto.php?key=YOUR_SECRET_KEY"
```

veya tarayıcıda:

```
https://yoursite.com/cron-update-stocks-auto.php?key=YOUR_SECRET_KEY
```

**Yanıt Örneği:**
```json
{
  "success": true,
  "updated": 1250,
  "skipped": 50,
  "notFound": 10,
  "errors": 0,
  "executionTime": 45.23,
  "timestamp": "2024-01-15 14:30:25"
}
```

## Log Dosyaları

Log dosyaları `logs/` klasöründe günlük olarak kaydedilir:

- `logs/stock-update-2024-01-15.log`
- `logs/stock-update-2024-01-16.log`
- vb.

Her log dosyası şu bilgileri içerir:
- İşlem başlangıç/bitiş zamanı
- Güncellenen ürün sayısı
- Manuel stoklu ürün sayısı
- Bulunamayan ürün sayısı
- Hata sayısı
- Toplam çalışma süresi

## Stok Güncelleme Mantığı

### Manuel Stok (stok_manuel = 1)

Eğer bir ürünün `stok_manuel` değeri `1` ise:
- Sadece depo stok bilgileri güncellenir (maslak_stok, bolu_stok, vb.)
- Genel stok durumu (`stok` alanı) **değiştirilmez**
- Admin panelinden manuel olarak ayarlanmış stok durumu korunur

### Otomatik Stok (stok_manuel = 0)

Eğer bir ürünün `stok_manuel` değeri `0` ise:
- Depo stok bilgileri güncellenir
- Genel stok durumu otomatik hesaplanır:
  - Herhangi bir depoda stok varsa → `stok = 1` (Var)
  - Hiçbir depoda stok yoksa → `stok = 0` (Yok)

## Depo Stok Alanları

Sistem şu depo stok alanlarını kontrol eder:
- `maslak_stok`
- `bolu_stok`
- `imes_stok`
- `ankara_stok`
- `ikitelli_stok`

## Sorun Giderme

### "Depo stok sütunları mevcut değil" Hatası

Bu hata, veritabanında depo stok sütunlarının olmadığını gösterir. `add_warehouse_columns.sql` dosyasını çalıştırmanız gerekebilir.

### "Ürünler çekilemedi" Hatası

- Eryaz API bağlantısını kontrol edin
- API kimlik bilgilerini kontrol edin (`api-eryaz.php`)
- Sunucunun internet bağlantısını kontrol edin

### Cron Job Çalışmıyor

- Cron job'ın doğru yolda olduğundan emin olun
- PHP yolunu kontrol edin: `which php` veya `php -v`
- Cron job loglarını kontrol edin (cPanel > Cron Jobs > Email)

### Log Dosyaları Oluşturulmuyor

- `logs/` klasörünün yazılabilir olduğundan emin olun: `chmod 755 logs`
- Klasörün var olduğundan emin olun: `mkdir -p logs`

## Performans

- Ortalama 5000 ürün için güncelleme süresi: 30-60 saniye
- Ortalama 10000 ürün için güncelleme süresi: 60-120 saniye
- API çağrısı süresi: 10-30 saniye (ürün sayısına bağlı)

## Güvenlik

- Secret key'i mutlaka değiştirin
- Secret key'i asla public repository'lerde paylaşmayın
- Web üzerinden erişim için HTTPS kullanın
- Log dosyalarını düzenli olarak temizleyin

## İletişim

Sorularınız için sistem yöneticinize başvurun.

