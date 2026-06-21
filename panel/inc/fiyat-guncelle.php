<?php

if($_POST){

	if(isset($_POST['kategori_id'])){
		$ids = '0';
		foreach ($_POST['kategori_id'] as $id) {
			$ids = $id.','.$ids;
		}


		$q1 = $db->query("SELECT urun_id FROM urun_kategori WHERE kategori_id IN('{$ids}') GROUP BY urun_id", PDO::FETCH_ASSOC);
		if($q1->rowCount()){
			foreach ($q1 as $q) {


				$u = $db->query("SELECT fiyat FROM urun WHERE id = '{$q['urun_id']}' LIMIT 1")->fetch(PDO::FETCH_ASSOC);

				if($_POST['tip'] == 1){

					$yenifiyat = $u['fiyat'] + $_POST['fiyat'];

					if(!empty($u['eski_fiyat'])){
						$yenifiyat1 = $u['eski_fiyat'] + $_POST['fiyat'];
					}

				}else if($_POST['tip'] == 2){

					$yenifiyat = $u['fiyat'] + (($u['fiyat'] * $_POST['fiyat'])  / 100);

					if(!empty($u['eski_fiyat'])){
						$yenifiyat1 = $u['eski_fiyat'] + (($u['eski_fiyat'] * $_POST['fiyat'])  / 100);
					}


				}else if($_POST['tip'] == 3){

					$yenifiyat = $u['fiyat'] - $_POST['fiyat'];

					if(!empty($u['eski_fiyat'])){
						$yenifiyat1 = $u['eski_fiyat'] - $_POST['fiyat'];
					}

				}else if($_POST['tip'] == 4){

					$yenifiyat = $u['fiyat'] - (($u['fiyat'] * $_POST['fiyat'])  / 100);

					if(!empty($u['eski_fiyat'])){
						$yenifiyat1 = $u['eski_fiyat'] - (($u['eski_fiyat'] * $_POST['fiyat'])  / 100);
					}

				}


				if(!empty($u['eski_fiyat'])){
					$islem = $db->prepare("UPDATE urun SET eski_fiyat = ? WHERE id = ?");
        			$islem = $islem->execute(array($yenifiyat1,$q['urun_id']));
				}


				$islem = $db->prepare("UPDATE urun SET fiyat = ? WHERE id = ?");
        		$islem = $islem->execute(array($yenifiyat,$q['urun_id']));

			}
		}


		if($islem){
	        echo b();
	    }else{
	        echo h();
	    }
	}

}
?>

<div class="page-header">
	<div class="page-leftheader">
		<h4 class="page-title"> Ücreti Değiştir</h4>
	</div>
</div>

<div class="row">
	<div class="col-md-12">
		<div class="card">
			<div class="card-body">
				<div class="row">
					<div class="col-md-12">
						<button class="btn btn-success pull-right" id="tumunu_sec">Tümünü Seç</button><br><br>
						<div class="order-table">
							<form action="" method="post">
							<div class="table-responsive">
								<table  class="table table-striped table-bordered text-nowrap  mb-0">
									<thead>
										<tr class="bold border-bottom">
											<th class="border-bottom-0">ID </th>
											<th class="border-bottom-0">Kategori Adı </th>
											<th class="border-bottom-0">İşlem</th>
										</tr>
									</thead>
									<tbody>
										<?php
					                      
					                      $query = $db->query("SELECT * FROM kategori", PDO::FETCH_ASSOC);
					                      
					                      if($query->rowCount()){
					                        foreach( $query as $row ){

					                          echo '
					                            <tr>
					                              <td>'.$row['id'].'</td>
					                              <td>'.$row['baslik'].'</td>
					                              <td>
					                              	<div class="col-md-9 custom-controls-stacked">
														<label class="custom-control custom-checkbox">
															<input type="checkbox" class="custom-control-input" name="kategori_id[]" value="'.$row['id'].'">
															<span class="custom-control-label">Seç</span>
														</label>
													</div>
					                              </td>
					                            </tr>
					                          ';
					                        }
					                      }else{
					                      	echo '<tr><td colspan="5"><center><h2>Ürün bulunamadı...</h2></center></td></tr>';
					                      }
					                    ?>
									</tbody>
								</table>
							</div>
							<br>
							<div class="row">
								<div class="col-md-4">
									<select  name="tip" class="form-control">
										<option value="1">+</option>
										<option value="2">%+</option>
										<option value="3">-</option>
										<option value="4">-%</option>
									</select>
								</div>
								<div class="col-md-4">
									
									<input type="text" class="form-control" name="fiyat" placeholder="Yeni Fiyat"  required="" style="width: 150px;float: right;margin-right: 10px">
								</div>
								<div class="col-md-4"><button type="submit" class="btn btn-success pull-right">Değiştir</button></div>
							</div>
							
							
							
							</form>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>



<script type="text/javascript">
	$(function(){
		$('#tumunu_sec').click(function(){
			$('input[type="checkbox"]').each(function(){
				$(this).attr('checked','checked');
			});
		});
	});
</script>