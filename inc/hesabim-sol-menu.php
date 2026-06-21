<?php
$query = $db->prepare("SELECT * FROM kullanici where id=:id LIMIT 1");
$kullanici = $query->execute(array(":id"=>$_SESSION['kullanici']['id']));
$kullanici = $query->fetch(PDO::FETCH_ASSOC);
?>
<div class="col-md-3">
	<div class="bg2 border p20" style="float: left;width: 100%">
		<div class="bg3 mb-5 p5 border"><center><img src="assets/images/pp.png" class="img-responsive" style="width: 100px"><h4><i class="las la-user-check"></i> <?php echo $kullanici['ad'].' '.$kullanici['soyad']; ?></h4></center></div>
		<ul class="hesabim_menu">
			<li><a href="hesabim"><i class="las la-user-cog"></i> Hesap Ayarlarım</a></li>
			<li><a href="siparislerim"><i class="las la-cart-arrow-down"></i> Siparişlerim</a></li>
			<li><a href="urun-yorumlarim"><i class="las la-comment-dots"></i> Ürün Yorumlarım</a></li>
			<li><a href="cikis-yap"><i class="las la-sign-out-alt"></i> Çıkış Yap</a></li>
		</ul>
	</div>
</div>
<script type="text/javascript">
	$(function(){
		$('a[href="<?php echo $sayfa; ?>"]').addClass('aktif');
	});
</script>