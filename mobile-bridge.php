<?php
/*-----------------------------------------------------------------------------+
| eMagicOne                                                                    |
| Copyright (c) 2012-2013 eMagicOne.com <contact@emagicone.com>                |
| All rights reserved                                                          |
+------------------------------------------------------------------------------+
|                                                                              |
| PHP MySQL Bridge for Store Assistant                                         |
|                                                                              |
| Developed by eMagicOne,                                                      |
| Copyright (c) 2012-2013                                                      |
+-----------------------------------------------------------------------------*/


// Please change immediately
// it is security threat to leave these values as is!
$username = 'adm';
$password = '4o5';


$version = '$Revision: 36 $';

/*
	Please uncomments following database connection information if you need to connect to some
	specific database or with some specific database login information.
	By default PHP MySQL Bridge is getting login information from your shopping cart.
	This option should be used on non-standard configuration, we assume you know what you are doing
*/
/*
define('USER_DB_SERVER','localhost'); // database host to connect
define('USER_DB_SERVER_USERNAME',''); // database user login to connect
define('USER_DB_SERVER_PASSWORD',''); // database user password to connect
define('USER_DB_DATABASE','');  	  // database name
define('USER_DB_TABLE_PREFIX','');    // database prefix
*/


#############################################################################################
#!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!#
#!                                                                                         !#
#! Don't change anything below this line! You should REALLY understand what are you doing! !#
#!                                                                                         !#
#!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!#
#############################################################################################
error_reporting(E_ERROR | E_WARNING | E_PARSE); //good (and pretty enough) for most hostings

$MainSA = new MainSA();
$CartClassName = $MainSA->CartClass;

$call_func = $MainSA->call_function;
$CartClass = new $CartClassName();

if(!method_exists($CartClass, $call_func)) {
    $MainSA->generate_output('old_bridge');
}

$request = $CartClass->$call_func();
$MainSA->close_db_connect();
$CartClass->close_db_connect();
$MainSA->generate_output($request);

class MainSA {
    public $CartClass = "";
    public $call_function;
    protected $sDBHost = '';
    protected $sDBUser = '';
    protected $sDBPwd  = '';
    protected $sDBName = '';
    protected $sDBPrefix = '';
    protected $site_url = '';
    private $rLink = 0;
    private $CartType = -1;
    private $callback;

    public function __construct() {
        if(!ini_get('date.timezone') || ini_get('date.timezone' == "")) {
            @date_default_timezone_set(@date_default_timezone_get());
        }

        $this->callback = $this->validate_type($_REQUEST['callback'], 'STR');
        $this->call_function = $this->validate_type($_REQUEST['call_function'], 'STR');
        $this->define_db_configs();

        if(empty($this->callback) && empty($this->call_function)) {
            $this->run_self_test();
        }

        if($this->call_function == 'phpinfo') {
            //echo "<a href='" . $_SERVER['HTTP_REFERER'] . "'>back</a><br>";
            //phpinfo();
            //echo "<br><a href='" . $_SERVER['HTTP_REFERER'] . "'>back</a>";
            die();
        }

        if(!$this->check_auth()) {
            $this->generate_output('auth_error');
        }

        if(!$this->connect_db()) {
            $this->generate_output('connection_error');
        }
        if($this->call_function == 'test_config') {
            $this->close_db_connect();
            $this->generate_output(array('test' => 1));
        }
        $params = $this->validate_types($_REQUEST, array(
            'show' => 'INT',
            'page' => 'INT',
            'search_order_id' => 'STR',
            'orders_from' => 'STR',
            'orders_to' => 'STR',
            'customers_from' => 'STR',
            'customers_to' => 'STR',
            'date_from' => 'STR',
            'date_to' => 'STR',
            'graph_from' => 'STR',
            'graph_to' => 'STR',
            'products_to' => 'STR',
            'products_from' => 'STR',
            'order_id' => 'INT',
            'user_id' => 'INT',
            'params' => 'STR',
            'val' => 'STR',
            'search_val' => 'STR',
            'statuses' => 'STR',
            'last_order_id' => 'STR',
            'product_id' => 'INT',
            'get_statuses' => 'INT',
            'cust_with_orders' => 'INT'
        ));
        foreach($params as $k => $value) {
            $this->{$k} = $value;
        }

        $full_url = "http://";
        if(!empty($_SERVER["HTTPS"])) {
            $full_url = "https://";
        }
        $full_url .= $_SERVER['SERVER_NAME'] . $_SERVER['PHP_SELF'];
        $this->site_url = str_replace(basename($_SERVER["SCRIPT_NAME"]), '', $full_url);
    }

    public function generate_output($data) {
        function reset_null(&$item, $key) {
            if(empty($item) && $item != 0) {
                $item = '';
            }
            $item = trim($item);
        }
		
		if(!is_array($data)) {
			$data = array($data);
		}
		
        if(is_array($data)) {
            array_walk_recursive($data, 'reset_null');
        }

//        if(!function_exists('json_encode')) {
//            $data = $this->my_json_encode($data);
//        } else {
//            $data = json_encode($data);
//        }
        $data = $this->to_json($data);

        if($this->callback) {
            header('Content-Type: text/javascript;charset=utf-8');
            die($this->callback . '(' . $data . ');');
        } else {
            header('Content-Type: text/javascript;charset=utf-8');
            die($data);
        }
    }

    public function to_json($data) {
        $isArray = true;
        $keys = array_keys($data);
        $prevKey = -1;

        foreach ($keys as $key) {
            if (!is_numeric($key) || $prevKey + 1 != $key) {
                $isArray = false;
                break;
            } else {
                $prevKey++;
            }
        }

        unset($keys);
        $items = array();

        foreach ($data as $key => $value) {
            $item = (!$isArray ? "\"$key\":" : '');

            if (is_array($value)) {
                $item .= $this->to_json($value);
            } elseif (is_null($value)) {
                $item .= 'null';
            } elseif (is_bool($value)) {
                $item .= $value ? 'true' : 'false';
            } elseif (is_string($value)){
                $item .= '"' . preg_replace('%([\\x00-\\x1f\\x22\\x5c])%e', 'sprintf("\\\\u%04X", ord("$1"))', $value) . '"';
            } elseif (is_numeric($value)) {
                $item .= $value;
            } else {
                //throw new Exception('Wrong argument.');
            }
            $items[] = $item;
        }

        return ($isArray ? '[' : '{') . implode(',' , $items) . ($isArray ? ']' : '}');
    }

    public function my_json_encode($data) {
        if( is_array($data) || is_object($data) ) {
            $islist = is_array($data) && ( empty($data) || array_keys($data) === range(0,count($data)-1) );

            if( $islist ) {
                $json = '[' . implode(',', array_map('my_json_encode', $data) ) . ']';
            } else {
                $items = Array();
                foreach( $data as $key => $value ) {
                    $items[] = $this->my_json_encode("$key") . ':' . $this->my_json_encode($value);
                }
                $json = '{' . implode(',', $items) . '}';
            }
        } elseif( is_string($data) ) {
            # Escape non-printable or Non-ASCII characters.
            # I also put the \\ character first, as suggested in comments on the 'addclashes' page.
            $string = '"' . addcslashes($data, "\\\"\n\r\t/" . chr(8) . chr(12)) . '"';
            $json    = '';
            $len    = strlen($string);
            # Convert UTF-8 to Hexadecimal Codepoints.
            for( $i = 0; $i < $len; $i++ ) {

                $char = $string[$i];
                $c1 = ord($char);

                # Single byte;
                if( $c1 <128 ) {
                    $json .= ($c1 > 31) ? $char : sprintf("\\u%04x", $c1);
                    continue;
                }

                # Double byte
                $c2 = ord($string[++$i]);
                if ( ($c1 & 32) === 0 ) {
                    $json .= sprintf("\\u%04x", ($c1 - 192) * 64 + $c2 - 128);
                    continue;
                }

                # Triple
                $c3 = ord($string[++$i]);
                if( ($c1 & 16) === 0 ) {
                    $json .= sprintf("\\u%04x", (($c1 - 224) <<12) + (($c2 - 128) << 6) + ($c3 - 128));
                    continue;
                }

                # Quadruple
                $c4 = ord($string[++$i]);
                if( ($c1 & 8 ) === 0 ) {
                    $u = (($c1 & 15) << 2) + (($c2>>4) & 3) - 1;

                    $w1 = (54<<10) + ($u<<6) + (($c2 & 15) << 2) + (($c3>>4) & 3);
                    $w2 = (55<<10) + (($c3 & 15)<<6) + ($c4-128);
                    $json .= sprintf("\\u%04x\\u%04x", $w1, $w2);
                }
            }
        } else {
            # int, floats, bools, null
            $json = strtolower(var_export( $data, true ));
        }
        return $json;
    }

    private function connect_db() {
        $this->rLink = mysql_connect($this->sDBHost, $this->sDBUser, $this->sDBPwd); // connecting to MySQL
        if($this->rLink) {
            mysql_select_db($this->sDBName, $this->rLink);
            if($this->CartType == 0) {
                mysql_query("SET NAMES 'latin1'");
            } else {
                mysql_query("SET NAMES 'utf8'");
            }
/*
            $res = mysql_query('SELECT @@character_set_database AS charset');
            $row = mysql_fetch_array($res);
            mysql_query("SET CHARACTER SET '".$row['charset']."'");
*/
        }
        return $this->rLink;
    }

    public function close_db_connect() {
        mysql_close($this->rLink);
    }

    private function check_auth() {
        global $username, $password;
        if(isset($_REQUEST["hash"]) && md5($username.md5($password)) == $_REQUEST["hash"]) {
            return true;
        }
        return false;
    }

    private function define_db_configs() {
        $this->CartType = $this->getCartType();
        if($this->CartType == -1) {
            $this->generate_output("unknown_cart_error");
        }
        if(!defined('USER_DB_SERVER') || !defined('USER_DB_SERVER_USERNAME') || !defined('USER_DB_SERVER_PASSWORD') || !defined('USER_DB_DATABASE')) {
            // osCommerce, CRE Loaded, Zen Cart
            if($this->CartType == 0) {
                require('./includes/configure.php');
                $this->sDBHost = DB_SERVER;
                $this->sDBName = DB_DATABASE;
                $this->sDBUser = DB_SERVER_USERNAME;
                $this->sDBPwd =  DB_SERVER_PASSWORD;
                $this->sDBPrefix = (defined('DB_PREFIX') ? DB_PREFIX : (defined('TABLE_PREFIX') ? TABLE_PREFIX : (defined('DB_TABLE_PREFIX') ? DB_TABLE_PREFIX : (defined('USER_DB_TABLE_PREFIX') ? USER_DB_TABLE_PREFIX : ''))));
            }
            // X-Cart
            elseif($this->CartType == 1) {
                define('XCART_START', true);
                require(dirname(__FILE__) . '/config.php');
                $this->sDBHost = $sql_host;
                $this->sDBName = $sql_db;
                $this->sDBUser = $sql_user;
                $this->sDBPwd =  $sql_password;
                $this->sDBPrefix =  (defined('USER_DB_TABLE_PREFIX') ? USER_DB_TABLE_PREFIX : 'xcart_');
            }
            // Magento
            elseif($this->CartType == 3) {
                $this->parseMagentoDbConfig();
            }
            // PrestaShop
            elseif($this->CartType == 5) {
                require_once dirname(__FILE__).'/config/settings.inc.php';
                $this->sDBHost = _DB_SERVER_;
                $this->sDBName = _DB_NAME_;
                $this->sDBUser = _DB_USER_;
                $this->sDBPwd =  _DB_PASSWD_;
                $this->sDBPrefix =  (defined('USER_DB_TABLE_PREFIX') ? USER_DB_TABLE_PREFIX : _DB_PREFIX_);
            }
            // VirtueMart
            elseif ($this->CartType == 6) {
                require_once dirname(__FILE__).'/configuration.php';
                $VMConfig = new JConfig();
                $this->sDBHost = $VMConfig->host;
                $this->sDBName = $VMConfig->db;
                $this->sDBUser = $VMConfig->user;
                $this->sDBPwd  = $VMConfig->password;
                $this->sDBPrefix = $VMConfig->dbprefix;
                $this->sDBCartPrefix = (defined('USER_DB_TABLE_PREFIX') ? USER_DB_TABLE_PREFIX : $this->sDBPrefix . "virtuemart_");
            }
            // OpenCart
            elseif ($this->CartType == 7) {
                $this->sDBHost = DB_HOSTNAME;
                $this->sDBName = DB_DATABASE;
                $this->sDBUser = DB_USERNAME;
                $this->sDBPwd =  DB_PASSWORD;
                $this->sDBPrefix = (defined('DB_PREFIX') ? DB_PREFIX : (defined('USER_DB_TABLE_PREFIX') ? USER_DB_TABLE_PREFIX : ''));
            }
        } else {
            $this->sDBHost = USER_DB_SERVER;
            $this->sDBUser = USER_DB_SERVER_USERNAME;
            $this->sDBPwd  = USER_DB_SERVER_PASSWORD;
            $this->sDBName = USER_DB_DATABASE;
            $this->sDBPrefix = USER_DB_TABLE_PREFIX;
        }
    }

    private function getCartType() {
        if(is_dir("./includes") && is_file("./includes/configure.php") ) {
            $this->CartClass = 'osCommSA';
            return 0; // osCommerce cart type
        } elseif( file_exists(dirname(__FILE__) . "/config.php") ) {
            define('XCART_START', 1);
            require('./config.php');
            if(defined('DB_DRIVER') && defined('DB_HOSTNAME') && defined('DB_USERNAME') && defined('DB_DATABASE')) {
                $this->CartClass = 'OpenCartSA';
                return 7; // OpenCart
            } elseif(isset($sql_host) && isset($sql_user) && isset($sql_db)) {
                $this->CartClass = 'XCartSA';
                return 1; // X-Cart
            }
        } elseif( file_exists(dirname(__FILE__) . "/app/Mage.php" ) ) {
            $this->CartClass = 'MagentoSA';
            return 3; // Magento
        } elseif( file_exists(dirname(__FILE__) . '/config/settings.inc.php') ) {
            $this->CartClass = 'PrestaShopSA';
            return 5; // PrestaShop
        } elseif( file_exists(dirname(__FILE__) . '/configuration.php') ) {  // VirtueMart
            $vm_version_file = dirname(__FILE__).'/administrator/components/com_virtuemart/version.php';
            if(file_exists($vm_version_file)) {
                define('_JEXEC', 1);
                require_once $vm_version_file;
                if(isset($VMVERSION->RELEASE)) {
                    $vm_version = $VMVERSION->RELEASE;
                } elseif(isset(vmVersion::$RELEASE)) {
                    $vm_version = vmVersion::$RELEASE;
                }
            }
            if(floatval($vm_version) < 2) {
                return -1;
            } else {
                $this->CartClass = 'VirtueMartv2xSA';
            }
            return 6;
        }
        return -1; // Unknown Cart Type
    }

    private function parseMagentoDbConfig() {
        $config_file = file_get_contents(dirname(__FILE__) . '/app/etc/local.xml');
        $p = xml_parser_create();
        xml_parse_into_struct($p, $config_file, $vals, $index);
        xml_parser_free($p);
        foreach($index['ACTIVE'] as $k => $ind) {
            if(intval($vals[$ind]['value']) == 1) {
                $this->sDBHost = $vals[ $index['HOST'][$k] ]['value'];
                $this->sDBUser = $vals[ $index['USERNAME'][$k] ]['value'];
                $this->sDBPwd  = $vals[ $index['PASSWORD'][$k] ]['value'];
                $this->sDBName = $vals[ $index['DBNAME'][$k] ]['value'];
                $this->sDBPrefix = $vals[ $index['TABLE_PREFIX'][$k] ]['value'];
                break;
            }
        }
    }

    private function test_default_password_is_changed() {
        return ! ( $GLOBALS['username'] == '1' && $GLOBALS['password'] == '1' );
    }

    private function run_self_test() {
        $utf_bom = false;
        $handle = @fopen(basename($_SERVER["SCRIPT_NAME"]), "r+b");
        $result = @fread($handle, 3);

        if(!$result) {
            $result = @file_get_contents(basename($_SERVER["SCRIPT_NAME"]));
        }

        if(strncmp($result, "\xEF\xBB\xBF", 3) == 0 || strncmp($result, "\xFE\xFF", 2) == 0 || strncmp($result, "\xFF\xFE", 2) == 0) {
            $utf_bom = true;
        }

        $html = '<h2>'.basename($_SERVER["SCRIPT_NAME"]).' Self Test Tool</h2>'
              . '<div style="padding: 5px; margin: 10px 0;">This tool checks your website to make sure there are no issues in your hosting configuration.<br />Your hosting support can solve all issues found here.</div>'
              . '<table cellpadding=2><tr><th>Test Title</th><th>Result</th></tr>'
              . '<tr><td>Bridge Version</td><td>' . $GLOBALS['version'] . '</td><td></td></tr>'
              . '<tr><td>Default Login and Password Changed</td><td>'
              . (( $res = $this->test_default_password_is_changed() ) ? '<span style="color: #008000;">Yes</span>' : '<span style="color: #ff0000;">Fail</span>') . '</td>';

        if(!$res) {
            $html .= '<td>Change your login credentials in '.basename($_SERVER["SCRIPT_NAME"]).' to make your connection secure</td>';
        }

        $html .= '<tr><td>'.basename($_SERVER["SCRIPT_NAME"]).' with encoding as "UTF-8 without BOM"<br>(BOM="byte-order-mark")</td><td>'
              . (( $utf_bom ) ? '<span style="color: #ff0000;">Fail</span>' : '<span style="color: #008000;">Yes</span>' ) . '</td>';

        if($utf_bom) {
            $html .= '<td>
                You need to save '.basename($_SERVER["SCRIPT_NAME"]).' in "UTF-8 without BOM" encoding.<br>
                For Windows it is Notepad++. Open and save the file in "UTF-8 without BOM" encoding.<br>
                For Mac OS it is TextEdit which by default saves the file in "UTF-8 without BOM" encoding.
                </td>';
        }

        $html .= '<tr><td>&nbsp;</td></tr>'
            . '<tr><td><b>Check Default Timezone Set</b></td><td>'
            . ( (ini_get('date.timezone') != "") ? '<span style="color: #008000;">OK</span> ('.date_default_timezone_get().')' : '<span style="color: #ff0000;">Fail</span></td><td><b>PHP Warning:</b> It is not safe to rely on the system\'s timezone settings.<br/>Please edit your php.ini file and set the "date.timezone" property to
the correct value for your server.<br/>In order to prevent errors occurrence in mobile-bridge.php operating - <br/><b>"'.date_default_timezone_get().'"</b> time zone was set as default, with the help of "date_default_timezone_set()" function') . '</td>';

        $html .= '<tr><td>&nbsp;</td></tr>'
              . '<tr><td><b>Database Connection Check</b></td><td>'
              . (( $this->connect_db() ) ? '<span style="color: #008000;">OK</span>' : '<span style="color: #ff0000;">Fail</span>') . '</td>';

        $html .= '</table><br/><br/>'
              //. '<a href="?call_function=phpinfo">More information about your PHP configuration</a><br /><br />'
              . '<div style="margin-top: 15px; font-size: 13px;">PHP MySQL Bridge by <a href="http://emagicone.com" target="_blank" style="color: #15428B">eMagicOne</a></div>';

        die($html);
    }

    protected function validate_types(&$array, $names) {
        foreach ($names as $name => $type) {
            if (isset($array["$name"])) {
                switch ($type) {
                    case 'INT':
                        $array["$name"] = intval($array["$name"]);
                        break;
                    case 'FLOAT':
                        $array["$name"] = floatval($array["$name"]);
                        break;
                    case 'STR':
                        $array["$name"] = str_replace(array("\r", "\n"), ' ', addslashes(htmlspecialchars(trim($array["$name"]))));
                        break;
                    case 'STR_HTML':
                        $array["$name"] = addslashes(trim($array["$name"]));
                        break;
                    default:
                        $array["$name"] = '';
                }
            } else {
                $array["$name"] = '';
            }
        }
        return $array;
    }

    protected function validate_type(&$value, $type) {
        switch ($type) {
            case 'INT':
                $value = intval($value);
                break;
            case 'FLOAT':
                $value = floatval($value);
                break;
            case 'STR':
                $value = str_replace(array("\r", "\n"), ' ', addslashes(htmlspecialchars(trim($value))));
                break;
            case 'STR_HTML':
                $value = addslashes(trim($value));
                break;
            default:
        }
        return $value;
    }

    //for address
    protected function split_values($arr, $keys, $sign = ', ') {
        $new_arr = array();
        foreach($keys as $key) {
            if(isset($arr[$key])) {
                if(!is_null($arr[$key]) && $arr[$key] != '') {
                    $new_arr[] = $arr[$key];
                }
            }
        }
        return implode($sign, $new_arr);
    }

    protected function bd_nice_number($n, $is_count = false) {
        // first strip any formatting;
        $n = intval((0+str_replace(",","",$n)));

        // is this a number?
        if(!is_numeric($n)) return $n;

        if($is_count) {
            $n = intval($n);
        } else {
            $n = number_format($n, 1, '.', '');
        }

        // now filter it;
        if($n>1000000000000000) return round(($n/1000000000000000), 1).'P';
        else if($n>1000000000000) return round(($n/1000000000000),1).'T';
        else if($n>1000000000) return round(($n/1000000000),1).'G';
        else if($n>1000000) return round(($n/1000000),1).'M';
        else if($n>1000) return round(($n/1000),1).'k';

        return $n;
    }
}


class osCommSA extends MainSA {
    public function get_store_title() {
        $query = "SELECT configuration_value FROM  ".$this->sDBPrefix."configuration WHERE configuration_key LIKE 'STORE_NAME'";
        $result = mysql_query($query);
        $row = mysql_fetch_assoc($result);
        return array('test' => 1, 'title' => $row['configuration_value']);
    }

    public function get_store_stats() {
        $store_stats = array('count_orders' => "0", 'total_sales' => "0", 'count_customers' => "0", "last_order_id" => "0", "new_orders" => "0");
        $default_attrs = $this->_get_default_attrs();
        /*
        if(!empty($this->date_from)) {
            $query_where_parts[] = " UNIX_TIMESTAMP(o.date_purchased) >= '".strtotime($this->date_from." 00:00:00")."'";
        }
        if(!empty($this->date_to)) {
            $query_where_parts[] = " UNIX_TIMESTAMP(o.date_purchased) <= '".strtotime($this->date_to." 23:59:59")."'";
        }
        */
        $today = date("Y-m-d", time());
        $query_where_parts[] = " UNIX_TIMESTAMP(o.date_purchased) >= '".strtotime($today . " 00:00:00")."'";
        $query_where_parts[] = " UNIX_TIMESTAMP(o.date_purchased) <= '".strtotime($today . " 23:59:59")."'";
        if(!empty($this->statuses)) {
            $statuses = explode("|", $this->statuses);
            if(!empty($statuses)) {
                $stat = array();
                foreach($statuses as $status) {
                    if($status != "") {
                        $stat[] = $status;
                    }
                }
                $parse_statuses = implode("','", $stat);
                $query_where_parts[] = " o.orders_status IN ('".$parse_statuses."')";
            }
        }

        $query = "SELECT
					ot.value,
					c.symbol_left,
					c.symbol_right,
					c.decimal_point,
					c.decimal_places
		 		  FROM ".$this->sDBPrefix."orders_total AS ot
		 		    LEFT JOIN ".$this->sDBPrefix."orders AS o ON o.orders_id = ot.orders_id
				    LEFT JOIN ".$this->sDBPrefix."currencies AS c ON c.code = '".$default_attrs['DEFAULT_CURRENCY']."'
				  WHERE ot.class = 'ot_total' ";
        if(!empty($query_where_parts)) {
            $query .= " AND " . implode(" AND ", $query_where_parts);
        }

        $result = mysql_query($query);
        if(mysql_num_rows($result) > 0) {
            while($row = mysql_fetch_assoc($result)) {
                $store_stats['count_orders']++;
                $store_stats['total_sales'] += $row['value'];
            }
        }
        $store_stats['total_sales'] = $this->_price_format($default_attrs['DEFAULT_CURRENCY'], $store_stats['total_sales'], false, true);

        if($this->last_order_id != "") {
            $query_max = "SELECT COUNT(orders_id) AS count_orders, MAX(orders_id) AS last_order_id
                          FROM ".$this->sDBPrefix."orders AS o
                          WHERE orders_id > ".$this->last_order_id;
            if(!empty($query_where_parts)) {
                $query_max .= " AND " . implode(" AND ", $query_where_parts);
            }

            $result_max = mysql_query($query_max);
            if(mysql_num_rows($result_max) > 0) {
                $row_max = mysql_fetch_assoc($result_max);
                $store_stats['last_order_id'] = intval($this->last_order_id);
                if(intval($row_max['last_order_id']) > intval($this->last_order_id)) {
                    $store_stats['last_order_id'] = intval($row_max['last_order_id']);
                }
                $store_stats['new_orders'] = intval($row_max['count_orders']);
            }
        }

        unset($query_where_parts);
        /*
        if(!empty($this->date_from)) {
            $query_where_parts[] = " UNIX_TIMESTAMP(ci.customers_info_date_account_created) >= '".strtotime($this->date_from." 00:00:00")."'";
        }
        if(!empty($this->date_to)) {
            $query_where_parts[] = " UNIX_TIMESTAMP(ci.customers_info_date_account_created) <= '".strtotime($this->date_to." 23:59:59")."'";
        }
        */

        $query_where_parts[] = " UNIX_TIMESTAMP(ci.customers_info_date_account_created) >= '".strtotime($today . " 00:00:00")."'";
        $query_where_parts[] = " UNIX_TIMESTAMP(ci.customers_info_date_account_created) <= '".strtotime($today . " 23:59:59")."'";
        $query = "SELECT COUNT(c.customers_id) AS count_customers
                    FROM ".$this->sDBPrefix."customers AS c
                    LEFT JOIN ".$this->sDBPrefix."customers_info AS ci ON ci.customers_info_id = c.customers_id";
        if (!empty($query_where_parts)) {
            $query .= " WHERE " . implode(" AND ", $query_where_parts);
        }

        $result = mysql_query($query);
        if(mysql_num_rows($result) > 0) {
            $row = mysql_fetch_assoc($result);
            $store_stats = array_merge($store_stats, $row);
        }

        $this->graph_to = $today;
        $this->graph_from = date("Y-m-d", mktime(0, 0, 0, date("m"), date("d")-7, date("Y")));
        $data_graphs = $this->get_data_graphs();

        $store_stats['count_orders'] = $this->bd_nice_number($store_stats['count_orders'], true);
        $store_stats['count_customers'] = $this->bd_nice_number($store_stats['count_customers'], true);

        $result = array_merge($store_stats, array('data_graphs' => $data_graphs));
        return $result;
    }

    public function get_data_graphs() {
        $default_attrs = $this->_get_default_attrs();
        $startDate = strtotime($this->graph_from." 00:00:00");
        $endDate = strtotime($this->graph_to." 23:59:59");
        $date = $startDate;
        $d = 0;
        $average = array('avg_sum_orders' => 0, 'avg_orders' => 0, 'avg_customers' => 0, 'avg_cust_order' => '0.00');
        while ($date <= $endDate) {
            $d++;
            $query = "SELECT UNIX_TIMESTAMP(o.date_purchased) AS date_add, SUM(ot.value) AS value, COUNT(o.orders_id) AS tot_orders
                      FROM ".$this->sDBPrefix."orders AS o
                        LEFT JOIN ".$this->sDBPrefix."orders_total AS ot ON ot.orders_id = o.orders_id AND ot.class = 'ot_total'
                      WHERE UNIX_TIMESTAMP(o.date_purchased) >= '".$date."'
                        AND UNIX_TIMESTAMP(o.date_purchased) < '".strtotime('+1 day', $date)."'";

            if(!empty($this->statuses)) {
                $statuses = explode("|", $this->statuses);
                if(!empty($statuses)) {
                    $stat = array();
                    foreach($statuses as $status) {
                        if($status != "") {
                            $stat[] = $status;
                        }
                    }
                    $parse_statuses = implode("','", $stat);
                    $query .= " AND o.orders_status IN ('".$parse_statuses."')";
                }
            }
            $query .= " GROUP BY DATE(o.date_purchased) ORDER BY o.date_purchased";

            $result = mysql_query($query);
            if(mysql_num_rows($result) > 0) {
                while($row = mysql_fetch_assoc($result)) {
                    $row['value'] = number_format($row['value'], 2, '.', '');
                    $orders[] = array($row['date_add']*1000, $row['value']);
                    $average['tot_orders'] += $row['tot_orders'];
                    $average['sum_orders'] += $row['value'];
                }
            } else {
                $orders[] = array($date*1000, 0);
            }

            $query = "SELECT COUNT(c.customers_id) AS tot_customers, UNIX_TIMESTAMP(ci.customers_info_date_account_created) AS date_add
                      FROM ".$this->sDBPrefix."customers AS c
                        LEFT JOIN ".$this->sDBPrefix."customers_info AS ci ON ci.customers_info_id = c.customers_id
                      WHERE UNIX_TIMESTAMP(ci.customers_info_date_account_created) >= '".$date."'
                        AND UNIX_TIMESTAMP(ci.customers_info_date_account_created) <= '".strtotime('+1 day', $date)."'
                      GROUP BY DATE(ci.customers_info_date_account_created)
                      ORDER BY ci.customers_info_date_account_created";

            $result = mysql_query($query);
            if(mysql_num_rows($result) > 0) {
                while($row = mysql_fetch_assoc($result)) {
                    $customers[] = array($row['date_add']*1000, $row['tot_customers']);
                    $average['tot_customers'] += $row['tot_customers'];
                }
            } else {
                $customers[] = array($date*1000, 0);
            }

            $date = strtotime('+1 day', $date);
        }

        $average['avg_sum_orders'] = number_format($average['sum_orders']/$d, 2, '.', '');
        $average['avg_orders'] = number_format($average['tot_orders']/$d, 1, '.', '');
        $average['avg_customers'] = number_format($average['tot_customers']/$d, 1, '.', '');

        if($average['tot_customers'] > 0) {
            $average['avg_cust_order'] = number_format($average['sum_orders']/$average['tot_customers'], 1, '.', '');
        }
        $average['sum_orders'] = number_format($average['sum_orders'], 2, '.', '');
        $average['tot_customers'] = number_format($average['tot_customers'], 1, '.', '');
        $average['tot_orders'] = number_format($average['tot_orders'], 1, '.', '');
        return array('orders' => $orders, 'customers' => $customers, 'currency_sign_l' => $default_attrs['DEFAULT_CURRENCY_SIGN_LEFT'], 'currency_sign_r' => $default_attrs['DEFAULT_CURRENCY_SIGN_RIGHT'], 'average' => $average);
    }

    public function get_orders() {
        $orders = array();
        $query_where_parts = array();
        if(!empty($this->search_order_id)) {
            $query_where_parts[] = " o.orders_id = '".$this->search_order_id."'";
        }
        if(!empty($this->orders_from)) {
            $query_where_parts[] = " UNIX_TIMESTAMP(o.date_purchased) >= '".strtotime($this->orders_from." 00:00:00")."'";
        }
        if(!empty($this->orders_to)) {
            $query_where_parts[] = " UNIX_TIMESTAMP(o.date_purchased) <= '".strtotime($this->orders_to." 23:59:59")."'";
        }
        if(!empty($this->statuses)) {
            $statuses = explode("|", $this->statuses);
            if(!empty($statuses)) {
                $stat = array();
                foreach($statuses as $status) {
                    if($status != "") {
                        $stat[] = $status;
                    }
                }
                $parse_statuses = implode("','", $stat);
                $query_where_parts[] = " o.orders_status IN ('".$parse_statuses."')";
            }
        }

        $default_attrs = $this->_get_default_attrs();
        $query = "SELECT
                    o.orders_id AS id_order,
                    o.date_purchased AS date_add,
                    o.currency,
                    ot.value,
                    o.customers_name AS customer,
                    os.orders_status_name AS ord_status,
                    (SELECT COUNT(products_id) FROM ".$this->sDBPrefix."orders_products WHERE orders_id = o.orders_id) AS count_prods
				  FROM ".$this->sDBPrefix."orders AS o
				    LEFT JOIN ".$this->sDBPrefix."orders_total AS ot ON ot.orders_id = o.orders_id AND ot.class = 'ot_total'
				    LEFT JOIN ".$this->sDBPrefix."orders_status AS os ON os.orders_status_id = o.orders_status AND os.language_id = '".$default_attrs['DEFAULT_LANGUAGE_ID']."'";
        $query_page = "SELECT COUNT(o.orders_id) AS count_ords, MAX(o.date_purchased) AS max_date, MIN(o.date_purchased) AS min_date, SUM(ot.value) AS orders_total
                       FROM ".$this->sDBPrefix."orders AS o
                         LEFT JOIN ".$this->sDBPrefix."orders_total AS ot ON o.orders_id = ot.orders_id AND ot.class = 'ot_total'";
        if (!empty($query_where_parts)) {
            $query .= " WHERE " . implode(" AND ", $query_where_parts);
            $query_page .= " WHERE " . implode(" AND ", $query_where_parts);
        }

        $result_page = mysql_query($query_page);
        $row_page = mysql_fetch_assoc($result_page);
        $query .= " ORDER BY o.orders_id DESC LIMIT ".($this->page - 1)*$this->show." , ".$this->show;
        $result = mysql_query($query);
        while($row = mysql_fetch_assoc($result)) {
            $row['total_paid'] = $this->_price_format($row['currency'], $row['value']);
            $orders[] = $row;
        }

        $orders_total = $this->_price_format($default_attrs['DEFAULT_CURRENCY'], $row_page['orders_total']);
        if($row_page['count_ords'] > 0) {
            $max_date = date("n/j/Y", strtotime($row_page['max_date']));
            $min_date = date("n/j/Y", strtotime($row_page['min_date']));
        }

        $orders_status = null;
        if(isset($this->get_statuses) && $this->get_statuses == 1) {
            $orders_status = $this->get_orders_statuses();
        }

        return array("orders" => $orders,
            "orders_count" => intval($row_page['count_ords']),
            "orders_total" => $orders_total,
            "max_date" => $max_date,
            "min_date" => $min_date,
            "orders_status" => $orders_status
        );
    }

    public function get_orders_statuses() {
        $default_attrs = $this->_get_default_attrs();
        $orders_status = array();
        $query = "SELECT orders_status_id AS st_id, orders_status_name AS st_name FROM ".$this->sDBPrefix."orders_status WHERE language_id = '".$default_attrs['DEFAULT_LANGUAGE_ID']."' ORDER BY orders_status_name";
        $result_status = mysql_query($query);
        while($row = mysql_fetch_assoc($result_status)) {
            $orders_status[] = $row;
        }
        return $orders_status;
    }

    public function get_orders_info() {
        $order_products = array();
        $order_info = array();
        $default_attrs = $this->_get_default_attrs();
        $query = "SELECT
					o.orders_id AS id_order,
					o.customers_id AS id_customer,
					o.date_purchased AS date_add,
					o.currency,
					ot_tot.value AS order_total,
					o.customers_name AS customer,
					o.customers_email_address AS email,
					os.orders_status_name AS status,
					o.delivery_name,
					o.delivery_company,
					o.delivery_street_address,
					o.delivery_suburb,
					o.delivery_city,
					o.delivery_postcode,
					o.delivery_state,
					o.delivery_country,
					o.billing_name,
					o.billing_company,
					o.billing_street_address,
					o.billing_suburb,
					o.billing_city,
					o.billing_postcode,
					o.billing_state,
					o.billing_country
				  FROM ".$this->sDBPrefix."orders AS o
				    LEFT JOIN ".$this->sDBPrefix."orders_total AS ot_tot ON ot_tot.orders_id = o.orders_id AND ot_tot.class = 'ot_total'
				    LEFT JOIN ".$this->sDBPrefix."orders_status AS os ON os.orders_status_id = o.orders_status AND os.language_id = '".$default_attrs['DEFAULT_LANGUAGE_ID']."'
				  WHERE o.orders_id = '".$this->order_id."'";
        $result = mysql_query($query);
        $order_info = mysql_fetch_assoc($result);
        $order_info['order_total'] = $this->_price_format($order_info['currency'], $order_info['order_total']);

        $order_total = array();
        $query_total = "SELECT title, value FROM ".$this->sDBPrefix."orders_total WHERE orders_id = '".$this->order_id."' AND class <> 'ot_total' ORDER BY sort_order";
        $result_total = mysql_query($query_total);
        while($row_total = mysql_fetch_assoc($result_total)) {
            $order_total[] = array('title' => $row_total['title'], 'value' => $this->_price_format($order_info['currency'], $row_total['value']));
        }

        $query = "SELECT
					orders_id AS id_order,
					products_id AS product_id,
					products_name,
					products_quantity,
					final_price,
					products_tax,
					products_model AS sku
 				 FROM ".$this->sDBPrefix."orders_products
				 WHERE orders_id = '".$this->order_id."' ";
        $query_page = "SELECT COUNT(products_id) AS count_prods FROM ".$this->sDBPrefix."orders_products WHERE orders_id = '".$this->order_id."' GROUP BY products_id";
        $result_page = mysql_query($query_page);
        $row_page = mysql_fetch_assoc($result_page);
        $query .= " ORDER BY orders_id DESC LIMIT ".($this->page - 1)*$this->show." , ".$this->show;
        $result = mysql_query($query);
        while($row = mysql_fetch_assoc($result)) {
            $row['product_price'] = $this->_price_format($order_info['currency'], ($row['final_price']+$row['final_price']/100*$row['products_tax'])*$row['products_quantity']);
            unset($row['products_tax']);
            $row['product_quantity'] = intval($row['products_quantity']);
            $row['product_name'] = $row['products_name'];
            $order_products[] = $row;
        }
        $order_full_info = array("order_info" => $order_info, "order_products" => $order_products, "o_products_count" => $row_page['count_prods'], "order_total" => $order_total);
        return $order_full_info;
    }

    public function get_customers() {
        $customers = array();
        $query_where_parts = array();
        if(!empty($this->customers_from)) {
            $query_where_parts[] = " UNIX_TIMESTAMP(ci.customers_info_date_account_created) >= '".strtotime($this->customers_from." 00:00:00")."'";
        }
        if(!empty($this->customers_to)) {
            $query_where_parts[] = " UNIX_TIMESTAMP(ci.customers_info_date_account_created) <= '".strtotime($this->customers_to." 23:59:59")."'";
        }
        if(!empty($this->search_val)) {
            $query_where_parts[] = " (c.customers_email_address LIKE '%".$this->search_val."%' OR c.customers_firstname LIKE '%".$this->search_val."%' OR c.customers_lastname LIKE '%".$this->search_val."%')";
        }
        if(!empty($this->cust_with_orders)) {
            $query_where_parts[] = " tot.total_orders > 0";
        }
        $query = "SELECT
					c.customers_id AS id_customer,
					c.customers_firstname AS firstname,
					c.customers_lastname AS lastname,
					c.customers_email_address AS email,
					ci.customers_info_date_account_created AS date_add,
                    IFNULL(tot.total_orders, 0) AS total_orders
				  FROM ".$this->sDBPrefix."customers AS c
				  	LEFT JOIN ".$this->sDBPrefix."customers_info AS ci ON ci.customers_info_id = c.customers_id
                    LEFT OUTER JOIN (SELECT COUNT(orders_id) AS total_orders, customers_id FROM ".$this->sDBPrefix."orders GROUP BY customers_id) AS tot ON tot.customers_id = c.customers_id";

        $query_page = "SELECT ci.customers_info_date_account_created AS date_created,
                              IFNULL(tot.total_orders, 0)
					   FROM  ".$this->sDBPrefix."customers_info AS ci
					     LEFT JOIN ".$this->sDBPrefix."customers AS c ON c.customers_id = ci.customers_info_id
    				     LEFT OUTER JOIN (SELECT COUNT(orders_id) AS total_orders, customers_id FROM ".$this->sDBPrefix."orders GROUP BY customers_id) AS tot ON tot.customers_id = c.customers_id";
        if(!empty($query_where_parts)) {
            $query .= " WHERE " . implode(" AND ", $query_where_parts);
            $query_page .= " WHERE " . implode(" AND ", $query_where_parts);
        }

        $query .= " ORDER BY c.customers_id DESC LIMIT ".($this->page - 1)*$this->show." , ".$this->show;

        $row_page = array('max_date' => 0, 'min_date' => 0, 'count_custs' => 0);
        $result_page = mysql_query($query_page);
        if(mysql_num_rows($result_page) > 0) {
            while($row = mysql_fetch_assoc($result_page)) {
                $row_page['count_custs']++;
                if($row['date_created'] > $row_page['max_date']) {
                    $row_page['max_date'] = $row['date_created'];
                }
                if($row['date_created'] < $row_page['min_date']) {
                    $row_page['min_date'] = $row['date_created'];
                }
            }
        }

        $result = mysql_query($query);
        while($row = mysql_fetch_assoc($result)) {
            $row['total_orders'] = intval($row['total_orders']);
            $customers[] = $row;
        }
        if($row_page['count_custs'] > 0) {
            $max_date = date("n/j/Y", strtotime($row_page['max_date']));
            $min_date = date("n/j/Y", strtotime($row_page['min_date']));
        }
        return array("customers_count" => $row_page['count_custs'],
            "customers" => $customers,
            "max_date" => $max_date,
            "min_date" => $min_date);
    }

    public function get_customers_info() {
        $customer_orders = array();
        $query = "SELECT c.*,
					ab.*,
					c.customers_email_address AS email,
					ci.customers_info_date_account_created AS date_add,
					c.customers_firstname AS firstname,
					c.customers_lastname AS lastname,
					co.countries_name
				  FROM ".$this->sDBPrefix."customers AS c
				  	LEFT JOIN ".$this->sDBPrefix."customers_info AS ci ON ci.customers_info_id = c.customers_id
				  	LEFT JOIN ".$this->sDBPrefix."address_book AS ab ON ab.customers_id = c.customers_id AND ab.address_book_id = c.customers_default_address_id
				  	LEFT JOIN ".$this->sDBPrefix."countries AS co ON co.countries_id = ab.entry_country_id
				  WHERE c.customers_id = '".$this->user_id."'";

        $result = mysql_query($query);
        $user_info = mysql_fetch_assoc($result);
        $user_info['address'] = $this->split_values($user_info, array('countries_name', 'entry_city', 'entry_street_address'));
        $user_info['phone'] = (isset($user_info['customers_telephone']) ? $user_info['customers_telephone'] : $user_info['entry_telephone']);

        $default_attrs = $this->_get_default_attrs();
        $query = "SELECT
					o.orders_id AS id_order,
					o.currency,
					ot.value,
					o.date_purchased AS date_add,
					os.orders_status_name AS ord_status,
					(SELECT SUM(products_quantity) FROM ".$this->sDBPrefix."orders_products WHERE orders_id = o.orders_id) AS pr_qty
				  FROM ".$this->sDBPrefix."orders AS o
				    LEFT JOIN ".$this->sDBPrefix."orders_total AS ot ON ot.orders_id = o.orders_id AND ot.class = 'ot_total'
				    LEFT JOIN ".$this->sDBPrefix."orders_status AS os ON os.orders_status_id = o.orders_status AND os.language_id = '".$default_attrs['DEFAULT_LANGUAGE_ID']."'
				  WHERE o.customers_id = '".$this->user_id."'";
        $query_page = "SELECT COUNT(o.orders_id) AS count_ords, SUM(ot.value) AS sum_ords
                        FROM ".$this->sDBPrefix."orders AS o
                        LEFT JOIN ".$this->sDBPrefix."orders_total AS ot ON ot.orders_id = o.orders_id AND ot.class = 'ot_total'
                       WHERE o.customers_id = '".$this->user_id."' GROUP BY o.orders_id";
        $result_page = mysql_query($query_page);
        $row_page = mysql_fetch_assoc($result_page);
        $row_page['sum_ords'] = $this->_price_format($default_attrs['DEFAULT_CURRENCY'], $row_page['sum_ords']);

        $query .= " ORDER BY o.orders_id DESC LIMIT ".($this->page - 1)*$this->show." , ".$this->show;
        $result = mysql_query($query);
        while($row = mysql_fetch_assoc($result)) {
            $row['total_paid'] = $this->_price_format($row['currency'], $row['value']);
            $customer_orders[] = $row;
        }
        $customer_info = array("user_info" => $user_info, "customer_orders" => $customer_orders, "c_orders_count" => intval($row_page['count_ords']), "sum_ords" => $row_page['sum_ords']);
        return $customer_info;
    }

    public function search_products() {
        $query_where_parts = array();
        $products = array();
        $this->params = explode("|", $this->params);
        foreach($this->params as $param) {
            switch ($param) {
                case 'pr_id':
                    $query_where_parts[] = " p.products_id = '".$this->val."'";
                    break;
                case 'pr_sku':
                    $query_where_parts[] = " p.products_model = '".$this->val."'";
                    break;
                case 'pr_name':
                    $query_where_parts[] = " pd.products_name LIKE '%".$this->val."%'";
                    break;
                case 'pr_desc':
                case 'pr_short_desc':
                    $query_where_parts[] = " pd.products_description LIKE '%".$this->val."%'";
                    break;
            }
        }
        $default_attrs = $this->_get_default_attrs();
        $query = "SELECT
                    p.products_id AS main_id,
                    pd.products_name AS name,
                    p.products_price,
                    sp.specials_new_products_price,
                    p.products_model AS sku,
                    p.products_quantity AS quantity
				  FROM ".$this->sDBPrefix."products AS p
				    LEFT JOIN ".$this->sDBPrefix."specials AS sp ON sp.products_id = p.products_id
				    LEFT JOIN ".$this->sDBPrefix."products_description AS pd ON pd.products_id = p.products_id AND language_id = '".$default_attrs['DEFAULT_LANGUAGE_ID']."'";
        $query_page = "SELECT COUNT(p.products_id) AS count_prods FROM ".$this->sDBPrefix."products AS p
				    	LEFT JOIN ".$this->sDBPrefix."products_description AS pd ON pd.products_id = p.products_id AND language_id = '".$default_attrs['DEFAULT_LANGUAGE_ID']."'";
        if (!empty($query_where_parts)) {
            $query .= " WHERE ( " . implode(" OR ", $query_where_parts) . " )";
            $query_page .= " WHERE ( " . implode(" OR ", $query_where_parts) . " )";
        }

        $result_page = mysql_query($query_page);
        $row_page = mysql_fetch_assoc($result_page);
        $query .= " ORDER BY p.products_id DESC LIMIT ".($this->page - 1)*$this->show." , ".$this->show;
        $result = mysql_query($query);
        while($row = mysql_fetch_assoc($result)) {
            $row['price'] = $this->_price_format($default_attrs['DEFAULT_CURRENCY'], $row['products_price']);
            if($row['specials_new_products_price'] > 0) {
                $row['spec_price'] = $this->_price_format($default_attrs['DEFAULT_CURRENCY'], $row['specials_new_products_price']);
            }
            $products[] = $row;
        }
        return array("products_count" => $row_page['count_prods'], "products" => $products);
    }

    public function search_products_ordered() {
        $query_where_parts = array();
        $products = array();
        $default_attrs = $this->_get_default_attrs();
        $this->params = explode("|", $this->params);
        foreach($this->params as $param) {
            switch ($param) {
                case 'pr_id':
                    $query_where_parts[] = " op.products_id = '".$this->val."'";
                    break;
                case 'pr_sku':
                    $query_where_parts[] = " op.products_model = '".$this->val."'";
                    break;
                case 'pr_name':
                    $query_where_parts[] = " op.products_name LIKE '%".$this->val."%'";
                    break;
            }
        }
        if(!empty($this->products_from)) {
            $query_where_parts[] = " UNIX_TIMESTAMP(o.date_purchased) >= '".strtotime($this->products_from." 00:00:00")."'";
        }
        if(!empty($this->products_to)) {
            $query_where_parts[] = " UNIX_TIMESTAMP(o.date_purchased) <= '".strtotime($this->products_to." 23:59:59")."'";
        }
        $query = "SELECT
                    op.orders_id AS main_id,
                    op.products_model AS sku,
                    op.products_name AS name,
                    op.products_price,
                    op.final_price,
                    op.products_quantity AS quantity,
                    os.orders_status_name AS status
				  FROM ".$this->sDBPrefix."orders_products AS op
				    LEFT JOIN ".$this->sDBPrefix."orders AS o ON o.orders_id = op.orders_id
				    LEFT JOIN ".$this->sDBPrefix."orders_status AS os ON os.orders_status_id = o.orders_status AND os.language_id = '".$default_attrs['DEFAULT_LANGUAGE_ID']."'";

        $query_page = "SELECT COUNT(op.products_id) AS count_prods, MAX(o.date_purchased) AS max_date, MIN(o.date_purchased) AS min_date FROM ".$this->sDBPrefix."orders_products AS op
				    	LEFT JOIN ".$this->sDBPrefix."orders AS o ON o.orders_id = op.orders_id";
        if (!empty($query_where_parts)) {
            $query .= " WHERE " . implode(" AND ", $query_where_parts);
            $query_page .= " WHERE " . implode(" AND ", $query_where_parts);
        }

        $result_page = mysql_query($query_page);
        $row_page = mysql_fetch_assoc($result_page);
        $query .= " ORDER BY op.orders_id DESC LIMIT ".($this->page - 1)*$this->show." , ".$this->show;
        $result = mysql_query($query);

        while($row = mysql_fetch_assoc($result)) {
            $row['price'] = $this->_price_format($default_attrs['DEFAULT_CURRENCY'], $row['products_price']);
            if($row['final_price'] > 0) {
                $row['final_price'] = $this->_price_format($default_attrs['DEFAULT_CURRENCY'], $row['final_price']);
            } else {
                unset($row['final_price']);
            }
            $products[] = $row;
        }

        if($row_page['count_prods'] > 0) {
            $max_date = date("n/j/Y", strtotime($row_page['max_date']));
            $min_date = date("n/j/Y", strtotime($row_page['min_date']));
        }
        return array("products_count" => $row_page['count_prods'],
            "products" => $products,
            "max_date" => $max_date,
            "min_date" => $min_date);
    }

    public function get_products_info() {
        $default_attrs = $this->_get_default_attrs();
        $query = "SELECT
					p.products_id AS id_product,
					pd.products_name AS name,
					p.products_model AS sku,
					p.products_price,
					sp.specials_new_products_price,
					p.products_quantity AS quantity,
					IF(p.products_status = 1, 'Active', 'Inactive') AS active,
					p.products_image,
					p.products_ordered AS total_ordered,
					pd.products_description AS descr
				FROM ".$this->sDBPrefix."products AS p
				LEFT JOIN ".$this->sDBPrefix."specials AS sp ON sp.products_id = p.products_id
				LEFT JOIN ".$this->sDBPrefix."products_description AS pd ON pd.products_id = p.products_id AND language_id = '".$default_attrs['DEFAULT_LANGUAGE_ID']."'
				WHERE p.products_id = '".$this->product_id."'";

        $result = mysql_query($query);
        if(mysql_num_rows($result) == 0) {
            return false;
        }
        $row = mysql_fetch_assoc($result);

        $row['price'] = $this->_price_format($default_attrs['DEFAULT_CURRENCY'], $row['products_price']);
        if($row['specials_new_products_price'] > 0) {
            $row['spec_price'] = $this->_price_format($default_attrs['DEFAULT_CURRENCY'], $row['specials_new_products_price']);
        }
        $row['products_image'] = 'images/' . $row['products_image'];
        if(file_exists($row['products_image']) && is_file($row['products_image'])) {
            $row['id_image'] = $this->site_url . $row['products_image'];
        }
        return $row;
    }

    public function get_products_descr() {
        $default_attrs = $this->_get_default_attrs();
        $query = "SELECT pd.products_description AS descr
				FROM ".$this->sDBPrefix."products AS p
				LEFT JOIN ".$this->sDBPrefix."products_description AS pd ON pd.products_id = p.products_id AND language_id = '".$default_attrs['DEFAULT_LANGUAGE_ID']."'
				WHERE p.products_id = '".$this->product_id."'";
        $row = mysql_fetch_assoc(mysql_query($query));

        return $row;
    }

    private function _get_default_attrs() {
        $default_attrs = array();
        $query = "SELECT configuration_key, configuration_value FROM ".$this->sDBPrefix."configuration WHERE configuration_key IN ( 'DEFAULT_CURRENCY', 'DEFAULT_LANGUAGE' )";
        $result = mysql_query($query);
        while($row = mysql_fetch_assoc($result)) {
            $default_attrs[$row['configuration_key']] = $row['configuration_value'];
            if($row['configuration_key'] == 'DEFAULT_LANGUAGE') {
                $query_lang = "SELECT languages_id FROM ".$this->sDBPrefix."languages WHERE code = '".$row['configuration_value']."'";
                $row_lang = mysql_fetch_assoc(mysql_query($query_lang));
                $default_attrs['DEFAULT_LANGUAGE_ID'] = $row_lang['languages_id'];
            }
            if($row['configuration_key'] == 'DEFAULT_CURRENCY') {
                $query_sign = "SELECT symbol_left, symbol_right, decimal_point, decimal_places, value FROM  ".$this->sDBPrefix."currencies WHERE code = '".$row['configuration_value']."'";
                $row_sign = mysql_fetch_assoc(mysql_query($query_sign));
                $default_attrs['DEFAULT_CURRENCY_SIGN_LEFT'] = $row_sign['symbol_left'];
                $default_attrs['DEFAULT_CURRENCY_SIGN_RIGHT'] = $row_sign['symbol_right'];
            }
        }
        return $default_attrs;
    }

    private function _price_format($sign, $price, $clear = false, $short_numb = false) {
        $query = "SELECT symbol_left, symbol_right, decimal_point, decimal_places, value FROM  ".$this->sDBPrefix."currencies WHERE code = '".$sign."'";
        $row = mysql_fetch_assoc(mysql_query($query));
        if($short_numb) {
            $result = ($row['symbol_left'] ? '<span>' . $row['symbol_left'] . '</span>' : '') . $this->bd_nice_number(round($price,2)*$row['value']) . ($row['symbol_right'] ? '<span>' . $row['symbol_right'] . '</span>' : '');
        } elseif($clear) {
            $result = number_format(round($price,2)*$row['value'], intval($row['decimal_places']), $row['decimal_point'], '');
        } else {
            $result = ($row['symbol_left'] ? '<span>' . $row['symbol_left'] . '</span>' : '') . number_format(round($price,2)*$row['value'], intval($row['decimal_places']), $row['decimal_point'], '') . ($row['symbol_right'] ? '<span>' . $row['symbol_right'] . '</span>' : '');
        }
        return $result;
    }
}


class PrestaShopSA extends MainSA {
    public function get_store_title() {
        $query = "SELECT value FROM  ".$this->sDBPrefix."configuration WHERE name = 'PS_SHOP_NAME'";
        $result = mysql_query($query);
        $row = mysql_fetch_assoc($result);
        return array('test' => 1, 'title' => $row['value']);
    }

    public function get_store_stats() {
        $store_stats = array('count_orders' => "0", 'total_sales' => "0", 'count_customers' => "0", "last_order_id" => "0", "new_orders" => "0");
        $default_attrs = $this->_get_default_attrs();
        /*
        if(!empty($this->date_from)) {
            $query_where_parts[] = " UNIX_TIMESTAMP(o.date_add) >= '".strtotime($this->date_from." 00:00:00")."'";
        }
        if(!empty($this->date_to)) {
            $query_where_parts[] = " UNIX_TIMESTAMP(o.date_add) <= '".strtotime($this->date_to." 23:59:59")."'";
        }
        */

        $today = date("Y-m-d", time());
        $query_where_parts[] = " UNIX_TIMESTAMP(o.date_add) >= '".strtotime($today . " 00:00:00")."'";
        $query_where_parts[] = " UNIX_TIMESTAMP(o.date_add) <= '".strtotime($today . " 23:59:59")."'";

        if(!empty($this->statuses)) {
            $statuses = explode("|", $this->statuses);
            if(!empty($statuses)) {
                $stat = array();
                foreach($statuses as $status) {
                    if($status != "") {
                        $stat[] = $status;
                    }
                }
                $parse_statuses = implode("','", $stat);
                $query_where_parts[] = " oh.id_order_state IN ('".$parse_statuses."')";
            }
        }

        $query = "SELECT COUNT(o.id_order) AS count_orders, SUM(o.total_paid/c.conversion_rate) AS total_sales
		 		  FROM ".$this->sDBPrefix."orders AS o
				    LEFT JOIN ".$this->sDBPrefix."currency AS c ON o.id_currency = c.id_currency
                    LEFT JOIN (SELECT id_order, id_order_history, id_order_state FROM ".$this->sDBPrefix."order_history ORDER BY id_order, id_order_history DESC) AS oh ON oh.id_order = o.id_order";
        if (!empty($query_where_parts)) {
            $query .= " WHERE " . implode(" AND ", $query_where_parts);
        }

        $result = mysql_query($query);
        if(mysql_num_rows($result) > 0) {
            $row = mysql_fetch_assoc($result);
            $row['total_sales'] = $this->_price_format($default_attrs['sign'], $default_attrs['format'], $this->bd_nice_number($row['total_sales']), true);
            $store_stats = array_merge($store_stats, $row);
        }

        if($this->last_order_id != "") {
            $query_max = "SELECT COUNT(o.id_order) AS count_orders, MAX(o.id_order) AS last_order_id
                          FROM ".$this->sDBPrefix."orders AS o
                          LEFT JOIN (SELECT id_order, id_order_history, id_order_state FROM ".$this->sDBPrefix."order_history ORDER BY id_order, id_order_history DESC) AS oh ON oh.id_order = o.id_order
                          WHERE o.id_order > ".$this->last_order_id;
            if(!empty($query_where_parts)) {
                $query_max .= " AND " . implode(" AND ", $query_where_parts);
            }

            $result_max = mysql_query($query_max);
            if(mysql_num_rows($result_max) > 0) {
                $row_max = mysql_fetch_assoc($result_max);
                $store_stats['last_order_id'] = intval($this->last_order_id);
                if(intval($row_max['last_order_id']) > intval($this->last_order_id)) {
                    $store_stats['last_order_id'] = intval($row_max['last_order_id']);
                }
                $store_stats['new_orders'] = intval($row_max['count_orders']);
            }
        }

        unset($query_where_parts);
        /*
        if(!empty($this->date_from)) {
            $query_where_parts[] = " UNIX_TIMESTAMP(date_add) >= '".strtotime($this->date_from." 00:00:00")."'";
        }
        if(!empty($this->date_to)) {
            $query_where_parts[] = " UNIX_TIMESTAMP(date_add) <= '".strtotime($this->date_to." 23:59:59")."'";
        }
        */

        $query_where_parts[] = " UNIX_TIMESTAMP(date_add) >= '".strtotime($today . " 00:00:00")."'";
        $query_where_parts[] = " UNIX_TIMESTAMP(date_add) <= '".strtotime($today . " 23:59:59")."'";
        $query = "SELECT COUNT(id_customer) AS count_customers FROM ".$this->sDBPrefix."customer";
        if (!empty($query_where_parts)) {
            $query .= " WHERE " . implode(" AND ", $query_where_parts);
        }
        $result = mysql_query($query);
        if(mysql_num_rows($result) > 0) {
            $row = mysql_fetch_assoc($result);
            $store_stats = array_merge($store_stats, $row);
        }

        $this->graph_to = $today;
        $this->graph_from = date("Y-m-d", mktime(0, 0, 0, date("m"), date("d")-7, date("Y")));
        $data_graphs = $this->get_data_graphs();

        $store_stats['count_orders'] = $this->bd_nice_number($store_stats['count_orders'], true);
        $store_stats['count_customers'] = $this->bd_nice_number($store_stats['count_customers'], true);

        $result = array_merge($store_stats, array('data_graphs' => $data_graphs));
        return $result;
    }

    public function get_data_graphs() {
        $default_attrs = $this->_get_default_attrs();
        $startDate = strtotime($this->graph_from." 00:00:00");
        $endDate = strtotime($this->graph_to." 23:59:59");
        $date = $startDate;
        $d = 0;
        $average = array('avg_sum_orders' => 0, 'avg_orders' => 0, 'avg_customers' => 0, 'avg_cust_order' => '0.00');
        while ($date <= $endDate) {
            $d++;
            $query = "SELECT
                        UNIX_TIMESTAMP(o.date_add) AS date_add,
                        SUM(o.total_paid/c.conversion_rate) AS value,
                        COUNT(o.id_order) AS tot_orders
                      FROM ".$this->sDBPrefix."orders AS o
                      LEFT JOIN ".$this->sDBPrefix."currency AS c ON o.id_currency = c.id_currency
                      LEFT JOIN (SELECT id_order, id_order_history, id_order_state FROM ".$this->sDBPrefix."order_history ORDER BY id_order, id_order_history DESC) AS oh ON oh.id_order = o.id_order
                       WHERE UNIX_TIMESTAMP(o.date_add) >= '".$date."'
                       AND UNIX_TIMESTAMP(o.date_add) < '".strtotime('+1 day', $date)."'";

            if(!empty($this->statuses)) {
                $statuses = explode("|", $this->statuses);
                if(!empty($statuses)) {
                    $stat = array();
                    foreach($statuses as $status) {
                        if($status != "") {
                            $stat[] = $status;
                        }
                    }
                    $parse_statuses = implode("','", $stat);
                    $query .= " AND oh.id_order_state IN ('".$parse_statuses."')";
                }
            }
            $query .= " GROUP BY DATE(o.date_add) ORDER BY o.date_add";

            $result = mysql_query($query);

            if(mysql_num_rows($result) > 0) {
                while($row = mysql_fetch_assoc($result)) {
                    $row['value'] = number_format($row['value'], 2, '.', '');
                    $orders[] = array($row['date_add']*1000, $row['value']);
                    $average['tot_orders'] += $row['tot_orders'];
                    $average['sum_orders'] += $row['value'];
                }
            } else {
                $orders[] = array($date*1000, 0);
            }

            $query = "SELECT COUNT(id_customer) AS tot_customers, UNIX_TIMESTAMP(date_add) AS date_add
                      FROM ".$this->sDBPrefix."customer
                      WHERE UNIX_TIMESTAMP(date_add) >= '".$date."'
                        AND UNIX_TIMESTAMP(date_add) < '".strtotime('+1 day', $date)."'
                       GROUP BY DATE(date_add) ORDER BY date_add";

            $result = mysql_query($query);

            if(mysql_num_rows($result) > 0) {
                while($row = mysql_fetch_assoc($result)) {
                    $customers[] = array($row['date_add']*1000, $row['tot_customers']);
                    $average['tot_customers'] += $row['tot_customers'];
                }
            } else {
                $customers[] = array($date*1000, 0);
            }
            $date = strtotime('+1 day', $date);
        }

        $average['avg_sum_orders'] = number_format($average['sum_orders']/$d, 2, '.', '');
        $average['avg_orders'] = number_format($average['tot_orders']/$d, 1, '.', '');
        $average['avg_customers'] = number_format($average['tot_customers']/$d, 1, '.', '');

        if($average['tot_customers'] > 0) {
            $average['avg_cust_order'] = number_format($average['sum_orders']/$average['tot_customers'], 1, '.', '');
        }
        $average['sum_orders'] = number_format($average['sum_orders'], 2, '.', '');
        $average['tot_customers'] = number_format($average['tot_customers'], 1, '.', '');
        $average['tot_orders'] = number_format($average['tot_orders'], 1, '.', '');
        return array('orders' => $orders, 'customers' => $customers, 'currency_sign' => $default_attrs['sign'], 'average' => $average);
    }

    public function get_orders() {
        $orders = array();
        $default_attrs = $this->_get_default_attrs();
        if(!empty($this->search_order_id)) {
            $query_where_parts[] = " o.id_order = '".$this->search_order_id."'";
        }
        if(!empty($this->orders_from)) {
            $query_where_parts[] = " UNIX_TIMESTAMP(o.date_add) >= '".strtotime($this->orders_from." 00:00:00")."'";
        }
        if(!empty($this->orders_to)) {
            $query_where_parts[] = " UNIX_TIMESTAMP(o.date_add) <= '".strtotime($this->orders_to." 23:59:59")."'";
        }
        if(!empty($this->statuses)) {
            $statuses = explode("|", $this->statuses);
            if(!empty($statuses)) {
                $stat = array();
                foreach($statuses as $status) {
                    if($status != "") {
                        $stat[] = $status;
                    }
                }
                $parse_statuses = implode("','", $stat);
                $query_where_parts[] = " oh.id_order_state IN ('".$parse_statuses."')";
            }
        }

        $query = "SELECT o.id_order, o.date_add, o.total_paid, c.iso_code, c.sign, c.format, cus.firstname, cus.lastname, osl.name AS ord_status,
                        (SELECT COUNT(product_id) FROM ".$this->sDBPrefix."order_detail WHERE id_order = o.id_order) AS count_prods
				  FROM ".$this->sDBPrefix."orders AS o
				    LEFT JOIN ".$this->sDBPrefix."customer AS cus ON cus.id_customer = o.id_customer
				    LEFT JOIN ".$this->sDBPrefix."currency AS c ON o.id_currency = c.id_currency
                    LEFT JOIN (SELECT id_order, id_order_history, id_order_state FROM ".$this->sDBPrefix."order_history ORDER BY id_order, id_order_history DESC) AS oh ON oh.id_order = o.id_order
                    LEFT JOIN ".$this->sDBPrefix."order_state_lang AS osl ON osl.id_order_state = oh.id_order_state AND osl.id_lang = '".$default_attrs['lang_id']."'";

        $query_page = "SELECT COUNT(o.id_order) AS count_ords, MAX(o.date_add) AS max_date, MIN(o.date_add) AS min_date, SUM(o.total_paid/c.conversion_rate) AS orders_total
				  FROM ".$this->sDBPrefix."orders AS o
				    LEFT JOIN ".$this->sDBPrefix."customer AS cus ON cus.id_customer = o.id_customer
				    LEFT JOIN (SELECT id_order, id_order_history, id_order_state FROM ".$this->sDBPrefix."order_history ORDER BY id_order, id_order_history DESC) AS oh ON oh.id_order = o.id_order
				    LEFT JOIN ".$this->sDBPrefix."currency AS c ON o.id_currency = c.id_currency";

        if (!empty($query_where_parts)) {
            $query .= " WHERE " . implode(" AND ", $query_where_parts);
            $query_page .= " WHERE " . implode(" AND ", $query_where_parts);
        }

        $result_page = mysql_query($query_page);
        $row_page = mysql_fetch_assoc($result_page);
        $query .= " GROUP BY o.id_order ORDER BY o.id_order DESC LIMIT ".($this->page - 1)*$this->show." , ".$this->show;

        $result = mysql_query($query);
        while($row = mysql_fetch_assoc($result)) {
            $row['total_paid'] = $this->_price_format($row['sign'], $row['format'], $row['total_paid']);
            $row['customer'] = $row['firstname'] . ' ' . $row['lastname'];
            $orders[] = $row;
        }
        if($row_page['count_ords'] > 0) {
            $max_date = date("n/j/Y", strtotime($row_page['max_date']));
            $min_date = date("n/j/Y", strtotime($row_page['min_date']));
        }

        $orders_status = null;
        if(isset($this->get_statuses) && $this->get_statuses == 1) {
            $orders_status = $this->get_orders_statuses();
        }

        $orders_total = $this->_price_format($default_attrs['sign'], $default_attrs['format'], $row_page['orders_total']);
        return array("orders" => $orders,
            "orders_count" => intval($row_page['count_ords']),
            "orders_total" => $orders_total,
            "max_date" => $max_date,
            "min_date" => $min_date,
            "orders_status" => $orders_status
        );
    }

    public function get_orders_statuses() {
        $orders_status = array();
        $default_attrs = $this->_get_default_attrs();
        $query = "SELECT id_order_state AS st_id, name AS st_name FROM ".$this->sDBPrefix."order_state_lang WHERE id_lang = '".$default_attrs['lang_id']."'";
        $result_status = mysql_query($query);
        while($row = mysql_fetch_assoc($result_status)) {
            $orders_status[] = $row;
        }
        return $orders_status;
    }

    public function get_orders_info() {
        $order_products = array();
        $order_info = array();
        $default_attrs = $this->_get_default_attrs();
        $query = "SELECT
                    o.id_order,
                    o.date_add,
                    o.id_customer,
                    o.total_paid,
                    o.total_discounts,
                    o.total_products,
                    o.total_products_wt,
                    o.total_shipping,
                    o.total_wrapping,
                    c.iso_code,
                    c.sign,
                    c.format,
                    CONCAT(cus.firstname, ' ', cus.lastname) AS customer,
                    cus.email,
                    CONCAT(ad.firstname, ' ', ad.lastname) AS d_name,
                    ad.company AS d_company,
                    ad.address1 AS d_address1,
                    ad.address2 AS d_address2,
                    ad.city AS d_city,
                    sd.name AS d_state,
                    ad.postcode AS d_postcode,
                    cld.name AS d_country,
                    ad.other AS d_other,
                    ad.phone AS d_phone,
                    ad.phone_mobile AS d_phone_mobile,
                    CONCAT(ai.firstname, ' ', ai.lastname) AS i_name,
                    ai.company AS i_company,
                    ai.address1 AS i_address1,
                    ai.address2 AS i_address2,
                    ai.city AS i_city,
                    si.name AS i_state,
                    ai.postcode AS i_postcode,
                    cli.name AS i_country,
                    ai.other AS i_other,
                    ai.phone AS i_phone,
                    ai.phone_mobile AS i_phone_mobile
				  FROM ".$this->sDBPrefix."orders AS o
				    LEFT JOIN ".$this->sDBPrefix."currency AS c ON o.id_currency = c.id_currency
				    LEFT JOIN ".$this->sDBPrefix."customer AS cus ON o.id_customer = cus.id_customer
				    LEFT JOIN ".$this->sDBPrefix."address AS ad ON o.id_address_delivery = ad.id_address
				    LEFT JOIN ".$this->sDBPrefix."address AS ai ON o.id_address_invoice = ai.id_address
				    LEFT JOIN ".$this->sDBPrefix."state AS sd ON ad.id_state = sd.id_state
				    LEFT JOIN ".$this->sDBPrefix."state AS si ON ai.id_state = si.id_state
				    LEFT JOIN ".$this->sDBPrefix."country_lang AS cld ON ad.id_country = cld.id_country AND cld.id_lang = '".$default_attrs['lang_id']."'
				    LEFT JOIN ".$this->sDBPrefix."country_lang AS cli ON ai.id_country = cli.id_country AND cli.id_lang = '".$default_attrs['lang_id']."'
				  WHERE o.id_order = '".$this->order_id."'";
        $result = mysql_query($query);
        $order_info = mysql_fetch_assoc($result);
        $order_info['total_paid_real'] = $order_info['total_paid'];

        $elements = array('total_paid', 'total_products', 'total_products_wt', 'total_discounts', 'total_shipping', 'total_wrapping', 'total_paid_real');
        foreach($elements as $element) {
            $order_info[$element] = $this->_price_format($order_info['sign'], $order_info['format'], $order_info[$element]);
        }

        $query_stat = "SELECT osl.name FROM ".$this->sDBPrefix."order_history AS oh
				         LEFT JOIN ".$this->sDBPrefix."order_state_lang AS osl ON osl.id_order_state = oh.id_order_state
				       WHERE oh.id_order = '".$this->order_id."' AND osl.id_lang = '".$default_attrs['lang_id']."'";
        $result_stat = mysql_query($query_stat);
        $row_stat = mysql_fetch_assoc($result_stat);
        $order_info['status'] = $row_stat['name'];

        $query = "SELECT
					od.id_order,
					od.product_id,
					od.tax_rate,
					od.reduction_percent,
					od.reduction_amount,
					od.group_reduction,
					pl.name AS product_name,
					od.product_ean13 AS sku,
					od.product_price,
					od.product_quantity,
					c.iso_code,
					c.sign,
					c.format
				  FROM ".$this->sDBPrefix."order_detail AS od
				    LEFT JOIN ".$this->sDBPrefix."orders AS o ON od.id_order = o.id_order
				    LEFT JOIN ".$this->sDBPrefix."product_lang AS pl ON od.product_id = pl.id_product AND o.id_lang = pl.id_lang
				    LEFT JOIN ".$this->sDBPrefix."currency AS c ON o.id_currency = c.id_currency
				  WHERE od.id_order = '".$this->order_id."'";
        $query_page = "SELECT COUNT(od.product_id) AS count_prods
					   FROM ".$this->sDBPrefix."order_detail AS od
						 LEFT JOIN ".$this->sDBPrefix."orders AS o ON od.id_order = o.id_order
						 LEFT JOIN ".$this->sDBPrefix."product_lang AS pl ON od.product_id = pl.id_product AND o.id_lang = pl.id_lang
					   WHERE od.id_order = '".$this->order_id."'";
        $query .= " LIMIT ".($this->page - 1)*$this->show." , ".$this->show;
        $result_page = mysql_query($query_page);
        $row_page = mysql_fetch_assoc($result_page);
        $result = mysql_query($query);
        while($row = mysql_fetch_assoc($result)) {
            //$product_tax = round($row['product_price']/100*$row['tax_rate'],2);
            //$red_percent = round(($row['product_price']+$product_tax)/100*$row['reduction_percent'],2);
            //$red_amount = $row['reduction_amount'];
            //$group_red = $row['group_reduction'];
            unset($row['tax_rate']); unset($row['reduction_percent']); unset($row['reduction_amount']); unset($row['group_reduction']);
            //$row['product_price'] = ($row['product_price'] + $product_tax - $red_percent - $red_amount - $group_red)*$row['product_quantity'];
            //$row['product_price'] = $this->_price_format($row['sign'], $row['format'], $row['product_price']);
            $row['product_price'] = "";
            unset($row['iso_code']); unset($row['sign']); unset($row['format']);
            $order_products[] = $row;
        }
        $order_full_info = array("order_info" => $order_info, "order_products" => $order_products, "o_products_count" => $row_page['count_prods']);
        return $order_full_info;
    }

    public function get_customers() {
        $customers = array();
        if(!empty($this->customers_from)) {
            $query_where_parts[] = " UNIX_TIMESTAMP(c.date_add) >= '".strtotime($this->customers_from." 00:00:00")."'";
        }
        if(!empty($this->customers_to)) {
            $query_where_parts[] = " UNIX_TIMESTAMP(c.date_add) <= '".strtotime($this->customers_to." 23:59:59")."'";
        }
        if(!empty($this->search_val)) {
            $query_where_parts[] = " (c.email LIKE '%".$this->search_val."%' OR c.firstname LIKE '%".$this->search_val."%' OR c.lastname LIKE '%".$this->search_val."%')";
        }
        if(!empty($this->cust_with_orders)) {
            $query_where_parts[] = " tot.total_orders > 0";
        }
        $query = "SELECT
                    c.id_customer,
                    c.firstname,
                    c.lastname,
                    c.email,
                    c.date_add,
                    IFNULL(tot.total_orders, 0) AS total_orders
				  FROM  ".$this->sDBPrefix."customer AS c
				  LEFT OUTER JOIN (SELECT COUNT(id_order) AS total_orders, id_customer FROM ".$this->sDBPrefix."orders GROUP BY id_customer) AS tot ON tot.id_customer = c.id_customer";
        $query_page = "SELECT COUNT(c.id_customer) AS count_custs, MAX(c.date_add) AS max_date, MIN(c.date_add) AS min_date FROM  ".$this->sDBPrefix."customer AS c
                       LEFT OUTER JOIN (SELECT COUNT(id_order) AS total_orders, id_customer FROM ".$this->sDBPrefix."orders GROUP BY id_customer) AS tot ON tot.id_customer = c.id_customer";
        if (!empty($query_where_parts)) {
            $query .= " WHERE " . implode(" AND ", $query_where_parts);
            $query_page .= " WHERE " . implode(" AND ", $query_where_parts);
        }

        $query .= " LIMIT ".($this->page - 1)*$this->show." , ".$this->show;
        $result_page = mysql_query($query_page);
        $row_page = mysql_fetch_assoc($result_page);
        $result = mysql_query($query);
        while($row = mysql_fetch_assoc($result)) {
            $date = explode(' ', $row['date_add']);
            $row['date_add'] = $date[0];
            $row['total_orders'] = intval($row['total_orders']);
            $customers[] = $row;
        }
        if($row_page['count_custs'] > 0) {
            $max_date = date("n/j/Y", strtotime($row_page['max_date']));
            $min_date = date("n/j/Y", strtotime($row_page['min_date']));
        }
        return array("customers_count" => $row_page['count_custs'],
            "customers" => $customers,
            "max_date" => $max_date,
            "min_date" => $min_date
        );
    }

    public function get_customers_info() {
        $customer_orders = array();
        $default_attrs = $this->_get_default_attrs();
        $query = "SELECT c.id_customer, c.firstname, c.lastname, c.date_add, c.email, a.address1, a.phone, a.city, cl.name AS country_name FROM ".$this->sDBPrefix."customer AS c
				    LEFT JOIN ".$this->sDBPrefix."address AS a ON a.id_customer = c.id_customer
				    LEFT JOIN ".$this->sDBPrefix."country_lang AS cl ON cl.id_country = a.id_country AND cl.id_lang = '".$default_attrs['lang_id']."'
				  WHERE c.id_customer = '".$this->user_id."'";

        $result = mysql_query($query);
        $user_info = mysql_fetch_assoc($result);
        $user_info['address'] = $this->split_values($user_info, array('country_name', 'city', 'address1'));

        $query = "SELECT o.id_order, o.date_add, o.total_paid, c.iso_code, c.sign, c.format, SUM(od.product_quantity) AS pr_qty
				  FROM ".$this->sDBPrefix."orders AS o
				    LEFT JOIN ".$this->sDBPrefix."currency AS c ON o.id_currency = c.id_currency
				    LEFT JOIN ".$this->sDBPrefix."order_detail AS od ON od.id_order = o.id_order
				  WHERE o.id_customer = '".$this->user_id."' GROUP BY o.id_order";

        $query_page = "SELECT COUNT(o.id_order) AS count_ords, SUM(o.total_paid/c.conversion_rate) AS sum_ords
					   FROM ".$this->sDBPrefix."orders AS o
						 LEFT JOIN ".$this->sDBPrefix."currency AS c ON o.id_currency = c.id_currency
					   WHERE o.id_customer = '".$this->user_id."'";

        $result_page = mysql_query($query_page);
        $row_page = mysql_fetch_assoc($result_page);
        $query .= " ORDER BY o.id_customer DESC LIMIT ".($this->page - 1)*$this->show." , ".$this->show;
        $result = mysql_query($query);
        while($row = mysql_fetch_assoc($result)) {
            $row['total_paid'] = $this->_price_format($row['sign'], $row['format'], $row['total_paid']);
            $row_stat = mysql_fetch_assoc(mysql_query("SELECT osl.name FROM ".$this->sDBPrefix."order_history AS oh
					         LEFT JOIN ".$this->sDBPrefix."order_state_lang AS osl ON osl.id_order_state = oh.id_order_state
					       WHERE oh.id_order = '".$row['id_order']."' AND osl.id_lang = '".$default_attrs['lang_id']."'"));
            $row['ord_status'] = $row_stat['name'];
            $customer_orders[] = $row;
        }

        $row_page['sum_ords'] = $this->_price_format($default_attrs['sign'], $default_attrs['format'], $row_page['sum_ords']);
        return array("user_info" => $user_info, "customer_orders" => $customer_orders, "c_orders_count" => intval($row_page['count_ords']), "sum_ords" => $row_page['sum_ords']);
    }

    public function search_products() {
        $query_where_parts = array();
        $products = array();
        $default_attrs = $this->_get_default_attrs();
        $this->params = explode("|", $this->params);
        foreach($this->params as $param) {
            switch ($param) {
                case 'pr_id':
                    $query_where_parts[] = " p.id_product = '".$this->val."'";
                    break;
                case 'pr_sku':
                    $query_where_parts[] = " p.ean13 = '".$this->val."'";
                    break;
                case 'pr_name':
                    $query_where_parts[] = " pl.name LIKE '%".$this->val."%'";
                    break;
                case 'pr_desc':
                    $query_where_parts[] = " pl.description LIKE '%".$this->val."%'";
                    break;
                case 'pr_short_desc':
                    $query_where_parts[] = " pl.description_short LIKE '%".$this->val."%'";
                    break;
            }
        }

        $row_quantity = "p.quantity";
        $join_stock_available = "";
        if(mysql_num_rows(mysql_query("SHOW TABLES LIKE '".$this->sDBPrefix."stock_available'"))==1) {
            $row_quantity = "sa.quantity";
            $join_stock_available = "LEFT JOIN ".$this->sDBPrefix."stock_available AS sa ON p.id_product = sa.id_product AND sa.id_product_attribute = 0";
        }

        $query = "SELECT p.id_product AS main_id, pl.name, p.price, ".$row_quantity.", p.ean13 AS sku, c.sign, c.format, c.iso_code FROM ".$this->sDBPrefix."product AS p
                LEFT JOIN ".$this->sDBPrefix."product_lang AS pl ON p.id_product = pl.id_product
                LEFT JOIN ".$this->sDBPrefix."currency AS c ON c.id_currency = '".$default_attrs['curr_id']."'
                ".$join_stock_available."
              WHERE pl.id_lang = '".$default_attrs['lang_id']."'";
        $query_page = "SELECT COUNT(p.id_product) AS count_prods FROM ".$this->sDBPrefix."product AS p
					     LEFT JOIN ".$this->sDBPrefix."product_lang AS pl ON p.id_product = pl.id_product
					     LEFT JOIN ".$this->sDBPrefix."currency AS c ON c.id_currency = '".$default_attrs['curr_id']."'
					   WHERE pl.id_lang = '".$default_attrs['lang_id']."'";
        if (!empty($query_where_parts)) {
            $query .= " AND ( " . implode(" OR ", $query_where_parts) . " )";
            $query_page .= " AND ( " . implode(" OR ", $query_where_parts) . " )";
        }
        $query .= " GROUP BY p.id_product LIMIT ".($this->page - 1)*$this->show." , ".$this->show;
        $result_page = mysql_query($query_page);
        $row_page = mysql_fetch_assoc($result_page);
        $result = mysql_query($query);
        while($row = mysql_fetch_assoc($result)) {
            $row['price'] = $this->_price_format($row['sign'], $row['format'], $row['price']);
            $products[] = $row;
        }
        return array("products_count" => $row_page['count_prods'], "products" => $products);
    }

    public function search_products_ordered() {
        $query_where_parts = array();
        $products = array();
        $default_attrs = $this->_get_default_attrs();
        $this->params = explode("|", $this->params);
        foreach($this->params as $param) {
            switch ($param) {
                case 'pr_id':
                    $query_where_parts[] = " od.product_id = '".$this->val."'";
                    break;
                case 'pr_sku':
                    $query_where_parts[] = " od.product_ean13 = '".$this->val."'";
                    break;
                case 'pr_name':
                    $query_where_parts[] = " od.product_name LIKE '%".$this->val."%'";
                    break;
            }
        }
        if(!empty($this->products_from)) {
            $query_where_parts[] = " UNIX_TIMESTAMP(o.date_add) >= '".strtotime($this->products_from." 00:00:00")."'";
        }
        if(!empty($this->products_to)) {
            $query_where_parts[] = " UNIX_TIMESTAMP(o.date_add) <= '".strtotime($this->products_to." 23:59:59")."'";
        }

        $query = "SELECT
					od.id_order AS main_id,
					od.product_id,
					od.tax_rate,
					od.reduction_percent,
					od.reduction_amount,
					od.group_reduction,
					pl.name,
					od.product_name AS name,
					od.product_ean13 AS sku,
					od.product_price,
					p.price AS real_product_price,
					od.product_quantity AS quantity,
					c.sign,
					c.format
				  FROM ".$this->sDBPrefix."order_detail AS od
				    LEFT JOIN ".$this->sDBPrefix."orders AS o ON od.id_order = o.id_order
				    LEFT JOIN ".$this->sDBPrefix."product AS p ON p.id_product = od.product_id
				    LEFT JOIN ".$this->sDBPrefix."product_lang AS pl ON od.product_id = pl.id_product AND o.id_lang = pl.id_lang
				    LEFT JOIN ".$this->sDBPrefix."currency AS c ON o.id_currency = c.id_currency";

        $query_page = "SELECT COUNT(od.product_id) AS count_prods, MAX(o.date_add) AS max_date, MIN(o.date_add) AS min_date
					   FROM ".$this->sDBPrefix."order_detail AS od
						 LEFT JOIN ".$this->sDBPrefix."orders AS o ON od.id_order = o.id_order
						 LEFT JOIN ".$this->sDBPrefix."product_lang AS pl ON od.product_id = pl.id_product AND o.id_lang = pl.id_lang";

        if (!empty($query_where_parts)) {
            $query .= " WHERE " . implode(" AND ", $query_where_parts);
            $query_page .= " WHERE " . implode(" AND ", $query_where_parts);
        }

        $query .= " ORDER BY od.id_order DESC LIMIT ".($this->page - 1)*$this->show." , ".$this->show;
        $result_page = mysql_query($query_page);
        $row_page = mysql_fetch_assoc($result_page);
        $result = mysql_query($query);
        while($row = mysql_fetch_assoc($result)) {
//            $product_tax = round($row['product_price']/100*$row['tax_rate'],2);
//            $red_percent = round(($row['product_price']+$product_tax)/100*$row['reduction_percent'],2);
//            $red_amount = $row['reduction_amount'];
//            $group_red = $row['group_reduction'];
            unset($row['tax_rate']); unset($row['reduction_percent']); unset($row['reduction_amount']); unset($row['group_reduction']);
//            $row['product_price'] = ($row['product_price'] + $product_tax - $red_percent - $red_amount - $group_red)*$row['quantity'];
//            $row['price'] = $this->_price_format($row['sign'], $row['format'], $row['product_price']);
            $row['price'] = $this->_price_format($row['sign'], $row['format'], $row['real_product_price']);
            unset($row['sign']); unset($row['format']);
            $row_stat = mysql_fetch_assoc(mysql_query("SELECT osl.name FROM ".$this->sDBPrefix."order_history AS oh
					         LEFT JOIN ".$this->sDBPrefix."order_state_lang AS osl ON osl.id_order_state = oh.id_order_state
					       WHERE oh.id_order = '".$row['main_id']."'  AND osl.id_lang = '".$default_attrs['lang_id']."'"));
            $row['status'] = $row_stat['name'];
            $products[] = $row;
        }
        if($row_page['count_prods'] > 0) {
            $max_date = date("n/j/Y", strtotime($row_page['max_date']));
            $min_date = date("n/j/Y", strtotime($row_page['min_date']));
        }

        return array("products_count" => $row_page['count_prods'],
            "products" => $products,
            "max_date" => $max_date,
            "min_date" => $min_date);
    }

    public function get_products_info() {
        $default_attrs = $this->_get_default_attrs();

        $row_quantity = "p.quantity";
        $join_stock_available = "";
        if(mysql_num_rows(mysql_query("SHOW TABLES LIKE '".$this->sDBPrefix."stock_available'"))==1) {
            $row_quantity = "sa.quantity";
            $join_stock_available = "LEFT JOIN ".$this->sDBPrefix."stock_available AS sa ON p.id_product = sa.id_product AND sa.id_product_attribute = 0";
        }

        $query = "SELECT
					p.id_product,
					pl.name,
					p.price,
					".$row_quantity.",
					p.ean13 AS sku,
					IF(p.active = 1, 'Enabled', 'Disabled') AS active,
					c.iso_code,
					c.sign,
					c.format,
					i.id_image,
					(SELECT SUM(product_quantity) FROM ".$this->sDBPrefix."order_detail WHERE product_id = p.id_product) AS total_ordered
				  FROM ".$this->sDBPrefix."product AS p
				    LEFT JOIN ".$this->sDBPrefix."product_lang AS pl ON pl.id_product = p.id_product
				    LEFT JOIN ".$this->sDBPrefix."image AS i ON i.id_product = p.id_product AND i.position = 1 AND i.cover = 1
				    LEFT JOIN ".$this->sDBPrefix."currency AS c ON c.id_currency = '".$default_attrs['curr_id']."'
				    ".$join_stock_available."
				  WHERE pl.id_lang = '".$default_attrs['lang_id']."' AND p.id_product = '".$this->product_id."'";
        $result = mysql_query($query);
        if(mysql_num_rows($result) == 0) {
            return false;
        }
        $row = mysql_fetch_assoc($result);
        $row['price'] = $this->_price_format($row['sign'], $row['format'], $row['price']);
        if(!$row['total_ordered']) $row['total_ordered'] = 0;
        $id_image = $row['id_image'];
        $id_image_path = str_split($id_image);
        $image_path = "img/p/".implode('/', $id_image_path)."/".$id_image."-home.jpg";
        $image_path_5 = "img/p/".implode('/', $id_image_path)."/".$id_image."-home_default.jpg";
        if(file_exists($image_path) && is_file($image_path)) {
            $row['id_image'] = $this->site_url . $image_path;
        } elseif(file_exists($image_path_5) && is_file($image_path_5)) {
            $row['id_image'] = $this->site_url . $image_path_5;
        }
        return $row;
    }

    public function get_products_descr() {
        $default_attrs = $this->_get_default_attrs();
        $query = "SELECT description AS descr FROM ".$this->sDBPrefix."product_lang WHERE id_product = '".$this->product_id."' AND id_lang = '".$default_attrs['lang_id']."'";
        $row = mysql_fetch_assoc(mysql_query($query));

        return $row;
    }

    private function _price_format($sign, $curr_format, $price, $no_format = false) {
        $prc_format = "";
        if(!$no_format) {
            $price = number_format($price, 2, '.', '');
        }
        $sign = '<span>' . $sign . '</span>';
        if(in_array($curr_format, array(1,3))) {
            $prc_format = $sign . $price;
        } else {
            $prc_format = $price . ' ' . $sign;
        }
        return $prc_format;
    }

    private function _get_default_attrs() {
        $query = "SELECT c.value AS curr_id, l.value AS lang_id, cu.sign, cu.iso_code, cu.format FROM ".$this->sDBPrefix."configuration AS c
				    LEFT JOIN ".$this->sDBPrefix."configuration AS l ON l.name = 'PS_LANG_DEFAULT'
				    LEFT JOIN ".$this->sDBPrefix."currency AS cu ON cu.id_currency = c.value
				  WHERE c.name = 'PS_CURRENCY_DEFAULT'";
        $result = mysql_query($query);
        if(mysql_num_rows($result) == 1) {
            $row = mysql_fetch_assoc($result);
            return $row;
        }
        return array();
    }
}


class MagentoSA extends MainSA {
    public function get_store_title() {
        $result = mysql_query("SELECT value FROM ".$this->sDBPrefix."core_config_data WHERE path = 'design/head/default_title'");
        $row = mysql_fetch_assoc($result);

        return array('test' => 1, 'title' => $row['value']);
    }

    public function get_store_stats() {
        $store_stats = array('count_orders' => "0", 'total_sales' => "0", 'count_customers' => "0", "last_order_id" => "0", "new_orders" => "0");
        /*
        if(!empty($this->date_from)) {
            $query_where_parts[] = " UNIX_TIMESTAMP(o.created_at) >= '".strtotime($this->date_from." 00:00:00")."'";
        }
        if(!empty($this->date_to)) {
            $query_where_parts[] = " UNIX_TIMESTAMP(o.created_at) <= '".strtotime($this->date_to." 23:59:59")."'";
        }
        */
        $today = date("Y-m-d", time());
        $query_where_parts[] = " UNIX_TIMESTAMP(o.created_at) >= '".strtotime($today . " 00:00:00")."'";
        $query_where_parts[] = " UNIX_TIMESTAMP(o.created_at) <= '".strtotime($today . " 23:59:59")."'";

        if(!empty($this->statuses)) {
            $statuses = explode("|", $this->statuses);
            if(!empty($statuses)) {
                $stat = array();
                foreach($statuses as $status) {
                    if($status != "") {
                        $stat[] = $status;
                    }
                }
                $this->statuses = implode("','", $stat);
                $query_where_parts[] = " o.status IN ('".$this->statuses."')";
            }
        }

        $query_currency = "SELECT global_currency_code
				  FROM ".$this->sDBPrefix."sales_flat_order AS o";
        if(!empty($query_where_parts)) {
            $query_currency .= " WHERE " . implode(" AND ", $query_where_parts);
        }

        $result_currency = mysql_query($query_currency);
        if(mysql_num_rows($result_currency) > 0) {
            $row_currency = mysql_fetch_assoc($result_currency);
            $iso_code = $row_currency['global_currency_code'];
        }

        $query = "SELECT COUNT(o.entity_id) AS count_orders, SUM(o.base_grand_total) AS total_sales
				  FROM ".$this->sDBPrefix."sales_flat_order AS o";
        if(!empty($query_where_parts)) {
            $query .= " WHERE " . implode(" AND ", $query_where_parts);
        }

        $result = mysql_query($query);
        if(mysql_num_rows($result) > 0) {
            $row = mysql_fetch_assoc($result);
            $row['total_sales'] = $this->_price_format((floatval($row['total_sales']) > 0 ? $iso_code : $this->_get_default_currency()), 1, $this->bd_nice_number($row['total_sales']), 0, 0);
            $store_stats = array_merge($store_stats, $row);
        }

        if($this->last_order_id != "") {
            $query_max = "SELECT COUNT(o.entity_id) AS count_orders, MAX(o.entity_id) AS last_order_id
                          FROM ".$this->sDBPrefix."sales_flat_order AS o
                          WHERE o.entity_id > ".$this->last_order_id;
            if(!empty($query_where_parts)) {
                $query_max .= " AND " . implode(" AND ", $query_where_parts);
            }
            $result_max = mysql_query($query_max);
            if(mysql_num_rows($result_max) > 0) {
                $row_max = mysql_fetch_assoc($result_max);
                $store_stats['last_order_id'] = intval($this->last_order_id);
                if(intval($row_max['last_order_id']) > intval($this->last_order_id)) {
                    $store_stats['last_order_id'] = intval($row_max['last_order_id']);
                }
                $store_stats['new_orders'] = intval($row_max['count_orders']);
            }
        }

        unset($query_where_parts);
        /*
        if(!empty($this->date_from)) {
            $query_where_parts[] = " UNIX_TIMESTAMP(c.created_at) >= '".strtotime($this->date_from." 00:00:00")."'";
        }
        if(!empty($this->date_to)) {
            $query_where_parts[] = " UNIX_TIMESTAMP(c.created_at) <= '".strtotime($this->date_to." 23:59:59")."'";
        }
        */

        $query_where_parts[] = " UNIX_TIMESTAMP(c.created_at) >= '".strtotime($today . " 00:00:00")."'";
        $query_where_parts[] = " UNIX_TIMESTAMP(c.created_at) <= '".strtotime($today . " 23:59:59")."'";
        $query = "SELECT COUNT(c.entity_id) AS count_customers FROM ".$this->sDBPrefix."customer_entity AS c";
        if (!empty($query_where_parts)) {
            $query .= " WHERE " . implode(" AND ", $query_where_parts);
        }
        $result = mysql_query($query);
        if(mysql_num_rows($result) > 0) {
            $row = mysql_fetch_assoc($result);
            $store_stats = array_merge($store_stats, $row);
        }

        $this->graph_to = $today;
        $this->graph_from = date("Y-m-d", mktime(0, 0, 0, date("m"), date("d")-7, date("Y")));
        $data_graphs = $this->get_data_graphs();
        $store_stats['count_orders'] = $this->bd_nice_number($store_stats['count_orders'], true);
        $store_stats['count_customers'] = $this->bd_nice_number($store_stats['count_customers'], true);

        $result = array_merge($store_stats, array('data_graphs' => $data_graphs));
        return $result;
    }

    public function get_data_graphs() {
        $startDate = strtotime($this->graph_from." 00:00:00");
        $endDate = strtotime($this->graph_to." 23:59:59");

        $date = $startDate;
        $d = 0;
        $average = array('avg_sum_orders' => 0, 'avg_orders' => 0, 'avg_customers' => 0, 'avg_cust_order' => '0.00');
        while ($date <= $endDate) {
            $d++;
            $query = "SELECT UNIX_TIMESTAMP(o.created_at) AS date_add, SUM(o.base_grand_total) AS value, COUNT(o.entity_id) AS tot_orders
                      FROM ".$this->sDBPrefix."sales_flat_order AS o
                      WHERE UNIX_TIMESTAMP(o.created_at) >= '".$date."' AND UNIX_TIMESTAMP(o.created_at) < '".strtotime('+1 day', $date)."'";

            if(!empty($this->statuses)) {
                $statuses = explode("|", $this->statuses);
                if(!empty($statuses)) {
                    $stat = array();
                    foreach($statuses as $status) {
                        if($status != "") {
                            $stat[] = $status;
                        }
                    }
                    $this->statuses = implode("','", $stat);
                    $query .= " AND o.status IN ('".$this->statuses."')";
                }
            }
            $query .= " GROUP BY DATE(o.created_at) ORDER BY o.created_at";

            $result = mysql_query($query);
            if(mysql_num_rows($result) > 0) {
                while($row = mysql_fetch_assoc($result)) {
                    $row['value'] = number_format($row['value'], 2, '.', '');
                    $orders[] = array($row['date_add']*1000, $row['value']);
                    $average['tot_orders'] += $row['tot_orders'];
                    $average['sum_orders'] += $row['value'];
                }
            } else {
                $orders[] = array($date*1000, 0);
            }

            $query = "SELECT COUNT(entity_id) AS tot_customers, UNIX_TIMESTAMP(created_at) AS date_add
                      FROM ".$this->sDBPrefix."customer_entity
                        WHERE UNIX_TIMESTAMP(created_at) >= '".$date."' AND UNIX_TIMESTAMP(created_at) < '".strtotime('+1 day', $date)."'
                           GROUP BY DATE(created_at) ORDER BY created_at";

            $result = mysql_query($query);

            if(mysql_num_rows($result) > 0) {
                while($row = mysql_fetch_assoc($result)) {
                    $customers[] = array($row['date_add']*1000, $row['tot_customers']);
                    $average['tot_customers'] += $row['tot_customers'];
                }
            } else {
                $customers[] = array($date*1000, 0);
            }
            $date = strtotime('+1 day', $date);
        }
        $sum = '0';
        $default_currency_sign = $this->_price_format($this->_get_default_currency(), 1, $sum, true);

        $average['avg_sum_orders'] = number_format($average['sum_orders']/$d, 2, '.', '');
        $average['avg_orders'] = number_format($average['tot_orders']/$d, 1, '.', '');
        $average['avg_customers'] = number_format($average['tot_customers']/$d, 1, '.', '');

        if($average['tot_customers'] > 0) {
            $average['avg_cust_order'] = number_format($average['sum_orders']/$average['tot_customers'], 1, '.', '');
        }
        $average['sum_orders'] = number_format($average['sum_orders'], 2, '.', '');
        $average['tot_customers'] = number_format($average['tot_customers'], 1, '.', '');
        $average['tot_orders'] = number_format($average['tot_orders'], 1, '.', '');

        return array('orders' => $orders, 'customers' => $customers, 'currency_sign' => $default_currency_sign, 'average' => $average);
    }

    public function get_orders() {
        $orders = array();
        if(!empty($this->search_order_id)) {
            $query_where_parts[] = " (entity_id = '".$this->search_order_id."' OR increment_id = '".$this->search_order_id."')";
        }
        if(!empty($this->orders_from)) {
            $query_where_parts[] = " UNIX_TIMESTAMP(created_at) >= '".strtotime($this->orders_from." 00:00:00")."'";
        }
        if(!empty($this->orders_to)) {
            $query_where_parts[] = " UNIX_TIMESTAMP(created_at) <= '".strtotime($this->orders_to." 23:59:59")."'";
        }
        if(!empty($this->statuses)) {
            $statuses = explode("|", $this->statuses);
            if(!empty($statuses)) {
                $stat = array();
                foreach($statuses as $status) {
                    if($status != "") {
                        $stat[] = $status;
                    }
                }
                $parse_statuses = implode("','", $stat);
                $query_where_parts[] = " status IN ('".$parse_statuses."')";
            }
        }
        $query = "SELECT
					entity_id AS id_order,
					increment_id AS order_number,
					customer_id AS id_customer,
					status AS ord_status,
					total_qty_ordered AS qty_ordered,
					base_grand_total AS total_paid,
					customer_firstname AS firstname,
					customer_lastname AS lastname,
					global_currency_code AS iso_code,
					created_at AS date_add,
					total_item_count AS count_prods
				  FROM ".$this->sDBPrefix."sales_flat_order";
        $query_page = "SELECT COUNT(entity_id) AS count_ords, MAX(created_at) AS max_date, MIN(created_at) AS min_date, SUM(base_grand_total) AS orders_total
						FROM ".$this->sDBPrefix."sales_flat_order";
        if (!empty($query_where_parts)) {
            $query .= " WHERE " . implode(" AND ", $query_where_parts);
            $query_page .= " WHERE " . implode(" AND ", $query_where_parts);
        }
        $result_page = mysql_query($query_page);
        $row_page = mysql_fetch_assoc($result_page);
        $query .= " ORDER BY entity_id DESC LIMIT ".($this->page - 1)*$this->show." , ".$this->show;
        $result = mysql_query($query);
        while($row = mysql_fetch_assoc($result)) {
            //$date = explode(' ', $row['date_add']);
            //$row['date_add'] = $date[0];
            $this->_price_format($row['iso_code'], 1, $row['total_paid']);
            $row['customer'] = $row['firstname'] . ' ' . $row['lastname'];
            $orders[] = $row;
        }
        if($row_page['count_ords'] > 0) {
            $max_date = date("n/j/Y", strtotime($row_page['max_date']));
            $min_date = date("n/j/Y", strtotime($row_page['min_date']));
        }

        $orders_status = null;
        if(isset($this->get_statuses) && $this->get_statuses == 1) {
            $orders_status = $this->get_orders_statuses();
        }

        $this->_price_format($this->_get_default_currency(), 1, $row_page['orders_total'], false);

        return array("orders" => $orders,
            "orders_count" => intval($row_page['count_ords']),
            "orders_total" => $row_page['orders_total'],
            "max_date" => $max_date,
            "min_date" => $min_date,
            "orders_status" => $orders_status);
    }

    public function get_orders_statuses() {
        $orders_status = array();
        $query = "SELECT status AS st_id, label AS st_name FROM ".$this->sDBPrefix."sales_order_status";
        $result_status = mysql_query($query);
        while($row = mysql_fetch_assoc($result_status)) {
            $orders_status[] = $row;
        }
        return $orders_status;
    }

    public function get_orders_info() {
        $order_products = array();
        $order_info = array();
        $query = "SELECT
					o.entity_id AS id_order,
					o.status,
					o.base_grand_total AS total_paid,
					CONCAT(o.customer_firstname, ' ', o.customer_lastname) AS customer,
					o.global_currency_code AS iso_code,
					o.created_at AS date_add,
					o.customer_email AS email,
					o.customer_id AS id_customer,
					o.base_subtotal AS subtotal,
                    o.base_shipping_amount AS sh_amount,
                    o.base_tax_amount AS tax_amount,
                    o.discount_amount AS d_amount,
                    o.base_grand_total AS g_total,
                    o.base_total_paid AS t_paid,
                    o.base_total_refunded AS t_refunded,
                    o.base_total_due AS t_due,
                    CONCAT(oa_s.firstname, ' ', oa_s.lastname) AS s_name,
                    oa_s.company AS s_company,
                    oa_s.street AS s_street,
                    oa_s.city AS s_city,
                    oa_s.region AS s_region,
                    oa_s.postcode AS s_postcode,
                    oa_s.country_id AS s_country_id,
                    oa_s.telephone AS s_telephone,
                    oa_s.fax AS s_fax,
                    CONCAT(oa_b.firstname, ' ', oa_b.lastname) AS b_name,
                    oa_b.company AS b_company,
                    oa_b.street AS b_street,
                    oa_b.city AS b_city,
                    oa_b.region AS b_region,
                    oa_b.postcode AS b_postcode,
                    oa_b.country_id AS b_country_id,
                    oa_b.telephone AS b_telephone,
                    oa_b.fax AS b_fax
				  FROM ".$this->sDBPrefix."sales_flat_order AS o
				  LEFT JOIN ".$this->sDBPrefix."sales_flat_order_address AS oa_s ON oa_s.entity_id = o.shipping_address_id
				  LEFT JOIN ".$this->sDBPrefix."sales_flat_order_address AS oa_b ON oa_b.entity_id = o.billing_address_id
				  WHERE o.entity_id = '".$this->order_id."'";

        $result = mysql_query($query);
        $order_info = mysql_fetch_assoc($result);
        //$date = explode(' ', $order_info['date_add']);
        //$order_info['date_add'] = $date[0];
        $iso_code = $order_info['iso_code'];
        $elements = array('total_paid', 'subtotal', 'sh_amount', 'tax_amount', 'd_amount', 'g_total', 't_paid', 't_refunded', 't_due');
        foreach($elements as $element) {
            $this->_price_format($iso_code, 1, $order_info[$element]);
        }

        $locales = simplexml_load_file("lib/Zend/Locale/Data/en.xml");
        $s_country_name = $locales->xpath("//localeDisplayNames/territories/territory[@type='".$order_info['s_country_id']."']");
        $b_country_name = $locales->xpath("//localeDisplayNames/territories/territory[@type='".$order_info['b_country_id']."']");
        $order_info['s_country_id']= (string)$s_country_name[0];
        $order_info['b_country_id'] = (string)$b_country_name[0];

        $query = "SELECT
					sfoi.order_id AS id_order,
					sfoi.product_id,
					sfoi.name AS product_name,
					sfoi.qty_ordered AS product_quantity,
					sfoi.base_row_total_incl_tax AS product_price,
					sfoi.sku AS sku,
					sfo.global_currency_code AS iso_code
 				 FROM ".$this->sDBPrefix."sales_flat_order_item AS sfoi
					LEFT JOIN ".$this->sDBPrefix."sales_flat_order AS sfo ON sfo.entity_id = sfoi.order_id
				 WHERE sfoi.order_id = '".$this->order_id."'
					AND ((sfoi.parent_item_id IS NULL)
					OR (sfoi.parent_item_id = 0))";
        $query_page = "SELECT COUNT(item_id) AS count_prods
					   FROM ".$this->sDBPrefix."sales_flat_order_item
					   WHERE order_id = '".$this->order_id."' AND ((parent_item_id IS NULL) OR (parent_item_id = 0))";
        $result_page = mysql_query($query_page);
        $row_page = mysql_fetch_assoc($result_page);
        $query .= " LIMIT ".($this->page - 1)*$this->show." , ".$this->show;
        $result = mysql_query($query);
        while($row = mysql_fetch_assoc($result)) {
            $row['product_price'] = $this->_price_format($order_info['iso_code'], 1, $row['product_price']);
            $row['product_quantity'] = intval($row['product_quantity']);
            $row['product_name'] = utf8_encode($row['product_name']);
            $order_products[] = $row;
        }
        $order_full_info = array("order_info" => $order_info, "order_products" => $order_products, "o_products_count" => $row_page['count_prods']);
        return $order_full_info;
    }

    public function get_customers() {
        $customers = array();
        if(!empty($this->customers_from)) {
            $query_where_parts[] = " UNIX_TIMESTAMP(c.created_at) >= '".strtotime($this->customers_from." 00:00:00")."'";
        }
        if(!empty($this->customers_to)) {
            $query_where_parts[] = " UNIX_TIMESTAMP(c.created_at) <= '".strtotime($this->customers_to." 23:59:59")."'";
        }
        if(!empty($this->search_val)) {
            $query_where_parts[] = " (c.email LIKE '%".$this->search_val."%' OR f.value LIKE '%".$this->search_val."%' OR m.value LIKE '%".$this->search_val."%' OR l.value LIKE '%".$this->search_val."%')";
        }
        if(!empty($this->cust_with_orders)) {
            $query_where_parts[] = " tot.total_orders > 0";
        }

        $cust_attr_ids = $this->_get_customers_attr();
        $query = "SELECT
					c.entity_id AS id_customer,
					f.value AS firstname,
					m.value AS middlename,
					l.value AS lastname,
					c.created_at AS date_add,
					c.email,
					IFNULL(tot.total_orders, 0) AS total_orders
				  FROM ".$this->sDBPrefix."customer_entity AS c
                    LEFT OUTER JOIN (SELECT COUNT(entity_id) AS total_orders, customer_id FROM ".$this->sDBPrefix."sales_flat_order GROUP BY customer_id) AS tot ON tot.customer_id = c.entity_id
    			    LEFT JOIN ".$this->sDBPrefix."customer_entity_varchar AS f ON f.entity_id = c.entity_id AND f.attribute_id = '".$cust_attr_ids['firstname']."'
				    LEFT JOIN ".$this->sDBPrefix."customer_entity_varchar AS m ON m.entity_id = c.entity_id AND m.attribute_id = '".$cust_attr_ids['middlename']."'
				    LEFT JOIN ".$this->sDBPrefix."customer_entity_varchar AS l ON l.entity_id = c.entity_id AND l.attribute_id = '".$cust_attr_ids['lastname']."'";

        $query_page = "SELECT COUNT(c.entity_id) AS count_custs, MAX(c.created_at) AS max_date, MIN(c.created_at) AS min_date FROM  ".$this->sDBPrefix."customer_entity AS c
                        LEFT OUTER JOIN (SELECT COUNT(entity_id) AS total_orders, customer_id FROM ".$this->sDBPrefix."sales_flat_order GROUP BY customer_id) AS tot ON tot.customer_id = c.entity_id
                        LEFT JOIN ".$this->sDBPrefix."customer_entity_varchar AS f ON f.entity_id = c.entity_id AND f.attribute_id = '".$cust_attr_ids['firstname']."'
				        LEFT JOIN ".$this->sDBPrefix."customer_entity_varchar AS m ON m.entity_id = c.entity_id AND m.attribute_id = '".$cust_attr_ids['middlename']."'
				        LEFT JOIN ".$this->sDBPrefix."customer_entity_varchar AS l ON l.entity_id = c.entity_id AND l.attribute_id = '".$cust_attr_ids['lastname']."'";
        if (!empty($query_where_parts)) {
            $query .= " WHERE " . implode(" AND ", $query_where_parts);
            $query_page .= " WHERE " . implode(" AND ", $query_where_parts);
        }
        $query .= " LIMIT ".($this->page - 1)*$this->show." , ".$this->show;
        $result_page = mysql_query($query_page);
        $row_page = mysql_fetch_assoc($result_page);
        $result = mysql_query($query);
        while($row = mysql_fetch_assoc($result)) {
            $date = explode(' ', $row['date_add']);
            $row['date_add'] = $date[0];
            $row['total_orders'] = intval($row['total_orders']);
            $customers[] = $row;
        }
        if($row_page['count_custs'] > 0) {
            $max_date = date("n/j/Y", strtotime($row_page['max_date']));
            $min_date = date("n/j/Y", strtotime($row_page['min_date']));
        }
        return array("customers_count" => $row_page['count_custs'],
            "customers" => $customers,
            "max_date" => $max_date,
            "min_date" => $min_date);
    }

    public function get_customers_info() {
        $customer_orders = array();
        $cust_attr_ids = $this->_get_customers_attr($this->user_id);

        $query = "SELECT
					c.entity_id,
					c.email,
					c.created_at AS date_add,
					f.value AS firstname,
					m.value AS middlename,
					l.value AS lastname,
					postcode.value AS postcode,
					city.value AS city,
					phone.value AS phone,
					region.value AS region,
					country.value AS country_code,
					street.value AS street
				  FROM ".$this->sDBPrefix."customer_entity AS c
				  LEFT JOIN ".$this->sDBPrefix."customer_address_entity AS cae ON cae.parent_id = c.entity_id " . ($cust_attr_ids['default_billing'] ? "AND cae.entity_id = '".$cust_attr_ids['default_billing']."' " : "") . "
					LEFT JOIN ".$this->sDBPrefix."customer_entity_varchar AS f ON c.entity_id = f.entity_id AND f.attribute_id = '".$cust_attr_ids['firstname']."'
					LEFT JOIN ".$this->sDBPrefix."customer_entity_varchar AS l ON c.entity_id = l.entity_id AND l.attribute_id = '".$cust_attr_ids['lastname']."'
					LEFT JOIN ".$this->sDBPrefix."customer_entity_varchar AS m ON c.entity_id = m.entity_id AND m.attribute_id = '".$cust_attr_ids['middlename']."'
					LEFT JOIN ".$this->sDBPrefix."customer_address_entity_varchar AS postcode ON cae.entity_id = postcode.entity_id AND postcode.attribute_id = '".$cust_attr_ids['postcode']."'
					LEFT JOIN ".$this->sDBPrefix."customer_address_entity_varchar AS city ON cae.entity_id = city.entity_id AND city.attribute_id = '".$cust_attr_ids['city']."'
					LEFT JOIN ".$this->sDBPrefix."customer_address_entity_varchar AS phone ON cae.entity_id = phone.entity_id AND phone.attribute_id = '".$cust_attr_ids['telephone']."'
					LEFT JOIN ".$this->sDBPrefix."customer_address_entity_varchar AS region ON cae.entity_id = region.entity_id AND region.attribute_id = '".$cust_attr_ids['region']."'
					LEFT JOIN ".$this->sDBPrefix."customer_address_entity_varchar AS country ON cae.entity_id = country.entity_id AND country.attribute_id = '".$cust_attr_ids['country_id']."'
					LEFT JOIN ".$this->sDBPrefix."customer_address_entity_text AS street ON cae.entity_id = street.entity_id AND street.attribute_id = '".$cust_attr_ids['street']."'
				  WHERE c.entity_id = '".$this->user_id."'";

        $result = mysql_query($query);
        if(mysql_num_rows($result) > 0) {
            $user_info = mysql_fetch_assoc($result);
            $locales = simplexml_load_file("lib/Zend/Locale/Data/en.xml");
            $country_name = $locales->xpath("//localeDisplayNames/territories/territory[@type='".$user_info['country_code']."']");
            $user_info['country_name'] = (string)$country_name[0];

            $user_info['address'] = $this->split_values($user_info, array('street', 'city', 'region', 'postcode', 'country_name'));
            unset($user_info['country_name']);

            $query = "SELECT
                        entity_id AS id_order,
                        status AS ord_status,
                        total_item_count AS pr_qty,
                        base_grand_total AS total_paid,
                        global_currency_code AS iso_code,
                        created_at AS date_add
                      FROM ".$this->sDBPrefix."sales_flat_order
                      WHERE customer_id = '".$this->user_id."'";
            $query_page = "SELECT COUNT(entity_id) AS count_ords, SUM(base_grand_total) AS sum_ords FROM ".$this->sDBPrefix."sales_flat_order WHERE customer_id = '".$this->user_id."'";
            $result_page = mysql_query($query_page);
            $row_page = mysql_fetch_assoc($result_page);
            $query .= " LIMIT ".($this->page - 1)*$this->show." , ".$this->show;
            $result = mysql_query($query);
            if(mysql_num_rows($result) > 0) {
                while($row = mysql_fetch_assoc($result)) {
                    $row['total_paid'] = $this->_price_format($row['iso_code'], 1, $row['total_paid']);
                    $row['pr_qty'] = intval($row['pr_qty']);
                    $customer_orders[] = $row;
                }
                $this->_price_format($this->_get_default_currency(), 1, $row_page['sum_ords'], false);
            }
        }
        $customer_info = array("user_info" => $user_info, "customer_orders" => $customer_orders, "c_orders_count" => intval($row_page['count_ords']), "sum_ords" => $row_page['sum_ords']);
        return $customer_info;
    }

    public function search_products() {
        $products = array();
        $query_where_parts = array();
        $prods_attr_ids = $this->_get_products_attr();
        $this->params = explode("|", $this->params);
        foreach($this->params as $param) {
            switch ($param) {
                case 'pr_id':
                    $query_where_parts[] = " cpe.entity_id = '".$this->val."'";
                    break;
                case 'pr_sku':
                    $query_where_parts[] = " cpe.sku = '".$this->val."'";
                    break;
                case 'pr_name':
                    $query_where_parts[] = " name.value LIKE '%".$this->val."%'";
                    $query_where_parts[] = " df_name.value LIKE '%".$this->val."%'";
                    break;
                case 'pr_desc':
                    $query_where_parts[] = " descr.value LIKE '%".$this->val."%'";
                    break;
                case 'pr_short_desc':
                    $query_where_parts[] = " short_desc.value LIKE '%".$this->val."%'";
                    $query_where_parts[] = " df_short_desc.value LIKE '%".$this->val."%'";
                    break;
            }
        }
        $query = "SELECT cpe.entity_id AS main_id, IFNULL(name.value, df_name.value) AS name, price.value AS price, sp_price.value AS spec_price, qty.qty AS quantity, cpe.sku
				  FROM ".$this->sDBPrefix."catalog_product_entity AS cpe
					  LEFT JOIN ".$this->sDBPrefix."catalog_product_entity_varchar AS name ON name.entity_id = cpe.entity_id AND name.attribute_id = '".$prods_attr_ids['name']."' AND name.store_id = '".$prods_attr_ids['store_id']."'
					  LEFT JOIN ".$this->sDBPrefix."catalog_product_entity_varchar AS df_name ON df_name.entity_id = cpe.entity_id AND df_name.attribute_id = '".$prods_attr_ids['name']."' AND df_name.store_id = 0
					  LEFT JOIN ".$this->sDBPrefix."catalog_product_entity_decimal AS price ON price.entity_id = cpe.entity_id AND price.attribute_id = '".$prods_attr_ids['price']."'
					  LEFT JOIN ".$this->sDBPrefix."catalog_product_entity_decimal AS sp_price ON sp_price.entity_id = cpe.entity_id AND sp_price.attribute_id = '".$prods_attr_ids['special_price']."'
					  LEFT JOIN ".$this->sDBPrefix."cataloginventory_stock_item AS qty ON qty.product_id = cpe.entity_id
					  LEFT JOIN ".$this->sDBPrefix."catalog_product_entity_varchar AS descr ON descr.entity_id = cpe.entity_id AND descr.attribute_id = '".$prods_attr_ids['description']."'
					  LEFT JOIN ".$this->sDBPrefix."catalog_product_entity_text AS short_desc ON short_desc.entity_id = cpe.entity_id AND short_desc.attribute_id = '".$prods_attr_ids['short_description']."' AND short_desc.store_id = '".$prods_attr_ids['store_id']."'
					  LEFT JOIN ".$this->sDBPrefix."catalog_product_entity_text AS df_short_desc ON df_short_desc.entity_id = cpe.entity_id AND df_short_desc.attribute_id = '".$prods_attr_ids['short_description']."' AND df_short_desc.store_id = 0
				  WHERE IF (cpe.type_id = 'configurable' OR cpe.type_id = 'bundle', cpe.has_options = 1, '1=1')
				 ";
        $query_page = "SELECT COUNT(cpe.entity_id) AS count_prods
					   FROM ".$this->sDBPrefix."catalog_product_entity AS cpe
							LEFT JOIN ".$this->sDBPrefix."catalog_product_entity_varchar AS name ON name.entity_id = cpe.entity_id AND name.attribute_id = '".$prods_attr_ids['name']."' AND name.store_id = '".$prods_attr_ids['store_id']."'
							LEFT JOIN ".$this->sDBPrefix."catalog_product_entity_varchar AS df_name ON df_name.entity_id = cpe.entity_id AND df_name.attribute_id = '".$prods_attr_ids['name']."' AND df_name.store_id = 0
							LEFT JOIN ".$this->sDBPrefix."catalog_product_entity_decimal AS price ON price.entity_id = cpe.entity_id AND price.attribute_id = '".$prods_attr_ids['price']."'
							LEFT JOIN ".$this->sDBPrefix."cataloginventory_stock_item AS qty ON qty.product_id = cpe.entity_id
							LEFT JOIN ".$this->sDBPrefix."catalog_product_entity_varchar AS descr ON descr.entity_id = cpe.entity_id AND descr.attribute_id = '".$prods_attr_ids['description']."'
							LEFT JOIN ".$this->sDBPrefix."catalog_product_entity_text AS short_desc ON short_desc.entity_id = cpe.entity_id AND short_desc.attribute_id = '".$prods_attr_ids['short_description']."' AND short_desc.store_id = '".$prods_attr_ids['store_id']."'
							LEFT JOIN ".$this->sDBPrefix."catalog_product_entity_text AS df_short_desc ON df_short_desc.entity_id = cpe.entity_id AND df_short_desc.attribute_id = '".$prods_attr_ids['short_description']."' AND df_short_desc.store_id = 0
					  WHERE IF (cpe.type_id = 'configurable' OR cpe.type_id = 'bundle', cpe.has_options = 1, '1=1')
					";
        if (!empty($query_where_parts)) {
            $query .= " AND ( " . implode(" OR ", $query_where_parts) . " )";
            $query_page .= " AND ( " . implode(" OR ", $query_where_parts) . " )";
        }
        $query .= " LIMIT ".($this->page - 1)*$this->show." , ". $this->show;
        $result_page = mysql_query($query_page);
        $row_page = mysql_fetch_assoc($result_page);
        $result = mysql_query($query);
        while($row = mysql_fetch_assoc($result)) {
            $row['price'] = $this->_price_format($this->_get_default_currency(), 1, $row['price']);
            if($row['spec_price'] > 0 && $row['spec_price'] != '') {
                $row['spec_price'] = $this->_price_format($this->_get_default_currency(), 1, $row['spec_price']);
            } else {
                unset($row['spec_price']);
            }
            $row['quantity'] = intval($row['quantity']);
            $row['name'] = utf8_encode($row['name']);
            $products[] = $row;
        }
        return array("products_count" => $row_page['count_prods'], "products" => $products);
    }

    public function search_products_ordered() {
        $query_where_parts = array();
        $this->params = explode("|", $this->params);
        foreach($this->params as $param) {
            switch ($param) {
                case 'pr_id':
                    $query_where_parts[] = " sfoi.product_id = '".$this->val."'";
                    break;
                case 'pr_sku':
                    $query_where_parts[] = " sfoi.sku  = '".$this->val."'";
                    break;
                case 'pr_name':
                    $query_where_parts[] = " sfoi.name LIKE '%".$this->val."%'";
                    break;
            }
        }
        if(!empty($this->products_from)) {
            $query_where_parts[] = " UNIX_TIMESTAMP(sfo.created_at) >= '".strtotime($this->products_from." 00:00:00")."'";
        }
        if(!empty($this->products_to)) {
            $query_where_parts[] = " UNIX_TIMESTAMP(sfo.created_at) <= '".strtotime($this->products_to." 23:59:59")."'";
        }

        $query = "SELECT
					sfoi.order_id AS main_id,
					sfoi.product_id,
					sfoi.name AS name,
					sfoi.qty_ordered AS quantity,
					sfoi.base_row_total_incl_tax AS price,
					(sfoi.original_price*sfoi.qty_ordered) AS orig_price,
					sfoi.sku AS sku,
					sfo.global_currency_code AS iso_code,
					sfo.created_at,
					sfo.status
 				 FROM ".$this->sDBPrefix."sales_flat_order_item AS sfoi
					LEFT JOIN ".$this->sDBPrefix."sales_flat_order AS sfo ON sfo.entity_id = sfoi.order_id";

        $query_page = "SELECT COUNT(sfoi.order_id) AS count_prods, MAX(sfo.created_at) AS max_date, MIN(sfo.created_at) AS min_date
                       FROM ".$this->sDBPrefix."sales_flat_order_item AS sfoi
                         LEFT JOIN ".$this->sDBPrefix."sales_flat_order AS sfo ON sfo.entity_id = sfoi.order_id";

        if (!empty($query_where_parts)) {
            $query .= " WHERE " . implode(" AND ", $query_where_parts);
            $query_page .= " WHERE " . implode(" AND ", $query_where_parts);
        }

        $result_page = mysql_query($query_page);
        $row_page = mysql_fetch_assoc($result_page);
        $query .= " LIMIT ".($this->page - 1)*$this->show." , ".$this->show;
        $result = mysql_query($query);

        while($row = mysql_fetch_assoc($result)) {
            $row['price'] = $this->_price_format($row['iso_code'], 1, $row['price']);
            if($row['orig_price'] > 0) {
                $row['orig_price'] = $this->_price_format($row['iso_code'], 1, $row['orig_price']);
            } else {
                unset($row['orig_price']);
            }
            $row['quantity'] = intval($row['quantity']);
            $row['name'] = utf8_encode($row['name']);
            $order_products[] = $row;
        }

        if($row_page['count_prods'] > 0) {
            $max_date = date("n/j/Y", strtotime($row_page['max_date']));
            $min_date = date("n/j/Y", strtotime($row_page['min_date']));
        }

        return array("products_count" => $row_page['count_prods'],
            "products" => $order_products,
            "max_date" => $max_date,
            "min_date" => $min_date);
    }

    public function get_products_info() {
        $prods_attr_ids = $this->_get_products_attr();
        $row = false;
        $query = "SELECT cpe.entity_id AS id_product, name.value AS name, price.value AS price, sp_price.value AS spec_price, qty.qty AS quantity, cpe.sku,
					IF(status.value = 1, 'Enabled', 'Disabled') AS active,
					(SELECT SUM(qty_ordered) FROM ".$this->sDBPrefix."sales_flat_order_item WHERE product_id = cpe.entity_id) AS total_ordered,
					(SELECT value FROM ".$this->sDBPrefix."catalog_product_entity_media_gallery WHERE entity_id = cpe.entity_id ORDER BY value_id LIMIT 1) AS image
				  FROM ".$this->sDBPrefix."catalog_product_entity AS cpe
					LEFT JOIN ".$this->sDBPrefix."catalog_product_entity_varchar AS name ON name.entity_id = cpe.entity_id AND name.attribute_id = '".$prods_attr_ids['name']."'
					LEFT JOIN ".$this->sDBPrefix."catalog_product_entity_decimal AS price ON price.entity_id = cpe.entity_id AND price.attribute_id = '".$prods_attr_ids['price']."'
					LEFT JOIN ".$this->sDBPrefix."catalog_product_entity_decimal AS sp_price ON sp_price.entity_id = cpe.entity_id AND sp_price.attribute_id = '".$prods_attr_ids['special_price']."'
					LEFT JOIN ".$this->sDBPrefix."cataloginventory_stock_item AS qty ON qty.product_id = cpe.entity_id
					LEFT JOIN ".$this->sDBPrefix."catalog_product_entity_varchar AS descr ON descr.entity_id = cpe.entity_id AND descr.attribute_id = '".$prods_attr_ids['description']."'
					LEFT JOIN ".$this->sDBPrefix."catalog_product_entity_text AS short_desc ON short_desc.entity_id = cpe.entity_id AND short_desc.attribute_id = '".$prods_attr_ids['short_description']."'
					LEFT JOIN ".$this->sDBPrefix."catalog_product_entity_int AS status ON status.entity_id = cpe.entity_id AND status.attribute_id = '".$prods_attr_ids['status']."'
				  WHERE cpe.entity_id = '".$this->product_id."'";
        $result = mysql_query($query);
        if(mysql_num_rows($result) > 0) {
            $row = mysql_fetch_assoc($result);
            $row['price'] = $this->_price_format($this->_get_default_currency(), 1, $row['price']);
            if($row['spec_price'] > 0 && $row['spec_price'] != '') {
                $row['spec_price'] = $this->_price_format($this->_get_default_currency(), 1, $row['spec_price']);
            } else {
                unset($row['spec_price']);
            }
            $row['quantity'] = intval($row['quantity']);
            $row['name'] = utf8_encode($row['name']);
            $row['total_ordered'] = intval($row['total_ordered']);
            $id_image_path = "/media/catalog/product".$row['image'];
            if(file_exists(realpath(dirname(__FILE__).$id_image_path)) && is_file(realpath(dirname(__FILE__).$id_image_path))) {
                $row['id_image'] = $this->site_url . $id_image_path;
            }
        }
        return $row;
    }

    public function get_products_descr() {
        $prods_attr_ids = $this->_get_products_attr();
        $query = "SELECT IFNULL(descr.value, df_descr.value) AS descr
				  FROM ".$this->sDBPrefix."catalog_product_entity AS cpe
					LEFT JOIN ".$this->sDBPrefix."catalog_product_entity_varchar AS descr ON descr.entity_id = cpe.entity_id AND descr.attribute_id = '".$prods_attr_ids['description']."'
					LEFT JOIN ".$this->sDBPrefix."catalog_product_entity_text AS df_descr ON df_descr.entity_id = cpe.entity_id AND df_descr.attribute_id = '".$prods_attr_ids['description']."' AND df_descr.store_id = 0
				  WHERE cpe.entity_id = '".$this->product_id."'";
        $row = mysql_fetch_assoc(mysql_query($query));

        return $row;
    }

    private function _get_default_currency() {
        $result = mysql_query("SELECT value FROM ".$this->sDBPrefix."core_config_data WHERE path = 'currency/options/base'");
        if(mysql_num_rows($result) == 1) {
            $row = mysql_fetch_assoc($result);
            return $row['value'];
        }

        $locales = simplexml_load_file("app/code/core/Mage/Directory/etc/config.xml");
        $currency_sign = $locales->xpath("//default/currency/options/base");

        return (string)$currency_sign[0];
    }

    private function _price_format($iso_code, $curr_format, &$price, $force = false, $format = true) {
        if($format) {
            $price = number_format($price, 2, '.', '');
        }
        if($iso_code == 'USD') {
            $currency_sign = '$';
        } elseif($iso_code == 'EUR') {
            $currency_sign = '€';
        } else {
            $locales = simplexml_load_file("lib/Zend/Locale/Data/root.xml");
            $currency_sign = $locales->xpath("//numbers/currencies/currency[@type='".$iso_code."']/symbol");
            $currency_sign = (string)$currency_sign[0];
        }
        if($force) {
            return $currency_sign;
        }
        $sign = '<span>' . $currency_sign . '</span>';
        if($curr_format == 1) {
            $price = $sign . $price;
        } else {
            $price = $price . ' ' . $sign;
        }
        return $price;
    }

    private function _get_customers_attr($user_id = false) {
        $customers_attrs = array('default_billing' => false, 'default_shipping' => false);

        $result = mysql_query("SELECT attribute_code, attribute_id FROM ".$this->sDBPrefix."eav_attribute
							   WHERE
								(attribute_code IN ('firstname', 'lastname', 'middlename')
									AND entity_type_id = (SELECT entity_type_id FROM ".$this->sDBPrefix."eav_entity_type WHERE entity_type_code = 'customer'))
								OR (attribute_code IN ('city', 'street', 'country_id', 'telephone', 'region', 'postcode')
									AND entity_type_id = (SELECT entity_type_id FROM ".$this->sDBPrefix."eav_entity_type WHERE entity_type_code = 'customer_address'))");
        while($row = mysql_fetch_assoc($result)) {
            $customers_attrs[$row['attribute_code']] = $row['attribute_id'];
        }

        if(intval($user_id)) {
            $result = mysql_query("SELECT value FROM ".$this->sDBPrefix."customer_entity_int
							   WHERE entity_id = '".intval($user_id)."' AND attribute_id = (SELECT attribute_id FROM ".$this->sDBPrefix."eav_attribute WHERE attribute_code = 'default_billing')");
            if(mysql_num_rows($result) > 0) {
                $row = mysql_fetch_assoc($result);
                $customers_attrs['default_billing'] = $row['value'];
            }

            $result = mysql_query("SELECT value FROM ".$this->sDBPrefix."customer_entity_int
							   WHERE entity_id = '".intval($user_id)."' AND attribute_id = (SELECT attribute_id FROM ".$this->sDBPrefix."eav_attribute WHERE attribute_code = 'default_shipping')");
            if(mysql_num_rows($result) > 0) {
                $row = mysql_fetch_assoc($result);
                $customers_attrs['default_shipping'] = $row['value'];
            }
        }

        return $customers_attrs;
    }

    private function _get_products_attr() {
        $products_attrs = array();
        $query = "SELECT attribute_code, attribute_id FROM ".$this->sDBPrefix."eav_attribute
				  WHERE
					attribute_code IN ('name', 'price', 'special_price', 'description', 'short_description', 'status')
					AND entity_type_id = (SELECT entity_type_id FROM ".$this->sDBPrefix."eav_entity_type WHERE entity_type_code = 'catalog_product')
				  UNION
					SELECT 'store_id' AS attribute_code, default_store_id AS attribute_id FROM ".$this->sDBPrefix."core_store_group WHERE group_id = (SELECT default_group_id FROM ".$this->sDBPrefix."core_website WHERE `is_default` = 1)";
        $result = mysql_query($query);
        while($row = mysql_fetch_assoc($result)) {
            $products_attrs[$row['attribute_code']] = $row['attribute_id'];
        }
        return $products_attrs;
    }
}


class VirtueMartv2xSA extends MainSA {
    public function get_store_title() {
        $title = '';
        $active_language = $this->_get_active_languages();
        $query = "SELECT vendor_store_name FROM  ".$this->sDBCartPrefix."vendors_".$active_language;
        if($result = mysql_query($query)) {
            $row = mysql_fetch_assoc($result);
            $title = $row['vendor_store_name'];
        }
        return array('test' => 1, 'title' => $title);
    }

    public function get_store_stats() {
        $store_stats = array('count_orders' => "0", 'total_sales' => "0", 'count_customers' => "0", "last_order_id" => "0", "new_orders" => "0");
        /*
        if(!empty($this->date_from)) {
            $query_where_parts[] = " UNIX_TIMESTAMP(created_on) >= '".strtotime($this->date_from." 00:00:00")."'";
        }
        if(!empty($this->date_to)) {
            $query_where_parts[] = " UNIX_TIMESTAMP(created_on) <= '".strtotime($this->date_to." 23:59:59")."'";
        }
        */
        $today = date("Y-m-d", time());
        $query_where_parts[] = " UNIX_TIMESTAMP(created_on) >= '".strtotime($today . " 00:00:00")."'";
        $query_where_parts[] = " UNIX_TIMESTAMP(created_on) <= '".strtotime($today . " 23:59:59")."'";
        if(!empty($this->statuses)) {
            $statuses = explode("|", $this->statuses);
            if(!empty($statuses)) {
                $stat = array();
                foreach($statuses as $status) {
                    if($status != "") {
                        $stat[] = $status;
                    }
                }
                $parse_statuses = implode("','", $stat);
                $query_where_parts[] = " order_status IN ('".$parse_statuses."')";
            }
        }

        $query = "SELECT COUNT(virtuemart_order_id) AS count_orders, SUM(order_total) AS total_sales, user_currency_id, order_currency
				  FROM ".$this->sDBCartPrefix."orders";

        if(!empty($query_where_parts)) {
            $query .= " WHERE " . implode(" AND ", $query_where_parts);
        }

        $result = mysql_query($query);
        if(mysql_num_rows($result) > 0) {
            $row = mysql_fetch_assoc($result);
            $convert_price = $this->_currency_convert($row['total_sales'], '', $row['order_currency']);
            $store_stats['count_orders'] = $row['count_orders'];
            $store_stats['total_sales'] = $this->_price_format($this->bd_nice_number($convert_price), 0, true);
        }

        if($this->last_order_id != "") {
            $query_max = "SELECT COUNT(virtuemart_order_id) AS count_orders, MAX(virtuemart_order_id) AS last_order_id
                          FROM ".$this->sDBCartPrefix."orders
                          WHERE virtuemart_order_id > ".$this->last_order_id;
            if(!empty($query_where_parts)) {
                $query_max .= " AND " . implode(" AND ", $query_where_parts);
            }
            $result_max = mysql_query($query_max);
            if(mysql_num_rows($result_max) > 0) {
                $row_max = mysql_fetch_assoc($result_max);
                $store_stats['last_order_id'] = intval($this->last_order_id);
                if(intval($row_max['last_order_id']) > intval($this->last_order_id)) {
                    $store_stats['last_order_id'] = intval($row_max['last_order_id']);
                }
                $store_stats['new_orders'] = intval($row_max['count_orders']);
            }
        }

        unset($query_where_parts);
        /*
        if(!empty($this->date_from)) {
            $query_where_parts[] = " UNIX_TIMESTAMP(ju.registerDate) >= '".strtotime($this->date_from." 00:00:00")."'";
        }
        if(!empty($this->date_to)) {
            $query_where_parts[] = " UNIX_TIMESTAMP(ju.registerDate) <= '".strtotime($this->date_to." 23:59:59")."'";
        }
        */

        $query_where_parts[] = " UNIX_TIMESTAMP(ju.registerDate) >= '".strtotime($today . " 00:00:00")."'";
        $query_where_parts[] = " UNIX_TIMESTAMP(ju.registerDate) <= '".strtotime($today . " 23:59:59")."'";

        $query = "SELECT COUNT(u.virtuemart_user_id) AS count_customers FROM ".$this->sDBCartPrefix."vmusers AS u
                  LEFT JOIN ".$this->sDBPrefix."users AS ju ON ju.id = u.virtuemart_user_id
				  WHERE u.user_is_vendor = '0'";

        if(!empty($query_where_parts)) {
            $query .= " AND " . implode(" AND ", $query_where_parts);
        }

        $result = mysql_query($query);
        if(mysql_num_rows($result) > 0) {
            $row = mysql_fetch_assoc($result);
            $store_stats['count_customers'] = $row['count_customers'];
        }

        $this->graph_to = $today;
        $this->graph_from = date("Y-m-d", mktime(0, 0, 0, date("m"), date("d")-7, date("Y")));
        $data_graphs = $this->get_data_graphs();

        $store_stats['count_orders'] = $this->bd_nice_number($store_stats['count_orders'], true);
        $store_stats['count_customers'] = $this->bd_nice_number($store_stats['count_customers'], true);

        $result = array_merge($store_stats, array('data_graphs' => $data_graphs));
        return $result;
    }

    public function get_data_graphs() {
        $startDate = strtotime($this->graph_from." 00:00:00");
        $endDate = strtotime($this->graph_to." 23:59:59");
        $date = $startDate;
        $d = 0;
        $average = array('avg_sum_orders' => 0, 'avg_orders' => 0, 'avg_customers' => 0, 'avg_cust_order' => '0.00');
        $orders = array();
        $customers = array();
        while ($date <= $endDate) {
            $d++;
            //Orders
            $query = "SELECT COUNT(virtuemart_order_id) AS tot_orders, UNIX_TIMESTAMP(created_on) AS date_add, SUM(order_total) AS value
                      FROM ".$this->sDBCartPrefix."orders
                      WHERE UNIX_TIMESTAMP(created_on) >= '".$date."' AND UNIX_TIMESTAMP(created_on) < '".strtotime('+1 day', $date)."'";

            if(!empty($this->statuses)) {
                $statuses = explode("|", $this->statuses);
                if(!empty($statuses)) {
                    $stat = array();
                    foreach($statuses as $status) {
                        if($status != "") {
                            $stat[] = $status;
                        }
                    }
                    $parse_statuses = implode("','", $stat);
                    $query .= " AND order_status IN ('".$parse_statuses."')";
                }
            }
            $query .= " GROUP BY DATE(created_on) ORDER BY created_on";

            $result = mysql_query($query);

            if(mysql_num_rows($result) > 0) {
                while($row = mysql_fetch_assoc($result)) {
                    $orders[] = array($row['date_add']*1000, $row['value']);

                    $average['tot_orders'] += $row['tot_orders'];
                    $average['sum_orders'] += $row['value'];
                }
            } else {
                $orders[] = array($date*1000, 0);
            }

            //Customers
            $query = "SELECT COUNT(u.virtuemart_user_id) AS tot_customers, UNIX_TIMESTAMP(ju.registerDate) AS date_add FROM ".$this->sDBCartPrefix."vmusers AS u
                  LEFT JOIN ".$this->sDBPrefix."users AS ju ON ju.id = u.virtuemart_user_id
				  WHERE u.user_is_vendor = '0' AND
				  UNIX_TIMESTAMP(ju.registerDate) >= '".$date."' AND UNIX_TIMESTAMP(ju.registerDate) < '".strtotime('+1 day', $date)."'
				  GROUP BY DATE(ju.registerDate) ORDER BY ju.registerDate";

            $result = mysql_query($query);

            if(mysql_num_rows($result) > 0) {
                while($row = mysql_fetch_assoc($result)) {
                    $customers[] = array($row['date_add']*1000, $row['tot_customers']);
                    $average['tot_customers'] += $row['tot_customers'];
                }
            } else {
                $customers[] = array($date*1000, 0);
            }
            $date = strtotime('+1 day', $date);
        }

        $query = "SELECT vendor_currency FROM ".$this->sDBCartPrefix."vendors";
        $result = mysql_query($query);
        $row = mysql_fetch_assoc($result);
        $iso_numeric_code = $row['vendor_currency'];
        $query = "SELECT currency_symbol, currency_decimal_place, currency_decimal_symbol, currency_thousands, currency_positive_style FROM ".$this->sDBCartPrefix."currencies
				  WHERE virtuemart_currency_id = '".$iso_numeric_code."'";
        $result = mysql_query($query);
        $row = mysql_fetch_assoc($result);

        $currency_sign = $row['currency_symbol'];
        $currency_style = $row['currency_positive_style'];

        $average['avg_sum_orders'] = $this->_price_format($average['sum_orders']/$d);
        $average['avg_orders'] = number_format($average['tot_orders']/$d, 1, '.', '');
        $average['avg_customers'] = number_format($average['tot_customers']/$d, 1, '.', '');

        if($average['tot_customers'] > 0) {
            $average['avg_cust_order'] = $this->_price_format($average['sum_orders']/$average['tot_customers']);
        }
        $average['sum_orders'] = $this->_price_format($average['sum_orders']);
        $average['tot_customers'] = number_format($average['tot_customers'], 1, '.', '');
        $average['tot_orders'] = number_format($average['tot_orders'], 1, '.', '');

        return array('orders' => $orders, 'customers' => $customers, 'currency_sign' => $currency_sign, 'average' => $average, 'currency_style' => $currency_style);
    }

    public function get_orders() {
        $orders = array();
        if(!empty($this->search_order_id)) {
            $query_where_parts[] = " (o.virtuemart_order_id = '".$this->search_order_id."' OR o.order_number = '".$this->search_order_id."')";
        }
        if(!empty($this->orders_from)) {
            $query_where_parts[] = " UNIX_TIMESTAMP(o.created_on) >= '".strtotime($this->orders_from." 00:00:00")."'";
        }
        if(!empty($this->orders_to)) {
            $query_where_parts[] = " UNIX_TIMESTAMP(o.created_on) <= '".strtotime($this->orders_to." 23:59:59")."'";
        }
        if(!empty($this->statuses)) {
            $statuses = explode("|", $this->statuses);
            if(!empty($statuses)) {
                $stat = array();
                foreach($statuses as $status) {
                    if($status != "") {
                        $stat[] = $status;
                    }
                }
                $parse_statuses = implode("','", $stat);
                $query_where_parts[] = " o.order_status IN ('".$parse_statuses."')";
            }
        }

        $query = "SELECT
                    o.virtuemart_order_id AS id_order,
                    o.order_number,
                    o.created_on AS date_add,
                    o.order_total,
					IF(o.virtuemart_user_id = 0, 'Guest', CONCAT(ui.first_name, ' ', ui.last_name)) AS customer,
					os.order_status_name AS ord_status,
					o.order_currency,
					(SELECT SUM(product_quantity) FROM ".$this->sDBCartPrefix."order_items WHERE virtuemart_order_id = o.virtuemart_order_id) AS qty_ordered
				  FROM ".$this->sDBCartPrefix."orders AS o
				  LEFT JOIN ".$this->sDBCartPrefix."userinfos AS ui ON ui.virtuemart_user_id = o.virtuemart_user_id AND ui.address_type = 'BT'
				  LEFT JOIN ".$this->sDBCartPrefix."orderstates AS os ON os.order_status_code = o.order_status";

        $query_page = "SELECT SUM(o.order_total) AS orders_total, COUNT(o.virtuemart_order_id) AS count_ords, MAX(o.created_on) AS max_date, MIN(o.created_on) AS min_date
				  FROM ".$this->sDBCartPrefix."orders AS o";

        if (!empty($query_where_parts)) {
            $query .= " WHERE " . implode(" AND ", $query_where_parts);
            $query_page .= " WHERE " . implode(" AND ", $query_where_parts);
        }
        $result_page = mysql_query($query_page);
        $row_page = mysql_fetch_assoc($result_page);
        $query .= " ORDER BY o.virtuemart_order_id DESC LIMIT ".($this->page - 1)*$this->show." , ".$this->show;

        $result = mysql_query($query);
        while($row = mysql_fetch_assoc($result)) {
            $row['total_paid'] = $this->_price_format($row['order_total'], $row['order_currency']);
            $orders[] = $row;
        }

        $max_date = '';
        $min_date = '';
        if($row_page['count_ords'] > 0) {
            $max_date = date("n/j/Y", strtotime($row_page['max_date']));
            $min_date = date("n/j/Y", strtotime($row_page['min_date']));
        }

        $orders_status = null;
        if(isset($this->get_statuses) && $this->get_statuses == 1) {
            $orders_status = $this->get_orders_statuses();
        }

        return array("orders" => $orders,
            "orders_count" => intval($row_page['count_ords']),
            "orders_total" => $this->_price_format($row_page['orders_total']),
            "max_date" => $max_date,
            "min_date" => $min_date,
            "orders_status" => $orders_status);
    }

    public function get_orders_statuses() {
        $orders_status = array();
        $query = "SELECT order_status_code AS st_id, order_status_name AS st_name FROM ".$this->sDBCartPrefix."orderstates";
        $result_status = mysql_query($query);
        while($row = mysql_fetch_assoc($result_status)) {
            $orders_status[] = $row;
        }
        return $orders_status;
    }

    public function get_orders_info() {
        $order_products = array();
        $query = "SELECT
					o.virtuemart_order_id AS id_order,
					o.virtuemart_user_id AS id_customer,
					o_stat.order_status_name AS status,
					CONCAT(ui.first_name, ' ', ui.last_name) AS customer,
					o.order_currency,
					o.created_on AS date_add,
					ju.email,
                    o.order_subtotal,
                    o.order_shipment,
                    o.order_payment,
                    o.order_total AS total_paid,
                    ui_b.email AS b_email,
                    CONCAT(ui_b.first_name, ' ', ui_b.last_name) AS b_name,
                    ui_b.company AS b_company,
                    ui_b.address_1 AS b_address_1,
                    ui_b.address_2 AS b_address_2,
                    ui_b.zip AS b_zip,
                    ui_b.city AS b_city,
                    b_c.country_name AS b_country,
                    b_s.state_name AS b_state,
                    ui_b.phone_1 AS b_phone_1,
                    ui_b.phone_2 AS b_phone_2,
                    ui_b.fax AS b_fax,
                    ui_s.email AS s_email,
                    CONCAT(ui_s.first_name, ' ', ui_s.last_name) AS s_name,
                    ui_s.company AS s_company,
                    ui_s.address_1 AS s_address_1,
                    ui_s.address_2 AS s_address_2,
                    ui_s.zip AS s_zip,
                    ui_s.city AS s_city,
                    s_c.country_name AS s_country,
                    s_s.state_name AS s_state,
                    ui_s.phone_1 AS s_phone_1,
                    ui_s.phone_2 AS s_phone_2,
                    ui_s.fax AS s_fax
				  FROM ".$this->sDBCartPrefix."orders AS o
				  LEFT JOIN ".$this->sDBCartPrefix."orderstates AS o_stat ON o_stat.order_status_code = o.order_status
				  LEFT JOIN ".$this->sDBPrefix."users AS ju ON ju.id = o.virtuemart_user_id
				  LEFT JOIN ".$this->sDBCartPrefix."userinfos AS ui ON ui.virtuemart_user_id = o.virtuemart_user_id AND ui.address_type = 'BT'
				  LEFT JOIN ".$this->sDBCartPrefix."order_userinfos AS ui_b ON ui_b.virtuemart_user_id = o.virtuemart_user_id AND ui_b.address_type = 'BT' AND ui_b.virtuemart_order_id = '".$this->order_id."'
				  LEFT JOIN ".$this->sDBCartPrefix."countries AS b_c ON b_c.virtuemart_country_id = ui_b.virtuemart_country_id
				  LEFT JOIN ".$this->sDBCartPrefix."states AS b_s ON b_s.virtuemart_state_id = ui_b.virtuemart_state_id
				  LEFT JOIN ".$this->sDBCartPrefix."order_userinfos AS ui_s ON ui_s.virtuemart_user_id = o.virtuemart_user_id AND ui_s.address_type = 'ST' AND ui_s.virtuemart_order_id = '".$this->order_id."'
				  LEFT JOIN ".$this->sDBCartPrefix."countries AS s_c ON s_c.virtuemart_country_id = ui_s.virtuemart_country_id
				  LEFT JOIN ".$this->sDBCartPrefix."states AS s_s ON s_s.virtuemart_state_id = ui_s.virtuemart_state_id
				  WHERE o.virtuemart_order_id = '".$this->order_id."'";

        $result = mysql_query($query);
        $order_info = mysql_fetch_assoc($result);

        $elements = array('order_subtotal', 'order_shipment', 'order_payment', 'total_paid');
        foreach($elements as $element) {
            $order_info[$element] = $this->_price_format($order_info[$element], $order_info['order_currency']);
        }

        $query = "SELECT
					oi.virtuemart_order_id AS id_order,
					oi.virtuemart_product_id AS product_id,
					oi.order_item_name AS product_name,
					oi.product_quantity AS product_quantity,
					oi.product_final_price AS product_price,
					oi.order_item_sku AS sku,
					o.order_currency
 				 FROM ".$this->sDBCartPrefix."order_items AS oi
                 LEFT JOIN ".$this->sDBCartPrefix."orders AS o ON o.virtuemart_order_id = oi.virtuemart_order_id
				 WHERE o.virtuemart_order_id = '".$this->order_id."'";

        $query_page = "SELECT COUNT(virtuemart_order_id) AS count_prods
					   FROM ".$this->sDBCartPrefix."order_items
					   WHERE virtuemart_order_id = '".$this->order_id."'";
        $result_page = mysql_query($query_page);
        $row_page = mysql_fetch_assoc($result_page);
        $query .= " LIMIT ".($this->page - 1)*$this->show." , ".$this->show;
        $result = mysql_query($query);
        while($row = mysql_fetch_assoc($result)) {
            $row['product_price'] = $this->_price_format($row['product_price'], $order_info['order_currency']);
            $row['product_quantity'] = intval($row['product_quantity']);
            $order_products[] = $row;
        }
        $order_full_info = array("order_info" => $order_info, "order_products" => $order_products, "o_products_count" => $row_page['count_prods']);
        return $order_full_info;
    }

    public function get_customers() {
        $customers = array();
        if(!empty($this->customers_from)) {
            $query_where_parts[] = " UNIX_TIMESTAMP(ju.registerDate) >= '".strtotime($this->customers_from." 00:00:00")."'";
        }
        if(!empty($this->customers_to)) {
            $query_where_parts[] = " UNIX_TIMESTAMP(ju.registerDate) <= '".strtotime($this->customers_to." 23:59:59")."'";
        }
        if(!empty($this->search_val)) {
            $query_where_parts[] = " (ju.email LIKE '%".$this->search_val."%' OR ui.first_name LIKE '%".$this->search_val."%' OR ui.last_name LIKE '%".$this->search_val."%')";
        }
        if(!empty($this->cust_with_orders)) {
            $query_where_parts[] = " tot.total_orders > 0";
        }

        $query = "SELECT
					u.virtuemart_user_id AS id_customer,
					ui.first_name AS firstname,
					ui.last_name AS lastname,
					ju.email,
					ju.registerDate AS date_add,
					IFNULL(tot.total_orders, 0) AS total_orders
				  FROM  ".$this->sDBCartPrefix."vmusers AS u
				  LEFT JOIN ".$this->sDBPrefix."users AS ju ON ju.id = u.virtuemart_user_id
				  LEFT JOIN ".$this->sDBCartPrefix."userinfos AS ui ON ui.virtuemart_user_id = u.virtuemart_user_id AND ui.address_type = 'BT'
                  LEFT OUTER JOIN (SELECT COUNT(virtuemart_order_id) AS total_orders, virtuemart_user_id FROM ".$this->sDBCartPrefix."orders GROUP BY virtuemart_user_id) AS tot ON tot.virtuemart_user_id = u.virtuemart_user_id
				  WHERE u.user_is_vendor = '0'";

        $query_page = "SELECT
							COUNT(u.virtuemart_user_id) AS count_custs,
							MAX(ju.registerDate) AS max_date,
							MIN(ju.registerDate) AS min_date
						FROM  ".$this->sDBCartPrefix."vmusers AS u
						LEFT JOIN ".$this->sDBPrefix."users AS ju ON ju.id = u.virtuemart_user_id
                        LEFT OUTER JOIN (SELECT COUNT(virtuemart_order_id) AS total_orders, virtuemart_user_id FROM ".$this->sDBCartPrefix."orders GROUP BY virtuemart_user_id) AS tot ON tot.virtuemart_user_id = u.virtuemart_user_id
						WHERE u.user_is_vendor = '0'";
        if (!empty($query_where_parts)) {
            $query .= " AND " . implode(" AND ", $query_where_parts);
            $query_page .= " AND " . implode(" AND ", $query_where_parts);
        }

        $query .= " LIMIT ".($this->page - 1)*$this->show." , ".$this->show;
        $result_page = mysql_query($query_page);
        $row_page = mysql_fetch_assoc($result_page);
        $result = mysql_query($query);
        while($row = mysql_fetch_assoc($result)) {
            $date = explode(' ', $row['date_add']);
            $row['date_add'] = $date[0];
            $customers[] = $row;
        }
        return array("customers_count" => $row_page['count_custs'],
            "customers" => $customers,
            "max_date" => date("n/j/Y", strtotime($row_page['max_date'])),
            "min_date" => date("n/j/Y", strtotime($row_page['min_date']))
        );
    }

    public function get_customers_info() {
        $customer_orders = array();
        $query = "SELECT
					ui.virtuemart_user_id AS customers_id,
					CONCAT(ui.first_name, ' ', ui.middle_name, ' ', ui.last_name) AS name,
					ju.email,
					ju.registerDate AS date_add,
					ui.company,
					ui.address_1,
					ui.address_2,
					ui.zip,
					ui.city,
					c.country_name AS country,
					s.state_name AS state,
					ui.phone_1,
					ui.phone_2,
					ui.fax
				  FROM ".$this->sDBCartPrefix."userinfos AS ui
                    LEFT JOIN ".$this->sDBCartPrefix."countries AS c ON c.virtuemart_country_id = ui.virtuemart_country_id
				    LEFT JOIN ".$this->sDBCartPrefix."states AS s ON s.virtuemart_state_id = ui.virtuemart_state_id
				    LEFT JOIN ".$this->sDBPrefix."users AS ju ON ju.id = ui.virtuemart_user_id
				  WHERE ui.virtuemart_user_id = '".$this->user_id."' AND ui.address_type = 'BT'";
        $result = mysql_query($query);
        $user_info = mysql_fetch_assoc($result);

        $user_info['address'] = $this->split_values($user_info, array('country', 'city', 'state', 'address_1', 'address_2'));
        unset($user_info['country']);
        unset($user_info['city']);
        unset($user_info['state']);
        unset($user_info['address_1']);
        unset($user_info['address_2']);

        $query = "SELECT
                    o.virtuemart_order_id AS id_order,
                    o.created_on AS date_add,
                    o.order_total AS total_paid,
					os.order_status_name AS ord_status,
					o.order_currency,
					(SELECT SUM(product_quantity) FROM ".$this->sDBCartPrefix."order_items WHERE virtuemart_order_id = o.virtuemart_order_id) AS pr_qty
				  FROM ".$this->sDBCartPrefix."orders AS o
				  LEFT JOIN ".$this->sDBCartPrefix."orderstates AS os ON os.order_status_code = o.order_status
				  WHERE o.virtuemart_user_id = '".$this->user_id."'";

        $query_page = "SELECT SUM(order_total) AS sum_ords, COUNT(virtuemart_order_id) AS count_ords
				  FROM ".$this->sDBCartPrefix."orders WHERE virtuemart_user_id = '".$this->user_id."'";
        $result_page = mysql_query($query_page);
        $row_page = mysql_fetch_assoc($result_page);

        $query .= " LIMIT ".($this->page - 1)*$this->show." , ".$this->show;
        $result = mysql_query($query);

        while($row = mysql_fetch_assoc($result)) {
            $row['total_paid'] = $this->_price_format($row['total_paid'], $row['order_currency']);
            $customer_orders[] = $row;
        }

        $customer_info = array("user_info" => $user_info, "customer_orders" => $customer_orders, "c_orders_count" => intval($row_page['count_ords']), "sum_ords" => $this->_price_format($row_page['sum_ords']));
        return $customer_info;
    }

    public function search_products() {
        $query_where_parts = array();
        $products = array();
        $active_language = $this->_get_active_languages();
        $this->params = explode("|", $this->params);
        foreach($this->params as $param) {
            switch ($param) {
                case 'pr_id':
                    $query_where_parts[] = " p.virtuemart_product_id = '".$this->val."'";
                    break;
                case 'pr_sku':
                    $query_where_parts[] = " p.product_sku = '".$this->val."'";
                    break;
                case 'pr_name':
                    $query_where_parts[] = " peg.product_name LIKE '%".$this->val."%'";
                    break;
                case 'pr_desc':
                    $query_where_parts[] = " peg.product_desc LIKE '%".$this->val."%'";
                    break;
                case 'pr_short_desc':
                    $query_where_parts[] = " peg.product_s_desc LIKE '%".$this->val."%'";
                    break;
            }
        }
        $query = "SELECT
					p.virtuemart_product_id AS main_id,
					peg.product_name AS name,
					pp.product_price AS price,
					pp.product_currency,
					p.product_sku AS sku,
					p.product_in_stock AS quantity
				  FROM ".$this->sDBCartPrefix."products AS p
				  	LEFT JOIN ".$this->sDBCartPrefix."product_prices AS pp ON pp.virtuemart_product_id = p.virtuemart_product_id
				  	LEFT JOIN ".$this->sDBCartPrefix."products_".$active_language." AS peg ON peg.virtuemart_product_id = p.virtuemart_product_id";

        $query_page = "SELECT COUNT(p.virtuemart_product_id) AS count_prods FROM ".$this->sDBCartPrefix."products AS p
						LEFT JOIN ".$this->sDBCartPrefix."product_prices AS pp ON pp.virtuemart_product_id = p.virtuemart_product_id
						LEFT JOIN ".$this->sDBCartPrefix."products_".$active_language." AS peg ON peg.virtuemart_product_id = p.virtuemart_product_id";
        if (!empty($query_where_parts)) {
            $query .= " WHERE ( " . implode(" OR ", $query_where_parts) . " )";
            $query_page .= " WHERE ( " . implode(" OR ", $query_where_parts) . " )";
        }

        $result_page = mysql_query($query_page);
        $row_page = mysql_fetch_assoc($result_page);

        $query .= " GROUP BY p.virtuemart_product_id LIMIT ".($this->page - 1)*$this->show." , ".$this->show;

        $result = mysql_query($query);

        while($row = mysql_fetch_assoc($result)) {
            $row['price'] = $this->_price_format($row['price'], $row['product_currency']);
            $products[] = $row;
        }
        return array("products_count" => $row_page['count_prods'], "products" => $products);
    }

    public function search_products_ordered() {
        $query_where_parts = array();
        $this->params = explode("|", $this->params);
        foreach($this->params as $param) {
            switch ($param) {
                case 'pr_id':
                    $query_where_parts[] = " oi.virtuemart_order_id = '".$this->val."'";
                    break;
                case 'pr_sku':
                    $query_where_parts[] = " oi.order_item_sku = '".$this->val."'";
                    break;
                case 'pr_name':
                    $query_where_parts[] = " oi.order_item_name LIKE '%".$this->val."%'";
                    break;
            }
        }
        if(!empty($this->products_from)) {
            $query_where_parts[] = " UNIX_TIMESTAMP(o.created_on) >= '".strtotime($this->products_from." 00:00:00")."'";
        }
        if(!empty($this->products_to)) {
            $query_where_parts[] = " UNIX_TIMESTAMP(o.created_on) <= '".strtotime($this->products_to." 23:59:59")."'";
        }

        $query = "SELECT
					o.virtuemart_order_id AS main_id,
					oi.order_item_name AS name,
					oi.product_quantity AS quantity,
					oi.product_item_price AS price,
					oi.product_subtotal_with_tax AS total_price,
					oi.product_final_price AS final_price,
					oi.order_item_sku AS sku,
					o.order_currency,
					o.created_on,
					os.order_status_name AS status
 				 FROM ".$this->sDBCartPrefix."order_items AS oi
					LEFT JOIN ".$this->sDBCartPrefix."orders AS o ON o.virtuemart_order_id = oi.virtuemart_order_id
					LEFT JOIN ".$this->sDBCartPrefix."orderstates AS os ON os.order_status_code = o.order_status";

        $query_page = "SELECT COUNT(oi.virtuemart_order_id) AS count_prods, MAX(o.created_on) AS max_date, MIN(o.created_on) AS min_date
                       FROM ".$this->sDBCartPrefix."order_items AS oi
                       LEFT JOIN ".$this->sDBCartPrefix."orders AS o ON o.virtuemart_order_id = oi.virtuemart_order_id";

        if (!empty($query_where_parts)) {
            $query .= " WHERE " . implode(" AND ", $query_where_parts);
            $query_page .= " WHERE " . implode(" AND ", $query_where_parts);
        }

        $result_page = mysql_query($query_page);
        $row_page = mysql_fetch_assoc($result_page);
        $query .= " LIMIT ".($this->page - 1)*$this->show." , ".$this->show;
        $result = mysql_query($query);

        while($row = mysql_fetch_assoc($result)) {
            $row['price'] = $this->_price_format($row['price'], $row['order_currency']);
            if($row['total_price'] > 0) {
                $row['total_price'] = $this->_price_format($row['total_price'], $row['order_currency']);
            } else {
                unset($row['total_price']);
            }
            if($row['final_price'] > 0) {
                $row['final_price'] = $this->_price_format($row['final_price'], $row['order_currency']);
            } else {
                unset($row['final_price']);
            }
            $row['quantity'] = intval($row['quantity']);
            $order_products[] = $row;
        }

        if($row_page['count_prods'] > 0) {
            $max_date = date("n/j/Y", strtotime($row_page['max_date']));
            $min_date = date("n/j/Y", strtotime($row_page['min_date']));
        }

        return array("products_count" => $row_page['count_prods'],
            "products" => $order_products,
            "max_date" => $max_date,
            "min_date" => $min_date);
    }

    public function get_products_info() {
        $active_language = $this->_get_active_languages();
        $query = "SELECT
					p.virtuemart_product_id AS id_product,
					peg.product_name AS name,
					pp.product_price AS price,
					pp.product_currency,
					p.product_sku AS sku,
					IF(p.published = 1, 'Published', 'Unpublished') AS active,
					p.product_in_stock AS quantity,
					m.file_url AS id_image,
					p.product_ordered AS total_ordered
				  FROM ".$this->sDBCartPrefix."products AS p
				  	LEFT JOIN ".$this->sDBCartPrefix."product_prices AS pp ON pp.virtuemart_product_id = p.virtuemart_product_id
				  	LEFT JOIN ".$this->sDBCartPrefix."products_".$active_language." AS peg ON peg.virtuemart_product_id = p.virtuemart_product_id
				  	LEFT JOIN ".$this->sDBCartPrefix."product_medias AS pm ON pm.virtuemart_product_id = p.virtuemart_product_id
				  	LEFT JOIN ".$this->sDBCartPrefix."medias AS m ON m.virtuemart_media_id = pm.virtuemart_media_id
				  WHERE p.virtuemart_product_id  = '".$this->product_id."' GROUP BY pm.virtuemart_product_id";

        $res = mysql_query($query);
        $row = false;
        if(mysql_num_rows($res) > 0) {
            $row = mysql_fetch_assoc($res);
            $row['price'] = $this->_price_format($row['price'], $row['product_currency']);
            $row['total_ordered'] = intval($row['total_ordered']);
            //$row['name'] = utf8_encode($row['name']);
            if(file_exists($row['id_image'])) {
                $query_url = "SELECT vendor_url FROM ".$this->sDBCartPrefix."vendors_".$active_language;
                $result_url = mysql_query($query_url);
                $row_url = mysql_fetch_assoc($result_url);

                $row['id_image'] = $row_url['vendor_url'] . "/" .  $row['id_image'];
            }
        }
        return $row;
    }

    public function get_products_descr() {
        $active_language = $this->_get_active_languages();
        $query = "SELECT product_desc AS descr FROM ".$this->sDBCartPrefix."products_".$active_language." WHERE virtuemart_product_id = '".$this->product_id."'";
        $row = mysql_fetch_assoc(mysql_query($query));

        return $row;
    }

    private function _currency_convert($amountA, $currA = '', $currB = '') {
        $amountA = floatval($amountA);
        if(intval($currB) > 0) {
            $query = "SELECT currency_code_3 FROM ".$this->sDBCartPrefix."currencies
					  WHERE virtuemart_currency_id = '".$currB."'";
            $row = mysql_fetch_assoc(mysql_query($query));
            $currB = $row['currency_code_3'];
        }
        $converter_filepath = "administrator/components/com_virtuemart/plugins/currency_converter/convertECB.php";
        if(file_exists(dirname(__FILE__)."/".$converter_filepath)) {
            define('_VALID_MOS', 1);
            define('_JEXEC', 1);
            require_once(dirname(__FILE__)."/".$converter_filepath);
            $convert = new convertECB();
            $curr_filename = $convert->document_address;
            $contents = @file_get_contents( $curr_filename );
            if(!$contents ) {
                return false;
            }
            $contents = str_replace ("<Cube currency='USD'", " <Cube currency='EUR' rate='1'/> <Cube currency='USD'", $contents);
            $xmlDoc = new DomDocument();
            if( !$xmlDoc->loadXML($contents) ) {
                return false;
            }
            $currency_list = $xmlDoc->getElementsByTagName( "Cube" );
            $length = $currency_list->length;
            for ($i = 0; $i < $length; $i++) {
                $currNode = $currency_list->item($i);
                if(!empty($currNode) && !empty($currNode->attributes->getNamedItem("currency")->nodeValue)){
                    $currency[$currNode->attributes->getNamedItem("currency")->nodeValue] = $currNode->attributes->getNamedItem("rate")->nodeValue;
                    unset( $currNode );
                }
            }
            if($currA == '') {
                $query = "SELECT vendor_currency FROM ".$this->sDBCartPrefix."vendors";
                $result = mysql_query($query);
                $row = mysql_fetch_assoc($result);
                $currA = $row['vendor_currency'];
            }
            if( $currA == $currB) {
                return $amountA;
            }
            $valA = isset($currency[$currA]) ? $currency[$currA] : 1;
            $valB = isset($currency[$currB]) ? $currency[$currB] : 1;
            $val = $amountA * $valB / $valA;
            return $val;
        }
        return false;
    }

    private function _price_format($price, $iso_numeric_code = 0, $no_format = false) {
        if($iso_numeric_code == 0) {
            $query = "SELECT vendor_currency FROM ".$this->sDBCartPrefix."vendors";
            $result = mysql_query($query);
            $row = mysql_fetch_assoc($result);
            $iso_numeric_code = $row['vendor_currency'];
        }

        $query = "SELECT currency_symbol, currency_decimal_place, currency_decimal_symbol, currency_thousands, currency_positive_style FROM ".$this->sDBCartPrefix."currencies
				  WHERE virtuemart_currency_id = '".$iso_numeric_code."'";
        $result = mysql_query($query);
        $row = mysql_fetch_assoc($result);

        $currency_sign = '<span>' . $row['currency_symbol'] . '</span>';
        $decimals = $row['currency_decimal_place'];
        $dec_point = $row['currency_decimal_symbol'];
        $thousands_sep = $row['currency_thousands'];

        if(!$no_format) {
            $price = number_format($price, intval($decimals), $dec_point, $thousands_sep);
        }

        $curr_format = str_replace('{number}', $price, $row['currency_positive_style']);
        $curr_format = str_replace('{symbol}', $currency_sign, $curr_format);
        return $curr_format;
    }

    private function _get_active_languages() {
        $query = "SELECT `config` FROM `".$this->sDBCartPrefix."configs` WHERE `virtuemart_config_id` = '1'";
        $result = mysql_query($query);
        if(mysql_num_rows($result) == 1) {
            $row = mysql_fetch_assoc($result);
            $conn = explode("|", $row['config']);

            $str = null;
            foreach($conn as $con) {
                $con = explode("=", $con);
                $str[$con[0]] = @unserialize($con[1]);
            }
            return $str['vmlang'];
        }
    }
}


class XCartSA extends MainSA {
    public function get_store_title() {
        $default_attrs = $this->_get_default_attrs();
        return array('test' => 1, 'title' => $default_attrs["company_name"]);
    }

    public function get_store_stats() {
        $store_stats = array('count_orders' => "0", 'total_sales' => "0", 'count_customers' => "0", "last_order_id" => "0", "new_orders" => "0");
        /*
        if(!empty($this->date_from)) {
            $query_where_parts[] = " date >= '".strtotime($this->date_from." 00:00:00")."'";
        }
        if(!empty($this->date_to)) {
            $query_where_parts[] = " date <= '".strtotime($this->date_to." 23:59:59")."'";
        }
        */

        $today = date("Y-m-d", time());
        $query_where_parts[] = " date >= '".strtotime($today . " 00:00:00")."'";
        $query_where_parts[] = " date <= '".strtotime($today . " 23:59:59")."'";
        if(!empty($this->statuses)) {
            $statuses = explode("|", $this->statuses);
            if(!empty($statuses)) {
                $stat = array();
                foreach($statuses as $status) {
                    if($status != "") {
                        $stat[] = $status;
                    }
                }
                $parse_statuses = implode("','", $stat);
                $query_where_parts[] = " status IN ('".$parse_statuses."')";
            }
        }
        $query = "SELECT COUNT(orderid) AS count_orders, SUM(total) AS total_sales FROM ".$this->sDBPrefix."orders";
        if(!empty($query_where_parts)) {
            $query .= " WHERE " . implode(" AND ", $query_where_parts);
        }

        $result = mysql_query($query);
        if(mysql_num_rows($result) > 0) {
            $store_stats = mysql_fetch_assoc($result);
            $store_stats['total_sales'] = $this->_price_format($this->bd_nice_number($store_stats['total_sales']), true);
        }

        if($this->last_order_id != "") {
            $query_max = "SELECT COUNT(orderid) AS count_orders, MAX(orderid) AS last_order_id
                          FROM ".$this->sDBPrefix."orders
                          WHERE orderid > ".$this->last_order_id;
            if(!empty($query_where_parts)) {
                $query_max .= " AND " . implode(" AND ", $query_where_parts);
            }
            $result_max = mysql_query($query_max);
            if(mysql_num_rows($result_max) > 0) {
                $row_max = mysql_fetch_assoc($result_max);
                $store_stats['last_order_id'] = intval($this->last_order_id);
                if(intval($row_max['last_order_id']) > intval($this->last_order_id)) {
                    $store_stats['last_order_id'] = intval($row_max['last_order_id']);
                }
                $store_stats['new_orders'] = intval($row_max['count_orders']);
            }
        }

        unset($query_where_parts);
        /*
        if(!empty($this->date_from)) {
            $query_where_parts[] = " first_login >= '".strtotime($this->date_from." 00:00:00")."'";
        }
        if(!empty($this->date_to)) {
            $query_where_parts[] = " first_login <= '".strtotime($this->date_to." 23:59:59")."'";
        }
        */

        $query_where_parts[] = " first_login >= '".strtotime($today . " 00:00:00")."'";
        $query_where_parts[] = " first_login <= '".strtotime($today . " 23:59:59")."'";
        $query = "SELECT COUNT(id) AS count_customers FROM ".$this->sDBPrefix."customers";
        if(!empty($query_where_parts)) {
            $query .= " WHERE " . implode(" AND ", $query_where_parts);
        }

        $result = mysql_query($query);
        if(mysql_num_rows($result) > 0) {
            $row = mysql_fetch_assoc($result);
            $store_stats = array_merge($store_stats, $row);
        }

        $this->graph_to = $today;
        $this->graph_from = date("Y-m-d", mktime(0, 0, 0, date("m"), date("d")-7, date("Y")));
        $data_graphs = $this->get_data_graphs();

        $store_stats['count_orders'] = $this->bd_nice_number($store_stats['count_orders'], true);
        $store_stats['count_customers'] = $this->bd_nice_number($store_stats['count_customers'], true);

        $result = array_merge($store_stats, array('data_graphs' => $data_graphs));
        return $result;
    }

    public function get_data_graphs() {
        $startDate = strtotime($this->graph_from." 00:00:00");
        $endDate = strtotime($this->graph_to." 23:59:59");
        $date = $startDate;
        $d = 0;
        $average = array('avg_sum_orders' => 0, 'avg_orders' => 0, 'avg_customers' => 0, 'avg_cust_order' => '0.00');
        $orders = array();
        $customers = array();
        while ($date <= $endDate) {
            $d++;
            //Orders
            $query = "SELECT COUNT(orderid) AS tot_orders, date AS date_add, SUM(total) AS value
                      FROM ".$this->sDBPrefix."orders
                      WHERE date >= '".$date."' AND date < '".strtotime('+1 day', $date)."'";

            if(!empty($this->statuses)) {
                $statuses = explode("|", $this->statuses);
                if(!empty($statuses)) {
                    $stat = array();
                    foreach($statuses as $status) {
                        if($status != "") {
                            $stat[] = $status;
                        }
                    }
                    $parse_statuses = implode("','", $stat);
                    $query .= " AND status IN ('".$parse_statuses."')";
                }
            }
            $query .= " GROUP BY DATE(date) ORDER BY date";

            $result = mysql_query($query);

            if(mysql_num_rows($result) > 0) {
                while($row = mysql_fetch_assoc($result)) {
                    $orders[] = array($row['date_add']*1000, $row['value']);

                    $average['tot_orders'] += $row['tot_orders'];
                    $average['sum_orders'] += $row['value'];
                }
            } else {
                $orders[] = array($date*1000, 0);
            }

            //Customers
            $query = "SELECT COUNT(id) AS tot_customers, first_login AS date_add FROM ".$this->sDBPrefix."customers
				  WHERE usertype <> 'P' AND
				  first_login >= '".$date."' AND first_login < '".strtotime('+1 day', $date)."'
				  GROUP BY DATE(first_login) ORDER BY first_login";

            $result = mysql_query($query);

            if(mysql_num_rows($result) > 0) {
                while($row = mysql_fetch_assoc($result)) {
                    $customers[] = array($row['date_add']*1000, $row['tot_customers']);
                    $average['tot_customers'] += $row['tot_customers'];
                }
            } else {
                $customers[] = array($date*1000, 0);
            }
            $date = strtotime('+1 day', $date);
        }

        $default_attrs = $this->_get_default_attrs();

        $average['avg_sum_orders'] = $this->_price_format($average['sum_orders']/$d);
        $average['avg_orders'] = number_format($average['tot_orders']/$d, 1, '.', '');
        $average['avg_customers'] = number_format($average['tot_customers']/$d, 1, '.', '');

        if($average['tot_customers'] > 0) {
            $average['avg_cust_order'] = $this->_price_format($average['sum_orders']/$average['tot_customers']);
        }
        $average['sum_orders'] = $this->_price_format($average['sum_orders']);
        $average['tot_customers'] = number_format($average['tot_customers'], 1, '.', '');
        $average['tot_orders'] = number_format($average['tot_orders'], 1, '.', '');

        return array('orders' => $orders, 'customers' => $customers, 'currency_sign' => $default_attrs['currency_symbol'], 'average' => $average, 'currency_style' => $default_attrs['currency_format']);
    }

    public function get_orders() {
        $orders = array();
        $default_attrs = $this->_get_default_attrs();
        if(!empty($this->search_order_id)) {
            $query_where_parts[] = " o.orderid = '".$this->search_order_id."'";
        }
        if(!empty($this->orders_from)) {
            $query_where_parts[] = " date >= '".strtotime($this->orders_from." 00:00:00")."'";
        }
        if(!empty($this->orders_to)) {
            $query_where_parts[] = " date <= '".strtotime($this->orders_to." 23:59:59")."'";
        }
        if(!empty($this->statuses)) {
            $statuses = explode("|", $this->statuses);
            if(!empty($statuses)) {
                $stat = array();
                foreach($statuses as $status) {
                    if($status != "") {
                        $stat[] = $status;
                    }
                }
                $this->statuses = implode("','", $stat);
                $query_where_parts[] = " status IN ('".$this->statuses."')";
            }
        }

        $query = "SELECT
                    o.orderid AS id_order,
                    o.date AS date_add,
                    o.total AS order_total,
					CONCAT(o.firstname, ' ', o.lastname) AS customer,
					o.status AS ord_status,
					(SELECT SUM(amount) FROM ".$this->sDBPrefix."order_details WHERE orderid = o.orderid) AS qty_ordered
				  FROM ".$this->sDBPrefix."orders AS o";

        $query_page = "SELECT SUM(o.total) AS orders_total, COUNT(o.orderid) AS count_ords, MAX(o.date) AS max_date, MIN(o.date) AS min_date
				  FROM ".$this->sDBPrefix."orders AS o";

        if (!empty($query_where_parts)) {
            $query .= " WHERE " . implode(" AND ", $query_where_parts);
            $query_page .= " WHERE " . implode(" AND ", $query_where_parts);
        }
        $result_page = mysql_query($query_page);
        $row_page = mysql_fetch_assoc($result_page);
        $query .= " ORDER BY o.orderid DESC LIMIT ".($this->page - 1)*$this->show." , ".$this->show;

        $result = mysql_query($query);

        while($row = mysql_fetch_assoc($result)) {
            $row['total_paid'] = $this->_price_format($row['order_total']);
            $row['ord_status'] = $this->_get_order_status($row['ord_status']);

            $row['date_add'] = strftime($default_attrs['date_format'], $row['date_add']) . ' ' . strftime($default_attrs['time_format'], $row['date_add']);
            $orders[] = $row;
        }

        $max_date = '';
        $min_date = '';
        if($row_page['count_ords'] > 0) {
            $max_date = date("n/j/Y", $row_page['max_date']);
            $min_date = date("n/j/Y", $row_page['min_date']);
        }

        $orders_status = null;
        if(isset($this->get_statuses) && $this->get_statuses == 1) {
            $orders_status = $this->get_orders_statuses();
        }

        return array("orders" => $orders,
            "orders_count" => intval($row_page['count_ords']),
            "orders_total" => $this->_price_format($row_page['orders_total']),
            "max_date" => $max_date,
            "min_date" => $min_date,
            "orders_status" => $orders_status);
    }

    public function get_orders_statuses() {
        $default_attrs = $this->_get_default_attrs();
        $orders_status = array();
        $statuses = array(
            'lbl_not_finished' => 'I',
            'lbl_processed' => 'P',
            'lbl_backordered' => 'B',
            'lbl_declined' => 'D',
            'lbl_failed' => 'F',
            'lbl_queued' => 'Q',
            'lbl_complete' => 'C');
        $query = "SELECT name, value FROM ".$this->sDBPrefix."languages WHERE name IN ('lbl_not_finished', 'lbl_processed', 'lbl_backordered', 'lbl_declined', 'lbl_failed', 'lbl_queued', 'lbl_complete') AND code = '".$default_attrs['default_admin_language']."'";

        $result_status = mysql_query($query);
        while($row = mysql_fetch_assoc($result_status)) {
            $orders_status[] = array('st_id' => $statuses[$row['name']], 'st_name' => $row['value']);
        }
        return $orders_status;
    }

    public function get_orders_info() {
        $order_products = array();
        $order_info = array();
        $default_attrs = $this->_get_default_attrs();
        $def_lang = $default_attrs['default_admin_language'];

        $query = "SELECT
            o.orderid AS id_order,
            o.userid AS id_customer,
            c.email,
            CONCAT(c.firstname, ' ', c.lastname) AS customer,
            o.date,
            o.status,
            o.payment_method,
            o.shipping,
            o.subtotal,
            o.discount,
            o.coupon_discount,
            o.shipping_cost,
            o.payment_surcharge,
            o.total,
            CONCAT(o.b_firstname, ' ', o.b_lastname) AS b_name,
            o.b_address,
            o.b_city,
            o.b_county,
            b_s.state AS b_state,
            o.b_zipcode,
            o.b_zip4,
            o.b_phone,
            o.b_fax,
            CONCAT(o.s_firstname, ' ', o.s_lastname) AS s_name,
            o.s_address,
            o.s_city,
            o.s_county,
            s_s.state AS s_state,
            o.s_country,
            o.s_zipcode,
            o.s_zip4,
            o.s_phone,
            o.s_fax,
            (SELECT value FROM ".$this->sDBPrefix."languages WHERE name = CONCAT('country_', o.b_country) AND code = '".$def_lang."') AS b_country,
            (SELECT value FROM ".$this->sDBPrefix."languages WHERE name = CONCAT('country_', o.s_country) AND code = '".$def_lang."') AS s_country
            FROM ".$this->sDBPrefix."orders AS o
            LEFT JOIN ".$this->sDBPrefix."customers AS c ON c.id = o.userid
            LEFT JOIN ".$this->sDBPrefix."states AS b_s ON b_s.code = o.b_state
            LEFT JOIN ".$this->sDBPrefix."states AS s_s ON s_s.code = o.s_state
            WHERE o.orderid = '".$this->order_id."'";

        $result = mysql_query($query);
        if(mysql_num_rows($result) > 0) {
            $order_info = mysql_fetch_assoc($result);
            $order_info['date_add'] = strftime($default_attrs['date_format'], $order_info['date']) . ' ' . strftime($default_attrs['time_format'], $order_info['date']);
            $order_info['subtotal'] = $this->_price_format($order_info['subtotal']);
            $order_info['discount'] = $this->_price_format($order_info['discount']);
            $order_info['coupon_discount'] = $this->_price_format($order_info['coupon_discount']);
            $order_info['shipping_cost'] = $this->_price_format($order_info['shipping_cost']);
            $order_info['payment_surcharge'] = $this->_price_format($order_info['payment_surcharge']);
            $order_info['total'] = $this->_price_format($order_info['total']);
            $order_info['status'] = $this->_get_order_status($order_info['status']);
        }
        $query = "SELECT orderid AS id_order, productid AS product_id, product, amount, price, productcode AS sku FROM ".$this->sDBPrefix."order_details WHERE orderid = '".$this->order_id."'";
        $query_page = "SELECT COUNT(productid) AS count_prods FROM ".$this->sDBPrefix."order_details WHERE orderid = '".$this->order_id."'";
        $result_page = mysql_query($query_page);
        $row_page = mysql_fetch_assoc($result_page);
        $query .= " LIMIT ".($this->page - 1)*$this->show." , ".$this->show;
        $result = mysql_query($query);
        while($row = mysql_fetch_assoc($result)) {
            $row['product_price'] = $this->_price_format($row['price']*$row['amount']);
            $row['product_quantity'] = intval($row['amount']);
            $row['product_name'] = utf8_encode($row['product']);
            $order_products[] = $row;
        }
        $order_full_info = array("order_info" => $order_info, "order_products" => $order_products, "o_products_count" => $row_page['count_prods']);
        return $order_full_info;
    }

    public function get_customers() {
        $customers = array();
        $default_attrs = $this->_get_default_attrs();

        if(!empty($this->customers_from)) {
            $query_where_parts[] = " c.first_login >= '".strtotime($this->customers_from." 00:00:00")."'";
        }
        if(!empty($this->customers_to)) {
            $query_where_parts[] = " c.first_login <= '".strtotime($this->customers_to." 23:59:59")."'";
        }
        if(!empty($this->search_val)) {
            $query_where_parts[] = " (c.email LIKE '%".$this->search_val."%' OR c.firstname LIKE '%".$this->search_val."%' OR c.lastname LIKE '%".$this->search_val."%')";
        }
        if(!empty($this->cust_with_orders)) {
            $query_where_parts[] = " tot.total_orders > 0";
        }

        $query = "SELECT
					c.id AS id_customer,
					c.firstname,
					c.lastname,
					c.first_login,
					c.email,
					IFNULL(tot.total_orders, 0) AS total_orders
				  FROM ".$this->sDBPrefix."customers AS c
                  LEFT OUTER JOIN (SELECT COUNT(orderid) AS total_orders, userid FROM ".$this->sDBPrefix."orders GROUP BY userid) AS tot ON tot.userid = c.id";

        $query_page = "SELECT COUNT(c.id) AS count_custs, MAX(c.first_login) AS max_date, MIN(c.first_login) AS min_date
						FROM ".$this->sDBPrefix."customers AS c
						LEFT OUTER JOIN (SELECT COUNT(orderid) AS total_orders, userid FROM ".$this->sDBPrefix."orders GROUP BY userid) AS tot ON tot.userid = c.id";
        if (!empty($query_where_parts)) {
            $query .= " WHERE " . implode(" AND ", $query_where_parts);
            $query_page .= " WHERE " . implode(" AND ", $query_where_parts);
        }
        $query .= " LIMIT ".($this->page - 1)*$this->show." , ".$this->show;
        $result_page = mysql_query($query_page);
        $row_page = mysql_fetch_assoc($result_page);

        $result = mysql_query($query);
        while($row = mysql_fetch_assoc($result)) {
            $row['date_add'] = strftime($default_attrs['date_format'], $row['first_login']) . ' ' . strftime($default_attrs['time_format'], $row['first_login']);
            $row['total_orders'] = intval($row['total_orders']);
            $customers[] = $row;
        }

        $max_date = '';
        $min_date = '';
        if($row_page['count_custs'] > 0) {
            $max_date = date("n/j/Y", $row_page['max_date']);
            $min_date = date("n/j/Y", $row_page['min_date']);
        }
        return array("customers_count" => $row_page['count_custs'],
            "customers" => $customers,
            "max_date" => $max_date,
            "min_date" => $min_date);
    }

    public function get_customers_info() {
        $customer_orders = array();
        $default_attrs = $this->_get_default_attrs();
        $query = "SELECT
					c.id AS customers_id,
					c.email,
					c.first_login,
					CONCAT(c.firstname, ' ', c.lastname) AS name,
					ab.phone,
					ab.fax,
					ab.city,
					ab.address,
					s.state,
                    (SELECT value FROM ".$this->sDBPrefix."languages WHERE name = CONCAT('country_', ab.country) AND code = '".$default_attrs['default_admin_language']."') AS country
				  FROM ".$this->sDBPrefix."customers AS c
				  	LEFT JOIN ".$this->sDBPrefix."address_book AS ab ON ab.userid = c.id AND default_s = 'Y'
				  	LEFT JOIN ".$this->sDBPrefix."states AS s ON s.code = ab.state
				  WHERE c.id = '".$this->user_id."'";
        $result = mysql_query($query);
        $user_info = mysql_fetch_assoc($result);
        $user_info['date_add'] = strftime($default_attrs['date_format'], $user_info['first_login']) . ' ' . strftime($default_attrs['time_format'], $user_info['first_login']);
        $user_info['address'] = $this->split_values($user_info, array('country','state','city','address'));

        $query = "SELECT orderid AS id_order, total, status, date, (SELECT SUM(amount) FROM ".$this->sDBPrefix."order_details WHERE orderid = o.orderid) AS pr_qty
				  FROM ".$this->sDBPrefix."orders AS o WHERE o.userid = '".$this->user_id."'";
        $query_page = "SELECT COUNT(orderid) AS count_ords, SUM(total) AS sum_ords FROM ".$this->sDBPrefix."orders WHERE userid = '".$this->user_id."'";
        $result_page = mysql_query($query_page);
        $row_page = mysql_fetch_assoc($result_page);
        $query .= " LIMIT ".($this->page - 1)*$this->show." , ".$this->show;
        $result = mysql_query($query);
        while($row = mysql_fetch_assoc($result)) {
            $row['total_paid'] = $this->_price_format($row['total']);
            $row['ord_status'] = $this->_get_order_status($row['status']);
            $row['date_add'] = strftime($default_attrs['date_format'], $row['date']) . ' ' . strftime($default_attrs['time_format'], $row['date']);
            $customer_orders[] = $row;
        }
        $row_page['sum_ords'] = $this->_price_format($row_page['sum_ords']);
        $customer_info = array("user_info" => $user_info, "customer_orders" => $customer_orders, "c_orders_count" => intval($row_page['count_ords']), "sum_ords" => $row_page['sum_ords']);
        return $customer_info;
    }

    public function search_products() {
        $query_where_parts = array();
        $products = array();
        $default_attrs = $this->_get_default_attrs();
        $def_lang = $default_attrs['default_admin_language'];

        $this->params = explode("|", $this->params);
        foreach($this->params as $param) {
            switch ($param) {
                case 'pr_id':
                    $query_where_parts[] = " p.productid = '".$this->val."'";
                    break;
                case 'pr_sku':
                    $query_where_parts[] = " p.productcode = '".$this->val."'";
                    break;
                case 'pr_name':
                    $query_where_parts[] = " prl.product LIKE '%".$this->val."%'";
                    break;
                case 'pr_desc':
                    $query_where_parts[] = " prl.fulldescr LIKE '%".$this->val."%'";
                    break;
                case 'pr_short_desc':
                    $query_where_parts[] = " prl.descr LIKE '%".$this->val."%'";
                    break;
            }
        }
        $query = "SELECT p.productid AS main_id, p.productcode AS sku, prl.product AS name, pr.price, p.list_price, p.avail AS quantity
				  FROM ".$this->sDBPrefix."products AS p
				  LEFT JOIN ".$this->sDBPrefix."pricing AS pr ON pr.productid = p.productid AND pr.quantity = 1 AND variantid = 0
				  LEFT JOIN ".$this->sDBPrefix."products_lng_".$def_lang." AS prl ON prl.productid = p.productid";

        $query_page = "SELECT COUNT(p.productid) AS count_prods FROM ".$this->sDBPrefix."products AS p
                       LEFT JOIN ".$this->sDBPrefix."products_lng_".$def_lang." AS prl ON prl.productid = p.productid";
        if (!empty($query_where_parts)) {
            $query .= " WHERE ( " . implode(" OR ", $query_where_parts) . " )";
            $query_page .= " WHERE ( " . implode(" OR ", $query_where_parts) . " )";
        }
        $result_page = mysql_query($query_page);
        $row_page = mysql_fetch_assoc($result_page);
        $query .= " GROUP BY p.productid LIMIT ".($this->page - 1)*$this->show." , ".$this->show;

        $result = mysql_query($query);
        while($row = mysql_fetch_assoc($result)) {
            $row['price'] = $this->_price_format($row['price']);
            $row['list_price'] = $this->_price_format($row['list_price']);
            $products[] = $row;
        }
        return array("products_count" => $row_page['count_prods'], "products" => $products);
    }

    public function search_products_ordered() {
        $query_where_parts = array();
        $products = array();
        $this->params = explode("|", $this->params);
        foreach($this->params as $param) {
            switch ($param) {
                case 'pr_id':
                    $query_where_parts[] = " od.productid = '".$this->val."'";
                    break;
                case 'pr_sku':
                    $query_where_parts[] = " od.productcode = '".$this->val."'";
                    break;
                case 'pr_name':
                    $query_where_parts[] = " od.product LIKE '%".$this->val."%'";
                    break;
            }
        }
        if(!empty($this->products_from)) {
            $query_where_parts[] = " o.date >= '".strtotime($this->products_from." 00:00:00")."'";
        }
        if(!empty($this->products_to)) {
            $query_where_parts[] = " o.date <= '".strtotime($this->products_to." 23:59:59")."'";
        }
        $query = "SELECT
                    od.orderid AS main_id,
                    od.productcode AS sku,
                    od.product AS name,
                    od.price,
                    od.amount AS quantity,
                    o.status
				  FROM ".$this->sDBPrefix."order_details AS od
				    LEFT JOIN ".$this->sDBPrefix."orders AS o ON o.orderid = od.orderid";

        $query_page = "SELECT COUNT(od.orderid) AS count_prods, MAX(o.date) AS max_date, MIN(o.date) AS min_date FROM ".$this->sDBPrefix."order_details AS od
                        LEFT JOIN ".$this->sDBPrefix."orders AS o ON o.orderid = od.orderid";
        if (!empty($query_where_parts)) {
            $query .= " WHERE " . implode(" AND ", $query_where_parts);
            $query_page .= " WHERE " . implode(" AND ", $query_where_parts);
        }

        $result_page = mysql_query($query_page);
        $row_page = mysql_fetch_assoc($result_page);
        $query .= " ORDER BY od.orderid DESC LIMIT ".($this->page - 1)*$this->show." , ".$this->show;
        $result = mysql_query($query);

        while($row = mysql_fetch_assoc($result)) {
            $row['price'] = $this->_price_format($row['price']);
            $row['status'] = $this->_get_order_status($row['status']);
            $products[] = $row;
        }

        $max_date = '';
        $min_date = '';
        if($row_page['count_prods'] > 0) {
            $max_date = date("n/j/Y", $row_page['max_date']);
            $min_date = date("n/j/Y", $row_page['min_date']);
        }
        return array("products_count" => $row_page['count_prods'],
            "products" => $products,
            "max_date" => $max_date,
            "min_date" => $min_date);
    }

    public function get_products_info() {
        $default_attrs = $this->_get_default_attrs();
        $def_lang = $default_attrs['default_admin_language'];

        $images_table = 'images_t';
        $sqlResult = mysql_query("SHOW TABLES LIKE '%".$this->sDBPrefix.$images_table."%'");
        $result = mysql_num_rows($sqlResult);
        if($result <= 0) {
            $images_table = 'images_T';
        }

        $query = "SELECT
					p.productid AS id_product,
					prl.product AS name,
					p.productcode AS sku,
					pr.price,
					p.list_price,
					p.avail AS quantity,
					i.image_path,
					(SELECT SUM(amount) FROM ".$this->sDBPrefix."order_details WHERE productid = p.productid) AS total_ordered,
					(SELECT value FROM ".$this->sDBPrefix."languages
					    WHERE
                            name = IF(p.forsale = 'Y', 'lbl_avail_for_sale', IF(p.forsale = 'B', 'lbl_pconf_avail_for_sale_bundled', IF(p.forsale = 'H', 'lbl_hidden', 'lbl_disabled')))
                            AND code = '".$def_lang."'
                    ) AS forsale
				FROM ".$this->sDBPrefix."products AS p
				    LEFT JOIN ".$this->sDBPrefix."pricing AS pr ON pr.productid = p.productid AND pr.quantity = 1 AND variantid = 0
					LEFT JOIN ".$this->sDBPrefix.$images_table." AS i ON i.id = p.productid
					LEFT JOIN ".$this->sDBPrefix."products_lng_".$def_lang." AS prl ON prl.productid = p.productid
				WHERE p.productid = '".$this->product_id."' GROUP BY pr.productid";

        $res = mysql_query($query);
        $row = false;
        if(mysql_num_rows($res) > 0) {
            $row = mysql_fetch_assoc($res);
            $row['price'] = $this->_price_format($row['price']);
            $row['list_price'] = $this->_price_format($row['list_price']);
            $row['total_ordered'] = intval($row['total_ordered']);
            if(file_exists($row['image_path'])) {
                $row['id_image'] = $this->site_url . $row['image_path'];
            }
        }
        return $row;
    }

    public function get_products_descr() {
        $default_attrs = $this->_get_default_attrs();
        $query = "SELECT IF(fulldescr = '', descr, fulldescr) AS descr FROM ".$this->sDBPrefix."products_lng_".$default_attrs['default_admin_language']." WHERE productid = '".$this->product_id."'";
        $row = mysql_fetch_assoc(mysql_query($query));
        return $row;
    }

    private function _price_format($price, $no_format = false) {
        $default_attrs = $this->_get_default_attrs();

        if(!$no_format) {
            $price = number_format($price, 2, '.', '');
        }

        $curr_format = str_replace('x', $price, $default_attrs['currency_format']);

        $curr_format = str_replace('$', '<span>' . $default_attrs['currency_symbol'] . '</span>', $curr_format);
        return $curr_format;
    }

    private function _get_default_attrs() {
        $default_attrs = array();
        $query = "SELECT name, value FROM ".$this->sDBPrefix."config WHERE name IN ( 'date_format', 'time_format', 'company_name', 'currency_symbol', 'default_admin_language', 'currency_format' )";
        $result = mysql_query($query);
        while($row = mysql_fetch_assoc($result)) {
            $default_attrs[$row['name']] = $row['value'];
        }
        return $default_attrs;
    }

    private function _get_order_status($status) {
        switch ($status) {
            case ('I'):
                $ord_status = 'lbl_not_finished';
                break;
            case ('P'):
                $ord_status = 'lbl_processed';
                break;
            case ('B'):
                $ord_status = 'lbl_backordered';
                break;
            case ('D'):
                $ord_status = 'lbl_declined';
                break;
            case ('F'):
                $ord_status = 'lbl_failed';
                break;
            case ('Q'):
                $ord_status = 'lbl_queued';
                break;
            case ('C'):
                $ord_status = 'lbl_complete';
                break;
            default:
                $ord_status = '';
        }
        if($ord_status == '') {
            return $ord_status;
        }

        $default_attrs = $this->_get_default_attrs();

        $query = "SELECT value FROM ".$this->sDBPrefix."languages WHERE name = '".$ord_status."' AND code = '".$default_attrs['default_admin_language']."'";
        $result = mysql_query($query);
        if(mysql_num_rows($result) == 1) {
            $row = mysql_fetch_assoc($result);
            return $row['value'];
        }
        return '';
    }
}


class OpenCartSA extends MainSA {
    var $_currencies;
    public function get_store_title() {
        $default_attrs = $this->_get_default_attrs();
        return array('test' => 1, 'title' => $default_attrs["config_name"]);
    }

    public function get_store_stats() {
        $store_stats = array('count_orders' => "0", 'total_sales' => "0", 'count_customers' => "0", "last_order_id" => "0", "new_orders" => "0");
        $default_attrs = $this->_get_default_attrs();
        /*
        if(!empty($this->date_from)) {
            $query_where_parts[] = " UNIX_TIMESTAMP(date_added) >= '".strtotime($this->date_from." 00:00:00")."'";
        }
        if(!empty($this->date_to)) {
            $query_where_parts[] = " UNIX_TIMESTAMP(date_added) <= '".strtotime($this->date_to." 23:59:59")."'";
        }
        */
        $today = date("Y-m-d", time());
        $query_where_parts[] = " UNIX_TIMESTAMP(date_added) >= '".strtotime($today . " 00:00:00")."'";
        $query_where_parts[] = " UNIX_TIMESTAMP(date_added) <= '".strtotime($today . " 23:59:59")."'";
        if(!empty($this->statuses)) {
            $statuses = explode("|", $this->statuses);
            if(!empty($statuses)) {
                $stat = array();
                foreach($statuses as $status) {
                    if($status != "") {
                        $stat[] = $status;
                    }
                }
                $parse_statuses = implode("','", $stat);
                $query_where_parts[] = " order_status_id IN ('".$parse_statuses."')";
            }
        }

        $query = "SELECT COUNT(order_id) AS count_orders, SUM(total) AS total_sales FROM `".$this->sDBPrefix."order`";
        if(!empty($query_where_parts)) {
            $query .= " WHERE " . implode(" AND ", $query_where_parts);
        }

        $result = mysql_query($query);
        if(mysql_num_rows($result) > 0) {
            $store_stats = mysql_fetch_assoc($result);
            $store_stats['total_sales'] = $this->_price_format($this->bd_nice_number($store_stats['total_sales']), $default_attrs['config_currency'], false);
        }

        if($this->last_order_id != "") {
            $query_max = "SELECT COUNT(order_id) AS count_orders, MAX(order_id) AS last_order_id
                          FROM `".$this->sDBPrefix."order`
                          WHERE order_id > ".$this->last_order_id;
            if(!empty($query_where_parts)) {
                $query_max .= " AND " . implode(" AND ", $query_where_parts);
            }
            $result_max = mysql_query($query_max);
            if(mysql_num_rows($result_max) > 0) {
                $row_max = mysql_fetch_assoc($result_max);
                $store_stats['last_order_id'] = intval($this->last_order_id);
                if(intval($row_max['last_order_id']) > intval($this->last_order_id)) {
                    $store_stats['last_order_id'] = intval($row_max['last_order_id']);
                }
                $store_stats['new_orders'] = intval($row_max['count_orders']);
            }
        }

        unset($query_where_parts);
        /*
        if(!empty($this->date_from)) {
            $query_where_parts[] = " UNIX_TIMESTAMP(date_added) >= '".strtotime($this->date_from." 00:00:00")."'";
        }
        if(!empty($this->date_to)) {
            $query_where_parts[] = " UNIX_TIMESTAMP(date_added) <= '".strtotime($this->date_to." 23:59:59")."'";
        }
        */
        $query_where_parts[] = " UNIX_TIMESTAMP(date_added) >= '".strtotime($today . " 00:00:00")."'";
        $query_where_parts[] = " UNIX_TIMESTAMP(date_added) <= '".strtotime($today . " 23:59:59")."'";
        $query = "SELECT COUNT(customer_id) AS count_customers FROM ".$this->sDBPrefix."customer";
        if(!empty($query_where_parts)) {
            $query .= " WHERE " . implode(" AND ", $query_where_parts);
        }

        $result = mysql_query($query);
        if(mysql_num_rows($result) > 0) {
            $row = mysql_fetch_assoc($result);
            $store_stats = array_merge($store_stats, $row);
        }

        $this->graph_to = $today;
        $this->graph_from = date("Y-m-d", mktime(0, 0, 0, date("m")  , date("d")-7, date("Y")));
        $data_graphs = $this->get_data_graphs();

        $store_stats['count_orders'] = $this->bd_nice_number($store_stats['count_orders'], true);
        $store_stats['count_customers'] = $this->bd_nice_number($store_stats['count_customers'], true);

        $result = array_merge($store_stats, array('data_graphs' => $data_graphs));
        return $result;
    }

    public function get_data_graphs() {
        $startDate = strtotime($this->graph_from." 00:00:00");
        $endDate = strtotime($this->graph_to." 23:59:59");
        $date = $startDate;
        $d = 0;
        $average = array('avg_sum_orders' => 0, 'avg_orders' => 0, 'avg_customers' => 0, 'avg_cust_order' => '0.00');
        $orders = array();
        $customers = array();
        while ($date <= $endDate) {
            $d++;
            //Orders
            $query = "SELECT COUNT(order_id) AS tot_orders, UNIX_TIMESTAMP(date_added) AS date_add, SUM(total) AS value
                      FROM `".$this->sDBPrefix."order`
                      WHERE UNIX_TIMESTAMP(date_added) >= '".$date."' AND UNIX_TIMESTAMP(date_added) < '".strtotime('+1 day', $date)."'";

            if(!empty($this->statuses)) {
                $statuses = explode("|", $this->statuses);
                if(!empty($statuses)) {
                    $stat = array();
                    foreach($statuses as $status) {
                        if($status != "") {
                            $stat[] = $status;
                        }
                    }
                    $parse_statuses = implode("','", $stat);
                    $query .= " AND order_status_id IN ('".$parse_statuses."')";
                }
            }
            $query .= " GROUP BY DATE(date_added) ORDER BY date_added";

            $result = mysql_query($query);
            if(mysql_num_rows($result) > 0) {
                while($row = mysql_fetch_assoc($result)) {
                    $orders[] = array($row['date_add']*1000, $row['value']);

                    $average['tot_orders'] += $row['tot_orders'];
                    $average['sum_orders'] += $row['value'];
                }
            } else {
                $orders[] = array($date*1000, 0);
            }

            //Customers
            $query = "SELECT COUNT(customer_id) AS tot_customers, UNIX_TIMESTAMP(date_added) AS date_add FROM ".$this->sDBPrefix."customer
				  WHERE UNIX_TIMESTAMP(date_added) >= '".$date."' AND UNIX_TIMESTAMP(date_added) < '".strtotime('+1 day', $date)."'
				  GROUP BY DATE(date_added) ORDER BY date_added";

            $result = mysql_query($query);
            if(mysql_num_rows($result) > 0) {
                while($row = mysql_fetch_assoc($result)) {
                    $customers[] = array($row['date_add']*1000, $row['tot_customers']);
                    $average['tot_customers'] += $row['tot_customers'];
                }
            } else {
                $customers[] = array($date*1000, 0);
            }
            $date = strtotime('+1 day', $date);
        }

        $default_attrs = $this->_get_default_attrs();

        $average['avg_sum_orders'] = $this->_price_format($average['sum_orders']/$d, $default_attrs['config_currency']);
        $average['avg_orders'] = number_format($average['tot_orders']/$d, 1, '.', '');
        $average['avg_customers'] = number_format($average['tot_customers']/$d, 1, '.', '');

        if($average['tot_customers'] > 0) {
            $average['avg_cust_order'] = $this->_price_format($average['sum_orders']/$average['tot_customers'], $default_attrs['config_currency']);
        }
        $average['sum_orders'] = $this->_price_format($average['sum_orders'], $default_attrs['config_currency']);
        $average['tot_customers'] = number_format($average['tot_customers'], 1, '.', '');
        $average['tot_orders'] = number_format($average['tot_orders'], 1, '.', '');

        return array('orders' => $orders, 'customers' => $customers, 'currency_sign' => $default_attrs['currency_symbol'], 'average' => $average, 'currency_style' => $default_attrs['currency_format']);
    }

    public function get_orders() {
        $orders = array();
        $default_attrs = $this->_get_default_attrs();
        if(!empty($this->search_order_id)) {
            $query_where_parts[] = " o.order_id = '".$this->search_order_id."'";
        }
        if(!empty($this->orders_from)) {
            $query_where_parts[] = " UNIX_TIMESTAMP(o.date_added) >= '".strtotime($this->orders_from." 00:00:00")."'";
        }
        if(!empty($this->orders_to)) {
            $query_where_parts[] = " UNIX_TIMESTAMP(o.date_added) <= '".strtotime($this->orders_to." 23:59:59")."'";
        }
        if(!empty($this->statuses)) {
            $statuses = explode("|", $this->statuses);
            if(!empty($statuses)) {
                $stat = array();
                foreach($statuses as $status) {
                    if($status != "") {
                        $stat[] = $status;
                    }
                }
                $this->statuses = implode("','", $stat);
                $query_where_parts[] = " o.order_status_id IN ('".$this->statuses."')";
            }
        }
        $query = "SELECT
                    o.order_id AS id_order,
                    o.date_added AS date_add,
                    o.total AS order_total,
                    o.currency_code,
					CONCAT(o.firstname, ' ', o.lastname) AS customer,
					o.order_status_id,
					os.name AS ord_status,
					(SELECT SUM(quantity) FROM ".$this->sDBPrefix."order_product WHERE order_id = o.order_id) AS qty_ordered
				  FROM `".$this->sDBPrefix."order` AS o
				  LEFT JOIN ".$this->sDBPrefix."order_status AS os ON os.order_status_id = o.order_status_id AND os.language_id = '".$default_attrs['language_id']."'";

        $query_page = "SELECT SUM(o.total) AS orders_total, COUNT(o.order_id) AS count_ords, MAX(o.date_added) AS max_date, MIN(o.date_added) AS min_date
				  FROM `".$this->sDBPrefix."order` AS o";

        if (!empty($query_where_parts)) {
            $query .= " WHERE " . implode(" AND ", $query_where_parts);
            $query_page .= " WHERE " . implode(" AND ", $query_where_parts);
        }

        $result_page = mysql_query($query_page);
        $row_page = mysql_fetch_assoc($result_page);
        $query .= " ORDER BY o.order_id DESC LIMIT ".($this->page - 1)*$this->show." , ".$this->show;

        $result = mysql_query($query);
        while($row = mysql_fetch_assoc($result)) {
            if($row['order_status_id'] == 0) {
                $row['ord_status'] = $default_attrs['text_missing'];
            }
            $row['total_paid'] = $this->_price_format($row['order_total'], $row['currency_code']);
            $orders[] = $row;
        }

        $max_date = '';
        $min_date = '';
        if($row_page['count_ords'] > 0) {
            $max_date = date("n/j/Y", strtotime($row_page['max_date']));
            $min_date = date("n/j/Y", strtotime($row_page['min_date']));
        }

        $orders_status = null;
        if(isset($this->get_statuses) && $this->get_statuses == 1) {
            $orders_status = $this->get_orders_statuses();
        }

        $row_page['orders_total'] = $this->_price_format($row_page['orders_total'], $default_attrs['config_currency']);
        return array("orders" => $orders,
            "orders_count" => intval($row_page['count_ords']),
            "orders_total" => $row_page['orders_total'],
            "max_date" => $max_date,
            "min_date" => $min_date,
            "orders_status" => $orders_status);
    }

    public function get_orders_statuses() {
        $default_attrs = $this->_get_default_attrs();
        $orders_status = array();
        $query = "SELECT os.order_status_id, os.name FROM ".$this->sDBPrefix."order_status AS os
             WHERE os.language_id = '".$default_attrs['language_id']."'";

        $result_status = mysql_query($query);
        $orders_status[] = array('st_id' => 0, 'st_name' => $default_attrs['text_missing']);
        while($row = mysql_fetch_assoc($result_status)) {
            $orders_status[] = array('st_id' => $row['order_status_id'], 'st_name' => $row['name']);
        }
        return $orders_status;
    }

    public function get_orders_info() {
        $order_products = array();
        $order_info = array();
        $default_attrs = $this->_get_default_attrs();

        $query = "SELECT
            o.order_id AS id_order,
            o.customer_id AS id_customer,
            o.email,
            o.telephone,
            o.fax,
            CONCAT(o.firstname, ' ', o.lastname) AS customer,
            o.date_added,
            o.order_status_id,
            os.name AS status,
            o.total,
            o.currency_code,
            o.payment_method AS p_method,
            CONCAT(o.payment_firstname, ' ', o.payment_lastname) AS p_name,
            o.payment_company AS p_company,
            o.payment_address_1 AS p_address_1,
            o.payment_address_2 AS p_address_2,
            o.payment_city AS p_city,
            o.payment_postcode AS p_postcode,
            o.payment_country AS p_country,
            o.payment_zone AS p_zone,
            o.shipping_method AS s_method,
            CONCAT(o.shipping_firstname, ' ', o.shipping_lastname) AS s_name,
            o.shipping_company AS s_company,
            o.shipping_address_1 AS s_address_1,
            o.shipping_address_2 AS s_address_2,
            o.shipping_city AS s_city,
            o.shipping_postcode AS s_postcode,
            o.shipping_country AS s_country,
            o.shipping_zone as s_zone
            FROM `".$this->sDBPrefix."order` AS o
            LEFT JOIN ".$this->sDBPrefix."order_status AS os ON os.order_status_id = o.order_status_id AND os.language_id = '".$default_attrs['language_id']."'
            WHERE o.order_id = '".$this->order_id."'";

        $result = mysql_query($query);
        if(mysql_num_rows($result) > 0) {
            $order_info = mysql_fetch_assoc($result);
            $order_info['total'] = $this->_price_format($order_info['total'], $order_info['currency_code']);
            if($order_info['order_status_id'] == 0) {
                $order_info['status'] = $default_attrs['text_missing'];
            }
        }

        $order_total = array();
        $query_total = "SELECT title, text FROM ".$this->sDBPrefix."order_total WHERE order_id = '".$this->order_id."' AND code <> 'total' ORDER BY sort_order";
        $result_total = mysql_query($query_total);
        while($row_total = mysql_fetch_assoc($result_total)) {
            $order_total[] = array('title' => $row_total['title'], 'value' => $row_total['text']);
        }

        $query_page = "SELECT COUNT(order_id) AS count_prods FROM ".$this->sDBPrefix."order_product WHERE order_id = '".$this->order_id."'";
        $result_page = mysql_query($query_page);
        $row_page = mysql_fetch_assoc($result_page);

        //orders products
        $query = "SELECT order_id AS id_order, product_id, name, quantity, price, model AS sku FROM ".$this->sDBPrefix."order_product WHERE order_id = '".$this->order_id."'";
        $query .= " LIMIT ".($this->page - 1)*$this->show." , ".$this->show;
        $result = mysql_query($query);
        while($row = mysql_fetch_assoc($result)) {
            $row['product_price'] = $this->_price_format($row['price'], $order_info['currency_code']);
            $row['product_quantity'] = intval($row['quantity']);
            $row['product_name'] = $row['name'];
            $order_products[] = $row;
        }
        $order_full_info = array("order_info" => $order_info, "order_products" => $order_products, "o_products_count" => $row_page['count_prods'], "order_total" => $order_total);
        return $order_full_info;
    }

    public function get_customers() {
        $customers = array();
        if(!empty($this->customers_from)) {
            $query_where_parts[] = " UNIX_TIMESTAMP(c.date_added) >= '".strtotime($this->customers_from." 00:00:00")."'";
        }
        if(!empty($this->customers_to)) {
            $query_where_parts[] = " UNIX_TIMESTAMP(c.date_added) <= '".strtotime($this->customers_to." 23:59:59")."'";
        }
        if(!empty($this->search_val)) {
            $query_where_parts[] = " (c.email LIKE '%".$this->search_val."%' OR c.firstname LIKE '%".$this->search_val."%' OR c.lastname LIKE '%".$this->search_val."%')";
        }
        if(!empty($this->cust_with_orders)) {
            $query_where_parts[] = " tot.total_orders > 0";
        }

        $query = "SELECT
					c.customer_id AS id_customer,
					c.firstname,
					c.lastname,
					c.date_added AS date_add,
					c.email,
					IFNULL(tot.total_orders, 0) AS total_orders
				  FROM ".$this->sDBPrefix."customer AS c
                  LEFT OUTER JOIN (SELECT COUNT(order_id) AS total_orders, customer_id FROM `".$this->sDBPrefix."order` GROUP BY customer_id) AS tot ON tot.customer_id = c.customer_id";

        $query_page = "SELECT COUNT(c.customer_id) AS count_custs, MAX(c.date_added) AS max_date, MIN(c.date_added) AS min_date
						FROM ".$this->sDBPrefix."customer AS c
						LEFT OUTER JOIN (SELECT COUNT(order_id) AS total_orders, customer_id FROM `".$this->sDBPrefix."order` GROUP BY customer_id) AS tot ON tot.customer_id = c.customer_id";
        if (!empty($query_where_parts)) {
            $query .= " WHERE " . implode(" AND ", $query_where_parts);
            $query_page .= " WHERE " . implode(" AND ", $query_where_parts);
        }
        $query .= " LIMIT ".($this->page - 1)*$this->show." , ".$this->show;
        $result_page = mysql_query($query_page);
        $row_page = mysql_fetch_assoc($result_page);

        $result = mysql_query($query);
        while($row = mysql_fetch_assoc($result)) {
            $row['total_orders'] = intval($row['total_orders']);
            $customers[] = $row;
        }

        $max_date = '';
        $min_date = '';
        if($row_page['count_custs'] > 0) {
            $max_date = date("n/j/Y", strtotime($row_page['max_date']));
            $min_date = date("n/j/Y", strtotime($row_page['min_date']));
        }
        return array("customers_count" => $row_page['count_custs'],
            "customers" => $customers,
            "max_date" => $max_date,
            "min_date" => $min_date);
    }

    public function get_customers_info() {
        $customer_orders = array();
        $default_attrs = $this->_get_default_attrs();
        $query = "SELECT
					c.customer_id,
					c.email,
					c.date_added,
					CONCAT(c.firstname, ' ', c.lastname) AS name,
					c.telephone AS phone,
					c.fax,
					c.date_added AS date_add,
                    a.company,
                    a.address_1,
                    a.address_2,
                    a.city,
                    a.postcode,
                    cn.name AS country,
                    z.name AS zone
				  FROM ".$this->sDBPrefix."customer AS c
				  	LEFT JOIN ".$this->sDBPrefix."address AS a ON a.address_id = c.address_id
				  	LEFT JOIN ".$this->sDBPrefix."country AS cn ON cn.country_id = a.country_id
				  	LEFT JOIN ".$this->sDBPrefix."zone AS z ON z.zone_id = a.zone_id
				  WHERE c.customer_id = '".$this->user_id."'";

        $result = mysql_query($query);
        $user_info = mysql_fetch_assoc($result);
        $user_info['address'] = $this->split_values($user_info, array('address_1','address_2','city','zone','country'));

        $query = "SELECT o.order_id AS id_order, o.total, o.order_status_id, os.name AS ord_status, o.date_added as date_add, (SELECT SUM(quantity) FROM ".$this->sDBPrefix."order_product WHERE order_id = o.order_id) AS pr_qty
				  FROM `".$this->sDBPrefix."order` AS o
				  LEFT JOIN ".$this->sDBPrefix."order_status AS os ON os.order_status_id = o.order_status_id AND os.language_id = '".$default_attrs['language_id']."'";
        $query_page = "SELECT COUNT(order_id) AS count_ords, SUM(total) AS sum_ords FROM `".$this->sDBPrefix."order` WHERE customer_id = '".$this->user_id."'";
        $result_page = mysql_query($query_page);
        $row_page = mysql_fetch_assoc($result_page);
        $query .= " WHERE o.customer_id = '".$this->user_id."' LIMIT ".($this->page - 1)*$this->show." , ".$this->show;

        $result = mysql_query($query);
        while($row = mysql_fetch_assoc($result)) {
            $row['total_paid'] = $this->_price_format($row['total'], $default_attrs['config_currency']);
            if($row['order_status_id'] == 0) {
                $row['ord_status'] = $default_attrs['text_missing'];
            }
            $customer_orders[] = $row;
        }
        $row_page['sum_ords'] = $this->_price_format($row_page['sum_ords'], $default_attrs['config_currency']);
        $customer_info = array("user_info" => $user_info, "customer_orders" => $customer_orders, "c_orders_count" => intval($row_page['count_ords']), "sum_ords" => $row_page['sum_ords']);
        return $customer_info;
    }

    public function search_products() {
        $query_where_parts = array();
        $products = array();
        $default_attrs = $this->_get_default_attrs();

        $this->params = explode("|", $this->params);
        foreach($this->params as $param) {
            switch ($param) {
                case 'pr_id':
                    $query_where_parts[] = " p.product_id = '".$this->val."'";
                    break;
                case 'pr_sku':
                    $query_where_parts[] = " p.model = '".$this->val."'";
                    break;
                case 'pr_name':
                    $query_where_parts[] = " pd.name LIKE '%".$this->val."%'";
                    break;
                case 'pr_desc':
                case 'pr_short_desc':
                    $query_where_parts[] = " pd.description LIKE '%".$this->val."%'";
                    break;
            }
        }
        $query = "SELECT p.product_id AS main_id, p.model AS sku, pd.name, p.price, ps.price as special_price, p.quantity
				  FROM ".$this->sDBPrefix."product AS p
				  LEFT JOIN ".$this->sDBPrefix."product_description AS pd ON pd.product_id = p.product_id AND pd.language_id = '".$default_attrs['language_id']."'
				  LEFT OUTER JOIN ".$this->sDBPrefix."product_special AS ps ON ps.product_id = p.product_id AND ps.priority = 1";

        $query_page = "SELECT COUNT(p.product_id) AS count_prods FROM ".$this->sDBPrefix."product AS p
                       LEFT JOIN ".$this->sDBPrefix."product_description AS pd ON pd.product_id = p.product_id AND pd.language_id = '".$default_attrs['language_id']."'";
        if (!empty($query_where_parts)) {
            $query .= " WHERE ( " . implode(" OR ", $query_where_parts) . " )";
            $query_page .= " WHERE ( " . implode(" OR ", $query_where_parts) . " )";
        }
        $result_page = mysql_query($query_page);
        $row_page = mysql_fetch_assoc($result_page);
        $query .= " GROUP BY p.product_id LIMIT ".($this->page - 1)*$this->show." , ".$this->show;

        $result = mysql_query($query);
        while($row = mysql_fetch_assoc($result)) {
            $row['price'] = $this->_price_format($row['price'], $default_attrs['config_currency']);
            if($row['special_price']) {
                $row['special_price'] = $this->_price_format($row['special_price'], $default_attrs['config_currency']);
            }
            $products[] = $row;
        }
        return array("products_count" => $row_page['count_prods'], "products" => $products);
    }

    public function search_products_ordered() {
        $default_attrs = $this->_get_default_attrs();
        $query_where_parts = array();
        $products = array();
        $this->params = explode("|", $this->params);
        foreach($this->params as $param) {
            switch ($param) {
                case 'pr_id':
                    $query_where_parts[] = " op.product_id = '".$this->val."'";
                    break;
                case 'pr_sku':
                    $query_where_parts[] = " op.model = '".$this->val."'";
                    break;
                case 'pr_name':
                    $query_where_parts[] = " op.name LIKE '%".$this->val."%'";
                    break;
            }
        }
        if(!empty($this->products_from)) {
            $query_where_parts[] = " UNIX_TIMESTAMP(o.date_added) >= '".strtotime($this->products_from." 00:00:00")."'";
        }
        if(!empty($this->products_to)) {
            $query_where_parts[] = " UNIX_TIMESTAMP(o.date_added) <= '".strtotime($this->products_to." 23:59:59")."'";
        }
        $query = "SELECT
                    op.order_id AS main_id,
                    op.model AS sku,
                    op.name,
                    op.price,
                    op.quantity,
                    o.order_status_id,
                    os.name AS status
				  FROM ".$this->sDBPrefix."order_product AS op
				    LEFT JOIN `".$this->sDBPrefix."order` AS o ON o.order_id = op.order_id
				    LEFT JOIN ".$this->sDBPrefix."order_status AS os ON os.order_status_id = o.order_status_id AND os.language_id = '".$default_attrs['language_id']."'";

        $query_page = "SELECT COUNT(op.product_id) AS count_prods, MAX(o.date_added) AS max_date, MIN(o.date_added) AS min_date FROM ".$this->sDBPrefix."order_product AS op
                        LEFT JOIN `".$this->sDBPrefix."order` AS o ON o.order_id = op.order_id";
        if (!empty($query_where_parts)) {
            $query .= " WHERE " . implode(" AND ", $query_where_parts);
            $query_page .= " WHERE " . implode(" AND ", $query_where_parts);
        }

        $result_page = mysql_query($query_page);
        $row_page = mysql_fetch_assoc($result_page);
        $query .= " ORDER BY op.order_id DESC LIMIT ".($this->page - 1)*$this->show." , ".$this->show;
        $result = mysql_query($query);

        while($row = mysql_fetch_assoc($result)) {
            $row['price'] = $this->_price_format($row['price'], $default_attrs['config_currency']);
            if($row['order_status_id'] == 0) {
                $row['status'] = $default_attrs['text_missing'];
            }
            $products[] = $row;
        }

        $max_date = '';
        $min_date = '';
        if($row_page['count_prods'] > 0) {
            $max_date = date("n/j/Y", strtotime($row_page['max_date']));
            $min_date = date("n/j/Y", strtotime($row_page['min_date']));
        }
        return array("products_count" => $row_page['count_prods'],
            "products" => $products,
            "max_date" => $max_date,
            "min_date" => $min_date);
    }

    public function get_products_info() {
        $default_attrs = $this->_get_default_attrs();
        $query = "SELECT
					p.product_id AS id_product,
					pd.name,
					p.model AS sku,
					p.price,
					ps.price AS special_price,
					p.quantity,
					p.image,
					(SELECT SUM(quantity) FROM ".$this->sDBPrefix."order_product WHERE product_id = p.product_id) AS total_ordered,
					(SELECT image FROM ".$this->sDBPrefix."product_image WHERE product_id = p.product_id AND image != '' ORDER BY sort_order LIMIT 1) AS product_img,
					ss.name AS forsale
				FROM ".$this->sDBPrefix."product AS p
				    LEFT JOIN ".$this->sDBPrefix."product_description AS pd ON pd.product_id = p.product_id AND pd.language_id = '".$default_attrs['language_id']."'
				    LEFT OUTER JOIN ".$this->sDBPrefix."product_special AS ps ON ps.product_id = p.product_id AND ps.priority = 1
					LEFT JOIN ".$this->sDBPrefix."stock_status AS ss ON ss.stock_status_id = p.stock_status_id AND ss.language_id = '".$default_attrs['language_id']."'
				WHERE p.product_id = '".$this->product_id."' GROUP BY p.product_id";

        $res = mysql_query($query);
        $row = false;
        if(mysql_num_rows($res) > 0) {
            $row = mysql_fetch_assoc($res);

            $row['price'] = $this->_price_format($row['price'], $default_attrs['config_currency']);
            if($row['special_price']) {
                $row['special_price'] = $this->_price_format($row['special_price'], $default_attrs['config_currency']);
            }
            $row['total_ordered'] = intval($row['total_ordered']);

            if(file_exists('image/' . $row['image']) && is_file('image/' . $row['image'])) {
                $row['id_image'] = $this->site_url . 'image/' . $row['image'];

            } elseif(file_exists('image/' . $row['product_img']) && is_file('image/' . $row['product_img'])) {
                $row['id_image'] = $this->site_url . 'image/' . $row['product_img'];
            }
        }
        return $row;
    }

    public function get_products_descr() {
        $default_attrs = $this->_get_default_attrs();
        $query = "SELECT description AS descr FROM ".$this->sDBPrefix."product_description WHERE product_id = '".$this->product_id."' AND language_id = '".$default_attrs['language_id']."'";
        $row = mysql_fetch_assoc(mysql_query($query));
        return $row;
    }

    private function _price_format($price, $currency, $number_format = true) {
        if(!$this->_currencies) {
            $this->_currencies = $this->_get_all_currencies();
        }

        $curr = $this->_currencies[$currency];
        if($number_format) {
            $price = number_format($price, intval($curr['decimal_place']), '.', '');
        }

        $price = ($curr['symbol_left'] != '' ? '<span>' . $curr['symbol_left'] . '</span>' : '') . $price . ($curr['symbol_right'] != '' ? '<span>' . $curr['symbol_right'] . '</span>' : '');
        return $price;
    }

    private function _get_default_attrs() {
        $default_attrs = array();
        $query = "SELECT `key`, `value` FROM `".$this->sDBPrefix."setting` WHERE `key` IN ( 'config_name', 'config_admin_language', 'config_currency' ) AND store_id = 0";

        $result = mysql_query($query);
        while($row = mysql_fetch_assoc($result)) {
            $default_attrs[$row['key']] = $row['value'];
        }

        $q_lang = "SELECT `language_id`, `directory` FROM `".$this->sDBPrefix."language` WHERE `code` = '".$default_attrs['config_admin_language']."'";
        $row_lang = mysql_fetch_assoc(mysql_query($q_lang));

        $default_attrs['language_id'] = $row_lang['language_id'];

        $default_attrs['text_missing'] = 'Missing Orders';
        if(file_exists('./admin/language/'.$row_lang['directory'].'/sale/order.php')) {
            include('./admin/language/'.$row_lang['directory'].'/sale/order.php');
            if(isset($_['text_missing'])) {
                $default_attrs['text_missing'] = $_['text_missing'];
            }
        }

        return $default_attrs;
    }

    private function _get_all_currencies() {
        $currencies = array();
        $query = "SELECT `currency_id`, `code`, `symbol_left`, `symbol_right`, `decimal_place` FROM `".$this->sDBPrefix."currency`";

        $result = mysql_query($query);
        while($row = mysql_fetch_assoc($result)) {
            $currencies[$row['code']] = $row;
        }
        return $currencies;
    }
}
?>