<?php
/*
@author	Dmitriy Kubarev
@link	http://www.simpleopencart.com
$email  dmitriy@simpleopencart.com
*/  

class ControllerCheckoutReminder extends Controller { 
    public function index() {
        if (trim($this->request->get['key']) == trim($this->config->get('reminder_cron_key'))) {
            $this->load->model('module/reminder');
            $this->model_module_reminder->sendRemiders();
        }
    }
}


?>