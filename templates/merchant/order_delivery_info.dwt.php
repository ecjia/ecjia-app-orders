<?php defined('IN_ECJIA') or exit('No permission resources.');?>
<!-- {extends file="ecjia-merchant.dwt.php"} -->

<!-- {block name="footer"} -->
<script type="text/javascript">
	ecjia.merchant.order_delivery.info();
</script>
<!-- {/block} -->

<!-- {block name="home-content"} -->
<div>
	<h3 class="page-header">
		<!-- {if $ur_here}{$ur_here}{/if} -->
	</h3>
</div>

<div class="row-fluid">
	<div class="span12">
		<form class="form-horizontal" action="{$form_action}" method="post" name="deliveryForm">
			<div id="accordion2" class="panel panel-default">
			     <div class="panel-heading">
                    <a data-toggle="collapse" data-parent="#accordion" href="#collapseOne">
                        <h4 class="panel-title">
                            <strong>基本信息</strong>
                        </h4>
                    </a>
                </div>
				<div class="accordion-body in collapse" id="collapseOne">
					<table class="table table-oddtd m_b0">
						<tr>
							<td><div align="right"><strong>订单号：</strong></div></td>
							<td>
								{$order.order_sn}
							</td>
							<td><div align="right"><strong>下单时间：</strong></div></td>
							<td>{$order.formated_add_time}</td>
						</tr>
						<tr>
							<td><div align="right"><strong>购货人：</strong></div></td>
							<td>{$order.user_name}</td>
							<td><div align="right"><strong>缺货处理：</strong></div></td>
							<td>{$order.how_oos}</td>
						</tr>
						<tr>
							<td><div align="right"><strong>配送方式：</strong></div></td>
							<td>
								<!-- {if $exist_real_goods} -->
									<!-- {if $order.shipping_id > 0} -->
										{$order.shipping_name}
										<a class="data-pjax" href='{url path="orders/merchant/edit_shipping" args="order_id={$order.order_id}{if $action_note}&action_note={$action_note}{/if}"}'>{t domain="orders"}编辑{/t}</a>
										<div style="margin-top:10px;color:#777;float:left;">注：修改配送方式，额外产生的配送费用不做修改</div>
									<!-- {else} -->
										*
									<!-- {/if} -->
									<!-- {if $order.insure_fee > 0} -->
										（保价费用：{$order.formated_insure_fee}）
									<!-- {/if} -->
								<!-- {/if} -->
							</td>
							<td><div align="right"><strong>配送费用：</strong></div></td>
							<td>{$order.formated_shipping_fee}</td>
						</tr>
						<tr>
							<td><div align="right"><strong>是否保价：</strong></div></td>
							<td>{if $insure_yn}是{else}否{/if}</td>
							<td><div align="right"><strong>保价费用：</strong></div></td>
							<td>{$order.formated_shipping_fee|default:0.00}</td>
						</tr>
					</table>
				</div>
			</div>
			
			<div id="accordion2" class="panel panel-default">
			     <div class="panel-heading">
                    <a data-toggle="collapse" data-parent="#accordion" href="#collapseTwo">
                        <h4 class="panel-title">
                            <strong>收货人信息</strong>
                        </h4>
                    </a>
                </div>
				<div class="accordion-body in collapse" id="collapseTwo">
					<table class="table table-oddtd m_b0">
						<tr>
							<td><div align="right"><strong>收货人：</strong></div></td>
							<td>{$order.consignee|escape}</td>
							<td><div align="right"><strong>手机：</strong></div></td>
							<td>{$order.mobile|escape}</td>
						</tr>
						<tr>
							<td><div align="right"><strong>地址：</strong></div></td>
							<td>[{$order.region}] {$order.address|escape}</td>
							<td><div align="right"><strong>最佳送货时间：</strong></div></td>
							<td>
							{if $shipping_code eq 'ship_o2o_express'}
									{$order.expect_shipping_time|escape}
								{elseif $shipping_code eq 'ship_ecjia_express'}
									{$order.expect_shipping_time|escape}
								{else}
									{$order.best_time|escape}
								{/if}
							</td>
						</tr>
						<tr>
							<td><div align="right"><strong>订单备注：</strong></div></td>
							<td colspan="3">{$order.postscript}</td>
						</tr>
					</table>
				</div>
			</div>
			
			<div id="accordion2" class="panel panel-default">
			     <div class="panel-heading">
                    <a data-toggle="collapse" data-parent="#accordion" href=#collapseThree>
                        <h4 class="panel-title">
                            <strong>商品信息</strong>
                        </h4>
                    </a>
                </div>
				<div class="accordion-body in collapse" id="collapseThree">
					<table class="table table-striped m_b0">
						<thead>
							<tr>
								<th class="sorting"><div><strong>商品名称 [ 品牌 ]</strong></div></th>
								<th><div><strong>货号</strong></div></th>
								<th ckass="w110"><div><strong>货品号</strong></div></th>
								<th><strong>属性</strong></th>
								<!-- {if $suppliers_list neq 0} -->
								<th><strong>供货商</strong></th>
								<!-- {/if} -->
								<th><strong>库存</strong></th>
								<th><div><strong>数量</strong></div></th>
								<th><div><strong>已发货数量</strong></div></th>
								<th class="w130"><div><strong>此单发货数量</strong></div></th>
							</tr>
						</thead>
						<tbody>
							<!-- {foreach from=$goods_list item=goods} -->
							<!--礼包-->
							<!-- {if $goods.goods_id gt 0 && $goods.extension_code eq 'package_buy'} -->
							<tr>
								<td>{$goods.goods_name}<span class="ecjiafc-FF0000">（礼包）</span></td>
								<td>{$goods.goods_sn}</td>
								<td>&nbsp;<!--货品货号--></td>
								<td>&nbsp;<!--属性--></td>
								<!-- {if $suppliers_list neq 0} -->
								<td><div></div></td>
								<!-- {/if} -->
								<td><div></div></td>
								<td><div>{$goods.goods_number}</div></td>
								<td><div></div></td>
								<td><div></div></td>
							</tr>
							<!-- {foreach from=$goods.package_goods_list item=package} -->
							<tr>
								<td>
									--&nbsp;<a href='{url path="goods/merchant/preview" args="id={$package.goods_id}"}' target="_blank">{$package.goods_name}</a>
								</td>
								<td>{$package.goods_sn}</td>
								<td>{$package.product_sn}</td>
								<td>{$package.goods_attr_str}</td>
								<!-- {if $suppliers_list neq 0} -->
								<td><div>{$suppliers_name[$package.suppliers_id]}</div></td>
								<!-- {/if} -->
								<td><div>{$package.storage}</div></td>
								<td><div>{$package.order_send_number}</div></td>
								<td><div>{$package.sended}</div></td>
								<td><div><input name="send_number[{$goods.rec_id}][{$package.g_p}]" type="text" id="send_number_{$goods.rec_id}_{$package.g_p}" value="{$package.send}" class="w50" {$package.readonly}/></div></td>
							</tr>
							<!-- {/foreach} -->
							<!-- {else} -->
							<tr>
								<td>
									<!-- {if $goods.goods_id gt 0 && $goods.extension_code neq 'package_buy'} -->
									<a href='{url path="goods/merchant/preview" args="id={$goods.goods_id}"}' target="_blank">{$goods.goods_name} {if $goods.brand_name}[ {$goods.brand_name} ]{/if}{if $goods.is_gift}{if $goods.goods_price > 0}（特惠品）{else}（赠品）{/if}{/if}{if $goods.parent_id > 0}（配件）{/if}</a>
									<!-- {/if} -->
								</td>
								<td>{$goods.goods_sn}</td>
								<td>{$goods.product_sn}</td>
								<td>{$goods.goods_attr|nl2br}</td>
								<!-- {if $suppliers_list neq 0} -->
								<td><div>{$suppliers_name[$goods.suppliers_id]}</div></td>
								<!-- {/if} -->
								<td><div>{$goods.storage}</div></td>
								<td><div>{$goods.goods_number}</div></td>
								<td><div>{$goods.sended}</div></td>
								<td><div class="order-query"><input class="form-control" name="send_number[{$goods.rec_id}]" type="text" id="send_number_{$goods.rec_id}" value="{$goods.send}" class="w50" {$goods.readonly}/></div></td>
							</tr>
							<!-- {/if} -->
							<!-- {/foreach} -->
						</tbody>
					</table>
				</div>11
			</div>
			
			<div id="accordion2" class="panel panel-default">
			     <div class="panel-heading">
                    <a data-toggle="collapse" data-parent="#accordion" href=#collapseFour>
                        <h4 class="panel-title">
                            <strong>操作信息</strong>
                        </h4>
                    </a>
                </div>
				<div class="accordion-body in collapse" id="collapseFour">
					<table class="table table-oddtd m_b0">
						<tbody class="first-td-no-leftbd">
							<!-- {if $suppliers_list neq 0} -->
							<tr> 
								<td width="15%"><div align="right"><strong>选择供货商：</strong></div></td> 
								<td colspan="3">
									<select name="suppliers_id" id="suppliers_id">
										<option value="0" selected="selected">不指定供货商本店自行处理</option>
										<!-- {foreach from=$suppliers_list item=suppliers} -->
										<option value="{$suppliers.suppliers_id}">{$suppliers.suppliers_name}</option>
										<!-- {/foreach} -->
									</select>
								</td>
							</tr>
							<!-- {/if} -->
							<tr>
								<td><div align="right"><strong>操作备注：</strong></div></td> 
								<td colspan="3">
									<textarea name="action_note" class="span10 form-control" cols="80" rows="3">{$action_note}</textarea>
								</td>
							</tr>
							<tr>
								<td><div align="right"><strong>{t}当前可执行操作：{/t}</strong></div></td> 
								<td colspan="3">
									<button class="btn btn-info" type="submit" name="delivery_confirmed">确认生成发货单</button>
									<a href='{url path="orders/merchant/info" args="order_id={$order_id}"}'>
									<button class="btn btn-info" type="button">取消</button>
									</a>

									<input name="order_id" type="hidden" value="{$order.order_id}">
									<input name="delivery[order_sn]" type="hidden" value="{$order.order_sn}">
									<input name="delivery[add_time]" type="hidden" value="{$order.order_time}">
									<input name="delivery[user_id]" type="hidden" value="{$order.user_id}">
									<input name="delivery[how_oos]" type="hidden" value="{$order.how_oos}">
									<input name="delivery[shipping_id]" type="hidden" value="{$order.shipping_id}">
									<input name="delivery[shipping_fee]" type="hidden" value="{$order.shipping_fee}">

									<input name="delivery[consignee]" type="hidden" value="{$order.consignee}">
									<input name="delivery[address]" type="hidden" value="{$order.address}">
									<input name="delivery[country]" type="hidden" value="{$order.country}">
									<input name="delivery[province]" type="hidden" value="{$order.province}">
									<input name="delivery[city]" type="hidden" value="{$order.city}">
									<input name="delivery[district]" type="hidden" value="{$order.district}">
									<input name="delivery[street]" type="hidden" value="{$order.street}">
									<input name="delivery[sign_building]" type="hidden" value="{$order.sign_building}">
									<input name="delivery[email]" type="hidden" value="{$order.email}">
									<input name="delivery[zipcode]" type="hidden" value="{$order.zipcode}">
									<input name="delivery[tel]" type="hidden" value="{$order.tel}">
									<input name="delivery[mobile]" type="hidden" value="{$order.mobile}">
									<input name="delivery[best_time]" type="hidden" value="{$order.best_time}">
									<input name="delivery[postscript]" type="hidden" value="{$order.postscript}">

									<input name="delivery[how_oos]" type="hidden" value="{$order.how_oos}">
									<input name="delivery[insure_fee]" type="hidden" value="{$order.insure_fee}">
									<input name="delivery[shipping_fee]" type="hidden" value="{$order.shipping_fee}">
									<input name="delivery[agency_id]" type="hidden" value="{$order.agency_id}">
									<input name="delivery[shipping_name]" type="hidden" value="{$order.shipping_name}">
									<input name="operation" type="hidden" value="{$operation}">
								</td>
							</tr>
							<tr>
								<td width="15%"><div align="right"> <strong>操作说明：</strong></div></td>
								<td colspan="3">
									【确认生成发货单】确认生成该订单的发货单；<br>
									【取消】取消生成发货单操作，返回到上一步；
								</td>
							</tr>
						</tbody>
					</table>
				</div>
			</div>
		</form>
	</div>
</div>
<!-- {/block} -->