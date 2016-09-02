<?php
defined('IN_ECJIA') or exit('No permission resources.');
/**
 * 订单祥情
 * @author royalwang
 *
 */
class detail_module extends api_front implements api_interface {
    public function handleRequest(\Royalcms\Component\HttpKernel\Request $request) {
    		
    	$this->authSession();
		RC_Loader::load_app_func('order', 'orders');
		$order_id = $this->requestdata('order_id', 0);
		if (!$order_id) {
			EM_Api::outPut(101);
		}
		
		$user_id = $_SESSION['user_id'];
		
		/* 订单详情 */
		$order = get_order_detail($order_id, $user_id);
		/*返回数据处理*/
		$order['order_id'] 			= intval($order['order_id']);
		$order['main_order_id'] 	= intval($order['main_order_id']);
		$order['user_id'] 			= intval($order['user_id']);
		$order['order_status'] 		= intval($order['order_status']);
		$order['shipping_status'] 	= intval($order['shipping_status']);
		$order['pay_status'] 		= intval($order['pay_status']);
		$order['shipping_id'] 		= intval($order['shipping_id']);
		$order['pay_id'] 			= intval($order['pay_id']);
		$order['pack_id'] 			= intval($order['pack_id']);
		$order['card_id'] 			= intval($order['card_id']);
		$order['bonus_id'] 			= intval($order['bonus_id']);
		$order['agency_id'] 		= intval($order['agency_id']); 
		$order['extension_id'] 		= intval($order['extension_id']);
		$order['parent_id'] 		= intval($order['parent_id']);
		
		if ($order === false) {
			EM_Api::outPut(8);
		}
		//收货人地址
		$db_region = RC_Loader::load_app_model('region_model', 'shipping');
		$region_name = $db_region->where(array('region_id' => array('in'=>$order['country'],$order['province'],$order['city'],$order['district'])))->order('region_type')->select();
		$order['country']	= $region_name[0]['region_name'];
		$order['province']	= $region_name[1]['region_name'];
		$order['city']		= $region_name[2]['region_name'];
		$order['district']	= $region_name[3]['region_name'];
		$goods_list = EM_order_goods($order_id);
// 		$msi_dbview = RC_Loader::load_app_model('merchants_shop_information_viewmodel', 'seller');
		foreach ($goods_list as $k => $v) {
// 			if ($k == 0) {
// 				if ($v['ru_id'] > 0) {
// 					$field ='msi.user_id, ssi.*, CONCAT(shoprz_brandName,shopNameSuffix) as seller_name';
// 					$seller_info = $msi_dbview->join(array('seller_shopinfo'))
// 												->field($field)
// 												->where(array('msi.user_id' => $v['ru_id']))
// 												->find();
					
// 				}
				
// 				$order['seller_id']					= isset($v['ru_id']) ? intval($v['ru_id']) : 0;
// 				$order['seller_name']				= isset($seller_info['seller_name']) ? $seller_info['seller_name'] : '自营';
// 				$order['service_phone']				= $seller_info['kf_tel'];
// 			}
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
					'goods_id'	=> $v['goods_id'],
					'name'		=> $v['goods_name'],
					'goods_attr'	=> empty($attr) ? '' : $attr,
					'goods_number'	=> $v['goods_number'],
					'subtotal'		=> price_format($v['subtotal'], false),
					'formated_shop_price' => $v['goods_price'] > 0 ? price_format($v['goods_price'], false) : __('免费'),
					'is_commented'	=> $v['is_commented'],
					'img' => array(
							'small'	=> !empty($v['goods_thumb']) ? RC_Upload::upload_url($v['goods_thumb']) : '',
							'thumb'	=> !empty($v['goods_img']) ? RC_Upload::upload_url($v['goods_img']) : '',
							'url' 	=> !empty($v['original_img']) ? RC_Upload::upload_url($v['original_img']) : '',
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