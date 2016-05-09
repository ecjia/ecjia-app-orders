<?php defined('IN_ECJIA') or exit('No permission resources.');?>
<!-- {extends file="ecjia.dwt.php"} -->
<!-- {block name="footer"} -->
<script type="text/javascript">
	ecjia.admin.sale_list.init()
</script>
<!-- {/block} -->
<!-- {block name="main_content"} -->
<!--销售明细-->
<div class="alert alert-info">
	<a class="close" data-dismiss="alert">×</a>
	<strong>注：</strong>{t}没有完成的订单不计入销售明细{/t}
</div>
<div>
	<h3 class="heading">
		<!-- {if $ur_here}{$ur_here}{/if} -->
		<!-- {if $action_link} --><a class="btn plus_or_reply"  id="sticky_a" href="{$action_link.href}&start_date={$start_date}&end_date={$end_date}"><i class="fontello-icon-download"></i>{t}{$action_link.text}{/t}</a><!-- {/if} -->
	</h3>
</div>

<div class="row-fluid">
	<div class="choose_list f_r">
		<form class="f_r" action="{$search_action}"  method="post" name="theForm">
			<span>按时间段查询：</span>
			<input class="start_date f_l w110" name="start_date" type="text" placeholder="开始时间" value="{$start_date}">
			<span class="f_l">-</span>
			<input class="end_date f_l w110" name="end_date" type="text" placeholder="结束时间" value="{$end_date}">
			<input class="btn screen-btn" type="submit" value="搜索">
		</form>
	</div>
</div>

<div class="row-fluid">
	<table class="table table-striped" id="smpl_tbl">
		<thead>
			<tr style="border-bottom:1px solid #ddd;">
				<th>{t}商品名称{/t}</th>
				<th class="w200">{t}订单号{/t}</th>
				<th class="w70">{t}数量{/t}</th>
				<th class="w120">{t}售价{/t}</th>
				<th class="w110">{t}售出日期{/t}</th>
			</tr>
		</thead>
		<tbody>
		<!-- {foreach from=$sale_list_data.item key=key item=list} -->
			<tr>
				<td>
					{assign var =goods_url value=RC_Uri::url('goods/admin/preview',"id={$list.goods_id}")}
					<a href="{$goods_url}" target="_blank">{$list.goods_name}</a>
				</td>
				<td>{$list.order_sn}</td>
				<td>{$list.goods_num}</td>
				<td>{$list.sales_price}</td>
				<td>{$list.sales_time}</td>
			</tr>
		<!-- {foreachelse} -->
	    	<tr><td class="dataTables_empty" colspan="5">没有找到任何记录</td></tr>
	  	<!-- {/foreach} -->
		</tbody>
	</table>
	<!-- {$sale_list_data.page} -->
</div>
<!-- {/block} -->