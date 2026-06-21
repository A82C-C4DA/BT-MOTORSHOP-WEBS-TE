<?php
$_title         =  'Sepetim';
?>
<main id="content" role="main">
    <!-- breadcrumb -->
    <div class="bg-gray-13 bg-md-transparent">
        <div class="container">
            <!-- breadcrumb -->
            <div class="my-md-3">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-3 flex-nowrap flex-xl-wrap overflow-auto overflow-xl-visble">
                        <li class="breadcrumb-item flex-shrink-0 flex-xl-shrink-1"><a href="index.php"><?php echo t('home', $language); ?></a></li>
                        <li class="breadcrumb-item flex-shrink-0 flex-xl-shrink-1 active" aria-current="page"><?php echo t('cart_page', $language); ?></li>
                    </ol>
                </nav>
            </div>
            <!-- End breadcrumb -->
        </div>
    </div>
    <!-- End breadcrumb -->

		<div class="container">
			<div class="row mt-20 mb-20">
				<div class="col-md-8">
					<div class="bg2 border sepet_ust">
						<div class="row p10 bg3 hidden-xs">
							<div class="col-md-5"><?php echo t('product', $language); ?></div>
							<div class="col-md-2"><?php echo t('unit_price', $language); ?></div>
							<div class="col-md-1"><?php echo t('quantity', $language); ?></div>
							<div class="col-md-2"><?php echo t('total', $language); ?></div>
							<div class="col-md-1"><?php echo t('action', $language); ?></div>
						</div>
						<?php
							$urun_toplam = 0;
							$kargo_toplam = 0;
							$kdv_toplam = 0;
							if(@count($_SESSION['sepet']['key']) > 0){

								foreach ($_SESSION['sepet']['key'] as $key) {

								  if(empty($_SESSION['sepet']['img'][$key])){

								  
						          $urunquery = $db->prepare("SELECT * FROM urun where id=:id LIMIT 1");
						          $urun = $urunquery->execute(array(":id"=>$_SESSION['sepet']['urun_id'][$key]));
						          $urun = $urunquery->fetch(PDO::FETCH_ASSOC);
		       
						          $urunimg = $db->prepare("SELECT * FROM urun_img where urun_id=:urun_id LIMIT 1");
						          $uimg = $urunimg->execute(array(":urun_id"=>$_SESSION['sepet']['urun_id'][$key]));
						          $uimg = $urunimg->fetch(PDO::FETCH_ASSOC);

						          $secenek = '';

						         $alt_secenek_fiyat = 0;
				                 if($_SESSION['sepet']['secenek_id'][$key] !=0){
				                  $alt_secenek = $db->prepare("SELECT * FROM urun_secenek_alt where id=:id LIMIT 1");
		                          $as = $alt_secenek->execute(array(":id"=>$_SESSION['sepet']['secenek_id'][$key]));
		                          $as = $alt_secenek->fetch(PDO::FETCH_ASSOC);

		                          $ust_secenek = $db->prepare("SELECT * FROM urun_secenek where id=:id LIMIT 1");
		                          $us = $ust_secenek->execute(array(":id"=>$as['urun_secenek_id']));
		                          $us = $ust_secenek->fetch(PDO::FETCH_ASSOC);

		                          $alt_secenek_fiyat = $as['fiyat'];

		                          $secenek = '<div><span>'.$us['baslik'].':</span> <span>'.$as['baslik'].'</span></div>';
				                }  


				              $urun['fiyat'] = $urun['fiyat'] + $alt_secenek_fiyat;

				              $urun_toplam += $_SESSION['sepet']['adet'][$key] * $urun['fiyat'];
					          $kargo_toplam += $_SESSION['sepet']['adet'][$key] * $urun['kargo_fiyati'];
					          $kdv_toplam += (($_SESSION['sepet']['adet'][$key] * ($urun['fiyat'])) * $urun['kdv']) / 100;
						?>

						
						<div class="row border-t p10">
							<div class="col-md-5 col-xs-6">
								<div class="row border-n">
									<div class="col-md-3 col-xs-4">
										<a href="urun/<?php echo $urun['sef']; ?>"><img src="upload/<?php echo $uimg['img']; ?>" class="img-responsive"></a>
									</div>
									<div class="col-md-9 col-xs-8 pt-5">
										<a href="urun/<?php echo $urun['sef']; ?>" style="color:#666"><?php echo $urun['baslik']; ?><br><?php echo $secenek; ?></a>
									</div>
								</div>
							</div>
							<div class="col-md-2 col-xs-2 pt-20 col-xs-4"><b><?php echo fiyat($urun['fiyat']); ?> TL</b></div>
							<div class="col-md-1 col-xs-2 pt-20 col-xs-4"><b><?php echo $_SESSION['sepet']['adet'][$key]; ?> </b></div>
							<div class="col-md-2 col-xs-1 pt-20 hidden-xs"><b><?php echo fiyat($_SESSION['sepet']['adet'][$key] * ($urun['fiyat'])); ?> TL</b></div>
							<div class="col-md-1 col-xs-2 pt-20 col-xs-4"><i class="fa fa-trash" data-toggle="tooltip" data-sepet-sayfa-sil="<?php echo $key; ?>" data-placement="bottom" title="<?php echo t('remove', $language); ?>"></i></div>
						</div>
						<?php
								}else{
									$urunquery = $db->prepare("SELECT * FROM turun where id=:id LIMIT 1");
						          	$urun = $urunquery->execute(array(":id"=>$_SESSION['sepet']['urun_id'][$key]));
						          	$urun = $urunquery->fetch(PDO::FETCH_ASSOC);

						          	$secenek = '';

							         $alt_secenek_fiyat = 0;
					                 if($_SESSION['sepet']['secenek_id'][$key] !=0){
					                  $alt_secenek = $db->prepare("SELECT * FROM turun_secenek_alt where id=:id LIMIT 1");
			                          $as = $alt_secenek->execute(array(":id"=>$_SESSION['sepet']['secenek_id'][$key]));
			                          $as = $alt_secenek->fetch(PDO::FETCH_ASSOC);

			                          $ust_secenek = $db->prepare("SELECT * FROM turun_secenek where id=:id LIMIT 1");
			                          $us = $ust_secenek->execute(array(":id"=>$as['urun_secenek_id']));
			                          $us = $ust_secenek->fetch(PDO::FETCH_ASSOC);

			                          $alt_secenek_fiyat = $as['fiyat'];

			                          $secenek = '<div><span>'.$us['baslik'].':</span> <span>'.$as['baslik'].'</span></div>';
					                }  


					              $urun['fiyat'] = $urun['fiyat'] + $alt_secenek_fiyat;

					              $urun_toplam += $_SESSION['sepet']['adet'][$key] * $urun['fiyat'];
						          $kargo_toplam += $_SESSION['sepet']['adet'][$key] * $urun['kargo_fiyati'];
						          $kdv_toplam += (($_SESSION['sepet']['adet'][$key] * ($urun['fiyat'])) * $urun['kdv']) / 100;

						          ?>
						          <div class="row border-t p10">
									<div class="col-md-5 col-xs-6">
										<div class="row border-n">
											<div class="col-md-3 col-xs-4">
												<a href="urun-tasarla/<?php echo $urun['sef']; ?>"><img src="<?php echo$_SESSION['sepet']['img'][$key]; ?>" class="img-responsive"></a>
											</div>
											<div class="col-md-9 col-xs-8 pt-5">
												<a href="urun-tasarla/<?php echo $urun['sef']; ?>" style="color:#666"><?php echo $urun['baslik']; ?><br><?php echo $secenek; ?></a>
											</div>
										</div>
									</div>
									<div class="col-md-2 col-xs-2 pt-20 col-xs-4"><b><?php echo fiyat($urun['fiyat']); ?> TL</b></div>
									<div class="col-md-1 col-xs-2 pt-20 col-xs-4"><b><?php echo $_SESSION['sepet']['adet'][$key]; ?> </b></div>
									<div class="col-md-2 col-xs-1 pt-20 hidden-xs"><b><?php echo fiyat($_SESSION['sepet']['adet'][$key] * ($urun['fiyat'])); ?> TL</b></div>
									<div class="col-md-1 col-xs-2 pt-20 col-xs-4"><i class="fa fa-trash" data-toggle="tooltip" data-sepet-sayfa-sil="<?php echo $key; ?>" data-placement="bottom" title="<?php echo t('remove', $language); ?>"></i></div>
								</div>
						          <?php

								}
							 }
							}else{
								echo '<div class="row border-t p10"><div class="col-md-12"><center><h3>Sepetinizde ürün bulunmuyor...</h3></center></div></tr>';
							}

							if($kargo_toplam == 0){
								$kargo_toplam = $cek['kargo_ucreti'];
							}

							if($urun_toplam >= $cek['kargo_bedava_limit'] AND !empty($cek['kargo_bedava_limit'])){
								$kargo_toplam = 0;
							}

							$indirim  = '';
							if( $cek['kac_lira_uzeri_indirim'] !=''){
								if($urun_toplam >= $cek['kac_lira_uzeri_indirim']){
									if($cek['kac_lira_uzeri_indirim_turu'] == 1){
										$urun_toplam = $urun_toplam - (($urun_toplam * $cek['kac_lira_uzeri_indirim_tutari']) / 100);
										$indirim = '<li><span>İndirim</span><span>%'.$cek['kac_lira_uzeri_indirim_tutari'].' İnidirim</span></li>';
									}else{
										$indirim = '<li><span>İndirim</span><span>-'.$cek['kac_lira_uzeri_indirim_tutari'].' TL İnidirim</span></li>';
										$urun_toplam = ($urun_toplam - $cek['kac_lira_uzeri_indirim_tutari']);
									}
								}
							}
						?>
					</div>
					<?php if(@count($_SESSION['sepet']['key']) > 0){ ?>


							<form action="" method="post">
								<div class="row">
									<fieldset class="form-group col-md-8 mt-10" style="margin-bottom: 0px">
										<input type="text" class="form-control" name="kod" required="" placeholder="Kupon Kodu Giriniz">
									</fieldset>
									<fieldset class="form-group col-md-4 mt-10" style="margin-bottom: 0px">
										<button type="submit" class="btn btn-success"><i class="fa fa-thumbs-up"></i> Kupon Kullan</button>
									</fieldset>
								</div>
							</form>

		            <?php
		           			 $kupon_text = 'Geçersiz Kupon';

		                    if($_POST){
		                      $_SESSION['kupon'] = $_POST['kod'];
		                    }

		                    if(isset($_SESSION['kupon'])){

		                      $kupon = $db->prepare("SELECT * FROM kupon where kod=:kod LIMIT 1");
		                      $k = $kupon->execute(array(":kod"=>$_SESSION['kupon']));
		                      $k = $kupon->fetch(PDO::FETCH_ASSOC);

		                      if($k){
		                        if($k['indirim_turu'] == 1){
		                          $urun_toplam = $urun_toplam - (($urun_toplam * $k['tutar']) / 100);
		                          $kupon_text = '%'.$k['tutar'].' İndirim';
		                        }else{
		                          $urun_toplam = $urun_toplam - $k['tutar'];
		                          $kupon_text = '-'.$k['tutar'].' TL İndirim';
		                        }
		                      }


		                    }
		            ?>
					<div class="col-md-12 bg2 border mt-10">
						<div class="row">
							<div class="col-md-12 pt-10 pb-10 bg3"><b><?php echo t('cart_page', $language); ?></b></div>
							<div class="col-md-12 pt-10 pb-10 sepet_ozet">
								<ul>
									<?php 
										if(isset($_SESSION['kupon'])){
					                      echo '<li><span> Kupon İndirimi: </span> <span>'.$kupon_text.'</span></li>';
					                    }
										echo $indirim;
									?>
									<li><span><?php echo t('subtotal', $language); ?></span><span><?php echo fiyat($urun_toplam - $kdv_toplam); ?> TL</span></li>
									<li><span><?php echo t('tax', $language); ?></span><span><?php echo fiyat($kdv_toplam); ?> TL</span></li>
									<li><span><?php echo t('shipping', $language); ?></span><span><?php echo fiyat($kargo_toplam); ?> TL</span></li>
									<li><span><?php echo t('grand_total', $language); ?></span><span><?php echo fiyat($urun_toplam + $kargo_toplam); ?> TL</span></li>
								</ul>
							</div>			
						</div>
					</div>
					<?php } ?>
				</div>

				<?php 
				if(@count($_SESSION['sepet']['key']) > 0){

					if(isset($_SESSION['kullanici']['login'])){
						$query = $db->prepare("SELECT * FROM kullanici where id=:id LIMIT 1");
			            $kullanici = $query->execute(array(":id"=>$_SESSION['kullanici']['id']));
			            $kullanici = $query->fetch(PDO::FETCH_ASSOC);
			        }

				?>
				<div class="col-md-4 bg2 border">
					<div class="row">
						<div class="col-md-12 pt-10 pb-10 bg3"><b>Sipariş Bilgileriniz</b></div>
						<div class="col-md-12 pt-10 pb-10">
							<form action="alisverisi-tamamla" method="post">
								<div class="row">
                  <fieldset class="form-group mt-4 col-md-6">
                     <label><?php echo t('name', $language); ?></label>
                     <input type="text" class="form-control" name="ad" required="" placeholder="<?php echo t('name', $language); ?>" value="<?php if(isset($_SESSION['kullanici']['login'])){ echo $kullanici['ad']; } ?>">
                  </fieldset>
                  <fieldset class="form-group mt-4 col-md-6">
                     <label><?php echo t('surname', $language); ?></label>
                     <input type="text" class="form-control" name="soyad" required="" placeholder="<?php echo t('surname', $language); ?>" value="<?php if(isset($_SESSION['kullanici']['login'])){ echo $kullanici['soyad']; } ?>">
                  </fieldset>
                  <fieldset class="form-group mt-4 col-md-6">
                     <label><?php echo t('phone', $language); ?></label>
                     <input type="text" class="form-control" name="telefon" required="" placeholder="<?php echo t('phone', $language); ?>" value="<?php if(isset($_SESSION['kullanici']['login'])){ echo $kullanici['telefon']; } ?>">
                  </fieldset>
                  <fieldset class="form-group mt-4 col-md-6">
                     <label><?php echo t('email_address', $language); ?></label>
                     <input type="email" class="form-control" name="email" required="" placeholder="<?php echo t('email_address', $language); ?>" value="<?php if(isset($_SESSION['kullanici']['login'])){ echo $kullanici['email']; } ?>">
                  </fieldset>
                  <fieldset class="form-group mt-4 col-md-12">
                     <label>Fatura İçin Tc Kimik No</label>
                     <input type="text" class="form-control" name="tc" placeholder="Tc Kimlik No" value="<?php if(isset($_SESSION['kullanici']['login'])){ echo $kullanici['tc']; } ?>">
                  </fieldset>
                  <?php if(!isset($_SESSION['kullanici']['login'])){ ?>
                  <fieldset class="form-group col-md-12">
                     <label>Şifreniz</label>
                     <input type="password" class="form-control" name="sifre" required="" placeholder="********">
                  </fieldset>
                  <?php } ?>
                  <fieldset class="form-group col-md-12">
                     <label>Sipariş Adresiniz</label>
                     <textarea class="form-control" name="adres" required="" placeholder="Sipariş Adresiniz" rows="3"><?php if(isset($_SESSION['kullanici']['login'])){ echo $kullanici['adres']; } ?></textarea>
                  </fieldset>
                  <fieldset class="form-group col-md-12">
                    <select name="odeme_yontemi" required="" class="form-control">
                      <option value="">Ödeme Yöntemi Seçiniz</option>
                      <?php
                      	$kapida_nakit = '';
                      	if($cek['kapida_nakit_odeme_kargo_ucreti'] > 0){
                      		$kapida_nakit = '(+'.$cek['kapida_nakit_odeme_kargo_ucreti'].' TL)';
                      	}


                      	$kapida_kredi = '';
                      	if($cek['kapida_kredi_karti_odeme_kargo_ucreti'] > 0){
                      		$kapida_kredi = '(+'.$cek['kapida_kredi_karti_odeme_kargo_ucreti'].' TL)';
                      	}
                      	$yontem = $db->query("SELECT * FROM odeme_yontemleri LIMIT 1")->fetch(PDO::FETCH_ASSOC);
                      	if($yontem['online_odeme'] == 1){ echo '<option value="1">Online Kredi Kartı</option>'; }
                      	if($yontem['kapida_kredi_karti'] == 1){ echo '<option value="2">Kapıda Kredi Kartı '.$kapida_kredi.'</option>'; }
                      	if($yontem['kapida_nakit'] == 1){ echo ' <option value="3">Kapıda Nakit '.$kapida_nakit.'</option>'; }
                      	if($yontem['banka_havalesi'] == 1){ echo '<option value="4">Banka Havalesi </option>'; }
                      ?>
                    </select>
                  </fieldset>
                  <fieldset class="form-group col-md-12">
                     <button class="btn icon-btn btn-success" style="width: 100%">Siparişi Oluştur</button>
                  </fieldset>
                 </div>
                </form>
						</div>			
					</div>
				</div>
				<?php } ?>

				
			</div>
		</div>
</main>
<?php include 'inc/sabit-css.php'; ?>
<style type="text/css">
.sepet_ozet ul li {float: left;}
</style>