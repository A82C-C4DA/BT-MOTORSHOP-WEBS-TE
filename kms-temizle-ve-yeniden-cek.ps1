<#
  Bu oturumda (eski, marka kontrolu olmayan mantikla) eklenen Bosch gorsellerini
  siler; ardindan strict (sadece Bosch / klasor 30) mantikla yeniden ceker.

  Once panel/kmotorshop-receiver.php'nin guncel surumunu (delete action'li) sunucuya yukleyin.
  Kullanim: .\kms-temizle-ve-yeniden-cek.ps1
#>
param(
    [string]$Site = "https://btmotorshop.com",
    [string]$Token = "btm-kms-2026"
)
$ErrorActionPreference = "Stop"
$receiver = "$Site/panel/kmotorshop-receiver.php"

# Bu oturumda (tum onceki turlarda) gorsel eklenen urun ID'leri.
$ids = @(
    2266,2267,2268,2269,2271,2272,2273,2274,2275,2276,2277,2278,2281,
    2287,2289,2290,2291,2292,2293,2294,2295,2298,2299,2300,2301,2302,2303,2304,
    2306,2309,2310,2311,2312,2313,2314,2316,2318,2319,2320,2321,2323,2324,2325,
    2329,2330,2332,2334,2335,2336,2338,2339,2340,2343,2344,2347,2348,2355,2356
)

Write-Host ("Silinecek urun sayisi: {0}" -f $ids.Count) -ForegroundColor Cyan
$del = 0
foreach ($id in $ids) {
    $url = "$($receiver)?action=delete&token=$Token&urun_id=$id"
    try {
        $r = & curl.exe -s --max-time 30 $url
        $j = $r | ConvertFrom-Json
        if ($j.ok) { $del++; Write-Host ("  #{0} silindi ({1} gorsel)" -f $id, $j.deleted) }
        else { Write-Host ("  #{0} silinemedi: {1}" -f $id, $r) -ForegroundColor Red }
    } catch {
        Write-Host ("  #{0} hata: {1}" -f $id, $r) -ForegroundColor Red
    }
}
Write-Host ("Silme bitti: {0}/{1}" -f $del, $ids.Count) -ForegroundColor Green
Write-Host "Strict (Bosch-only) cekme baslatiliyor..." -ForegroundColor Cyan

& "$PSScriptRoot\kmotorshop-yerel-cek.ps1" -Site $Site -Token $Token -Limit 50 -MaxRounds 1000
