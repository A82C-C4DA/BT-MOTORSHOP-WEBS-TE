<?php

$_title         =  'Banka Hesaaplarımız';


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
                        <li class="breadcrumb-item flex-shrink-0 flex-xl-shrink-1 active" aria-current="page">Banka Hesaplarımız</li>
                    </ol>
                </nav>
            </div>
            <!-- End breadcrumb -->
        </div>
    </div>
    <!-- End breadcrumb -->

    <div class="container">
        <div class="row">
          <?php
          $query = $db->query("SELECT * FROM banka_hesaplari", PDO::FETCH_ASSOC);
          if($query->rowCount()){
            foreach($query as $row){
                echo '
                <div class="col-md-4" style="margin-bottom:20px;">
                    <div class="bg2 border" style="padding:20px">
                        <div><img src="upload/'.$row['img'].'" class="img-responsive"></div>
                        <div><b>'.$row['baslik'].'</b></div>
                        <div><b>'.$row['aciklama'].'</b></div>
                    </div>
                </div>';
            }
          }else{
            echo '<div class="col-md-12"><div class="border bg2"><center><h3>Hesap bilgileri bulunmuyor.</h3></center></div></div>';
          }
        ?>  
        </div>
    </div>
</main>