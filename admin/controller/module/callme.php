<?php
class ControllerModuleCallme extends Controller {
	private $error = array(); 
	
	public function index() {   
		$this->load->language('module/callme');

		$this->document->setTitle($this->language->get('heading_title'));
		
		$this->load->model('setting/setting');
				
		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->model_setting_setting->editSetting('callme', $this->request->post);		
					
			$this->session->data['success'] = $this->language->get('text_success');
						
			$this->redirect($this->url->link('extension/module', 'token=' . $this->session->data['token'], 'SSL'));
		}
		$textArray = array(
			'heading_title',
			'text_enabled',
			'text_disabled',
			'text_content_top',
			'text_content_bottom',		
			'text_column_left',
			'text_column_right',
			'text_module',
			'text_submit_success',
			'text_content_top',
			'text_content_bottom',
			'text_column_left',
			'text_column_right',
			'text_to',
			'text_from',
			'text_header',
			'text_name', 
			'text_phone', 
			'text_submit',
			'text_header_title',
			'text_name_title', 
			'text_phone_title', 
			'text_submit_title',
			'text_success',
			'text_error', 
			'text_error_name', 
			'text_error_phone',
			'text_success',
			'text_mess_title', 
			'text_mess_name', 
			'text_mess_phone', 
			'text_tc', 
			'text_vfb', 
			'text_vfe', 
			
			'entry_layout',
			'entry_position',
			'entry_status',
			'entry_sort_order',
			
			'button_save',
			'button_cancel',
			'button_add_module',
			'button_remove'
		);
		foreach ($textArray as $param)	{
			$this->data[$param] = $this->language->get($param);
		}

 		if (isset($this->error['warning'])) {
			$this->data['error_warning'] = $this->error['warning'];
		} else {
			$this->data['error_warning'] = '';
		}

  		$this->data['breadcrumbs'] = array();

   		$this->data['breadcrumbs'][] = array(
       		'text'      => $this->language->get('text_home'),
			'href'      => $this->url->link('common/home', 'token=' . $this->session->data['token'], 'SSL'),
      		'separator' => false
   		);

   		$this->data['breadcrumbs'][] = array(
       		'text'      => $this->language->get('text_module'),
			'href'      => $this->url->link('extension/module', 'token=' . $this->session->data['token'], 'SSL'),
      		'separator' => ' :: '
   		);
		
   		$this->data['breadcrumbs'][] = array(
       		'text'      => $this->language->get('heading_title'),
			'href'      => $this->url->link('module/callme', 'token=' . $this->session->data['token'], 'SSL'),
      		'separator' => ' :: '
   		);
		
		$this->data['action'] = $this->url->link('module/callme', 'token=' . $this->session->data['token'], 'SSL');
		
		$this->data['cancel'] = $this->url->link('extension/module', 'token=' . $this->session->data['token'], 'SSL');
				
		$this->data['modules'] = array();
		
		if (isset($this->request->post['callme_module'])) {
			$this->data['modules'] = $this->request->post['callme_module'];
		} elseif ($this->config->get('callme_module')) { 
			$this->data['modules'] = $this->config->get('callme_module');
		}
		$entryArray = array (
			'entry_header',
			'entry_to',
			'entry_from',
			'entry_name',
			'entry_phone',
			'entry_submit',
			'entry_header_title',
			'entry_name_title',
			'entry_phone_title',
			'entry_submit_title',
			'entry_success',
			'entry_error',
			'entry_error_name',
			'entry_error_phone',
			'entry_mess_title',
			'entry_mess_name',
			'entry_mess_phone',
			'entry_tc',
			'entry_vfb',
			'entry_vfe'
		);
		foreach ($entryArray as $entry)	{
			if (!$this->config->get($entry))	{
				$this->data[$entry] = $this->language->get($entry);
			}	else	{
				if (isset($this->request->post[$entry])) {
					$this->data[$entry] = $this->request->post[$entry];
				} else {
					$this->data[$entry] = $this->config->get($entry);
				}
			}
		}
		
		$this->load->model('design/layout');
		
		$this->data['layouts'] = $this->model_design_layout->getLayouts();

		$this->template = 'module/callme.tpl';
		$this->children = array(
			'common/header',
			'common/footer'
		);
				
		$this->response->setOutput($this->render());
	}
	
	private function validate() {
		if (!$this->user->hasPermission('modify', 'module/callme')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}
		
		if (!$this->error) {
			return true;
		} else {
			return false;
		}	
	}
}
?>