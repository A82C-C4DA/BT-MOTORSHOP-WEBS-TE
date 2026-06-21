<div class="js-slide products-group">
    <div class="product-item">
        <div class="product-item__outer h-100">
            <div class="product-item__inner px-wd-4 p-2 p-md-3">
                <div class="product-item__body pb-xl-2">
                    <div class="mb-2">
                        <a href="urun-tasarla/<?php echo $row['sef']; ?>" class="d-block text-center"><img class="img-fluid" src="upload/<?php echo $row['img']; ?> " alt=""></a>
                    </div>

                    <h5 class="mb-1 product-item__title"><a href="urun-tasarla/<?php echo $row['sef']; ?>" class="text-blue font-weight-bold"><?php echo $row['baslik']; ?></a></h5>
                    <div class="flex-center-between mb-1">
                        <div>
                            <?php if(!empty($row['eski_fiyat'])){ ?>
                            <div class="prodcut-price" style="float:left;width:100%">
                                <div class="text-gray-100" style="text-decoration: line-through;color:red"><?php echo fiyat($row['fiyat']); ?> TL</div>
                            </div>
                            <?php } ?>
                            <div class="prodcut-price" style="float:left;width:100%">
                                <div class="text-gray-100"><?php echo fiyat($row['fiyat']); ?> TL</div>
                            </div>
                        </div>
                        <div class="d-none d-xl-block prodcut-add-cart">
                            <a href="urun-tasarla/<?php echo $row['sef']; ?>" class="btn-add-cart btn-primary transition-3d-hover"><i class="ec ec-add-to-cart"></i></a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>