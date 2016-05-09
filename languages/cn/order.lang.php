<?php
defined('IN_ECJIA') or exit('No permission resources.');

/**
 * ECJIA 订单管理语言文件
 */

return array(
	'orders' 		=> '订单',
	'orders_desc'   => '订单功能描述',
	
	/* 订单搜索 */
	'order_sn' 		=> '订单号',
	'consignee' 	=> '收货人',
	'all_status' 	=> '订单状态',
	'thumb_img' 	=> '缩略图',
		
	'cs' => array(
		OS_UNCONFIRMED 	=> '待确认',
		CS_AWAIT_PAY 	=> '待付款',
		CS_AWAIT_SHIP 	=> '待发货',
		CS_FINISHED 	=> '已完成',
		PS_PAYING 		=> '付款中',
		OS_CANCELED 	=> '取消',
		OS_INVALID 		=> '无效',
		OS_RETURNED 	=> '退货',
		OS_SHIPPED_PART => '部分发货',
	),
		
	/* 订单状态 */
	'os' => array(
		OS_UNCONFIRMED 	=> '未确认',
		OS_CONFIRMED 	=> '已确认',
		OS_CANCELED 	=> '<font color="red">取消</font>',
		OS_INVALID 		=> '<font color="red">无效</font>',
		OS_RETURNED 	=> '<font color="red">退货</font>',
		OS_SPLITED 		=> '已分单',
		OS_SPLITING_PART => '部分分单',
	),
		
	'ss' => array(
		SS_UNSHIPPED 	=> '未发货',
		SS_PREPARING 	=> '配货中',
		SS_SHIPPED 		=> '已发货',
		SS_RECEIVED 	=> '收货确认',
		SS_SHIPPED_PART => '已发货(部分商品)',
		SS_SHIPPED_ING 	=> '发货中',
	),
		
	'ps' => array(
		PS_UNPAYED 	=> '未付款',
		PS_PAYING	=> '付款中',
		PS_PAYED 	=> '已付款',
	),
		
	'ss_admin' => array(
		SS_SHIPPED_ING => '发货中（前台状态：未发货）'
	),
				
	/* 订单操作 */
	'label_operable_act'	=> '当前可执行操作：',
	'label_action_note' 	=> '操作备注：',
	'label_invoice_note' 	=> '发货备注：',
	'label_invoice_no' 		=> '发货单号：',
	'label_cancel_note'	 	=> '取消原因：',
	'notice_cancel_note' 	=> '（会记录在商家给客户的留言中）',
	
	'op_confirm' 	=> '确认',
	'op_pay' 		=> '付款',
	'op_prepare' 	=> '配货',
	'op_ship' 		=> '发货',
	'op_cancel' 	=> '取消',
	'op_invalid' 	=> '无效',
	'op_return' 	=> '退货',
	'op_unpay' 		=> '设为未付款',
	'op_unship' 	=> '未发货',
	
	'op_cancel_ship' 	=> '取消发货',
	'op_receive' 		=> '已收货',
	'op_assign' 		=> '指派给',
	'op_after_service' 	=> '售后',
	'act_ok' 			=> '操作成功',
	'act_false' 		=> '操作失败',
	'act_ship_num' 		=> '此单发货数量不能超出订单商品数量',
	'act_good_vacancy' 	=> '商品已缺货',
	'act_good_delivery' => '货已发完',
	'notice_gb_ship' 	=> '备注：团购活动未处理为成功前，不能发货',
	'back_list' 		=> '返回订单列表',
	'op_remove' 		=> '删除',
	'op_you_can' 		=> '您可进行的操作',
	'op_split' 			=> '生成发货单',
	'op_to_delivery' 	=> '去发货',
		
	/* 订单列表 */
	'order_amount' 		=> '应付金额',
	'total_fee' 		=> '总金额',
	'shipping_name' 	=> '配送方式',
	'pay_name' 			=> '支付方式',
	'address' 			=> '地址',
	'order_time' 		=> '下单时间',
	'detail' 			=> '查看',
	'phone' 			=> '电话',
	'group_buy' 		=> '（团购）',
	'error_get_goods_info' 	=> '获取订单商品信息错误',
	'exchange_goods' 		=> '（积分兑换）',
		
	'js_languages' 		=> array('remove_confirm' 	=> '删除订单将清除该订单的所有信息。您确定要这么做吗？'),
	'merge_confirm' 	=> '您确定要合并这两个订单吗？',
	'action_note_sure' 	=> '请输入操作备注！',
	'back_order_info' 	=> '返回订单详情！',
		
	/* 订单搜索 */
	'label_order_sn' 	=> '订单号：',
	'label_all_status' 	=> '订单状态：',
	'label_user_name' 	=> '购货人：',
	'label_consignee' 	=> '收货人：',
	'label_email' 		=> '电子邮件：',
	'label_address' 	=> '地址：',
	'label_zipcode' 	=> '邮编：',
	'label_tel' 		=> '电话：',
	'label_mobile' 		=> '手机：',
	'label_shipping' 	=> '配送方式：',
	'label_payment' 	=> '支付方式：',
	'label_order_status' 	=> '订单状态：',
	'label_pay_status' 		=> '付款状态：',
	'label_shipping_status' => '发货状态：',
	'label_area' 		=> '所在地区：',
	'label_time' 		=> '下单时间：',
	
	/* 订单详情 */
	'prev' 				=> '前一个订单',
	'next' 				=> '后一个订单',
	'print_order' 		=> '打印订单',
	'print_shipping' 	=> '打印快递单',
	'print_order_sn' 	=> '订单编号：',
	'print_buy_name' 	=> '购 货 人：',
	'label_consignee_address' => '收货地址：',
	'no_print_shipping' => '很抱歉,目前您还没有设置打印快递单模板.不能进行打印',
	'suppliers_no' 		=> '不指定供货商本店自行处理',
	'restaurant' 		=> '本店',
	
	'order_info' 	=> '订单信息',
	'base_info' 	=> '基本信息',
	'other_info' 	=> '其他信息',
	'consignee_info'=> '收货人信息',
	'fee_info' 		=> '费用信息',
	'action_info' 	=> '操作信息',
	'shipping_info' => '配送信息',
	
	'label_how_oos' 		=> '缺货处理：',
	'label_how_surplus' 	=> '余额处理：',
	'label_pack' 			=> '包装：',
	'label_card' 			=> '贺卡：',
	'label_card_message' 	=> '贺卡祝福语：',
	'label_order_time' 		=> '下单时间：',
	'label_pay_time' 		=> '付款时间：',
	'label_shipping_time' 	=> '发货时间：',
	'label_sign_building' 	=> '标志性建筑：',
	'label_best_time' 		=> '最佳送货时间：',
	'label_inv_type' 		=> '发票类型：',
	'label_inv_payee' 		=> '发票抬头：',
	'label_inv_content' 	=> '发票内容：',
	'label_postscript' 		=> '客户给商家的留言：',
	'label_region' 			=> '所在地区：',
	
	'label_shop_url' 		=> '网址：',
	'label_shop_address' 	=> '地址：',
	'label_service_phone' 	=> '电话：',
	'label_print_time' 		=> '打印时间：',
	
	'label_suppliers' 		=> '选择供货商：',
	'label_agency' 			=> '办事处：',
	'suppliers_name' 		=> '供货商',
	
	'product_sn' 			=> '货品号',
	'goods_info' 			=> '商品信息',
	'goods_name' 			=> '商品名称',
	'goods_name_brand' 		=> '商品名称 [ 品牌 ]',
	'goods_sn' 				=> '货号',
	'goods_price' 			=> '价格',
	'goods_number' 			=> '数量',
	'goods_attr' 			=> '属性',
	'goods_delivery' 		=> '已发货数量',
	'goods_delivery_curr' 	=> '此单发货数量',
	'storage' 				=> '库存',
	'subtotal' 				=> '小计',
	'label_total' 			=> '合计：',
	'label_total_weight' 	=> '商品总重量：',
	
	'label_goods_amount' 	=> '商品总金额：',
	'label_discount' 		=> '折扣：',
	'label_tax' 			=> '发票税额：',
	'label_shipping_fee' 	=> '配送费用：',
	'label_insure_fee' 		=> '保价费用：',
	'label_insure_yn' 		=> '是否保价：',
	'label_pay_fee' 			=> '支付费用：',
	'label_pack_fee' 		=> '包装费用：',
	'label_card_fee' 		=> '贺卡费用：',
	'label_money_paid' 		=> '已付款金额：',
	'label_surplus' 			=> '使用余额：',
	'label_integral' 		=> '使用积分：',
	'label_bonus' 			=> '使用红包：',
	'label_order_amount' 	=> '订单总金额：',
	'label_money_dues' 		=> '应付款金额：',
	'label_money_refund' 	=> '应退款金额：',
	'label_to_buyer' 		=> '商家给客户的留言：',
	'save_order' 			=> '保存订单',
	'notice_gb_order_amount' => '（备注：团购如果有保证金，第一次只需支付保证金和相应的支付费用）',
	
	'action_user' 		=> '操作者：',
	'action_time' 		=> '操作时间',
	'order_status' 		=> '订单状态',
	'pay_status' 		=> '付款状态',
	'shipping_status' 	=> '发货状态',
	'action_note' 		=> '备注',
	'pay_note' 			=> '支付备注：',
	
	'sms_time_format' 	=> 'm月j日G时',
	'order_shipped_sms' => '您的订单%s已于%s发货 [%s',
	'order_splited_sms' => '您的订单%s,%s正在%s [%s',
	'order_removed' 	=> '订单删除成功。',
	'return_list' 		=> '返回订单列表',
	
	/* 订单处理提示 */
	'surplus_not_enough'	=> '该订单使用 %s 余额支付，现在用户余额不足',
	'integral_not_enough' 	=> '该订单使用 %s 积分支付，现在用户积分不足',
	'bonus_not_available' 	=> '该订单使用红包支付，现在红包不可用',
	
	/* 购货人信息 */
	'display_buyer' => '显示购货人信息',
	'buyer_info' 	=> '购货人信息',
	'pay_points' 	=> '消费积分',
	'rank_points' 	=> '等级积分',
	'user_money' 	=> '账户余额',
	'email' 		=> '电子邮件',
	'rank_name' 	=> '会员等级',
	'bonus_count' 	=> '红包数量',
	'zipcode' 		=> '邮编',
	'tel' 			=> '电话',
	'mobile' 		=> '备用电话',
	
	/* 合并订单 */
	'order_sn_not_null'		=> '请填写要合并的订单号',
	'two_order_sn_same'		=> '要合并的两个订单号不能相同',
	'order_not_exist' 		=> '定单 %s 不存在',
	'os_not_unconfirmed_or_confirmed' => '%s 的订单状态不是“未确认”或“已确认”',
	'ps_not_unpayed' 		=> '订单 %s 的付款状态不是“未付款”',
	'ss_not_unshipped' 		=> '订单 %s 的发货状态不是“未发货”',
	'order_user_not_same' 	=> '要合并的两个订单不是同一个用户下的',
	'merge_invalid_order' 	=> '对不起，您选择合并的订单不允许进行合并的操作。',
	
	'from_order_sn' 	=> '从订单：',
	'to_order_sn' 		=> '主订单：',
	'merge' 			=> '合并',
	'notice_order_sn' 	=> '当两个订单不一致时，合并后的订单信息（如：支付方式、配送方式、包装、贺卡、红包等）以主订单为准。',
	
	/* 批处理 */
	'pls_select_order' 	=> '请选择您要操作的订单',
	'no_fulfilled_order'=> '没有满足操作条件的订单。',
	'updated_order' 	=> '更新的订单：',
	'order' 			=> '订单：',
	'confirm_order' 	=> '有订单无法设置为确认状态',
	'invalid_order' 	=> '有订单无法设置为无效',
	'cancel_order' 		=> '有订单无法取消',
	'remove_order' 		=> '有订单无法被移除',
	'check_info' 		=> '查看详情',
	
	/* 编辑订单打印模板 */
	'edit_order_templates' 	=> '编辑订单打印模板',
	'template_resetore' 	=> '还原模板',
	'edit_template_success' => '编辑订单打印模板操作成功!',
	'remark_fittings' 	=> '（配件）',
	'remark_gift' 		=> '（赠品）',
	'remark_favourable' => '（特惠品）',
	'remark_package' 	=> '（礼包）',
	
	/* 订单来源统计 */
	'from_order' 	=> '订单来源：',
	'from_ad_js' 	=> '广告：',
	'from_goods_js' => '商品站外JS投放',
	'from_self_site'=> '来自本站',
	'from' 			=> '来自站点：',
		
	/* 添加、编辑订单 */
	'add_order'		=> '添加订单',
	'edit_order' 	=> '编辑订单',
	
	'step' => array(
		'user' 		=> '请选择您要为哪个会员下订单',
		'goods' 	=> '选择商品',
		'consignee' => '设置收货人信息',
		'shipping' 	=> '选择配送方式',
		'payment' 	=> '选择支付方式',
		'other' 	=> '设置其他信息',
		'money' 	=> '设置费用',
	),
		
	'anonymous' 	=> '匿名用户',
	'by_useridname' => '按会员编号或会员名搜索',
	'button_prev' 	=> '上一步',
	'button_next' 	=> '下一步',
	'button_finish' => '完成',
	'button_cancel' => '取消',
	'name' 			=> '名称',
	'desc' 			=> '描述',
	'shipping_fee' 	=> '配送费',
	'free_money'	=> '免费额度',
	'insure' 		=> '保价费',
	'pay_fee' 		=> '手续费',
	'pack_fee' 		=> '包装费',
	'card_fee' 		=> '贺卡费',
	'no_pack' 		=> '不要包装',
	'no_card' 		=> '不要贺卡',
	'add_to_order' 	=> '加入订单',
	'calc_order_amount' => '计算订单金额',
	'available_surplus' => '可用余额：',
	'available_integral'=> '可用积分：',
	'available_bonus' 	=> '可用红包：',
	'admin' 			=> '管理员添加',
	'search_goods' 		=> '按商品编号或商品名称或商品货号搜索',
	'category' 			=> '分类',
	'brand' 			=> '品牌',
	'user_money_not_enough' 	=> '用户余额不足',
	'pay_points_not_enough' 	=> '用户积分不足',
	'money_paid_enough' 		=> '已付款金额比商品总金额和各种费用之和还多，请先退款',
	'price_note' 				=> '备注：商品价格中已包含属性加价',
	'select_pack' 				=> '选择包装',
	'select_card' 				=> '选择贺卡',
	'select_shipping' 			=> '请先选择配送方式',
	'want_insure' 				=> '我要保价',
	'update_goods' 				=> '更新商品',
	'notice_user' 				=> '<strong>注意：</strong>搜索结果只显示前50条记录，如果没有找到相' .
			'应会员，请更精确地查找。另外，如果该会员是从论坛注册的且没有在商城登录过，' .
			'也无法找到，需要先在商城登录。',
	'amount_increase' 			=> '由于您修改了订单，导致订单总金额增加，需要再次付款',
	'amount_decrease' 			=> '由于您修改了订单，导致订单总金额减少，需要退款',
	'continue_shipping' 		=> '由于您修改了收货人所在地区，导致原来的配送方式不再可用，请重新选择配送方式',
	'continue_payment' 			=> '由于您修改了配送方式，导致原来的支付方式不再可用，请重新选择支付方式',
	'refund' 					=> '退款',
	'cannot_edit_order_shipped' => '您不能修改已发货的订单',
	'address_list' 				=> '从已有收货地址中选择：',
	'order_amount_change' 		=> '订单总金额由 %s 变为 %s',
	'shipping_note' 			=> '说明：因为订单已发货，修改配送方式将不会改变配送费和保价费。',
	'change_use_surplus' 		=> '编辑订单 %s ，改变使用预付款支付的金额',
	'change_use_integral' 		=> '编辑订单 %s ，改变使用积分支付的数量',
	'return_order_surplus' 		=> '由于取消、无效或退货操作，退回支付订单 %s 时使用的预付款',
	'return_order_integral' 	=> '由于取消、无效或退货操作，退回支付订单 %s 时使用的积分',
	'order_gift_integral' 		=> '订单 %s 赠送的积分',
	'return_order_gift_integral'=> '由于退货或未发货操作，退回订单 %s 赠送的积分',
	'invoice_no_mall' 			=> '&nbsp,&nbsp,&nbsp,&nbsp,多个发货单号，请用英文逗号（“,”）隔开。',
		
	'js_languages' => array(
		'input_price' 			=> '自定义价格',
		'pls_search_user' 		=> '请搜索并选择会员',
		'confirm_drop' 			=> '确认要删除该商品吗？',
		'invalid_goods_number' 	=> '商品数量不正确',
		'pls_search_goods' 		=> '请搜索并选择商品',
		'pls_select_area' 		=> '请完整选择所在地区',
		'pls_select_shipping' 	=> '请选择配送方式',
		'pls_select_payment' 	=> '请选择支付方式',
		'pls_select_pack' 		=> '请选择包装',
		'pls_select_card' 		=> '请选择贺卡',
		'pls_input_note' 		=> '请您填写备注！',
		'pls_input_cancel' 		=> '请您填写取消原因！',
		'pls_select_refund' 	=> '请选择退款方式！',
		'pls_select_agency' 	=> '请选择办事处！',
		'pls_select_other_agency' => '该订单现在就属于这个办事处，请选择其他办事处！',
		'loading' 				=> '加载中...',
	),
			
	/* 订单操作 */
	'order_operate' 		=> '订单操作：',
	'label_refund_amount' 	=> '退款金额：',
	'label_handle_refund' 	=> '退款方式：',
	'label_refund_note' 	=> '退款说明：',
	'return_user_money' 	=> '退回用户余额',
	'create_user_account'	=> '生成退款申请',
	
	'not_handle' 	=> '不处理，误操作时选择此项',
	'order_refund' 	=> '订单退款：%s',
	'order_pay' 	=> '订单支付：%s',
	'send_mail_fail'=> '发送邮件失败',
	'send_message' 	=> '发送/查看留言',
	
	
	/* 发货单操作 */
	'delivery_operate' 	=> '发货单操作：',
	'delivery_sn_number'=> '发货单流水号：',
	'invoice_no_sms' 	=> '请填写发货单号！',
	
	/* 发货单搜索 */
	'delivery_sn' => '发货单',
	
	/* 发货单状态 */
	'delivery_status' => array(
		0 	=> '已发货',
		1 	=> '退货',
		2 	=> '正常',
	),
	
	/* 发货单标签 */
	'label_delivery_status' => '发货单状态',
	'label_suppliers_name' 	=> '供货商',
	'label_delivery_time' 	=> '生成时间',
	'label_delivery_sn' 	=> '发货单流水号',
	
	'label_add_time' 	=> '下单时间',
	'label_update_time' => '发货时间',
	'label_send_number' => '发货数量',
	'tips_delivery_del' => '发货单删除成功！',
		
	/* 退货单操作 */
	'back_operate'		=> '退货单操作：',
	
	/* 退货单标签 */
	'return_time' 		=> '退货时间：',
	'label_return_time'	=> '退货时间',
	
	/* 退货单提示 */
	'tips_back_del' 	=> '退货单删除成功！',
	'goods_num_err' 	=> '库存不足，请重新选择！',
	//追加
	'action_user_two' 		=> '操作者',
	'op' => array(
		'confirm' 	=> '确认',
		'pay' 		=> '付款',
		'prepare' 	=> '配货',
		'ship' 		=> '发货',
		'cancel' 	=> '取消',
		'invalid' 	=> '无效',
		'return' 	=> '退货',
		'unpay' 		=> '设为未付款',
		'unship' 	=> '未发货',
		
		'cancel_ship' 	=> '取消发货',
		'receive' 		=> '已收货',
		'assign' 		=> '指派给',
		'after_service' 	=> '售后',
		'remove' 		=> '删除',
		'you_can' 		=> '您可进行的操作',
		'split' 			=> '生成发货单',
		'to_delivery' 	=> '去发货',
	),
);

// end