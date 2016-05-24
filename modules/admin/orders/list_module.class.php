<?php
defined('IN_ECJIA') or exit('No permission resources.');
/**
 * 订单列表
 * @author will
 *
 */
class list_module implements ecjia_interface {
	
	public function run(ecjia_api & $api) {
		
		$ecjia = RC_Loader::load_app_class('api_admin', 'api');
		$ecjia->authadminSession();
		$result = $ecjia->admin_priv('order_view');
		
		if (is_ecjia_error($result)) {
			EM_Api::outPut($result);
		}
		$type		= _POST('type', 'await_pay');
		$keywords	= _POST('keywords');
		$size		= EM_Api::$pagination['count'];
		$page		= EM_Api::$pagination['page'];
		
		$device = _POST('device', array());
		$device_code = isset($device['code']) ? $device['code'] : '8001';

		$device_udid = isset($device['udid']) ? $device['udid'] : '5f3434e351a1c2aaf0e27292851bc1f18bcc0a84';
		$device_client = isset($device['client']) ? $device['client'] : '';
		
		$order_query = RC_Loader::load_app_class('order_query', 'orders');
		$db = RC_Loader::load_app_model('order_info_model', 'orders');
		$db_view = RC_Loader::load_app_model('order_info_viewmodel', 'orders');
		
		$where = array();
		if ( !empty($keywords)) {
			$where[] = "( oi.order_sn like '%".$keywords."%' or oi.consignee like '%".$keywords."%' )";
		}
		if ($device_code != '8001') {
			switch ($type) {
				case 'await_pay':
					$where = $order_query->order_await_pay('oi.');
					break;
				case 'await_ship':
					$where = $order_query->order_await_ship('oi.');
					break;
				case 'shipped':
					$where = $order_query->order_shipped('oi.');
					break;
				case 'finished':
					$where = $order_query->order_finished('oi.');
					break;
				case 'refund':
					$where = $order_query->order_refund('oi.');
					break;
				case 'closed' :
					$where = array_merge($order_query->order_invalid('oi.'),$order_query->order_canceled('oi.'));
					break;
			}

			$db_orderinfo_view = RC_Loader::load_app_model('order_info_viewmodel', 'orders');
			$result = ecjia_app::validate_application('seller');
			if (!is_ecjia_error($result)) {
				$db_orderinfo_view->view = array(
						'order_info' => array(
								'type'	=> Component_Model_View::TYPE_LEFT_JOIN,
								'alias'	=> 'oii',
								'on'	=> 'oi.order_id = oii.main_order_id'
						),
						'order_goods' => array(
								'type'	=> Component_Model_View::TYPE_LEFT_JOIN,
								'alias'	=> 'og',
								'on'	=> 'oi.order_id = og.order_id'
						)
				);
			} else {
				$db_orderinfo_view->view = array(
						'order_goods' => array(
								'type'	=> Component_Model_View::TYPE_LEFT_JOIN,
								'alias'	=> 'og',
								'on'	=> 'oi.order_id = og.order_id'
						)
				);
			}
			
			if ($_SESSION['ru_id'] > 0) {
				$where['ru_id'] = $_SESSION['ru_id'];
				$where[] = 'oii.order_id is null';
			}
			/* 获取记录条数 */
			$record_count = $db_orderinfo_view->where($where)->count('oi.order_id');
			
			//加载分页类
			RC_Loader::load_sys_class('ecjia_page', false);
			//实例化分页
			$page_row = new ecjia_page($record_count, $size, 6, '', $page);
			
			$total_fee = "(oi.goods_amount + oi.tax + oi.shipping_fee + oi.insure_fee + oi.pay_fee + oi.pack_fee + oi.card_fee) as total_fee";
			$field = 'oi.order_id, oi.order_sn, oi.consignee, oi.mobile, oi.tel, oi.order_status, oi.pay_status, oi.shipping_status, oi.pay_id, oi.pay_name, '.$total_fee.', oi.integral_money, oi.bonus, oi.shipping_fee, oi.discount, oi.add_time, og.goods_number, og.goods_id,  og.goods_name';
			$order_ids = $db_orderinfo_view->where($where)->field('oi.order_id')->limit($page_row->limit())->select();
			foreach ($order_ids as $val) {
				$where['oi.order_id'][] = $val['order_id'];
			}
			if ($_SESSION['ru_id'] > 0) {
				$data = $db_orderinfo_view->field($field)->where($where)->order(array('oi.order_id' => 'desc'))->limit($page_row->limit())->select();
			} else {
				$data = $db_orderinfo_view->join(array('order_goods'))->field($field)->where($where)->order(array('oi.order_id' => 'desc'))->limit($page_row->limit())->select();
			}
		} else {
			$db_adviser_log_view = RC_Loader::load_app_model('adviser_log_viewmodel', 'orders');
			$where['al.device_id'] = $_SESSION['device_id'];
			/*获取记录条数 */
			$record_count = $db_adviser_log_view->where($where)->count('al.order_id'); 			
			//实例化分页
			$page_row = new ecjia_page($record_count, $size, 6, '', $page);
			$total_fee = "(oi.goods_amount + oi.tax + oi.shipping_fee + oi.insure_fee + oi.pay_fee + oi.pack_fee + oi.card_fee) as total_fee";
			$field = 'oi.order_id, ad.username, oi.order_sn, oi.consignee, oi.mobile, oi.tel, oi.order_status, oi.pay_status, oi.shipping_status, oi.pay_id, oi.pay_name, '.$total_fee.', oi.integral_money, oi.bonus, oi.shipping_fee, oi.discount, oi.add_time,og.goods_id, og.goods_number, og.goods_name';
			$order_ids = $db_adviser_log_view->where($where)->field('oi.order_id')->limit($page_row->limit())->select();
			foreach ($order_ids as $val) {
				$where['oi.order_id'][] = $val['order_id']; 
			}

			$data = $db_adviser_log_view->join(array('order_info', 'adviser', 'order_goods'))->where($where)->field($field)->limit($page_row->limit())->select();		
		}
		
		RC_Lang::load('orders/order');

		$order_list = array();
		if (!empty($data)) {
			$goods_db = RC_Loader::load_app_model('goods_model', 'goods');
			$order_id = $goods_number = $goods_type_number = 0;
			foreach ($data as $val) {
				if ($order_id == 0 || $val['order_id'] != $order_id ) {
					$goods_number = $goods_type_number = 0;
					$goods_type_number ++;
					$goods_number += isset($val['goods_number']) ? $val['goods_number'] : 0;
					
					$goods_lists = array();
					$goods_list = $goods_db->find(array('goods_id' => $val['goods_id']));
					$order_status = ($val['order_status'] != '2' || $val['order_status'] != '3') ? RC_Lang::lang('os/'.$val['order_status']) : '';
					$order_status = $val['order_status'] == '2' ? __('已取消') : $order_status;
					$order_status = $val['order_status'] == '3' ? __('无效') : $order_status;
					
					$payment_method = RC_Loader::load_app_class('payment_method', 'payment');
					if ($val['pay_id'] > 0) {
						$payment = $payment_method->payment_info_by_id($val['pay_id']);
					}
					
					if (in_array($val['order_status'], array(OS_CONFIRMED, OS_SPLITED)) &&
					in_array($val['shipping_status'], array(SS_RECEIVED)) &&
					in_array($val['pay_status'], array(PS_PAYED, PS_PAYING)))
					{
						$label_order_status = '已完成';
						$status_code = 'finished';
					}
					elseif (in_array($val['shipping_status'], array(SS_SHIPPED)))
					{
						$label_order_status = '待收货';
						$status_code = 'shipped';
					}
					elseif (in_array($val['order_status'], array(OS_CONFIRMED, OS_SPLITED, OS_UNCONFIRMED)) &&
							in_array($val['pay_status'], array(PS_UNPAYED)) &&
							(in_array($val['shipping_status'], array(SS_SHIPPED, SS_RECEIVED)) || !$payment['is_cod']))
					{
						$label_order_status = '待付款';
						$status_code = 'await_pay';
					}
					elseif (in_array($val['order_status'], array(OS_UNCONFIRMED, OS_CONFIRMED, OS_SPLITED, OS_SPLITING_PART)) &&
							in_array($val['shipping_status'], array(SS_UNSHIPPED, SS_PREPARING, SS_SHIPPED_ING)) &&
							(in_array($val['pay_status'], array(PS_PAYED, PS_PAYING)) || $payment['is_cod']))
					{
						$label_order_status = '待发货';
						$status_code = 'await_ship';
					}
					elseif (in_array($val['order_status'], array(OS_CANCELED))) {
						$label_order_status = '已取消';
						$status_code = 'canceled';
					}
						
// 					if ($device_code == '8001') {
// 						$goods_lists[] = array(
// 								'goods_id'	=> $val['goods_id'],
// 								'name'		=> $val['goods_name'],
// 								'img'		=> array(
// 										'thumb'	=> RC_Uri::admin_url('statics/images/nopic.png'),
// 										'url'	=> RC_Uri::admin_url('statics/images/nopic.png'),
// 										'small'	=> RC_Uri::admin_url('statics/images/nopic.png')
											
// 								)
// 						);
// 					} else {
						$goods_lists[] = array(
								'goods_id'	=> $val['goods_id'],
								'name'		=> $val['goods_name'],
								'img'		=> array(
										'thumb'	=>(isset($val['goods_img']) && !empty($val['goods_img'])) ? RC_Upload::upload_url($val['goods_img']) : RC_Uri::admin_url('statics/images/nopic.png'),
										'url'	=> (isset($val['original_img']) && !empty($val['original_img'])) ? RC_Upload::upload_url($val['original_img']) : RC_Uri::admin_url('statics/images/nopic.png'),
										'small'	=> (isset($val['goods_thumb']) && !empty($val['goods_thumb'])) ? RC_Upload::upload_url($val['goods_thumb']) : RC_Uri::admin_url('statics/images/nopic.png')
											
								)
						);
// 					}
					$order_list[] = array(
							'order_id'	=> $val['order_id'],
							'order_sn'	=> $val['order_sn'],
							'total_fee' => $val['total_fee'],
							'pay_name'	=> $val['pay_name'],
							'consignee' => $val['consignee'],
							'mobile'	=> empty($val['mobile']) ? $val['tel'] : $val['mobile'],
							'formated_total_fee' 		=> price_format($val['total_fee'], false),
							'formated_integral_money'	=> price_format($val['integral_money'], false),
							'formated_bonus'			=> price_format($val['bonus'], false),
							'formated_shipping_fee'		=> price_format($val['shipping_fee'], false),
							'formated_discount'			=> price_format($val['discount'], false),
							'status'					=> $order_status.','.RC_Lang::lang('ps/'.$val['pay_status']).','.RC_Lang::lang('ss/'.$val['shipping_status']),
							'label_order_status'		=> $label_order_status,
							'goods_number'				=> intval($goods_number),
							'create_time' 				=> RC_Time::local_date(ecjia::config('date_format'), $val['add_time']),
							'username' 					=> $val['username'],
							'goods_items' 				=> $goods_lists
					);	
				} else {
					$goods_number += isset($val['goods_number']) ? $val['goods_number'] : 0;
				}
		    }
		} 

		$pager = array(
				"total" => $page_row->total_records,
				"count" => $page_row->total_records,
				"more" => $page_row->total_pages <= $page ? 0 : 1,
		);
		
		
		
		EM_Api::outPut($order_list, $pager);
		

	} 
}


// end