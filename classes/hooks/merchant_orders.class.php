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

class orders_merchant_plugin
{

    //订单统计
    public static function merchant_dashboard_left_8_1()
    {
        //当前时间戳
        $now = RC_Time::gmtime();

        //本月开始时间
        $start_month = RC_Time::local_mktime(0, 0, 0, RC_Time::local_date('m'), 1, RC_Time::local_date('Y'));

        RC_Loader::load_app_class('merchant_order_list', 'orders', false);
        $order = new merchant_order_list();

        $order_money = RC_DB::table('order_info as o')
            ->leftJoin('order_goods as og', RC_DB::raw('o.order_id'), '=', RC_DB::raw('og.order_id'))
            ->selectRaw("(" . $order->order_amount_field('o.') . ") AS order_amount")
            ->where(RC_DB::raw('o.store_id'), $_SESSION['store_id'])
            ->where(RC_DB::raw('o.add_time'), '>=', $start_month)
            ->where(RC_DB::raw('o.add_time'), '<=', $now)
            ->where(RC_DB::raw('o.is_delete'), 0)
            ->whereIn(RC_DB::raw('o.order_status'), array(OS_CONFIRMED, OS_SPLITED))
            ->whereIn(RC_DB::raw('o.shipping_status'), array(SS_SHIPPED, SS_RECEIVED))
            ->whereIn(RC_DB::raw('o.pay_status'), array(PS_PAYING, PS_PAYED))
            ->groupBy(RC_DB::raw('o.order_id'))
            ->get();

        //本月订单总额
        $num = 0;
        if (!empty($order_money)) {
            foreach ($order_money as $val) {
                $num += $val['order_amount'];
            }
            $num = price_format($num);
        }

        //本月订单数量
        $order_number = RC_DB::table('order_info')
            ->where('store_id', $_SESSION['store_id'])
            ->where('add_time', '>=', $start_month)
            ->where('is_delete', 0)
            ->count(RC_DB::raw('distinct order_id'));

        //今日开始时间
        $start_time = RC_Time::local_mktime(0, 0, 0, RC_Time::local_date('m'), RC_Time::local_date('d'), RC_Time::local_date('Y'));

        //今日待确认订单
        $order_unconfirmed = RC_DB::table('order_info as oi')
            ->leftJoin('order_goods as g', RC_DB::raw('oi.order_id'), '=', RC_DB::raw('g.order_id'))
            ->select(RC_DB::raw('oi.order_id'))
            ->where(RC_DB::raw('oi.store_id'), $_SESSION['store_id'])->where(RC_DB::raw('oi.order_status'), 0)
            ->where(RC_DB::raw('oi.add_time'), '>=', $start_time)->where(RC_DB::raw('oi.add_time'), '<=', $now)
            ->where(RC_DB::raw('oi.is_delete'), 0)
            ->groupBy(RC_DB::raw('oi.order_id'))->get();
        $order_unconfirmed = count($order_unconfirmed);

        $db_order_info = RC_DB::table('order_info as o');

        $payment_method = RC_Loader::load_app_class('payment_method', 'payment');
        $payment_id_row = $payment_method->payment_id_list(true);
        $payment_id = "";
        foreach ($payment_id_row as $v) {
            $payment_id .= empty($payment_id) ? $v : ',' . $v;
        }
        $payment_id = empty($payment_id) ? "''" : $payment_id;

        $db_order_info->whereIn(RC_DB::raw($alias . 'order_status'), array(OS_UNCONFIRMED, OS_CONFIRMED, OS_SPLITED, OS_SPLITING_PART));
        $db_order_info->whereIn(RC_DB::raw($alias . 'shipping_status'), array(SS_UNSHIPPED, SS_PREPARING, SS_SHIPPED_ING));
        $db_order_info->whereRaw("( {$alias}pay_status in (" . PS_PAYED . "," . PS_PAYING . ") OR {$alias}pay_id in (" . $payment_id . "))");

        //今日待发货订单
        $order_await_ship = $db_order_info
            ->leftJoin('order_goods as g', RC_DB::raw('o.order_id'), '=', RC_DB::raw('g.order_id'))
            ->select(RC_DB::raw('o.order_id'))
            ->where(RC_DB::raw('o.store_id'), $_SESSION['store_id'])->where(RC_DB::raw('o.order_status'), 0)
            ->where(RC_DB::raw('o.add_time'), '>=', $start_time)->where(RC_DB::raw('o.add_time'), '<=', $now)
            ->where(RC_DB::raw('o.is_delete'), 0)
            ->groupBy(RC_DB::raw('o.order_id'))->get();
        $order_await_ship = count($order_await_ship);

        ecjia_admin::$controller->assign('order_money', $num);
        ecjia_admin::$controller->assign('order_number', $order_number);
        ecjia_admin::$controller->assign('order_unconfirmed', $order_unconfirmed);
        ecjia_admin::$controller->assign('order_await_ship', $order_await_ship);

        ecjia_admin::$controller->assign('month_start_time', RC_Time::local_date('Y-m-d', $start_month)); //本月开始时间
        ecjia_admin::$controller->assign('month_end_time', RC_Time::local_date('Y-m-d', $now)); //本月结束时间

        ecjia_admin::$controller->assign('today_start_time', RC_Time::local_date('Y-m-d H:i:s', $start_time)); //今天开始时间
        ecjia_admin::$controller->assign('today_end_time', RC_Time::local_date('Y-m-d H:i:s', $start_time + 24 * 3600 - 1)); //今天结束时间
        ecjia_admin::$controller->assign('wait_ship', CS_AWAIT_SHIP); //待发货
        ecjia_admin::$controller->assign('unconfirmed', OS_UNCONFIRMED); //待确认

        ecjia_merchant::$controller->display(
            RC_Package::package('app::orders')->loadTemplate('merchant/library/widget_merchant_dashboard_overview.lbi', true)
        );
    }

    //店铺首页 店铺资金 订单统计类型 平台配送 商家配送 促销活动 商品热卖榜
    public static function merchant_dashboard_left_8_2()
    {
    	//店铺资金
    	$data = RC_DB::table('store_account')->where('store_id', $_SESSION['store_id'])->first();
    	if (empty($data)) {
    		$data['formated_amount_available'] = $data['formated_money'] = $data['formated_frozen_money'] = $data['formated_deposit'] = '￥0.00';
    		$data['amount_available'] = $data['money'] = $data['frozen_money'] = $data['deposit'] = '0.00';
    	} else {
    		$amount_available = $data['money'] - $data['deposit']; //可用余额=money-保证金
    		$data['formated_amount_available'] = price_format($amount_available);
    		$data['amount_available'] = $amount_available;
    
    		$money = $data['money'] + $data['frozen_money']; //总金额=money+冻结
    		$data['formated_money'] = price_format($money);
    		$data['money'] = $money;
    
    		$data['formated_frozen_money'] = price_format($data['frozen_money']);
    		$data['formated_deposit'] = price_format($data['deposit']);
    	}
    	ecjia_merchant::$controller->assign('data', $data);
    
    	ecjia_merchant::$controller->display(
    	RC_Package::package('app::orders')->loadTemplate('merchant/library/widget_merchant_dashboard_commission.lbi', true)
    	);
    }
    
    //订单走势图
    public static function merchant_dashboard_left_8_3()
    {
        if (!isset($_SESSION['store_id']) || $_SESSION['store_id'] === '') {
            $count_list = array();
        } else {
            $cache_key = 'order_bar_chart_' . md5($_SESSION['store_id']);
            $count_list = RC_Cache::app_cache_get($cache_key, 'order');

            if (!$count_list) {
                $format = '%Y-%m-%d';
                $time = (RC_Time::local_mktime(0, 0, 0, RC_Time::local_date('m'), RC_Time::local_date('d'), RC_Time::local_date('Y')) - 1);
                $start_time = $time - 30 * 86400;
                $store_id = $_SESSION['store_id'];

                $where = "add_time >= '$start_time' AND add_time <= '$time' AND store_id = $store_id AND is_delete = 0";

                $list = RC_DB::table('order_info')
                    ->selectRaw("FROM_UNIXTIME(add_time+8*3600, '" . $format . "') AS day, count('order_id') AS count")
                    ->whereRaw($where)
                    ->groupby('day')
                    ->get();

                $days = $data = $count_list = array();

                for ($i = 30; $i > 0; $i--) {
                    $days[] = RC_Time::local_date("Y-m-d", RC_Time::local_strtotime(' -' . $i . 'day'));
                }

                $max_count = 100;
                if (!empty($list)) {
                    foreach ($list as $k => $v) {
                        $data[$v['day']] = $v['count'];
                    }
                }

                foreach ($days as $k => $v) {
                    if (!array_key_exists($v, $data)) {
                        $count_list[$v] = 0;
                    } else {
                        $count_list[$v] = $data[$v];
                    }
                }

                $tmp_day = '';
                $tmp_count = '';
                foreach ($count_list as $k => $v) {
                    $k = intval(date('d', strtotime($k)));
                    $tmp_day .= "'$k',";
                    $tmp_count .= "$v,";
                }

                $tmp_day = rtrim($tmp_day, ',');
                $tmp_count = rtrim($tmp_count, ',');
                $count_list['day'] = $tmp_day;
                $count_list['count'] = $tmp_count;

                RC_Cache::app_cache_set($cache_key, $count_list, 'order', 60 * 24); //24小时缓存
            }
        }
        ecjia_merchant::$controller->assign('order_arr', $count_list);
        ecjia_merchant::$controller->display(
            RC_Package::package('app::orders')->loadTemplate('merchant/library/widget_merchant_dashboard_bar_chart.lbi', true)
        );
    }

    public static function orders_stats_admin_menu_api($menus)
    {
        $menu = array(
            2 => ecjia_merchant::make_admin_menu('02_order_stats', __('订单统计'), RC_Uri::url('orders/mh_order_stats/init'), 2)->add_purview('order_stats')->add_icon('fa-bar-chart-o')->add_base('stats'),
            3 => ecjia_merchant::make_admin_menu('03_sale_general', __('销售概况'), RC_Uri::url('orders/mh_sale_general/init'), 3)->add_purview('sale_general_stats')->add_icon('fa-bar-chart-o')->add_base('stats'),
            4 => ecjia_merchant::make_admin_menu('04_sale_list', __('销售明细'), RC_Uri::url('orders/mh_sale_list/init'), 4)->add_purview('sale_list_stats')->add_icon('fa-list')->add_base('stats'),
            5 => ecjia_merchant::make_admin_menu('05_sale_order', __('销售排行'), RC_Uri::url('orders/mh_sale_order/init'), 5)->add_purview('sale_order_stats')->add_icon('fa-trophy')->add_base('stats'),
        );
        $menus->add_submenu($menu);
        return $menus;
    }

}

RC_Hook::add_action('merchant_dashboard_left8', array('orders_merchant_plugin', 'merchant_dashboard_left_8_1'), 1);
RC_Hook::add_action('merchant_dashboard_left8', array('orders_merchant_plugin', 'merchant_dashboard_left_8_2'), 2);
RC_Hook::add_action('merchant_dashboard_left8', array('orders_merchant_plugin', 'merchant_dashboard_left_8_3'), 3);

RC_Hook::add_filter('stats_merchant_menu_api', array('orders_merchant_plugin', 'orders_stats_admin_menu_api'));

// end
