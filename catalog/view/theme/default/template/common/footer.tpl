</div>
<div id="footer" class="f1">
  <div class="FootContent MRLAuto">
      <div class="FCompayInfo">
          <?php if ($buy_process) { ?>
          <dl class="f1">
            <dt><?php echo $text_information; ?></dt>
              <?php foreach ($buy_process as $information) { ?>
              <dd><a href="<?php echo $information['href']; ?>"><?php echo $information['title']; ?></a></dd>
              <?php } ?>
          </dl>
          <?php } ?>
          <dl class="f1">
            <dt><?php echo $text_service; ?></dt>

              <?php foreach ($shipping_method as $information) { ?>
              <dd><a href="<?php echo $information['href']; ?>"><?php echo $information['title']; ?></a></dd>
              <?php } ?>

          </dl>
          <dl class="f1">
            <dt><?php echo $text_extra; ?></dt>

              <?php foreach ($pay_method as $information) { ?>
              <dd><a href="<?php echo $information['href']; ?>"><?php echo $information['title']; ?></a></dd>
              <?php } ?>

          </dl>
          <dl class="f1">
            <dt><?php echo $text_account; ?></dt>

              <?php foreach ($change_product as $information) { ?>
              <dd><a href="<?php echo $information['href']; ?>"><?php echo $information['title']; ?></a></dd>
              <?php } ?>

          </dl>
          <dl class="f1">
              <dt>淘拓手机端</dt>
              <dd><img src="catalog/view/theme/default/image/er.jpg"></dd>
          </dl>
      </div>
  </div>
</div>
<div id="footerwz" class="f1">
    <div class="MRLAuto">
        <p>
            Copyright © 2008-2014 tao-tuo.com，All Rights Reserved 粤ICP备08114786号 许可证：粤B2-20080329 使用本网站即表示接受用户协议。<br>
            版权所有 广东深圳海天力电子商务有限公司
        </p>
    </div>
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