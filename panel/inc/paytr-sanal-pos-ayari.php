<?php
if($_POST){
		

    $islem = $db->prepare("UPDATE paytr_api SET merchant_id = ?, merchant_key = ?, merchant_salt = ?");
    $islem = $islem->execute(array($_POST['merchant_id'],$_POST['merchant_key'],$_POST['merchant_salt']));


    if($islem){
        echo b();
    }else{
        echo h();
    }

}

$duzenle = $db->query("SELECT * FROM paytr_api LIMIT 1")->fetch(PDO::FETCH_ASSOC);
?>
<div class="breadcrumb-header justify-content-between">
	<div class="my-auto">
		<div class="d-flex">
			<h4 class="content-title mb-0 my-auto">Paytr Sanal Pos Ayarı</h4><span class="text-muted mt-1 tx-13 ml-2 mb-0">/ Ekle - Düzenle</span>
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
						<label class="col-md-4 form-label">merchant_id	</label>
						<div class="col-md-8">
							<input type="text" class="form-control" name="merchant_id" value="<?php echo @$duzenle['merchant_id']; ?>" required="">
						</div>
					</div>
					<div class="form-group row">
						<label class="col-md-4 form-label">merchant_key</label>
						<div class="col-md-8">
							<input type="text" class="form-control" name="merchant_key" value="<?php echo @$duzenle['merchant_key']; ?>" required="">
						</div>
					</div>
					<div class="form-group row">
						<label class="col-md-4 form-label">merchant_salt</label>
						<div class="col-md-8">
							<input type="text" class="form-control" name="merchant_salt" value="<?php echo @$duzenle['merchant_salt']; ?>" required="">
						</div>
					</div>
					<div class="form-group row">
						<label class="col-md-4 form-label"><b>Bildirim Url:</b></label>
						<div class="col-md-8"><?php echo $site; ?>paytr-bildirim.php</div>
					</div>

					<div class="form-group row">
						<div class="col-md-12"><center><button type="submit" class="btn btn-success">Değişiklikleri Kaydet</button></center></div>
					</div>

				</form>
			</div>
		</div>
	</div>
</div>
