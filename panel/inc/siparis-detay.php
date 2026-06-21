<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

if($_POST){
	$islem = $db->prepare("UPDATE siparis SET  durum = ? WHERE id = ?");
	$islem = $islem->execute(array($_POST['durum'],$_GET['no']));
	echo b();
}

$query = $db->prepare("SELECT * FROM siparis WHERE id=:id LIMIT 1");
$siparis = $query->execute(array(":id"=>$_GET['no']));
$siparis = $query->fetch(PDO::FETCH_ASSOC);


if($_POST){

	$kargo_takip = ''; 

	if(isset($_POST['kargo_takip']) AND !empty($_POST['kargo_takip'])){

		$kargoquery = $db->prepare("SELECT * FROM siparis_kargo_takip WHERE siparis_id=:siparis_id LIMIT 1");
		$kargo = $kargoquery->execute(array(":siparis_id"=>$_GET['no']));
		$kargo = $kargoquery->fetch(PDO::FETCH_ASSOC);
		if(!$kargo){
			$islem = $db->prepare("INSERT INTO siparis_kargo_takip SET siparis_id = ?, kargo_no = ?, kargo_adi = ?");
        	$islem = $islem->execute(array($_GET['no'],$_POST['kargo_takip'],$_POST['kargo_adi']));
		}

		$kargo_takip = '<tr><th>Kargo Firması: '.$_POST['kargo_adi'].'</th></tr>';
		$kargo_takip .= '<tr><th>Kargo Takip Numaranız: '.$_POST['kargo_takip'].'</th></tr>';

	}

	if($_POST['durum'] == 3){

		
		$query = $db->query("SELECT * FROM siparis_urun WHERE siparis_id = '{$_GET['no']}'", PDO::FETCH_ASSOC);
        if($query->rowCount()){
      		foreach($query as $row){



      			$urunquery = $db->prepare("SELECT * FROM urun where id=:id LIMIT 1");
		        $urun = $urunquery->execute(array(":id"=>$row['urun_id']));
		        $urun = $urunquery->fetch(PDO::FETCH_ASSOC);

		        if($row['secenek'] > 0){

		        	$alt_secenek = $db->prepare("SELECT * FROM urun_secenek_alt where id=:id LIMIT 1");
                    $as = $alt_secenek->execute(array(":id"=>$row['secenek']));
                    $as = $alt_secenek->fetch(PDO::FETCH_ASSOC);

                    $guncel_stok = $as['stok'] - $row['adet'];

                    $islem = $db->prepare("UPDATE urun_secenek_alt SET  stok = ? WHERE id = ?");
					$islem = $islem->execute(array($guncel_stok,$as['id']));

		        }else{

                    $guncel_stok = $urun['stok'] - $row['adet'];

		        	$islem = $db->prepare("UPDATE urun SET  stok = ? WHERE id = ?");
					$islem = $islem->execute(array($guncel_stok,$row['urun_id']));

		        }

		    }
		}

	}

	$mailbody = '<!DOCTYPE html>
    <html>
    <head>
      <title>'.$ayar['title'].'</title>
      <meta charset="utf-8">
    </head>
    <body style="padding: 30px">
      <div style="width: 98%;margin:0 auto;background: #02add9;padding: 1%;display: inline-block;border-radius: 10px">
        <div style="width: 90%;float: left;background: #fff;padding: 10px 5% 20px 5%;">
          <center><img src="'.$site.'upload/'.$ayar['logo'].'" style="width: 200px"></center>
          <table>
            <tr>
              <th>Merhaba, '.$siparis['ad'].' '.$siparis['soyad'].', sipariş durumunuz güncellenmiştir.<br>Yeni Sipariş Durumunuz: '.$siparis_durum[$siparis['durum']].'<br>Değişiklik Tarihi: '.date('Y-m-d H:i:s', time()).'</th>
            </tr>
            '.$kargo_takip.'
          </table>
        </div>
      </div>
      <style type="text/css">
      body{font-family:arial}table{width:100%;border:1px solid #ddd}table tr{padding:0;margin:0}table tr th{border:1px solid #ddd;padding:0;margin:0;background:#02add9;color:#fff;padding:10px}table tr td{border:1px solid #ddd;padding:0;text-align:center;margin:0;border-spacing:0}
      </style>
    </body>
    </html>';


    email_send($siparis['email'],$mailbody,'Şifremi Unuttum');

	if($sms_izin['siparis_durumu'] == 1){
		sms('Siparişinizin durumu güncellenedi. Siparişinizin durumu: '.$siparis_durum[$siparis['durum']],$siparis['telefon']);
	}
	

}


if(!$siparis){
	die('<meta http-equiv="refresh" content="0;URL=index.php">');
}
?> 
<div class="row" style="margin-top: 40px;">
	<div class="col-md-12">
		<div class="card">
			<div class="card-body">
				<div class="invoice-header text-left d-block ">
					<h1 class="invoice-title font-weight-bold text-uppercase mb-1" style="color: green;">Sipariş ID : <?php echo $siparis['id']; ?></h1>

				</div><!-- invoice-header -->
				<div class="row mt-4">
					<div class="col-md">
						<label class="font-weight-bold">Ad Soyad: <?php echo $siparis['ad'].' '.$siparis['soyad']; ?></label><br>
						<label class="font-weight-bold">Telefon: <?php echo $siparis['telefon']; ?></label><br>
						<label class="font-weight-bold">Email: <?php echo $siparis['email']; ?></label><br>
						<label class="font-weight-bold">Sevk Adresi: <?php echo $siparis['adres']; ?></label><br>
						<label class="font-weight-bold">Tc: <?php echo $siparis['tc']; ?></label><br>
						<label class="font-weight-bold">Sipariş Tarihi: <?php echo date('Y-m-d H:i:s',$siparis['siparis_tarihi']); ?></label><br>
						<label class="font-weight-bold">Toplam Tutar: <?php echo fiyat($siparis['toplam_tutar']); ?> TL</label><br>
						<label class="font-weight-bold" style="color: green">Sipariş Durumu: <?php echo $siparis_durum[$siparis['durum']]; ?></label><br>
						<?php
						if($siparis['odeme_yontemi'] == 1){
							if($siparis['kredi_karti_odendi'] == 1){
								echo '<label class="font-weight-bold" style="color: green">Ödeme Alındı</label><br>';
							}else{
								echo '<label class="font-weight-bold" style="color: red">Ödeme Yapılmamış</label><br>';
							}
						}
						?>
					</div>
				</div>
				<div class="table-responsive mt-4">
					<table class="table table-bordered border text-nowrap mb-0">
						<thead>
							<tr>
								<th class="wd-20p">Ürün ID</th>
								<th class="wd-20p">Fotoğraf</th>
								<th class="wd-20p">Ürün Adı</th>
								<th class="tx-center">Seçenek</th>
								<th class="tx-center">Sipariş Adedi</th>
							</tr>
						</thead>
						<tbody>

							<?php
				              $query = $db->query("SELECT * FROM siparis_urun WHERE siparis_id = '{$siparis['id']}'", PDO::FETCH_ASSOC);
				              if($query->rowCount()){
				              	foreach($query as $row){


				              		if(!empty($row['img'])){

				              			$urunquery = $db->prepare("SELECT * FROM turun where id=:id LIMIT 1");
														$urun = $urunquery->execute(array(":id"=>$row['urun_id']));
														$urun = $urunquery->fetch(PDO::FETCH_ASSOC);

														$secenek = '';
														if($row['secenek'] !=0){
															$alt_secenek = $db->prepare("SELECT * FROM turun_secenek_alt where id=:id LIMIT 1");
															$as = $alt_secenek->execute(array(":id"=>$row['secenek']));
															$as = $alt_secenek->fetch(PDO::FETCH_ASSOC);
															$ust_secenek = $db->prepare("SELECT * FROM turun_secenek where id=:id LIMIT 1");
															$us = $ust_secenek->execute(array(":id"=>$as['urun_secenek_id']));
															$us = $ust_secenek->fetch(PDO::FETCH_ASSOC);
															$secenek .= '<b>'.$us['baslik'].':</b> '.$as['baslik'].'<br>';
								        		}


													echo '<tr>
														<td class="tx-right">'.$row['urun_id'].'</td>
														<td class="tx-right"><a href="'.$row['img'].'" target="_blank"><img src="'.$row['img'].'" style="border:1px solid #ddd;padding:5px;max-width:100px"></a></td>
														<td class="tx-right">'.$urun['baslik'].'</td>
														<td class="tx-right">'.$secenek.'</td>
														<td class="tx-right">'.$row['adet'].'</td>
													</tr>';

				              		}else{
														$urunquery = $db->prepare("SELECT * FROM urun where id=:id LIMIT 1");
														$urun = $urunquery->execute(array(":id"=>$row['urun_id']));
														$urun = $urunquery->fetch(PDO::FETCH_ASSOC);

														$urunimg = $db->prepare("SELECT * FROM urun_img where urun_id=:urun_id LIMIT 1");
														$uimg = $urunimg->execute(array(":urun_id"=>$row['urun_id']));
														$uimg = $urunimg->fetch(PDO::FETCH_ASSOC);

														$secenek = '';
														if($row['secenek'] !=0){
															$alt_secenek = $db->prepare("SELECT * FROM urun_secenek_alt where id=:id LIMIT 1");
															$as = $alt_secenek->execute(array(":id"=>$row['secenek']));
															$as = $alt_secenek->fetch(PDO::FETCH_ASSOC);
															$ust_secenek = $db->prepare("SELECT * FROM urun_secenek where id=:id LIMIT 1");
															$us = $ust_secenek->execute(array(":id"=>$as['urun_secenek_id']));
															$us = $ust_secenek->fetch(PDO::FETCH_ASSOC);
															$secenek .= '<b>'.$us['baslik'].':</b> '.$as['baslik'].'<br>';
								        		}


													echo '<tr>
														<td class="tx-right">'.$row['urun_id'].'</td>
														<td class="tx-right"><img src="../upload/'.$uimg['img'].'" style="border:1px solid #ddd;padding:5px;max-width:100px"></td>
														<td class="tx-right">'.$urun['baslik'].'</td>
														<td class="tx-right">'.$secenek.'</td>
														<td class="tx-right">'.$row['adet'].'</td>
													</tr>';
												}
								}
							}
							?>

							<tr>
								<td class="valign-middle" colspan="2" rowspan="4"></td>
								<td class="text-uppercase font-weight-semibold">Toplam Tutar</td>
								<td class="tx-right" colspan="3">
									<h4 class="text-primary font-weight-bold"><?php echo fiyat($siparis['toplam_tutar']); ?> TL</h4>
								</td>
							</tr>
						</tbody>
					</table>
				</div>
				<div class="float-right">
					<form action="" method="post">
						<?php
							$k = $db->query("SELECT * FROM siparis_kargo_takip WHERE siparis_id = '{$_GET['no']}' LIMIT 1")->fetch(PDO::FETCH_ASSOC);

						?>
						<input type="text" class="form-control" name="kargo_adi" value="<?php echo @$k['kargo_adi']; ?>" style="margin-top:20px;margin-bottom:20px" placeholder="Kargo Adı">
						<input type="text" class="form-control" name="kargo_takip" value="<?php echo @$k['kargo_no']; ?>" style="margin-top:20px;margin-bottom:20px" placeholder="Kargo Takip Numarası">
						<select id="durum" name="durum" class="form-control" style="margin-top: 20px">
							<option value="0">Onay Bekleyen</option>
							<option value="1">Ödeme Bekleyen</option>
							<option value="2">Ödeme Alındı</option>
							<option value="3">Onaylanan</option>
							<option value="4">Kargoda</option>
							<option value="5">Tamamlanan</option>
							<option value="6">İptal Edilen</option>
						</select>
						<button type="submit" class="btn btn-info mt-4"> Sipariş Durumunu Değiştir</button>
					</form>
				</div>
			</div>
		</div>
	</div>
</div>
<script type="text/javascript">
	$(function(){


		$('#durum option[value="<?php echo $siparis['durum']; ?>"]').attr('selected','selected');

	});
</script>