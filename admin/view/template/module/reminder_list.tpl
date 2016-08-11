<script type="text/javascript">
    var remind = function() {
        $('#remind').hide();
        $.get('<?php echo $remind_action ?>', function(){
            $('#reminder').load('<?php echo $reminders_action ?>');
        });
        return false;
    }
</script>
<style>
    tr.similar1 td {
        background-color: #EEEEEE !important;
    }
    tr.similar2 td {
        background-color: #DDDDDD !important;
    }
    tr.similar3 td {
        background-color: #CCCCCC !important;
    }
    tr.similar4 td {
        background-color: #BBBBBB !important;
    }
</style>
<div style="overflow: hidden;">
    <div style="float:left;width:30%;margin-right:10px;">
        <h2><?php echo $text_reminders ?></h2>
        <?php if (count($not_in_stock)) { ?>
            <div class="not_in_stock">
            <div class="warning"><?php echo $text_attention; ?></div>
            <table class="list">
                <thead>
                    <tr>
                        <td class="right">
                            <?php echo $text_model ?>
                        </td>
                        <td class="left">
                            <?php echo $text_name ?>
                        </td>
                        <td class="left">
                            <?php echo $text_quantity ?>
                        </td>
                    </tr>
                </thead>
            <?php foreach ($not_in_stock as $product) { ?>
                <tr>
                    <td class="right">
                        <?php echo $product['model'] ?>
                    </td>
                    <td class="left">
                        <?php echo $product['name'] ?>
                    </td>
                    <td class="left">
                        <?php echo $product['quantity'] ?>
                    </td>
                </tr>
                <?php foreach ($product['options'] as $option) { ?>
                   <tr>
                       <td class="right">
                       </td>
                       <td class="left">
                           - <?php echo $option['name'] ?>: <?php echo $option['value'] ?>
                       </td>
                       <td class="left">
                           <?php echo $option['quantity'] ?>
                       </td>
                   </tr>     
                <?php } ?>
            <?php } ?>
            </table>
            </div>
        <?php } ?>
        <?php if ($remind_action) { ?>
            <a href="<?php echo $remind_action ?>" id="remind" onclick="remind();return false;" class="button"><span><?php echo $button_remind ?></span></a>
        <?php } ?>
        <?php if (count($reminders)) { ?>
            <table class="form">
            <?php foreach ($reminders as $key => $value) { ?>
              <tr>
                <td><?php echo $value['datetime_remind']; ?></td>
                <td><?php echo $value['datetime_visit'] ? sprintf($text_visited, $value['datetime_visit']) : $text_not_visited; ?></td>
              </tr>
            <?php } ?>
            </table>
        <?php } ?>
    </div>
    <div style="float:right;width:69%">
        <h2><?php echo $text_orders ?></h2>
        <?php if (count($orders)) { ?>
        <div class="not_in_stock">
        <table class="list" style="width:100%">
            <thead>
                <tr>
                    <td class="right">
                        <?php echo $text_order_id ?>
                    </td>
                    <td class="left">
                        <?php echo $text_customer ?>
                    </td>
                    <td class="left">
                        <?php echo $text_methods ?>
                    </td>
                    <td class="left">
                        <?php echo $text_order_status ?>
                    </td>
                    <td class="left">
                        <?php echo $text_total ?>
                    </td>
                    <td class="left">
                        <?php echo $text_date_added ?>
                    </td>
                    <td class="left">
                       
                    </td>
                </tr>
            </thead>
        <?php foreach ($orders as $order) { ?>
            <tr class="similar<?php echo $order['similar'] ?>">
                <td class="right">
                    <?php echo $order['order_id'] ?>
                </td>
                <td class="left">
                    <?php echo $order['customer'] ?>
                </td>
                <td class="left">
                    <?php echo $order['methods'] ?>
                </td>
                <td class="left">
                    <?php echo $order['status'] ?>
                </td>
                <td class="left">
                    <?php echo $order['total'] ?>
                </td>
                <td class="left">
                    <?php echo $order['date_added'] ?>
                </td>
                <td class="left">
                    <a href="<?php echo $order['action']['href'] ?>"><?php echo $order['action']['text'] ?></a>
                </td>
            </tr>
        <?php } ?>
        </table>
        </div>
        <?php } ?>
    </div>
</div>