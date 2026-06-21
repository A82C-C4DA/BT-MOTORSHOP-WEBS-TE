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
    <!-- Hero: Arac Arama + 2'li Slider -->
    <div class="container pt-4 mb-4 hero-arama-slider">
        <div class="row align-items-center">
            <!-- Sol: Sasi / Arac arama kutusu -->
            <div class="col-lg-4 mb-4 mb-lg-0">
                <div class="hero-search-card">
                    <!-- Sasi numarasi ile ara -->
                    <form action="ara" method="post">
                        <input type="text" name="ara" class="form-control hero-input mb-2" placeholder="Şasi numarası ile ara" required>
                        <button type="submit" class="btn hero-btn">Ara</button>
                    </form>

                    <hr class="hero-divider">

                    <label class="hero-label mb-3">Uygun parçaları daha kolay bulabilmek için <strong>aracınızın model bilgilerini seçin.</strong></label>

                    <!-- Marka / Model / Motor -->
                    <form action="ara" method="post" id="hero-model-form">
                        <select name="ara" id="hero-marka" class="form-control hero-select mb-3" required>
                            <option value="" selected disabled>Marka</option>
                            <?php
                              $markaQ = $db->query("SELECT baslik FROM marka WHERE baslik <> '' ORDER BY baslik ASC", PDO::FETCH_ASSOC);
                              if($markaQ){ foreach($markaQ as $m){ echo '<option value="'.htmlspecialchars($m['baslik']).'">'.htmlspecialchars($m['baslik']).'</option>'; } }
                            ?>
                        </select>
                        <select id="hero-model" class="form-control hero-select mb-3" disabled>
                            <option value="" selected disabled>Model</option>
                        </select>
                        <select id="hero-motor" class="form-control hero-select mb-3" disabled>
                            <option value="" selected disabled>Motor</option>
                        </select>
                        <button type="submit" class="btn hero-btn">Ara</button>
                    </form>
                </div>
            </div>

            <!-- Sag: 2'li slider -->
            <div class="col-lg-8">
                <div class="hero-slider-wrap">
                    <div class="js-slick-carousel u-slick hero-slider"
                        data-slides-show="2"
                        data-slides-scroll="1"
                        data-autoplay="true"
                        data-infinite="true"
                        data-pagi-classes="text-center position-absolute right-0 bottom-0 left-0 u-slick__pagination justify-content-center mb-2"
                        data-responsive='[{"breakpoint":768,"settings":{"slidesToShow":1}}]'>
                        <?php
                          $sliderQ = $db->query("SELECT * FROM slider ORDER BY sira ASC", PDO::FETCH_ASSOC);
                          if($sliderQ && $sliderQ->rowCount()){
                            foreach($sliderQ as $row){
                        ?>
                        <div class="js-slide hero-slide">
                            <a href="<?php echo $row['link']; ?>">
                                <img class="hero-slide-img" src="upload/<?php echo $row['img']; ?>" alt="<?php echo htmlspecialchars($row['aciklama']); ?>">
                            </a>
                        </div>
                        <?php } } ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End Hero Section -->
    <style>
    /* === Hero: Arama kutusu + 2'li Slider === */
    .hero-search-card {
        background-color: #1c2536;
        color: #fff;
        padding: 1.75rem;
        border-radius: 1rem;
        height: 100%;
    }
    .hero-input,
    .hero-select {
        height: 52px;
        border: none;
        border-radius: 0.5rem;
        font-size: 15px;
        box-shadow: none;
        width: 100%;
    }
    .hero-input::placeholder { color: #8a93a6; }
    .hero-select { color: #333; background-color: #fff; }
    .hero-select:disabled { background-color: #e9ecef; color: #9aa0ab; cursor: not-allowed; }
    .hero-btn {
        width: 100%;
        height: 52px;
        background-color: #6c7689;
        color: #fff;
        font-weight: 600;
        border: none;
        border-radius: 0.5rem;
        transition: background-color .2s ease;
    }
    .hero-btn:hover { background-color: #ff6600; color: #fff; }
    .hero-divider { border-top: 1px solid rgba(255,255,255,.15); margin: 1.5rem 0; }
    .hero-label { font-size: 16px; line-height: 1.4; color: #fff; display: block; }

    /* Sag slider */
    .hero-slider-wrap { position: relative; }
    .hero-slide { padding: 0 8px; }
    .hero-slide-img {
        width: 100%;
        aspect-ratio: 1 / 0.92;
        height: auto;
        object-fit: cover;
        object-position: center;
        border-radius: 1rem;
        display: block;
    }
    @supports not (aspect-ratio: 1 / 1) {
        .hero-slide-img { height: 24vw; }
    }
    .hero-slider .slick-list { margin: 0 -8px; }

    @media (max-width: 991px) {
        .hero-slide-img { aspect-ratio: 16 / 7; }
        @supports not (aspect-ratio: 1 / 1) {
            .hero-slide-img { height: 40vw; }
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