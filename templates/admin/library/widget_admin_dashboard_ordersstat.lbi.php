<?php defined('IN_ECJIA') or exit('No permission resources.');?>
<div class="move-mod-group" id="widget_admin_dashboard_ordersstat">
	<div class="heading clearfix move-mod-head">
		<h3 class="pull-left">{$title}</h3>
		<span class="pull-right label label-important">{$order_count}</span>
	</div>

	<table class="table table-bordered mediaTable dash-table-oddtd">
		<thead>
			<tr>
				<th colspan="4" class="optional">订单统计信息</th>
			</tr>
		</thead>
		<tbody>
			<tr>
				<td><a href='{url path="orders/admin/init" args="composite_status={$status.await_ship}"}' title="待发货订单">待发货订单</a></td>
				<td class="dash-table-color"><strong>{$order.await_ship}</strong></td>
				<td><a href='{url path="orders/admin/init" args="composite_status={$status.unconfirmed}"}' title="未接单订单">未接单订单</a></td>
				<td><strong>{$order.unconfirmed}</strong></td>
			</tr>
			<tr>
				<td><a href='{url path="orders/admin/init" args="composite_status={$status.await_pay}"}' title="待支付订单">待支付订单</a></td>
				<td><strong>{$order.await_pay}</strong></td>
				<td><a href='{url path="orders/admin/init" args="composite_status={$status.finished}"}' title="已成交订单数">已成交订单数</a></td>
				<td><strong>{$order.finished}</strong></td>
			</tr>
			<tr>
				<td><a href='{url path="orders/admin/init" args="composite_status={$status.shipped_part}"}' title="部分发货订单">部分发货订单</a></td>
				<td><strong>{$order.shipped_part}</strong></td>
				<td><a href='{url path="refund/admin/init"}' title="退款申请">退款申请</a></td>
				<td><strong>{$new_repay}</strong></td>
			</tr>
		</tbody>
	</table>
</div>