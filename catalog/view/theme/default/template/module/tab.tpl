<div class="clear" ></div>
<div class="box">
    <div class="tabs01_menu" id="tab-s">
        <li class="tab01 stimg selected" ><?php echo $heading_title; ?></li>
        <li class="tab02 stimg" ><?php echo $brand_heading_title;?></li>
        <li class="tab03 stimg" ><?php echo $group_buy_heading_title;?></li>
        <li class="tab04 stimg" ><?php echo $flash_sale_heading_title?></li>
    </div>
    <div class="tabs01_box" id="tabs-content">
        <div class="box-product tab-product" >
            <?php
            if(!empty($new_products)){
                foreach ($new_products as $product) { ?>
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
            <?php
                }
            }
            ?>
        </div>
        <div class="box-product tab-product">
            <?php
            if(!empty($group_buy_products)){
                foreach ($group_buy_products as $product) { ?>
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
            <?php
                }
            }
            ?>
        </div>
        <div class="box-product tab-product">
            <?php
            if(!empty($flash_sale_products)){
                foreach ($flash_sale_products as $product) { ?>
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
            <?php }
            }
            ?>
        </div>
        <div class="box-product tab-product">
            <?php
            if(!empty($brand_products)){
                foreach ($brand_products as $product) { ?>
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
            <?php }
             }
             ?>
        </div>
    </div>
</div>
<script type="text/javascript">
    $(document).ready(function(){
        $("#tabs-content > .box-product").hide();
        $("#tabs-content > .box-product:first").show();
        $("#tab-s li:first").addClass("selected").show();

        $("#tab-s li").bind("click",function(){
            $(this).addClass("selected").siblings("li").removeClass("selected");
            var active_index = $("#tab-s li").index(this);
            $("#tabs-content").children().eq(active_index).show().siblings().hide();
        });
    });
</script>
