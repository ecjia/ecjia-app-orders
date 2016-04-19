<?php
defined('IN_ECJIA') or exit('No permission resources.');
/**
 * 订单列表接口
 * @author 
 *
 */
class orders_order_list_api extends Component_Event_Api {
	/**
	 * 查看订单列表
	 * @param array $options
	 * @return  array
	 */
	public function call (&$options) {
		if (!is_array($options) || !isset($options['type'])) {
			return new ecjia_error('invalid_parameter', '参数无效');
		}
		
		$user_id	= $_SESSION['user_id'];
		$type		= !empty($options['type']) ? $options['type'] : '';
		
		$size = $options['size'];
		$page = $options['page'];
		
		
		$orders = $this->user_orders_list($user_id, $type, $page, $size);
		
		return $orders;
	}
	
	/**
	 *  获取用户指定范围的订单列表
	 *
	 * @access  public
	 * @param   int         $user_id        用户ID号
	 * @param   int         $num            列表最大数量
	 * @param   int         $start          列表起始位置
	 * @return  array       $order_list     订单列表
	 */
	private function user_orders_list($user_id, $type = '', $page = 1, $size = 15)
	{
		/**
		 * await_pay 待付款
		 * await_ship 待发货
		 * shipped 待收货
		 * finished 已完成
		 */
		$dbview_order_info = RC_Model::model('orders/order_info_viewmodel');
		$dbview_order_info->view = array(
				'order_info' => array(
						'type'	=> Component_Model_View::TYPE_LEFT_JOIN,
						'alias'	=> 'oii',
						'on'	=> 'oi.order_id = oii.main_order_id'
				),
				'order_goods' => array(
						'type'  =>	Component_Model_View::TYPE_LEFT_JOIN,
						'alias'	=>	'og',
						'on'    =>	'oi.order_id = og.order_id ',
				),
				'goods' => array(
						'type' 		=> Component_Model_View::TYPE_LEFT_JOIN,
						'alias' 	=> 'g',
						'on' 		=> 'og.goods_id = g.goods_id'
				),
				'term_relationship' => array(
						'type' 		=> Component_Model_View::TYPE_LEFT_JOIN,
						'alias' 	=> 'tr',
						'on' 		=> 'tr.object_id = og.rec_id'
				),
		);
		
		RC_Loader::load_app_class('order_list', 'orders', false);
		$where = array('oi.user_id' => $user_id, 'oi.extension_code' => '', 'oii.order_id is null');
		
		if (!empty($type)) {
			$order_type = 'order_'.$type;
			$where = order_list::$order_type('oi.');
		}
		
		$record_count = $dbview_order_info->join(array('order_info'))->where($where)->count('*');
		//实例化分页
		$page_row = new ecjia_page($record_count, $size, 6, '', $page);
		
		$order_group = $dbview_order_info->join(array('order_info'))->field('oi.order_id')->where($where)->order(array('oi.add_time' => 'desc'))->limit($page_row->limit())->select();
		
		if (empty($order_group)) {
			return array('order_list' => array(), 'page' => $page_row);
		} else {
			foreach ($order_group as $val) {
				$where['oi.order_id'][] = $val['order_id'];
			}
			
		}
		$field = 'oi.order_id, oi.order_sn, oi.order_status, oi.shipping_status, oi.pay_status, oi.add_time, (oi.goods_amount + oi.shipping_fee + oi.insure_fee + oi.pay_fee + oi.pack_fee + oi.card_fee + oi.tax - oi.integral_money - oi.bonus - oi.discount) AS total_fee, oi.discount, oi.integral_money, oi.bonus, oi.shipping_fee, oi.pay_id, oi.order_amount'.
		', og.*, og.goods_price * og.goods_number AS subtotal, g.goods_thumb, g.original_img, g.goods_img, tr.relation_id';

		$res = $dbview_order_info->join(array('order_info', 'order_goods', 'goods', 'term_relationship'))->field($field)->where($where)->order(array('oi.order_id' => 'desc'))->select();
		
		RC_Lang::load('orders/order');

		/* 取得订单列表 */
		$orders = array();
		if (!empty($res)) {
			$order_id = $goods_number = $goods_type_number = 0;
			$payment_method = RC_Loader::load_app_class('payment_method', 'payment');
			foreach ($res as $row) {
				$attr = array();
				if (isset($row['goods_attr']) && !empty($row['goods_attr'])) {
					$goods_attr = explode("\n", $row['goods_attr']);
					$goods_attr = array_filter($goods_attr);
					foreach ($goods_attr as  $val) {
						$a = explode(':',$val);
						if (!empty($a[0]) && !empty($a[1])) {
							$attr[] = array('name' => $a[0], 'value' => $a[1]);
						}
					}
				}
				
				if ($order_id == 0 || $row['order_id'] != $order_id ) {
					$goods_number = $goods_type_number = 0;
					if ($row['pay_id'] > 0) {
						$payment = $payment_method->payment_info_by_id($row['pay_id']);
					}
					$goods_type_number ++;
					$subject = $row['goods_name'].'等'.$goods_type_number.'种商品';
					$goods_number += isset($row['goods_number']) ? $row['goods_number'] : 0;
					
					
					if (in_array($row['order_status'], array(OS_CONFIRMED, OS_SPLITED)) && 
						in_array($row['shipping_status'], array(SS_RECEIVED)) && 
						in_array($row['pay_status'], array(PS_PAYED, PS_PAYING))) 
					{
						$label_order_status = '已完成';
					}
					
					if (in_array($row['order_status'], array(OS_UNCONFIRMED, OS_CONFIRMED, OS_SPLITED, OS_SPLITING_PART)) && 
						in_array($row['shipping_status'], array(SS_UNSHIPPED, SS_PREPARING, SS_SHIPPED_ING)) &&
						(in_array($row['pay_status'], array(PS_PAYED, PS_PAYING)) || $payment['is_cod']))
					{
						$label_order_status = '待发货';
					}
					
					if (in_array($row['order_status'], array(OS_CONFIRMED, OS_SPLITED, OS_UNCONFIRMED)) &&
					in_array($row['pay_status'], array(PS_UNPAYED)) &&
					(in_array($row['shipping_status'], array(SS_SHIPPED, SS_RECEIVED)) || !$payment['is_cod']))
					{
						$label_order_status = '待付款';
					} 
					elseif (in_array($row['order_status'], array(OS_CONFIRMED)) &&
					in_array($row['shipping_status'], array(SS_SHIPPED)))
					{
						$label_order_status = '待收货';
					}
					
					$orders[$row['order_id']] = array(
							'order_id'					=> $row['order_id'],
							'order_sn'					=> $row['order_sn'],
							'order_status'				=> $row['order_status'],
							'shipping_status'			=> $row['shipping_status'],
							'pay_status'				=> $row['pay_status'],
							'label_order_status'		=> $label_order_status,
							'order_time'				=> RC_Time::local_date(ecjia::config('time_format'), $row['add_time']),
							'total_fee'					=> $row['total_fee'],
							'discount'					=> $row['discount'],
							'goods_number'				=> $goods_number,
							'is_cod'					=> $payment['is_cod'],
							'formated_total_fee'		=> price_format($row['total_fee'], false), // 订单总价
							'formated_integral_money'	=> price_format($row['integral_money'], false),//积分 钱
							'formated_bonus'			=> price_format($row['bonus'], false),//红包 钱
							'formated_shipping_fee'		=> price_format($row['shipping_fee'], false),//运送费
							'formated_discount'			=> price_format($row['discount'], false), //折扣
							'order_info'				=> array(
									'pay_code'		=> isset($payment['pay_code']) ? $payment['pay_code'] : '',
									'order_amount'	=> $row['order_amount'],
									'order_id'		=> $row['order_id'],
									'subject'		=> $subject,
									'desc'			=> $subject,
									'order_sn'		=> $row['order_sn'],
							),
							'goods_list'				=> array(
										array(
											'goods_id'	=> isset($row['goods_id']) ? $row['goods_id'] : 0,
											'name'		=> isset($row['goods_name']) ? $row['goods_name'] : '',
											'goods_attr'	=> empty($attr) ? '' : $attr,
											'goods_number'	=> isset($row['goods_number']) ? $row['goods_number'] : 0,
											'subtotal'		=> isset($row['subtotal']) ? price_format($row['subtotal'], false) : 0,
											'formated_shop_price' => isset($row['goods_price']) ? price_format($row['goods_price'], false) : 0,
											'img' => array(
													'small'	=> (isset($row['goods_thumb']) && !empty($row['goods_thumb'])) ? RC_Upload::upload_url($row['goods_thumb']) : '',
													'thumb'	=> (isset($row['goods_img']) && !empty($row['goods_img'])) ? RC_Upload::upload_url($row['goods_img']) : '',
													'url'	=> (isset($row['original_img']) && !empty($row['original_img'])) ? RC_Upload::upload_url($row['original_img']) : '',
											),
											'is_commented'	=> empty($row['relation_id']) ? 0 : 1,
									)
							)
					);
					
					$order_id = $row['order_id'];
				} else {
					$goods_number += isset($row['goods_number']) ? $row['goods_number'] : 0;
					$orders[$row['order_id']]['goods_number'] = $goods_number;
					$goods_type_number ++;
					$subject = $row['goods_name'].'等'.$goods_type_number.'种商品';
					$orders[$row['order_id']]['order_info']['subject']	= $subject;
					$orders[$row['order_id']]['order_info']['desc']		= $subject;
					$orders[$row['order_id']]['goods_list'][] = array(
									'goods_id'	=> isset($row['goods_id']) ? $row['goods_id'] : 0,
									'name'		=> isset($row['goods_name']) ? $row['goods_name'] : '',
									'goods_attr'	=> empty($attr) ? '' : $attr,
									'goods_number'	=> isset($row['goods_number']) ? $row['goods_number'] : 0,
									'subtotal'		=> isset($row['subtotal']) ? price_format($row['subtotal'], false) : 0,
									'formated_shop_price' => isset($row['goods_price']) ? price_format($row['goods_price'], false) : 0,
									'img' => array(
											'small'	=> (isset($row['goods_thumb']) && !empty($row['goods_thumb'])) ? RC_Upload::upload_url($row['goods_thumb']) : '',
											'thumb'	=> (isset($row['goods_img']) && !empty($row['goods_img'])) ? RC_Upload::upload_url($row['goods_img']) : '',
											'url'	=> (isset($row['original_img']) && !empty($row['original_img'])) ? RC_Upload::upload_url($row['original_img']) : '',
									),
									'is_commented'	=> empty($row['relation_id']) ? 0 : 1,
					);
					
				}
			}
		}
		$orders = array_merge($orders);
		
		return array('order_list' => $orders, 'page' => $page_row);
	}
}


// end