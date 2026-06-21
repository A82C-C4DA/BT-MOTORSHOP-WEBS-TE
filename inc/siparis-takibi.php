<?php

$_title         =  'Ödeme Bildirimi';


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
                        <li class="breadcrumb-item flex-shrink-0 flex-xl-shrink-1 active" aria-current="page">Sipariş Takibi</li>
                    </ol>
                </nav>
            </div>
            <!-- End breadcrumb -->
        </div>
    </div>
    <!-- End breadcrumb -->

    <div class="container">
        <br><br><br><br><br>   
        <?php
            if($_POST){
                $query = $db->prepare("SELECT * FROM siparis where id=:id LIMIT 1");
                $s = $query->execute(array(":id"=>$_POST['no']));
                $s = $query->fetch(PDO::FETCH_ASSOC);

                if($s){
                   echo '<div class="basari">Sipariş Durumunuz: '.$siparis_durum[$s['durum']].'</div>';
                }else{
                    echo '<div class="hata">Sipariş bulunamadı.</div>';
                }
            }
        ?>
        <form class="js-validate" novalidate="novalidate" action="" method="post">
            <div class="row">
                <div class="col-md-5 mb-3">
                    <!-- Form Group -->
                    <div class="js-form-message form-group">
                        <label class="form-label" for="orderid">Sipariş Numaranızı Yazın    
                        </label>
                        <input type="text" class="form-control" name="no" id="orderid" placeholder="Sipariş numarası" aria-label="Found in your order confirmation email.">
                    </div>
                    <!-- End Form Group -->
                </div>
                <!-- Button -->
            </div>
            <div class="row">
                <div class="col mb-1">
                    <button class="btn btn-soft-secondary mb-3 mb-md-0 font-weight-normal px-5 px-md-4 px-lg-5 w-100 w-md-auto">Kontrol Et</button>
                </div>
                <!-- End Button -->
            </div>
        </form>
        <br><br><br><br><br><br>
    </div>
</main>