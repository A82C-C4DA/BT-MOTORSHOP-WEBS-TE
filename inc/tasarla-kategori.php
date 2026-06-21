<?php

$query = $db->prepare("SELECT * FROM tkategori where sef=:sef LIMIT 1");
$kategori = $query->execute(array(":sef"=>@$_GET['sef']));
$kategori = $query->fetch(PDO::FETCH_ASSOC);

if(!$kategori){
   $_title         =  'Ürün Tasarla';
   $_description   =  'Ürün Tasarla';
}else{
    $_title         =  $kategori['baslik'];
    $_description   =  $kategori['kisa_aciklama'];
}
?>  
<main id="content" role="main">
    <!-- breadcrumb -->
    <div class="bg-gray-13 bg-md-transparent">
        <div class="container">
            <!-- breadcrumb -->
            <div class="my-md-3">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-3 flex-nowrap flex-xl-wrap overflow-auto overflow-xl-visble">
                        <li class="breadcrumb-item flex-shrink-0 flex-xl-shrink-1"><a href="index.php">Anasayfa</a></li>
                        <li class="breadcrumb-item flex-shrink-0 flex-xl-shrink-1 active" aria-current="page"><?php echo $_title; ?></li>
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
                          if($kategori){
                              $bir_ust = $db->query("SELECT * FROM tkategori WHERE id = '{$kategori['ust_kategori']}' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
                              if($bir_ust){
                                echo '<li class="dropdown-toggle dropdown-toggle-collapse"><a href="tasarla-kategori/'.$bir_ust['sef'].'" title="" style="font-weight: bold;color: green;"><i class="las la-angle-right"></i> Üst Kategoriye Dön</a></li>';
                              }

                              $query = $db->query("SELECT * FROM tkategori WHERE ust_kategori = '{$kategori['id']}' ORDER BY sira ASC", PDO::FETCH_ASSOC);
                              if($query->rowCount()){
                                  foreach($query as $row){
                                    echo '<li class="dropdown-toggle dropdown-toggle-collapse"><i class="las la-angle-right"></i><a href="tasarla-kategori/'.$row['sef'].'" title="'.$row['baslik'].'">'.$row['baslik'].'</a></li>';
                                  }
                              }else{
                                $query = $db->query("SELECT * FROM tkategori WHERE ust_kategori = '{$kategori['ust_kategori']}' ORDER BY sira ASC", PDO::FETCH_ASSOC);
                                if($query->rowCount()){
                                    foreach($query as $row){
                                      echo '<li class="dropdown-toggle dropdown-toggle-collapse"><i class="las la-angle-right"></i><a href="tasarla-kategori/'.$row['sef'].'" title="'.$row['baslik'].'">'.$row['baslik'].'</a></li>';
                                    }
                                }
                              }
                          }else{
                              $query = $db->query("SELECT * FROM tkategori WHERE ust_kategori = 0 ORDER BY sira ASC", PDO::FETCH_ASSOC);
                              if($query->rowCount()){
                                  foreach($query as $row){
                                    echo '<li class="dropdown-toggle dropdown-toggle-collapse"><i class="las la-angle-right"></i><a href="tasarla-kategori/'.$row['sef'].'" title="'.$row['baslik'].'">'.$row['baslik'].'</a></li>';
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
                    <h3 class="font-size-25 mb-0"><?php echo $_title; ?> Ürünleri</h3>
                </div>
                <!-- End shop-control-bar Title -->
                <!-- Shop Body -->
                <!-- Tab Content -->
                <div class="tab-content" id="pills-tabContent">
                    <div class="tab-pane fade pt-2 show active" id="pills-one-example1" role="tabpanel" aria-labelledby="pills-one-example1-tab" data-target-group="groups">
                        <ul class="row list-unstyled products-group no-gutters">
                            <?php
                              $baslangic = ($_GET['no'] * 12) - 12;

                              if($kategori){
                                $kataegori_idleri = alt_kategori_bul1($kategori['id']).'0';
                                $query = $db->query("SELECT
                                            turun.baslik,
                                            turun.sef,
                                            turun.eski_fiyat,
                                            turun.fiyat,
                                            turun.id,
                                            turun_img.img
                                            FROM
                                            turun_kategori
                                            INNER JOIN turun ON turun_kategori.urun_id = turun.id
                                            INNER JOIN turun_img ON turun.id = turun_img.urun_id
                                            WHERE
                                            turun_kategori.kategori_id IN ({$kataegori_idleri})
                                            GROUP BY
                                            turun_img.urun_id
                                            LIMIT {$baslangic},12", PDO::FETCH_ASSOC);
                              }else{
                                $query = $db->query("SELECT
                                            turun.baslik,
                                            turun.sef,
                                            turun.eski_fiyat,
                                            turun.fiyat,
                                            turun.id,
                                            turun_img.img
                                            FROM
                                            turun
                                            INNER JOIN turun_img ON turun.id = turun_img.urun_id
                                            GROUP BY
                                            turun_img.urun_id
                                            LIMIT {$baslangic},12", PDO::FETCH_ASSOC);
                              }
                              
                              if($query->rowCount()){
                                foreach( $query as $row ){
                              ?>
                            <li class="col-6 col-md-3 product-item">
                                <?php include 'inc/urun-sabit-tasarla.php'; ?>
                            </li>
                            <?php } } ?>
                        </ul>
                    </div>
                </div>
                <!-- End Tab Content -->
                <!-- End Shop Body -->
                <!-- Shop Pagination -->
                <?php
                if($kategori){
                        $say = $db->query("SELECT
                        turun.id
                        FROM
                        turun_kategori
                        INNER JOIN turun ON turun_kategori.urun_id = turun.id
                        WHERE
                        turun_kategori.kategori_id IN ({$kataegori_idleri})
                        GROUP BY
                        turun_kategori.urun_id")->fetchAll();
                        $page_url  = 'tasarla-kategori/'.$kategori['sef'].'/';
                }else{
                    $say = $db->query("SELECT id FROM turun")->fetchAll();
                    $page_url  = 'tasarla-kategori/';
                }

                $top_sayfa = count($say);
                $page      = $_GET['no'];
                $limit     = 12;
               
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
                <?php if($kategori AND !empty($kategori['aciklama'])){ ?>
                <div style="float:left;width: 100%;padding: 10px;border:1px solid #ddd;border-radius: 10px;margin-top:10px">
                    <?php echo $kategori['aciklama']; ?>
                </div>
                <?php } ?>

            </div>
        </div>
    </div>
</main>