<div id="footer">
<div class="column">
    <h3><?php echo $text_information; ?></h3>
    <ul>
      <?php foreach ($informations as $information) { ?>
      <li><a href="<?php echo $information['href']; ?>"><?php echo $information['title']; ?></a></li>
      <?php } ?>
    </ul>
  </div>
  <div class="column">
    <h3><?php echo $text_service; ?></h3>
    <ul>
      <li><a href="<?php echo $contact; ?>"><?php echo $text_contact; ?></a></li>
      <li><a href="<?php echo $sitemap; ?>"><?php echo $text_sitemap; ?></a></li>
    </ul>
  </div>
  <div class="column">
    <h3>Наши партнёры</h3>
    <ul>
      <li><a href="http://www.yamal-tennis.ru">Сайт федерации тенниса в ЯНАО: yamal-tennis.ru</a></li>
    </ul>
  </div>
</div>
<div id="powered"><?php echo $powered; ?></div>
</div>
</body></html>