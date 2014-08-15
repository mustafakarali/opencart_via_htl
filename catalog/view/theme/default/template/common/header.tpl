<!DOCTYPE html>
<html dir="<?php echo $direction; ?>" lang="<?php echo $lang; ?>">
<head>
<meta charset="UTF-8" />
<title><?php echo $title; ?></title>
<base href="<?php echo $base; ?>" />
<?php if ($description) { ?>
<meta name="description" content="<?php echo $description; ?>" />
<?php } ?>
<?php if ($keywords) { ?>
<meta name="keywords" content="<?php echo $keywords; ?>" />
<?php } ?>
<?php if ($icon) { ?>
<link href="<?php echo $icon; ?>" rel="icon" />
<?php } ?>
<?php foreach ($links as $link) { ?>
<link href="<?php echo $link['href']; ?>" rel="<?php echo $link['rel']; ?>" />
<?php } ?>
    <link rel="stylesheet" type="text/css" href="catalog/view/theme/default/stylesheet/stylesheet.css" />
    <link rel="stylesheet" type="text/css" href="catalog/view/theme/default/stylesheet/style.css" />
<?php foreach ($styles as $style) { ?>
<link rel="<?php echo $style['rel']; ?>" type="text/css" href="<?php echo $style['href']; ?>" media="<?php echo $style['media']; ?>" />
<?php } ?>
<script type="text/javascript" src="catalog/view/javascript/jquery/jquery-1.7.1.min.js"></script>
<script type="text/javascript" src="catalog/view/javascript/jquery/ui/jquery-ui-1.8.16.custom.min.js"></script>
<link rel="stylesheet" type="text/css" href="catalog/view/javascript/jquery/ui/themes/ui-lightness/jquery-ui-1.8.16.custom.css" />
<script type="text/javascript" src="catalog/view/javascript/common.js"></script>
<?php foreach ($scripts as $script) { ?>
<script type="text/javascript" src="<?php echo $script; ?>"></script>
<?php } ?>
<!--[if IE 7]> 
<link rel="stylesheet" type="text/css" href="catalog/view/theme/default/stylesheet/ie7.css" />
<![endif]-->
<!--[if lt IE 7]>
<link rel="stylesheet" type="text/css" href="catalog/view/theme/default/stylesheet/ie6.css" />
<script type="text/javascript" src="catalog/view/javascript/DD_belatedPNG_0.0.8a-min.js"></script>
<script type="text/javascript">
DD_belatedPNG.fix('#logo img');
</script>
<![endif]-->
<?php if ($stores) { ?>
<script type="text/javascript"><!--
$(document).ready(function() {
<?php foreach ($stores as $store) { ?>
$('body').prepend('<iframe src="<?php echo $store; ?>" style="display: none;"></iframe>');
<?php } ?>
});
//--></script>
<?php } ?>
<?php echo $google_analytics; ?>
</head>
<body>
<div id="notification"></div>
<div id="NavTopBg">
    <div id="navtop" class="MRLAuto" href="#toTop">
        <div class="nttext">
            <div class="f1 phonem" id="d1" style="display:inline; position:relative;">
                <i class="samebg f1 picon"></i><span class="pj f1">手机版</span><i class="samebg f1 jicon"></i>
                <div id="d2" style="width:168px;height:130px;border:1px solid #e5e5e5;background-color:#fff;display:none;padding:5px;position:absolute;">
                    <i class="samebg f1 picon"></i><span class="pj f1">手机版</span><i class="samebg f1 jicon"></i>
                    <span class="c1 f1 pxl">扫描二维码，下载淘拓客户端</span>
                </div>
            </div>
            <div class="r1 infome">
                <span class="c1">淘拓在线欢迎您！</span>
                <a class="acurr">登陆 </a>
                <a>免费注册</a>
            </div>
            <!--登陆成功状态-->
            <div class="cginfome r1" style="display:none">
                <div class="yh"><span class="f1 c1"> Hi， chunzi_88 </span><a class="r1">退出</a></div>
                <ul class="f1">
                    <li><a>我的订单</a></li>
                    <li><a>侍付款订单</a></li>
                    <li><a>我的帐户</a></li>
                </ul>
            </div>
        </div>
    </div>
</div>
<div class="clear"></div>
<div class="MRLAuto">
    <div class="clear"></div>
    <div class="Hd2 f1">
        <a class="logo f1"><img src="<?php echo $logo; ?>"></a>
        <div class="Search f1">
            <input type="text" name="keyword" class="keysh nonebg f1">
            <input type="button" name="tjsh" class="tjsh nonebg f1">
        </div>
        <?php echo $cart;?>
    </div>
    <div class="clear"></div>
    <div id="NavMenu" class="dis">
        <div class="nav MRLAuto">
            <h2 class="LinkBg f1">
                <a class="LinkAll White" href="#"><strong>全部商品分类</strong></a>
            </h2>
            <ul class="f1">
                <li class="cur f1"><a class="White"><strong>首页</strong></a></li>
                <li class="f1"><a class="White"><strong>优惠促销</strong></a></li>
                <li class="f1"><a class="White"><strong> 品牌故事 </strong> </a></li>
                <li class="f1"><a class="White"><strong>帮助中心</strong></a></li>
                <li class="f1"><a class="White"><strong>联系我们</strong></a></li>
            </ul>
        </div>
    </div>
