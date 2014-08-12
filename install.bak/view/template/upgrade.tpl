<?php echo $header; ?>
<h1>系统升级</h1>
<div id="column-right">
  <ul>
    <li><b>升级</b></li>
    <li>完成</li>
  </ul>
</div>
<div id="content">
  <?php if ($error_warning) { ?>
  <div class="warning"><?php echo $error_warning; ?></div>
  <?php } ?>
  <form action="<?php echo $action; ?>" method="post" enctype="multipart/form-data">
    <fieldset>
    <p><b>请仔细按照如下步骤操作</b></p>
    <ol>
      <li>如遇到问题，请在论坛求助</li>
      <li>升级完成后，清除cookies等</li>
      <li>加载后台页面后，按下Ctrl+F5 两次以加载新的css</li>
      <li>登陆后台，系统设置 -> 用户管理 -> 用户群组，选中所有复选框</li>
      <li>登陆后台，系统设置 -> 网店设置，更新相关选项，即使没有更新任何内容，也需要点击保存</li>
      <li>访问网站前台，按下Ctrl+F5 两次以强行加载新的css</li>
    </ol>
    </fieldset>
    <div class="buttons">
	  <div class="right">
        <input type="submit" value="继续升级" class="button" />
      </div>
	</div>
  </form>
</div>
<?php echo $footer; ?> 