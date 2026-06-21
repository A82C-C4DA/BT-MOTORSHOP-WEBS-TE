<?php
if(!isset($_SESSION['kullanici']['login'])){
    die('<meta http-equiv="refresh" content="0;URL=index.php">');
}
$_title         =  'Ürün Yorumlarım';

?>
<main id="content" role="main">
<div class="container">
	<div class="row mt-20 mb-20">
		
		<?php
			include 'inc/hesabim-sol-menu.php';
		?>


		<div class="col-md-6">
			
			<div class="bg2 border p20" style="float: left;width: 100%">
				<h2>Ürün Yorumlarım</h2>
				<?php
			      $query = $db->query("SELECT * FROM urun_yorum WHERE kullanici_id = '{$_SESSION['kullanici']['id']}'", PDO::FETCH_ASSOC);
			      if($query->rowCount()){
			        foreach( $query as $row ){

			        	$urun = $db->query("SELECT * FROM urun WHERE id = '{$row['urun_id']}' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
			        	$img = $db->query("SELECT * FROM urun_img WHERE urun_id = '{$row['urun_id']}' LIMIT 1")->fetch(PDO::FETCH_ASSOC);

			        	echo '<div class="row mb-20 border-b pb-10">
			        			<div class="col-md-3"><img src="upload/'.$img['img'].'" class="img-responsive"></div>
			        			<div><b>Ürün:</b> <a href="urun/'.$urun['sef'].'">'.$urun['baslik'].'</a><br><b>Yorumunuz:</b> '.$row['yorum'].' <br><small>'.date('Y-m-d H:i:s',$row['tarih']).'</small></div>
			        		  </div>';
			        }
			      }else{
			      	echo '<center><h3>Ürün yorumunuz bulunmuyor</h3></center>';
			      }
			    ?>

			</div>
		</div>


	</div>
</div>
</main>

<?php include 'inc/sabit-css.php'; ?>