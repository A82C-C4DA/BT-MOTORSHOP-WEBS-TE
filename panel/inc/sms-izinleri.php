<?php
if($_POST){

	$islem = $db->prepare("UPDATE sms_izinleri SET kullanici_kayit = ?, sifre_sifirlama = ?, siparis_durumu = ?, siparis_yonetici = ?");
    $islem = $islem->execute(array(0,0,0,0));

	
	if(empty($_POST['kullanici_kayit'])){ $_POST['kullanici_kayit'] = 0; }
	if(empty($_POST['sifre_sifirlama'])){ $_POST['sifre_sifirlama'] = 0; }
	if(empty($_POST['siparis_durumu'])){ $_POST['siparis_durumu'] = 0; }
	if(empty($_POST['siparis_yonetici'])){ $_POST['siparis_yonetici'] = 0; }

    $islem = $db->prepare("UPDATE sms_izinleri SET kullanici_kayit = ?, sifre_sifirlama = ?, siparis_durumu = ?, siparis_yonetici = ?");
    $islem = $islem->execute(array($_POST['kullanici_kayit'],$_POST['sifre_sifirlama'],$_POST['siparis_durumu'],$_POST['siparis_yonetici']));


    if($islem){
        echo b();
    }else{
        echo h();
    }

}

$duzenle = $db->query("SELECT * FROM sms_izinleri LIMIT 1")->fetch(PDO::FETCH_ASSOC);
?>

<script type="text/javascript">
	$(function(){
		$('[name="kullanici_kayit"][value="<?php echo $duzenle['kullanici_kayit']; ?>"]').attr('checked','checked');
		$('[name="sifre_sifirlama"][value="<?php echo $duzenle['sifre_sifirlama']; ?>"]').attr('checked','checked');
		$('[name="siparis_durumu"][value="<?php echo $duzenle['siparis_durumu']; ?>"]').attr('checked','checked');
		$('[name="siparis_yonetici"][value="<?php echo $duzenle['siparis_yonetici']; ?>"]').attr('checked','checked');
	});
</script>
	
<div class="breadcrumb-header justify-content-between">
	<div class="my-auto">
		<div class="d-flex">
			<h4 class="content-title mb-0 my-auto">Sms İzinleri</h4><span class="text-muted mt-1 tx-13 ml-2 mb-0">/ Ekle - Düzenle</span>
		</div>
	</div>
</div>

<div class="row">
	<div class="col-md-6">
		<div class="card">
			<div class="card-body">
				<form action="" method="post">
					
					<div class="form-group row">
						<div class="col-md-12 custom-controls-stacked">
							<label class="custom-control custom-checkbox">
								<input type="checkbox" class="custom-control-input" name="kullanici_kayit" value="1">
								<span class="custom-control-label">Kullanıcı Kayıt Olduğunda Hoşgeldin Mesajı</span>
							</label>

							<label class="custom-control custom-checkbox">
								<input type="checkbox" class="custom-control-input" name="sifre_sifirlama" value="1">
								<span class="custom-control-label">Kullanıcı ve Mağaza Şifresini Sıfırladığında</span>
							</label>
							<label class="custom-control custom-checkbox">
								<input type="checkbox" class="custom-control-input" name="siparis_durumu" value="1">
								<span class="custom-control-label">Sipariş Durumu Değiştiğinde Alıcıya Bilgi Ver</span>
							</label>
							<label class="custom-control custom-checkbox">
								<input type="checkbox" class="custom-control-input" name="siparis_yonetici" value="1">
								<span class="custom-control-label">Sipariş Oluşturulduğunda Yöneticiye Haber Ver</span>
							</label>
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
