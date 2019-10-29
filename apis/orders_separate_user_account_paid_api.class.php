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
use Ecjia\System\Notifications\OrderPay;

defined('IN_ECJIA') or exit('No permission resources.');


/**
 * 余额支付后处理主订单的接口
 * @author hyy
 */
class orders_separate_user_account_paid_api extends Component_Event_Api
{

    /**
     * @param  $options ['user_id'] 会员id
     *         $options['order_sn'] 订单sn
     * @return array|ecjia_error
     */
    public function call(&$options)
    {
        if (!is_array($options)
            || !isset($options['user_id'])
            || !isset($options['order_sn'])) {
            return new ecjia_error('invalid_parameter', sprintf(__('请求接口%s参数无效', 'orders'), __CLASS__));
        }

        $result = $this->user_account_paid($options['user_id'], $options['order_sn']);

        if (is_ecjia_error($result)) {
            return $result;
        } else {
            return true;
        }

    }

    /**
     * 余额支付
     *
     * @access  public
     * @param   integer $user_id 用户id
     * @param   integer $order_id 订单id
     * @return  void
     */
    private function user_account_paid($user_id, $order_sn)
    {
        /* 订单详情 */
        $order_info = RC_Api::api('orders', 'separate_order_info', array('order_sn' => $order_sn));
        if ($user_id != $order_info['user_id']) {
            return new ecjia_error('error_order_detail', __('订单不属于该用户', 'orders'));
        }

        /* 会员详情*/
        $user_info = RC_Api::api('user', 'user_info', array('user_id' => $user_id));

        /* 检查订单是否已经付款 */
        if ($order_info['pay_status'] == PS_PAYED && $order_info['pay_time']) {
            return new ecjia_error('order_paid', __('该订单已经支付，请勿重复支付。', 'orders'));
        }

        /* 检查订单金额是否大于余额 */
        if ($order_info['order_amount'] > ($user_info['user_money'] + $user_info['credit_line'])) {
            return new ecjia_error('balance_less', __('您的余额不足以支付整个订单，请选择其他支付方式。', 'orders'));
        }
        $params = [
            'order_sn'   => $order_sn,
            'order_info' => $order_info,
            'money'      => $order_info['order_amount'],
            'pay_code'   => 'pay_balance',
        ];
        $result = RC_Api::api('orders', 'separate_order_paid', $params);
        if (is_ecjia_error($result)) {
            //失败返还TODO
            return $result;
        }

        //支付后扩展处理
//        RC_Hook::do_action('order_payed_do_something', $order_info);

        return true;
    }
}

// end