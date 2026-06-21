# Eryaz Entegrasyon Servisi - Kullanım Örnekleri

Bu dosya, Eryaz API entegrasyonu için farklı dillerde örnekler içerir.

## C# Kullanımı

### RestSharp ile (Mevcut Proje)

```csharp
using RestSharp;
using Newtonsoft.Json;

var client = new RestClient("http://share.eryaz.net/api/integration/getdata");
var request = new RestRequest(Method.POST);

request.AddHeader("Cache-Control", "no-cache");
request.AddHeader("Content-Type", "application/json");
request.AddHeader("Accept", "application/json");

object body = new
{
    CompanyKey = "Mh2HTV2R",
    FunctionName = "GetProductList",
    UserName = "teknikdizel_btmotorshop",
    Password = "teknikdizel_btmotor123",
    Parameters = new { @pStart = 1, @pEnd = 1000 }
    // Parameters = "" // Parametre kullanımı ile ilgili bilgi verilmediyse boş gönderin
};

string json = JsonConvert.SerializeObject(body);
request.AddParameter("application/json", json, ParameterType.RequestBody);

var response = await client.ExecuteAsync(request);
return response.Content;
```

## PHP Kullanımı

```php
<?php
$curl = curl_init();
curl_setopt_array($curl, array(
    CURLOPT_URL => "http://share.eryaz.net/api/Integration/getdata",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => "",
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 0,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => "POST",
    CURLOPT_POSTFIELDS =>"{\r\n
        \"CompanyKey\": \"Mh2HTV2R\",\r\n
        \"FunctionName\": \"GetProductList\",\r\n
        \"UserName\": \"teknikdizel_btmotorshop\",\r\n
        \"Password\": \"teknikdizel_btmotor123\",\r\n
        \"Parameters\": {\r\n
            \"@pStart\": 1,\r\n
            \"@pEnd\": 1000\r\n
        }\r\n}",
    CURLOPT_HTTPHEADER => array(
        "Accept: application/json",
        "Content-Type: application/json",
        "Cache-Control: no-cache"
    ),
));
$response = curl_exec($curl);
curl_close($curl);
echo $response;
?>
```

## Web API Endpoint Kullanımı

### JSON Formatında

```http
GET /api/Product/GetProductList?format=json&pStart=1&pEnd=1000
```

### XML Formatında

```http
GET /api/Product/GetProductList?format=xml&pStart=1&pEnd=1000
```

### Otomatik Format (Önce JSON, sonra XML)

```http
GET /api/Product/GetProductList?pStart=1&pEnd=1000
```

## Request Formatı

Tüm isteklerde aşağıdaki format kullanılmalıdır:

```json
{
    "CompanyKey": "FİRMA_KODU",
    "FunctionName": "FONKSİYON_ADI",
    "UserName": "KULLANICI_ADI",
    "Password": "PAROLA",
    "Parameters": {
        "@pStart": 1,
        "@pEnd": 1000
    }
}
```

**Not:** `Parameters` alanı:
- Boş string (`""`) olabilir
- JSON object olabilir (örn: `{"@pStart": 1, "@pEnd": 1000}`)
- Parametre kullanımı ile ilgili bilgi verilmediyse boş gönderin

## Response Formatları

### JSON Response

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

### XML Response

XML formatında dönen yanıt, XML yapısına göre parse edilir.

## Header'lar

Tüm isteklerde aşağıdaki header'lar kullanılmalıdır:

- `Cache-Control: no-cache`
- `Content-Type: application/json`
- `Accept: application/json` (JSON için)
- `Accept: application/xml` (XML için)

