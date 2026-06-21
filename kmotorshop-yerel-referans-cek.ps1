<#
  KMotorShop OEM referans + GORSEL cekme - YEREL calistirici.

  KMotorShop Cloudflare hosting IP'sini engelledigi icin (panel butonu 403 alir),
  veri YEREL bilgisayardan cekilir ve canli sitedeki receiver endpoint'ine gonderilir:
   - OEM referanslar -> urun_referans
   - Urun gorseli (/document/tecdoc/...) -> upload/ + urun_img (urunde gorsel yoksa)

  Kullanim:
    .\kmotorshop-yerel-referans-cek.ps1                         (tum Bosch urunleri, referans+gorsel)
    .\kmotorshop-yerel-referans-cek.ps1 -Site "https://btmotorshop.com" -Limit 30
    .\kmotorshop-yerel-referans-cek.ps1 -ProductId 55584 -Code "0445118041"
    .\kmotorshop-yerel-referans-cek.ps1 -WithImage 0           (sadece referans, gorsel cekme)

  Onceden sunucuya yukleyin: panel/kmotorshop-receiver.php (guncel surum).
  Is bitince receiver dosyasini silin.
#>

param(
    [string]$Site = "https://btmotorshop.com",
    [string]$Token = "btm-kms-2026",
    [int]$Limit = 40,
    [int]$OnlyNoReferans = 1,
    [int]$MaxRounds = 1000,
    [int]$DelayMs = 400,
    [int]$ProductId = 0,
    [string]$Code = "",
    [int]$WithImage = 1
)

$ErrorActionPreference = "Stop"
$UA = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0 Safari/537.36"
$receiver = "$Site/panel/kmotorshop-receiver.php"
$KmsBase = "https://www.kmotorshop.com"

function Get-SearchCodes([string]$code) {
    $clean = ($code -replace '^(30-|31-|32-|3e-?)', '').Trim()
    $digits = ($clean -replace '\D', '')
    $list = New-Object System.Collections.Generic.List[string]
    if ($clean) { [void]$list.Add($clean) }
    if ($digits -and ($digits -ne $clean)) { [void]$list.Add($digits) }
    if ($digits.Length -eq 10) {
        $spaced = $digits.Substring(0,1) + ' ' + $digits.Substring(1,3) + ' ' + $digits.Substring(4,3) + ' ' + $digits.Substring(7,3)
        if ($list -notcontains $spaced) { [void]$list.Add($spaced) }
    }
    return $list
}

function Get-Html([string]$url) {
    for ($try = 1; $try -le 3; $try++) {
        try {
            $html = & curl.exe -s -A $UA -H "Accept-Language: en-US,en;q=0.9" --max-time 35 $url
        } catch { $html = "" }
        if ($html -and $html.Length -gt 1200 -and ($html -notmatch 'Just a moment')) { return $html }
        Start-Sleep -Milliseconds (600 * $try)
    }
    return ""
}

function Get-DetailUrl([string]$listHtml, [string]$digits) {
    $links = [regex]::Matches($listHtml, '(?i)href="(/en/article-detail/view/\d+/[^"]+)"')
    $fallback = $null
    foreach ($m in $links) {
        $href = $m.Groups[1].Value
        if ($digits -and ($href -notmatch [regex]::Escape($digits))) { continue }
        if (-not $fallback) { $fallback = $href }
        if ($href -match '(?i)bosch') { return $href }
    }
    return $fallback
}

function Add-Ref([hashtable]$map, [string]$no, [string]$brand) {
    $no = ($no -replace '\s+', ' ').Trim()
    $brand = ($brand -replace '\s+', ' ').Trim()
    if (-not $no) { return }
    $norm = ($no -replace '[\s\.\-]', '').ToLower()
    if (-not $norm) { return }
    if ($map.ContainsKey($norm)) { return }
    if (-not $brand) { $brand = 'OEM' }
    $map[$norm] = @{ marka_adi = $brand.ToUpper(); referans_no = $no }
}

function Parse-ReferansFromDetail([string]$html, [string]$primaryCode) {
    $map = @{}
    $primaryNorm = (($primaryCode -replace '^(30-|31-|32-|3e-?)', '') -replace '[\s\.\-]', '').ToLower()
    $brands = 'CUMMINS|TEMSA|TATA|FORD|BOSCH|DAF|MAN|VOLVO|SCANIA|IVECO|RENAULT|PEUGEOT|CITROEN|NISSAN|TOYOTA|HYUNDAI|KIA|MAZDA|HONDA|MERCEDES|VW|AUDI|OPEL|BMW|JAGUAR'

    foreach ($m in [regex]::Matches($html, '(?i)/en/article-list/oe-list/([^"''?\s#]+)')) {
        $no = [uri]::UnescapeDataString($m.Groups[1].Value)
        if ((($no -replace '[\s\.\-]', '').ToLower()) -ne $primaryNorm) {
            Add-Ref $map $no 'OEM'
        }
    }

    foreach ($m in [regex]::Matches($html, '(?i)badge-table[^>]*>\s*([A-Z][A-Z\s]{1,40})\s*#\s*</span>\s*<span[^>]*>\s*([^<]+?)\s*</span>')) {
        Add-Ref $map $m.Groups[2].Value.Trim() $m.Groups[1].Value.Trim()
    }

    foreach ($m in [regex]::Matches($html, '(?i)(?:alt|title)="([^"]{12,})"')) {
        $alt = $m.Groups[1].Value
        if ($alt -notmatch ',') { continue }
        if (($alt -notmatch '(?i)document/tecdoc') -and ($alt -notmatch '^\d')) { continue }
        $parts = $alt -split ',\s*'
        if ($parts.Count -lt 4) { continue }
        for ($i = 3; $i -lt $parts.Count; $i++) {
            $token = $parts[$i].Trim()
            if (-not $token -or ($token -match '(?i)^https?://')) { continue }
            if ($token -match ('(?i)^(' + $brands + ')\s+#?\s*(.+)$')) {
                Add-Ref $map $matches[2] $matches[1]
            } else {
                Add-Ref $map $token 'OEM'
            }
        }
    }

    foreach ($m in [regex]::Matches($html, '(?i)For OE number:\s*</[^>]+>\s*([^<]+)')) {
        Add-Ref $map ($m.Groups[1].Value.Trim()) 'OEM'
    }

    foreach ($m in [regex]::Matches($html, ('(?i)\b(' + $brands + ')\s*(?:#|</|\|)\s*([0-9A-Z][0-9A-Z\.\s\-]{2,})'))) {
        Add-Ref $map $m.Groups[2].Value $m.Groups[1].Value
    }

    if ($html -match '(?i)/en/article-detail/view/\d+/([^"''?\s#]+)') {
        $slug = [uri]::UnescapeDataString($matches[1])
        if ($slug -match '(?i)-bosch-(.+)$') {
            foreach ($tok in ($matches[1] -split '-')) {
                $tok = ($tok -replace '_', ' ').Trim()
                if ($tok.Length -ge 4 -and ($tok -match '\d')) {
                    Add-Ref $map $tok 'OEM'
                }
            }
        }
    }

    if ($primaryNorm) { $map.Remove($primaryNorm) | Out-Null }
    return @($map.Values)
}

function Get-ImageUrlFromHtml([string]$html) {
    foreach ($m in [regex]::Matches($html, '(?i)(?:src|href)="([^"]+\.(?:jpg|jpeg|png|webp)(?:\?[^"]*)?)"')) {
        $u = $m.Groups[1].Value
        if ($u -match '^//') { $u = 'https:' + $u }
        elseif ($u -match '^/') { $u = $KmsBase + $u }
        $lu = $u.ToLower()
        if ($lu -match '/images/brand-logo/') { continue }
        if ($lu -match '/images/360_') { continue }
        if ($lu -match 'tn_600_ruzne') { continue }
        if ($lu -match '/document/tecdoc/') { return $u }
    }
    return ''
}

function Save-Image([int]$productId, [string]$imageUrl) {
    if (-not $imageUrl) { return @{ ok = $false; error = 'no_url' } }
    $ext = 'jpg'
    if ($imageUrl -match '\.(jpe?g|png|webp)(\?|$)') { $ext = ($matches[1].ToLower() -replace 'jpeg', 'jpg') }
    $tmp = Join-Path $env:TEMP ("kmsimg_" + [guid]::NewGuid().ToString('N') + "." + $ext)
    try { & curl.exe -s -A $UA --max-time 40 -o $tmp $imageUrl } catch { return @{ ok = $false; error = 'download' } }
    if (-not (Test-Path $tmp) -or (Get-Item $tmp).Length -lt 200) {
        if (Test-Path $tmp) { Remove-Item $tmp -Force -ErrorAction SilentlyContinue }
        return @{ ok = $false; error = 'empty' }
    }
    try {
        $resp = & curl.exe -s --max-time 60 -F "urun_id=$productId" -F "only_if_no_image=1" -F ("gorsel=@" + $tmp + ";type=image/jpeg") "$($receiver)?action=save&token=$Token"
        $j = $resp | ConvertFrom-Json
    } catch { $j = $null }
    Remove-Item $tmp -Force -ErrorAction SilentlyContinue
    if ($j -and $j.ok) { return @{ ok = $true; img = $j.img; skipped = $j.skipped } }
    return @{ ok = $false; error = 'upload' }
}

function Find-KmsDataForCode([string]$code) {
    $digits = (($code -replace '^(30-|31-|32-|3e-?)', '') -replace '\D', '')
    foreach ($search in (Get-SearchCodes $code)) {
        if (-not $search) { continue }
        $listUrl = "$KmsBase/en/article-list/oe-list/" + [uri]::EscapeDataString($search)
        $listHtml = Get-Html $listUrl
        if (-not $listHtml) { $listHtml = Get-Html ($listUrl + '*') }
        if (-not $listHtml) { continue }

        $detailPath = Get-DetailUrl $listHtml $digits
        if (-not $detailPath) { continue }

        $detailHtml = Get-Html ($KmsBase + $detailPath)
        if (-not $detailHtml) { continue }

        $refs = Parse-ReferansFromDetail $detailHtml $code
        $img = Get-ImageUrlFromHtml $detailHtml
        if (-not $img) { $img = Get-ImageUrlFromHtml $listHtml }
        if ($refs.Count -gt 0 -or $img) { return @{ refs = @($refs); image = $img } }
    }
    return @{ refs = @(); image = '' }
}

function Save-Referans([int]$productId, $referans) {
    $referansArr = @()
    if ($referans -is [System.Collections.IDictionary] -and $referans.Contains('referans_no')) {
        $referansArr = @($referans)
    } else {
        foreach ($item in @($referans)) {
            if ($null -eq $item) { continue }
            if ($item -is [System.Collections.IDictionary] -and $item.Contains('referans_no')) {
                $referansArr += $item
            }
        }
    }
    if ($referansArr.Count -eq 0) {
        return @{ ok = $false; error = 'bos_referans_listesi' }
    }

    $json = @{ urun_id = $productId; referans = $referansArr } | ConvertTo-Json -Depth 6 -Compress
    try {
        return Invoke-RestMethod -Uri "$($receiver)?action=save_referans&token=$Token" -Method Post -Body ([System.Text.Encoding]::UTF8.GetBytes($json)) -ContentType 'application/json; charset=utf-8' -TimeoutSec 40
    } catch {
        return @{ ok = $false; error = $_.Exception.Message }
    }
}

Write-Host "KMotorShop referans cekme basliyor. Site: $Site" -ForegroundColor Cyan

if ($ProductId -gt 0 -and $Code.Trim()) {
    $cleanCode = ($Code -replace '^(30-|31-|32-|3e-?)', '').Trim()
    Write-Host "Tek urun modu: #$ProductId kod=$cleanCode" -ForegroundColor Cyan
    $data = Find-KmsDataForCode $cleanCode
    $refs = @($data.refs)
    if ($refs.Count -gt 0) {
        try {
            $save = Save-Referans $ProductId $refs
            if ($save -and $save.ok -and [int]$save.added -gt 0) {
                Write-Host ("REFERANS EKLENDI: {0} yeni (toplam {1})" -f $save.added, $refs.Count) -ForegroundColor Green
            } elseif ($save -and $save.ok) {
                Write-Host "Referanslar zaten kayitli." -ForegroundColor DarkGray
            } else {
                Write-Host ("Referans kaydedilemedi: {0}" -f ($save.error)) -ForegroundColor Red
            }
        } catch {
            Write-Host "Referans kayit hatasi: $_" -ForegroundColor Red
        }
    } else {
        Write-Host "Referans bulunamadi." -ForegroundColor DarkYellow
    }
    if ($WithImage -eq 1 -and $data.image) {
        $imgSave = Save-Image $ProductId $data.image
        if ($imgSave.ok -and -not $imgSave.skipped) {
            Write-Host ("GORSEL EKLENDI: {0}" -f $imgSave.img) -ForegroundColor Green
        } elseif ($imgSave.ok -and $imgSave.skipped) {
            Write-Host "Gorsel zaten vardi." -ForegroundColor DarkGray
        } else {
            Write-Host ("Gorsel eklenemedi: {0}" -f $imgSave.error) -ForegroundColor DarkYellow
        }
    }
    if ($refs.Count -eq 0 -and -not $data.image) { exit 1 }
    exit 0
}

$lastId = 0
$totalOk = 0; $totalAdded = 0; $totalNotFound = 0; $totalErr = 0; $totalProcessed = 0; $totalImg = 0

for ($round = 1; $round -le $MaxRounds; $round++) {
    $listUrl = "$($receiver)?action=list_referans&token=$Token&limit=$Limit&last_id=$lastId&only_no_referans=$OnlyNoReferans"
    try {
        $raw = & curl.exe -s --max-time 40 $listUrl
        $resp = $raw | ConvertFrom-Json
    } catch {
        Write-Host "Liste alinamadi: $raw" -ForegroundColor Red
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
        $totalProcessed++
        $data = Find-KmsDataForCode $p.code
        $refs = @($data.refs)

        # Gorsel (varsa) — receiver mevcut gorseli olan urunu otomatik atlar.
        $imgInfo = ''
        if ($WithImage -eq 1 -and $data.image) {
            $imgSave = Save-Image ([int]$p.id) $data.image
            if ($imgSave.ok -and -not $imgSave.skipped) { $totalImg++; $imgInfo = ' +gorsel' }
        }

        if ($refs.Count -eq 0) {
            $totalNotFound++
            Write-Host ("  #{0} {1} -> referans bulunamadi{2}" -f $p.id, $p.code, $imgInfo) -ForegroundColor DarkYellow
            if ($DelayMs -gt 0) { Start-Sleep -Milliseconds $DelayMs }
            continue
        }

        try {
            $save = Save-Referans ([int]$p.id) $refs
        } catch {
            $totalErr++
            Write-Host ("  #{0} {1} -> kayit hatasi" -f $p.id, $p.code) -ForegroundColor Red
            continue
        }

        if ($save -and $save.ok -and [int]$save.added -gt 0) {
            $totalOk++
            $totalAdded += [int]$save.added
            Write-Host ("  #{0} {1} -> EKLENDI ({2} yeni referans, {3} bulundu){4}" -f $p.id, $p.code, $save.added, $refs.Count, $imgInfo) -ForegroundColor Green
        } elseif ($save -and $save.ok) {
            Write-Host ("  #{0} {1} -> zaten vardi{2}" -f $p.id, $p.code, $imgInfo) -ForegroundColor DarkGray
        } else {
            $totalErr++
            Write-Host ("  #{0} {1} -> kaydedilemedi" -f $p.id, $p.code) -ForegroundColor Red
        }

        if ($DelayMs -gt 0) { Start-Sleep -Milliseconds $DelayMs }
    }

    $lastId = $resp.next_last_id
    Write-Host ("Tur {0}: islenen={1} ok={2} eklenen-ref={3} bulunamayan={4} hata={5} last_id={6}" -f $round, $totalProcessed, $totalOk, $totalAdded, $totalNotFound, $totalErr, $lastId) -ForegroundColor Cyan
    if ($resp.done) { Write-Host "Bitti: tum urunler tarandi." -ForegroundColor Green; break }
}

Write-Host ("=== SONUC === urun-ok={0} yeni-referans={1} eklenen-gorsel={2} bulunamayan={3} hata={4}" -f $totalOk, $totalAdded, $totalImg, $totalNotFound, $totalErr) -ForegroundColor Cyan
