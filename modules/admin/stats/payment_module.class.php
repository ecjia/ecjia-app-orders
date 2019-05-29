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
 * //收银台收银统计
 * @author will.chen
 *
 */
class admin_stats_payment_module extends api_admin implements api_interface
{
    public function handleRequest(\Royalcms\Component\HttpKernel\Request $request)
    {
        $this->authadminSession();
        if ($_SESSION['staff_id'] <= 0) {
            return new ecjia_error(100, __('Invalid session', 'orders'));
        }

        $device = $this->device;
        $codes  = config('app-cashier::cashier_device_code');

        if (!in_array($device['code'], $codes)) {
            $result = $this->admin_priv('order_stats');
            if (is_ecjia_error($result)) {
                return $result;
            }
        }

        //传入参数
        $start_date = $this->requestData('start_date');
        $end_date   = $this->requestData('end_date');
// 		$start_date = $end_date = '2016-05-23';
        if (empty($start_date) || empty($end_date)) {
            return new ecjia_error('invalid_parameter', __('参数错误', 'orders'));
        }
        $cache_key = 'cashdesk_stats_' . md5($start_date . $end_date);
        $data      = RC_Cache::app_cache_get($cache_key, 'stats');
        $data      = null;
        if (empty($data)) {
            $device   = $this->device;
            $response = $this->payment_stats($start_date, $end_date, $device);
            RC_Cache::app_cache_set($cache_key, $response, 'stats', 60);
        } else {
            $response = $data;
        }
        return $response;
    }

    private function payment_stats($start_date, $end_date, $device)
    {
        $type       = $start_date == $end_date ? 'time' : 'day';
        $start_date = RC_Time::local_strtotime($start_date . ' 00:00:00');
        $end_date   = RC_Time::local_strtotime($end_date . ' 23:59:59');

        /* 获取请求当前数据的device信息*/
        $codes = RC_Loader::load_app_config('cashier_device_code', 'cashier');
        if (!is_array($device) || !isset($device['code']) || !in_array($device['code'], $codes)) {
            return new ecjia_error('caskdesk_error', __('非收银台请求！', 'orders'));
        }

        /* 获取收银台的固有支付方式*/
        $cashdesk_payment = array('pay_cash', 'pay_koolyun_alipay', 'pay_koolyun_unionpay', 'pay_koolyun_wxpay', 'pay_balance', 'pay_shouqianba');
        $pay_id_group     = RC_DB::table('payment')->where('enabled', 1)->whereIn('pay_code', $cashdesk_payment)->select('pay_code', 'pay_id', 'pay_name')->get();
        $pay_id_group_new = [];
        if (!empty($pay_id_group)) {
            foreach ($pay_id_group as $key => $value) {
                $pay_id_group_new[$value['pay_code']] = $value;
            }
        }

        /* 定义默认数据*/
        $data        = array();
        $device_type = Ecjia\App\Cashier\CashierDevice::get_device_type($device['code']);

        $field = 'count(*) as count, SUM((goods_amount - discount + tax + shipping_fee + insure_fee + pay_fee + pack_fee + card_fee)) AS total_fee';
        foreach ($cashdesk_payment as $val) {
            if (isset($pay_id_group_new[$val])) {
                $dbview = RC_DB::table('cashier_record as cr')
                    ->leftJoin('order_info as oi', RC_DB::raw('cr.order_id'), '=', RC_DB::raw('oi.order_id'));

                $dbview->where(RC_DB::raw('oi.pay_status'), 2)
                    ->where(RC_DB::raw('oi.pay_time'), '>=', $start_date)
                    ->where(RC_DB::raw('oi.pay_time'), '<=', $end_date)
                    ->where(RC_DB::raw('cr.store_id'), $_SESSION['store_id'])
                    ->where(RC_DB::raw('cr.order_type'), 'buy')
                    ->where('pay_id', $pay_id_group_new[$val]['pay_id']);

                //收银通不区分设备；收银台和POS机区分设备
                if ($device['code'] == Ecjia\App\Cashier\CashierDevice::CASHIERCODE) {
                    $dbview->where(RC_DB::raw('cr.device_type'), $device_type);
                } else {
                    $dbview->where(RC_DB::raw('cr.mobile_device_id'), $_SESSION['device_id']);
                }

                $order_stats = $dbview
                    ->select(RC_DB::raw('count("DISTINCT cr.order_id") as count'), RC_DB::raw('SUM((goods_amount - discount + tax + shipping_fee + insure_fee + pay_fee + pack_fee + card_fee)) AS total_fee'))
                    ->first();

                $data[] = array(
                    'pay_code'               => $val,
                    'pay_name'               => $pay_id_group_new[$val]['pay_name'],
                    'order_count'            => $order_stats['count'],
                    'order_amount'           => $order_stats['total_fee'] > 0 ? $order_stats['total_fee'] : '0.00',
                    'formatted_order_amount' => price_format($order_stats['total_fee'], false),
                );
            }
        }


        return $data;
    }

}

