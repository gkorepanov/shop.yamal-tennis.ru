<?php

/*
@author  Dmitriy Kubarev
@link    http://www.simpleopencart.com
$email   dmitriy@simpleopencart.com
*/  

class ControllerPaymentReminder extends Controller {
	protected function index() {
    	$this->data['button_confirm'] = $this->language->get('button_confirm');

		$this->data['continue'] = $this->url->link('checkout/success');

        $this->data['show_description'] = $this->config->get('reminder_show_description');

        if ($this->config->get('reminder_show_description')) {
            $this->data['description'] = nl2br($this->config->get('reminder_description_' . $this->config->get('config_language_id')));
        }
		
		if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/payment/reminder.tpl')) {
			$this->template = $this->config->get('config_template') . '/template/payment/reminder.tpl';
		} else {
			$this->template = 'default/template/payment/reminder.tpl';
		}	
		
		$this->render();
	}
	
	public function confirm() {
		$this->load->model('checkout/order');
		$this->model_checkout_order->confirm($this->session->data['order_id'],$this->config->get('reminder_deffered_status'));
	}
}
?>