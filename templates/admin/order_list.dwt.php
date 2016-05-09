<?php defined('IN_ECJIA') or exit('No permission resources.');?>
<!-- {extends file="ecjia.dwt.php"} -->

<!-- {block name="footer"} -->
<script type="text/javascript">
	ecjia.admin.order.init();
</script>
<!-- {/block} -->

<!-- {block name="main_content"} -->
<div>
	<h3 class="heading">
		<!-- {if $ur_here}{$ur_here}{/if} -->
		<!-- {if $action_link} -->
		<a class="btn plus_or_reply data-pjax" href="{$action_link.href}"><i class="icon-search"></i>{$action_link.text}</a>
		<!-- {/if} -->
	</h3>
</div>
<!-- #BeginLibraryItem "/library/order_operate.lbi" --><!-- #EndLibraryItem -->
<div class="row-fluid batch" >
	<form action="{$search_action}" name="searchForm" method="post" >
		<div class="btn-group f_l m_r5">
			<a class="btn dropdown-toggle" data-toggle="dropdown" href="#">
				<i class="fontello-icon-cog"></i>{t}批量操作{/t}
				<span class="caret"></span>
			</a>
			<ul class="dropdown-menu operate_note" data-url='{url path="orders/admin/operate_note"}'>
				<li><a class="batch-del-btn" data-toggle="ecjiabatch" data-name="order_id" data-idClass=".checkbox:checked" data-url="{$form_action}&operation=confirm" data-msg="您确定要审批这些订单吗？" data-noSelectMsg="请选择需要操作的订单！" href="javascript:;"><i class="fontello-icon-ok"></i>{$lang.op_confirm}</a></li>
				<li><a class="batch-operate batch-operate-invalid" data-operatetype="invalid" data-url="{$form_action}&operation=invalid" data-invalid-msg="您确定需要把这些订单设置为无效吗？" href="javascript:;"><i class="fontello-icon-block"></i>{$lang.op_invalid}</a></li>
				<li><a class="batch-operate batch-operate-cancel" data-operatetype="cancel" data-url="{$form_action}&operation=cancel" data-cancel-msg="您确定要取消这些订单吗？" href="javascript:;"><i class="fontello-icon-cancel"></i>{$lang.op_cancel}</a></li>
				<li><a class="batch-del-btn" data-toggle="ecjiabatch" data-name="order_id" data-idClass=".checkbox:checked" data-url="{$form_action}&operation=remove" data-msg="删除订单将清除该订单的所有信息。您确定要这么做吗？" href="javascript:;"><i class="fontello-icon-trash"></i>{$lang.remove}</a></li>
				<li><a class="batch-print" data-url="{$form_action}&print=1" href="javascript:;"><i class="fontello-icon-print"></i>{$lang.print_order}</a></li>
			</ul>
			<input name="batch" type="hidden" value="1" />
		</div>
		<!-- 订单状态-->
		<select class="down-menu w120" name="status" id="select-rank">
			<option value="-1">{$lang.all_status}</option>
			<!-- {html_options options=$status_list selected=$order_list.filter.composite_status } -->
		</select>
		<a class="btn m_l5 screen-btn">{t}筛选{/t}</a>
		<div class="choose_list f_r" >
			<input type="text" name="keywords" value="{$order_list.filter.keywords}" placeholder="请输入订单号或者收货人"/> 
			<button class="btn" type="submit">{t}搜索订单{/t}</button>
		</div>
	</form>
</div>
<div class="row-fluid">
	<div class="span12">
		<form action="{$form_action}" name="orderpostForm" id="listForm" data-pjax-url="{$search_action}" method="post">
			<div class="row-fluid">
				<table class="table table-striped table-hide-edit">
					<thead>
						<tr>
							<th class="table_checkbox"><input type="checkbox" data-toggle="selectall" data-children=".checkbox" /></th>
							<th>{$lang.order_sn}</th>
							<th>{$lang.order_name}</th>
							<th>{$lang.order_time}</th>
							<th>{$lang.consignee}</th>
							<th>{$lang.total_fee}</th>
							<th>{$lang.order_amount}</th>
							<th>{$lang.all_status}</th>
						</tr>
					</thead>
					<tbody>
						<!-- {foreach from=$order_list.orders item=order key=okey} -->
						<tr>
							<td><input type="checkbox" class="checkbox" name="order_id[]"  value="{$order.order_id}" /></td>
							<td class="hide-edit-area">
								{$order.order_sn}{if $order.extension_code eq "group_buy"}{$lang.group_buy}{elseif $order.extension_code eq "exchange_goods"}{$lang.exchange_goods}{/if}
								{if $order.stet eq 1}<font style="color:#0e92d0;">(子订单)</font>{elseif $order.stet eq 2}<font style="color:#F00;"><span data-original-title="{foreach from=$order.children_order item=val}{$val};{/foreach}" data-toggle="tooltip">(主订单)</span></font>{/if}
								<div class="edit-list">
									<a href='{url path="orders/admin/info" args="order_id={$order.order_id}"}' class="data-pjax" title="{$lang.detail}">{t}查看详情{/t}</a>
									{if $order.can_remove}
									&nbsp;|&nbsp;
									<a class="ajaxremove ecjiafc-red" data-toggle="ajaxremove" data-msg='{t name="{$order.order_sn}"}您确定要删除订单[ %1 ]吗？{/t}' href='{url path="orders/admin/remove_order" args="order_id={$order.order_id}"}' title="{t}移除{/t}">{t}移除{/t}</a>
									{/if}
								</div>
							</td>
							<td>
								<!-- {if $order.shop_name} -->
								<font style="color:#F00;">{$order.shop_name}</font>
								<!-- {else} -->
								<font style="color:#0e92d0;">{t}自营{/t}</font>
								<!-- {/if} -->
							</td>
							<td>
								{$order.user_name}<br/>{$order.short_order_time}
							</td>
							<td align="left">
								{$order.consignee} [TEL：{$order.mobile}]<br/>{$order.address}
							</td>
							<td align="right" valign="top" nowrap="nowrap">{$order.formated_total_fee}</td>
							<td align="right" valign="top" nowrap="nowrap">{$order.formated_order_amount}</td>
							<td align="center" valign="top" nowrap="nowrap">{$lang.os[$order.order_status]},{$lang.ps[$order.pay_status]},{$lang.ss[$order.shipping_status]}</td>
						</tr>
						<!-- {foreachelse}-->
						<tr><td class="no-records" colspan="11">{$lang.no_records}</td></tr>
						<!-- {/foreach} -->
					</tbody>
				</table>
				<!-- {$order_list.page} -->	
			</div>
		</form>
	</div>
</div>
<!-- {/block} -->