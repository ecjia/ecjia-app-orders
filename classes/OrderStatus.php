<?php

namespace Ecjia\App\Orders;

use RC_Lang;

class OrderStatus
{
    /* 已完成订单 */
    const FINISHED = 'finished';
    
    /* 待付款订单 */
    const AWAIT_PAY = 'await_pay';
    
    /* 待发货订单 */
    const AWAIT_SHIP = 'await_ship';
    
    /* 未确认订单 */
    const UNCONFIRMED = 'unconfirmed';
    
    /* 未处理订单：用户可操作 */
    const UNPROCESSED = 'unprocessed';
    
    /* 未付款未发货订单：管理员可操作 */
    const UNPAY_UNSHIP = 'unpay_unship';
    
    /* 已发货订单：不论是否付款 */
    const SHIPPED = 'shipped';
    
    /* 退货*/
    const REFUND = 'refund';
    
    /* 无效*/
    const INVALID = 'invalid';
    
    /* 取消*/
    const CANCELED = 'canceled';
    

    
    public static function getOrderStatusLabel($order_status, $shipping_status, $pay_status, $is_cod) 
    {
        if (in_array($order_status, array(OS_CONFIRMED, OS_SPLITED)) &&
            in_array($shipping_status, array(SS_RECEIVED)) &&
            in_array($pay_status, array(PS_PAYED, PS_PAYING)))
        {
            $label_order_status = RC_Lang::get('orders::order.cs.'.CS_FINISHED);
            $status_code = 'finished';
        }
        elseif (in_array($shipping_status, array(SS_SHIPPED)))
        {
            $label_order_status = RC_Lang::get('orders::order.label_await_confirm');
            $status_code = 'shipped';
        }
        elseif (in_array($order_status, array(OS_CONFIRMED, OS_SPLITED, OS_UNCONFIRMED)) &&
            in_array($pay_status, array(PS_UNPAYED)) &&
            (in_array($shipping_status, array(SS_SHIPPED, SS_RECEIVED)) || !$is_cod))
        {
            $label_order_status = RC_Lang::get('orders::order.label_await_pay');
            $status_code = 'await_pay';
        }
        elseif (in_array($order_status, array(OS_UNCONFIRMED, OS_CONFIRMED, OS_SPLITED, OS_SPLITING_PART)) &&
            in_array($shipping_status, array(SS_UNSHIPPED, SS_PREPARING, SS_SHIPPED_ING)) &&
            (in_array($pay_status, array(PS_PAYED, PS_PAYING)) || $is_cod))
        {
            $label_order_status = RC_Lang::get('orders::order.label_await_ship');
            $status_code = 'await_ship';
        }
        elseif (in_array($order_status, array(OS_SPLITING_PART)) &&
            in_array($shipping_status, array(SS_SHIPPED_PART)))
        {
            $label_order_status = RC_Lang::get('orders::order.label_shipped_part');
            $status_code = 'shipped_part';
        }
        elseif (in_array($order_status, array(OS_CANCELED))) {
            $label_order_status = RC_Lang::get('orders::order.label_canceled');
            $status_code = 'canceled';
        }
        
        return array($label_order_status, $status_code);
    }
    
    /* 已完成订单 */
    public static function queryOrderFinished($alias = '')
    {
    	return function ($query) {
    		$query->whereIn('order_info.order_status', array(OS_CONFIRMED, OS_SPLITED))
    		->whereIn('order_info.shipping_status', array(SS_SHIPPED, SS_RECEIVED))
    		->whereIn('order_info.pay_status', array(PS_PAYED, PS_PAYING));
    	};
        
    }
    
    /* 待付款订单 */
    public static function queryOrderAwaitPay($alias = '') 
    {
    	//pay_id待处理
    	return function ($query) {
    		$query->whereIn('order_info.order_status', array(OS_UNCONFIRMED, OS_CONFIRMED, OS_SPLITED))
    		->where('order_info.pay_status', PS_UNPAYED)
    		->whereIn('order_info.shipping_status', array(SS_SHIPPED, SS_RECEIVED));
    	};
    }
    
    
    /* 待发货订单 */
    public static function queryOrderAwaitShip($alias = '') 
    {
    	//pay_id待处理
    	return function ($query) {
    		$query->whereIn('order_info.order_status', array(OS_UNCONFIRMED, OS_CONFIRMED, OS_SPLITED, OS_SPLITING_PART))
    		->whereIn('order_info.shipping_status', array(SS_UNSHIPPED, SS_PREPARING, SS_SHIPPED_ING))
    		->whereIn('order_info.pay_status', array(PS_PAYED, PS_PAYING));
    		
    	};
        
    }
    
    /* 未确认订单 */
    public static function queryOrderUnconfirmed() 
    {
        return function ($query) {
            $query->where('order_info.order_status', OS_UNCONFIRMED);
        };
    }
    
    
    /* 未处理订单：用户可操作 */
    public static function queryOrderUnprocessed() 
    {
        return function ($query) {
        	$query->whereIn('order_info.order_status', array(OS_UNCONFIRMED, OS_CONFIRMED))
        	      ->where('order_info.shipping_status', SS_UNSHIPPED)
        	      ->where('order_info.pay_status', PS_UNPAYED);
        };
    }
    
    /* 未付款未发货订单：管理员可操作 */
    public static function queryOrderUnpayUnship() 
    {
    	return function ($query) {
    		$query->whereIn('order_info.order_status', array(OS_UNCONFIRMED, OS_CONFIRMED))
    		->whereIn('order_info.shipping_status', array(SS_UNSHIPPED, SS_PREPARING))
    		->where('order_info.pay_status', PS_UNPAYED);
    	};
    }
    
    /* 已发货订单：不论是否付款 */
    public static function queryOrderShipped($alias = '') 
    {
    	return function ($query) {
    		$query->where('order_info.shipping_status', SS_SHIPPED);
    	};
    }
    
    /* 退货*/
    public static function queryOrderRefund($alias = '') 
    {
    	return function ($query) {
    		$query->where('order_info.order_status', OS_RETURNED);
    	};
    }
    
    /* 无效*/
    public static function queryOrderRefundInvalid($alias = '')
    {
    	return function ($query) {
    		$query->where('order_info.order_status', OS_INVALID);
    	};
    }
    
    /* 取消*/
    public static function queryOrderRefundCanceled($alias = '') 
    {
    	return function ($query) {
    		$query->where('order_info.order_status', OS_CANCELED);
    	};
    }

}
