<?php
class ModelCatalogAttributesToText extends Model {

  public function getText($product_id, $options) {
    
    $text = "";
    $this->load->model('catalog/product');
		
		$attribute_groups = $this->model_catalog_product->getProductAttributes($product_id);
				
		$attr_arr = array();
		foreach ($attribute_groups as $group) {
		  foreach ($group['attribute'] as $attribute) {
  		  if (isset($options['attributes'][$attribute['attribute_id']])) { 
	  	    if ($options['attributes'][$attribute['attribute_id']]['show'] == 1) {
	  	      $attr_arr[] = $attribute['text']; 
	  	    }
	  	    else if ($options['attributes'][$attribute['attribute_id']]['show'] == 2 
	  	      && in_array($attribute['text'], explode(',', $options['attributes'][$attribute['attribute_id']]['replace']))) {
	  	      $attr_arr[] = $attribute['name']; 
	  	    } 
	  	  }
	  	}  
		}
		
		if ($attr_arr) {
		  $separator = isset($options['attributes_separator']) ? $options['attributes_separator'] : "/";
		  $text = implode($attr_arr, $separator);
		}
		if (isset($options['attributes_cut'])) {
		  $dots = strlen($text) > $options['attributes_cut'] ? '..' : '';
		  $text = utf8_substr($text, 0, $options['attributes_cut']) . $dots;
		}
		return $text;
	}
	
}
