<?php echo $header; ?>
<div id="content">
    <div class="breadcrumb">
        <?php foreach ($breadcrumbs as $breadcrumb) { ?>
        <?php echo $breadcrumb['separator']; ?><a href="<?php echo $breadcrumb['href']; ?>"><?php echo $breadcrumb['text']; ?></a>
        <?php } ?>
    </div>
    <?php if ($error_warning) { ?>
    <div class="warning"><?php echo $error_warning; ?></div>
    <?php } ?>
    <div class="box">
        <div class="heading">
            <h1><img src="view/image/module.png" alt="" /> <?php echo $heading_title; ?></h1>
            <div class="buttons"><a onclick="$('#form').submit();" class="button"><?php echo $button_save; ?></a><a href="<?php echo $cancel; ?>" class="button"><?php echo $button_cancel; ?></a></div>
        </div>
        <div class="content">
            <form action="<?php echo $action; ?>" method="post" enctype="multipart/form-data" id="form">
                <table class="form">
                    <tr>
                        <td><?php echo $entry_product; ?></td>
                        <td><input type="text" name="tab-new-product" value="" /></td>
                    </tr>
                    <tr>
                        <td>&nbsp;</td>
                        <td><div id="featured-product" class="scrollbox">
                                <?php $class = 'odd'; ?>
                                <?php foreach ($products as $product) { ?>
                                <?php $class = ($class == 'even' ? 'odd' : 'even'); ?>
                                <div id="featured-product<?php echo $product['product_id']; ?>" class="<?php echo $class; ?>"><?php echo $product['name']; ?> <img src="view/image/delete.png" alt="" />
                                    <input type="hidden" value="<?php echo $product['product_id']; ?>" />
                                </div>
                                <?php } ?>
                            </div>
                            <input type="hidden" name="tab_new_product" value="<?php echo $tab_new_product; ?>" /></td>
                    </tr>
                </table>
                <!--品牌推荐 start-->
                <table class="form">
                    <tr>
                        <td><?php echo $entry_brand_product; ?></td>
                        <td><input type="text" name="tab-brand-product" value="" /></td>
                    </tr>
                    <tr>
                        <td>&nbsp;</td>
                        <td><div id="brand-product" class="scrollbox">
                                <?php $class = 'odd'; ?>
                                <?php foreach ($brand_products as $product) { ?>
                                <?php $class = ($class == 'even' ? 'odd' : 'even'); ?>
                                <div id="featured-product<?php echo $product['product_id']; ?>" class="<?php echo $class; ?>"><?php echo $product['name']; ?> <img src="view/image/delete.png" alt="" />
                                    <input type="hidden" value="<?php echo $product['product_id']; ?>" />
                                </div>
                                <?php } ?>
                            </div>
                            <input type="hidden" name="tab_brand_product" value="<?php echo $tab_brand_product; ?>" /></td>
                    </tr>
                </table>
                <!--品牌推荐end-->
                <!--团购产品 start-->
                <table class="form">
                    <tr>
                        <td><?php echo $entry_group_buy_product; ?></td>
                        <td><input type="text" name="tab-group-buy-product" value="" /></td>
                    </tr>
                    <tr>
                        <td>&nbsp;</td>
                        <td><div id="group-buy-product" class="scrollbox">
                                <?php $class = 'odd'; ?>
                                <?php foreach ($group_buy_products as $product) { ?>
                                <?php $class = ($class == 'even' ? 'odd' : 'even'); ?>
                                <div id="featured-product<?php echo $product['product_id']; ?>" class="<?php echo $class; ?>"><?php echo $product['name']; ?> <img src="view/image/delete.png" alt="" />
                                    <input type="hidden" value="<?php echo $product['product_id']; ?>" />
                                </div>
                                <?php } ?>
                            </div>
                            <input type="hidden" name="tab_group_buy_product" value="<?php echo $tab_group_buy_product; ?>" /></td>
                    </tr>
                </table>
                <!--团购产品end-->
                <!-限时抢购 start-->
                <table class="form">
                    <tr>
                        <td><?php echo $entry_flash_sale_product; ?></td>
                        <td><input type="text" name="tab-flash-sale-product" value="" /></td>
                    </tr>
                    <tr>
                        <td>&nbsp;</td>
                        <td><div id="flash-sale-product" class="scrollbox">
                                <?php $class = 'odd'; ?>
                                <?php foreach ($flash_sale_products as $product) { ?>
                                <?php $class = ($class == 'even' ? 'odd' : 'even'); ?>
                                <div id="featured-product<?php echo $product['product_id']; ?>" class="<?php echo $class; ?>"><?php echo $product['name']; ?> <img src="view/image/delete.png" alt="" />
                                    <input type="hidden" value="<?php echo $product['product_id']; ?>" />
                                </div>
                                <?php } ?>
                            </div>
                            <input type="hidden" name="tab_flash_sale_product" value="<?php echo $tab_flash_sale_product; ?>" /></td>
                    </tr>
                </table>
                <!--限时抢购end-->
                <table id="module" class="list">
                    <thead>
                    <tr>
                        <td class="left"><?php echo $entry_limit; ?></td>
                        <td class="left"><?php echo $entry_image; ?></td>
                        <td class="left"><?php echo $entry_layout; ?></td>
                        <td class="left"><?php echo $entry_position; ?></td>
                        <td class="left"><?php echo $entry_status; ?></td>
                        <td class="right"><?php echo $entry_sort_order; ?></td>
                        <td></td>
                    </tr>
                    </thead>
                    <?php $module_row = 0; ?>
                    <?php foreach ($modules as $module) { ?>
                    <tbody id="module-row<?php echo $module_row; ?>">
                    <tr>
                        <td class="left"><input type="text" name="tab_module[<?php echo $module_row; ?>][limit]" value="<?php echo $module['limit']; ?>" size="1" /></td>
                        <td class="left"><input type="text" name="tab_module[<?php echo $module_row; ?>][image_width]" value="<?php echo $module['image_width']; ?>" size="3" />
                            <input type="text" name="tab_module[<?php echo $module_row; ?>][image_height]" value="<?php echo $module['image_height']; ?>" size="3" />
                            <?php if (isset($error_image[$module_row])) { ?>
                            <span class="error"><?php echo $error_image[$module_row]; ?></span>
                            <?php } ?></td>
                        <td class="left"><select name="tab_module[<?php echo $module_row; ?>][layout_id]">
                                <?php foreach ($layouts as $layout) { ?>
                                <?php if ($layout['layout_id'] == $module['layout_id']) { ?>
                                <option value="<?php echo $layout['layout_id']; ?>" selected="selected"><?php echo $layout['name']; ?></option>
                                <?php } else { ?>
                                <option value="<?php echo $layout['layout_id']; ?>"><?php echo $layout['name']; ?></option>
                                <?php } ?>
                                <?php } ?>
                            </select></td>
                        <td class="left"><select name="tab_module[<?php echo $module_row; ?>][position]">
                                <?php if ($module['position'] == 'content_top') { ?>
                                <option value="content_top" selected="selected"><?php echo $text_content_top; ?></option>
                                <?php } else { ?>
                                <option value="content_top"><?php echo $text_content_top; ?></option>
                                <?php } ?>
                                <?php if ($module['position'] == 'content_bottom') { ?>
                                <option value="content_bottom" selected="selected"><?php echo $text_content_bottom; ?></option>
                                <?php } else { ?>
                                <option value="content_bottom"><?php echo $text_content_bottom; ?></option>
                                <?php } ?>
                                <?php if ($module['position'] == 'column_left') { ?>
                                <option value="column_left" selected="selected"><?php echo $text_column_left; ?></option>
                                <?php } else { ?>
                                <option value="column_left"><?php echo $text_column_left; ?></option>
                                <?php } ?>
                                <?php if ($module['position'] == 'column_right') { ?>
                                <option value="column_right" selected="selected"><?php echo $text_column_right; ?></option>
                                <?php } else { ?>
                                <option value="column_right"><?php echo $text_column_right; ?></option>
                                <?php } ?>
                            </select></td>
                        <td class="left"><select name="tab_module[<?php echo $module_row; ?>][status]">
                                <?php if ($module['status']) { ?>
                                <option value="1" selected="selected"><?php echo $text_enabled; ?></option>
                                <option value="0"><?php echo $text_disabled; ?></option>
                                <?php } else { ?>
                                <option value="1"><?php echo $text_enabled; ?></option>
                                <option value="0" selected="selected"><?php echo $text_disabled; ?></option>
                                <?php } ?>
                            </select></td>
                        <td class="right"><input type="text" name="tab_module[<?php echo $module_row; ?>][sort_order]" value="<?php echo $module['sort_order']; ?>" size="3" /></td>
                        <td class="left"><a onclick="$('#module-row<?php echo $module_row; ?>').remove();" class="button"><?php echo $button_remove; ?></a></td>
                    </tr>
                    </tbody>
                    <?php $module_row++; ?>
                    <?php } ?>
                    <tfoot>
                    <tr>
                        <td colspan="6"></td>
                        <td class="left"><a onclick="addModule();" class="button"><?php echo $button_add_module; ?></a></td>
                    </tr>
                    </tfoot>
                </table>
            </form>
        </div>
    </div>
</div>
<script type="text/javascript"><!--
    $('input[name=\'tab-new-product\']').autocomplete({
        delay: 500,
        source: function(request, response) {
            $.ajax({
                url: 'index.php?route=catalog/product/autocomplete&token=<?php echo $token; ?>&filter_name=' +  encodeURIComponent(request.term),
                dataType: 'json',
                success: function(json) {
                    response($.map(json, function(item) {
                        return {
                            label: item.name,
                            value: item.product_id
                        }
                    }));
                }
            });
        },
        select: function(event, ui) {
            $('#featured-product' + ui.item.value).remove();

            $('#featured-product').append('<div id="featured-product' + ui.item.value + '">' + ui.item.label + '<img src="view/image/delete.png" alt="" /><input type="hidden" value="' + ui.item.value + '" /></div>');

            $('#featured-product div:odd').attr('class', 'odd');
            $('#featured-product div:even').attr('class', 'even');

            data = $.map($('#featured-product input'), function(element){
                return $(element).attr('value');
            });

            $('input[name=\'tab_new_product\']').attr('value', data.join());

            return false;
        },
        focus: function(event, ui) {
            return false;
        }
    });

    $('#featured-product div img').live('click', function() {
        $(this).parent().remove();

        $('#featured-product div:odd').attr('class', 'odd');
        $('#featured-product div:even').attr('class', 'even');

        data = $.map($('#featured-product input'), function(element){
            return $(element).attr('value');
        });

        $('input[name=\'tab_new_product\']').attr('value', data.join());
    });

    //品牌推荐

    $('input[name=\'tab-brand-product\']').autocomplete({
        delay: 500,
        source: function(request, response) {
            $.ajax({
                url: 'index.php?route=catalog/product/autocomplete&token=<?php echo $token; ?>&filter_name=' +  encodeURIComponent(request.term),
                dataType: 'json',
                success: function(json) {
                    response($.map(json, function(item) {
                        return {
                            label: item.name,
                            value: item.product_id
                        }
                    }));
                }
            });
        },
        select: function(event, ui) {
            $('#brand-product' + ui.item.value).remove();

            $('#brand-product').append('<div id="brand-product' + ui.item.value + '">' + ui.item.label + '<img src="view/image/delete.png" alt="" /><input type="hidden" value="' + ui.item.value + '" /></div>');

            $('#brand-product div:odd').attr('class', 'odd');
            $('#brand-product div:even').attr('class', 'even');

            data = $.map($('#brand-product input'), function(element){
                return $(element).attr('value');
            });

            $('input[name=\'tab_brand_product\']').attr('value', data.join());

            return false;
        },
        focus: function(event, ui) {
            return false;
        }
    });


    $('#brand-product div img').live('click', function() {
        $(this).parent().remove();

        $('#brand-product div:odd').attr('class', 'odd');
        $('#brand-product div:even').attr('class', 'even');

        data = $.map($('#brand-product input'), function(element){
            return $(element).attr('value');
        });

        $('input[name=\'tab_brand_product\']').attr('value', data.join());
    });
    //品牌推荐 end
    //团购产品

    $('input[name=\'tab-group-buy-product\']').autocomplete({
        delay: 500,
        source: function(request, response) {
            $.ajax({
                url: 'index.php?route=catalog/product/autocomplete&token=<?php echo $token; ?>&filter_name=' +  encodeURIComponent(request.term),
                dataType: 'json',
                success: function(json) {
                    response($.map(json, function(item) {
                        return {
                            label: item.name,
                            value: item.product_id
                        }
                    }));
                }
            });
        },
        select: function(event, ui) {
            $('#group-buy-product' + ui.item.value).remove();

            $('#group-buy-product').append('<div id="group-buy-product' + ui.item.value + '">' + ui.item.label + '<img src="view/image/delete.png" alt="" /><input type="hidden" value="' + ui.item.value + '" /></div>');

            $('#group-buy-product div:odd').attr('class', 'odd');
            $('#group-buy-product div:even').attr('class', 'even');

            data = $.map($('#group-buy-product input'), function(element){
                return $(element).attr('value');
            });

            $('input[name=\'tab_group_buy_product\']').attr('value', data.join());

            return false;
        },
        focus: function(event, ui) {
            return false;
        }
    });
    $('#group-buy-product div img').live('click', function() {
        $(this).parent().remove();

        $('#group-buy-product div:odd').attr('class', 'odd');
        $('#group-buy-product div:even').attr('class', 'even');

        data = $.map($('#group-buy-product input'), function(element){
            return $(element).attr('value');
        });

        $('input[name=\'tab_group_buy_product\']').attr('value', data.join());
    });
    //团购产品 end

//限时抢购 start

$('input[name=\'tab-flash-sale-product\']').autocomplete({
    delay: 500,
    source: function(request, response) {
        $.ajax({
            url: 'index.php?route=catalog/product/autocomplete&token=<?php echo $token; ?>&filter_name=' +  encodeURIComponent(request.term),
            dataType: 'json',
            success: function(json) {
                response($.map(json, function(item) {
                    return {
                        label: item.name,
                        value: item.product_id
                    }
                }));
            }
        });
    },
    select: function(event, ui) {
        $('#flash-sale-product' + ui.item.value).remove();

        $('#flash-sale-product').append('<div id="flash-sale-product' + ui.item.value + '">' + ui.item.label + '<img src="view/image/delete.png" alt="" /><input type="hidden" value="' + ui.item.value + '" /></div>');

        $('#flash-sale-product div:odd').attr('class', 'odd');
        $('#flash-sale-product div:even').attr('class', 'even');

        data = $.map($('#flash-sale-product input'), function(element){
            return $(element).attr('value');
        });

        $('input[name=\'tab_flash_sale_product\']').attr('value', data.join());

        return false;
    },
    focus: function(event, ui) {
        return false;
    }
});
$('#flash-sale-product div img').live('click', function() {
    $(this).parent().remove();

    $('#flash-sale-product div:odd').attr('class', 'odd');
    $('#flash-sale-product div:even').attr('class', 'even');

    data = $.map($('#flash-sale-product input'), function(element){
        return $(element).attr('value');
    });

    $('input[name=\'tab_flash_sale_product\']').attr('value', data.join());
});
//限时抢购 end
    //--></script>
<script type="text/javascript"><!--
    var module_row = <?php echo $module_row; ?>;

    function addModule() {
        html  = '<tbody id="module-row' + module_row + '">';
        html += '  <tr>';
        html += '    <td class="left"><input type="text" name="tab_module[' + module_row + '][limit]" value="5" size="1" /></td>';
        html += '    <td class="left"><input type="text" name="tab_module[' + module_row + '][image_width]" value="80" size="3" /> <input type="text" name="tab_module[' + module_row + '][image_height]" value="80" size="3" /></td>';
        html += '    <td class="left"><select name="tab_module[' + module_row + '][layout_id]">';
    <?php foreach ($layouts as $layout) { ?>
            html += '      <option value="<?php echo $layout['layout_id']; ?>"><?php echo addslashes($layout['name']); ?></option>';
        <?php } ?>
        html += '    </select></td>';
        html += '    <td class="left"><select name="tab_module[' + module_row + '][position]">';
        html += '      <option value="content_top"><?php echo $text_content_top; ?></option>';
        html += '      <option value="content_bottom"><?php echo $text_content_bottom; ?></option>';
        html += '      <option value="column_left"><?php echo $text_column_left; ?></option>';
        html += '      <option value="column_right"><?php echo $text_column_right; ?></option>';
        html += '    </select></td>';
        html += '    <td class="left"><select name="tab_module[' + module_row + '][status]">';
        html += '      <option value="1" selected="selected"><?php echo $text_enabled; ?></option>';
        html += '      <option value="0"><?php echo $text_disabled; ?></option>';
        html += '    </select></td>';
        html += '    <td class="right"><input type="text" name="tab_module[' + module_row + '][sort_order]" value="" size="3" /></td>';
        html += '    <td class="left"><a onclick="$(\'#module-row' + module_row + '\').remove();" class="button"><?php echo $button_remove; ?></a></td>';
        html += '  </tr>';
        html += '</tbody>';

        $('#module tfoot').before(html);

        module_row++;
    }
    //--></script>
<?php echo $footer; ?>