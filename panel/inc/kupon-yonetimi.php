<?php

$kupon_indirim_turu[1] = '%';
$kupon_indirim_turu[2] = '-';

if(isset($_GET['sil_id'])){
    $delete = $db->exec("DELETE FROM kupon WHERE id = '{$_GET['sil_id']}' LIMIT 1");
    echo b();
}

if($_POST){

    if(isset($_GET['duzenle_id'])){
        $islem = $db->prepare("UPDATE kupon SET kod = ?, tutar = ?, indirim_turu = ? WHERE id = ?");
        $islem = $islem->execute(array($_POST['kod'],$_POST['tutar'],$_POST['indirim_turu'],$_GET['duzenle_id']));
    }else{
        $islem = $db->prepare("INSERT INTO kupon SET kod = ?, tutar = ?, indirim_turu = ?");
        $islem = $islem->execute(array($_POST['kod'],$_POST['tutar'],$_POST['indirim_turu']));
    }
    if($islem){
        echo b();
    }else{
        echo h();
    }
}

if(isset($_GET['duzenle_id'])){
    $duzenle = $db->query("SELECT * FROM kupon WHERE id = '{$_GET['duzenle_id']}' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    ?>
      <script type="text/javascript">
        $(function(){
        	$('[name="indirim_turu"] option[value="<?php echo $duzenle['indirim_turu']; ?>"]').attr('selected','selected');
        });
      </script>
    <?php
}
?>
<div class="page-header">
	<div class="page-leftheader">
		<h4 class="page-title">Kupon Yönetimi</h4>
	</div>
	<div class="page-rightheader ml-auto d-lg-flex d-none">
		<ol class="breadcrumb">
			<li class="breadcrumb-item"><a href="index.php">Anasayfa</a></li>
			<li class="breadcrumb-item active" aria-current="page">Kupon Yönetimi</li>
		</ol>
	</div>
</div>
						
<div class="row">
	<div class="col-md-6">
		<div class="card">
			<div class="card-body">
				<div class="row">
					<div class="col-md-12">
						
						<div class="table-responsive">
							<table class="table table-bordered text-nowrap" id="example2">
								<thead>
									<tr>
										<th class="wd-15p border-bottom-0">ID</th>
										<th class="wd-15p border-bottom-0">Kod</th>
										<th class="wd-15p border-bottom-0">Tutar</th>
										<th class="wd-15p border-bottom-0">Tür</th>
										<th class="wd-15p border-bottom-0">İşlem</th>
									</tr>
								</thead>
								<tbody>
									<?php
				                      $query = $db->query("SELECT * FROM kupon", PDO::FETCH_ASSOC);
				                      if($query->rowCount()){
				                        foreach( $query as $row ){
				                          echo '
				                            <tr>
				                              <td>'.$row['id'].'</td>
				                              <td>'.$row['kod'].'</td>
				                              <td>'.$row['tutar'].'</td>
				                              <td>'.$kupon_indirim_turu[$row['indirim_turu']].'</td>
				                              <td>
				                                <a href="'.$sayfa.'/duzenle/'.$row['id'].'" class="badge badge-success" style="margin-right:10px">Güncelle</span>
				                                <a href="'.$sayfa.'/sil/'.$row['id'].'" class="badge badge-danger">Sil</span>
				                              </td>
				                            </tr>
				                          ';
				                        }
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

							<div class="form-group row">
								<label class="col-md-3 form-label">Kupon Kodu</label>
								<div class="col-md-9">
									<input type="text" class="form-control" name="kod" value="<?php echo @$duzenle['kod']; ?>" required="">
								</div>
							</div>

							<div class="form-group row">
								<label class="col-md-3 form-label">Tutar</label>
								<div class="col-md-9">
									<input type="text" class="form-control" name="tutar" value="<?php echo @$duzenle['tutar']; ?>" required="">
								</div>
							</div>

							<div class="form-group row">
								<label class="col-md-3 form-label">İndirim Türü</label>
								<div class="col-md-9">
									<select name="indirim_turu" class="form-control">
										<option value="1">%</option>
										<option value="2">-</option>
									</select>
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
