<?php

if($_POST || !empty($_FILES)){
	include '../../db-ayar.php';
	
	// $targetFolder değişkeninin tanımlı olduğundan emin ol
	if(!isset($targetFolder) || empty($targetFolder)){
		$targetFolder = '/upload/';
	}
	
	$time_al = time().'-'.uniqid();

	$verifyToken = 'sayim'.@$_POST['timestamp'].'sayim';
	
	// Hem 'Filedata' (uploadifive) hem de 'file' (drag & drop) desteği
	$fileField = 'Filedata';
	$tokenValid = false;
	
	if(isset($_FILES['file']) && !empty($_FILES['file']['name'])){
		$fileField = 'file';
		// Drag & drop için token kontrolü yap (eğer gönderilmişse)
		if(isset($_POST['token']) && isset($_POST['timestamp'])){
			$tokenValid = ($_POST['token'] == $verifyToken);
		} else {
			// Token gönderilmemişse (drag & drop için) geçerli kabul et
			$tokenValid = true;
		}
	} else {
		// Uploadifive için token kontrolü zorunlu
		$tokenValid = (isset($_POST['token']) && $_POST['token'] == $verifyToken);
	}

	if (!empty($_FILES) && $tokenValid) {
		
		$fileTypes = array('jpg','jpeg','JPG','JPEG','png','PNG','svg','gif');
		
		if(isset($_FILES[$fileField]) && !empty($_FILES[$fileField]['name'])){
			$fileParts = pathinfo($_FILES[$fileField]['name']);
			
			// Dosya uzantısı kontrolü
			if(!isset($fileParts['extension']) || empty($fileParts['extension'])){
				echo 2; // Geçersiz format
				exit;
			}
			
			$tempFile = $_FILES[$fileField]['tmp_name'];
			
			// Dosya yolu kontrolü
			if(empty($tempFile) || !is_uploaded_file($tempFile)){
				echo 3; // Dosya yükleme hatası
				exit;
			}
			
			$targetPath = $_SERVER['DOCUMENT_ROOT'] . $targetFolder;
			
			// Klasör yoksa oluştur
			if(!is_dir($targetPath)){
				if(!mkdir($targetPath, 0755, true)){
					echo 3; // Klasör oluşturma hatası
					exit;
				}
			}
			
			// Yazma izni kontrolü
			if(!is_writable($targetPath)){
				echo 3; // Yazma izni yok
				exit;
			}
			
			if (in_array(strtolower($fileParts['extension']), array('jpg','jpeg','png','svg','gif','webp'), true)) {
				require_once __DIR__ . '/../../../image-optimizer.php';
				$savedName = btm_optimize_image($tempFile, $targetPath, $time_al, $fileParts['extension']);
				if($savedName !== false){
					echo $savedName;
				} else {
					echo 3; // Dosya taşıma/optimize hatası
				}
			} else {
				echo 2; // Geçersiz dosya formatı
			}
		} else {
			echo 3; // Dosya bulunamadı
		}
		
	}else{
		echo 3; // Token hatası veya dosya yok
	}

}
?>