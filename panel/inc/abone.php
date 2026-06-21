<?php
if(isset($_GET['sil_id'])){
    $delete = $db->exec("DELETE FROM abone WHERE id = '{$_GET['sil_id']}' LIMIT 1");
    echo b();
}

?>
<div class="breadcrumb-header justify-content-between">
	<div class="my-auto">
		<div class="d-flex">
			<h4 class="content-title mb-0 my-auto">Aboneler</h4><span class="text-muted mt-1 tx-13 ml-2 mb-0">/ Ekle - Düzenle</span>
		</div>
	</div>
</div>

	<div class="row">
		<div class="col-md-6">
			<div class="card">
				<div class="card-header pb-0">
					<div class="d-flex justify-content-between">
						<h4 class="card-title mg-b-0">Eklenen Veriler</h4>
					</div>
				</div>
				<div class="card-body">
					<div class="row">
						<div class="col-md-12">
							<div class="order-table">
								<div class="table-responsive">
									<table id="example1" class="table table-striped table-bordered text-nowrap  mb-0">
										<thead>
											<tr class="bold border-bottom">
												<th class="border-bottom-0">ID </th>
												<th class="border-bottom-0">Email </th>
												<th class="border-bottom-0">İşlem</th>
											</tr>
										</thead>
										<tbody>
											<?php
						                      $query = $db->query("SELECT * FROM abone", PDO::FETCH_ASSOC);
						                      if($query->rowCount()){
						                        foreach( $query as $row ){
						                          echo '
						                            <tr>
						                              <td>'.$row['id'].'</td>
						                              <td>'.$row['email'].'</td>
						                              <td>
														<a href="'.$sayfa.'/sil/'.$row['id'].'" data-toggle="tooltip" data-original-title="Sil"><svg class="svg-icon" xmlns="http://www.w3.org/2000/svg" height="24" viewBox="0 0 24 24" width="24"><path d="M0 0h24v24H0V0z" fill="none"/><path d="M8 9h8v10H8z" opacity=".3"/><path d="M15.5 4l-1-1h-5l-1 1H5v2h14V4zM6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM8 9h8v10H8V9z"/></svg></a>
						                              </td>
						                            </tr>
						                          ';
						                        }
						                      }else{
						                      	echo '<tr><td colspan="3"><center><h3>Veri Bulunamadı.</h3></center></td></tr>';
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




		
