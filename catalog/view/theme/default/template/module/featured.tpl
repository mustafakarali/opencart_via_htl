<div class="clear" ></div>
<div class="box">
    <div class="f1">
        <div class="bott">
            <span class="f1 fz14" style="background: #b80101;"><?php echo $_heading_title; ?><i class="samebg jt1 jt2  jt3 f1"></i> </span>
        </div>
        <div class="box-content ">
        <div class="box-product">
            <?php foreach ($products as $product) { ?>
            <div>
                <?php if ($product['thumb']) { ?>
                <div class="image"><a href="<?php echo $product['href']; ?>"><img src="<?php echo $product['thumb']; ?>" alt="<?php echo $product['name']; ?>" /></a></div>
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


    <?php if (count($products) > 0) { ?>

    <div class="sgk dppro history f1">
        <span class="wztit fz14"><?php echo $heading_title; ?></span>
        <?php foreach ($viewd_products as $product) { ?>
        <div class="box f1">
            <?php if ($product['thumb']) { ?>
            <a href="<?php echo $product['href']; ?>" class="img f1"><img src="<?php echo $product['thumb']; ?>" alt="<?php echo $product['name']; ?>" /></a>
            <?php } ?>
              <span class="hiswz f1">
                  <a href="<?php echo $product['href']; ?>" class="title f1"><?php echo $product['name']; ?></a>
                  <div class="price Price1">
                      <?php if (!$product['special']) { ?>
                        <?php echo $product['price']; ?>
                      <?php } else { ?>
                          <span class="price-old"><?php echo $product['price']; ?></span> <span class="price-new"><?php echo $product['special']; ?></span>
                      <?php } ?>
                  </div>
              </span>
        </div>
        <?php } ?>
    </div>
    <?php } ?>

</div>
