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
<?php if ($success) { ?>
<div class="success"><?php echo $success; ?></div>
<?php } ?>
<div class="box">
  <div class="heading">
    <h1><img src="view/image/module.png" alt="" /> <?php echo $heading_title; ?></h1>
    <div class="buttons"><a onclick="$('#form').submit();" class="button"><span><?php echo $button_save; ?></span></a><a onclick="location = '<?php echo $cancel; ?>';" class="button"><span><?php echo $button_cancel; ?></span></a></div>
  </div>
  <div class="content">
    <div id="tabs" class="htabs">
        <a href="#tab-main"><?php echo $tab_main; ?></a>
        <a href="#tab-deffered"><?php echo $tab_deffered; ?></a>
        <a href="#tab-stat"><?php echo $tab_stat; ?></a>
    </div>
    <form action="<?php echo $action; ?>" method="post" enctype="multipart/form-data" id="form">
      <div style="width:100%;height:1px;clear:both;"></div>
        <div id="tab-main">
          <table class="form">
            <tr>
                <td>
                <?php echo $entry_allow_selection ?>
                </td>
                <td>
                    <label><input type="radio" name="reminder_allow_selection" value="1" <?php if (!empty($reminder_allow_selection)) { ?>checked="checked"<?php } ?>><?php echo $text_yes ?></label>
                    <label><input type="radio" name="reminder_allow_selection" value="0" <?php if (empty($reminder_allow_selection)) { ?>checked="checked"<?php } ?>><?php echo $text_no ?></label>
                </td>
            </tr>
            <?php foreach ($languages as $language) { ?>
            <tr>
                <td><span class="required">*</span> <?php echo $entry_status_name; ?></td>
                <td><img src="view/image/flags/<?php echo $language['image']; ?>" title="<?php echo $language['name']; ?>" style="vertical-align: top;" />&nbsp;<input type="text" name="reminder_status[<?php echo $language['language_id']; ?>]" value="<?php echo !empty($reminder_status[$language['language_id']]) ? $reminder_status[$language['language_id']] : 'Unconfirmed/Unpaid';?>"></td>
            </tr>
            <?php } ?>
          </table>
          <h3><?php echo $text_cron ?></h3>
          <table class="form">
            <tr>
                <td>
                <?php echo $entry_cron_key ?>
                </td>
                <td>
                    <input type="text" name="reminder_cron_key" size="50" onkeyup="$('.cron_key').text($(this).val())" value="<?php echo $reminder_cron_key ?>">
                </td>
            </tr>
            <tr>
                <td>
                <?php echo $entry_cron_count ?>
                </td>
                <td>
                    <input type="text" name="reminder_cron_count" size="3" value="<?php echo $reminder_cron_count ?>">
                </td>
            </tr>
            <tr>
                <td>
                <?php echo $entry_cron_stop_visited ?>
                </td>
                <td>
                    <label><input type="radio" name="reminder_cron_stop_visited" value="1" <?php if (!empty($reminder_cron_stop_visited)) { ?>checked="checked"<?php } ?>><?php echo $text_yes ?></label>
                    <label><input type="radio" name="reminder_cron_stop_visited" value="0" <?php if (empty($reminder_cron_stop_visited)) { ?>checked="checked"<?php } ?>><?php echo $text_no ?></label>
                </td>
            </tr>
            <tr>
                <td>
                <?php echo $text_link_for_cron ?>
                </td>
                <td>
                    <?php echo HTTP_CATALOG ?>index.php?route=checkout/reminder&key=<span class="cron_key"><?php echo $reminder_cron_key ?></span>
                </td>
            </tr>
            <tr>
                <td>
                <?php echo $text_cron_instruction ?>
                </td>
                <td>
                    0 12 * * * wget <?php echo HTTP_CATALOG ?>index.php?route=checkout/reminder&key=<span class="cron_key"><?php echo $reminder_cron_key ?></span>
                </td>
            </tr>
          </table>
        </div>
        <div id="tab-deffered">
          <table class="form">
            <tr>
                <td colspan="2"><h2><?php echo $text_use_deffered ?></h2></td>
            </tr>
            <?php foreach ($payment_extensions as $payment_code => $payment_name) { ?>
            <tr>
                <td>
                <?php echo $payment_name ?>
                </td>
                <td>
                    <label><input type="radio" name="reminder_deffered[<?php echo $payment_code ?>]" value="1" <?php if (!empty($reminder_deffered[$payment_code])) { ?>checked="checked"<?php } ?>><?php echo $text_yes ?></label>
                    <label><input type="radio" name="reminder_deffered[<?php echo $payment_code ?>]" value="0" <?php if (empty($reminder_deffered[$payment_code])) { ?>checked="checked"<?php } ?>><?php echo $text_no ?></label>
                </td>
            </tr>
            <?php } ?>
            </table>
            <table class="form">
            <tr>
                <td><?php echo $entry_status_deffered; ?></td>
                <td>
                <select name="reminder_deffered_status">
                    <?php foreach ($order_statuses as $order_status) { ?>
                    <?php if ($order_status['order_status_id'] == $reminder_deffered_status) { ?>
                    <option value="<?php echo $order_status['order_status_id']; ?>" selected="selected"><?php echo $order_status['name']; ?></option>
                    <?php } else { ?>
                    <option value="<?php echo $order_status['order_status_id']; ?>"><?php echo $order_status['name']; ?></option>
                    <?php } ?>
                    <?php } ?>
                </select>
                </td>
            </tr>
            <tr>
                <td>
                <?php echo $entry_show_description ?>
                </td>
                <td>
                    <label><input type="radio" name="reminder_show_description" value="1" <?php if (!empty($reminder_show_description)) { ?>checked="checked"<?php } ?>><?php echo $text_yes ?></label>
                    <label><input type="radio" name="reminder_show_description" value="0" <?php if (empty($reminder_show_description)) { ?>checked="checked"<?php } ?>><?php echo $text_no ?></label>
                </td>
            </tr>
            <?php foreach ($languages as $language) { ?>
              <tr>
                <td><span class="required">*</span> <?php echo $entry_description; ?></td>
                <td><textarea name="reminder_description_<?php echo $language['language_id']; ?>" cols="80" rows="10"><?php echo isset(${'reminder_description_' . $language['language_id']}) ? ${'reminder_description_' . $language['language_id']} : ''; ?></textarea>
                  <img src="view/image/flags/<?php echo $language['image']; ?>" title="<?php echo $language['name']; ?>" style="vertical-align: top;" /><br />
                  </td>
              </tr>
              <?php } ?>
          </table>
        </div>
        <div id="tab-stat">
          <table class="form">
            <tr>
                <td>
                    <?php echo $text_total_reminders ?>
                </td>
                <td>
                    <?php echo $total_reminders ?>
                </td>
            </tr>
            <tr>
                <td>
                    <?php echo $text_total_visits ?>
                </td>
                <td>
                    <?php echo $total_visits ?>
                </td>
            </tr>
            <tr>
                <td>
                    <?php echo $text_total_success ?>
                </td>
                <td>
                    <?php echo $total_success ?>
                </td>
            </tr>
          </table>
          <h2><?php echo $text_last_visits ?></h2>
            <table class="list" style="width:33%">
            <thead>
                <tr>
                    <td class="right">
                        <?php echo $text_order_id ?>
                    </td>
                    <td class="left">
                        <?php echo $text_order_status ?>
                    </td>
                    <td class="left">
                        <?php echo $text_date_reminded ?>
                    </td>
                    <td class="left">
                        <?php echo $text_date_visited ?>
                    </td>
                    <td></td>
                </tr>
            </thead>
            <?php foreach ($last_visits as $key => $value) { ?>
                <tr>
                    <td class="right">
                        <?php echo $value['order_id'] ?>
                    </td>
                    <td class="left">
                        <?php echo $value['status'] ?>
                    </td>
                    <td class="left">
                        <?php echo $value['datetime_remind'] ?>
                    </td>
                    <td class="left">
                        <?php echo $value['datetime_visit'] ?>
                    </td>
                    <td class="left">
                        <a href="<?php echo $value['action']['href'] ?>"><?php echo $value['action']['text'] ?></a>
                    </td>
                </tr>
            <?php } ?>
          </table>
        </div>
    </form>
  </div>
</div> 
<script type="text/javascript">
    $(function() {
        $('#tabs a').tabs(); 
        setTimeout(function() {
            $('.success').hide('slow');
        }, 2000);
    });
</script>
<?php echo $footer; ?>