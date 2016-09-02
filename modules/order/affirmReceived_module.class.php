<?php
defined('IN_ECJIA') or exit('No permission resources.');
/**
 * 订单确认收货
 * @author royalwang
 *
 */
class affirmReceived_module extends api_front implements api_interface {
    public function handleRequest(\Royalcms\Component\HttpKernel\Request $request) {	
    	$this->authSession();
		
		$user_id = $_SESSION['user_id'];
		$order_id = $this->requestdata('order_id', 0);
		
		$result = affirm_received($order_id, $user_id);	
		
		if (!is_ecjia_error($result)) {
		    return array();
		} else {
			EM_Api::outPut(8);
		}
	}
}


/**
 * 确认一个用户订单
 *
 * @access public
 * @param int $order_id
 *            订单ID
 * @param int $user_id
 *            用户ID
 *
 * @return bool $bool
 */
function affirm_received($order_id, $user_id = 0)
{
    $db = RC_Loader::load_app_model('order_info_model', 'orders');
    /* 查询订单信息，检查状态 */
    $order = $db->field('user_id, order_sn , order_status, shipping_status, pay_status')->find(array('order_id' => $order_id));

    // 如果用户ID大于 0 。检查订单是否属于该用户
    if ($user_id > 0 && $order['user_id'] != $user_id) {
        return new ecjia_error('no_priv', RC_Lang::lang('no_priv'));
    }    /* 检查订单 */
    elseif ($order['shipping_status'] == SS_RECEIVED) {
        return new ecjia_error('order_already_received', RC_Lang::lang('order_already_received'));
    } elseif ($order['shipping_status'] != SS_SHIPPED) {
        return new ecjia_error('order_invalid', RC_Lang::lang('order_invalid'));
    }     /* 修改订单发货状态为“确认收货” */
    else {
        $data = array(
            'shipping_status' => SS_RECEIVED
        );
        $query = $db->where(array('order_id' => $order_id))->update($data);
        if ($query) {
        	$db_order_status_log = RC_Loader::load_app_model('order_status_log_model', 'orders');
        	$order_status_data = array(
            		'order_status' => '确认收货',
            		'order_id' 	   => $order_id,
            		'message'	   => '商品已送达，请签收，感谢您下次光顾！',
            		'add_time'	   => RC_Time::gmtime()
            );
            $db_order_status_log->insert($order_status_data);
            /* 记录日志 */
        	RC_Loader::load_app_func('order', 'orders');
            order_action($order['order_sn'], $order['order_status'], SS_RECEIVED, $order['pay_status'], '', '买家');
            return true;
        } else {
            return new ecjia_error('database_query_error', $db->error());
        }
    }
}

// end