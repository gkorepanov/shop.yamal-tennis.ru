<div class="box">
  <div class="box-heading account"><?php echo $heading_title; ?></div>
  <div class="box-content">
   
      <?php if (!$logged) { ?>
      <a href="<?php echo $login; ?>"><?php echo $text_login; ?></a> 
      <a href="<?php echo $register; ?>"><?php echo $text_register; ?></a>
      <a href="<?php echo $forgotten; ?>"><?php echo $text_forgotten; ?></a>
      <?php } ?>
      <a href="<?php echo $account; ?>"><?php echo $text_account; ?></a>
      <?php if ($logged) { ?>
      <?php /*<a href="<?php echo $edit; ?>"><?php echo $text_edit; ?></a> */?>
      <a href="<?php echo $password; ?>"><?php echo $text_password; ?></a>
      <?php } ?>
      <?php /*<a href="<?php echo $wishlist; ?>"><?php echo $text_wishlist; ?></a> */?>
      <a href="<?php echo $order; ?>"><?php echo $text_order; ?></a>
      <?php /*<a href="<?php echo $download; ?>"><?php echo $text_download; ?></a> */?>
      <?php /*<a href="<?php echo $return; ?>"><?php echo $text_return; ?></a> */?>
      <a href="<?php echo $transaction; ?>"><?php echo $text_transaction; ?></a>
      <?php /*<a href="<?php echo $newsletter; ?>"><?php echo $text_newsletter; ?></a> */?>
      <?php if ($logged) { ?>
      <a href="<?php echo $logout; ?>"><?php echo $text_logout; ?></a>
      <?php } ?>
    
  </div>
</div>
