<?php

if(isset($_SESSION['kullanici']['login'])){

    die('<meta http-equiv="refresh" content="0;URL=index.php">');

}

$_title         =  'Giriş Yap';

?>
<main id="content" role="main">
<div class="container">

	<div class="row mt-20 mb-20">

		<div class="col-md-7 kayit_sol">

			<div class="bg3 border p20">

				<h2><?php echo t('not_member', $language); ?></h2>

				<p><?php echo t('dont_miss_opportunities', $language); ?></p>

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

					<div class="col-md-6 col-md-offset-3 mt-20 mb-20"><a href="kayit-ol" class="btn btn-warning" style="width: 100%;padding: 15px;font-size: 25px"><?php echo t('register', $language); ?></a></div>

				</div>

			</div>

		</div>

		<div class="col-md-5">

			<div class="bg2 border p20">

				<?php

					if($_POST){

						if(!empty($_POST['email']) AND !empty($_POST['sifre'])){

							$query = $db->prepare("SELECT * FROM kullanici where email=:email AND sifre=:sifre AND aktif=:aktif LIMIT 1");

			                $giris = $query->execute(array(":email"=>$_POST['email'],":sifre"=>$_POST['sifre'],":aktif"=>1));

			                $giris = $query->fetch(PDO::FETCH_ASSOC);



			                if($giris){

			                    echo '<div class="basari">Başarı ile giriş yaptınız.Yönlendiriliyorsunuz.<meta http-equiv="refresh" content="2;URL=index.php"></div>';

			                    $_SESSION['kullanici']['login'] = 1;

			                    $_SESSION['kullanici']['id'] = $giris['id'];

			                }else{

			                    echo '<div class="hata">Giriş bilgileriniz yanlış veya üyeliğiniz durdurulmuş olabilir.</div>';

			                }

						}

					}

				?>

				<h2><?php echo t('login_page', $language); ?></h2>

				<form action="" method="post">

					<div class="row mt-20">

						<div class="col-md-12">

							<div>E-Posta Adresi</div>

							<input type="email" name="email" class="form-control" placeholder="<?php echo t('email_address', $language); ?>" value="destek@sayim.com.tr" required="">

						</div>

					</div>

					<div class="row mt-20">

						<div class="col-md-12">

							<div><?php echo t('password', $language); ?></div>

							<input type="password" name="sifre" class="form-control" placeholder="<?php echo t('password', $language); ?>" value="123" required="">

						</div>

					</div>

			       	<div class="row mt-10">

						<div class="col-md-12"><a href="sifremi-unuttum" style="color:green"><i class="las la-binoculars"></i> <?php echo t('forgot_password', $language); ?></a></div>

			       	</div>

			       	<div class="row mt-10">

						<div class="col-md-12"><button type="submit" class="btn btn-success" style="width: 100%;font-size: 20px"><?php echo t('login', $language); ?></button></div>

			       	</div>

				</form>

			</div>

		</div>

	</div>

</div>
</main>
<?php include 'inc/sabit-css.php'; ?>