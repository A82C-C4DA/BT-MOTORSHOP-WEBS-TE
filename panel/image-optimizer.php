<?php
/**
 * Görselleri sunucuya kaydetmeden önce yeniden boyutlandırıp sıkıştırır.
 * Mümkünse WebP'ye çevirir (en küçük dosya); WebP desteklenmiyorsa
 * orijinal formatında (JPG/PNG) sıkıştırarak kaydeder.
 * SVG ve GIF gibi formatlar (vektör/animasyon korunsun diye) olduğu gibi taşınır.
 *
 * @return string|false Kaydedilen dosyanın adı (uzantısıyla) ya da hata.
 */
if (!function_exists('btm_optimize_image')) {
	function btm_optimize_image($sourceFile, $targetDir, $baseName, $extension, $maxDim = 1920, $quality = 82) {
		$extension = strtolower(trim((string)$extension));
		$targetDir = rtrim($targetDir, "/\\");
		$rasterTypes = array('jpg', 'jpeg', 'png');

		// Sıkıştırılamayan / dokunulmaması gereken formatlar: olduğu gibi taşı.
		if (!in_array($extension, $rasterTypes, true) || !function_exists('imagecreatetruecolor')) {
			$fileName = $baseName . '.' . $extension;
			$dest = $targetDir . '/' . $fileName;
			if (@is_uploaded_file($sourceFile)) {
				return @move_uploaded_file($sourceFile, $dest) ? $fileName : false;
			}
			return @copy($sourceFile, $dest) ? $fileName : false;
		}

		// Kaynağı belleğe al.
		if ($extension === 'png') {
			$img = @imagecreatefrompng($sourceFile);
		} else {
			$img = @imagecreatefromjpeg($sourceFile);
		}

		// GD okuyamazsa orijinali olduğu gibi taşı (veri kaybetme).
		if (!$img) {
			$fileName = $baseName . '.' . $extension;
			$dest = $targetDir . '/' . $fileName;
			if (@is_uploaded_file($sourceFile)) {
				return @move_uploaded_file($sourceFile, $dest) ? $fileName : false;
			}
			return @copy($sourceFile, $dest) ? $fileName : false;
		}

		$w = imagesx($img);
		$h = imagesy($img);

		// Oranı koruyarak küçült (sadece maksimum boyuttan büyükse).
		if ($w > $maxDim || $h > $maxDim) {
			$ratio = min($maxDim / $w, $maxDim / $h);
			$nw = max(1, (int)round($w * $ratio));
			$nh = max(1, (int)round($h * $ratio));
			$resized = imagecreatetruecolor($nw, $nh);
			imagealphablending($resized, false);
			imagesavealpha($resized, true);
			$transparent = imagecolorallocatealpha($resized, 0, 0, 0, 127);
			imagefilledrectangle($resized, 0, 0, $nw, $nh, $transparent);
			imagecopyresampled($resized, $img, 0, 0, 0, 0, $nw, $nh, $w, $h);
			imagedestroy($img);
			$img = $resized;
		}

		// 1) WebP destekleniyorsa WebP olarak kaydet (en iyi sıkıştırma).
		if (function_exists('imagewebp')) {
			if (function_exists('imagepalettetotruecolor')) {
				@imagepalettetotruecolor($img);
			}
			imagealphablending($img, false);
			imagesavealpha($img, true);
			$fileName = $baseName . '.webp';
			$dest = $targetDir . '/' . $fileName;
			$ok = @imagewebp($img, $dest, $quality);
			imagedestroy($img);
			return $ok ? $fileName : false;
		}

		// 2) WebP yoksa orijinal formatta sıkıştır.
		if ($extension === 'png') {
			imagealphablending($img, false);
			imagesavealpha($img, true);
			$fileName = $baseName . '.png';
			$dest = $targetDir . '/' . $fileName;
			$ok = @imagepng($img, $dest, 8); // 0-9 (9 = en yüksek sıkıştırma)
		} else {
			$fileName = $baseName . '.jpg';
			$dest = $targetDir . '/' . $fileName;
			$ok = @imagejpeg($img, $dest, $quality);
		}
		imagedestroy($img);
		return $ok ? $fileName : false;
	}
}
