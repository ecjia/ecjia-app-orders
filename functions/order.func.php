<?php
defined('IN_ECJIA') or exit('No permission resources.');
/**
* ECJIA 购物流程函数库
*/

/**
* 处理序列化的支付、配送的配置参数
* 返回一个以name为索引的数组
*
* @access  public
* @param   string	   $cfg
* @return  void
*/
function unserialize_config($cfg) {
	if (is_string($cfg) && ($arr = unserialize($cfg)) !== false) {
		$config = array();
		foreach ($arr AS $key => $val) {
			$config[$val['name']] = $val['value'];
		}
		return $config;
	} else {
		return false;
	}
}

/**
* 计算运费
* @param   string  $shipping_code	  配送方式代码
* @param   mix	 $shipping_config	配送方式配置信息
* @param   float   $goods_weight	   商品重量
* @param   float   $goods_amount	   商品金额
* @param   float   $goods_number	   商品数量
* @return  float   运费
*/
//TODO:方法后期可废弃，已移入shipping_method类中
function shipping_fee($shipping_code, $shipping_config, $goods_weight, $goods_amount, $goods_number='') {
	if (!is_array($shipping_config)) {
		$shipping_config = unserialize($shipping_config);
	}

	if (RC_Loader::load_app_module($shipping_code,"shipping")) {
		$obj = new $shipping_code($shipping_config);
		return $obj->calculate($goods_weight, $goods_amount, $goods_number);
	} else {
		return 0;
	}
}

/**
* 获取指定配送的保价费用
*
* @access  public
* @param   string	  $shipping_code  配送方式的code
* @param   float	   $goods_amount   保价金额
* @param   mix		 $insure		 保价比例
* @return  float
*/
function shipping_insure_fee($shipping_code, $goods_amount, $insure) {
	if (strpos($insure, '%') === false) {
		/* 如果保价费用不是百分比则直接返回该数值 */
		return floatval($insure);
	} else {
		if (RC_Loader::load_app_module($shipping_code,"shipping")){
			$shipping = new $shipping_code;
			$insure   = floatval($insure) / 100;
			if (method_exists($shipping, 'calculate_insure')) {
				return $shipping->calculate_insure($goods_amount, $insure);
			} else {
				return ceil($goods_amount * $insure);
			}
		} else {
			return false;
		}
	}
}

/**
* 获得订单需要支付的支付费用
*
* @access  public
* @param   integer $payment_id
* @param   float   $order_amount
* @param   mix	 $cod_fee
* @return  float
*/
function pay_fee($payment_id, $order_amount, $cod_fee=null) {
	$payment_method = RC_Loader::load_app_class('payment_method','payment');
	$pay_fee = 0;
	$payment = $payment_method->payment_info($payment_id);
	$rate	= ($payment['is_cod'] && !is_null($cod_fee)) ? $cod_fee : $payment['pay_fee'];

	if (strpos($rate, '%') !== false) {
		/* 支付费用是一个比例 */
		$val		= floatval($rate) / 100;
		$pay_fee	= $val > 0 ? $order_amount * $val /(1- $val) : 0;
	} else {
		$pay_fee	= floatval($rate);
	}
	return round($pay_fee, 2);
}


/**
* 取得订单信息
* @param   int	 $order_id   订单id（如果order_id > 0 就按id查，否则按sn查）
* @param   string  $order_sn   订单号
* @return  array   订单信息（金额都有相应格式化的字段，前缀是formated_）
*/
function order_info($order_id, $order_sn = '', $type = '') {

	if (!empty($type) && $type == 'front') {/*接口订单详情*/
		$db_order_info = RC_DB::table('order_info as o');
	} else {
		$db_order_info = RC_DB::table('order_info as o')->leftJoin('store_franchisee as s', RC_DB::raw('o.store_id'), '=', RC_DB::raw('s.store_id'));
	}
	/* 计算订单各种费用之和的语句 */
	$total_fee = " (goods_amount - discount + tax + shipping_fee + insure_fee + pay_fee + pack_fee + card_fee) AS total_fee ";

	$order_id = intval($order_id);
	if ($order_id > 0) {
		$db_order_info->where('order_id', $order_id);
	} else {
		$db_order_info->where('order_sn', $order_sn);
	}
	$db_order_info->where('is_delete', 0);

	if (!empty($type) && $type == 'front') {/*接口订单详情*/
		$order = $db_order_info->select('*', RC_DB::raw($total_fee))->first();
	} else {
		$order = $db_order_info
		->selectRaw('o.*, s.merchants_name, s.responsible_person, s.contact_mobile, s.email as merchants_email, s.company_name, '. $total_fee)
		->first();
	}

	if ($order) {
    	$order['store_id'] = intval($order['store_id']);
    	$order['invoice_no'] = empty($order['invoice_no']) ? '' : $order['invoice_no'];

		/* 格式化金额字段 */
		$order['formated_goods_amount']		= price_format($order['goods_amount'], false);
		$order['formated_discount']			= price_format($order['discount'], false);
		$order['formated_tax']				= price_format($order['tax'], false);
		$order['formated_shipping_fee']		= price_format($order['shipping_fee'], false);
		$order['formated_insure_fee']		= price_format($order['insure_fee'], false);
		$order['formated_pay_fee']			= price_format($order['pay_fee'], false);
		$order['formated_pack_fee']			= price_format($order['pack_fee'], false);
		$order['formated_card_fee']			= price_format($order['card_fee'], false);
		$order['formated_total_fee']		= price_format($order['total_fee'], false);
		$order['formated_money_paid']		= price_format($order['money_paid'], false);
		$order['formated_bonus']			= price_format($order['bonus'], false);
		$order['formated_integral_money']	= price_format($order['integral_money'], false);
		$order['formated_surplus']			= price_format($order['surplus'], false);
		$order['formated_order_amount']		= price_format(abs($order['order_amount']), false);
		$order['formated_add_time']			= RC_Time::local_date(ecjia::config('time_format'), $order['add_time']);
	}
	return $order;
}

/**
* 判断订单是否已完成
* @param   array   $order  订单信息
* @return  bool
*/
function order_finished($order) {
	return $order['order_status']  == OS_CONFIRMED &&
	($order['shipping_status'] == SS_SHIPPED || $order['shipping_status'] == SS_RECEIVED) &&
	($order['pay_status'] == PS_PAYED || $order['pay_status'] == PS_PAYING);
}

/**
* 取得订单商品
* @param   int	 $order_id   订单id
* @return  array   订单商品数组
*/
function order_goods($order_id) {
// 	$db = RC_Loader::load_app_model('order_goods_model','orders');
// 	$data = $db->field('rec_id, goods_id, goods_name, goods_sn,product_id, market_price, goods_number,goods_price, goods_attr, is_real, parent_id, is_gift,goods_price * goods_number|subtotal, extension_code')->where(array('order_id' => $order_id))->select();

	$data = RC_DB::table('order_goods')
		->selectRaw('rec_id, goods_id, goods_name, goods_sn, product_id, market_price, goods_number, goods_price, goods_attr, is_real, parent_id, is_gift, goods_price * goods_number as subtotal, extension_code')
		->where('order_id', $order_id)
		->get();
	$goods_list = array();
	if (!empty($data)) {
		foreach ($data as $row) {
			if ($row['extension_code'] == 'package_buy') {
				$row['package_goods_list'] = get_package_goods($row['goods_id']);
			}
			$goods_list[] = $row;
		}
	}
	return $goods_list;
}

/**
* 取得订单总金额
* @param   int	 $order_id   订单id
* @param   bool	$include_gift   是否包括赠品
* @return  float   订单总金额
*/
function order_amount($order_id, $include_gift = true) {
// 	$db = RC_Loader::load_app_model('order_goods_model', 'orders');
	$db_order_goods = RC_DB::table('order_goods')->where('order_id', $order_id);

	if (!$include_gift) {
// 		$data = $db->where(array('order_id' => $order_id , 'is_gift' => 0))->sum('goods_price * goods_number');
		$db_order_goods->where('is_gift', 0);
	}
// 	$data = $db->where(array('order_id' => $order_id))->sum('goods_price * goods_number');
	$data = $db_order_goods->sum(RC_DB::raw('goods_price * goods_number'));

	return floatval($data);
}

/**
* 取得某订单商品总重量和总金额（对应 cart_weight_price）
* @param   int	 $order_id   订单id
* @return  array   ('weight' => **, 'amount' => **, 'formated_weight' => **)
*/
function order_weight_price($order_id) {
// 	$dbview = RC_Loader::load_app_model('order_order_goods_viewmodel', 'orders');
// 	$dbview->view = array(
// 		'goods' => array(
// 			'type'	=> Component_Model_View::TYPE_LEFT_JOIN,
// 			'alias'	=> 'g',
// 			'field'	=> 'SUM(g.goods_weight * o.goods_number)|weight,SUM(o.goods_price * o.goods_number)|amount,SUM(o.goods_number)|number',
// 			'on'	=> 'o.goods_id = g.goods_id ',
// 		)
// 	);
// 	$row = $dbview->find(array('o.order_id' => $order_id));

	$row = $db = RC_DB::table('order_goods as o')
		->leftJoin('goods as g', RC_DB::raw('g.goods_id'), '=', RC_DB::raw('o.goods_id'))
		->selectRaw('SUM(g.goods_weight * o.goods_number) as weight, SUM(o.goods_price * o.goods_number) as amount, SUM(o.goods_number) as number')
		->where(RC_DB::raw('o.order_id'), $order_id)
		->first();

	$row['weight'] = floatval($row['weight']);
	$row['amount'] = floatval($row['amount']);
	$row['number'] = intval($row['number']);

	/* 格式化重量 */
	$row['formated_weight'] = formated_weight($row['weight']);
	return $row;
}

/**
 * 获得订单中的费用信息
 *
 * @access  public
 * @param   array   $order
 * @param   array   $goods
 * @param   array   $consignee
 * @param   bool    $is_gb_deposit  是否团购保证金（如果是，应付款金额只计算商品总额和支付费用，可以获得的积分取 $gift_integral）
 * @return  array
 */
function order_fee($order, $goods, $consignee, $cart_id = array()) {
// 	$sql = 'SELECT count(*) FROM ' . $GLOBALS['ecs']->table('cart') . " WHERE  `session_id` = '" . SESS_ID. "' AND `extension_code` != 'package_buy' AND `is_shipping` = 0";
// 	$shipping_count = $GLOBALS['db']->getOne($sql);

	RC_Loader::load_app_func('common','goods');
	RC_Loader::load_app_func('cart','cart');
	$db 	= RC_Loader::load_app_model('cart_model', 'cart');
	$dbview = RC_Loader::load_app_model('cart_exchange_viewmodel', 'cart');
    /* 初始化订单的扩展code */
    if (!isset($order['extension_code'])) {
        $order['extension_code'] = '';
    }

//     TODO: 团购等促销活动注释后暂时给的固定参数
    $order['extension_code'] = '';
    $group_buy ='';
//     TODO: 团购功能暂时注释
//     if ($order['extension_code'] == 'group_buy') {
//         $group_buy = group_buy_info($order['extension_id']);
//     }

    $total = array(
		'real_goods_count' => 0,
        'gift_amount'      => 0,
        'goods_price'      => 0,
        'market_price'     => 0,
        'discount'         => 0,
        'pack_fee'         => 0,
        'card_fee'         => 0,
        'shipping_fee'     => 0,
        'shipping_insure'  => 0,
        'integral_money'   => 0,
        'bonus'            => 0,
        'surplus'          => 0,
       	'cod_fee'          => 0,
        'pay_fee'          => 0,
        'tax'              => 0
	);
    $weight = 0;

    /* 商品总价 */
    foreach ($goods AS $key => $val) {
        /* 统计实体商品的个数 */
        if ($val['is_real']) {
            $total['real_goods_count']++;
        }

        $total['goods_price']  += $val['goods_price'] * $val['goods_number'];
        $total['market_price'] += $val['market_price'] * $val['goods_number'];
        $area_id = $consignee['province'];
        //多店铺开启库存管理以及地区后才会去判断
        if ( $area_id > 0 ) {
//         	$db_goods = RC_Loader::load_app_model('goods_model', 'goods');
//         	$goods_model_inventory = $db_goods->where(array('goods_id' => $val['goods_id']))->get_field('model_inventory');
//         	if ($goods_model_inventory > 0 ) {
        		$warehouse_db = RC_Loader::load_app_model('warehouse_model', 'warehouse');
        		$warehouse = $warehouse_db->where(array('regionId' => $area_id))->find();
//         		$area = $warehouse['region_id'];
        		$warehouse_id = $warehouse['parent_id'];
        		$goods[$key]['warehouse_id'] = $warehouse_id;
        		$goods[$key]['area_id'] = $area_id;
//         	}
        }
    }

    $total['saving']    = $total['market_price'] - $total['goods_price'];
    $total['save_rate'] = $total['market_price'] ? round($total['saving'] * 100 / $total['market_price']) . '%' : 0;

    $total['goods_price_formated']  = price_format($total['goods_price'], false);
    $total['market_price_formated'] = price_format($total['market_price'], false);
    $total['saving_formated']       = price_format($total['saving'], false);

    /* 折扣 */
    if ($order['extension_code'] != 'group_buy') {
    	RC_Loader::load_app_func('cart','cart');
        $discount = compute_discount($type = 0, $newInfo = array(), $cart_id);
        $total['discount'] = $discount['discount'];
        if ($total['discount'] > $total['goods_price']) {
            $total['discount'] = $total['goods_price'];
        }
    }
    $total['discount_formated'] = price_format($total['discount'], false);

    /* 税额 */
    if (!empty($order['need_inv']) && $order['inv_type'] != '') {
        /* 查税率 */
        $rate = 0;
        $invoice_type=ecjia::config('invoice_type');
        foreach ($invoice_type['type'] as $key => $type) {
            if ($type == $order['inv_type']) {
            	$rate_str = $invoice_type['rate'];
                $rate = floatval($rate_str[$key]) / 100;
                break;
            }
        }
        if ($rate > 0) {
            $total['tax'] = $rate * $total['goods_price'];
        }
    }
    $total['tax_formated'] = price_format($total['tax'], false);
    //	TODO：暂时注释
    /* 包装费用 */
//     if (!empty($order['pack_id'])) {
//         $total['pack_fee']      = pack_fee($order['pack_id'], $total['goods_price']);
//     }
//     $total['pack_fee_formated'] = price_format($total['pack_fee'], false);

//	TODO：暂时注释
//    /* 贺卡费用 */
//    if (!empty($order['card_id'])) {
//        $total['card_fee']      = card_fee($order['card_id'], $total['goods_price']);
//    }
    $total['card_fee_formated'] = price_format($total['card_fee'], false);

	RC_Loader::load_app_func('bonus', 'bonus');
   	/* 红包 */
	if (!empty($order['bonus_id'])) {
		$bonus          = bonus_info($order['bonus_id']);
		$total['bonus'] = $bonus['type_money'];
	}
	$total['bonus_formated'] = price_format($total['bonus'], false);
    /* 线下红包 */
    if (!empty($order['bonus_kill'])) {

        $bonus  = bonus_info(0,$order['bonus_kill']);
        $total['bonus_kill'] = $order['bonus_kill'];
        $total['bonus_kill_formated'] = price_format($total['bonus_kill'], false);
    }

    /* 配送费用 */
    $shipping_cod_fee = NULL;
    if ($order['shipping_id'] > 0 && $total['real_goods_count'] > 0) {
        $region['country']  = $consignee['country'];
        $region['province'] = $consignee['province'];
        $region['city']     = $consignee['city'];
        $region['district'] = $consignee['district'];

        $shipping_method = RC_Loader::load_app_class('shipping_method', 'shipping');
        $shipping_info 		= $shipping_method->shipping_area_info($order['shipping_id'], $region);

        if (!empty($shipping_info)) {

            if ($order['extension_code'] == 'group_buy') {
                $weight_price = cart_weight_price(CART_GROUP_BUY_GOODS);
            } else {
                $weight_price = cart_weight_price(CART_GENERAL_GOODS, $cart_id);
            }
			if (!empty($cart_id)) {
				$shipping_count_where = array('rec_id' => $cart_id);
			}
            // 查看购物车中是否全为免运费商品，若是则把运费赋为零
            if ($_SESSION['user_id']) {
            	$shipping_count = $db->where(array_merge($shipping_count_where, array('user_id' => $_SESSION['user_id'] , 'extension_code' => array('neq' => 'package_buy') , 'is_shipping' => 0)))->count();
            } else {
            	$shipping_count = $db->where(array_merge($shipping_count_where, array('session_id' => SESS_ID , 'extension_code' => array('neq' => 'package_buy') , 'is_shipping' => 0)))->count();
            }

            //ecmoban模板堂 --zhuo start
            if (ecjia::config('freight_model') == 0) {
            	$total['shipping_fee'] = ($shipping_count == 0 AND $weight_price['free_shipping'] == 1) ? 0 :  $shipping_method->shipping_fee($shipping_info['shipping_code'],$shipping_info['configure'], $weight_price['weight'], $total['goods_price'], $weight_price['number']);
//             	$total['shipping_fee'] = ($shipping_count == 0 AND $weight_price['free_shipping'] == 1) ?0 :  shipping_fee($shipping_info['shipping_code'],$shipping_info['configure'], $weight_price['weight'], $total['goods_price'], $weight_price['number']);
            } elseif (ecjia::config('freight_model') == 1) {
            	$shipping_fee = get_goods_order_shipping_fee($goods, $region, $shipping_info['shipping_code']);
            	$total['shipping_fee'] = ($shipping_count == 0 AND $weight_price['free_shipping'] == 1) ? 0 :  $shipping_fee['shipping_fee'];
//             	$total['ru_list'] = $shipping_fee['ru_list']; //商家运费详细信息
            }

            //ecmoban模板堂 --zhuo end
//             $total['shipping_fee'] = ($shipping_count == 0 AND $weight_price['free_shipping'] == 1) ? 0 :  $shipping_method->shipping_fee($shipping_info['shipping_code'],$shipping_info['configure'], $weight_price['weight'], $total['goods_price'], $weight_price['number']);

            if (!empty($order['need_insure']) && $shipping_info['insure'] > 0) {
                $total['shipping_insure'] = shipping_insure_fee($shipping_info['shipping_code'],$total['goods_price'], $shipping_info['insure']);
            } else {
                $total['shipping_insure'] = 0;
            }

            if ($shipping_info['support_cod']) {
                $shipping_cod_fee = $shipping_info['pay_fee'];
            }
        }
    }

    $total['shipping_fee_formated']    = price_format($total['shipping_fee'], false);
    $total['shipping_insure_formated'] = price_format($total['shipping_insure'], false);

    // 购物车中的商品能享受红包支付的总额
    $bonus_amount = compute_discount_amount($cart_id);
    // 红包和积分最多能支付的金额为商品总额
    $max_amount = $total['goods_price'] == 0 ? $total['goods_price'] : $total['goods_price'] - $bonus_amount;

    /* 计算订单总额 */
    if ($order['extension_code'] == 'group_buy' && $group_buy['deposit'] > 0) {
        $total['amount'] = $total['goods_price'];
    } else {
        $total['amount'] = $total['goods_price'] - $total['discount'] + $total['tax'] + $total['pack_fee'] + $total['card_fee'] + $total['shipping_fee'] + $total['shipping_insure'] + $total['cod_fee'];
        // 减去红包金额
        $use_bonus        = min($total['bonus'], $max_amount); // 实际减去的红包金额
        if(isset($total['bonus_kill'])) {
            $use_bonus_kill   = min($total['bonus_kill'], $max_amount);
            $total['amount'] -=  $price = number_format($total['bonus_kill'], 2, '.', ''); // 还需要支付的订单金额
        }

        $total['bonus']   			= $use_bonus;
        $total['bonus_formated'] 	= price_format($total['bonus'], false);

        $total['amount'] -= $use_bonus; // 还需要支付的订单金额
        $max_amount      -= $use_bonus; // 积分最多还能支付的金额
    }

    /* 余额 */
    $order['surplus'] = $order['surplus'] > 0 ? $order['surplus'] : 0;
    if ($total['amount'] > 0) {
        if (isset($order['surplus']) && $order['surplus'] > $total['amount']) {
            $order['surplus'] = $total['amount'];
            $total['amount']  = 0;
        } else {
            $total['amount'] -= floatval($order['surplus']);
        }
    } else {
        $order['surplus'] = 0;
        $total['amount']  = 0;
    }
    $total['surplus'] 			= $order['surplus'];
    $total['surplus_formated'] 	= price_format($order['surplus'], false);

    /* 积分 */
    $order['integral'] = $order['integral'] > 0 ? $order['integral'] : 0;
    if ($total['amount'] > 0 && $max_amount > 0 && $order['integral'] > 0) {
        $integral_money = value_of_integral($order['integral']);
        // 使用积分支付
        $use_integral            = min($total['amount'], $max_amount, $integral_money); // 实际使用积分支付的金额
        $total['amount']        -= $use_integral;
        $total['integral_money'] = $use_integral;
        $order['integral']       = integral_of_value($use_integral);
    } else {
        $total['integral_money'] = 0;
        $order['integral']       = 0;
    }
    $total['integral'] 			 = $order['integral'];
    $total['integral_formated']  = price_format($total['integral_money'], false);

    /* 保存订单信息 */
    $_SESSION['flow_order'] = $order;
    $se_flow_type = isset($_SESSION['flow_type']) ? $_SESSION['flow_type'] : '';

    /* 支付费用 */
    if (!empty($order['pay_id']) && ($total['real_goods_count'] > 0 || $se_flow_type != CART_EXCHANGE_GOODS)) {
        $total['pay_fee']      	= pay_fee($order['pay_id'], $total['amount'], $shipping_cod_fee);
    }
    $total['pay_fee_formated'] 	= price_format($total['pay_fee'], false);
    $total['amount']           += $total['pay_fee']; // 订单总额累加上支付费用
    $total['amount_formated']  	= price_format($total['amount'], false);

    /* 取得可以得到的积分和红包 */
    if ($order['extension_code'] == 'group_buy') {
        $total['will_get_integral'] = $group_buy['gift_integral'];
    } elseif ($order['extension_code'] == 'exchange_goods') {
        $total['will_get_integral'] = 0;
    } else {
        $total['will_get_integral'] = get_give_integral($goods);
    }

    $total['will_get_bonus']        = $order['extension_code'] == 'exchange_goods' ? 0 : price_format(get_total_bonus(), false);
    $total['formated_goods_price']  = price_format($total['goods_price'], false);
    $total['formated_market_price'] = price_format($total['market_price'], false);
    $total['formated_saving']       = price_format($total['saving'], false);

    if ($order['extension_code'] == 'exchange_goods') {
//         $sql = 'SELECT SUM(eg.exchange_integral) '.
//                'FROM ' . $GLOBALS['ecs']->table('cart') . ' AS c,' . $GLOBALS['ecs']->table('exchange_goods') . 'AS eg '.
//                "WHERE c.goods_id = eg.goods_id AND c.session_id= '" . SESS_ID . "' " .
//                "  AND c.rec_type = '" . CART_EXCHANGE_GOODS . "' " .
//                '  AND c.is_gift = 0 AND c.goods_id > 0 ' .
//                'GROUP BY eg.goods_id';
//         $exchange_integral = $GLOBALS['db']->getOne($sql);
		if ($_SESSION['user_id']) {
			$exchange_integral = $dbview->join('exchange_goods')->where(array('c.user_id' => $_SESSION['user_id'] , 'c.rec_type' => CART_EXCHANGE_GOODS , 'c.is_gift' => 0 ,'c.goods_id' => array('gt' => 0)))->group('eg.goods_id')->sum('eg.exchange_integral');
		} else {
			$exchange_integral = $dbview->join('exchange_goods')->where(array('c.session_id' => SESS_ID , 'c.rec_type' => CART_EXCHANGE_GOODS , 'c.is_gift' => 0 ,'c.goods_id' => array('gt' => 0)))->group('eg.goods_id')->sum('eg.exchange_integral');
		}
    	$total['exchange_integral'] = $exchange_integral;
    }
    return $total;
}

/**
* 修改订单
* @param   int	 $order_id   订单id
* @param   array   $order	  key => value
* @return  bool
*/
function update_order($order_id, $order) {
// 	$db = RC_Loader::load_app_model('order_info_model', 'orders');
// 	return $db->where('order_id = '.$order_id.'')->update($order);
    $db_order_info = RC_DB::table('order_info');
    $db_order_info->where('order_id', $order_id);
    if(!empty($_SESSION['store_id'])){
        $db_order_info->where('store_id', $_SESSION['store_id']);
    }
	return $db_order_info->update($order);
}

/**
* 得到新订单号
* @return  string
*/
function get_order_sn() {
	/* 选择一个随机的方案 */
	mt_srand((double) microtime() * 1000000);
	return date('Ymd') . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
}

/**
* 取得用户信息
* @param   int	 $user_id	用户id
* @return  array   用户信息
*/
function user_info($user_id) {
	$user = RC_DB::table('users')->where('user_id', $user_id)->first();

	unset($user['question']);
	unset($user['answer']);

	/* 格式化帐户余额 */
	if ($user) {
		$user['formated_user_money']	= price_format($user['user_money'], false);
		$user['formated_frozen_money']	= price_format($user['frozen_money'], false);
	}
	return $user;
}

/**
* 修改用户
* @param   int	 $user_id   订单id
* @param   array   $user	  key => value
* @return  bool
*/
function update_user($user_id, $user) {
//  return $GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('users'),$user, 'UPDATE', "user_id = '$user_id'");

	$db_users = RC_Loader::load_app_model("users_model","user");
	return $db_users->where(array('user_id' => $user_id))->update($user);
}

/**
* 取得用户地址列表
* @param   int	 $user_id	用户id
* @return  array
*/
function address_list($user_id) {
//	 $sql = "SELECT * FROM " . $GLOBALS['ecs']->table('user_address') ." WHERE user_id = '$user_id'";
//	 return $GLOBALS['db']->getAll($sql);

// 	$db_users = RC_Loader::load_app_model("user_address_model","user");
// 	return $db_users->where(array('user_id' => $user_id))->select();

	return RC_DB::table('user_address')->where('user_id', $user_id)->get();
}

/**
* 取得用户地址信息
* @param   int	 $address_id	 地址id
* @return  array
*/
function address_info($address_id) {
//  $sql = "SELECT * FROM " . $GLOBALS['ecs']->table('user_address') ." WHERE address_id = '$address_id'";
//  return $GLOBALS['db']->getRow($sql);

	$db_users = RC_Loader::load_app_model("user_address_model","user");
	return $db_users->find(array('address_id' => $address_id));
}


/**
* 计算积分的价值（能抵多少钱）
* @param   int	 $integral   积分
* @return  float   积分价值
*/
function value_of_integral($integral) {
	$scale = floatval(ecjia::config('integral_scale'));
	return $scale > 0 ? round(($integral / 100) * $scale, 2) : 0;
}

/**
* 计算指定的金额需要多少积分
*
* @access  public
* @param   integer $value  金额
* @return  void
*/
function integral_of_value($value) {
	$scale = floatval(ecjia::config('integral_scale'));
	return $scale > 0 ? round($value / $scale * 100) : 0;
}

/**
* 订单退款
* @param   array   $order		  订单
* @param   int	 $refund_type	退款方式 1 到帐户余额 2 到退款申请（先到余额，再申请提款） 3 不处理
* @param   string  $refund_note	退款说明
* @param   float   $refund_amount  退款金额（如果为0，取订单已付款金额）
* @return  bool
*/
function order_refund($order, $refund_type, $refund_note, $refund_amount = 0) {
//	$GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('user_account'), $account, 'INSERT');
//	include_once(ROOT_PATH . 'languages/' .$GLOBALS['_CFG']['lang']. '/admin/order.php');
//	die('anonymous, cannot return to account balance');
//	die('invalid params');

// 	$db = RC_Loader::load_app_model('user_account_model', 'user');
	/* 检查参数 */
	$user_id = $order['user_id'];
	if ($user_id == 0 && $refund_type == 1) {
		ecjia_admin::$controller->showmessage(RC_Lang::get('orders::order.refund_error_notice'), ecjia::MSGTYPE_JSON | ecjia::MSGSTAT_ERROR);
	}

	$amount = $refund_amount > 0 ? $refund_amount : $order['money_paid'];
	if ($amount <= 0) {
		return true;
	}

	if (!in_array($refund_type, array(1, 2, 3))) {
		ecjia_admin::$controller->showmessage(RC_Lang::get('orders::order.error_notice'), ecjia::MSGTYPE_JSON | ecjia::MSGSTAT_ERROR);
	}

	/* 备注信息 */
	if ($refund_note) {
		$change_desc = $refund_note;
	} else {
		$change_desc = sprintf(RC_Lang::lang('order_refund'), $order['order_sn']);
	}

	/* 处理退款 */
	if (1 == $refund_type) {
		$options = array(
			'user_id'		=> $user_id,
			'user_money'	=> $amount,
			'change_desc'	=> $change_desc
		);
		RC_Api::api('user', 'account_change_log', $options);
		return true;
	} elseif (2 == $refund_type) {
		/* 如果非匿名，退回余额 */
		if ($user_id > 0) {
			$options = array(
				'user_id'		=> $user_id,
				'user_money'	=> $amount,
				'change_desc'	=> $change_desc
			);
			RC_Api::api('user', 'account_change_log', $options);
		}

		/* user_account 表增加提款申请记录 */
		$account = array(
			'user_id'		=> $user_id,
			'amount'		=> (-1) * $amount,
			'add_time'		=> RC_Time::gmtime(),
			'user_note'		=> $refund_note,
			'process_type'	=> SURPLUS_RETURN,
			'admin_user'	=> $_SESSION['admin_name'],
			'admin_note'	=> sprintf(RC_Lang::lang('order_refund'), $order['order_sn']),
			'is_paid'		=> 0
		);
// 		$db->insert($account);
		RC_DB::table('user_account')->insert($account);
		return true;
	} else {
		return true;
	}
// 		log_account_change($user_id, $amount, 0, 0, 0, $change_desc);
// 			log_account_change($user_id, $amount, 0, 0, 0, $change_desc);
}



/**
* 查询购物车（订单id为0）或订单中是否有实体商品
* @param   int	 $order_id   订单id
* @param   int	 $flow_type  购物流程类型
* @return  bool
*/
function exist_real_goods($order_id = 0, $flow_type = CART_GENERAL_GOODS) {
//	$sql = "SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('cart') .
//	" WHERE session_id = '" . SESS_ID . "' AND is_real = 1 " ."AND rec_type = '$flow_type'";
//	$sql = "SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('order_goods') .
//	" WHERE order_id = '$order_id' AND is_real = 1";
//	return $GLOBALS['db']->getOne($sql) > 0;

	if ($order_id <= 0) {
		$db_cart = RC_DB::table('cart')->where('is_real', 1)->where('rec_type', $flow_type);

		if ($_SESSION['user_id']) {
			$db_cart->where('user_id', $_SESSION['user_id']);
		} else {
			$db_cart->where('session_id', SESS_ID);
		}
		$query = $db_cart->count();
	} else {
		$query = RC_DB::table('order_goods')->where('order_id', $order_id)->where('is_real', 1)->count();
	}
	return $query > 0;
}

/**
* 查询配送区域属于哪个办事处管辖
* @param   array   $regions	配送区域（1、2、3、4级按顺序）
* @return  int	 办事处id，可能为0
*/
function get_agency_by_regions($regions) {
//	$sql = "SELECT region_id, agency_id " .
//			"FROM " . $GLOBALS['ecs']->table('region') .
//			" WHERE region_id " . db_create_in($regions) .
//			" AND region_id > 0 AND agency_id > 0";
//	$res = $GLOBALS['db']->query($sql);
//	while ($row = $GLOBALS['db']->fetchRow($res))

	$db = RC_Loader::load_app_model('region_model','shipping');
	if (!is_array($regions) || empty($regions)) {
		return 0;
	}

	$arr = array();
	$data = $db->field('region_id, agency_id')->where(array('region_id' => array('gt' => 0) , 'agency_id' => array('gt' => 0)))->in(array('region_id' =>$regions))->select();

	if(!empty($data)) {
		foreach ($data as $row) {
			$arr[$row['region_id']] = $row['agency_id'];
		}
	}
	if (empty($arr)) {
		return 0;
	}

	$agency_id = 0;
	for ($i = count($regions) - 1; $i >= 0; $i--) {
		if (isset($arr[$regions[$i]])) {
			return $arr[$regions[$i]];
		}
	}
}

/**
* 改变订单中商品库存
* @param   int	 $order_id   订单号
* @param   bool	$is_dec	 是否减少库存
* @param   bool	$storage	 减库存的时机，1，下订单时；0，发货时；
*/
function change_order_goods_storage($order_id, $is_dec = true, $storage = 0) {
// 	$db			= RC_Loader::load_app_model('order_goods_model', 'orders');
// 	$db_package	= RC_Loader::load_app_model('package_goods_model', 'goods');
// 	$db_goods	= RC_Loader::load_app_model('goods_model', 'goods');

	/* 查询订单商品信息  */
	switch ($storage) {
		case 0 :
// 		$data = $db->field('goods_id, SUM(send_number) as num, MAX(extension_code) as extension_code, product_id')->where(array('order_id' => $order_id , 'is_real' => 1))->group(array('goods_id', 'product_id'))->select();
		$data = RC_DB::table('order_goods')
			->selectRaw('goods_id, SUM(send_number) as num, MAX(extension_code) as extension_code, product_id')
			->where('order_id', $order_id)->where('is_real', 1)
			->groupby('goods_id')->groupby('product_id')
			->get();
		break;

		case 1 :
// 		$data = $db->field('goods_id, SUM(goods_number) as num, MAX(extension_code) as extension_code, product_id')->where(array('order_id' => $order_id , 'is_real' => 1))->group(array('goods_id', 'product_id'))->select();
		$data = RC_DB::table('order_goods')
			->selectRaw('goods_id, SUM(goods_number) as num, MAX(extension_code) as extension_code, product_id')
			->where('order_id', $order_id)->where('is_real', 1)
			->groupby('goods_id')->groupby('product_id')
			->get();
		break;
	}

	if (!empty($data)) {
		foreach ($data as $row) {
			if ($row['extension_code'] != "package_buy") {
				if ($is_dec) {
					$result = change_goods_storage($row['goods_id'], $row['product_id'], - $row['num']);
				} else {
					$result = change_goods_storage($row['goods_id'], $row['product_id'], $row['num']);
				}
			} else {
// 				$data = $db_package->field('goods_id, goods_number')->where('package_id = "' . $row['goods_id'] . '"')->select();
				$data = RC_DB::table('package_goods')->select('goods_id', 'goods_number')->where('package_id', $row['goods_id'])->get();

				if (!empty($data)) {
					foreach ($data as $row_goods) {
// 						$is_goods = $db_goods->field('is_real')->find('goods_id = "'. $row_goods['goods_id'] .'"');
						$is_goods = RC_DB::table('goods')->select('is_real')->where('goods_id', $row_goods['goods_id'])->first();

						if ($is_dec) {
							$result = change_goods_storage($row_goods['goods_id'], $row['product_id'], - ($row['num'] * $row_goods['goods_number']));
						} elseif ($is_goods['is_real']) {
							$result = change_goods_storage($row_goods['goods_id'], $row['product_id'], ($row['num'] * $row_goods['goods_number']));
						}
					}
				}
			}
		}
	}
	return $result;

//	$sql = "SELECT goods_id, SUM(send_number) AS num, MAX(extension_code) AS extension_code, product_id FROM " . $GLOBALS['ecs']->table('order_goods') .
//	" WHERE order_id = '$order_id' AND is_real = 1 GROUP BY goods_id, product_id";

//	$sql = "SELECT goods_id, SUM(goods_number) AS num, MAX(extension_code) AS extension_code, product_id FROM " . $GLOBALS['ecs']->table('order_goods') .
//	" WHERE order_id = '$order_id' AND is_real = 1 GROUP BY goods_id, product_id";

//	$res = $GLOBALS['db']->query($sql);
//	while ($row = $GLOBALS['db']->fetchRow($res))
//	$GLOBALS['db']->query($sql);

//	$sql = "SELECT goods_id, goods_number" .
//			" FROM " . $GLOBALS['ecs']->table('package_goods') .
//			" WHERE package_id = '" . $row['goods_id'] . "'";
//	$res_goods = $GLOBALS['db']->query($sql);

//	while ($row_goods = $GLOBALS['db']->fetchRow($res_goods))

//	$sql = "SELECT is_real" .
//			" FROM " . $GLOBALS['ecs']->table('goods') .
//			" WHERE goods_id = '" . $row_goods['goods_id'] . "'";
//	$real_goods = $GLOBALS['db']->query($sql);
//	$is_goods = $GLOBALS['db']->fetchRow($real_goods);
}

/**
* 商品库存增与减 货品库存增与减
*
* @param   int	$good_id		 商品ID
* @param   int	$product_id	  货品ID
* @param   int	$number		  增减数量，默认0；
*
* @return  bool			   true，成功；false，失败；
*/
function change_goods_storage($goods_id, $product_id, $number = 0) {
	$db_goods		= RC_Loader::load_app_model('goods_model', 'goods');
	$db_products	= RC_Loader::load_app_model('products_model', 'goods');
	if ($number == 0) {
		return true; // 值为0即不做、增减操作，返回true
	}
	if (empty($goods_id) || empty($number)) {
		return false;
	}
	/* 处理货品库存 */
	$products_query = true;
	if (!empty($product_id)) {
		/* by will.chen start*/
// 		$product_number = $db_products->where(array('goods_id' => $goods_id, 'product_id' => $product_id))->get_field('product_number');
		$product_number = RC_DB::table('products')->where('goods_id', $goods_id)->where('product_id', $product_id)->pluck('product_number');

		if ($product_number < $number) {
			return new ecjia_error('low_stocks', RC_Lang::get('orders::order.goods_num_err'));
		}
		/* end*/
// 		$products_query = $db_products->inc('product_number', 'goods_id='.$goods_id.' and product_id='.$product_id, $number);
		$products_query = RC_DB::table('products')->where('goods_id', $goods_id)->where('product_id', $product_id)->increment('product_number', $number);
	}

	/* by will.chen start*/
// 	$goods_number = $db_goods->where(array('goods_id' => $goods_id))->get_field('goods_number');
	$goods_number = RC_DB::table('goods')->where('goods_id', $goods_id)->pluck('goods_number');

	if ($goods_number < $number) {
		return new ecjia_error('low_stocks', RC_Lang::get('orders::order.goods_num_err'));
	}
	/* end*/
	/* 处理商品库存 */
// 	$query = $db_goods->inc('goods_number', 'goods_id='.$goods_id, $number);
	$query = RC_DB::table('goods')->where('goods_id', $goods_id)->increment('goods_number', $number);

	if ($query && $products_query) {
		return true;
	} else {
		return false;
	}

	// 	$number = ($number >= 0) ? '+ ' . $number : $number;
	// 		$data = 'product_number = product_number'.$number;
	// 		$products_query = $db_products->where(array('goods_id' => $goods_id , 'product_id' => $product_id))->update($data);
	// 	$data = 'goods_number = goods_number'.$number;
	// 	$query = $db_goods->where(array('goods_id' => $goods_id))->update($data);

//	$sql = "UPDATE " . $GLOBALS['ecs']->table('products') ."
//	SET product_number = product_number $number
//	WHERE goods_id = '$good_id'
//	AND product_id = '$product_id'
//	LIMIT 1";
//	$products_query = $GLOBALS['db']->query($sql);

//	$sql = "UPDATE " . $GLOBALS['ecs']->table('goods') ."
//	SET goods_number = goods_number $number
//	WHERE goods_id = '$good_id' LIMIT 1";
//	$query = $GLOBALS['db']->query($sql);
}

/**
* 生成查询订单总金额的字段
* @param   string  $alias  order表的别名（包括.例如 o.）
* @return  string
*/
function order_amount_field($alias = '') {
	return "   {$alias}goods_amount + {$alias}tax + {$alias}shipping_fee" .
	" + {$alias}insure_fee + {$alias}pay_fee + {$alias}pack_fee" .
	" + {$alias}card_fee ";
}

/**
* 生成计算应付款金额的字段
* @param   string  $alias  order表的别名（包括.例如 o.）
* @return  string
*/
function order_due_field($alias = '') {
	return order_amount_field($alias) .
	" - {$alias}money_paid - {$alias}surplus - {$alias}integral_money" .
	" - {$alias}bonus - {$alias}discount ";
}


/**
* 取得某订单应该赠送的积分数
* @param   array   $order  订单
* @return  int	 积分数
*/
function integral_to_give($order) {
// 	$dbview = RC_Loader::load_app_model('order_order_goods_viewmodel', 'orders');
    /* 判断是否团购 */
// 	TODO:团购暂时注释给的固定参数
	$order['extension_code'] = '';
    if ($order['extension_code'] == 'group_buy') {
		RC_Loader::load_app_func('goods', 'goods');
		//TODO wu
        $group_buy = group_buy_info(intval($order['extension_id']));
        return array('custom_points' => $group_buy['gift_integral'], 'rank_points' => $order['goods_amount']);
    } else {
//     	$dbview->view = array(
//     		'goods' => array(
//     			'type'  => Component_Model_View::TYPE_LEFT_JOIN,
//     			'alias' => 'g',
//     			'field' => 'SUM(o.goods_number * IF(g.give_integral > -1, g.give_integral, o.goods_price)) AS custom_points, SUM(o.goods_number * IF(g.rank_integral > -1, g.rank_integral, o.goods_price)) AS rank_points',
//     			'on'    => 'o.goods_id = g.goods_id ',
//     		)
//     	);
//     	return $dbview->find(array('o.order_id' => $order['order_id'] , 'o.goods_id' => array('gt' => 0 ) , 'o.parent_id' => 0 , 'o.is_gift' => 0 , 'o.extension_code' => array('neq' => 'package_buy')));

    	return RC_DB::table('order_goods as o')->leftJoin('goods as g', RC_DB::raw('o.goods_id'), '=', RC_DB::raw('g.goods_id'))
    		->select(RC_DB::raw('SUM(o.goods_number * IF(g.give_integral > -1, g.give_integral, o.goods_price)) AS custom_points, SUM(o.goods_number * IF(g.rank_integral > -1, g.rank_integral, o.goods_price)) AS rank_points'))
    		->where(RC_DB::raw('o.order_id'), $order['order_id'])
    		->where(RC_DB::raw('o.goods_id'), '>', 0)
    		->where(RC_DB::raw('o.parent_id'), '=', 0)
    		->where(RC_DB::raw('o.is_gift'), '=', 0)
    		->where(RC_DB::raw('o.extension_code'), '!=', 'package_buy')
    		->first();
    }
    //         include_once(ROOT_PATH . 'includes/lib_goods.php');
    // 	$sql = "SELECT SUM(o.goods_number * IF(g.give_integral > -1, g.give_integral, o.goods_price)) AS custom_points, SUM(o.goods_number * IF(g.rank_integral > -1, g.rank_integral, o.goods_price)) AS rank_points " .
    // 			"FROM " . $GLOBALS['ecs']->table('order_goods') . " AS o, " .
    // 			$GLOBALS['ecs']->table('goods') . " AS g " .
    // 			"WHERE o.goods_id = g.goods_id " .
    // 			"AND o.order_id = '$order[order_id]' " .
    // 			"AND o.goods_id > 0 " .
    // 			"AND o.parent_id = 0 " .
    // 			"AND o.is_gift = 0 AND o.extension_code != 'package_buy'";
    // 	return $GLOBALS['db']->getRow($sql);
}

/**
* 发红包：发货时发红包
* @param   int	 $order_id   订单号
* @return  bool
*/
function send_order_bonus($order_id) {
// 	RC_Loader::load_app_func('common', 'goods');
// 	$db		=  RC_Loader::load_app_model('user_bonus_model', 'bonus');
// 	$dbview	=  RC_Loader::load_app_model('order_info_viewmodel', 'orders');
	/* 取得订单应该发放的红包 */
	$bonus_list = order_bonus($order_id);

	/* 如果有红包，统计并发送 */
	if ($bonus_list) {
		/* 用户信息 */
// 		$dbview->view = array(
// 			'users' => array(
// 				'type'	=> Component_Model_View::TYPE_LEFT_JOIN,
// 				'alias'	=> 'u',
// 				'field'	=> 'u.user_id, u.user_name, u.email',
// 				'on'	=> 'oi.user_id = u.user_id ',
// 			)
// 		);
// 		$user = $dbview->find(array('oi.order_id' => $order_id));

		$user = RC_DB::table('order_info as oi')
			->leftJoin('users as u', RC_DB::raw('oi.user_id'), '=', RC_DB::raw('u.user_id'))
			->select(RC_DB::raw('u.user_id, u.user_name, u.email'))
			->where(RC_DB::raw('oi.order_id'), $order_id)
			->first();

		/* 统计 */
		$count = 0;
		$money = '';
		foreach ($bonus_list AS $bonus) {
			$count += $bonus['number'];
			$money .= price_format($bonus['type_money']) . ' [' . $bonus['number'] . '], ';

			/* 修改用户红包 */
			$data = array(
				'bonus_type_id' => $bonus['type_id'],
				'user_id'	   	=> $user['user_id']
			);

			for ($i = 0; $i < $bonus['number']; $i++) {
// 				if (!$db->insert($data)) {
// 					return $db->errorMsg();
// 				}
				$id = RC_DB::table('user_bonus')->insertGetId($data);
			}
		}

		/* 如果有红包，发送邮件 */
		if ($count > 0) {
			$tpl_name = 'send_bonus';
			$tpl = RC_Api::api('mail', 'mail_template', $tpl_name);

			ecjia_front::$controller->assign('user_name', $user['user_name']);
			ecjia_front::$controller->assign('count', $count);
			ecjia_front::$controller->assign('money', $money);
			ecjia_front::$controller->assign('shop_name', ecjia::config('shop_name'));
			ecjia_front::$controller->assign('send_date', RC_Time::local_date(ecjia::config('date_format')));

			$content = ecjia_front::$controller->fetch_string($tpl['template_content']);
			RC_Mail::send_mail($user['user_name'], $user['email'] , $tpl['template_subject'], $content, $tpl['is_html']);
		}
	}
	return true;

//	$sql = "SELECT u.user_id, u.user_name, u.email " .
//			"FROM " . $GLOBALS['ecs']->table('order_info') . " AS o, " .
//			$GLOBALS['ecs']->table('users') . " AS u " .
//			"WHERE o.order_id = '$order_id' " .
//			"AND o.user_id = u.user_id ";
//	$user = $GLOBALS['db']->getRow($sql);

//	$sql = "INSERT INTO " . $GLOBALS['ecs']->table('user_bonus') . " (bonus_type_id, user_id) " .
//			"VALUES('$bonus[type_id]', '$user[user_id]')";
//	if (!$GLOBALS['db']->query($sql))
}

/**
* 返回订单发放的红包
* @param   int	 $order_id   订单id
*/
function return_order_bonus($order_id) {
// 	$db	= RC_Loader::load_app_model('user_bonus_model', 'bonus');
	/* 取得订单应该发放的红包 */
	$bonus_list = order_bonus($order_id);

	/* 删除 */
	if ($bonus_list) {
		/* 取得订单信息 */
		$order = order_info($order_id);
		$user_id = $order['user_id'];
		foreach ($bonus_list AS $bonus) {
// 			$db->where(array('bonus_type_id' => $bonus[type_id] , 'user_id' => $user_id , 'order_id' => 0))->limit($bonus['number'])->delete();
			RC_DB::table('user_bonus')
				->where('bonus_type_id', $bonus['type_id'])
				->where('user_id', $user_id)
				->where('order_id', 0)
				->limit($bonus['number'])
				->delete();
		}
	}

//	$sql = "DELETE FROM " . $GLOBALS['ecs']->table('user_bonus') .
//	" WHERE bonus_type_id = '$bonus[type_id]' " .
//	"AND user_id = '$user_id' " .
//	"AND order_id = '0' LIMIT " . $bonus['number'];
//	$GLOBALS['db']->query($sql);
}

/**
* 取得订单应该发放的红包
* @param   int	 $order_id   订单id
* @return  array
*/
function order_bonus($order_id) {
// 	$db_bonus_type	= RC_Loader::load_app_model('bonus_type_model', 'bonus');
// 	$db_order_info	= RC_Loader::load_app_model('order_info_model', 'orders');
 // $dbview			= RC_Loader::load_app_model('order_order_goods_viewmodel', 'orders');

	/* 查询按商品发的红包 */
// 	$day	= getdate();
// 	$today	= RC_Time::local_mktime(23, 59, 59, $day['mon'], $day['mday'], $day['year']);
	$store_id = RC_DB::table('order_info')->where('order_id', $order_id)->pluck('store_id');
	$today = RC_Time::gmtime();

	// $dbview->view = array(
	// 	'goods' => array(
	// 		'type'	=> Component_Model_View::TYPE_LEFT_JOIN,
	// 		'alias'	=> 'g',
	// 		'field'	=> 'b.type_id, b.type_money, SUM(o.goods_number) AS number',
	// 		'on'	=> 'o.goods_id = g.goods_id',
	// 	),
	// 	'bonus_type' => array(
	// 		'type'	=> Component_Model_View::TYPE_LEFT_JOIN,
	// 		'alias'	=> 'b',
	// 		'on'	=> 'g.bonus_type_id = b.type_id',
	// 	)
	// );
	// $list = $dbview->where(array('o.order_id' => $order_id , 'o.is_gift' => 0 , 'b.send_type' => SEND_BY_GOODS , 'b.send_start_date' => array('elt' => $today) , 'b.send_end_date' => array('egt' => $today)))->group('b.type_id')->select();

	$list = RC_DB::table('order_goods as o')
		->leftJoin('goods as g', RC_DB::raw('o.goods_id'), '=', RC_DB::raw('g.goods_id'))
		->leftJoin('bonus_type as b', RC_DB::raw('g.bonus_type_id'), '=', RC_DB::raw('b.type_id'))
		->selectRaw('b.type_id, b.type_money, SUM(o.goods_number) AS number')
		->whereRaw('o.order_id = ' . $order_id . ' and o.is_gift = 0 and b.send_type = ' . SEND_BY_GOODS . ' and b.send_start_date <= ' . $today . ' and b.send_end_date >= ' . $today. ' and (b.store_id = '. $store_id .' OR b.store_id = 0 )')
		->groupby(RC_DB::raw('b.type_id'))
		->get();
	/* 查询定单中非赠品总金额 */
	$amount = order_amount($order_id, false);

	/* 查询订单日期 */
// 	$order_time = $db_order_info->where(array('order_id' => $order_id))->get_field('add_time');
	$order_time = RC_DB::table('order_info')->where('order_id', $order_id)->pluck('add_time');

	/* 查询按订单发的红包 */
// 	$data = $db_bonus_type->field('type_id, type_money, IFNULL(FLOOR('.$amount.' / min_amount), 1)|number')->where(array('send_type' => SEND_BY_ORDER , 'send_start_date' => array('elt' => $order_time) ,  'send_end_date' => array('egt' => $order_time)))->select();
	$data = RC_DB::table('bonus_type')->select('type_id', 'type_money', RC_DB::raw('IFNULL(FLOOR('.$amount.' / min_amount), 1) as number'))
		// ->where('send_type', SEND_BY_ORDER)->where('send_start_date', '<=', $order_time)->where('send_end_date', '>=', $order_time)
		->whereRaw('send_type = '. SEND_BY_ORDER .' AND send_start_date <='. $order_time . ' AND send_end_date >= '. $order_time . ' AND (store_id = '. $store_id .' OR store_id = 0)')
		->get();
	if (!empty($data)) {
		$list = array_merge($list, $data);
	}
	return $list;

//	$sql = "SELECT b.type_id, b.type_money, SUM(o.goods_number) AS number " .
//			"FROM " . $GLOBALS['ecs']->table('order_goods') . " AS o, " .
//			$GLOBALS['ecs']->table('goods') . " AS g, " .
//			$GLOBALS['ecs']->table('bonus_type') . " AS b " .
//			" WHERE o.order_id = '$order_id' " .
//			" AND o.is_gift = 0 " .
//			" AND o.goods_id = g.goods_id " .
//			" AND g.bonus_type_id = b.type_id " .
//			" AND b.send_type = '" . SEND_BY_GOODS . "' " .
//			" AND b.send_start_date <= '$today' " .
//			" AND b.send_end_date >= '$today' " .
//			" GROUP BY b.type_id ";
//	$list = $GLOBALS['db']->getAll($sql);

//	$sql = "SELECT add_time " ." FROM " . $GLOBALS['ecs']->table('order_info') ." WHERE order_id = '$order_id' LIMIT 1";
//	$order_time = $GLOBALS['db']->getOne($sql);

//	$sql = "SELECT type_id, type_money, IFNULL(FLOOR('$amount' / min_amount), 1) AS number " .
//	"FROM " . $GLOBALS['ecs']->table('bonus_type') ."WHERE send_type = '" . SEND_BY_ORDER . "' " .
//	"AND send_start_date <= '$order_time' " ."AND send_end_date >= '$order_time' ";
//	$list = array_merge($list, $GLOBALS['db']->getAll($sql));
}

/**
* 得到新发货单号
* @return  string
*/
function get_delivery_sn() {
	/* 选择一个随机的方案 */
	mt_srand((double) microtime() * 1000000);
	return date('YmdHi') . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
}

/**
 * 记录订单操作记录
 *
 * @access public
 * @param string $order_sn
 *        	订单编号
 * @param integer $order_status
 *        	订单状态
 * @param integer $shipping_status
 *        	配送状态
 * @param integer $pay_status
 *        	付款状态
 * @param string $note
 *        	备注
 * @param string $username
 *        	用户名，用户自己的操作则为 buyer
 * @return void
 */
function order_action($order_sn, $order_status, $shipping_status, $pay_status, $note = '', $username = null, $place = 0) {
	// 	$sql = 'INSERT INTO ' . $GLOBALS['ecs']->table('order_action') .
	// 	' (order_id, action_user, order_status, shipping_status, pay_status, action_place, action_note, log_time) ' .
	// 	'SELECT ' .
	// 	"order_id, '$username', '$order_status', '$shipping_status', '$pay_status', '$place', '$note', '" .gmtime() . "' " .
	// 	'FROM ' . $GLOBALS['ecs']->table('order_info') . " WHERE order_sn = '$order_sn'";
	// 	$GLOBALS['db']->query($sql);

// 	$db_action = RC_Loader::load_app_model ( 'order_action_model', 'orders' );
// 	$db_info = RC_Loader::load_app_model ( 'order_info_model', 'orders' );
	if (empty($username)) {
		$username = empty($_SESSION['admin_name'])? $_SESSION['staff_name'] : $_SESSION['admin_name'];
	}

// 	$row = $db_info->field('order_id')->find(array('order_sn' => $order_sn));
	$row = RC_DB::table('order_info')->where('order_sn', $order_sn)->first();

	$data = array (
		'order_id'           => $row ['order_id'],
		'action_user'        => $username,
		'order_status'       => $order_status,
		'shipping_status'    => $shipping_status,
		'pay_status'         => $pay_status,
		'action_place'       => $place,
		'action_note'        => $note,
		'log_time'           => RC_Time::gmtime()
	);
// 	$db_action->insert($data);
	RC_DB::table('order_action')->insert($data);
}


///**
//* 获取配送插件的实例
//* @param   int   $shipping_id	配送插件ID
//* @return  object	 配送插件对象实例
//*/
//function &get_shipping_object($shipping_id) {
////	$file_path = ROOT_PATH.'includes/modules/shipping/' . $shipping['shipping_code'] . '.php';
////	include_once($file_path);
//	$shipping_method = RC_Loader::load_app_class("shipping_method","shipping");
//	$shipping = $shipping_method->shipping_info($shipping_id);
//	if (!$shipping) {
//		$object = new stdClass();
//		return $object;
//	}
//
//	RC_Loader::load_app_module($shipping['shipping_code'],'shipping');
//	$object = new $shipping['shipping_code'];
//	return $object;
//}

//
///**
//* 检查某商品是否已经存在于购物车
//*
//* @access  public
//* @param   integer	 $id
//* @param   array	   $spec
//* @param   int		 $type   类型：默认普通商品
//* @return  boolean
//*/
//function cart_goods_exists($id, $spec, $type = CART_GENERAL_GOODS) {
//	/* 检查该商品是否已经存在在购物车中 */
////	 $sql = "SELECT COUNT(*) FROM " .$GLOBALS['ecs']->table('cart').
////			 "WHERE session_id = '" .SESS_ID. "' AND goods_id = '$id' ".
////			 "AND parent_id = 0 AND goods_attr = '" .get_goods_attr_info($spec). "' " .
////			 "AND rec_type = '$type'";
//
////	 return ($GLOBALS['db']->getOne($sql) > 0);
//
//	$db = RC_Loader::load_app_model('cart_model','flow');
//	return $db->where('session_id = "'.SESS_ID. '" AND goods_id = '.$id.' AND parent_id = 0 AND goods_attr = " '.get_goods_attr_info($spec). '" AND rec_type = "'.$type.'"')->count();
//}

///**
//* 添加商品到购物车
//*
//* @access  public
//* @param   integer $goods_id   商品编号
//* @param   integer $num		商品数量
//* @param   array   $spec	   规格值对应的id数组
//* @param   integer $parent	 基本件
//* @return  boolean
//*/
//function addto_cart($goods_id, $num = 1, $spec = array(), $parent = 0) {
//	$dbview			= RC_Loader::load_app_model('sys_goods_member_viewmodel','goods');
//	$db_cart		= RC_Loader::load_app_model('cart_model','flow');
//	$db_products	= RC_Loader::load_app_model('products_model','goods');
//	$db_group		= RC_Loader::load_app_model('group_goods_model','goods');
//	$_parent_id	= $parent;
//
//	/* 取得商品信息 */
//	$dbview->view = array(
//		'member_price' => array(
//			'type'	=> Component_Model_View::TYPE_LEFT_JOIN,
//			'alias'	=> 'mp',
//			'field'	=> "g.goods_name, g.goods_sn, g.is_on_sale, g.is_real,g.market_price, g.shop_price AS org_price, g.promote_price, g.promote_start_date,g.promote_end_date, g.goods_weight, g.integral, g.extension_code,g.goods_number, g.is_alone_sale, g.is_shipping,IFNULL(mp.user_price, g.shop_price * '$_SESSION[discount]') AS shop_price",
//			'on'	=> "mp.goods_id = g.goods_id AND mp.user_rank = '$_SESSION[user_rank]'"
//		)
//	);
//	$goods = $dbview->find(array('g.goods_id' => $goods_id , 'g.is_delete' => 0));
//	if (empty($goods)) {
//		$GLOBALS['err']->add(RC_Lang::lang('goods_not_exists'), ERR_NOT_EXISTS);
//		return false;
//	}
//
//	/* 如果是作为配件添加到购物车的，需要先检查购物车里面是否已经有基本件 */
//	if ($parent > 0) {
//		$count = $db_cart->where(array('goods_id' => $parent , 'session_id' => SESS_ID , 'extension_code' => array('neq' => 'package_buy')))->count();
//		if ($count == 0) {
//			$GLOBALS['err']->add(RC_Lang::lang('no_basic_goods'), ERR_NO_BASIC_GOODS);
//			return false;
//		}
//	}
//
//	/* 是否正在销售 */
//	if ($goods['is_on_sale'] == 0) {
//		$GLOBALS['err']->add(RC_Lang::lang('not_on_sale'), ERR_NOT_ON_SALE);
//		return false;
//	}
//
//	/* 不是配件时检查是否允许单独销售 */
//	if (empty($parent) && $goods['is_alone_sale'] == 0) {
//		$GLOBALS['err']->add(RC_Lang::lang('cannt_alone_sale'), ERR_CANNT_ALONE_SALE);
//		return false;
//	}
//
//	/* 如果商品有规格则取规格商品信息 配件除外 */
//	$prod = $db_products->find(array('goods_id' => $goods_id));
//	if (is_spec($spec) && !empty($prod)) {
//		$product_info = get_products_info($goods_id, $spec);
//	}
//	if (empty($product_info)) {
//		$product_info = array('product_number' => '', 'product_id' => 0);
//	}
//
//	/* 检查：库存 */
//	if (ecjia::config('use_storage') == 1) {
//		//检查：商品购买数量是否大于总库存
//		if ($num > $goods['goods_number']) {
//			$GLOBALS['err']->add(sprintf(RC_Lang::lang('shortage'), $goods['goods_number']), ERR_OUT_OF_STOCK);
//			return false;
//		}
//
//		//商品存在规格 是货品 检查该货品库存
//		if (is_spec($spec) && !empty($prod)) {
//			if (!empty($spec)) {
//				/* 取规格的货品库存 */
//				if ($num > $product_info['product_number']) {
//					$GLOBALS['err']->add(sprintf(RC_Lang::lang('shortage'), $product_info['product_number']), ERR_OUT_OF_STOCK);
//					return false;
//				}
//			}
//		}
//	}
//
//	/* 计算商品的促销价格 */
//	$spec_price				= spec_price($spec);
//	$goods_price			= get_final_price($goods_id, $num, true, $spec);
//	$goods['market_price']	+= $spec_price;
//	$goods_attr				= get_goods_attr_info($spec);
//	$goods_attr_id			= join(',', $spec);
//
//	/* 初始化要插入购物车的基本件数据 */
//	$parent = array(
//		'user_id'			=> $_SESSION['user_id'],
//		'session_id'		=> SESS_ID,
//		'goods_id'			=> $goods_id,
//		'goods_sn'			=> addslashes($goods['goods_sn']),
//		'product_id'		=> $product_info['product_id'],
//		'goods_name'		=> addslashes($goods['goods_name']),
//		'market_price'		=> $goods['market_price'],
//		'goods_attr'		=> addslashes($goods_attr),
//		'goods_attr_id'		=> $goods_attr_id,
//		'is_real'			=> $goods['is_real'],
//		'extension_code'	=> $goods['extension_code'],
//		'is_gift'			=> 0,
//		'is_shipping'		=> $goods['is_shipping'],
//		'rec_type'			=> CART_GENERAL_GOODS
//	);
//
//	/* 如果该配件在添加为基本件的配件时，所设置的“配件价格”比原价低，即此配件在价格上提供了优惠， */
//	/* 则按照该配件的优惠价格卖，但是每一个基本件只能购买一个优惠价格的“该配件”，多买的“该配件”不享 */
//	/* 受此优惠 */
//	$basic_list = array();
//	$data = $db_group->field('parent_id, goods_price')->where('goods_id = '.$goods_id.' AND goods_price < "'.$goods_price.'" AND parent_id = '.$_parent_id.'')->order('goods_price asc')->select();
//
//	if(!empty($data)) {
//		foreach ($data as $row) {
//			$basic_list[$row['parent_id']] = $row['goods_price'];
//		}
//	}
//	/* 取得购物车中该商品每个基本件的数量 */
//	$basic_count_list = array();
//	if ($basic_list) {
//		$data = $db_cart->field('goods_id, SUM(goods_number)|count')->where('session_id = "'.SESS_ID.'" AND parent_id = 0 AND extension_code <> "package_buy"')->in(array('goods_id'=>array_keys($basic_list)))->order('goods_id asc')->select();
//		if(!empty($data)) {
//			foreach ($data as $row) {
//				$basic_count_list[$row['goods_id']] = $row['count'];
//			}
//		}
//	}
//
//	/* 取得购物车中该商品每个基本件已有该商品配件数量，计算出每个基本件还能有几个该商品配件 */
//	/* 一个基本件对应一个该商品配件 */
//	if ($basic_count_list) {
//		$data = $db_cart->field('parent_id, SUM(goods_number)|count')->where('session_id = "'.SESS_ID.'" AND goods_id = '.$goods_id.' AND extension_code <> "package_buy"')->in(array('parent_id'=>array_keys($basic_count_list)))->order('parent_id asc')->select();
//		if(!empty($data)) {
//			foreach ($data as $row) {
//				$basic_count_list[$row['parent_id']] -= $row['count'];
//			}
//		}
//	}
//
//	/* 循环插入配件 如果是配件则用其添加数量依次为购物车中所有属于其的基本件添加足够数量的该配件 */
//	foreach ($basic_list as $parent_id => $fitting_price) {
//		/* 如果已全部插入，退出 */
//		if ($num <= 0) {
//			break;
//		}
//
//		/* 如果该基本件不再购物车中，执行下一个 */
//		if (!isset($basic_count_list[$parent_id])) {
//			continue;
//		}
//
//		/* 如果该基本件的配件数量已满，执行下一个基本件 */
//		if ($basic_count_list[$parent_id] <= 0) {
//			continue;
//		}
//
//		/* 作为该基本件的配件插入 */
//		$parent['goods_price']	= max($fitting_price, 0) + $spec_price; //允许该配件优惠价格为0
//		$parent['goods_number']	= min($num, $basic_count_list[$parent_id]);
//		$parent['parent_id']	= $parent_id;
//
//		/* 添加 */
//		$db_cart->insert($parent);
//		/* 改变数量 */
//		$num -= $parent['goods_number'];
//	}
//
//	/* 如果数量不为0，作为基本件插入 */
//	if ($num > 0) {
//		/* 检查该商品是否已经存在在购物车中 */
//		$row = $db_cart->field('goods_number')->find('session_id = "' .SESS_ID. '" AND goods_id = '.$goods_id.' AND parent_id = 0 AND goods_attr = "' .get_goods_attr_info($spec).'" AND extension_code <> "package_buy" AND rec_type = "'.CART_GENERAL_GOODS.'" ');
//
//		if($row) {
//	//如果购物车已经有此物品，则更新
//			$num += $row['goods_number'];
//			if(is_spec($spec) && !empty($prod) ) {
//				$goods_storage=$product_info['product_number'];
//			} else {
//				$goods_storage=$goods['goods_number'];
//			}
//			if (ecjia::config('use_storage') == 0 || $num <= $goods_storage) {
//				$goods_price = get_final_price($goods_id, $num, true, $spec);
//				$data =  array(
//					'goods_number'	=> $num,
//					'goods_price'	=> $goods_price
//					);
//				$db_cart->where('session_id = "' .SESS_ID. '" AND goods_id = '.$goods_id.' AND parent_id = 0 AND goods_attr = "' .get_goods_attr_info($spec).'" AND extension_code <> "package_buy" AND rec_type = "'.CART_GENERAL_GOODS.'" ')->update($data);
//			} else {
//				$GLOBALS['err']->add(sprintf(RC_Lang::lang('shortage'), $num), ERR_OUT_OF_STOCK);
//				return false;
//			}
//		} else {
//	//购物车没有此物品，则插入
//			$goods_price = get_final_price($goods_id, $num, true, $spec);
//			$parent['goods_price']	= max($goods_price, 0);
//			$parent['goods_number']	= $num;
//			$parent['parent_id']	= 0;
//			$db_cart->insert($parent);
//		}
//	}
//
//	/* 把赠品删除 */
//	$db_cart->where(array('session_id' => SESS_ID , 'is_gift' => array('neq' => 0)))->delete();
//	return true;
//
////	$GLOBALS['err']->clean();
////	$sql = "SELECT g.goods_name, g.goods_sn, g.is_on_sale, g.is_real, ".
////			"g.market_price, g.shop_price AS org_price, g.promote_price, g.promote_start_date, ".
////			"g.promote_end_date, g.goods_weight, g.integral, g.extension_code, ".
////			"g.goods_number, g.is_alone_sale, g.is_shipping,".
////			"IFNULL(mp.user_price, g.shop_price * '$_SESSION[discount]') AS shop_price ".
////			" FROM " .$GLOBALS['ecs']->table('goods'). " AS g ".
////			" LEFT JOIN " . $GLOBALS['ecs']->table('member_price') . " AS mp ".
////			"ON mp.goods_id = g.goods_id AND mp.user_rank = '$_SESSION[user_rank]' ".
////			" WHERE g.goods_id = '$goods_id'" .
////			" AND g.is_delete = 0";
////	$goods = $GLOBALS['db']->getRow($sql);
//
////	$sql = "SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('cart') .
////	" WHERE goods_id='$parent' AND session_id='" . SESS_ID . "' AND extension_code <> 'package_buy'";
////	if ($GLOBALS['db']->getOne($sql) == 0)
//
////	$sql = "SELECT * FROM " .$GLOBALS['ecs']->table('products'). " WHERE goods_id = '$goods_id' LIMIT 0, 1";
////	$prod = $GLOBALS['db']->getRow($sql);
//
////	$sql = "SELECT parent_id, goods_price " .
////			"FROM " . $GLOBALS['ecs']->table('group_goods') .
////			" WHERE goods_id = '$goods_id'" .
////			" AND goods_price < '$goods_price'" .
////			" AND parent_id = '$_parent_id'" .
////			" ORDER BY goods_price";
////	$res = $GLOBALS['db']->query($sql);
////	while ($row = $GLOBALS['db']->fetchRow($res))
//
////	$sql = "SELECT goods_id, SUM(goods_number) AS count " .
////			"FROM " . $GLOBALS['ecs']->table('cart') .
////			" WHERE session_id = '" . SESS_ID . "'" .
////			" AND parent_id = 0" .
////			" AND extension_code <> 'package_buy' " .
////			" AND goods_id " . db_create_in(array_keys($basic_list)) .
////			" GROUP BY goods_id";
////	$res = $GLOBALS['db']->query($sql);
////	while ($row = $GLOBALS['db']->fetchRow($res))
//
////	$sql = "SELECT parent_id, SUM(goods_number) AS count " .
////			"FROM " . $GLOBALS['ecs']->table('cart') .
////			" WHERE session_id = '" . SESS_ID . "'" .
////			" AND goods_id = '$goods_id'" .
////			" AND extension_code <> 'package_buy' " .
////			" AND parent_id " . db_create_in(array_keys($basic_count_list)) .
////			" GROUP BY parent_id";
////	$res = $GLOBALS['db']->query($sql);
////	while ($row = $GLOBALS['db']->fetchRow($res))
////	$GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('cart'), $parent, 'INSERT');
//
////	$sql = "SELECT goods_number FROM " .$GLOBALS['ecs']->table('cart').
////	" WHERE session_id = '" .SESS_ID. "' AND goods_id = '$goods_id' ".
////	" AND parent_id = 0 AND goods_attr = '" .get_goods_attr_info($spec). "' " .
////	" AND extension_code <> 'package_buy' " .
////	" AND rec_type = 'CART_GENERAL_GOODS'";
////	$row = $GLOBALS['db']->getRow($sql);
//
////	$sql = "UPDATE " . $GLOBALS['ecs']->table('cart') . " SET goods_number = '$num'" .
////	" , goods_price = '$goods_price'".
////	" WHERE session_id = '" .SESS_ID. "' AND goods_id = '$goods_id' ".
////	" AND parent_id = 0 AND goods_attr = '" .get_goods_attr_info($spec). "' " .
////	" AND extension_code <> 'package_buy' " .
////	"AND rec_type = 'CART_GENERAL_GOODS'";
////	$GLOBALS['db']->query($sql);
////	$GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('cart'), $parent, 'INSERT');
//
////	$sql = "DELETE FROM " . $GLOBALS['ecs']->table('cart') . " WHERE session_id = '" . SESS_ID . "' AND is_gift <> 0";
////	$GLOBALS['db']->query($sql);
//
//}

/**
* 获得指定的商品属性
* @access	  public
* @param	   array	   $arr		规格、属性ID数组
* @param	   type		$type	   设置返回结果类型：pice，显示价格，默认；no，不显示价格
* @return	  string
*/
function get_goods_attr_info($arr, $type = 'pice', $warehouse_id = 0, $area_id = 0) {
	$dbview = RC_Loader::load_app_model('goods_attr_viewmodel','goods');
    $attr   = '';
    if (!empty($arr)) {
        $fmt = "%s:%s[%s] \n";

//        $dbview->view =array(
// 				'attribute' => array(
// 				     'type' 	=> Component_Model_View::TYPE_LEFT_JOIN,
// 					 'alias' 	=> 'a',
// 					 'field' 	=> 'a.attr_name, ga.attr_value, ga.attr_price',
// 					 'on' 		=> 'a.attr_id = ga.attr_id'
// 				)
// 		);
		$field = "ga.goods_attr_id, a.attr_name, ga.attr_value, ".
				" IF(g.model_attr < 1, ga.attr_price, IF(g.model_attr < 2, wap.attr_price, wa.attr_price)) as attr_price ";
		$dbview->view = array(
			'attribute' => array(
			     'type' 	=> Component_Model_View::TYPE_LEFT_JOIN,
				 'alias' 	=> 'a',
				 'on' 		=> 'a.attr_id = ga.attr_id'
			),
       		'goods' => array(
       			'type'  => Component_Model_View::TYPE_LEFT_JOIN,
       			'alias' => 'g',
       			'on'   	=> "g.goods_id = ga.goods_id"
       		),
       		'warehouse_attr' => array(
       			'type'  => Component_Model_View::TYPE_LEFT_JOIN,
       			'alias' => 'wap',
       			'on'   	=> "ga.goods_id = wap.goods_id and wap.warehouse_id = '$warehouse_id' and ga.goods_attr_id = wap.goods_attr_id"
       		),
       		'warehouse_area_attr' => array(
       			'type'  => Component_Model_View::TYPE_LEFT_JOIN,
       			'alias' => 'wa',
       			'on'   	=> "ga.goods_id = wa.goods_id and wa.area_id = '$area_id' and ga.goods_attr_id = wa.goods_attr_id"
       		),
		);
        $data = $dbview->field($field)->join(array('attribute', 'goods', 'warehouse_attr', 'warehouse_area_attr'))->in(array('ga.goods_attr_id'=> $arr))->select();

        if(!empty($data)) {
	        foreach ($data as $row) {
	            $attr_price = round(floatval($row['attr_price']), 2);

	            $attr .= sprintf($fmt, $row['attr_name'], $row['attr_value'], $attr_price);
	        }
        }
        $attr = str_replace('[0]', '', $attr);
    }
    return $attr;
    // 	$sql = "SELECT a.attr_name, ga.attr_value, ga.attr_price ".
    // 			"FROM ".$GLOBALS['ecs']->table('goods_attr')." AS ga, ".
    // 			$GLOBALS['ecs']->table('attribute')." AS a ".
    // 			"WHERE " .db_create_in($arr, 'ga.goods_attr_id')." AND a.attr_id = ga.attr_id";
    // 	$res = $GLOBALS['db']->query($sql);
    // 	while ($row = $GLOBALS['db']->fetchRow($res))
}

/**
* 取得收货人信息
* @param   int	 $user_id	用户编号
* @return  array
*/
function get_consignee($user_id) {
	$dbview = RC_Loader::load_app_model('user_address_user_viewmodel','user');

	if (isset($_SESSION['flow_consignee'])) {
		/* 如果存在session，则直接返回session中的收货人信息 */
		return $_SESSION['flow_consignee'];
	} else {
		/* 如果不存在，则取得用户的默认收货人信息 */
		$arr = array();
		if ($user_id > 0) {
			/* 取默认地址 */
			$arr = $dbview->join('users')->find(array('u.user_id' => $user_id));
		}
		return $arr;

		//     $sql = "SELECT ua.*"." FROM " . $GLOBALS['ecs']->table('user_address') . "AS ua, ".$GLOBALS['ecs']->table('users').' AS u '.
		//             " WHERE u.user_id='$user_id' AND ua.address_id = u.address_id";
		//     $arr = $GLOBALS['db']->getRow($sql);

	}
}

/**
* 检查收货人信息是否完整
* @param   array   $consignee  收货人信息
* @param   int	 $flow_type  购物流程类型
* @return  bool	true 完整 false 不完整
*/
function check_consignee_info($consignee, $flow_type) {
	$db = RC_Loader::load_app_model('region_model','shipping');
    if (exist_real_goods(0, $flow_type)) {
        /* 如果存在实体商品 */
        $res = !empty($consignee['consignee']) &&
            !empty($consignee['country']) &&
//             !empty($consignee['email']) &&
            (!empty($consignee['tel']) || !empty($consignee['mobile']));

        if ($res) {
            if (empty($consignee['province'])) {
                /* 没有设置省份，检查当前国家下面有没有设置省份 */
                $pro = $db->get_regions(1, $consignee['country']);
                $res = empty($pro);
            } elseif (empty($consignee['city'])) {
                /* 没有设置城市，检查当前省下面有没有城市 */
                $city = $db->get_regions(2, $consignee['province']);
                $res = empty($city);
            } elseif (empty($consignee['district'])) {
                $dist = $db->get_regions(3, $consignee['city']);
                $res = empty($dist);
            }
        }
        return $res;
    } else {
        /* 如果不存在实体商品 */
        return !empty($consignee['consignee']) &&
//             !empty($consignee['email']) &&
            (!empty($consignee['tel']) || !empty($consignee['mobile']));
    }
}

/**
* 获得上一次用户采用的支付和配送方式
*
* @access  public
* @return  void
*/
function last_shipping_and_payment() {
	$db_order = RC_Loader::load_app_model('order_info_model','orders');
	$row = $db_order->field('shipping_id, pay_id')->order('order_id DESC')->find(array('user_id' => $_SESSION['user_id']));
    if (empty($row)) {
        /* 如果获得是一个空数组，则返回默认值 */
        $row = array('shipping_id' => 0, 'pay_id' => 0);
    }
    return $row;

    //     $sql = "SELECT shipping_id, pay_id " .
    //             " FROM " . $GLOBALS['ecs']->table('order_info') .
    //             " WHERE user_id = '$_SESSION[user_id]' " .
    //             " ORDER BY order_id DESC LIMIT 1";
    //     $row = $GLOBALS['db']->getRow($sql);
}



//
///**
//* 添加礼包到购物车
//* @access  public
//* @param   integer $package_id   礼包编号
//* @param   integer $num		  礼包数量
//* @return  boolean
//*/
//function add_package_to_cart($package_id, $num = 1) {
////	$GLOBALS['err']->clean();
//
//	/* 检查库存 */
////	if ($GLOBALS['_CFG']['use_storage'] == 1 && $num > $package['goods_number']) {
////		   $num = $goods['goods_number'];
////		   $GLOBALS['err']->add(sprintf($GLOBALS['_LANG']['shortage'], $num), ERR_OUT_OF_STOCK);
////		   return false;
////	   }
//
////	$sql = "SELECT goods_number FROM " .$GLOBALS['ecs']->table('cart').
////	" WHERE session_id = '" .SESS_ID. "' AND goods_id = '" . $package_id . "' ".
////	" AND parent_id = 0 AND extension_code = 'package_buy' " ." AND rec_type = '" . CART_GENERAL_GOODS . "'";
////	$row = $GLOBALS['db']->getRow($sql);
//
////	$sql = "UPDATE " . $GLOBALS['ecs']->table('cart') . " SET goods_number = '" . $num . "'" .
////			" WHERE session_id = '" .SESS_ID. "' AND goods_id = '$package_id' ".
////			" AND parent_id = 0 AND extension_code = 'package_buy' " .
////			" AND rec_type = '" . CART_GENERAL_GOODS . "'";
////	$GLOBALS['db']->query($sql);
//
////	$GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('cart'), $parent, 'INSERT');
//
////	$sql = "DELETE FROM " . $GLOBALS['ecs']->table('cart') . " WHERE session_id = '" . SESS_ID . "' AND is_gift <> 0";
////	$GLOBALS['db']->query($sql);
//
//
//	$db = RC_Loader::load_app_model('cart_model','flow');
//	/* 取得礼包信息 */
//	$package = get_package_info($package_id);
//	if (empty($package)) {
//		$GLOBALS['err']->add(RC_Lang::lang('goods_not_exists'), ERR_NOT_EXISTS);
//		return false;
//	}
//
//	/* 是否正在销售 */
//	if ($package['is_on_sale'] == 0) {
//		$GLOBALS['err']->add(RC_Lang::lang('not_on_sale'), ERR_NOT_ON_SALE);
//		return false;
//	}
//
//	/* 现有库存是否还能凑齐一个礼包 */
//	if (ecjia::config('use_storage') == '1' && judge_package_stock($package_id)) {
//		$GLOBALS['err']->add(sprintf(RC_Lang::lang('shortage'), 1), ERR_OUT_OF_STOCK);
//		return false;
//	}
//
//	/* 初始化要插入购物车的基本件数据 */
//	$parent = array(
//		'user_id'			=> $_SESSION['user_id'],
//		'session_id'		=> SESS_ID,
//		'goods_id'			=> $package_id,
//		'goods_sn'			=> '',
//		'goods_name'		=> addslashes($package['package_name']),
//		'market_price'		=> $package['market_package'],
//		'goods_price'		=> $package['package_price'],
//		'goods_number'		=> $num,
//		'goods_attr'		=> '',
//		'goods_attr_id'		=> '',
//		'is_real'			=> $package['is_real'],
//		'extension_code'	=> 'package_buy',
//		'is_gift'			=> 0,
//		'rec_type'			=> CART_GENERAL_GOODS
//	);
//
//	/* 如果数量不为0，作为基本件插入 */
//	if ($num > 0) {
//		/* 检查该商品是否已经存在在购物车中 */
//		$row = $db->field('goods_number')->find(array('session_id' => SESS_ID , 'goods_id' => $package_id , 'parent_id' => 0 , 'extension_code' => "package_buy" , 'rec_type' => CART_GENERAL_GOODS ));
//
//		if($row) {
//			/* 如果购物车已经有此物品，则更新 */
//			$num += $row['goods_number'];
//			if (ecjia::config('use_storage') == 0 || $num > 0) {
//				$data = array(
//					'goods_number' => $num
//					);
//				$db->where(array('session_id' => SESS_ID , 'goods_id' => $package_id , 'parent_id' => 0 , 'extension_code' => "package_buy" , 'rec_type' => CART_GENERAL_GOODS))->update($data);
//			} else {
//				$GLOBALS['err']->add(sprintf(RC_Lang::lang('shortage'), $num), ERR_OUT_OF_STOCK);
//				return false;
//			}
//		} else {
//			/* 购物车没有此物品，则插入 */
//			$db->insert($parent);
//		}
//	}
//
//	/* 把赠品删除 */
//	$db->where(array('session_id' => SESS_ID , 'is_gift' => array('neq' => 0)))->delete();
//	return true;
//}

/**
* 检查礼包内商品的库存
* @return  boolen
*/
function judge_package_stock($package_id, $package_num = 1) {
	$db_package_goods 	= RC_Loader::load_app_model('package_goods_model','goods');
	$db_products_view 	= RC_Loader::load_app_model('products_viewmodel','goods');
	$db_goods_view 		= RC_Loader::load_app_model('goods_auto_viewmodel','goods');

	$row = $db_package_goods->field('goods_id, product_id, goods_number')->where(array('package_id' => $package_id))->select();
    if (empty($row)) {
        return true;
    }

    /* 分离货品与商品 */
    $goods = array('product_ids' => '', 'goods_ids' => '');
    foreach ($row as $value) {
        if ($value['product_id'] > 0) {
            $goods['product_ids'] .= ',' . $value['product_id'];
            continue;
        }
        $goods['goods_ids'] .= ',' . $value['goods_id'];
    }

    /* 检查货品库存 */
    if ($goods['product_ids'] != '') {
    	$row = $db_products_view->join('package_goods')->where(array('pg.package_id' => $package_id , 'pg.goods_number' * $package_num => array('gt' => 'p.product_number')))->in(array('p.product_id' => trim($goods['product_ids'], ',')))->select();
        if (!empty($row)) {
            return true;
        }
    }

    /* 检查商品库存 */
    if ($goods['goods_ids'] != '') {
    	$db_goods_view->view = array(
    		'package_goods' => array(
    			'type' 	=> Component_Model_View::TYPE_LEFT_JOIN,
    			'alias'	=> 'pg',
    			'field' => 'g.goods_id',
    			'on' 	=> 'pg.goods_id = g.goods_id'
    		)
    	);
    	$row = $db_goods_view->where(array('pg.goods_number' * $package_num => array('gt' => 'g.goods_number')  , 'pg.package_id' => $package_id))->in(array('pg.goods_id' => trim($goods['goods_ids'] , ',')))->select();
        if (!empty($row)) {
            return true;
        }
    }
    return false;

    // 	$sql = "SELECT goods_id, product_id, goods_number
    //             FROM " . $GLOBALS['ecs']->table('package_goods') . "
    //             WHERE package_id = '" . $package_id . "'";
    // 	$row = $GLOBALS['db']->getAll($sql);

    // 	$sql = "SELECT p.product_id
    //                 FROM " . $GLOBALS['ecs']->table('products') . " AS p, " . $GLOBALS['ecs']->table('package_goods') . " AS pg
    // 	                WHERE pg.product_id = p.product_id
    // 	                AND pg.package_id = '$package_id'
    // 	                AND pg.goods_number * $package_num > p.product_number
    // 	                AND p.product_id IN (" . trim($goods['product_ids'], ',') . ")";
    // 	$row = $GLOBALS['db']->getAll($sql);

    // 	$sql = "SELECT g.goods_id
    //                 FROM " . $GLOBALS['ecs']->table('goods') . "AS g, " . $GLOBALS['ecs']->table('package_goods') . " AS pg
    // 	                WHERE pg.goods_id = g.goods_id
    // 	                AND pg.goods_number * $package_num > g.goods_number
    // 	                AND pg.package_id = '" . $package_id . "'
    //                 AND pg.goods_id IN (" . trim($goods['goods_ids'], ',') . ")";
    // 	$row = $GLOBALS['db']->getAll($sql);
}

/**
 * 获取指订单的详情
 *
 * @access public
 * @param int $order_id
 *            订单ID
 * @param int $user_id
 *            用户ID
 *
 * @return arr $order 订单所有信息的数组
 */
function get_order_detail ($order_id, $user_id = 0, $type) {
    //$db = RC_Loader::load_app_model('shipping_model', 'shipping');
    //$dbview = RC_Loader::load_app_model('package_goods_viewmodel', 'goods');
    $pay_method = RC_Loader::load_app_class('payment_method', 'payment');

    $order_id = intval($order_id);
    if ($order_id <= 0) {
        return new ecjia_error('error_order_detail', RC_Lang::get('orders::order.invalid_parameter'));
    }
    $order = order_info($order_id, '', $type);

    // 检查订单是否属于该用户
    if ($user_id > 0 && $user_id != $order['user_id']) {
        return new ecjia_error('error_order_detail', RC_Lang::get('orders::order.error_order_detail'));
    }

    /* 入驻商信息*/
    if ($order['store_id'] > 0) {
//     	$seller_info = RC_Model::model('seller/seller_shopinfo_model')->find(array('id' => $order['seller_id']));
    	$merchant_info = RC_DB::table('store_franchisee')->where('store_id', $order['store_id'])->first();
    	$order['seller_name']= $merchant_info['merchants_name'];
    	//$order['merchants_name']= $merchant_info['merchants_name'];后期增加
    	$order['service_phone']	= RC_DB::table('merchants_config')->where(RC_DB::raw('store_id'), $order['store_id'])->where(RC_DB::raw('code'), 'shop_kf_mobile')->pluck('value');
    } else {
    	//$order['merchants_name']= RC_Lang::get('orders::order.self_support');后期增加
    	$order['seller_name']= RC_Lang::get('orders::order.self_support');
    	$order['service_phone']	= ecjia::config('service_phone');
    }

    /* 对发货号处理 */
    if (! empty($order['invoice_no'])) {
//         $shipping_code = $db->field('shipping_code')->find('shipping_id = ' . $order[shipping_id] . '');
//         $shipping_code = $shipping_code['shipping_code'];

        $shipping_code = RC_DB::table('shipping')->where('shipping_id', $order['shipping_id'])->pluck('shipping_code');
    }

    /* 只有未确认才允许用户修改订单地址 */
    if ($order['order_status'] == OS_UNCONFIRMED) {
        $order['allow_update_address'] = 1; // 允许修改收货地址
    } else {
        $order['allow_update_address'] = 0;
    }

    /* 获取订单中实体商品数量 */
    $order['exist_real_goods'] = exist_real_goods($order_id);

    // 获取需要支付的log_id
    $order['log_id'] = intval($pay_method->get_paylog_id($order['order_id'], $pay_type = PAY_ORDER));

    $order['user_name'] = $_SESSION['user_name'];

    /* 无配送时的处理 */
    $order['shipping_id'] == - 1 and $order['shipping_name'] = RC_Lang::lang('shipping_not_need');

    /* 其他信息初始化 */
    $order['how_oos_name'] = $order['how_oos'];
    $order['how_surplus_name'] = $order['how_surplus'];

    /* 虚拟商品付款后处理 */
    if ($order['pay_status'] != PS_UNPAYED) {
        /* 取得已发货的虚拟商品信息 */
    	//TODO
        RC_Loader::load_app_func('common', 'goods');
        $virtual_goods = get_virtual_goods($order_id, true);
        $virtual_card = array();
        foreach ($virtual_goods as $code => $goods_list) {
            /* 只处理虚拟卡 */
            if ($code == 'virtual_card') {
                foreach ($goods_list as $goods) {
                    $info = virtual_card_result($order['order_sn'], $goods);
                    if ($info) {
                        $virtual_card[] = array(
                            'goods_id' => $goods['goods_id'],
                            'goods_name' => $goods['goods_name'],
                            'info' => $info
                        );
                    }
                }
            }
            /* 处理超值礼包里面的虚拟卡 */
            if ($code == 'package_buy') {
            	$db_package_goods = RC_DB::table('package_goods as pg')->leftJoin('goods as g', RC_DB::table('pg.goods_id'), '=', RC_DB::table('g.goods_id'));
                foreach ($goods_list as $goods) {
//                     $dbview->view = array(
//                         'goods' => array(
//                             'type' 	=> Component_Model_View::TYPE_LEFT_JOIN,
//                             'alias' => 'g',
//                             'field' => 'g.goods_id',
//                             'on' 	=> 'pg.goods_id = g.goods_id'
//                         )
//                     );
//                     $vcard_arr = $dbview->where('pg.package_id = ' . $goods['goods_id'] . ' AND extension_code = "virtual_card" ')->select();

                   $vcard_arr = $db_package_goods->selectRaw('g.goods_id')->whereRaw('pg.package_id = '.$goods['goods_id'].' AND extension_code = "virtual_card"')->get();

                    if (! empty($vcard_arr)) {
                        foreach ($vcard_arr as $val) {
                            $info = virtual_card_result($order['order_sn'], $val);
                            if ($info) {
                                $virtual_card[] = array(
                                    'goods_id' => $goods['goods_id'],
                                    'goods_name' => $goods['goods_name'],
                                    'info' => $info
                                );
                            }
                        }
                    }
                }
            }
        }
        $var_card = deleteRepeat($virtual_card);
        ecjia_admin::$controller->assign('virtual_card', $var_card);
    }

    /* 确认时间 支付时间 发货时间 */
    if ($order['confirm_time'] > 0 && ($order['order_status'] == OS_CONFIRMED || $order['order_status'] == OS_SPLITED || $order['order_status'] == OS_SPLITING_PART)) {
//         $order['confirm_time'] = sprintf(RC_Lang::lang('confirm_time'), RC_Time::local_date(ecjia::config('time_format'), $order['confirm_time']));
    	$order['confirm_time'] = RC_Time::local_date(ecjia::config('time_format'), $order['confirm_time']);
    } else {
        $order['confirm_time'] = '';
    }
    if ($order['pay_time'] > 0 && $order['pay_status'] != PS_UNPAYED) {
//         $order['pay_time'] = sprintf(RC_Lang::lang('pay_time'), RC_Time::local_date(ecjia::config('time_format'), $order['pay_time']));
        $order['pay_time'] = RC_Time::local_date(ecjia::config('time_format'), $order['pay_time']);
    } else {
        $order['pay_time'] = '';
    }
    if ($order['shipping_time'] > 0 && in_array($order['shipping_status'], array(
        SS_SHIPPED,
        SS_RECEIVED
    ))) {
//         $order['shipping_time'] = sprintf(RC_Lang::lang('shipping_time'), RC_Time::local_date(ecjia::config('time_format'), $order['shipping_time']));
        $order['shipping_time'] = RC_Time::local_date(ecjia::config('time_format'), $order['shipping_time']);
    } else {
        $order['shipping_time'] = '';
    }

    return $order;
}


/**
 * 返回虚拟卡信息
 *
 * @access public
 * @param
 *
 * @return void
 */
function virtual_card_result($order_sn, $goods) {
// 	$db = RC_Loader::load_app_model ( 'virtual_card_model', 'goods' );

// 	$res = $db->field ('card_sn, card_password, end_date, crc32')->where(array('goods_id' => $goods [goods_id], 'order_sn' => $order_sn))->select ();
	$res = RC_DB::table('virtual_card')->selectRaw('card_sn, card_password, end_date, crc32')->where('goods_id', $goods['goods_id'])->where('order_sn', $order_sn)->get();

	$cards = array ();
	if (! empty ( $res )) {
		$auth_key = ecjia_config::instance()->read_config('auth_key');
		foreach ( $res as $row ) {
			/* 卡号和密码解密 */
			if ($row ['crc32'] == 0 || $row ['crc32'] == crc32 ( $auth_key )) {
				$row ['card_sn'] = RC_Crypt::decrypt ( $row ['card_sn'] );
				$row ['card_password'] = RC_Crypt::decrypt ( $row ['card_password'] );
			}  else {
				$row ['card_sn'] = '***';
				$row ['card_password'] = '***';
			}

			$cards [] = array (
				'card_sn' 		=> $row ['card_sn'],
				'card_password' => $row ['card_password'],
				'end_date' 		=> date ( ecjia::config('date_format'), $row ['end_date'] )
			);
		}
	}
	return $cards;

	/* 包含加密解密函数所在文件 */
	// include_once(ROOT_PATH . 'includes/lib_code.php');

	// 	RC_Loader::load_sys_func ( 'code' );

	/* 获取已经发送的卡片数据 */
	// $sql = "SELECT card_sn, card_password, end_date, crc32 FROM ".$GLOBALS['ecs']->table('virtual_card')." WHERE goods_id= '$goods[goods_id]' AND order_sn = '$order_sn' ";
	// $res= $GLOBALS['db']->query($sql);

	// 	$res = $db->field ( 'card_sn, card_password, end_date, crc32' )->where ( 'goods_id= ' . $goods [goods_id] . ' AND order_sn = "' . $order_sn . '"' )->select ();
	// while ($row = $GLOBALS['db']->FetchRow($res))

		// 	elseif ($row ['crc32'] == crc32 ( OLD_AUTH_KEY )) {
		// 		$row ['card_sn'] = RC_Crypt::decrypt ( $row ['card_sn'], OLD_AUTH_KEY );
		// 		$row ['card_password'] = RC_Crypt::decrypt ( $row ['card_password'], OLD_AUTH_KEY );
		// 	}
}

/**
 * 去除虚拟卡中重复数据
 */
function deleteRepeat ($array)
{
	$_card_sn_record = array();
	foreach ($array as $_k => $_v) {
		foreach ($_v['info'] as $__k => $__v) {
			if (in_array($__v['card_sn'], $_card_sn_record)) {
				unset($array[$_k]['info'][$__k]);
			} else {
				array_push($_card_sn_record, $__v['card_sn']);
			}
		}
	}
	return $array;
}

//TODO:从api中移入的func
/**
 * 取得订单商品
 * @param   int     $order_id   订单id
 * @return  array   订单商品数组
 */
function EM_order_goods($order_id , $page=1 , $pagesize = 10)
{
	//$dbview = RC_Loader::load_app_model('order_goods_comment_viewmodel', 'orders');
	$dbview = RC_Model::model('orders/order_goods_comment_viewmodel');
	$dbview->view = array(
		'goods' => array(
			'type' 		=> Component_Model_View::TYPE_LEFT_JOIN,
			'alias' 	=> 'g',
			'on' 		=> 'og.goods_id = g.goods_id'
		),
		'term_relationship' => array(
			'type' 		=> Component_Model_View::TYPE_LEFT_JOIN,
			'alias' 	=> 'tr',
			'on' 		=> 'tr.object_id = og.rec_id and object_type = "ecjia.comment"'
		),
	);
	$field = 'og.*, og.goods_price * og.goods_number AS subtotal, g.goods_thumb, g.original_img, g.goods_img, g.store_id, tr.relation_id';

	$res = $dbview->field($field)
				  ->where(array('og.order_id' => $order_id))
// 				  ->group(array('og.goods_id'))
				  ->limit(($page-1)*$pagesize, $pagesize)
				  ->select();

	if (!empty($res)) {
		RC_Loader::load_app_func('common', 'goods');
		foreach ($res as $row) {
			if ($row['extension_code'] == 'package_buy') {
				$row['package_goods_list'] = get_package_goods($row['goods_id']);
			}
			$row['is_commented'] = empty($row['relation_id']) ? 0 : 1 ;
			$goods_list[] = $row;
		}
	}
	return $goods_list;
}


/**
 * 生成查询订单的sql
 * @param   string  $type   类型
 * @param   string  $alias  order表的别名（包括.例如 o.）
 * @return  string
 */
function EM_order_query_sql($type = 'finished', $alias = '') {
	RC_Loader::load_app_func('common', 'goods');
	$payment_method = RC_Loader::load_app_class('payment_method', 'payment');
	/* 已完成订单 */
	if ($type == 'finished') {
		return "{$alias}order_status " . db_create_in(array(OS_CONFIRMED, OS_SPLITED)) .
		" AND {$alias}shipping_status " . db_create_in(array(SS_RECEIVED)) .
		" AND {$alias}pay_status " . db_create_in(array(PS_PAYED, PS_PAYING)) . " ";
	} elseif ($type == 'await_ship') {
		/* 待发货订单 */
		return "{$alias}order_status " .
		db_create_in(array(OS_UNCONFIRMED, OS_CONFIRMED, OS_SPLITED, OS_SPLITING_PART)) .
		" AND   {$alias}shipping_status " .
		db_create_in(array(SS_UNSHIPPED, SS_PREPARING, SS_SHIPPED_ING)) .
		" AND ( {$alias}pay_status " . db_create_in(array(PS_PAYED, PS_PAYING)) . " OR {$alias}pay_id " . db_create_in($payment_method->payment_id_list(true)) . ") ";
		} elseif ($type == 'await_pay') {
		/* 待付款订单 */
		return "{$alias}order_status " . db_create_in(array(OS_CONFIRMED, OS_SPLITED, OS_UNCONFIRMED)) .
			" AND   {$alias}pay_status = '" . PS_UNPAYED . "'" .
			" AND ( {$alias}shipping_status " . db_create_in(array(SS_SHIPPED, SS_RECEIVED)) . " OR {$alias}pay_id " . db_create_in($payment_method->payment_id_list(false)) . ") ";
		} elseif ($type == 'unconfirmed') {
			/* 未确认订单 */
			return "{$alias}order_status = '" . OS_UNCONFIRMED . "' ";
		} elseif ($type == 'unprocessed') {
				/* 未处理订单：用户可操作 */
			return "{$alias}order_status " . db_create_in(array(OS_UNCONFIRMED, OS_CONFIRMED)) .
			" AND {$alias}shipping_status = '" . SS_UNSHIPPED . "'" .
			" AND {$alias}pay_status = '" . PS_UNPAYED . "' ";
		} elseif ($type == 'unpay_unship') {
		/* 未付款未发货订单：管理员可操作 */
		return "{$alias}order_status " . db_create_in(array(OS_UNCONFIRMED, OS_CONFIRMED)) .
			" AND {$alias}shipping_status " . db_create_in(array(SS_UNSHIPPED, SS_PREPARING)) .
			" AND {$alias}pay_status = '" . PS_UNPAYED . "' ";
		} elseif ($type == 'shipped') {
		/* 已发货订单：不论是否付款 */
			return "{$alias}shipping_status " . db_create_in(array(SS_SHIPPED)) . " ";
		} else {
			die('函数 order_query_sql 参数错误');
		}
}


//TODO 多商铺版新增fun
//计算会员下订单的商品总运费
function get_goods_order_shipping_fee($goods = array(), $region = '', $shipping_code = ''){

	$arr = array();
	$arr['shipping_fee'] = 0;

	//订单总运费计算
	$cart_goods = get_warehouse_cart_goods_info($goods, 1, $region, $shipping_code);
	$arr['shipping_fee'] = $cart_goods['shipping']['shipping_fee'];
	$arr['ru_list'] = $cart_goods['ru_list'];

	return $arr;
}


function get_warehouse_cart_goods_info($goods, $type, $region, $shipping_code){

	if($type == 1){
		$goods = get_cart_goods_ru_list($goods, $region);
	}else{
		$goods = get_cart_goods_warehouse_list($goods);
	}

	//总运费
	$shipping_fee = get_cart_goods_combined_freight($goods, $type, $region, $shipping_code);

	return $shipping_fee;
}

//划分商家或平台运费 start
function get_cart_goods_ru_list($goods, $region = array()) { //商家划分

	$ru_id_list = get_cart_goods_ru_id($goods);
	$ru_id_list = array_values(array_unique($ru_id_list));

	if (!empty($region)) {
		$area_id = $region['province'];
		//多店铺开启库存管理以及地区后才会去判断
		if ( $area_id > 0 ) {

			$warehouse_db = RC_Loader::load_app_model('warehouse_model', 'warehouse');
			$warehouse = $warehouse_db->where(array('regionId' => $area_id))->find();
			$warehouse_id = $warehouse['parent_id'];
// 			$goods[$key]['warehouse_id'] = $warehouse_id;
// 			$goods[$key]['area_id'] = $area_id;
			//         	}
		}
	}

	$arr = array();
	foreach($ru_id_list as $wkey=>$ru){
		foreach($goods as $gkey=>$row){

			if($ru == $row['ru_id']){
				if (!empty($region)) {
					$row['warehouse_id'] = $warehouse_id;
					$row['area_id'] = $area_id;
				}
				$arr[$ru][$gkey] = $row;
			}
		}
	}

	$new_arr = array();
	foreach($arr as $key=>$row){
		$new_arr[$key] = get_cart_goods_warehouse_list($row);
	}

	//get_print_r($new_arr);
	return $new_arr;
}

function get_cart_goods_ru_id($goods){

	$arr = array();
	foreach($goods as $key=>$row){
		$arr[$key] = $row['ru_id'];
	}

	return $arr;
}

function get_cart_goods_warehouse_list($goods){ //仓库划分

	$warehouse_id_list = get_cart_goods_warehouse_id($goods);
	$warehouse_id_list = array_values(array_unique($warehouse_id_list));

	$arr = array();
	foreach ($warehouse_id_list as $wkey=>$warehouse) {
		foreach ($goods as $gkey=>$row) {
			if ($warehouse == $row['warehouse_id']){
				$arr[$warehouse][$gkey] = $row;
			}
		}
	}

	return $arr;
}

function get_cart_goods_warehouse_id($goods){

	$arr = array();
	foreach ($goods as $key=>$row) {
		$arr[$key] = $row['warehouse_id'];
	}

	return $arr;
}

/*
 *合计运费
*购物车显示
*订单分单
*$type
*/
function get_cart_goods_combined_freight($goods, $type=0, $region = '', $shipping_code = ''){

	$arr = array();
	$new_arr = array();

	if($type == 1){ //购物车显示
		foreach($goods as $key=>$row){
			foreach($row as $warehouse => $rows){
				foreach($rows as $gkey=>$grow){
					$arr[$key][$warehouse]['weight'] 	+= $grow['goodsWeight'] * $grow['goods_number']; //商品总重量
					$arr[$key][$warehouse]['goods_price'] 	+= $grow['goods_price'] * $grow['goods_number']; //商品总金额
					$arr[$key][$warehouse]['number'] 	+= $grow['goods_number']; //商品总数量
					$arr[$key][$warehouse]['ru_id']	= $key; //商家ID
					$arr[$key][$warehouse]['warehouse_id']	 = $warehouse; //仓库ID
					$arr[$key][$warehouse]['is_shipping'] = $grow['is_shipping'];
// 					@$arr[$key][$warehouse]['warehouse_name'] = $GLOBALS['db']->getOne("SELECT region_name FROM " .$GLOBALS['ecs']->table("region_warehouse"). " WHERE region_id = '$warehouse'"); //仓库名称
				}
			}
		}

		foreach($arr as $key=>$row){
			foreach($row as $warehouse => $rows){
				$arr[$key][$warehouse]['shipping'] = get_goods_freight($rows, $rows['warehouse_id'], $region, $rows['goods_number'], $shipping_code);

			}
		}

		$new_arr['shipping_fee'] = 0;
		foreach($arr as $key=>$row){
			foreach($row as $warehouse => $rows){
				$new_arr['shipping_fee'] += $rows['is_shipping'] == 1 ? 0 : $rows['shipping']['shipping_fee'];
			}
		}

		$arr = array('ru_list' => $arr, 'shipping' => $new_arr);
		return $arr;
	} elseif($type == 2) { //订单分单

		$arr = get_cart_goods_warehouse_list($goods);
// 		$new_arr['shipping_fee'] = 0;
		$shipping_fee = 0;
		$db = RC_Loader::load_app_model('goods_model', 'goods');
		foreach($arr as $warehouse=>$row){
			foreach($row as $gw=>$grow){
				$new_arr[$warehouse]['weight'] 			+= $grow['goodsWeight'] * $grow['goods_number']; //商品总重量
				$new_arr[$warehouse]['goods_price'] 	+= $grow['goods_price'] * $grow['goods_number']; //商品总金额
				$new_arr[$warehouse]['number'] 			+= $grow['goods_number']; //商品总数量
				$new_arr[$warehouse]['ru_id']			 = $grow['ru_id']; //商家ID
				$new_arr[$warehouse]['warehouse_id']	 = $warehouse; //仓库ID
				$new_arr[$warehouse]['order_id']		 = $grow['order_id']; //订单ID

				$new_arr[$warehouse]['is_shipping']		 = $db->where(array('goods_id' => $grow['goods_id']))->get_field('is_shipping');
// 				$new_arr[$warehouse]['is_shipping'] 	 = $grow['is_shipping'];
// 				@$new_arr[$warehouse]['warehouse_name'] 	 = $GLOBALS['db']->getOne("SELECT region_name FROM " .$GLOBALS['ecs']->table("region_warehouse"). " WHERE region_id = '$warehouse'"); //仓库名称
			}
		}

		foreach($new_arr as $key=>$row){
			$db_order_info = RC_Loader::load_app_model('order_info_model', 'orders');
			$order = $db_order_info->field('country, province, city, district, shipping_id')->find(array('order_id' => $row['order_id']));
// 			$sql = "SELECT country, province, city, district, shipping_id FROM " .$GLOBALS['ecs']->table('order_info'). " WHERE order_id = '" .$row['order_id']. "'";
// 			$order = $GLOBALS['db']->getRow($sql);

			$db_shipping  = RC_Loader::load_app_model('shipping_model', 'shipping');
			$shipping_code = $db_shipping->where(array('shipping_id' => $order['shipping_id']))->get_field('shipping_code');
// 			$shipping_code	 	= $GLOBALS['db']->getOne("SELECT shipping_code FROM " .$GLOBALS['ecs']->table("shipping"). " WHERE shipping_id = '" .$order['shipping_id']. "'"); //配送代码
			$new_arr[$key]['shipping'] = get_goods_freight($row, $row['warehouse_id'], $order, $row['number'], $shipping_code);

// 			$new_arr['shipping_fee'] += $new_arr[$key]['shipping']['shipping_fee'];
			$shipping_fee += $new_arr[$warehouse]['is_shipping'] == 1 ? 0 : $new_arr[$key]['shipping']['shipping_fee'];
		}
		$new_arr['shipping_fee'] = $shipping_fee;
		$arr = $new_arr;
	}

	return $arr;
}

//查询商品的默认配送方式运费金额
function get_goods_freight($goods, $warehouse_id = 0, $goods_region = array(), $buy_number = 1, $shipping_code){

	$shipping_method = RC_Loader::load_app_class('shipping_method', 'shipping');
// 	$sql = "select shipping_code, shipping_name from " .$GLOBALS['ecs']->table('shipping'). " where shipping_code = '$shipping_code'";
// 	$shipping = $GLOBALS['db']->getRow($sql);
	if ($goods['ru_id'] > 0 ) {
		$city_configure = get_goods_freight_configure($goods, $warehouse_id, $goods_region['city'], $shipping_code);
		$province_configure = get_goods_freight_configure($goods, $warehouse_id, $goods_region['province'], $shipping_code);

		if(!empty($city_configure)){
			$configure = $city_configure;
		}else{
			$configure = $province_configure;
		}
	} else {
		$db = RC_Loader::load_app_model('shipping_model', 'shipping');
		$shipping_id = $db->where(array('shipping_code' => $shipping_code))->get_field('shipping_id');

		$result = $shipping_method->shipping_area_info($shipping_id, $goods_region);
		$configure = $result['configure'];

	}

// 	$shipping_cfg = sc_unserialize_config($configure);
	$shipping_cfg = $shipping_method->unserialize_config($configure);
// 	$configure_price = goods_shipping_fee($shipping_code, $configure, $goods['weight'], $goods['goods_price'], $goods['number']);
	$configure_price = $shipping_method->shipping_fee($shipping_code, $configure, $goods['weight'], $goods['goods_price'], $goods['number']);
	$arr['shipping_fee'] = $configure_price;
	$arr['configure_price'] = price_format($configure_price, false);
// 	$arr['shipping_name'] = $shipping['shipping_name'];

	$arr['item_fee'] = price_format($shipping_cfg['item_fee'], false); /* 单件商品的配送价格（默认） */
	$arr['base_fee'] = price_format($shipping_cfg['base_fee'], false); /* N(500或1000克)克以内的价格 */
	$arr['step_fee'] = price_format($shipping_cfg['step_fee'], false); /* 续重每N(500或1000克)克增加的价格 */
	$arr['free_money'] = price_format($shipping_cfg['free_money'], false); //免费额度
	$arr['fee_compute_mode'] = $shipping_cfg['fee_compute_mode']; //费用计算方式
	@$arr['pay_fee'] = price_format($shipping_cfg['pay_fee'], false); //货到付款支付费用

	$arr['warehouse_id'] = $warehouse_id;
	return $arr;
}

//查询商品设置配送地区运费数据
function get_goods_freight_configure($goods, $warehouse_id, $region_id, $shipping_code){

	$user_id = $goods['ru_id'];

// 	$date = array('shipping_id');
// 	$where = "shipping_code = '$shipping_code'";
// 	$shipping_id = get_table_date('shipping', $where, $date, 2);
	$shiping_db = RC_Loader::load_app_model('shipping_model', 'shipping');
	$shipping_id = $shiping_db->where(array('shipping_code' => $shipping_code))->get_field('shipping_id');

	$db_warehouse_freight = RC_Loader::load_app_model('warehouse_freight_model', 'warehouse');
	$config = $db_warehouse_freight->where(array('user_id' => $user_id, 'warehouse_id' => $warehouse_id, 'shipping_id' => $shipping_id, 'region_id' => $region_id))->get_field('configure');
	return $config;


// 	$sql = "select configure from " .$GLOBALS['ecs']->table('warehouse_freight'). " where user_id = '$user_id' and warehouse_id = '$warehouse_id' and shipping_id = '$shipping_id' and region_id = '$region_id'";
// 	return $GLOBALS['db']->getOne($sql);
}

/**
 * 处理序列化的支付、配送的配置参数
 * 返回一个以name为索引的数组
 *
 * @access  public
 * @param   string       $cfg
 * @return  void
 */
function sc_unserialize_config($cfg)
{
	if (is_string($cfg) && ($arr = unserialize($cfg)) !== false)
	{
		$config = array();

		foreach ($arr AS $key => $val)
		{
			$config[$val['name']] = $val['value'];
		}

		return $config;
	}
	else
	{
		return false;
	}
}


/**
 * 添加管理员记录日志操作对象
 *
 */
function assign_adminlog_content() {
    ecjia_admin_log::instance()->add_action('produce', RC_Lang::get('orders::order.produce'));

    ecjia_admin_log::instance()->add_object('delivery_order', RC_Lang::get('orders::order.delivery_sn'));
    ecjia_admin_log::instance()->add_object('back_order', RC_Lang::get('orders::order.back_sn'));
    ecjia_admin_log::instance()->add_object('order_payment', RC_Lang::get('orders::order.order_payment'));
    ecjia_admin_log::instance()->add_object('order_status', RC_Lang::get('orders::order.order_status'));
    ecjia_admin_log::instance()->add_object('order_consignee', RC_Lang::get('orders::order.order_consignee'));
}

//	TODO:下列方法已移到了相关class中

// /**
// * 生成查询订单的sql
// * @param   string  $type   类型
// * @param   string  $alias  order表的别名（包括.例如 o.）
// * @return  string
// */
// function order_query_sql($type = 'finished', $alias = '') {
// 	RC_Loader::load_app_func('common','goods');
// 	if ($type == 'finished') {
// 		/* 已完成订单 */
// 		return " AND {$alias}order_status " . db_create_in(array(OS_CONFIRMED, OS_SPLITED)) .
// 		" AND {$alias}shipping_status " . db_create_in(array(SS_SHIPPED, SS_RECEIVED)) .
// 		" AND {$alias}pay_status " . db_create_in(array(PS_PAYED, PS_PAYING)) . " ";
// 	} elseif ($type == 'await_ship') {
// 		$payment_method = RC_Loader::load_app_class('payment_method','payment');
// 		/* 待发货订单 */
// 		return " AND   {$alias}order_status " .
// 		db_create_in(array(OS_CONFIRMED, OS_SPLITED, OS_SPLITING_PART)) .
// 		" AND   {$alias}shipping_status " .
// 		db_create_in(array(SS_UNSHIPPED, SS_PREPARING, SS_SHIPPED_ING)) .
// 		" AND ( {$alias}pay_status " . db_create_in(array(PS_PAYED, PS_PAYING)) . " OR {$alias}pay_id " . db_create_in($payment_method->payment_id_list(true)) . ") ";
// 	} elseif ($type == 'await_pay') {
// 		$payment_method = RC_Loader::load_app_class('payment_method','payment');
// 		/* 待付款订单 */
// 		return " AND   {$alias}order_status " . db_create_in(array(OS_CONFIRMED, OS_SPLITED)) .
// 		" AND   {$alias}pay_status = '" . PS_UNPAYED . "'" .
// 		" AND ( {$alias}shipping_status " . db_create_in(array(SS_SHIPPED, SS_RECEIVED)) . " OR {$alias}pay_id " . db_create_in($payment_method->payment_id_list(false)) . ") ";
// 	} elseif ($type == 'unconfirmed') {
// 		/* 未确认订单 */
// 		return " AND {$alias}order_status = '" . OS_UNCONFIRMED . "' ";
// 	} elseif ($type == 'unprocessed') {
// 		/* 未处理订单：用户可操作 */
// 		return " AND {$alias}order_status " . db_create_in(array(OS_UNCONFIRMED, OS_CONFIRMED)) .
// 		" AND {$alias}shipping_status = '" . SS_UNSHIPPED . "'" .
// 		" AND {$alias}pay_status = '" . PS_UNPAYED . "' ";
// 	} elseif ($type == 'unpay_unship') {
// 		/* 未付款未发货订单：管理员可操作 */
// 		return " AND {$alias}order_status " . db_create_in(array(OS_UNCONFIRMED, OS_CONFIRMED)) .
// 		" AND {$alias}shipping_status " . db_create_in(array(SS_UNSHIPPED, SS_PREPARING)) .
// 		" AND {$alias}pay_status = '" . PS_UNPAYED . "' ";
// 	} elseif ($type == 'shipped') {
// 		/* 已发货订单：不论是否付款 */
// 		return " AND {$alias}order_status = '" . OS_CONFIRMED . "'" .
// 		" AND {$alias}shipping_status " . db_create_in(array(SS_SHIPPED, SS_RECEIVED)) . " ";
// 	} else {
// 		ecjia_front::$controller->showmessage(__('操作有误！请重新操作！') , ecjia_admin::MSGTYPE_HTML | ecjia_admin::MSGSTAT_ERROR);
// 	}
// }

///**
//* 取得已安装的配送方式
//* @return  array   已安装的配送方式
//*/
//function shipping_list() {
////	$sql = 'SELECT shipping_id, shipping_name ' .'FROM ' . $GLOBALS['ecs']->table('shipping') .' WHERE enabled = 1';
////	return $GLOBALS['db']->getAll($sql);
//
//	$db = RC_Loader::load_app_model('shipping_model','shipping');
//	return $db->field('shipping_id, shipping_name')->where(array('enabled' => 1))->select();
//}
//
///**
//* 取得配送方式信息
//* @param   int	 $shipping_id	配送方式id
//* @return  array   配送方式信息
//*/
//function shipping_info($shipping_id) {
////	 $sql = 'SELECT * FROM ' . $GLOBALS['ecs']->table('shipping') ." WHERE shipping_id = '$shipping_id' " .'AND enabled = 1';
////	 return $GLOBALS['db']->getRow($sql);
//
//	$db = RC_Loader::load_app_model('shipping_model','shipping');
//	return $db->find(array('shipping_id' => $shipping_id , 'enabled' => 1));
//}
//
///**
//* 取得可用的配送方式列表
//* @param   array   $region_id_list	 收货人地区id数组（包括国家、省、市、区）
//* @return  array   配送方式数组
//*/
//function available_shipping_list($region_id_list) {
////	 $sql = 'SELECT s.shipping_id, s.shipping_code, s.shipping_name, ' .
////				 's.shipping_desc, s.insure, s.support_cod, a.configure ' .
////			 'FROM ' . $GLOBALS['ecs']->table('shipping') . ' AS s, ' .
////				 $GLOBALS['ecs']->table('shipping_area') . ' AS a, ' .
////				 $GLOBALS['ecs']->table('area_region') . ' AS r ' .
////			 'WHERE r.region_id ' . db_create_in($region_id_list) .
////			 ' AND r.shipping_area_id = a.shipping_area_id AND a.shipping_id = s.shipping_id AND s.enabled = 1 ORDER BY s.shipping_order';
////	 return $GLOBALS['db']->getAll($sql);
//
//
//	$dbview = RC_Loader::load_app_model('shipping_area_viewmodel','shipping');
//	$dbview->view = array(
//		'shipping_area' => array(
//			'type'	=> Component_Model_View::TYPE_LEFT_JOIN,
//			'alias'	=> 'a',
//			'field'	=> 's.shipping_id, s.shipping_code, s.shipping_name,s.shipping_desc, s.insure, s.support_cod, a.configure',
//			'on'	=> 'a.shipping_id = s.shipping_id',
//			),
//		'area_region' => array(
//			'type'	=> Component_Model_View::TYPE_LEFT_JOIN,
//			'alias'	=> 'r',
//			'on'	=> 'r.shipping_area_id = a.shipping_area_id ',
//			)
//		);
//
//	return $dbview->where(array('s.enabled' => 1))->in(array('r.region_id' => $region_id_list))->order(array('s.shipping_order'=>'asc'))->select();
//}
//
///**
//* 取得某配送方式对应于某收货地址的区域信息
//* @param   int	 $shipping_id		配送方式id
//* @param   array   $region_id_list	 收货人地区id数组
//* @return  array   配送区域信息（config 对应着反序列化的 configure）
//*/
//function shipping_area_info($shipping_id, $region_id_list) {
////	 $sql = 'SELECT s.shipping_code, s.shipping_name, ' .
////				 's.shipping_desc, s.insure, s.support_cod, a.configure ' .
////			 'FROM ' . $GLOBALS['ecs']->table('shipping') . ' AS s, ' .
////				 $GLOBALS['ecs']->table('shipping_area') . ' AS a, ' .
////				 $GLOBALS['ecs']->table('area_region') . ' AS r ' .
////			 "WHERE s.shipping_id = '$shipping_id' " .
////			 'AND r.region_id ' . db_create_in($region_id_list) .
////			 ' AND r.shipping_area_id = a.shipping_area_id AND a.shipping_id = s.shipping_id AND s.enabled = 1';
////	 $row = $GLOBALS['db']->getRow($sql);
//
//	$dbview = RC_Loader::load_app_model('shipping_area_viewmodel','shipping');
//	$dbview->view = array(
//		'shipping_area' => array(
//			'type'	=> Component_Model_View::TYPE_LEFT_JOIN,
//			'alias'	=> 'a',
//			'field'	=> 's.shipping_code, s.shipping_name,s.shipping_desc, s.insure, s.support_cod, a.configure',
//			'on'	=> 'a.shipping_id = s.shipping_id',
//			),
//		'area_region' => array(
//			'type'	=> Component_Model_View::TYPE_LEFT_JOIN,
//			'alias'	=> 'r',
//			'on'	=> 'r.shipping_area_id = a.shipping_area_id ',
//			)
//		);
//
//	$row = $dbview->in(array('r.region_id' => $region_id_list))->find(array('s.shipping_id' => $shipping_id, 's.enabled' => 1));
//	if (!empty($row)) {
//		$shipping_config = unserialize_config($row['configure']);
//		if (isset($shipping_config['pay_fee'])) {
//			if (strpos($shipping_config['pay_fee'], '%') !== false) {
//				$row['pay_fee']	= floatval($shipping_config['pay_fee']) . '%';
//			} else {
//				$row['pay_fee']	= floatval($shipping_config['pay_fee']);
//			}
//		} else {
//			$row['pay_fee']	= 0.00;
//		}
//	}
//	return $row;
//}
//
///**
//* 取得已安装的支付方式列表
//* @return  array   已安装的配送方式列表
//*/
//function payment_list() {
////	 $sql = 'SELECT pay_id, pay_name ' . 'FROM ' . $GLOBALS['ecs']->table('payment') .' WHERE enabled = 1';
////	 return $GLOBALS['db']->getAll($sql);
//
//	$db = RC_Loader::load_app_model('payment_model','payment');
//	return $db->field('pay_id, pay_name')->where(array('enabled' => 1))->select();
//}

///**
//* 取得支付方式信息
//* @param   int	 $pay_id	 支付方式id
//* @return  array   支付方式信息
//*/
//function payment_info($pay_id) {
////	$sql = 'SELECT * FROM ' . $GLOBALS['ecs']->table('payment') ." WHERE pay_id = '$pay_id' AND enabled = 1";
////	return $GLOBALS['db']->getRow($sql);
//
//	$db = RC_Loader::load_app_model('payment_model','payment');
//	return $db->find(array('pay_id' => $pay_id , 'enabled' => 1 ));
//}
//
///**
//* 取得可用的支付方式列表
//* @param   bool	$support_cod		配送方式是否支持货到付款
//* @param   int	 $cod_fee			货到付款手续费（当配送方式支持货到付款时才传此参数）
//* @param   int	 $is_online		  是否支持在线支付
//* @return  array   配送方式数组
//*/
//function available_payment_list($support_cod, $cod_fee = 0, $is_online = false) {
////	$sql = 'SELECT pay_id, pay_code, pay_name, pay_fee, pay_desc, pay_config, is_cod' .' FROM ' . $GLOBALS['ecs']->table('payment') .' WHERE enabled = 1 ';
////	$sql .= "AND is_online = '1' ";
////	$sql .= 'AND is_cod = 0 '; // 如果不支持货到付款
////	$sql .= 'ORDER BY pay_order'; // 排序
////	$res = $GLOBALS['db']->query($sql);
////	while ($row = $GLOBALS['db']->fetchRow($res))
////	include_once(ROOT_PATH.'includes/lib_compositor.php');
////	RC_Loader::load_sys_func('compositor');
//
//
//	RC_Loader::load_app_func('common','goods');
//	$db = RC_Loader::load_app_model('payment_model','payment');
//
//	$where = '';
//	if (!$support_cod) {
//		$where.= ' AND is_cod = 0 ';
//	}
//	if ($is_online) {
//		$where.= ' AND is_online = 1 ';
//	}
//
//	$data = $db->field('pay_id, pay_code, pay_name, pay_fee, pay_desc, pay_config, is_cod')->where('enabled = 1 '.$where)->order(array('pay_order' => 'asc'))->select();
//	$pay_list = array();
//
//	if (!empty($data)) {
//		foreach ($data as $row) {
//			if ($row['is_cod'] == '1') {
//				$row['pay_fee'] = $cod_fee;
//			}
//
//			$row['format_pay_fee'] = strpos($row['pay_fee'], '%') !== false ? $row['pay_fee'] :
//			price_format($row['pay_fee'], false);
//			$modules[] = $row;
//		}
//	}
//
//	RC_Loader::load_app_func('global','payment');
//	$modules = payment_compositor($modules);
//
//	if(isset($modules)) {
//		return $modules;
//	}
//}

///**
//* 取得支付方式id列表
//* @param   bool	$is_cod 是否货到付款
//* @return  array
//*/
//function payment_id_list($is_cod) {
//
//	$db = RC_Loader::load_app_model('payment_model','payment');
//	if ($is_cod) {
//		$where = "is_cod = 1" ;
//	} else {
//		$where = "is_cod = 0" ;
//	}
//	$row = $db->field('pay_id')->where($where)->select();
//	$arr = array();
//	foreach ($row as $val) {
//		$arr[] = $val['pay_id'];
//	}
//	return $arr;
////  $sql = "SELECT pay_id FROM " . $GLOBALS['ecs']->table('payment');
////	$sql .= " WHERE is_cod = 1";
////	$sql .= " WHERE is_cod = 0";
////	return $GLOBALS['db']->getCol($sql);
//}


// TODO:以下为移入到app/cart的func的func
/**
 * 获得购物车中商品的总重量、总价格、总数量
 *
 * @access  public
 * @param   int	 $type   类型：默认普通商品
 * @return  array
 */
// function cart_weight_price($type = CART_GENERAL_GOODS) {
// 	$db 			= RC_Loader::load_app_model('cart_model', 'cart');
// 	$dbview 		= RC_Loader::load_app_model('package_goods_viewmodel','orders');
// 	$db_cartview 	= RC_Loader::load_app_model('cart_good_member_viewmodel', 'cart');

// 	$package_row['weight'] 			= 0;
// 	$package_row['amount'] 			= 0;
// 	$package_row['number'] 			= 0;
// 	$packages_row['free_shipping'] 	= 1;

// 	/* 计算超值礼包内商品的相关配送参数 */
// 	if ($_SESSION['user_id']) {
// 		$row = $db->field('goods_id, goods_number, goods_price')->where(array('extension_code' => 'package_buy' , 'user_id' => $_SESSION['user_id'] ))->select();
// 	} else {
// 		$row = $db->field('goods_id, goods_number, goods_price')->where(array('extension_code' => 'package_buy' , 'session_id' => SESS_ID ))->select();
// 	}

// 	if ($row) {
// 		$packages_row['free_shipping'] = 0;
// 		$free_shipping_count = 0;
// 		foreach ($row as $val) {
// 			// 如果商品全为免运费商品，设置一个标识变量
// 			$dbview->view = array(
// 					'goods' => array(
// 							'type'  => Component_Model_View::TYPE_LEFT_JOIN,
// 							'alias' => 'g',
// 							'on'    => 'g.goods_id = pg.goods_id ',
// 					)
// 			);

// 			$shipping_count = $dbview->where(array('g.is_shipping' => 0 , 'pg.package_id' => $val['goods_id']))->count();
// 			if ($shipping_count > 0) {
// 				// 循环计算每个超值礼包商品的重量和数量，注意一个礼包中可能包换若干个同一商品
// 				$dbview->view = array(
// 						'goods' => array(
// 								'type'  => Component_Model_View::TYPE_LEFT_JOIN,
// 								'alias' => 'g',
// 								'field' => 'SUM(g.goods_weight * pg.goods_number)|weight,SUM(pg.goods_number)|number',
// 								'on'    => 'g.goods_id = pg.goods_id',
// 						)
// 				);
// 				$goods_row = $dbview->find(array('g.is_shipping' => 0 , 'pg.package_id' => $val['goods_id']));

// 				$package_row['weight'] += floatval($goods_row['weight']) * $val['goods_number'];
// 				$package_row['amount'] += floatval($val['goods_price']) * $val['goods_number'];
// 				$package_row['number'] += intval($goods_row['number']) * $val['goods_number'];
// 			} else {
// 				$free_shipping_count++;
// 			}
// 		}
// 		$packages_row['free_shipping'] = $free_shipping_count == count($row) ? 1 : 0;
// 	}

// 	/* 获得购物车中非超值礼包商品的总重量 */
// 	$db_cartview->view =array(
// 			'goods' => array(
// 					'type'  => Component_Model_View::TYPE_LEFT_JOIN,
// 					'alias' => 'g',
// 					'field' => 'SUM(g.goods_weight * c.goods_number)|weight,SUM(c.goods_price * c.goods_number)|amount,SUM(c.goods_number)|number',
// 					'on'    => 'g.goods_id = c.goods_id'
// 			)
// 	);
// 	if ($_SESSION['user_id']) {
// 		$row = $db_cartview->find(array('c.user_id' => $_SESSION['user_id'] , 'rec_type' => $type , 'g.is_shipping' => 0 , 'c.extension_code' => array('neq' => package_buy)));
// 	} else {
// 		$row = $db_cartview->find(array('c.session_id' => SESS_ID , 'rec_type' => $type , 'g.is_shipping' => 0 , 'c.extension_code' => array('neq' => package_buy)));
// 	}

// 	$packages_row['weight'] = floatval($row['weight']) + $package_row['weight'];
// 	$packages_row['amount'] = floatval($row['amount']) + $package_row['amount'];
// 	$packages_row['number'] = intval($row['number']) + $package_row['number'];
// 	/* 格式化重量 */
// 	$packages_row['formated_weight'] = formated_weight($packages_row['weight']);
// 	return $packages_row;
// 	// 	$sql = 'SELECT goods_id, goods_number, goods_price FROM ' . $GLOBALS['ecs']->table('cart') . " WHERE extension_code = 'package_buy' AND session_id = '" . SESS_ID . "'";
// 	// 	$row = $GLOBALS['db']->getAll($sql);

// 	// 	$sql = 'SELECT count(*) FROM ' .
// 	// 			$GLOBALS['ecs']->table('package_goods') . ' AS pg, ' .
// 	// 			$GLOBALS['ecs']->table('goods') . ' AS g ' .
// 	// 			"WHERE g.goods_id = pg.goods_id AND g.is_shipping = 0 AND pg.package_id = '"  . $val['goods_id'] . "'";
// 	// 	$shipping_count = $GLOBALS['db']->getOne($sql);

// 	// 	$sql = 'SELECT SUM(g.goods_weight * pg.goods_number) AS weight, ' .
// 	// 			'SUM(pg.goods_number) AS number FROM ' .
// 	// 			$GLOBALS['ecs']->table('package_goods') . ' AS pg, ' .
// 	// 			$GLOBALS['ecs']->table('goods') . ' AS g ' .
// 	// 			"WHERE g.goods_id = pg.goods_id AND g.is_shipping = 0 AND pg.package_id = '"  . $val['goods_id'] . "'";
// 	// 	$goods_row = $GLOBALS['db']->getRow($sql);

// 	// 	$sql    = 'SELECT SUM(g.goods_weight * c.goods_number) AS weight, ' .
// 	// 			'SUM(c.goods_price * c.goods_number) AS amount, ' .
// 	// 			'SUM(c.goods_number) AS number '.
// 	// 			'FROM ' . $GLOBALS['ecs']->table('cart') . ' AS c '.
// 	// 			'LEFT JOIN ' . $GLOBALS['ecs']->table('goods') . ' AS g ON g.goods_id = c.goods_id '.
// 	// 			"WHERE c.session_id = '" . SESS_ID . "' " .
// 	// 			"AND rec_type = '$type' AND g.is_shipping = 0 AND c.extension_code != 'package_buy'";
// 	// 	$row = $GLOBALS['db']->getRow($sql);
// }

// /**
//  * 取得购物车商品
//  * @param   int     $type   类型：默认普通商品
//  * @return  array   购物车商品数组
//  */
// function cart_goods($type = CART_GENERAL_GOODS) {

// 	$db = RC_Loader::load_app_model('cart_model', 'cart');

// 	if ($_SESSION['user_id']) {
// 		$arr = $db->field('rec_id, user_id, goods_id, goods_name, goods_sn, goods_number,market_price, goods_price, goods_attr, is_real, extension_code, parent_id, is_gift, is_shipping, goods_price * goods_number|subtotal')->
// 		where('user_id = "'. $_SESSION['user_id'] . '" AND rec_type = "'.$type.'"')->select();
// 	} else {
// 		$arr = $db->field('rec_id, user_id, goods_id, goods_name, goods_sn, goods_number,market_price, goods_price, goods_attr, is_real, extension_code, parent_id, is_gift, is_shipping, goods_price * goods_number|subtotal')->
// 		where('session_id = "'. SESS_ID . '" AND rec_type = "'.$type.'"')->select();
// 	}


// 	/* 格式化价格及礼包商品 */
// 	foreach ($arr as $key => $value) {
// 		$arr[$key]['formated_market_price'] = price_format($value['market_price'], false);
// 		$arr[$key]['formated_goods_price']  = price_format($value['goods_price'], false);
// 		$arr[$key]['formated_subtotal']     = price_format($value['subtotal'], false);

// 		if ($value['extension_code'] == 'package_buy') {
// 			$arr[$key]['package_goods_list'] = get_package_goods($value['goods_id']);
// 		}
// 	}
// 	return $arr;

// 	//     $sql = "SELECT rec_id, user_id, goods_id, goods_name, goods_sn, goods_number, " .
// 	//             "market_price, goods_price, goods_attr, is_real, extension_code, parent_id, is_gift, is_shipping, " .
// 	//             "goods_price * goods_number AS subtotal " .
// 	//             "FROM " . $GLOBALS['ecs']->table('cart') ." WHERE session_id = '" . SESS_ID . "' " ."AND rec_type = '$type'";
// 	//     $arr = $GLOBALS['db']->getAll($sql);
// 	//	$arr = $db->field('rec_id, user_id, goods_id, goods_name, goods_sn, goods_number,market_price, goods_price, goods_attr, is_real, extension_code, parent_id, is_gift, is_shipping, goods_price * goods_number|subtotal')->
// 	//	where('session_id = "'. SESS_ID . '" AND rec_type = "'.$type.'"')->select();

// }


// /**
//  * 取得购物车总金额
//  * @params  boolean $include_gift   是否包括赠品
//  * @param   int     $type           类型：默认普通商品
//  * @return  float   购物车总金额
//  */
// function cart_amount($include_gift = true, $type = CART_GENERAL_GOODS) {
// 	$db = RC_Loader::load_app_model('cart_model', 'cart');

// 	if ($_SESSION['user_id']) {
// 		$where['user_id'] = $_SESSION['user_id'];
// 	} else {
// 		$where['session_id'] = SESS_ID;
// 	}

// 	$where['rec_type'] = $type;

// 	if (!$include_gift) {
// 		$where['is_gift'] = 0;
// 		$where['goods_id']= array('gt'=>0);
// 	}

// 	$data = $db->where($where)->sum('goods_price * goods_number');
// 	return $data;

// 	//     $sql = "SELECT SUM(goods_price * goods_number) " .
// 	//             " FROM " . $GLOBALS['ecs']->table('cart') .
// 	//             " WHERE session_id = '" . SESS_ID . "' " ."AND rec_type = '$type' ";
// 	// 	$sql .= ' AND is_gift = 0 AND goods_id > 0';
// 	// 	return floatval($GLOBALS['db']->getOne($sql));


// 	//	$db = RC_Loader::load_app_model('cart_model','flow');
// 	//    $data = $db->where("session_id = '" . SESS_ID . "' AND rec_type = '$type' ".$where)->sum('goods_price * goods_number');
// }

// /**
//  * 清空购物车
//  * @param   int	 $type   类型：默认普通商品
//  */
// function clear_cart($type = CART_GENERAL_GOODS) {
// 	//  $sql = "DELETE FROM " . $GLOBALS['ecs']->table('cart') ." WHERE session_id = '" . SESS_ID . "' AND rec_type = '$type'";
// 	//  $GLOBALS['db']->query($sql);

// 	//$db_cart = RC_Loader::load_app_model('cart_model','flow');
// 	$db_cart = RC_Loader::load_app_model('cart_model', 'cart');
// 	if ($_SESSION['user_id']) {
// 		$db_cart->where(array('user_id' => $_SESSION['user_id'] , 'rec_type' => $type))->delete();
// 	} else {
// 		$db_cart->where(array('session_id' => SESS_ID , 'rec_type' => $type))->delete();
// 	}
// }

// /**
//  * 获得购物车中的商品
//  *
//  * @access  public
//  * @return  array
//  */
// function get_cart_goods() {
// 	RC_Loader::load_app_func('common','goods');
// 	$db_cart 		= RC_Loader::load_app_model('cart_model', 'cart');
// 	$db_goods_attr 	= RC_Loader::load_app_model('goods_attr_model','goods');
// 	$db_goods 		= RC_Loader::load_app_model('goods_model','goods');

// 	/* 初始化 */
// 	$goods_list = array();
// 	$total = array(
// 			'goods_price'  => 0, // 本店售价合计（有格式）
// 			'market_price' => 0, // 市场售价合计（有格式）
// 			'saving'       => 0, // 节省金额（有格式）
// 			'save_rate'    => 0, // 节省百分比
// 			'goods_amount' => 0, // 本店售价合计（无格式）
// 	);

// 	/* 循环、统计 */
// 	if ($_SESSION['user_id']) {
// 		$data = $db_cart->field('*,IF(parent_id, parent_id, goods_id)|pid')->where(array('user_id' => $_SESSION['user_id'] , 'rec_type' => CART_GENERAL_GOODS))->order(array('pid'=>'asc', 'parent_id'=>'asc'))->select();
// 	} else {
// 		$data = $db_cart->field('*,IF(parent_id, parent_id, goods_id)|pid')->where(array('session_id' => SESS_ID , 'rec_type' => CART_GENERAL_GOODS))->order(array('pid'=>'asc', 'parent_id'=>'asc'))->select();
// 	}


// 	/* 用于统计购物车中实体商品和虚拟商品的个数 */
// 	$virtual_goods_count = 0;
// 	$real_goods_count    = 0;

// 	if (!empty($data)) {
// 		foreach ($data as $row) {
// 			$total['goods_price']  += $row['goods_price'] * $row['goods_number'];
// 			$total['market_price'] += $row['market_price'] * $row['goods_number'];
// 			$row['subtotal']     	= price_format($row['goods_price'] * $row['goods_number'], false);
// 			$row['goods_price']  	= price_format($row['goods_price'], false);
// 			$row['market_price'] 	= price_format($row['market_price'], false);

// 			/* 统计实体商品和虚拟商品的个数 */
// 			if ($row['is_real']) {
// 				$real_goods_count++;
// 			} else {
// 				$virtual_goods_count++;
// 			}

// 			/* 查询规格 */
// 			if (trim($row['goods_attr']) != '') {
// 				$row['goods_attr'] = addslashes($row['goods_attr']);
// 				$attr_list = $db_goods_attr->field('attr_value')->in(array('goods_attr_id' =>$row['goods_attr_id']))->select();
// 				foreach ($attr_list AS $attr) {
// 					$row['goods_name'] .= ' [' . $attr[attr_value] . '] ';
// 				}
// 			}
// 			/* 增加是否在购物车里显示商品图 */
// 			if ((ecjia::config('show_goods_in_cart') == "2" || ecjia::config('show_goods_in_cart') == "3") &&
// 			$row['extension_code'] != 'package_buy') {

// 				$goods_thumb 		= $db_goods->field('goods_thumb')->find(array('goods_id' => '{'.$row['goods_id'].'}'));
// 				$row['goods_thumb'] = get_image_path($row['goods_id'], $goods_thumb, true);
// 			}
// 			if ($row['extension_code'] == 'package_buy') {
// 				$row['package_goods_list'] = get_package_goods($row['goods_id']);
// 			}
// 			$goods_list[] = $row;
// 		}
// 	}
// 	$total['goods_amount'] = $total['goods_price'];
// 	$total['saving']       = price_format($total['market_price'] - $total['goods_price'], false);
// 	if ($total['market_price'] > 0) {
// 		$total['save_rate'] = $total['market_price'] ? round(($total['market_price'] - $total['goods_price']) *
// 				100 / $total['market_price']).'%' : 0;
// 	}
// 	$total['goods_price']  			= price_format($total['goods_price'], false);
// 	$total['market_price'] 			= price_format($total['market_price'], false);
// 	$total['real_goods_count']    	= $real_goods_count;
// 	$total['virtual_goods_count'] 	= $virtual_goods_count;

// 	return array('goods_list' => $goods_list, 'total' => $total);
// 	// 	$sql = "SELECT *, IF(parent_id, parent_id, goods_id) AS pid " .
// 	// 			" FROM " . $GLOBALS['ecs']->table('cart') . " " .
// 	// 			" WHERE session_id = '" . SESS_ID . "' AND rec_type = '" . CART_GENERAL_GOODS . "'" ." ORDER BY pid, parent_id";
// 	// 	$res = $GLOBALS['db']->query($sql);
// 	// 	while ($row = $GLOBALS['db']->fetchRow($res))

// 	// 	$sql = "SELECT attr_value FROM " . $GLOBALS['ecs']->table('goods_attr') . " WHERE goods_attr_id " .db_create_in($row['goods_attr']);
// 	// 	$attr_list = $GLOBALS['db']->getCol($sql);

// 	// 	$goods_thumb = $GLOBALS['db']->getOne("SELECT `goods_thumb` FROM " . $GLOBALS['ecs']->table('goods') . " WHERE `goods_id`='{$row['goods_id']}'");

// }

// /**
//  * 计算折扣：根据购物车和优惠活动
//  * @return  float   折扣
//  */
// function compute_discount() {
// 	//	$db 			= RC_Loader::load_app_model('favourable_activity_model','favourable');
// 	$db 			= RC_Loader::load_app_model('favourable_activity_model', 'orders');
// 	$db_cartview 	= RC_Loader::load_app_model('cart_good_member_viewmodel', 'cart');

// 	/* 查询优惠活动 */
// 	$now = RC_Time::gmtime();
// 	$user_rank = ',' . $_SESSION['user_rank'] . ',';

// 	$favourable_list = $db->where("start_time <= '$now' AND end_time >= '$now' AND CONCAT(',', user_rank, ',') LIKE '%" . $user_rank . "%'")->in(array('act_type'=>array(FAT_DISCOUNT, FAT_PRICE)))->select();
// 	if (!$favourable_list) {
// 		return 0;
// 	}

// 	/* 查询购物车商品 */
// 	$db_cartview->view = array(
// 			'goods' => array(
// 					'type'  => Component_Model_View::TYPE_LEFT_JOIN,
// 					'alias' => 'g',
// 					'field' => "c.goods_id, c.goods_price * c.goods_number AS subtotal, g.cat_id, g.brand_id",
// 					'on'   	=> 'c.goods_id = g.goods_id'
// 			)
// 	);
// 	if ($_SESSION['user_id']) {
// 		$goods_list = $db_cartview->where(array('c.user_id' => $_SESSION['user_id'] , 'c.parent_id' => 0 , 'c.is_gift' => 0 , 'rec_type' => CART_GENERAL_GOODS))->select();
// 	} else {
// 		$goods_list = $db_cartview->where(array('c.session_id' => SESS_ID , 'c.parent_id' => 0 , 'c.is_gift' => 0 , 'rec_type' => CART_GENERAL_GOODS))->select();
// 	}


// 	if (!$goods_list) {
// 		return 0;
// 	}

// 	/* 初始化折扣 */
// 	$discount = 0;
// 	$favourable_name = array();

// 	/* 循环计算每个优惠活动的折扣 */
// 	foreach ($favourable_list as $favourable) {
// 		$total_amount = 0;
// 		if ($favourable['act_range'] == FAR_ALL) {
// 			foreach ($goods_list as $goods) {
// 				$total_amount += $goods['subtotal'];
// 			}
// 		} elseif ($favourable['act_range'] == FAR_CATEGORY) {
// 			/* 找出分类id的子分类id */
// 			$id_list = array();
// 			$raw_id_list = explode(',', $favourable['act_range_ext']);
// 			foreach ($raw_id_list as $id) {
// 				$id_list = array_merge($id_list, array_keys(cat_list($id, 0, false)));
// 			}
// 			$ids = join(',', array_unique($id_list));

// 			foreach ($goods_list as $goods) {
// 				if (strpos(',' . $ids . ',', ',' . $goods['cat_id'] . ',') !== false) {
// 					$total_amount += $goods['subtotal'];
// 				}
// 			}
// 		} elseif ($favourable['act_range'] == FAR_BRAND) {
// 			foreach ($goods_list as $goods) {
// 				if (strpos(',' . $favourable['act_range_ext'] . ',', ',' . $goods['brand_id'] . ',') !== false) {
// 					$total_amount += $goods['subtotal'];
// 				}
// 			}
// 		} elseif ($favourable['act_range'] == FAR_GOODS) {
// 			foreach ($goods_list as $goods) {
// 				if (strpos(',' . $favourable['act_range_ext'] . ',', ',' . $goods['goods_id'] . ',') !== false) {
// 					$total_amount += $goods['subtotal'];
// 				}
// 			}
// 		} else {
// 			continue;
// 		}

// 		/* 如果金额满足条件，累计折扣 */
// 		if ($total_amount > 0 && $total_amount >= $favourable['min_amount'] &&
// 		($total_amount <= $favourable['max_amount'] || $favourable['max_amount'] == 0)) {
// 			if ($favourable['act_type'] == FAT_DISCOUNT) {
// 				$discount += $total_amount * (1 - $favourable['act_type_ext'] / 100);

// 				$favourable_name[] = $favourable['act_name'];
// 			} elseif ($favourable['act_type'] == FAT_PRICE) {
// 				$discount += $favourable['act_type_ext'];
// 				$favourable_name[] = $favourable['act_name'];
// 			}
// 		}
// 	}
// 	return array('discount' => $discount, 'name' => $favourable_name);

// 	// 	$sql = "SELECT *" .
// 	// 			"FROM " . $GLOBALS['ecs']->table('favourable_activity') .
// 	// 			" WHERE start_time <= '$now'" .
// 	// 			" AND end_time >= '$now'" .
// 	// 			" AND CONCAT(',', user_rank, ',') LIKE '%" . $user_rank . "%'" .
// 	// 			" AND act_type " . db_create_in(array(FAT_DISCOUNT, FAT_PRICE));
// 	// 	$favourable_list = $GLOBALS['db']->getAll($sql);

// 	// 	$sql = "SELECT c.goods_id, c.goods_price * c.goods_number AS subtotal, g.cat_id, g.brand_id " .
// 	// 			"FROM " . $GLOBALS['ecs']->table('cart') . " AS c, " . $GLOBALS['ecs']->table('goods') . " AS g " .
// 	// 			"WHERE c.goods_id = g.goods_id " .
// 	// 			"AND c.session_id = '" . SESS_ID . "' " .
// 	// 			"AND c.parent_id = 0 " .
// 	// 			"AND c.is_gift = 0 " .
// 	// 			"AND rec_type = '" . CART_GENERAL_GOODS . "'";
// 	// 	$goods_list = $GLOBALS['db']->getAll($sql);
// }
// /**
//  * 计算购物车中的商品能享受红包支付的总额
//  * @return  float   享受红包支付的总额
//  */
// function compute_discount_amount() {
// 	// 	$sql = "SELECT *" .
// 	// 			"FROM " . $GLOBALS['ecs']->table('favourable_activity') .
// 	// 			" WHERE start_time <= '$now'" .
// 	// 			" AND end_time >= '$now'" .
// 	// 			" AND CONCAT(',', user_rank, ',') LIKE '%" . $user_rank . "%'" .
// 	// 			" AND act_type " . db_create_in(array(FAT_DISCOUNT, FAT_PRICE));
// 	// 	$favourable_list = $GLOBALS['db']->getAll($sql);

// 	// 	$sql = "SELECT c.goods_id, c.goods_price * c.goods_number AS subtotal, g.cat_id, g.brand_id " .
// 	// 			"FROM " . $GLOBALS['ecs']->table('cart') . " AS c, " . $GLOBALS['ecs']->table('goods') . " AS g " .
// 	// 			"WHERE c.goods_id = g.goods_id " .
// 	// 			"AND c.session_id = '" . SESS_ID . "' " ."AND c.parent_id = 0 " ."AND c.is_gift = 0 " .
// 	// 			"AND rec_type = '" . CART_GENERAL_GOODS . "'";
// 	// 	$goods_list = $GLOBALS['db']->getAll($sql);



// 	//	$db 			= RC_Loader::load_app_model('favourable_activity_model','favourable');
// 	$db 			= RC_Loader::load_app_model('favourable_activity_model', 'orders');
// 	$db_cartview 	= RC_Loader::load_app_model('cart_good_member_viewmodel', 'cart');
// 	/* 查询优惠活动 */
// 	$now = RC_Time::gmtime();
// 	$user_rank = ',' . $_SESSION['user_rank'] . ',';

// 	$favourable_list = $db->where('start_time <= '.$now.' AND end_time >= '.$now.' AND CONCAT(",", user_rank, ",") LIKE "%' . $user_rank . '%" ')->in(array('act_type' => array(FAT_DISCOUNT, FAT_PRICE)))->select();
// 	if (!$favourable_list) {
// 		return 0;
// 	}

// 	/* 查询购物车商品 */
// 	$db_cartview->view = array(
// 			'goods' => array(
// 					'type'  => Component_Model_View::TYPE_LEFT_JOIN,
// 					'alias' => 'g',
// 					'field' => "c.goods_id, c.goods_price * c.goods_number AS subtotal, g.cat_id, g.brand_id",
// 					'on'    => 'c.goods_id = g.goods_id'
// 			)
// 	);
// 	if ($_SESSION['user_id']) {
// 		$goods_list = $db_cartview->where(array('c.user_id' => $_SESSION['user_id'] , 'c.parent_id' => 0 , 'c.is_gift' => 0 , 'rec_type' => CART_GENERAL_GOODS))->select();
// 	} else {
// 		$goods_list = $db_cartview->where(array('c.session_id' => SESS_ID , 'c.parent_id' => 0 , 'c.is_gift' => 0 , 'rec_type' => CART_GENERAL_GOODS))->select();
// 	}

// 	if (!$goods_list) {
// 		return 0;
// 	}

// 	/* 初始化折扣 */
// 	$discount = 0;
// 	$favourable_name = array();

// 	/* 循环计算每个优惠活动的折扣 */
// 	foreach ($favourable_list as $favourable) {
// 		$total_amount = 0;
// 		if ($favourable['act_range'] == FAR_ALL) {
// 			foreach ($goods_list as $goods) {
// 				$total_amount += $goods['subtotal'];
// 			}
// 		} elseif ($favourable['act_range'] == FAR_CATEGORY) {
// 			/* 找出分类id的子分类id */
// 			$id_list = array();
// 			$raw_id_list = explode(',', $favourable['act_range_ext']);
// 			foreach ($raw_id_list as $id) {
// 				$id_list = array_merge($id_list, array_keys(cat_list($id, 0, false)));
// 			}
// 			$ids = join(',', array_unique($id_list));

// 			foreach ($goods_list as $goods) {
// 				if (strpos(',' . $ids . ',', ',' . $goods['cat_id'] . ',') !== false) {
// 					$total_amount += $goods['subtotal'];
// 				}
// 			}
// 		} elseif ($favourable['act_range'] == FAR_BRAND) {
// 			foreach ($goods_list as $goods) {
// 				if (strpos(',' . $favourable['act_range_ext'] . ',', ',' . $goods['brand_id'] . ',') !== false) {
// 					$total_amount += $goods['subtotal'];
// 				}
// 			}
// 		} elseif ($favourable['act_range'] == FAR_GOODS) {
// 			foreach ($goods_list as $goods) {
// 				if (strpos(',' . $favourable['act_range_ext'] . ',', ',' . $goods['goods_id'] . ',') !== false) {
// 					$total_amount += $goods['subtotal'];
// 				}
// 			}
// 		} else {
// 			continue;
// 		}

// 		if ($total_amount > 0 && $total_amount >= $favourable['min_amount'] && ($total_amount <= $favourable['max_amount'] || $favourable['max_amount'] == 0)) {
// 			if ($favourable['act_type'] == FAT_DISCOUNT) {
// 				$discount += $total_amount * (1 - $favourable['act_type_ext'] / 100);
// 			} elseif ($favourable['act_type'] == FAT_PRICE) {
// 				$discount += $favourable['act_type_ext'];
// 			}
// 		}
// 	}
// 	return $discount;
// }

// /**
//  * 取得购物车该赠送的积分数
//  * @return  int	 积分数
//  */
// function get_give_integral() {

// 	$db_cartview = RC_Loader::load_app_model('cart_good_member_viewmodel', 'cart');

// 	$db_cartview->view = array(
// 			'goods' => array(
// 					'type'  => Component_Model_View::TYPE_LEFT_JOIN,
// 					'alias' => 'g',
// 					'field' => "c.rec_id, c.goods_id, c.goods_attr_id, g.promote_price, g.promote_start_date, c.goods_number,g.promote_end_date, IFNULL(mp.user_price, g.shop_price * '$_SESSION[discount]') AS member_price",
// 					'on'    => 'g.goods_id = c.goods_id'
// 			),
// 	);
// 	$field = array();
// 	if ($_SESSION['user_id']) {
// 		return  intval($db_cartview->where(array('c.user_id' => $_SESSION['user_id'] , 'c.goods_id' => array('gt' => 0) ,'c.parent_id' => 0 ,'c.rec_type' => 0 , 'c.is_gift' => 0))->sum('c.goods_number * IF(g.give_integral > -1, g.give_integral, c.goods_price)'));
// 	} else {
// 		return  intval($db_cartview->where(array('c.session_id' => SESS_ID , 'c.goods_id' => array('gt' => 0) ,'c.parent_id' => 0 ,'c.rec_type' => 0 , 'c.is_gift' => 0))->sum('c.goods_number * IF(g.give_integral > -1, g.give_integral, c.goods_price)'));
// 	}
// 	//         $sql = "SELECT SUM(c.goods_number * IF(g.give_integral > -1, g.give_integral, c.goods_price))" .
// 	//                 "FROM " . $GLOBALS['ecs']->table('cart') . " AS c, " . $GLOBALS['ecs']->table('goods') . " AS g " .
// 	//                 "WHERE c.goods_id = g.goods_id " ."AND c.session_id = '" . SESS_ID . "' " .
// 	//                 "AND c.goods_id > 0 " ."AND c.parent_id = 0 " . "AND c.rec_type = 0 " ."AND c.is_gift = 0";
// 	//         return intval($GLOBALS['db']->getOne($sql));

// 	//	$db_cartview = RC_Loader::load_app_model('cart_good_member_viewmodel','flow');
// }

// end
