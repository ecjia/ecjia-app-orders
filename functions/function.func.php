<?php
defined('IN_ECJIA') or exit('No permission resources.');
/**
 * ECJIA function库
 */

/**
 * admin.php
 */
/**
 * 取得状态列表
 * @param   string  $type   类型：all | order | shipping | payment
 */
function get_status_list($type = 'all') 
{
	$list = array();
	if ($type == 'all' || $type == 'order') {
		$pre = $type == 'all' ? 'os_' : '';
		foreach (RC_Lang::lang('os') AS $key => $value) {
			$list[$pre . $key] = $value;
		}
	}

	if ($type == 'all' || $type == 'shipping') {
		$pre = $type == 'all' ? 'ss_' : '';
		foreach (RC_Lang::lang('ss') AS $key => $value) {
			$list[$pre . $key] = $value;
		}
	}

	if ($type == 'all' || $type == 'payment') {
		$pre = $type == 'all' ? 'ps_' : '';
		foreach (RC_Lang::lang('ps') AS $key => $value) {
			$list[$pre . $key] = $value;
		}
	}
	return $list;
}

/**
 * 退回余额、积分、红包（取消、无效、退货时），把订单使用余额、积分、红包设为0
 * @param   array   $order  订单信息
 */
function return_user_surplus_integral_bonus($order) 
{
	$db = RC_Loader::load_app_model('order_info_model', 'orders');
	/* 处理余额、积分、红包 */
	if ($order['user_id'] > 0 && $order['surplus'] > 0) {
		$surplus = $order['money_paid'] < 0 ? $order['surplus'] + $order['money_paid'] : $order['surplus'];
		$options = array(
			'user_id'		=> $order['user_id'],
			'user_money'	=> $surplus,
			'change_desc'	=> sprintf(RC_Lang::lang('return_order_surplus'), $order['order_sn'])
		);
		RC_Api::api('user', 'account_change_log',$options);
		
		$data = array(
			'order_amount' => '0'
		);
		$db->where(array('order_id' => $order['order_id']))->update($data);
	}

	if ($order['user_id'] > 0 && $order['integral'] > 0) {
		$options = array(
			'user_id'		=> $order['user_id'],
			'pay_points'	=> $order['integral'],
			'change_desc'	=> sprintf(RC_Lang::lang('return_order_integral'), $order['order_sn'])
		);
		RC_Api::api('user', 'account_change_log',$options);

	}

	if ($order['bonus_id'] > 0) {
		RC_Loader::load_app_func('bonus','bonus');
		unuse_bonus($order['bonus_id']);
	}

	/* 修改订单 */
	$arr = array(
		'bonus_id'			=> 0,
		'bonus'				=> 0,
		'integral'			=> 0,
		'integral_money'	=> 0,
		'surplus'			=> 0
	);
	update_order($order['order_id'], $arr);
//        $GLOBALS['db']->query("UPDATE ". $GLOBALS['ecs']->table('order_info') . " SET `order_amount` = '0' WHERE `order_id` =". $order['order_id']);
// 		log_account_change($order['user_id'], $surplus, 0, 0, 0, sprintf(RC_Lang::lang('return_order_surplus'), $order['order_sn']));
// 		log_account_change($order['user_id'], 0, 0, 0, $order['integral'], sprintf(RC_Lang::lang('return_order_integral'), $order['order_sn']));
}

/**
 * 更新订单总金额
 * @param   int     $order_id   订单id
 * @return  bool
 */
function update_order_amount($order_id)
{
	$db = RC_Loader::load_app_model('order_info_model', 'orders');
	
	$query = $db->dec('order_amount','order_id='.$order_id,'order_amount+'.order_due_field());

	return $query;
}

/**
 * 返回某个订单可执行的操作列表，包括权限判断
 * @param   array   $order      订单信息 order_status, shipping_status, pay_status
 * @param   bool    $is_cod     支付方式是否货到付款
 * @return  array   可执行的操作  confirm, pay, unpay, prepare, ship, unship, receive, cancel, invalid, return, drop
 * 格式 array('confirm' => true, 'pay' => true)
 */
function operable_list($order) {
	/* 取得订单状态、发货状态、付款状态 */
	$os = $order['order_status'];
	$ss = $order['shipping_status'];
	$ps = $order['pay_status'];

	/* 取得订单操作权限 */
	$actions = $_SESSION['action_list'];
	if ($actions == 'all') {
		$priv_list	= array('os' => true, 'ss' => true, 'ps' => true, 'edit' => true);
	} else {
		$actions    = ',' . $actions . ',';
		$priv_list  = array(
			'os'	=> strpos($actions, ',order_os_edit,') !== false,
			'ss'	=> strpos($actions, ',order_ss_edit,') !== false,
			'ps'	=> strpos($actions, ',order_ps_edit,') !== false,
			'edit'	=> strpos($actions, ',order_edit,') !== false
		);
	}

	/* 取得订单支付方式是否货到付款 */
	$payment_method = RC_Loader::load_app_class('payment_method','payment');
	$payment = $payment_method->payment_info($order['pay_id']);

	$is_cod  = $payment['is_cod'] == 1;

	/* 根据状态返回可执行操作 */
	$list = array();
	if (OS_UNCONFIRMED == $os) {
		/* 状态：未确认 => 未付款、未发货 */
		if ($priv_list['os']) {
			$list['confirm']	= true;	// 确认
			$list['invalid']	= true;	// 无效
			$list['cancel']		= true;	// 取消
			if ($is_cod) {
				/* 货到付款 */
				if ($priv_list['ss']) {
					$list['prepare']	= true;	// 配货
					$list['split']		= true;	// 分单
				}
			} else {
				/* 不是货到付款 */
				if ($priv_list['ps']) {
					$list['pay'] = true;	// 付款
				}
			}
		}
	} elseif (OS_CONFIRMED == $os || OS_SPLITED == $os || OS_SPLITING_PART == $os) {
		/* 状态：已确认 */
		if (PS_UNPAYED == $ps) {
			/* 状态：已确认、未付款 */
			if (SS_UNSHIPPED == $ss || SS_PREPARING == $ss) {
				/* 状态：已确认、未付款、未发货（或配货中） */
				if ($priv_list['os']) {
					$list['cancel'] = true;		// 取消
					$list['invalid'] = true;	// 无效
				}
				if ($is_cod) {
					/* 货到付款 */
					if ($priv_list['ss']) {
						if (SS_UNSHIPPED == $ss) {
							$list['prepare'] = true;	// 配货
						}
						$list['split'] = true;	// 分单
					}
				} else {
					/* 不是货到付款 */
					if ($priv_list['ps']) {
						$list['pay'] = true;	// 付款
					}
				}
			} elseif (SS_SHIPPED_ING == $ss || SS_SHIPPED_PART == $ss) {
				/* 状态：已确认、未付款、发货中 */
				// 部分分单
				if (OS_SPLITING_PART == $os) {
					$list['split'] = true;		// 分单
				}
				$list['to_delivery'] = true;	// 去发货
			} else {
				/* 状态：已确认、未付款、已发货或已收货 => 货到付款 */
				if ($priv_list['ps']) {
					$list['pay'] = true;	// 付款
				}
				if ($priv_list['ss']) {
					if (SS_SHIPPED == $ss) {
						$list['receive'] = true;	// 收货确认
					}
					$list['unship'] = true;	// 设为未发货
					if ($priv_list['os']) {
						$list['return'] = true;	// 退货
					}
				}
			}
		} else {
			/* 状态：已确认、已付款和付款中 */
			if (SS_UNSHIPPED == $ss || SS_PREPARING == $ss) {
				/* 状态：已确认、已付款和付款中、未发货（配货中） => 不是货到付款 */
				if ($priv_list['ss']) {
					if (SS_UNSHIPPED == $ss) {
						$list['prepare'] = true;	// 配货
					}
					$list['split'] = true;	// 分单
				}
				if ($priv_list['ps']) {
					$list['unpay'] = true;	// 设为未付款
					if ($priv_list['os']) {
						$list['cancel'] = true;	// 取消
					}
				}
			} elseif (SS_SHIPPED_ING == $ss || SS_SHIPPED_PART == $ss) {
				/* 状态：已确认、未付款、发货中 */
				// 部分分单
				if (OS_SPLITING_PART == $os) {
					$list['split'] = true;	// 分单
				}
				$list['to_delivery'] = true;	// 去发货
			} else {
				/* 状态：已确认、已付款和付款中、已发货或已收货 */
				if ($priv_list['ss']) {
					if (SS_SHIPPED == $ss) {
						$list['receive'] = true;	// 收货确认
					}
					if (!$is_cod) {
						$list['unship'] = true;	// 设为未发货
					}
				}
				if ($priv_list['ps'] && $is_cod) {
					$list['unpay']  = true;	// 设为未付款
				}
				if ($priv_list['os'] && $priv_list['ss'] && $priv_list['ps']) {
					$list['return'] = true;	// 退货（包括退款）
				}
			}
		}
	} elseif (OS_CANCELED == $os) {
		/* 状态：取消 */
		if ($priv_list['os']) {
			$list['confirm'] = true;
		}
		if ($priv_list['edit']) {
			$list['remove'] = true;
		}
	} elseif (OS_INVALID == $os) {
		/* 状态：无效 */
		if ($priv_list['os']) {
			$list['confirm'] = true;
		}
		if ($priv_list['edit']) {
			$list['remove'] = true;
		}
	} elseif (OS_RETURNED == $os) {
		/* 状态：退货 */
		if ($priv_list['os']) {
			$list['confirm'] = true;
		}
	}

	/* 修正发货操作 */
	if (!empty($list['split'])) {
		/* 如果是团购活动且未处理成功，不能发货 */
		
		if ($order['extension_code'] == 'group_buy') {
			RC_Loader::load_app_func('goods','goods');
			$group_buy = group_buy_info(intval($order['extension_id']));
			if ($group_buy['status'] != GBS_SUCCEED) {
				unset($list['split']);
				unset($list['to_delivery']);
			}
		}

		/* 如果部分发货 不允许 取消 订单 */
		if (order_deliveryed($order['order_id'])) {
			$list['return'] = true;	// 退货（包括退款）
			unset($list['cancel']);	// 取消
		}
	}

	/* 售后 */
	$list['after_service'] = true;
	return $list;
}

/**
 * 处理编辑订单时订单金额变动
 * @param   array   $order  订单信息
 * @param   array   $msgs   提示信息
 * @param   array   $links  链接信息
 */
function handle_order_money_change($order, &$msgs, &$links) {
	$order_id = $order['order_id'];
	if ($order['pay_status'] == PS_PAYED || $order['pay_status'] == PS_PAYING) {
		/* 应付款金额 */
		$money_dues = $order['order_amount'];
		if ($money_dues > 0) {
			/* 修改订单为未付款 */
			update_order($order_id, array('pay_status' => PS_UNPAYED, 'pay_time' => 0));
			$msgs[]		= RC_Lang::lang('amount_increase');
			$links[]	= array('text' => RC_Lang::lang('order_info'), 'href' => RC_Uri::url('orders/admin/info', 'order_id=' . $order_id));
		} elseif ($money_dues < 0) {
			$anonymous	= $order['user_id'] > 0 ? 0 : 1;
			$msgs[]		= RC_Lang::lang('amount_decrease');
			$links[]	= array('text' => RC_Lang::lang('refund'), 'href' => RC_Uri::url('orders/admin/process', 'func=load_refund&anonymous='.$anonymous.'&order_id=' . $order_id .'&refund_amount=' . abs($money_dues)));
		}
	}
}

/**
 * 更新订单对应的 pay_log
 * 如果未支付，修改支付金额；否则，生成新的支付log
 * @param   int     $order_id   订单id
 */
function update_pay_log($order_id) {
	$db_order	= RC_Loader::load_app_model('order_info_model', 'orders');
	$db_pay		= RC_Loader::load_app_model('pay_log_model', 'orders');
	
	$order_id = intval($order_id);
	if ($order_id > 0) {
		$order_amount = $db_order->field('order_amount')->find('order_id = "'.$order_id.'"');
		$order_amount = $order_amount['order_amount'];
		if (!is_null($order_amount)) {
			$query = $db_pay->field('log_id')->find('order_id = "'.$order_id.'" and order_type = "'.PAY_ORDER.'" and is_paid = 0');
			$log_id = intval($query['log_id']);
			if ($log_id > 0) {
				/* 未付款，更新支付金额 */
				$data = array(
					'order_amount' => $order_amount,
				);
				$db_pay->where(array('log_id' =>$log_id))->update($data);
			} else {
				/* 已付款，生成新的pay_log */
				$data = array(
					'order_id'		=> $order_id,
					'order_amount'	=> $order_amount,
					'order_type'	=> PAY_ORDER,
					'is_paid'		=> 0,
				);
				$db_pay->insert($data);
			}
		}
	}
}


/**
 * 取得订单商品
 * @param   array     $order  订单数组
 * @return array
 */
function get_order_goods($order) {
	$dbview = RC_Loader::load_app_model('order_order_goods_viewmodel', 'orders');

	$goods_list = array();
	$goods_attr = array();

	$dbview->view = array(
		'products' => array(
			'type'	=> Component_Model_View::TYPE_LEFT_JOIN,
			'alias'	=> 'p',
			'field'	=> "o.*, g.suppliers_id AS suppliers_id,IF(o.product_id > 0, p.product_number, g.goods_number) AS storage, o.goods_attr, IFNULL(b.brand_name, '') AS brand_name, p.product_sn",
			'on'	=> 'o.product_id = p.product_id ',
		),
		'goods' => array(
			'type'	=> Component_Model_View::TYPE_LEFT_JOIN,
			'alias'	=> 'g',
			'on'	=> 'o.goods_id = g.goods_id ',
		),
		'brand' => array(
			'type'	=> Component_Model_View::TYPE_LEFT_JOIN,
			'alias'	=> 'b',
			'on'	=> 'g.brand_id = b.brand_id ',
		)
	);

	$data = $dbview->where(array('o.order_id' => $order['order_id']))->select();

	foreach ($data as $key => $row) {
		// 虚拟商品支持
// 		TODO:虚拟商品语言项
// 		if ($row['is_real'] == 0) {
// 			/* 取得语言项 */
// 			$filename = ROOT_PATH . 'plugins/' . $row['extension_code'] . '/languages/common_' . ecjia::config('lang') . '.php';
// 			if (file_exists($filename)) {
// 				include_once($filename);
// 				if (!empty($GLOBALS['_LANG'][$row['extension_code'].'_link'])) {
// 					$row['goods_name'] = $row['goods_name'] . sprintf(RC_Lang::lang($row['extension_code'].'_link'), $row['goods_id'], $order['order_sn']);
// 				}
// 			}
// 		}

		$row['formated_subtotal']		= price_format($row['goods_price'] * $row['goods_number']);
		$row['formated_goods_price']	= price_format($row['goods_price']);

		$goods_attr[] = explode(' ', trim($row['goods_attr'])); //将商品属性拆分为一个数组

		if ($row['extension_code'] == 'package_buy') {
			$row['storage'] = '';
			$row['brand_name'] = '';
			$row['package_goods_list'] = get_package_goods_list($row['goods_id']);
		}

		//处理货品id
		$row['product_id'] = empty($row['product_id']) ? 0 : $row['product_id'];
		$goods_list[] = $row;
	}

	$attr	= array();
	$arr	= array();
	foreach ($goods_attr AS $index => $array_val) {
		foreach ($array_val AS $value) {
			$arr = explode(':', $value);//以 : 号将属性拆开
			$attr[$index][] =  @array('name' => $arr[0], 'value' => $arr[1]);
		}
	}
	return array('goods_list' => $goods_list, 'attr' => $attr);
}

/**
 * 取得礼包列表
 * @param   integer     $package_id  订单商品表礼包类商品id
 * @return array
 */
function get_package_goods_list($package_id) {
	$dbview		= RC_Loader::load_app_model('package_goods_viewmodel', 'orders');
	$db_goods	= RC_Loader::load_app_model('goods_attr_attribute_viewmodel', 'orders');
	
	$resource = $dbview->join(array('goods','products'))->where(array('pg.package_id' => $package_id))->select();
	if (!$resource) {
		return array();
	}

	$row = array();

	/* 生成结果数组 取存在货品的商品id 组合商品id与货品id */
	$good_product_str = '';

	if(!empty($resource)) {
		foreach ($resource as $key => $_row) {
			if ($_row['product_id'] > 0) {
				/* 取存商品id */
				$good_product_str .= ',' . $_row['goods_id'];

				/* 组合商品id与货品id */
				$_row['g_p'] = $_row['goods_id'] . '_' . $_row['product_id'];
			} else {
				/* 组合商品id与货品id */
				$_row['g_p'] = $_row['goods_id'];
			}

			//生成结果数组
			$row[] = $_row;
		}
	}
	$good_product_str = trim($good_product_str, ',');

	/* 释放空间 */
	unset($resource, $_row, $sql);

	/* 取商品属性 */
	if ($good_product_str != '') {
		$db_goods->view = array(
			'attribute' => array(
				'type'	=> Component_Model_View::TYPE_LEFT_JOIN,
				'alias'	=> 'a',
				'field'	=> 'ga.goods_attr_id, ga.attr_value, ga.attr_price, a.attr_name',
				'on'	=> 'ga.attr_id  = a.attr_id',
			),
		);
		$result_goods_attr = $db_goods->where(array('a.attr_type' => 1))->in(array('goods_id' => $good_product_str))->select();
		
		$_goods_attr = array();
		if(!empty($result_goods_attr)) {
			foreach ($result_goods_attr as $value) {
				$_goods_attr[$value['goods_attr_id']] = $value;
			}
		}
	}

	/* 过滤货品 */
	$format[0] = "%s:%s[%d] <br>";
	$format[1] = "%s--[%d]";
	foreach ($row as $key => $value) {
		if ($value['goods_attr'] != '') {
			$goods_attr_array = explode('|', $value['goods_attr']);

			$goods_attr = array();
			foreach ($goods_attr_array as $_attr) {
				$goods_attr[] = sprintf($format[0], $_goods_attr[$_attr]['attr_name'], $_goods_attr[$_attr]['attr_value'], $_goods_attr[$_attr]['attr_price']);
			}
			$row[$key]['goods_attr_str'] = implode('', $goods_attr);
		}
		$row[$key]['goods_name'] = sprintf($format[1], $value['goods_name'], $value['order_goods_number']);
	}
	return $row;
}

/**
 * 订单单个商品或货品的已发货数量
 *
 * @param   int     $order_id       订单 id
 * @param   int     $goods_id       商品 id
 * @param   int     $product_id     货品 id
 *
 * @return  int
 */
function order_delivery_num($order_id, $goods_id, $product_id = 0) {
	$dbview = RC_Loader::load_app_model('delivery_viewmodel', 'orders');

	$dbview->view = array(
		'delivery_order' => array(
			'type'	=> Component_Model_View::TYPE_LEFT_JOIN,
			'alias'	=> 'o',
			'on'	=> 'o.delivery_id = dg.delivery_id',
		)
	);
	
	if($product_id > 0) {
		$sum = $dbview->where('o.status = 0 and o.order_id = "'.$order_id.'" and dg.extension_code <> "package_buy" and dg.goods_id = "'. $goods_id.'" and dg.product_id = "'.$product_id.'"')->sum('dg.send_number | sums');
	} else {
		$sum = $dbview->where('o.status = 0 and o.order_id = "'.$order_id.'" and dg.extension_code <> "package_buy" and dg.goods_id = "'. $goods_id.'"')->sum('dg.send_number | sums');
	}

	if (empty($sum)) {
		$sum = 0;
	}
	return $sum;
}

/**
 * 判断订单是否已发货（含部分发货）
 * @param   int     $order_id  订单 id
 * @return  int     1，已发货；0，未发货
 */
function order_deliveryed($order_id) {
	$db = RC_Loader::load_app_model('delivery_order_model', 'orders');
	$return_res = 0;

	if (empty($order_id)) {
		return $return_res;
	}
	$sum = $db->where(array('order_id' => $order_id, 'status' => 0))->count('delivery_id');
	if ($sum) {
		$return_res = 1;
	}
	return $return_res;
}

/**
 * 更新订单商品信息
 * @param   int     $order_id       订单 id
 * @param   array   $_sended        Array(‘商品id’ => ‘此单发货数量’)
 * @param   array   $goods_list
 * @return  Bool
 */
function update_order_goods($order_id, $_sended, $goods_list = array()) {
	$db = RC_Loader::load_app_model('order_goods_model', 'orders');
	
	if (!is_array($_sended) || empty($order_id)) {
		return false;
	}

	foreach ($_sended as $key => $value) {
		// 超值礼包
		if (is_array($value)) {
			if (!is_array($goods_list)) {
				$goods_list = array();
			}
			foreach ($goods_list as $goods) {
				if (($key != $goods['rec_id']) || (!isset($goods['package_goods_list']) || !is_array($goods['package_goods_list']))) {
					continue;
				}

				$goods['package_goods_list'] = package_goods($goods['package_goods_list'], $goods['goods_number'], $goods['order_id'], $goods['extension_code'], $goods['goods_id']);
				$pg_is_end = true;

				foreach ($goods['package_goods_list'] as $pg_key => $pg_value) {
					if ($pg_value['order_send_number'] != $pg_value['sended']) {
						$pg_is_end = false; // 此超值礼包，此商品未全部发货
						break;
					}
				}

				// 超值礼包商品全部发货后更新订单商品库存
				if ($pg_is_end) {
// 					$data = array(
// 						'send_number' => goods_number,
// 					);
// 					$data = 'send_number=goods_number';
// 					$db->where(array('order_id' => $order_id, 'goods_id' => $goods['goods_id']))->update($data);
					$db->inc('send_number','order_id='.$order_id. ' and goods_id='.$goods['goods_id'],'0,send_number=goods_number');
				}
			}
		} elseif (!is_array($value)) {
			// 商品（实货）（货品）
			/* 检查是否为商品（实货）（货品） */
			foreach ($goods_list as $goods) {
				if ($goods['rec_id'] == $key && $goods['is_real'] == 1) {
// 					$data = array(
// 						'send_number' => send_number + $value,
// 					);
					
// 					$data = 'send_number = send_number +'.$value;
// 					$db->inc('send_number','order_id='.$order_id. ' and rec_id='.$key,$value);
					$db->inc('send_number','order_id='.$order_id. ' and rec_id='.$key,'0,send_number=goods_number');
					break;
				}
			}
		}
	}
	return true;
}

/**
 * 更新订单虚拟商品信息
 * @param   int     $order_id       订单 id
 * @param   array   $_sended        Array(‘商品id’ => ‘此单发货数量’)
 * @param   array   $virtual_goods  虚拟商品列表
 * @return  Bool
 */
function update_order_virtual_goods($order_id, $_sended, $virtual_goods) {
	$db = RC_Loader::load_app_model('order_goods_model', 'orders');

	if (!is_array($_sended) || empty($order_id)) {
		return false;
	}
	if (empty($virtual_goods)) {
		return true;
	}
	elseif (!is_array($virtual_goods)) {
		return false;
	}

	foreach ($virtual_goods as $goods) {
// 		$data = array(
// 			'send_number' => send_number + $goods['num'],
// 		);
// 		$data = 'send_number = send_number +'.$goods['num'];
// 		$query = $db->where(array('order_id' => $order_id, 'goods_id' => $goods['goods_id']))->update($data);
		$query = $db->inc('send_number','order_id='.$order_id. ' and goods_id='.$goods['goods_id'],$goods['num']);
		
		if (!$query) {
			return false;
		}
	}
	return true;
}

/**
 * 订单中的商品是否已经全部发货
 * @param   int     $order_id  订单 id
 * @return  int     1，全部发货；0，未全部发货
 */
function get_order_finish($order_id) {
// 	$db = RC_Loader::load_app_model('order_goods_model', 'orders');
	$db_order_goods = RC_DB::table('order_goods');
	
	$return_res = 0;

	if (empty($order_id)) {
		return $return_res;
	}
// 	$where = array();
// 	$where['order_id'] = $order_id;
// 	$where[] = "goods_number > send_number";
// 	$sum = $db->where($where)->count('rec_id');
	
	$sum = $db_order_goods->where('order_id', $order_id)->where('goods_number', '>', 'send_number')->count('rec_id');

	if (empty($sum)) {
		$return_res = 1;
	}
	return $return_res;
}

function trim_array_walk(&$array_value) {
	if (is_array($array_value)) {
		array_walk($array_value, 'trim_array_walk');
	} else {
		$array_value = trim($array_value);
	}
}

function intval_array_walk(&$array_value) {
	if (is_array($array_value)) {
		array_walk($array_value, 'intval_array_walk');
	} else {
		$array_value = intval($array_value);
	}
}

/**
 * 删除发货单(不包括已退货的单子)
 * @param   int     $order_id  订单 id
 * @return  int     1，成功；0，失败
 */
function del_order_delivery($order_id) {
	$db_order = RC_Loader::load_app_model('delivery_order_model', 'orders');
	$db_goods = RC_Loader::load_app_model('delivery_goods_model', 'orders');
	$return_res = 0;

	if (empty($order_id)) {
		return $return_res;
	}
	//查找delivery_id 
	$delivery_id = $db_order->field('delivery_id')->where('order_id = "'.$order_id.'" and status = 0')->select();
	if(!empty($delivery_id)) {
		if(is_array($delivery_id)) {
			foreach ($delivery_id as $key => $val) {
				//删除记录 (表 delivery_goods)
				$db_goods->where(array('delivery_id' => $val))->delete();
			}
		}
		$query = $db_goods->where(array('delivery_id' => $delivery_id))->delete();
		$query .= $db_order->where(array('order_id' => $order_id, 'status' => 0))->delete();
	}

	if ($query) {
		$return_res = 1;
	}

	return $return_res;
}

/**
 * 删除订单所有相关单子
 * @param   int     $order_id      订单 id
 * @param   int     $action_array  操作列表 Array('delivery', 'back', ......)
 * @return  int     1，成功；0，失败
 */
function del_delivery($order_id, $action_array) {
	$db_order = RC_Loader::load_app_model('delivery_order_model', 'orders');
	$db_goods = RC_Loader::load_app_model('delivery_goods_model', 'orders');
	
	$db_back_goods = RC_Loader::load_app_model('back_goods_model', 'orders');
	$db_back_order = RC_Loader::load_app_model('back_order_model', 'orders');
	
	$return_res = 0;

	if (empty($order_id) || empty($action_array)) {
		return $return_res;
	}

	$query_delivery = 1;
	$query_back = 1;
	if (in_array('delivery', $action_array)) {
		//查找delivery_id
		$delivery_id = $db_order->field('delivery_id')->where('order_id = "'.$order_id.'" ')->select();
		if(!empty($delivery_id)) {
			if(is_array($delivery_id)) {
				foreach ($delivery_id as $key => $val) {
					//删除记录 (表 delivery_goods)
					$db_goods->where(array('delivery_id' => $val))->delete();
				}
			}
			 
			$query_delivery = $db_goods->where(array('delivery_id' => $delivery_id))->delete();
			$query_delivery = $db_order->where(array('order_id' => $order_id))->delete();
		}
		
	}
	if (in_array('back', $action_array)) {
		//查找back_id
		$back_id = $db_back_order->field('back_id')->where(array('order_id' => $order_id))->select();
		if(!empty($back_id)) {
			if(is_array($back_id)) {
				foreach ($back_id as $key => $val) {
					//删除记录 (表 back_goods)
					$db_back_goods->where(array('back_id' => $val))->delete();
				}
			}
			 
			$query_back = $db_back_goods->where(array('back_id' => $back_id))->delete();
			$query_back = $db_back_order->where(array('order_id' => $order_id))->delete();
		}
		
	}

	if ($query_delivery && $query_back) {
		$return_res = 1;
	}
	return $return_res;
}


/**
 * 超级礼包发货数处理
 * @param   array   超级礼包商品列表
 * @param   int     发货数量
 * @param   int     订单ID
 * @param   varchar 虚拟代码
 * @param   int     礼包ID
 * @return  array   格式化结果
 */
function package_goods(&$package_goods, $goods_number, $order_id, $extension_code, $package_id) {
	$return_array = array();

	if (count($package_goods) == 0 || !is_numeric($goods_number)) {
		return $return_array;
	}

	foreach ($package_goods as $key=>$value) {
		$return_array[$key]							= $value;
		$return_array[$key]['order_send_number']	= $value['order_goods_number'] * $goods_number;
		$return_array[$key]['sended']				= package_sended($package_id, $value['goods_id'], $order_id, $extension_code, $value['product_id']);
		$return_array[$key]['send']					= ($value['order_goods_number'] * $goods_number) - $return_array[$key]['sended'];
		$return_array[$key]['storage']				= $value['goods_number'];


		if ($return_array[$key]['send'] <= 0) {
			$return_array[$key]['send']		= RC_Lang::lang('act_good_delivery');
			$return_array[$key]['readonly']	= 'readonly="readonly"';
		}

		/* 是否缺货 */
		if ($return_array[$key]['storage'] <= 0 && ecjia::config('use_storage') == '1') {
			$return_array[$key]['send']		= RC_Lang::lang('act_good_vacancy');
			$return_array[$key]['readonly']	= 'readonly="readonly"';
		}
	}
	return $return_array;
}

/**
 * 获取超级礼包商品已发货数
 *
 * @param       int         $package_id         礼包ID
 * @param       int         $goods_id           礼包的产品ID
 * @param       int         $order_id           订单ID
 * @param       varchar     $extension_code     虚拟代码
 * @param       int         $product_id         货品id
 *
 * @return  int     数值
 */
function package_sended($package_id, $goods_id, $order_id, $extension_code, $product_id = 0) {
	$dbview = RC_Loader::load_app_model('delivery_viewmodel', 'orders');

	if (empty($package_id) || empty($goods_id) || empty($order_id) || empty($extension_code)) {
		return false;
	}

	$dbview->view = array(
		'delivery_order' => array(
			'type'	=> Component_Model_View::TYPE_LEFT_JOIN,
			'alias'	=> 'o',
			'on'	=> 'o.delivery_id = dg.delivery_id',
		)
	);

	if($product_id > 0) {
		$send = $dbview->where('o.order_id = "'.$order_id.'" AND dg.parent_id = "'.$package_id.'" AND dg.goods_id = "'.$goods_id.'" AND dg.extension_code = "'.$extension_code.'" and dg.product_id = "'.$product_id.'"')->in(array('o.status'=>array(0,2)))->sum('dg.send_number');
	} else {
		$send = $dbview->where('o.order_id = "'.$order_id.'" AND dg.parent_id = "'.$package_id.'" AND dg.goods_id = "'.$goods_id.'" AND dg.extension_code = "'.$extension_code.'"')->in(array('o.status'=>array(0,2)))->sum('dg.send_number');
	}

	return empty($send) ? 0 : $send;
}

/**
 * 改变订单中商品库存
 * @param   int     $order_id  订单 id
 * @param   array   $_sended   Array(‘商品id’ => ‘此单发货数量’)
 * @param   array   $goods_list
 * @return  Bool
 */
function change_order_goods_storage_split($order_id, $_sended, $goods_list = array()) {
	$db = RC_Loader::load_app_model('goods_model', 'goods');
	/* 参数检查 */
	if (!is_array($_sended) || empty($order_id)) {
		return false;
	}

	foreach ($_sended as $key => $value) {
		// 商品（超值礼包）
		if (is_array($value)) {
			if (!is_array($goods_list)) {
				$goods_list = array();
			}
			foreach ($goods_list as $goods) {
				if (($key != $goods['rec_id']) || (!isset($goods['package_goods_list']) || !is_array($goods['package_goods_list']))) {
					continue;
				}

				// 超值礼包无库存，只减超值礼包商品库存
				foreach ($goods['package_goods_list'] as $package_goods) {
					if (!isset($value[$package_goods['goods_id']])) {
						continue;
					}

					// 减库存：商品（超值礼包）（实货）、商品（超值礼包）（虚货）
// 					$data = array(
// 						'goods_number' => goods_number - $value[$package_goods['goods_id']]
// 					);
					$data = 'goods_number = goods_number -'.$value[$package_goods['goods_id']];
					$db->where(array('goods_id' => $package_goods['goods_id']))->update($data);
				}
			}
		} elseif (!is_array($value)) {
			// 商品（实货）
			/* 检查是否为商品（实货） */
			foreach ($goods_list as $goods) {
				if ($goods['rec_id'] == $key && $goods['is_real'] == 1) {
// 					$data = array(
// 						'goods_number' => goods_number - $value
// 					);
					$data = 'goods_number = goods_number -'.$value;
// 					$db->where(array('goods_id' => $goods['goods_id']))->update($data);
					$query = $db->dec('goods_number','goods_id='.$goods['goods_id'],$value);
					break;
				}
			}
		}
	}

	return true;
}

/**
 *  超值礼包虚拟卡发货、跳过修改订单商品发货数的虚拟卡发货
 *
 * @access  public
 * @param   array      $goods      超值礼包虚拟商品列表数组
 * @param   string      $order_sn   本次操作的订单
 *
 * @return  boolen
 */
function package_virtual_card_shipping($goods, $order_sn) {
	$db			= RC_Loader::load_app_model('virtual_card_model', 'goods');
	$db_order	= RC_Loader::load_app_model('order_info_model', 'orders');
	if (!is_array($goods)) {
		return false;
	}

	/* 包含加密解密函数所在文件 */
// 	include_once(ROOT_PATH . 'includes/lib_code.php');

	// 取出超值礼包中的虚拟商品信息
	foreach ($goods as $virtual_goods_key => $virtual_goods_value) {
		/* 取出卡片信息 */
		$arr = $db->field('card_id, card_sn, card_password, end_date, crc32')->where(array('goods_id' => $virtual_goods_value['goods_id'], 'is_saled' => 0))->limit($virtual_goods_value['num'])->select();
		/* 判断是否有库存 没有则推出循环 */
		if (count($arr) == 0) {
			continue;
		}

		$card_ids = array();
		$cards = array();

		foreach ($arr as $virtual_card) {
			$card_info = array();

			/* 卡号和密码解密 */
			if ($virtual_card['crc32'] == 0 || $virtual_card['crc32'] == crc32(AUTH_KEY)) {
				$card_info['card_sn']		= RC_Script::decrypt($virtual_card['card_sn']);
				$card_info['card_password']	= RC_Script::decrypt($virtual_card['card_password']);
			} elseif ($virtual_card['crc32'] == crc32(OLD_AUTH_KEY)) {
				$card_info['card_sn']		= RC_Script::decrypt($virtual_card['card_sn'], OLD_AUTH_KEY);
				$card_info['card_password']	= RC_Script::decrypt($virtual_card['card_password'], OLD_AUTH_KEY);
			} else {
				return false;
			}
			$card_info['end_date']	= date(ecjia::config('date_format'), $virtual_card['end_date']);
			$card_ids[]				= $virtual_card['card_id'];
			$cards[]				= $card_info;
		}

		/* 标记已经取出的卡片 */
		$data = array(
			'is_saled' => 1,
			'order_sn' => $order_sn
		);

		$query = $db->in(array('card_id' => $card_ids))->update($data);


		if(!$query) {
			return false;
		}

		/* 获取订单信息 */
		$order = $db_order->field('order_id, order_sn, consignee, email')->find(array('order_sn' => $order_sn));

		$cfg = ecjia::config('send_ship_email');
		if ($cfg == '1') {
			/* 发送邮件 */
			$this->assign('virtual_card'	, $cards);
			$this->assign('order'			, $order);
			$this->assign('goods'			, $virtual_goods_value);
			$this->assign('send_time'		, date('Y-m-d H:i:s'));
			$this->assign('shop_name'		, ecjia::config('shop_name'));
			$this->assign('send_date'		, date('Y-m-d'));
			$this->assign('sent_date'		, date('Y-m-d'));

			//$tpl = get_mail_template('virtual_card');
			$tpl_name = 'virtual_card';
			$tpl   = RC_Api::api('mail', 'mail_template', $tpl_name);
			
			$content = $this->fetch_string($tpl['template_content']);
			RC_Mail::send_mail($order['consignee'], $order['em'], $tpl['template_subject'], $content, $tpl['is_html']);
		}
	}

	return true;
}


/**
 * 获取站点根目录网址
 *
 * @access  private
 * @return  Bool
 */
function get_site_root_url() {
	return 'http://' . $_SERVER['HTTP_HOST'] . str_replace('/' . '/order.php', '', PHP_SELF);

}

/**
 * 获取区域名
 * @param 订单id $order_id
 */
function get_regions($order_id) {
	$db		=  RC_Loader::load_app_model('order_region_viewmodel', 'orders');
	$field	= array("concat(IFNULL(c.region_name, ''), '  ', IFNULL(p.region_name, ''),'  ', IFNULL(t.region_name, ''), '  ', IFNULL(d.region_name, '')) AS region");
	$region	= $db->field($field)->find('o.order_id = "'.$order_id.'"');
	return $region['region'] ;
	//TODO 同表多次联合查询
//	$sql = "SELECT concat(IFNULL(c.region_name, ''), '  ', IFNULL(p.region_name, ''), " .
//			"'  ', IFNULL(t.region_name, ''), '  ', IFNULL(d.region_name, '')) AS region " .
//			"FROM ecs_order_info AS o " .
//			"LEFT JOIN ecs_region AS c ON o.country = c.region_id " .
//			"LEFT JOIN ecs_region AS p ON o.province = p.region_id " .
//			"LEFT JOIN ecs_region AS t ON o.city = t.region_id " .
//			"LEFT JOIN ecs_region AS d ON o.district = d.region_id " .
//			"WHERE o.order_id = '$order_id'";
//	$query = $db->query($sql);

// 	$this->db_order_region->view = array(
// 			'region ' => array(
// 					'type' =>Component_Model_View::TYPE_LEFT_JOIN,
// 					'on'   => 'order_info.country = region.region_id  '
// 			),
// 			'region as p' => array(
// 					'type' =>Component_Model_View::TYPE_LEFT_JOIN,
// 					'on'   => 'order_info.province = region.region_id '
// 			),
// 			'region as t' => array(
// 					'type' =>Component_Model_View::TYPE_LEFT_JOIN,
// 					'on'   => 'order_info.city = region.region_id '
// 			),
// 			'region as d' => array(
// 					'type' =>Component_Model_View::TYPE_LEFT_JOIN,
// 					'on'   => 'order_info.district = region.region_id '
// 			)
// 	);
// 	$field = array("concat(IFNULL(ecs_region.region_name, ''), '  ', IFNULL(p.region_name, ''),'  ', IFNULL(t.region_name, ''), '  ', IFNULL(d.region_name, '')) AS region");
// 	$query = $this->db_order_region->field($field)->find('ecs_order_info.order_id = "'.$order['order_id'].'"');
// 	$order['region'] = $query['region'];

//	return $query[0]['region'];
}


/**
 * order_back.php
 */

/**
 *  获取退货单列表信息
 * @access  public
 * @param
 * @return void
 */
function get_back_list() {	
	$db_back_order = RC_DB::table('back_order');
	
	$args = $_GET;
	/* 过滤信息 */
	$filter['delivery_sn']	= empty($args['delivery_sn'])	? '' : trim($args['delivery_sn']);
	$filter['order_sn']		= empty($args['order_sn'])		? '' : trim($args['order_sn']);
	$filter['order_id']		= empty($args['order_id'])		? 0 : intval($args['order_id']); 
	$filter['consignee']	= empty($args['consignee'])		? '' : trim($args['consignee']);
	$filter['sort_by']		= empty($args['sort_by'])		? 'update_time' : trim($args['sort_by']);
	$filter['sort_order']	= empty($args['sort_order'])	? 'DESC' : trim($args['sort_order']);
	$filter['keywords']		= empty($args['keywords'])		? '' : trim($args['keywords']);
	
	if ($filter['order_sn']) {
		$db_back_order->where('order_sn', 'like', '%'.mysql_like_quote($filter['order_sn']).'%');
	}
	if ($filter['consignee']) {
		$db_back_order->where('consignee', 'like', '%'.mysql_like_quote($filter['consignee']).'%');
	}
	if ($filter['delivery_sn']) {
		$db_back_order->where('delivery_sn', 'like', '%'.mysql_like_quote($filter['delivery_sn']).'%');
	}
	if ($filter['keywords']) {
		$db_back_order->where('order_sn', 'like', '%'.mysql_like_quote($filter['keywords']).'%')->orWhere('consignee', 'like', '%'.mysql_like_quote($filter['keywords']).'%');
	}
	
// 	/* 获取管理员信息 */
// 	$where = array_merge($where, get_order_bac_where());

	/* 记录总数 */
	
	$count = $db_back_order->count();
	$filter['record_count'] = $count;

	//实例化分页
	$page = new ecjia_page($count, 15, 6);
	
	/* 查询 */
	$row = $db_back_order
		->select('back_id', 'order_id', 'delivery_sn', 'order_sn', 'order_id', 'add_time', 'action_user', 'consignee', 'country', 'province', 'city', 'district', 'tel', 'status', 'update_time', 'email', 'return_time')
		->orderby($filter['sort_by'], $filter['sort_order'])
		->take(15)->skip($page->start_id-1)->get();
	
	if (!empty($row) && is_array($row)) {
		/* 格式化数据 */
		foreach ($row AS $key => $value) {
			$row[$key]['return_time']	= RC_Time::local_date(ecjia::config('time_format'), $value['return_time']);
			$row[$key]['add_time']		= RC_Time::local_date(ecjia::config('time_format'), $value['add_time']);
			$row[$key]['update_time']	= RC_Time::local_date(ecjia::config('time_format'), $value['update_time']);
			if ($value['status'] == 1) {
				$row[$key]['status_name'] = RC_Lang::lang('delivery_status/1');
			} else {
				$row[$key]['status_name'] = RC_Lang::lang('delivery_status/0');
			}
		}	
	}
	return array('back' => $row, 'filter' => $filter, 'page' => $page->show(15), 'desc' => $page->page_desc());
}


/**
 * 根据id取得退货单信息
 * @param   int     $back_id   退货单 id（如果 back_id > 0 就按 id 查，否则按 sn 查）
 * @return  array   退货单信息（金额都有相应格式化的字段，前缀是 formated_ ）
 */
function back_order_info($back_id) {
	$return_order = array();
	if (empty($back_id) || !is_numeric($back_id)) {
		return $return_order;
	}

	$db_back_order = RC_DB::table('back_order')->where('back_id', $back_id);
	
// 	/* 获取管理员信息 */
// 	$where = array_merge($where, get_order_bac_where());

	$back = $db_back_order->first();
	if ($back) {
		/* 格式化金额字段 */
		$back['formated_insure_fee']		= price_format($back['insure_fee'], false);
		$back['formated_shipping_fee']		= price_format($back['shipping_fee'], false);

		/* 格式化时间字段 */
		$back['formated_add_time']			= RC_Time::local_date(ecjia::config('time_format'), $back['add_time']);
		$back['formated_update_time']		= RC_Time::local_date(ecjia::config('time_format'), $back['update_time']);
		$back['formated_return_time']		= RC_Time::local_date(ecjia::config('time_format'), $back['return_time']);

		$return_order = $back;
	}

	return $return_order;
}


// 	TODO:此版本不用
// /**
//  * 判断管理员是否属于某个办事处的sql条件
//  * @return string
//  */
// function get_order_bac_where()
// {
// 	/* 获取管理员信息 */
// 	$admin_info = admin_info();
// 	$where = array();
// 	/* 如果管理员属于某个办事处，只列出这个办事处管辖的发货单 */
// 	if ($admin_info['agency_id'] > 0) {
// 		$where['agency_id'] = $admin_info['agency_id'];
// 	}
	
// 	/* 如果管理员属于某个供货商，只列出这个供货商的发货单 */
// 	if ($admin_info['suppliers_id'] > 0) {
// 		$where['suppliers_id'] = $admin_info['suppliers_id'];
// 	}
// 	return $where;
// }


/**
 * order_delivery.php
 */


/**
 *  获取发货单列表信息
 *
 * @access  public
 * @param
 *
 * @return void
 */
function get_delivery_list() {
	$args = $_GET;
	/* 过滤信息 */
	$filter['delivery_sn']	= empty($args['delivery_sn'])	? '' 				: trim($args['delivery_sn']);
	$filter['order_sn']		= empty($args['order_sn'])		? '' 				: trim($args['order_sn']);
	$filter['order_id']		= empty($args['order_id'])		? 0 				: intval($args['order_id']);
	$filter['consignee']	= empty($args['consignee'])		? '' 				: trim($args['consignee']);
	$filter['status']		= isset($args['status'])		? $args['status'] 	: -1;
	$filter['sort_by']		= empty($args['sort_by'])		? 'update_time' 	: trim($args['sort_by']);
	$filter['sort_order']	= empty($args['sort_order'])	? 'DESC' 			: trim($args['sort_order']);
	$filter['keywords']		= empty($args['keywords'])		? '' 				: trim($args['keywords']);
	
	$db_delivery_order = RC_DB::table('delivery_order as do');
	$where = array();
	if ($filter['order_sn']) {
		$where['order_sn'] = array('like' => '%'.mysql_like_quote($filter['order_sn']).'%');
		
		$db_delivery_order->where('order_sn', 'like', '%'.mysql_like_quote($filter['order_sn']).'%');
	}
	if ($filter['consignee']) {
		$where['consignee'] = array('like' => '%'.mysql_like_quote($filter['consignee']).'%');
		
		$db_delivery_order->where('consignee', 'like', '%'.mysql_like_quote($filter['consignee']).'%');
	}
	if ($filter['status'] >= 0) {
		$where['status'] = mysql_like_quote($filter['status']);
		
		$db_delivery_order->where('status', $filter['status']);
	}
	if ($filter['delivery_sn']) {
		$where['delivery_sn'] = array('like' => '%'.mysql_like_quote($filter['delivery_sn']).'%');
		
		$db_delivery_order->where('delivery_sn', 'like', '%'.mysql_like_quote($filter['delivery_sn']).'%');
	}
	if ($filter['keywords']) {
		$where[] = "(order_sn like '%".mysql_like_quote($filter['keywords'])."%' or consignee like '%".mysql_like_quote($filter['keywords'])."%')";
		
		$db_delivery_order->where('order_sn', 'like', '%'.mysql_like_quote($filter['keywords']).'%')->orWhere('consignee', 'like', '%'.mysql_like_quote($filter['keywords']).'%');
	}
	
// 	TODO:此版本不用办事处与供货商	
// 	/* 获取管理员信息 */
// 	$admin_info = admin_info();
	
// 	/* 如果管理员属于某个办事处，只列出这个办事处管辖的发货单 */
// 	if ($admin_info['agency_id'] > 0) {
// 		$where['agency_id'] = $admin_info['agency_id'];	
// 	}
	
// 	/* 如果管理员属于某个供货商，只列出这个供货商的发货单 */
// 	if ($admin_info['suppliers_id'] > 0) {
// 		$where['suppliers_id'] = $admin_info['suppliers_id'];
// 	}
	
	/* 记录总数 */
// 	$db_delivery_order	= RC_Loader::load_app_model('delivery_order_model', 'orders');
	$delivery_order_viewmodel = RC_Loader::load_app_model('delivery_order_suppliers_viewmodel', 'orders');
	
// 	$count = $db_delivery_order->where($where)->count();
	$count = $db_delivery_order->count();
	$filter['record_count'] = $count;
	$page = new ecjia_page($count, 15, 6);
	
	/* 查询 */
// 	$field = 'do.delivery_id, do.order_id, do.delivery_sn, do.order_sn, do.order_id, do.add_time, do.action_user, do.consignee, do.country,do.province, do.city, do.district, do.tel, do.status, do.update_time, do.email, do.suppliers_id, s.suppliers_name';
// 	$row = $delivery_order_viewmodel->field($field)->where($where)->order(array($filter['sort_by'] => $filter['sort_order']))->limit($page->limit())->select();
	
	$row = $db_delivery_order->leftJoin('suppliers as s', RC_DB::raw('do.suppliers_id'), '=', RC_DB::raw('s.suppliers_id'))
		->select(RC_DB::raw('do.delivery_id, do.order_id, do.delivery_sn, do.order_sn, do.order_id, do.add_time, do.action_user, do.consignee, do.country, do.province, do.city, do.district, do.tel, do.status, do.update_time, do.email, do.suppliers_id, s.suppliers_name'))
		->orderby($filter['sort_by'], $filter['sort_order'])->take(15)->skip($page->start_id-1)->get();
	
// 	TODO:暂不用供货商
// 	/* 获取供货商列表 */
// 	$suppliers_list = get_suppliers_list();
// 	$_suppliers_list = array();
	
// 	if(!empty($suppliers_list)){
// 		foreach ($suppliers_list as $value) {
// 			$_suppliers_list[$value['suppliers_id']] = $value['suppliers_name'];
// 		}
// 	}
	/* 格式化数据 */
	if (!empty($row)) {
		foreach ($row AS $key => $value) {
			$row[$key]['add_time'] = RC_Time::local_date(ecjia::config('time_format'), $value['add_time']);
			$row[$key]['update_time'] = RC_Time::local_date(ecjia::config('time_format'), $value['update_time']);
			if ($value['status'] == 1) {
				$row[$key]['status_name'] = RC_Lang::get('orders::order.delivery_status.1');
			} elseif ($value['status'] == 2) {
				$row[$key]['status_name'] = RC_Lang::get('orders::order.delivery_status.2');
			} else {
				$row[$key]['status_name'] = RC_Lang::get('orders::order.delivery_status.0');
			}
// 			$row[$key]['suppliers_name'] = isset($_suppliers_list[$value['suppliers_id']]) ? $_suppliers_list[$value['suppliers_id']] : '';
		}
	}
	
	return array('delivery' => $row, 'filter' => $filter, 'page' => $page->show(15), 'desc' => $page->page_desc());		
}

/**
 * 取得发货单信息
 * @param   int     $delivery_order   发货单id（如果delivery_order > 0 就按id查，否则按sn查）
 * @param   string  $delivery_sn      发货单号
 * @return  array   发货单信息（金额都有相应格式化的字段，前缀是formated_）
 */
function delivery_order_info($delivery_id, $delivery_sn = '') {
	$return_order = array();
	if (empty($delivery_id) || !is_numeric($delivery_id)) {
		return $return_order;
	}
	
	$db_delivery_order = RC_DB::table('delivery_order');
	if ($delivery_id > 0) {
		$db_delivery_order->where('delivery_id', $delivery_id);
	} else {
		$db_delivery_order->where('delivery_sn', $delivery_sn);
	}
	
// 	TODO:此版本不用办事处与供货商	
// 	/* 获取管理员信息 */
// 	$admin_info = admin_info();

// 	/* 如果管理员属于某个办事处，只列出这个办事处管辖的发货单 */
// 	if ($admin_info['agency_id'] > 0) {
// 		$where['agency_id'] = $admin_info['agency_id'];
// 	}

// 	/* 如果管理员属于某个供货商，只列出这个供货商的发货单 */
// 	if ($admin_info['suppliers_id'] > 0) {
// 		$where['suppliers_id'] = $admin_info['suppliers_id'];
// 	}

	$delivery = $db_delivery_order->first();
	if ($delivery) {
		/* 格式化金额字段 */
		$delivery['formated_insure_fee']	= price_format($delivery['insure_fee'], false);
		$delivery['formated_shipping_fee']	= price_format($delivery['shipping_fee'], false);

		/* 格式化时间字段 */
		$delivery['formated_add_time']		= RC_Time::local_date(ecjia::config('time_format'), $delivery['add_time']);
		$delivery['formated_update_time']	= RC_Time::local_date(ecjia::config('time_format'), $delivery['update_time']);

		$return_order = $delivery;
	}
	return $return_order;
}

/**
 * 删除发货单时进行退货
 *
 * @access   public
 * @param    int     $delivery_id      发货单id
 * @param    array   $delivery_order   发货单信息数组
 *
 * @return  void
 */
function delivery_return_goods($delivery_id, $delivery_order) {
// 	$db_delivery	= RC_Loader::load_app_model('delivery_goods_model', 'orders');
// 	$db_order_goods = RC_Loader::load_app_model('order_goods_model', 'orders');
// 	$db_order_info	= RC_Loader::load_app_model('order_info_model', 'orders');

	/* 查询：取得发货单商品 */
// 	$goods_list = $db_delivery->where(array('delivery_id' => $delivery_order['delivery_id']))->select();
	$goods_list = RC_DB::table('delivery_goods')->where('delivery_id', $delivery_order['delivery_id'])->get();

	/* 更新： */
	if (!empty($goods_list)) {
		foreach ($goods_list as $key => $val) {
// 			$db_order_goods->dec('send_number', 'order_id='.$delivery_order['order_id']. ' and goods_id='.$val['goods_id'], $val['send_number']);
			RC_DB::table('order_goods')
				->where('order_id', $delivery_order['order_id'])
				->where('goods_id', $val['goods_id'])
				->decrement('send_number', $val['send_number']);
		}
	}
	$data = array(
		'shipping_status'	=> '0',
		'order_status'		=> 1
	);
// 	$db_order_info->where(array('order_id' => $delivery_order['order_id']))->update($data);
	RC_DB::table('order_info')->where('order_id', $delivery_order['order_id'])->update($data);
}

/**
 * 删除发货单时删除其在订单中的发货单号
 *
 * @access   public
 * @param    int      $order_id              定单id
 * @param    string   $delivery_invoice_no   发货单号
 *
 * @return  void
 */
function del_order_invoice_no($order_id, $delivery_invoice_no) {
	/* 查询：取得订单中的发货单号 */
// 	$db = RC_Loader::load_app_model('order_info_model', 'orders');
// 	$order_invoice_no = $db->where(array('order_id' => $order_id))->get_field('invoice_no');
	$order_invoice_no = RC_DB::table('order_info')->where('order_id', $order_id)->pluck('invoice_no');

	/* 如果为空就结束处理 */
	if (empty($order_invoice_no)) {
		return;
	}

	/* 去除当前发货单号 */
	$order_array	= explode('<br>', $order_invoice_no);
	$delivery_array = explode('<br>', $delivery_invoice_no);

	foreach ($order_array as $key => $invoice_no) {
		$ii = array_search($invoice_no, $delivery_array);
		if ($ii) {
			unset($order_array[$key], $delivery_array[$ii]);
		}
	}

	$arr['invoice_no'] = implode('<br>', $order_array);
	update_order($order_id, $arr);
}

/**
 * 判断订单的发货单是否全部发货
 * @param   int     $order_id  订单 id
 * @return  int     1，全部发货；0，未全部发货；-1，部分发货；-2，完全没发货；
 */
function get_all_delivery_finish($order_id) {
// 	$db = RC_Loader::load_app_model('delivery_order_model', 'orders');
	$db_delivery_order = RC_DB::table('delivery_order');
	
	$return_res = 0;

	if (empty($order_id)) {
		return $return_res;
	}

	/* 未全部分单 */
	if (!get_order_finish($order_id)) {
		return $return_res;
	} else {
		/* 已全部分单 */
		/* 是否全部发货 */
// 		$sum = $db->where(array('order_id' => $order_id, 'status' => 2))->count('delivery_id');
		$sum = $db_delivery_order->where('order_id', $order_id)->where('status', 2)->count('delivery_id');
		/* 全部发货 */
		if (empty($sum)) {
			$return_res = 1;
		} else {
			/* 未全部发货 */
			/* 订单全部发货中时：当前发货单总数 */
// 			$_sum = $db->where(array('order_id' => $order_id, 'status' => array('neq' => 1)))->count('delivery_id');
			$_sum = $db_delivery_order->where('order_id', $order_id)->where('status', '!=', 1)->count('delivery_id');
			
			if ($_sum == $sum) {
				$return_res = -2; // 完全没发货
			} else {
				$return_res = -1; // 部分发货
			}
		}
	}
	return $return_res;
}

/**
	 * 合并订单
	 * @param   string  $from_order_sn  从订单号
	 * @param   string  $to_order_sn    主订单号
	 * @return  成功返回true，失败返回错误信息
	 */
function merge_order($from_order_sn, $to_order_sn) 
{
	$db_order_good 	= RC_Loader::load_app_model('order_goods_model', 'orders');
	$db_order_info 	= RC_Loader::load_app_model('order_info_model', 'orders');
	$db_pay_log 	= RC_Loader::load_app_model('pay_log_model', 'orders');

	/* 订单号不能为空 */
	if (trim($from_order_sn) == '' || trim($to_order_sn) == '') {
		ecjia::$controller->showmessage(RC_Lang::lang('order_sn_not_null'), ecjia::MSGTYPE_JSON | ecjia::MSGSTAT_ERROR);
	}

	/* 订单号不能相同 */
	if ($from_order_sn == $to_order_sn) {
		ecjia::$controller->showmessage(RC_Lang::lang('two_order_sn_same'), ecjia::MSGTYPE_JSON | ecjia::MSGSTAT_ERROR);
	}

	/* 取得订单信息 */
	$from_order = order_info(0, $from_order_sn);
	$to_order   = order_info(0, $to_order_sn);

	/* 检查订单是否存在 */
	if (!$from_order) {
		ecjia::$controller->showmessage(sprintf(RC_Lang::lang('order_not_exist'), $from_order_sn), ecjia::MSGTYPE_JSON | ecjia::MSGSTAT_ERROR);
	} elseif (!$to_order) {
		ecjia::$controller->showmessage(sprintf(RC_Lang::lang('order_not_exist'), $to_order_sn), ecjia::MSGTYPE_JSON | ecjia::MSGSTAT_ERROR);
	}

	/* 检查合并的订单是否为普通订单，非普通订单不允许合并 */
	if ($from_order['extension_code'] != '' || $to_order['extension_code'] != 0) {
		ecjia::$controller->showmessage(RC_Lang::lang('merge_invalid_order'), ecjia::MSGTYPE_JSON | ecjia::MSGSTAT_ERROR);
	}

	/* 检查订单状态是否是已确认或未确认、未付款、未发货 */
	if ($from_order['order_status'] != OS_UNCONFIRMED && $from_order['order_status'] != OS_CONFIRMED) {
		ecjia::$controller->showmessage(sprintf(RC_Lang::lang('os_not_unconfirmed_or_confirmed'), $from_order_sn), ecjia::MSGTYPE_JSON | ecjia::MSGSTAT_ERROR);
		
	} elseif ($from_order['pay_status'] != PS_UNPAYED) {
		ecjia::$controller->showmessage(sprintf(RC_Lang::lang('ps_not_unpayed'), $from_order_sn), ecjia::MSGTYPE_JSON | ecjia::MSGSTAT_ERROR);
	} elseif ($from_order['shipping_status'] != SS_UNSHIPPED) {
		ecjia::$controller->showmessage(sprintf(RC_Lang::lang('ss_not_unshipped'), $from_order_sn), ecjia::MSGTYPE_JSON | ecjia::MSGSTAT_ERROR);
	}

	if ($to_order['order_status'] != OS_UNCONFIRMED && $to_order['order_status'] != OS_CONFIRMED) {
		ecjia::$controller->showmessage(sprintf(RC_Lang::lang('os_not_unconfirmed_or_confirmed'), $to_order_sn), ecjia::MSGTYPE_JSON | ecjia::MSGSTAT_ERROR);
	} elseif ($to_order['pay_status'] != PS_UNPAYED) {
		ecjia::$controller->showmessage(sprintf(RC_Lang::lang('ps_not_unpayed'), $to_order_sn), ecjia::MSGTYPE_JSON | ecjia::MSGSTAT_ERROR);
	} elseif ($to_order['shipping_status'] != SS_UNSHIPPED) {
		ecjia::$controller->showmessage(sprintf(RC_Lang::lang('ss_not_unshipped'), $to_order_sn), ecjia::MSGTYPE_JSON | ecjia::MSGSTAT_ERROR);
	}

	/* 检查订单用户是否相同 */
	if ($from_order['user_id'] != $to_order['user_id']) {
		ecjia::$controller->showmessage(RC_Lang::lang('order_user_not_same'), ecjia::MSGTYPE_JSON | ecjia::MSGSTAT_ERROR);
	}

	/* 合并订单 */
	$order = $to_order;
	$order['order_id']  = '';
	$order['add_time']  = RC_Time::gmtime();

	// 合并商品总额
	$order['goods_amount'] += $from_order['goods_amount'];

	// 合并折扣
	$order['discount'] += $from_order['discount'];

	if ($order['shipping_id'] > 0) {
		// 重新计算配送费用
		$weight_price       	= order_weight_price($to_order['order_id']);
		$from_weight_price  	= order_weight_price($from_order['order_id']);
		$weight_price['weight'] += $from_weight_price['weight'];
		$weight_price['amount'] += $from_weight_price['amount'];
		$weight_price['number'] += $from_weight_price['number'];

		$region_id_list = array($order['country'], $order['province'], $order['city'], $order['district']);
		$shipping_method = RC_Loader::load_app_class('shipping_method','shipping');
		$shipping_area = $shipping_method->shipping_area_info($order['shipping_id'], $region_id_list);

		$order['shipping_fee'] = $shipping_method->shipping_fee($shipping_area['shipping_code'],
				unserialize($shipping_area['configure']), $weight_price['weight'], $weight_price['amount'], $weight_price['number']);

		// 如果保价了，重新计算保价费
		if ($order['insure_fee'] > 0) {
			$order['insure_fee'] = shipping_insure_fee($shipping_area['shipping_code'], $order['goods_amount'], $shipping_area['insure']);
		}
	}
	
	// 重新计算包装费、贺卡费
	if ($order['pack_id'] > 0) {
// 		$pack = pack_info($order['pack_id']);
// 		$order['pack_fee'] = $pack['free_money'] > $order['goods_amount'] ? $pack['pack_fee'] : 0;
		$order['pack_fee'] = 0;
	}
	if ($order['card_id'] > 0) {
// 		$card = card_info($order['card_id']);
// 		$order['card_fee'] = $card['free_money'] > $order['goods_amount'] ? $card['card_fee'] : 0;
		$order['card_fee'] = 0;
	}
	
	// 红包不变，合并积分、余额、已付款金额
	$order['integral']      += $from_order['integral'];
	$order['integral_money'] = value_of_integral($order['integral']);
	$order['surplus']       += $from_order['surplus'];
	$order['money_paid']    += $from_order['money_paid'];

	// 计算应付款金额（不包括支付费用）
	$order['order_amount'] = $order['goods_amount'] - $order['discount']
	+ $order['shipping_fee']
	+ $order['insure_fee']
	+ $order['pack_fee']
	+ $order['card_fee']
	- $order['bonus']
	- $order['integral_money']
	- $order['surplus']
	- $order['money_paid'];

	// 重新计算支付费
	if ($order['pay_id'] > 0) {
		// 货到付款手续费
		$cod_fee          = $shipping_area ? $shipping_area['pay_fee'] : 0;
		$order['pay_fee'] = pay_fee($order['pay_id'], $order['order_amount'], $cod_fee);

		// 应付款金额加上支付费
		$order['order_amount'] += $order['pay_fee'];
	}
	
	/* 插入订单表 */
	$order['order_sn'] = get_order_sn(); 
	$result = $db_order_info->insert(rc_addslashes($order));
	if (!$result) {
		ecjia_admin::$controller->showmessage(__('订单合并失败！'), ecjia::MSGTYPE_JSON | ecjia::MSGSTAT_ERROR);
	}
//	do {
//		$order['order_sn'] = get_order_sn();
//		if ($db_order_info->insert(addslashes_deep($order))) {
//			break;
//		} else {
//			if ($db_order_info->errno() != 1062) {
//				die($db_order_info->errorMsg());
//			}
//		}
//	}
//	while (true); // 防止订单号重复
	
	/* 订单号 */
	$order_id = $db_order_info->last_insert_id();
	
	/* 更新订单商品 */
	$data = array(
			'order_id' => $order_id
	);
	$db_order_good->in(array('order_id' => array($from_order['order_id'], $to_order['order_id'])))->update($data);
	
	$payment_method = RC_Loader::load_app_class('payment_method','payment');
	/* 插入支付日志 */
	$payment_method->insert_pay_log($order_id, $order['order_amount'], PAY_ORDER);
	/* 删除原订单 */
	$db_order_info->in(array('order_id' => array($from_order['order_id'], $to_order['order_id'])))->delete();

	/* 删除原订单支付日志 */
	$db_pay_log->in(array('order_id' => array($from_order['order_id'], $to_order['order_id'])))->delete();
	/* 返还 from_order 的红包，因为只使用 to_order 的红包 */
	if ($from_order['bonus_id'] > 0) {
		RC_Loader::load_app_func('bonus','bonus');
		unuse_bonus($from_order['bonus_id']);
	}
	
	ecjia_admin::admin_log($from_order['order_sn'].'与'.$to_order['order_sn'].'，合并成新订单，订单号为：'.$order['order_sn'], 'edit', 'order');
	/* 返回成功 */
	return true;
}

// end