<?php
//
//    ______         ______           __         __         ______
//   /\  ___\       /\  ___\         /\_\       /\_\       /\  __ \
//   \/\  __\       \/\ \____        \/\_\      \/\_\      \/\ \_\ \
//    \/\_____\      \/\_____\     /\_\/\_\      \/\_\      \/\_\ \_\
//     \/_____/       \/_____/     \/__\/_/       \/_/       \/_/ /_/
//
//   上海商创网络科技有限公司
//
//  ---------------------------------------------------------------------------------
//
//   一、协议的许可和权利
//
//    1. 您可以在完全遵守本协议的基础上，将本软件应用于商业用途；
//    2. 您可以在协议规定的约束和限制范围内修改本产品源代码或界面风格以适应您的要求；
//    3. 您拥有使用本产品中的全部内容资料、商品信息及其他信息的所有权，并独立承担与其内容相关的
//       法律义务；
//    4. 获得商业授权之后，您可以将本软件应用于商业用途，自授权时刻起，在技术支持期限内拥有通过
//       指定的方式获得指定范围内的技术支持服务；
//
//   二、协议的约束和限制
//
//    1. 未获商业授权之前，禁止将本软件用于商业用途（包括但不限于企业法人经营的产品、经营性产品
//       以及以盈利为目的或实现盈利产品）；
//    2. 未获商业授权之前，禁止在本产品的整体或在任何部分基础上发展任何派生版本、修改版本或第三
//       方版本用于重新开发；
//    3. 如果您未能遵守本协议的条款，您的授权将被终止，所被许可的权利将被收回并承担相应法律责任；
//
//   三、有限担保和免责声明
//
//    1. 本软件及所附带的文件是作为不提供任何明确的或隐含的赔偿或担保的形式提供的；
//    2. 用户出于自愿而使用本软件，您必须了解使用本软件的风险，在尚未获得商业授权之前，我们不承
//       诺提供任何形式的技术支持、使用担保，也不承担任何因使用本软件而产生问题的相关责任；
//    3. 上海商创网络科技有限公司不对使用本产品构建的商城中的内容信息承担责任，但在不侵犯用户隐
//       私信息的前提下，保留以任何方式获取用户信息及商品信息的权利；
//
//   有关本产品最终用户授权协议、商业授权与技术服务的详细内容，均由上海商创网络科技有限公司独家
//   提供。上海商创网络科技有限公司拥有在不事先通知的情况下，修改授权协议的权力，修改后的协议对
//   改变之日起的新授权用户生效。电子文本形式的授权协议如同双方书面签署的协议一样，具有完全的和
//   等同的法律效力。您一旦开始修改、安装或使用本产品，即被视为完全理解并接受本协议的各项条款，
//   在享有上述条款授予的权力的同时，受到相关的约束和限制。协议许可范围以外的行为，将直接违反本
//   授权协议并构成侵权，我们有权随时终止授权，责令停止损害，并保留追究相关责任的权力。
//
//  ---------------------------------------------------------------------------------
//
defined('IN_ECJIA') or exit('No permission resources.');

/**
 * 打印订单
 */
class mh_print extends ecjia_merchant
{
    public function init()
    {
        $this->admin_priv('mh_orders_order_print', ecjia::MSGTYPE_JSON);

        $order_id = intval($_GET['order_id']);
        $order    = RC_Api::api('orders', 'order_info', array('order_id' => $order_id, 'store_id' => $_SESSION['store_id']));

        $type          = 'print_buy_orders';
        $shipping_data = ecjia_shipping::getPluginDataById($order['shipping_id']);
        if ($shipping_data['shipping_code'] == 'ship_o2o_express') {
            $type = 'print_takeaway_orders';
        }

        $store_info     = RC_DB::table('store_franchisee')->where('store_id', $_SESSION['store_id'])->first();
        $contact_mobile = RC_DB::table('merchants_config')->where('store_id', $_SESSION['store_id'])->where('code', 'shop_kf_mobile')->pluck('value');

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

        $order_trade_no   = RC_DB::table('payment_record')->where('order_sn', 'LIKE', '%' . mysql_like_quote($order['order_sn']) . '%')->where('trade_type', 'buy')->pluck('trade_no');
        $integral_balance = RC_DB::table('users')->where('user_id', $order['user_id'])->pluck('pay_points');

        RC_Loader::load_app_func('admin_order', 'orders');
        $integral      = integral_to_give($order);
        $integral_give = !empty($integral['custom_points']) ? $integral['custom_points'] : 0;

        /* 取得用户名 */
        if ($order['user_id'] > 0) {
            $user = RC_Api::api('user', 'user_info', array('user_id' => $order['user_id']));
            if (!empty($user)) {
                $order['user_name'] = $user['user_name'];
            }
        } else {
            $order['user_name'] = '匿名用户';
        }

        if ($type == 'print_buy_orders') {
            $data = array(
                'order_sn'            => $order['order_sn'], //订单编号
                'order_trade_no'      => $order_trade_no, //流水编号
                'user_name'           => $order['user_name'], //会员账号
                'purchase_time'       => RC_Time::local_date('Y-m-d H:i:s', $order['add_time']), //下单时间

                'goods_lists'         => $goods_list,
                'goods_subtotal'      => $order['goods_amount'], //商品总计

                'integral_money'      => '-' . $order['integral_money'],
                'integral_give'       => $integral_give, //获得积分
                'integral_balance'    => $integral_balance, //积分余额
                'favourable_discount' => '-' . $order['discount'], //满减满折
                'bonus_discount'      => '-' . $order['bonus'], //红包折扣

                'shipping_fee'        => $order['shipping_fee'],
                'receivables'         => $order['total_fee'], //应收金额
                'order_amount'        => $order['money_paid'], //实收金额
                'payment'             => $order['pay_name'],

                'order_remarks'       => $order['postscript'],
                'qrcode'              => $order['order_sn'],
            );
        } elseif ($type == 'print_takeaway_orders') {
            $address = '';
            if (!empty($order['province'])) {
                $address .= ecjia_region::getRegionName($order['province']);
            }
            if (!empty($order['city'])) {
                $address .= ecjia_region::getRegionName($order['city']);
            }
            if (!empty($order['district'])) {
                $address .= ecjia_region::getRegionName($order['district']);
            }
            if (!empty($order['street'])) {
                $address .= ecjia_region::getRegionName($order['street']);
            }
            if (!empty($address)) {
                $address .= ' ';
            }
            $address .= $order['address'];

            $data = array(
                'order_sn'             => $order['order_sn'], //订单编号
                'order_trade_no'       => $order_trade_no, //流水编号
                'payment'              => $order['pay_name'], //支付方式
                'pay_status'           => RC_Lang::get('orders::order.ps.' . $order['pay_status']), //支付状态
                'purchase_time'        => RC_Time::local_date('Y-m-d H:i:s', $order['add_time']), //下单时间
                'expect_shipping_time' => RC_Time::local_date('Y-m-d H:i:s', $order['expect_shipping_time']), //期望送达时间
                'integral_money'       => $order['integral_money'], //积分抵扣

                'integral_balance'     => $integral_balance, //积分余额
                'integral_give'        => $integral_give, //获得积分
                'shipping_fee'         => $order['shipping_fee'],

                'receivables'          => $order['total_fee'], //应收金额
                'favourable_discount'  => '-' . $order['discount'], //满减满折
                'bonus_discount'       => '-' . $order['bonus'], //红包折扣

                'order_amount'         => $order['money_paid'], //实收金额
                'order_remarks'        => $order['postscript'],
                'consignee_address'    => $address,
                'consignee_name'       => $order['consignee'],
                'consignee_mobile'     => $order['mobile'],
                'goods_lists'          => $goods_list,
                'goods_subtotal'       => $order['goods_amount'], //商品总计
                'qrcode'               => $order['order_sn'],
            );
        }

        $data['order_type']      = 'buy';
        $data['merchant_name']   = $store_info['merchants_name'];
        $data['merchant_mobile'] = $contact_mobile;

        $result = RC_Api::api('printer', 'send_event_print', [
            'store_id' => $_SESSION['store_id'],
            'event'    => $type,
            'value'    => $data,
        ]);
        if (is_ecjia_error($result)) {
            return $this->showmessage($result->get_error_message(), ecjia::MSGTYPE_JSON | ecjia::MSGSTAT_ERROR);
        }
        return $this->showmessage('打印已发送', ecjia::MSGTYPE_JSON | ecjia::MSGSTAT_SUCCESS, array('pjaxurl' => RC_Uri::url('orders/merchant/info', array('order_id' => $order_id))));
    }
}

//end
