<?php

(defined('BASEPATH')) OR exit('No direct script access allowed');

/**
 * Image CMS
 * Module Frame
 */
class Payment_method_interkassa extends MY_Controller
{
    const ikUrlSCI = 'https://sci.interkassa.com/';
    const ikUrlAPI = 'https://api.interkassa.com/v1/';

    public $paymentMethod;

    public $moduleName = 'payment_method_interkassa';

    public function __construct() {
        parent::__construct();
        $lang = new MY_Lang();
        $lang->load('payment_method_interkassa');
    }

    public function index() {
        lang('interkassa', 'payment_method_interkassa');

        if(!empty($_POST)) {
            $this -> getPaymentSettings();
            $request = $_POST;
            if (isset($request['ik_act']) && $request['ik_act'] == 'process'){
                $request['ik_sign'] = self::IkSignFormation($request, $this -> secret_key);
                $data = self::getAnswerFromAPI($request);
            }
            else
                $data = self::IkSignFormation($request, $this -> secret_key);
            echo $data;
            exit;
        }
    }

    /**
     * Вытягивает данные способа оплаты
     * @param str $key
     * @return array
     */
    private function getPaymentSettings($key) {
        $ci = &get_instance();
        $id = '';
        if(!$key) {
            $id = $ci->db->where('payment_system_name', $this->moduleName)
                ->get('shop_payment_methods')->row()->value;

            $key = $id . '_' . $this->moduleName;
        }

        $value = $ci->db->where('name', $key)->get('shop_settings');
        if ($value) {
            $value = $value->row()->value;
        } else {
            show_error($ci->db->_error_message());
        }

        $settings = unserialize($value);

        $this -> id_cashbox = $settings['id_cashbox'];
        $this -> secret_key = $settings['secret_key'];
        $this -> test_key = $settings['test_key'];
        $this -> api_id = $settings['api_id'];
        $this -> api_key = $settings['api_key'];
        $this -> test_mode = $settings['test_mode']? true : false;
        $this -> enableAPI = $settings['enableAPI']? true : false;

        return $settings;
    }

    /**
     * Вызывается при редактировании способов оплатыв админке
     * @param integer $id ид метода оплаты
     * @param string $payName название payment_method_liqpay
     * @return string
     */
    public function getAdminForm($id, $payName = null) {
        if (!$this->dx_auth->is_admin()) {
            redirect('/');
            exit;
        }

        $nameMethod = $payName ? $payName : $this->paymentMethod->getPaymentSystemName();
        $key = $id . '_' . $nameMethod;
        $data = $this->getPaymentSettings($key);

        $codeTpl = \CMSFactory\assetManager::create()
                ->setData('data', $data)
                ->fetchTemplate('adminForm');

        return $codeTpl;
    }

    //Конвертация в другую валюту

    public function convert($price, $currencyId) {
        if ($currencyId == \Currency\Currency::create()->getMainCurrency()->getId()) {
            $return['price'] = $price;
            $return['code'] = \Currency\Currency::create()->getMainCurrency()->getCode();
            return $return;
        } else {
            $return['price'] = \Currency\Currency::create()->convert($price, $currencyId);
            $return['code'] = \Currency\Currency::create()->getCodeById($currencyId);
            return $return;
        }
    }

    //Наценка

    public function markup($price, $percent) {
        $price = (float) $price;
        $percent = (float) $percent;
        $factor = $percent / 100;
        $residue = $price * $factor;
        return $price + $residue;
    }

    /**
     * Формирование кнопки оплаты
     * @param obj $param Данные о заказе
     * @return str
     */
    public function getForm($param) {

        $payment_method_id = $param->getPaymentMethod();
        $key = $payment_method_id . '_' . $this->moduleName;
        $paySettings = $this->getPaymentSettings($key);
        $image_path = $this->getImgPath();

        $descr = 'Order Id: ' . $param->id;
        $price = $param->getDeliveryPrice() ? ($param->getTotalPrice() + $param->getDeliveryPrice()) : $param->getTotalPrice();
        $code = \Currency\Currency::create()->getMainCurrency()->getCode();

        if ($paySettings['merchant_currency']) {
            $arrPriceCode = $this->convert($price, $paySettings['merchant_currency']);
            $price = $arrPriceCode['price'];
            $code = $arrPriceCode['code'];
        }

        if ($paySettings['merchant_markup']) {
            $price = $this->markup($price, $paySettings['merchant_markup']);
        }

        $data = array(
            'ik_co_id' => $this -> id_cashbox,
            'ik_am'    => $price,
            'ik_cur'   => $code,
            'ik_desc'  => $descr,
            'ik_pm_no' => $param->id,
            'ik_ia_u'  => site_url('/payment_method_interkassa/callback'),
            'ik_suc_u' => site_url() . 'shop/order/view/' . $param->getKey(),
            'ik_pnd_u' => site_url() . 'shop/order/view/' . $param->getKey(),
            'ik_fal_u' => site_url() . 'shop/order/view/' . $param->getKey(),
        );

        if($this -> test_mode)
            $data['ik_pw_via'] = 'test_interkassa_test_xts';

        $data['ik_sign'] = self::IkSignFormation($data, $this -> secret_key);

        if($this -> enableAPI)
            $payment_systems = $this->getIkPaymentSystems();
        else
            $payment_systems = '';

        CMSFactory\assetManager::create()
            ->registerStyle('interkassa')
            ->registerScript('bootstrap.min')
            ->registerScript('interkassa');

        $codeTpl = \CMSFactory\assetManager::create()
            ->setData('data', $data)
            ->setData('image_path', $image_path)
            ->setData('payment_systems', $payment_systems)
            ->setData('ajax_url', "/{$this->moduleName}")
            ->fetchTemplate('form');

        return $codeTpl;
    }

    private function getImgPath()
    {
        return '/' . getModulePath($this->moduleName) . 'assets/images/';
    }

    private static function IkSignFormation($data, $secret_key)
    {
        $dataSet = array();
        foreach ($data as $key => $value) {
            if (preg_match('/ik_/i', $key) && $key != 'ik_sign')
                $dataSet[$key] = $value;
        }
        ksort($dataSet, SORT_STRING);
        array_push($dataSet, $secret_key);
        $arg = implode(':', $dataSet);
        $ik_sign = base64_encode(md5($arg, true));
        return $ik_sign;
    }
    public static function getAnswerFromAPI($data)
    {
        $ch = curl_init(self::ikUrlSCI);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        return $result;
    }
    public function getIkPaymentSystems()
    {
        $username = $this -> api_id;
        $password = $this -> api_key;
        $remote_url = self::ikUrlAPI . 'paysystem-input-payway?checkoutId=' . $this -> id_cashbox;

        $businessAcc = $this->getIkBusinessAcc($username, $password);

        $ikHeaders = [];
        $ikHeaders[] = "Authorization: Basic " . base64_encode("$username:$password");
        if(!empty($businessAcc)) {
            $ikHeaders[] = "Ik-Api-Account-Id: " . $businessAcc;
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $remote_url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $ikHeaders);
        $response = curl_exec($ch);

		if(empty($response))
			return '<strong style="color:red;">Error!!! System response empty!</strong>';

        $json_data = json_decode($response);
        if ($json_data->status != 'error') {
            $payment_systems = array();
			if(!empty($json_data->data)){
				foreach ($json_data->data as $ps => $info) {
					$payment_system = $info->ser;
					if (!array_key_exists($payment_system, $payment_systems)) {
						$payment_systems[$payment_system] = array();
						foreach ($info->name as $name) {
							if ($name->l == 'en') {
								$payment_systems[$payment_system]['title'] = ucfirst($name->v);
							}
							$payment_systems[$payment_system]['name'][$name->l] = $name->v;
						}
					}
					$payment_systems[$payment_system]['currency'][strtoupper($info->curAls)] = $info->als;
				}
			}

            return !empty($payment_systems)? $payment_systems : '<strong style="color:red;">API connection error or system response empty!</strong>';
        } else {
            if(!empty($json_data->message))
				return '<strong style="color:red;">API connection error!<br>' . $json_data->message . '</strong>';
			else
				return '<strong style="color:red;">API connection error or system response empty!</strong>';
		}
    }

    public function getIkBusinessAcc($username = '', $password = '')
    {
        $tmpLocationFile = __DIR__ . '/tmpLocalStorageBusinessAcc.ini';
        $dataBusinessAcc = function_exists('file_get_contents')? file_get_contents($tmpLocationFile) : '{}';
        $dataBusinessAcc = json_decode($dataBusinessAcc, 1);
        $businessAcc = is_string($dataBusinessAcc['businessAcc'])? trim($dataBusinessAcc['businessAcc']) : '';
        if(empty($businessAcc) || sha1($username . $password) !== $dataBusinessAcc['hash']) {
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, self::ikUrlAPI . 'account');
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_FOLLOWLOCATION, false);
            curl_setopt($curl, CURLOPT_HEADER, false);
            curl_setopt($curl, CURLOPT_HTTPHEADER, ["Authorization: Basic " . base64_encode("$username:$password")]);
            $response = curl_exec($curl);

            if (!empty($response['data'])) {
                foreach ($response['data'] as $id => $data) {
                    if ($data['tp'] == 'b') {
                        $businessAcc = $id;
                        break;
                    }
                }
            }

            if(function_exists('file_put_contents')){
                $updData = [
                    'businessAcc' => $businessAcc,
                    'hash' => sha1($username . $password)
                ];
                file_put_contents($tmpLocationFile, json_encode($updData, JSON_PRETTY_PRINT));
            }

            return $businessAcc;
        }

        return $businessAcc;
    }

    public function checkIP(){
        $ip_stack = array(
            'ip_begin'=>'151.80.190.97',
            'ip_end'=>'151.80.190.104'
        );
        $ip = ip2long($_SERVER['REMOTE_ADDR'])? ip2long($_SERVER['REMOTE_ADDR']) : !ip2long($_SERVER['REMOTE_ADDR']);
        if(($ip >= ip2long($ip_stack['ip_begin'])) && ($ip <= ip2long($ip_stack['ip_end']))){
            return true;
        }
        return false;
    }


    /**
     * Метод куда система шлет статус заказа
     */
    public function callback() {
        if ($_POST) {
            $this->checkPaid($_POST);
        }
    }

    /**
     * Метов обработке статуса заказа
     * @param array $param пост от метода callback
     */
    private function checkPaid($param) {
        $ci = &get_instance();

        $order_id = $param['ik_pm_no'];
        $userOrder = $ci->db->where('id', $order_id)
            ->get('shop_orders');
        if ($userOrder) {
            $userOrder = $userOrder->row();
        } else {
            show_error($ci->db->_error_message());
        }

        $key = $userOrder->payment_method . '_' . $this->moduleName;
        $paySettings = $this->getPaymentSettings($key);

        if ($paySettings['test_mode'])
            $secret_key = $paySettings['test_key'];
        else
            $secret_key = $paySettings['secret_key'];

        $sigPost = $param['ik_sign'];
        $sign_hash = self::IkSignFormation($param, $secret_key);

        if ($param['ik_inv_st'] == 'success' && $sigPost === $sign_hash && $order_id) {
            $this->successPaid($order_id, $userOrder);
        }
    }

    /**
     * Save settings
     *
     * @return bool|string
     */
    public function saveSettings(SPaymentMethods $paymentMethod) {
        $saveKey = $paymentMethod->getId() . '_' . $this->moduleName;
        \ShopCore::app()->SSettings->set($saveKey, serialize($_POST['payment_method_interkassa']));

        return true;
    }

    /**
     * Переводит статус заказа в оплачено, и прибавляет пользователю
     * оплеченную сумму к акаунту
     * @param integer $order_id ид заказа который обрабатывается
     * @param obj $userOrder данные заказа
     */
    private function successPaid($order_id, $userOrder) {
        $ci = &get_instance();
        $amount = $ci->db->select('amout')
            ->get_where('users', ['id' => $userOrder->user_id]);

        if ($amount) {
            $amount = $amount->row()->amout;
        } else {
            show_error($ci->db->_error_message());
        }

        /* Учитывается цена с доставкой */
        //        $amount += $userOrder->delivery_price?($userOrder->total_price+$userOrder->delivery_price):$userOrder->total_price;
        /* Учитывается цена без доставки */
        $amount += $userOrder->total_price;

        $result = $ci->db->where('id', $order_id)
            ->update('shop_orders', ['paid' => '1', 'date_updated' => time()]);
        if ($ci->db->_error_message()) {
            show_error($ci->db->_error_message());
        }

        \CMSFactory\Events::create()->registerEvent(['system' => __CLASS__, 'order_id' => $order_id], 'PaimentSystem:successPaid');
        \CMSFactory\Events::runFactory();

        $result = $ci->db
            ->where('id', $userOrder->user_id)
            ->limit(1)
            ->update(
                'users',
                [
                 'amout' => str_replace(',', '.', $amount),
                ]
            );
        if ($ci->db->_error_message()) {
            show_error($ci->db->_error_message());
        }
    }

    public function autoload() {

    }

    public function _install() {
        $ci = &get_instance();

        $result = $ci->db->where('name', $this->moduleName)
            ->update('components', ['enabled' => '1']);
        if ($ci->db->_error_message()) {
            show_error($ci->db->_error_message());
        }
    }

    public function _deinstall() {
        $ci = &get_instance();

        $result = $ci->db->where('payment_system_name', $this->moduleName)
            ->update(
                'shop_payment_methods',
                [
                 'active'              => '0',
                 'payment_system_name' => '0',
                ]
            );
        if ($ci->db->_error_message()) {
            show_error($ci->db->_error_message());
        }

        $result = $ci->db->like('name', $this->moduleName)
            ->delete('shop_settings');
        if ($ci->db->_error_message()) {
            show_error($ci->db->_error_message());
        }
    }

}