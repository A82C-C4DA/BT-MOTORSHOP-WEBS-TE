<?php
$_title         =  'İletişim';
?>
<main id="content" role="main">
	<!-- breadcrumb -->
    <div class="bg-gray-13 bg-md-transparent">
        <div class="container">
            <!-- breadcrumb -->
            <div class="my-md-3">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-3 flex-nowrap flex-xl-wrap overflow-auto overflow-xl-visble">
                        <li class="breadcrumb-item flex-shrink-0 flex-xl-shrink-1"><a href="index.php">Anasayfa</a></li>
                        <li class="breadcrumb-item flex-shrink-0 flex-xl-shrink-1 active" aria-current="page">İletişim</li>
                    </ol>
                </nav>
            </div>
            <!-- End breadcrumb -->
        </div>
    </div>
    <!-- End breadcrumb -->
	<div class="container">

	<div class="row mt-20 mb-20">
		<div class="col-md-12">
			<div class="bg2">
				<div class="destek">
	              <div><i class="las la-headset"></i></div>
	              <div>
	                <span>Çağrı Merkezi</span>
	                <a href="tel:<?php echo $cek['telefon']; ?>" title="İletişim: <?php echo $cek['telefon']; ?>" alt="<?php echo $cek['telefon']; ?>"><?php echo $cek['telefon']; ?></a>
	              </div>
	            </div>
	            <div class="adres-email">
	              <div><span>Adres: </span><span><?php echo $cek['adres']; ?></span></div>
	              <div><span>Email: </span><span><?php echo $cek['email']; ?></span></div>
	            </div>
	            <ul class="list-inline mb-0 opacity-7">
                  <?php if(!empty($cek['facebook'])){ echo '<li class="list-inline-item mr-0">
                        <a class="btn font-size-20 btn-icon btn-soft-dark btn-bg-transparent rounded-circle" href="'.$cek['facebook'].'">
                            <span class="fab fa-facebook-f btn-icon__inner"></span>
                        </a>
                    </li>'; } ?>
                  <?php if(!empty($cek['twitter'])){ echo '<li class="list-inline-item mr-0">
                        <a class="btn font-size-20 btn-icon btn-soft-dark btn-bg-transparent rounded-circle" href="'.$cek['twitter'].'">
                            <span class="fab fa-twitter btn-icon__inner"></span>
                        </a>
                    </li>'; } ?>
                  <?php if(!empty($cek['instagram'])){ echo '<li class="list-inline-item mr-0">
                        <a class="btn font-size-20 btn-icon btn-soft-dark btn-bg-transparent rounded-circle" href="'.$cek['instagram'].'">
                            <span class="fab fa-instagram btn-icon__inner"></span>
                        </a>
                    </li>'; } ?>
                </ul>
			</div>
		</div>
		<div class="col-md-12">
			<?php echo $cek['google_harita_kodu']; ?>
		</div>
	</div>
</div>
</main>

<?php include 'inc/sabit-css.php'; ?>