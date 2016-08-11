<?php
/*
@author  Dmitriy Kubarev
@link    http://www.simpleopencart.com
$email   dmitriy@simpleopencart.com
*/  

class ControllerModuleReminder extends Controller {
    private $error = array(); 

    public function install() {
        $this->load->model('module/reminder');

        $this->model_module_reminder->createStatusWaiting();
        $this->model_module_reminder->createReminderTables();
    }

    private function load_language($path) {
        $language = $this->language;
        if (isset($language) && method_exists($language, 'load')) {
            $this->language->load($path);
            unset($language);
            return;
        }

        $load = $this->load;
        if (isset($load) && method_exists($load, 'language')) {
            $this->load->language($path);
            unset($load);
            return;
        }
    }

    public function index() {   
        $this->load_language('module/reminder');

        $this->document->setTitle(strip_tags($this->language->get('heading_title')));
        
        $this->load->model('setting/setting');
        $this->load->model('module/reminder');
        
        $this->data['success'] = '';
        
        if (isset($this->session->data['success'])) {
            $this->data['success'] = $this->session->data['success'];
            unset($this->session->data['success']);
        }

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {            
            $this->model_setting_setting->editSetting('reminder', $this->request->post);       

            $this->model_module_reminder->editStatusWaiting($this->request->post['reminder_status']); 
            
            $this->session->data['success'] = $this->language->get('text_success');
            
            $this->redirect($this->url->link('module/reminder', 'token=' . $this->session->data['token'], 'SSL'));
        }
                
        $this->data['heading_title']           = strip_tags($this->language->get('heading_title'));
        
        $this->data['tab_main']                = $this->language->get('tab_main');
        $this->data['tab_deffered']            = $this->language->get('tab_deffered');
        $this->data['tab_stat']                = $this->language->get('tab_stat');
        
        $this->data['text_enabled']            = $this->language->get('text_enabled');
        $this->data['text_disabled']           = $this->language->get('text_disabled');
        $this->data['text_yes']                = $this->language->get('text_yes');
        $this->data['text_no']                 = $this->language->get('text_no');
        $this->data['text_use_deffered']       = $this->language->get('text_use_deffered');
        $this->data['text_total_reminders']    = $this->language->get('text_total_reminders');
        $this->data['text_total_visits']       = $this->language->get('text_total_visits');
        $this->data['text_total_success']      = $this->language->get('text_total_success');
        $this->data['text_last_visits']        = $this->language->get('text_last_visits');
        $this->data['text_order_id']           = $this->language->get('text_order_id');
        $this->data['text_order_status']       = $this->language->get('text_order_status');
        $this->data['text_date_reminded']      = $this->language->get('text_date_reminded');
        $this->data['text_date_visited']       = $this->language->get('text_date_visited');
        $this->data['text_cron']               = $this->language->get('text_cron');
        $this->data['text_link_for_cron']      = $this->language->get('text_link_for_cron');
        $this->data['text_link_for_cron']      = $this->language->get('text_link_for_cron');
        $this->data['text_cron_instruction']   = $this->language->get('text_cron_instruction');
        
        $this->data['entry_allow_selection']   = $this->language->get('entry_allow_selection');
        $this->data['entry_status_deffered']   = $this->language->get('entry_status_deffered');
        $this->data['entry_show_description']  = $this->language->get('entry_show_description');
        $this->data['entry_description']       = $this->language->get('entry_description');
        $this->data['entry_status_name']       = $this->language->get('entry_status_name');
        $this->data['entry_cron_key']          = $this->language->get('entry_cron_key');
        $this->data['entry_cron_count']        = $this->language->get('entry_cron_count');
        $this->data['entry_cron_stop_visited'] = $this->language->get('entry_cron_stop_visited');
        
        $this->data['button_save']             = $this->language->get('button_save');
        $this->data['button_cancel']           = $this->language->get('button_cancel');
        
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
           'text'      => strip_tags($this->language->get('heading_title')),
           'href'      => $this->url->link('module/reminder', 'token=' . $this->session->data['token'], 'SSL'),
           'separator' => ' :: '
        );
        
        $this->data['action'] = $this->url->link('module/reminder', 'token=' . $this->session->data['token'], 'SSL');
        
        $this->data['cancel'] = $this->url->link('extension/module', 'token=' . $this->session->data['token'], 'SSL');

        $this->data['token'] = $this->session->data['token'];
    
        $this->load->model('setting/extension');
        $this->load->model('localisation/order_status');
        
        $payment_extensions = $this->model_setting_extension->getInstalled('payment');
        $tmp = array();
        foreach ($payment_extensions as $extension) {
            if ($this->config->get($extension . '_status')) {
                $tmp[] = $extension;
            }
        }
        $payment_extensions = $tmp;
        
        $this->data['payment_extensions'] = array();
        
        $files = glob(DIR_APPLICATION . 'controller/payment/*.php');
        
        if ($files) {
            foreach ($files as $file) {
                $extension = basename($file, '.php');
                
                if (in_array($extension, $payment_extensions)) {
                    $this->load_language('payment/' . $extension);
                    $this->data['payment_extensions'][$extension] = $this->language->get('heading_title');
                }
            }
        }
        
        if (isset($this->request->post['reminder_allow_selection'])) {
            $this->data['reminder_allow_selection'] = $this->request->post['reminder_allow_selection'];
        } else {
            $this->data['reminder_allow_selection'] = $this->config->get('reminder_allow_selection');
        }

        if (isset($this->request->post['reminder_deffered'])) {
            $this->data['reminder_deffered'] = $this->request->post['reminder_deffered'];
        } else {
            $this->data['reminder_deffered'] = $this->config->get('reminder_deffered');
        }

        if (isset($this->request->post['reminder_deffered_status'])) {
            $this->data['reminder_deffered_status'] = $this->request->post['reminder_deffered_status'];
        } else {
            $this->data['reminder_deffered_status'] = $this->config->get('reminder_deffered_status');
        }

        if (isset($this->request->post['reminder_show_description'])) {
            $this->data['reminder_show_description'] = $this->request->post['reminder_show_description'];
        } else {
            $this->data['reminder_show_description'] = $this->config->get('reminder_show_description');
        }

        if (isset($this->request->post['reminder_cron_stop_visited'])) {
            $this->data['reminder_cron_stop_visited'] = $this->request->post['reminder_cron_stop_visited'];
        } else {
            $this->data['reminder_cron_stop_visited'] = $this->config->get('reminder_cron_stop_visited');
        }

        if (isset($this->request->post['reminder_cron_key'])) {
            $this->data['reminder_cron_key'] = $this->request->post['reminder_cron_key'];
        } if (!is_null($this->config->get('reminder_cron_key'))) {
            $this->data['reminder_cron_key'] = $this->config->get('reminder_cron_key');
        } else {
            $this->data['reminder_cron_key'] = md5(time());
        }

        if (isset($this->request->post['reminder_cron_count'])) {
            $this->data['reminder_cron_count'] = $this->request->post['reminder_cron_count'];
        } if (!is_null($this->config->get('reminder_cron_count'))) {
            $this->data['reminder_cron_count'] = $this->config->get('reminder_cron_count');
        } else {
            $this->data['reminder_cron_count'] = 3;
        }

        $order_statuses = $this->model_localisation_order_status->getOrderStatuses();
        $this->data['order_statuses'] = array();

        foreach ($order_statuses as $key => $value) {
            if ($value['order_status_id']) {
                $this->data['order_statuses'][] = $value;
            }
        }

        $this->load->model('localisation/language');
        
        $languages = $this->model_localisation_language->getLanguages();
        
        foreach ($languages as $language) {
            if (isset($this->request->post['reminder_description_' . $language['language_id']])) {
                $this->data['reminder_description_' . $language['language_id']] = $this->request->post['reminder_description_' . $language['language_id']];
            } else {
                $this->data['reminder_description_' . $language['language_id']] = $this->config->get('reminder_description_' . $language['language_id']);
            }
        }
        
        $this->data['languages'] = $languages;

        $this->data['reminder_status'] = $this->model_module_reminder->getStatusWaiting();

        $this->data['total_reminders'] = $this->model_module_reminder->reportTotalReminders();
        $this->data['total_visits']    = $this->model_module_reminder->reportTotalVisits();
        $this->data['total_success']   = $this->model_module_reminder->reportTotalSuccess();

        $this->load_language('sale/order');

        $last_visits = $this->model_module_reminder->reportLastVisits();
        
        $this->data['last_visits'] = array();

        foreach ($last_visits as $key => $value) {
            $value['datetime_remind'] = date($this->language->get('datetime_format'), strtotime($value['datetime_remind']));
            $value['datetime_visit'] = date($this->language->get('datetime_format'), strtotime($value['datetime_visit']));

            $value['action'] = array(
                'text' => $this->language->get('text_view'),
                'href' => $this->url->link('sale/order/info', 'token=' . $this->session->data['token'] . '&order_id=' . $value['order_id'], 'SSL')
            );

            $this->data['last_visits'][] = $value;
        }

        $this->template = 'module/reminder.tpl';
        $this->children = array(
            'common/header',
            'common/footer'
        );
                
        $this->response->setOutput($this->render());
    }
    
    public function remind() {
        if (!empty($this->request->get['order_id'])) {
            $this->load->model('module/reminder');

            if ($this->model_module_reminder->checkEmailAndStatus($this->request->get['order_id'])) {
                $this->model_module_reminder->remindAboutOrder($this->request->get['order_id']);
            }
        }
    }

    public function reminders() {
        if (!empty($this->request->get['order_id'])) {
            $this->load->model('module/reminder');
            $this->load_language('module/reminder');

            $this->data['button_remind']     = $this->language->get('button_remind');
            $this->data['text_reminders']    = $this->language->get('text_reminders');
            $this->data['text_visited']      = $this->language->get('text_visited');
            $this->data['text_not_visited']  = $this->language->get('text_not_visited');
            $this->data['text_name']         = $this->language->get('text_name');
            $this->data['text_model']        = $this->language->get('text_model');
            $this->data['text_quantity']     = $this->language->get('text_quantity');
            $this->data['text_orders']       = $this->language->get('text_orders');
            $this->data['text_order_id']     = $this->language->get('text_order_id');
            $this->data['text_order_status'] = $this->language->get('text_order_status');
            $this->data['text_customer']     = $this->language->get('text_customer');
            $this->data['text_methods']      = $this->language->get('text_methods');
            $this->data['text_total']        = $this->language->get('text_total');
            $this->data['text_date_added']   = $this->language->get('text_date_added');
            
            $this->data['remind_action']    = '';
            $this->data['reminders_action'] = '';
            $this->data['not_in_stock']     = array();
            $this->data['text_attention']   = '';
            $this->data['reminders']        = array();

            if ($this->model_module_reminder->checkEmailAndStatus($this->request->get['order_id'])) {
                $this->data['remind_action'] = ($this->config->get('config_use_ssl') ? HTTPS_SERVER : HTTP_SERVER).'index.php?route=module/reminder/remind&order_id='.$this->request->get['order_id'].'&token=' . $this->session->data['token'];
                $this->data['reminders_action'] = ($this->config->get('config_use_ssl') ? HTTPS_SERVER : HTTP_SERVER).'index.php?route=module/reminder/reminders&order_id='.$this->request->get['order_id'].'&token=' . $this->session->data['token'];
            
                $this->data['not_in_stock'] = $this->model_module_reminder->getNotInStockProducts($this->request->get['order_id']);
                $this->data['text_attention'] = count($this->data['not_in_stock']) > 0 ? $this->language->get('text_attention') : '';
            }

            $reminders = $this->model_module_reminder->getReminders($this->request->get['order_id']);

            foreach ($reminders as $key => $value) {
                $value['datetime_visit'] = $value['datetime_visit'] ? date($this->language->get('datetime_format'), strtotime($value['datetime_visit'])) : '';
                $this->data['reminders'][] = $value;
            }

            $this->load->model('sale/order');
            $order_info = $this->model_sale_order->getOrder($this->request->get['order_id']);

            $orders_by_customer_id = array();
            if ($order_info['customer_id']) {
                $orders_by_customer_id = $this->model_module_reminder->getOrdersByCustomerId($order_info['customer_id']);
                unset($orders_by_customer_id[$order_info['order_id']]);
            }
 
            $orders_by_email = array();
            if ($order_info['email'] && !in_array($order_info['email'], array('empty@localhost', $this->config->get('simple_empty_email')))) {
                $orders_by_email = $this->model_module_reminder->getOrdersByCustomerEmail($order_info['email']);
                unset($orders_by_email[$order_info['order_id']]);
            }

            $orders_by_telephone = array();
            if ($order_info['telephone']) {
                $orders_by_telephone = $this->model_module_reminder->getOrdersByTelephone($order_info['telephone']);
                unset($orders_by_telephone[$order_info['order_id']]);
            }

            $orders = $orders_by_customer_id + $orders_by_email + $orders_by_telephone;

            $this->data['orders'] = array();

            foreach ($orders as $order_id => $order) {
                $similar = 0;
                if (!$order_info['order_status_id'] && $order['order_status_id']) {
                    if (abs(strtotime($order_info['date_added']) - strtotime($order['date_added'])) < 3600) {
                        $similar++;
                    }

                    if ($similar && abs($order['total'] - $order_info['total'])/($order['total'] + 0.1) < 0.1) {
                        $similar++;
                    }

                    if ($similar && $order_info['payment_method'] == $order['payment_method']) {
                        $similar++;
                    }

                    if ($similar && $order_info['shipping_method'] == $order['shipping_method']) {
                        $similar++;
                    }
                }

                $this->data['orders'][$order_id] = array(
                    'order_id'      => $order['order_id'],
                    'customer'      => $order['firstname'].' '.$order['lastname'],
                    'methods'       => $order['payment_method'].($order['shipping_method'] ? '<br>'.$order['shipping_method'] : ''),
                    'status'        => $order['status'],
                    'total'         => $this->currency->format($order['total'], $order['currency_code'], $order['currency_value']),
                    'date_added'    => date($this->language->get('datetime_format'), strtotime($order['date_added'])),
                    'similar'       => $similar,
                    'action'        => array(
                                            'text' => $this->language->get('text_view'),
                                            'href' => $this->url->link('sale/order/info', 'token=' . $this->session->data['token'] . '&order_id=' . $order['order_id'], 'SSL')
                                        )
                );
            }

            $this->template = 'module/reminder_list.tpl';

            $this->response->setOutput($this->render());
        }
    }

    private function validate() {
        if (!$this->user->hasPermission('modify', 'module/reminder')) {
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