<?php defined('IN_ECJIA') or exit('No permission resources.');?>
<!-- {extends file="ecjia.dwt.php"} -->
<!-- {block name="footer"} -->
<script type="text/javascript">
	ecjia.admin.visit_sold.init();
</script>
<!-- {/block} -->
<!-- {block name="main_content"} -->
	<!--访问购买率-->
	<div class="alert alert-info">
		<a class="close" data-dismiss="alert">×</a>
		<strong>注：</strong>{t}没有完成的订单不计入访问购买率{/t}
	</div>
	<div>
		<h3 class="heading">
			<!-- {if $ur_here}{$ur_here}{/if} -->
			<!-- {if $action_link} -->
			<a class="btn plus_or_reply" id="sticky_a" href='{$action_link.href}&cat_id={$cat_id}&brand_id={$brand_id}&show_num={$show_num}'><i class="fontello-icon-download"></i>{$action_link.text}</a>
		    <!-- {/if} -->
		</h3>
	</div>
	
	<div class="row-fluid batch">
		<div class="choose_list f_r">
			<form name="searchForm" action="{RC_Uri::url('orders/admin_visit_sold/init')}" method="post">
				<select class="w150" name="cat_id">
			      	<option value="0">请选择商品分类</option><!-- {$cat_list} -->
			    </select>
			    <select class="w150" name="brand_id">
			      	<option value="0">请选择商品品牌</option>
			      <!-- {html_options options=$brand_list selected=$brand_id} -->
	    		</select>
			    <input name="show_num" type="text" class="w70" value="{$show_num}" placeholder="显示数量" />
			    <input type="hidden" name="order_type" value="{$order_type}" />
			    <input type="submit" name="submit" value="查询" class="btn" />
			</form>
		</div>
	</div>
	
	<div class="row-fluid">
		<table class="table table-striped" id="smpl_tbl">
			<thead>
				<tr>
					<th class="w100">{t}排行{/t}</th>
					<th>{t}商品名称{/t}</th>
					<th class="w150">{t}人气指数{/t}</th>
					<th class="w150">{t}购买次数{/t}</th>
					<th class="w100">{t}访问购买率{/t}</th>
				</tr>
			</thead>
			<tbody>
			<!-- {foreach from=$click_sold_info.item key=Key item=list} -->
				<tr>
					<td>{$Key+1}</td>
					<td>
						{assign var =goods_url value=RC_Uri::url('goods/admin/preview',"id={$list.goods_id}")}
						<a href="{$goods_url}" target="_blank">{$list.goods_name}</a>
					</td>
					<td>{$list.click_count}</td>
					<td>{$list.sold_times}</td>
					<td>{$list.scale}</td>
				</tr>
			<!-- {foreachelse} -->
		    	<tr><td class="dataTables_empty" colspan="5">没有找到任何记录</td></tr>
		  	<!-- {/foreach} -->
			</tbody>
		</table>
		<!-- {$click_sold_info.page} -->
	</div>
<!-- {/block} -->