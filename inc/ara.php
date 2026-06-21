<?php
// Dil seçeneği yönetimi
$language = isset($_GET['lang']) ? $_GET['lang'] : (isset($_COOKIE['site_language']) ? $_COOKIE['site_language'] : 'en');

if(!$_POST){
    die('<meta http-equiv="refresh" content="0;URL=index.php">');
}

$_title         =  $_POST['ara'];
?>  
<main id="content" role="main">
    <!-- breadcrumb -->
    <div class="bg-gray-13 bg-md-transparent">
        <div class="container">
            <!-- breadcrumb -->
            <div class="my-md-3">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-3 flex-nowrap flex-xl-wrap overflow-auto overflow-xl-visble">
                        <li class="breadcrumb-item flex-shrink-0 flex-xl-shrink-1"><a href="index.php"><?php 
                            if ($language == 'ru') echo 'Главная';
                            elseif ($language == 'en') echo 'Home';
                            else echo 'Anasayfa';
                        ?></a></li>
                        <li class="breadcrumb-item flex-shrink-0 flex-xl-shrink-1 active" aria-current="page"><?php echo $_POST['ara']; ?></li>
                    </ol>
                </nav>
            </div>
            <!-- End breadcrumb -->
        </div>
    </div>
    <!-- End breadcrumb -->

    <div class="container">
        <div class="row mb-8">
            <div class="col-xl-12 col-wd-9gdot5">
                <!-- Shop-control-bar Title -->
                <div class="flex-center-between mb-3">
                    <h3 class="font-size-25 mb-0"><?php 
                        echo $_POST['ara'];
                        if ($language == 'ru') echo ' Товары';
                        elseif ($language == 'en') echo ' Products';
                        else echo ' Ürünleri';
                    ?></h3>
                </div>
                <!-- End shop-control-bar Title -->
                <!-- Shop Body -->
                <!-- Tab Content -->
                <div class="tab-content" id="pills-tabContent">
                    <div class="tab-pane fade pt-2 show active" id="pills-one-example1" role="tabpanel" aria-labelledby="pills-one-example1-tab" data-target-group="groups">
                        <ul class="row list-unstyled products-group no-gutters">
                            <?php
                              $searchTerm = trim($_POST['ara']);
                              list($searchWhere, $searchParams) = arama_urun_sql_kosul($searchTerm);

                              // Kelime kelime eslesme: "d1403 piston" -> baslikta hem D1403 hem PISTON aranir
                              $query = $db->prepare("SELECT
                                                    urun.baslik,
                                                    urun.baslik_en,
                                                    urun.baslik_ru,
                                                    urun.id,
                                                    urun.sef,
                                                    urun.eski_fiyat,
                                                    urun.fiyat,
                                                    urun.stok_kodu,
                                                    urun.stok,
                                                    urun.liste_fiyati_eur,
                                                    urun.liste_fiyati_tl,
                                                    urun.iskonto_orani,
                                                    urun.doviz_kuru,
                                                    urun.kredi_karti_fiyati,
                                                    urun.pesin_odeme_fiyati,
                                                    urun.kdv,
                                                    (SELECT img FROM urun_img WHERE urun_id = urun.id LIMIT 1) as img
                                                    FROM urun
                                                    WHERE {$searchWhere}
                                                    ORDER BY urun.id DESC
                                                    LIMIT 50");
                              $query->execute($searchParams);
                              
                              if($query->rowCount()){
                                foreach( $query as $row ){
                              ?>
                            <li class="col-6 col-md-3 product-item">
                                <?php include 'inc/urun-sabit.php'; ?>
                            </li>
                            <?php 
                                } 
                              } else {
                                // Ürün bulunamadı mesajı
                            ?>
                            <li class="col-12">
                                <div class="alert alert-warning text-center">
                                    <h5><i class="fa fa-search"></i> <?php 
                                        if ($language == 'ru') echo 'Товар не найден';
                                        elseif ($language == 'en') echo 'Product Not Found';
                                        else echo 'Ürün Bulunamadı';
                                    ?></h5>
                                    <p class="mb-0">"<strong><?php echo htmlspecialchars($searchTerm); ?></strong>" <?php 
                                        if ($language == 'ru') echo 'для поиска результатов не найдено.';
                                        elseif ($language == 'en') echo 'no search results found for.';
                                        else echo 'için arama sonucu bulunamadı.';
                                    ?></p>
                                    <p class="mt-2"><small><?php 
                                        if ($language == 'ru') echo 'Пожалуйста, попробуйте другой поисковый запрос или ищите по названию товара/коду склада.';
                                        elseif ($language == 'en') echo 'Please try a different search term or search by product name/stock code/reference number.';
                                        else echo 'Lütfen farklı bir arama terimi deneyin veya ürün adı/stok kodu/referans numarası ile arayın.';
                                    ?></small></p>
                                </div>
                            </li>
                            <?php } ?>
                        </ul>
                    </div>
                </div>
                <!-- End Tab Content -->
                <!-- End Shop Body -->
            </div>
        </div>
    </div>
</main>