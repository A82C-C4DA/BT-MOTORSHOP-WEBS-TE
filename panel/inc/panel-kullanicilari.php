<?php
if(isset($_GET['sil_id'])){
    $delete = $db->exec("DELETE FROM panel_kullanicilari WHERE id = '{$_GET['sil_id']}' LIMIT 1");
    echo b();
}

if($_POST){
	
    if(isset($_GET['duzenle_id'])){
        $islem = $db->prepare("UPDATE panel_kullanicilari SET kullanici_adi = ?, sifre = ? WHERE id = ?");
        $islem = $islem->execute(array($_POST['kullanici_adi'],$_POST['sifre'],$_GET['duzenle_id']));
    }else{
        $islem = $db->prepare("INSERT INTO panel_kullanicilari SET kullanici_adi = ?, sifre = ?");
        $islem = $islem->execute(array($_POST['kullanici_adi'],$_POST['sifre']));
    }

    if($islem){
        echo b();
    }else{
        echo h();
    }

}

if(isset($_GET['duzenle_id'])){
    $duzenle = $db->query("SELECT * FROM panel_kullanicilari WHERE id = '{$_GET['duzenle_id']}' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
}


?>
<div class="breadcrumb-header justify-content-between">
	<div class="my-auto">
		<div class="d-flex">
			<h4 class="content-title mb-0 my-auto">Panel Kullanıcıları</h4><span class="text-muted mt-1 tx-13 ml-2 mb-0">/ Ekle - Düzenle</span>
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
											<th class="border-bottom-0">Kullanıcı Adı </th>
											<th class="border-bottom-0">Şifre </th>
											<th class="border-bottom-0">İşlem</th>
										</tr>
									</thead>
									<tbody>
										<?php
					                      $query = $db->query("SELECT * FROM panel_kullanicilari", PDO::FETCH_ASSOC);
					                      if($query->rowCount()){
					                        foreach( $query as $row ){
					                          echo '
					                            <tr>
					                              <td>'.$row['id'].'</td>
					                              <td>'.$row['kullanici_adi'].'</td>
					                              <td>'.$row['sifre'].'</td>
					                              <td>
					                              	<a href="'.$sayfa.'/duzenle/'.$row['id'].'" data-toggle="tooltip" data-original-title="Düzenle"><svg class="svg-icon mr-2" xmlns="http://www.w3.org/2000/svg" height="24" viewBox="0 0 24 24" width="24"><path d="M0 0h24v24H0V0z" fill="none"/><path d="M14.06 9.02l.92.92L5.92 19H5v-.92l9.06-9.06M17.66 3c-.25 0-.51.1-.7.29l-1.83 1.83 3.75 3.75 1.83-1.83c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.2-.2-.45-.29-.71-.29zm-3.6 3.19L3 17.25V21h3.75L17.81 9.94l-3.75-3.75z"/></svg></a>
													<a href="'.$sayfa.'/sil/'.$row['id'].'" data-toggle="tooltip" data-original-title="Sil"><svg class="svg-icon" xmlns="http://www.w3.org/2000/svg" height="24" viewBox="0 0 24 24" width="24"><path d="M0 0h24v24H0V0z" fill="none"/><path d="M8 9h8v10H8z" opacity=".3"/><path d="M15.5 4l-1-1h-5l-1 1H5v2h14V4zM6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM8 9h8v10H8V9z"/></svg></a>
					                              </td>
					                            </tr>
					                          ';
					                        }
					                      }else{
					                      	echo '<tr><td colspan="4"><center><h3>Veri Bulunamadı.</h3></center></td></tr>';
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
	<div class="col-md-6">
		<div class="card">
			<div class="card-body">
				<form action="" method="post">
					<div class="form-group row">
						<label class="col-md-3 form-label">Kullanıcı Adı</label>
						<div class="col-md-9">
							<input type="text" class="form-control" name="kullanici_adi" value="<?php echo @$duzenle['kullanici_adi']; ?>" required="">
						</div>
					</div>
					<div class="form-group row">
						<label class="col-md-3 form-label">Şifre</label>
						<div class="col-md-9">
							<input type="text" class="form-control" name="sifre" value="<?php echo @$duzenle['sifre']; ?>" required="">
						</div>
					</div>
					<div class="form-group row">
						<div class="col-md-12"><center><button type="submit" class="btn btn-success">Değişiklikleri Kaydet</button></center></div>
					</div>

				</form>
			</div>
		</div>
	</div>
</div>



