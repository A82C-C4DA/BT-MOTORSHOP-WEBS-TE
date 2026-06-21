<?php
// Dil seçeneği yönetimi
$language = isset($_GET['lang']) ? $_GET['lang'] : (isset($_COOKIE['site_language']) ? $_COOKIE['site_language'] : 'en');

$query = $db->prepare("SELECT * FROM kategori where sef=:sef LIMIT 1");
$kategori = $query->execute(array(":sef"=>$_GET['sef']));
$kategori = $query->fetch(PDO::FETCH_ASSOC);

if(!$kategori){
  echo '<meta http-equiv="refresh" content="0;URL=index.php">';
}

// Kategori başlığını dil seçimine göre al
$kategori_baslik = $kategori['baslik'];
if ($language == 'en' && !empty($kategori['baslik_en'])) {
    $kategori_baslik = $kategori['baslik_en'];
} elseif ($language == 'ru' && !empty($kategori['baslik_ru'])) {
    $kategori_baslik = $kategori['baslik_ru'];
}

$_title         =  $kategori_baslik;
$_description   =  $kategori['kisa_aciklama'];

// Kategori SEO canonical + hreflang
$current_url = $site . 'kategori/' . $kategori['sef'];
$canonical_url = $current_url . ($language !== 'en' ? '?lang=' . $language : '');

$hreflang_tags = '';
$supported_languages = ['tr', 'en', 'ru', 'fr', 'es', 'ar', 'pl'];
foreach ($supported_languages as $lang_code) {
    $href = $current_url . ($lang_code !== 'en' ? '?lang=' . $lang_code : '');
    $hreflang_tags .= '<link rel="alternate" hreflang="' . $lang_code . '" href="' . htmlspecialchars($href) . '" />' . "\n";
}
$hreflang_tags .= '<link rel="alternate" hreflang="x-default" href="' . htmlspecialchars($current_url) . '" />' . "\n";

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
                        <li class="breadcrumb-item flex-shrink-0 flex-xl-shrink-1 active" aria-current="page"><?php echo $kategori_baslik; ?></li>
                    </ol>
                </nav>
            </div>
            <!-- End breadcrumb -->
        </div>
    </div>
    <!-- End breadcrumb -->

    <div class="container">
        <div class="row mb-8">
            <div class="d-none d-xl-block col-xl-3 col-wd-2gdot5">
                <div class="mb-6 border border-width-2 border-color-3 borders-radius-6">
                    <!-- List -->
                    <ul id="sidebarNav" class="list-unstyled mb-0 sidebar-navbar view-all">
                        <li><div class="dropdown-title">Kategoriler</div></li>
                        <?php
                          $bir_ust = $db->query("SELECT * FROM kategori WHERE id = '{$kategori['ust_kategori']}' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
                          if($bir_ust){
                            echo '<li class="dropdown-toggle dropdown-toggle-collapse"><a href="kategori/'.$bir_ust['sef'].'" title="" style="font-weight: bold;color: green;"><i class="las la-angle-right"></i> Üst Kategoriye Dön</a></li>';
                          }

                          $query = $db->query("SELECT * FROM kategori WHERE ust_kategori = '{$kategori['id']}' ORDER BY sira ASC", PDO::FETCH_ASSOC);
                          if($query->rowCount()){
                              foreach($query as $row){
                                echo '<li class="dropdown-toggle dropdown-toggle-collapse"><i class="las la-angle-right"></i><a href="kategori/'.$row['sef'].'" title="'.$row['baslik'].'">'.$row['baslik'].'</a></li>';
                              }
                          }else{
                            $query = $db->query("SELECT * FROM kategori WHERE ust_kategori = '{$kategori['ust_kategori']}' ORDER BY sira ASC", PDO::FETCH_ASSOC);
                            if($query->rowCount()){
                                foreach($query as $row){
                                  echo '<li class="dropdown-toggle dropdown-toggle-collapse"><i class="las la-angle-right"></i><a href="kategori/'.$row['sef'].'" title="'.$row['baslik'].'">'.$row['baslik'].'</a></li>';
                                }
                            }
                          }
                        ?>
                    </ul>
                    <!-- End List -->
                </div>
            </div>
            <div class="col-xl-9 col-wd-9gdot5">
                <!-- Shop-control-bar Title -->
                <div class="flex-center-between mb-3">
                    <h3 class="font-size-25 mb-0"><?php 
                        echo $kategori_baslik;
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
                              $baslangic = ($_GET['no'] * 12) - 12;
                              $kataegori_idleri = alt_kategori_bul($kategori['id']).'0';
                              try {
                                  $checkSiraCol = $db->query("SHOW COLUMNS FROM urun LIKE 'sira'")->fetch();
                                  if (!$checkSiraCol) {
                                      $db->exec('ALTER TABLE urun ADD COLUMN sira INT NOT NULL DEFAULT 9999');
                                  }
                              } catch (Exception $e) {}
                              $query = $db->query("SELECT
                                            urun.baslik,
                                            urun.baslik_en,
                                            urun.baslik_ru,
                                            urun.sef,
                                            urun.eski_fiyat,
                                            urun.fiyat,
                                            urun.id,
                                            urun.stok,
                                            urun.sira,
                                            urun.liste_fiyati_eur,
                                            urun.liste_fiyati_tl,
                                            urun.doviz_kuru,
                                            (SELECT ui.img FROM urun_img ui WHERE ui.urun_id = urun.id ORDER BY ui.id ASC LIMIT 1) AS img
                                            FROM
                                            urun_kategori
                                            INNER JOIN urun ON urun_kategori.urun_id = urun.id
                                            WHERE
                                            urun_kategori.kategori_id IN ({$kataegori_idleri})
                                            GROUP BY
                                            urun.id
                                            ORDER BY
                                            urun.sira ASC,
                                            urun.id DESC
                                            LIMIT {$baslangic},12", PDO::FETCH_ASSOC);
                              if($query->rowCount()){
                                foreach( $query as $row ){
                              ?>
                            <li class="col-6 col-md-3 product-item">
                                <?php include 'inc/urun-sabit.php'; ?>
                            </li>
                            <?php } } ?>
                        </ul>
                    </div>
                </div>
                <!-- End Tab Content -->
                <!-- End Shop Body -->
                <!-- Shop Pagination -->
                <?php
                $say = $db->query("SELECT COUNT(DISTINCT urun.id) AS cnt
                                FROM
                                urun_kategori
                                INNER JOIN urun ON urun_kategori.urun_id = urun.id
                                WHERE
                                urun_kategori.kategori_id IN ({$kataegori_idleri})")->fetch(PDO::FETCH_ASSOC);

                $top_sayfa = isset($say['cnt']) ? (int)$say['cnt'] : 0;
                $page      = $_GET['no'];
                $limit     = 12;
                $page_url  = 'kategori/'.$kategori['sef'].'/';
                $baslangic = ($page * $limit) - $limit;
                ?>
                <div class="row">
                    <div class="col-md-12">
                        <div class="pagination-wrapper pull-right">
                            <nav aria-label="Page navigation">
                                <ul class="pagination mb-0">
                                    <?php Sayfala($top_sayfa,$page,$limit,$page_url); ?>
                                </ul>
                            </nav>
                        </div>
                    </div>
                </div>
                <!-- End Shop Pagination -->

                <div style="float:left;width: 100%;padding: 10px;border:1px solid #ddd;border-radius: 10px;margin-top:10px">
                    <?php echo $kategori['aciklama']; ?>
                </div>

            </div>
        </div>
    </div>
</main>