<?php
class ModelCatalogSearchSuggestion extends Model {

  public function getNewModules () {
  
		$this->load->model('design/layout');		
		$layouts = $this->model_design_layout->getLayouts();
				
		$layouts_count = count($layouts);
		$modules = array();
		for($i=0; $i<$layouts_count; $i++) {
		  $modules[$i] = array(
		    'layout_id' => $layouts[$i]['layout_id'],
		    'position' => 'content_bottom',
		    'status' => 1,
		    'sort_order' => 0
		  );
		}
    return $modules;  
  }
  
  public function getDefaultOptions() {
  
    return array( 
      'search_order' => 'name',
      'search_order_dir' => 'asc',
      'search_logic' => 'and',  
      'search_limit' => 7,
      'search_cache' => 1,
      'search_where' => array ( 
        'name' => 1,
        'tags' => 1, 
        'description' => 1 
       ), 
      'search_field' => array ( 
        'description_cut' => 50,
        'attributes_cut' => 50,
        'attributes_separator' => '/'
       ),
      'relevance' => array (
          'model_word_weight' => 30,
          'sku_word_weight' => 30,
      )  
    );
  }

}
