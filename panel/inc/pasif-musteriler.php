<?php

if($_POST){

	if(isset($_POST['id'])){
		foreach ($_POST['id'] as $id) {
			$islem = $db->prepare("UPDATE kullanici SET aktif = ? WHERE id = ?");
        	$islem = $islem->execute(array(1,$id));
		}
		if($islem){
	        echo b();
	    }else{
	        echo h();
	    }
	}

}
?>
<br><br><br>
<div class="page-header">
	<div class="page-leftheader">
		<h4 class="page-title">Kullanıcıları Aktif Et</h4>
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
								<table id="example1" class="table table-striped table-bordered text-nowrap  mb-0">
									<thead>
										<tr class="bold border-bottom">
											<th class="border-bottom-0">ID </th>
											<th class="border-bottom-0">Ad</th>
											<th class="border-bottom-0">Soyad</th>
											<th class="border-bottom-0">Email</th>
											<th class="border-bottom-0">Telefon</th>
											<th class="border-bottom-0">Tc</th>
											<th class="border-bottom-0">Adres</th>
											<th class="border-bottom-0">İşlem</th>
										</tr>
									</thead>
									<tbody>
										<?php
					                      
					                      $query = $db->query("SELECT * FROM kullanici WHERE aktif = 0", PDO::FETCH_ASSOC);
					                      
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
					                              <td>
					                              	<div class="col-md-9 custom-controls-stacked">
														<label class="custom-control custom-checkbox">
															<input type="checkbox" class="custom-control-input" name="id[]" value="'.$row['id'].'">
															<span class="custom-control-label">Seç</span>
														</label>
													</div>
					                              </td>
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
							<br>
							<button type="submit" class="btn btn-success pull-right">Aktif Et</button>
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