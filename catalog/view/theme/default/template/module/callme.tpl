<style>
#callmeform {
border-left: 1px solid <?php echo $entry_vfb; ?>;
}
#viewform, .callme_submit {
color: <?php echo $entry_tc; ?>;
background: <?php echo $entry_vfb; ?>;
background: -moz-linear-gradient(top, <?php echo $entry_vfb; ?>, <?php echo $entry_vfe; ?>);
background: -webkit-gradient(linear, left top, left bottom, color-stop(0%,<?php echo $entry_vfb; ?>), color-stop(100%,<?php echo $entry_vfe; ?>));
background: -webkit-linear-gradient(top, <?php echo $entry_vfb; ?>, <?php echo $entry_vfe; ?>);
background: -o-linear-gradient(top, <?php echo $entry_vfb; ?>, <?php echo $entry_vfe; ?>);
background: -ms-linear-gradient(top, <?php echo $entry_vfb; ?>, <?php echo $entry_vfe; ?>);
background: linear-gradient(top, <?php echo $entry_vfb; ?>, <?php echo $entry_vfe; ?>);
}

.callmeform_hover {
background: #a00!important;
background: -moz-linear-gradient(top, <?php echo $entry_vfe; ?>, <?php echo $entry_vfb; ?>)!important;
background: -webkit-gradient(linear, left top, left bottom, color-stop(0%,<?php echo $entry_vfe; ?>), color-stop(100%,<?php echo $entry_vfb; ?>))!important;
background: -webkit-linear-gradient(top, <?php echo $entry_vfe; ?>, <?php echo $entry_vfb; ?>)!important;
background: -o-linear-gradient(top, <?php echo $entry_vfe; ?>, <?php echo $entry_vfb; ?>)!important;
background: -ms-linear-gradient(top, <?php echo $entry_vfe; ?>, <?php echo $entry_vfb; ?>)!important;
background: linear-gradient(top, <?php echo $entry_vfe; ?>, <?php echo $entry_vfb; ?>)!important;
}
</style>

<div class="box">
	<div class="callme">
		<button id="viewform" title="<?php echo $entry_header_title; ?>"><?php echo $entry_header; ?></button>
   </div>
   <div class="hide-on" id="callmeform">
         <input class="text" id="cname" name="cname" maxlength="32" size="30" type="text" autocomplete="on" placeholder="<?php echo $entry_name; ?>" title="<?php echo $entry_name_title; ?>" value="" onchange="changeName()" />
         <input class="text" id="cphone" maxlength="20" size="30" type="text" autocomplete="on" placeholder="<?php echo $entry_phone; ?>" title="<?php echo $entry_phone_title; ?>" onchange="changePhone()" />
         <button class="callme_submit"  title="<?php echo $entry_submit_title; ?>"><?php echo $entry_submit; ?></button>
   </div>
	<div id="callme_result">
	</div>
</div>

<script type="text/javascript"><!--

var error_name = true;
var error_phone = true;

$(function(){
$("#viewform").click(function(){
	$("#callmeform").slideToggle("slow");
});

$("#viewform").hover(
	function () {
		$(this).addClass("callmeform_hover");
	},
	function () {
		$(this).removeClass("callmeform_hover");
	}
);

});

function show(){
	$.ajax({
		type: "POST",
		url: "index.php?route=module/callme/sendmail",
		data: {cphone: $("#cphone").val(), cname: $("#cname").val()},
		success: function(html){
			$("#callme_result").html(html);
			setTimeout( function(){ $("#viewform").click(); }, 100);
			setTimeout( function(){ $("#callme_result").slideToggle("slow"); }, 30000);
		}
	});
}

function changeName()	{
	if (!/([a-zа-яё]{2,32} *){1,3}/i.test($("#cname").val()))	{
		alert('<?php echo $entry_error_name; ?>');
		error_name = true;
	}	else	{
		error_name = false;
	}
}

function changePhone()	{
	if (!/^((8|\+7)[\- ]?)?(\(?\d{3}\)?[\- ]?)?[\d\- ]{7,10}$/.test($("#cphone").val()))	{
		alert('<?php echo $entry_error_phone; ?>');
		error_phone = true;
	}	else	{
		error_phone = false;
	}
}

$(document).ready(function(){
	document.getElementById('cname').value = "";
	document.getElementById('cphone').value = "";
	inputPlaceholder (document.getElementById("cname"));
	inputPlaceholder (document.getElementById("cphone"));

	$(".callme_submit").click(function(){
		var er_mess = "";
		if (!error_name)	{
			if (!error_phone)	{
				show();
			}
		}
	});
	
});

function inputPlaceholder (input, color) {

	if (!input) return null;

	// Do nothing if placeholder supported by the browser (Webkit, Firefox 3.7)
	if (input.placeholder && 'placeholder' in document.createElement(input.tagName)) return input;

	color = color || '#AAA';
	var default_color = input.style.color;
	var placeholder = input.getAttribute('placeholder');
	if (input.value === '' || input.value == placeholder) {
		input.value = placeholder;
		input.style.color = color;
		input.setAttribute('data-placeholder-visible', 'true');
	}

	var add_event = /*@cc_on'attachEvent'||@*/'addEventListener';

	input[add_event](/*@cc_on'on'+@*/'focus', function(){
	 input.style.color = default_color;
	 if (input.getAttribute('data-placeholder-visible')) {
		 input.setAttribute('data-placeholder-visible', '');
		 input.value = '';
	 }
	}, false);

	input[add_event](/*@cc_on'on'+@*/'blur', function(){
		if (input.value === '') {
			input.setAttribute('data-placeholder-visible', 'true');
			input.value = placeholder;
			input.style.color = color;
		} else {
			input.style.color = default_color;
			input.setAttribute('data-placeholder-visible', '');
		}
	}, false);

	input.form && input.form[add_event](/*@cc_on'on'+@*/'submit', function(){
		if (input.getAttribute('data-placeholder-visible')) {
			input.value = '';
		}
	}, false);

	return input;
}
//--></script>