<style>
    .list td.code {
        border-right: none;
        width: 10px;
    }
    .list td.title {
        vertical-align: middle;
    }
    #payment_form {
        margin-top: 15px;
    }
    .message_box {
        margin-bottom: 10px;
    }
</style>
<script type="text/javascript">
    var pml_hide_continue = function() {
        $('a[href="<?php echo $continue; ?>"]').closest('div.buttons,div.s_submit').hide();
    }
    var pml_hide_actions = function() {
        $('select[name="action"]').closest('div.buttons,div.s_submit').hide();
    }
    var pml_hide_checkbox_and_return = function() {
        $('td input[type="checkbox"]').attr('disabled', 'disabled');
        $('td a').hide();
    }
    $(function(){
        pml_hide_continue();
        pml_hide_actions();
        pml_hide_checkbox_and_return();

        $('#send_message').click(function() {
            if ($('#message').val().trim() != '') { 
                $('#send_form').submit();
            }
        });

        setTimeout(function() {
            $('.success').hide('slow');
        }, 2000);
    });
</script>
<?php if (!empty($payment_methods)) { ?>
<form action="<?php echo $payment_action ?>" method="POST" id="change_payment">            
    <table class="list">
        <thead>
          <tr>
            <td colspan="2"><h2><?php echo $text_methods; ?></h2></td>
          </tr>
        </thead> 
        <tbody>
        <?php foreach ($payment_methods as $payment_method) { ?>
            <tr>
                <td class="code">
                    <input type="radio" name="payment_code" value="<?php echo $payment_method['code']; ?>" id="<?php echo $payment_method['code']; ?>" <?php if ($payment_method['code'] == $payment_code) { ?>checked="checked"<?php } ?> onchange="$('#change_payment').submit();" />
                </td>
                <td class="title">
                    <label for="<?php echo $payment_method['code']; ?>"><?php echo $payment_method['title']; ?></label>
                </td>
            </tr>
            <?php if (!empty($payment_method['description'])) { ?>
                <tr>
                    <td class="code">
                    </td>
                    <td class="title">
                        <label for="<?php echo $payment_method['code']; ?>"><?php echo $payment_method['description']; ?></label>
                    </td>
                </tr>
            <?php } ?>
        <?php } ?>
        </tbody>
    </table>
    <input type="hidden" name="order_id" value="<?php echo $order_id ?>">
</form>
<?php } ?>
<?php if ($text_attention) { ?>
<div class="warning"><?php echo $text_attention ?></div>
<?php } ?>
<?php if ($payment_form) { ?>
<div id="payment_form"><?php echo $payment_form ?></div>
<?php } ?>
<form action="<?php echo $message_action ?>" method="POST" id="send_form"> 
<?php if ($message_sended) { ?>
<div class="success"><?php echo $message_sended ?></div>
<?php } ?>
<h2><?php echo $text_send_message ?></h2>
<div class="message_box">
<textarea name="message" id="message" cols="40" rows="10" style="width: 99%;"></textarea>
<input type="hidden" name="order_id" value="<?php echo $order_id ?>">
</div>
<div class="buttons">
  <div class="right">
    <input type="button" value="<?php echo $button_send; ?>" id="send_message" class="button" />
  </div>
</div>
</form>