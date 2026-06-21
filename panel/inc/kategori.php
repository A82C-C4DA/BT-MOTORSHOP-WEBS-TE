<?php
if(isset($_GET['sil_id'])){
    $ids = alt_kategori_bul($_GET['sil_id']).$_GET['sil_id'];
    $delete = $db->exec("DELETE FROM kategori WHERE id IN ({$ids})");
    echo b();
}

if($_POST AND !isset($_POST['siralama'])){

	if(!isset($_POST['ust_menu'])){ $_POST['ust_menu'] = 0; }
	if(!isset($_POST['alt_menu'])){ $_POST['alt_menu'] = 0; }
	
    if(isset($_GET['duzenle_id'])){
        $islem = $db->prepare("UPDATE kategori SET baslik = ?, sef = ?, ust_kategori = ?, ust_menu = ?, alt_menu = ?, aciklama = ?, kisa_aciklama = ?, sira = ? WHERE id = ?");
        $islem = $islem->execute(array($_POST['baslik'],'',$_POST['ust_kategori'],$_POST['ust_menu'],$_POST['alt_menu'],$_POST['aciklama'],$_POST['kisa_aciklama'],9999,$_GET['duzenle_id']));
        $id = $_GET['duzenle_id'];
    }else{
        $islem = $db->prepare("INSERT INTO kategori SET baslik = ?, sef = ?, ust_kategori = ?, ust_menu = ?, alt_menu = ?, aciklama = ?, kisa_aciklama = ?, sira = ?");
        $islem = $islem->execute(array($_POST['baslik'],'',$_POST['ust_kategori'],$_POST['ust_menu'],$_POST['alt_menu'],$_POST['aciklama'],$_POST['kisa_aciklama'],9999));
        $id = $db->lastInsertId();
    }
    if($islem){
    	$sef = sef($_POST['baslik']).'-'.$id;
    	$islem = $db->prepare("UPDATE kategori SET sef = ? WHERE id = ?");
    	$islem = $islem->execute(array($sef,$id));
        echo b();
    }else{
        echo h();
    }
    ?>
	<iframe src="../sitemap-olustur.php" style="width: 1px;height: 1px;"></iframe>
	<?php
}

if(isset($_GET['duzenle_id'])){
    $duzenle = $db->query("SELECT * FROM kategori WHERE id = '{$_GET['duzenle_id']}' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    ?>
	<script type="text/javascript">
		$(function(){
			$('select[name="ust_kategori"] option[value="<?php echo $duzenle['ust_kategori']; ?>"]').attr('selected','select');
			$('[name="ust_menu"][value="<?php echo $duzenle['ust_menu']; ?>"]').attr('checked','checked');
			$('[name="alt_menu"][value="<?php echo $duzenle['alt_menu']; ?>"]').attr('checked','checked');
		});
	</script>
	<?php
}


if($_POST AND isset($_POST['siralama'])){

	$siralama = str_replace('\"', '"', $_POST['siralama']);
	$data = json_decode($siralama);
	$readbleArray = parseJsonArray($data);
	$i = 1;
	foreach($readbleArray as $row){
		$islem = $db->prepare("UPDATE kategori SET sira = ?, ust_kategori = ? WHERE id = ?");
		$islem = $islem->execute(array($i,$row['parentID'],$row['id']));
		$i++;
	}
	echo b();

}
?>
<div class="breadcrumb-header justify-content-between">
	<div class="my-auto">
		<div class="d-flex">
			<h4 class="content-title mb-0 my-auto">Kategori</h4><span class="text-muted mt-1 tx-13 ml-2 mb-0">/ Ekle - Düzenle</span>
		</div>
	</div>
</div>

<div class="row">
	<div class="col-xl-6">
		<div class="card mg-b-20">
			<div class="card-header pb-0">
				<div class="d-flex justify-content-between">
					<h4 class="card-title mg-b-0">Eklenen Veriler</h4>
					<div>
						<button type="button" class="btn btn-info btn-sm" id="aiSortCategories" style="margin-right: 10px;">
							<i class="fas fa-robot"></i> AI ile Sırala
						</button>
					</div>
				</div>
			</div>
			<div class="card-body">
				<?php 
					$cek = $db->query("SELECT * FROM kategori WHERE ust_kategori = 0 ORDER BY sira ASC", PDO::FETCH_ASSOC);
					if($cek->rowCount()){
						?>
						<form action="" method="post">
							<div class="dd" id="nestable" style="float: left;width: 100%">
					            <ol class="dd-list">
					                
					                <?php
							            function alt_eleman($x,$sayfa){
						            	  global $db;
							              $out = '';
							              $cek = $db->query("SELECT id, baslik FROM kategori WHERE ust_kategori = '{$x}' ORDER BY sira ASC ", PDO::FETCH_ASSOC);
										  if($cek->rowCount()){

							                $out.='<ol style="" class="dd-list">';
							                foreach ($cek as $v) {
							                  $out.='<li data-id="'.$v['id'].'" class="dd-item">
							                          <div class="dd-handle">
							                          #'.$v['id'].' - '.$v['baslik'].'
							                          </div>
							                          	<a href="'.$sayfa.'/sil/'.$v['id'].'" class="sil" style="float:right;color:red">Sil</span>
		                    							<a href="'.$sayfa.'/duzenle/'.$v['id'].'" class="duzenle" style="float:right;color:green;margin-right:20px">Düzenle</a>
							                         '.alt_eleman($v['id'],$sayfa);
							                }
							                $out.='</ol>';
							              }
							              return $out;
							            }

								        foreach ($cek as $ak) {
							              echo '<li data-id="'.$ak['id'].'" class="dd-item">
							                      <div class="dd-handle">
							                      	#'.$ak['id'].' - '.$ak['baslik'].'
							                      </div>
							                      	<a href="'.$sayfa.'/sil/'.$ak['id'].'" class="sil" style="float:right;color:red">Sil</span>
		                    						<a href="'.$sayfa.'/duzenle/'.$ak['id'].'" class="duzenle" style="float:right;color:green;margin-right:20px">Düzenle</a>
							                      '.alt_eleman($ak['id'],$sayfa).'
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
	<div class="col-lg-6 col-xl-6 col-md-12 col-sm-12">
		<div class="card  box-shadow-0">
			<div class="card-body pt-10">
				<form action="" method="post">
					<div class="col-lg-12 col-md-12">
						<div class="form-group row">
						  <label class="col-sm-3 form-label">Üst Kategori</label>
						  <div class="col-sm-9">
							<select class="form-control select2v1" name="ust_kategori" required="">
								<option value="0">Anakategori</option>
								<?php
									$cek = $db->query("SELECT * FROM kategori", PDO::FETCH_ASSOC);
									if($cek->rowCount()){
										foreach( $cek as $c ){
											echo '<option value="'.$c['id'].'">#'.$c['id'].' - '.$c['baslik'].'</option>';
										}
									}
								?>
							</select>
						  </div>
						</div>
						<div class="form-group row">
							<label class="col-md-3 form-label">Kategori Adı</label>
							<div class="col-md-9">
								<input type="text" class="form-control" name="baslik" required="" value="<?php echo @$duzenle['baslik']; ?>">
							</div>
						</div>
						<div class="form-group row">
							<label class="col-md-3 form-label">Kısa Açıklama<br><small>(Description)</small></label>
							<div class="col-md-9">
								<textarea class="form-control" name="kisa_aciklama"><?php echo @$duzenle['kisa_aciklama']; ?></textarea>
							</div>
						</div>
						<div class="form-group row">
							<div class="col-md-3 form-label">Menü Yönetimi</div>
							<div class="col-md-9 custom-controls-stacked">
								<label class="custom-control custom-checkbox">
									<input type="checkbox" class="custom-control-input" name="ust_menu" value="1">
									<span class="custom-control-label">Üst Menüde Görünsün</span>
								</label>
								<label class="custom-control custom-checkbox">
									<input type="checkbox" class="custom-control-input" name="alt_menu" value="1">
									<span class="custom-control-label">Alt Menüde Görünsün</span>
								</label>
							</div>
						</div>

						<div class="form-group row">
							<div class="col-md-12 form-label">Açıklama</div>
							<div class="col-md-12">
								<textarea class="form-control content" rows="3" name="aciklama"><?php echo @$duzenle['aciklama']; ?></textarea>
							</div>
						</div>
						<div class="form-group row">
			                <div class="col-md-3">
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
							<label class="col-md-9 form-label">
								<input type="text" class="form-control" id="url" name="" value="" placeholder="Resim Url Adresi">
							</label>
			            </div>
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


<link href="assets/plugins/wysiwyag/richtext.css" rel="stylesheet" />
<script src="assets/plugins/wysiwyag/jquery.richtext.js"></script>
<script src="assets/js/form-editor.js"></script>


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

    // AI ile sıralama
    $('#aiSortCategories').on('click', function() {
        if (!confirm('Kategoriler AI tarafından otomatik olarak sıralanacak. Devam etmek istiyor musunuz?')) {
            return;
        }
        
        var btn = $(this);
        var originalText = btn.html();
        btn.prop('disabled', true);
        btn.html('<i class="fas fa-spinner fa-spin"></i> Sıralanıyor...');
        
        $.ajax({
            url: 'inc/ai-handler.php',
            method: 'POST',
            data: {
                message: 'Kategorileri mantıklı bir sıraya göre düzenle'
            },
            dataType: 'json',
            success: function(response) {
                btn.prop('disabled', false);
                btn.html(originalText);
                
                if (response.success) {
                    // Başarı mesajı göster
                    alert(response.message);
                    // Sayfayı yenile
                    setTimeout(function() {
                        location.reload();
                    }, 1000);
                } else {
                    alert('Hata: ' + (response.error || 'Bilinmeyen hata'));
                }
            },
            error: function() {
                btn.prop('disabled', false);
                btn.html(originalText);
                alert('Bağlantı hatası oluştu. Lütfen tekrar deneyin.');
            }
        });
    });

});
</script>