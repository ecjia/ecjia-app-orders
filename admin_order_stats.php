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
 * 订单统计
 */
class admin_order_stats extends ecjia_admin
{
    public function __construct()
    {
        parent::__construct();

        RC_Loader::load_app_func('global', 'orders');
        /* 加载所有全局 js/css */
        RC_Script::enqueue_script('bootstrap-placeholder');
        RC_Script::enqueue_script('jquery-validate');
        RC_Script::enqueue_script('jquery-form');
        RC_Script::enqueue_script('smoke');
        RC_Script::enqueue_script('jquery-chosen');
        RC_Style::enqueue_style('chosen');
        RC_Script::enqueue_script('jquery-uniform');
        RC_Style::enqueue_style('uniform-aristo');

        //时间控件
        RC_Script::enqueue_script('bootstrap-datepicker', RC_Uri::admin_url('statics/lib/datepicker/bootstrap-datepicker.min.js'));
        RC_Style::enqueue_style('datepicker', RC_Uri::admin_url('statics/lib/datepicker/datepicker.css'));

        //百度图表
        RC_Script::enqueue_script('echarts-min-js', RC_App::apps_url('statics/js/echarts.min.js', __FILE__));

        RC_Script::enqueue_script('order_stats', RC_App::apps_url('statics/js/order_stats.js', __FILE__));
        RC_Script::enqueue_script('order_stats_chart', RC_App::apps_url('statics/js/order_stats_chart.js', __FILE__));
        RC_Style::enqueue_style('orders-css', RC_App::apps_url('statics/css/admin_orders.css', __FILE__));

        RC_Script::localize_script('order_stats', 'js_lang', RC_Lang::get('orders::statistic.js_lang'));
        RC_Script::localize_script('order_stats_chart', 'js_lang', RC_Lang::get('orders::statistic.js_lang'));
    }

    /**
     * 列表
     */
    public function init()
    {
        $this->admin_priv('order_stats');

        ecjia_screen::get_current_screen()->add_nav_here(new admin_nav_here(RC_Lang::get('orders::statistic.order_stats')));
        ecjia_screen::get_current_screen()->add_help_tab(array(
            'id' => 'overview',
            'title' => RC_Lang::get('orders::statistic.overview'),
            'content' => '<p>' . RC_Lang::get('orders::statistic.order_stats_help') . '</p>',
        ));

        ecjia_screen::get_current_screen()->set_help_sidebar(
            '<p><strong>' . RC_Lang::get('orders::statistic.more_info') . '</strong></p>' .
            '<p>' . __('<a href="https://ecjia.com/wiki/帮助:ECJia智能后台:订单统计#.E8.AE.A2.E5.8D.95.E6.A6.82.E5.86.B5" target="_blank">' . RC_Lang::get('orders::statistic.about_order_stats') . '</a>') . '</p>'
        );

        $this->assign('ur_here', RC_Lang::get('orders::statistic.order_stats'));
        $this->assign('action_link', array('text' => RC_Lang::get('orders::statistic.down_order_statistics'), 'href' => RC_Uri::url('orders/admin_order_stats/download')));

        $list = $this->get_merchant_list();
        $this->assign('list', $list);

        if (!empty($list['stats_data'])) {
            $this->assign('data', json_encode($list['stats_data']));
        }
        $stats = !empty($_GET['stats']) ? trim($_GET['stats']) : 'valid_amount';
        $this->assign('stats', $stats);

        $this->display('order_stats_list.dwt');
    }

    /**
     * 订单统计 - 订单概况
     */
    public function stats()
    {
        $this->admin_priv('order_stats');

        ecjia_screen::get_current_screen()->add_nav_here(new admin_nav_here(RC_Lang::get('orders::statistic.order_stats')));
        ecjia_screen::get_current_screen()->add_help_tab(array(
            'id' => 'overview',
            'title' => RC_Lang::get('orders::statistic.overview'),
            'content' => '<p>' . RC_Lang::get('orders::statistic.order_stats_help') . '</p>',
        ));

        ecjia_screen::get_current_screen()->set_help_sidebar(
            '<p><strong>' . RC_Lang::get('orders::statistic.more_info') . '</strong></p>' .
            '<p>' . __('<a href="https://ecjia.com/wiki/帮助:ECJia智能后台:订单统计#.E8.AE.A2.E5.8D.95.E6.A6.82.E5.86.B5" target="_blank">' . RC_Lang::get('orders::statistic.about_order_stats') . '</a>') . '</p>'
        );

        $store_id = intval($_GET['store_id']);
        $store_info = RC_Api::api('store', 'store_info', array('store_id' => $store_id));
        if (empty($store_info)) {
            return $this->showmessage('该店铺不存在', ecjia::MSGTYPE_HTML | ecjia::MSGSTAT_ERROR, array('links' => array(array('text' => '订单统计', 'href' => RC_Uri::url('orders/admin_order_stats/init')))));
        }

        $this->assign('ur_here', $store_info['merchants_name'] . ' - ' . RC_Lang::get('orders::statistic.order_stats'));
        $this->assign('action_link', array('text' => RC_Lang::get('orders::statistic.down_order_statistics'), 'href' => RC_Uri::url('orders/admin_order_stats/download')));

        //获取订单统计信息
        $order_stats = $this->get_order_stats($store_id);
        $this->assign('order_stats', $order_stats);
        $this->assign('order_stats_json', json_encode($order_stats['type']));

        $current_year = RC_Time::local_date('Y', RC_Time::gmtime());
        $year_list = [];
        for ($i = 0; $i < 6; $i++) {
            $year_list[] = ($current_year - $i);
        }
        $month_list = [];
        for ($i = 12; $i > 0; $i--) {
            $month_list[] = $i;
        }
        $year = !empty($_GET['year']) ? intval($_GET['year']) : $current_year;
        $month = !empty($_GET['month']) ? intval($_GET['month']) : 0;

        $this->assign('store_id', $store_id);
        $this->assign('year_list', $year_list);
        $this->assign('month_list', $month_list);
        $this->assign('year', $year);
        $this->assign('month', $month);
        $this->assign('page', 'init');

        $this->assign('form_action', RC_Uri::url('orders/admin_order_stats/stats', array('store_id' => $store_id)));

        $data = $this->get_order_general($store_id);
        $this->assign('data', $data);

        $this->display('order_stats.dwt');
    }

    /**
     * 订单统计 - 配送方式
     */
    public function shipping_status()
    {
        $this->admin_priv('order_stats');

        ecjia_screen::get_current_screen()->add_nav_here(new admin_nav_here(RC_Lang::get('orders::statistic.order_stats')));
        ecjia_screen::get_current_screen()->add_help_tab(array(
            'id' => 'overview',
            'title' => RC_Lang::get('orders::statistic.overview'),
            'content' => '<p>' . RC_Lang::get('orders::statistic.order_stats_help') . '</p>',
        ));

        ecjia_screen::get_current_screen()->set_help_sidebar(
            '<p><strong>' . RC_Lang::get('orders::statistic.more_info') . '</strong></p>' .
            '<p>' . __('<a href="https://ecjia.com/wiki/帮助:ECJia智能后台:订单统计#.E9.85.8D.E9.80.81.E6.96.B9.E5.BC.8F" target="_blank">' . RC_Lang::get('orders::statistic.about_order_stats') . '</a>') . '</p>'
        );

        $store_id = intval($_GET['store_id']);
        $store_info = RC_Api::api('store', 'store_info', array('store_id' => $store_id));
        if (empty($store_info)) {
            return $this->showmessage('该店铺不存在', ecjia::MSGTYPE_HTML | ecjia::MSGSTAT_ERROR, array('links' => array(array('text' => '订单统计', 'href' => RC_Uri::url('orders/admin_order_stats/init')))));
        }

        $this->assign('ur_here', $store_info['merchants_name'] . ' - ' . RC_Lang::get('orders::statistic.order_stats'));
        $this->assign('action_link', array('text' => RC_Lang::get('orders::statistic.down_order_statistics'), 'href' => RC_Uri::url('orders/admin_order_stats/download')));

        //获取订单统计信息
        $order_stats = $this->get_order_stats($store_id);
        $this->assign('order_stats', $order_stats);
        $this->assign('order_stats_json', json_encode($order_stats['type']));

        $current_year = RC_Time::local_date('Y', RC_Time::gmtime());
        $year_list = [];
        for ($i = 0; $i < 6; $i++) {
            $year_list[] = ($current_year - $i);
        }
        $month_list = [];
        for ($i = 12; $i > 0; $i--) {
            $month_list[] = $i;
        }
        $year = !empty($_GET['year']) ? intval($_GET['year']) : $current_year;
        $month = !empty($_GET['month']) ? intval($_GET['month']) : 0;

        $this->assign('store_id', $store_id);
        $this->assign('year_list', $year_list);
        $this->assign('month_list', $month_list);
        $this->assign('year', $year);
        $this->assign('month', $month);
        $this->assign('page', 'shipping_status');

        $this->assign('form_action', RC_Uri::url('orders/admin_order_stats/shipping_status', array('store_id' => $store_id)));

        $data = $this->get_ship_status($store_id);
        $this->assign('data', $data);

        $this->display('order_stats.dwt');
    }

    /**
     * 订单统计 - 支付方式
     */
    public function pay_status()
    {
        $this->admin_priv('order_stats');

        ecjia_screen::get_current_screen()->add_nav_here(new admin_nav_here(RC_Lang::get('orders::statistic.order_stats')));
        ecjia_screen::get_current_screen()->add_help_tab(array(
            'id' => 'overview',
            'title' => RC_Lang::get('orders::statistic.overview'),
            'content' => '<p>' . RC_Lang::get('orders::statistic.order_stats_help') . '</p>',
        ));

        ecjia_screen::get_current_screen()->set_help_sidebar(
            '<p><strong>' . RC_Lang::get('orders::statistic.more_info') . '</strong></p>' .
            '<p>' . __('<a href="https://ecjia.com/wiki/帮助:ECJia智能后台:订单统计#.E6.94.AF.E4.BB.98.E6.96.B9.E5.BC.8F" target="_blank">' . RC_Lang::get('orders::statistic.about_order_stats') . '</a>') . '</p>'
        );

        $store_id = intval($_GET['store_id']);
        $store_info = RC_Api::api('store', 'store_info', array('store_id' => $store_id));
        if (empty($store_info)) {
            return $this->showmessage('该店铺不存在', ecjia::MSGTYPE_HTML | ecjia::MSGSTAT_ERROR, array('links' => array(array('text' => '订单统计', 'href' => RC_Uri::url('orders/admin_order_stats/init')))));
        }

        $this->assign('ur_here', $store_info['merchants_name'] . ' - ' . RC_Lang::get('orders::statistic.order_stats'));
        $this->assign('action_link', array('text' => RC_Lang::get('orders::statistic.down_order_statistics'), 'href' => RC_Uri::url('orders/admin_order_stats/download')));

        //获取订单统计信息
        $order_stats = $this->get_order_stats($store_id);
        $this->assign('order_stats', $order_stats);
        $this->assign('order_stats_json', json_encode($order_stats['type']));

        $current_year = RC_Time::local_date('Y', RC_Time::gmtime());
        $year_list = [];
        for ($i = 0; $i < 6; $i++) {
            $year_list[] = ($current_year - $i);
        }
        $month_list = [];
        for ($i = 12; $i > 0; $i--) {
            $month_list[] = $i;
        }
        $year = !empty($_GET['year']) ? intval($_GET['year']) : $current_year;
        $month = !empty($_GET['month']) ? intval($_GET['month']) : 0;

        $this->assign('store_id', $store_id);
        $this->assign('year_list', $year_list);
        $this->assign('month_list', $month_list);
        $this->assign('year', $year);
        $this->assign('month', $month);
        $this->assign('page', 'pay_status');

        $this->assign('form_action', RC_Uri::url('orders/admin_order_stats/pay_status', array('store_id' => $store_id)));

        $data = $this->get_pay_status();
        $this->assign('data', $data);

        $this->display('order_stats.dwt');
    }

    /**
     * 报表下载
     */
    public function download()
    {
        /* 判断权限 */
        $this->admin_priv('order_stats', ecjia::MSGTYPE_JSON);

        $store_id = intval($_GET['store_id']);

        $current_year = RC_Time::local_date('Y', RC_Time::gmtime());
        $year = !empty($_GET['year']) ? intval($_GET['year']) : $current_year;
        $month = !empty($_GET['month']) ? intval($_GET['month']) : 0;

        if (empty($month)) {
            $smonth = 1;
            $emonth = 12;
        } else {
            $smonth = $month;
            $emonth = $month;
        }
        $start_time = $year . '-' . $smonth . '-1 00:00:00';
        $em = $year . '-' . $emonth . '-1 23:59:59';
        $end_time = RC_Time::local_date('Y-m-d H:i:s', RC_Time::local_strtotime($em));

        $start_date = RC_Time::local_strtotime($start_time);
        $end_date = RC_Time::local_strtotime($end_time);

        $filename = RC_Lang::get('orders::statistic.order_statement');
        if (!empty($start_time) && !empty($end_time)) {
            $filename .= '_' . $start_time . '至' . $end_time;
        }

        $order_stats = $this->get_order_stats($store_id);

        $count_key = array('await_pay_count', 'await_ship_count', 'shipped_count', 'returned_count', 'canceled_count', 'finished_count');
        $data_key = array('order_count_data', 'groupbuy_count_data', 'storebuy_count_data', 'storepickup_count_data', 'cashdesk_count_data');
        $order_stats['order_count_data']['title'] = '配送型订单';
        $order_stats['groupbuy_count_data']['title'] = '团购型订单';
        $order_stats['storebuy_count_data']['title'] = '到店型订单';
        $order_stats['storepickup_count_data']['title'] = '自提型订单';
        $order_stats['cashdesk_count_data']['title'] = '收银台型订单';

        $count_arr = $count_data_arr = [];
        foreach ($order_stats as $k => $v) {
            if (in_array($k, $count_key)) {
                $count_arr[] = $v;
            }
            if (in_array($k, $data_key)) {
                $count_data_arr[$k]['title'] = $order_stats[$k]['title'];
                $count_data_arr[$k]['order_count'] = $order_stats[$k]['order_count'];
                $count_data_arr[$k]['total_fee'] = $order_stats[$k]['total_fee'];
            }
        }

        RC_Excel::load(RC_APP_PATH . 'orders' . DIRECTORY_SEPARATOR . 'statics/files/orders_stats.xls', function ($excel) use ($count_arr, $count_data_arr) {
            $excel->sheet('First sheet', function ($sheet) use ($count_arr, $count_data_arr) {
                $sheet->appendRow(2, $count_arr);
                $i = 5;
                foreach ($count_data_arr as $k => $v) {
                    $sheet->appendRow($i, $v);
                    $i++;
                }
            });
        })->download('xls');
    }

    /**
     * 获取订单统计信息
     * @return    $arr 订单统计信息
     */
    private function get_order_stats($store_id = 0)
    {
        $current_year = RC_Time::local_date('Y', RC_Time::gmtime());
        $year = !empty($_GET['year']) ? intval($_GET['year']) : $current_year;
        $month = !empty($_GET['month']) ? intval($_GET['month']) : 0;

        if (empty($month)) {
            $smonth = 1;
            $emonth = 12;
        } else {
            $smonth = $month;
            $emonth = $month;
        }
        $start_time = $year . '-' . $smonth . '-1 00:00:00';
        $em = $year . '-' . $emonth . '-1 23:59:59';
        $end_time = RC_Time::local_date('Y-m-d H:i:s', RC_Time::local_strtotime($em));

        $start_date = RC_Time::local_strtotime($start_time);
        $end_date = RC_Time::local_strtotime($end_time);

        $field = 'SUM(goods_amount + shipping_fee + insure_fee + pay_fee + pack_fee + card_fee + tax + integral_money - bonus - discount) as total_fee';
        //待付款订单总金额
        $pay_cod_id = RC_DB::table('payment')->where('pay_code', 'pay_cod')->pluck('pay_id');
        $pay_cod_id = !empty($pay_cod_id) ? intval($pay_cod_id) : 0;

        $await_pay_count = RC_DB::table('order_info')
            ->where('is_delete', 0)
            ->where('store_id', $store_id)
            ->where('add_time', '>=', $start_date)
            ->where('add_time', '<', $end_date)
            ->whereIn('order_status', array(OS_UNCONFIRMED, OS_SPLITED))
            ->where('pay_status', PS_UNPAYED)
            ->where('pay_id', '!=', $pay_cod_id)
            ->select(RC_DB::raw($field))
            ->first();
        $data['await_pay_count'] = price_format($await_pay_count['total_fee']);

        //待发货订单总金额
        $await_ship_count = RC_DB::table('order_info')
            ->where('is_delete', 0)
            ->where('store_id', $store_id)
            ->where('add_time', '>=', $start_date)
            ->where('add_time', '<', $end_date)
            ->whereIn('order_status', array(OS_CONFIRMED, OS_SPLITED, OS_SPLITING_PART))
            ->where(function ($query) {
                $query->whereIn('pay_status', array(PS_PAYED, PS_PAYING))
                    ->orWhere('pay_id', $pay_cod_id);
            })
            ->whereIn('shipping_status', array(SS_UNSHIPPED, SS_PREPARING, SS_SHIPPED_ING))
            ->select(RC_DB::raw($field))
            ->first();
        $data['await_ship_count'] = price_format($await_ship_count['total_fee']);

        //已发货订单总金额
        $shipped_count = RC_DB::table('order_info')
            ->where('is_delete', 0)
            ->where('store_id', $store_id)
            ->where('add_time', '>=', $start_date)
            ->where('add_time', '<', $end_date)
            ->where('order_status', '!=', OS_RETURNED)
            ->where('shipping_status', SS_SHIPPED)
            ->select(RC_DB::raw($field))
            ->first();
        $data['shipped_count'] = price_format($shipped_count['total_fee']);

        //退货订单总金额
        $returned_count = RC_DB::table('order_info')
            ->where('is_delete', 0)
            ->where('store_id', $store_id)
            ->where('add_time', '>=', $start_date)
            ->where('add_time', '<', $end_date)
            ->where('order_status', OS_RETURNED)
            ->where('pay_status', PS_PAYED)
            ->select(RC_DB::raw($field))
            ->first();
        $data['returned_count'] = price_format($returned_count['total_fee']);

        //已取消订单总金额
        $canceled_count = RC_DB::table('order_info')
            ->where('is_delete', 0)
            ->where('store_id', $store_id)
            ->where('add_time', '>=', $start_date)
            ->where('add_time', '<', $end_date)
            ->whereIn('order_status', array(OS_CANCELED, OS_INVALID))
            ->select(RC_DB::raw($field))
            ->first();
        $data['canceled_count'] = price_format($canceled_count['total_fee']);

        //已完成订单总金额
        $finished_count = RC_DB::table('order_info')
            ->where('is_delete', 0)
            ->where('store_id', $store_id)
            ->where('add_time', '>=', $start_date)
            ->where('add_time', '<', $end_date)
            ->whereIn('order_status', array(OS_CONFIRMED, OS_SPLITED))
            ->where('shipping_status', SS_RECEIVED)
            ->select(RC_DB::raw($field))
            ->first();
        $data['finished_count'] = price_format($finished_count['total_fee']);

        //配送型订单数及总金额
        $data['order_count_data'] = RC_DB::table('order_info')
            ->where('is_delete', 0)
            ->where('store_id', $store_id)
            ->where('add_time', '>=', $start_date)
            ->where('add_time', '<', $end_date)
            ->whereIn('order_status', array(OS_CONFIRMED, OS_SPLITED))
            ->where('shipping_status', SS_RECEIVED)
            ->where(function ($query) {
                $query->where('extension_code', '')
                    ->orWhere('extension_code', null);
            })
            ->select(RC_DB::raw("count('order_id') as order_count"), RC_DB::raw("SUM(goods_amount + shipping_fee + insure_fee + pay_fee + pack_fee + card_fee + tax + integral_money - bonus - discount) as total_fee"))
            ->first();

        //团购型订单数及总金额
        $data['groupbuy_count_data'] = RC_DB::table('order_info')
            ->where('is_delete', 0)
            ->where('store_id', $store_id)
            ->where('add_time', '>=', $start_date)
            ->where('add_time', '<', $end_date)
            ->whereIn('order_status', array(OS_CONFIRMED, OS_SPLITED))
            ->where('shipping_status', SS_RECEIVED)
            ->where('extension_code', 'group_buy')
            ->select(RC_DB::raw("count('order_id') as order_count"), RC_DB::raw("SUM(goods_amount + shipping_fee + insure_fee + pay_fee + pack_fee + card_fee + tax + integral_money - bonus - discount) as total_fee"))
            ->first();

        //到店型订单数及总金额
        $data['storebuy_count_data'] = RC_DB::table('order_info')
            ->where('is_delete', 0)
            ->where('store_id', $store_id)
            ->where('add_time', '>=', $start_date)
            ->where('add_time', '<', $end_date)
            ->whereIn('order_status', array(OS_CONFIRMED, OS_SPLITED))
            ->where('shipping_status', SS_RECEIVED)
            ->where('extension_code', 'storebuy')
            ->select(RC_DB::raw("count('order_id') as order_count"), RC_DB::raw("SUM(goods_amount + shipping_fee + insure_fee + pay_fee + pack_fee + card_fee + tax + integral_money - bonus - discount) as total_fee"))
            ->first();

        //自提型订单数及总金额
        $data['storepickup_count_data'] = RC_DB::table('order_info')
            ->where('is_delete', 0)
            ->where('store_id', $store_id)
            ->where('add_time', '>=', $start_date)
            ->where('add_time', '<', $end_date)
            ->whereIn('order_status', array(OS_CONFIRMED, OS_SPLITED))
            ->where('shipping_status', SS_RECEIVED)
            ->where('extension_code', 'storepickup')
            ->select(RC_DB::raw("count('order_id') as order_count"), RC_DB::raw("SUM(goods_amount + shipping_fee + insure_fee + pay_fee + pack_fee + card_fee + tax + integral_money - bonus - discount) as total_fee"))
            ->first();

        //收银台型订单数及总金额
        $data['cashdesk_count_data'] = RC_DB::table('order_info')
            ->where('is_delete', 0)
            ->where('store_id', $store_id)
            ->where('add_time', '>=', $start_date)
            ->where('add_time', '<', $end_date)
            ->whereIn('order_status', array(OS_CONFIRMED, OS_SPLITED))
            ->where('shipping_status', SS_RECEIVED)
            ->where('extension_code', 'cashdesk')
            ->select(RC_DB::raw("count('order_id') as order_count"), RC_DB::raw("SUM(goods_amount + shipping_fee + insure_fee + pay_fee + pack_fee + card_fee + tax + integral_money - bonus - discount) as total_fee"))
            ->first();

        $data['count_all'] = $data['order_count_data']['order_count'] + $data['groupbuy_count_data']['order_count'] + $data['storebuy_count_data']['order_count'] + $data['storepickup_count_data']['order_count'] + $data['cashdesk_count_data']['order_count'];

        $data['order_count_data']['total_fee'] = price_format($data['order_count_data']['total_fee']);
        $data['groupbuy_count_data']['total_fee'] = price_format($data['groupbuy_count_data']['total_fee']);
        $data['storebuy_count_data']['total_fee'] = price_format($data['storebuy_count_data']['total_fee']);
        $data['storepickup_count_data']['total_fee'] = price_format($data['storepickup_count_data']['total_fee']);
        $data['cashdesk_count_data']['total_fee'] = price_format($data['cashdesk_count_data']['total_fee']);

        $data['type'] = array(
            array('name' => '配送', 'value' => $data['order_count_data']['order_count']),
            array('name' => '团购', 'value' => $data['groupbuy_count_data']['order_count']),
            array('name' => '到店', 'value' => $data['storebuy_count_data']['order_count']),
            array('name' => '自提', 'value' => $data['storepickup_count_data']['order_count']),
            array('name' => '收银台', 'value' => $data['cashdesk_count_data']['order_count']),
        );
        return $data;
    }

    /**
     * 订单概况
     */
    private function get_order_general($store_id)
    {
        $current_year = RC_Time::local_date('Y', RC_Time::gmtime());
        $year = !empty($_GET['year']) ? intval($_GET['year']) : $current_year;
        $month = !empty($_GET['month']) ? intval($_GET['month']) : 0;

        if (empty($month)) {
            $smonth = 1;
            $emonth = 12;
        } else {
            $smonth = $month;
            $emonth = $month;
        }
        $start_time = $year . '-' . $smonth . '-1 00:00:00';
        $em = $year . '-' . $emonth . '-1 23:59:59';
        $end_time = RC_Time::local_date('Y-m-d H:i:s', RC_Time::local_strtotime($em));

        $start_date = RC_Time::local_strtotime($start_time);
        $end_date = RC_Time::local_strtotime($end_time);

        $order_info = $this->get_orderinfo($start_date, $end_date, $store_id);

        if (!empty($order_info)) {
            foreach ($order_info as $k => $v) {
                if ($k == 'await_pay_num') {
                    $key = RC_Lang::get('orders::statistic.await_pay_order');
                    $order_info[$key] = $order_info['await_pay_num'];
                    unset($order_info['await_pay_num']);

                } elseif ($k == 'await_ship_num') {
                    $key = RC_Lang::get('orders::statistic.await_ship_order');
                    $order_info[$key] = $order_info['await_ship_num'];
                    unset($order_info['confirmed_num']);

                } elseif ($k == 'shipped_num') {
                    $key = RC_Lang::get('orders::statistic.shipped_order');
                    $order_info[$key] = $order_info['shipped_num'];
                    unset($order_info['shipped_num']);

                } elseif ($k == 'returned_num') {
                    $key = RC_Lang::get('orders::statistic.returned_order');
                    $order_info[$key] = $order_info['returned_num'];
                    unset($order_info['returned_num']);
                } elseif ($k == 'canceled_num') {
                    $key = RC_Lang::get('orders::statistic.canceled_order');
                    $order_info[$key] = $order_info['canceled_num'];
                    unset($order_info['canceled_num']);
                } elseif ($k == 'finished_num') {
                    $key = RC_Lang::get('orders::statistic.succeed_order');
                    $order_info[$key] = $order_info['finished_num'];
                    unset($order_info['finished_num']);
                }
            }
            arsort($order_info);
            foreach ($order_info as $k => $v) {
                if ($order_info[RC_Lang::get('orders::statistic.await_pay_order')] == 0 && $order_info[RC_Lang::get('orders::statistic.await_ship_order')] == 0
                    && $order_info[RC_Lang::get('orders::statistic.shipped_order')] == 0 && $order_info[RC_Lang::get('orders::statistic.returned_order')] == 0
                    && $order_info[RC_Lang::get('orders::statistic.canceled_order')] == 0 && $order_info[RC_Lang::get('orders::statistic.succeed_order')] == 0) {
                    $order_info = null;
                } else {
                    break;
                }
            }
        }
        $order_infos = json_encode($order_info);
        return $order_infos;
    }

    /**
     * 获取配送方式数据
     */
    private function get_ship_status()
    {
        $current_year = RC_Time::local_date('Y', RC_Time::gmtime());
        $year = !empty($_GET['year']) ? intval($_GET['year']) : $current_year;
        $month = !empty($_GET['month']) ? intval($_GET['month']) : 0;

        if (empty($month)) {
            $smonth = 1;
            $emonth = 12;
        } else {
            $smonth = $month;
            $emonth = $month;
        }
        $start_time = $year . '-' . $smonth . '-1 00:00:00';
        $em = $year . '-' . $emonth . '-1 23:59:59';
        $end_time = RC_Time::local_date('Y-m-d H:i:s', RC_Time::local_strtotime($em));

        $start_date = RC_Time::local_strtotime($start_time);
        $end_date = RC_Time::local_strtotime($end_time);

        $where = "i.add_time >= '$start_date' AND i.add_time <= '$end_date'" . order_query_sql('finished');

        $ship_info = RC_DB::table('shipping as sp')
            ->leftJoin('order_info as i', RC_DB::raw('sp.shipping_id'), '=', RC_DB::raw('i.shipping_id'))
            ->select(RC_DB::raw('sp.shipping_name AS ship_name, COUNT(i.order_id) AS order_num'))
            ->whereRaw($where)
            ->groupby(RC_DB::raw('i.shipping_id'))
            ->orderby('order_num', 'desc')
            ->get();

        if (!empty($ship_info)) {
            arsort($ship_info);
        } else {
            $ship_info = null;
        }
        $ship_infos = json_encode($ship_info);
        return $ship_infos;
    }

    /**
     * 获取支付方式数据
     */
    private function get_pay_status()
    {
        $current_year = RC_Time::local_date('Y', RC_Time::gmtime());
        $year = !empty($_GET['year']) ? intval($_GET['year']) : $current_year;
        $month = !empty($_GET['month']) ? intval($_GET['month']) : 0;

        if (empty($month)) {
            $smonth = 1;
            $emonth = 12;
        } else {
            $smonth = $month;
            $emonth = $month;
        }
        $start_time = $year . '-' . $smonth . '-1 00:00:00';
        $em = $year . '-' . $emonth . '-1 23:59:59';
        $end_time = RC_Time::local_date('Y-m-d H:i:s', RC_Time::local_strtotime($em));

        $start_date = RC_Time::local_strtotime($start_time);
        $end_date = RC_Time::local_strtotime($end_time);

        $where = "i.add_time >= '$start_date' AND i.add_time <= '$end_date'" . order_query_sql('finished');

        $pay_info = RC_DB::table('payment as p')
            ->leftJoin('order_info as i', RC_DB::raw('p.pay_id'), '=', RC_DB::raw('i.pay_id'))
            ->select(RC_DB::raw('i.pay_id, p.pay_name, COUNT(i.order_id) AS order_num'))
            ->whereRaw($where)
            ->groupby(RC_DB::raw('i.pay_id'))
            ->orderby('order_num', 'desc')
            ->get();

        if (!empty($pay_info)) {
            foreach ($pay_info as $key => $val) {
                unset($pay_info[$key]['pay_id']);
            }
            arsort($pay_info);
        } else {
            $pay_info = null;
        }
        $pay_infos = json_encode($pay_info);
        return $pay_infos;
    }

    /**
     * 取得订单概况数据(包括订单的几种状态)
     * @param       $start_date    开始查询的日期
     * @param       $end_date      查询的结束日期
     * @return      $order_info    订单概况数据
     */
    private function get_orderinfo($start_date, $end_date, $store_id = 0)
    {
        $order_info = array();
        /*待付款订单*/
        $order_info['await_pay_num'] = RC_DB::table('order_info')
            ->select(RC_DB::raw('COUNT(*) AS await_pay_num'))
            ->where('pay_status', PS_UNPAYED)
            ->where('add_time', '>=', $start_date)
            ->where('add_time', '<', $end_date)
            ->where('is_delete', 0)
            ->where('store_id', $store_id)
            ->count();

        /* 待发货订单数 */
        $order_info['await_ship_num'] = RC_DB::table('order_info')
            ->select(RC_DB::raw('COUNT(*) AS await_ship_num'))
            ->whereIn('order_status', array(OS_CONFIRMED, OS_SPLITED, OS_SPLITING_PART))
            ->whereIn('shipping_status', array(SS_UNSHIPPED, SS_PREPARING, SS_SHIPPED_ING))
            ->whereIn('pay_status', array(PS_PAYED, PS_PAYING))
            ->where('add_time', '>=', $start_date)
            ->where('add_time', '<', $end_date)
            ->where('is_delete', 0)
            ->where('store_id', $store_id)
            ->count();

        /* 已发货订单数 */
        $order_info['shipped_num'] = RC_DB::table('order_info')
            ->select(RC_DB::raw('COUNT(*) AS shipped_num'))
            ->where('shipping_status', SS_SHIPPED)
            ->where('order_status', '!=', OS_RETURNED)
            ->where('add_time', '>=', $start_date)
            ->where('add_time', '<', $end_date)
            ->where('is_delete', 0)
            ->where('store_id', $store_id)
            ->count();

        /* 退货订单数 */
        $order_info['returned_num'] = RC_DB::table('order_info')
            ->select(RC_DB::raw('COUNT(*) AS returned_num'))
            ->where('pay_status', PS_PAYED)
            ->where('order_status', OS_RETURNED)
            ->where('add_time', '>=', $start_date)
            ->where('add_time', '<', $end_date)
            ->where('is_delete', 0)
            ->where('store_id', $store_id)
            ->count();

        /* 已取消订单数 */
        $order_info['canceled_num'] = RC_DB::table('order_info')
            ->select(RC_DB::raw('COUNT(*) AS canceled_num'))
            ->whereIn('order_status', array(OS_CANCELED, OS_INVALID))
            ->where('add_time', '>=', $start_date)
            ->where('add_time', '<', $end_date)
            ->where('is_delete', 0)
            ->where('store_id', $store_id)
            ->count();

        /* 已完成订单数 */
        $order_info['finished_num'] = RC_DB::table('order_info')
            ->select(RC_DB::raw('COUNT(*) AS finished_num'))
            ->where('shipping_status', SS_RECEIVED)
            ->whereIn('pay_status', array(PS_PAYED, PS_PAYING))
            ->where('add_time', '>=', $start_date)
            ->where('add_time', '<', $end_date)
            ->where('is_delete', 0)
            ->where('store_id', $store_id)
            ->count();

        return $order_info;
    }

    private function get_merchant_list()
    {
        $db = RC_DB::table('store_franchisee as s');

        $keywords = !empty($_GET['keywords']) ? trim($_GET['keywords']) : '';

        $table_store_franchisee = RC_DB::getTableFullName('store_franchisee');
        $table_order_info = RC_DB::getTableFullName('order_info');

        $sql = "select s.store_id, s.merchants_name, total_order, total_amount, valid_order, valid_amount
from " . $table_store_franchisee . " as s

INNER JOIN (select store_id, count(order_id) as total_order from " . $table_order_info . " where is_delete = 0 GROUP BY store_id)
as a on a.store_id = s.store_id

INNER JOIN (select store_id, sum(goods_amount + shipping_fee + insure_fee + pay_fee + pack_fee + card_fee + tax + integral_money - bonus - discount) as total_amount from " . $table_order_info . " where is_delete = 0 and pay_status in (2, 1) GROUP BY store_id)
as b on b.store_id = s.store_id

INNER JOIN (select store_id, count(order_id) as valid_order from " . $table_order_info . " where is_delete = 0 and order_status in (1, 5) and shipping_status = 2 and pay_status in (2, 1) GROUP BY store_id)
as c on c.store_id = s.store_id

INNER JOIN (select store_id, sum(goods_amount + shipping_fee + insure_fee + pay_fee + pack_fee + card_fee + tax + integral_money - bonus - discount) as valid_amount from " . $table_order_info . " where is_delete = 0 and order_status in (1, 5) and shipping_status = 2 and pay_status in (2, 1) GROUP BY store_id)
as d on d.store_id = s.store_id

where s.shop_close = 0 and s.identity_status = 2";

        $pagenum = isset($_GET['page']) ? intval($_GET['page']) : 1;
        $sort_by = isset($_GET['sort_by']) && $_GET['sort_by'] != 'level' ? trim($_GET['sort_by']) : 'valid_amount';
        $sort_order = isset($_GET['sort_order']) ? trim($_GET['sort_order']) : 'desc';

        //图表数据 根据按钮状态切换显示 start
        $stats = !empty($_GET['stats']) ? trim($_GET['stats']) : 'valid_amount';
        if ($stats == 'valid_order') {
            $level_sql = $sql . " ORDER BY valid_order desc";
        } else {
            $level_sql = $sql . " ORDER BY valid_amount desc";
        }
        $stats_data = RC_DB::select($level_sql);
        //图表数据 根据按钮状态切换显示 end

        //店铺排行 不受分页/关键字影响 start
        $amount_sql = $sql . " ORDER BY valid_amount desc";
        $level_data = RC_DB::select($amount_sql);
        $level = [];
        if (!empty($level_data)) {
            foreach ($level_data as $k => $v) {
                $level[$v['store_id']]['level'] = $k + 1;
            }
        }
        //店铺排行 不受分页/关键字影响 end

        //列表数据 start
        $data = [];
        if (!empty($keywords)) {
            $sql .= ' and s.merchants_name like "' . '%' . $keywords . '%"';
        }
        $sql .= " ORDER BY " . $sort_by . ' ' . $sort_order;
        $data = RC_DB::select($sql);
        //列表数据 end

        $pageSize = 30;
        $count = count($data);
        $page = new ecjia_page($count, $pageSize, 6);

        $sql .= " limit " . ($pagenum - 1) * $pageSize . "," . $pageSize;
        $result = RC_DB::select($sql);

        if (!empty($result)) {
            foreach ($result as $k => $v) {
                $result[$k]['formated_total_amount'] = price_format($v['total_amount']);
                $result[$k]['formated_valid_amount'] = price_format($v['valid_amount']);
                //店铺排行
                $result[$k]['level'] = $level[$v['store_id']]['level'];
            }
            if (empty($sort_by)) {
                $result = $this->array_sort($result, 'level');
            } else if ($sort_by == 'level') {
                $result = $this->array_sort($result, 'level', $sort_order);
            }
        }
        return array('item' => $result, 'page' => $page->show(2), 'stats_data' => $stats_data);
    }

    private function array_sort($arr, $keys, $type = 'asc')
    {
        $keysvalue = $new_array = array();
        foreach ($arr as $k => $v) {
            $keysvalue[$k] = $v[$keys];
        }
        if ($type == 'asc') {
            asort($keysvalue);
        } else {
            arsort($keysvalue);
        }
        reset($keysvalue);
        foreach ($keysvalue as $k => $v) {
            $new_array[$k] = $arr[$k];
        }
        return $new_array;
    }
}

// end
