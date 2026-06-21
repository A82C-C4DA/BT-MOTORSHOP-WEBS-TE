<?php
if($_POST){
		

    $islem = $db->prepare("UPDATE netgsm_ayari SET username = ?, password = ?, header = ?");
    $islem = $islem->execute(array($_POST['username'],$_POST['password'],$_POST['header']));


    if($islem){
        echo b();
    }else{
        echo h();
    }

}

$duzenle = $db->query("SELECT * FROM netgsm_ayari LIMIT 1")->fetch(PDO::FETCH_ASSOC);
?>
<div class="breadcrumb-header justify-content-between">
	<div class="my-auto">
		<div class="d-flex">
			<h4 class="content-title mb-0 my-auto">NetGsm</h4><span class="text-muted mt-1 tx-13 ml-2 mb-0">/ Ekle - Düzenle</span>
		</div>
	</div>
</div>

<div class="row">
	<div class="col-md-6">
		<div class="card">
			<div class="card-header pb-0">
				<div class="d-flex justify-content-between">
					<h4 class="card-title mg-b-0">Eklenen Veriler</h4>
				</div>
			</div>
			<div class="card-body">
				<form action="" method="post">
					<div class="form-group row">
						<label class="col-md-4 form-label">Header</label>
						<div class="col-md-8">
							<input type="text" class="form-control" name="header" value="<?php echo @$duzenle['header']; ?>" >
						</div>
					</div>
					<div class="form-group row">
						<label class="col-md-4 form-label">Kullanıcı Adı</label>
						<div class="col-md-8">
							<input type="text" class="form-control" name="username" value="<?php echo @$duzenle['username']; ?>" >
						</div>
					</div>
					<div class="form-group row">
						<label class="col-md-4 form-label">Şifre</label>
						<div class="col-md-8">
							<input type="text" class="form-control" name="password" value="<?php echo @$duzenle['password']; ?>">
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
