using EryazIntegrationService.Models;
using EryazIntegrationService.Services;

var builder = WebApplication.CreateBuilder(args);

// Add services to the container.
builder.Services.AddControllers();
builder.Services.AddEndpointsApiExplorer();
builder.Services.AddSwaggerGen();

// Eryaz Settings Configuration
var eryazSettings = new EryazSettings();
builder.Configuration.GetSection("EryazSettings").Bind(eryazSettings);
builder.Services.AddSingleton(eryazSettings);

// EryazIntegrationService
builder.Services.AddScoped<EryazIntegrationService>();

// CORS ayarları (gerekirse)
builder.Services.AddCors(options =>
{
    options.AddDefaultPolicy(policy =>
    {
        policy.AllowAnyOrigin()
              .AllowAnyMethod()
              .AllowAnyHeader();
    });
});

var app = builder.Build();

// Configure the HTTP request pipeline.
if (app.Environment.IsDevelopment())
{
    app.UseSwagger();
    app.UseSwaggerUI();
}

app.UseHttpsRedirection();
app.UseCors();
app.UseAuthorization();
app.MapControllers();

app.Run();

