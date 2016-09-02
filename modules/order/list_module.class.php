<?php
defined('IN_ECJIA') or exit('No permission resources.');
/**
 * 订单列表
 * @author royalwang
 *
 */
class list_module extends api_front implements api_interface {
    public function handleRequest(\Royalcms\Component\HttpKernel\Request $request) {	
    	$this->authSession();
		
		$type = $this->requestdata('type');
		if (!empty($type) && !in_array($type, array('await_pay', 'await_ship', 'shipped', 'finished', 'unconfirmed'))) {
			EM_Api::outPut(101);
		}
		$page_parm = EM_Api::$pagination;
		$page = $page_parm['page'];
		$size = $page_parm['count'];
		
		
		
		$options = array('type' => $type, 'page' => $page, 'size' => $size);
		$result = RC_Api::api('orders', 'order_list', $options);
		if (is_ecjia_error($result)) {
			return $result;
		}
		
		$pager = array(
				'total' => $result['page']->total_records,
				'count' => $result['page']->total_records,
				'more'	=> $result['page']->total_pages <= $page ? 0 : 1,
		);
		
		EM_Api::outPut($result['order_list'], $pager);

	 }	
}
// end