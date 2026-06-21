<main id="content" role="main">
    <!-- breadcrumb -->
    <div class="bg-gray-13 bg-md-transparent">
        <div class="container">
            <!-- breadcrumb -->
            <div class="my-md-3">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-3 flex-nowrap flex-xl-wrap overflow-auto overflow-xl-visble">
                        <li class="breadcrumb-item flex-shrink-0 flex-xl-shrink-1"><a href="index.php">Anasayfa</a></li>
                        <li class="breadcrumb-item flex-shrink-0 flex-xl-shrink-1 active" aria-current="page">Blog</li>
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
            $query = $db->query("SELECT * FROM blog", PDO::FETCH_ASSOC);
            if($query->rowCount()){
                foreach($query as $row){
            ?>
            <div class="col-md-4 col-wd">
                <div class="max-width-1100-wd">
                    <article class="card mb-13 border-0">
                        <a href="blog/<?php echo $row['sef']; ?>" class="d-block"><img class="img-fluid" src="upload/<?php echo $row['img']; ?>" alt=""></a>
                        <div class="card-body pt-5 pb-0 px-0">
                            <h4 class="mb-3"><a href="blog/<?php echo $row['sef']; ?>"><?php echo $row['baslik']; ?></a></h4>
                            <div class="flex-horizontal-center">
                                <a href="blog/<?php echo $row['sef']; ?>" class="btn btn-soft-secondary-w mb-md-0 font-weight-normal px-5 px-md-4 px-lg-5">Devamını Oku</a>
                            </div>
                        </div>
                    </article>
                </div>
            </div>
            <?php } } ?>
        </div>
    </div>
</main>