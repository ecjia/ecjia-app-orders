<?php

namespace Ecjia\App\Orders;

use RC_Time;
use RC_DB;

/**
 * 订单状态日志记录
 */
class OrderStatusLog
{
    
    protected $order_id;
    
    public function __construct($order_id)
    {
        $this->order_id = $order_id;
    }
    
    /**
     * 生成订单时状态日志
     * @param string $order_sn 订单编号
     * @return bool
     */
    public function generateOrder($order_sn)
    {
        $order_status = '订单提交成功';
        $message = '下单成功，订单号：'.$order_sn;
        return $this->execute($order_status, $message);
    }
    
    /**
     * 生成订单同时提醒付款
     * @return bool
     */
    public function remindPay()
    {
        $order_status = '待付款';
        $message = '请尽快支付该订单，超时将会自动取消订单';
        return $this->execute($order_status, $message);
    }
    
    /**
     * 订单付款成功时
     * @return bool
     */
    public function orderPaid()
    {
        $order_status = '已付款';
        $message = '已通知商家处理，请耐心等待';
        return $this->execute($order_status, $message);
    }
    
    /**
     * 订单付款成功时同时通知商家
     * @return bool
     */
    public function notifyMerchant()
    {
        $order_status = '等待商家接单';
        $message = '订单已通知商家，等待商家处理';
        return $this->execute($order_status, $message);
    }
    
    /**
     * 发货单入库
     * @param string $order_sn 订单编号
     * @return bool
     */
    public function generateDeliveryOrderInvoice($order_sn)
    {
        $order_status = '配货中';
        $message = sprintf("订单号为 %s 的商品正在备货中，请您耐心等待", $order_sn);
        return $this->execute($order_status, $message);
    }
    
    /**
     * 完成发货
     * @param string $order_sn 订单编号
     * @return bool
     */
    public function deliveryShipFinished($order_sn)
    {
        $order_status = '已发货';
        $message = sprintf("订单号为 %s 的商品已发货，请您耐心等待", $order_sn);
        return $this->execute($order_status, $message);
    }
    
    /**
     * 订单确认收货
     * @return bool
     */
    public function affirmReceived()
    {
        $order_status = '已确认收货';
        $message = '宝贝已签收，购物愉快！';
        $this->execute($order_status, $message);
        
        $order_status = '订单已完成';
        $message = '感谢您在'.\ecjia::config('shop_name').'购物，欢迎您再次光临！';
        return $this->execute($order_status, $message);
    }
    
    /**
     * 取消订单
     * @return bool
     */
    public function cancel()
    {
        $order_status = '订单已取消';
        $message = '您的订单已取消成功！';
        return $this->execute($order_status, $message);
    }
    
    /**
     * 仅退款订单已处理
     * @param integer $status
     * @return bool
     */
    public function refundOrderProcess($status)
    {
        if ($status == 1) {
            $message = '申请审核已通过';
        } else {
            $message ='申请审核未通过';
        }
        
        $order_status = '订单退款申请已处理';
        return $this->execute($order_status, $message);
    }
    
    /**
     * 退货退款订单已处理
     * @param integer $status
     * @return bool
     */
    public function returnOrderProcess($status)
    {
        $order_status = '订单退货退款申请已处理';
        if ($status == 1) {
    		$message = '申请审核已通过，请选择返回方式';
    	} else {
    		$message = '申请审核未通过';
    	}
        return $this->execute($order_status, $message);
    }
    
    /**
     * 订单确认收货处理
     * @param integer $status
     * @return bool
     */
    public function returnConfirmReceive($status)
    {
        $order_status = '确认收货处理';
        if ( $status == 3) {
    		$message = '商家已确认收货，等价商家退款';
    	} else {
    		$message = '商家拒绝确认收货，理由：商品没有问题';
    	}
        return $this->execute($order_status, $message);
    }
    
    /**
     * 订单退款到账处理
     * @param array $options
     * @return bool
     */
    public function refundPayRecord($back_money)
    {
        $order_status = '退款到账';
        $message = '您的退款'.$back_money.'元，已退回至您的余额，请查收';
        return $this->execute($order_status, $message);
    }
    
    
    
    /**
     * Database writes
     * @param string $order_status
     * @param string $message
     */
    protected function execute($order_status, $message)
    {
        $data = [
        	'order_status'  => $order_status,
            'order_id'      => $this->order_id,
            'message'       => $message,
            'add_time'      => RC_Time::gmtime(),
        ];
        
        return RC_DB::table('order_status_log')->insert($data);
    }
    
    
}

// end