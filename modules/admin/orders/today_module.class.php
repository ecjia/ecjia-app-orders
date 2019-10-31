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
 * 商家今日订单列表
 * @author zrl
 *
 */
class admin_orders_today_module extends api_admin implements api_interface
{
    public function handleRequest(\Royalcms\Component\HttpKernel\Request $request)
    {
        $this->authadminSession();
        if ($_SESSION['staff_id'] <= 0) {
            return new ecjia_error(100, __('Invalid session', 'orders'));
        }
        $result = $this->admin_priv('order_view');

        if (is_ecjia_error($result)) {
            return $result;
        }

        $type              = $this->requestData('type');
        $time              = RC_Time::gmtime();
        $last_refresh_time = $this->requestData('last_refresh_time');

        if (empty($last_refresh_time)) {
            $last_refresh_time = RC_Time::local_date(ecjia::config('date_format'), $time);
        }

        $start_date        = RC_Time::local_strtotime(RC_Time::local_date(ecjia::config('date_format'), $time));
        $end_date          = $start_date + 86399;
        $last_refresh_time = RC_Time::local_strtotime($last_refresh_time);

        $size = $this->requestData('pagination.count', 15);
        $page = $this->requestData('pagination.page', 1);


        $order_query = RC_Loader::load_app_class('order_query', 'orders');
        $db          = RC_Loader::load_app_model('order_info_model', 'orders');
        $db_view     = RC_Loader::load_app_model('order_info_viewmodel', 'orders');

        $where         = array();
        $where1        = array();
        $has_new_order = 0;

        $where1['oi.add_time'] = array('egt' => $start_date, 'elt' => $end_date);
        if (!empty($last_refresh_time) && $type == 'payed') {
            //$where1['oi.pay_time'] = array('egt' => $last_refresh_time, 'elt'=> $time);

            $payment_id = RC_DB::table('payment')->where('pay_code', 'pay_cod')->value('pay_id');

            $payment_id = empty($payment_id) ? 0 : $payment_id;

            $where1[] = "((oi.pay_time >= " . $last_refresh_time . " AND oi.pay_time <= " . $time . ") OR oi.pay_id in (" . $payment_id . "))";
        }

        if (!empty($type)) {
            switch ($type) {
                case 'await_pay':
                    $where = $order_query->order_await_pay('oi.');
                    break;
                case 'payed' :
                    $where = $order_query->order_await_ship('oi.');
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
                    $where = array_merge($order_query->order_invalid('oi.'), $order_query->order_canceled('oi.'));
                    break;
            }
        }

        if (!empty($last_refresh_time) && $type == 'payed') {
            if (!empty($where1)) {
                $where = array_merge($where1, $where);
            }
        }

        $total_fee = "(oi.goods_amount + oi.tax + oi.shipping_fee + oi.insure_fee + oi.pay_fee + oi.pack_fee + oi.card_fee) as total_fee";
        $field     = 'oi.order_id, oi.order_sn, oi.integral, oi.consignee, oi.mobile, oi.tel, oi.order_status, oi.pay_status, oi.shipping_status, oi.pay_id, oi.pay_name, ' . $total_fee . ', oi.integral_money, oi.bonus, oi.shipping_fee, oi.discount, oi.money_paid, oi.surplus, oi.order_amount, oi.add_time, og.goods_number, og.goods_id,  og.goods_name, oi.store_id, g.goods_img, g.goods_thumb, g.original_img';

        $db_orderinfo_view = RC_Loader::load_app_model('order_info_viewmodel', 'orders');
        $result            = ecjia_app::validate_application('store');
        if (!is_ecjia_error($result)) {
            $db_orderinfo_view->view = array(
                'order_goods' => array(
                    'type'  => Component_Model_View::TYPE_LEFT_JOIN,
                    'alias' => 'og',
                    'on'    => 'oi.order_id = og.order_id'
                ),
                'goods'       => array(
                    'type'  => Component_Model_View::TYPE_LEFT_JOIN,
                    'alias' => 'g',
                    'on'    => 'g.goods_id = og.goods_id'
                ),
            );

            if (isset($_SESSION['store_id']) && $_SESSION['store_id'] > 0) {
                $where['oi.store_id'] = $_SESSION['store_id'];
            }

            /*获取记录条数*/
            $record_count = $db_orderinfo_view->join(array('order_goods'))->where($where)->count('DISTINCT oi.order_id');

            /*已付款刷新是否有新订单*/
            if (!empty($last_refresh_time) && ($type == 'payed')) {
                $has_new_order = 0;
                if (!empty($record_count)) {
                    $has_new_order = 1;
                }
                unset($where['0']);
                $record_count = $db_orderinfo_view->join(null)->where($where)->count('oi.order_id');
            }

            //实例化分页
            $page_row = new ecjia_page($record_count, $size, 6, '', $page);

            $order_id_group = $db_orderinfo_view->field('oi.order_id')->join(array('order_goods'))->where($where)->limit($page_row->limit())->order(array('oi.add_time' => 'desc'))->group('oi.order_id')->select();

            if (empty($order_id_group)) {
                $data = array();
            } else {
                foreach ($order_id_group as $val) {
                    $where['oi.order_id'][] = $val['order_id'];
                }
                $data = $db_orderinfo_view->field($field)->join(array('order_info', 'order_goods', 'goods'))->where($where)->order(array('oi.add_time' => 'desc'))->select();
            }

        } else {
            $db_orderinfo_view->view = array(
                'order_goods' => array(
                    'type'  => Component_Model_View::TYPE_LEFT_JOIN,
                    'alias' => 'og',
                    'on'    => 'oi.order_id = og.order_id'
                ),
                'goods'       => array(
                    'type'  => Component_Model_View::TYPE_LEFT_JOIN,
                    'alias' => 'g',
                    'on'    => 'g.goods_id = og.goods_id'
                ),
            );
            /*获取记录条数*/
            $record_count = $db_orderinfo_view->join(null)->where($where)->count('oi.order_id');
            /*已付款刷新是否有新订单*/
            if (!empty($last_refresh_time) && ($type == 'payed')) {
                $has_new_order = 0;
                if (!empty($record_count)) {
                    $has_new_order = 1;
                }
                unset($where['0']);
                $record_count = $db_orderinfo_view->join(null)->where($where)->count('oi.order_id');
            }
            //实例化分页
            $page_row = new ecjia_page($record_count, $size, 6, '', $page);

            $order_id_group = $db_orderinfo_view->join(null)->where($where)->order(array('oi.add_time' => 'desc'))->get_field('order_id', true);
            if (empty($order_id_group)) {
                $data = array();
            } else {
                $where['oi.order_id'] = $order_id_group;
                $data                 = $db_orderinfo_view->field($field)->join(array('order_goods', 'goods'))->where($where)->limit($page_row->limit())->order(array('oi.add_time' => 'desc'))->select();
            }
        }

        $order_list = array();
        if (!empty($data)) {
            $order_id    = $goods_number = 0;
            $seller_name = array();
            $ps          = array(
                PS_UNPAYED => __('未付款', 'orders'),
                PS_PAYING  => __('付款中', 'orders'),
                PS_PAYED   => __('已付款', 'orders'),
            );
            $ss          = array(
                SS_UNSHIPPED    => __('未发货', 'orders'),
                SS_PREPARING    => __('配货中', 'orders'),
                SS_SHIPPED      => __('已发货', 'orders'),
                SS_RECEIVED     => __('收货确认', 'orders'),
                SS_SHIPPED_PART => __('已发货(部分商品)', 'orders'),
                SS_SHIPPED_ING  => __('发货中', 'orders'),
            );

            foreach ($data as $val) {
                if ($order_id == 0 || $val['order_id'] != $order_id) {
                    $goods_number = 0;
                    $order_status = ($val['order_status'] != '2' || $val['order_status'] != '3') ? RC_Lang::lang('os/' . $val['order_status']) : '';
                    $order_status = $val['order_status'] == '2' ? __('已取消', 'orders') : $order_status;
                    $order_status = $val['order_status'] == '3' ? __('无效', 'orders') : $order_status;

// 					$payment_method = RC_Loader::load_app_class('payment_method', 'payment');
                    if ($val['pay_id'] > 0) {
                        $payment = with(new Ecjia\App\Payment\PaymentPlugin)->getPluginDataById($val['pay_id']);
                    }

                    $goods_lists   = array();
                    $goods_lists[] = array(
                        'goods_id'     => $val['goods_id'],
                        'name'         => $val['goods_name'],
                        'goods_number' => $val['goods_number'],
                        'img'          => array(
                            'thumb' => !empty($val['goods_img']) ? RC_Upload::upload_url($val['goods_img']) : RC_Uri::admin_url('statics/images/nopic.png'),
                            'url'   => !empty($val['original_img']) ? RC_Upload::upload_url($val['original_img']) : RC_Uri::admin_url('statics/images/nopic.png'),
                            'small' => !empty($val['goods_thumb']) ? RC_Upload::upload_url($val['goods_thumb']) : RC_Uri::admin_url('statics/images/nopic.png')
                        )
                    );

                    $label_info         = $this->label_order_status($val['order_status'], $val['shipping_status'], $val['pay_status'], $type, $payment['is_cod']);
                    $label_order_status = $label_info['label_order_status'];
                    $status_code        = $label_info['status_code'];

                    $goods_number                 = $val['goods_number'];
                    $order_list[$val['order_id']] = array(
                        'order_id'                => intval($val['order_id']),
                        'order_sn'                => $val['order_sn'],
                        'total_fee'               => $val['total_fee'],
                        'pay_name'                => $val['pay_name'],
                        'consignee'               => $val['consignee'],
                        'mobile'                  => empty($val['mobile']) ? $val['tel'] : $val['mobile'],
                        'formated_total_fee'      => price_format($val['total_fee'], false),
                        'order_amount'            => $val['order_amount'],
                        'surplus'                 => $val['surplus'],
                        'money_paid'              => $val['money_paid'],
                        'formated_order_amount'   => price_format($val['order_amount'], false),
                        'formated_surplus'        => price_format($val['surplus'], false),
                        'formated_money_paid'     => price_format($val['money_paid'], false),
                        'formated_integral_money' => price_format($val['integral_money'], false),
                        'integral'                => intval($val['integral']),
                        'formated_bonus'          => price_format($val['bonus'], false),
                        'formated_shipping_fee'   => price_format($val['shipping_fee'], false),
                        'formated_discount'       => price_format($val['discount'], false),
                        'status'                  => $order_status . ',' . $ps[$val['pay_status']] . ',' . $ss[$val['shipping_status']],
                        'label_order_status'      => $label_order_status,
                        'order_status_code'       => $status_code,
                        'goods_number'            => intval($goods_number),
                        'create_time'             => RC_Time::local_date(ecjia::config('date_format'), $val['add_time']),
                        'goods_items'             => $goods_lists
                    );

                    $order_id = $val['order_id'];
                } else {
                    $goods_number                                  += $val['goods_number'];
                    $order_list[$val['order_id']]['goods_number']  = $goods_number;
                    $order_list[$val['order_id']]['goods_items'][] = array(
                        'goods_id'     => $val['goods_id'],
                        'name'         => $val['goods_name'],
                        'goods_number' => intval($val['goods_number']),
                        'img'          => array(
                            'thumb' => !empty($val['goods_img']) ? RC_Upload::upload_url($val['goods_img']) : RC_Uri::admin_url('statics/images/nopic.png'),
                            'url'   => !empty($val['original_img']) ? RC_Upload::upload_url($val['original_img']) : RC_Uri::admin_url('statics/images/nopic.png'),
                            'small' => !empty($val['goods_thumb']) ? RC_Upload::upload_url($val['goods_thumb']) : RC_Uri::admin_url('statics/images/nopic.png')
                        )
                    );
                }
            }
        }
        $order_list = array_merge($order_list);

        $pager = array(
            'total' => $page_row->total_records,
            'count' => $page_row->total_records,
            'more'  => $page_row->total_pages <= $page ? 0 : 1,
        );

        return array('data' => array('order_list' => $order_list, 'has_new_order' => $has_new_order), 'pager' => $pager);
    }

    /**
     * 订单状态
     */
    private function label_order_status($order_status, $shipping_status, $pay_status, $type, $is_cod)
    {
        $label_order_status = '';
        $status_code        = '';
        if (in_array($order_status, array(OS_CONFIRMED, OS_SPLITED)) && in_array($shipping_status, array(SS_RECEIVED)) && in_array($pay_status, array(PS_PAYED, PS_PAYING))) {
            $label_order_status = __('已完成', 'orders');
            $status_code        = 'finished';
        } elseif (in_array($shipping_status, array(SS_SHIPPED))) {
            $label_order_status = __(__('已发货', 'orders'), 'orders');
            $status_code        = 'shipped';
        } elseif (in_array($order_status, array(OS_CONFIRMED, OS_SPLITED, OS_UNCONFIRMED)) && in_array($pay_status, array(PS_UNPAYED)) && (in_array($shipping_status, array(SS_SHIPPED, SS_RECEIVED)) || !$is_cod)) {
            $label_order_status = __('待付款', 'orders');
            $status_code        = 'await_pay';
        } elseif (in_array($order_status, array(OS_UNCONFIRMED, OS_CONFIRMED, OS_SPLITED, OS_SPLITING_PART)) && in_array($shipping_status, array(SS_UNSHIPPED, SS_SHIPPED_PART, SS_PREPARING, SS_SHIPPED_ING, OS_SHIPPED_PART)) && (in_array($pay_status, array(PS_PAYED, PS_PAYING)) || $is_cod)) {
            if (!in_array($pay_status, array(PS_PAYED)) && $type == 'payed') {
                //continue;
            }
            $label_order_status = __('待发货', 'orders');
            $status_code        = 'await_ship';
        } elseif (in_array($order_status, array(OS_CANCELED))) {
            $label_order_status = __('已关闭', 'orders');
            $status_code        = 'canceled';
        }

        return array('label_order_status' => $label_order_status, 'status_code' => $status_code);
    }

}


// end