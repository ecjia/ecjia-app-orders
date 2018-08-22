<?php defined('IN_ECJIA') or exit('No permission resources.');?>
<!-- {extends file="ecjia.dwt.php"} -->

<!-- {block name="footer"} -->
<script type="text/javascript">
	ecjia.admin.order_stats.init();
</script>
<!-- {/block} -->

<!-- {block name="main_content"} -->
<div>
	<h3 class="heading">
		<!-- {if $ur_here}{$ur_here}{/if} -->
	</h3>
</div>

<div class="row-fluid batch">
	<form action="{RC_Uri::url('orders/admin_order_stats/init')}" name="searchForm" method="post">
		<div class="choose_list f_r">
			<input type="text" name="keywords" value="{$smarty.get.keywords}" placeholder="请输入商家名称关键字" />
			<button class="btn search-btn" type="button">搜索</button>
		</div>
	</form>
</div>

<div class="row-fluid">
	<div class="span12">
		<div class="row-fluid">
			<table class="table table-striped table-hide-edit">
				<thead>
					<tr>
						<th class="w180">商家名称</th>
						<th>下单总数</th>
						<th>下单总金额</th>
						<th>成交订单数</th>
						<th>成交总金额</th>
						<th class="w80">店铺排行</th>
					</tr>
				</thead>
				<tbody>
					<!-- {foreach from=$list.item key=key item=val} -->
					<tr>
						<td class="hide-edit-area">
							{$val.merchants_name}
							<div class="edit-list">
								<a class="data-pjax" href='{url path="orders/admin_order_stats/stats" args="store_id={$val.store_id}"}'>查看统计</a>
							</div>
						</td>
						<td>{$val.total_order}</td>
						<td>{$val.formated_total_amount}</td>
						<td>{$val.valid_order}</td>
						<td>{$val.formated_valid_amount}</td>
						<td>{$val.level}</td>
					</tr>
					<!-- {foreachelse}-->
					<tr>
						<td class="no-records" colspan="6">{lang key='system::system.no_records'}</td>
					</tr>
					<!-- {/foreach} -->
				</tbody>
			</table>
			<!-- {$list.page} -->
		</div>
	</div>
</div>
<!-- {/block} -->