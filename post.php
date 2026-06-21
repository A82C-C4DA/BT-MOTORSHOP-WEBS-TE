<?php
  foreach($_GET    as $k => $v) $_GET[$k]    = strip_tags($v);
  foreach($_POST   as $k => $v) $_POST[$k]   = strip_tags($v);


  if($_POST){
    include 'panel/fonksiyon.php';

    if($_POST['islem'] == 'abone'){
      $abonex = $db->prepare("SELECT * FROM abone where email=:email LIMIT 1");
      $abone = $abonex->execute(array(":email"=>$_POST['email']));
      $abone = $abonex->fetch(PDO::FETCH_ASSOC);

      if($abone){
        echo 2;
      }else{
        $islem = $db->prepare("INSERT INTO abone SET email = ?");
        $islem = $islem->execute(array($_POST['email']));
        echo 1;
      }
    }else if($_POST['islem'] == 'urun-ekle'){
        if(is_numeric($_POST['urun_id']) AND is_numeric($_POST['adet']) AND is_numeric($_POST['secenek_id'])){
            if($_POST['adet'] < 1){
              $_POST['adet'] = 1;
            }
            $urunquery = $db->prepare("SELECT * FROM urun where id=:id LIMIT 1");
            $urun = $urunquery->execute(array(":id"=>$_POST['urun_id']));
            $urun = $urunquery->fetch(PDO::FETCH_ASSOC);
            if($urun){
              if($_POST['secenek_id'] > 0){
                $query = $db->prepare("SELECT * FROM urun_secenek_alt where id=:id LIMIT 1");
                $alt_secenek = $query->execute(array(":id"=>$_POST['secenek_id']));
                $alt_secenek = $query->fetch(PDO::FETCH_ASSOC);
                if($alt_secenek){
                  // Stok kontrolü: stok = 1 ise (Var) sınırsız, stok = 0 ise (Yok) eklenemez
                  if($alt_secenek['stok'] == 0){
                    echo 0;
                  }else{
                    $uniqid = $_POST['urun_id'].'-'.$_POST['secenek_id'];
                    $_SESSION['sepet']['urun_id'][$uniqid]      = $_POST['urun_id'];
                    $_SESSION['sepet']['adet'][$uniqid]         = $_POST['adet'];
                    $_SESSION['sepet']['secenek_id'][$uniqid]   = $_POST['secenek_id'];
                    $_SESSION['sepet']['img'][$uniqid]          = $_POST['img'];
                    $_SESSION['sepet']['json'][$uniqid]         = $_POST['json'];
                    $_SESSION['sepet']['key'][$uniqid]          = $uniqid;
                    echo 2;
                  }
                }else{
                  echo 1;
                }
              }else{
                // Stok kontrolü: stok = 1 ise (Var) sınırsız, stok = 0 ise (Yok) eklenemez
                if($urun['stok'] == 0){
                  echo 0;
                }else{
                  $uniqid = $_POST['urun_id'];
                  $_SESSION['sepet']['urun_id'][$uniqid]      = $_POST['urun_id'];
                  $_SESSION['sepet']['adet'][$uniqid]         = $_POST['adet'];
                  $_SESSION['sepet']['img'][$uniqid]          = $_POST['img'];
                  $_SESSION['sepet']['json'][$uniqid]         = $_POST['json'];
                  $_SESSION['sepet']['secenek_id'][$uniqid]   = 0;
                  $_SESSION['sepet']['key'][$uniqid]          = $uniqid;
                  echo 2;
                }
              }
            }else{
              echo 1;
            }
        }else{
          echo 1;
        }
    }else if($_POST['islem'] == 'turun-ekle'){
        if(is_numeric($_POST['urun_id']) AND is_numeric($_POST['adet']) AND is_numeric($_POST['secenek_id'])){
            if($_POST['adet'] < 1){
              $_POST['adet'] = 1;
            }
            $urunquery = $db->prepare("SELECT * FROM turun where id=:id LIMIT 1");
            $urun = $urunquery->execute(array(":id"=>$_POST['urun_id']));
            $urun = $urunquery->fetch(PDO::FETCH_ASSOC);
            if($urun){
              if($_POST['secenek_id'] > 0){
                $query = $db->prepare("SELECT * FROM turun_secenek_alt where id=:id LIMIT 1");
                $alt_secenek = $query->execute(array(":id"=>$_POST['secenek_id']));
                $alt_secenek = $query->fetch(PDO::FETCH_ASSOC);
                if($alt_secenek){
                  // Stok kontrolü: stok = 1 ise (Var) sınırsız, stok = 0 ise (Yok) eklenemez
                  if($alt_secenek['stok'] == 0){
                    echo 0;
                  }else{
                    $uniqid = $_POST['urun_id'].'-'.$_POST['secenek_id'];
                    $_SESSION['sepet']['urun_id'][$uniqid]      = $_POST['urun_id'];
                    $_SESSION['sepet']['adet'][$uniqid]         = $_POST['adet'];
                    $_SESSION['sepet']['secenek_id'][$uniqid]   = $_POST['secenek_id'];
                    $_SESSION['sepet']['img'][$uniqid]          = $_POST['img'];
                    $_SESSION['sepet']['json'][$uniqid]         = $_POST['json'];
                    $_SESSION['sepet']['key'][$uniqid]          = $uniqid;
                    echo 2;
                  }
                }else{
                  echo 1;
                }
              }else{
                // Stok kontrolü: stok = 1 ise (Var) sınırsız, stok = 0 ise (Yok) eklenemez
                if($urun['stok'] == 0){
                  echo 0;
                }else{
                  $uniqid = $_POST['urun_id'];
                  $_SESSION['sepet']['urun_id'][$uniqid]      = $_POST['urun_id'];
                  $_SESSION['sepet']['adet'][$uniqid]         = $_POST['adet'];
                  $_SESSION['sepet']['img'][$uniqid]          = $_POST['img'];
                  $_SESSION['sepet']['json'][$uniqid]         = $_POST['json'];
                  $_SESSION['sepet']['secenek_id'][$uniqid]   = 0;
                  $_SESSION['sepet']['key'][$uniqid]          = $uniqid;
                  echo 2;
                }
              }
            }else{
              echo 1;
            }
        }else{
          echo 1;
        }
    }else if($_POST['islem'] == 'sepet_sil'){
        $uniqid = $_POST['id'];
        unset($_SESSION['sepet']['urun_id'][$uniqid]);
        unset($_SESSION['sepet']['adet'][$uniqid]);
        unset($_SESSION['sepet']['secenek_id'][$uniqid]);
        unset($_SESSION['sepet']['key'][$uniqid]);
        unset($_SESSION['sepet']['json'][$uniqid]);
        unset($_SESSION['sepet']['img'][$uniqid]);
        echo 1;
    }else if($_POST['islem'] == 'sepet_sayisi'){
        echo @count($_SESSION['sepet']['key']);
    }

  }


?>