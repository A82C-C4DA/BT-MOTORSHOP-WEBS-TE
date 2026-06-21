using System.Xml.Linq;
using Newtonsoft.Json;
using RestSharp;
using EryazIntegrationService.Models;

namespace EryazIntegrationService.Services
{
    public class EryazIntegrationService
    {
        private readonly EryazSettings _settings;
        private readonly ILogger<EryazIntegrationService> _logger;

        public EryazIntegrationService(
            EryazSettings settings,
            ILogger<EryazIntegrationService> logger)
        {
            _settings = settings;
            _logger = logger;
        }

        /// <summary>
        /// Eryaz API'den ürün listesini çeker. Öncelik JSON, sonra XML formatında dener.
        /// </summary>
        public async Task<EryazResponse> GetProductListAsync(string? format = null, object? parameters = null)
        {
            try
            {
                // Format belirtilmemişse önce JSON dene
                if (string.IsNullOrEmpty(format))
                {
                    var jsonResult = await TryGetProductListAsync("json", parameters);
                    if (jsonResult.Success)
                    {
                        return jsonResult;
                    }

                    // JSON başarısız olursa XML dene
                    return await TryGetProductListAsync("xml", parameters);
                }

                // Belirtilen format ile dene
                return await TryGetProductListAsync(format.ToLower(), parameters);
            }
            catch (Exception ex)
            {
                _logger.LogError(ex, "Ürün listesi çekilirken hata oluştu");
                return new EryazResponse
                {
                    Success = false,
                    Message = $"Hata: {ex.Message}"
                };
            }
        }

        private async Task<EryazResponse> TryGetProductListAsync(string format, object? parameters = null)
        {
            try
            {
                var client = new RestClient(_settings.IntegrationUrl);
                var request = new RestRequest(Method.POST);

                // Header'ları ayarla (PHP örneğine uygun)
                request.AddHeader("Cache-Control", "no-cache");
                request.AddHeader("Content-Type", "application/json");
                
                // Format'a göre Accept header'ı ayarla
                if (format == "json")
                {
                    request.AddHeader("Accept", "application/json");
                }
                else if (format == "xml")
                {
                    request.AddHeader("Accept", "application/xml");
                }

                // Parameters'ı belirle
                object? parametersObj = null;
                if (parameters != null)
                {
                    parametersObj = parameters;
                }
                else if (!string.IsNullOrEmpty(_settings.Parameters))
                {
                    // Eğer Parameters JSON string ise parse et
                    try
                    {
                        parametersObj = JsonConvert.DeserializeObject(_settings.Parameters);
                    }
                    catch
                    {
                        // JSON değilse boş string olarak gönder
                        parametersObj = "";
                    }
                }
                else
                {
                    parametersObj = "";
                }

                // Request body'yi oluştur
                object body = new
                {
                    CompanyKey = _settings.CompanyKey,
                    FunctionName = _settings.FunctionName,
                    UserName = _settings.UserName,
                    Password = _settings.Password,
                    Parameters = parametersObj
                };

                string json = JsonConvert.SerializeObject(body);
                request.AddParameter("application/json", json, ParameterType.RequestBody);

                _logger.LogInformation($"Eryaz API'ye istek gönderiliyor. Format: {format}, URL: {_settings.IntegrationUrl}");

                var response = await client.ExecuteAsync(request);

                if (response == null || !response.IsSuccessful)
                {
                    var statusCode = response?.StatusCode ?? System.Net.HttpStatusCode.InternalServerError;
                    var errorMessage = response?.ErrorMessage ?? "Yanıt alınamadı";
                    var content = response?.Content ?? "";
                    
                    _logger.LogWarning($"API yanıt hatası: {statusCode} - {content}");
                    return new EryazResponse
                    {
                        Success = false,
                        Message = $"API Hatası: {statusCode} - {errorMessage}",
                        RawData = content
                    };
                }

                if (string.IsNullOrEmpty(response.Content))
                {
                    return new EryazResponse
                    {
                        Success = false,
                        Message = "API'den boş yanıt alındı"
                    };
                }

                // Format'a göre parse et
                if (format == "json")
                {
                    return ParseJsonResponse(response.Content);
                }
                else if (format == "xml")
                {
                    return ParseXmlResponse(response.Content);
                }

                return new EryazResponse
                {
                    Success = false,
                    Message = "Desteklenmeyen format"
                };
            }
            catch (Exception ex)
            {
                _logger.LogError(ex, $"{format} formatında veri çekilirken hata oluştu");
                return new EryazResponse
                {
                    Success = false,
                    Message = $"{format} formatında hata: {ex.Message}"
                };
            }
        }

        private EryazResponse ParseJsonResponse(string jsonContent)
        {
            try
            {
                var jsonData = JsonConvert.DeserializeObject<dynamic>(jsonContent);
                var products = new List<Product>();

                // JSON yapısına göre ürünleri parse et
                if (jsonData != null)
                {
                    // Eğer direkt array ise
                    if (jsonData is Newtonsoft.Json.Linq.JArray jsonArray)
                    {
                        foreach (var item in jsonArray)
                        {
                            products.Add(ParseProductFromJson(item));
                        }
                    }
                    // Eğer object içinde data/Products gibi bir property varsa
                    else if (jsonData is Newtonsoft.Json.Linq.JObject jsonObject)
                    {
                        var dataProperty = jsonObject["Data"] ?? jsonObject["data"] ?? jsonObject["Products"] ?? jsonObject["products"];
                        if (dataProperty != null && dataProperty is Newtonsoft.Json.Linq.JArray)
                        {
                            foreach (var item in dataProperty)
                            {
                                products.Add(ParseProductFromJson(item));
                            }
                        }
                        else
                        {
                            // Tek bir ürün olabilir
                            products.Add(ParseProductFromJson(jsonObject));
                        }
                    }
                }

                return new EryazResponse
                {
                    Success = true,
                    Message = "Başarılı",
                    Products = products,
                    RawData = jsonData
                };
            }
            catch (Exception ex)
            {
                _logger.LogError(ex, "JSON parse hatası");
                return new EryazResponse
                {
                    Success = false,
                    Message = $"JSON parse hatası: {ex.Message}",
                    RawData = jsonContent
                };
            }
        }

        private Product ParseProductFromJson(dynamic item)
        {
            var product = new Product
            {
                AdditionalProperties = new Dictionary<string, object>()
            };

            try
            {
                if (item["Id"] != null) product.Id = Convert.ToInt32(item["Id"].ToString());
                if (item["ID"] != null) product.Id = Convert.ToInt32(item["ID"].ToString());
                if (item["id"] != null) product.Id = Convert.ToInt32(item["id"].ToString());

                product.Name = item["Name"]?.ToString() ?? item["NAME"]?.ToString() ?? item["name"]?.ToString();
                product.Code = item["Code"]?.ToString() ?? item["CODE"]?.ToString() ?? item["code"]?.ToString();
                product.Description = item["Description"]?.ToString() ?? item["DESCRIPTION"]?.ToString() ?? item["description"]?.ToString();
                product.ImageUrl = item["ImageUrl"]?.ToString() ?? item["IMAGEURL"]?.ToString() ?? item["imageurl"]?.ToString() ?? item["Image"]?.ToString();
                product.Category = item["Category"]?.ToString() ?? item["CATEGORY"]?.ToString() ?? item["category"]?.ToString();
                product.Brand = item["Brand"]?.ToString() ?? item["BRAND"]?.ToString() ?? item["brand"]?.ToString();

                if (item["Price"] != null) product.Price = Convert.ToDecimal(item["Price"].ToString());
                else if (item["PRICE"] != null) product.Price = Convert.ToDecimal(item["PRICE"].ToString());
                else if (item["price"] != null) product.Price = Convert.ToDecimal(item["price"].ToString());

                if (item["Stock"] != null) product.Stock = Convert.ToDecimal(item["Stock"].ToString());
                else if (item["STOCK"] != null) product.Stock = Convert.ToDecimal(item["STOCK"].ToString());
                else if (item["stock"] != null) product.Stock = Convert.ToDecimal(item["stock"].ToString());

                // Tüm property'leri AdditionalProperties'e ekle
                if (item is Newtonsoft.Json.Linq.JObject jObj)
                {
                    foreach (var prop in jObj.Properties())
                    {
                        if (!product.AdditionalProperties.ContainsKey(prop.Name))
                        {
                            product.AdditionalProperties[prop.Name] = prop.Value?.ToString() ?? "";
                        }
                    }
                }
            }
            catch (Exception ex)
            {
                _logger.LogWarning(ex, "Ürün parse edilirken hata oluştu");
            }

            return product;
        }

        private EryazResponse ParseXmlResponse(string xmlContent)
        {
            try
            {
                var products = new List<Product>();
                XDocument doc = XDocument.Parse(xmlContent);

                // XML yapısına göre ürünleri parse et
                var productElements = doc.Descendants().Where(x => 
                    x.Name.LocalName.Equals("Product", StringComparison.OrdinalIgnoreCase) ||
                    x.Name.LocalName.Equals("Item", StringComparison.OrdinalIgnoreCase) ||
                    x.Name.LocalName.Equals("Row", StringComparison.OrdinalIgnoreCase));

                foreach (var element in productElements)
                {
                    products.Add(ParseProductFromXml(element));
                }

                // Eğer hiç ürün bulunamadıysa, root element'i kontrol et
                if (products.Count == 0 && doc.Root != null)
                {
                    products.Add(ParseProductFromXml(doc.Root));
                }

                return new EryazResponse
                {
                    Success = true,
                    Message = "Başarılı",
                    Products = products,
                    RawData = xmlContent
                };
            }
            catch (Exception ex)
            {
                _logger.LogError(ex, "XML parse hatası");
                return new EryazResponse
                {
                    Success = false,
                    Message = $"XML parse hatası: {ex.Message}",
                    RawData = xmlContent
                };
            }
        }

        private Product ParseProductFromXml(XElement element)
        {
            var product = new Product
            {
                AdditionalProperties = new Dictionary<string, object>()
            };

            try
            {
                var idElement = element.Elements().FirstOrDefault(x => 
                    x.Name.LocalName.Equals("Id", StringComparison.OrdinalIgnoreCase) ||
                    x.Name.LocalName.Equals("ID", StringComparison.OrdinalIgnoreCase));
                if (idElement != null && int.TryParse(idElement.Value, out int id))
                {
                    product.Id = id;
                }

                product.Name = GetXmlElementValue(element, "Name", "NAME", "name");
                product.Code = GetXmlElementValue(element, "Code", "CODE", "code");
                product.Description = GetXmlElementValue(element, "Description", "DESCRIPTION", "description");
                product.ImageUrl = GetXmlElementValue(element, "ImageUrl", "IMAGEURL", "imageurl", "Image");
                product.Category = GetXmlElementValue(element, "Category", "CATEGORY", "category");
                product.Brand = GetXmlElementValue(element, "Brand", "BRAND", "brand");

                var priceValue = GetXmlElementValue(element, "Price", "PRICE", "price");
                if (!string.IsNullOrEmpty(priceValue) && decimal.TryParse(priceValue, out decimal price))
                {
                    product.Price = price;
                }

                var stockValue = GetXmlElementValue(element, "Stock", "STOCK", "stock");
                if (!string.IsNullOrEmpty(stockValue) && decimal.TryParse(stockValue, out decimal stock))
                {
                    product.Stock = stock;
                }

                // Tüm element'leri AdditionalProperties'e ekle
                foreach (var child in element.Elements())
                {
                    if (!product.AdditionalProperties.ContainsKey(child.Name.LocalName))
                    {
                        product.AdditionalProperties[child.Name.LocalName] = child.Value;
                    }
                }

                // Attribute'ları da ekle
                foreach (var attr in element.Attributes())
                {
                    if (!product.AdditionalProperties.ContainsKey(attr.Name.LocalName))
                    {
                        product.AdditionalProperties[attr.Name.LocalName] = attr.Value;
                    }
                }
            }
            catch (Exception ex)
            {
                _logger.LogWarning(ex, "XML ürün parse edilirken hata oluştu");
            }

            return product;
        }

        private string? GetXmlElementValue(XElement element, params string[] names)
        {
            foreach (var name in names)
            {
                var child = element.Elements().FirstOrDefault(x => 
                    x.Name.LocalName.Equals(name, StringComparison.OrdinalIgnoreCase));
                if (child != null && !string.IsNullOrEmpty(child.Value))
                {
                    return child.Value;
                }
            }
            return null;
        }
    }
}
