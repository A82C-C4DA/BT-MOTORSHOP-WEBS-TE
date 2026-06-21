<?php
	include 'fonksiyon.php';

	if(isset($_SESSION['admin']['login'])){
		die('<meta http-equiv="refresh" content="0;URL='.$site.'panel/">');
	}
	

    $ayar = $db->query("SELECT * FROM ayar LIMIT 1")->fetch(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="tr">
	<head>

		<meta charset="UTF-8">
		<meta name='viewport' content='width=device-width, initial-scale=1.0, user-scalable=0'>
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta name="Author" content="https://www.sayim.com.tr">

		<!-- Title -->
		<title>Giriş Yap</title>

		<!-- Favicon -->
		<link rel="icon" href="assets/img/brand/favicon.png" type="image/x-icon"/>

		<!-- Icons css -->
		<link href="assets/css/icons.css" rel="stylesheet">

		<!--  Right-sidemenu css -->
		<link href="assets/plugins/sidebar/sidebar.css" rel="stylesheet">

		<!--  Custom Scroll bar-->
		<link href="assets/plugins/mscrollbar/jquery.mCustomScrollbar.css" rel="stylesheet"/>

		<!--- Style css --->
		<link href="assets/css/style.css" rel="stylesheet">

		<!--- Dark-mode css --->
		<link href="assets/css/style-dark.css" rel="stylesheet">

		<!---Skinmodes css-->
		<link href="assets/css/skin-modes.css" rel="stylesheet" />

		<!--- Animations css-->
		<link href="assets/css/animate.css" rel="stylesheet">

		<style type="text/css">
			#saydam{
				position: absolute;
				left: 0px;
				top: 0px;
				width: 100%;
				height: 100%;
				background: #0d324f6b;
				pointer-events: none;
				z-index: 0;
			}
			.page {
				position: relative;
				z-index: 1;
			}
		</style>
	</head>
	<body class="error-page1" style="background: url('assets/img/backgrounds/login.png');background-size:100%">

		<div id="saydam"></div>
		<!-- Loader -->
		<div id="global-loader">
			<img src="assets/img/loader.svg" class="loader-img" alt="Loader">
		</div>
		<!-- /Loader -->

		<!-- Page -->
		<div class="page">

			<div class="container-fluid">
				<div class="row no-gutter">
					<!-- The image half -->
					<div class="col-md-4"></div>
					<!-- The content half -->
					<div class="col-md-4 col-lg-4 col-xl-4">
						<div class="login d-flex align-items-center py-2">
							<!-- Demo content-->
							<div class="container p-0">
								<div class="row  bg-white" style="padding: 30px;border-radius: 20px;box-shadow: 0px 0px 20px #3a3737;">
									<div class="col-md-10 col-lg-10 col-xl-9 mx-auto">
										<div class="card-sigin">
											<div class="mb-5 d-flex"> <center><img src="../upload/<?php echo $ayar['logo']; ?>" class="sign-favicon ht-40" alt=""></center></div>
											<div class="card-sigin">
												<?php
													if($_POST){

														$query = $db->prepare("SELECT * FROM panel_kullanicilari where kullanici_adi=:kullanici_adi AND sifre=:sifre");
														$sql = $query->execute(array(":kullanici_adi"=>$_POST['kullanici_adi'],":sifre"=>$_POST['sifre']));
														$sql = $query->fetch(PDO::FETCH_ASSOC);

														if($sql){
															$_SESSION['admin']['login'] = 1;
					                                  		$_SESSION['admin']['id'] = $sql['id'];
															echo '<div style="background: green;margin-bottom: 20px;color: #fff;font-size: 17px;float: left;width: 100%;padding: 5px 0px;text-align:center">Yönlendiriliyorsunuz.</div><meta http-equiv="refresh" content="2;URL=anasayfa">';
														}else{
															echo '<div style="background: #ff0000;margin-bottom: 20px;color: #fff;font-size: 17px;float: left;width: 100%;padding: 5px 0px;text-align:center">Giriş bilgileri yanlış.</div>';
														}
														
													}
												?>
												<div class="main-signup-header">
													<form action="" method="post">
														<div class="form-group">
															<input type="text" class="form-control" placeholder="Kullanıcı Adı" name="kullanici_adi" value="admin" required="">
														</div>
														<div class="form-group">
															<input type="password" class="form-control" placeholder="Şifre" name="sifre" value="admin" required="">
														</div>
														<button type="submit" class="btn btn-main-primary btn-block">Giriş Yap</button>
													</form>
												</div>
											</div>
										</div>
									</div>
								</div>
							</div><!-- End -->
						</div>
					</div><!-- End -->
				</div>
			</div>

		</div>
		<!-- End Page -->

		<!-- JQuery min js -->
		<script src="assets/plugins/jquery/jquery.min.js"></script>

		<!-- Bootstrap Bundle js -->
		<script src="assets/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>

		<!-- Ionicons js -->
		<script src="assets/plugins/ionicons/ionicons.js"></script>

		<!-- Moment js -->
		<script src="assets/plugins/moment/moment.js"></script>

		<!-- eva-icons js -->
		<script src="assets/js/eva-icons.min.js"></script>

		<!-- Rating js-->
		<script src="assets/plugins/rating/jquery.rating-stars.js"></script>
		<script src="assets/plugins/rating/jquery.barrating.js"></script>

		<!-- custom js -->
		<script src="assets/js/custom.js"></script>

	</body>
</html>