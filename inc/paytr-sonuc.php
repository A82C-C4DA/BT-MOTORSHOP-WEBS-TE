<?php
$_title         =  'Alışveriş Sonuç';
?>
<main id="content" role="main">
<div class="container">
	<div class="row mt-20 mb-20">

  <div class="col-md-12">
		 <?php
            if(isset($_GET['siparis_key'])){

              $query = $db->prepare("SELECT * FROM siparis where siparis_key=:siparis_key LIMIT 1");
              $gel = $query->execute(array(":siparis_key"=>$_GET['siparis_key']));
              $gel = $query->fetch(PDO::FETCH_ASSOC);

              if($gel){
                
                $islem = $db->prepare("UPDATE siparis SET kredi_karti_odendi = ? WHERE id = ?");
                $islem = $islem->execute(array(1,$gel['id']));

                echo '<center><img src="assets/images/basari.png" style="width:150px"><br><br>
                <div style="padding-bottom: 20px;font-size: 18px;color: #229f38;"><b>Siparişiniz başarı ile oluşturuldu.</b></div></li>
                <div style="padding-bottom: 20px;font-size: 18px;"><b>Sipariş özetiniz email adresinize gönderilmiştir.</b></div></center>';

              }else{
                echo '<center><img src="assets/images/hata.png" style="width:150px"><br><br><span style="font-size:25px;color:red;font-weight:bold">Bir hata oluştu.</span></center>';
              }

            }else{
              echo '<center><img src="assets/images/hata.png" style="width:150px"><br><br><span style="font-size:25px;color:red;font-weight:bold">Bir hata oluştu.</span></center>';
            }
        ?>
      </div>
	</div>
</div>
</main>

<?php include 'inc/sabit-css.php'; ?>