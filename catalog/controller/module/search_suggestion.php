<?php
class ControllerModuleSearchSuggestion extends Controller {
	protected function index() {
 		
 		$this->id = 'search_suggestion';
    
    $this->document->addScript('catalog/view/javascript/search_suggestion.js');

    if (file_exists('catalog/view/theme/' . $this->config->get('config_template') . '/stylesheet/search_suggestion.css')) {
      $this->document->addStyle('catalog/view/theme/' . $this->config->get('config_template') . '/stylesheet/search_suggestion.css');
    } else {
      $this->document->addStyle('catalog/view/theme/default/stylesheet/search_suggestion.css');
    }

	}  	
}
?>
