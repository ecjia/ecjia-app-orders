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
 * 销售概况
*/
class admin_sale_general extends ecjia_admin {
	public function __construct() {
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
		RC_Style::enqueue_style('datepicker', RC_Uri::admin_url('statics/lib/datepicker/datepicker.css'));
		RC_Script::enqueue_script('bootstrap-datepicker', RC_Uri::admin_url('statics/lib/datepicker/bootstrap-datepicker.min.js'));
		RC_Script::enqueue_script('bootstrap-editable-script', RC_Uri::admin_url('statics/lib/x-editable/bootstrap-editable/js/bootstrap-editable.min.js'));
		RC_Style::enqueue_style('bootstrap-editable-css', RC_Uri::admin_url('statics/lib/x-editable/bootstrap-editable/css/bootstrap-editable.css'));

		/*自定义*/
        RC_Script::enqueue_script('acharts-min', RC_App::apps_url('statics/js/acharts-min.js', __FILE__));
        RC_Style::enqueue_style('orders-css', RC_App::apps_url('statics/css/admin_orders.css', __FILE__));
        RC_Script::enqueue_script('sale_general', RC_App::apps_url('statics/js/sale_general.js', __FILE__));
        RC_Script::enqueue_script('sale_general_chart', RC_App::apps_url('statics/js/sale_general_chart.js', __FILE__));

        RC_Script::localize_script('sale_general', 'js_lang', RC_Lang::get('orders::statistic.js_lang'));
        RC_Script::localize_script('sale_general_chart', 'js_lang', RC_Lang::get('orders::statistic.js_lang'));
	}

	/**
	 * 显示统计信息
	 */
	public function init() {
		/*权限判断 */
		$this->admin_priv('sale_general_stats', ecjia::MSGTYPE_JSON);

		ecjia_screen::get_current_screen()->add_nav_here(new admin_nav_here(RC_Lang::get('orders::statistic.report_sell')));
		ecjia_screen::get_current_screen()->add_help_tab(array(
			'id'		=> 'overview',
			'title'		=> RC_Lang::get('orders::statistic.overview'),
			'content'	=> '<p>' . RC_Lang::get('orders::statistic.sale_general_help') . '</p>'
		));
		ecjia_screen::get_current_screen()->set_help_sidebar(
			'<p><strong>' . RC_Lang::get('orders::statistic.more_info') . '</strong></p>' .
			'<p>' . __('<a href="https://ecjia.com/wiki/帮助:ECJia智能后台:销售概况" target="_blank">'. RC_Lang::get('orders::statistic.about_sale_general') .'</a>') . '</p>'
		);

		$this->assign('ur_here', RC_Lang::get('orders::statistic.report_sell'));
		$this->assign('action_link', array('text' => RC_Lang::get('orders::statistic.down_sales_stats'), 'href' => RC_Uri::url('orders/admin_sale_general/download')));

		if (empty($_GET['query_type'])) {
			$query_type = 'month';
			$start_time 		= RC_Time::local_mktime(0, 0, 0, 1, 1, intval(date('Y')-3));
			$end_time   		= RC_Time::local_mktime(23, 59, 59, 12, 31, intval(date('Y')));
			$start_month_time 	= RC_Time::local_mktime(0, 0, 0, 1, 1, intval(date('Y')-3));
			$end_month_time   	= RC_Time::local_mktime(23, 59, 59, 12, 31, intval(date('Y')));
		}
		if (!empty($_GET['query_by_year'])) {
			$query_type = 'year';
			$start_time 		= RC_Time::local_mktime(0, 0, 0, 1, 1, intval($_GET['year_beginYear']));
			$end_time   		= RC_Time::local_mktime(23, 59, 59, 12, 31, intval($_GET['year_endYear']));
			$start_month_time 	= RC_Time::local_mktime(0, 0, 0, 1, 1, intval($_GET['year_beginYear']));
			$end_month_time   	= RC_Time::local_mktime(23, 59, 59, 12, 31, intval($_GET['year_endYear']));
		} elseif (!empty($_GET['query_by_month'])) {
			$start_time 		= RC_Time::local_mktime(0, 0, 0, intval($_GET['month_beginMonth']), 1, intval($_GET['month_beginYear']));
			$end_time   		= RC_Time::local_mktime(23, 59, 59, intval($_GET['month_endMonth']), 31, intval($_GET['month_endYear']));
			$start_month_time 	= RC_Time::local_mktime(0, 0, 0, intval($_GET['month_beginMonth']), 1, intval($_GET['month_beginYear']));
			$end_month_time   	= RC_Time::local_mktime(23, 59, 59, intval($_GET['month_endMonth']), 31, intval($_GET['month_endYear']));
		}

		$this->assign('query_type', $query_type);
		$this->assign('start_time', RC_Time::local_date('Y-m-d', $start_time));
		$this->assign('end_time', RC_Time::local_date('Y-m-d', $end_time));

		$this->assign('form_action', RC_Uri::url('orders/admin_sale_general/init'));
		$this->assign('start_month_time', RC_Time::local_date('Y-m-d', $start_month_time));
		$this->assign('end_month_time', RC_Time::local_date('Y-m-d', $end_month_time));
		$this->assign('page', 'init');

		$this->display('sale_general.dwt');
	}

	/**
	 * 显示销售额走势
	 */
	public function sales_trends() {
		/*权限判断 */
		$this->admin_priv('sale_general_stats', ecjia::MSGTYPE_JSON);

		ecjia_screen::get_current_screen()->add_nav_here(new admin_nav_here(RC_Lang::get('orders::statistic.report_sell')));
		ecjia_screen::get_current_screen()->add_help_tab( array(
			'id'		=> 'overview',
			'title'		=> RC_Lang::get('orders::statistic.overview'),
			'content'	=> '<p>' . RC_Lang::get('orders::statistic.sale_general_help') . '</p>'
		));

		ecjia_screen::get_current_screen()->set_help_sidebar(
			'<p><strong>' . RC_Lang::get('orders::statistic.more_info') . '</strong></p>' .
			'<p>' . __('<a href="https://ecjia.com/wiki/帮助:ECJia智能后台:销售概况" target="_blank">'. RC_Lang::get('orders::statistic.about_sale_general') .'</a>') . '</p>'
		);

		$this->assign('ur_here', RC_Lang::get('orders::statistic.report_sell'));
		$this->assign('action_link',array('text' => RC_Lang::get('orders::statistic.down_sales_stats'),'href' => RC_Uri::url('orders/admin_sale_general/download')));

		if (empty($_GET['query_type'])) {
			$query_type = 'month';
			$start_time 		= RC_Time::local_mktime(0, 0, 0, 1, 1, intval(date('Y')-3));
			$end_time   		= RC_Time::local_mktime(23, 59, 59, 12, 31, intval(date('Y')));
			$start_month_time 	= RC_Time::local_mktime(0, 0, 0, 1, 1, intval(date('Y')-3));
			$end_month_time   	= RC_Time::local_mktime(23, 59, 59, 12, 31, intval(date('Y')));
		}
		if (!empty($_GET['query_by_year'])) {
			$query_type = 'year';
			$start_time 		= RC_Time::local_mktime(0, 0, 0, 1, 1, intval($_GET['year_beginYear']));
			$end_time   		= RC_Time::local_mktime(23, 59, 59, 12, 31, intval($_GET['year_endYear']));
			$start_month_time 	= RC_Time::local_mktime(0, 0, 0, 1, 1, intval($_GET['year_beginYear']));
			$end_month_time   	= RC_Time::local_mktime(23, 59, 59, 12, 31, intval($_GET['year_endYear']));
		} elseif (!empty($_GET['query_by_month'])) {
			$start_time 		= RC_Time::local_mktime(0, 0, 0, intval($_GET['month_beginMonth']), 1, intval($_GET['month_beginYear']));
			$end_time   		= RC_Time::local_mktime(23, 59, 59, intval($_GET['month_endMonth']), 31, intval($_GET['month_endYear']));
			$start_month_time 	= RC_Time::local_mktime(0, 0, 0, intval($_GET['month_beginMonth']), 1, intval($_GET['month_beginYear']));
			$end_month_time   	= RC_Time::local_mktime(23, 59, 59, intval($_GET['month_endMonth']), 31, intval($_GET['month_endYear']));
		}

		$this->assign('query_type', $query_type);
		$this->assign('start_time', RC_Time::local_date('Y-m-d', $start_time));
		$this->assign('end_time', RC_Time::local_date('Y-m-d', $end_time));
		$this->assign('form_action', RC_Uri::url('orders/admin_sale_general/sales_trends'));
		$this->assign('start_month_time', RC_Time::local_date('Y-m-d', $start_month_time));
		$this->assign('end_month_time', RC_Time::local_date('Y-m-d', $end_month_time));
		$this->assign('page', 'sales_trends');

		$this->display('sale_general.dwt');
	}

	/**
	 * 获取销售概况图表数据
	 */
	public function get_order_status() {
		$query_type = $_GET['query_type'] == 'year' ? 'year' : 'month';
		if ($query_type =='year') {
			/*时间参数*/
			$start_time = RC_Time::local_strtotime($_GET['start_time']);
			$end_time   = RC_Time::local_strtotime($_GET['end_time']);
		} else {
			$start_time = RC_Time::local_strtotime($_GET['start_month_time']);
			$end_time   = RC_Time::local_strtotime($_GET['end_month_time']);
		}
		$format = ($query_type == 'year') ? '%Y' : '%Y-%m';
		$where =  "(order_status = '" . OS_CONFIRMED . "' OR order_status >= '" . OS_SPLITED . "' ) AND ( pay_status = '" . PS_PAYED . "' OR pay_status = '" . PS_PAYING . "') AND (shipping_status = '" . SS_SHIPPED . "' OR shipping_status = '" . SS_RECEIVED . "' ) AND (shipping_time >= ' ". $start_time ."' AND shipping_time <= '" .$end_time. "')";
		$where .= " AND is_delete = 0";

		$templateCount = RC_DB::table('order_info')
			->select(RC_DB::raw("DATE_FORMAT(FROM_UNIXTIME(shipping_time), '". $format ."') AS period, COUNT(*) AS order_count, SUM(goods_amount + shipping_fee + insure_fee + pay_fee + pack_fee + card_fee - discount) AS order_amount"))
			->whereRaw($where)
			->groupby('period')
			->get();

		if ($_GET['order_type'] == 1) {
			if (!empty($templateCount)) {
				foreach ($templateCount as $k=>$v) {
					unset($templateCount[$k]['order_amount']);
				}
			} else {
				$templateCount = null;
			}
			$templateCounts = json_encode($templateCount);
			echo $templateCounts;
		} else {
			if (!empty($templateCount)) {
				foreach ($templateCount as $k=>$v) {
					unset($templateCount[$k]['order_count']);
				}
			} else {
				$templateCount = null;
			}
			$templateCounts = json_encode($templateCount);
			echo $templateCounts;
		}
	}
	/**
	 * 下载EXCEL报表
	 */
	public function download() {
		/* 权限判断 */
		$this->admin_priv('sale_general_stats', ecjia::MSGTYPE_JSON);

		$start_time = RC_Time::local_strtotime($_GET['start_time']);
		$end_time   = RC_Time::local_strtotime($_GET['end_time']);
		$query_type = $_GET['query_type'];

		/* 分组统计订单数和销售额：已发货时间为准 */
		$format = ($query_type == 'year') ? '%Y' : '%Y-%m';
		$where =  " (order_status = '" . OS_CONFIRMED . "' OR order_status >= '" . OS_SPLITED . "' ) AND ( pay_status = '" . PS_PAYED . "' OR pay_status = '" . PS_PAYING . "') AND (shipping_status = '" . SS_SHIPPED . "' OR shipping_status = '" . SS_RECEIVED . "' ) AND (shipping_time >= ' ". $start_time ."' AND shipping_time <= '" .$end_time. "'  )";
		$where .= " AND is_delete = 0";

		$data_list = RC_DB::table('order_info')
			->select(RC_DB::raw("DATE_FORMAT(FROM_UNIXTIME(shipping_time), '". $format ."') AS period, COUNT(*) AS order_count, SUM(goods_amount + shipping_fee + insure_fee + pay_fee + pack_fee + card_fee - discount) AS order_amount"))
			->whereRaw($where)
			->groupby('period')
			->get();

		/* 文件名 */
		$filename = mb_convert_encoding(RC_Lang::get('orders::statistic.sales_statistics').'_'.$_GET['start_time'].'至'.$_GET['end_time'], "GBK", "UTF-8");

		header("Content-type: application/vnd.ms-excel; charset=utf-8");
		header("Content-Disposition: attachment; filename=$filename.xls");

		/* 文件标题 */
		echo mb_convert_encoding(RC_Lang::get('orders::statistic.sales_statistics'), "GBK", "UTF-8") . "\t\n";

		/* 订单数量, 销售出商品数量, 销售金额 */
		echo mb_convert_encoding(RC_Lang::get('orders::statistic.period'), "GBK", "UTF-8") . "\t";
		echo mb_convert_encoding(RC_Lang::get('orders::statistic.order_count_trend'), "GBK", "UTF-8") . "\t";
		echo mb_convert_encoding(RC_Lang::get('orders::statistic.order_amount_trend'), "GBK", "UTF-8") . "\t\n";
		if (!empty($data_list)) {
			foreach ($data_list AS $data) {
				echo mb_convert_encoding($data['period'], "GBK", "UTF-8") . "\t";
				echo mb_convert_encoding($data['order_count'], "GBK", "UTF-8") . "\t";
				echo mb_convert_encoding($data['order_amount'], "GBK", "UTF-8") . "\t";
				echo "\n";
			}
		}
		exit;
	}
}

// end