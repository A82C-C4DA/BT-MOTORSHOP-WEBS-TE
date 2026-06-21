<?php
if(isset($_GET['sil_id'])){
    $delete = $db->exec("DELETE FROM haftanin_urunleri WHERE urun_id = '{$_GET['sil_id']}' LIMIT 1");
    echo b();
}

if($_POST AND !isset($_POST['siralama'])){
	
    $islem = $db->prepare("INSERT INTO haftanin_urunleri SET urun_id = ?, sira = ?");
    $islem = $islem->execute(array($_POST['urun_id'],9999)); 
 
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
		$islem = $db->prepare("UPDATE haftanin_urunleri SET sira = ? WHERE id = ?");
		$islem = $islem->execute(array($i,$row['id']));
		$i++;
	}
	echo b();

}
?>

<div class="breadcrumb-header justify-content-between">
	<div class="my-auto">
		<div class="d-flex">
			<h4 class="content-title mb-0 my-auto">Haftanın Ürünleri</h4><span class="text-muted mt-1 tx-13 ml-2 mb-0">/ Ekle - Düzenle</span>
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
							$cek = $db->query("SELECT * FROM haftanin_urunleri ORDER BY sira ASC", PDO::FETCH_ASSOC);
							if($cek->rowCount()){
								?>
								<form action="" method="post">
									<div class="dd" id="nestable" style="float: left;width: 100%">
							            <ol class="dd-list">
							                <?php
										        foreach ($cek as $ak) {
										          $u = $db->query("SELECT * FROM urun WHERE id = '{$ak['urun_id']}' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
									              echo '<li data-id="'.$ak['id'].'" class="dd-item">
									                      <div class="dd-handle">
									                      	#'.$u['id'].' - '.$u['baslik'].'
									                      </div>
									                      	<a href="'.$sayfa.'/sil/'.$u['id'].'" class="sil" style="float:right;color:red">Sil</span></a>
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
								echo '<center><h3>Veri Bulunamadı...</h3></center>';
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
						  <label class="col-sm-3 form-label">Ürünler</label>
						  <div class="col-sm-9">
							<select class="form-control select2v1" name="urun_id" required="">
								<?php
									$cek = $db->query("SELECT * FROM urun", PDO::FETCH_ASSOC);
									if($cek->rowCount()){
										foreach( $cek as $c ){
											echo '<option value="'.$c['id'].'">#'.$c['id'].' - '.$c['baslik'].'</option>';
										}
									}
								?>
							</select>
						  </div>
						</div>

			            <div class="col-md-12">
							<div class="form-group"><center><button type="submit" class="btn btn-success">Değişiklikleri Kaydet</button></center></div>
						</div>
					</div>
				</form>
			</div>
		</div>
	</div>
</div>



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
