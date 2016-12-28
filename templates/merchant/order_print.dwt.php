<?php defined('IN_ECJIA') or exit('No permission resources.');?>
<meta charset="UTF-8">
<title>打印订单</title>
<style type="text/css">
body,td { font-size:13px; }
</style>

<h1 align="center">{$lang.order_info}</h1>
<table width="100%" cellpadding="1">
	<tr>
		<td width="8%">{$lang.print_buy_name}</td>
		<td>{if $order.user_name}{$order.user_name}{else}{$lang.anonymous}{/if}<!-- 购货人姓名 --></td>
		<td align="right">{$lang.label_order_time}</td><td>{$order.order_time}<!-- 下订单时间 --></td>
		<td align="right">{$lang.label_payment}</td><td>{$order.pay_name}<!-- 支付方式 --></td>
		<td align="right">{$lang.print_order_sn}</td><td>{$order.order_sn}<!-- 订单号 --></td>
	</tr>
	<tr>
		<td>{$lang.label_pay_time}</td><td>{$order.pay_time}</td><!-- 付款时间 -->
		<td align="right">{$lang.label_shipping_time}</td><td>{$order.shipping_time}<!-- 发货时间 --></td>
		<td align="right">{$lang.label_shipping}</td><td>{$order.shipping_name}<!-- 配送方式 --></td>
		<td align="right">{$lang.label_invoice_no}</td><td>{$order.invoice_no} <!-- 发货单号 --></td>
	</tr>
	<tr>
		<td>{$lang.label_consignee_address}</td>
		<td colspan="7">
			[{$order.region}]&nbsp;{$order.address}&nbsp;<!-- 收货人地址 -->
			{$lang.label_consignee}{$order.consignee}&nbsp;<!-- 收货人姓名 -->
			{if $order.zipcode}{$lang.label_zipcode}{$order.zipcode}&nbsp;{/if}<!-- 邮政编码 -->
			{if $order.tel}{$lang.label_tel}{$order.tel}&nbsp; {/if}<!-- 联系电话 -->
			{if $order.mobile}{$lang.label_mobile}{$order.mobile}{/if}<!-- 手机号码 -->
		</td>
	</tr>
</table>
<table width="100%" border="1" style="border-collapse:collapse;border-color:#000;">
	<tr align="center">
		<td bgcolor="#cccccc">{$lang.goods_name}  <!-- 商品名称 --></td>
		<td bgcolor="#cccccc">{$lang.goods_sn}    <!-- 商品货号 --></td>
		<td bgcolor="#cccccc">{$lang.goods_attr}  <!-- 商品属性 --></td>
		<td bgcolor="#cccccc">{$lang.goods_price} <!-- 商品单价 --></td>
		<td bgcolor="#cccccc">{$lang.goods_number}<!-- 商品数量 --></td>
		<td bgcolor="#cccccc">{$lang.subtotal}    <!-- 价格小计 --></td>
	</tr>
	<!-- {foreach from=$goods_list item=goods key=key} -->
	<tr>
		<td>&nbsp;{$goods.goods_name}<!-- 商品名称 -->
			{if $goods.is_gift}{if $goods.goods_price gt 0}{$lang.remark_favourable}{else}{$lang.remark_gift}{/if}{/if}
			{if $goods.parent_id gt 0}{$lang.remark_fittings}{/if}
		</td>
		<td>&nbsp;{$goods.goods_sn} <!-- 商品货号 --></td>
		<td><!-- 商品属性 -->
			<!-- {foreach key=key from=$goods_attr[$key] item=attr} -->
			<!-- {if $attr.name} --> {$attr.name}:{$attr.value} <!-- {/if} -->
			<!-- {/foreach} -->
		</td>
		<td align="right">{$goods.formated_goods_price}&nbsp;<!-- 商品单价 --></td>
		<td align="right">{$goods.goods_number}&nbsp;<!-- 商品数量 --></td>
		<td align="right">{$goods.formated_subtotal}&nbsp;<!-- 商品金额小计 --></td>
	</tr>
	<!-- {/foreach} -->
	<tr>
		<!-- 发票抬头和发票内容 -->
		<td colspan="4">
			{if $order.inv_payee}
				{$lang.label_inv_payee}{$order.inv_payee}&nbsp;&nbsp;&nbsp;
				{$lang.label_inv_content}{$order.inv_content}
			{/if}
		</td>
		<!-- 商品总金额 -->
		<td colspan="2" align="right">{$lang.label_goods_amount}{$order.formated_goods_amount}</td>
	</tr>
</table>
<table width="100%" border="0">
	<tr align="right">
		<td>
			{if $order.discount gt 0}- {$lang.label_discount}{$order.formated_discount}{/if}{if $order.pack_name and $order.pack_fee neq '0.00'}
			<!-- 包装名称包装费用 -->
				+ {$lang.label_pack_fee}{$order.formated_pack_fee}
			{/if}
			{if $order.card_name and $order.card_fee neq '0.00'}<!-- 贺卡名称以及贺卡费用 -->
				+ {$lang.label_card_fee}{$order.formated_card_fee}
			{/if}
			{if $order.pay_fee neq '0.00'}<!-- 支付手续费 -->
				+ {$lang.label_pay_fee}{$order.formated_pay_fee}
			{/if}
			{if $order.shipping_fee neq '0.00'}<!-- 配送费用 -->
				+ {$lang.label_shipping_fee}{$order.formated_shipping_fee}
			{/if}
			{if $order.insure_fee neq '0.00'}<!-- 保价费用 -->
				+ {$lang.label_insure_fee}{$order.formated_insure_fee}
			{/if}
			<!-- 订单总金额 -->
			= {$lang.label_order_amount}{$order.formated_total_fee}
		</td>
	</tr>
	<tr align="right">
		<td>
			<!-- 如果已付了部分款项, 减去已付款金额 -->
			{if $order.money_paid neq '0.00'}- {$lang.label_money_paid}{$order.formated_money_paid}{/if}
			<!-- 如果使用了余额支付, 减去已使用的余额 -->
			{if $order.surplus neq '0.00'}- {$lang.label_surplus}{$order.formated_surplus}{/if}
			<!-- 如果使用了积分支付, 减去已使用的积分 -->
			{if $order.integral_money neq '0.00'}- {$lang.label_integral}{$order.formated_integral_money}{/if}
			<!-- 如果使用了红包支付, 减去已使用的红包 -->
			{if $order.bonus neq '0.00'}- {$lang.label_bonus}{$order.formated_bonus}{/if}
			<!-- 应付款金额 -->
			= {$lang.label_money_dues}{$order.formated_order_amount}
		</td>
	</tr>
</table>
<table width="100%" border="0">
	<!-- {if $order.to_buyer} -->
	<tr><!-- 给购货人看的备注信息 -->
		<td>{$lang.label_to_buyer}{$order.to_buyer}</td>
	</tr>
	<!-- {/if}  -->
	<!-- {if $order.invoice_note} -->
	<tr> <!-- 发货备注 -->
		<td>{$lang.label_invoice_note} {$order.invoice_note}</td>
	</tr>
	<!-- {/if} -->
	<!-- {if $order.pay_note} -->
	<tr> <!-- 支付备注 -->
		<td>{$lang.pay_note} {$order.pay_note}</td>
	</tr>
	<!-- {/if} -->

	<tr><!-- 网店名称, 网店地址, 网店URL以及联系电话 -->
		<td>
			{$shop_name}（{$shop_url}）
			{$lang.label_shop_address}{$shop_address}&nbsp;&nbsp;{$lang.label_service_phone}{$service_phone}
		</td>
	</tr>
	<tr align="right"><!-- 订单操作员以及订单打印的日期 -->
		<td>{$lang.label_print_time}{$print_time}&nbsp;&nbsp;&nbsp;{$lang.action_user}{$action_user}</td>
	</tr>
</table>