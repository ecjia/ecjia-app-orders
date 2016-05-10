<?php
defined('IN_ECJIA') or exit('No permission resources.');
/**
 * ECJIA 订单查询条件类文件
 */

defined('IN_ECJIA') or exit('No permission resources.');

class order_list{
	
// 	private $where = array();//where条件数组
	
	/* 已完成订单 */
	public static function order_finished($alias = '') {
		$where = array();
    	$where[$alias.'order_status'] = array(OS_CONFIRMED, OS_SPLITED);
		$where[$alias.'shipping_status'] = array(SS_SHIPPED, SS_RECEIVED);
		$where[$alias.'pay_status'] = array(PS_PAYED, PS_PAYING);
		return $where;
	}
	
	/* 待付款订单 */
	public static function order_await_pay($alias = '') {
		$where = array();
		$payment_method = RC_Loader::load_app_class('payment_method','payment');
		$payment_id_row = $payment_method->payment_id_list(false);
		$payment_id = "";
		foreach ($payment_id_row as $v) {
			$payment_id .= empty($payment_id) ? $v : ','.$v ;
		}
		$payment_id = empty($payment_id) ? "''" : $payment_id;
    	$where[$alias.'order_status'] = array(OS_UNCONFIRMED, OS_CONFIRMED,OS_SPLITED);
        $where[$alias.'pay_status'] = PS_UNPAYED;
        $where[]= "( {$alias}shipping_status in (". SS_SHIPPED .",". SS_RECEIVED .") OR {$alias}pay_id in (" . $payment_id . ") )";
        return $where;
	}
	
	/* 待发货订单 */
	public static function order_await_ship($alias = '') {
		$where = array();
		$payment_method = RC_Loader::load_app_class('payment_method','payment');
		$payment_id_row = $payment_method->payment_id_list(true);
		$payment_id = "";
		foreach ($payment_id_row as $v) {
			$payment_id .= empty($payment_id) ? $v : ','.$v ;
		}
		$payment_id = empty($payment_id) ? "''" : $payment_id;
    	$where[$alias.'order_status'] = array(OS_UNCONFIRMED, OS_CONFIRMED, OS_SPLITED, OS_SPLITING_PART);
		$where[$alias.'shipping_status'] = array(SS_UNSHIPPED, SS_PREPARING, SS_SHIPPED_ING);
		$where[] = "( {$alias}pay_status in (" . PS_PAYED .",". PS_PAYING.") OR {$alias}pay_id in (" . $payment_id . "))";
		return $where;
	}
	
	/* 未确认订单 */
	public static function order_unconfirmed($alias = '') {
		$where = array();
		$where[$alias.'order_status'] = OS_UNCONFIRMED;
		return $where;
	}
	
	/* 未处理订单：用户可操作 */
	public static function order_unprocessed($alias = '') {
		$where = array();
    	$where[$alias.'order_status'] =  array(OS_UNCONFIRMED, OS_CONFIRMED);
        $where[$alias.'shipping_status'] = SS_UNSHIPPED;
        $where[$alias.'pay_status'] = PS_UNPAYED;
		return $where;
	}
	
	/* 未付款未发货订单：管理员可操作 */
	public static function order_unpay_unship($alias = '') {
		$where = array();
    	$where[$alias.'order_status'] = array(OS_UNCONFIRMED, OS_CONFIRMED);
        $where[$alias.'shipping_status'] = array(SS_UNSHIPPED, SS_PREPARING);
        $where[$alias.'pay_status'] = PS_UNPAYED;
        return $where;
	}
	
	/* 已发货订单：不论是否付款 */
	public static function order_shipped($alias = '') {
		$where = array();
//         $where[$alias.'order_status'] = OS_CONFIRMED;
        $where[$alias.'shipping_status'] = array(SS_SHIPPED);
        return $where;
	}

	/* 退货*/
    public static function order_refund($alias = '') {
    	$where = array();
        $where[$alias.'order_status'] = OS_RETURNED;
        return $where;
    }
    
    /* 无效*/
    public static function order_invalid($alias = '') {
    	$where = array();
        $where[$alias.'order_status'] = OS_INVALID;
        return $where;
    }
    
    /* 取消*/
    public static function order_canceled($alias = '') {
    	$where = array();
    	$where[$alias.'order_status'] = OS_CANCELED;
    	return $where;
    }

	
	
// 	/**
// 	 * 生成查询订单总金额的字段
// 	 * @param   string  $alias  order表的别名（包括.例如 o.）
// 	 * @return  string
// 	 */
// 	function order_amount_field($alias = '') {
// 	    return "   {$alias}goods_amount + {$alias}tax + {$alias}shipping_fee" .
// 	           " + {$alias}insure_fee + {$alias}pay_fee + {$alias}pack_fee" .
// 	           " + {$alias}card_fee ";
// 	}
	
	
	 
}

// end