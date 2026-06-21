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
                        <li class="breadcrumb-item flex-shrink-0 flex-xl-shrink-1 active" aria-current="page">Ödeme Bildirimi</li>
                    </ol>
                </nav>
            </div>
            <!-- End breadcrumb -->
        </div>
    </div>
    <!-- End breadcrumb -->

    <div class="container">
        <br><br>  
        <?php
            if($_POST){
                
                if(!empty($_POST['no']) AND !empty($_POST['adsoyad']) AND !empty($_POST['bank'])){
                     

                          $mailbody = '<!DOCTYPE html>
                            <html>
                            <head>
                              <title>'.$cek['title'].'</title>
                              <meta charset="utf-8">
                            </head>
                            <body style="padding: 30px">
                              <div style="width: 98%;margin:0 auto;background: #02add9;padding: 1%;display: inline-block;border-radius: 10px">
                                <div style="width: 90%;float: left;background: #fff;padding: 10px 5% 20px 5%;">
                                  <center><img src="'.$site.'upload/'.$cek['logo'].'" style="width: 200px"></center>
                                  <table>
                                    <tr>
                                      <th>Sipariş Numarası</th>
                                      <th>Ad Soyad</th>
                                      <th>Banka</th>
                                    </tr>
                                    <tr>
                                      <td>'.$_POST['no'].'</td>
                                      <td>'.$_POST['adsoyad'].'</td>
                                      <td>'.$_POST['bank'].'</td>
                                    </tr>
                                  </table>
                                </div>
                              </div>
                              <style type="text/css">
                              body{font-family:arial}table{width:100%;border:1px solid #ddd}table tr{padding:0;margin:0}table tr th{border:1px solid #ddd;padding:0;margin:0;background:#02add9;color:#fff;padding:10px}table tr td{border:1px solid #ddd;padding:0;text-align:center;margin:0;border-spacing:0}
                              </style>
                            </body>
                            </html>';

                            email_send($cek['email'],$mailbody,'Ödeme Bildirimi');
                                
                  
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
                        <input type="text" class="form-control" name="no" id="orderid" placeholder="Sipariş Numaranız" aria-label="Found in your order confirmation email.">
                    </div>
                    <div class="js-form-message form-group">
                        <label class="form-label" for="orderid">Adınız Soyadınız
                        </label>
                        <input type="text" class="form-control" name="adsoyad" id="orderid" placeholder="Ad Soyad" aria-label="">
                    </div>
                    <div class="js-form-message form-group">
                        <label class="form-label" for="orderid">Sipariş Numaranızı Yazın    
                        </label>
                        <select class="form-control" required="" name="bank">
                            <?php
                                $query = $db->query("SELECT * FROM banka_hesaplari", PDO::FETCH_ASSOC);
                                  if($query->rowCount()){
                                    foreach($query as $row){
                                        echo '<option value="'.$row['baslik'].'">'.$row['baslik'].'</option>';
                                    }
                                  }
                            ?>
                        </select>
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