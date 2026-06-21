<?php
$online = $db->query("SELECT count(id) as toplam FROM siparis  WHERE odeme_yontemi = 1 ")->fetch(PDO::FETCH_ASSOC);
$kapida_nakit = $db->query("SELECT count(id) as toplam FROM siparis  WHERE odeme_yontemi = 3 ")->fetch(PDO::FETCH_ASSOC);
$kapda_kredi = $db->query("SELECT count(id) as toplam FROM siparis  WHERE odeme_yontemi = 2 ")->fetch(PDO::FETCH_ASSOC);
$banka = $db->query("SELECT count(id) as toplam FROM siparis  WHERE odeme_yontemi = 4 ")->fetch(PDO::FETCH_ASSOC);



$bugun_baslangic = strtotime(date('Y-m-d'));
$bugun_bitis = strtotime(date('Y-m-d').' 23:59:00');

$bugun_alinan = $db->query("SELECT sum(toplam_tutar) as tutar FROM siparis  WHERE durum != 6 AND siparis_tarihi > '{$bugun_baslangic}' AND siparis_tarihi < '{$bugun_bitis}' ")->fetch(PDO::FETCH_ASSOC);


$strtotime = date("o-\WW");
$start = strtotime($strtotime);
$end = strtotime("+6 days 23:59:59", $start);
$buhafta_alinan = $db->query("SELECT sum(toplam_tutar) as tutar FROM siparis  WHERE durum != 6 AND siparis_tarihi > '{$start}' AND siparis_tarihi < '{$end}' ")->fetch(PDO::FETCH_ASSOC);

$month_ini = date("Y-m-d", mktime(0, 0, 0, date("m"), 1, date("Y", strtotime("-1 month"))));
$start = strtotime($month_ini);
$month_end = date("Y-m-d", mktime(0, 0, 0, date("m"), date("t", strtotime("+1 month")), date("Y", strtotime("+1 month"))));
$end = strtotime($month_end);
$buay_alinan = $db->query("SELECT sum(toplam_tutar) as tutar FROM siparis  WHERE durum != 6 AND siparis_tarihi BETWEEN '{$start}' AND '{$end}' ")->fetch(PDO::FETCH_ASSOC);


$toplam_tutar = $db->query("SELECT sum(toplam_tutar) as tutar FROM siparis  WHERE durum != 6 ")->fetch(PDO::FETCH_ASSOC);



?>
<!-- breadcrumb -->
<div class="breadcrumb-header justify-content-between">
	<div class="left-content">
		<div>
		  <h2 class="main-content-title tx-24 mg-b-1 mg-b-lg-1">Yönetim Paneline Hoşgeldin</h2>
		  <p class="mg-b-0">Bu Sayfada Genel Özeti Görebilirsiniz!</p>
		</div>
	</div>
	<div class="main-dashboard-header-right">
		<div>
			<label class="tx-13">Online Satış Sayısı</label>
			<h5><?php echo $online['toplam']; ?></h5>
		</div>
		<div>
			<label class="tx-13">Kapıda Nakit Satış Sayısı</label>
			<h5><?php echo $kapida_nakit['toplam']; ?></h5>
		</div>
		<div>
			<label class="tx-13">Kapıda Kredi Kartı Satış Sayısı</label>
			<h5><?php echo $kapda_kredi['toplam']; ?></h5>
		</div>
		<div>
			<label class="tx-13">Banka Havalesi Satış Sayısı</label>
			<h5><?php echo $banka['toplam']; ?></h5>
		</div>	
	</div>
</div>
<!-- /breadcrumb -->

<!-- row -->
<div class="row row-sm">
	<div class="col-md-3 col-xm-12">
		<div class="card overflow-hidden sales-card bg-primary-gradient">
			<div class="pl-3 pt-3 pr-3 pb-2 pt-0">
				<div class="">
					<h6 class="mb-3 tx-12 text-white">Bugün Toplam Satış</h6>
				</div>
				<div class="pb-0 mt-0">
					<div class="d-flex">
						<div class="">
							<h4 class="tx-20 font-weight-bold mb-1 text-white"><?php echo fiyat($bugun_alinan['tutar']); ?> TL</h4>
							<p class="mb-0 tx-12 text-white op-7">İptal Edilen Siparişler Hariçtir</p>
						</div>
					</div>
				</div>
			</div>
			<span id="compositeline" class="pt-1">5,9,5,6,4,12,18,14,10,15,12,5,8,5,12,5,12,10,16,12</span>
		</div>
	</div>
	<div class="col-md-3 col-xm-12">
		<div class="card overflow-hidden sales-card bg-danger-gradient">
			<div class="pl-3 pt-3 pr-3 pb-2 pt-0">
				<div class="">
					<h6 class="mb-3 tx-12 text-white">Bu Hafta Toplam Satış</h6>
				</div>
				<div class="pb-0 mt-0">
					<div class="d-flex">
						<div class="">
							<h4 class="tx-20 font-weight-bold mb-1 text-white"><?php echo fiyat($buhafta_alinan['tutar']); ?> TL</h4>
							<p class="mb-0 tx-12 text-white op-7">İptal Edilen Siparişler Hariçtir</p>
						</div>
					</div>
				</div>
			</div>
			<span id="compositeline2" class="pt-1">3,2,4,6,12,14,8,7,14,16,12,7,8,4,3,2,2,5,6,7</span>
		</div>
	</div>
	<div class="col-md-3 col-xm-12">
		<div class="card overflow-hidden sales-card bg-success-gradient">
			<div class="pl-3 pt-3 pr-3 pb-2 pt-0">
				<div class="">
					<h6 class="mb-3 tx-12 text-white">Bu Ay Toplam Satış</h6>
				</div>
				<div class="pb-0 mt-0">
					<div class="d-flex">
						<div class="">
							<h4 class="tx-20 font-weight-bold mb-1 text-white"><?php echo fiyat($buay_alinan['tutar']); ?> TL</h4>
							<p class="mb-0 tx-12 text-white op-7">İptal Edilen Siparişler Hariçtir</p>
						</div>
					</div>
				</div>
			</div>
			<span id="compositeline3" class="pt-1">5,10,5,20,22,12,15,18,20,15,8,12,22,5,10,12,22,15,16,10</span>
		</div>
	</div>
	<div class="col-md-3 col-xm-12">
		<div class="card overflow-hidden sales-card bg-warning-gradient">
			<div class="pl-3 pt-3 pr-3 pb-2 pt-0">
				<div class="">
					<h6 class="mb-3 tx-12 text-white">Genel Toplam Satış</h6>
				</div>
				<div class="pb-0 mt-0">
					<div class="d-flex">
						<div class="">
							<h4 class="tx-20 font-weight-bold mb-1 text-white"><?php echo fiyat($buay_alinan['tutar']); ?> TL</h4>
							<p class="mb-0 tx-12 text-white op-7">İptal Edilen Siparişler Hariçtir</p>
						</div>
					</div>
				</div>
			</div>
			<span id="compositeline4" class="pt-1">5,9,5,6,4,12,18,14,10,15,12,5,8,5,12,5,12,10,16,12</span>
		</div>
	</div>
</div>
<!-- row closed -->

<div class="row" style="margin-top: 20px">

	<div class="col-12">
		<div class="card">
			<div class="card-header">
				<div class="card-title"><?php echo $siparis_durum[0]; ?></div>
			</div>
			<div class="card-body">
				<div class="table-responsive">
					<table class="table table-bordered text-nowrap" id="example2">
						<thead>
							<tr>
								<th class="wd-15p border-bottom-0">ID</th>
								<th class="wd-15p border-bottom-0">Ad</th>
								<th class="wd-15p border-bottom-0">Soyad</th>
								<th class="wd-15p border-bottom-0">Telefon</th>
								<th class="wd-15p border-bottom-0">Email</th>
								<th class="wd-15p border-bottom-0">Referans Kodu</th>
								<th class="wd-15p border-bottom-0">Ödeme Yöntemi</th>
								<th class="wd-15p border-bottom-0">Toplam Tutar</th>
								<th class="wd-15p border-bottom-0">Sipariş Tarihi</th>
								<th class="wd-15p border-bottom-0">Adres</th>
								<th class="wd-15p border-bottom-0">İşlem</th>
							</tr>
						</thead>
						<tbody>
							<?php
		                      $query = $db->query("SELECT * FROM siparis WHERE durum = '0' ORDER BY id DESC", PDO::FETCH_ASSOC);
		                      if($query->rowCount()){
		                        foreach( $query as $row ){
		                          echo '
		                            <tr>
		                              <td>'.$row['id'].'</td>
		                              <td>'.$row['ad'].'</td>
		                              <td>'.$row['soyad'].'</td>
		                              <td>'.$row['telefon'].'</td>
		                              <td>'.$row['email'].'</td>
		                              <td>'.$row['siparis_key'].'</td>
		                              <td>'.$odeme_yontemi[$row['odeme_yontemi']].'</td>
		                              <td>'.$row['toplam_tutar'].' TL</td>
		                              <td>'.date('Y-m-d H:i:s',$row['siparis_tarihi']).'</td>
		                              <td>'.$row['adres'].'</td>
		                              <td>
		                                <a href="siparis-detay/'.$row['id'].'" class="badge badge-success" style="margin-right:10px">Düzenle</span>
		                              </td>
		                            </tr>
		                          ';
		                        }
		                      }else{
		                      	echo '<tr><td colspan="11"><center><h3>Veri Bulunamadı.</h3></center></td></tr>';
		                      }
		                    ?>
						</tbody>
					</table>
				</div>
			</div>
		</div>
	</div>

</div>


<div class="row" style="margin-top: 20px">

	<div class="col-12">
		<div class="card">
			<div class="card-header">
				<div class="card-title">Son Kayıt Olan Kullanıcılar</div>
			</div>
			<div class="card-body">
				<div class="table-responsive">
					<table class="table table-bordered text-nowrap" id="example2">
						<thead>
							<tr>
								<th class="border-bottom-0">ID </th>
								<th class="border-bottom-0">Ad</th>
								<th class="border-bottom-0">Soyad</th>
								<th class="border-bottom-0">Email</th>
								<th class="border-bottom-0">Telefon</th>
								<th class="border-bottom-0">Tc</th>
								<th class="border-bottom-0">Adres</th>
							</tr>
						</thead>
						<tbody>
							<?php
					                      
		                      $query = $db->query("SELECT * FROM kullanici ORDER BY id DESC LIMIT 10", PDO::FETCH_ASSOC);
		                      
		                      if($query->rowCount()){
		                        foreach( $query as $row ){

		                          echo '
		                            <tr>
		                              <td>'.$row['id'].'</td>
		                              <td>'.$row['ad'].'</td>
		                              <td>'.$row['soyad'].'</td>
		                              <td>'.$row['email'].'</td>
		                              <td>'.$row['telefon'].'</td>
		                              <td>'.$row['tc'].'</td>
		                              <td>'.$row['adres'].'</td>
		                            </tr>
		                          ';
		                        }
		                      }else{
		                      	echo '<tr><td colspan="8"><center><h2>Kullanıcı bulunamadı...</h2></center></td></tr>';
		                      }
		                    ?>
						</tbody>
					</table>
				</div>
			</div>
		</div>
	</div>

</div>

<script src="assets/plugins/jquery-sparkline/jquery.sparkline.min.js"></script>
<script src="assets/js/index.js"></script>
