<?php
defined('IN_ECJIA') or exit('No permission resources.');

class quickpay_module extends api_admin implements api_interface {
    public function handleRequest(\Royalcms\Component\HttpKernel\Request $request) {
    		
		$this->authadminSession();
		/**
		 * bonus 0 //红包
		 * how_oos 0 //缺货处理
		 * integral 0 //积分
		 * payment 3 //支付方式
		 * postscript //订单留言
		 * shipping 3 //配送方式
		 * surplus 0 //余额
		 * inv_type 4 //发票类型
		 * inv_payee 发票抬头
		 * inv_content 发票内容
		 */
		$ecjia = RC_Loader::load_app_class('api_admin', 'api');
		define('SESS_ID', RC_Session::session()->get_session_id());
		if ($_SESSION['temp_user_id'] > 0) {
			$_SESSION['user_id'] = $_SESSION['temp_user_id'];
		}

		RC_Loader::load_app_func('cart','cart');
		RC_Loader::load_app_func('order','orders');

		$pay_id = $this->requestData('pay_id');
		$amount = $this->requestData('amount');
		
		/* 判断是否是会员 */
		$consignee = array();
		if ($_SESSION['user_id']) {
			$db_user_model = RC_Loader::load_app_model('users_model','user');
			$user_info = $db_user_model->field('user_name, mobile_phone, email')
			->where(array('user_id'=>$_SESSION['user_id']))
			->find();
			$consignee = array(
					'consignee'		=> $user_info['user_name'],
					'mobile'		=> $user_info['mobile_phone'],
					'tel'			=> $user_info['mobile_phone'],
					'email'			=> $user_info['email'],
			);
		} else {//匿名用户
			$consignee = array(
					'consignee'	=> '匿名用户',
					'mobile'	=> '',
					'tel'		=> '',
					'email'		=> '',
			);
		}

		/* 获取商家或平台的地址 作为收货地址 */
		$region = RC_Loader::load_app_model('region_model','shipping');
		if ($_SESSION['ru_id'] > 0){
			$msi_dbview = RC_Loader::load_app_model('merchants_shop_information_viewmodel', 'seller');
			$where = array();
			$where['ssi.status'] = 1;
			$where['msi.merchants_audit'] = 1;
			$where['msi.user_id'] = $_SESSION['ru_id'];
			$info = $msi_dbview->join(array('category', 'seller_shopinfo'))
			->field('ssi.*')
			->where($where)
			->find();
			$region_info = array(
					'country'			=> $info['country'],
					'province'			=> $info['province'],
					'city'				=> $info['city'],
					'address'			=> $info['shop_address'],
			);
			$consignee = array_merge($consignee, $region_info);
		} else {
			$region_info = array(
					'country'			=> ecjia::config('shop_country'),
					'province'			=> ecjia::config('shop_province'),
					'city'				=> ecjia::config('shop_city'),
					'address'			=> ecjia::config('shop_address'),
			);
			$consignee = array_merge($consignee, $region_info);
		}

		$order = array(
				'user_id' => $_SESSION['user_id'],
				'pay_id' 		=> intval($pay_id),
				'goods_amount' 	=> isset($amount) ? floatval($amount) : '0.00',
				'money_paid' 	=> isset($amount) ? floatval($amount) : '0.00',
				'order_amount' 	=> isset($amount) ? floatval($amount) : '0.00',
				'add_time' 		=> RC_Time::gmtime(),
				'order_status'  => OS_CONFIRMED,
				'shipping_status'=> SS_UNSHIPPED,
				'pay_status' 	=> PS_UNPAYED,
				'agency_id' => get_agency_by_regions(array(
						$consignee['country'],
						$consignee['province'],
						$consignee['city'],
						$consignee['district']
				))
		);

		/* 收货人信息 */
		foreach ($consignee as $key => $value) {
			$order[$key] = addslashes($value);
		}

		$payment_method = RC_Loader::load_app_class('payment_method','payment');
		/* 支付方式 */
		if ($pay_id > 0) {
			$payment = $payment_method->payment_info_by_id($order['pay_id']);
			$order['pay_name'] = addslashes($payment['pay_name']);
		}

		$order['from_ad'] = ! empty($_SESSION['from_ad']) ? $_SESSION['from_ad'] : '0';
		//TODO:订单来源收银台暂时写死
		$order['referer'] = 'ecjia-cashdesk'; // !empty($_SESSION['referer']) ? addslashes($_SESSION['referer']) : '';

		$parent_id = 0;
		$order['parent_id'] = $parent_id;

		/* 插入订单表 */
		$order['order_sn'] = get_order_sn(); // 获取新订单号

		$db_order_info = RC_Loader::load_app_model('order_info_model','orders');
		$new_order_id = $db_order_info->insert($order);

		/* 插入订单商品 */
		if ($new_order_id > 0) {
			$db_order_goods = RC_Loader::load_app_model('order_goods_model','orders');
			$arr = array(
					'order_id' 		=> $new_order_id,
					'goods_id' 		=> '0',
					'goods_name' 	=> '收银台快捷收款',
					'goods_sn' 		=> '',
					'product_id' 	=> '0',
					'goods_number' 	=> '1',
					'market_price' 	=> '0.00',
					'goods_price' 	=> '0.00',
					'goods_attr' 	=> '',
					'is_real' 		=> '1',
			);
			if ($_SESSION['ru_id'] > 0) {
				$arr['ru_id'] = $_SESSION['ru_id'];
			}
			$order_goods_id = $db_order_goods->insert($arr);
		}
		
		if ($new_order_id > 0 && $order_goods_id > 0) {
			$adviser_log = array(
				'adviser_id' => $_SESSION['adviser_id'],
				'order_id'	 => $new_order_id,
				'device_id'	 => $_SESSION['device_id'],
				'type'   	 => '2',//收款
				'add_time'	 => RC_Time::gmtime(),
			);
			$adviser_log_id = RC_Model::model('achievement/adviser_log_model')->insert($adviser_log);
		}
		
		/* 插入支付日志 */
		$order['log_id'] = $payment_method->insert_pay_log($new_order_id, $order['order_amount'], PAY_ORDER);
		$payment_info = $payment_method->payment_info_by_id($pay_id);

		$subject = '收银台快捷收款￥'.floatval($amount).'';
		$out = array(
				'order_sn' => $order['order_sn'],
				'order_id' => $new_order_id,
				'order_info' => array(
						'pay_code' 		=> $payment_info['pay_code'],
						'order_amount' 	=> $order['order_amount'],
						'order_id' 		=> $new_order_id,
						'subject' 		=> $subject,
						'desc' 			=> $subject,
						'order_sn' 		=> $order['order_sn']
				)
		);
		EM_Api::outPut($out);
	}
}

// end