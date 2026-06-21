<?php
if($_POST){
    
    if(isset($_GET['duzenle_id'])){
        $islem = $db->prepare("UPDATE turun SET baslik = ?, sef = ?, kisa_aciklama = ?, stok_kodu = ?, stok = ?, marka_id = ?, eski_fiyat = ?, fiyat = ?, kdv = ?, kargo_fiyati = ?, aciklama = ?, json = ? WHERE id = ?");
        $islem = $islem->execute(array($_POST['baslik'],'',$_POST['kisa_aciklama'],$_POST['stok_kodu'],$_POST['stok'],$_POST['marka_id'],$_POST['eski_fiyat'],$_POST['fiyat'],$_POST['kdv'],$_POST['kargo_fiyati'],$_POST['aciklama'],$_POST['json_adi'],$_GET['duzenle_id']));


        $id = $_GET['duzenle_id'];
        
        $delete = $db->exec("DELETE FROM turun_kategori WHERE urun_id = '{$id}' ");
        $delete = $db->exec("DELETE FROM turun_img WHERE urun_id = '{$id}' ");
        $query = $db->query("SELECT * FROM turun_secenek WHERE urun_id = '{$id}'", PDO::FETCH_ASSOC);
		if($query->rowCount()){
			foreach( $query as $row ){
				$delete = $db->exec("DELETE FROM turun_secenek_alt WHERE urun_secenek_id = '{$row['id']}'");
				$delete = $db->exec("DELETE FROM turun_secenek WHERE id = '{$row['id']}'");
			}
		}


    }else{
        $islem = $db->prepare("INSERT INTO turun SET baslik = ?, sef = ?, kisa_aciklama = ?, stok_kodu = ?, stok = ?, marka_id = ?, eski_fiyat = ?, fiyat = ?, kdv = ?, kargo_fiyati = ?, aciklama = ?, json = ?");
        $islem = $islem->execute(array($_POST['baslik'],'',$_POST['kisa_aciklama'],$_POST['stok_kodu'],$_POST['stok'],$_POST['marka_id'],$_POST['eski_fiyat'],$_POST['fiyat'],$_POST['kdv'],$_POST['kargo_fiyati'],$_POST['aciklama'],$_POST['json_adi']));
        $id = $db->lastInsertId();
    }



    if(isset($_POST['img'])){
    	foreach ($_POST['img'] as $img) {
    		$islem = $db->prepare("INSERT INTO turun_img SET urun_id = ?, img = ?");
        	$islem = $islem->execute(array($id,$img));
    	}
    }

    $i = 0;
    if(isset($_POST['secenek_adi'])){
    	foreach ($_POST['secenek_adi'] as $s) {
    		$islem = $db->prepare("INSERT INTO turun_secenek SET urun_id = ?, baslik = ?");
        	$islem = $islem->execute(array($id,$s));
        	$secenek_id = $db->lastInsertId();

        	$ii = 0;
        	if(isset($_POST['alt_secenek_adi'.$i])){
        		foreach ($_POST['alt_secenek_adi'.$i] as $as) {
        			$islem = $db->prepare("INSERT INTO turun_secenek_alt SET urun_secenek_id = ?, baslik = ?, stok = ?, fiyat = ?");
        			$islem = $islem->execute(array($secenek_id,$as,$_POST['alt_secenek_stok'.$i][$ii],$_POST['alt_secenek_fiyat'.$i][$ii]));
        			$ii++;
        		}
        	}

        	$i++;
    	}
    }


    if($_POST['kategori']){
		foreach ($_POST['kategori'] as $k) {
			$islem = $db->prepare("INSERT INTO turun_kategori SET urun_id = ?, kategori_id = ?");
        	$islem = $islem->execute(array($id,$k));
		}
	}

    if($islem){
    	$sef = sef($_POST['baslik']).'-'.$id;
    	$islem = $db->prepare("UPDATE turun SET sef = ? WHERE id = ?");
    	$islem = $islem->execute(array($sef,$id));
        echo b();
    }else{
        echo h();
    }

}

$json_adi = uniqid().'.json';
if(isset($_GET['duzenle_id'])){
    $duzenle = $db->query("SELECT * FROM turun WHERE id = '{$_GET['duzenle_id']}' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    $json_adi = $duzenle['json'];
    ?>
		<script type="text/javascript">
			$(function(){
				$('select[name="marka"] option[value="<?php echo $duzenle['marka_id']; ?>"]').attr('selected','select');
				$('select[name="kdv"] option[value="<?php echo $duzenle['kdv']; ?>"]').attr('selected','select');
				<?php
				$cek = $db->query("SELECT * FROM urun_kategori WHERE urun_id = '{$_GET['duzenle_id']}' ", PDO::FETCH_ASSOC);
				if($cek->rowCount()){
					foreach( $cek as $c ){
						?>
							$('input[name="kategori[]"][value="<?php echo $c['kategori_id']; ?>"]').attr('checked','checked');
						<?php
					}
				}
				?>
			});
		</script>
	<?php
}
?>
<div class="breadcrumb-header justify-content-between">
	<div class="my-auto">
		<div class="d-flex">
			<h4 class="content-title mb-0 my-auto">Ürün</h4><span class="text-muted mt-1 tx-13 ml-2 mb-0">/ Ekle - Düzenle</span>
		</div>
	</div>
</div>

<form class="form-horizontal" action="" method="post">
  
      <center>
        <div class="alert alert-danger" role="alert" style="display:table">
        İlk önce tasarımınızı yapın ve <b>Şablonu Oluştur</b> butonuna tıklayın başarı mesajından sonra ürünü ekleyin
        </div>
      </center>
	<div class="row">
		<div id="iframe" style="float:left;width: 100%">
			<iframe src="tasarla/index.php?json_adi=<?php echo $json_adi; ?>&id=<?php echo @$duzenle['id']; ?>"  scrolling="no" frameborder="0px" style="width: 100%;height: 900px;overflow: hidden;"></iframe>
		</div>

		<input type="hidden" name="json_adi" value="<?php echo $json_adi; ?>">

		<div class="col-lg-6 col-xl-6 col-md-12 col-sm-12">
			<div class="card  box-shadow-0">
				<div class="card-body pt-10">
					<div class="form-group">
						<input type="text" class="form-control" name="baslik" placeholder="Ürün Adı" required="" value="<?php echo @$duzenle['baslik']; ?>">
					</div>
					<div class="form-group">
						<textarea class="form-control" name="kisa_aciklama" placeholder="Kısa Açıklama (Description)"><?php echo @$duzenle['kisa_aciklama']; ?></textarea>
					</div>
					<div class="form-group">
						<input type="text" class="form-control" name="stok_kodu" placeholder="Stok Kodu" value="<?php echo @$duzenle['stok_kodu']; ?>" required>
					</div>
					<div class="form-group">
						<input type="number" class="form-control" name="stok" placeholder="Stok Sayısı (Varyansız Ürün İçin)" value="<?php echo @$duzenle['stok']; ?>" required>
					</div>
					<div class="form-group">
						<select class="form-control select2" name="marka_id">
							<option value="0">Markasız Ürün</option>
							<?php
								$query = $db->query("SELECT * FROM marka ORDER BY id DESC", PDO::FETCH_ASSOC);
			                    if($query->rowCount()){
			                        foreach( $query as $row ){
										echo '<option value="'.$row['id'].'">'.$row['baslik'].'</option>';
									}
								}
							?>
						</select>
					</div>
				</div>
			</div>
		</div>
		<div class="col-lg-6 col-xl-6 col-md-12 col-sm-12">
			<div class="card  box-shadow-0">
				<div class="card-body pt-10">
					<label>Kategori Seçiniz</label>
					<div style="float: left;width: 100%;max-height: 120px;border: 1px solid #ddd;padding: 10px;overflow-x: scroll;margin-bottom: 20px;">
						<?php
							$cek = $db->query("SELECT * FROM tkategori", PDO::FETCH_ASSOC);
							if($cek->rowCount()){
								foreach( $cek as $c ){
									echo '<div class="form-group mb-0 justify-content-end">
											<div class="checkbox">
												<div class="custom-checkbox custom-control">
													<input type="checkbox" name="kategori[]" class="custom-control-input"  value="'.$c['id'].'" id="kategori'.$c['id'].'">
													<label for="kategori'.$c['id'].'" class="custom-control-label mt-1">#' .$c['id'].' '.$c['baslik'].'</label>
												</div>
											</div>
										</div>';
					  			}
					  		}
				  		?>
						
					</div>
					<div class="form-group">
						<input type="text" class="form-control" name="eski_fiyat" placeholder="İndirimsiz Fiyat" value="<?php echo @$duzenle['eski_fiyat']; ?>" >
					</div>
					<div class="form-group">
						<input type="text" class="form-control" name="fiyat" placeholder="Satış Fiyatı" value="<?php echo @$duzenle['fiyat']; ?>" required>
					</div>
					<div class="form-group">
						<select class="form-control" name="kdv" required="">
							<option value="">Kdv Seçimi Yapınız</option>
							<option value="1">%1</option>
							<option value="8">%8</option>
							<option value="18">%18</option>
						</select>
					</div>
					<div class="form-group">
						<input type="text" class="form-control" name="kargo_fiyati" placeholder="Kargo Fiyatı" required value="<?php echo @$duzenle['kargo_fiyati']; ?>">
					</div>
				</div>
			</div>
		</div>
	</div>
	<div class="row">
		<div class="col-lg-12 col-xl-12 col-md-12 col-sm-12">
			<div class="card  box-shadow-0">
				<div class="card-header">
					<h4 class="card-title">Ürün Açıklaması</h4>
				</div>
				<div class="card-body pt-10">
					<div class="row">
						<div class="col-lg-12 col-md-12">
							<div class="form-group row">
								<div class="col-md-12">
									<textarea class="content" name="aciklama"><?php echo @$duzenle['aciklama']; ?></textarea>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
	<div class="row">
		<div class="col-lg-12 col-xl-12 col-md-12 col-sm-12">
			<div class="card">
				<div class="card-header">
					<h4 class="card-title">Varyantlar</h4>
				</div>
				<div class="card-body">
					<div class="row">
						<div class="col-lg-12 col-md-12">
							<div class="form-group">
								<div class="row" id="secenekler">
								<?php
									$i = 0;
									if(isset($_GET['duzenle_id'])){
										$cek = $db->query("SELECT * FROM turun_secenek WHERE urun_id = '{$_GET['duzenle_id']}' ", PDO::FETCH_ASSOC);
										if($cek->rowCount()){
											foreach( $cek as $c ){
												echo '<div class="col-md-12" data-secenek="'.$i.'">
														<div class="row form-group">
															<div class="col-md-8"><input type="text" class="form-control" name="secenek_adi[]" value="'.$c['baslik'].'" placeholder="Varyant Adı"></div>
															<div class="col-md-1"><button type="button" data-secenek-sil="'.$i.'" class="btn btn-danger">Sil</button></div>
															<div class="col-md-3"><button type="button" data-alt-secenek-ekle="'.$i.'" class="btn btn-success">Alt Seçenek Ekle</button></div>
														</div>
														<div class="row form-group alt_senecekler" data-alt-secenek="'.$i.'">
														';

														$ii = 0;
														$cek1 = $db->query("SELECT * FROM turun_secenek_alt WHERE urun_secenek_id = '{$c['id']}' ", PDO::FETCH_ASSOC);
														if($cek1->rowCount()){
															foreach( $cek1 as $c1 ){
																echo '<div class="col-md-12" data-alt-secenek-dis="'.$ii.'">
																		<div class="row form-group">
																			<div class="col-md-3">
																				<input type="text" class="form-control" placeholder="Alt Seçenek Adı" value="'.$c1['baslik'].'" name="alt_secenek_adi'.$i.'[]">
																			</div>
																			<div class="col-md-3">
																				<input type="text" class="form-control" placeholder="Stok Sayısı" value="'.$c1['stok'].'" name="alt_secenek_stok'.$i.'[]">
																			</div>
																			<div class="col-md-3">
																				<input type="text" class="form-control" placeholder="+Fiyat" value="'.$c1['fiyat'].'" name="alt_secenek_fiyat'.$i.'[]">
																			</div>
																			<div class="col-md-3">
																				<button type="button" data-alt-secenek-sil="'.$ii.'" class="btn btn-danger">Sil</button>
																			</div>
																		</div>
																	</div>';
																$ii++;
															}
														}


												echo 	'</div>
													</div>';
									            $i++;
											}
											?>
											<script type="text/javascript">
												$(document).ready(function(){
													$('#ekle').hide(1000);
												});
											</script>
											<?php
										}
									}
								?>
								</div>
								<button type="button" id="ekle" class="btn btn-success">Varyant Ekle</button>
								<script type="text/javascript">
									$(document).ready(function(){

										$('#ekle').click(function(){
											var say = $('[data-secenek]').length;
											$('#secenekler').append('\
												<div class="col-md-12" data-secenek="'+say+'">\
													<div class="row form-group">\
														<div class="col-md-8"><input type="text" class="form-control" name="secenek_adi[]" placeholder="Seçenek Adı"></div>\
														<div class="col-md-1"><button type="button" data-secenek-sil="'+say+'" class="btn btn-danger">Sil</button></div>\
														<div class="col-md-3"><button type="button" data-alt-secenek-ekle="'+say+'" class="btn btn-success">Alt Seçenek Ekle</button></div>\
													</div>\
													<div class="row form-group alt_senecekler" data-alt-secenek="'+say+'"></div>\
												</div>\
											');
											$('#ekle').hide(1000);
										});

										$(document).on('click','[data-secenek-sil]', function(){
											$('[data-secenek="'+$(this).attr('data-secenek-sil')+'"]').remove();
											$('#ekle').fadeIn(1000);
										});

										$(document).on('click','[data-alt-secenek-ekle]', function(){
											var say = $('[data-alt-secenek-dis]').length;
											$('[data-alt-secenek="'+$(this).attr('data-alt-secenek-ekle')+'"]').append('\
												<div class="col-md-12" data-alt-secenek-dis="'+say+'">\
													<div class="row form-group">\
														<div class="col-md-3">\
															<input type="text" class="form-control" placeholder="Alt Seçenek Adı" name="alt_secenek_adi'+$(this).attr('data-alt-secenek-ekle')+'[]">\
														</div>\
														<div class="col-md-3">\
															<input type="text" class="form-control" placeholder="Stok Sayısı" name="alt_secenek_stok'+$(this).attr('data-alt-secenek-ekle')+'[]">\
														</div>\
														<div class="col-md-3">\
															<input type="text" class="form-control" placeholder="+Fiyat" name="alt_secenek_fiyat'+$(this).attr('data-alt-secenek-ekle')+'[]">\
														</div>\
														<div class="col-md-3">\
															<button type="button" data-alt-secenek-sil="'+say+'" class="btn btn-danger">Sil</button>\
														</div>\
													</div>\
												</div>\
											');
										});

										$(document).on('click','[data-alt-secenek-sil]', function(){
											$('[data-alt-secenek-dis="'+$(this).attr('data-alt-secenek-sil')+'"]').remove();
										});

									});
								</script>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
	<div class="row">
		<div class="col-lg-12 col-xl-12 col-md-12 col-sm-12">
			<div class="card">
				<div class="card-header">
					<h4 class="card-title">Ürün Fotoğrafları</h4>
				</div>
				<div class="card-body">
					<div class="row">
						<div class="col-lg-12 col-md-12">
							<div class="form-group row" id="resimler">
								<?php
									$i = 0;
									if(isset($_GET['duzenle_id'])){
										$cek = $db->query("SELECT * FROM turun_img WHERE urun_id = '{$_GET['duzenle_id']}' ", PDO::FETCH_ASSOC);
										if($cek->rowCount()){
											foreach( $cek as $c ){
												echo '<div class="col-md-3" data-resim-dis-id="'.$i.'">
									                    <div class="uploaddis pasif" style="float:left;">
									        			  <div class="yuklendi">
									        				  <img src="../upload/'.$c['img'].'">
									        				  <div class="icon" data-resim-sil-id="'.$i.'"><span class="fa fa-trash"></span></div>
									        				  <input type="hidden" name="img[]" value="'.$c['img'].'" required="">
									        			  </div>
									        			</div>
									                </div>';
									            $i++;
											}
										}
									}
								?>
							</div>
							<div class="form-group row">
				                <div class="col-md-4 offset-4">
				                    <div class="uploaddis aktif" data-id="1" style="margin:0 auto;">
				        			  <div class="upload1">
				        				  <span class="metin" style="width: 100%;float: left;">Ürün Resimi Yükle</span>
				        				  <div class="icon"><span class="fa fa-upload" data-id="1"></span></div>
				        			  </div>
				        			</div>
				                </div>
				            </div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
	<div class="row">
		<div class="col-md-12">
			<center><div class="form-group"><button type="submit" class="btn btn-success">Değişiklikleri Kaydet</button></div></center>
		</div>
	</div>
</form>

<div id="queue"></div>

<link href="assets/plugins/wysiwyag/richtext.css" rel="stylesheet" />
<script src="assets/plugins/wysiwyag/jquery.richtext.js"></script>
<script src="assets/js/form-editor.js"></script>
