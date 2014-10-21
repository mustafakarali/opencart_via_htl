<?php if ($addresses) { ?>
<div id="payment-existing">

    <?php foreach ($addresses as $address) { ?>
            <dl class="item <?php if ($address['address_id'] == $address_id) { echo 'selected';}?> ">
                <dt><?php echo $address['firstname']; ?> <?php echo $address['lastname']; ?></dt>
                <dd>
                    <p class="tel"><?php echo $address['mobile']?></p>
                    <p><?php echo $address['country']; ?> <?php echo $address['zone']; ?>  <?php echo $address['city']; ?></p>
                    <p><?php echo $address['address_1']; ?></p>
                </dd>
                <dd style="display:none">
                    <input type="radio" name="address_id" class="addressId" value="<?php echo $address['address_id']?>">
                </dd>
            </dl>
    <?php } ?>
    <br/>
    <div class="clear"></div>
    <div class="rowdz1">
        <div class="adddz f1">
            <button onclick="createOrUpdate()" class="short-btn short-btn1" type="button">使用新地址</button>
        </div>
    </div>
</div>
<?php } ?>
<div id="payment-new" style="display: <?php echo ($addresses ? 'none' : 'block'); ?>;">
  <div class="new-address">
  <table class="form">
    <tr>
        <td>使用新地址
        </td>
        <td>
            <span class="buy-common-overlay-close-x"></span>
        </td>
    </tr>
      <tr>
          <td><span class="required">*</span> <?php echo $entry_country; ?></td>
          <td><select name="country_id" class="large-field">
                  <option value=""><?php echo $text_select; ?></option>
                  <?php foreach ($countries as $country) { ?>
                  <?php if ($country['country_id'] == $country_id) { ?>
                  <option value="<?php echo $country['country_id']; ?>" selected="selected"><?php echo $country['name']; ?></option>
                  <?php } else { ?>
                  <option value="<?php echo $country['country_id']; ?>"><?php echo $country['name']; ?></option>
                  <?php } ?>
                  <?php } ?>
              </select></td>
      </tr>
      <tr>
          <td><span class="required">*</span> <?php echo $entry_zone; ?></td>
          <td><select name="zone_id" class="large-field">
              </select></td>
      </tr>

      <tr>
          <td><span class="required">*</span> <?php echo $entry_city; ?></td>
          <td><input type="text" name="city" value="" class="large-field" /></td>
      </tr>
      <tr>
          <td><span class="required">*</span> <?php echo $entry_address_1; ?></td>
          <td><input type="text" name="address_1" value="" class="large-field" /></td>
      </tr>

      <tr>
          <td><span class="required">*</span> <?php echo $entry_lastname; ?></td>
          <td><input type="text" name="lastname" value="" class="large-field" /></td>
      </tr>

        <tr>
            <td><span class="required">*</span> <?php echo $entry_firstname; ?></td>
            <td><input type="text" name="firstname" value="" class="large-field" /></td>
        </tr>

    <tr class="hidden">
      <td><?php echo $entry_company; ?></td>
      <td><input type="text" name="company" value="" class="large-field" /></td>
    </tr>

    <?php if ($company_id_display) { ?>
    <tr>
      <td><?php if ($company_id_required) { ?>
        <span class="required">*</span>
        <?php } ?>
        <?php echo $entry_company_id; ?></td>
      <td><input type="text" name="company_id" value="" class="large-field" /></td>
    </tr>
    <?php } ?>
    <?php if ($tax_id_display) { ?>
    <tr>
      <td><?php if ($tax_id_required) { ?>
        <span class="required">*</span>
        <?php } ?>
        <?php echo $entry_tax_id; ?></td>
      <td><input type="text" name="tax_id" value="" class="large-field" /></td>
    </tr>
    <?php } ?>
    <tr class="hidden">
      <td><?php echo $entry_address_2; ?></td>
      <td><input type="text" name="address_2" value="" class="large-field" /></td>
    </tr>
      <tr>
          <td><span id="payment-postcode-required" class="required">*</span> <?php echo $entry_postcode; ?></td>
          <td><input type="text" name="postcode" value="" class="large-field" /></td>
      </tr>
      <tr>
          <td>手机号码</td>
          <td><input type="text" name="mobile" value="" class="large-field" placeholder="电话号码、手机号码必须填一项" /></td>
      </tr>
      <tr>
          <td >电话号码</td>
          <td>
            <input type="text" name="phoneSection" value="" class="smail-field" placeholder="区号" />-
              <input type="text" name="phoneCode" value="" class="smail-field" placeholder="电话号码"/>-
              <input type="text" name="phoneExt" value="" class="smail-field" placeholder="分机"/>
          </td>
      </tr>
  </table>
  <div class="buttons">
    <div class="left">
      <input type="button" value="<?php echo $button_save; ?>" id="button-payment-address-save" class="button" />
    </div>
    <div class="right">
      <input type="button" value="<?php echo $button_reset; ?>" id="button-payment-address-reset" class="button" />
    </div>
  </div>
  </div> <!--新地址 end-->
</div>
<br />
<?php if ($shipping_required) { ?>
  <input type="checkbox" name="same_address" value="1" id="shipping" checked="checked" class="hidden" />
<?php } ?>
<script type="text/javascript"><!--
$('#payment-address input[name=\'payment_address\']').live('change', function() {
	if (this.value == 'new') {
		$('#payment-existing').hide();
		$('#payment-new').show();
	} else {
		$('#payment-existing').show();
		$('#payment-new').hide();
	}
});
//--></script> 
<script type="text/javascript"><!--
$('#payment-address select[name=\'country_id\']').bind('change', function() {
	if (this.value == '') return;
	$.ajax({
		url: 'index.php?route=onepage/checkout/country&country_id=' + this.value,
		dataType: 'json',
		beforeSend: function() {
			$('#payment-address select[name=\'country_id\']').after('<span class="wait">&nbsp;<img src="catalog/view/theme/default/image/loading.gif" alt="" /></span>');
		},
		complete: function() {
			$('.wait').remove();
		},			
		success: function(json) {
			if (json['postcode_required'] == '1') {
				$('#payment-postcode-required').show();
			} else {
				$('#payment-postcode-required').hide();
			}
			
			html = '<option value=""><?php echo $text_select; ?></option>';
			
			if (json['zone'] != '') {
				for (i = 0; i < json['zone'].length; i++) {
        			html += '<option value="' + json['zone'][i]['zone_id'] + '"';
	    			
					if (json['zone'][i]['zone_id'] == '<?php echo $zone_id; ?>') {
	      				html += ' selected="selected"';
	    			}
	
	    			html += '>' + json['zone'][i]['name'] + '</option>';
				}
			} else {
				html += '<option value="0" selected="selected"><?php echo $text_none; ?></option>';
			}
			
			$('#payment-address select[name=\'zone_id\']').html(html);
		}
	});
});

$('#payment-address select[name=\'country_id\']').trigger('change');

<?php if (!$shipping_required) { echo "load_payment_method();"; }; ?>

    //add jalen
    $("#payment-existing dl").each(function(i,element){
        $("#payment-existing dl").click(function(){
            $("#payment-existing dl:eq("+i+")").removeClass("selected");
            $(this).addClass("selected");
        })
});
    function createOrUpdate(){
        $("#payment-new").show();
        $('<div style="width: 100%; left: 0px; top: 0px; height: 100%; position: fixed; -webkit-user-select: none; z-index: 20000;background: #333;opacity: .5; " class="buy-common-dialog-mask buy-common-overlay-mask"></div>').appendTo("body");
    };

    $(".buy-common-overlay-close-x").click(function(){
        $("#payment-new").hide();
        $(".buy-common-dialog-mask").remove();
    });
//--></script>