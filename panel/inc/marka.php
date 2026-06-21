<?php
if(isset($_GET['sil_id'])){
    $delete = $db->exec("DELETE FROM marka WHERE id = '{$_GET['sil_id']}' LIMIT 1");
    echo b();
}

if($_POST){
	
    if(isset($_GET['duzenle_id'])){
        $islem = $db->prepare("UPDATE marka SET baslik = ?, img = ? WHERE id = ?");
        $islem = $islem->execute(array($_POST['baslik'],$_POST['img1'],$_GET['duzenle_id']));
    }else{
        $islem = $db->prepare("INSERT INTO marka SET baslik = ?, img = ?");
        $islem = $islem->execute(array($_POST['baslik'],$_POST['img1']));
    }

    if($islem){
        echo b();
    }else{
        echo h();
    }

}

if(isset($_GET['duzenle_id'])){
    $duzenle = $db->query("SELECT * FROM marka WHERE id = '{$_GET['duzenle_id']}' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    ?>
	<script type="text/javascript">
		$(function(){
			<?php
             if($duzenle['img'] !='' AND is_file('../upload/'.$duzenle['img'])){
                ?>
                  $('.uploaddis[data-id="1"] .yuklendi img').attr('src','../upload/<?php echo $duzenle['img']; ?>');
                  $('.uploaddis[data-id="1"] input').val('<?php echo $duzenle['img']; ?>');
                  $('.uploaddis[data-id="1"]').removeClass('aktif');
                  $('.uploaddis[data-id="1"]').addClass('pasif');
                <?php
              }
            ?>
		});
	</script>
	<?php
}
?>
<div class="breadcrumb-header justify-content-between">
	<div class="my-auto">
		<div class="d-flex">
			<h4 class="content-title mb-0 my-auto">Marka</h4><span class="text-muted mt-1 tx-13 ml-2 mb-0">/ Ekle - Düzenle</span>
		</div>
	</div>
</div>

<div class="row">
	<div class="col-xl-6">
		<div class="card mg-b-20">
			<div class="card-header pb-0">
				<div class="d-flex justify-content-between">
					<h4 class="card-title mg-b-0">Eklenen Veriler</h4>
				</div>
			</div>
			<div class="card-body">
				<div class="table-responsive">
					<table id="example1" class="table table-striped table-bordered text-nowrap  mb-0">
						<thead>
							<tr>
								<th>ID</th>
								<th>Fotoğraf</th>
								<th>Adı</th>
								<th>İşlem</th>
							</tr>
						</thead>
						<tbody>
							<?php
		                      $query = $db->query("SELECT * FROM marka ORDER BY id DESC", PDO::FETCH_ASSOC);
		                      if($query->rowCount()){
		                        foreach( $query as $row ){
		                          echo '
		                            <tr>
		                              <td>'.$row['id'].'</td>
		                              <td><img src="../upload/'.$row['img'].'" style="max-width:100px;border:1px solid #ddd;padding:5px"></td>
		                              <td>'.$row['baslik'].'</td>
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
	<div class="col-lg-6 col-xl-6 col-md-12 col-sm-12">
		<div class="card  box-shadow-0">
			<div class="card-body pt-10">
					<form action="" method="post">
					<div class="form-group row">
						<div class="col-md-12">
							<input type="text" class="form-control" name="baslik" placeholder="Marka Adı" value="<?php echo @$duzenle['baslik']; ?>" required="">
						</div>
					</div>

					<div class="form-group row">
		                <div class="col-md-9">
		                    <div class="uploaddis aktif" data-id="1" style="float:left;">
		        			  <div class="yuklendi">
		        				  <img src="">
		        				  <div class="icon" data-id="1"><span class="fa fa-trash"></span></div>
		        				  <input type="hidden" name="img1" value="" required="">
		        			  </div>
		        			  <div class="upload">
		        				  <span class="metin" style="width: 100%;float: left;">Resim Yükle</span>
		        				  <div class="icon"><span class="fa fa-upload" data-id="1"></span></div>
		        			  </div>
		        			</div>
		                </div>
		            </div>
					<div class="row">
						<div class="col-md-12">
							<div class="form-group"><button type="submit" class="btn btn-success">Değişiklikleri Kaydet</button></div>
						</div>
					</div>

				</form>

			</div>
		</div>
	</div>
</div>

	

<div id="queue"></div>