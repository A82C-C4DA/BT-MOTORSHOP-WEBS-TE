<?php

if(isset($_GET['sil_id'])){
    $delete = $db->exec("DELETE FROM hikaye WHERE id = '{$_GET['sil_id']}' LIMIT 1");
    echo b();
}

if($_POST){
	
    if(isset($_GET['duzenle_id'])){
        $islem = $db->prepare("UPDATE hikaye SET link = ?, kucuk_img = ?, buyuk_img = ?, baslik = ? WHERE id = ?");
        $islem = $islem->execute(array($_POST['link'],$_POST['img1'],$_POST['img2'],$_POST['baslik'],$_GET['duzenle_id']));
    }else{
        $islem = $db->prepare("INSERT INTO hikaye SET link = ?, kucuk_img = ?, buyuk_img = ?, baslik = ?");
        $islem = $islem->execute(array($_POST['link'],$_POST['img1'],$_POST['img2'],$_POST['baslik']));
    }

    if($islem){
        echo b();
    }else{
        echo h();
    }

}

if(isset($_GET['duzenle_id'])){
    $duzenle = $db->query("SELECT * FROM hikaye WHERE id = '{$_GET['duzenle_id']}' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    ?>
	<script type="text/javascript">
		$(function(){
			<?php
             if($duzenle['kucuk_img'] !='' AND is_file('../upload/'.$duzenle['kucuk_img'])){
                ?>
                  $('.uploaddis[data-id="1"] .yuklendi img').attr('src','../upload/<?php echo $duzenle['kucuk_img']; ?>');
                  $('.uploaddis[data-id="1"] input').val('<?php echo $duzenle['kucuk_img']; ?>');
                  $('.uploaddis[data-id="1"]').removeClass('aktif');
                  $('.uploaddis[data-id="1"]').addClass('pasif');
                <?php
              }
              if($duzenle['buyuk_img'] !='' AND is_file('../upload/'.$duzenle['buyuk_img'])){
                ?>
                  $('.uploaddis[data-id="2"] .yuklendi img').attr('src','../upload/<?php echo $duzenle['buyuk_img']; ?>');
                  $('.uploaddis[data-id="2"] input').val('<?php echo $duzenle['buyuk_img']; ?>');
                  $('.uploaddis[data-id="2"]').removeClass('aktif');
                  $('.uploaddis[data-id="2"]').addClass('pasif');
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
			<h4 class="content-title mb-0 my-auto">Hikaye</h4><span class="text-muted mt-1 tx-13 ml-2 mb-0">/ Ekle - Düzenle</span>
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
						<div class="table-responsive">
							<table class="table table-bordered text-nowrap" id="example2">
								<thead>
									<tr>
										<th class="wd-15p border-bottom-0">ID</th>
										<th class="wd-15p border-bottom-0">Küçük Fotoğraf</th>
										<th class="wd-15p border-bottom-0">Büyük Fotoğraf</th>
										<th class="wd-15p border-bottom-0">İşlem</th>
									</tr>
								</thead>
								<tbody>
									<?php
				                      $query = $db->query("SELECT * FROM hikaye ORDER BY id DESC", PDO::FETCH_ASSOC);
				                      if($query->rowCount()){
				                        foreach( $query as $row ){
				                          echo '
				                            <tr>
				                              <td>'.$row['id'].'</td>
				                              <td><img src="../upload/'.$row['kucuk_img'].'"></td>
				                              <td><img src="../upload/'.$row['buyuk_img'].'"></td>
				                              <td>
				                                <a href="'.$sayfa.'/duzenle/'.$row['id'].'" class="badge badge-success" style="margin-right:10px">Düzenle</span>
				                                <a href="'.$sayfa.'/sil/'.$row['id'].'" class="badge badge-danger" style="margin-right:10px">Sil</span>
				                              </td>
				                            </tr>
				                          ';
				                        }
				                      }else{
				                      	echo '<tr><td colspan="4"><center><h3>Veri bulunamadı</h3></center></td></tr>';
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
	<div class="col-md-6">
		<div class="card">
			<div class="card-body">
				<div class="row">
					<div class="col-md-12">
						
						<form class="form-horizontal" method="post" action="">

							<label style="color: red">Büyük Görseli Yüklemezseniz Hikaye Direkt Linke Yönlendirir</label>

							<div class="form-group row">
								<label class="col-md-3 form-label">Başlık</label>
								<div class="col-md-9">
									<input type="text" class="form-control" name="baslik" value="<?php echo @$duzenle['baslik']; ?>" required="">
								</div>
							</div>

							<div class="form-group row">
								<label class="col-md-3 form-label">Link</label>
								<div class="col-md-9">
									<input type="text" class="form-control" name="link" value="<?php echo @$duzenle['link']; ?>" required="">
								</div>
							</div>

							<div class="form-group row">
				                <div class="col-md-3"></div>
				                <div class="col-md-9">
				                    <div class="uploaddis aktif" data-id="1" style="float:left;">
				        			  <div class="yuklendi">
				        				  <img src="">
				        				  <div class="icon" data-id="1"><span class="fa fa-trash"></span></div>
				        				  <input type="hidden" name="img1" value="" required="">
				        			  </div>
				        			  <div class="upload">
				        				  <span class="metin" style="width: 100%;float: left;">Küçük Resim Yükle</span>
				        				  <div class="icon"><span class="fa fa-upload" data-id="1"></span></div>
				        			  </div>
				        			</div>
				                </div>
				            </div>

				            <div class="form-group row">
				                <div class="col-md-3"></div>
				                <div class="col-md-9">
				                    <div class="uploaddis aktif" data-id="2" style="float:left;">
				        			  <div class="yuklendi">
				        				  <img src="">
				        				  <div class="icon" data-id="2"><span class="fa fa-trash"></span></div>
				        				  <input type="hidden" name="img2" value="" required="">
				        			  </div>
				        			  <div class="upload">
				        				  <span class="metin" style="width: 100%;float: left;">Büyük Resim Yükle</span>
				        				  <div class="icon"><span class="fa fa-upload" data-id="2"></span></div>
				        			  </div>
				        			</div>
				                </div>
				            </div>

							<div class="form-group row">
								<div class="col-md-12"><center><button type="submit" class="btn btn-info">Kaydet</button></center></div>
							</div>


						</form>

					</div>
				</div>
			</div>
		</div>
	</div>
</div>
<div id="queue"></div>