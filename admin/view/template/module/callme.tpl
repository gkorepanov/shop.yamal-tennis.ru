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
    <div class="buttons"><a onclick="$('#form').submit();" class="button"><?php echo $button_save; ?></a><a onclick="location = '<?php echo $cancel; ?>';" class="button"><?php echo $button_cancel; ?></a></div>
  </div>
  <div class="content">
    <form action="<?php echo $action; ?>" method="post" enctype="multipart/form-data" id="form">
      <table style="padding: 10px;"><tbody>
      	<tr><td><?php echo $text_to; ?></td><td><input type="text" size="100px" name="entry_to" value="<?php echo $entry_to; ?>" /></td></tr>
      	<tr><td><?php echo $text_from; ?></td><td><input type="text" size="100px" name="entry_from" value="<?php echo $entry_from; ?>" /></td></tr>
      	<tr><td><?php echo $text_header; ?></td><td><input type="text" size="100px" name="entry_header" value="<?php echo $entry_header; ?>" /></td></tr>
      	<tr><td><?php echo $text_name; ?></td><td><input type="text" size="100px" name="entry_name" value="<?php echo $entry_name; ?>" /></td></tr>
      	<tr><td><?php echo $text_phone; ?></td><td><input type="text" size="100px" name="entry_phone" value="<?php echo $entry_phone; ?>" /></td></tr>
      	<tr><td><?php echo $text_submit; ?></td><td><input type="text" size="100px" name="entry_submit" value="<?php echo $entry_submit; ?>" /></td></tr>
      	<tr><td><?php echo $text_header_title; ?></td><td><input type="text" size="100px" name="entry_header_title" value="<?php echo $entry_header_title; ?>" /></td></tr>
      	<tr><td><?php echo $text_name_title; ?></td><td><input type="text" size="100px" name="entry_name_title" value="<?php echo $entry_name_title; ?>" /></td></tr>
      	<tr><td><?php echo $text_phone_title; ?></td><td><input type="text" size="100px" name="entry_phone_title" value="<?php echo $entry_phone_title; ?>" /></td></tr>
      	<tr><td><?php echo $text_submit_title; ?></td><td><input type="text" size="100px" name="entry_submit_title" value="<?php echo $entry_submit_title; ?>" /></td></tr>
      	<tr><td><?php echo $text_submit_success; ?></td><td><input type="text" size="100px" name="entry_success" value="<?php echo $entry_success; ?>" /></td></tr>
      	<tr><td><?php echo $text_error; ?></td><td><input type="text" size="100px" name="entry_error" value="<?php echo $entry_error; ?>" /></td></tr>
      	<tr><td><?php echo $text_error_name; ?></td><td><input type="text" size="100px" name="entry_error_name" value="<?php echo $entry_error_name; ?>" /></td></tr>
      	<tr><td><?php echo $text_error_phone; ?></td><td><input type="text" size="100px" name="entry_error_phone" value="<?php echo $entry_error_phone; ?>" /></td></tr>
      	<tr><td><?php echo $text_mess_title; ?></td><td><input type="text" size="100px" name="entry_mess_title" value="<?php echo $entry_mess_title; ?>" /></td></tr>
      	<tr><td><?php echo $text_mess_name; ?></td><td><input type="text" size="100px" name="entry_mess_name" value="<?php echo $entry_mess_name; ?>" /></td></tr>
      	<tr><td><?php echo $text_mess_phone; ?></td><td><input type="text" size="100px" name="entry_mess_phone" value="<?php echo $entry_mess_phone; ?>" /></td></tr>
      	<tr><td><?php echo $text_tc; ?></td><td><input type="text" size="100px" name="entry_tc" value="<?php echo $entry_tc; ?>" /></td></tr>
      	<tr><td><?php echo $text_vfb; ?></td><td><input type="text" size="100px" name="entry_vfb" value="<?php echo $entry_vfb; ?>" /></td></tr>
      	<tr><td><?php echo $text_vfe; ?></td><td><input type="text" size="100px" name="entry_vfe" value="<?php echo $entry_vfe; ?>" /></td></tr>
      	</td></tr></tbody>
      </table>

      <table id="module" class="list">
        <thead>
          <tr>
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
            <td class="left"><select name="callme_module[<?php echo $module_row; ?>][layout_id]">
                <?php foreach ($layouts as $layout) { ?>
                <?php if ($layout['layout_id'] == $module['layout_id']) { ?>
                <option value="<?php echo $layout['layout_id']; ?>" selected="selected"><?php echo $layout['name']; ?></option>
                <?php } else { ?>
                <option value="<?php echo $layout['layout_id']; ?>"><?php echo $layout['name']; ?></option>
                <?php } ?>
                <?php } ?>
              </select></td>
            <td class="left"><select name="callme_module[<?php echo $module_row; ?>][position]">
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
            <td class="left"><select name="callme_module[<?php echo $module_row; ?>][status]">
                <?php if ($module['status']) { ?>
                <option value="1" selected="selected"><?php echo $text_enabled; ?></option>
                <option value="0"><?php echo $text_disabled; ?></option>
                <?php } else { ?>
                <option value="1"><?php echo $text_enabled; ?></option>
                <option value="0" selected="selected"><?php echo $text_disabled; ?></option>
                <?php } ?>
              </select></td>
            <td class="right"><input type="text" name="callme_module[<?php echo $module_row; ?>][sort_order]" value="<?php echo $module['sort_order']; ?>" size="3" /></td>
            <td class="left"><a onclick="$('#module-row<?php echo $module_row; ?>').remove();" class="button"><?php echo $button_remove; ?></a></td>
          </tr>
        </tbody>
        <?php $module_row++; ?>
        <?php } ?>
        <tfoot>
          <tr>
            <td colspan="4"></td>
            <td class="left"><a onclick="addModule();" class="button"><?php echo $button_add_module; ?></a></td>
          </tr>
        </tfoot>
      </table>
    </form>
  </div>
</div>
<script type="text/javascript"><!--
var module_row = <?php echo $module_row; ?>;

function addModule() {	
	html  = '<tbody id="module-row' + module_row + '">';
	html += '  <tr>';
	html += '    <td class="left"><select name="callme_module[' + module_row + '][layout_id]">';
	<?php foreach ($layouts as $layout) { ?>
	html += '      <option value="<?php echo $layout["layout_id"]; ?>"><?php echo $layout["name"]; ?></option>';
	<?php } ?>
	html += '    </select></td>';
	html += '    <td class="left"><select name="callme_module[' + module_row + '][position]">';
	html += '      <option value="content_top"><?php echo $text_content_top; ?></option>';
	html += '      <option value="content_bottom"><?php echo $text_content_bottom; ?></option>';
	html += '      <option value="column_left"><?php echo $text_column_left; ?></option>';
	html += '      <option value="column_right"><?php echo $text_column_right; ?></option>';
	html += '    </select></td>';
	html += '    <td class="left"><select name="callme_module[' + module_row + '][status]">';
    html += '      <option value="1" selected="selected"><?php echo $text_enabled; ?></option>';
    html += '      <option value="0"><?php echo $text_disabled; ?></option>';
    html += '    </select></td>';
	html += '    <td class="right"><input type="text" name="callme_module[' + module_row + '][sort_order]" value="" size="3" /></td>';
	html += '    <td class="left"><a onclick="$(\'#module-row' + module_row + '\').remove();" class="button"><?php echo $button_remove; ?></a></td>';
	html += '  </tr>';
	html += '</tbody>';
	
	$('#module tfoot').before(html);
	
	module_row++;
}
//--></script>
<?php echo $footer; ?>