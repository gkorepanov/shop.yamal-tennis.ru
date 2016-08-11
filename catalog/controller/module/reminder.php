<?php  
/*
@author  Dmitriy Kubarev
@link    http://www.simpleopencart.com
$email   dmitriy@simpleopencart.com
*/  

class ControllerModuleReminder extends Controller {
    protected function index() {
        if (!empty($this->request->get['order_id'])) {
            $this->load->model('account/order');
            $this->load->model('module/reminder');
            
            if (!empty($this->request->get['order_id']) && !empty($this->request->get['key'])) {
                $order_info = $this->model_module_reminder->getOrder($this->request->get['order_id'], $this->request->get['key']);
            } else {
                $order_info = $this->model_account_order->getOrder($this->request->get['order_id']);
            }
            
            $this->data['payment_form']    = false;
            $this->data['payment_methods'] = false;
            $this->data['payment_code']    = false;

            $this->language->load('module/reminder');

            $this->data['text_methods']      = $this->language->get('text_methods');
            $this->data['text_payment']      = $this->language->get('text_payment');
            $this->data['text_send_message'] = $this->language->get('text_send_message');
            $this->data['button_payment']    = $this->language->get('button_payment');
            $this->data['button_send']       = $this->language->get('button_send');

            $this->data['payment_action'] = $this->url->link('module/reminder/change', (!empty($this->request->get['order_id']) && !empty($this->request->get['key'])) ? '&order_id='.$this->request->get['order_id'].'&key='.$this->request->get['key'] : '', 'SSL');
            $this->data['message_action'] = $this->url->link('module/reminder/send', (!empty($this->request->get['order_id']) && !empty($this->request->get['key'])) ? '&order_id='.$this->request->get['order_id'].'&key='.$this->request->get['key'] : '', 'SSL');
            
            $this->data['message_sended'] = isset($this->session->data['message_sended']) ? $this->language->get('message_sended') : '';
            unset($this->session->data['message_sended']);

            $this->data['order_id'] = $order_info['order_id'];
            
            $this->data['not_in_stock'] = $this->model_module_reminder->getNotInStockProducts($order_info['order_id']);
            $this->data['text_attention'] = count($this->data['not_in_stock']) > 0 ? $this->language->get('text_attention') : '';

            if (!$order_info['order_status_id'] && empty($this->data['not_in_stock'])) {
                $payment_address = array(
                    'firstname'  => $order_info['payment_firstname'],
                    'lastname'   => $order_info['payment_lastname'],
                    'address_1'  => $order_info['payment_address_1'],
                    'address_2'  => $order_info['payment_address_2'],
                    'city'       => $order_info['payment_city'],
                    'postcode'   => $order_info['payment_postcode'],
                    'zone'       => $order_info['payment_zone'],
                    'zone_id'    => $order_info['payment_zone_id'],
                    'zone_code'  => $order_info['payment_zone_code'],
                    'country'    => $order_info['payment_country'],
                    'country_id' => $order_info['payment_country_id']  
                );

                $totals = $this->model_account_order->getOrderTotals($this->request->get['order_id']);
                $total_data = end($totals);
                $total_value = isset($total_data['value']) ? $total_data['value'] : 0;
            
                $payment_methods = array();

                if ($this->config->get('reminder_allow_selection')) {
                    $payment_methods = $this->loadPaymentMethods($payment_address, $total_value);
                    $this->data['payment_methods'] = $payment_methods;
                    $this->session->data['payment_methods'] = $payment_methods;
                }

                $payment_code = false;
                
                if (empty($order_info['payment_code'])) {
                    if (empty($payment_methods)) {
                        $payment_methods = $this->loadPaymentMethods($payment_address, $total_value);
                    }
                    foreach ($payment_methods as $method) {
                        if ($method && $method['title'] == $order_info['payment_method']) {
                            $payment_code = $method['code'];
                            break;
                        }
                    }
                } else {
                    $payment_code = $order_info['payment_code'];
                }

                if ($payment_code) {
                    $this->session->data['order_id'] = $order_info['order_id'];
                    $this->data['payment_form'] = $this->getChild('payment/' . $payment_code);
                }  

                $this->session->data['prevent_delete'] = array();
                $this->session->data['prevent_delete'][$order_info['order_id']] = true;

                $this->data['payment_code'] = $payment_code;
            }

            $this->data['continue'] = $this->url->link('account/order', '', 'SSL');

            if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/module/reminder.tpl')) {
                $this->template = $this->config->get('config_template') . '/template/module/reminder.tpl';
            } else {
                $this->template = 'default/template/module/reminder.tpl';
            }
        }
        
        $this->render();
    }

    public function send() {
        if (!empty($this->request->post['order_id'])) {
            if (!empty($this->request->post['message'])) {
                $this->load->model('module/reminder');

                $this->model_module_reminder->saveComment($this->request->post['order_id'], $this->request->post['message']);
                
                $this->session->data['message_sended'] = true;
            }
            
            if ($this->customer->isLogged()) {
                $this->redirect($this->url->link('account/order/info', '&order_id='.$this->request->post['order_id'].'#send_form', 'SSL'));
            } elseif (!empty($this->request->get['order_id']) && !empty($this->request->get['key'])) {
                $this->redirect($this->url->link('module/reminder/info', '&order_id='.$this->request->get['order_id'].'&key='.$this->request->get['key'].'#send_form', 'SSL')); 
            }
        }               
        $this->redirect($this->url->link('account/order', '', 'SSL'));
    }

    public function change() {
        if (!empty($this->request->post['order_id']) && !empty($this->request->post['payment_code']) && !empty($this->session->data['payment_methods'][$this->request->post['payment_code']]['title'])) {
            $this->load->model('module/reminder');
            
            if ($this->customer->isLogged()) {
                $this->model_module_reminder->editPaymentMethod($this->request->post['order_id'],$this->request->post['payment_code'],$this->session->data['payment_methods'][$this->request->post['payment_code']]['title']);
                $this->redirect($this->url->link('account/order/info', 'order_id='.$this->request->post['order_id'].'#payment_form', 'SSL'));
            } elseif (!empty($this->request->get['order_id']) && !empty($this->request->get['key']) && $this->model_module_reminder->checkVisitor($this->request->get['order_id'], $this->request->get['key'])) {
                $this->model_module_reminder->editPaymentMethod($this->request->post['order_id'],$this->request->post['payment_code'],$this->session->data['payment_methods'][$this->request->post['payment_code']]['title']);
                $this->redirect($this->url->link('module/reminder/info', '&order_id='.$this->request->get['order_id'].'&key='.$this->request->get['key'].'#payment_form', 'SSL')); 
            }
        }               
        $this->redirect($this->url->link('account/order', '', 'SSL'));
    }

    public function visit() {
        $this->load->model('module/reminder');

        if (!empty($this->request->get['order_id']) && !empty($this->request->get['key']) && $this->model_module_reminder->checkVisitor($this->request->get['order_id'], $this->request->get['key'])) {
            
            $this->model_module_reminder->setVisited($this->request->get['order_id'], $this->request->get['key']);

            $order_info = $this->model_module_reminder->getOrderShort($this->request->get['order_id']);
            
            $this->customer->logout();

            if ($order_info && $order_info['email'] != '' && $order_info['customer_id'] && $this->customer->login($order_info['email'], '', true)) {
                $this->redirect($this->url->link('account/order/info', '&order_id='.$this->request->get['order_id'].'#payment_form', 'SSL')); 
            } else if (!$order_info || ($order_info && !$order_info['customer_id'])) {
                $this->redirect($this->url->link('module/reminder/info', '&order_id='.$this->request->get['order_id'].'&key='.$this->request->get['key'].'#payment_form', 'SSL')); 
            }
        } else {
            $this->redirect($this->url->link('common/home', '', ''));
        }      
    }

    public function info() { 
        $opencart_version = explode('.', VERSION);
        $four = isset($opencart_version[3]) ? 0.1*$opencart_version[3] : 0;
        $main = $opencart_version[0].$opencart_version[1].$opencart_version[2];
        $opencart_version = (int)$main + $four;

        $this->language->load('account/order');
        
        $this->load->model('account/order');
        $this->load->model('module/reminder');
            
        $order_info = $this->model_module_reminder->getOrder($this->request->get['order_id'], $this->request->get['key']);
        
        if ($order_info) {
            $this->document->setTitle($this->language->get('text_order'));
            
            $this->data['breadcrumbs'] = array();
        
            $this->data['breadcrumbs'][] = array(
                'text'      => $this->language->get('text_home'),
                'href'      => $this->url->link('common/home'),         
                'separator' => false
            ); 
        
            $this->data['breadcrumbs'][] = array(
                'text'      => $this->language->get('text_order'),
                'href'      => $this->url->link('module/reminder/info', 'order_id=' . $this->request->get['order_id'] . '&key='.$this->request->get['key'], 'SSL'),
                'separator' => $this->language->get('text_separator')
            );
                    
            $this->data['heading_title'] = $this->language->get('text_order');
            
            $this->data['text_order_detail'] = $this->language->get('text_order_detail');
            $this->data['text_invoice_no'] = $this->language->get('text_invoice_no');
            $this->data['text_order_id'] = $this->language->get('text_order_id');
            $this->data['text_date_added'] = $this->language->get('text_date_added');
            $this->data['text_shipping_method'] = $this->language->get('text_shipping_method');
            $this->data['text_shipping_address'] = $this->language->get('text_shipping_address');
            $this->data['text_payment_method'] = $this->language->get('text_payment_method');
            $this->data['text_payment_address'] = $this->language->get('text_payment_address');
            $this->data['text_history'] = $this->language->get('text_history');
            $this->data['text_comment'] = $this->language->get('text_comment');

            $this->data['column_name'] = $this->language->get('column_name');
            $this->data['column_model'] = $this->language->get('column_model');
            $this->data['column_quantity'] = $this->language->get('column_quantity');
            $this->data['column_price'] = $this->language->get('column_price');
            $this->data['column_total'] = $this->language->get('column_total');
            $this->data['column_action'] = $this->language->get('column_action');
            $this->data['column_date_added'] = $this->language->get('column_date_added');
            $this->data['column_status'] = $this->language->get('column_status');
            $this->data['column_comment'] = $this->language->get('column_comment');
            
            $this->data['button_return'] = $this->language->get('button_return');
            $this->data['button_continue'] = $this->language->get('button_continue');

            $this->data['error_warning'] = '';
            $this->data['text_action']   = '';
            $this->data['text_selected'] = '';
            $this->data['text_reorder']  = '';
            $this->data['text_return']   = '';
            $this->data['action']        = '';

            if ($order_info['invoice_no']) {
                $this->data['invoice_no'] = $order_info['invoice_prefix'] . $order_info['invoice_no'];
            } else {
                $this->data['invoice_no'] = '';
            }
            
            $this->data['order_id'] = $this->request->get['order_id'];
            $this->data['date_added'] = date($this->language->get('date_format_short'), strtotime($order_info['date_added']));
            
            if ($order_info['payment_address_format']) {
                $format = $order_info['payment_address_format'];
            } else {
                $format = '{firstname} {lastname}' . "\n" . '{company}' . "\n" . '{address_1}' . "\n" . '{address_2}' . "\n" . '{city} {postcode}' . "\n" . '{zone}' . "\n" . '{country}';
            }
        
            $find = array(
                '{firstname}',
                '{lastname}',
                '{company}',
                '{address_1}',
                '{address_2}',
                '{city}',
                '{postcode}',
                '{zone}',
                '{zone_code}',
                '{country}'
            );
    
            $replace = array(
                'firstname' => $order_info['payment_firstname'],
                'lastname'  => $order_info['payment_lastname'],
                'company'   => $order_info['payment_company'],
                'address_1' => $order_info['payment_address_1'],
                'address_2' => $order_info['payment_address_2'],
                'city'      => $order_info['payment_city'],
                'postcode'  => $order_info['payment_postcode'],
                'zone'      => $order_info['payment_zone'],
                'zone_code' => $order_info['payment_zone_code'],
                'country'   => $order_info['payment_country']  
            );
            
            $this->data['payment_address'] = str_replace(array("\r\n", "\r", "\n"), '<br />', preg_replace(array("/\s\s+/", "/\r\r+/", "/\n\n+/"), '<br />', trim(str_replace($find, $replace, $format))));

            $this->data['payment_method'] = $order_info['payment_method'];
            
            if ($order_info['shipping_address_format']) {
                $format = $order_info['shipping_address_format'];
            } else {
                $format = '{firstname} {lastname}' . "\n" . '{company}' . "\n" . '{address_1}' . "\n" . '{address_2}' . "\n" . '{city} {postcode}' . "\n" . '{zone}' . "\n" . '{country}';
            }
        
            $find = array(
                '{firstname}',
                '{lastname}',
                '{company}',
                '{address_1}',
                '{address_2}',
                '{city}',
                '{postcode}',
                '{zone}',
                '{zone_code}',
                '{country}'
            );
    
            $replace = array(
                'firstname' => $order_info['shipping_firstname'],
                'lastname'  => $order_info['shipping_lastname'],
                'company'   => $order_info['shipping_company'],
                'address_1' => $order_info['shipping_address_1'],
                'address_2' => $order_info['shipping_address_2'],
                'city'      => $order_info['shipping_city'],
                'postcode'  => $order_info['shipping_postcode'],
                'zone'      => $order_info['shipping_zone'],
                'zone_code' => $order_info['shipping_zone_code'],
                'country'   => $order_info['shipping_country']  
            );

            $this->data['shipping_address'] = str_replace(array("\r\n", "\r", "\n"), '<br />', preg_replace(array("/\s\s+/", "/\r\r+/", "/\n\n+/"), '<br />', trim(str_replace($find, $replace, $format))));

            $this->data['shipping_method'] = $order_info['shipping_method'];
            
            $this->data['products'] = array();
            
            $products = $this->model_account_order->getOrderProducts($this->request->get['order_id']);

            foreach ($products as $product) {
                $option_data = array();
                
                $options = $this->model_account_order->getOrderOptions($this->request->get['order_id'], $product['order_product_id']);

                foreach ($options as $option) {
                    if ($option['type'] != 'file') {
                        $value = $option['value'];
                    } else {
                        $value = utf8_substr($option['value'], 0, utf8_strrpos($option['value'], '.'));
                    }
                    
                    $option_data[] = array(
                        'name'  => $option['name'],
                        'value' => (utf8_strlen($value) > 20 ? utf8_substr($value, 0, 20) . '..' : $value)
                    );                  
                }

                $this->data['products'][] = array(
                    'order_product_id' => $product['product_id'],
                    'name'             => $product['name'],
                    'model'            => $product['model'],
                    'option'           => $option_data,
                    'quantity'         => $product['quantity'],
                    'selected'         => false,
                    'price'            => $this->currency->format($product['price'] + ($this->config->get('config_tax') ? $product['tax'] : 0), $order_info['currency_code'], $order_info['currency_value']),
                    'total'            => $this->currency->format($product['total'] + ($this->config->get('config_tax') ? ($product['tax'] * $product['quantity']) : 0), $order_info['currency_code'], $order_info['currency_value']),
                    'return'           => $this->url->link('common/home', '', 'SSL')
                );
            }

            $this->data['vouchers'] = array();
            
            if ($opencart_version >= 152) {    
                $vouchers = $this->model_account_order->getOrderVouchers($this->request->get['order_id']);
                
                foreach ($vouchers as $voucher) {
                    $this->data['vouchers'][] = array(
                        'description' => $voucher['description'],
                        'amount'      => $this->currency->format($voucher['amount'], $order_info['currency_code'], $order_info['currency_value'])
                    );
                }
            }
            
            $this->data['totals'] = $this->model_account_order->getOrderTotals($this->request->get['order_id']);
            
            $this->data['comment'] = nl2br($order_info['comment']);
            
            $this->data['histories'] = array();

            $results = $this->model_account_order->getOrderHistories($this->request->get['order_id']);

            foreach ($results as $result) {
                $this->data['histories'][] = array(
                    'date_added' => date($this->language->get('date_format_short'), strtotime($result['date_added'])),
                    'status'     => $result['status'],
                    'comment'    => nl2br($result['comment'])
                );
            }

            if (!$order_info['order_status_id']) {
                $this->data['reminder'] = $this->getChild('module/reminder');
            }

            $this->data['continue'] = $this->url->link('account/order', '', 'SSL');

            $this->data['address_display'] = true;
        
            if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/account/order_info.tpl')) {
                $this->template = $this->config->get('config_template') . '/template/account/order_info.tpl';
            } else {
                $this->template = 'default/template/account/order_info.tpl';
            }
            
            $this->children = array(
                'common/column_left',
                'common/column_right',
                'common/content_top',
                'common/content_bottom',
                'common/footer',
                'common/header' 
            );
                                
            $this->response->setOutput($this->render());        
        } else {
            $this->redirect($this->url->link('common/home', '', ''));
        }
    }

    private function loadPaymentMethods($payment_address, $total) {
        $this->load->model('setting/extension');
        
        $payment_methods = array();

        $payment_extensions = $this->model_setting_extension->getExtensions('payment');

        foreach ($payment_extensions as $extension) {
            if ($this->config->get($extension['code'] . '_status')) {
                $this->load->model('payment/' . $extension['code']);
                $method = $this->{'model_payment_' . $extension['code']}->getMethod($payment_address, $total); 
                    
                if ($method) {
                    $payment_methods[$extension['code']] = $method;
                }
            }
        }
                     
        $sort_order = array(); 

        foreach ($payment_methods as $key => $value) {
            $sort_order[$key] = $value['sort_order'];
        }

        array_multisort($sort_order, SORT_ASC, $payment_methods);    

        return $payment_methods;
    }    
}
?>