<?php
if(!isset($_SESSION['kullanici']['login'])){
    die('<meta http-equiv="refresh" content="0;URL=index.php">');
}
$_title         =  'Hesabım';

?>
<main id="content" role="main">
<div class="container">
	<div class="row mt-20 mb-20">
		
		<?php
			include 'inc/hesabim-sol-menu.php';
		?>


		<div class="col-md-6">
			<?php
              if($_POST){
                $query = $db->prepare("SELECT * FROM kullanici where email=:email AND id !=:id LIMIT 1");
                $bilgi = $query->execute(array(":email"=>$_POST['email'],":id"=>$_SESSION['kullanici']['id']));
                $bilgi = $query->fetch(PDO::FETCH_ASSOC);
                if($bilgi){
                    echo '<div class="hata">Bu email adresi zaten kullanılıyor.</div>';
                }else{
                  $islem = $db->prepare("UPDATE kullanici SET  ad = ?, soyad = ?, telefon = ?, email = ?, sifre = ?, adres = ?, tc = ? WHERE id = ?");
                  $islem = $islem->execute(array($_POST['ad'],$_POST['soyad'],$_POST['telefon'],$_POST['email'],$_POST['sifre'],$_POST['adres'],$_POST['tc'],$_SESSION['kullanici']['id']));
                  if($islem){
                        echo '<div class="basari">Bilgileriniz başarı ile değiştirildi.<meta http-equiv="refresh" content="2;URL=hesabim"></div>';
                    }else{
                        echo '<div class="hata">İşlem başarısız.</div>';
                    } 
                }
              }
              ?>
			<div class="bg2 border p20" style="float: left;width: 100%">
				<h2>Bilgilerimi Güncelle</h2>
				<form action="" method="post">
					<div class="row">
	              <fieldset class="form-group mt-4 col-md-6">
	                 <label>Adınız</label>
	                 <input type="text" class="form-control" name="ad" required="" placeholder="Adınız" value="<?php echo $kullanici['ad']; ?>">
	              </fieldset>
	              <fieldset class="form-group mt-4 col-md-6">
	                 <label>Soyadınız</label>
	                 <input type="text" class="form-control" name="soyad" required="" placeholder="Soyadınız" value="<?php echo $kullanici['soyad'];  ?>">
	              </fieldset>
	              <fieldset class="form-group mt-4 col-md-6">
	                 <label>Telefon</label>
	                 <input type="text" class="form-control" name="telefon" required="" placeholder="Telefon" value="<?php echo $kullanici['telefon']; ?>">
	              </fieldset>
	              <fieldset class="form-group mt-4 col-md-6">
	                 <label>Email Adresiniz</label>
	                 <input type="email" class="form-control" name="email" required="" placeholder="Email Adresiniz" value="<?php echo $kullanici['email']; ?>">
	              </fieldset>
	              <fieldset class="form-group col-md-12">
	                 <label>Şifreniz</label>
	                 <input type="password" class="form-control" name="sifre" required="" value="<?php echo $kullanici['sifre']; ?>">
	              </fieldset>
	              <fieldset class="form-group col-md-12">
	                 <label>Tc</label>
	                 <input type="text" class="form-control" name="tc" required="" value="<?php echo $kullanici['tc']; ?>">
	              </fieldset>
	              <fieldset class="form-group col-md-12">
	                 <label>Adresiniz</label>
	                 <textarea class="form-control" name="adres" required="" placeholder="Adresiniz" rows="3"><?php echo $kullanici['adres']; ?></textarea>
	              </fieldset>
	              <fieldset class="form-group col-md-12">
	                 <button class="btn icon-btn btn-success" style="width: 100%">Bilgilerimi Güncelle</button>
	              </fieldset>
	          </div>
	            </form>
			</div>
		</div>


	</div>
</div>
</main>

<?php include 'inc/sabit-css.php'; ?>