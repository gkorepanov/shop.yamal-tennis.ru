<?php
/*
@author  Dmitriy Kubarev
@link    http://www.simpleopencart.com
$email   dmitriy@simpleopencart.com
*/  

class ModelModuleReminder extends Model {
    
    public function editPaymentMethod($order_id, $payment_code, $payment_method) {
        $opencart_version = explode('.', VERSION);
        $four = isset($opencart_version[3]) ? 0.1*$opencart_version[3] : 0;
        $main = $opencart_version[0].$opencart_version[1].$opencart_version[2];
        $opencart_version = (int)$main + $four;

        if ($opencart_version < 152) {
            $this->db->query("UPDATE `" . DB_PREFIX . "order` SET payment_method = '" . $this->db->escape($payment_method) . "' WHERE order_id  = '" . (int)$order_id . "' AND customer_id = '" . (int)$this->customer->getId() . "' AND order_status_id = '0'");
        } else {
            $this->db->query("UPDATE `" . DB_PREFIX . "order` SET payment_code = '" . $this->db->escape($payment_code) . "', payment_method = '" . $this->db->escape($payment_method) . "' WHERE order_id  = '" . (int)$order_id . "' AND customer_id = '" . (int)$this->customer->getId() . "' AND order_status_id = '0'");
        }
    }

    public function checkVisitor($order_id, $key) {
        $query = $this->db->query("SELECT `id` FROM `reminder` WHERE `order_id`  = '" . (int)$order_id . "' AND `key` = '" . $this->db->escape($key) . "'");
        
        if ($query->num_rows) {
            return true;
        }

        return false;
    }

    public function setVisited($order_id, $key) {
        $this->db->query("UPDATE `reminder` SET `datetime_visit` = NOW() WHERE `order_id`  = '" . (int)$order_id . "' AND `key` = '" . $this->db->escape($key) . "'");
    }

    public function getOrderShort($order_id) {
        $query = $this->db->query("SELECT `customer_id`,`firstname`,`lastname`,`email`,`order_status_id` FROM `" . DB_PREFIX . "order` WHERE order_id  = '" . (int)$order_id . "' AND order_status_id = '0'");
        
        return $query->row;
    }

    public function getNotInStockProducts($order_id) {
        $products = array();

        $order_product_query = $this->db->query("SELECT order_product_id, product_id, name, model, quantity FROM " . DB_PREFIX . "order_product WHERE order_id = '" . (int)$order_id . "'");
    
        foreach($order_product_query->rows as $product) {
            $product_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product p WHERE p.product_id = '" . (int)$product['product_id'] . "' AND p.date_available <= NOW() AND p.status = '1'");
            
            if ($product_query->num_rows) {
                $options = array();

                $order_option_query = $this->db->query("SELECT product_option_id, product_option_value_id, name, value, type FROM " . DB_PREFIX . "order_option WHERE order_id = '" . (int)$order_id . "' AND order_product_id = '". (int)$product['order_product_id'] ."'");
        
                if ($order_option_query->num_rows) {
                    foreach ($order_option_query->rows as $option) {
                        
                        if ($option['type'] == 'select' || $option['type'] == 'radio' || $option['type'] == 'image' || $option['type'] == 'checkbox') {
                            $option_value_query = $this->db->query("SELECT option_value_id, quantity, subtract FROM " . DB_PREFIX . "product_option_value WHERE product_option_value_id = '" . (int)$option['product_option_value_id'] . "'");
                            
                            if ($option_value_query->num_rows) {
                                if ($option_value_query->row['subtract'] && (!$option_value_query->row['quantity'] || ($option_value_query->row['quantity'] < $product['quantity']))) {
                                    $options[] = array(
                                        'name'     => $option['name'],
                                        'value'    => (utf8_strlen($option['value']) > 20 ? utf8_substr($option['value'], 0, 20) . '..' : $option['value']),
                                        'quantity' => $option_value_query->row['quantity']
                                    );
                                }                    
                            }
                        } 
                    }
                } 
            
                if (!$product_query->row['quantity'] || ($product_query->row['quantity'] < $product['quantity']) || !empty($options)) {
                    $products[$product['product_id']] = array(
                        'name'     => $product['name'],
                        'model'    => $product['model'],
                        'options'  => $options,
                        'quantity' => $product_query->row['quantity']
                    );
                }
            } else {
                $products[$product['product_id']] = array(
                    'name'     => $product['name'],
                    'model'    => $product['model'],
                    'options'  => array(),
                    'quantity' => 0
                );
            }
        }

        return $products;
    }

    public function saveComment($order_id, $message) {
        $this->language->load('module/reminder');
                
        $order_info = $this->getOrderShort($order_id);

        $this->db->query("INSERT INTO " . DB_PREFIX . "order_history SET order_id = '" . (int)$order_id . "', order_status_id = '" . (int)$order_info['order_status_id'] . "', notify = '1', comment = '" . $this->db->escape(strip_tags($message)) . "', date_added = NOW()");
    
        $mail = new Mail();
        $mail->protocol  = $this->config->get('config_mail_protocol');
        $mail->parameter = $this->config->get('config_mail_parameter');
        $mail->hostname  = $this->config->get('config_smtp_host');
        $mail->username  = $this->config->get('config_smtp_username');
        $mail->password  = $this->config->get('config_smtp_password');
        $mail->port      = $this->config->get('config_smtp_port');
        $mail->timeout   = $this->config->get('config_smtp_timeout');             
        $mail->setTo($this->config->get('config_email'));
        $mail->setFrom($order_info['email']);
        $mail->setSender($order_info['firstname'].' '.$order_info['lastname']);
        $mail->setSubject(html_entity_decode(sprintf($this->language->get('email_subject'), $order_id, ENT_QUOTES, 'UTF-8')));
        $mail->setText(strip_tags(html_entity_decode($message, ENT_QUOTES, 'UTF-8')));
        $mail->send();
    }

    public function getOrder($order_id, $key) {
        $order_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "order` WHERE order_id = '" . (int)$order_id . "' AND order_status_id = '0'");
        
        if ($order_query->num_rows && $this->checkVisitor($order_id, $key)) {
            $country_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "country` WHERE country_id = '" . (int)$order_query->row['payment_country_id'] . "'");
            
            if ($country_query->num_rows) {
                $payment_iso_code_2 = $country_query->row['iso_code_2'];
                $payment_iso_code_3 = $country_query->row['iso_code_3'];
            } else {
                $payment_iso_code_2 = '';
                $payment_iso_code_3 = '';               
            }
            
            $zone_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "zone` WHERE zone_id = '" . (int)$order_query->row['payment_zone_id'] . "'");
            
            if ($zone_query->num_rows) {
                $payment_zone_code = $zone_query->row['code'];
            } else {
                $payment_zone_code = '';
            }
            
            $country_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "country` WHERE country_id = '" . (int)$order_query->row['shipping_country_id'] . "'");
            
            if ($country_query->num_rows) {
                $shipping_iso_code_2 = $country_query->row['iso_code_2'];
                $shipping_iso_code_3 = $country_query->row['iso_code_3'];
            } else {
                $shipping_iso_code_2 = '';
                $shipping_iso_code_3 = '';              
            }
            
            $zone_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "zone` WHERE zone_id = '" . (int)$order_query->row['shipping_zone_id'] . "'");
            
            if ($zone_query->num_rows) {
                $shipping_zone_code = $zone_query->row['code'];
            } else {
                $shipping_zone_code = '';
            }
            
            return array(
                'order_id'                => $order_query->row['order_id'],
                'invoice_no'              => $order_query->row['invoice_no'],
                'invoice_prefix'          => $order_query->row['invoice_prefix'],
                'store_id'                => $order_query->row['store_id'],
                'store_name'              => $order_query->row['store_name'],
                'store_url'               => $order_query->row['store_url'],                
                'customer_id'             => $order_query->row['customer_id'],
                'firstname'               => $order_query->row['firstname'],
                'lastname'                => $order_query->row['lastname'],
                'telephone'               => $order_query->row['telephone'],
                'fax'                     => $order_query->row['fax'],
                'email'                   => $order_query->row['email'],
                'payment_firstname'       => $order_query->row['payment_firstname'],
                'payment_lastname'        => $order_query->row['payment_lastname'],             
                'payment_company'         => $order_query->row['payment_company'],
                'payment_address_1'       => $order_query->row['payment_address_1'],
                'payment_address_2'       => $order_query->row['payment_address_2'],
                'payment_postcode'        => $order_query->row['payment_postcode'],
                'payment_city'            => $order_query->row['payment_city'],
                'payment_zone_id'         => $order_query->row['payment_zone_id'],
                'payment_zone'            => $order_query->row['payment_zone'],
                'payment_zone_code'       => $payment_zone_code,
                'payment_country_id'      => $order_query->row['payment_country_id'],
                'payment_country'         => $order_query->row['payment_country'],  
                'payment_iso_code_2'      => $payment_iso_code_2,
                'payment_iso_code_3'      => $payment_iso_code_3,
                'payment_address_format'  => $order_query->row['payment_address_format'],
                'payment_method'          => $order_query->row['payment_method'],
                'shipping_firstname'      => $order_query->row['shipping_firstname'],
                'shipping_lastname'       => $order_query->row['shipping_lastname'],                
                'shipping_company'        => $order_query->row['shipping_company'],
                'shipping_address_1'      => $order_query->row['shipping_address_1'],
                'shipping_address_2'      => $order_query->row['shipping_address_2'],
                'shipping_postcode'       => $order_query->row['shipping_postcode'],
                'shipping_city'           => $order_query->row['shipping_city'],
                'shipping_zone_id'        => $order_query->row['shipping_zone_id'],
                'shipping_zone'           => $order_query->row['shipping_zone'],
                'shipping_zone_code'      => $shipping_zone_code,
                'shipping_country_id'     => $order_query->row['shipping_country_id'],
                'shipping_country'        => $order_query->row['shipping_country'], 
                'shipping_iso_code_2'     => $shipping_iso_code_2,
                'shipping_iso_code_3'     => $shipping_iso_code_3,
                'shipping_address_format' => $order_query->row['shipping_address_format'],
                'shipping_method'         => $order_query->row['shipping_method'],
                'comment'                 => $order_query->row['comment'],
                'total'                   => $order_query->row['total'],
                'order_status_id'         => $order_query->row['order_status_id'],
                'language_id'             => $order_query->row['language_id'],
                'currency_id'             => $order_query->row['currency_id'],
                'currency_code'           => $order_query->row['currency_code'],
                'currency_value'          => $order_query->row['currency_value'],
                'date_modified'           => $order_query->row['date_modified'],
                'date_added'              => $order_query->row['date_added'],
                'ip'                      => $order_query->row['ip']
            );
        } else {
            return false;   
        }
    }

    public function sendRemiders() {
        $cron_count = $this->config->get('reminder_cron_count');

        $date = date('Y-m-d H:i:s', strtotime('-'.($cron_count + 1).' day'));
        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "order` WHERE order_status_id = '0' AND date_modified > '".$date."'");   
    
        foreach ($query->rows as $row) {
            $test = $this->db->query("SELECT * FROM `reminder` WHERE order_id = '".(int)$row['order_id']."'");

            $stop = false;

            if ($test->num_rows >= $cron_count) {
                $stop = true;
            }

            if (!$stop && $this->config->get('reminder_cron_stop_visited')) {
                foreach ($test->rows as $tmp) {
                    if ($tmp['datetime_visit']) {
                        $stop = true;
                        break;
                    }
                }
            }

            if (!$stop) {
                $this->remindAboutOrder($row['order_id']);
            }
        }
    }

    public function remindAboutOrder($order_id) {
        $opencart_version = explode('.', VERSION);
        $four = isset($opencart_version[3]) ? 0.1*$opencart_version[3] : 0;
        $main = $opencart_version[0].$opencart_version[1].$opencart_version[2];
        $opencart_version = (int)$main + $four;

        $this->load->model('checkout/order');

        $order_info = $this->model_checkout_order->getOrder($order_id);
         
        if ($order_info && !$order_info['order_status_id'] && $order_info['email']) {

            $unique_key = md5(microtime(true));

            $order_product_query  = $this->db->query("SELECT * FROM " . DB_PREFIX . "order_product WHERE order_id = '" . (int)$order_id . "'");
            $order_download_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "order_download WHERE order_id = '" . (int)$order_id . "'");
            if ($opencart_version >= 152) {               
                $order_voucher_query  = $this->db->query("SELECT * FROM " . DB_PREFIX . "order_voucher WHERE order_id = '" . (int)$order_id . "'");
            }
            $order_total_query    = $this->db->query("SELECT * FROM `" . DB_PREFIX . "order_total` WHERE order_id = '" . (int)$order_id . "' ORDER BY sort_order ASC");
            
            $language = new Language($order_info['language_directory']);
            $language->load($order_info['language_filename']);
            $language->load('mail/reminder');
                            
            $subject = sprintf($language->get('text_new_subject'), $order_info['store_name'], $order_id);
        
            $template = new Template();
            
            $template->data['title'] = sprintf($language->get('text_new_subject'), html_entity_decode($order_info['store_name'], ENT_QUOTES, 'UTF-8'), $order_id);
            
            $template->data['text_greeting']         = sprintf($language->get('text_new_greeting'), html_entity_decode($order_info['store_name'], ENT_QUOTES, 'UTF-8'));
            $template->data['text_link']             = $this->checkOrderIsAbandoned($order_id) ? $language->get('text_new_link_abandoned') : $language->get('text_new_link_deffered');
            $template->data['text_download']         = $language->get('text_new_download');
            $template->data['text_order_detail']     = $language->get('text_new_order_detail');
            $template->data['text_order_id']         = $language->get('text_new_order_id');
            $template->data['text_date_added']       = $language->get('text_new_date_added');
            $template->data['text_payment_method']   = $language->get('text_new_payment_method'); 
            $template->data['text_shipping_method']  = $language->get('text_new_shipping_method');
            $template->data['text_email']            = $language->get('text_new_email');
            $template->data['text_telephone']        = $language->get('text_new_telephone');
            $template->data['text_payment_address']  = $language->get('text_new_payment_address');
            $template->data['text_shipping_address'] = $language->get('text_new_shipping_address');
            $template->data['text_product']          = $language->get('text_new_product');
            $template->data['text_model']            = $language->get('text_new_model');
            $template->data['text_quantity']         = $language->get('text_new_quantity');
            $template->data['text_price']            = $language->get('text_new_price');
            $template->data['text_total']            = $language->get('text_new_total');
            $template->data['text_footer']           = $language->get('text_new_footer');
            
            $template->data['logo']                  = HTTP_IMAGE . $this->config->get('config_logo');       
            $template->data['store_name']            = $order_info['store_name'];
            $template->data['store_url']             = $order_info['store_url'];
            $template->data['customer_id']           = $order_info['customer_id'];
            $template->data['link']                  = $order_info['store_url'] . 'index.php?route=module/reminder/visit&order_id=' . $order_id . '&key=' . $unique_key;
            
            if ($order_download_query->num_rows) {
                $template->data['download'] = $order_info['store_url'] . 'index.php?route=account/download';
            } else {
                $template->data['download'] = '';
            }

            $template->data['order_id'] = $order_id;
            $template->data['date_added'] = date($language->get('date_format_short'), strtotime($order_info['date_added']));        
            $template->data['payment_method'] = $order_info['payment_method'];
            $template->data['shipping_method'] = $order_info['shipping_method'];
            $template->data['email'] = $order_info['email'];
            $template->data['telephone'] = $order_info['telephone'];
            
            $template->data['comment'] = '';
                        
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
        
            $template->data['payment_address'] = str_replace(array("\r\n", "\r", "\n"), '<br />', preg_replace(array("/\s\s+/", "/\r\r+/", "/\n\n+/"), '<br />', trim(str_replace($find, $replace, $format))));                     
                                    
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
        
            $template->data['shipping_address'] = str_replace(array("\r\n", "\r", "\n"), '<br />', preg_replace(array("/\s\s+/", "/\r\r+/", "/\n\n+/"), '<br />', trim(str_replace($find, $replace, $format))));
            
            $template->data['products'] = array();
                
            foreach ($order_product_query->rows as $product) {
                $option_data = array();
                
                $order_option_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "order_option WHERE order_id = '" . (int)$order_id . "' AND order_product_id = '" . (int)$product['order_product_id'] . "'");
                
                foreach ($order_option_query->rows as $option) {
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
              
                $template->data['products'][] = array(
                    'name'     => $product['name'],
                    'model'    => $product['model'],
                    'option'   => $option_data,
                    'quantity' => $product['quantity'],
                    'price'    => $this->currency->format($product['price'] + ($this->config->get('config_tax') ? $product['tax'] : 0), $order_info['currency_code'], $order_info['currency_value']),
                    'total'    => $this->currency->format($product['total'] + ($this->config->get('config_tax') ? ($product['tax'] * $product['quantity']) : 0), $order_info['currency_code'], $order_info['currency_value'])
                );
            }
        
            $template->data['vouchers'] = array();
            
            if ($opencart_version >= 152) {
                foreach ($order_voucher_query->rows as $voucher) {
                    $template->data['vouchers'][] = array(
                        'description' => $voucher['description'],
                        'amount'      => $this->currency->format($voucher['amount'], $order_info['currency_code'], $order_info['currency_value']),
                    );
                }
            }
        
            $template->data['totals'] = $order_total_query->rows;
            
            if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/mail/reminder.tpl')) {
                $html = $template->fetch($this->config->get('config_template') . '/template/mail/reminder.tpl');
            } else {
                $html = $template->fetch('default/template/mail/reminder.tpl');
            }
            
            $text  = sprintf($language->get('text_new_greeting'), html_entity_decode($order_info['store_name'], ENT_QUOTES, 'UTF-8')) . "\n\n";
            $text .= $language->get('text_new_order_id') . ' ' . $order_id . "\n";
            $text .= $language->get('text_new_date_added') . ' ' . date($language->get('date_format_short'), strtotime($order_info['date_added'])) . "\n";
            
            $text .= $language->get('text_new_products') . "\n";
            
            foreach ($order_product_query->rows as $product) {
                $text .= $product['quantity'] . 'x ' . $product['name'] . ' (' . $product['model'] . ') ' . html_entity_decode($this->currency->format($product['total'] + ($this->config->get('config_tax') ? ($product['tax'] * $product['quantity']) : 0), $order_info['currency_code'], $order_info['currency_value']), ENT_NOQUOTES, 'UTF-8') . "\n";
                
                $order_option_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "order_option WHERE order_id = '" . (int)$order_id . "' AND order_product_id = '" . $product['order_product_id'] . "'");
                
                foreach ($order_option_query->rows as $option) {
                    $text .= chr(9) . '-' . $option['name'] . ' ' . (utf8_strlen($option['value']) > 20 ? utf8_substr($option['value'], 0, 20) . '..' : $option['value']) . "\n";
                }
            }
            
            if ($opencart_version >= 152) {
                foreach ($order_voucher_query->rows as $voucher) {
                    $text .= '1x ' . $voucher['description'] . ' ' . $this->currency->format($voucher['amount'], $order_info['currency_code'], $order_info['currency_value']);
                }
            }
            
            $text .= "\n";
            
            $text .= $language->get('text_new_order_total') . "\n";
            
            foreach ($order_total_query->rows as $total) {
                $text .= $total['title'] . ': ' . html_entity_decode($total['text'], ENT_NOQUOTES, 'UTF-8') . "\n";
            }           
            
            $text .= "\n";
            
            if ($order_info['customer_id']) {
                $text .= $language->get('text_new_link') . "\n";
                $text .= $order_info['store_url'] . 'index.php?route=module/reminder/visit&order_id=' . $order_id . '&key=' . $unique_key . "\n\n";
            }

            if ($order_download_query->num_rows) {
                $text .= $language->get('text_new_download') . "\n";
                $text .= $order_info['store_url'] . 'index.php?route=account/download' . "\n\n";
            }
            
            if ($order_info['comment']) {
                $text .= $language->get('text_new_comment') . "\n\n";
                $text .= $order_info['comment'] . "\n\n";
            }
            
            $text .= $language->get('text_new_footer') . "\n\n";
        
            $mail = new Mail(); 
            $mail->protocol = $this->config->get('config_mail_protocol');
            $mail->parameter = $this->config->get('config_mail_parameter');
            $mail->hostname = $this->config->get('config_smtp_host');
            $mail->username = $this->config->get('config_smtp_username');
            $mail->password = $this->config->get('config_smtp_password');
            $mail->port = $this->config->get('config_smtp_port');
            $mail->timeout = $this->config->get('config_smtp_timeout');         
            $mail->setTo($order_info['email']);
            $mail->setFrom($this->config->get('config_email'));
            $mail->setSender($order_info['store_name']);
            $mail->setSubject(html_entity_decode($subject, ENT_QUOTES, 'UTF-8'));
            $mail->setHtml($html);
            $mail->setText(html_entity_decode($text, ENT_QUOTES, 'UTF-8'));
            $mail->send();

            $this->db->query("INSERT INTO `reminder` (
                                `order_id`, 
                                `datetime_remind`,
                                `datetime_visit`,
                                `key`
                            ) VALUES (
                                '" . (int)$order_id . "', 
                                NOW(),
                                NULL,
                                '".$unique_key."'
                            )");
        }
    }

    public function checkOrderIsAbandoned($order_id) {
        $test = $this->db->query("SELECT * FROM `" . DB_PREFIX . "order_history` WHERE order_id = '".(int)$order_id."'");

        if (!$test->num_rows) {
            return true;
        }

        return false;
    }
}
?>