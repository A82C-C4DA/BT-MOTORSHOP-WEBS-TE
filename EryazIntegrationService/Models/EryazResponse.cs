namespace EryazIntegrationService.Models
{
    public class EryazResponse
    {
        public bool Success { get; set; }
        public string? Message { get; set; }
        public List<Product>? Products { get; set; }
        public object? RawData { get; set; }
    }
}

