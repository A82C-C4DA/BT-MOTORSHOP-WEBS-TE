<?php
if(isset($_GET['sil_id'])){
    $delete = $db->exec("DELETE FROM turun WHERE id = '{$_GET['sil_id']}' LIMIT 1");

    $delete = $db->exec("DELETE FROM turun_kategori WHERE urun_id = '{$_GET['sil_id']}' ");
    $delete = $db->exec("DELETE FROM turun_img WHERE urun_id = '{$_GET['sil_id']}'");
    $delete = $db->exec("DELETE FROM vitrin_urun1 WHERE urun_id = '{$_GET['sil_id']}'");

    $query = $db->query("SELECT * FROM turun_secenek WHERE urun_id = '{$_GET['sil_id']}' ", PDO::FETCH_ASSOC);
	 if($query->rowCount()){
	    foreach( $query as $row ){

	    	$delete = $db->exec("DELETE FROM turun_secenek_alt WHERE urun_secenek_id = '{$row['id']}'");
	    	$delete = $db->exec("DELETE FROM turun_secenek WHERE id = '{$row['id']}'");

	    }
	 }
    echo b();
}


$sorgusay = $db->prepare("SELECT count(id) FROM turun");
$sorgusay->execute();
$say = $sorgusay->fetchColumn();

$top_sayfa = $say;
$page      = $_GET['no'];
$limit     = 10;
$page_url  = $sayfa.'/';
$baslangic = ($page * $limit) - $limit;
?>

<div class="breadcrumb-header justify-content-between">
	<div class="my-auto">
		<div class="d-flex">
			<h4 class="content-title mb-0 my-auto">Ürünler</h4><span class="text-muted mt-1 tx-13 ml-2 mb-0">/ Listele</span>
		</div>
	</div>
</div>

<div class="row">
	<div class="col-md-12">
		<div class="card">
			<div class="card-body">
				<div class="row">
					<div class="col-md-12">
						<div class="order-table">
							<div class="table-responsive">
								<form action="" method="post" style="margin-bottom: 30px">
									<div class="row">
										<div class="col-md-6">&nbsp;</div>
										<div class="col-md-4">
											<input type="text" name="ara" placeholder="Ürün adı ile arayın" class="form-control">
										</div>
										<div class="col-md-2">
											<button type="submit" class="btn btn-success" style="width: 100%">Ürün Ara</button>
										</div>
									</div>
								</form>
								<table id="example1" class="table table-striped table-bordered text-nowrap  mb-0">
									<thead>
										<tr class="bold border-bottom">
											<th class="border-bottom-0">ID </th>
											<th class="border-bottom-0">Fotoğraf </th>
											<th class="border-bottom-0">Ürün Adı </th>
											<th class="border-bottom-0">Fiyatı </th>
											<th class="border-bottom-0">Link </th>
											<th class="border-bottom-0">İşlem</th>
										</tr>
									</thead>
									<tbody>
										<?php
										  if($_POST){
										  	$query = $db->query("SELECT * FROM turun WHERE baslik LIKE '%{$_POST['ara']}%' ", PDO::FETCH_ASSOC);
										  }else{
					                      	$query = $db->query("SELECT * FROM turun LIMIT {$baslangic},{$limit}", PDO::FETCH_ASSOC);
					                      }
					                      if($query->rowCount()){
					                        foreach( $query as $row ){

					                        $img = $db->query("SELECT * FROM turun_img WHERE urun_id = '{$row['id']}' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
					                          echo '
					                            <tr>
					                              <td>'.$row['id'].'</td>
					                              <td><img src="../upload/'.$img['img'].'" style="max-width:100px;border:1px solid #ddd;padding:5px"></td>
					                              <td>'.$row['baslik'].'</td>
					                              <td>'.fiyat($row['fiyat']).' TL</td>
					                              <td><a href="'.$site.'tasarla/'.$row['sef'].'">Ürüne gitmek için tıklayın...</a></td>
					                              <td>
					                              	<a href="turun/duzenle/'.$row['id'].'" data-toggle="tooltip" data-original-title="Düzenle"><svg class="svg-icon mr-2" xmlns="http://www.w3.org/2000/svg" height="24" viewBox="0 0 24 24" width="24"><path d="M0 0h24v24H0V0z" fill="none"/><path d="M14.06 9.02l.92.92L5.92 19H5v-.92l9.06-9.06M17.66 3c-.25 0-.51.1-.7.29l-1.83 1.83 3.75 3.75 1.83-1.83c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.2-.2-.45-.29-.71-.29zm-3.6 3.19L3 17.25V21h3.75L17.81 9.94l-3.75-3.75z"/></svg></a>
													<a href="'.$sayfa.'/sil/'.$row['id'].'" data-toggle="tooltip" data-original-title="Sil"><svg class="svg-icon" xmlns="http://www.w3.org/2000/svg" height="24" viewBox="0 0 24 24" width="24"><path d="M0 0h24v24H0V0z" fill="none"/><path d="M8 9h8v10H8z" opacity=".3"/><path d="M15.5 4l-1-1h-5l-1 1H5v2h14V4zM6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM8 9h8v10H8V9z"/></svg></a>
					                              </td>
					                            </tr>
					                          ';
					                        }
					                      }else{
					                      	echo '<tr><td colspan="6"><center><h2>Ürün bulunamadı.</h2></center></td></tr>';
					                      }
					                    ?>
									</tbody>
								</table>
							</div>
						</div>
						<div class="pagination-wrapper" style="margin-bottom: 10px;float: right;">
							<nav aria-label="Page navigation">
								<ul class="pagination mb-0">
									<?php Sayfala($top_sayfa,$page,$limit,$page_url); ?>
								</ul>
							</nav>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>



