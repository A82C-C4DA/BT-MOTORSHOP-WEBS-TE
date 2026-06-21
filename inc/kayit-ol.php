<?php
if(isset($_SESSION['kullanici']['login'])){
    die('<meta http-equiv="refresh" content="0;URL=index.php">');
}
$_title         =  'Kayıt Ol';
?>
<main id="content" role="main">
<div class="container">
	<div class="row mt-20 mb-20">
		<div class="col-md-7 kayit_sol">
			<div class="bg3 border p20">
				<h2><?php echo t('already_member', $language); ?></h2>
				<p><?php echo t('click_to_login', $language); ?></p>
				<div class="row mt-20">
					<div class="col-md-4">
						<div class="bg2 p20 border">
							<i class="fa fa-user-shield"></i>
							<div><?php echo t('secure_payment', $language); ?> <br><?php echo t('enjoyable_shopping', $language); ?></div>
						</div>
					</div>
					<div class="col-md-4">
						<div class="bg2 p20 border">
							<i class="fa fa-home"></i>
							<div><?php echo t('free_easy_register', $language); ?></div>
						</div>
					</div>
					<div class="col-md-4">
						<div class="bg2 p20 border">
							<i class="fa fa-skiing"></i>
							<div><?php echo t('fast_secure_shopping', $language); ?></div>
						</div>
					</div>
				</div>
				<div class="row mt-20">
					<div class="col-md-6 col-md-offset-3 mt-20 mb-20"><a href="giris-yap" class="btn btn-info" style="width: 100%;padding: 15px;font-size: 25px"><?php echo t('login', $language); ?></a></div>
				</div>
			</div>
		</div>
		<div class="col-md-5">
			<div class="bg2 border p20">
				<?php
					if($_POST){
						if(!empty($_POST['ad']) AND !empty($_POST['soyad']) AND !empty($_POST['telefon']) AND !empty($_POST['email']) AND !empty($_POST['sifre'])){
							$query = $db->prepare("SELECT * FROM kullanici where email=:email LIMIT 1");
			                $kayit = $query->execute(array(":email"=>$_POST['email']));
			                $kayit = $query->fetch(PDO::FETCH_ASSOC);

			                if($kayit){
			                  echo '<div class="hata">'.t('email_already_used', $language).'</div>';
			                }else{
			                  $islem = $db->prepare("INSERT INTO kullanici SET ad = ?, soyad = ?, telefon = ?, email = ?, sifre = ?, kayit_tarihi = ?, aktif = ?, tc = ?, adres = ?");
			                  $islem = $islem->execute(array($_POST['ad'],$_POST['soyad'],$_POST['telefon'],$_POST['email'],$_POST['sifre'],$time,1,'',''));
			                  if($islem){
			                      echo '<div class="basari">'.t('registration_success', $language).'.<meta http-equiv="refresh" content="2;URL=index.php"></div>';

			                      if($sms_izin['kullanici_kayit'] == 1){
										$mesaj = 'Merhaba '.$_POST['ad'].' başarı ile kayıt oldunuz.';
										sms($mesaj,$_POST['telefon']);
									}

			                      $_SESSION['kullanici']['login'] = 1;
			                      $_SESSION['kullanici']['id'] = $db->lastInsertId();
			                  }else{
			                      echo '<div class="hata">İşlem başarısız.</div>';
			                  }
			                }
						}else{
							echo '<div class="hata">Lütfen tüm alanları doldurun.</div>';
						}
					}
				?>
				
				<h2><?php echo t('register', $language); ?></h2>
				<form action="" method="post">
					<div class="row mt-20">
						<div class="col-md-6">
							<input type="text" name="ad" class="form-control" placeholder="<?php echo t('name', $language); ?>" required="">
						</div>
						<div class="col-md-6">
							<input type="text" name="soyad" class="form-control" placeholder="<?php echo t('surname', $language); ?>" required="">
						</div>
					</div>
					<div class="row mt-20">
						<div class="col-md-12">
							<div><?php echo t('phone', $language); ?></div>
							<input type="text" name="telefon" class="form-control" placeholder="<?php echo t('phone', $language); ?>" required="">
						</div>
					</div>
					<div class="row mt-20">
						<div class="col-md-12">
							<div><?php echo t('email_address', $language); ?></div>
							<input type="email" name="email" class="form-control" placeholder="<?php echo t('email_address', $language); ?>" required="">
						</div>
					</div>
					<div class="row mt-20">
						<div class="col-md-12">
							<div><?php echo t('password', $language); ?></div>
							<input type="password" name="sifre" class="form-control" placeholder="<?php echo t('password', $language); ?>" required="">
						</div>
					</div>
					<div class=" mt-20">
						<div class="checkbox">
				          <label>
				            <input type="checkbox" value="1" required="">
				            <a href="sayfa/uyelik-sozlesmesi" target="_blank" style="color:green">Üyelik Sözleşmesi</a> okudum ve kabul ediyorum.
				          </label>
				        </div>
			       	</div>
			       	<div class=" mt-10">
						<div class="checkbox">
				          <label>
				            <input type="checkbox" value="1" required="">
				            <a href="sayfa/aydinlatma-ve-riza-metni" target="_blank">Aydınlatma ve Rıza Metni</a> kapsamında elektronik ileti almak istiyorum.
				          </label>
				        </div>
			       	</div>
			       	<div class=" mt-10">
						<div class="col-md-12">Oturum açarak kişisel verileriniz <a href="sayfa/aydinlatma-ve-riza-metni" target="_blank">Aydınlatma ve Rıza Metni</a>, kapsamında işlenmektedir. Üye Ol butonuna basarak <a href="sayfa/gizlilik-politikasi" target="_blank">Gizlilik ve Çerez Politikası</a>’nı okuduğunuzu ve kabul ettiğinizi onaylıyorsunuz.</div>
			       	</div>
			       	<div class="row mt-10">
						<div class="col-md-12"><button type="submit" class="btn btn-success" style="width: 100%;font-size: 20px"><?php echo t('register', $language); ?></button></div>
			       	</div>
				</form>
			</div>
		</div>
	</div>
</div>
</main>
<?php include 'inc/sabit-css.php'; ?>