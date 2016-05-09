<?php
defined('IN_ECJIA') or exit('No permission resources.');
/**
 * 订单支付确认
 * @author will
 *
 */
class payConfirm_module implements ecjia_interface {
	
	public function run(ecjia_api & $api) {
		$ecjia = RC_Loader::load_app_class('api_admin', 'api');
		$ecjia->authadminSession();
		$result = $ecjia->admin_priv('order_stats');
		if (is_ecjia_error($result)) {
			EM_Api::outPut($result);
		}

		$order_id = _POST('order_id');
		if (empty($order_id)) {
			EM_Api::outPut(101);
		}
		
		/* 查询订单信息 */
		$order = RC_Api::api('orders', 'order_info', array('order_id' => $order_id, 'order_sn' => ''));
		if (empty($order)) {
			EM_Api::outPut(13);
		}
		
		$payment_method	= RC_Loader::load_app_class('payment_method', 'payment');
		$pay_info = $payment_method->payment_info_by_id($order['pay_id']);
		$payment = $payment_method->get_payment_instance($pay_info['pay_code']);
		
		/* 判断是否有支付方式以及是否为现金支付和酷银*/
		if (!$payment) {
			EM_Api::outPut(8);
		}
		$payment->set_orderinfo($order);
		
		if ($pay_info['pay_code'] == 'pay_cash') {
			$pay_priv = $ecjia->admin_priv('order_ps_edit');
			if (is_ecjia_error($pay_priv)) {
				EM_Api::outPut($pay_priv);
			}
			/* 进行确认*/
			RC_Api::api('orders', 'order_operate', array('order_id' => $order_id, 'order_sn' => '', 'operation' => 'confirm', 'note' => array('action_note' => '收银台订单确认')));
			/* 配货*/
			RC_Api::api('orders', 'order_operate', array('order_id' => $order_id, 'order_sn' => '', 'operation' => 'prepare', 'note' => array('action_note' => '收银台配货')));
			/* 分单（生成发货单）*/
			$result = RC_Api::api('orders', 'order_operate', array('order_id' => $order_id, 'order_sn' => '', 'operation' => 'split', 'note' => array('action_note' => '收银台生成发货单')));
			
			if (is_ecjia_error($result)) {
				return $result;
			}
			/* 发货*/
			$db_delivery_order	= RC_Loader::load_app_model('delivery_order_model', 'orders');
			$delivery_id = $db_delivery_order->where(array('order_sn' => array('like' => '%'.$order_info['order_sn'].'%')))->order(array('delivery_id' => 'desc'))->get_field('delivery_id');
			
			$result = delivery_ship($order_id, $delivery_id);
			$result = $payment->notify();
			if (is_ecjia_error($result)) {
				return $result;
			} else {
				/* 确认收货*/
				RC_Api::api('orders', 'order_operate', array('order_id' => $order_id, 'order_sn' => '', 'operation' => 'receive', 'note' => array('action_note' => '收银台确认收货')));
				
				$order_info = RC_Api::api('orders', 'order_info', array('order_id' => $order_id, 'order_sn' => ''));
				$data = array(
						'order_id' 		=> $order_info['order_id'],
						'money_paid'	=> $order_info['money_paid'],
						'formatted_money_paid'	=> $order_info['formated_money_paid'],
						'order_amount'	=> $order_info['order_amount'],
						'formatted_order_amount' => $order_info['formated_order_amount'],
						'pay_code'		=> $pay_info['pay_code'],
						'pay_name'		=> $pay_info['pay_name'],
						'pay_status'	=> 'success',
						'desc'			=> '订单支付成功！'
				);
				
				return array('payment' => $data);
			}
		}
		
		if ($pay_info['pay_code'] == 'pay_koolyun') {
			$result = $payment->notify();
			if (is_ecjia_error($result)) {
				return $result;
			} else {
				/* 配货*/
				RC_Api::api('orders', 'order_operate', array('order_id' => $order_id, 'order_sn' => '', 'operation' => 'prepare', 'note' => array('action_note' => '收银台配货')));
				/* 分单（生成发货单）*/
				$result = RC_Api::api('orders', 'order_operate', array('order_id' => $order_id, 'order_sn' => '', 'operation' => 'split', 'note' => array('action_note' => '收银台生成发货单')));
				
				if (is_ecjia_error($result)) {
					return $result;
				}
				/* 发货*/
				$db_delivery_order	= RC_Loader::load_app_model('delivery_order_model', 'orders');
				$delivery_id = $db_delivery_order->where(array('order_sn' => array('like' => '%'.$order_info['order_sn'].'%')))->order(array('delivery_id' => 'desc'))->get_field('delivery_id');
				
				$result = delivery_ship($order_id, $delivery_id);
				/* 确认收货*/
				RC_Api::api('orders', 'order_operate', array('order_id' => $order_id, 'order_sn' => '', 'operation' => 'receive', 'note' => array('action_note' => '收银台确认收货')));
				
				$order_info = RC_Api::api('orders', 'order_info', array('order_id' => $order_id, 'order_sn' => ''));
				$data = array(
						'order_id' 		=> $order_info['order_id'],
						'money_paid'	=> $order_info['money_paid'],
						'formatted_money_paid'	=> $order_info['formated_money_paid'],
						'order_amount'	=> $order_info['order_amount'],
						'formatted_order_amount' => $order_info['formated_order_amount'],
						'pay_code'		=> $pay_info['pay_code'],
						'pay_name'		=> $pay_info['pay_name'],
						'pay_status'	=> 'success',
						'desc'			=> '订单支付成功！'
				);
				return array('payment' => $data);
			}
		}
	}
}


function delivery_ship($order_id, $delivery_id) {
	RC_Loader::load_app_func('function', 'orders');
	RC_Loader::load_app_func('order', 'orders');
	$db_delivery = RC_Loader::load_app_model('delivery_viewmodel','orders');
	$db_delivery_order		= RC_Loader::load_app_model('delivery_order_model','orders');
	$db_goods				= RC_Loader::load_app_model('goods_model','goods');
	$db_products			= RC_Loader::load_app_model('products_model','goods');
	RC_Lang::load('order');
	/* 定义当前时间 */
	define('GMTIME_UTC', RC_Time::gmtime()); // 获取 UTC 时间戳
	/* 取得参数 */
	$delivery				= array();
	$order_id				= intval(trim($order_id));			// 订单id
	$delivery_id			= intval(trim($delivery_id));		// 发货单id
	$delivery['invoice_no']	= isset($_POST['invoice_no']) ? trim($_POST['invoice_no']) : '';
	$action_note			= isset($_POST['action_note']) ? trim($_POST['action_note']) : '';

	/* 根据发货单id查询发货单信息 */
	if (!empty($delivery_id)) {
		$delivery_order = delivery_order_info($delivery_id);
	} else {
		return new ecjia_error('delivery_id_error', __('无法找到对应发货单！'));
		// 		$this->showmessage( __('无法找到对应发货单！') , ecjia_admin::MSGTYPE_JSON | ecjia_admin::MSGSTAT_ERROR);
	}
	if (empty($delivery_order)) {
		return new ecjia_error('delivery_error', __('无法找到对应发货单！'));
	}
	// 	/* 查询订单信息 */
	// 	$order = order_info($order_id);
	/* 查询订单信息 */
	$order = RC_Api::api('orders', 'order_info', array('order_id' => $order_id, 'order_sn' => ''));


	/* 检查此单发货商品库存缺货情况 */
	$virtual_goods			= array();
	$delivery_stock_result	= $db_delivery->join(array('goods', 'products'))->where(array('dg.delivery_id' => $delivery_id))->group('dg.product_id')->select();

	/* 如果商品存在规格就查询规格，如果不存在规格按商品库存查询 */
	if(!empty($delivery_stock_result)) {
		foreach ($delivery_stock_result as $value) {
			if (($value['sums'] > $value['storage'] || $value['storage'] <= 0) &&
			((ecjia::config('use_storage') == '1'  && ecjia::config('stock_dec_time') == SDT_SHIP) ||
					(ecjia::config('use_storage') == '0' && $value['is_real'] == 0))) {
				return new ecjia_error('act_good_vacancy', sprintf(RC_Lang::lang('act_good_vacancy'), $value['goods_name']));
				// 				/* 操作失败 */
				// 				$links[] = array('text' => RC_Lang::lang('order_info'), 'href' => RC_Uri::url('orders/admin_order_delivery/delivery_info', 'delivery_id=' . $delivery_id));
				// 				$this->showmessage(sprintf(RC_Lang::lang('act_good_vacancy'), $value['goods_name']), ecjia_admin::MSGTYPE_JSON | ecjia_admin::MSGSTAT_ERROR, array('links' => $links));
			}

			/* 虚拟商品列表 virtual_card */
			if ($value['is_real'] == 0) {
				$virtual_goods[] = array(
						'goods_id'		=> $value['goods_id'],
						'goods_name'	=> $value['goods_name'],
						'num'			=> $value['send_number']
				);
			}
		}
	} else {
		$db_delivery->view = array(
				'goods' => array(
						'type'		=> Component_Model_View::TYPE_LEFT_JOIN,
						'alias'		=> 'g',
						'field'		=> 'dg.goods_id, dg.is_real, SUM(dg.send_number) AS sums, g.goods_number, g.goods_name, dg.send_number',
						'on'		=> 'dg.goods_id = g.goods_id ',
				)
		);

		$delivery_stock_result = $db_delivery->where(array('dg.delivery_id' => $delivery_id))->group('dg.goods_id')->select();

		foreach ($delivery_stock_result as $value) {
			if (($value['sums'] > $value['goods_number'] || $value['goods_number'] <= 0) &&
			((ecjia::config('use_storage') == '1'  && ecjia::config('stock_dec_time') == SDT_SHIP) ||
					(ecjia::config('use_storage') == '0' && $value['is_real'] == 0))) {
				return new ecjia_error('act_good_vacancy', sprintf(RC_Lang::lang('act_good_vacancy'), $value['goods_name']));
			}

			/* 虚拟商品列表 virtual_card*/
			if ($value['is_real'] == 0) {
				$virtual_goods[] = array(
						'goods_id'		=> $value['goods_id'],
						'goods_name'	=> $value['goods_name'],
						'num'			=> $value['send_number']
				);
			}
		}
	}

	/* 发货 */
	/* 处理虚拟卡 商品（虚货） */
	if (is_array($virtual_goods) && count($virtual_goods) > 0) {
		RC_Loader::load_app_func('common', 'goods');
		foreach ($virtual_goods as $virtual_value) {
			virtual_card_shipping($virtual_value,$order['order_sn'], $msg, 'split');
		}
	}

	/* 如果使用库存，且发货时减库存，则修改库存 */
	if (ecjia::config('use_storage') == '1' && ecjia::config('stock_dec_time') == SDT_SHIP) {
		foreach ($delivery_stock_result as $value) {
			/* 商品（实货）、超级礼包（实货） */
			if ($value['is_real'] != 0) {
				/* （货品） */
				if (!empty($value['product_id'])) {
					$data = array(
							'product_number' => $value['storage'] - $value['sums'],
					);
					$db_products->where(array('product_id' => $value['product_id']))->update($data);
				} else {
					$data = array(
							'goods_number' => $value['storage'] - $value['sums'],
					);
					$db_goods->where(array('goods_id' => $value['goods_id']))->update($data);
				}
			}
		}
	}

	/* 修改发货单信息 */
	$invoice_no = str_replace(',', '<br>', $delivery['invoice_no']);
	$invoice_no = trim($invoice_no, '<br>');
	$_delivery['invoice_no']	= $invoice_no;
	$_delivery['status']		= 0;	/* 0，为已发货 */
	$result = $db_delivery_order->where(array('delivery_id' => $delivery_id))-> update($_delivery);

	if (!$result) {
		return new ecjia_error('act_false', RC_Lang::lang('act_false'));
	}

	/* 标记订单为已确认 “已发货” */
	/* 更新发货时间 */
	$order_finish				= get_all_delivery_finish($order_id);
	$shipping_status			= ($order_finish == 1) ? SS_SHIPPED : SS_SHIPPED_PART;
	$arr['shipping_status']		= $shipping_status;
	$arr['shipping_time']		= GMTIME_UTC; // 发货时间
	$arr['invoice_no']			= trim($order['invoice_no'] . '<br>' . $invoice_no, '<br>');
	update_order($order_id, $arr);

	/* 发货单发货记录log */
	order_action($order['order_sn'], OS_CONFIRMED, $shipping_status, $order['pay_status'], $action_note, null, 1);
	ecjia_admin::admin_log('发货，订单号是'.$order['order_sn'], 'setup', 'order');

	/* 如果当前订单已经全部发货 */
	if ($order_finish) {
		/* 如果订单用户不为空，计算积分，并发给用户；发红包 */
		if ($order['user_id'] > 0) {
			/* 取得用户信息 */
			$user = user_info($order['user_id']);
			/* 计算并发放积分 */
			$integral = integral_to_give($order);
			$options = array(
					'user_id'		=> $order['user_id'],
					'rank_points'	=> intval($integral['rank_points']),
					'pay_points'	=> intval($integral['custom_points']),
					'change_desc'	=> sprintf(RC_Lang::lang('order_gift_integral'), $order['order_sn'])
			);
			RC_Api::api('user', 'account_change_log',$options);
			/* 发放红包 */
			send_order_bonus($order_id);
		}
	}

	return true;
}

// end