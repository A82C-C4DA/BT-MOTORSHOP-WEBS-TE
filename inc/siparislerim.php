<?php
if(!isset($_SESSION['kullanici']['login'])){
    die('<meta http-equiv="refresh" content="0;URL=index.php">');
}
$_title         =  'Siparişlerim';


?>
<main id="content" role="main">
<div class="container">
	<div class="row mt-20 mb-20">
		
		<?php
			include 'inc/hesabim-sol-menu.php';
		?>


		<div class="col-md-9">
			
			<div class="bg2 border p20" style="float: left;width: 100%">
				<h2>Siparişlerim</h2>
				
				<div class="panel-group" id="accordion" role="tablist" aria-multiselectable="true">
          
	                <?php
	                  $i = 0;
	                  $query = $db->query("SELECT * FROM siparis WHERE kullanici_id = '{$_SESSION['kullanici']['id']}' ORDER BY id DESC ", PDO::FETCH_ASSOC);
	                  if($query->rowCount()){
	                    foreach($query as $row){
	                          
	                          $in = '';
	                          if($i == 0){
	                            $in = 'in';
	                          }

	                          $odeme_durumu = '';
	                          if($row['odeme_yontemi'] == 1){
	                            $odeme_durumu = '<b>Ödeme Durumu :</b> <br>'.$kredi_karti_odendi[$row['kredi_karti_odendi']];
	                          }

	                          echo '<div class="panel panel-default">
	                              <div class="panel-heading" role="tab" id="heading'.$row['id'].'">
	                                  <h4 class="panel-title">
	                                      <a class="collapsed" data-toggle="collapse" data-parent="#accordion" href="#collapse'.$row['id'].'" aria-expanded="false" aria-controls="collapse'.$row['id'].'">
	                                          Sipariş ID: '.$row['id'].' - Toplam Tutar: '.$row['toplam_tutar'].' TL - Ödeme Yöntemi : '.$odeme_yontemi[$row['odeme_yontemi']].'
	                                      </a>
	                                  </h4>
	                              </div>
	                              <div id="collapse'.$row['id'].'" class="panel-collapse collapse '.$in.'" role="tabpanel" aria-labelledby="heading'.$row['id'].'">
	                                  <div class="panel-body">
	                                  <div class="row">';

	                                  $query1 = $db->query("SELECT * FROM siparis_urun WHERE siparis_id = '{$row['id']}'", PDO::FETCH_ASSOC);
	                                  if($query1->rowCount()){
	                                    foreach($query1 as $row1){

	                                    	if(!empty($row1['img'])){
	                                    		 $urunquery = $db->prepare("SELECT * FROM turun where id=:id LIMIT 1");
		                                      $urun = $urunquery->execute(array(":id"=>$row1['urun_id']));
		                                      $urun = $urunquery->fetch(PDO::FETCH_ASSOC);

		                                      $urunimg = $db->prepare("SELECT * FROM turun_img where urun_id=:urun_id LIMIT 1");
		                                      $uimg = $urunimg->execute(array(":urun_id"=>$row1['urun_id']));
		                                      $uimg = $urunimg->fetch(PDO::FETCH_ASSOC);

		                                      $secenek = '';
		                                      if($row1['secenek'] !=0){

		                                          $alt_secenek = $db->prepare("SELECT * FROM turun_secenek_alt where id=:id LIMIT 1");
		                                          $as = $alt_secenek->execute(array(":id"=>$row1['secenek']));
		                                          $as = $alt_secenek->fetch(PDO::FETCH_ASSOC);

		                                          $ust_secenek = $db->prepare("SELECT * FROM turun_secenek where id=:id LIMIT 1");
		                                          $us = $ust_secenek->execute(array(":id"=>$as['urun_secenek_id']));
		                                          $us = $ust_secenek->fetch(PDO::FETCH_ASSOC);

		                                          $secenek .= '<b>'.$us['baslik'].':</b> '.$as['baslik'].'<br>';

		                                      }


		                                      echo '
		                                        <div class="col-md-6">
		                                          <div style="float:left;width:30%"><img src="'.$row1['img'].'" style="max-width:100%;border:1px solid #ddd;padding:5px;"></div>
		                                          <div style="float:left;width:65%;padding-left:5%">
		                                          <b style="color:#000">'.$urun['baslik'].'</b><br>
		                                          '.$secenek.'
		                                          <b>Adet:</b> '.$row1['adet'].'<br>
		                                          <b>Fiyat:</b> '.$row1['fiyat'].' TL<br>
		                                          '.$odeme_durumu.'<br>
		                                          <b>Sipariş Tarihi:</b> '.date('Y-m-d H:i:s', $row['siparis_tarihi']).'<br>
		                                          <b style="color:green;">Sipariş Durumu:<br> '.$siparis_durum[$row['durum']].'</b>
		                                          </div>
		                                        </div>
		                                      ';
	                                    	}else{
		                                      $urunquery = $db->prepare("SELECT * FROM urun where id=:id LIMIT 1");
		                                      $urun = $urunquery->execute(array(":id"=>$row1['urun_id']));
		                                      $urun = $urunquery->fetch(PDO::FETCH_ASSOC);

		                                      $urunimg = $db->prepare("SELECT * FROM urun_img where urun_id=:urun_id LIMIT 1");
		                                      $uimg = $urunimg->execute(array(":urun_id"=>$row1['urun_id']));
		                                      $uimg = $urunimg->fetch(PDO::FETCH_ASSOC);

		                                      $secenek = '';
		                                      if($row1['secenek'] !=0){

		                                          $alt_secenek = $db->prepare("SELECT * FROM urun_secenek_alt where id=:id LIMIT 1");
		                                          $as = $alt_secenek->execute(array(":id"=>$row1['secenek']));
		                                          $as = $alt_secenek->fetch(PDO::FETCH_ASSOC);

		                                          $ust_secenek = $db->prepare("SELECT * FROM urun_secenek where id=:id LIMIT 1");
		                                          $us = $ust_secenek->execute(array(":id"=>$as['urun_secenek_id']));
		                                          $us = $ust_secenek->fetch(PDO::FETCH_ASSOC);

		                                          $secenek .= '<b>'.$us['baslik'].':</b> '.$as['baslik'].'<br>';

		                                      }


		                                      echo '
		                                        <div class="col-md-6">
		                                          <div style="float:left;width:30%"><img src="upload/'.$uimg['img'].'" style="max-width:100%;border:1px solid #ddd;padding:5px;"></div>
		                                          <div style="float:left;width:65%;padding-left:5%">
		                                          <b style="color:#000">'.$urun['baslik'].'</b><br>
		                                          '.$secenek.'
		                                          <b>Adet:</b> '.$row1['adet'].'<br>
		                                          <b>Fiyat:</b> '.$row1['fiyat'].' TL<br>
		                                          '.$odeme_durumu.'<br>
		                                          <b>Sipariş Tarihi:</b> '.date('Y-m-d H:i:s', $row['siparis_tarihi']).'<br>
		                                          <b style="color:green;">Sipariş Durumu:<br> '.$siparis_durum[$row['durum']].'</b>
		                                          </div>
		                                        </div>
		                                      ';
		                                    }


	                                    }
	                                  }

	                          echo '    </div>
	                                  </div>
	                              </div>
	                          </div>';
	                          $i++;
	                    } 
	                  }else{
	                    echo '<center><h1>Siparişiniz bulunmamaktadır.</h1></center>';
	                  }
	                ?>
	                
	            </div>

			</div>
		</div>


	</div>
</div>
</main>
<?php include 'inc/sabit-css.php'; ?>