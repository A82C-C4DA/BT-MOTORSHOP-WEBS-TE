<?php
if($_POST){
		

    $islem = $db->prepare("UPDATE iyzico_api SET setApiKey = ?, setSecretKey = ?");
    $islem = $islem->execute(array($_POST['setApiKey'],$_POST['setSecretKey']));


    if($islem){
        echo b();
    }else{
        echo h();
    }

}

$duzenle = $db->query("SELECT * FROM iyzico_api LIMIT 1")->fetch(PDO::FETCH_ASSOC);
?>
<div class="breadcrumb-header justify-content-between">
	<div class="my-auto">
		<div class="d-flex">
			<h4 class="content-title mb-0 my-auto">İyzico Sanal Pos Ayarı</h4><span class="text-muted mt-1 tx-13 ml-2 mb-0">/ Ekle - Düzenle</span>
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
						<label class="col-md-4 form-label">setApiKey</label>
						<div class="col-md-8">
							<input type="text" class="form-control" name="setApiKey" value="<?php echo @$duzenle['setApiKey']; ?>" required="">
						</div>
					</div>
					<div class="form-group row">
						<label class="col-md-4 form-label">setSecretKey</label>
						<div class="col-md-8">
							<input type="text" class="form-control" name="setSecretKey" value="<?php echo @$duzenle['setSecretKey']; ?>" required="">
						</div>
					</div>

					<div class="form-group row">
						<div class="col-md-12"><center><button type="submit" class="btn btn-success">Değişiklikleri Kaydet</button></center></div>
					</div>

				</form>
			</div>
		</div>
	</div>
</div>
