<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

if(isset($_SESSION['kullanici']['login'])){
    die('<meta http-equiv="refresh" content="0;URL=index.php">');
}
$_title         =  'Şifremi Unuttum';
?>

<main id="content" role="main">
<div class="container">
	<div class="row mt-20 mb-20">
		<div class="col-md-7 kayit_sol">
			<div class="bg3 border p20">
				<h2>Zaten Üye Misiniz ?</h2>
				<p>Giriş yap butonuna tıklayarak giriş yapabilirsiniz.</p>
				<div class="row mt-20">
					<div class="col-md-4">
						<div class="bg2 p20 border">
							<i class="fa fa-user-shield"></i>
							<div>Güvenli Ödeme <br>Keyifli Alışveriş</div>
						</div>
					</div>
					<div class="col-md-4">
						<div class="bg2 p20 border">
							<i class="fa fa-home"></i>
							<div>Ücretsiz ve kolay<br> kayıt ol</div>
						</div>
					</div>
					<div class="col-md-4">
						<div class="bg2 p20 border">
							<i class="fa fa-skiing"></i>
							<div>Hızlı ve güvenli <br>alışverişin yeni adresi</div>
						</div>
					</div>
				</div>
				<div class="row mt-20">
					<div class="col-md-6 col-md-offset-3 mt-20 mb-20"><a href="giris-yap" class="btn btn-info" style="width: 100%;padding: 15px;font-size: 25px">Giriş Yap</a></div>
				</div>
			</div>
		</div>
		<div class="col-md-5">
			<div class="bg2 border p20">
				<?php
					if($_POST){
						if(!empty($_POST['email'])){
							  $query = $db->prepare("SELECT * FROM kullanici where email=:email LIMIT 1");
			                  $kullanici = $query->execute(array(":email"=>$_POST['email']));
			                  $kullanici = $query->fetch(PDO::FETCH_ASSOC);
			                  if($kullanici){

			                  	  if($sms_izin['sifre_sifirlama'] == 1){
			                  	  	$mesaj = 'Giriş Email Adresiniz: '.$kullanici['email'].' Giriş Şifreniz: '.$kullanici['sifre'];
			                  	  	sms($mesaj,$kullanici['telefon']);
			                  	  }
			                  	  
			                      $mailbody = '<!DOCTYPE html>
			                        <html>
			                        <head>
			                          <title>'.$cek['title'].'</title>
			                          <meta charset="utf-8">
			                        </head>
			                        <body style="padding: 30px">
			                          <div style="width: 98%;margin:0 auto;background: #02add9;padding: 1%;display: inline-block;border-radius: 10px">
			                            <div style="width: 90%;float: left;background: #fff;padding: 10px 5% 20px 5%;">
			                              <center><img src="'.$site.'upload/'.$cek['logo'].'" style="width: 200px"></center>
			                              <table>
			                                <tr>
			                                  <th>Email Adresiniz</th>
			                                  <th>Şifreniz</th>
			                                </tr>
			                                <tr>
			                                  <td>'.$kullanici['email'].'</td>
			                                  <td>'.$kullanici['sifre'].'</td>
			                                </tr>
			                              </table>
			                            </div>
			                          </div>
			                          <style type="text/css">
			                          body{font-family:arial}table{width:100%;border:1px solid #ddd}table tr{padding:0;margin:0}table tr th{border:1px solid #ddd;padding:0;margin:0;background:#02add9;color:#fff;padding:10px}table tr td{border:1px solid #ddd;padding:0;text-align:center;margin:0;border-spacing:0}
			                          </style>
			                        </body>
			                        </html>';

			                      email_send($_POST['email'],$mailbody,'Şifremi Unuttum');

			                      echo '<center><img src="assets/images/basari.png" style="width:150px"><br><br><span style="font-size:25px;color:red;font-weight:bold">Şifreniz mail adresinize gönderildi</span></center>';
			                  }else{
			                     echo '<center><img src="assets/images/hata.png" style="width:150px"><br><br><span style="font-size:25px;color:red;font-weight:bold">Kullanıcı Bulunamadı</span></center>';
			                  }
						}
					}
				?>
				<h2>Şifremi Unuttum</h2>
				<form action="" method="post">
					<div class="row mt-20">
						<div class="col-md-12">
							<div>E-Posta Adresi</div>
							<input type="email" name="email" class="form-control" placeholder="E-Posta Adresi" required="">
						</div>
					</div>
			       	<div class="row mt-10">
						<div class="col-md-12"><button type="submit" class="btn btn-success" style="width: 100%;font-size: 20px">Şifremi Gönder</button></div>
			       	</div>
				</form>
			</div>
		</div>
	</div>
</div>
</main>

<?php include 'inc/sabit-css.php'; ?>