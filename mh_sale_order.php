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
 * 商品销售排行
 */
class mh_sale_order extends ecjia_merchant
{
    private $db_order_goods_view;

    public function __construct()
    {
        parent::__construct();

        /* 加载所有全局 js/css */
        RC_Script::enqueue_script('bootstrap-placeholder');
        RC_Script::enqueue_script('jquery-validate');
        RC_Script::enqueue_script('jquery-form');
        RC_Script::enqueue_script('smoke');
        RC_Script::enqueue_script('jquery-uniform');
        RC_Style::enqueue_style('uniform-aristo');

        //时间控件
        RC_Script::enqueue_script('bootstrap-datepicker', RC_Uri::admin_url('statics/lib/datepicker/bootstrap-datepicker.min.js'));
        RC_Style::enqueue_style('datepicker', RC_Uri::admin_url('statics/lib/datepicker/datepicker.css'));

        /*自定义js*/
        RC_Script::enqueue_script('sale_order', RC_App::apps_url('statics/js/merchant_sale_order.js', __FILE__), array('ecjia-merchant'), false, 1);
        RC_Script::localize_script('sale_order', 'js_lang', config('app-orders::jslang.merchant_sale_order_page'));

        RC_Loader::load_app_func('global', 'orders');
        $this->db_order_goods_view = RC_Loader::load_app_model('order_goods_viewmodel', 'orders');

        ecjia_merchant_screen::get_current_screen()->set_parentage('stats', 'stats/mh_sale_order.php');
    }

    public function init()
    {
        /* 权限检查 */
        $this->admin_priv('sale_order_stats');

        ecjia_merchant_screen::get_current_screen()->add_nav_here(new admin_nav_here(__('报表统计', 'orders'), RC_Uri::url('stats/mh_keywords_stats/init')));
        ecjia_merchant_screen::get_current_screen()->add_nav_here(new admin_nav_here(__('销售排行', 'orders')));

        /* 赋值到模板 */
        $this->assign('ur_here', __('销售排行', 'orders'));
        $this->assign('action_link', array('text' => __('销售排行报表下载', 'orders'), 'href' => RC_Uri::url('orders/mh_sale_order/download')));

        /*时间参数*/
        $start_date = !empty($_GET['start_date']) ? $_GET['start_date'] : RC_Time::local_date(ecjia::config('date_format'), RC_Time::local_strtotime('-1 month'));
        $end_date   = !empty($_GET['end_date']) ? $_GET['end_date'] : RC_Time::local_date(ecjia::config('date_format'), RC_Time::local_strtotime('+1 day'));

        $filter['start_date'] = RC_Time::local_strtotime($start_date);
        $filter['end_date']   = RC_Time::local_strtotime($end_date);

        $filter['sort_by']    = empty($_GET['sort_by']) ? 'goods_num' : trim($_GET['sort_by']);
        $filter['sort_order'] = empty($_GET['sort_order']) ? 'DESC' : trim($_GET['sort_order']);

        $goods_order_data = $this->get_sales_order(true, $filter);

        $this->assign('start_date', $start_date);
        $this->assign('end_date', $end_date);
        $this->assign('filter', $filter);

        $this->assign('goods_order_data', $goods_order_data);
        $this->assign('search_action', RC_Uri::url('orders/mh_sale_order/init'));

        return $this->display('sale_order.dwt');
    }

    /**
     * 商品销售排行报表下载
     */
    public function download()
    {
        /* 检查权限 */
        $this->admin_priv('sale_order_stats', ecjia::MSGTYPE_JSON);

        /*时间参数*/
        $start_date = !empty($_GET['start_date']) ? $_GET['start_date'] : RC_Time::local_date(ecjia::config('date_format'), RC_Time::local_strtotime('-1 month'));
        $end_date   = !empty($_GET['end_date']) ? $_GET['end_date'] : RC_Time::local_date(ecjia::config('date_format'), RC_Time::local_strtotime('+1 day'));

        $filter['start_date'] = RC_Time::local_strtotime($start_date);
        $filter['end_date']   = RC_Time::local_strtotime($end_date);
        $filter['sort_by']    = empty($_GET['sort_by']) ? 'goods_num' : trim($_GET['sort_by']);
        $filter['sort_order'] = empty($_GET['sort_order']) ? 'DESC' : trim($_GET['sort_order']);

        $goods_order_data = $this->get_sales_order(false, $filter);
        $filename         = __('销售排行报表', 'orders');

        header("Content-type: application/vnd.ms-excel; charset=utf-8");
        header("Content-Disposition: attachment; filename=$filename.xls");

        echo mb_convert_encoding(sprintf(__('%s销售排行报表', 'orders'), $filename), 'UTF-8', 'UTF-8') . "\t\n";
        $data = __('排行', 'orders') . "\t" . __('商品名称', 'orders') . "\t" . __('货号', 'orders') . "\t" . __('销售量', 'orders') . "\t" . __('销售额', 'orders') . "\t" . __('均价', 'orders') . "\n";
        foreach ($goods_order_data['item'] as $k => $v) {
            $order_by = $k + 1;
            $data     .= "$order_by\t$v[goods_name]\t$v[goods_sn]\t$v[goods_num]\t$v[turnover]\t$v[wvera_price]\n";
        }
        echo mb_convert_encoding($data . "\t", 'UTF-8', 'auto');
        exit;
    }

    /**
     * 取得销售排行数据信息
     * @param   bool $is_pagination 是否分页
     * @return  array   销售排行数据
     */
    private function get_sales_order($is_pagination, $filter)
    {
        $where = 'oi.store_id = ' . $_SESSION['store_id'] . order_query_sql('finished', 'oi.');
        if ($filter['start_date']) {
            $where .= " AND oi.add_time >= '" . $filter['start_date'] . "'";
        }
        if ($filter['end_date']) {
            $where .= " AND oi.add_time <= '" . $filter['end_date'] . "'";
        }
        $where .= " AND oi.is_delete = 0";

        $count = $this->db_order_goods_view->where($where)->count('distinct(og.goods_id)');
        $page  = new ecjia_merchant_page($count, 20, 5);

        $db_goods_view = RC_DB::table('goods as g')
            ->leftJoin('order_goods as og', RC_DB::raw('og.goods_id'), '=', RC_DB::raw('g.goods_id'))
            ->leftJoin('order_info as oi', RC_DB::raw('oi.order_id'), '=', RC_DB::raw('og.order_id'))
            ->select(RC_DB::raw('og.goods_id'), RC_DB::raw('og.goods_sn'), RC_DB::raw('og.goods_name'), RC_DB::raw('oi.order_status'), RC_DB::raw('SUM(og.goods_number) AS goods_num'), RC_DB::raw('SUM(og.goods_number * og.goods_price) AS turnover'))
            ->whereRaw($where)
            ->groupBy(RC_DB::raw('og.goods_id'))
            ->orderBy($filter['sort_by'], $filter['sort_order'])
            ->orderBy('turnover', 'desc');

        if ($is_pagination) {
            $db_goods_view->take(20)->skip($page->start_id - 1);
        }
        $sales_order_data = $db_goods_view->get();

        foreach ($sales_order_data as $key => $item) {
            $sales_order_data[$key]['wvera_price'] = price_format($item['goods_num'] ? $item['turnover'] / $item['goods_num'] : 0);
            $sales_order_data[$key]['short_name']  = sub_str($item['goods_name'], 30, true);
            $sales_order_data[$key]['turnover']    = price_format($item['turnover']);
            $sales_order_data[$key]['taxis']       = $key + 1;
        }

        $arr = array('item' => $sales_order_data, 'filter' => $filter, 'desc' => $page->page_desc(), 'page' => $page->show(2));
        return $arr;
    }
}

// end