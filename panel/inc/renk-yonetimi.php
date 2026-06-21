<?php
if($_POST){
	
        $islem = $db->prepare("UPDATE ayar SET renk1 = ?, renk2 = ? ");
        $islem = $islem->execute(array($_POST['renk1'],$_POST['renk2']));
        echo b();
   
}
$ayar = $db->query("SELECT * FROM ayar LIMIT 1")->fetch(PDO::FETCH_ASSOC);

?>



<script src="assets/colorpalet/html5kellycolorpicker.min.js"></script>
<link href="assets/colorpalet/common.css" rel="stylesheet">

<div class="breadcrumb-header justify-content-between">
	<div class="my-auto">
		<div class="d-flex">
			<h4 class="content-title mb-0 my-auto">Renk Yönetimi</h4><span class="text-muted mt-1 tx-13 ml-2 mb-0">/ Ekle - Düzenle</span>
		</div>
	</div>
</div>
<div class="row">
	<div class="col-md-6">
		<div class="card">
			<div class="card-header pb-0">
				<div class="d-flex justify-content-between">
					<h4 class="card-title mg-b-0">Düzenle</h4>
				</div>
			</div>
			<div class="card-body">
				<div class="row">
					<div class="col-md-12">
						<form class="form-horizontal" method="post" action="">



							<div class="form-group row">
								<label class="col-md-3 form-label">Site Rengi 1</label>
								<div class="col-md-9">
									
									<div class="row">
										<div class="col-md-5">
											<canvas id="picker"></canvas>
										</div>
										<div class="col-md-7" style="padding-top: 50px">
											<input id="color" class="form-control" name="renk1" type="text" value="<?php echo @$ayar['renk1']; ?>" required="">
										</div>
							            <script>
							                new KellyColorPicker({place : 'picker', input : 'color', size : 150});
							            </script>
							        </div>
									
								</div>
							</div>

							<div class="form-group row">
								<label class="col-md-3 form-label">Site Rengi 2</label>
								<div class="col-md-9">
									
									<div class="row">
										<div class="col-md-5">
											<canvas id="picker1"></canvas>
										</div>
										<div class="col-md-7" style="padding-top: 50px">
											<input id="color1" class="form-control" name="renk2" type="text" value="<?php echo @$ayar['renk2']; ?>" required="">
										</div>
							            <script>
							                new KellyColorPicker({place : 'picker1', input : 'color1', size : 150});
							            </script>
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

