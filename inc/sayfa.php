<?php

$query = $db->prepare("SELECT * FROM sayfa where sef=:sef LIMIT 1");
$icsayfa = $query->execute(array(":sef"=>$_GET['sef']));
$icsayfa = $query->fetch(PDO::FETCH_ASSOC);

if(!$icsayfa){
  echo '<meta http-equiv="refresh" content="0;URL=index.php">';
}


$_title         =  $icsayfa['baslik'];
$_description   =  $icsayfa['kisa_aciklama'];


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
                        <li class="breadcrumb-item flex-shrink-0 flex-xl-shrink-1 active" aria-current="page"><?php echo $icsayfa['baslik']; ?></li>
                    </ol>
                </nav>
            </div>
            <!-- End breadcrumb -->
        </div>
    </div>
    <!-- End breadcrumb -->

    <div class="container">
        <div class="mb-12 text-center">
            <h1><?php echo $icsayfa['baslik']; ?></h1>  
        </div>
        <div class="mb-10">
            <?php echo $icsayfa['aciklama']; ?>
        </div>
    </div>
</main>