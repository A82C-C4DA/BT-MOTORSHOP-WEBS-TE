<?php
if(isset($_GET['sil_id'])){
    $delete = $db->exec("DELETE FROM vitrin1 WHERE id = '{$_GET['sil_id']}' LIMIT 1");
    echo b();
}

if($_POST AND !isset($_POST['siralama'])){
	
 
    if(isset($_GET['duzenle_id'])){
        $islem = $db->prepare("UPDATE vitrin1 SET baslik = ?, img = ?, link = ? WHERE id = ?");
        $islem = $islem->execute(array($_POST['baslik'],$_POST['img1'],$_POST['link'],$_GET['duzenle_id']));
    }else{
        $islem = $db->prepare("INSERT INTO vitrin1 SET baslik = ?, img = ?, sira = ?, link = ?");
        $islem = $islem->execute(array($_POST['baslik'],$_POST['img1'],999,$_POST['link']));
    }

    if($islem){
        echo b();
    }else{
        echo h();
    }

}



if($_POST AND isset($_POST['siralama'])){

	$siralama = str_replace('\"', '"', $_POST['siralama']);
	$data = json_decode($siralama);
	$readbleArray = parseJsonArray($data);
	$i = 1;
	foreach($readbleArray as $row){
		$islem = $db->prepare("UPDATE vitrin1 SET sira = ? WHERE id = ?");
		$islem = $islem->execute(array($i,$row['id']));
		$i++;
	}
	echo b();

}

if(isset($_GET['duzenle_id'])){
    $duzenle = $db->query("SELECT * FROM vitrin1 WHERE id = '{$_GET['duzenle_id']}' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
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
			<h4 class="content-title mb-0 my-auto">Tasarla Vitrin</h4><span class="text-muted mt-1 tx-13 ml-2 mb-0">/ Ekle - Düzenle</span>
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
						<?php 
							$cek = $db->query("SELECT * FROM vitrin1 ORDER BY sira ASC", PDO::FETCH_ASSOC);
							if($cek->rowCount()){
								?>
								<form action="" method="post">
									<div class="dd" id="nestable" style="float: left;width: 100%">
							            <ol class="dd-list">
							                <?php
										        foreach ($cek as $ak) {
									              echo '<li data-id="'.$ak['id'].'" class="dd-item">
									                      <div class="dd-handle">
									                      	#'.$ak['id'].' - '.$ak['baslik'].'
									                      </div>
									                      	<a href="'.$sayfa.'/sil/'.$ak['id'].'" class="sil" style="float:right;color:red">Sil</span>
					                    					<a href="'.$sayfa.'/duzenle/'.$ak['id'].'" class="duzenle" style="float:right;color:green;margin-right:20px">Düzenle</a>
					                    					<a href="vitrin-urun-ekle1/'.$ak['id'].'" class="duzenle" style="float:right;color:green;margin-right:100px">Ürün Ekle</a>
									                    </li>';
									            }
									        ?>
							            </ol>
							        </div>
							        <input type="hidden" name="siralama" id="nestable-output">
									<div class="col-md-12">
										<button type="submit" class="btn btn-success" style="float: right;">Kaydet <i class="fa fa-check"></i></button>
									</div>
								</form>
								<?php
							}else{
		                      	echo '<center><h3>Veri Bulunamadı.</h3></center>';
		                    }
						?>
					</div>
				</div>
			</div>
		</div>
	</div>
	<div class="col-md-6">
		<div class="card">
			<div class="card-body">
				<form action="" method="post">
					<div class="col-lg-12 col-md-12">
						<div class="form-group row">
						  <label class="col-sm-3 form-label">Vitrin  Başlığı</label>
						  <div class="col-sm-9">
							<input type="text" class="form-control" name="baslik" value="<?php echo @$duzenle['baslik']; ?>" required="">
						  </div>
						</div>
						<div class="form-group row">
						  <label class="col-sm-3 form-label">Fotoğraf Linki</label>
						  <div class="col-sm-9">
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
			        				  <span class="metin" style="width: 100%;float: left;">Resim Yükle</span>
			        				  <div class="icon"><span class="fa fa-upload" data-id="1"></span></div>
			        			  </div>
			        			</div>
			                </div>
			            </div>
			            <div class="row">
							<div class="col-md-12">
								<div class="form-group"><center><button type="submit" class="btn btn-success">Değişiklikleri Kaydet</button></center></div>
							</div>
						</div>
					</div>
				</form>
			</div>
		</div>
	</div>
</div>

<div id="queue"></div>



<script type="text/javascript" src="assets/js/jquery.nestable.js"></script>
<link rel="stylesheet" type="text/css" href="assets/css/nestable.css">
<script>
$(document).ready(function(){

    var updateOutput = function(e){
        var list   = e.length ? e : $(e.target),
            output = list.data('output');
        if (window.JSON) {
            output.val(window.JSON.stringify(list.nestable('serialize')));
        } else {
            output.val('JSON browser support required for this demo.');
        }
    };

    $('#nestable').nestable({
        group: 1
    }).on('change', updateOutput);

    updateOutput($('#nestable').data('output', $('#nestable-output')));

});
</script>