<?php
$query = $db->prepare("SELECT * FROM turun where sef=:sef LIMIT 1");
$urun = $query->execute(array(":sef"=>$_GET['sef']));
$urun = $query->fetch(PDO::FETCH_ASSOC);

if(!$urun){
  echo '<meta http-equiv="refresh" content="0;URL=index.php">';
}

$_title         =  $urun['baslik'];
$_description   =  $urun['kisa_aciklama'];

$json_adi = uniqid().'.json';
?>
<link rel="stylesheet" type="text/css" href="panel/tasarla/css/FancyProductDesigner-all.min.css" />
<script src="panel/tasarla/js/jquery.min.js" type="text/javascript"></script>
<script src="panel/tasarla/js/jquery-ui.min.js" type="text/javascript"></script>
<script src="panel/tasarla/js/fabric.min.js" type="text/javascript"></script>
<script src="panel/tasarla/js/FancyProductDesigner-all.min.js" type="text/javascript"></script>

<script type="text/javascript">
jQuery(document).ready(function(){
    var $yourDesigner = $('#clothing-designer'),
    pluginOpts = {
        stageWidth: 1920,
        stageHeight: 1200,
        editorMode: true,
        smartGuides: true,
        //uiTheme: 'doyle',
        fonts: [
            {name: 'Helvetica'},
            {name: 'Times New Roman'},
            {name: 'Arial'},
            {name: 'Lobster', url: 'google'}
        ],
        customTextParameters: {
            colors: true,
            removable: true,
            resizable: true,
            draggable: true,
            rotatable: true,
            autoCenter: true,
            boundingBox: "Base",
            curvable: true
        },
        customImageParameters: {
            draggable: true,
            removable: true,
            resizable: true,
            rotatable: true,
            colors: '#000',
            autoCenter: true,
            boundingBox: "Base"
        },
        actions:  {
            'top': ['download','print', 'snap', 'preview-lightbox'],
            'right': ['magnify-glass', 'zoom', 'reset-product', 'qr-code', 'ruler'],
            'bottom': ['undo','redo'],
            'left': ['manage-layers','info','save','load']
        }
    },

    yourDesigner = new FancyProductDesigner($yourDesigner, pluginOpts);

    $.post("panel/tasarla/olustur.php", { action: 'load', json_adi: '<?php echo $urun['json']; ?>' }, function(data) {
        if(data != 0){
            yourDesigner.loadProduct(JSON.parse(data));
        }
    });


    $(function(){
        $('#sepeteekle').click(function() {
            yourDesigner.getProductDataURL(function(dataURL) {
                $.post( "panel/tasarla/php/save_image.php", { base64_image: dataURL} ).done(function( img ){
                    $('[name="img"]').val(img);
                    $.post("panel/tasarla/olustur.php", { action: 'store', json_adi: '<?php echo $json_adi; ?>', views: JSON.stringify(yourDesigner.getProduct()) }, function(data) {
                        if(parseInt(data) > 0) {

                            $('[data-sepete-ekle]').click();

                        }
                        else {
                            alert('Error: '+data+'');
                        }
                    });

                });
            });
        });

    });

});
</script>

<div data-sepete-ekle="<?php echo $urun['id']; ?>" style="display: none;">Gizli Ekle</div>
<input type="hidden" name="json" value="<?php echo $json_adi; ?>">
<input type="hidden" name="img" value="">

<main id="content" role="main">
    <!-- breadcrumb -->
    <div class="bg-gray-13 bg-md-transparent">
        <div class="container">
            <!-- breadcrumb -->
            <div class="my-md-3">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-3 flex-nowrap flex-xl-wrap overflow-auto overflow-xl-visble">
                        <li class="breadcrumb-item flex-shrink-0 flex-xl-shrink-1"><a href="index.php">Anasayfa</a></li>
                        <li class="breadcrumb-item flex-shrink-0 flex-xl-shrink-1 active" aria-current="page"><?php echo $urun['baslik']; ?></li>
                    </ol>
                </nav>
            </div>
            <!-- End breadcrumb -->
        </div>
    </div>
    <!-- End breadcrumb -->
    <div class="container">
    <!-- Single Product Body -->
    <div class="mb-xl-14 mb-6">
        <div class="row">
            <div class="col-md-8 mb-4 mb-md-0">
               
                <div id="clothing-designer" style="float:left" class="fpd-container fpd-shadow-2 fpd-topbar fpd-tabs fpd-tabs-side fpd-top-actions-centered fpd-bottom-actions-centered fpd-views-inside-left"> </div>

            </div>
            <div class="col-md-4 mb-md-6 mb-lg-0">
                <div class="mb-2">
                    <div class="border-bottom mb-3 pb-md-1 pb-3">
                        <h2 class="font-size-25 text-lh-1dot2"><?php echo $urun['baslik']; ?></h2>
                        <div class="mb-2">
                            <a class="d-inline-flex align-items-center small font-size-15 text-lh-1" href="#">
                                <div class="text-warning mr-2">
                                    <small class="fas fa-star"></small>
                                    <small class="fas fa-star"></small>
                                    <small class="fas fa-star"></small>
                                    <small class="fas fa-star"></small>
                                    <small class="fas fa-star"></small>
                                </div>
                            </a>
                        </div>
                        <div class="d-md-flex align-items-center">
                            <?php 
                            if($urun['marka_id'] != 0){
                                $marka = $db->query("SELECT * FROM marka WHERE id = '{$urun['marka_id']}' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
                            ?>
                            <a href="#" class="max-width-150 ml-n2 mb-2 mb-md-0 d-block"><img class="img-fluid" src="upload/<?php echo $marka['img']; ?>" alt=""></a>
                            <?php } ?>
                            <div class="ml-md-3 text-gray-9 font-size-14">Stok Durumu: <span class="text-green font-weight-bold"><?php echo $urun['stok'] > 0 ? 'Mevcut' : 'Tükendi'; ?></span></div>
                        </div>
                    </div>
                    <div class="mb-2">
                        <?php echo $urun['kisa_aciklama']; ?>
                    </div>
                    <?php
                    if (!function_exists('display_stock_code_without_eryaz_prefix')) {
                        function display_stock_code_without_eryaz_prefix($stokKodu) {
                            return preg_replace('/^(30-|31-|32-|3e-?)/i', '', trim((string)$stokKodu));
                        }
                    }
                    $display_stok_kodu = display_stock_code_without_eryaz_prefix(isset($urun['stok_kodu']) ? $urun['stok_kodu'] : '');
                    ?>
                    <p><strong>Stok Kodu</strong>: <?php echo htmlspecialchars($display_stok_kodu, ENT_QUOTES, 'UTF-8'); ?></p>
                    <div class="mb-4">
                        <div class="d-flex align-items-baseline">
                            <ins class="font-size-36 text-decoration-none" data-guncel-fiyat="<?php echo $urun['fiyat']; ?>"><?php echo fiyat($urun['fiyat']); ?> TL</ins>
                            <?php if(!empty($urun['eski_fiyat'])){ ?><del class="font-size-20 ml-2 text-gray-6"><?php echo fiyat($urun['eski_fiyat']); ?> TL</del><?php } ?>
                        </div>
                    </div>


                    <?php
                      $query = $db->query("SELECT * FROM turun_secenek WHERE urun_id = '{$urun['id']}' ", PDO::FETCH_ASSOC);
                      if($query->rowCount()){
                        foreach( $query as $row ){
                    ?>
                    <div class="row varyant" style="margin-bottom:20px">
                        <div class="col-md-12">
                            <strong><?php echo $row['baslik']; ?></strong>
                        </div>
                        <div class="col-md-12">
                            <ul>
                                <?php
                                  $query1 = $db->query("SELECT * FROM turun_secenek_alt WHERE urun_secenek_id = '{$row['id']}' ORDER BY id ASC", PDO::FETCH_ASSOC);
                                  if($query1->rowCount()){
                                    foreach( $query1 as $row1 ){
                                        echo '<li data-stok="'.$row1['stok'].'" data-fiyat="'.$row1['fiyat'].'" data-secenek-id="'.$row1['id'].'">'.$row1['baslik'].'</li>';
                                    }
                                  }
                                ?>
                            </ul>
                        </div>
                    </div>
                    <?php } } ?>

                    <div class="row">
                        <div id="sepete_ekle_durum"></div>
                    </div>

                    <div class="d-md-flex align-items-end mb-3">
                        <div class="max-width-150 mb-4 mb-md-0">
                            <!-- Quantity -->
                            <div class="border rounded-pill py-2 px-3 border-color-1">
                                <div class="js-quantity row align-items-center">
                                    <div class="col">
                                        <input class="js-result form-control h-auto border-0 rounded p-0 shadow-none" id="adet" name="adet" type="number" min="1" value="1">
                                    </div>
                                </div>
                            </div>
                            <!-- End Quantity -->
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                             <button class="btn px-5 btn-primary-dark transition-3d-hover" id="sepeteekle"><i class="ec ec-add-to-cart mr-2 font-size-20"></i> Sepete Ekle</button><br><br>
                            <a href="https://api.whatsapp.com/send?phone=9<?php echo $cek['whatsapp']; ?>&amp;text=<?php echo $site.'urun-tasarla/'.$urun['sef']; ?> Merhaba, bu ürünü sipariş vermek istiyorum." class="btn px-5 btn-success transition-3d-hover" data-sepete-ekle="<?php echo $urun['id']; ?>"><i class="ec ec-add-to-cart mr-2 font-size-20"></i> Whatsappp ile Sipariş</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End Single Product Body -->
    <!-- Single Product Tab -->
    <div class="mb-8">
        <div class="position-relative position-md-static px-md-6">
            <ul class="nav nav-classic nav-tab nav-tab-lg justify-content-xl-center flex-nowrap flex-xl-wrap overflow-auto overflow-xl-visble border-0 pb-1 pb-xl-0 mb-n1 mb-xl-0" id="pills-tab-8" role="tablist">
                <li class="nav-item flex-shrink-0 flex-xl-shrink-1 z-index-2">
                    <a class="nav-link active" id="Jpills-one-example1-tab" data-toggle="pill" href="#Jpills-one-example1" role="tab" aria-controls="Jpills-one-example1" aria-selected="true">Ürün Açıklaması</a>
                </li>
                <li class="nav-item flex-shrink-0 flex-xl-shrink-1 z-index-2">
                    <a class="nav-link" id="Jpills-three-example1-tab" data-toggle="pill" href="#Jpills-three-example1" role="tab" aria-controls="Jpills-three-example1" aria-selected="false">İade Koşulları</a>
                </li>
                <li class="nav-item flex-shrink-0 flex-xl-shrink-1 z-index-2">
                    <a class="nav-link" id="Jpills-four-example1-tab" data-toggle="pill" href="#Jpills-four-example1" role="tab" aria-controls="Jpills-four-example1" aria-selected="false">Ürün Yorumları</a>
                </li>
            </ul>
        </div>
        <!-- Tab Content -->
        <style type="text/css">
            #Jpills-one-example1 *{max-width: 85%;}
        </style>
        <div class="borders-radius-17 border p-4 mt-4 mt-md-0 px-lg-10 py-lg-9">
            <div class="tab-content" id="Jpills-tabContent">
                <div class="tab-pane fade active show" id="Jpills-one-example1" role="tabpanel" aria-labelledby="Jpills-one-example1-tab">
                    <?php echo $urun['aciklama']; ?>
                </div>
                <div class="tab-pane fade" id="Jpills-three-example1" role="tabpanel" aria-labelledby="Jpills-three-example1-tab">
                    <?php
                        $iade_kosullari = $db->query("SELECT * FROM sayfa WHERE sef = 'iade-kosullari' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
                        echo $iade_kosullari['aciklama'];
                    ?>
                </div>
                <div class="tab-pane fade" id="Jpills-four-example1" role="tabpanel" aria-labelledby="Jpills-four-example1-tab">
                    <?php if(isset($_SESSION['kullanici']['login'])){ ?>
                    <div class="row mb-8">
                        <div class="col-md-12">
                            <h3 class="font-size-18 mb-5">Yorum Ekle</h3>
                            <!-- Form -->
                            <form class="js-validate">
                                <div class="js-form-message form-group mb-3 row">
                                    <div class="col-md-4 col-lg-3">
                                        <label for="descriptionTextarea" class="form-label">Yorumunuz</label>
                                    </div>
                                    <div class="col-md-8 col-lg-9">
                                        <textarea class="form-control" name="yorum" rows="3" id="descriptionTextarea"
                                        data-msg="Please enter your message."
                                        data-error-class="u-has-error"
                                        data-success-class="u-has-success"></textarea>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="offset-md-4 offset-lg-3 col-auto">
                                        <button type="submit" class="btn btn-primary-dark btn-wide transition-3d-hover">Yorum Ekle</button>
                                    </div>
                                </div>
                            </form>
                            <!-- End Form -->
                        </div>
                    </div>
                    <?php } ?>
                    <!-- Review -->

                    <?php
                    $query = $db->query("
                            SELECT
                            kullanici.ad,
                            urun_yorum.yorum,
                            urun_yorum.tarih
                            FROM
                            urun_yorum
                            INNER JOIN kullanici ON urun_yorum.kullanici_id = kullanici.id
                            WHERE
                            urun_yorum.urun_id = '{$urun['id']}' AND
                            urun_yorum.durum = 1
                            ", PDO::FETCH_ASSOC);
                      if($query->rowCount()){
                        foreach($query as $row){
                        echo '
                        <div class="border-bottom border-color-1 pb-4 mb-4">
                            <p class="text-gray-90">'.$row['yorum'].'</p>
                            <div class="mb-2">
                                <strong>'.$row['ad'].'</strong>
                                <span class="font-size-13 text-gray-23">'.date('Y-m-d H:i', $row['tarih']).'</span>
                            </div>
                        </div>';
                        }
                    }else{
                        echo '
                        <div class="border-bottom border-color-1 pb-4 mb-4">
                            <p class="text-gray-90">Yorum bulunamadı.</p>
                        </div>';
                    } 
                ?>
                </div>
            </div>
        </div>
        <!-- End Tab Content -->
    </div>
    <!-- End Single Product Tab -->
    <!-- Related products -->
    <div class="mb-6">
        <div class="d-flex justify-content-between align-items-center border-bottom border-color-1 flex-lg-nowrap flex-wrap mb-4">
            <h3 class="section-title mb-0 pb-2 font-size-22">Önerilen Diğer Ürünler</h3>
        </div>
        <ul class="row list-unstyled products-group no-gutters">
            <?php
            $query = $db->query("SELECT
                                urun.id,
                                urun.baslik,
                                urun.sef,
                                urun.fiyat,
                                urun.eski_fiyat,
                                urun_img.img
                                FROM
                                urun
                                INNER JOIN urun_img ON urun.id = urun_img.urun_id
                                GROUP BY
                                urun_img.urun_id
                                ORDER BY RAND()
                                LIMIT 5
                      ", PDO::FETCH_ASSOC);
            if($query->rowCount()){
              foreach($query as $row){
          ?>
            <li class="col-6 col-md-3 col-xl-2gdot4-only col-wd-2 product-item">
                <?php include 'inc/urun-sabit.php'; ?>
            </li>
            <?php } } ?>
        </ul>
    </div>
    <!-- End Related products -->
    </div>
</main>