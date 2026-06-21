<?php
if($_POST){
		

    $islem = $db->prepare("UPDATE shopier_api SET kullanici_adi = ?, sifre = ?");
    $islem = $islem->execute(array($_POST['kullanici_adi'],$_POST['sifre']));


    if($islem){
        echo b();
    }else{
        echo h();
    }

}

$duzenle = $db->query("SELECT * FROM shopier_api LIMIT 1")->fetch(PDO::FETCH_ASSOC);
?>
<div class="breadcrumb-header justify-content-between">
	<div class="my-auto">
		<div class="d-flex">
			<h4 class="content-title mb-0 my-auto">Shopier Sanal Pos Ayarı</h4><span class="text-muted mt-1 tx-13 ml-2 mb-0">/ Ekle - Düzenle</span>
		</div>
	</div>
</div>
<div class="row">
	<div class="col-md-6">
		<div class="card">
			<div class="card-header">
				<h4 class="card-title">Api Bilgileri</h4>
			</div>
			<div class="card-body">
				<form action="" method="post">
					<div class="form-group row">
						<label class="col-md-4 form-label">Kullanıcı Adı</label>
						<div class="col-md-8">
							<input type="text" class="form-control" name="kullanici_adi" value="<?php echo @$duzenle['kullanici_adi']; ?>" required="">
						</div>
					</div>
					<div class="form-group row">
						<label class="col-md-4 form-label">Şifre</label>
						<div class="col-md-8">
							<input type="text" class="form-control" name="sifre" value="<?php echo @$duzenle['sifre']; ?>" required="">
						</div>
					</div>
					<div class="form-group row">
						<label class="col-md-4 form-label"><b>Bildirim Url:</b></label>
						<div class="col-md-8"><?php echo $site; ?>shopier-sonuc</div>
					</div>

					<div class="form-group row">
						<div class="col-md-12"><center><button type="submit" class="btn btn-success">Değişiklikleri Kaydet</button></center></div>
					</div>

				</form>
			</div>
		</div>
	</div>
</div>
