<?php

namespace Ecjia\App\Orders;

use RC_Api;
use RC_DB;

class OrderPrint
{
    
    protected $order_id;
    
    protected $store_id;
    
    
    public function __construct($order_id, $store_id)
    {
        $this->order_id = $order_id;
        $this->store_id = $store_id;
        
        
        
        
    }
    
    
    
    public function doPrint()
    {
        //1.获取订单信息
        $order    = RC_Api::api('orders', 'order_info', array('order_id' => $this->order_id, 'store_id' => $this->store_id));
        if (is_ecjia_error($order)) {
            return $order;
        }
        
        //2.获取店铺信息
        $store    = RC_Api::api('store', 'store_info', array('store_id' => $this->store_id));
        if (is_ecjia_error($store)) {
            return $store;
        }
        
        //3.获取用户信息
        $user = RC_Api::api('user', 'user_info', array('user_id' => $order['user_id']));
        if (is_ecjia_error($user)) {
            $order['user_name'] = '匿名用户';
        } else {
            $order['user_name'] = $user['user_name'];
        }
        
        //4.获取配送方式，判断订单类型
        $type          = 'print_buy_orders';
        $shipping_data = ecjia_shipping::getPluginDataById($order['shipping_id']);
        if ($shipping_data['shipping_code'] == 'ship_o2o_express') {
            $type = 'print_takeaway_orders';
        }
        
        //5.获取订单中的商品列表
        $goods_list = $this->getGoodsList();
        
        //6.获取支付流水号
        $order_trade_no = $this->getPayRecord($order);
        
        
        if ($type == 'print_buy_orders') {
            $this->print_buy_orders($order, $store, $user, $goods_list, $order_trade_no);
        } elseif ($type == 'print_takeaway_orders') {
            $this->print_takeaway_orders($order, $store, $user, $goods_list, $order_trade_no);
        }
    }
    
    /**
     * 打印普通订单小票
     */
    public function print_buy_orders($order, $store, $user, $goods_list, $order_trade_no)
    {
        
    }
    
    
    /**
     * 打印外卖订单小票
     */
    public function print_takeaway_orders($order, $store, $user, $goods_list, $order_trade_no)
    {
        
    }
    
    
    /**
     * 获取商品列表
     * @return array
     */
    public function getGoodsList()
    {
        $goods_list = array();
        
        $data = RC_DB::table('order_goods as o')
                    ->leftJoin('products as p', RC_DB::raw('p.product_id'), '=', RC_DB::raw('o.product_id'))
                    ->leftJoin('goods as g', RC_DB::raw('o.goods_id'), '=', RC_DB::raw('g.goods_id'))
                    ->selectRaw("o.*, IF(o.product_id > 0, p.product_number, g.goods_number) AS storage,
                    o.goods_attr, g.suppliers_id, p.product_sn, g.goods_img, g.goods_sn as goods_sn")
                            ->where(RC_DB::raw('o.order_id'), $order_id)
                            ->get();
        
        if (!empty($data)) {
            foreach ($data as $key => $row) {
                $row['formated_subtotal']    = price_format($row['goods_price'] * $row['goods_number']);
                $row['formated_goods_price'] = price_format($row['goods_price']);
                $goods_list[]                = array(
                    'goods_name'   => $row['goods_name'],
                    'goods_number' => $row['goods_number'],
                    'goods_amount' => $row['goods_price'],
                );
            }
        }
        
        return $goods_list;
    }
    
    /**
     * 获取支付记录
     */
    protected function getPayRecord($order)
    {
        $record   = RC_DB::table('payment_record')->where('order_sn', $order['order_sn'])->where('trade_type', 'buy')->first();
        return $record['trade_no'] ?: $record['order_trade_no'];
    }
    
    
}