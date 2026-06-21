using Microsoft.AspNetCore.Mvc;
using EryazIntegrationService.Services;
using EryazIntegrationService.Models;

namespace EryazIntegrationService.Controllers
{
    [ApiController]
    [Route("api/[controller]")]
    public class ProductController : ControllerBase
    {
        private readonly EryazIntegrationService _eryazService;
        private readonly ILogger<ProductController> _logger;

        public ProductController(
            EryazIntegrationService eryazService,
            ILogger<ProductController> logger)
        {
            _eryazService = eryazService;
            _logger = logger;
        }

        /// <summary>
        /// Ürün listesini getirir. Öncelik JSON, sonra XML formatında dener.
        /// </summary>
        /// <param name="format">İstenen format (json veya xml). Belirtilmezse önce JSON, sonra XML denenir.</param>
        /// <param name="pStart">Başlangıç kayıt numarası (opsiyonel)</param>
        /// <param name="pEnd">Bitiş kayıt numarası (opsiyonel)</param>
        /// <returns>Ürün listesi</returns>
        [HttpGet("GetProductList")]
        [ProducesResponseType(typeof(EryazResponse), StatusCodes.Status200OK)]
        [ProducesResponseType(StatusCodes.Status500InternalServerError)]
        public async Task<ActionResult<EryazResponse>> GetProductList(
            [FromQuery] string? format = null,
            [FromQuery] int? pStart = null,
            [FromQuery] int? pEnd = null)
        {
            try
            {
                _logger.LogInformation("GetProductList isteği alındı. Format: {Format}, pStart: {PStart}, pEnd: {PEnd}", 
                    format ?? "auto", pStart, pEnd);

                // Parameters objesi oluştur
                object? parameters = null;
                if (pStart.HasValue || pEnd.HasValue)
                {
                    parameters = new { @pStart = pStart ?? 1, @pEnd = pEnd ?? 1000 };
                }

                var result = await _eryazService.GetProductListAsync(format, parameters);

                if (result.Success)
                {
                    return Ok(result);
                }
                else
                {
                    return StatusCode(500, result);
                }
            }
            catch (Exception ex)
            {
                _logger.LogError(ex, "GetProductList işlemi sırasında hata oluştu");
                return StatusCode(500, new EryazResponse
                {
                    Success = false,
                    Message = $"Sunucu hatası: {ex.Message}"
                });
            }
        }

        /// <summary>
        /// Ürün listesini JSON formatında getirir.
        /// </summary>
        [HttpGet("GetProductListJson")]
        [ProducesResponseType(typeof(EryazResponse), StatusCodes.Status200OK)]
        public async Task<ActionResult<EryazResponse>> GetProductListJson()
        {
            return await GetProductList("json");
        }

        /// <summary>
        /// Ürün listesini XML formatında getirir.
        /// </summary>
        [HttpGet("GetProductListXml")]
        [ProducesResponseType(typeof(EryazResponse), StatusCodes.Status200OK)]
        public async Task<ActionResult<EryazResponse>> GetProductListXml()
        {
            return await GetProductList("xml");
        }
    }
}

