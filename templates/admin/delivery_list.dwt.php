<?php defined('IN_ECJIA') or exit('No permission resources.');?>
<!-- {extends file="ecjia.dwt.php"} -->

<!-- {block name="footer"} -->
<script type="text/javascript">
	ecjia.admin.order_delivery.init();
</script>
<!-- {/block} -->

<!-- {block name="main_content"} -->
<div>
	<h3 class="heading">
		<!-- {if $ur_here}{$ur_here}{/if} -->
	</h3>
</div>
<!-- #BeginLibraryItem "/library/order_consignee.lbi" --><!-- #EndLibraryItem -->
<div class="row-fluid">
	<div class="choose_list span12">
		<form action="{$search_action}" name="searchForm" method="post">
			<div class="btn-group f_l m_r5">
				<a class="btn dropdown-toggle" data-toggle="dropdown" href="#">
					<i class="fontello-icon-cog"></i>{t}批量操作{/t}
					<span class="caret"></span>
				</a>
				<ul class="dropdown-menu">
					<li><a class="batch-del-btn" data-toggle="ecjiabatch" data-name="delivery_id" data-idClass=".checkbox:checked" data-url="{$form_action}" data-msg="您确定需要删除这些发货单吗？" data-noSelectMsg="请选择需要操作的发货单！" href="javascript:;"><i class="fontello-icon-trash"></i>{$lang.remove}</a></li>
				</ul>
			</div>
			<select class="down-menu good_br w100" name="status" id="select-rank">
				<option value="-1">{$lang.select_please}</option>
				<!-- {html_options options=$lang.delivery_status selected=$filter.status} -->
			</select>
			<a class="btn m_l5 screen-btn">{t}筛选{/t}</a>
			<div class="choose_list f_r" >
				<input type="text" name="delivery_sn"  value="{$filter.delivery_sn}"  placeholder="请输入发货单流水号"/>
				<input type="text" name="keywords" value="{$filter.keywords}" placeholder="请输入订单号或者收货人"/>
				<button class="btn" type="submit">{t}搜索{/t}</button>
			</div>
		</form>
	</div>
</div>	
<div class="row-fluid">
	<div class="span12">
		<form method="post" action="{$form_action}" name="listForm">
			<div class="row-fluid">
				<table class="table table-striped table-hide-edit">
					<thead>
						<tr>
							<th class="table_checkbox"><input type="checkbox" data-toggle="selectall" data-children=".checkbox"/></th>
							<th>{$lang.label_delivery_sn}</th>
							<th>{$lang.order_sn}</th>
							<th>{$lang.label_add_time}</th>
							<th>{$lang.consignee}</th>
							<th>{$lang.label_update_time}</th>
							<th>供货商</th>
							<th>{$lang.label_delivery_status}</th>
							<th>{$lang.operator}</th>
						</tr>
					</thead>
					<tbody>
						<!-- {foreach from=$delivery_list.delivery item=delivery key=dkey} -->
						<tr>
							<td valign="top" nowrap="nowrap"><input type="checkbox" class="checkbox" name="delivery_id[]"  value="{$delivery.delivery_id}" /></td>
							<td class="hide-edit-area">
								{$delivery.delivery_sn}
								<div class="edit-list">
									<a class="data-pjax" href='{url path="orders/admin_order_delivery/delivery_info" args="delivery_id={$delivery.delivery_id}"}' title="{$lang.detail}">{t}详细信息{/t}</a>&nbsp;|&nbsp; 
									<a class="ajaxremove ecjiafc-red" data-toggle="ajaxremove" data-msg='{t name="{$delivery.delivery_sn}"}您确定要删除发货单[ %1 ]吗？{/t}' href='{url path="orders/admin_order_delivery/remove" args="delivery_id={$delivery.delivery_id}"}' title="{t}移除{/t}">{t}移除{/t}</a>
								</div>
							</td>
							<td><a href='{url path="orders/admin/info" args="order_id={$delivery.order_id}"}' target="_blank" title="{t}查看订单{/t}">{$delivery.order_sn}</a></td>
							<td>{$delivery.add_time}</td>							
							<td><a class="cursor_pointer consignee_info" data-url='{url path="orders/admin_order_delivery/consignee_info" args="delivery_id={$delivery.delivery_id}"}' title="{t}显示收货人信息{/t}">{$delivery.consignee|escape}</a></td>
							<td>{$delivery.update_time}</td>
							<td>{$delivery.suppliers_name}</td>
							<td>{$delivery.status_name}</td>
							<td>{$delivery.action_user}</td>
						</tr>
					   <!-- {foreachelse}-->
						<tr><td class="no-records" colspan="11">{t}没有找到任何数据{/t}</td></tr>
						<!-- {/foreach} -->
					</tbody>
				</table> 
				<!-- {$delivery_list.page} -->
			</div>
		</form>
	</div>
</div>
<!-- {/block} -->