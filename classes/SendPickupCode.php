<?php

namespace Ecjia\App\Orders;

use RC_Api;
use RC_DB;
use RC_Time;
use ecjia;

class SendPickupCode
{

    /**
     * 门店自提，发送提货验证码
     * @param array $order 订单信息
     * @return boolean
     */
    public static function send_pickup_code($order = array())
    {
    	if (!empty($order)) {
    		if (!empty($order['shipping_id'])) {
    			$shipping_info = RC_DB::table('shipping')->where('shipping_id', $order['shipping_id'])->first();
    			
    			if ($shipping_info['shipping_code'] == 'ship_cac') {
    				/*生成提货码*/
    				$db_term_meta = RC_DB::table('term_meta');
    				$max_code = $db_term_meta->where('object_type', 'ecjia.order')->where('object_group', 'order')->where('meta_key', 'receipt_verification')->max('meta_value');
    				 
    				$max_code = $max_code ? ceil($max_code/10000) : 1000000;
    				$code = $max_code . str_pad(mt_rand(0, 9999), 4, '0', STR_PAD_LEFT);
    				$meta_data = array(
    						'object_type'	=> 'ecjia.order',
    						'object_group'	=> 'order',
    						'object_id'		=> $order['order_id'],
    						'meta_key'		=> 'receipt_verification',
    						'meta_value'	=> $code,
    				);
    				$db_term_meta->insert($meta_data);
    				/*短信给用户发送收货验证码*/
    				$mobile = RC_DB::table('users')->where('user_id', $order['user_id'])->pluck('mobile_phone');
    				if (!empty($mobile)) {
    					$options = array(
    							'mobile' => $mobile,
    							'event'	 => 'sms_order_pickup',
    							'value'  =>array(
    									'order_sn'  	=> $order['order_sn'],
    									'user_name' 	=> $order['consignee'],
    									'code'  		=> $code,
    									'service_phone' => ecjia::config('service_phone'),
    							),
    					);
    					RC_Api::api('sms', 'send_event_sms', $options);
    				}
    			}
    		}
    	}
    } 
    
}