# Eryaz Entegrasyon Servisi

Bu proje, Eryaz platformu ile entegrasyon sağlayan bir C# Web API uygulamasıdır. Müşterilerinizin ürünlerini Eryaz platformundan çekmek için kullanılır.

## Özellikler

- JSON ve XML formatlarında veri çekme desteği
- Öncelik sırası: JSON (öncelik 1), XML (öncelik 2)
- RESTful API endpoint'leri
- Swagger dokümantasyonu
- Hata yönetimi ve loglama

## Kurulum

1. .NET 8.0 SDK'nın yüklü olduğundan emin olun
2. Projeyi klonlayın veya indirin
3. Proje dizinine gidin:
   ```
   cd EryazIntegrationService
   ```
4. Bağımlılıkları yükleyin:
   ```
   dotnet restore
   ```
5. Uygulamayı çalıştırın:
   ```
   dotnet run
   ```

## Yapılandırma

`appsettings.json` dosyasında Eryaz entegrasyon ayarları bulunmaktadır:

```json
{
  "EryazSettings": {
    "IntegrationUrl": "http://share.eryaz.net/api/integration/getdata",
    "CompanyKey": "Mh2HTV2R",
    "UserName": "teknikdizel_btmotorshop",
    "Password": "teknikdizel_btmotor123",
    "FunctionName": "GetProductList",
    "Parameters": ""
  }
}
```

**Not:** `Parameters` alanı boş string olabilir veya JSON formatında parametreler içerebilir (örn: `{"@pStart": 1, "@pEnd": 1000}`).

## API Endpoint'leri

### GetProductList
Ürün listesini getirir. Format belirtilmezse önce JSON, sonra XML denenir.

**Endpoint:** `GET /api/Product/GetProductList`

**Query Parameters:**
- `format` (opsiyonel): `json` veya `xml`
- `pStart` (opsiyonel): Başlangıç kayıt numarası (örn: 1)
- `pEnd` (opsiyonel): Bitiş kayıt numarası (örn: 1000)

**Örnek:**
```
GET /api/Product/GetProductList
GET /api/Product/GetProductList?format=json
GET /api/Product/GetProductList?format=xml
GET /api/Product/GetProductList?format=json&pStart=1&pEnd=1000
```

### GetProductListJson
Ürün listesini JSON formatında getirir.

**Endpoint:** `GET /api/Product/GetProductListJson`

### GetProductListXml
Ürün listesini XML formatında getirir.

**Endpoint:** `GET /api/Product/GetProductListXml`

## Yanıt Formatı

```json
{
  "success": true,
  "message": "Başarılı",
  "products": [
    {
      "id": 1,
      "name": "Ürün Adı",
      "code": "URUN001",
      "price": 100.00,
      "stock": 50,
      "description": "Ürün açıklaması",
      "imageUrl": "https://...",
      "category": "Kategori",
      "brand": "Marka",
      "additionalProperties": {}
    }
  ],
  "rawData": {}
}
```

## Swagger Dokümantasyonu

Uygulama çalıştığında Swagger UI'ya şu adresten erişebilirsiniz:
- HTTP: `http://localhost:5000/swagger`
- HTTPS: `https://localhost:5001/swagger`

## Geliştirme

Proje yapısı:
- `Controllers/`: API endpoint'leri
- `Services/`: İş mantığı ve Eryaz entegrasyon servisi
- `Models/`: Veri modelleri
- `appsettings.json`: Yapılandırma dosyası

## Notlar

- Servis, platform bağımsız olarak çalışmaktadır
- IP adresleri üzerinden veri alımı yapılmaktadır
- Hata durumlarında detaylı log kayıtları tutulmaktadır
- RestSharp kütüphanesi kullanılmaktadır
- Request formatı Eryaz API dokümantasyonuna uygun şekilde hazırlanmıştır
- Accept header'ı ile format belirlenir (application/json veya application/xml)

