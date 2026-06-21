<?php

if(isset($_GET['sil_id'])){
    $delete = $db->exec("DELETE FROM siparis WHERE id = '{$_GET['sil_id']}' LIMIT 1");
    $delete = $db->exec("DELETE FROM siparis_urun WHERE siparis_id = '{$_GET['sil_id']}' LIMIT 1");
    echo b();
}

?>
<div class="row" style="margin-top: 20px">

	<div class="col-12">
		<div class="card">
			<div class="card-header">
				<div class="card-title"><?php echo $siparis_durum[$_GET['no']]; ?></div>
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
		                      $query = $db->query("SELECT * FROM siparis WHERE durum = '{$_GET['no']}' ORDER BY id DESC", PDO::FETCH_ASSOC);
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
		                                <a href="siparis/'.$_GET['no'].'/'.$row['id'].'" class="badge badge-danger" style="margin-right:10px">Sil</span>
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