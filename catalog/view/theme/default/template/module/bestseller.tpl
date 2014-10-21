<div class="clear"></div>
<div class="box">
    <div class="f1">
        <div class="bott">
            <span class="f1 fz14" style="background: #f8450e;"><?php echo $heading_title; ?><i class="samebg jt1 jt2 f1"></i> </span>
        </div>
        <div class="box-content">
        <div class="box-product">
            <?php foreach ($products as $i => $product) { ?>
            <div>
                <?php if ($product['thumb']) { ?>
                <div class="image">
                    <i class="num<?php echo $i;?> samebg f1"></i><a href="<?php echo $product['href']; ?>"><img src="<?php echo $product['thumb']; ?>" alt="<?php echo $product['name']; ?>" /></a></div>
                <?php } ?>
                <div class="name"><a href="<?php echo $product['href']; ?>"><?php echo $product['name']; ?></a></div>
                <?php if ($product['price']) { ?>
                <div class="price">
                    <?php if (!$product['special']) { ?>
                    <?php echo $product['price']; ?>
                    <?php } else { ?>
                    <span class="price-old"><?php echo $product['price']; ?></span> <span class="price-new"><?php echo $product['special']; ?></span>
                    <?php } ?>
                </div>
                <?php } ?>
                <?php if ($product['rating']) { ?>
                <div class="rating"><img src="catalog/view/theme/default/image/stars-<?php echo $product['rating']; ?>.png" alt="<?php echo $product['reviews']; ?>" /></div>
                <?php } ?>
            </div>
            <?php } ?>
        </div>
    </div>
    </div>
        <?php
        if($this->request->get['route'] == "common/home"){

        ?>
        <div class="sgk dppro f1">
            <span class="wztit fz14">商城公告</span>
            <div class="box">
                <a href="#" class="img"><img src="catalog/view/theme/default/image/pro5.jpg"></a>
                <a href="#" class="title">EarPods 苹果5原装耳机时尚典雅，音质纯</a>
                <div class="Price"><span class="price">¥123</span></div>
            </div>
        </div>
    <?php
        }

        if($this->request->get['route'] == "product/category" || $this->request->get['route'] == "product/product"){

    ?>
            <style>
                .box-product > div{width:188px;border-right:none;}
                .box-product > div:hover{border-right:none}\
            </style>
    <?php
        }
    ?>
</div>

