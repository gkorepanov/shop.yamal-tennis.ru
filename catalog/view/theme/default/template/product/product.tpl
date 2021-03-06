﻿<?php echo $header; ?><?php echo $column_left; ?>
<div id="content"><?php echo $content_top; ?>
  <div class="breadcrumb">
    <?php foreach ($breadcrumbs as $breadcrumb) { ?>
    <?php echo $breadcrumb['separator']; ?><a href="<?php echo $breadcrumb['href']; ?>"><?php echo $breadcrumb['text']; ?></a>
    <?php } ?>
  </div>
  <h1><?php echo $heading_title; ?></h1>
  <div class="product-info">
    <?php if ($thumb || $images) { ?>
    <div class="left">
      <?php if ($thumb) { ?>
      
			
				<div class="image"><a href="<?php echo $popup; ?>" title="<?php echo $heading_title; ?>" class="colorbox" rel="colorbox"><?php if ($upc) { ?> <img src="catalog/view/theme/default/image/sticker_<?php echo $upc; ?>.png" id="sticker" /><?php } ?><img src="<?php echo $thumb; ?>" title="<?php echo $heading_title; ?>" alt="<?php echo $heading_title; ?>" id="image" /></a></div>
			
			
      <?php } ?>
      <?php if ($images) { ?>
      <div class="image-additional">
        <?php foreach ($images as $image) { ?>
        <a href="<?php echo $image['popup']; ?>" title="<?php echo $heading_title; ?>" class="colorbox" rel="colorbox"><img src="<?php echo $image['thumb']; ?>" title="<?php echo $heading_title; ?>" alt="<?php echo $heading_title; ?>" /></a>
        <?php } ?>
      </div>
      <?php } ?>
    </div>
    <?php } ?>
    <div class="right">
      <div class="description">
      <img src="<?php echo $mimage; ?>" title="<?php echo $manufacturer; ?>" />
        <span><?php echo $text_model; ?></span> <?php echo $model; ?><br />
        <?php if ($reward) { ?>
        <span><?php echo $text_reward; ?></span> <?php echo $reward; ?><br />
        <?php } ?>
        <?php /*if($has_option == 0 && $subtract != 0){echo'<span>'.$text_stock.'</span>'.$stock;}*/?> 
        <?php if ($manufacturer) { ?>
        <span><?php echo $text_manufacturer; ?></span> <a href="<?php echo $manufacturers; ?>"><?php echo $manufacturer; ?></a><br />
        <?php } ?>
        </div>
      <?php if ($price) { ?>
      <div class="price"><?php echo $text_price; ?>
        <?php if (!$special) { ?>
        <?php echo $price; ?>
        <?php } else { ?>
        <span class="price-old"><?php echo $price; ?></span> <span class="price-new"><?php echo $special; ?></span>
        <?php } ?>
        <br />
        <?php if ($tax) { ?>
        <span class="price-tax"><?php echo $text_tax; ?> <?php echo $tax; ?></span><br />
        <?php } ?>
        <?php if ($points) { ?>
        <span class="reward"><small><?php echo $text_points; ?> <?php echo $points; ?></small></span><br />
        <?php } ?>
        <?php if ($discounts) { ?>
        <br />
        <div class="discount">
          <?php foreach ($discounts as $discount) { ?>
          <?php echo sprintf($text_discount, $discount['quantity'], $discount['price']); ?><br />
          <?php } ?>
        </div>
        <?php } ?>
      </div>
      <?php } ?>
      <?php /*if (! $options) { ?>
      <div class="warning">Предзаказ</div>
      <?php }*/ ?>
      <?php if ($options) { ?>
      <div class="options">
	<div id="optionsamples"> 
		<div id="sampleoptionstock"><div class="sampleoption"></div> <span>- В наличии</span></div>
		<div id="sampleoptionnostock"><div class="sampleoption"></div> <span>- На заказ</span></div>
	</div>

        <h2><?php echo $text_option; ?></h2>
        <?php $i = 0; foreach ($options as $option) { ?>
        <?php if ($option['type'] == 'select') { $i++; ?>
        <div id="option-<?php echo $option['product_option_id']; ?>" class="option">
          <?php if ($option['required']) { ?>
          <span class="required">*</span>
          <?php } ?>
          <b><?php echo $option['name']; ?>:</b><br />
          <select name="option[<?php echo $option['product_option_id']; ?>]" class="optionChoice">
            <option value=""><?php echo $text_select; ?></option>
            <?php foreach ($option['option_value'] as $option_value) { ?>
            <option value="<?php echo $option_value['product_option_value_id']; ?>"><?php echo $option_value['name']; ?>
            <?php if ($option_value['price']) { ?>
            
            <?php } ?>
            </option>
            <?php } ?>
          </select>
        </div>
        <br />
        <?php } ?>
        
        
        <?php if ($option['type'] == 'radio') { $i++;?>
        <div id="option-<?php echo $option['product_option_id']; ?>" value="<?php echo ($i-1) ?>" class="option option-<?php echo ($i-1) ?>">
        
        	<b><?php echo $option['name']; ?>:</b><br />
          <?php if (isset($previous_name)) { ?>
          	<span>Сначала выберите параметр «<?php echo $previous_name; ?>».</span>
          <?php } ?>
          
          <?php $previous_name = $option['name']; ?>
          <?php foreach ($option['option_value'] as $option_value) { ?>
          <div name="option[<?php echo $option['product_option_id']; ?>]" value="<?php echo $option_value['product_option_value_id']; ?>" id="option-value-<?php echo $option_value['product_option_value_id']; ?>" class="optionChoice off" />
          <?php echo $option_value['name']; ?>
          </div>

          <?php } ?>
        </div>
        
        
        
        
       
        <?php } ?>
        <?php if ($option['type'] == 'checkbox') { ?>
        <div id="option-<?php echo $option['product_option_id']; ?>" class="option">
          <?php if ($option['required']) { ?>
          <span class="required">*</span>
          <?php } ?>
          <b><?php echo $option['name']; ?>:</b><br />
          <?php foreach ($option['option_value'] as $option_value) { ?>
          <input type="checkbox" name="option[<?php echo $option['product_option_id']; ?>][]" value="<?php echo $option_value['product_option_value_id']; ?>" id="option-value-<?php echo $option_value['product_option_value_id']; ?>" />
          <label for="option-value-<?php echo $option_value['product_option_value_id']; ?>"><?php echo $option_value['name']; ?>
            <?php if ($option_value['price']) { ?>
            
            <?php } ?>
          </label>
          <br />
          <?php } ?>
        </div>
        <br />
        <?php } ?>
        <?php if ($option['type'] == 'image') { $i++; ?>
        <div id="option-<?php echo $option['product_option_id']; ?>" class="option">
          <?php if ($option['required']) { ?>
          <span class="required">*</span>
          <?php } ?>
          <b><?php echo $option['name']; ?>:</b><br />
          <table class="option-image">
            <?php foreach ($option['option_value'] as $option_value) { ?>
            <tr>
              <td style="width: 1px;"><input type="radio" name="option[<?php echo $option['product_option_id']; ?>]" value="<?php echo $option_value['product_option_value_id']; ?>" id="option-value-<?php echo $option_value['product_option_value_id']; ?>" class="optionChoice" /></td>
              <td><label for="option-value-<?php echo $option_value['product_option_value_id']; ?>"><img src="<?php echo $option_value['image']; ?>" alt="<?php echo $option_value['name'] . ($option_value['price'] ? ' ' . $option_value['price_prefix'] . $option_value['price'] : ''); ?>" /></label></td>
              <td><label for="option-value-<?php echo $option_value['product_option_value_id']; ?>"><?php echo $option_value['name']; ?>
                  <?php if ($option_value['price']) { ?>
                  
                  <?php } ?>
                </label></td>
            </tr>
            <?php } ?>
          </table>
        </div>
        <br />
        <?php } ?>
        <?php if ($option['type'] == 'text') { ?>
        <div id="option-<?php echo $option['product_option_id']; ?>" class="option">
          <?php if ($option['required']) { ?>
          <span class="required">*</span>
          <?php } ?>
          <b><?php echo $option['name']; ?>:</b><br />
          <input type="text" name="option[<?php echo $option['product_option_id']; ?>]" value="<?php echo $option['option_value']; ?>" />
        </div>
        <br />
        <?php } ?>
        <?php if ($option['type'] == 'textarea') { ?>
        <div id="option-<?php echo $option['product_option_id']; ?>" class="option">
          <?php if ($option['required']) { ?>
          <span class="required">*</span>
          <?php } ?>
          <b><?php echo $option['name']; ?>:</b><br />
          <textarea name="option[<?php echo $option['product_option_id']; ?>]" cols="40" rows="5"><?php echo $option['option_value']; ?></textarea>
        </div>
        <br />
        <?php } ?>
        <?php if ($option['type'] == 'file') { ?>
        <div id="option-<?php echo $option['product_option_id']; ?>" class="option">
          <?php if ($option['required']) { ?>
          <span class="required">*</span>
          <?php } ?>
          <b><?php echo $option['name']; ?>:</b><br />
          <input type="button" value="<?php echo $button_upload; ?>" id="button-option-<?php echo $option['product_option_id']; ?>" class="button">
          <input type="hidden" name="option[<?php echo $option['product_option_id']; ?>]" value="" />
        </div>
        <br />
        <?php } ?>
        <?php if ($option['type'] == 'date') { ?>
        <div id="option-<?php echo $option['product_option_id']; ?>" class="option">
          <?php if ($option['required']) { ?>
          <span class="required">*</span>
          <?php } ?>
          <b><?php echo $option['name']; ?>:</b><br />
          <input type="text" name="option[<?php echo $option['product_option_id']; ?>]" value="<?php echo $option['option_value']; ?>" class="date" />
        </div>
        <br />
        <?php } ?>
        <?php if ($option['type'] == 'datetime') { ?>
        <div id="option-<?php echo $option['product_option_id']; ?>" class="option">
          <?php if ($option['required']) { ?>
          <span class="required">*</span>
          <?php } ?>
          <b><?php echo $option['name']; ?>:</b><br />
          <input type="text" name="option[<?php echo $option['product_option_id']; ?>]" value="<?php echo $option['option_value']; ?>" class="datetime" />
        </div>
        <br />
        <?php } ?>
        <?php if ($option['type'] == 'time') { ?>
        <div id="option-<?php echo $option['product_option_id']; ?>" class="option">
          <?php if ($option['required']) { ?>
          <span class="required">*</span>
          <?php } ?>
          <b><?php echo $option['name']; ?>:</b><br />
          <input type="text" name="option[<?php echo $option['product_option_id']; ?>]" value="<?php echo $option['option_value']; ?>" class="time" />
        </div>
        <br />
        <?php } ?>
        <?php } ?>
<input type="hidden" name="optionNumbers" value="<?php echo $i; ?>" id="optionNumbers" />
      </div>
      <?php } ?>
      <div class="cart">
<div id="product-cart">
        <div><?php echo $text_qty; ?>
          <input type="text" name="quantity" size="2" value="<?php echo $minimum; ?>" />
          <input type="hidden" name="product_id" size="2" value="<?php echo $product_id; ?>" />
          &nbsp;
          <input type="button" value="<?php echo $button_cart; ?>" id="button-cart" class="button" />
        </div>
        <?php if ($minimum > 1) { ?>
        <div class="minimum"><?php echo $text_minimum; ?></div>
        <?php } ?>
      </div>
      <?php if ($review_status) { ?>
      <div class="review">
      	<div class="review-stars stars-<?php echo $rating; ?>"></div>
        <a onclick="$('a[href=\'#tab-review\']').trigger('click');"><?php echo $reviews; ?></a><a onclick="$('a[href=\'#tab-review\']').trigger('click');"><?php echo $text_write; ?></a>
        
      </div>
      <?php } ?>
    </div>
  </div>
  <div id="tabs" class="htabs"><a href="#tab-description"><?php echo $tab_description; ?></a>
    <?php if ($review_status) { ?>
    <a href="#tab-review"><?php echo $tab_review; ?></a>
    <?php } ?>
    
  </div>
  <div id="tab-description" class="tab-content"><?php echo $description; ?>
  <?php if ($attribute_groups) { ?>
    
    
  <table class="attribute">
      <?php foreach ($attribute_groups as $attribute_group) { ?>
      <thead>
        <tr>
          <td colspan="2"><?php echo $attribute_group['name']; ?></td>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($attribute_group['attribute'] as $attribute) { ?>
        <tr>
          <td><?php echo $attribute['name']; ?></td>
          <td><?php echo $attribute['text']; ?></td>
        </tr>
        <?php } ?>
      </tbody>
      <?php } ?>
    </table>
    <?php } ?>
    </div>
  
  <?php if ($review_status) { ?>
  <div id="tab-review" class="tab-content">
    <div id="review"></div>
    <h2 id="review-title"><?php echo $text_write; ?></h2>
    <b><?php echo $entry_name; ?></b><br />
    <input type="text" name="name" value="" />
    <br />
    <br />
    <b><?php echo $entry_review; ?></b>
    <textarea name="text" cols="40" rows="8" style="width: 98%;"></textarea>
    <span style="font-size: 11px;"><?php echo $text_note; ?></span><br />
    <br />
    <b><?php echo $entry_rating; ?></b> <span><?php echo $entry_bad; ?></span>&nbsp;
    <input type="radio" name="rating" value="1" />
    &nbsp;
    <input type="radio" name="rating" value="2" />
    &nbsp;
    <input type="radio" name="rating" value="3" />
    &nbsp;
    <input type="radio" name="rating" value="4" />
    &nbsp;
    <input type="radio" name="rating" value="5" />
    &nbsp;<span><?php echo $entry_good; ?></span><br />
    <br />
    <b><?php echo $entry_captcha; ?></b><br />
    <input type="text" name="captcha" value="" />
    <br />
    <img src="index.php?route=product/product/captcha" alt="" id="captcha" /><br />
    <br />
    <div class="buttons">
      <div class="right"><a id="button-review" class="button"><?php echo $button_continue; ?></a></div>
    </div>
  </div>
  <?php } ?>
  
    </div>
    
  <?php if ($products) { ?>
  <div class="box">
  	<div class="box-heading">
    	<?php echo $tab_related; ?> (<?php echo count($products); ?>)
    </div>
    <div class="box-content">
    <div class="box-product">
    	<?php foreach ($products as $product) { ?>
      <div>
        <?php if ($product['thumb']) { ?>
        
			
				<div class="image"><a href="<?php echo $product['href']; ?>"><?php if ($product['upc']) { ?><img src="catalog/view/theme/default/image/sticker_<?php echo $product['upc']; ?>.png" id="sticker" /><?php } ?><img src="<?php echo $product['thumb']; ?>" title="<?php echo $product['name']; ?>" alt="<?php echo $product['name']; ?>" /></a></div>
			
			
        <?php } ?>
        <div class="cart">
        		<a href="<?php echo $product['href']; ?>" class="button">Подробнее</a>
      	</div>
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
    <?php } ?>
    <?php echo $content_bottom; ?></div>
<script type="text/javascript"><!--
$('.colorbox').colorbox({
	overlayClose: true,
	opacity: 0.5
});
//--></script> 

<?php if ($options) { ?>
<script type="text/javascript" src="catalog/view/javascript/jquery/ajaxupload.js"></script>
<?php $i = 0; foreach ($options as $option) { ?>
<?php if ($option['type'] == 'file') { ?>
<script type="text/javascript"><!--
new AjaxUpload('#button-option-<?php echo $option['product_option_id']; ?>', {
	action: 'index.php?route=product/product/upload',
	name: 'file',
	autoSubmit: true,
	responseType: 'json',
	onSubmit: function(file, extension) {
		$('#button-option-<?php echo $option['product_option_id']; ?>').after('<img src="catalog/view/theme/default/image/loading.gif" class="loading" style="padding-left: 5px;" />');
		$('#button-option-<?php echo $option['product_option_id']; ?>').attr('disabled', true);
	},
	onComplete: function(file, json) {
		$('#button-option-<?php echo $option['product_option_id']; ?>').attr('disabled', false);
		
		$('.error').remove();
		
		if (json['success']) {
			alert(json['success']);
			
			$('input[name=\'option[<?php echo $option['product_option_id']; ?>]\']').attr('value', json['file']);
		}
		
		if (json['error']) {
			$('#option-<?php echo $option['product_option_id']; ?>').after('<span class="error">' + json['error'] + '</span>');
		}
		
		$('.loading').remove();	
	}
});
//--></script>
<?php } ?>
<?php } ?>
<?php } ?>
<script type="text/javascript"><!--
$('#review .pagination a').live('click', function() {
	$('#review').fadeOut('slow');
		
	$('#review').load(this.href);
	
	$('#review').fadeIn('slow');
	
	return false;
});			

$('#review').load('index.php?route=product/product/review&product_id=<?php echo $product_id; ?>');

$('#button-review').bind('click', function() {
	$.ajax({
		url: 'index.php?route=product/product/write&product_id=<?php echo $product_id; ?>',
		type: 'post',
		dataType: 'json',
		data: 'name=' + encodeURIComponent($('input[name=\'name\']').val()) + '&text=' + encodeURIComponent($('textarea[name=\'text\']').val()) + '&rating=' + encodeURIComponent($('input[name=\'rating\']:checked').val() ? $('input[name=\'rating\']:checked').val() : '') + '&captcha=' + encodeURIComponent($('input[name=\'captcha\']').val()),
		beforeSend: function() {
			$('.success, .warning').remove();
			$('#button-review').attr('disabled', true);
			$('#review-title').after('<div class="attention"><img src="catalog/view/theme/default/image/loading.gif" alt="" /> <?php echo $text_wait; ?></div>');
		},
		complete: function() {
			$('#button-review').attr('disabled', false);
			$('.attention').remove();
		},
		success: function(data) {
			if (data['error']) {
				$('#review-title').after('<div class="warning">' + data['error'] + '</div>');
			}
			
			if (data['success']) {
				$('#review-title').after('<div class="success">' + data['success'] + '</div>');
								
				$('input[name=\'name\']').val('');
				$('textarea[name=\'text\']').val('');
				$('input[name=\'rating\']:checked').attr('checked', '');
				$('input[name=\'captcha\']').val('');
			}
		}
	});
});
//--></script> 
<script type="text/javascript"><!--
$('#tabs a').tabs();
//--></script> 
<script type="text/javascript" src="catalog/view/javascript/jquery/ui/jquery-ui-timepicker-addon.js"></script> 
<script type="text/javascript"><!--
if ($.browser.msie && $.browser.version == 6) {
	$('.date, .datetime, .time').bgIframe();
}

$('.date').datepicker({dateFormat: 'yy-mm-dd'});
$('.datetime').datetimepicker({
	dateFormat: 'yy-mm-dd',
	timeFormat: 'h:m'
});
$('.time').timepicker({timeFormat: 'h:m'});
//--></script>
<?php echo $column_right; ?> 



<script type="text/javascript">
var optionlevel = 0;
var levels = $('#optionNumbers').val();
var cur_options = [];

$('#button-cart').bind('click', function() {
	var i = 0;
	var str='';
	//if (optionlevel = 
	
	
	for(i=0; i < optionlevel; i++) {
			str = str + "&" + $(".option-"+i).attr("id").replace("option-", "option[")+"]="+cur_options[i];
	}
	str = "quantity="+$(".product-info input[type=\'text\']").attr("value")+"&product_id="+$("#product-cart input[type=\'hidden\']").attr("value")+str;
	
	//console.log($('.product-info input[type=\'text\'], .product-info input[type=\'hidden\'], .product-info .optionChoice.active, .product-info input[type=\'checkbox\']:checked, .product-info select, .product-info textarea'));
	$.ajax({
		url: 'index.php?route=checkout/cart/add',
		type: 'post',
		data: str, //$('.product-info input[type=\'text\'], .product-info input[type=\'hidden\'], .product-info .optionChoice.active, .product-info input[type=\'checkbox\']:checked, .product-info select, .product-info textarea'),
		dataType: 'json',
		success: function(json) {
			$('.success, .warning, .attention, information, .error').remove();
			
			if (json['error']) {
				if (json['error']['option']) {
					for (i in json['error']['option']) {
						$('#option-' + i).after('<span class="error">' + json['error']['option'][i] + '</span>');
					}
				}
			} 
			
			if (json['success']) {
				$('#notification').html('<div class="success" style="display: none;">' + json['success'] + '<img src="catalog/view/theme/default/image/close.png" alt="" class="close" /></div>');
					
				$('.success').fadeIn('slow');
					
				$('#cart-total').html(json['total']);
				$('#cartprice').html(json['total_price']);
				
				$('html, body').animate({ scrollTop: 0 }, 'slow'); 
			}	
		}
	});
});
function update_options(chosen) {
	str = '';
	if (chosen != 0) {
		cur_options[optionlevel-1] = chosen;
		str = str + cur_options[0];
	}
	
	for(var i=1; i < optionlevel; i++) {
			str = str + ":" + cur_options[i];
	}
	
	console.log('var=' + str + '&product_id=<?php echo $product_id; ?>'+'&level='+optionlevel);
	if (optionlevel == levels)
		finish_options(str);
	else {
	
	$.ajax({
            type: 'POST',
            url: 'index.php?route=openstock/openstock/optionStatus',
            dataType: 'json',
            data: 'var=' + str + '&product_id=<?php echo $product_id; ?>'+'&level='+optionlevel,
            beforeSend: function() {
                $('.success, .warning').remove();
                $('.options').after('<div class="loading"><?php echo $text_checking_options; ?></div>');
                //$('#product-cart').hide();
            },
            complete: function() {},
            success: function(data) {
            	
                setTimeout(function(){
                		console.log(data);
                		console.log(data.list);
                		list = data.list;
                		if (data.status == "success") {
		                		$(".option-"+optionlevel+" > div").each(function() {
		                			$(this).addClass("nostock");
							$(this).removeClass("off");
		                		});
		                		
		                		$(".option-"+optionlevel+" > div").each(function() {
		                			current = $( this );
							if ($.isArray(list)) {
				        			list.forEach(function(item, i, arr) {
				        				console.log(item);
				        				console.log(current);
				        				console.log(current.attr("value"));
				        				if (item == current.attr("value")) {
				        					current.removeClass("nostock");
				        				}
				        				
				        			});
							}
		                		});
		                			
		                		$(".option-"+optionlevel+" > span").addClass("off");
		                		$(".option-"+optionlevel+" .optionChoice").removeClass("active");
		                		for(var i=optionlevel+1; i<=levels; i++) {
		                				$(".option-"+i+" > span").removeClass("off");
		                				$(".option-"+i+" .optionChoice").addClass("off").removeClass("nostock");
		                		}
		                		
		                		$('.loading').remove();
                		}
                		else {
                			$('.loading').removeClass('loading').addClass('warning').empty().html('<img src="http://shop.yamal-tennis.ru/catalog/view/theme/default/image/not_ok.png" alt="Ошибка!"><?php echo $text_nostock; ?>');
					//$('.options').remove();
                			
                		}
                }, 500);
            }
	});
}}


$(function() {
	$('.success, .warning').remove();
	console.log("loaded!");
	if (levels > 0) 
		update_options(0);
	else if(<?php echo $stock;?> > 0) {
		$('.product-info .price').after('<div class="success"><img src="http://shop.yamal-tennis.ru/catalog/view/theme/default/image/ok.png" alt="Успешно!"><?php echo $text_instock;?></div>');
	}
	else
	$('.product-info .price').after('<div class="warning"><img src="http://shop.yamal-tennis.ru/catalog/view/theme/default/image/not_ok.png" alt="Ошибка!"><?php echo $text_nostock;?></div>');
});
	
	
$('.optionChoice').click(function() {
	if (! $(this).hasClass("off")) {
		$(this).parent().children().removeClass("active");
		$(this).addClass("active");
		optionlevel = Number($(this).parent().attr("value"))+1;
		
		update_options(Number($(this).attr("value")));
		
	}
});



/*$('.optionChoice').hover(function() {
	if ($(this).parent().val() <= optionlevel ) {
		$(this).addClass("hover");
		alert($(this).parent().val());
	}
});

$('.optionChoice').mouseleave(function() {
	$(this).removeClass("hover");
});*/



function finish_options(optionStr){
    var i = parseInt(0);
    var optionNumbers = $('#optionNumbers').val();
    var imgThumbOriginal = '<?php echo $thumb; ?>';
    var imgPopOriginal = '<?php echo $popup; ?>';
    var stringPrice = ''; var stringDiscount = '';
    
	    $.ajax({
            type: 'POST',
            url: 'index.php?route=openstock/openstock/optionFinish',
            dataType: 'json',
            data: 'var=' + optionStr + '&product_id=<?php echo $product_id; ?>',
            beforeSend: function() {
                $('.success, .warning').remove();
                $('.options').after('<div class="loading"><?php echo $text_checking_options; ?></div>');
                
            },
            complete: function() {},
            success: function(data) {
                setTimeout(function(){
                    //product price label
                    stringPrice = '<?php echo $text_price; ?> ';

                    //if the original price is greater then its on special
                    if(data.data.originaltax != data.data.pricetax){
                        stringPrice += '<span class="price-old">'+data.data.originaltax+'</span> ';
                    }

                    //product price and product excluding tax
                    stringPrice += data.data.pricetax+'<br /><?php if ($tax) { ?><span class="price-tax"><?php echo $text_tax; ?> '+data.data.price+'</span><?php } ?>';

                  
                    
                    if (data.error) {
                        $('.loading').removeClass('loading').addClass('warning').empty().text(data.error);
                        $('#product-cart').hide();
                    }

                    if (data.success) {
                        $('.loading').removeClass('loading').addClass('success').empty().html('<img src="http://shop.yamal-tennis.ru/catalog/view/theme/default/image/ok.png" alt="Успешно!">'+data.success);
                        $('.product-info .price').html(stringPrice).append(stringDiscount).show();
                        $('#product-cart').show();
                    }

                    if (data.nostock) {
                        $('.loading').removeClass('loading').addClass('warning').empty().html('<img src="http://shop.yamal-tennis.ru/catalog/view/theme/default/image/not_ok.png" alt="Ошибка!">'+data.nostock);
                        $('.product-info .price').html(stringPrice).append(stringDiscount).show();

                        if(data.nostockcheckout == 1){
                            $('#product-cart').show();
                        }else{
                            $('#product-cart').hide();
                        }
                    }

                    if (data.notactive) {
                        $('.loading').removeClass('loading').addClass('warning').empty().text(data.notactive);
                        $('.product-info .price').html(stringPrice).append(stringDiscount).show();
                        $('#product-cart').hide();
                    }
                    
                    // Image swapping for variant 
                    /*if(data.data.image !='')
                    {
                        $('#image').attr('src', data.data.thumb);
                        $('.image a').attr('href', data.data.pop);
                    }else{
                        $('#image').attr('src', imgThumbOriginal);
                        $('.image a').attr('href', imgPopOriginal);
                    }*/
                }, 200);
            }
	    });
}



</script>
<?php echo $footer; ?>
