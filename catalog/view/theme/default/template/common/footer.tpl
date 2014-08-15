</div>
<div id="footer" class="f1">
  <div class="FootContent MRLAuto">
      <div class="FCompayInfo">
          <?php if ($informations) { ?>
          <dl class="f1">
            <dt><?php echo $text_information; ?></dt>
              <?php foreach ($informations as $information) { ?>
              <dd><a href="<?php echo $information['href']; ?>"><?php echo $information['title']; ?></a></ddd>
              <?php } ?>
          </dl>
          <?php } ?>
          <dl class="f1">
            <dt><?php echo $text_service; ?></dt>

              <dd><a href="<?php echo $contact; ?>"><?php echo $text_contact; ?></a></dd>
              <dd><a href="<?php echo $return; ?>"><?php echo $text_return; ?></a></dd>
              <dd><a href="<?php echo $sitemap; ?>"><?php echo $text_sitemap; ?></a></dd>

          </dl>
          <dl class="f1">
            <dt><?php echo $text_extra; ?></dt>

              <dd><a href="<?php echo $manufacturer; ?>"><?php echo $text_manufacturer; ?></a></dd>
              <dd><a href="<?php echo $voucher; ?>"><?php echo $text_voucher; ?></a></dd>
              <dd><a href="<?php echo $affiliate; ?>"><?php echo $text_affiliate; ?></a></dd>
              <dd><a href="<?php echo $special; ?>"><?php echo $text_special; ?></a></dd>

          </dl>
          <dl class="f1">
            <dt><?php echo $text_account; ?></dt>

              <dd><a href="<?php echo $account; ?>"><?php echo $text_account; ?></a></dd>
              <dd><a href="<?php echo $order; ?>"><?php echo $text_order; ?></a></dd>
              <dd><a href="<?php echo $wishlist; ?>"><?php echo $text_wishlist; ?></a></dd>
              <dd><a href="<?php echo $newsletter; ?>"><?php echo $text_newsletter; ?></a></dd>

          </dl>
          <dl class="f1">
              <dt>淘拓手机端</dt>
              <dd><img src="images/er.jpg"></dd>
          </dl>
      </div>
  </div>
</div>
<div id="footerwz" class="f1">
    <div class="MRLAuto">
        <p>
            Copyright © 2008-2014 vip.com，All Rights Reserved 粤ICP备08114786号 许可证：粤B2-20080329 使用本网站即表示接受用户协议。<br>
            版权所有 广州信息科技有限公司
        </p>
    </div>
</div>
<div class="backtop">
    <a class="Top f1" href="javascript:void(0)" title="回到顶部" id="toTop"></a>
</div>
<!--
OpenCart is open source software and you are free to remove the powered by OpenCart if you want, but its generally accepted practise to make a small donation.
Please donate via PayPal to donate@opencart.com
//-->
<!--<div id="powered"><?php echo $powered; ?></div>-->
<!--
OpenCart is open source software and you are free to remove the powered by OpenCart if you want, but its generally accepted practise to make a small donation.
Please donate via PayPal to donate@opencart.com
//-->
</div>
</body></html>