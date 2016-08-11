<?php
class ControllerModuleCallme extends Controller {
	public function index() {
		$entryArray = array (
			'entry_header',
			'entry_name',
			'entry_phone',
			'entry_submit',
			'entry_header_title',
			'entry_name_title',
			'entry_phone_title',
			'entry_submit_title',
			'entry_error_name',
			'entry_error_phone',
			'entry_tc',
			'entry_vfb',
			'entry_vfe'
		);
		foreach ($entryArray as $entry)	{
			$this->data[$entry] = $this->config->get($entry);
		}
		
		$this->document->addStyle('catalog/view/theme/' . $this->config->get('config_template') . '/stylesheet/callme.css');		
		
		if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/module/callme.tpl')) {
			$this->template = $this->config->get('config_template') . '/template/module/callme.tpl';
		} else {
			$this->template = 'default/template/module/callme.tpl';
		}

		$this->render();
	
	}

	public function sendmail() {
		if ($this->request->post['cname'] && $this->request->post['cphone'])	{
			$name = substr(htmlspecialchars(trim($this->request->post['cname'])), 0, 32);
			$phone = $this->request->post['cphone'];

			$entry_to = $this->config->get('entry_to');
			$entry_from = $this->config->get('entry_from');
			$entry_success = $this->config->get('entry_success');
			$entry_error = $this->config->get('entry_error');
			$entry_mess_title = $this->config->get('entry_mess_title');
			$entry_mess_name = $this->config->get('entry_mess_name');
			$entry_mess_phone = $this->config->get('entry_mess_phone');
	
			if (strlen($name)>2 && preg_match('/^((8|\+7)[\- ]?)?(\(?\d{3}\)?[\- ]?)?[\d\- ]{7,10}$/', $phone))	{
				$title = $entry_mess_title;
				$mess =  $entry_mess_name."\n".$name."\n\n".$entry_mess_phone."\n".$phone;
				$headers = "From: ".$entry_from."\r\n";
				$headers .= "Content-type: text/plain; charset=utf-8\r\n";
				if (@mail($entry_to, $title, $mess, $headers))	{
					echo '<div class="c_success">'.$entry_success.'</div>';
				} else {
					echo '<div class="c_error">'.$entry_error.'</div>';
				}
			}
		}
	}
}
?>