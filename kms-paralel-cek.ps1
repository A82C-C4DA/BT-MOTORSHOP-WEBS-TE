<#
  Bosch gorsel cekme - PARALEL baslatici.
  Birden fazla isci (worker) ayni anda calisir; her isci urunlerin bir bolumunu
  (id % ShardCount == Shard) isler, boylece cakisma olmaz ve is cok hizlanir.

  Kullanim:  .\kms-paralel-cek.ps1               (varsayilan 6 isci)
             .\kms-paralel-cek.ps1 -Workers 8
  Bitince her iscinin logu kms-log-0.txt, kms-log-1.txt ... dosyalarinda olur.
#>
param(
    [string]$Site = "https://btmotorshop.com",
    [string]$Token = "btm-kms-2026",
    [int]$Workers = 6,
    [int]$Limit = 60,
    [int]$OnlyNoImage = 1
)

$ErrorActionPreference = "Stop"
$worker = Join-Path $PSScriptRoot "kmotorshop-yerel-cek.ps1"

Write-Host ("{0} paralel isci baslatiliyor..." -f $Workers) -ForegroundColor Cyan
$procs = @()
for ($i = 0; $i -lt $Workers; $i++) {
    $log = Join-Path $PSScriptRoot ("kms-log-{0}.txt" -f $i)
    $cmd = "& '$worker' -Site '$Site' -Token '$Token' -Limit $Limit -OnlyNoImage $OnlyNoImage -Shard $i -ShardCount $Workers *>&1 | Tee-Object -FilePath '$log'"
    $p = Start-Process -FilePath "powershell" -ArgumentList @("-ExecutionPolicy","Bypass","-Command",$cmd) -WindowStyle Hidden -PassThru
    $procs += $p
    Write-Host ("  isci #{0} basladi (pid {1}) -> {2}" -f $i, $p.Id, (Split-Path $log -Leaf))
    Start-Sleep -Milliseconds 400
}

Write-Host "Tum isciler calisiyor. Bekleniyor..." -ForegroundColor Cyan
$procs | ForEach-Object { Wait-Process -Id $_.Id -ErrorAction SilentlyContinue }

# Toplam: her iscinin SONUC satirini topla.
$sumOk = 0; $sumImg = 0; $sumNF = 0; $sumErr = 0; $sumProc = 0
for ($i = 0; $i -lt $Workers; $i++) {
    $log = Join-Path $PSScriptRoot ("kms-log-{0}.txt" -f $i)
    if (-not (Test-Path $log)) { continue }
    $line = Select-String -Path $log -Pattern '=== SONUC ===' | Select-Object -Last 1
    if ($line) {
        $t = $line.Line
        if ($t -match 'islenen:(\d+)')      { $sumProc += [int]$Matches[1] }
        if ($t -match 'urun-ok:(\d+)')       { $sumOk  += [int]$Matches[1] }
        if ($t -match 'toplam-gorsel:(\d+)') { $sumImg += [int]$Matches[1] }
        if ($t -match 'bulunamayan:(\d+)')   { $sumNF  += [int]$Matches[1] }
        if ($t -match 'hata:(\d+)')          { $sumErr += [int]$Matches[1] }
    }
}

Write-Host ("=== SONUC === islenen:{0} urun-ok:{1} toplam-gorsel:{2} bulunamayan:{3} hata:{4}" -f $sumProc, $sumOk, $sumImg, $sumNF, $sumErr) -ForegroundColor Green
