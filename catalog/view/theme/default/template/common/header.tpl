<!DOCTYPE html>
<html dir="<?php echo $direction; ?>" lang="<?php echo $lang; ?>">
<head>
<meta charset="UTF-8" />
<title><?php echo $title; ?></title>
<base href="<?php echo $base; ?>" />
<?php if ($description) { ?>
<meta name="description" content="<?php echo $description; ?>" />
<meta name="viewport" content="width=device-width,initial-scale=1.0" />
<?php } ?>
<?php if ($keywords) { ?>
<meta name="keywords" content="<?php echo $keywords; ?>" />
<?php } ?>
<meta property="og:title" content="<?php echo $title; ?>" />
<meta property="og:type" content="website" />
<meta property="og:url" content="<?php echo $og_url; ?>" />
<?php if ($og_image) { ?>
<meta property="og:image" content="<?php echo $og_image; ?>" />
<?php } else { ?>
<meta property="og:image" content="<?php echo $logo; ?>" />
<?php } ?>
<meta property="og:site_name" content="<?php echo $name; ?>" />
<?php if ($icon) { ?>
<link href="<?php echo $icon; ?>" rel="icon" />
<?php } ?>
<?php foreach ($links as $link) { ?>
<link href="<?php echo $link['href']; ?>" rel="<?php echo $link['rel']; ?>" />
<?php } ?>
<link rel="stylesheet" type="text/css" href="catalog/view/theme/default/stylesheet/stylesheet.css" />
<?php foreach ($styles as $style) { ?>
<link rel="<?php echo $style['rel']; ?>" type="text/css" href="<?php echo $style['href']; ?>" media="<?php echo $style['media']; ?>" />
<?php } ?>
<script type="text/javascript" src="catalog/view/javascript/jquery/jquery-1.7.1.min.js"></script>
<script type="text/javascript" src="catalog/view/javascript/jquery/ui/jquery-ui-1.8.16.custom.min.js"></script>
<link rel="stylesheet" type="text/css" href="catalog/view/javascript/jquery/ui/themes/ui-lightness/jquery-ui-1.8.16.custom.css" />
<script type="text/javascript" src="catalog/view/javascript/jquery/ui/external/jquery.cookie.js"></script>
<script type="text/javascript" src="catalog/view/javascript/jquery/colorbox/jquery.colorbox.js"></script>
<link rel="stylesheet" type="text/css" href="catalog/view/javascript/jquery/colorbox/colorbox.css" media="screen" />
<script type="text/javascript" src="catalog/view/javascript/jquery/tabs.js"></script>
<script type="text/javascript" src="catalog/view/javascript/common.js"></script>
<?php foreach ($scripts as $script) { ?>
<script type="text/javascript" src="<?php echo $script; ?>"></script>
<?php } ?>
<!--[if IE 7]>
<link rel="stylesheet" type="text/css" href="catalog/view/theme/default/stylesheet/ie7.css" />
<![endif]-->
<!--[if lt IE 7]>
<link rel="stylesheet" type="text/css" href="catalog/view/theme/default/stylesheet/ie6.css" />
<script type="text/javascript" src="catalog/view/javascript/DD_belatedPNG_0.0.8a-min.js"></script>
<script type="text/javascript">
DD_belatedPNG.fix('#logo img');
</script>
<![endif]-->
<?php echo $google_analytics; ?>
</head>
<body>
<div id="container">
<div id="header">
  <?php if ($logo) { ?>
  <div id="logo"><a href="<?php echo $home; ?>"><img src="<?php echo $logo; ?>" title="<?php echo $name; ?>" alt="<?php echo $name; ?>" /></a></div>
  <?php } ?>
  <div id="phone">
  	<img src="/image/data/system/phone.png" title="самовывоз" />
  	<div>
		<span>+7 937 987 65 56</span>
  </div>
  </div>
  <!--<?php echo $language; ?>
  <?php echo $currency; ?>-->
  <div id="search">
    <div class="button-search"></div>
    <?php if ($filter_name) { ?>
    <input type="text" name="filter_name" value="<?php echo $filter_name; ?>" />
    <?php } else { ?>
    <input type="text" name="filter_name" value="<?php echo $text_search; ?>" onclick="this.value = '';" onkeydown="this.style.color = '#000000';" />
    <?php } ?>
  </div>
  <div id="welcome">
    <?php if (!$logged) { ?>
    <?php echo $text_welcome; ?>
    <?php } else { ?>
    <?php echo $text_logged; ?>
    <?php } ?>
  </div>
  
  <?php echo $cart; ?>
  
  <div class="links">
  	<a href="<?php echo $home; ?>"><?php echo $text_home; ?></a>
  	<?php foreach ($informations as $information) { ?>
			<a href="<?php echo $information['href']; ?>"><?php echo $information['title']; ?></a>
    <?php } ?>
    <a href="<?php echo $contact; ?>"><?php echo $text_contact; ?></a>
  </div>
</div>


<?php 
$echo_level = function ($c_list, $level) use ( &$echo_level ) {
    foreach ($c_list as $category) { ?>
          <li>
            <?php if ($level == 0) { ?><div id="wrapper"><?php } ?>
              <?php if ($category['children']) { ?>
                <a href="<?php echo $category['href']; ?>" class="withchild"><?php echo $category['name']; ?></a>
              <?php } else { ?>
                <a href="<?php echo $category['href']; ?>"><?php echo $category['name']; ?></a>
              <?php } ?>

              <?php if ($category['children']) { ?>
                <?php if ($level != 0) { ?><div class="arrow"><img src="catalog/view/theme/default/image/right-arrow.jpg"></div><?php } ?>
                <div id="child_category"><ul>
                <?php $echo_level($category['children'], 1); ?>
                </ul></div>
              <?php } ?>
        <?php if ($level == 0) { ?></div><?php } ?></li>
<?php } };?>

<?php if ($categories) { ?>
  <div id="menu">
    <ul>
        <?php $echo_level($categories, 0); ?>
    </ul>
  </div>
<?php } ?>





<?php if ($categories) { /*?>
<div id="menu">
  <ul>
    <?php foreach ($categories as $category) { ?>
    <li><div id="wrapper"><?php if (1) { ?>
			<a href="<?php echo $category['href']; ?>" class="active"><?php echo $category['name']; ?></a>
				<?php } else { ?>
			<a href="<?php echo $category['href']; ?>"><?php echo $category['name']; ?></a>
		<?php } ?>

    <?php if ($category['children']) { ?>
      <div>
        <?php for ($i = 0; $i < count($category['children']);) { ?>
        <ul>
          <?php $j = $i + ceil(count($category['children']) / $category['column']); ?>
          <?php for (; $i < $j; $i++) { ?>
          <?php if (isset($category['children'][$i])) { ?>
          <li><a href="<?php echo $category['children'][$i]['href']; ?>"><?php echo $category['children'][$i]['name']; ?></a></li>
          <?php } ?>
          <?php } ?>
        </ul>
        <?php } ?>
      </div>
      <?php } ?>
    </div></li>
    <?php } ?>
  </ul>
</div>
<?php */} ?>

<div id="notification"></div>
