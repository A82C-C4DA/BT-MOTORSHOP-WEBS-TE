namespace EryazIntegrationService.Models
{
    public class EryazSettings
    {
        public string IntegrationUrl { get; set; } = string.Empty;
        public string CompanyKey { get; set; } = string.Empty;
        public string UserName { get; set; } = string.Empty;
        public string Password { get; set; } = string.Empty;
        public string FunctionName { get; set; } = string.Empty;
        public string? Parameters { get; set; } // JSON string veya boş string olabilir
    }
}

