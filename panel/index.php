<?php
	include 'fonksiyon.php';

	if(!isset($_SESSION['admin']['login'])){
		die('<meta http-equiv="refresh" content="0;URL='.$site.'panel/giris-yap.php">');
	}

	// Urunler inline AJAX — HTML cikmadan once saf JSON dondur (SyntaxError onlenir)
	if (isset($_POST['ajax_urunler']) && $_POST['ajax_urunler'] === '1') {
		include __DIR__ . '/inc/urunler.php';
		exit;
	}

	if(isset($_GET['sayfa'])) {
      $sayfa = cleanAZ($_GET['sayfa']);
      if($sayfa == 'cikis-yap'){
        unset($_SESSION['admin']['login']);
        die('<meta http-equiv="refresh" content="0;URL='.$site.'panel/giris-yap.php">');
      }
      if (!is_file('inc/'.$sayfa.'.php')) {
            $sayfa = 'anasayfa';
      }
    }else{
      $sayfa = 'anasayfa'; 
    }

    function b(){
    	return '<script>$(function(){ not7(); });</script>';
    }

    function h(){
    	return '<script>jQuery(document).ready(function($){ if(typeof not8 !== "undefined"){ not8(); }else{ alert("Bir hata oluştu!"); } });</script>';
    }

    $ayar = $db->query("SELECT * FROM ayar LIMIT 1")->fetch(PDO::FETCH_ASSOC);

?>
<!doctype html>
<html lang="tr" dir="ltr">
	<head>

		<meta charset="UTF-8">
		<meta name='viewport' content='width=device-width, initial-scale=1.0, user-scalable=0'>
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<base href="<?php echo $site; ?>panel/">
		<!-- Title -->
		<title>Yönetim Paneli</title>

		<!-- Favicon -->
		<link rel="icon" href="inc/assets/img/brand/favicon.png" type="image/x-icon"/>

		<!-- Icons css -->
		<link href="inc/assets/css/icons.css" rel="stylesheet">

		<!-- Internal Spectrum-colorpicker css -->
		<link href="inc/assets/plugins/spectrum-colorpicker/spectrum.css" rel="stylesheet">

		<!--Internal   Notify -->
		<link href="inc/assets/plugins/notify/css/notifIt.css" rel="stylesheet"/>

		<!--- Style css-->
		<link href="inc/assets/css/style.css" rel="stylesheet">

		<!---Skinmodes css-->
		<link href="inc/assets/css/skin-modes.css" rel="stylesheet" />

		<!--- Animations css-->
		<link href="inc/assets/css/animate.css" rel="stylesheet">
		
		<!-- Select2 CSS -->
		<link href="inc/assets/plugins/select2/css/select2.min.css" rel="stylesheet" />
		
		<!-- JQuery min js -->
		<script src="inc/assets/plugins/jquery/jquery.min.js"></script>
		
		<!-- jQuery Mask Plugin -->
		<script src="inc/assets/plugins/jquery.maskedinput/jquery.maskedinput.js"></script>
		
		<!-- Rating Stars Plugin -->
		<script src="inc/assets/plugins/rating/jquery.rating-stars.js"></script>

	</head>

	<body class="main-body">

		<!-- Loader -->
		<div id="global-loader">
			<img src="inc/assets/img/loader.svg" class="loader-img" alt="Loader">
		</div>
		<!-- /Loader -->

		<!-- Page -->
		<div class="page">

			<!-- main-header opened -->
			<div class="main-header nav nav-item hor-header">
				<div class="container">
					<div class="main-header-left ">
						<a class="animated-arrow hor-toggle horizontal-navtoggle"><span></span></a><!-- sidebar-toggle-->
						<a class="header-brand" href="index.php">
							<img src="../upload/<?php echo $ayar['logo']; ?>" class="desktop-dark">
							<img src="../upload/<?php echo $ayar['logo']; ?>" class="desktop-logo">
							<img src="../upload/<?php echo $ayar['fav']; ?>" class="desktop-logo-1">
							<img src="../upload/<?php echo $ayar['fav']; ?>" class="desktop-logo-dark">
						</a>
						<div class="main-header-center  ml-4">
							<form action="urunler" method="post">
							<input class="form-control" placeholder="Ürün Adı İle Arama Yap..." name="ara" type="search"><button type="submit" class="btn"><i class="fe fe-search"></i></button>
							</form>
						</div>
					</div><!-- search -->
					<div class="main-header-right">
						<div class="nav nav-item  navbar-nav-right ml-auto">
							<div class="nav-item full-screen fullscreen-button">
								<a class="new nav-link full-screen-link" href="#"><svg xmlns="http://www.w3.org/2000/svg" class="header-icon-svgs" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-maximize"><path d="M8 3H5a2 2 0 0 0-2 2v3m18 0V5a2 2 0 0 0-2-2h-3m0 18h3a2 2 0 0 0 2-2v-3M3 16v3a2 2 0 0 0 2 2h3"></path></svg></a>
							</div>
							<div class="dropdown main-profile-menu nav nav-item nav-link">
								<a class="profile-user d-flex" href=""><img alt="" src="../upload/<?php echo $ayar['fav']; ?>"></a>
							</div>
						</div>
					</div>
				</div>
			</div>
			<!-- /main-header -->

			<!--Horizontal-main -->
			<div class="sticky">
				<div class="horizontal-main hor-menu clearfix side-header">
					<div class="horizontal-mainwrapper container clearfix">
						<!--Nav-->
						<nav class="horizontalMenu clearfix">
							<ul class="horizontalMenu-list">
								<li aria-haspopup="true"><a href="anasayfa" class=""><i class="si si-chart"></i>Özet</a></li>
								<li aria-haspopup="true"><a href="#" class="sub-icon"><i class="fa fa-shopping-basket"></i> Siparişler<i class="fe fe-chevron-down horizontal-icon"></i></a>
									<ul class="sub-menu">
										<li aria-haspopup="true"><a href="siparis/0" class="slide-item">Onay Bekleyen</a></li>
										<li aria-haspopup="true"><a href="siparis/1" class="slide-item">Ödeme Bekleyen</a></li>
										<li aria-haspopup="true"><a href="siparis/2" class="slide-item">Ödeme Alındı</a></li>
										<li aria-haspopup="true"><a href="siparis/3" class="slide-item">Onaylanan</a></li>
										<li aria-haspopup="true"><a href="siparis/4" class="slide-item">Kargoda</a></li>
										<li aria-haspopup="true"><a href="siparis/5" class="slide-item">Tamamlanan</a></li>
										<li aria-haspopup="true"><a href="siparis/6" class="slide-item">İptal Edilen</a></li>
									</ul>
								</li>
								<li aria-haspopup="true"><a href="#" class="sub-icon"><i class="fa fa-tags"></i> Ürün<i class="fe fe-chevron-down horizontal-icon"></i></a>
									<ul class="sub-menu">
										<li aria-haspopup="true"><a href="urun" class="slide-item">Ürün Ekle</a></li>
										<li aria-haspopup="true"><a href="urunler" class="slide-item">Ürünleri Listele</a></li>
										<li aria-haspopup="true"><a href="eryaz-urunler" class="slide-item">Eryaz Ürünleri</a></li>
										<li aria-haspopup="true"><a href="eryaz-kategori-eslestir" class="slide-item">Eryaz Kategori Eşleştirme</a></li>
									</ul>
								</li>
								<li aria-haspopup="true"><a href="#" class="sub-icon"><i class="fa fa-tags"></i> Ürün Tasarla<i class="fe fe-chevron-down horizontal-icon"></i></a>
									<ul class="sub-menu">
										<li aria-haspopup="true"><a href="tkategori" class="slide-item">Tasarla Kategorileri</a></li>
										<li aria-haspopup="true"><a href="turun" class="slide-item">Ürün Ekle</a></li>
										<li aria-haspopup="true"><a href="turunler" class="slide-item">Ürünleri Listele</a></li>
									</ul>
								</li>
								<li aria-haspopup="true"><a href="kategori"><i class="fa fa-server"></i> Kategori</a></li>
								<li aria-haspopup="true"><a href="marka"><i class="fas fa-flag"></i> Marka</a></li>
								<li aria-haspopup="true"><a href="#" class="sub-icon"><i class="fas fa-comment-dots"></i> Yorum<i class="fe fe-chevron-down horizontal-icon"></i></a>
									<ul class="sub-menu">
										<li aria-haspopup="true"><a href="yorum/0" class="slide-item">Onay Bekleyen Yorumlar</a></li>
										<li aria-haspopup="true"><a href="yorum/1" class="slide-item">Onaylanan Yorumlar</a></li>
									</ul>
								</li>
								<li aria-haspopup="true"><a href="#" class="sub-icon"><i class="fas fa-file-alt"></i> İçerik Yönetimi<i class="fe fe-chevron-down horizontal-icon"></i></a>
									<ul class="sub-menu">
										<li aria-haspopup="true"><a href="abone" class="slide-item">Abone Olanlar</a></li>
										<li aria-haspopup="true"><a href="kupon-yonetimi" class="slide-item">Kupon Yönetimi</a></li>
										<li aria-haspopup="true"><a href="ic-sayfa" class="slide-item">İç Sayfalar</a></li>
										<li aria-haspopup="true"><a href="slider" class="slide-item">Anasayfa Slider</a></li>
										<li aria-haspopup="true"><a href="hikaye" class="slide-item">Anasayfa Hikaye</a></li>
										<li aria-haspopup="true"><a href="haftanin-urunleri" class="slide-item">Haftanın Ürünleri</a></li>
										<li aria-haspopup="true"><a href="kampanyalar-1" class="slide-item">Anasayfa Kampanyalar Üst</a></li>
										<li aria-haspopup="true"><a href="kampanyalar-2" class="slide-item">Anasayfa Kampanyalar Alt</a></li>
										<li aria-haspopup="true"><a href="blog" class="slide-item">Blog Yazıları</a></li>
										<li aria-haspopup="true"><a href="etiket" class="slide-item">Site Altı Etiketler</a></li>										
										<li aria-haspopup="true"><a href="vitrin1" class="slide-item">Anasayfa Tasarla Ürün Vitrinleri</a></li>
										<li aria-haspopup="true"><a href="vitrin" class="slide-item">Anasayfa Ürün Vitrinleri</a></li>
										<li aria-haspopup="true"><a href="banka-hesaplari" class="slide-item">Banka Hesapları</a></li>
										<li aria-haspopup="true"><a href="aktif-musteriler" class="slide-item">Aktif Müşteriler</a></li>
										<li aria-haspopup="true"><a href="pasif-musteriler" class="slide-item">Pasif Müşteriler</a></li>
									</ul>
								</li>
								<li aria-haspopup="true"><a href="ai-asistan"><i class="fas fa-robot"></i> AI Asistan</a></li>
								<li aria-haspopup="true"><a href="#" class="sub-icon"><i class="fa fa-code"></i> Yapılandırma<i class="fe fe-chevron-down horizontal-icon"></i></a>
									<ul class="sub-menu">
										<li aria-haspopup="true"><a href="fiyat-guncelle" class="slide-item">Kategori Bazlı Fiyat Güncelle</a></li>
										<li aria-haspopup="true"><a href="odeme-yontemleri" class="slide-item">Ödeme Yöntemleri</a></li>
										<li aria-haspopup="true"><a href="iyzico-sanal-pos-ayari" class="slide-item">İyzico Sanal Pos Ayarı</a></li>
										<li aria-haspopup="true"><a href="paytr-sanal-pos-ayari" class="slide-item">Paytr Sanal Pos Ayarı</a></li>
										<li aria-haspopup="true"><a href="shopier-sanal-pos-ayari" class="slide-item">Shopier Sanal Pos Ayarı</a></li>
										<li aria-haspopup="true"><a href="email-ayari" class="slide-item">E-mail Ayarı</a></li>
										<li aria-haspopup="true"><a href="netgsm" class="slide-item">NetGsm Ayarı</a></li>
										<li aria-haspopup="true"><a href="sms-izinleri" class="slide-item">Sms İzinleri</a></li>
										<li aria-haspopup="true"><a href="ai-ayar" class="slide-item"><i class="fas fa-robot"></i> AI Asistan Ayarları</a></li>
										<li aria-haspopup="true"><a href="panel-kullanicilari" class="slide-item">Panel Kullanıcıları</a></li>
										<li aria-haspopup="true"><a href="ayar" class="slide-item">Genel Ayarlar</a></li>
									</ul>
								</li>

								<li aria-haspopup="true"><a href="kategori"><i class="fa fa-times"></i> Çıkış Yap</a></li>
							</ul>
						</nav>
						<!--Nav-->
					</div>
				</div>
			</div>
			<!--Horizontal-main -->

			<!-- main-content opened -->
			<div class="main-content horizontal-content">

				<!-- container opened -->
				<div class="container">

					<?php include 'inc/'.$sayfa.'.php'; ?>

				</div>
				<!-- Container closed -->
			</div>
			<!-- main-content closed -->




			<!-- Footer opened -->
			<div class="main-footer ht-40">
				<div class="container-fluid pd-t-0-f ht-100p">
					<span>Bu site <a href="https://netkreatif.com.tr">Netkreatif</a>® <a href="https://eticaretscript.com.tr" title="e-ticaret yazılımı">E-Ticaret</a> sistemleri ile hazırlanmıştır.</span>
				</div>
			</div>
			<!-- Footer closed -->

		</div>
		<!-- End Page -->

		<!-- Back-to-top -->
		<a href="#top" id="back-to-top"><i class="las la-angle-double-up"></i></a>

		<!-- Bootstrap Bundle js -->
		<script src="inc/assets/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>

		<!--Internal  spectrum-colorpicker js -->
		<script src="inc/assets/plugins/spectrum-colorpicker/spectrum.js"></script>

		<!-- Horizontalmenu js-->
		<script src="inc/assets/plugins/horizontal-menu/horizontal-menu-2/horizontal-menu.js"></script>

		<!--Internal  Notify js -->
		<script src="inc/assets/plugins/notify/js/notifIt.js"></script>
		<script src="inc/assets/plugins/notify/js/notifit-custom.js"></script>
		
		<!-- Notify fonksiyonlarının yüklendiğinden emin ol -->
		<script>
		jQuery(document).ready(function($){
			// not8 fonksiyonunun yüklendiğini kontrol et
			if(typeof not8 === 'undefined'){
				// not8 tanımlı değilse, global olarak tanımla
				window.not8 = function(){
					alert("Bir hata oluştu!");
				};
			}
			if(typeof not7 === 'undefined'){
				// not7 tanımlı değilse, global olarak tanımla
				window.not7 = function(){
					alert("İşlem başarıyla tamamlandı!");
				};
			}
		});
		</script>

		<!-- Select2 JS - form-elements.js'den ÖNCE yüklenmeli -->
		<script src="inc/assets/plugins/select2/js/select2.min.js"></script>
		<!-- Select2 Türkçe dil desteği -->
		<script src="inc/assets/plugins/select2/js/i18n/tr.js"></script>
		
		<!-- Internal form-elements js -->
		<script src="inc/assets/js/form-elements.js"></script>
		
		<!-- custom js -->
		<script src="inc/assets/js/custom.js"></script>

		<!-- Fotoğraf upload-->
		<link rel="stylesheet" href="inc/assets/uploadfive/uploadifive.css" type="text/css">
    	<script src="inc/assets/uploadfive/jquery.uploadifive.min.js" type="text/javascript"></script>
    	<script type="text/javascript">
	    jQuery(document).ready(function($){

	      	var date = new Date();
	        var date_time = date.getTime();
	        $('.upload .icon span').uploadifive({
	            'auto'             : true,
	            'queueID'  : 'queue',
	            'fileSizeLimit' : '15360KB',
	            'fileExt'     : '*.jpg;*.jpeg;*.JPG;*.JPEG;*.png;*.PNG;*.svg;*.gif',
	            'width' : 25,
	            'buttonText' : " ",
	            'formData'         : {'timestamp' : date_time,'token' : 'sayim'+date_time+'sayim'},
	            'uploadScript'     : 'inc/assets/uploadfive/uploadifive.php',
	            'removeCompleted' : true,
	            'onUploadComplete' : function(file, data) {
	                if(data == '2'){
	                    alert('Lütfen Geçerli Fortmatta Yükleme Yapınız.');
	                }else if(data == '3'){
	                    alert('İşlem Başarısız.(Dosya Boyutu İle Alakalı Olabilir.)');
	                }else{
	                    var id = $(this).attr('data-id');
	                    $('input[name="img'+id+'"]').val(data);
	                    $('#url').val('<?php echo $site; ?>upload/'+data);
	                    $('.uploaddis[data-id="'+id+'"] .yuklendi img').attr('src','../upload/'+data);
	                    $('.uploaddis[data-id="'+id+'"]').removeClass('aktif');
	                    $('.uploaddis[data-id="'+id+'"]').addClass('pasif');
	                }
	            }
	        });

	        $('.upload1 .icon span').uploadifive({
	            'auto'             : true,
	            'queueID'  : 'queue',
	            'fileSizeLimit' : '15360KB',
	            'fileExt'     : '*.jpg;*.jpeg;*.JPG;*.JPEG;*.png;*.PNG;*.svg;*.gif',
	            'width' : 25,
	            'buttonText' : " ",
	            'formData'         : {'timestamp' : date_time,'token' : 'sayim'+date_time+'sayim'},
	            'uploadScript'     : 'inc/assets/uploadfive/uploadifive.php',
	            'removeCompleted' : true,
	            'onUploadComplete' : function(file, data) {
	                if(data == '2'){
	                    alert('Lütfen Geçerli Fortmatta Yükleme Yapınız.');
	                }else if(data == '3'){
	                    alert('İşlem Başarısız.(Dosya Boyutu İle Alakalı Olabilir.)');
	                }else{
	                    var say = $('#resimler .col-md-3').length;
	                    $('#resimler').append('\
	                    	<div class="col-md-3" data-resim-dis-id="'+say+'">\
				                    <div class="uploaddis pasif" style="float:left;">\
				        			  <div class="yuklendi">\
				        				  <img src="../upload/'+data+'">\
				        				  <div class="icon" data-resim-sil-id="'+say+'"><span class="fa fa-trash"></span></div>\
				        				  <input type="hidden" name="img[]" value="'+data+'" required="">\
				        			  </div>\
				        			</div>\
				                </div>\
				        ');

	                }
	            }
	        });
	        $(document).on('click','[data-resim-sil-id]', function(){
	        	$('[data-resim-dis-id="'+$(this).attr('data-resim-sil-id')+'"]').remove();
	        });

	        $('.yuklendi .icon').click(function(){
	            var id = $(this).attr('data-id');
	            $('.uploaddis[data-id="'+id+'"]').removeClass('pasif');
	            $('.uploaddis[data-id="'+id+'"]').addClass('aktif');
	            $('input[name="img'+id+'"]').val('');
	            $('.uploaddis[data-id="'+id+'"] .yuklendi img').attr('src','');
	        });
	      });
	    </script>
	</body>
</html>