<?php
include '../../db-ayar.php';

if(isset($_POST['image_url']) && !empty($_POST['image_url'])){
	$imageUrl = $_POST['image_url'];
	
	// URL'den resmi indir
	$imageData = @file_get_contents($imageUrl);
	
	if($imageData === false){
		echo json_encode(array('success' => false, 'message' => 'Resim indirilemedi.'));
		exit;
	}
	
	// MIME type kontrolü
	$finfo = finfo_open(FILEINFO_MIME_TYPE);
	$mimeType = finfo_buffer($finfo, $imageData);
	finfo_close($finfo);
	
	$allowedTypes = array('image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/svg+xml');
	
	if(!in_array($mimeType, $allowedTypes)){
		echo json_encode(array('success' => false, 'message' => 'Geçersiz resim formatı.'));
		exit;
	}
	
	// Dosya uzantısını belirle
	$extension = '';
	switch($mimeType){
		case 'image/jpeg':
		case 'image/jpg':
			$extension = 'jpg';
			break;
		case 'image/png':
			$extension = 'png';
			break;
		case 'image/gif':
			$extension = 'gif';
			break;
		case 'image/svg+xml':
			$extension = 'svg';
			break;
	}
	
	if(empty($extension)){
		echo json_encode(array('success' => false, 'message' => 'Desteklenmeyen resim formatı.'));
		exit;
	}
	
	// Dosya adını oluştur (uzantısız taban; optimize fonksiyonu kesin uzantıyı belirler)
	$time_al = time().'-'.uniqid();
	
	// Upload klasörüne kaydet
	$targetPath = $_SERVER['DOCUMENT_ROOT'] . $targetFolder;
	
	if(!is_dir($targetPath)){
		mkdir($targetPath, 0755, true);
	}
	
	// İndirilen veriyi geçici dosyaya yazıp boyutlandır/sıkıştır.
	$tmpFile = tempnam(sys_get_temp_dir(), 'btmurl');
	file_put_contents($tmpFile, $imageData);
	require_once __DIR__ . '/../../../image-optimizer.php';
	$savedName = btm_optimize_image($tmpFile, $targetPath, $time_al, $extension);
	@unlink($tmpFile);
	
	if($savedName !== false){
		echo json_encode(array('success' => true, 'filename' => $savedName));
	} else {
		echo json_encode(array('success' => false, 'message' => 'Dosya kaydedilemedi.'));
	}
} else {
	echo json_encode(array('success' => false, 'message' => 'Resim URL\'si gönderilmedi.'));
}
?>

