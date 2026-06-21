<?php

if($_POST){
	
    $islem = $db->prepare("UPDATE ayar SET title = ?, description = ?, logo = ?, fav = ?, site_ust_img = ?, telefon = ?, email = ?, siparis_mail = ?, adres = ?, google_harita_kodu = ?, analistik_kodu = ?, facebook = ?, twitter = ?, youtube = ?, whatsapp = ?, instagram = ?, ust_img_link = ?, kargo_bedava_limit = ?, kargo_ucreti = ?, kac_lira_uzeri_indirim = ?, kac_lira_uzeri_indirim_tutari = ?, kac_lira_uzeri_indirim_turu = ?, kapida_nakit_odeme_kargo_ucreti = ?, kapida_kredi_karti_odeme_kargo_ucreti = ?, tasarla = ?");
    $islem = $islem->execute(array($_POST['title'], $_POST['description'], $_POST['img1'], $_POST['img2'], $_POST['img3'], $_POST['telefon'], $_POST['email'], $_POST['siparis_mail'], $_POST['adres'], $_POST['google_harita_kodu'], $_POST['analistik_kodu'], $_POST['facebook'], $_POST['twitter'], $_POST['youtube'], $_POST['whatsapp'],$_POST['instagram'],$_POST['ust_img_link'],$_POST['kargo_bedava_limit'], $_POST['kargo_ucreti'], $_POST['kac_lira_uzeri_indirim'], $_POST['kac_lira_uzeri_indirim_tutari'], $_POST['kac_lira_uzeri_indirim_turu'], $_POST['kapida_nakit_odeme_kargo_ucreti'], $_POST['kapida_kredi_karti_odeme_kargo_ucreti'],$_POST['tasarla']));


    if($islem){
        echo b();
    }else{
        echo h();
    }

}

$duzenle = $db->query("SELECT * FROM ayar LIMIT 1")->fetch(PDO::FETCH_ASSOC);
?>
<script type="text/javascript">
	$(function(){
		<?php
	     if($duzenle['logo'] !='' AND is_file('../upload/'.$duzenle['logo'])){
	        ?>
	          $('.uploaddis[data-id="1"] .yuklendi img').attr('src','../upload/<?php echo $duzenle['logo']; ?>');
	          $('.uploaddis[data-id="1"] input').val('<?php echo $duzenle['logo']; ?>');
	          $('.uploaddis[data-id="1"]').removeClass('aktif');
	          $('.uploaddis[data-id="1"]').addClass('pasif');
	        <?php
	      }
	      if($duzenle['fav'] !='' AND is_file('../upload/'.$duzenle['fav'])){
	        ?>
	          $('.uploaddis[data-id="2"] .yuklendi img').attr('src','../upload/<?php echo $duzenle['fav']; ?>');
	          $('.uploaddis[data-id="2"] input').val('<?php echo $duzenle['fav']; ?>');
	          $('.uploaddis[data-id="2"]').removeClass('aktif');
	          $('.uploaddis[data-id="2"]').addClass('pasif');
	        <?php
	      }
	      if($duzenle['site_ust_img'] !='' AND is_file('../upload/'.$duzenle['site_ust_img'])){
	        ?>
	          $('.uploaddis[data-id="3"] .yuklendi img').attr('src','../upload/<?php echo $duzenle['site_ust_img']; ?>');
	          $('.uploaddis[data-id="3"] input').val('<?php echo $duzenle['site_ust_img']; ?>');
	          $('.uploaddis[data-id="3"]').removeClass('aktif');
	          $('.uploaddis[data-id="3"]').addClass('pasif');
	        <?php
	      }
	    ?>
	    $('[name="kac_lira_uzeri_indirim_turu"] option[value="<?php echo $duzenle['kac_lira_uzeri_indirim_turu']; ?>"]').attr('selected','selected');
	    $('[name="tasarla"] option[value="<?php echo $duzenle['tasarla']; ?>"]').attr('selected','selected');
	});
</script>


<div class="row">
	<div class="col-md-3"></div>
	<div class="col-md-6">

		<div class="breadcrumb-header justify-content-between">
			<div class="my-auto">
				<div class="d-flex">
					<h4 class="content-title mb-0 my-auto">Ayar</h4><span class="text-muted mt-1 tx-13 ml-2 mb-0">/ Ekle - Düzenle</span>
				</div>
			</div>
		</div>

		<div class="card">
			<div class="card-header pb-0">
				<div class="d-flex justify-content-between">
					<h4 class="card-title mg-b-0">Eklenen Veriler</h4>
				</div>
			</div>
			<div class="card-body">
				<form action="" method="post">
					<div class="form-group row">
						<div class="col-md-4">
							<div class="uploaddis aktif" data-id="1" style="float:left;">
		        			  <div class="yuklendi">
		        				  <img src="">
		        				  <div class="icon" data-id="1"><span class="fa fa-trash"></span></div>
		        				  <input type="hidden" name="img1" value="" required="">
		        			  </div>
		        			  <div class="upload">
		        				  <span class="metin" style="width: 100%;float: left;">Logo </span>
		        				  <div class="icon"><span class="fa fa-upload" data-id="1"></span></div>
		        			  </div>
		        			</div>
		        		</div>
		        		<div class="col-md-4">
							<div class="uploaddis aktif" data-id="2" style="float:left;">
		        			  <div class="yuklendi">
		        				  <img src="">
		        				  <div class="icon" data-id="2"><span class="fa fa-trash"></span></div>
		        				  <input type="hidden" name="img2" value="" required="">
		        			  </div>
		        			  <div class="upload">
		        				  <span class="metin" style="width: 100%;float: left;">Fav İco </span>
		        				  <div class="icon"><span class="fa fa-upload" data-id="2"></span></div>
		        			  </div>
		        			</div>
		        		</div>
		        		<div class="col-md-4">
							<div class="uploaddis aktif" data-id="3" style="float:left;">
		        			  <div class="yuklendi">
		        				  <img src="">
		        				  <div class="icon" data-id="3"><span class="fa fa-trash"></span></div>
		        				  <input type="hidden" name="img3" value="" required="">
		        			  </div>
		        			  <div class="upload">
		        				  <span class="metin" style="width: 100%;float: left;">Site Üst Görseli </span>
		        				  <div class="icon"><span class="fa fa-upload" data-id="3"></span></div>
		        			  </div>
		        			</div>
		        		</div>
					</div>
					<div id="queue"></div>
					<div class="form-group row">
						<label class="col-md-3 form-label">Title</label>
						<div class="col-md-9">
							<input type="text" class="form-control" name="title" value="<?php echo @$duzenle['title']; ?>" required="">
						</div>
					</div>

					<div class="form-group row">
						<label class="col-md-3 form-label">Description</label>
						<div class="col-md-9">
							<textarea class="form-control" name="description"><?php echo @$duzenle['description']; ?></textarea>
						</div>
					</div>

					<div class="form-group row">
						<label class="col-md-3 form-label">Telefon</label>
						<div class="col-md-9">
							<input type="text" class="form-control" name="telefon" value="<?php echo @$duzenle['telefon']; ?>" required="">
						</div>
					</div>


					<div class="form-group row">
						<label class="col-md-3 form-label">Site Üst Resim Link</label>
						<div class="col-md-9">
							<input type="text" class="form-control" name="ust_img_link" value="<?php echo @$duzenle['ust_img_link']; ?>" required="">
						</div>
					</div>

					<div class="form-group row">
						<label class="col-md-3 form-label">Email</label>
						<div class="col-md-9">
							<input type="email" class="form-control" name="email" value="<?php echo @$duzenle['email']; ?>" required="">
						</div>
					</div>

					<div class="form-group row">
						<label class="col-md-3 form-label">Sipariş Email</label>
						<div class="col-md-9">
							<input type="text" class="form-control" name="siparis_mail" value="<?php echo @$duzenle['siparis_mail']; ?>" required="">
						</div>
					</div>

					<div class="form-group row">
						<label class="col-md-3 form-label">Adres</label>
						<div class="col-md-9">
							<textarea class="form-control" name="adres"><?php echo @$duzenle['adres']; ?></textarea>
						</div>
					</div>

					<div class="form-group row">
						<label class="col-md-3 form-label">Google Harita Kodu</label>
						<div class="col-md-9">
							<textarea class="form-control" name="google_harita_kodu"><?php echo @$duzenle['google_harita_kodu']; ?></textarea>
						</div>
					</div>

					<div class="form-group row">
						<label class="col-md-3 form-label">Analistik Kodu</label>
						<div class="col-md-9">
							<textarea class="form-control" name="analistik_kodu"><?php echo @$duzenle['analistik_kodu']; ?></textarea>
						</div>
					</div>

					<div class="form-group row">
						<label class="col-md-3 form-label">Facebook</label>
						<div class="col-md-9">
							<input type="text" class="form-control" name="facebook" value="<?php echo @$duzenle['facebook']; ?>" required="">
						</div>
					</div>

					<div class="form-group row">
						<label class="col-md-3 form-label">Twitter</label>
						<div class="col-md-9">
							<input type="text" class="form-control" name="twitter" value="<?php echo @$duzenle['twitter']; ?>" required="">
						</div>
					</div>

					<div class="form-group row">
						<label class="col-md-3 form-label">Youtube</label>
						<div class="col-md-9">
							<input type="text" class="form-control" name="youtube" value="<?php echo @$duzenle['youtube']; ?>" required="">
						</div>
					</div>

					<div class="form-group row">
						<label class="col-md-3 form-label">Instagram</label>
						<div class="col-md-9">
							<input type="text" class="form-control" name="instagram" value="<?php echo @$duzenle['instagram']; ?>" required="">
						</div>
					</div>

					<div class="form-group row">
						<label class="col-md-3 form-label">Whatsapp</label>
						<div class="col-md-9">
							<input type="text" class="form-control" name="whatsapp" value="<?php echo @$duzenle['whatsapp']; ?>" required="">
						</div>
					</div>


					<div class="form-group row">
						<label class="col-md-3 form-label">Kargo Kaç Lira ve Üzeri Bedava Olacak</label>
						<div class="col-md-9">
							<input type="text" class="form-control" name="kargo_bedava_limit" value="<?php echo @$duzenle['kargo_bedava_limit']; ?>">
						</div>
					</div>

					<div class="form-group row">
						<label class="col-md-3 form-label">Kargo Ücreti Ne Kadar</label>
						<div class="col-md-9">
							<input type="text" class="form-control" name="kargo_ucreti" value="<?php echo @$duzenle['kargo_ucreti']; ?>">
						</div>
					</div>



					<div class="form-group row">
						<label class="col-md-3 form-label">Kaç Lira Üzeri İndirim Olacak</label>
						<div class="col-md-9">
							<input type="text" class="form-control" name="kac_lira_uzeri_indirim" value="<?php echo @$duzenle['kac_lira_uzeri_indirim']; ?>">
						</div>
					</div>

					<div class="form-group row">
						<label class="col-md-3 form-label">Kaç Lira Üzeri İndirim Tutarı</label>
						<div class="col-md-9">
							<input type="text" class="form-control" name="kac_lira_uzeri_indirim_tutari" value="<?php echo @$duzenle['kac_lira_uzeri_indirim_tutari']; ?>">
						</div>
					</div>

					<div class="form-group row">
						<label class="col-md-3 form-label">Kaç Lira Üzeri İndirim Türü</label>
						<div class="col-md-9">
							<select name="kac_lira_uzeri_indirim_turu" class="form-control">
								<option value="1">%</option>
								<option value="2">-</option>
							</select>
						</div>
					</div>

					<div class="form-group row">
						<label class="col-md-3 form-label">Ürün Tasarla</label>
						<div class="col-md-9">
							<select name="tasarla" class="form-control">
								<option value="1">Aktif</option>
								<option value="2">Pasif</option>
							</select>
						</div>
					</div>


					<div class="form-group row">
						<label class="col-md-3 form-label">Kapıda Nakit Ödeme Kargo Ücreti</label>
						<div class="col-md-9">
							<input type="text" class="form-control" name="kapida_nakit_odeme_kargo_ucreti" value="<?php echo @$duzenle['kapida_nakit_odeme_kargo_ucreti']; ?>">
						</div>
					</div>


					<div class="form-group row">
						<label class="col-md-3 form-label">Kapıda Kredi Kartı Ödeme Kargo Ücreti</label>
						<div class="col-md-9">
							<input type="text" class="form-control" name="kapida_kredi_karti_odeme_kargo_ucreti" value="<?php echo @$duzenle['kapida_kredi_karti_odeme_kargo_ucreti']; ?>">
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



