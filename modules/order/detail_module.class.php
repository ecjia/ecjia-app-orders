<?php
defined('IN_ECJIA') or exit('No permission resources.');
/**
 * 订单祥情
 * @author royalwang
 *
 */
class detail_module implements ecjia_interface {
	
	public function run(ecjia_api & $api) {

		EM_Api::authSession();

		RC_Loader::load_app_func('order', 'orders');
		$order_id = _POST('order_id', 0);
		if (!$order_id) {
			EM_Api::outPut(101);
		}
		
		$user_id = $_SESSION['user_id'];
		
		/* 订单详情 */
		$order = get_order_detail($order_id, $user_id);
		
		if ($order === false) {
			EM_Api::outPut(8);
		}
		//收货人地址
		$db_region = RC_Loader::load_app_model('region_model', 'shipping');
		$region_name = $db_region->where(array('region_id' => array('in'=>$order['country'],$order['province'],$order['city'],$order['district'])))->order('region_type')->select();
		$order['country'] = $region_name[0]['region_name'];
		$order['province'] = $region_name[1]['region_name'];
		$order['city'] = $region_name[2]['region_name'];
		$order['district'] = $region_name[3]['region_name'];
		$goods_list = EM_order_goods($order_id);
		
		foreach ($goods_list as $k =>$v) {
			$attr = array();
			if (!empty($v['goods_attr'])) {
				$goods_attr = explode("\n", $v['goods_attr']);
				$goods_attr = array_filter($goods_attr);
				foreach ($goods_attr as  $val) {
					$a = explode(':',$val);
					if (!empty($a[0]) && !empty($a[1])) {
						$attr[] = array('name'=>$a[0], 'value'=>$a[1]);
					}
				}
			}
			
			$goods_list[$k] = array(
					"goods_id" => $v['goods_id'],
					"name" => $v['goods_name'],
					"goods_attr"   => empty($attr) ? '' : $attr,
					"goods_number" => $v['goods_number'],
					"subtotal" => price_format($v['subtotal'], false),
					"formated_shop_price" => $v['goods_price'] > 0 ? price_format($v['goods_price'], false) : __('免费'),
					'is_commented'	=> $v['is_commented'],
					"img" => array(
							'small'=>API_DATA('PHOTO', $v['goods_thumb']),
							'thumb'=>API_DATA('PHOTO', $v['goods_img']),
							'url' => API_DATA('PHOTO', $v['original_img'])
					)
			);
		}
		$order['goods_list'] = $goods_list;
		
		$db_term_meta = RC_Loader::load_model('term_meta_model');
		$meta_data_where = array(
				'object_type'	=> 'ecjia.order',
				'object_group'	=> 'order',
				'object_id'		=> $order_id,
				'meta_key'		=> 'receipt_verification',
		);
		$receipt_code = $db_term_meta->where($meta_data_where)->get_field('meta_value');
		if (!empty($receipt_code)) {
			$order['receipt_verification'] = $receipt_code;
		}
		
		$order_status_log = RC_Model::model('orders/order_status_log_model')->where(array('order_id' => $order_id))->order(array('log_id' => 'desc'))->select();
		$order['order_status_log'] = array();
		if (!empty($order_status_log)) {
			foreach ($order_status_log as $val) {
				$order['order_status_log'][] = array(
					'order_status'	=> $val['order_status'],
					'message'		=> $val['message'],
					'time'			=> RC_Time::local_date(ecjia::config('time_format'), $val['add_time']),
				);
			}
		}
		
		
		//支付方式信息
		$payment_method = RC_Loader::load_app_class('payment_method', 'payment');
		$payment_info = array();
		$payment_info = $payment_method->payment_info_by_id($order['pay_id']);
		
		if ($payment_info['pay_code'] == 'upmp') {
		    RC_Log::write('upmp get code ' . $payment_info['pay_code'], RC_Log::DEBUG);
		    if (RC_Loader::load_app_module($payment_info['pay_code'], 'payment', false)) {
		        $payment = get_payment($payment['pay_code']);
		        $pay_obj = new $payment_info['pay_code']();
		        list($resp, $validResp) = $pay_obj->get_tn($order, $payment);
		        
		        // 商户的业务逻辑
		        if ($validResp){
		            // 服务器应答签名验证成功
		            RC_Log::write('upmp get code trade success', RC_Log::DEBUG);
		        
		            if ($resp['respCode'] == '00') {
		                $order['pay_upmp_tn'] = $resp['tn'];
		            }
		            else {
		                $order['pay_error'] = $resp[respMsg];
		            }
		        }
		        else
		        {
		            // 服务器应答签名验证失败
		            RC_Log::write('upmp get code trade fail', RC_Log::DEBUG);
		        }
		    }
		}
		
		EM_Api::outPut(array('data' => $order));
	}
}

// end