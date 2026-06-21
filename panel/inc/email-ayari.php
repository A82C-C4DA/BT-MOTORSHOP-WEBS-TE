<?php
if($_POST){
		
	if(!isset($_POST['ssl_'])){ $_POST['ssl_'] = 'tls'; }

    $islem = $db->prepare("UPDATE email_ayar SET host = ?, email = ?, password = ?, port = ?, ssl_ = ?");
    $islem = $islem->execute(array($_POST['host'],$_POST['email'],$_POST['password'], $_POST['port'],$_POST['ssl_']));


    if($islem){
        echo b();
    }else{
        echo h();
    }

}

$edit = $db->query("SELECT * FROM email_ayar LIMIT 1")->fetch(PDO::FETCH_ASSOC);
?>
<script type="text/javascript">
  $(function(){
    $('[name="ssl_"][value="<?php echo $edit['ssl_']; ?>"]').attr('checked','checked');
  });
</script>
<div class="breadcrumb-header justify-content-between">
	<div class="my-auto">
		<div class="d-flex">
			<h4 class="content-title mb-0 my-auto">E-mail Ayarı</h4><span class="text-muted mt-1 tx-13 ml-2 mb-0">/ Ekle - Düzenle</span>
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
			<form action="" method="post">
			<div class="panel-body">
	          <div class="form-group">
	            <label for="a1">Host</label>
	            <input type="text" class="form-control" id="a1" name="host" placeholder="Host" value="<?php echo @$edit['host']; ?>" data-message="Host Gerekli">
	          </div>
	          <div class="form-group">
	            <label for="a2">Email</label>
	            <input type="email" class="form-control" id="a2" name="email" placeholder="E-mail" value="<?php echo @$edit['email']; ?>" data-message="E-mail Gerekli">
	          </div>
	          <div class="form-group">
	            <label for="a3">Password</label>
	            <input type="text" class="form-control" id="a3" name="password" placeholder="Şifre" value="<?php echo @$edit['password']; ?>" data-message="Şifre Gerekli">
	          </div>
	          <div class="form-group">
	            <label for="a4">Port</label>
	            <input type="text" class="form-control" id="a4" name="port" placeholder="Port" value="<?php echo @$edit['port']; ?>" data-message="Port Gerekli">
	          </div>
	          <div class="form-check">
	            <input type="checkbox" class="form-check-input" id="a5" name="ssl_" value="ssl">
	            <label class="form-check-label" for="a5">SSL Kullan</label>
	          </div>
	          <div class="form-group">
	            <div class="row">
	              <div class="col-md-12">
	                <button class="btn btn-success">Kaydet</button>
	              </div>
	            </div>
	          </div>
	        </div>
	        </form>
		</div>
	</div>
</div>
