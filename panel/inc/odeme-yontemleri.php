<?php
if($_POST){
	
	if(empty($_POST['online_odeme'])){ $_POST['online_odeme'] = 0; }
	if(empty($_POST['banka_havalesi'])){ $_POST['banka_havalesi'] = 0; }
	if(empty($_POST['kapida_nakit'])){ $_POST['kapida_nakit'] = 0; }
	if(empty($_POST['kapida_kredi_karti'])){ $_POST['kapida_kredi_karti'] = 0; }

    $islem = $db->prepare("UPDATE odeme_yontemleri SET online_odeme = ?, banka_havalesi = ?, kapida_nakit = ?, kapida_kredi_karti = ?, sanal_pos = ?");
    $islem = $islem->execute(array($_POST['online_odeme'],$_POST['banka_havalesi'],$_POST['kapida_nakit'],$_POST['kapida_kredi_karti'],$_POST['sanal_pos']));


    if($islem){
        echo b();
    }else{
        echo h();
    }

}

$duzenle = $db->query("SELECT * FROM odeme_yontemleri LIMIT 1")->fetch(PDO::FETCH_ASSOC);7
?>

<script type="text/javascript">
	$(function(){
		$('[name="online_odeme"][value="<?php echo $duzenle['online_odeme']; ?>"]').attr('checked','checked');
		$('[name="banka_havalesi"][value="<?php echo $duzenle['banka_havalesi']; ?>"]').attr('checked','checked');
		$('[name="kapida_nakit"][value="<?php echo $duzenle['kapida_nakit']; ?>"]').attr('checked','checked');
		$('[name="kapida_kredi_karti"][value="<?php echo $duzenle['kapida_kredi_karti']; ?>"]').attr('checked','checked');
		$('[name="sanal_pos"][value="<?php echo $duzenle['sanal_pos']; ?>"]').attr('checked','checked');
	});
</script>
	
<div class="breadcrumb-header justify-content-between">
	<div class="my-auto">
		<div class="d-flex">
			<h4 class="content-title mb-0 my-auto">Ödeme Yöntemleri</h4><span class="text-muted mt-1 tx-13 ml-2 mb-0">/ Ekle - Düzenle</span>
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
								<input type="checkbox" class="custom-control-input" name="online_odeme" value="1">
								<span class="custom-control-label">Online Kredi Kartı Ödeme</span>
							</label>
							<label class="custom-control custom-checkbox">
								<input type="checkbox" class="custom-control-input" name="banka_havalesi" value="1">
								<span class="custom-control-label">Banka Havalesi</span>
							</label>
							<label class="custom-control custom-checkbox">
								<input type="checkbox" class="custom-control-input" name="kapida_nakit" value="1">
								<span class="custom-control-label">Kapıda Nakit</span>
							</label>
							<label class="custom-control custom-checkbox">
								<input type="checkbox" class="custom-control-input" name="kapida_kredi_karti" value="1">
								<span class="custom-control-label">Kapıda Kredi Kartı</span>
							</label>
							<label class="custom-control custom-checkbox">
								<input type="radio" class="custom-control-input" name="sanal_pos" value="1">
								<span class="custom-control-label">İyzico Sanal Posu Kullan</span>
							</label>
							<label class="custom-control custom-checkbox">
								<input type="radio" class="custom-control-input" name="sanal_pos" value="2">
								<span class="custom-control-label">Paytr Sanal Posu Kullan</span>
							</label>
							<label class="custom-control custom-checkbox">
								<input type="radio" class="custom-control-input" name="sanal_pos" value="3">
								<span class="custom-control-label">Shopier Sanal Posu Kullan</span>
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
