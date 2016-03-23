<?php defined('IN_ECJIA') or exit('No permission resources.');?>
<div class="move-mod-group" id="widget_admin_dashboard_ordersstat">
	<div class="heading clearfix move-mod-head">
		<h3 class="pull-left">{$title}</h3>
		<span class="pull-right label label-important">{$order_count}</span>
	</div>

	<table class="table table-bordered mediaTable dash-table-oddtd">
		<thead>
			<tr>
				<th colspan="4" class="optional">{t}订单统计信息{/t}</th>
			</tr>
		</thead>
		<tbody>
			<tr>
				<td><a href='{url path="orders/admin/init" args="composite_status={$status.await_ship}"}' title="{t}待发货订单{/t}">{t}待发货订单{/t}</a></td>
				<td class="dash-table-color"><strong>{$order.await_ship}</strong></td>
				<td><a href='{url path="orders/admin/init" args="composite_status={$status.unconfirmed}"}' title="{t}未确认订单{/t}">{t}未确认订单{/t}</a></td>
				<td><strong>{$order.unconfirmed}</strong></td>
			</tr>
			<tr>
				<td><a href='{url path="orders/admin/init" args="composite_status={$status.await_pay}"}' title="{t}待支付订单{/t}">{t}待支付订单{/t}</a></td>
				<td><strong>{$order.await_pay}</strong></td>
				<td><a href='{url path="orders/admin/init" args="composite_status={$status.finished}"}' title="{t}已成交订单数{/t}">{t}已成交订单数{/t}</a></td>
				<td><strong>{$order.finished}</strong></td>
			</tr>
			<tr>
				<td><a href='{url path="orders/admin_goods_booking/init"}' title="{t}新缺货登记{/t}">{t}新缺货登记{/t}</a></td>
				<td><strong>{$booking_goods}</strong></td>
				<td><a href='{url path="user/admin_account/init" args="process_type=1&is_paid=0"}' title="{t}退款申请{/t}">{t}退款申请{/t}</a></td>
				<td><strong>{$new_repay}</strong></td>
			</tr>
			<tr>
				<td><a href='{url path="orders/admin/init" args="composite_status={$status.shipped_part}"}' title="{t}部分发货订单{/t}">{t}部分发货订单{/t}</a></td>
				<td colspan="3"><strong>{$order.shipped_part}</strong></td>
			</tr>
		</tbody>
	</table>
</div>