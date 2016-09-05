<?php
defined('IN_ECJIA') or exit('No permission resources.');
/**
 * 余额支付后处理订单的接口
 * @author royalwang
 *
 */
class orders_user_account_paid_api extends Component_Event_Api {
	
    /**
     * @param  $options['user_id'] 会员id
     *         $options['order_id'] 订单id
     *
     * @return array
     */
	public function call(&$options) {	
	    if (!is_array($options) 
	        || !isset($options['user_id']) 
	        || !isset($options['order_id'])) {
	        return new ecjia_error('invalid_parameter', RC_Lang::get('orders::order.invalid_parameter'));
	    }
	    
	    $result = $this->user_account_paid($options['user_id'], $options['order_id']);
	    
	    if (is_ecjia_error($result)) {
	    	return $result;
	    } else {
	    	return true;
	    }
	    
	}
	
	/**
	 * 余额支付
	 *
	 * @access  public
	 * @param   integer $user_id 用户id
	 * @param   integer $order_id 订单id
	 * @return  void
	 */
	private function user_account_paid($user_id, $order_id)
	{
		RC_Loader::load_app_func('order', 'orders');
		
		/* 订单详情 */
		$order_info = get_order_detail($order_id, $user_id);
		
		/* 会员详情*/
		$user_info = user_info($user_id);
		/* 检查订单是否已经付款 */
		if ($order_info['pay_status'] == PS_PAYED && $order_info['pay_time']) {
			return new ecjia_error('order_paid', '该订单已经支付，请勿重复支付。');
		}
		
		/* 检查订单金额是否大于余额 */
		if ($order_info['order_amount'] > ($user_info['user_money'] + $user_info['credit_line'])) {
			return new ecjia_error('balance_less', '您的余额不足以支付整个订单，请选择其他支付方式。');
		}
		
		/* 余额支付里如果输入了一个金额 */
		if($order_info['surplus'] > 0) {
			$order_info['order_amount'] = $order_info['order_amount'] + $order_info['surplus'];
			$order_info['surplus'] = 0;
		}
		
		/* 更新订单表支付后信息 */
		$data = array(
			'order_status'    => OS_CONFIRMED,
			'confirm_time'    => RC_Time::gmtime(),
			'pay_status'      => PS_PAYED,
			'pay_time'        => RC_Time::gmtime(),
			'order_amount'    => 0,
			'surplus'         => $order_info['order_amount'],
		);
		
		/*更新订单状态及信息*/
		update_order($order_info['order_id'], $data);
		
		/* 处理余额变动信息 */
		if ($order_info['user_id'] > 0 && $data['surplus'] > 0) {
			$options = array(
				'user_id'       => $order_info['user_id'],
				'user_money'    => $order_info['order_amount'] * (-1),
				'change_desc'   => sprintf('支付订单 %s', $order_info['order_sn'])
			);
			RC_Api::api('user', 'account_change_log', $options);
			/* 插入支付日志 */
			$payment_method = RC_Loader::load_app_class('payment_method', 'payment');
			$payment_method->insert_pay_log($order_info['order_id'], $order_info['order_amount'], PAY_SURPLUS);
		}
		
		/* 判断是否是主订单 */
		$main_order_id = $order_info['main_order_id'];
		if ($main_order_id <= 0) {
			$data = array(
				'order_status' => OS_CONFIRMED,
				'confirm_time' => RC_Time::gmtime(),
				'pay_status'   => PS_PAYED,
				'pay_time'     => RC_Time::gmtime(),
			);
			$db_order = RC_Loader::load_app_model('order_info_model', 'orders');
			$db_order->where(array('main_order_id' => $order_info['order_id']))->update($data);
			$db_order->inc('money_paid', "main_order_id=".$order_info['order_id'], '0, money_paid=order_amount, order_amount=0');
		
		
			$order_res = $db_order->field('order_sn')->where(array('main_order_id' => $order_info['order_id']))->select();
			 
			foreach ($order_res AS $row) {
				/* 记录订单操作记录 */
				order_action($row['order_sn'], OS_CONFIRMED, SS_UNSHIPPED, PS_PAYED, '', '买家');
			}
		}
		
		RC_Model::model('orders/order_status_log_model')->insert(array(
				'order_status'	=> '已付款',
				'order_id'		=> $order_info['order_id'],
				'message'		=> '已通知商家揽收，请耐心等待！',
				'add_time'		=> RC_Time::gmtime(),
		));
		
		$result = ecjia_app::validate_application('sms');
		if (!is_ecjia_error($result)) {
			/* 收货验证短信  */
			if (ecjia::config('sms_receipt_verification') == '1' && ecjia::config('sms_shop_mobile') != '') {
				
				$db_term_meta = RC_Loader::load_model('term_meta_model');
				$meta_where = array(
					'object_type'	=> 'ecjia.order',
					'object_group'	=> 'order',
					'meta_key'		=> 'receipt_verification',
				);
				$max_code = $db_term_meta->where($meta_where)->max('meta_value');
				$max_code = $max_code ? ceil($max_code/10000) : 1000000;
				$code = $max_code . str_pad(mt_rand(0, 9999), 4, '0', STR_PAD_LEFT);
// 				$code = rand(100000, 999999);
				$tpl_name = 'sms_receipt_verification';
				$tpl   = RC_Api::api('sms', 'sms_template', $tpl_name);
				if (!empty($tpl)) {
					ecjia::$view_object->assign('order_sn', $order_info['order_sn']);
					ecjia::$view_object->assign('user_name', $order_info['consignee']);
					ecjia::$view_object->assign('code', $code);
					
					$content = ecjia::$controller->fetch_string($tpl['template_content']);
						
					$options = array(
						'mobile' 		=> $order_info['mobile'],
						'msg'			=> $content,
						'template_id' 	=> $tpl['template_id'],
					);
					$response = RC_Api::api('sms', 'sms_send', $options);
					
// 					$db_term_meta = RC_Loader::load_model('term_meta_model');
					$meta_data = array(
						'object_type'	=> 'ecjia.order',
						'object_group'	=> 'order',
						'object_id'		=> $order_id,
						'meta_key'		=> 'receipt_verification',
						'meta_value'	=> $code,
					);
					$db_term_meta->insert($meta_data);
				}
				if ($main_order_id <= 0) {
					$order_res = $db_order->field('order_id')->where(array('main_order_id' => $order_id))->select();
					foreach ($order_res AS $row) {
						$meta_data = array(
							'object_type'	=> 'ecjia.order',
							'object_group'	=> 'order',
							'object_id'		=> $row['order_id'],
							'meta_key'		=> 'receipt_verification',
							'meta_value'	=> $code,
						);
						$db_term_meta->insert($meta_data);
					}
				}
			}
		
			/* 客户付款短信提醒 */
			if (ecjia::config('sms_order_payed') == '1' && ecjia::config('sms_shop_mobile') != '') {
				//发送短信
				$tpl_name = 'order_payed_sms';
				$tpl   = RC_Api::api('sms', 'sms_template', $tpl_name);
				if (!empty($tpl)) {
					ecjia::$view_object->assign('order_sn', $order_info['order_sn']);
					ecjia::$view_object->assign('consignee', $order_info['consignee']);
					ecjia::$view_object->assign('mobile', $order_info['mobile']);
					ecjia::$view_object->assign('order_amount', $order_info['order_amount']);
					$content = ecjia::$controller->fetch_string($tpl['template_content']);
			
					$options = array(
						'mobile' 		=> ecjia::config('sms_shop_mobile'),
						'msg'			=> $content,
						'template_id' 	=> $tpl['template_id'],
					);
					$response = RC_Api::api('sms', 'sms_send', $options);
				}
			}
		}
		return true;
    }
}

// end