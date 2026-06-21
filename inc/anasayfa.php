<div class="modal fade" id="hikaye-popup" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document" style="width: fit-content;">
    <div class="modal-content">
      <div id="saniye" style="width: 0px;height: 10px;background: #f12870;float: left;"></div>
      <div class="modal-body" style="padding: 0px">
        <div class="row">
          <div class="col-md-12" id="icerik"></div>
        </div>
      </div>
    </div>
  </div>
</div>
<!-- ========== MAIN CONTENT ========== -->
<main id="content" role="main">
    <!-- Slider Section -->
    <div class="mb-4">
        <div class="slider-container-3264x1312">
            <div class="js-slick-carousel u-slick slider-main"
                data-pagi-classes="text-center position-absolute right-0 bottom-0 left-0 u-slick__pagination u-slick__pagination--long justify-content-center mb-3">
                <?php
                  $query = $db->query("SELECT * FROM slider ORDER BY sira ASC", PDO::FETCH_ASSOC);
                  if($query->rowCount()){
                    foreach($query as $row){
                ?>
                <div class="js-slide">
                    <div class="slider-slide-wrapper">
                        <img class="slider-image" src="upload/<?php echo $row['img']; ?>" alt="<?php echo htmlspecialchars($row['aciklama']); ?>">
                        <div class="slider-overlay">
                            <div class="container">
                                <div class="row align-items-center h-100">
                                    <div class="col-12 col-md-8">
                                        <h1 class="slider-title"
                                            data-scs-animation-in="fadeInUp"
                                            data-scs-animation-delay="200">
                                           <strong class="font-weight-bold"><?php echo $row['aciklama']; ?></strong>
                                        </h1>
                                        <a href="<?php echo $row['link']; ?>" class="btn btn-primary transition-3d-hover rounded-lg py-2 px-md-7 px-3 font-size-16 slider-button"
                                            data-scs-animation-in="fadeInUp"
                                            data-scs-animation-delay="300">
                                            Detaylı İncele
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php } } ?>
            </div>
        </div>
    </div>
    <!-- End Slider Section -->
    <style>
    /* Slider 3264x1312 Oranı */
    .slider-container-3264x1312 {
        position: relative;
        width: 100%;
        padding-top: 40.196%; /* 1312/3264 = 0.40196 = 40.196% (aspect ratio) */
        overflow: hidden;
        background-color: #000;
    }
    
    .slider-container-3264x1312 .js-slick-carousel {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
    }
    
    .slider-slide-wrapper {
        position: relative;
        width: 100%;
        height: 100%;
    }
    
    .slider-image {
        width: 100%;
        height: 100%;
        object-fit: cover;
        object-position: center;
        display: block;
    }
    
    .slider-overlay {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: linear-gradient(to right, rgba(0,0,0,0.5) 0%, rgba(0,0,0,0.2) 50%, transparent 100%);
        display: flex;
        align-items: center;
    }
    
    .slider-title {
        font-size: 2.5rem;
        line-height: 1.2;
        font-weight: 300;
        color: #fff;
        margin-bottom: 1.5rem;
        text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
    }
    
    .slider-button {
        box-shadow: 0 4px 8px rgba(0,0,0,0.3);
    }
    
    @media (max-width: 768px) {
        .slider-title {
            font-size: 1.75rem;
        }
        
        .slider-overlay {
            background: linear-gradient(to bottom, rgba(0,0,0,0.6) 0%, transparent 100%);
        }
    }
    </style>
    <div class="container">
        <div class="row">
            <div class="col-xl-12 col-wd-auto">
                <!-- Banner -->
               <?php
              $query = $db->query("SELECT * FROM hikaye ORDER BY id DESC", PDO::FETCH_ASSOC);
              if($query->rowCount()){
              ?>
              <div class="row">
                <div class="col-md-12">
                  <div id="hikaye" class="js-slick-carousel u-slick position-static overflow-hidden u-slick-overflow-visble pb-7 pt-2 px-1"
                                data-pagi-classes="text-center right-0 bottom-1 left-0 u-slick__pagination u-slick__pagination--long mb-0 z-index-n1 mt-3 mt-md-0"
                                data-slides-show="5"
                                data-slides-scroll="1"
                                data-arrows-classes="position-absolute top-0 font-size-17 u-slick__arrow-normal top-10"
                                data-arrow-left-classes="fa fa-angle-left right-1"
                                data-arrow-right-classes="fa fa-angle-right right-0"
                                data-responsive='[{
                                  "breakpoint": 2000,
                                  "settings": {
                                    "slidesToShow": 10
                                  }
                                },{
                                  "breakpoint": 1400,
                                  "settings": {
                                    "slidesToShow": 10
                                  }
                                }, {
                                    "breakpoint": 1200,
                                    "settings": {
                                      "slidesToShow": 10
                                    }
                                }, {
                                  "breakpoint": 992,
                                  "settings": {
                                    "slidesToShow": 7
                                  }
                                }, {
                                  "breakpoint": 768,
                                  "settings": {
                                    "slidesToShow": 4
                                  }
                                }, {
                                  "breakpoint": 554,
                                  "settings": {
                                    "slidesToShow": 4
                                  }
                                }]'>
                    <?php
                          foreach($query as $row){
                            echo '<div class="item"><a href="javascript:void(0)" data-buyuk-img="'.$row['buyuk_img'].'" data-link="'.$row['link'].'"><img src="upload/'.$row['kucuk_img'].'"><span>'.$row['baslik'].'</span></a></div>';
                          }
                      ?>
                  </div>
                </div>
              </div>
              <?php } ?>

                <div class="row mb-6">
                    <?php
                      $query = $db->query("SELECT * FROM kampanya WHERE alt_ust = 0 ORDER BY sira ASC", PDO::FETCH_ASSOC);
                      if($query->rowCount()){
                        foreach($query as $row){
                          echo '<div class="col-md-3 col-xs-6"><a href="'.$row['link'].'"><img style="max-width:100%;border-radius:10px;border:1px solid #ddd;background:#ddd;padding:5px" alt="" src="upload/'.$row['img'].'"></a></div>';
                        }
                      }
                    ?>
                </div>
                <!-- End Banner -->

                <?php
                  // vitrin1 (Ürün Tasarla kategorisi) bölümü kaldırıldı
                ?>

                <?php
                  // Belirtilen başlıkları SQL sorgusunda filtrele (kısmi eşleşme)
                  $query = $db->query("SELECT * FROM vitrin 
                    WHERE LOWER(baslik) NOT LIKE '%en çok satanlar%' 
                    AND LOWER(baslik) NOT LIKE '%çok satanlar%'
                    AND LOWER(baslik) NOT LIKE '%popüler ürünler%'
                    AND LOWER(baslik) NOT LIKE '%popüler%'
                    AND LOWER(baslik) NOT LIKE '%haftanın ürünleri%'
                    AND LOWER(baslik) NOT LIKE '%haftanın%'
                    AND LOWER(baslik) NOT LIKE '%fırsat ürünleri%'
                    AND LOWER(baslik) NOT LIKE '%fırsat%'
                    ORDER BY sira ASC", PDO::FETCH_ASSOC);
                  if($query->rowCount()){
                    foreach($query as $row1){
                ?>
                <!-- Recently viewed -->
                <div class="position-relative">
                    <div class="border-bottom border-color-1 mb-2">
                        <h3 class="section-title mb-0 pb-2 font-size-22"><?php echo $row1['baslik']; ?></h3>
                    </div>
                    <div class="row">
                        <?php if(!empty($row1['img'])){ ?>
                        <div class="col-md-2">
                          <a href="<?php echo $row1['link']; ?>"><img src="upload/<?php echo $row1['img']; ?>" style="max-width:100%;border-radius:10px;border:1px solid #ddd;background:#ddd;padding:5px" class="img-responsive"></a>
                        </div>
                        <?php } ?>
                        <div class="col-md-<?php if(!empty($row1['img'])){ echo '9'; }else{ echo '12'; } ?>">
                            <div class="js-slick-carousel u-slick position-static overflow-hidden u-slick-overflow-visble pb-7 pt-2 px-1"
                                data-pagi-classes="text-center right-0 bottom-1 left-0 u-slick__pagination u-slick__pagination--long mb-0 z-index-n1 mt-3 mt-md-0"
                                data-slides-show="5"
                                data-slides-scroll="1"
                                data-arrows-classes="position-absolute top-0 font-size-17 u-slick__arrow-normal top-10"
                                data-arrow-left-classes="fa fa-angle-left right-1"
                                data-arrow-right-classes="fa fa-angle-right right-0"
                                data-responsive='[{
                                  "breakpoint": 1400,
                                  "settings": {
                                    "slidesToShow": 4
                                  }
                                }, {
                                    "breakpoint": 1200,
                                    "settings": {
                                      "slidesToShow": 3
                                    }
                                }, {
                                  "breakpoint": 992,
                                  "settings": {
                                    "slidesToShow": 3
                                  }
                                }, {
                                  "breakpoint": 768,
                                  "settings": {
                                    "slidesToShow": 2
                                  }
                                }, {
                                  "breakpoint": 554,
                                  "settings": {
                                    "slidesToShow": 2
                                  }
                                }]'>

                                <?php 
                                  // Dil seçeneği yönetimi - eğer henüz tanımlı değilse
                                  if (!isset($language)) {
                                      $language = isset($_GET['lang']) ? $_GET['lang'] : (isset($_COOKIE['site_language']) ? $_COOKIE['site_language'] : 'en');
                                  }
                                  
                                  $query = $db->query("SELECT
                                  urun.id,
                                  urun.baslik,
                                  urun.baslik_en,
                                  urun.baslik_ru,
                                  urun.sef,
                                  urun.eski_fiyat,
                                  urun.fiyat,
                                  urun.stok,
                                  urun.liste_fiyati_eur,
                                  urun.liste_fiyati_tl,
                                  urun.doviz_kuru,
                                  urun_img.img
                                  FROM
                                  vitrin_urun
                                  INNER JOIN urun ON vitrin_urun.urun_id = urun.id
                                  INNER JOIN urun_img ON urun.id = urun_img.urun_id 
                                  WHERE
                                  vitrin_urun.vitrin_id = '{$row1['id']}'
                                  GROUP BY
                                  urun_img.urun_id
                                  ORDER BY
                                  vitrin_urun.sira ASC
                                      ", PDO::FETCH_ASSOC);
                                if($query->rowCount()){
                                    foreach($query as $row){
                                        include 'inc/urun-sabit.php';
                                    } 
                                } 
                             ?>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- End Recently viewed -->
                <?php
                    }
                  }
                ?>

                <!-- Banner -->
                <div class="row mb-6">
                    <?php
                      $query = $db->query("SELECT * FROM kampanya WHERE alt_ust = 1 ORDER BY sira ASC", PDO::FETCH_ASSOC);
                      if($query->rowCount()){
                        foreach($query as $row){
                          echo '<div class="col-md-4 col-xs-6" style="margin-bottom:20px"><a href="'.$row['link'].'"><img style="max-width:100%;border-radius:10px;border:1px solid #ddd;background:#ddd;padding:5px" alt="" src="upload/'.$row['img'].'"></a></div>';
                        }
                      }
                    ?>
                </div>
                <!-- End Banner -->


            </div>
        </div>
    </div>
</main>
<!-- ========== END MAIN CONTENT ========== -->