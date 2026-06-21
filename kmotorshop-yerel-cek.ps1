<#
  Bosch görsel çekme - YEREL çalıştırıcı.
  Kaynak: https://www.onlineyedekparca.com  (izinli, Cloudflare yok)

  Görselleri SENİN bilgisayarından çeker ve canlı sitedeki receiver
  endpoint'ine yükler; receiver upload/ klasörüne kaydedip urun_img'e ekler.

  Mantık:
   - /arama/{kod} ile urunu bulur, /urun/{slug} sayfasini acar.
   - O urunun TUM galeri gorsellerini (slug-1.jpg, slug-2.jpg ...) ceker; -thumb haric.
   - Bir urunun TUM gorselleri inemezse o urune HIC eklemez (yarim kalmasin).

  Kullanım:  .\kmotorshop-yerel-cek.ps1
  İş bitince bu dosyayı ve sunucudaki panel/kmotorshop-receiver.php'yi silin.
#>

param(
    [string]$Site = "https://btmotorshop.com",
    [string]$Token = "btm-kms-2026",
    [int]$Limit = 50,
    [int]$OnlyNoImage = 1,
    [int]$MaxRounds = 1000,
    [int]$DelayMs = 0,
    [int]$Shard = 0,        # paralel calistirma: bu iscinin indeksi (0..ShardCount-1)
    [int]$ShardCount = 1    # toplam isci sayisi
)

$ErrorActionPreference = "Stop"
$UA = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0 Safari/537.36"
$receiver = "$Site/panel/kmotorshop-receiver.php"
$Base = "https://www.onlineyedekparca.com"

function Get-CandidateCodes([string]$code) {
    $clean = ($code -replace '^(30-|31-|32-|3e-?)', '').Trim()
    $digits = ($code -replace '\D', '')
    $cands = New-Object System.Collections.Generic.List[string]
    if ($clean) { [void]$cands.Add($clean) }
    if ($digits -and ($digits -ne $clean)) { [void]$cands.Add($digits) }
    return $cands
}

function Get-Html([string]$url) {
    # Not: onlineyedekparca arama/urun sayfalari bazen kucuk (~5KB) ama gecerli
    # (urun linkleri ve gorseller iceren) yanit donuyor; bu yuzden esik dusuk tutulur.
    for ($try = 1; $try -le 3; $try++) {
        try {
            $html = & curl.exe -s -A $UA -H "Accept-Language: tr,en;q=0.8" --max-time 30 $url
        } catch { $html = "" }
        if ($html -and $html.Length -gt 800) { return $html }
        Start-Sleep -Milliseconds (500 * $try)
    }
    return ""
}

function Test-IsImage([string]$path) {
    if (-not (Test-Path $path)) { return $false }
    if ((Get-Item $path).Length -lt 800) { return $false }
    try {
        $fs = [System.IO.File]::OpenRead($path)
        $buf = New-Object byte[] 12
        $n = $fs.Read($buf, 0, 12)
        $fs.Close()
    } catch { return $false }
    if ($n -lt 12) { return $false }
    if ($buf[0] -eq 0xFF -and $buf[1] -eq 0xD8 -and $buf[2] -eq 0xFF) { return $true }                       # JPEG
    if ($buf[0] -eq 0x89 -and $buf[1] -eq 0x50 -and $buf[2] -eq 0x4E -and $buf[3] -eq 0x47) { return $true }   # PNG
    if ($buf[0] -eq 0x47 -and $buf[1] -eq 0x49 -and $buf[2] -eq 0x46 -and $buf[3] -eq 0x38) { return $true }   # GIF
    if ($buf[0] -eq 0x52 -and $buf[1] -eq 0x49 -and $buf[2] -eq 0x46 -and $buf[3] -eq 0x46 -and $buf[8] -eq 0x57 -and $buf[9] -eq 0x45 -and $buf[10] -eq 0x42 -and $buf[11] -eq 0x50) { return $true } # WEBP
    return $false
}

function Save-Image([string]$url, [string]$referer, [string]$outPath) {
    for ($try = 1; $try -le 3; $try++) {
        if (Test-Path $outPath) { Remove-Item $outPath -Force -ErrorAction SilentlyContinue }
        try {
            & curl.exe -s -A $UA -e $referer --max-time 40 -o $outPath $url | Out-Null
        } catch {}
        if (Test-IsImage $outPath) { return $true }
        Start-Sleep -Milliseconds (400 * $try)
    }
    return $false
}

function Find-ImageUrls([string]$code) {
    $digits = ($code -replace '\D', '')
    foreach ($s in (Get-CandidateCodes $code)) {
        if (-not $s) { continue }
        $searchUrl = "$Base/arama/" + [uri]::EscapeDataString($s)
        $html = Get-Html $searchUrl
        if (-not $html) { continue }

        # Arama sonucundan eslesen urun linkini sec (slug'da kod gecen, tercihen bosch).
        $links = [regex]::Matches($html, '(?i)href="(/urun/[^"]+)"')
        $chosen = $null
        if ($digits) {
            foreach ($l in $links) {
                $href = $l.Groups[1].Value
                if (($href -match [regex]::Escape($digits)) -and ($href -match '(?i)bosch')) { $chosen = $href; break }
            }
            if (-not $chosen) {
                foreach ($l in $links) {
                    $href = $l.Groups[1].Value
                    if ($href -match [regex]::Escape($digits)) { $chosen = $href; break }
                }
            }
        }
        if (-not $chosen) { continue }

        $slug = ($chosen -replace '^/urun/', '') -replace '[/?#].*$', ''
        $slugEsc = [regex]::Escape($slug)

        $detailUrl = "$Base$chosen"
        $ph = Get-Html $detailUrl
        if (-not $ph) { continue }

        # Urun galeri gorselleri: cdn.onl.com.tr ... {slug}(-N).(jpg|png|webp), -thumb HARIC.
        $ms = [regex]::Matches($ph, "(?i)https://cdn\.onl\.com\.tr/images/[^""'' ]*$slugEsc[^""'' ]*\.(?:jpg|jpeg|png|webp)")
        $urls = @()
        foreach ($m in $ms) {
            $u = $m.Value
            if ($u -match '(?i)-thumb') { continue }
            if ($urls -notcontains $u) { $urls += $u }
        }
        if ($urls.Count -gt 0) {
            return @{ images = $urls; referer = $detailUrl }
        }
    }
    return $null
}

Write-Host "Bosch gorsel cekme basliyor (onlineyedekparca). Site: $Site" -ForegroundColor Cyan

$lastId = 0
$totalImgs = 0; $totalOk = 0; $totalNotFound = 0; $totalErr = 0; $totalProcessed = 0
$tmpDir = Join-Path $env:TEMP "kms_yerel"
if (-not (Test-Path $tmpDir)) { New-Item -ItemType Directory -Path $tmpDir | Out-Null }

for ($round = 1; $round -le $MaxRounds; $round++) {
    $listUrl = "$($receiver)?action=list&token=$Token&limit=$Limit&last_id=$lastId&only_no_image=$OnlyNoImage"
    try {
        $raw = & curl.exe -s --max-time 40 $listUrl
        $resp = $raw | ConvertFrom-Json
    } catch {
        Write-Host "Liste alinamadi (receiver yuklendi mi? token dogru mu?): $raw" -ForegroundColor Red
        break
    }
    if (-not $resp.ok) {
        Write-Host "Receiver hata: $raw" -ForegroundColor Red
        break
    }

    $products = $resp.products
    if (-not $products -or $products.Count -eq 0) {
        if ($resp.done) { Write-Host "Bitti: taranacak urun kalmadi." -ForegroundColor Green; break }
        $lastId = $resp.next_last_id
        continue
    }

    foreach ($p in $products) {
        # Paralel calistirmada her urunu yalnizca tek isci islesin (cakismayi onler).
        if ($ShardCount -gt 1 -and (([int]$p.id % $ShardCount) -ne $Shard)) { continue }

        $totalProcessed++
        $found = Find-ImageUrls $p.code
        if (-not $found -or $found.images.Count -eq 0) {
            $totalNotFound++
            Write-Host ("  #{0} {1} -> bulunamadi" -f $p.id, $p.code) -ForegroundColor DarkYellow
            continue
        }

        # Once TUM gorselleri yerel indir (hepsi gercek gorsel olmali).
        $localFiles = @()
        $allOk = $true
        $idx = 0
        foreach ($imgUrl in $found.images) {
            $idx++
            $fp = Join-Path $tmpDir ("img_{0}_{1}.bin" -f $p.id, $idx)
            if (Save-Image $imgUrl $found.referer $fp) {
                $localFiles += $fp
            } else {
                $allOk = $false
                break
            }
        }

        if (-not $allOk -or $localFiles.Count -eq 0) {
            foreach ($f in $localFiles) { Remove-Item $f -Force -ErrorAction SilentlyContinue }
            $totalErr++
            Write-Host ("  #{0} {1} -> atlandi (tum gorseller inemedi, sonraki turda tekrar)" -f $p.id, $p.code) -ForegroundColor Red
            continue
        }

        $added = 0
        foreach ($f in $localFiles) {
            $up = & curl.exe -s -A $UA -F "token=$Token" -F "action=save" -F ("urun_id=" + $p.id) -F ("gorsel=@`"$f`";type=image/jpeg;filename=img.jpg") $receiver
            try { $upJson = $up | ConvertFrom-Json } catch { $upJson = $null }
            if ($upJson -and $upJson.ok) { $added++; $totalImgs++ }
            Remove-Item $f -Force -ErrorAction SilentlyContinue
        }

        if ($added -gt 0) {
            $totalOk++
            Write-Host ("  #{0} {1} -> EKLENDI ({2} gorsel)" -f $p.id, $p.code, $added) -ForegroundColor Green
        } else {
            $totalErr++
            Write-Host ("  #{0} {1} -> kaydedilemedi" -f $p.id, $p.code) -ForegroundColor Red
        }

        if ($DelayMs -gt 0) { Start-Sleep -Milliseconds $DelayMs }
    }

    $lastId = $resp.next_last_id
    Write-Host ("Tur {0} bitti. islenen:{1} urun-ok:{2} gorsel:{3} bulunamayan:{4} hata:{5} (last_id={6})" -f $round, $totalProcessed, $totalOk, $totalImgs, $totalNotFound, $totalErr, $lastId) -ForegroundColor Cyan
    if ($resp.done) { Write-Host "Bitti: tum urunler tarandi." -ForegroundColor Green; break }
}

Get-ChildItem -Path $tmpDir -File -ErrorAction SilentlyContinue | Remove-Item -Force -ErrorAction SilentlyContinue
Write-Host ("=== SONUC === islenen:{0} urun-ok:{1} toplam-gorsel:{2} bulunamayan:{3} hata:{4}" -f $totalProcessed, $totalOk, $totalImgs, $totalNotFound, $totalErr) -ForegroundColor Cyan
