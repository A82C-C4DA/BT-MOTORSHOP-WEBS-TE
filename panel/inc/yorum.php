
<?php
if($_POST){
	
    $islem = $db->prepare("UPDATE urun_yorum SET durum = ? WHERE id = ?");
    $islem = $islem->execute(array($_POST['durum'],$_POST['id']));

    if($islem){
        echo b();
    }else{
        echo h();
    }

}
?>
<br><br><br>
<div class="page-header">
	<div class="page-leftheader">
		<h4 class="page-title">Yorumlar Siparişleri</h4>
	</div>
</div>

<div class="row">
	<div class="col-md-12">
		<div class="card">
			<div class="card-body">
				<div class="row">
					<div class="col-md-12">
						<div class="order-table">
							<form action="" method="post">
							<div class="table-responsive">
								<table id="example1" class="table table-striped table-bordered text-nowrap  mb-0">
									<thead>
										<tr class="bold border-bottom">
											<th class="border-bottom-0">Kullanıcı </th>
											<th class="border-bottom-0">Ürün Adı </th>
											<th class="border-bottom-0">Yorum </th>
											<th class="border-bottom-0">Tarih</th>
											<th class="border-bottom-0">Yorum Durumu</th>
											<th class="border-bottom-0">Durumu Değiştir</th>
										</tr>
									</thead>
									<tbody>
										<?php
					                      
					                      $query = $db->query("SELECT * FROM urun_yorum WHERE durum = '{$_GET['no']}'", PDO::FETCH_ASSOC);
					                      if($query->rowCount()){
					                        foreach( $query as $row ){

					                        	 $k = $db->query("SELECT * FROM kullanici WHERE id = '{$row['kullanici_id']}' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
					                        	 $u = $db->query("SELECT * FROM urun WHERE id = '{$row['urun_id']}' LIMIT 1")->fetch(PDO::FETCH_ASSOC);

						                          echo '
						                            <tr>
						                              <td>'.$k['ad'].' '.$k['soyad'].'</td>
						                              <td>#'.$u['id'].' '.$u['baslik'].'</td>
						                              <td>'.$row['yorum'].'</td>
						                              <td>'.date('Y-m-d H:i:s', $row['tarih']).'</td>
						                              <td>'.$yorum_durum[$row['durum']].'</td>
						                              <td>
						                              	<form action="" method="post">
							                              	<select class="form-control" name="durum" required>
							                              		<option value="">Seçim Yapınız</option>
							                              		<option value="0">Onay Bekleyen</option>
							                              		<option value="1">Onaylandı</option>
							                              	</select>
							                              	<input type="hidden" value="'.$row['id'].'" name="id">
							                              	<button type="submit" class="btn btn-info">Değiştir</button>
						                              	</form>
						                              </td>
						                            </tr>
						                          ';
					                        }
					                      }else{
					                      	echo '<tr><td colspan="6"><center><h2>Yorum bulunamadı...</h2></center></td></tr>';
					                      }
					                    ?>
									</tbody>
								</table>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>